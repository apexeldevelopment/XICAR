<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 20/3/23
 * Time: 3:06 PM
 */

namespace App\Http\Controllers\PaymentMethods\Midtrans;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Traits\PaymentNotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use DB;

class MidtransController extends Controller
{
    use ApiResponseTrait, MerchantTrait, PaymentNotificationTrait;

    public function callback(Request $request, $transaction_id)
    {
        try {
            $transaction = Transaction::find($transaction_id);
            $data = $request->all();

            // $response = array(
            //     'request' => $request->all(),
            // );
            \Log::channel('mtrans_api')->emergency($request->all());

            if (array_key_exists('status_code',$data) && $data['status_code'] == 200 && $data['transaction_status'] == "settlement") {
                if (!empty($transaction) && ($transaction->request_status == 1 || $transaction->request_status == 5)) {
                    if($transaction->request_status == 1){
                        if ($transaction->status == 1) { // If User
                            $paramArray = array(
                                'user_id' => $transaction->user_id,
                                'booking_id' => NULL,
                                'amount' => $transaction->amount,
                                'narration' => 2,
                                'platform' => 2,
                                'payment_method' => 2,
                                'payment_option_id' => $transaction->payment_option_id,
                            );
                            // WalletTransaction::UserWalletCredit($paramArray);
                            return redirect(route('midtrans-success'));
                        } else {
                            $paramArray = array(
                                'driver_id' => $transaction->driver_id,
                                'booking_id' => NULL,
                                'amount' => $transaction->amount,
                                'narration' => 2,
                                'platform' => 2,
                                'payment_method' => 2,
                                'receipt' => rand(1000, 9999),
                            );
                            // WalletTransaction::WalletCredit($paramArray);
                            return redirect(route('midtrans-success'));
                        }
                    }
                    $transaction->payment_transaction_id = $data['order_id'];
                    $transaction->request_status = 2; // SUCCESS
                    $transaction->status_message = "SUCCESS";
                    $transaction->save();

                    $for = $transaction->status == 1 ? "USER" : "DRIVER";
                    $receiver_id  = $transaction->status == 1 ? $transaction->user_id : $transaction->driver_id;
                    $this->paymentSuccessNotification($for, $receiver_id, $transaction->merchant_id, "Payment Success", "Payment Success");
                }
            } else {
                $transaction->request_status = 3; // FAILED
                $transaction->status_message = "FAILED";
                $transaction->save();
                $for = $transaction->status == 1 ? "USER" : "DRIVER";
                $receiver_id  = $transaction->status == 1 ? $transaction->user_id : $transaction->driver_id;
                $this->paymentFailedNotification($for, $receiver_id, $transaction->merchant_id, "Payment Failed", "Payment Failed");
            }
            return "OK";
        } catch (\Exception $exception) {
            $response = array(
                'request' => $data,
                'error' => $exception->getMessage(),
                'detail' => $exception->getTraceAsString(),
            );
            \Log::channel('mtrans_api')->emergency($response);
            return "OK";
        }
    }

    public function createTransaction($request, $payment_option_config, $calling_from)
    {
        try{
            $user = ($calling_from == "USER") ? $request->user('api') : $request->user('api-driver');
            $user_id = $user->id;
            $merchant_id = $user->merchant_id;
            $transaction_id = "MIDTRANS-".$merchant_id.Carbon::now()->timestamp;

            $request_status = $request->booking_payment == "true" ? 5 : 1;

            $transaction = new Transaction();
            $transaction->status = $calling_from == "USER" ? 1 : 2;
            $transaction->card_id = NULL;
            $transaction->user_id = $calling_from == "USER" ? $user_id : NULL;
            $transaction->driver_id = $calling_from == "DRIVER" ? $user_id : NULL;
            $transaction->merchant_id = $merchant_id;
            $transaction->payment_option_id = $payment_option_config->payment_option_id;
            $transaction->checkout_id = NULL;
            $transaction->amount = $request->amount;
            $transaction->payment_transaction_id = $transaction_id;
            $transaction->payment_transaction = NULL;
            $transaction->request_status = $request_status;
            $transaction->status_message = 'PENDING';
            $transaction->save();

            $base_url = $payment_option_config->gateway_condition == 1 ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
            $secret_key = base64_encode($payment_option_config->api_public_key);

            $request_data = array(
                "transaction_details" => array(
                    "order_id" => $transaction_id,
                    "gross_amount" => $request->amount
                ),
                "credit_card" => array(
                    "secure" => true
                ),
                "callbacks" => array(
                    "finish" => route("midtrans.callback",$transaction->id)
                )
            );

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url."/snap/v1/transactions",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic $secret_key",
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response);
            if(isset($response->token) && !empty($response->token)){
                $transaction->payment_transaction = $response->token;
                $transaction->save();

                return array(
                    'status' => 'NEED_TO_OPEN_WEBVIEW',
                    'transaction_id' => $transaction_id,
                    'url' => $response->redirect_url,
                    'success_url' => route('midtrans-success'),
                    'fail_url' => route('midtrans-fail'),
                );
            }else{
                throw new \Exception(json_encode($response));
            }
        }catch(\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
    
    public function Success(Request $request)
    {
        \Log::channel('mtrans_api')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Fail(Request $request)
    {
        \Log::channel('mtrans_api')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }
}
