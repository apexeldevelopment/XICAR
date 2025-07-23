<?php

namespace App\Http\Controllers\PaymentMethods\Payaw;

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

class PayawPaymentController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function getPayawConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'PAYAW')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function MakePayawPayment(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required',
            'calling_for' => 'required',
            'phone'=> 'required'
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        
        try{
            $type = $request->type;
            if($type == "DRIVER") {
                $user = $request->user('api-driver');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            $paymentConfig = $this->getPayawConfig($merchant_id);
            $secretKey = $paymentConfig->api_secret_key;
            $apiKey = $paymentConfig->api_public_key;
            
            $string_file = $this->getStringFile($merchant_id);
            
            $key_text = $apiKey . ':' . $secretKey;
            $token = base64_encode($key_text);
            $reference = 'ref_'.time();
            $data = [
                 "userId"=>$request->phone,
                 "amount"=>$request->amount,
                 "apiKey"=>$apiKey,
                 "checkNumber"=>$reference
            ];
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://vendor.pay.aw/titan-vendor-services/v1/webpayment/request/client/vendor',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>json_encode($data),
              CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.$token,
                'Content-Type: application/json'
              ),
            ));

            $response = json_decode(curl_exec($curl),true);
            curl_close($curl);
            if(isset($response['message']) && $response['message'] == 'Payment successful') {
                $transactionId = $response['requestId'];
                $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
                DB::table('transactions')->insert([
                    'user_id' => $type == "USER" ? $id : NULL,
                    'driver_id' => $type == "DRIVER" ? $id : NULL,
                    'status' => $calling_for,
                    'booking_id' => $request->booking_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $transactionId,
                    'amount' => $request->amount,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'reference_id'=>$reference,
                    'request_status'=> 2,
                    'status_message'=> 'SUCCESS',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            }elseif(isset($response) && isset($response['requestId'])){
                $transactionId = $response['requestId'];
                $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
                 DB::table('transactions')->insert([
                    'user_id' => $type == "USER" ? $id : NULL,
                    'driver_id' => $type == "DRIVER" ? $id : NULL,
                    'status' => $calling_for,
                    'booking_id' => $request->booking_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $transactionId,
                    'amount' => $request->amount,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'reference_id'=>$reference,
                    'request_status'=> 3,
                    'status_message'=> 'FAILED',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
            }
            else{
                return error_response($response['message']);
            }
            
            DB::commit();

            $data = [
                'transaction_status'=> $response['message'],
                'transaction_id' => $response['requestId'],
                'success_url' => route('payaw-success'),
                'fail_url' => route('payaw-fail'),
            ];
        
            return success_response($response['message'],$data);
            

        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
    
    public function Success(Request $request)
    {
        \Log::channel('Payaw')->emergency($request->all());
        echo "<h2 style='text-align: center;color: green'>SUCCESS</h2>";
    }

    public function Fail(Request $request)
    {
        \Log::channel('Payaw')->emergency($request->all());
        echo "<h2 style='text-align: center;color: red'>Failed</h2>";
    }
    
    public function PaymentStatus(Request $request){
        $transactionId = $request->transaction_id; 
        $trans =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
        $payment_status =   $trans->request_status == 2 ?  true : false;
        $data = [];
        if($trans->request_status == 1)
        {
            $request_status_text = "processing";
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text];
        }
        else if($trans->request_status == 2)
        {
            $request_status_text = "success";
            $receipt = "Application : " . $transactionId;
                $paramArray = array(
                    'booking_id' => NULL,
                    'amount' => $trans->amount,
                    'narration' => 2,
                    'platform' => 2,
                    'payment_method' => 2,
                    'receipt' => $receipt,
                    'transaction_id' => $transactionId,
                );

                if($trans->status == 1){
                    $paramArray['user_id'] = $trans->user_id;
                    WalletTransaction::UserWalletCredit($paramArray);
                }elseif($trans->status == 2){
                    $paramArray['driver_id'] = $trans->driver_id;
                    WalletTransaction::WalletCredit($paramArray);
                }else{
                    if(!empty($trans->booking_id)){
                        $booking = Booking::find($trans->booking_id);
                        $booking->payment_status = 1;
                        $booking->save();
                        $string_file = $this->getStringFile(NULL, $booking->Merchant);
                        $title = trans("$string_file.payment_success");
                        $message = trans("$string_file.payment_done");
                        $data['notification_type'] = 'PAYMENT_COMPLETE';
                        $data['segment_type'] = $booking->Segment->slag;
                        $data['segment_data'] = ['id'=>$booking->id,'handyman_order_id'=>NULL,'order_id'=> NULL];
                        $arr_param = ['data' => $data, 'message' => $message, 'merchant_id' => $booking->merchant_id, 'title' => $title, 'large_icon' => ""];
                        $user_param = $arr_param;
                        $user_param['user_id'] = $booking->user_id;
                        Onesignal::UserPushMessage($user_param);
                        $driver_param = $arr_param;
                        $driver_param['driver_id'] = $booking->driver_id;
                        Onesignal::DriverPushMessage($driver_param);
                    }
                    elseif(!empty($trans->order_id)){
                        $order = Order::find($trans->order_id);
                        $order->payment_status = 1;
                        $order->save();
                        $string_file = $this->getStringFile(NULL, $order->Merchant);
                        $title = trans("$string_file.payment_success");
                        $message = trans("$string_file.payment_done");
                        $data['notification_type'] = 'PAYMENT_COMPLETE';
                        $data['segment_type'] = $order->Segment->slag;
                        $data['segment_data'] = ['id'=>NULL,'handyman_order_id'=>NULL,'order_id'=> $order->id];
                        $arr_param = ['data' => $data, 'message' => $message, 'merchant_id' => $order->merchant_id, 'title' => $title, 'large_icon' => ""];
                        $user_param = $arr_param;
                        $user_param['user_id'] = $order->user_id;
                        Onesignal::UserPushMessage($user_param);
                        $driver_param = $arr_param;
                        $driver_param['driver_id'] = $order->driver_id;
                        Onesignal::DriverPushMessage($driver_param);
                        
                    }
                    else{
                        $handymanOrder = HandymanOrder::find($trans->handyman_order_id);
                        $handymanOrder->payment_status = 1;
                        $handymanOrder->save();
                         $string_file = $this->getStringFile(NULL, $handymanOrder->Merchant);
                        $title = trans("$string_file.payment_success");
                        $message = trans("$string_file.payment_done");
                        $data['notification_type'] = 'PAYMENT_COMPLETE';
                        $data['segment_type'] = $handymanOrder->Segment->slag;
                        $data['segment_data'] = ['id'=>NULL,'handyman_order_id'=>$handymanOrder->id,'order_id'=> NULL];
                        $arr_param = ['data' => $data, 'message' => $message, 'merchant_id' => $handymanOrder->merchant_id, 'title' => $title, 'large_icon' => ""];
                        $user_param = $arr_param;
                        $user_param['user_id'] = $handymanOrder->user_id;
                        Onesignal::UserPushMessage($user_param);
                        $driver_param = $arr_param;
                        $driver_param['driver_id'] = $handymanOrder->driver_id;
                        Onesignal::DriverPushMessage($driver_param);
                    }
                }
                
                
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text];
        }
        else
        {
            $request_status_text = "failed";
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text];
        }
        return success_response($request_status_text,$data);
    }

}