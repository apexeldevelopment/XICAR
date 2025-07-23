<?php

namespace App\Http\Controllers\PaymentMethods\PeachPayment;

use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use DB;

class PeachServerToServer extends Controller
{
    //

    use ApiResponseTrait, MerchantTrait;
    public function initiatePaymentRequest(Request $request, $paymentConfig, $calling_from)
    {

        DB::beginTransaction();
        try {

            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
            } else {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
            }
            $string_file = $this->getStringFile($user->merchant_id);
            $entityID = $paymentConfig->api_public_key;
            $token = $paymentConfig->auth_token;
            $url  = ($paymentConfig->gateway_condition == 2) ? 'https://eu-test.oppwa.com/v1/payments' : "https://eu-prod.oppwa.com/v1/payments";


            $data = [
                "entityId" => $entityID,
                "amount" => $request->amount,
                "currency" => $request->currency,
                "paymentBrand" => $request->paymentBrand,
                "paymentType" => "DB",
                "card.number" => $request->card_number,
                "card.holder" => $request->card_holder,
                "card.expiryMonth" => $request->card_expiry_month,
                "card.expiryYear" => (int)$request->card_expiry_year,
                "card.cvv" => (int)$request->card_cvv,
                "shopperResultUrl" => route('peach-redirect'),
            ];



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
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response, true);
            \Log::channel('peach_pay')->emergency($res);

            if (isset($res)) {
                if(!isset($res['redirect']['url'])){
                    return [false, $res['result']['description']];
                }
                if ($res['result']['description'] == 'transaction pending' && !empty($res['redirect']['url'])) {
                    $calling_for = $request->calling_from == 'BOOKING' ? 3 : ($request->calling_from == "USER" ? 1 : 2);
                    $payment_transaction_id = $res['id'];
                    DB::table('transactions')->insert([
                        'user_id' => $request->calling_from == "USER" ? $id : NULL,
                        'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                        'status' => $calling_for,
                        'merchant_id' => $merchant_id,
                        'payment_transaction_id' => $payment_transaction_id,
                        'payment_transaction' => $response,
                        'amount' => $request->amount,
                        'payment_option_id' => $paymentConfig->payment_option_id,
                        'request_status' => 1,
                        "payment_mode" => "Card",
                        'status_message' => 'PENDING',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    DB::commit();
                }

                return [true,
                    [
                        'status' => 'NEED_TO_OPEN_WEBVIEW',
                        'url' => $res['redirect']['url'] ?? '',
                        'transaction_id' => $payment_transaction_id ?? '',
                        'success_url' => route('peach-redirect'),
                        'fail_url' => "",
                    ]
                ];
            } else {
                return [false, trans("$string_file.something_went_wrong")];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function paymentStatus(Request $request, $paymentConfig)
    {
        $transaction_id = $request->transaction_id; // confirmReference from payment gateway
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transaction_id)->first();
        $request_status_text = "failed";
        $payment_status = false;

        $entityID = $paymentConfig->api_public_key;
        $token = $paymentConfig->auth_token;
        $url = ($paymentConfig->gateway_condition == 2) ? "https://eu-test.oppwa.com/v1/payments/$transaction_table->payment_transaction_id?entityId=$entityID" :  "https://eu-prod.oppwa.com/v1/payments/$transaction_table->payment_transaction_id?entityId=$entityID";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response, true);

        if (isset($res['result']['code']) && isset($transaction_table)) {
            if ($res['result']['code'] == "000.100.110" || $res['result']['code'] == "000.000.000" || $res['result']['code'] == "000.000.100") {
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $res['id']])
                    ->update([
                        'request_status' => 2,
                        'payment_transaction' => $response,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status_message' => 'SUCCESS'
                    ]);
            } elseif ($res['result']['code'] == "000.200.000") {
                DB::table('transactions')
                    ->where(['payment_transaction_id' =>  $res['id']])
                    ->update([
                        'request_status' => 1,
                        'payment_transaction' => $response,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status_message' => 'PENDING'
                    ]);
            } elseif($res['result']['code'] == "800.120.100"){
                // too many requests
                return ["message" => "too many requests"];
            } else {
                DB::table('transactions')
                    ->where(['payment_transaction_id' =>  $res['id']])
                    ->update([
                        'request_status' => 3,
                        'payment_transaction' => $response,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status_message' => 'FAILED'
                    ]);
            }
        }

        $transaction_status = 1;
        if (isset($transaction_table)) {
            $payment_status =   $transaction_table->request_status == 2 ?  true : false;
            if ($transaction_table->request_status == 1) {
                $request_status_text = "processing";
            } else if ($transaction_table->request_status == 2) {
                $request_status_text = "success";
                $transaction_status = 2;
            } else if ($transaction_table->request_status == 3) {
                $request_status_text = "failed";
                $transaction_status = 3;
            }
        }

        return ['payment_status' => $payment_status, 'request_status' => $request_status_text,  'transaction_status' => $transaction_status];
    }
}
