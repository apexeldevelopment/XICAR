<?php

namespace App\Http\Controllers\PaymentMethods\PayPay;
use App\Http\Controllers\Controller;
use App\Models\UserCard;
use hisorange\BrowserDetect\Exceptions\Exception;
use Illuminate\Http\Request;
use DB;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use App\Traits\ContentTrait;
use App\Models\Transaction;
use App\Models\DriverCard;
use App\Models\PaymentOptionsConfiguration;
use PayPay\OpenPaymentAPI\Client;
use PayPay\OpenPaymentAPI\Models\CreateQrCodePayload;
use PayPay\OpenPaymentAPI\Models\OrderItem;
use PayPay\OpenPaymentAPI\Models\CapturePaymentAuthPayload;
use Log;
use App\Models\Onesignal;
use App\Models\User;
use App\Models\Driver;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;

class PaypayController extends Controller
{
    use ApiResponseTrait, MerchantTrait, ContentTrait;

    public function __construct()
    {
    }



//    Card payment by webview

    public function paymentRequest($request,$payment_option_config,$calling_from){
        try {

            // check whether request is from driver or user
            if($calling_from == "DRIVER")
            {
                $status = 2;
                $driver = $request->user('api-driver');
                $code = $driver->Country->phonecode;
                $country_code = $driver->Country->country_code;
                $country = $driver->Country;
                $country_name = $country->CountryName;
                $currency = $driver->Country->isoCode;
                $phone_number = $driver->phoneNumber;
                $logged_user = $driver;
                $user_merchant_id = $driver->driver_merchant_id;
                $first_name = $driver->first_name;
                $last_name = $driver->last_name;
                $email = $driver->email;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
            }
            elseif($calling_from == "USER")
            {
                $status = 1;
                $user = $request->user('api');
                $code = $user->Country->phonecode;
                $country = $user->Country;
                $country_name = $country->CountryName;
                $currency = $user->Country->isoCode;
                $phone_number = $user->UserPhone;
                $logged_user = $user;
                $user_merchant_id = $user->user_merchant_id;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $email = $user->email;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
                $country_code = $user->Country->country_code;
            }
            elseif($calling_from == "BOOKING")
            {
                $status = 3;
                $booking = Booking::find($request->booking_id);
                $user = $booking->User;
                $code = $user->Country->phonecode;
                $country = $user->Country;
                $country_name = $country->CountryName;
                $currency = $user->Country->isoCode;
                $phone_number = $user->UserPhone;
                $logged_user = $user;
                $user_merchant_id = $user->user_merchant_id;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $email = $user->email;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
                $country_code = $user->Country->country_code;
            }

            $amount = $request->amount;
            $transaction_id = $id.'_'.time();


            $payment_data = json_decode($payment_option_config->additional_data,true);

            $curl = curl_init();
            $request_no = $transaction_id;
            $partner_id=$payment_data['partner_id'];
            $sales_product_code=$payment_data['sales_product_code'];

            $public_key = $payment_option_config->api_public_key;
            $private_key = $payment_option_config->api_secret_key;
            $paypay_public_key = $payment_option_config->auth_token;

            $biz_content = [
                "payer_platform_type" => "1",
                "payer_ip"=> $_SERVER['REMOTE_ADDR'],
                "sale_product_code"=> $sales_product_code,
                "cashier_type"=> "SDK",
                "timeout_express"=> "2h",
                "trade_info"=> [
                    "out_trade_no"=> $request_no,
                    "subject"=> "Serviceexpenses",
                    "currency"=> $currency,
                    "price"=> $amount,
                    "quantity"=> "1",
                    "total_amount"=> $amount,
                    "payee_identity_type"=> "1",
                    "payee_identity"=> $partner_id,
                    "biz_no" => ""
                ]
            ];
            $biz_content = json_encode($biz_content);
            // p($biz_content);
            $encrypt_biz_content = $this->privEncrypt($biz_content,$private_key);
//            p($encrypt_biz_content);


            $timestamp = date("Y-m-d H:i:s");
            $param = [
                "request_no"=> $request_no,
                "charset"=> "UTF-8",
                "partner_id"=> $partner_id,
                "service"=> "instant_trade",
                "biz_content"=> $encrypt_biz_content,
                "format"=> "JSON",
                "timestamp" => date("Y-m-d H:i:s"),
                "version"=> "1.0",
                "language"=> "en",
                "sign_type"=> "RSA",
            ];
            ksort($param);

            $data = '';
            foreach ($param as $key => $value) {
                if ($key !== 'sign' && $key !== 'sign_type') {
                    $data .= $key . '=' . $value . '&';
                }
            }
            $data = rtrim($data, '&');

            $sign_str = $this->privateSign($data, $private_key);

            $param['sign'] = $sign_str;

            foreach ($param as $key => $value) {
                $param[$key] = urlencode($value);
            }

            // echo json_encode($param);die;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://gateway.paypayafrica.com/recv.do',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($param),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $res= curl_exec($curl);

            // $res = json_decode($res);
            // p($res);
            if (curl_errno($curl)) {
                $error_msg = curl_error($curl);
                throw new \Exception($error_msg);
            }
            $response = $this->verifySign($res, $paypay_public_key);
            // p($response);
            curl_close($curl);
            $res = json_decode($res);
            if($res->msg=="success"){
                $web_view_url = $response['biz_content']['dynamic_link'];
                $redirect_url =route("paypay-callback",['TransID'=>$transaction_id]);

                $payment_transaction = [
                    'type'=>'payment request',
                    'data'=>$response
                ];
                // \Log::channel('paypay_payment_api')->emergency($payment_transaction);

                $tx_reference =  $transaction_id;

                // enter data
                DB::table('transactions')->insert([
                    'status' => $status, // 1 for user, 2 for driver, 3 for booking
                    'card_id' => NULL,
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'checkout_id' => NULL,
                    'payment_transaction_id' => $transaction_id,
                    'payment_transaction' => json_encode($payment_transaction),
                    'reference_id' => $tx_reference, // payment reference id
                    'amount' => $amount, // amount
                    'request_status' => 1,
                    'status_message' => "pending",
                    'booking_id' => ($status==3)?$request->booking_id:NULL,
                ]);


                return [
                    'status'=>'NEED_TO_OPEN_WEBVIEW',
                    'url'=>$web_view_url,
                    'redirect_url'=>$redirect_url,
                    'trasaction_id' => $transaction_id
                ];
            }
            else{
                throw new \Exception($res->msg);
            }
        }catch(\Exception $e)
        {
            throw new Exception($e->getMessage());
        }

    }

    public function PaymentCallBackOld(Request $request)
    {
        // p('heyy');
         try
         {
            $request_response = $request->all();
            // p($request_response);
            $data = [
                'type'=>'callback notification',
                'data'=>$request_response
            ];
            Log::channel('paypay_payment_api')->emergency($request_response);
            // p($request_response);

             $tx_reference = $request_response['TransID']; // order id
             $arr_data = DB::table("transactions")->where('reference_id', $tx_reference)->first();
             // p($arr_data);
             $payment_option = PaymentOptionsConfiguration::where([['merchant_id','=',$arr_data->merchant_id],['payment_option_id','=',$arr_data->payment_option_id]])->first();
             $payment_data = json_decode($payment_option->additional_data,true);
             $partner_id=$payment_data['partner_id'];
             $sales_product_code=$payment_data['sales_product_code'];
             $public_key = $payment_option->api_public_key;
             $private_key = $payment_option->api_secret_key;
             $paypay_public_key = $payment_option->auth_token;

             $timestamp = date('Y-m-d H:i:s');

             $biz_content["out_trade_no"] = "$tx_reference";

             $biz_content = json_encode($biz_content);

             $params = array(
                 'service' => 'trade_query', //query order,
                 'partner_id' => $partner_id,
                 'request_no' => time() . rand(1000, 9999),
                 'biz_content' => $biz_content,
                 'timestamp' => $timestamp,
                 'format' => 'JSON',
                 'charset' => 'UTF-8',
                 'version' => '1.0',
                 'language' => 'en',
                 'sign_type' => 'RSA',
             );


// encode the encrypted biz_content value as base64
             $encrypt_biz_content = $this->privEncrypt($biz_content,$private_key);
             $params['biz_content'] = $encrypt_biz_content;

// sort the parameters in ASCII order
             ksort($params);

// Step 2: generate the signature with SHA1withRSA
// create the data string by concatenating the parameter key-value pairs
             $data = '';
             foreach ($params as $key => $value) {
                 if ($key !== 'sign' && $key !== 'sign_type') {
                     $data .= $key . '=' . $value . '&';
                 }
             }
             $data = rtrim($data, '&');


// sign the data with the private key
             $data = rtrim($data, '&');

             $sign_str = $this->privateSign($data, $private_key);

             $params['sign'] = $sign_str;

// urlencode all values
             foreach ($params as $key => $value) {
                 $params[$key] = urlencode($value);
             }

//             echo json_encode($params);

             $curl = curl_init();

             curl_setopt_array($curl, array(
                 CURLOPT_URL => 'https://gateway.paypayafrica.com/recv.do',
                 CURLOPT_RETURNTRANSFER => true,
                 CURLOPT_ENCODING => '',
                 CURLOPT_MAXREDIRS => 10,
                 CURLOPT_TIMEOUT => 0,
                 CURLOPT_FOLLOWLOCATION => true,
                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                 CURLOPT_CUSTOMREQUEST => 'POST',
                 CURLOPT_POSTFIELDS => json_encode($params),
                 CURLOPT_HTTPHEADER => array(
                     'Content-Type: application/json'
                 ),
             ));

             $response = curl_exec($curl);
             $res = json_decode($response);
             if (curl_errno($curl)) {
                 $error_msg = curl_error($curl);
                 throw new \Exception($error_msg);
             }

             curl_close($curl);

            if($response->msg=='success' && ($response->biz_content->status=="TRADE_SUCCESS" || $response->biz_content->status=="TRADE_FINISHED")){
                $transaction = Transaction::where('reference_id', $tx_reference)->first();
                $transaction->request_status  = 2;
                $transaction->status_message  = "Successful payment";
                $transaction->save();
            }
            else{
                throw new \Exception($res->msg);
            }

        }catch(\Exception $e)
        {

    //        p($e->getLine(),0);
            return $e->getMessage();
            //p($e->getTrace());
        }

    }

    public function PaymentCallBack(Request $request)
    {
         try
         {
            $request_response = $request->all();
            // p($request_response);
            $data = [
                'type'=>'callback notification',
                'data'=>$request_response
            ];
            Log::channel('paypay_payment_api')->emergency($request_response);
            // p($request_response);

             $tx_reference = $request_response['out_trade_no']; // order id
             $arr_data = DB::table("transactions")->where('reference_id', $tx_reference)->first();
             // p($arr_data);

            if($arr_data->request_status==1){
                // if($request_response['status']=="TRADE_SUCCESS" || $request_response['status']=="TRADE_FINISHED"){
                if($request_response['status']=="TRADE_SUCCESS"){
                    $transaction = Transaction::where('reference_id', $tx_reference)->first();
                    $transaction->request_status  = 2;
                    $transaction->status_message  = "Successful payment";
                    $transaction->save();
                    if($transaction->status == 1){
                        $user = User::find($transaction->user_id);
                        $paramArray = array(
                            'user_id' => $user->id,
                            'booking_id' => NULL,
                            'amount' => $transaction->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'payment_option_id' => $transaction->payment_option_id,
                            'transaction_id' => 2,
                        );
                        WalletTransaction::UserWalletCredit($paramArray);



                        $notification_data['notification_type'] = 'PAYPAY_PAYMENT_SUCCESS';
                        $notification_data['segment_data'] = [];
                        $notification_data['segment_type'] = '';
                        // send success notification to user
                        $arr_param = array(
                            'user_id' => $user->id,
                            'data'=>$notification_data,
                            'message'=>"Paypay Payment successful",
                            'merchant_id'=>$user->merchant_id,
                            'title' => "Paypay Payment successful",
                            'large_icon'=>""
                        );
                        Onesignal::UserPushMessage($arr_param);

                    }elseif($transaction->status == 2){
                        $driver = Driver::find($transaction->driver_id);
                        // Log::channel('paypay_payment_api')->emergency($request_response);
                        $paramArray = array(
                            'driver_id' => $driver->id,
                            'booking_id' => NULL,
                            'amount' => $transaction->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'payment_option_id' => $transaction->payment_option_id,
                            // 'transaction_id' => 2,
                        );
                        WalletTransaction::WalletCredit($paramArray);


                        // send notification to driver
                        $notification_data['notification_type'] = 'PAYPAY_PAYMENT_SUCCESS';
                        $notification_data['segment_type'] = '';
                        $notification_data['segment_data'] = [];
                        $arr_param = array(
                            'driver_id' => $driver->id,
                            'large_icon' => "",
                            'data'=>$notification_data,
                            'message'=>"Paypay Payment successful",
                            'merchant_id'=>$driver->merchant_id,
                            'title' => 'Paypay Payment successful',
                        );
                        Onesignal::DriverPushMessage($arr_param);

                    }
                    elseif($transaction->status == 3 && !empty($transaction->booking_id)){
                        Booking::where('id', $transaction->booking_id)->update(['payment_status' => 1]);
                        $booking = Booking::find($transaction->booking_id);

                        $notification_data['notification_type'] = 'PAYPAY_PAYMENT_SUCCESS';
                        $notification_data['segment_data'] = [];
                        $notification_data['segment_type'] = '';
                        // send success notification to user
                        $arr_param = array(
                            'user_id' => $booking->user_id,
                            'data'=>$notification_data,
                            'message'=>"Paypay Payment successful",
                            'merchant_id'=>$booking->merchant_id,
                            'title' => "Paypay Payment successful",
                            'large_icon'=>""
                        );
                        Onesignal::UserPushMessage($arr_param);

                    }
                    
                }
                else{
                    throw new \Exception($res->msg);
                }
                return "success";
            }


        }catch(\Exception $e)
        {

    //        p($e->getLine(),0);
                // p($e->getMessage());
            return $e->getMessage();
            //p($e->getTrace());
        }

    }

    public function paymentStatus($request,$payment_option_config,$calling_from)
    {
        // check whether request is from driver or user
        if($calling_from == "DRIVER")
        {
            $driver = $request->user('api-driver');
            $code = $driver->Country->phonecode;
            $country_code = $driver->Country->country_code;
            $country = $driver->Country;
            $country_name = $country->CountryName;
            $currency = $driver->Country->isoCode;
            $phone_number = $driver->phoneNumber;
            $logged_user = $driver;
            $user_merchant_id = $driver->driver_merchant_id;
            $first_name = $driver->first_name;
            $last_name = $driver->last_name;
            $email = $driver->email;
            $id = $driver->id;
            $merchant_id = $driver->merchant_id;
            $description = "driver wallet topup";
        }
        else
        {
            $user = $request->user('api');
            $code = $user->Country->phonecode;
            $country = $user->Country;
            $country_name = $country->CountryName;
            $currency = $user->Country->isoCode;
            $phone_number = $user->UserPhone;
            $logged_user = $user;
            $user_merchant_id = $user->user_merchant_id;
            $first_name = $user->first_name;
            $last_name = $user->last_name;
            $email = $user->email;
            $id = $user->id;
            $merchant_id = $user->merchant_id;
            $description = "payment from user";
            $country_code = $user->Country->country_code;
        }


        // query created order detail
        $biz_content["out_trade_no"] = $request->transaction_id;
        $payment_data = json_decode($payment_option_config->additional_data,true);
        $partner_id=$payment_data['partner_id'];
        $sales_product_code=$payment_data['sales_product_code'];

        $public_key = $payment_option_config->api_public_key;
        $private_key = $payment_option_config->api_secret_key;
        $paypay_public_key = $payment_option_config->auth_token;

        $bizContentJson = json_encode($biz_content);
        $encrypt_biz_content = $this->privEncrypt($bizContentJson,$private_key);

        $params = array(
            'service' => 'trade_query', //query order,
            'partner_id' => $partner_id,
            'request_no' => time() . rand(1000, 9999),
            'biz_content' => $encrypt_biz_content,
            'timestamp' => date('Y-m-d H:i:s'),
            'format' => 'JSON',
            'charset' => 'UTF-8',
            'version' => '1.0',
            'language' => 'en',
            'sign_type' => 'RSA',
        );

        // sort the parameters in ASCII order
                ksort($params);

        // Step 2: generate the signature with SHA1withRSA
        // create the data string by concatenating the parameter key-value pairs
                $data = '';
                foreach ($params as $key => $value) {
                    if ($key !== 'sign' && $key !== 'sign_type') {
                        $data .= $key . '=' . $value . '&';
                    }
                }
                $data = rtrim($data, '&');


        $sign_str = $this->privateSign($data, $private_key);

// add the signature to the request parameters
        $params['sign'] = $sign_str;

// urlencode all values
        foreach ($params as $key => $value) {
            $params[$key] = urlencode($value);
        }

//        echo json_encode($params);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://gateway.paypayafrica.com/recv.do',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $res= curl_exec($curl);

        // $res = json_decode($res);
        // p($res);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            throw new \Exception($error_msg);
        }
        $response = $this->verifySign($res, $paypay_public_key);
        // p($response);
        curl_close($curl);
        $res = json_decode($res);


        $tx_reference = $request->transaction_id; // order id
        $transaction =  DB::table("transactions")->where('reference_id',$tx_reference)->first();
        // check payment status
        $request_status = 1;
        $request_status_text = "processing";
        if($res->msg=='success' && ($res->biz_content->status=="TRADE_SUCCESS" || $res->biz_content->status=="TRADE_FINISHED")){
            $request_status = 2;
        }
        elseif($res->msg=='success' && $res->biz_content->status=="WAIT_BUYER_PAY"){
            $request_status = 1;
            $request_status_text = "processing";
        }
        else{
            $request_status = 3;
            $request_status_text = "failed";
        }
        DB::table('transactions')->where('reference_id', $tx_reference)->update(['request_status' => $request_status, 'status_message' => "Successful payment",'updated_at'=>date('Y-m-d H:i:s')]);

        $payment_status =   $request_status == 2 ?  true : false;
        return $return = [
            'payment_status' =>$payment_status,
            'request_status'=>$request_status];
    }

    function getPrivateKey($key)
    {
        // $key = "MIICWwIBAAKBgQC5Dqq07Si9grlzPck5+38L+cZhehWwiKkyC8kif2qMPJSRrbEWDXnwb+mRmQaJXWrqesPe4GfFwyKDvBhqWS80rDYF0qh3RliTgJdFEV60Mf6KCAIT0C7+pEJO6dD2Wjqe/l7dd5GW0aYOhglyVfc2YeDf1Dk2y/qXxetFAgDarQIDAQABAoGAEV/QJSZzAb/pO2mcn+X92pj7yCEXMjjScdFrc+K0lTAG3tqI2sIvJaTMMBBG7dSoehVGmIFHHOkiL24UeL+gz888PdZm/aPbE12zL9di7/kXObjhCMh2gLvx/RsEcgfYoveOUTUqTWviqqqlcIYsQviUhCBmlYvhv1sUwakwJB0CQQDnnXfGL10wAPvRxFaswuKPafNvQUOHyNwJODM4dG6stvWgQ4p8sbcKA0Tx++3qfe4omRRnT+AI9Gbc5A7RHE23AkEAzIpdQpFQjjhWXwrznIk0x8SGnaGU7q9lCAQys1Xppaonl2LQBtXyk+qTb69bPww6QDVVegeBWMA98KMpIQyauwJAWrlkvD27S91mxmEY7m0cH78Juu+eiyaTgg0Ai0GYRJEaH5+1NGjMYOCs9fiP1gVj74Ue5+Tyxa8uR6IRZ7mlewJAHOwXwdjwbhvTQr82sVTJbNICQvndKF8OxzJoxOkKD83eqU5kogLQuU+7J1jBa0ncVsXz3zx+csFEQOmhYDX7DwJADG+R31K+0cO/otLhy7Q1orn+sPhAE11F10xNpdhvoTzKLck9r4W0OjvXnxbq1N8Q181/V1yedoDX9bfw4pNUBw==";
        $pubPem = chunk_split($key, 64, "\n");
        $content = "-----BEGIN RSA PRIVATE KEY-----\n" . $pubPem . "-----END RSA PRIVATE KEY-----\n";
        return openssl_pkey_get_private($content);
    }

    function getPublicKey($key)
    {

        // $key = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC5Dqq07Si9grlzPck5+38L+cZhehWwiKkyC8kif2qMPJSRrbEWDXnwb+mRmQaJXWrqesPe4GfFwyKDvBhqWS80rDYF0qh3RliTgJdFEV60Mf6KCAIT0C7+pEJO6dD2Wjqe/l7dd5GW0aYOhglyVfc2YeDf1Dk2y/qXxetFAgDarQIDAQAB";
        $pubPem = chunk_split($key, 64, "\n");
        $content = "-----BEGIN PUBLIC KEY-----\n" . $pubPem . "-----END PUBLIC KEY-----\n";
        return openssl_pkey_get_public($content);
    }

    function getPaypayPublicKey($key)
    {

//         $content = "-----BEGIN PUBLIC KEY-----
// MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArL1akdPqJVYIGI4vGNiNdvoxn7TWYOorLrNOBz3BP2yVSf31L6yPbQIs8hn59iOzbWy8raXAYWjYgM9Lh6h26XutwmEjZHqqoH5pLDYvZALMxEwunDpeTFrikuej0nWxjmpA9m4eicXcJbCMJowL47a5Jw61VkF+wbIj5vxEcSN4SSddJ04zEye1iwkWi9myecU39Do1THBN62ZKiGtd8jqAqKuDzLtch2mcEjMlgi51RM3IhxtYGY98JE6ICcVu+VDcsAX+OWwOXaWGyv755TQG6V8fnYO+Qd4R13jO+32V+EgizHQirhVayAFQGbTBSPIg85G8gVNU64SxbZ5JXQIDAQAB
// -----END PUBLIC KEY-----";
//        p($content);
//        p(openssl_pkey_get_public($content));
        $pubPem = chunk_split($key, 64, "\n");
        $content = "-----BEGIN PUBLIC KEY-----\n" . $pubPem . "-----END PUBLIC KEY-----\n";
        return openssl_pkey_get_public($content);
    }

    function privEncrypt($data = '',$private_key)
    {
        $privatePEMKey = $this->getPrivateKey($private_key);

        if(!$privatePEMKey){
            die('invalid private key');
        }
        $encrypted = '';
        $plainData = str_split($data, 117);
        foreach($plainData as $chunk)
        {
            $partialEncrypted = '';
            $encryptionOk = openssl_private_encrypt($chunk, $partialEncrypted, $privatePEMKey);
            if($encryptionOk === false){return false;}
            $encrypted .= $partialEncrypted;
        }
        return base64_encode($encrypted);
    }

    function pubEncrypt($data = '', $public_key)
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_public_encrypt($data,$encrypted , getPublicKey($public_key)) ? base64_encode($encrypted) : null;
    }

    function privDecrypt($encrypted = '',$private_key)
    {
        $publicPEMKey = getPrivateKey($private_key);
        $decrypted = '';
        $data = str_split(base64_decode($encrypted), 128);
        foreach($data as $chunk)
        {
            $partial = '';
            $decryptionOK = openssl_public_decrypt($chunk, $partial, $publicPEMKey, OPENSSL_PKCS1_PADDING);
            if($decryptionOK === false){return false;}
            $decrypted .= $partial;
        }
        return $decrypted;
    }

    function publicDecrypt($encrypted = '',$public_key)
    {
        if (!is_string($encrypted)) {
            return null;
        }
        $publicPEMKey = getPublicKey($public_key);
        $decrypted = '';
        $data = str_split(base64_decode($encrypted), 128);
        foreach($data as $chunk)
        {
            $partial = '';
            $decryptionOK = openssl_public_decrypt($chunk, $partial, $publicPEMKey, OPENSSL_PKCS1_PADDING);
            if($decryptionOK === false){return false;}
            $decrypted .= $partial;
        }
        return $decrypted;
    }

    function signVerify($str, $sign, $public_key) {
        $key = getPublicKey($public_key);
        $sign = base64_decode($sign);
        $verify = openssl_verify($str, $sign, $key);
        openssl_free_key($key);
        return $verify;
    }

    function publicSignVerify($str, $sign, $paypay_public_key) {
        $key = $this->getPaypayPublicKey($paypay_public_key);
        $sign = base64_decode($sign);
        $verfiy = openssl_verify($str, $sign, $key);
        openssl_free_key($key);
        return $verfiy === 1;
    }

    function privateSign($data, $private_key) {
        $pId = $this->getPrivateKey($private_key);
        $signature = '';
        openssl_sign($data, $signature, $pId);
        openssl_free_key($pId);
        $r = base64_encode($signature);
        error_log("我生成的签名: $r");
        return base64_encode($signature);
    }

    function ddurldecord($params) {
        $ret = [];
        foreach ($params as $k => $v) {
//            $ret[] = "$k=" . urlencode(urlencode($v));
            $ret[$k] = urlencode(urlencode($v));
        }
//        return implode("&", $ret);
        return $ret;
    }

    function curlPost($params) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, API_GATEWAY);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE );
        curl_setopt($curl, CURLOPT_POST, 1);
        $str = ddurldecord($params);
        error_log("参数:" . $str);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $str);
        $data = curl_exec($curl);
        if (curl_error($curl)) {
        } else {
            return $data;
        }
        curl_close($curl);
    }

    function verifySign($res, $paypay_public_key) {
        $data = json_decode($res, true);
//        p($data);
        if ($data['sign_type'] == 'RSA') {
            $sign = $data['sign'];
            $data['sign_type'] = null;
            $data['sign'] = null;
            ksort($data);
            $str = $this->formatData($data);
            error_log("服务器的签名" . $sign);
            error_log("验证签名前:" . $str);
            if ($this->publicSignVerify($str, $sign, $paypay_public_key)) {
                return $data;
            }
        }
        return false;
    }

    function verifySign2($res) {
        parse_str($res, $data);
        if ($data['sign_type'] == 'RSA') {
            $sign = $data['sign'];
            $data['sign_type'] = null;
            $data['sign'] = null;
            ksort($data);
            $str = formatData($data);
            error_log("服务器的签名" . $sign);
            error_log("验证签名前:" . $str);
            if (publicSignVerify($str, $sign)) {
                return $data;
            }
        }
        return false;
    }

    function formatData($data) {
        $str = [];
        foreach ($data as $k => $v) {
            if ($v == null) {
                continue;
            }
            if (!is_scalar($v)) {
                $v = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $str[] = "$k=$v";
        }
//        p($str);
        return implode("&", $str);
    }
}
