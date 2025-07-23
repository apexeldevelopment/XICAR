<?php
namespace App\Http\Controllers\PaymentMethods\Webxpay;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Driver;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use DB;
use Illuminate\Http\Request;
use App\Models\Transaction;

class Webxpay extends Controller
{
    use MerchantTrait, ApiResponseTrait;

    public function makePayment(Request $request, $check_for = "user")
    {
        $response = [];
        try {
            $payer = ($check_for == "user") ? $request->user('api') : $request->user('api-driver');
            $payment_option = PaymentOption::where('slug', 'WEBXPAY')->first();
            $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id','=', $payer->merchant_id],['payment_option_id','=', $payment_option->id]])->first();

            $base_url = ($payment_option_config->gateway_condition == 1) ? "https://webxpay.com" : "https://stagingxpay.info";

            $transaction_id = rand(1000,99999);
            $plaintext = "$transaction_id|$request->amount";
            //load public key for encrypting

            $publickey = $payment_option_config->additional_data;

            openssl_public_encrypt($plaintext, $encrypt, $publickey);
            //encode for data passing
            $payment = base64_encode($encrypt);
            //checkout URL
            $url = $base_url."/index.php?route=checkout/billing";

            $transaction = new Transaction();
            $transaction->status = ($check_for == "user") ? 1 : 2; // for user
            $transaction->card_id = NULL;
            $transaction->user_id = ($check_for == "user") ? $payer->id : NULL;
            $transaction->driver_id = ($check_for == "user") ? NULL : $payer->id;
            $transaction->merchant_id = $payer->merchant_id;
            $transaction->payment_option_id = $payment_option->id;
            $transaction->checkout_id = NULL;
            $transaction->booking_id = NULL;
            $transaction->order_id = NULL;
            $transaction->handyman_order_id = NULL;
            $transaction->payment_transaction_id = NULL;
            $transaction->payment_transaction = NULL;
            $transaction->amount = $request->amount; // amount
            $transaction->request_status =  1; // PENDING
            $transaction->status_message = "PENDING";
            $transaction->save();

            //custom fields
            //cus_1|cus_2|cus_3|cus_4
            $custom_fields = base64_encode("$payer->merchant_id|$check_for|$payer->id|$transaction->id");

            $user_data = array(
                "first_name" => $payer->first_name,
                "last_name" => $payer->last_name,
                "email" => $payer->email,
                "contact_number" => ($check_for == "user") ? $payer->phoneNumber : $payer->UserPhone,
                "address_line_one" => "",
                "address_line_two" => "",
                "city" => "",
                "state" => "",
                "postal_code" => "",
                "country" => $payer->Country->CountryName,
                "process_currency" => "LKR",
                "cms" => "PHP",
                "custom_fields" => $custom_fields,
                "enc_method" => "JCs3J+6oSz4V0LgE0zi/Bg==",
                "secret_key" => $payment_option_config->api_secret_key,
                "payment" => $payment
            );
            return view("webxpay.index",compact("url","user_data"));
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function returnCallBack(Request $request){
        //decode & get POST parameters
        $payment = base64_decode($_POST ["payment"]);
        $signature = base64_decode($_POST ["signature"]);
        $custom_fields = base64_decode($_POST ["custom_fields"]);
        $custom_fields_varible = explode('|', $custom_fields);

        $merchant_id = $custom_fields_varible[0];
        $check_for = $custom_fields_varible[1];
        $payer_id = $custom_fields_varible[2];
        $transaction_id = $custom_fields_varible[3];

        $payer = ($check_for == "user") ? User::find($payer_id) : Driver::find($payer_id);
        $payment_option = PaymentOption::where('slug', 'WEBXPAY')->first();
        $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id','=', $payer->merchant_id],['payment_option_id','=', $payment_option->id]])->first();

        //load public key for signature matching
        $publickey = $payment_option_config->additional_data;

        openssl_public_decrypt($signature, $value, $publickey);

        $signature_status = false ;

        //get payment response in segments
        //payment format: order_id|order_refference_number|date_time_transaction|status_code|comment|payment_gateway_used
        $responseVariables = explode('|', $payment);
        $payment_status = false;
        if($value == $payment){
            $signature_status = true ;
            $check_for_value = ($check_for == "user") ? 1 : 2;
            $payer_column = ($check_for == "user") ? "user_id" : "driver_id";
            $transaction = Transaction::where(["id" => $transaction_id, "merchant_id" => $merchant_id, "status" => $check_for_value, $payer_column => $payer_id])->first();
            if(!empty($transaction) && $transaction->request_status == 1){
                DB::beginTransaction();
                try{
                    if($responseVariables[3] == 0){
                        if ($transaction->status == 1) { // For User
                            $paramArray = array(
                                'user_id' => $payer_id,
                                'booking_id' => NULL,
                                'amount' => $transaction->amount,
                                'narration' => 2,
                                'platform' => 2,
                                'payment_method' => 2,
                                'payment_option_id' => $payment_option->id,
                                'transaction_id' => $responseVariables[1]
                            );
                            WalletTransaction::UserWalletCredit($paramArray);
                        } else {
                            $paramArray = array(
                                'driver_id' => $payer_id,
                                'booking_id' => NULL,
                                'amount' => $transaction->amount,
                                'narration' => 2,
                                'platform' => 2,
                                'payment_method' => $payment_option->name,
                                'receipt' => $responseVariables[1],
                                'transaction_id' => $responseVariables[1]
                            );
                            WalletTransaction::WalletCredit($paramArray);
                        }
                        Transaction::where(["id" => $transaction->id])->update(["request_status" => 2, "status_message" => "SUCCESS", "payment_transaction" => json_encode($responseVariables)]);
                        $payment_status = true;
                    }else{
                        Transaction::where(["id" => $transaction->id])->update(["request_status" => 3, "status_message" => "FAILED", "payment_transaction" => json_encode($responseVariables)]);
                    }
                }catch (\Exception  $exception){
                    DB::rollback();
                    return $this->failedResponse($exception->getMessage());
                }
                DB::commit();
            }
        }
        if($signature_status == true) {
            if($payment_status == true) {
                return $this->successResponse("Success");
            }else{
                return $this->failedResponse("Payment Failed");
            }
        }else {
            return $this->failedResponse("Failed");
        }
    }
}