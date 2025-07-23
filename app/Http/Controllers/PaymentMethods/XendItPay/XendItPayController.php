<?php

namespace App\Http\Controllers\PaymentMethods\XendItPay;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class XendItPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    
    public function getXendItPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'XENDIT_PAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    
    public function CreateInvoice($request,$payment_option_config, $calling_from){
        try{
            
            $secretKey = $payment_option_config->api_public_key;
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
                $email = $user->email;
            }
            else{
                $user = $request->user('api');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
                $email = $user->email;
            }
        
            $endpoint = 'https://api.xendit.co/v2/invoices';
        
            $externalID = 'invoice_'.time();
            $reference = 'REF_'.time();
            $params = [
                'external_id' => $externalID,
                'amount' => $request->amount,
                'payer_email' => $email ?? 'payucustomer@payment',
                'description' => $user->Merchant->BusinessName . ' Payment',
                'success_redirect_url'=> route('xenditpay-success'),
                'failure_redirect_url' => route('xenditpay-fail'),
            ];
    
            $payload = json_encode($params);
            $header = [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($secretKey . ':'),
            ];
            $ch = curl_init();
        
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        
            $response = json_decode(curl_exec($ch),true);
            \Log::channel('xendit_pay')->emergency([
                'payload' => $payload,
                'response' => $response,
            ]);
            curl_close($ch);
            if(isset($response['status']) && isset($response['id'])){
                 DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $externalID,
                    'amount' => $request->amount,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'request_status'=> 1,
                    'reference_id'=> $reference,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            if (isset($response['error_code'])) {
                throw new \Exception($response['message']);
            }
            
           
            
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }

        DB::commit();
        
        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => isset($response['invoice_url']) && $response['invoice_url'] ? $response['invoice_url'] : '',
            'transaction_id' => $externalID ?? '',
            'success_url' => route('xenditpay-success'),
            'fail_url' => route('xenditpay-fail'),
        ];
        
    }
    public function Callback(Request $request,$merchant_id){
        \Log::channel('xendit_pay')->emergency([
                'headers' => getallheaders(),
                'body' => file_get_contents('php://input'),
                'request' => $request->all(),
            ]);
        $data = $request->all();
        $payment_option_config = $this->getXendItPayConfig($merchant_id);
        $signature = $request->header('X-Callback-Token');
        $expectedSignature = $payment_option_config->auth_token;
        
        if (!hash_equals($signature, $expectedSignature)) {
            \Log::channel('xendit_pay')->emergency([
                'signature'=> 'Invalid signature'
            ]);
            return response('Invalid signature', 401);
        }
        
        if(isset($data['status']) && $data['status'] == 'PAID'){
            $transaction_id = $data['external_id'];
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                'request_status' => 2,
                'status_message' => 'SUCCESS',
                'payment_transaction'=> json_encode($data),
                'updated_at'=> date('Y-m-d H:i:s')
            ]);
        }else{
            $transaction_id = $data['external_id'];
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                'request_status' => 3,
                'status_message' => 'FAIL',
                'payment_transaction'=> json_encode($data),
                'updated_at'=> date('Y-m-d H:i:s')
            ]);
        }
    }
    
    public function Success(Request $request){
        \Log::channel('xendit_pay')->emergency([
                'success' =>'success',
                'request' => $request->all(),
            ]);
        echo "<h1>Success</h1>";
    }
    
    public function Fail(Request $request){
        \Log::channel('xendit_pay')->emergency([
                'fail' =>'fail',
                'request' => $request->all(),
            ]);
        echo "<h1>Fail</h1>";
    }
    
    
    
    
}