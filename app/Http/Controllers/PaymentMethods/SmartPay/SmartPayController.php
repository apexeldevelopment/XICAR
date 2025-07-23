<?php

namespace App\Http\Controllers\PaymentMethods\SmartPay;

use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SmartPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getSmartPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'SMARTPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getBaseUrl($env){
        return $env == 1 ? 'https://voucherapi.demos.classicinformatics.net/api/' : 'https://voucherapi.demos.classicinformatics.net/api/';
    }

    public function registerOnSmartPay(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'password' => 'required',
            'pin' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try{
            if ($request->type == 'USER'){
                $user = $request->user('api');
                $name = $user->first_name.' '.$user->last_name;
                $email = $user->email;
                $complete_phone = $user->UserPhone;
                $country_code = $user->Country->phonecode;
                $phone = str_replace($country_code,'',$complete_phone);
                $merchant_id = $user->merchant_id;
            }else{
                $driver = $request->user('api-driver');
                $name = $driver->first_name.' '.$driver->last_name;
                $email = $driver->email;
                $complete_phone = $driver->phoneNumber;
                $country_code = $driver->Country->phonecode;
                $phone = str_replace($country_code,'',$complete_phone);
                $merchant_id = $driver->merchant_id;
            }
            $phone = !empty($request->phone) ? str_replace($country_code,'',$request->phone) : $phone;

            $paymentConfig = $this->getSmartPayConfig($merchant_id);
            $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);

            $post_data = [
                'SubscriberType' => 'Organisation',
                'OrganizationName' => $name,
                'MobileCode' => str_replace('+','',$country_code),
                'MobileNumber' => $phone,
                'Email' => $email,
                'BBAN' => '',
                'BBANLinkMobileNumber' => '0',
                'BBANLinkMobileCode' => '0',
                'AccountCreatedBy' => 'Self',
                'IsVerifiedByAdmin' => 'false',
                'Password' => $request->password,
                'MMOId' => '0',
                'MMOAccountNumber' => '',
                'BankProfileId' => '22',
                'AuthFactorType' => '1FA',
                'ExcidedPaymentAmount' => '50000',
                'Pin' => $request->pin,
                'BankAccountNumber' => str_replace('+','',$complete_phone)
            ];
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $baseUrl.'Subscriber/SelfRegistration',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                    'authority: voucherapi.demos.classicinformatics.net',
                    'accept: application/json, text/plain, */*',
                    'accept-language: en-US,en;q=0.9',
                    'origin: https://voucheraui.demos.classicinformatics.net',
                    'referer: https://voucheraui.demos.classicinformatics.net/',
                    'sec-ch-ua: "Not/A)Brand";v="99", "Google Chrome";v="115", "Chromium";v="115"',
                    'sec-ch-ua-mobile: ?0',
                    'sec-ch-ua-platform: "Windows"',
                    'sec-fetch-dest: empty',
                    'sec-fetch-mode: cors',
                    'sec-fetch-site: same-site',
                    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response, true);
            if ($response['responseCode'] == "200"){
                return $this->successResponse($response['responseMessage'], $response['result']);
            }else{
                return $this->failedResponse($response['responseMessage']);
            }
        }catch (\Exception $e){
            return $this->failedResponse($e->getMessage());
        }
    }

    public function generatePaymentToken($phone, $password, $baseUrl, $calling_from = 'PAYMENT'){
        $post_data = ['email' => $phone, 'password' => $password];
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseUrl.'POS/PaymentPinVerification',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'accept: */*',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response,true);

        if ($calling_from == 'PAYMENT'){
            if ($response['responseCode'] == "200"){
                return $response['result']['token'];
            }else{
                return "";
            }
        }else{
            return ['responseCode' => $response['responseCode'],'responseMessage' => $response['responseMessage']];
        }
    }

    public function processSmartPayPayment(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'phone' => 'required',
            'pin' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try{
            $merchant_id = $request->type == "USER" ? $request->user('api')->merchant_id : $request->user('api-driver')->merchant_id;
            $paymentConfig = $this->getSmartPayConfig($merchant_id);
            $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);
            $phone = str_replace('+','',$request->phone);
            $token = $this->generatePaymentToken($phone, $request->pin, $baseUrl);
            if (empty($token)){
                return $this->failedResponse('Payment Token is not generated.');
            }

            $post_data = [
                'merchantUserId' => $paymentConfig->auth_token,
                'amount' => (integer)$request->amount,
                'productId' => 'PROD_'.date('YmdHis'),
                'otp' => '',
                'pin' => $request->pin
            ];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $baseUrl.'Transactions/voucherpayment',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($post_data),
                CURLOPT_HTTPHEADER => array(
                    'accept: */*',
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$token
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $response = json_decode($response, true);
            if (isset($response['responseCode']) && $response['responseCode'] == '200'){
                return $this->successResponse($response['responseMessage'], $response['result']);
            }else{
                $message = isset($response['responseMessage']) ? $response['responseMessage'] : "Something Went Wrong!";
                return $this->failedResponse($message);
            }
        }catch (\Exception $e){
            return $this->failedResponse($e->getMessage());
        }
    }

    public function checkUser(Request $request){
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'pin' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == "USER" ? $request->user('api')->merchant_id : $request->user('api-driver')->merchant_id;
        $paymentConfig = $this->getSmartPayConfig($merchant_id);
        $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);
        $phone = str_replace('+','',$request->phone);
        $response = $this->generatePaymentToken($phone, $request->pin, $baseUrl, 'CHECK_USER');
        if ($response['responseCode'] == "200"){
            return $this->successResponse($response['responseMessage']);
        }else{
            return $this->failedResponse($response['responseMessage']);
        }
    }
}
