<?php

namespace App\Http\Controllers\PaymentMethods\S3P_Pay;


use Exception;
use Illuminate\Http\Request;
use App\Models\PaymentOption;
use App\Traits\MerchantTrait;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;

class S3PPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function getS3PConfig($merchant_id)
    {
        $payment_option = PaymentOption::where('slug', 'S3P_PAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getBaseUrl($env)
    {
        return $env == 1 ? 'https://s3p.smobilpay.staging.maviance.info/v2/' : 'https://s3p.smobilpay.staging.maviance.info/v2/';
    }


    private function generateS3PAuthHeader($paymentConfig, $method, $url, $queryParams = [], $bodyParams = [])
    {
        $token = $paymentConfig->api_public_key;
        $secret = $paymentConfig->api_secret_key;

        // Use milliseconds for timestamp
        $timestamp = round(microtime(true) * 1000);
        $nonce = $timestamp; // Use timestamp as nonce
        $signatureMethod = "HMAC-SHA1";

        // Merge query and body parameters
        $input = array_merge($queryParams, $bodyParams);

        // Add S3P authentication parameters
        $s3pParams = [
            's3pAuth_nonce' => $nonce,
            's3pAuth_timestamp' => $timestamp,
            's3pAuth_signature_method' => $signatureMethod,
            's3pAuth_token' => $token
        ];

        $params = array_merge($input, $s3pParams);

        // Trim all parameter values (to match Postman behavior)
        foreach ($params as $key => $value) {
            $params[$key] = is_string($value) ? trim($value) : $value;
        }

        // Sort parameters alphabetically
        ksort($params);

        // Construct parameter string (without URL encoding)
        $parameterString = implode('&', array_map(function ($key, $value) {
            return $key . '=' . $value;
        }, array_keys($params), $params));


        // Construct base string
        $baseString = strtoupper($method) . "&" . rawurlencode($url) . "&" . rawurlencode($parameterString);

        // Generate HMAC-SHA1 signature
        $signature = base64_encode(hash_hmac('sha1', $baseString, $secret, true));



        // Construct authorization header
        $authHeader = sprintf(
            's3pAuth, s3pAuth_nonce="%s", s3pAuth_signature="%s", s3pAuth_signature_method="%s", s3pAuth_timestamp="%s", s3pAuth_token="%s"',
            $nonce,
            $signature,
            $signatureMethod,
            $timestamp,
            $token
        );



        return $authHeader;
    }

    public function CashoutServices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $type = $request->type;
            if ($type == "DRIVER") {
                $user = $request->user('api-driver');
                $merchant_id = $user->merchant_id;
            } else {
                $user = $request->user('api');
                $merchant_id = $user->merchant_id;
            }
            $paymentConfig = $this->getS3PConfig($merchant_id);

            $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);
            $url = $baseUrl . 'service';
            // Cashout API

            $authHeader = $this->generateS3PAuthHeader($paymentConfig, 'GET', $url);

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'x-api-version' => '3.0.0',
            ])->get(rtrim($this->getBaseUrl($paymentConfig->gateway_condition), '/') . '/service');

            $services = json_decode($response->getBody(), true);

            $cashoutServices = array_filter($services, function ($service) {
                return isset($service['type']) && strtoupper($service['type']) === 'CASHOUT';
            });

            $filteredServices = array_map(function ($service) {
                return [
                    'serviceid' => $service['serviceid'] ?? null,
                    'merchant' => $service['merchant'] ?? null,
                    'title' => $service['title'] ?? null,
                    'description' => $service['description'] ?? null,
                    'country' => $service['country'] ?? null,
                    'localCur' => $service['localCur'] ?? null
                ];
            }, $cashoutServices);

            return $this->successResponse('Success', array_values($filteredServices));
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function MakePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'serviceid' => 'required',
            'amount' => 'required|numeric',
            'payment_option_id' => 'required',
            'payment_method_id' => 'required',
            'serviceNumber' => 'required',
            'customerPhonenumber' => 'required',
            'customerEmailaddress' => 'required|email',
            'customerName' => 'required',
            'customerAddress' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try {
            $type = $request->type;
            if ($type == "DRIVER") {
                $user = $request->user('api-driver');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
            } else {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
            }

            $paymentConfig = $this->getS3PConfig($merchant_id);
            $service_id = $request->serviceid;
            $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);
            $url = $baseUrl . 'cashout';
            // Cashout API

            $authHeader = $this->generateS3PAuthHeader($paymentConfig, 'GET', $url, ['serviceid' => $service_id]);
            $response = Http::withHeaders([
                'Authorization' => $authHeader
            ])->get($baseUrl . 'cashout', [
                'serviceid' => $service_id
            ]);


            $response = json_decode($response->getBody(), true);

            if ($response[0]['merchant'] == 'MTNMOMO' || $response[0]['merchant'] == 'CMORANGEMOMO') {
                if (!preg_match('/^(237)?((650|651|652|653|654|680|681|682|683|684)[0-9]{6}$|(67[0-9]{7})$)/', $request->serviceNumber)) {
                    return $this->failedResponse('Invalid service number for ' . $response[0]['merchant']);
                }
            } else {
                if (!preg_match('/^(237)?((655|656|657|658|659|686|687|688|689|640)[0-9]{6}$|(69[0-9]{7})$)/', $request->serviceNumber)) {
                    return $this->failedResponse('Invalid service number for ' . $response[0]['merchant']);
                }
            }

            if (isset($response['respCode']) && $response['respCode'] == 40602) {
                return $this->failedResponse('Service not found');
            } else if (isset($response['respCode']) && $response['respCode'] == 4006) {
                return $this->failedResponse('Invalid Signature');
            }

            $tscId = time();
            $string_file = $this->getStringFile($merchant_id);
            $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
            $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
            Transaction::create([
                'user_id' => $type == "USER" ? $id : NULL,
                'driver_id' => $type == "DRIVER" ? $id : NULL,
                'status' => $calling_for,
                'merchant_id' => $merchant_id,
                'payment_transaction_id' => $tscId,
                'payment_transaction' => json_encode($response),
                'amount' => $request->amount,
                'payment_option_id' => $paymentConfig->payment_option_id,
                'request_status' => 1,
                'status_message' => 'PENDING',

            ]);
            return $this->PaymentQuote($paymentConfig, $response, $baseUrl, $request, $tscId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function PaymentQuote($paymentConfig, $response, $baseUrl, $request, $tscId)
    {
        // Post quote API
        $url = $baseUrl . 'quotestd';
        $body = [
            "payItemId" => $response[0]['payItemId'],
            "amount" => $request->amount
        ];

        $authHeader = $this->generateS3PAuthHeader($paymentConfig, 'POST', $url, [], $body);

        $response = Http::withHeaders([
            'Authorization' => $authHeader
        ])->post($url, $body);

        $response = json_decode($response->getBody(), true);

        if (isset($response['respCode']) && $response['respCode'] == 4207) {
            return $this->failedResponse('Invalid payment item id. Improper format');
        }
        return $this->PaymentCollection($paymentConfig, $response, $baseUrl, $request, $tscId);
    }

    private function PaymentCollection($paymentConfig, $response, $baseUrl, $request, $tscId)
    {
        // Post collect API
        $url = $baseUrl . 'collectstd';
        $body = [
            "quoteId" => $response['quoteId'],
            "customerPhonenumber" => $request->customerPhonenumber,
            "customerEmailaddress" => $request->customerEmailaddress,
            "customerName" => $request->customerName,
            "customerAddress" => $request->customerAddress,
            "serviceNumber" => $request->serviceNumber,
            "trid" => strval($tscId)
        ];

        $authHeader = $this->generateS3PAuthHeader($paymentConfig, 'POST', $url, [], $body);

        $response = Http::withHeaders([
            'Authorization' => $authHeader
        ])->post($url, $body);

        $response = json_decode($response->getBody(), true);

        if (isset($response['respCode']) && $response['respCode'] == 4209) {
            return $this->failedResponse('Quote is invalid or expired');
        } else if (isset($response['respCode']) && $response['respCode'] == 40409) {
            return $this->failedResponse($response['customerMsg'][0]['content']);
        }
        Transaction::where('payment_transaction_id', $tscId)->update([
            'payment_transaction' => json_encode($response),
        ]);
        return $this->successResponse('Payment initiate.', ['transaction_id' => (string)$tscId]);
    }


    public function PaymentVerify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'trid' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $type = $request->type;
        if ($type == "DRIVER") {
            $user = $request->user('api-driver');
            $id = $user->id;
            $merchant_id = $user->merchant_id;
        } else {
            $user = $request->user('api');
            $id = $user->id;
            $merchant_id = $user->merchant_id;
        }
        $trid = $request->trid;
        $paymentConfig = $this->getS3PConfig($merchant_id);

        $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);
        $url = $baseUrl . 'verifytx';
        // Cashout API

        $authHeader = $this->generateS3PAuthHeader($paymentConfig, 'GET', $url, ['trid' => $trid]);
        $response = Http::withHeaders([
            'Authorization' => $authHeader
        ])->get($baseUrl . 'verifytx', [
            'trid' => $trid,
        ]);
        

        $response = json_decode($response->getBody(), true);
        $data = [];
        if (!empty($response)) {
            if ($response[0]['status'] == 'SUCCESS') {
                $request_status_text = "success";
                $transaction_status = 2;
                $data = ['payment_status' => true, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            } else if ($response[0]['status'] == 'ERRORED') {
                $request_status_text = "failed";
                if ($response[0]['errorCode'] == 40302) {
                    $request_status_text = 'One or more input parameters are invalid or contain invalid values';
                } else if ($response[0]['errorCode'] == 40015) {
                    $request_status_text = 'User provide in credentials does not exist';
                } else if ($response[0]['errorCode'] == 40010) {
                    $request_status_text = 'Missing mandatory fields in request';
                } else if ($response[0]['errorCode'] == 703202) {
+                    $request_status_text = 'Customer has rejected the payment / denied the approval of the transaction. The transaction cannot complete without the customer confirmation.';
                }
                $transaction_status = 3;
                $data = ['payment_status' => false, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            } else {
                $request_status_text = "pending";
                $transaction_status = 1;
                $data = ['payment_status' => false, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }
            
            if ($response[0]['status'] != 'PENDING') {
                Transaction::where('payment_transaction_id', $trid)->update([
                    'payment_transaction' => json_encode($response),
                    'status_message' => $response[0]['status'],
                    'request_status' => $transaction_status,
                ]);
            }
        }else{
             $transaction_status = 3;
                $data = ['payment_status' => false, 'request_status' => 'Failed', 'transaction_status' => $transaction_status];
                 Transaction::where('payment_transaction_id', $trid)->update([
                    'payment_transaction' => [],
                    'status_message' => 'Failed',
                    'request_status' => $transaction_status,
                ]);
        }
        return $this->successResponse('Payment status.', $data);
    }
}
