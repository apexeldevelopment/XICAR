<?php

namespace App\Http\Controllers\PaymentMethods\Pagadito;

require_once 'lib/Pagadito.php';

use App\Http\Controllers\Controller;
use hisorange\BrowserDetect\Exceptions\Exception;
use Illuminate\Http\Request;
use DB;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use App\Traits\ContentTrait;
use App\Models\PaymentOptionsConfiguration;
use App\Models\User;
use App\Models\Driver;

use Pagadito;

class PagaditoController extends Controller
{
  use ApiResponseTrait, MerchantTrait, ContentTrait;

  public function __construct()
  {

    $this->apipg                    = "https://comercios.pagadito.com/apipg/charges.php";
    $this->apipg_sandbox            = "https://sandbox.pagadito.com/comercios/apipg/charges.php";
    //Cambie $this->format_return para definir el formato de respuesta que desee utilizar: json, php o xml
    $this->format_return            = "json";
    $this->sandbox_mode             = false;
  }

  public function paymentRequest(Request $request, $payment_option_config, $calling_from)
  {
    try {
      $url = $this->apipg;
      $uid = '4f4edae497b959823ff510c7b4378ad5';
      $wsk = "588a91ee3dc7c3aa3015e62ecde943dc";
      if ($payment_option_config->gateway_condition == 1) {
        $url = $this->apipg_sandbox;
        $uid = $payment_option_config->auth_token;
        $wsk = $payment_option_config->api_secret_key;
      }

      // check whether request is from driver or user
      if ($calling_from == "DRIVER") {
        $driver = $request->user('api-driver');
        $id = $driver->id;
        $merchant_id = $driver->merchant_id;
      } else {
        $user = $request->user('api');
        $id = $user->id;
        $merchant_id = $user->merchant_id;
      }
      $transaction_id = $id . '_' . time();

      \Log::channel('pagadito_api')->emergency(['transaction_id' => $transaction_id]);

      DB::beginTransaction();
      // insert data in transaction table
      DB::table('transactions')->insert([
        'status' => 1, // for user
        'reference_id' => "",
        'card_id' => NULL,
        'user_id' => $calling_from == "USER" ? $id : NULL,
        'driver_id' => $calling_from == "DRIVER" ? $id : NULL,
        'merchant_id' => $merchant_id,
        'payment_option_id' => $payment_option_config->payment_option_id,
        'checkout_id' => NULL,
        'booking_id' => isset($additional_data['booking_id']) ? $additional_data['booking_id'] : NULL,
        'order_id' => isset($additional_data['order_id']) ? $additional_data['order_id'] : NULL,
        'handyman_order_id' => isset($additional_data['handyman_order_id']) ? $additional_data['handyman_order_id'] : NULL,
        'payment_transaction_id' => $transaction_id,
        'payment_transaction' => NULL,
        'request_status' => null,
        'amount' => $request->amount,

      ]);

      DB::commit();
    } catch (\Exception $e) {
      DB::rollBack();
      throw new \Exception($e->getMessage());
    }

    return [
      'transaction_id' => $transaction_id,

      'status' => 'NEED_TO_OPEN_WEBVIEW',
      'url' => route('pagadito-payment-request', ['trans_id' => $transaction_id]),
      'failed_url' => route('pagadito-failed'),
      'success_url' => route('pagadito-success'),
    ];
  }


  public function pagaditoPayment(Request $request)
  {
    try {

      //   dd('in');
      \Log::channel('pagadito_api')->emergency($request->all());
      $payment  = DB::table('transactions')->where('payment_transaction_id', $request->trans_id)->first();
      $paymentOption = PaymentOptionsConfiguration::where('payment_option_id', $payment->payment_option_id)->latest()->first();

      if ($payment->user_id) {
        $user = User::select('id', 'country_id')->find($payment->user_id);
      } else {
        $user = Driver::select('id', 'country_id')->find($payment->driver_id);
      }
      $currency = $user->Country->isoCode;

      // if (!empty($paymentOption)) {

      $sandbox = $paymentOption->gateway_condition == 1 ? false : true;
      //   p($payment->amount);
      // $Pagadito = new Pagadito(UID, WSK);
      $Pagadito = new Pagadito($paymentOption->api_secret_key, $paymentOption->auth_token, $currency);
      if ($sandbox) {
        $Pagadito->mode_sandbox_on();
      }
      // dd('in');
      // dd($Pagadito->connect());
      if ($Pagadito->connect()) {
        // dd('dd');
        // $Pagadito->change_currency_gtq();
        if ($payment->amount > 0) {
          // $Pagadito->add_detail(1, 'GuaTaxi Payment', $request->price);
          $Pagadito->add_detail(1, 'Taxi Payment', $payment->amount);
        }

        DB::table('transactions')->where('payment_transaction_id', $request->trans_id)->update(['reference_id' => $Pagadito->get_rs_value(), 'request_status' => 1]);
        $Pagadito->enable_pending_payments();
        $ern = rand(1000, 2000);

        if (!$Pagadito->exec_trans($ern)) {
          switch ($Pagadito->get_rs_code()) {
            case "PG2001":
              /*Incomplete data*/
              $request_status = 1;
            case "PG3002":
              /*Error*/
              $request_status = 3;
            case "PG3003":
              /*Unregistered transaction*/
            case "PG3004":
              /*Match error*/
              $request_status = 3;
            case "PG3005":
              /*Disabled connection*/
              $request_status = 3;
            default:
              $request_status = 3;
              break;
          }
        } else {
          switch ($Pagadito->get_rs_code()) {
            case "PG2001":
              /*Incomplete data*/
              $request_status = 1;
            case "PG3002":
              /*Error*/
              $request_status = 3;
            case "PG3003":
              /*Unregistered transaction*/
            case "PG3004":
              /*Match error*/
              $request_status = 3;
            case "PG3005":
              /*Disabled connection*/
              $request_status = 3;
            default:
              $request_status = 3;
              break;
          }
        }
      } else {
        // $msgPrincipal = "Atenci&oacute;n";
        // $msgSecundario = "No ha llenado los campos adecuadamente";
        // return response()->json(['result' => 0, 'message' => $msgPrincipal, 'data' => $msgSecundario]);
        $request_status = 3;
      }

      DB::table('transactions')->where('payment_transaction_id', $request->trans_id)->update(['reference_id' => $Pagadito->get_rs_value(), 'request_status' => $request_status]);
      if ($request_status == 3) {
        return redirect()->route('pagadito-failed');
      }
    } catch (\Exception $e) {
      dd($e->getMessage());
    }
  }

  public function PagaditoPayback(Request $request)
  {

    \Log::channel('pagadito_api')->emergency($request->all());
    $payment  = DB::table('transactions')->where('reference_id', $request->value)->first();


    if ($payment->user_id) {
      $user = User::select('id', 'country_id')->find($payment->user_id);
    } else {
      $user = Driver::select('id', 'country_id')->find($payment->driver_id);
    }
    $currency = $user->Country->isoCode;

    $paymentOption = PaymentOptionsConfiguration::where('payment_option_id', $payment->payment_option_id)->latest()->first();
    $sandbox = $paymentOption->gateway_condition == 1 ? false : true;
    $Pagadito = new Pagadito($paymentOption->api_secret_key, $paymentOption->auth_token, $currency);
    if ($sandbox) {
      $Pagadito->mode_sandbox_on();
    }

    $request_status = 3;

    if ($Pagadito->connect()) {
      // echo 'hi';
      // echo $Pagadito->get_rs_code(); die;
      // dd($Pagadito->get_status($request->value));
      // dd($Pagadito->get_rs_status());
      // dd($Pagadito->get_status($request->value));
      if ($Pagadito->get_status($request->value)) {
        //  echo $Pagadito->get_rs_status();
        //  die;
        switch ($Pagadito->get_rs_status()) {
          case "COMPLETED":
            $request_status = 2;

            $msgPrincipal = "Su compra fue exitosa";
            $msgSecundario = 'Gracias por comprar con Pagadito. NAP(N&uacute;mero de Aprobaci&oacute;n Pagadito): ' . $Pagadito->get_rs_reference();
            break;

          case "REGISTERED":
            $msgPrincipal = "Atenci&oacute;n";
            $msgSecundario = "La transacci&oacute;n fue cancelada";
            break;

          case "VERIFYING":
            $msgPrincipal = "Atenci&oacute;n";
            $msgSecundario = 'Su pago est&aacute; en validaci&oacute;n. NAP(N&uacute;mero de Aprobaci&oacute;n Pagadito): ' . $Pagadito->get_rs_reference();
            break;

          case "REVOKED":
            $msgPrincipal = "Atenci&oacute;n";
            $msgSecundario = "La transacci&oacute;n fue denegada";
            break;

          case "FAILED":
            $msgPrincipal = "Atenci&oacute;n";
            $msgSecundario = "Tratamiento para una transacci贸n fallida.";
            break;
          default:
            $msgPrincipal = "Atenci&oacute;n";
            $msgSecundario = "La transacci&oacute;n no fue realizada.";
            break;
        }
      } else {
        switch ($Pagadito->get_rs_code()) {
          case "PG2001":
            /*Incomplete data*/
          case "PG3002":
            /*Error*/
          case "PG3003":
            /*Unregistered transaction*/
          default:
            $msgPrincipal = "Error en la transacci&oacute;n";
            $msgSecundario = "La transacci&oacute;n no fue completada.";
            break;
        }
      }
    } else {
      switch ($Pagadito->get_rs_code()) {
        case "PG2001":
          /*Incomplete data*/
        case "PG3001":
          /*Problem connection*/
        case "PG3002":
          /*Error*/
        case "PG3003":
          /*Unregistered transaction*/
        case "PG3005":
          /*Disabled connection*/
        case "PG3006":
          /*Exceeded*/
        default:
          $msgPrincipal = "Respuesta de Pagadito API";
          $msgSecundario = "COD: " . $Pagadito->get_rs_code() . ", MSG: " . $Pagadito->get_rs_message();
          break;
      }
    }

    // echo $request_status; die;
    DB::table('transactions')->where('reference_id', $request->value)->update(['request_status' => $request_status]);

    if ($request_status == 3) {
      return redirect()->route('pagadito-failed');
    } else {
      return redirect()->route('pagadito-success');
    }
  }

  public function PagaditoFailed(Request $request)
  {
    echo "Payment request has been failed, please try again";
  }
  public function PagaditoSuccess(Request $request)
  {
    echo "Payment request has been successfully completed";
  }

  public function paymentStatus(Request $request)
  {
    $tx_reference = $request->transaction_id; // order id

    $transaction =  DB::table('transactions')->where('payment_transaction_id', $tx_reference)->first();

    $payment_status = false;
    $request_status_text = "failed";
    if ($transaction->request_status == 2) {
      $payment_status = true;
      $request_status_text = "success";
    }
    return ['payment_status' => $payment_status, 'request_status' => $request_status_text];
  }
}
