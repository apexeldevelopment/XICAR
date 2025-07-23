<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Merchant;
use App\Models\PaymentOption;
use App\Models\PaymentOptionTranslation;
use App;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;
use DB;
use App\Models\BonsBankToBankQrGateway;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\Transaction;
use App\Models\Onesignal;

class PaymentOptionController extends Controller
{
    use ImageTrait,MerchantTrait;
    
    public function index()
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $payment = PaymentOption::whereHas('PaymentOptionsConfiguration',function($q) use($merchant){
            $q->where('merchant_id',$merchant->id);
        })->with(['PaymentOptionTranslation' => function($query) use ($merchant_id) {
            $query->where('merchant_id', $merchant_id);
        }])->get();
        // $payment = $merchant->PaymentOption;
        return view('merchant.payment_options.index', compact('payment','merchant'));
    }
    
    public function edit($id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $payment = PaymentOption::where('id', $id)
        ->whereHas('PaymentOptionsConfiguration',function($q) use($merchant){
            $q->where('merchant_id',$merchant->id);
        })->with(['PaymentOptionTranslation' => function($query) use ($merchant_id) {
            $query->where('merchant_id', $merchant_id);
        }])->first();
        return view('merchant.payment_options.edit', compact('payment','merchant'));
    }
    
    public function update($id,Request $request){
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $request->validate([
            'payment_option_name' => 'required'
        ]);
        
        PaymentOptionTranslation::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'payment_option_id' => $id
        ], [
            'name' => $request->payment_option_translation,
        ]);

        return redirect()->route('merchant.payment-option')->withSuccess(trans("$string_file.saved_successfully"));
    }
    
    public function BonsPaymentGatewayApprovalRequest(Request $request){
         $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        
        $bonsQrPayment = BonsBankToBankQrGateway::where('merchant_id',$merchant_id)->first();
        
        $transactions = DB::table('transactions')->where('merchant_id',$merchant_id)->where('reference_id',$bonsQrPayment->id)->get();
        
        return view('merchant.bons-payments.approveReject',compact('bonsQrPayment','transactions'));
    }
    
    public function BonsApproval(Request $request,$id){
        $transaction = $transaction = Transaction::find($id);
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        if(!empty($transaction) && $transaction->request_status == 1){
            $transaction->request_status = 2;
            $transaction->save();
            
            $receipt = "Application : " . $transaction->id;
            $paramArray = array(
                        'booking_id' => NULL,
                        'amount' => $transaction->amount,
                        'narration' => 2,
                        'platform' => 2,
                        'payment_method' => 2,
                        'receipt' => $receipt,
                        'transaction_id' => $transaction->id,
                    );

                    if($transaction->status == 1){
                        $paramArray['user_id'] = $transaction->user_id;
                        WalletTransaction::UserWalletCredit($paramArray);
                    }elseif($transaction->status == 2){
                        $paramArray['driver_id'] = $transaction->driver_id;
                        WalletTransaction::WalletCredit($paramArray);
                    }
            return redirect()->back()->withSuccess(trans("$string_file.sent_successfully"));
        }
        
         return redirect()->back()->withError(trans("$string_file.already_approved"));
    }
    
    public function BonsRejectRequest(Request $request){
        $id = $request->input('id');
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $transaction = Transaction::find($id);
        if(!empty($transaction) && $transaction->request_status == 1){
            $transaction->request_status = 3;
            $transaction->save();
            
            $msg = trans("$string_file.bons_rejected");
            $title = trans("$string_file.bons_rejected");
            $data['notification_type'] = "BONS_REJECTED";
            $data['segment_type'] = "";
            $data['segment_data'] = [];
            if($transaction->status == 1){
                $arr_param = ['user_id' => $transaction->user_id, 'data' => $data, 'message' => $msg, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => ''];
                Onesignal::UserPushMessage($arr_param);
            }else{
                $arr_param = ['driver_id' => $transaction->driver_id, 'data' => $data, 'message' => $msg, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => ''];
                Onesignal::DriverPushMessage($arr_param);
            }
            
            return redirect()->back()->withSuccess(trans("$string_file.rejected_successfully"));
        }
    }
    
}