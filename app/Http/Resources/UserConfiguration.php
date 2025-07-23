<?php

namespace App\Http\Resources;

use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\CommonController;
use App\Models\AdvertisementBanner;
use App\Models\Country;
use App\Models\MerchantFarePolicy;
use App\Models\MerchantNavDrawer;
use App\Models\PaymentOptionsConfiguration;
use App\Models\SmsConfiguration;
use App\Models\User;
use App\Models\InAppCallingConfigurations;
use App\Traits\MerchantTrait;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Controllers\Helper\PolygenController;
use App\Models\CountryArea;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Helper\Merchant;
use App\Models\PaymentOption;
use Illuminate\Support\Facades\Schema;
use App\Models\CancelReason;

class UserConfiguration extends JsonResource
{
    use MerchantTrait;

    public function toArray($data)
    {
        //Encrypt Decrypt
        $androidKey = $this->BookingConfiguration->android_user_key;
        $iosKey = $this->BookingConfiguration->ios_user_key;
        $googleKey = $this->BookingConfiguration->google_key;
        $mapboxKey = $this->BookingConfiguration->map_box_key;
        $heremapKey = $this->BookingConfiguration->here_map_key;
        $mail = $this->Configuration->report_issue_email;
        $phone = $this->Configuration->report_issue_phone;
        $googleSignUp = $this->Configuration->google_signup_key;
        $fbSignUp = $this->Configuration->facebook_signup_key;
        $subscription_enabled = $this->Configuration->subscription_enabled == 1 ? true : false;
        if($this->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                if($this->BookingConfiguration->android_user_key ){
                    $androidKey = encryptText($this->BookingConfiguration->android_user_key,$secret,$iv);
                }
                if($this->BookingConfiguration->ios_user_key){
                    $iosKey = encryptText($this->BookingConfiguration->ios_user_key,$secret,$iv);
                }

                if($googleKey){
                    $googleKey = encryptText($googleKey,$secret,$iv);
                }

                if($mail){
                    $mail = encryptText($mail,$secret,$iv);
                }

                if($phone){
                    $phone = encryptText($phone,$secret,$iv);
                }
                if($googleSignUp){
                    $googleSignUp = encryptText($googleSignUp,$secret,$iv);
                }

                if($fbSignUp){
                    $fbSignUp = encryptText($fbSignUp,$secret,$iv);
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }

        $request_data = $data->all();
        //        $newBookingData = new BookingDataController();
        $string_file = $this->getStringFile(NULL, $this);
        if (request()->device == 1) {
            $main_show_dialog = $this->Configuration->android_user_maintenance_mode == 1 ? true : false;
            $main_show_message = $this->Configuration->android_user_maintenance_mode == 1 ? trans('api.message56') : "";
            $version_show_dialog = $this->Configuration->android_user_version > request()->apk_version ? true : false;
            $version_mandatory = $this->Configuration->android_user_mandatory_update == 1 ? true : false;
            $version_dialog_message = $version_show_dialog == true ? trans("$string_file.app_update_warning") : '';
        } else {
            $main_show_dialog = $this->Configuration->ios_user_maintenance_mode == 1 ? true : false;
            $main_show_message = $this->Configuration->ios_user_maintenance_mode == 1 ? trans('api.message56') : "";
            $version_show_dialog = $this->Configuration->ios_user_version > request()->apk_version ? true : false;
            $version_mandatory = $this->Configuration->ios_user_mandatory_update == 1 ? true : false;
            $version_dialog_message = $version_show_dialog == true ? trans("$string_file.app_update_warning") : '';
        }
        $merchant_obj = new Merchant;
        $countries = $merchant_obj->CountryList($this);
        //  $Question = $this->Question->toArray();
        if ($this->ApplicationConfiguration->security_question == 1) {
            $questions = $this->Question;
            foreach ($questions as $value) {
                $value->question = $value->Questionss;
            }
        }

        $no_of_pool_seats = $this->Configuration->no_of_pool_seats;
        $pool_seats = array();
        for ($i = 1; $i <= $no_of_pool_seats; $i++) {
            $pool_seats[]['name'] = $i . " Seat";
        }
        $additional_info = false;
        if ($this->ApplicationConfiguration->gender == 1 || $this->Configuration->family_member_enable == 1 || $this->BookingConfiguration->wheel_chair_enable == 1 || $this->Configuration->no_of_person == 1 || $this->Configuration->no_of_children == 1 || $this->Configuration->no_of_bags == 1 || $this->Configuration->vehicle_ac_enable == 1 || $this->Configuration->no_of_pats == 1) {
            $additional_info = true;
        }


        $arr_nevigation = [];

        $navigationDrawer = MerchantNavDrawer::with("AppNavigationDrawer")->where([['merchant_id', '=', $this->id], ['status', '=', true]])->orderBy('sequence', 'asc')->select(['id', 'app_navigation_drawer_id', 'image', 'sequence', 'additional_data'])->get();
        foreach ($navigationDrawer as $key => $values):
            $image = !empty($values->image) ? get_image($values->image, 'drawericons', $this->id, true) :
                get_image($values->AppNavigationDrawer->image, 'drawer_icon', null, false);
            $values['image'] = $image;
//                ($values['image'] == null) ? $values->AppNavigationDrawer->image : $values['image'];
            $values['name'] = $values->name;
            $values['slug'] = $values->slug;
            $values['id'] = $values->app_navigation_drawer_id;
            $values['text_colour'] = $this->ApplicationTheme->navigation_colour;
            $values['text_style'] = $this->ApplicationTheme->navigation_style;
            unset($values['app_navigation_drawer_id']);
            $navigationDrawer[$key] = $values;
        endforeach;

        if (count($navigationDrawer) > 0) { // $this->NavigationDrawer
            foreach ($navigationDrawer as $nevigation) { // $this->NavigationDrawer
                $check_status = true;
                //check wallet user enabled from merchant
                if ($nevigation['slug'] == 'wallet-activity' && $this->Configuration->user_wallet_status != 1) {
                    $check_status = false;
                }
                //check favourite driver enabled from merchant
                if ($nevigation['slug'] == 'favourite-driver' && $this->ApplicationConfiguration->favourite_driver_module != 1) {
                    $check_status = false;
                }
                //check SOS for user/driver enabled from merchant
                if ($nevigation['slug'] == 'emergency-contacts' && $this->ApplicationConfiguration->sos_user_driver != 1) {
                    $check_status = false;
                }
                if ($check_status == true) {
                    // check images from merchant if not found then search in super admin
                    $image = $nevigation->image;
                    //                        !empty($nevigation->image) ? get_image($nevigation->image, 'drawericons', $request_data['merchant_id'], true) :
                    //                        get_image($nevigation->AppNavigationDrawer->image, 'drawer_icon', null, false);
                    $url = !empty($nevigation->additional_data) ? $nevigation->additional_data : "";
                    $arr_nevigation[] = array(
                        'id' => $nevigation['id'],
                        'image' => $image,
                        'sequence' => $nevigation['sequence'],
                        'name' => $nevigation['name'],
                        'url' => $url,
                        'slug' => $nevigation['slug'],
                        'text_colour' => $nevigation['text_colour'],
                        'text_style' => $nevigation['text_style'],
                    );
                }
            }
        }
        $banners = [];
        //        if (isset($this->advertisement_module) && $this->advertisement_module == 1) {
        //            $banner_for = explode(',', $this->advertisement_banner);
        //            if (in_array(1, $banner_for)) {
        //                $current_date = date('Y-m-d');
        //                $add_banners = AdvertisementBanner::where([['merchant_id', '=', $this->id], ['status', '=', 1], ['activate_date', '<=', $current_date], ['is_deleted', '=', null]])->whereIn('banner_for', [1, 4])->get();
        //                if (!empty($add_banners)) {
        //                    foreach ($add_banners as $add_banner) {
        //                        $banner_button = false;
        //                        $banner_redirect_url = "";
        //                        if ($add_banner->redirect_url != '') {
        //                            $banner_button = true;
        //                            $banner_redirect_url = $add_banner->redirect_url;
        //                        }
        //                        if ($add_banner->validity == 1) {
        //                            array_push($banners, array(
        //                                'banner_image' => get_image($add_banner->image, 'banners', $this->id),
        //                                'banner_button' => $banner_button,
        //                                'banner_redirect_url' => $banner_redirect_url
        //                            ));
        //                        } elseif ($add_banner->validity == 2 && $add_banner->expire_date >= $current_date) {
        //                            array_push($banners, array(
        //                                'banner_image' => get_image($add_banner->image, 'banners', $this->id),
        //                                'banner_button' => $banner_button,
        //                                'banner_redirect_url' => $banner_redirect_url
        //                            ));
        //                        }
        //                    }
        //                }
        //            }
        //        }
        $otp_enable = false;
        if (isset($this->Configuration->user_login_with_otp) && $this->Configuration->user_login_with_otp == 1) {
            $SmsConfiguration = SmsConfiguration::where([['merchant_id', '=', $this->id]])->first();
            if (!empty($SmsConfiguration) || $this->ApplicationConfiguration->otp_from_firebase == 1) {
                $otp_enable = true;
            }
        }
        $user_signup_card_store_enable = false;
        if (isset($this->Configuration->user_signup_card_store_enable) && $this->Configuration->user_signup_card_store_enable == 1) {
            $user_signup_card_store_enable = true;
        }

        $fare_policy_text = '';
        $fare_policy = MerchantFarePolicy::select('fare_policy')->where([['merchant_id', '=', $this->id], ['locale', '=', App::getLocale()]])->first();
        if (isset($fare_policy->fare_policy)) {
            $fare_policy_text = $fare_policy->fare_policy;
        } else {
            $fare_policy = MerchantFarePolicy::select('fare_policy')->where([['merchant_id', '=', $this->id], ['locale', '=', 'en']])->first();
            if (isset($fare_policy->fare_policy)) {
                $fare_policy_text = $fare_policy->fare_policy;
            }
        }
        $payment_option_list = $this->PaymentOption;
        $payment_option_list_slugs = [];
        if(isset($this->Configuration->countrywise_payment_gateway) && $this->Configuration->countrywise_payment_gateway == 1 && isset($request_data['user_id']) && !empty($request_data['user_id'])){
            // dd(in_array($request_data['user_id'],$this->User->pluck('id')->toArray()),$this->User->pluck('id')->toArray());
            $merchantUserIds = $this->User->pluck('id')->toArray();
            if(in_array($request_data['user_id'],$merchantUserIds)){
                $user = User::find($request_data['user_id']);
                $country = Country::find($user->country_id);
                $payment_option_list = $this->PaymentOption->whereIn("id", $country->paymentoption->pluck('payment_option_id')->toArray())->values();
                if(count($payment_option_list) == 0){
                    $payment_option_list = $this->PaymentOption;
                }
                // dd($payment_option_list,$country->paymentoption,$payment_option_list,$country->paymentoption->pluck('payment_option_id')->toArray());
                $payment_option_list_slugs = $payment_option_list->pluck("slug")->toArray();
            }else{
                return trans("$string_file.not_valid_user");
            }
           
        }
        else if (isset($this->logged_user_id) && !empty($this->logged_user_id)) {
            $having_payment_methods = $this->PaymentMethod->whereIn("id", [2, 4]);
            if (count($having_payment_methods) >= 1) {
                $user = User::find($this->logged_user_id);
                $country = Country::find($user->country_id);
                if (isset($country->payment_option_ids) && !empty($country->payment_option_ids)) {
                    $payment_option_list = $this->PaymentOption->whereIn("id", explode(",", $country->payment_option_ids))->values();
                    $payment_option_list_slugs = $payment_option_list->pluck("slug")->toArray();
                }
            }
        }
        $arr_payment_option = CommonController::filteredPaymentOptions($payment_option_list, $this->id,1,$this);
        if ($this->id == 976) {
            $arr_payment_option = array_values(array_filter($arr_payment_option->toArray(), function ($option) {
                return $option['slug'] !== 'MPESA';
            }));
        }
        // dd($arr_payment_option,$payment_option_list);
//        foreach ($arr_payment_option as $option)
//        {
//            if($option['slug'] == "OZOH")
//            {
//                $arr_details =  json_decode($option['params'],true);
//                $arr_details['payment_redirect_url'] = route('api.ozo-payment-success');
//                $arr_details['callback_url'] = route('api.ozo-payment-notification');
//                $updated_details = json_encode($arr_details);
//                $option['params'] = $updated_details;
//            }
//            elseif($option['slug'] == "MaxiCash")
//            {
//                $arr_details =  json_decode($option['params'],true);
//                $arr_details['success_url'] = route('api.maxi-cash-success');
//                $arr_details['cancel_url'] = route('api.maxi-cash-cancel');
//                $arr_details['failure_url'] = route('api.maxi-cash-failure');
//                $arr_details['notify_url'] = route('api.maxi-cash-notification');
//                $updated_details = json_encode($arr_details);
//                $option['params'] = $updated_details;
//            }
//        }
        $card_option = PaymentOption::whereHas('PaymentOptionsConfiguration', function ($q) {
            $q->where('merchant_id', $this->id);
        })->where(function ($q) use ($payment_option_list_slugs) {
            if (!empty($payment_option_list_slugs)) {
                $q->whereIn("slug", $payment_option_list_slugs);
            }
        })->get()->pluck('slug')->toArray();
        $add_card_option = !empty($card_option) ? $card_option[0] : "";

        $handyman_apply_promocode = $this->merchantHandymanPromocode($this->id);

        $account_types = [];
        if (!empty($this->AccountType)) {
            foreach ($this->AccountType as $account_type) {
                array_push($account_types, array(
                    'id' => $account_type->id,
                    'title' => $account_type->Name
                ));
            }
        }

        $only_cash = false;
        $id = NULL;
        $name = "";
        if ($this->PaymentMethod->count() == 1 && in_array(1, array_pluck($this->PaymentMethod, 'id'))) {
            $only_cash = true;
            $id = 1;
            $paymentMethod = isset($this->PaymentMethod[0]) ? $this->PaymentMethod[0] : NULL;
            $name = $paymentMethod->MethodName($this->id) ? $paymentMethod->MethodName($this->id) : $paymentMethod->payment_method;
        }

        $payment_method_list = [];
//        foreach ($this->PaymentMethod as $paymentMethod) {
//            $merchantPaymentMethod = $paymentMethod->Merchant->where('id',$this->id);
//            $merchantPaymentMethod = collect($merchantPaymentMethod->values());
//            if(isset($merchantPaymentMethod) && !empty($merchantPaymentMethod[0]->pivot['icon'])) {
//                $icon = get_image($merchantPaymentMethod[0]->pivot['icon'],'p_icon',$this->id);
//            }else{
//                $icon = get_image($paymentMethod->payment_icon,'payment_icon',$this->id,false);
//            }
//            $payment_method_list[] = [
//                'id' => $paymentMethod->id,
//                'name' => $paymentMethod->MethodName($this->id) ? $paymentMethod->MethodName($this->id) : $paymentMethod->payment_method,
//                'slug' => !empty($paymentMethod->slug) ? $paymentMethod->slug : "",
//                'icon' => $icon
//            ];
//        }

        $arr_intro_screen = $this->ApplicationTheme->user_intro_screen ? json_decode($this->ApplicationTheme->user_intro_screen, true) : [];
        $arr_intro_text = $this->ApplicationTheme->UserIntroText ? json_decode($this->ApplicationTheme->UserIntroText, true): [];
        foreach ($arr_intro_screen as $key => $intro) {
            $arr_intro_screen[$key] = [
                'text' => isset($arr_intro_text[$key]) ? $arr_intro_text[$key]['text'] : $intro['text'],
                'image' => get_image($intro['image'], 'user_app_theme', $this->id),
            ];
        }

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


        $dynamic_navigation_drawer = [];
        $dynamic_navigation_drawer_enable = false;
        if(isset($this->Configuration->dynamic_navigation_drawer) && $this->Configuration->dynamic_navigation_drawer == 1){
            $dynamic_navigation_drawer_enable = true;
            $dynamic_navigation_drawer = CommonController::getNavigationMenu($this->id);
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
        
        $in_app_call_config = InAppCallingConfigurations::where('merchant_id', $this->id)->first();
        return [
            'navigation_drawer' => $arr_nevigation,
            'dynamic_navigation_drawer_enable' => $dynamic_navigation_drawer_enable,
            'dynamic_navigation_drawer' => $dynamic_navigation_drawer,
            'register' => [
                'smoker' => $this->ApplicationConfiguration->smoker == 1 ? true : false,
                'email' => $this->ApplicationConfiguration->user_email == 1 ? true : false,
                'user_email_visibility' => isset($this->ApplicationConfiguration->user_email_visibility) && $this->ApplicationConfiguration->user_email_visibility == 1 ? true : false,
                'user_email_otp' => $this->ApplicationConfiguration->user_email_otp == 1 ? true : false,
                'phone' => $this->ApplicationConfiguration->user_phone == 1 ? true : false,
                'user_phone_otp' => $this->ApplicationConfiguration->user_phone_otp == 1 ? true : false,
                'gender' => $this->ApplicationConfiguration->gender == 1 ? true : false,
                'userImage_enable' => $this->ApplicationConfiguration->userImage_enable == 1 ? true : false,
            ],
            'ride_later' => [
                'ride_later_hours' => $this->BookingConfiguration->normal_ride_later_booking_hours ? (string)($this->BookingConfiguration->normal_ride_later_booking_hours * 60) : "",
                'outstation_time' => $this->BookingConfiguration->outstation_booking_hours ? (string)($this->BookingConfiguration->outstation_booking_hours * 60) : "",
                'rental_ride_later_hours' => $this->BookingConfiguration->rental_ride_later_booking_hours ? (string)($this->BookingConfiguration->rental_ride_later_booking_hours * 60) : "",
                'transfer_ride_later_hours' => $this->BookingConfiguration->transfer_ride_later_booking_hours ? (string)($this->BookingConfiguration->transfer_ride_later_booking_hours * 60) : "",
                'ride_later_max_num_days' => $this->BookingConfiguration->ride_later_max_num_days ? (string)($this->BookingConfiguration->ride_later_max_num_days) : '',
            ],
            'app_version' => [
                'show_dialog' => $version_show_dialog,
                "mandatory" => $version_mandatory,
                "dialog_message" => $version_dialog_message,
                "ios_user_appid" => isset($this->Application->ios_user_appid) ? (string)($this->Application->ios_user_appid) : '',
                "ios_user_link" => isset($this->Application->ios_user_link) ? (string)($this->Application->ios_user_link) : '',
            ],
            'app_maintainance' => [
                'show_dialog' => $main_show_dialog,
                'show_message' => $main_show_message
            ],
            'languages' => $this->Language,
            'countries' => $countries,
            'ride_config' => [
                'ride_button_now_text' => trans("$string_file.ride_now"),
                'ride_button_later_text' => trans("$string_file.ride_later"),
                'gender_matching' => $this->ApplicationConfiguration->gender == 1 ? true : false,
                'category_vehicle_type_module' => $this->ApplicationConfiguration->home_screen_view == 1 ? true : false,
//                'home_screen_view' => $this->ApplicationConfiguration->home_screen_view ? $this->ApplicationConfiguration->home_screen_view : 2,
                'multiple_rides' => $this->BookingConfiguration->multiple_rides == 1 ? true : false,
                'add_note' => $this->BookingConfiguration->additional_note == 1 ? true : false,
                'outstation_ride_now_enabled' => $this->BookingConfiguration->outstation_ride_now_enabled == 1 ? true : false,
                'multi_destination' => $this->BookingConfiguration->multi_destination == 1 ? true : false,
                'total_distination' => (int)$this->BookingConfiguration->count_multi_destination ? $this->BookingConfiguration->count_multi_destination : 3,
                'normal' => array(
                    'drop_location' => array(
                        'ride_now' => ($this->BookingConfiguration->normal_ride_now_drop_location == 1 || $this->BookingConfiguration->final_bill_calculation == 2) ? true : false,
                        'ride_later' => ($this->BookingConfiguration->normal_ride_later_drop_location == 1 || $this->BookingConfiguration->final_bill_calculation == 2) ? true : false,
                    ),
                ),
                'rental' => array(
                    'drop_location' => array(
                        'ride_now' => ($this->BookingConfiguration->rental_ride_now_drop_location == 1 || $this->BookingConfiguration->final_bill_calculation == 2) ? true : false,
                        'ride_later' => ($this->BookingConfiguration->rental_ride_later_drop_location == 1 || $this->BookingConfiguration->final_bill_calculation == 2) ? true : false,
                    ),
                ),
                'pickup_color' => $this->ApplicationConfiguration->pickup_color,
                'dropoff_color' => $this->ApplicationConfiguration->dropoff_color,
                'booking_eta' => $this->BookingConfiguration->booking_eta == 1 ? true : false,
//                'drop_location_request' => $this->BookingConfiguration->drop_location_request == 1 ? true : false, // Not used on app
                'change_payment_method' => $this->BookingConfiguration->change_payment_method == 1 ? true : false,
                'drop_outside_area' => $this->Configuration->drop_outside_area == 1 ? true : false,
                'user_request_timeout' => $this->BookingConfiguration->user_request_timeout,
                'home_screen_view' => $this->ApplicationConfiguration->home_screen_view, //this is added for service view, category view or bolt ui
                'delivery_app_theme' => isset($this->ApplicationConfiguration->delivery_app_theme) && $this->ApplicationConfiguration->delivery_app_theme != 1 ? $this->ApplicationConfiguration->delivery_app_theme : 1,
                'category_delivery_vehicle_type_module' => $this->ApplicationConfiguration->delivery_home_screen_view == 1 ? true : false
            ],
            'general_config' => [
                'corporate_enable' => $this->Configuration->corporate_admin == 1 ? true : false,
                'additional_info' => $additional_info,
                'chat' => $this->BookingConfiguration->chat == 1 ? true : false,
                'googleKey' => $googleKey ? $googleKey : "",
                'mapBoxKey' => $mapboxKey ? $mapboxKey : "",
                'hereMapKey' => $heremapKey ? $heremapKey : "",
                'favourite_driver_module' => $this->ApplicationConfiguration->favourite_driver_module == 1 ? true : false,
                'static_map' => $this->BookingConfiguration->static_map == 1,
                //                'wallet' => in_array(3, array_pluck($this->PaymentMethod, 'id')) ? true : false,
                'wallet' => $this->Configuration->user_wallet_status == 1 ? true : false,
                'demo' => $this->Configuration->demo == 1 ? true : false,
                'demo_login_message' => "Please make sure you have loggedin with demo driver as well",
                'homescreen_estimate_fare' => $this->Configuration->homescreen_estimate_fare == 1 ? true : false,
                'default_language' => $this->ApplicationConfiguration->user_default_language,
                'card' => in_array(2, array_pluck($this->PaymentMethod, 'id')) ? true : false,
                'splash_screen' => $this->BusinessName,
                'user_wallet_package' => json_decode($this->Configuration->user_wallet_amount),
                'vehicle_rating_enable' => $this->ApplicationConfiguration->vehicle_rating_enable == 1 ? true : false,
                'security_question' => $this->ApplicationConfiguration->security_question == 1 ? true : false,
                'security_questions' => $this->ApplicationConfiguration->security_question == 1 ? $this->Question : [],
                'tip_enable' => $this->ApplicationConfiguration->tip_status == 1 ? true : false,
                'user_document' => $this->ApplicationConfiguration->user_document == 1 ? true : false,
                'sur_charge' => $this->ApplicationConfiguration->sub_charge == 1 ? true : false,
                'emergency_contact' => $this->ApplicationConfiguration->sos_user_driver == 1 ? true : false,
                'show_logo_main' => isset($this->ApplicationConfiguration->show_logo_main) && $this->ApplicationConfiguration->show_logo_main == 1 ? true : false,
                'logo_main' => isset($this->ApplicationConfiguration->show_logo_main) && $this->ApplicationConfiguration->show_logo_main == 1 ? get_image($this->ApplicationConfiguration->logo_main, "business_logo", $this->ApplicationConfiguration->merchant_id) : "",
                'autocomplete_start' => (int)$this->BookingConfiguration->autocomplete_start,
                'baby_seat_enable' => $this->BookingConfiguration->baby_seat_enable == 1 ? true : false,
                'no_of_person' => $this->Configuration->no_of_person == 1 ? true : false,
                'no_of_children' => $this->Configuration->no_of_children == 1 ? true : false,
                'no_of_bags' => $this->Configuration->no_of_bags == 1 ? true : false,
                'no_of_pats' => $this->Configuration->no_of_pats == 1 ? true : false,
                'wheel_chair_enable' => $this->BookingConfiguration->wheel_chair_enable == 1 ? true : false,
                'family_member_enable' => $this->Configuration->family_member_enable == 1 ? true : false,
                'user_number_track_screen' => $this->Configuration->user_number_track_screen == 1 ? true : false,
                'no_of_pool_seats' => $pool_seats,
                'user_cpf_number_enable' => $this->ApplicationConfiguration->user_cpf_number_enable == 1 ? true : false,
                'splash_screen_user' => $this->ApplicationConfiguration->splash_screen_user,
                'banner_image_user' => $this->ApplicationConfiguration->banner_image_user,
                'restrict_country_wise_searching' => $this->ApplicationConfiguration->restrict_country_wise_searching == 1 ? true : false,
                'otp_from_firebase' => $this->ApplicationConfiguration->otp_from_firebase == 1 ? true : false,
                'vehicle_ac_enable' => $this->Configuration->vehicle_ac_enable == 1 ? true : false,
                'network_code_visibility' => $this->Configuration->network_code_visibility == 1 ? true : false,
                'referral_code_enable' => isset($this->Configuration->referral_code_enable) && $this->Configuration->referral_code_enable == 1 ? true : false,
                'referral_code_mandatory_user_signup' => $this->Configuration->referral_code_mandatory_user_signup == 1 ? true : false,
                'push_notification' => isset($this->Configuration->push_notification_provider) ? $this->Configuration->push_notification_provider : 1,
                'advance_payment_of_min_bill' => !empty($this->HandymanConfiguration->advance_payment_of_min_bill) ? true : false,
                'handyman_category_view' => isset($this->HandymanConfiguration->category_view_enable) && $this->HandymanConfiguration->category_view_enable == 1 ? true : false,
                'segment_per_raw' => !empty($this->ApplicationConfiguration->segment_per_raw) ? $this->ApplicationConfiguration->segment_per_raw : 4,
                'lat_long_storing_at' => isset($this->Configuration->lat_long_storing_at) ? $this->Configuration->lat_long_storing_at : 1, //1 means in driver table of same db
                'handyman_apply_promocode' => $handyman_apply_promocode,
                'polyline' => $this->BookingConfiguration->polyline == 1 ? true : false,
                'guest_user' => $this->Configuration->guest_user == 1 ? true : false,
                'transfer_money_enable' => $this->Configuration->transfer_money_enable == 1 ? true : false,
                'user_face_recognition' => $this->ApplicationConfiguration->user_face_recognition == 1 ? true : false,
                'payment_image'=> $payment_image,
                'countrywise_payment_gateway'=> $this->Configuration->countrywise_payment_gateway == 1 ? true : false,
                'ordering_for_someone_configuration'=> $this->ApplicationConfiguration->order_someone_else_config == 1 ? true : false,
                'encrypt_decrypt_configuration'=> $this->Configuration->encrypt_decrypt_enable == 1 ? true : false,
                'encrypt_decrypt_enable'=> $this->Configuration->encrypt_decrypt_enable == 1 ? true : false,
                'offer_amount_changes'=> isset($this->Configuration->offer_amount_changes) ? (string) $this->Configuration->offer_amount_changes : "10",
                'encrypt_decrypt_secret_key'=> 'p9Nf8@xLqzB1w@Kv3rjY@5Tg4D2H@7VbXs6C',
                'encrypt_decrypt_iv_key'=> '1a2b3@c4d5e6@f7890',
                'subarea_code_enable'=> ($this->Configuration->sub_area_code_enable == 1) ? 1 : (($this->Configuration->sub_area_code_enable == 3) ? 3 : 1),
                'user_app_image_config' => $this->ApplicationConfiguration->user_app_image_config == 1 ? true : false,
                'map_theme_select' => $this->Configuration->map_theme_select,
                'grocery_product_search'=>$this->ApplicationConfiguration->grocery_product_search == 1 ? true : false,
                'in_drive_new_design'=>$this->ApplicationConfiguration->in_drive_app_design == 1 ? true : false,
                'tracking_zoom_in_out'=>$this->ApplicationConfiguration->tracking_zoom_in_out == 1 ? true : false,
                'store_veg_nonveg_visiblity' => $this->ApplicationConfiguration->store_veg_nonveg_visiblity == 1 ? true : false,
                'proof_of_delivery' => $this->ApplicationConfiguration->proof_of_delivery,
                'user_subscription_package' => $this->ApplicationConfiguration->user_subscription_package == 1 ? true : false,
                'business_segment_search' => $this->ApplicationConfiguration->business_segment_search,
                'delivery_product_pricing' => $this->Configuration->delivery_product_pricing == 1 ? true : false,
                'plus_sign_on_address' => $this->Configuration->plus_sign_on_address == 1 ? true : false,
                'input_comma_in_app'=> $this->ApplicationConfiguration->enable_input_format_in_app,   //input comma in app
                'login_signup_ui'=> (string)$this->ApplicationConfiguration->login_signup_ui,
                'is_decimal_rating' => $this->Configuration->is_decimal_rating == 1 ? true : false,
                'user_wallet_add_money'=> $this->Configuration->user_wallet_add_money == 1 ? true : false,
                'full_screen_intro'=> isset($this->Configuration->full_screen_intro) && $this->Configuration->full_screen_intro == 1 ? true : false,
                'tracking_api_foreground_background'=> isset($this->Configuration->tracking_api_foreground_background) && $this->Configuration->tracking_api_foreground_background == 1 ? true : false,
                'business_segment_tax_inclusive'=> isset($this->ApplicationConfiguration->business_segment_tax_inclusive) &&  $this->ApplicationConfiguration->business_segment_tax_inclusive == 1 ? true : false,
                'additional_info_in_grocery' => isset($this->ApplicationConfiguration->additional_info_in_grocery) ? $this->ApplicationConfiguration->additional_info_in_grocery == 1 : false,
                'wallet_decimal_enable' => isset($this->ApplicationConfiguration->wallet_decimal_enable) ? $this->ApplicationConfiguration->wallet_decimal_enable == 1 : false,
                'handyman_services_per_row' => isset($this->ApplicationConfiguration->handyman_services_per_row) ? $this->ApplicationConfiguration->handyman_services_per_row : 0,
                'password_length_for_app' => isset($this->ApplicationConfiguration->password_length_for_app) ? $this->ApplicationConfiguration->password_length_for_app : 8,
                'return_trip_on_taxi_module' => isset($this->ApplicationConfiguration->return_trip_on_taxi_module) ? $this->ApplicationConfiguration->return_trip_on_taxi_module == 1 : false
            ],
            'handyman_bidding' => array(
                'module_type' => $module_type,
                'user_bid_enable' => $this->Configuration->handyman_bidding_module_user_bid_enable == 1 ? true : false,
                'handyman_manual_bidding' => $this->Configuration->handyman_manual_bidding == 1 ? true : false,
            ),
            'login' => array(
                'email' => $this->ApplicationConfiguration->user_login == 'EMAIL' ? true : false,
                'phone' => $this->ApplicationConfiguration->user_login == 'PHONE' ? true : false,
                'otp' => $otp_enable,
                'skip_login' => $this->Configuration->skip_login == 1 ? true : false,
                'ignore_login' => $this->Configuration->ignore_login == 1 ? true : false, // Move to homescreen without login screen
            ),
            'customer_support' => [
                "mail" => $mail,
                "phone" => $phone
            ],
            'social' => [
                'enable' => $this->Configuration->social_signup == 1 ? true : false,
                'google' => $this->Configuration->google == 1 ? true : false,
                'google_signup_key' => $googleSignUp ? $googleSignUp : "",
                'facebook' => $this->Configuration->facebook == 1 ? true : false,
                'facebook_signup_key' => $fbSignUp ? $fbSignUp : "",
            ],
            "paymentOption" => is_array($arr_payment_option)? $arr_payment_option : array_values($arr_payment_option->toArray()),
            'theme_cofig' => [
                'primary_color_user' => $this->ApplicationTheme->primary_color_user ? $this->ApplicationTheme->primary_color_user : "#E33A45",
                'user_app_logo' => $this->ApplicationTheme->user_app_logo ? get_image($this->ApplicationTheme->user_app_logo, 'user_app_theme', $this->id) : "",
                'user_intro_screen' => $arr_intro_screen,
                'chat_button_color' => $this->ApplicationTheme->chat_button_color,
                'share_button_color' => $this->ApplicationTheme->share_button_color,
                'call_button_color' => $this->ApplicationTheme->call_button_color,
                'cancel_button_color' => $this->ApplicationTheme->cancel_button_color,
                'font_config' => $this->ApplicationTheme->font_config == 1 ? true : false,
                'font_size' => !empty($this->ApplicationTheme->font_size) ? json_decode($this->ApplicationTheme->font_size,true) : (object)[],
                'font_family' => !empty($this->ApplicationTheme->font_family) ? $this->ApplicationTheme->font_family : ""
            ],
            'business_logo' => get_image($this->BusinessLogo, 'business_logo', $this->id),
            'advertise_banner' => $banners,
            'advertise_banner_visibility' => isset($this->advertisement_module) ? true : false,
            'additional_information' => getAdditionalInfo(),
            'user_signup_card_store' => $user_signup_card_store_enable,
            'user_card' => $this->user_card,
            'fare_policy_text' => $fare_policy_text,
            'add_card_option' => $add_card_option,
            'account_types' => $account_types,
            'accept_mobile_number_without_zero' => $this->Configuration->accept_mobile_number_without_zero == 1 ? true : false,
            'payment_method' => [
                'only_cash' => $only_cash,
                'name' => $name,
                'id' => $id,
            ],
//            'arr_payment_method' => $payment_method_list,
            'user_tip_package' => json_decode($this->ApplicationConfiguration->tip_short_amount),
            'payment_option_exist' => !empty($this->PaymentOption) && $this->PaymentOption->count() > 0 ? true : false,
            'key_data' => [
                'user_android_key' => $androidKey,
                'user_ios_key' => $iosKey,
            ],
            'map_load_from' => $this->BookingConfiguration->ios_map_load_from,
            "enable_store_user_chat" => $this->Configuration->enable_store_user_chat,
            "app_isocode_list" => isset($this->Configuration->app_isocode_list) && $this->Configuration->app_isocode_list == 1 ? true : false,
            "admin_country_list" => isset($this->ApplicationConfiguration->admin_country_list) && $this->ApplicationConfiguration->admin_country_list == 1 ? true : false,
            'app_auto_cashout' => $this->ApplicationConfiguration->app_auto_cashout == 1 ? true : false,
            'mileage_reward' => $this->ApplicationConfiguration->mileage_reward == 1 ? true : false,
            'referral_autofill' => $this->Configuration->referral_autofill == 1 ? true : false,
            'app_loading_bar' => $this->ApplicationConfiguration->app_loading_bar,
            'show_single_segment_home_screen' => $this->ApplicationConfiguration->show_single_segment_home_screen == 1 ? true : false,
            'rate_us_user_driver' => $this->ApplicationConfiguration->rate_us_user_driver == 1 ? true : false,
            'in_drive_enable' => isset($this->BookingConfiguration->in_drive_enable) && $this->BookingConfiguration->in_drive_enable == 1 ? true :false,
            'cell_layout' => $this->ApplicationConfiguration->cell_layout,
            'pickup_map_marker' => !empty($this->MapMarker) ? explode_image_path($this->MapMarker->pickup_map_marker) : '',
            'drop_map_marker' => !empty($this->MapMarker) ? explode_image_path($this->MapMarker->drop_map_marker) : '',
            'apple_pay_enable' => isset($this->Configuration->apple_pay_enable) && $this->Configuration->apple_pay_enable == 1 ? true : false,
            'price_format' => $this->Configuration->format_price,
            'in_app_call_active' => ($this->Configuration->in_app_call == 1) ? true : false,
            'in_app_call_config' => [
                "provider_slug" => !empty($in_app_call_config)? $in_app_call_config->provider_slug: "",
                "api_key" => !empty($in_app_call_config)? $in_app_call_config->api_key: "",
                "api_secret" => !empty($in_app_call_config)? $in_app_call_config->api_secret: "",
                "auth_token" => !empty($in_app_call_config)? $in_app_call_config->auth_token: "",
                "calling_number" => !empty($in_app_call_config)? $in_app_call_config->calling_number: "",
            ],
            'handyman_clubbing' => ($this->ApplicationConfiguration->handyman_clubbing == 1) ? true: false,
            'fare_breakup_view'=> ($this->ApplicationConfiguration->fare_breakup_view == 1) ? true: false,
            'contactless_delivery'=>($this->ApplicationConfiguration->contactless_delivery == 1)? true: false,
            'new_sos_enable'=>($this->ApplicationConfiguration->new_sos_enable == 1)? true: false,
            'bus_booking_package_delivery'=>($this->ApplicationConfiguration->bus_booking_package_delivery == 1)? true: false,
            'kin_person_details_on_signup' => ($this->Configuration->kin_person_details_on_signup == 1)? true: false,
            'handyman_tax_calculation_flow' => $this->Configuration->tax_calculation_flow,
            'rating_emoji_enable'=> $this->ApplicationConfiguration->rating_emoji_enable == 1,
            'vehicle_make_text' => $this->ApplicationConfiguration->vehicle_make_text == 1 ? true : false,
            'is_whatsapp_calling'=> isset($this->Configuration->is_whatsapp_calling) && $this->Configuration->is_whatsapp_calling == 1 ? true : false,
            'vehicle_model_text' => $this->ApplicationConfiguration->vehicle_model_text == 1 ? true : false,
            'user_cars_update_with_time'  => $this->ApplicationConfiguration->user_cars_update_with_time == 1 ? true : false,
            'local_citizen_foreigner_documents' => $this->ApplicationConfiguration->local_citizen_foreigner_documents == 1 ? [['id' => 1, 'name' => 'Local'], ['id' => 2, 'name' => "Foreigner"]] : [],
            'time_format'=> $this->Configuration->time_format,
            'product_subscription_package_enable' => $subscription_enabled,
            'account_delete_cancel_reason'=> $cancelReason
        ];
        
    }
    //    public function with($data)
    //    {
    //        return [
    //            'result' => "1",
    //            'message' => trans('api.appconfig'),
    //        ];
    //    }
}
