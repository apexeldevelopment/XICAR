<?php

namespace App\Http\Controllers\PaymentMethods;

use App\Http\Controllers\PaymentSplit\StripeConnect;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PaymentOptionsConfiguration;
use DB;
use App\Models\TaxiCompany;
use App\Models\Transaction;
use App\Http\Controllers\Helper\WalletTransaction;

class StripeCardPayment extends Controller
{
    public function checkout(Request  $request){
        $user = null;
        $key = "";
        $output=[];
        if($request->for == 'taxicompany'){
            $user = get_taxicompany();
            $key = "taxicompany_id";
        }
        DB::beginTransaction();
        try {
            $payment_config = PaymentOptionsConfiguration::where([['payment_option_id', '=', 1], ['merchant_id', '=', $user->merchant_id]])->first();
            if (empty($payment_config) > 0)
                return "failed";
            
            $stripe = new \Stripe\StripeClient($payment_config->api_secret_key);
            $amt = $request->value;
            $currency = $request->currency;
            
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => (float)$amt*100,
                'currency' => $currency,
                'payment_method_types' => ['card']
                // 'automatic_payment_methods' => [
                //     'enabled' => true,
                // ],
            ]);
            
            if(!empty($paymentIntent->client_secret)){
                    $trans = new Transaction();
                    $trans->$key = $user->id;
                    $trans->merchant_id = $user->merchant_id;
                    $trans->payment_transaction_id = $paymentIntent->client_secret;
                    $trans->amount = $amt;
                    $trans->payment_option_id = $payment_config->payment_option_id;
                    $trans->request_status = 1;
                    $trans->payment_mode = "Card Payment";
                    $trans->status_message = 'PENDING';
                    $trans->created_at = date('Y-m-d H:i:s');
                    $trans->updated_at = date('Y-m-d H:i:s');
                    $trans->save();
            }
            $output = [
                'clientSecret' => $paymentIntent->client_secret,
            ];
        } catch (\Exception $e) {
             DB::rollBack();
            return $e->getMessage();
        }
        DB::commit();
        return  json_encode($output);
    }
    
    public function stripeSuccess(Request $request){

        $user = null;
        $for = "";
        $message = "";
        DB::beginTransaction();
        try{
            $payment_transaction_id = $request->payment_intent_client_secret;
            $payment_intent = $request->payment_intent;
            $transaction = DB::table('transactions')->where("payment_transaction_id", $payment_transaction_id)->first();
            if(!empty($transaction->taxicompany_id)){
                $user = TaxiCompany::find($transaction->taxicompany_id);
                $for = 'taxicompany';
            }
            $payment_config = PaymentOptionsConfiguration::where([['payment_option_id', '=', 1], ['merchant_id', '=', $user->merchant_id]])->first();
            $stripe = new \Stripe\StripeClient($payment_config->api_secret_key);
            $retrieved_payment = $stripe->paymentIntents->retrieve($payment_intent, []);
            
            switch ($retrieved_payment->status) {
                case "succeeded":
                    if($for == 'taxicompany'){
                        $trans = Transaction::where("payment_transaction_id", $payment_transaction_id)->first();
                        $trans->request_status = 2;
                        $trans->payment_transaction = $retrieved_payment;
                        $trans->status_message = $retrieved_payment->status;
                        $trans->save();
                        WalletTransaction::TaxiComapnyWalletCredit($user->id,$trans->amount,2,$retrieved_payment->client_secret,"ONLINE_PAYMENT_STRIPE");
                    }
                    $message= $retrieved_payment->status;
                  break;
                case "processing":
                  $message= "Your payment is processing.";
                  break;
                case "requires_payment_method":
                  $message= "Your payment was not successful, please try again.";
                  break;
                default:
                  $message= "Something went wrong.";
                  break;
             }
        }
        catch(\Exception $e){
            DB::rollBack();
            return $e->getMessage();
        }
        DB::commit();
        return view('taxicompany.random.success', compact('message'));
        
    }


}