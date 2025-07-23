<?php

namespace App\Http\Controllers\PaymentMethods\BeyonicPayment;

require "beyonic/lib/Beyonic.php";

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;
use App\Models\Onesignal;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Driver;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use DB;

use function GuzzleHttp\json_encode;


class BeyonicController extends Controller
{
    use ApiResponseTrait, MerchantTrait;


    // It's same code of randompayment controller
    // just modified for multi-service code
    public function __construct()
    {
        // uat details
        // request url
        $this->api_secret_key = 'cf5fcd6d70c12517c05e2e2a86b5a63af2e7e0ab';
        // login details
        // https://payments.beyonic.com/users/login/
        // amba@apporio.in //Amba123#

    }

    // Mobile Money Payment
    public function paymentRequest($request, $payment_option_config, $calling_from)
    {
        try {
            
            // check whether request is from driver or user
            if ($calling_from == "DRIVER") {
                $driver = $request->user('api-driver');
                $currency = $driver->Country->isoCode;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
            } else {
                $user = $request->user('api');
                $currency = $user->Country->isoCode;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "user wallet topup";
            }
            $currency = "BXC";
            $is_live = false;
            $api_secret_key = $this->api_secret_key;
            if ($payment_option_config->gateway_condition == 1) {

                $api_secret_key = $payment_option_config->api_secret_key;
                $is_live = true;
                $currency = "CDF";
            }

            \Beyonic::setApiKey($api_secret_key);

            $booking_id = $order_id = $handyman_order_id = NULL;
            if ($request->booking_id) {
                $booking_id = $id = $request->booking_id;
                $description = "Ride Payment";
            } else if ($request->order_id) {
                $order_id = $id = $request->order_id;
                $description = "Order Payment";
            } else if ($request->handyman_order_id) {
                $handyman_order_id = $id = $request->handyman_order_id;
                $description = "Handyman Booking Payment";
            }
        //   p(array(
        //         "phonenumber" => $request->payment_phone_number,
        //         "amount" => $request->amount,
        //         "currency" => $currency,
        //         "metadata" => array("id" => $id, "name" => $description),
        //         "send_instructions" => "True"
        //     ),0);
            $collection_request = \Beyonic_Collection_Request::create(array(
                "phonenumber" => $request->payment_phone_number,
                "amount" => $request->amount,
                "currency" => $currency,
                "metadata" => array("id" => $id, "name" => $description),
                "send_instructions" => "True"
            ));
            
            // p($collection_request);
            
            \Log::channel('beyonic')->emergency(['request_type' => "Payment request", "data" => (array) $collection_request]);
            if (!empty($collection_request->id)) {

                $transaction_id = $collection_request->id;
                DB::table('transactions')->insert([
                    'status' => 1, // for user
                    'reference_id' => NULL,
                    'card_id' => NULL,
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'checkout_id' => NULL,
                    'booking_id' => $booking_id,
                    'order_id' => $order_id,
                    'handyman_order_id' => $handyman_order_id,
                    'payment_transaction_id' => $transaction_id,
                    'payment_transaction' => json_encode((array) $collection_request), // store token
                    'request_status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                return [
                    'status' => 'PROCESSING',
                    'url' => "",
                    'transaction_id' => $transaction_id
                ];
            } else {
                throw new \Exception("Beyonic server is down, please try later");
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function beyonicCallback(Request $request)
    {
        $response = file_get_contents('php://input');
        $resp = json_decode($response, true);
        $res = $resp;
        
        $status = $res['data']['collection_request']['status'];
       // p($status);
        $transaction_id = $res['data']['collection_request']['id'];       
        \Log::channel('beyonic')->emergency(["request_type" => "Callback", "data" => $res]);
        if ($status == "successful") {
            $request_status = 2;
           
        } else if ($status == "processing_started") {
            $request_status = 1;
        } else {
            $request_status = 3;
        }
        if ($request_status == 2 || $request_status == 3) {
            DB::table('transactions')->where('payment_transaction_id', $transaction_id)->update(['request_status' => $request_status, 'payment_transaction' => $response]);
        }
    }
    public function paymentStatus(Request $request)
    {
        $tx_reference = $request->transaction_id; // order id

        $transaction =  DB::table('transactions')->where('payment_transaction_id', $tx_reference)->first();
        $request_status =  $transaction->request_status;
        if ($request_status == 1) {
            $request_status_text = "processing";
            $request_time = strtotime($transaction->created_at);
            $now = time();
            $time_diff = ($now - $request_time);
            if ($time_diff > 180) {
                $request_status = 3; // mark as failed
                $request_status_text = "failed";
                  DB::table('transactions')->where('payment_transaction_id', $tx_reference)->update(['request_status' => $request_status, 'payment_transaction' => NULL]);
            }  
        } else if ($request_status == 2) {
            $request_status_text = "success";
        } else {
            $request_status_text = "failed";
        }
        $payment_status =   $request_status == 2 ?  true : false;
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
    }
}
