<?php

namespace App\Http\Controllers\PaymentMethods\MoovMoney;

use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MoovMoneyController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getMoovMoneyConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'MOOVMONEY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function getUrl($env){
        return $env == 1 ? "https://196.28.245.227/tlcfzc_gw_prod/mbs-gateway/gateway/3pp/transaction/process" : "https://196.28.245.227/tlcfzc_gw/api/gateway/3pp/transaction/process";
    }

    public function MoovMoneyRequestId()
    {
        return sprintf('%03x-%05x-%05x%05x%05x%05x%05x',
            mt_rand(0, 0xfff), mt_rand(0, 0xfffff),
            mt_rand(0, 0x0ffff) | 0x4000,
            mt_rand(0, 0x3ffff) | 0x8000,
            mt_rand(0, 0xfffff), mt_rand(0, 0xfffff), mt_rand(0, 0xfffff)
        );
    }

    public function SendMoovMoneyOTP(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'amount' => 'required',
            'phone' => 'required',
            'calling_for' => 'required',
            'otp' => 'required_if:calling_for,VERIFY_OTP',
            'trans_id' => 'required_if:calling_for,VERIFY_OTP',
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try{
            $merchant_id = $request->type == 'USER' ? $request->user('api')->merchant_id : $request->user('api-driver')->merchant_id;
            $paymentOption = $this->getMoovMoneyConfig($merchant_id);
            $payment_url = $this->getUrl($paymentOption->gateway_condition);
            $phone = str_replace("+", "", $request->phone);
            $token = base64_encode($paymentOption->api_public_key.':'.$paymentOption->api_secret_key);


            $data = [
                'request-id' => $this->MoovMoneyRequestId(),
                'destination' => $phone,
                'amount' => $request->amount,
            ];
            if ($request->calling_for == 'SEND_OTP'){
                $data['remarks'] = 'OTP Merchant';
                $data['extended-data'] = ['module' => 'MERCHOTPPAY'];
                $command_id = 'process-create-mror-otp';
            }else{
                $data['remarks'] = 'Merchant Payment with OTP';
                $data['extended-data'] = [
                    'module' => 'MERCHOTPPAY',
                    'otp' => $request->otp,
                    'ext1' => '',
                    'ext2' => '',
                    'trans-id' => $request->trans_id
                ];
                $command_id = 'process-commit-otppay';
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $payment_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer '.$token,
                    'command-id: '.$command_id
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            // dd(json_encode($data),$command_id,$token,$response);
            $response = json_decode($response, true);
            if (!empty($response) && isset($response['status']) && $response['status'] == '0'){
                if ($request->calling_for == 'SEND_OTP'){
                    $calling_for = $request->calling_from == 'BOOKING' ? 3 : ($request->type == "USER" ? 1 : 2);
                    DB::table('transactions')->insert([
                        'status' => $calling_for,
                        'reference_id' => $response['request-id'],
                        'card_id' => NULL,
                        'merchant_id' => $merchant_id,
                        'payment_option_id' => $paymentOption->payment_option_id,
                        'checkout_id' => NULL,
                        'amount' => $request->amount,
                        'booking_id' => $request->booking_id,
                        'order_id' => $request->order_id,
                        'handyman_order_id' => $request->handyman_order_id,
                        'payment_transaction_id' => $response['trans-id'],
                        'payment_transaction' => NULL,
                        'request_status' => 1,
                        'user_id' => $request->type == "USER" ? $request->user('api')->id : NULL,
                        'driver_id' => $request->type == "DRIVER" ? $request->user('api-driver')->id : NULL,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }else{
                    $trans = DB::table('transactions')->where(['request_status' => 1,'payment_transaction_id' => $request->trans_id])->first();
                    if (!empty($trans)){
                        DB::table('transactions')
                            ->where(['payment_transaction_id' => $request->trans_id])
                            ->update(['request_status' => 2, 'payment_transaction' => json_encode($response), 'updated_at' => date('Y-m-d H:i:s')]);
                    }
                }
                DB::commit();
                return $this->successResponse($response['message'], ['request_id' => $response['request-id'], 'trans_id' => $response['trans-id']]);
            }else{
                DB::rollBack();
                $message = !empty($response) && isset($response['message']) ? $response['message'] : "Something Went Wrong!";
                return $this->failedResponse($message);
            }
        }catch (\Exception $exception){
            DB::rollBack();
            throw  new \Exception($exception->getMessage());
        }
    }
}
