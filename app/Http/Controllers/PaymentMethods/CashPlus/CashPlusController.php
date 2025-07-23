<?php

namespace App\Http\Controllers\PaymentMethods\CashPlus;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;
use App\Models\Order;
use App\Models\HandymanOrder;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CashPlusController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getCashPlusConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'CASHPLUS')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    
    //Worked only for wallet recharge and used in driver app
    public function generateToken($request, $payment_option_config, $calling_from){
        try{
            $merchantCode = $payment_option_config->api_public_key;
            $secretKey = $payment_option_config->api_secret_key;
            
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }

            $gatewayUrl = $payment_option_config->gateway_condition == 1 ? 'https://cpms.cashplus.ma/cpws/cpmarchand/index.cfm/generate_token' : 'https://moneyservicedev.cashplus.ma:4434/cpws/cpmarchand/index.cfm/generate_token';
            $payment_option = $this->getCashPlusConfig($merchant_id);
            $transaction_id = 'TRANS_'.$id.'_'.time();
            $reference = 'REF_'.uniqid();
            $amount = $request->amount;
            $hmac = $this->generateHmac($merchantCode,$secretKey,$amount);
            $data = [
                "request_id"=>$reference,
                "amount"=> $amount,
                "fees"=> 0,
                "marchand_code"=> $merchantCode,
                "hmac"=>$hmac,
                "json_data"=>""
            ];
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $gatewayUrl,
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
                ),
            ));
            
            
            $response = json_decode(curl_exec($curl),true);
            \Log::channel('cash_plus')->emergency(['res'=>$response]);
            curl_close($curl);
            if(isset($response['SUCCESS']) && $response['SUCCESS'] == 1 && isset($response['TOKEN'])) {
                    DB::table('transactions')->insert([
                        'user_id' => $calling_from == "USER" ? $id : NULL,
                        'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                        'status' => $calling_from == "DRIVER" ? 2 : 1,
                        'merchant_id' => $merchant_id,
                        'payment_transaction_id'=> $transaction_id,
                        'amount' => $amount,
                        'payment_option_id' => $payment_option->payment_option_id,
                        'request_status'=> 1,
                        'checkout_id'=> $response['TOKEN'],
                        'reference_id'=> $reference,
                        'status_message'=> 'PENDING',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    DB::commit();
                    $data=[
                        "transaction_id"=> $transaction_id,
                        "generated_token"=> $response['TOKEN']
                    ];
                    
                    return $data;
            }else{
                throw new \Exception($response['MESSAGE']);
            }
             $data=[
                    "transaction_id"=> $transaction_id,
                    "generated_token"=> "",
                    "result"=> 0
                ];
                    
            return $data;
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        
        
    }
    
    public function generateHmac($merchantCode,$secretKey,$amount){
        $data = $merchantCode . $secretKey . $amount;
        // Generate the HMAC using SHA-256 (SHA2)
        $hmac = strtoupper(hash('sha256', $data));
        return $hmac;
    }
    
    public function Callback(Request $request){
        \Log::channel('cash_plus')->emergency(['callback_cashplus'=> $request->all()]);
        $data = $request->all();
        try{
            if(isset($data) && !empty($data['date_reception']) && !empty($data['request_id']) && !empty($data['date_reception'])){
                $trans = DB::table('transactions')->where(['request_status' => 1, 'reference_id' => $data['request_id']])->first();
                if(!empty($trans)){
                $token = $trans->checkout_id;
                    DB::table('transactions')
                    ->where(['reference_id' => $data['request_id']])
                    ->update(['request_status' => 2, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
                    $receipt = "Application : " . $trans->payment_transaction_id;
                    $paramArray = array(
                        'booking_id' => NULL,
                        'amount' => $trans->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $trans->payment_transaction_id,
                    );
                    if($trans->status == 1){
                        $paramArray['user_id'] = $trans->user_id;
                        WalletTransaction::UserWalletCredit($paramArray);
                    }elseif($trans->status == 2){
                        $paramArray['driver_id'] = $trans->driver_id;
                        WalletTransaction::WalletCredit($paramArray);
                    }
                }
            }
        }
        catch(\Exception $e){
            \Log::channel('cash_plus')->emergency(['callback_errorlog'=> $e->getMessage()]);
        }
    }
    
    public function listTransaction(Request $request){
        $validator = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date'=> 'required',
            'calling_from'=> 'required',
            'payment_option_id'=> 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        
        $calling_from = $request->calling_from;
        $start_date = $request->start_date . ' 00:00:00';
        $end_date = $request->end_date. ' 23:59:59';
        $payment_option_id = $request->payment_option_id;
        
        if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
        
        $transactions = DB::table('transactions')->where(['merchant_id'=> $merchant_id,'payment_option_id'=> $payment_option_id])->whereBetween('created_at', [$start_date, $end_date])->orderBy('created_at', 'desc')->get();
        $data = [];
        foreach($transactions as $trans){
            $transaction = [
                'token'=> $trans->checkout_id,
                'transaction_id'=> $trans->payment_transaction_id,
                'request_id'=> $trans->reference_id,
                'is_paid'=> $trans->request_status == 2 ? true : false,
                'amount'=> $trans->amount,
                'created_at'=> $trans->created_at
            ];
            array_push($data,$transaction);
        }
        
        return $this->successResponse('SUCCESS',$data);
        
        
    }
    
}