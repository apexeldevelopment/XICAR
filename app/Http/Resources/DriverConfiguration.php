<?php

namespace App\Http\Resources;

use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\Merchant;
use App\Models\AdvertisementBanner;
use App\Models\Country;
use App\Models\Driver;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use App\Models\SmsConfiguration;
use App\Models\InAppCallingConfigurations;
use App\Traits\AreaTrait;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\MerchantTrait;
use App\Models\CancelReason;

class DriverConfiguration extends JsonResource
{
     use MerchantTrait, AreaTrait;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($data)
    {
        $request_data = $data->all();
        $string_file = $this->getStringFile($this->id);
        if (request()->device == 1) {
            $main_show_dialog = $this->Configuration->android_driver_maintenance_mode == 1 ? true : false;
            $main_show_message = $this->Configuration->android_driver_maintenance_mode == 1 ? trans("$string_file.android_driver_maintenance_mode"): "";
            $version_show_dialog = $this->Configuration->android_driver_version > request()->apk_version  ? true : false;
            $version_mandatory = $this->Configuration->android_driver_mandatory_update == 1 ? true : false;
            $version_dialog_message = $version_show_dialog == true ? trans("$string_file.app_update_warning") : '';
        } else {
            $main_show_dialog = $this->Configuration->ios_driver_maintenance_mode == 1 ? true : false;
            $main_show_message = $this->Configuration->ios_driver_maintenance_mode == 1 ? trans("$string_file.ios_driver_maintenance_mode") : "";
            $version_mandatory = $this->Configuration->ios_driver_mandatory_update == 1 ? true : false;
            $version_show_dialog =  $this->Configuration->ios_driver_version > request()->apk_version ? true : false;
            $version_dialog_message = $version_show_dialog == true ? trans("$string_file.app_update_warning") : '';
        }

        $online_transaction_code = array(
            'name' => $this->Configuration->online_transaction_code,
            'placeholder' => "Please enter " . $this->Configuration->online_transaction_code . " Code",
        );
        $newMerchant = new Merchant();
        $listCountry = $newMerchant->CountrywithAreaList($this);
        $listCommissionOptions = $this->ApplicationConfiguration->driver_commission_choice ==1 ? $newMerchant->DriverCommissionChoices($this) : [];

        $banners = [];
//        if(isset($this->advertisement_module) && $this->advertisement_module == 1){
//            $banner_for = explode(',',$this->advertisement_banner);
//            if(in_array(2,$banner_for)){
//                $current_date = date('Y-m-d');
//                $add_banners = AdvertisementBanner::where([['merchant_id', '=', $this->id], ['status', '=', 1], ['activate_date', '<=', $current_date],['is_deleted', '=', null]])->whereIn('banner_for',[2,4])->get();
//                if(!empty($add_banners)){
//                    foreach ($add_banners as $add_banner){
//                        $banner_button = false;
//                        $banner_redirect_url = "";
//                        if($add_banner->redirect_url != ''){
//                            $banner_button = true;
//                            $banner_redirect_url = $add_banner->redirect_url;
//                        }
//                        if($add_banner->validity == 1){
//                            array_push($banners, array(
//                                'banner_image' => get_image($add_banner->image, 'banner',$this->id),
//                                'banner_button' => $banner_button,
//                                'banner_redirect_url' => $banner_redirect_url
//                            ));
//                        }elseif($add_banner->validity == 2 && $add_banner->expire_date >= $current_date){
//                            array_push($banners, array(
//                                'banner_image' => get_image($add_banner->image, 'banner',$this->id),
//                                'banner_button' => $banner_button,
//                                'banner_redirect_url' => $banner_redirect_url
//                            ));
//                        }
//                    }
//                }
//            }
//        }

        $otp_enable = false;
        if(isset($this->Configuration->user_login_with_otp) && $this->Configuration->user_login_with_otp == 1){
            $SmsConfiguration = SmsConfiguration::where([['merchant_id', '=', $this->id]])->first();
            if(!empty($SmsConfiguration) || $this->ApplicationConfiguration->otp_from_firebase == 1){
                $otp_enable = true;
            }
        }
        $stripe_connect_enable = false;
        if(isset($this->Configuration->stripe_connect_enable) && $this->Configuration->stripe_connect_enable == 1){
            $payment_option = PaymentOption::where('slug', 'STRIPE')->first();
            $paymentoption = PaymentOptionsConfiguration::where([['merchant_id','=', $this->id],['payment_option_id','=', $payment_option->id]])->first();
            if(!empty($paymentoption)){
                $stripe_connect_enable = true;
            }
        }

        $paystack_split_payment_enable = false;
        if(isset($this->Configuration->paystack_split_payment_enable) && $this->Configuration->paystack_split_payment_enable == 1){
            $payment_option = PaymentOption::where('slug', 'PAYSTACK')->first();
            $paymentoption = PaymentOptionsConfiguration::where([['merchant_id','=', $this->id],['payment_option_id','=', $payment_option->id]])->first();
            if(!empty($paymentoption)){
                $paystack_split_payment_enable = true;
            }
        }

        $driver_add_wallet_money_enable = false;
        if(isset($this->Configuration->driver_add_wallet_money_enable) && $this->Configuration->driver_add_wallet_money_enable == 1){
            $driver_add_wallet_money_enable = true;
        }

        $areas = [];
        if($this->Configuration->geofence_module == 1){
            $areas = $this->getGeofenceAreaList(false,$this->id);
            $areas = $areas->get();
            if(!empty($areas)){
                $areas = $areas->map(function ($item, $key)
                {
                    return array(
                        'id' => $item->id,
                        'area_name' => $item->CountryAreaName,
                        'base_area_id' => isset($item->RestrictedArea->base_areas) ? explode(',',$item->RestrictedArea->base_areas) : [],
                        'queue_system' => (isset($item->RestrictedArea->queue_system) && $item->RestrictedArea->queue_system == 1) ? true : false,
                        'coordinates' => json_decode($item->AreaCoordinates,true),
                    );
                });
            }
        }

        $account_types = [];
        if(!empty($this->AccountType)){
            foreach($this->AccountType as $account_type){
                array_push($account_types,array(
                    'id' => $account_type->id,
                    'title' => $account_type->Name
                ));
            }
        }

        $payment_method_list = [];
        foreach ($this->PaymentMethod as $paymentMethod)
        {
            if($this->id == 976){
                if($paymentMethod->id == 4){
                    $name = "M-pesa";
                }
            }
            $payment_method_list[] = [
                'id'=>$paymentMethod->id,
                'name'=>isset($name) ? $name : ($paymentMethod->MethodName($this->id) ? $paymentMethod->MethodName($this->id) : $paymentMethod->payment_method),
                'slug'=>!empty($paymentMethod->slug) ? $paymentMethod->slug : "",
            ];
        }

        $payment_option_list = $this->PaymentOption;
        $payment_option_list_slugs = [];
        
        //Encrypt Decrypt
        $androidKey = $this->BookingConfiguration->android_driver_key;
        $iosKey = $this->BookingConfiguration->ios_driver_key;
        $mail = $this->Configuration->report_issue_email;
        $phone = $this->Configuration->report_issue_phone;
        if($this->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($androidKey ){
                    $androidKey = encryptText($androidKey,$secret,$iv);
                }
                if($iosKey){
                    $iosKey = encryptText($iosKey,$secret,$iv);
                }

                if($mail){
                    $mail = encryptText($mail,$secret,$iv);
                }

                if($phone){
                    $phone = encryptText($phone,$secret,$iv);
                }
            }catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }

        if(isset($this->Configuration->countrywise_payment_gateway) && $this->Configuration->countrywise_payment_gateway == 1 && isset($request_data['driver_id']) && !empty($request_data['driver_id'])){
            $merchantDriverIds = $this->Driver->pluck('id')->toArray();
            if(in_array($request_data['driver_id'],$merchantDriverIds)){
                $driver = Driver::find($request_data['driver_id']);
                $country = Country::find($driver->country_id);
                $payment_option_list = $this->PaymentOption->whereIn("id", $country->paymentoption->pluck('payment_option_id')->toArray())->values();
                if(count($payment_option_list) == 0){
                    $payment_option_list = $this->PaymentOption;
                }
                $payment_option_list_slugs = $payment_option_list->pluck("slug")->toArray();
            }else{
                return trans("$string_file.not_valid_driver");
            }
           
        }
        else if(isset($this->logged_driver_id) && !empty($this->logged_driver_id)){
            $having_payment_methods = $this->PaymentMethod->whereIn("id",[2,4]);
            if(count($having_payment_methods) >= 1){
                $driver = Driver::find($this->logged_driver_id);
                $country = Country::find($driver->country_id);
                if(isset($country->payment_option_ids) && !empty($country->payment_option_ids)){
                    $payment_option_list = $this->PaymentOption->whereIn("id", explode(",", $country->payment_option_ids))->values();
                    $paymentOptionList = CommonController::filteredPaymentOptions($payment_option_list, $this->id,1,$this);
                }
            }
        }
        
        $paymentOptionList = CommonController::filteredPaymentOptions($payment_option_list, $this->id);
        if ($this->id == 976) {
            $paymentOptionList = array_values(array_filter($paymentOptionList->toArray(), function ($option) {
                return $option['slug'] !== 'IMBANK_MPESA';
            }));
        }

        // if(isset($this->logged_driver_id) && !empty($this->logged_driver_id)){
        //     $having_payment_methods = $this->PaymentMethod->whereIn("id",[2,4]);
        //     if(count($having_payment_methods) >= 1){
        //         $driver = Driver::find($this->logged_driver_id);
        //         $country = Country::find($driver->country_id);
        //         if(isset($country->payment_option_ids) && !empty($country->payment_option_ids)){
        //             $payment_option_list = $this->PaymentOption->whereIn("id", explode(",", $country->payment_option_ids))->values();
        //             $paymentOptionList = CommonController::filteredPaymentOptions($payment_option_list, $this->id);
        //         }
        //     }
        // }
        $in_app_call_config = InAppCallingConfigurations::where('merchant_id', $this->id)->first();
        

        switch ($this->Configuration->handyman_bidding_module_enable) {
            case 1:
                $module_type = "HANDYMAN_WITH_BIDDING";
                break;
            case 2:
                $module_type = "HANDYMAN_ONLY";
                break;
            case 3:
                $module_type = "BIDDING_ONLY";
                break;
            default:
                $module_type = "HANDYMAN_ONLY";
        }

        $payment_image = "";
        if(!empty($this->Configuration->payment_image)){
            $payment_image = get_image($this->Configuration->payment_image,'merchant',$this->id);
        }

        //for account delete cancel reason
            $cancelReason = CancelReason::where([['merchant_id', '=', $this->id],['reason_type_for', '=', 2]])->get()
            ->map(function($reason) {
                return [
                    'id' => $reason->id,
                    'reason' => $reason->ReasonName
                ];
            })->toArray();
            
            $cancelReason[] = [
                'id' => "",
                'reason' => trans("$string_file.others")
            ];
        
        $available_payment_method = array_column($this->PaymentMethod->toArray(), 'id');

        return [
            'countries' => $listCountry,
            'navigation_drawer' => array(
                'language' => true,
                'customer_support' => true,
                'report_issue' => true,
                'cms_page' => true,
                'wallet' => $this->Configuration->driver_wallet_status == 1 ? true : false,
                'card' => in_array(2,$available_payment_method) ? true : false,
            ),
            'driver_commission_choices' =>$listCommissionOptions,
            'register' => [
                'driver_commission_choice' => ($this->Configuration->subscription_package == 1 && $this->ApplicationConfiguration->driver_commission_choice == 1 ) ? true : false,
                'subscription_package_type' => $this->Configuration->subscription_package_type , // 1: old 2:new (subsc modules)
                'smoker' => $this->ApplicationConfiguration->smoker == 1 ? true : false,
                'email' => $this->ApplicationConfiguration->driver_email == 1 ? true : false,
                'driver_email_visibility' => isset($this->ApplicationConfiguration->driver_email_visibility) && $this->ApplicationConfiguration->driver_email_visibility == 1 ? true : false,
                'driver_email_otp' => $this->ApplicationConfiguration->driver_email_otp == 1 ? true : false,
                'phone' => $this->ApplicationConfiguration->driver_phone == 1 ? true : false,
                'driver_phone_otp' => $this->ApplicationConfiguration->driver_phone_otp == 1 ? true : false,
                'gender' => $this->ApplicationConfiguration->gender == 1 ? true : false,
                'driver_helper_based_registration'=> $this->ApplicationConfiguration->driver_helper_based_registration == 1 ? true : false,
            ],
            'general_config' => [
                'manual_dispatch' => $this->BookingConfiguration->driver_manual_dispatch == 1 ? true : false,
                'service_type_selection' => $this->BookingConfiguration->service_type_selection == 1 ? true : false,
                'demo' => $this->Configuration->demo == 1 ? true : false,
                'demo_login_message' => "Please make sure you have loggedin with demo user as well",
                'demand_spot_enable' => $this->Configuration->demand_spot_enable == 1 ? true : false,
                'add_multiple_vehicle' => $this->Configuration->add_multiple_vehicle == 1 ? true : false,
                'auto_accept_mode' => $this->BookingConfiguration->auto_accept_mode == 1 ? true : false,
                'subscription_package' => $this->Configuration->subscription_package == 1 ? true : false,
                'driver_limit' => $this->Configuration->driver_limit == 1 ? true : false,
                'default_language' => $this->ApplicationConfiguration->driver_default_language ?? "",
                'driver_wallet_package' => json_decode($this->Configuration->driver_wallet_amount),
                'chat' => $this->BookingConfiguration->chat == 1 ? true : false,
                'splash_screen' => $this->BusinessName,
                'emergency_contact' => $this->ApplicationConfiguration->sos_user_driver == 1 ? true : false,
                'vehicle_owner' => $this->ApplicationConfiguration->vehicle_owner == 1 ? true : false,
                'vehicle_ac_enable' => $this->Configuration->vehicle_ac_enable == 1 ? true : false,
                'vehicle_make_text' => $this->ApplicationConfiguration->vehicle_make_text == 1 ? true : false,
                'vehicle_model_text' => $this->ApplicationConfiguration->vehicle_model_text == 1 ? true : false,
                'enable_super_driver' => $this->ApplicationConfiguration->enable_super_driver == 1 ? true : false,
                'bank_details_enable' => $this->Configuration->bank_details_enable == 1 ? true : false,
//                'home_address_enable' => $this->BookingConfiguration->home_address_enable == 1 ? true : false,
                'show_logo_main' => isset($this->ApplicationConfiguration->show_logo_main) && $this->ApplicationConfiguration->show_logo_main == 1 ? true : false,
                'logo_main' => isset($this->ApplicationConfiguration->show_logo_main) && $this->ApplicationConfiguration->show_logo_main == 1 ? get_image($this->ApplicationConfiguration->logo_main, "business_logo", $this->ApplicationConfiguration->merchant_id) : "",
                'existing_vehicle_enable' => $this->Configuration->existing_vehicle_enable == 1 ? true : false,
//                'existing_vehicle_enable' =>false,
                'baby_seat_enable' => $this->BookingConfiguration->baby_seat_enable == 1 ? true : false,
                'wheel_chair_enable' => $this->BookingConfiguration->wheel_chair_enable == 1 ? true : false,
                'online_transaction_code' => $online_transaction_code,
                'driver_rating_enable' => $this->ApplicationConfiguration->driver_rating_enable == 1 ? true : false,
                'driver_cpf_number_enable' => $this->ApplicationConfiguration->driver_cpf_number_enable == 1 ? true : false,
                'splash_screen_driver' => $this->ApplicationConfiguration->splash_screen_driver,
                'otp_from_firebase' => $this->ApplicationConfiguration->otp_from_firebase == 1 ? true : false,
                'polyline' => $this->BookingConfiguration->polyline == 1 ? true : false,
                'booking_eta' => $this->BookingConfiguration->booking_eta == 1 ? true : false,
                'driver_address' => $this->Configuration->driver_address == 1 ? true : false,
                'network_code_visibility' => $this->Configuration->network_code_visibility == 1 ? true : false,
                'referral_code_enable' => isset($this->Configuration->referral_code_enable) && $this->Configuration->referral_code_enable == 1 ? true : false,
                'referral_code_mandatory_driver_signup' => $this->Configuration->referral_code_mandatory_driver_signup == 1 ? true : false,
//                'distance_unit' => Country::select('distance_unit')->where('merchant_id',$merchant_id)->first();
                'push_notification' => isset($this->Configuration->push_notification_provider) ? $this->Configuration->push_notification_provider : 1,
                'driver_cashout_module' => $this->Configuration->driver_cashout_module == 1 ? true : false,
                'restrict_country_wise_searching' => $this->ApplicationConfiguration->restrict_country_wise_searching == 1 ? true : false,
                // 'geofence_module' => $this->Configuration->geofence_module == 1 ? true : false,
                'lat_long_storing_at' => isset($this->Configuration->lat_long_storing_at) ? $this->Configuration->lat_long_storing_at : 1, //1 means in driver table of same db
                'vehicle_model_expire' => isset($this->Configuration->vehicle_model_expire)  && $this->Configuration->vehicle_model_expire == 1 ? true : false, //1 means in driver table of same db
                'driver_screen_active' => isset($this->Configuration->driver_screen_active)  && $this->Configuration->driver_screen_active == 1 ? true : false, //1 Driver screen will be active all time
                'driver_transfer_wallet_money' => isset($this->Configuration->driver_transfer_wallet_money)  && $this->Configuration->driver_transfer_wallet_money == 1 ? true : false, //1 transfer money module is enabled
                'reward_point_enable' => $this->ApplicationConfiguration->reward_points == 1 ? true : false,
                'driver_face_recognition' => $this->ApplicationConfiguration->driver_face_recognition == 1 ? true : false,
                'payment_image'=> $payment_image,
                'driver_vat_configuration'=> $this->ApplicationConfiguration->driver_vat_configuration == 1 ? true : false,
                'countrywise_payment_gateway'=> $this->Configuration->countrywise_payment_gateway == 1 ? true : false,
                'driver_service_slots'=> $this->ApplicationConfiguration->driver_service_slots == 1 ? true : false,
                'encrypt_decrypt_configuration'=> $this->Configuration->encrypt_decrypt_enable == 1 ? true : false,
                'encrypt_decrypt_enable'=> $this->Configuration->encrypt_decrypt_enable == 1 ? true : false,
                'encrypt_decrypt_secret_key'=> 'p9Nf8@xLqzB1w@Kv3rjY@5Tg4D2H@7VbXs6C',
                'encrypt_decrypt_iv_key'=> '1a2b3@c4d5e6@f7890',
                'subarea_code_enable'=> ($this->Configuration->sub_area_code_enable == 1) ? 1 : (($this->Configuration->sub_area_code_enable == 3) ? 3 : 1),
                'map_theme_select' => $this->Configuration->map_theme_select,
                'latra_vehicle_verification_enable'=> ($this->ApplicationConfiguration->latra_vehicle_verification == 1 && !empty($this->ApplicationConfiguration->latra_auth_token) && !empty($this->ApplicationConfiguration->latra_system_url)) ? true : false,
                'rental_fixed_price_type'=> $this->BookingConfiguration->rental_price_type == 1 ? true : false,
                'show_available_ride_requests'=> $this->Configuration->show_available_ride_request == 1? true : false,
                'proof_of_delivery' => !empty($this->ApplicationConfiguration->proof_of_delivery) ? $this->ApplicationConfiguration->proof_of_delivery : "",
                'volumetric_capacity_calculation' =>isset($this->BookingConfiguration->volumetric_capacity_calculation) ? number_format((float) $this->BookingConfiguration->volumetric_capacity_calculation, 2, '.', '') : (string)1.00,
                'booking_notification_api_enable'=> isset($this->Merchant->Configuration->booking_notification_api_enable) && $this->Merchant->Configuration->booking_notification_api_enable == 1 ? true : false,
                'plus_sign_on_address' => $this->Configuration->plus_sign_on_address == 1 ? true : false,
                'input_comma_in_app'=> $this->ApplicationConfiguration->enable_input_format_in_app,  //comma in input field
                'login_signup_ui'=> (string)$this->ApplicationConfiguration->login_signup_ui,
                'category_delivery_vehicle_type_module' => $this->ApplicationConfiguration->delivery_home_screen_view == 1 ? true : false,
                'body_number_enable' => $this->ApplicationConfiguration->body_number_enable == 1 ? true : false,
                'is_decimal_rating' => $this->Configuration->is_decimal_rating == 1 ? true : false,
                'is_whatsapp_calling'=> isset($this->Configuration->is_whatsapp_calling) && $this->Configuration->is_whatsapp_calling == 1 ? true : false,
                'business_segment_tax_inclusive'=> isset($this->ApplicationConfiguration->business_segment_tax_inclusive) &&  $this->ApplicationConfiguration->business_segment_tax_inclusive == 1 ? true : false,
                'show_driver_online_time'=> isset($this->ApplicationConfiguration->show_driver_online_time_enable) &&  $this->ApplicationConfiguration->show_driver_online_time_enable == 1 ? true : false,
                'bons_bank_to_bank_qr_enable' => isset($this->ApplicationConfiguration->bons_bank_to_bank_pg_enable) ? $this->ApplicationConfiguration->bons_bank_to_bank_pg_enable == 1 : false,
                'wallet_decimal_enable' => isset($this->ApplicationConfiguration->wallet_decimal_enable) ? $this->ApplicationConfiguration->wallet_decimal_enable == 1 : false,
                'password_length_for_app' => isset($this->ApplicationConfiguration->password_length_for_app) ? $this->ApplicationConfiguration->password_length_for_app : 8,
                'driver_add_vehicle_seatOrside_image_enable' => isset($this->ApplicationConfiguration->driver_add_vehicle_seatOrside_image_enable) ? $this->ApplicationConfiguration->driver_add_vehicle_seatOrside_image_enable == 1 : false,
            ],
            'languages' => $this->Language,
            'customer_support' => [
                "mail" => $mail,
                "phone" => $phone
            ],
            'paymentOption' => is_array($paymentOptionList)? $paymentOptionList: array_values($paymentOptionList->toArray()), //$paymentOptionList,
            'app_version_dialog' => [
                'show_dialog' => $version_show_dialog,
                "mandatory" => $version_mandatory,
                "android_driver_version" => $this->Configuration->android_driver_version,
                "ios_driver_version" => $this->Configuration->ios_driver_version,
                "dialog_message" => $version_dialog_message,
                "ios_driver_appid" => isset($this->Application->ios_driver_appid) ? (string)($this->Application->ios_driver_appid) : '',
                "ios_driver_link" => isset($this->Application->ios_driver_link) ? (string) ($this->Application->ios_driver_link) : '',
            ],
            'app_maintainance' => [
                'show_dialog' => $main_show_dialog,
                'show_message' => $main_show_message
            ],
            'ride_config' => [
                'outstation' => in_array(4, $this->Service) ? true : false,
                // Not in use
//                "location_update_timeband" => $this->Configuration->location_update_timeband,
//                "tracking_screen_refresh_timeband" => $this->Configuration->tracking_screen_refresh_timeband,
                "slide_button" => $this->BookingConfiguration->slide_button == 1 ? true : false,
                'drop_outside_area' => $this->Configuration->drop_outside_area == 1 ? true : false,
                "outstation_notification_popup" => $this->BookingConfiguration->outstation_notification_popup == 1 ? true : false,
                "auto_upgrade" => $this->Configuration->no_driver_availabe_enable == 1 ? true : false,
                "manual_downgrade" => $this->Configuration->manual_downgrade_enable == 1 ? true : false,
//                "drop_location_visible" => isset($this->ApplicationConfiguration->drop_location_visible) && $this->ApplicationConfiguration->drop_location_visible == 1 ? true : false,
                "drop_location_visible" => isset($this->BookingConfiguration->drop_location_request) && $this->BookingConfiguration->drop_location_request == 1 ? true : false,
                "distance_from_app"  => $this->BookingConfiguration->distance_from_app == 1 ? true : false,
                "handyman_booking_dispute" => $this->BookingConfiguration->handyman_booking_dispute == 1 ? true : false,
            ],
            'tracking' => [
                'scroll' => $this->BookingConfiguration->slide_button == 1 ? true : false
            ],
            'receiving' => [
                // 'drop_point' => false,//Not used in app, $this->BookingConfiguration->drop_location_request == 1 ? true : false,
                'estimate_fare' => $this->BookingConfiguration->estimate_fare_request == 1 ? true : false,
            ],
            'login' => [
                'email' => $this->ApplicationConfiguration->driver_login == 'EMAIL' ? true : false,
                'phone' => $this->ApplicationConfiguration->driver_login == 'PHONE' ? true : false,
                'otp' => $otp_enable,
            ],
            'theme_cofig' => [
                'primary_color_driver' => $this->ApplicationTheme->primary_color_driver ? $this->ApplicationTheme->primary_color_driver : "#E33A45",
                'driver_app_logo' => $this->ApplicationTheme->driver_app_logo ? get_image($this->ApplicationTheme->driver_app_logo,'driver_app_theme',$this->id) : "",
                'chat_button_color_driver' => $this->ApplicationTheme->chat_button_color_driver,
                'share_button_color_driver' => $this->ApplicationTheme->share_button_color_driver,
                'call_button_color_driver' => $this->ApplicationTheme->call_button_color_driver,
                'cancel_button_color_driver' => $this->ApplicationTheme->cancel_button_color_driver,
                'font_config' => $this->ApplicationTheme->font_config == 1 ? true : false,
                'font_size' => !empty($this->ApplicationTheme->font_size) ? json_decode($this->ApplicationTheme->font_size,true) : (object)[],
                'font_family' => !empty($this->ApplicationTheme->font_family) ? $this->ApplicationTheme->font_family : ""
            ],
            'geofence_areas' => $areas,
            'business_logo' => get_image($this->BusinessLogo,'business_logo',$this->id),
            'advertise_banner' => $banners,
            'advertise_banner_visibility' => isset($this->advertisement_module) ? true : false,
            'additional_information' => getAdditionalInfo(),
            'stripe_connect_enable' => $stripe_connect_enable,
            'paystack_split_payment_enable' => $paystack_split_payment_enable,
            'add_wallet_money_enable' => $driver_add_wallet_money_enable,
            'segment_group' => $this->segmentGroup($this->id,"",$string_file,[3]),
            'account_types' => $account_types,
            'accept_mobile_number_without_zero' => $this->Configuration->accept_mobile_number_without_zero == 1 ? true : false,
            'arr_payment_method' =>$payment_method_list,
            'key_data' => [
                'driver_android_key' => $androidKey ? $androidKey : "",
                'driver_ios_key' => $iosKey ? $iosKey : "",
            ],
            'driver_arriving_reminder' => $this->BookingConfiguration->driver_arriving_reminder == 1 ? true : false,
            "app_isocode_list" => isset($this->Configuration->app_isocode_list) && $this->Configuration->app_isocode_list == 1 ? true  : false,
            'app_auto_cashout' => $this->ApplicationConfiguration->app_auto_cashout == 1 ? true : false,
            'referral_autofill' => $this->Configuration->referral_autofill == 1 ? true : false,
            'app_loading_bar' => $this->ApplicationConfiguration->app_loading_bar,
            'handyman_bidding' => array(
                'module_type' => $module_type,
                'user_bid_enable' => $this->Configuration->handyman_bidding_module_user_bid_enable == 1 ? true : false,
            ),
            'ride_later_ride_allocation' => $this->BookingConfiguration->ride_later_ride_allocation,
            'rate_us_user_driver' => $this->ApplicationConfiguration->rate_us_user_driver == 1 ? true : false,
            'business_name_on_signup' => $this->ApplicationConfiguration->business_name_on_signup == 1 ? true : false,
            'in_drive_enable' => isset($this->BookingConfiguration->in_drive_enable) && $this->BookingConfiguration->in_drive_enable == 1 ? true :false,
            'lat_long_update'=> array(
                "according_to" => isset($this->Configuration->location_api_calling) && !empty($this->Configuration->location_api_calling) ? $this->Configuration->location_api_calling : "TIME",
                "distance_meter" => isset($this->Configuration->location_api_distance) && !empty($this->Configuration->location_api_distance) ? $this->Configuration->location_api_distance : 10,
                "time_second" => isset($this->Configuration->location_api_time) && !empty($this->Configuration->location_api_time) ? $this->Configuration->location_api_time : 3,
            ),
            'apple_pay_enable' => isset($this->Configuration->apple_pay_enable) && $this->Configuration->apple_pay_enable == 1 ? true : false,
            'price_format' => $this->Configuration->format_price,
            'in_app_call_active' => ($this->Configuration->in_app_call == 1) ? true : false,
            'provider_site_visit_request' => $this->ApplicationConfiguration->provider_site_visit_request,
            'in_app_call_config' => [
                "provider_slug" => !empty($in_app_call_config)? $in_app_call_config->provider_slug: "",
                "api_key" => !empty($in_app_call_config)? $in_app_call_config->api_key: "",
                "api_secret" => !empty($in_app_call_config)? $in_app_call_config->api_secret: "",
                "auth_token" => !empty($in_app_call_config)? $in_app_call_config->auth_token: "",
                "calling_number" => !empty($in_app_call_config)? $in_app_call_config->calling_number: "",
            ],
            'location_update_ride_config'=> [
                "speed"=> $this->id == 976 ? "0" : "5",
                "time"=>"180",
                "timestamp"=>"1",
                "distance"=>"50",

            ],
            'location_update_without_ride_config'=> [
                "speed"=> $this->id == 976 ? "0" : "5",
                "time"=>"180",
                "timestamp"=>"1",
                "distance"=>"50",
            ],
            'dvla_verification' => ($this->Configuration->dvla_verification == 1) ? true : false,
            'choose_model_screen' => ($this->ApplicationConfiguration->choose_model_screen == 1) ? true : false,
            'engine_based_vehicle_type' => ($this->ApplicationConfiguration->engine_based_vehicle_type == 1) ? true : false,
            'handyman_sp_base_price_start' => isset($this->ApplicationConfiguration->handyman_sp_base_price_start) ? round($this->ApplicationConfiguration->handyman_sp_base_price_start) : "",
            'handyman_cash_confirmation' => isset($this->Configuration->cash_confirmation) && $this->Configuration->cash_confirmation == 1 ? true : false,
            "on_tracking_estimate_price_visible" => isset($this->Configuration->driver_tracking_price_visibe) && $this->Configuration->driver_tracking_price_visibe == 1 ? true : false,
            'new_sos_enable'=>($this->ApplicationConfiguration->new_sos_enable == 1)? true: false,
            'geofence_list_visible'=>($this->ApplicationConfiguration->geofence_list_visible == 1)? true: false,
            'multiple_delivery_ride_screen' => $this->BookingConfiguration->multiple_delivery_ride_screen == 1,
            'enter_crn_number' => $this->ApplicationConfiguration->enter_crn_number == 1,
            'enter_receiver_name' => $this->ApplicationConfiguration->enter_receiver_name == 1,
            'handyman_visit_site_request'=> $this->ApplicationConfiguration->handyman_visit_site_request == 1,
            'vehicle_type_auto_select'=> false,
            'rating_emoji_enable'=> $this->ApplicationConfiguration->rating_emoji_enable == 1,
            'driver_kin_person_details_on_signup'=> $this->Configuration->driver_kin_person_details_on_signup == 1,
            'driver_bypass_relogin_after_signup' => $this->ApplicationConfiguration->driver_bypass_relogin_after_signup == 1,
            'gallery_upload_enable' => $this->ApplicationConfiguration->gallery_upload_enable == 1,
            'phone_number_editable' => $this->ApplicationConfiguration->phone_number_editable == 1,
            'local_citizen_foreigner_documents' => $this->ApplicationConfiguration->local_citizen_foreigner_documents == 1 ? [['id' => 1, 'name' => 'Local'], ['id' => 2, 'name' => "Foreigner"]] : [],
            'working_with_redis'=> $this->ApplicationConfiguration->working_with_redis == 1,
            'working_with_microservices'=> $this->ApplicationConfiguration->working_with_redis == 1,
            'driver_renewable_subscription_history'=>  $this->ApplicationConfiguration->driver_renewable_subscription_history == 1,
            'time_format'=> $this->Configuration->time_format,
            'account_delete_cancel_reason'=> $cancelReason
        ];
    }

    public function with($data)
    {
        return [
            'result' => "1",
            'message' => "success",
        ];
    }
}
