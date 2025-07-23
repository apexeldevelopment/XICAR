<?php

namespace App\Http\Controllers\PaymentMethods\OrangeMoney;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Onesignal;

class OrangeMoneyCoreController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getOrangeMoneyConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'ORANGEMONEYCORE')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getUrl($env){
        return $env == 1 ? "https://api-s1.orange.cm" : "https://api-s1.orange.cm";
    }

    public function getAuthToken($username, $password, $base_url){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $base_url.'/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization:Basic ' . base64_encode("$username:$password")
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        if (isset($response['access_token'])){
            return $response['access_token'];
        }
        return '';
    }

    public function initPayment($xAuthToken,$token,$base_url){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $base_url.'/omcoreapis/1.0.2/mp/init',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization:Bearer ' .$token,
                'X-Auth-Token:' . $xAuthToken
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        if (isset($response['data']) && isset($response['data']['payToken'])){
            return $response['data']['payToken'];
        }
        return '';
    }

    public function initiatePaymentRequest($request, $payment_option_config, $calling_from)
    {
        try{
            $xAuthToken = $payment_option_config->auth_token;
            $username = $payment_option_config->api_public_key;
            $password = $payment_option_config->api_secret_key;
            $pin = $payment_option_config->operator;
            $channelUserMssidn = $payment_option_config->additional_data;
            // dd($xAuthToken,$username,$password,$pin,$channelUserMssidn);
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 2;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 1;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            
            $payment_option = $this->getOrangeMoneyConfig($merchant_id);
            $base_url = $this->getUrl($payment_option->gateway_condition);
            $token = $this->getAuthToken($username,$password, $base_url);
            
            if(empty($token)){
                throw new \Exception('Token not generated');
            }
            
            
            $order_id = 'ORDER_'.time();
            $transaction_id = 'TRANS_'.time();
            $reference = 'REF_'.$id.'_'.time();
            $amount = $request->amount;
            $payToken = $this->initPayment($xAuthToken,$token,$base_url);

            $data = [
                "subscriberMsisdn"=>(string)$request->phone,
                "channelUserMsisdn"=>(string)$channelUserMssidn,
                "amount"=> (string)(int)$amount,
                "description"=>"cashin",
                "orderId"=>$order_id,
                "pin"=> (string)$pin,
                "payToken"=> (string)$payToken,
                "notifUrl"=> route('orangemoneycore-notify')
            ];

            
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url .'/omcoreapis/1.0.2/mp/pay',
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
                    'Authorization:Bearer ' .$token,
                    'X-Auth-Token:' . $xAuthToken
                ),
            ));
            
            
            $response = json_decode(curl_exec($curl),true);
            \Log::channel('orangemoneycore_api')->emergency($response);
            curl_close($curl);
            if(isset($response['data']) && $response['data']['status'] == 'PENDING') {
                // $transaction_id = $response['data']['txnid'];
                DB::table('transactions')->insert([
                'user_id' => $calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'merchant_id' => $merchant_id,
                'payment_transaction_id'=> $transaction_id,
                'payment_transaction'=> json_encode($response),
                'amount' => $request->amount,
                'payment_option_id' => $payment_option->payment_option_id,
                'request_status'=> 1,
                'reference_id'=> $reference,
                'status_message'=> 'PENDING',
                'status'=> $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            }else{
                throw new \Exception('Payment Url not generated');
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }

        DB::commit();
        // dd($response);
        return [
            'payToken' => isset($response['data']) && $response['data']['payToken'] ? $response['data']['payToken'] : '',
            'transaction_id' => $transaction_id,
            'bearer_token' => $token,
            'success_url' => route('orangemoneycore-success'),
            'fail_url' => route('orangemoneycore-fail'),
        ];
    }

    public function notify(Request $request){
        \Log::channel('orangemoneycore_api')->emergency($body);
    }

    public function Success(Request $request)
    {
        \Log::channel('orangemoneycore_api')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Cancel(Request $request)
    {
        \Log::channel('orangemoneycore_api')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }

    public function paymentStatus(Request $request){
        $calling_from = $request->calling_from;
        if($calling_from == "DRIVER") {
            $user = $request->user('api-driver');
            $merchant_id = $user->merchant_id;
        }
        else{
            $user = $request->user('api');
            $merchant_id = $user->merchant_id;
        }
        $payment_option = $this->getOrangeMoneyConfig($merchant_id);
        $xAuthToken = $payment_option->auth_token;
        $transactionId = $request->transaction_id; 
        $payToken = $request->pay_token;
        $bearerToken = $request->bearer_token;

        $transactionStatus = $this->getOrangeMoneyTranStatus($user,$transactionId,$calling_from, $request);
        // dd($transactionStatus);
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        $data = [];
            if($transaction_table->request_status == 1)
            {
                $request_status_text = "processing";
                $transaction_status = 1;
                $data = ['payment_status' => 1, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }
            else if($transaction_table->request_status == 2)
            {
                $request_status_text = "success";
                $transaction_status = 2;
                $data = ['payment_status' => 2, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }
            else
            {
                $request_status_text = "failed";
                $transaction_status = 3;
                $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                
            }

            return $data;
    }

    public function getOrangeMoneyTranStatus($user,$transactionId,$calling_from, $request){
        $string_file = $this->getStringFile($user->merchant_id);
        $payment_option = PaymentOption::where('slug', 'ORANGEMONEYCORE')->first();
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        if (empty($paymentConfig)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }

        $payToken = $request->pay_token;
        $bearerToken = $request->bearer_token;
        $xAuthToken = $paymentConfig->auth_token;

        $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL =>'https://api-s1.orange.cm/omcoreapis/1.0.2/mp/paymentstatus/'.$payToken,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization:Bearer ' .$bearerToken,
                    'X-Auth-Token:' . $xAuthToken
                ),
            ));
            
            
        $response = json_decode(curl_exec($curl),true);
        \Log::channel('orangemoneycore_api')->emergency($response);
        if(isset($response['data']) && $response['data']['status'] == 'PENDING') {
            return $response;
            
        }elseif(isset($response['data']) && $response['data']['status'] == 'SUCCESSFULL') {
            $transaction =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
            if (!empty($transaction)) {
                if($transaction->request_status == 2){
                    return $transaction;
                }
                else {
                    DB::table('transactions')
                    ->where(['payment_transaction_id' => $transactionId])
                    ->update(['request_status'=>2,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($response)]);

                // if ($transaction->status == 1) {
                //     $receipt = "Application : " . $transactionId;
                //     $paramArray = array(
                //         'user_id' => $transaction->user_id,
                //         'booking_id' => NULL,
                //         'amount' => $transaction->amount,
                //         'narration' => 2,
                //         'platform' => 2,
                //         'payment_method' => 2,
                //         'receipt' => $receipt,
                //         'transaction_id' => $transactionId,
                //         'notification_type' => 89
                //     );
                //     WalletTransaction::UserWalletCredit($paramArray);
                // } else {
                //     $receipt = "Application : " . $transactionId;
                //     $paramArray = array(
                //         'driver_id' => $transaction->user_id,
                //         'booking_id' => NULL,
                //         'amount' => $transaction->amount,
                //         'narration' => 2,
                //         'platform' => 2,
                //         'payment_method' => 3,
                //         'receipt' => $receipt,
                //         'transaction_id' => $transactionId,
                //         'notification_type' => 89
                //     );
                //     WalletTransaction::WalletCredit($paramArray);
                // }
                }
            }
        }else{
            DB::table('transactions')
                    ->where(['payment_transaction_id' => $transactionId])
                    ->update(['request_status'=>3,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($response)]);
            // $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
            // $message = 'Failed';
            // $data = ['result' => '0', 'amount' => $transaction_table->amount, 'message' => $message];
            // $merchant_id = $transaction_table->merchant_id;
            // $arr_param = [];
            //         $arr_param['data'] = $data;
                    
            //         $arr_param['merchant_id'] = $merchant_id;
            //         $arr_param['large_icon'] = "";
            //         $arr_param['message'] = $message;
            //         $arr_param['title'] = $message;

            //         if ($transaction_table->status == 1) {
            //             $arr_param['user_id'] = $transaction_table->user_id;
            //             Onesignal::UserPushMessage($arr_param);
            //         } else {
            //             $arr_param['driver_id'] = $transaction_table->user_id;
            //             Onesignal::DriverPushMessage($arr_param);
            //         }
        }

        return $response;

    }

}