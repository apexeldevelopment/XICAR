<?php

namespace App\Http\Controllers\PaymentMethods\Opay;
use App\Http\Controllers\Controller;
use App\Models\UserCard;
use hisorange\BrowserDetect\Exceptions\Exception;
use Illuminate\Http\Request;
use DB;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use App\Traits\ContentTrait;
use SimpleXMLElement;
use App\Models\Transaction;
use App\Models\DriverCard;


class OpayController extends Controller
{
    use ApiResponseTrait, MerchantTrait, ContentTrait;

    public function __construct()
    {
    }


    // DPO Think Payment


//    Card payment by webview

    public function paymentRequest($request,$payment_option_config,$calling_from){

        try {

            // check whether request is from driver or user
            if($calling_from == "DRIVER")
            {
                $driver = $request->user('api-driver');
                $code = $driver->Country->phonecode;
                $country_code = $driver->Country->country_code;
                $country = $driver->Country;
                $country_name = $country->CountryName;
                $currency = $driver->Country->isoCode;
                $phone_number = $driver->phoneNumber;
                $logged_user = $driver;
                $user_merchant_id = $driver->driver_merchant_id;
                $first_name = $driver->first_name;
                $last_name = $driver->last_name;
                $email = $driver->email;
                $id = $driver->id;
                $merchant_id = $driver->merchant_id;
                $description = "driver wallet topup";
            }
            else
            {
                $user = $request->user('api');
                $code = $user->Country->phonecode;
                $country = $user->Country;
                $country_name = $country->CountryName;
                $currency = $user->Country->isoCode;
                $phone_number = $user->UserPhone;
                $logged_user = $user;
                $user_merchant_id = $user->user_merchant_id;
                $first_name = $user->first_name;
                $last_name = $user->last_name;
                $email = $user->email;
                $id = $user->id;
                $merchant_id = $user->merchant_id;
                $description = "payment from user";
                $country_code = $user->Country->country_code;
            }

            $amount = $request->amount;
            $transaction_id = $request->reference_id;
            $redirect_url =route("opay-callback");
            $tx_reference =  $transaction_id;
            $request_response = $request->all();
            $data = [
                'type'=>'callback notification',
                'data'=>$request_response
            ];
            \Log::channel('opay_payment_api')->emergency($data);

                // enter data
                DB::table('transactions')->insert([
                    'status' => 1, // for user
                    'card_id' => NULL,
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'checkout_id' => NULL,
                    'payment_transaction_id' => $transaction_id,
                    'payment_transaction' => NULL,
                    'reference_id' => $tx_reference, // payment reference id
                    'amount' => $amount, // amount
                    'request_status' => 1,
                    'status_message' => "success",
                ]);


                $web_view_url = "https://secure.3gdirectpay.com/dpopayment.php?ID=".$tx_reference;
                return [
                    'status'=>'NEED_TO_OPEN_WEBVIEW',
                    'redirect_url'=>$redirect_url
                ];



        }catch(\Exception $e)
        {
            throw new Exception($e->getMessage());
        }
    }

    public function PaymentCallBack(Request $request)
    {
        $request_response = $request->all();
        $data = [
            'type'=>'callback notification',
            'data'=>$request_response
        ];
        \Log::channel('opay_payment_api')->emergency($data);


//        if (isset($xml['Result']) &&  $xml['Result'] == 000)
//        {
//            $transaction = Transaction::where('reference_id', $tx_reference)->first();
//            $transaction->request_status  = 2;
//            $transaction->status_message  = "Successful payment";
//            $transaction->save();
//
//
//
//        }
    }


    public function paymentStatus(Request $request)
    {
        $tx_reference = $request->transaction_id; // order id
        $transaction_table =  DB::table("transactions")->where('reference_id',$tx_reference)->first();

//        if($transaction_table->request_status != 2) // payment pending/failed
//        {
//            $status_url  = "https://secure.3gdirectpay.com/API/v6/";
//            $company_token =  "8D3DA73D-9D7F-4E09-96D4-3D44E7A83EA3"; //$payment_option_config->api_secret_key
//
//            $arr_post_data =
//
//        <API3G>
//            <CompanyToken>'.$company_token.'</CompanyToken>
//            <Request>verifyToken</Request>
//            <TransactionToken>'.$tx_reference.'</TransactionToken>
//        </API3G>';
//
//            $curl = curl_init();
//            curl_setopt_array($curl, array(
//                CURLOPT_URL => $status_url,
//                CURLOPT_RETURNTRANSFER => true,
//                CURLOPT_ENCODING => '',
//                CURLOPT_MAXREDIRS => 10,
//                CURLOPT_TIMEOUT => 0,
//                CURLOPT_FOLLOWLOCATION => true,
//                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                CURLOPT_CUSTOMREQUEST => 'POST',
//                CURLOPT_POSTFIELDS =>$arr_post_data,
//                CURLOPT_HTTPHEADER => array(
//                    'Content-Type:application/xml'
//                ),
//            ));
//            $response = curl_exec($curl);
//
//            curl_close($curl);
//            $xml = new SimpleXMLElement($response);
//            $response_data = $xml = json_encode($xml);
//            $xml = json_decode($xml, true);
//
//
//            $request_response = $request->all();
//            $data = [
//                'type'=>'check payment status',
//                'data'=>$response_data
//            ];
//            \Log::channel('dpo_think_payment_api')->emergency($data);
//
//
//            if (isset($xml['Result']) &&  $xml['Result'] == 000)
//            {
//                DB::table("transactions")->where('reference_id', $tx_reference)->update(['request_status' => 2, 'status_message' => "Successful payment"]);
//
//                $transaction_table = DB::table("transactions")->where('reference_id', $tx_reference)->first();
//                //0: Successful payment 2: In progress 4: Expired 6: Canceled
//            }
//        }
//        else
//        {
//            DB::table("transactions")->where('reference_id', $tx_reference)->update(['request_status' => 3, 'status_message' => "Payment failed"]);
//        }

        // check payment status
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        return $return = [
            'payment_status' =>$payment_status ];
    }

}