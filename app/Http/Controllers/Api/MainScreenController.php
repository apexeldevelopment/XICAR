<?php

namespace App\Http\Controllers\Api;

use App\Models\State;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Traits\BannerTrait;
use App\Traits\ApiResponseTrait;
use App\Models\PromoCode;
use App\Models\BusinessSegment\BusinessSegment;
use App\Traits\AreaTrait;
use App\Models\Merchant;
use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\GoogleController;
use App\Models\BusinessSegment\BusinessSegmentConfigurations;
use App\Models\Outstanding;

class MainScreenController extends Controller
{
    use BannerTrait, MerchantTrait, ApiResponseTrait, AreaTrait;

    // get home screen data of food app
    public function mainScreenSegments(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $merchant = Merchant::find($merchant_id);
        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $vertical_view_text_color= "";
        $vertical_view_service_background_color= "";
        $return_data = [];
        $banner_res['cell_title'] = "BANNERS";
        $user = $request->user('api');

        // Set language for notification
        $commonObj = new CommonController();
        $commonObj->setLanguage($user->id, 1);

        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $banner_res['cell_title_text'] = trans("$string_file.banner");
        $banner_res['cell_contents'] = [];
        $recent_services['cell_title'] = "RECENTS";
        $recent_services['cell_title_text'] = trans("$string_file.recent");
        $recent_services['cell_contents'] = [];
        $merchant_services_res['cell_title'] = "ALL SERVICES";
        $merchant_services_res['cell_title_text'] = trans("$string_file.all_services");
        $merchant_services_res['cell_icon'] = ($user->Merchant->ApplicationConfiguration->handyman_clubbing == 1) ?   get_image($user->Merchant->handyman_segement_group_icon, 'segment_group_icons', $merchant_id) : "";
        $merchant_services_res['cell_contents'] = [];
        $merchant_services_res['cell_name'] = ($user->Merchant->ApplicationConfiguration->handyman_clubbing == 1) ?   !empty($user->Merchant->handyman_segement_group_name )? $user->Merchant->handyman_segement_group_name : "" : "";

        
        $merchant_services_res_v2['cell_title'] = "ALL SERVICES V2";
        $merchant_services_res_v2['cell_title_text'] = trans("$string_file.all_services_v2");
        $merchant_services_res_v2['cell_icon'] = ($user->Merchant->ApplicationConfiguration->handyman_clubbing == 1) ?   get_image($user->Merchant->handyman_segement_group_icon, 'segment_group_icons', $merchant_id) : "";
        $merchant_services_res_v2['cell_contents'] = [];
        $merchant_services_res_v2['cell_name'] = ($user->Merchant->ApplicationConfiguration->handyman_clubbing == 1) ?   !empty($user->Merchant->handyman_segement_group_name )? $user->Merchant->handyman_segement_group_name : "" : "";

        if($user->Merchant->ApplicationConfiguration->show_horizontal_services == 1){
            $merchant_services_res_horizontal['cell_title'] = "HORIZONTAL_ALL_SERVICES";
            $merchant_services_res_horizontal['cell_title_text'] = trans("$string_file.all_services");
            $merchant_services_res_horizontal['cell_contents'] = [];
        }

        $zaaou_merchant_services['cell_title'] = "ZAAOU_ALL_SERVICES";
        $zaaou_merchant_services['cell_title_text'] = trans("$string_file.all_services");
        $zaaou_merchant_services['cell_icon'] = ($user->Merchant->ApplicationConfiguration->handyman_clubbing == 1) ? get_image($user->Merchant->handyman_segement_group_icon, 'segment_group_icons', $merchant_id) : "";
        $zaaou_merchant_services['cell_contents'] = [];
        $zaaou_merchant_services['cell_name'] = ($user->Merchant->ApplicationConfiguration->handyman_clubbing == 1) ?   !empty($user->Merchant->handyman_segement_group_name )? $user->Merchant->handyman_segement_group_name : "" : "";

        $merchant_segment_count = $user->Merchant->Segment->count();
        if ($merchant_segment_count > 1 || $merchant_segment_count == 0) {
            try {
                // call area trait to get id of area
                $this->getAreaByLatLong($request, $string_file);
            } catch (\Exception $e) {
                array_push($return_data, $banner_res, $recent_services, $merchant_services_res);
                return $this->failedResponse($e->getMessage(), $return_data);
            }
        }

        try {

            $service_background_color = "#faf1ea";
            if(isset($user->Merchant->ApplicationConfiguration->zaaou_service_holder_color)){
                $service_background_color = $user->Merchant->ApplicationConfiguration->zaaou_service_holder_color;
            }

            $country_area_id = $request->area;
            $request->merge(['merchant_id' => $merchant_id, 'home_screen' => 1, 'segment_id' => NULL, 'home_screen_holder_id'=>1,'banner_for' => 1]);
            $arr_banner = $this->getMerchantBanner($request);
            $banner_res['cell_contents'] = $arr_banner->map(function ($item, $key) use ($merchant_id,$string_file) {
                $is_business_segment_open = false;
                $is_open_from_admin = false;
                $admin_text = trans("$string_file.close");
                if(!empty($item->business_segment_id) && isset($item->business_segment_id)){
                    $businessSegmentConfig = BusinessSegmentConfigurations::where('business_segment_id',  $item->business_segment_id)->first();
                    if(!empty($businessSegmentConfig)){
                        $is_open_from_admin = $businessSegmentConfig->is_open == 1 ? true : false;
                        $admin_text = $businessSegmentConfig->is_open == 1 ? trans("$string_file.open") : trans("$string_file.close");
                    }
                    date_default_timezone_set($item->BusinessSegment->CountryArea['timezone']);
                    $current_time = date('H:i');
                    $current_day = date('w');
                    $arr_open_time = json_decode($item->BusinessSegment->open_time, true);
                    $arr_close_time = json_decode($item->BusinessSegment->close_time, true);
                    $open_time = isset($arr_open_time[$current_day]) ? $arr_open_time[$current_day] : NULL;
                    $close_time = isset($arr_close_time[$current_day]) ? $arr_close_time[$current_day] : NULL;
                    if ($open_time > $close_time) {
                        $close_time_n = date('Y-m-d H:i:s', strtotime($close_time . ' +1 day'));
                    } else {
                        $close_time_n = date('Y-m-d H:i:s', strtotime($close_time));
                    }
                    $open_time_n = date('Y-m-d H:i:s', strtotime($open_time));
                    $current_time_n = date("Y-m-d H:i:s");
                    if ($open_time_n < $current_time_n && $close_time_n > $current_time_n) {
                        $is_business_segment_open = true;
                    }
                    
                }
                $image = get_image($item->banner_images, 'banners', $merchant_id);
                return array(
                    'id' => $item->id,
                    'business_segment_id' => $item->business_segment_id,
                    // 'title' => $item->banner_name,
                    'title'=> ($item->action_type == "BUSINESS SEGMENT" || $item->action_type == "SEGMENT") ? (!empty($item->Segment) ? $item->Segment->slag : NULL) : $item->banner_name,
                    'image' => $image,
                    'image_width'=> !empty($item->image_width)? $item->image_width : 1000,
                    'image_height'=>!empty($item->image_height)? $item->image_height : 500,
                    'segment_id' => $item->segment_id,
                    'segment_group_id' => !empty($item->Segment) ? $item->Segment->segment_group_id : NULL,
                    'segment_sub_group' => !empty($item->Segment) ? $item->Segment->sub_group_for_app : NULL,
                    'name' => !empty($item->Segment) ? $item->Segment->name : NULL,
                    'redirect_url' => !empty($item->redirect_url) ? $item->redirect_url : "",
                    'action'=> $item->action_type,
                    'slug'=> ($item->action_type == "BUSINESS SEGMENT" || $item->action_type == "SEGMENT") ? (!empty($item->Segment) ? $item->Segment->slag : NULL) : '',
                    'is_business_segment_open' => $is_business_segment_open,
                    'is_admin_business_segment_open'=> isset($is_open_from_admin) && !empty($is_open_from_admin) ? $is_open_from_admin : false,
                    'admin_store_text_from_admin'=>$admin_text
                );
            });
//            array_push($return_data, $banner_res);

            $app_config = $merchant->ApplicationConfiguration;
            $show_category = $app_config->home_screen_view == 1 && $user->Merchant->alias_name == "zaaou" ? true : false;

            $arr_services = $this->getMerchantSegmentServices($merchant_id, 'api', NULL, [], $country_area_id, false, [], null, 'ALL', $show_category);
            $arr_services = collect($arr_services);

            $vehicle_based_arr_services = $this->getMerchantSegmentServices($merchant_id, 'api', [1,3], [], $country_area_id, false, [], null, 'ALL', $show_category);
            $vehicle_based_arr_services = collect($vehicle_based_arr_services);

            $helper_based_arr_services = $this->getMerchantSegmentServices($merchant_id, 'api', 2, [], $country_area_id, false, [], null, 'ALL', $show_category);
            $helper_based_arr_services = collect($helper_based_arr_services);

            // add money holder on home screen
            $user = $request->user('api');
            $balance = (float) isset($user->wallet_balance) ? $user->wallet_balance : 0.00;
            $add_money = [];
            if (isset($app_config->main_screen_add_money_button) && $app_config->main_screen_add_money_button == 1) {
                $add_money['cell_title'] = "ADDMONEY";
                $add_money['cell_title_text'] = "";
                $add_money['cell_contents'][] = [
                    "id" => 74,
                    "title" => isset($user->country) ?  $user->country->iso_code.' '. $balance : "",
                    "btntext" => isset($app_config->add_wallet_money_btntext) ? $app_config->add_wallet_money_btntext : "",
                    "btncolor" => isset($app_config->add_wallet_money_btncolor) ? $app_config->add_wallet_money_btncolor : "",
                    "image" => isset($app_config->add_wallet_money_image) ? get_image($app_config->add_wallet_money_image,"business_logo",$merchant->id) : ''
                ];
//                array_push($return_data, $add_money);
            }

            $recommended_banner = [];
            if(isset($app_config->show_recommended_services) && $app_config->show_recommended_services == 1){
                $recommended_banner['cell_title'] = "RECOMMENDED_SERVICE";
                $recommended_banner['cell_title_text'] = "Recommended Service For You";
                $recommended_services = [];
                foreach($arr_services as $service_arr){
                    foreach($service_arr['arr_services'] as $service){
                        if(isset($service['service_is_recommended']) && $service['service_is_recommended'] == 1){
                            array_push($recommended_services, array(
                                "id" => $service['id'],
                                "segment_id" => $service['segment_id'],
                                "title" => $service_arr['slag'],
                                "name" => $service_arr['name'],
                                "is_coming_soon" => $service_arr['is_coming_soon'],
                                "segment_group_id" => $service_arr['segment_group_id'],
                                "segment_sub_group" => $service_arr['sub_group_for_app'],
                                "price_card_owner" => $service_arr['price_card_owner'],
                                "service_name" => $service['locale_service_name'],
                                "description" => $service['locale_service_description'],
                                "image" => !empty($service['service_icon']) ? $service['service_icon'] : get_image("1684758443_646b5fab96501_banners.jpg", 'banners', $merchant_id),
                                "dynamic_url"=> !empty($service_arr['dynamic_url']) ? $service_arr['dynamic_url'] : "",
                                "segment_background_gradient_1" => !empty($service_arr['segment_background_gradient_1']) ? $service_arr['segment_background_gradient_1'] : "",
                                "segment_background_gradient_2" => !empty($service_arr['segment_background_gradient_2']) ? $service_arr['segment_background_gradient_2'] : "",
                                "segment_home_screen_image" => !empty($service_arr['segment_home_screen_image']) ? $service_arr['segment_home_screen_image'] : "",
                            ));
                        }
                    }
                }
                $recommended_banner['cell_contents'] = $recommended_services;
//                array_push($return_data, $recommended_banner);
            }

            if ($app_config->recent_services_enable == "1") {
                $recent_services['cell_contents'] = $arr_services->map(function ($item, $key) use ($merchant_id) {
                    return array(
                        'id' => $item['segment_id'],
                        'title' => $item['slag'],
                        'is_coming_soon' => $item['is_coming_soon'],
                        'segment_group_id' => $item['segment_group_id'],
                        'segment_sub_group' => $item['sub_group_for_app'],
                        'name' => $item['name'],
                        'price_card_owner' => $item['price_card_owner'],
                        'image' => $item['segment_icon'],
                        'arr_services' => $item['arr_services'],
                        'arr_categories' => $item['arr_categories'],
                        "dynamic_url"=> !empty($item['dynamic_url']) ? $item['dynamic_url'] : ""
                    );
                });
//                array_push($return_data, $recent_services);
            }

            $merchant_services_res['cell_contents'] = $arr_services->map(function ($item, $key) use ($merchant_id) {
                $multi_store = false;
                if ($item['slag'] == 'GROCERY') {
                    $store_count = $store_count = BusinessSegment::where('merchant_id', $merchant_id)->where('segment_id', $item['segment_id'])->count();
                    $multi_store = $store_count > 1 ? true : false;
                }
                return array(
                    'id' => $item['segment_id'],
                    'title' => $item['slag'],
                    'is_coming_soon' => $item['is_coming_soon'],
                    'segment_group_id' => $item['segment_group_id'],
                    'segment_sub_group' => $item['sub_group_for_app'],
                    'price_card_owner' => $item['price_card_owner'],
                    'name' => $item['name'],
                    'multi_store' => $multi_store,
                    'image' => $item['segment_icon'],
                    'arr_services' => $item['arr_services'],
                    'arr_categories' => $item['arr_categories'],
                    "dynamic_url"=> !empty($item['dynamic_url']) ? $item['dynamic_url'] : "",
                    "segment_background_gradient_1" => !empty($item['segment_background_gradient_1']) ? $item['segment_background_gradient_1'] : "",
                    "segment_background_gradient_2" => !empty($item['segment_background_gradient_2']) ? $item['segment_background_gradient_2'] : "",
                    "segment_home_screen_image" => !empty($item['segment_home_screen_image']) ? $item['segment_home_screen_image'] : "",
                );
            });

            $merchant_services_res_v2['cell_contents'] = $arr_services->map(function ($item, $key) use ($merchant_id) {
                $multi_store = false;
                if ($item['slag'] == 'GROCERY') {
                    $store_count = $store_count = BusinessSegment::where('merchant_id', $merchant_id)->where('segment_id', $item['segment_id'])->count();
                    $multi_store = $store_count > 1 ? true : false;
                }
                return array(
                    'id' => $item['segment_id'],
                    'title' => $item['slag'],
                    'is_coming_soon' => $item['is_coming_soon'],
                    'segment_group_id' => $item['segment_group_id'],
                    'segment_sub_group' => $item['sub_group_for_app'],
                    'price_card_owner' => $item['price_card_owner'],
                    'name' => $item['name'],
                    'multi_store' => $multi_store,
                    'image' => $item['segment_icon'],
                    'arr_services' => $item['arr_services'],
                    'arr_categories' => $item['arr_categories'],
                    "dynamic_url"=> !empty($item['dynamic_url']) ? $item['dynamic_url'] : "",
                    "segment_background_gradient_1" => !empty($item['segment_background_gradient_1']) ? $item['segment_background_gradient_1'] : "",
                    "segment_background_gradient_2" => !empty($item['segment_background_gradient_2']) ? $item['segment_background_gradient_2'] : "",
                    "segment_home_screen_image" => !empty($item['segment_home_screen_image']) ? $item['segment_home_screen_image'] : "",
                );
            });
//            array_push($return_data, $merchant_services_res);

            if($user->Merchant->ApplicationConfiguration->show_horizontal_services == 1){
                $merchant_services_res_horizontal['cell_contents'] = $merchant_services_res['cell_contents'];
//                array_push($return_data, $merchant_services_res_horizontal);
            }

            foreach($merchant_services_res['cell_contents'] as $service){
                $temp = $service;
                $temp['background_color'] = $service_background_color;
                array_push($zaaou_merchant_services['cell_contents'], $temp);
            }

            $helper_based_cell_content = $helper_based_arr_services->map(function ($item, $key) use ($merchant_id, $service_background_color) {
                $multi_store = false;
                if ($item['slag'] == 'GROCERY') {
                    $store_count = $store_count = BusinessSegment::where('merchant_id', $merchant_id)->where('segment_id', $item['segment_id'])->count();
                    $multi_store = $store_count > 1 ? true : false;
                }
                return array(
                    'id' => $item['segment_id'],
                    'title' => $item['slag'],
                    'is_coming_soon' => $item['is_coming_soon'],
                    'segment_group_id' => $item['segment_group_id'],
                    'segment_sub_group' => $item['sub_group_for_app'],
                    'price_card_owner' => $item['price_card_owner'],
                    'name' => $item['name'],
                    'multi_store' => $multi_store,
                    'image' => $item['segment_icon'],
                    'arr_services' => $item['arr_services'],
                    'arr_categories' => $item['arr_categories'],
                    'background_color'=> $service_background_color,
                    "dynamic_url"=> !empty($item['dynamic_url']) ? $item['dynamic_url'] : "",
                    "segment_background_gradient_1" => !empty($item['segment_background_gradient_1']) ? $item['segment_background_gradient_1'] : "",
                    "segment_background_gradient_2" => !empty($item['segment_background_gradient_2']) ? $item['segment_background_gradient_2'] : "",
                    "segment_home_screen_image" => !empty($item['segment_home_screen_image']) ? $item['segment_home_screen_image'] : "",
                );
            });

            $vehicle_based_cell_content = $vehicle_based_arr_services->map(function ($item, $key) use ($merchant_id, $service_background_color) {
                $multi_store = false;
                if ($item['slag'] == 'GROCERY') {
                    $store_count = $store_count = BusinessSegment::where('merchant_id', $merchant_id)->where('segment_id', $item['segment_id'])->count();
                    $multi_store = $store_count > 1 ? true : false;
                }
                return array(
                    'id' => $item['segment_id'],
                    'title' => $item['slag'],
                    'is_coming_soon' => $item['is_coming_soon'],
                    'segment_group_id' => $item['segment_group_id'],
                    'segment_sub_group' => $item['sub_group_for_app'],
                    'price_card_owner' => $item['price_card_owner'],
                    'name' => $item['name'],
                    'multi_store' => $multi_store,
                    'image' => $item['segment_icon'],
                    'arr_services' => $item['arr_services'],
                    'arr_categories' => $item['arr_categories'],
                    'background_color'=> $service_background_color,
                    "dynamic_url"=> !empty($item['dynamic_url']) ? $item['dynamic_url'] : "",
                    "segment_background_gradient_1" => !empty($item['segment_background_gradient_1']) ? $item['segment_background_gradient_1'] : "",
                    "segment_background_gradient_2" => !empty($item['segment_background_gradient_2']) ? $item['segment_background_gradient_2'] : "",
                    "segment_home_screen_image" => !empty($item['segment_home_screen_image']) ? $item['segment_home_screen_image'] : "",
                );
            });

            // array_push($return_data,$banner_res,$recent_services,$merchant_services_res);

            // popular restaurants ans stores

            // popular restaurant and store data
            $popular_restaurant_res['cell_title'] = "POPULAR_RESTAURANT";
            $popular_restaurant_res['cell_title_text'] = trans("$string_file.popular_restaurants");

            $popular_store_res['cell_title'] = "POPULAR_STORE";
            $popular_store_res['cell_title_text'] = trans("$string_file.popular_stores");

            $popular_pharmacy_res['cell_title'] = "POPULAR_PHARMACY";
            $popular_pharmacy_res['cell_title_text'] = trans("$string_file.popular_pharmacy");
            $distance = $user->Merchant->BookingConfiguration->store_radius_from_user;
            $request->request->add(['distance' => $distance]);
            $arr_popular_business_segment = $this->getMerchantPopularBusinessSegment($request);
            // p($arr_popular_business_segment);

            //laundry
            $popular_laundry_res['cell_title'] = "POPULAR_LAUNDRY";
            $popular_laundry_res['cell_title_text'] = trans("$string_file.popular_laundry");


            $arr_restaurants = [];
            $arr_stores = [];
            $arr_pharmacy = [];

            $user_lat = $request->latitude;
            $user_long = $request->longitude;
            $google_key = $user->Merchant->BookingConfiguration->google_key;

            $google = new GoogleController();
            $unit = isset($user->country) ?  $user->country->distance_unit : "";
            foreach ($arr_popular_business_segment as $item) {


                $store_lat = $item->latitude;
                $store_long = $item->longitude;


                // if ($user->Merchant->demo == 1) {
                    $distance_from_user = $google->arialDistance($user_lat, $user_long, $store_lat, $store_long, $unit, $string_file, true, true);
                    // calculate distance from google direction api
//                 } else {
//                     $user_drop_location[0] = [
//                         'drop_latitude' => $user_lat,
//                         'drop_longitude' => $user_long,
//                         'drop_location' => ""
//                     ];
// //                    $distance_from_user = GoogleController::GoogleStaticImageAndDistance($store_lat, $store_long, $user_drop_location, $google_key, "", $string_file);
// //                    $distance_from_user = isset($distance_from_user['total_distance_text']) ? $distance_from_user['total_distance_text'] : "";

//                     // if api google api fails , do not throw exception
//                     try{
//                         $distance_from_user = GoogleController::GoogleStaticImageAndDistance($store_lat, $store_long, $user_drop_location, $google_key, "", $string_file);
//                         $distance_from_user = isset($distance_from_user['total_distance_text'])? $distance_from_user['total_distance_text'] : "";
//                     }
//                     catch(\Exception $e){
//                         $distance_from_user = "";
//                     }
//                 }

                // p(round($item->distance,1));
                // only setting timezone // uncommented this code because of app end requirement
                $is_open_from_admin = false;
                 $businessSegmentConfig = BusinessSegmentConfigurations::where('business_segment_id',  $item->id)->first();
                if(!empty($businessSegmentConfig)){
                        $is_open_from_admin = $businessSegmentConfig->is_open == 1 ? true : false;
                        $admin_text = $businessSegmentConfig->is_open == 1 ? trans("$string_file.open") : trans("$string_file.close");
                    }
                // only setting timezone
                date_default_timezone_set($item->CountryArea['timezone']);
                $current_time = date('H:i');
                $is_business_segment_open = false;
                $current_day = date('w');
                $arr_open_time = json_decode($item->open_time, true);
                $arr_close_time = json_decode($item->close_time, true);
                $open_time = isset($arr_open_time[$current_day]) ? $arr_open_time[$current_day] : NULL;
                $close_time = isset($arr_close_time[$current_day]) ? $arr_close_time[$current_day] : NULL;

                //  Changes for midnight store time
                if ($open_time > $close_time) {
                    $close_time_n = date('Y-m-d H:i:s', strtotime($close_time . ' +1 day'));
                } else {
                    $close_time_n = date('Y-m-d H:i:s', strtotime($close_time));
                }
                $open_time_n = date('Y-m-d H:i:s', strtotime($open_time));
                $current_time_n = date("Y-m-d H:i:s");
                if ($open_time_n < $current_time_n && $close_time_n > $current_time_n) {
                    $is_business_segment_open = true;
                }
                //                $arr_style =   $item->StyleManagement->map(function ($style) use ($merchant_id,$string_file){
                //                    return ['style_name' => $style->Name($merchant_id)];
                //                });
                //                $m_amount = $item->minimum_amount;
                //                $m_amount_for = $item->minimum_amount_for;
                $business_segment = array(
                    'business_segment_id' => $item->id,
                    'id' => $item->segment_id, // segment id of business segment
                    'name' => $item->full_name,
                    'title' => $item->Segment->slag,
                    'time' => $item->Segment->sub_group_for_app == 1 ? "$item->delivery_time " . trans("$string_file.minute") : "",
                    'distance' => $distance_from_user,
                    //                    'amount' => !empty($item->minimum_amount) ? "$m_amount" : "",
                    //                    'amount_for' => !empty($item->minimum_amount_for) ? "$m_amount_for" : "",
                    //                    'currency' => $item->Country->isoCode,
                    //                    'style' => array_pluck($arr_style,'style_name'),//array_pluck($item->StyleManagement, 'style_name'),
                    //                    'open_time' => $open_time,
                    //                    'close_time' => $close_time,
                    //                    'rating' => !empty($item->rating) ? $item->rating : "2.5",
                    'image' => get_image($item->business_logo, 'business_logo', $merchant_id),
                    'is_business_segment_open' => $is_business_segment_open,
                    'background_color'=> $service_background_color,
                    'is_admin_business_segment_open'=> isset($is_open_from_admin) && !empty($is_open_from_admin) ? $is_open_from_admin : false,
                    'admin_store_text_from_admin'=>$admin_text,
                    'is_favourite' => !empty($item->FavouriteBusinessSegment) && !empty($item->FavouriteBusinessSegment->where("user_id", $user->id)) ? true : false,
                );

                if ($item->Segment->sub_group_for_app == 1) {
                    $arr_restaurants[] = $business_segment;
                } else {
                    if ($item->Segment->slag == 'PHARMACY'){
                        $arr_pharmacy[] = $business_segment;
                    }else{
                        $arr_stores[] = $business_segment;
                    }
                }
            }

            $popular_restaurant_res['cell_contents'] = $arr_restaurants;
            $popular_store_res['cell_contents'] = $arr_stores;
            $popular_pharmacy_res['cell_contents'] = $arr_pharmacy;
//            if (count($arr_restaurants) > 0) {
//                array_push($return_data, $popular_restaurant_res);
//            }
//            if (count($arr_stores) > 0) {
//                array_push($return_data, $popular_store_res);
//            }

            //laundry
            $popular_laundry_outlets = $this->getMerchantPopularLaundryOutlet($request);
            $popular_laundry_res['cell_contents'] = $popular_laundry_outlets->map(function($item , $key) use($unit, $merchant_id, $user,$user_lat, $user_long,$google, $google_key, $string_file,$service_background_color) {

                $outlet_lat = $item->latitude;
                $outlet_long = $item->longitude;

                // if ($user->Merchant->demo == 1) {
                    $distance_from_user = $google->arialDistance($user_lat, $user_long, $outlet_lat, $outlet_long, $unit, $string_file, true, true);
                // } else {
                //     $user_drop_location[0] = [
                //         'drop_latitude' => $user_lat,
                //         'drop_longitude' => $user_long,
                //         'drop_location' => ""
                //     ];
                //     $distance_from_user = GoogleController::GoogleStaticImageAndDistance($outlet_lat, $outlet_long, $user_drop_location, $google_key, "", $string_file);
                //     $distance_from_user = $distance_from_user['total_distance_text'] ?? "";
                // }

                date_default_timezone_set($item->CountryArea['timezone']);
                $current_time = date('H:i');
                $is_outlet_open = false;
                $current_day = date('w');
                $arr_open_time = json_decode($item->open_time, true);
                $arr_close_time = json_decode($item->close_time, true);
                $open_time = $arr_open_time[$current_day] ?? NULL;
                $close_time = $arr_close_time[$current_day] ?? NULL;
                if ($open_time < $current_time && $close_time > $current_time) {
                    $is_outlet_open = true;
                } else {
                    $is_outlet_open = false;
                }

                return [
                    'laundry_outlet_id' => $item->id,
                    'id' => $item->segment_id,
                    'name' => $item->full_name,
                    'title' => $item->Segment->slag,
                    'distance' => $distance_from_user,
                    'image' => get_image($item->business_logo, 'laundry_outlet_logo', $merchant_id),
                    'is_outlet_open' => $is_outlet_open,
                    'background_color'=> $service_background_color,
                    'is_admin_outlet_open'=> true,
                ];
            });

            $bottom_banner['cell_title'] = "BOTTOM_BANNERS";
            $bottom_banner['cell_title_text'] = "Banner";
            $bottom_banner_1['cell_title'] = "BOTTOM_BANNERS";
            $bottom_banner_1['cell_title_text'] = "Banner";
            $bottom_banner_2['cell_title'] = "BOTTOM_BANNERS";
            $bottom_banner_2['cell_title_text'] = "Banner";
            // $bottom_banner['cell_contents'][] = [
            //     "id" => 96,
            //     "business_segment_id" => null,
            //     "title" => "Banner 2",
            //     "image" => get_image("1693574181_64f1e42595c6c_banners.png", 'banners', $merchant_id),
            //     "redirect_url" => ""
            // ];

//            $request->merge(['home_screen_holder_id'=>8]);
            $bottom_banner_arr = [8, 21,22];
            foreach($bottom_banner_arr as $banner_id){

                $request = $request->merge(['home_screen_holder_id'=>$banner_id]);
                $arr_banner = $this->getMerchantBanner($request);

                $bottom_banner_data = $arr_banner->map(function ($item, $key) use ($merchant_id) {

                    $image = get_image($item->banner_images, 'banners', $merchant_id);
                    return array(
                        'id' => $item->id,
                        'business_segment_id' => $item->business_segment_id,
                        'title' => $item->banner_name,
                        'image' => $image,
                        'image_width'=> !empty($item->image_width)? $item->image_width : 1000,
                        'image_height'=>!empty($item->image_height)? $item->image_height : 500,
                        'redirect_url' => !empty($item->redirect_url) ? $item->redirect_url : "",
                    );
                });

                switch($banner_id){
                    case 8:
                        $bottom_banner['cell_contents'] = $bottom_banner_data;
                        break;
                    case 21:
                        $bottom_banner_1['cell_contents'] = $bottom_banner_data;
                        break;
                    case 22:
                        $bottom_banner_2['cell_contents'] = $bottom_banner_data;
                        break;
                }
            }

            // $post_your_requirement['cell_title'] = "POST_YOUR_REQUIREMENT";
            // $post_your_requirement['cell_title_text'] = "Post Your Requirement";
            // $post_your_requirement['cell_contents'][] = [
            //     "id" => 1,
            //     "title" => "Post Your Requiement",
            //     "image" => get_image("1684758443_646b5fab96501_banners.jpg", 'banners', $merchant_id),
            // ];
            // array_push($return_data, $post_your_requirement);

            $search_holder['cell_title'] = "SEARCH";
            $search_holder['cell_title_text'] = trans("$string_file.search");
            $search_holder['cell_contents'][] = ['background_color' => "#fff"];

            $bidding_enquiry_holder['cell_title'] = "BIDDING_ENQUIRY";
            $bidding_enquiry_holder['cell_title_text'] = trans("$string_file.bidding").' '.trans("$string_file.enquiry");
            $bidding_enquiry_holder['cell_contents'] = [];

            $location_holder['cell_title'] = "LOCATION_HOLDER";
            $location_holder['cell_title_text'] = "";
            $location_holder['cell_contents'] = [];

            $homeScreenHolders = $merchant->MerchantHomeScreenHolder;
            if(!empty($homeScreenHolders->toArray())){
                foreach($homeScreenHolders as $holder){
                    if($holder->slug == 'BANNER'){
                        array_push($return_data, $banner_res);
                    }
                    if($holder->slug == 'RECENTS' && $app_config->recent_services_enable == "1"){
                        array_push($return_data, $recent_services);
                    }
                    if($holder->slug == 'ALL_SERVICES'){
                        array_push($return_data, $merchant_services_res);
                    }
                    if($holder->slug == 'ALL_SERVICES_V2'){
                        array_push($return_data, $merchant_services_res_v2);
                    }
                    if($holder->slug == 'HORIZONTAL_ALL_SERVICES' && $user->Merchant->ApplicationConfiguration->show_horizontal_services == 1){
                        array_push($return_data, $merchant_services_res_horizontal);
                    }
                    if($holder->slug == 'RECOMMENDED_SERVICE' && $app_config->show_recommended_services == 1){
                        array_push($return_data, $recommended_banner);
                    }
                    if($holder->slug == 'POPULAR_RESTAURANT' && count($arr_restaurants) > 0){
                        array_push($return_data, $popular_restaurant_res);
                    }
                    if($holder->slug == 'POPULAR_STORE' && count($arr_stores) > 0){
                        array_push($return_data, $popular_store_res);
                    }
                    if($holder->slug == 'POPULAR_STORE' && count($arr_pharmacy) > 0){
                        array_push($return_data, $popular_pharmacy_res);
                    }
                    if($holder->slug == 'BOTTOM_BANNERS'){
                        array_push($return_data, $bottom_banner);
                    }
                    if($holder->slug == 'BOTTOM_BANNER_1'){
                        array_push($return_data, $bottom_banner_1);
                    }
                    if($holder->slug == 'BOTTOM_BANNER_2'){
                        array_push($return_data, $bottom_banner_2);
                    }
                    if($holder->slug == 'ADDMONEY' && $app_config->main_screen_add_money_button == 1){
                        array_push($return_data, $add_money);
                    }
                    if($holder->slug == 'ZAAOU_ALL_SERVICES'){
                        array_push($return_data, $zaaou_merchant_services);
                    }
                    if($holder->slug == 'POPULAR_LAUNDRY'){
                        array_push($return_data, $popular_laundry_res);
                    }
                    if($holder->slug == 'HANDYMAN_ALL_SERVICES'){
                        $handyman_services['cell_title'] = "HANDYMAN_ALL_SERVICES";
                        $handyman_services['cell_title_text'] = trans("$string_file.handyman").' '.trans("$string_file.all_services");
                        $handyman_services['cell_contents'] = $helper_based_cell_content;
                        array_push($return_data, $handyman_services);
                    }
                    if($holder->slug == 'RIDEHAILING_ALL_SERVICES'){
                        $vehicle_based_services['cell_title'] = "RIDEHAILING_ALL_SERVICES";
                        $vehicle_based_services['cell_title_text'] = trans("$string_file.e_hailing").' '.trans("$string_file.services");
                        $vehicle_based_services['cell_contents'] = $vehicle_based_cell_content;
                        array_push($return_data, $vehicle_based_services);
                    }
                    if($holder->slug == 'SEARCH'){
                        array_push($return_data, $search_holder);
                    }
                    if($holder->slug == 'BIDDING_ENQUIRY'){
                        array_push($return_data, $bidding_enquiry_holder);
                    }
                    if($holder->slug == 'LOCATION_HOLDER'){
                        array_push($return_data, $location_holder);
                    }
                    
                    // @ayush (Dynamic Holders  and Vertical view holders)
                    
                    if($holder->slug == 'DYNAMIC_HOLDER'){
                        $segment_ids = $holder->HolderSegment->pluck('segment_id')->toArray();
                        $contents = isset($merchant_services_res['cell_contents'])? $merchant_services_res['cell_contents']->toArray() : [];
                        $dynamic_holder = [
                            'cell_title' => "DYNAMIC_HOLDER",
                            'cell_title_text' => $holder->name,
                            'cell_image' => get_image($holder->holder_image, "merchant", $merchant_id),
                            'cell_contents' => array_values(array_filter($contents, function ($service) use ($segment_ids) {
                                    return in_array($service['id'], $segment_ids);
                                }
                            )),
                        ];
                        array_push($return_data, $dynamic_holder);
                    }
                    
                    if($holder->slug == 'VERTICAL_VIEW_SERVICE_HOLDER'){
                        $vertical_view_service = [
                            'cell_name' => ($user->Merchant->ApplicationConfiguration->handyman_clubbing == 1) ?   !empty($user->Merchant->handyman_segement_group_name )? $user->Merchant->handyman_segement_group_name : "" : "",
                            'cell_title' => $holder->slug,
                            'cell_title_text' => "",
                            'cell_icon' => ($user->Merchant->ApplicationConfiguration->handyman_clubbing == 1) ?   get_image($user->Merchant->handyman_segement_group_icon, 'segment_group_icons', $merchant_id) : "",
                            'cell_contents' => array_map(function ($content) use ($service_background_color) {
                                return array_merge($content, [
                                    'background_color' => $service_background_color,
                                    'text_color' => "#FFFFFF"
                                ]);
                            }, $merchant_services_res['cell_contents']->toArray()) 
                        ];
                        
                        $vertical_view_text_color = "#FFFFFF";
                        $vertical_view_service_background_color = $service_background_color;
                        array_push($return_data, $vertical_view_service);
                    }

                }
//                $slugs = array_column($homeScreenHolders->toArray(),'slug');
//                if(in_array('HANDYMAN_ALL_SERVICES',$slugs) || in_array('RIDEHAILING_ALL_SERVICES',$slugs)){
                    $hid_all_services['cell_title'] = "ALL_SERVICE_HIDDEN";
                    $hid_all_services['cell_title_text'] = trans("$string_file.hidden").' '.trans("$string_file.services");
                    $hid_all_services['cell_contents'] = $merchant_services_res['cell_contents'];
                    array_push($return_data, $hid_all_services);
//                }
            }else{
                //default holder positioning
                array_push($return_data, $search_holder);
                array_push($return_data, $banner_res);
                if (isset($app_config->main_screen_add_money_button) && $app_config->main_screen_add_money_button == 1) {
                    array_push($return_data, $add_money);
                }
                if(isset($app_config->show_recommended_services) && $app_config->show_recommended_services == 1){
                    array_push($return_data, $recommended_banner);
                }
                if($app_config->recent_services_enable == "1"){
                    array_push($return_data, $recent_services);
                }
                array_push($return_data, $merchant_services_res);
                $hid_all_services['cell_title'] = "ALL_SERVICE_HIDDEN";
                $hid_all_services['cell_title_text'] = trans("$string_file.hidden").' '.trans("$string_file.services");
                $hid_all_services['cell_contents'] = $merchant_services_res['cell_contents'];
                array_push($return_data, $hid_all_services);

                if($user->Merchant->ApplicationConfiguration->show_horizontal_services == 1){
                    array_push($return_data, $merchant_services_res_horizontal);
                }
                if (count($arr_restaurants) > 0) {
                    array_push($return_data, $popular_restaurant_res);
                }
                if (count($arr_stores) > 0) {
                    array_push($return_data, $popular_store_res);
                }
                if (count($arr_pharmacy) > 0) {
                    array_push($return_data, $popular_pharmacy_res);
                }
                if ($user->Merchant->alias_name == "redaak-app") {
                    array_push($return_data, $bottom_banner);
                }
            }
            
            $outstanding = Outstanding::where("user_id", $user->id)->where("pay_status", 0)->first();
            $amt_required= 0;
            if(!empty($outstanding)){
                $wallet_balance = !empty($user->wallet_balance)? $user->wallet_balance : 0;
                $amt_required = $outstanding->amount-$wallet_balance;
            }

            $additional_data['outstanding_id']= !empty($outstanding)? (string)$outstanding->id : "";
            $additional_data['outstanding_amount']= !empty($outstanding)? (string)$outstanding->amount : "";
            $additional_data['outstanding_msg']= !empty($outstanding)? trans("$string_file.outstanding_msg").' '.(string)$outstanding->extra_charge_amount  : "";
            $additional_data['outstanding_amount_required']= (string)$amt_required;
            $additional_data['tap_customer_token']= !empty($user->tap_user_customer_token) ? (string)$user->tap_user_customer_token : "";
            $additional_data['vertical_view_text_color'] = $vertical_view_text_color;
            $additional_data['vertical_view_service_background_color'] = $vertical_view_service_background_color;

        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successfullResponse(trans("$string_file.success"), $return_data, $additional_data);
    }


    // public function getPromoCodeList(Request $request)
    // {
    //     $merchant_id = $request->merchant_id;
    //     $user = $request->user('api');
    //     $string_file = $this->getStringFile(NULL,$user->Merchant);
    //     $validator = Validator::make($request->all(), [
    //         'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')],
    //     ]);
    //     if ($validator->fails()) {
    //         $errors = $validator->messages()->all();
    //         $this->failedResponse($errors[0]);
    //     }
    //     $arr_promo_code_list = PromoCode::select('promoCode','promo_code_value','promo_code_description','order_minimum_amount','promo_code_value_type','promo_percentage_maximum_discount')->where([['deleted','=',NULL], ['merchant_id','=',$merchant_id]
    //     ])->orderBy('promo_code_value')->get()->toArray();
    //     return $this->successResponse(trans("$string_file.data_found"), $arr_promo_code_list);
    // }


    // updated code for country are wise promo code list
    public function getPromoCodeList(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $user = $request->user('api');
        $vehicle_type_id = $request->vehicle_type_id;
        // call area trait to get id of area
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $area_id = NULL;
        try{
            if (!empty($request->latitude) && !empty($request->longitude)) {
                $this->getAreaByLatLong($request, $string_file);
                $area_id = $request->area;
            }
        }
        catch(\Exception $e){
            return $this->failedResponse($e->getMessage());
        }
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            $this->failedResponse($errors[0]);
        }
        $arr_promo_code_list = [];
        if($user->Merchant->ApplicationConfiguration->promocode_list_enable == 1){
            $arr_promo_code_list = PromoCode::select('id','promoCode', 'promo_code_value', 'promo_code_description', 'order_minimum_amount', 'promo_code_value_type', 'promo_percentage_maximum_discount','promo_code_vehicle_type')->where([
                ['deleted', '=', NULL], ['merchant_id', '=', $merchant_id], ['segment_id', '=', $request->segment_id], ['promo_code_status', '=', 1]
            ])->where(function ($q) use ($area_id) {
                if (!empty($area_id)) {
                    $q->where('country_area_id', $area_id);
                }
            })->orderBy('promo_code_value')->get();
       
        if(!empty($vehicle_type_id))
        {
           
            $arr_promo_code_list = $arr_promo_code_list->filter(function($promo) use ($vehicle_type_id) {
                if (!empty($promo->promo_code_vehicle_type)) {
                    $vehicleTypeIds = json_decode($promo->promo_code_vehicle_type, true);
                    return is_array($vehicleTypeIds) && in_array($vehicle_type_id, $vehicleTypeIds);
                }
                return false; // If promo_code_vehicle_type is NULL, exclude this promo code
            })->values()->toArray();
            
        }

            return $this->successResponse(trans("$string_file.data_found"), $arr_promo_code_list);
        }else{
            return $this->failedResponse(trans("$string_file.not_found"));
        }
    }
}
