<?php

namespace App\Http\Controllers\PaymentMethods\Serfinsa;

use App\Http\Controllers\Controller;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SerfinsaController extends Controller
{
    use ApiResponseTrait, MerchantTrait;
    public function getSerfinsaConfig($merchant_id){
        $payment_option = PaymentOption::where('slug', 'SERFINSA')->first();
        $paymentOption = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$payment_option->id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        if (empty($paymentOption)) {
            return $this->failedResponse(trans("$string_file.configuration_not_found"));
        }
        return $paymentOption;
    }

    public function serfinsaMakePayment(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'amount' => 'required',
            'card_number' => 'required',
            'expire_date' => 'required',
            'expire_year' => 'required',
            'cvv' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }

        try{
            $user = $request->type == "USER" ? $request->user('api') : $request->user('api-driver');
            $string_file = $this->getStringFile($user->merchant_id);
            $paymentConfig = $this->getSerfinsaConfig($user->merchant_id);
            $audit_no = date('His');
            $amount = $request->amount*100;
            $amount_len = strlen($amount);
            $required_len = 12;
            $final_amount = '';
            for($i = 0; $i < ($required_len-$amount_len); $i++){
                $final_amount .= '0';
            }
            $final_amount .= $amount;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://pgtest.redserfinsa.com:2027/WebPubTransactor/TransactorWS?WSDL',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="http://webservices.serfinsa.sysdots.com/">
                <soapenv:Header/>
                <soapenv:Body>
                    <web:cardtransaction>
                    <!--Optional:-->
                    <security>{"comid":"'.$paymentConfig->api_public_key.'","comkey":"'.$paymentConfig->api_secret_key.'","comwrkstation":"'.$paymentConfig->auth_token.'"}</security>
                    <!--Optional:-->
                    <txn>MANCOMPRANOR</txn>
                    <!--Optional:-->
                    <message>
                        {"CLIENTE_TRANS_TARJETAMAN":"'.$request->card_number.'",
                            "CLIENTE_TRANS_MONTO":"'.$final_amount.'",
                            "CLIENTE_TRANS_AUDITNO":"'.$audit_no.'",
                            "CLIENTE_TRANS_TARJETAVEN":"'.$request->expire_year.$request->expire_date.'",
                            "CLIENTE_TRANS_MODOENTRA":"012",
                            "CLIENTE_TRANS_TERMINALID":"'.$paymentConfig->operator.'",
                            "CLIENTE_TRANS_RETAILERID":"'.$paymentConfig->tokenization_url.'",
                            "CLIENTE_TRANS_RECIBOID":"'.$audit_no.'",
                            "CLIENTE_TRANS_TOKENCVV":"1611 '.$request->cvv.'"}
                    </message>
                    </web:cardtransaction>
                </soapenv:Body>
            </soapenv:Envelope>',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: text/xml'
                ),
            ));

            $response = curl_exec($curl);
            // echo curl_error($curl);
            // $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $response = json_decode(strip_tags($response),true);
            if(isset($response['cliente_trans_respuesta']) && $response['cliente_trans_respuesta'] == "00"){
                return $this->successResponse("Success", $response);
            }else{
                $message = $this->getStatusText($response['cliente_trans_respuesta']);
                $final_message = !empty($message) ? $message : trans("$string_file.failed").', '.trans("$string_file.some_thing_went_wrong");
                return $this->failedResponse($final_message);
            }
        }catch (\Exception $exception){
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function getStatusText($responseCode){
        $message = '';
        switch ($responseCode){
            case "01":
            case "02":
            case "03":
            case "05":
            case "14":
            case "25":
            case "50":
            case "N7":
            case "O0":
            case "T5":
                $message = "CALL THE ISSUER";
                break;
            case "04":
            case "07":
            case "41":
            case "43":
            case "R8":
                $message = "LOCKED CARD";
                break;
            case "12":
                $message = "INVALID TRANSACTION";
                break;
            case "13":
            case "T1":
                $message = "INVALID AMOUNT";
                break;
            case "15":
            case "91":
            case "92":
                $message = "ISSUER NOT AVAILABLE";
                break;
            case "19":
                $message = "RETRY TRANSACTION";
                break;
            case "30":
                $message = "FORMAT ERROR";
                break;
            case "39":
                $message = "NOT A CREDIT ACCOUNT";
                break;
            case "31":
                $message = "BANK NOT SUPPORTED";
                break;
            case "48":
                $message = "INVALID CREDENTIAL";
                break;
            case "51":
                $message = "INSUFFICIENT FUNDS";
                break;
            case "52":
                $message = "NOT A CHECKING ACCOUNT";
                break;
            case "53":
                $message = "NOT A SAVINGS ACCOUNT";
                break;
            case "54":
                $message = "EXPIRED CARD";
                break;
            case "55":
                $message = "INCORRECT PIN";
                break;
            case "56":
                $message = "CARD NOT VALID";
                break;
            case "57":
            case "58":
                $message = "TRANSACTION NOT ALLOWED";
                break;
            case "59":
                $message = "SUSPECTED FRAUD";
                break;
            case "61":
                $message = "LIMIT ACTIVITY EXCEEDED";
                break;
            case "62":
                $message = "RESTRICTED CARD";
                break;
            case "65":
            case "N6":
                $message = "MAXIMUM ALLOWED REACHED";
                break;
            case "75":
                $message = "PIN ATTEMPTS EXCEEDED";
                break;
            case "82":
                $message = "NO HSM";
                break;
            case "83":
            case "84":
                $message = "ACCOUNT DOES NOT EXIST";
                break;
            case "85":
                $message = "RECORD NOT FOUND";
                break;
            case "86":
                $message = "AUTHORIZATION NOT VALID";
                break;
            case "87":
                $message = "CVV2 INVALID";
                break;
            case "88":
                $message = "TRANSACTION LOG ERROR";
                break;
            case "89":
                $message = "INVALID SERVICE PATH";
                break;
            case "93":
                $message = "TRANSACTION CANNOT BE PROCESSED";
                break;
            case "94":
                $message = "DUPLICATE TRANSACTION";
                break;
            case "96":
                $message = "SYSTEM NOT AVAILABLE";
                break;
            case "97":
                $message = "INVALID SECURITY TOKEN";
                break;
            case "D0":
                $message = "SISTEMA NO DISPONIBLE";
                break;
            case "D1":
                $message = "INVALIDITY TRADE";
                break;
            case "H0":
                $message = "FOLIO ALREADY EXISTS";
                break;
            case "H1":
                $message = "CHECK IN EXISTENTE";
                break;
            case "H2":
                $message = "RESERVATION SERVICE NOT ALLOWED";
                break;
            case "H3":
                $message = "RESERVATION NOT FOUND IN THE SYSTEM";
                break;
            case "H4":
                $message = "CARD NOT FOUND CHECK IN";
                break;
            case "H5":
                $message = "EXCEED CHECK IN OVERDRAFT";
                break;
            case "N0":
                $message = "AUTHORIZATION DISABLED";
                break;
            case "N1":
                $message = "INVALID CARD";
                break;
            case "N2":
                $message = "FULL PRE-AUTHORISATIONS";
                break;
            case "N3":
            case "N4":
                $message = "MAXIMUM AMOUNT REACHED";
                break;
            case "N5":
                $message = "MAXIMUM RETURNS ACHIEVED";
                break;
            case "N8":
                $message = "OVERDRAWN ACCOUNT";
                break;
            case "N9":
                $message = "ALLOWED ATTEMPTS REACHED";
                break;
            case "O1":
                $message = "NEG FILE PROBLEM";
                break;
            case "O2":
                $message = "WITHDRAWAL AMOUNT NOT ALLOWED";
                break;
            case "O3":
                $message = "DELINQUENT";
                break;
            case "O4":
                $message = "LIMIT EXCEEDED";
                break;
            case "O5":
                $message = "PIN REQUIRED";
                break;
            case "O6":
                $message = "INVALID CHECKER DIGIT";
                break;
            case "O7":
                $message = "FORCE POST";
                break;
            case "O8":
                $message = "NO ACCOUNT";
                break;
            case "T2":
                $message = "INVALID TRANSACTION DATE";
                break;
        }
        return $message;
    }
}
