<?php

namespace App\Http\Controllers\PaymentMethods\OrangeMoneyWeb;

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

class OrangeMoneyWebController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getOrangeMoneyConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'ORANGEMONEY_WEB')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getAuthToken($authToken, $grantType){
        try{
            $url = 'https://api.orange.com/oauth/v3/token';

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type='.$grantType,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '. $authToken,
                'Content-Type: application/x-www-form-urlencoded'
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $res = json_decode($response,true);
            if(isset($res['access_token'])){
                return $res['access_token'];
            }
            else{
                return '';
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    //on production
    public function createPaymentUrl($request, $payment_option_config, $calling_from)
    {
        try{
            $authToken = $payment_option_config->auth_token;
            $merchantKey = $payment_option_config->api_public_key;
            $countryCode = $payment_option_config->operator;
            $grantType = 'client_credentials';

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

            $gatewayUrl = $payment_option_config->gateway_condition == 1 ? 'https://api.orange.com/orange-money-webpay/'.$countryCode.'/v1/webpayment' : 'https://api.orange.com/orange-money-webpay/dev/v1/webpayment';
            
            
            $token = $this->getAuthToken($authToken, $grantType);
            
            if(empty($token)){
                throw new \Exception('Token not generated');
            }
            $payment_option = $this->getOrangeMoneyConfig($merchant_id);
            $order_id = 'MY_ORDER_ID_'.$id.'_'.time();
            $reference = 'REFERENCE_MERCHANT_'.time();
            $amount = $request->amount;
            $data = [
                'merchant_key'=> $merchantKey,
                'currency'=>$payment_option_config->gateway_condition == 1 ? $currency : "OUV",
                'amount'=>$request->amount,
                'order_id'=> $order_id,
                'return_url'=> route('orangemoneyweb-redirect',[$reference]),
                'cancel_url'=> route('orangemoneyweb-cancel'),
                'notif_url'=> route('orangemoneyweb-notify'),
                "lang"=> $lang,
                'reference'=> $reference
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
                    'Authorization: Bearer '.$token,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ),
            ));
            
            
            $response = json_decode(curl_exec($curl));
            // \Log::channel('orangemoneycore_api')->emergency($response);
            curl_close($curl);
            if(isset($response->status) && $response->status == 201) {
                $transaction_id = $response->notif_token;
                DB::table('transactions')->insert([
                'user_id' => $calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'merchant_id' => $merchant_id,
                'payment_transaction_id'=> $transaction_id,
                'amount' => $request->amount,
                'payment_option_id' => $payment_option->payment_option_id,
                'request_status'=> 1,
                'reference_id'=> $reference,
                'status_message'=> 'PENDING',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            }else{
                throw new \Exception('Payment Url not generated');
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }

        DB::commit();

        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $response->payment_url ?? '',
            'transaction_id' => $response->notif_token ?? '',
            'success_url' => route('orangemoneyweb-success'),
            'fail_url' => route('orangemoneyweb-cancel'),
        ];
    }

    public function Redirect(Request $request)
    {
        \Log::channel('Orangemoney_redirect')->emergency($request->all());
        // echo "<h1>Success</h1>";
        if($request->refrenceId){
            $transaction = DB::table('transactions')->where('reference_id',$request->refrenceId)->first();
            if($transaction->request_status == 2 && $transaction->status_message == 'SUCCESS'){
                return redirect(route('orangemoneyweb-success'));
            }
            else{
                return redirect(route('orangemoneyweb-cancel'));
            }
        }else{
            throw new \Exception('Reference not found');
        }
        
    }

    public function Success(Request $request)
    {
        \Log::channel('Orangemoney')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Cancel(Request $request)
    {
        \Log::channel('Orangemoney')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }

    public function Notify(Request $request)
    {
        \Log::channel('Orangemoney')->emergency($request->all());
        $status = $request['status'];
        $transaction_id = $request['notif_token'];
        DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update([
            'request_status' => $status == 'SUCCESS' ? 2 : ($status == 'FAILED' ? 3 : 4),
            'status_message' => $status == 'SUCCESS' ? 'SUCCESS' : "FAIL"
        ]);
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

    // public function OrangeMoneyURL(Request $request)
    // {
    //     $validator = Validator::make($request->all(),[
    //         'for' => 'required|integer|between:1,2',
    //         'amount' => 'required',
    //         'booking_id' => 'nullable|exists:bookings,id'
    //     ]);
    //     if ($validator->fails()){
    //         $errors = $validator->messages()->all();
    //         return $this->failedResponse($errors[0]);
    //     }

    //     if($request->for == 1){
    //         $user = $request->user('api');
    //         $user_id = $user->id;
    //         $driver_id = NULL;
    //         $merchant_id = $user->Merchant->id;
    //     } else {
    //         $user = $request->user('api-driver');
    //         $driver_id = $user->id;
    //         $user_id = NULL;
    //         $merchant_id = $user->Merchant->id;
    //     }

    //     $paymentOption = $this->getOrangeMoneyConfig($merchant_id);
    //     $identifier = $paymentOption->api_secret_key;
    //     $site_id = $paymentOption->api_public_key;
    //     $client_secret = $paymentOption->auth_token;
    //     $string_file = $this->getStringFile($merchant_id);

    //     if(is_null($identifier) || is_null($site_id) || is_null($client_secret)){
    //         return $this->failedResponse(trans("$string_file.configuration_not_found"));
    //     }
    //     $amount = sprintf("%0.2f", $request->amount);
    //     $order_id = time();

    //     DB::table('orange_money_transactions')->insert([
    //         'merchant_id' => $merchant_id,
    //         'user_id' => $user_id,
    //         'driver_id' => $driver_id,
    //         'booking_id' => $request->booking_id,
    //         'order_id' => $order_id,
    //         'amount' => $amount,
    //         'created_at' => Carbon::now(),
    //     ]);

    //     $identifier = md5($identifier);
    //     $site_id = md5($site_id);
    //     $date = date('c');
    //     $screen_message = "Test Payment by Orange Money";
    //     $algo = "sha512";
    //     $binary_secret = pack("H*", $client_secret);
    //     $message = "S2M_COMMANDE=$screen_message"."&S2M_DATEH=$date"."&S2M_HTYPE=$algo"."&S2M_IDENTIFIANT=$identifier"."&S2M_REF_COMMANDE=$order_id"."&S2M_SITE=$site_id"."&S2M_TOTAL=$amount";
    //     $hmac = strtoupper(Hash_hmac(Strtolower($algo), $message, $binary_secret));
    //     $url = route('api.orange_money_url')."?identifier=".$identifier."&site_id=".$site_id."&amount=".$amount."&order_id=".$order_id."&message=".$screen_message."&date=".encrypt($date)."&algo=".$algo."&hmac=".$hmac;
    //     return $this->successResponse(trans("$string_file.payment_success"), ['payment_url' => $url]);
    // }

    // public function OrangeMoney(Request $request)
    // {
    //     $identifier = $request->query('identifier');
    //     $site_id = $request->query('site_id');
    //     $amount = $request->query('amount');
    //     $order_id = $request->query('order_id');
    //     $message = $request->query('message');
    //     $date = decrypt($request->query('date'));
    //     $algo = $request->query('algo');
    //     $hmac = $request->query('hmac');
    //     return view('payment/orange/index', compact('identifier', 'site_id', 'amount', 'order_id', 'message', 'date', 'algo', 'hmac'));
    // }

    // public function OrangeMoneyNotify(Request $request)
    // {
    //     $data = $request->all();
    //     switch ($request->STATUT) {
    //         case '117':
    //             $status = 'Failed';
    //             break;
    //         case '200':
    //             $status = 'Successful';
    //             break;
    //         case '220':
    //             $status = 'Transaction Not Found';
    //             break;
    //         case '375':
    //             $status = 'OTP is expired or already used or invalid';
    //             break;
    //     }
    //     DB::table('orange_money_transactions')->where(['order_id' => $request->REF_CMD])
    //         ->update([
    //             'ref_id' => $request->UID,
    //             'status' => $status,
    //             'updated_at' => Carbon::now(),
    //         ]);
    //     $transaction = DB::table('orange_money_transactions')->where(['order_id' => $request->order_id])->limit(1);
    //     $user = $transaction->first()->user_id != NULL ? User::find($transaction->first()->user_id) : Driver::find($transaction->first()->driver_id);
    //     $paymentOption = $this->getOrangeMoneyConfig($user->merchant_id);
    //     $client_secret = $paymentOption->auth_token;
    //     $bin_key = pack("H*", $client_secret);
    //     ksort($data);
    //     $message = urldecode(http_build_query($data));
    //     $hmac = strtoupper(hash_hmac(strtolower($data['ALGO']), $message, $bin_key));

    //     if ($hmac === $_POST['HMAC'] && $request->STATUT == '200'){
    //         if(isset($transaction->first()->booking_id)){
    //             $this->OnlinePaymentBookingStatus($transaction->first()->booking_id, 1);
    //         }
    //     }
    // }

    // public function OrangeMoneySuccess(Request $request)
    // {
    //     DB::table('orange_money_transactions')->where(['order_id' => $request->ref_commande])
    //         ->update([
    //             'status' => $request->message,
    //             'updated_at' => Carbon::now(),
    //         ]);
    //     $response = trans('api.transaction_completed_successfully');
    //     return view('payment/orange/callback', compact('response'));
    // }

    // public function OrangeMoneyFail(Request $request)
    // {
    //     DB::table('orange_money_transactions')->where(['order_id' => $request->ref_commande])
    //         ->update([
    //             'status' => $request->message,
    //             'updated_at' => Carbon::now(),
    //         ]);
    //     $response = trans('api.transaction_failed');
    //     return view('payment/orange/callback', compact('response'));
    // }

    // public function OrangeRedirect(Request $request){
    //     dd($request->all());
    // }
}
