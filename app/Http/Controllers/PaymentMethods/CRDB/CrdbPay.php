<?php

namespace App\Http\Controllers\PaymentMethods\CRDB;

// use App\Driver;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Models\Driver;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\Onesignal;
use DB;

class CrdbPay extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function initiatePaymentRequest($request, $payment_option_config, $calling_from)
    {
        if($calling_from == "DRIVER") 
            $user = $request->user('api-driver');
        else
            $user = $request->user('api');
        $id = $user->id;
        

        DB::commit();
        return [
                'status' => 'NEED_TO_OPEN_WEBVIEW',
                'url' => route('crdb-process-payment', [
                            'user_id'=>$id,
                            'calling_from' => $calling_from,
                            'payment_config_id' => $payment_option_config->id,
                            'amt' => $request->amount,
                            'locale' => !empty($locale)? $locale: 'en',
                            'currency'=>$request->currency,
                        ]),
                'success_url' => route('crdb-success'),
                'fail_url' => route('crdb-faliure'),
            ];
    }
    
    public function processPayment(Request $request, $id,  $calling_from, $payment_option_config, $amt, $locale, $currency){
        $payment_option_config= PaymentOptionsConfiguration::where('id', $payment_option_config)->first();
        $locale = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
        $transaction_id =  uniqid();
        $data=[
            'access_key' => $payment_option_config->api_secret_key,
            'profile_id' => $payment_option_config->api_public_key,
            'is_live' => $payment_option_config->gateway_condition,
            'secret_key' => $payment_option_config->auth_token,
            'locale' => $locale,
            'transaction_type' => 'sale',
            'transaction_id'=>$transaction_id,
            'signed_date_time'=> gmdate("Y-m-d\TH:i:s\Z"),
            'reference_number' => "".uniqid().time(),
            'amount' => (float)$amt,
            'currency'=>$currency,
        ];
        return view("merchant.random.crdb_process", compact('data', 'id',  'calling_from', 'payment_option_config'));
    }
    
    
    public function confirm(Request $request){
        $is_live = $request->is_live;
        $secret_key = $request->secret_key;
        
        $signature = $this->sign($request->all(), $secret_key);
        $request->merge(['signature'=>$signature]);
    
        $params = $request->all();

        DB::beginTransaction();
        try{
            if($request->calling_from == "DRIVER") 
            {
                $user = Driver::find($request->id);
//                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 2;
                $locale = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else
            {
                $user = User::find($request->id);
//                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 1;
                $locale = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            $config = PaymentOptionsConfiguration::find($request->config_id);
            // dd($request->all());
            
            DB::table('transactions')->insert([
                'user_id' => $request->calling_from == "USER" ? $id : NULL,
                'driver_id' => $request->calling_from == "DRIVER" ? $id : NULL,
                'status' => $status,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => $request->transaction_uuid,
                'transaction_type'=>1,
                'checkout_id'=>$request->signature,
                'reference_id'=>$request->reference_number,
                'amount' => $request->amount,
                'payment_option_id' => $config->payment_option_id,
                'request_status' => 1,
                'status_message' => 'PENDING(TESTING3)',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            DB::commit();
        }
        catch(\Exception $e){
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        
        unset($params['is_live']);
        unset($params['secret_key']);
        unset($params['calling_from']);
        unset($params['id']);
        unset($params['config_id']);

        return view("merchant.random.crdb_confirm", compact('params', 'is_live'));
    }
    
    
    
    public function sign ($params, $key) {
      return $this->signData($this->buildDataToSign($params),  $key);
    }
    
    public function signData($data, $secretKey) {
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }
    
    public function buildDataToSign($params) {
            $signedFieldNames = explode(",",$params["signed_field_names"]);
            foreach ($signedFieldNames as $field) {
              $dataToSign[] = $field . "=" . $params[$field];
            }
            return $this->commaSeparate($dataToSign);
    }
    
    public function commaSeparate ($dataToSign) {
        return implode(",",$dataToSign);
    }

    public function successCallBack(Request $request){
        dd($request->all());
    }

    public function failiureCallBack(Request $request){
        return "Failed";
    }


}