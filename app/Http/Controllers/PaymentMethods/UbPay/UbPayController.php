<?php

namespace App\Http\Controllers\PaymentMethods\UbPay;

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

class UbPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getUbPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'UBPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function UbpayGetUrl($request,$paymentOptionConfig,$calling_from)
    {
        $merchantIdKey = $paymentOptionConfig->api_secret_key ? $paymentOptionConfig->api_secret_key : 333;
        $currency = $request->currency;
        $amount = $request->amount;
        $transaction_id = 'TRANS_'.$merchantIdKey.'_'.time();

        if($calling_from == "DRIVER") {
            $user = $request->user('api-driver');
            $id = $user->id;
            $merchant_id = $user->merchant_id;
            $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
        }
        else{
            $user = $request->user('api');
            $id = $user->id;
            $merchant_id = $user->merchant_id;
            $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
        }
        $merchant_id = $user->merchant_id;
        try {
            $key_generate = $this->getUniqueUbPayRef($merchant_id);
            $json_parameter = array(
                "merchantId" => $merchantIdKey,
                "description" => "Make Payment",
                "language" => "EN",
                "merchantRef" => $key_generate,
                "currency" => $currency,
                "amount" => $amount,
                "successUrl" => route('ubpay-success',[$key_generate]),
                "failedUrl" => route('ubpay-fail',[$key_generate]),
                "cancelledUrl" => route('ubpay-cancel',[$key_generate]),
                "redirectUrl" => route('ubpay-redirect',[$key_generate])
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://apps.ub-pay.net/prod/merchantController/requestPayment",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($json_parameter, true),
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return response()->json(['result' => "0", 'result' => $err]);
            } else {
                $response = json_decode($response, true);
                if ($response['url'] != null) {
                    DB::table('transactions')->insert(
                        [
                            'user_id' => $calling_from == "USER" ? $id : NULL,
                            'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                            'merchant_id' => $merchant_id, 
                            'amount' => $amount, 
                            'reference_id' => $key_generate, 
                            'checkout_id' => $merchantIdKey,
                            'payment_transaction_id'=> $transaction_id,
                            'payment_option_id' => $paymentOptionConfig->payment_option_id,
                            'request_status'=> 1,
                            'status_message'=> 'PENDING',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                } else {
                    throw new \Exception('Payment Url not generated');
                }
            }
            
        } catch (Exception $e) {
            return response()->json(['result' => "0", 'error' => $e->getMessage()]);
        }
        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $response['url'] ?? '',
            'transaction_id' => $key_generate ?? '',
            'success_url' => route('ubpay-success',[$key_generate]),
            'fail_url' => route('ubpay-fail',[$key_generate]),
        ];
    }

    protected function getUniqueUbPayRef($merchant_id, $length = 10)
    {
        $key_generate = substr(str_shuffle("1234567890"), 0, $length);
        if (DB::table('transactions')->where([['reference_id', '=', $key_generate], ['merchant_id', '=', $merchant_id]])->exists()):
            $this->getUniqueUbPayRef($merchant_id);
        endif;
        return $key_generate;
    }

    public function UbpayCallback($merchantRef, $status)
    {
            \Log::channel('ub_pay')->emergency(['callback'=>'callback','mer_ref'=> $merchantRef,'status'=> $status]);
        // try {
        //     $existRecord = DB::table('transactions')->where('reference_id', $merchantRef)->get();
        //     if ($existRecord->count() > 0) {
        //         $existRecord->payment_status = $status;
        //         $existRecord->save();
        //         return response()->json(['result' => "1", 'status' => $status, 'merchantRef' => $merchantRef]);
        //     }
        // } catch (Exception $e) {
        //     return response()->json(['result' => "1", 'error' => $e->getMessage()]);
        // }
        // return response()->json(['result' => "0", 'status' => $status, 'merchantRef' => $merchantRef]);
    }

    public function Redirect(Request $request,$merchantRef)
    {
        \Log::channel('ub_pay')->emergency(['redirect'=>$request->all(),'merRef'=>$merchantRef]);
        if($merchantRef){
            $transaction = DB::table('transactions')->where('reference_id',$merchantRef)->first();
            if($transaction->request_status == 2 && $transaction->status_message == 'SUCCESS'){
                return redirect(route('ubpay-success',[$merchantRef]));
            }
            elseif($transaction->request_status == 3 && ($transaction->status_message == 'FAILED' || $transaction->status_message == 'CANCELLED')){
                return redirect(route('ubpay-fail',[$merchantRef]));
            }else{
                return redirect(route('ubpay-fail',[$merchantRef]));
            }
        }else{
            throw new \Exception('Reference not found');
        }
        
    }

    public function Success(Request $request,$merchantRef)
    {
        \Log::channel('ub_pay')->emergency(['success'=>$request->all()]);
        if($request['details'] == 'Payment Successful'){
            DB::table('transactions')->where('reference_id', $merchantRef)->update([
                'request_status' => 2,
                'status_message' => 'SUCCESS',
                'payment_transaction'=> json_encode($request->all())
            ]);
        }
        echo "<h1>Success</h1>";
    }

    public function Fail(Request $request,$merchantRef)
    {
        \Log::channel('ub_pay')->emergency(['fail'=>$request->all()]);
        if($request['details'] == 'Payment Failed'){
            DB::table('transactions')->where('reference_id', $merchantRef)->update([
                'request_status' => 3,
                'status_message' => 'FAILED',
                'payment_transaction'=> json_encode($request->all())
            ]);
        }
        echo "<h1>FAILED</h1>";
    }

    public function Cancel(Request $request,$merchantRef)
    {
       \Log::channel('ub_pay')->emergency(['cancel'=>$request->all(),'merRef'=> $merchantRef]);
        if($request['details'] == 'Customer cancelled Transaction'){
            DB::table('transactions')->where('reference_id', $merchantRef)->update([
                'request_status' => 3,
                'status_message' => 'CANCELLED',
                'payment_transaction'=> json_encode($request->all())
            ]);
        }
    }
}
