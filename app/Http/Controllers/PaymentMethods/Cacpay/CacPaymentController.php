<?php

namespace App\Http\Controllers\PaymentMethods\Cacpay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Transaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App;

class CacPaymentController extends Controller
{
    //
    use ApiResponseTrait, MerchantTrait;
    public function getPaymentConfig($merchant_id)
    {
        $payment_option = PaymentOption::where('slug', 'CACPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }


    public function signIn($username, $password)
    {
        try {
            $data = ["username" => $username, "password" => $password];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => '196.201.197.61:8080/pay/paymentapi/auth/signin',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response, true);
            if (isset($res['accessToken'])) {
                return $res['accessToken'];
            } else {
                return '';
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function initiatePaymentRequest($request, $paymentConfig, $calling_from)
    {
        try {
            $status = 3;
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $status = 2;
            } elseif ($calling_from == "USER") {
                $user = $request->user('api');
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $status = 1;
            }


            $username = $paymentConfig->api_public_key;
            $password = $paymentConfig->api_secret_key;
            $app_key = $paymentConfig->operator;
            $api_key = $paymentConfig->auth_token;
            $company_services_id = $paymentConfig->additional_data;

            $token = $this->signIn($username, $password);
            if (empty($token)) {
                throw new \Exception('Token not generated');
            }

            $reference = "ref" . "-" . $merchant_id . "-" . $id . "-" . time();

            $data = [
                "app_key" => $app_key,
                "api_key" => $api_key,
                "customer_mobile" => $request->recipient,
                "currency" => "DJF",
                "vender_ref" => $reference,
                "amount" => (float)$request->amount,
                "company_services_id" => $company_services_id
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "196.201.197.61:8080/pay/paymentapi/PaymentInitiateRequest",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ),
            ));


            $response = json_decode(curl_exec($curl), true);
            curl_close($curl);

            if (isset($response['paymentRequestId'])) {
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'status' => $status,
                    'merchant_id' => $merchant_id,
                    'reference_id' => $reference, // got reference if from api as a reference that payment is initiated and waiting for otp
                    'payment_transaction_id'=>$response['paymentRequestId'],
                    'amount' => $request->amount,
                    'payment_option_id' => $paymentConfig->payment_option_id,
                    'request_status' => 1,
                    'status_message' => 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();
                return $response;
            }
            else{
                return $response;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
            'reference_id' => 'required',
        ], [
            'otp' => 'otp is required',
            'reference_id' => 'reference_id is required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $transaction = Transaction::where("payment_transaction_id", $request->reference_id)->first();
        if (!isset($transaction)) {
            return $this->failedResponse("No Transaction found for this reference id !");
        }
        
        try {
            $config = $this->getPaymentConfig($transaction->merchant_id);
            $token = $this->signIn($config->api_public_key, $config->api_secret_key);
            
            $data =[
                    "app_key"=>$config->operator,
                    "api_key"=>$config->auth_token,
                    "payment_request_id"=>$request->reference_id,
                    "otp"=>(string)$request->otp
                ];
                
          
             
             $curl = curl_init();
             curl_setopt_array($curl, array(
                CURLOPT_URL => "196.201.197.61:8080/pay/paymentapi/PaymentConfirmationRequest",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ),
            ));
    
    
            $response = json_decode(curl_exec($curl), true);
            curl_close($curl);
            // dd($response, $data);
            if(isset($response)){
                if(isset($response['description']) && isset($response['reference'])){
                    if( $response['description']=="SUCCESS!"){
                        $transaction->payment_transaction = $response;
                        $transaction->request_status = 2;
                        $transaction->updated_at = date('Y-m-d H:i:s');
                        $transaction->status_message = "SUCCESS";
                        $transaction->save();
                    }
                    else{
                        return $response;
                    }

                }
                else{
                    $transaction->payment_transaction = $response;
                    $transaction->request_status = 3;
                    $transaction->updated_at = date('Y-m-d H:i:s');
                    $transaction->status_message = "FAIL";
                    $transaction->save();
                    return $this->failedResponse($response);
                }
                return $this->successResponse("Success", $response);
            }
            return $this->failedResponse(trans("$string_file.some_thing_went_wrong"));
            
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
    
        public function paymentStatus(Request $request)
        {
            \Log::channel('cacpay')->emergency($request->all());
            $transaction_id = $request->transaction_id; // confirmReference from payment gateway
            $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transaction_id)->first();
            $request_status_text = "failed";
            $payment_status = false;
            if (isset($transaction_table)) {
                $payment_status =   $transaction_table->request_status == 2 ?  true : false;
                if ($transaction_table->request_status == 1) {
                    $request_status_text = "processing";
                } else if ($transaction_table->request_status == 2) {
                    $request_status_text = "success";
                }
            }
            return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
        }
}
