<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 8/2/23
 * Time: 10:32 AM
 */

namespace App\Http\Controllers\PaymentMethods\Sahay;

use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class SahayGateway
{
    use ApiResponseTrait, MerchantTrait;

    protected $BASE_URL = "http://196.191.76.123:9096/third-party/api";

    public function checkPhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required',
            'for' => 'required|IN:USER,DRIVER',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","SAHAY")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/v1/generate-token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode(array("consumerKey" => $payment_option_config->api_public_key, "consumerSecret" => $payment_option_config->api_secret_key)),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $auth_response = curl_exec($curl);
            curl_close($curl);

            $auth_response = json_decode($auth_response,true);
            if($auth_response['Status'] != "00"){
                throw new \Exception($auth_response['Message']);
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/v1/check-customer',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode(array("PhoneNumber" => str_replace("+","",$request->phone_number))),
                CURLOPT_HTTPHEADER => array(
                    "Authorization:Bearer ".$auth_response['AccessToken'],
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);
            if(isset($response['response']) && $response['response'] == "000"){
                return $this->successResponse(trans("$string_file.success"),array(
                    "name" => $response['customerName'],
                    "client" => $response['Client'],
                    "phone_number" => $response['PhoneNumber']
                ));
            }else{
                throw new \Exception($response['responseDescription']);
            }
        }catch (\Exception $exception){
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function requestPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required',
            'amount' => 'required',
            'for' => 'required|IN:USER,DRIVER',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","SAHAY")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/v1/generate-token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode(array("consumerKey" => $payment_option_config->api_public_key, "consumerSecret" => $payment_option_config->api_secret_key)),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $auth_response = curl_exec($curl);
            curl_close($curl);

            $auth_response = json_decode($auth_response,true);
            if($auth_response['Status'] != "00"){
                throw new \Exception($auth_response['Message']);
            }

            $timestamp = strtotime(date("Y-m-d H:i:s"));
            $reference = $user->merchant_id."00".$timestamp;
            $transaction_id = "";

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/v1/stage-request',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode(array("PhoneNumber" => str_replace("+","",$request->phone_number), "Amount" => $request->amount, "BillerReference" => $reference, "Timestamp" => $timestamp)),
                CURLOPT_HTTPHEADER => array(
                    "Authorization:Bearer ".$auth_response['AccessToken'],
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);
            if(isset($response['response']) && $response['response'] == "000"){
                $transaction_id = $response['sahayRef'];
            }else{
                throw new \Exception($response['responseDescription']);
            }

            $transaction = new Transaction();
            $transaction->status = ($request->for == "USER") ? 1 : 2; // for user
            $transaction->card_id = NULL;
            $transaction->user_id = ($request->for == "USER") ? $user->id : NULL;
            $transaction->driver_id = ($request->for == "USER") ? NULL : $user->id;
            $transaction->merchant_id = $user->merchant_id;
            $transaction->payment_option_id = $payment_option->id;
            $transaction->checkout_id = NULL;
            $transaction->booking_id = NULL;
            $transaction->order_id = NULL;
            $transaction->handyman_order_id = NULL;
            $transaction->payment_transaction_id = $transaction_id;
            $transaction->payment_transaction = json_encode(array("phone_number" => $request->phone_number, "reference" => $reference));
            $transaction->amount = $request->amount; // amount
            $transaction->request_status =  1; // PENDING
            $transaction->status_message = "PENDING";
            $transaction->save();

            DB::commit();
            return $this->successResponse(trans("$string_file.success"),array(
                "phone_number" => $request->phone_number,
                "amount" => $request->amount,
                "transaction_id" => $transaction->id,
            ));
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function confirmPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'for' => 'required|IN:USER,DRIVER',
            'otp' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","SAHAY")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $transaction = Transaction::find($request->transaction_id);

            if($transaction->request_status != 1){
                if($transaction->request_status == 2){
                    return $this->successResponse(trans("$string_file.payment_success"));
                }else{
                    return $this->failedResponse(trans("$string_file.payment_failed"));
                }
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/v1/generate-token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode(array("consumerKey" => $payment_option_config->api_public_key, "consumerSecret" => $payment_option_config->api_secret_key)),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $auth_response = curl_exec($curl);
            curl_close($curl);

            $auth_response = json_decode($auth_response,true);
            if($auth_response['Status'] != "00"){
                throw new \Exception($auth_response['Message']);
            }

            $pay_trans = json_decode($transaction->payment_transaction, true);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/v1/fulfill-request',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode(array("BillerReference" => $pay_trans['reference'], "TransRef" => $transaction->payment_transaction_id, "Code" => $request->otp)),
                CURLOPT_HTTPHEADER => array(
                    "Authorization:Bearer ".$auth_response['AccessToken'],
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);
            if(isset($response['response']) && $response['response'] == "000"){
                // Update Payment Transaction
                $transaction->request_status = 2;
                $transaction->status_message = "SUCCESS";
                $transaction->save();

                // Transfer the amount
                if ($transaction->status == 1) { // For User
                    $paramArray = array(
                        'user_id' => $user->id,
                        'booking_id' => NULL,
                        'amount' => $transaction->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'payment_option_id' => $payment_option->id,
                        'transaction_id' => $response['transRef']
                    );
                    WalletTransaction::UserWalletCredit($paramArray);
                } else {
                    $paramArray = array(
                        'driver_id' => $user->id,
                        'booking_id' => NULL,
                        'amount' => $transaction->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => $payment_option->name,
                        'receipt' => $response['transRef'],
                        'transaction_id' => $response['transRef']
                    );
                    WalletTransaction::WalletCredit($paramArray);
                }
            }else{
                throw new \Exception($response['responseDescription']);
            }
            DB::commit();
            return $this->successResponse(trans("$string_file.payment_success"));
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }
}
