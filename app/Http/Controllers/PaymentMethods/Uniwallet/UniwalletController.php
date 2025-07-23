<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 16/12/22
 * Time: 1:15 PM
 */

namespace App\Http\Controllers\PaymentMethods\Uniwallet;


use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use DB;

class UniwalletController
{
    use ApiResponseTrait, MerchantTrait;

    protected $BASE_URL = "";

    protected $STRING_FILE_NAME = "";

    protected $MERCHANT_ID = "";

    protected $PRODUCT_ID = "";

    protected $API_KEY = "";

    protected $PAYMENT_OPTION_ID = "";

    /*
     * Set values for payment gateway api access
     */
    public function setValues($merchant_id)
    {
        $payment_option = PaymentOption::where('slug', 'UNIWALLET')->first();
        $payment_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
        if (!empty($payment_config)) {
            $this->BASE_URL = $payment_config->tokenization_url;
            $this->MERCHANT_ID = $payment_config->api_public_key;
            $this->PRODUCT_ID = $payment_config->auth_token;
            $this->API_KEY = $payment_config->api_secret_key;
            $this->PAYMENT_OPTION_ID = $payment_option->id;
        } else {
            throw new \Exception(trans("$this->STRING_FILE_NAME.configuration_not_found"));
        }
    }

    /*
     * Check Callback response
     */
    public function callback(Request $request)
    {
        $request_response = $request->all();
        $data = [
            'type' => 'Uniwallet - Callback',
            'data' => $request_response
        ];
        \Log::channel('uniwallet_api')->emergency($data);

        // Find Transaction
        $transaction = Transaction::where(["reference_id" => $request_response['refNo']])->first();

        if ($transaction->request_status == 1) { // If transaction status is pending
            if ($request_response['responseCode'] == "01") {
                $transaction->request_status = 2;
                $transaction->payment_transaction = json_encode($request_response);
                $transaction->save();

                if ($transaction->status == 1) {
                    $paramArray = array(
                        'user_id' => $transaction->user_id,
                        'booking_id' => NULL,
                        'amount' => $transaction->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => "Uniwallet Transaction",
                    );
                    WalletTransaction::UserWalletCredit($paramArray);
                } else {
                    $paramArray = array(
                        'driver_id' => $transaction->driver_id,
                        'booking_id' => NULL,
                        'amount' => $transaction->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => "Uniwallet Transaction",
                    );
                    WalletTransaction::WalletCredit($paramArray);
                }
            } elseif ($request_response['responseCode'] == "100") {
                $transaction->payment_transaction = json_encode($response);
                $transaction->save();
            }
            $message = $this->getResponseCodeString($request_response['responseCode']);
            $result = $request_response['responseCode'] == "01" ? '1' : '0';

            $app = array('result' => $result, 'amount' => $transaction->amount, 'message' => $message, 'reference_id' => $transaction->reference_id);
            $title = "Uniwallet Transaction";

            $data = array(
                'notification_type' => 'UNIWALLET',
                'segment_type' => "",
                'segment_data' => $app,
                'notification_gen_time' => time(),
            );

            if ($transaction->status == 1) {
                $large_icon = get_image($transaction->Merchant->BusinessLogo, 'business_logo', $transaction->merchant_id, true);
                $arr_param = ['user_id' => $transaction->user_id, 'data' => $data, 'message' => $message, 'merchant_id' => $transaction->merchant_id, 'title' => $title, 'large_icon' => $large_icon];
                Onesignal::UserPushMessage($arr_param);
            } else {
                $large_icon = get_image($transaction->Merchant->BusinessLogo, 'business_logo', $transaction->merchant_id, true);
                $arr_param = ['driver_id' => $transaction->driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $transaction->merchant_id, 'title' => $title, 'large_icon' => $large_icon];
                Onesignal::DriverPushMessage($arr_param);
            }
        }

        return response()->json(["responseCode" => "01", "responseMessage" => "Callback Successful."], 200);
    }

    /*
     * Get network list
     */
    public function getNetworkList(Request $request, $type)
    {
        try {
            $this->STRING_FILE_NAME = $this->getStringFile($request->merchant_id);

            self::setValues($request->merchant_id);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL . '/uniwallet/get/available/networks',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response);
            foreach ($response as $key => $item){
                if(!in_array($item, ["ARTLTIGO","MTN","VODAFONE"])){
                    unset($response[$key]);
                }
            }
            $response = array_values($response);
            return $this->successResponse(trans("$this->STRING_FILE_NAME.success"), $response);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    /*
     * Check phone number
     */
    public function nameEnquiry(Request $request, $type)
    {
        try {
            $this->STRING_FILE_NAME = $this->getStringFile($request->merchant_id);

            self::setValues($request->merchant_id);

            $validator = Validator::make($request->all(), [
                'phone_number' => 'required',
                'network' => 'required'
            ]);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL . '/uniwallet/validate/account/holder',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(array(
                    "merchantId" => $this->MERCHANT_ID,
                    "productId" => $this->PRODUCT_ID,
                    "apiKey" => $this->API_KEY,
                    "msisdn" => str_replace("+", "", $request->phone_number),
                    "network" => strtoupper($request->network)
                )),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);
            if ($response['responseCode'] != "01") {
                throw new \Exception($response['responseMessage']);
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL . '/uniwallet/name/enquiry',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(array(
                    "merchantId" => $this->MERCHANT_ID,
                    "productId" => $this->PRODUCT_ID,
                    "apiKey" => $this->API_KEY,
                    "msisdn" => str_replace("+", "", $request->phone_number),
                    "network" => strtoupper($request->network)
                )),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);
            if ($response['responseCode'] != "01") {
                throw new \Exception($response['responseMessage']);
            }

            $data = array(
                "name" => $response['name'],
                "phone_number" => $request->phone_number,
                "network" => $request->network,

            );

            return $this->successResponse(trans("$this->STRING_FILE_NAME.success"), $data);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    /*
     * Initiate Transaction
     */
    public function createTransaction(Request $request)
    {
        try {
            $this->STRING_FILE_NAME = $this->getStringFile($request->merchant_id);

            self::setValues($request->merchant_id);

            $validator = Validator::make($request->all(), [
                'phone_number' => 'required',
                'network' => 'required',
                'amount' => 'required',
                'transaction_for' => 'required|IN:USER,DRIVER'
            ]);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }

            $currentDateTime = now(); // Get the current date and time
            // $startTime = $currentDateTime->subMinutes(11); // subtract the specified interval from the current date and time
            
            // Retrieve the latest data from the transaction table with records in the last 11 minutes
            $checkPendingTrans = Transaction::where(["merchant_id" => $request->merchant_id, "payment_option_id" => $this->PAYMENT_OPTION_ID,"request_status"=>1])->latest()->first();
            
            if(!empty($checkPendingTrans)){
                $data = array(
                    "phone_number" => null,
                    "network" => null,
                    "reference_id" => $checkPendingTrans->reference_id,
                    "amount"=> $checkPendingTrans->amount,
                    "transaction_id"=> $checkPendingTrans->payment_transaction_id,
                    "transacton_for"=> $checkPendingTrans->status
                );
            }
            else{

            $user = ($request->transaction_for == "USER") ? $request->user('api') : $request->user('api-driver');

            $reference_id = $request->merchant_id . Carbon::now()->timestamp;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL . '/uniwallet/debit/customer',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(array(
                    "merchantId" => $this->MERCHANT_ID,
                    "productId" => $this->PRODUCT_ID,
                    "apiKey" => $this->API_KEY,
                    "msisdn" => str_replace("+", "", $request->phone_number),
                    "network" => strtoupper($request->network),
                    "refNo" => $reference_id,
                    "amount" => $request->amount,
                    "narration" => "Taxi App Debit " . strtoupper($request->network) . " Customer",
                )),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);

            if ($response['responseCode'] != "03") {
                throw new \Exception($response['responseMessage']);
            }

            $transaction = Transaction::create([
                "merchant_id" => $request->merchant_id,
                "status" => $request->transaction_for == "USER" ? 1 : 2,
                "user_id" => $request->transaction_for == "USER" ? $user->id : NULL,
                "driver_id" => $request->transaction_for == "DRIVER" ? $user->id : NULL,
                "payment_option_id" => $this->PAYMENT_OPTION_ID,
                "amount" => $request->amount,
                "reference_id" => $reference_id,
                "payment_transaction_id" => $response['uniwalletTransactionId'],
                "request_status" => 1 // PENDING
            ]);

            $data = array(
                "phone_number" => $request->phone_number,
                "network" => $request->network,
                "reference_id" => $reference_id
            );
        }

            return $this->successResponse(trans("$this->STRING_FILE_NAME.success"), $data);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    /*
     * Check Transaction Status
     */
    public function checkTransaction(Request $request)
    {
        DB::beginTransaction();
        try {
            $this->STRING_FILE_NAME = $this->getStringFile($request->merchant_id);

            self::setValues($request->merchant_id);

            $validator = Validator::make($request->all(), [
                // 'phone_number' => 'required',
                // 'network' => 'required',
                'reference_id' => 'required',
                'transaction_for' => 'required|IN:USER,DRIVER'
            ]);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }

            if(!empty($request->phone_number) && !empty($request->network)){
                $validator = Validator::make($request->all(), [
                    'phone_number' => 'required',
                    'network' => 'required',
                ]);
                if ($validator->fails()) {
                    $errors = $validator->messages()->all();
                    return $this->failedResponse($errors[0]);
                }
            }

            $user = ($request->transaction_for == "USER") ? $request->user('api') : $request->user('api-driver');
            $u_status = ($request->transaction_for == "USER") ? 1 : 2;

            $transaction = Transaction::where(["merchant_id" => $request->merchant_id, "status" => $u_status, "payment_option_id" => $this->PAYMENT_OPTION_ID, "reference_id" => $request->reference_id])->first();

            if (!empty($transaction)) {
                if ($transaction->request_status == 2) { // If Transaction Already Successfull
                    throw new \Exception(trans("$this->STRING_FILE_NAME.transaction") . " " . trans("$this->STRING_FILE_NAME.success"));
                } elseif ($transaction->request_status == 3) {
                    throw new \Exception(trans("$this->STRING_FILE_NAME.transaction") . " " . trans("$this->STRING_FILE_NAME.failed"));
                }
            } else {
                throw new \Exception(trans("$this->STRING_FILE_NAME.transaction") . " " . trans("$this->STRING_FILE_NAME.not_found"));
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->BASE_URL . '/uniwallet/check/transaction/status/' . $transaction->reference_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(array(
                    "merchantId" => $this->MERCHANT_ID,
                    "productId" => $this->PRODUCT_ID,
                    "apiKey" => $this->API_KEY,
                )),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $response = json_decode($response, true);

            $message = "";
            $success = false;
            if ($response['status'] == "SUCCESSFULL") {
                $transaction->request_status = 2;
                $transaction->payment_transaction = json_encode($response);
                $transaction->save();

                if ($transaction->status == 1) {
                    $paramArray = array(
                        'user_id' => $transaction->user_id,
                        'booking_id' => NULL,
                        'amount' => $transaction->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => "Uniwallet Transaction",
                    );
                    WalletTransaction::UserWalletCredit($paramArray);
                } else {
                    $paramArray = array(
                        'driver_id' => $transaction->driver_id,
                        'booking_id' => NULL,
                        'amount' => $transaction->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => "Uniwallet Transaction",
                    );
                    WalletTransaction::WalletCredit($paramArray);
                }

                $success = true;
                $message = trans("$this->STRING_FILE_NAME.transaction") . " " . trans("$this->STRING_FILE_NAME.successfully") . " " . trans("$this->STRING_FILE_NAME.done");
            } elseif ($response['status'] == "TIMEOUT" || $response['status'] == "FAILURE" || $response['status'] == "NOT FOUND") {
                $transaction->request_status = 3;
                $transaction->payment_transaction = json_encode($response);
                $transaction->save();

                $message = trans("$this->STRING_FILE_NAME.transaction") . " " . trans("$this->STRING_FILE_NAME.failed");
            } else {
                $message = trans("$this->STRING_FILE_NAME.transaction") . " " . trans("$this->STRING_FILE_NAME.processing");
            }

            DB::commit();
            if ($success) {
                return $this->successResponse($message);
            } else {
                return $this->failedResponse($message);
            }
        } catch (\Exception $exception) {
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    /*
     *
     */
    public function getResponseCodeString($response_code)
    {
        $message = "";
        switch ($response_code) {
            case "03":
                $message = "Processing payment";
                break;
            case "01":
                $message = "Payment successful.";
                break;
            case "100":
                $message = "Payment failed.";
                break;
            case "400":
                $message = "Invalid request";
                break;
            case "107":
                $message = "Invalid Credentials";
                break;
            case "112":
                $message = "Service unavailable. Try again later.";
                break;
            case "113":
                $message = "Request timed out";
                break;
            case "121":
                $message = "Not allowed to access this service.";
                break;
            case "400":
                $message = "Insufficient Funds In merchant account";
                break;
            case "110":
                $message = "Duplicate Transaction";
                break;
            case "529":
                $message = "MTN - Transaction will cause wallet limit rule to be violated";
                break;
            case "527":
                $message = "Number is not registered on mobile money";
                break;
            case "515":
                $message = "The MTN msisdn provided is not a registered subscriber";
                break;
            case "682":
                $message = "An internal error caused the operation to fail";
                break;
            case "04":
                $message = "Payment Amount is not in range.";
                break;
            case "779":
                $message = "Some other transactional operation is being performed on the wallet therefore this transaction can not be completed at this time.";
                break;
            case "2058":
                $message = "The vodafone voucher provided is invalid or has expired.";
                break;
            case "1001":
                $message = "The vodafone msisdn specified is not correct.";
                break;
            case "60019":
                $message = "Dear Customer, you have insufficient funds. 5 successive invalid transfers will lock your wallet. Call 100 to locate an agent.|  Customer has insufficient funds.";
                break;
            case "00042":
                $message = "Requested amount not in multiple of allowed value|  The amount specified in the request is not allowed.";
                break;
            case "00068":
                $message = "Dear Customer, the PIN you have entered is incorrect. 5 successive wrong entries will lock your wallet. Please call 100 for help.|  Incorrect PIN.";
                break;
            case "00017":
                $message = "Invalid Pin length|  Invalid Pin length.";
                break;
            case "00210":
                $message = "Dear Customer, your wallet is locked due to 5 successive invalid PIN entry. Please call 100 for help.| Customer wallet is locked.";
                break;
            default:
                $message = "Invalid type of transaction.";
        }
        return $message;
    }
}
