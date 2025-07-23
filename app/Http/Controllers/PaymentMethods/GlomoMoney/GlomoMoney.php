<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 20/1/23
 * Time: 1:30 PM
 */

namespace App\Http\Controllers\PaymentMethods\GlomoMoney;


use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GlomoMoney
{
    use ApiResponseTrait, MerchantTrait;

    protected $BASE_URL = "https://apis.glomoapp.com/v1";

    protected $TEST_BASE_URL = "https://apis.glomoapp.com/v1/test";

    public function checkPhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'phone_number' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","GLOMO_MONEY")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://glomoapp.com/android/global".'/account/getuserbyphonenumber',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode(array("phoneNumber" => str_replace("+","",$request->phone_number), "amount" => $request->amount)),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);
            if(isset($response['status']) && $response['status'] == 0){
                return $this->successResponse(trans("$string_file.success"),array(
                    "name" => $response['user']['first_name']." ".$response['user']['last_name'],
                    "phone_number" => $request->phone_number,
                    "glomo_money_transaction_id" => $response["transaction_id"]
                ));
            }else{
                throw new \Exception(isset($response['mes']) ? $response['mes'] : $response['message']);
            }
        }catch (\Exception $exception){
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function makeTransfer(array $params)
    {
        try{
            $string_file = $params['string_file'];
            $payment_option = PaymentOption::where("slug","GLOMO_MONEY")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }
            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $params['merchant_id'], "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            if(isset($params['glomo_money_transaction_id']) && !empty($params['glomo_money_transaction_id'])){
                $glomo_money_transaction_id = $params['glomo_money_transaction_id'];
            }else{
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://glomoapp.com/android/global".'/account/getuserbyphonenumber',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS =>json_encode(array("phoneNumber" => str_replace("+","",$params['phone_number']), "amount" => $params['amount'])),
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json'
                    ),
                ));
                $response = curl_exec($curl);
                curl_close($curl);

                $response = json_decode($response,true);
                if(isset($response['status']) && $response['status'] == 0){
                    $glomo_money_transaction_id = $response['transaction_id'];
                }else{
                    throw new \Exception(isset($response['mes']) ? $response['mes'] : $response['message']);
                }
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://glomoapp.com/android/global".'/account/refill',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode(array("transaction_id" => $glomo_money_transaction_id)),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);
            if(isset($response['status']) && $response['status'] == 0){
                return $glomo_money_transaction_id;
            }else{
                throw new \Exception(isset($response['mes']) ? $response['mes'] : $response['message']);
            }
        }catch (\Exception $exception){
            throw new \Exception("Glomo Money - ".$exception->getMessage());
        }
    }

    public function paymentTypes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
        $string_file = $this->getStringFile(null, $user->Merchant);
        $data = array(
            "GLOMO" => "Glomo Money",
            "MOMO" => "MOMO Money",
            "OM" => "Om"
        );
        return $this->successResponse(trans("$string_file.success"), $data);
    }

    public function makeDebitPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'phone_number' => 'required',
            'amount' => 'required',
            'payment_type' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","GLOMO_MONEY")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $url = "";
            $data = "";
            $api_token = "";

            $base_url = "";
            if($payment_option_config->gateway_condition == 1){
                $base_url = $this->BASE_URL;
            }else{
                $base_url = $this->TEST_BASE_URL;
            }
            // p($base_url."token/generate");
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url."/token/generate",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'App-Id: '.$payment_option_config->api_public_key,
                    'App-Secret: '.$payment_option_config->api_secret_key
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response,true);

            if(isset($response['status']) && $response['status'] == "OK"){
                $api_token = $response['token'];
            }else{
                throw new \Exception($response['error_message']);
            }
            $phone_number = str_replace("+237","",$request->phone_number);
            if($request->payment_type == "GLOMO"){
                $url = "/account/debit/initiate";
                $data = array("numero" => $phone_number, "amount" => $request->amount, "title" => "Taxi Payment");
            }elseif($request->payment_type == "MOMO"){
                $url = "/momo/payment/receive";
                $data = array("numero" => $phone_number, "amount" => $request->amount, "title" => "Taxi Payment");
            }elseif ($request->payment_type == "OM"){
                $url = "/om/payment/receive";
                $data = array("numero" => $phone_number, "amount" => $request->amount, "title" => "Taxi Payment");
            }else{
                throw new \Exception(trans("$string_file.invalid_payment_type"));
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url.$url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'App-Id: '.$payment_option_config->api_public_key,
                    'App-Secret: '.$payment_option_config->api_secret_key,
                    'Authorisation: Bearer '.$api_token,
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);

            if(isset($response['status']) && ($response['status'] == "PENDING" || $response['status'] == "OK")){
                $temp['token'] = $response['cle_operation'];
                $temp['message'] = $response['message'];
                return $this->successResponse(trans("$string_file.success"),$temp);
            }else{
                throw new \Exception($response['error_message']);
            }
        }catch (\Exception $exception){
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function checkDebitPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'token' => 'required',
            'payment_type' => 'required',
            'otp' => 'required_if:payment_type,GLOMO',
            'password' => 'required_if:payment_type,GLOMO'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","GLOMO_MONEY")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $base_url = "";
            if($payment_option_config->gateway_condition == 1){
                $base_url = $this->BASE_URL;
            }else{
                $base_url = $this->TEST_BASE_URL;
            }

            $url = "";
            $data = "";
            $api_token = "";

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url."/token/generate",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'App-Id: '.$payment_option_config->api_public_key,
                    'App-Secret: '.$payment_option_config->api_secret_key,
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response,true);

            if(isset($response['status']) && $response['status'] == "OK"){
                $api_token = $response['token'];
            }else{
                throw new \Exception($response['error_message']);
            }

            $phone_number = str_replace("+237","",$request->phone_number);
            $response = "";
            if($request->payment_type == "GLOMO"){
                $url = "/account/debit/valid";
                $data = array("key" => $request->token, "code_app" => $request->password, "code_sms" => $request->otp);

                $curl = curl_init();
                curl_setopt_array($curl, array(
                  CURLOPT_URL => $base_url.$url,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS => json_encode($data),
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'App-Id: '.$payment_option_config->api_public_key,
                    'App-Secret: '.$payment_option_config->api_secret_key,
                    'Authorisation: Bearer '.$api_token,
                  ),
                ));
                $response = curl_exec($curl);
                curl_close($curl);

            }elseif($request->payment_type == "MOMO" || $request->payment_type == "OM"){
                $url = "/account/operation/status?key=$request->token";
                $data = array("numero" => $phone_number, "amount" => $request->amount, "title" => "Taxi Payment");

                $curl = curl_init();
                curl_setopt_array($curl, array(
                  CURLOPT_URL => $base_url.$url,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'GET',
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'App-Id: '.$payment_option_config->api_public_key,
                    'App-Secret: '.$payment_option_config->api_secret_key,
                    'Authorisation: Bearer '.$api_token,
                  ),
                ));
                $response = curl_exec($curl);
                curl_close($curl);
            }else{
                throw new \Exception(trans("$string_file.invalid_payment_type"));
            }

            $response = json_decode($response,true);
            if(isset($response['status']) && $response['status'] == "OK"){
                if($request->payment_type == "GLOMO"){
                    $result = $response['status'] == "VALIDATED" ? true : false;
                }else{
                    $result = $response['operation']['state'] == "VALIDATED" ? true : false;
                }
                return $this->successResponse(trans("$string_file.success"), ["result" => $result]);
            }else{
                throw new \Exception($response['error_message']);
            }
        }catch (\Exception $exception){
            return $this->failedResponse($exception->getMessage());
        }
    }

    private function get_message($string){
        $message = "";
        switch ($string){
            case "THE_TRANSACTION_HAS_BEEN_CANCELLED":
                $message = "The transaction has been cancelled.";
                break;
            case "ACCOUNT_NOT_ACTIVE":
                $message = "Account not active.";
                break;
            case "INSUFFICENT_AMOUNT":
                $message = "Insufficent Amount";
                break;
            case "INCORRECT_PASSWORD_OR_PHONE_NUMBER":
                $message = "Incorrect password or phone number";
                break;
            case "INCORRECT_PASSWORD_OR_PHONE_NUMBER":
                $message = "Incorrect password or phone number";
                break;
            case "AMOUNT_MUST_BE_GREATER_THAN_500_FCFA":
                $message = "AMOUNT_MUST_BE_GREATER_THAN_500_FCFA";
                break;
            default:
                $message = "Failed";
        }
        return $message;
    }
}
