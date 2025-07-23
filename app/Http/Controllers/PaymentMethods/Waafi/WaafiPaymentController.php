<?php

namespace App\Http\Controllers\PaymentMethods\Waafi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use App\Traits\ContentTrait;

class WaafiPaymentController extends Controller
{
    use ApiResponseTrait, MerchantTrait, ContentTrait;

    public function __construct()
    { }

    // waafi payment option
    public function waafiPayRequest($request, $payment_option_config, $calling_from)
    {

        // p('in');
        try {
            
            // check whether request is from driver or user
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $code = $driver->Country->phonecode;
                $country = $driver->Country;
                $country_name = $country->CountryName;
                $currency = $driver->Country->isoCode;
                $phone_number = str_replace($code, "", $driver->phoneNumber);
                $logged_user = $driver;
                $user_merchant_id = $driver->driver_merchant_id;
                $first_name = $driver->first_name;
                $last_name = $driver->last_name;
                $email = $driver->email;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
                $status = 2;
            } else {
                $user = $request->user('api');
                $code = $user->Country->phonecode;
                $country = $user->Country;
                $country_name = $country->CountryName;
                $currency = $user->Country->isoCode;
                $phone_number = str_replace($code, "", $user->UserPhone);
                $logged_user = $user;
                $user_merchant_id = $user->user_merchant_id;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $email = $user->email;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
                $status = 1;
            }
            
            if ($payment_option_config->gateway_condition == 1) {
                // $url = "https://secure.waafi.com/request.php";
                $url = "https://api.waafipay.net/asm";
                // $url = "https://pg.waafipay.com/asm";
                $public_key = $payment_option_config->api_public_key; //hpp Key
                $secret_key = $payment_option_config->api_secret_key; //"merchantUid"
                $store_id = $payment_option_config->auth_token; //api user id//store id
                
                // dd($url,$public_key,$secret_key,$store_id);
                
                
            }else{
                $url = "https://sandbox.waafipay.net/asm";
                $public_key = $payment_option_config->api_public_key; //hpp Key
                $secret_key = $payment_option_config->api_secret_key; //"merchantUid"
                $store_id = $payment_option_config->auth_token; //api user id//store id
            }
            
            $transaction_id = $id . '_' . time();
            $success_url = route("waafi-success");
            $fail_url = route("waafi-fail");
            
            $fields = [
                "schemaVersion" => "1.0",
                "requestId" => "R" . $transaction_id,
                "timestamp" => time(),
                "channelName" => "WEB",
                "serviceName" => "API_PREAUTHORIZE",
                "serviceParams" => [
                    "apiUserId" => $store_id,//"1000238",
                    "apiKey" => $public_key,//"HPP-961814494",
                    "merchantUid" => $secret_key,//"M0912255",
                    "paymentMethod" => "MWALLET_ACCOUNT",
                    "payerInfo" => [
                        "accountNo" => $request->phone
                    ],
                    "transactionInfo" => [
                        "referenceId" => $transaction_id,
                        "invoiceId" => $id,
                        "amount" => (float) $request->amount,
                        "currency" => "USD", //$currency,
                        "description" => "Test initiate payment",
                        "paymentBrand" => "WAAFI / ZAAD / SAHAL / EVCPLUS / VISA / MASTERCARD",
                        "transactionCategory" => "ECOMMERCE / AIRLINE/ APPOINTMENTS"
                    ],
                    "billto" => [
                        "id" => $id,
                        "name" => $first_name . ' ' . $last_name,
                        "phone" => $phone_number
                    ],
                    "shipTo" => [
                        "id" => $id,
                        "name" => $first_name . ' ' . $last_name,
                        "phone" => $phone_number
                    ],
                    "items" => [
                        [
                            "id" => "454",
                            "merchantItemId" => "454",
                            "description" => $description,
                            "name" => "Order",
                            "type" => "PHYSICAL/ DIGITAL / MIXED",
                            "sku" => "EERF123",
                            "quantity" => 1,
                            "price" => $request->amount
                        ]
                    ]
                ]

            ];
            

            $fields_string = json_encode($fields);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $fields_string,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            // dd($url,$fields,$response);
            $response = json_decode($response, true);
            curl_close($curl);
            
            // p($response);
            \Log::channel('waafi_api')->emergency($response);
            $arr = [
                'request_response' => $response,
                'callback_response' => []
            ];
            // $url_forward = str_replace('"', '', stripslashes($response));

            if ($response['responseMsg'] == 'RCS_SUCCESS' && $response['responseCode'] == 2001 && isset($response['params']) && $response['params']['state'] == "APPROVED") {
                \Log::channel('waafi_api')->emergency($response);
                DB::table('transactions')->insert([
                    'status' => $status, // for user
                    'reference_id' => $response['params']['referenceId'],
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
                    'payment_transaction' => json_encode($arr), // store token
                    'request_status' => 1,
                ]);
                
                
                $commitResponse = $this->PreAuthorizeCommit($url,$transaction_id,$public_key,$secret_key,$store_id,$response['params']['referenceId'],$response['params']['transactionId']);
                // dd($commitResponse);
                $data = [
                    'transaction_status'=> $commitResponse['responseMsg'],
                    'transaction_id' => $transaction_id,
                    'success_url' => $success_url,
                    'fail_url' => $fail_url,
                    'payment_status'=> (isset($commitResponse['params']) && isset($commitResponse['params']['description']) && $commitResponse['params']['description'] == "success") ? 2 : 3
                ];
                
            } else {
                \Log::channel('waafi_api')->emergency($response);
                if(isset($response['errorCode'])){
                    DB::table('transactions')->insert([
                        'status' => $status, // for user
                        'reference_id' => isset($response['params']) && isset($response['params']['referenceId']) ? $response['params']['referenceId'] : '' ,
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
                        'payment_transaction' => json_encode($arr),
                        'request_status' => 3,
                    ]);
                    
                }
                
                $data = [
                        'transaction_status'=> $response['responseMsg'],
                        'transaction_id' => $transaction_id,
                        'success_url' => $success_url,
                        'fail_url' => $fail_url,
                        'payment_status'=> 3
                ];
                
                // throw new \Exception($response['responseMsg']);
            }
            
            return $data;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
    
    public function PreAuthorizeCommit($url,$transaction_id,$public_key,$secret_key,$store_id,$refId,$transId){
            $fields = [
               "schemaVersion" => "1.0",
                "requestId" => "R" . $transaction_id,
                "timestamp" => time(),
                "channelName" => "WEB",
                "serviceName" => "API_PREAUTHORIZE_COMMIT",
                "serviceParams" => [
                    "apiUserId" => $store_id,
                    "apiKey" => $public_key,
                    "merchantUid" => $secret_key,
                    "transactionId"=>$transId,
                    "description"=>"PREAUTH Complete",
                    "referenceId"=>$refId
                ]
              ];
              
              $fields_string = json_encode($fields);
              $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $fields_string,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            $response = json_decode($response, true);
            curl_close($curl);
            
            \Log::channel('waafi_api')->emergency($response);
            $arr = [
                'request_response' => $response,
                'callback_response' => []
            ];
            
            if ($response['responseMsg'] == 'RCS_SUCCESS' && $response['responseCode'] == 2001 && isset($response['params']) && $response['params']['description'] == "success") {
                DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update(['request_status' => 2,'reference_id' => $response['params']['referenceId'], 'payment_transaction' => json_encode($arr)]);
                
                // $receipt = "Application : " . $data['transaction']['id'];
                // $paramArray = array(
                //     'booking_id' => NULL,
                //     'amount' => $trans->amount,
                //     'narration' => 2,
                //     'platform' => 2,
                //     'payment_method' => 2,
                //     'receipt' => $receipt,
                //     'transaction_id' => $data['transaction']['id'],
                // );

                // if($trans->status == 1){
                //     $paramArray['user_id'] = $trans->user_id;
                //     WalletTransaction::UserWalletCredit($paramArray);
                // }elseif($trans->status == 2){
                //     $paramArray['driver_id'] = $trans->driver_id;
                //     WalletTransaction::WalletCredit($paramArray);
                // }else{
                //     if(!empty($trans->booking_id)){
                //         $booking = Booking::find($trans->booking_id);
                //         $booking->payment_status = 1;
                //         $booking->save();
                //         $string_file = $this->getStringFile(NULL, $booking->Merchant);
                //         $title = trans("$string_file.payment_success");
                //         $message = trans("$string_file.payment_done");
                //         $data['notification_type'] = 'PAYMENT_COMPLETE';
                //         $data['segment_type'] = $booking->Segment->slag;
                //         $data['segment_data'] = ['id'=>$booking->id,'handyman_order_id'=>NULL,'order_id'=> NULL];
                //         $arr_param = ['data' => $data, 'message' => $message, 'merchant_id' => $booking->merchant_id, 'title' => $title, 'large_icon' => ""];
                //         $user_param = $arr_param;
                //         $user_param['user_id'] = $booking->user_id;
                //         Onesignal::UserPushMessage($user_param);
                //         $driver_param = $arr_param;
                //         $driver_param['driver_id'] = $booking->driver_id;
                //         Onesignal::DriverPushMessage($driver_param);
                //     }
                //     elseif(!empty($trans->order_id)){
                //         $order = Order::find($trans->order_id);
                //         $order->payment_status = 1;
                //         $order->save();
                //         $string_file = $this->getStringFile(NULL, $order->Merchant);
                //         $title = trans("$string_file.payment_success");
                //         $message = trans("$string_file.payment_done");
                //         $data['notification_type'] = 'PAYMENT_COMPLETE';
                //         $data['segment_type'] = $order->Segment->slag;
                //         $data['segment_data'] = ['id'=>NULL,'handyman_order_id'=>NULL,'order_id'=> $order->id];
                //         $arr_param = ['data' => $data, 'message' => $message, 'merchant_id' => $order->merchant_id, 'title' => $title, 'large_icon' => ""];
                //         $user_param = $arr_param;
                //         $user_param['user_id'] = $order->user_id;
                //         Onesignal::UserPushMessage($user_param);
                //         $driver_param = $arr_param;
                //         $driver_param['driver_id'] = $order->driver_id;
                //         Onesignal::DriverPushMessage($driver_param);
                        
                //     }
                //     else{
                //         $handymanOrder = HandymanOrder::find($trans->handyman_order_id);
                //         $handymanOrder->payment_status = 1;
                //         $handymanOrder->save();
                //          $string_file = $this->getStringFile(NULL, $handymanOrder->Merchant);
                //         $title = trans("$string_file.payment_success");
                //         $message = trans("$string_file.payment_done");
                //         $data['notification_type'] = 'PAYMENT_COMPLETE';
                //         $data['segment_type'] = $handymanOrder->Segment->slag;
                //         $data['segment_data'] = ['id'=>NULL,'handyman_order_id'=>$handymanOrder->id,'order_id'=> NULL];
                //         $arr_param = ['data' => $data, 'message' => $message, 'merchant_id' => $handymanOrder->merchant_id, 'title' => $title, 'large_icon' => ""];
                //         $user_param = $arr_param;
                //         $user_param['user_id'] = $handymanOrder->user_id;
                //         Onesignal::UserPushMessage($user_param);
                //         $driver_param = $arr_param;
                //         $driver_param['driver_id'] = $handymanOrder->driver_id;
                //         Onesignal::DriverPushMessage($driver_param);
                //     }
                // }
            }
            else{
                DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update(['request_status' => 3,'reference_id' => isset($response['params']) ? $response['params']['referenceId'] : '', 'payment_transaction' => json_encode($arr)]);
            }
            
            return $response;
               
    }

    public function successCallBack(Request $request)
    {
        $data = $request->all();
        \Log::channel('waafi_api')->emergency($data);
        echo '<h4>SUCCESS</h4>';
    }

    public function failCallBack(Request $request)
    {
        $data = $request->all();
        \Log::channel('waafi_api')->emergency($data);
        echo '<h4>FAIL</h4>';
    }

    // check payment status
    public function paymentStatus(Request $request)
    {
        $tx_reference = $request->transaction_id; // order id
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $tx_reference)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        $data = [];
        if($transaction_table->request_status == 1)
        {
            $request_status_text = "processing";
            $transaction_status = 1;
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }
        else if($transaction_table->request_status == 2)
        {
            $request_status_text = "success";
            $transaction_status = 2;
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
        }
        else
        {
            $request_status_text = "failed";
            $transaction_status = 3;
            $data = ['payment_status' => $payment_status, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
            
        }
        return $data;
    }
}
