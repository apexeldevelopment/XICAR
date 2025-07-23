<?php

namespace App\Http\Controllers\PaymentMethods\BillBox;

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

class BillBoxController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getBillBoxConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'BILLBOX')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getBillBoxURL($env){
        $url = $env == 2 ? "https://kbposapi.mykowri.com/" : "https://posapi.usebillbox.com/";
        return $url;
    }

    public function getBillBoxRequestId()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function getPaymentOptionList(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
        ]);
        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == "USER" ? $request->user('api')->merchant_id :  $request->user('api-driver')->merchant_id;
//        $string_file = $this->getStringFile($merchant_id);
        $paymentOptionConf = $this->getBillBoxConfig($merchant_id);
        $url = $this->getBillBoxURL($paymentOptionConf->gateway_condition);

        $data = [
            'requestId' => $this->getBillBoxRequestId(),
            'appReference' => $paymentOptionConf->api_public_key,
            'secret' => $paymentOptionConf->api_secret_key,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'webpos/listPayOptions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'appId: '.$paymentOptionConf->auth_token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);
        if (!empty($response) && $response['success'] == true && $response['statusCode'] == 'SUCCESS'){
            return $this->successResponse($response['statusMessage'], $response['result']);
        }else{
            $message = isset($response['statusMessage']) ? $response['statusMessage'] : "503 Service Temporarily Unavailable";
            return $this->failedResponse($message);
        }
    }

    public function createInvoice(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required',
            'currency' => 'required'
        ]);
        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == "USER" ? $request->user('api')->merchant_id :  $request->user('api-driver')->merchant_id;
//        $string_file = $this->getStringFile($merchant_id);
        $paymentOptionConf = $this->getBillBoxConfig($merchant_id);
        $url = $this->getBillBoxURL($paymentOptionConf->gateway_condition);

        $data = [
            'requestId' => $this->getBillBoxRequestId(),
            'appReference' => $paymentOptionConf->api_public_key,
            'secret' => $paymentOptionConf->api_secret_key,
            'merchantOrderId' => date('YmdHis'),
            'reference' => 'REF_'.date('YmdHis'),
            'currency' => $request->currency,
            'invoiceItems' => [
                [
                    'name' => 'Papilo Goods and Services',
                    'description' => 'Purchase of Goods and services',
                    // 'imgUrl' => 'https://test.usebillbox.com/static/images/logo/apsu.jpg',
                    'imgUrl' => 'https://www.usebillbox.com/static/images/logo/hour_hand.png',
                    'unitPrice' => $request->amount,
                    'quantity' => 1,
                    'subTotal' => $request->amount,
                ]
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'webpos/createInvoice',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'appId: '.$paymentOptionConf->auth_token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);
        if (!empty($response) && $response['success'] == true && $response['statusCode'] == 'SUCCESS'){
            $data = $response['result'];
            $data['success_url'] = route('BillBoxSuccess');
            $data['fail_url'] = route('BillBoxFail');
            return $this->successResponse($response['statusMessage'], $data);
        }else{
            $message = isset($response['statusMessage']) ? $response['statusMessage'] : "503 Service Temporarily Unavailable";
            return $this->failedResponse($message);
        }
    }

    public function processPayment(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'invoiceNum' => 'required',
            'phone' => 'required',
            'provider' => 'required',
        ]);
        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == "USER" ? $request->user('api')->merchant_id :  $request->user('api-driver')->merchant_id;
        $name = $request->type == "USER" ? $request->user('api')->first_name.' '.$request->user('api')->last_name : $request->user('api-driver')->first_name.' '.$request->user('api-driver')->last_name;
        //        $string_file = $this->getStringFile($merchant_id);
        $paymentOptionConf = $this->getBillBoxConfig($merchant_id);
        $url = $this->getBillBoxURL($paymentOptionConf->gateway_condition);
        $transactionId = date('YmdHis');

        $data = [
            'appReference' => $paymentOptionConf->api_public_key,
            'secret' => $paymentOptionConf->api_secret_key,
            'requestId' => $this->getBillBoxRequestId(),
            'invoiceNum' => $request->invoiceNum,
            'transactionId' => $transactionId,
            'trustedNum' => $request->phone,
            'provider' => $request->provider,
            'walletRef' => $request->phone,
            'customerName' => "Adamah Welbeck",
            'customerMobile' => $request->phone
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'webpos/processPayment',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'appId: '.$paymentOptionConf->auth_token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);
        if (!empty($response) && $response['success'] == true && $response['statusCode'] == 'SUCCESS'){
            $response['result']['transaction_id'] = $transactionId;
            return $this->successResponse($response['statusMessage'], $response['result']);
        }else{
            $message = isset($response['statusMessage']) ? $response['statusMessage'] : "503 Service Temporarily Unavailable";
            return $this->failedResponse($message);
        }
    }

    public function checkPaymentStatus(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'transactionId' => 'required',
        ]);
        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == "USER" ? $request->user('api')->merchant_id :  $request->user('api-driver')->merchant_id;
//        $name = $request->type == "USER" ? $request->user('api')->first_name.' '.$request->user('api')->last_name : $request->user('api-driver')->first_name.' '.$request->user('api-driver')->last_name;
        //        $string_file = $this->getStringFile($merchant_id);
        $paymentOptionConf = $this->getBillBoxConfig($merchant_id);
        $url = $this->getBillBoxURL($paymentOptionConf->gateway_condition);

        $data = [
            'requestId' => $this->getBillBoxRequestId(),
            'appReference' => $paymentOptionConf->api_public_key,
            'secret' => $paymentOptionConf->api_secret_key,
            'transactionId' => $request->transactionId,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'webpos/checkPaymentStatus',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'appId: '.$paymentOptionConf->auth_token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);
        if (!empty($response) && $response['success'] == true && $response['statusCode'] == 'SUCCESS'){
            return $this->successResponse($response['statusMessage'], $response['result']);
        }else{
            $message = isset($response['statusMessage']) ? $response['statusMessage'] : "503 Service Temporarily Unavailable";
            return $this->failedResponse($message);
        }
    }

    public function BillBoxCallback(Request $request){
        \Log::channel('BillBox')->emergency($request->all());
        // dd($request->all());

        $paymentOptionConf = $this->getBillBoxConfig($request->merchant_id);
        $url = $this->getBillBoxURL($paymentOptionConf->gateway_condition);

        $data = [
            'requestId' => $this->getBillBoxRequestId(),
            'appReference' => $paymentOptionConf->api_public_key,
            'secret' => $paymentOptionConf->api_secret_key,
            'transactionId' => $request->transac_id,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'webpos/checkPaymentStatus',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'appId: '.$paymentOptionConf->auth_token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);
        if (!empty($response) && $response['success'] == true && $response['statusCode'] == 'SUCCESS'){
            // return $this->successResponse($response['statusMessage'], $response['result']);
            return redirect(route('BillBoxSuccess'));
        }else{
            $message = isset($response['statusMessage']) ? $response['statusMessage'] : "503 Service Temporarily Unavailable";
            // return $this->failedResponse($message);
            return redirect(route('BillBoxFail',['msg' => $message]));
        }
        // response in callback url from billbox
        // [
        //   "status" => "0"
        //   "transac_id" => "lXG-VLH3d2"
        //   "cust_ref" => "20221012131641"
        //   "pay_token" => "06629e64-ec1f-4b62-839f-3143d191ea8b"
        // ];
    }

    public function RedirectSuccess(Request $request){
        $message = !empty($request->msg) ? $request->msg : "Success";
        return view('payment/billbox/response', compact('message'));
    }

    public function RedirectFail(Request $request){
        $message = !empty($request->msg) ? $request->msg : "Failed";
        return view('payment/billbox/response', compact('message'));
    }
}
