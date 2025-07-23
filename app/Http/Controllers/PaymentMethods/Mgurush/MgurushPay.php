<?php

namespace App\Http\Controllers\PaymentMethods\Mgurush;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Traits\ApiResponseTrait;


class MgurushPay extends Controller
{
    //
    use ApiResponseTrait;
    
    public function initiatePaymentRequest(Request $request, $paymentConfig, $calling_from, $string_file)
    {
        DB::beginTransaction();
        try {

            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
            } else {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
            }

            $access_key = $paymentConfig->api_public_key;
            $secret_key = $paymentConfig->api_secret_key;
            $url  = "https://uat.mgurush.com/irh/ecomTxn/betting";


            $data = json_encode([
                "mobileNumber"=> $request->mobile_number,
                "txnRefNumber" => "Ref-".$merchant_id."-".$id."-".time(),
                "amount"=> (float)$request->amount,
                "currency"=> $request->currency,
            ]);
            
            // Generate HMAC using SHA-256 algorithm
            $hash = hash_hmac('sha256', $data, $secret_key, true);
            $hashInBase64 = base64_encode($hash);

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
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    'access_key: ' .$access_key,
                    'Content-Type: application/json',
                    'hmac: '.$hashInBase64
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response, true);

            if (isset($res)) {
                if ($res['status']['statusCode'] == 0) {
                    $calling_for = $request->calling_from == 'BOOKING' ? 3 : ($request->calling_from == "USER" ? 1 : 2);
                    $payment_transaction_id = $res['message']['txnRefNumber'];
                    DB::table('transactions')->insert([
                        'user_id' => $request->calling_from == "USER" ? $id : NULL,
                        'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                        'status' => $calling_for,
                        'merchant_id' => $merchant_id,
                        'payment_transaction_id' => $payment_transaction_id,
                        'amount' => $request->amount,
                        'payment_option_id' => $paymentConfig->payment_option_id,
                        'request_status' => 1,
                        "payment_mode" => "Third-party App",
                        'status_message' => 'PENDING',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    DB::commit();
                }
                 return $res;
            } else {
                return trans("$string_file.something_went_wrong");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    
    public function paymentStatus($request, $paymentConfig, $string_file){

        DB::beginTransaction();
        try{
            $access_key = $paymentConfig->api_public_key;
            $secret_key = $paymentConfig->api_secret_key;
            $url  = "https://uat.mgurush.com/irh/ecomTxn/getTxnStatus";

            $data = json_encode([
                "txnRefNumber"=> $request->transaction_id,
            ]);
            $dataToHash = "POST" . $url . $data;
            
            // Generate HMAC using SHA-256 algorithm
            $hash = hash_hmac('sha256', $data, $secret_key, true);
            $hashInBase64 = base64_encode($hash);

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
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    'access_key: ' .$access_key,
                    'Content-Type: application/json',
                    'hmac: '.$hashInBase64
                ),
            ));
            
            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response, true);
            

            if (isset($res)) {
                if ($res['status']['statusCode'] == 0) {
                        $data = [
                                'request_status' =>4,
                                'status_message'=>"OTHER",
                                'payment_transaction' => $response,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];
                    
                    if($res['message']['txnStatus'] == "Initiated"){
                        $data = [
                                'request_status' => 1,
                                'status_message'=>$res['message']['txnStatus'],
                                'payment_transaction' => $response,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];
                    }
                    else if($res['message']['txnStatus'] == "Success"){
                        $data = [
                                'request_status' => 2,
                                'status_message'=>$res['message']['txnStatus'],
                                'payment_transaction' => $response,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];
                    }
                    DB::table('transactions')
                        ->where(['payment_transaction_id' => $res['message']['txnRefNumber']])
                        ->update($data);

                    DB::commit();
                }
                else if($res['status']['statusCode'] == 1){
                    
                    if($res['message']['txnStatus'] == "Failed"){
                        DB::table('transactions')
                        ->where(['payment_transaction_id' => $res['message']['txnRefNumber']])
                        ->update([
                            'request_status' => 3,
                            'status_message'=>$res['message']['txnStatus'],
                            'payment_transaction' => $response,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                    else{
                            return trans("$string_file.something_went_wrong");
                    }
                }
            }
            else{
                return trans("$string_file.something_went_wrong");
            }
            
            $transaction_id = $request->transaction_id;
            $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transaction_id)->first();
            $request_status_text = "failed";
            $payment_status = false;
            if(isset($transaction_table)){
                $payment_status =   $transaction_table->request_status == 2 ?  true : false;
                if ($transaction_table->request_status == 1) {
                    $request_status_text = "processing";
                } else if ($transaction_table->request_status == 2) {
                    $request_status_text = "success";
                }
            }
            return ['payment_status' => $payment_status, 'request_status' => $request_status_text , "transaction_status"=>$transaction_table->request_status];
            
        }catch(\Exception $e){
            
            DB::rollBack();
            throw $e;
        }
    }
}
