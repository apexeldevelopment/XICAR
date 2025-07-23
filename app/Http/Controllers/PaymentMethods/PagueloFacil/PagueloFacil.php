<?php
namespace App\Http\Controllers\PaymentMethods\PagueloFacil;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Driver;
use App\Models\PaymentOption;
use App\Models\User;
use App\Traits\MerchantTrait;
use DB;

class PagueloFacil extends Controller
{
    use MerchantTrait;

    private $cclw_token = NULL;
    private $auth_token = NULL;
    private $base_url = NULL;
    private $is_live = false;

    public function __construct($secret_key, $public_key, $is_live)
    {
        $this->cclw_token = $secret_key;
        $this->auth_token = $public_key;
        $this->is_live = $is_live;
        $this->base_url = ($is_live == true) ? "https://secure.paguelofacil.com" : "https://sandbox.paguelofacil.com";
    }

    public function addCard($request, $for_user = true, $user = NULL, $driver = NULL)
    {
        $response = [];
        try {
            $payment_option = PaymentOption::where('slug', 'PAGUELO_FACIL')->first();
            if ($for_user) {
                $request_data = $this->get_api_payload(1.00, $user->first_name, $user->last_name, $user->email, $user->UserPhone, "Add Card", $request->card_number, $request->exp_month, $request->exp_year, $request->cvv);
                $response = $this->auth_and_capture($request_data);
                $paramArray = array(
                    'user_id' => $user->id,
                    'booking_id' => NULL,
                    'amount' => 1.00,
                    'narration' => 2,
                    'platform' => 2,
                    'payment_method' => 2,
                    'payment_option_id' => $payment_option->id,
                );
                WalletTransaction::UserWalletCredit($paramArray);
            } else {
                $request_data = $this->get_api_payload(1.00, $driver->first_name, $driver->last_name, $driver->email, $driver->phoneNumber, "Add Card", $request->card_number, $request->exp_month, $request->exp_year, $request->cvv);
                $response = $this->auth_and_capture($request_data);
                $paramArray = array(
                    'driver_id' => $driver->id,
                    'booking_id' => NULL,
                    'amount' => 1.00,
                    'narration' => 2,
                    'platform' => 2,
                    'payment_method' => $request->payment_method,
                    'receipt' => rand(1000,9999),
                );
                WalletTransaction::WalletCredit($paramArray);
            }
            return array("card_token" => $response);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    private function auth_and_capture($request_data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->base_url.'/rest/processTx/Auth-Capt',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($request_data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: '.$this->auth_token,
                'Content-Type: application/json',
                'locale:en'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        if(!empty($response) && isset($response["success"]) && $response["success"] == 1){
            if($response['data']['status'] == 1){
                return $response['data']['codOper'];
            }else{
                throw new \Exception($response['data']['messageSys']);
            }
        }else{
            throw new \Exception($response['headerStatus']['description']);
        }
    }

    public function chargeCard($amount, $card, $for_user = true, $user = NULL, $driver = NULL, $payment_option_id = NULL, $currency = "USD", $booking_id = NULL, $order_id = NULL, $handyman_order_id = NULL){
        try{
            $supported_currency = array("USD");

            if(!in_array($currency, $supported_currency)){
                $string_file = ($for_user) ? $this->getStringFile(NULL,$user->Merchant) : $this->getStringFile(NULL,$driver->Merchant);
                throw new \Exception(trans("$string_file.payment_gateway_currency_not_supported"));
            }

            if($for_user){
                $request_data = $this->get_card_payment_api_payload($amount, $user->email, $user->UserPhone,$card->token);
            }else{
                $request_data = $this->get_card_payment_api_payload($amount, $driver->email, $driver->phoneNumber, $card->token);
            }
            $transaction_token = $this->card_payment($request_data);

            if($transaction_token){
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
                    'payment_transaction_id' => $transaction_token,
                    'payment_transaction' => $transaction_token,
                    'amount' => $amount, // amount
                    'request_status' =>  2,
                    'status_message' => "SUCEESS",
                ]);
                return true;
            }elseif(isset($response['error'])){
                throw new \Exception($response['error']);
            }else{
                throw new \Exception($transaction_token);
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    private function card_payment($request_data)
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->base_url.'/rest/processTx/RECURRENT',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: '.$this->auth_token,
                    'Content-Type: application/json',
                    'locale:en'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response, true);
            if(!empty($response) && isset($response["success"]) && $response["success"] == 1){
                if($response['data']['status'] == 1){
                    return $response['data']['codOper'];
                }else{
                    throw new \Exception($response['data']['messageSys']);
                }
            }else{
                throw new \Exception($response['headerStatus']['description']);
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    private function get_api_payload($amount, $first_name, $last_name, $email, $phone, $description, $card_number, $exp_month, $exp_year, $cvv){
        return array(
            "cclw" => $this->cclw_token,
            "amount" => $amount,
            "taxAmount" => $amount,
            "email" => $email,
            "phone" => $phone,
            "concept" => $description,
            "description" => $description,
            "lang" => 'EN',
            "cardInformation" => array(
                "cardNumber" => $card_number,
                "expMonth" => $exp_month,
                "expYear" => $exp_year,
                "cvv" => $cvv,
                "firstName" => $first_name,
                "lastName" => $last_name,
            )
        );
    }

    private function get_card_payment_api_payload($amount, $email, $phone, $card_token, $description = "Taxi Payment"){
        return array(
            "cclw" =>  $this->cclw_token,
            "amount" => $amount,
            "taxAmount" => 0.00,
            "email" => $email,
            "phone" => $phone,
            "codOper" => $card_token,
            "concept" => "Taxi Payment",
            "description" => "Taxi Payment"
        );
    }
}