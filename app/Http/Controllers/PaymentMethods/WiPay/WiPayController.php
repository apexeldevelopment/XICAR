<?php

namespace App\Http\Controllers\PaymentMethods\WiPay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\DB;
use App\Traits\ContentTrait;

class WiPayController extends Controller
{
    use ApiResponseTrait, MerchantTrait, ContentTrait;

    public function WiPayInitiate($request, $payment_option_config, $calling_from)
    {
        DB::beginTransaction();
        try {
            if ($calling_from == 'USER' || $calling_from == 'BOOKING') {
                $user = $request->user('api');
                $id = $user->id;
                $email = $user->email;
                $country_code = $user->Country->country_code;
                $merchant_id = $user->merchant_id;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
//                $currency = $user->Country->isoCode;
            } else {
                $user = $request->user('api-driver');
                $id = $user->id;
                $email = $user->email;
                $country_code = $user->CountryArea->Country->country_code;
                $merchant_id = $user->merchant_id;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
//                $currency = $user->Country->isoCode;
            }

            $order_id = 'ORD_' . date('His');
            $environment = $payment_option_config->gateway_condition == 1 ? 'live' : 'sandbox';
            $return_url = \route('wipay.return');
            $country_code = "TT";
            $data = array(
                'account_number' => $payment_option_config->api_public_key,
                'country_code' => $country_code,
                'total' => $request->amount,
                'currency' => $request->currency,
                'response_url' => $return_url,
                'environment' => $environment,
                'fee_structure' => 'customer_pay',
                'method' => 'credit_card',
                'order_id' => $order_id,
                'origin' => str_replace(" ","",$user->Merchant->BusinessName),
                'email' => $email,
                'fname' => $first_name,
                'lname' => $last_name
            );
            $fieldsString = http_build_query($data);

            $url = $this->getUrl($country_code);
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HEADER => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded'
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $fieldsString,
                CURLOPT_RETURNTRANSFER => true
            ]);
            $result = curl_exec($curl);
            curl_close($curl);

            $result = json_decode($result,true);
            $payment_transaction_id = NULL;
            if(isset($result['message']) && $result['message'] == 'OK'){
                $payment_transaction_id = $result['transaction_id'];
                $complete_url = $result['url'];
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'checkout_id' => NULL,
                    'amount' => $request->amount,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'payment_transaction_id' => $result['transaction_id'],
                    'payment_transaction' => NULL,
                    'request_status' => 1,
                    'reference_id' => $order_id
                ]);
            }else{
                DB::rollBack();
                throw new \Exception($result['message']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        DB::commit();
        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $complete_url ?? '',
            'success_url' => route('wipay.success'),
            'fail_url' => route('wipay.fail'),
            'transaction_id' => $payment_transaction_id
        ];
    }

    public function getUrl($country){
        $url = "";
        switch ($country){
            case "TT":
                $url = "https://tt.wipayfinancial.com/plugins/payments/request";
                break;
            case "JM":
                $url = "https://jm.wipayfinancial.com/plugins/payments/request";
                break;
            case "BB":
                $url = "https://bb.wipayfinancial.com/plugins/payments/request";
                break;
        }
        return $url;
    }

    public function WiPayReturn(Request $request)
    {
        $trans = DB::table('transactions')->where(['request_status' => 1, 'payment_transaction_id' => $request->transaction_id])->first();
        if (!empty($trans)) {
            $payment_status = $request->status == 'success' ? 2 : 3;
            DB::table('transactions')
                ->where(['payment_transaction_id' => $request->transaction_id])
                ->update(['request_status' => $payment_status, 'payment_transaction' => json_encode($request->all()), 'updated_at' => date('Y-m-d H:i:s')]);
            $msg = $request->message;
            if ($request->status == 'success') {
                return redirect(route('wipay.success', $msg));
            } else {
                return redirect(route('wipay.fail', $msg));
            }
        }
    }

    public function WiPaySuccess(Request $request)
    {
        $message = !empty($request->msg) ? $request->msg : "Success";
        echo "<h2 style='text-align: center;color: green'>" . $message . "</h2>";
    }

    public function WiPayFail(Request $request)
    {
        $message = !empty($request->msg) ? $request->msg : "Failed";
        echo "<h2 style='text-align: center;color: red'>" . $message . "</h2>";
    }

    public function paymentStatus($request)
    {
        $tx_reference = $request->transaction_id; // order id
        $transaction_table = DB::table("transactions")->where('payment_transaction_id', $tx_reference)->first();
        $payment_status = $transaction_table->request_status == 2 ? true : false;
        if ($transaction_table->request_status == 1) {
            $request_status_text = "processing";
        } else if ($transaction_table->request_status == 2) {
            $request_status_text = "success";
        } else {
            $request_status_text = "failed";
        }
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
    }
}