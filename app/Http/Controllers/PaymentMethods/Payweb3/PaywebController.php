<?php

namespace App\Http\Controllers\PaymentMethods\Payweb3;
use App\Http\Controllers\Controller;
use App\Models\PaymentOptionsConfiguration;
use Illuminate\Http\Request;
use DateTime;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\ContentTrait;

class PaywebController extends Controller
{
    use ApiResponseTrait, MerchantTrait, ContentTrait;

    public function PayWeb3Initiate($request, $payment_option_config, $calling_from){
        DB::beginTransaction();
        try{
            if ($calling_from == 'USER'  || $calling_from == 'BOOKING'){
                $user = $request->user('api');
                $id = $user->id;
                $country = $this->CountryCode($user->Country->country_code);
                $email = $user->email;
                $merchant_id = $user->merchant_id;
            }else{
                $user = $request->user('api-driver');
                $id = $user->id;
                $country = $this->CountryCode($user->Country->country_code);
                $email = $user->email;
                $merchant_id = $user->merchant_id;
            }
            $currency = $request->currency;
            if($payment_option_config->gateway_condition == 2){
                $country = "ASM";
                $currency = "USD";
            }

//            $payment_config = PaymentOptionsConfiguration::where('merchant_id',$user->merchant_id)->first();
//            if (empty($payment_config)){
//                return response()->json(['result' => "0", 'message' => __('api.message194'), 'data' => []]);
//            }

            $ref_number = date('YmdHis');
            $encryptionKey = $payment_option_config->api_secret_key;
            $return_url = \route('payweb3.return');
            $DateTime = new DateTime();
            $data = array(
                'PAYGATE_ID'        => $payment_option_config->auth_token,
                'REFERENCE'         => $ref_number,
                'AMOUNT'            => $request->amount*100,
                'CURRENCY'          => $currency,
                'RETURN_URL'        => $return_url,
                'TRANSACTION_DATE'  => $DateTime->format('Y-m-d H:i:s'),
                'LOCALE'            => 'en',
                'COUNTRY'           => empty($country) ? "ASM" : $country,
                'EMAIL'             => $email
            );

            $checksum = md5(implode('', $data) . $encryptionKey);
            $data['CHECKSUM'] = $checksum;
            $fieldsString = http_build_query($data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://secure.paygate.co.za/payweb3/initiate.trans');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, false);
            curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);

            $result = curl_exec($ch);
            curl_close($ch);
            if(explode('=',$result)[0] != 'ERROR'){;
                $pay_request_id = explode('=',explode('&',$result)[1])[1];
                $returned_checksum = explode('=',explode('&',$result)[3])[1];
                $complete_url = \route('payweb3.webview',[$pay_request_id,$returned_checksum]);
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_option_id' => $payment_option_config->payment_option_id,
                    'checkout_id' => NULL,
                    'amount' => $currency.' '.$request->amount,
                    'booking_id' => $request->booking_id,
                    'order_id' => $request->order_id,
                    'handyman_order_id' => $request->handyman_order_id,
                    'payment_transaction_id' => $pay_request_id,
                    'payment_transaction' => NULL,
                    'request_status' => 1,
                    'reference_id' => $ref_number
                ]);
            }else{
                DB::rollBack();
                throw new \Exception(explode('=',$result)[1]);
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        DB::commit();
        return [
            'status' => 'NEED_TO_OPEN_WEBVIEW',
            'url' => $complete_url ?? '',
            'success_url' => route('payweb3.SuccessUrl'),
            'fail_url' => route('payweb3.FailUrl'),
            'transaction_id' => $pay_request_id
        ];
    }

    public function PayWeb3FormView(Request $request){
        $pay_request_id = $request->pay_request_id;
        $checksum = $request->checksum;
        return view('payment.payweb3.index',compact('pay_request_id','checksum'));
    }

    public function PayWeb3Return(Request $request){
        $trans = DB::table('transactions')->where(['request_status' => 1,'payment_transaction_id' => $request->PAY_REQUEST_ID])->first();
        if (!empty($trans)){
            $string_file = $this->getStringFile($trans->merchant_id);
            $payment_config = PaymentOptionsConfiguration::where('merchant_id',$trans->merchant_id)->first();
            if (empty($payment_config)){
                $this->failedResponse(trans("$string_file.configuration_not_found"));
            }
            // $encryptionKey = 'secret';
            $encryptionKey = $payment_config->api_secret_key;
            $data = array(
                'PAYGATE_ID'        => $payment_config->auth_token,
                'PAY_REQUEST_ID'    => $request->PAY_REQUEST_ID,
                'REFERENCE'         => $trans->reference_id,
            );

            $checksum = md5(implode('', $data) . $encryptionKey);
            $data['CHECKSUM'] = $checksum;
            $fieldsString = http_build_query($data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://secure.paygate.co.za/payweb3/query.trans');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, false);
            curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);
            $result = curl_exec($ch);
            curl_close($ch);

            $transaction_status = explode('=',explode('&',$result)[3])[1];
            $result_code = explode('=',explode('&',$result)[4])[1];
            $payment_status = $transaction_status == 1 ? 2 : 3;
            DB::table('transactions')
                ->where(['payment_transaction_id' => $request->PAY_REQUEST_ID])
                ->update(['request_status' => $payment_status, 'payment_transaction' => json_encode(explode('&',$result)), 'updated_at' => date('Y-m-d H:i:s')]);
            $msg = $this->Payweb3ResultDesc($result_code);
            if ($transaction_status == 1){
                return redirect(route('payweb3.SuccessUrl',$msg));
            }else{
                return redirect(route('payweb3.FailUrl',$msg));
            }
        }
    }

    public function CountryCode($code){
        $iso3 = '';
        switch ($code){
            case "IN":
                $iso3 = "IND";
                break;
            case "ZA":
                $iso3 = "ZAF";
                break;
        }
        return $iso3;
    }

    public function Payweb3ResultDesc($result_code){
        $msg = '';
        switch ($result_code){
            case "900001":
                $msg = 'Call for Approval';
                break;
            case "900002":
                $msg = 'Card Expired';
                break;
            case "900003":
                $msg = 'Insufficient Funds';
                break;
            case "900004":
                $msg = 'Invalid Card Number';
                break;
            case "900005":
                $msg = 'Bank Interface Timeout. Indicates a communications failure between the banks systems.';
                break;
            case "900006":
                $msg = 'Invalid Card';
                break;
            case "900007":
                $msg = 'Declined';
                break;
            case "900009":
                $msg = 'Lost Card';
                break;
            case "900010":
                $msg = 'Invalid Card Length';
                break;
            case "900011":
                $msg = 'Suspected Fraud';
                break;
            case "900012":
                $msg = 'Card Reported as Stolen';
                break;
            case "900013":
                $msg = 'Restricted Card';
                break;
            case "900014":
                $msg = 'Excessive Card Usage';
                break;
            case "900015":
                $msg = 'Card Blacklisted';
                break;
            case "900207":
                $msg = 'Declined; authentication failed. Indicates the cardholder did not enter their MasterCard SecureCode / Verified by Visa password correctly.';
                break;
            case "990020":
                $msg = 'Auth Declined';
                break;
            case "900210":
                $msg = '3D Secure Lookup Timeout';
                break;
            case "991001":
                $msg = 'Invalid expiry date';
                break;
            case "991002":
                $msg = 'Invalid Amount';
                break;
            case "990017":
                $msg = 'Auth Done';
                break;
            case "900205":
                $msg = 'Unexpected authentication result (phase 1)';
                break;
            case "900206":
                $msg = 'Unexpected authentication result (phase 2)';
                break;
            case "990001":
                $msg = 'Could not insert into Database';
                break;
            case "990022":
                $msg = 'Bank not available';
                break;
            case "990053":
                $msg = 'Error processing transaction';
                break;
            case "900209":
                $msg = 'Transaction verification failed (phase 2). Indicates the verification data returned from MasterCard SecureCode / Verified-by-Visa has been altered.';
                break;
            case "900019":
                $msg = 'Invalid PayVault Scope';
                break;
            case "990013":
                $msg = 'Error processing a batch transaction';
                break;
            case "990024":
                $msg = 'Duplicate Transaction Detected. Please check before submitting';
                break;
            case "990028":
                $msg = 'Transaction cancelled. Customer clicks the ‘Cancel’ button on the payment page.';
                break;
        }
        return $msg;
    }

    public function Payweb3Success(Request $request)
    {
        $message = !empty($request->msg) ? $request->msg : "Success";
        echo "<h2 style='text-align: center;color: green'>".$message."</h2>";
    }

    public function Payweb3Fail(Request $request)
    {
        $message = !empty($request->msg) ? $request->msg : "Failed";
        echo "<h2 style='text-align: center;color: red'>".$message."</h2>";
    }

    public function paymentStatus($request){
        $tx_reference = $request->transaction_id; // order id
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $tx_reference)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        if($transaction_table->request_status == 1)
        {
            $request_status_text = "processing";
        }
        else if($transaction_table->request_status == 2)
        {
            $request_status_text = "success";
        }
        else
        {
            $request_status_text = "failed";
        }
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
    }
}