<?php

namespace App\Http\Controllers\PaymentMethods\BudPay;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BudPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getBudPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'BUDPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getServiceProvider(Request $request){
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'slug'=> 'required',
            'currency'=> 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
        $string_file = $this->getStringFile(null, $user->Merchant);
        $merchant_id = $user->merchant_id;
        $payment_option_config = $this->getBudPayConfig($merchant_id);
        $secret_key = $payment_option_config->api_secret_key;
        $currency = $request->currency;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.budpay.com/api/mobile_money/providers/'.$currency,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '. $secret_key,
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response,true);    

        if(isset($res['banks']) && count($res['banks']) > 0){
            return $this->successResponse(trans("$string_file.success"), $res['banks']);
        }else{
            return $this->failedResponse('No Service Provider for this country or check selected country');
        }


    }
    public function initiatePaymentRequest($request, $payment_option_config, $calling_from)
    {
        try{
            $secret_key = $payment_option_config->api_secret_key;
            $public_key = $payment_option_config->api_public_key;

            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
                $email = $user->email;
            }else{
                $user = $request->user('api');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
                $email = $user->email;
              
            }
            $amount = (string)$request->amount;
            $bankName = $request->bank_name;
            $bankCode = $request->bank_code;
            $currency = $request->currency;
            $phone = $request->phone;
            $reference = "Ref_".time();
            $transaction = "Trans_".time();
            $data = array(
               "amount"=> $amount, 
                "bankName"=>$bankName, 
                "name"=>$bankName, 
                "bankCode"=>$bankCode, 
                "callbackUrl"=>route('budpay-notify'), 
                "currency"=>$currency,
                "description"=> "Server to Server test", 
                "phone"=>$phone,
                "reference"=>$reference
            );
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.budpay.com/api/s2s/v2/momo/payment_request',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '. $secret_key,
                'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response);
            if(!empty($response->success) && $response->success == true){
                
                DB::table('transactions')->insert([
                    'user_id' => $request->calling_from == "USER" ? $id : NULL,
                    'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                    'status' => $calling_from,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id' => $reference,
                    'amount' => $request->amount,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'request_status' => 1,
                    "payment_mode" => "Third-party App",
                    'status_message' => 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                return array(
                  'transaction_id' => $reference,
                  'message'=> $response->CustomerMessage,
                  'success_url' => route('budpay-success'),
                  'fail_url' => route('budpay-fail')
                );
                
                
            }else{
                return array(
                    'result'=> 0,
                    'response'=> $response->message
                );
            }
       

        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function Success(Request $request)
    {
        \Log::channel('BudPay')->emergency($request->all());
            DB::table('transactions')->where('payment_transaction_id', $request->reference)->update([
                    'request_status' => 2,'status_message' => $request->status,'payment_transaction'=>json_encode($request)]);
            echo "<h1>Success</h1>";
    }

    public function Failure(Request $request)
    {
        \Log::channel('BudPay')->emergency($request->all());
            DB::table('transactions')->where('payment_transaction_id', $request->reference)->update([
            'request_status' => 3,'status_message' => $request->status,'payment_transaction'=>json_encode($request)]);
            echo "<h1>Failed</h1>";
    }

    public function returnUrl(Request $request)
    {
        \Log::channel('BudPay')->emergency($request->all());
        if($request->status == "success")
        {
            $arr = array(
                "request_status" => 2,
                "status_message" => "SUCCESS"
            );
            $transaction_id = $request->reference;
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update($arr);
            
        }
        elseif($request->status == "Pending")
        {
            $arr = array(
                "request_status" => 1,
                "status_message" => "PENDING"
            );
            $transaction_id = $request->reference;
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update($arr);
            
        }
        elseif(in_array($request->status,['Expired','User canceled']))
        {
            $arr = array(
                "request_status" => 3,
                "status_message" => "FAILED"
            );
            $transaction_id = $request->reference;
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update($arr);
             
        }
        else{
            $arr = array(
                "request_status" => 3,
                "status_message" => "OTHER"
            );
            $transaction_id = $request->reference;
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update($arr);

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
        $transactionId = $request->transaction_id; 
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

}