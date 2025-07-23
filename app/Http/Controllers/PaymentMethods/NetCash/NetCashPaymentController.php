<?php

namespace App\Http\Controllers\PaymentMethods\NetCash;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class NetCashPaymentController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    
    public function getNetCashConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'NETCASH')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    
    public function generatePaymentForm(Request $request){
            $validator = Validator::make($request->all(),[
                'calling_from' => 'required',
            ]);
    
            if ($validator->fails()){
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }
            
            $calling_from = $request->calling_from;
        
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
            
            $transaction = 'TRANS_'.time();
            $paymentConfig = $this->getNetCashConfig($merchant_id);
            
            $M1 = $paymentConfig->api_secret_key;
            $M2 = $paymentConfig->api_public_key;
            $desc = $paymentConfig->description;
            $string_file = $this->getStringFile($merchant_id);
            
            $data = [
                'status' => 'NEED_TO_OPEN_WEBVIEW',
                'url' => route('netcash-webview',['M1'=>$M1,'M2'=>$M2,'currency'=>$currency,'transaction_id'=>$transaction]),
                'transaction_id' => $transaction,
                'success_url' => route('netcash-success'),
                'fail_url' => route('netcash-fail'),
            ];
            
            return $this->successResponse(trans("$string_file.success"), $data);
    }
    
    public function webview(Request $request){
        $m1 = $request->M1;
        $m2 = $request->M2;
        $currency = $request->currency;
        $string_file = $request->string_file;
        $trans = $request->transaction_id;
        
        return view('merchant.paymentgateways.netcash',['M1'=>$m1,'M2'=>$m2,'currency'=>$currency,'transaction_id'=>$trans]);
    }
    
    public function success(Request $request)
    {
        \Log::channel('net_cash')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function fail(Request $request)
    {
        \Log::channel('net_cash')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }

    public function notify(Request $request)
    {
        $log_data = ['notify'=>true,'request'=> $request->all()];
        \Log::channel('net_cash')->emergency($log_data);
        $status = $request['Reason'];
        $transaction_id = $request['Reference'];
        DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
            'request_status' => $status == 'Success' ? 2 : ($status == 'Declined' ? 3 : 4),
            'status_message' => $status == 'Success' ? 'SUCCESS' : "FAIL",
            'payment_transaction'=> json_encode($request->all())
        ]);
    }
    
    public function accept(Request $request){
        $log_data = ['accept'=>true,'request'=> $request->all()];
        \Log::channel('net_cash')->emergency($log_data);
        return redirect()->route('netcash-success');
    }
    
    public function decline(Request $request){
        $log_data = ['decline'=>true,'request'=> $request->all()];
        \Log::channel('net_cash')->emergency($log_data);
        return redirect()->route('netcash-fail');
    }
    
    public function redirect(Request $request){
        $log_data = ['redirect'=>true,'request'=> $request->all()];
        \Log::channel('net_cash')->emergency($log_data);
        $transaction_id = $request['Reference'];
        if(isset($request['TransactionAccepted']) && $request['TransactionAccepted'] == "false"){
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                'request_status' => 3,
                'status_message' => "FAIL",
                'payment_transaction'=> json_encode($request->all())
            ]);
            
            return redirect()->route('netcash-fail');
        }elseif(isset($request['TransactionAccepted']) && $request['TransactionAccepted'] == "true"){
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                'request_status' => 2,
                'status_message' => "SUCCESS",
                'payment_transaction'=> json_encode($request->all())
            ]);
            
            return redirect()->route('netcash-success');
        }
    }
    

}