<?php

namespace App\Http\Controllers\PaymentMethods\PagoPlux;

use App\Http\Controllers\Controller;
use App\Models\DriverCard;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\UserCard;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PagoPluxController extends Controller
{
    use ApiResponseTrait;
    public function getPagoPluxConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'PAGOPLUX')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        if (empty($paymentOption)) {
            return response()->json(['result' => 0, 'message' => trans('api.message194')]);
        }
        return $paymentOption;
    }

    public function PagoPluxSaveCardCheckout(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            // 'currency' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $user = ($request->type == 1) ? $request->user('api') : $request->user('api-driver');
        $customer_name = $user->first_name.' '.$user->last_name;
        $customer_phone = $request->type == 1 ? $user->UserPhone : $user->phoneNumber;
        $user_driver_id = $user->id;
        $merchant_id = $user->merchant_id;
        $paymentConfig = $this->getPagoPluxConfig($merchant_id);
        $gateway_env = $paymentConfig->gateway_condition;
        $PayboxIdPlan = $paymentConfig->payment_redirect_url;
        $pagoplux_email = $paymentConfig->tokenization_url;
        $PayBoxClientIdentification = $paymentConfig->auth_token;
        $payment_option_id = $paymentConfig->payment_option_id;

        $return_data = [
            'web_view_data' => route('pagoplux.view') . '?customer_phone=' . $customer_phone . '&customer_name='.$customer_name.'&customer_email=' . $user->email . '&customer_address=Direcci贸n del cliente&calling_from=SAVE_CARD&PayboxIdPlan='.$PayboxIdPlan.'&pagoplux_email='.$pagoplux_email.'&PayBoxClientIdentification='.$PayBoxClientIdentification.'&gateway_env='.$gateway_env.'&payment_option_id='.$payment_option_id.'&user_driver_id='.$user_driver_id.'&type='.$request->type
        ];
        $return_data['success_url'] = route('PagoPluxSuccess');
        $return_data['fail_url'] = route('PagoPluxFail');
        return $this->successResponse("WebView Url", $return_data);
    }

    public function LoadView(Request $request){
        $customer_phone = $request->query('customer_phone');
        $customer_name = $request->query('customer_name');
        $customer_email = $request->query('customer_email');
        $customer_address = $request->query('customer_address');
        $calling_from = $request->query('calling_from');
        $PayboxIdPlan = $request->query('PayboxIdPlan');
        $pagoplux_email = $request->query('pagoplux_email');
        $PayBoxClientIdentification = $request->query('PayBoxClientIdentification');
        $gateway_env = $request->query('gateway_env');
        $payment_option_id = $request->query('payment_option_id');
        $user_driver_id = $request->query('user_driver_id');
        $type = $request->query('type');
        $redirect_form = route('pagoplux.redirect');
        $fail_url = route('PagoPluxFail');

        // $script_url = $tokenization_url."v1/paymentWidgets.js?checkoutId=".$checkout_id;
        if ($calling_from == 'SAVE_CARD'){
            $PayboxListCard = 0;
            $PayboxIdSuscription = "";
            // $redirect_url = route('hyperPayRedirectSave');
        }else{
            $PayboxListCard = 1;
            $PayboxIdSuscription = "";
            // $redirect_url = route('hyperPay.redirectUpdateStatus');
        }
        // $redirect_url = $redirect_url.'?merchant_id='.$merchant_id.'&type='.$type.'&user_drive_id='.$user_drive_id;
        // dd($redirect_url);
        return view('payment/pagoplux/index',compact('customer_phone','customer_name','customer_email','customer_address','PayboxIdPlan','pagoplux_email','PayBoxClientIdentification','PayboxListCard','PayboxIdSuscription','gateway_env','redirect_form','fail_url','payment_option_id','user_driver_id','type'));
    }

    public function redirectSave(Request $request){
        try{
            $data = $request->all();
            // $resourcePath = $data['resourcePath'];
            // $merchant_id = $data['merchant_id'];
            $type = $data['type'];
            $user_drive_id = $data['user_driver_id'];

            if ($type == 1){
                UserCard::create([
                    'user_id' => $user_drive_id,
                    'token' => $data['token'],
                    'payment_option_id' => $data['payment_option_id'],
                    'card_number' => $data['cardInfo'],
                    'card_type' => $data['cardIssuer'],
                    'user_token' => $data['idSuscription'],
                    'card_holder' => $data['clientName'],
                ]);
            }else{
                DriverCard::create([
                    'driver_id' => $user_drive_id,
                    'token' => $data['token'],
                    'payment_option_id' => $data['payment_option_id'],
                    'card_number' => $data['cardInfo'],
                    'card_type' => $data['cardIssuer'],
                    'driver_token' => $data['idSuscription'],
                    'card_holder' => $data['clientName'],
                ]);
            }
            $msg = "Card Saved Successfully!";
            return redirect(route('PagoPluxSuccess',$msg));
        }catch(\Exception $exception){
            return redirect(route('PagoPluxFail',$exception->getMessage()));
            // return $this->failedResponse($exception->getMessage());
        }
    }

    public function getPagoPluxBaseUrl($env){
        return $env == 1 ? "https://api.pagoplux.com/intv1" : "https://apipre.pagoplux.com/intv1";
    }

    public function PagoPluxMakePayment($customer_id, $secret_password, $env, $amount, $token){
        $auth = base64_encode($customer_id.':'.$secret_password);
        $url = $this->getPagoPluxBaseUrl($env);
        $data = [
            "monto" => (float)$amount,
            "token" => $token
        ];
        // dd(json_encode($data,true));
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'/admincards/paymentByTokenCardResource',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data,true),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.$auth,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response,true);
    }

    public function DeletePagoPluxSavedCard($customer_id, $secret_password, $env, $token){
        $auth = base64_encode($customer_id.':'.$secret_password);
        $url = $this->getPagoPluxBaseUrl($env);
        $data = [
            "token" => $token,
            "estado" => 0,
        ];
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'/admincards/updateStatusCardResource',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.$auth,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response,true);
    }

    public function getPagoPluxCards($customer_id, $secret_password, $env, $user_token, $card_token, $get_all = false){
        $auth = base64_encode($customer_id.':'.$secret_password);
        $id_subscription = base64_encode($user_token);
        $url = $this->getPagoPluxBaseUrl($env);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'/admincards/listCardsResource?idSus='.$id_subscription,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.$auth
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response,true);

        if($get_all){
            return $response;
        }else{
            if(in_array($card_token,array_column($response['detail'],'token'))){
                $key = array_search($card_token,array_column($response['detail'],'token'));
                $card_data = $response['detail'][$key];
                return [
                    'card_number' => base64_decode($card_data['numeroTarjeta']),
                    'exp_date' => base64_decode($card_data['fechaExpiracion']),
                ];
            }
        }
        return [];
    }

    public function RedirectSuccess(Request $request){
        $message = !empty($request->msg) ? $request->msg : "Success";
//        $message = trans('api.message65');
        return view('payment/hyperPay/response', compact('message'));
    }

    public function RedirectFail(Request $request){
        $message = !empty($request->msg) ? $request->msg : "Failed";
        // $message = trans('api.message163');
        return view('payment/hyperPay/response', compact('message'));
    }
}
