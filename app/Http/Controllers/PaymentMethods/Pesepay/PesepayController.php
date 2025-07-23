<?php

namespace App\Http\Controllers\PaymentMethods\Pesepay;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PesepayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    const BASE_URL = "https://api.pesepay.com/api/payments-engine";
    const INITIATE_PAYMENT_URL = self::BASE_URL.'/v1/payments/initiate';
    const CHECK_PAYMENT_STATUS_URL = self::BASE_URL.'/v1/payments/check-payment';
    const ALGORITHM = 'AES-256-CBC';
    const INIT_VECTOR_LENGTH = 16;

    public function getPesepayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'PESEPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

//    public function getBaseUrl($env){
//        return $env == 1 ? 'https://voucherapi.demos.classicinformatics.net/api/' : 'https://voucherapi.demos.classicinformatics.net/api/';
//    }

    public function generatePesepayUrl($request, $paymentOption, $calling_from){
//        $validator = Validator::make($request->all(), [
//            'type' => 'required',
//            'amount' => 'required',
//            'currency' => 'required',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return $this->failedResponse($errors[0]);
//        }

        DB::beginTransaction();
        try{
            if ($calling_from == 'USER'){
                $user = $request->user('api');
                $merchant = $user->Merchant;
                $merchant_id = $merchant->id;
            }else{
                $driver = $request->user('api-driver');
                $merchant = $driver->Merchant;
                $merchant_id = $merchant->id;
            }

//            $paymentOption = $this->getPesepayConfig($merchant_id);
//            $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);
            $external_ref_id = date('YmdHis');
            $post_data = [
                'amountDetails' => [
                    'amount' => $request->amount,
                    'currencyCode' => $request->currency
                ],
                'reasonForPayment' => $merchant->BusinessName.' Payment',
                'resultUrl' => route('pesepay.result'),
                'returnUrl' => route('pesepay.return',['reference_id' => $external_ref_id])
            ];

            $encryptedData = $this->encrypt(json_encode($post_data), $paymentOption->api_secret_key);
            $payload = json_encode(['payload' => $encryptedData]);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => self::INITIATE_PAYMENT_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => array(
                    'authorization: '.$paymentOption->auth_token,
                    'content-type: application/json',
                ),
            ));

            $response = curl_exec($curl);
            $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $result = json_decode($response, true);
            if ($status_code == 200){
                $decryptedData = $this->decrypt($result['payload'], $paymentOption->api_secret_key);
                $jsonDecoded = json_decode($decryptedData, true);
                $referenceNumber = $jsonDecoded['referenceNumber'];
//                $pollUrl = $jsonDecoded['pollUrl'];
                $complete_url = $jsonDecoded['redirectUrl'];

                $calling_for = $calling_from == 'BOOKING' ? 3 : ($calling_from == "USER" ? 1 : 2);
                DB::table('transactions')->insert([
                    'status' => $calling_for,
                    'reference_id' => $external_ref_id,
                    'card_id' => NULL,
                    'merchant_id' => $merchant_id,
                    'payment_option_id' => $paymentOption->payment_option_id,
                    'checkout_id' => NULL,
                    'amount' => $request->amount,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'payment_transaction_id' => $referenceNumber,
                    'payment_transaction' => NULL,
                    'request_status' => 1,
                    'user_id' => $calling_from == "USER" ? $request->user('api')->id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $request->user('api-driver')->id : NULL,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

            }else{
                DB::rollBack();
                $message = $result['message'];
                throw new \Exception($message);
//                return $this->failedResponse($message);
            }
        }catch (\Exception $e){
            DB::rollBack();
            throw new \Exception($e->getMessage());
//            return $this->failedResponse($e->getMessage());
        }

        DB::commit();
        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $complete_url ?? '',
            'success_url' => route('pesepay.success-url'),
            'fail_url' => route('pesepay.fail-url'),
            'transaction_id' => $referenceNumber
        ];
    }

    public function PesepayResult(Request $request){
        \Log::channel('pesepay')->emergency($request->all());
        $trans = DB::table('transactions')->where(['request_status' => 1,'payment_transaction_id' => $request->referenceNumber])->first();
        if (!empty($trans)){
            if ($request->transactionStatus == 'SUCCESS'){
                DB::table('transactions')
                    ->where(['payment_transaction_id' => $request->referenceNumber])
                    ->update(['request_status' => 2, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s')]);

                $receipt = "Application : " . $request->referenceNumber;
                $paramArray = array(
                    'booking_id' => NULL,
                    'amount' => $trans->amount,
                    'narration' => 2,
                    'platform' => 2,
                    'payment_method' => 2,
                    'receipt' => $receipt,
                    'transaction_id' => $request->referenceNumber,
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
                    ->where(['payment_transaction_id' => $request->referenceNumber])
                    ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }else{
            \Log::channel('pesepay')->emergency(['message' => 'Transaction Not Found!']);
        }
    }

    public function PesepayReturn(Request $request){
        $trans = DB::table('transactions')->where(['reference_id' => $request->reference_id])->first();
        if (!empty($trans)){
            if ($trans->request_status == 2){
                return redirect(route('pesepay.success-url'));
            }else{
                return redirect(route('pesepay.fail-url'));
            }
        }else{
            $msg = "Transaction Reference Not Found!";
            return redirect(route('pesepay.fail-url',['msg' => $msg]));
        }
    }

    public function PesepaySuccess(Request $request){
        $message = !empty($request->msg) ? $request->msg : "Success";
        echo "<h2 style='text-align: center;color: green'>".$message."</h2>";
    }

    public function PesepayFail(Request $request)
    {
        $message = !empty($request->msg) ? $request->msg : "Failed";
        echo "<h2 style='text-align: center;color: red'>".$message."</h2>";
    }

    public function paymentStatus($request){
        $tx_reference = $request->transaction_id; // order id
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $tx_reference)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        if($transaction_table->request_status == 1){
            $request_status_text = "processing";
        }
        else if($transaction_table->request_status == 2){
            $request_status_text = "success";
        }else{
            $request_status_text = "failed";
        }
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
    }

    private function encrypt($plainText, $encryptionKey){
        try {
            if (!$this->isKeyLengthValid($encryptionKey)) {
                throw new \InvalidArgumentException("Secret key's length must be 128, 192 or 256 bits");
            }

            $initVector = substr($encryptionKey, 0, self::INIT_VECTOR_LENGTH);
            $raw = openssl_encrypt($plainText, self::ALGORITHM, $encryptionKey, 0, $initVector);
            return $raw;
        } catch (\Exception $e) {
            return new static(isset($initVector), null, $e->getMessage());
        }
    }

    private function decrypt($cipherText, $encryptionKey){
        try {
            if (!$this->isKeyLengthValid($encryptionKey)) {
                throw new \InvalidArgumentException("Secret key's length must be 128, 192 or 256 bits");
            }

            $encoded = base64_decode($cipherText);
            $initVector = substr($encryptionKey, 0, self::INIT_VECTOR_LENGTH);
            $decoded = openssl_decrypt($encoded, self::ALGORITHM, $encryptionKey, OPENSSL_RAW_DATA,$initVector);
            if ($decoded === false) {
                // Operation failed
                return new static(isset($initVector), null, openssl_error_string());
            }

            return $decoded;
        } catch (\Exception $e) {
            return new static(isset($initVector), null, $e->getMessage());
        }

    }

    private function isKeyLengthValid($secretKey){
        $length = strlen($secretKey);
        return $length == 16 || $length == 24 || $length == 32;
    }
}
