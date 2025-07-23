<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 9/2/23
 * Time: 5:03 PM
 */

namespace App\Http\Controllers\PaymentMethods\Ebankily;


use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Validator;

class Ebankily
{
    use MerchantTrait, ApiResponseTrait;

    protected $BASE_URL = "https://ebankily.appspot.com";

    public function makePayment(Request $request){
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'phone_number' => 'required',
            'amount' => 'required',
            'passcode' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","EBANKILY")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            /**
             * Generate Authentication Token
             */
            $data = "grant_type=password&username=$payment_option_config->api_public_key&password=$payment_option_config->api_secret_key&client_id=$payment_option_config->auth_token";
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/authentification',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$data,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));
            $auth_response = curl_exec($curl);
            curl_close($curl);

            $auth_response = json_decode($auth_response,true);
            $access_token = "";
            if(isset($auth_response['access_token']) && !empty($auth_response['access_token'])) {
                $access_token = $auth_response['access_token'];
            }else{
                throw new \Exception($auth_response['message']);
            }

            $phone_number = str_replace("+","",$request->phone_number);
            $operation_id = $user->merchant_id."00".strtotime(date("Y-m-d H:i:s"));
            $data = array("clientPhone" => $phone_number, "amount" => $request->amount, "passcode" => $request->passcode, "operationId" => $operation_id, "language" => "EN");

            /**
             * Create Request for Payment
             */
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL."/payment",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    "Authorization: Bearer $access_token"
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);
            // dd($this->BASE_URL."/payment",$data,$response);
            $transaction_id = "";
            if(isset($response['errorCode']) && $response['errorCode'] == 0){
                $transaction_id = $response['transactionId'];
            }else{
                throw new \Exception($response['errorMessage']);
            }

            /**
             * Check the Payment Transaction
             */
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL."/checkTransaction",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode(array("operationId" => $operation_id)),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    "Authorization: Bearer $access_token"
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);
            $transaction_id = "";

            /**
             * Generate a Transaction and Credit the amount in wallet
             */
            $data = [];
            if(isset($response['errorCode']) && $response['errorCode'] == 0){
                $transaction_id = $response['transactionId'];

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
                $transaction->payment_transaction_id = $transaction_id;
                $transaction->payment_transaction = $operation_id;
                $transaction->amount = $request->amount; // amount
                $transaction->request_status =  2; // PENDING
                $transaction->status_message = "SUCCESS";
                $transaction->save();

                $created_at = convertTimeToUSERzone($transaction->created_at, $user->Country->timezone, null, $transaction->Merchant,1);

                $data = array(
                    "payment_transaction_id" => $transaction_id,
                    "operation_id" => $operation_id,
                    "amount" => $request->amount,
                    "payment_date" => $created_at
                );

                $add_money = isset($request->add_money) && $request->add_money == false ? false :true;

                if($add_money){
                    // Transfer the amount
                    if ($transaction->status == 1) { // For User
                        $paramArray = array(
                            'user_id' => $user->id,
                            'booking_id' => NULL,
                            'amount' => $transaction->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => 2,
                            'payment_option_id' => $payment_option->id,
                            'transaction_id' => $transaction_id
                        );
                        WalletTransaction::UserWalletCredit($paramArray);
                    } else {
                        $paramArray = array(
                            'driver_id' => $user->id,
                            'booking_id' => NULL,
                            'amount' => $transaction->amount,
                            'narration' => 2,
                            'platform' => 2,
                            'payment_method' => $payment_option->name,
                            'receipt' => $transaction_id,
                            'transaction_id' => $transaction_id
                        );
                        WalletTransaction::WalletCredit($paramArray);
                    }
                }
            }else{
                throw new \Exception($response['errorMessage']);
            }

            DB::commit();
            return $this->successResponse(trans("$string_file.success"), $data);
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }
}
