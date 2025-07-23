<?php

namespace App\Http\Controllers\PaymentMethods\PayGo;

use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use DB;
use Illuminate\Http\Request;

use function GuzzleHttp\json_encode;

class PayGoController extends Controller
{
    use MerchantTrait;

    public function __construct()
    {
        // uat details
        // request url
        $this->base_url_uat = 'https://dss-gw-loadbal-uat01.digitalpaygo.com:9906/ServiceLayer/request/postRequest';
        // query url
        $this->query_url_uat = 'https://dss-gw-loadbal-uat01.digitalpaygo.com:9906/ServiceLayer/transaction/query';
        // password
        $this->api_secret_key_uat = "f857a6ca5e89ef7d62a1a95e4e77ba579d085de8ce4346efc32533ab6ff8ed58008e7844143bb4a971a91d2b1b35558c9e664103729ebf7784abeec22a3decfa";
        // user name
        $this->api_public_key_uat = "Ivenpro";
        // client id
        $this->auth_token_uat = "2033";
        // service id
        // $this->service_id_uat = "1";
        // $this->service_id = "1";
        $this->card_url_uat = "https://dss-gw-loadbal-uat01.digitalpaygo.com:9906/ServiceLayer/card/request";
        // $this->card_url = "https://dss-gw-loadbal-uat01.digitalpaygo.com:9906/ServiceLayer/card/request";
        $this->card_url = "https://dssl-payment-gateway.digitalpaygo.com/payment/v1/card";


        // production details
        // old urls
        //$this->base_url = 'https://dss-gw-loadbal-prod.digitalpaygo.com:7705/ServiceLayer/request/postRequest';
       // $this->query_url = 'https://dss-gw-loadbal-prod.digitalpaygo.com:7705/ServiceLayer/transaction/query';
       
       //New URL for PROD
        $this->base_url = 'https://dssl-payment-gateway.digitalpaygo.com/ServiceLayer/request/postRequest';
        $this->query_url = 'https://dssl-payment-gateway.digitalpaygo.com/ServiceLayer/transaction/query';
       
    }

    // payment option
    public function paymentRequest($request, $payment_option_config, $calling_from)
    {

        try {
            $user_name = $this->api_public_key_uat;
            $password = $this->api_secret_key_uat;
            $client_id = $this->auth_token_uat;
            $currency = $request->currency;

            $is_live = false;

            $base_url = $request->payment_type == "card" ? $this->card_url : $this->base_url_uat;
            //p($base_url);
            //$base_url =  $this->base_url_uat;
            $query_url = $this->base_url_uat;
            if ($payment_option_config->gateway_condition == 1) {
                $user_name = $payment_option_config->api_public_key;
                $password = $payment_option_config->api_secret_key;
                $client_id = $payment_option_config->auth_token;

                $is_live = true;
                $base_url = $request->payment_type == "card" ? $this->card_url : $this->base_url;
                $query_url = $this->base_url;
            }
            // check whether request is from driver or user
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                // $currency = $driver->Country->isoCode;
                $accountno = $driver->phoneNumber;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
                $country_code = $driver->Country->country_code;
            } else {
                $user = $request->user('api');
                // $currency = $user->Country->isoCode;
                $id = $user->id;
                $accountno = $user->UserNumber;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
                $country_code = $user->Country->country_code;
            }
            //p($base_url);
            $body = [];
            $transaction_id = $id . '_' . time();
            $msisdn = str_replace("+", "", $request->payment_phone_number);
            $accountno = str_replace("+", "", $request->payment_phone_number);
            // p($currency);
            $body = [
                "username" => $user_name,
                "password" => $password,
                "clientid" => $client_id,
                "amount" => $request->amount,
                "accountno" => $accountno,
                "msisdn" => $msisdn,
                "currencycode" => $currency,
                "transactionid" => $transaction_id,
                "timestamp" => date("Y/m/d H:i:s"),
            ];
            if ($request->payment_type == "mtn") {
                $body["serviceid"] = "1";
                $body["payload"] = [
                    "accounttype" => "MSISDN",
                    "narration" => "Make payment"
                ];
            } elseif ($request->payment_type == "zamtel") {
                $body["serviceid"] = $is_live ? "5" : "7";
            } else if ($request->payment_type == "airtel_money") {
                $body["serviceid"] = $is_live ? "7" : "8";
                $body["payload"] = [
                    "narration" => "Make payment",
                    "country" => $country_code
                ];
            } else if ($request->payment_type == "card") {

                // $rawPayload = '{"cardtype": "' . $request->card_type . '","cardnumber": "' . $request->card_number . '","expdate": "' . $request->exp_month . '-' . $request->exp_year . '","cvv": "' . $request->cvv . '","firstname": "' . $request->fname . '","surname": "' . $request->mname . '","email": "' . $request->email . '","street": "' . $request->street . '","city": "' . $request->city . '","country": "' . $country_code . '","postalcode": "' . $request->postal_code . '"}';
                // $ecryptedPayload = $this->encryptpayload(env('PAYMENT_PATH') . 'external.pub', $rawPayload);
                //p($rawPayload);
                // $body["serviceid"] = "4";
                // $body["resulturl"] = route('paygo-callback');
                // $body["payload"] = $ecryptedPayload;
                
                $body["serviceid"] = "13";
                $body["resulturl"] = route('paygo-callback');
                $body["narration"] = "pay";
                $body["payload"] = [
                    "firstname" => $request->fname,
                    "lastname"=> $request->mname,
                    "email"=> $request->email
                ];
            }
            //  p($body);


            $body_request = json_encode($body);
            // dd($body_request);
            //   p($base_url);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $body_request,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                ),
            ));


            //         $channel = curl_init();
            //         $header = Array('Content-Type: application/json',		
            // 		'Content-Length: ' . strlen($body_request));

            //     curl_setopt($channel, CURLOPT_URL, "https://dss-gw-loadbal-uat01.digitalpaygo.com:9906/ServiceLayer/card/request");
            //     curl_setopt($channel, CURLOPT_CONNECTTIMEOUT, 120);
            //     curl_setopt($channel, CURLOPT_TIMEOUT, 120);
            //     curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, false);
            //     curl_setopt($channel, CURLOPT_SSL_VERIFYHOST, false);
            //     curl_setopt($channel, CURLOPT_HTTPHEADER, $header);
            //     curl_setopt($channel, CURLOPT_POSTFIELDS, $body_request);
            //     curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
            //     curl_setopt($channel, CURLOPT_VERBOSE, true);

            //     $response = curl_exec($channel);

            \Log::channel('paygo_api')->emergency($body);
            $response = curl_exec($curl);
            // p($request->email);
            // dd($base_url,$response,$body_request);
            if ($response === false) {
                p('error');
            }
            $response_result = json_decode($response, true);
            // dd($base_url,$body_request,$response_result);
            //p($response_result);
            // \Log::channel('paygo_api')->emergency($response_result);
            curl_close($curl);
            if (isset($response_result['status']) && $response_result['status'] == 00) {
                DB::table('transactions')->insert([
                    'status' => 1, // for user
                    'reference_id' => $body["serviceid"],
                    'card_id' => NULL,
                    'payment_mode'=> $request->payment_type ?? "",
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'checkout_id' => NULL,
                    'booking_id' => $request->booking_id ? $request->booking_id : NULL,
                    'order_id' => $request->order_id ? $request->order_id : NULL,
                    'handyman_order_id' => $request->handyman_order_id ? $request->handyman_order_id : NULL,
                    'payment_transaction_id' => $transaction_id,
                    'payment_transaction' => $response, // response from payment gateway
                    'request_status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            return [
                'status' => 'PROCESSING',
                'url' => isset($response_result['url']) ? $response_result['url'] : "",
                'transaction_id' => $transaction_id,
                'success_url'=> route('paygo-success'),
                'fail_url'=> route('paygo-fail')
            ];
            // $msg = $response_result['statusDescription'] ? $response_result['statusDescription'] : "Error on payment server";
            // throw new \Exception($msg);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function Redirect(Request $request){
        \Log::channel('paygo_api')->emergency($request->all());
        $transactionId = $request['transactionid'];
            $transaction = DB::table('transactions')->where('payment_transaction_id',$transactionId)->first();
            // dd($transaction);
            if ($request['status'] == 00) { // success
                $request_status = 2;
                $request_status_text = "success";
                
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $transactionId])
                    ->update(['request_status'=>2,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($request->all())]);
                    
                return redirect(route('paygo-success'));
            } else if ($request['status'] == 15) { // processing
                $request_status = null;
                $request_status_text = "processing";
                
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $transactionId])
                    ->update(['request_status'=>1,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($request->all())]);
            }else{
                $request_status = 3;
                $request_status_text = "fail";
                
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $transactionId])
                    ->update(['request_status'=>3,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($request->all())]);
                    
                return redirect(route('paygo-fail'));
            }
            
            
            if($transaction->request_status == 2 && $transaction->status_message == 'SUCCESS'){
                return redirect(route('paygo-success'));
            }
            else{
                return redirect(route('paygo-cancel'));
            }
    }
    
   public function Success(Request $request)
    {
        \Log::channel('paygo_api')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Fail(Request $request)
    {
        \Log::channel('paygo_api')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }


    public function encryptpayload($keylocation, $payload)
    {
        $myfile = fopen($keylocation, "r") or die("Unable to open file!");
        $pubkey = fread($myfile, filesize($keylocation));
        $pubkey = "-----BEGIN PUBLIC KEY-----\r\n" . $pubkey . "\r\n-----END PUBLIC KEY-----";

        //openssl_public_encrypt($plaintext, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING)
        if (openssl_public_encrypt($payload, $encrypted, $pubkey))
            return base64_encode($encrypted);
        else
            throw new \Exception('Unable to encrypt payload. Perhaps it is bigger than the key size?');

        fclose($myfile);
    }


    public function paygoCallback(Request $request)
    {
        $data = $request->all();
        // dd($request->all());
        \Log::channel('paygo_api')->emergency($data);
        if(isset($data['transactionid'])){
            if (isset($data['status']) && $data['status'] == 00) {
                DB::table('transactions')->where('payment_transaction_id', $data['transactionid'])->update(['request_status' => 2, 'payment_transaction' => json_encode($data),'updated_at'=>date('Y-m-d H:i:s')]);
                return redirect(route('paygo-success'));
            }
            else if(isset($data['status']) && $data['status'] == 15){
                DB::table('transactions')->where('payment_transaction_id', $data['transactionid'])->update(['request_status' => 1, 'payment_transaction' => json_encode($data),'updated_at'=>date('Y-m-d H:i:s')]);
            }else{
                DB::table('transactions')->where('payment_transaction_id', $data['transactionid'])->update(['request_status' => 3, 'payment_transaction' => json_encode($data),'updated_at'=>date('Y-m-d H:i:s')]);
                return redirect(route('paygo-fail'));
            }
        }
        else if(isset($data['transactionID'])){
             if (isset($data['status']) && $data['status'] == 00) {
                DB::table('transactions')->where('payment_transaction_id', $data['transactionID'])->update(['request_status' => 2, 'payment_transaction' => json_encode($data),'updated_at'=>date('Y-m-d H:i:s')]);
                // dd('hello');
            }
            else if(isset($data['status']) && $data['status'] == 15){
                DB::table('transactions')->where('payment_transaction_id', $data['transactionID'])->update(['request_status' => 1, 'payment_transaction' => json_encode($data),'updated_at'=>date('Y-m-d H:i:s')]);
            }else{
                DB::table('transactions')->where('payment_transaction_id', $data['transactionID'])->update(['request_status' => 3, 'payment_transaction' => json_encode($data),'updated_at'=>date('Y-m-d H:i:s')]);
                // dd('hiiii');
            }
        }
        
    }

    public function paymentStatus(Request $request)
    {
        $tx_reference = $request->transaction_id; // order id

        $transaction =  DB::table('transactions')->where('payment_transaction_id', $tx_reference)->first();
        if($transaction->request_status == 2){
            $request_status = 2;
             $request_status_text = "success";
        }elseif($transaction->request_status == 3)
        {
           $request_status = 3; 
            $request_status_text = "failed";
        }else{

        $payment_option_config = DB::table('payment_options_configurations')->where('merchant_id', $transaction->merchant_id)->where('payment_option_id', $transaction->payment_option_id)->first();

        $user_name = $this->api_public_key_uat;
        $password = $this->api_secret_key_uat;
        $client_id = $this->auth_token_uat;

        $is_live = false;
        $base_url = $this->base_url_uat;
        $query_url = $this->base_url_uat;

        if ($payment_option_config->gateway_condition == 1) {
            $user_name = $payment_option_config->api_public_key;
            $password = $payment_option_config->api_secret_key;
            $client_id = $payment_option_config->auth_token;

            $is_live = true;
            $base_url = $this->base_url;
            $query_url = $this->base_url;
        }

        $curl = curl_init();

        $fields =  json_encode([
            "username" => $user_name,
            "password" => $password,
            "clientid" => $client_id,
            "serviceid" => $transaction->reference_id,
            "transactionid" => $tx_reference
        ]);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->query_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);
// p($response);
        // \Log::channel('paygo_api')->emergency($response);
        curl_close($curl);
        if (isset($response['status'])) {
            if ($response['status'] == 00) { // success
                $request_status = 2;
                $request_status_text = "success";
            } else if ($response['status'] == 15) { // processing
                $request_status = null;
                $request_status_text = "processing";
                $request_time = strtotime($transaction->created_at);
                $now = time();
                $time_diff = ($now - $request_time);
                // if ($time_diff > 180) {
                //     $request_status = 3; // marked as failed
                //     $request_status_text = "failed";
                // }
            } else { // failed
                $request_status = 3;
                $request_status_text = "failed";
            }
        }

        if ($request_status) {
             DB::table('transactions')->where('payment_transaction_id', $tx_reference)->update(['request_status' => $request_status, 'payment_transaction' => $response]);
        }
        }

        $payment_status =   $request_status == 2 ?  true : false;
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
    }
}





// namespace App\Http\Controllers\PaymentMethods\PayGo;

// use App\Http\Controllers\Controller;
// use App\Traits\MerchantTrait;
// use DB;
// use Illuminate\Http\Request;

// use function GuzzleHttp\json_encode;

// class PayGoController extends Controller
// {
//     use MerchantTrait;

//     public function __construct()
//     {
//         // uat details
//         // request url
//         $this->base_url_uat = 'https://dss-gw-loadbal-uat01.digitalpaygo.com:9906/ServiceLayer/request/postRequest';
//         // query url
//         $this->query_url_uat = 'https://dss-gw-loadbal-uat01.digitalpaygo.com:9906/ServiceLayer/transaction/query';
//         // password
//         $this->api_secret_key_uat = "f857a6ca5e89ef7d62a1a95e4e77ba579d085de8ce4346efc32533ab6ff8ed58008e7844143bb4a971a91d2b1b35558c9e664103729ebf7784abeec22a3decfa";
//         // user name
//         $this->api_public_key_uat = "Ivenpro";
//         // client id
//         $this->auth_token_uat = "2033";
//         // service id
//         // $this->service_id_uat = "1";
//         // $this->service_id = "1";
//         $this->card_url_uat = "https://dss-gw-loadbal-uat01.digitalpaygo.com:9906/ServiceLayer/card/request";
//         $this->card_url = "https://dss-gw-loadbal-uat01.digitalpaygo.com:9906/ServiceLayer/card/request";


//         // production details
//         // old urls
//         //$this->base_url = 'https://dss-gw-loadbal-prod.digitalpaygo.com:7705/ServiceLayer/request/postRequest';
//        // $this->query_url = 'https://dss-gw-loadbal-prod.digitalpaygo.com:7705/ServiceLayer/transaction/query';
       
//        //New URL for PROD
//         $this->base_url = 'https://dssl-payment-gateway.digitalpaygo.com/ServiceLayer/request/postRequest';
//         $this->query_url = 'https://dssl-payment-gateway.digitalpaygo.com/ServiceLayer/transaction/query';
       
//     }

//     // aamarPay payment option
//     public function paymentRequest($request, $payment_option_config, $calling_from)
//     {

//         try {

//             $user_name = $this->api_public_key_uat;
//             $password = $this->api_secret_key_uat;
//             $client_id = $this->auth_token_uat;

//             $is_live = false;

//             $base_url = $request->payment_type == "card" ? $this->card_url_uat : $this->base_url_uat;
//             //p($base_url);
//             //$base_url =  $this->base_url_uat;
//             $query_url = $this->base_url_uat;

//             if ($payment_option_config->gateway_condition == 1) {
//                 $user_name = $payment_option_config->api_public_key;
//                 $password = $payment_option_config->api_secret_key;
//                 $client_id = $payment_option_config->auth_token;

//                 $is_live = true;
//                 $base_url = $request->payment_type == "card" ? $this->card_url : $this->base_url;
//                 $query_url = $this->base_url;
//             }
//             // check whether request is from driver or user
//             if ($calling_from == "DRIVER") {
//                 $driver = $request->user('api-driver');
//                 $currency = $driver->Country->isoCode;
//                 $accountno = $driver->phoneNumber;
//                 $id = $driver->id;
//                 $merchant_id = $driver->merchant_id;
//                 $description = "driver wallet topup";
//                 $country_code = $driver->Country->country_code;
//             } else {
//                 $user = $request->user('api');
//                 $currency = $user->Country->isoCode;
//                 $id = $user->id;
//                 $accountno = $user->UserNumber;
//                 $merchant_id = $user->merchant_id;
//                 $description = "payment from user";
//                 $country_code = $user->Country->country_code;
//             }
//             //p($base_url);
//             $body = [];
//             $transaction_id = $id . '_' . time();
//             $msisdn = str_replace("+", "", $request->payment_phone_number);
//             $accountno = str_replace("+", "", $request->payment_phone_number);
            
//             $body = [
//                 "username" => $user_name,
//                 "password" => $password,
//                 "clientid" => $client_id,
//                 "amount" => $request->amount,
//                 "accountno" => $accountno,
//                 "msisdn" => $msisdn,
//                 "currencycode" => $currency,
//                 "transactionid" => $transaction_id,
//                 "timestamp" => date("Y/m/d H:i:s"),
//             ];
//             if ($request->payment_type == "mtn") {
//                 $body["serviceid"] = "1";
//                 $body["payload"] = [
//                     "accounttype" => "MSISDN",
//                     "narration" => "Make payment"
//                 ];
//             } elseif ($request->payment_type == "zamtel") {
//                 $body["serviceid"] = $is_live ? "5" : "7";
//             } else if ($request->payment_type == "airtel_money") {
//                 $body["serviceid"] = $is_live ? "7" : "8";
//                 $body["payload"] = [
//                     "narration" => "Make payment",
//                     "country" => $country_code
//                 ];
//             } else if ($request->payment_type == "card") {

//                 $rawPayload = '{"cardtype": "' . $request->card_type . '","cardnumber": "' . $request->card_number . '","expdate": "' . $request->exp_month . '-' . $request->exp_year . '","cvv": "' . $request->cvv . '","firstname": "' . $request->fname . '","surname": "' . $request->mname . '","email": "' . $request->email . '","street": "' . $request->street . '","city": "' . $request->city . '","country": "' . $country_code . '","postalcode": "' . $request->postal_code . '"}';
//                 $ecryptedPayload = $this->encryptpayload(env('PAYMENT_PATH') . 'external.pub', $rawPayload);
//                 //p($rawPayload);
//                 $body["serviceid"] = "4";
//                 $body["resulturl"] = route('paygo-callback');
//                 $body["payload"] = $ecryptedPayload;
//             }
//             //  p($body);


//             $body_request = json_encode($body);
//             //   p($base_url);
//             $curl = curl_init();
//             curl_setopt_array($curl, array(
//                 CURLOPT_URL => $base_url,
//                 CURLOPT_RETURNTRANSFER => true,
//                 CURLOPT_ENCODING => '',
//                 CURLOPT_MAXREDIRS => 10,
//                 CURLOPT_TIMEOUT => 0,
//                 CURLOPT_FOLLOWLOCATION => true,
//                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                 CURLOPT_CUSTOMREQUEST => 'POST',
//                 CURLOPT_POSTFIELDS => $body_request,
//                 CURLOPT_HTTPHEADER => array(
//                     'Content-Type: application/json',
//                 ),
//             ));


//             //         $channel = curl_init();
//             //         $header = Array('Content-Type: application/json',		
//             // 		'Content-Length: ' . strlen($body_request));

//             //     curl_setopt($channel, CURLOPT_URL, "https://dss-gw-loadbal-uat01.digitalpaygo.com:9906/ServiceLayer/card/request");
//             //     curl_setopt($channel, CURLOPT_CONNECTTIMEOUT, 120);
//             //     curl_setopt($channel, CURLOPT_TIMEOUT, 120);
//             //     curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, false);
//             //     curl_setopt($channel, CURLOPT_SSL_VERIFYHOST, false);
//             //     curl_setopt($channel, CURLOPT_HTTPHEADER, $header);
//             //     curl_setopt($channel, CURLOPT_POSTFIELDS, $body_request);
//             //     curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
//             //     curl_setopt($channel, CURLOPT_VERBOSE, true);

//             //     $response = curl_exec($channel);

//             \Log::channel('paygo_api')->emergency($body);
//             $response = curl_exec($curl);
//             if ($response === false) {
//                 p('error');
//             }
//             $response_result = json_decode($response, true);
//             //p($response_result);
//             \Log::channel('paygo_api')->emergency($response_result);
//             curl_close($curl);
//             if (isset($response_result['status']) && $response_result['status'] == 00) {
//                 DB::table('transactions')->insert([
//                     'status' => 1, // for user
//                     'reference_id' => $body["serviceid"],
//                     'card_id' => NULL,
//                     'user_id' => $calling_from == "USER" ? $id : NULL,
//                     'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
//                     'merchant_id' => $merchant_id,
//                     'payment_option_id' => $payment_option_config->payment_option_id,
//                     'checkout_id' => NULL,
//                     'booking_id' => $request->booking_id ? $request->booking_id : NULL,
//                     'order_id' => $request->order_id ? $request->order_id : NULL,
//                     'handyman_order_id' => $request->handyman_order_id ? $request->handyman_order_id : NULL,
//                     'payment_transaction_id' => $transaction_id,
//                     'payment_transaction' => $response, // response from payment gateway
//                     'request_status' => 1,
//                     'created_at' => date('Y-m-d H:i:s'),
//                     'updated_at' => date('Y-m-d H:i:s')
//                 ]);

//                 return [
//                     'status' => 'PROCESSING',
//                     'url' => "",
//                     'transaction_id' => $transaction_id
//                 ];
//             }
//             // $msg = $response_result['statusDescription'] ? $response_result['statusDescription'] : "Error on payment server";
//             throw new \Exception($msg);
//         } catch (\Exception $e) {
//             throw new \Exception($e->getMessage());
//         }
//     }



//     public function encryptpayload($keylocation, $payload)
//     {
//         $myfile = fopen($keylocation, "r") or die("Unable to open file!");
//         $pubkey = fread($myfile, filesize($keylocation));
//         $pubkey = "-----BEGIN PUBLIC KEY-----\r\n" . $pubkey . "\r\n-----END PUBLIC KEY-----";

//         //openssl_public_encrypt($plaintext, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING)
//         if (openssl_public_encrypt($payload, $encrypted, $pubkey))
//             return base64_encode($encrypted);
//         else
//             throw new \Exception('Unable to encrypt payload. Perhaps it is bigger than the key size?');

//         fclose($myfile);
//     }

//     // public function paygoCallback(Request $request)
//     // {
//     //     $data = $request->all();
//     //     \Log::channel('paygo_api')->emergency($data);
//     //     if (isset($data['response']) && $data['response']) {
//     //         // payment accepted successfully
//     //         // check payment confirmation

//     //         $transaction =  DB::table('transactions')->where('reference_id', $data['token'])->first();

//     //         $payment_option_config = DB::table('payment_options_configurations')->where('merchant_id', $transaction->merchant_id)->where('payment_option_id', $transaction->payment_option_id)->first();

//     //         // live details
//     //         $user_name = "";
//     //         $client_id = "";

//     //         // $is_live = true;
//     //         if ($payment_option_config->gateway_condition == 1) {
//     //             $user_name = $payment_option_config->api_public_key;
//     //             $client_id = $payment_option_config->auth_token;
//     //         }

//     //         $transaction_id = $transaction->payment_transaction_id;
//     //         $curl = curl_init();
//     //         $fields = array(
//     //             "AppKey" => $user_name,
//     //             "AuthKey" => $client_id,
//     //             "OrderID" => $transaction_id,
//     //             "token" => $data['token'],
//     //             "vendorID" => time(),
//     //         );
//     //         $fields = json_encode($fields);

//     //         curl_setopt_array($curl, array(
//     //             CURLOPT_URL => $this->base_url . 'paymentconfirmation',
//     //             CURLOPT_RETURNTRANSFER => true,
//     //             CURLOPT_ENCODING => '',
//     //             CURLOPT_MAXREDIRS => 10,
//     //             CURLOPT_TIMEOUT => 0,
//     //             CURLOPT_FOLLOWLOCATION => true,
//     //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//     //             CURLOPT_CUSTOMREQUEST => 'POST',
//     //             CURLOPT_POSTFIELDS => $fields,
//     //             CURLOPT_HTTPHEADER => array(
//     //                 'Content-Type: application/json'
//     //             ),
//     //         ));
//     //         $response = curl_exec($curl);
//     //         curl_close($curl);
//     //         $response = json_decode($response, true);
//     //         $request_status = 3;
//     //         if (isset($response['status'])) {
//     //             $request_status = 2;
//     //         }
//     //         DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update(['request_status' => $request_status, 'payment_transaction' => $response]);
//     //     }
//     // }


//     public function paygoCallback(Request $request)
//     {
//         $data = $request->all();
        
//         \Log::channel('paygo_api')->emergency($data);
//         if (isset($data['transactionID']) && $data['transactionID']) {
//             $request_status = 3;
//             if (isset($data['status']) && $data['status'] == 00) {
//                 $request_status = 2;
//             }
//             DB::table('transactions')->where('payment_transaction_id', $data['transactionID'])->update(['request_status' => $request_status, 'payment_transaction' => json_encode($data),'updated_at'=>date('Y-m-d H:i:s')]);
//         }
//     }

//     public function paymentStatus(Request $request)
//     {
//         $tx_reference = $request->transaction_id; // order id

//         $transaction =  DB::table('transactions')->where('payment_transaction_id', $tx_reference)->first();

//         $payment_option_config = DB::table('payment_options_configurations')->where('merchant_id', $transaction->merchant_id)->where('payment_option_id', $transaction->payment_option_id)->first();

//         $user_name = $this->api_public_key_uat;
//         $password = $this->api_secret_key_uat;
//         $client_id = $this->auth_token_uat;

//         $is_live = false;
//         $base_url = $this->base_url_uat;
//         $query_url = $this->base_url_uat;

//         if ($payment_option_config->gateway_condition == 1) {
//             $user_name = $payment_option_config->api_public_key;
//             $password = $payment_option_config->api_secret_key;
//             $client_id = $payment_option_config->auth_token;

//             $is_live = true;
//             $base_url = $this->base_url;
//             $query_url = $this->base_url;
//         }

//         $curl = curl_init();

//         $fields =  json_encode([
//             "username" => $user_name,
//             "password" => $password,
//             "clientid" => $client_id,
//             "serviceid" => $transaction->reference_id,
//             "transactionid" => $tx_reference
//         ]);

//         curl_setopt_array($curl, array(
//             CURLOPT_URL => $this->query_url,
//             CURLOPT_RETURNTRANSFER => true,
//             CURLOPT_ENCODING => '',
//             CURLOPT_MAXREDIRS => 10,
//             CURLOPT_TIMEOUT => 0,
//             CURLOPT_FOLLOWLOCATION => true,
//             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//             CURLOPT_CUSTOMREQUEST => 'POST',
//             CURLOPT_POSTFIELDS => $fields,
//             CURLOPT_HTTPHEADER => array(
//                 'Content-Type: application/json'
//             ),
//         ));

//         $response = curl_exec($curl);
//         $response = json_decode($response, true);

//         \Log::channel('paygo_api')->emergency($response);
//         curl_close($curl);
//         if (isset($response['status'])) {
//             if ($response['status'] == 00) { // success
//                 $request_status = 2;
//                 $request_status_text = "success";
//             } else if ($response['status'] == 15) { // processing
//                 $request_status = null;
//                 $request_status_text = "processing";
//                 $request_time = strtotime($transaction->created_at);
//                 $now = time();
//                 $time_diff = ($now - $request_time);
//                 if ($time_diff > 180) {
//                     $request_status = 3; // marked as failed
//                     $request_status_text = "failed";
//                 }
//             } else { // failed
//                 $request_status = 3;
//                 $request_status_text = "failed";
//             }
//         }

//         if ($request_status) {
//             //  DB::table('transactions')->where('payment_transaction_id', $tx_reference)->update(['request_status' => $request_status, 'payment_transaction' => $response]);
//         }

//         $payment_status =   $request_status == 2 ?  true : false;
//         return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
//     }
// } 
