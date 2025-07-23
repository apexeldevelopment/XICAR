<?php

namespace App\Http\Controllers\PaymentMethods\ESewa;

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

class ESewaPaymentController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getESewaConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'ESEWA_PAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    
    public function getBaseUrl($env){
        return $env == 1 ? 'https://epay.esewa.com.np/api/epay/main/v2/form' : 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';
    }
    
    public function generateForm($request,$payment_option_config, $calling_from){
            $status = 3;
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $countryCode = $user->country->country_code;
                $currency = $user->country->isoCode;
                $id = $user->id;
                $status = 2;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $countryCode = $user->country->country_code;
                $currency = $user->country->isoCode;
                $id = $user->id;
                $status = 1;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            
            $payment_option = $this->getESewaConfig($merchant_id);
            $transaction_id = 'TRANS'.$id.'_'.time();
            $reference = 'REF_MERCHANT_'.time();
            $productionCode = $payment_option_config->api_public_key;
            $amount = $request->amount;
            $post_url = $this->getBaseUrl($payment_option->gateway_condition);
            
            $string_file = $this->getStringFile($merchant_id);
            
            DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $transaction_id,
                    'amount' => $request->amount,
                    'payment_option_id' => $payment_option->payment_option_id,
                    'reference_id'=> $reference,
                    'request_status'=> 1,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            
            $data = [
                'status' => 'NEED_TO_OPEN_WEBVIEW',
                'url' => route('esewa-webview',['trans_uuid'=>$transaction_id,'amount'=>$amount,'product_code'=>$productionCode,'merchant_id'=> $merchant_id,'post_url'=>$post_url]),
                'transaction_id' =>$transaction_id ,
                'success_url' => route('esewa-success',$merchant_id),
                'fail_url' => route('esewa-fail',[$merchant_id,$transaction_id]),
            ];
            
            return $data;
    }
    
    public function webview(Request $request){
        $trans_uuid = $request->trans_uuid;
        $amount = $request->amount;
        $product_code = $request->product_code;
        $merchantId = $request->merchant_id;
        $postUrl = $request->post_url;
        
        return view('payment.Esewa.esewa',['trans_uuid'=>$trans_uuid,'amount'=>$amount,'product_code'=>$product_code,'merchant_id'=>$merchantId,'post_url'=> $postUrl]);
    }
    
    // public function PaymentStatus($request,$payment_option_config){
    //     $transactionId = $request->transaction_id; 
    //     $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
    //     $payment_status =   $transaction_table->request_status == 2 ?  true : false;
    //     $data = [];
    //     if($transaction_table->request_status == 1)
    //     {
    //         $request_status_text = "processing";
    //         $transaction_status = 1;
    //         $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
    //     }
    //     else if($transaction_table->request_status == 2)
    //     {
    //         $request_status_text = "success";
    //         $transaction_status = 2;
    //         $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
    //     }
    //     else
    //     {
    //         $request_status_text = "failed";
    //         $transaction_status = 3;
    //         $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
    //     }
    //     return $data;
    // }
    
    public function Success(Request $request)
    {
        \Log::channel('esewa_pay')->emergency($request->all());
        if(isset($request['data'])){
            $base64Decode = base64_decode($request['data']);
            $data = json_decode($base64Decode,true);
            $transactionId = $data['transaction_uuid'];
            $trans = DB::table('transactions')->where(['payment_transaction_id' => $transactionId])->first();
            if(isset($data['status']) && $data['status'] == "COMPLETE"){
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $transactionId])
                    ->update(['request_status' => 2, 'payment_transaction' => $base64Decode, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
            }elseif(isset($data['status']) && ($data['status'] == "PENDING" || $data['status'] == "CANCELED")){
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $transactionId])
                    ->update(['request_status' => 3, 'payment_transaction' => $base64Decode, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'FAIL']);
            }
        }
        
        \Log::channel('esewa_pay')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Fail(Request $request)
    {
        \Log::channel('esewa_pay')->emergency($request->all());
        $transactionId = $request->transId;
        DB::table('transactions')
                    ->where(['payment_transaction_id' => $transactionId])
                    ->update(['request_status' => 3, 'payment_transaction' => $base64Decode, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'FAIL']);
        \Log::channel('esewa_pay')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }
}