<?php

namespace App\Http\Controllers\PaymentMethods\Tap;

use App\Http\Controllers\Controller;
use App\Models\DriverCard;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\UserCard;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TapController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getTapConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'TAP')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getUrl($env){
        return $env == 1 ? "https://api.tap.company/v2/" : "https://api.tap.company/v2/";
    }

    public function CreateCustomer($customer,$env,$secret_key){
        $url = $this->getUrl($env);
        $post_data = [
            'first_name' => $customer['first_name'],
            'last_name' => $customer['last_name'],
            'email' => $customer['email'],
            'phone' => [
                'country_code' => $customer['country_code'],
                'number' => $customer['number'],
            ],
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'customers',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$secret_key,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response,true);
        if (isset($response['id'])){
            return $response['id'];
        }else{
            return $this->failedResponse($response['errors'][0]['description']);
        }
    }

    public function generateSaveCardUrl(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $customer_data = $request->type == 'USER' ? $request->user('api') : $request->user('api-driver');
        $merchant_id = $customer_data->merchant_id;
        $string_file = $this->getStringFile($merchant_id);

        $customer['first_name'] = $customer_data->first_name;
        $customer['last_name'] = $customer_data->last_name;
        $customer['email'] = $customer_data->email;
        $customer['country_code'] = $customer_data->Country->phonecode;
        if($request->type == 'USER'){
            $customer['number'] = str_replace($customer_data->Country->phonecode,'',$customer_data->UserPhone);
        }else{
            $customer['number'] = str_replace($customer_data->Country->phonecode,'',$customer_data->phoneNumber);
        }
        $paymentOption = $this->getTapConfig($merchant_id);
        $customer_id = $this->CreateCustomer($customer, $paymentOption->gateway_condition, $paymentOption->api_secret_key);
        if(is_object($customer_id) && !empty($customer_id->getContent())){
            $data = json_decode($customer_id->getContent(),true);
            return $this->failedResponse($data['message'].' Could not create customer.');
        }

        $redirect_url = route('tap.save-card-redirect');
        $return_data = [
            'web_view_data' => route('tap.load.saveCard.view') . '?customer_id=' . $customer_id . '&public_key=' . $paymentOption->api_public_key . '&redirect_url=' . $redirect_url.'&merchant_id='.$merchant_id.'&type='.$request->type.'&udid='.$customer_data->id,
            'success_url' => route('tapSuccess'),
            'fail_url' => route('tapFail'),
        ];
        return $this->successResponse(trans("$string_file.payment"), $return_data);
    }

    public function LoadSaveCardView(Request $request){
        $customer_id = $request->query('customer_id');
        $redirect_url = $request->query('redirect_url');
        $public_key = $request->query('public_key');
        $merchant_id = $request->query('merchant_id');
        $type = $request->query('type');
        $udid = $request->query('udid');
        return view('payment.tap.save-card',compact('redirect_url','public_key','customer_id','merchant_id','type','udid'));
    }

    public function SaveCard(Request $request){
        DB::beginTransaction();
        try{
            $paymentOption = $this->getTapConfig($request->merchant_id);
            $url = $this->getUrl($paymentOption->gateway_condition);
            $post_data = ['source' => $request->tapToken];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url.'card/'.$request->customer_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($post_data),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$paymentOption->api_secret_key,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response,true);
            if(isset($response['id'])){
                $card_id = $response['id'];
                if ($request->type == 'USER'){
                    UserCard::create([
                        'user_id' => $request->udid,
                        'token' => $card_id,
                        'payment_option_id' => $paymentOption->payment_option_id,
                        'card_number' => $response['first_six'].'******'.$response['last_four'],
                        'card_type' => $response['brand'],
                        'user_token' => $request->customer_id,
                        'card_holder' => $response['name'],
                        'exp_month' => $response['exp_month'],
                        'exp_year' => $response['exp_year'],
                    ]);
                }else{
                    DriverCard::create([
                        'driver_id' => $request->udid,
                        'token' => $card_id,
                        'payment_option_id' => $paymentOption->payment_option_id,
                        'card_number' => $response['first_six'].'******'.$response['last_four'],
                        'card_type' => $response['brand'],
                        'driver_token' => $request->customer_id,
                        'card_holder' => $response['name'],
                        'exp_month' => $response['exp_month'],
                        'exp_year' => $response['exp_year'],
                    ]);
                }
                DB::commit();
                $msg = "Card Saved Successfully!";
                return redirect(route('tapSuccess',$msg));
            }else{
                DB::rollBack();
                return redirect(route('tapFail',$response['errors'][0]['description']));
            }
        }catch(\Exception $exception){
            DB::rollBack();
            return redirect(route('tapFail',$exception->getMessage()));
        }
    }

    public function generatePaymentUrl(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required',
            'currency' => 'required',
            'card_id' => 'required',
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $merchant_id = $request->type == 'USER' ? $request->user('api')->merchant_id : $request->user('api-driver')->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $paymentOption = $this->getTapConfig($merchant_id);
        $url = $this->getUrl($paymentOption->gateway_condition);
        $card = $request->type == 'USER' ? UserCard::find($request->card_id) : DriverCard::find($request->card_id);
        $customer_id = $request->type == 'USER' ? $card->user_token : $card->driver_token;
        $card_token = $this->generatePaymentToken($paymentOption->api_secret_key, $url, $card->token, $customer_id);
        if(empty($card_token)){
            return $this->failedResponse("Payment Token Not Generated. Something Went Wrong!");
        }

        $post_data = [
            'amount' => $request->amount,
            'currency' => $request->currency,
            'threeDSecure' => true,
            'save_card' => false,
            'description' => $paymentOption->Merchant->BusinessName.' Payment',
//            'statement_descriptor' => 'Sample',
            'metadata' => ['merchant_id' => $merchant_id],
            'reference' => [
                'transaction' => 'txn_'.date('YmdHis'),
                'order' => 'ord_'.date('YmdHis')
            ],
            'customer' => [
                'id' => $customer_id
            ],
            'source' => ['id' => $card_token],
            'post' => ['url' => route('tap.payment.redirect').'?merchant_id='.$merchant_id],
            'redirect' => ['url' => route('tap.payment.redirect').'?merchant_id='.$merchant_id],
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'charges',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$paymentOption->api_secret_key,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        if (isset($response['id'])){
            $return_data = [
                'payment_url' => $response['transaction']['url'],
                'charge_id' => $response['id'],
                'success_url' => route('tapSuccess'),
                'fail_url' => route('tapFail'),
            ];
            return $this->successResponse(trans("$string_file.payment"), $return_data);
        }else{
            return $this->failedResponse($response['errors'][0]['description']);
        }
    }

    public function generatePaymentToken($secret_key, $url, $card_id, $customer_id){
        $post_data = [
            'saved_card' => [
                'card_id' => $card_id,
                'customer_id' => $customer_id
            ],
            'client_ip' => request()->ip()
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'tokens',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$secret_key,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        if(isset($response['id'])){
            return $response['id'];
        }else{
            return '';
        }
    }

    public function paymentCallback(Request $request){
        $paymentOption = $this->getTapConfig($request->merchant_id);
        $url = $this->getUrl($paymentOption->gateway_condition);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.'charges/'.$request->tap_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$paymentOption->api_secret_key
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        if (isset($response['id']) && $response['response']['code'] == "000"){
            return redirect(route('tapSuccess',$response['response']['message']));
        }else{
            return redirect(route('tapFail',$response['response']['message']));
        }
    }

    public function TapSuccess(Request $request){
        $message = !empty($request->msg) ? $request->msg : "Success";
        echo "<h2 style='text-align: center;color: green'>".$message."</h2>";
    }

    public function TapFail(Request $request){
        $message = !empty($request->msg) ? $request->msg : "Failed";
        echo "<h2 style='text-align: center;color: red'>".$message."</h2>";
    }
}
