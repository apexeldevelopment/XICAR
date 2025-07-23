<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 9/2/23
 * Time: 5:03 PM
 */

namespace App\Http\Controllers\PaymentMethods\Payriff;


use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\DriverCard;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Models\UserCard;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Validator;

class Payriff
{
    use MerchantTrait, ApiResponseTrait;

    protected $BASE_URL = "https://api.payriff.com/api/v2";

    /**
     * Payriff Web View Apis
     */

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
            $payment_option = PaymentOption::where("slug","PAYRIFF")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            /**
             * Generate Payment URL
             */
            $data = array(
                "body" => [
                    "amount" => $request->amount,
                    "approveURL" => route("paymentcomplate"),
                    "cancelURL" => route("paymentfail"),
                    "cardUuid" => "",
                    "currencyType" => "AZN",
                    "declineURL" => route("paymentfail"),
                    "description" => "Taxi Payment",
                    "directPay" => true,
                    "installmentPeriod" => 0,
                    "installmentProductType" => "BIRKART",
                    "language" => "AZ",
                    "senderCardUID" => ""
                ],
                "merchant" => $payment_option_config->api_public_key
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/createOrder',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: $payment_option_config->api_secret_key",
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);

            if($response['code'] != "00000") {
                throw new \Exception($response['message']);
            }

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
            $transaction->payment_transaction_id = $response['payload']['orderId'];
            $transaction->payment_transaction = $response['payload']['sessionId'];
            $transaction->amount = $request->amount; // amount
            $transaction->request_status =  1; // PENDING
            $transaction->status_message = "PENDING";
            $transaction->save();

            $data = array(
                "payment_url" => $response['payload']['paymentUrl'],
                "transaction_id" => $transaction->id,
            );

            DB::commit();
            return $this->successResponse(trans("$string_file.success"), $data);
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function checkPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'for' => 'required|IN:USER,DRIVER'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","PAYRIFF")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $transaction = Transaction::find($request->transaction_id);

            if($transaction->request_status != 1){
                if($transaction->request_status == 2){
                    return $this->successResponse(trans("$string_file.payment_success"));
                }else{
                    return $this->failedResponse(trans("$string_file.payment_failed"));
                }
            }

            /**
             * Generate Payment URL
             */
            $data = array(
                "body" => [
                    "languageType" => "AZ",
                    "orderId" => $transaction->payment_transaction_id,
                    "sessionId" => $transaction->payment_transaction
                ],
                "merchant" => $payment_option_config->api_public_key
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/getStatusOrder',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: $payment_option_config->api_secret_key",
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);
            if($response['code'] != "00000"){
                throw new \Exception($response['Message']);
            }

            if(isset($response['payload']) && $response['payload']['orderStatus'] == "APPROVED"){
                // Update Payment Transaction
                $transaction->request_status = 2;
                $transaction->status_message = "SUCCESS";
                $transaction->save();

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
                        'transaction_id' => $transaction->payment_transaction_id
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
                        'receipt' => $transaction->payment_transaction_id,
                        'transaction_id' => $transaction->payment_transaction_id
                    );
                    WalletTransaction::WalletCredit($paramArray);
                }
            }
            else{
                throw new \Exception($response['payload']['responseDescription']);
            }
            DB::commit();
            return $this->successResponse(trans("$string_file.payment_success"));
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    /************************************************************************************************/
    /**
     * Payriff Save Card Provision Apis
     */

    public function saveCardOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'amount' => 'nullable',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $amount = isset($request->amount) && !empty($request->amount) ? $request->amount : "0.01";
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","PAYRIFF_CARD_SAVE")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

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
            $transaction->save();

            /**
             * Generate Payment URL
             */
            $data = array(
                "body" => [
                    "amount" => $amount,
                    "approveURL" => route("api.payriff.callback",["type" => "success", "transaction_id" => $transaction->id]),
                    "cancelURL" => route("api.payriff.callback",["type" => "cancel", "transaction_id" => $transaction->id]),
                    "declineURL" => route("api.payriff.callback",["type" => "decline", "transaction_id" => $transaction->id]),
                    "currencyType" => "AZN",
                    "description" => "Taxi Payment",
                    "directPay" => true,
                    "installmentPeriod" => 0,
                    "installmentProductType" => "BIRKART",
                    "language" => "AZ",
                ],
                "merchant" => $payment_option_config->api_public_key
            );

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/cardSave',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: $payment_option_config->api_secret_key",
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);

            if($response['code'] != "00000") {
                throw new \Exception($response['message']);
            }

            $transaction->payment_transaction_id = $response['payload']['orderId'];
            $transaction->payment_transaction = $response['payload']['sessionId'];
            $transaction->amount = $amount; // amount
            $transaction->request_status =  1; // PENDING
            $transaction->status_message = "PENDING";
            $transaction->save();

            $data = array(
                "payment_url" => $response['payload']['paymentUrl'],
                "transaction_id" => $transaction->id,
            );

            DB::commit();
            return $this->successResponse(trans("$string_file.success"), $data);
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function callback(Request $request, $type, $transaction_id)
    {
        $data = $request->all();
        try{
            /*for log*/
            $response = array(
                'type' => $type,
                'request' => $request->all(),
            );
            \Log::channel('payriff')->emergency($response);

            if($type == "success"){
                $request = $request->all();

                if($request['payload']['orderStatus'] == "APPROVED"){
//                    p(array("id" => $transaction_id, "payment_transaction" => $request['payload']['sessionId'], "payment_transaction_id" => $request['payload']['orderID']));
                    $transaction = Transaction::where(array("id" => $transaction_id, "payment_transaction" => $request['payload']['sessionId'], "payment_transaction_id" => $request['payload']['orderID']))->first();
//                    p($transaction);
                    if(!empty($transaction) && $transaction->request_status == 1){
                        if($transaction->status == 1){ // If User
                            UserCard::create([
                                'user_id' => $transaction->user_id,
                                'token' => $request['payload']['cardRegistration']['CardUID'],
                                'payment_option_id' => $transaction->payment_option_id,
                                'card_number' => $request['payload']['cardRegistration']['MaskedPAN'],
                                'card_type' => $request['payload']['cardRegistration']['Brand'],
                                'exp_month' => "",
                                'exp_year' => ""
                            ]);
                        }else{
                            DriverCard::create([
                                'driver_id' => $transaction->driver_id,
                                'token' => $request['payload']['cardRegistration']['CardUID'],
                                'payment_option_id' => $transaction->payment_option_id,
                                'card_number' => $request['payload']['cardRegistration']['MaskedPAN'],
                                'card_type' => $request['payload']['cardRegistration']['Brand'],
                                'exp_month' => null,
                                'exp_year' => null
                            ]);
                        }
                        $transaction->request_status =  2; // SUCCESS
                        $transaction->status_message = "SUCCESS";
                        $transaction->save();
                    }else{
                        return $this->failedResponse("No transaction found.");
                    }
                }else{
                    return $this->failedResponse("Transaction Not Completed");
                }
            }else{
                return $this->failedResponse($type);
            }
            return $this->successResponse("success");
        }catch (\Exception $exception){
            /*for log*/
            $response = array(
                'request' => $data,
                'error' => $exception->getMessage(),
                'detail' => $exception->getTraceAsString(),
            );
            \Log::channel('payriff')->emergency($response);
            return $this->failedResponse($exception->getMessage());
        }
    }

    /**
     * @param array $params => (type : USER/DRIVER, amount:, card_id:, merchant_id:, string_file:,) - > optional (checkout_id, booking_id, order_id, handyman_order_id)
     * @return \Illuminate\Http\JsonResponse
     */
    public function cardPayment(array $params){
        DB::beginTransaction();
        try{
            $string_file = $params['string_file'];
            $card = $params['type'] == "USER" ? UserCard::find($params['card_id']) : DriverCard::find($params['card_id']);

            if(empty($card)){
                throw new \Exception(trans("$string_file.card_not_found"));
            }

            $payment_option = PaymentOption::where("slug","PAYRIFF_CARD_SAVE")->first();
            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $params['merchant_id'], "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            /**
             * Generate Payment URL
             */
            $data = array(
                "body" => [
                    "amount" => $params['amount'],
                    "approveURL" => "",
                    "cancelURL" => "",
                    "declineURL" => "",
                    "cardUuid" => $card->token,
                    "currencyType" => "AZN",
                    "description" => "Taxi Payment",
                    "directPay" => true,
                    "installmentPeriod" => 0,
                    "installmentProductType" => "BIRKART",
                    "language" => "AZ",
                ],
                "merchant" => $payment_option_config->api_public_key
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/createOrder',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: $payment_option_config->api_secret_key",
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response,true);
            // p($response);

            if($response['code'] != "00000") {
                return false;
            }

            /**
             * Auto Payment
             */
            $auth_pay_data = array(
                "body" => [
                    "amount" => $params['amount'],
                    "cardUuid" => $card->token,
                    "description" => "Taxi Payment",
                    "orderId" => $response['payload']['orderId'],
                    "sessionId" => $response['payload']['sessionId'],
                ],
                "merchant" => $payment_option_config->api_public_key
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL.'/autoPay',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($auth_pay_data),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: $payment_option_config->api_secret_key",
                    'Content-Type: application/json'
                ),
            ));
            $auth_pay_response = curl_exec($curl);
            curl_close($curl);

            $auth_pay_response = json_decode($auth_pay_response,true);

            if($auth_pay_response['code'] != "00000" && $auth_pay_response['payload']['paymentStatus'] == "APPROVED") {
                return false;
            }

            $transaction = new Transaction();
            $transaction->status = ($params['type'] == "USER") ? 1 : 2; // for user
            $transaction->card_id = ($params['type'] == "USER") ? $card->user_id : $card->driver_id; // for user
            $transaction->user_id = ($params['type'] == "USER") ? $card->user_id : NULL; // for user
            $transaction->driver_id = ($params['type'] == "USER") ? NULL : $card->driver_id; // for user
            $transaction->merchant_id = $params['merchant_id'];
            $transaction->payment_option_id = $card->payment_option_id;
            $transaction->checkout_id = isset($params['checkout_id']) ? $params['checkout_id'] : NULL;
            $transaction->booking_id = isset($params['booking_id']) ? $params['booking_id'] : NULL;
            $transaction->order_id = isset($params['order_id']) ? $params['order_id'] : NULL;
            $transaction->handyman_order_id = isset($params['handyman_order_id']) ? $params['handyman_order_id'] : NULL;
            $transaction->payment_transaction_id = $response['payload']['orderId'];
            $transaction->payment_transaction = $response['payload']['sessionId'];
            $transaction->amount = $params['amount']; // amount
            $transaction->request_status =  2; // SUCCESS
            $transaction->status_message = "SUCCESS";
            $transaction->save();

            DB::commit();
            return true;
        }catch (\Exception $exception){
            DB::rollback();
            // p($exception->getMessage());
            throw new \Exception($exception->getMessage());
        }
    }
}
