<?php

namespace App\Http\Controllers\PaymentMethods;


use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Controllers\PaymentMethods\AamarPay\AamarPayController;
use App\Http\Controllers\PaymentMethods\FlutterWave\FlutterWaveStandard;
use App\Http\Controllers\PaymentMethods\LigdiCash\LigdiCashPaymentController;
use App\Http\Controllers\PaymentMethods\OrangeMoney\OrangeMoneyB2B;
use App\Http\Controllers\PaymentMethods\UniPay\UniPayController;
use App\Http\Controllers\PaymentMethods\Tranzak\TranzakController;
use App\Http\Controllers\PaymentMethods\OrangeMoneyWeb\OrangeMoneyWebController;
use App\Http\Controllers\PaymentMethods\DibsyPayment\DibsyPaymentController;
use App\Http\Controllers\PaymentMethods\CashFree\CashFreeController;
use App\Http\Controllers\PaymentMethods\CxPay\CxPayController;
use App\Http\Controllers\PaymentMethods\CashPay\CashPayController;
use App\Http\Controllers\PaymentMethods\Clover\CloverController;
use App\Http\Controllers\PaymentMethods\Hub2\Hub2Controller;
use App\Http\Controllers\PaymentMethods\Kushki\KushkiController;
use App\Http\Controllers\PaymentMethods\Midtrans\MidtransController;
use App\Http\Controllers\PaymentMethods\PagueloFacil\PagueloFacil;
use App\Http\Controllers\PaymentMethods\PayHere\PayHereController;
use App\Http\Controllers\PaymentMethods\PayMaya\PayMayaController;
use App\Http\Controllers\PaymentMethods\Paypal\PaypalController;
use App\Http\Controllers\PaymentMethods\PayPhone\PayPhoneController;
use App\Http\Controllers\PaymentMethods\Payriff\Payriff;
use App\Http\Controllers\PaymentMethods\Payweb3\PaywebController;
use App\Http\Controllers\PaymentMethods\Pesepay\PesepayController;
use App\Http\Controllers\PaymentMethods\SDGExpress\SDGExpressController;
use App\Http\Controllers\PaymentMethods\TelebirrPay\TelebirrPayController;
use App\Http\Controllers\PaymentMethods\TelebirrPay\TelebirrPayNewController;
use App\Http\Controllers\PaymentMethods\Wave\WaveController;
use App\Http\Controllers\PaymentMethods\Tingg\TinggController;
use App\Http\Controllers\PaymentMethods\WiPay\WiPayController;
use App\Http\Controllers\PaymentMethods\Binance\BinanceController;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingTransaction;
use App\Models\BusinessSegment\Order;
use App\Models\CorporateWalletTransaction;
use App\Models\Country;
use App\Models\HandymanOrder;
use App\Models\PaymentOption;
use App\Models\Transaction;
use App\Models\UserCard;
use App\Models\UserWalletTransaction;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Runner\Exception;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Http\Controllers\PaymentMethods\PayBox\PayBoxController;
use App\Http\Controllers\PaymentMethods\Mercado\MercadoController;
use App\Http\Controllers\PaymentMethods\PaygateGlobal\PaygateGlobalController;
use App\Http\Controllers\PaymentMethods\Square\SquareController;
use App\Http\Controllers\PaymentMethods\DPO\DpoController;
use App\Http\Controllers\PaymentMethods\TouchPay\TouchPayController;
use App\Http\Controllers\PaymentMethods\Opay\OpayController;
use App\Http\Controllers\PaymentMethods\PinPayment\PinPayment;
use App\Http\Controllers\PaymentMethods\SamPay\SamPayController;
use App\Http\Controllers\PaymentMethods\PayGo\PayGoController;
use App\Http\Controllers\PaymentMethods\BeyonicPayment\BeyonicController;
use App\Http\Controllers\PaymentMethods\PayPay\PaypayController;
use Illuminate\Support\Str;
use App\Http\Controllers\PaymentMethods\KPay\KPayController;
use App\Http\Controllers\PaymentMethods\Waafi\WaafiController;
use App\Http\Controllers\PaymentMethods\Waafi\WaafiPaymentController;
use App\Http\Controllers\PaymentMethods\Pagadito\PagaditoController;
use App\Http\Controllers\PaymentMethods\Pesapal\PesapalController;
use App\Http\Controllers\PaymentMethods\Bog\BogPaymentController;
use App\Http\Controllers\PaymentMethods\PayNow\PayNowController;
use App\Http\Controllers\PaymentMethods\FastHub\FastHubController;
use App\Http\Controllers\PaymentMethods\Cacpay\CacPaymentController;
use App\Http\Controllers\PaymentMethods\PeachPayment\PeachServerToServer;
use App\Http\Controllers\PaymentMethods\Mgurush\MgurushPay;
use App\Http\Controllers\PaymentMethods\OrangeMoney\OrangeMoneyCoreController;
use App\Http\Controllers\PaymentMethods\Maxicash\MaxicashController;
use App\Http\Controllers\PaymentMethods\CRDB\CrdbPay;
use App\Http\Controllers\PaymentMethods\PayPay\PaypayAfricaController;
use App\Http\Controllers\PaymentMethods\Khalti\khaltiController;
use App\Http\Controllers\PaymentMethods\PawaPay\PawaPayController;
use App\Http\Controllers\PaymentMethods\BudPay\BudPayController;
use App\Http\Controllers\PaymentMethods\PaySuite\PaySuiteController;
use App\Http\Controllers\PaymentMethods\Tap\TapPaymentController;
use App\Http\Controllers\PaymentMethods\RevolutPay\RevolutPaymentController;
use App\Http\Controllers\PaymentMethods\UbPay\UbPayController;
use App\Http\Controllers\PaymentMethods\CashPlus\CashPlusController;
use App\Http\Controllers\PaymentMethods\Razorpay\RazorpayController;
use App\Http\Controllers\PaymentMethods\IMBankMpesa\IMBankController;
use App\Http\Controllers\PaymentMethods\ESewa\ESewaPaymentController;
use App\Http\Controllers\PaymentMethods\FlexPay\FlexPayController;
use App\Http\Controllers\PaymentMethods\Aubpay\AubPayController;
use App\Http\Controllers\PaymentMethods\RemitaPay\RemitaPayController;
use App\Http\Controllers\PaymentMethods\EasyPay\EasyPayController;
use App\Http\Controllers\PaymentMethods\XendItPay\XendItPayController;

class Payment
{
    //    public function MakePayment($bookingId, $payment_method_id, $amount, $userId, $card_id = null, $currency = null, $booking_transaction = null, $driver_sc_account_id = null)
    use ApiResponseTrait, MerchantTrait;
    public function MakePayment($array_param)
    {
        //        $array_param = array(
        //            'booking_id' => 'bookingId',
        //            'order_id' => 'order_id',
        //            'handyman_order_id' => 'handyman_order_id',
        //            'payment_method_id' => 'payment_method_id',
        //            'amount' => 'amount',
        //            'user_id' => 'user_id',
        //            'card_id' => 'card_id',
        //            'currency' => 'currency',
        //            'booking_transaction' => 'booking_transaction',
        //            'driver_sc_account_id' => 'driver_sc_account_id'
        //        );
        $merchant_id = isset($array_param['merchant_id']) ? $array_param['merchant_id'] : NULL;
        $string_file = $this->getStringFile($merchant_id);
        $bookingId = isset($array_param['booking_id']) ? $array_param['booking_id'] : NULL;
        $order_id = isset($array_param['order_id']) ? $array_param['order_id'] : NULL;
        $handyman_order_id = isset($array_param['handyman_order_id']) ? $array_param['handyman_order_id'] : NULL;
        $payment_method_id = isset($array_param['payment_method_id']) ? $array_param['payment_method_id'] : NULL;
        $amount = isset($array_param['amount']) ? $array_param['amount'] : NULL;
        $userId = isset($array_param['user_id']) ? $array_param['user_id'] : NULL;
        $card_id = isset($array_param['card_id']) ? $array_param['card_id'] : NULL;
        $currency = isset($array_param['currency']) ? $array_param['currency'] : NULL;
        $booking_transaction = isset($array_param['booking_transaction']) ? $array_param['booking_transaction'] : NULL;
        $driver_sc_account_id = isset($array_param['driver_sc_account_id']) ? $array_param['driver_sc_account_id'] : NULL;
        $driver_paystack_account_id = isset($array_param['driver_paystack_account_id']) ? $array_param['driver_paystack_account_id'] : NULL;
        $payment_type = isset($array_param['payment_type']) ? $array_param['payment_type'] : "FINAL";
        $payment_option_id = isset($array_param['payment_option_id']) ? $array_param['payment_option_id'] : NULL;

        // throw error if payment request is calling from user side
        $request_from = isset($array_param['request_from']) ? $array_param['request_from'] : "";
        try {
            switch ($payment_method_id) {
                case "1": // cash
                    $this->UpdateStatus($array_param);
                    $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                    return true;
                    break;
                    // card
                case "2":
                    $card = UserCard::with('PaymentOption')->find($card_id);
                    if (!empty($card)) {
                        $slug = $card->PaymentOption->slug;
                        switch ($slug) {
                            case "STRIPE":
                                $user = User::find($userId);
                                $payment_config = PaymentOptionsConfiguration::where([['payment_option_id', '=', $card->payment_option_id], ['merchant_id', '=', $user->merchant_id]])->first();
                                if (!empty($payment_config) > 0) {;
                                    $newCard = new StripeController($payment_config->api_secret_key);
                                    if ($booking_transaction && $booking_transaction->instant_settlement && $driver_sc_account_id) {
                                        $charge = $newCard->Connect_charge($amount, $currency, $card->token, $booking_transaction->driver_total_payout_amount, $driver_sc_account_id, $user->merchant_id,$payment_config->auth_token,$order_id);
                                    } else {
                                        $charge = $newCard->Charge($amount, $currency, $card->token, $user->email,$payment_config->auth_token,$user);
                                    }
                                    if (is_array($charge)) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "SENANGPAY":
                                $user = User::find($userId);
                                $cardss = UserCard::find($card_id);
                                $name = $user->first_name . "" . $user->last_name;
                                $email = $user->email;
                                $detail = "taxi payment";
                                $phone = $user->UserPhone;
                                $order_id = $bookingId;
                                $amount = $amount * 100;
                                $token = $cardss->token;

                                $payment_option = PaymentOption::where('slug', 'SENANGPAY')->first();
                                $paymentoption = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();

                                $condition = $paymentoption['gateway_condition'];
                                if ($condition == 2) {
                                    $payment_redirect_url = "https://sandbox.senangpay.my/apiv1/pay_cc";
                                } else {
                                    $payment_redirect_url = $paymentoption['payment_redirect_url'];
                                }

                                $curl = curl_init();

                                curl_setopt_array($curl, array(
                                    CURLOPT_URL => $payment_redirect_url,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => "",
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 30,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => "POST",
                                    CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"name\"\r\n\r\n$name\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"email\"\r\n\r\n$email\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"detail\"\r\n\r\n$detail\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"phone\"\r\n\r\n$phone\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"order_id\"\r\n\r\n$order_id\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"amount\"\r\n\r\n$amount\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"token\"\r\n\r\n$token\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
                                    CURLOPT_HTTPHEADER => array(
                                        "Authorization: Basic " . $paymentoption['auth_token'],
                                        "cache-control: no-cache",
                                        "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
                                    ),
                                ));

                                $response = curl_exec($curl);
                                $err = curl_error($curl);

                                curl_close($curl);

                                if ($err) {
                                    echo "cURL Error #:" . $err;
                                } else {
                                    $response = json_decode($response, true);
                                    if ($response['status'] == 1) {
                                        $this->UpdateStatus($array_param);
                                        return response()->json(['result' => $response['status'], 'transaction_id' => $response['transaction_id'], 'order_id' => $response['order_id'], 'amount_paid' => $response['amount_paid'], 'message' => $response['msg'], 'hash' => $response['hash']]);
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                        return response()->json(['result' => $response['status'], 'message' => $response['msg']]);
                                    }
                                }
                                break;
                            case "PAYSTACK":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'PAYSTACK')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentConfig)) {
                                    $booking_transaction_fee = $booking_transaction ?  ($booking_transaction->cancellation_charge_received + $booking_transaction->company_earning) : "";
                                    $payment = new RandomPaymentController();
                                    if ($booking_transaction && $booking_transaction->instant_settlement && $driver_paystack_account_id) {
                                        $charge = $payment->ChargePaystack($amount, $user->Country->isoCode, $card->token, $user->email, $paymentConfig->api_secret_key, $paymentConfig->payment_redirect_url, 'BOOKING', $bookingId ?? $booking_transaction->booking_id, $request_from, $booking_transaction_fee, $driver_paystack_account_id);
                                    } else {
                                        $charge = $payment->ChargePaystack($amount, $user->Country->isoCode, $card->token, $user->email, $paymentConfig->api_secret_key, $paymentConfig->payment_redirect_url, 'BOOKING', $bookingId ? $booking_transaction->booking_id : ($order_id ? $order_id : ($handyman_order_id ? $handyman_order_id : '' )) , $request_from);
                                    }
                                    if (is_array($charge) && array_key_exists('id', $charge)) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                        // return false;
                                        throw new \Exception($charge[0]);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "CIELO":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'CIELO')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentConfig)) {
                                    $userName = $user->first_name . " " . $user->last_name;
                                    $payment = new RandomPaymentController();
                                    $charge = $payment->ChargeCielo($amount, $userName, $card->card_type, $card->token, $paymentConfig->api_secret_key, $paymentConfig->api_public_key, $paymentConfig->payment_redirect_url);
                                    if (is_array($charge)) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "BANCARD":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'BANCARD')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentConfig)) {
                                    // $shopProcessId = uniqid();
                                    $shopProcessId = rand(11111, 99999);
                                    $amount = number_format($amount, 2);
                                    $token = md5('.' . $paymentConfig->api_secret_key . $shopProcessId . 'charge' . $amount . '1');
                                    $payment = new RandomPaymentController();
                                    $charge = $payment->ChargeBancard($paymentConfig->payment_redirect_url, $paymentConfig->api_public_key, $token, $shopProcessId, $amount, $currency, $card->token);
                                    if (is_array($charge) && $charge['result'] == 1) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "DPO":

                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'DPO')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentConfig)) {
                                    $payment = new DpoController();
                                    $charge = $payment->cardPayment("USER", $user, $paymentConfig, $card, $amount);

                                    if (is_array($charge) && $charge['status'] == true) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                }

                                //                                $user = User::find($userId);
                                //                                $payment_option = PaymentOption::where('slug', 'DPO')->first();
                                //                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id','=', $user->merchant_id],['payment_option_id','=', $payment_option->id]])->first();
                                //                                if (!empty($paymentConfig)) {
                                //                                    $payment = new RandomPaymentController();
                                //                                    $charge = $payment->ridePaymentDPO($paymentConfig->auth_token, $amount, $currency, $user->email, $user->first_name, $user->last_name, $user->UserPhone, $card->token);
                                //                                    if (is_array($charge)) {
                                //                                        $this->UpdateStatus($array_param);
                                //                                        return true;
                                //                                    } else {
                                //                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                //                                    }
                                //                                } else {
                                //                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                //                                }
                                break;
                            case "PEACHPAYMENT":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'PEACHPAYMENT')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (empty($paymentConfig)) {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                $charge = new RandomPaymentController();
                                $charge = $charge->peachpayment($paymentConfig->api_secret_key, $paymentConfig->auth_token, $amount, "ZAR", $card->token, true, $userId, $paymentConfig->tokenization_url);
                                if (is_array($charge)) {
                                    $this->UpdateStatus($array_param);
                                    return true;
                                } else {
                                    $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                }
                                break;
                            case "HYPERPAY":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'HYPERPAY')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (empty($paymentConfig)) {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                $charge = new RandomPaymentController();
                                $charge = $charge->hyperPayment($paymentConfig->api_secret_key, $paymentConfig->auth_token, $amount, "SAR", $card->token, true, $userId, $paymentConfig->tokenization_url);
                                if (is_array($charge)) {
                                    $this->UpdateStatus($array_param);
                                    return true;
                                } else {
                                    $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                }
                                break;
                            case "MONERIS":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'MONERIS')->first();
                                $payment_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($payment_config) > 0) {
                                    $storeId = $payment_config->api_secret_key;
                                    $apiToken = $payment_config->auth_token;
                                    $cardToken = $card->token;
                                    $charge = new RandomPaymentController();
                                    $charge = $charge->MonerisMakePayment($userId, $cardToken, $amount, $storeId, $apiToken);
                                    if ($charge['ResponseCode'] == '027' && !empty($charge['DataKey'])) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "EZYPOD":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'EZYPOD')->first();
                                $payment_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($payment_config) > 0) {
                                    $apiKey = $payment_config->api_secret_key;
                                    $loginID = $payment_config->api_public_key;
                                    $paymentMode = $payment_config->payment_redirect_url;
                                    $am = $amount;
                                    $famt = '';
                                    if ($am != '') {
                                        $am2 = str_replace(".", "", $am);
                                        $am3 = strlen($am2);
                                        $len = 12 - $am3;
                                        $amArr = "";
                                        for ($len = 0; $len < $len; $len++) {
                                            $amArr .= "0";
                                        }
                                        $famt = $amArr . $am2;
                                    } else {
                                        $famt = '000000000000';
                                    }
                                    $country = Country::find($user->country_id);
                                    $user_phone = str_replace($country->phonecode, "", $user->UserPhone);
                                    $payArr = array(
                                        "service" => "MOBI_EZYREC_REQ",
                                        "cardToken" => $card->token,
                                        "amount" => $famt,
                                        "mobileNo" => $user_phone
                                    );
                                    $charge = new RandomPaymentController();
                                    $result = $charge->EZYPODMakePayment($apiKey, $loginID, $paymentMode, $payArr);
                                    if ($result) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "CONEKTA":
                                $payment = new RandomPaymentController();
                                $payment_config = PaymentOptionsConfiguration::where([['payment_option_id', '=', $card->payment_option_id], ['merchant_id', '=', $card->User->merchant_id]])->first();
                                if (!empty($payment_config)) {
                                    $arr_data = ['currency' => $currency, 'quantity' => $array_param['quantity'], 'name' => $array_param['order_name'], 'amount' => $amount, 'private_key' => $payment_config->api_secret_key, 'customer_token' => $card->user_token];
                                    $payment_response =  $payment->createConektaOrder($arr_data);
                                    $payment_response = json_decode($payment_response);
                                    if (!empty($payment_response->id) && $payment_response->payment_status == 'paid') {
                                        $array_param['transaction_id'] = $payment_response->id;
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $message = isset($payment_response->details[0]) ? $payment_response->details[0]->debug_message : $payment_response->object;
                                        throw new \Exception(trans("$string_file.payment_failed") . ' : ' . $message);
                                        //                                $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return $this->failedResponse(trans("$string_file.payment_configuration_not_found"));
                                }
                                break;
                            case "PAYU":
                                $user = User::find($userId);
                                $payment_config = PaymentOptionsConfiguration::where([['payment_option_id', '=', $card->payment_option_id], ['merchant_id', '=', $user->merchant_id]])->first();
                                $payment = new RandomPaymentController();
                                $locale = "en";
                                $transaction = [];
                                $estimate = NULL;
                                $payment_data = [];
                                if (!empty($bookingId)) {
                                    $booking = Booking::select('estimate_bill', 'user_id')->find($bookingId);
                                    $estimate =   $booking->estimate_bill;
                                    // p($estimate,0);
                                    // p($amount);
                                    $call_void_if_error = false;
                                    // when authorization amount is equal to capture
                                    if ($amount == $estimate) {
                                        if ($payment_config->payment_step > 1) {
                                            $transaction = DB::table("transactions")->select("id", "payment_transaction")->where([["reference_id", '=', $user->id], ["card_id", '=', $card->id], ["booking_id", '=', $bookingId], ["status", '=', 1]])->first();
                                            $transaction = !empty($transaction->payment_transaction)  ? json_decode($transaction->payment_transaction, true) : [];
                                        }
                                        $payment_data = $payment->payuPayment($user, $amount, $card, $payment_config, $locale, $transaction);
                                        $call_void_if_error = true;
                                    } elseif ($amount < $estimate) {
                                        if ($payment_config->payment_step > 1) {
                                            $transaction = DB::table("transactions")->select("id", "payment_transaction")->where([["reference_id", '=', $user->id], ["card_id", '=', $card->id], ["booking_id", '=', $bookingId], ["status", '=', 1]])->first();
                                            $transaction = !empty($transaction->payment_transaction)  ? json_decode($transaction->payment_transaction, true) : [];
                                        }
                                        // partial capture
                                        $payment_data = $payment->payuPartialPayment($user, $amount, $card, $payment_config, $locale, $transaction);
                                        $call_void_if_error = false; // if state in refund mode then can't void
                                        // refund of remaining amount
                                    } elseif ($amount > $estimate) {
                                        $transaction = DB::table("transactions")->select("id", "payment_transaction")->where([["reference_id", '=', $user->id], ["card_id", '=', $card->id], ["booking_id", '=', $bookingId], ["status", '=', 1]])->first();
                                        $transaction = !empty($transaction->payment_transaction)  ? json_decode($transaction->payment_transaction, true) : [];
                                        // cancel the existing authorization and create new with final amount
                                        if (isset($transaction['code']) && $transaction['code'] == "SUCCESS" && $transaction['transactionResponse']['state'] == "APPROVED") {
                                            // p($transaction);
                                            $result = $payment->payuPaymentVoid($booking->User, $booking->final_amount_paid, $booking->UserCard, $payment_config, $locale, $transaction);
                                        }
                                        $authorization_data = $payment->payuPaymentAuthorization($user, $amount, $card, $payment_config, $locale);
                                        // p($authorization_data);
                                        if (isset($authorization_data['code']) && $authorization_data['code'] == "SUCCESS" && $authorization_data['transactionResponse']['state'] == "APPROVED") {
                                            DB::table('transactions')->insert([
                                                'status' => 1, // for user
                                                'reference_id' => $user->id,
                                                'card_id' => $card->id,
                                                'merchant_id' => $booking->merchant_id,
                                                'payment_option_id' => $card->payment_option_id,
                                                'checkout_id' => $booking->id,
                                                'payment_transaction' => json_encode($authorization_data),
                                            ]);
                                            $transaction = $authorization_data;
                                            // caputure
                                            $payment_data = $payment->payuPayment($user, $amount, $card, $payment_config, $locale, $transaction);
                                            // p($payment_data);
                                            $call_void_if_error = true;
                                        } elseif (isset($authorization_data['code']) && $authorization_data['code'] == "SUCCESS" && $authorization_data['transactionResponse']['state'] == "DECLINED") {
                                            // $payment->payuPaymentVoid($booking->User, $amount, $booking->UserCard,$payment_config,$locale,$transaction);
                                            $message = isset($authorization_data['transactionResponse']['paymentNetworkResponseErrorMessage']) ? $authorization_data['transactionResponse']['paymentNetworkResponseErrorMessage'] : $authorization_data['transactionResponse']['responseCode'];
                                            throw new \Exception(trans("$string_file.payment_failed") . ' : ' . $message);
                                        } else {
                                            $message = isset($authorization_data['error']) ? $authorization_data['error'] : "";
                                            throw new \Exception(trans("$string_file.payment_failed") . ' : ' . $message);
                                        }
                                    }

                                    if (isset($payment_data['code']) && $payment_data['code'] == "SUCCESS" && $payment_data['transactionResponse']['state'] == "APPROVED") {
                                        $array_param['transaction_id'] = $payment_data['transactionResponse']['transactionId'];
                                        $this->UpdateStatus($array_param);
                                    } elseif (isset($payment_data['code']) && $payment_data['code'] == "SUCCESS" && $payment_data['transactionResponse']['state'] == "DECLINED") {
                                        // first void existing authorisation
                                        if ($call_void_if_error == true) {
                                            $payment->payuPaymentVoid($booking->User, $amount, $booking->UserCard, $payment_config, $locale, $transaction);
                                        }
                                        $message = isset($payment_data['transactionResponse']['paymentNetworkResponseErrorMessage']) ? $payment_data['transactionResponse']['paymentNetworkResponseErrorMessage'] : $payment_data['transactionResponse']['responseCode'];
                                        throw new \Exception(trans("$string_file.payment_failed") . ' : ' . $message);
                                    } else {
                                        $message = isset($payment_data['error']) ? $payment_data['error'] : "";
                                        throw new \Exception(trans("$string_file.payment_failed") . ' : ' . $message);
                                    }
                                }
                                break;
                            case "FLUTTERWAVE":
                                $user = User::find($userId);
                                $cardss = UserCard::find($card_id);
                                $first_name = $user->first_name;
                                $last_name = $user->last_name;
                                $email = $user->email;
                                $detail = "taxi payment";
                                $phone = $user->UserPhone;
                                $order_id = $bookingId;
                                $amount = $amount;
                                $token = $cardss->token;

                                $payment_option = PaymentOption::where('slug', 'FLUTTERWAVE')->first();
                                $paymentoption = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentoption)) {
                                    $secret_key = $paymentoption->api_secret_key;
                                    $curl = curl_init();
                                    $data = array(
                                        "token" => $token,
                                        "currency" => "NGN",
                                        "country" => "NG",
                                        "amount" => $amount,
                                        "email" => $email,
                                        "first_name" => $first_name,
                                        "last_name" => $last_name,
                                        "narration" => "Sample tokenized charge",
                                        "tx_ref" => "tokenized-c-001"
                                    );
                                    $data = json_encode($data);

                                    curl_setopt_array($curl, array(
                                        CURLOPT_URL => "https://api.flutterwave.com/v3/tokenized-charges",
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => "",
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 0,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => "POST",
                                        CURLOPT_POSTFIELDS => $data,
                                        CURLOPT_HTTPHEADER => array(
                                            "Content-Type: application/json",
                                            "Authorization: Bearer $secret_key"
                                        ),
                                    ));

                                    $res = curl_exec($curl);
                                    curl_close($curl);
                                    $response = json_decode($res);

                                    if ($response->status == 'success') {
                                        $array_param['transaction_id'] = $response->data->id;
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $message = $response->message;
                                        throw new \Exception(trans("$string_file.payment_failed") . ' : ' . $message);
                                    }
                                } else {
                                    return $this->failedResponse(trans("$string_file.payment_configuration_not_found"));
                                }
                                break;
                            case "PayGate":
                                // its webview based payment so we will consider it any online payment
                                break;
                            case "PAYMAYA":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'PAYSTACK')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentConfig)) {
                                    $newCard = new PayMayaController();
                                    $charge = $newCard->card_payment($amount, $user->Country->isoCode, $card, $paymentConfig, 'USER', $bookingId ?? ($booking_transaction->booking_id ?? NULL));
                                    if (is_array($charge)) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "PAYHERE":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'PAYHERE')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentConfig)) {
                                    $newCard = new PayHereController();
                                    $charge = $newCard->CardPayment($amount, $user->Country->isoCode, $user->id, 1, $card, $bookingId ?? ($booking_transaction->booking_id ?? NULL), $order_id, $handyman_order_id);
                                    if ($charge['result'] == true) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "SDGEXPRESS":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'SDGEXPRESS')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentConfig)) {
                                    $newCard = new SDGExpressController();
                                    $charge = $newCard->card_payment($amount, $user->Country->isoCode, $card, $paymentConfig, 'USER', $bookingId ?? ($booking_transaction->booking_id ?? NULL), $order_id, $handyman_order_id);
                                    if ($charge['result'] == true) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "COVERAGEPAY":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'COVERAGEPAY')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentConfig)) {
                                    $newCard = new RandomPaymentController();
                                    $charge = $newCard->coverageCardPayment(
                                        $amount,
                                        $card,
                                        $user,
                                        $paymentConfig,
                                        $bookingId,
                                        $order_id,
                                        $handyman_order_id
                                    );
                                    if ($charge == true) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "CLOVER":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'CLOVER')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentConfig)) {
                                    $newCard = new CloverController();
                                    $charge = $newCard->cloverCardPayment($amount, $card, $user, $paymentConfig, "USER", $bookingId, $order_id, $handyman_order_id);
                                    if ($charge == true) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "PINPAYMENT":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'PINPAYMENT')->first();
                                $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentConfig)) {
                                    $gateway_condition = $paymentConfig->gateway_condition == 1 ? true : false;
                                    $pinPayment = new PinPayment($paymentConfig->api_secret_key, $paymentConfig->api_public_key, $gateway_condition);
                                    $charge = $pinPayment->chargeCard($amount, $card, true, $user, null, $payment_option->id, $user->Country->isoCode, $bookingId, $order_id, $handyman_order_id);

                                    if ($charge == true) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return response()->json(['result' => "0", 'message' => "configurations not found"]);
                                }
                                break;
                            case "PAGUELO_FACIL":
                                try {
                                    $user = User::find($userId);
                                    $payment_option = PaymentOption::where('slug', 'PAGUELO_FACIL')->first();
                                    $paymentoption = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                    if (!empty($paymentoption)) {
                                        $gateway_condition = $paymentoption->gateway_condition == 1 ? true : false;
                                        $pagueloFacil = new PagueloFacil($paymentoption->api_secret_key, $paymentoption->api_public_key, $gateway_condition);
                                        $charge = $pagueloFacil->chargeCard($amount, $card, true, $user, null, $payment_option->id, $user->Country->isoCode, $bookingId, $order_id, $handyman_order_id);
                                        $result = "0";
                                        if ($charge == true) {
                                            $this->UpdateStatus($array_param);
                                            return true;
                                        } else {
                                            $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                        }
                                    } else {
                                        return $this->failedResponse(trans("$string_file.payment_configuration_not_found"));
                                    }
                                    break;
                                } catch (\Exception $e) {
                                    // p($e->getMessage());
                                    $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                }
                            case "POWERTRANZ":
                                try {
                                    $user = User::find($userId);
                                    $payment_option = PaymentOption::where('slug', 'POWERTRANZ')->first();
                                    $paymentoption = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                    if (!empty($paymentoption)) {

                                        $random_payment = new RandomPaymentController();
                                        $powertranz_response = $random_payment->PowerTranzCardPayment($amount, $card, true, $user, null, $payment_option->id, $user->Country->currency_numeric_code, $bookingId, $order_id, $handyman_order_id);

                                        // echo $response;
                                        if ($powertranz_response == true) {
                                            $this->UpdateStatus($array_param);
                                            return true;
                                        } else {
                                            $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                        }
                                    } else {
                                        return $this->failedResponse(trans("$string_file.payment_configuration_not_found"));
                                    }
                                } catch (\Exception $e) {
                                    // p($e->getMessage());
                                    $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                }
                                break;
                            case "PAYRIFF_CARD_SAVE":
                                $user = User::find($userId);
                                $payment_option = PaymentOption::where('slug', 'PAYRIFF_CARD_SAVE')->first();
                                $paymentoption = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id], ['payment_option_id', '=', $payment_option->id]])->first();
                                if (!empty($paymentoption)) {
                                    $newCard = new Payriff();
                                    $params = array(
                                        "type" => "USER",
                                        "amount" => $amount,
                                        "card_id" => $card->id,
                                        "merchant_id" => $user->merchant_id,
                                        "string_file" => $string_file,
                                        "booking_id" => $bookingId,
                                        "order_id" => $order_id,
                                        "handyman_order_id" => $handyman_order_id
                                    );
                                    $charge = $newCard->cardPayment($params);
                                    if ($charge) {
                                        $this->UpdateStatus($array_param);
                                        return true;
                                    } else {
                                        $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                                    }
                                } else {
                                    return $this->failedResponse(trans("$string_file.payment_configuration_not_found"));
                                }
                                break;
                        }
                    }
                    break;
                case "3": // wallet
                    $user = User::find($userId);
                    if ($user->corporate_id != null) {
                        if ($user->Corporate->wallet_balance < $amount) {
                            $remain = $user->Corporate->wallet_balance;
                            $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                            $sucess = false;
                        } else {
                            $remain = $user->Corporate->wallet_balance - $amount;
                            $this->CorporateWalletTransaction($user->Corporate, $amount, $bookingId, $order_id, $handyman_order_id);
                            $this->UpdateStatus($array_param);
                            $sucess = true;
                        }
                        $user->Corporate->wallet_balance = round_number($remain);
                        $user->Corporate->save();
                    } else {
                        if ($user->wallet_balance < $amount) {
                            $remain = $user->wallet_balance;
                            $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                            $sucess = false;
                        } else {
                            $remain = $user->wallet_balance - $amount;
                            $paramArray = array(
                                'user_id' => $user->id,
                                'booking_id' => $bookingId,
                                'order_id' => $order_id,
                                'handyman_order_id' => $handyman_order_id,
                                'amount' => $amount,
                                'narration' => (!empty($bookingId) || !empty($order_id)) ? 4 : 8,
                                'platform' => 2,
                                'payment_method' => 1,
                            );
                            // wallet can be debit for tip
                            WalletTransaction::UserWalletDebit($paramArray);
                            //                        CommonController::UserWalletDebit($user->id,$bookingId,$amount,4,2,1);
                            if (!empty($bookingId)) {
                                $this->UpdateStatus($array_param);
                            }
                            $sucess = true;
                        }
                        $user->wallet_balance = sprintf("%.2f", $remain);
                        $user->save();
                    }
                    return $sucess;
                    break;
                case "4":
                    $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id], ['payment_option_id', '=', $payment_option_id]])->first();
                    if (!empty($payment_option_config)) {
                        $slug  = $payment_option_config->PaymentOption->slug;
                        if ($slug == "CLOVER") {
                            $clover = new CloverController();
                            $transaction = DB::table('transactions')->where([['user_id', '=', $userId], ['booking_id', '=', $bookingId], ['handyman_order_id', '=', $handyman_order_id]])->first();
                            $capture = $transaction->reference_id;
                            $clover->capturePayment($amount, $capture, $payment_option_config, $bookingId, $order_id, $handyman_order_id);
                        }
                    }
                    $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                    return false;
                    break;
                case "5":
                    $this->UpdateStatus($array_param);
                    $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                    return true;
                    break;
                    //            case "6":
                    //                // Pay later Case
                    //                $booking = Booking::find($bookingId);
                    //                CommonController::AddUserRideOutstading($booking->user_id, $booking->driver_id, $booking->id, $amount);
                    //                $this->UpdateStatus($array_param);
                    //                $this->UpdateBookingOrderDetailStatus($array_param, $amount);
                    //                return true;
                    //                break;
                case "6":
                    // Pay later Case
                    if (!empty($bookingId)) {
                        $booking = Booking::find($bookingId);
                        CommonController::AddUserRideOutstading($booking->user_id, $booking->driver_id, $amount, $booking->id);
                        $this->UpdateStatus($array_param);
                        BookingDetail::where([['booking_id', '=', $bookingId]])->update(['pending_amount' => $amount]);
                    } elseif (!empty($handyman_order_id)) {
                        $booking = HandymanOrder::find($handyman_order_id);
                        CommonController::AddUserRideOutstading($booking->user_id, $booking->driver_id, $amount, NULL, $handyman_order_id);
                        $this->UpdateStatus($array_param);
                        //
                        //                        BookingDetail::where([['booking_id', '=', $bookingId]])->update(['pending_amount' => $amount]);
                    }
                    return true;
                    break;
                case "7":
                    $user = User::find($userId);
                    $ewallet_result = false;
                    $paymentoption = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id]])->first();
                    $payment_option = PaymentOption::find($paymentoption->payment_option_id);

                    $phone_card_no = isset($array_param['phone_card_no']) ? $array_param['phone_card_no'] : null;
                    // p($payment_option);
                    switch ($payment_option->slug) {
                        case "AMOLE":
                            $ewallet_user_otp = isset($array_param['ewallet_user_otp_pin']) ? $array_param['ewallet_user_otp_pin'] : null;
                            // p($array_param);
                            $ewallet_pin_expire = isset($array_param['ewallet_pin_expire']) ? $array_param['ewallet_pin_expire'] : "";
                            if ($ewallet_user_otp != NULL) {
                                $header = array(
                                    "HDR_Signature: CgRs_7DpRQm8StaX9n5jBdLy8sHl67rzyNTqPR4ZpPPbmsFrMBJEbyq-mb5dnitt",
                                    "HDR_IPAddress: 35.178.56.137",
                                    "HDR_UserName: hubert2",
                                    "HDR_Password: test",
                                    "Content-Type: application/x-www-form-urlencoded"
                                );
                                $body_param = array(
                                    "BODY_CardNumber" => $phone_card_no,
                                    "BODY_ExpirationDate" => $ewallet_pin_expire,
                                    "BODY_PaymentAction" => "01",
                                    "BODY_PIN" => $ewallet_user_otp,
                                    "BODY_AmountX" => $amount,
                                    "BODY_AmoleMerchantID" => "HUBERTAXI",
                                    "BODY_OrderDescription" => "For Taxi Payment",
                                    "BODY_SourceTransID" => time(),
                                    "BODY_VendorAccount" => "",
                                    "BODY_AdditionalInfo1" => "",
                                    "BODY_AdditionalInfo2" => "",
                                    "BODY_AdditionalInfo3" => "",
                                    "BODY_AdditionalInfo4" => "",
                                    "BODY_AdditionalInfo5" => ""
                                );
                                // p($body_param);
                                $payment_result = EwalletController::amolePayment($header, $body_param);
                                // p($payment_result);
                                if (is_array($payment_result) && $payment_result[0]->MSG_ShortMessage == 'Success') {
                                    $ewallet_result = true;
                                } else {
                                    throw new \Exception($payment_result[0]->MSG_LongMessage);
                                    break;
                                }
                            } else {
                                $ewallet_result = false;
                            }
                            break;
                        default:
                            $ewallet_result = false;
                    }
                    // var_dump($ewallet_result && (!empty($bookingId) || !empty($order_id) || !empty($handyman_order_id)));
                    // p('end');
                    // p($order_id);
                    if ($ewallet_result && (!empty($bookingId) || !empty($order_id) || !empty($handyman_order_id))) {
                        $this->UpdateStatus($array_param);
                    }
                    // Booking::where([['id', '=', $bookingId]])->update(['is_ewallet_payment' => 1]);
                    // BookingDetail::where([['booking_id', '=', $bookingId]])->update(['pending_amount' => $amount]);
                    return $ewallet_result;
                    break;
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function UpdateStatus(array $array_param)
    {
        // means update status only when payment is final

        try {
            $bookingId = isset($array_param['booking_id']) ? $array_param['booking_id'] : NULL;
            $order_id = isset($array_param['order_id']) ? $array_param['order_id'] : NULL;
            $handyman_order_id = isset($array_param['handyman_order_id']) ? $array_param['handyman_order_id'] : NULL;
            if (!empty($bookingId)) {
                // Booking::where('id', $bookingId)->update(['payment_status' => 1]);
                $booking= Booking::find($bookingId);
                if($booking->Merchant->Configuration->cash_confirmation == 1){ //only in case of cash we have to skip automatic pay for cash , we need confirmation

                    if(isset($array_param['payment_method_id'])){
                        if($array_param['payment_method_id'] != 1){
                            $booking->payment_status = 1;
                            $booking->save();
                        }
                    }
                }
                else{
                    $booking->payment_status = 1;
                    $booking->save();
                }
            }
            if (!empty($order_id)) {
                // Order::where('id', $order_id)->update(['payment_status' => 1]);
                Order::where('id', $order_id)
                    ->where('payment_method_id', '!=', 1)
                    ->update(['payment_status' => 1]);
            }
            if (!empty($handyman_order_id)) {

                $payment_type = isset($array_param['payment_type']) ? $array_param['payment_type'] : "FINAL";
                if ($payment_type != "ADVANCE") {
                    HandymanOrder::where('id', $handyman_order_id)->update(['payment_status' => 1]);
                }
            }
            if (!empty($array_param['transaction_id'])) {
                BookingTransaction::where([['booking_id', '=', $bookingId], ['order_id', '=', $order_id], ['handyman_order_id', '=', $handyman_order_id]])->update(['transaction_id' => $array_param['transaction_id']]);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function UpdateBookingOrderDetailStatus($array_param, $amount)
    {
        try {
            $bookingId = isset($array_param['booking_id']) ? $array_param['booking_id'] : NULL;
            $order_id = isset($array_param['order_id']) ? $array_param['order_id'] : NULL;
            $handyman_order_id = isset($array_param['handyman_order_id']) ? $array_param['handyman_order_id'] : NULL;
            $payment_method_id = isset($array_param['payment_method_id']) ? $array_param['payment_method_id'] : NULL;
            if (!empty($bookingId) && $payment_method_id != 4) {
                BookingDetail::where([['booking_id', '=', $bookingId]])->update(['pending_amount' => $amount, 'payment_failure' => 2]);
            } else {
                BookingDetail::where([['booking_id', '=', $bookingId]])->update(['pending_amount' => $amount]);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function CorporateWalletTransaction($corporate, $amount, $bookingId = NULL, $order_id = NULL, $handyman_order_id = NULL)
    {
        CorporateWalletTransaction::create([
            'merchant_id' => $corporate->merchant_id,
            'corporate_id' => $corporate->id,
            'narration' => 'Amount Deduct on Ride',
            'transaction_type' => 2,
            'payment_method' => 'Wallet',
            'amount' => $amount,
            'platform' => 'Application',
            'booking_id' => $bookingId,
            'description' => 'Amount Deduct on Ride',
            'receipt_number' => $bookingId
        ]);
    }


    // topup wallet of user and driver by online payment method
    // online payment
    // using for order and handyman bookings too
    public function onlinePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'payment_option_id' => 'required|integer|exists:payment_options,id',
            'calling_from' => 'required|in:USER,DRIVER,BOOKING',
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $response = [];
            $calling_from = $request->calling_from;
            $user = $request->user('api');
            if ($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
            }
            $string_file = $this->getStringFile(null, $user->Merchant);
            // PAYPHONE CASE
            if ($request->payment_method_id == 4 && !empty($request->payment_option_id)) {
                $option = PaymentOption::select('slug', 'id')->where('id', $request->payment_option_id)->first();
                if (empty($option)) {
                    throw new \Exception(trans("$string_file.configuration_not_found"));
                }
                $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id],['payment_option_id','=',$option->id]])->first();
                // dd($payment_option_config,$user->merchant_id,$option->slug);
                if ($option->slug == 'PAYPHONE') {
                    $payphone =  new PayPhoneController;
                    $response_req = $payphone->validateUser($request, $payment_option_config, $calling_from);
                    if ($response_req) {
                        $amount = $request->amount;
                        $arr_payment_details = [];
                        $arr_payment_details['amount'] = [
                            'amount' => $amount,
                            'tax' => 0,
                            'amount_with_tax' => 0,
                            'amount_without_tax' => $amount,
                        ];
                        $arr_payment_details['booking_id'] = NULL;
                        $arr_payment_details['order_id'] = $request->order_id;
                        $arr_payment_details['handyman_order_id'] = $request->handyman_order_id;
                        $payphone =  new PayPhoneController;
                        $response = $payphone->paymentRequest($request, $payment_option_config, $arr_payment_details, $calling_from);
                    }
                } elseif ($option->slug == 'AAMARPAY') {
                    $payphone =  new AamarPayController;
                    $response = $payphone->aamarPayRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'CASHFREE') {
                    $cash_free =  new CashFreeController();
                    $response = $cash_free->PaymentUrl($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'PAYBOX') {
                    if ($request->amount < 50) {
                        $message = trans("$string_file.minimum_amount") . ' >=50';
                        throw new \Exception($message);
                    }
                    $payphone =  new PayBoxController;
                    $response = $payphone->payboxRequest($request, $payment_option_config, $calling_from);
                } elseif (($option->slug == 'MERCADOCARD') || $option->slug == 'MERCADO') {
                    $mercado =  new MercadoController;
                    if ($request->request_from == 'PAYMENT') {
                        $response = $mercado->getWebViwUrlSplit(
                            $request,
                            $payment_option_config,
                            $calling_from
                        );
                    } else {
                        $response = $mercado->getWebViwUrl($request, $payment_option_config, $calling_from);
                    }
                } elseif ($option->slug == 'MERCADOPIX') {

                    $mercado =  new MercadoController;
                    if ($request->request_from == 'PAYMENT') {
                        $response = $mercado->pixPaymentRequest($request, $payment_option_config, $calling_from);
                    } else {
                        $response = $mercado->pixPaymentRequestSplit(
                            $request,
                            $payment_option_config,
                            $calling_from
                        );
                    }
                } elseif ($option->slug == 'EDAHAB') {
                    $validator = Validator::make($request->all(), [
                        'edahab_number' => 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }

                    $random_payment = new RandomPaymentController();
                    $response = $random_payment->edahabRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'COVERAGEPAY') //convergepay
                {
                    $validator = Validator::make($request->all(), [
                        'card_number' => 'required',
                        'expire_date' => 'required',
                        'cvv' => 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }

                    $random_payment = new RandomPaymentController();
                    $response = $random_payment->coveragePay($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'ZAAD') //convergepay
                {
                    $validator = Validator::make($request->all(), [
                        'mwallet_account_number' => 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }

                    $random_payment = new RandomPaymentController();
                    $response = $random_payment->zaadRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'CLOVER') //clover payment gateway
                {
                    $validator = Validator::make($request->all(), [
                        'card_number' => 'required',
                        'exp_year' => 'required',
                        'exp_month' => 'required',
                        'cvv' => 'required',
                        'calling_for' => 'required', // TAXI/HANDYMAN/FOOD/WALLET segment group
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }

                    $random_payment = new CloverController();
                    $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'PAYGATEGLOBAL') //
                {
                    $validator = Validator::make($request->all(), [
                        'payment_phone_number' => 'required|min:8,max:8',
                        'amount' => 'int|min:150',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }

                    $random_payment = new PaygateGlobalController();
                    $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'SQUARE') //convergepay
                {
                    $random_payment = new SquareController();
                    $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'DPO') //convergepay
                {
                    $random_payment = new DpoController();
                    $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'TOUCHPAY') //convergepay
                {
                    $validator = Validator::make($request->all(), [
                        'recipient_number' => 'required|min:8,max:8',
                        'amount' => 'int|min:100',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        return $this->failedResponse($errors[0]);
                    }
                    $random_payment = new TouchPayController();
                    $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'KUSHKI') //KUSHKI payment gateway
                {
                    $random_payment = new KushkiController();
                    if ($request->payment_type == "CARD") {
                        $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                    } elseif ($request->payment_type == "TRANSFERIN") {
                        $validator = Validator::make($request->all(), [
                            'document_type' => 'required',
                            'document_number' => 'required',
                        ]);

                        if ($validator->fails()) {
                            $errors = $validator->messages()->all();
                            throw new \Exception($errors[0]);
                        }
                        $response = $random_payment->transferInRequest($request, $payment_option_config, $calling_from);
                    }
                } elseif ($option->slug == 'OPAY') //OPAY
                {
                    $validator = Validator::make($request->all(), [
                        'reference_id' => 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $random_payment = new OpayController();
                    $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'SAMPAY') {
                    $payphone =  new SamPayController;
                    $response = $payphone->checkoutRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'PAYGO') {
                    $random_payment = new PayGoController();
                    // accountno
                    // like card, mtn, airtel & zamtel
                    $validator = Validator::make($request->all(), [
                        'payment_phone_number' => 'required',
                        'payment_type' => 'required',
                        // 'card_number' => 'required_if:payment_type,card',
                        // 'exp_year' => 'required_if:payment_type,card',
                        // 'exp_month' => 'required_if:payment_type,card',
                        // 'cvv' => 'required_if:payment_type,card',
                        'fname' => 'required_if:payment_type,card',
                        'mname' => 'required_if:payment_type,card',
                        'email' => 'required_if:payment_type,card',
                        // 'street' => 'required_if:payment_type,card',
                        // 'city' => 'required_if:payment_type,card',
                        //'country' => 'required_if:payment_type,card',
                        // 'postal_code' => 'required_if:payment_type,card',
                        // 'card_type' => 'required_if:payment_type,card',
                        'currency'=> 'required'
                    ]);

                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                } elseif (!empty($option) && $option->slug == 'PAYPAY') //convergepay
                {
                    $payment_option_config  = PaymentOptionsConfiguration::where('payment_option_id',$option->id)->where('merchant_id',$user->merchant_id)->first();

                    $validator = Validator::make($request->all(), []);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        return $this->failedResponse($errors[0]);
                    }

                    $random_payment = new PaypayController();
                    $payphone_response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                    return $this->successResponse(trans("$string_file.success"), $payphone_response);
                }
                elseif (!empty($option) && $option->slug == 'PAYPAY_AFRICA') //convergepay
                {
                    $payment_option_config  = PaymentOptionsConfiguration::where('payment_option_id',$option->id)->where('merchant_id',$user->merchant_id)->first();

                    $validator = Validator::make($request->all(), []);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        return $this->failedResponse($errors[0]);
                    }

                    $random_payment = new PaypayAfricaController();
                    $payphone_response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                    return $this->successResponse(trans("$string_file.success"), $payphone_response);
                }
                elseif ($option->slug == 'BEYONICMOBILE') {
                    $random_payment = new BeyonicController();
                    $validator = Validator::make($request->all(), [
                        'payment_phone_number' => 'required',
                    ]);

                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'KPay') {
                    $random_payment = new KPayController();
                    $validator = Validator::make($request->all(), [
                        'payment_phone_number' => 'required',
                        'payment_type' => 'required',
                    ]);

                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'WAAFI') {
                    $random_payment = new WaafiController();
                    $validator = Validator::make($request->all(), [
                        // 'payment_phone_number' => 'required',
                        // 'payment_type' => 'required',
                    ]);

                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $response = $random_payment->waafiPayRequest($request, $payment_option_config, $calling_from);
                } elseif ($option->slug == 'WAVE') {
                    $wave = new WaveController();
                    $validator = Validator::make($request->all(), [
                        'currency' => 'required',
                    ]);

                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $response = $wave->generateWaveUrl($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'TINGG'){
                    $tingg = new TinggController();
                    $validator = Validator::make($request->all(), [
                        'phone' => 'required',
                    ]);

                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $response = $tingg->TinggCheckout($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'PAGADITO') {
                    $random_payment = new PagaditoController();
                    $response = $random_payment->paymentRequest($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'PESAPAL') {
                    $pesapal_payment = new PesapalController();
                    $response = $pesapal_payment->PesapalCheckout($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'CASH_PAY') {
                    $random_payment = new CashPayController();
                    $response = $random_payment->PaymentUrl($request, $payment_option_config, $calling_from);
                }elseif($option->slug == 'PAYWEB3'){
                    $validator = Validator::make($request->all(), [
                        'currency' => 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $random_payment = new PaywebController();
                    $response = $random_payment->PayWeb3Initiate($request, $payment_option_config, $calling_from);
                }elseif ($option->slug == 'MIDTRANS_MOBILE_SDK') {
                    $random_payment = new MidtransController();
                    $response = $random_payment->createTransaction($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'HUB_2') {
                    $random_payment = new Hub2Controller();
                    $response = $random_payment->createTransaction($request, $payment_option_config, $calling_from);
                }elseif ($option->slug == 'WIPAY') {
                    $wiPay = new WiPayController();
                    $response = $wiPay->WiPayInitiate($request, $payment_option_config, $calling_from);
                }elseif ($option->slug == 'PAYPAL'){
                    $paypal = new PaypalController();
                    $response = $paypal->createPaymentUrl($request, $payment_option_config, $calling_from);
                }elseif($option->slug == 'TELEBIRRPAY'){
                    $telebirrPay = new TelebirrPayController();
                    $response = $telebirrPay->createPaymentUrl($request, $payment_option_config, $calling_from);
                }elseif ($option->slug == 'PESEPAY') {
                    $pesepay = new PesepayController();
                    $validator = Validator::make($request->all(), [
                        'currency' => 'required',
                    ]);

                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $response = $pesepay->generatePesepayUrl($request, $payment_option_config, $calling_from);
                }
                elseif($option->slug == 'UNI5PAY'){
                    $uniPay = new UniPayController();
                    $response = $uniPay->createQrPaymentUrl($request,$payment_option_config,$calling_from);
                }
                elseif($option->slug == 'ORANGEMONEY_WEB'){
                    $orangeMoney = new OrangeMoneyWebController();
                    $response = $orangeMoney->createPaymentUrl($request,$payment_option_config,$calling_from);
                }
                elseif($option->slug == 'CXPAY'){
                    $cx_pay = new CxPayController();
                    $response = $cx_pay->processStepOne($request,$payment_option_config,$calling_from);
                }
                elseif($option->slug == 'WAAFIPAY'){
                    $random_payment = new WaafiPaymentController();
                    $validator = Validator::make($request->all(), [
                        'phone' => 'required',
                    ]);

                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $response = $random_payment->waafiPayRequest($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'BOGPAY') {
                    $validator = Validator::make($request->all(), [
                        'currency' => 'required|in:GEL,USD,EUR,GBP',
                    ], [
                        'currency' => 'currency is required | GEL,USD,EUR,GBP '
                    ]);

                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $bog_pay = new BogPaymentController();
                    $response = $bog_pay->makePaymentUsingBog($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'PAYNOW') {
                    $pay_now = new PayNowController();
                    $response = $pay_now->initiatePaymentRequest($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == "FASTHUB") {
                    $validator = Validator::make($request->all(), [
                        'recipient' => 'required',
                    ], [
                        'recipient' => 'recipient is required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $fasthub = new FastHubController();
                    $response = $fasthub->fasthubPaymentRequest($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == "CACPAY"){
                    $validator = Validator::make($request->all(), [
                        'recipient' => 'required',
                    ], [
                        'recipient' => 'recipient is required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $cacpay = new CacPaymentController();
                    $response = $cacpay->initiatePaymentRequest($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == "PEACH_SERVER_TO_SERVER") {
                    $validator = Validator::make($request->all(), [
                        'currency' => ['required', 'regex:/^(ZAR)$/'],
                        'paymentBrand' => ['required', 'regex:/^(VISA|MASTER)$/'],
                        'card_number' => ['required'],
                        'card_holder' => ['required'],
                        'card_expiry_month' => ['required', 'digits:2', 'between:1,12'],
                        'card_expiry_year' => ['required', 'digits:4', ],
                        'card_cvv' => ['required', 'digits:3'],
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $peach_server_to_server = new PeachServerToServer();
                    $response = $peach_server_to_server->initiatePaymentRequest($request, $payment_option_config, $calling_from);
                    if($response[0]){
                        return $this->successResponse(trans("$string_file.success"), $response[1]);
                    }
                    return $this->failedResponse($response[1]);
                }
                elseif($option->slug == "DIBSY_PAYMENT"){
                    $validator = Validator::make($request->all(), [
                        'currency'=> 'required',
                        'card_number' => 'required',
                        'card_holder' => 'required',
                        'card_expiry_month' => 'required',
                        'card_expiry_year' => 'required',
                        'card_cvv' => 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $dibsy = new DibsyPaymentController();
                    $response = $dibsy->paymentRequest($request,$payment_option_config,$calling_from);
                }
                else if($option->slug == "MGURUSH"){
                    $validator = Validator::make($request->all(), [
                        'currency'=> 'required|string',
                        'mobile_number'=> 'required|string',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $mgurush = new MgurushPay();
                    $response = $mgurush->initiatePaymentRequest($request, $payment_option_config, $calling_from, $string_file);

                    if(!is_string($response)){
                        if($response['status']['statusCode'] == 0)
                            return $this->successResponse(trans("$string_file.success"), $response);
                        return $this->failedResponse($response['status']['messageDescription']);
                    }
                    else{
                        return $this->failedResponse($response);
                    }
                }
                else if($option->slug == "ORANGEMONEYCORE"){
                    $validator = Validator::make($request->all(), [
                        'phone'=> 'required|string',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $mgurush = new OrangeMoneyCoreController();
                    $response = $mgurush->initiatePaymentRequest($request, $payment_option_config, $calling_from, $string_file);
                }
                else if($option->slug == "CRDB"){
                    $validator = Validator::make($request->all(), [
                        'currency'=>'required|string',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $crdb = new CrdbPay();
                    $response = $crdb->initiatePaymentRequest($request, $payment_option_config, $calling_from, $string_file);
                }
                elseif($option->slug == 'TELEBIRRPAY_NEW'){
                    $telebirrPay = new TelebirrPayNewController();
                    $response = $telebirrPay->createPaymentUrl($request, $payment_option_config, $calling_from);
                }
                elseif($option->slug == "MAXICASH"){
                    $maxicash = new MaxicashController();
                    $response = $maxicash->paymentInitiate($request, $payment_option_config, $calling_from);
                }
                elseif($option->slug == "KHALTI")
                {
                    $Khalti = new khaltiController();
                    $response = $Khalti->initiatePayment($request, $payment_option_config, $calling_from);
                }
                elseif($option->slug == "BINANCE")
                {
                    $binance = new BinanceController();
                    $response = $binance->paymentHistory($request, $payment_option_config, $calling_from);
                }
                else if($option->slug == "ORANGE_MONEY_B2B"){
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                        'currency' => 'required:string',
                        'phone'=> 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $orange_money_b2b = new OrangeMoneyB2B();
                    $response = $orange_money_b2b->initiatePayment($request, $payment_option_config, $calling_from);
                }
                else if($option->slug == "PAWAPAY"){
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                        'phone'=> 'required',
                        'correspondent_name'=> 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $pawapay = new PawaPayController();
                    $response = $pawapay->paymentRequest($request, $payment_option_config, $calling_from);
                }
                else if($option->slug == "TAP_PAYMENT"){
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                        'phone'=> 'required',
                        'token_id'=> 'required',
                        "currency"=> 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $tappay = new TapPaymentController();
                    $response = $tappay->authorizePayment($request, $payment_option_config, $calling_from);
                }
                else if($option->slug == "BUDPAY"){
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                        'bank_name'=>'required',
                        'bank_code'=> 'required',
                        'currency'=>'required',
                        'phone'=> 'required'
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $budpay = new BudPayController();
                    $response = $budpay->initiatePaymentRequest($request, $payment_option_config, $calling_from);
                }
                else if($option->slug == "PAYSUITE"){
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                        'currency'=> 'required'
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $paysuite = new PaySuiteController();
                    $response = $paysuite->generateUrl($request, $payment_option_config, $calling_from);
                }
                else if($option->slug == "FLUTTERWAVE_STANDARD"){
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                        'currency'=> 'required'
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $flutterWave = new FlutterWaveStandard();
                    $response = $flutterWave->initiatePayment($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'LIGDICASH') {
                    $ligdicash = new LigdiCashPaymentController();
                    $response = $ligdicash->initiatePayment($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'REVOLUTPAY') {
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                        'currency'=> 'required'
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $revolut = new RevolutPaymentController();
                    $response = $revolut->createOrder($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'UBPAY') {
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                        'currency'=> 'required'
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $ubpay = new UbPayController();
                    $response = $ubpay->UbpayGetUrl($request, $payment_option_config, $calling_from);
                }
                elseif ($option->slug == 'CASHPLUS') {
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $cashplus = new CashPlusController();
                    $response = $cashplus->generateToken($request, $payment_option_config, $calling_from);
                }
                elseif($option->slug == "RAZORPAY"){
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $razorpay = new RazorpayController();
                    $response = $razorpay->createOrder($request,$payment_option_config, $calling_from);
                }
                elseif($option->slug == "IMBANK_MPESA"){
                    // $validator = Validator::make($request->all(), [
                    //     'card_number'=> 'required',
                    //     'expiry'=> 'required'
                    // ]);
                    // if ($validator->fails()) {
                    //     $errors = $validator->messages()->all();
                    //     throw new \Exception($errors[0]);
                    // }
                    $imbank = new IMBankController();
                    $response = $imbank->generateForm($request,$payment_option_config, $calling_from);
                }
                elseif($option->slug == "ESEWA_PAY"){
                    $esewa = new ESewaPaymentController();
                    $response = $esewa->generateForm($request,$payment_option_config, $calling_from);
                }
                elseif($option->slug == "FLEX_PAY"){
                    $validator = Validator::make($request->all(), [
                        'currency'=>'required',
                        'phone'=>'required'
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $esewa = new FlexPayController();
                    $response = $esewa->paymentRequest($request,$payment_option_config, $calling_from);
                }
                elseif($option->slug == "AUB_PAY"){
                    $aubpay = new AubPayController();
                    $response = $aubpay->paymentRequest($request,$payment_option_config, $calling_from);
                }
                elseif($option->slug == "REMITA_PAY"){
                    $remita = new RemitaPayController();
                    $response = $remita->MakePayment($request,$payment_option_config, $calling_from);
                }
                elseif($option->slug == "EASY_PAY"){
                    $validator = Validator::make($request->all(), [
                        'currency'=>'required',
                        'phone'=>'required'
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $easypay = new EasyPayController();
                    $response = $easypay->MakePayment($request,$payment_option_config, $calling_from);
                }
                elseif($option->slug == "XENDIT_PAY"){
                    $xenditpay = new XendItPayController();
                    $response = $xenditpay->CreateInvoice($request,$payment_option_config, $calling_from);
                }
                elseif($option->slug == "PAYSUITE_TECH"){
                    $validator = Validator::make($request->all(), [
                        'amount' => 'required',
                        'currency'=> 'required'
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new \Exception($errors[0]);
                    }
                    $paysuite = new PaySuiteController();
                    $response = $paysuite->generateCheckoutUrl($request, $payment_option_config, $calling_from);
                }

                if(isset($response['result']) && $response['result'] == 0){
                    if(isset($response['response'])){
                        return $this->failedResponse($response['response']);
                    }
                }

                return $this->successResponse(trans("$string_file.success"), $response);
            }
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function onlinePaymentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
            'payment_option_id' => 'required|integer|exists:payment_options,id',
            'calling_from' => 'required|in:USER,DRIVER',
            //            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $calling_from = $request->calling_from;
            $user = $request->user('api');
            if ($calling_from == "DRIVER") {
                $user = $request->user('api-driver');
            }
            $string_file = $this->getStringFile(null, $user->Merchant);
            // PAYPHONE CASE
            $option = PaymentOption::select('slug', 'id')->where('id', $request->payment_option_id)->first();
            $payment_option_config  = $option->PaymentOptionConfiguration;
            if (empty($request->payment_option_id)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }
            if ($option->slug == 'SQUARE') {
                $random_payment = new SquareController();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            } elseif ($option->slug == 'DPO') {
                $payment_option_config  = $option->PaymentOptionConfiguration;
                $random_payment = new DpoController();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            } elseif ($option->slug == 'SAMPAY') {
                $random_payment = new SamPayController();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            } elseif ($option->slug == 'PAYGO') {
                $random_payment = new PayGoController();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            } elseif ($option->slug == 'BEYONICMOBILE') {
                $random_payment = new BeyonicController();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            } elseif (!empty($request->payment_option_id) && $option->slug == 'PAYPAY') {
                $payment_option_config  = PaymentOptionsConfiguration::where('payment_option_id',$option->id)->where('merchant_id',$user->merchant_id)->first();
                $random_payment = new PaypayController();
                $payphone_response = $random_payment->paymentStatus($request,$payment_option_config,$calling_from);

                return $this->successResponse(trans("$string_file.success"),$payphone_response);

            }elseif (!empty($request->payment_option_id) && $option->slug == 'PAYPAY_AFRICA') {
                $payment_option_config  = PaymentOptionsConfiguration::where('payment_option_id',$option->id)->where('merchant_id',$user->merchant_id)->first();
                $random_payment = new PaypayAfricaController();
                $payphone_response = $random_payment->paymentStatus($request,$payment_option_config,$calling_from);

                return $this->successResponse(trans("$string_file.success"),$payphone_response);

            }elseif ($option->slug == 'KPAY') {
                $random_payment = new KPayController();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            } elseif ($option->slug == 'WAVE') {
                $wave = new WaveController();
                $return_response = $wave->paymentStatus($request);
            } elseif ($option->slug == 'WAAFI') {
                $random_payment = new WaafiController();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            }elseif ($option->slug == 'TINGG'){
                $tingg = new TinggController();
                $return_response = $tingg->paymentStatus($request);
            }elseif ($option->slug == 'PAGADITO') {
                $random_payment = new PagaditoController();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            }elseif ($option->slug == 'CASH_PAY') {
                $random_payment = new CashPayController();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            }elseif ($option->slug == 'PESAPAL') {
                $random_payment = new PesapalController();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            }elseif ($option->slug == 'PAYWEB3') {
                $random_payment = new PaywebController();
                $return_response = $random_payment->paymentStatus($request);
            }elseif ($option->slug == 'HUB_2') {
                $random_payment = new Hub2Controller();
                $return_response = $random_payment->paymentStatus($request, $payment_option_config, $calling_from);
            }elseif ($option->slug == 'WIPAY') {
                $wiPay = new WiPayController();
                $return_response = $wiPay->paymentStatus($request);
            }elseif ($option->slug == 'PESEPAY') {
                $pesepay = new PesepayController();
                $return_response = $pesepay->paymentStatus($request);
            }elseif($option->slug == 'ORANGEMONEY_WEB'){
                $orangeMoney = new OrangeMoneyWebController();
                $return_response = $orangeMoney->paymentStatus($request);
            }elseif($option->slug == 'MOMOPAY'){
                $validator = Validator::make($request->all(), [
                    'access_token' => 'required',
                ]);
                if ($validator->fails()) {
                    $errors = $validator->messages()->all();
                    return $this->failedResponse($errors[0]);
                }
                $momo = new RandomPaymentController();
                $return_response = $momo->MomoPaymentStatus($request);
            }elseif($option->slug == 'UNI5PAY'){
                $uniPay = new UniPayController();
                $return_response = $uniPay->paymentStatus($request);
            }elseif ($option->slug == "FASTHUB") {
                $fasthub = new FastHubController();
                $return_response = $fasthub->paymentStatus($request);
            }elseif ($option->slug == "CACPAY") {
                $cacpay = new CacPaymentController();
                $return_response = $cacpay->paymentStatus($request);
            }
             elseif ($option->slug == "PEACH_SERVER_TO_SERVER") {
                $peach_server_to_server = new PeachServerToServer();
                $return_response = $peach_server_to_server->paymentStatus($request, $payment_option_config);
            }
            elseif ($option->slug == "MGURUSH") {
                $mgurush = new MgurushPay();
                $return_response = $mgurush->paymentStatus($request, $payment_option_config, $string_file);
            }
            elseif($option->slug == "DIBSY_PAYMENT"){
                    $dibsy = new DibsyPaymentController();
                    $return_response = $dibsy->paymentStatus($request,$payment_option_config);
            }
            elseif($option->slug == "ORANGEMONEYCORE"){
                    $validator = Validator::make($request->all(), [
                        'pay_token' => 'required',
                        'bearer_token'=> 'required',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        return $this->failedResponse($errors[0]);
                    }
                    $orange = new OrangemoneyCoreController();
                    $return_response = $orange->paymentStatus($request,$payment_option_config);
            }
            elseif($option->slug == "KHALTI")
            {
                $Khalti = new khaltiController();
                $response = $Khalti->paymentStatus($request, $payment_option_config, $calling_from);
            }
            elseif($option->slug == "ORANGE_MONEY_B2B")
            {
                $orange_money_b2b = new OrangeMoneyB2B();
                $return_response = $orange_money_b2b->paymentStatus($request);
            }
            elseif($option->slug == "PAWAPAY")
            {
                $pawa = new PawaPayController();
                $return_response = $pawa->paymentStatus($request);
            }
            else if($option->slug == "TAP_PAYMENT")
            {
                $tappay = new TapPaymentController();
                $return_response = $tappay->paymentStatus($request);
            }
            else if($option->slug == "BUDPAY"){
                $budpay = new BudPayController();
                $return_response = $budpay->paymentStatus($request);
            }
            else if($option->slug == "REVOLUTPAY"){
                $validator = Validator::make($request->all(), [
                    'order_id' => 'required',
                ]);
                if ($validator->fails()) {
                    $errors = $validator->messages()->all();
                    return $this->failedResponse($errors[0]);
                }
                $revolutpay = new RevolutPaymentController();
                $return_response = $revolutpay->paymentStatus($request);
            }
            elseif($option->slug == "RAZORPAY"){
                $razorpay = new RazorpayController();
                $return_response = $razorpay->paymentStatus($request,$payment_option_config);
            }
            elseif($option->slug == "IMBANK_MPESA"){
                    $imbank = new IMBankController();
                    $return_response = $imbank->paymentStatus($request,$payment_option_config);
            }
            elseif($option->slug == "ESEWA_PAY"){
                $esewa = new ESewaPaymentController();
                $return_response = $esewa->paymentStatus($request,$payment_option_config);
            }
            elseif($option->slug == "FLEX_PAY"){
                $flex = new FlexPayController();
                $return_response = $flex->paymentStatus($request,$payment_option_config);
            }
            elseif($option->slug == "AUB_PAY"){
                $aubpay = new AubPayController();
                $return_response = $aubpay->paymentStatus($request,$payment_option_config);
            }
            elseif($option->slug == "EASY_PAY"){
                $easypay = new EasyPayController();
                $return_response = $easypay->paymentStatus($request,$payment_option_config);
            }
            return $this->successResponse(trans("$string_file.success"), $return_response);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function pendingTransactions(Request $request){
        $validator = Validator::make($request->all(), [
            'calling_from' => 'required|in:USER,DRIVER',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try{
            if ($request->calling_from == "DRIVER") {
                $user = $request->user('api-driver');
            }else{
                $user = $request->user('api');
            }
            $string_file = $this->getStringFile(null, $user->Merchant);
            $transactions = Transaction::select("*")
                ->whereNull("booking_id")
                ->whereNull("order_id")
                ->whereNull("handyman_order_id")
                ->where(function($query) use($request, $user){
                    if ($request->calling_from == "DRIVER") {
                        $query->where("driver_id", $user->id);
                    }else{
                        $query->where("user_id", $user->id);
                    }
                })->where("request_status", 1)->get();
        }catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function getStripeIntentSecret(Request $request){
        $validator = Validator::make($request->all(), [
            'calling_from' => 'required|in:USER,DRIVER',
            'amount' => 'required',
            'currency' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try{
            if ($request->calling_from == "DRIVER") {
                $user = $request->user('api-driver');
            }else{
                $user = $request->user('api');
            }
            $string_file = $this->getStringFile(null, $user->Merchant);
            $option = PaymentOption::select('slug', 'id')->where('slug', 'STRIPE')->first();
            $payment_option_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $user->merchant_id],['payment_option_id','=',$option->id]])->first();
            if (empty($payment_option_config)) {
                throw new \Exception(trans("$string_file.configuration_not_found"));
            }

            $stripe = new StripeController($payment_option_config->api_secret_key);
            $intent_secret = $stripe->CreatePaymentIntent($request->amount, $request->currency);
            return $this->successResponse(trans("$string_file.success"), ["intent_secret" => $intent_secret]);
        }catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }
}
