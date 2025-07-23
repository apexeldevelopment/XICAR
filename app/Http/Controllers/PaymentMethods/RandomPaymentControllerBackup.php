<?php

namespace App\Http\Controllers\PaymentMethods;

use App\Http\Controllers\Controller;
use App\Models\UserCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentOptionsConfiguration;
use Illuminate\Validation\Rule;

require 'iugu/lib/Iugu.php';

class RandomPaymentControllerBackup extends Controller
{
    public function ChargePaystack($amount = 0, $currency = null, $CustomerID = null, $email = null, $paystack = null, $payment_redirect_url = null)
    {
        $amount = $amount * 100;
        $postdata = array('authorization_code' => $CustomerID, 'email' => $email, 'currency' => $currency, 'amount' => $amount);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payment_redirect_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));  //Post Fields
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [
            'Authorization: Bearer ' . $paystack,
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $request = curl_exec($ch);
        curl_close($ch);
        $result1 = json_decode($request, true);
        if ($result1['status'] == true) {
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
                return array('id' => $result12['data']['status']);
            } else {
                return false;
            }
        } else {
            return array($result1['message']);
        }
    }

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
                return array('id' => $result['data']['authorization']);
            } else {
                return false;
            }
        }
    }

    public function tokenGenerateCielo($cardNumber = null, $expMonth = null, $expYear = null, $cardType = null, $cvv = null, $email = null, $userName = null, $merchantKey = null, $merchantId = null, $tokenizationUrl = null)
    {
        $rand = rand(111111, 999999);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $tokenizationUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{  \r\n   \"MerchantOrderId\":\"$rand\",\r\n   \"Customer\":{  \r\n      \"Name\":\"$userName\",\r\n      \"email\":\"$email\"\r\n   },\r\n   \"Payment\":{  \r\n     \"Type\":\"CreditCard\",\r\n     \"Amount\":1,\r\n     \"Installments\":1,\r\n     \"Authenticate\":false,\r\n     \"CreditCard\":{  \r\n         \"CardNumber\":\"$cardNumber\",\r\n         \"Holder\":\"$userName\",\r\n         \"ExpirationDate\":\"$expMonth/$expYear\",\r\n         \"SecurityCode\":\"$cvv\",\r\n         \"SaveCard\":\"true\",\r\n         \"Brand\":\"$cardType\"\r\n     }\r\n   }\r\n}",
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
        if (isset($res['Payment'])) {
            if ($res['Payment']['Status'] == 1 || $res['Payment']['Status'] == 2) {
                return array('id' => 1, 'data' => $res);
            } else {
                return array('0' => 'Payment Failed');
            }
        } else {
            $message = array_key_exists(0, $res) ? $res[0]['Message'] : $res[1]['Message'];
            return array($message);
        }
    }

    public function ChargeCielo($amount = 0, $userName, $cardType, $token = null, $merchantKey, $merchantId, $payment_redirect_url = null)
    {
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
                return false;
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
        \Braintree_Configuration::environment($envir);
        \Braintree_Configuration::merchantId($merchant_id);
        \Braintree_Configuration::publicKey($publicKey);
        \Braintree_Configuration::privateKey($privateKey);
        $see = \Braintree_ClientToken::generate();
        return array('clientToken' => $see);
    }

    public function brainTreeCreateTrans($amount, $nonce)
    {
        $result = \Braintree_Transaction::sale([
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
        \Iugu::setApiKey($paymentoption['api_secret_key']);
        $paymentToken = \Iugu_PaymentToken::create([
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
        \Iugu::setApiKey($paymentoption['api_secret_key']);
        $charge = \Iugu_Charge::create(
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
                foreach ($cards as $card) {
                    $card_num = substr($card['card_masked_number'], -4);
                    $card = UserCard::updateOrCreate(
                        ['user_id' => $userId, 'card_number' => $card_num],
                        [
                            'token' => $card['alias_token'],
                            'payment_option_id' => 'BANCARD',
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
                CURLOPT_POSTFIELDS => "{\r\n    \"public_key\": \"$paymentConfig->api_public_key\",\r\n    \"operation\": {\r\n        \"token\": \"$token\",\r\n        \"card_id\": $rand,\r\n        \"user_id\": $user->id,\r\n        \"user_cell_phone\": \"$user->UserPhone\",\r\n        \"user_mail\": \"$user->email\",\r\n        \"return_url\": \"$paymentConfig->callback_url\"\r\n    },\r\n    \"test_client\": true\r\n}",
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
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8888",
            CURLOPT_URL => $payment_redirect_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n    \"public_key\": \"$api_public_key\",\r\n    \"operation\": {\r\n        \"token\": \"$token\",\r\n        \"shop_process_id\": $shopProcessId,\r\n        \"items\": [\r\n            {\r\n                \"name\": \"TaxiPayment1\",\r\n                \"store\": 4,\r\n                \"store_branch\": 46,\r\n                \"amount\": \"$amount\",\r\n                \"currency\": \"$currency\"\r\n            }\r\n        ],\r\n        \"number_of_payments\": 1,\r\n        \"additional_data\": \"\",\r\n        \"alias_token\": \"$cardToken\"\r\n    },\r\n    \"test_client\": true\r\n}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $res = json_decode($response,true);
        if (isset($res['status']) && $res['status'] == 'success') {
            return array('result' => "1", 'message' => 'Payment Successful');
        } else {
            return false;
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

    public function getSubTokenDPO($companyToken = null, $email = null)
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
            CURLOPT_POSTFIELDS => "<API3G> \r\n\r\n<CompanyToken>$companyToken</CompanyToken> \r\n\r\n<Request>getSubscriptionToken</Request> \r\n\r\n<SearchCriteria>1</SearchCriteria> \r\n\r\n<SearchCriteriaValue>$email</SearchCriteriaValue> \r\n\r\n</API3G> ",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/xml"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $xml = new \SimpleXMLElement($response);
        $xml = json_encode($xml);
        $xml = json_decode($xml, true);
        if ($xml['Result'] == 000) {
            $cards = $this->getCardsDPO($xml['CustomerToken'], $companyToken);
            if (array_key_exists('data', $cards)) {
                return $cards;
            } else {
                return $cards;
            }
        } else {
            return array('result' => "0", 'message' => $xml['ResultExplanation']);
        }
    }

    public function getCardsDPO($custToken, $companyToken)
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
            CURLOPT_POSTFIELDS => "<API3G>\n    <CompanyToken>$companyToken</CompanyToken>\n    <Request>pullAccount</Request>\n    <customerToken>$custToken</customerToken>\n</API3G>",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/xml"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $xml = new \SimpleXMLElement($response);
        $xml = json_encode($xml);
        $xml = json_decode($xml, true);
        if ($xml['Result'] == 000) {
            return array('data' => $xml['paymentOptions']['option']);
        } else {
            return array('message' => $xml['ResultExplanation']);
        }
    }

    public function createTransDPO(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required',
            'currency' => 'required',
            'countryDialcode' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $user = ($request->for == "user") ? $request->user('api') : $request->user('api-driver');
        $payConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
        $date = date('Y/m/d H:i');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://secure.3gdirectpay.com/API/v6/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<API3G>\r\n    <CompanyToken>$payConfig->auth_token</CompanyToken>\r\n    <Request>createToken</Request>\r\n    <Transaction>\r\n        <PaymentAmount>$request->amount</PaymentAmount>\r\n        <PaymentCurrency>$request->currency</PaymentCurrency>\r\n        <CompanyRefUnique>1</CompanyRefUnique>\r\n        <PTL>5</PTL>\r\n        <TransactionChargeType>1</TransactionChargeType>\r\n        <customerEmail>$user->email</customerEmail>\r\n        <customerFirstName>$user->first_name</customerFirstName>\r\n        <customerLastName>$user->last_name</customerLastName>\r\n        <customerDialCode>$request->countryDialcode</customerDialCode>\r\n        <customerPhone>$user->UserPhone</customerPhone>\r\n    <AllowRecurrent>1</AllowRecurrent>\r\n    </Transaction>\r\n    <Services>\r\n        <Service>\r\n            <ServiceType>5525</ServiceType>\r\n            <ServiceDescription>Service1</ServiceDescription>\r\n            <ServiceDate>$date</ServiceDate>\r\n        </Service>\r\n    </Services>\r\n    </API3G>",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/xml",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $xml = new \SimpleXMLElement($response);
        $xml = json_encode($xml);
        $xml = json_decode($xml, true);
        if ($xml['Result'] == 000) {
            return redirect('https://secure.3gdirectpay.com/dpopayment.php?ID=' . $xml['TransToken']);
        } else {
            return response()->json(['result' => "0", 'message' => 'Operation Failed']);
        }
    }

    public function ridePaymentDPO($auth_token = null, $amount = null, $currency = null, $email = null, $firstNname = null, $lastName = null, $UserPhone = null, $cardToken = null)
    {
        $date = date('Y/m/d H:i');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://secure.3gdirectpay.com/API/v6/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<API3G>\r\n    <CompanyToken>$auth_token</CompanyToken>\r\n    <Request>createToken</Request>\r\n    <Transaction>\r\n        <PaymentAmount>$amount</PaymentAmount>\r\n        <PaymentCurrency>$currency</PaymentCurrency>\r\n        <CompanyRefUnique>1</CompanyRefUnique>\r\n        <PTL>5</PTL>\r\n        <TransactionChargeType>1</TransactionChargeType>\r\n        <customerEmail>$email</customerEmail>\r\n        <customerFirstName>$firstNname</customerFirstName>\r\n        <customerLastName>$lastName</customerLastName>\r\n        <customerPhone>$UserPhone</customerPhone>\r\n    <AllowRecurrent>1</AllowRecurrent>\r\n    </Transaction>\r\n    <Services>\r\n        <Service>\r\n            <ServiceType>5525</ServiceType>\r\n            <ServiceDescription>Service1</ServiceDescription>\r\n            <ServiceDate>$date</ServiceDate>\r\n        </Service>\r\n    </Services>\r\n    </API3G>",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/xml",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $xml = new \SimpleXMLElement($response);
        $xml = json_encode($xml);
        $xml = json_decode($xml, true);
        if ($xml['Result'] == 000) {
            $payment = $this->makepaymentDPO($auth_token, $xml['TransToken'], $cardToken);
            if (is_array($payment)) {
                return array();
            } else {
                return false;
            }
        } else {
            return response()->json(['result' => "0", 'message' => 'Operation Failed']);
        }
    }

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
        $xml = new \SimpleXMLElement($response);
        $xml = json_encode($xml);
        $xml = json_decode($xml, true);
        if ($xml['Result'] == 000) {
            return array('result' => "1", 'message' => $xml['ResultExplanation']);
        } else {
            return false;
        }
    }


    public function korbaWeb(Request $request)
    {
        $transaction_id = uniqid();
        $user = ($request->for == "user") ? $request->user('api') : $request->user('api-driver');
        $amount=$request->amount;
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', $user->merchant_id]])->first();
        $user_phone = str_replace("+233","0",$user->UserPhone);

        if (!empty($paymentConfig)) {
            $callback_url = $paymentConfig->callback_url;
            $secret = $paymentConfig->api_secret_key;
            $message = "amount=$request->total_payed_amount&callback_url=$callback_url&client_id=$paymentConfig->auth_token&customer_number=$user_phone&description=Taxi_Payment&network_code=$user->network_code&transaction_id=$transaction_id";
            $HMAC = hash_hmac('sha256', $message, $secret);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://xchange.korbaweb.com/api/v1.0/collect/",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\r\n   \"amount\": $amount,    \r\n   \"callback_url\": \"$callback_url\",\r\n   \"client_id\": \"$paymentConfig->auth_token\",\r\n   \"customer_number\": \"$user_phone\",\r\n   \"description\": \"Taxi_Payment\",\r\n   \"network_code\": \"$user->network_code\",\r\n   \"transaction_id\": \"$transaction_id\"\r\n}\r\n\r\n\r\n",
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

    public function callbackkorba(Request $request){
        $trans = explode("-",$request->transaction_id);
        $done_ride = $trans[0];
        $status = $request->status;
        $message = $request->message;
        if($status == "FAILED"){
            return response()->json(['result' => 0,'message' => $message, 'data' => $done_ride]);
        }else{
            return response()->json(['result' => 1, 'message' => $message,'data' => $done_ride]);
        }
    }

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
}
