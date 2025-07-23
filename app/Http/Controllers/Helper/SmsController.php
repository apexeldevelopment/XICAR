<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\SmsGateways\SimpleSms;
use App\Models\EmailConfig;
use App\Models\SmsConfiguration;
use App\Http\Controllers\Controller;
use AfricasTalking\SDK\AfricasTalking;
use App\Models\User;
use Twilio\Rest\Client;
use App\Traits\MailTrait;
use App\Traits\MerchantTrait;

use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\ChatGrant;

class SmsController extends Controller
{
    use MailTrait, MerchantTrait;

    public function SendSms($merchant_id, $phone, $otp, $event = null, $email = "", $debugging = false, $sms_gateway_config_id = null, $custom_message = NULL)
    {
        //SendSms($merchant_id, $phone, 1, 'RIDE_BOOK');
        $string_file = $this->getStringFile($merchant_id);
        if($debugging){
            $message = $otp; // In case of debugging send the message as it is.
        }else{
            $message = trans("$string_file.otp_for_verification") . " " . $otp;
        }
        if(!empty($sms_gateway_config_id)){
            $SmsConfiguration = SmsConfiguration::find($sms_gateway_config_id);
        }else{
            $SmsConfiguration = SmsConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
        }
        switch ($event) {
            case 'USER_OTP':
                $message = trans("$string_file.otp_for_verification") . " " . $otp;
                break;
            case 'DRIVER_OTP':
                $message = trans("$string_file.otp_for_verification") . " " . $otp;
                break;
            case 'RIDE_START':
                $message = $SmsConfiguration->ride_start_msg;
                break;
            case 'RIDE_END':
                $message = $SmsConfiguration->ride_end_msg;
                break;
            case 'RIDE_ACCEPT':
                $message = $SmsConfiguration->ride_accept_msg;
                break;
            case 'RIDE_BOOK':
                $message = $SmsConfiguration->ride_book_msg;
                break;
            case 'PUSH_MSG':
                $message = $otp;
                break;
            case 'USER_LOGIN_OTP':
                $message = trans("$string_file.otp_for_verification") . " " . $otp;
                break;
            case 'USER_SIGN_OTP':
                $message = trans("$string_file.otp_for_verification") . " " . $otp;
                break;
            case 'USER_FORGOT_PASSWORD_OTP':
                $message = trans("$string_file.otp_for_verification") . " " . $otp;
                break;
            case 'DRIVER_LOGIN_OTP':
                $message = trans("$string_file.otp_for_verification") . " " . $otp;
                break;
            case 'DRIVER_FORGOT_PASSWORD':
                $message = trans("$string_file.otp_for_verification") . " " . $otp;
                break;
            case 'DRIVER_SIGNUP':
                $message = trans("$string_file.otp_for_verification") . " " . $otp;
                break;
            case 'CUSTOM':
                $message = $custom_message;
                break;
        }

        if (!empty($email)) {
            $configuration = EmailConfig::where('merchant_id', '=', $merchant_id)->first();
            $this->sendMail($configuration, $email, $message, 'otp', '', '', '', $string_file);
        }

        $response = "No response found";
        if (!empty($phone) && !empty($SmsConfiguration->sms_provider)) {
            $merchantCountry = [];
            $countryAddInfo =[];
            $matchedPhoneCodeCountry = [];
            if(isset($SmsConfiguration->merchant_countries) && count(json_decode($SmsConfiguration->merchant_countries,true)) > 0 && count(explode(',',$SmsConfiguration->additional_info_for_merchant_country)) > 0 && count(json_decode($SmsConfiguration->merchant_countries,true)) == count(explode(',',$SmsConfiguration->additional_info_for_merchant_country))){
                $matchedPhoneCodeCountry = $SmsConfiguration->Merchant->Country->whereIn('id',json_decode($SmsConfiguration->merchant_countries,true));
                $merchantCountry =  json_decode($SmsConfiguration->merchant_countries,true);
                $countryAddInfo = explode(',',$SmsConfiguration->additional_info_for_merchant_country);
            }
            switch ($SmsConfiguration->sms_provider) {
                case "KUTILITY":
                    $sendKutilty = new SimpleSms();
                    $response = $sendKutilty->kutility($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->sender);
                    break;

                case "TWILLIO":
                    $accountSid = $SmsConfiguration->api_key;
                    $authToken = $SmsConfiguration->auth_token;
                    $client = new Client($accountSid, $authToken);
                    try {
                        $response = $client->messages->create(
                            $phone,
                            array(
                                'from' => $SmsConfiguration->sender_number,
                                'body' => $message
                            )
                        );
                    } catch (\Exception $e) {
                        $response = $e->getMessage();
                        // echo "Error: " . $e->getMessage();
                    }
                    break;

                case "AFRICATALKING":
                    $username = $SmsConfiguration->api_key;
                    $authToken = $SmsConfiguration->auth_token;
                    try {
                        $AT = new AfricasTalking($username, $authToken);
                        $sms = $AT->sms();
                        $response = $sms->send(array(
                            "to" => $phone,
                            "from" => $SmsConfiguration->sender,
                            "message" => $message,
                        ));
//                        return $response;
                    } catch (\Exception $e) {
                        $response = $e->getMessage();
                    }
                    break;

                case "MobiReach":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MobiReach($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;

                case "SENANGPAY":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Senagpay($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token);
                    break;

                case "ONEWAYSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Onewaysms($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;

                case "KNOWLARITY":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Knowlarity($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;

                case "ROUTESMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->RouteSms($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;

                case "JAVNA":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->JavnaSms($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token);
                    break;

                case "EASYSENDSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Easysendsms($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "ROBISEARCH":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Robisearch($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "EXOTEL":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Exotel($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "TEXTLOCAL":
                    $sendsms = new SimpleSms();
                    $phone = array($phone);
                    $response = $sendsms->TextLocal($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->sender);
                    break;
                case "CLICKATELL":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->clickatell($phone, $message, $SmsConfiguration->api_key);
                    break;
                case "NEXMO":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Nexmo($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->api_secret_key);
                    break;
                case "EASYSERVICE":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Easyservice($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->sender, $SmsConfiguration->api_secret_key);
                    break;
                case "NRSGATEWAY":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->NrsGateway($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "WIREPICK":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->wirepick($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "WAUSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->WauSms($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "SENDPULSE":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Sendpulse($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "SMSCOUNTRY":
                    //$url = ("http://api.smscountry.com/SMSCwebservice_bulk.aspx?User=$SmsConfiguration->api_key&passwd=$SmsConfiguration->auth_token&mobilenumber=$phone&message=$message&sid=$SmsConfiguration->sender&mtype=N&DR=Y");
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SmsCountry($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "CELLSYNT":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Cellsynt($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;

                case "EBULKSMS":
                    try {
                        $data = array(
                            "to" => $phone,
                            "from" => $SmsConfiguration->sender,
                            "message" => $message,
                            "api_key" => $SmsConfiguration->auth_token,
                            "user_name" => $SmsConfiguration->api_key,
                        );
                        $sendsms = new SimpleSms();
                        $response = $sendsms->EBulkSMS($data);
                    } catch (\Exception $e) {
                        $response = $e->getMessage();
                    }
                    break;
                case "ENGAGE SPARK":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->EngageSpark($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "POSTAGUVERCINI":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->PostaGuvercini($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token);
                    break;
                case "SMARTSMSSOLUTIONS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SmartSmsSolutions($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token);
                    break;
                case "SMSVIRO":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSVIRO($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token);
                    break;
                case "AAKASHSMS":
                    $phone = substr($phone, -10);
                    $sendsms = new SimpleSms();
                    $response = $sendsms->AakashSMS($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token);
                    break;
                case "BULKSMSNIGERIA":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BulkSmsNigeria($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token);
                    break;
                case "BULKSMSZAMTEL":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BulkSmsZamtel($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->sender);
                    break;
                case "SSLWIRELESS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SslWireLess($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->api_secret_key);
                    break;
                case "MYTELESOM":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MYTELESOM($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender);
                    break;
                case "SELCOMSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SELCOMSMS($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->api_secret_key);
                    break;
                case "NSEMFUA":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Nsemfua($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->sender);
                    break;
                case "PLIVO":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Plivo($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "BULKSMSBD":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BulkSmsBD($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token);
                    break;
                case "MULTITEXTER":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MULTITEXTER($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "MSG91":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Msg91($phone, $message, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "OUTREACH":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->OutReach($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender);
                    break;
                case "BUDGETSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BudgetSms($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender, $SmsConfiguration->api_secret_key);
                    break;
                case "CLICKATELLAPI":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->ClickATellApi($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender);
                    break;
                case "DATASOFT":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->DataSoft($SmsConfiguration->api_key, $SmsConfiguration->sender, $SmsConfiguration->auth_token, $phone, $message);
                    break;
                case "SHAMELSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->ShamelSms($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message, $SmsConfiguration->sender);
                    break;
                case "SMSLIVE247":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSLive247($SmsConfiguration->api_key, $SmsConfiguration->subacct, $SmsConfiguration->auth_token, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "INFOBIP":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Infobip($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "TWWWIRELESS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->TWWWireless($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMS123":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Sms123($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "BULKSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BulkSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "TEXTINGHOUSE":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->textingHouse($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message, $SmsConfiguration->sender);
                    break;
                case "MOBILE360":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->mobile360Sms($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "FACILITA_MOVEL":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->facilitaMovel($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "E_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->eSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "INFOBIP_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->INFOBIPSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "iSmart":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->iSmart($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "smsportal":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->smsPortal($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "ArkeselSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->ArkeselSMS($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "BurstSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BurstSMS($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMSBOX":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSBOX($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->account_id, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "WhatsAppTodo":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->whatsAppTodo($phone, $message);
                    break;
                case "mymobileapi":
                    $sendsms = new SimpleSms(); //smsworx
                    $response = $sendsms->myMobileApi($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "SMSCTP":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSCTP($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMSDEV":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSDEV($SmsConfiguration->api_key, $phone, $message);
                    break;
                case "MUTHOFUN":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MuthoFun($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "KINGSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->KingSms($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "GLOBELABS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->GlobeLabs($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender_number, $SmsConfiguration->auth_token, $phone, $message);
                    break;
                case "MULTITEXTERSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MultiTexterSms($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMSPRO_NIKITA":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->smsProNikita($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "MONTYMOBILE":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->montyMobile($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "MESSAGEBIRD":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MessageBird($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "FLOPPYSEND":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->FloppySend($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "RICHCOMMUNICATION":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->RichCommunication($SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMSTO":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSTo($phone, $message, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender);
                    break;
                case "TELESOM":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->telesom(
                        $SmsConfiguration->api_key,
                        $SmsConfiguration->api_secret_key,
                        $SmsConfiguration->sender,
                        $phone,
                        $message,
                        $SmsConfiguration->auth_token
                    );
                    break;
                case "SUDACELLBULKSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->sudacellBulkSms(
                        $SmsConfiguration->api_key,
                        $SmsConfiguration->api_secret_key,
                        $SmsConfiguration->sender,
                        $phone,
                        $message
                    );
                    break;
                case "BULKSMSSERVICES":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BulkSMSServices($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "ORANGESMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->ORANGESMS($phone, $message, $SmsConfiguration->api_key, $SmsConfiguration->sender, $SmsConfiguration->auth_token);
                    break;
                case "CLICKSEND":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->ClickSend($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "SINCH":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Sinch($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMSBUS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SmsBus($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "MESSAGEMEDIA":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MessageMedia($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;

                case "NIGERIABULKSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->NigeriaBulkSms($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "AIRTELBULKSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->AirtelBulkSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "ORANGESMSPRO":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->OrangeSMSPro($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender, $SmsConfiguration->subacct, $phone, $message);
                    break;
                case "SMSZEDEKAA":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SmsZedekaa($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "NOTIFY":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->appNotifyLk($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                    break;
                case "BEEMAFRICA":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BeemAfrica($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender,$phone, $message);
                    break;
                case "MULTITEXTER_V2":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MultiTexterV2($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "LINXSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->LinxSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "BULKSMSDHIRAAGU":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BulkSMSDhiraagu($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "CLOUDWEBSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->CloudWebSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMSPOH":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSPoh($SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "CMTELECOME":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->cmTelecome($SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                case "NSEMFUA_V3":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->NsemfuaV3($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SPARROWSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SparrowSMS($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "AAKASHSMSV3":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->AakashSMSV3($SmsConfiguration->auth_token, $phone, $message);
                    break;
                case "BULKSMSPLANS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BulkSMSPlans($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $SmsConfiguration->sender_number, $phone, $message);
                    break;
                case "WEBLINE":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->WebLine($SmsConfiguration->auth_token, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMSTEKNIK":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSTeknik($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "INTOUCHSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->IntouchSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "NALOSOLUTIONSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->NaloSolutionsSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "GEEZSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->GeezSMS($SmsConfiguration->auth_token, $phone, $message,$SmsConfiguration->sender);
                    break;
                case "TWILIO_WHATSAPP":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->twilioWhatsapp($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "TERMII":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Termii($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "MOBIWEB":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MobiWeb($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMS_ETHIOPIA":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->smsEthiopia($SmsConfiguration->auth_token, $SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "ETECH":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->eTech($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "ADERASMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->aderaSms($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "NEXTSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->NextSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMSONFONMEDIA":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSOnfonMedia($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "ZOOMCONNECT":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->ZoomConnect($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "VONAGE_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->vonageSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "TCAST_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->tCastSMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMSUEHTP":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSUEHTP($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "TELESIGN":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->TeleSign($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "HADARABULKSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->HadaraBulkSMS($SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "M_NOTIFY":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->mNotify($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "WASSA_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->wassaSMS($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMSHUB":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSHub($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "SMSARKESEL":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SmsArkesel($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "LE_TEXTO":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->leTexto($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "SMARTVISION":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SmartVision($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "NITEXT":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->NiText($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "UNIMATRIX":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Unimatrix($SmsConfiguration->api_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "RELEANS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Releans($SmsConfiguration->auth_token, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "M_TARGET":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->mTarget($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $SmsConfiguration->auth_token, $phone, $message);
                    break;
                case "YEGARA":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Yegara($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $phone, $otp);
                    break;
                case "ZYNLE":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Zynle($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $otp);
                    break;
                case "HORMUUD":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Hormuud($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $otp);
                    break;
                case "NATYABIP":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->NatyaBIP($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $otp);
                    break;
                case "ARKESEL":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Arkesel($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "KWIKTALKSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->Kwiktalksms($SmsConfiguration->api_key, $SmsConfiguration->auth_token, $SmsConfiguration->sender, $phone, $message);
                    break;
                case "ET_DELIVERY_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->EtDeliverySMS($SmsConfiguration->api_secret_key,$phone, $message);
                    break;
                case "MSGOWLSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MsgOwlSMS($SmsConfiguration->api_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "SMSMASIVOS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSMASIVOS($SmsConfiguration->api_secret_key,$phone, $message);
                    break;
                case "SMSD7":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSD7($SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "SMSTRANZAK":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSTRANZAK($SmsConfiguration->api_secret_key,$SmsConfiguration->api_key,$phone, $message);
                    break;
                case "MTARGETSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MTARGETSMS($SmsConfiguration->api_secret_key,$SmsConfiguration->api_key,$phone, $message);
                    break;
                case "TERMIISMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->TERMIISMS($SmsConfiguration->api_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "TEXTSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->TEXTSMS($SmsConfiguration->api_key,$SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break; 
                case "ISMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->ISMS($SmsConfiguration->api_key,$SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;      
                case "TELESIGNSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->TELESIGNSMS($SmsConfiguration->api_key,$SmsConfiguration->api_secret_key,$phone, $message);
                    break;   
                case "SMSALERT":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSALERT($SmsConfiguration->api_key,$SmsConfiguration->sender,$phone, $message);
                    break; 
                case "BIRDSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BIRDSMS($SmsConfiguration , $phone, $message);
                    break; 
                case "CHEVNI_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->CHEVNISMS($SmsConfiguration , $phone, $message);
                    break; 
                case "WHATSAPP_GATEWAY":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->WHATSAPP_SMS($SmsConfiguration , $phone, $message);
                    break;
                case "DREAM_DIGTAL":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->DREAM_DIGTAL($SmsConfiguration , $phone, $message);
                    break;
                case "SAMAYA_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SAMAYASMS($SmsConfiguration , $phone, $message);
                    break;
                case "TEXTMAGIC":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->TEXTMAGIC($SmsConfiguration , $phone, $message);
                    break;
                case "FARU":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->FARU($SmsConfiguration , $phone, $message);
                    break;
                case "HUD_HUD_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->HUDHUDSMS($SmsConfiguration->api_key,$SmsConfiguration->api_secret_key, $phone, $message);
                    break;
                case "SPEEDA_MOBILE":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SPEEDAMOBILE($SmsConfiguration->api_key,$SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "MOBIWEB":
                        $sendsms = new SimpleSms();
                        $response = $sendsms->MobiWeb($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key, $SmsConfiguration->sender, $phone, $message);
                        break;
                case "GEEZESMS":             
                        $sendsms = new SimpleSms();
                        $response = $sendsms->GEEZESMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key,$phone, $message);
                        break;
                case "KUDISMS":
                        $sendsms = new SimpleSms();
                        $response = $sendsms->KUDISMS($SmsConfiguration->api_key, $SmsConfiguration->sender,$phone, $message);
                        break;
                case "TWILLIO_MESSAGE_SERVICE":
                        $accountSid = $SmsConfiguration->api_key;
                        $authToken = $SmsConfiguration->auth_token;
                        $client = new Client($accountSid, $authToken);
                        try {
                            $response = $client->messages->create(
                                $phone,
                                array(
                                    'messagingServiceSid' => $SmsConfiguration->sender,
                                    'body' => $message
                                )
                            );
                        } catch (\Exception $e) {
                            $response = $e->getMessage();
                            // echo "Error: " . $e->getMessage();
                        }
                        break; 
                case "DEXATEL":
                        $sendsms = new SimpleSms();
                        $response = $sendsms->DEXATEL($SmsConfiguration->api_key, $SmsConfiguration->sender,$phone, $message);
                        break;
                case "AQILAS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->AQILAS($SmsConfiguration->api_key, $SmsConfiguration->sender,$phone, $message);
                case "TERMII_V3_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->TERMII_V3_LATEST($matchedPhoneCodeCountry,$merchantCountry,$countryAddInfo,$SmsConfiguration->api_key, $SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "WIN_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->WIN_SMS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key,$phone, $message);
                    break;
                case "XWIRELESS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->XWIRELESS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "NEXAH_SMSVAS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMSVAS($SmsConfiguration->api_key, $SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "EASYDIGITALSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->EASYDIGITALSMS($SmsConfiguration->api_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "MTSMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->MTSMS($SmsConfiguration->api_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "AFOMESS_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->AFOMESS_SMS($SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "360nrs_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->SMS360nrs($SmsConfiguration->api_key,$SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "Textlk_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->TextlkSMS($SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "Bulk_Messaging_Gateway":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->BulkMessagingGateway($SmsConfiguration->api_key,$SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;
                case "CLARION_SMS":
                    $sendsms = new SimpleSms();
                    $response = $sendsms->CLARION_SMSGateway($SmsConfiguration->api_secret_key,$SmsConfiguration->sender,$phone, $message);
                    break;
            }
        }
        if($debugging){
            return !empty($response) ? $response : "No response handling";
        }
    }
}
