<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 13/4/23
 * Time: 5:23 PM
 */

namespace App\Http\Controllers\PaymentMethods\OneVision;

use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OneVisionController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    protected $BASE_URL = "https://1vision.app";

    protected $TEST_BASE_URL = "https://1vision.app";

    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'card_no' => 'required',
            'cvv' => 'required',
            'exp_month' => 'required',
            'exp_year' => 'required',
            'card_holder' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug", "ONE_VISION")->first();
            if (empty($payment_option)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if (empty($payment_option_config)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $base_url = ($payment_option_config->gateway_condition == 1) ? $this->BASE_URL : $this->TEST_BASE_URL;
            $amount = 100.00;

            $transaction = Transaction::create(array(
                'status' => ($request->for == "USER") ? 1 : 2, // for user
                'user_id' => $request->for == "USER" ? $user->id : NULL,
                'driver_id' => $request->for == "DRIVER" ? $user->id : NULL,
                'merchant_id' => $user->merchant_id,
                'payment_option_id' => $payment_option->id,
                'checkout_id' => NULL,
                'booking_id' => NULL,
                'order_id' => NULL,
                'handyman_order_id' => NULL,
                'reference_id' => $user->id . $user->merchant_id . time(),
                'amount' => $amount, // amount
                'request_status' => 1,
                'status_message' => "PENDING",
            ));

            $data = [
                'api_key' => $payment_option_config->api_public_key,
                'expiration' => date('Y-m-d', strtotime(date("Y-m-d") . ' + 10 days')),
                'amount' => $amount,
                'currency' => 'KZT',
                'description' => $user->Merchant->BusinessName . ' Payment',
                'reference' => $transaction->reference_id,
                "success_url" => route("api.one-vision.callback", ["type" => "success", "transaction_id" => $transaction->id]),
                "failure_url" => route("api.one-vision.callback", ["type" => "failure", "transaction_id" => $transaction->id]),
                "lang" => "ru",
                "ip" => $_SERVER['REMOTE_ADDR'],
                "card_data" => array(
                    "pan" => $request->card_no,
                    "cvv" => $request->cvv,
                    "exp_month" => $request->exp_month,
                    "exp_year" => $request->exp_year,
                    "cardholder" => $request->card_holder
                ),
                "params" => array(
                    "pay_token_flag" => 1,
                    "user_id" => "",
                    "user_email" => $user->email,
                    "user_phone" => ($request->for == "USER") ? $user->UserPhone : $user->phoneNumber,
                )
            ];

            $data = base64_encode(json_encode($data));
            $sign = hash_hmac('md5', $data, $payment_option_config->api_secret_key); //signature generation

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url . '/pay/direct',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => "data=$data&sign=$sign",
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);
            if (isset($response['success']) && $response['success'] == 1) {
                $transaction->payment_transaction = json_encode($response);
                $transaction->save();
                return $this->successResponse(trans("$string_file.success"), array(
                    "transaction_id" => $transaction->id
                ));
            } else {
                $transaction->delete();
                throw new \Exception(isset($response['error_msg']) ? $response['error_msg'] : json_encode($response));
            }
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    // Debit from user/ driver
    public function makeTransfer(array $params)
    {
        try {
            $string_file = $params['string_file'];
            $payment_option = PaymentOption::where("slug", "ONE_VISION")->first();

            if (empty($payment_option)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $params['merchant_id'], "payment_option_id" => $payment_option->id))->first();
            if (empty($payment_option_config)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $transaction = Transaction::create(array(
                'status' => ($params['for'] == "USER") ? 1 : 2, // for user
                'user_id' => $params['for'] == "USER" ? $params['id'] : NULL,
                'driver_id' => $params['for'] == "DRIVER" ? $params['id'] : NULL,
                'merchant_id' => $params['merchant_id'],
                'payment_option_id' => $payment_option->id,
                'checkout_id' => NULL,
                'booking_id' => NULL,
                'order_id' => NULL,
                'handyman_order_id' => NULL,
                'reference_id' => $params['id'] . $params['merchant_id'] . time(),
                'amount' => $params['amount'], // amount
                'request_status' => 1,
                'status_message' => "PENDING",
            ));

            $base_url = ($payment_option_config->gateway_condition == 1) ? $this->BASE_URL : $this->TEST_BASE_URL;

            $data = [
                'api_key' => $payment_option_config->api_public_key,
                'expiration' => date('Y-m-d', strtotime(date("Y-m-d") . ' + 10 days')),
                'amount' => $params['amount'],
                'currency' => 'KZT',
                'description' => $params['business_name'] . ' Payment',
                'reference' => $params['id'] . $params['merchant_id'] . time(),
                "success_url" => route("api.one-vision.callback", ["type" => "success", "transaction_id" => $transaction->id]),
                "failure_url" => route("api.one-vision.callback", ["type" => "failure", "transaction_id" => $transaction->id]),
                "lang" => "ru",
                "ip" => $_SERVER['REMOTE_ADDR'],
                "pay_token" => $params['card_token'],
                "params" => array(
                    "pay_token_flag" => 1,
                    "user_id" => "",
                    "user_email" => $params['email'],
                    "user_phone" => $params['phone'],
                )
            ];

            $data = base64_encode(json_encode($data));
            $sign = hash_hmac('md5', $data, $payment_option_config->api_secret_key); //signature generation

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url . '/pay/direct',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => "data=$data&sign=$sign",
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);
            if (isset($response['data']) && isset($response['sign'])) {
                $sign = hash_hmac('md5', $response['data'], $payment_option_config->api_secret_key); //signature generation
                if ($sign == $response['sign']) {
                    if (isset($response['success']) && $response['success'] == 1) {
                        $transaction->payment_transaction_id = $response['transaction_id'];
                        $transaction->request_status = 2;
                        $transaction->status_message = "SUCCESS";
                        $transaction->save();
                        return true;
                    } else {
                        throw new \Exception($response['error_msg']);
                    }
                } else {
                    throw new \Exception("Singature Mismatched.");
                }
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (\Exception $exception) {
            return false;
            throw new \Exception("One Vision - " . $exception->getMessage());
        }
    }

    public function callback(Request $request, $type, $transaction_id)
    {
        try {
            $data = $request->all();
            /*for log*/
            $response = array(
                'type' => $type,
                'transaction_id' => $transaction_id,
                'request' => $request->all(),
            );
            \Log::channel('one_vision')->emergency($response);

            $secret_key = '9461bf25210faef9bd591e99b035531f1a8dfb6fc1d4bad3'; // shop password

            $data = $request->data;
            $resp_sign = $request->sign;
            $sign = hash_hmac('md5', $data, $secret_key); //signature generation
            if($sign != $resp_sign){
                return "OK";
            }

            $data = json_decode(base64_decode($data));
             if($type == "callback"){
                 $transaction = Transaction::find($transaction_id);
                 if($data['status_name'] == "success"){
                     if(!empty($transaction) && $transaction->request_status == 1){
                         if($transaction->status == 1){ // If User
                             UserCard::create([
                                 'user_id' => $transaction->user_id,
                                 'token' => $data['pay_token'],
                                 'payment_option_id' => $transaction->payment_option_id,
                                 'card_number' => $data['card_number'],
                                 'card_type' => "",
                                 'exp_month' => "",
                                 'exp_year' => ""
                             ]);
                             $paramArray = array(
                                 'user_id' => $transaction->user_id,
                                 'booking_id' => NULL,
                                 'amount' => $transaction->amount,
                                 'narration' => 2,
                                 'platform' => 2,
                                 'payment_method' => 2,
                                 'payment_option_id' => $transaction->payment_option_id,
                             );
                             WalletTransaction::UserWalletCredit($paramArray);
                         }else{
                             DriverCard::create([
                                 'driver_id' => $transaction->driver_id,
                                 'token' => $data['pay_token'],
                                 'payment_option_id' => $transaction->payment_option_id,
                                 'card_number' => $data['card_number'],
                                 'card_type' => "",
                                 'exp_month' => null,
                                 'exp_year' => null
                             ]);
                             $paramArray = array(
                                 'driver_id' => $transaction->driver_id,
                                 'booking_id' => NULL,
                                 'amount' => $transaction->amount,
                                 'narration' => 2,
                                 'platform' => 2,
                                 'payment_method' => 2,
                                 'receipt' => rand(1000,9999),
                             );
                             WalletTransaction::WalletCredit($paramArray);
                         }
                         $transaction->payment_transaction_id = $data['transaction_id'];
                         $transaction->request_status =  2; // SUCCESS
                         $transaction->status_message = "SUCCESS";
                         $transaction->save();
                     }else{
                         return "OK";
                     }
                 }else{
                     $transaction->request_status =  3; // FAILED
                     $transaction->status_message = "FAILED";
                     $transaction->save();
                     return "OK";
                 }
             }else{
                 return "OK";
             }
            return "OK";
        } catch (\Exception $exception) {
            $response = array(
                'request' => $data,
                'error' => $exception->getMessage(),
                'detail' => $exception->getTraceAsString(),
            );
            \Log::channel('one_vision')->emergency($response);
            return "OK";
        }
    }

    public function checkPayment(Request $request){
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'transaction_id' => 'required|exists:transactions,id'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $transaction = Transaction::find($request->transaction_id);
            if($transaction->request_status == 2){
                $result = "SUCCESS";
            }elseif($transaction->request_status == 1){
                $result = "PENDING";
            }else{
                $result = "FAILED";
            }
            return $this->successResponse(trans("$string_file.success"),array("result" => $result));
        }catch (\Exception $exception){
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function payoutCallback(Request $request, $type, $transaction_id = NULL)
    {
        try {
            $data = $request->all();
            /*for log*/
            $response = array(
                'payout' => "Yes",
                'type' => $type,
                'transaction_id' => $transaction_id,
                'request' => $request->all(),
            );
            \Log::channel('one_vision')->emergency($response);

            return "OK";
        } catch (\Exception $exception) {
            $response = array(
                'payout' => "Yes",
                'request' => $data,
                'error' => $exception->getMessage(),
                'detail' => $exception->getTraceAsString(),
            );
            \Log::channel('one_vision')->emergency($response);
            return "OK";
        }
    }

    // Credit Amount to user/driver
    public function makePayoutTransfer(array $params)
    {
        try {
            $string_file = $params['string_file'];
            $payment_option = PaymentOption::where("slug", "ONE_VISION_CASHOUT")->first();

            if (empty($payment_option)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $params['merchant_id'], "payment_option_id" => $payment_option->id))->first();
            if (empty($payment_option_config)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $base_url = ($payment_option_config->gateway_condition == 1) ? $this->BASE_URL : $this->TEST_BASE_URL;
            $reference_id = $params['id'] . $params['merchant_id'] . time();

            $data = [
                'api_key' => $payment_option_config->api_public_key,
                'expiration' => date('Y-m-d', strtotime(date("Y-m-d") . ' + 10 days')),
                'amount' => $params['amount'],
                'currency' => 'KZT',
                'description' => $params['business_name'] . ' Payment',
                'reference' => $reference_id,
                "success_url" => route("api.one-vision.callback", ["type" => "success", "transaction_id" => $reference_id]),
                "failure_url" => route("api.one-vision.callback", ["type" => "failure", "transaction_id" => $reference_id]),
                "lang" => "ru",
                "card_data" => array(
                    "pan" => $params['card_no'],
                    "cardholder" => $params['cardholder'],
                ),
                "params" => array(
                    "user_id" => "",
                    "user_email" => $params['email'],
                    "user_phone" => $params['phone'],
                )
            ];

            $data = base64_encode(json_encode($data));
            $sign = hash_hmac('md5', $data, $payment_option_config->api_secret_key); //signature generation

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url . '/pay/direct',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => "data=$data&sign=$sign",
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);
            if (isset($response['data']) && isset($response['sign'])) {
                $sign = hash_hmac('md5', $response['data'], $payment_option_config->api_secret_key); //signature generation
                if ($sign == $response['sign']) {
                    if (isset($response['success']) && $response['success'] == 1) {
                        $response['transaction_id'];
                    } else {
                        throw new \Exception($response['error_msg']);
                    }
                } else {
                    throw new \Exception("Singature Mismatched.");
                }
            } else {
                throw new \Exception(json_encode($response));
            }
        } catch (\Exception $exception) {
            throw new \Exception("One Vision - " . $exception->getMessage());
        }
    }
}
