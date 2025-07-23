<?php

namespace App\Http\Controllers\PaymentMethods\AfriMoneyGambia;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AfriMoneyGambiaController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getAfriMoneyConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'AFRIMONEY_GAMBIA')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getAfriMoneyURL($env){
        $url = $env == 2 ? "https://api.sandbox.afrimoney.gm/" : "https://api.sandbox.afrimoney.gm/";
        return $url;
    }

    public function processPayment(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required',
            'phone' => 'required'
        ]);
        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == "USER" ? $request->user('api')->merchant_id :  $request->user('api-driver')->merchant_id;
        $user_driver = $request->type == "USER" ? $request->user('api') : $request->user('api-driver');
        $paymentOptionConf = $this->getAfriMoneyConfig($merchant_id);
        $url = $this->getAfriMoneyURL($paymentOptionConf->gateway_condition);

        $data = [
            'serviceCode' => 'MERCHPAY',
            'transactionAmount' => $request->amount,
            'initiator' => 'transactor',
            'currency' => '101',
            'bearerCode' => 'USSD',
            'source' => $user_driver->Merchant->BusinessName,
            'language' => 'en',
            'externalReferenceId' => 'EXT'.date('YmdHis'),
            'remarks' => 'CashOut',
            'transactor' => [
                'idType' => 'mobileNumber',
                'productId' => '12',
                'idValue' => $paymentOptionConf->api_public_key,
                'mpin' => $paymentOptionConf->api_secret_key
            ],
            'sender' => [
                'idType' => 'mobileNumber',
                'productId' => '12',
                'idValue' => $request->phone
            ]
        ];
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'MERCHPAY',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$paymentOptionConf->auth_token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        // $err =  curl_error($curl);
        // $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $response = json_decode($response,true);
        if (!empty($response) && $response['status'] == "PAUSED" && $response['txnStatus'] == "TI"){
            $response_data['transaction_id'] = $response['transactionId'];
            $response_data['txnStatus'] = $response['txnStatus'];
            $response_data['status'] = $response['status'];
            return $this->successResponse($response['message'], $response_data);
        }else{
            $message = isset($response['errors'][0]) ? $response['errors'][0]['message'] : "Something Went Wrong!!";
            return $this->failedResponse($message);
        }
    }

    public function checkTransactionEnquiry(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'transaction_id' => 'required',
        ]);
        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == "USER" ? $request->user('api')->merchant_id :  $request->user('api-driver')->merchant_id;
        $paymentOptionConf = $this->getAfriMoneyConfig($merchant_id);
        $url = $this->getAfriMoneyURL($paymentOptionConf->gateway_condition);
        $transactionId = date('YmdHis');

        $data = '<?xml version="1.0"?>
        <COMMAND>
            <TYPE>TRANSREQ</TYPE>
            <REFERENCEID>'.$request->transaction_id.'</REFERENCEID>
        </COMMAND>';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'TxnEnquiry',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$paymentOptionConf->auth_token,
                'Content-Type: application/xml'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        
        $json = json_encode(simplexml_load_string($response));
        $arr_response = json_decode($json,TRUE);
        
        if (!empty($arr_response) && $arr_response['REFTXNSTATUS'] == 200 && ($arr_response['REFTXNSTATE'] == "TS" || $arr_response['REFTXNSTATE'] == "TP")){
            $msg = $arr_response['REFTXNSTATE'] == "TS" ? $arr_response['REFMESSAGE'] : "Pending";
            return $this->successResponse($msg, $arr_response['REFERENCEID']);
        }else{
            $message = isset($arr_response['REFTXNSTATE']) ? $arr_response['REFTXNSTATE'].", Failed" : "Something Went Wrong!!";
            return $this->failedResponse($message);
        }
    }
}
