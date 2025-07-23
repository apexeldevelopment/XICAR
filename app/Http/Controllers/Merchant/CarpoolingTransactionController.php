<?php

namespace App\Http\Controllers\Merchant;

use App\Models\UserCashout;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use App\Models\Onesignal;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Helper\WalletTransaction;
use DB;


class CarpoolingTransactionController extends Controller
{
    use MerchantTrait;
    public function index(){
        $merchant_id = get_merchant_id('false');
        $user_cashout = UserCashout::with('User')->where('merchant_id', $merchant_id)->latest()->paginate(20);

        return view('merchant.carpooling-transaction.user-transaction', compact('user_cashout'));
    }

    public function edit($id){
        $user_cashout=UserCashout::findOrFail($id);
        return view('merchant.carpooling-transaction.user-transaction-save',compact('user_cashout'));
    }

    public function update(Request $request,$id){

        $merchant_id=  get_merchant_id('false');
        $validator = Validator::make($request->all(), [
            'status' => 'required',Rule::in(['1', '2']),
            'action_by' => 'required',
            'transaction_id' => 'required|Unique:user_cashouts',
            'comment' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withErrors($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user_cashout=UserCashout::find($id);
            $user_cashout->cashout_status = $request->status;
            $user_cashout->action_by = $request->action_by;
            $user_cashout->comment = $request->comment;
            $user_cashout->transaction_id = $request->transaction_id;
            $user_cashout->save();
            $user=User::find($user_cashout->user_id);
            //p($user->Country->isoCode);
            $string_file = $this->getStringFile( $user_cashout->merchant_id);
            if($request->status==1){
            $message = trans("$string_file.account_transfer",['amount' => $user_cashout->amount]);
            $title = 'Payment Success Notification';
            $data = array('notification_type' => 'PAYMENT_SUCCESS', 'segment_type' => "PAYMENT_SUCCESS",'segment_data'=>[]);
            $arr_param = array(
            'user_id' => $user_cashout->user_id,
            'data'=>$data,
            'message'=>$message,
            'merchant_id'=>$user_cashout->merchant_id,
            'title' => $title,
             );
            Onesignal::UserPushMessage($arr_param);
            }else{
                $paramArray = array(
                'user_id' => $user_cashout->user_id,
                'booking_id' => NULL,
                'amount' => $user_cashout->amount,
                'narration' => 2,
                'platform' => 2,
                'payment_request'=>'Refund Amount',
                'display_payment'=>'Refund Amount',
                'payment_method' => 2,
                'transaction_id' => 2,
                'transaction_type'=>3,
                );
            WalletTransaction::UserWalletCredit($paramArray);
            $message = trans("$string_file.account_refund",['amount' =>$user_cashout->amount]);
            $title = 'Payment Success Notification';
            $data = array('notification_type' => 'PAYMENT_SUCCESS', 'segment_type' => "PAYMENT_SUCCESS",'segment_data'=>[]);
            $arr_param = array(
            'user_id' => $user_cashout->user_id,
            'data'=>$data,
            'message'=>$message,
            'merchant_id'=>$user_cashout->merchant_id,
            'title' => $title,
             );
             Onesignal::UserPushMessage($arr_param);
            }
        }
        catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
            return redirect()->back()->with('error', trans('common.error'));
        }
        DB::commit();
        return redirect()->route('merchant.carpool.user.transaction')->with('success', 'Cashout edited successfully');

    }
}
