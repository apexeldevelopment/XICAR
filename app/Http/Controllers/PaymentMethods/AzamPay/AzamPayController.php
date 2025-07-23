<?php

namespace App\Http\Controllers\PaymentMethods\AzamPay;

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

class AzamPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getAzamPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'AZAMPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getUrl($env, $calling_from){
        if($env == 1){
            if ($calling_from == 'TOKEN'){
                $url = "https://authenticator.azampay.co.tz";
            }else{
                $url = "https://azampay.co.tz";
            }
        }else{
            if ($calling_from == 'TOKEN'){
                $url = "https://authenticator-sandbox.azampay.co.tz";
            }else{
                $url = "https://sandbox.azampay.co.tz";
            }
        }
        return $url;
    }

    public function generateOAuthToken($appName, $client_id, $client_secret, $env){
        $base_url = $this->getUrl($env, 'TOKEN');
        $post_data = [
            "appName" => $appName,
            "clientId" => $client_id,
            "clientSecret" => $client_secret
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $base_url.'/AppRegistration/GenerateToken',
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
        $response = json_decode($response, true);
        if (isset($response['data'])){
            return $response['data']['accessToken'];
        }
        return '';
    }

    public function MakeAzamPayPayment(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required',
            'currency' => 'required',
            'msisdn' => 'required',
            'provider' => 'required',
            'calling_for' => 'required',
            'booking_id' => 'required_if:calling_for,BOOKING'
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == 'USER' ? $request->user('api')->merchant_id : $request->user('api-driver')->merchant_id;
        $paymentOption = $this->getAzamPayConfig($merchant_id);
        $token = $this->generateOAuthToken($paymentOption->api_public_key, $paymentOption->api_secret_key, $paymentOption->auth_token, $paymentOption->gateway_condition);
        if (empty($token)){
            return $this->failedResponse("No OAuth Token Found!!");
        }

        $base_url = $this->getUrl($paymentOption->gateway_condition,'PAYMENT');
        $externalId = date('YmdHis');
        $data = [
            'accountNumber' => $request->msisdn,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'externalId' => $externalId,
            'provider' => $request->provider
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $base_url.'/azampay/mno/checkout',
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
                'Authorization: Bearer '.$token,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);
        if ($response['success'] == true && $response['messageCode'] == 0){
            $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
            DB::table('transactions')->insert([
                'status' => $calling_for,
                'reference_id' => $externalId,
                'card_id' => NULL,
                'merchant_id' => $merchant_id,
                'payment_option_id' => $paymentOption->payment_option_id,
                'checkout_id' => NULL,
                'amount' => $request->amount,
                'booking_id' => $request->booking_id,
                'order_id' => $request->order_id,
                'handyman_order_id' => $request->handyman_order_id,
                'payment_transaction_id' => $response['transactionId'],
                'payment_transaction' => NULL,
                'request_status' => 1,
                'user_id' => $request->type == "USER" ? $request->user('api')->id : NULL,
                'driver_id' => $request->type == "DRIVER" ? $request->user('api-driver')->id : NULL,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->successResponse($response['message'], $response);
        }else{
            return $this->failedResponse($response['message'], $response);
        }
    }

    public function AzamPayCallback(Request $request){
        \Log::channel('AzamPay')->emergency($request->all());
        $trans = DB::table('transactions')->where(['request_status' => 1,'payment_transaction_id' => $request->transid])->first();
        if (!empty($trans)){
            if ($request->transactionstatus == 'success'){
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $request->transid])
                    ->update(['request_status' => 2, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s')]);

                $receipt = "Application : " . $request->reference;
                $paramArray = array(
                    'booking_id' => NULL,
                    'amount' => $trans->amount,
                    'narration' => 2,
                    'platform' => 2,
                    'payment_method' => 2,
                    'receipt' => $receipt,
                    'transaction_id' => $request->reference,
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
                    ->where(['payment_transaction_id' => $request->transid])
                    ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }else{
            \Log::channel('AzamPay')->emergency(['message' => 'Transaction Not Found!']);
        }
    }

    public function AzamPayProviders(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == 'USER' ? $request->user('api')->merchant_id : $request->user('api-driver')->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $providers = ['Airtel','Tigo','Halopesa','Azampesa','Mpesa'];
        return $this->successResponse(trans("$string_file.success"), $providers);
    }
}
