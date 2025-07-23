<?php

namespace App\Http\Controllers\PaymentMethods\Aubpay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Transaction;


class AubPayController extends Controller
{
    public function getSignature($secret_key, $params)
    {
        unset($params['sign']);
        ksort($params);
        $queryString = "";
        foreach ($params as $k => $v) {
            if ($v !== "" && $v !== null) {
                $queryString .= "$k=$v&";
            }
        }
        $queryString .= "key=$secret_key";
        return strtoupper(md5($queryString));
    }

    public function paymentRequest($request, $paymentConfig, $calling_from)
    {
        DB::beginTransaction();
        try {
            $status = 3;
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $status = 2;
            } elseif($calling_from == "USER") {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 1;
            }

            $sign_key = $paymentConfig->auth_token;
            $merch_id = $paymentConfig->api_public_key;
            $out_trade_no = uniqid().time();
            $nonce_str = uniqid();

            $params = [
                'body' => 'TRANSIT-'.uniqid(),
                'mch_create_ip' => $request->ip(),
                'mch_id' => $merch_id,
                'nonce_str' => $nonce_str,
                'notify_url' => route('aub-callback'),
                'out_trade_no' => $out_trade_no,
                'service' => 'pay.gcash.native',
                'sign_type' => 'MD5',
                'total_fee' => $request->amount
            ];

            $token = $this->getSignature($sign_key, $params);
            if (empty($token)) {
                throw new \Exception('Signature Error Occured');
            }


            $url = 'https://gateway.wepayez.com/pay/gateway';
            $data = "<xml>
                    <body><![CDATA[{$params['body']}]]></body>
                    <mch_create_ip><![CDATA[{$params['mch_create_ip']}]]></mch_create_ip>
                    <mch_id>{$params['mch_id']}</mch_id>
                    <nonce_str>{$params['nonce_str']}</nonce_str>
                    <notify_url><![CDATA[{$params['notify_url']}]]></notify_url>
                    <out_trade_no>{$params['out_trade_no']}</out_trade_no>
                    <service><![CDATA[{$params['service']}]]></service>
                    <sign><![CDATA[{$token}]]></sign>
                    <sign_type>MD5</sign_type>
                    <total_fee>{$params['total_fee']}</total_fee>
                </xml>";

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://gateway.wepayez.com/pay/gateway',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$data,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/xml',
                ),
            ));

            $response = curl_exec($curl);
            $gwResponse = new \SimpleXMLElement($response);
            $responseArray = [];
            foreach ($gwResponse as $key => $value) {
                $responseArray[$key] = (string) $value;
            }
            curl_close($curl);

            if (isset($responseArray) && $responseArray['status'] == 0  && $responseArray['result_code'] == 0) {

                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id' => $nonce_str,
                    'reference_id'=>$out_trade_no,
                    'amount' => $request->amount,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'request_status' => 1,
                    'status_message' => 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();

                return [
                    'status' => 'NEED_TO_OPEN_WEBVIEW',
                    'url' => $responseArray['code_img_url'] ?? '',
                    'transaction_id' => $nonce_str ?? '',
                    'success_url' => route('aub-success'),
                    'fail_url' => route('aub-fail'),
                ];
            } else {
                \Log::channel('aub_pay')->emergency(["error" => $responseArray]);
                throw new \Exception($responseArray['message']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function AubPayCallback(Request $request)
    {
        \Log::channel('aub_pay')->emergency(["notify_url_callback"=>$request->all()]);
        return "success";
    }

    public function AubPayFail(Request $request)
    {
        $data = $request->all();
        \Log::channel('aub_pay')->emergency(["fail_callback"=>$data]);
        echo "<h1>Success</h1>";
    }

    public function AubPaySuccess(Request $request)
    {
        $data = $request->all();
        \Log::channel('aub_pay')->emergency(["success_callback"=>$data]);
        echo "<h1>Failed</h1>";
    }


    public function paymentStatus($request, $paymentConfig)
    {
        try{
            $sign_key = $paymentConfig->auth_token;
            $merch_id = $paymentConfig->api_public_key;
            $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $request->transaction_id)->first();
            if(empty($transaction_table)){
                return $data = ['payment_status' => false, 'request_status' => "TRANSACTION_NOT_FOUND", 'transaction_status' => 0];
            }

            $params = [
                'mch_id' => $merch_id,
                'nonce_str' => $transaction_table->payment_transaction_id,
                'out_trade_no' => $transaction_table->reference_id,
                'service' => 'unified.trade.query',
                'sign_type' => 'MD5',
            ];

            $token = $this->getSignature($sign_key, $params);
            if (empty($token)) {
                throw new \Exception('Signature Error Occured');
            }


            $url = 'https://gateway.wepayez.com/pay/gateway';

            $data = "<xml>
          <service><![CDATA[{$params['service']}]]></service>
          <mch_id><![CDATA[{$params['mch_id']}]]></mch_id>
          <nonce_str><![CDATA[{$params['nonce_str']}]]></nonce_str>
          <out_trade_no><![CDATA[{$params['out_trade_no']}]]></out_trade_no>
          <sign><![CDATA[{$token}]]></sign>
          <sign_type><![CDATA[{$params['sign_type']}]]></sign_type>
        </xml>";

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://gateway.wepayez.com/pay/gateway',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$data,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/xml',
                ),
            ));

            $response = curl_exec($curl);
            $gwResponse = new \SimpleXMLElement($response);
            $responseArray = [];
            foreach ($gwResponse as $key => $value) {
                $responseArray[$key] = (string) $value;
            }

            curl_close($curl);


            if (!empty($responseArray)) {
                $trans = DB::table('transactions')->where(['payment_transaction_id' => $responseArray['nonce_str']])->first();
                if (!empty($trans)) {

                    if($responseArray['trade_state'] == "SUCCESS"){
                        $payment_status = 2;
                    }
                    else if($responseArray['trade_state'] == "NOTPAY"){
                        $payment_status  = 1;
                    }
                    else{
                        $payment_status = 3;
                    }


                    if($payment_status == 1){
                        $data = ['payment_status' => false, 'request_status' => "PENDING", 'transaction_status' => 1];
                    }
                    else if ($payment_status == 2) {
                        DB::table('transactions')
                            ->where(['payment_transaction_id' => $responseArray['nonce_str']])
                            ->update([
                                'request_status' => 2,
                                'card_is_active' => 1,
                                'payment_transaction' => json_encode($responseArray),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'status_message' => 'SUCCESS'
                            ]);
                        $data = ['payment_status' => true, 'request_status' => "SUCCESS", 'transaction_status' => 2];
                    }
                    else {
                        DB::table('transactions')
                            ->where(['payment_transaction_id' => $responseArray['nonce_str']])
                            ->update([
                                'request_status' => 3,
                                'card_is_active' => 2,
                                'payment_transaction' => json_encode($responseArray),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'status_message' => 'FAIL'
                            ]);
                        $data = ['payment_status' => false, 'request_status' => "FAILED", 'transaction_status' => 3];
                    }
                }
            }
            return $data;
        }
        catch(\Exception $e){
            \Log::channel('aub_pay')->emergency(["payment_status_error"=>$e->getMessage()]);
            return $e->getMessage();
        }
    }
}
