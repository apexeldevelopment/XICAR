<?php

namespace App\Http\Controllers\PaymentMethods\GCash;
use App\Http\Controllers\Controller;
use hisorange\BrowserDetect\Exceptions\Exception;
use Illuminate\Http\Request;
use DB;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use App\Traits\ContentTrait;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Onesignal;


class GCashController extends Controller
{
    use ApiResponseTrait, MerchantTrait, ContentTrait;

    public function __construct()
    {
    }

//    public function paymentRequest($request,$payment_option_config,$calling_from){
//
//        try {
//            // check whether gateway is on sandbox or live
//            $digest_auth = $payment_option_config->additional_data; // public access token
//            $passwordAgent = $payment_option_config->api_secret_key; // Private access token
//            $loginAgent = $payment_option_config->api_public_key; // public access token
//            $serviceCode = $payment_option_config->auth_token; // public access token
//            $user_name = '9C80264CDA3AD7AF463D15FB6C69E92E2837DDF5E9812E27DEEDE3B781FCD5C5';
//            $password = 'B303D8F83F33517B43E1FB28B9DA98FD8FD1A5C39DE5274791B1D0567D123695';
//            if($payment_option_config->gateway_condition == 1)
//            {
//                $passwordAgent = $payment_option_config->api_secret_key; // Private access token
//                $loginAgent = $payment_option_config->api_public_key; // public access token
//                $serviceCode = $payment_option_config->auth_token; // public access token
//                $digest_auth = $payment_option_config->additional_data; // public access token
//                $digest_auth = json_decode($digest_auth,true);
//                $user_name = $digest_auth['username'];
//                $password = $digest_auth['password'];
//            }
//
//            // check whether request is from driver or user
//            if($calling_from == "DRIVER")
//            {
//                $driver = $request->user('api-driver');
//                $code = $driver->Country->phonecode;
//                $country = $driver->Country;
//                $country_name = $country->CountryName;
//                $currency = "CLP";//$driver->Country->isoCode;
//                $phone_number = $driver->phoneNumber;
//                $logged_user = $driver;
//                $user_merchant_id = $driver->driver_merchant_id;
//                $first_name = $driver->first_name;
//                $last_name = $driver->last_name;
//                $email = $driver->email;
//                $id = $driver->id;
//                $merchant_id = $driver->merchant_id;
//                $description = "driver wallet topup";
//            }
//            else
//            {
//                $user = $request->user('api');
//                $code = $user->Country->phonecode;
//                $country = $user->Country;
//                $country_name = $country->CountryName;
//                $currency = "CLP";//$user->Country->isoCode;
//                $phone_number = $user->UserPhone;
//                $logged_user = $user;
//                $user_merchant_id = $user->user_merchant_id;
//                $first_name = $user->first_name;
//                $last_name = $user->last_name;
//                $email = $user->email;
//                $id = $user->id;
//                $merchant_id = $user->merchant_id;
//                $description = "payment from user";
//            }
//
//            $amount = $request->amount;
//            $transaction_id = $id.'_'.time();
//
//
//            $call_back_url = route('touch-pay.callback');
//            $fields['idFromClient'] = $transaction_id;
//            $fields['additionnalInfos'] = [
//                'recipientEmail'=>$email,
//                'recipientFirstName'=>$first_name,
//                'recipientLastName'=>$last_name,
//                'destinataire'=>"54791752",//
//            ];
//            $fields['amount'] = $amount;
//            $fields['currency'] = $currency;
//            $fields['callback'] = $call_back_url;
//            $fields['serviceCode'] = $serviceCode; //
//            $fields['recipientNumber'] = $request->recipient_number; //
//
//            $url = 'https://api.gutouch.com/dist/api/touchpayapi/v1/IMPER2033/transaction?loginAgent='.$loginAgent.'&passwordAgent='.$passwordAgent;
//
//
////            '9C80264CDA3AD7AF463D15FB6C69E92E2837DDF5E9812E27DEEDE3B781FCD5C5:B303D8F83F33517B43E1FB28B9DA98FD8FD1A5C39DE5274791B1D0567D123695', // username:password
//
//            $fields = json_encode($fields);
//
//            $curl = curl_init();
//
//            curl_setopt_array($curl, array(
//                CURLOPT_FOLLOWLOCATION => true,
//                CURLOPT_URL => $url,
//                CURLOPT_RETURNTRANSFER => true,
//                CURLOPT_USERPWD => $user_name.':'.$password, // username:password
//                CURLOPT_ENCODING => '',
//                CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
//                CURLOPT_MAXREDIRS => 10,
//                CURLOPT_TIMEOUT => 0,
//                CURLOPT_FOLLOWLOCATION => true,
//                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                CURLOPT_CUSTOMREQUEST => 'PUT',
//                CURLOPT_POSTFIELDS =>$fields,
//                CURLOPT_HTTPHEADER => array(
//                    'Content-Type: application/json'
//                ),
//            ));
//
//            $response = curl_exec($curl);
//            curl_close($curl);
//            $response = json_decode($response,true);
//
//
//            $data = [
//                'type'=>'Token',
//                'data'=>$response
//            ];
//            \Log::channel('touch_pay_api')->emergency($data);
//            if(isset($response['code']) && !empty($response['code']))
//            {
//                throw new Exception($response['description']);
//            }
//            if(isset($response['status']) && $response['status'] == 401)
//            {
//                throw new Exception($response['detailMessage']);
//            }
//
//            // enter data
//            DB::table('transactions')->insert([
//                'status' => 1, // for user
//                'card_id' => NULL,
//                'user_id' => $calling_from == "USER" ? $id : NULL,
//                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
//                'merchant_id' => $merchant_id,
//                'payment_option_id' => $payment_option_config->payment_option_id,
//                'checkout_id' => NULL,
//                'booking_id' => $request->booking_id ? $request->booking_id : NULL,
//                'order_id' => $request->order_id ? $request->order_id : NULL,
//                'handyman_order_id' => $request->handyman_order_id ? $request->handyman_order_id : NULL,
//                'payment_transaction_id' => $transaction_id,
//                'payment_transaction' => json_encode($response),
//                'reference_id' => $response['idFromClient'], // payment reference id
//                'amount' => $amount, // amount
//                'request_status' => 1,
//                'status_message' => $response['status'],
//            ]);
//
//            $string_file  = $this->getStringFile($merchant_id);
//            return trans("$string_file.touchpay_payment_initiated");
//
//        }catch(\Exception $e)
//        {
//            throw new Exception($e->getMessage());
//        }
//    }

    public function gcashCallback(Request $request)
    {
        $request_response = $request->all();

//        $transaction_id = $request_response['partner_transaction_id'];
//        $reference_id = $request_response['gu_transaction_id'];
//        $loginAgent = "60795570"; // Private access token
//        $passwordAgent = "thiveP9astaw09Ub"; // Private access token
//        $status_url = "https://api.gutouch.com/dist/api/touchpayapi/v1/IMPER2033/transaction/'".$transaction_id."'?loginAgent='".$loginAgent."'&passwordAgent=".$passwordAgent;
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_VERBOSE, true);
//        curl_setopt($ch, CURLOPT_URL, $status_url);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//        curl_setopt($ch, CURLOPT_HTTPHEADER,
//            array('Accept: application/json',
//                'Content-Type: application/json'
//            )
//        );
//
//        $response2 = curl_exec($ch);
//        curl_close($ch);
//
//        $response2 = json_decode($response2,true);
        // p($response2);
        $data = [
            'type'=>'Payment status',
            'data'=>$request_response
        ];
        \Log::channel('gcash_api')->emergency($data);

//        $transaction_table =  DB::table("transactions")->where('payment_transaction_id',$transaction_id)->first();
//        if(isset($request_response['status']) && $request_response['status'] == "SUCCESSFUL")
//        {
//            DB::table("transactions")->where('payment_transaction_id',$transaction_id)->update(['request_status' =>2,'status_message'=>$request_response['status'],'reference_id'=>$reference_id]);
//
//
//            // credit user wallet & and sed notification to user for payment success
//// p($transaction_table->merchant_id);
//            if(!empty($transaction_table->user_id))
//            {
//                $paramArray = [
//                    'amount'=> $transaction_table->amount,
//                    'user_id'=> $transaction_table->user_id,
//                    'narration'=> 2,
//                ];
//                WalletTransaction::UserWalletCredit($paramArray);
//
//                // payment done notification
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_done");
//                $title =trans("$string_file.payment_success");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['user_id'] = $transaction_table->user_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::UserPushMessage($arr_param);
//
//            }
//            elseif(!empty($transaction_table->driver_id))
//            {
//
//
//                $paramArray = array(
//                    'merchant_id' => $transaction_table->merchant_id,
//                    'driver_id' => $transaction_table->driver_id,
//                    'amount' => $transaction_table->amount,
//                    'narration' => 2,
//                    'platform' => 1,
//                    'payment_method' => 4,
//                );
//                WalletTransaction::WalletCredit($paramArray);
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_done");
//                $title =trans("$string_file.payment_success");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['driver_id'] = $transaction_table->driver_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::DriverPushMessage($arr_param);
//
//            }
//
//
//        }
//        elseif(isset($request_response['status']) && $request_response['status'] == "FAILED")
//        {
//            // payment failed
//            DB::table("transactions")->where('payment_transaction_id',$transaction_id)->update(['request_status' =>3,'status_message'=>$request_response['status'],'reference_id'=>$reference_id]);
//            if(!empty($transaction_table->user_id))
//            {
//
//                // payment done notification
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_failed");
//                $title =trans("$string_file.payment_failed_title");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['user_id'] = $transaction_table->user_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::UserPushMessage($arr_param);
//
//            }
//            elseif(!empty($transaction_table->driver_id))
//            {
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_failed");
//                $title =trans("$string_file.payment_failed_title");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['driver_id'] = $transaction_table->driver_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::DriverPushMessage($arr_param);
//
//            }
//        }
    }











    //    Card payment by webview
//    public function paymentRequest($request,$payment_option_config,$calling_from){
//
//        try {
//            // check whether gateway is on sandbox or live
//            $url = "https://api-uat.kushkipagos.com/card-async/v1/init";
//            $token_url = "https://api-uat.kushkipagos.com/card-async/v1/tokens";
//            $private_merchant_id = "5e990516d9964fa3bfe3a638f6a18156";
//            $public_merchant_id = "4951ab3a025c43d2b978edbbc7089130";
//            $status_url = "https://api-uat.kushkipagos.com/card-async/v1/status/";
//            if($payment_option_config->gateway_condition == 1)
//            {
//                $url = "https://scl.clover.com/v1/charges";
//                $token_url = "https://token.clover.com/v1/tokens";
//                $status_url = "https://api-uat.kushkipagos.com/card-async/v1/status/";
//                $private_merchant_id = $payment_option_config->api_secret_key; // Private access token
//                $public_merchant_id = $payment_option_config->api_public_key; // public access token
//            }
//
//            // check whether request is from driver or user
//            if($calling_from == "DRIVER")
//            {
//                $driver = $request->user('api-driver');
//                $code = $driver->Country->phonecode;
//                $country = $driver->Country;
//                $country_name = $country->CountryName;
//                $currency = "CLP";//$driver->Country->isoCode;
//                $phone_number = $driver->phoneNumber;
//                $logged_user = $driver;
//                $user_merchant_id = $driver->driver_merchant_id;
//                $first_name = $driver->first_name;
//                $last_name = $driver->last_name;
//                $email = $driver->email;
//                $id = $driver->id;
//                $merchant_id = $driver->merchant_id;
//                $description = "driver wallet topup";
//            }
//            else
//            {
//                $user = $request->user('api');
//                $code = $user->Country->phonecode;
//                $country = $user->Country;
//                $country_name = $country->CountryName;
//                $currency = "CLP";//$user->Country->isoCode;
//                $phone_number = $user->UserPhone;
//                $logged_user = $user;
//                $user_merchant_id = $user->user_merchant_id;
//                $first_name = $user->first_name;
//                $last_name = $user->last_name;
//                $email = $user->email;
//                $id = $user->id;
//                $merchant_id = $user->merchant_id;
//                $description = "payment from user";
//            }
//
//            $amount = (int)$request->amount;
//            $transaction_id = $id.'_'.time();
//
//
//            $call_back_url = route('kushki.card.callback');
//            $fields['totalAmount'] = $amount;
//            $fields['currency'] = $currency;
//            $fields['returnUrl'] = $call_back_url;
//            $fields['email'] = $email;
//            $fields['description'] = $description.' '.$transaction_id;
//
//            $fields = json_encode($fields);
//
//            $curl = curl_init();
//            curl_setopt_array($curl, array(
//                CURLOPT_URL => $token_url,
//                CURLOPT_RETURNTRANSFER => true,
//                CURLOPT_ENCODING => '',
//                CURLOPT_MAXREDIRS => 10,
//                CURLOPT_TIMEOUT => 0,
//                CURLOPT_FOLLOWLOCATION => true,
//                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                CURLOPT_CUSTOMREQUEST => 'POST',
//                CURLOPT_POSTFIELDS =>$fields,
//                CURLOPT_HTTPHEADER => array(
//                    'Accept: application/json',
//                    'Content-Type: application/json',
//                    'Public-Merchant-Id:'.$public_merchant_id
//                ),
//            ));
//            $response = curl_exec($curl);
//
//            curl_close($curl);
//            $response = json_decode($response,true);
//            $data = [
//                'type'=>'Token',
//                'data'=>$response
//            ];
//            \Log::channel('kushki_api')->emergency($data);
//// p($response);
//            if(isset($response['error']) && !empty($response['error']['message']))
//            {
//                throw new Exception($response['error']['message']);
//            }
//
//            if(!isset($response['token']) && !empty($response['message']))
//            {
//                throw new Exception($response['message']);
//            }
//
//            // create charge
//            //$capture = in_array($request->calling_from,['TAXI','HANDYMAN']) ? false : true;
//
//            $fields_string = [
//                'amount'=>[
//                    'iva'=>0,
//                    'subtotalIva'=>0,
//                    'subtotalIva0'=>$amount,
//
//                ],
//                'token'=>$response['token'],
//            ];
//
//            $fields_string = json_encode($fields_string);
//            // p($fields_string);
//            //$url = "https://scl.clover.com/v1/charges";
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_VERBOSE, true);
//            curl_setopt($ch, CURLOPT_URL, $url);
//            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($ch, CURLOPT_HTTPHEADER,
//                array('Accept: application/json',
//                    'Content-Type: application/json',
//                    'Private-Merchant-Id:'.$private_merchant_id//3d89d8fd-464e-d126-9f46-3d6b8948caf8
//                )
//            );
//
//            $response1 = curl_exec($ch);
//            curl_close($ch);
//
//            $response1 = json_decode($response1,true);
//            // p($response1);
//            $data = [
//                'type'=>'Charges',
//                'data'=>$response1
//            ];
//            \Log::channel('kushki_api')->emergency($data);
//            // return error
//            if(isset($response1['error']) && !empty($response1['error']['message']))
//            {
//                throw new Exception($response1['error']['message']);
//            }
//
//
//            $status_url = $status_url.$response['token'];
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_VERBOSE, true);
//            curl_setopt($ch, CURLOPT_URL, $status_url);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//            curl_setopt($ch, CURLOPT_HTTPHEADER,
//                array('Accept: application/json',
//                    'Content-Type: application/json',
//                    'Private-Merchant-Id:'.$private_merchant_id//3d89d8fd-464e-d126-9f46-3d6b8948caf8
//                )
//            );
//
//            $response2 = curl_exec($ch);
//            curl_close($ch);
//
//            $response2 = json_decode($response2,true);
//// p($response2);
////            if(isset($response2['error']) && !empty($response2['error']['message']))
////            {
////                throw new Exception($response2['error']['message']);
////            }
//            // enter data
//            DB::table('transactions')->insert([
//                'status' => 1, // for user
//                'card_id' => NULL,
//                'user_id' => $calling_from == "USER" ? $id : NULL,
//                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
//                'merchant_id' => $merchant_id,
//                'payment_option_id' => $payment_option_config->payment_option_id,
//                'checkout_id' => NULL,
//                'booking_id' => $request->booking_id ? $request->booking_id : NULL,
//                'order_id' => $request->order_id ? $request->order_id : NULL,
//                'handyman_order_id' => $request->handyman_order_id ? $request->handyman_order_id : NULL,
//                'payment_transaction_id' => $transaction_id,
//                'payment_transaction' => json_encode($response1),
//                'reference_id' => $response2['ticketNumber'], // payment reference id
//                'amount' => $amount, // amount
//                'request_status' => 1,
//                'status_message' => "success",
//            ]);
//
//            return ['url'=>$response1['redirectUrl'],'call_back_url'=>$call_back_url]; // redirect url
//
//        }catch(\Exception $e)
//        {
//            throw new Exception($e->getMessage());
//        }
//    }
//
//    public function cardStatus(Request $request)
//    {
//        $request_response = $request->all();
//
//        $status_url = "https://api-uat.kushkipagos.com/card-async/v1/status/".$request_response['token'];
//        $private_merchant_id = "5e990516d9964fa3bfe3a638f6a18156";
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_VERBOSE, true);
//        curl_setopt($ch, CURLOPT_URL, $status_url);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//        curl_setopt($ch, CURLOPT_HTTPHEADER,
//            array('Accept: application/json',
//                'Content-Type: application/json',
//                'Private-Merchant-Id:'.$private_merchant_id//3d89d8fd-464e-d126-9f46-3d6b8948caf8
//            )
//        );
//
//        $response2 = curl_exec($ch);
//        curl_close($ch);
//
//        $response2 = json_decode($response2,true);
//        // p($response2);
//        $data = [
//            'type'=>'Card Sync Status',
//            'data'=>$response2
//        ];
//        \Log::channel('kushki_api')->emergency($data);
//
//        $transaction_table =  DB::table("transactions")->where('reference_id',$response2['ticketNumber'])->first();
//        if(isset($response2['status']) && $response2['status'] == "approvedTransaction")
//        {
//            DB::table("transactions")->where('reference_id',$response2['ticketNumber'])->update(['request_status' =>2,'status_message'=>$response2['status']]);
//
//
//            // credit user wallet & and sed notification to user for payment success
//// p($transaction_table->merchant_id);
//            if(!empty($transaction_table->user_id))
//            {
//                $paramArray = [
//                    'amount'=> $transaction_table->amount,
//                    'user_id'=> $transaction_table->user_id,
//                    'narration'=> 2,
//                ];
//                WalletTransaction::UserWalletCredit($paramArray);
//
//                // payment done notification
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_done");
//                $title =trans("$string_file.payment_success");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['user_id'] = $transaction_table->user_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::UserPushMessage($arr_param);
//
//            }
//            elseif(!empty($transaction_table->driver_id))
//            {
//
//
//                $paramArray = array(
//                    'merchant_id' => $transaction_table->merchant_id,
//                    'driver_id' => $transaction_table->driver_id,
//                    'amount' => $transaction_table->amount,
//                    'narration' => 2,
//                    'platform' => 1,
//                    'payment_method' => 4,
//                );
//                WalletTransaction::WalletCredit($paramArray);
//
//
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_done");
//                $title =trans("$string_file.payment_success");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['driver_id'] = $transaction_table->driver_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::DriverPushMessage($arr_param);
//
//            }
//
//
//        }
//        else
//        {
//            if(!empty($transaction_table->user_id))
//            {
//
//                // payment done notification
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_failed");
//                $title =trans("$string_file.payment_failed_title");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['user_id'] = $transaction_table->user_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::UserPushMessage($arr_param);
//
//            }
//            elseif(!empty($transaction_table->driver_id))
//            {
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_failed");
//                $title =trans("$string_file.payment_failed_title");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['driver_id'] = $transaction_table->driver_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::DriverPushMessage($arr_param);
//
//            }
//        }
//    }
//
//    // b) create token for transfer in
//    public function transferInRequest($request,$payment_option_config,$calling_from){
//
//        try {
//            // check whether gateway is on sandbox or live
//            $status_url = "https://api-uat.kushkipagos.com/transfer/v1/status/";
//            $url = "https://api-uat.kushkipagos.com/transfer/v1/init";
//            $token_url = "https://api-uat.kushkipagos.com/transfer/v1/tokens";
//            $private_merchant_id = "5e990516d9964fa3bfe3a638f6a18156";
//            $public_merchant_id = "4951ab3a025c43d2b978edbbc7089130";
//            if($payment_option_config->gateway_condition == 1)
//            {
//                $url = "https://scl.clover.com/v1/charges";
//                $token_url = "https://token.clover.com/v1/tokens";
//                $private_merchant_id = $payment_option_config->api_secret_key; // Private access token
//                $public_merchant_id = $payment_option_config->api_public_key; // public access token
//                $status_url = "https://api-uat.kushkipagos.com/transfer/v1/status/";
//            }
//
//            // check whether request is from driver or user
//            if($calling_from == "DRIVER")
//            {
//                $driver = $request->user('api-driver');
//                $code = $driver->Country->phonecode;
//                $country = $driver->Country;
//                $country_name = $country->CountryName;
//                $currency = "CLP";//$driver->Country->isoCode;
//                $phone_number = $driver->phoneNumber;
//                $logged_user = $driver;
//                $user_merchant_id = $driver->driver_merchant_id;
//                $first_name = $driver->first_name;
//                $last_name = $driver->last_name;
//                $email = $driver->email;
//                $id = $driver->id;
//                $merchant_id = $driver->merchant_id;
//                $description = "driver wallet topup";
//            }
//            else
//            {
//                $user = $request->user('api');
//                $code = $user->Country->phonecode;
//                $country = $user->Country;
//                $country_name = $country->CountryName;
//                $currency = "CLP";//$user->Country->isoCode;
//                $phone_number = $user->UserPhone;
//                $logged_user = $user;
//                $user_merchant_id = $user->user_merchant_id;
//                $first_name = $user->first_name;
//                $last_name = $user->last_name;
//                $email = $user->email;
//                $id = $user->id;
//                $merchant_id = $user->merchant_id;
//                $description = "payment from user";
//            }
//
//            $transaction_id = $id.'_'.time();
//            $call_back_url = route("kushki.transferin.callback");
//            $amount = (int)$request->amount;
//            $fields = [
//                'amount'=>[
//                    'subtotalIva'=>0,
//                    'subtotalIva0'=>$amount,
//                    'iva'=>0,
//                ],
//                'callbackUrl'=>$call_back_url,
//                'userType'=>"0",
//                'documentType'=>$request->document_type,
//                'documentNumber'=>$request->document_number,
//                'paymentDescription'=>"",
//                'email'=>$email,
//                'currency'=>'CLP',
//                'bankId'=>"",
//            ];
//
//// p($private_merchant_id);
//            $fields = json_encode($fields);
//
//            $curl = curl_init();
//            curl_setopt_array($curl, array(
//                CURLOPT_URL => $token_url,
//                CURLOPT_RETURNTRANSFER => true,
//                CURLOPT_ENCODING => '',
//                CURLOPT_MAXREDIRS => 10,
//                CURLOPT_TIMEOUT => 0,
//                CURLOPT_FOLLOWLOCATION => true,
//                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                CURLOPT_CUSTOMREQUEST => 'POST',
//                CURLOPT_POSTFIELDS =>$fields,
//                CURLOPT_HTTPHEADER => array(
//                    'Accept: application/json',
//                    'Content-Type: application/json',
//                    'Public-Merchant-Id:'.$public_merchant_id
//                ),
//            ));
//            $response = curl_exec($curl);
//// p($response);
//            curl_close($curl);
//            $response = json_decode($response,true);
//            $data = [
//                'type'=>'Token',
//                'data'=>$response
//            ];
//            \Log::channel('kushki_api')->emergency($data);
//
//            if(isset($response['error']) && !empty($response['error']['message']))
//            {
//                throw new Exception($response['error']['message']);
//            }
//
//            // create charge
//            //$capture = in_array($request->calling_from,['TAXI','HANDYMAN']) ? false : true;
//
//            $fields_string = [
//                'token'=>$response['token'],
//                'amount'=>[
//                    'ice'=>0,
//                    'iva'=>0,
//                    'subtotalIva'=>0,
//                    'subtotalIva0'=>$amount,
//                    'ExtraTaxes'=>[
//                        'propina'=>0,
//                        'tasaAeroportuaria'=>0,
//                        'agenciaDeViajes'=>0,
//                        'iac'=>0
//                    ]
//                ],
//            ];
//
//            $fields_string = json_encode($fields_string);
//            //$url = "https://scl.clover.com/v1/charges";
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_VERBOSE, true);
//            curl_setopt($ch, CURLOPT_URL, $url);
//            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($ch, CURLOPT_HTTPHEADER,
//                array('Accept: application/json',
//                    'Content-Type: application/json',
//                    'Private-Merchant-Id:'.$private_merchant_id//3d89d8fd-464e-d126-9f46-3d6b8948caf8
//                )
//            );
//
//            $response1 = curl_exec($ch);
//            curl_close($ch);
//
//            $response1 = json_decode($response1,true);
//            // p($response1);
//            $data = [
//                'type'=>'Init transaction',
//                'data'=>$response1
//            ];
//            \Log::channel('kushki_api')->emergency($data);
//            // return error
//            if(isset($response1['error']) && !empty($response1['error']['message']))
//            {
//                throw new Exception($response1['error']['message']);
//            }
//
//
//
//            // check payment status
//
//            //$url = "https://scl.clover.com/v1/charges";
//            $status_url = $status_url.$response['token'];
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_VERBOSE, true);
//            curl_setopt($ch, CURLOPT_URL, $status_url);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//            curl_setopt($ch, CURLOPT_HTTPHEADER,
//                array('Accept: application/json',
//                    'Content-Type: application/json',
//                    'Private-Merchant-Id:'.$private_merchant_id//3d89d8fd-464e-d126-9f46-3d6b8948caf8
//                )
//            );
//
//            $response2 = curl_exec($ch);
//            curl_close($ch);
//
//            $response2 = json_decode($response2,true);
//
//            if(isset($response2['error']) && !empty($response2['error']['message']))
//            {
//                throw new Exception($response2['error']['message']);
//            }
//
//            // enter data
//            DB::table('transactions')->insert([
//                'status' => 1, // for user
//                'card_id' => NULL,
//                'user_id' => $calling_from == "USER" ? $id : NULL,
//                'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
//                'merchant_id' => $merchant_id,
//                'payment_option_id' => $payment_option_config->payment_option_id,
//                'checkout_id' => NULL,
//                'booking_id' => $request->booking_id ? $request->booking_id : NULL,
//                'order_id' => $request->order_id ? $request->order_id : NULL,
//                'handyman_order_id' => $request->handyman_order_id ? $request->handyman_order_id : NULL,
//                'payment_transaction_id' => $transaction_id,
//                'payment_transaction' => json_encode($response1),
//                'reference_id' => $response2['ticketNumber'], // payment reference id
//                'amount' => $amount, // amount
//                'request_status' => 1,
//                'status_message' => "pending",
//            ]);
//
////            if($response2['status'] == "approvedTransaction")
////            {
////                DB::table("transactions")->where('reference_id',$response2['transactionReference'])->update(['request_status' =>2,'status_message'=>$response2['status']]);
////            }
//
//            return ['url'=>$response1['redirectUrl'],'call_back_url'=>$call_back_url]; // reference_id
//
//        }catch(\Exception $e)
//        {
//            throw new Exception($e->getMessage());
//        }
//    }
//
//    public function transferInCallback(Request $request)
//    {
//        $request_response = $request->all();
//        $private_merchant_id = "5e990516d9964fa3bfe3a638f6a18156";
//        $status_url = "https://api-uat.kushkipagos.com/transfer/v1/status/".$request_response['token'];
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_VERBOSE, true);
//        curl_setopt($ch, CURLOPT_URL, $status_url);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//        curl_setopt($ch, CURLOPT_HTTPHEADER,
//            array('Accept: application/json',
//                'Content-Type: application/json',
//                'Private-Merchant-Id:'.$private_merchant_id//3d89d8fd-464e-d126-9f46-3d6b8948caf8
//            )
//        );
//
//        $response2 = curl_exec($ch);
//        curl_close($ch);
//
//        $response2 = json_decode($response2,true);
//// p($response2);
//        if(isset($response2['error']) && !empty($response2['error']['message']))
//        {
//            throw new Exception($response2['error']['message']);
//        }
//        // credit user wallet & and sed notification to user for payment success
//        $transaction_table =  DB::table("transactions")->where('reference_id',$response2['ticketNumber'])->first();
//
//        if($response2['status'] == "approvedTransaction")
//        {
//            DB::table("transactions")->where('reference_id',$response2['ticketNumber'])->update(['request_status' =>2,'status_message'=>$response2['status']]);
//
//
//            if(!empty($transaction_table->user_id))
//            {
//                $paramArray = [
//                    'amount'=> $transaction_table->amount,
//                    'user_id'=> $transaction_table->user_id,
//                    'narration'=> 2,
//                ];
//                WalletTransaction::UserWalletCredit($paramArray);
//
//                // payment done notification
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_done");
//                $title =trans("$string_file.payment_success");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['user_id'] = $transaction_table->user_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::UserPushMessage($arr_param);
//
//            }
//            elseif(!empty($transaction_table->driver_id))
//            {
//
//
//                $paramArray = array(
//                    'merchant_id' => $transaction_table->merchant_id,
//                    'driver_id' => $transaction_table->driver_id,
//                    'amount' => $transaction_table->amount,
//                    'narration' => 2,
//                    'platform' => 1,
//                    'payment_method' => 4,
//                );
//                WalletTransaction::WalletCredit($paramArray);
//
//
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_done");
//                $title =trans("$string_file.payment_success");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['driver_id'] = $transaction_table->driver_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::DriverPushMessage($arr_param);
//
//            }
//        }
//        else
//        {
//
//            if(!empty($transaction_table->user_id))
//            {
//
//                // payment done notification
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_failed");
//                $title =trans("$string_file.payment_failed_title");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['user_id'] = $transaction_table->user_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::UserPushMessage($arr_param);
//
//            }
//            elseif(!empty($transaction_table->driver_id))
//            {
//                $string_file  = $this->getStringFile($transaction_table->merchant_id);
//                $message = trans("$string_file.payment_failed");
//                $title =trans("$string_file.payment_failed_title");
//                $data['notification_type'] = "PAYMENT_STATUS";
//                $data['segment_data'] = [];
//                $arr_param['driver_id'] = $transaction_table->driver_id;
//                $arr_param['data'] = $data;
//                $arr_param['message'] = $message;
//                $arr_param['merchant_id'] = $transaction_table->merchant_id;
//                $arr_param['title'] = $title; // notification title
//                Onesignal::DriverPushMessage($arr_param);
//
//            }
//
//        }
//
//    }
}