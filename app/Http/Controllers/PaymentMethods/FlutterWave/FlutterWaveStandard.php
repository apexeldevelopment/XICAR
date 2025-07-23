<?php

namespace App\Http\Controllers\PaymentMethods\FlutterWave;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FlutterWaveStandard extends Controller
{
    //
    public function initiatePayment($request, $payment_option_config, $calling_from){
        DB::beginTransaction();
        try{
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
            }
            else{
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
            }
            $currency = $request->currency;
            $tax_ref = 'TRANS'.uniqid().time();
            $amount = $request->amount;

            $data = [
                "tx_ref" => $tax_ref,
                "amount" => $amount,
                "currency" => $currency,
                "redirect_url" => route('flutterwave-callback'),
                "customer" => [
                    "email" => $user->email,
                    "name" => $user->first_name." ".$user->last_name,
                    "phonenumber" => ($calling_from == "DRIVER") ? $user->phoneNumber : $user->UserPhone,
                ],
                "customizations" => [
                    "title" => "Flutterwave Standard Payment"
                ],
                "configurations"=> [
                    "session_duration"=> 10, // Session timeout in minutes (maxValue: 1440)
                    "max_retry_attempt"=> 5  // Max retry (int)
                ],
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.flutterwave.com/v3/payments',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$payment_option_config->api_secret_key,
                    'Content-Type: application/json'
                ),
            ));

            $res = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($res);

            if(isset($response->data->link) && isset($response->status) && $response->status == "success"){
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $tax_ref,
                    'reference_id'=> '',
                    'amount' => $amount,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'request_status'=> 1,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }else{
                throw new \Exception('Payment Url not generated');
            }
        }
        catch (\Exception $e){
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        DB::commit();
        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $response->data->link ?? '',
            'transaction_id'=> $tax_ref,
            'success_url' => route('flutterwave-success'),
            'fail_url' => route('flutterwave-fail'),
        ];
    }


    public function callbackUrl(Request $request){
        \Log::channel('flutterwave_standard')->emergency(['callback'=> $request->all()]);
        $data = $request->all();
        $taxRef = $data['tx_ref'];
        if(isset($taxRef) && isset($data['status']) && $data['status'] == "successful" ){
            DB::table('transactions')->where('payment_transaction_id', $taxRef)->update([
                'request_status' => 2,
                'status_message' => 'SUCCESS',
                'payment_transaction'=> json_encode($data),
                'reference_id'=>$data['transaction_id'],
            ]);
            return redirect()->route("flutterwave-success");
        }else{
            DB::table('transactions')->where('payment_transaction_id', $taxRef)->update([
                'request_status' => 3,
                'status_message' => 'FAIL',
                'payment_transaction'=> json_encode($data),
                'reference_id'=>$data['transaction_id'],
            ]);
            return redirect()->route("flutterwave-fail");
        }
    }

    public function Success(Request $request){
        \Log::channel('flutterwave_standard')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Fail(Request $request){
        \Log::channel('flutterwave_standard')->emergency($request->all());
        echo "<h1>Success</h1>";
    }
}
