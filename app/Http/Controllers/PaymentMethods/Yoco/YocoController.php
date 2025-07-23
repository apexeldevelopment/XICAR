<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 22/3/23
 * Time: 7:16 PM
 */

namespace App\Http\Controllers\PaymentMethods\Yoco;


use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\Transaction;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class YocoController extends Controller
{
    use MerchantTrait, ApiResponseTrait;

    public function makePayment(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'for' => 'required|IN:USER,DRIVER',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user("api-driver");
            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::where("slug","YOCO")->first();
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
            $transaction->payment_transaction_id = NULL;
            $transaction->payment_transaction = NULL;
            $transaction->amount = $request->amount; // amount
            $transaction->request_status =  1; // PENDING
            $transaction->status_message = "PENDING";
            $transaction->save();
            $amount = $request->amount*100;
            $transaction_id = $transaction->id;
            DB::commit();
            $error_url = route("yoco.error");
            $success_url = route("yoco.charge",["transaction_id" => $transaction_id]);
            return view("payment.yoco.index", compact("user","amount", "payment_option_config", "success_url", "error_url"));
        }catch (\Exception $exception){
            DB::rollback();
            return $this->errorResponse($exception->getMessage());
        }
    }

    public function chargeCall(Request $request, $transaction_id, $payment_token){
        try{
            $transaction = Transaction::find($transaction_id);
            $user = ($transaction->status == 1) ? $transaction->User : $transaction->Driver;

            $string_file = $this->getStringFile(null, $user->Merchant);
            $payment_option = PaymentOption::find($transaction->payment_option_id);

            if(empty($payment_option)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }
            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $user->merchant_id, "payment_option_id" => $payment_option->id))->first();
            if(empty($payment_option_config)){
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $param = array(
                "token" => $payment_token,
                "amountInCents" => $transaction->amount*100,
                "currency" => "ZAR"
            );
            $auth_token = base64_encode("$payment_option_config->api_secret_key:");
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://online.yoco.com/v1/charges/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($param),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Basic $auth_token",
                    //'Authorization: Basic c2tfdGVzdF9kYjFkZjYyOWxwZXFLRG05Y2U5NGQxZmE2ZmRhOg==',
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response, true);
            if(isset($response['captureState']) && $response['captureState'] == "captured"){
                $transaction->payment_transaction_id = $payment_token;
                $transaction->payment_transaction = $response['chargeId'];
                $transaction->request_status = 2;
                $transaction->status_message = "SUCCESS";
                $transaction->save();
                return "success";
            }else{
                $transaction->payment_transaction_id = $payment_token;
                $transaction->request_status = 3;
                $transaction->status_message = "ERROR";
                $transaction->save();
                return redirect()->route("yoco.error",["message" => $response['displayMessage']]);
            }
        }catch (\Exception $exception){
            return redirect()->route("yoco.error",["message" => $exception->getMessage()]);
        }
    }

    public function error(Request $request, $message){
        return $message;
    }
}
