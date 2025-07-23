<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 7/7/23
 * Time: 12:18 PM
 */

namespace App\Http\Controllers\PaymentMethods\Hub2;

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
use Illuminate\Support\Facades\Validator;

class Hub2Controller
{
    use ApiResponseTrait, MerchantTrait, ContentTrait, PaymentNotificationTrait;

    public function getPaymentOptions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $data = array(
                array("slug" => "orange_money", "name" => "Orange Money", "image" => ""),
                array("slug" => "moov_money", "name" => "Moov Money", "image" => "")
            );
            return $this->successResponse(trans("$string_file.success"), $data);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function createTransaction($request, $payment_option_config, $calling_from)
    {
        DB::beginTransaction();
        try {
            // check whether request is from driver or user
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $status = 2;
                $currency = $driver->CountryArea->Country->isoCode;
                $country_code = $driver->CountryArea->Country->country_code;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
            } else {
                $user = $request->user('api');
                $status = 1;
                $currency = $user->Country->isoCode;
                $country_code = $user->Country->country_code;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
            }

            $amount = $request->amount;

            $transaction = new Transaction();
            $transaction->status = $status;
            $transaction->card_id = NULL;
            $transaction->user_id = $calling_from == "USER" ? $id : NULL;
            $transaction->driver_id = $calling_from == "DRIVER" ? $id : NULL;
            $transaction->merchant_id = $merchant_id;
            $transaction->payment_option_id = $payment_option_config->payment_option_id;
            $transaction->checkout_id = NULL;
            $transaction->amount = $amount;
            $transaction->request_status = 1;
            $transaction->save();

            $environment = ($payment_option_config->gateway_condition == 1) ? "live" : "sandbox";
            $timestamp = strtotime(date("y-m-d H:i:s"));
            $request_intent_data = array(
                "customerReference" => $request->phone."_".$request->option_slug."_"."$id"."_".$timestamp,
                "purchaseReference" => $request->phone."_".$request->option_slug."_"."$id"."_".$timestamp,
                "amount" => (integer)$amount,
                "currency" => $currency
            );

            $header = array(
                "merchantId: $payment_option_config->api_public_key",
                "environment: $environment",
                "ApiKey: $payment_option_config->api_secret_key",
                "Content-Type: application/json"
            );

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.hub2.io/payment-intents',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($request_intent_data),
                CURLOPT_HTTPHEADER => $header,
            ));
            $intent_response = curl_exec($curl);
            curl_close($curl);
            $intent_response = json_decode($intent_response);
            // dd($request_intent_data,$header,$intent_response);
            if (isset($intent_response->status) && $intent_response->status != 'payment_required') {
                throw new \Exception(json_encode($intent_response));
            }
            $transaction->payment_transaction_id = $intent_response->id;
            $transaction->payment_transaction = $intent_response->token;
            $transaction->save();

            $data = array(
                "token" => $intent_response->token,
                "paymentMethod" => "mobile_money",
                "country" => $country_code, //"ML",
                "provider" => $request->option_slug == "orange_money" ? "orange" : "moov",
                "mobileMoney" => array(
                    "msisdn" => $request->phone,
                    "otp"=> $request->otp
                )
            );

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.hub2.io/payment-intents/$intent_response->id/payments",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => $header,
            ));
            $payment_response = curl_exec($curl);
            curl_close($curl);
            $payment_response = json_decode($payment_response);
            // dd($data,$payment_response);
            // p($payment_response);

            $message = "";
            if(isset($payment_response->status)){
                if($payment_response->status == "successful"){
                    $status = "DONE";
                }elseif($payment_response->status == "processing"){

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://api.hub2.io/payment-intents/$intent_response->id?token=$intent_response->token",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => $header,
                    ));
                    $action_response = curl_exec($curl);
                    curl_close($curl);
                    $action_response = json_decode($action_response, true);
                    if(isset($action_response['status']) && $action_response['status'] == "action_required"){
                        if(isset($action_response['nextAction']['type']) && $action_response['nextAction']['type'] == "otp"){
                            $status = "OTP";
                            $message = $action_response['nextAction']['message'];
                        }else{
                            $status = "WAIT";
                        }
                    }else{
                        $status = "WAIT";
                    }
                }elseif($payment_response->status == 400){
                    $error = isset($payment_response->error->message) ? $payment_response->error->message[0] : json_encode($payment_response);
                    throw new \Exception($error);
                }
            }else{
                throw new \Exception(json_encode($payment_response));
            }
            DB::commit();
            return [
                'status' => $status,
                'transaction_id' => $transaction->id,
                'message' => $message
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function paymentStatus(Request $request, $payment_option_config, $calling_from){

        $transaction = Transaction::find($request->transaction_id);
        $environment = ($payment_option_config->gateway_condition == 1) ? "live" : "sandbox";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.hub2.io/payment-intents/$transaction->payment_transaction_id?token=$transaction->payment_transaction",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "merchantId: $payment_option_config->api_public_key",
                "environment: $environment",
                "ApiKey: $payment_option_config->api_secret_key"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        // p($response);

        $status = "WAIT";
        $message = "";
        if(isset($response['status'])){
            if($response['status'] == "successful"){
                $status = "DONE";
            }elseif($response['status'] == "processing"){
                $status = "WAIT";
            }elseif($response['status'] == "action_required"){
                if(isset($response['nextAction']['type']) && $response['nextAction']['type'] == "otp") {
                    $status = "OTP";
                    $message = $response['nextAction']['message'];
                }
            }
        }else{
            throw new \Exception(json_encode($response));
        }
        return ['payment_status' => $status, 'message' => $message];
    }

    public function validateOTP(Request $request){
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|integer|exists:transactions,id',
            'for' => 'required|in:USER,DRIVER',
            'otp' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try{
            $transaction = Transaction::find($request->transaction_id);
            $string_file = $this->getStringFile(null, $transaction->Merchant);

            $option = PaymentOption::select('slug', 'id')->where('id', $transaction->payment_option_id)->first();
            if (empty($option)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }
            $payment_option_config  = $option->PaymentOptionConfiguration;
            $environment = ($payment_option_config->gateway_condition == 1) ? "live" : "sandbox";

            $header = array(
                "merchantId: $payment_option_config->api_public_key",
                "environment: $environment",
                "ApiKey: $payment_option_config->api_secret_key",
                "Content-Type: application/json"
            );
            $body = array("token" => $transaction->payment_transaction, "confirmationCode" => $request->otp);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.hub2.io/payment-intents/$transaction->payment_transaction_id/authentication",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => $header,
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $status = "WAIT";
            $message = "";

            if(isset($response['status'])){
                if($response['status'] == "successful"){
                    $status = "DONE";
                }elseif($response['status'] == "processing"){
                    $status = "WAIT";
                }elseif($response['status'] == "action_required"){
                    if(isset($response['nextAction']['type']) && $response['nextAction']['type'] == "otp") {
                        $status = "OTP";
                        $message = $response['nextAction']['message'];
                    }
                }
            }else{
                throw new \Exception(json_encode($response));
            }
            return $this->successResponse(trans("$string_file.success"), array("status" => $status, "message" => $message));
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        try {
            $data = $request->all();
            $response = array(
                'request' => $data,
            );
            \Log::channel('hub2_log')->emergency($response);

            $request_data = isset($data['data']) ? $data['data'] : "";
            if(isset($request_data['intentId']) && !empty($request_data['intentId'])){
                $transaction = Transaction::where("payment_transaction_id", $request_data['intentId'])->first();

                if ($request_data['status'] == "successful") {
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
                        $transaction->request_status = 2; // SUCCESS
                        $transaction->status_message = "SUCCESS";
                        $transaction->save();

                        $for = $transaction->status == 1 ? "USER" : "DRIVER";
                        $receiver_id = $transaction->status == 1 ? $transaction->user_id : $transaction->driver_id;
                        $this->paymentSuccessNotification($for, $receiver_id, $transaction->merchant_id, "Payment Success", "Payment Success");
                    }
                } else {
                    $transaction->request_status = 3; // FAILED
                    $transaction->status_message = "FAILED";
                    $transaction->save();
                    $for = $transaction->status == 1 ? "USER" : "DRIVER";
                    $receiver_id = $transaction->status == 1 ? $transaction->user_id : $transaction->driver_id;
                    $this->paymentFailedNotification($for, $receiver_id, $transaction->merchant_id, "Payment Failed", "Payment Failed");
                }
            }
            return "OK";
        } catch (\Exception $exception) {
            $response = array(
                'request' => $data,
                'error' => $exception->getMessage(),
                'detail' => $exception->getTraceAsString(),
            );
            \Log::channel('hub2_log')->emergency($response);
            return "OK";
        }
    }
}
