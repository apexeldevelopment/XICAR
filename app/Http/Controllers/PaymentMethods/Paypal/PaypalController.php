<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 19/9/23
 * Time: 4:58 PM
 */

namespace App\Http\Controllers\PaymentMethods\Paypal;


use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Carbon;

class PaypalController extends Controller
{
    public function createPaymentUrl($request, $paymentOption, $calling_from){
        try{
            if ($calling_from == 'USER') {
                $user = $request->user('api');
            } else {
                $user = $request->user('api-driver');
            }
            $conversion_id = $paymentOption->auth_token; // currency conversion api id
            // $conversion_id = $paymentOption->api_secret_key; // secret key
            $client_id = $paymentOption->api_public_key; // open exchange rate id

            $amount = sprintf("%0.2f", $request->amount);
            $currency = strtoupper($request->currency);
            $paypal_order_id = time();

            DB::table('paypal_transactions')->insert([
                'user_id' => $calling_from == 'USER' ? $user->id : NULL,
                'driver_id' => $calling_from == 'DRIVER' ? $user->id : NULL,
                'booking_id' => $request->booking_id,
                'order_id' => $request->order_id,
                'handyman_order_id' => $request->handyman_order_id,
                'paypal_order_id' => $paypal_order_id,
                'amount' => $currency . " " . $amount,
                'created_at' => Carbon::now() 
            ]);

            $success_url = route('api.paypal_success_url');
            $fail_url = route('api.paypal_fail_url');
            $notify_url = route('api.paypal_notify_url');

            $supported_codes = ['INR', 'USD', 'AUD', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB'];
            // BRL, MYR and INR support currencies for their respective countries' businsess account

            if (in_array($currency, $supported_codes)) {
                $noSupportCurrencies = ['HUF', 'JPY', 'TWD'];
                if (in_array($currency, $noSupportCurrencies)) {
                    $amount = round($amount);
                }
            } else {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://openexchangerates.org/api/latest.json?app_id=" . $conversion_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                ));
                $response = curl_exec($curl);
                curl_close($curl);
                $response = json_decode($response);
                if (!empty($response->error) && $response->error == 1) {
                    throw new \Exception($response->description);
                } else {
                    if (!$response->rates->$currency) {
                        throw new \Exception("Invalid OpenExchange Account Key");
                    }
                    $amount = sprintf("%0.2f", round((1 / $response->rates->$currency) * $amount, 2));
                    $currency = 'USD';
                }
            }
            $return_data = [
                'web_view_data' => route('paypalview') . '?client_id=' . $client_id . '&amount=' . $amount . '&currency=' . $currency . '&success_url=' . $success_url . '&fail_url=' . $fail_url . '&notify_url=' . $notify_url . '&order_id=' . $paypal_order_id
            ];
            return $return_data;
        }catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }
    }
}
