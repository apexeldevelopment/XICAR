<?php

namespace App\Http\Controllers\PaymentMethods\TelebirrPay;

use App\Http\Controllers\Controller;
use App\Models\DriverCard;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\UserCard;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use phpseclib3\Crypt\RSA;

class TelebirrPayNewController extends Controller
{
    use ApiResponseTrait;
    public function getTelebirrPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'TELEBIRRPAY_NEW')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        if (empty($paymentOption)) {
            return response()->json(['result' => 0, 'message' => trans('api.message194')]);
        }
        return $paymentOption;
    }
    
    public function createPaymentUrl($request, $paymentConfig, $calling_from){
        try{
            $user = $calling_from == "USER" ? $request->user('api') : $request->user('api-driver');
            $type = $calling_from == "USER" ? 1 : 2;
            $title = 'createOrder';
            $amount = $request->amount;
            $configUrl = $paymentConfig->payment_redirect_url;
            $appSecretkey = $paymentConfig->api_secret_key;
            $fabricAppId = $paymentConfig->auth_token;
            $merchantAppId = $paymentConfig->api_public_key;
            $shortCode = $paymentConfig->tokenization_url;
            // $webBaseUrl = "https://196.188.120.3:38443/payment/web/paygate?";
            $webBaseUrl = "https://developerportal.ethiotelebirr.et:38443/payment/web/paygate?";
            
            $fabricToken = $this->getFabricToken($appSecretkey,$fabricAppId,$configUrl);
            
            $result = json_decode($fabricToken);
            $authToken = $result->token;
            
            $createOrderResult = $this->requestCreateOrder($request,$authToken, $title, $amount,$configUrl,$fabricAppId,$merchantAppId,$shortCode,$user,$type);
            if(isset($createOrderResult->original) && isset(($createOrderResult->original)['result']) && ($createOrderResult->original)['result'] == "0"){
                return ['message_api_response'=> ($createOrderResult->original)['message']];
            }
            // $res = json_decode($createOrderResult,true);
            // if($res['result'] && $res['result'] == 'SUCCESS'){
            //     return ['transaction_id'=> $res['biz_content']['merch_order_id']];
            // }else{
            //     return ['transaction_id'=> ""];
            // }
            $res = json_decode($createOrderResult,true);
            $prepayId = json_decode($createOrderResult)->biz_content->prepay_id;
            
            $rawRequest = $this->createRawRequest($prepayId,$merchantAppId,$shortCode);
            $assembledUrl = $webBaseUrl . $rawRequest . "&version=1.0&trade_type=Checkout";
            // dd($assembledUrl);
            return ['url'=>$assembledUrl];
                
        }catch (\Exception $exception){
            throw  new \Exception($exception->getMessage());
        }
    }
    
    public function getFabricToken($appSecretkey,$fabricAppId,$configUrl){
        $payload =  array(
            "appSecret" => $appSecretkey
        );
          
        $headers = array(
            "Content-Type: application/json",
            "X-APP-Key: " . $fabricAppId
        );        
          
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $configUrl . "/payment/v1/token",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => $headers,
            ));
                
            $response = curl_exec($curl);
            $err = curl_error($curl);
            if($err){
                return response()->json(['result' => '0', 'message' => $err, 'data' => []]);
            }
            return  $response;                          
    }
    
        public function requestCreateOrder($request,$fabricToken, $title, $amount,$configUrl,$fabricAppId,$merchantAppId,$shortCode,$user,$type)
                {
                    $merchant_id = $user->merchant_id;
                    $merchant_order_id = (string) date('Ymd').time();
                    $paymentConfig = $this->getTelebirrPayConfig($merchant_id);
                    
                    // Header parameters
                    $headers = array(
                        "Content-Type: application/json",
                        "X-APP-Key: " . $fabricAppId,
                        "Authorization: " . $fabricToken
                    );
                    
                    // Body parameters
                    $payload = $this->createRequestObject($title, $amount,$merchantAppId,$shortCode,$user,$merchant_order_id);
                    // dd(json_decode($payload));
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $configUrl . "/payment/v1/merchant/preOrder",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_POSTFIELDS => $payload,
                        CURLOPT_HTTPHEADER => $headers,
                    ));
            
                    $server_output = curl_exec($curl);
                    
                    curl_close($curl);
                    $response = json_decode($server_output,true);
                    if(isset($response['result']) && $response['result'] == "SUCCESS"){
                         DB::table('transactions')->insert([
                            'user_id' => $type == 1 ? $user->id : NULL,
                            'driver_id' => $type == 2 ? $user->id : NULL,
                            'status' => $type,
                            'booking_id' => $request->booking_id,
                            'order_id' => $request->order_id,
                            'handyman_order_id' => $request->handyman_order_id,
                            'merchant_id' => $merchant_id,
                            'payment_transaction_id'=> $merchant_order_id,
                            'amount' => $amount,
                            'payment_option_id' => $paymentConfig->payment_option_id,
                            'request_status'=> 1,
                            'status_message'=> 'PENDING',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }else{
                        return $this->failedResponse($response['errorMsg']);
                    }
            
                    return $server_output;
                }
                
                
    
    
    public function createRequestObject($title,$amount,$merchantAppId,$shortCode,$user,$merchant_order_id){
            $req = array(
                'nonce_str' => $this->TeliberrUUID(),
                'method' => 'payment.preorder',
                'timestamp' => (string) time(),
                'version' => '1.0',
                'biz_content' => [],
            );
            
            $merchant_order = $merchant_order_id ;
            
            $biz = array(
                        'notify_url' => route('teliberrNotifyNew',['merchant_id' => $user->merchant_id]), // set your notify end point
                        'trade_type' => 'InApp',
                        'appid' => $merchantAppId,
                        'merch_code' => $shortCode,
                        'merch_order_id' => $merchant_order,
                        'title' => $title,
                        'trade_type'=>"Checkout",
                        'total_amount' => $amount,
                        'trans_currency' => 'ETB',
                        'timeout_express' => '120m'
                    );
                    
             $req['biz_content'] = $biz;
            $req['sign'] = $this->sign($req);
            $req['sign_type'] = "SHA256WithRSA";
            return json_encode($req);
    }
    
    public function createRawRequest($prepayId,$merchantAppId,$shortCode){
        $rawRequest = "";
                    $maps = array(
                        "appid" => $merchantAppId,
                        "merch_code" => $shortCode,
                        "nonce_str" => $this->TeliberrUUID(),
                        "prepay_id" => $prepayId,
                        "timestamp" => time()
                    );
                    
                    foreach ($maps as $map => $m) {
                            $rawRequest .= $map . '=' . $m."&";
                    }
                    $sign = $this->sign($maps);
                    // order by ascii in array
                    $rawRequest = $rawRequest.'sign='. $sign.'&sign_type=SHA256WithRSA';
            
                    return $rawRequest;
    }
    
    // public function requestCheckout($authToken,$configUrl,$fabricAppId,$merchantAppId,$prepayId){
    //     // Header parameters
    //                 $headers = array(
    //                     "Content-Type: application/json",
    //                     "X-APP-Key: " . $fabricAppId,
    //                     "Authorization: " . $fabricToken
    //                 );
                    
    //                 // Body parameters
    //                 $payload = $this->createRequestObjectForCheckout($title, $amount,$merchantAppId,$shortCode,$user);
    //                 // dd(json_decode($payload));
    //                 $curl = curl_init();
    //                 curl_setopt_array($curl, array(
    //                     CURLOPT_URL => $configUrl . "/payment/v1/merchant/preOrder",
    //                     CURLOPT_RETURNTRANSFER => true,
    //                     CURLOPT_ENCODING => '',
    //                     CURLOPT_MAXREDIRS => 10,
    //                     CURLOPT_TIMEOUT => 30,
    //                     CURLOPT_FOLLOWLOCATION => true,
    //                     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //                     CURLOPT_CUSTOMREQUEST => 'POST',
    //                     CURLOPT_SSL_VERIFYPEER => 0,
    //                     CURLOPT_SSL_VERIFYHOST => false,
    //                     CURLOPT_POSTFIELDS => $payload,
    //                     CURLOPT_HTTPHEADER => $headers,
    //                 ));
            
    //                 $server_output = curl_exec($curl);
    //                 curl_close($curl);
            
    //                 return $server_output;
    // }
    
    public function teliberrNotify($merchant_id,Request $request){
        \Log::channel('teliberrPayNew')->emergency(['merchant_id' => $merchant_id,'request'=> $request->all()]);
        $paymentConfig = $this->getTelebirrPayConfig($merchant_id);
        //$content = file_get_contents('php://input');
        //$publicKey = $paymentConfig->auth_token;

        //$nofityData = $this->decryptRSA($content, $publicKey);
        // \Log::channel('teliberrPay')->emergency($nofityData);
        //$notify_data = json_decode($nofityData,true);
        // \Log::channel('teliberrPayNew')->emergency($notify_data);
        // if(!empty($notify_data)){
        //     if ($notify_data['tradeStatus'] == 2){
        //         $trans = DB::table('telebirrpay_transactions')->where(['payment_status' => null, 'outTradeNo' => $notify_data['outTradeNo']])->first();
        //         if (!empty($trans)){
        //             DB::table('telebirrpay_transactions')
        //                 ->where(['outTradeNo' => $notify_data['outTradeNo']])
        //                 ->update(['payment_status' => 'Success', 'updated_at' => date('Y-m-d H:i:s'), 'callback_response' => $nofityData]);
        //         }
        //     }else{
        //         $trans = DB::table('telebirrpay_transactions')->where(['payment_status' => null, 'outTradeNo' => $notify_data['outTradeNo']])->first();
        //         if (!empty($trans)){
        //             DB::table('telebirrpay_transactions')
        //                 ->where(['outTradeNo' => $notify_data['outTradeNo']])
        //                 ->update(['payment_status' => 'Failed', 'updated_at' => date('Y-m-d H:i:s'), 'callback_response' => $nofityData]);
        //         }
        //     }
        // }
    }
    
    public function TeliberrUUID()
    {
        return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    public function generateDynamicString($maxLength = 512) {
        // Define allowed characters (excluding: ^~`!#$%^*()-+=)
        $allowedChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    
        $charactersLength = strlen($allowedChars);
        $randomString = '';
    
        $length = rand(1, $maxLength); // Random length between 1 and maxLength
    
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $allowedChars[rand(0, $charactersLength - 1)];
        }
    
        return $randomString;
    }
    
    public function sign($params){
        // $signPars = '';
        // foreach ($params as $k => $v) {
        //     // Convert arrays to JSON strings
        //     if (is_array($v)) {
        //         $v = json_encode($v,JSON_UNESCAPED_SLASHES);
        //     }
            
        //     // Concatenate parameters
        //     if ($signPars == '') {
        //         $signPars = $k . '=' . $v;
        //     } else {
        //         $signPars = $signPars . '&' . $k . '=' . $v;
        //     }
        // }
        
        // // dd($signPars);
        // $sign = [
        //     'sha256' => hash("sha256", $signPars),
        //     // 'values' => $signPars
        // ];
        // return $sign;
        $exclude_fields = array("sign", "sign_type", "header", "refund_info", "openType", "raw_request");
    $data = $params;
    ksort($data);
    $stringApplet = '';
    foreach ($data as $key => $values) {

        if (in_array($key, $exclude_fields)) {
            continue;
        }

        if ($key == "biz_content") {
            foreach ($values as $value => $single_value) {
                if ($stringApplet == '') {
                    $stringApplet = $value . '=' . $single_value;
                } else {
                    $stringApplet = $stringApplet . '&' . $value . '=' . $single_value;
                }
            }
        } else {
            if ($stringApplet == '') {
                $stringApplet = $key . '=' . $values;
            } else {
                $stringApplet = $stringApplet . '&' . $key . '=' . $values;
            }
        }
    }

    $sortedString = $this->sortedString($stringApplet);

    return $this->SignWithRSA($sortedString);
    
    }
    
    public function sortedString($stringApplet)
{
    $stringExplode = '';
    $sortedArray = explode("&", $stringApplet);
    sort($sortedArray);
    foreach ($sortedArray as $x => $x_value) {
        if ($stringExplode == '') {
            $stringExplode = $x_value;
        } else {
            $stringExplode = $stringExplode . '&' . $x_value;
        }
    }

    return $stringExplode;
}

function SignWithRSA($data)
{
    
    // dd(public_path('/telebirr_private_key.pem'));

    $privateKeyLoad = file_get_contents(public_path('/telebirr_private_key.pem'));

    // $private_key = trimPrivateKey($privateKeyLoad)[2];

    // if ($rsa->loadKey($private_key) != TRUE) {
    //     echo "Error loading PrivateKey";
    //     return;
    // };
    
    $privateKey = RSA::load($privateKeyLoad);

    $privateKey->withHash('sha256');
        $privateKey->withMGFHash('sha256');

    // $rsa->signatureMode(Crypt_RSA::$signatureMode);
    $signtureByte = $privateKey->sign($data);

    return base64_encode($signtureByte);
}
    
    
}