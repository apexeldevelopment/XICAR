<?php

namespace App\Http\Controllers\PaymentMethods\RemitaPay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Models\PaymentOption;
use App\Traits\MerchantTrait;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RemitaPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function getRemitaPayConfig($merchant_id)
    {
        $payment_option = PaymentOption::where('slug', 'REMITA_PAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getBaseUrl($env)
    {
        return $env == 1 ? 'https://login.remita.net/remita/exapp/api/v1/send/api/' : 'https://demo.remita.net/remita/exapp/api/v1/send/api/';
    }

    public function MakePayment($request,$payment_option_config, $type)
    {
        // $validator = Validator::make($request->all(), [
        //     'type' => 'required',
        //     'amount' => 'required|numeric:min:200',
        //     'customerName' => 'required',
        //     'customerEmailaddress' => 'required|email',
        // ]);

        // if ($validator->fails()) {
        //     return $this->failedResponse($validator->errors());
        // }

        try {
            if ($type == "DRIVER") {
                $user = $request->user('api-driver');
                $id = $user->id;
                $customerName = $user->first_name;
                $customerEmailaddress = $user->email;
                $merchant_id = $user->merchant_id;
            } else {
                $user = $request->user('api');
                $id = $user->id;
                $customerName = $user->first_name;
                $customerEmailaddress = $user->email;
                $merchant_id = $user->merchant_id;
            }

            $paymentConfig = $this->getRemitaPayConfig($merchant_id);
            $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);

            $merchantId = $paymentConfig->operator;
            $apiKey = $paymentConfig->api_secret_key;
            $serviceTypeId = $paymentConfig->auth_token;
            $orderId = time();
            $totalAmount = $request->amount;

            // Generate API hash
            $apiHash = hash('sha512', $merchantId . $serviceTypeId . $orderId . $totalAmount . $apiKey);

            // Prepare payload
            $payload = [
                "serviceTypeId" => $serviceTypeId,
                "amount" => $totalAmount,
                "orderId" => $orderId,
                "payerName" => $customerName,
                "payerEmail" => $customerEmailaddress,
            ];
            // Make API call
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "remitaConsumerKey=$merchantId,remitaConsumerToken=$apiHash",
            ])->post($baseUrl . 'echannelsvc/merchant/api/paymentinit', $payload);

            // Handle response
            $responseBody = $response->body();
            if (Str::startsWith($responseBody, 'jsonp')) {
                $responseBody = substr($responseBody, 7, -1);
            }

            $response = json_decode($responseBody, true);

            if (isset($response['RRR'])) {
                $rrr = $response['RRR'];

                $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
                $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
                Transaction::create([
                    'user_id' => $type == "USER" ? $id : NULL,
                    'driver_id' => $type == "DRIVER" ? $id : NULL,
                    'status' => $calling_for,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id' => $rrr,
                    'payment_transaction' => json_encode($response),
                    'amount' => $totalAmount,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'request_status' => 1,
                    'status_message' => 'PENDING'
                ]);
                $payment_url = route('remita.payment-url', ['key' => encrypt($paymentConfig->api_public_key), 'rrr' => encrypt($rrr)]);
                return ['status' => 'NEED_TO_OPEN_WEBVIEW','transaction_id' => $rrr, 'url' => $payment_url, 'success_url' => route('remita.success', $rrr), 'fail_url' => route('remita.fail', $rrr)];
            } else {
                throw new \Exception($response['statusMessage']);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    public function PaymentUrl($key, $rrr)
    {
        try {
            return view('payment.RemitaPay.ramita_pay', [
                'key' => decrypt($key),
                'transactionId' => time(),
                'rrr' => decrypt($rrr),
            ]);
        } catch (Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }


    public function Success($rrr)
    {
        $this->PaymentStatus($rrr);
        return '<h1>Payment Success.</h1>';
    }
    public function Fail($rrr)
    {
        $this->PaymentStatus($rrr);
        return '<h1>Payment Faild.</h1>';
    }

    private function PaymentStatus($rrr)
    {
        try {
            $transaction =  Transaction::where('payment_transaction_id', $rrr)->first();
            $paymentConfig = $this->getRemitaPayConfig($transaction->merchant_id);
            $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);

            $merchantId = $paymentConfig->operator;
            $apiKey = $paymentConfig->api_secret_key;

            // Generate API hash
            $apiHash = hash('sha512', $rrr . $apiKey . $merchantId);

            // Make API call
            $url = $baseUrl . "echannelsvc/$merchantId/$rrr/$apiHash/status.reg";
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "remitaConsumerKey=$merchantId,remitaConsumerToken=$apiHash",
            ])->get($url);

            // Handle response
            $responseBody = $response->body();
            if (Str::startsWith($responseBody, 'jsonp')) {
                $responseBody = substr($responseBody, 7, -1);
            }

            $response = json_decode($responseBody, true);
            if (isset($response['status']) && $response['status'] == 00) {
                $request_status = 2;
                $status_message = 'SUCCESS';
                $data = ['payment_status' => true, 'request_status' => 'success', 'transaction_status' => 2];
            } elseif (isset($data['status']) && $response['status'] == 02) {
                $request_status = 3;
                $status_message = 'FAILED';
                $data = ['payment_status' => false, 'request_status' => 'failed', 'transaction_status' => 3];
            } else {
                $request_status = 1;
                $status_message = 'PENDING';
                $data = ['payment_status' => false, 'request_status' => 'pending', 'transaction_status' => 1];
            }

            $transaction->update([
                'payment_transaction' => json_encode($response),
                'request_status' => $request_status,
                'status_message' => $status_message,
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
