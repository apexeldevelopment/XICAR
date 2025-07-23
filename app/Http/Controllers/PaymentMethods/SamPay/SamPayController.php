<?php

namespace App\Http\Controllers\PaymentMethods\SamPay;

use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use DB;
use Illuminate\Http\Request;

use function GuzzleHttp\json_encode;

class SamPayController extends Controller
{
    use MerchantTrait;

    public function __construct()
    {
        $this->base_url = 'https://samafricaonline.com/sam_pay/public/';
    }
    // aamarPay payment option
    public function checkoutRequest($request, $payment_option_config, $calling_from)
    {

        try {

            $api_public_key = "";
            $auth_token = "";
            $is_live = false;
            $base_url = $this->base_url;

            if ($payment_option_config->gateway_condition == 1) {
                $api_public_key = $payment_option_config->api_public_key;
                $auth_token = $payment_option_config->auth_token;
                $is_live = true;
            }
            // check whether request is from driver or user
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $currency = $driver->Country->isoCode;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
            } else {
                $user = $request->user('api');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
            }

            $transaction_id = $id . '_' . time();
            $fields = array(
                "AppKey" => $api_public_key,
                "AuthKey" => $auth_token,
                "OrderID" => $transaction_id,
                "OrderName" => $description,
                "OrderDetails" => $description,
                "Currency" => $currency,
                "OrderTotal" => $request->amount,
            );

            $curl = curl_init();

            $fields = json_encode($fields);
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url . 'merchantcheckout',
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
                ),
            ));

            $response = curl_exec($curl);
            $response = json_decode($response);
            curl_close($curl);
            if ($response->status == 200) {
                DB::table('transactions')->insert([
                    'status' => 1, // for user
                    'reference_id' => $response->message, // transaction reference id
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
                    'payment_transaction' => null, // store token
                    'request_status' => 1,
                ]);

                return [
                    'status' => 'NEED_TO_OPEN_WEBVIEW',
                    'url' => $base_url . 'merchantpayment?token=' . $response->message
                ];
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function samPayResponse(Request $request)
    {
        $data = $request->all();
        \Log::channel('sampay_api')->emergency($data);
        if (isset($data['response']) && $data['response']) {
            // payment accepted successfully
            // check payment confirmation

            $transaction =  DB::table('transactions')->where('reference_id', $data['token'])->first();

            $payment_option_config = DB::table('payment_options_configurations')->where('merchant_id', $transaction->merchant_id)->where('payment_option_id', $transaction->payment_option_id)->first();

            // live details
            $api_public_key = "";
            $auth_token = "";

            // $is_live = true;
            if ($payment_option_config->gateway_condition == 1) {
                $api_public_key = $payment_option_config->api_public_key;
                $auth_token = $payment_option_config->auth_token;
            }

            $transaction_id = $transaction->payment_transaction_id;
            $curl = curl_init();
            $fields = array(
                "AppKey" => $api_public_key,
                "AuthKey" => $auth_token,
                "OrderID" => $transaction_id,
                "token" => $data['token'],
                "vendorID" => time(),
            );
            $fields = json_encode($fields);

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->base_url . 'paymentconfirmation',
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
            curl_close($curl);
            $response = json_decode($response, true);
            $request_status = 3;
            if (isset($response['status'])) {
                $request_status = 2;
            }
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update(['request_status' => $request_status, 'payment_transaction' => $response]);
        }
    }

    public function paymentStatus(Request $request)
    {
        $tx_reference = $request->transaction_id; // order id
        $transaction_table =  DB::table("transactions")->where('reference_id', $tx_reference)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        return ['payment_status' => $payment_status];
    }
}
