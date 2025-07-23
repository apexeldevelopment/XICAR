<?php

namespace App\Http\Controllers\SmsGateways;
//require __DIR__.'/../vendor/autoload.php';
use Plivo\RestClient;
use telesign\sdk\messaging\MessagingClient;
use Twilio\Rest\Client as TwilioRestClient;
use ManeOlawale\Termii\Client;
use Illuminate\Support\Facades\Http;

class SimpleSms
{

    public function getCurl($url, $with_header = false, $header = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if ($with_header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function postCurl($url, $postFields, $is_xml = false, $is_json = false, $header = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($is_xml) {
            curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        } elseif ($is_json) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function TextLocal($numbers = array(), $message = null, $api_key, $sender = 'TextLocal', $sched = null, $test = false, $receiptURL = null, $custom = null, $optouts = false, $simpleReplyService = false)
    {
        if (!is_null($sched) && !is_numeric($sched))
            throw new Exception('Invalid date format. Use numeric epoch format');

        $params = array(
            'message' => rawurlencode($message),
            'numbers' => implode(',', $numbers),
            'sender' => rawurlencode($sender),
            'schedule_time' => $sched,
            'test' => $test,
            'receipt_url' => $receiptURL,
            'custom' => $custom,
            'optouts' => $optouts,
            'simple_reply' => $simpleReplyService,
            'api_key' => $api_key,
            'username' => false,
        );
        $url = 'https://api.textlocal.in/send/';
        return $this->postCurl($url, $params);
    }

    public function kutility($phone = null, $message = null, $api_key = null, $from = null)
    {
        $phone = str_replace("+", "", $phone);
        $sms_text = urlencode($message);
        $url = "http://kutility.in/app/smsapi/index.php";
        $args = http_build_query(array(
            'key' => $api_key,
            'routeid' => 415,
            'type' => "text",
            'contacts' => $phone,
            'senderid' => $from,
            'msg' => $sms_text
        ));
        return $this->postCurl($url, $args);
    }

    public function MobiReach($phone = null, $message = null, $username, $password, $sender)
    {

        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        $url = "https://api.mobireach.com.bd/SendTextMessage?Username=$username&Password=$password&From=$sender&To=$phone&Message=$message";
        return $this->getCurl($url);
    }

    public function Senagpay($to, $msg, $username, $password)
    {
        $to = str_replace("+", "", $to);
        $url = 'https://api.senangpay.my/notification/sms/send/linkz';
        $params = array(
            "recipient" => $to,
            "body" => $msg
        );
        return $this->postCurl($url, $params);
    }

    public function Onewaysms($phone = null, $message = null, $username, $password, $sender)
    {
        $phone = str_replace("+", "", $phone);
        $query_string = "api.aspx?apiusername=" . $username . "&apipassword=" . $password;
        $query_string .= "&senderid=" . rawurlencode($sender) . "&mobileno=" . rawurlencode($phone);
        $query_string .= "&message=" . rawurlencode(stripslashes($message)) . "&languagetype=1";
        $url = "http://gatewayd2.onewaysms.sg:10002/" . $query_string;
        return $this->getCurl($url);
    }


    public function Knowlarity($phone = null, $message = null, $username, $password, $sender)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        $url = "http://message.knowlarity.com/api/mt/SendSMS?user=$username&password=$password&senderid=$sender&channel=Trans&DCS=0&flashsms=0&number=$phone&text=$message&route=9";
        return $this->getCurl($url);
    }

    public function RouteSms($phone = null, $message = null, $username, $password, $sender)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        //Smpp http Url to send sms.
        $url = "http://rslr.connectbind.com/bulksms/bulksms?username=" . $username . "&password=" . $password . "&type=0&dlr=1&destination=" . $phone . "&source=" . $sender . "&message=" . $message . "";
        return $this->getCurl($url);
    }

    public function JavnaSms($phone = null, $otpmessage = null, $username = null, $password = null)
    {
        $phone = str_replace("+", "", $phone);
        $url = "http://http1.javna.com/epicenter/gatewaysend.asp?LoginName=" . $username . "&Password=" . $password . "&MessageRecipients=" . $phone . "&MessageBody=" . urlencode($otpmessage) . "&SenderName=Pink";
        return $this->getCurl($url);
    }


    public function Easysendsms($phone = null, $message = null, $username, $password, $sender)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        //Smpp http Url to send sms.
        $url = "https://www.easysendsms.com/sms/bulksms-api/bulksms-api?username=" . $username . "&password=" . $password . "&from=" . $sender . "&to=" . $phone . "&text=" . $message . "&type=0";
        return $this->getCurl($url);
    }

    public function Robisearch($phone = null, $message = null, $username, $password, $sender)
    {

        $url = 'http://sms.robisearch.com/sendsms.jsp?';
        $xml_data = '<?xml version="1.0"?><smslist><sms><user>' . $username . '</user><password>' . $password . '</password><message>' . $message . '</message><mobiles>' . $phone . '</mobiles><senderid>' . $sender . '</senderid><cdmasenderid>00201009546244</cdmasenderid><group>-1</group><clientsmsid>0</clientsmsid></sms></smslist>';
        return $this->postCurl($url, $xml_data, true);
    }

    public function Exotel($phone = null, $message = null, $username, $password, $sender)
    {
        $phone = str_replace("+", "", $phone);
        $post_data = array(
            'From' => $sender,
            'To' => $phone,
            'Body' => $message,
        );
        $url = "https://" . $username . ":" . $password . "@api.exotel.com/v1/Accounts/" . $username . "/Sms/send";
        return $this->postCurl($url, http_build_query($post_data));
    }

    public function clickatell($phone = null, $message = null, $username = null)
    {
        $message = urlencode($message);
        $url = "https://platform.clickatell.com/messages/http/send?apiKey=$username&to=$phone&content=$message";
        return $this->getCurl($url);
    }


    public function Nexmo($phone, $message, $api_key, $api_secret)
    {

        $basic = new \Nexmo\Client\Credentials\Basic($api_key, $api_secret);
        $client = new \Nexmo\Client($basic);

        try {
            $message = $client->message()->send([
                'to' => $phone,
                'from' => config('app.name'),
                'text' => $message
            ]);
            return $message->getResponseData();
        } catch (Exception $e) {
            return "The message was not sent. Error: " . $e->getMessage() . "\n";
        }
    }


    public function Easyservice($phone = null, $message = null, $api_key = null, $sender = null, $messageType = null)
    {
        $url = "http://app.easy.com.np/easyApi?key=" . $api_key . "&source=" . $sender . "&destination=" . $phone . "&type=" . $messageType . "&message=" . urlencode($message);
        return $this->getCurl($url);
    }

    public function NrsGateway($phone = null, $message = null, $api_key = null, $auth_token = null, $sender = null)
    {
        $post['to'] = array($phone);
        $post['text'] = $message;
        $post['from'] = $sender;
        $user = $api_key;
        $password = $auth_token;

        $url = "https://gateway.plusmms.net/rest/message";
        $header = array("Accept: application/json", "Authorization: Basic " . base64_encode($user . ":" . $password));
        return $this->postCurl($url, json_encode($post), false, true, $header);
    }

    public function WIREPICK($phone = null, $message = null, $username, $password, $sender)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        $url = "https://api.wirepick.com/httpsms/send?client=" . $username . "&password=" . $password . "&phone=$phone&text=$message&from=" . $sender . "&type=0";
        return $this->getCurl($url);
    }

    public function Cellsynt($phone, $message, $username, $password, $sender)
    {
        $sms_url = "http://se-1.cellsynt.net/sms.php";        // Gateway URL
        $type = "text";                                        // Message type
        $originatortype = "alpha";                            // Message originator (alpha = Alphanumeric, numeric = Numeric, shortcode = Operator shortcode)
        $originator = $sender;                                // Message originator
        // GET parameters
        $parameters = "username=$username&password=$password";
        $parameters .= "&type=$type&originatortype=$originatortype&originator=" . urlencode($originator);
        $parameters .= "&destination=$phone&text=" . urlencode($message);

        // Send HTTP request
        $url = $sms_url . "?" . $parameters;
        return $this->getCurl($url);
    }

    public function SmsCountry($phone, $message, $username, $password, $sender)
    {
        $user = $username;
        $password = $password;
        $mobilenumbers = $phone;
        $message = $message;
        $senderid = $sender;
        $messagetype = "N";
        $DReports = "Y";
        $url = "http://www.smscountry.com/SMSCwebservice_Bulk.aspx";
        $message = urlencode($message);
        $post_value = "User=$user&passwd=$password&mobilenumber=$mobilenumbers&message=$message&sid=$senderid&mtype=$messagetype&DR=$DReports";
        return $this->postCurl($url, $post_value);
    }

    public function Sendpulse($phone, $message, $username, $password, $sender)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.sendpulse.com/oauth/access_token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"grant_type\"\r\n\r\nclient_credentials\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"client_id\"\r\n\r\n$username\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"client_secret\"\r\n\r\n$password\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
                "postman-token: 6d5fc463-ee52-07fd-726e-ebd892c29edc"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        $access_token = $response['access_token'];
        if ($access_token) {
            $phone = str_replace("+", "", $phone);
            $message = substr($message, -4);
            $url = "https://api.sendpulse.com/sms/send";
            $body_param = json_encode(array("sender" => $sender, "phones" => "[$phone]", "body" => $message, "transliterate" => 0, "emulate" => 0));
            $header = array(
                "Authorization:Bearer $access_token",
                "Content-Type: application/json"
            );
            return $this->postCurl($url, $body_param, false, true, $header);
        } else {
            return $response;
        }
    }

    public function WauSms($phone = null, $message = null, $username, $password, $sender)
    {
        $post['to'] = array($phone);
        $post['text'] = $message;
        $post['from'] = $sender;
        $user = $username;
        $password = $password;
        $url = "https://dashboard.wausms.com/Api/rest/message";
        $header = array(
            "Accept: application/json",
            "Authorization: Basic " . base64_encode($user . ":" . $password)
        );
        return $this->postCurl($url, json_encode($post), false, true, $header);
    }

    public function EBulkSMS($data)
    {
        $url = "http://api.ebulksms.com/sendsms?username=" . $data['user_name'] . "&apikey=" . $data['api_key'] . "&sender=" . urlencode($data['from']) . "&messagetext=" . urlencode($data['message']) . "&flash=0&recipients=" . $data['to'];
        return $this->getCurl($url);
    }

    public function EngageSpark($phone, $msg, $orgId, $auth, $sender)
    {
        $phone = substr($phone, 1);
        $url = "https://api.engagespark.com/v1/sms/contact?=";
        $header = array(
            "Authorization: " . $auth,
            "Content-Type: application/json",
            "Postman-Token: e3dc5d3c-ae32-4f85-9796-cb2f9615c0b7",
            "cache-control: no-cache"
        );
        $data = "{\n\t\"orgId\":$orgId,\n\t\"to\":\"$phone\",\n\t\"from\":\"$sender\",\n\t\"message\":\"$msg\"\n}\n";
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function PostaGuvercini($phone, $msg, $username, $password)
    {
        $url = "http://www.postaguvercini.com/api_http/sendsms.asp?user=" . $username . "&password=" . $password . "&gsm=" . $phone . "&text=" . urlencode($msg);
        return $this->getCurl($url);
    }

    public function SmartSmsSolutions($phone, $msg, $senderId, $token)
    {
        $message = urlencode($msg);
        $senderid = urlencode($senderId);
        $to = $phone;
        $routing = 5; //basic route = 5
        $type = 0;
        $baseurl = 'https://smartsmssolutions.com/api/json.php?';
        $sendsms = $baseurl . 'message=' . $message . '&to=' . $to . '&sender=' . $senderid . '&type=' . $type . '&routing=' . $routing . '&token=' . $token;
        return $this->getCurl($sendsms);
    }

    public function SMSVIRO($phone, $msg, $senderId, $token)
    {
        $senderid = urlencode($senderId);
        $baseurl = 'http://107.20.199.106/restapi/sms/1/text/single';
        $paremeters = json_encode(array('from' => '', 'to' => $phone, 'text' => $msg), true);
        $header = array(
            "Authorization: " . $token,
            "Content-Type: application/json",
            "Accept: application/json"
        );
        return $this->postCurl($baseurl, $paremeters, false, true, $header);
    }

    public function AakashSMS($phone, $msg, $sender, $token)
    {
        $args = http_build_query(array(
            'auth_token' => $token,
            'from' => $sender,
            'to' => "$phone",
            'text' => $msg
        ));
        $url = "http://aakashsms.com/admin/public/sms/v1/send/";
        return $this->postCurl($url, $args);
    }

    public function BulkSmsNigeria($phone, $msg, $senderId, $token)
    {
        $url = "https://www.bulksmsnigeria.com/api/v1/sms/create?api_token=" . $token . "&from=" . $senderId . "&to=" . $phone . "&body=" . urlencode($msg);
        return $this->getCurl($url);
    }

    public function BulkSmsZamtel($phone, $msg, $apiKey, $senderId)
    {
        $phone = substr($phone, 1);
        $msg = urlencode($msg);
        $url = "http://bulksms.zamtel.co.zm/api/sms/send/batch?message=" . $msg . "&key=" . $apiKey . "&contacts=" . $phone . "&senderId=" . $senderId;
        return $this->getCurl($url);
    }

    public function SslWireLess($phone, $msg, $apiKey, $auth_token, $api_secret_key)
    {
        $user = $auth_token;
        $pass = $api_secret_key;
        $sid = $apiKey;
        $message = urlencode($msg);
        $to = $phone;

        $url = "http://sms.sslwireless.com/pushapi/dynamic/server.php";
        $param = "user=$user&pass=$pass&sms[0][0]=$to&sms[0][1]=$message&sms[0][2]=123456789&sid=$sid";
        return $this->postCurl($url, $param);
    }

    public function MYTELESOM($phone, $msg, $apiKey, $auth_token, $api_secret_key, $sender)
    {
        $username = $auth_token;
        $password = $api_secret_key;
        $key = $apiKey;
        $from = $sender;
        $to = "0" . substr($phone, -9); // Format used in sms
        $date = date('d/m/Y');

        $hashkey = $username . "|" . $password . "|" . $to . "|" . $msg . "|" . $from . "|" . $date . "|" . $key;
        $hashkey = strtoupper(md5($hashkey));
        $message = urlencode($msg);

        $url = "http://gateway.mytelesom.com/gw/" . strtolower($from) . "/sendsms?username=" . $username . "&password=" . $password . "&to=" . $to . "&msg=" . $message . "&from=" . $from . "&key=" . $hashkey;
        return $this->getCurl($url);
    }

    public function SELCOMSMS($phone, $msg, $api_key, $api_secret_key)
    {
        $phone = str_replace('+', '', $phone);
        $url = "https://gw.selcommobile.com:8443/bin/send.json?USERNAME=" . $api_key . "&PASSWORD=" . $api_secret_key . "&DESTADDR=" . $phone . "&MESSAGE=" . urlencode($msg);
        return $this->getCurl($url);
    }

    public function Nsemfua($phone, $msg, $api_key, $sender)
    {
        $phone = str_replace('+', '', $phone);
        $url = "http://nsemfua.com/portal/sms/api?action=send-sms&api_key=" . $api_key . "&to=" . $phone . "&from=" . $sender . "&sms=" . urlencode($msg);
        return $this->getCurl($url);
    }

    public function Plivo($phone, $msg, $authId, $authToken, $sender)
    {
        $client = new RestClient($authId, $authToken);
        try {
            return $client->messages->create(
                $sender, #from
                [$phone], #to
                $msg #text
            );
        } catch (\Exception $e) {
            return "The message was not sent. Error: " . $e->getMessage() . "\n";
        }
    }

    public function BulkSmsBD($phone, $msg, $username, $password)
    {
        $phone = str_replace('+880', '', $phone);  //country specific sms gateway for bangladesh
        $url = "http://66.45.237.70/api.php";
        $data = array(
            'username' => "$username",
            'password' => "$password",
            'number' => "$phone",
            'message' => "$msg"
        );
        $data = http_build_query($data);
        return $this->postCurl($url, $data);
    }

    public function MULTITEXTER($phone, $msg, $api_user, $api_password, $sender)
    {
        $phone = str_replace('+', '', $phone);
        $message = urlencode($msg);
        $url = "http://www.multitexter.com/tools/geturl/Sms.php?username=" . $api_user . "&password=" . $api_password . "&sender=" . $sender . "&message=" . $message . "&flash=1&recipients=" . $phone;
        return $this->getCurl($url);
    }

    public function Msg91($phone, $msg, $token, $sender)
    {
        $phone = str_replace('+', '', $phone);
        $message = urlencode($msg);
        $url = "https://api.msg91.com/api/sendhttp.php?mobiles=" . $phone . "&authkey=" . $token . "&route=4&sender=" . $sender . "&message=" . $message . "&country=243";
        return $this->getCurl($url);
    }

    public function OutReach($phone, $msg, $username, $password, $masking)
    {
        $phone = str_replace('+', '', $phone);
        $type = "xml";
        $lang = "English";
        $msg = urlencode($msg);
        $data = "id=" . $username . "&pass=" . $password . "&msg=" . $msg . "&to=" . $phone . "&lang=" . $lang . "&mask=" . $masking . "&type=" . $type;
        $url = 'http://www.outreach.pk/api/sendsms.php/sendsms/url';
        return $this->postCurl($url, $data);
    }

    public function BudgetSms($phone, $msg, $username, $handle, $senderId, $userId)
    {
        $phone = str_replace('+', '', $phone);
        $message = urlencode($msg);
        $senderId = urlencode($senderId);
        $url = "https://api.budgetsms.net/sendsms/?username=" . $username . "&userid=" . $userId . "&handle=" . $handle . "&msg=" . $message . "&from=" . $senderId . "&to=" . $phone;
        return $this->getCurl($url);
    }

    public function ClickATellApi($phone, $msg, $username, $password, $apiId)
    {
        $phone = str_replace('+', '', $phone);
        $message = urlencode($msg);
        $url = "https://api.clickatell.com/http/sendmsg?user=" . $username . "&password=" . $password . "&api_id=" . $apiId . "&to=" . $phone . "&text=" . $message;
        return $this->getCurl($url);
    }

    public function DataSoft($username, $senderId, $password, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $message = urlencode($message);
        $senderId = urlencode($senderId);

        $url = "http://196.202.134.90/dsms/webacc.aspx?user=$username&pwd=$password&Sender=$senderId&smstext=$message&Nums=$phone";
        return $this->getCurl($url);
    }

    public function ShamelSms($username, $password, $phone, $msg, $senderName)
    {
        $phone = str_replace('+', '', $phone);
        $url = 'http://www.shamelsms.net/api/httpSms.aspx?' . http_build_query(array(
            'username' => $username,
            'password' => $password,
            'mobile' => $phone,
            'message' => $msg,
            'sender' => $senderName,
            'unicodetype' => 'U'
        ));
        return $this->getCurl($url);
    }

    public function SMSLive247($mainAccount, $subAccount, $subAccountPass, $sender, $phone, $msg)
    {
        $phone = str_replace('+', '', $phone);
        $msg = urlencode($msg);
        $url = "http://www.smslive247.com/http/index.aspx?cmd=sendquickmsg&owneremail=" . $mainAccount . "&subacct=" . $subAccount . "&subacctpwd=" . $subAccountPass . "&message=" . $msg . "&sender=" . $sender . "&sendto=" . $phone . "&msgtype=0";
        return $this->getCurl($url);
    }

    public function Infobip($username, $password, $phone, $msg)
    {
        $phone = str_replace('+', '', $phone);
        $msg = urlencode($msg);
        $url = "https://qzepm.api.infobip.com/sms/1/text/query?username=" . $username . "&password=" . $password . "&to=" . $phone . "&text=" . $msg;
        return $this->getCurl($url);
    }

    public function TWWWireless($username, $password, $sender, $phone, $msg)
    {
        $phone = str_replace('+', '', $phone);
        $msg = urlencode($msg);
        $url = "http://webservices2.twwwireless.com.br/reluzcap/wsreluzcap.asmx/EnviaSMS?NumUsu=" . $username . "&Senha=" . $password . "&SeuNum=" . $sender . "&Celular=" . $phone . "&Mensagem=" . $msg;
        return $this->getCurl($url);
    }

    public function Sms123($api, $companyName, $phone, $msg)
    {
        $phone = str_replace('+', '', $phone);
        $msg = $companyName . ' ' . urlencode($msg);
        $url = "https://www.sms123.net/api/send.php?apiKey=" . $api . "&recipients=" . $phone . "&messageContent=" . $msg;
        return $this->getCurl($url);
    }

    public function BulkSMS($userName, $password, $phone, $msg)
    {
        $phone = str_replace('+', '', $phone);
        $msg = urlencode($msg);
        $auth = base64_encode($userName . ':' . $password);
        $url = "https://api.bulksms.com/v1/messages/send?to=" . $phone . "&body=" . $msg;
        $header = array(
            "Authorization: Basic " . $auth,
            "Content-Type: application/json",
        );
        return $this->getCurl($url, true, $header);
    }

    public function textingHouse($userName, $password, $phone, $msg, $sender = "")
    {
        $phone = str_replace("+", "", $phone);
        $data = array(
            'user' => $userName,
            'pass' => $password,
            'cmd' => 'sendsms',
            'to' => $phone,
            'txt' => $msg,
            'iscom' => 'N',
            'from' => $sender
        );
        $url = 'https://api.textinghouse.com/http/v1/do';
        $postString = http_build_query($data, '', '&');
        return $this->postCurl($url, $postString);
    }

    public function mobile360Sms($userName, $password, $sender, $phone, $msg)
    {
        $phone = str_replace("+", '', $phone);
        $url = "https://api.mobile360.ph/v3/api/broadcast";
        $postString = array(
            "username" => $userName,
            "password" => $password,
            "msisdn" => $phone,
            "content" => $msg,
            "shortcode_mask" => $sender,
            "is_intl" => false
        );
        $header = array(
            "Content-Type: application/json"
        );
        return $this->postCurl($url, json_encode($postString), false, true, $header);
    }

    public function facilitaMovel($username, $password, $phone, $msg)
    {
        $phone = str_replace("+", '', $phone);
        $msgEncoded = urlencode($msg);
        $url = "https://www.facilitamovel.com.br/api/simpleSend.ft?user=$username&password=$password&destinatario=$phone&msg=" . $msgEncoded;
        return $this->getCurl($url);
    }

    public function eSMS($api_key, $secret_key, $sender, $phone, $msg)
    {
        $phone = str_replace("+", '0', $phone);
        $msg = "($sender) " . $msg;
        $msgEncoded = urlencode($msg);
        $sender = urlencode($sender);
        $url = "http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_get?Phone=$phone&Content=$msgEncoded&ApiKey=$api_key&SecretKey=$secret_key&Brandname=$sender&SmsType=2";
        return $this->getCurl($url);
    }

    public function INFOBIPSMS($api_key, $base_url, $sender, $phone, $msg)
    {
        $phone = str_replace("+", '', $phone);
        $url = "$base_url/sms/2/text/advanced";
        $header = array(
            'Authorization: App ' . $api_key,
            'Content-Type: application/json',
            'Accept: application/json'
        );
        $post = '{"messages": [{"from": "' . $sender . '","destinations": [{"to": "' . $phone . '"}],"text": "' . $msg . '"}]}';
        return $this->postCurl($url, $post, false, true, $header);
    }

    public function iSmart($api_key, $secret_key, $phone, $msg)
    {
        $phone = str_replace("+", '', $phone);
        $url = 'https://www.ismartsms.net/iBulkSMS/HttpWS/SMSDynamicAPI.aspx?UserId=' . $api_key . '&Password=' . $secret_key . '&MobileNo=' . $phone . '&Message=' . urlencode($msg) . '&Lang=0&FLashSMS=N';
        return $this->getCurl($url);
    }

    public function smsPortal($api_key, $secret_key, $phone, $msg)
    {
        $curl = curl_init();
        $base64_secret = base64_encode("$api_key:$secret_key");
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://rest.smsportal.com/v1/Authentication',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . $base64_secret
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $returned_data = json_decode($response, true);
        $token = isset($returned_data['token']) ? $returned_data['token'] : "";
        // message send part
        $url = 'https://rest.smsportal.com/v1/BulkMessages';
        $header = array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        );
        $phone = str_replace("+", '', $phone);
        $data = json_encode(['Messages' => [['Content' => $msg, 'Destination' => $phone]]]);
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function ArkeselSMS($api_key, $sender, $phone, $msg)
    {
        $phone = str_replace('+', '', $phone);
        $msg = urlencode($msg);
        $url = "https://sms.arkesel.com/sms/api?action=send-sms&api_key=$api_key&to=$phone&from=$sender&sms=$msg";
        return $this->getCurl($url);
    }

    public function BurstSMS($api_key, $sender, $phone, $msg)
    {
        $phone = str_replace('+', '', $phone);
        $msg = urlencode($msg);
        $url = "https://api.transmitsms.com/send-sms.json?message=$msg&to=$phone&from=$sender";
        $header = array(
            "Authorization: Basic $api_key"
        );
        return $this->getCurl($url, true, $header);
    }

    public function SMSBOX($userName, $password, $customerId, $sender_id, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $message = urlencode($message);
        $url = 'https://www.smsbox.com/SMSGateway/Services/Messaging.asmx/Http_SendSMS?username=' . $userName . '&password=' . $password . '&customerId=' . $customerId . '&senderText=' . $sender_id . '&messageBody=' . $message . '&recipientNumbers=' . $phone . '&defdate=&isBlink=false&isFlash=false';
        return $this->getCurl($url);
    }

    // to-do en client's customised sms gateway
    public function whatsAppTodo($phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $data = [
            'phone' => $phone,
            'message' => $message,
        ];
        $data = json_encode($data);
        $url = 'http://api.norrisgps.com/teu/norris.php';
        $header = array(
            'Content-Type: application/json'
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function myMobileApi($api_key, $secret_key, $phone, $msg) //smsworx
    {
        $curl = curl_init();
        $base64_secret = base64_encode("$api_key:$secret_key");

        $url = 'https://rest.mymobileapi.com/v1/Authentication';
        $header = array(
            'Authorization: Basic ' . $base64_secret
        );
        $response = $this->getCurl($url, true, $header);
        $returned_data = json_decode($response, true);

        $token = isset($returned_data['token']) ? $returned_data['token'] : "";

        // message send part
        $phone = str_replace("+", '', $phone);
        $data = json_encode(['Messages' => [['Content' => $msg, 'Destination' => $phone]]]);
        $url = 'https://rest.mymobileapi.com/v1/BulkMessages';
        $header = array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function SMSCTP($userName, $password, $sender_id, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $message = urlencode($message);
        $url = "http://smsctp3.eocean.us:24555/api?action=sendmessage&username=$userName&password=$password&recipient=$phone&originator=$sender_id&messagedata=$message";
        return $this->getCurl($url);
    }

    public function SMSDEV($api_key, $phone, $message)
    {
        $url = 'https://api.smsdev.com.br/v1/send';
        $data = array(
            'key' => $api_key,
            'type' => '9',
            'number' => str_replace('+', '', $phone),
            'msg' => $message
        );
        return $this->postCurl($url, $data);
    }

    public function MuthoFun($userName, $password, $phone, $message)
    {
        $message = urlencode($message);
        $phone = str_replace('+', '', $phone);

        $url = "http://developer.muthofun.com/sms.php?username=$userName&password=$password&mobiles=$phone&sms=$message&uniccode=1";
        return $this->getCurl($url);
    }

    public function KingSms($userName, $password, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $message = urlencode($message);
        $url = "http://painel.kingsms.com.br/kingsms/api.php?acao=sendsms&login=$userName&token=$password&numero=$phone&msg=$message";
        return $this->getCurl($url);
    }

    public function GlobeLabs($app_id, $app_secret, $short_code, $pass_phrase, $phone, $message)
    {
        $url = "https://devapi.globelabs.com.ph/smsmessaging/v1/outbound/" . $short_code . "/requests?app_id=" . $app_id . "&app_secret=" . $app_secret . "&passphrase=" . $pass_phrase;
        $data = "{\"outboundSMSMessageRequest\": { \"senderAddress\": \"" . $short_code . "\", \"outboundSMSTextMessage\": {\"message\": \"" . $message . "\"}, \"address\": \"" . $phone . "\" } }";
        $header = array(
            "Content-Type: application/json"
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function MultiTexterSms($userName, $password, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $url = "https://app.multitexter.com/v2/app/sms?email=$userName&password=$password&message=$message&sender_name=$sender&recipients=$phone";
        return $this->getCurl($url);
    }

    public function MessageBird($key, $sender, $phone, $message)
    {
        $url = "https://rest.messagebird.com/messages";
        $data = array('recipients' => $phone, 'originator' => $sender, 'body' => $message);
        $header = array(
            "Authorization: AccessKey $key"
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function FloppySend($api_key, $sender, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);

        $url = "https://api.floppy.ai/sms";
        $data = "to=$phone&from=$sender&Dcs=0&Text=$message";
        $header = array(
            "X-api-key: $api_key",
            'Content-Type: application/x-www-form-urlencoded'
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function RichCommunication($auth_key, $senderId, $phone, $message)
    {
        $message = urlencode($message);
        $phone = str_replace('+', '', $phone);
        $url = "https://richcommunication.dialog.lk/api/sms/inline/send?q=$auth_key&destination=$phone&message=$message&from=$senderId";
        return $this->getCurl($url);
    }


    public function smsProNikita($username, $password, $sender, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $url = 'https://smspro.nikita.kg/api/message';
        $id = time();
        $xml_data = '<?xml version="1.0" encoding="UTF-8"?><message><login>' . $username . '</login><pwd>' . $password . '</pwd><id>' . $id . '</id><sender>' . $sender . '</sender><text>' . $message . '</text><time></time><phones><phone>' . $phone . '</phone></phones></message>';
        return $this->postCurl($url, $xml_data, true);
    }

    public function montyMobile($phone = null, $message = null, $username, $password, $sender)
    {
        $url = "https://sms.montymobile.com/API/SendSMS?username=" . $username . "&apiId=" . $password . "&json=True&destination=" . $phone . "&source=251911679409&text=(text)";
        return $this->getCurl($url);
    }

    public function SMSTo($phone, $message, $api_key, $sender)
    {
        $url = "https://api.sms.to/sms/send";
        $data = "{\n    \"message\": \"$message\",\n    \"to\": \"$phone\",\n    \"sender_id\": \"$sender\"    \n}";
        $header = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Bearer $api_key"
        );
        return $this->postCurl($url, $data, false, true, $header);
    }
    // for gaari grocery
    public function telesom($username, $passowrd, $from, $to, $message, $key)
    {
        $to = str_replace('+', '', $to);
        $curl = curl_init();
        $message = str_ireplace(" ", "%20", $message);
        $curentDate = date('d/m/Y');
        $hashkey = strtoupper(md5($username . "|" . $passowrd . "|" . $to . "|" . $message . "|" . $from . "|" . $curentDate . "|" . $key));
        $url = "https://sms.mytelesom.com/index.php/Gateway/sendsms/" . $from . "/" . $message . "/" . $to . "/" . $hashkey;
        $header = array(
            "Content-Type: application/json",
        );
        return $this->getCurl($url, true, $header);
    }

    // for oro project
    public function sudacellBulkSms($username, $passowrd, $from, $to, $message)
    {

        $to = str_replace('+', '', $to);
        $curl = curl_init();
        $message = str_ireplace(" ", "%20", $message);

        $url = "http://196.202.134.90/Smsbulk/webacc.aspx?user=" . $username . "&pwd=" . $passowrd . "&Sender=" . $from . "&smstext=" . $message . "&Nums=" . $to;
        $header = array(
            "Content-Type: application/json",
        );
        return $this->getCurl($url, true, $header);
    }

    public function BulkSMSServices($username, $password, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $phone = str_replace('+', '', $phone);
        $url = 'https://www.bulksmsservices.net/components/com_spc/smsapi.php?username=' . $username . '&password=' . $password . '&sender=' . $sender . '&recipient=' . $phone . '&message=' . $message;
        return $this->getCurl($url);
    }

    public function ORANGESMS($phone, $message, $senderAddress, $senderName, $authToken)
    {
        $url = "https://api.orange.com/oauth/v3/token";
        $data = "grant_type=client_credentials";
        $header = array(
            "Authorization: Basic $authToken",
            "Content-Type: application/x-www-form-urlencoded"
        );
        $response = $this->postCurl($url, $data, false, true, $header);
        $token = json_decode($response)->access_token;

        $url = "https://api.orange.com/smsmessaging/v1/outbound/tel%3A%2B" . $senderAddress . "/requests";
        $data = "{\n\t\"outboundSMSMessageRequest\": {\n \"address\": \"tel:$phone\",\n \"outboundSMSTextMessage\":{\n \"message\": \"$message\"\n },\n \"senderAddress\": \"tel:+$senderAddress\",\n \"senderName\": \"$senderName\"\n }\n}";
        $header = array(
            "Authorization:  Bearer $token",
            "Content-Type:  application/json"
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function ClickSend($username, $api_key, $phone, $message)
    {
        $message = urlencode($message);
        $url = 'https://api-mapper.clicksend.com/http/v2/send.php?method=http&username=' . $username . '&key=' . $api_key . '&to=' . $phone . '&message=' . $message;
        return $this->getCurl($url);
    }

    public function Sinch($service_plan_id, $bearer_token, $send_from, $phone, $message)
    {
        // Check recipient_phone_numbers for multiple numbers and make it an array.
        if (stristr($phone, ',')) {
            $phone = explode(',', $phone);
        } else {
            $phone = [$phone];
        }
        // Set necessary fields to be JSON encoded
        $content = [
            'to' => array_values($phone),
            'from' => $send_from,
            'body' => $message
        ];

        $data = json_encode($content);
        $ch = curl_init("https://us.sms.api.sinch.com/xms/v1/{$service_plan_id}/batches");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
        curl_setopt($ch, CURLOPT_XOAUTH2_BEARER, $bearer_token);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return 'Curl error: ' . curl_error($ch);
        } else {
            return $result;
        }
        curl_close($ch);
    }

    public function SmsBus($username, $passowrd, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $sender = urlencode($sender);
        $phone = str_replace('+', '', $phone);
        $url = 'https://www.lesmsbus.com:7170/ines.smsbus/smsbusMt?to=' . $phone . '&text=' . $message . '&username=' . $username . '&password=' . $passowrd . '&from=' . $sender;
        return $this->getCurl($url);
    }

    public function MessageMedia($api_key, $api_secret, $phone, $message)
    {
        $token = base64_encode($api_key . ':' . $api_secret);
        $data = [
            'messages' => [
                [
                    'content' => $message,
                    'destination_number' => $phone
                ]
            ]
        ];
        $data = \GuzzleHttp\json_encode($data);
        $url = 'https://api.messagemedia.com/v1/messages';
        $header = array(
            'Authorization: Basic ' . $token,
            'Content-Type: application/json'
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    // sms nigeria bulk sms
    public function NigeriaBulkSms($username, $passoword, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $sender = urlencode($sender);
        $phone = str_replace('+', '', $phone);
        $url = 'https://portal.nigeriabulksms.com/api/?mobiles=' . $phone . '&message=' . $message . '&username=' . $username . '&password=' . $passoword . '&sender=' . $sender;
        return $this->getCurl($url);
    }

    public function AirtelBulkSMS($username, $password, $sender_id, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $message = urlencode($message);
        $url = "http://www.airtel.sd/bulksms/webacc.aspx?user=" . $username . "&pwd=" . $password . "&smstext=" . $message . "&Sender=" . $sender_id . "&Nums=" . $phone;
        return $this->getCurl($url);
    }

    public function OrangeSMSPro($login, $api_access_key, $token, $subject, $signature, $recipient, $content)
    {
        $recipient = str_replace('+', '', $recipient);
        $content = urlencode($content);
        $subject = urlencode($subject);
        $signature = urlencode($signature);
        $timestamp = time();
        $msgToEncrypt = $token . $subject . $signature . $recipient . $content . $timestamp;
        $key = hash_hmac('sha1', $msgToEncrypt, $api_access_key);
        $uri = 'https://api.orangesmspro.sn:8443/api?token=' . $token . '&subject=' . $subject . '&signature=' . $signature . '&recipient=' . $recipient . '&content=' . $content . '&timestamp=' . $timestamp . '&key=' . $key;
        $baisc_auth = base64_encode($login . ':' . $token);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $uri,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . $baisc_auth
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function SmsZedekaa($apiKey, $clientId, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $sender = urlencode($sender);
        $phone = str_replace('+', '', $phone);
        $url = "http://dashboard.smszedekaa.com:6005/api/v2/SendSMS?SenderId=" . $sender . "&Is_Unicode=false&Is_Flash=false&Message=" . $message . "&MobileNumbers=" . $phone . "&ApiKey=" . $apiKey . "&ClientId=" . $clientId;
        return $this->getCurl($url);
    }

    public function appNotifyLk($user_id, $api_key, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $sender = urlencode($sender);
        $phone = str_replace('+', '', $phone);
        $url = "https://app.notify.lk/api/v1/send?user_id=" . $user_id . "&api_key=" . $api_key . "&sender_id=" . $sender . "&to=" . $phone . "&message=" . $message;
        return $this->getCurl($url);
    }
    public function BeemAfrica($apiKey, $secretKey, $sender, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $postData = array(
            'source_addr' => $sender,
            'encoding' => 0,
            'message' => $message,
            'recipients' => [array('recipient_id' => '1', 'dest_addr' => $phone)]
        );
        $url = 'https://apisms.beem.africa/v1/send';
        $header = array(
            'Authorization:Basic ' . base64_encode("$apiKey:$secretKey"),
            'Content-Type: application/json'
        );
        // dd($postData,$url,$header,$apiKey,$secretKey,$sender);
        return $this->postCurl($url, json_encode($postData), false, true, $header);
    }

    public function MultiTexterV2($apiKey, $sender, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $url = 'https://app.multitexter.com/v2/app/sendsms';
        $data = array('message' => $message, 'sender_name' => $sender, 'recipients' => $phone, 'forcednd' => 1);
        $header = array(
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json'
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function LinxSMS($username, $password, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $phone = str_replace('+', '', $phone);
        $url = "https://www.5linxsms.com/api/sendsms.php?user=" . $username . "&pass=" . $password . "&receiver=" . $phone . "&sender=" . $sender . "&message=" . $message;
        return $this->getCurl($url);
    }

    public function BulkSMSDhiraagu($username, $password, $phone, $message)
    {
        $message = urlencode($message);
        $phone = str_replace('+', '', $phone);
        $url = "https://bulkmessage.dhiraagu.com.mv/jsp/receiveSMS.jsp?userid=" . $username . "&password=" . $password . "&to=" . $phone . "&text=" . $message;
        return $this->getCurl($url);
    }

    public function CloudWebSMS($apiKey, $apiToken, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $phone = str_replace('+', '', $phone);
        $url = "http://cloud.websms.lk/smsAPI?sendsms&apikey=" . $apiKey . "&apitoken=" . $apiToken . "&type=sms&from=" . $sender . "&to=" . $phone . "&text=" . $message;
        return $this->getCurl($url);
    }

    public function SMSPoh($apiKey, $sender, $phone, $message)
    {
        $data = [
            'to' => $phone,
            'message' => $message,
            'sender' => $sender
        ];
        $url = 'https://smspoh.com/api/v2/send';
        $data = json_encode($data, true);
        $header = array(
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function cmTelecome($apiKey, $sender, $phone, $message)
    {
        $data = [
            'messages' => [
                'authentication' => ['productToken' => $apiKey],
                'msg' => [
                    [
                        'body' => [
                            'type' => "auto",
                            'content' => $message,
                        ],
                        'to' => [['number' => $phone]],
                        'from' => $sender, //"CM-Telecom",
                        'allowedChannels' => ["SMS"],
                    ],
                ]
            ],

        ];
        $url = 'https://gw.cmtelecom.com/v1.0/message';
        $data = json_encode($data, true);
        $header = array(
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        );
        return $this->postCurl($url, $data, false, true, $header);
    }


    public function NsemfuaV3($apiKey, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $phone = str_replace('+', '', $phone);
        $data = [
            'recipient' => $phone,
            'sender_id' => $sender,
            'message' => $message
        ];
        $url = 'https://web.nsemfua.com/api/v3/sms/send';
        $data = json_encode($data);
        $header = array(
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
            'Content-Type: application/json'
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function SparrowSMS($token, $sender, $phone, $message)
    {
        $args = http_build_query(array(
            'token' => $token,
            'from'  => $sender,
            'to'    => $phone,
            'text'  => $message
        ));

        $url = "http://api.sparrowsms.com/v2/sms/";
        return $this->postCurl($url, $args);
    }

    public function AakashSMSV3($token, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $args = http_build_query(array(
            'auth_token' => $token,
            'to'    => $phone,
            'text'  => $message
        ));
        $url = "https://sms.aakashsms.com/sms/v3/send/";
        return $this->postCurl($url, $args);
    }

    public function BulkSMSPlans($api_id, $api_password, $sender, $template_id, $phone, $message)
    {
        $message = urlencode($message);
        $phone = str_replace('+', '', $phone);
        $url = "https://www.bulksmsplans.com/api/send_sms?api_id=$api_id&api_password=$api_password&sms_type=Transactional&sms_encoding=text&sender=$sender&number=$phone&message=$message&template_id=$template_id";
        return $this->getCurl($url);
    }

    public function WebLine($token, $sender_id, $phone, $message)
    {
        $message = urlencode($message);
        $phone = str_replace('+', '', $phone);
        $sender_id = urlencode($sender_id);
        $url = "https://sms.webline.co.tz/api/v3/sms/send?recipient=" . $phone . "&sender_id=" . $sender_id . "&message=" . $message;
        $data = [];
        $header = array(
            'Authorization: Bearer ' . $token
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function SMSTeknik($username, $password, $id, $phone, $message)
    {
        $post_data = '<?xml version="1.0" encoding="utf-8"?>
        <sms-teknik>
            <udmessage>' . $message . '</udmessage>
            <smssender>SMSTeknik</smssender>
            <items>
                <recipient>
                    <nr>' . $phone . '</nr>
                </recipient>
            </items>
        </sms-teknik>';

        $url = 'https://api.smsteknik.se/send/xml/?id=' . $id . '&user=' . $username . '&pass=' . $password;
        return $this->postCurl($url, $post_data, true);
    }

    public function IntouchSMS($username, $password, $sender, $phone, $message)
    {
        $phone = str_replace('+', '', $phone);
        $data = array(
            "sender" => $sender,
            "recipients" => $phone,
            "message" => $message,
        );

        $url = "https://www.intouchsms.co.rw/api/sendsms/.json";
        $data = http_build_query($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $result;
    }

    public function NaloSolutionsSMS($user_name, $password, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $phone = str_replace('+', '', $phone);
        $url = 'https://sms.nalosolutions.com/smsbackend/clientapi/Resl_Nalo/send-message/?username=' . $user_name . '&password=' . $password . '&type=0&destination=' . $phone . '&dlr=1&source=' . $sender . '&message=' . $message;
        return $this->getCurl($url);
    }

    public function GeezSMS($token, $phone, $message, $sender = "")
    {
        $message = urlencode($message);
        $sender = !empty($sender) ? "&shortcode_id=" . $sender : "";
        $url = "https://api.geezsms.com/api/v1/sms/send?token=" . $token . "&phone=" . $phone . "&msg=" . $message . $sender;
        return $this->getCurl($url);
    }

    public function twilioWhatsapp($api_key, $secret_key, $sender, $phone, $message)
    {
        $twilio = new TwilioRestClient($api_key, $secret_key);
        return $twilio->messages->create("whatsapp:$phone", array("from" => "whatsapp:+$sender", "body" => $message));
    }

    public function Termii($apiKey, $sender, $phone, $message)
    {
        $client = new Client($apiKey, [
            'sender_id' => $sender,
            'channel' => 'generic',
            "attempts" => 10,
            "time_to_live" => 30,
            "length" => 6,
            'pin_type' => 'ALPHANUMERIC',
            'message_type' => 'ALPHANUMERIC',
            'type' => 'plain',
        ]);
        return $client->sms->send($phone, $message);
    }

    // public function MobiWeb($username, $password, $sender, $phone = null, $message = null)
    // {
    //     $url = 'https://sms.solutions4mobiles.com/apis/auth';
    //     $data = array('type' => "access_token", 'username' => $username, 'password' => $password,);
    //     $header = array('Content-type: application/json');
    //     $response = $this->postCurl($url, json_encode($data), false, true, $header);
    //     $response = json_decode($response, true);

    //     $access_token = isset($response['payload']) ? $response['payload']['access_token'] : "";

    //     if ($access_token) {
    //         $phone = str_replace("+", "", $phone);
    //         $body_param = array("to" => [$phone], "from" => $sender, "message" => $message);
    //         $url = 'https://sms.solutions4mobiles.com/apis/sms/mt/v2/send';
    //         $data = "[$body_param]";
    //         $header = array(
    //             'Content-type: application/json',
    //             "Authorization: Bearer $access_token"
    //         );
    //         return $this->postCurl($url, json_encode($data), false, true, $header);
    //     }else{
    //         return $response;
    //     }
    // }

    public function NextSMS($user_name, $password, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $token = base64_encode($user_name . ':' . $password);

        $data = ["from" => $sender, "to" => $phone, "text" => $message];
        $url = 'https://messaging-service.co.tz/api/sms/v1/text/single';
        $header = array(
            'Authorization: Basic ' . $token,
            'Content-Type: application/json'
        );
        return $this->postCurl($url, json_encode($data), false, true, $header);
    }

    public function SMSOnfonMedia($api_key, $clientId, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        $api_key = urlencode($api_key);
        $url = "http://52.210.214.211:6005/api/v2/SendSMS?SenderId=$sender&Is_Unicode=true&Is_Flash=false&Message=$message&MobileNumbers=$phone&ApiKey=$api_key&ClientId=$clientId";
        return $this->getCurl($url);
    }

    public function smsEthiopia($url, $username, $password, $phone, $message)

    {
        $phone = str_replace('+', '', $phone);
        $args = array(
            'username' => $username,
            'password' => $password,
            'to'    => $phone,
            'text'  => $message
        );
        $header = array(
            'Content-Type: application/json'
        );
        return $this->postCurl($url, json_encode($args), false, true, $header);
    }

    public function eTech($username, $password, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $sender = urlencode($sender);
        $phone = str_replace('+', '', $phone);
        $url = 'https://sms.etech-keys.com/ss/api.php?login=' . $username . '&password=' . $password . '&sender_id=' . $sender . '&destinataire=' . $phone . '&message=' . $message;
        return $this->getCurl($url);
    }

    public function aderaSms($username, $password, $sender, $phone = null, $message = null)
    {
        //get access_token by authentication
        $url = 'http://197.156.70.196:9095/api/send_sms';
        $data = array(
            'to' => str_replace("+", "", $phone),
            'username' => $username,
            'password' => $password,
            'text' => $message
        );
        $header = array(
            'Content-type: application/json'
        );
        return $this->postCurl($url, json_encode($data), false, true, $header);
    }

    public function vonageSMS($api_key, $api_secret, $sender, $phone, $message)
    {
        // $basic = new \Vonage\Client\Credentials\Basic($api_key, $api_secret);
        // $client = new \Vonage\Client($basic);
        // $response = $client->sms->send(
        //     new \Vonage\SMS\Message\SMS($phone, $sender, $message)
        // );
        // return $response->current();

        $post['to'] = $phone;
        $post['text'] = $message;
        $post['from'] = $sender;
        $post['channel'] = "sms";
        $post['message_type'] = "text";
        $apiKey = $api_key;
        $apiSecretKey = $api_secret;

        $url = "https://api.nexmo.com/v1/messages";
        $header = array("Content-Type: application/json", "Authorization: Basic " . base64_encode($apiKey . ":" . $apiSecretKey));
        return $this->postCurl($url, json_encode($post), false, true, $header);
    }

    public function tCastSMS($api_key, $client_id, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $sender = urlencode($sender);
        $phone = str_replace('+', '', $phone);
        $url = "https://api.tcastsms.net/api/v2/SendSMS?ApiKey=$api_key&ClientId=$client_id&SenderId=$sender&Message=$message&Is_Unicode=false&Is_Flash=false&MobileNumbers=$phone";
        return $this->getCurl($url);
    }

    public function ZoomConnect($email, $token, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        $url = "https://www.zoomconnect.com:443/app/api/rest/v1/sms/send-url-parameters?recipientNumber=" . $phone . "&message=" . $message . "&email=" . $email . "&token=" . $token;
        return $this->getCurl($url);
    }

    public function SMSUEHTP($apiKey, $secretKey, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        $url = "https://smsc.kz/sys/send.php?login=$apiKey&psw=$secretKey&phones=$phone&mes=$message";
        $header = array(
            'Content-Type: application/json'
        );
        return $this->getCurl($url, true, $header);
    }

    public function TeleSign($customer_id, $api_key, $phone, $message)
    {
        $messaging = new MessagingClient($customer_id, $api_key, $rest_endpoint = "https://rest-api.telesign.com");
        return $messaging->message($phone, $message, "ARN");
    }

    public function HadaraBulkSMS($apiKey, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        $url = "http://smsservice.hadara.ps:4545/SMS.ashx/bulkservice/sessionvalue/sendmessage/?apikey=" . $apiKey . "&to=" . $phone . "&msg=" . $message;
        return $this->getCurl($url);
    }

    public function mNotify($apiKey, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        $url = "https://apps.mnotify.net/smsapi?key=$apiKey&to=$phone&msg=$message&sender_id=$sender";
        return $this->getCurl($url);
    }

    public function wassaSMS($token, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $dlrurl = "";
        $url = "http://www.wassasms.com/wassasms/api/web/v3/sends?access-token=" . $token . "&sender=" . $sender . "&receiver=" . $phone . "&text=" . $message . "&dlr_url=" . $dlrurl;
        return $this->getCurl($url);
    }

    public function SMSHub($authId, $secretKey, $phone, $message)
    {
        //generate token
        $url = 'https://app.smshub.ao/api/authentication';
        $data = array('authId' => $authId, 'secretKey' => $secretKey);
        $response = json_decode($this->postCurl($url, $data), true);
        $token = $response['data']['authToken'];

        //sending sms
        $phone = str_replace("+244", "", $phone);
        $post_data = ['contactNo' => [$phone], 'message' => $message];
        $header = array('accessToken: ' . $token, 'Content-Type: application/json');
        $url = 'https://app.smshub.ao/api/sendsms';
        return $this->postCurl($url, json_encode($post_data), false, true, $header);
    }

    public function SmsArkesel($api_key, $sender_id, $phone, $message)
    {
        $message = urlencode($message);
        $url = "https://sms.arkesel.com/sms/api?action=send-sms&api_key=" . $api_key . "&to=" . $phone . "&from=" . $sender_id . "&sms=" . $message;
        return $this->getCurl($url);
    }

    public function leTexto($username, $password, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        $sender = urlencode($sender);
        $cliMsgId = $phone . strtotime(now());
        $sendAt = date("Y-m-d");
        $url = "https://httpapi.letexto.com/api/v1/sms/send?cliMsgId=$cliMsgId&from=$sender&to=$phone&dlrUrl=http%3A%2F%2Fexample.com%2Fdlr&text=$message&charset=0&sendAt=$sendAt&username=$username&password=$password";
        return $this->getCurl($url);
    }

    public function SmartVision($apiKey, $senderId, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $message = urlencode($message);
        $url = "http://customers.smartvision.ae/sms/smsapi?api_key=" . $apiKey . "&type=text&contacts=" . $phone . "&senderid=" . $senderId . "&msg=" . $message;
        return $this->getCurl($url);
    }

    public function NiText($username, $password, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $payload = [['msisdn' => $phone, 'message' => $message, 'unique_id' => 12095]];
        $data = [
            'username' => $username,
            'password' => $password,
            'oa' => $sender,
            'payload' => json_encode($payload)
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://nitext.co.ke/index.php/api/sendSmsMultiple',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: ci_session=ts73fasbqqdfkpkk3tsld79pd64ojg3d'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //        echo $response;
    }

    public function Unimatrix($accessKey, $senderId, $phone, $message)
    {
        //        $final_message = sprintf($custom_message,$otp);
        $data = [
            'to' => $phone,
            // 'signature' => $senderId,
            'text' => $message,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.unimtx.com/?action=sms.message.send&accessKeyId=' . $accessKey,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);

        curl_close($curl);
        //        echo $response;
    }

    public function Releans($accessToken, $senderId, $phone, $message)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.releans.com/v2/message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('sender' => $senderId, 'mobile' => $phone, 'content' => $message),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $accessToken,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //        echo $response;
    }

    public function mTarget($username, $password, $senderId, $serviceId, $phone, $message)
    {
        $phone = str_replace("+", "00", $phone);
        $message = urlencode($message);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-public-2.mtarget.fr/messages?username=' . $username . '&password=' . $password . '&msisdn=' . $phone . '&msg=' . $message . '&serviceid=' . $serviceId . '&sender=' . $senderId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: SERVERID=A'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function Yegara($username, $password, $phone, $otp)
    {
        $phone = str_replace("+", "", $phone);
        $template_id = 'otp';
        $server = 'https://sms.yegara.com/api2/send';
        $postData = array('to' => $phone, 'message' => $otp, 'template_id' => $template_id, 'password' => $password, 'username' => $username);
        return $this->postCurl($server, json_encode($postData));
    }

    public function Zynle($username, $password, $senderId, $phone, $message)
    {
        $url = "https://www.smszambia.com/smsservice/httpapi?username=$username&password=$password&msg=$message&sender_id=$senderId&phone=$phone";
        return $this->getCurl($url);
    }

    public function Hormuud($username, $password, $senderId, $phone, $message)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, 'https://smsapi.hormuud.com/token');
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array('Username' => $username, 'Password' => $password, 'grant_type' => 'password')));
        $Access_Token = curl_exec($curl);
        $Access_Token = json_decode($Access_Token);
        $data = [
            'mobile' => $phone,
            'message' => $message
        ];
        $data = json_encode($data);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://smsapi.hormuud.com/api/SendSMS',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $Access_Token->access_token,
                'Content-Type: application/json',
                'Cookie: cookiesession1=678B2867U024688024BCDEFGHIJLF5AD'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function NatyaBIP($username, $password, $senderId, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $data = array('username' => $username, 'password' => $password, 'sender' => $senderId, 'to' => $phone, 'text' => $message);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://apisms.natyaservices.com/apiv2_WEB/FR/api.awp',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        $response = curl_exec($curl);
        if ($response === false)
            $response = curl_error($curl);

        curl_close($curl);
        return $response;

        //endpoint working for diellcab
        //$url = "https://api.natyabip.com/apipreprod_WEB/FR/api.awp?username=".$username."&password=".$password."&sender=".$senderId."&to=".$phone."&text=".$message;
    }

    public function Arkesel($apiKey, $secretKey, $senderId, $phone, $message)
    {
        $message = urlencode($message);
        $url = "https://sms.arkesel.com/sms/api?action=$apiKey&api_key=" . $secretKey . "&to=" . $phone . "&from=" . $senderId . "&sms=" . $message;
        return $this->getCurl($url);
    }

    public function Kwiktalksms($apiKey, $authToken, $senderId, $phone, $message)
    {
        $url = "https://kwiktalk.io/api/v2/submit";
        $postData = array('Authorization' => $authToken, 'Payload' => $message, 'Sender' => $senderId, 'Recipient' => $phone);
        $header = array(
            "Content-Type: application/json",
            "ApiKey: $apiKey"
        );
        return $this->postCurl($url, json_encode($postData), false, true, $header);
    }

    public function EtDeliverySMS($configUrl, $phone, $message)
    {
        $message = urlencode($message);
        // $url = "http://196.188.175.2:8080/send-sms?body=".$message."&phoneNumber=".$phone;
        $url = $configUrl . "/send-sms?body=" . $message . "&phoneNumber=" . $phone;
        return $this->getCurl($url);
    }

    public function MsgOwlSMS($apiKey, $senderId, $phone, $message)
    {
        $url = "https://rest.msgowl.com/messages";
        $postData = array('body' => $message, 'sender_id' => $senderId, 'recipients' => $phone);
        $header = array(
            "Content-Type: application/json",
            "Authorization: AccessKey $apiKey"
        );

        return $this->postCurl($url, json_encode($postData), false, true, $header);
    }

    public function SMSMASIVOS($securityKey, $phone, $message)
    {
        $message = urlencode($message . '_' . time());
        $url = "http://servicio.smsmasivos.com.ar/enviar_sms.asp?api=1&APIKEY=" . $securityKey . "&TOS=" . $phone . "&TEXTO=" . $message;
        return $this->getCurl($url);
    }

    public function SMSD7($apiKey, $senderId, $phone, $message)
    {
        $url = "https://api.d7networks.com/messages/v1/send";
        $curl = curl_init();
        $recipients = array($phone);
        $message_obj =  array(
            "channel" => "sms",
            "msg_type" => "text",
            "recipients" => $recipients,
            "content" => $message,
            "data_coding" => "auto"
        );
        $globals_obj = array(
            "originator" => $senderId,
            "report_url" => "https://the_url_to_recieve_delivery_report.com",
        );
        $payload = json_encode(
            array(
                "messages" => array($message_obj),
                "message_globals" => $globals_obj
            )
        );

        $header = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Bearer $apiKey"
        );
        return $this->postCurl($url, $payload, false, true, $header);
    }

    public function SMSTRANZAK($appKey, $appId, $phone, $message)
    {
        $token = $this->getAuthToken($appId, $appKey);
        if (empty($token)) {
            throw new \Exception('Token not generated');
        }
        $url = "https://dsapi.tranzak.me/dn088/v1/sms/api/send";
        $postData = array('msg' => $message, 'phones' => $phone);
        $header = array(
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        );
        return $this->postCurl($url, json_encode($postData), false, true, $header);
    }

    public function getAuthToken($appId, $appKey)
    {
        try {
            $data = [
                'appId' => $appId,
                'appKey' => $appKey
            ];

            $curl = curl_init();


            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://dsapi.tranzak.me/auth/token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json'
                ),
            ));


            $response = curl_exec($curl);
            curl_close($curl);
            $res = json_decode($response, true);
            if (isset($res['data']['token'])) {
                return $res['data']['token'];
            } else {
                return '';
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function MTARGETSMS($api_secret_key, $api_key, $phone, $message)
    {
        $url = "https://api-public-2.mtarget.fr/messages";
        $data = "username=$api_key&password=$api_secret_key&msisdn=$phone&msg=$message";
        $header = array(
            'Content-Type: application/x-www-form-urlencoded'
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function TERMIISMS($api_key, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $url = 'https://api.ng.termii.com/api/sms/send';
        $postData =  array(
            "channel" => "dnd",
            "type" => "plain",
            "to" => $phone,
            "sms" => $message,
            "from" => $sender,
            "api_key" => $api_key
        );
        $header = array(
            "Content-Type: application/json"
        );

        return $this->postCurl($url, json_encode($postData), false, true, $header);
    }

    public function TEXTSMS($api_key, $api_secret_key, $sender, $phone, $message)
    {
        $url = 'https://sms.textsms.co.ke/api/services/sendsms/';
        $postData =  array(
            "apikey" => $api_secret_key,
            "partnerID" => (int) $api_key,
            "mobile" => $phone,
            "message" => $message,
            "shortcode" => $sender
        );
        $header = array(
            "Content-Type: application/json"
        );

        return $this->postCurl($url, json_encode($postData), false, true, $header);
    }

    public function ISMS($api_key, $api_secret_key, $sender, $phone, $message)
    {
        $api_secret_key = urlencode($api_secret_key);
        $api_key = urlencode($api_key);
        $sender = urlencode($sender);
        $phone = urlencode($phone);
        $message = urlencode($message);
        $url = "https://isms.celcomafrica.com/api/services/sendsms/?apikey=" . $api_secret_key . "&partnerID=" . $api_key . "&shortcode=" . $sender . "&message=" . $message . "&mobile=" . $phone;
        return $this->getCurl($url);
    }

    public function TELESIGNSMS($customerId, $api_key, $phone, $message)
    {
        $url = "https://gateway.plusmms.net/rest/message";
        $postData =  array(
            "phone_number" => $phone,
            "message" => (int) $message,
            "message_type" => 'OTP',
            "is_primary" => true
        );
        $header = array("Accept: application/x-www-form-urlencoded", "Authorization: Basic " . base64_encode($customerId . ":" . $api_key));
        return $this->postCurl($url, json_encode($postData), false, true, $header);
    }

    public function SMSALERT($api_key, $sender, $phone, $message)
    {
        // $api_key = urlencode($api_key);
        // $sender = urlencode($sender);
        // $phone = urlencode($phone);
        $message = urlencode($message);
        $url = "https://www.smsalert.co.in/api/push.json?apikey=" . $api_key . "&sender=" . $sender . "&text=" . $message . "&mobileno=" . $phone;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }


    public function BIRDSMS($config, $phone, $message)
    {

        $data = array(
            "body" => array(
                "type" => "text",
                "text" => array(
                    "text" => $message,
                )
            ),
            "receiver" => array(
                "contacts" => array(
                    array(
                        "identifierValue" => $phone,
                        "identifierKey" => "phonenumber"
                    )
                )
            )
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.bird.com/workspaces/$config->api_secret_key/channels/$config->sender/messages",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: AccessKey ' . $config->api_key,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    public function CHEVNISMS($config, $phone, $message)
    {
        $sender = $config->sender_number;
        $dst = "995" . $phone;
        $msg = urlencode($message);
        $url = "http://91.151.128.64:7777/pls/sms/phttp2sms.Process?src=$sender&dst=$dst&txt=$msg";
        dd($this->getCurl($url));
        return $this->getCurl($url);
    }

    public function WHATSAPP_SMS($config, $phone, $message)
    {
        $sender = $config->sender;
        $auth_key = $config->auth_token;
        $msg = urlencode($message);
        $url = "https://gateway.standingtech.com/api/v4/sms/send";
        $data = [
            "recipient" => $phone,
            "sender_id" => $sender,
            "type" => "whatsapp",
            "message" => $message,
            "lang" => \App::getLocale(),
        ];
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://gateway.standingtech.com/api/v4/sms/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $auth_key,
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }


    public function DREAM_DIGTAL($config, $phone, $message)
    {
        $api_id = $config->api_key;
        $api_pass = $config->auth_token;
        $sender = $config->sender;
        $url = "https://api2.dream-digital.info/api/SendSMS?api_id=$api_id&api_password=$api_pass&sms_type=T&encoding=T&sender_id=$sender&phonenumber=$phone&textmessage=" . urlencode($message);
        $response = $this->getCurl($url, false);
        return $response;
    }

    public function SAMAYASMS($config, $phone, $message)
    {
        $apiKey = $config->api_key;
        $sender = $config->sender;
        $message = urlencode($message);
        $url = "https://samayasms.com.np/smsapi/index?key=" . $apiKey . "&contacts=" . $phone . "&senderid=" . $sender . "&msg=" . $message . "&responsetype=json";
        return $this->getCurl($url);
    }

    public function TEXTMAGIC($config, $phone, $message)
    {
        $apiKey = $config->api_key;
        $username = $config->auth_token;

        $data = [
            "text" => $message,
            "phones" => $phone
        ];

        $header = array("Accept: application/json",  "Content-Type: application/json", "Authorization: Basic " . base64_encode($username . ":" . $apiKey));

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://rest.textmagic.com/api/v2/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function FARU($config, $phone, $message)
    {
        $api_key = $config->api_key;
        $sender = $config->sender;
        $url = "https://api.tililtech.com/sms/v3/sendsms";

        $data = [
            "api_key" => $api_key,
            "service_id" => 0,
            "mobile" => $phone,
            "response_type" => "json",
            "shortcode" => $sender,
            "message" => $message
        ];
        return $this->postCurl($url, json_encode($data), false, true, []);
    }

    public function HUDHUDSMS($username, $password, $phone = null, $message = null)
    {
        $url = 'http://192.168.100.55:8034/api/auth/login';
        $data = array('username' => $username, 'password' => $password);
        $header = array('Content-type: application/json');
        $response = $this->postCurl($url, json_encode($data), false, true, $header);
        $response = json_decode($response, true);
        $access_token = isset($response['token']) ? $response['token'] : "";

        if ($access_token) {
            $body_param = array("receiverAddress" => $phone, "message" => $message);
            $url = 'http://192.168.100.55:8034/api/v1/send-sms';
            $header = array(
                'Content-type: application/json',
                "Authorization: Bearer $access_token"
            );
            return $this->postCurl($url, json_encode($body_param), false, true, $header);
        } else {
            return $response;
        }
    }

    public function SPEEDAMOBILE($apiId, $apiPassword, $sender, $phone, $message)
    {
        $message = urlencode($message);
        $url = "http://apidocs.speedamobile.com/api/SendSMS?api_id=" . $apiId . "&api_password=" . $apiPassword . "&sms_type=P&encoding=T&sender_id=" . $sender . "&phonenumber=" . $phone . "&textmessage=" . $message;
        return $this->getCurl($url);
    }

    public function MobiWeb($username, $password, $sender, $phone = null, $message = null)
    {
        //get access_token by authentication
        $curl = curl_init();
        $params = json_encode(array(
            'type' => "access_token",
            'username' => $username,
            'password' => $password,
        ));
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sms.solutions4mobiles.com/apis/auth',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => array(
                'Content-type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $response = json_decode($response, true);

        $access_token = isset($response['payload']) ? $response['payload']['access_token'] : "";
        if ($access_token) {
            $phone = str_replace("+", "", $phone);
            $message = $message;
            $body_param = json_encode(array("to" => [$phone], "from" => $sender, "message" => $message));
            // p($body_param);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://sms.solutions4mobiles.com/apis/sms/mt/v2/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => "[
            	$body_param
            ]",
                CURLOPT_HTTPHEADER => array(
                    'Content-type: application/json',
                    "Authorization: Bearer $access_token"
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return $response;
        }
    }

    public function GEEZESMS($shortCodeId, $token, $phone, $message)
    {
        $url = "https://api.geezsms.com/api/v1/sms/send";
        $postData =  array(
            "phone" => $phone,
            "msg" => $message,
            "token" => $token,
            "shortcode_id" => $shortCodeId
        );
        $header = array("Accept: application/json");
        return $this->postCurl($url, $postData, false, true, $header);
    }

    public function KUDISMS($apiKey, $sender, $phone, $message)
    {
        $url = "https://my.kudisms.net/api/corporate";
        $postData =  array(
            "recipients" => $phone,
            "message" => $message,
            "token" => $apiKey,
            "senderID" => $sender
        );
        $header = array("Accept: application/json");
        return $this->postCurl($url, $postData, false, true, $header);
    }

    public function DEXATEL($apiKey, $sender, $phone, $message)
    {
        $filterSender = str_replace(' ', '', $sender);
        preg_match('/\d+/', $message, $matches);
        $otpCode = '';
        if (isset($matches[0])) {
            $otpCode = $matches[0];
        } else {
            $res = "number is required in message";
        }
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.dexatel.com/v1/templates?page_size=1&filter[name]=" . $filterSender,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "X-Dexatel-Key:" . $apiKey,
                "accept: application/json"
            ],
        ]);

        $response = json_decode(curl_exec($curl), true);
        $err = curl_error($curl);

        curl_close($curl);

        if (!empty($otpCode) && isset($response) && isset($response['data']) && isset($response['data'][0]) && isset($response['data'][0]['status']) && $response['data'][0]['status'] == "COMPLETED") {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.dexatel.com/v1/messages",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode([
                    'data' => [
                        'channel' => 'SMS',
                        'to' => [
                            $phone
                        ],
                        'template' => $response['data'][0]['id'],
                        'from' => $sender,
                        'variables' => [
                            $otpCode
                        ]
                    ]
                ]),
                CURLOPT_HTTPHEADER => [
                    "X-Dexatel-Key: " . $apiKey,
                    "accept: application/json",
                    "content-type: application/json"
                ],
            ]);

            $res = curl_exec($ch);
            curl_close($ch);
            return $res;
        } else {
            return $response;
        }
    }

    public function AQILAS($apiKey, $sender, $phone, $message)
    {

        $postData = [
            "from" => $sender,
            "text" => $message,
            "to" => [$phone]
        ];
        $token = 'X-AUTH-TOKEN: ' . $apiKey;
        $header = array($token, "Content-Type: application/json");
        return $this->postCurl("https://www.aqilas.com/api/v1/sms", json_encode($postData), false, true, $header);
    }
    public function TERMII_V3_LATEST($matchedPhoneCodeCountry, $merchantCountry, $countryAddInfo, $api_key, $base_url, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        if (count($merchantCountry) > 0  && count($countryAddInfo) > 0 && $matchedPhoneCodeCountry) {
            foreach ($matchedPhoneCodeCountry as $country) {
                // Remove + if it exists
                $phoneCode = ltrim($country->phonecode, '+');

                if (strpos($phone, $phoneCode) === 0) {
                    // Phone code matches at start

                    // Find the index of the matched country id in $merchantCountry
                    $index = array_search($country->id, $merchantCountry);
                    if ($index !== false && isset($countryAddInfo[$index])) {
                        $sender = $countryAddInfo[$index];
                    }
                }
            }
        }
        $url = $base_url . '/api/sms/send';
        $postData =  array(
            "channel" => "generic",
            "type" => "plain",
            "to" => $phone,
            "sms" => $message,
            "from" => $sender,
            "api_key" => $api_key
        );
        $header = array(
            "Content-Type: application/json"
        );

        return $this->postCurl($url, json_encode($postData), false, true, $header);
    }

    public function WIN_SMS($username, $password, $phone, $message)
    {
        $url = "https://www.winsms.co.za/api/batchmessage.asp?";
        $userp = "user=";
        $passwordp = "&password=";
        $messagep = "&message=";
        $numbersp = "&Numbers=";
        $encmessage = urlencode(utf8_encode($message));
        $link = $url . $userp . $username . $passwordp . $password . $messagep . $encmessage . $numbersp . $phone;
        return $this->getCurl($link);
    }

    public function XWIRELESS($api_key, $clientId, $sender, $phone, $message)
    {
        $phone = str_replace("234", "", $phone);
        $url = 'https://secure.xwireless.net/api/v2/SendSMS';
        $postData =  array(
            "MobileNumbers" => $phone,
            "Message" => $message,
            "SenderId" => $sender,
            "ApiKey" => $api_key,
            'ClientId' => $clientId
        );
        $header = array(
            "Content-Type: application/json"
        );

        return $this->postCurl($url, json_encode($postData), false, true, $header);
    }

    public function SMSVAS($api_key, $api_secret_key, $sender, $phone, $message)
    {
        $message = $message . " " . $sender;
        $message = urlencode($message);
        $url = "https://smsvas.com/bulk/public/index.php/api/v1/sendsms?user=" . $api_key . "&password=" . $api_secret_key . "&senderid=" . $sender . "&sms=" . $message . "&mobiles=" . $phone;
        return $this->getCurl($url);
    }

    public function EASYDIGITALSMS($api_key, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $url = 'https://api.easyservice.com.np/api/v1/sms/send';
        $data =  array(
            "message_type" => "plain",
            "contacts" => [$phone],
            "message" => $message,
            "sender_id" => [
                "nt" => $sender,
                "ncell" => "MD_Alert",
                "smart" => "MD_Alert"
            ],
            "apikey" => $api_key,
            "billing_type" => "alert"
        );
        $header = array(
            "Content-Type: application/json"
        );
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function MTSMS($api_key, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $url = 'https://gatewayapi.eu/rest/mtsms';
        $data = "sender=$sender&recipients.0.msisdn=$phone&message=$message";
        $header = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization:Basic ' . $api_key
        );
        return $this->postCurl($url, $data, false, true, $header);
    }

    public function AFOMESS_SMS($api_key, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $url = 'https://www.afomess.com/v1/rest-api/messages/send';
        $postData =  array(
            "message" => $message,
            "dest_type" => "singleton",
            "senderid" => $sender,
            "pending_type" => (string)0,
            'singleton' => $phone
        );

        $header = [
            'Authorization:Bearer ' . $api_key,
            'Content-Type: application/json'
        ];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function SMS360nrs($username, $password, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $url = 'https://dashboard.360nrs.com/api/rest/sms';
        $header = array("Accept: application/json",  "Content-Type: application/json", "Authorization: Basic " . base64_encode($username . ":" . $password));
        $data = [
            "to" => [$phone],
            "from" => $sender,
            "message" => $message
        ];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function TextlkSMS($secretKey, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $url = 'https://app.text.lk/api/v3/sms/send';
        $postData =  array(
            "recipient" => $phone,
            "message" => $message,
            "sender_id" => $sender,
            "TYPE" => 'plain'
        );
        $header = array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $secretKey
        );

        return $this->postCurl($url, json_encode($postData), false, true, $header);
    }

    public function BulkMessagingGateway($username, $password, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $url = 'https://mobile.esolutions.co.zw/bmg/api/single';
        $header = array("Accept: application/json",  "Content-Type: application/json", "Authorization: Basic " . base64_encode($username . ":" . $password));
        $data = [
            "destination" => $phone,
            "originator" => $sender,
            "messageText" => $message,
            "messageReference" => "REF_" . time(),
            "messageDate" => time(),
            "messageValidity" => "",
            "sendDateTime" => "",
        ];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    public function CLARION_SMSGateway($token, $sender, $phone, $message)
    {
        $phone = str_replace("+", "", $phone);
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->post('https://bulk.clarionsms.com/api/v3/sms/send', [
            'recipient' =>   $phone,
            'sender_id' => $sender,
            'type' => 'plain',
            'message' =>  $message,
        ]);

        // Handle response
        if ($response->successful()) {
            return $response->json();
        } else {
            // Log or throw error
            return response()->json([
                'error' => true,
                'message' => $response->body(),
            ], $response->status());
        }
    }
}
