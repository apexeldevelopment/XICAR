<?php

namespace App\Http\Controllers\PaymentMethods\Ihela;

use App\Http\Controllers\Controller;
use App\Models\DriverCard;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\UserCard;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class IhelaController extends Controller
{
    use ApiResponseTrait;
    public function getIhelaConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'IHELA')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        if (empty($paymentOption)) {
            return $this->failedResponse('Configuration Not Found!');
        }
        return $paymentOption;
    }

    public function getIhelaAuthToken($merchant_id){
        $paymentConfig = $this->getIhelaConfig($merchant_id);
        $env = $paymentConfig->gateway_condition;
        $url = $env == 2 ? "https://testgate.ihela.online/oAuth2" : "https://oa2.ihela.bi";
        $client_id = $paymentConfig->api_public_key;
        $client_secret = $paymentConfig->api_secret_key;
        $token = base64_encode($client_id.':'.$client_secret);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'/token/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('grant_type' => 'client_credentials'),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.$token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);
        if(isset($response['access_token'])){
            return $response['access_token'];
        }
        return NULL;
    }

    public function getIhelaURL($merchant_id){
        $paymentConfig = $this->getIhelaConfig($merchant_id);
        $env = $paymentConfig->gateway_condition;
        $url = $env == 2 ? "https://testgate.ihela.online" : "https://api.ihela.bi";
        return $url;
    }

    public function getIhelaBankList(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $user = $request->type == 1 ? $request->user('api') : $request->user('api-driver');
        $token = $this->getIhelaAuthToken($user->merchant_id);
        if (empty($token)){
            return $this->failedResponse('Token Not Generated');
        }

        $url = $this->getIhelaURL($user->merchant_id);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'/api/v1/payments/bank',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response,true);
        return $this->successResponse("Bank List", ['banks' => $response['banks']]);
    }

    public function IhelaCustomerAccountLookup(Request $request){
        $validator = Validator::make($request->all(), [
            'bank_slug' => 'required',
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $user = $request->type == 1 ? $request->user('api') : $request->user('api-driver');
        $customer_id = !empty($request->customer_id) ? $request->customer_id : $user->email;
        $url = $this->getIhelaURL($user->merchant_id);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'/api/v1/bank/'.$request->bank_slug.'/account/lookup?customer_id='.$customer_id,
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
        $response = json_decode($response,true);
        if (isset($response['account_number'])){
            return $this->successResponse("Account Details", $response);
        }else{
            return $this->failedResponse($response['details']);
        }
    }

    public function generateIhelaUrl(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try{
            $user = $request->type == 1 ? $request->user('api') : $request->user('api-driver');
            $customer_id = !empty($request->customer_id) ? $request->customer_id : $user->email;
            $token = $this->getIhelaAuthToken($user->merchant_id);
            if (empty($token)){
                return $this->failedResponse('Token Not Generated');
            }

            $url = $this->getIhelaURL($user->merchant_id);
            $merchantRef = 'REF'.date('Ymd').time();
            $data=[
                'user' => $customer_id,
                'merchant_reference' => $merchantRef,
                'amount' => $request->amount,
                'description' => $user->Merchant->BusinessName.' Payment',
                'bank' => !empty($request->bank_slug) ? $request->bank_slug : NULL,
                'redirect_uri' => $request->redirect_uri,
            ];
            if ($request->bank_slug) {
                $data['bank_client_id'] = $customer_id;
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url.'/api/v1/payments/bill/init/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$token
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response,true);
            if(isset($response['bill'])){
                DB::table('ihela_transactions')->insert([
                    'merchant_id' => $user->merchant_id,
                    'user_id' => $user->id,
                    'type' => $request->type,
                    'merchant_ref' => $merchantRef,
                    'bill_code' => $response['bill']['code'],
                    'amount' => $request->amount,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }else{
                return $this->failedResponse('Something went wrong!');
            }
        }catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }

        DB::commit();
        return $this->successResponse($response['bill']['description'], ['payment_url' => $response['bill']['confirmation_uri'],'return_url' => route('ihela.return')]);
    }

    public function checkPaymentStatus(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try{
            $user = $request->type == 1 ? $request->user('api') : $request->user('api-driver');
            $trans = DB::table('ihela_transactions')->where(['status' => null,'type' => $request->type,'user_id' => $user->id])->latest()->first();
            if (!empty($trans)){
                $token = $this->getIhelaAuthToken($user->merchant_id);
                if (empty($token)){
                    return $this->failedResponse('Token Not Generated');
                }
                $url = $this->getIhelaURL($user->merchant_id);

                $data=[
                    'reference' => $trans->bill_code,
                    'code' => $trans->merchant_ref,
                ];

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url.'/api/v1/payments/bill/verify/',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer '.$token
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                $response = json_decode($response,true);
                if(isset($response['bill']) && $response['bill']['status'] == 'Paid'){
                    DB::table('ihela_transactions')->where(['bill_code' => $response['bill']['reference']])->update(['status' => $response['bill']['status'], 'updated_at' => date('Y-m-d H:i:s')]);
                }
                $message = $response['bill']['message'];
            }else{
                return $this->failedResponse('Data Not Found!');
            }
        }catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse($message, $response['bill']);
    }

    public function IhelaCallback(Request $request){
        dd($request->all());
        $trans = DB::table('telebirrpay_transactions')->where(['outTradeNo' => $outTradeNo])->first();
        if(!empty($trans)){
            if($trans->payment_status == "Success"){
                return redirect(route('teliberr.success'));
            }else{
                return redirect(route('teliberr.failed'));
            }
        }else{
            return redirect(route('teliberr.failed',"outTradeNo Data Not Found"));
        }
    }

    public function IhelaSuccess(Request $request){
        $response = "Success!";
        return view('payment/telebirr_pay/callback', compact('response'));
    }

    public function IhelaFailed(Request $request){
        $response = !empty($request->msg) ? $request->msg : "Failed!";
        return view('payment/telebirr_pay/callback', compact('response'));
    }
}
