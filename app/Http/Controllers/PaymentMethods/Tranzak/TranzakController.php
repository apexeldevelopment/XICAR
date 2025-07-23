<?php

namespace App\Http\Controllers\PaymentMethods\Tranzak;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TranzakController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    
    public function getTranzakConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'TRANZAK')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getBaseUrl($env){
        return $env == 1 ? 'https://dsapi.tranzak.me/' : 'https://sandbox.dsapi.tranzak.me/';
    }
    
    public function getAuthToken($appId,$appKey,$baseUrl){
        try{
            $data = [
                'appId'=> $appId,
                'appKey'=> $appKey
            ];

            $curl = curl_init();
            

            curl_setopt_array($curl, array(
              CURLOPT_URL => $baseUrl.'auth/token',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>json_encode($data),
              CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json'
              ),
            ));
            

            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response,true);
            if(isset($res['data']['token'])){
                return $res['data']['token'];
            }
            else{
                return '';
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function MakeTranzakPayment(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required',
            'mobileWalletNumber'=> 'required',
            'calling_for' => 'required',
            'booking_id' => 'required_if:calling_for,BOOKING'
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        
        try{
            $type = $request->type;
            if($type == "DRIVER") {
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
            $paymentConfig = $this->getTranzakConfig($merchant_id);
            $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);
            $appKey = $paymentConfig->api_secret_key;
            $appId = $paymentConfig->api_public_key;
            $redirectUrl = $paymentConfig->payment_redirect_url;
            
            $string_file = $this->getStringFile($merchant_id);
            
            $token = $this->getAuthToken($appId, $appKey,$baseUrl);
            if(empty($token)){
                throw new \Exception('Token not generated');
            }
            
           
            $mobile = $request->mobileWalletNumber;
            
            $description = 'Wallet Payment Description';
            $payerNote = 'Wallet Payment Note';
            
            $data = [
                'mobileWalletNumber'=> $mobile,
                'amount'=> $request->amount,
                'currencyCode'=> $currency,
                'description'=> $description,
                'payerNote'=> $payerNote,
                // 'returnUrl'=> route('tranzak-redirect')
            ];
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => $baseUrl.'xp021/v1/request/create-mobile-wallet-charge',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>json_encode($data),
              CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$token,
                'Content-Type: application/json'
              ),
            ));

            $response = json_decode(curl_exec($curl),true);
            curl_close($curl);
            if(isset($response['data']['status']) && $response['data']['status'] == 'PAYMENT_IN_PROGRESS') {
                $transactionId = $response['data']['requestId'];
                $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
                DB::table('transactions')->insert([
                    'user_id' => $type == "USER" ? $id : NULL,
                    'driver_id' => $type == "DRIVER" ? $id : NULL,
                    'status' => $calling_for,
                    'booking_id' => $request->booking_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $transactionId,
                    'amount' => $request->amount,
                    'payment_option_id' => $paymentConfig->id,
                    'request_status'=> 1,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                DB::commit();
        
                return $this->successResponse(trans("$string_file.confirm_transaction"),$response);

            }else{
                throw new \Exception($response['data']['message']);
            }
            

        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }

        
        
    }

    public function Redirect(Request $request){
        \Log::channel('Tranzak_redirect')->emergency($request->all());
        $data = $request->all();
        $trans = DB::table('transactions')->where(['request_status' => 1, 'payment_transaction_id' => $data['resource']['requestId']])->first();
        if (!empty($trans)) {
            $payment_status = $data['resource']['status'] == 'SUCCESSFUL' ? 2 : 3;
            if($payment_status == 2){
                DB::table('transactions')
                ->where(['payment_transaction_id' => $data['resource']['requestId']])
                ->update(['request_status' => $payment_status, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
                $receipt = "Application : " . $data['resource']['requestId'];
                $paramArray = array(
                    'booking_id' => NULL,
                    'amount' => $trans->amount,
                    'narration' => 2,
                    'platform' => 2,
                    'payment_method' => 2,
                    'receipt' => $receipt,
                    'transaction_id' => $data['resource']['requestId'],
                );

                if($trans->status == 1){
                    $paramArray['user_id'] = $trans->user_id;
                    WalletTransaction::UserWalletCredit($paramArray);
                }elseif($trans->status == 2){
                    $paramArray['driver_id'] = $trans->driver_id;
                    WalletTransaction::WalletCredit($paramArray);
                }else{
                    $booking = Booking::find($trans->booking_id);
                    $booking->payment_status = 1;
                    $booking->save();

                    $string_file = $this->getStringFile(NULL, $booking->Merchant);
                    $title = trans("$string_file.payment_success");
                    $message = trans("$string_file.payment_done");
                    $data['notification_type'] = 'PAYMENT_COMPLETE';
                    $data['segment_type'] = $booking->Segment->slag;
                    $data['segment_data'] = ['id'=>$booking->id,'handyman_order_id'=>NULL];
                    $arr_param = ['data' => $data, 'message' => $message, 'merchant_id' => $booking->merchant_id, 'title' => $title, 'large_icon' => ""];
                    $user_param = $arr_param;
                    $user_param['user_id'] = $booking->user_id;
                    Onesignal::UserPushMessage($user_param);
                    $driver_param = $arr_param;
                    $driver_param['driver_id'] = $booking->driver_id;
                    Onesignal::DriverPushMessage($driver_param);
                }
            }else{
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $data['resource']['requestId']])
                    ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
            }
            
        }else{
            \Log::channel('Tranzak_redirect')->emergency(['message' => 'Transaction Not Found!']);
        }
        
    }
    
    // public function Success(Request $request)
    // {
    //     $message = !empty($request->msg) ? $request->msg : "Success";
    //     echo "<h2 style='text-align: center;color: green'>" . $message . "</h2>";
    // }

    // public function Fail(Request $request)
    // {
    //     $message = !empty($request->msg) ? $request->msg : "Failed";
    //     echo "<h2 style='text-align: center;color: red'>" . $message . "</h2>";
    // }
}
