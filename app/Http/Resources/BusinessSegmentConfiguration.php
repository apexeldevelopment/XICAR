<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 24/11/23
 * Time: 10:29 AM
 */

namespace App\Http\Resources;

use App\Http\Controllers\Helper\CommonController;
use App\Models\Country;
use App\Traits\MerchantTrait;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Controllers\Helper\Merchant;
use App\Models\CmsPage;

class BusinessSegmentConfiguration extends JsonResource
{
    use MerchantTrait;

    public function toArray($data)
    {
        if (request()->call_from == "ANDROID") {
            $main_show_dialog = $this->Configuration->android_store_app_maintenance_mode == 1 ? true : false;
            $main_show_message = $this->Configuration->android_store_app_maintenance_mode == 1 ? trans('api.message56') : "";
            $version_show_dialog = $this->Configuration->android_store_app_version > request()->apk_version ? true : false;
            $version_mandatory = $this->Configuration->android_store_app_mandatory_update == 1 ? true : false;
            $version_dialog_message = $version_show_dialog == true ? trans("Update Your Android App") : '';
        } else {
            $main_show_dialog = $this->Configuration->ios_store_app_maintenance_mode == 1 ? true : false;
            $main_show_message = $this->Configuration->ios_store_app_maintenance_mode == 1 ? trans('api.message56') : "";
            $version_show_dialog = $this->Configuration->ios_store_app_version > request()->apk_version ? true : false;
            $version_mandatory = $this->Configuration->ios_store_app_mandatory_update == 1 ? true : false;
            $version_dialog_message = $version_show_dialog == true ? trans("Update Your iOS App") : '';
        }
        $string_file = $this->getStringFile(NULL, $this);
        $merchant_obj = new Merchant;
        $countries = $merchant_obj->CountryList($this);
        
        $arr_segment_services = $this->getMerchantSegmentServices($this->id, '', 1,[],NULL,false,[],2);
        $segmentData = array_map(function($item) {
           return [
                'id' => $item['segment_id'],
                'name' => $item['slag']
            ];
        }, $arr_segment_services);
        $payment_option_list = $this->PaymentOption;
        $arr_payment_option = CommonController::filteredPaymentOptions($payment_option_list, $this->id,1,$this);


        $account_types = [];
        if (!empty($this->AccountType)) {
            foreach ($this->AccountType as $account_type) {
                array_push($account_types, array(
                    'id' => $account_type->id,
                    'title' => !empty($account_type->Name) ? $account_type->Name : ""
                ));
            }
        }

        $cmsPages = CmsPage::where(['application'=>3,'merchant_id'=> $this->id])->get();
        $cms_pages = [];
        $terms_and_cond_count = 0;
        if (!empty($cmsPages)) {
            foreach ($cmsPages as $cmsPage) {
                if($cmsPage->slug == "terms_and_Conditions") $terms_and_cond_count++;
                if($terms_and_cond_count > 1 && $cmsPage->slug == "terms_and_Conditions") continue;
                array_push($cms_pages, array(
                    'slug' => $cmsPage->slug,
                    'tile' => !empty($cmsPage->LanguageSingle->title)? $cmsPage->LanguageSingle->title : "",
                    'description'=> !empty($cmsPage->LanguageSingle->description)? $cmsPage->LanguageSingle->description : "",
                ));
            }
        }

        return [
            'languages' => $this->Language,
            'countries' => $countries,
            'general_config' => [
                'chat' => $this->BookingConfiguration->chat == 1 ? true : false,
                'googleKey' => $this->BookingConfiguration->google_key ? $this->BookingConfiguration->google_key : "",
                'static_map' => $this->BookingConfiguration->static_map == 1,
                'demo' => $this->Configuration->demo == 1 ? true : false,
                'default_language' => $this->ApplicationConfiguration->user_default_language,
                'emergency_contact' => $this->ApplicationConfiguration->sos_user_driver == 1 ? true : false,
                'show_logo_main' => isset($this->ApplicationConfiguration->show_logo_main) && $this->ApplicationConfiguration->show_logo_main == 1 ? true : false,
                'logo_main' => isset($this->ApplicationConfiguration->show_logo_main) && $this->ApplicationConfiguration->show_logo_main == 1 ? get_image($this->ApplicationConfiguration->logo_main, "business_logo", $this->ApplicationConfiguration->merchant_id) : "",
                'autocomplete_start' => (int)$this->BookingConfiguration->autocomplete_start,
                'push_notification' => isset($this->Configuration->push_notification_provider) ? $this->Configuration->push_notification_provider : 1,
                'lat_long_storing_at' => isset($this->Configuration->lat_long_storing_at) ? $this->Configuration->lat_long_storing_at : 1, //1 means in driver table of same db
                'polyline' => $this->BookingConfiguration->polyline == 1 ? true : false,
                'encrypt_decrypt_configuration'=> $this->Configuration->encrypt_decrypt_enable == 1 ? true : false,
                'encrypt_decrypt_enable'=> $this->Configuration->encrypt_decrypt_enable == 1 ? true : false,
                'encrypt_decrypt_secret_key'=> 'p9Nf8@xLqzB1w@Kv3rjY@5Tg4D2H@7VbXs6C',
                'encrypt_decrypt_iv_key'=> '1a2b3@c4d5e6@f7890',
                'add_money_enable_disable' => $this->Configuration->check_wallet_for_order_receiving == 1,
                "store_wallet_package" => [
                    ["amount"=> "100"],
                    ["amount"=> "100"],
                    ["amount"=> "100"],
                ],
                'business_segment_signup_enable'=> isset($this->Configuration->business_segment_signup_enable) && $this->Configuration->business_segment_signup_enable == 1
            ],
            'customer_support' => [
                "mail" => $this->Configuration->report_issue_email,
                "phone" => $this->Configuration->report_issue_phone
            ],
            'business_logo' => get_image($this->BusinessLogo, 'business_logo', $this->id),
            'account_types' => $account_types,
            'accept_mobile_number_without_zero' => $this->Configuration->accept_mobile_number_without_zero == 1 ? true : false,
            'map_load_from' => $this->BookingConfiguration->ios_map_load_from,
            "enable_store_user_chat" => $this->Configuration->enable_store_user_chat,
            "app_isocode_list" => isset($this->Configuration->app_isocode_list) && $this->Configuration->app_isocode_list == 1 ? true : false,
            'app_loading_bar' => $this->ApplicationConfiguration->app_loading_bar,
            'store_cms_pages'=> $cms_pages,
            'app_version' => [
                'show_dialog' => $version_show_dialog,
                "mandatory" => $version_mandatory,
                "dialog_message" => $version_dialog_message,
                "ios_store_appid" => isset($this->Application->store_appid_ios) ? (string)($this->Application->store_appid_ios) : '',
                "ios_store_link" => isset($this->Application->store_ios_link) ? (string)($this->Application->store_ios_link) : '',
            ],
            'app_maintainance' => [
                'show_dialog' => $main_show_dialog,
                'show_message' => $main_show_message
            ],
            "paymentOption" => array_values($arr_payment_option->toArray()),
            "segment_data"=> count($segmentData) > 0 ? $segmentData : []
        ];
    }
}
