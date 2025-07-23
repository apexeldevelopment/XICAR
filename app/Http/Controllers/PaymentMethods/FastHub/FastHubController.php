<?php

namespace App\Http\Controllers\PaymentMethods\FastHub;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use DB;


class FastHubController extends Controller
{
    use ApiResponseTrait;
    public function fasthubPaymentRequest(Request $request, $paymentConfig, $calling_from)
    {
        // dd($paymentConfig);
        DB::beginTransaction();
        // dd("working");
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

            $clientSecret = $paymentConfig->api_secret_key;
            $clientId = $paymentConfig->api_public_key;
            $psk = $paymentConfig->auth_token;
            $creds = "Basic " . base64_encode($clientId . ":" . $clientSecret);

            $reference_id = (string)($merchant_id . "-" . $id . "-" . time() . str_random(16));
            $channel_id = 2975;

            $datastring = "amount=" . urlencode($request->amount) . "&channel=" . urlencode($channel_id) . "&callback_url=" . urlencode(route("fasthub-callback")) . "&recipient=" . urlencode($request->recipient) . "&reference_id=" . urlencode($reference_id) . "&trx_date=" . urlencode(date('Y-m-d H:i:s'));
            $hash = hash_hmac("sha256", $datastring, $psk);

            $transactiontype = "Debit";
            if(!empty($request->transaction_type)){
                $transactiontype = ($request->transaction_type =="DEPOSIT") ? "Deposit" : "Debit";
            }

            $data = [
                "request" => [
                    "hash" => $hash,
                    "channel" => $channel_id,
                    "callback_url" => route("fasthub-callback"),
                    "recipient" => (string)$request->recipient,
                    "amount" => (float)$request->amount,
                    "trx_date" => date('Y-m-d H:i:s'),
                    "reference_id" => (string)$reference_id,
                    "bill_ref" => (string)$request->recipient,
                    "transactionTypeName" =>  $transactiontype,
                ]
            ];
            
            $data = json_encode($data);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://gcs-api.fasthub.co.tz/fasthub/mobile/money/debitdeposit/api/v2/json',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $creds,
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response, true);
            
            \Log::channel('fasthub')->emergency(['Payment_initiate_response '=>$res]);
            
            if (isset($res)) {
                if ($res['isSuccessful'] && $res['error_code'] == 0) {
                    $calling_for = $request->calling_from == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
                    $reference_id = $res['reference_id'];
                    DB::table('transactions')->insert([
                        'user_id' => $request->calling_from == "USER" ? $id : NULL,
                        'driver_id' =>  $request->calling_from == "DRIVER" ? $id : NULL,
                        'status' => $calling_for,
                        'merchant_id' => $merchant_id,
                        'payment_transaction_id' => $reference_id,
                        'amount' => $request->amount,
                        'payment_option_id' => $paymentConfig->payment_option_id,
                        'request_status' => 1,
                        "payment_mode" => "Third-party App",
                        'status_message' => 'PENDING',
                        'transaction_type'=> ($transactiontype == "Debit") ? 1 : 2,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    DB::commit();
                }
                return $res;
            } else {
                return $this->failedResponse(trans("$string_file.something_went_wrong"));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }


    public function fasthubCallback(Request $request)
    {
        \Log::channel('fasthub')->emergency(["callback_res" => $request->all()]);
        $data = $request->all();
        
        $return_id = null;
        $refrence_id = null;
        if( array_key_exists("txid",$data) ){
            $return_id = $data['txid'];
        }
        elseif( array_key_exists("reference_id",$data) ){
            $return_id = $data['reference_id'];
        }
        
        if( array_key_exists("reference",$data) ){
            $refrence_id = $data['reference'];
        }
        elseif( array_key_exists("reference_id",$data) ){
            $refrence_id = $data['reference_id'];
        }

        if (!empty($data)) {
            $trans = DB::table('transactions')->where(['request_status' => 1, 'payment_transaction_id' => $return_id])->first();
            if (!empty($trans)) {
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $return_id])
                    ->update([
                        'request_status' => 2,
                        'payment_transaction' => json_encode($request->all()),
                        'reference_id' => $refrence_id,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status_message' => 'SUCCESS',
                    ]);
            }
        }
        
        return response()->json([
            "code" => "0",
            "status" => "OK",
            "reference_id" => $return_id,
        ]);
    }
    
    
    public function paymentStatus(Request $request)
    {
        $transaction_id = $request->transaction_id; // order id
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
    }
}
