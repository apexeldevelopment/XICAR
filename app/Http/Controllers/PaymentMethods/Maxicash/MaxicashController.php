<?php

namespace App\Http\Controllers\PaymentMethods\MaxiCash;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Traits\PaymentNotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use DB;


class MaxicashController extends Controller
{
    use ApiResponseTrait, MerchantTrait, PaymentNotificationTrait;
    
    public function getUrl($env){
        return $env == 1 ? "https://webapi.maxicashapp.com/Integration/PayEntryWeb" : "https://webapi-test.maxicashapp.com/Integration/PayEntryWeb";
    }
    
    public function paymentInitiate($request, $payment_option_config,$calling_from)
    {
        
        DB::beginTransaction();
        try{
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                // $currency = $driver->Country->isoCode;
                $accountno = $driver->phoneNumber;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
                $country_code = $driver->Country->country_code;
            } 
            else
            {
                $user = $request->user('api');
                // $currency = $user->Country->isoCode;
                $id = $user->id;
                $accountno = $user->UserNumber;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
                $country_code = $user->Country->country_code;
            }
            $transaction_id = "REF".uniqid().time();
            $phone = $request->phone;
            DB::table('transactions')->insert([
                'user_id' => $request->calling_from == "USER" ? $id : NULL,
                'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                'status' => $calling_from,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => $transaction_id,
                'amount' => $request->amount,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'payment_transaction' => "",
                'request_status' => 1,
                "payment_mode" => "Third-party App",
                'status_message' => 'PENDING',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            DB::commit();
            return [
                'status' => 'NEED_TO_OPEN_WEBVIEW',
                'url' => route('maxicash-process-payment', [
                            'user_id'=>$id,
                            'calling_from' => $calling_from,
                            'payment_config_id' => $payment_option_config->id,
                            'amt' => $request->amount,
                            'locale' => !empty($request->header('locale'))? $request->header('locale'): 'En',
                            'transaction_id' => $transaction_id,
                            'phone'=>$phone,
                            'currency'=> $request->currency
                        ]),
                'success_url' => route('maxicashweb-success'),
                'fail_url'    => route('maxicashweb-failure'),
            ];
            
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function processPayment(Request $request, $id,  $calling_from, $payment_option_config, $amt, $locale, $transaction_id,$phone,$currency){
        
        
        $payment_option_config= PaymentOptionsConfiguration::where('id', $payment_option_config)->first();
        
        $data=[
            'url_type'        => $payment_option_config->gateway_condition,
            'PayType'         => "MaxiCash",
            'MerchantID'      =>  $payment_option_config->api_secret_key,
            'MerchantPassword'=>  $payment_option_config->api_public_key,
            'Amount'          =>  (string)$amt,
            'Currency'        =>  $currency,
            'Telephone'       =>  $phone,                  //"896409407",
            'Language'        =>  $locale,
            'Reference'       =>  $transaction_id,
            'accepturl'       =>  route('maxicashweb-success'),
            'declineurl'      =>  route('maxicashweb-failure'),
            'cancelurl'       =>  route('maxicashweb-cancel'),
            'notifyurl'       =>  route('maxicashweb-notify'),
            'Email'           => "info@panda-rdc.com"
            
        ];
 
        return view("merchant.random.maxicash_process", compact('data', 'id',  'calling_from', 'payment_option_config'));
    }

    public function Success(Request $request)
    {
        \Log::channel('Maxicash')->emergency($request->all());
         DB::table('transactions')->where('payment_transaction_id', $request->reference)->update([
                 'request_status' => 2,'status_message' => $request->status,'payment_transaction'=>json_encode($request)]);
         echo "<h1>Success</h1>";
    }

    public function Cancel(Request $request)
    {
        \Log::channel('Maxicash')->emergency($request->all());
         DB::table('transactions')->where('payment_transaction_id', $request->reference)->update([
            'request_status' => 3,'status_message' => $request->status,'payment_transaction'=>json_encode($request)]);
         echo "<h1>Failed</h1>";
    }

    public function Notify(Request $request)
    {
        \Log::channel('Maxicash')->emergency($request->all());
        echo "<h1>Failed</h1>";
        // $transaction_id = $request['notif_token'];
        // DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
        //     'request_status' => $status == 'SUCCESS' ? 2 : ($status == 'FAILED' ? 3 : 4),
        //     'status_message' => $status == 'SUCCESS' ? 'SUCCESS' : "FAIL"
        // ]);
    }

    public function Failure(Request $request)
    {
        \Log::channel('Maxicash')->emergency($request->all());
         DB::table('transactions')->where('payment_transaction_id', $request->reference)->update([
            'request_status' => 3,'status_message' => $request->status,'payment_transaction'=>json_encode($request)]);
         echo "<h1>Failed</h1>";
    }
}