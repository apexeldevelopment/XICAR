<?php

namespace App\Http\Controllers\PaymentMethods\KPay;

use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use DB;
use Illuminate\Http\Request;

use function GuzzleHttp\json_encode;

class KPayController extends Controller
{
    use MerchantTrait;

    public function __construct()
    {
        $this->base_url = 'https://pay.esicia.com/';
    }
    // aamarPay payment option
    public function paymentRequest($request, $payment_option_config, $calling_from)
    {
        try {
            $api_public_key = "bluemoric";
            $api_secret_key = "8Rxp0t";
            $is_live = false;
            $base_url = $this->base_url;

            if ($payment_option_config->gateway_condition == 1) {
                $api_public_key = $payment_option_config->api_public_key;
                $api_secret_key = $payment_option_config->api_secret_key;
                $base_url = "https://pay.esicia.rw";
                $is_live = true;
            }
            // check whether request is from driver or user
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $email = $driver->email;
                $name = $driver->first_name . ' ' . $driver->last_name;
                $phoneCode = $driver->Country->phonecode;
                $currency = $driver->Country->isoCode;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
            } else {
                $user = $request->user('api');
                $email = $user->email;
                $name = $user->first_name . ' ' . $user->last_name;
                $currency = $user->Country->isoCode;
                $phoneCode = $user->Country->phonecode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
            }

            $return_url = route("kpay-return");
            $redirect_url = route("kpay-redirect");
            $phone_num = $request->payment_phone_number;
            $transaction_id = $id . '_' . time();
            $fields = array(
                "details" => $description,
                "action" => "pay",
                "msisdn" => str_replace("+", "", $phone_num),
                "refid" => $transaction_id,
                "amount" => $request->amount,
                "email" => $email,
                "cname" => $name,
                "cnumber" => str_replace($phoneCode, "", $phone_num),
                "pmethod" => $request->payment_type,
                "retailerid" => $payment_option_config->auth_token,
                "returl" => $return_url, //"https://msprojects.apporioproducts.com/multi-service-v2/public/api/kpay-return",
                "redirecturl" => $redirect_url, //"https://msprojects.apporioproducts.com/multi-service-v2/public/api/kpay-redirect",
                "bankid" => "000",
            );

            $curl = curl_init();

            $fields = json_encode($fields);
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $fields,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode("$api_public_key:$api_secret_key")
                ),
            ));

            $response = curl_exec($curl);
            $response = json_decode($response, true);
            $arr = [
                'request_response' => $response,
                'callback_response' => []
            ];
            curl_close($curl);
            if ($response['success'] == 1) {
                DB::table('transactions')->insert([
                    'status' => 1, // for user
                    'reference_id' => $request->payment_type, // transaction reference id
                    'card_id' => NULL,
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'checkout_id' => NULL,
                    'booking_id' => $request->booking_id ? $request->booking_id : NULL,
                    'order_id' => $request->order_id ? $request->order_id : NULL,
                    'handyman_order_id' => $request->handyman_order_id ? $request->handyman_order_id : NULL,
                    'payment_transaction_id' => $transaction_id,
                    'payment_transaction' => json_encode($arr), // store token
                    'request_status' => 1,
                ]);
                return [
                    'status' => 'NEED_TO_OPEN_WEBVIEW',
                    'transaction_id' => $transaction_id,
                    'url' => $response['url'],
                    'return_url' => $request->payment_type == 'momo' ? $return_url : "",
                    'redirect_url' => $request->payment_type == 'cc' ? $redirect_url : "",
                ];
            } else {
                throw new \Exception($response['reply']);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function paymentStatus(Request $request)
    {
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

    // KPay Return
    public function KPayReturn(Request $request)
    {
        // redirect will be called in case of cc or card payment

        // Response was coming in this format
        // [2023-01-02 07:58:02] local.EMERGENCY: array (
        //     '{"tid":"A70911672646230","refid":"35290_1672646230","momtransactionid":"1672646281","statusdesc":"TARGET_AUTHORIZATION_ERROR","statusid":"02"}' => NULL,
        //   )  
        // $response = json_decode(array_keys($response)[0],true);
        // To convert it in array format
        //   [2023-01-02 08:09:02] local.EMERGENCY: array (
        //     'tid' => 'A70921672646907',
        //     'refid' => '35290_1672646906',
        //     'momtransactionid' => '1672646941',
        //     'statusdesc' => 'SUCCESSFUL',
        //     'statusid' => '01',
        //   ) 

        // redirect will be called in case of momo or mobile payment
        $response = $request->all();
        $response = json_decode(array_keys($response)[0], true);
        \Log::channel('kpay_api')->emergency($response);
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $response['refid'])->first();
        $payment_response = json_decode($transaction_table->payment_transaction, true);
        $payment_response['callback_response'] = $response;
        $request_status = 3;
        if (isset($response['statusid']) && $response['statusid'] == "01") {
            $request_status = 2;
        }
        // $response
        DB::table('transactions')->where('payment_transaction_id', $response['refid'])->update(['request_status' => $request_status, 'payment_transaction' => json_encode($payment_response)]);
        return true;
    }

    // KPay Redirect
    public function KPayRedirect(Request $request)
    {
        // redirect will be called in case of cc or card payment

        // Response was coming in this format
        // [2023-01-02 07:58:02] local.EMERGENCY: array (
        //     '{"tid":"A70911672646230","refid":"35290_1672646230","momtransactionid":"1672646281","statusdesc":"TARGET_AUTHORIZATION_ERROR","statusid":"02"}' => NULL,
        //   )  
        // $response = json_decode(array_keys($response)[0],true);
        // To convert it in array format
        //   [2023-01-02 08:09:02] local.EMERGENCY: array (
        //     'tid' => 'A70921672646907',
        //     'refid' => '35290_1672646906',
        //     'momtransactionid' => '1672646941',
        //     'statusdesc' => 'SUCCESSFUL',
        //     'statusid' => '01',
        //   )  

        $response = $request->all();
        $response = json_decode(array_keys($response)[0], true);
        \Log::channel('kpay_api')->emergency($response);
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $response['refid'])->first();
        $payment_response = json_decode($transaction_table->payment_transaction, true);
        $payment_response['callback_response'] = $response;
        $request_status = 3;
        if (isset($response['statusid']) && $response['statusid'] == "01") {
            $request_status = 2;
        }
        // $response
        DB::table('transactions')->where('payment_transaction_id', $response['refid'])->update(['request_status' => $request_status, 'payment_transaction' => json_encode($payment_response)]);
        return true;
    }
}
