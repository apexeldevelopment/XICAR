<?php

namespace App\Http\Controllers\PaymentMethods\FlexPay;

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

class FlexPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getFlexPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'FLEX_PAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    
    public function paymentRequest($request,$payment_option_config, $calling_from){
            $status = 3;
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $countryCode = $user->country->country_code;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 2;
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
            $paymentConfig = $this->getFlexPayConfig($merchant_id);
            $apiKey = $paymentConfig->api_secret_key;
            $merchantCode = $paymentConfig->api_public_key;
            $phone = $request->phone;
            $currency = $request->currency;
            $amount = $request->amount;
            $string_file = $this->getStringFile($merchant_id);
            
            $tscId = 'trans'.date('His');
            $ref = 'MER_REF_'.time();
            
            $data = [
                "merchant"=>$merchantCode,
                "type"=> 1,
                "phone"=> $phone,
                "reference"=>$ref,
                "amount"=> $amount,
                "currency"=> $currency,
                "callbackUrl"=> route('flexpay-callback',$merchant_id)
            ];
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => 'http://backend.flexpay.cd/api/rest/v1/paymentService',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>json_encode($data),
              CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$apiKey,
                'Content-Type: application/json'
              ),
            ));

            $response = json_decode(curl_exec($curl),true);
            \Log::channel('flex_pay')->emergency($response);
            curl_close($curl);
            
            if($response && isset($response['orderNumber'])){
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=>$tscId,
                    'amount' => $amount,
                    'reference_id'=> $ref,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'request_status'=> 1,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                return ['order_number'=> $response['orderNumber'],'transaction_id'=>$ref];
            }else{
                throw new \Exception("Something Went Wrong");
            }
    }
    
    public function PaymentStatus($request,$payment_option_config){
        $mer_ref = $request->transaction_id; 
        $orderNumber = $request->order_number;
        $apiKey = $payment_option_config->api_secret_key;
        $transaction_table =  DB::table("transactions")->where('reference_id', $mer_ref)->first();
        $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => 'http://backend.flexpay.cd/api/rest/v1/check/'.$orderNumber,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
              CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$apiKey
              ),
            ));

            $response = json_decode(curl_exec($curl),true);
            \Log::channel('flex_pay')->emergency($response);
            curl_close($curl);
            
            if($response && isset($response['transaction']) && isset($response['transaction']['status']) && $response['transaction']['status'] == 0){ //success
                DB::table('transactions')
                    ->where(['reference_id' => $mer_ref])
                    ->update(['request_status' => 2, 'payment_transaction' => $response, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
                    
                $request_status_text = "success";
                $transaction_status = 2;
                $data = ['payment_status' => true, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }elseif($response && isset($response['transaction']) && isset($response['transaction']['status']) && $response['transaction']['status'] == 1){ //failed
                  DB::table('transactions')
                    ->where(['reference_id' => $mer_ref])
                    ->update(['request_status' => 3, 'payment_transaction' => $response, 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'FAIL']);
                $request_status_text = "failed";
                $transaction_status = 3;
                $data = ['payment_status' => false, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }else{
                $request_status_text = "processing";
                $transaction_status = 1;
                $data = ['payment_status' => false, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }
       
            return $data;
    }
    
    public function FlexPayCallback(Request $request){
        \Log::channel('flex_pay')->emergency($request->all());
    }
}