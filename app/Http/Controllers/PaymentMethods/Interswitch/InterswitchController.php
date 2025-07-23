<?php

namespace App\Http\Controllers\PaymentMethods\Interswitch;

include(public_path("Interswitch-1.0.19/Crypt/RSA.php"));
include(public_path("Interswitch-1.0.19/Math/BigInteger.php"));

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Driver;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use DB;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;

class InterswitchController extends Controller
{
    use MerchantTrait, ApiResponseTrait;

    private $public_modulus = "009c7b3ba621a26c4b02f48cfc07ef6ee0aed8e12b4bd11c5cc0abf80d5206be69e1891e60fc88e2d565e2fabe4d0cf630e318a6c721c3ded718d0c530cdf050387ad0a30a336899bbda877d0ec7c7c3ffe693988bfae0ffbab71b25468c7814924f022cb5fda36e0d2c30a7161fa1c6fb5fbd7d05adbef7e68d48f8b6c5f511827c4b1c5ed15b6f20555affc4d0857ef7ab2b5c18ba22bea5d3a79bd1834badb5878d8c7a4b19da20c1f62340b1f7fbf01d2f2e97c9714a9df376ac0ea58072b2b77aeb7872b54a89667519de44d0fc73540beeaec4cb778a45eebfbefe2d817a8a8319b2bc6d9fa714f5289ec7c0dbc43496d71cf2a642cb679b0fc4072fd2cf";
    private $public_exponent = "010001";

    public function paymentInitiate(Request $request, $check_for)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required',
                'card_type' => 'required|in:visa,master,verve',
                'pan' => 'required',
                'cvv' => 'required',
                'pin' => 'required_if:card_type,master,verve',
                'year' => 'required',
                'month' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                throw new \Exception($errors[0]);
            }

            $return_data = [];

            $payer = ($check_for == "user") ? $request->user('api') : $request->user('api-driver');
            $customerId = empty($payer->email) ? ($check_for == "user") ? $payer->UserPhone : $payer->phoneNumber : $payer->email;

            $payment_option = PaymentOption::where('slug', 'INTERSWITCH')->first();
            $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $payer->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();

            $base_url = ($payment_option_config->gateway_condition == 1) ? "https://api.interswitchng.com" : "https://payment-service.k8.isw.la";

            $transactionRef = "AVA-".rand(100, 999)."0".time();

            // Get Access Token
            $access_token_param = $payment_option_config->auth_token;
            $access_token = $this->generateAccessToken($access_token_param, $payment_option_config->gateway_condition);

            // Get Auth Data (Encrypted Card Details)
            $expire_date = substr($request->year, -2) . $request->month;
            $auth_data = $this->getAuthData($request->pan, $expire_date, $request->cvv, $request->pin);

            // Initiate Payment
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url."/api/v3/purchases",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(array(
                    "customerId" => $customerId,
                    "amount" => "$request->amount",
                    "transactionRef" => $transactionRef,
                    "transactionMode" => "AUTH_CAPTURE",
                    "currency" => "NGN",
                    "authData" => $auth_data
                )),
                CURLOPT_HTTPHEADER => array("Authorization: Bearer $access_token", 'Content-Type: application/json'),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                throw new \Exception($err);
            } else {
                $response = json_decode($response, true);
                if(isset($response['errors']) && !empty($response['errors'])){
                    throw new \Exception(json_encode($response['errors']));
                }

                $transaction = new Transaction();
                $transaction->status = ($check_for == "user") ? 1 : 2;
                $transaction->card_id = NULL;
                $transaction->user_id = ($check_for == "user") ? $payer->id : NULL;
                $transaction->driver_id = ($check_for == "user") ? NULL : $payer->id;
                $transaction->merchant_id = $payer->merchant_id;
                $transaction->payment_option_id = $payment_option->id;
                $transaction->checkout_id = NULL;
                $transaction->booking_id = NULL;
                $transaction->order_id = NULL;
                $transaction->handyman_order_id = NULL;
                $transaction->payment_transaction_id = $transactionRef;
                $transaction->payment_transaction = json_encode($response);
                $transaction->reference_id = $response['paymentId'];
                $transaction->amount = $request->amount;
                $transaction->request_status = 1; // PENDING
                $transaction->status_message = "PENDING";
                $transaction->save();

                // https://qa.interswitchng.com/collections/api/v1/pay/cardinalCallBack
                $return_data = array(
                    "response_code" => $response['responseCode'],
                    "transaction_id" => $transaction->id,
                    "payment_id" => $response['paymentId'],
                    "message" => isset($response['message']) ? $response['message'] : "",
                    "plainTextSupportMessage" => isset($response['plainTextSupportMessage']) ? $response['plainTextSupportMessage'] : "",
                    "payment_url" => ($response['responseCode'] == "S0") ? route("interswitch-web-payment",["transaction_id" => $transaction->id]) : "",
                    "auth_data" => $auth_data
                );
            }
            DB::commit();
            return $this->successResponse("Success", $return_data);
        } catch (\Exception $exception) {
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function paymentConfirmation(Request $request, $check_for)
    {
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'transaction_id' => ['required', 'exists:transactions,id'],
                'otp' => 'nullable',
                'auth_data' => 'required'
            ]);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                throw new \Exception($errors[0]);
            }
            $payment_status = false;

            $payer = ($check_for == "user") ? $request->user('api') : $request->user('api-driver');
            $transaction = Transaction::findOrFail($request->transaction_id);

            if($transaction->request_status == 2 || $transaction->status_message == "SUCCESS"){
                throw new \Exception("Payment Already Processed");
            }

            $payment_transaction = json_decode($transaction->payment_transaction, true);

            if($payment_transaction['responseCode'] == "T0" && empty($request->otp)){
                throw new \Exception("The otp field is required");
            }

            $payment_option = PaymentOption::where('slug', 'INTERSWITCH')->first();
            $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $payer->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();

            $base_url = ($payment_option_config->gateway_condition == 1) ? "https://api.interswitchng.com" : "https://payment-service.k8.isw.la";

            // Get Access Token
            $access_token_param = $payment_option_config->auth_token;
            $access_token = $this->generateAccessToken($access_token_param, $payment_option_config->gateway_condition);

            if($payment_transaction['responseCode'] == "S0"){
                $request_data = array(
                    "paymentId" => $transaction->reference_id,
                    "transactionId" => $payment_transaction['transactionId'],
                    "eciFlag" => $payment_transaction['eciFlag']
                );
            }else{
                $request_data = array(
                    "paymentId" => $transaction->reference_id,
                    "authData" => $request->auth_data,
                    "otp" => $request->otp
                );
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url."/api/v3/purchases/otps/auths",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_HTTPHEADER => array("Authorization: Bearer $access_token", 'Content-Type: application/json'),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                throw new \Exception($err);
            } else {
                $response = json_decode($response, true);
                if(isset($response['responseCode']) && $response['responseCode'] == "00"){
                    if ($transaction->status == 1) { // For User
                        $paramArray = array(
                            'user_id' => $payer->id,
                            'amount' => $transaction->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'payment_option_id' => $payment_option->id,
                            'transaction_id' => $response['transactionRef']
                        );
                        WalletTransaction::UserWalletCredit($paramArray);
                    } else {
                        $paramArray = array(
                            'driver_id' => $payer->id,
                            'amount' => $transaction->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => $payment_option->name,
                            'receipt' => $response['transactionRef'],
                            'transaction_id' => $response['transactionRef']
                        );
                        WalletTransaction::WalletCredit($paramArray);
                    }
                    $transaction->request_status = 2;
                    $transaction->status_message = "SUCCESS";
                    $transaction->save();

                    $payment_status = true;
                }else{
                    if(isset($response['errors']) && !empty($response['errors'])){
                        throw new \Exception(json_encode($response['errors']));
                    }else{
                        $transaction->request_status = 3;
                        $transaction->status_message = "FAILED";
                        $transaction->save();

                        $payment_status = false;
                    }
                }
            }
            DB::commit();
            if ($payment_status == true) {
                return $this->successResponse("Success");
            } else {
                return $this->failedResponse("Payment Failed");
            }
        } catch (\Exception $exception) {
            DB::rollback();
            // p($exception->getMessage(),0);
            // p($exception->getTraceAsString());
            return $this->failedResponse($exception->getMessage());
        }
    }

    private function getAuthData($pan, $expDate, $cvv, $pin)
    {
        $authDataCipher = '1' . 'Z' . $pan . 'Z' . $pin . 'Z' . $expDate . 'Z' . $cvv;
        $rsa = new \Crypt_RSA();
        $modulus = new \Math_BigInteger($this->public_modulus, 16);
        $exponent = new \Math_BigInteger($this->public_exponent, 16);
        $rsa->loadKey(array('n' => $modulus, 'e' => $exponent));
        $rsa->setPublicKey();
        $pub_key = $rsa->getPublicKey();

        openssl_public_encrypt($authDataCipher, $encryptedData, $pub_key);
        $authData = base64_encode($encryptedData);

        return $authData;
    }

    private function generateAccessToken($access_token_param, $gateway_condition)
    {
//        $auth_token = base64_encode("$client_id:$secret_key");
        $access_token_param = base64_encode($access_token_param);
        $base_url = ($gateway_condition == 1) ? "https://passport.Interswitchng.com" : "https://apps.qa.interswitchng.com";
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $base_url . "/passport/oauth/token?grant_type=client_credentials",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic $access_token_param",
                "Accept: application/json",
                "Content-Type: application/x-www-form-urlencoded"
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            throw new \Exception($err);
        } else {
            $data = json_decode($response, true);
            if (isset($data['access_token'])) {
                return $data['access_token'];
            } else {
                throw new \Exception($response);
            }
        }
    }

    public function webPayment(Request $request, $transaction_id){
        try{
            $transaction = Transaction::findOrFail($transaction_id);
            $payment_transaction = json_decode($transaction->payment_transaction, true);
            return view('interswitch.index',compact('payment_transaction'));
        }catch (\Exception $exception){
            return $this->failedResponse($exception->getMessage());
        }
    }
}