<?php

namespace App\Http\Controllers\PaymentMethods\Bog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Transaction;


class BogPaymentController extends Controller
{
    //

    public function getLanguage($currency)
    {
        return ($currency == "GEN") ? "ka" : "en";
    }

    public function getAuthTokenForBog($client_id, $client_secret)
    {
        try {
            $creds = "Basic " . base64_encode($client_id . ":" . $client_secret);
            $data = [
                'grant_type' => 'client_credentials'
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $creds,
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response, true);
            if (isset($res['access_token'])) {
                return $res['access_token'];
            } else {
                return '';
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function makePaymentUsingBog($request, $paymentConfig, $calling_from)
    {
        DB::beginTransaction();
        try {
            $status = 3;
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $status = 2;
            } elseif($calling_from == "USER") {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 1;
            }

            $clientSecret = $paymentConfig->api_secret_key;
            $clientId = $paymentConfig->api_public_key;

            $token = $this->getAuthTokenForBog($clientId, $clientSecret);
            if (empty($token)) {
                throw new \Exception('Token not generated');
            }

            $trans_id = "trans_" . $id . '_' . time();
            $product_id = "prod_" . time();
            $url = "https://api.bog.ge/payments/v1/ecommerce/orders";

            $data = [
                "callback_url" => route("bog-callback"),
                "external_order_id" => $trans_id,
                "purchase_units" => [
                    "currency" => $request->currency,
                    "total_amount" => $request->amount,
                    "basket" => [
                        [
                            "quantity" => 1,
                            "unit_price" => $request->amount,
                            "product_id" => $product_id,
                        ]
                    ]
                ],
                "redirect_urls" => [
                    "fail" => route("bog-fail"),
                    "success" => route("bog-success")
                ]
            ];

            //already saved card order id found
            $saved_card = Transaction::where("request_status", 2)
                ->where("card_is_active", 1)
                ->whereNotNull("card_order_id")
                ->where(function ($query) use ($id) {
                    $query->where('user_id', $id)
                        ->orWhere('driver_id', $id);
                })
                ->orderby("id", "desc")
                ->first();

            //if card order is exists then pay using saved card order id 
            if (isset($saved_card)) {
                $url = 'https://api.bog.ge/payments/v1/ecommerce/orders/' . $saved_card->card_order_id;
            }

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
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                    'Accept-Language: ' . $this->getLanguage($request->currency),
                ),
            ));

            $response = json_decode(curl_exec($curl), true);
            curl_close($curl);

            if (isset($response['_links']['redirect']['href']) && isset($response['id'])) {
                $order_id = $response['id'];
                
                //if card order id is not exist in the database then we have to save it on bank's server 
                if(!isset($saved_card)){
                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://api.bog.ge/payments/v1/orders/$order_id/cards",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'PUT',
                        CURLOPT_POSTFIELDS => json_encode($data),
                        CURLOPT_HTTPHEADER => array(
                            'Authorization: Bearer ' . $token,
                        ),
                    ));
    
                    $resp = curl_exec($curl);
                    curl_close($curl);
                }

                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'card_order_id' => (isset($saved_card)) ? $saved_card->card_order_id : $order_id,
                    'status' => $status,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id' => $trans_id,
                    'amount' => $request->amount,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'request_status' => 1,
                    'status_message' => 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();

                return [
                    'status' => 'NEED_TO_OPEN_WEBVIEW',
                    'url' => $response['_links']['redirect']['href'] ?? '',
                    'transaction_id' => $trans_id ?? '',
                    'success_url' => route('bog-success'),
                    'fail_url' => route('bog-fail'),
                ];
            } else {
                throw new \Exception($response['message']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }


    public function bogPaysuccess(Request $request)
    {
        $data = $request->all();
        \Log::channel('bog_pay')->emergency($data);
        echo "<h1>Success</h1>";
    }

    public function bogPayFail(Request $request)
    {
        $data = $request->all();
        \Log::channel('bog_pay')->emergency($data);
        echo "<h1>Failed</h1>";
    }

    public function bogPayCallback(Request $request)
    {
        \Log::channel('bog_pay')->emergency($request->all());
        $data = $request->all();

        if (!empty($data)) {
            $trans = DB::table('transactions')->where(['request_status' => 1, 'payment_transaction_id' => $data['body']['external_order_id']])->first();
            if (!empty($trans)) {
                $payment_status = $data['body']['order_status']['key'] == "completed" ? 2 : 3;
                if ($payment_status == 2) {
                    DB::table('transactions')
                        ->where(['payment_transaction_id' => $data['body']['external_order_id']])
                        ->update([
                            'request_status' => 2,
                            'card_is_active' => 1,
                            'payment_transaction' => json_encode($request->all()),
                            'reference_id' => $data['body']['order_id'],
                            'updated_at' => date('Y-m-d H:i:s'),
                            'status_message' => 'SUCCESS'
                        ]);
                } else {
                    DB::table('transactions')
                        ->where(['payment_transaction_id' => $data['body']['external_order_id ']])
                        ->update([
                            'request_status' => 3,
                            'card_is_active' => 2,
                            'payment_transaction' => json_encode($request->all()),
                            'reference_id' => $data['body']['order_id'],
                            'updated_at' => date('Y-m-d H:i:s'),
                            'status_message' => 'FAIL'
                        ]);
                }
            }
        }
        return "success";
    }
}
