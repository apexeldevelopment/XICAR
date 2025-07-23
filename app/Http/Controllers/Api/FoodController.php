<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\HolderController;
use App\Http\Controllers\Helper\Merchant;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Controllers\Merchant\PriceCardController;
use App\Http\Controllers\PaymentMethods\Payment;
use App\Models\BusinessSegment\OrderDetail;
use App\Models\BusinessSegment\PackagingPreference;
use App\Models\BusinessSegment\ProductVariant;
use App\Models\CountryArea;
use App\Models\PriceCard;
use App\Models\ProductCart;
use App\Models\UserAddress;
use App\Models\TableBooking;
use App\Traits\BannerTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BusinessSegment\BusinessSegment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\BusinessSegment\Product;
use App\Models\Category;
use App\Models\PromoCode;
use App\Models\BusinessSegment\Order;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Traits\OrderTrait;
use App\Traits\ProductTrait;
use App\Traits\AreaTrait;
use App\Models\OptionType;
use DateTime;
use DateTimeZone;
use App\Models\Onesignal;
use App\Models\FavouriteBusinessSegment;
use Exception;
use App\Models\BusinessSegment\BusinessSegmentConfigurations;
use App\Models\MerchantMembershipPlan;
use Carbon\Carbon;

class FoodController extends Controller
{
    // get home screen data of food app
    use BannerTrait, ApiResponseTrait, OrderTrait, ProductTrait, AreaTrait;

    public function homeScreen(Request $request)
    {
        // call area trait to get id of area
        $merchant_id = $request->merchant_id;
        $segment_id = $request->segment_id;
        $is_search = $request->is_search;
        $merchant_helper = new Merchant();
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            // return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        try {
            $user = $request->user('api');
            $merchant = $user->Merchant;

            $string_file = $this->getStringFile(NULL, $merchant);
            $this->getAreaByLatLong($request, $string_file);
            $distance = $merchant->BookingConfiguration->store_radius_from_user;
            $google_key = $merchant->BookingConfiguration->google_key;
            $request->request->add(['distance' => $distance]);
            if ((!$request->has('page') || $request->page == 1) && $is_search != 1) {
                $request->request->add(['merchant_id' => $merchant_id, 'home_screen' => NULL, 'segment_id' => $segment_id, 'banner_for' => 1]);

                if ($merchant->advertisement_module == 1) {
                    $arr_banner = $this->getMerchantBanner($request);
                    $banner_res['cell_title'] = 'BANNER_CELL';
                    $banner_res['cell_contents'] = $arr_banner->map(function ($item, $key) use ($merchant_id, $string_file, $merchant_helper) {

                        $image = get_image($item->banner_images, 'banners', $merchant_id, true, false);

                        $return = array(
                            'id' => $item->id,
                            'business_segment_id' => $item->business_segment_id,
                            'title' => $item->banner_name,
                            'image' => $image,
                            'image_width'=> $item->image_width,
                            'image_height'=>$item->image_height,
                            'redirect_url' => !empty($item->redirect_url) ? $item->redirect_url : "",
                        );
                        if (!empty($item->BusinessSegment)) {
                            $m_amount = $item->BusinessSegment->minimum_amount;
                            $m_amount_for = $item->BusinessSegment->minimum_amount_for;
                            $return['name'] = $item->BusinessSegment->full_name;
                            $return['time'] = $item->BusinessSegment->delivery_time . ' ' . trans("$string_file.minute");
                            // $return['amount'] = !empty($item->BusinessSegment->minimum_amount) ? "$m_amount" : "";
                            // $return['amount_for'] = !empty($item->BusinessSegment->minimum_amount_for) ? "$m_amount_for" : "";
                            $return['amount'] = !empty($item->BusinessSegment->minimum_amount) ? (string)$merchant_helper->PriceFormat($m_amount, $merchant_id) : "";
                            $return['amount_for'] = !empty($item->BusinessSegment->minimum_amount_for) ? (string)$merchant_helper->PriceFormat($m_amount_for, $merchant_id) : "";
                            $return['currency'] = "₹";
                        }
                        return $return;
                    });
                }
                $request->request->add(['is_popular' => 'YES']);
                $arr_popular_restaurant = $this->getMerchantBusinessSegment($request);
                //                    $this->getPopularBusinessSegment($request);
                $popular_restaurant_heading['cell_title'] = 'TITLE';
                $popular_restaurant_heading['cell_contents'][0] = ['title' => trans("$string_file.popular_brands")];

                $popular_restaurant_res['cell_title'] = 'POPULAR_BRAND_CELL';

                $popular_restaurant_res['cell_contents'] = $arr_popular_restaurant->map(function ($item, $key) use ($merchant_id, $string_file, $merchant_helper,$user,$request) {
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
                    $arr_style =   $item->StyleManagement->map(function ($style) use ($merchant_id, $string_file) {
                        return ['style_name' => $style->Name($merchant_id)];
                    });
                    $m_amount = $item->minimum_amount;
                    $m_amount_for = $item->minimum_amount_for;
                    $favBs = FavouriteBusinessSegment::where([['user_id', '=', $user->id], ['business_segment_id', '=', $item->id], ['segment_id', '=', $request->segment_id]])->first();
                    return array(
                        'business_segment_id' => $item->id,
                        'title' => $item->full_name,
                        'time' => "$item->delivery_time " . trans("$string_file.minute"),
                        // 'amount' => !empty($item->minimum_amount) ? "$m_amount" : "",
                        // 'amount_for' => !empty($item->minimum_amount_for) ? "$m_amount_for" : "",
                        'amount' => !empty($item->minimum_amount) ? (string)$merchant_helper->PriceFormat($m_amount, $merchant_id) : "",
                        'amount_for' => !empty($item->minimum_amount_for) ? (string)$merchant_helper->PriceFormat($m_amount_for, $merchant_id) : "",
                        'currency' => isset($item->Country) ? $item->Country->isoCode : '',
                        'style' => array_pluck($arr_style, 'style_name'), //array_pluck($item->StyleManagement, 'style_name'),
                        'open_time' => $open_time,
                        'close_time' => $close_time,
                        'rating' => !empty($item->rating) ? $item->rating : "2.5",
                        'image' => get_image($item->business_logo, 'business_logo', $merchant_id),
                        'is_business_segment_open' => $is_business_segment_open,
                        'is_admin_business_segment_open'=> $is_open_from_admin,
                        'admin_store_text_from_admin'=>$admin_text,
                        'is_favourite' => !empty($item->FavouriteBusinessSegment) && !empty($favBs) ? true : false,
                    );
                });

                $restaurant_heading['cell_title'] = 'TITLE';
                $restaurant_heading['cell_contents'][0] = ['title' => trans("$string_file.all_restaurants")];
            }

            $request->request->add(['is_popular' => NULL]);
            $arr_restaurant = $this->getMerchantBusinessSegment($request);
            $arr_restaurant_pg = $arr_restaurant;
            $google = new GoogleController;
            $user_lat = $request->latitude;
            $user_long = $request->longitude;
            $user = $request->user('api');
            $unit = isset($user->Country) ? $user->Country->distance_unit : "";
            $demo = $user->Merchant->demo;

            $restaurant_res['cell_title'] = 'RESTRAURANT_CELL';
            $restaurant_res['cell_contents'] = $arr_restaurant->map(function ($item, $key) use (
                $merchant_id,
                $google,
                $user_lat,
                $user_long,
                $unit,
                $string_file,
                $google_key,
                $demo,
                $user,
                $merchant_helper,
                $request
            ) {
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

                $arr_style =   $item->StyleManagement->map(function ($style) use ($merchant_id, $unit, $string_file) {
                    return ['style_name' => $style->Name($merchant_id)];
                });

                $store_lat = $item->latitude;
                $store_long = $item->longitude;

                // if ($demo ==  1) {
                    $distance_from_user = $google->arialDistance($user_lat, $user_long, $store_lat, $store_long, $unit, $string_file, true, true);
                // } else {
                //     // calculate distance from google direction api
                //     $user_drop_location[0] = [
                //         'drop_latitude' => $user_lat,
                //         'drop_longitude' => $user_long,
                //         'drop_location' => ""
                //     ];

                //     $distance_from_user = GoogleController::GoogleStaticImageAndDistance($store_lat, $store_long, $user_drop_location, $google_key, "", $string_file);
                //     $distance_from_user = isset($distance_from_user['total_distance_text']) ? $distance_from_user['total_distance_text'] : "";
                // }
                $favBs = FavouriteBusinessSegment::where([['user_id', '=', $user->id], ['business_segment_id', '=', $item->id], ['segment_id', '=', $request->segment_id]])->first();
                return array(
                    'business_segment_id' => $item->id,
                    'title' => $item->full_name,
                    'time' => "$item->delivery_time " . trans("$string_file.minute"),
                    // 'amount' => !empty($item->minimum_amount) ? "$item->minimum_amount" : "",
                    // 'amount_for' => !empty($item->minimum_amount_for) ? "$item->minimum_amount_for" : "",
                    'amount' => !empty($item->minimum_amount) ? (string)$merchant_helper->PriceFormat($item->minimum_amount, $merchant_id) : "",
                    'amount_for' => !empty($item->minimum_amount_for) ? (string)$merchant_helper->PriceFormat($item->minimum_amount_for, $merchant_id) : "",
                    'currency' => isset($item->Country) ? $item->Country->isoCode : '',
                    'style' => array_pluck($arr_style, 'style_name'), //array_pluck($item->StyleManagement, 'style_name'),
                    'open_time' => $open_time,
                    'close_time' => $close_time,
                    'rating' => !empty($item->rating) ? $item->rating : "2.5",
                    'distance' => trans("$string_file.distance") . ' ' . $distance_from_user,
                    'image' => get_image($item->business_logo, 'business_logo', $merchant_id),
                    'is_business_segment_open' => $is_business_segment_open,
                    'promo_code' => $item->ActivePromoCode ? $item->ActivePromoCode : (object)[],
                    'is_favourite' => !empty($item->FavouriteBusinessSegment) && !empty($favBs) ? true : false,
                    'is_admin_business_segment_open'=> $is_open_from_admin,
                    'admin_store_text_from_admin'=>$admin_text
                );
            });

            $arr_category = $this->getMerchantCategory($request);
            $user_lat = $request->latitude;
            $user_long = $request->longitude;
            $user = $request->user('api');

            $category_res['cell_title'] = 'CATEGORY_CELL';
            $category_res['cell_contents'] = $arr_category->map(function ($item, $key) use (
                 $merchant_id,
                 $user_lat,
                 $user_long,
                 $string_file
            ){
                return array(
                    'category_id'=> $item->id,
                    'category_image'=> get_image($item->category_image,'category',$merchant_id),
                    'name'=> $item->name,
                    'is_selected'=> $item->is_selected
                );
            });

            $return_data = [];
            if ((!$request->has('page') || $request->page == 1) && $is_search != 1) {
                if ($merchant->advertisement_module == 1) {
                    array_push($return_data, $banner_res);
                }
                array_push($return_data, $popular_restaurant_heading, $popular_restaurant_res, $category_res,$restaurant_heading, $restaurant_res);
                // $return_data[0] = $banner_res;
                // $return_data[1] = $popular_restaurant_heading;
                // $return_data[2] = $popular_restaurant_res;
                // $return_data[3] = $restaurant_heading;
                // $return_data[4] = $restaurant_res;
            } else {
                //            $return_data[0] = $restaurant_heading;
                $return_data[0] = $restaurant_res;
            }
            $restaurant_res = $arr_restaurant_pg->toArray();
            $next_page_url = $restaurant_res['next_page_url'];
            $next_page_url = $next_page_url == "" ? "" : $next_page_url;
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return response()->json(['result' => "1", 'message' => trans("$string_file.data_found"), 'next_page_url' => $next_page_url, 'total_pages' => $restaurant_res['last_page'], 'current_page' => $restaurant_res['current_page'], 'data' => $return_data]);
    }

    //    public function getPopularBusinessSegment(Request $request)
    //    {
    //        // call area trait to get id of area
    //        // $this->getAreaByLatLong($request);
    //
    //        $merchant_id = $request->merchant_id;
    //        $segment_id = $request->segment_id;
    //        $country_area_id = $request->area;
    //        $distance_unit = 1;
    //        $radius = $distance_unit == 2 ? 3958.756 : 6367;
    //        $distance = 50;
    //        $latitude = $request->latitude;
    //        $longitude = $request->longitude;
    //        $arr_popular_restro = BusinessSegment::select('id', 'country_id', 'full_name', 'business_logo', 'delivery_time', 'minimum_amount', 'minimum_amount_for', 'open_time', 'close_time')
    //            ->addSelect(DB::raw('( ' . $radius . ' * acos( cos( radians(' . $latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) ) AS distance'))
    //            ->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id]])
    ////            ->having('distance', '<', $distance)
    //            ->where('country_area_id', $country_area_id)
    //            ->orderBy('distance')
    //            ->get();
    //        return $arr_popular_restro;
    //    }

    // get products list of restaurant
    public function foodProducts(Request $request)
    {
        // its business segment id
        $id = $request->id;
        $merchant_id = $request->merchant_id;
        $user = $request->user('api');
        $user_id = $user->id;
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $merchant_helper = new Merchant();
        $trip_calculation_method = $user->Merchant->Configuration->trip_calculation_method;
        $format_price = $user->Merchant->Configuration->format_price;
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        // products restaurant

        $business_segment = BusinessSegment::select('id', 'full_name', 'country_area_id', 'delivery_time', 'minimum_amount', 'minimum_amount_for', 'rating','open_time','close_time','slot_end_time')
            ->with(['FavouriteBusinessSegment' => function ($q) use ($user_id) {
                $q->where([['user_id', '=', $user_id]]);
            }])
            ->Find($request->id);
        $arr_style =   $business_segment->StyleManagement->map(function ($style) use ($merchant_id) {
            return ['style_name' => $style->Name($merchant_id)];
        });
        $currency = isset($business_segment->CountryArea->Country) ? $business_segment->CountryArea->Country->isoCode : "";

        $arr_products = $this->getProducts($request, $currency);

        $request->merge(['business_segment_id' => $business_segment->id, 'merchant_id' => $merchant_id, 'home_screen' => NULL, 'segment_id' => $business_segment->segment_id, 'banner_for' => 1]);
        $arr_banner = $this->getBusinessSegmentBanner($request);
        // Add Business segment logo if banner not exist.
        if (empty($arr_banner)) {
            array_push($arr_banner, array(
                'id' => null,
                'business_segment_id' => $business_segment->id,
                'title' => $business_segment->full_name,
                'image' => get_image($business_segment->business_logo, 'business_logo', $merchant_id, true, false),
                'redirect_url' => "",

            ));
        }
        $businessSegmentConfig = BusinessSegmentConfigurations::where('business_segment_id',  $business_segment->id)->first();
        if(!empty($businessSegmentConfig)){
            $is_open_from_admin = $businessSegmentConfig->is_open == 1 ? true : false;
            $admin_text = $businessSegmentConfig->is_open == 1 ? trans("$string_file.open") : trans("$string_file.close");
        }
        
        // only setting timezone
        date_default_timezone_set($business_segment->CountryArea['timezone']);
        $is_business_segment_open = false;
        
        if($user->Merchant->Configuration->bs_slot_end_time_enable == 1){
            $current_time = date('H:i');
                    $is_business_segment_open = false;
                    $current_day = date('w');
                    $arr_open_time = json_decode($business_segment->open_time, true);
                    $arr_close_time = json_decode($business_segment->close_time, true);
                    $arr_slot_end_time = isset($business_segment->slot_end_time) ? json_decode($business_segment->slot_end_time, true) : [2,2,2,2,2,2,2];
                    $open_time = isset($arr_open_time[$current_day]) ? $arr_open_time[$current_day] : NULL;
                    $close_time = isset($arr_close_time[$current_day]) ? $arr_close_time[$current_day] : NULL;
                    $slot_end_time = isset($arr_slot_end_time[$current_day]) ? $arr_slot_end_time[$current_day] : NULL;
                    if($open_time > $close_time ){
                        if($open_time < $current_time){
                            $is_business_segment_open = true;
                        } else {
                            $is_business_segment_open = false;
                        }
                        if(!$is_business_segment_open){
                            if($current_day == 0){
                                $before_day = 6;
                            }else{
                                $before_day = $current_day -1 ;
                            }
                            $before_slot_end_time = isset($arr_slot_end_time[$before_day]) ? $arr_slot_end_time[$before_day] : "2";
                            if($before_slot_end_time == 1){
                                $before_open_time = "00:00";
                                $before_close_time = isset($arr_close_time[$before_day]) ? $arr_close_time[$before_day] : NULL;
                                $beforeday_close_time = $before_close_time;
                                if ($before_open_time < $current_time && $beforeday_close_time > $current_time) {
                                    $is_business_segment_open = true;
                                } else {
                                    $is_business_segment_open = false;
                                }
                                
                            }else{
                                $is_business_segment_open = false;
                            }
                        }
                        
                    }else{
                        if ($open_time < $current_time && $close_time > $current_time) {
                            $is_business_segment_open = true;
                        } else {
                            if(!$is_business_segment_open){
                                if($current_day == 0){
                                    $before_day = 6;
                                }else{
                                    $before_day = $current_day -1 ;
                                }
                                $before_slot_end_time = isset($arr_slot_end_time[$before_day]) ? $arr_slot_end_time[$before_day] : "2";
                                    if($before_slot_end_time == 1){
                                        $before_open_time = "00:00";
                                        $before_close_time = isset($arr_close_time[$before_day]) ? $arr_close_time[$before_day] : NULL;
                                        $beforeday_close_time = $before_close_time;
                                        if ($before_open_time < $current_time && $beforeday_close_time > $current_time) {
                                            $is_business_segment_open = true;
                                        } else {
                                            $is_business_segment_open = false;
                                        }
                                        
                                    }else{
                                        $is_business_segment_open = false;
                                    }
                                
                            }
                        }
                    }
                    
        }else{
            
                    $current_time = date('H:i');
                    $is_business_segment_open = false;
                    $current_day = date('w');
                    $arr_open_time = json_decode($business_segment->open_time, true);
                    $arr_close_time = json_decode($business_segment->close_time, true);
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
        }
        
        $restaurant = [
            'business_segment_id' => $business_segment->id,
            'currency' => $currency,
            'name' => $business_segment->full_name,
            'time' => $business_segment->delivery_time . " " . trans("$string_file.minute"),
            // 'amount' => !empty($business_segment->minimum_amount) ? "$business_segment->minimum_amount" : "",
            // 'amount_for' => !empty($business_segment->minimum_amount_for) ? "$business_segment->minimum_amount_for" : NULL,
            'amount' => !empty($business_segment->minimum_amount) ? (string)$merchant_helper->PriceFormat($business_segment->minimum_amount, $merchant_id, $format_price, $trip_calculation_method) : "",
            'amount_for' => !empty($business_segment->minimum_amount_for) ? (string)$merchant_helper->PriceFormat($business_segment->minimum_amount_for, $format_price , $trip_calculation_method) : NULL,
            'style' => array_pluck($arr_style, 'style_name'), //array_pluck($business_segment->StyleManagement, 'style_name'),
            'rating' => !empty($business_segment->rating) ? $business_segment->rating : "2.5",
            "banners" => $arr_banner,
            "is_favourite" => !empty($business_segment->FavouriteBusinessSegment->id) ? true : false,
            'is_business_segment_open'=> $is_business_segment_open,
            'is_admin_business_segment_open'=> $is_open_from_admin,
            'admin_store_text_from_admin'=>$admin_text
        ];
        $return_data['restaurant'] = $restaurant;
        $return_data['arr_product'] = $arr_products;
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    public function getProducts($request, $currency = NULL)
    {
        $return_type = $request->return_type;
        $id = ($return_type == "single_product_detail") ? $request->product_id : $request->id; // return_type will be single_product_detail if getting a specefic product otherwise null
        $merchant_id = $request->merchant_id;
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $food_type = $request->food_type; // for veg & non-veg
        $merchant_helper = new Merchant();
        $trip_calculation_method = $user->Merchant->Configuration->trip_calculation_method;
        $format_price = $user->Merchant->Configuration->format_price;
        $arr_products = Category::with(['Product' => function ($q) use ($id, $food_type, $return_type) {
            $q->select('id', 'category_id','status','business_segment_id', 'manage_inventory', 'food_type', 'product_preparation_time', 'product_cover_image','sequence');
            if($return_type == "single_product_detail"){
                $q->where([['id', '=', $id], ['delete', '=', NULL],['status','=',1]]);
                $q->orderBy('sequence');
            }
            else{
                $q->where([['business_segment_id', '=', $id], ['delete', '=', NULL],['status', '=', 1]]);
                $q->orderBy('sequence');
            }
            if (!empty($food_type)) {
                $q->where('food_type', $food_type);
            }
            // products_variant
            $q->with(['ProductVariant' => function ($qq) use ($id) {
                $qq->select('id', 'product_id', 'weight_unit_id', 'product_price', 'weight', 'status', 'is_title_show', 'discount', 'product_title');
                $qq->where([['status', '=', 1], ['delete', '=', NULL]]);
                $qq->with(['ProductInventory' => function ($qq) use ($id) {
                    $qq->select('id', 'product_variant_id', 'current_stock');
                }]);
            }]);

            $q->whereHas('ProductVariant', function ($qq) use ($id) {
                $qq->select('id', 'product_id', 'weight_unit_id', 'product_price', 'weight', 'status', 'is_title_show', 'product_title');
                $qq->where([['status', '=', 1], ['delete', '=', NULL]]);
            });
            $q->with(['Option' => function ($qq) use ($id) {
            }]);
        }])
            ->whereHas('Product', function ($q) use ($id, $food_type, $return_type) {
                if ($return_type == "single_product_detail") {
                    $q->where([['id', '=', $id], ['delete', '=', NULL], ['status', '=', 1]]);
                } else {
                    $q->where([['business_segment_id', '=', $id], ['delete', '=', NULL], ['status', '=', 1]]);
                    if (!empty($food_type)) {
                        $q->where('food_type', $food_type);
                    }
                }

            })
           
            ->select('id','sequence')
            ->where('merchant_id', $merchant_id)
            ->where('delete', NULL)
            ->where('status', 1)
            ->orderBy('sequence')
            ->get();
            
        $return_data = [];
        $arr_product_variant = [];
        $arr_product_option = [];
        foreach ($arr_products as $category) {
            $product_data = [];
            if ($category->Product->count() > 0) {
                foreach ($category->Product as $product) {
                    $product_variants = $product->ProductVariant;
                    $arr_product_variant = [];
                    $arr_product_option = [];
                    $product_lang = $product->langData($merchant_id);
                    if ($product_variants->count() > 0) {
                        // product variant as option
                        $product_variant_data = [];
                        foreach ($product_variants as $key => $product_variant) {
                            if ($product->manage_inventory == 1 && empty($product_variant->ProductInventory->id)) {
                                continue;
                            } else {
                                $selected = $key == 0 ? true : false;
                                $unit = !empty($product_variant->weight_unit_id) ? $product_variant->WeightUnit->WeightUnitName : "";
                                $discounted_price = !empty($product_variant->discount) && $product_variant->discount > 0 ? ($product_variant->product_price - $product_variant->discount) : "";
                                $product_variant_data[] = [
                                    'id' => $product_variant->id, // first variant id
                                    // 'title' => $product_variant->is_title_show == 1 ? $product_variant->Name($merchant_id) : $product_lang->name,
                                    'title' => $product_variant->is_title_show == 1 ? $product_variant->product_title : $product_lang->name,
                                    'price' => $merchant_helper->TripCalculation($product_variant->product_price, $merchant_id, $trip_calculation_method),
                                    'discount' => !empty($discounted_price) ? $merchant_helper->TripCalculation($product_variant->discount, $merchant_id, $trip_calculation_method) : "",
                                    'discounted_price' => !empty($discounted_price) ? $merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method) : "",
                                    'product_id' => $product->id,
                                    'selected' => $selected,
                                    'weight_unit' => $product_variant->weight . ' ' . $unit,
                                    'stock_quantity' => isset($product_variant->ProductInventory->id) ? $product_variant->ProductInventory->current_stock : NULL,
                                    'product_availability' => $product_variant->status == 1 ? true : false,
                                ];
                            }
                        }
                        if (count($product_variant_data) > 1) {
                            $variant_heading = [
                                'cell_title' => trans("$string_file.size"),
                                'message' => "", //trans_choice("$string_file.can_select_max_option", 3,  ['NUM' => trans("$string_file.one"),'OBJECT' => trans("$string_file.option")]),
                                'mandatory' => true,
                                'minSelection' => 1,
                                'max_selection' => 10000, // unlimited
                                'cell_contents' => $product_variant_data,
                            ];

                            array_push($arr_product_variant, $variant_heading);
                        }

                        // product option from option table
                        if (!empty($product->Option[0]) && $product->Option[0]->pivot->product_id == $product->id) {
                            $product_option_type = OptionType::select('id', 'charges_type', 'max_option_on_app', 'select_type')
                                ->with(["Option" => function ($q) use ($id, $product) {
                                    $q->addSelect('id', 'option_type_id')
                                        ->where([['status', '=', 1], ['business_segment_id', '=', $id]]);
                                    $q->with(["Product" => function ($qq) use ($product) {
                                        $qq->where('product_id', $product->id);
                                    }]);
                                }])
                                ->whereHas("Option", function ($q) use ($id, $product) {
                                    $q->where([['status', '=', 1], ['business_segment_id', '=', $id]]);
                                    $q->whereHas("Product", function ($qqq) use ($product) {
                                        $qqq->where('product_id', $product->id);
                                    });
                                })
                                ->where([['status', '=', 1], ['merchant_id', '=', $merchant_id]])
                                ->get();

                            $select_id = NULL;
                            foreach ($product_option_type as $option_type) {
                                $arr_option = [];
                                foreach ($option_type->Option as $key => $option) {
                                    if (isset($option->Product[0]) && $option->Product[0]->pivot->product_id == $product->id) {
                                        $arr_option[] = [
                                            'id' => $option->id,
                                            'title' => $option->Name($id),
                                            //                                        'selected' => $option_type->select_type == 2 && $key == 0 ? true : false,
                                            'selected' => false,
                                            'price' => isset($option->Product[0]) && !empty($option->Product[0]->pivot->option_amount) ? $merchant_helper->TripCalculation($option->Product[0]->pivot->option_amount, $merchant_id, $trip_calculation_method) : "",
                                        ];
                                    }
                                }
                                if (count($arr_option) > 0) {
                                    $option_heading = [
                                        'cell_title' => $option_type->Type($merchant_id),
                                        'message' => trans_choice("$string_file.can_select_max_option", 3,  ['NUM' => $option_type->max_option_on_app, 'OBJECT' => trans("$string_file.option")]),
                                        'mandatory' => $option_type->select_type == 2 ? true : false,
                                        'max_selection' => !empty($option_type->max_option_on_app) ? $option_type->max_option_on_app : 0,
                                        'cell_contents' => $arr_option,
                                    ];
                                    array_push($arr_product_option, $option_heading);
                                }
                            }
                        }
                        // product details
                        $first_variant = isset($product_variants[0]) ? $product_variants[0]  : NULL;
                        // p($first_variant);
                        if ($product->manage_inventory == 1 && empty($first_variant->ProductInventory->id)) {
                            continue;
                        } else {
                            $unit = !empty($first_variant->weight_unit_id) ? $first_variant->WeightUnit->WeightUnitName : "";
                            $discounted_price = !empty($first_variant->discount) && $first_variant->discount > 0 ? ($first_variant->product_price - $first_variant->discount) : "";
                            $product_image = !empty($product->product_cover_image) ? get_image($product->product_cover_image, 'product_cover_image', $merchant_id) : "";
                            if(count($product->ProductImage) > 0 && !empty($product->ProductImage[0])){
                                $product_image = get_image($product->ProductImage[0]->product_image, 'product_image', $merchant_id);
                            }
                            
                            $product_price =  $merchant_helper->TripCalculation($first_variant->product_price, $merchant_id, $trip_calculation_method);
                            $discounted_price = !empty($discounted_price) ? (string) $merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method) : "";
                            
                            $product_data[] = [
                                'id' => $first_variant->id, // first variant id
                                'product_id' => $product->id,
                                'product_name' => $product_lang->name,
                                //                                'product_name' => $product_variant->is_title_show == 1 ? $product_variant->Name($merchant_id) : $product_lang->name,
                                'product_cover_image' => !empty($product->product_cover_image) ? get_image($product->product_cover_image, 'product_cover_image', $merchant_id) : $product_image,
                                'product_image' => $product_image,
                                'currency' => "$currency",
                                // 'product_price' => (string) $merchant_helper->TripCalculation($first_variant->product_price, $merchant_id, $trip_calculation_method),
                                // 'discount' => !empty($first_variant->discount) ? $merchant_helper->TripCalculation($first_variant->discount, $merchant_id, $trip_calculation_method) : "",
                                // 'discounted_price' => !empty($discounted_price) ? (string) $merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method) : "",
                                'product_price' => $product_price,
                                'formatted_product_price' => ($format_price != 1) ? $merchant_helper->PriceFormat($product_price, $merchant_id, $format_price, $trip_calculation_method) : "",
                                'discount' => !empty($first_variant->discount) ? $merchant_helper->TripCalculation($first_variant->discount, $merchant_id, $trip_calculation_method) : "",
                                'discounted_price' => $discounted_price,
                                'formatted_discount_price' => ($format_price != 1 &&  !empty($discounted_price)) ? $merchant_helper->PriceFormat($discounted_price,  $merchant_id, $format_price , $trip_calculation_method) : "",
                                
                                'food_type' => $product->food_type,
                                'product_description' => !empty($product_lang->description) ? $product_lang->description : "",
                                'ingredients' => !empty($product_lang->ingredients) ? $product_lang->ingredients : "",
                                'weight_unit' => $first_variant->weight . ' ' . $unit,
                                'manage_inventory' => $product->manage_inventory,
                                'stock_quantity' => isset($first_variant->ProductInventory->id) ? $first_variant->ProductInventory->current_stock : NULL,
                                'product_availability' => $first_variant->status == 1 ? true : false,
                                'arr_variant' => $arr_product_variant,
                                'arr_option' => $arr_product_option
                            ];
                        }
                    }
                }
            }
            $return_data[] = [
                'id' => $category->id,
                'category_name' => $category->Name($merchant_id),
                'product' => $product_data,
            ];
        }
        //
        return $return_data;
        // return $arr_products;
    }
    
    public function productDetails(Request $request)
    {
        
         $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer'],
            'business_segment_id' => ['required', 'integer'],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        
        $string_file = $this->getStringFile($request->merchant_id);
        $request->merge(['return_type' => 'single_product_detail']);
        // $return_data = $this->getProductDetails($request);

        $business_segment = BusinessSegment::Find($request->business_segment_id);
        $currency = isset($business_segment->CountryArea->Country) ? $business_segment->CountryArea->Country->isoCode : "";
        $return_data = $this->getProducts($request, $currency);
        
        
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    public function saveProductCart(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $request_fields = [
            'segment_id' => "required_if:product_update,==,NO",
            'service_type_id' => "required_if:product_update,==,NO",
            'longitude' => 'required_if:product_update,==,NO',
            'latitude' => 'required_if:product_update,==,NO',
            'product_update' => 'required|in:YES,NO',
            'product_details' => 'required_if:product_update,==,NO',
            'cart_id' => 'required_if:product_update,==,YES',
            'product_variant_id' => 'required_if:product_update,==,YES',
            'quantity' => 'required_if:product_update,==,YES',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant_id = $request->merchant_id;
            // call area trait to get id of area
            $this->getAreaByLatLong($request, $string_file);
            $id = $request->cart_id; // product cart/checkout table
            if ($request->product_update == "YES" && !empty($id)) {
                $product_variant_id = $request->product_variant_id;
                $product_quantity = $request->quantity;
                $product_options = [];
                if(isset($request->product_details)){
                    $request_product_details =json_decode($request->product_details);
                    $product_options = $request_product_details[0]->options;
                }
                $product_cart = ProductCart::where('id', $id)->first();
                if (empty($product_cart->id)) {
                    $string_file = $this->getStringFile($merchant_id);
                    throw new \Exception(trans("$string_file.cart_not_found"));
                }
                $product_details = $product_cart->product_details;
                $product_details = json_decode($product_details, true);
                $updated_products = [];
                foreach ($product_details as $product) {
                    $quantity = 0;
                    if(isset($product['options']) && count($product_options) > 0){
                        $quantity =(($product['product_variant_id'] == $product_variant_id) && ($product['options']==$product_options)) ? $product_quantity : $product['quantity'];
                    }else{
                        $quantity =($product['product_variant_id'] == $product_variant_id) ? $product_quantity : $product['quantity'];
                    }
                    // $updated_products[] = ['product_variant_id' => $product['product_variant_id'], 'quantity' => $quantity];
                    $updated_list =  ['product_variant_id' => $product['product_variant_id'], 'quantity' => $quantity,'options' =>[]];
                    if (isset($product['options']) && !empty($product['options'])) {
                        $updated_list['options'] = $product['options'];
                    }else{
                        $updated_list['options'] = [];
                    }
                    $updated_products[] = $updated_list;
                }
                // p($updated_products);
                $product_cart->product_details = json_encode($updated_products);
            } else {

                $segment_id = $request->segment_id;
                $service_type_id = $request->service_type_id;
                //$this->getSegmentService($segment_id, $merchant_id, 'id');
                $country_area_id = $request->area;
                // price card to check delivery charges of user
                $price_card = PriceCard::where([['status', '=', 1], ['country_area_id', '=', $country_area_id], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $service_type_id], ['segment_id', '=', $segment_id], ['price_card_for', '=', 2]])->first();
                if (empty($price_card)) {
                    return $this->failedResponse(trans("$string_file.no_price_card_for_area"));
                }
                $segment_id = $request->segment_id;
                $merchant_id = $request->merchant_id;
                $user_id = $user->id;
                $product_cart = ProductCart::where('id', $id)->orWhere(function ($q) use ($user_id, $segment_id, $merchant_id) {
                    $q->where([['user_id', '=', $user_id], ['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id]]);
                })->first();

                if (empty($product_cart->id)) {
                    $product_cart = new ProductCart;
                    $product_cart->user_id = $user_id;
                    $product_cart->merchant_id = $request->merchant_id;
                    $product_cart->segment_id = $request->segment_id;
                }
                // save cart data
                $product_cart->service_type_id = $request->service_type_id;
                $product_cart->product_details = $request->product_details;
                $product_cart->price_card_id = $price_card->id;
            }
            // return cart data
            $calling_from = "save_cart";
            $product_cart->save();
            $product_cart->area = $request->area;
            $return_cart = $this->getCartData($product_cart, false, "", $calling_from, $request, $string_file);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"), $return_cart);
    }

    public function getProductCart(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $request_fields = [
            'cart_id' => ['required', 'integer', Rule::exists('product_carts', 'id')->where(function ($query) {
            }),],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $id = $request->cart_id; // product cart/checkout table
        // return cart data
        $return_cart = $this->getCartData($id, true, NULL, "", $request, $string_file);
        return $this->successResponse(trans("$string_file.data_found"), $return_cart);
    }

    public function deleteCart(Request $request)
    {
        $request_fields = [
            'delete_type' => 'required|in:CART,PRODUCT',
            'cart_id' => 'required',
            'product_variant_id' => 'required_if:delete_type,==,PRODUCT',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $merchant_id = $request->merchant_id;
            $string_file = $this->getStringFile($merchant_id);
            $id = $request->cart_id; // product cart/checkout table
            $product_cart = ProductCart::where('id', $id)->first();
            if ($request->delete_type == 'CART') {
                $product_cart->delete();
                return $this->successResponse(trans("$string_file.cart_deleted"));
            } else {
                $product_id = $request->product_variant_id;
                $product_details = $product_cart->product_details;
                $product_details = json_decode($product_details, true);
                $product_options = [];
                if(isset($request->product_details)){
                    $request_product_details =json_decode($request->product_details);
                    $product_options = $request_product_details[0]->options;
                }
                $updated_products = [];
                foreach ($product_details as $product) {
                    if ($product['product_variant_id'] != $product_id || (isset($product['options']) && $product['options']!=$product_options)) {

                        $updated_list =  ['product_variant_id' => $product['product_variant_id'], 'quantity' => $product['quantity']];
                        if (isset($product['options']) && !empty($product['options'])) {
                            $updated_list['options'] = $product['options'];
                        }else{
                              $updated_list['options'] = [];
                        }
                        $updated_products[] = $updated_list;
                        // $updated_products[] = ['product_variant_id' => $product['product_variant_id'], 'quantity' => $product['quantity'],'options'=>$product['options']];
                        // $updated_products[] = ['product_variant_id' => $product['product_variant_id'], 'quantity' => $product['quantity']];
                    }
                }

                if (count($updated_products) == 0) {
                    $product_cart->delete();
                    return $this->successResponse(trans("$string_file.cart_deleted"));
                }
                // p($updated_products);
                $product_cart->product_details = json_encode($updated_products);
                $product_cart->save();
            }
            // return cart data
            $return_cart = $this->getCartData($product_cart, false, "", "delete_cart", $request, $string_file);
            return $this->successResponse(trans("$string_file.cart_product_deleted"), $return_cart);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function getCartData($product_cart, $find_by_cart_id = true, $promo_code = null, $calling_from = "", $request = NULL, $string_file = "")
    {
        try {
            $area_id = isset($product_cart->area) ? $product_cart->area : NULL;
            $area_id = ($area_id == NULL && $request->area) ? $request->area : $area_id;
            if (isset($product_cart['area'])) {
                unset($product_cart['area']);
            }
            if ($find_by_cart_id == true) {
                $product_cart = ProductCart::Find($product_cart);
            }
            $merchant_helper = new Merchant();
            $trip_calculation_method = $product_cart->Merchant->Configuration->trip_calculation_method;
            $format_price = $product_cart->Merchant->Configuration->format_price;
            $arr_cart_product = json_decode($product_cart->product_details, true);
            $res = [];
            $res2 = [];
            foreach ($arr_cart_product as $key => $product) {
                    $res[$product['product_variant_id'].'_'.$key] = $product;
                    $res2[] = $product['product_variant_id'];
            }
            $arr_variant = $res;
            $arr_product_id = $res2;
            $arr_cart_product_list = array_column($arr_cart_product, NULL, 'product_variant_id');
            $arr_product_list = ProductVariant::select('id', 'product_id', 'product_price', 'weight', 'weight_unit_id', 'is_title_show', 'discount', 'status')
                ->whereIn('id', $arr_product_id)->where([['delete', '=', NULL]])
                ->with(['Product' => function ($q) {
                    $q->select('id', 'category_id', 'merchant_id', 'manage_inventory', 'tax', 'product_cover_image', 'food_type', 'business_segment_id');
                    $q->with(['Option' => function ($q) {
                    }]);
                }])
                ->with(['WeightUnit' => function ($q) {
                    $q->select('id');
                }])
                ->with(['ProductInventory' => function ($q) {
                    $q->select('id', 'product_variant_id', 'current_stock');
                }])
                ->get();

            $total_cart_quantity = 0;
            $total_cart_amount = 0;
            $total_tax_amount = 0;
            $business_segment_id = NULL;
            $arr_cart_product = [];
            $product_data = [];
            if (isset($area_id) && !empty($area_id)) {
                $country_area = CountryArea::find($area_id);
                $currency = isset($country_area->Country) ? $country_area->Country->isoCode : "";
            } else {
                $currency = isset($product_cart->User->Country) ? $product_cart->User->Country->isoCode : "";
            }
            $cart_out_of_stock = false; // overall cart status like out of stock or not
            $total_product_discount = 0;
            $arrProdList = array_column($arr_product_list->toArray(),NULL,'id');
            // dd($arr_variant);
            foreach ($arr_variant as $key => $variant) {
                $product = $arrProdList[$variant['product_variant_id']];
                $prodVariant = ProductVariant::find($variant['product_variant_id']);
                $prodId = $product['product']['id'];
                $productData = \App\Models\BusinessSegment\Product::find($prodId);
                $merchant_id = $product['product']['merchant_id'];
                $arr_return_option = [];
                $arr_option_amount = [];
                $business_segment_id = $product['product']['business_segment_id'];
                $arr_options = isset($variant['options']) ? $variant['options'] : [];
                if (!empty($arr_options)) {
                    $arr_cart_option = $product['product']['option'];
                    foreach ($arr_cart_option as $option) {
                        if (in_array($option['id'], $arr_options)) {
                            $optionData = \App\Models\BusinessSegment\Option::find($option['id']);
                            $arr_return_option[] = [
                                'id' => $option['id'],
                                'option_name' => $optionData->Name($business_segment_id),
                                'amount' => $option['pivot']['option_amount'],
                            ];
                            $arr_option_amount[] = $option['pivot']['option_amount'];
                        }
                    }
                }
                $product_option_amount = array_sum($arr_option_amount);
                $product_price = $product['product_price'];
                $product_discount = (!empty($product['discount'])  && $product['discount'] > 0) ? $product['discount'] : NULL;
                //            $product_tax = $product->Product->tax;
                $quantity = (int) $variant['quantity'];
                $unit = 0;
                if(isset($product['weight_unit_id'])){
                    $weightUnitData = \App\Models\WeightUnit::find($product['weight_unit_id']);
                    $unit = $weightUnitData->WeightUnitName;
                }
                

                // check item stock
            $manage_inventory = $product['product']['manage_inventory'];
            $item_out_of_stock = false;
            $current_stock = 0;

            if ($manage_inventory == 1 && !empty($product['product_inventory']['id'])) {
                $current_stock = $product['product_inventory']['current_stock'];
                $item_out_of_stock = $current_stock < $quantity;
                if ($item_out_of_stock) {
                    $cart_out_of_stock = $item_out_of_stock;
                }
            }

            $product_image =count($productData->ProductImage) > 0
              ? get_image($productData->ProductImage[0]['product_image'], 'product_image', $merchant_id)
                 : "";
                // $product_image = $product->Product->ProductImage && $product->Product->ProductImage->count() > 0 ? get_image($product->Product->ProductImage[0]->product_image, 'product_image', $merchant_id) : "";
                $total_product_discount = $total_product_discount + $product_discount;
                $product_total_price = (($product_price - $product_discount) + $product_option_amount) * $quantity;
                $product_data['product_id'] = $product['product_id'];
                $product_data['weight_unit_id'] = $product['weight_unit_id'];
                $product_data['product_variant_id'] = $product['id'];
                $product_data['food_type'] = $product['product']['food_type'];
                $product_data['quantity'] = $quantity;
                $product_data['current_stock'] = $current_stock;
                $product_data['manage_inventory'] = $manage_inventory;
                $product_data['currency'] = "$currency";
                $product_data['item_out_of_stock'] = $item_out_of_stock;
                // $product_data['product_price'] = $merchant_helper->TripCalculation($product_price, $merchant_id, $trip_calculation_method);
                // $product_data['discount'] = !empty($product_discount) ? $merchant_helper->TripCalculation($product_discount, $merchant_id, $trip_calculation_method) : "";
                $product_data['product_price'] = $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method);
                $product_data['discount'] = !empty($product_discount) ? $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_discount, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method) : "";
                //              $product_data['tax'] = $product_tax;
                $discounted_price = !empty($product_discount) ? ($product_price - $product_discount) : "";
                // $product_data['discounted_price'] = !empty($discounted_price) ? $merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method) : "";
                $product_data['discounted_price'] = !empty($discounted_price) ? $merchant_helper->PriceFormat($merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method) : "";
                // $product_data['total_price'] = $merchant_helper->TripCalculation($product_total_price, $merchant_id, $trip_calculation_method);
                $product_data['total_price'] = $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_total_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method);

                //            $product_tax_amount = $product_total_price * $product_tax / 100;
                //            $product_data['tax_amount'] = $product_tax_amount;
                $product_data['weight_unit'] = $product['weight'] . ' ' . $unit;
                $product_data['product_name'] = $productData->Name($merchant_id);  

                $product_data['variant_title_heading'] = $product['is_title_show'] == 1 ? trans("$string_file.size") : "";
                $product_data['variant_title'] = $product['is_title_show'] == 1 ? $prodVariant->Name($merchant_id) : "";
                $product_data['variant_status'] = $product['status'];
                // dd($product['product']['product_cover_image']);
                $product_data['product_cover_image'] = !empty($product['product']['product_cover_image']) ? get_image($product['product']['product_cover_image'], 'product_cover_image', $product_cart->merchant_id, true) : $product_image;
                $product_data['arr_option'] = $arr_return_option;
                $arr_cart_product[] = $product_data;
                $total_cart_quantity += $quantity;
                $total_cart_amount += $product_total_price;
                //            $total_tax_amount += $product_tax_amount;
                //p($product_data);
                
                
            }
            
            // dd($product_data);



            $product_cart->business_segment_id = $business_segment_id;
            $product_cart->cart_amount = $total_cart_amount;
            //            if ($calling_from == "save_cart") {
            // just update the business segment id
            $all_packaging_preferences = PackagingPreference::where("business_segment_id", $business_segment_id)->get();
            $preferences = [];
            $additional_preference_amount = 0;
            $packaging_preference_ids = !empty($request->packaging_preferences)? json_decode($request->packaging_preferences, true) : [];
            foreach($all_packaging_preferences as $preference){
                $is_applied = in_array($preference->id, $packaging_preference_ids)? true : false;
                $additional_preference_amount += ($is_applied) ? $preference->amount : 0;
                $preferences[] = [
                    "id"=>$preference->id,
                    "description"=>$preference->getPackagingPreferenceDescriptionAttribute(),
                    "amount"=> $currency ." ".$preference->amount,
                    "icon"=> get_image($preference->icon ,'packaging_preferences', $merchant_id),
                    "is_applied" => $is_applied
                ];
            }
            if( $calling_from == "savePackagingPreferences"){
                $product_cart->packaging_preferences = json_encode($preferences);
            }
            $product_cart->save();
            //            }


            /**
             * Delivery charges will be calculated only in home delivery service
             */
            $delivery_charge = 0;
            $service_type = 6; // self  pickup
            //drop_distance_from_restaurant
            if ($product_cart->ServiceType->type == 1) // home delivery
            {
                $service_type = $product_cart->ServiceType->type;
                $price_card_detail_id = NULL;
                if ($calling_from != "delete_cart") {
                    // in case of demo user order_pickup point will be same as user current location
                    if ($product_cart->User->login_type == 1) {
                        $from = $request->latitude . ',' . $request->longitude;
                    } else {
                        $pickup_lat = $product_cart->BusinessSegment->latitude;
                        $pickup_long = $product_cart->BusinessSegment->longitude;
                        $from = $pickup_lat . ',' . $pickup_long;
                    }

                    $to = $request->latitude . ',' . $request->longitude;
                    $google_key = $product_cart->Merchant->BookingConfiguration->google_key;
                    $units = isset($product_cart->BusinessSegment->CountryArea->Country) && ($product_cart->BusinessSegment->CountryArea->Country['distance_unit'] == 1) ? 'metric' : 'imperial';
                    $google_result = GoogleController::GoogleDistanceAndTime($from, $to, $google_key, $units, false, 'foodCart', $string_file);
                    // distance in meter
                    $user_distance = isset($google_result['distance_in_meter']) ? $google_result['distance_in_meter'] : NULL;
                    // distance in km
                    $product_cart->estimate_distance = isset($google_result['distance']) ? $google_result['distance'] : NULL;
                    $delivery_charge_slabs = $product_cart->PriceCard->PriceCardDetail->where('status', 1)->toArray();
                    if (!empty($user_distance)) {
                        $request->request->add(['for' => 2, 'distance' => $user_distance, 'cart_amount' => $total_cart_amount]);
                        $slab = $this->getDistanceSlab($request, $delivery_charge_slabs);
                        if (isset($slab['id']) && isset($slab['slab_amount'])) {
                            $delivery_charge = $slab['slab_amount'];
                            $price_card_detail_id = $slab['id'];
                        }
                    }
                    $product_cart->delivery_charge = $delivery_charge;
                    $product_cart->save();
                    // just for bill details
                    $product_cart->price_card_detail_id = $price_card_detail_id;
                } else {
                    $delivery_charge = $product_cart->delivery_charge;
                }
            }

            $discount_amount = 0;
            $promocode = "";
            if (!empty($promo_code->id)) {
                if ($promo_code->promoCode == "FIRSTDELFREE") {
                    $discount_amount = $delivery_charge;
                } else {
                    $promo_details = $promo_code;
                    // flat discount promo_code_value_type ==1
                    $discount_amount = $promo_details->promo_code_value;
                    if ($promo_details->promo_code_value_type == 2) {
                        // percentage discount promo_code_value_type == 2
                        $promoMaxAmount = $promo_details->promo_percentage_maximum_discount;
                        $discount_amount = ($total_cart_amount * $discount_amount) / 100;
                        $discount_amount = !empty($promoMaxAmount) && ($discount_amount > $promoMaxAmount) ? $promoMaxAmount : $discount_amount;
                    }
                }
                $promocode = $promo_code->promoCode;
            }
            $product_cart->preferences = $preferences;
            $product_cart->tip_status = $product_cart->Merchant->ApplicationConfiguration->tip_status == 1 ? true : false;
            $product_cart->cart_out_of_stock = $cart_out_of_stock;
            $product_cart->promoCode = $promocode;

            $tax_per = $product_cart->PriceCard->tax;
            if ($tax_per > 0) {
                $total_tax_amount = ($total_cart_amount * $tax_per / 100);
            }


            $final = ($total_cart_amount - $discount_amount) + $total_tax_amount + $delivery_charge + $additional_preference_amount;


            $merchant_cart_commission_amount = 0;
            $business_segment = BusinessSegment::select('commission_method', 'commission', 'full_name', 'address')->find($business_segment_id);
            $commission = $business_segment->commission;
            if ($business_segment->commission_method == 1) {
                if ($total_cart_amount >= $commission) {
                    $merchant_cart_commission_amount = $commission;
                } else {
                    $merchant_cart_commission_amount = $total_cart_amount;
                }
            } elseif ($business_segment->commission_method == 2) {
                // Percentage Commission
                $merchant_cart_commission_amount = ($commission * $total_cart_amount) / 100;
            }
            
            $total_amount = $merchant_helper->TripCalculation($total_cart_amount, $merchant_id, $trip_calculation_method);
            $discount_amount = $merchant_helper->TripCalculation($discount_amount, $merchant_id, $trip_calculation_method);
            $tax_amount = $merchant_helper->TripCalculation($total_tax_amount, $merchant_id, $trip_calculation_method);
            $delivery_charge = $merchant_helper->TripCalculation($delivery_charge, $merchant_id, $trip_calculation_method);
            $final_amount = $merchant_helper->TripCalculation($final, $merchant_id, $trip_calculation_method);
            $application_fee = $merchant_helper->TripCalculation($merchant_cart_commission_amount, $merchant_id, $trip_calculation_method);


            $product_cart->receipt = [
                'currency' => $currency,
                'quantity' => $total_cart_quantity,
                'total_amount' => $merchant_helper->PriceFormat($total_amount, $merchant_id, $format_price, $trip_calculation_method),
                'total_amount_formatted' => $total_amount,
                'discount_amount' => $merchant_helper->PriceFormat($discount_amount, $merchant_id, $format_price, $trip_calculation_method),
                'tax_amount' => $merchant_helper->PriceFormat($tax_amount, $merchant_id, $format_price, $trip_calculation_method),
                'tax_amount_formatted' => $tax_amount,
                'delivery_charge' => $merchant_helper->PriceFormat($delivery_charge, $merchant_id, $format_price, $trip_calculation_method),
                'delivery_charge_formatted' => $delivery_charge,
                'final_amount' => $merchant_helper->PriceFormat($final_amount, $merchant_id, $format_price, $trip_calculation_method),
                'final_amount_without_format' => $final_amount,
                'application_fee' => $merchant_helper->PriceFormat($application_fee, $merchant_id, $format_price, $trip_calculation_method), // commission of merchant for
                'additional_preference_amount' => (string) $additional_preference_amount,
                'additional_preference_amount_formatted' => $merchant_helper->TripCalculation($additional_preference_amount, $merchant_id, $trip_calculation_method),
            ];
            $product_cart->receipt_holder = $this->getPriceDetailHolder($product_cart, $string_file, $currency, $product_cart->receipt, $preferences);;
            $cancel_minutes = 0;
            $cancel_charges = 0;
            $order_cancel_status = false;
            if (!empty($product_cart->price_card_id) && $product_cart->PriceCard->cancel_charges == 1) {
                $order_cancel_status = true;
                $cancel_minutes = $product_cart->PriceCard->cancel_time;
                $cancel_charges = $product_cart->PriceCard->cancel_amount;
            }
            $product_cart->cancel_text = [
                'cancel_order' => $order_cancel_status,
                'header' => trans("$string_file.cancel_text_header"),
                'body' => trans("$string_file.order_cancel_warning", ["time" => $cancel_minutes, "amount" => $cancel_charges])
            ];
            $product_cart->product_details = $arr_cart_product;
            $paymentMethods = $product_cart->PriceCard->CountryArea->PaymentMethod;
            $bookingData = new BookingDataController();
            $options = $bookingData->PaymentOption($paymentMethods, $product_cart->user_id, null, $product_cart->PriceCard->minimum_wallet_amount);
            $product_cart->payment_method = $options;
            $product_cart->store_name = $business_segment->full_name;
            $product_cart->store_address = $business_segment->address;
            $product_cart->service_type = $service_type; // 1 home delivery, 6 self pickup
            $product_cart->promo_code_text = !empty($request->promo_code)? trans("$string_file.apply_promo_applied") : trans("$string_file.apply_promo_code"); 
            $product_cart->active_promo_code = !empty($promocode);
            unset($product_cart->PriceCard);
            unset($product_cart->User);
            unset($product_cart->BusinessSegment);
            unset($product_cart->Merchant);
            unset($product_cart->created_at);
            unset($product_cart->updated_at);
            unset($product_cart->Segment);
            unset($product_cart->ServiceType);

            return $product_cart;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function placeOrder(Request $request)
    {
        $user = $request->user('api');
        $request_fields = [
            'cart_id' => ['required', 'integer', Rule::exists('product_carts', 'id')->where(function ($query) {
            }),],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where(function ($query) {
            }),],
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'service_type_id' => ['required', 'integer', Rule::exists('service_types', 'id')->where(function ($query) {
            }),],
            'address' => 'required',
            'card_id' => 'required_if:payment_method_id,=,2',
            //            'payment_status' => 'required',
        ];

        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            // call area trait to get id of area
            $string_file = $this->getStringFile($user->merchant_id);
            $this->getAreaByLatLong($request, $string_file);
            $promocode = $request->promo_code;
            $promo_code_id = NULL;
            if (!empty($request->promo_code)) {
                $commont_controller = new CommonController();
                $check_promo_code = $commont_controller->checkPromoCode($request);
                if (isset($check_promo_code['status']) && $check_promo_code['status'] == true) {
                    $promocode = $check_promo_code['promo_code'];
                    $promo_code_id = $promocode->id;
                } else {
                    return $check_promo_code;
                }
            }
            //$return_cart = $this->getCartData($request->cart_id, true, "", $promocode,$request);
            $return_cart = $this->getCartData($request->cart_id, true, $promocode, "", $request, $string_file);

            $bs = $return_cart->BusinessSegment;
            $wallet_balance_low = ($bs->wallet_amount <= 0|| empty($bs->wallet_amount));
            if($wallet_balance_low && $bs->Merchant->Configuration->check_wallet_for_order_receiving == 1){
                return $this->failedResponse(trans("$string_file.store_is_closed"));
            }
            //when product is in stock and its variants is in cart but not enabled then return out of stock for variant
            $product_variant_not_available = [];
            foreach($return_cart->product_details as $product_variant){
                if($product_variant['variant_status'] == 2)
                return $this->successResponse(trans("$string_file.cart_out_of_stock"), $return_cart);
            }
            //Subscription based
            $business_segment_id = $return_cart->business_segment_id;
            $bs = BusinessSegment::find($business_segment_id);
            if($bs->order_based_on == 2){
               if(isset($bs->subscription_expired) && $bs->subscription_expired == 1){
                   $membershipPlanId = $bs->membership_plan_id;
                   $storePlan = MerchantMembershipPlan::find($membershipPlanId);
                   $subscriptionDate = $bs->subscription_date_timestamp;
                   if($storePlan->plan_type == 1){
                       return $this->failedResponse(trans("$string_file.subscription_time_expired"));
                   }elseif($storePlan->plan_type == 2){
                       $all_orders = Order::select('id','business_segment_id','merchant_id','service_time_slot_detail_id','order_timestamp','country_area_id','driver_id','order_status','segment_id','user_id','order_date')
                               ->whereIn('order_status', [1])->where('merchant_id',$user->merchant_id)->where('order_timestamp','>',$subscriptionDate)->get();
                       if($storePlan->number_of_order && count($all_orders) > $storePlan->number_of_order){
                           return $this->failedResponse(trans("$string_file.subscription_order_expired"));
                       }else{
                           return $this->failedResponse(trans("$string_file.subscription_time_expired"));
                       }
                   }
               }else{
                   $membershipPlanId = $bs->membership_plan_id;
                   $storePlan = MerchantMembershipPlan::find($membershipPlanId);
                   $subscriptionDate = $bs->subscription_date_timestamp;
                    if(!empty($subscriptionDate)){
                        $all_orders = Order::select('id','business_segment_id','merchant_id','service_time_slot_detail_id','order_timestamp','country_area_id','driver_id','order_status','segment_id','user_id','order_date')
                                ->whereIn('order_status', [1])->where('merchant_id',$user->merchant_id)->where('order_timestamp','>',$subscriptionDate)->get();
                    
                        if($storePlan->plan_type == 2 && $storePlan->number_of_order !=0 && count($all_orders) > $storePlan->number_of_order){
                            $bs->subscription_expired = 1;
                            $bs->save();
                            return $this->failedResponse(trans("$string_file.subscription_order_expired"));
                        }
                    }else{
                        return $this->failedResponse(trans("$string_file.purchase_subscription"));
                    }
               }
           }
           
           //check hte store is open or closed
           // only setting timezone
           date_default_timezone_set($bs->CountryArea['timezone']);
           $current_time = date('H:i');
           $is_business_segment_open = false;
           $current_day = date('w');
           $arr_open_time = json_decode($bs->open_time, true);
           $arr_close_time = json_decode($bs->close_time, true);
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
           
           $businessSegmentConfig = BusinessSegmentConfigurations::where('business_segment_id',  $bs->id)->first();
            if(!empty($businessSegmentConfig)){
                $is_open_from_admin = $businessSegmentConfig->is_open == 1 ? true : false;
                $admin_text = $businessSegmentConfig->is_open == 1 ? trans("$string_file.open") : trans("$string_file.close");
            }
           
          if(!$is_business_segment_open || !$is_open_from_admin){
              return $this->failedResponse(trans("$string_file.store_is_closed"));
          }

            // if cart status is out of stock then return cart response in place order api
            if ($return_cart->cart_out_of_stock == true) {

                return $this->successResponse(trans("$string_file.cart_out_of_stock"), $return_cart);
            }

            $service_type_id = $request->service_type_id;
            //                $this->getSegmentService($return_cart->segment_id, $request->merchant_id, 'id');

            // Check driver pricecard is exist or not
            if ($return_cart->ServiceType->type == 1) {
                $price_card_object = new PriceCardController();
                $check_for_driver = $price_card_object->checkFoodGroceryPriceCard("DRIVER", $request->merchant_id, $return_cart->segment_id, $request->area, $service_type_id);
                if (!$check_for_driver) {
                    throw new \Exception(trans("$string_file.driver") . " " . trans("$string_file.price_card") . " " . trans("$string_file.data_not_found"));
                }
            }

            if($bs->minimum_amount > $return_cart['receipt']['final_amount_without_format']){
                throw new \Exception(trans("$string_file.order")." ".trans("$string_file.amount") . " " . trans("$string_file.less_than") . " " . trans("$string_file.minimum")." ".trans("$string_file.amount"));
            }

            $country_area = CountryArea::find($request->area);
            // date_default_timezone_set($country_area->timezone);

            $order = new Order;
            $order->merchant_id = $request->merchant_id;
            $order->card_id = $request->card_id;
            $order->user_id = $user->id;
            $order->segment_id = $return_cart->segment_id;
            $order->order_status = 1; //order placed
            $order->country_area_id = $request->area;
            $order->payment_option_id = $request->payment_option_id;
            $order->payment_status = !empty($request->payment_status) ? $request->payment_status : 2;
            $order->service_type_id = $service_type_id;
            $order->delivery_mode = $request->delivery_mode; // contactless 1 , 2 default
            $cart_amount = $return_cart['receipt'];

//            $final_amount = $cart_amount['final_amount'];
            $final_amount = $cart_amount['final_amount_without_format'];
            if ($request->payment_method_id == 3) {
                $common_controller = new CommonController;
                $common_controller->checkUserWallet($user, $final_amount);
            }

            // Set Promocode in order table
            $order->promo_code_id = $promo_code_id;
            $order->cart_amount = $cart_amount['total_amount_formatted'];
            $order->discount_amount = $cart_amount['discount_amount'];
            $order->tax = $cart_amount['tax_amount_formatted'];
            $order->tip_amount = $request->tip_amount;
            // tip will be added in total amount
            $order->final_amount_paid = $final_amount + $request->tip_amount;
            $order->delivery_amount = $cart_amount['delivery_charge_formatted'];

            $order->payment_method_id = $request->payment_method_id;
            $order->business_segment_id = $return_cart->business_segment_id;

            // for user card
            $order->price_card_id = $return_cart->price_card_id;
            $arr['user'] = ['price_card_detail_id' => $return_cart->price_card_detail_id, 'slab_amount' => $cart_amount['final_amount'], 'distance' => $return_cart->estimate_distance];
            $arr['driver'] = [];
            $order->bill_details = json_encode($arr);

            $order->drop_latitude = $request->latitude;
            $order->drop_longitude = $request->longitude;
            $order->user_address_id = $request->user_address_id;
            // we should store drop location if get address id
            $drop_location = $request->address;
            if (empty($request->address)) {
                $drop_location = "";
                if (!empty($request->user_address_id)) {
                    $user_address = UserAddress::Find($request->user_address_id);
                    $drop_location = $user_address->house_name . ',' . $user_address->building . ',' . $user_address->address;
                }
            }
            $order->drop_location = $drop_location;
            $order->additional_notes = $request->additional_notes;
            $order->estimate_amount = 0;
            $order->order_timestamp = time();
            $order->order_date = Carbon::now()->setTimeZone($bs->CountryArea->timezone)->format('Y-m-d');
            $order->quantity = $cart_amount['quantity'];
            $order->packaging_preferences = $return_cart->packaging_preferences;

            // will do later, its bill details
            //$parameter[] = array('parameter' => "", 'parameterType' => "", 'amount' => "");
            $order->save();
            $this->saveOrderStatusHistory($request, $order);
            $arr_ordered_product = $return_cart->product_details;
            foreach ($arr_ordered_product as $product) {
                $product_obj = new OrderDetail;
                $product_obj->order_id = $order->id;
                $product_obj->product_id = $product['product_id'];
                $product_obj->weight_unit_id = $product['weight_unit_id'];
                $product_obj->product_variant_id = $product['product_variant_id'];
                $product_obj->options = isset($product['arr_option']) && !empty($product['arr_option']) ? json_encode($product['arr_option']) : NULL;
                $product_obj->quantity = $product['quantity'];
                $product_obj->price = $product['product_price'];
                $product_obj->discount = 0;
                //                $product_obj->tax = $product['tax'];
                //                $product_obj->tax_amount = $product['tax_amount'];
                $product_obj->total_amount = $product['total_price'];
                $product_obj->save();

                // manage product inventory
                if ($product['manage_inventory'] == 1) {
                    $request->request->add([
                        'order_id' => $product_obj->order_id,
                        'product_id' => $product_obj->product_id,
                        'id' => $product_obj->product_variant_id,
                        'new_stock' => $product_obj->quantity,
                        'stock_type' => 2,
                        'stock_out_id' => $product_obj->id,
                    ]);
                    $this->manageProductVariantInventory($request);
                }
            }

            // In case of Non-Cash payment method, do payment first
            if ($request->payment_method_id != 1) {
                $payment = new Payment();
                $array_param = array(
                    'order_id' => $order->id,
                    'payment_option_id' => $order->payment_option_id,
                    'payment_method_id' => $order->payment_method_id,
                    'amount' => $order->final_amount_paid,
                    'user_id' => $order->user_id,
                    'card_id' => $order->card_id,
                    'currency' => isset($order->User->Country) ? $order->User->Country->isoCode : "",
                    'quantity' => $order->quantity,
                    'order_name' => $order->merchant_order_id,
                    'booking_transaction'=> $order->OrderTransaction,
                    'ewallet_user_otp_pin' => $request->otp_pin, // for amole payment gateway
                    'ewallet_pin_expire' => $request->pin_expire_date,
                    'phone_card_no' => $request->phone_card_no,
                );
                $payment_status = $payment->MakePayment($array_param);
                // payment done successfully
                if ($payment_status) {
                    $order->payment_status = 1; // means payment done while order place
                    $order->save();
                }
            }

            $success_message = $return_cart->ServiceType->type == 6 ? trans("$string_file.self_pickup_order_place") : trans("$string_file.order_placed");

            $product_cart = ProductCart::Find($request->cart_id);


            if ($product_cart->ServiceType->type == 1) {
                $business_seg = BusinessSegment::select('id', 'order_request_receiver', 'segment_id', 'merchant_id', 'latitude', 'longitude', 'delivery_service')->Find($product_cart->business_segment_id);
                $arr_agency_id = []; // we can check
                $delivery_service = $business_seg->delivery_service;
                if ($delivery_service == 2) {
                    $arr_agency_id = $business_seg->DriverAgency->pluck('id')->toArray();
                }
                // send notification to driver is configuration is set to direct driver
                if (!empty($business_seg->order_request_receiver) && $business_seg->order_request_receiver == 2) {
                    $request->request->add([
                        'latitude' => $business_seg->latitude, 'longitude' => $business_seg->longitude,
                        'merchant_id' => $business_seg->merchant_id, 'segment_id' => $business_seg->segment_id, 'arr_agency_id' => $arr_agency_id
                    ]);
                    $this->orderAcceptNotification($request, $order);
                } else {
                    $success_message = trans("$string_file.later_order_placed");
                }
            }
            //            else
            //            {
            $this->sendPushNotificationToWeb($request, $order);
            //            }
            // delete cart
            $product_cart->delete();

            // Send mail to merchant as well as to restro
            $this->sendNewOrderMail($order);

            //send onesignal message to restro
            $data = array('order_id' => $order->id, 'order_number' => $order->merchant_order_id, 'notification_type' => 'ORDER_PLACED', 'segment_type' => $order->Segment->slag, 'order_number' => $order->merchant_order_id);
            $arr_param = array(
                'business_segment_id' => $order->business_segment_id,
                'data' => $data,
                'message' => trans("$string_file.new_order_driver_message"),
                'merchant_id' => $order->merchant_id,
                'title' => trans("$string_file.order_placed_title")
            );
            Onesignal::BusinessSegmentPushMessage($arr_param);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        $data = ['order_id' => $order->id, 'order_status' => $order->order_status];
        return $this->successResponse($success_message, $data);
    }

    public function getOrders(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $merchant_id = $user->merchant_id;
        $req_param['merchant_id'] = $merchant_id;
        $config_status = $this->getOrderStatus($req_param);
        $currency = isset($user->Country) ? $user->Country->isoCode : "";
        $merchant_helper = new Merchant();
        $request_fields = [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'type' => 'required', // 1 for schedule 2 ongoing 3 for past and rejected
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $segment_id = $request->segment_id;
            $user_id = $user->id;
            $order_status = [];
            if ($request->type == 2) {
                $order_status = [1, 6, 7, 9, 10];
            } elseif ($request->type == 3) {
                $order_status = [11, 3, 2];
            }
            $orders = Order::select('business_segment_id', 'country_area_id', 'merchant_id', 'id', 'merchant_order_id', 'payment_method_id', 'final_amount_paid', 'created_at', 'order_status', 'quantity')
                ->with(['BusinessSegment' => function ($q) {
                    $q->addSelect('id', 'full_name', 'business_logo', 'address');
                }])
                ->with(['PaymentMethod' => function ($q) {
                    $q->addSelect('id', 'payment_method');
                }])
                ->whereIn('order_status', $order_status)
                ->where([['segment_id', '=', $segment_id], ['user_id', '=', $user_id]])
                ->orderBy('created_at', 'DESC')
                ->get();

            $orders = $orders->map(function ($order, $key) use ($currency, $config_status,$merchant_helper) {
                $merchant_id = $order->merchant_id;
                $date = new DateTime($order->created_at);
                // $date = new DateTime($order->order_timestamp);
                // $date->setTimezone(new DateTimeZone($order->CountryArea->timezone));
                return [
                    'order_id' => $order->id,
                    'restaurant_name' => $order->BusinessSegment->full_name,
                    'restaurant_address' => $order->BusinessSegment->address,
                    'restaurant_logo' => get_image($order->BusinessSegment->business_logo, 'business_logo', $merchant_id),
                    'order_date' => $date->format('H:i D, d-m-Y'),
                    'total_items' => $order->quantity,
                    'currency' => "$currency",
                    // 'total_amount' => $order->final_amount_paid,
                    'total_amount' => $merchant_helper->PriceFormat($order->final_amount_paid, $merchant_id),
                    'order_status' => $config_status[$order->order_status],
                    //                    'product_data' => $product_data,
                ];
            });
            return $this->successResponse(trans("$string_file.data_found"), $orders);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function getOrderDetails(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $merchant_id = $user->merchant_id;
        $req_param['merchant_id'] = $merchant_id;
        $config_status = $this->getOrderStatus($req_param);
        $currency = isset($user->Country) ? $user->Country->isoCode : "";
        $merchant_helper = new Merchant();
        $trip_calculation_method = $user->Merchant->Configuration->trip_calculation_method;
        $format_price = $user->Merchant->Configuration->format_price;
        $request_fields = [
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->where(function ($query) {
            })],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $order = Order::select('business_segment_id', 'country_area_id', 'price_card_id', 'merchant_id', 'id', 'merchant_order_id', 'drop_location', 'user_address_id', 'payment_method_id', 'promo_code_id', 'final_amount_paid', 'tax', 'discount_amount', 'cart_amount', 'delivery_amount', 'created_at', 'order_status', 'quantity', 'tip_amount', 'order_status_history', 'service_type_id', 'otp_for_pickup', 'delivery_image', 'packaging_preferences')
                ->with(['BusinessSegment' => function ($q) {
                    $q->addSelect('id', 'address','latitude','longitude');
                }])
                ->with(['OrderDetail' => function ($q) {
                    $q->addSelect('id', 'order_id', 'product_id', 'product_variant_id', 'weight_unit_id', 'quantity', 'price', 'discount', 'total_amount');
                }])
                ->with(['OrderDetail.Product' => function ($q) {
                }])
                ->with(['PaymentMethod' => function ($q) {
                    $q->addSelect('id', 'payment_method');
                }])
                ->where('id', $request->order_id)
                ->first();
            $arr_option_amount = [];

            $merchant_id = $order->merchant_id;
            $total_product_discount = 0;
            $product_data = $order->OrderDetail->map(function ($product, $key) use ($merchant_id, $currency,$format_price,$merchant_helper, $total_product_discount,$trip_calculation_method) {
                $unit = isset($product->weight_unit_id) ? $product->WeightUnit->WeightUnitName : "";
                $quantity = $product->quantity;
                $arr_option = !empty($product->options) ? json_decode($product->options, true) : [];
                $product_option =  !empty($arr_option) ? array_sum(array_column($arr_option, 'amount')) : 0;
                $arr_option_amount[] = $product_option;
                $product_price = $product->price;
                $product_discount = (!empty($product->ProductVariant->discount)  && $product->ProductVariant->discount > 0) ? $product->ProductVariant->discount : NULL;
                $total_product_discount = $total_product_discount + $product_discount;
                $product_total_price = (($product_price - $product_discount) + $product_option) * $quantity;
                $discounted_price = !empty($product_discount) ? ($product_price - $product_discount) : "";
                return [
                    'title' => $product->Product->Name($merchant_id),
                    'variant_title' => $product->ProductVariant->Name($merchant_id),
                    'image' => get_image($product->Product->product_cover_image, 'product_cover_image', $merchant_id),
                    'quantity' => $quantity,
                    'weight_unit' => $product->ProductVariant->weight . ' ' . $unit,
                    'food_type' => $product->Product->food_type,
                    // 'total_price' => $merchant_helper->PriceFormat(round_number($product->price + $product_option), $merchant_id),
                    'total_price' => $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_total_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method),
                    'product_price' => $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method),
                    // 'product_price' => $merchant_helper->PriceFormat(round_number($product->ProductVariant->product_price), $merchant_id),
                    'discount' => !empty($product_discount) ? $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_discount, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method) : "",
                    'discounted_price' => !empty($discounted_price) ? $merchant_helper->PriceFormat($merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method) : "",
                    'total_product_price' => $merchant_helper->PriceFormat(round_number($product->quantity * round_number($product->ProductVariant->product_price)), $merchant_id),
                    'arr_option' => $arr_option,

                ];
            });

            $order_cancel_status = false;
            $order_eligible_for_cancel = [1, 6, 7];
            $cancel_minutes = 0;
            $cancel_charges = 0;
            $status_history = json_decode($order->order_status_history, true);

            $is_order_status_nine = false;
            foreach ($status_history as $status_hst) {
                if ($status_hst['order_status'] == 9) {
                    $is_order_status_nine = true;
                    break;
                }
            }
            if (in_array($order->order_status, $order_eligible_for_cancel) && !$is_order_status_nine && $order->order_status != 2) {
                if (isset($order->PriceCard) && $order->PriceCard->cancel_charges == 1 && $order->payment_method_id == 1) {
                    $order_cancel_status = true;
                    $cancel_minutes = $order->PriceCard->cancel_time;
                    $cancel_charges = $order->PriceCard->cancel_amount;
                }
            }
            // p($order_cancel_status);
            $self_pickup = $order->ServiceType->type == 6 ? true : false;
            $date = new DateTime($order->order_timestamp);
            $date->setTimezone(new DateTimeZone($order->CountryArea->timezone));
            $option_amount = array_sum($arr_option_amount);
            $cart_amount = round_number($order->cart_amount + $option_amount);
            $receipt_holder_data  = [
                'total_amount_formatted' => $merchant_helper->PriceFormat($cart_amount,  $merchant_id, $format_price, $trip_calculation_method),
                'tax_amount_formatted' => !empty($order->tax) ? $merchant_helper->PriceFormat($order->tax, $merchant_id) : "",
                'discount_amount' => $merchant_helper->PriceFormat($order->discount_amount, $merchant_id, $format_price, $trip_calculation_method),
                'delivery_charge_formatted' => $merchant_helper->PriceFormat($order->delivery_amount, $merchant_id,$format_price, $trip_calculation_method),
                'final_amount_formatted' => (string)$merchant_helper->PriceFormat($order->final_amount_paid, $merchant_id, $format_price, $trip_calculation_method),
                'tip_amount' => !empty($order->tip_amount) ? "$order->tip_amount" : "",
                'time_charges'=> ""
                ];
            $preferences = !empty($order->packaging_preferences)? json_decode($order->packaging_preferences, true): [];
            $order_details =  [
                'order_id' => $order->id,
                'order_no' => $order->merchant_order_id,
                'pickup' => $order->BusinessSegment->address,
                'drop_off' => $order->drop_location,
                'latitude'=> $order->BusinessSegment->latitude,
                'longitude'=> $order->BusinessSegment->longitude,
                'self_pickup'=> $self_pickup,
                'order_date' => $date->format('H:i, d-m-Y'),
                'total_items' => $order->quantity,
                'currency' => "$currency",
                'option_amount' => "$option_amount",
                'order_status' => $config_status[$order->order_status],
                'product_data' => $product_data,
                'time_charges_enable' => false,
                'time_charges_placeholder' => "",
                'otp_for_pickup' => $self_pickup && !empty($order->otp_for_pickup) ? $order->otp_for_pickup : "",
                'receipt' => [
                    'cart_amount' => $merchant_helper->PriceFormat($cart_amount, $merchant_id),
                    'total_amount' => $merchant_helper->PriceFormat((string)$order->final_amount_paid, $merchant_id),
                    'delivery_amount' => !empty($order->delivery_amount) ? $merchant_helper->PriceFormat((string)$order->delivery_amount, $merchant_id) : "0.00",
                    'tax' => !empty($order->tax) ? "$order->tax" : "",
                    'tip_amount' => !empty($order->tip_amount) ? "$order->tip_amount" : "",
                    'time_charges' => "",
                    'discount_amount' => "$order->discount_amount",
                ],
                'cancel_receipt' => HolderController::userOrderCancelHolder($order, $string_file),
                'receipt_holder' => $this->getPriceDetailHolder($order, $string_file, $currency, $receipt_holder_data ,$preferences),
                'arr_action' => [
                    'tracking' => !$self_pickup && in_array($order->order_status, [6, 7, 9, 10]) ? true : false,
                    'cancel_order' => $order_cancel_status,
                    'cancel_text' => trans("$string_file.order_cancel_warning", ["time" => $cancel_minutes, "amount" => (isset($order->CountryArea->Country) ? $order->CountryArea->Country->isoCode : "") . " " . $cancel_charges])
                ],
                'delivery_image'=> !empty($order->delivery_image)? get_image($order->delivery_image, 'booking_images', $order->merchant_id) : "",
            ];
            return $this->successResponse(trans("$string_file.data_found"), $order_details);
        } catch (\Exception $e) {
            // p($e->getTraceAsString());
            return $this->failedResponse($e->getMessage());
        }
    }

    public function applyRemovePromoCode(Request $request)
    {
        $user = $request->user('api');
        $request_fields = [
            'cart_id' => ['required', 'integer', Rule::exists('product_carts', 'id')->where(function ($query) {
            }),],
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            // call area trait to get id of area
            $string_file = $this->getStringFile($user->merchant_id);
            $this->getAreaByLatLong($request, $string_file);


            $promocode = NULL;
            if (isset($request->promo_code) && !empty($request->promo_code)) {
                $cart = ProductCart::select('cart_amount', 'id')->find($request->cart_id);
                $request->merge(['order_amount' => $cart->cart_amount]);
                $common_controller = new CommonController();
                $check_promo_code = $common_controller->checkPromoCode($request);
                if (isset($check_promo_code['status']) && $check_promo_code['status']) {
                    $promocode = $check_promo_code['promo_code'];
                } else {
                    return $check_promo_code;
                }
            }
            $return_cart = $this->getCartData($request->cart_id, true, $promocode, "", $request, $string_file);
            // if cart status is out of stock then return cart response in place order api
            if ($return_cart->cart_out_of_stock == true) {
                return $this->successResponse(trans("$string_file.cart_out_of_stock"), $return_cart);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            $failed = $this->failedResponse($message);
            $failedData = json_decode(json_encode($failed),true)['original'];
            $failedData['data'] = (object)[];
            return $failedData;
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.promo_code_applied"), $return_cart);
    }


    // Favourite Business Segments of user
    public function favouriteBusinessSegment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_segment_id' => 'required|integer',
            'segment_id' => 'required|integer',
            'action' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $user = $request->user('api');
        $user_id = $user->id;
        $string_file = $this->getStringFile();
        if ($request->action == 1) // add / update
        {
            FavouriteBusinessSegment::updateOrCreate(
                ['user_id' => $user_id, 'business_segment_id' => $request->business_segment_id],
                ['segment_id' => $request->segment_id, 'merchant_id' => $request->merchant_id]
            );
        } elseif ($request->action == 2) // delete
        {
            //            $driver = (object)[];
            FavouriteBusinessSegment::where([['user_id', '=', $user_id], ['business_segment_id', '=', $request->business_segment_id], ['segment_id', '=', $request->segment_id]])->delete();
        }
        $message = trans("$string_file.favourite")." ".trans("$string_file.store")." ";
        $message .= $request->action == 1 ? trans("$string_file.added_successfully") : trans("$string_file.deleted_successfully");
        return $this->successResponse($message);
//        return response()->json(['result' => "1", 'message' => trans("$string_file.favourite"), 'data' => []]);
    }

    public function getFavouriteBusinessSegment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => 'required',
            //            'area_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $user_id = $user->id;
        $merchant_id = $user->merchant_id;


        try {
            // call area trait to get id of area
            $this->getAreaByLatLong($request, $string_file);
        } catch (\Exception $e) {

            return $this->failedResponse($e->getMessage(), []);
        }

        //        $arr_driver = FavouriteBusinessSegment::select('id', 'business_segment_id')->where([['user_id', '=', $user_id], ['segment_id', '=', $request->segment_id]
        //        ])->with(['BusinessSegment' => function ($q) {
        //            $q->select("id", "first_name", "last_name", "phoneNumber", "email", "profile_image", "rating");
        //        }])
        //            ->get();


        $user = $request->user('api');
        $distance = $user->Merchant->BookingConfiguration->store_radius_from_user;
        $google_key = $user->Merchant->BookingConfiguration->google_key;
        $request->request->add(['user_id' => $user_id, 'is_favourite' => "YES", 'distance' => $distance]);
        $arr_restaurant = $this->getMerchantBusinessSegment($request);
        $google = new GoogleController;
        $user_lat = $request->latitude;
        $user_long = $request->longitude;

        $unit = isset($user->Country) ? $user->Country->distance_unit : "";




        $fav_restaurant_res = $arr_restaurant->map(function ($item, $key) use (
            $merchant_id,
            $google,
            $user_lat,
            $user_long,
            $unit,
            $string_file,
            $google_key
        ) {
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

            $arr_style =   $item->StyleManagement->map(function ($style) use ($merchant_id, $unit, $string_file) {
                return ['style_name' => $style->Name($merchant_id)];
            });

            $store_lat = $item->latitude;
            $store_long = $item->longitude;
            $distance_from_user = $google->arialDistance($user_lat, $user_long, $store_lat, $store_long, $unit, $string_file, true, true);
            // $user_drop_location[0] = [
            //     'drop_latitude' => $user_lat,
            //     'drop_longitude' => $user_long,
            //     'drop_location' => ""
            // ];
            // $distance_from_user = GoogleController::GoogleStaticImageAndDistance($store_lat, $store_long, $user_drop_location, $google_key, "", $string_file);
            // $distance_from_user = isset($distance_from_user['total_distance_text']) ? $distance_from_user['total_distance_text'] : "";
            return array(
                'business_segment_id' => $item->id,
                'title' => $item->full_name,
                'time' => "$item->delivery_time " . trans("$string_file.minute"),
                'amount' => !empty($item->minimum_amount) ? "$item->minimum_amount" : "",
                'amount_for' => !empty($item->minimum_amount_for) ? "$item->minimum_amount_for" : "",
                'currency' => isset($item->Country) ? $item->Country->isoCode : '',
                'style' => array_pluck($arr_style, 'style_name'), //array_pluck($item->StyleManagement, 'style_name'),
                'open_time' => $open_time,
                'close_time' => $close_time,
                'rating' => !empty($item->rating) ? $item->rating : "2.5",
                'distance' => trans("$string_file.distance") . ' ' . $distance_from_user,
                'image' => get_image($item->business_logo, 'business_logo', $merchant_id),
                'is_business_segment_open' => $is_business_segment_open,
            );
        });
        return $this->successResponse(trans("$string_file.data_found"), $fav_restaurant_res);
    }

    // ssearch restaurant with products
    public function searchStoreProducts(Request $request)
    {
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $this->getAreaByLatLong($request, $string_file);
            $distance = $user->Merchant->BookingConfiguration->store_radius_from_user;
            $request->merge(['distance' => $distance]);

            $result_data =  $this->getBusinessSegmentWithProducts($request);
            return $this->successResponse(trans("$string_file.data_found"), $result_data);
        } catch (Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
    }

    public function TableBooking(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $request_fields = [
            'business_segment_id' => ['required', 'integer', Rule::exists('business_segments', 'id')->where(function ($query) {
            }),],
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'service_type_id' => ['required', 'integer', Rule::exists('service_types', 'id')->where(function ($query) {
            }),],
            // 'country_area_id' => ['required', 'integer', Rule::exists('country_areas', 'id')->where(function ($query) {
            // }),],
            'no_of_tables' => 'required'
        ];

        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {
            $business_segment = BusinessSegment::find($request->business_segment_id);
            $table_booking = new TableBooking;
            $table_booking->merchant_id = $user->merchant_id;
            $table_booking->segment_id = $request->segment_id;
            $table_booking->business_segment_id = $request->business_segment_id;
            $table_booking->country_area_id = $business_segment->country_area_id;
            $table_booking->service_type_id = $request->service_type_id;
            $table_booking->user_id = $user->id;
            $table_booking->no_of_tables = $request->no_of_tables;
            $table_booking->status = 1;
            $table_booking->save();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        $data = ['table_booking_id' => $table_booking->id, 'booking_status' => $table_booking->status];
        return $this->successResponse(trans("$string_file.success"), $data);
    }

    public function savePackagingPreferences(Request $request){
        $user = $request->user('api');
        $request_fields = [
            'cart_id' => ['required', 'integer', Rule::exists('product_carts', 'id')->where(function ($query) {
            }),],
            'packaging_preferences' => ['required'],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $string_file = $this->getStringFile($user->merchant_id);
            $this->getAreaByLatLong($request, $string_file);
            $return_cart = $this->getCartData($request->cart_id, true, NULL, "savePackagingPreferences", $request, $string_file);
            if ($return_cart->cart_out_of_stock == true) {
                return $this->successResponse(trans("$string_file.cart_out_of_stock"), $return_cart);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            $failed = $this->failedResponse($message);
            $failedData = json_decode(json_encode($failed),true)['original'];
            $failedData['data'] = (object)[];
            return $failedData;
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.promo_code_applied"), $return_cart);
    }

    //    // ssearch restaurant with products
    //    public function searchStoreProducts(Request $request)
    //    {
    //        try {
    //            $user = $request->user('api');
    //            $string_file = $this->getStringFile(NULL, $user->Merchant);
    //            $this->getAreaByLatLong($request, $string_file);
    //            $distance = $user->Merchant->BookingConfiguration->store_radius_from_user;
    //            $request->request->add(['distance' => $distance]);
    //
    //            $result_data =  $this->getBusinessSegmentWithProducts($request);
    //            return $this->successResponse(trans("$string_file.data_found"), $result_data);
    //        } catch (Exception $e) {
    //            $message = $e->getMessage();
    //            return $this->failedResponse($message);
    //        }
    //    }
}
