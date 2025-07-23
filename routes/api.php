<?php

use Illuminate\Http\Request;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: *');
//header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Credentials, Access-Control-Allow-Origin, Access-Control-Allow-Methods, Access-Control-Allow-Headers, aliasName, publicKey, secretKey, locale');


Route::get('test',function(Request $request){
    dd($request->all());
});
//sms testing route
Route::post('/MessageMedia', 'SmsGateways\SimpleSms@MessageMedia');
//stripe callback
Route::post('/stripe/generate-url', 'PaymentMethods\StripeController@GetWebViewUrl');
Route::any('/stripe/success', 'PaymentMethods\StripeController@stripeSuccess')->name('stripe.success');
Route::any('/stripe/cancel', 'PaymentMethods\StripeController@stripeCancel')->name('stripe.cancel');
Route::any('/stripe/card-payment/success', 'PaymentMethods\StripeCardPayment@stripeSuccess')->name('card-payment.stripe.success');

// ViuPay Callback
Route::any('/viupay/callback', 'PaymentMethods\ViuPay\ViuPayController@callback');

// Hub2 Callback payment and transfer endpoint different
Route::any('/hub2/callback/payment', 'PaymentMethods\Hub2\Hub2Controller@callback');
Route::any('/hub2/callback/transfer', 'PaymentMethods\Hub2\Hub2Controller@callback');

// Cashpay Callback
Route::any('/cashpay/callback/{transaction_id}', 'PaymentMethods\CashPay\CashPayController@callback')->name("cashpay.callback");

//BillBox Callback
Route::any('/billBox/callback', 'PaymentMethods\BillBox\BillBoxController@BillBoxCallback');
Route::get('/billBox/redirectSuccess/{msg?}', 'PaymentMethods\BillBox\BillBoxController@RedirectSuccess')->name('BillBoxSuccess');
Route::get('/billBox/redirectFail/{msg?}', 'PaymentMethods\BillBox\BillBoxController@RedirectFail')->name('BillBoxFail');

//ihelaPay
Route::any('/ihela/callback', 'PaymentMethods\Ihela\IhelaController@IhelaCallback')->name('ihela.return');

// Orange Money Routes
Route::get('/orange', 'PaymentMethods\OrangeMoney\OrangeMoneyController@OrangeMoney')->name('api.orange_money_url');
Route::post('/orange/success', 'PaymentMethods\OrangeMoney\OrangeMoneyController@OrangeMoneySuccess')->name('api.orange_success_url');
Route::post('/orange/fail', 'PaymentMethods\OrangeMoney\OrangeMoneyController@OrangeMoneyFail')->name('api.orange_fail_url');
Route::post('/orange/notify', 'PaymentMethods\OrangeMoney\OrangeMoneyController@OrangeMoneyNotify')->name('api.orange_notify_url');

// Telebirr Pay Routes
Route::any('/teliberrNotify/{merchant_id?}', 'PaymentMethods\TelebirrPay\TelebirrPayController@teliberrNotify')->name('teliberrNotify');
Route::any('/teliberrCallback/{outTradeNo}', 'PaymentMethods\TelebirrPay\TelebirrPayController@teliberrCallback')->name('teliberrCallback');
Route::get('/telebirr/success/{msg?}', 'PaymentMethods\TelebirrPay\TelebirrPayController@teliberrSuccess')->name('teliberr.success');
Route::get('/telebirr/failed/{msg?}', 'PaymentMethods\TelebirrPay\TelebirrPayController@teliberrFailed')->name('teliberr.failed');

//New Route Controller telebirr
Route::any('/teliberrNotify/{merchant_id?}', 'PaymentMethods\TelebirrPay\TelebirrPayNewController@teliberrNotify')->name('teliberrNotifyNew');

//MpesaB2C callback
Route::any('/mpesaCallback', 'PaymentMethods\Mpesa\MpesaController@MpesaB2CRequestCallback')->name('mpesa.b2c.callback');
Route::any('/mpesab2c/success', 'PaymentMethods\Mpesa\MpesaController@MpesaB2CSuccess')->name('mpesa.Success');
Route::any('/mpesab2c/failed', 'PaymentMethods\Mpesa\MpesaController@MpesaB2cFail')->name('mpesa.Fail');

//MomoPay callback Routes
Route::post('/momo/callback', 'PaymentMethods\RandomPaymentController@MOMOCallback')->name('momo.callback');
Route::post('/wasl/driver-vehicle-regi', 'Api\WaslController@driverVehicleRegister');

//Buttler Routes
Route::any('user/mpessapayment_confirmation', 'PaymentMethods\RandomPaymentController@MpessaCallBack');
Route::post('/{alias_name}/api/trips', 'Api\ButtlerController@index');
Route::get('/{alias_name}/api/trips/{tripId}', 'Api\ButtlerController@tripstatus');
Route::delete('/{alias_name}/api/trips/{tripId}', 'Api\ButtlerController@trips_delete');

Route::get('callbackkorba', 'PaymentMethods\RandomPaymentController@callbackkorba');
Route::any('/bancardCallback', 'PaymentMethods\RandomPaymentController@bancardCallback')->name('bancardCallback');
Route::post('redirectPeach', 'PaymentMethods\RandomPaymentController@redirectPeach')->name('redirectPeach');
Route::get('/shopper/{id}', 'PaymentMethods\RandomPaymentController@shopper')->name('shopper');
// Route::post('/beyonicCallback', 'PaymentMethods\RandomPaymentController@beyonicCallback')->name('beyonicCallback');
Route::post('/beyonicCallback', 'PaymentMethods\BeyonicPayment\BeyonicController@beyonicCallback')->name('beyonicCallback');
//common api's for all apps
//Route::post('/checkBookingStatus', 'Api\BookingController@CheckBookingStatus');
Route::post('/check-booking-status', 'Api\BookingController@checkBookingStatus');
Route::post('send-mail', 'Api\EmailController@test');
Route::post('/estimate', 'Helper\ExtraCharges@NewnightchargeEstimate');
Route::get('/copypaste', 'Api\UserController@CopySignUp');
Route::post('/bookingStatus', 'Api\BookingController@BookingStatus');
Route::post('/conekta', 'Api\FoodController@conekta');
Route::get('/time', function () {
    return response()->json(['result' => '1', 'message' => 'Time Stamp', 'time' => time()]);
});

Route::post('/PayHere/AddCardNotification', ['as' => 'PayHere.AddCardNotification', 'uses' => 'PaymentMethods\PayHere\PayHereController@AddCardCallBack']);

Route::get('/union-bank/auth_code/redirect', ['as' => 'union-bank.auth_code.redirect', 'uses' => 'PaymentMethods\UnionBank\UnionBankController@AuthCodeRedirect']);

Route::post('/proxypay/callback', ['as' => 'proxy_pay.callback', 'uses' => 'PaymentMethods\ProxyPay\ProxyPayController@resultWebhook']);

Route::post('/cash_free/redirect', ['as' => 'cash_free.redirect', 'uses' => 'PaymentMethods\CashFree\CashFreeController@Redirect']);
Route::post('/cash_free/notify', ['as' => 'cash_free.notify', 'uses' => 'PaymentMethods\CashFree\CashFreeController@Notify']);
Route::get('/cash_free/success', ['as' => 'cash_free.success', 'uses' => 'PaymentMethods\CashFree\CashFreeController@Success']);
Route::get('/cash_free/fail', ['as' => 'cash_free.fail', 'uses' => 'PaymentMethods\CashFree\CashFreeController@Fail']);

/*pagadito payment*/
// need to submit payment request and self redirect on payment page

Route::get('pagadito/payment-request', 'PaymentMethods\Pagadito\PagaditoController@pagaditoPayment')->name('pagadito-payment-request');
Route::get('pagadito/payback/', ['as' => 'api.pagadito-payback', 'uses' => 'PaymentMethods\Pagadito\PagaditoController@PagaditoPayback']);
Route::get('pagadito/failed/', 'PaymentMethods\Pagadito\PagaditoController@PagaditoFailed')->name('pagadito-failed');
Route::get('pagadito/success/', 'PaymentMethods\Pagadito\PagaditoController@PagaditoSuccess')->name('pagadito-success');
// Syberpay payment gateway
Route::post('/SyberpayGetUrl', ['as' => 'api.syberpay.getUrl', 'uses' => 'PaymentMethods\RandomPaymentController@SyberpayGetUrl']);
Route::post('/SyberpayPaymentStatus', ['as' => 'api.syberpay.paymentstatus', 'uses' => 'PaymentMethods\RandomPaymentController@SyberpayPaymentStatus']);
Route::post('/SyberpayRedirect', ['as' => 'api.syberpay.redirectUrl', 'uses' => 'PaymentMethods\RandomPaymentController@SyberpayRedirectUrl']);

// imepay recording url
Route::post('/imepay/recording', ['as' => 'api.imepay.recording', 'uses' => 'PaymentMethods\RandomPaymentController@ImepayRecording']);

// UBpay payment gateway
Route::get('/ubpay/callback/{merchantRef}/{status}', ['as' => 'api.ubpay.callback', 'uses' => 'PaymentMethods\RandomPaymentController@UbpayCallback']);

Route::post('/driver/location/test', ['as' => 'api.driver-location', 'uses' => 'Api\DriverController@Location']);

// Razorpay Callback URL
Route::any('/razorpay/callback/{id}', 'PaymentMethods\Razorpay\RazorpayController@RazorpayCallback')->name('razorpay-callback');
Route::any('/razorpay/success', 'PaymentMethods\Razorpay\RazorpayController@Success')->name('razorpay-success');
Route::any('/razorpay/fail', 'PaymentMethods\Razorpay\RazorpayController@Fail')->name('razorpay-fail');
Route::any('/razorpay/web-view-form', 'PaymentMethods\Razorpay\RazorpayController@webview')->name('razorpay-webview');

// 2C2P Redirect URL
Route::post('/2c2p/return/url', 'PaymentMethods\RandomPaymentController@TwoCTwoPReturn');
Route::post('/2c2p/token', 'PaymentMethods\RandomPaymentController@TwoCTwoPToken');

//EasyPay Callback
Route::any('/easypay/{merchantId}', 'PaymentMethods\EasyPay\EasyPayController@EasyPayCallabck')->name('easypay-callback');

//IM Bank

Route::any('/imbank/return/url/{merchantId}', 'PaymentMethods\IMBankMpesa\IMBankController@IMBankReturn')->name('imbank-callback');
Route::any('/imbank/success', 'PaymentMethods\IMBankMpesa\IMBankController@Success')->name('imbank-success');
Route::any('/imbank/fail', 'PaymentMethods\IMBankMpesa\IMBankController@Fail')->name('imbank-fail');
Route::any('/imbank/webviewform/{reference}/{application_id}/{currency}/{amount}/{merchant_id}', 'PaymentMethods\IMBankMpesa\IMBankController@webview')->name('imbank-webview');

//Esewa
Route::any('/esewa/success/{merchantId}', 'PaymentMethods\ESewa\ESewaPaymentController@Success')->name('esewa-success');
Route::any('/esewa/fail/{merchantId}/{transId}', 'PaymentMethods\ESewa\ESewaPaymentController@Fail')->name('esewa-fail');
Route::any('/esewa/webviewform', 'PaymentMethods\ESewa\ESewaPaymentController@webview')->name('esewa-webview');

//Flex Pay Callback
Route::any('/flexpay/return/url/{merchantId}', 'PaymentMethods\FlexPay\FlexPayController@FlexPayCallback')->name('flexpay-callback');

// paypal
Route::get('/paypal', 'PaymentMethods\RandomPaymentController@Paypal')->name('paypalview');
Route::get('/paypal/success', 'PaymentMethods\RandomPaymentController@paypal_success')->name('api.paypal_success_url');
Route::get('/paypal/fail', 'PaymentMethods\RandomPaymentController@paypal_fail')->name('api.paypal_fail_url');
Route::post('/paypal/notify', 'PaymentMethods\RandomPaymentController@paypal_notify')->name('api.paypal_notify_url');

//QuickPay
Route::get('/quickPay/checkout', 'PaymentMethods\RandomPaymentController@QuickPayCheckout')->name('quickpay.checkout');
// Route::post('/quickPay/checkOrder','PaymentMethods\RandomPaymentController@QuickPayReturn');
Route::get('/quickPay/approve', 'PaymentMethods\RandomPaymentController@QuickPayApprove')->name('quickpay.approve');
Route::get('/quickPay/cancel', 'PaymentMethods\RandomPaymentController@QuickPayCancel')->name('quickpay.cancel');
Route::get('/quickPay/decline', 'PaymentMethods\RandomPaymentController@QuickPayDecline')->name('quickpay.decline');

// Paygate Payhost payment gateway webview based url
//Step 1

Route::get('paygate/step1', 'PaymentMethods\Paygate\PaygateController@paygateStep1')->name('paygate-step1');
Route::get('paygate/step2', 'PaymentMethods\Paygate\PaygateController@paygateStep2')->name('paygate-step2');
Route::post('paygate/notify', 'PaymentMethods\Paygate\PaygateController@notify')->name('paygate-notify');
Route::any('paygate-success', 'PaymentMethods\Paygate\PaygateController@paygateStep3')->name('paygate-success');

// payphone response
Route::get('payphone-response', 'PaymentMethods\PayPhone\PayPhoneController@payPhoneResponse')->name('payphone-response');

// aamarpay
Route::any('aamarpay-success', 'PaymentMethods\AamarPay\AamarPayController@aamarPaysuccess')->name('aamarpay-success');
Route::any('aamarpay-fail', 'PaymentMethods\AamarPay\AamarPayController@aamarPayFail')->name('aamarpay-fail');
Route::any('aamarpay-cancel', 'PaymentMethods\AamarPay\AamarPayController@aamarPayCancel')->name('aamarpay-cancel');


// payfast
Route::any('payfast-success', 'PaymentMethods\RandomPaymentController@payFastSuccess')->name('payfast-success');
Route::any('payfast-fail', 'PaymentMethods\RandomPaymentController@payFastCancel')->name('payfast-fail');
//Route::get('payfast-redirect', ['as' => 'payfast-redirect', 'uses' => 'PaymentMethods\RandomController@payFastResponse'])->name('payfast-redirect');


Route::get('/edahab-request', 'PaymentMethods\RandomPaymentController@edahabRequest')->name('edahab-request');
Route::get('/edahab-return', 'PaymentMethods\RandomPaymentController@edahabReturn')->name('edahab-return');
Route::get('/edahab-success', 'PaymentMethods\RandomPaymentController@edahabSuccess')->name('edahab-success');
Route::get('/edahab-fail', 'PaymentMethods\RandomPaymentController@edahabFail')->name('edahab-fail');


//Route::get('/paybox-request', 'PaymentMethods\RandomPaymentController@payboxRequest')->name('paybox-request');
Route::get('/paybox-success', 'PaymentMethods\PayBox\PayBoxController@payboxSuccess')->name('paybox-success');
Route::get('/paybox-fail', 'PaymentMethods\PayBox\PayBoxController@payboxFail')->name('paybox-fail');
Route::get('/paybox-result', 'PaymentMethods\PayBox\PayBoxController@payboxResult')->name('paybox-result');


// mercado payment gateway
Route::get('/mercado/auth-code/response', 'PaymentMethods\Mercado\MercadoController@mercadoAuthCodeResponse')->name('mercado.code.response');
Route::post('/process_payment', 'PaymentMethods\Mercado\MercadoController@processPayment')->name('process-payment');
Route::get('mercado-webpage/{unique_no}/{locale}', 'PaymentMethods\Mercado\MercadoController@mercadoWebViewPage')->name('mercado-web-page');
Route::get('process-payment-success', 'PaymentMethods\Mercado\MercadoController@mercadoPageSuccess')->name('process-payment-success');
Route::get('process-payment-fail', 'PaymentMethods\Mercado\MercadoController@mercadoPageFail')->name('process-payment-fail');
Route::post('payment-notification', 'PaymentMethods\Mercado\MercadoController@cardPaymentNotification')->name('card-payment-notification');
Route::post('webhook-notification', 'PaymentMethods\Mercado\MercadoController@webhookNotification')->name('webhook-notification');
Route::get('mercado-webpage-split/{unique_no}/', 'PaymentMethods\Mercado\MercadoController@mercadoWebViewPageSplit')->name('mercado-web-page-split');
Route::post('/process_payment_split', 'PaymentMethods\Mercado\MercadoController@processPaymentSplit')->name('process-payment-split');


// kushki payment callback
Route::any('/kushki-transferin-status', 'PaymentMethods\Kushki\KushkiController@transferInCallback')->name('kushki.transferin.callback');
Route::any('/kushki-card-status', 'PaymentMethods\Kushki\KushkiController@cardStatus')->name('kushki.card.callback');

//hyperpay routs
Route::get('/loadView', 'PaymentMethods\HyperPay\HyperPayController@LoadView')->name('hyperpay.view');
Route::get('/redirectSaveCardView', 'PaymentMethods\HyperPay\HyperPayController@redirectSave')->name('hyperPayRedirectSave');
Route::get('/redirectView', 'PaymentMethods\HyperPay\HyperPayController@redirectUpdateStatus')->name('hyperPay.redirectUpdateStatus');
Route::post('/hyperPayRevarsal', 'PaymentMethods\HyperPay\HyperPayController@HyperPayRevarsal');
Route::get('/redirectSuccess/{msg?}', 'PaymentMethods\HyperPay\HyperPayController@RedirectSuccess')->name('HyperPaySuccess');
Route::get('/redirectFail/{msg?}', 'PaymentMethods\HyperPay\HyperPayController@RedirectFail')->name('HyperPayFail');

//PeachPayment routs
Route::get('/loadView', 'PaymentMethods\PeachPayment\PeachPaymentController@LoadView')->name('peach.view');
Route::get('/redirectSaveCardView', 'PaymentMethods\PeachPayment\PeachPaymentController@redirectSave')->name('PeachRedirectSave');
Route::get('/redirectView', 'PaymentMethods\PeachPayment\PeachPaymentController@redirectUpdateStatus')->name('peach.redirectUpdateStatus');
Route::post('/peachPaymentRevarsal', 'PaymentMethods\PeachPayment\PeachPaymentController@PeachReversal');
Route::get('/redirectSuccess/{msg?}', 'PaymentMethods\PeachPayment\PeachPaymentController@RedirectSuccess')->name('PeachSuccess');
Route::get('/redirectFail/{msg?}', 'PaymentMethods\PeachPayment\PeachPaymentController@RedirectFail')->name('PeachFail');


// paygate global webhook
Route::any('/paygate-global-webhook', 'PaymentMethods\PaygateGlobal\PaygateGlobalController@webhook')->name('paygate-global-webhook');
// square webhook
Route::any('/square-webhook', 'PaymentMethods\Square\SquareController@webhook')->name('square-webhook');

// DPO Think payment
Route::any('/dpo-callback', 'PaymentMethods\DPO\DpoController@PaymentCallBack')->name('dpo-callback');
Route::any('/dpo-back', 'PaymentMethods\DPO\DpoController@back')->name('dpo-back');


// touch pay call back
Route::any('/touch-pay-callback', 'PaymentMethods\TouchPay\TouchPayController@touchPayCallback')->name('touch-pay.callback');

// opay callback
Route::any('/opay-callback', 'PaymentMethods\Opay\OpayController@PaymentCallBack')->name('opay-callback');


// g-cash call back
Route::any('/gcash-callback', 'PaymentMethods\GCash\GCashController@gcashCallback')->name('gcash-callback');

// kbzpay call back
Route::any('/kbzpay/notify', 'PaymentMethods\Kbzpay\KbzpayController@notify')->name('kbzpay.notify');
Route::any('/kbzpay/referer', 'PaymentMethods\Kbzpay\KbzpayController@referer')->name('kbzpay.referer');
Route::any('/kbzpay/callback', 'PaymentMethods\Kbzpay\KbzpayController@callback')->name('kbzpay.callback');

// SamPay
Route::any('sampay-response', 'PaymentMethods\SamPay\SamPayController@samPayResponse')->name('sampay-response');

// PayGo
Route::any('paygo-callback', 'PaymentMethods\PayGo\PayGoController@PayGoCallback')->name('paygo-callback');
Route::any('paygo-redirect', 'PaymentMethods\PayGo\PayGoController@Redirect')->name('paygo-redirect');
Route::any('paygo-success', 'PaymentMethods\PayGo\PayGoController@Success')->name('paygo-success');
Route::any('paygo-fail', 'PaymentMethods\PayGo\PayGoController@Fail')->name('paygo-fail');

// KPay
Route::any('kpay-return', 'PaymentMethods\KPay\KPayController@KPayReturn')->name('kpay-return');
Route::any('kpay-redirect', 'PaymentMethods\KPay\KPayController@KPayRedirect')->name('kpay-redirect');

Route::any('/evMakCallback', 'PaymentMethods\EvMak\EvMakController@EvMakRequestCallback')->name('evmak.callback');
Route::any('/evMakPayOutCallback', 'PaymentMethods\EvMak\EvMakController@EvMakPayOutCallback')->name('evmak.payout.callback');

//MIPS callback
Route::any('/mips/request/return/{id_order}', 'PaymentMethods\MIPS\MIPSController@MIPSReturn')->name('mips.return');
Route::any('/mips/request/callback', 'PaymentMethods\MIPS\MIPSController@MIPSCallback')->name('mips.callback');
Route::get('/mips/success', 'PaymentMethods\MIPS\MIPSController@MIPSSuccess')->name('mips.success');
Route::get('/mips/failed', 'PaymentMethods\MIPS\MIPSController@MIPSFailed')->name('mips.failed');

// Waafi Payment success
Route::get('/waafi/success', 'PaymentMethods\Waafi\WaafiController@successCallBack')->name('waafi-success');
Route::get('/waafi/fail', 'PaymentMethods\Waafi\WaafiController@failCallBack')->name('waafi-fail');

Route::get('paymentfail', function () {
    return 'failed';
})->name("paymentfail");
Route::get('paymentcomplate', function () {
    return 'done';
})->name("paymentcomplate");
// Redirect URL
Route::any('paymentredirect', function () {
    return "Online Payment Redirection";
})->name('paymentredirect');

// Callback for Paryiff gateway to save the card
Route::any('/payriff-callback/{type}/{transaction_id}', ['as' => 'api.payriff.callback', 'uses' => 'PaymentMethods\Payriff\Payriff@callback']);


// Midtrans Call Back
Route::any('/midtrans/{transaction_id}', 'PaymentMethods\Midtrans\MidtransController@callback')->name("midtrans.callback");
Route::any('/success', 'PaymentMethods\Midtrans\MidtransController@Success')->name("midtrans-success");
Route::any('/fail', 'PaymentMethods\Midtrans\MidtransController@Fail')->name("midtrans-fail");

// Paypay payment
Route::any('/paypay-callback', 'PaymentMethods\PayPay\PaypayController@PaymentCallBack')->name('paypay-callback');

//PawaPay Payment
Route::any('/pawapay-callback', 'PaymentMethods\PawaPay\PawaPayController@Callback')->name('pawapay-callback');
Route::any('/pawapay-success', 'PaymentMethods\PawaPay\PawaPayController@Sucess')->name('pawapay-success');
Route::any('/pawapay-fail', 'PaymentMethods\PawaPay\PawaPayController@Fail')->name('pawapay-fail');

// Hubtel Payment Gateway Callback
Route::any('/hubtel/callback/{transaction_id}', 'PaymentMethods\Hubtel\HubtelController@callBack')->name('hubtel-callback');

// OneVision Callback
Route::any('/one-vision/{type}/{transaction_id}', ['as' => 'api.one-vision.callback', 'uses' => 'PaymentMethods\OneVision\OneVisionController@callback']);


//Route::get('paymentsuccess/{booking_id?}', 'PaymentController@returnResponse');
//Route::post('paymentsuccess/{booking_id?}', 'PaymentController@returnResponse');
//Route::post('payment/notify', 'PaymentController@notify');
//Route::get('managepayment/{paymentToken}', 'PaymentController@managepayment')->name('managecard');
//Route::get('deletemethod/{card_id}', 'PaymentController@deleteCard')->name('delete.method');
//
//Route::post('paymentUsingCard/{user_id}', 'PaymentController@PaymentUsingWeb')->name('payusingvault');
//Route::post('savecard/{user_id}','PaymentController@saveCard')->name('saveCard');
//Route::post('deletecard/{user_id}','PaymentController@deleteCard')->name('deleteCard');

// SenangPay URL
Route::get('/senangpay/callback', ['as' => 'user.api.senangpay-callback', 'uses' => 'PaymentMethods\RandomPaymentController@SenangPayCallback']);
Route::get('/senangpay/return', ['as' => 'user.api.senangpay-return', 'uses' => 'PaymentMethods\RandomPaymentController@SenangPayReturnUrl']);

// For Sahal Taxi
Route::post('/save-guest-user', ['as' => 'user.api.save-guest-user-info', 'uses' => 'Api\CommonController@SaveGuestUserInfo']);

// Pesapal Callback URL
Route::any('/pesapal/callback', 'PaymentMethods\Pesapal\PesapalController@PesapalCallback')->name('pesapal.callback');
Route::post('/pesapal/ipn', 'PaymentMethods\Pesapal\PesapalController@PesapalIPN')->name('pesapal.ipn');
Route::get('/pesapal/success', 'PaymentMethods\Pesapal\PesapalController@PesapalSuccess')->name('pesapal.success');
Route::get('/pesapal/fail', 'PaymentMethods\Pesapal\PesapalController@PesapalFailed')->name('pesapal.fail');

Route::any('/flo-ozoh/notification', 'PaymentMethods\RandomPaymentController@ozowNotification')->name('api.ozo-payment-notification');
Route::any('/flo-ozoh/success', 'PaymentMethods\RandomPaymentController@ozowSuccess')->name('api.ozo-payment-success');
Route::any('/payu/notification', 'PaymentMethods\RandomPaymentController@payuNotification')->name('api.payu-notification');
Route::any('/maxi-cash/success', 'PaymentMethods\RandomPaymentController@maxiCashSuccess')->name('api.maxi-cash-success');
Route::any('/maxi-cash/cancel', 'PaymentMethods\RandomPaymentController@maxiCashCancel')->name('api.maxi-cash-cancel');
Route::any('/maxi-cash/failure', 'PaymentMethods\RandomPaymentController@maxiCashFailure')->name('api.maxi-cash-failure');
Route::any('/maxi-cash/notification', 'PaymentMethods\RandomPaymentController@maxiCashNotification')->name('api.maxi-cash-notification');

// tripay payment gateway routes
Route::post('/tripay/callback', 'PaymentMethods\RandomPaymentController@TriPayCheckTransactionCallback');
Route::post('/tripay/redirect', 'PaymentMethods\RandomPaymentController@TriPayCheckTransactionRedirect');

//cashplus payment gateway routes
Route::post('/cashplus/callback', 'PaymentMethods\CashPlus\CashPlusController@Callback');

// bookeey payment gateway routes
Route::get('/bookeey/fail', 'PaymentMethods\RandomPaymentController@BookeeyFail')->name('BookeeyFail');
Route::get('/bookeey/success', 'PaymentMethods\RandomPaymentController@BookeeySuccess')->name('BookeeySuccess');
//jazzcash payment gateway routes
Route::get('jazzcash/webview', 'PaymentMethods\RandomPaymentController@JazzCashWebView')->name('jazzcash.webview');
Route::post('jazzcash/return', 'PaymentMethods\RandomPaymentController@JazzCashReturn')->name('jazzcash.return');
Route::get('jazzcash/success', 'PaymentMethods\RandomPaymentController@JazzCashSuccess')->name('jazzcash.success');
Route::get('jazzcash/fail', 'PaymentMethods\RandomPaymentController@JazzCashFail')->name('jazzcash.fail');

//pagoplux
Route::get('/pagoplux/load-view', 'PaymentMethods\PagoPlux\PagoPluxController@LoadView')->name('pagoplux.view');
Route::post('/pagoplux/redirect-save', 'PaymentMethods\PagoPlux\PagoPluxController@redirectSave')->name('pagoplux.redirect');
Route::get('/pagoplux/success/{msg?}', 'PaymentMethods\PagoPlux\PagoPluxController@RedirectSuccess')->name('PagoPluxSuccess');
Route::get('/pagoplux/fail/{msg?}', 'PaymentMethods\PagoPlux\PagoPluxController@RedirectFail')->name('PagoPluxFail');

//Wave Payment gateway routes
Route::any('wave/callback', 'PaymentMethods\Wave\WaveController@WaveNotify');
Route::any('wave/fail', 'PaymentMethods\Wave\WaveController@WaveFailed')->name('wave.fail');
Route::any('wave/success', 'PaymentMethods\Wave\WaveController@WaveSuccess')->name('wave.success');

Route::get('twilio-token', 'Helper\SmsController@twilioToken')->name('twilio.token');
Route::post('/get-secret-keys', 'Api\CommonController@getSecretKeys');

// Yoco Payment Callback urlss
Route::get('/yoco-payment-response/error/{message?}', 'PaymentMethods\Yoco\YocoController@error')->name("yoco.error");
Route::get('/yoco-payment-response/success/{transaction_id}/{payment_token?}', 'PaymentMethods\Yoco\YocoController@chargeCall')->name("yoco.charge");

// Uniwallet Payment Gateway Callback
Route::any('uniwallet/callback', 'PaymentMethods\Uniwallet\UniwalletController@callback')->name('uniwallet.callback');

// Tingg Payment Callback
Route::post('/tingg-callback', 'PaymentMethods\Tingg\TinggController@TinggCallback')->name('tingg.callback');
Route::any('/tingg-success', 'PaymentMethods\Tingg\TinggController@TinggSuccess')->name('tingg.success');
Route::any('/tingg-fail', 'PaymentMethods\Tingg\TinggController@TinggFailed')->name('tingg.fail');

//tap payment
Route::group(["prefix" => "tap"], function () {
    Route::any('/redirect', 'PaymentMethods\Tap\TapPaymentController@paymentCallback')->name('tap-payment-redirect');;
    Route::any('/success', 'PaymentMethods\Tap\TapPaymentController@TapSuccess')->name('tap-Success');
    Route::any('/failed', 'PaymentMethods\Tap\TapPaymentController@TapFail')->name('tap-Fail');
    Route::any('/post-url', 'PaymentMethods\Tap\TapPaymentController@TapPostUrl')->name('tap-posturl');

    Route::get('/load-save-card-view', 'PaymentMethods\Tap\TapController@LoadSaveCardView')->name('tap.load.saveCard.view');
    Route::post('/save-card', 'PaymentMethods\Tap\TapController@SaveCard')->name('tap.save-card-redirect');
    Route::any('/payment-redirect', 'PaymentMethods\Tap\TapController@paymentCallback')->name('tap.payment.redirect');
    Route::get('/success/{msg?}', 'PaymentMethods\Tap\TapController@TapSuccess')->name('tapSuccess');
    Route::get('/failed/{msg?}', 'PaymentMethods\Tap\TapController@TapFail')->name('tapFail');
});

//Payweb3 Paygate route
Route::group(['prefix' => 'payweb3'], function () {
    Route::get('/webview/{pay_request_id}/{checksum}', 'PaymentMethods\Payweb3\PaywebController@PayWeb3FormView')->name('payweb3.webview');
    Route::post('/return', 'PaymentMethods\Payweb3\PaywebController@PayWeb3Return')->name('payweb3.return');
    Route::get('/success', 'PaymentMethods\Payweb3\PaywebController@Payweb3Success')->name('payweb3.SuccessUrl');
    Route::get('/failed', 'PaymentMethods\Payweb3\PaywebController@Payweb3Fail')->name('payweb3.FailUrl');
});

//RevolutPay route
Route::group(['prefix' => 'revolutpay'], function () {
    Route::get('/success', 'PaymentMethods\RevolutPay\RevolutPaymentController@Success')->name('revolutpay-success');
    Route::get('/failed', 'PaymentMethods\RevolutPay\RevolutPaymentController@Failure')->name('revolutpay-fail');
    Route::get('/redirectapp', 'PaymentMethods\RevolutPay\RevolutPaymentController@redirectToApp')->name('revolutpay-redirectapp');
    Route::get('/redirect/{trans_id}', 'PaymentMethods\RevolutPay\RevolutPaymentController@Redirect')->name('revolutpay-redirect');
});

//xendit payment
Route::group(['prefix' => 'xenditpay'], function () {
    Route::any('/callback/{merchant_id}', 'PaymentMethods\XendItPay\XendItPayController@Callback')->name('xenditpay-callback');
    Route::any('/success', 'PaymentMethods\XendItPay\XendItPayController@Success')->name('xenditpay-success');
    Route::any('/fail', 'PaymentMethods\XendItPay\XendItPayController@Fail')->name('xenditpay-fail');
});

//WiPay route
Route::group(['prefix' => 'wipay'], function () {
    Route::any('/return', 'PaymentMethods\WiPay\WiPayController@WiPayReturn')->name('wipay.return');
    Route::get('/success/{msg?}', 'PaymentMethods\WiPay\WiPayController@WiPaySuccess')->name('wipay.success');
    Route::get('/failed/{msg?}', 'PaymentMethods\WiPay\WiPayController@WiPayFail')->name('wipay.fail');
});

//azampay callback
Route::any('/azampay/callback', 'PaymentMethods\AzamPay\AzamPayController@AzamPayCallback');

//pesepay callback
Route::group(['prefix' => 'pesepay'], function () {
    Route::any('/return', 'PaymentMethods\Pesepay\PesepayController@PesepayReturn')->name('pesepay.return');
    Route::any('/result', 'PaymentMethods\Pesepay\PesepayController@PesepayResult')->name('pesepay.result');
    Route::get('/success', 'PaymentMethods\Pesepay\PesepayController@PesepaySuccess')->name('pesepay.success-url');
    Route::get('/fail', 'PaymentMethods\Pesepay\PesepayController@PesepayFail')->name('pesepay.fail-url');
});

//Uni5Pay route Payment
Route::group(['prefix' => 'uni5pay'], function () {
    Route::any('/success', 'PaymentMethods\UniPay\UniPayController@UniPaySuccess')->name('unipay-success');
    Route::any('/failed', 'PaymentMethods\UniPay\UniPayController@UniPayFail')->name('unipay-failure');
    Route::any('/notify', 'PaymentMethods\UniPay\UniPayController@UniPayNotify')->name('unipay-notify');
});

//OrangeMoney Web route Payment
Route::group(['prefix' => 'orangemoneyweb'], function () {
    Route::any('/redirect/{refrenceId}', 'PaymentMethods\OrangeMoneyWeb\OrangeMoneyWebController@Redirect')->name('orangemoneyweb-redirect');
    Route::any('/success', 'PaymentMethods\OrangeMoneyWeb\OrangeMoneyWebController@Success')->name('orangemoneyweb-success');
    Route::any('/failed', 'PaymentMethods\OrangeMoneyWeb\OrangeMoneyWebController@Cancel')->name('orangemoneyweb-cancel');
    Route::any('/notify', 'PaymentMethods\OrangeMoneyWeb\OrangeMoneyWebController@Notify')->name('orangemoneyweb-notify');
});

//UBPAY Web payment
Route::group(['prefix' => 'ubpay'], function () {
    Route::any('/redirect/{merchantRef}', 'PaymentMethods\UbPay\UbPayController@Redirect')->name('ubpay-redirect');
    Route::any('/success/{merchantRef}', 'PaymentMethods\UbPay\UbPayController@Success')->name('ubpay-success');
    Route::any('/failed/{merchantRef}', 'PaymentMethods\UbPay\UbPayController@Fail')->name('ubpay-fail');
    Route::any('/cancelled/{merchantRef}', 'PaymentMethods\UbPay\UbPayController@Cancel')->name('ubpay-cancel');
});

//orangemoney core api 
Route::any('orangemoneycore/success', 'PaymentMethods\OrangeMoney\OrangeMoneyCoreController@Success')->name('orangemoneycore-success');
Route::any('orangemoneycore/failed', 'PaymentMethods\OrangeMoney\OrangeMoneyCoreController@Fail')->name('orangemoneycore-fail');
Route::any('orangemoneycore/notify', 'PaymentMethods\OrangeMoney\OrangeMoneyCoreController@Notify')->name('orangemoneycore-notify');

//orangeMoney B2B
Route::any('orangemoney/b2b/notifications/{merchant_id?}', 'PaymentMethods\OrangeMoney\OrangeMoneyB2B@notifications')->name('orangemoney-b2b-notifications');

//Callback Url Airtel Payment gatway
Route::group(['prefix' => 'airtel'], function () {
    Route::any('/callback', 'PaymentMethods\Airtel\AirtelPaymentController@Redirect')->name('airtel-redirect');
});

Route::any('mpesa-callback', 'PaymentMethods\Mpesa\MpesaController@mpesaCallback')->name('mpesa.callback');

//Return or Callback Url tranzak payment
Route::group(['prefix' => 'tranzak'], function () {
    Route::any('/redirect', 'PaymentMethods\Tranzak\TranzakController@Redirect')->name('tranzak-redirect');
});

//Payaw Success and fail url
Route::group(['prefix' => 'payaw'], function () {
    Route::any('/success', 'PaymentMethods\Payaw\PayawPaymentController@Success')->name('payaw-success');
    Route::any('/failed', 'PaymentMethods\Payaw\PayawPaymentController@Fail')->name('payaw-fail');
});

//cxpay Success and fail url
Route::group(['prefix' => 'cx-pay'], function () {
    Route::get('/step-two-form', 'PaymentMethods\CxPay\CxPayController@stepTwoForm')->name('cx-pay.step-two-form');
    Route::get('/complete-transaction', 'PaymentMethods\CxPay\CxPayController@CompleteTransaction')->name('cx-pay.complete-transaction');
    Route::get('/redirect-step-two', 'PaymentMethods\CxPay\CxPayController@redirectStepTwo')->name('cx-pay.redirect');
    Route::any('/success', 'PaymentMethods\CxPay\CxPayController@CxPaySuccess')->name('cxpay-success');
    Route::any('/failed', 'PaymentMethods\CxPay\CxPayController@CxPayFail')->name('cxpay-fail');
});


// api.bog.ge
Route::any('bog-success', 'PaymentMethods\Bog\BogPaymentController@bogPaysuccess')->name('bog-success');
Route::any('bog-fail', 'PaymentMethods\Bog\BogPaymentController@bogPayFail')->name('bog-fail');
Route::any('bog-callback', 'PaymentMethods\Bog\BogPaymentController@bogPayCallback')->name('bog-callback');


//api.aubpay
Route::any('aub-pay-callback', 'PaymentMethods\Aubpay\AubPayController@AubPayCallback')->name('aub-callback');
Route::any('aub-fail', 'PaymentMethods\Aubpay\AubPayController@AubPayFail')->name('aub-fail');
Route::any('aub-success', 'PaymentMethods\Aubpay\AubPayController@AubPaySuccess')->name('aub-success');

//crdb card payment
Route::any('crdb-success', 'PaymentMethods\CRDB\CrdbPay@successCallBack')->name('crdb-success');
Route::any('crdb-faliure', 'PaymentMethods\CRDB\CrdbPay@failiureCallBack')->name('crdb-faliure');

//ligdiCash card payment
Route::any('ligdicash-success', 'PaymentMethods\LigdiCash\LigdiCashPaymentController@successCallBack')->name('ligdicash-success');
Route::any('ligdicash-cancel', 'PaymentMethods\LigdiCash\LigdiCashPaymentController@CancelCallBack')->name('ligdicash-cancel');
Route::any('ligdicash-callback', 'PaymentMethods\LigdiCash\LigdiCashPaymentController@handleCallback')->name('ligdicash-callback');


//PayNow Return and result url
Route::group(['prefix' => 'paynow'], function () {
    Route::any('/return', 'PaymentMethods\PayNow\PayNowController@return')->name('paynow-return');
    Route::any('/result', 'PaymentMethods\PayNow\PayNowController@result')->name('paynow-result');
    Route::any('/success', 'PaymentMethods\PayNow\PayNowController@Success')->name('paynow-success');
    Route::any('/fail', 'PaymentMethods\PayNow\PayNowController@Fail')->name('paynow-fail');
});

Route::any("fasthub-callback", 'PaymentMethods\FastHub\FastHubController@fasthubCallback')->name("fasthub-callback");

Route::post('cacpay/verify-otp', 'PaymentMethods\Cacpay\CacPaymentController@verifyOtp')->name("cacpay-verifyOtp");

Route::any("peach-redirect", function () {
    return "Payment Request Has Been Processed Successfully";
})->name('peach-redirect');


// Dibsy Payment redirect and webhook url
Route::group(['prefix' => 'dibsy'], function () {
    Route::any('/redirect/{transactionId}', 'PaymentMethods\DibsyPayment\DibsyPaymentController@redirect')->name('dibsy-redirect');
    Route::any('/webhook/{merchant_id}/{transactionId}', 'PaymentMethods\DibsyPayment\DibsyPaymentController@webhook')->name('dibsy-webhook');
    Route::any('/success', 'PaymentMethods\DibsyPayment\DibsyPaymentController@Success')->name('dibsy-success');
    Route::any('/fail', 'PaymentMethods\DibsyPayment\DibsyPaymentController@Fail')->name('dibsy-fail');
});

//Paysuite Success and fail url
Route::group(['prefix' => 'paysuite'], function () {
    Route::any('/callback', 'PaymentMethods\PaySuite\PaySuiteController@callbackUrl')->name('paysuite-callback');
    Route::any('/redirect', 'PaymentMethods\PaySuite\PaySuiteController@Redirect')->name('paysuite-redirect');
    Route::any('/success', 'PaymentMethods\PaySuite\PaySuiteController@Success')->name('paysuite-success');
    Route::any('/failed', 'PaymentMethods\PaySuite\PaySuiteController@Fail')->name('paysuite-fail');
});

//FlutterWave Standard
Route::group(['prefix' => 'flutterwave-standard'], function () {
    Route::any('/callback', 'PaymentMethods\FlutterWave\FlutterWaveStandard@callbackUrl')->name('flutterwave-callback');
    Route::any('/success', 'PaymentMethods\FlutterWave\FlutterWaveStandard@Success')->name('flutterwave-success');
    Route::any('/failed', 'PaymentMethods\FlutterWave\FlutterWaveStandard@Fail')->name('flutterwave-fail');
});

Route::group(['prefix' => 'Maxicash'], function () {
    Route::any('/success', 'PaymentMethods\Maxicash\MaxicashController@Success')->name('maxicashweb-success');
    Route::any('/cancel', 'PaymentMethods\Maxicash\MaxicashController@Cancel')->name('maxicashweb-cancel');
    Route::any('/notify', 'PaymentMethods\Maxicash\MaxicashController@Notify')->name('maxicashweb-notify');
    Route::any('/failed', 'PaymentMethods\Maxicash\MaxicashController@Failure')->name('maxicashweb-failure');
    Route::any('/maxicash/{user_id}/{calling_from}/{payment_config_id}/{amt}/{locale}{transaction_id}/{phone}/{currency}', 'PaymentMethods\Maxicash\MaxicashController@processPayment')->name('maxicash-process-payment');
});

Route::group(['prefix' => 'Khalti'], function () {
    Route::any('/notify', 'PaymentMethods\Khalti\khaltiController@notify')->name('Khalti-notify');
    Route::any('/returnUrl', 'PaymentMethods\Khalti\khaltiController@returnUrl')->name('khalti-noti');
    Route::any('/success', 'PaymentMethods\Khalti\khaltiController@successUrl')->name('khalti-success');
    Route::any('/fail', 'PaymentMethods\Khalti\khaltiController@failUrl')->name('khalti-fail');
});

Route::group(['prefix' => 'Budpay'], function () {
    Route::any('/success', 'PaymentMethods\BudPay\BudPayController@Success')->name('budpay-success');
    Route::any('/failed', 'PaymentMethods\BudPay\BudPayController@Failure')->name('budpay-fail');
    Route::any('/notify', 'PaymentMethods\BudPay\BudPayController@returnUrl')->name('budpay-notify');
});

//NetCash WebView
Route::group(['prefix' => 'netcash'], function () {
    Route::any('/accept', 'PaymentMethods\NetCash\NetCashPaymentController@accept')->name('netcash-accept');
    Route::any('/decline', 'PaymentMethods\NetCash\NetCashPaymentController@decline')->name('netcash-decline');
    Route::any('/success', 'PaymentMethods\NetCash\NetCashPaymentController@success')->name('netcash-success');
    Route::any('/fail', 'PaymentMethods\NetCash\NetCashPaymentController@fail')->name('netcash-fail');
    Route::any('/notify', 'PaymentMethods\NetCash\NetCashPaymentController@notify')->name('netcash-notify');
    Route::any('/redirect', 'PaymentMethods\NetCash\NetCashPaymentController@redirect')->name('netcash-redirect');
    Route::any('/web-view-form', 'PaymentMethods\NetCash\NetCashPaymentController@webview')->name('netcash-webview');
});

//CRDB CARD PAYMENT
Route::get('/crdb-pay/{user_id}/{calling_from}/{payment_config_id}/{amt}/{locale}/{currency}', 'PaymentMethods\CRDB\CrdbPay@processPayment')->name('crdb-process-payment');
Route::post('/confirm-crdb-pay/{id}/{calling_from}/{config_id}', 'PaymentMethods\CRDB\CrdbPay@confirm')->name('confirm-crdb-pay');


Route::post('whatsapp/new-message', 'Api\WhatsappController@receivedNewMessage');

//Route for Authorize.net payment gateway

//Route For In-App-Calling
Route::post('/twilio/redirect-call/{mobile}', function ($mobile) {


    $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
        "<Response>\n" .
        "    <Dial>\n" .
        "        <Number\n" .
        "         statusCallbackEvent=\"initiated ringing answered completed\"\n" .
        "         statusCallback=\"https://webhook.site/06eeadd8-acff-4f33-9cd4-9ce29949dc00\"\n" .
        "         statusCallbackMethod=\"POST\">\n" .
        "            {$mobile}\n" .
        "        </Number>\n" .
        "    </Dial>\n" .
        "</Response>";


    return response($content, 200)
        ->header('Content-Type', 'text/xml');
})->name("in-app-call-redirect");





/*map load api */
Route::group(['middleware' => ['merchant']], function () {
    Route::post('/map-load', 'Helper\GoogleController@mapLoad');
    Route::post('/static-map-load', 'Helper\GoogleController@staticMapLoad');
    Route::post('/country-list', 'Helper\CommonController@countryList');

    Route::post('/payment-gateway-list', ['as' => 'api.merchant.payment-gateway', 'uses' => 'Api\CommonController@getPaymentGateway']);

    // get handyman bookings of merchant
    Route::post('/booking-list', 'Api\HandymanOrderController@bookingList');
    //chap merchant api
    Route::post('/bookings-driver-detail', 'Api\BookingController@getBookingsDetail');

    Route::post('/get-navigation-drawer', 'Api\NavigationDrawerController@getNavigationDrawer');
    Route::post('/save-navigation-drawer', 'Api\NavigationDrawerController@saveNavigationDrawer');
    Route::post('/get-navigation-drawer-config', 'Api\NavigationDrawerController@getNavigationDrawerConfig');

    // Uniwallet Get Network List
    Route::post('{type}/uniwallet/get-networks', 'PaymentMethods\Uniwallet\UniwalletController@getNetworkList')->name('uniwallet.getNetworks');
    Route::post('{type}/uniwallet/name-enquiry', 'PaymentMethods\Uniwallet\UniwalletController@nameEnquiry')->name('uniwallet.nameEnquiry');

    // Encryption Key
    Route::group(['middleware' => ['limit_api']], function () {
        Route::post('get-encryption-key', 'Api\CommonController@getEncryptionKey');
        Route::post('get-decryption-key', 'Api\CommonController@getDecryptionKey');
    });

    Route::get('/get-website-strings', 'Api\CommonController@getWebsiteString');

    Route::post('/get-auth-token', 'Api\MerchantController@getAuthToken');
    Route::post('/get-bookings', 'Api\MerchantController@getBooking');
});

// api's for user app
Route::prefix('user')->group(function () {
    Route::any('/verifyFlutterwaveTransaction', ['as' => 'api.verifyFlutterwaveTransaction', 'uses' => 'Api\CardController@verifyFlutterwaveTransaction']);
    // api request either login token or merchant public+ secret key
    Route::group(['middleware' => ['merchant']], function () {
        //website
        Route::post('/website/home-screen', 'Api\WebsiteController@HomeScreen');
        Route::post('/website/segment-home-screen', 'Api\WebsiteController@segmentHomeScreen');
        Route::post('/website/service', 'Api\WebsiteController@cars');
        Route::post('/carsWithoutLogin', 'Api\HomeController@cars');
        //        Route::post('/checkEstimate', 'Api\BookingController@estimate');
        Route::post('/AddWalletMoneyCoupon', 'Api\CommonController@AddWalletMoneyCoupon');
        Route::post('/configuration', ['as' => 'api.user.configuration', 'uses' => 'Api\UserController@Configuration']);
        Route::get('/fastlane-configuration', ['as' => 'api.user.configuration', 'uses' => 'Api\UserController@FastLaneConfiguration']);
        Route::post('/countryList', ['as' => 'api.user.countryList', 'uses' => 'Api\CommonController@CountryList']);

        Route::group(['middleware' => ['limit_api']], function () {
            Route::post('/otp', ['as' => 'api.user-otp', 'uses' => 'Api\UserController@Otp']);
        });

        Route::post('/cms/pages', 'Api\CommonController@UserCmsPage');
        Route::post('/faq', 'Api\CommonController@UserFaq');
        //Account
        Route::post('/getnetworkcode', 'Api\CommonController@getNetworkCode');

        // Login OLD Version
        Route::post('/demoUser', ['as' => 'user.api.demoUser', 'uses' => 'Account\UserController@DemoUser']);
        Route::post('/login', ['as' => 'api.user-login', 'uses' => 'Account\UserController@Login']);
        Route::post('/login/otp', ['as' => 'api.user-login-otp', 'uses' => 'Account\UserController@loginOtp']);
        Route::post('/signup', ['as' => 'api.user.signup', 'uses' => 'Account\UserController@SignUp']);
        Route::post('/socialsingup', ['as' => 'api.user-socialsingup', 'uses' => 'Account\UserController@SocialSignup']);
        Route::post('/socialsignin', ['as' => 'api.user-socialsignin', 'uses' => 'Account\UserController@SocialSign']);

        // Login New Version
        Route::post('/demo-onboard', ['as' => 'user.api.demoUser', 'uses' => 'Account\UserController@DemoUser']);
        Route::post('/on-board', ['as' => 'api.user-login', 'uses' => 'Account\UserController@Login']);
        Route::post('/on-board/otp', ['as' => 'api.user-login-otp', 'uses' => 'Account\UserController@loginOtp']);
        Route::post('/normal-reg', ['as' => 'api.user.signup', 'uses' => 'Account\UserController@SignUp']);
        Route::post('/social-reg', ['as' => 'api.user-socialsingup', 'uses' => 'Account\UserController@SocialSignup']);
        Route::post('/social-on-board', ['as' => 'api.user-socialsignin', 'uses' => 'Account\UserController@SocialSign']);

        Route::post('/validate-data', 'Api\UserController@SignupValidation');

        Route::post('/forgotpassword', ['as' => 'api.user.password', 'uses' => 'Account\UserController@ForgotPassword']);
        Route::post('/details', 'Account\UserController@Details');
        Route::post('/edit-profile', 'Account\UserController@EditProfile');

        Route::post('/getString', ['as' => 'api.getLatestString', 'uses' => 'Api\StringLanguageController@getLatestString']);
        Route::post('/korbapayment', 'PaymentMethods\RandomPaymentController@korbaWeb')->name('korbapayment');

        Route::post('/on-board/otp-validate', ['as' => 'api.user-login-new-otp', 'uses' => 'Account\UserController@validateOtp']);

        Route::post('/guest/login', ['as' => 'api.user.guest.login', 'uses' => 'Account\UserController@guestLogin']);
        Route::post("/in-app-call", ['as' => 'api.user.in_app_call', 'uses' => 'Api\CommonController@inAppCalling']);
    });

    // api request with login token
    Route::group(['middleware' => ['auth:api', 'validuser']], function () {
        // user home screen for vehicle based segment
        //Route::post('/cars', ['as' => 'api.cars', 'uses' => 'Api\HomeController@index']);

        //bons qr bank to bank payment
        Route::post('/get-bons-bank-details', ['as' => 'user.get-bons-bank-details', 'uses' => 'Api\CommonController@getBonsBankDetails']);
        Route::post('/submit-bons-bank-details', ['as' => 'user.submit-bons-bank-details', 'uses' => 'Api\CommonController@submitBonsBankDetails']);

        Route::post('/get-delivery-package', ['as' => 'api.delivery-package', 'uses' => 'Api\HomeController@getDeliveryPackage']);
        Route::post('/cars', ['as' => 'api.cars', 'uses' => 'Api\HomeController@userHomeScreen']);
        Route::post('/rental-cars', ['as' => 'api.cars', 'uses' => 'Api\HomeController@rentalCars']);
        Route::post('/checkout', ['as' => 'api.checkout', 'uses' => 'Api\BookingController@checkout']);
        Route::post('/checkout-additional-info', ['as' => 'api.checkout-additional-info', 'uses' => 'Api\BookingController@checkoutAdditionalInfo']);
        Route::post('/outstation-details', ['as' => 'api.outstation', 'uses' => 'Services\OutstationController@outstationDetail']);
        Route::post('/checkout-payment', ['as' => 'api.booking-payment', 'uses' => 'Api\BookingController@checkoutPayment']);
        Route::post('/changePaymentOption', 'Api\BookingController@changePaymentDuringRide');

        Route::post('/confirm', ['as' => 'api.booking-confirm', 'uses' => 'Api\BookingController@confirmBooking']);

        Route::post('/in-drive-confirm', ['as' => 'api.in-drive-booking-confirm', 'uses' => 'Api\BookingController@inDriveBookingConfirm']);

        Route::post('/booking/details', ['as' => 'api.user.booking.details', 'uses' => 'Api\BookingController@bookingDetails']);
        //        Route::post('/PagaditoPayment',['as' => 'api.PagaditoPayment', 'uses' => 'Api\RandomPaymentController@PagaditoPayment']);

        //        Route::post('/delivery/homescreen', 'Delivery\ApiController@HomeScreen');
        //        Route::post('/delivery/vehicleType', 'Delivery\ApiController@VehicleType');
        //        Route::post('/delivery/checkout', 'Delivery\ApiController@Checkout');

        //Delivery Routes
        Route::post('/delivery/checkout', 'Api\DeliveryController@Checkout');
        Route::post('/delivery/checkout-details', 'Api\DeliveryController@CheckoutDetails');
        Route::post('/delivery/checkout/store-drop-details', 'Api\DeliveryController@storeCheckoutDetails');
        Route::post('/delivery/checkout/vehicle-delivery-package', 'Api\DeliveryController@getVehicleFromDeliveryPackage');
        Route::post('/confirm/delivery', ['as' => 'api.booking-confirm-delivery', 'uses' => 'Api\DeliveryController@Confirm']);


        Route::post('/increaseRideRequestArea', ['as' => 'api.increaseRideRequestArea', 'uses' => 'Api\BookingController@getNextRadiusDriver']);

        // sos routes
        Route::post('/sos', 'Api\SosController@SosUser');
        Route::post('/sos/create', 'Api\SosController@addSosUser');
        Route::post('/sos/distory', 'Api\SosController@delete');
        //Account module
        Route::post('/details', 'Account\UserController@Details');
        Route::post('/UserDetail', 'Account\UserController@UserDetail');
        Route::post('/edit-profile', ['as' => 'api.edit-profile', 'uses' => 'Account\UserController@EditProfile']);
        Route::post('/add-tip', 'Api\BookingController@addTip');

        Route::post('/logout', ['as' => 'api.user-logout', 'uses' => 'Account\UserController@Logout']);
        Route::post('/out-board', ['as' => 'api.user-logout', 'uses' => 'Account\UserController@Logout']);

        Route::post('/change-password', ['as' => 'api.change-password', 'uses' => 'Account\UserController@ChangePassword']);
        Route::post('/userDocList', 'Api\UserController@getCountryDocuments');
        Route::post('/userDocSave', 'Api\UserController@addDocument');

        Route::post('/favouritedrivers', 'Api\BookingController@getFavouriteDrivers');
        Route::post('/updateTerms', 'Api\UserController@UserTermUpdate');
        Route::post('/paytmchecksum', 'Api\CardController@PaytmChecksum');
        Route::post('/prepareCheckout', 'Api\CardController@prepareCheckout');
        Route::get('/paymentStatus', 'Api\CardController@paymentStatus');
        Route::get('/notify', 'Api\CardController@prepareCheckout')->name('notify');
        Route::post('/IugoPayment', 'Api\CardController@IugoPayment');
        Route::post('/creatPrefId', 'Api\CardController@prefIdMercado');
        Route::post('/flutterwavePaymentRequest', 'Api\CardController@flutterwavePaymentRequest');
        Route::post('/YoPaymentRequest', 'Api\CardController@YoPaymentRequest');

        Route::post('BancardCheckout', 'PaymentMethods\RandomPaymentController@BancardCheckout');
        Route::get('/redirectBancard', 'PaymentMethods\RandomPaymentController@redirectBancard')->name('redirectBancard');
        //Route::get('/redirectBancard', 'PaymentMethods\RandomPaymentController@redirectBancard');
        Route::post('/createTransDPO', 'PaymentMethods\RandomPaymentController@createTransDPO');
        Route::post('/mobileMoneyDPO', 'PaymentMethods\RandomPaymentController@DpoMobileMoney');
        Route::post('/beyonicMobileMoney', 'PaymentMethods\RandomPaymentController@beyonicMobileMoney');
        Route::post('/verifymobileMoneyDPO', 'PaymentMethods\RandomPaymentController@verifyMobileMoneyDPO');
        //user cancel reason
        Route::post('/cancel-reasons', ['as' => 'user.api.cancel.reason', 'uses' => 'Api\CommonController@cancelReason']);
        Route::post('/receipt', ['as' => 'user.api.viewDoneRideInfo', 'uses' => 'Api\BookingController@UserReceipt']);
        Route::post('/save-card', ['as' => 'user.api.save-card', 'uses' => 'Api\CardController@SaveCard']);

        Route::post('/senangpay/tokenization', ['as' => 'user.api.senangpay-token', 'uses' => 'Api\CardController@SenangPayToken']);
        Route::post('/senangpay/record/transaction', ['as' => 'user.api.senangpay-record', 'uses' => 'PaymentMethods\RandomPaymentController@SenangPayRecordTransaction']);

        //        Route::post('/make_payment', 'Api\CardController@pay');
        Route::post('/cards', ['as' => 'api.user.cards', 'uses' => 'Api\CardController@Cards']);
        Route::post('/card/delete', ['as' => 'api.user.delete.card', 'uses' => 'Api\CardController@DeleteCard']);
        Route::post('/card/make-payment', ['as' => 'api.user.card.payment', 'uses' => 'Api\CardController@CardPayment']);

        Route::post('/paymaya/save-card', 'PaymentMethods\PayMaya\PayMayaController@createToken');

        Route::post('/peachsavecard', 'PaymentMethods\RandomPaymentController@tokenizePeach');

        Route::get('/mpes', 'PaymentMethods\MpesContoller@start');
        Route::post('/paymentsubmit', 'PaymentMethods\MpesContoller@paymentsubmit')->name('paymentsubmit');
        Route::get('/paymentresponse', 'PaymentMethods\MpesContoller@paymentresponse')->name('paymentresponse');
        Route::get('/paymentsuccessfull', 'PaymentMethods\MpesContoller@paymentSuccess')->name('paymentsuccessfull');
        Route::get('/paymentfailed', 'PaymentMethods\MpesContoller@paymentFailed')->name('paymentfailed');


        // t family members in app
        Route::post('/AddFamilyMember', 'Api\UserController@AddFamilyMember');
        Route::post('/DeleteFamilyMember', 'Api\UserController@DeleteFamilyMember');
        Route::post('/ListFamilyMember', 'Api\UserController@ListFamilyMember');
        Route::post('/check_babySeat', ['as' => 'api.user.babySeat', 'uses' => 'Api\UserController@check_babySeat']);

        // check additional features/facilities while booking ride
        Route::post('/CheckSeats', ['as' => 'api.user.CheckSeats', 'uses' => 'Api\UserController@CheckSeats']);
        Route::post('/check_wheelChair', ['as' => 'api.user.wheelChair', 'uses' => 'Api\UserController@check_wheelChair']);

        Route::post('/wallet/transaction', ['as' => 'api.user.wallet', 'uses' => 'Api\UserController@WalletTransaction']);
        Route::post('/wallet/addMoney', ['as' => 'api.user.addMoney', 'uses' => 'Api\UserController@AddMoneyWallet']);
        Route::post('/sos/request', ['as' => 'user.api.sos.request', 'uses' => 'Api\CommonController@SosRequest']);

        Route::post('/refer', ['as' => 'api.user-refer', 'uses' => 'Api\UserController@Referral']);

        // mark/remove favourite driver
        Route::post('/favourite-driver', ['as' => 'api.favourite-driver', 'uses' => 'Api\UserController@favouriteDriver']);

        /*This Api has been merged with get favourite driver in case of checkout*/
        // This is still using in app so uncommenting it again, 20231201
        Route::post('/favourite/drivers', ['as' => 'api.user-favouritedriver', 'uses' => 'Api\UserController@FavouriteDrivers']);
        /*This api is merged with add fav driver*/
        //        Route::post('/delete-favourite-driver', ['as' => 'ap.delete-favourite-driver', 'uses' => 'Api\UserController@DeleteFavouriteDrivers']);
        Route::post('/get-favourite-driver', ['as' => 'api.favourite.driver', 'uses' => 'Api\UserController@getFavouriteDriver']);
        Route::post('/location', ['as' => 'api.location', 'uses' => 'Api\UserController@Location']);
        //Route::post('/test/location', ['as' => 'api.location', 'uses' => 'Api\UserController@UserLocation']);

        //mark fav location to get easy drop location for taxi segment
        //Fav location module is merged with add address in Account/userController, so no use of this module
        Route::post('/get-favourite-location', ['as' => 'api.favourite.view-location', 'uses' => 'Api\CommonController@viewFavouriteLocation']);
        Route::post('/add-favourite-location', ['as' => 'api.save-favourite.location', 'uses' => 'Api\CommonController@saveFavouriteLocation']);
        Route::post('/delete-favourite-location', ['as' => 'api.delete-favourite.location', 'uses' => 'Api\CommonController@deleteFavouriteLocation']);

        Route::post('/pricecard', ['as' => 'api.pricecard', 'uses' => 'Api\CommonController@Pricecard']);
        //        Route::post('/pricecard-delivery', ['as' => 'api.pricecard-delivery', 'uses' => 'Delivery\ApiController@Pricecard']);
        Route::post('/checkout/apply-promo', ['as' => 'api.apply-promo', 'uses' => 'Api\BookingController@ApplyPromoCode']);
        Route::post('/checkout/remove-promo', ['as' => 'api.remove-promo', 'uses' => 'Api\BookingController@RemovePromoCode']);
        Route::post('/driver', ['as' => 'api.home-driver', 'uses' => 'Api\HomeController@homeScreenDrivers']);
        Route::post('/homescree/driver', ['as' => 'api.homescree-driver', 'uses' => 'Api\HomeController@UserHomeScreenDrivers']);
        Route::post('/areas', ['as' => 'api.home-areas', 'uses' => 'Api\HomeController@Areas']);
        Route::post('/payment-option', ['as' => 'api.payment-option', 'uses' => 'Api\BookingController@paymentOption']);
        Route::post('/check-ride-time', 'Api\CommonController@CheckBookingTime');
        Route::post('/check-droplocation/area', ['as' => 'api.droplocation-area', 'uses' => 'Api\HomeController@CheckDropLocation']);
        Route::post('/booking/cancel', ['as' => 'api.user.booking.cancel', 'uses' => 'Api\BookingController@cancelBookingByUSer']);
        Route::post('/booking/autocancel', ['as' => 'api.user.booking.autocancel', 'uses' => 'Api\BookingController@UserAutoCancel']);
        Route::post('/booking/change_address', ['as' => 'api.user.booking.changeaddess', 'uses' => 'Api\BookingController@UserChangeAddress']);

        // booking tracking on user app
        Route::post('/booking/tracking', ['as' => 'api.user.booking.tracking', 'uses' => 'Api\BookingController@userTracking']);

        Route::post('/rate-to-driver', ['as' => 'api.user.rate.driver', 'uses' => 'Api\CommonController@rateToDriverByUser']);
        //        Route::post('/booking/rate/driver', ['as' => 'api.user.rate.driver', 'uses' => 'Api\BookingController@UserRating']);
        Route::post('/booking/active', ['as' => 'api.user.active.booking', 'uses' => 'Api\BookingHistoryController@ActiveBookings']);
        //        Route::post('/booking/history', ['as' => 'api.user.bookings', 'uses' => 'Api\BookingHistoryController@UserBookings']);
        Route::post('/booking/history/detail', ['as' => 'api.user.bookings.details', 'uses' => 'Api\BookingHistoryController@BookingDetail']);

        //        Route::post('/booking/history/active', ['as' => 'api.user.active.bookings', 'uses' => 'Api\BookingHistoryController@userHistoryBookings']);
        Route::post('/booking/history', ['as' => 'api.user.active.bookings', 'uses' => 'Api\BookingHistoryController@userHistoryBookings']);

        Route::post('/booking/invoice/{booking_id}', ['as' => 'api.user.active.bookings', 'uses' => 'Api\EmailController@Invoice']);
        Route::post('/booking/make-payment', ['as' => 'api.user.bookings.payment', 'uses' => 'Api\BookingController@MakePayment']);
        Route::post('/promotion/notification', ['as' => 'api.promotion.notification', 'uses' => 'Api\UserController@PromotionNotification']);
        Route::post('/chat/send_message', ['as' => 'api.user.send_message', 'uses' => 'Api\ChatController@UserSendMessage']);
        Route::post('/chat', ['as' => 'api.user.chat', 'uses' => 'Api\ChatController@ChatHistory']);

        Route::post('/customer_support', ['as' => 'api.user.customer_support', 'uses' => 'Api\CommonController@Customer_Support']);
        Route::post('/AverageRating', 'Api\CommonController@UserRaing');

        Route::post('/redeem-points', 'Api\CommonController@redeemPoints');
        Route::post('/reward-points', 'Api\UserController@rewardPoints');

        //this mpesa api is not in use
        Route::post('mpessaAddmoney', 'PaymentMethods\RandomPaymentController@MpessaAddMoney');
        //this mpesa api is not in use

        Route::post('/bayarindAddMoney', 'PaymentMethods\RandomPaymentController@BayarindAddMoney');

        // UBpay payment gateway
        Route::post('/ubpay', ['as' => 'api.ubpay', 'uses' => 'PaymentMethods\RandomPaymentController@UbpayGetUrl']);

        //Ragerpay store transaction
        Route::post('/razerpay/transaction', 'PaymentMethods\RandomPaymentController@razerpayTransaction');
        Route::post('/razerpay/logs', 'PaymentMethods\RandomPaymentController@razerpayUserLog');

        // 2C2P payment gateway
        Route::post('/2c2p/transaction', 'PaymentMethods\RandomPaymentController@TwoCTwoPStoreTransaction');

        Route::group(["prefix" => "paypay"], function () {
            Route::post('/get-payment-methods', ['as' => 'api.paypay.get-payment-methods', 'uses' => 'PaymentMethods\PayPay\PaypayAfricaController@getPaymentMethods']);
        });

        //NetCash WebView
        Route::group(["prefix" => "netcash"], function () {
            Route::post('/payment-form', ['as' => 'api.netcash.payment-form', 'uses' => 'PaymentMethods\NetCash\NetCashPaymentController@generatePaymentForm']);
        });

        // Pesapal transaction store
        Route::post('/pesapal/transaction', 'PaymentMethods\RandomPaymentController@PesapalTransaction');

        // Delivery Routes
        Route::post('/delivery/product-list', 'Api\DeliveryController@getDeliveryProduct');
        Route::post('/manage-tip', 'Api\CommonController@addTip');

        Route::post('/dpo-paygate-initiate', 'PaymentMethods\RandomPaymentController@dpoPaygateInitiate');

        Route::post('amole/otp/generate', ['as' => 'api.user.amole.otp.generate', 'uses' => 'PaymentMethods\EwalletController@amoleGeneratePaymentOtp']);

        Route::post('/search-store-products', ['as' => 'api.search-store-product', 'uses' => 'Api\FoodController@searchStoreProducts']);

        // Food  Module APIs
        Route::prefix('food')->group(function () {
            Route::post('/home-screen', ['as' => 'api.user.food.home.screen', 'uses' => 'Api\FoodController@homeScreen']);
            Route::post('/product-list', ['as' => 'api.user.food.products', 'uses' => 'Api\FoodController@foodProducts']);
            Route::post('/get-product', ['as' => 'api.user.food.products', 'uses' => 'Api\FoodController@productDetails']);
            Route::post('/save-product-cart', ['as' => 'api.save.product-cart', 'uses' => 'Api\FoodController@saveProductCart']);
            Route::post('/get-product-cart', ['as' => 'api.get.product-cart', 'uses' => 'Api\FoodController@getProductCart']);
            Route::post('/apply-remove-promo-code', ['as' => 'api.food-promo-code', 'uses' => 'Api\FoodController@applyRemovePromoCode']);
            Route::post('/save-packaging-preferences', ['as' => 'api.save.packaging-preferences', 'uses' => 'Api\FoodController@savePackagingPreferences']);
            Route::post('/place-order', ['as' => 'api.place-food-order', 'uses' => 'Api\FoodController@placeOrder']);
            Route::post('/get-orders', ['as' => 'api.get-food-order', 'uses' => 'Api\FoodController@getOrders']);
            Route::post('/get-order-details', ['as' => 'api.get-food-order', 'uses' => 'Api\FoodController@getOrderDetails']);
            Route::post('/delete-product-cart', ['as' => 'api.delete-product-or-cart', 'uses' => 'Api\FoodController@deleteCart']);
            Route::post('/table-booking', ['as' => 'api.table-booking', 'uses' => 'Api\FoodController@TableBooking']);
        });
        // Grocery Module APIs
        Route::prefix('grocery')->group(function () {
            Route::post('/home-screen', ['as' => 'api.user.grocery.home.screen', 'uses' => 'Api\GroceryController@homeScreen']);
            Route::post('/get-store-categories', ['as' => 'api.user.store-category', 'uses' => 'Api\GroceryController@getCategory']);
            Route::post('/get-sub-category', ['as' => 'api.user.get-sub-category', 'uses' => 'Api\GroceryController@getSubCategory']);
            Route::post('/category-products', ['as' => 'api.user.category.products', 'uses' => 'Api\GroceryController@categoryProducts']);
            Route::post('/get-category-product', ['as' => 'api.user.category.products', 'uses' => 'Api\GroceryController@categoryProductDetails']);
            Route::post('/save-product-cart', ['as' => 'api.save.product-cart', 'uses' => 'Api\GroceryController@saveProductCart']);
            Route::post('/subscribe-plan-list', ['as' => 'api.subscribe.plan.list', 'uses' => 'Api\GroceryController@subscribePlanList']);
            Route::post('/subscribe-product', ['as' => 'api.subscribe.product', 'uses' => 'Api\GroceryController@subscribeProduct']);
            Route::post('/subscribe-product-details', ['as' => 'api.subscribe.product.details', 'uses' => 'Api\GroceryController@SubscribedProductsDetails']);
            Route::post('/get-subscribe-product-list', ['as' => 'api.get.subscribe.product', 'uses' => 'Api\GroceryController@getSubscribedProducts']);
            Route::post('/update-subscribe-product', ['as' => 'api.update.subscribe.product', 'uses' => 'Api\GroceryController@UpdateSubscribeProduct']);
            Route::post('/get-product-cart', ['as' => 'api.get.product-cart', 'uses' => 'Api\GroceryController@getProductCart']);
            Route::post('/apply-remove-promo-code', ['as' => 'api.food-promo-code', 'uses' => 'Api\GroceryController@applyRemovePromoCode']);
            Route::post('/save-packaging-preferences', ['as' => 'api.save.packaging-preferences', 'uses' => 'Api\GroceryController@savePackagingPreferences']);
            Route::post('/checkout/remove-promo', ['as' => 'api.remove-promo', 'uses' => 'Api\BookingController@RemovePromoCode']);
            Route::post('/place-order', ['as' => 'api.grocery.place.order', 'uses' => 'Api\GroceryController@placeOrder']);
            Route::post('/get-orders', ['as' => 'api.get-grocery-order', 'uses' => 'Api\GroceryController@getOrders']);
            //            Route::post('/cancel-order', ['as' => 'api.cancel-grocery-order', 'uses' => 'Api\GroceryController@cancelOrder']);
            Route::post('/get-order-details', ['as' => 'api.get-grocery-order', 'uses' => 'Api\GroceryController@getOrderDetails']);
            Route::post('/delete-product-cart', ['as' => 'api.delete-product-or-cart', 'uses' => 'Api\GroceryController@deleteCart']);
                
            Route::post('/add-remove-favourite-product', ['as' => 'api.user.add-remove-grocery-favt-product', 'uses' => 'Api\GroceryController@addRemoveFavouriteProduct']);
            Route::post('/favourite-products', ['as' => 'api.user.grocery-favt-products', 'uses' => 'Api\GroceryController@favouriteProducts']);
            Route::post('/product', ['as' => 'api.user.grocery-product', 'uses' => 'Api\GroceryController@product']);
        });

        // food and grocery receipt api
        Route::post('/order-receipt', ['as' => 'api.user.food.grocery.receipt', 'uses' => 'Api\OrderController@orderReceipt']);
        Route::post('/track-order', ['as' => 'api.track-order', 'uses' => 'Api\OrderController@trackOrder']);
        Route::post('/track-order-details', ['as' => 'api.track-order', 'uses' => 'Api\OrderController@trackOrderDetails']);
        Route::post('/order-cancel', ['as' => 'api.cancel-order', 'uses' => 'Api\OrderController@userCancelOrder']);

        Route::post('/segments', ['as' => 'api.user.merchant.segments', 'uses' => 'Api\MainScreenController@mainScreenSegments']);
        Route::post('/add-address', ['as' => 'api.user.add-address', 'uses' => 'Account\UserController@saveUserAddress']);
        Route::post('/get-address', ['as' => 'api.user.get-address', 'uses' => 'Account\UserController@getUserAddress']);
        Route::post('/delete-address', ['as' => 'api.user.delete-address', 'uses' => 'Account\UserController@deleteUserAddress']);
        Route::post('/get-promo-code', ['as' => 'api.get-promo-code-list', 'uses' => 'Api\MainScreenController@getPromoCodeList']);
        Route::post('/service-slots', ['as' => 'api.user.merchant.service-slots', 'uses' => 'Api\DriverController@getServiceTimeSlot']);
        Route::post('/payment-methods', ['as' => 'api.user.payment.method', 'uses' => 'Api\CommonController@getPaymentMethod']);

         //laundry-module
        Route::prefix('laundry-outlet')->group(function () {
            Route::post('/home-screen', ['as' => 'api.user.laundry.home.screen', 'uses' => 'LaundryOutlet\Api\LaundryOutletController@homeScreen']);
            Route::post('/get-categories', ['as' => 'api.user.laundry-category', 'uses' => 'LaundryOutlet\Api\LaundryOutletController@getCategory']);
            Route::post('/category-services', ['as' => 'api.user.laundry.services', 'uses' => 'LaundryOutlet\Api\LaundryOutletController@categoryServices']);
            Route::post('/save-service-cart', ['as' => 'api.save.service-cart', 'uses' => 'LaundryOutlet\Api\LaundryServiceController@saveServiceCart']);
            Route::post('/delete-service-cart', ['as' => 'api.delete-service-or-cart', 'uses' => 'LaundryOutlet\Api\LaundryServiceController@deleteCart']);
            Route::post('/get-service-cart', ['as' => 'api.get.service-cart', 'uses' => 'LaundryOutlet\Api\LaundryServiceController@getServiceCart']);
            Route::post('/apply-remove-promo-code', ['as' => 'api.service-promo-code', 'uses' => 'LaundryOutlet\Api\LaundryServiceController@applyRemovePromoCode']);
             Route::post('/drop-slots', ['as' => 'api.drop.slots', 'uses' => 'LaundryOutlet\Api\LaundryServiceController@dropSlots']);
            Route::post('/place-order', ['as' => 'api.service.place.order', 'uses' => 'LaundryOutlet\Api\LaundryServiceController@placeOrder']);
            Route::post('/get-orders', ['as' => 'bs.all-orders', 'uses' => 'LaundryOutlet\Api\LaundryServiceController@getOrders']);
            Route::post('/get-order-details', ['as' => 'api.get-laundry-order', 'uses' => 'LaundryOutlet\Api\LaundryServiceController@getOrderDetails']);
            Route::post('/order-cancel', ['as' => 'api.cancel-order', 'uses' => 'LaundryOutlet\Api\LaundryServiceController@userCancelOrder']);
        });

        Route::prefix('handyman')->group(function () {
            Route::post('/service-slots', ['as' => 'api.user.merchant.handyman.service-slots', 'uses' => 'Api\PlumberController@getServiceTimeSlot']);
            Route::post('/get-providers', ['as' => 'api.get-driver-list', 'uses' => 'Api\PlumberController@getPlumbers']);
            Route::post('/get-provider', ['as' => 'api.get-driver', 'uses' => 'Api\PlumberController@getPlumber']);
            Route::post('/get-categories', ['as' => 'api.get-handyman-categories', 'uses' => 'Api\PlumberController@getCategories']);
            Route::post('/get-services', ['as' => 'api.get-handyman-services', 'uses' => 'Api\PlumberController@getPlumberServices']);
            Route::post('/apply-remove-promo-code', ['as' => 'api.handyman.promo-code', 'uses' => 'Api\PlumberController@applyRemovePromoCode']);
            Route::post('/save-booking-cart', ['as' => 'api.save.handyman-booking.cart', 'uses' => 'Api\PlumberController@saveBookingCart']);
            Route::post('/get-cart', ['as' => 'api.get.handyman-booking.cart', 'uses' => 'Api\PlumberController@getCart']);
            Route::post('/delete-cart', ['as' => 'api.delete.handyman-booking.cart', 'uses' => 'Api\PlumberController@deleteCart']);
            Route::post('/confirm-order', ['as' => 'api.handyman.confirm-order', 'uses' => 'Api\PlumberController@confirmOrder']);
            Route::post('/confirm-order-web', ['as' => 'api.handyman.confirm-order', 'uses' => 'Api\PlumberController@confirmOrderWeb']);
            Route::post('/apply-remove-promo-code-web', ['as' => 'api.handyman.promo-code-web', 'uses' => 'Api\PlumberController@applyRemovePromoCodeWeb']);
            Route::post('/get-orders', ['as' => 'api.handyman.orders', 'uses' => 'Api\PlumberController@getOrders']);
            Route::post('/get-order-detail', ['as' => 'api.handyman.order.detail', 'uses' => 'Api\PlumberController@getOrderDetail']);
            Route::post('/cancel-order', ['as' => 'api.handyman.cancel.order', 'uses' => 'Api\PlumberController@cancelOrder']);
            Route::post('/rate/provider', ['as' => 'api.provider.rate', 'uses' => 'Api\HandymanOrderController@providerRating']);
            Route::post('/booking-payment', ['as' => 'api.booking.payment', 'uses' => 'Api\HandymanOrderController@bookingPayment']);

            Route::prefix('bidding')->group(function () {
                Route::post('/get-orders', ['as' => 'api.handyman.bidding.orders', 'uses' => 'Api\PlumberBiddingController@getOrders']);
                Route::post('/get-order-detail', ['as' => 'api.handyman.bidding.order', 'uses' => 'Api\PlumberBiddingController@getOrderDetail']);
                Route::post('/create-order', ['as' => 'api.handyman.bidding.create.order', 'uses' => 'Api\PlumberBiddingController@createOrder']);
                Route::post('/accept-order', ['as' => 'api.handyman.bidding.accept.order', 'uses' => 'Api\PlumberBiddingController@acceptOrder']);
                Route::post('/cancel-or-delete-order', ['as' => 'api.handyman.bidding.cancel-or-delete.order', 'uses' => 'Api\PlumberBiddingController@cancelDeleteOrder']);
                Route::post('/apply-remove-promo-code', ['as' => 'api.handyman.bidding.apply-remove-promo-code', 'uses' => 'Api\PlumberBiddingController@ApplyRemovePromoCode']);
            });
        });

        //Check User for Wallet Transaction
        Route::post('/check-user', ['as' => 'api.check-user', 'uses' => 'Api\UserController@CheckUser']);
        Route::post('/transfer-money', ['as' => 'api.transfer-money', 'uses' => 'Api\UserController@TransferWalletMoney']);

        Route::post('/direction-data', ['as' => 'api.user-direction', 'uses' => 'Api\CommonController@googleDirectionData']);

        // User Carpooling Segment Related API's
        Route::post('/get-document-list', ['as' => 'api.user-document-list', 'uses' => 'Account\UserController@getDocumentList']);
        Route::post('/add-document', ['as' => 'api.user-add-document', 'uses' => 'Account\UserController@addDocument']);

        // Skip If all documents are non mandatory
        Route::post('/skip-document-step', ['as' => 'api.user-skip-document-step', 'uses' => 'Account\UserController@skipDocumentStep']);

        Route::post('/get-payment-method', ['as' => 'api.user-get-payment-method', 'uses' => 'Account\UserController@countryWisePaymentGateway']);
        Route::post('/get-cashout-method', ['as' => 'api.user-get-cashout-method', 'uses' => 'Account\UserController@countryWiseCashoutPayment']);
        Route::post('/get-country-area', ['as' => 'api.get-country-area', 'uses' => 'Api\HomeController@getCountryAreaList']);


        // User vehicle api's
        Route::post('/vehicle-configuration', ['as' => 'api.user-vehicle-config', 'uses' => 'Api\UserVehicleController@vehicleConfiguration']);
        Route::post('/vehicle-model', ['as' => 'api.user-vehicle-model', 'uses' => 'Api\DriverVehicleController@getVehicleModel']);
        Route::post('/add-vehicle', ['as' => 'api.user-add-vehicle', 'uses' => 'Api\UserVehicleController@addVehicle']);
        Route::post('/vehicle-request', ['as' => 'api.user-vehicle-request', 'uses' => 'Api\UserVehicleController@VehicleRequest']);
        Route::post('/vehicle-request-verify', ['as' => 'api.user-vehicle-request-verify', 'uses' => 'Api\UserVehicleController@vehicleOtpVerify']);
        Route::post('/vehicle-delete', ['as' => 'api.user.vehicle.delete', 'uses' => 'Api\UserVehicleController@VehicleDelete']);
        Route::post('/bank-details/update', ['as' => 'user.bank-details-update', 'uses' => 'Api\UserController@BankDetailsUpdate']);

        // user vehicle Default
        Route::post('/vehicle-default', ['as' => 'user.vehicle.default', 'uses' => 'Api\UserVehicleController@userVehicleDefault']);
        Route::post('/get-vehicle-list', ['as' => 'user.vehicle.list', 'uses' => 'Api\UserVehicleController@UserVehicleList']);

        // Cashout Module
        Route::post('/cashout/request', ['as' => 'merchant.user.cashout.request', 'uses' => 'Api\UserCashoutController@request']);
        Route::post('/cashout/history', ['as' => 'merchant.user.cashout.history', 'uses' => 'Api\UserCashoutController@index']);


        

        //paypal
        // This Option added inside Online make payment api
        Route::post('/paypal', 'PaymentMethods\RandomPaymentController@PaypalWebViewURL');

        // Illico Cash Payment
        Route::post('/illicocash/payment', 'PaymentMethods\EwalletController@illicoCashPayment');
        Route::post('/illicocash/payment/confirm', 'PaymentMethods\EwalletController@illicoCashPaymentOTP');

        // Tripay Cash Payment
        Route::post('/tripay/get-payment-channels', 'PaymentMethods\RandomPaymentController@TriPayPaymentChannels');
        Route::post('/tripay/payment', 'PaymentMethods\RandomPaymentController@TriPayCreateTransaction');

        // Bookeey
        Route::post('/bookeey/url', 'PaymentMethods\RandomPaymentController@BookeeyURL')->name('BookeeyURL');
        Route::post('/paygate-webview-url', 'PaymentMethods\Paygate\PaygateController@getWebViewUrl')->name('api.get-paygate-webview');

        // payment via online payment options
        Route::post('/online/make-payment', ['as' => 'api.user.online.payment', 'uses' => 'PaymentMethods\Payment@onlinePayment']);
        Route::post('/online/payment-status', ['as' => 'api.user.online.payment.status', 'uses' => 'PaymentMethods\Payment@onlinePaymentStatus']);
        Route::post('/momopay/make-payment', ['as' => 'api.user.momopay.payment', 'uses' => 'PaymentMethods\RandomPaymentController@MOMOPayRequest']);
        Route::post('/pending-transactions', ['as' => 'api.user.pending.transactions', 'uses' => 'PaymentMethods\Payment@pendingTransactions']);

        // ProxyPay
        Route::post('/proxy_pay/initiate_transaction', ['as' => 'proxy_pay.initiate_transaction', 'uses' => 'PaymentMethods\ProxyPay\ProxyPayController@createReference']);
        Route::post('/proxy_pay/transaction_status', ['as' => 'proxy_pay.transaction_status', 'uses' => 'PaymentMethods\ProxyPay\ProxyPayController@acknowledgePayment']);

        //Payhere payment gateway
        Route::post('/PayHere/AddCard', ['as' => 'PayHere.AddCardTransaction', 'uses' => 'PaymentMethods\PayHere\PayHereController@AddCardTransaction']);
        //jazzcash Payment Gateway
        Route::post('jazzcash', 'PaymentMethods\RandomPaymentController@JazzCash');
        //mpesaB2C api
        Route::post('/mpesa-lib/b2c/request', 'PaymentMethods\RandomPaymentController@submitB2CRequest');

        Route::post('/kushki-document-list', ['as' => 'user.kushki-document-list', 'uses' => 'PaymentMethods\Kushki\KushkiController@getKushkiDocumentList']);

        //hyperPay
        Route::post('/hyperPay/SaveCardCheckout', 'PaymentMethods\HyperPay\HyperPayController@HyperPaySaveCardCheckout');
        Route::post('/hyperPay/PaymentCheckout', 'PaymentMethods\HyperPay\HyperPayController@HyperPayPaymentCheckout');

        //PeachPayment
        Route::post('/peach/SaveCardCheckout', 'PaymentMethods\PeachPayment\PeachPaymentController@PeachSaveCardCheckout');
        Route::post('/peach/PaymentCheckout', 'PaymentMethods\PeachPayment\PeachPaymentController@PeachPaymentCheckout');
        //QuickPay
        Route::post('/quickPay', 'PaymentMethods\RandomPaymentController@QuickPay');


        // mark/remove favourite business-segment
        Route::post('/favourite-business-segment', ['as' => 'api.favourite-business-segment', 'uses' => 'Api\FoodController@favouriteBusinessSegment']);
        Route::post('/get-favourite-business-segment', ['as' => 'api.favourite.business-segment', 'uses' => 'Api\FoodController@getFavouriteBusinessSegment']);

        Route::post('/webxpay/make-payment/{check_for}', 'PaymentMethods\Webxpay\Webxpay@makePayment');

        Route::post('/interswitch/payment-initiate/{check_for}', 'PaymentMethods\Interswitch\InterswitchController@paymentInitiate');
        Route::post('/interswitch/payment-confirmation/{check_for}', 'PaymentMethods\Interswitch\InterswitchController@paymentConfirmation');

        Route::post('/account-delete', 'Api\UserController@AccountDelete');

        //TelebirrPay
        // Not in use added in Online Make Payment API
        Route::post('/generateTelebirrPayUrl', 'PaymentMethods\TelebirrPay\TelebirrPayController@generateTeliberrUrl');

        // Orange Money
        Route::post('/orange', 'PaymentMethods\OrangeMoney\OrangeMoneyController@OrangeMoneyURL');

        //ihelaPay
        Route::post('/get-bank-list', 'PaymentMethods\Ihela\IhelaController@getIhelaBankList');
        Route::post('/customer-account-lookup', 'PaymentMethods\Ihela\IhelaController@IhelaCustomerAccountLookup');
        Route::post('/generate-ihela-url', 'PaymentMethods\Ihela\IhelaController@generateIhelaUrl');
        Route::post('/check-ihela-payment-status', 'PaymentMethods\Ihela\IhelaController@checkPaymentStatus');
        //OrangeMoney Push
        Route::post('/orange-money-push', 'PaymentMethods\RandomPaymentController@OrangeMoneyPush');
        //BillBox
        Route::post('/get-payment-option-list', 'PaymentMethods\BillBox\BillBoxController@getPaymentOptionList');
        Route::post('/create-invoice', 'PaymentMethods\BillBox\BillBoxController@createInvoice');
        Route::post('/process-payment', 'PaymentMethods\BillBox\BillBoxController@processPayment');
        Route::post('/check-payment-status', 'PaymentMethods\BillBox\BillBoxController@checkPaymentStatus');
        //EvMak
        Route::post('/send-request', 'PaymentMethods\EvMak\EvMakController@EvMakSendRequest');
        Route::post('/evmak/payout-request', 'PaymentMethods\EvMak\EvMakController@EvMakPayOutRequest'); //MIPS
        Route::post('/mips/request', 'PaymentMethods\MIPS\MIPSController@MIPS');

        //send chat message to store
        Route::post('/chat/send_message_to_store', ['as' => 'user.chat.send_message_to_store', 'uses' => 'Api\ChatController@UserSendMessageToStore']);

        //chat history api between store and user
        Route::post('/chat/store_and_user', ['as' => 'user.chat.store_and_user', 'uses' => 'Api\ChatController@ChatHistoryBetweenStoreAndUser']);

        // get & set strings key value of app via API
        Route::post('/get-key-value-strings', 'Api\CommonController@getAppStrings');
        Route::post('/set-key-value-strings', 'Api\CommonController@setAppStrings');

        Route::post('/redeem-reward-points', 'Api\UserController@RedeemRewardPoints');

        Route::post('/get-payment-options', ['as' => 'api.payment-options', 'uses' => 'Api\CommonController@getPaymentOptions']);

        // Credit Account Details
        Route::post('/store-credit-account-details', ['as' => 'api.store-credit-account-details', 'uses' => 'Api\CreditAccountDetailController@storeDetails']);
        Route::post('/get-credit-account-details', ['as' => 'api.get-credit-account-details', 'uses' => 'Api\CreditAccountDetailController@getDetails']);

        // Glomo Money Payment Gateway
        Route::group(["prefix" => "glomo-money"], function () {
            // for credit
            Route::post('/validate-phone', ['as' => 'api.glomo-money.validate-phone', 'uses' => 'PaymentMethods\GlomoMoney\GlomoMoney@checkPhoneNumber']);

            // for Debit payment types
            Route::post('/payment-types', ['as' => 'api.glomo-money.payment-types', 'uses' => 'PaymentMethods\GlomoMoney\GlomoMoney@paymentTypes']);
            Route::post('/make-payment', ['as' => 'api.glomo-money.make-debit-payment', 'uses' => 'PaymentMethods\GlomoMoney\GlomoMoney@makeDebitPayment']);
            Route::post('/check-payment', ['as' => 'api.glomo-money.check-debit-payment', 'uses' => 'PaymentMethods\GlomoMoney\GlomoMoney@checkDebitPayment']);
        });

        // Sahay Payment Gateway
        Route::group(["prefix" => "sahay"], function () {
            Route::post('/check-phone-number', ['as' => 'api.sahay.check-phone-number', 'uses' => 'PaymentMethods\Sahay\SahayGateway@checkPhoneNumber']);
            Route::post('/request-payment', ['as' => 'api.sahay.request-payment', 'uses' => 'PaymentMethods\Sahay\SahayGateway@requestPayment']);
            Route::post('/confirm-payment', ['as' => 'api.sahay.confirm-payment', 'uses' => 'PaymentMethods\Sahay\SahayGateway@confirmPayment']);
        });

        // Ebankily Payment Gateway
        Route::post('/ebankily/make-payment', ['as' => 'api.ebankily.make-payment', 'uses' => 'PaymentMethods\Ebankily\Ebankily@makePayment']);

        // Payriff Payment Gateway
        Route::post('/payriff/create-order', ['as' => 'api.payriff.create-order', 'uses' => 'PaymentMethods\Payriff\Payriff@createOrder']);
        Route::post('/payriff/check-payment', ['as' => 'api.payriff.check-payment', 'uses' => 'PaymentMethods\Payriff\Payriff@checkPayment']);

        Route::post('/payriff/save-card-order', ['as' => 'api.payriff.save-card-order', 'uses' => 'PaymentMethods\Payriff\Payriff@saveCardOrder']);
        //Pagoplux
        Route::post('/pagoplux/save-card-web-view', ['as' => 'api.pagoplux.save.card', 'uses' => 'PaymentMethods\PagoPlux\PagoPluxController@PagoPluxSaveCardCheckout']);
        //Hubtel
        Route::post('/hubtel/create-order', ['as' => 'api.hubtel.create-order', 'uses' => 'PaymentMethods\Hubtel\HubtelController@createOrder']);
        //Wave Payout
        Route::post('/wave-payout', 'PaymentMethods\Wave\WaveController@WavePayout');

        // Midtrans Payment Gateway
        //        Route::post('/midtrans/create-transaction', ['as' => 'api.midtrans.create-transaction', 'uses' => 'PaymentMethods\Midtrans\MidtransController@createTransaction']);
        /**
         * Get Job Vacancies on  user app screen
         */
        Route::post('/job-vacancies', 'Api\JobVacancyController@jobVacanies');
        Route::post('/apply-job', 'Api\JobVacancyController@applyJob');
        // ViuPay
        Route::group(["prefix" => "viu-pay"], function () {
            Route::post('/get-payment-methods', ['as' => 'api.viu.get-payment-methods', 'uses' => 'PaymentMethods\ViuPay\ViuPayController@getPaymentMethods']);
            Route::post('/get-payment-options', ['as' => 'api.viu.get-payment-options', 'uses' => 'PaymentMethods\ViuPay\ViuPayController@getPaymentOption']);
            Route::post('/set-payment-option', ['as' => 'api.viu.save-payment-option', 'uses' => 'PaymentMethods\ViuPay\ViuPayController@setPaymentOption']);
            Route::post('/initiate-payment', ['as' => 'api.viu.initiate-payment', 'uses' => 'PaymentMethods\ViuPay\ViuPayController@initiatePayment']);
        });

        //PawaPay
        Route::group(["prefix" => "pawa-pay"], function () {
            Route::post('/get-payment-options', ['as' => 'api.pawapay.get-payment-options', 'uses' => 'PaymentMethods\PawaPay\PawaPayController@getPaymentCorrespondentOption']);
        });

        //BudPay
        Route::group(["prefix" => "bud-pay"], function () {
            Route::post('/get-service-providers', ['as' => 'api.budpay.service-providers', 'uses' => 'PaymentMethods\BudPay\BudPayController@getServiceProvider']);
        });

        //Binance
        Route::prefix('Binance')->group(function () {
            Route::post('/bank-list', 'PaymentMethods\Binance\BinanceController@bankList')->name('Binance-bankList');
        });

        // Hub2 Payment Gateway
        Route::post('/hub2/get-payment-options', ['as' => 'api.hub2.get-payment-methods', 'uses' => 'PaymentMethods\Hub2\Hub2Controller@getPaymentOptions']);
        Route::post('/hub2/validate-otp', ['as' => 'api.hub2.validate-otp', 'uses' => 'PaymentMethods\Hub2\Hub2Controller@validateOTP']);

        // Uniwallet Payment Gateway
        Route::post('uniwallet/debit-request', 'PaymentMethods\Uniwallet\UniwalletController@createTransaction');
        Route::post('uniwallet/check-request', 'PaymentMethods\Uniwallet\UniwalletController@checkTransaction');

        // Ebankily Payment Gateway
        Route::post('/ebankily/make-payment', ['as' => 'api.ebankily.make-payment', 'uses' => 'PaymentMethods\Ebankily\Ebankily@makePayment']);

        // Midtrans Payment Gateway
        Route::post('/midtrans/create-transaction', ['as' => 'api.midtrans.create-transaction', 'uses' => 'PaymentMethods\Midtrans\MidtransController@createTransaction']);

        // Yoco Payment Gateway
        Route::post('/yoco-payment', 'PaymentMethods\Yoco\YocoController@makePayment');
        //OrangeMoney Push
        Route::post('/orange-money-payment', 'PaymentMethods\OrangeMoney\OrangeMoneyController@MakeOrangeMoneyPayment');
        Route::post('/orange-money-payout', 'PaymentMethods\OrangeMoney\OrangeMoneyController@OrangeMoneyPayout');
        // OneVision Callback
        Route::post('/one-vision/initiate-payment', ['as' => 'api.one-vision.initiate-payment', 'uses' => 'PaymentMethods\OneVision\OneVisionController@initiatePayment']);
        Route::post('/one-vision/check-payment', ['as' => 'api.one-vision.check-payment', 'uses' => 'PaymentMethods\OneVision\OneVisionController@checkPayment']);

        //AfriMoney Gambia
        Route::post('/afri-money/process-payment', 'PaymentMethods\AfriMoneyGambia\AfriMoneyGambiaController@processPayment');
        Route::post('/afri-money/check-payment-status', 'PaymentMethods\AfriMoneyGambia\AfriMoneyGambiaController@checkTransactionEnquiry');

        Route::group(["prefix" => "tap"], function () {
            Route::post('/generate-save-card-url', 'PaymentMethods\Tap\TapController@generateSaveCardUrl');
            Route::post('/generate-payment-url', 'PaymentMethods\Tap\TapController@generatePaymentUrl');
        });

        //Reward System
        Route::post('/reward-gift-list', 'Api\RewardGiftController@getRewardGiftList');
        Route::post('/redeem-reward-gift', 'Api\RewardGiftController@RedeemRewardGift');
        Route::post('/check-eligibility', 'Api\RewardGiftController@checkEligibleRewardGift');
        Route::post('/reward-history', 'Api\RewardGiftController@getRewardHistory');
        Route::post('/redeemed-rewards', 'Api\RewardGiftController@getRedeemedGifts');
        Route::post('/redeem-reward-point', 'Api\RewardGiftController@RedeemRewardGift');

        //azampay
        Route::group(["prefix" => "azampay"], function () {
            Route::post('/send-payment-request', 'PaymentMethods\AzamPay\AzamPayController@MakeAzamPayPayment');
            Route::post('/providers-list', 'PaymentMethods\AzamPay\AzamPayController@AzamPayProviders');
        });

        Route::post('/moov-money/send-verify-otp', 'PaymentMethods\MoovMoney\MoovMoneyController@SendMoovMoneyOTP');

        Route::post('/serfinsa/make-payment', 'PaymentMethods\Serfinsa\SerfinsaController@serfinsaMakePayment');

        //tranzak payment
        Route::group(["prefix" => "tranzak"], function () {
            Route::post('/send-payment-request', 'PaymentMethods\Tranzak\TranzakController@MakeTranzakPayment');
        });

        //airtel payment
        Route::group(["prefix" => "airtel"], function () {
            Route::post('/payment', 'PaymentMethods\Airtel\AirtelPaymentController@MakeAirtelPayment');
            Route::post('/check-payment-status', 'PaymentMethods\Airtel\AirtelPaymentController@PaymentStatus');
        });

        //S3P payment
        Route::group(["prefix" => "s3p"], function () {
            Route::post('/cashout-services', 'PaymentMethods\S3P_Pay\S3PPayController@CashoutServices');
            Route::post('/make-payment', 'PaymentMethods\S3P_Pay\S3PPayController@MakePayment');
            Route::post('/payment-verify', 'PaymentMethods\S3P_Pay\S3PPayController@PaymentVerify');
        });


      

        //vendor payaw payment
        Route::group(["prefix" => "payaw"], function () {
            Route::any('/send-payment-request', 'PaymentMethods\Payaw\PayawPaymentController@MakePayawPayment');
            Route::any('/check-payment-status', 'PaymentMethods\Payaw\PayawPaymentController@PaymentStatus');
        });

        //cx-pay payment gateway
        Route::post('cx-pay/step-one', 'PaymentMethods\CxPay\CxPayController@processStepOne')->name('cx-pay.step-one.get');

        //check status for pending transaction in app side
        Route::post('/check-transaction-status', 'PaymentMethods\RandomPaymentController@CheckPaymentTransactionStatus');
        Route::post('/cancel-transaction', 'PaymentMethods\RandomPaymentController@CancelTransaction');

       

        //smart pay
        Route::post('/smart-pay/check-user-driver', 'PaymentMethods\SmartPay\SmartPayController@checkUser');
        Route::post('/smart-pay/register', 'PaymentMethods\SmartPay\SmartPayController@registerOnSmartPay');
        Route::post('/smart-pay/make-payment', 'PaymentMethods\SmartPay\SmartPayController@processSmartPayPayment');

        //Pesepay
        Route::post('/pesepay/generate-payment-url', 'PaymentMethods\Pesepay\PesepayController@generatePesepayUrl');

        //mpesa
        Route::group(["prefix" => "mpesa"], function () {
            Route::post('/submit-request', 'PaymentMethods\Mpesa\MpesaController@mpesaExpress');
            Route::post('/fetch-transaction-status', 'PaymentMethods\Mpesa\MpesaController@mpesaTransactionStatus');
        });

        Route::post('/stripe/create-intent', 'PaymentMethods\Payment@getStripeIntentSecret');

        Route::post('/search/places', ['as' => 'api.search-places', 'uses' => 'Api\CommonController@searchPlaces']);
        Route::post('/wallet-recharge-request', 'Api\CommonController@walletRechageRequest');
        Route::post("/sos-request", ['as' => 'api.user.sos', 'uses' => 'Api\CommonController@sos']);

        /**Subscription for User */
        Route::post('/get-subscriptions-list', ['as' => 'api.user-view-subscription-packages', 'uses' => 'Api\SubscriptionPackageController@getSubscriptionPackageList']);
        Route::post('/get-subscriptions-history', ['as' => 'api.user-view-subscription-history', 'uses' => 'Api\SubscriptionPackageController@getSubscriptionHistory']);
        Route::post('/get-active-subscription', ['as' => 'api.user-active-subscription', 'uses' => 'Api\SubscriptionPackageController@getActiveSubscription']);
        Route::post('/activate-subscription-package', ['as' => 'api.user-activate-subscription-packages', 'uses' => 'Api\SubscriptionPackageController@ActivatePackage']);
        Route::post('/country-area-segments', ['as' => 'api.user.segments', 'uses' => 'Api\UserController@getCountryAreasegments']);
        
        Route::post('/user-additional-details', ['as' => 'api.user-additional-details', 'uses' => 'Api\UserController@userAdditionalDetails']);
    });
});

// driver app api
Route::prefix('driver')->group(function () {
    Route::group(['middleware' => ['driver']], function () {

        Route::post('/booking-notification-api', 'Api\CommonController@BookingNotificationApi');
        Route::post('/vehicle-details/dvla', 'Api\DriverController@DvlaVehivehicleDetails');
        //Latra Integeration for verify vehicle license
        Route::post('/verify-vehicle-license', 'Api\LatraController@verifyVehicleLicense');

        Route::post('/validate-data', 'Api\DriverController@SignupValidation');
        // Driver Registration step one
        Route::post('/reg-step-one', ['as' => 'api.driver.reg-step-one', 'uses' => 'Api\DriverController@RegStepOne']);

        Route::post('/reg-step-two', ['as' => 'api.driver.reg-step-two', 'uses' => 'Api\DriverController@RegStepTwo']);
        // add working mode for driver
        Route::post('/reg-step-three', ['as' => 'api.driver.reg-step-three', 'uses' => 'Api\DriverController@RegStepThree']);

        Route::post('/reg-step-five', ['as' => 'api.driver.reg-step-five', 'uses' => 'Api\DriverController@RegStepFive']);

        Route::post('/bank-details/save', ['as' => 'driver.bankdetails', 'uses' => 'Api\DriverController@BankDetailsSave']);
        // driver login
        Route::post('/on-board', ['as' => 'api.driver-login', 'uses' => 'Api\DriverController@Login']);

        // driver login
        Route::post('/direction-data', ['as' => 'api.driver-login', 'uses' => 'Api\CommonController@googleDirectionData']);

        Route::post('/get-stripe-connect-required-details', 'Account\DriverController@getStripeConnectRequireDetails');
        Route::post('/register-stripe-connect', 'Account\DriverController@RegisterToStripeConnect');
        Route::post('/check-stripe-connect', 'Account\DriverController@CheckStripeConnect');

        Route::post('/website/homeScreen', 'Api\WebsiteController@DriverHomeScreen');
        Route::post('/check-droplocation/area', ['as' => 'api.droplocation-area', 'uses' => 'Api\HomeController@CheckDropLocation']);
        Route::post('/configuration', ['as' => 'api.driver.configuration', 'uses' => 'Api\DriverController@Configuration']);
        Route::get('/fastlane-configuration', ['as' => 'api.user.configuration', 'uses' => 'Api\DriverController@FastLaneConfiguration']);

        Route::post('/get-document-list', ['as' => 'api.document-list', 'uses' => 'Api\DriverController@getDocumentList']);


        Route::post('/documentlist', ['as' => 'api.driver-documentlist', 'uses' => 'Api\DriverController@DocumentList']);
        Route::post('/vehicledocumentlist', 'Api\DriverController@VehicleDocumentList');
        //        Route::post('/firtstep', ['as' => 'api.driver.signup-firstStep', 'uses' => 'Api\DriverController@BasicInformation']);
        //        Route::post('/login', ['as' => 'api.driver-login', 'uses' => 'Api\DriverController@Login']);
        Route::post('/login/otp', ['as' => 'api.driver-login-otp', 'uses' => 'Api\DriverController@LoginOtp']);
        Route::post('/vehicle-configuration', ['as' => 'api.driver-vehicle-config', 'uses' => 'Api\DriverVehicleController@vehicleConfiguration']);
        Route::post('/vehicle-model', ['as' => 'api.driver-vehicle-model', 'uses' => 'Api\DriverVehicleController@getVehicleModel']);
        Route::post('/add-vehicle', ['as' => 'api.driver-add-vehicle', 'uses' => 'Api\DriverVehicleController@addVehicle']);
        Route::post('/vehicle/otp', ['as' => 'api.driver-addvehicle.otp', 'uses' => 'Api\DriverVehicleController@VehicleOtpVerifiy']);
        Route::post('/vehicle-request', ['as' => 'api.request', 'uses' => 'Api\DriverVehicleController@vehicleRequest']);
        //        Route::post('/add-requested-vehicle', ['as' => 'api.add-requested-vehicle', 'uses' => 'Api\DriverVehicleController@addRequestedVehicle']);
        Route::post('/add-document', ['as' => 'api.driver-add-document', 'uses' => 'Api\DriverController@addDocument']); // add personal document
        //        Route::post('/addvehicledocument', ['as' => 'api.driver-addvehicledocument', 'uses' => 'Api\DriverController@AddVehicleDocument']); // add vehicle document of driver

        Route::group(['middleware' => ['limit_api']], function () {
            Route::post('/otp', ['as' => 'api.driver-otp', 'uses' => 'Api\DriverController@Otp']);
        });

        Route::post('/forgotpassword', ['as' => 'api.driver.password', 'uses' => 'Api\DriverController@ForgotPassword']);
        Route::post('/cms/pages', 'Api\CommonController@DriverCmsPage');
        //        Route::post('/demo', ['as' => 'driver.api.demoUser', 'uses' => 'Api\DriverController@Demo']);
        Route::post('/edit-profile', 'Api\DriverController@editProfile');
        Route::post('/details', 'Api\DriverController@DriverDetails');
        Route::post('/account-types', ['as' => 'driver.api.account-types', 'uses' => 'Api\DriverController@AccountTypes']);
        Route::post('/getnetworkcode', 'Api\CommonController@getNetworkCode');
        Route::post('/korbapayment', 'PaymentMethods\RandomPaymentController@korbaWeb')->name('korbapayment');
        Route::post('/driver-all-document', 'Api\DriverController@driverDocument');
        Route::post('/ride-payment-status', 'Api\BookingController@driverRidePaymentStatus');


        // multi-service
        Route::post('/service-slots', ['as' => 'api.driver.merchant.service-slots', 'uses' => 'Api\DriverController@getServiceTimeSlot']);

        Route::post('/get-segment-gallery', ['as' => 'api.driver.get.segment.gallery', 'uses' => 'Api\DriverController@getDriverGallery']);
        Route::post('/save-segment-gallery', ['as' => 'api.driver.save.segment.gallery', 'uses' => 'Api\DriverController@saveDriverGallery']);
        Route::post('/delete-segment-gallery', ['as' => 'api.driver.delete.segment.gallery', 'uses' => 'Api\DriverController@deleteDriverGallery']);

        Route::post('/demo-onboard', ['as' => 'driver.api.demo.onboard', 'uses' => 'Api\DriverController@demoLogin']);

        // only get data according to driver time zone
        Route::post('/promotion/notification', ['as' => 'api.driver.promotion.notification', 'uses' => 'Api\DriverController@PromotionNotification']);
        Route::post('/wallet/transaction', ['as' => 'api.driver.wallet', 'uses' => 'Api\DriverEarningController@WalletTransaction']);
        Route::post('/getString', ['as' => 'api.getLatestString', 'uses' => 'Api\StringLanguageController@getLatestString']);

        Route::post("/in-app-call", ['as' => 'api.driver.in_app_call', 'uses' => 'Api\CommonController@inAppCalling']);
        Route::post("/driver-additional-details", ['as' => 'api.driver.additional-details', 'uses' => 'Api\DriverController@driverAdditionalDetails']);

    });


    Route::group(['middleware' => ['auth:api-driver', 'timezone']], function () {

         //bons qr bank to bank payment
        Route::post('/get-bons-bank-details', ['as' => 'driver.get-bons-bank-details', 'uses' => 'Api\CommonController@getBonsBankDetails']);
        Route::post('/submit-bons-bank-details', ['as' => 'driver.submit-bons-bank-details', 'uses' => 'Api\CommonController@submitBonsBankDetails']);

        Route::post('/get-paystack-bank-codes', ['as' => 'driver.get-paystack-bank-codes', 'uses' => 'Api\CardController@getPaystackBankCodes']);

        Route::post('/paystack-registration', ['as' => 'driver.paystack-registration', 'uses' => 'Api\CardController@PaystackRegistration']);

        // driver segment time slot
        Route::post('/save-service-time-slot', ['as' => 'api.driver.segment-configuration', 'uses' => 'Api\DriverController@saveServiceTimeSlot']);

        // driver get driver online work configuration
        Route::post('/get-online-work-config', ['as' => 'api.driver.get-online-configuration', 'uses' => 'Api\DriverController@getOnlineConfig']);
        Route::post('/save-online-work-config', ['as' => 'api.driver.save-online-configuration', 'uses' => 'Api\DriverController@saveOnlineConfig']);

        // notification testing api
        //  Route::post('/test-notification', ['as' => 'api.test-noti', 'uses' => 'Api\DriverController@testNotification']);

        // get driver subscription package config
        Route::post('/get-subscriptions-list', ['as' => 'api.driver.get-subscription-package', 'uses' => 'Api\SubscriptionPackageController@getSubscriptionPackageList']);
        //    Route::post('/save-online-work-config', ['as' => 'api.driver.save-online-configuration', 'uses' => 'Api\DriverController@saveOnlineConfig']);


        Route::post('/get-main-screen-config', ['as' => 'api.driver.main-screen-config', 'uses' => 'Api\DriverController@getMainScreenConfig']);

        // get driver segment list with already configured
        Route::post('/get-segment-list', ['as' => 'api.driver.config-segment-list', 'uses' => 'Api\DriverController@getSegmentList']);

        // get driver enrolled/signedup segment list
        Route::post('/get-enrolled-segments', ['as' => 'api.driver.enrolled-segment-list', 'uses' => 'Api\DriverController@getEnrolledSegments']);

        Route::post('/get-segment-services', ['as' => 'api.driver.segment-config', 'uses' => 'Api\DriverController@getSegmentServicesConfig']);
        // driver segment configuration
        Route::post('/save-segment-config', ['as' => 'api.driver.segment-configuration', 'uses' => 'Api\DriverController@saveSegmentConfig']);

        // get vehicle list
        Route::post('/get-vehicle-list', ['as' => 'api.driver-vehicle-list', 'uses' => 'Api\DriverVehicleController@getVehicleList']);

        // order or booking info
        //Route::post('/bookings/detail', ['as' => 'api.driver-bookings-detail', 'uses' => 'Api\BookingController@Detail']);
        Route::post('/booking-order-info', ['as' => 'api.booking-order-information', 'uses' => 'Api\CommonController@bookingOrderInfo']);

        // accept booking-order
        // Route::post('/bookings/accept', ['as' => 'api.driver-bookings-accept', 'uses' => 'Api\BookingController@BookingAccept']);
        //        Route::post('/bookings/reject', ['as' => 'api.driver-bookings-reject', 'uses' => 'Api\BookingController@Reject']);
        Route::post('/booking-order-accept-reject', ['as' => 'api.driver-update-booking-order-status', 'uses' => 'Api\CommonController@bookingOrderAcceptReject']);
        // Route::post('/bookings/arrive', ['as' => 'api.driver-bookings-arrive', 'uses' => 'Api\BookingController@Arrive']);
        Route::post('/arrived-at-pickup', ['as' => 'api.driver-arrived-at-pickup', 'uses' => 'Api\CommonController@arrivedAtPickup']);
        Route::post('/order-in-process', ['as' => 'api.order-in-process', 'uses' => 'Api\CommonController@orderInProcess']);

        // In Drive counter by driver
        Route::post('/booking/in-drive-counter', ['as' => 'api.driver.booking.in-driver-count', 'uses' => 'Api\BookingController@bookingCounterOffer']);


        //Route::post('/bookings/start', ['as' => 'api.driver-bookings-start', 'uses' => 'Api\BookingController@Start']);
        Route::post('/booking-order-picked', ['as' => 'api.booking-order-on-the-way', 'uses' => 'Api\CommonController@bookingOrderPicked']);

        // in case driver save booking additional info
        Route::post('/booking-save-additional-info', ['as' => 'api.booking-save-additional-info', 'uses' => 'Api\BookingController@saveBookingAdditionalInfo']);
        // in case of booking(taxi + delivery etc)
        Route::post('/booking/end', ['as' => 'api.driver-bookings-end', 'uses' => 'Api\BookingController@endBooking']);
        Route::post('/booking/payment-confirmation', ['as' => 'api.driver-bookings-confirmation', 'uses' => 'Api\BookingController@confirmation']);
        // in case of booking(taxi etc)
        Route::post('/booking/pass', ['as' => 'api.driver-bookings-pass', 'uses' => 'Api\BookingController@passBooking']);
        // in case of booking(taxi etc)
        Route::post('/booking/pass-cancel', ['as' => 'api.driver-bookings-pass-cancel', 'uses' => 'Api\BookingController@passBookingCancel']);
        // in case of order
        Route::post('/deliver-order', ['as' => 'api.order-delivered', 'uses' => 'Api\CommonController@deliverOrder']);
        // Route::post('/bookings/cancel', ['as' => 'api.driver-bookings-cancel', 'uses' => 'Api\BookingController@DriverCancel']);
        Route::post('/cancel-booking-order', ['as' => 'api.cancel-booking-order', 'uses' => 'Api\CommonController@cancelBookingOrder']);

        Route::post('/get-booking-order-payment-info', ['as' => 'api.delivered-order-info', 'uses' => 'Api\CommonController@bookingOrderPaymentInfo']);
        Route::post('/update-booking-order-payment-status', ['as' => 'api.update-payment-status', 'uses' => 'Api\CommonController@updateBookingOrderPaymentStatus']);

        //Route::post('/bookings/close', ['as' => 'api.driver-bookings-close', 'uses' => 'Api\BookingController@BookingClose']);
        Route::post('/complete-booking-order', ['as' => 'api.complete-booking-order', 'uses' => 'Api\CommonController@completeBookingOrder']);

        Route::post('/slider-data', ['as' => 'api.driver-slider-data', 'uses' => 'Api\CommonController@sliderData']);

        // get active booking and orders
        //        Route::post('/booking/history/active', ['as' => 'api.driver-.activebookings', 'uses' => 'Api\BookingHistoryController@DriverActiveBooking']);
        Route::post('/get-active-booking-order', ['as' => 'api.get-active-booking-order', 'uses' => 'Api\CommonController@getActiveBookingOrder']);
        // get past booking and orders
        //        Route::post('/booking/history/past', ['as' => 'api.driver-bookings', 'uses' => 'Api\BookingHistoryController@DriverBookingHistory']);
        Route::post('/get-past-booking-order', ['as' => 'api.get-past-booking-order', 'uses' => 'Api\CommonController@getPastBookingOrder']);


        Route::post('/get-booking-order-details', ['as' => 'api.get-booking-order-details', 'uses' => 'Api\CommonController@getBookingOrderDetails']);
        // update driver location
        Route::post('/location', ['as' => 'api.driver-location', 'uses' => 'Api\DriverController@Location']);


        // tutu changes
        Route::post('/redeem-points-driver', 'Api\CommonController@driverRedeemPoints');
        Route::post('/withdraw-driver-wallet', 'Api\DriverController@withdrawWallet');
        Route::post('/reward-points', 'Api\DriverController@rewardPoints');
        // end

        Route::post('/sos', 'Api\SosController@SosDriver');
        Route::post('/sos/create', 'Api\SosController@addSosDriver');
        Route::post('/sos/distory', 'Api\SosController@deleteDriverSos');

        Route::post('/timeStamp', 'Api\DriverController@CheckTimeStap');
        //        Route::post('/sendMoneyToUser', 'Api\DriverController@sendMoneyToUser');
        //Route::post('/view-subscription-packages', ['as' => 'api.driver-view-subscription-packages', 'uses' => 'Api\SubscriptionPackageController@ViewPackages']);
        Route::post('/get-subscriptions-list', ['as' => 'api.driver-view-subscription-packages', 'uses' => 'Api\SubscriptionPackageController@getSubscriptionPackageList']);
        Route::post('/get-subscriptions-history', ['as' => 'api.driver-view-subscription-history', 'uses' => 'Api\SubscriptionPackageController@getSubscriptionHistory']);
        Route::post('/get-active-subscription', ['as' => 'api.driver-active-subscription', 'uses' => 'Api\SubscriptionPackageController@getActiveSubscription']);
        Route::post('/activate-subscription-package', ['as' => 'api.driver-activate-subscription-packages', 'uses' => 'Api\SubscriptionPackageController@ActivatePackage']);
        Route::post('/activate-renewable-subscription', ['as' => 'api.user-activate-renewable-subscription', 'uses' => 'Api\SubscriptionPackageController@ActivateRenewableSubscription']);
        Route::post('/get-renewable-subscription-history', ['as' => 'api.user-activate-renewable-subscription-history', 'uses' => 'Api\SubscriptionPackageController@getRenewableSubscriptionHistory']);
        Route::post('/driverData', 'Api\DriverController@Driver');
        Route::post('/updateTerms', 'Api\DriverController@DriverTermUpdate');
        Route::post('/booking/otp_verify', ['as' => 'api.driver-bookings-otp-verify', 'uses' => 'Api\BookingController@BookingOtpVerify']);

        Route::post('/auto_accept_mode', ['as' => 'driver.api.auto_accept_mode', 'uses' => 'Api\DriverController@AutoAcceptEnable']);

        // using
        Route::post('/manual-booking/checkout', ['as' => 'driver.api.manual.checkoutBooking', 'uses' => 'Api\ManualDispatchController@checkoutBooking']);
        // using
        Route::post('/manual-booking/confirm', ['as' => 'driver.api.manual.booking', 'uses' => 'Api\ManualDispatchController@confirmBooking']);

        Route::post('/cancel-reasons', ['as' => 'driver.api.cancel-reason', 'uses' => 'Api\CommonController@driverCancelReason']);
        Route::post('/auto-upgrade', ['as' => 'driver.api.auto.request', 'uses' => 'Api\DriverController@AutoUpgradetion']);
        Route::post('/manual-downgrade', ['as' => 'driver.api.downgrade.request', 'uses' => 'Api\DriverController@ManualDowngradation']);
        Route::post('/manual-downgrade/vehicle-type/list', ['as' => 'driver.downgrade.vehicle_type.list', 'uses' => 'Api\DriverController@ManualDowngradeVehicleTypeList']);
        Route::post('/sos/request', ['as' => 'driver.api.sos.request', 'uses' => 'Api\CommonController@DriverSosRequest']);
        Route::post('/bank-details/update', ['as' => 'driver.bankdetails', 'uses' => 'Api\DriverController@BankDetailsUpdate']);
        Route::post('/set-radius', ['as' => 'driver.set-radius-driver', 'uses' => 'Api\DriverController@driverSetRadius']);
        Route::post('/refer', ['as' => 'api.driver-refer', 'uses' => 'Api\DriverController@DriverReferral']);
        //        Route::post('/manual_dispatch_estimate', ['as' => 'api.driver-estimate', 'uses' => 'Api\CommonController@estimate']);

        Route::post('/personal/documentlist', ['as' => 'api.personal-documentlist', 'uses' => 'Api\DriverController@PersonalDocumentList']);

        // driver home/additional address api
        Route::post('/add-address', ['as' => 'api.add-driver-address', 'uses' => 'Api\DriverController@addAddress']);
        Route::post('/get-address', ['as' => 'api.get-driver-address', 'uses' => 'Api\DriverController@getAddress']);

        Route::post('/home-address-status', ['as' => 'api.driver-homeaddress.status', 'uses' => 'Api\DriverController@homeAddressStatus']);
        Route::post('/select/homelocation', ['as' => 'api.driver-homelocation.select', 'uses' => 'Api\DriverController@SelectAddress']);
        Route::post('/delete/homelocation', ['as' => 'api.driver-delete.status', 'uses' => 'Api\DriverController@DeleteHomeLocation']);
        Route::post('/demand-spot', ['as' => 'api.driver-demand-spot', 'uses' => 'Api\DriverController@heatMap']);
        Route::post('/receipt', ['as' => 'api.driver.receipt', 'uses' => 'Api\BookingController@driverReceipt']);
        //Route::post('/vehicles', ['as' => 'api.driver-vehicles', 'uses' => 'Api\DriverVehicleController@VehicleList']);

        Route::post('/out-board', ['as' => 'api.driver-logout', 'uses' => 'Api\DriverController@Logout']);

        //        Route::post('/active_vehicle', ['as' => 'api.driver-vehicles', 'uses' => 'Api\DriverVehicleController@ActiveVehicle']);
        Route::post('/changeVehicle', 'Api\DriverVehicleController@ChangeVehicle');
        Route::post('/pool/on-off', ['uses' => 'Api\DriverController@PoolOnOff']);


        Route::post('/getlocationfromLatlong', 'Api\DriverController@CurrentLocation');
        Route::post('/online-offline', ['as' => 'api.driver-location', 'uses' => 'Api\DriverController@OnlineOffline']);
        //        Route::post('/changepassword', ['as' => 'api.driver-location', 'uses' => 'Api\DriverController@ChangePassword']);
        Route::post('/main/screen', ['as' => 'api.driver-main.screen', 'uses' => 'Api\DriverController@MainScreen']);
        Route::post('/booking/change_address', ['as' => 'api.driver.booking.changeaddess', 'uses' => 'Api\BookingController@DriverChangeAddress']);

        Route::post('/accept-upcoming-booking', ['as' => 'api.driver-bookings-partial-accept', 'uses' => 'Api\BookingController@acceptUpcomingBooking']);


        //  reached at multi-drop location
        Route::post('/reached-at-multi-drop', ['as' => 'api.driver-reached-at-multi-drop', 'uses' => 'Api\BookingController@reachedAtMultiDrop']);

        Route::post('/booking/rate/user', ['as' => 'api.user-rate', 'uses' => 'Api\BookingController@DriverRating']);


        Route::post('/booking/pool-details', ['as' => 'api.pool-details', 'uses' => 'Api\BookingController@getPoolDetails']);

        //        Route::post('/bookings/close', ['as' => 'api.driver-bookings-close', 'uses' => 'Api\BookingController@BookingClose']);

        //no need of tracking on driver side
        //Route::post('/booking/tracking', ['as' => 'api.driver.booking.tracking', 'uses' => 'Api\BookingController@DriverTracking']);

        Route::post('/get-schedule-upcoming-booking', ['as' => 'api.driver-bookings-schedule', 'uses' => 'Api\BookingHistoryController@getScheduleUpcomingBooking']);

        //        Route::post('/booking/history/schedule', ['as' => 'api.driver-bookings-schedule', 'uses' => 'Api\BookingHistoryController@DriverScheduleHistory']);
        //        Route::post('/booking/history/upcomming', ['as' => 'api.driver-bookings-upcomming', 'uses' => 'Api\BookingHistoryController@DriverUpcommingHistory']);
        Route::post('/booking/history/upcomming/outstation', ['as' => 'api.driver-bookings-outstation', 'uses' => 'Api\BookingHistoryController@DriverUpcommingOutStationHistory']);

        Route::post('/booking/history/detail', ['as' => 'api.driver-booking-detail', 'uses' => 'Api\BookingHistoryController@DriverBookingDetails']);
        Route::post('/getSuperDrivers', 'Api\DriverController@SuperDrivers');

        //        Route::post('/earnings_revised', ['as' => 'api.driver.earnings_revised', 'uses' => 'Api\DriverEarningController@DriverEarningsCalculation']);
        //        Route::post('/earnings', ['as' => 'api.driver.earnings', 'uses' => 'Api\DriverEarningController@Earning']);
        //        Route::post('/earning/details', ['as' => 'api.driver.earning', 'uses' => 'Api\DriverEarningController@EarningHolder']);
        //        Route::post('/earnings/singleDay', ['as' => 'api.driver.earnings.details', 'uses' => 'Api\DriverEarningController@DailyEarning']);

        Route::post('/chat/send_message', ['as' => 'api.driver.send_message', 'uses' => 'Api\ChatController@DriverSendMessage']);
        Route::post('/chat', ['as' => 'api.driver.chat', 'uses' => 'Api\ChatController@ChatHistory']);
        Route::post('/customer_support', ['as' => 'api.driver.customer_support', 'uses' => 'Api\CommonController@Driver_Customer_Support']);
        Route::post('/AverageRating', 'Api\CommonController@DriverRating');
        Route::post('/paytmchecksumdriver', 'Api\CardController@PaytmChecksum');
        // Not in use
        //        Route::post('/chargePaystackDriver', 'Api\CardController@ChargePaystack');
        ///driver cards
        Route::post('/cards', 'Api\CardController@DriverCards');
        //        Route::post('/savecards', 'Api\CardController@DriverSaveCards');
        Route::post('/save-card', 'Api\CardController@saveDriverCard');
        Route::post('/card/delete', 'Api\CardController@DriverDeleteCard');
        //        Route::post('/makePayment', 'Api\CardController@DriverCardPayment');
        Route::post('/card/make-payment', 'Api\CardController@DriverCardPayment');
        //        Route::post('/wallet/addMoney', ['as' => 'api.driver.addMoney', 'uses' => 'Api\DriverEarningController@AddMoney']);
        //      /// New add walletmoney api
        Route::post('/wallet/add-money', ['as' => 'api.driver.addMoney', 'uses' => 'Api\DriverEarningController@AddMoney']);
        Route::post('/expiredocuments', ['as' => 'driver.expiredocuments', 'uses' => 'Api\ExpireDocumentController@index']);
        Route::post('/IugoPayment', 'Api\CardController@IugoPayment');
        Route::post('/creatPrefId', 'Api\CardController@prefIdMercado');
        Route::post('/createTransDPO', 'PaymentMethods\RandomPaymentController@createTransDPO');
        Route::post('/mobileMoneyDPO', 'PaymentMethods\RandomPaymentController@DpoMobileMoney');
        Route::post('/beyonicMobileMoney', 'PaymentMethods\RandomPaymentController@beyonicMobileMoney');
        Route::post('/peachsavecard', 'PaymentMethods\RandomPaymentController@tokenizePeach');
        Route::post('/paymaya/save-card', 'PaymentMethods\PayMaya\PayMayaController@createToken');
        Route::post('/verifymobileMoneyDPO', 'PaymentMethods\RandomPaymentController@verifyMobileMoneyDPO');
        //        Route::post('/booking/history/upcomming/delivery', ['as' => 'api.driver-bookings-upcomming-delivery', 'uses' => 'Api\BookingHistoryController@DriverUpcommingHistoryDelivery']);

        //cashplus transaction list used only for driver
        Route::post('/cashplus/transaction-list', 'PaymentMethods\CashPlus\CashPlusController@listTransaction');

        //this mpesa api is not in use
        Route::post('mpessaAddmoney', 'PaymentMethods\RandomPaymentController@MpessaAddMoney');
        //this mpesa api is not in use

        //Geofence Queue
        Route::post('/geofence/queue/in-out', 'Api\CommonController@geofenceQueueInOut');
        Route::post('/geofence/in-out', 'Api\CommonController@geofenceInOut');
        Route::post('/geofence/list', 'Api\CommonController@getGeofenceArea');

        //        Route::post('/bookings/pause/resume', ['as' => 'api.driver-bookings-pause-resume', 'uses' => 'Api\BookingController@RidePauseResume']);

        //Razerpay store transaction
        Route::post('/razerpay/transaction', 'PaymentMethods\RandomPaymentController@razerpayTransaction');
        Route::post('/razerpay/logs', 'PaymentMethods\RandomPaymentController@razerpayDriverLog');

        // Senangpay
        Route::post('/senangpay/tokenization', ['as' => 'user.api.senangpay-token', 'uses' => 'Api\CardController@SenangPayToken']);
        Route::post('/senangpay/record/transaction', ['as' => 'user.api.senangpay-record', 'uses' => 'PaymentMethods\RandomPaymentController@SenangPayRecordTransaction']);

        // 2C2P payment gateway
        Route::post('/2c2p/transaction', 'PaymentMethods\RandomPaymentController@TwoCTwoPStoreTransaction');

        Route::group(["prefix" => "paypay"], function () {
            Route::post('/get-payment-methods', ['as' => 'api.paypay.get-payment-methods', 'uses' => 'PaymentMethods\PayPay\PaypayAfricaController@getPaymentMethods']);
        });

        //NetCash WebView
        Route::group(["prefix" => "netcash"], function () {
            Route::post('/payment-form', ['as' => 'api.netcash.payment-form', 'uses' => 'PaymentMethods\NetCash\NetCashPaymentController@generatePaymentForm']);
        });


        
        // Cashout Module
        Route::post('/cashout/request', ['as' => 'merchant.driver.cashout.request', 'uses' => 'Api\DriverCashoutController@request']);
        Route::post('/cashout/history', ['as' => 'merchant.driver.cashout.history', 'uses' => 'Api\DriverCashoutController@index']);

        // New Earning Screen
        Route::post('/account/earnings', ['as' => 'api.driver.account.earnings', 'uses' => 'Api\CommonController@getBookingOrderAccountDetails']);
        //        Route::post('/account/earnings/old', ['as' => 'api.driver.account.earnings', 'uses' => 'Api\DriverEarningController@DriverAccountEarningsOld']);
        //        Route::post('/account/earning/details', ['as' => 'api.driver.account.earning.details', 'uses' => 'Api\DriverEarningController@AccountEarningHolder']);

        Route::post('development/verification', ['as' => 'api.driver.develop.mode.verification', 'uses' => 'Api\DriverController@developModeVerification']);

        // uploaded item loaded images in delivery
        Route::post('upload-loaded-item-image', ['as' => 'api.driver.delivery-loaded-images', 'uses' => 'Api\DeliveryController@saveProductLoadedImages']);

        // check driver document expired/ will expire
        Route::post('check-expired-document', ['as' => 'api.driver.expired-document', 'uses' => 'Api\DriverController@checkDocumentStatus']);


        // upload booking image
        Route::post('upload-booking-image', ['as' => 'api.driver.booking-image', 'uses' => 'Api\HandymanOrderController@saveBookingImage']);
        Route::post('get-booking-image', ['as' => 'api.driver.get-booking-image', 'uses' => 'Api\HandymanOrderController@getBookingImage']);
        Route::post('toll-api', ['as' => 'api.driver.toll-api', 'uses' => 'Helper\Toll@peajeTollApi']);

        //paypal to open paypal web view
        Route::post('/paypal', 'PaymentMethods\RandomPaymentController@PaypalWebViewURL');

        // TriPay Payment
        Route::post('/tripay/payment', 'PaymentMethods\RandomPaymentController@TriPayCreateTransaction');
        Route::post('/tripay/get-payment-channels', 'PaymentMethods\RandomPaymentController@TriPayPaymentChannels');

        // Bookeey
        Route::post('/bookeey/url', 'PaymentMethods\RandomPaymentController@BookeeyURL')->name('BookeeyURL');
        //  paygate
        Route::post('/paygate-webview-url-driver', 'PaymentMethods\Paygate\PaygateController@getWebViewUrlDriver')->name('api.get-paygate-webview-driver');

        // wallet topup via online payment options
        Route::post('/online/make-payment', ['as' => 'api.driver.online.payment', 'uses' => 'PaymentMethods\Payment@onlinePayment']);
        Route::post('/online/payment-status', ['as' => 'api.driver.online.payment.status', 'uses' => 'PaymentMethods\Payment@onlinePaymentStatus']);
        Route::post('/momopay/make-payment', ['as' => 'api.driver.momopay.payment', 'uses' => 'PaymentMethods\RandomPaymentController@MOMOPayRequest']);

        // ProxyPay
        Route::post('/proxy_pay/initiate_transaction', ['as' => 'proxy_pay.initiate_transaction', 'uses' => 'PaymentMethods\ProxyPay\ProxyPayController@createReference']);
        Route::post('/proxy_pay/transaction_status', ['as' => 'proxy_pay.transaction_status', 'uses' => 'PaymentMethods\ProxyPay\ProxyPayController@acknowledgePayment']);

        //Payhere payment gateway
        Route::post('/PayHere/AddCard', ['as' => 'PayHere.AddCardTransaction', 'uses' => 'PaymentMethods\PayHere\PayHereController@AddCardTransaction']);
        //jazzcash Payment Gateway
        Route::post('jazzcash', 'PaymentMethods\RandomPaymentController@JazzCash');
        //mpesaB2C api
        Route::post('/mpesa-lib/b2c/request', 'PaymentMethods\RandomPaymentController@submitB2CRequest');
        Route::post('/changeRideGender', 'Api\DriverController@changeRideGender');
        //QuickPay
        Route::post('/quickPay', 'PaymentMethods\RandomPaymentController@QuickPay');

        Route::post('/kushki-document-list', ['as' => 'driver.kushki-document-list', 'uses' => 'PaymentMethods\Kushki\KushkiController@getKushkiDocumentList']);

        // mercado token setup
        Route::post('/mercado/auth-code', 'PaymentMethods\Mercado\MercadoController@mercadoAuthCodeRequest')->name('mercado.code');

        //hyperPay
        Route::post('/hyperPay/SaveCardCheckout', 'PaymentMethods\HyperPay\HyperPayController@HyperPaySaveCardCheckout');
        Route::post('/hyperPay/PaymentCheckout', 'PaymentMethods\HyperPay\HyperPayController@HyperPayPaymentCheckout');

        //PeachPayment
        Route::post('/peach/SaveCardCheckout', 'PaymentMethods\PeachPayment\PeachPaymentController@PeachSaveCardCheckout');
        Route::post('/peach/PaymentCheckout', 'PaymentMethods\PeachPayment\PeachPaymentController@PeachPaymentCheckout');

        Route::post('/webxpay/make-payment/{check_for}', 'PaymentMethods\Webxpay\Webxpay@makePayment');

        Route::post('/account-delete', 'Api\DriverController@AccountDelete');

        //TelebirrPay
        // Not in use added in Online Make Payment API
        Route::post('/generateTelebirrPayUrl', 'PaymentMethods\TelebirrPay\TelebirrPayController@generateTeliberrUrl');

        //Check Receiver for Wallet Transaction
        Route::post('/check-driver', ['as' => 'api.check-driver', 'uses' => 'Api\DriverController@CheckDriver']);
        Route::post('/transfer-money', ['as' => 'api.transfer-money', 'uses' => 'Api\DriverController@TransferWalletMoney']);

        // Orange Money
        Route::post('/orange', 'PaymentMethods\OrangeMoney\OrangeMoneyController@OrangeMoneyURL');

        //ihelaPay
        Route::post('/get-bank-list', 'PaymentMethods\Ihela\IhelaController@getIhelaBankList');
        Route::post('/customer-account-lookup', 'PaymentMethods\Ihela\IhelaController@IhelaCustomerAccountLookup');
        Route::post('/generate-ihela-url', 'PaymentMethods\Ihela\IhelaController@generateIhelaUrl');
        Route::post('/check-ihela-payment-status', 'PaymentMethods\Ihela\IhelaController@checkPaymentStatus');
        //OrangeMoney Push
        Route::post('/orange-money-push', 'PaymentMethods\RandomPaymentController@OrangeMoneyPush');
        //BillBox
        Route::post('/get-payment-option-list', 'PaymentMethods\BillBox\BillBoxController@getPaymentOptionList');
        Route::post('/create-invoice', 'PaymentMethods\BillBox\BillBoxController@createInvoice');
        Route::post('/process-payment', 'PaymentMethods\BillBox\BillBoxController@processPayment');
        Route::post('/check-payment-status', 'PaymentMethods\BillBox\BillBoxController@checkPaymentStatus');
        Route::post('/interswitch/payment-initiate/{check_for}', 'PaymentMethods\Interswitch\InterswitchController@paymentInitiate');
        Route::post('/interswitch/payment-confirmation/{check_for}', 'PaymentMethods\Interswitch\InterswitchController@paymentConfirmation');
        //EvMak
        Route::post('/send-request', 'PaymentMethods\EvMak\EvMakController@EvMakSendRequest');
        Route::post('/evmak/payout-request', 'PaymentMethods\EvMak\EvMakController@EvMakPayOutRequest'); //send driver arriving reminder to user
        Route::post('/sendReminder', 'Api\BookingController@sendReminderToUser');
        //MIPS
        Route::post('/mips/request', 'PaymentMethods\MIPS\MIPSController@MIPS');

        // get & set strings key value of app via API
        Route::post('/get-key-value-strings', 'Api\CommonController@getAppStrings');
        Route::post('/set-key-value-strings', 'Api\CommonController@setAppStrings');

        // Route::post('/redeem-reward-points', 'Api\DriverController@RedeemRewardPoints');

        Route::post('/get-payment-options', ['as' => 'api.driver.payment-options', 'uses' => 'Api\CommonController@getPaymentOptions']);

        // Credit Account Details
        Route::post('/store-credit-account-details', ['as' => 'api.store-credit-account-details', 'uses' => 'Api\CreditAccountDetailController@storeDetails']);
        Route::post('/get-credit-account-details', ['as' => 'api.get-credit-account-details', 'uses' => 'Api\CreditAccountDetailController@getDetails']);

        // Glomo Money Payment Gateway
        Route::group(["prefix" => "glomo-money"], function () {
            // for credit
            Route::post('/validate-phone', ['as' => 'driver.api.glomo-money.validate-phone', 'uses' => 'PaymentMethods\GlomoMoney\GlomoMoney@checkPhoneNumber']);

            // for Debit payment types
            Route::post('/payment-types', ['as' => 'driver.api.glomo-money.payment-types', 'uses' => 'PaymentMethods\GlomoMoney\GlomoMoney@paymentTypes']);
            Route::post('/make-payment', ['as' => 'driver.api.glomo-money.make-debit-payment', 'uses' => 'PaymentMethods\GlomoMoney\GlomoMoney@makeDebitPayment']);
            Route::post('/check-payment', ['as' => 'driver.api.glomo-money.check-debit-payment', 'uses' => 'PaymentMethods\GlomoMoney\GlomoMoney@checkDebitPayment']);
        });

        // Sahay Payment Gateway
        Route::group(["prefix" => "sahay"], function () {
            Route::post('/check-phone-number', ['as' => 'api.sahay.check-phone-number', 'uses' => 'PaymentMethods\Sahay\SahayGateway@checkPhoneNumber']);
            Route::post('/request-payment', ['as' => 'api.sahay.request-payment', 'uses' => 'PaymentMethods\Sahay\SahayGateway@requestPayment']);
            Route::post('/confirm-payment', ['as' => 'api.sahay.confirm-payment', 'uses' => 'PaymentMethods\Sahay\SahayGateway@confirmPayment']);
        });

        // Ebankily Payment Gateway
        Route::post('/ebankily/make-payment', ['as' => 'api.ebankily.make-payment', 'uses' => 'PaymentMethods\Ebankily\Ebankily@makePayment']);

        // Payriff Payment Gateway
        Route::post('/payriff/create-order', ['as' => 'api.payriff.create-order', 'uses' => 'PaymentMethods\Payriff\Payriff@createOrder']);
        Route::post('/payriff/check-payment', ['as' => 'api.payriff.check-payment', 'uses' => 'PaymentMethods\Payriff\Payriff@checkPayment']);

        Route::post('/payriff/save-card-order', ['as' => 'api.payriff.save-card-order', 'uses' => 'PaymentMethods\Payriff\Payriff@saveCardOrder']);
        // Pagoplux
        Route::post('/pagoplux/save-card-web-view', ['as' => 'api.pagoplux.save.card', 'uses' => 'PaymentMethods\PagoPlux\PagoPluxController@PagoPluxSaveCardCheckout']);

        //Hubtel
        Route::post('/hubtel/create-order', ['as' => 'api.hubtel.create-order', 'uses' => 'PaymentMethods\Hubtel\HubtelController@createOrder']);
        //Wave Payout
        Route::post('/wave-payout', 'PaymentMethods\Wave\WaveController@WavePayout');
        // Sahay Payment Gateway
        Route::group(["prefix" => "sahay"], function () {
            Route::post('/check-phone-number', ['as' => 'api.sahay.check-phone-number', 'uses' => 'PaymentMethods\Sahay\SahayGateway@checkPhoneNumber']);
            Route::post('/request-payment', ['as' => 'api.sahay.request-payment', 'uses' => 'PaymentMethods\Sahay\SahayGateway@requestPayment']);
            Route::post('/confirm-payment', ['as' => 'api.sahay.confirm-payment', 'uses' => 'PaymentMethods\Sahay\SahayGateway@confirmPayment']);
        });

        // Ebankily Payment Gateway
        Route::post('/ebankily/make-payment', ['as' => 'api.ebankily.make-payment', 'uses' => 'PaymentMethods\Ebankily\Ebankily@makePayment']);

        // Yoco Payment Gateway
        Route::post('/yoco-payment', 'PaymentMethods\Yoco\YocoController@makePayment');
        //OrangeMoney Push
        Route::post('/orange-money-payment', 'PaymentMethods\OrangeMoney\OrangeMoneyController@MakeOrangeMoneyPayment');
        Route::post('/orange-money-payout', 'PaymentMethods\OrangeMoney\OrangeMoneyController@OrangeMoneyPayout');
        // OneVision Callback
        Route::post('/one-vision/initiate-payment', ['as' => 'api.driver.one-vision.initiate-payment', 'uses' => 'PaymentMethods\OneVision\OneVisionController@initiatePayment']);
        Route::post('/one-vision/check-payment', ['as' => 'api.driver.one-vision.check-payment', 'uses' => 'PaymentMethods\OneVision\OneVisionController@checkPayment']);

        //AfriMoney Gambia
        Route::post('/afri-money/process-payment', 'PaymentMethods\AfriMoneyGambia\AfriMoneyGambiaController@processPayment');
        Route::post('/afri-money/check-payment-status', 'PaymentMethods\AfriMoneyGambia\AfriMoneyGambiaController@checkTransactionEnquiry');
        // ViuPay
        Route::group(["prefix" => "viu-pay"], function () {
            Route::post('/get-payment-methods', ['as' => 'api.driver.viu.get-payment-methods', 'uses' => 'PaymentMethods\ViuPay\ViuPayController@getPaymentMethods']);
            Route::post('/get-payment-options', ['as' => 'api.driver.viu.get-payment-options', 'uses' => 'PaymentMethods\ViuPay\ViuPayController@getPaymentOption']);
            Route::post('/set-payment-option', ['as' => 'api.driver.viu.save-payment-option', 'uses' => 'PaymentMethods\ViuPay\ViuPayController@setPaymentOption']);
            Route::post('/initiate-payment', ['as' => 'api.driver.viu.initiate-payment', 'uses' => 'PaymentMethods\ViuPay\ViuPayController@initiatePayment']);
        });

        //PawaPay
        Route::group(["prefix" => "pawa-pay"], function () {
            Route::post('/get-payment-options', ['as' => 'api.pawapay.get-payment-options', 'uses' => 'PaymentMethods\PawaPay\PawaPayController@getPaymentCorrespondentOption']);
        });

        //BudPay
        Route::group(["prefix" => "bud-pay"], function () {
            Route::post('/get-service-providers', ['as' => 'api.budpay.service-providers', 'uses' => 'PaymentMethods\BudPay\BudPayController@getServiceProvider']);
        });

        // Binance
        Route::prefix('Binance')->group(function () {
            Route::post('/bank-list', 'PaymentMethods\Binance\BinanceController@bankList')->name('Binance-bankList');
        });

        // Uniwallet Payment Gateway
        Route::post('uniwallet/debit-request', 'PaymentMethods\Uniwallet\UniwalletController@createTransaction');
        Route::post('uniwallet/check-request', 'PaymentMethods\Uniwallet\UniwalletController@checkTransaction');

        Route::group(["prefix" => "tap"], function () {
            Route::post('/generate-save-card-url', 'PaymentMethods\Tap\TapController@generateSaveCardUrl');
            Route::post('/generate-payment-url', 'PaymentMethods\Tap\TapController@generatePaymentUrl');
        });

        //Reward System
        Route::post('/reward-gift-list', 'Api\RewardGiftController@getRewardGiftList');
        Route::post('/redeem-reward-gift', 'Api\RewardGiftController@RedeemRewardGift');
        Route::post('/check-eligibility', 'Api\RewardGiftController@checkEligibleRewardGift');
        Route::post('/reward-history', 'Api\RewardGiftController@getRewardHistory');
        Route::post('/redeemed-rewards', 'Api\RewardGiftController@getRedeemedGifts');

        //azampay
        Route::group(["prefix" => "azampay"], function () {
            Route::post('/send-payment-request', 'PaymentMethods\AzamPay\AzamPayController@MakeAzamPayPayment');
            Route::post('/providers-list', 'PaymentMethods\AzamPay\AzamPayController@AzamPayProviders');
        });

        //tranzak payment
        Route::group(["prefix" => "tranzak"], function () {
            Route::post('/send-payment-request', 'PaymentMethods\Tranzak\TranzakController@MakeTranzakPayment');
        });

        //airtel payment
        Route::group(["prefix" => "airtel"], function () {
            Route::post('/payment', 'PaymentMethods\Airtel\AirtelPaymentController@MakeAirtelPayment');
            Route::post('/check-payment-status', 'PaymentMethods\Airtel\AirtelPaymentController@PaymentStatus');
        });
        
        //S3P payment
        Route::group(["prefix" => "s3p"], function () {
            Route::post('/cashout-services', 'PaymentMethods\S3P_Pay\S3PPayController@CashoutServices');
            Route::post('/make-payment', 'PaymentMethods\S3P_Pay\S3PPayController@MakePayment');
            Route::post('/payment-verify', 'PaymentMethods\S3P_Pay\S3PPayController@PaymentVerify');
        });

       


        //vendor payaw payment
        Route::group(["prefix" => "payaw"], function () {
            Route::any('/send-payment-request', 'PaymentMethods\Payaw\PayawPaymentController@MakePayawPayment');
            Route::any('/check-payment-status', 'PaymentMethods\Payaw\PayawPaymentController@PaymentStatus');
        });

        //cx-pay payment gateway
        Route::post('cx-pay/step-one', 'PaymentMethods\CxPay\CxPayController@processStepOne')->name('cx-pay.step-one.get');

        //check status for pending transaction in app side
        Route::post('/check-transaction-status', 'PaymentMethods\RandomPaymentController@CheckPaymentTransactionStatus');
        Route::post('/cancel-transaction', 'PaymentMethods\RandomPaymentController@CancelTransaction');
       

        Route::post('/moov-money/send-verify-otp', 'PaymentMethods\MoovMoney\MoovMoneyController@SendMoovMoneyOTP');

        Route::post('/serfinsa/make-payment', 'PaymentMethods\Serfinsa\SerfinsaController@serfinsaMakePayment');
        //smartPay
        Route::post('/smart-pay/check-user-driver', 'PaymentMethods\SmartPay\SmartPayController@checkUser');
        Route::post('/smart-pay/register', 'PaymentMethods\SmartPay\SmartPayController@registerOnSmartPay');
        Route::post('/smart-pay/make-payment', 'PaymentMethods\SmartPay\SmartPayController@processSmartPayPayment');

        Route::group(["prefix" => "mpesa"], function () {
            Route::post('/submit-request', 'PaymentMethods\Mpesa\MpesaController@mpesaExpress');
            Route::post('/fetch-transaction-status', 'PaymentMethods\Mpesa\MpesaController@mpesaTransactionStatus');

            Route::post('/b2c/request', 'PaymentMethods\Mpesa\MpesaController@submitB2CRequest');
        });

        Route::post('/stripe/create-intent', 'PaymentMethods\Payment@getStripeIntentSecret');
        Route::post("/available-bookings", ['as' => 'api.driver.available.bookings', 'uses' => 'Api\DriverController@availableBooking']);
        Route::post("/delivery-available-bookings", ['as' => 'api.driver.delivery.available.bookings', 'uses' => 'Api\DriverController@deliveryAvailableBooking']);
        Route::post('/wallet-recharge-request', 'Api\CommonController@walletRechageRequest');
        Route::post("/sos-request", ['as' => 'api.user.sos', 'uses' => 'Api\CommonController@sos']);

        
    });
});


Route::post('/webxpay/return-callback', 'PaymentMethods\Webxpay\Webxpay@returnCallBack');

Route::get('/interswitch/web-payment/{transaction_id}', 'PaymentMethods\Interswitch\InterswitchController@webPayment')->name('interswitch-web-payment');

Route::get('/test-merchant-notification', ['as' => 'test.notify.merchant', 'uses' => 'Api\CommonController@testMerchantNotification']);


Route::get('/test-email', function () {
    // $booking = App\Models\Booking::where("id", 63899)->first();
    $template = new \App\Http\Controllers\Merchant\emailTemplateController();
    $order = \App\Models\HandymanOrder::find(50090);
    dd($order);
    $template->SendUserHandymanInvoiceMail(137629);
    dd('test');
});


Route::any('multi-service-status', function () {

    return [
        "version" => "58.10",
        "result" => "0",
        "message" => "Data fetched successfully",
        "data" => [],
    ];
})->name('multi-service-status');

Route::any('multi-service-driver-status', function () {

    return [
        "version" => "58.10",
        "result" => "0",
        "message" => "Data fetched successfully",
        "data" => [],
    ];
})->name('multi-service-status');


Route::get('remita/make-payment/{key}/{rrr}', 'PaymentMethods\RemitaPay\RemitaPayController@PaymentUrl')->name('remita.payment-url');
Route::get('remita/success/{rrr}', 'PaymentMethods\RemitaPay\RemitaPayController@Success')->name('remita.success');
Route::get('remita/fail/{rrr}', 'PaymentMethods\RemitaPay\RemitaPayController@Fail')->name('remita.fail');