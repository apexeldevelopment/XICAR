<?php

namespace App\Http\Controllers\PaymentMethods\RevolutPay;

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

class RevolutPaymentController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getRevolutPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'REVOLUTPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function createOrder($request, $payment_option_config, $calling_from)
    {
        try{
            $secretKey = $payment_option_config->api_secret_key;
            $currency = $request->currency;

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

            $payment_option = $this->getRevolutPayConfig($merchant_id);
            $transaction_id = 'TRANS'.$id.'_'.time();
            $reference = 'REFERENCE_MERCHANT_'.time();
            $amt = str_replace('.','',$request->amount);
            $amount = str_replace(',','',$amt);
            $amount = (int)$amount ;
            $data = [
                'currency'=> $currency,
                'amount'=> $amount,
                "redirect_url"=> route('revolutpay-redirect',[$transaction_id])
            ];

            
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://merchant.revolut.com/api/orders',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$secretKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Revolut-Api-Version:2023-09-01'
                ),
            ));
            
            
            $response = json_decode(curl_exec($curl));
            // dd($response);
            \Log::channel('revolut_pay')->emergency(['response'=> $response]);
            curl_close($curl);
            if(isset($response->type) && $response->type == 'payment' && $response->state == 'pending' && isset($response->checkout_url)) {
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $transaction_id,
                    'amount' => $amount,
                    'payment_option_id' => $payment_option->payment_option_id,
                    'request_status'=> 1,
                    'reference_id'=> $reference,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }else{
                throw new \Exception('Url not generated');
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }

        DB::commit();

        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $response->checkout_url ?? '',
            'order_id'=> $response->id,
            'transaction_id'=> $transaction_id,
            'success_url' => route('revolutpay-redirectapp'),
            'fail_url' => route('revolutpay-redirectapp'),
        ];
    }

    public function Success(Request $request)
    {
           \Log::channel('revolt_pay')->emergency($request->all());
           echo "<h1>Success</h1>";
       }

       public function Failure(Request $request)
       {
           \Log::channel('revolt_pay')->emergency($request->all());
           echo "<h1>Fail</h1>";
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
        $payment_option = $this->getRevolutPayConfig($merchant_id);
        $secretKey = $payment_option->api_secret_key;
        $transactionId = $request->transaction_id;
        $orderId = $request->order_id;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://merchant.revolut.com/api/orders/'.$orderId.'/payments',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Authorization:Bearer ' .$secretKey,
            ),
        ));
        $response = json_decode(curl_exec($curl),true);
        \Log::channel('revolut_pay')->emergency(['response_status'=>$response]);
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
        if(isset($transaction_table)){
                $timestamp = strtotime($transaction_table->created_at) + 3 * 60;   //add 3 min
            if($transaction_table->request_status == 1){
                if(isset($response[0]) && isset($response[0]['state']) && $response[0]['state'] == 'captured'){
                    $request_status_text = "success";
                    $transaction_status = 2;
                    $data = ['payment_status' => 2, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                    DB::table('transactions')
                                ->where(['payment_transaction_id' => $transactionId])
                                ->update(['request_status'=>2,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($response)]);
                }elseif(empty($response)){
                    if(time() >= $timestamp){
                        $request_status_text = "failed";
                        $transaction_status = 3;
                        $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            
                        DB::table('transactions')
                                    ->where(['payment_transaction_id' => $transactionId])
                                    ->update(['request_status'=>3,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($response)]);
                    }else{
                        $request_status_text = "processing";
                        $transaction_status = 1;
                        $data = ['payment_status' => 1, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                    }
                    
                }
            }elseif($transaction_table->request_status == 2){
                $request_status_text = "success";
                    $transaction_status = 2;
                    $data = ['payment_status' => 2, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }elseif($transaction_table->request_status == 3){
                $request_status_text = "failed";
                        $transaction_status = 3;
                        $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }
            
        }

        return $data;

    }
    
    public function redirect(Request $request,$transaction_id){
        \Log::channel('revolut_pay')->emergency(['redirect'=>'redirect','response_status'=>$request->all,'trans'=> $transaction_id]);
        if(isset($request[0]) && isset($request[0]['state']) && $request[0]['state'] == 'captured'){
            return redirect()->route('revolutpay-redirectapp');
        }else{
            return redirect()->route('revolutpay-redirectapp');
        }
    }
    
    public function redirectToApp(){
        echo "<h1>Redirecting.....</h1>";
    }

}