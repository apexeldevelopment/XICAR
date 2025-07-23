<?php

namespace App\Http\Controllers\PaymentMethods\Tap;

use App\Http\Controllers\Controller;
use App\Models\DriverCard;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\UserCard;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Driver;

class TapPaymentController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getTapConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'TAP_PAYMENT')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getBaseUrl($env){
        return $env == 1 ? "https://api.tap.company/v2/" :  "https://api.tap.company/v2/";
    }

    public function authorizePayment(Request $request){

        // dd($request->all());
        try{
            $type = $request->calling_from;
            if($type == "DRIVER") {
                $user = $request->user('api-driver');
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $email = $user->email;
                // $phone = $user->UserPhone;
                // $currency = $user->Country->isoCode;
                $countryCode = str_replace('+','',$user->Country->phonecode);
                $id = $user->id;
                $status = 1;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $email = $user->email;
                $status = 2;
                // $phone = $user->phoneNumber;
                // $currency = $user->Country->isoCode;
                $countryCode = str_replace('+','',$user->Country->phonecode);
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            $paymentConfig = $this->getTapConfig($merchant_id);
            $paymentConfig->gateway_condition = 2;
            $pk = $paymentConfig->api_public_key;
            $sk = $paymentConfig->api_secret_key;
            $merchantIdPg = $paymentConfig->auth_token;
            $tokenId = $request->token_id;

            $string_file = $this->getStringFile($merchant_id);
            $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);
            $transactionId = "TRANS_". time();
            $orderId = "ORDER_". time();
            $data = [
                "amount"=> $request->amount,
                "currency"=> $request->currency,
                "customer_initiated"=> "true",
                "threeDSecure"=> true,
                "save_card"=> false,
                "statement_descriptor"=> "sample",
                "metadata"=> [
                    "udf1"=> "test_data_1",
                    "udf2"=> "test_data_2",
                    "udf3"=> "test_data_3"
                ],
                "reference"=> [
                    "transaction"=> $transactionId,
                    "order"=> $orderId
                ],
                "receipt"=> [
                    "email"=> false,
                    "sms"=> false
                ],
                "customer"=> [
                    "first_name"=> $first_name,
                    "middle_name"=> "",
                    "last_name"=> $last_name,
                    "email"=> $email,
                    "phone"=> [
                        "country_code"=> "965",
                        "number"=> $request->phone
                    ]
                ],
                "merchant"=> [
                    "id"=> $merchantIdPg
                ],
                "source"=> [
                    "id"=> $tokenId
                ],
                "authorize_debit"=> false,
                "auto"=> [
                    "type"=> "VOID",
                    "time"=> 100
                ],
                "post"=> [
                    "url"=> route('tap-posturl')
                ],
                "redirect"=> [
                    "url"=> route('tap-payment-redirect')
                ]
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $baseUrl.'authorize/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$sk,
                    'Content-Type: application/json'
                ),
            ));

            $response = json_decode(curl_exec($curl),true);
            curl_close($curl);
            if(isset($response['status']) && $response['status'] == "INITIATED"){
                DB::table('transactions')->insert([
                    'user_id' => $type == "USER" ? $id : NULL,
                    'driver_id' => $type == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $transactionId,
                    'amount' => $request->amount,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'payment_transaction'=> json_encode($response),
                    'reference_id'=> $response['id'],
                    'request_status'=> 1,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }elseif(isset($response['errors'])){
                return ['result'=> 0 ,'response'=> $response['errors'][0]['description']];
            }



            return [
                "url"=> isset($response['transaction']) && isset($response['transaction']['url']) ? $response['transaction']['url'] : "",
                "authorizationToken"=> isset($response['id']) ? $response['id'] : "",
                'success_url' => route('tap-Success'),
                'fail_url' => route('tap-Fail'),
            ];




        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function paymentCallback(Request $request){
        \Log::channel('tap_pay_api')->emergency(['request_callback'=> $request->all()]);
        $data = $request->all();

        if(isset($data['tap_id'])){
            $authId = $data['tap_id'];
            $transaction = Transaction::where('reference_id',$data['tap_id'])->first();
            if($transaction->request_status == 2){
                return redirect(route('tap-Success'));
            }
            elseif($transaction->request_status == 3){
                return redirect(route('tap-Fail'));
            }
        }
    }

    public function TapPostUrl(Request $request){
        \Log::channel('tap_pay_api')->emergency(['request_post_step1'=> $request->all()]);
        $data = $request->all();
        if(isset($data['id']) && isset($data['status'])){
            \Log::channel('tap_pay_api')->emergency(['request_post_step2'=> [$data['id'],$data['status']]]);
            $authId = $data['id'];
            $transaction = Transaction::where('reference_id',$authId)->first();
            $status = $data['status'];
            if($status == "AUTHORIZED"){
                $paymentConfig = $this->getTapConfig($transaction->merchant_id);
                $sk = $paymentConfig->api_secret_key;
                $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition);
                // \Log::channel('tap_pay_api')->emergency(['request_post_step3'=> [$data['id'],$data['merchant']['id'],$data['customer'],$data['amount'],$data['currency'],$data['metadata'],$data['reference']['transaction'],$data['reference']['order'],]]);
                $paramData = [
                    "amount"=> $data['amount'],
                    "currency"=> $data['currency'],
                    "customer_initiated"=> "true",
                    "threeDSecure"=> true,
                    "save_card"=> false,
                    "statement_descriptor"=> "sample",
                    "metadata"=> $data['metadata'],
                    "reference"=> [
                        "transaction"=> $data['reference']['transaction'],
                        "order"=>  $data['reference']['order'],
                    ],
                    "receipt"=> [
                        "email"=> false,
                        "sms"=> false
                    ],
                    "customer"=> [
                        "id"=> $data['customer']['id'],
                        "first_name"=> $data['customer']['first_name'],
                        "middle_name"=> "",
                        "last_name"=> $data['customer']['last_name'],
                        "phone"=> $data['customer']['phone']
                    ],
                    "merchant"=> [
                        "id"=> $data['merchant']['id']
                    ],
                    "source"=> [
                        "id"=> $data['id']
                    ],
                    "authorize_debit"=> false,
                    "auto"=> [
                        "type"=> "VOID",
                        "time"=> 100
                    ],
                    "post"=> [
                        "url"=> route('tap-posturl')
                    ],
                    "redirect"=> [
                        "url"=> route('tap-payment-redirect')
                    ]
                ];
                $userId = $transaction->user_id;
                $driverId = $transaction->driver_id;
                
                if($userId){
                    $user = User::find($userId);
                    $user->tap_user_customer_token = $data['customer']['id'];
                    $user->save();
                }elseif($driverId){
                     $driver = Driver::find($driverId);
                    $driver->tap_driver_customer_token = $data['customer']['id'];
                    $driver->save();
                }
                // \Log::channel('tap_pay_api')->emergency(['request_post_step4'=> $paramData]);
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $baseUrl.'charges/',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS =>json_encode($paramData),
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer '.$sk,
                        'Content-Type: application/json'
                    ),
                ));

                $response = json_decode(curl_exec($curl),true);
                \Log::channel('tap_pay_api')->emergency(['request_post_step5'=> $response]);
                curl_close($curl);

                if(isset($response['status']) && $response['status'] == 'CAPTURED'){
                    DB::table('transactions')->where('reference_id', $authId)->update([
                        'request_status' =>  2 ,
                        'checkout_id'=> $data['customer']['id'],
                        'status_message' =>'SUCCESS',
                        'payment_transaction'=> json_encode($response)
                    ]);
                }else{
                    DB::table('transactions')->where('reference_id', $authId)->update([
                        'request_status' =>  3 ,
                        'checkout_id'=> $data['customer']['id'],
                        'status_message' =>'FAIL',
                        'payment_transaction'=> json_encode($response)
                    ]);
                }


            }


        }
    }

    public function TapSuccess(Request $request)
    {
        \Log::channel('tap_pay_api')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function TapFail(Request $request)
    {
        \Log::channel('tap_pay_api')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }
    
    public function paymentStatus(Request $request){
        $authId = $request->transaction_id;
        $transaction = Transaction::where('reference_id',$authId)->first();
        $paymentConfig = $this->getTapConfig($transaction->merchant_id);
        $sk = $paymentConfig->api_secret_key;
        $baseUrl = $this->getBaseUrl($paymentConfig->gateway_condition); 
        
         $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $baseUrl.'authorize/'.$authId,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer '.$sk,
                        'Content-Type: application/json'
                    ),
                ));

                $response = json_decode(curl_exec($curl),true);
            \Log::channel('tap_pay_api')->emergency(['request_post_step2'=> $response]);
                curl_close($curl);
                if(isset($response['id']) && isset($response['status'])){
                    $authId = $response['id'];
                    $status = $response['status'];
                    if($status == "AUTHORIZED"){
                        $paramData = [
                            "amount"=> $response['amount'],
                            "currency"=> $response['currency'],
                            "customer_initiated"=> "true",
                            "threeDSecure"=> true,
                            "save_card"=> false,
                            "statement_descriptor"=> "sample",
                            "metadata"=> $response['metadata'],
                            "reference"=> [
                                "transaction"=> $response['reference']['transaction'],
                                "order"=>  $response['reference']['order'],
                            ],
                            "receipt"=> [
                                "email"=> false,
                                "sms"=> false
                            ],
                            "customer"=> [
                                "id"=> $response['customer']['id'],
                                "first_name"=> $response['customer']['first_name'],
                                "middle_name"=> "",
                                "last_name"=> $response['customer']['last_name'],
                                "phone"=> $response['customer']['phone']
                            ],
                            "merchant"=> [
                                "id"=> $response['merchant']['id']
                            ],
                            "source"=> [
                                "id"=> $response['id']
                            ],
                            "authorize_debit"=> false,
                            "auto"=> [
                                "type"=> "VOID",
                                "time"=> 100
                            ],
                             "post"=> [
                                "url"=> route('tap-posturl')
                            ],
                            "redirect"=> [
                                "url"=> route('tap-payment-redirect')
                            ]
                        ];
                        \Log::channel('tap_pay_api')->emergency(['request_post_step4'=> $paramData]);
                        $curl = curl_init();
        
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $baseUrl.'charges/',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS =>json_encode($paramData),
                            CURLOPT_HTTPHEADER => array(
                                'Authorization: Bearer '.$sk,
                                'Content-Type: application/json'
                            ),
                        ));
        
                        $responseData = json_decode(curl_exec($curl),true);
                        \Log::channel('tap_pay_api')->emergency(['request_post_step5'=> $responseData]);
                        curl_close($curl);
        
                        if(isset($responseData['status']) && $responseData['status'] == 'CAPTURED'){
                            DB::table('transactions')->where('reference_id', $authId)->update([
                                'request_status' =>  2 ,
                                'checkout_id'=> $response['customer']['id'],
                                'status_message' =>'SUCCESS',
                                'payment_transaction'=> json_encode($responseData)
                            ]);
                        }else{
                            DB::table('transactions')->where('reference_id', $authId)->update([
                                'request_status' =>  3 ,
                                'checkout_id'=> $response['customer']['id'],
                                'status_message' =>'FAIL',
                                'payment_transaction'=> json_encode($responseData)
                            ]);
                        }
        
        
                    }
                    elseif($status == "CAPTURED"){
                        DB::table('transactions')->where('reference_id', $authId)->update([
                                'request_status' =>  2 ,
                                'checkout_id'=> $response['customer']['id'],
                                'status_message' =>'SUCCESS',
                                'payment_transaction'=> json_encode($response)
                        ]);
                    }
                }
                
                $transaction = Transaction::where('reference_id',$authId)->first();
                $payment_status =   $transaction->request_status == 2 ?  true : false;
                        
                        if($transaction->request_status == 1)
                        {
                            $request_status_text = "processing";
                            $transaction_status = 1;
                            $data = ['payment_status' => 1, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                        }
                        else if($transaction->request_status == 2)
                        {
                            $request_status_text = "success";
                            $transaction_status = 2;
                            $data = ['payment_status' => 2, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                        }
                        else
                        {
                            $request_status_text = "failed";
                            $transaction_status = 3;
                            $data = ['payment_status' => 3, 'request_status' => $request_status_text, 'transaction_status' => $transaction_status];
                            
                        }
                        
                        return $data;
                
        
    }


}