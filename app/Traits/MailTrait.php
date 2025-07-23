<?php

namespace App\Traits;

use App\Models\User;
use App\Models\UserDevice;
use Auth;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\OAuth;

trait MailTrait
{
    public function sendMail($configuration, $receiver_email, $html_message, $email_type = 'default', $BusinessName = 'Unknown', $customer_support_number = NULL, $cc_email = "", $string_file = "")
    {
        if (empty($configuration)) {
            $default_config = (object)array(
                'host' => 'email-smtp.eu-west-2.amazonaws.com',
                'sender' => 'no-reply@mailer.apporio.com',
                'username' => 'AKIAWENYI3JEXILLWAFR',
                'password' => 'BNvaIzlzh6huSeRnKqWhp2MogsuBAFUNTNQXL20R3dtJ',
                'encryption' => 'tls',
                'port' => 587,
                'slug' => 'PHP_MAILER',
                'api_key' => '',
            );
        } else {
            $default_config = (object)array(
                'host' => $configuration->host,
                'sender' => isset($configuration->sender) ? $configuration->sender : $configuration->username,
                'username' => $configuration->username,
                'password' => $configuration->password,
                'encryption' => $configuration->encryption,
                'port' => $configuration->port,
                'slug' => $configuration->slug,
                'api_key' => $configuration->api_key,
                // 'domain_name'=> $configuration->mailgun_domain,
                // 'secret'=> $configuration->mailgun_secret,
            );
        }
        $configuration = $default_config;
        $error = 'Success';
        $mail = new PHPMailer(true);
        try {
            switch ($email_type) {
                case 'welcome':
                    $subject = 'Welcome on ' . $BusinessName;
                    break;
                case 'customer_support':
                    $subject = 'Customer Support Query of ' . $customer_support_number;
                    break;
                case 'driver_bill_settle':
                    $subject = 'Your Settled Bill Details';
                    break;
                case 'signup_otp_varification':
                    $subject = 'SignUp Otp Verification';
                    break;
                case 'forgot_password':
                    $subject = 'Forgot Password';
                    break;
                case 'ride_invoice':
                    $subject = trans("$string_file.ride_invoice");
                    break;
                case 'order_invoice':
                    $subject = trans("$string_file.order_invoice");
                    break;
                case 'booking_invoice':
                    $subject = trans("$string_file.booking_invoice");
                    break;
                case 'new_order':
                    $subject = trans("$string_file.new_order_request");
                    break;
                case 'new_ride':
                    $subject = trans("$string_file.new_ride_request");
                    break;
                case 'otp':
                    $subject = trans("$string_file.otp");
                    break;
                case 'sos':
                    $subject = trans("$string_file.sos_subject");
                    break;
                default:
                    $subject = 'Welcome';
            }

            // HTML content not found.
            if ($html_message == NULL) {
                $html_message = $subject;
            }
            
            if($configuration->slug == 'PHP_MAILER'){
                $this->PhpMailer($configuration, $receiver_email, $html_message, $subject, $BusinessName, $cc_email);
            }elseif($configuration->slug == 'BREVO'){
                $this->BrevoMail($configuration, $receiver_email, $html_message, $subject, $BusinessName);
            }
            // elseif($configuration->slug == 'MAILGUN'){
            //     $this->MailGun($configuration, $receiver_email, $html_message, $subject, $BusinessName);
            // }

            // $mail->SMTPDebug = 0;
            // $mail->isSMTP();
            // $mail->SMTPOptions = [
            //     'ssl' => [
            //         'verify_peer' => false,
            //         'verify_peer_name' => false,
            //         'allow_self_signed' => true
            //     ]
            // ];
            // $mail->Host = isset($configuration->host) ? $configuration->host : 'smtp.gmail.com';
            // $mail->SMTPAuth = true;
            // $mail->Username = isset($configuration->username) ? $configuration->username : NULL;
            // $mail->Password = isset($configuration->password) ? $configuration->password : NULL;
            // $mail->SMTPSecure = isset($configuration->encryption) ? $configuration->encryption : 'ssl';
            // $mail->Port = isset($configuration->port) ? $configuration->port : 465;

            // //Recipients
            // $mail->setFrom(isset($configuration->sender) ? $configuration->sender : NULL);
            // $mail->addAddress($receiver_email);
            // // $mail->addAddress('navdeep.singh@apporio.in');
            // if (!empty($cc_email)) {
            //     $mail->AddCC($cc_email);
            // }

            // //Content
            // $mail->isHTML(true);
            // $mail->Subject = $subject;
            // $mail->Body = $html_message;
            // $mail->AltBody = 'Alt Body';
            // $mail->CharSet = 'UTF-8';  // For Urdu and Arabic Characters
            // $mail->send();
//            p($mail);
//        } catch (phpmailerException $e) {
//            $mail->Host = 'smtp.gmail.com';
//            $mail->SMTPAuth = true;
//            $mail->Username = 'messagedelivery2020@gmail.com';
//            $mail->Password = 'RMVC,%euSzaf6fuQ';
//            $mail->SMTPSecure = 'tls';
//            $mail->Port = 587;
//
//            //Recipients
//            $mail->setFrom(isset($configuration->username) ? $configuration->username : NULL);
//            $mail->addAddress($receiver_email);
//            if (!empty($cc_email)) {
//                $mail->AddCC($cc_email);
//            }
//
//            //Content
//            $mail->isHTML(true);
//            $mail->Subject = $subject;
//            $mail->Body = $html_message;
//            $mail->AltBody = 'Alt Body';
//            $res = $mail->send();
//            $error = $e->errorMessage(); //Pretty error messages from PHPMailer
        } catch (Exception $e) {
//            $mail->Host = 'smtp.gmail.com';
//            $mail->SMTPAuth = true;
//            $mail->Username = 'messagedelivery2020@gmail.com';
//            $mail->Password = 'RMVC,%euSzaf6fuQ';
//            $mail->SMTPSecure = 'tls';
//            $mail->Port = 587;
//
//            //Recipients
//            $mail->setFrom(isset($configuration->username) ? $configuration->username : NULL);
//            $mail->addAddress($receiver_email);
//            if(!empty($cc_email))
//            {
//                $mail->AddCC($cc_email);
//            }
//
//            //Content
//            $mail->isHTML(true);
//            $mail->Subject = $subject;
//             $mail->Body = $html_message;
//            $mail->AltBody = 'Alt Body';
//            $res=$mail->send();
            $error = $e->getMessage(); //Boring error messages from anything else!
//            p($error);
        }
        $log_data = array(
            'request_type' => 'Mail Request',
            'data' => $error,
            'additional_notes' => $subject
        );
        $this->mailLog($log_data);
    }
    
    public function PhpMailer($configuration, $receiver_email, $html_message, $subject, $BusinessName = 'Unknown', $cc_email = ''){
        $error = 'Success';
        $mail = new PHPMailer(true);
        try{
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            $mail->Host = isset($configuration->host) ? $configuration->host : 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = isset($configuration->username) ? $configuration->username : NULL;
            $mail->Password = isset($configuration->password) ? $configuration->password : NULL;
            $mail->SMTPSecure = isset($configuration->encryption) ? $configuration->encryption : 'ssl';
            $mail->Port = isset($configuration->port) ? $configuration->port : 465;

            //Recipients
            $mail->setFrom(isset($configuration->sender) ? $configuration->sender : NULL);
            $mail->addAddress($receiver_email);
            if (!empty($cc_email)) {
                $mail->AddCC($cc_email);
            }

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_message;
            $mail->AltBody = 'Alt Body';
            $mail->CharSet = 'UTF-8';  // For Urdu and Arabic Characters
            $mail->send();
        }catch(Exception $e){
            $error = $e->getMessage();
        }
        
        $log_data = array(
            'request_type' => 'PHPMailer Mail Request',
            'data' => $error,
            'additional_notes' => $subject
        );
        $this->mailLog($log_data);
    }
    
    
    public function BrevoMail($configuration, $receiver_email, $html_message, $subject, $BusinessName = 'Unknown'){
        $error = 'Success';
        $api_key = !empty($configuration->api_key) ? $configuration->api_key : "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";
        try{
            $data = [
                'sender' => [
                    'name' => $BusinessName,
                    'email' => $configuration->sender
                ],
                'to' => [
                    [
                        'email' => $receiver_email,
                        'name' => $receiver_email,
                    ]
                ],
                'subject' => $subject,
                'htmlContent' => $html_message
            ];
            
//            $log_data = array(
//                'brevo_post_data' => $data,
//                'hit_time' => date('Y-m-d H:i:s')
//            );
//            \Log::channel('maillog')->info($log_data);
            $curl = curl_init();
    
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.brevo.com/v3/smtp/email',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'api-key: '.$api_key,
                    'content-type: application/json'
                ),
            ));
    
            $response = curl_exec($curl);
            curl_close($curl);
        }catch(Exception $e){
            $error = $e->getMessage();
        }
        
        $log_data = array(
            'request_type' => 'Brevo Mail Request',
            'data' => $error,
            'response'=> $response,
            'additional_notes' => $subject
        );
        $this->mailLog($log_data);
    }
    
    public function MailGun($configuration, $receiver_email, $html_message, $subject, $BusinessName = 'Unknown'){
        $error = 'Success';
        $mailgun_domain = !empty($configuration->mailgun_domain) ? $configuration->mailgun_domain : "sandboxcf9390ba39d24f1f886e11983fe43bbb.mailgun.org";
        $mailgun_secret = !empty($configuration->mailgun_secret) ? $configuration->mailgun_secret : "4678f92c0907be3b14daf53346674a8e-f68a26c9-3972244a";
        try{
            $key_text = $configuration->username . ':' . $configuration->password;
            $token = base64_encode($key_text);
            $data = [
                'from' => $configuration->sender,
                'to' => $receiver_email,
                'subject' => $subject,
                'html' => $html_message
            ];
            $curl = curl_init();
    
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.mailgun.net/v3/'.$mailgun_domain.'/messages',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'Authorization:Basic '.$token,
                    'content-type: application/json'
                ),
            ));
    
            $response = curl_exec($curl);
            curl_close($curl);
            
        }catch(Exception $e){
            $error = $e->getMessage();
        }
        
        $log_data = array(
            'request_type' => 'MailGun Request',
            'data' => $error,
            'response'=> $response,
            'additional_notes' => $subject
        );
        $this->mailLog($log_data);
    }
    

    protected function mailLog($data)
    {
        $log_data = array(
            'request_type' => $data['request_type'],
            'request_data' => $data['data'],
            'additional_notes' => $data['additional_notes'],
            'response'=> $data['response'] ?? '',
            'hit_time' => date('Y-m-d H:i:s')
        );
        \Log::channel('maillog')->info($log_data);
    }
}
