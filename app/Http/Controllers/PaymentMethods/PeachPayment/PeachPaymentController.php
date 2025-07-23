<?php

namespace App\Http\Controllers\PaymentMethods\PeachPayment;

use App\Http\Controllers\Controller;
use App\Models\DriverCard;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\UserCard;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PeachPaymentController extends Controller
{
    use ApiResponseTrait;
    public function getPeachPaymentConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'PEACHPAYMENT')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        if (empty($paymentOption)) {
            return response()->json(['result' => 0, 'message' => trans('api.message194')]);
        }
        return $paymentOption;
    }

    public function PeachSaveCardCheckout(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'currency' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $user = ($request->type == 1) ? $request->user('api') : $request->user('api-driver');
        $user_id = $user->id;
        $merchant_id = $user->merchant_id;
        $paymentConfig = $this->getPeachPaymentConfig($merchant_id);
        $SSL_VERIFYPEER = $paymentConfig->gateway_condition == 1 ? true : false;
        $currency = $request->currency;
        $url = $paymentConfig->tokenization_url."v1/checkouts";
        $data = "entityId=" .$paymentConfig->api_secret_key.
            "&amount=1.00" .
            "&currency=" .$currency.
            "&paymentType=DB" .
            "&createRegistration=true";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:Bearer '.$paymentConfig->auth_token));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $SSL_VERIFYPEER);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if(curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);

        $data = json_decode($responseData,true);
        if ($data['result']['code'] == "000.200.100"){
            $checkout_id = $data['id'];
            $msg = $data['result']['description'];
            $return_data = [
                'web_view_data' => route('peach.view') . '?checkout_id=' . $checkout_id . '&amount=1.00&currency=' . $currency . '&tokenization_url=' . $paymentConfig->tokenization_url . '&calling_from=SAVE_CARD&type='.$request->type.'&user_drive_id='.$user_id.'&merchant_id='.$merchant_id
            ];
            $return_data['success_url'] = route('PeachSuccess');
            $return_data['fail_url'] = route('PeachFail');
            return $this->successResponse($msg, $return_data);
        }else{
            return $this->failedResponse("Something went wrong!!");
        }
    }

    public function LoadView(Request $request){
        $amount = $request->query('amount');
        $currency = $request->query('currency');
        $checkout_id = $request->query('checkout_id');
        $tokenization_url = $request->query('tokenization_url');
        $calling_from = $request->query('calling_from');
        $type = $request->query('type');
        $user_drive_id = $request->query('user_drive_id');
        $merchant_id = $request->query('merchant_id');
        $script_url = $tokenization_url."v1/paymentWidgets.js?checkoutId=".$checkout_id;
        if ($calling_from == 'SAVE_CARD'){
            $redirect_url = route('PeachRedirectSave');
        }else{
            $redirect_url = route('peach.redirectUpdateStatus');
        }
        $redirect_url = $redirect_url.'?merchant_id='.$merchant_id.'&type='.$type.'&user_drive_id='.$user_drive_id;
        // dd($redirect_url);
        return view('payment/peachPayment/index',compact('amount','currency','script_url','redirect_url'));
    }

    public function redirectSave(Request $request){
        try{
            $data = $request->all();
            $resourcePath = $data['resourcePath'];
            $merchant_id = $data['merchant_id'];
            $type = $data['type'];
            $user_drive_id = $data['user_drive_id'];
            $paymentConfig = $this->getPeachPaymentConfig($merchant_id);
            $tokenization_url = substr($paymentConfig->tokenization_url, 0, -1);
            $SSL_VERIFYPEER = $paymentConfig->gateway_condition == 1 ? true : false;
            $url = $tokenization_url.$resourcePath;
            $url .= "?entityId=".$paymentConfig->api_secret_key;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:Bearer '.$paymentConfig->auth_token));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $SSL_VERIFYPEER);// this should be set to true in production
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if(curl_errno($ch)) {
                return curl_error($ch);
            }
            curl_close($ch);

            $data = json_decode($responseData,true);
            if ($data['result']['code'] == "000.100.110"){
                if ($type == 1){
                    UserCard::create([
                        'user_id' => $user_drive_id,
                        'token' => $data['registrationId'],
                        'payment_option_id' => $paymentConfig->payment_option_id,
                        'card_number' => $data['card']['last4Digits'],
                        'exp_month' => $data['card']['expiryMonth'],
                        'exp_year' => $data['card']['expiryYear'],
                    ]);
                }else{
                    DriverCard::create([
                        'driver_id' => $user_drive_id,
                        'token' => $data['registrationId'],
                        'payment_option_id' => $paymentConfig->payment_option_id,
                        'card_number' => $data['card']['last4Digits'],
                        'exp_month' => $data['card']['expiryMonth'],
                        'exp_year' => $data['card']['expiryYear'],
                    ]);
                }
                $msg = "Card Saved Successfully";
                $reversal = $this->PeachReversal($data['id'],$merchant_id);
                if ($reversal['result']['code'] == "000.100.110"){
                    $msg .= " and Refund Processed";
                }
                return redirect(route('PeachSuccess',$msg));
            }else{
                return redirect(route('PeachFail'));
            }
        }catch(\Exception $exception){
            return redirect(route('PeachFail',$exception->getMessage()));
            // return $this->failedResponse($exception->getMessage());
        }
    }

    public function PeachReversal($payment_id,$merchant_id){
        $paymentConfig = $this->getPeachPaymentConfig($merchant_id);
        $SSL_VERIFYPEER = $paymentConfig->gateway_condition == 1 ? true : false;
        $url = $paymentConfig->tokenization_url."v1/payments/".$payment_id;
        $data = "entityId=" .$paymentConfig->api_secret_key.
            "&paymentType=RV";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:Bearer '.$paymentConfig->auth_token));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $SSL_VERIFYPEER);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if(curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return json_decode($responseData,true);
    }

    public function PeachPaymentCheckout(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'currency' => 'required',
            'amount' => 'required',
            // 'card_id' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $user = ($request->type == 1) ? $request->user('api') : $request->user('api-driver');
        $user_id = $user->id;
        $merchant_id = $user->merchant_id;
        $paymentConfig = $this->getPeachPaymentConfig($merchant_id);
        $SSL_VERIFYPEER = $paymentConfig->gateway_condition == 1 ? true : false;
        $currency = $request->currency;
        $amount = $request->amount;
        $cards = $request->type == 1 ? UserCard::where('user_id',$user_id)->get() : DriverCard::where('driver_id',$user_id)->get();

        $url = $paymentConfig->tokenization_url."v1/checkouts";
        $data = "entityId=" .$paymentConfig->api_secret_key.
            "&amount=" .$amount.
            "&currency=" .$currency.
            "&paymentType=DB" .
            "&createRegistration=true";
        // "&registrations[0].id=".$card->token;

        foreach($cards as $key => $value){
            $data .= '&registrations['.$key.'].id='.$value->token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:Bearer '.$paymentConfig->auth_token));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $SSL_VERIFYPEER);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if(curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        $data = json_decode($responseData,true);
        // dd($data);
        if ($data['result']['code'] == "000.200.100"){
            $checkout_id = $data['id'];
            $msg = $data['result']['description'];
            $return_data = [
                'web_view_data' => route('peach.view') . '?checkout_id=' . $checkout_id . '&amount='.$amount.'&currency=' . $currency . '&tokenization_url=' . $paymentConfig->tokenization_url . '&calling_from=SAVE_PAYMENT&type='.$request->type.'&user_drive_id='.$user_id.'&merchant_id='.$merchant_id
            ];
            $return_data['success_url'] = route('PeachSuccess');
            $return_data['fail_url'] = route('PeachFail');
            return $this->successResponse($msg, $return_data);
        }else{
            $msg = $data['result']['description'];
            $error_name = $data['result']['parameterErrors'][0]['name'];
            $error_value = $data['result']['parameterErrors'][0]['value'];
            $error_message = $data['result']['parameterErrors'][0]['message'];
            $msg = $msg.'( '.$error_name.'='.$error_value.','.$error_message.' )';
            return $this->failedResponse($msg);
        }
    }

    public function redirectUpdateStatus(Request $request){
        $data = $request->all();
        $resourcePath = $data['resourcePath'];
        $merchant_id = $data['merchant_id'];
        $type = $data['type'];
        $user_drive_id = $data['user_drive_id'];
        $paymentConfig = $this->getPeachPaymentConfig($merchant_id);
        $tokenization_url = substr($paymentConfig->tokenization_url, 0, -1);
        $SSL_VERIFYPEER = $paymentConfig->gateway_condition == 1 ? true : false;
        $url = $tokenization_url.$resourcePath;
        $url .= "?entityId=".$paymentConfig->api_secret_key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:Bearer '.$paymentConfig->auth_token));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $SSL_VERIFYPEER);// this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if(curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);

        $data = json_decode($responseData,true);
        if ($data['result']['code'] == "000.100.110"){
            return redirect(route('PeachSuccess'));
        }else{
            return redirect(route('PeachFail'));
        }
    }

    public function RedirectSuccess(Request $request){
        $message = !empty($request->msg) ? $request->msg : "Success";
//        $message = trans('api.message65');
        return view('payment/peachPayment/response', compact('message'));
    }

    public function RedirectFail(Request $request){
        $message = !empty($request->msg) ? $request->msg : "Failed";
        // $message = trans('api.message163');
        return view('payment/peachPayment/response', compact('message'));
    }
}
