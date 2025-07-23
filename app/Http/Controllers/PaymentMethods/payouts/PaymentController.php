<?php

namespace App\Http\Controllers\PaymentMethods\payouts;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentMethods\MonetbilController;
use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Booking;
use App\Models\DriverCard;
use App\Models\DriverWalletTransaction;
use App\Models\Onesignal;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserDevice;
use App\Traits\ApiResponseTrait;
use App\Models\UserWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\Driver;
use Illuminate\Validation\Rule;
use Kabangi\Mpesa\Init as Mpesa;
use DB;
use DateTime;
use PaymentTokenGenerate as TwoCTwoPPaymentGateway;

class PaymentController extends Controller
{
    public function withdrawal(Request $request){
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'phonenumber'=>'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $amount = sprintf("%0.2f", $request->amount);
        $currency = strtoupper($user->Country->isoCode);
        $processing_id = time();   
        $user_phone = str_replace('+', "", $request->phonenumber);
        $notify_url=route('api.monetbill_payout_url');
        $data=array(
          'service' => '7kUosGFBYiu29baP4ToMuRGmRsE33zq8',
          'service_secret'=>'1WzpPm0tL5dby8h0va2ZqYuIOwuXDwgWmcURdp4Diif0NwOmWYI6WuWoPycgglfX',
          'processing_number'=> $processing_id,
          'payout_notification_url'=>$notify_url,
          'phonenumber'=>$user_phone,
          'amount' =>   $amount ,
          //'currency'=> $currency,
          'operator'=>'CM_MTNMOBILEMONEY',
        );
       // p($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.monetbil.com/v1/payouts/withdrawal');
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
           // p(curl_exec($ch));
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $jsonArry = json_decode($json, true);
            return $jsonArry;
    }
    public function monetbillNotify(Request $request)
    {
        DB::table('monetbill_transactions')->where(['processing_id' => $request->processing_number])
            ->update([
                'cashout_transaction_id' => $request->transaction_id,
                'status' => $request->sucess,
                'updated_at' => Carbon::now()
            ]);
       
    }

}