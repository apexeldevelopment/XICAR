<?php

namespace App\Http\Controllers\PaymentMethods\DibsyPayment;

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

class DibsyPaymentController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getDibsyConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'DIBSY_PAYMENT')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getCardToken($publicKey, $request){
        try{
            // dd(substr($request->card_expiry_year, -2));
            $data = [
                "cardNumber"=>$request->card_number ? $request->card_number : "4242424242424242",
                "cardCVC"=>$request->card_cvv ? $request->card_cvv : "321",
                "cardExpiryMonth"=>substr($request->card_expiry_month, 0, 1) == "0" ? ltrim($request->card_expiry_month, "0") : "12",
                "cardExpiryYear"=>$request->card_expiry_year ? substr($request->card_expiry_year, -2) : "25",
                "cardHolder"=>$request->card_holder ? $request->card_holder : "Stan Marsh"
            ];
            $url = 'https://vault.dibsy.one/card-tokens';

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization:Bearer '.$publicKey,
                'Content-Type:application/json'
            ),
            ));

            $response = curl_exec($curl);
            // dd($response,$data);

            curl_close($curl);
            $res = json_decode($response,true);
            if(isset($res['cardToken'])){
                return $res['cardToken'];
            }
            else{
                return '';
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }


    public function paymentRequest($request, $payment_option_config, $calling_from)
    {
        try{
            DB::beginTransaction();
            $secretKey = $payment_option_config->api_secret_key;
            $publicKey = $payment_option_config->api_public_key;

            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            
            $token = $this->getCardToken($publicKey, $request);
            
            if(empty($token)){
                throw new \Exception('Card Token not generated');
            }
            $payment_option = $this->getDibsyConfig($merchant_id);
            $order_id = $id.'_'.time();
            $transaction_id = 'trans_'.time();
            $amount = number_format($request->amount, 2, '.', '');
            $currency = $request->currency;
            $data = [
                "amount"=> [
                    "value"=> (string)$amount,
                    "currency"=> $currency
                ],
                "description"=> "Order #".$order_id,
                "method"=> "creditcard",
                "cardToken"=> $token,
                "redirectUrl"=> route('dibsy-redirect',[$transaction_id]),
                "webhookUrl"=> route('dibsy-webhook',[$merchant_id, $transaction_id]),
                "metadata"=> [
                    "order_id"=> $order_id
                ]
            ];

            
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.dibsy.one/v2/payments',
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
                ),
            ));
            
            
            $response = json_decode(curl_exec($curl));
            // dd($url,$response,$data);
            curl_close($curl);
            if(isset($response->status) && $response->status == "open") {
                $url = $response->_links->checkout->href;
                DB::table('transactions')->insert([
                'user_id' => $calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'merchant_id' => $merchant_id,
                'payment_transaction_id'=> $transaction_id,
                'amount' => $request->amount,
                'payment_option_id' => $payment_option->payment_option_id,
                'request_status'=> 1,
                'reference_id'=> $order_id,
                'status_message'=> 'PENDING',
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

        return [
            'url' => $url ?? '',
            'transaction_id' => $transaction_id ?? '',
            'success_url' => route('dibsy-success'),
            'fail_url' => route('dibsy-fail'),
        ];
    }

    public function redirect(Request $request)
    {
        \Log::channel('dibsy_pay')->emergency($request->all());
        if($request->transactionId){
            $transaction = DB::table('transactions')->where('payment_transaction_id',$request->transactionId)->first();
            // dd($transaction);
            if($transaction->request_status == 2 && $transaction->status_message == 'SUCCESS'){
                return redirect(route('dibsy-success'));
            }
            else{
                return redirect(route('dibsy-fail'));
            }
        }else{
            throw new \Exception('Transaction not found');
        }
        
        
    }

    public function Success(Request $request)
    {
        \Log::channel('dibsy_pay')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Fail(Request $request)
    {
        \Log::channel('dibsy_pay')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }

    public function webhook(Request $request, $merchant_id, $transaction_id)
    {
        \Log::channel('dibsy_pay1')->emergency(["request"=>$request->all(), "merch"=>$merchant_id]);
        $data = $request->all();
        $id = $request->id;
        $payment_option = $this->getDibsyConfig($merchant_id);
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.dibsy.one/v2/payments/'.$id,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$payment_option->api_secret_key,
          ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);
        
       
         $response = json_decode($response);
          \Log::channel('dibsy_pay1')->emergency(["request"=>$request->all(), 'status_response'=>$response->status]);
        // echo $response;
        
        if(isset($response)){
            if($response->status == 'succeeded'){
                DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                    'request_status' => 2,
                    'status_message' =>'SUCCESS'
                ]);
            }else{
                DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                    'request_status' => 3,
                    'status_message' =>'FAILED'
                ]);
            }
        }else{
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                    'request_status' => 4,
                    'status_message' =>'OTHER'
                ]);
        }
        

        
    }
    
    public function paymentStatus(Request $request){
        $transactionId = $request->transaction_id; 
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        if($transaction_table->request_status == 1)
        {
            $request_status_text = "processing";
        }
        else if($transaction_table->request_status == 2)
        {
            $request_status_text = "success";
        }
        else
        {
            $request_status_text = "failed";
        }
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
    }
}
