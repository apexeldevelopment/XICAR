<?php

namespace App\Http\Controllers\PaymentMethods\Mpesa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MpesaController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getMpesaConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'MPESA')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getUrl($env){
        return $env == 1 ? "https://api.safaricom.co.ke" : "https://sandbox.safaricom.co.ke";
    }

    public function MpesaRequestId()
    {
        return sprintf('%03x-%05x-%05x%05x%05x%05x%05x',
            mt_rand(0, 0xfff), mt_rand(0, 0xfffff),
            mt_rand(0, 0x0ffff) | 0x4000,
            mt_rand(0, 0x3ffff) | 0x8000,
            mt_rand(0, 0xfffff), mt_rand(0, 0xfffff), mt_rand(0, 0xfffff)
        );
    }

    public function generateAccessToken($url, $consumer_key, $consumer_secret){
        $credentials = base64_encode($consumer_key.':'.$consumer_secret);
        $header = array('Authorization: Basic '.$credentials, 'Content-Type: application/json');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url.'/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $response = json_decode($response);
        if ($info["http_code"] == 200) {
            $access_token = $response->access_token;
            $this->access_token = $access_token;
            return $access_token;
        } else {
            throw new \Exception("Invalid Consumer key or secret");
        }
    }

    private function SubmitMpesaRequest($url, $data, $access_token){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Authorization: Bearer '.$access_token));

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function mpesaExpress(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required',
            'phone' => 'required',
            'calling_for' => 'required',
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try{
//            $user_id = $request->type == 'USER' ? $request->user('api')->id : $request->user('api-driver')->id;
            $merchant_id = $request->type == 'USER' ? $request->user('api')->merchant_id : $request->user('api-driver')->merchant_id;
            $paymentOption = $this->getMpesaConfig($merchant_id);
            $driverPhone = '';
            $driverName = '';
            if($request->type == 'USER'){

            }else{
                $driverPhone = $request->user('api-driver')->phoneNumber;
                $driverName = $request->user('api-driver')->first_name .' ' . $request->user('')->last_name;
            }
            //check subscription activate
            if($request->calling_from == 'RENEWABLE_SUBSCRIPTION' && $request->type == 'DRIVER' && $request->user('api-driver')->hasActiveRenewableSubscriptionRecord()){
                $message = "Subscription Already Active";
                return $this->failedResponse($message);
            }
            $short_code = $paymentOption->operator;
            $pass_key = $paymentOption->auth_token;
            $transaction_type = $paymentOption->tokenization_url; //Ex.CustomerPayBillOnline
            $base_url = $this->getUrl($paymentOption->gateway_condition);
            $token = $this->generateAccessToken($base_url,$paymentOption->api_public_key, $paymentOption->api_secret_key);
            $phone = str_replace('+','',$request->phone);
            $timestamp = date('YmdHis');
            $password = base64_encode($short_code.$pass_key.$timestamp);
            $ref_id = date('YmdHis');
            $tillNumber = !empty($paymentOption->description) && ctype_digit($paymentOption->description) ? $paymentOption->description : $short_code;

            $data = array(
                'BusinessShortCode' => $short_code,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $transaction_type,
                'Amount' => $request->amount,
                'PartyA' => $phone,
                'PartyB' => !empty($tillNumber) ? $tillNumber : $short_code,
                'PhoneNumber' => $phone,
                'CallBackURL' => route('mpesa.callback',$ref_id),
                'AccountReference' => $phone,
                'TransactionDesc' => isset($request->pay_mode) && $request->pay_mode == 2 ? $paymentOption->Merchant->BusinessName. ' Subscription for'. $driverName . $driverPhone : $paymentOption->Merchant->BusinessName.' Payment',
            );
            $data = json_encode($data);
            $url = $base_url.'/mpesa/stkpush/v1/processrequest';
            $response = $this->SubmitMpesaRequest($url, $data, $token);
            $response = json_decode($response,true);
            // dd($token,$data,$url,$response,$pass_key,$short_code);
            if (isset($response) && isset($response['ResponseCode']) && $response['ResponseCode'] == "0") {
                $calling_for = $request->calling_for == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
                DB::table('transactions')->insert([
                    'status' => $calling_for,
                    'reference_id' => "",
                    'card_id' => NULL,
                    'merchant_id' => $merchant_id,
                    'payment_option_id' => $paymentOption->payment_option_id,
                    'checkout_id' => NULL,
                    'amount' => $request->amount,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'payment_transaction_id' => $response['CheckoutRequestID'],
                    'payment_transaction' => NULL,
                    'request_status' => 1,
                    'user_id' => $request->type == "USER" ? $request->user('api')->id : NULL,
                    'driver_id' => $request->type == "DRIVER" ? $request->user('api-driver')->id : NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                DB::commit();
                \Log::channel('mpessa_api')->emergency(['response'=> $response,'request'=>$data,'url'=>$url]);
                return $this->successResponse($response['CustomerMessage'], ['transaction_id' => $response['CheckoutRequestID'], 'status' => $response['ResponseCode']]);
            } else {
                return $this->failedResponse($response['errorMessage']);
            }
        }catch (\Exception $e){
            DB::rollBack();
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
    }

    public function mpesaTransactionStatus(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'transaction_id' => 'required',
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try{
            $merchant_id = $request->type == 'USER' ? $request->user('api')->merchant_id : $request->user('api-driver')->merchant_id;
            $string_file = $this->getStringFile($merchant_id);
            $transaction = DB::table('transactions')->where(['payment_transaction_id' => $request->transaction_id])->first();
            if (!empty($transaction)){
                if ($transaction->request_status == 1){
                    $paymentOption = $this->getMpesaConfig($merchant_id);
                    $short_code = $paymentOption->operator;
                    $pass_key = $paymentOption->auth_token;
                    $timestamp = date('YmdHis');
                    $password = base64_encode($short_code.$pass_key.$timestamp);

                    $base_url = $this->getUrl($paymentOption->gateway_condition);
                    $token = $this->generateAccessToken($base_url,$paymentOption->api_public_key, $paymentOption->api_secret_key);
                    $data = array(
                        'BusinessShortCode' => $short_code,
                        'Password' => $password,
                        'Timestamp' => $timestamp,
                        'CheckoutRequestID' => $transaction->payment_transaction_id
                    );
                     $data = json_encode($data);
                    $url = $base_url.'/mpesa/stkpushquery/v1/query';
                    if($paymentOption->additional_data == 'v2'){
                        $url = $base_url.'/mpesa/stkpushquery/v2/query';
                    }
                    $response = $this->SubmitMpesaRequest($url, $data, $token);
                    \Log::channel('mpessa_api')->emergency(['response'=> $response,'request'=>$data,'url'=>$url]);
                    $response = json_decode($response,true);
                    if (isset($response['ResultCode']) && $response['ResultCode'] == "0"){
                        DB::table('transactions')
                            ->where(['payment_transaction_id' => $response['CheckoutRequestID']])
                            ->update(['request_status' => 2,  'reference_id'=>!empty($response['MpesaReceiptNumber']) ? $response['MpesaReceiptNumber'] : "" ,'payment_transaction' => json_encode($response), 'updated_at' => date('Y-m-d H:i:s')]);


//                        $receipt = "Application : " . $transaction->payment_transaction_id;
//                        $paramArray = array(
//                            'booking_id' => NULL,
//                            'amount' => $transaction->amount,
//                            'narration' => 2,
//                            'platform' => 2,
//                            'payment_method' => 2,
//                            'receipt' => $receipt,
//                            'transaction_id' => $transaction->payment_transaction_id,
//                            'notification_type' => 89,
//                            'payment_option_id' => $paymentOption->payment_option_id,
//                        );
//                        if ($transaction->status == 1) {
//                            $paramArray['user_id'] = $transaction->user_id;
//                            WalletTransaction::UserWalletCredit($paramArray);
//                        } elseif($transaction->status == 2) {
//                            $paramArray['driver_id'] = $transaction->driver_id;
//                            WalletTransaction::WalletCredit($paramArray);
//                        }
                        DB::commit();
                        return $this->successResponse($response['ResultDesc'], $response);
                    }else{
                        if (isset($response['errorCode']) && $response['errorCode'] == "500.001.1001"){
                            $message = $response['errorMessage'];
                            return $this->pendingResponse($message);
                        }elseif(isset($response['ResponseCode']) && $response['ResponseCode'] == "4002"){
                            $message = $response['ResponseDescription'];
                            return $this->pendingResponse($message);
                        }
                        elseif(isset($response['ResultCode']) && $response['ResultCode'] == '1037'){
                            $added_created_at = Carbon::parse($transaction->created_at)->addMinutes(5);
                            if(isset($added_created_at) && $added_created_at->lessThan(Carbon::now())){
                                $message = isset($response['ResultDesc']) && !empty($response['ResultDesc']) ? $response['ResultDesc'] : "Not getting Any Description";
                                return $this->failedResponse($message);
                            }else{
                                $message = $response['ResponseDescription'];
                                return $this->pendingResponse($message);
                            }
                        }
                        else{
                            $message = isset($response['ResultDesc']) && !empty($response['ResultDesc']) ? $response['ResultDesc'] : "Not getting Any Description";
                            DB::table('transactions')->where(['payment_transaction_id' => $response['CheckoutRequestID']])
                                ->update(['request_status' => 3, 'reference_id'=>isset($response['MpesaReceiptNumber']) ? $response['MpesaReceiptNumber'] : '','payment_transaction' => json_encode($response), 'updated_at' => date('Y-m-d H:i:s')]);
                            DB::commit();
                            return $this->failedResponse($message);
                        }
                    }
                }else{
                    if($transaction->request_status == 2 && $transaction->payment_transaction){
                        $response = isset($transaction->payment_transaction) ? json_decode($transaction->payment_transaction,true) : '';
                        return $this->successResponse($response['ResultDesc'], $response);
                    }elseif($transaction->request_status == 3 && $transaction->payment_transaction){
                        $response = isset($transaction->payment_transaction) ? json_decode($transaction->payment_transaction,true) : '';
                        return $this->failedResponse($response['ResultDesc']);
                    }
                    return $this->failedResponse(trans("$string_file.transaction_status_changed"));
                }
            }else{
                if($transaction->request_status == 2 && $transaction->payment_transaction){
                        $response = isset($transaction->payment_transaction) ? json_decode($transaction->payment_transaction,true) : '';
                        return $this->successResponse($response['ResultDesc'], $response);
                    }elseif($transaction->request_status == 3 && $transaction->payment_transaction){
                        $response = isset($transaction->payment_transaction) ? json_decode($transaction->payment_transaction,true) : '';
                        return $this->failedResponse($response['ResultDesc']);
                    }else{
                        $response = isset($transaction->payment_transaction) ? json_decode($transaction->payment_transaction,true) : '';
                        $message = !empty($response) && $response['ResponseDescription'] ? $response['ResponseDescription'] : '';
                        return $this->pendingResponse($message);
                    }
                // return $this->failedResponse(trans("$string_file.data_not_found"));
            }
        }catch (\Exception $e){
            DB::rollBack();
            $message = $e->getMessage();
            return $this->failedResponse($message);
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
    
    //Mpesa B2C
    public function MpesaURLandFile($gateway_env,$type){
        if ($type == 1){
            if ($gateway_env == 1){
                $file_name = 'MPesaProductionCertificate.cer';
            }else{
                $file_name = 'MPesaSandboxCertificate.cer';
            }
            return $file_name;
        }else{
            if ($gateway_env == 1){
                $url = 'https://api.safaricom.co.ke/';
            }else{
                $url = 'https://sandbox.safaricom.co.ke/';
            }
            return $url;
        }
    }

    public function generateB2CSecurityCredentials($password,$gateway_env){
        $file_name = $this->MpesaURLandFile($gateway_env,1);
        $crt_file_path = 'certificates/'.$file_name;
        $fp = fopen($crt_file_path,"r");
        $publicKey = fread($fp,8192);
        fclose($fp);
        openssl_get_publickey($publicKey);
        openssl_public_encrypt($password, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);
    }

    public function submitB2CRequest(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'phone' => 'required',
            'amount' => 'required',
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        if($request->amount < 10){
            return $this->failedResponse('The amount must be at least 10.');
        }
        $calling_for = 3;
        if ($request->type == 1) {
            $user = $request->user('api');
            $wallet_amount = $user->wallet_balance;
            $currency_code = $user->CountryArea->Country->phonecode;
            $calling_for = 1;
            
        } elseif ($request->type == 2) {
            $user = $request->user('api-driver');
            $wallet_amount = $user->wallet_money;
            $currency_code = $user->CountryArea->Country->phonecode;
            $calling_for = 2;
        } else {
            return $this->failedResponse("Invalid Type");
        }
        if($wallet_amount < $request->amount){
            return $this->failedResponse('Your Wallet is not having '.$request->amount.' amount');
        }

        try{
            $string_file = $this->getStringFile($user->merchant_id);
            $payment_option = PaymentOption::where('slug', 'MPESA_B2C')->first();
            $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
            if (empty($paymentConfig)) {
                return $this->failedResponse(trans("$string_file.configuration_not_found"));
            }
            $short_code = $paymentConfig->operator; 
            $initiatorName = $paymentConfig->auth_token;
            $password=$paymentConfig->additional_data;
            $consumerSecretKey = $paymentConfig->api_secret_key;
            $base_url = $this->getUrl($paymentConfig->gateway_condition);
            $auth_token = $this->generateAccessToken($base_url,$paymentConfig->api_public_key, $paymentConfig->api_secret_key);
            // $token = $this->getB2CAuthToken($paymentConfig->api_public_key,$paymentConfig->api_secret_key,$paymentConfig->gateway_condition);
            $security_credentials = $this->generateB2CSecurityCredentials($password,$paymentConfig->gateway_condition);
            $url = $this->MpesaURLandFile($paymentConfig->gateway_condition,2);
            $callback_url = route('mpesa.b2c.callback');
            $phone = str_replace('+', '', $currency_code.''.$request->phone);
            $passing_data = [
                'InitiatorName' => $initiatorName,
                'SecurityCredential' => $security_credentials,
                'CommandID' => 'BusinessPayment',
                'Amount' => $request->amount,
                'PartyA' => $short_code,
                'PartyB' => $phone,
                'Remarks' => $user->Merchant->BusinessName.' Payment',
                'QueueTimeOutURL' => $callback_url,
                'ResultURL' => $callback_url,
                'Occasion' => $user->Merchant->BusinessName.' Payment',
            ];
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url.'mpesa/b2c/v1/paymentrequest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($passing_data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$auth_token,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $response = json_decode($response,true);
            if ($httpcode == 200 && $response['ResponseCode'] == 0 && !empty($response['ConversationID']) && !empty($response['OriginatorConversationID'])) {
                DB::table('mpessa_transactions')->insert([
                    'merchant_id' => $user->merchant_id,
                    'user_id' => $user->id,
                    'amount' => $request->amount,
                    'type'=> $request->type,
                    'checkout_request_id' => $response['OriginatorConversationID'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                return array('result' => "1", 
                    'message' => $response['ResponseDescription'],
                    'transaction_id'=>$response['OriginatorConversationID'],
                    'success_url' => route('mpesa.Success'),
                    'fail_url' => route('mpesa.Fail')
                );
            } else {
                return array('result' => "0", 'message' => $response['errorMessage']);
            }
        }catch (\Exception $e) {
            $response = json_decode($e->getMessage());
            $message = $response->errorMessage ?? $e->getMessage();
            return response()->json(['result' => "0", 'message' => $message, 'data' => '']);
        }
    }

    public function MpesaB2CRequestCallback(Request $request){
        $data = $request->all();
        \Log::channel('MpesaB2C_callback')->emergency($data);
        $result_data = $data['Result'];
        if ($result_data['ResultCode'] == 0) {
            $trans_id = $result_data['OriginatorConversationID'];
            $trans = DB::table('mpessa_transactions')->where(['payment_status' => NULL,'checkout_request_id' => $trans_id])->first();
            if (!empty($trans)) {
                DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => $trans_id])
                    ->update(['payment_status' => 'success', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($result_data)]);
                $message = "Wallet Message";
                $amount = $trans->amount;
                $type = $trans->type;
                $data = ['result' => '1', 'amount' => $amount, 'message' => $message];
                $merchant_id = $trans->merchant_id;
                if ($type == 1) {
                    $receipt = "Application : " . $trans_id;
                    $paramArray = array(
                        'user_id' => $trans->user_id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 17,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $trans_id,
                        'notification_type' => 89
                    );
                    WalletTransaction::UserWalletDebit($paramArray);
                } else {
                    $receipt = "Application : " . $trans_id;
                    $paramArray = array(
                        'driver_id' => $trans->user_id,
                        'booking_id' => NULL,
                        'amount' => $amount,
                        'narration' => 24,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $trans_id,
                        'notification_type' => 89
                    );
                    WalletTransaction::WalletDeduct($paramArray);
                }
            }

            // $data = array(
            //     "amount" => $amount,
            //     "transcode" => $trans_id,
            //     "user_id" => $trans->user_id,
            //     "status" => "COMPLETE",
            // );
            // \Log::channel('MpesaB2C_callback')->emergency($data);
            // return redirect(route('mpesa.Success'));
        } else {
            $trans_id = $result_data['OriginatorConversationID'];
            $trans = DB::table('mpessa_transactions')->where(['payment_status' => NULL,'checkout_request_id' => $trans_id])->first();
            if (!empty($trans)) {
                DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => $trans_id])
                    ->update(['payment_status' => 'success', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => json_encode($result_data)]);
                $message = $result_data['ResultDesc'];
                $amount = $trans->amount;
                $type = $trans->type;
                $data = ['result' => '0', 'amount' => $amount, 'message' => $message];
                $merchant_id = $trans->merchant_id;
                if ($type == 1) {
                    Onesignal::UserPushMessage($trans->user_id, $data, $message, 89, $merchant_id);
                } else {
                    Onesignal::DriverPushMessage($trans->user_id, $data, $message, 89, $merchant_id);
                }
            }

            // $data = array(
            //     "amount" => $amount,
            //     "transcode" => $trans_id,
            //     "user_id" => $trans->user_id,
            //     "status" => "FAILED",
            // );
            // \Log::channel('MpesaB2C_callback')->emergency($data);
            // return redirect(route('mpesa.Fail'));
        }
    }
    
    public function MpesaB2CSuccess(Request $request){
        echo "<h1>Success</h1>";
    }
    
    public function MpesaB2CFail(Request $request){
        echo "<h1>Failed</h1>";
    }
}
