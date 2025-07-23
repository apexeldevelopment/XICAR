<?php

namespace App\Http\Controllers\PaymentMethods\UniPay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentOption;
use DB;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Models\Onesignal;

class UniPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function createQrPaymentUrl($request, $paymentConfig, $calling_from){
        try{

            $appkey = $paymentConfig->api_public_key;
            $payment_option = PaymentOption::where("slug","UNI5PAY")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }
            $status = 3;
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $id = $user->id;
                $currencyCode = $user->Country->isoCode;
                $merchant_id = $user->merchant_id;
                $status = 2;
            }
            elseif($calling_from == "USER"){
                $user = $request->user('api');
                $id = $user->id;
                $currencyCode = $user->Country->isoCode;
                $merchant_id = $user->merchant_id;
                $status = 1;
            }

            $countryCode = $this->getNumericCountryCode();
            // $countryCode = config('app.countries.' . $isoCode);
            $currency = ($currencyCode == "INR") ? "356":"968";
            $mchtOrderNo = date('His');
            $data = [
                'mchtOrderNo'=> $mchtOrderNo,
                'terminalId'=> 'WEB',
                'amount'=> $request->amount,
                'currency'=> $countryCode[$currencyCode],
                // 'payment_desc'=> $user->Merchant->BusinessName.' Payment',
                'url_success'=> route('unipay-success'),
                'url_failure'=> route('unipay-failure'),
                'url_notify'=> route('unipay-notify',['transactionId'=>$mchtOrderNo])
            ];
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://payment.uni5pay.sr/v1/qrcode_get',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'apiKey: '.$appkey,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $res = json_decode($response, true);
            if($res['rspMsg'] == "SUCCESS"){
                $deepLink = $res['deepLink'];
                $qrCode = $res['qrCode'];
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $user->merchant_id,
                    'payment_transaction_id'=> $mchtOrderNo,
                    'amount' => $request->amount,
                    'payment_option_id' => $payment_option->id,
                    'request_status'=> 1,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'status_message'=> 'PENDING',
                    'status'=> $status,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                // $qrCode = $this->getQrCode(json_encode($data),$appkey);

                // if(!empty($qrCode) && $qrCode['rspMsg'] == "SUCCESS"){
                //     $deepLink = $qrCode['deepLink'];
                // }
                // else{
                //     throw new \Exception($res['rspMsg']);
                // }

            }
            else{
                throw new \Exception($res['rspMsg']);
            }

        }catch (\Exception $exception){
            throw  new \Exception($exception->getMessage());
        }

        DB::commit();

        return [
            // 'status' => 'NEED_TO_OPEN_WEBVIEW',
            // 'url' => $res['paymentLink'] ?? '',
            'qr_code'=> $qrCode,
            'deepLink'=> $deepLink,
            'transaction_id' => $mchtOrderNo ?? '',
            'success_url' => route('unipay-success'),
            'fail_url' => route('unipay-failure'),
        ];
    }

    public function getQrCode($data,$appkey){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://payment.uni5pay.sr/v1/qrcode_get',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'apiKey: '.$appkey,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $res = json_decode($response, true);
        return $res;
    }

    public function getNumericCountryCode(){

        $data = [
            "SRD"=> "968",
            "INR"=> "356"
        ];

        return $data;
    }

    public function UniPaySuccess(Request $request){
        \Log::channel('Uni5Pay')->emergency($request->all());
        return $response = "Success!";
    }

    public function UniPayFailure(Request $request){
        \Log::channel('Uni5Pay')->emergency($request->all());
        return $response = "Fail!";
    }

    public function UniPayNotify(Request $request){
        \Log::channel('Uni5Pay')->emergency($request->all());
        $data = $request->all();
        if(!empty($data) && $data['status'] == 'PAID'){
            $trans = DB::table('transactions')->where(['payment_transaction_id' => $data['transactionId'],'request_status'=> 1])->first();
            if(!empty($trans)){
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
                if($trans->status == 1 && empty($trans->booking_id)){
                    $paramArray['user_id'] = $trans->user_id;
                    WalletTransaction::UserWalletCredit($paramArray);
                }elseif($trans->status == 2 && empty($trans->booking_id)){
                    $paramArray['driver_id'] = $trans->driver_id;
                    WalletTransaction::WalletCredit($paramArray);
                }else{
                    if(!empty($trans->booking_id)){
                        $booking = \App\Models\Booking::find($trans->booking_id);
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
                        $order = \App\Models\Order::find($trans->order_id);
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
                        $handymanOrder = \App\Models\HandymanOrder::find($trans->handyman_order_id);
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
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $data['transactionId']])
                    ->update(['request_status' => 2, 'payment_transaction' => json_encode($request->all()),'status_message'=>'SUCCESS']);
            }
            else{
                $trans = DB::table('transactions')->where(['payment_transaction_id' => $data['transactionId'],'request_status'=> 1])->first();
                if(!empty($trans)){

                    DB::table('transactions')
                        ->where(['payment_transaction_id' => $data['transactionId']])
                        ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all()),'status_message'=>'FAILED']);
                }
            }
        }
        else{
            $trans = DB::table('transactions')->where(['payment_transaction_id' => $data['transactionId'],'request_status'=> 1])->first();
            if(!empty($trans)){

                DB::table('transactions')
                    ->where(['payment_transaction_id' => $data['transactionId']])
                    ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all()),'status_message'=>'FAILED']);
            }
        }
    }

    public function paymentStatus($request){
        $tx_reference = $request->transaction_id;
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $tx_reference)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        $data = [];
        if($transaction_table->request_status == 1)
        {
            $request_status_text = "processing";
            $transaction_status = 1;
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }
        else if($transaction_table->request_status == 2)
        {
            $request_status_text = "success";
            $transaction_status = 2;
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }
        else
        {
            $request_status_text = "failed";
            $transaction_status = 3;
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];

        }
        return $data;
    }
}
