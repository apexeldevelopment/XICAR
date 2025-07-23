<?php

namespace App\Http\Controllers\PaymentMethods\Tingg;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use checkout\encryption\Encryption;

class TinggController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function TinggCheckout($request, $payment_option_config, $calling_from){
        DB::beginTransaction();
        try{
            if($calling_from == "USER"){
                $user = $request->user('api');
                $id = $user->id;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $email = $user->email;
                $merchant = $user->Merchant;
                $merchant_id = $user->merchant_id;
                $currency = $user->Country->isoCode;
                $country_code = $user->Country->country_code;
            }else{
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $first_name = $driver->first_name;
                $last_name = $driver->last_name;
                $email = $driver->email;
                $merchant = $driver->Merchant;
                $merchant_id = $driver->merchant_id;
                $currency = $driver->CountryArea->Country->isoCode;
                $country_code = $driver->CountryArea->Country->country_code;
            }

            $transaction_id = 'TINGG_'.date('YmdHis');
            $phone = str_replace('+', '', $request->phone);
            $request_data = [
                "msisdn" => $phone,
                "language_code" => "en",
                "raise_invoice" => false,
                "request_amount" => $request->amount,
                "request_description" => $merchant->BusinessName,
                "payment_option_code" => "",
                "callback_url" => route('tingg.callback'),
                "fail_redirect_url" => route('tingg.fail'),
                "success_redirect_url" => route('tingg.success'),
                "merchant_transaction_id" => $transaction_id,
                // "service_code" => "SOFTBRAND",
                "service_code" => $payment_option_config->operator,
                "due_date" => date('Y-m-d H:i:s',strtotime("+3 minutes")),
                "account_number" => "101",
                "currency_code" => $currency,
                "country_code" => $this->getThreeISO($country_code),
                "customer_first_name" => $first_name,
                "customer_last_name" => $last_name,
                "customer_email" => $email,
                "invoice_number" => ""
            ];
            
            $obj = new Encryption();
            $encryptedParams = $obj->encrypt($payment_option_config->api_public_key, $payment_option_config->api_secret_key, $request_data);
            $base_url = $payment_option_config->gateway_condition == 1 ? "https://checkout.tingg.africa/express/checkout" : "https://online.uat.tingg.africa/testing/express/checkout";
            $url = $base_url."?access_key=".$payment_option_config->auth_token."&encrypted_payload=".$encryptedParams;
            DB::table('transactions')->insert([
                'user_id' => $calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'merchant_id' => $merchant_id,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'checkout_id' => NULL,
                'amount' => $currency.' '.$request->amount,
                'booking_id' => $request->booking_id,
                'order_id' => $request->order_id,
                'handyman_order_id' => $request->handyman_order_id,
                'payment_transaction_id' => $transaction_id,
                'payment_transaction' => NULL,
                'request_status' => 1,
            ]);
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        DB::commit();
        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $url ?? '',
            'success_url' => route('tingg.success'),
            'fail_url' => route('tingg.fail'),
            'transaction_id' => $transaction_id
        ];
    }

    public function TinggCallback(Request $request){
        // \Log::channel('tingg_checkout')->emergency($request->ip());
        \Log::channel('tingg_checkout')->emergency($request->all());
        $data = $request->all();
        if(!empty($data) && $data['request_status_code'] == 178){
            $trans = DB::table('transactions')->where(['payment_transaction_id' => $data['merchant_transaction_id'], 'request_status' => 1])->first();
            if (!empty($trans)){
                DB::table('transactions')
                ->where(['payment_transaction_id' => $data['merchant_transaction_id']])
                ->update(['request_status' => 2, 'payment_transaction' => json_encode($request->all())]);
            }
        }else{
            DB::table('transactions')
                ->where(['payment_transaction_id' => $data['merchant_transaction_id']])
                ->update(['request_status' => 3, 'payment_transaction' => json_encode($request->all())]);
        }
        
        $return_data = [
            'checkout_request_id' => $data['checkout_request_id'],
            'merchant_transaction_id' => $data['merchant_transaction_id'],
            'status_code' => $data['request_status_code'] == 178 ? 183 : 188,
            'status_description' => 'Payment processed successfully',
            'receipt_number' => 'REC_'.date('YmdHis')
        ];
        \Log::channel('tingg_checkout')->emergency(json_encode($return_data,true));
        echo json_encode($return_data,true);
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

    public function getThreeISO($iso){
        switch ($iso){
            case "IN":
                $three_iso = "IND";
                break;
            case "MW":
                $three_iso = "MWI";
                break;
            case "GH":
                $three_iso = "GHA";
                break;
            case "NG":
                $three_iso = "NGA";
                break;
            default:
                $three_iso = "IND";
                break;
        }
        return $three_iso;
    }

    public function TinggSuccess(){
        echo "<h3 style='text-align: center'>Success!</h3>";
    }

    public function TinggFailed(){
        echo "<h3 style='text-align: center'>Failed!</h3>";
    }
}
