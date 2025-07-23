<?php

namespace App\Http\Controllers\PaymentMethods\CashPay;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\Transaction;
use App\Traits\PaymentNotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Traits\ContentTrait;
use Twilio\Jwt\JWT;

class CashPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait, ContentTrait, PaymentNotificationTrait;

    protected $BASE_URL = "https://sandbox.semoa-payments.com/api";

    public function __construct(){
    }

    public function getToken($client_id, $client_secret, $username, $password)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->BASE_URL . '/auth',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(array('client_id' => $client_id, 'client_secret' => $client_secret, 'username' => $username, 'password' => $password)),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        if (isset($info['http_code']) && $info['http_code'] == 200) {
            $response = json_decode($response);
            return $response->access_token;
        } else {
            throw new \Exception($response);
        }
    }

    public function PaymentUrl($request, $payment_option_config, $calling_from)
    {
        DB::beginTransaction();
        try {
//            $client_id = $payment_option_config->api_public_key;
//            $client_secret = $payment_option_config->api_secret_key;
//            $additional_data = json_decode($payment_option_config->additional_data,true);
//            $username = $additional_data['username'];
//            $password = $additional_data['password'];

//            $token = $this->getToken($client_id, $client_secret, $username, $password);

            // check whether request is from driver or user
            if($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $code = $driver->Country->phonecode;
                $status = 2;
                $currency = $driver->Country->isoCode;
                $phone_number = str_replace($code,"",$driver->phoneNumber);
                $first_name = $driver->first_name;
                $last_name = $driver->last_name;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "Driver wallet recharge";
            } else {
                $user = $request->user('api');
                $code = $user->Country->phonecode;
                $status = 1;
                $currency = $user->Country->isoCode;
                $phone_number = str_replace($code,"",$user->UserPhone);
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "Payment from user";
            }

            $transaction_id = $id.'_'.time();
            $amount = $request->amount;

            $transaction = new Transaction();
            $transaction->status = $status;
            $transaction->card_id = NULL;
            $transaction->user_id = $calling_from == "USER" ? $id : NULL;
            $transaction->driver_id = $calling_from == "DRIVER" ? $id : NULL;
            $transaction->merchant_id = $merchant_id;
            $transaction->payment_option_id = $payment_option_config->payment_option_id;
            $transaction->checkout_id = NULL;
            $transaction->amount = $currency.' '.(int)$amount;
            $transaction->payment_transaction_id = $transaction_id;
            $transaction->payment_transaction = NULL;
            $transaction->request_status = 1;
            $transaction->save();

            $fields = array(
                'amount' => (int)$request->amount,
                'description' => $description,
                'client' => array(
                    "lastname" => $last_name,
                    "firstname" => $first_name,
                    "phone" => "+22890112783" //$phone_number
                ),
                'callback_url' => route("cashpay.callback",$transaction->id),
                'redirect_url' => route('paymentredirect')
            );

            $salt = time();
            $login = $payment_option_config->api_public_key; // "demo.pikipiki";
            $api_key = $payment_option_config->api_secret_key; // "sVTb379gjHD1zgGmv6MfEz8Sf4aMnAAbHm24";
            $someup = $login.$api_key.$salt;
            $api_secure = hash("sha256", $someup);
            $header = array(
                'login: demo.pikipiki',
                "apisecure: $api_secure",
                'apireference: 31',
                "salt: $salt",
                'Content-Type: application/json'
            );
// p($fields,0);
// p("https://sandbox.semoa-payments.com/api/orders", 0);
// p($header,0);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://sandbox.semoa-payments.com/api/orders',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($fields),
                //   '{
                // 	"amount": 100,
                // 	"description": "Test environnement Sandbox",
                // 	"callback_url": "",
                // 	"redirect_url": "https://msprojects.apporioproducts.com/multi-service-v3/public/api/paymentredirect",
                // 	"client": {
                // 		"lastname": "LAKIGNAN",
                // 		"firstname": "Sonia Sika",
                // 		"phone": "+22890112783"
                // 	}

                // }',
                CURLOPT_HTTPHEADER => $header,
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response);
            // p($response);
            if(isset($response->status) && $response->status != 'success') {
                throw new \Exception($response->message);
            }elseif(isset($response->code) && $response->code == 500){
                throw new \Exception($response->message);
            }

            $transaction->payment_transaction = $response->order_reference;
            $transaction->save();
            DB::commit();
            return [
                'status' => 'NEED_TO_OPEN_WEBVIEW',
                'url' => $response->bill_url ? $response->bill_url : "",
                'transaction_id' => $transaction->id,
                'redirect_url' => route('paymentredirect')
            ];
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function paymentStatus(Request $request, $payment_option_config)
    {
//        $client_id = $payment_option_config->api_public_key;
//        $client_secret = $payment_option_config->api_secret_key;
//        $additional_data = json_decode($payment_option_config->additional_data,true);
//        $username = $additional_data['username'];
//        $password = $additional_data['password'];

//            $token = $this->getToken($client_id, $client_secret, $username, $password);

        $transaction =  Transaction::find($request->transaction_id);

        $salt = time();
        $login = $payment_option_config->api_public_key; // "demo.pikipiki";
        $api_key = $payment_option_config->api_secret_key; // "sVTb379gjHD1zgGmv6MfEz8Sf4aMnAAbHm24";
        $someup = $login.$api_key.$salt;
        $api_secure = hash("SHA256", $someup);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->BASE_URL."/orders/".$transaction->payment_transaction."/status",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "login: $login",
                "apisecure: $api_secure",
                "apireference: 31",
                "salt: $salt",
            ),
        ));
        $response = json_decode(curl_exec($curl));
        curl_close($curl);

        $payment_status = false;
        $request_status_text = "";
        if(isset($response->state) && $response->state == 'Paid') {
            $transaction->request_status = 2;
            $transaction->save;
            $payment_status = true;
            $request_status_text = "SUCCESS";
        }elseif(isset($response->state) && $response->state == "Pending"){
            $request_status_text = "PENDING";
        }else{
            $request_status_text = "FAILED";
        }
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
    }

    public function callback(Request $request, $transaction_id)
    {
        try {
            $transaction = Transaction::find($transaction_id);
            $option = PaymentOption::select('slug', 'id')->where('id', $transaction->payment_option_id)->first();
            $payment_option_config  = $option->PaymentOptionConfiguration;
            $data = JWT::decode($request->token, $payment_option_config->api_secret_key, 'HS256');

            $response = array(
                'token' => $request->token,
                'request' => $data,
            );
            \Log::channel('cashpay_log')->emergency($response);

            if ($data->state == "Paid") {
                if (!empty($transaction) && $transaction->request_status == 1) {
                    if ($transaction->status == 1) { // If User
                        $paramArray = array(
                            'user_id' => $transaction->user_id,
                            'booking_id' => NULL,
                            'amount' => explode(" ",$transaction->amount)[1],
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'payment_option_id' => $transaction->payment_option_id,
                        );
                        WalletTransaction::UserWalletCredit($paramArray);
                    } else {
                        $paramArray = array(
                            'driver_id' => $transaction->driver_id,
                            'booking_id' => NULL,
                            'amount' => explode(" ",$transaction->amount)[1],
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'receipt' => rand(1000, 9999),
                        );
                        WalletTransaction::WalletCredit($paramArray);
                    }
                    $transaction->payment_transaction_id = $data->order_reference;
                    $transaction->request_status = 2; // SUCCESS
                    $transaction->status_message = "SUCCESS";
                    $transaction->save();

                    $for = $transaction->status == 1 ? "USER" : "DRIVER";
                    $receiver_id  = $transaction->status == 1 ? $transaction->user_id : $transaction->driver_id;
                    $this->paymentSuccessNotification($for, $receiver_id, $transaction->merchant_id, "Payment Success", "Payment Success");
                }
            } else {
                $transaction->request_status = 3; // FAILED
                $transaction->status_message = "FAILED";
                $transaction->save();
                $for = $transaction->status == 1 ? "USER" : "DRIVER";
                $receiver_id  = $transaction->status == 1 ? $transaction->user_id : $transaction->driver_id;
                $this->paymentFailedNotification($for, $receiver_id, $transaction->merchant_id, "Payment Failed", "Payment Failed");
            }
        } catch (\Exception $exception) {
            $response = array(
                'request' => $data,
                'error' => $exception->getMessage(),
                'detail' => $exception->getTraceAsString(),
            );
            \Log::channel('cashpay_log')->emergency($response);
            return "OK";
        }
    }
}
