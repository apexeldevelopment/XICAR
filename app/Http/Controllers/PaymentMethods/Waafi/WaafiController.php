<?php

namespace App\Http\Controllers\PaymentMethods\Waafi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use App\Traits\ContentTrait;

class WaafiController extends Controller
{
    use ApiResponseTrait, MerchantTrait, ContentTrait;

    public function __construct()
    { }

    // waafi payment option
    public function waafiPayRequest($request, $payment_option_config, $calling_from)
    {

        // p('in');
        try {
            // check whether gateway is on sandbox or live
            $url = "https://sandbox.waafipay.net/asm";
            $public_key = "HPP-961814494"; //hppKey
            $secret_key = "M0912255"; //"merchantUid"
            $store_id = "1000238";
            if ($payment_option_config->gateway_condition == 1) {
                // $url = "https://secure.waafi.com/request.php";
                $url = "https://api.waafipay.net/asm";
                $public_key = $payment_option_config->api_public_key; //hpp Key
                $secret_key = $payment_option_config->api_secret_key; //"merchantUid"
                $store_id = $payment_option_config->auth_token; //api user id//store id
            }else{
                $url = "https://sandbox.waafipay.net/asm";
                $public_key = $payment_option_config->api_public_key; //hpp Key
                $secret_key = $payment_option_config->api_secret_key; //"merchantUid"
                $store_id = $payment_option_config->auth_token; //api user id//store id
            }


            // check whether request is from driver or user
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $code = $driver->Country->phonecode;
                $country = $driver->Country;
                $country_name = $country->CountryName;
                $currency = $driver->Country->isoCode;
                $phone_number = str_replace($code, "", $driver->phoneNumber);
                $logged_user = $driver;
                $user_merchant_id = $driver->driver_merchant_id;
                $first_name = $driver->first_name;
                $last_name = $driver->last_name;
                $email = $driver->email;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
            } else {
                $user = $request->user('api');
                $code = $user->Country->phonecode;
                $country = $user->Country;
                $country_name = $country->CountryName;
                $currency = $user->Country->isoCode;
                $phone_number = str_replace($code, "", $user->UserPhone);
                $logged_user = $user;
                $user_merchant_id = $user->user_merchant_id;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $email = $user->email;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
            }

            $transaction_id = $id . '_' . time();
            $success_url = route("waafi-success");
            $fail_url = route("waafi-fail");
            $fields = [
                "schemaVersion" => "1.0",
                "requestId" => "R" . $transaction_id,
                "timestamp" => '1507209344090',
                "channelName" => "WEB",
                "serviceName" => "HPP_PURCHASE",
                "serviceParams" => [
                    "storeId" => $store_id,//"1000238",
                    "hppKey" => $public_key,//"HPP-961814494",
                    "merchantUid" => $secret_key,//"M0912255",
                    "hppSuccessCallbackUrl" => $success_url,
                    "hppFailureCallbackUrl" => $fail_url,
                    "hppRespDataFormat" => "2",
                    "paymentMethod" => "MWALLET_ACCOUNT",
                    "payerInfo" => [
                        "subscriptionId" => 252615414470
                    ],
                    "transactionInfo" => [
                        "referenceId" => $transaction_id,
                        "invoiceId" => $id,
                        "amount" => (float) $request->amount,
                        "currency" => "USD", //$currency,
                        "description" => "Hpp purchase",
                        "paymentBrand" => "WAAFI / ZAAD / SAHAL / EVCPLUS / VISA / MASTERCARD",
                        "transactionCategory" => "ECOMMERCE / AIRLINE/ APPOINTMENTS"
                    ],
                    "billto" => [
                        "id" => $id,
                        "name" => $first_name . ' ' . $last_name,
                        "phone" => $phone_number
                    ],
                    "shipTo" => [
                        "id" => $id,
                        "name" => $first_name . ' ' . $last_name,
                        "phone" => $phone_number
                    ],
                    "items" => [
                        [
                            "id" => "454",
                            "merchantItemId" => "454",
                            "description" => $description,
                            "name" => "Order",
                            "type" => "PHYSICAL/ DIGITAL / MIXED",
                            "sku" => "EERF123",
                            "quantity" => 1,
                            "price" => $request->amount
                        ]
                    ]
                ]

            ];

            $fields_string = json_encode($fields);
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
                CURLOPT_POSTFIELDS => $fields_string,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            $response = json_decode($response, true);
            curl_close($curl);
            // p($response);
            \Log::channel('waafi_api')->emergency($response);
            $arr = [
                'request_response' => $response,
                'callback_response' => []
            ];
            // $url_forward = str_replace('"', '', stripslashes($response));

            if ($response['responseMsg'] == 'RCS_SUCCESS' && $response['responseCode'] == 2001) {
                DB::table('transactions')->insert([
                    'status' => 1, // for user
                    'reference_id' => "",
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
                    'url' => $response['params']['hppUrl'],
                    'success_url' => $success_url,
                    'fail_url' => $fail_url,
                    'other' => $response['params']

                ];
            } else {
                throw new \Exception($response['responseMsg']);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function successCallBack(Request $request)
    {
        $data = $request->all();
        \Log::channel('waafi_api')->emergency($data);
        $this->updateTransaction($data);
    }

    public function failCallBack(Request $request)
    {
        $data = $request->all();
        \Log::channel('waafi_api')->emergency($data);
        $this->updateTransaction($data);
    }

    public function updateTransaction($data)
    {
        if (isset($data['responseCode'])) {
            $request_status = $data['responseCode'] == 2001 ? 2 : 3;
            $transaction_id = $data['referenceId'];
            $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transaction_id)->first();
            $payment_response = json_decode($transaction_table->payment_transaction, true);
            $payment_response['callback_response'] = $data;
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update(['request_status' => $request_status,'reference_id' => $data['transactionId'], 'payment_transaction' => json_encode($payment_response)]);
        }
    }

    // check payment status
    public function paymentStatus(Request $request)
    {
        $tx_reference = $request->transaction_id; // order id
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $tx_reference)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        if ($transaction_table->request_status == 1) {
            $request_status_text = "processing";
        } else if ($transaction_table->request_status == 2) {
            $request_status_text = "success";
        } else {
            $request_status_text = "failed";
        }
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
    }
}
