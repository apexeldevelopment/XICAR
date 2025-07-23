<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 25/5/23
 * Time: 5:23 PM
 */

namespace App\Http\Controllers\PaymentMethods\ViuPay;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Traits\PaymentNotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ViuPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait, PaymentNotificationTrait;

    protected $BASE_URL = "https://api.viupay.co.ke";

    public function getToken($client_id, $client_secret)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->BASE_URL . '/oauth/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('grant_type' => 'client_credentials', 'client_id' => $client_id, 'client_secret' => $client_secret, 'scope' => ''),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json'
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

    public function getPaymentMethods(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug", "VIU_PAY")->first();
            if (empty($payment_option)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }
            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if (empty($payment_option_config)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }
            $token = $this->getToken($payment_option_config->api_public_key, $payment_option_config->api_secret_key);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL . '/api/paymentMethods',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Authorization: Bearer ' . $token
                ),
            ));
            $response = curl_exec($curl);
            $info = curl_getinfo($curl);
            curl_close($curl);
            $data = [];
            if (isset($info['http_code']) && $info['http_code'] == 200) {
                $response = json_decode($response, true);
                foreach ($response as $item) {
                    if ($item['enabled'] == true) {
                        unset($item['enabled']);
                        array_push($data, $item);
                    }
                }
            } else {
                throw new \Exception($response);
            }
            return $this->successResponse(trans("$string_file.success"), $data);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function getPaymentOption(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'slug' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $column = ($request->for == "USER") ? "user_id" : "driver_id";
            $methods = DB::table("viupay_payment_instruments")->where(array($column => $user->id, "for" => $request->for, "slug" => $request->slug))->get();
            $data = [];
            foreach ($methods as $method) {
                array_push($data, array(
                    "pay_option_id" => $method->id,
                    "slug" => $method->slug,
                    "name" => ($method->slug == "card_hosted") ? "Card" : "Mpesa",
                    "detail" => !empty($method->detail) ? $method->detail : "",
                ));
            }
            return $this->successResponse(trans("$string_file.success"), $data);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function setPaymentOption(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'slug' => 'required',
            'phone_number' => 'required_if:slug,mpesa-lib',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);

            $payment_option = PaymentOption::where("slug", "VIU_PAY")->first();
            if (empty($payment_option)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }
            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if (empty($payment_option_config)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }
            $token = $this->getToken($payment_option_config->api_public_key, $payment_option_config->api_secret_key);

            $data = array(
                'type' => $request->slug,
                'usage' => 'reusable',
                'owner_email' => $user->email,
                'owner_name' => $user->first_name,
                'owner_phone' => $request->for == "USER" ? $user->UserPhone : $user->phoneNumber,
                'customer_id' => $user->id,
            );
            if ($request->slug == "mobile_money") {
                $data['telco'] = "safaricom_ke";
                $data['msisdn'] = $request->phone_number;
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL . '/api/payInstruments',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Authorization: Bearer ' . $token
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);
            if (isset($response['code']) && $response['code'] == 201) {
                $column = ($request->for == "USER") ? "user_id" : "driver_id";
                DB::table("viupay_payment_instruments")->insert([
                    $column => $user->id,
                    "for" => $request->for,
                    "slug" => $request->slug,
                    "payment_instrument_token" => $response['token'],
                    "detail" => $request->phone_number
                ]);
            } else {
                throw new \Exception(json_encode($response));
            }
            DB::commit();
            return $this->successResponse(trans("$string_file.success"));
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'pay_option_id' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug", "VIU_PAY")->first();
            if (empty($payment_option)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if (empty($payment_option_config)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_instrument = DB::table("viupay_payment_instruments")->find($request->pay_option_id);

            $transaction = Transaction::create(array(
                'status' => ($request->for == "USER") ? 1 : 2, // for user
                'user_id' => $request->for == "USER" ? $user->id : NULL,
                'driver_id' => $request->for == "DRIVER" ? $user->id : NULL,
                'merchant_id' => $user->merchant_id,
                'payment_option_id' => $payment_option->id,
                'checkout_id' => NULL,
                'booking_id' => NULL,
                'order_id' => NULL,
                'handyman_order_id' => NULL,
                'reference_id' => $user->id . $user->merchant_id . time(),
                'amount' => $request->amount, // amount
                'request_status' => 1,
                'status_message' => "PENDING",
            ));

            $data = [
                'payment_request' =>
                    array(
                        'statement_descriptor' => $user->Merchant->BusinessName . " Payment",
                        'pay_instrument' => $payment_instrument->payment_instrument_token,
                        'invoice_number' => "$transaction->id",
                        'currency' => "KES", //user->Country->isoCode,
                        'amount' => $request->amount,
                        "receipt_email" => $user->email,
                        "receipt_phone" => $request->for == "USER" ? $user->UserPhone : $user->phoneNumber,
                        "billing" => "charge_automatically",
                        "redirect_url" => route("paymentcomplate")
                    )
            ];

            $token = $this->getToken($payment_option_config->api_public_key, $payment_option_config->api_secret_key);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL . '/api/payReqs',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);
            if (isset($response['code']) && $response['code'] == 201) {
                DB::commit();
                return $this->successResponse(trans("$string_file.success"), array(
                    "transaction_id" => $transaction->id,
                    "hosted_payment" => isset($response['payment_request']['attributes']['hosted_payment']) ? $response['payment_request']['attributes']['hosted_payment']['link'] : ""
                ));
            } else {
                $transaction->delete();
                throw new \Exception(isset($response['message']) ? $response['message'] : json_encode($response));
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function callback(Request $request)
    {
        try {
            $response = array(
                'request' => $request->all(),
            );
            \Log::channel('viupay_log')->emergency($response);


            $data = $request->all();
            $transaction = Transaction::find($data['payment_request']['invoice_number']);
            if ($data['payment_request']['status'] != "failed") {
                if (!empty($transaction) && $transaction->request_status == 1) {
                    if ($transaction->status == 1) { // If User
                        $paramArray = array(
                            'user_id' => $transaction->user_id,
                            'booking_id' => NULL,
                            'amount' => $transaction->amount,
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
                            'amount' => $transaction->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'receipt' => rand(1000, 9999),
                        );
                        WalletTransaction::WalletCredit($paramArray);
                    }
                    $transaction->payment_transaction_id = $data['payment_request']['id'];
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
            \Log::channel('viupay_log')->emergency($response);
            return "OK";
        }
    }

//     public function checkPayment(Request $request){
//         $validator = Validator::make($request->all(), [
//             'for' => 'required|IN:USER,DRIVER',
//             'transaction_id' => 'required|exists:transactions,id'
//         ]);
//         if ($validator->fails()) {
//             $errors = $validator->messages()->all();
//             return $this->failedResponse($errors[0]);
//         }
//         try{
//             $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
//             $string_file = $this->getStringFile(null, $user->Merchant);
//             $transaction = Transaction::find($request->transaction_id);
//             if($transaction->request_status == 2){
//                 $result = "SUCCESS";
//             }elseif($transaction->request_status == 1){
//                 $result = "PENDING";
//             }else{
//                 $result = "FAILED";
//             }
//             return $this->successResponse(trans("$string_file.success"),array("result" => $result));
//         }catch (\Exception $exception){
//             return $this->failedResponse($exception->getMessage());
//         }
//     }
}
