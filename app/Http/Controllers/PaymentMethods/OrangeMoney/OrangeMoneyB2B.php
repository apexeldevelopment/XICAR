<?php

namespace App\Http\Controllers\PaymentMethods\OrangeMoney;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;

class OrangeMoneyB2B extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function notifications(Request $request, $merchant_id=NULL){

        if(empty($merchant_id)){
            $merchant_id = '806';
        }

        if (!$request->secure()) {
            return response()->json(['message' => 'prohibited'], 500);
        }
        $config = $this->getOrangeMoneyB2BConfig($merchant_id);

        //default credentials for msprojects.apporioproducts.com orangemoney B2B callback url
        $username = $config->api_public_key; //username saved in this field
        $password = $config->additional_data; //password saved in this field

        if ($request->headers->has('Authorization')) {
            $authHeader = $request->headers->get('Authorization');
            list($type, $credentials) = explode(' ', $authHeader, 2);
            if (strtolower($type) === 'basic') {
                $decodedCredentials = base64_decode($credentials);
                list($inputUsername, $inputPassword) = explode(':', $decodedCredentials, 2);
                if($inputUsername === $username && $inputPassword === $password  && $credentials == $config->tokenization_url ){
                    \Log::channel('orangemoney_b2b')->info(["response_orange_money"=>$request->all(), "date"=>date('Y-m-d H:i:s')]);
                    $callback_data = $request->all();
                    DB::beginTransaction();
                    try {

                        $req_status = 4;
                        if($callback_data['status'] == "SUCCESS"){
                            $req_status = 2;
                        }
                        elseif($callback_data['status'] == "FAILED"){
                            $req_status = 3;
                        }

                        if (isset($callback_data['status'])) {
                            $transaction = Transaction::where("payment_transaction_id",$callback_data["transactionData"]["transactionId"])->first();
                            $transaction->request_status = $req_status;
                            $transaction->status_message = $callback_data['status'];
                            $transaction->reference_id = $callback_data['transactionData']['txnId'];
                            $transaction->updated_at = date('Y-m-d H:i:s');
                            $transaction->save();
                            DB::commit();
                        }
                    }
                    catch (\Exception $e){
                        DB::rollback();
                        \Log::channel('orangemoney_b2b')->info(["callback_got_error"=>$e->getMessage(), "date"=>date('Y-m-d H:i:s')]);
                    }

                    return response()->json(['message' => 'Created'], 200);
                }
                else{
                    return response()->json(['message' => 'Unauthenticated'], 401);
                }

            }
        }
        else{
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
    }

    public function getOrangeMoneyB2BConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'ORANGE_MONEY_B2B')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getAuthToken($authToken, $grantType){
        try{
            $url = 'https://api.orange.com/oauth/v3/token';

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
                CURLOPT_POSTFIELDS => 'grant_type='.$grantType,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: '.$authToken,
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $res = json_decode($response,true);
            if(isset($res['access_token'])){
                return $res['access_token'];
            }
            else{
                return '';
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }


    public function initiatePayment($request, $payment_option_config, $calling_from){

        $authToken = $payment_option_config->auth_token;
        $posId = $payment_option_config->api_secret_key;

        $authorization = $this->getAuthToken($authToken, "client_credentials");

        if($calling_from == "DRIVER") {
            $user = $request->user('api-driver');
            $id = $user->id;
            $merchant_id = $user->merchant_id;
        }
        else{
            $user = $request->user('api');
            $id = $user->id;
            $merchant_id = $user->merchant_id;
        }

        $amount = $request->amount;
        $currency = $request->currency;
        $transaction_id = $merchant_id."-".$user->id."-".uniqid()."-".time();
        $msisdn = $request->phone;
        $string_file = $this->getStringFile($merchant_id);

        $data = [
            "peerId"=>$msisdn,
            "peerIdType"=>"msisdn",
            "amount"=>(float)$amount,
            "currency"=>$currency,
            "posId"=>$posId,
            "transactionId"=>$transaction_id
        ];

        $service_type = 'cashin';
        if(!empty($request->service_type) && $request->service_type == 'cashout'){
            $service_type = $request->service_type;
        }
        elseif(!empty($request->service_type) && $request->service_type != 'cashout'){
            return "invalid service type";
        }

        DB::beginTransaction();

        try{
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.orange.com/orange-money-b2b/v1/sx/transactions/'.$service_type,
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
                    'Authorization: Bearer '.$authorization,
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response,true);

            if (isset($res)) {
                if (isset($res['status'])) {
                    $calling_for = $request->calling_from == 'BOOKING' ? 3 : ($request->calling_from == "USER" ? 1 : 2);
                    $payment_transaction_id = $res['transactionData']['transactionId'];
                    DB::table('transactions')->insert([
                        'user_id' => $request->calling_from == "USER" ? $id : NULL,
                        'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                        'status' => $calling_for,
                        'merchant_id' => $merchant_id,
                        'payment_transaction_id' => $payment_transaction_id,
                        'amount' => $res['transactionData']['amount'],
                        'payment_option_id' => $payment_option_config->payment_option_id,
                        'request_status' => 1,
                        "payment_mode" => "Third-party App",
                        'status_message' => $res['status'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    DB::commit();
                }
                return $res;
            } else {
                return trans("$string_file.something_went_wrong");
            }
        }
        catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function paymentStatus(Request $request): array
    {
        $transaction_id = $request->transaction_id;
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transaction_id)->first();
        $request_status_text = "failed";
        $payment_status = false;
        if (isset($transaction_table)) {
            $payment_status =   $transaction_table->request_status == 2 ?  true : false;
            if ($transaction_table->request_status == 1) {
                $request_status_text = "processing";
            } else if ($transaction_table->request_status == 2) {
                $request_status_text = "success";
            }
        }
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
    }

}
