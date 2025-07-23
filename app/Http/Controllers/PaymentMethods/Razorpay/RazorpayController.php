<?php

namespace App\Http\Controllers\PaymentMethods\Razorpay;

use App\Models\Driver;
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

class RazorpayController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getRazorPayConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'RAZORPAY')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }
    
    public function createOrder($request, $payment_option_config, $calling_from){
        try{
            $keyId = $payment_option_config->api_public_key;
            $secretKey = $payment_option_config->api_secret_key;
            $currency = 'INR';
            $status = 3;
            if($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
                $id = $user->id;
                $status = 2;
                $email= $user->email;
                $name = $user->first_name.' '.$user->last_name;
                $phone = $user->phoneNumber;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }
            else{
                $user = $request->user('api');
                $id = $user->id;
                $status = 1;
                $email= $user->email;
                $name = $user->first_name.' '.$user->last_name;
                $phone = $user->UserPhone;
                $merchant_id = $user->merchant_id;
                $lang = isset($user->Country->default_language) ? $user->Country->default_language : 'en';
            }

            $payment_option = $this->getRazorpayConfig($merchant_id);
            $transaction_id = 'TRANS'.$id.'_'.time();
            $reference = 'REF_'.time();
            $amount = $request->amount * 100;  //for convert from paisa to rupee
            $data = [
                'currency'=> $currency,
                'amount'=> $amount,
                'receipt'=>$reference,
                'partial_payment'=> true,
                'first_payment_min_amount'=> 1   //in rupee
            ];

            
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.razorpay.com/v1/orders',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                ),
                CURLOPT_USERPWD => $keyId . ":" . $secretKey,
            ));
            
            
            $response = json_decode(curl_exec($curl));
            \Log::channel('razor_pay')->emergency(['response'=> $response]);
            curl_close($curl);
            if(isset($response->id) && $response->status == "created") {
                DB::table('transactions')->insert([
                    'user_id' => $calling_from == "USER" ? $id : NULL,
                    'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
                    'merchant_id' => $merchant_id,
                    'payment_transaction_id'=> $transaction_id,
                    'amount' => $amount,
                    'payment_option_id' => $payment_option->payment_option_id,
                    'request_status'=> 1,
                    'status'=>$status,
                    'reference_id'=> $response->id,
                    'status_message'=> 'PENDING',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $order_id = $response->id;
                $string_file = $this->getStringFile($merchant_id);
                DB::commit();
        
                return [
                    'name'=> $name,
                    'email'=> $email,
                    'phone'=> $phone,
                    'amount'=>(string)$amount,
                    'currency'=>'INR',
                    'order_id'=> $response->id,
                    'transaction_id'=> $transaction_id,
                    'public_key'=>$keyId,
                    'success_url' => route('razorpay-success'),
                    'fail_url' => route('razorpay-fail'),
                ];
                
            }else{
                if(isset($response->error) && isset($response->error->description)){
                    throw new \Exception($response->error->description);
                }else{
                    throw new \Exception('Something went wrong');
                }
            }
        }catch(\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        
        return [
                    'order_id'=> "",
                    'transaction_id'=> $transaction_id,
                    'success_url' => route('razorpay-success'),
                    'fail_url' => route('razorpay-fail'),
                ];
        
        

        
    }
    
    // public function webview(Request $request){
    //     $order_id = $request->orderId;
    //     $amount = $request->amount;
    //     $currency = 'INR';
    //     $userId = $request->user;
    //     if($request->status == 1){
    //         $user = User::find($userId);
    //     }else{
    //         $user = Driver::find($userId);
    //     }
        
    //         $merchant = $user->Merchant;
        
    //     $payment_option_id = $request->payment_option;
    //     $data=[
    //         'order_id'=> $order_id,
    //         'amount'=>$amount,
    //         'user_name'=> $user->first_name .' '. $user->last_name,
    //         'email'=> $user->email,
    //         'business_name'=> $merchant->BusinessName,
    //         'address'=> $merchant->merchantAddress,
    //         // 'logo'=> get_image($merchant->BusinessLogo,'business_logo', $merchant->id, true)
    //     ];
        
    //     return view('payment.Razorpay.razorpay',['data'=>$data]);
    // }
    
    public function RazorpayCallback(Request $request,$merchantId){
        \Log::channel('razor_pay')->emergency(['request'=> $request->all(),'merchant'=> $merchantId]);
        if(isset($request['razorpay_payment_id'])){
                $payment_option = $this->getRazorpayConfig($merchantId);
                $keyId = $payment_option->api_public_key;
                $secretKey = $payment_option->api_secret_key;
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://api.razorpay.com/v1/payments/'.$request['razorpay_payment_id'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json'
                    ],
                    CURLOPT_USERPWD => $keyId . ":" . $secretKey, // Basic Authentication
                ]);
                
                $response = json_decode(curl_exec($curl),true);
                \Log::channel('razor_pay')->emergency(['response'=> $response,'merchant'=> $merchantId]);
                curl_close($curl);
                if(isset($response) && isset($response['status']) && $response['status'] == 'captured'){
                     DB::table('transactions')
                    ->where(['reference_id' => $request['razorpay_order_id']])
                    ->update(['request_status' => 2, 'payment_transaction' => json_encode($response), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'SUCCESS']);
                    
                    return redirect()->route('razorpay-success');
                }else{
                    DB::table('transactions')
                    ->where(['reference_id' => $request['razorpay_order_id']])
                    ->update(['request_status' => 3, 'payment_transaction' => json_encode($response), 'updated_at' => date('Y-m-d H:i:s'),'status_message'=> 'FAILED']);
                    
                    return redirect()->route('razorpay-fail');
                }
                
        }
    }
    
    public function paymentStatus($request, $paymentConfig){
        $transactionId = $request->transaction_id; 
        $transaction_table =  DB::table("transactions")->where('payment_transaction_id', $transactionId)->first();
        $payment_status =   $transaction_table->request_status == 2 ?  true : false;
        if($transaction_table->request_status == 1)
        {
            $request_status_text = "processing";
            $transaction_status = 1;
        }
        else if($transaction_table->request_status == 2)
        {
            $request_status_text = "success";
            $transaction_status = 2;
        }
        else
        {
            $request_status_text = "failed";
            $transaction_status = 3;
        }
        return ['payment_status' => $payment_status, 'request_status' => $request_status_text,'transaction_status'=>$transaction_status];
    }
    
    public function Success(Request $request)
    {
        \Log::channel('razor_pay')->emergency($request->all());
        echo "<h1>Success</h1>";
    }

    public function Cancel(Request $request)
    {
        \Log::channel('razor_pay')->emergency($request->all());
        echo "<h1>Failed</h1>";
    }
    
}