<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 28/3/23
 * Time: 2:29 PM
 */

namespace App\Http\Controllers\PaymentMethods\Hubtel;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class HubtelController extends Controller
{
    use ApiResponseTrait, MerchantTrait;

    public function createOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","HUBTEL_WEBVIEW")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $unique_no = $user->merchant_id."00".strtotime(date("Y-m-d H:i:s"));

            $transaction = new Transaction();
            $transaction->status = ($request->for == "USER") ? 1 : 2; // for user
            $transaction->card_id = NULL;
            $transaction->user_id = ($request->for == "USER") ? $user->id : NULL;
            $transaction->driver_id = ($request->for == "USER") ? NULL : $user->id;
            $transaction->merchant_id = $user->merchant_id;
            $transaction->payment_option_id = $payment_option->id;
            $transaction->checkout_id = NULL;
            $transaction->booking_id = NULL;
            $transaction->order_id = NULL;
            $transaction->handyman_order_id = NULL;
            $transaction->payment_transaction_id = $unique_no;
            $transaction->amount = $request->amount; // amount
            $transaction->request_status =  1; // PENDING
            $transaction->status_message = "PENDING";
            $transaction->save();

            /**
             * Generate Payment URL
             */
            $data = array(
                "totalAmount" => $request->amount,
                "description" => $user->Merchant->BusinessName." Payment",
                "callbackUrl" => route("hubtel-callback",["transaction_id" => $transaction->id]),
                "returnUrl" => route("paymentcomplate"),
                "merchantAccountNumber" => $payment_option_config->auth_token,
                "cancellationUrl" => route("paymentfail"),
                "clientReference" => $unique_no
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://payproxyapi.hubtel.com/items/initiate",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic ".base64_encode("$payment_option_config->api_public_key:$payment_option_config->api_secret_key"),
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);

            if($response['responseCode'] != "0000") {
                $message = isset($response['data'][0]) ? $response['data'][0]['errorMessage'] : $response['status'];
                throw new \Exception($message);
            }

            $transaction->payment_transaction = $response['data']['checkoutId'];
            $transaction->save();

            $data = array(
                "payment_url" => $response['data']['checkoutUrl'],
                "transaction_id" => $transaction->id,
            );
            DB::commit();
            return $this->successResponse(trans("$string_file.success"), $data);
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function callBack(Request $request, $transaction_id){
        $data = $request->all();
        try{
            /*for log*/
            $response = array(
                'type' => $transaction_id,
                'request' => $request->all(),
            );
            \Log::channel('hubtel')->emergency($response);

            if(isset($data['ResponseCode']) && $data['ResponseCode'] == "0000"){
                if($request['Data']['Status'] == "Success"){
                    $transaction = Transaction::find($transaction_id);
                    if(!empty($transaction)){
                        if(!empty($transaction) && $transaction->request_status == 1){
                            if($transaction->status == 1){ // If User
                                $paramArray = array(
                                    'user_id' => $transaction->user_id,
                                    'booking_id' => NULL,
                                    'amount' => $transaction->amount,
                                    'narration' => 2,
                                    'platform' => 2,
                                    'payment_method' => 2,
                                    'receipt' => "Hubtel Transaction",
                                );
                                WalletTransaction::UserWalletCredit($paramArray);
                            }
                            else{
                                $paramArray = array(
                                    'driver_id' => $transaction->driver_id,
                                    'booking_id' => NULL,
                                    'amount' => $transaction->amount,
                                    'narration' => 2,
                                    'platform' => 2,
                                    'payment_method' => 2,
                                    'receipt' => "Hubtel Transaction",
                                );
                                WalletTransaction::WalletCredit($paramArray);
                            }
                            $transaction->request_status =  2; // SUCCESS
                            $transaction->status_message = "SUCCESS";
                            $transaction->save();
                        }else{
                            return $this->successResponse("Success");
                        }
                    }else{
                        return $this->failedResponse("No transaction found.");
                    }
                }else{
                    return $this->failedResponse("Payment Failed");
                }
            }else{
                return $this->failedResponse($data['Status']);
            }
            return $this->successResponse("Success");
        }catch (\Exception $exception){
            /*for log*/
            $response = array(
                'request' => json_encode($data),
                'error' => $exception->getMessage(),
                'detail' => $exception->getTraceAsString(),
            );
            \Log::channel('hubtel')->emergency($response);
            return $this->failedResponse($exception->getMessage());
        }
    }
}
