<?php

namespace App\Http\Controllers\PaymentMethods\PawaPay;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PawaPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getPawaPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'PAWAPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getUrl($env){
        return $env == 1 ? "https://api.pawapay.cloud" : "https://api.sandbox.pawapay.cloud";
    }

    public function getPaymentCorrespondentOption(Request $request){
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'slug'=> 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
        $string_file = $this->getStringFile(null, $user->Merchant);
        $column = ($request->for == "USER") ? "user_id" : "driver_id";
        $country_code = isset($user->Country) ? $user->Country->country_code : "";
        if(isset($user->Country)){
            $correspondents = [];
            $correspondents = $this->chooseDataByCountry($country_code,'correspondent');
            return $this->successResponse(trans("$string_file.success"), $correspondents);
        }else{
            return $this->failedResponse('No Matched Country Correspondent');
        }

    }

    public function Callback(Request $request){
        \Log::channel('pawapay')->emergency($request->all());
        $response = $request->all();
        $transactionId = $request['depositId'];
        $status = $request['status'];
        if($status == "FAILED" || $status == "REJECTED"){
            DB::table('transactions')
                ->where(['payment_transaction_id' => $transactionId])
                ->update(['request_status'=>3,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($response)]);
        }elseif($status == "COMPLETED"){
            DB::table('transactions')
                ->where(['payment_transaction_id' => $transactionId])
                ->update(['request_status'=>2,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($response)]);
        }
    }

    public function paymentRequest($request, $payment_option_config, $calling_from){
        try{
            $amount = $request->amount;
            $apiToken = $payment_option_config->api_secret_key;
            $correspondent = $request->correspondent_name;
            $uuid = Str::uuid()->toString();
            $phone = $request->phone;
            $currentTimestamp = Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z');
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $currency = $user->Country->isoCode;
                $country = $user->Country->country_code;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 2;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $currency = $user->Country->isoCode;
                $country = $user->Country->country_code;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 1;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            
            $country = $this->chooseDataByCountry($country);
            
            $payment_option = $this->getPawaPayConfig($merchant_id);
            $base_url = $this->getUrl($payment_option->gateway_condition);
            $transactionId = 'TRANS_'.time();

            $data = [
                "depositId"=>$uuid,
                "amount"=>$amount,
                "currency"=> $currency,
                "country"=> $country,
                "correspondent"=>$correspondent,
                "payer"=>[
                    "type"=>"MSISDN",
                    "address"=>[
                        "value"=>$phone
                    ]
                ],
                "customerTimestamp"=> $currentTimestamp,
                "statementDescription"=> "Deposit Request"
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url .'/deposits',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type:application/json',
                    'Authorization:Bearer '.$apiToken,
                ),
            ));

            $response = json_decode(curl_exec($curl),true);
            \Log::channel('pawapay')->emergency(['response'=>$response]);
            curl_close($curl);

            if(isset($response['status']) && $response['status'] == 'ACCEPTED'){
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $response['depositId'],
                    'amount' => $request->amount,
                    'payment_option_id' => $payment_option->payment_option_id,
                    'reference_id'=> $transactionId,
                    'request_status'=> 1,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }elseif(isset($response['status']) && $response['status'] == 'REJECTED'){
                return ['result'=> 0 ,'response'=> $response['rejectionReason']['rejectionMessage']];
            }
            else{
                throw new \Exception($response['errorMessage']);
            }

            return [
                'transaction_id'=> $response['depositId'],
                'success_url' => route('pawapay-success'),
                'fail_url' => route('pawapay-fail'),
            ];

        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
    
    public function chooseDataByCountry($country_code,$correspondent = NULL){
        if($correspondent){
            $correspondents = [];
            if($country_code == "CD"){
                $correspondents = [['correspondent_name'=>'AIRTEL_COD'],['correspondent_name'=>'VODACOM_MPESA_COD'],['correspondent_name'=>'ORANGE_COD']];
            }elseif($country_code == "MW"){
                $correspondents = [['correspondent_name'=>'AIRTEL_MWI'],['correspondent_name'=>'TNM_MWI']];
            }elseif($country_code == "GA"){
                $correspondents = [['correspondent_name'=>'AIRTEL_GAB']];
            }
            
            return $correspondents;
        }else{
            if($country_code == "CD"){
                $correspondents = "COD";
            }elseif($country_code == "MW"){
                $correspondents = "MWI";
            }elseif($country_code == "GA"){
                $correspondents = "GAB";
            }
            return $correspondents;
        }
    }

    public function paymentStatus(Request $request){
        $calling_from = $request->calling_from;
        if($calling_from == "DRIVER") {
            $user = $request->user('api-driver');
            $merchant_id = $user->merchant_id;
        }
        else{
            $user = $request->user('api');
            $merchant_id = $user->merchant_id;
        }
        $payment_option = $this->getPawaPayConfig($merchant_id);
        $transactionId = $request->transaction_id;

        $transactionStatus = $this->getTranStatus($user,$transactionId,$calling_from, $request);
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        $data = [];
        if($transaction_table->request_status == 1)
        {
            $request_status_text = "processing";
            $transaction_status = 1;
            $data = ['payment_status' => 1, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }
        else if($transaction_table->request_status == 2)
        {
            $request_status_text = "success";
            $transaction_status = 2;
            $data = ['payment_status' => 2, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }
        else
        {
            $request_status_text = "failed";
            $transaction_status = 3;
            $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];

        }

        return $data;
    }

    public function getTranStatus($user,$transactionId,$calling_from, $request){
        $string_file = $this->getStringFile($user->merchant_id);
        $payment_option = PaymentOption::where('slug', 'PAWAPAY')->first();
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        if (empty($paymentConfig)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }

        $transaction =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
        if(!empty($transaction)){
            if($transaction->request_status == 3 || $transaction->request_status == 2){
                return $transaction;
            }
        }

        $apiToken = $paymentConfig->api_secret_key;
        $base_url = $this->getUrl($paymentConfig->gateway_condition);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $base_url .'/deposits/'.$transactionId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Authorization:Bearer ' .$apiToken,
            ),
        ));


        $response = json_decode(curl_exec($curl),true);
        \Log::channel('pawapay')->emergency(['response_status'=>$response]);
        if(isset($response[0]['status'])){
            $transaction =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
            if (!empty($transaction)) {
                if($response[0]['status'] == "COMPLETED"){
                    DB::table('transactions')
                        ->where(['payment_transaction_id' => $transactionId])
                        ->update(['request_status'=>2,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($response)]);
                }elseif($response[0]['status'] == 'FAILED' || $response[0]['status'] == "REJECTED"){
                    DB::table('transactions')
                        ->where(['payment_transaction_id' => $transactionId])
                        ->update(['request_status'=>3,'updated_at' => date('Y-m-d H:i:s'),'payment_transaction'=> json_encode($response)]);
                }
            }
        }

        return $response;
    }

    public function Success(Request $request)
    {
        \Log::channel('pawapay')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Fail(Request $request)
    {
        \Log::channel('pawapay')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }
}