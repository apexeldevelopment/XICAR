<?php
namespace App\Http\Controllers\PaymentMethods\PinPayment;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use App\Traits\MerchantTrait;
use DB;

class PinPayment extends Controller
{
    use MerchantTrait;
    
    private $secret_key = NULL;
    private $public_key = NULL;
    private $base_url = NULL;
    private $is_live = false;

    public function __construct($secret_key, $public_key, $is_live)
    {
        $this->secret_key = $secret_key;
        $this->public_key = $public_key;
        $this->is_live = $is_live;
        $this->base_url = ($is_live == true) ? "https://api.pinpayments.com/1" : "https://test-api.pinpayments.com/1";
    }

    public function addCard($request, $for_user = true, $user = NULL, $driver = NULL)
    {
        $response = [];
        try {
            if ($for_user) {
                $customer_token = $this->is_live ? $user->pin_payment_customer_token_live : $user->pin_payment_customer_token;
                if (!empty($customer_token)) {
                    $request_data = array(
                        "number" => $request->card_number, // "5520000000000000",
                        "expiry_month" => $request->exp_month, // "05
                        "expiry_year" => $request->exp_year, //"2023",
                        "cvc" => $request->cvv, //"123",
                        "name" => $user->first_name." ".$user->last_name, // "Roland Robot",
                        "address_line1" => "42 Sevenoaks St",
                        "address_city" => "Lathlain",
                        "address_state" => "WA",
                        "address_country" => "Australia"
                    );
                    $response = $this->addCustomerCard($customer_token, $request_data);
                } else {
                    $request_data = array(
                        "email" => $user->email,
                        "first_name" => $user->first_name,
                        "last_name" => $user->last_name,
                        "card" => array(
                            "number" => $request->card_number, // "5520000000000000",
                            "expiry_month" => $request->exp_month, // "05
                            "expiry_year" => $request->exp_year, //"2023",
                            "cvc" => $request->cvv, //"123",
                            "name" => $user->first_name." ".$user->last_name, // "Roland Robot",
                            "address_line1" => "42 Sevenoaks St",
                            "address_city" => "Lathlain",
                            "address_state" => "WA",
                            "address_country" => "Australia"
                        )
                    );
                    $response = $this->createCutomerWithCard($request_data);
                    $key_name = $this->is_live ? 'pin_payment_customer_token_live' : 'pin_payment_customer_token';
                    User::where(["id" => $user->id])->update([$key_name => $response["customer_token"]]);
                }
            } else {
                $customer_token = $this->is_live ? $driver->pin_payment_customer_token_live : $driver->pin_payment_customer_token;
                if (!empty($customer_token)) {
                    $request_data = array(
                        "number" => $request->card_number, // "5520000000000000",
                        "expiry_month" => $request->exp_month, // "05
                        "expiry_year" => $request->exp_year, //"2023",
                        "cvc" => $request->cvv, //"123",
                        "name" => $driver->first_name." ".$driver->last_name, // "Roland Robot",
                        "address_line1" => "42 Sevenoaks St",
                        "address_city" => "Lathlain",
                        "address_state" => "WA",
                        "address_country" => "Australia"
                    );
                    $response = $this->addCustomerCard($customer_token, $request_data);
                } else {
                    $request_data = array(
                        "email" => $driver->email,
                        "first_name" => $driver->first_name,
                        "last_name" => $driver->last_name,
                        "card" => array(
                            "number" => $request->card_number, // "5520000000000000",
                            "expiry_month" => $request->exp_month, // "05
                            "expiry_year" => $request->exp_year, //"2023",
                            "cvc" => $request->cvv, //"123",
                            "name" => $driver->first_name." ".$driver->last_name, // "Roland Robot",
                            "address_line1" => "42 Sevenoaks St",
                            "address_city" => "Lathlain",
                            "address_state" => "WA",
                            "address_country" => "Australia"
                        )
                    );
                    $response = $this->createCutomerWithCard($request_data);
                    $key_name = $this->is_live ? 'pin_payment_customer_token_live' : 'pin_payment_customer_token';
                    Driver::where(["id" => $driver->id])->update([$key_name => $response["customer_token"]]);
                }
            }
            return $response;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    private function createCutomerWithCard($request_data)
    {
        // p($request_data);
        $auth_token = base64_encode($this->secret_key.":"."");
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->base_url.'/customers',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic '.$auth_token, // MENZV2N6ZnZjUUoyS09PenhtcHQ1Zzo=
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response, true);
            // p($response);
            if(isset($response['response']) && isset($response['response']['token']) && $response['response']['token'] != ""){
                return array("customer_token" => $response['response']['token'], "card_token" => $response['response']['card']['token']);
            }else{
                throw new \Exception($response['error']);
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    private function addCustomerCard($pin_payment_customer_token, $request_data)
    {
        try {
            $auth_token = base64_encode($this->secret_key.":"."");

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->base_url.'/customers/'.$pin_payment_customer_token.'/cards',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic '.$auth_token, // MENZV2N6ZnZjUUoyS09PenhtcHQ1Zzo=
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response, true);
            $response = $response['response'];
            if(isset($response['token']) && $response['token']){
                return array("customer_token" => NULL, "card_token" => $response['token']);
            }else{
                throw new \Exception($response['error']);
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }


    public function chargeCard($amount, $card, $for_user = true, $user = NULL, $driver = NULL, $payment_option_id = NULL, $currency = "AUD", $booking_id = NULL, $order_id = NULL, $handyman_order_id = NULL){
        try{
            $auth_token = base64_encode($this->secret_key.":"."");
            $supported_currency = array("AUD","USD","NZD","SGD","EUR","GBP","CAD","HKD","JPY","MYR","THB","PHP","ZAR","IDR","TWD");

            if(!in_array($currency, $supported_currency)){
                $string_file = ($for_user) ? $this->getStringFile(NULL,$user->Merchant) : $this->getStringFile(NULL,$driver->Merchant);
                throw new \Exception(trans("$string_file.payment_gateway_currency_not_supported"));
            }

            $request_data = array(
                "amount" => ((int)$amount) * 1000,
                "currency" => "AUD",
                "description" => ($for_user) ? $user->Merchant->BusinessName." charge" : $driver->Merchant->BusinessName." charge",
                "email" => ($for_user) ? $user->email : $driver->email,
                "card_token" => $card->token
            );
            //                "ip_address" => "203.192.1.172",
// p($request_data);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->base_url."/charges",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic '.$auth_token, // MENZV2N6ZnZjUUoyS09PenhtcHQ1Zzo=
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response, true);
            if(isset($response['response']) && $response['response']['success'] == true){
                $transaction_id = $for_user == true ? $user->id : $driver->id;
                // enter data
                DB::table('transactions')->insert([
                    'status' => ($for_user) ? 1 : 2, // for user
                    'card_id' => $card->id,
                    'user_id' => $for_user == true ? $user->id : NULL,
                    'driver_id' => $for_user == true ? NULL : $driver->id,
                    'merchant_id' => $for_user == true ? $user->merchant_id : $driver->merchant_id,
                    'payment_option_id' => $payment_option_id,
                    'checkout_id' => NULL,
                    'booking_id' => $booking_id,
                    'order_id' => $order_id,
                    'handyman_order_id' => $handyman_order_id,
                    'payment_transaction_id' => $transaction_id.'_'.time(),
                    'payment_transaction' => $response['response']['token'],
                    'amount' => $amount, // amount
                    'request_status' =>  2,
                    'status_message' => "SUCEESS",
                ]);
                return true;
            }elseif(isset($response['error'])){
                throw new \Exception($response['error']);
            }else{
                throw new \Exception();
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}