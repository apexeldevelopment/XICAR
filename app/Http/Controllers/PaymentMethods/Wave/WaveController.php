<?php

namespace App\Http\Controllers\PaymentMethods\Wave;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WaveController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getWaveConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'WAVE')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    
    public function generateWaveUrl($request, $payment_option_config, $calling_from){
        DB::beginTransaction();
        try{
            if($calling_from == "USER"){
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $currency = $user->Country->isoCode;
                $status = 1;
            }else{
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $currency = $driver->Country->isoCode;
                $status = 2;
            }

            $data=[
                'amount' => $request->amount,
                'currency' => $request->currency,
                'error_url' => route('wave.fail'),
                'success_url' => route('wave.success'),
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.wave.com/v1/checkout/sessions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$payment_option_config->api_secret_key,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $response = json_decode($response,true);
            if(!empty($response['id'])){
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'checkout_id' => NULL,
                    'amount' => $request->amount,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'payment_transaction_id' => $response['id'],
                    'payment_transaction' => NULL,
                    'request_status' => 1,
                    'status' => $status,
                ]);
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        DB::commit();

        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $response['wave_launch_url'] ?? '',
            'success_url' => route('wave.success'),
            'fail_url' => route('wave.fail'),
            'transaction_id' => $response['id']
        ];
    }

    public function WaveNotify(Request $request){
        \Log::channel('wave_webhook')->emergency($request->all());
        $data = $request->all();
        if(!empty($data) && $data['type'] == 'checkout.session.completed' && $data['data']['payment_status'] == 'succeeded'){
            $trans = DB::table('transactions')->where(['payment_transaction_id' => $data['data']['id']])->first();
            if (!empty($trans)){
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
                }else{
                    $paramArray['driver_id'] = $trans->driver_id;
                    WalletTransaction::WalletCredit($paramArray);
                }
            }
            DB::table('transactions')
                ->where(['payment_transaction_id' => $data['data']['id']])
                ->update(['request_status' => 2, 'payment_transaction' => json_encode($request->all())]);
        }else{
            DB::table('transactions')
                ->where(['payment_transaction_id' => $data['data']['id']])
                ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all())]);
        }
    }

    public function paymentStatus($request){
        $tx_reference = $request->transaction_id; // order id
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $tx_reference)->first();
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

    public function WaveSuccess(){
        echo "<h3 style='text-align: center'>Success!</h3>";
        return true;
    }

    public function WaveFailed(){
        echo "<h3 style='text-align: center'>Failed!</h3>";
        return true;
//        return view('payment/telebirr_pay/callback', compact('response'));
    }
    
    public function WavePayout(Request $request){
        $validator = Validator::make($request->all(), [
            'calling_from' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            if($request->calling_from == "USER"){
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $currency = $user->Country->isoCode;
                $name = $user->first_name.' '.$user->last_name;
                $phone = $user->UserPhone;
                $wallet_amount = $user->wallet_balance;
            }else{
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $currency = $driver->Country->isoCode;
                $name = $driver->first_name.' '.$driver->last_name;
                $phone = $driver->phoneNumber;
                $wallet_amount = $driver->wallet_money;
            }
            
            $string_file = $this->getStringFile($merchant_id);
            if($wallet_amount < $request->amount){
                return $this->failedResponse(trans("$string_file.low_wallet_payout"));
            }
            
            $payment_option_config = $this->getWaveConfig($merchant_id);
            $data = [
                'currency' => $currency,
                'receive_amount' => $request->amount,
                'name' => $name,
                'mobile' => $phone,
            ];
            $uuid = getUUID();

            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://api.wave.com/v1/payout',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => json_encode($data),
              CURLOPT_HTTPHEADER => array(
                'idempotency-key: '.$uuid,
                'Authorization: Bearer '.$payment_option_config->auth_token,
                'Content-Type: application/json'
              ),
            ));
            
            $response = curl_exec($curl);
            
            curl_close($curl);
            // echo $response;
            $response = json_decode($response,true);
            if(!empty($response['id']) && isset($response['status']) && $response['status'] = "succeeded"){
                $receipt = "Application : " . $response['id'];
                $amount = $response['receive_amount']-$response['fee'];
                $amount = $amount + 0.04*$amount;
                $paramArray = array(
                    'booking_id' => NULL,
                    'amount' => $amount,
                    'platform' => 2,
                    'payment_method' => 2,
                    'receipt' => $receipt,
                    'transaction_id' => $response['id'],
                    'notification_type' => 89
                );
                if ($request->calling_from == "USER") {
                    $paramArray['user_id'] = $id;
                    $paramArray['narration'] = 17;
                    WalletTransaction::UserWalletDebit($paramArray);
                   \Log::channel('debugger')->emergency(["LOG_FOR"=> "WAVE_PAYOUT", "calling_from"=> "USER", "response"=>$response]);             

                } else {
                    $paramArray['driver_id'] = $id;
                    $paramArray['narration'] = 24;
                    WalletTransaction::WalletDeduct($paramArray);
                    \Log::channel('debugger')->emergency(["LOG_FOR"=> "WAVE_PAYOUT", "calling_from"=> "DRIVER", "response"=>$response]);

                }
            }else{
                $message = isset($response['details']) ? $response['details'][0]['msg'] : trans("$string_file.some_thing_went_wrong");
                return $this->failedResponse($message);
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"));
    }
}
