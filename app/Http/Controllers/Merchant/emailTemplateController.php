<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Helper\EmailTemplates;
use App\Http\Controllers\Helper\HolderController;
use App\Mail\UserInvoiceEmail;
use App\Mail\UserSignup;
use App\Mail\Welcome;
use App\Models\ApplicationConfiguration;
use App\Models\Booking;
use App\Models\CustomerSupport;
use App\Models\Driver;
use App\Models\DriverAccount;
use App\Models\EmailConfig;
use App\Models\EmailTemplate;
use App\Models\InfoSetting;
use App\Models\LanguageEmailTemplate;
use App\Models\Merchant;
use App\Models\User;
use App\Traits\MailTrait;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HandymanOrder;
use App\Models\BusinessSegment\Order;
use Auth;
use File;
use Mail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use DB;
use App;
use View;
use App\Traits\MerchantTrait;
use App\Models\BusinessSegment\BusinessSegment;

class emailTemplateController extends Controller
{
    use ImageTrait, MailTrait,MerchantTrait;

    public function emailconfiguration()
    {
        $checkPermission = check_permission(1, 'view_email_configurations');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $is_demo = $merchant->demo == 1 ? true : false;
        $merchant_id = $merchant->id;
        $configuration = EmailConfig::where('merchant_id', '=', $merchant_id)->first();
        // $template = EmailTemplate::where('merchant_id', '=', $merchant_id)->get();

        // if (!empty($template) && $template->isNotEmpty() && count($template) > 0) {
        //     $event = array();
        //     foreach ($template as $k => $v) {
        //         $logo = json_decode($v->logo);
        //         $event[$v->template_name]['logo'] = isset($logo->filename) ? $logo->filename : null;
        //         $event[$v->template_name]['logo_align'] = isset($logo->alignment) ? $logo->alignment : null;

        //         if (!empty($v->image)) {
        //             $image = json_decode($v->image);
        //             $event[$v->template_name]['image'] = $image->filename;
        //             $event[$v->template_name]['image_align'] = $image->alignment;
        //         }
        //         if (!empty($v->social_links)) {
        //             $links = json_decode($v->social_links);
        //             $event[$v->template_name]['social_links'] = $links->links;
        //         }

        //     }
        //     $template['event'] = $event;
        // }
        // $welcome = EmailTemplate::where([['merchant_id', '=', $merchant_id],['template_name','=','welcome']])->first();
        $info_setting = InfoSetting::where('slug', 'EMAIL_CONFIGURATION')->first();
        // return view('merchant.random.emailtemplate', compact('template', 'configuration','welcome','info_setting','is_demo'));
        return view('merchant.random.emailconfiguration', compact('configuration','info_setting','is_demo'));
    }

    public function storeemailconfiguration(Request $request)
    {
        $checkPermission =  check_permission(1,'edit_email_configurations');
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        if($request->slug == "BREVO"){
            $request->validate([
                'slug' => 'required',
                'sender' => 'required',
                'api_key' => 'required'
            ]);
        }elseif($request->slug == "PHP_MAILER"){
            $request->validate([
                'slug' => 'required',
                'host' => 'required',
                'port' => 'required',
                'username' => 'required',
                'password' => 'required',
                'encryption' => 'required'
            ]);
        }
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        DB::beginTransaction();
        try {
            // $welcomeDetails = [];
            // $invoiceDetails = [];
            // if (isset($request->logo)) {
            //     $logofilename = $this->uploadImage('logo', 'email');
            //     $logo = json_encode(array('filename' => $logofilename, 'alignment' => $request->logo_align));
            //     $log = array('logo' => $logo);
            //     $welcomeDetails = array_merge($welcomeDetails, $log);
            // }
            // if (!empty($request->image)) {
            //     $imagefilename = $this->uploadImage('image', 'email');
            //     $image = json_encode(array('filename' => $imagefilename, 'alignment' => 'center'));
            //     $img = array('image' => $image);
            //     $welcomeDetails = array_merge($welcomeDetails, $img);
            // }
            // if (isset($request->heading)) {
            //     $heading = json_encode(array('text' => $request->heading, 'alignment' => 'center'));
            //     $head = array('heading' => $heading);
            //     $welcomeDetails = array_merge($welcomeDetails, $head);
            // }
            // if (isset($request->heading)) {
            //     $subheading = json_encode(array('text' => $request->subheading, 'alignment' => 'center'));
            //     $subHead = array('subheading' => $subheading);
            //     $welcomeDetails = array_merge($welcomeDetails, $subHead);
            // }
            // if (isset($request->heading)) {
            //     $message = json_encode(array('text' => $request->textmessage, 'alignment' => 'center'));
            //     $msg = array('message' => $message);
            //     $welcomeDetails = array_merge($welcomeDetails, $msg);
            // }
            // if (isset($request->socialLinks)) {
            //     $links = json_encode(array('links' => $request->socialLinks, 'alignment' => 'center'));
            //     $link = array('social_links' => $links);
            //     $invoiceDetails = array_merge($invoiceDetails, $link);
            // }
            // if (isset($request->invoice_logo)) {
            //     $logofilename = $this->uploadImage('invoice_logo', 'email');
            //     $logo = json_encode(array('filename' => $logofilename, 'alignment' => $request->logo_align));
            //     $log = array('logo' => $logo);
            //     $invoiceDetails = array_merge($invoiceDetails, $log);
            // }
            // if (!empty($welcomeDetails)) {
            //     $email_template = EmailTemplate::updateOrCreate([
            //         'merchant_id' => $merchant_id,
            //         'template_name' => 'welcome',
            //     ], $welcomeDetails);
            //     $this->SaveLanguageEmailTemplate($merchant_id, $email_template->id, $request->heading, $request->subheading, $request->textmessage);
            // }
            // if (!empty($invoiceDetails)) {
            //     EmailTemplate::updateOrCreate([
            //         'merchant_id' => $merchant_id,
            //         'template_name' => 'invoice',
            //     ], $invoiceDetails);
            // }
            
            // if($request->mailgun_domain && $request->mailgun_secret){
            //     dd('lll',$request->all());
            //     EmailConfig::updateOrCreate([
            //     'merchant_id' => $merchant_id,
            // ], [
            //     'slug' => $request->slug,
            //     'host' => $request->host,
            //     'port' => $request->port,
            //     'sender' => isset($request->sender) ? $request->sender : $request->username,
            //     'username' => $request->username,
            //     'password' => $request->password,
            //     'encryption' => $request->encryption,
            //     'driver' => 'mailgun',
            //     'domain'=> $request->mailgun_domain,
            //     'secret'=> $request->mailgun_secret,
            //     'api_key'=> 
            // ]);
            // }
            // else{
            EmailConfig::updateOrCreate([
                'merchant_id' => $merchant_id,
            ], [
                'slug' => $request->slug,
                'host' => !empty($request->host) ? $request->host : 'host',
                'port' => !empty($request->port) ? $request->port : 'port',
                'sender' => isset($request->sender) ? $request->sender : $request->username,
                'username' => !empty($request->username) ? $request->username :  'user',
                'password' => !empty($request->password) ? $request->password : 'password',
                'encryption' => !empty($request->encryption) ? $request->encryption : 'encryption',
                'driver' => 'smtp',
                'api_key' => $request->api_key,
                // 'mailgun_domain'=> $request->mailgun_domain,
                // 'mailgun_secret'=> $request->mailgun_secret,
            ]);
            // }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return redirect()->back()->withErrors($message[0]);
        }
        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }
    public function emailtemplate()
    {
        $checkPermission = check_permission(1, 'view_email_configurations');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $is_demo = $merchant->demo == 1 ? true : false;
        $merchant_id = $merchant->id;
        $template = EmailTemplate::where('merchant_id', '=', $merchant_id)->get();

        if (!empty($template) && $template->isNotEmpty() && count($template) > 0) {
            $event = array();
            foreach ($template as $k => $v) {
                $logo = json_decode($v->logo);
                $event[$v->template_name]['logo'] = isset($logo->filename) ? $logo->filename : null;
                $event[$v->template_name]['logo_align'] = isset($logo->alignment) ? $logo->alignment : null;

                if (!empty($v->image)) {
                    $image = json_decode($v->image);
                    $event[$v->template_name]['image'] = $image->filename;
                    $event[$v->template_name]['image_align'] = $image->alignment;
                }
                if (!empty($v->social_links)) {
                    $links = json_decode($v->social_links);
                    $event[$v->template_name]['social_links'] = $links->links;
                }

            }
            $template['event'] = $event;
        }
        $welcome = EmailTemplate::where([['merchant_id', '=', $merchant_id],['template_name','=','welcome']])->first();
        return view('merchant.random.emailtemplate', compact('template','welcome','is_demo'));
    }

    public function storeemailtemplate(Request $request)
    {
        $checkPermission =  check_permission(1,'edit_email_configurations');
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        DB::beginTransaction();
        try {
            $welcomeDetails = [];
            $invoiceDetails = [];
            if (isset($request->logo)) {
                $logofilename = $this->uploadImage('logo', 'email');
                $logo = json_encode(array('filename' => $logofilename, 'alignment' => $request->logo_align));
                $log = array('logo' => $logo);
                $welcomeDetails = array_merge($welcomeDetails, $log);
            }
            if (!empty($request->image)) {
                $imagefilename = $this->uploadImage('image', 'email');
                $image = json_encode(array('filename' => $imagefilename, 'alignment' => 'center'));
                $img = array('image' => $image);
                $welcomeDetails = array_merge($welcomeDetails, $img);
            }
            if (isset($request->heading)) {
                $heading = json_encode(array('text' => $request->heading, 'alignment' => 'center'));
                $head = array('heading' => $heading);
                $welcomeDetails = array_merge($welcomeDetails, $head);
            }
            if (isset($request->heading)) {
                $subheading = json_encode(array('text' => $request->subheading, 'alignment' => 'center'));
                $subHead = array('subheading' => $subheading);
                $welcomeDetails = array_merge($welcomeDetails, $subHead);
            }
            if (isset($request->heading)) {
                $message = json_encode(array('text' => $request->textmessage, 'alignment' => 'center'));
                $msg = array('message' => $message);
                $welcomeDetails = array_merge($welcomeDetails, $msg);
            }
            if (isset($request->socialLinks)) {
                $links = json_encode(array('links' => $request->socialLinks, 'alignment' => 'center'));
                $link = array('social_links' => $links);
                $invoiceDetails = array_merge($invoiceDetails, $link);
            }
            if (isset($request->invoice_logo)) {
                $logofilename = $this->uploadImage('invoice_logo', 'email');
                $logo = json_encode(array('filename' => $logofilename, 'alignment' => $request->logo_align));
                $log = array('logo' => $logo);
                $invoiceDetails = array_merge($invoiceDetails, $log);
            }
            if (!empty($welcomeDetails)) {
                $email_template = EmailTemplate::updateOrCreate([
                    'merchant_id' => $merchant_id,
                    'template_name' => 'welcome',
                ], $welcomeDetails);
                $this->SaveLanguageEmailTemplate($merchant_id, $email_template->id, $request->heading, $request->subheading, $request->textmessage);
            }
            if (!empty($invoiceDetails)) {
                EmailTemplate::updateOrCreate([
                    'merchant_id' => $merchant_id,
                    'template_name' => 'invoice',
                ], $invoiceDetails);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return redirect()->back()->withErrors($message[0]);
        }
        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function SaveLanguageEmailTemplate($merchant_id, $email_template_id, $heading, $subheading, $message)
    {
        LanguageEmailTemplate::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'email_template_id' => $email_template_id
        ], [
            'heading' => $heading,
            'subheading' => $subheading,
            'message' => $message,
        ]);
    }

    public function WelcomeOnSignup($user_id = null)
    {
        $user_obj = User::findorfail($user_id);
        setS3Config($user_obj->Merchant);
        $string_file = $this->getStringFile(NULL,$user_obj->Merchant);
        $app_config = ApplicationConfiguration::select('user_login')->where('merchant_id', $user_obj->merchant_id)->first();
        $temp = EmailTemplate::where('merchant_id', '=', $user_obj->merchant_id)->where('template_name', '=', "welcome")->first();
        $merchant = Merchant::Find($user_obj->merchant_id);
        $configuration = EmailConfig::where('merchant_id', '=', $user_obj->merchant_id)->first();
        $email = $user_obj->email;
        if(!empty($email)  && !empty($configuration)){
            $data['temp'] = $temp;
            $data['merchant'] = $merchant;
            $data['user'] = $user_obj;
            $data['login_type'] = $app_config->login_type;
            $data['user_name'] = trans("$string_file.user_name");
            $data['message']=trans("$string_file.thanks_fo_choosing").' '.$merchant->BusinessName;
            $email_html = View::make('mail.user-welcome')->with($data)->render();
            $this->sendMail($configuration, $email, $email_html, 'welcome', $user_obj->Merchant->BusinessName,'','',$string_file);
        }
    }

    public function WelcomeOnSignupDriver($driver_id = null)
    {
        $user_obj = Driver::findorfail($driver_id);
        setS3Config($user_obj->Merchant);
        $string_file = $this->getStringFile(NULL,$user_obj->Merchant);
        $configuration = EmailConfig::where('merchant_id', '=', $user_obj->merchant_id)->first();
        $email = $user_obj->email;
        if (!empty($email) && !empty($configuration)):
            $temp = EmailTemplate::where('merchant_id', '=', $user_obj->merchant_id)->where('template_name', '=', "welcome")->first();
            $merchant = Merchant::Find($user_obj->merchant_id);
            $data['temp'] = $temp;
            $data['merchant'] = $merchant;
            $data['driver'] = $user_obj;
            $email_html = View::make('mail.driver-welcome')->with($data)->render();
            $this->sendMail($configuration, $email, $email_html, 'welcome', $user_obj->Merchant->BusinessName,'','',$string_file);
        endif;
    }

    public function CustomerSupportSendEmail(CustomerSupport $customerSupport)
    {
        $template = new EmailTemplates();
        $message = $template->CustomerSupportTemplate($customerSupport);
        $configuration = EmailConfig::where('merchant_id', '=', $customerSupport->merchant_id)->first();
        if (!empty($configuration)):
            $string_file = $this->getStringFile(NULL,$configuration->Merchant);
            $this->sendMail($configuration, $customerSupport->Merchant->Configuration->report_issue_email, $message, 'customer_support', NULL, $customerSupport->phone,'',$string_file);
        endif;
    }

    public function DriverBillEmail(DriverAccount $driver_account)
    {
        $template = new EmailTemplates();
        $message = $template->DriverBillTemplate($driver_account);
        $merchant_config = $driver_account->Driver->Merchant->Configuration;
        $configuration = EmailConfig::where('merchant_id', '=', $driver_account->Driver->merchant_id)->first();
        if (!empty($configuration)):
            $string_file = $this->getStringFile(NULL,$configuration->Merchant);
            $this->sendMail($configuration, $driver_account->Driver->email, $message, 'driver_bill_settle','','','',$string_file);
        endif;
    }

    public function DriverSignupEmailOtp($merchant_id, $driver_email = null, $otp = null)
    {
        $template = new EmailTemplates();
        $configuration = EmailConfig::where('merchant_id', '=', $merchant_id)->first();
        if (!empty($driver_email) && !empty($configuration)):
            $string_file = $this->getStringFile(NULL,$configuration->Merchant);
            $message = trans("$string_file.otp_for_verification") . " " . $otp;
            $message = $template->DriverSignUpOtpTemplate($merchant_id, $message,$string_file);
            $this->sendMail($configuration, $driver_email, $message, 'signup_otp_varification',$configuration->Merchant->BusinessName,'','',$string_file);
        endif;

    }

    public function UserSignupEmailOtp($merchant_id, $user_email, $otp)
    {
        $template = new EmailTemplates();
        $configuration = EmailConfig::where('merchant_id', '=', $merchant_id)->first();
        $string_file = isset($configuration)? $this->getStringFile(NULL,$configuration->Merchant) : $this->getStringFile($merchant_id);
        $message = trans("$string_file.otp_for_verification") . " " . $otp;
        $message = $template->SignUpOtpTemplate($merchant_id,$message,$string_file);
        $log_data = array(
            'config' => $configuration,
            'user_email' => $user_email,
            'mess' => $message,
            'hit_time' => date('Y-m-d H:i:s')
        );
        \Log::channel('maillog')->info($log_data);
        if(!empty($configuration)){
            $this->sendMail($configuration, $user_email, $message, 'signup_otp_varification',$configuration->Merchant->BusinessName,'','',$string_file);
        }
        else{
            $merchant = Merchant::find($merchant_id);
            $this->sendMail($configuration, $user_email, $message, 'signup_otp_varification',$merchant->BusinessName,'','',$string_file);
        }
    }

    public function ForgotPasswordEmail(User $user, $otp = null)
    {
        $template = new EmailTemplates();
        $configuration = EmailConfig::where('merchant_id', '=', $user->merchant_id)->first();
        if (!empty($configuration)):
            $string_file = $this->getStringFile(NULL,$configuration->Merchant);
            $message = trans("$string_file.otp_for_verification") . " " . $otp;
            $message = $template->ForgotPasswordTemplate($user, $message);
            $this->sendMail($configuration, $user->email, $message, 'forgot_password','','','',$string_file);
        endif;
    }

    public function ForgotPasswordEmailDriver(Driver $driver, $otp = null)
    {
        $template = new EmailTemplates();
        $configuration = EmailConfig::where('merchant_id', '=', $driver->merchant_id)->first();
        if (!empty($driver->email) && !empty($configuration)):
            $string_file = $this->getStringFile(NULL,$configuration->Merchant);
            $message = trans("$string_file.otp_for_verification") . " " . $otp;
            $message = $template->ForgotPasswordTemplateDriver($driver, $message);
            $this->sendMail($configuration, $driver->email, $message, 'forgot_password','','','',$string_file);
        endif;
    }

    public function SendTaxiInvoiceEmail(Booking $booking)
    {
        if($booking->Merchant->BookingConfiguration->send_user_invoice_mail == 1){
            $booking->map_image = $booking->map_image . "&zoom=12&size=600x300";
            setS3Config($booking->Merchant);
            $configuration = EmailConfig::where('merchant_id', '=', $booking->Merchant->id)->first();
            $email = $booking->user->email;
            $string_file = $this->getStringFile(NULL,$booking->Merchant);
            if(!empty($email)){
                $temp = EmailTemplate::where('merchant_id', '=', $booking->merchant_id)->where('template_name', '=', "invoice")->first();
                if (!empty($booking->BookingDetail->bill_details)) {
                    $price = json_decode($booking->BookingDetail->bill_details);
                    $holder = HolderController::PriceDetailHolder($price, $booking->id);
                } else {
                    $holder = [];
                }
                $data['booking'] = $booking;
                $data['holder'] = $holder;
                $data['temp'] = $temp;
                $data['s_string_file'] = $string_file;
                $data['formatted_start_time']= convertTimeToUSERzone(date('Y-m-d H:i:s', $booking->BookingDetail->start_timestamp), $booking->CountryArea->timezone, null, $booking->Merchant);
                $data['formattted_end_time'] = convertTimeToUSERzone(date('Y-m-d H:i:s', $booking->BookingDetail->end_timestamp), $booking->CountryArea->timezone, null, $booking->Merchant);
                $invoice_html = View::make('mail.invoice-taxi')->with($data)->render();
                // \Log::channel('debugger')->emergency($invoice_html);
                $this->sendMail($configuration, $email, $invoice_html, 'ride_invoice',$booking->Merchant->BusinessName,'','',$string_file);
            }
        }

    }

    public function SendUserHandymanInvoiceMail(HandymanOrder $handymanOrder){
        if($handymanOrder->Merchant->BookingConfiguration->send_user_invoice_mail == 1){
            setS3Config($handymanOrder->Merchant);
            $configuration = EmailConfig::where('merchant_id', '=', $handymanOrder->merchant_id)->first();
            $temp = EmailTemplate::where('merchant_id', '=', $handymanOrder->merchant_id)->where('template_name', '=', "invoice")->first();
            $email = $handymanOrder->User->email;
            if(!empty($email) && !empty($configuration->id)){
                $string_file = $this->getStringFile(NULL,$handymanOrder->Merchant);
                $data['temp'] = $temp;
                $data['booking'] = $handymanOrder;
                $data['s_string_file'] = $string_file;
                $invoice_html = View::make('mail.booking-invoice')->with($data)->render();
                //only for contrato
                if($handymanOrder->Merchant->id == 601){
                    $data['additional_email_variables'] = isset($handymanOrder->Merchant->ApplicationConfiguration->additional_email_variables) ? json_decode($handymanOrder->Merchant->ApplicationConfiguration->additional_email_variables): [];
                    $invoice_html = View::make('mail.booking-invoice-detailed')->with($data)->render();
                }
                $this->sendMail($configuration, $email, $invoice_html, 'booking_invoice', $handymanOrder->Merchant->BusinessName,NULL,$handymanOrder->Merchant->email,$string_file);
            }
        }
    }

    public function SendNewOrderRequestMail(Order $order){
        setS3Config($order->Merchant);
        $temp = EmailTemplate::where('merchant_id', '=', $order->merchant_id)->where('template_name', '=', "invoice")->first();
        $email = $order->BusinessSegment->email;
        if(!empty($email) && !empty($temp->id)){
            $string_file = $this->getStringFile(NULL,$order->Merchant);
            $data['order'] = $order;
            $data['temp'] = $temp;
            $order_request = View::make('mail.new-order-request')->with($data)->render();
            $configuration = EmailConfig::where('merchant_id', '=', $order->merchant_id)->first();
            $this->sendMail($configuration, $email, $order_request, 'new_order', $order->Merchant->BusinessName,NULL,$order->Merchant->email,$string_file);
        }
    }

    public function SendOrderInvoiceMail(Order $order){
        if($order->Merchant->BookingConfiguration->send_user_invoice_mail == 1){
            $temp = EmailTemplate::where('merchant_id', '=', $order->merchant_id)->where('template_name', '=', "invoice")->first();
            $email = $order->BusinessSegment->email;
            if(!empty($email) && !empty($temp->id)){
                $string_file = $this->getStringFile(NULL,$order->Merchant);
                $data['order'] = $order;
                $data['temp'] = $temp;
                $order_request = View::make('mail.order-invoice')->with($data)->render();
                $configuration = EmailConfig::where('merchant_id', '=', $order->merchant_id)->first();
                $this->sendMail($configuration, $email, $order_request, 'order_invoice', $order->Merchant->BusinessName,NULL,$order->Merchant->email,$string_file);
            }
        }
    }

    public function SendNewRideRequestMail(Booking $booking){
        $temp = EmailTemplate::where('merchant_id', '=', $booking->merchant_id)->where('template_name', '=', "invoice")->first();

        $email = $booking->Merchant->email;
//        $email = "bhuvanesh@apporio.com";
        if(!empty($email) && !empty($temp->id)){
            $string_file = $this->getStringFile(NULL,$booking->Merchant);
            $data['booking'] = $booking;
            $data['temp'] = $temp;
            $data['formatted_time'] = convertTimeToUSERzone(date('Y-m-d H:i:s', $booking->booking_timestamp), $booking->CountryArea->timezone, null, $booking->Merchant);
            $order_request = View::make('mail.new-ride-request')->with($data)->render();
            $configuration = EmailConfig::where('merchant_id', '=', $booking->merchant_id)->first();
            $this->sendMail($configuration, $email, $order_request, 'new_ride', $booking->Merchant->BusinessName,'','',$string_file);
        }
    }

    public function ForgotPasswordEmailBusinessSegment(BusinessSegment $business_segment, $otp = null)
    {
        $template = new EmailTemplates();
        $string_file = $this->getStringFile(NULL,$business_segment->Merchant);
        setS3Config($business_segment->Merchant);
        $message = trans("$string_file.otp_for_verification") . " " . $otp;
        $message = $template->ForgotPasswordTemplateBusinessSegment($business_segment, $message);
        $configuration = EmailConfig::where('merchant_id', '=', $business_segment->merchant_id)->first();
        if (!empty($business_segment->email) && !empty($configuration)):
            $this->sendMail($configuration, $business_segment->email, $message, 'forgot_password','','','',$string_file);
        endif;
    }
    
    public function SendTestOtpInvoice(){
        $merchant_id = 436;
        $user_email = 'navdeep.singh@apporio.in';
        $otp = 1231;
        $template = new EmailTemplates();
        $configuration = EmailConfig::where('merchant_id', '=', $merchant_id)->first();
        // dd($configuration->Merchant->BusinessName);
        if (!empty($configuration)):
            $string_file = $this->getStringFile(NULL,$configuration->Merchant);
            $message = trans("$string_file.otp_for_verification") . " " . $otp;
            $message = $template->SignUpOtpTemplate($merchant_id,$message);
            $log_data = array(
                'config' => $configuration,
                'user_email' => $user_email,
                'mess' => $message,
                'hit_time' => date('Y-m-d H:i:s')
            );
            \Log::channel('maillog')->info($log_data);
            $this->sendMail($configuration, $user_email, $message, 'signup_otp_varification','Finder TT','','',$string_file);
        endif;
    }
    
    public function dummy(){
        $order = HandymanOrder::where('id',1627)->first();
        $this->SendUserHandymanInvoiceMail($order);
        return "hello";
    }


    public function SendSosNotificationEmail(Booking $booking, $loc, $request_from)
    {
        $config = $booking->Merchant->Configuratuion;
        $booking->map_image = $booking->map_image . "&zoom=12&size=600x300";
        setS3Config($booking->Merchant);
        $configuration = EmailConfig::where('merchant_id', '=', $booking->Merchant->id)->first();
        $email = $booking->Merchant->Configuration->report_issue_email;
        $cc_email = !empty($booking->Merchant->Configuration->additional_report_issue_email)? $booking->Merchant->Configuration->additional_report_issue_email: "";
        $string_file = $this->getStringFile(NULL,$booking->Merchant);
        if(!empty($email)){
            $temp = EmailTemplate::where('merchant_id', '=', $booking->merchant_id)->where('template_name', '=', "invoice")->first();
            $data['booking'] = $booking;
            $data['request_from'] = $request_from;
            $data['temp'] = $temp;
            $data['loc'] = $loc;
            $data['s_string_file'] = $string_file;
            $invoice_html = View::make('mail.sos-email')->with($data)->render();
            $this->sendMail($configuration, $email, $invoice_html, 'sos',$booking->Merchant->BusinessName,'',$cc_email,$string_file);
        }
    }
}
