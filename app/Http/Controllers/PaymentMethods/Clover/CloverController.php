<?php

namespace App\Http\Controllers\PaymentMethods\Clover;
use App\Http\Controllers\Controller;
use hisorange\BrowserDetect\Exceptions\Exception;
use Illuminate\Http\Request;
use DB;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use App\Traits\ContentTrait;

class CloverController extends Controller
{
    use ApiResponseTrait, MerchantTrait, ContentTrait;

    public function __construct()
    {
    }

    //
    //Steps to integrate payment gateway
    // Reffered Document =>REST API with access token
    // first create token from card (one time use only)
    // save card using that token
    // create charge using token (with/without capture)
    // cpature the payment

    //b58dca91767e82531a3501f9512daaa9 (access token)
//public : b58dca91767e82531a3501f9512daaa9
// private : ed1113f0-c5ec-85d7-bdea-f14d27b6734e




    public function paymentRequest($request,$payment_option_config,$calling_from){

        try {
            // check whether gateway is on sandbox or live
            $url = "https://scl-sandbox.dev.clover.com/v1/charges";
            $token_url = "https://token-sandbox.dev.clover.com/v1/tokens";
            $access_token = "ed1113f0-c5ec-85d7-bdea-f14d27b6734e";
            $public_token = "b58dca91767e82531a3501f9512daaa9";
            if($payment_option_config->gateway_condition == 1)
            {
                $url = "https://scl.clover.com/v1/charges";
                $token_url = "https://token.clover.com/v1/tokens";
                $access_token = $payment_option_config->api_secret_key; // Private access token
                $public_token = $payment_option_config->api_public_key; // public access token
            }

            // check whether request is from driver or user
            if($calling_from == "DRIVER")
            {
                $driver = $request->user('api-driver');
                $code = $driver->Country->phonecode;
                $country = $driver->Country;
                $country_name = $country->CountryName;
                $currency = $driver->Country->isoCode;
                $phone_number = str_replace($code,"",$driver->phoneNumber);
                $logged_user = $driver;
                $user_merchant_id = $driver->driver_merchant_id;
                $first_name = $driver->first_name;
                $last_name = $driver->last_name;
                $email = $driver->email;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
            }
            else
            {
                $user = $request->user('api');
                $code = $user->Country->phonecode;
                $country = $user->Country;
                $country_name = $country->CountryName;
                $currency = $user->Country->isoCode;
                $phone_number = str_replace($code,"",$user->UserPhone);
                $logged_user = $user;
                $user_merchant_id = $user->user_merchant_id;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $email = $user->email;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
            }

            $transaction_id = $id.'_'.time();
            $fields['card'] = array(
             'number'=>$request->card_number,
             'exp_month'=>$request->exp_month,
             'exp_year'=>$request->exp_year,
             'cvv'=>$request->cvv,
            );
            $fields = json_encode($fields);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $token_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$fields,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'apikey:'.$public_token
                ),
            ));
            $response = curl_exec($curl);

            curl_close($curl);
            $response = json_decode($response,true);
            $data = [
                'type'=>'Token',
                'data'=>$response
            ];
            \Log::channel('clover_api')->emergency($data);

            if(isset($response['error']) && !empty($response['error']['message']))
            {
                throw new Exception($response['error']['message']);
            }

            // create charge
            $capture = in_array($request->calling_for,['TAXI','HANDYMAN']) ? false : true;
            $amount = $request->amount *100;
            $fields_string = [
                'amount'=>$amount,
                'currency'=>'cad',
                'capture'=>$capture,
                'source'=>$response['id'],
            ];

            $fields_string = json_encode($fields_string);
            //$url = "https://scl.clover.com/v1/charges";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER,
                array('Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer '.$access_token//3d89d8fd-464e-d126-9f46-3d6b8948caf8
                )
            );

           $response1 = curl_exec($ch);
            curl_close($ch);

            $response1 = json_decode($response1,true);
            $data = [
                'type'=>'Charges',
                'data'=>$response1
            ];
            \Log::channel('clover_api')->emergency($data);
            // return error
            if(isset($response1['error']) && !empty($response1['error']['message']))
            {
                throw new Exception($response1['error']['message']);
            }

            // enter data
            DB::table('transactions')->insert([
                'status' => 1, // for user
                'card_id' => NULL,
                'user_id' => $calling_from == "USER" ? $id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                'merchant_id' => $merchant_id,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'checkout_id' => NULL,
                'booking_id' => $request->booking_id ? $request->booking_id : NULL,
                'order_id' => $request->order_id ? $request->order_id : NULL,
                'handyman_order_id' => $request->handyman_order_id ? $request->handyman_order_id : NULL,
                'payment_transaction_id' => $transaction_id,
                'payment_transaction' => NULL,
                'reference_id' => $response1['id'], // payment reference id
                'amount' => $amount, // amount
                'request_status' => $capture == true ? 2 : 1,
                'status_message' => $response1['status'],
            ]);

            return ['payment_capture_id'=>$response1['id']]; // reference_id

        }catch(\Exception $e)
        {
            throw new Exception($e->getMessage());
        }
    }


    // add card
    public function addCard(Request $request,$payment_option_config)
    {

        try {

//            $url = "https://token.clover.com/v1/tokens";
//            $access_token = $payment_option_config->api_secret_key; // Private access token
//            $public_token = $payment_option_config->api_public_key; // public access token

//            $url = "https://scl-sandbox.dev.clover.com/v1/charges";
            $token_url = "https://token-sandbox.dev.clover.com/v1/tokens";
            $access_token = "ed1113f0-c5ec-85d7-bdea-f14d27b6734e";
            $public_token = "b58dca91767e82531a3501f9512daaa9";
            $customer_url = "https://scl-sandbox.dev.clover.com/v1/customers";
            if($payment_option_config->gateway_condition == 1)
            {
//                $url = "https://scl.clover.com/v1/charges";
                $token_url = "https://token.clover.com/v1/tokens";
                $access_token = $payment_option_config->api_secret_key; // Private access token
                $public_token = $payment_option_config->api_public_key; // public access token
                $customer_url = "https://scl.clover.com/v1/customers";
            }

            $fields['card'] = array(
                'number' => $request->cc_number,
                'exp_month' => $request->exp_month,
                'exp_year' => $request->exp_year,
                'cvv' => $request->cvv,
            );
            $fields = json_encode($fields);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $token_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $fields,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'apikey:' . $public_token
                ),
            ));
            $response = curl_exec($curl);

            curl_close($curl);
            $response = json_decode($response,true);
            $data = [
                'type'=>'Token for customer',
                'data'=>$response
            ];
            \Log::channel('clover_api')->emergency($data);
            if (isset($response['error']) && !empty($response['error']['message'])) {
                throw new Exception($response['error']['message']);
            }

            // create charge
            $fields_string = [
                'source' => $response['id'],
                'email' => $request->email,
            ];

            $fields_string = json_encode($fields_string);
           // $customer_url = "https://scl.clover.com/v1/customers";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_URL, $customer_url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER,
                array('Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token//3d89d8fd-464e-d126-9f46-3d6b8948caf8
                )
            );

            $response1 = curl_exec($ch);
            curl_close($ch);
            $response1 = json_decode($response1, true);
            $data = [
                'type'=>'Create Customer for saved token',
                'data'=>$response1
            ];
            \Log::channel('clover_api')->emergency($data);
            // return error
            if (isset($response1['error']) && !empty($response1['error']['message'])) {
                throw new Exception($response1['error']['message']);
            }

            return $response1;

        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // card payment for wallet recharge
    public function cloverCardPayment($amount,$card,$user,$payment_option_config,$calling_from ="USER" ,$bookingId = NULL, $order_id = NULL, $handyman_order_id = NULL)
    {

        try {

//            $access_token = $payment_option_config->api_secret_key; // Private access token
//            $public_token = $payment_option_config->api_public_key; // public access token

            $url = "https://scl-sandbox.dev.clover.com/v1/charges";
          //  $token_url = "https://token-sandbox.dev.clover.com/v1/tokens";
            $access_token = "ed1113f0-c5ec-85d7-bdea-f14d27b6734e";
           // $public_token = "b58dca91767e82531a3501f9512daaa9";
            //$customer_url = "https://scl-sandbox.dev.clover.com/v1/customers";
            if($payment_option_config->gateway_condition == 1)
            {
//                $url = "https://scl.clover.com/v1/charges";
               // $token_url = "https://token.clover.com/v1/tokens";
                $access_token = $payment_option_config->api_secret_key; // Private access token
               // $public_token = $payment_option_config->api_public_key; // public access token
               // $customer_url = "https://scl.clover.com/v1/customers";
            }

//            $amount = $request->amount *100;
            $amount = $amount *100;
            $fields_string = [
                'amount'=>$amount,
                'currency'=>'cad',
                'source'=>$card->token,
            ];
            $fields_string = json_encode($fields_string);
            //$url = "https://scl.clover.com/v1/charges";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER,
                array('Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$access_token//3d89d8fd-464e-d126-9f46-3d6b8948caf8
                )
            );

            $response1 = curl_exec($ch);
            curl_close($ch);

            $response1 = json_decode($response1,true);
            $data = [
                'type'=>'payment by saved card',
                'data'=>$response1
            ];
            \Log::channel('clover_api')->emergency($data);

            // return error
            if(isset($response1['error']) && !empty($response1['error']['message']))
            {
                throw new Exception($response1['error']['message']);
            }
            // insert data in transaction table

            $transaction_id = $user->id.'_'.time();
            // enter data
            DB::table('transactions')->insert([
                'status' => 1, // for user
                'card_id' => NULL,
                'user_id' => $calling_from == "USER" ? $user->id : NULL,
                'driver_id' => $calling_from == "DRIVER" ? $user->id : NULL,
                'merchant_id' => $user->merchant_id,
                'payment_option_id' => $payment_option_config->payment_option_id,
                'checkout_id' => NULL,
                'booking_id' => $bookingId,
                'order_id' => $order_id,
                'handyman_order_id' => $handyman_order_id,
                'payment_transaction_id' => $transaction_id,
                'payment_transaction' => NULL,
                'reference_id' => $response1['id'], // payment reference id
                'amount' => $amount, // amount
                'request_status' =>  2,
                'status_message' => $response1['status'],
            ]);

        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }


    // capture payment
    public function capturePayment($amount,$capture,$payment_option_config,$booking_id = NULL,$order_id = NULL,$handyman_order_id = NULL)
    {

        try {

            $capture_url = "https://scl-sandbox.dev.clover.com/v1/charges/".$capture."/capture";
           // $url = "https://scl-sandbox.dev.clover.com/v1/charges";
            //  $token_url = "https://token-sandbox.dev.clover.com/v1/tokens";
            $access_token = "ed1113f0-c5ec-85d7-bdea-f14d27b6734e";
            // $public_token = "b58dca91767e82531a3501f9512daaa9";
            //$customer_url = "https://scl-sandbox.dev.clover.com/v1/customers";
            if($payment_option_config->gateway_condition == 1)
            {
                $capture_url = "https://scl.clover.com/v1/charges/".$capture."/capture";
//                $url = "https://scl.clover.com/v1/charges";
                // $token_url = "https://token.clover.com/v1/tokens";
                $access_token = $payment_option_config->api_secret_key; // Private access token
                // $public_token = $payment_option_config->api_public_key; // public access token
                // $customer_url = "https://scl.clover.com/v1/customers";
            }

//            $access_token = $payment_option_config->api_secret_key; // Private access token
           // $capture = $request->capture;//WSTRNCZC5Y2ER;
            $amount = 1;
            $amount = $amount *100;
            $fields_string = [
                'amount'=>$amount,
//                'currency'=>'cad',
            ];
            $fields_string = json_encode($fields_string);
//            $url = "https://scl.clover.com/v1/charges/".$capture."/capture";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_URL, $capture_url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER,
                array('Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$access_token//3d89d8fd-464e-d126-9f46-3d6b8948caf8
                )
            );

            $response1 = curl_exec($ch);
            curl_close($ch);

            $response1 = json_decode($response1,true);
            $data = [
                'type'=>'captured payment at ride/booking end',
                'data'=>$response1
            ];
            \Log::channel('clover_api')->emergency($data);
            // return error
            if(isset($response1['error']) && !empty($response1['error']['message']))
            {
                throw new Exception($response1['error']['message']);
            }
            // insert data in transaction table

//            DB::table('transactions')->where([['reference_id','=',$capture],['booking_id','=',$booking_id],['order_id','=',$order_id],['handyman_order_id','=',$handyman_order_id]])->update([
//                'status' => 2,
//            ]);

            DB::table('transactions')->where('reference_id','=',$capture)->update([
                'request_status' => 2,
            ]);

        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }
}