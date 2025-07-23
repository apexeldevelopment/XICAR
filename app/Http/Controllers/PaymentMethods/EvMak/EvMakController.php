<?php

namespace App\Http\Controllers\PaymentMethods\EvMak;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\DriverCard;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\UserCard;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class EvMakController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getEvMakConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'EVMAK')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function EvMakSendRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'amount' => 'required',
            'request_from' => 'required',
            'mobile_number' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        
        $user = $request->type == 1 ? $request->user('api') : $request->user('api-driver');
        $paymentConfig = $this->getEvMakConfig($user->merchant_id);
        $hash_text = $paymentConfig->api_secret_key.'|'.date('d-m-Y');
        $hash = md5($hash_text);
        $callback_url = route('evmak.callback');
        $request_url = $paymentConfig->gateway_condition == 2 ? "https://vodaapi.evmak.com/test/" : "https://vodaapi.evmak.com/prd/";
        $phone = str_replace('+', '', $request->mobile_number);
        
        $post_data = [
            'api_source' => $paymentConfig->api_public_key,
            'api_to' => $request->request_from,
            'amount' => $request->amount,
            'product' => $paymentConfig->api_public_key,
            'callback' => $callback_url,
            'hash' => $hash,
            'user' => $paymentConfig->api_secret_key,
            'mobileNo' => $phone,
            'reference' => date('YmdHis'),
        ];
        // dd($post_data,json_encode($post_data));

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response,true);
        if(!empty($response) && $response['response_code'] == 200 && ($response['response_desc'] == "0" || $response['response_desc'] == "Success." || $response['response_desc'] == "Request send to the user")){
            DB::table('mpessa_transactions')->insert([
                'merchant_id' => $user->merchant_id,
                'user_id' => $user->id,
                'type' => $request->type,
                'checkout_request_id' => $response['order_id'],
                'amount' => $request->amount,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return $this->successResponse("Request Processed!!");
        }else{
            return $this->failedResponse($response['response_desc'].' & Something Went Wrong!!');
        }
    }

    public function EvMakRequestCallback(Request $request){
        $data = $request->all();
        \Log::channel('EvMak')->emergency($data);
        if(($data['TransactionStatus'] == "Success" || $data['TransactionStatus'] == true) && ($data['ResultType'] == "Completed" || $data['ResultType'] == "Dear customer, your payment is successfully completed")){
            $trans = DB::table('mpessa_transactions')->where(['checkout_request_id' => $data['ThirdPartyReference']])->first();
            if(!empty($trans)){
                DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => $data['ThirdPartyReference']])
                    ->update(['payment_status' => 'success', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);

                if ($trans->type == 1) {
                    $receipt = "Application : " . $data['TransID'];
                    $paramArray = array(
                        'user_id' => $trans->user_id,
                        'booking_id' => NULL,
                        'amount' => $trans->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $data['ThirdPartyReference'],
                        'notification_type' => 89
                    );
                    WalletTransaction::UserWalletCredit($paramArray);
                } else {
                    $receipt = "Application : " . $data['TransID'];
                    $paramArray = array(
                        'driver_id' => $trans->user_id,
                        'booking_id' => NULL,
                        'amount' => $trans->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 3,
                        'receipt' => $receipt,
                        'transaction_id' => $data['ThirdPartyReference'],
                        'notification_type' => 89
                    );
                    WalletTransaction::WalletCredit($paramArray);
                }

                $success_data = array(
                    "amount" => $data['Amount'],
                    "transcode" => $data['ThirdPartyReference'],
                    "user_id" => $trans->user_id,
                    "status" => "COMPLETE",
                );
                \Log::channel('EvMak')->emergency($success_data);
                return response()->json(['Status' => "Success"]);
            }
        }else{
            $trans = DB::table('mpessa_transactions')->where(['checkout_request_id' => $data['ThirdPartyReference']])->first();
            if(!empty($trans)){
                DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => $data['ThirdPartyReference']])
                    ->update(['payment_status' => 'failed', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);

                $message = $data['TransactionStatus'];
                $data = array(
                    'notification_type' => 'PAYMENT',
                    'segment_type' => "PAYMENT",
                    'segment_data' => [],
//            'notification_gen_time' => time(),
                );
                $merchant_id = $trans->merchant_id;
                $arr_param = array(
                    'large_icon' => "",
                    'data'=>$data,
                    'message'=>$message,
                    'merchant_id'=>$merchant_id,
                    'title' => $data['ResultType'],
                );
                if ($trans->type == 1) {
                    $arr_param['user_id'] = $trans->user_id;
                    Onesignal::UserPushMessage($arr_param);
                } else {
                    $arr_param['driver_id'] = $trans->user_id;
                    Onesignal::DriverPushMessage($arr_param);
                }
                $failed_data = array(
                    "user_id" => $trans->user_id,
                    "status" => "FAILED",
                );
                \Log::channel('EvMak')->emergency($failed_data);
                return response()->json(['Status' => "Failed"]);
            }
        }
    }
    
    public function EvMakPayOutRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'amount' => 'required',
            'request_from' => 'required',
            'mobile_number' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        
        $user = $request->type == 1 ? $request->user('api') : $request->user('api-driver');
        $paymentConfig = $this->getEvMakConfig($user->merchant_id);
        $hash_text = $paymentConfig->api_secret_key.'|'.date('d-m-Y');
        $hash = md5($hash_text);
        $callback_url = route('evmak.payout.callback');
        $request_url = $paymentConfig->gateway_condition == 2 ? "http://test-dash.evmak.com/sandbox/payout/" : "https://vodaapi.evmak.com/payout/";
        $phone = str_replace('+', '', $request->mobile_number);
        
        $post_data = [
            'user' => $paymentConfig->api_secret_key,
            'hash' => $hash,
            'amount' => $request->amount,
            'reference' => date('YmdHis'),
            'method' => 'PayOut',
            'methodNumber' => $phone,
            'network' => $request->request_from,
            'notifyurl' => $callback_url,
        ];
        // dd($post_data,json_encode($post_data));

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response,true);
        if(!empty($response) && $response['response_code'] == 200 && ($response['response_desc'] == "Submitted")){
            DB::table('mpessa_transactions')->insert([
                'merchant_id' => $user->merchant_id,
                'user_id' => $user->id,
                'type' => $request->type,
                'checkout_request_id' => 'PayOut_'.$response['order_id'],
                'amount' => $request->amount,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return $this->successResponse("Request Processed, It will take approx 30 minutes to reflect into your account!!");
        }else{
            return $this->failedResponse($response['response_desc'].' & Something Went Wrong!!');
        }
    }
    
    public function EvMakPayOutCallback(Request $request){
        $data = $request->all();
        \Log::channel('EvMak')->emergency($data);
        if($data['PayoutStatus'] == "Success" && $data['StatusCode'] == 200){
            $trans = DB::table('mpessa_transactions')->where(['checkout_request_id' => 'PayOut_'.$data['ReferenceNo']])->first();
            if(!empty($trans)){
                DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => 'PayOut_'.$data['ReferenceNo']])
                    ->update(['payment_status' => 'success', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);

                if ($trans->type == 1) {
                    $receipt = "Application : " . $data['ReferenceNo'];
                    $paramArray = array(
                        'user_id' => $trans->user_id,
                        'booking_id' => NULL,
                        'amount' => $trans->amount,
                        'narration' => 17,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $data['ReferenceNo'],
                        'notification_type' => 89
                    );
                    WalletTransaction::UserWalletDebit($paramArray);
                } else {
                    $receipt = "Application : " . $data['ReferenceNo'];
                    $paramArray = array(
                        'driver_id' => $trans->user_id,
                        'booking_id' => NULL,
                        'amount' => $trans->amount,
                        'narration' => 24,
                        'platform' => 2,
                        'payment_method' => 3,
                        'receipt' => $receipt,
                        'transaction_id' => $data['ReferenceNo'],
                        'notification_type' => 89
                    );
                    WalletTransaction::WalletDeduct($paramArray);
                }

                $success_data = array(
                    "amount" => $data['Amount'],
                    "transcode" => $data['ReferenceNo'],
                    "user_id" => $trans->user_id,
                    "status" => "COMPLETE",
                );
                \Log::channel('EvMak')->emergency($success_data);
                return response()->json(['Status' => "Success"]);
            }
        }else{
            $trans = DB::table('mpessa_transactions')->where(['checkout_request_id' => 'PayOut_'.$data['ReferenceNo']])->first();
            if(!empty($trans)){
                DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => 'PayOut_'.$data['ReferenceNo']])
                    ->update(['payment_status' => 'failed', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($request->all(), true)]);

                $message = $data['PayoutStatus'];
                $notification_data = array(
                    'notification_type' => 'PAYMENT',
                    'segment_type' => "PAYMENT",
                    'segment_data' => [],
//            'notification_gen_time' => time(),
                );
                $merchant_id = $trans->merchant_id;
                $arr_param = array(
                    'large_icon' => "",
                    'data'=>$notification_data,
                    'message'=>$message,
                    'merchant_id'=>$merchant_id,
                    'title' => $data['PayoutStatus'],
                );
                if ($trans->type == 1) {
                    $arr_param['user_id'] = $trans->user_id;
                    Onesignal::UserPushMessage($arr_param);
                } else {
                    $arr_param['driver_id'] = $trans->user_id;
                    Onesignal::DriverPushMessage($arr_param);
                }
                $failed_data = array(
                    "user_id" => $trans->user_id,
                    "status" => "FAILED",
                );
                \Log::channel('EvMak')->emergency($failed_data);
                return response()->json(['Status' => "Failed"]);
            }
        }
    }

//    public function teliberrSuccess(Request $request){
//        $response = "Success!";
//        return view('payment/telebirr_pay/callback', compact('response'));
//    }
//
//    public function teliberrFailed(Request $request){
//        $response = !empty($request->msg) ? $request->msg : "Failed!";
//        return view('payment/telebirr_pay/callback', compact('response'));
//    }
}
