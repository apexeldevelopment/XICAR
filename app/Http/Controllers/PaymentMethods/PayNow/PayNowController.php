<?php

namespace App\Http\Controllers\PaymentMethods\PayNow;

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

class PayNowController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getPayNowConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'PAYNOW')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getHashKey($intKey, $intId,$reference,$amount,$returnUrl,$resultUrl,$email){
        try{
            $values = [
                "id" => $intId,
                "reference" => $reference,
                "amount" => $amount,
                "additionalinfo" => "done",
                "returnurl" => $returnUrl,
                "resulturl" => $resultUrl,
                "authemail" => $email,
                "status" => "Message"
            ];
            
            $integration_key = $intKey;
            
            // Generate hash
            $hash = $this->make_hash($values, $integration_key);
            
            // Add hash to the values
            $values['hash'] = $hash;
            
            return $values;
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
    
    public function make_hash(array $values, $integration_key)
    {
        $string = "";
        foreach ($values as $key => $value) {
            if (strtoupper($key) != "HASH") {
                $string .= $value;
            }
        }
        $string .= $integration_key;
        $hash = hash("sha512", $string);
        return strtoupper($hash);
    }

    //on production
    public function initiatePaymentRequest($request, $payment_option_config, $calling_from)
    {
        try{
            $integerationKey = $payment_option_config->api_secret_key;
            $integerationId = $payment_option_config->api_public_key;

            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
                $email = $user->email;
            }
            else{
                $user = $request->user('api');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
                $email = $user->email;
            }
            
            $reference = 'REFERENCE_MERCHANT_'.time();
            $amount = $request->amount;
            $returnUrl = route('paynow-return',['transactionId'=>$reference]);
            $resultUrl = route('paynow-result');
            
            $hashedData = $this->getHashKey($integerationKey, $integerationId,$reference,$amount,$returnUrl,$resultUrl,$email);
            
            if(empty($hashedData)){
                throw new \Exception('Hash not generated');
            }
            $payment_option = $this->getPayNowConfig($merchant_id);

            $curl = curl_init("https://www.paynow.co.zw/interface/initiatetransaction");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($hashedData)); // URL-encode the post fields
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Only if your environment does not have a valid SSL certificate
            
                $response = curl_exec($curl);
                if ($response === false) {
                    die(curl_error($curl));
                }
                curl_close($curl);
                
                // Parse the response string into an array
                parse_str($response, $parsedResponse);
            if(isset($parsedResponse['status']) && $parsedResponse['status'] == "Ok") {
                $browseUrl = $parsedResponse['browserurl'];
                $pollUrl = $parsedResponse['pollurl'];
                
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $reference,
                    'amount' => $request->amount,
                    'payment_option_id' => $payment_option->payment_option_id,
                    'request_status'=> 1,
                    'reference_id'=> "",
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }else{
                throw new \Exception('Transaction not generated');
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }


        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $parsedResponse['browserurl'] ?? '',
            'transaction_id' => $reference,
            'success_url' => route('paynow-success'),
            'fail_url' => route('paynow-fail'),
        ];
    }

    public function return(Request $request)
    {
        \Log::channel('pay_now')->emergency($request->all());
        if($request->transactionId){
            $transaction = DB::table('transactions')->where('payment_transaction_id',$request->transactionId)->first();
            if($transaction->request_status == 2 && $transaction->status_message == 'SUCCESS'){
                return redirect(route('paynow-success'));
            }
            else{
                return redirect(route('paynow-fail'));
            }
        }else{
            throw new \Exception('Reference not found');
        }
        
    }

    public function Success(Request $request)
    {
        \Log::channel('pay_now')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Fail(Request $request)
    {
        \Log::channel('pay_now')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }

    public function result(Request $request)
    {
        \Log::channel('pay_now')->emergency($request->all());
        $transId = $request['reference'];
        $pollUrl = $request['pollurl'];
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $pollUrl,
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
                ),
            ));
            $response = curl_exec($curl);
            
            if ($response === false) {
                die(curl_error($curl));
            }
            curl_close($curl);
            parse_str($response, $parsedResponse);
        if(isset($request['status']) && $request['status'] == 'Paid'){
            
            if($request['reference']){
                DB::table('transactions')->where('payment_transaction_id', $transId)->update([
                    'request_status' => 2,
                    'status_message' => "SUCCESS",
                    'payment_transaction'=> json_encode($parsedResponse)
                ]);
            }else{
                DB::table('transactions')->where('payment_transaction_id', $transId)->update([
                    'request_status' => 3,
                    'status_message' => "FAIL",
                    'payment_transaction'=> json_encode($parsedResponse)
                ]);
            }
            
        }else{
            throw new \Exception('Reference not found');
        }
        
    }
    
    public function paymentStatus(Request $request){
        $transactionId = $request->transaction_id; 
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        if($transaction_table->request_status == 1)
        {
            $request_status_text = "processing";
        }
        else if($transaction_table->request_status == 2)
        {
            $request_status_text = "success";
        }
        else
        {
            $request_status_text = "failed";
        }
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
    }
}
