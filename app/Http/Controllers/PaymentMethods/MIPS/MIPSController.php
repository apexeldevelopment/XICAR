<?php

namespace App\Http\Controllers\PaymentMethods\MIPS;

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

class MIPSController extends Controller
{
    use ApiResponseTrait,MerchantTrait;
    public function getMIPSConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'MIPS')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function MIPS(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $user = $request->type == 1 ? $request->user('api') : $request->user('api-driver');
        $paymentConfig = $this->getMIPSConfig($user->merchant_id);
        $payment_data = json_decode($paymentConfig->additional_data,true);

        $auth = base64_encode($paymentConfig->api_public_key.':'.$paymentConfig->api_secret_key);
        $order_id = "INV".date('YmdHis');
        // $amount = 10.25;
        $currency = !empty($request->currency) ? $request->currency : "MUR";
        $data = [
            'authentify' => [
                'id_merchant' => $payment_data['id_merchant'],
                'id_entity' => $payment_data['id_entity'],
                'id_operator' => $payment_data['id_operator'],
                'operator_password' => $payment_data['operator_password'],
            ],
            'order' => [
                'id_order' => $order_id,
                'currency' => $currency,
                'amount' => $request->amount
            ],
            'iframe_behavior' => [
                'height' => 400,
                'width' => 350,
                'custom_redirection_url' => route('mips.return',['id_order' => $order_id]),
                'language' => 'EN'
            ],
            'request_mode' => 'simple',
            'touchpoint' => 'native_app'
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.mips.mu/api/load_payment_zone",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic ".$auth,
                "Content-Type: application/json",
                "user-agent: Chrome/60.0.3112.78"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response,true);
        if(isset($response['answer']) && isset($response['answer']['operation_status']) && $response['answer']['operation_status'] == "success"){
            DB::table('mpessa_transactions')->insert([
                 'merchant_id' => $user->merchant_id,
                 'user_id' => $user->id,
//                'merchant_id' => 1,
//                'user_id' => 10,
                'type' => $request->type,
                'checkout_request_id' => $order_id,
                'amount' => $request->amount,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $response['answer']['success_url'] = route('mips.success');
            $response['answer']['fail_url'] = route('mips.failed');
            return $this->successResponse($response['answer']['operation_status'], $response['answer']);
        }else{
            return $this->failedResponse($response['answer']['operation_status']);
        }
    }

    public function MIPSCallback(Request $request){
        \Log::channel('MIPS')->emergency($request->all());
        $trans = DB::table('mpessa_transactions')->where(['checkout_request_id' => $request->id_order])->first();
        if(!empty($trans)){
            $paymentConfig = $this->getMIPSConfig($trans->merchant_id);
            $payment_data = json_decode($paymentConfig->additional_data,true);
        }
        $auth = base64_encode($paymentConfig->api_public_key.':'.$paymentConfig->api_secret_key);
        $data = [
            'authentify' => [
                'id_merchant' => $payment_data['id_merchant'],
                'id_entity' => $payment_data['id_entity'],
                'id_operator' => $payment_data['id_operator'],
                'operator_password' => $payment_data['operator_password'],
            ],
            'salt' => $payment_data['salt'],
            'cipher_key' => $payment_data['cipher_key'],
            'received_crypted_data' => $request->posted_data
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.mips.mu/api/decrypt_imn_data",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic ".$auth,
                "Content-Type: application/json",
                "user-agent: "
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $response_data = json_decode($response,true);
        \Log::channel('MIPS')->emergency($response_data);
//        $trans = [];
//        $id_order = $request->id_order;
//        $trans = DB::table('mpessa_transactions')->where(['checkout_request_id' => $id_order])->first();
        if($response_data['status'] == "SUCCESS"){
            if (!empty($trans)){
                DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => $response_data['id_order']])
                    ->update(['payment_status' => 'Success', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => $response]);
            }
        }else{
            if (!empty($trans)){
                DB::table('mpessa_transactions')
                    ->where(['checkout_request_id' => $response_data['id_order']])
                    ->update(['payment_status' => 'Failed', 'updated_at' => date('Y-m-d H:i:s'), 'request_parameters' => $response]);
            }
        }
    }

    public function MIPSReturn($id_order){
        $trans = DB::table('mpessa_transactions')->where(['checkout_request_id' => $id_order])->first();
        if(!empty($trans)){
            if($trans->payment_status == "Success"){
                return redirect(route('mips.success'));
            }else{
                return redirect(route('mips.failed'));
            }
        }else{
            return redirect(route('mips.failed',"id_order Data Not Found"));
        }
    }

    public function MIPSSuccess(Request $request){
        $response = "Success!";
        return view('payment/telebirr_pay/callback', compact('response'));
    }

    public function MIPSFailed(Request $request){
        $response = !empty($request->msg) ? $request->msg : "Failed!";
        return view('payment/telebirr_pay/callback', compact('response'));
    }
}
