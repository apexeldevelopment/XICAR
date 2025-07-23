<?php

namespace App\Http\Controllers\PaymentMethods\Khalti;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Log;

class khaltiController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function initiatePayment($request, $payment_option_config, $calling_from)
    {
        // DB::beginTransaction();
        try {
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


            $request_data = array(
                "return_url" => route('khalti-noti'),
                "website_url" => route('khalti-success'),
                "amount" => $request->amount * 100,
                "purchase_order_id" => "TEST" . time(),
                "purchase_order_name" => "TEST",

            );

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->getUrl($payment_option_config->gateway_condition),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: key ' . $payment_option_config->api_secret_key,
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response);
            Log::channel('khalti')->emergency(['$response' => $response, 'from' => "response"]);
            if (!empty($response->payment_url) && !empty($response->pidx)) {
                DB::table('transactions')->insert([
                    'user_id' => $request->calling_from == "USER" ? $id : NULL,
                    'driver_id' => $request->calling_from == "DRIVER" ? $id : NULL,
                    'status' => $calling_from,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id' => $response->pidx,
                    'amount' => $request->amount,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'request_status' => 1,
                    "payment_mode" => "Third-party App",
                    'status_message' => 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);


                return array(
                    'status' => 'NEED_TO_OPEN_WEBVIEW',
                    'url' => $response->payment_url ?? '',
                    'transaction_id' => $response->pidx ?? '',
                    'success_url' => route('khalti-success'),
                    'fail_url' => route('khalti-fail')
                );
            } else {
                throw new Exception(json_encode($response));
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUrl($env)
    {
        return $env == 1 ? "https://khalti.com/api/v2/epayment/initiate/" : "https://a.khalti.com/api/v2/epayment/initiate/";
    }

    public function returnUrl(Request $request)
    {
        Log::channel('khalti')->emergency(['request' => $request->all(), 'from' => "returnUrl"]);
        $transaction_id = $request->transaction_id;
        if ($request->status == "Completed") {
            $arr = array(
                "request_status" => 2,
                "status_message" => "SUCCESS"
            );

            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update($arr);
            return redirect()->route("khalti-success");
        } elseif ($request->status == "Pending") {
            $arr = array(
                "request_status" => 1,
                "status_message" => "PENDING"
            );
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update($arr);
            return redirect()->route("khalti-fail");

        } elseif (in_array($request->status, ['Expired', 'User canceled'])) {
            $arr = array(
                "request_status" => 3,
                "status_message" => "FAILED"
            );
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update($arr);
            return redirect()->route("khalti-fail");
        } else {
            $arr = array(
                "request_status" => 4,
                "status_message" => "OTHER"
            );
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update($arr);
            return redirect()->route("khalti-fail");
        }

    }

    public function successUrl($request)
    {
        Log::channel('khalti')->emergency(['request' => $request->all(), 'from' => "successUrl"]);
        echo "<h1>Success</h1>";
    }

    public function failUrl($request)
    {
        Log::channel('khalti')->emergency(['request' => $request->all(), 'from' => "failUrl"]);
        echo "<h1>Failed</h1>";
    }


    public function paymentStatus(Request $request)
    {
        $transactionId = $request->transaction_id;
        $transaction_table = DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
        $payment_status = $transaction_table->request_status == 2 ? true : false;
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
