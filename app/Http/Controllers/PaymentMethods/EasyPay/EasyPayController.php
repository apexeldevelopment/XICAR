<?php

namespace App\Http\Controllers\PaymentMethods\EasyPay;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;
use App\Models\Order;
use App\Models\HandymanOrder;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class EasyPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function getEasyPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'EASY_PAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    public function EasyPayCallabck(Request $request){
            \Log::channel('easy_pay')->emergency([
                'headers' => getallheaders(),
                'body' => file_get_contents('php://input'),
                'request' => $request->all(),
            ]);
            
            // Step 1: Get raw input
            $rawBody = file_get_contents('php://input');
        
            // Step 2: Extract boundary from Content-Type header
            $contentType = $request->header('Content-Type');
            preg_match('/boundary=(.*)$/', $contentType, $matches);
            if (!isset($matches[1])) {
                return response()->json(['error' => 'Boundary not found'], 400);
            }
        
            $boundary = trim($matches[1], '"');
        
            // Step 3: Parse multipart data
            $parsed = $this->parseMultipartFormData($rawBody, $boundary);
            if(isset($parsed['status']) && $parsed['status'] == "Sucess"){
                DB::table('transactions')
                                ->where(['payment_transaction_id' => $parsed['referenceId']])
                                ->update(['request_status' => 2, 'payment_transaction' => json_encode($parsed), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
            }elseif(isset($parsed['status']) && $parsed['status'] == "Failed"){
                DB::table('transactions')
                                ->where(['payment_transaction_id' => $parsed['referenceId']])
                                ->update(['request_status' => 3, 'payment_transaction' => json_encode($parsed), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'FAIL']);
            }else{
                DB::table('transactions')
                                ->where(['payment_transaction_id' => $parsed['referenceId']])
                                ->update(['request_status' => 1, 'payment_transaction' => json_encode($parsed), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'PENDING']);
            }
        
            // Get Authorization Header
            $authHeader = $request->header('authorization');
        
            if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
                return response()->json([
                    'errormsg' => 'Authorization header missing or invalid',
                    'success' => 0
                ], 401)->header('WWW-Authenticate', 'Basic realm="Access Denied"');
            }
        
            // Decode Basic Auth
            $encodedCredentials = substr($authHeader, 6); // remove "Basic "
            if (preg_match('/[^A-Za-z0-9+\/=]/', $encodedCredentials)) {
                return response()->json([
                    'errormsg' => 'Invalid authorization format',
                    'success' => 0
                ], 401);
            }
        
            $decoded = base64_decode($encodedCredentials);
            if (!$decoded || !str_contains($decoded, ':')) {
                return response()->json([
                    'errormsg' => 'Invalid base64 credentials',
                    'success' => 0
                ], 401);
            }
        
            [$username, $password] = explode(':', $decoded, 2);
        
            // Check credentials
            if ($username !== 'lukatout@gmail.com' || $password !== 'Fercy@4045') {
                return response()->json([
                    'errormsg' => 'Invalid credentials',
                    'success' => 0
                ], 403)->header('WWW-Authenticate', 'Basic realm="Access Denied"');
            }
        
            // Authentication successful, log the request payload
            $payload = $request->all();
            // Log::info('EasyPay Callback Received:', $payload);
        
            return response()->json([
                'message' => 'Transaction processed successful',
                'success' => 1
            ]);
        }
        
        private function parseMultipartFormData($body, $boundary)
        {
            $results = [];
        
            // Split the body into parts by boundary
            $blocks = preg_split("/-+$boundary(?:--)?\s*/", $body);
            foreach ($blocks as $block) {
                if (empty(trim($block))) continue;
        
                // Match the name and content
                if (preg_match('/name="([^"]+)"\s*\r\n\r\n(.*)/s', $block, $matches)) {
                    $name = $matches[1];
                    $value = rtrim($matches[2]);
                    $results[$name] = $value;
                }
            }
        
            return $results;
        }
        
        public function generateAuthToken($authKey){
            try{

                $headers = [
                    'Authorization: Basic ' . $authKey
                ];
                 $ch = curl_init('https://www.easypay.co.ug/payments/api/v1/auth');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
                $response = curl_exec($ch);
                curl_close($ch);
                $res = json_decode($response,true);
                if(isset($res['data']['token'])){
                    return $res['data']['token'];
                }
                else{
                    return '';
                }
            }catch(\Exception $e) {
                DB::rollBack();
                throw new \Exception($e->getMessage());
            }
        }
        
        public function MakePayment($request,$payment_option_config, $calling_from){
            $status = 3;
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $countryCode = $user->country->country_code;
                $id = $user->id;
                $status = 2;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $countryCode = $user->country->country_code;
                $id = $user->id;
                $status = 1;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            
            $paymentConfig = $this->getEasyPayConfig($merchant_id);
            $authKey = $payment_option_config->api_secret_key;
            
            $string_file = $this->getStringFile($merchant_id);
            
            $token = $this->generateAuthToken($authKey);
            if(empty($token)){
                throw new \Exception('Token not generated');
            }
            
            $transactionId = 'REF_TRANS_'.time();
            $data = [
                "referenceId"=>$transactionId,
                "phone"=>$request->phone,
                "currency"=> $request->currency,
                "amount"=> $request->amount
            ];
            
            $headers = [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ];
        
            $ch = curl_init('https://www.easypay.co.ug/payments/api/v1/collections');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
            $response = json_decode(curl_exec($ch),true);
            curl_close($ch);
            if(isset($response['data']) && isset($response['data']['status']) && $response['data']['status'] == 'Pending') {
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $transactionId,
                    'amount' => $request->amount,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'request_status'=> 1,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }else{
                throw new \Exception($response['errormsg']);
            }
            
            return [
                'transaction_id'=> $transactionId,
                'token'=> $token
            ];
        }
        
        public function paymentStatus($request,$payment_option_config){
            $transactionId = $request->transaction_id; 
            $token = $request->token;
            $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
            $payment_status =   $transaction_table->request_status == 2 ?  true : false;
            $data = [];
            if($transaction_table->request_status == 1)
            {
                $request_status_text = "processing";
                $transaction_status = 1;
                
                $postData = [
                    "referenceId"=> $transactionId
                ];
                //check status 
                $curl = curl_init();
                
    
                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://www.easypay.co.ug/payments/api/v1/status',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS =>json_encode($postData),
                  CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$token
                  ),
                ));
                
    
                $response = json_decode(curl_exec($curl),true);
                curl_close($curl);
                if(isset($response['data']) && isset($response['data']['status']) && $response['data']['status'] == "Sucess"){
                    DB::table('transactions')
                                    ->where(['payment_transaction_id' => $response['data']['referenceId']])
                                    ->update(['request_status' => 2, 'payment_transaction' => json_encode($response), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
                                    
                    $data = ['payment_status' => true, 'request_status' => "success", 'transaction_status' => 2];
                }elseif(isset($response['data']['status']) && $response['data']['status'] == "Failed"){
                    DB::table('transactions')
                                    ->where(['payment_transaction_id' => $response['data']['referenceId']])
                                    ->update(['request_status' => 3, 'payment_transaction' => json_encode($response), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'FAIL']);
                                    
                    $data = ['payment_status' => false, 'request_status' => "fail", 'transaction_status' => 3];
                }else{
                    DB::table('transactions')
                                    ->where(['payment_transaction_id' => $response['data']['referenceId']])
                                    ->update(['request_status' => 1, 'payment_transaction' => json_encode($response), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'PENDING']);
                                    
                    $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                }
                
            }
            else if($transaction_table->request_status == 2)
            {
                $request_status_text = "success";
                $transaction_status = 2;
                $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }
            else
            {
                $request_status_text = "failed";
                $transaction_status = 3;
                $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                
            }
            
            return $data;
        }


}

