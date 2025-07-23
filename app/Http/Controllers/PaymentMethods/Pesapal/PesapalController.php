<?php

namespace App\Http\Controllers\PaymentMethods\Pesapal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use checkout\encryption\Encryption;

class PesapalController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function getPesapalBaseUrl($env){
        return $env == 1 ? "https://pay.pesapal.com/v3" : "https://cybqa.pesapal.com/pesapalv3";
    }

    public function generateAuthToken($payment_option_config){
        $url = $this->getPesapalBaseUrl($payment_option_config->gateway_condition);
        $post_data =[
            'consumer_key' => $payment_option_config->api_public_key,
            'consumer_secret' => $payment_option_config->api_secret_key
        ];
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'/api/Auth/RequestToken',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);

        if($response['status'] == "200"){
            return $response['token'];
        }else{
            return   $response;
        }
    }

    public function RegisterIPN($url, $token){
        $post_data = [
            'url' => route('pesapal.ipn'),
            'ipn_notification_type' => 'POST'
        ];
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'/api/URLSetup/RegisterIPN',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$token,
                'Accept: application/json',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);
        if($response['status'] == "200"){
            return $response['ipn_id'];
        }else{
            return   $response;
        }
    }

    public function PesapalCheckout($request, $payment_option_config, $calling_from){
        DB::beginTransaction();
        try{
            if($calling_from == "USER"){
                $user = $request->user('api');
                $id = $user->id;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $email = $user->email;
                $phone = "";
                $merchant = $user->Merchant;
                $merchant_id = $user->merchant_id;
                $currency = $user->Country->isoCode;
            }else{
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $first_name = $driver->first_name;
                $last_name = $driver->last_name;
                $email = $driver->email;
                $phone = "";
                $merchant = $driver->Merchant;
                $merchant_id = $driver->merchant_id;
                $currency = $driver->Country->isoCode;
            }

            $url = $this->getPesapalBaseUrl($payment_option_config->gateway_condition);
            $token = $this->generateAuthToken($payment_option_config);
            if(is_array($token)){
                throw new \Exception($token['status'].', '.$token['error']['code']);
            }

            $ipn = $this->RegisterIPN($url, $token);
            if(is_array($ipn)){
                throw new \Exception($ipn['status'].', '.$ipn['error']['code']);
            }

            $transaction_id = 'Trans_'.date('YmdHis');
            // $phone = str_replace('+', '', $request->phone);
            $request_data = [
                "id" => $transaction_id,
                "currency" => $currency,
                "amount" => $request->amount,
                "description" => $merchant->BusinessName.' Payment',
                "callback_url" => route('pesapal.callback'),
                "notification_id" => $ipn,
                "billing_address" => [
                    "email_address" => $email,
                    "phone_number" => $phone,
                    "country_code" => "",
                    "first_name" => $first_name,
                    "middle_name" => "",
                    "last_name" => $last_name,
                    "line_1" => "",
                    "line_2" => "",
                    "city" => "",
                    "state" => "",
                    "postal_code" => "",
                    "zip_code" => ""
                ]
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url.'/api/Transactions/SubmitOrderRequest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Authorization: Bearer '.$token,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response,true);
            $url = $response['redirect_url'];

            DB::table('transactions')->insert([
                'user_id' => $calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'merchant_id' => $merchant_id,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'checkout_id' => NULL,
                'amount' => $currency.' '.$request->amount,
                'booking_id' => $request->booking_id,
                'order_id' => $request->order_id,
                'handyman_order_id' => $request->handyman_order_id,
                'payment_transaction_id' => $transaction_id,
                'payment_transaction' => NULL,
                'request_status' => 1,
            ]);
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        DB::commit();
        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $url ?? '',
            'success_url' => route('pesapal.success'),
            'fail_url' => route('pesapal.fail'),
            'transaction_id' => $transaction_id
        ];
    }

    public function PesapalIPN(Request $request){
        \Log::channel('pesapal')->emergency($request->all());
        // $data = $request->all();
        // if(!empty($data) && $data['request_status_code'] == 178){
        //     $trans = DB::table('transactions')->where(['payment_transaction_id' => $data['merchant_transaction_id'], 'request_status' => 1])->first();
        //     if (!empty($trans)){
        //         DB::table('transactions')
        //         ->where(['payment_transaction_id' => $data['merchant_transaction_id']])
        //         ->update(['request_status' => 2, 'payment_transaction' => json_encode($request->all())]);
        //     }
        // }else{
        //     DB::table('transactions')
        //         ->where(['payment_transaction_id' => $data['merchant_transaction_id']])
        //         ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all())]);
        // }
    }

    public function PesapalCallback(Request $request){
        $orderId = $request->OrderTrackingId;
        $merchant_ref = $request->OrderMerchantReference;
        $option = PaymentOption::select('id')->where('slug', 'PESAPAL')->first();
        $payment_option_config = $option->PaymentOptionConfiguration;
        $trans = DB::table('transactions')->where(['payment_transaction_id' => $merchant_ref, 'request_status' => 1])->first();
        if (!empty($trans)){
            $merchant_id = $trans->merchant_id;
            $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$option->id]])->first();
        }

        $url = $this->getPesapalBaseUrl($payment_option_config->gateway_condition);
        $token = $this->generateAuthToken($payment_option_config);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'/api/Transactions/GetTransactionStatus?orderTrackingId='.$orderId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Authorization: Bearer '.$token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);
        if($response['payment_status_description'] == "Completed" && $response['status'] == 200){
            $trans = DB::table('transactions')->where(['payment_transaction_id' => $merchant_ref, 'request_status' => 1])->first();
            if (!empty($trans)){
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $merchant_ref])
                    ->update(['request_status' => 2, 'payment_transaction' => json_encode($request->all())]);
            }
            return redirect()->route('pesapal.success');
        }else{
            DB::table('transactions')
                ->where(['payment_transaction_id' => $merchant_ref])
                ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all())]);
            return redirect()->route('pesapal.fail');
        }
    }

    public function paymentStatus($request){
        $tx_reference = $request->transaction_id; // order id
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $tx_reference)->first();
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

    public function PesapalSuccess(){
        echo "<h3 style='text-align: center'>Success!</h3>";
    }

    public function PesapalFailed(){
        echo "<h3 style='text-align: center'>Failed!</h3>";
    }
}
