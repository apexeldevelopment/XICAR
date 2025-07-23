<?php

namespace App\Http\Controllers\PaymentMethods;

use App\Http\Controllers\PaymentSplit\StripeConnect;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Stripe\Service\PriceService;
use Stripe\Service\ProductService;

class StripeController extends Controller
{
    protected $StripeKey;

    public function __construct($StripeKey = null)
    {
        $this->StripeKey = $StripeKey;
        \Stripe\Stripe::setApiKey($this->StripeKey);
    }


    public function CreateCustomer($token = null, $email = null,$connectedAccId = null)
    {
        try {
            if($connectedAccId){
                $Customer = \Stripe\Customer::create([
                    "description" => $email,
                    "source" => $token
                ],
                [
                    'stripe_account' => $connectedAccId, // Pass the connected account ID as a header
                ]);
            }else{
                $Customer = \Stripe\Customer::create([
                    "description" => $email,
                    "source" => $token
                ]);
            }
            return array('id' => $Customer->id);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function ListCustomer($cardObj,$connectedAccId = null)
    {
        $c = array();
        foreach ($cardObj as $card) {
            try {
                $Customer = \Stripe\Customer::allSources($card->token,[
                    'object' => 'card', // Specify the type of sources you want to retrieve, e.g., 'card'
                    ],
                    [
                        'stripe_account' => $connectedAccId, // Pass the connected account ID
                    ]);
                $c[] = array(
                    'card_id' => $card->id,
                    'card_number' => $Customer['data'][0]['last4'],
                    'card_type' => $Customer['data'][0]['brand'],
                    'exp_month' => $Customer['data'][0]['exp_month'],
                    'exp_year' => $Customer['data'][0]['exp_year']
                );
//                $Customer = \Stripe\Customer::retrieve($card->token);
//                $c[] = array(
//                    'card_id' => $card->id,
//                    'card_number' => $Customer['sources']['data'][0]['last4'],
//                    'card_type' => $Customer['sources']['data'][0]['brand'],
//                    'exp_month' => $Customer['sources']['data'][0]['exp_month'],
//                    'exp_year' => $Customer['sources']['data'][0]['exp_year']
//                );
            } catch (\Exception $e) {
            }
        }
        return $c;

    }

    public function CustomerDetails($card,$connectedAccId = null)
    {
        $c = null;
        try {
            if($connectedAccId){
                $Customer = \Stripe\Customer::allSources($card->token,[
                'object' => 'card', // Specify the type of sources you want to retrieve, e.g., 'card'
                ],
                [
                    'stripe_account' => $connectedAccId, // Pass the connected account ID
                ]);
            }else{
                $Customer = \Stripe\Customer::allSources($card->token);
            }
            $c = array(
                'card_id' => $card->id,
                'card_number' => $Customer['data'][0]['last4'],
                'card_type' => $Customer['data'][0]['brand'],
                'exp_month' => $Customer['data'][0]['exp_month'],
                'exp_year' => $Customer['data'][0]['exp_year']
            );
//            $Customer = \Stripe\Customer::retrieve($card->token);
//            $c = array(
//                'card_number' => $Customer['sources']['data'][0]['last4'],
//                'card_type' => $Customer['sources']['data'][0]['brand'],
//                'exp_month' => $Customer['sources']['data'][0]['exp_month'],
//                'exp_year' => $Customer['sources']['data'][0]['exp_year']
//            );
        } catch (\Exception $e) {
        }
        return $c;

    }

    public function DeleteCustomer($CustomerID)
    {
        try {
            $cu = \Stripe\Customer::retrieve($CustomerID);
            $cu->delete();
        } catch (\Exception $exception) {
        }

    }

    public function Charge($amount = 0, $currency = null, $CustomerID = null, $email = null,$connectedAccId = null,$order_id=null)
    {
        try {
            if($connectedAccId){
                $charge = \Stripe\Charge::create([
                    "amount" => $amount * 100,
                    "currency" => $currency,
                    "customer" => $CustomerID,
                    "description" => $email
                ],[
                    'stripe_account' => $connectedAccId, // Pass the connected account ID as a header
                ]);
            }else{
                $charge = \Stripe\Charge::create([
                    "amount" => $amount * 100,
                    "currency" => $currency,
                    "customer" => $CustomerID,
                    "description" => $email
                ]);
            }
            
            return array('charge_id' => $charge['id']);
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function Connect_charge($amount = 0, $currency = null, $token , $driver_total_payable_amount , $driver_sc_account_id, $merchant_id,$connectedAccId = null,$order_id)
    {
        try {
            if($connectedAccId){
                $charge = StripeConnect::charge_amount($driver_total_payable_amount,$amount,$driver_sc_account_id,$token,$currency, $merchant_id,$connectedAccId,$order_id);
                return array('charge_id' => $charge->id);
            }else{
                $charge = StripeConnect::charge_amount($driver_total_payable_amount,$amount,$driver_sc_account_id,$token,$currency, $merchant_id,$connectedAccId = null,$order_id);
                return array('charge_id' => $charge->id);
            }
        }
        catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function CreatePaymentIntent($amount, $currency){
        try {
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amount*100,
                'currency' => $currency,
            ]);
            $client_secret = $intent->client_secret;
            return $client_secret;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function GetWebViewUrl(){
        \Stripe\Stripe::setApiKey('sk_test_51JodNsET7ySD4ddvBzrKHNCTTeAYX5N1OscQCKBYUOgj4BfRfK9zNvEQxJ2iGRp2YyKQDHMxUl3Y1sBLiwnuHZRI00lz6UOLxQ');
        $success_url = route('stripe.success');
        $cancel_url = route('stripe.cancel');

        $stripe = new \Stripe\StripeClient('sk_test_51JodNsET7ySD4ddvBzrKHNCTTeAYX5N1OscQCKBYUOgj4BfRfK9zNvEQxJ2iGRp2YyKQDHMxUl3Y1sBLiwnuHZRI00lz6UOLxQ');

        // $product = ProductService::create(['name' => 'T-shirt']);
        $product = $stripe->products->create(['name' => 'T-shirt']);
        $price = $stripe->prices->create([
            'product' => $product->id,
            'unit_amount' => 200,
            'currency' => 'myr',
        ]);

        // $price = PriceService::create([
        //     'product' => $product->id,
        //     'unit_amount' => 100,
        //     'currency' => 'usd',
        // ]);

        $checkout_session = \Stripe\Checkout\Session::create([
            'line_items' => [[
                # Provide the exact Price ID (e.g. pr_1234) of the product you want to sell
                'price' => $price->id,
                'quantity' => 1,
            ]],
            'payment_method_types' => ['card','alipay','grabpay','fpx'],
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
        ]);

        dd($checkout_session);
    }

    public function stripeSuccess(Request $request){
        dd($request->all());
    }

    public function stripeCancel(Request $request){
        dd($request->all());
    }
}