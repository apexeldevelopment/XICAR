<?php

namespace App\Http\Controllers\PaymentMethods;

require "mpesa-lib/src/autoload.php";
require "pesapal-lib/OAuth.php";
require "2c2p/PaymentTokenGenerate.php";
require "BeyonicPayment/beyonic/lib/Beyonic.php";
require 'iugu/lib/Iugu.php';
// require_once 'pagadito/lib/Pagadito.php';
require 'Moneris/mpgClasses.php';
//require_once 'Monetbil.php';

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CarpoolingController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentMethods\MonetbilController;
use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;
use App\Models\Country;
use App\Models\DriverCard;
use App\Models\DriverWalletTransaction;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\TripayTransaction;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserDevice;
use App\Models\UserWalletTransaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Braintree_ClientToken;
use Braintree_Configuration;
use Braintree_Transaction;
use DateTimeZone;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use App\Models\Driver;
use Illuminate\Validation\Rule;
use Iugu;
use Iugu_Charge;
use Iugu_PaymentToken;
use Kabangi\Mpesa\Init as Mpesa;
use DateTime;
use Log;
use mpgHttpsPost;
use mpgRequest;
use mpgTransaction;
// use Pagadito;
use PaymentTokenGenerate as TwoCTwoPPaymentGateway;
use SimpleXMLElement;
use Illuminate\Support\Str;
use App\Models\UserCashout;
use DB;
use App\Models\Transaction;
use App\Models\BusinessSegment\Order;


class RandomPaymentController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function ChargePaystack($amount = 0, $currency = "NGN", $CustomerID = null, $email = null, $paystack = null, $payment_redirect_url = null, $calling_from = '', $booking_id = null,$request_from = "", $booking_transaction_fee = null, $driver_paystack_account_id = null)
    {
        $payment_option = PaymentOption::where('slug', 'PAYSTACK')->first();
        switch ($calling_from) {
            case 'USER' :
                $status = 1;
                $user_id = request()->user('api')->id;
                $driver_id = NULL;
                $bs = NULL;
                break;
            case 'DRIVER' :
                $status = 2;
                $user_id = NULL;
                $driver_id = request()->user('api-driver')->id;
                $bs = NULL;
                break;
            case 'BOOKING' :
                $status = 3;
                if(!empty($booking_id)){
                    $booking = Booking::find($booking_id);
                    if(empty($booking)){
                        $booking = Order::find($booking_id); //for checking orders
                        if(!empty($booking)){
                            $user_id = $booking->user_id;
                            $driver_id = $booking->driver_id;
                            $bs = NULL;
                        }
                    }else{
                        $user_id = $booking->user_id;
                        $driver_id = $booking->driver_id;
                        $bs = NULL;
                    }
                    
                }else{
                    $user_id = request()->user('api')->id;
                    $driver_id = request()->user('api-driver')->id;
                    $bs = NULL;
                }
                break;
            case "BUSINESS_SEGMENT":
                $status =4;
                $user_id = NULL;
                $driver_id = NULL;
                $bs = request()->user('business-segment-api')->id;
                break;
            default :
                $status = 0;
                $user_id = request()->user('api')->id;
                $driver_id = request()->user('api-driver')->id;
                $bs = NULL;
                break;
        }
        $transaction_id = mt_rand(1000, 10000) . time();
        DB::table('transactions')->insert([
            'status' => $status,
            'card_id' => request()->card_id ?? ($booking->card_id ?? NULL),
            'user_id' => $user_id,
            'driver_id' => $driver_id,
            'business_segment_id' => $bs,
            'merchant_id' => request()->merchant_id,
            'payment_option_id' => $payment_option->id,
            'amount' => $currency . ' ' . $amount,
            'booking_id' => $booking_id,
            'payment_transaction_id' => $transaction_id,
            'payment_mode' => 'Card',
            'request_status' => 1,
        ]);

        $request_data = array('authorization_code' => $CustomerID, 'email' => $email, 'currency' => $currency, 'amount' => $amount * 100);
        if(!empty($booking_transaction_fee) && !empty($driver_paystack_account_id)){
            $request_data["subaccount"] = $driver_paystack_account_id;
            $request_data["transaction_charge"] = $booking_transaction_fee;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payment_redirect_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));  //Post Fields
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [
            'Authorization: Bearer ' . $paystack,
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $request = curl_exec($ch);
        curl_close($ch);
        $result1 = json_decode($request, true);

        DB::table('transactions')->where([
            ['merchant_id', '=', request()->merchant_id],
            ['payment_transaction_id', '=', $transaction_id],
            ['payment_option_id', '=', $payment_option->id]
        ])->update([
            'status_message' => $result1['message']
        ]);
         if (isset($result1['data']['status']) &&  $result1['data']['status'] == "failed") {
              DB::table('transactions')->where([
                ['merchant_id', '=', request()->merchant_id],
                ['payment_transaction_id', '=', $transaction_id],
                ['payment_option_id', '=', $payment_option->id],

            ])->update([
                'reference_id' => $result1['data']['reference'],
                'status_message' => $result1['data']['gateway_response']
            ]);
              if($request_from == "USER_MAKE_PAYMENT")
              {
                throw new \Exception($result1['data']['gateway_response']);
              }
              else
              {
                //   return false;
                return [$result1['message'], false];
              }


         }
        elseif ($result1['status'] == true  && $result1['data']['status'] == "success") {
            DB::table('transactions')->where([
                ['merchant_id', '=', request()->merchant_id],
                ['payment_transaction_id', '=', $transaction_id],
                ['payment_option_id', '=', $payment_option->id]
            ])->update([
                'reference_id' => $result1['data']['reference'],
                'status_message' => $result1['data']['gateway_response']
            ]);
            $reference = $result1['data']['reference'];
            $url = 'https://api.paystack.co/transaction/verify/' . $reference;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt(
                $ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $paystack]
            );
            $request = curl_exec($ch);
            curl_close($ch);

            $result12 = json_decode($request, true);
            if ($result12['data']['status'] === 'success') {
                DB::table('transactions')->where([
                    ['merchant_id', '=', request()->merchant_id],
                    ['payment_transaction_id', '=', $transaction_id],
                    ['payment_option_id', '=', $payment_option->id]
                ])->update([
                    'request_status' => 2,
                    'status_message' => $result12['data']['gateway_response']
                ]);
                return array('id' => $result12['data']['status']);
            } elseif ($result12['data']['status'] === 'failed') {
                DB::table('transactions')->where([
                    ['merchant_id', '=', request()->merchant_id],
                    ['payment_transaction_id', '=', $transaction_id],
                    ['payment_option_id', '=', $payment_option->id]
                ])->update([
                    'request_status' => 3,
                    'status_message' => $result12['data']['gateway_response']
                ]);
                if($request_from == "USER_MAKE_PAYMENT")
                {
                    throw new \Exception($result1['data']['gateway_response']);
                }
                else
                {
                    // return false;
                    return [$result12['message'], false];
                }
//                return false;
            } else {
                DB::table('transactions')->where([
                    ['merchant_id', '=', request()->merchant_id],
                    ['payment_transaction_id', '=', $transaction_id],
                    ['payment_option_id', '=', $payment_option->id]
                ])->update([
                    'request_status' => 4,
                    'status_message' => $result12['data']['gateway_response']
                ]);
                if($request_from == "USER_MAKE_PAYMENT")
                {
                    throw new \Exception($result1['data']['gateway_response']);
                }
                else
                {
                    // return false;
                    return [$result1['message'], false];
                }
//                return false;
            }
        } else {
            if($request_from == "USER_MAKE_PAYMENT")
            {
                throw new \Exception(isset($result1['message']) ? $result1['message']:'something went wrong');
            }
            else
            {
                // return false;
                return [$result1['message'], false];
            }
//            return false;
        }
    }

    // this function is only made for verifing add card transaction
    // because the charged amount would be refunded back instantly.
    public function VerifyTransactionPaystack($transRef = null, $paystack = null)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $transRef,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $paystack,
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($response) {
            $result = json_decode($response, true);
            if ($result['status'] == true) {
                // refund the add card charge immediately
                $fields_string = http_build_query(array('transaction' => $transRef, 'amount' => $result['data']['amount']));
                //open connection
                $ch = curl_init();
                curl_setopt_array($ch, array(
                    CURLOPT_URL => 'https://api.paystack.co/refund',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $fields_string,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer " . $paystack,
                    ),
                ));
                //So that curl_exec returns the contents of the cURL; rather than echoing it
                curl_exec($ch);
                return array('id' => $result['data']['authorization']);
            } else {
                return false;
            }
        }
    }

    public function tokenGenerateCielo($cardNumber = null, $expMonth = null, $expYear = null, $cardType = null, $cvv = null, $email = null, $userName = null, $merchantKey = null, $merchantId = null, $tokenizationUrl = null)
    {
        $tokenizationUrl = 'https://api.cieloecommerce.cielo.com.br/1/card/';
        $rand = rand(111111, 999999);
        $post_param = ["CustomerName" => $rand, "CardNumber" => $cardNumber, "Holder" => $email, "ExpirationDate" => $expMonth . '/' . $expYear, "Brand" => $cardType];
        $post_param = json_encode($post_param, true);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $tokenizationUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            //CURLOPT_POSTFIELDS => "{  \r\n   \"MerchantOrderId\":\"$rand\",\r\n   \"Customer\":{  \r\n      \"Name\":\"$userName\",\r\n      \"email\":\"$email\"\r\n   },\r\n   \"Payment\":{  \r\n     \"Type\":\"CreditCard\",\r\n     \"Amount\":1,\r\n     \"Installments\":1,\r\n     \"Authenticate\":false,\r\n     \"CreditCard\":{  \r\n         \"CardNumber\":\"$cardNumber\",\r\n         \"Holder\":\"$userName\",\r\n         \"ExpirationDate\":\"$expMonth/$expYear\",\r\n         \"SecurityCode\":\"$cvv\",\r\n         \"SaveCard\":\"true\",\r\n         \"Brand\":\"$cardType\"\r\n     }\r\n   }\r\n}",
            CURLOPT_POSTFIELDS => $post_param,
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json",
                "merchantid: " . $merchantId,
                "merchantkey: " . $merchantKey,
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $res = json_decode($response, true);
        // if (isset($res['Payment'])) {
        //     if ($res['Payment']['Status'] == 1 || $res['Payment']['Status'] == 2) {
        //         return array('id' => 1, 'data' => $res);
        //     } else {
        //         return array('0' => 'Payment Failed');
        //     }
        // } else {
        //     $message = array_key_exists(0, $res) ? $res[0]['Message'] : $res[1]['Message'];
        //     return array($message);
        // }
        if (isset($res['CardToken'])) {
            return $res;
        } else {
            $message = array_key_exists(0, $res) ? $res[0]['Message'] : $res[1]['Message'];
            return array($message);
        }
    }

    public function ChargeCielo($amount = 0, $userName, $cardType, $token = null, $merchantKey, $merchantId, $payment_redirect_url = null)
    {
        $amount = ($amount * 100);
        $rand = uniqid();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $payment_redirect_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{  \r\n   \"MerchantOrderId\":\"$rand\",\r\n   \"Customer\":{  \r\n      \"Name\":\"$userName\"\r\n   },\r\n   \"Payment\":{  \r\n     \"Type\":\"CreditCard\",\r\n     \"Amount\":$amount,\r\n     \"Installments\":1,\r\n  \r\n     \"CreditCard\":{  \r\n         \"CardToken\":\"$token\",\r\n         \"Brand\":\"$cardType\"\r\n     }\r\n   }\r\n}",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json",
                "merchantid: " . $merchantId,
                "merchantkey: " . $merchantKey,
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $res = json_decode($response, true);
        if (!empty($res['Payment'])) {
            if ($res['Payment']['Status'] == 1) {
                return array('id' => 1, 'data' => $res);
            } else {
                return $res['Payment']['ReturnMessage'];
            }
        } else {
            return array('result' => 0, 'message' => $res[0]['Message']);
        }
    }

    public function brainTreeClientToken($privateKey, $publicKey, $merchant_id, $env)
    {
        if ($env == 1) {
            $envir = "live";
        } else {
            $envir = "sandbox";
        }
        Braintree_Configuration::environment($envir);
        Braintree_Configuration::merchantId($merchant_id);
        Braintree_Configuration::publicKey($publicKey);
        Braintree_Configuration::privateKey($privateKey);
        $see = Braintree_ClientToken::generate();
        return array('clientToken' => $see);
    }

    public function brainTreeCreateTrans($amount, $nonce)
    {
        $result = Braintree_Transaction::sale([
            'amount' => $amount,
            'paymentMethodNonce' => $nonce,
            'options' => [
                'submitForSettlement' => true
            ]
        ]);
        if ($result->success) {
            return array('transaction' => $result);
        } else {
            return false;
        }
    }

    public function IugoToken($amount = null, $userDetails = null, $cardDetails = null, $paymentoption = null)
    {
        $amount = $amount * 100;
        Iugu::setApiKey($paymentoption['api_secret_key']);
        $paymentToken = Iugu_PaymentToken::create([
            "test" => "true",
            "account_id" => $paymentoption['auth_token'],
            "method" => "credit_card",
            "data" => [
                "number" => $cardDetails['card_number'],
                "verification_value" => $cardDetails['cvv'],
                "first_name" => $userDetails['firstName'],
                "last_name" => $userDetails['lastName'],
                "month" => $cardDetails['exp_month'],
                "year" => $cardDetails['exp_year']
            ]
        ]);
        $charge = $this->IugoCharge($paymentToken['id'], $paymentoption, $userDetails, $amount);
        if (isset($charge['success']) == 1) {
            return array('charge' => $charge);
        } else {
            return false;
        }
    }

    public function IugoCharge($paymentToken = null, $paymentoption = null, $userDetails = null, $amount = null)
    {
        Iugu::setApiKey($paymentoption['api_secret_key']);
        $charge = Iugu_Charge::create(
            [
                "token" => $paymentToken,
                "restrict_payment_method" => true,
                "email" => $userDetails['email'],
                "items" => [
                    [
                        "description" => "taxiPayment",
                        "quantity" => "1",
                        "price_cents" => $amount
                    ]
                ]
            ]
        );
        return $charge;
    }

    public function SaveCardBancard($api_public_key = null, $userId = null, $token = null)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8888",
            CURLOPT_URL => "https://vpos.infonet.com.py:8888/vpos/api/0.3/users/$userId/cards",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n    \"public_key\": \"$api_public_key\",\r\n    \"operation\": {\r\n        \"token\": \"$token\"\r\n    },\r\n    \"test_client\": true\r\n}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $res = json_decode($response, true);
            if ($res['status'] == "success") {
                $cards = $res['cards'];
                $payment_option_id = PaymentOption::where('slug', '=', 'BANCARD')->first();
                foreach ($cards as $card) {
                    $card_num = substr($card['card_masked_number'], -4);
                    $card = UserCard::updateOrCreate(
                        ['user_id' => $userId, 'card_number' => $card_num],
                        [
                            'token' => $card['alias_token'],
                            'payment_option_id' => $payment_option_id->id,
                            'expiry_date' => $card['expiration_date']
                        ]);
                }
                return array('result' => "1", 'cards' => $card);
            } else {
                return false;
            }
        }
    }

    public function redirectBancard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'process_id' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $process_id = $request->process_id;
        return view('merchant.paymentgateways.bancard', compact('process_id'));
    }

    public function BancardCheckout(Request $request)
    {
        $user = $request->user('api');
        $rand = rand(11111, 99999);
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
        if (!empty($paymentConfig)) {
            $token = md5("." . $paymentConfig->api_secret_key . $rand . $user->id . "request_new_card");
            $return_url = route('bancardCallback');
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_PORT => "8888",
                CURLOPT_URL => $paymentConfig->tokenization_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\r\n    \"public_key\": \"$paymentConfig->api_public_key\",\r\n    \"operation\": {\r\n        \"token\": \"$token\",\r\n        \"card_id\": $rand,\r\n        \"user_id\": $user->id,\r\n        \"user_cell_phone\": \"$user->UserPhone\",\r\n        \"user_mail\": \"$user->email\",\r\n        \"return_url\": \"$return_url\"\r\n    },\r\n    \"test_client\": true\r\n}",
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            $res = json_decode($response, true);
            if ($res['status'] == "success") {
                $process_id = array('process_id' => $res['process_id']);
                return redirect(route('redirectBancard', $process_id));
            } else {
                return response()->json(['result' => "0", 'message' => $res['messages']]);
            }
        } else {
            return response()->json(['result' => 0, 'message' => trans('api.message195'), 'data' => []]);
        }
    }

    public function bancardCallback(Request $request)
    {
        return $request;
    }

    public function DeleteCardBancard($cardToken = null, $userID = null, $token = null, $api_public_key = null)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8888",
            CURLOPT_URL => "https://vpos.infonet.com.py:8888/vpos/api/0.3/users/" . $userID . "/cards",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_POSTFIELDS => "{\n    \"public_key\": \"$api_public_key\",\n    \"operation\": {\n        \"token\": \"$token\",\n        \"alias_token\": \"$cardToken\"\n    },\n    \"test_client\": true\n}\n",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $res = json_decode($response, true);
            if (isset($res['status']) == "success") {
                return array('result' => "1", 'message' => 'Card Deleted Successfully');
            } else {
                return false;
            }
        }
    }

    public function ChargeBancard($payment_redirect_url = null, $api_public_key = null, $token = null, $shopProcessId = null, $amount = null, $currency = null, $cardToken = null)
    {
        $amount = number_format($amount, 2);
        $body_values = array(
            'public_key' => $api_public_key,
            'operation' => array(
                'token' => $token,
                'shop_process_id' => $shopProcessId,
                'items' => [array(
                    'name' => 'TaxiPayment1',
                    'store' => 4,
                    'store_branch' => 46,
                    'amount' => $amount,
                    'currency' => $currency,
                )],
                'number_of_payments' => 1,
                'additional_data' => "",
                'alias_token' => $cardToken,
                'test_client' => true));
        $body_values = json_encode($body_values, true);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8888",
            CURLOPT_URL => $payment_redirect_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            // CURLOPT_POSTFIELDS => "{\r\n    \"public_key\": \"$api_public_key\",\r\n    \"operation\": {\r\n        \"token\": \"$token\",\r\n        \"shop_process_id\": $shopProcessId,\r\n        \"items\": [\r\n            {\r\n                \"name\": \"TaxiPayment1\",\r\n                \"store\": 4,\r\n                \"store_branch\": 46,\r\n                \"amount\": \"$amount\",\r\n                \"currency\": \"$currency\"\r\n            }\r\n        ],\r\n        \"number_of_payments\": 1,\r\n        \"additional_data\": \"\",\r\n        \"alias_token\": \"$cardToken\"\r\n    },\r\n    \"test_client\": true\r\n}",
            CURLOPT_POSTFIELDS => $body_values,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $res = json_decode($response, true);
        if (isset($res['status']) && $res['status'] == 'success') {
            return array('result' => "1", 'message' => 'Payment Successful');
        } else {
            return array('result' => "0", 'message' => $res['messages'][0]['dsc']);
        }
    }

    public function createPrefIdMercado($authToken = null, $amount = null, $email = null, $currency = null)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.mercadopago.com/checkout/preferences?access_token=" . $authToken,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n    \"items\": [\n        {\n            \"title\": \"Taxi Payment\",\n            \"description\": \"Taxi Payment\",\n            \"quantity\": 1,\n            \"currency_id\": \"$currency\",\n            \"unit_price\": $amount\n        }\n    ],\n    \"payer\": {\n        \"email\": \"$email\"\n    }\n}",
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        $response = json_decode($response, true);
        if (!empty($response)) {
            return $response;
        } else {
            return false;
        }
    }


    //DPO payment gateway has been integrated according to new code and It's in DpoController file

//    public function getSubTokenDPO($companyToken = null, $email = null)
//    {
//        $curl = curl_init();
//        curl_setopt_array($curl, array(
//            CURLOPT_URL => "https://secure.3gdirectpay.com/API/v6/",
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_ENCODING => "",
//            CURLOPT_MAXREDIRS => 10,
//            CURLOPT_TIMEOUT => 30,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//            CURLOPT_CUSTOMREQUEST => "POST",
//            CURLOPT_POSTFIELDS => "<API3G> \r\n\r\n<CompanyToken>$companyToken</CompanyToken> \r\n\r\n<Request>getSubscriptionToken</Request> \r\n\r\n<SearchCriteria>1</SearchCriteria> \r\n\r\n<SearchCriteriaValue>$email</SearchCriteriaValue> \r\n\r\n</API3G> ",
//            CURLOPT_HTTPHEADER => array(
//                "Content-Type: application/xml"
//            ),
//        ));
//
//        $response = curl_exec($curl);
//        $err = curl_error($curl);
//        curl_close($curl);
//        $xml = new SimpleXMLElement($response);
//        $xml = json_encode($xml);
//        $xml = json_decode($xml, true);
//        if ($xml['Result'] == 000) {
//            $cards = $this->getCardsDPO($xml['CustomerToken'], $companyToken);
//            if (array_key_exists('data', $cards)) {
//                return $cards;
//            } else {
//                return $cards;
//            }
//        } else {
//            return array('result' => "0", 'message' => $xml['ResultExplanation']);
//        }
//    }
//
//    public function getCardsDPO($custToken, $companyToken)
//    {
//        $curl = curl_init();
//        curl_setopt_array($curl, array(
//            CURLOPT_URL => "https://secure.3gdirectpay.com/API/v6/",
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_ENCODING => "",
//            CURLOPT_MAXREDIRS => 10,
//            CURLOPT_TIMEOUT => 30,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//            CURLOPT_CUSTOMREQUEST => "POST",
//            CURLOPT_POSTFIELDS => "<API3G>\n    <CompanyToken>$companyToken</CompanyToken>\n    <Request>pullAccount</Request>\n    <customerToken>$custToken</customerToken>\n</API3G>",
//            CURLOPT_HTTPHEADER => array(
//                "Content-Type: application/xml"
//            ),
//        ));
//        $response = curl_exec($curl);
//        $err = curl_error($curl);
//        curl_close($curl);
//        $xml = new SimpleXMLElement($response);
//        $xml = json_encode($xml);
//        $xml = json_decode($xml, true);
//        if ($xml['Result'] == 000) {
//            return array('data' => $xml['paymentOptions']['option']);
//        } else {
//            return array('message' => $xml['ResultExplanation']);
//        }
//    }
//
//    public function createTransDPO(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'for' => 'required',
//            'currency' => 'required',
//            'countryDialcode' => 'required',
//            'amount' => 'required',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return $this->failedResponse($errors[0]);
//        }
//        $user = ($request->for == "user") ? $request->user('api') : $request->user('api-driver');
//        $payConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
//        $date = date('Y/m/d H:i');
//        $curl = curl_init();
//        curl_setopt_array($curl, array(
//            CURLOPT_URL => "https://secure.3gdirectpay.com/API/v6/",
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_ENCODING => "",
//            CURLOPT_MAXREDIRS => 10,
//            CURLOPT_TIMEOUT => 30,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//            CURLOPT_CUSTOMREQUEST => "POST",
//            CURLOPT_POSTFIELDS => "<API3G>\r\n    <CompanyToken>$payConfig->auth_token</CompanyToken>\r\n    <Request>createToken</Request>\r\n    <Transaction>\r\n        <PaymentAmount>$request->amount</PaymentAmount>\r\n        <PaymentCurrency>$request->currency</PaymentCurrency>\r\n        <CompanyRefUnique>1</CompanyRefUnique>\r\n        <PTL>5</PTL>\r\n        <TransactionChargeType>1</TransactionChargeType>\r\n        <customerEmail>$user->email</customerEmail>\r\n        <customerFirstName>$user->first_name</customerFirstName>\r\n        <customerLastName>$user->last_name</customerLastName>\r\n        <customerDialCode>$request->countryDialcode</customerDialCode>\r\n        <customerPhone>$user->UserPhone</customerPhone>\r\n    <AllowRecurrent>1</AllowRecurrent>\r\n    </Transaction>\r\n    <Services>\r\n        <Service>\r\n            <ServiceType>5525</ServiceType>\r\n            <ServiceDescription>Service1</ServiceDescription>\r\n            <ServiceDate>$date</ServiceDate>\r\n        </Service>\r\n    </Services>\r\n    </API3G>",
//            CURLOPT_HTTPHEADER => array(
//                "Content-Type: application/xml",
//            ),
//        ));
//
//        $response = curl_exec($curl);
//        $err = curl_error($curl);
//        curl_close($curl);
//        $xml = new SimpleXMLElement($response);
//        $xml = json_encode($xml);
//        $xml = json_decode($xml, true);
//        if ($xml['Result'] == 000) {
//            DB::table('dpo_transactions')->insert([
//                'merchant_id' => $user->merchant_id,
//                'user_id' => $user->id,
//                'type' => ($request->for == "user") ? 1 : 2,
//                'amount' => $request->amount,
//                'transaction_token' => $xml['TransToken'],
//                'created_at' => date('Y-m-d H:i:s'),
//                'updated_at' => date('Y-m-d H:i:s')
//            ]);
//            $url = 'https://secure.3gdirectpay.com/dpopayment.php?ID=' . $xml['TransToken'];
//            return $this->successResponse("Success", array('url' => $url, "TransactionToken" => $xml['TransToken']));
//        } else {
//            return $this->failedResponse('Operation Failed');
//        }
//    }
//
//    public function ridePaymentDPO($auth_token = null, $amount = null, $currency = null, $email = null, $firstNname = null, $lastName = null, $UserPhone = null, $cardToken = null)
//    {
//        $date = date('Y/m/d H:i');
//        $curl = curl_init();
//        curl_setopt_array($curl, array(
//            CURLOPT_URL => "https://secure.3gdirectpay.com/API/v6/",
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_ENCODING => "",
//            CURLOPT_MAXREDIRS => 10,
//            CURLOPT_TIMEOUT => 30,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//            CURLOPT_CUSTOMREQUEST => "POST",
//            CURLOPT_POSTFIELDS => "<API3G>\r\n    <CompanyToken>$auth_token</CompanyToken>\r\n    <Request>createToken</Request>\r\n    <Transaction>\r\n        <PaymentAmount>$amount</PaymentAmount>\r\n        <PaymentCurrency>$currency</PaymentCurrency>\r\n        <CompanyRefUnique>1</CompanyRefUnique>\r\n        <PTL>5</PTL>\r\n        <TransactionChargeType>1</TransactionChargeType>\r\n        <customerEmail>$email</customerEmail>\r\n        <customerFirstName>$firstNname</customerFirstName>\r\n        <customerLastName>$lastName</customerLastName>\r\n        <customerPhone>$UserPhone</customerPhone>\r\n    <AllowRecurrent>1</AllowRecurrent>\r\n    </Transaction>\r\n    <Services>\r\n        <Service>\r\n            <ServiceType>5525</ServiceType>\r\n            <ServiceDescription>Service1</ServiceDescription>\r\n            <ServiceDate>$date</ServiceDate>\r\n        </Service>\r\n    </Services>\r\n    </API3G>",
//            CURLOPT_HTTPHEADER => array(
//                "Content-Type: application/xml",
//            ),
//        ));
//
//        $response = curl_exec($curl);
//        $err = curl_error($curl);
//        curl_close($curl);
//        $xml = new SimpleXMLElement($response);
//        $xml = json_encode($xml);
//        $xml = json_decode($xml, true);
//        if ($xml['Result'] == 000) {
//            $payment = $this->makepaymentDPO($auth_token, $xml['TransToken'], $cardToken);
//            if (is_array($payment)) {
//                return array();
//            } else {
//                return false;
//            }
//        } else {
//            return $this->failedResponse('Operation Failed');
//        }
//    }

//    public function tokenizePeach(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'cc_number' => 'required',
//            'exp_month' => 'required',
//            'exp_year' => 'required',
//            'card_type' => 'required',
//            'cvv' => 'required',
//            'for' => 'required'
//
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
//        }
//        $user = ($request->for == "user") ? $request->user('api') : $request->user('api-driver');
//        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
//        if (empty($paymentConfig)) {
//            return response()->json(['result' => 0, 'message' => trans("$string_file.configuration_not_found"), 'data' => []]);
//        }
//        $gateway_condition = $paymentConfig->gateway_condition == 1 ? true : false;
//        $url = $paymentConfig->payment_redirect_url;
//        $data = "entityId=" . $paymentConfig->api_secret_key .
//            "&amount=1.00" .
//            "&currency=ZAR" .
//            "&paymentBrand=" . $request->card_type .
//            "&paymentType=PA" .
//            "&card.number=" . $request->cc_number .
//            "&card.holder=" . $user->userName .
//            "&card.expiryMonth=" . $request->exp_month .
//            "&card.expiryYear=" . $request->exp_year .
//            "&card.cvv=" . $request->cvv .
//            "&createRegistration=true&shopperResultUrl=" . route('shopper', $user->id);
//
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//            'Authorization:Bearer ' . $paymentConfig->auth_token));
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $gateway_condition);// this should be set to true in production
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        $responseData = curl_exec($ch);
//        if (curl_errno($ch)) {
//            return curl_error($ch);
//        }
//        curl_close($ch);
//        $responseData = json_decode($responseData, true);
//        if ($responseData['result']['code'] == "000.000.000" || $responseData['result']['code'] == "000.100.110" || $responseData['result']['code'] == "000.200.000") {
//            if (array_key_exists("registrationId", $responseData)) {
//                return redirect(route('redirectPeach', $responseData));
//            }
//        } else {
//            return false;
//        }
//    }

    public function makepaymentDPO($companyToken = null, $transToken = null, $subToken = null)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://secure.3gdirectpay.com/API/v6/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<API3G>  \r\n<CompanyToken>$companyToken</CompanyToken> \r\n<Request>chargeTokenRecurrent</Request> \r\n<TransactionToken>$transToken</TransactionToken> \r\n<subscriptionToken>$subToken</subscriptionToken> \r\n</API3G> ",
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Content-Type: application/xml",
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $xml = new SimpleXMLElement($response);
        $xml = json_encode($xml);
        $xml = json_decode($xml, true);
        if ($xml['Result'] == 000) {
            return array('result' => "1", 'message' => $xml['ResultExplanation']);
        } else {
            return false;
        }
    }

    public function tokenizePeach($cc_number = null, $exp_month = null, $exp_year = null, $card_type = null, $cvv = null, $userName = null, $api_secret_key = null, $auth_token = null, $tokenization_url = null)
    {
        $url = $tokenization_url . "v1/registrations";
        $data = "entityId=" . $api_secret_key .
            "&paymentBrand=" . $card_type .
            "&card.number=" . $cc_number .
            "&card.holder=" . $userName .
            "&card.expiryMonth=" . $exp_month .
            "&card.expiryYear=" . $exp_year .
            "&card.cvv=" . $cvv;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . $auth_token));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        $responseData = json_decode($responseData, true);
        if ($responseData['result']['code'] == "000.000.000" || $responseData['result']['code'] == "000.100.110") {
            if (array_key_exists("id", $responseData)) {
                return $responseData;
            }
        } else {
            return false;
        }
    }

    public function tokenizeHyper($cc_number = null, $exp_month = null, $exp_year = null, $card_type = null, $cvv = null, $userName = null, $api_secret_key = null, $auth_token = null, $tokenization_url = null)
    {
        $url = $tokenization_url . "v1/registrations";
        $data = "entityId=" . $api_secret_key . "&paymentBrand=" . $card_type . "&card.number=" . $cc_number . "&card.holder=" . $userName . "&card.expiryMonth=" . $exp_month . "&card.expiryYear=" . $exp_year . "&card.cvv=" . $cvv;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . $auth_token));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
//        p($responseData);
//        p(curl_error($ch));
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        $responseData = json_decode($responseData, true);
        if ($responseData['result']['code'] == "000.000.000" || $responseData['result']['code'] == "000.100.110") {
            if (array_key_exists("id", $responseData)) {
                return $responseData;
            }
        } else {
            return false;
        }
    }

    public function redirectPeach(Request $request)
    {
        if (!empty($request)) {
            $responseData = $request->toArray();
            return view('merchant.paymentgateways.peachpayment', compact('responseData'));
        }
    }

    public function shopper(Request $request, $id)
    {
        $result = $this->getStatusPeach($request->id, $id);
        return $result;
    }

    public function getStatusPeach($paymentId = NULL, $userId = NULL)
    {
        $user = User::find($userId);
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
        if (empty($paymentConfig)) {
            return response()->json(['result' => 0, 'message' => trans('api.message195'), 'data' => []]);
        }
        $gateway_condition = $paymentConfig->gateway_condition == 1 ? true : false;
        if ($gateway_condition == false) {
            $url = "https://test.oppwa.com/v1/payments/" . $paymentId;
        } else {
            $url = "https://oppwa.com/v1/payments/" . $paymentId;
        }
        $url .= "?entityId=" . $paymentConfig->api_secret_key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . $paymentConfig->auth_token));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $gateway_condition);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        $responseData = json_decode($responseData, true);
        if ($responseData['result']['code'] == "000.000.000" || $responseData['result']['code'] == "000.100.110") {
            $this->savecardPeach($responseData, "user", $user->id, "PEACHPAYMENT", $responseData['paymentBrand'], $responseData['card']['expiryMonth'], $responseData['card']['expiryYear']);
            $status = "1";
            $messsage = trans('api.message131');
        } else {
            $status = "0";
            $messsage = $responseData['result']['description'];
        }
        return response()->json(['result' => $status, 'message' => $messsage]);
    }

    public function savecardPeach($responseData, $for, $userId, $payment_option_id, $card_type = NULL, $card_month = NULL, $card_year = NULL)
    {
        if ($for == "user") {
            $card = UserCard::updateOrCreate([
                'user_id' => $userId,
                'card_number' => $responseData['card']['last4Digits']],
                ['token' => $responseData['id'],
                    'payment_option_id' => $payment_option_id,
                    'card_type' => $card_type,
                    'exp_month' => $card_month,
                    'exp_year' => $card_year,
                ]);
        } else {
            DriverCard::updateOrCreate([
                'driver_id' => $userId,
                'card_number' => $responseData['card']['last4Digits']],
                ['token' => $responseData['id'],
                    'payment_option_id' => $payment_option_id
                ]);
        }
    }

    public function peachpayment($api_secret_key = null, $auth_token = null, $amount, $currency = null, $token = null, $gateway_condition = NULL, $userId = null, $tokenization_url)
    {
        if ($gateway_condition == false) {
            $url = $tokenization_url . "v1/registrations/$token/payments";
        } else {
            $url = $tokenization_url . "v1/registrations/$token/payments";
        }
        $amount = number_format($amount, 2);
        $data = "entityId=" . $api_secret_key .
            "&amount=" . $amount .
            "&currency=" . $currency .
            "&recurringType=REPEATED" .
            "&paymentType=DB" .
            "&shopperResultUrl=" . route('shopper', ['id' => $userId]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . $auth_token));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $gateway_condition);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        $responseData = json_decode($responseData, true);
        if ($responseData['result']['code'] == "000.000.000" || $responseData['result']['code'] == "000.100.110") {
            return $responseData;
        } else {
            return false;
        }
    }

    public function DpoMobileMoney(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required',
            'TransactionToken' => 'required',
            'PhoneNumber' => 'required',
            'MNO' => 'required',
            'MNOcountry' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = ($request->for == "user") ? $request->user('api') : $request->user('api-driver');
        $payConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
        $string_file = $this->getStringFile($user->merchant_id);
        if (empty($payConfig)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://secure.3gdirectpay.com/API/v6/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<API3G>\n    <CompanyToken>$payConfig->auth_token</CompanyToken>\n    <Request>ChargeTokenMobile</Request>\n    <TransactionToken>$request->TransactionToken</TransactionToken>\n    <PhoneNumber>$request->PhoneNumber</PhoneNumber>\n    <MNO>$request->MNO</MNO>\n    <MNOcountry>$request->MNOcountry</MNOcountry>\n</API3G>",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/xml"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        $xml = new SimpleXMLElement($response);
        $xml = json_encode($xml);
        $response = json_decode($xml, true);
        return $response;

    }

    public function verifyMobileMoneyDPO(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required',
            'TransactionToken' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = ($request->for == "user") ? $request->user('api') : $request->user('api-driver');
        $string_file = $this->getStringFile($user->merchant_id);
        $payConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
        if (empty($payConfig)) {
            return $this->failedResponse(trans("$string_file.payment_configuration_not_found"));
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://secure.3gdirectpay.com/API/v6/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<API3G>\n    <CompanyToken>$payConfig->auth_token</CompanyToken>\n    <Request>verifyToken</Request>\n    <TransactionToken>$request->TransactionToken</TransactionToken>\n</API3G>",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/xml",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        $xml = new SimpleXMLElement($response);
        $xml = json_encode($xml);
        $response = json_decode($xml, true);
        if ($response['Result'] == 000) {
            $type = ($request->for == "user") ? 1 : 2;
            $trans = DB::table('dpo_transactions')->where(['merchant_id' => $user->merchant_id, 'type' => $type, 'user_id' => $user->id, 'payment_status' => 0, 'transaction_token' => $request->TransactionToken])->first();
            if (!empty($trans)) {
                $amount = $trans->amount;
                DB::table('dpo_transactions')
                    ->where(['merchant_id' => $user->merchant_id, 'type' => $type, 'user_id' => $user->id, 'payment_status' => 0, 'transaction_token' => $request->TransactionToken])
                    ->update(['payment_status' => '1', 'updated_at' => date('Y-m-d H:i:s')]);

                $message = trans("$string_file.payment_done");
                $data = ['result' => '1', 'amount' => $amount, 'message' => $message];
                $merchant_id = $user->merchant_id;
                if ($type == 1) {
                    $wallet = $user->wallet_balance;
                    $user->wallet_balance = sprintf("%0.2f", $wallet + $amount);
                    $user->save();
                    $money = UserWalletTransaction::create([
                        'merchant_id' => $merchant_id,
                        'user_id' => $user->id,
                        'platfrom' => 2,
                        'amount' => $amount,
                        'receipt_number' => "Application",
                        'type' => 1,
                        'transaction_id' => $request->TransactionToken,
                    ]);
                    Onesignal::UserPushMessage($user->id, $data, $message, 89, $merchant_id);
                } else {
                    $money = $user->wallet_money;
                    $user->wallet_money = sprintf("%0.2f", $money + $amount);
                    $user->save();
                    $money = DriverWalletTransaction::create([
                        'merchant_id' => $merchant_id,
                        'driver_id' => $user->id,
                        'transaction_type' => 1,
                        'payment_method' => 3,
                        'receipt_number' => "Application",
                        'amount' => sprintf("%0.2f", $amount),
                        'platform' => 2,
                        'description' => "Add Wallet Money",
                        'narration' => 2,
                        'transaction_id' => $request->TransactionToken,
                    ]);
                    Onesignal::DriverPushMessage($user->id, $data, $message, 89, $merchant_id);
                }
            }
            return $this->successResponse(trans('api.message135'));
        } else {
            return $this->failedResponse($response['ResultExplanation']);
        }
    }

    public function korbaWeb(Request $request)
    {
        $transaction_id = uniqid();
        $user = ($request->for == "user") ? $request->user('api') : $request->user('api-driver');
        $phoneCode = $user->Country->phonecode;
        $amount = $request->amount;
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
        $user_phone = '';
        if (isset($user->UserPhone) && $user->UserPhone != "") {
            $user_phone = str_replace($phoneCode, "", $user->UserPhone);
        } else if (isset($user->phoneNumber) && $user->phoneNumber != "") {
            $user_phone = str_replace($phoneCode, "", $user->phoneNumber);
        }
        if (!empty($paymentConfig)) {
            $callback_url = $paymentConfig->callback_url;
            $secret = $paymentConfig->api_secret_key;
            $message = "amount=$amount&callback_url=$callback_url&client_id=$paymentConfig->auth_token&customer_number=$user_phone&description=Taxi_Payment&network_code=$user->network_code&transaction_id=$transaction_id&vodafone_voucher_code=599020";
            $HMAC = hash_hmac('sha256', $message, $secret);            // p($HMAC);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://xchange.korbaweb.com/api/v1.0/collect/",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\r\n   \"amount\": $amount,    \r\n   \"callback_url\": \"$callback_url\",\r\n   \"client_id\": \"$paymentConfig->auth_token\",\r\n   \"customer_number\": \"$user_phone\",\r\n   \"description\": \"Taxi_Payment\",\r\n   \"network_code\": \"$user->network_code\",\r\n   \"transaction_id\": \"$transaction_id\"\r\n,\r\n   \"vodafone_voucher_code\": \"599020\"}\r\n\r\n\r\n",
                CURLOPT_HTTPHEADER => array(
                    "authorization: HMAC $paymentConfig->api_public_key:$HMAC",
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "postman-token: 5e39360c-1526-f5f4-a557-ccc7ecde7316"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            $response = json_decode($response, true);
            if ($err) {
                return response()->json(['result' => 0, 'message' => "cURL Error #:" . $err, 'data' => []]);
            } else {
                return response()->json(['result' => 1, 'message' => 'success', 'data' => $response]);
            }
        } else {
            return response()->json(['result' => 0, 'message' => trans('api.message195'), 'data' => []]);
        }
    }

    public function callbackkorba(Request $request)
    {
        $trans = explode("-", $request->transaction_id);
        $done_ride = $trans[0];
        $status = $request->status;
        $message = $request->message;
        if ($status == "FAILED") {
            return response()->json(['result' => 0, 'message' => $message, 'data' => $done_ride]);
        } else {
            return response()->json(['result' => 1, 'message' => $message, 'data' => $done_ride]);
        }
    }

    public function beyonicMobileMoney(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required',
            'currency' => 'required',
            'Phone' => 'required',
            'amount' => 'required',
            'type' => 'required', //1=wallet,2=Booking
            'booking_id' => 'required_if:type,2' //1=wallet,2=Booking
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $user = ($request->for == "user") ? $request->user('api') : $request->user('api-driver');
        $string_file = $this->getStringFile($user->merchant_id);
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
        if (empty($paymentConfig)) {
            return response()->json(['result' => 0, 'message' => trans("$string_file.payment_configuration_not_found"), 'data' => []]);
        }

        \Beyonic::setApiKey($paymentConfig->api_secret_key);
        $id = $request->type == 2 ? $request->booking_id : $user->id;
        $collection_request = \Beyonic_Collection_Request::create(array(
            "phonenumber" => $request->Phone,
            "amount" => $request->amount,
            "currency" => $request->currency,
            "metadata" => array("id" => $id, "name" => $request->type . "-" . $request->for,"booking_id"=>$request->booking_id),
            "send_instructions" => "True"
        ));
        \Log::channel('beyonic')->emergency(['request_type'=>"Payment request","data"=>(array)$collection_request]);
        if (!empty($collection_request->id)) {
            return response()->json(['result' => "1",'message' => __("$string_file.request_send_successfully"),'data' => $collection_request->id]);
        }else{
            return response()->json(['result' => 0, 'message' => "Request Not Processed", 'data' => []]);
        }
    }

    public function beyonicCallback(Request $request)
    {
        $response = file_get_contents('php://input');
        $resp = json_decode($response, true);
        $res['data'] = $resp;
        $status = $res['data']['status'];
        \Log::channel('beyonic')->emergency(["request_type"=>"Callback","data"=>$resp]);
        if($status == "processing_started" || $status == "successful"){
            \Log::channel('beyonic')->emergency($res);
        }
        $Id = $res['data']['collection_request']['metadata']['id'];
        $type = explode('-', $res['data']['collection_request']['metadata']['name']);
        $wallet_booking = $type[0];
        $user_driver = $type[1];
        if ($status == "successful") {
            $amount = $res['data']['amount'];
            $transactionId = $res['data']['id'];
            if($wallet_booking == 1){
                if($user_driver == 'user'){
                    $receipt = "Application : " . $transactionId;
                    $paramArray = array(
                        'user_id' => $Id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $transactionId,
                        'notification_type' => 3
                    );
                    WalletTransaction::UserWalletCredit($paramArray);
                    // CommonController::UserWalletCredit($Id,null,$amount,2,2,2,null,$transactionId,3);
                }else{
                    $receipt = "Application : " . $transactionId;
                    $paramArray = array(
                        'driver_id' => $Id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $transactionId,
                        'notification_type' => 3
                    );
                    WalletTransaction::WalletCredit($paramArray);
                    // CommonController::WalletCredit($Id,null,$amount,2,2,2,null,$transactionId,3);
                }
            } else {
                $Id = $res['data']['collection_request']['metadata']['booking_id'];
                $booking = Booking::find($Id);
                $booking->payment_status = 1;
                $booking->save();
                $message = trans('api.message65');
                $data = ['message' => $message];
                Onesignal::UserPushMessage($booking->user_id, $data, $message, 3, $booking->merchant_id);
                Onesignal::DriverPushMessage($booking->driver_id, $data, $message, 3, $booking->merchant_id);
//                $message =trans("$string_file.payment_done");
////                $playerids = array($booking->Driver->player_id);
//                Onesignal::DriverPushMessage($booking->driver_id, '', $message, 23, $booking->merchant_id);
            }
        }else{
            if(!empty($res['data']['error_message'])){
                $message = explode('-',$res['data']['error_message'])[1];
                $data = ['message' => $message];
                if($wallet_booking == 1){
                    if($user_driver == 'user'){
                        $user = User::find($Id);
                        Onesignal::UserPushMessage($user->id, $data, $message, 3, $user->merchant_id);
                    }else{
                        $driver = Driver::find($Id);
                        Onesignal::DriverPushMessage($driver->id, $data, $message, 3, $driver->merchant_id);
                    }
                }else{
                    $booking = Booking::find($Id);
                    Onesignal::UserPushMessage($booking->user_id, $data, $message, 3, $booking->merchant_id);
                }
            }
        }
    }

    public function MpessaAddMoney(Request $request)
    {
        $type = $_POST['type'];
        $phone = substr($_POST["phone"], 1);
        $amount = (int)$_POST["amount"];
        $url = env('APP_URL');

        if ($type == 1) {
            $user = $request->user('api');
            $user_id = $user->id;
        } else {
            $user = $request->user('api-driver');
            $user_id = $user->id;
        }

        $trans = DB::table('mpessa_transactions')->where(['merchant_id' => $user->merchant_id, 'type' => $type, 'user_id' => $user_id, 'payment_status' => 'success'])->first();
        $diff = '';
        if (!empty($trans)) {
            $CurrentDate = date('Y-m-d H:i:s');
            $diff = round((strtotime($CurrentDate) - strtotime($trans->updated_at)) / 60, 2);
        }

        if (empty($diff) || $diff > 3) {
            $mpesa = new Mpesa();
            try {

                // $response = $mpesa-lib->STKPush([
                //     'amount' => $amount, // contains the amount user want to load to wallet
                //     'transactionDesc' => "load your wallet", // a string
                //     'phoneNumber' => $phone, // Phone number should look like 254700000000
                //     'accountReference' => $phone, // you can pass phone number
                //     'CallBackURL' => $url . 'api/user/mpessapayment_confirmation?user_id=' . $user_id . '&type=' . $type . '&amnt=' . $amount . '', // the callback get repsonse from mpesa-lib server. Check implementation in the next fucntion
                // ]);
                //p($response);

                $time = date('YmdHis');
                $shortCode = 4101899;
                $passkey = '22c71dcf18d22b909ffd9c3e4cf4bb01eff6a2a96c479b13aeaca4e83dc731ec';
                $password  = \base64_encode($shortCode . $passkey . $time);
                $response = $mpesa->STKPush([
                    'BusinessShortCode' => $shortCode,
                    'Password' => $password,
                    'Timestamp' => $time,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => $amount, // contains the amount user want to load to wallet
                    'PartyA' => $phone,
                    // 'PartyB' => 9679573,
                    'TransactionDesc' => "load your wallet", // a string
                    'PhoneNumber' => $phone, // Phone number should look like 254700000000
                    'AccountReference' => $phone, // you can pass phone number
                    'CallBackURL' => $url . 'api/mpessapayment_confirmation?user_id=' . $user_id . '&type=' . $type . '&amnt=' . $amount . '&merchant_id='.$user->merchant_id, // the callback get repsonse from mpesa-lib server. Check implementation in the next fucntion
                ]);
            } catch (Exception $e) {
                $response = json_decode($e->getMessage());
                $message = $response->errorMessage ?? $e->getMessage();
                return response()->json(['result' => 0, 'message' => $message, 'data' => '']);
            }

            $log = " Api Name:-MpessaAddMoney - : " . date("Y-m-d,h:i:s A") . PHP_EOL . "request: " . file_get_contents('php://input') . PHP_EOL . "response: " . json_encode($response) . PHP_EOL . "user_id='$user_id'" . PHP_EOL . "-------------------------" . PHP_EOL;
            Log::channel('mpessa_api')->emergency($log);
            // file_put_contents('./logfile/log_MpessaAddMoney_' . date("Y.m.d") . '.txt', $log, FILE_APPEND);
            // p($user_id);
            // p([
            //                     'merchant_id' => $user->merchant_id,
            //                     'user_id' => $user_id,
            //                     'type' => $type,
            //                     'amount' => $amount,
            //                     'checkout_request_id' => $response->CheckoutRequestID,
            //                     'created_at' => date('Y-m-d H:i:s'),
            //                     'updated_at' => date('Y-m-d H:i:s')
            //                 ]);
            if ($response->CheckoutRequestID) {
                $a = DB::table('mpessa_transactions')->insert([
                    'merchant_id' => $user->merchant_id,
                    'user_id' => $user_id,
                    'type' => $type,
                    'amount' => $amount,
                    'checkout_request_id' => $response->CheckoutRequestID,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                //p($a);
                return array('result' => "1", 'message' => "Transaction is proceeding ,Please don't Terminate the App Or don't press back button");
            } else {
                return array('result' => "0", 'message' => "Something went wrong");
            }
        } else {
            return array('result' => "0", 'message' => trans('api.mpesa_error_msg'));
        }
    }

    public function MpessaCallBack(Request $request)
    {
        // p('sd');
        $userId = $request->user_id;
        $string_file = $this->getStringFile($request->merchant_id);
        $type = $request->type;
        $amount = $request->amnt;
        //  \Log::channel('mpessa_api')->emergency($request->all());
        $data = file_get_contents('php://input');
        if (!$data) {
            $log = " Api Name:-MpessaAddMoney - : " . date("Y-m-d,h:i:s A") . PHP_EOL . "request:invalid request " . file_get_contents('php://input') . PHP_EOL . "response: " . json_encode($data) . PHP_EOL . "user_id=$userId" . PHP_EOL . "-------------------------" . PHP_EOL;
            Log::channel('mpessa_api')->emergency($log);
            echo "Invalid Request";
            exit;
        }
        $data = json_decode($data);
        $tmp = $data->Body->stkCallback;
        $master = array();
        if ($tmp->ResultCode == 0) {
            foreach ($data->Body->stkCallback->CallbackMetadata->Item as $item) {
                $item = (array)$item;
                $master[$item['Name']] = ((isset($item['Value'])) ? $item['Value'] : NULL);
            }
        }
        $master = (object)$master;
        $master->ResultCode = $tmp->ResultCode;
        $master->MerchantRequestID = $tmp->MerchantRequestID;
        $master->CheckoutRequestID = $tmp->CheckoutRequestID;
        $master->ResultDesc = $tmp->ResultDesc;
        //Check MPESA status query then dump data in the table
        $user = $type == 1 ? User::find($userId) : Driver::find($userId);
        // p($type);
        // p($master->ResultCode);
        if ($master->ResultCode == 0) {
            $trans = DB::table('mpessa_transactions')->where(['merchant_id' => $user->merchant_id, 'type' => $type, 'user_id' => $userId, 'payment_status' => null, 'amount' => $amount, 'checkout_request_id' => $tmp->CheckoutRequestID])->first();
            // p($trans);
            if (!empty($trans)) {
                DB::table('mpessa_transactions')
                    ->where(['merchant_id' => $user->merchant_id, 'type' => $type, 'user_id' => $userId, 'amount' => $amount, 'checkout_request_id' => $tmp->CheckoutRequestID])
                    ->update(['payment_status' => 'success', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);

                $message =trans("$string_file.payment_done");
                $data = ['result' => '1', 'amount' => $amount, 'message' => $message];
                $merchant_id = $user->merchant_id;
                if ($type == 1) {
                    // p('ssd');
                    $receipt = "Application : " . $master->CheckoutRequestID;
                    $paramArray = array(
                        'user_id' => $user->id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $master->CheckoutRequestID,
                        'notification_type' => 89
                    );
                    // p($paramArray);
                    WalletTransaction::UserWalletCredit($paramArray);
                    //                    CommonController::UserWalletCredit($user->id,null,$amount,2,2,2,$receipt,$master->CheckoutRequestID,89);
                    //                    $wallet = $user->wallet_balance;
                    //                    $user->wallet_balance = sprintf("%0.2f", $wallet + $amount);
                    //                    $user->save();
                    //                    $money = UserWalletTransaction::create([
                    //                        'merchant_id' => $merchant_id,
                    //                        'user_id' => $user->id,
                    //                        'platfrom' => 2,
                    //                        'amount' => $amount,
                    //                        'receipt_number' => "Application",
                    //                        'type' => 1,
                    //                        'transaction_id' => $master->CheckoutRequestID,
                    //                    ]);
                    //                    Onesignal::UserPushMessage($userId, $data, $message, 89, $merchant_id);
                } else {
                    $receipt = "Application : " . $master->CheckoutRequestID;
                    $paramArray = array(
                        'driver_id' => $user->id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 3,
                        'receipt' => $receipt,
                        'transaction_id' => $master->CheckoutRequestID,
                        'notification_type' => 89
                    );
                    WalletTransaction::WalletCredit($paramArray);
                    //                    CommonController::WalletCredit($user->id,null,$amount,2,2,3,$receipt,$master->CheckoutRequestID,89);
                    //                    $money = $user->wallet_money;
                    //                    $user->wallet_money = sprintf("%0.2f", $money + $amount);
                    //                    $user->save();
                    //                    $money = DriverWalletTransaction::create([
                    //                        'merchant_id' => $merchant_id,
                    //                        'driver_id' => $user->id,
                    //                        'transaction_type' => 1,
                    //                        'payment_method' => 3,
                    //                        'receipt_number' => "Application",
                    //                        'amount' => sprintf("%0.2f", $amount),
                    //                        'platform' => 2,
                    //                        'description' => "Add Wallet Money",
                    //                        'narration' => 2,
                    //                        'transaction_id' => $master->CheckoutRequestID,
                    //                    ]);
                    //                    Onesignal::DriverPushMessage($userId, $data, $message, 89, $merchant_id);
                }
            }

            $data = array(
                "amount" => $master->Amount,
                "transcode" => $master->MpesaReceiptNumber,
                "user_id" => $userId,
                "status" => "COMPLETE",
            );
            $log = " Api Name:-MpessaAddMoney - : " . date("Y-m-d,h:i:s A") . PHP_EOL . "request: " . file_get_contents('php://input') . PHP_EOL . "response: " . json_encode($data) . PHP_EOL . "user_id=$userId" . PHP_EOL . "-------------------------" . PHP_EOL;
            Log::channel('mpessa_api')->emergency($log);
            exit;
        } else {
            $trans = DB::table('mpessa_transactions')->where(['merchant_id' => $user->merchant_id, 'type' => $type, 'user_id' => $userId, 'payment_status' => null, 'amount' => $amount, 'checkout_request_id' => $tmp->CheckoutRequestID])->first();
            if (!empty($trans)) {
                DB::table('mpessa_transactions')
                    ->where(['merchant_id' => $user->merchant_id, 'type' => $type, 'user_id' => $userId, 'amount' => $amount, 'checkout_request_id' => $tmp->CheckoutRequestID])
                    ->update(['payment_status' => 'Failed', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);

                $message = $master->ResultDesc;
                $data = ['result' => '0', 'amount' => $amount, 'message' => $message];
                $merchant_id = $user->merchant_id;
                if ($type == 1) {
                    Onesignal::UserPushMessage($userId, $data, $message, 89, $merchant_id);
                } else {
                    Onesignal::DriverPushMessage($userId, $data, $message, 89, $merchant_id);
                }
            }

            $data = array(
                "user_id" => $userId,
                "status" => "FAILED",
            );
            $log = " Api Name:-MpessaAddMoney - : " . date("Y-m-d,h:i:s A") . PHP_EOL . "request: " . file_get_contents('php://input') . PHP_EOL . "response: " . json_encode($data) . PHP_EOL . "user_id=$userId" . PHP_EOL . "-------------------------" . PHP_EOL;
            Log::channel('mpessa_api')->emergency($log);
            exit;
        }
    }


    public function DeleteCardPeachPayment($api_secret_key = null, $auth_token = null, $card_token = null, $tokenization_url = null)
    {
        $url = $tokenization_url . "v1/registrations/$card_token";
        $url .= "?entityId=" . $api_secret_key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . $auth_token
        ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        $responseData = json_decode($responseData, true);
        if ($responseData['result']['code'] == "000.000.000" || $responseData['result']['code'] == "000.100.110") {
            if (array_key_exists("id", $responseData)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function hyperPayment($api_secret_key = null, $auth_token = null, $amount, $currency = null, $token = null, $gateway_condition = NULL, $userId = null, $tokenization_url)
    {
        $url = $tokenization_url . "v1/registrations/$token/payments";
        $amount = number_format($amount, 2);
        $data = "entityId=" . $api_secret_key .
            "&amount=" . $amount .
            "&currency=" . $currency .
            "&recurringType=REPEATED" .
            "&paymentType=DB" .
            "&shopperResultUrl=" . route('shopper', ['id' => $userId]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer ' . $auth_token));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $gateway_condition);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $responseData = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        $responseData = json_decode($responseData, true);
        if ($responseData['result']['code'] == "000.000.000" || $responseData['result']['code'] == "000.100.110") {
            return $responseData;
        } else {
            return false;
        }
    }

    //    public function tokenGenerateSelcom($cardNumber = null, $expMonth = null, $expYear = null, $cardType = null, $cvv = null, $email = null, $userName = null, $merchantKey = null, $merchantId = null, $tokenizationUrl = null)
    //    {
    //        $isPost = 1;
    //        $url = 'http://127.0.0.1/selcom-api-gateway/v1/utilitypayment/process';
    //        $authorization = base64_encode('202cb962ac59075b964b07152d234b70');
    //        $req = array("utilityref"=>"12345", "transid"=>"transid", "amount"=>"amount");
    //        $json = json_encode($req);
    //        $signed_fields  = implode(',', array_keys($req));
    //        $timestamp = date('c'); //2019-02-26T09:30:46+03:00
    //        $api_secret = '81dc9bdb52d04dc20036dbd8313ed055';
    //
    //        $digest = computeSignature($req, $signed_fields, $timestamp, $api_secret);
    //        $headers = array(
    //            "Content-type: application/json;charset=\"utf-8\"", "Accept: application/json", "Cache-Control: no-cache",
    //            "Authorization: SELCOM $authorization",
    //            "Digest-Method: HS256",
    //            "Digest: $digest",
    //            "Timestamp: $timestamp",
    //            "Signed-Fields: $signed_fields",
    //        );
    //
    //        $ch = curl_init();
    //        curl_setopt($ch, CURLOPT_URL, $url);
    //        if($isPost){
    //            curl_setopt($ch, CURLOPT_POST, 1);
    //            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    //        }
    //        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //        curl_setopt($ch,CURLOPT_TIMEOUT,90);
    //        $result = curl_exec($ch);
    //        curl_close($ch);
    //        $res = json_decode($result, true);
    //        p($res);
    //
    //        if (isset($res['Payment'])) {
    //            if ($res['Payment']['Status'] == 1 || $res['Payment']['Status'] == 2) {
    //                return array('id' => 1, 'data' => $res);
    //            } else {
    //                return array('0' => 'Payment Failed');
    //            }
    //        } else {
    //            $message = array_key_exists(0, $res) ? $res[0]['Message'] : $res[1]['Message'];
    //            return array($message);
    //        }
    //    }
    //
    //    public function ChargeSelcom($amount = 0, $userName, $cardType, $token = null, $merchantKey, $merchantId, $payment_redirect_url = null)
    //    {
    //        $rand = uniqid();
    //        $curl = curl_init();
    //        curl_setopt_array($curl, array(
    //            CURLOPT_URL => $payment_redirect_url,
    //            CURLOPT_RETURNTRANSFER => true,
    //            CURLOPT_ENCODING => "",
    //            CURLOPT_MAXREDIRS => 10,
    //            CURLOPT_TIMEOUT => 30,
    //            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //            CURLOPT_CUSTOMREQUEST => "POST",
    //            CURLOPT_POSTFIELDS => "{  \r\n   \"MerchantOrderId\":\"$rand\",\r\n   \"Customer\":{  \r\n      \"Name\":\"$userName\"\r\n   },\r\n   \"Payment\":{  \r\n     \"Type\":\"CreditCard\",\r\n     \"Amount\":$amount,\r\n     \"Installments\":1,\r\n  \r\n     \"CreditCard\":{  \r\n         \"CardToken\":\"$token\",\r\n         \"Brand\":\"$cardType\"\r\n     }\r\n   }\r\n}",
    //            CURLOPT_HTTPHEADER => array(
    //                "content-type: application/json",
    //                "merchantid: " . $merchantId,
    //                "merchantkey: " . $merchantKey,
    //            ),
    //        ));
    //
    //        $response = curl_exec($curl);
    //        $err = curl_error($curl);
    //        curl_close($curl);
    //        $res = json_decode($response, true);
    //        if (!empty($res['Payment'])) {
    //            if ($res['Payment']['Status'] == 1) {
    //                return array('id' => 1, 'data' => $res);
    //            } else {
    //                return false;
    //            }
    //        } else {
    //            return array('result' => 0, 'message' => $res[0]['Message']);
    //        }
    //    }
    //
    //    function computeSignature($parameters, $signed_fields, $request_timestamp, $api_secret){
    //        $fields_order = explode(',', $signed_fields);
    //        $sign_data = "timestamp=$request_timestamp";
    //        foreach ($fields_order as $key) {
    //            $sign_data .= "&$key=".$parameters[$key];
    //        }
    //        //RS256 Signature Method
    //        #$private_key_pem = openssl_get_privatekey(file_get_contents("path_to_private_key_file"));
    //        #openssl_sign($sign_data, $signature, $private_key_pem, OPENSSL_ALGO_SHA256);
    //        #return base64_encode($signature);
    //
    //        //HS256 Signature Method
    //        return base64_encode(hash_hmac('sha256', $sign_data, $api_secret, true));
    //    }

//    public function BayarindAddMoney(Request $request){
//        $user = $request->user('api');
//        $user_id = $user->id;
//
//        $minimum_amount = 1;
//        $name = isset($request->name) ? $request->name : '';
//        $phone = isset($request->phone) ? $request->phone : '';
//        $amount = isset($request->amount) ? $request->amount : '';
//        $payment_option = isset($request->payment_option) ? $request->payment_option : '';
//
//        if($name == '' && $phone == ''){
//            return response()->json(['result' => 0,'message' => 'Invalid Parameters']);
//        }
//        // Convert number according to payment gateway
//        $phone = $newstring = '0'.substr($phone, -10);
//        if($amount == '' && $amount < $minimum_amount){
//            $message  = 'Amount must be grater than '.$minimum_amount.'.';
//            return response()->json(['result' => 0,'message' => $message]);
//        }
//        if($payment_option == '' && $payment_option != 'BAYARIND'){
//            return response()->json(['result' => 0,'message' => 'Invalid String']);
//        }
//
//        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
//        if(empty($paymentConfig)){
//            return response()->json(['result' => 0,'message' => 'Payment Configuration Not Found.']);
//        }
//        $base_url = 'https://staging.bayarind.id:50080/';
//        $merchantId = $paymentConfig->api_public_key;
//        $secretConnectID = $paymentConfig->api_public_key;
//        $valueMerchantAccess = hash('sha256', $merchantId.' '.$secretConnectID);
//        $bayarindTime = date('YMdHms'); // Date Time Format
//
//        // Check user is register or not, if not then register
//        $user_card = UserCard::where('user_id', $user_id)->first();
//        $user_token = '';
//        if(!empty($user_card)){
//            $user_token = $user_card->token;
//            $bayarindSignature = hash('sha256', $user_token.' '.$bayarindTime);; //sha256(X-Bayarind-User-Token + X-Bayarind-Time + <<empty_string>>)
//            $curl = curl_init();
//            curl_setopt_array($curl, array(
//                CURLOPT_PORT => "50080",
//                CURLOPT_URL => $base_url."msp/service/token/refresh",
//                CURLOPT_RETURNTRANSFER => true,
//                CURLOPT_ENCODING => "",
//                CURLOPT_MAXREDIRS => 10,
//                CURLOPT_TIMEOUT => 30,
//                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                CURLOPT_CUSTOMREQUEST => "GET",
//                CURLOPT_POSTFIELDS => "",
//                CURLOPT_HTTPHEADER => array(
//                    "Content-Type: application/json",
//                    "Postman-Token: ec9b3118-bdfa-4b0e-b30c-1f4e4d0c70d3",
//                    "X-Bayarind-Merchant: ".$merchantId,
//                    "X-Bayarind-Signature: ".$bayarindSignature,
//                    "X-Bayarind-Time: ".$bayarindTime,
//                    "X-Bayarind-User-Token: ".$user_token,
//                    "cache-control: no-cache"
//                ),
//            ));
//            $response = curl_exec($curl);
//            $err = curl_error($curl);
//            if($err != ''){
//                return response()->json(['result' => 0,'message' => 'Bayarind Refresh Token -'.$err]);
//            }
//            $response = json_decode($response,  true);
//            if(!empty($response) && ($response['error'] == null)) {
//                $user_token = $response['data']['userTokenAccess'];
//            }else{
//                return response()->json(['result' => 0,'message' => 'Bayarind Refresh Token Error.']);
//            }
//        }
//        else{
//            $curl = curl_init();
//            curl_setopt_array($curl, array(
//                CURLOPT_PORT => "50080",
//                CURLOPT_URL => $base_url."msc/service/register",
//                CURLOPT_RETURNTRANSFER => true,
//                CURLOPT_ENCODING => "",
//                CURLOPT_MAXREDIRS => 10,
//                CURLOPT_TIMEOUT => 30,
//                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                CURLOPT_CUSTOMREQUEST => "POST",
//                CURLOPT_POSTFIELDS => "{\n\t\"name\":\".$name.\",\n\t\"noHp\":\".$phone.\"\n}",
//                CURLOPT_HTTPHEADER => array(
//                    "Content-Type: application/json",
//                    "Postman-Token: 41f5b5f9-1d71-4583-9208-1acb6129e8cb",
//                    "X-Bayarind-Merchant: ".$merchantId,
//                    "X-Bayarind-Merchant-Access: ".$valueMerchantAccess,
//                    "cache-control: no-cache"
//                ),
//            ));
//            $response = curl_exec($curl);
//            $err = curl_error($curl);
//            curl_close($curl);
//            if($err != ''){
//                return response()->json(['result' => 0,'message' => 'Bayarind Registration -'.$err]);
//            }
//            $response = json_decode($response,  true);
//            if(!empty($response) && ($response['error'] == null || $response['error']['code'] == 904)){
//                $curl = curl_init();
//                curl_setopt_array($curl, array(
//                    CURLOPT_PORT => "50080",
//                    CURLOPT_URL => $base_url."msc/service/binding/".$phone,
//                    CURLOPT_RETURNTRANSFER => true,
//                    CURLOPT_ENCODING => "",
//                    CURLOPT_MAXREDIRS => 10,
//                    CURLOPT_TIMEOUT => 30,
//                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                    CURLOPT_CUSTOMREQUEST => "GET",
//                    CURLOPT_POSTFIELDS => "",
//                    CURLOPT_HTTPHEADER => array(
//                        "Content-Type: application/json",
//                        "Postman-Token: c6ea3e21-9e11-4b47-8245-c960eaf100ab",
//                        "X-Bayarind-Merchant: ".$merchantId,
//                        "X-Bayarind-Merchant-Access: ".$valueMerchantAccess,
//                        "cache-control: no-cache"
//                    ),
//                ));
//                $response = curl_exec($curl);
//                $err = curl_error($curl);
//                curl_close($curl);
//                if($err != ''){
//                    return response()->json(['result' => 0,'message' => 'Bayarind Binding '.$err]);
//                }
//                $response = json_decode($response,  true);
//                if(!empty($response) && $response['error'] == null){
//                    $user_token = isset($response['data']['userTokenAccess']) ? $response['data']['userTokenAccess'] : '';
//                }else{
//                    return response()->json(['result' => 0,'message' => 'Binding not completed.']);
//                }
//                if($user_token == ''){
//                    return response()->json(['result' => 0,'message' => 'Invalid Token.']);
//                }
//            }else{
//                return response()->json(['result' => 0,'message' => 'Registration not completed.']);
//            }
//        }
//        // Create User token.
//        $userCard = UserCard::updateOrCreate(['user_id' => $user_id, 'payment_option_id' => $paymentConfig->payment_option_id],['token' => $user_token]);
//        $user_token = $userCard->token;
//
//        $bayarindSignature = hash('sha256', $user_token.$bayarindTime);; //sha256(X-Bayarind-User-Token + X-Bayarind-Time + <<empty_string>>)
//        // Check account balance
//        $curl = curl_init();
//        curl_setopt_array($curl, array(
//            CURLOPT_PORT => "50080",
//            CURLOPT_URL => $base_url."msp/service/detail/account",
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_ENCODING => "",
//            CURLOPT_MAXREDIRS => 10,
//            CURLOPT_TIMEOUT => 30,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//            CURLOPT_CUSTOMREQUEST => "GET",
//            CURLOPT_POSTFIELDS => "",
//            CURLOPT_HTTPHEADER => array(
//                "Content-Type: application/json",
//                "Postman-Token: 0b62a715-66ad-4493-885a-7f9c28ef1b2c",
//                "X-Bayarind-Merchant: ".$merchantId,
//                "X-Bayarind-Signature: ".$bayarindSignature,
//                "X-Bayarind-Time: ".$bayarindTime,
//                "X-Bayarind-User-Token: ".$user_token,
//                "cache-control: no-cache"
//            ),
//        ));
//        $response = curl_exec($curl);
//        $err = curl_error($curl);
//        curl_close($curl);
//        if($err != ''){
//            return response()->json(['result' => 0,'message' => 'Bayarind User -'.$err]);
//        }
//        $response = json_decode($response,  true);
//        $userAccountBalance = 0;
//        $userAccountLimit = 0;
//        if(!empty($response) && ($response['error'] == null)) {
//            $userAccountBalance = isset($response['data']['virtualAccounts']) ? $response['data']['virtualAccounts']['balance'] : '';
//            $userAccountLimit = isset($response['data']['limit']) ? $response['data']['virtualAccounts']['limit'] : '';
//        }else{
//            return response()->json(['result' => 0,'message' => 'Bayarind User Not Found.']);
//        }
//        if($amount > $userAccountLimit){
//            return response()->json(['result' => 0,'message' => 'Bayarind - User Account Limit Exceed.']);
//        }
//        if($amount > $userAccountBalance){
//            return response()->json(['result' => 0,'message' => 'Bayarind - You have Insufficient Balance.']);
//        }
//        $storeName = "CAR";
//        $merchantTransactionNumber = "TRX1234533";
//        $paymentOptionName = "WALLET";
//        $transactionType = "TRANSPORTATION";
//        $totalAmount = "15000";
//        $destinationAccount = "08977294471";
//
//        // Bayarind Transaction
//        $curl = curl_init();
//        curl_setopt_array($curl, array(
//            CURLOPT_PORT => "50080",
//            CURLOPT_URL => $base_url . "msp/trx/payment",
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_ENCODING => "",
//            CURLOPT_MAXREDIRS => 10,
//            CURLOPT_TIMEOUT => 30,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//            CURLOPT_CUSTOMREQUEST => "POST",
//            CURLOPT_POSTFIELDS => "{\n    \"storeName\": \".$storeName.\",\n    \"merchantTransactionNumber\": \".$merchantTransactionNumber.\",\n    \"paymentOptionName\": \".$paymentOptionName.\",\n    \"transactionType\": \".$transactionType.\",\n    \"totalAmount\": \".$totalAmount.\",\n    \"destinationAccount\": \".$destinationAccount.\"\n}",
//            CURLOPT_HTTPHEADER => array(
//                "Content-Type: application/json",
//                "Postman-Token: 0f7cbbea-7ed2-4d90-be95-a879b7ed4403",
//                "X-Bayarind-Merchant: 4",
//                "X-Bayarind-Signature: " . $bayarindSignature,
//                "X-Bayarind-Time: " . $bayarindTime,
//                "X-Bayarind-User-Token: " . $user_token,
//                "cache-control: no-cache"
//            ),
//        ));
//        $response = curl_exec($curl);
//        $err = curl_error($curl);
//        if ($err != '') {
//            return response()->json(['result' => 0, 'message' => 'Bayarind Payment -' . $err]);
//        }
//        $response = json_decode($response, true);
//        if (!empty($response) && ($response['error'] == null)) {
//            return response()->json(['result' => 1, 'message' => 'Payment Done Successfully']);
//        }else{
//            $err_message = $response['error']['message'];
//            return response()->json(['result' => 0, 'message' => 'Bayarind Payment -' . $err_message]);
//        }
//    }

    // This code has been moved in controller class
    // public function PagaditoPayment(Request $request)
    // {
    //     $paymentOption = PaymentOptionsConfiguration::where('merchant_id', $request->merchant_id)->latest()->first();
    //     if (!empty($paymentOption)) {
    //         session_start();
    //         $_SESSION['paymentConfig'] = $paymentOption;
    //         $sandbox = $paymentOption->gateway_condition == 1 ? false : true;
    //         define("UID", $paymentOption->api_secret_key);
    //         define("WSK", $paymentOption->auth_token);
    //         define("SANDBOX", $sandbox);

    //         if (isset($request->price) && is_numeric($request->price)) {
    //             $Pagadito = new Pagadito(UID, WSK);
    //             if (SANDBOX) {
    //                 $Pagadito->mode_sandbox_on();
    //             }
    //             if ($Pagadito->connect()) {
    //                 $Pagadito->change_currency_gtq();
    //                 if ($request->price > 0) {
    //                     $Pagadito->add_detail(1, 'GuaTaxi Payment', $request->price);
    //                 }
    //                 $Pagadito->enable_pending_payments();
    //                 $ern = rand(1000, 2000);
    //                 if (!$Pagadito->exec_trans($ern)) {
    //                     switch ($Pagadito->get_rs_code()) {
    //                         case "PG2001":
    //                             /*Incomplete data*/
    //                         case "PG3002":
    //                             /*Error*/
    //                         case "PG3003":
    //                             /*Unregistered transaction*/
    //                         case "PG3004":
    //                             /*Match error*/
    //                         case "PG3005":
    //                             /*Disabled connection*/
    //                         default:
    //                             $msgPrincipal = "Respuesta de Pagadito API";
    //                             $msgSecundario = "COD: " . $Pagadito->get_rs_code() . ", MSG: " . $Pagadito->get_rs_message();
    //                             return response()->json(['result' => 0, 'message' => $msgPrincipal, 'data' => $msgSecundario]);
    //                             break;
    //                     }
    //                 }
    //             } else {
    //                 switch ($Pagadito->get_rs_code()) {
    //                     case "PG2001":
    //                         /*Incomplete data*/
    //                     case "PG3001":
    //                         /*Problem connection*/
    //                     case "PG3002":
    //                         /*Error*/
    //                     case "PG3003":
    //                         /*Unregistered transaction*/
    //                     case "PG3005":
    //                         /*Disabled connection*/
    //                     case "PG3006":
    //                         /*Exceeded*/
    //                     default:
    //                         $msgPrincipal = "Respuesta de Pagadito API";
    //                         $msgSecundario = "COD: " . $Pagadito->get_rs_code() . ", MSG: " . $Pagadito->get_rs_message();
    //                         return response()->json(['result' => 0, 'message' => $msgPrincipal, 'data' => $msgSecundario]);
    //                         break;
    //                 }
    //             }
    //         } else {
    //             $msgPrincipal = "Atenci&oacute;n";
    //             $msgSecundario = "No ha llenado los campos adecuadamente";
    //             return response()->json(['result' => 0, 'message' => $msgPrincipal, 'data' => $msgSecundario]);
    //         }
    //     } else {
    //         return response()->json(['result' => 0, 'message' => trans('api.payment_config'), 'data' => []]);
    //     }
    // }

    // public function PagaditoPayback(Request $request)
    // {
    //     $result = 0;
    //     $msgPrincipal = '';
    //     $msgSecundario = '';
    //     session_start();
    //     $paymentOption = $_SESSION['paymentConfig'];
    //     if (!empty($paymentOption)) {
    //         $sandbox = $paymentOption->gateway_condition == 1 ? false : true;
    //         define("UID", $paymentOption->api_secret_key);
    //         define("WSK", $paymentOption->auth_token);
    //         define("SANDBOX", $sandbox);

    //         if (isset($request->value) && $request->value != '') {
    //             $Pagadito = new Pagadito(UID, WSK);
    //             if (SANDBOX) {
    //                 $Pagadito->mode_sandbox_on();
    //             }
    //             if ($Pagadito->connect()) {
    //                 if ($Pagadito->get_status($request->value)) {
    //                     switch ($Pagadito->get_rs_status()) {
    //                         case "COMPLETED":
    //                             $result = 1;
    //                             $msgPrincipal = "Su compra fue exitosa";
    //                             $msgSecundario = 'Gracias por comprar con Pagadito. NAP(N&uacute;mero de Aprobaci&oacute;n Pagadito): ' . $Pagadito->get_rs_reference();
    //                             break;

    //                         case "REGISTERED":
    //                             $msgPrincipal = "Atenci&oacute;n";
    //                             $msgSecundario = "La transacci&oacute;n fue cancelada";
    //                             break;

    //                         case "VERIFYING":
    //                             $msgPrincipal = "Atenci&oacute;n";
    //                             $msgSecundario = 'Su pago est&aacute; en validaci&oacute;n. NAP(N&uacute;mero de Aprobaci&oacute;n Pagadito): ' . $Pagadito->get_rs_reference();
    //                             break;

    //                         case "REVOKED":
    //                             $msgPrincipal = "Atenci&oacute;n";
    //                             $msgSecundario = "La transacci&oacute;n fue denegada";
    //                             break;

    //                         case "FAILED":
    //                             $msgPrincipal = "Atenci&oacute;n";
    //                             $msgSecundario = "Tratamiento para una transacción fallida.";
    //                             break;
    //                         default:
    //                             $msgPrincipal = "Atenci&oacute;n";
    //                             $msgSecundario = "La transacci&oacute;n no fue realizada.";
    //                             break;
    //                     }
    //                 } else {
    //                     switch ($Pagadito->get_rs_code()) {
    //                         case "PG2001":
    //                             /*Incomplete data*/
    //                         case "PG3002":
    //                             /*Error*/
    //                         case "PG3003":
    //                             /*Unregistered transaction*/
    //                         default:
    //                             $msgPrincipal = "Error en la transacci&oacute;n";
    //                             $msgSecundario = "La transacci&oacute;n no fue completada.";
    //                             break;
    //                     }
    //                 }
    //             } else {
    //                 switch ($Pagadito->get_rs_code()) {
    //                     case "PG2001":
    //                         /*Incomplete data*/
    //                     case "PG3001":
    //                         /*Problem connection*/
    //                     case "PG3002":
    //                         /*Error*/
    //                     case "PG3003":
    //                         /*Unregistered transaction*/
    //                     case "PG3005":
    //                         /*Disabled connection*/
    //                     case "PG3006":
    //                         /*Exceeded*/
    //                     default:
    //                         $msgPrincipal = "Respuesta de Pagadito API";
    //                         $msgSecundario = "COD: " . $Pagadito->get_rs_code() . ", MSG: " . $Pagadito->get_rs_message();
    //                         break;
    //                 }
    //             }
    //         } else {
    //             $msgPrincipal = "Atenci&oacute;n";
    //             $msgSecundario = "No se recibieron los datos correctamente. La transacci&oacute;n no fue completada.";
    //         }
    //     }
    //     session_destroy();
    //     return response()->json(['result' => $result, 'message' => $msgPrincipal, 'data' => array('return_msg' => $msgSecundario)]);
    // }

    public function SyberpayGetUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required|exists:merchants,id',
            'amount' => 'required|numeric',
            'payment_option' => 'required',
            'type' => ['required', Rule::In([1, 2])],
            'user_id' => 'required_if:type,1',
            'driver_id' => 'required_if:type,2',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        // $paymentOption = PaymentOptionsConfiguration::where('merchant_id',$request->merchant_id)->latest()->first();
        // if (!empty($paymentOption)){
        $headers = array('Content-Type: application/json');
        $syberpayURL = 'https://syberpay.test.sybertechnology.com/syberpay/getUrl';
        /** Uncomment this to test for production */
        // $syberpayURL = 'https://syberpay.sybertechnology.com/syberpay/getUrl';
        $applicationId = '0000000128';
        $serviceId = '000010020125';
        $key = 'y5lgm6rxq';
        $salt = 'l3emxga9b';

        $currencyDesc = 'SDG';
        $orderId = $this->getUniqueSyberPayOrderID($request->merchant_id);
        $totalAmount = $request->amount;
        $customerName = "Moe Ezzo";
        $HashedData = hash('sha256', $key . '|' . $applicationId . '|' . $serviceId . '|' . $totalAmount . '|' . $currencyDesc . '|' . $orderId . '|' . $salt);
        //  Payment info here
        $paymentInfo = array('orderNo' => $orderId, 'customerName' => $customerName);
        // PHP Array contain all request body parameters
        $jsonDataArray = array(
            'applicationId' => $applicationId,
            'serviceId' => $serviceId,
            'customerRef' => $orderId,
            'amount' => $totalAmount,
            'currency' => $currencyDesc,
            'paymentInfo' => $paymentInfo,
            'hash' => $HashedData
        );
        // Convert PHP array into JSON object
        $jsonData = json_encode($jsonDataArray);
        // Using CURL to send post request
        $ch = curl_init();
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $syberpayURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        // Execute post
        $result = curl_exec($ch);
        curl_close($ch);
        // Parse JSON reponse into PHP array
        $result_array = json_decode($result, true);
        // Get SyberPay url from result array
        $paymentUrl = isset($result_array['paymentUrl']) ? $result_array['paymentUrl'] : '';
        if ($request->type == 1) {
            DB::table('syber_payment_transaction')->insert(
                ['merchant_id' => $request->merchant_id, 'user_id' => 1, 'order_id' => $orderId, 'amount' => $totalAmount, 'type' => $request->type]
            );
        } elseif ($request->type == 2) {
            DB::table('syber_payment_transaction')->insert(
                ['merchant_id' => $request->merchant_id, 'driver_id' => 1, 'order_id' => $orderId, 'amount' => $totalAmount, 'type' => $request->type]
            );
        }
        return response()->json(['result' => 1, 'message' => 'success', 'payment_url' => $paymentUrl]);
        // }else{
        //     return response()->json(['result' => 0, 'message' => trans('api.payment_config'),'data' => []]);
        // }
    }

    protected function getUniqueSyberPayOrderID($merchant_id, $length = 10)
    {
        $key_generate = substr(str_shuffle("1234567890"), 0, $length);
        if (DB::table('syber_payment_transaction')->where([['order_id', '=', $key_generate], ['merchant_id', '=', $merchant_id]])->exists()) :
            $this->getUniqueSyberPayOrderID($merchant_id);
        endif;
        return $key_generate;
    }

    public function SyberpayPaymentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transactionId' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        // $paymentOption = PaymentOptionsConfiguration::where('merchant_id',$request->merchant_id)->latest()->first();
        // if (!empty($paymentOption)){
        $headers = array('Content-Type: application/json');
        $syberpayURL = 'https://syberpay.test.sybertechnology.com/syberpay/payment_status';
        $transactionId = $request->transactionId;
        $applicationId = '0000000128';
        $serviceId = '000010020125';
        $key = 'y5lgm6rxq';
        $salt = 'l3emxga9b';

        $HashedData = hash('sha256', $key . '|' . $applicationId . '|' . $transactionId . '|' . $salt);
        // PHP Array contain all request body parameters
        $jsonDataArray = array(
            'applicationId' => $applicationId,
            'transactionId' => $transactionId,
            'hash' => $HashedData,
        );
        // Convert PHP array into JSON object
        $jsonData = json_encode($jsonDataArray);
        // Using CURL to send post request
        $ch = curl_init();
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $syberpayURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        // Execute post
        $result = curl_exec($ch);
        curl_close($ch);
        // Parse JSON reponse into PHP array
        $result_array = json_decode($result, true);

        $order_id = '';
        if (isset($result_array['status']) && $result_array['status'] == 'Successful') {
            $order_id = isset($result_array['payment']['customerRef']) ? $result_array['payment']['customerRef'] : '';
        }
        if ($order_id != '') {
            DB::table('syber_payment_transaction')
                ->where('order_id', $order_id)
                ->update(['payment_status' => $result_array['status'], 'api_response' => $result, 'request_data' => json_encode($request->all())]);
        }
        // You can also print_r or var_dump for the reponse
        return response()->json(['result' => 1, 'message' => 'success', 'data' => $result_array]);
        // }else{
        //     return response()->json(['result' => 0, 'message' => trans('api.payment_config'),'data' => []]);
        // }
    }

    public function SyberpayRedirectUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customerRef' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $payment_details = DB::table('syber_payment_transaction')->where('order_id', $request->customerRef)->first();
        if (!empty($payment_details)) {
            return response()->json(['result' => 0, 'message' => 'Payment Response ' . $payment_details->payment_status]);
        } else {
            return response()->json(['result' => 1, 'message' => 'Payment Details Not Found']);
        }
    }

    public function MonerisVaultAddCard($user_id, $userPhone, $userEmail, $card_number, $expireDate, $store_id, $api_token)
    {
        $type = 'res_add_cc';
        $cust_id = $user_id;
        $phone = $userPhone;
        $email = $userEmail;
        $note = 'Save Card';
        $pan = $card_number;
        $expiry_date = $expireDate;
        $crypt_type = '1';
        $avs_street_number = '';
        $avs_street_name = '';
        $avs_zipcode = '';

        $txnArray = array(
            'type' => $type,
            'cust_id' => $cust_id,
            'phone' => $phone,
            'email' => $email,
            'note' => $note,
            'pan' => $pan,
            'expdate' => $expiry_date,
            'crypt_type' => $crypt_type
        );

        $avsTemplate = array(
            'avs_street_number' => $avs_street_number,
            'avs_street_name' => $avs_street_name,
            'avs_zipcode' => $avs_zipcode
        );

        $mpgAvsInfo = new mpgAvsInfo($avsTemplate);
        $cof = new CofInfo();
        $cof->setIssuerId("139X3130ASCXAS9");
        $mpgTxn = new mpgTransaction($txnArray);
        $mpgTxn->setAvsInfo($mpgAvsInfo);
        $mpgTxn->setCofInfo($cof);
        $mpgRequest = new mpgRequest($mpgTxn);
        $mpgRequest->setProcCountryCode("CA"); //"US" for sending transaction to US environment
        $mpgRequest->setTestMode(false); //false or comment out this line for production transactions
        $mpgHttpPost = new mpgHttpsPost($store_id, $api_token, $mpgRequest);
        $mpgResponse = $mpgHttpPost->getMpgResponse();
        $response = $mpgResponse->responseData;
        return $response;
    }

    public function MonerisViewCard($card_token, $store_id, $api_token)
    {
        $type = 'res_lookup_full';    //will only return the masked card number
        $txnArray = array('type' => $type,
            'data_key' => $card_token
        );

        $mpgTxn = new mpgTransaction($txnArray);
        $mpgRequest = new mpgRequest($mpgTxn);
        $mpgRequest->setProcCountryCode("CA"); //"US" for sending transaction to US environment
        $mpgRequest->setTestMode(false); //false or comment out this line for production transactions

        $mpgHttpPost = new mpgHttpsPost($store_id, $api_token, $mpgRequest);
        $mpgResponse = $mpgHttpPost->getMpgResponse();
        $response = $mpgResponse->responseData;
        $cardDetails = $mpgResponse->resolveData;
        return array('api_response' => $response, 'card_details' => $cardDetails);
    }

    public function MonerisMakePayment($user_id, $card_token, $amount, $store_id, $api_token)
    {
        $orderid = 'res-purch-' . date("dmy-G:i:s");
        $custid = $user_id;
        $crypt_type = '1';

        $txnArray = array(
            'type' => 'res_purchase_cc',
            'data_key' => $card_token,
            'order_id' => $orderid,
            'cust_id' => $custid,
            'amount' => $amount,
            'crypt_type' => $crypt_type
        );

        $mpgTxn = new mpgTransaction($txnArray);
        $cof = new CofInfo();
        $cof->setPaymentIndicator("U");
        $cof->setPaymentInformation("2");
        $cof->setIssuerId("168451306048014");
        $mpgTxn->setCofInfo($cof);
        $mpgRequest = new mpgRequest($mpgTxn);
        $mpgRequest->setProcCountryCode("CA"); //"US" for sending transaction to US environment
        $mpgRequest->setTestMode(false); //false or comment out this line for production transactions
        $mpgHttpPost = new mpgHttpsPost($store_id, $api_token, $mpgRequest);
        $mpgResponse = $mpgHttpPost->getMpgResponse();
        $response = $mpgResponse->responseData;
        return $response;
    }

    public function MonerisDeleteCard($store_id, $api_token, $card_token)
    {
        $type = 'res_delete';
        $txnArray = array(
            'type' => $type,
            'data_key' => $card_token
        );

        $mpgTxn = new mpgTransaction($txnArray);
        $mpgRequest = new mpgRequest($mpgTxn);
        $mpgRequest->setProcCountryCode("CA"); //"US" for sending transaction to US environment
        $mpgRequest->setTestMode(false); //false or comment out this line for production transactions
        $mpgHttpPost = new mpgHttpsPost($store_id, $api_token, $mpgRequest);
        $mpgResponse = $mpgHttpPost->getMpgResponse();
        $response = $mpgResponse->responseData;
        return $response;
    }

    public function ImepayRecording(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'TokenId' => 'required',
            'Amount' => 'required',
            'ReferenceId' => 'required',
            'MerchantCode' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['ResponseCode' => "1", 'error' => $errors[0]]);
        }
        try {
            $existRecord = DB::table('imepay_payment_transaction')->where('token_id', $request->TokenId)->get();
            if ($existRecord->count() > 0) {
                return response()->json(['ResponseCode' => "0", 'message' => $request->ReferenceId, 'ResponseDescription' => "Payment Request Already Recorded"]);
            } else {
                DB::table('imepay_payment_transaction')->insert(
                    ['token_id' => $request->TokenId, 'amount' => $request->Amount, 'reference_id' => $request->ReferenceId, 'merchant_code' => $request->MerchantCode]
                );
                return response()->json(['ResponseCode' => "0", 'ReferenceId' => $request->ReferenceId, 'ResponseDescription' => "Payment Request Recorded"]);
            }
        } catch (Exception $e) {
            return response()->json(['ResponseCode' => "1", 'error' => $e->getMessage()]);
        }
    }

    public function UbpayGetUrl(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'payment_gateway' => [
                'required',
                Rule::in(['UBPAY']),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $merchant_id = $user->merchant_id;
        try {
            $merchantId = 333;
            $key_generate = $this->getUniqueUbPayRef($merchant_id);
            $json_parameter = array(
                "merchantId" => $merchantId,
                "description" => "Make Payment",
                "language" => "EN",
                "merchantRef" => $key_generate,
                "currency" => "USD",
                "amount" => $request->amount,
                "successUrl" => "https://delhi.apporiotaxi.com/Apporiov20/public/api/ubpay/callback/$key_generate/success",
                "failedUrl" => "https://delhi.apporiotaxi.com/Apporiov20/public/api/ubpay/callback/$key_generate/fail",
                "cancelledUrl" => "https://delhi.apporiotaxi.com/Apporiov20/public/api/ubpay/callback/$key_generate/cancelled",
                "redirectUrl" => "https://delhi.apporiotaxi.com/Apporiov20/public/api/ubpay/callback/$key_generate/redirect"
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://apps.ub-pay.net/merchantController/requestPayment",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($json_parameter, true),
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return response()->json(['result' => "0", 'result' => $err]);
            } else {
                $response = json_decode($response, true);
                if ($response['url'] != null) {
                    $payment_url = $response['url'];
                } else {
                    return response()->json(['result' => "0", 'error' => 'Url Not Found']);
                }
            }
            DB::table('ubpay_payment_transactions')->insert(
                ['user_id' => $user->id, 'merchant_id' => $user->merchant_id, 'amount' => $request->amount, 'paymentgateway_ref' => $key_generate, 'paymentgateway_id' => $merchantId]
            );
        } catch (Exception $e) {
            return response()->json(['result' => "0", 'error' => $e->getMessage()]);
        }
        return response()->json(['result' => "1", 'payment_url' => $payment_url]);
    }

    protected function getUniqueUbPayRef($merchant_id, $length = 10)
    {
        $key_generate = substr(str_shuffle("1234567890"), 0, $length);
        if (DB::table('ubpay_payment_transactions')->where([['paymentgateway_ref', '=', $key_generate], ['merchant_id', '=', $merchant_id]])->exists()):
            $this->getUniqueUbPayRef($merchant_id);
        endif;
        return $key_generate;
    }

    public function UbpayCallback($merchantRef, $status)
    {
        try {
            $existRecord = DB::table('ubpay_payment_transactions')->where('paymentgateway_ref', $merchantRef)->get();
            if ($existRecord->count() > 0) {
                $existRecord->payment_status = $status;
                $existRecord->save();
                return response()->json(['result' => "1", 'status' => $status, 'merchantRef' => $merchantRef]);
            }
        } catch (Exception $e) {
            return response()->json(['result' => "1", 'error' => $e->getMessage()]);
        }
        return response()->json(['result' => "0", 'status' => $status, 'merchantRef' => $merchantRef]);
    }

    public function EZYPODMakePayment($apiKey, $loginID, $paymentMode, $payArr)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $paymentMode);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $headers = array("mobiApiKey:" . $apiKey, "loginId:" . $loginID);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payArr));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $server_output = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($server_output, true);
            if (isset($result['responseMessage']) && $result['responseMessage'] == 'SUCCESSFUL') {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function razerpayTransaction(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
            'payment_gateway' => [
                'required',
                Rule::in(['RAZERPAY']),
            ],
            'type' => 'required',
            'user_id' => 'required',
            'amount' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        try {
            if ($request->type == 1) {
                $user = User::find($request->user_id);
            } elseif ($request->type == 2) {
                $user = Driver::find($request->user_id);
            }
            DB::table('razerpay_transactions')->insert([
                'merchant_id' => $user->merchant_id,
                'user_id' => $user->id,
                'type' => $request->type,
                'transaction_id' => $request->transaction_id,
                'amount' => $request->amount,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            return response()->json(['result' => "0", 'message' => $e->getMessage()]);
        }
        return response()->json(['result' => "1", 'message' => 'Transaction Saved Successfully']);
    }

    public function RazerpayCallback(Request $request)
    {
        dd($request->all());
        try {
            $vkey = "516d4535c0a9c4474e03a54d2a870df8";
            $nbcb = $request->nbcb;
            $tranID = $request->tranID;
            $orderid = $request->orderid;
            $status = $request->status;
            $domain = $request->domain;
            $amount = $request->amount;
            $currency = $request->currency;
            $appcode = $request->appcode;
            $paydate = $request->paydate;
            $skey = $request->skey;

            /***********************************************************
             * To verify the data integrity sending by PG
             ************************************************************/
            $key0 = md5($tranID . $orderid . $status . $domain . $amount . $currency);
            $key1 = md5($paydate . $domain . $key0 . $appcode . $vkey);
            if ($skey != $key1) {
                $status = -1; // Invalid Transaction
            }
            $trans = DB::table('razerpay_transactions')->where('transaction_id', $tranID)->first();
            if ($status == "00" && !empty($trans)) {
                if ($trans->type == 1 && $trans->payment_status == '') { // If payment Status Not Updated
                    $user = User::find($trans->user_id);
                    if (isset($user->CountryArea) && in_array($user->CountryArea->timezone, DateTimeZone::listIdentifiers())) {
//                        date_default_timezone_set($user->CountryArea->timezone);
                    }
                    $transaction = DB::table('razerpay_transactions')
                        ->where('transaction_id', $request->tranID)
                        ->update([
                            'request_parameters' => json_encode($request->all()),
                            'payment_status' => 'success',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    $paramArray = array(
                        'user_id' => $user->id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => "RAZERPAY",
                    );
                    WalletTransaction::UserWalletCredit($paramArray);
                    //                    CommonController::UserWalletCredit($user->id,null,$amount,2,2,2,"RAZERPAY");
                    //                    $wallet = $user->wallet_balance;
                    //                    $user->wallet_balance = sprintf("%0.2f", $wallet + $amount);
                    //                    $user->save();
                    //                    $money = UserWalletTransaction::create([
                    //                        'merchant_id' => $user->merchant_id,
                    //                        'user_id' => $user->id,
                    //                        'platfrom' => 2,
                    //                        'amount' => $amount,
                    //                        'receipt_number' => "RAZERPAY",
                    //                        'type' => 1,
                    //                    ]);
                    ////                    $userdevices = UserDevice::where([['user_id', '=', $user->id]])->get();
                    ////                    $playerids = array_pluck($userdevices, 'player_id');
                    //                    $message = trans('api.money');
                    //                    $data = ['message' => $message];
                    //                    Onesignal::UserPushMessage($user->id, $data, $message, 3, $user->merchant_id);
                } else if ($trans->type == 2 && $trans->payment_status == '') { // If payment Status Not Updated
                    $driver = Driver::find($trans->user_id);
                    if (isset($driver->CountryArea) && in_array($driver->CountryArea->timezone, DateTimeZone::listIdentifiers())) {
//                        date_default_timezone_set($driver->CountryArea->timezone);
                    }
                    $transaction = DB::table('razerpay_transactions')
                        ->where('transaction_id', $request->tranID)
                        ->update([
                            'request_parameters' => json_encode($request->all()),
                            'payment_status' => 'success',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    $paramArray = array(
                        'driver_id' => $driver->id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => "RAZERPAY",
                    );
                    WalletTransaction::WalletCredit($paramArray);
                    //                    CommonController::WalletCredit($driver->id,null,$amount,2,2,2,"RAZERPAY");

                    //                    $money = $driver->wallet_money;
                    //                    $driver->wallet_money = sprintf("%0.2f", $money + $amount);
                    //                    $driver->save();
                    //                    $money = DriverWalletTransaction::create([
                    //                        'merchant_id' => $driver->merchant_id,
                    //                        'driver_id' => $driver->id,
                    //                        'transaction_type' => 1,
                    //                        'payment_method' => '2',
                    //                        'receipt_number' => 'RAZERPAY',
                    //                        'amount' => sprintf("%0.2f", $amount),
                    //                        'platform' => 2,
                    //                        'description' => 'RAZERPAY',
                    //                        'narration' => 2,
                    //                    ]);
                    //                    $playerids = array($driver->player_id);
                    //                    $message = trans('api.money');
                    //                    $data = ['message' => $message];
                    //                    Onesignal::DriverPushMessage($playerids, $data, $message, 3, $merchant_id);
                }
            } else {
                $transaction = DB::table('razerpay_transactions')
                    ->where('transaction_id', $request->tranID)
                    ->update([
                        'request_parameters' => json_encode($request->all()),
                        'payment_status' => 'failed',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                if ($trans->type == 1) {
                    $user = User::find($trans->user_id);
                    //                    $playerids = array_pluck($userdevices, 'player_id');
                    $message = trans('api.message163') . ' ' . trans('api.transaction_id') . ' ' . $tranID;
                    $data = ['message' => $message];
                    Onesignal::UserPushMessage($user->id, $data, $message, 3, $user->merchant_id);

                } else if ($trans->type == 2) {
                    $driver = Driver::find($trans->user_id);
                    $playerids = array($driver->player_id);
                    $message = trans('api.message163') . ' ' . trans('api.transaction_id') . ' ' . $tranID;
                    $data = ['message' => $message];
                    Onesignal::DriverPushMessage($playerids, $data, $message, 3, $driver->merchant_id);
                }
            }
            if ($nbcb == 1) {
                //callback IPN feedback to notified PG
                echo "CBTOKEN:MPSTATOK​";
                exit;
            }
        } catch (Exception $e) {
//            print_r($e->getMessage());
        }
    }

    public function razerpayUserLog(Request $request)
    {
        $user = $request->user('api');
        $currency = $user->Country->isoCode;
        $transactions = [];
        try {
            $payment_transactions = DB::table('razerpay_transactions')->where(['user_id' => $user->id, 'type' => 1])->get();
            if (!empty($payment_transactions)) {
                foreach ($payment_transactions as $payment_transaction) {
                    $payment_status = trans('api.payment_pending');
                    $payment_status_code = 0;
                    if ($payment_transaction->payment_status == 'success') {
                        $payment_status = trans('api.payment_success');
                        $payment_status_code = 1;
                    } else if ($payment_transaction->payment_status == 'failed') {
                        $payment_status = trans('api.payment_failed');
                        $payment_status_code = 2;
                    }
                    $created_at = new DateTime($payment_transaction->created_at);
                    array_push($transactions, array(
                        'id' => $payment_transaction->id,
                        'transaction_id' => $payment_transaction->transaction_id,
                        'amount' => $currency . ' ' . $payment_transaction->amount,
                        'payment_status' => $payment_status,
                        'payment_status_code' => $payment_status_code,
                        'created_at' => $created_at->format('M d, Y | H:i:s')
                    ));
                }
            }
        } catch (Exception $e) {
            return response()->json(['result' => "0", 'message' => $e->getMessage()]);
        }
        return response()->json(['result' => "1", 'message' => 'success', 'data' => $transactions]);
    }

    public function razerpayDriverLog(Request $request)
    {
        $driver = $request->user('api-driver');
        $currency = $driver->CountryArea->Country->isoCode;
        $transactions = [];
        try {
            $payment_transactions = DB::table('razerpay_transactions')->where(['user_id' => $driver->id, 'type' => 2])->get();
            if (!empty($payment_transactions)) {
                foreach ($payment_transactions as $payment_transaction) {
                    $payment_status = trans('api.payment_pending');
                    $payment_status_code = 0;
                    if ($payment_transaction->payment_status == 'success') {
                        $payment_status = trans('api.payment_success');
                        $payment_status_code = 1;
                    } else if ($payment_transaction->payment_status == 'failed') {
                        $payment_status = trans('api.payment_failed');
                        $payment_status_code = 2;
                    }
                    $created_at = new DateTime($payment_transaction->created_at);
                    array_push($transactions, array(
                        'id' => $payment_transaction->id,
                        'transaction_id' => $payment_transaction->transaction_id,
                        'amount' => $currency . ' ' . $payment_transaction->amount,
                        'payment_status' => $payment_status,
                        'payment_status_code' => $payment_status_code,
                        'created_at' => $created_at->format('M d, Y | H:i:s')
                    ));
                }
            }
        } catch (Exception $e) {
            return response()->json(['result' => "0", 'message' => $e->getMessage()]);
        }
        return response()->json(['result' => "1", 'message' => 'success', 'data' => $transactions]);
    }

    public function SENANGPAYTokenGenerate($token_url, $auth_token, $api_secret_key, $card)
    {
        $name = $card['name'];
        $email = $card['email'];
        $phone = $card['phone'];
        $cc_number = $card['cc_number'];
        $cc_exp = $card['cc_exp'];
        $cc_cvv = $card['cc_cvv'];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $token_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"name\"\r\n\r\n$name\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"email\"\r\n\r\n$email\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"phone\"\r\n\r\n$phone\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"cc_number\"\r\n\r\n$cc_number\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"cc_exp\"\r\n\r\n$cc_exp\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"cc_cvv\"\r\n\r\n$cc_cvv\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . $auth_token,
                "Username: " . $auth_token,
                "Password: ",
                "cache-control: no-cache",
                "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return array("result" => "error", "data" => "cURL Error #:" . $err);
        }
        return array("result" => "success", "data" => $response);
    }

    public function SENANGPAYMakePayment($payment_redirect_url, $auth_token, $name, $email, $phone, $order_id, $amount, $card_token, $detail)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $payment_redirect_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"name\"\r\n\r\n$name\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"email\"\r\n\r\n$email\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"detail\"\r\n\r\n$detail\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"phone\"\r\n\r\n$phone\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"order_id\"\r\n\r\n$order_id\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"amount\"\r\n\r\n$amount\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"token\"\r\n\r\n$card_token\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . $auth_token,
                "cache-control: no-cache",
                "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return array("result" => "error", "data" => "cURL Error #:" . $err);
        }
        return array("result" => "success", "data" => $response);
    }

    public function TwoCTwoPStoreTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'amount' => 'required',
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            error_response($errors[0]);
        }
        if ($request->type == 1) {
            $user = $request->user('api');
        } elseif ($request->type == 2) {
            $user = $request->user('api-driver');
        } else {
            error_response("Invalid Type");
        }
        try {
            $existRecord = DB::table('twoctwop_transactions')->where('order_id', $request->order_id)->get();
            if ($existRecord->count() > 0) {
                error_response("Payment Request Already Recorded");
            } else {
                DB::table('twoctwop_transactions')->insert(
                    ['order_id' => $request->order_id, 'amount' => $request->amount, 'merchant_id' => $user->merchant_id, 'user_id' => $user->id, 'type' => $request->type]
                );
                $token = '';
                $mid = "104104000000329"; //Get MerchantID when opening account with 2C2P
                $secret_key = "3A1459F7DACBB42C6E5AF20BC198137D2C682596AE9DFDD4F19AD304A26A76CE"; //Get SecretKey from 2C2P PGW dashboard
                $desc = "Taxi Payment";
                $amount = str_pad($request->amount, 12, '0', STR_PAD_LEFT);
                $invoice_no = $request->order_id;
                $tokenGenerate = new TwoCTwoPPaymentGateway($mid, $secret_key);
                $token = $tokenGenerate->GenerateToken($amount, $desc, $invoice_no);
                if (!empty($token)) {
                    return array("result" => "1", "message" => "Payment Request Recorded", "data" => $token);
                } else {
                    return array("result" => "0", "message" => "Failed To Generate Token", "data" => $token);
                }

            }
        } catch (Exception $e) {
            error_response($e->getMessage());
        }
    }

    public function TwoCTwoPReturn(Request $request)
    {
        $transaction = DB::table('twoctwop_transactions')
            ->where('order_id', '20200121190128')
            ->update([
                'request_parameters' => json_encode($request->all()),
                'payment_status' => 'success',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        try {
            $mid = "104104000000329"; //Get MerchantID when opening account with 2C2P
            $secret_key = "3A1459F7DACBB42C6E5AF20BC198137D2C682596AE9DFDD4F19AD304A26A76CE"; //Get SecretKey from 2C2P PGW dashboard
            $tokenGenerate = new TwoCTwoPPaymentGateway($mid, $secret_key);
            $payment_status = $tokenGenerate->checkPaymentStatus($request);
            if (isset($payment_status['result']) && $payment_status['result'] == 1) {
                if ($payment_status['resp_code'] != 000) {
                    echo "Payment Failed : " . $payment_status['invoice_no'];
                    exit;
                }
                $trans = DB::table('twoctwop_transactions')->where('order_id', $payment_status['invoice_no'])->first();
                if ($trans->type == 1 && $trans->payment_status == '') { // If payment Status Not Updated
                    $user = User::find($trans->user_id);
                    if (isset($user->CountryArea) && in_array($user->CountryArea->timezone, DateTimeZone::listIdentifiers())) {
//                        date_default_timezone_set($user->CountryArea->timezone);
                    }
                    $transaction = DB::table('twoctwop_transactions')
                        ->where('order_id', $payment_status['invoice_no'])
                        ->update([
                            'request_parameters' => json_encode($request->all()),
                            'payment_status' => 'success',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    $amount = ($trans->amount / 100);
                    $paramArray = array(
                        'user_id' => $user->id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => "TwoCTwoP",
                    );
                    WalletTransaction::UserWalletCredit($paramArray);
                    //                    CommonController::UserWalletCredit($user->id,null,$amount,2,2,2,"TwoCTwoP");
                    //                    $wallet = $user->wallet_balance;
                    //                    $user->wallet_balance = sprintf("%0.2f", $wallet + ($trans->amount / 100));
                    //                    $user->save();
                    //                    $money = UserWalletTransaction::create([
                    //                        'merchant_id' => $user->merchant_id,
                    //                        'user_id' => $user->id,
                    //                        'platfrom' => 2,
                    //                        'amount' => sprintf("%0.2f", ($trans->amount / 100)),
                    //                        'receipt_number' => "TwoCTwoP",
                    //                        'type' => 1,
                    //                    ]);
                    //                    $userdevices = UserDevice::where([['user_id', '=', $user->id]])->get();
                    //                    $playerids = array_pluck($userdevices, 'player_id');
                    $message = trans('api.money');
                    $data = ['message' => $message];
                    //                    Onesignal::UserPushMessage($user->id, $data, $message, 3, $user->merchant_id);
                    echo $data;
                    exit;
                } else if ($trans->type == 2 && $trans->payment_status == '') { // If payment Status Not Updated
                    $driver = Driver::find($trans->user_id);
                    if (isset($driver->CountryArea) && in_array($driver->CountryArea->timezone, DateTimeZone::listIdentifiers())) {
//                        date_default_timezone_set($driver->CountryArea->timezone);
                    }
                    $transaction = DB::table('twoctwop_transactions')
                        ->where('order_id', $payment_status['invoice_no'])
                        ->update([
                            'request_parameters' => json_encode($request->all()),
                            'payment_status' => 'success',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    $amount = ($trans->amount / 100);
                    $paramArray = array(
                        'driver_id' => $driver->id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => "TwoCTwoP",
                    );
                    WalletTransaction::WalletCredit($paramArray);
                    //                    CommonController::WalletCredit($driver->id,null,$amount,2,2,2,"TwoCTwoP");

//                    $money = $driver->wallet_money;
//                    $driver->wallet_money = sprintf("%0.2f", $money + ($trans->amount / 100));
//                    $driver->save();
//                    $money = DriverWalletTransaction::create([
//                        'merchant_id' => $driver->merchant_id,
//                        'driver_id' => $driver->id,
//                        'transaction_type' => 1,
//                        'payment_method' => '2',
//                        'receipt_number' => 'TwoCTwoP',
//                        'amount' => sprintf("%0.2f", ($trans->amount / 100)),
//                        'platform' => 2,
//                        'description' => 'TwoCTwoP',
//                        'narration' => 2,
//                    ]);
//                    $playerids = array($driver->player_id);
                    $message = trans('api.money');
                    $data = ['message' => $message];
//                    Onesignal::DriverPushMessage($driver->id, $data, $message, 3, $driver->merchant_id);
                    echo $data;
                    exit;
                } else {
                    return response()->json(['result' => 0, 'message' => 'Invalid Type']);
                }
            } else {
                return response()->json(['result' => 0, 'message' => $payment_status['message']]);
            }
        } catch (Exception $e) {
            error_response($e->getMessage());
        }
    }

    public function SenangPayReturnUrl(Request $request)
    {
        try {
            $transaction_id = $request->transaction_id;
            $order_id = $request->order_id;
            $status_id = $request->status_id;
            $msg = $request->msg;
            $hash = $request->hash;

            $trans = DB::table('senang_transactions')->where('order_id', $order_id)->first();
            if (!empty($trans)) {
                // $payment_option = PaymentOption::where('slug', 'SENANGPAY')->first();
                // $paymentoption = PaymentOptionsConfiguration::where([['merchant_id','=', $trans->merchant_id],['payment_option_id','=', $payment_option->id]])->first();
                /***********************************************************
                 * To verify the data integrity sending by PG
                 ************************************************************/
                /*
				$key0 = md5('865-395'.$trans->detail.$trans->amount.$trans->order_id);
                $key1 = md5($status_id.$order_id.$transaction_id.$msg);
                if($key0 != $key1){
                    $status_id = -1; // Invalid Transaction
                    echo "Invalid Transaction.";
                    exit;
                }*/
                if ($trans->type == 1) {
                    $user = User::find($trans->user_id);
                    $userdevices = UserDevice::where([['user_id', '=', $user->id]])->get();
                    $playerids = array_pluck($userdevices, 'player_id');
                    if (isset($user->CountryArea) && in_array($user->CountryArea->timezone, DateTimeZone::listIdentifiers())) {
//                        date_default_timezone_set($user->CountryArea->timezone);
                    }
                } elseif ($trans->type == 2) {
                    $user = Driver::find($trans->user_id);
                    $playerids = array($user->player_id);
                    if (isset($user->CountryArea) && in_array($user->CountryArea->timezone, \DateTimeZone::listIdentifiers())) {
                        date_default_timezone_set($user->CountryArea->timezone);
                    }
                }
                if ($trans->payment_status == '') { // If payment Status Not Updated
                    if (isset($user->CountryArea) && in_array($user->CountryArea->timezone, \DateTimeZone::listIdentifiers())) {
                        date_default_timezone_set($user->CountryArea->timezone);
                    }
                    if ($status_id == 1 && $trans->payment_status == '') { // If payment Status Not Updated
                        $transaction = DB::table('senang_transactions')
                            ->where('order_id', $order_id)
                            ->update([
                                'request_parameters' => json_encode($request->all()),
                                'payment_status' => 'success',
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        //                        if($trans->type == 1){
                        //                            $wallet = $user->wallet_balance;
                        //                            $user->wallet_balance = sprintf("%0.2f", $wallet + $trans->amount);
                        //                        }else{
                        //                            $wallet = $user->wallet_money;
                        //                            $user->wallet_money = sprintf("%0.2f", $wallet + $trans->amount);
                        //                        }
                        $user->save();
                        if ($trans->type == 1) {
                            $paramArray = array(
                                'user_id' => $user->id,
                                'booking_id' => NULL,
                                'amount' => $trans->amount,
                                'narration' => 2,
                                'platform' => 2,
                                'payment_method' => 2,
                                'receipt' => "SenangPay",
                            );
                            WalletTransaction::UserWalletCredit($paramArray);
                            //                            CommonController::UserWalletCredit($user->id,null,$trans->amount,2,2,2,"SenangPay");
                            //                            $money = UserWalletTransaction::create([
                            //                                'merchant_id' => $user->merchant_id,
                            //                                'user_id' => $user->id,
                            //                                'platfrom' => 2,
                            //                                'amount' => sprintf("%0.2f", $trans->amount),
                            //                                'receipt_number' => "SenangPay",
                            //                                'type' => 1,
                            //                            ]);
                        } elseif ($trans->type == 2) {
                            $paramArray = array(
                                'driver_id' => $user->id,
                                'booking_id' => NULL,
                                'amount' => $trans->amount,
                                'narration' => 2,
                                'platform' => 2,
                                'payment_method' => 2,
                                'receipt' => "SenangPay",
                            );
                            WalletTransaction::WalletCredit($paramArray);
                            //                            CommonController::WalletCredit($user->id,null,$trans->amount,2,2,2,"SenangPay");
                            //                            $money = DriverWalletTransaction::create([
                            //                                'merchant_id' => $user->merchant_id,
                            //                                'driver_id' => $user->id,
                            //                                'transaction_type' => 1,
                            //                                'payment_method' => '2',
                            //                                'receipt_number' => 'SenangPay',
                            //                                'amount' => sprintf("%0.2f", $trans->amount),
                            //                                'platform' => 2,
                            //                                'description' => 'SenangPay',
                            //                                'narration' => 2,
                            //                            ]);
                        }
                        $message = trans('api.money');
                    } elseif ($status_id == 0 && $trans->payment_status == '') {
                        $transaction = DB::table('senang_transactions')
                            ->where('order_id', $order_id)
                            ->update([
                                'request_parameters' => json_encode($request->all()),
                                'payment_status' => 'failed',
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        $message = trans('api.message163');
                    }
                    //                    $data = ['message' => $message];
                    //                    Onesignal::UserPushMessage($playerids, $data, $message, 3, $user->merchant_id);
                    echo $message;
                    exit;
                }
            } else {
                echo $msg;
                exit;
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }
    }

    public function SenangPayCallback(Request $request)
    {
        p($request->all());
    }

    public function SenangPayRecordTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detail' => 'required',
            'amount' => 'required',
            'order_id' => 'required',
            'type' => 'required'
            // 'hash' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0]]);
        }
        try {
            $existRecord = DB::table('senang_transactions')->where('order_id', $request->order_id)->get();
            if ($existRecord->count() > 0) {
                return response()->json(['result' => "0", 'message' => "Request Order Id already exist." . $request->order_id]);
            } else {
                if ($request->type == 1) {
                    $user = $request->user('api');
                } elseif ($request->type == 2) {
                    $user = $request->user('api-driver');
                }
                DB::table('senang_transactions')->insert(
                    ['merchant_id' => $user->merchant_id, 'user_id' => $user->id, 'type' => $request->type, 'order_id' => $request->order_id, 'amount' => $request->amount, 'detail' => $request->detail]
                );
                return response()->json(['result' => "1", 'message' => "Payment Request Recorded"]);
            }
        } catch (Exception $e) {
            return response()->json(['result' => "0", 'message' => $e->getMessage()]);
        }
    }

    public function PesapalTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detail' => 'required',
            'amount' => 'required',
            'order_id' => 'required',
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0]]);
        }
        try {
            $existRecord = DB::table('pesapal_transactions')->where('order_id', $request->order_id)->get();
            if ($existRecord->count() > 0) {
                return response()->json(['result' => "0", 'message' => "Request Order Id already exist." . $request->order_id]);
            } else {
                if ($request->type == 1) {
                    $user = $request->user('api');
                } elseif ($request->type == 2) {
                    $user = $request->user('api-driver');
                }
                DB::table('pesapal_transactions')->insert(
                    ['merchant_id' => $user->merchant_id, 'user_id' => $user->id, 'type' => $request->type, 'order_id' => $request->order_id, 'amount' => $request->amount, 'detail' => $request->detail]
                );
                return response()->json(['result' => "1", 'message' => "Payment Request Recorded"]);
            }
        } catch (Exception $e) {
            return response()->json(['result' => "0", 'message' => $e->getMessage()]);
        }
    }

    public function PesapalCallback(Request $request)
    {
        try {
            $pesapalNotification = $_GET['pesapal_notification_type'];
            $pesapalTrackingId = $_GET['pesapal_transaction_tracking_id'];
            $pesapal_merchant_reference = $_GET['pesapal_merchant_reference'];

            $statusrequestAPI = 'https://demo.pesapal.com/api/querypaymentstatus';
            // $statusrequestAPI = 'https://www.pesapal.com/api/querypaymentstatus';
            $consumer_key = "xxxxxxxxxxxxxxxxxx";
            $consumer_secret = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";

            if ($pesapalNotification == "CHANGE" && $pesapalTrackingId != '') {
                $token = $params = NULL;
                $consumer = new OAuthConsumer($consumer_key, $consumer_secret);
                $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
                //get transaction statuss
                $request_status = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $statusrequestAPI, $params);
                $request_status->set_parameter("pesapal_merchant_reference", $pesapal_merchant_reference);
                $request_status->set_parameter("pesapal_transaction_tracking_id", $pesapalTrackingId);
                $request_status->sign_request($signature_method, $consumer, $token);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $request_status);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                if (defined('CURL_PROXY_REQUIRED')) if (CURL_PROXY_REQUIRED == 'True') {
                    $proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
                    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                    curl_setopt($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
                }
                $response = curl_exec($ch);
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $raw_header  = substr($response, 0, $header_size - 4);
                $headerArray = explode("\r\n\r\n", $raw_header);
                $header      = $headerArray[count($headerArray) - 1];
                //transaction status
                $elements = preg_split("/=/", substr($response, $header_size));
                $status = $elements[1];
                curl_close($ch);
                if (DB_UPDATE_IS_SUCCESSFUL && $status != "PENDING") {
                    $this->PesapalMoneyCredit($pesapalTrackingId, 1, $request);
                    $resp = "pesapal_notification_type=$pesapalNotification&pesapal_transaction_tracking_id=$pesapalTrackingId&pesapal_merchant_reference=$pesapal_merchant_reference";
                    ob_start();
                    echo $resp;
                    ob_flush();
                    exit;
                }
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }
    }

    public function PesapalMoneyCredit($order_id, $status_id, $request)
    {
        $trans = DB::table('pesapal_transactions')->where('order_id', $order_id)->first();
        if (!empty($trans)) {
            if ($trans->type == 1) {
                $user = User::find($trans->user_id);
                $userdevices = UserDevice::where([['user_id', '=', $user->id]])->get();
                $playerids = array_pluck($userdevices, 'player_id');
                if (isset($user->CountryArea) && in_array($user->CountryArea->timezone, DateTimeZone::listIdentifiers())) {
//                    date_default_timezone_set($user->CountryArea->timezone);
                }
            } elseif ($trans->type == 2) {
                $user = Driver::find($trans->user_id);
                $playerids = array($user->player_id);
                if (isset($user->CountryArea) && in_array($user->CountryArea->timezone, \DateTimeZone::listIdentifiers())) {
                    date_default_timezone_set($user->CountryArea->timezone);
                }
            }
            if ($trans->payment_status == '') { // If payment Status Not Updated
                if (isset($user->CountryArea) && in_array($user->CountryArea->timezone, \DateTimeZone::listIdentifiers())) {
                    date_default_timezone_set($user->CountryArea->timezone);
                }
                if ($status_id == 1 && $trans->payment_status == '') { // If payment Status Not Updated
                    $transaction = DB::table('pesapal_transactions')
                        ->where('order_id', $order_id)
                        ->update([
                            'request_parameters' => json_encode($request->all()),
                            'payment_status' => 'success',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    $wallet = $user->wallet_balance;
                    $user->wallet_balance = sprintf("%0.2f", $wallet + $trans->amount);
                    $user->save();
                    if ($trans->type == 1) {
                        $paramArray = array(
                            'user_id' => $user->id,
                            'booking_id' => NULL,
                            'amount' => $trans->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'receipt' => "Pesapal",
                        );
                        WalletTransaction::UserWalletCredit($paramArray);
                        //                        CommonController::UserWalletCredit($user->id,null,$trans->amount,2,2,2,"Pesapal");
                        //                        $money = UserWalletTransaction::create([
                        //                            'merchant_id' => $user->merchant_id,
                        //                            'user_id' => $user->id,
                        //                            'platfrom' => 2,
                        //                            'amount' => sprintf("%0.2f", $trans->amount),
                        //                            'receipt_number' => "Pesapal",
                        //                            'type' => 1,
                        //                        ]);
                    } elseif ($trans->type == 2) {
                        $paramArray = array(
                            'driver_id' => $user->id,
                            'booking_id' => NULL,
                            'amount' => $trans->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'receipt' => "Pesapal",
                        );
                        WalletTransaction::WalletCredit($paramArray);
                        //                        CommonController::WalletCredit($user->id,null,$trans->amount,2,2,2,"Pesapal");
                        //                        $money = DriverWalletTransaction::create([
                        //                            'merchant_id' => $user->merchant_id,
                        //                            'driver_id' => $user->id,
                        //                            'transaction_type' => 1,
                        //                            'payment_method' => '2',
                        //                            'receipt_number' => 'Pesapal',
                        //                            'amount' => sprintf("%0.2f", $trans->amount),
                        //                            'platform' => 2,
                        //                            'description' => 'Pesapal',
                        //                            'narration' => 2,
                        //                        ]);
                    }
                    $message = trans('api.money');
                } elseif ($status_id == 0 && $trans->payment_status == '') {
                    $transaction = DB::table('pesapal_transactions')
                        ->where('order_id', $order_id)
                        ->update([
                            'request_parameters' => json_encode($request->all()),
                            'payment_status' => 'failed',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    $message = trans('api.message163');
                }
                $data = ['message' => $message];
                Onesignal::UserPushMessage($playerids, $data, $message, 3, $user->merchant_id);
            }
        }
    }
    // conekta create customer
    public function createConektaCustomerToken($card_token,$private_key, $user)
    {
        $full_name = $user->first_name.' '.$user->last_name;
        $phone = $user->UserPhone;
        $arr_data = [
            'name'=>$full_name,
            'email'=>$user->email,
            'phone'=>$phone,
            'payment_sources'=>[[
                'token_id'=>"$card_token",
                'type'=>"card",
            ]],
            'shipping_contacts'=>[[
                "phone"=> $phone,
                "receiver"=> $full_name,
                "address"=> [
                    "street1"=> "Nuevo Leon 4",
                    "country"=> $user->Country->CountryName,
                    "postal_code"=> "06100"
                ]]
            ]];
        $post_field = json_encode($arr_data);
        // p($post_field);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.conekta.io/customers",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            // CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_field,
            CURLOPT_HTTPHEADER => array(
                "Accept: application/vnd.conekta-v2.0.0+json",
                "Content-type: application/json",
                "Authorization: Bearer " . $private_key
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return $response;

    }

    // conekta create order means doing payment
    public function createConektaOrder($order)
    {
        $order_name = "Order No ".$order['name'];
        $arr_data = [
            'currency'=>$order['currency'],
            'customer_info'=>[
                'customer_id'=>$order['customer_token'],
            ],
            'line_items'=>[[ // we are sending total cart amount in unit price thats why quantity is one
                "name"=> $order_name,
                "unit_price"=> $order['amount'] * 100, // need to multiply by 100
                "quantity"=> 1,
             ]],
            "charges"=> [["payment_method"=> ["type"=> "default"]]]
        ];
        $post_field = json_encode($arr_data);
        // p($post_field);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.conekta.io/orders",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            // CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_field,
            CURLOPT_HTTPHEADER => array(
                "Accept: application/vnd.conekta-v2.0.0+json",
                "Content-type: application/json",
                "Authorization: Bearer ".$order['private_key']
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return $response;
    }

    public function ozowNotification(Request $request)
    {
        $log_data = array(
            'request_type' => 'Ozow Notification',
            'data' => $request->all(),
            'hit_time' => date('Y-m-d H:i:s')
        );
        Log::channel('flo_payment')->emergency($log_data);
    }

    public function ozowSuccess(Request $request)
    {
        $log_data = array(
            'request_type' => 'Ozow success',
            'data' => $request->all(),
            'hit_time' => date('Y-m-d H:i:s')
        );
        Log::channel('flo_payment')->emergency($log_data);
        echo "Thanks, Payment done successfully";
    }


//    public function payuPaymentOld($user, $amount, $card,$payment_option_config,$locale = "en")
//    {
//        $currency = $user->Country->isoCode;
//        $apiLogin = !empty($payment_option_config->api_secret_key) ? $payment_option_config->api_secret_key : '';
//        $apiKey = !empty($payment_option_config->api_public_key) ? $payment_option_config->api_public_key : '';
//        $account_id = !empty($payment_option_config->auth_token) ? $payment_option_config->auth_token : '';
//        $country_code = $user->Country->country_code;
//        $reference_code = "payment_test_".time();
//        // $merchant_id = "508029";
////        $merchant_id = "510700"; // sandbox
//        $merchant_id = "888886"; // live
//        //“ApiKey~merchantId~referenceCode~amount~currency”.
//        // $signature = $apiKey.'~'.$merchant_id.'~'.$reference_code.'~'.$amount.'~'.$currency;
//        $signature = $apiKey.'~'.$merchant_id.'~'.$reference_code.'~'.$amount.'~'.'CLP';
//        $signature = md5($signature);
//        // $arr_param = [
//        //     "language"=> $locale,
//        //     "command"=> "SUBMIT_TRANSACTION",
//        //     "merchant"=> [
//        //         "apiLogin"=> $apiLogin,
//        //         "apiKey"=> $apiKey
//        //     ],
//        //     "transaction"=> [
//        //         "order"=> [
//        //             "accountId"=> "515219",
//        //             "referenceCode"=> $reference_code,
//        //             "description"=> "payment test",
//        //             "language"=> $locale,
//        //             "signature"=> $signature,//"ff2e00a61625269c38895043034abb44",
//        //             "notifyUrl"=> route('api.payu-notification'),//"https://demo.apporioproducts.com/multi-service/public/api/payu/notification",
//        //             "additionalValues"=> [
//        //                 "TX_VALUE"=> [
//        //                     "value"=> $amount,
//        //                     "currency"=> $currency
//        //                 ]
//        //             ],
//        //             "buyer"=> [
//        //                 "merchantBuyerId"=>$user->user_merchant_id,
//        //                 "fullName"=> $user->first_name.' '.$user->last_name,
//        //                 "emailAddress"=> $user->email,
//        //                 "contactPhone"=> $user->UserPhone,
//        //                 "dniNumber"=> "",
//        //                 "cnpj"=> "",
//        //                 "shippingAddress"=> [
//        //                     "street1"=> "",
//        //                     "street2"=> "",
//        //                     "city"=> "",
//        //                     "state"=> "",
//        //                     "country"=> $country_code,
//        //                     "postalCode"=> "",
//        //                     "phone"=> ""
//        //                 ]
//        //             ]
//        //         ],
//        //         "creditCardTokenId"=> $card->token,//"3d2c34a7-78fd-4997-9be3-7ebac026a4ff",
//        //         "extraParameters"=> [
//        //             "INSTALLMENTS_NUMBER"=> 1
//        //         ],
//        //         "type"=> "AUTHORIZATION",
//        //         "paymentMethod"=> "VISA",
//        //         "paymentCountry"=> $country_code,
//        //         "ipAddress"=> "127.0.0.1"
//        //     ],
//        //     "test"=> false
//        // ];
//
//        $arr_param=[
//           "command"=>"SUBMIT_TRANSACTION",
//           "language"=>$locale,
//           "merchant"=>[
//              "apiKey"=>$apiKey,//"Xx6UMYQ499q4Pz9GC6fMeG5o3J",
//              "apiLogin"=>$apiLogin,//"ZUy1qlh29pM1HSz"
//            ],
//           "test"=>false,
//           "transaction"=>[
//                "cookie"=>"cookie PayU",
//                "creditCardTokenId"=> $card->token,//"7c06c5e9-2d66-413d-9f0b-e4600c96efb5",
//                "deviceSessionId"=>"",
//                "creditCard"=> [
//                    "processWithoutCvv2" => true
//                ],
//                "extraParameters"=>[
//                     "EXTRA1"=>"Extra Information First Field",
//                     "EXTRA2"=>"Extra Information Second Field",
//                     "EXTRA3"=>"Extra Information Third Field",
//                     "INSTALLMENTS_NUMBER"=>1,
//                     "RESPONSE_URL" => route('api.payu-notification'),//"https://demo.apporioproducts.com/multi-service/public/api/payu/notification",
//                     "SOFT_DESCRIPTOR"=>"CL SOFT NAME"
//                ],
//              "ipAddress"=>"127.0.0.1",
//              "order"=>[
////                  "accountId"=>"515219",
////                 "accountId"=>"895386", // without cvv
//                 "accountId"=>$account_id, // without cvv
//                 "additionalValues"=>[
//                    "TX_VALUE"=>[
//                       "value"=>$amount,
//                       "currency"=>$currency//"CLP"
//                    ]
//                 ],
//                 "buyer"=>[
//                    "cnpj"=> "32593371000110",
//                    "contactPhone"=>$user->UserPhone,
//                    "emailAddress"=>$user->email,
//                    "fullName"=>$user->first_name.' '.$user->last_name,
//                    "dniNumber"=>"811.807.405-64",
//                    "merchantBuyerId"=>$user->user_merchant_id,
//                    "shippingAddress"=>[
//                       "city"=>"Shipping Buyer City",
//                       "country"=>$country_code,//"CL",
//                       "phone"=>"3573573573",
//                       "postalCode"=>"101001000",
//                       "state"=>"Shipping Buyer City",
//                       "street1"=>"Shipping Buyer 123",
//                       "street2"=>"Shipping Buyer Complement 123"
//                    ]
//                 ],
//                 "description"=>"Payment Description",
//                 "language"=>$locale,
//                 "notifyUrl"=>route('api.payu-notification'),//"https://demo.apporioproducts.com/multi-service/public/api/payu/notification",
//                 "referenceCode"=>$reference_code,
//                 "signature"=>$signature,
//                 "shippingAddress"=>[
//                    "city"=>"Shipping Order City",
//                    "country"=>$country_code,//"CL",
//                    "phone"=>$user->UserPhone,
//                    "postalCode"=>"101001000",
//                    "state"=>"Shipping Order City",
//                    "street1"=>"Shipping Order 123",
//                    "street2"=>"Shipping Order Complement 123"
//                 ]
//              ],
//              "payer"=>[
//                 "billingAddress"=>[
//                    "city"=>"Billing City",
//                    "country"=>$country_code,//"CL",
//                    "phone"=>$user->UserPhone,
//                    "postalCode"=>"101001000",
//                    "state"=>"Billing State",
//                    "street1"=>"Billing Address 123",
//                    "street2"=>"Billing Complemento"
//                 ],
//                 "birthdate"=> "1980-01-01",
//                 "contactPhone"=>$user->UserPhone,
//                 "dniNumber"=>"9876543210",
//                 "emailAddress"=>$user->email,
//                 "fullName"=>$user->first_name.' '.$user->last_name,
//                 "merchantPayerId"=>"MPI20180521"
//              ],
//              "paymentCountry"=>$country_code,//"CL",
//              "paymentMethod"=>"VISA",
//              "type"=>"AUTHORIZATION",//"AUTHORIZATION",
//              "userAgent"=>"Mozilla PostMan X"
//           ]
//        ];
//        $arr_param = json_encode($arr_param,JSON_UNESCAPED_SLASHES);
//        $curl = curl_init();
//        curl_setopt_array($curl, array(
////            CURLOPT_URL => 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi',
//            CURLOPT_URL => 'https://api.payulatam.com/payments-api/4.0/service.cgi', // live
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_ENCODING => '',
//            CURLOPT_MAXREDIRS => 10,
//            CURLOPT_TIMEOUT => 0,
//            CURLOPT_FOLLOWLOCATION => true,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//            CURLOPT_CUSTOMREQUEST => 'POST',
//            CURLOPT_POSTFIELDS =>$arr_param,
//            CURLOPT_HTTPHEADER => array(
//                'Content-Type: application/json'
//            ),
//        ));
//        $response = curl_exec($curl);
//        $xml   = simplexml_load_string($response);
//        $response = json_decode(json_encode($xml),true);
//        curl_close($curl);
//        return $response;
//    }


    // partial capture
    // when authorisation amount is greater than capture
    public function payuPartialPayment($user, $amount, $card, $payment_option_config, $locale = "en", $transaction = NULL)
    {
        $currency = $user->Country->isoCode;
        $apiLogin = !empty($payment_option_config->api_secret_key) ? $payment_option_config->api_secret_key : '';
        $apiKey = !empty($payment_option_config->api_public_key) ? $payment_option_config->api_public_key : '';
        $id = isset($transaction['transactionResponse']) ? $transaction['transactionResponse']['orderId'] : "";
        $transaction_id = isset($transaction['transactionResponse']) ? $transaction['transactionResponse']['transactionId'] : "";

        $arr_param = [
            "command" => "SUBMIT_TRANSACTION",
            "language" => $locale,
            "merchant" => [
                "apiKey" => $apiKey,//"Xx6UMYQ499q4Pz9GC6fMeG5o3J",
                "apiLogin" => $apiLogin,//"ZUy1qlh29pM1HSz"
            ],

            "test" => false,
            "transaction" => [
                "order" => [
                    "id" => $id,
                ],
                "additionalValues" => [
                    "TX_VALUE" => [
                        "value" => $amount,
                        "currency" => $currency,
                    ]
                ],
                "type" => "CAPTURE",
                "parentTransactionId" => $transaction_id,//"AUTHORIZATION",

            ]
        ];
        // p($arr_param);
        $arr_param = json_encode($arr_param, JSON_UNESCAPED_SLASHES);
        // p($arr_param);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.payulatam.com/payments-api/4.0/service.cgi', // live
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $arr_param,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        // p($response);
        $xml = simplexml_load_string($response);
        $response = json_decode(json_encode($xml), true);
        curl_close($curl);
        // p($response);
        return $response;
    }

    // void payu authorisation request
    public function payuPaymentVoid($user, $amount, $card, $payment_option_config, $locale = "en", $transaction = NULL)
    {
        $locale = !empty($locale) ? $locale : "en";
        $currency = $user->Country->isoCode;
        $apiLogin = !empty($payment_option_config->api_secret_key) ? $payment_option_config->api_secret_key : '';
        $apiKey = !empty($payment_option_config->api_public_key) ? $payment_option_config->api_public_key : '';
        $id = isset($transaction['transactionResponse']) ? $transaction['transactionResponse']['orderId'] : "";
        $transaction_id = isset($transaction['transactionResponse']) ? $transaction['transactionResponse']['transactionId'] : "";
        $arr_param = [
            "command" => "SUBMIT_TRANSACTION",
            "language" => $locale,
            "merchant" => [
                "apiKey" => $apiKey,//"Xx6UMYQ499q4Pz9GC6fMeG5o3J",
                "apiLogin" => $apiLogin,//"ZUy1qlh29pM1HSz"
            ],
            "test" => false,
            "transaction" => [
                "order" => [
                    "id" => $id,
                ],
                "type" => "VOID",
                "reason" => "VOID REQUESTED BY THE MERCHANT",
                "parentTransactionId" => $transaction_id,//"AUTHORIZATION",
            ]
        ];
        $arr_param = json_encode($arr_param, JSON_UNESCAPED_SLASHES);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.payulatam.com/payments-api/4.0/service.cgi', // live
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>$arr_param,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        // p($response);
        $xml = simplexml_load_string($response);
        $response = json_decode(json_encode($xml), true);
        curl_close($curl);
        // p($response);
        return $response;
    }

    // capture
    public function payuPayment($user, $amount, $card, $payment_option_config, $locale = "en", $transaction = NULL,$type = 1)
    {
            $currency = $user->Country->isoCode;
            $country_code = $user->Country->country_code;
        if($type == 1){
            $user_merchant_id = $user->user_merchant_id;
            $name = $user->first_name . ' ' . $user->last_name;
            $email = $user->email;
            $phone = $user->UserPhone;
            
        }else{
            $user_merchant_id = $user->merchant_driver_id;
            $name = $user->first_name . ' ' . $user->last_name;
            $email = $user->email;
            $phone = $user->phoneNumber;
        }
        $apiLogin = !empty($payment_option_config->api_secret_key) ? $payment_option_config->api_secret_key : '';
        $apiKey = !empty($payment_option_config->api_public_key) ? $payment_option_config->api_public_key : '';
        $accountId = !empty($payment_option_config->auth_token) ? $payment_option_config->auth_token : '';
        $merchantId = !empty($payment_option_config->operator) ? $payment_option_config->operator : '';
        $gatewayUrl = ($payment_option_config->gateway_condition == 1) ? 'https://api.payulatam.com/payments-api/4.0/service.cgi' : 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi';
        // $id = isset($transaction['transactionResponse']) ? $transaction['transactionResponse']['orderId'] : "";
        // $transaction_id = isset($transaction['transactionResponse']) ? $transaction['transactionResponse']['transactionId'] : "";
        // $arr_param = [
        //     "command" => "SUBMIT_TRANSACTION",
        //     "language" => $locale,
        //     "merchant" => [
        //         "apiKey" => $apiKey,//"Xx6UMYQ499q4Pz9GC6fMeG5o3J",
        //         "apiLogin" => $apiLogin,//"ZUy1qlh29pM1HSz"
        //     ],
        //     "test" => false,
        //     "transaction" => [
        //         "order" => [
        //             "id" => $id,
        //         ],
        //         "type" => "CAPTURE",
        //         "parentTransactionId" => $transaction_id,//"AUTHORIZATION",
        //     ]
        // ];
        // $merchantId = '508029';
        $referenceCode = 'REFERENCE_'.time();
        $txValue = 3; 
        // $currency = 'COP';
        
        $signatureString = "$apiKey~$merchantId~$referenceCode~$txValue~$currency";
        $signature = md5($signatureString);
        $arr_param = [
        "language" => $locale,
        "command" => "SUBMIT_TRANSACTION",
        "merchant" => [
            "apiKey" => $apiKey,
            "apiLogin" => $apiLogin
        ],
        "transaction" => [
            "order" => [
                "accountId" => $accountId,
                "referenceCode" => $referenceCode,
                "description" => "Payment test description",
                "language" => $locale,
                "signature" => $signature,
                "notifyUrl" => route('api.payu-notification'),
                "additionalValues" => [
                    "TX_VALUE" => [
                        "value" => $txValue,
                        "currency" => $currency
                    ]
                ],
                "buyer" => [
                    "merchantBuyerId" => $user_merchant_id,
                    "fullName" => $name,
                    "emailAddress" => $email ?? "buyer_test@test.com",
                    "contactPhone" => $phone,
                    "dniNumber" => "123456789",
                    "shippingAddress" => [
                        "street1" => "calle 100",
                        "street2" => "5555487",
                        "phone" => "3573573573",
                        "city" => "Shipping Buyer City",
                        "state" => "Shipping Buyer City",
                        "country" => $country_code,//"CO",
                        "postalCode" => "101001000",
                    ]
                ],
                "shippingAddress" => [
                    "street1" => "calle 100",
                    "street2" => "5555487",
                    "city" => "Shipping Order City",
                    "state" => "Shipping Order City",
                    "country" => $country_code, //CO
                    "postalCode" => "101001000",
                    "phone" => $phone
                ]
            ],
            "payer" => [
                "merchantPayerId" => "MPI20180521",
                "fullName" => $name,
                "emailAddress" => $email,
                "contactPhone" => $phone,
                "dniNumber" => "5415668464654",
                "billingAddress" => [
                    "street1" => "calle 93",
                    "street2" => "125544",
                    "city" => "Billing City",
                    "state" => "Billing State",
                    "country" => $country_code,//CO
                    "postalCode" => "101001000",
                    "phone" => $phone
                ]
            ],
            "creditCardTokenId" => $card->token,
            "creditCard" => [
                "processWithoutCvv2" => true,
            ],
            "extraParameters" => [
                "INSTALLMENTS_NUMBER" => 1
            ],
            "type" => "AUTHORIZATION_AND_CAPTURE",
            "paymentMethod" => strtoupper($card->card_type),
            "paymentCountry" => "CO",
            "deviceSessionId" => "vghs6tvkcle931686k1900o6e1",
            "ipAddress" => "127.0.0.1",
            "cookie" => "pt1t38347bs6jc9ruv2ecpv7o2",
            "userAgent" => "Mozilla/5.0 (Windows NT 5.1; rv:18.0) Gecko/20100101 Firefox/18.0"
        ],
        "test" => $payment_option_config->gateway_condition == 1 ? false : true
    ];

        $arr_param = json_encode($arr_param, JSON_UNESCAPED_SLASHES);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            // CURLOPT_URL => 'https://api.payulatam.com/payments-api/4.0/service.cgi', // live
            CURLOPT_URL => $gatewayUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $arr_param,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response1 = curl_exec($curl);
        $xml = simplexml_load_string($response1);
        $response = json_decode(json_encode($xml), true);
        curl_close($curl);
        // p($response);
        return $response;
    }

    public function payuPaymentAuthorization($user, $amount, $card, $payment_option_config, $locale = "en", $type = "")
    {
        try {
            $currency = $user->Country->isoCode;
            $apiLogin = !empty($payment_option_config->api_secret_key) ? $payment_option_config->api_secret_key : '';
            $apiKey = !empty($payment_option_config->api_public_key) ? $payment_option_config->api_public_key : '';
            $account_id = !empty($payment_option_config->auth_token) ? $payment_option_config->auth_token : '';
            $country_code = $user->Country->country_code;
            $reference_code = "payment_test_" . time();
//        $merchant_id = "510700"; // sandbox
            $merchant_id = "888886"; // live
            //“ApiKey~merchantId~referenceCode~amount~currency”.
            // $signature = $apiKey.'~'.$merchant_id.'~'.$reference_code.'~'.$amount.'~'.$currency;
            $signature = $apiKey . '~' . $merchant_id . '~' . $reference_code . '~' . $amount . '~' . $currency;
            $signature = md5($signature);


            $arr_param = [
                "command" => "SUBMIT_TRANSACTION",
                "language" => $locale,
                "merchant" => [
                    "apiKey" => $apiKey,//"Xx6UMYQ499q4Pz9GC6fMeG5o3J",
                    "apiLogin" => $apiLogin,//"ZUy1qlh29pM1HSz"
                ],
                "test" => false,
                "transaction" => [
                    "cookie" => "cookie PayU",
                    "creditCardTokenId" => $card->token,//"7c06c5e9-2d66-413d-9f0b-e4600c96efb5",
                    "deviceSessionId" => "",
                    "creditCard" => [
                        "processWithoutCvv2" => true
                    ],
                    "extraParameters" => [
                        "EXTRA1" => "Extra Information First Field",
                        "EXTRA2" => "Extra Information Second Field",
                        "EXTRA3" => "Extra Information Third Field",
                        "INSTALLMENTS_NUMBER" => 1,
                        "RESPONSE_URL" => route('api.payu-notification'),//"https://demo.apporioproducts.com/multi-service/public/api/payu/notification",
                        "SOFT_DESCRIPTOR" => "CL SOFT NAME"
                    ],
                    "ipAddress" => "127.0.0.1",
                    "order" => [
//                  "accountId"=>"515219",
//                 "accountId"=>"895386", // without cvv
                        "accountId" => $account_id, // without cvv
                        "additionalValues" => [
                            "TX_VALUE" => [
                                "value" => $amount,
                                "currency" => $currency//"CLP"
                            ]
                        ],
                        "buyer" => [
                            "cnpj" => "32593371000110",
                            "contactPhone" => $user->UserPhone,
                            "emailAddress" => $user->email,
                            "fullName" => $user->first_name . ' ' . $user->last_name,
                            "dniNumber" => "811.807.405-64",
                            "merchantBuyerId" => $user->user_merchant_id,
                            "shippingAddress" => [
                                "city" => "Shipping Buyer City",
                                "country" => $country_code,//"CL",
                                "phone" => "3573573573",
                                "postalCode" => "101001000",
                                "state" => "Shipping Buyer City",
                                "street1" => "Shipping Buyer 123",
                                "street2" => "Shipping Buyer Complement 123"
                            ]
                        ],
                        "description" => "Payment Description",
                        "language" => $locale,
                        "notifyUrl" => route('api.payu-notification'),//"https://demo.apporioproducts.com/multi-service/public/api/payu/notification",
                        "referenceCode" => $reference_code,
                        "signature" => $signature,
                        "shippingAddress" => [
                            "city" => "Shipping Order City",
                            "country" => $country_code,//"CL",
                            "phone" => $user->UserPhone,
                            "postalCode" => "101001000",
                            "state" => "Shipping Order City",
                            "street1" => "Shipping Order 123",
                            "street2" => "Shipping Order Complement 123"
                        ]
                    ],
                    "payer" => [
                        "billingAddress" => [
                            "city" => "Billing City",
                            "country" => $country_code,//"CL",
                            "phone" => $user->UserPhone,
                            "postalCode" => "101001000",
                            "state" => "Billing State",
                            "street1" => "Billing Address 123",
                            "street2" => "Billing Complemento"
                        ],
                        "birthdate" => "1980-01-01",
                        "contactPhone" => $user->UserPhone,
                        "dniNumber" => "9876543210",
                        "emailAddress" => $user->email,
                        "fullName" => $user->first_name . ' ' . $user->last_name,
                        "merchantPayerId" => "MPI20180521"
                    ],
                    "paymentCountry" => $country_code,//"CL",
                    "paymentMethod" => "MASTERCARD",
//                    "paymentMethod"=>"VISA",
                    "type" => "AUTHORIZATION",
                    "userAgent" => "Mozilla PostMan X"
                ]
            ];
            $arr_param = json_encode($arr_param, JSON_UNESCAPED_SLASHES);
            $curl = curl_init();
            curl_setopt_array($curl, array(
//            CURLOPT_URL => 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi',
                CURLOPT_URL => 'https://api.payulatam.com/payments-api/4.0/service.cgi', // live
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $arr_param,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            $xml = simplexml_load_string($response);
            $response = json_decode(json_encode($xml), true);
            curl_close($curl);

            if ($response['code'] == "SUCCESS") {
                return $response;
            } else {
                throw new Exception($response['error']);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function payuPaymentAuthorizationAndCapture($user, $amount, $card, $payment_option_config, $locale = "en", $type = "")
    {
        try {
            $currency = $user->Country->isoCode;
            $apiLogin = !empty($payment_option_config->api_secret_key) ? $payment_option_config->api_secret_key : '';
            $apiKey = !empty($payment_option_config->api_public_key) ? $payment_option_config->api_public_key : '';
            $account_id = !empty($payment_option_config->auth_token) ? $payment_option_config->auth_token : '';
            $country_code = $user->Country->country_code;
            $reference_code = "payment_test_" . time();
//        $merchant_id = "510700"; // sandbox
            $merchant_id = "888886"; // live
            //“ApiKey~merchantId~referenceCode~amount~currency”.
            // $signature = $apiKey.'~'.$merchant_id.'~'.$reference_code.'~'.$amount.'~'.$currency;
            $signature = $apiKey . '~' . $merchant_id . '~' . $reference_code . '~' . $amount . '~' . $currency;
            $signature = md5($signature);

            $arr_param = [
                "command" => "SUBMIT_TRANSACTION",
                "language" => $locale,
                "merchant" => [
                    "apiKey" => $apiKey,//"Xx6UMYQ499q4Pz9GC6fMeG5o3J",
                    "apiLogin" => $apiLogin,//"ZUy1qlh29pM1HSz"
                ],
                "test" => false,
                "transaction" => [
                    "cookie" => "cookie PayU",
                    "creditCardTokenId" => $card->token,//"7c06c5e9-2d66-413d-9f0b-e4600c96efb5",
                    "deviceSessionId" => "",
                    "creditCard" => [
                        "processWithoutCvv2" => true
                    ],
                    "extraParameters" => [
                        "EXTRA1" => "Extra Information First Field",
                        "EXTRA2" => "Extra Information Second Field",
                        "EXTRA3" => "Extra Information Third Field",
                        "INSTALLMENTS_NUMBER" => 1,
                        "RESPONSE_URL" => route('api.payu-notification'),//"https://demo.apporioproducts.com/multi-service/public/api/payu/notification",
                        "SOFT_DESCRIPTOR" => "CL SOFT NAME"
                    ],
                    "ipAddress" => "127.0.0.1",
                    "order" => [
//                  "accountId"=>"515219",
//                 "accountId"=>"895386", // without cvv
                        "accountId" => $account_id, // without cvv
                        "additionalValues" => [
                            "TX_VALUE" => [
                                "value" => $amount,
                                "currency" => $currency//"CLP"
                            ]
                        ],
                        "buyer" => [
                            "cnpj" => "32593371000110",
                            "contactPhone" => $user->UserPhone,
                            "emailAddress" => $user->email,
                            "fullName" => $user->first_name . ' ' . $user->last_name,
                            "dniNumber" => "811.807.405-64",
                            "merchantBuyerId" => $user->user_merchant_id,
                            "shippingAddress" => [
                                "city" => "Shipping Buyer City",
                                "country" => $country_code,//"CL",
                                "phone" => "3573573573",
                                "postalCode" => "101001000",
                                "state" => "Shipping Buyer City",
                                "street1" => "Shipping Buyer 123",
                                "street2" => "Shipping Buyer Complement 123"
                            ]
                        ],
                        "description" => "Payment Description",
                        "language" => $locale,
                        "notifyUrl" => route('api.payu-notification'),//"https://demo.apporioproducts.com/multi-service/public/api/payu/notification",
                        "referenceCode" => $reference_code,
                        "signature" => $signature,
                        "shippingAddress" => [
                            "city" => "Shipping Order City",
                            "country" => $country_code,//"CL",
                            "phone" => $user->UserPhone,
                            "postalCode" => "101001000",
                            "state" => "Shipping Order City",
                            "street1" => "Shipping Order 123",
                            "street2" => "Shipping Order Complement 123"
                        ]
                    ],
                    "payer" => [
                        "billingAddress" => [
                            "city" => "Billing City",
                            "country" => $country_code,//"CL",
                            "phone" => $user->UserPhone,
                            "postalCode" => "101001000",
                            "state" => "Billing State",
                            "street1" => "Billing Address 123",
                            "street2" => "Billing Complemento"
                        ],
                        "birthdate" => "1980-01-01",
                        "contactPhone" => $user->UserPhone,
                        "dniNumber" => "9876543210",
                        "emailAddress" => $user->email,
                        "fullName" => $user->first_name . ' ' . $user->last_name,
                        "merchantPayerId" => "MPI20180521"
                    ],
                    "paymentCountry" => $country_code,//"CL",
                    "paymentMethod" => "MASTERCARD",
//                    "paymentMethod"=>"VISA",
                    "type" => "AUTHORIZATION_AND_CAPTURE",//"AUTHORIZATION",
                    "userAgent" => "Mozilla PostMan X"
                ]
            ];
            $arr_param = json_encode($arr_param, JSON_UNESCAPED_SLASHES);
            $curl = curl_init();
            curl_setopt_array($curl, array(
//            CURLOPT_URL => 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi',
                CURLOPT_URL => 'https://api.payulatam.com/payments-api/4.0/service.cgi', // live
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $arr_param,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            $xml = simplexml_load_string($response);
            $response = json_decode(json_encode($xml), true);
            curl_close($curl);
            // p($response);
            if ($response['code'] == "SUCCESS") {
                return $response;
            } else {
                throw new Exception($response['error']);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function payuNotification(Request $request)
    {
        $log_data = array(
            'request_type' => 'PayU Notification',
            'data' => $request->all(),
            'hit_time' => date('Y-m-d H:i:s')
        );
        echo "Success";
        Log::channel('payu_log')->emergency($log_data);
    }

    // success
    public function maxiCashSuccess(Request $request)
    {
        $log_data = array(
            'request_type' => 'maxiCashSuccess',
            'data' => $request->all(),
            'hit_time' => date('Y-m-d H:i:s')
        );
//        echo "Success";
        Log::channel('maxi_cash')->emergency($log_data);
    }

    // cancel
    public function maxiCashCancel(Request $request)
    {
        $log_data = array(
            'request_type' => 'maxiCashCancel',
            'data' => $request->all(),
            'hit_time' => date('Y-m-d H:i:s')
        );
        Log::channel('maxi_cash')->emergency($log_data);
    }

    // failure
    public function maxiCashFailure(Request $request)
    {
        $log_data = array(
            'request_type' => 'maxiCashFailure',
            'data' => $request->all(),
            'hit_time' => date('Y-m-d H:i:s')
        );
        Log::channel('maxi_cash')->emergency($log_data);
    }

    // notification
    public function maxiCashNotification(Request $request)
    {
        $log_data = array(
            'request_type'=>'maxiCashFailure',
            'data'=>$request->all(),
            'hit_time' => date('Y-m-d H:i:s')
        );
        \Log::channel('maxi_cash')->emergency($log_data);
    }

    public function PaypalWebViewURL(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'currency' => 'required|string',
            'for' => 'required|in:1,2',
            'segment_type' => 'required|in:BOOKING,ORDER,HANDYMAN,WALLET',
            'handyman_order_id' => 'required_if:segment_type,==,HANDYMAN',
            'order_id' => 'required_if:segment_type,==,ORDER',
            'booking_id' => 'required_if:segment_type,==,BOOKING',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        if ($request->for == 1) {
            $user = $request->user('api');
            $user_id = $user->id;
            $driver_id = NULL;
        } else {
            $user = $request->user('api-driver');
            $driver_id = $user->id;
            $user_id = NULL;
        }

        $payment_option = PaymentOption::where('slug', 'PAYPAL')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        $string_file = $this->getStringFile($user->merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }

        $currencyExchangePlateform = $paymentOption->currency_exchange_id;
        $conversion_id = $paymentOption->auth_token; // currency conversion api id
//        $conversion_id = $paymentOption->api_secret_key; // secret key
        $client_id = $paymentOption->api_public_key; // open exchange rate id

        $amount = sprintf("%0.2f", $request->amount);
        $currency = strtoupper($request->currency);
        $paypal_order_id = time();

        DB::table('paypal_transactions')->insert([
            'user_id' => $user_id,
            'driver_id' => $driver_id,
            'booking_id' => $request->booking_id,
            'order_id' => $request->order_id,
            'handyman_order_id' => $request->handyman_order_id,
            'paypal_order_id' => $paypal_order_id,
            'amount' => $currency . " " . $amount,
            'created_at' => Carbon::now()
        ]);

        $success_url = route('api.paypal_success_url');
        $fail_url = route('api.paypal_fail_url');
        $notify_url = route('api.paypal_notify_url');

        if($currencyExchangePlateform == 1){
            $supported_codes = ['INR', 'USD', 'AUD', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB'];
            // BRL, MYR and INR support currencies for their respective countries' businsess account

            if (in_array($currency, $supported_codes)) {
                $noSupportCurrencies = ['HUF', 'JPY', 'TWD'];
                if (in_array($currency, $noSupportCurrencies)) {
                    $amount = round($amount);
                }
            } else {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://openexchangerates.org/api/latest.json?app_id=" . $conversion_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                ));
                $response = curl_exec($curl);
                curl_close($curl);
                $response = json_decode($response);
                if (!empty($response->error) && $response->error == 1) {
                    return $this->failedResponse($response->description);

                } else {
                    if (!$response->rates->$currency) {
                        return $this->failedResponse("Invalid OpenExchange Account Key");
                    }
                    $amount = sprintf("%0.2f", round((1 / $response->rates->$currency) * $amount, 2));
                    $currency = 'USD';
                }
            }
        }elseif($currencyExchangePlateform == 2){
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.currencyapi.com/v3/latest",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
             'apikey:'.$conversion_id
            ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response,true);
            if($response && isset($response['data']) && isset($response['data'][$currency])){
                $amount = sprintf("%0.2f", round((1 / $response['data'][$currency]['value']) * $amount, 2));
                $currency = 'USD';
            }else{
                throw new \Exception($response->message);
            }
        }
        $return_data = [
            // 'web_view_data' => route('paypalview') . '?client_id=' . $client_id . '&amount=' . $amount . '&currency=' . $currency . '&success_url=' . $success_url . '&fail_url=' . $fail_url . '&notify_url=' . $notify_url . '&order_id=' . $paypal_order_id
            // 'web_view_data' => route('paypalview') . '?client_id=' . $client_id . '&amount=' . $amount . '&currency=' . $currency,
            'web_view_data' => route('paypalview') . '?client_id=' . $client_id . '&amount=' . $amount . '&currency=' . $currency .  '&order_id=' . $paypal_order_id, //fix by ayush
            'url' => route('paypalview') . '?client_id=' . $client_id . '&amount=' . $amount . '&currency='. $currency .  '&order_id=' . $paypal_order_id, //fix by ayush for ios
            'success_url'=> $success_url,
            'fail_url' => $fail_url,
            'notify_url' => $notify_url
        ];
        return $this->successResponse(trans("$string_file.payment"), $return_data);
    }

    public function Paypal(Request $request)
    {
        $amount = $request->query('amount');
        $currency = $request->query('currency');
        $client_id = $request->query('client_id');
        // $success_url = $request->query('success_url');
        // $fail_url = $request->query('fail_url');
        // $notify_url = $request->query('notify_url');
        $order_id = $request->query('order_id');
        return view('payment/paypal/index', compact('amount', 'currency', 'client_id','order_id'));
    }

    public function Paypal_notify(Request $request)
    {
        DB::table('paypal_transactions')->where(['order_id' => $request->order_id])
            ->update([
                'ref_id' => $request->ref_id,
                'status' => $request->status,
                'updated_at' => Carbon::now()
            ]);
    }

    public function Paypal_success()
    {
        $response = trans("common.transaction_completed_successfully");
        return view('payment/paypal/callback', compact('response'));
    }

    public function Paypal_fail()
    {
        $response = trans("common.transaction_failed");
        return view('payment/paypal/callback', compact('response'));
    }

    public function TriPayPaymentChannels(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        if ($request->type == 1) {
            $user = $request->user('api');
        } elseif ($request->type == 2) {
            $user = $request->user('api-driver');
        } else {
            return $this->failedResponse("Invalid Type");
        }
        try {
            $string_file = $this->getStringFile($user->merchant_id);
            $payment_option = PaymentOption::where('slug', 'TriPay')->first();
            $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
            if (!empty($paymentConfig)) {
                $apiKey = $paymentConfig->api_secret_key;
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_FRESH_CONNECT => true,
                    CURLOPT_URL => "https://payment.tripay.co.id/api/payment/channel",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer " . $apiKey
                    ),
                    CURLOPT_FAILONERROR => false
                ));
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                $response = json_decode($response, true);
                if (isset($response['success']) && $response['success'] == 1) {
                    $payment_channels = [];
                    foreach ($response['data'] as $payment_group) {
                        $payments = [];
                        foreach ($payment_group['payment'] as $payment) {
                            array_push($payments, array(
                                "code" => $payment['code'],
                                "name" => $payment['name'],
                                "description" => $payment['description'],
                                "icon_url" => isset($payment['icon_url']) ? $payment['icon_url'] : "",
                                "fee" => isset($payment['fee']['flat']) ? (string)$payment['fee']['flat'] : ""
                            ));
                        }
                        array_push($payment_channels, array("group_name" => $payment_group['group_name'], "name" => $payments));
                    }
                    return $this->successResponse(trans("$string_file.success"), $payment_channels);
                } else {
                    return $this->failedResponse($response["message"]);
                }
            } else {
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            }
        } catch (Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function TriPayCreateTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'type' => 'required|integer',
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        if ($request->type == 1) {
            $user = $request->user('api');
            $name = $user->UserName;
            $email = $user->email;
            $phone = $user->UserPhone;
        } elseif ($request->type == 2) {
            $user = $request->user('api-driver');
            $name = $user->fullName;
            $email = $user->email;
            $phone = $user->phoneNumber;
        } else {
            return $this->failedResponse("Invalid Type");
        }
        DB::beginTransaction();
        try {
            $string_file = $this->getStringFile($user->merchant_id);
            $payment_option = PaymentOption::where('slug', 'TriPay')->first();
            $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
            if (!empty($paymentConfig)) {
                $merchantRef = 'MREF-' . time();
                $amount = $request->amount;// * 100;//1000000;
                $data = [
                    'method' => $request->code,//'BRIVA',
                    'merchant_ref' => $merchantRef,
                    'amount' => $amount,
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                    'order_items' => [
                        [
//                            'sku' => 'ADD-WALLET',
//                            'name' => 'Add Wallet All In One',
                            'sku' => 'TOP UP DOMPET',
                            'name' => 'TopUp Dompet ' . $user->Merchant->BusinessName,
                            'price' => $amount,
                            'quantity' => 1
                        ]
                    ],
                    'callback_url' => URL::to('/tripay/callback'),// 'https://domainanda.com/callback',
                    'return_url' => URL::to('/tripay/redirect'), // 'https://domainanda.com/redirect',
                    'expired_time' => (time() + (24 * 60 * 60)), // 24 jam
                    'signature' => hash_hmac('sha256', $paymentConfig->auth_token . $merchantRef . $amount, $paymentConfig->api_public_key)
                ];
                if ($paymentConfig->gateway_condition == 1) {
                    $url = "https://payment.tripay.co.id/api/transaction/create";
                } else {
                    $url = "https://payment.tripay.co.id/api-sandbox/transaction/create";
                }
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_FRESH_CONNECT => true,
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer " . $paymentConfig->api_secret_key
                    ),
                    CURLOPT_FAILONERROR => false,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($data)
                ));
                $response = curl_exec($curl);
                curl_close($curl);
                $response = json_decode($response, true);
                if (isset($response['success']) && $response['success'] == true) {
                    $transaction = new TripayTransaction();
                    $transaction->merchant_id = $user->merchant_id;
                    $transaction->user_id = $user->id;
                    $transaction->type = $request->type;
                    $transaction->code = $request->code;
                    $transaction->amount = $request->amount;
                    $transaction->merchant_ref = $merchantRef;
                    $transaction->reference = $response['data']['reference'];
                    $transaction->save();
                    DB::commit();
                    $data = ["merchant_ref" => $transaction->merchant_ref, "reference" => $transaction->reference, "checkout_url" => $response['data']['checkout_url']];
                    return $this->successResponse(trans("$string_file.success"), $data);
                } else {
                    return $this->failedResponse($response["message"]);
                }
            } else {
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            }
        } catch (Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
    }

    public function TriPayCheckTransactionRedirect(Request $request)
    {
        return response()->json([
            'success' => true
        ]);
    }

    public function TriPayCheckTransactionCallback(Request $request)
    {
        $payment_option = PaymentOption::where('slug', 'TriPay')->first();
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', 85], ['payment_option_id', '=', $payment_option->id]])->first();
        $privateKey = $paymentConfig->api_public_key;
        // ambil callback signature
        $callbackSignature = $request->server('HTTP_X_CALLBACK_SIGNATURE') ?? '';

        // ambil data JSON
        $json = $request->getContent();

        // generate signature untuk dicocokkan dengan X-Callback-Signature
        $signature = hash_hmac('sha256', $json, $privateKey);

        // validasi signature
        if ($callbackSignature !== $signature) {
            return "Invalid Signature"; // signature tidak valid, hentikan proses
        }

        $data = json_decode($json);
        $event = $request->server('HTTP_X_CALLBACK_EVENT');

        if ($event == 'payment_status') {
            if ($data->status == 'PAID') {
                $merchantRef = $data->merchant_ref;

                $transaction = TripayTransaction::where([["merchant_ref", "=", $merchantRef]])->first();
                if (!empty($transaction)) {
                    $transaction->payment_status = "PAID";
                    $transaction->response = json_encode($data);
                    $transaction->save();
                    if ($transaction->type == 1) {
                        $user = User::find($transaction->user_id);
                        $paramArray = array(
                            'user_id' => $user->id,
                            'booking_id' => NULL,
                            'amount' => $transaction->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'receipt' => "TriPay Transaction",
                        );
                        WalletTransaction::UserWalletCredit($paramArray);
                    } else {
                        $driver = Driver::find($transaction->user_id);
                        $paramArray = array(
                            'driver_id' => $driver->id,
                            'booking_id' => NULL,
                            'amount' => $transaction->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'receipt' => "TriPay Transaction",
                        );
                        WalletTransaction::WalletCredit($paramArray);
                    }
                    return response()->json([
                        'success' => true
                    ]);
                } else {
                    return "Transaction not found";
                }
            }
        }
        return "No action was taken";
    }

    public function BookeeyURL(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'for' => 'required|string',  // USER/DRIVER
            'os' => 'required|string',
            'payment_method' => 'required|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = ($request->for == 'USER') ? $request->user('api') : $request->user('api-driver');
            $user_id = ($request->for == 'USER') ? $user->id : NULL;
            $driver_id = ($request->for == 'USER') ? NULL : $user->id;

            $string_file = $this->getStringFile($user->merchant_id);
            $os = $request->os;
            $payment_option = PaymentOption::where('slug', 'BOOKEEY')->first();
            $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
            if (!empty($paymentOption)) {
                $merchant_uid = $paymentOption->api_public_key;
                $product_id = $paymentOption->api_public_key;
                $order_id = Carbon::now()->timestamp;
                $F_URL = route('BookeeyFail');
                $S_URL = route('BookeeySuccess');
                $amount = $request->amount;
                $cross_cat = "GEN";
                $method = $request->payment_method ?? 'knet'; // possible values: knet,credit,amex,Bookeey
                $HashedData = hash('sha512', $merchant_uid . '|' . $order_id . '|' . $S_URL . '|' . $F_URL . '|' . $amount . '|' . $cross_cat . '|' . $product_id . '|' . $order_id);
                DB::table('bookeey_transactions')->insert([
                    'merchant_id' => $user->merchant_id,
                    'user_id' => $user_id,
                    'driver_id' => $driver_id,
                    'payment_method' => $request->payment_method,
                    'order_id' => $order_id,
                    'amount' => sprintf('%0.2f', $request->amount),
                    'created_at' => Carbon::now('UTC')
                ]);
                $curl = curl_init();
                $url = $paymentOption->gateway_condition == 2 ? "https://apps.bookeey.com/pgapi/api/payment/requestLink" : "https://pg.bookeey.com/internalapi/api/payment/requestLink";
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => '{
                "DBRqst":"PY_ECom",
                "Do_Appinfo":{
                    "APIVer":"1.7",
                    "APPID":"",
                    "APPTyp":"MOB",
                    "AppVer":"22",
                    "Country":"Kuwait",
                    "DevcType":"5",
                    "HsCode":"",
                    "IPAddrs":"",
                    "MdlID":"",
                    "OS":"' . $os . '",
                    "UsrSessID":""
                },
                "Do_MerchDtl":{
                    "BKY_PRDENUM":"' . $product_id . '",
                    "FURL":"' . $F_URL . '",
                    "MerchUID":"' . $merchant_uid . '",
                    "SURL":"' . $S_URL . '"
                },
                "Do_MoreDtl":{
                    "Cust_Data1":"",
                    "Cust_Data2":"",
                    "Cust_Data3":""
                },
                "Do_PyrDtl":{
                    "Pyr_MPhone":"",
                    "Pyr_Name":""
                },
                "Do_TxnDtl":[
                    {
                        "SubMerchUID":"' . $merchant_uid . '",
                        "Txn_AMT":"' . $amount . '"
                    }
                ],
                "Do_TxnHdr":{
                    "BKY_Txn_UID":"",
                    "Merch_Txn_UID":"' . $order_id . '",
                    "PayFor":"ECom",
                    "PayMethod":"' . $method . '",
                    "Txn_HDR":"' . $order_id . '",
                    "hashMac":"' . $HashedData . '"
                }
            }',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json'
                    ),
                ));
                $response = json_decode(curl_exec($curl), true);
                curl_close($curl);
                if (!isset($response['PayUrl'])) {
                    return $this->failedResponse(trans("$string_file.data_not_found"));
                }
            } else {
                return $this->failedResponse(trans("$string_file.config") . " " . trans("$string_file.data_not_found"));
            }
        } catch (Exception $exception) {
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"), $response['PayUrl']);
    }

    public function BookeeySuccess(Request $request)
    {
        $order_id = $request['merchantTxnId'];
        $reference_id = $request['txnId'];
        $response = $this->BookeeyCheckStatus($order_id, $reference_id);
        return view('payment/bookeey/callback', compact('response'));
    }

    public function BookeeyCheckStatus($order_id, $reference_id, $message = NULL)
    {
        $transaction = DB::table('bookeey_transactions')->where(['order_id' => $order_id])->first();
        if (!empty($transaction)) {
            $payment_option = PaymentOption::where('slug', 'BOOKEEY')->first();
            $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $transaction->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
            $merchant_uid = $paymentOption->api_public_key;
            $product_id = $paymentOption->api_public_key;
            $HashedData = hash('sha512', $merchant_uid . '|' . $product_id);

            $curl = curl_init();
            $url = $paymentOption->gateway_condition == 2 ? "https://apps.bookeey.com/pgapi/api/payment/paymentstatus" : "https://pg.bookeey.com/internalapi/api/payment/paymentstatus";
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POSTFIELDS => '{
                "Mid":"' . $merchant_uid . '",
                "MerchantTxnRefNo":[
                    "' . $order_id . '"
                ],
                "HashMac":"' . $HashedData . '"
            }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = json_decode(curl_exec($curl), true);
            curl_close($curl);

            $transaction->ref_id = $reference_id;
            $transaction->payment_id = $response['PaymentStatus'][0]['PaymentId'] ?? NULL;
            $transaction->status = $message ?? $response['PaymentStatus'][0]['StatusDescription'];
            $transaction->updated_at = Carbon::now('UTC');
            $transaction->save();

            if ($transaction->payment_status == 0 && isset($response['PaymentStatus'][0]['StatusDescription']) && $response['PaymentStatus'][0]['StatusDescription'] == 'Transaction Success') {
                if ($transaction->user_id != NULL) {
                    $user = User::find($transaction->user_id);
                    $paramArray = array(
                        'user_id' => $user->id,
                        'booking_id' => NULL,
                        'amount' => $transaction->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => "Bookeey Pay Transaction",
                    );
                    WalletTransaction::UserWalletCredit($paramArray);
                } else {
                    $driver = Driver::find($transaction->user_id);
                    $paramArray = array(
                        'driver_id' => $driver->id,
                        'booking_id' => NULL,
                        'amount' => $transaction->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => "Bookeey Pay Transaction",
                    );
                    WalletTransaction::WalletCredit($paramArray);
                }
                $transaction->payment_status = 1;
                $transaction->save();
            }
            return $response['PaymentStatus'][0]['StatusDescription'] ?? NULL;
        } else {
            return "Order not found";
        }
    }

    public function BookeeyFail(Request $request)
    {
        $order_id = $request['merchantTxnId'];
        $message = $request['errorMessage'];
        $response = $this->BookeeyCheckStatus($order_id, NULL, $message);
        return view('payment/bookeey/callback', compact('response'));
    }

    // payfast response

    public function payFastSuccess(Request $request)
    {
        $data = $request->all();
        Log::channel('payfast_api')->emergency($data);
    }

    // payfast response
    public function payFastCancel(Request $request)
    {
        $data = $request->all();
        Log::channel('payfast_api')->emergency($data);
    }


//    public function airtelSouthAfrica(Request $request)
//    {
//        $client_id = "1ea009eb-7a07-462f-9502-2004634e6959";
//        $secret_key = "1ea009eb-7a07-462f-9502-2004634e6959";
//        $url = "https://openapiuat.airtel.africa/auth/oauth2/token";
//        require 'vendor/autoload.php';
//        $headers = array(
//            'Content-Type' => 'application/json',
//        );
//        $client = new GuzzleHttpClient();
//// Define array of request body.
//        $request_body = array();
//        try {
//            $response = $client->request('POST','https://openapiuat.airtel.africa/auth/oauth2/token', array(
//                    'headers' => $headers,
//                    'json' => $request_body,
//                )
//            );
//            print_r($response->getBody()->getContents());
//        }
//        catch (GuzzleHttpExceptionBadResponseException $e) {
//            // handle exception or api errors.
//            print_r($e->getMessage());
//        }
//    }

//onefix payment gateway
    public function onefix()
    {
        $Header = json_encode([
            'typ' => "JWT",
            'alg' => "HS256",
            'cty' => "appotapay-api;v=1"
        ]);

        $Payload = json_encode([
            "iss" => "TEST",
            "jti" => "oMhJpkz7K6HDcR6S" . "-" . time(),
            "api_key" => "oMhJpkz7K6HDcR6S",
            "exp" => ""

        ]);


        $JWTSignature = HMACSHA256(
            base64UrlEncode($Header) . "." . base64UrlEncode($Payload),
            'DcR6S0pkz7K6HMqTzf1a5suBJk2WoMhJ'
        );


        $base64UrlHeader = strtr(base64_encode($Header), '+/', '-_');
        $base64UrlHeader = rtrim($base64UrlHeader, '=');
        $base64UrlHeader = $base64UrlHeader . PHP_EOL;

        $base64UrlPayload = strtr(base64_encode($Payload), '+/', '-_');
        $base64UrlPayload = rtrim($base64UrlPayload, '=');
        $base64UrlPayload = $base64UrlPayload . PHP_EOL;


        $JWTSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'DcR6S0pkz7K6HMqTzf1a5suBJk2WoMhJ');

        $base64UrlSignature = strtr(base64_encode($JWTSignature), '+/', '-_');
        $base64UrlSignature = rtrim($base64UrlSignature, '=');
        $base64UrlSignature = $base64UrlSignature . PHP_EOL;

        $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    }


    // edahab payment gateway
    public function edahabRequest($request,$payment_option_config,$calling_from)
    {

        try{
// p($payment_option_config);
            $currency = "";
            if($calling_from == "DRIVER")
            {
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
                $currency = $driver->Country->isoCode;
            }
            else
            {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
                $currency = $user->Country->isoCode;
            }
            //         $apiKey = "sIVrFOFCTx3ioNuYevWzb5LsH6XCUYIBEFWQiBzET";
            //         $edahabNumber = 654078924;
            //         $amount = 100;
            //         $agentCode = "708557";
            //         $hashed_param = hash('SHA256', $json_param."kQEnEmYQK2eixHq4AVIUWVYKD5YTAKvIHDvOTB");

            $transactionId = 'Transc_'.time();
            $apiKey = $payment_option_config->api_public_key;
            $edahabNumber = $request->edahab_number;
            $amount = $request->amount;
            $agentCode = $payment_option_config->auth_token;
            $returnUrl = route('edahab-return',['transactionId'=>$transactionId]);

            $arr = [
                'apiKey' => $apiKey,
                'edahabNumber' => $edahabNumber,
                'agentCode' => (string)$agentCode,
                'returnUrl' => $returnUrl,
                'amount' => (int)$amount,
                'currency' => 'SLSH',
            ];
// p($arr);
            $json_param = json_encode($arr, JSON_UNESCAPED_SLASHES);
            $hashed_param = hash('SHA256', $json_param.$payment_option_config->api_secret_key);
// p($hashed_param);
            $url = "https://edahab.net/api/api/IssueInvoice?hash=".$hashed_param;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json_param,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $result = curl_exec($curl);
            $response = json_decode($result, true);
            curl_close($curl);
            if(isset($response['StatusDescription']) && $response['StatusDescription'] == "Success" && $response['StatusCode'] == 0){
                $invoiceId =  $response['InvoiceId'];
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $transactionId,
                    'amount' => $request->amount,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'request_status'=> 1,
                    'reference_id'=> $invoiceId,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            else{
                throw new \Exception($response['StatusDescription']);
            }

            DB::commit();

            return [
                'status' => 'NEED_TO_OPEN_WEBVIEW',
                'url' => 'https://edahab.net/api/payment?invoiceId='.$invoiceId,
                'success_url' => route('edahab-success'),
                'fail_url' => route('edahab-fail'),
            ];

//        "InvoiceStatus":"paid" success case
//        {"InvoiceStatus":"Unpaid","InvoiceId":1444361,"StatusCode":0,"RequestId":4444218,"StatusDescription":"Success","ValidationErrors":null}

        }catch (\Exception $e)
        {
            throw new \Exception($e->getMessage());
        }
    }
    
    public function edahabReturn(Request $request)
    {
        \Log::channel('edahab_redirect')->emergency($request->all());
        $transaction = DB::table('transactions')->where('payment_transaction_id',$request->transactionId)->first();
        if(!empty($transaction)){
            $merchant_id = $transaction->merchant_id;
            $invoiceId = $transaction->reference_id;
            $payment_option = PaymentOption::where('slug', 'EDAHAB')->first();
            $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
            $string_file = $this->getStringFile($merchant_id);
            if (empty($paymentConfig)) {
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            }
            $apiKey = $paymentConfig->api_public_key;
            
            $arr = [
                'apiKey'=> $apiKey,
                'invoiceId'=> $invoiceId
            ];
            
            $json_param = json_encode($arr, JSON_UNESCAPED_SLASHES);
            $hashed_param = hash('SHA256', $json_param.$paymentConfig->api_secret_key);
            
            $url = "https://edahab.net/api/api/CheckInvoiceStatus?hash=".$hashed_param;
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json_param,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $result = curl_exec($curl);
            $response = json_decode($result, true);
            curl_close($curl);
            
            if(isset($response['InvoiceStatus']) && $response['InvoiceStatus'] == 'Paid' && $response['StatusCode'] == 0)
            {
                \Log::channel('edahab_redirect')->emergency($apiKey);
                DB::table('transactions')->where('payment_transaction_id',$request->transactionId)->update([
                    'reference_id' => $invoiceId,
                    'request_status' => 2,
                    'status_message' => "Success",
                ]);
            }
            else
            {
                DB::table('transactions')->where('payment_transaction_id',$request->transactionId)->update([
                    'reference_id' => $invoiceId,
                    'request_status' => 3,
                    'status_message' => "failed",
                ]);
                if(isset($response['InvoiceStatus']) && $response['InvoiceStatus'] == 'Unpaid' && $response['StatusCode'] == 0)
                {
                    throw new \Exception("Request was denied, please try again");
                }
                else
                {
                    throw new \Exception($response['StatusDescription']);
                }
            }
        }
    }

    public function edahabSuccess(Request $request)
    {
        \Log::channel('edahab_redirect')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function edahabFail(Request $request)
    {
        \Log::channel('edahab_redirect')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }

    public function MOMOPayRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|integer',
            'amount' => 'required',
            'currency' => 'required',
            'phone' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        if ($request->type == 1) {
            $user = $request->user('api');
        } elseif ($request->type == 2) {
            $user = $request->user('api-driver');
        } else {
            return $this->failedResponse("Invalid Type");
        }

        $string_file = $this->getStringFile($user->merchant_id);
        $payment_option = PaymentOption::where('slug', 'MOMOPAY')->first();
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        if (empty($paymentConfig)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }

        $token = $this->MOMOPayToken($paymentConfig->api_public_key, $paymentConfig->api_secret_key, $paymentConfig->auth_token);
        if (empty($token)) {
            return $this->failedResponse("$string_file.token_not_generated");
        }
        $callback = route('momo.callback');
        $uuid = $this->MOMOPayUUID();
//        $live_key = 'a49103f9fca043ccb38a43e960c344aa';
        $live_key = $paymentConfig->auth_token;
        $targetEnv = $paymentConfig->operator;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
          "amount": "' . $request->amount . '",
          "currency": "' . $request->currency . '",
          "externalId": "' . $uuid . '",
          "payer": {
            "partyIdType": "MSISDN",
            "partyId": "' . $request->phone . '"
          },
          "payerMessage": "Wallet Recharge",
          "payeeNote": "Wallet Recharge"
        }',
            CURLOPT_HTTPHEADER => array(
                'X-Reference-Id: ' . $uuid,
                'X-Target-Environment: '. $targetEnv,
                'Ocp-Apim-Subscription-Key: ' . $live_key,
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                // 'X-Callback-Url: ' . $callback
            ),
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $request_data = ["amount"=> $request->amount,'currency'=> $request->currency,'externalId'=> $uuid,'payer'=> ['partyType'=>"MSISDN","partyId"=> $request->phone]];
        $headersData = ["X-Reference-Id"=> $uuid,"X-Target-Environment"=>$targetEnv,"Ocp-Apim-Subscription-Key"=> $live_key,"Authorization"=> "Bearer ".$token];
        // dd('https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay',$request_data,$headersData,$httpcode,'{
        //   "amount": "' . $request->amount . '",
        //   "currency": "' . $request->currency . '",
        //   "externalId": "' . $uuid . '",
        //   "payer": {
        //     "partyIdType": "MSISDN",
        //     "partyId": "' . $request->phone . '"
        //   },
        //   "payerMessage": "Wallet Recharge",
        //   "payeeNote": "Wallet Recharge"
        // }');
        curl_close($curl);
         \Log::channel('momopay_api')->emergency(['time'=> date('d-m-Y'),'httpcode'=> $httpcode]);
        // dd($httpcode);
        if ($httpcode == 202) {
            DB::table('mpessa_transactions')->insert([
                'merchant_id' => $user->merchant_id,
                'user_id' => $user->id,
                'type' => $request->type,
                'amount' => $request->amount,
                'checkout_request_id' => $uuid,
                'payment_status'=>'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $data =  array('transaction_id'=> $uuid,'access_token'=>$token);
            return $this->successResponse(trans("$string_file.transaction_process"),$data);
        } else {
            // return array('result' => "0", 'message' => "Something went wrong");
            return $this->failedResponse(trans("$string_file.some_thing_went_wrong"));
        }
    }

    public function MOMOPayToken($api_user, $api_key, $subscription_key)
    {
//        $txt = 'e4c07e8a-cfb1-4545-9243-90fb8e28be00:dd70fdd4b1c94d57b82af036c214069b';
        $key_text = $api_user . ':' . $api_key;
        $token = base64_encode($key_text);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://proxy.momoapi.mtn.com/collection/token/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Length: 0',
                'Ocp-Apim-Subscription-Key: ' . $subscription_key,
                'Authorization: Basic ' . $token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response_token = '';
        $response = json_decode($response, true);
        if (in_array('access_token', $response)) {
            return $response['access_token'];
        } else {
            return $response_token;
        }
    }

    function MOMOPayUUID()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function MOMOCallback(Request $request)
    {
        // $data = $request->all();
        // Log::channel('momopay_api')->emergency($data);
        // if ($data['status'] == 'SUCCESSFUL') {
        //     $externalId = $data['externalId'];
        //     $trans = DB::table('mpessa_transactions')->where(['payment_status' => null, 'checkout_request_id' => $externalId])->first();
        //     if (!empty($trans)) {
        //         DB::table('mpessa_transactions')
        //             ->where(['checkout_request_id' => $externalId])
        //             ->update(['payment_status' => 'success', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);

//                $message = trans('api.message65');
//                $data = ['result' => '1', 'amount' => $trans->amount, 'message' => $message];
                // if ($trans->type == 1) {
                //     $receipt = "Application : " . $trans->checkout_request_id;
                //     $paramArray = array(
                //         'user_id' => $trans->user_id,
                //         'booking_id' => NULL,
                //         'amount' => $trans->amount,
                //         'narration' => 2,
                //         'platform' => 2,
                //         'payment_method' => 2,
                //         'receipt' => $receipt,
                //         'transaction_id' => $trans->checkout_request_id,
                //         'notification_type' => 89
                //     );
                //     WalletTransaction::UserWalletCredit($paramArray);
                // } else {
                //     $receipt = "Application : " . $trans->checkout_request_id;
                //     $paramArray = array(
                //         'driver_id' => $trans->user_id,
                //         'booking_id' => NULL,
                //         'amount' => $trans->amount,
                //         'narration' => 2,
                //         'platform' => 2,
                //         'payment_method' => 3,
                //         'receipt' => $receipt,
                //         'transaction_id' => $trans->checkout_request_id,
                //         'notification_type' => 89
                //     );
                //     WalletTransaction::WalletCredit($paramArray);
                // }
        //     }
        // } else {
        //     $trans = DB::table('mpessa_transactions')->where(['payment_status' => null, 'checkout_request_id' => $data['externalId']])->first();
        //     if (!empty($trans)) {
        //         DB::table('mpessa_transactions')
        //             ->where(['checkout_request_id' => $data['externalId']])
        //             ->update(['payment_status' => 'Failed', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);

        //         $message = 'Failed';
        //         $data = ['result' => '0', 'amount' => $trans->amount, 'message' => $message];
        //         $merchant_id = $trans->merchant_id;
        //         if ($trans->type == 1) {
        //             Onesignal::UserPushMessage($trans->user_id, $data, $message, 89, $merchant_id);
        //         } else {
        //             Onesignal::DriverPushMessage($trans->user_id, $data, $message, 89, $merchant_id);
        //         }
        //     }
        // }
    }

    public function MomoPaymentStatus(Request $request){
        // Log::channel('momopay_api')->emergency($request->all());
        $transactionId = $request->transaction_id;
        $token = $request->access_token;
        $callingFrom = $request->calling_from;
        if ($callingFrom == "USER") {
            $user = $request->user('api');
        } elseif ($callingFrom == "DRIVER") {
            $user = $request->user('api-driver');
        } else {
            return $this->failedResponse("Invalid calling from data");
        }
        $transaction_table =  DB::table("mpessa_transactions")->where('checkout_request_id', $transactionId)->first();
            $transactionStatus = $this->getMomoTransactionStatus($user,$transaction_table->payment_status,$transactionId,$token,$callingFrom, $request);
            $payment_status =   $transaction_table->payment_status == 'success' ?  true : false;
            $data = [];
            if($transaction_table->payment_status == 'pending')
            {
                $request_status_text = "processing";
                $transaction_status = 1;
                $data = ['payment_status' => 1, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }
            else if($transaction_table->payment_status == 'success')
            {
                $request_status_text = "success";
                $transaction_status = 2;
                $data = ['payment_status' => 2, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }
            else if($transaction_table->payment_status == 'failed')
            {
                $request_status_text = "failed";
                $transaction_status = 3;
                $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                
            }else{
                $request_status_text = "processing";
                $transaction_status = 1;
                $data = ['payment_status' => 1, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            }
            
            return $data;
    }
    
    public function getMomoTransactionStatus($user,$payment_status,$transactionId,$token,$calling_from, $request){
        $string_file = $this->getStringFile($user->merchant_id);
        $payment_option = PaymentOption::where('slug', 'MOMOPAY')->first();
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        if (empty($paymentConfig)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        
        $callback = route('momo.callback');
        $live_key = $paymentConfig->auth_token;
        $targetEnv = $paymentConfig->operator;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay/'.$transactionId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                'X-Reference-Id: ' . $transactionId,
                'X-Target-Environment: '. $targetEnv,
                'Ocp-Apim-Subscription-Key: ' . $live_key,
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                // 'X-Callback-Url: ' . $callback
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response,true);
        \Log::channel('momopay_api')->emergency(['time'=> date('d-m-Y') ,'payment_status_api'=>'Check Payment Status','url'=>'https://proxy.momoapi.mtn.com/collection/v1_0/requesttopay/'.$transactionId,'trans_id'=>$transactionId,'response'=>$res]);

        if(isset($res['financialTransactionId']) && $res['status'] == "SUCCESSFUL"){
            $externalId = $res['externalId'];
            $trans = DB::table('mpessa_transactions')->where(['checkout_request_id' => $externalId])->first();
            if (!empty($trans)) {
                if($trans->payment_status == 'success'){
                    return $trans;
                }
                else {
                    DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => $externalId])
                    ->update(['payment_status' => 'success', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($res, true)]);

                // if ($trans->type == 1) {
                //     $receipt = "Application : " . $trans->checkout_request_id;
                //     $paramArray = array(
                //         'user_id' => $trans->user_id,
                //         'booking_id' => NULL,
                //         'amount' => $trans->amount,
                //         'narration' => 2,
                //         'platform' => 2,
                //         'payment_method' => 2,
                //         'receipt' => $receipt,
                //         'transaction_id' => $trans->checkout_request_id,
                //         'notification_type' => 89
                //     );
                //     WalletTransaction::UserWalletCredit($paramArray);
                // } else {
                //     $receipt = "Application : " . $trans->checkout_request_id;
                //     $paramArray = array(
                //         'driver_id' => $trans->user_id,
                //         'booking_id' => NULL,
                //         'amount' => $trans->amount,
                //         'narration' => 2,
                //         'platform' => 2,
                //         'payment_method' => 3,
                //         'receipt' => $receipt,
                //         'transaction_id' => $trans->checkout_request_id,
                //         'notification_type' => 89
                //     );
                //     WalletTransaction::WalletCredit($paramArray);
                // }
                }
            }
        } elseif(isset($res['status']) && $res['status'] == "PENDING") {
            DB::table('mpessa_transactions')
                        ->where(['checkout_request_id' => $res['externalId']])
                        ->update(['payment_status' => 'pending', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);
        }elseif(isset($res['status']) && $res['status'] == "FAILED"){
            if(count($res) > 0){
                $trans = DB::table('mpessa_transactions')->where(['checkout_request_id' => $res['externalId']])->first();
                if (!empty($trans)) {
                    DB::table('mpessa_transactions')
                        ->where(['checkout_request_id' => $res['externalId']])
                        ->update(['payment_status' => 'failed', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);
    
                    $message = 'Failed';
                    $data = ['result' => '0', 'amount' => $trans->amount, 'message' => $message];
                    $merchant_id = $trans->merchant_id;
                    // dd($trans->user_id, $data, $message, 89, $merchant_id, $trans);
                    // if ($trans->type == 1) {
                    //     Onesignal::UserPushMessage($trans->user_id, $data, $message, 89, $merchant_id);
                    // } else {
                    //     Onesignal::DriverPushMessage($trans->user_id, $data, $message, 89, $merchant_id);
                    // }
                    
                    $arr_param = [];
                    // $arr_param['data'] = $data;
                    
                    // $arr_param['merchant_id'] = $merchant_id;
                    // $arr_param['large_icon'] = "";
                    // $arr_param['message'] = $message;
                    // $arr_param['title'] = $message;

                    // if ($trans->type == 1) {
                    //     $arr_param['user_id'] = $trans->user_id;
                    //     Onesignal::UserPushMessage($arr_param);
                    // } else {
                    //     $arr_param['driver_id'] = $trans->user_id;
                    //     Onesignal::DriverPushMessage($arr_param);
                    // }
                }
            }
            else{
                return response()->json('Access Token is expired for this trasaction,Please do payment again');
            }
            
        }
        
        return $res;
    }


    public function coveragePay($request,$payment_option_config,$calling_from)
    {

        try{
            $amount = $request->amount;
            if($calling_from == "DRIVER")
            {
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
            }
            else
            {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
            }

            $transaction_id = $id.'_'.time();
            DB::table('transactions')->insert([
                'status' => 1, // for user
                'reference_id' => "",
                'card_id' => NULL,
                'user_id' => $calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'merchant_id' => $merchant_id,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'checkout_id' => NULL,
                'booking_id' => $request->booking_id,
                'order_id' => $request->order_id,
                'handyman_order_id' => $request->handyman_order_id,
                'payment_transaction_id' => $transaction_id,
                'payment_transaction' => NULL,
                'request_status' => 1,
                'status_message' => $description,
                'amount'=>$amount
            ]);

// p('in');
            $ssl_pin = $payment_option_config->api_secret_key;
            $ssl_user_id = $payment_option_config->api_public_key;
            $ssl_merchant_account = $payment_option_config->auth_token;
            $card_number = $request->card_number;
            $card_pin = $request->cvv;
            $expire_date = $request->expire_date;


            $url = "https://api.convergepay.com/VirtualMerchant/processxml.do";
// p('<txn>
//                     <ssl_merchant_id>'.$ssl_merchant_account.'</ssl_merchant_id>
//                     <ssl_user_id>'.$ssl_user_id.'</ssl_user_id>
//                     <ssl_pin>'.$ssl_pin.'</ssl_pin>
//                     <ssl_transaction_type>ccsale</ssl_transaction_type>
//                     <ssl_card_number>'.$card_number.'</ssl_card_number>
//                     <ssl_exp_date>'.$expire_date.'</ssl_exp_date>
//                     <ssl_amount>'.$amount.'</ssl_amount>
//                     <ssl_cvv2cvc2_indicator>1</ssl_cvv2cvc2_indicator>
//                     <ssl_cvv2cvc2>'.$card_pin.'</ssl_cvv2cvc2>
//                     </txn>');
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => '<txn>
                    <ssl_merchant_id>'.$ssl_merchant_account.'</ssl_merchant_id>
                    <ssl_user_id>'.$ssl_user_id.'</ssl_user_id>
                    <ssl_pin>'.$ssl_pin.'</ssl_pin>
                    <ssl_transaction_type>ccsale</ssl_transaction_type>
                    <ssl_card_number>'.$card_number.'</ssl_card_number>
                    <ssl_exp_date>'.$expire_date.'</ssl_exp_date>
                    <ssl_amount>'.$amount.'</ssl_amount>
                    <ssl_cvv2cvc2_indicator>1</ssl_cvv2cvc2_indicator>
                    <ssl_cvv2cvc2>'.$card_pin.'</ssl_cvv2cvc2>
                    <ssl_get_token>Y</ssl_get_token>
                    <ssl_add_token>Y</ssl_add_token>
                    </txn>',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: NjE4NzEzMWE0MDM4ZDcwOWJhMWRlOGI4YzY1NDU4ZGE4ZTc0ZjA0ZmQ0OTBjZTM1ZjA1YWRlZjY3ZWY1N2FjMA',
                    'Cookie: JSESSIONID=J97nnhiuWjVHPurG9iVN5VX-61aOBs5ImWdxqJ-l'
                ),
            ));
            $response = curl_exec($curl);
            //  $xml = json_encode($response);
            $xml = new SimpleXMLElement($response);
            $xml = json_encode($xml);
            $xml = json_decode($xml, true);

            curl_close($curl);
            if(isset($xml['errorCode']) && !empty($xml['errorCode']))
            {

                // DB::table('transactions')->where('payment_transaction_id',$transaction_id)->update([
                //     'reference_id' => $response['InvoiceId'],
                //     'request_status' => 2,
                //     'status_message' => "Success",
                // ]);


                DB::table('transactions')->where('payment_transaction_id',$transaction_id)->update([
                    'reference_id' => NULL,
                    'request_status' => 3,
                    'status_message' => $xml['errorName'],
                ]);
                throw new \Exception($xml['errorMessage']);
            }

            if(isset($xml['ssl_result']) && $xml['ssl_result'] == 0)
            {

                DB::table('transactions')->where('payment_transaction_id',$transaction_id)->update([
                    'reference_id' => $xml['ssl_txn_id'],
                    'request_status' => 2,
                    'status_message' => "Success",
                ]);

                // save card
                if(isset($xml['ssl_get_token']) && $xml['ssl_get_token'] == 'Y')
                {

                    $expire_month= substr($expire_date, 0, 2);
                    $expire_year = substr('0227', -2, 2);
                    if($calling_from == "DRIVER")
                    {
//                        driver card
                      $driver_card =  DriverCard::where([['card_number','=',$card_number],['payment_option_id','=',
                      $payment_option_config->payment_option_id],['driver_id','=',$id]])->first();

                      if(empty($driver_card))
                      {
                          $driver_card = new DriverCard;
                          $driver_card->card_number = $card_number;
                          $driver_card->exp_month = $expire_month;
                          $driver_card->exp_year = $expire_year;
                          $driver_card->driver_id = $id;
                          $driver_card->payment_option_id = $payment_option_config->payment_option_id;
                          $driver_card->card_type = $xml['ssl_card_short_description'];
                      }
                        $driver_card->token = $xml['ssl_token'];
                        $driver_card->save();
                    }
                    else
                    {
                        // user card
                        $user_card =  UserCard::where([['card_number','=',$card_number],['payment_option_id','=',
                            $payment_option_config->payment_option_id],['user_id','=',$id]])->first();
                        if(empty($user_card))
                        {
                            $user_card = new UserCard;
                            $user_card->card_number = $card_number;
                            $user_card->exp_month = $expire_month;
                            $user_card->exp_year = $expire_year;
                            $user_card->card_type = $xml['ssl_card_short_description'];
                            $user_card->user_id = $id;
                            $user_card->payment_option_id = $payment_option_config->payment_option_id;
                            $user_card->status = 1;
                        }
                        $user_card->token = $xml['ssl_token'];
                        $user_card->save();
                    }
                }
            }
            else
            {
                DB::table('transactions')->where('payment_transaction_id',$transaction_id)->update([
                    'reference_id' => isset($xml['ssl_txn_id']) ? $xml['ssl_txn_id'] : NULL,
                    'request_status' => 3,
                    'status_message' => "failed",
                ]);

                throw new \Exception($xml['ssl_result_message']);
            }


//        "InvoiceStatus":"paid" success case
//        {"InvoiceStatus":"Unpaid","InvoiceId":1444361,"StatusCode":0,"RequestId":4444218,"StatusDescription":"Success","ValidationErrors":null}

        }catch (\Exception $e)
        {
            throw new \Exception($e->getMessage());
        }
    }


    public function coverageCardPayment($amount,$card,$user,$payment_option_config,$bookingId = NULL, $order_id =
    NULL,$handyman_order_id = NULL,$calling_from = "USER"){

        try{

                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
                $transaction_id = $id.'_'.time();
            DB::table('transactions')->insert([
                'status' => 1, // for user
                'reference_id' => "",
                'card_id' => NULL,
                'user_id' =>$calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'merchant_id' => $merchant_id,
                'payment_option_id' => $card->payment_option_id,
                'checkout_id' => NULL,
                'booking_id' => $bookingId,
                'order_id' => $order_id,
                'handyman_order_id' => $handyman_order_id,
                'payment_transaction_id' => $transaction_id,
                'payment_transaction' => NULL,
                'request_status' => 1,
                'status_message' => $description,
                'amount'=>$amount
            ]);


            $ssl_pin = $payment_option_config->api_secret_key;
            $ssl_user_id = $payment_option_config->api_public_key;
            $ssl_merchant_account = $payment_option_config->auth_token;

            $url = "https://api.convergepay.com/VirtualMerchant/processxml.do";
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => '<txn>
                    <ssl_merchant_id>'.$ssl_merchant_account.'</ssl_merchant_id>
                    <ssl_user_id>'.$ssl_user_id.'</ssl_user_id>
                    <ssl_pin>'.$ssl_pin.'</ssl_pin>
                    <ssl_transaction_type>ccsale</ssl_transaction_type>
                    <ssl_amount>'.$amount.'</ssl_amount>
                    <ssl_token>'.$card->token.'</ssl_token>
                    </txn>',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: NjE4NzEzMWE0MDM4ZDcwOWJhMWRlOGI4YzY1NDU4ZGE4ZTc0ZjA0ZmQ0OTBjZTM1ZjA1YWRlZjY3ZWY1N2FjMA',
                    'Cookie: JSESSIONID=J97nnhiuWjVHPurG9iVN5VX-61aOBs5ImWdxqJ-l'
                ),
            ));
            $response = curl_exec($curl);
            //  $xml = json_encode($response);
            $xml = new SimpleXMLElement($response);
            $xml = json_encode($xml);
            $xml = json_decode($xml, true);

            curl_close($curl);
            if(isset($xml['errorCode']) && !empty($xml['errorCode']))
            {
                DB::table('transactions')->where('payment_transaction_id',$transaction_id)->update([
                    'reference_id' => NULL,
                    'request_status' => 3,
                    'status_message' => $xml['errorName'],
                ]);
                throw new \Exception($xml['errorMessage']);
            }

            if(isset($xml['ssl_result']) && $xml['ssl_result'] == 0)
            {

                DB::table('transactions')->where('payment_transaction_id',$transaction_id)->update([
                    'reference_id' => $xml['ssl_txn_id'],
                    'request_status' => 2,
                    'status_message' => "Success",
                ]);
                return true;
            }
            else
            {
                DB::table('transactions')->where('payment_transaction_id',$transaction_id)->update([
                    'reference_id' => isset($xml['ssl_txn_id']) ? $xml['ssl_txn_id'] : NULL,
                    'request_status' => 3,
                    'status_message' => "failed",
                ]);
                throw new \Exception($xml['ssl_result_message']);
            }

        }catch (\Exception $e)
        {
            throw new \Exception($e->getMessage());
        }
    }

    // edahab payment gateway
    public function zaadRequest($request,$payment_option_config,$calling_from)
    {

        try{
            if($calling_from == "DRIVER")
            {
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
            }
            else
            {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
            }

            $transaction_id = $id.'_'.time();
            DB::table('transactions')->insert([
                'status' => 1, // for user
                'reference_id' => "",
                'card_id' => NULL,
                'user_id' => $calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'merchant_id' => $merchant_id,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'checkout_id' => NULL,
                'booking_id' => $request->booking_id,
                'order_id' => $request->order_id,
                'handyman_order_id' => $request->handyman_order_id,
                'payment_transaction_id' => $transaction_id,
                'payment_transaction' => NULL,
                'request_status' => 1,
                'status_message' => $description,
            ]);

            $mwallet_amount = str_replace("063","25263",$request->mwallet_account_number);

            // pre authorize, means hold the amount in wallet/account
            $arr = [
                'schemaVersion' => "1.0",
                'requestId' => $transaction_id,
                'timestamp' => date('Y-m-d H:i:s'),
                'channelName' => "WEB",
                'serviceName' => "API_PREAUTHORIZE",
                'serviceParams' => [
//                    'merchantUid' => "M0910353",
                    'merchantUid' => $payment_option_config->auth_token,
//                    'apiUserId' => "1000573",
                    'apiUserId' => $payment_option_config->api_public_key,
//                    'apiKey' => "API-975062629AHX",
                    'apiKey' => $payment_option_config->api_secret_key,
                    'paymentMethod' => "MWALLET_ACCOUNT",
                    'payerInfo'=>[
                        'accountNo'=>$mwallet_amount,
                    ],
                    'pgAccountId'=>'20001250',
                    'transactionInfo'=>[
                        'referenceId'=>time(),
                        'invoiceId'=>$transaction_id,
                        'amount'=>$request->amount,
                        'currency'=>"SLSH",
                        'description'=>$description
                    ]
                ],
            ];

            $url = "https://api.waafi.com/asm";
            $json_param = json_encode($arr);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json_param,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $result = curl_exec($curl);
            $response = json_decode($result, true);
//            p($response);

            if(isset($response['params']['state']) && $response['params']['state'] == 'APPROVED')
            {

                $pre_authorize_transaction_id = $response['params']['transactionId'];
                // authorize commit
                $arr_v1 = [
                    'schemaVersion' => "1.0",
                    'requestId' => $pre_authorize_transaction_id,
                    'timestamp' => "2021-11-26 Standard",
                    'channelName' => "WEB",
                    'serviceName' => "API_PREAUTHORIZE_COMMIT",
                    'serviceParams' => [
                        'merchantUid' => $payment_option_config->auth_token,
                        'apiUserId' => $payment_option_config->api_public_key,
                        'apiKey' => $payment_option_config->api_secret_key,
                        'transactionId' => $pre_authorize_transaction_id,
                        'description'=>"Commited",
                        'referenceId'=>time(),
                    ],
                ];

                $json_param1 = json_encode($arr_v1);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $json_param1,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json'
                    ),
                ));
                $result = curl_exec($curl);
                $commit_response = json_decode($result, true);

                curl_close($curl);
                if (isset($commit_response['params']['state']) && $commit_response['params']['state'] == 'APPROVED') {

                    DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                        'reference_id' => $transaction_id,
                        'request_status' => 2,
                        'status_message' => "Success",
                    ]);
                } else {
                    DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
                        'reference_id' => $transaction_id,
                        'request_status' => 3,
                        'status_message' => $commit_response['responseMsg'],
                    ]);
                }
            }
            else
            {
                throw new \Exception($response['responseMsg']);
            }

        }catch (\Exception $e)
        {
            throw new \Exception($e->getMessage());
        }
    }


    public function JazzCash(Request $request){
        $validator = Validator::make($request->all(),[
            'amount' => 'required',
            'currency' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }

        $user = $request->type == 1 ? $request->user('api') : $request->user('api-driver');
        $payment_config = PaymentOptionsConfiguration::where([['merchant_id','=',$user->merchant_id],['payment_gateway_provider','=','JAZZCASH']])->first();
        if (empty($payment_config)){
            return response()->json(['result' => "0", 'message' => 'Configuration Not Found', 'data' => []]);
        }

        $merchant_id = $payment_config->api_public_key;
        $password = $payment_config->api_secret_key;
        $date_time = date('YmdHis');
        $trans_ref_no = 'T'.$date_time;
        $bill_ref = $date_time;
        $amount = $request->amount*100;
        $currency = $request->currency;
        $return_url = \route('jazzcash.return');
        $salt = $payment_config->auth_token;
        $complete_url = \route('jazzcash.webview');
        $complete_url = $complete_url.'?merchant_id='.$merchant_id.'&password='.$password.'&date_time='.$date_time.'&trans_ref_no='.$trans_ref_no.'&amount='.$amount.'&currency='.$currency.'&salt='.$salt.'&bill_ref='.$bill_ref.'&return_url='.$return_url;
        $success_url = route('jazzcash.success');
        $fail_url = route('jazzcash.fail');
        return response()->json(['result' => "1", 'message' => 'Payment URL', 'data' => $complete_url,'success_url' => $success_url,'fail_url' => $fail_url]);
    }

    public function JazzCashWebView(Request $request){
        $merchant_id = $request->merchant_id;
        $password = $request->password;
        $date_time = $request->date_time;
        $trans_ref_no = $request->trans_ref_no;
        $amount = $request->amount;
        $currency = $request->currency;
        $return_url = $request->return_url;
        $salt = $request->salt;
        $bill_ref = $request->bill_ref;
        return view('payment.jazzcash.index',compact('merchant_id','password','date_time','trans_ref_no','amount','currency','salt','return_url','bill_ref'));
    }

    public function JazzCashReturn(Request $request){
        $msg = $request->pp_ResponseMessage;
        if ($request->pp_ResponseCode == '000'){
            $success_url = \route('jazzcash.success');
            header("Location: ".$success_url.'?msg='.$msg);
            exit();
        }else{
            $fail_url = \route('jazzcash.fail');
            header("Location: ".$fail_url.'?msg='.$msg);
            exit();
        }
    }

    public function JazzCashSuccess(Request $request){
        $response = $request->msg;
        return view('payment/jazzcash/callback', compact('response'));
    }

    public function JazzCashFail(Request $request){
        $response = $request->msg;
        return view('payment/jazzcash/callback', compact('response'));
    }

    //Mpesa B2C
    public function MpesaURLandFile($gateway_env,$type){
        if ($type == 1){
            if ($gateway_env == 1){
                $file_name = 'ProductionCertificate.cer';
            }else{
                $file_name = 'SandboxCertificate.cer';
            }
            return $file_name;
        }else{
            if ($gateway_env == 1){
                $url = 'https://api.safaricom.co.ke/';
            }else{
                $url = 'https://sandbox.safaricom.co.ke/';
            }
            return $url;
        }
    }

    public function getB2CAuthToken($consumer_key, $consumer_secret,$gateway_env){
        $token = base64_encode($consumer_key.':'.$consumer_secret);
        $url = $this->MpesaURLandFile($gateway_env,2);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'oauth/v1/generate?grant_type=client_credentials',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.$token
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);
        return $response->access_token;
    }

    public function generateB2CSecurityCredentials($password,$gateway_env){
        $file_name = $this->MpesaURLandFile($gateway_env,1);
        $crt_file_path = env('CERTIFICATE_FILE_PATH').$file_name;
        $fp = fopen($crt_file_path,"r");
        $publicKey = fread($fp,8192);
        fclose($fp);
        openssl_get_publickey($publicKey);
        openssl_public_encrypt($password, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);
    }

    public function submitB2CRequest(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'phone' => 'required',
            'amount' => 'required',
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        if($request->amount < 100){
            return $this->failedResponse('The amount must be at least 100.');
        }

        if ($request->type == 1) {
            $user = $request->user('api');
            $wallet_amount = $user->wallet_balance;
        } elseif ($request->type == 2) {
            $user = $request->user('api-driver');
            $wallet_amount = $user->wallet_money;
        } else {
            return $this->failedResponse("Invalid Type");
        }
        if($wallet_amount < $request->amount){
            return $this->failedResponse('Your Wallet is not having '.$request->amount.' amount');
        }

        try{
            $string_file = $this->getStringFile($user->merchant_id);
            $payment_option = PaymentOption::where('slug', 'MPESAB2C')->first();
            $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
            if (empty($paymentConfig)) {
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            }
            $auth_token = $this->getB2CAuthToken($paymentConfig->api_public_key,$paymentConfig->api_secret_key,$paymentConfig->gateway_condition);
            $security_credentials = $this->generateB2CSecurityCredentials($paymentConfig->password,$paymentConfig->gateway_condition);
            $url = $this->MpesaURLandFile($paymentConfig->gateway_condition,2);
            $callback_url = route('mpesa-lib.b2c.callback');
            $phone = str_replace('+', '', $request->phone);
            $passing_data = [
                'InitiatorName' => $paymentConfig->user_name,
                'SecurityCredential' => $security_credentials,
                'CommandID' => 'BusinessPayment',
                'Amount' => $request->amount,
                'PartyA' => $paymentConfig->auth_token,
                'PartyB' => $phone,
                'Remarks' => $user->Merchant->BusinessName.' Payment',
                'QueueTimeOutURL' => $callback_url,
                'ResultURL' => $callback_url,
                'Occasion' => $user->Merchant->BusinessName.' Payment',
            ];
            // dd($auth_token,$passing_data);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url.'mpesa-lib/b2c/v1/paymentrequest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => \GuzzleHttp\json_encode($passing_data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$auth_token,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $response = json_decode($response,true);
            if ($httpcode == 200 && $response['ResponseCode'] == 0 && !empty($response['ConversationID']) && !empty($response['OriginatorConversationID'])) {
                DB::table('mpessa_transactions')->insert([
                    'merchant_id' => $user->merchant_id,
                    'user_id' => $user->id,
                    'type' => $request->type,
                    'amount' => $request->amount,
                    'checkout_request_id' => $response['OriginatorConversationID'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                return array('result' => "1", 'message' => $response['ResponseDescription']);
            } else {
                return array('result' => "0", 'message' => "Something went wrong");
            }
        }catch (\Exception $e) {
            $response = json_decode($e->getMessage());
            $message = $response->errorMessage ?? $e->getMessage();
            return response()->json(['result' => "0", 'message' => $message, 'data' => '']);
        }
    }

    public function MpesaB2CRequestCallback(Request $request){
        $data = $request->all();
        \Log::channel('MpesaB2C_callback')->emergency($data);
        $result_data = $data['Result'];
        if ($result_data['ResultCode'] == 0) {
            $trans_id = $result_data['OriginatorConversationID'];
            $trans = DB::table('mpessa_transactions')->where(['payment_status' => null,'checkout_request_id' => $trans_id])->first();
            if (!empty($trans)) {
                DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => $trans_id])
                    ->update(['payment_status' => 'success', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);

                $message = trans('api.message65');
                $amount = $trans->amount;
                $type = $trans->type;
                $data = ['result' => '1', 'amount' => $amount, 'message' => $message];
                $merchant_id = $trans->merchant_id;
                if ($type == 1) {
                    $receipt = "Application : " . $trans_id;
                    $paramArray = array(
                        'user_id' => $trans->user_id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 17,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $trans_id,
                        'notification_type' => 89
                    );
                    WalletTransaction::UserWalletDebit($paramArray);
                } else {
                    $receipt = "Application : " . $trans_id;
                    $paramArray = array(
                        'driver_id' => $trans->user_id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 23,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $trans_id,
                        'notification_type' => 89
                    );
                    WalletTransaction::WalletDeduct($paramArray);
                }
            }

            $data = array(
                "amount" => $amount,
                "transcode" => $trans_id,
                "user_id" => $trans->user_id,
                "status" => "COMPLETE",
            );
            \Log::channel('MpesaB2C_callback')->emergency($data);
            exit;
        } else {
            $trans_id = $result_data['OriginatorConversationID'];
            $trans = DB::table('mpessa_transactions')->where(['payment_status' => null,'checkout_request_id' => $trans_id])->first();
            if (!empty($trans)) {
                DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => $trans_id])
                    ->update(['payment_status' => 'Failed', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);

                $message = $result_data['ResultDesc'];
                $amount = $trans->amount;
                $type = $trans->type;
                $data = ['result' => '0', 'amount' => $amount, 'message' => $message];
                $merchant_id = $trans->merchant_id;
                if ($type == 1) {
                    Onesignal::UserPushMessage($trans->user_id, $data, $message, 89, $merchant_id);
                } else {
                    Onesignal::DriverPushMessage($trans->user_id, $data, $message, 89, $merchant_id);
                }
            }

            $data = array(
                "amount" => $amount,
                "transcode" => $trans_id,
                "user_id" => $trans->user_id,
                "status" => "FAILED",
            );
            \Log::channel('MpesaB2C_callback')->emergency($data);
            exit;
        }
    }

    public function generateTeliberrUrl(){
        $api = 'http://196.188.120.3:11443/service-openup/toTradeWebPay';
        $appkey = '7e870232a16b4f89bf602cd13c2ede86';
        $publicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5MxPGUliTPEQ5Oq21Ej+86uMHi+hsr5P/RxiM88dhpm5SRcB6DS6P/Xx+RKtA7/LEpRFQLZdM3Nb5ZTTUiv7GvzRL/rkDedVvrM8/i0fqrao8hw0fsnUdvbYElf58ZQ/MHZSfMqJs7pE4ViK96+jGCWr2JsCyxYW110DT33Imit0H7Y5BdFWyo+tBdbJuCJvq8wiCSGpMfWSBbufygqmadP+OtQoGHZK+jSbsjr79Rnq9wLKj0mWS0UEMDg8+nlyOT7otSxS98uI3KJilyt5W2wniNLs10C6JCZihvfF4j+uRTHVK4YzvJaJsQDWIV77001iv6sMmCMJUPA6jhP1fQIDAQAB';

        $data=[
            'outTradeNo' => date('Ymd').time(),
            'subject' => 'testing payment',
            'totalAmount' => 10,
            'shortCode' => 220308,
            'notifyUrl' => $_POST['notifyUrl'],
            'returnUrl' => $_POST['returnUrl'],
            'receiveName' => 'Org Name',
            'appId' => '6e34f76fa3074b8babcf01843a066549',
            'timeoutExpress' => 30,
            'nonce' => $this->TeliberrUUID(),
            'timestamp' => time()
        ];
        ksort($data);
        $ussd = $data;
        $data['appKey'] = $appkey;
        ksort($data);
        $sign = $this->sign($data);
        $encode = [
            'appid' => $data['appId'],
            'sign' => $sign['sha256'],
            'ussd' => $this->encryptRSA(json_encode($ussd),$publicKey)
        ];

//        $result = [
//            'encode' => json_encode($encode),
//            'sign' => $sign['values'],
//            'ussd' => json_encode($ussd)
//        ];
//        echo json_encode($result);

        list($returnCode, $returnContent) = $this->http_post_json($api, json_encode($encode));
        if($returnCode == 200){
            $rsp = json_decode($returnContent,true);
            echo 'xxxxxxx'.$returnContent .'  \n'.$sign['values'];
            header('location:'.$rsp['data']['toPayUrl']);
        }else{
            echo 'Fail:'.$returnCode . '   '.$sign['values'];
        }
    }

    public function teliberrNotify(){
        $content = file_get_contents('php://input');
//        $api = 'http://196.188.120.3:11443/service-openup/toTradeWebPay';
//        $appkey = '7e870232a16b4f89bf602cd13c2ede86';
        $publicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5MxPGUliTPEQ5Oq21Ej+86uMHi+hsr5P/RxiM88dhpm5SRcB6DS6P/Xx+RKtA7/LEpRFQLZdM3Nb5ZTTUiv7GvzRL/rkDedVvrM8/i0fqrao8hw0fsnUdvbYElf58ZQ/MHZSfMqJs7pE4ViK96+jGCWr2JsCyxYW110DT33Imit0H7Y5BdFWyo+tBdbJuCJvq8wiCSGpMfWSBbufygqmadP+OtQoGHZK+jSbsjr79Rnq9wLKj0mWS0UEMDg8+nlyOT7otSxS98uI3KJilyt5W2wniNLs10C6JCZihvfF4j+uRTHVK4YzvJaJsQDWIV77001iv6sMmCMJUPA6jhP1fQIDAQAB';
        $nofityData = $this->decryptRSA($content, $publicKey);
        \Log::channel('teliberrPay')->emergency($nofityData);
    }

    public function sign($params){
        $signPars = '';
        foreach($params as $k => $v){
            if($signPars == ''){
                $signPars = $k.'='.$v;
            }else{
                $signPars = $signPars.'&'.$k.'='.$v;
            }
        }
        $sign = [
            'sha256' => hash("sha256", $signPars),
            'values' => $signPars
        ];
        return $sign;
    }

    public function encryptRSA($data, $public){
        $pubPem = chunk_split($public, 64, "\n");
        $pubPem = "-----BEGIN PUBLIC KEY-----\n" . $pubPem . "-----END PUBLIC KEY-----\n";

        $public_key = openssl_pkey_get_public($pubPem);
        if(!$public_key){
            die('invalid public key');
        }
        $crypto = '';
        foreach(str_split($data, 117) as $chunk){
            $return = openssl_public_encrypt($chunk, $cryptoItem, $public_key);
            if(!$return){
                return('fail');
            }
            $crypto .= $cryptoItem;
        }
        $ussd = base64_encode($crypto);
        return $ussd;
    }

    public function decryptRSA($source, $key) {
        $pubPem = chunk_split($key, 64, "\n");
        $pubPem = "-----BEGIN PUBLIC KEY-----\n" . $pubPem . "-----END PUBLIC KEY-----\n";
        $public_key = openssl_pkey_get_public($pubPem);
        if(!$public_key){
            return 'invalid public key';
        }
        $decrypted='';//decode must be done before spliting for getting the binary String
        $data=str_split(base64_decode($source),256);
        foreach($data as $chunk){
            $partial = '';//be sure to match padding
            $decryptionOK = openssl_public_decrypt($chunk,$partial,$public_key,OPENSSL_PKCS1_PADDING);
            if($decryptionOK===false){
                return 'fail';
            }
            $decrypted.=$partial;
        }
        return $decrypted;
    }

    public function TeliberrUUID()
    {
        return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function http_post_json($url, $jsonStr){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array($httpCode, $response);
    }

    public function teliberrCallback(){
        $content = file_get_contents('php://input');
        echo "This is static text from development end";
        print_r($content);
    }

    public function OrangeMoneyPush(Request $request){
        $validator = Validator::make($request->all(),[
            'amount' => 'required',
            'phone' => 'required',
            'otp' => 'required',
            'type' => 'required'
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == "USER" ? $request->user('api')->merchant_id :  $request->user('api-driver')->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $payment_option = PaymentOption::where('slug','ORANGEMONEY_PUSH')->first();
        $payment_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        if (empty($payment_config)){
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }

        // $url = $payment_config->gateway_condition == 1 ? "https://apiom.orange.bf:9007/payment" : "https://testom.orange.bf:9008/payment";
        $url = $payment_config->gateway_condition == 1 ? "https://apiom.orange.bf/" : "https://testom.orange.bf/";
        $refNum = 'Ref_'.date('YmdHis');
        $ext_txn_id = date('YmdHis');
        $amount = (int)$request->amount;
        $data = '<?xml version="1.0" encoding="UTF-8"?>
            <COMMAND>
                <TYPE>OMPREQ</TYPE>
                <customer_msisdn>'.$request->phone.'</customer_msisdn>
                <merchant_msisdn>'.$payment_config->auth_token.'</merchant_msisdn>
                <api_username>'.$payment_config->api_public_key.'</api_username>
                <api_password>'.$payment_config->api_secret_key.'</api_password>
                <amount>'.$request->amount.'</amount>
                <PROVIDER>101</PROVIDER>
                <PROVIDER2>101</PROVIDER2>
                <PAYID>12</PAYID>
                <PAYID2>12</PAYID2>
                <otp>'.$request->otp.'</otp>
                <reference_number>'.$refNum.'</reference_number>
                <ext_txn_id>'.$ext_txn_id.'</ext_txn_id>
            </COMMAND>';

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
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/xml'
            ),
        ));

        $response = curl_exec($curl);
        // dd($response);
        curl_close($curl);

        $response = '<?xml version="1.0"?><response>'.$response.'</response>';
        $xml = simplexml_load_string($response);
        $response = json_decode(json_encode($xml), true);
        if(isset($response['status']) && $response['status'] == 200){
            return $this->successResponse($response['message'], $response['transID']);
        }else{
            return $this->failedResponse($response['message']);
        }
    }

    public function PowerTranzCardPayment($amount, $card, $for_user = true, $user = NULL, $driver = NULL, $payment_option_id = NULL, $currency = "USD", $booking_id = NULL, $order_id = NULL, $handyman_order_id = NULL){
        try{
            if($for_user){
                $paymentoption = PaymentOptionsConfiguration::where([['merchant_id','=', $user->merchant_id],['payment_option_id','=', $payment_option_id]])->first();
                $string_file = $this->getStringFile($user->merchant_id);
            }
            else {
                $paymentoption = PaymentOptionsConfiguration::where([['merchant_id', '=', $driver->merchant_id], ['payment_option_id', '=', $payment_option_id]])->first();
                $string_file = $this->getStringFile($driver->merchant_id);
            }

            $curl = curl_init();
            $power_tranz_id = $paymentoption->api_public_key;
            $power_tranz_password = $paymentoption->api_secret_key;

            $i=0;
            $guid =(string) Str::uuid();
            while($i==0){
                $exist = DB::table('transactions')->where('payment_transaction_id',$guid)->first();
                $guid =(string) Str::uuid();
                if(empty($exist)){
                    $i=1;
                }
            }

            if(!empty($booking_id)){
                $odr_id = $booking_id;
            }else if(!empty($order_id)){
                $odr_id = $order_id;
            }else if(!empty($handyman_order_id)){
                $odr_id = $handyman_order_id;
            }
            else{
                $odr_id = $for_user == true ? $user->id.'_'.time() : $driver->id.'_'.time();
            }

            //set currency to USD
            $currency = 840;
            $data = array(
                "TransactionIdentifier"=> "$guid",
                "TotalAmount"=> $amount,
                "CurrencyCode"=> 840, // "USD", // "$currency",
                "ThreeDSecure"=> false,
                "Source"=> [
                    "CardPan"=> "$card->card_number",
                    "CardExpiration"=> $card->exp_year.sprintf("%02d", $card->exp_month),
                    "CardholderName"=> "$card->card_holder"
                ],
                "OrderIdentifier"=> "$odr_id",
                "BillingAddress"=> [
                    "CountryCode"=> "$currency",
                ],
                "AddressMatch"=> false
            );
            $data=json_encode($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://staging.ptranz.com/Api/sale',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    "PowerTranz-PowerTranzId: $power_tranz_id",
                    "PowerTranz-PowerTranzPassword: $power_tranz_password",
                    'Host: staging.ptranz.com',
                    'Expect: 100-continue',
                    'Connection: Keep-Alive',
                    'Content-Type: application/json; charset=utf-8'
                ),
            ));

            $res = curl_exec($curl);
            $response = json_decode($res);
            curl_close($curl);

            $transaction_token = $response->TransactionIdentifier;
            if ($response->Approved == true) {

                // enter data
                DB::table('transactions')->insert([
                    'status' => ($for_user) ? 1 : 2, // for user
                    'card_id' => $card->id,
                    'user_id' => $for_user == true ? $user->id : NULL,
                    'driver_id' => $for_user == true ? NULL : $driver->id,
                    'merchant_id' => $for_user == true ? $user->merchant_id : $driver->merchant_id,
                    'payment_option_id' => $payment_option_id,
                    'checkout_id' => NULL,
                    'booking_id' => $booking_id,
                    'order_id' => $order_id,
                    'handyman_order_id' => $handyman_order_id,
                    'payment_transaction_id' => $transaction_token,
                    'payment_transaction' => $transaction_token,
                    'amount' => $amount, // amount
                    'request_status' =>  2,
                    'status_message' => "SUCEESS",
                ]);
                return true;
            }elseif(isset($response->Errors)){
                if($response->IsoResponseCode==12){
                    throw new \Exception("Currency is not supported");
                }else{
                    $message = isset($response->Errors[0]) ? $response->Errors[0]->Message : trans("$string_file.some_thing_went_wrong");
                    throw new \Exception($message);
                }
            }else{
                throw new \Exception($transaction_token);
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function CheckPaymentTransactionStatus(Request $request){
        try{
            $merchant_id = $request->merchant_id;
            $string_file = $this->getStringFile($request->merchant_id);
            $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id]])->get();
            $trans_data = [];
            foreach($payment_option_config as $config){
                $option = PaymentOption::select('slug', 'id')->where('id', $config->payment_option_id)->first();
                if (empty($option)) {
                    throw new \Exception(trans("$string_file.configuration_not_found"));
                } 
                
                if ($option->slug == 'UNIWALLET') {
                    
                    $currentDateTime = now(); // Get the current date and time
                    // $startTime = $currentDateTime->subMinutes(11); // subtract the specified interval from the current date and time
                    $transaction = Transaction::where(["merchant_id" => $merchant_id,"payment_option_id" => $config->payment_option_id,"request_status"=>1])->latest()->first();
                    // dd($transaction);
                    if(!empty($transaction)){
                        // foreach($transaction as $trans){
                        //     $data = [
                        //         'transaction_id'=> $trans->payment_transaction_id,
                        //         'reference_id'=> $trans->reference_id,
                        //         'action'=> ['VIEW','CANCEL']
                        //     ];
                        //     array_push($trans_data,$data);
                        // }
                        $trans_data = [
                                'transaction_id'=> $transaction->payment_transaction_id,
                                'reference_id'=> $transaction->reference_id,
                                'action'=> ['VIEW','CANCEL']
                            ];
                    }
                    else{
                        // return ["result"=> 1,"message"=>trans("$string_file.pending_transaction") . " " . trans("$string_file.not_found_data"),"data"=>[]];
                        return $this->failedResponse(trans("$string_file.pending_transaction") . " " . trans("$string_file.not_found_data"));
                    }
                }
            }
            
            return $this->successResponse(trans("$string_file.pending_transaction"),$trans_data);
        }catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
        
        
    }
    public function CancelTransaction(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required'
            ]);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }
            $merchant_id = $request->merchant_id;
            $trans_id = $request->transaction_id;
            $string_file = $this->getStringFile($request->merchant_id);
            $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id]])->get();
            foreach($payment_option_config as $config){
                $option = PaymentOption::select('slug', 'id')->where('id', $config->payment_option_id)->first();
                if (empty($option)) {
                    throw new \Exception(trans("$string_file.configuration_not_found"));
                } 
                if ($option->slug == 'UNIWALLET') {
                    $transaction = Transaction::where(["merchant_id" => $merchant_id,"payment_option_id" => $config->payment_option_id,"payment_transaction_id"=>$trans_id])->first();
                    if(!empty($transaction)){
                        DB::table('transactions')
                        ->where(['payment_transaction_id' => $trans_id])
                        ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'CANCELLED']);
                        
                        return $this->successResponse(trans("$string_file.transaction_cancelled"));
                        // return ['message'=>trans("$string_file.transaction_cancelled")];
                    }
                    else{
                        return $this->failedResponse(trans("$string_file.transaction") . " " . trans("$string_file.not_found"));
                    }
                }else if ($option->slug == 'UNI5PAY') {
                    $transaction = Transaction::where(["merchant_id" => $merchant_id,"payment_option_id" => $config->payment_option_id,"payment_transaction_id"=>$trans_id,'request_status'=> 1])->first();
                    // dd($transaction);
                    if(!empty($transaction)){
                        DB::table('transactions')
                        ->where(['payment_transaction_id' => $trans_id])
                        ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'CANCELLED']);
                        
                        return $this->successResponse(trans("$string_file.transaction_cancelled"));
                        // return ['message'=>trans("$string_file.transaction_cancelled")];
                    }
                    else{
                        return $this->failedResponse(trans("$string_file.transaction") . " " . trans("$string_file.not_found"));
                    }
                }
            }
        }catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
        
        
    }
    
    
    public function PeachServerToServerInitiate(Request $request, $paymentConfig, $calling_from)
    {
        // dd($paymentConfig);
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
                "card.expiryYear" => $request->card_expiry_year,
                "card.cvv" => $request->card_cvv,
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
            
            // dd($response, $res);
            
            if (isset($res)) {
                if ($res['resultDetails']['StatusDetails'] == 'Success') {
                    $calling_for = $request->calling_from == 'BOOKING' ? 3 : ($request->calling_from == "USER" ? 1 : 2);
                    $payment_transaction_id = $res['id'];
                    DB::table('transactions')->insert([
                        'user_id' => $request->calling_from == "USER" ? $id : NULL,
                        'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                        'status' => $calling_for,
                        'merchant_id' => $merchant_id,
                        'payment_transaction_id' => $payment_transaction_id,    
                        'payment_transaction' => $response,    
                        'amount' => $res['amount'],
                        'payment_option_id' => $paymentConfig->payment_option_id,
                        'request_status' => 2, 
                        "payment_mode" => "Card",
                        'status_message' => 'SUCCESS',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    DB::commit();
                }
                return $res;
            } else {
                return $this->failedResponse(trans("$string_file.something_went_wrong"));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
    
    public function IMBankReturn(Request $request){
        dd($request->all());
    }
}
