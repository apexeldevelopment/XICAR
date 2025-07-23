<?php

namespace App\Http\Controllers\Api;

use DateTime;
use DateTimeZone;
use Carbon\Carbon;
use App\Models\Brand;
use App\Models\Event;
use App\Models\Category;
use App\Models\Onesignal;
use App\Models\PriceCard;
use App\Models\PromoCode;
use App\Traits\AreaTrait;
use App\Traits\ImageTrait;
use App\Traits\OrderTrait;
use App\Models\CountryArea;
use App\Models\ProductCart;
use App\Models\UserAddress;
use App\Traits\BannerTrait;
use App\Traits\ProductTrait;
use Illuminate\Http\Request;
use App\Traits\MerchantTrait;
use App\Models\ServiceTimeSlot;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BusinessSegment\Order;
use App\Models\ServiceTimeSlotDetail;
use App\Models\UserSubscriptionRecord;
use App\Models\BusinessSegment\Product;
use App\Models\FavouriteBusinessSegment;
use App\Http\Controllers\Helper\Merchant;
use Illuminate\Support\Facades\Validator;
use App\Models\BusinessSegment\OrderDetail;
use App\Http\Controllers\Api\CommonController;
use App\Models\BusinessSegment\ProductVariant;
use App\Models\BusinessSegment\BusinessSegment;
use App\Http\Controllers\PaymentMethods\Payment;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\HolderController;
use App\Models\BusinessSegment\PackagingPreference;
use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Merchant\PriceCardController;

class GroceryController extends Controller
{
    // get home screen data of food app
    use BannerTrait, ApiResponseTrait, ProductTrait, OrderTrait, AreaTrait, ImageTrait;
    public function homeScreen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {}),],
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            // call area trait to get id of area
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $this->getAreaByLatLong($request, $string_file);
            $request->merge(['calling_from' => "home_screen"]);
            $data = $this->homeCategoryScreen($request);
            if ($data['store'] == false) {
                return $this->failedResponse(trans("$string_file.no_stores_within_radius"));
            }
            $return_data['response_data'] = $data['data'];
            $multi_store = $data['status'] == "multi_store" ? true : false;
            $return_data['multi_store'] = $multi_store;
            $return_data['business_segment_id'] = $data['business_segment_id'];
            $return_data['next_page_url'] = $data['next_page_url'];
            $return_data['total_pages'] = $data['total_pages'];
            $return_data['current_page'] = $data['current_page'];
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }

        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    public function getCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {}),],
            'business_segment_id' => 'required_if:multi_store,==,1', // multi-store 1 means multiple store 2 means single store
            'latitude' => 'required_if:multi_store,==,2',
            'longitude' => 'required_if:multi_store,==,2',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        if (!empty($request->latitude) && !empty($request->longitude)) {
            // set area so we can get business segment id in case of website calling and not sending bs id
            $this->getAreaByLatLong($request, $string_file);
        }
        if (isset($request->is_website_calling) && $request->is_website_calling == true) {
            $request->merge(['calling_from' => "website_screen"]);
        } else {
            $request->merge(['calling_from' => "category_screen"]);
        }
        $data = $this->homeCategoryScreen($request);
        $return_data['response_data'] = $data['data'];
        $fav = FavouriteBusinessSegment::select('id')->where([['user_id', '=', $user->id], ['segment_id', '=', $request->segment_id], ['business_segment_id', '=', $request->business_segment_id]])->first();
        $return_data['if_fav'] = !empty($fav->id) ? true : false;

        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    function homeCategoryScreen($request)
    {
        $user = $request->user('api');
        $is_search = isset($request->is_search) ? $request->is_search : NULL;
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $segment_id = $request->segment_id;
        $calling_from = $request->calling_from;
        $response = "";
        $business_segment_id = NULL;
        $store = 0;
        $arr_popular_restaurant = [];

        $config = $user->Merchant->Configuration;
        $category_type_view_config = false;
        if (isset($config->category_type_view) && $config->category_type_view == 1) {
            $category_type_view_config = true;
        }

        if (empty($request->business_segment_id)) {
            $distance = $user->Merchant->BookingConfiguration->store_radius_from_user;
            $request->merge(['distance' => $distance]);
            $arr_restaurant = $this->getMerchantBusinessSegment($request);

            // GET POPULAR STORES
            $request->merge(['is_popular' => "YES"]);
            $arr_popular_restaurant = $this->getMerchantBusinessSegment($request);

            $request->merge(['business_segment_id' => array_pluck($arr_restaurant, 'id')]);
            $store = $arr_restaurant->count();
        }
        //        $arr_restaurant = $this->getMerchantBusinessSegment($request);
        //        $request->request->add(['business_segment_id'=>array_pluck($arr_restaurant,'id')]);
        //        $store = $arr_restaurant->count();
        if ($calling_from == "home_screen") {
            // if pagination has one store then it should work
            if ($store > 1 || $is_search == 1 || ($request->page > 1)) {
                $holder_item = ["BANNER", "STORE", "POPULAR_STORE"]; // multi_store
                $response = "multi_store";
            } else {
                $holder_item = ["BANNER", "CATEGORY", "PRODUCT"]; //
                $response = "single_store";
                if ($category_type_view_config) {
                    array_push($holder_item, "EVENT", "EVENT_TWO", "BRAND", "TOP_SELLER");
                }
                // app need busniness segment id in case of single store
                $business_segment_id =  $store == 1 ?  $arr_restaurant[0]->id : NULL;
                // $arr_restaurant->;
                // array_pluck($arr_restaurant,'id');
            }
        } elseif ($calling_from == "category_screen") {
            // if store is multiple then we have to open next screen as category of store
            $holder_item = ["BANNER", "CATEGORY"]; // category_screen
            $response = "category_screen";
        } elseif ($calling_from == 'website_screen') {
            $holder_item = ["BANNER", "CATEGORY"]; // category_screen
            $response = "category_screen";
        }
        //p($holder_item);
        // get banner list for holder
        $banner_res = [];
        if ($user->Merchant->advertisement_module == 1 && in_array("BANNER", $holder_item) && $is_search != 1) {
            $request->merge(['merchant_id' => $merchant_id, 'home_screen' => NULL, 'segment_id' => $segment_id, 'banner_for' => 1]);
            // made this changes because in db banner does not have business_segment_id value
            $arr_business_segment = [];
            if (is_array($request->business_segment_id)) {
                $arr_business_segment = $request->business_segment_id;
                $request->merge(['business_segment_id' => NULL]);
            }
            $arr_banner = $this->getMerchantBanner($request);
            if (!empty($arr_business_segment)) {
                $request->merge(['business_segment_id' => $arr_business_segment]);
            }
            if (isset($request->business_segment_id) && !empty($request->business_segment_id)) {
                $business_arr_banner = $arr_banner->whereIn('business_segment_id', $request->business_segment_id);
                if ($business_arr_banner->count() > 0) {
                    $arr_banner = $business_arr_banner;
                } else {
                    $arr_banner = $arr_banner->where('business_segment_id', '=', NULL);
                }
                $arr_banner = $arr_banner->values();
            }
            //p($arr_banner);
            $banner_res['cell_title'] = 'BANNER_CELL';
            $banner_res['cell_contents'] = $arr_banner->map(function ($item, $key) use ($merchant_id) {
                $redirect_sub_category = false;
                if (isset($item->action_type) && $item->action_type == "CATEGORY" && !empty($item->category_id)) {
                    $redirect_sub_category = Category::where("category_parent_id", $item->category_id)->get()->count();
                    $redirect_sub_category = $redirect_sub_category > 0 ? true : false;
                }

                $image = get_image($item->banner_images, 'banners', $merchant_id);

                $return = array(
                    'id' => $item->id,
                    'business_segment_id' => $item->business_segment_id,
                    'title' => $item->banner_name,
                    'image' => $image,
                    'image_width' => !empty($item->image_width) ? $item->image_width : 1000,
                    'image_height' => !empty($item->image_height) ? $item->image_height : 500,
                    'action_type' => !empty($item->action_type) ? $item->action_type : "",
                    'redirect_url' => !empty($item->redirect_url) ? $item->redirect_url : "",
                    'redirect_product_id' => !empty($item->product_id) ? $item->product_id : null,
                    'redirect_category_id' => !empty($item->category_id) ? $item->category_id : null,
                    'redirect_sub_category' => $redirect_sub_category,
                );
                if (!empty($item->BusinessSegment)) {
                    $return['name']  = $item->BusinessSegment->full_name;
                }
                return $return;
            });
        }
        //  get store list for holder
        if (in_array("STORE", $holder_item)) {
            $arr_store = $arr_restaurant;
            $restaurant_res = $arr_restaurant->toArray();
            $next_page_url = $restaurant_res['next_page_url'];
            $total_pages = $restaurant_res['last_page'];
            $current_page = $restaurant_res['current_page'];
            //$this->getMerchantBusinessSegment($request);
            $store_heading['cell_title'] = 'TITLE';
            $store_heading['cell_contents'][0] = ['title' => trans("$string_file.all_stores")];

            $store_res['cell_title'] = 'STORE_CELL';
            $google = new GoogleController;
            $user_lat = $request->latitude;
            $user_long = $request->longitude;
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $unit = isset($user->Country) ? $user->Country->distance_unit : '';
            $demo = $user->Merchant->demo;
            $google_key = $user->Merchant->BookingConfiguration->google_key;
            $store_res['cell_contents'] = $arr_store->map(function ($item, $key) use (
                $merchant_id,
                $google,
                $user_lat,
                $user_long,
                $unit,
                $string_file,
                $google_key,
                $demo,
                $user,
                $request
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
                // p($current_time,0);
                // p($open_time,0);
                // p($close_time);
                if ($open_time_n < $current_time_n && $close_time_n > $current_time_n) {
                    $is_business_segment_open = true;
                }

                // if ($open_time < $current_time && $close_time > $current_time) {
                //     $is_business_segment_open = true;
                // }
                $arr_style =   $item->StyleManagement->map(function ($style) use ($merchant_id, $unit) {
                    return ['style_name' => $style->Name($merchant_id)];
                });

                $store_lat = $item->latitude;
                $store_long = $item->longitude;
                // if ($demo ==  1) {
                $distance_from_user = $google->arialDistance($user_lat, $user_long, $store_lat, $store_long, $unit, $string_file, true, true);
                // } else {
                //     $user_drop_location[0] = [
                //         'drop_latitude' => $user_lat,
                //         'drop_longitude' => $user_long,
                //         'drop_location' => ""
                //     ];
                //     $distance_from_user = GoogleController::GoogleStaticImageAndDistance($store_lat, $store_long, $user_drop_location, $google_key, "", $string_file);
                //     $distance_from_user = isset($distance_from_user['total_distance_text']) ? $distance_from_user['total_distance_text'] : "";
                // }
                return array(
                    'business_segment_id' => $item->id,
                    'title' => $item->full_name,
                    'style' => array_pluck($arr_style, 'style_name'),
                    'rating' => !empty($item->rating) ? $item->rating : "2.5",
                    'open_time' => $open_time,
                    'close_time' => $close_time,
                    'image' => get_image($item->business_logo, 'business_logo', $merchant_id),
                    'is_business_segment_open' => $is_business_segment_open,
                    'distance' => trans("$string_file.distance") . ' ' . $distance_from_user,
                    'promo_code' => $item->ActivePromoCode ? $item->ActivePromoCode : (object)[],
                    'is_favourite' => !empty($item->FavouriteBusinessSegment) && $item->FavouriteBusinessSegment->where("user_id", $user->id)->where('segment_id', $request->segment_id)->where('business_segment_id', $item->id)->exists() ? true : false,
                    //                    'is_favourite' => !empty($item->FavouriteBusinessSegment->id) ? true : false,
                );
            });
        }

        //  get popular store list for holder
        if (in_array("POPULAR_STORE", $holder_item)) {
            $arr_store = $arr_popular_restaurant;
            $restaurant_res = $arr_popular_restaurant->toArray();
            $next_page_url = $restaurant_res['next_page_url'];
            $total_pages = $restaurant_res['last_page'];
            $current_page = $restaurant_res['current_page'];
            //$this->getMerchantBusinessSegment($request);
            $popular_store_heading['cell_title'] = 'TITLE';
            $popular_store_heading['cell_contents'][0] = ['title' => trans("$string_file.popular_stores")];

            $popular_store_res['cell_title'] = 'POPULAR_CELL';
            $google = new GoogleController;
            $user_lat = $request->latitude;
            $user_long = $request->longitude;
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $unit = isset($user->Country) ? $user->Country->distance_unit : '';
            $google_key = $user->Merchant->BookingConfiguration->google_key;
            $popular_store_res['cell_contents'] = $arr_store->map(function ($item, $key) use (
                $merchant_id,
                $google,
                $user_lat,
                $user_long,
                $unit,
                $string_file,
                $google_key,
                $user,
                $request
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
                // p($current_time,0);
                // p($open_time,0);
                // p($close_time);
                if ($open_time_n < $current_time_n && $close_time_n > $current_time_n) {
                    $is_business_segment_open = true;
                }

                // if ($open_time < $current_time && $close_time > $current_time) {
                //     $is_business_segment_open = true;
                // }
                $arr_style =   $item->StyleManagement->map(function ($style) use ($merchant_id, $unit) {
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
                    'style' => array_pluck($arr_style, 'style_name'),
                    'rating' => !empty($item->rating) ? $item->rating : "2.5",
                    'open_time' => $open_time,
                    'close_time' => $close_time,
                    'image' => get_image($item->business_logo, 'business_logo', $merchant_id),
                    'is_business_segment_open' => $is_business_segment_open,
                    'distance' => trans("$string_file.distance") . ' ' . $distance_from_user,
                    'is_favourite' => !empty($item->FavouriteBusinessSegment) && $item->FavouriteBusinessSegment->where("user_id", $user->id)->where('segment_id', $request->segment_id)->where('business_segment_id', $item->id)->exists() ? true : false,
                    //                    'is_favourite' => !empty($item->FavouriteBusinessSegment->id) ? true : false,
                );
            });
        }

        //  get store list for holder
        if (in_array("CATEGORY", $holder_item)) {
            // home categories
            $category_heading['cell_title'] = 'TITLE';
            $category_heading['cell_contents'][0] = ['title' => trans("$string_file.all_categories")];
            if ($category_type_view_config) {
                $request->merge(["category_type" => "CAT"]);
            }
            $arr_categories = $store > 0 || !empty($request->business_segment_id) ? $this->getGroceryCategories($request) : [];
            if ($category_type_view_config) {
                $request->merge(["category_type" => NULL]);
            }
            $category_res['cell_title'] = 'CATEGORY_CELL';
            $category_res['cell_contents'] = $arr_categories;
            $if_fav_store = "";
        }

        //  get events list for holder
        if ($category_type_view_config && in_array("EVENT", $holder_item)) {
            // home categories
            $event_heading['cell_title'] = 'TITLE';
            $event_heading['cell_contents'][0] = ['title' => trans("$string_file.weekday_events")];
            $request->merge(["category_type" => "EVENT"]);
            $arr_events = $store > 0 || !empty($request->business_segment_id) ? $this->getGroceryCategories($request) : []; //$this->getEventList($request);
            $request->merge(["category_type" => NULL]);
            $event_res['cell_title'] = 'EVENT_CELL';
            $event_res['cell_contents'] = $arr_events;
        }

        //  get events list for holder
        if ($category_type_view_config && in_array("EVENT_TWO", $holder_item)) {
            // home categories
            $event_two_heading['cell_title'] = 'TITLE';
            $event_two_heading['cell_contents'][0] = ['title' => trans("$string_file.weekend_events")];
            $request->merge(["category_type" => "EVENT_TWO"]);
            $arr_events = $store > 0 || !empty($request->business_segment_id) ? $this->getGroceryCategories($request) : []; //$this->getEventList($request);
            $request->merge(["category_type" => NULL]);
            $event_two_res['cell_title'] = 'EVENT_CELL';
            $event_two_res['cell_contents'] = $arr_events;
        }

        //  get events list for holder
        if (in_array("BRAND", $holder_item)) {
            // home categories
            $brand_heading['cell_title'] = 'TITLE';
            $brand_heading['cell_contents'][0] = ['title' => trans("$string_file.all_brands")];
            $arr_brands = $this->getBrandList($request);
            $brand_res['cell_title'] = 'BRAND_CELL';
            $brand_res['cell_contents'] = $arr_brands;
        }

        //  get events list for holder
        if (in_array("TOP_SELLER", $holder_item)) {
            // home categories
            $top_seller_heading['cell_title'] = 'TITLE';
            $top_seller_heading['cell_contents'][0] = ['title' => trans("$string_file.all_top_sellers")];
            $request->merge(["top_seller" => true]);
            $arr_top_sellers = $store > 0 ? $this->getGroceryProducts($request) : [];
            $request->merge(["top_seller" => false]);
            $top_seller_res['cell_title'] = 'TOP_SELLER';
            $top_seller_res['cell_contents'] = $arr_top_sellers;
        }

        //  get product list for holder
        if (in_array("PRODUCT", $holder_item)) {
            // home screen's product
            $product_heading['cell_title'] = 'TITLE';
            $product_heading['cell_contents'][0] = ['title' => trans("$string_file.all_products")];
            $arr_categories = $store > 0 ? $this->getGroceryProducts($request) : [];
            $product_res['cell_title'] = 'PRODUCT_CELL';
            $product_res['cell_contents'] = $arr_categories;
        }

        $return_data = [];
        //p($response);
        if ($is_search != 1 && !empty($banner_res)) {
            array_push($return_data, $banner_res);
        }
        if ($response == "multi_store") {
            array_push($return_data, $popular_store_heading, $popular_store_res, $store_heading, $store_res);
        } elseif ($response == "single_store") {
            if ($category_type_view_config) {
                array_push($return_data, $category_heading, $category_res, $event_heading, $event_res, $event_two_heading, $event_two_res, $brand_heading, $brand_res, $top_seller_heading, $top_seller_res, $product_heading, $product_res);
            } else {
                array_push($return_data, $category_heading, $category_res, $product_heading, $product_res);
            }
        } elseif ($response == "category_screen") {
            array_push($return_data, $category_heading, $category_res);
        }
        $return = [];
        $return['next_page_url'] = $next_page_url ?? "";
        $return['total_pages'] = $total_pages ?? 1;
        $return['current_page'] = $current_page ?? 1;
        $return['data'] = $return_data;
        $return['status'] = $response;
        $return['business_segment_id'] = $business_segment_id;
        $return['store'] = in_array("STORE", $holder_item) ? true : false;
        return $return;
    }

    // get grocery categories
    public function getGroceryCategories($request)
    {
        $segment_id = $request->segment_id;
        $user = $request->user('api');
        $merchant_id = $user->merchant_id;
        $area_id = $request->area;
        $product = Product::select('id', 'category_id')
            ->whereHas('BusinessSegment', function ($q) use ($segment_id, $merchant_id, $area_id) {
                $q->where([['segment_id', '=', $segment_id], ['merchant_id', '=', $merchant_id]]);
                if (!empty($area_id)) {
                    $q->where('country_area_id', $area_id);
                }
            })
            ->where(function ($q) use ($request) {
                if (!empty($request->business_segment_id)) {
                    $q->where('business_segment_id', $request->business_segment_id);
                }
            })
            ->where([['status', '=', 1], ['delete', '=', NULL]])
            ->get();
        $arr_categories_id  =  $product->map(function ($item, $key) use ($merchant_id) {
            if ($item->Category->delete != 1) {
                return ['id' => $item->Category->category_parent_id == 0 ? $item->Category->id : $item->Category->category_parent_id];
            }
        });

        $arr_categories_id = array_unique(array_pluck($arr_categories_id, 'id'));
        $category_query = Category::whereHas('Segment', function ($q) use ($segment_id, $merchant_id) {
            $q->where([['segment_id', '=', $segment_id], ['merchant_id', '=', $merchant_id]]);
        })
            ->whereIn('id', $arr_categories_id)
            ->where('category_parent_id', 0)
            ->where('merchant_id', $merchant_id)
            ->where('status', 1)
            ->where('delete', NULL);

        if (isset($request->category_type) && !empty($request->category_type)) {
            if (isset($request->category_type) && $request->category_type == "CAT") {
                $category_query->where("category_type", "CAT")->orderBy('sequence', "ASC");
            } elseif (isset($request->category_type) && $request->category_type == "EVENT") {
                $category_query->where("category_type", "EVENT")->orderBy('sequence', "ASC");
            } elseif (isset($request->category_type) && $request->category_type == "EVENT_TWO") {
                $category_query->where("category_type", "EVENT_TWO")->orderBy('sequence', "ASC");
            }
        } else {
            $category_query->orderBy('sequence');
        }

        $categories = $category_query->get();
        $sub_categories = [];
        if (isset($request->calling_from) && $request->calling_from == 'website_screen') {
            $sub_categories = Category::select('id', 'category_parent_id')->whereHas('Segment', function ($q) use ($segment_id, $merchant_id) {
                $q->where([['segment_id', '=', $segment_id], ['merchant_id', '=', $merchant_id]]);
            })
                ->where('category_parent_id', '!=', 0)
                ->where('merchant_id', $merchant_id)
                ->where('status', 1)
                ->orderBy('sequence')
                ->where('delete', NULL)
                ->get();
        }
        $arr_category = $categories->map(function ($item, $key) use ($request, $merchant_id, $sub_categories) {
            $sub_cat_count = Category::where('category_parent_id', $item->id)->count();
            $return = array(
                'id' => $item->id,
                'title' => $item->Name($merchant_id), //$item->category_name,
                'image' => get_image($item->category_image, 'category', $merchant_id),
                'sub_category' => $sub_cat_count > 0 ? true : false,
                'cel_slug' => isset($request->category_type) ? $request->category_type : ""
            );
            if (isset($request->calling_from) && $request->calling_from == 'website_screen') {
                $sub_cats = $sub_categories->where('category_parent_id', $item->id);
                $sub_cats = $sub_cats->map(function ($sub_cat) use ($merchant_id) {
                    return [
                        'id' => $sub_cat->id,
                        'category_parent_id' => $sub_cat->category_parent_id,
                        'category_name' => $sub_cat->Name($merchant_id),
                    ];
                });
                $return['sub_categories'] = array_values($sub_cats->toArray());
            }
            return $return;
        });
        return $arr_category;
    }

    public function getGroceryProducts(Request $request)
    {
        $request->merge(['display_type' => 1, 'return_type' => 'modified_array', 'pagination' => false]);
        $arr_product = $this->getProducts($request);
        return $arr_product;
    }

    public function getSubCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {}),],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(function ($query) {}),],

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            //                response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $merchant_id = $request->merchant_id;
        $category_id = $request->category_id;
        $business_segment_id = $request->business_segment_id;
        $string_file = $this->getStringFile($merchant_id);

        $arr_category_products = Category::select('id')
            ->with(['Product' => function ($q) use ($business_segment_id) {
                $q->where([['status', '=', 1], ['delete', '=', NULL]]);
                if (!empty($business_segment_id)) {
                    $q->where([['business_segment_id', '=', $business_segment_id]]);
                }
            }])
            ->whereHas('Product', function ($q) use ($business_segment_id) {
                $q->where([['status', '=', 1], ['delete', '=', NULL]]);
                if (!empty($business_segment_id)) {
                    $q->where([['business_segment_id', '=', $business_segment_id]]);
                }
            })
            ->where('category_parent_id', $category_id)
            ->where([['merchant_id', '=', $merchant_id], ['status', '=', 1], ['delete', '=', NULL]])
            ->orderBy('sequence')
            ->get();

        $arr_category_products = $arr_category_products->map(function ($item, $key) use ($request, $merchant_id) {
            return array(
                'id' => $item->id,
                'category_name' => $item->Name($merchant_id),
            );
        });
        return $this->successResponse(trans("$string_file.data_found"), $arr_category_products);
    }

    // get products list of restaurant
    public function categoryProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {}),],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(function ($query) {}),],
            //            'business_segment_id' => ['required', 'integer', Rule::exists('business_segments', 'id')->where(function ($query) {}),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $request->merge(['return_type' => 'modified_array', 'pagination' => false]);
        $products = $this->getProducts($request);
        $string_file = $this->getStringFile($request->merchant_id);
        return $this->successResponse(trans("$string_file.data_found"), $products);
    }

    public function categoryProductDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer'],
            'category_id' => ['required', 'integer'],
            'business_segment_id' => ['required', 'integer'],
            'product_id' => ['required', 'integer']
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $request->merge(['return_type' => 'single_product_detail', 'pagination' => false]);
        $products = $this->getProducts($request);
        $string_file = $this->getStringFile($request->merchant_id);
        return $this->successResponse(trans("$string_file.data_found"), $products);
    }

    public function saveProductCart(Request $request)
    {
        // dd($request->all());
        // call area trait to get id of area
        $user = $request->user('api');
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
            $string_file = $this->getStringFile($merchant_id);
            // dd($request->all());
            $this->getAreaByLatLong($request, $string_file);
            $id = $request->cart_id; // product cart/checkout table
            if ($request->product_update == "YES" && !empty($id)) {
                $product_variant_id = $request->product_variant_id;
                $product_quantity = $request->quantity;
                $empty_bottle_quantity = $request->empty_bottle_quantity;
                $product_cart = ProductCart::where('id', $id)->first();
                if (empty($product_cart->id)) {
                    throw new \Exception(trans("$string_file.cart_not_found"));
                }
                $product_details = $product_cart->product_details;
                $product_details = json_decode($product_details, true);
                $updated_products = [];
                foreach ($product_details as $product) {
                    $quantity = $product['product_variant_id'] == $product_variant_id ? $product_quantity : $product['quantity'];
                    $product['empty_bottle_quantity'] = $empty_bottle_quantity !== null ? (int) $empty_bottle_quantity : (isset($product['empty_bottle_quantity']) ? (int) $product['empty_bottle_quantity'] : 0);
                    $updated_products[] = ['product_variant_id' => $product['product_variant_id'], 'quantity' => $quantity, 'empty_bottle_quantity' => (int) $product['empty_bottle_quantity']];
                }
                $product_cart->product_details = json_encode($updated_products);
            } else {
                $segment_id = $request->segment_id;
                $service_type_id = $request->service_type_id;
                //                    $this->getSegmentService($segment_id,$merchant_id,'id');
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
            $return_cart = $this->getCartData($product_cart, false, NULL, $calling_from, $request);
        } catch (\Exception $e) {
            $message = $e->getLine()." ".$e->getMessage().' '.$e->getfile();
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
            'cart_id' => ['required', 'integer', Rule::exists('product_carts', 'id')->where(function ($query) {}),],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $id = $request->cart_id; // product cart/checkout table
        // return cart data
        $return_cart = $this->getCartData($id, true, null, "", $request);
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

        $id = $request->cart_id; // product cart/checkout table
        $product_cart = ProductCart::where('id', $id)->first();
        $merchant_id = $product_cart->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $this->getAreaByLatLong($request, $string_file);
        if ($request->delete_type == 'CART') {
            $product_cart->delete();
            return $this->successResponse(trans("$string_file.cart_deleted"));
        } else {
            $product_id = $request->product_variant_id;
            $product_details = $product_cart->product_details;
            $product_details = json_decode($product_details, true);
            $updated_products = [];
            foreach ($product_details as $product) {
                if ($product['product_variant_id'] != $product_id) {
                    $updated_products[] = ['product_variant_id' => $product['product_variant_id'], 'quantity' => $product['quantity']];
                }
            }

            if (count($updated_products) == 0) {
                $product_cart->delete();
                return $this->successResponse(trans("$string_file.cart_deleted"));
            }
            $product_cart->product_details = json_encode($updated_products);
            $product_cart->save();
        }
        // return cart data
        $product_cart->area = $request->area;
        $return_cart = $this->getCartData($product_cart, false, "", "delete_cart", $request);
        return $this->successResponse(trans("$string_file.cart_product_deleted"), $return_cart);
    }

    public function getCartData($product_cart, $find_by_cart_id = true, $promo_code = null, $calling_from = "", $request = NULL)
    {
      
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
        $arr_product_id = array_column($arr_cart_product, 'product_variant_id');
        // dd( $arr_cart_product ,   $arr_product_id);
        //p($arr_cart_product);
        $arr_cart_product_list = array_column($arr_cart_product, NULL, 'product_variant_id');
        $arr_product_list = ProductVariant::select('id', 'product_id', 'product_price', 'weight', 'weight_unit_id', 'discount',  'status', 'is_title_show')
            ->whereIn('id', $arr_product_id)->where([['delete', '=', NULL]])
            ->with(['Product' => function ($q) {
                $q->select('id', 'category_id', 'manage_inventory', 'merchant_id', 'tax', 'product_cover_image', 'food_type', 'business_segment_id', 'empty_bottle_return', 'bottle_price');
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
        $total_empty_bottle_price = 0;
        $business_segment_id = NULL;
        $arr_cart_product = [];
        $product_data = [];
        if (isset($area_id) && !empty($area_id)) {
            $country_area = CountryArea::find($area_id);
            $currency = isset($country_area->Country) ? $country_area->Country->isoCode : "";
        } else {
            $currency = isset($product_cart->User->Country) ? $product_cart->User->Country->isoCode : "";
        }
        $string_file = $this->getStringFile(NULL, $product_cart->User->Merchant);
        $cart_out_of_stock = false; // overall cart status like out of stock or not
        $total_product_discount = 0;
        // dd($arr_product_list);
        foreach ($arr_product_list as $key => $product) {
            $product_price = $product->product_price;
            $product_discount = !empty($product->discount) && $product->discount > 0 ? $product->discount : 0;
            $merchant_id = $product->Product->merchant_id;
            $business_segment_id = $product->Product->business_segment_id;
            $quantity = (int) $arr_cart_product_list[$product->id]['quantity'];
            // dd($arr_cart_product_list[$product->id]['empty_bottle_quantity'])
            $empty_bottle_quantity = isset($arr_cart_product_list[$product->id]['empty_bottle_quantity']) ? (int) $arr_cart_product_list[$product->id]['empty_bottle_quantity'] : 0;

            $unit = isset($product->weight_unit_id) ? $product->WeightUnit->WeightUnitName : "";
            // check item stock
            $manage_inventory = $product->Product->manage_inventory;
            $item_out_of_stock = false;
            $current_stock = 0;
            if ($manage_inventory == 1 && !empty($product->ProductInventory->id)) {
                $current_stock = $product->ProductInventory->current_stock;
                $item_out_of_stock = $current_stock <  $quantity ? true : false;
                if ($item_out_of_stock == true) {
                    // we will handle this thing later
                    $cart_out_of_stock = $item_out_of_stock;
                }
            }

            $product_image = $product->Product->ProductImage && $product->Product->ProductImage->count() > 0 ? get_image($product->Product->ProductImage[0]->product_image, 'product_image', $merchant_id) : "";
            $product_total_price = (($product_price - $product_discount) * $quantity);
            $product_data['product_id'] = $product->product_id;
            $product_data['weight_unit_id'] = $product->weight_unit_id;
            $product_data['product_variant_id'] = $product->id;
            $product_data['food_type'] = $product->Product->food_type;
            $product_data['quantity'] = $quantity;
            $product_data['current_stock'] = $current_stock;
            $product_data['manage_inventory'] = $manage_inventory;
            $product_data['currency'] = "$currency";
            $product_data['item_out_of_stock'] = $item_out_of_stock;
            // $product_data['product_price'] = $merchant_helper->TripCalculation($product_price, $merchant_id, $trip_calculation_method);
            // $product_data['discount'] = $merchant_helper->TripCalculation($product_discount, $merchant_id, $trip_calculation_method);
            // $discounted_price = !empty($product_discount) ? ($product_price - $product_discount) : "";
            // $product_data['discounted_price'] = !empty($discounted_price) ? $merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method) : "";
            // $product_data['total_price'] = $merchant_helper->TripCalculation($product_total_price, $merchant_id, $trip_calculation_method);
            $product_data['product_price'] = $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method);
            $product_data['discount'] = $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_discount, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method);
            $discounted_price = !empty($product_discount) ? ($product_price - $product_discount) : "";
            $product_data['discounted_price'] = !empty($discounted_price) ? $merchant_helper->PriceFormat($merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method) : "";
            if ($product->Product->empty_bottle_return ==  1 && $empty_bottle_quantity > 0) {
                $product_data['empty_bottle_quantity'] = (int) $empty_bottle_quantity;
                $product_data['empty_bottle_price'] =   $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product->Product->bottle_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method);
                $product_data['total_empty_bottle_price'] =  $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product->Product->bottle_price * $empty_bottle_quantity, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method);
                $product_data['total_price'] = $merchant_helper->TripCalculation($product_total_price - $product_data['total_empty_bottle_price'], $merchant_id, $trip_calculation_method);
                $product_data['total_price_formatted'] = $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_total_price - $product_data['total_empty_bottle_price'], $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method);
            } else {
                $product_data['empty_bottle_quantity'] = 0;
                $product_data['empty_bottle_price'] = "";
                $product_data['total_empty_bottle_price'] = "";
                $product_data['total_price'] = $merchant_helper->TripCalculation($product_total_price, $merchant_id, $trip_calculation_method);
                $product_data['total_price_formatted'] = $merchant_helper->PriceFormat($merchant_helper->TripCalculation($product_total_price, $merchant_id, $trip_calculation_method), $merchant_id, $format_price, $trip_calculation_method);
            }
            $product_data['empty_bottle_return'] = $product->Product->empty_bottle_return;
            $product_data['weight_unit'] = $product->weight . ' ' . $unit;
            $product_data['product_name'] = $product->is_title_show == 1 && !empty($product->Name($merchant_id)) ? $product->Product->Name($product->Product->merchant_id) . '(' . $product->Name($merchant_id) . ')' : $product->Product->Name($product->Product->merchant_id);
            $product_data['variant_title_heading'] = $product->is_title_show == 1 ? trans("$string_file.size") : "";
            $product_data['variant_title'] = $product->is_title_show == 1 ? $product->Name($merchant_id) : "";
            $product_data['variant_status'] = $product->status;
            $product_data['product_cover_image'] = !empty($product->Product->product_cover_image) ? get_image($product->Product->product_cover_image,  'product_cover_image', $product_cart->merchant_id,  true) : $product_image;
            $product_data['arr_option'] = [];

            $arr_cart_product[] = $product_data;
            $total_cart_quantity += $quantity;
            $total_cart_amount += $product_total_price;
            if ($product->Product->empty_bottle_return ==  1 && $empty_bottle_quantity > 0) {
                $total_empty_bottle_price +=  $product_data['total_empty_bottle_price'];
            }
            //            $total_tax_amount +=$product_tax_amount;
            //p($product_data);
        }
        $product_cart->business_segment_id = $business_segment_id;
        $product_cart->cart_amount = (string) $total_cart_amount;
        //        if($calling_from =="save_cart")
        //        {
        // just update the business segment id
        $packaging_preference_ids = !empty($request->packaging_preferences) ? json_decode($request->packaging_preferences, true) : [];
        $all_packaging_preferences = PackagingPreference::where("business_segment_id", $business_segment_id)->get();

        $preferences = [];
        $additional_preference_amount = 0;
        foreach ($all_packaging_preferences as $preference) {
            $is_applied = in_array($preference->id, $packaging_preference_ids) ? true : false;
            $additional_preference_amount += ($is_applied) ? $preference->amount : 0;
            $preferences[] = [
                "id" => $preference->id,
                "description" => $preference->getPackagingPreferenceDescriptionAttribute(),
                "amount" => $currency . " " . $preference->amount,
                "icon" => get_image($preference->icon, 'packaging_preferences', $merchant_id),
                "is_applied" => $is_applied
            ];
        }

        if ($calling_from == "savePackagingPreferences") {
            $product_cart->packaging_preferences = json_encode($preferences);
        }
        $product_cart->save();
        //        }
        $delivery_charge = 0;
        //        $price_card_detail_id = NULL;
        $service_type = 6; // self  pickup
        //drop_distance_from_restaurant
        if ($product_cart->ServiceType->type == 1) // home delivery
        {
            $service_type = $product_cart->ServiceType->type;
            $price_card_detail_id = NULL;
            
            if (!empty($request) && $calling_from != "delete_cart") {
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
                $google_result = GoogleController::GoogleDistanceAndTime($from, $to, $google_key, $units, false, 'groceryCart', $string_file);
                // distance in meter
                $user_distance = isset($google_result['distance_in_meter']) ? $google_result['distance_in_meter'] : NULL;

                // distance in km
                $product_cart->estimate_distance = isset($google_result['distance']) ? $google_result['distance'] : NULL;

                $delivery_charge_slabs = $product_cart->PriceCard->PriceCardDetail->where('status', 1)->toArray();
                if (!empty($user_distance)) {
                    $request->merge(['for' => 2, 'distance' => $user_distance, 'cart_amount' => $total_cart_amount]);
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
        $time_charges = 0;
        $time_charges_enable = false;
        $time_charges_placeholder = "";
        if (isset($product_cart->Merchant->Configuration->user_time_charges) && $product_cart->Merchant->Configuration->user_time_charges == 1) {
            $time_charges_details = $product_cart->PriceCard->time_charges_details;
            if (!empty($time_charges_details)) {
                $time_charges_details = json_decode($time_charges_details, true);
                $now = DateTime::createFromFormat('H:i', date('H:i'));
                $begintime = DateTime::createFromFormat('H:i', $time_charges_details['time_from']);
                $endtime = DateTime::createFromFormat('H:i', $time_charges_details['time_to']);
                if ($begintime <= $now || $now <= $endtime) {
                    if ($time_charges_details['charges_type'] == 1) {
                        $time_charges = $time_charges_details['charges'];
                    } else {
                        $time_charges = ($total_cart_amount * $time_charges_details['charges'] / 100);
                    }
                    $time_charges_enable = true;
                    $time_charges_placeholder = $time_charges_details['charge_parameter'];
                }
            }
        }



        $trip_calc = $product_cart->Merchant->Configuration->trip_calculation_method;


        $total_amount = $merchant_helper->TripCalculation($total_cart_amount, $merchant_id, $trip_calculation_method);
        $discount_amount = $merchant_helper->TripCalculation($discount_amount, $merchant_id, $trip_calculation_method);
        $tax_amount = $merchant_helper->TripCalculation($total_tax_amount, $merchant_id, $trip_calculation_method);
        $delivery_charge = $merchant_helper->TripCalculation($delivery_charge, $merchant_id, $trip_calculation_method);


        // $receipt_data = [
        //     'currency' => $currency,
        //     'quantity' => $total_cart_quantity,
        //     'total_amount' => $merchant_helper->TripCalculation($total_cart_amount, $merchant_id, $trip_calculation_method),
        //     'discount_amount' => $merchant_helper->TripCalculation($discount_amount, $merchant_id, $trip_calculation_method),
        //     'tax_amount' => $merchant_helper->TripCalculation($total_tax_amount, $merchant_id, $trip_calculation_method),
        //     'delivery_charge' => $merchant_helper->TripCalculation($delivery_charge, $merchant_id, $trip_calculation_method),
        // ];


        $receipt_data = [
            'currency' => $currency,
            'quantity' => $total_cart_quantity,
            'total_amount' => $total_amount,
            'total_amount_formatted' => $merchant_helper->PriceFormat($total_amount,  $merchant_id, $format_price, $trip_calculation_method),
            'discount_amount' => $merchant_helper->PriceFormat($discount_amount, $merchant_id, $format_price, $trip_calculation_method),
            'tax_amount' => $merchant_helper->PriceFormat($tax_amount, $merchant_id, $format_price, $trip_calculation_method),
            'tax_amount_formatted' => $tax_amount,
            'delivery_charge' => $merchant_helper->PriceFormat($delivery_charge, $merchant_id, $format_price, $trip_calculation_method),
            'delivery_charge_formatted' => $delivery_charge,
            'empty_bottle_price' => $total_empty_bottle_price > 0 ? $merchant_helper->TripCalculation($total_empty_bottle_price, $merchant_id, $trip_calculation_method) : '',
            'additional_preference_amount' => (string) $additional_preference_amount,
            'additional_preference_amount_formatted' => $merchant_helper->TripCalculation($additional_preference_amount, $merchant_id, $trip_calculation_method),
        ];


        if ($time_charges_enable) {
            $receipt_data['time_charges'] = (int) $time_charges;
        }
        // product discount and coupon discount
        $tip_amount = $request->tip_amount;
        $final_amount = (($total_cart_amount - $discount_amount) + $total_tax_amount + $delivery_charge + (int) $time_charges +  (float)$tip_amount) - ($total_empty_bottle_price) + $additional_preference_amount;
        $receipt_data['final_amount'] = $merchant_helper->TripCalculation($final_amount, $merchant_id, $trip_calculation_method);
        $receipt_data['final_amount_formatted'] = (string)$merchant_helper->PriceFormat($final_amount, $merchant_id, $format_price, $trip_calculation_method);
        $product_cart->time_charges_enable = $time_charges_enable;
        $product_cart->time_charges_placeholder = $time_charges_placeholder;
        $product_cart->receipt = $receipt_data;
        $product_cart->receipt_holder = $this->getPriceDetailHolder($product_cart, $string_file, $currency, $receipt_data, $preferences);

        //for grocery instant delivery or slot time or both(1 for instant,2 for time slot , 3 for both)
        $bs = BusinessSegment::find($business_segment_id);
        $is_business_segment_open = false;
        date_default_timezone_set($bs->CountryArea['timezone']);
        $current_time = date('H:i');
        $current_day = date('w');
        $arr_open_time = json_decode($bs->open_time, true);
        $arr_close_time = json_decode($bs->close_time, true);
        $open_time = $arr_open_time[$current_day] ?? NULL;
        $close_time = $arr_close_time[$current_day] ?? NULL;

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
        //  $product_cart->grocery_delivery_configuration = $bs->grocery_configuration_instant_slot;
        //if store is close user can still book for future date using time slots
        $product_cart->grocery_delivery_configuration = $is_business_segment_open ? $bs->grocery_configuration_instant_slot : 2;


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

        $business_segment = BusinessSegment::select('commission_method', 'commission', 'full_name', 'address')->find($business_segment_id);
        $product_cart->product_details = $arr_cart_product;
        $paymentMethods = $product_cart->PriceCard->CountryArea->PaymentMethod;
        $bookingData = new BookingDataController();
        $options = $bookingData->PaymentOption($paymentMethods, $product_cart->user_id, null, $product_cart->PriceCard->minimum_wallet_amount);
        $product_cart->payment_method = $options;
        $product_cart->store_name = $business_segment->full_name;
        $product_cart->store_address = $business_segment->address;
        $product_cart->service_type = $service_type; // 1 home delivery, 6 self pickup
        $product_cart->promo_code_text = !empty($request->promo_code) ? trans("$string_file.apply_promo_applied") : trans("$string_file.apply_promo_code");
        $product_cart->active_promo_code = !empty($promocode);
        $upload_prescription = false;
        if (!empty($product_cart->segment_id)) {
            $upload_prescription = $product_cart->Segment->slag == "PHARMACY" ? true : false;
        }
        $instant_order = $product_cart->Merchant->Configuration->instant_order;
        unset($product_cart->PriceCard);
        unset($product_cart->User);
        unset($product_cart->BusinessSegment);
        unset($product_cart->Merchant);
        unset($product_cart->created_at);
        unset($product_cart->updated_at);
        unset($product_cart->Segment);
        unset($product_cart->ServiceType);
        $request_data = (object) array(
            'driver_id' => NULL,
            'calling_from' => 'grocery',
            'merchant_id' => $product_cart->merchant_id,
            'segment_id' => $product_cart->segment_id,
            'area' => $area_id,
        );
        $product_cart->time_slot_details = ServiceTimeSlot::getServiceTimeSlot($request_data, $string_file);
        $product_cart->upload_prescription = $upload_prescription;
        $product_cart->instant_order = $instant_order == 1 ? true : false;
        return $product_cart;
    }

    public function placeOrder(Request $request)
    {
        // call area trait to get id of area

        $user = $request->user('api');
        $request_fields = [
            'cart_id' => ['required', 'integer', Rule::exists('product_carts', 'id')->where(function ($query) {}),],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where(function ($query) {}),],
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {}),],
            'address' => 'required',
            //            'service_time_slot_id' => 'required',
            //            'service_time_slot_detail_id' => 'required',
            //            'order_date' => 'required',
            'card_id' => 'required_if:payment_method_id,=,2',
            'service_type_id' => ['required', 'integer', Rule::exists('service_types', 'id')->where(function ($query) {}),],

        ];
        // $custom_message = [
        //     'area.required' => trans('api.no_service_area'),
        // ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {

            $string_file = $this->getStringFile($user->merchant_id);
            $this->getAreaByLatLong($request, $string_file);
            $promocode = $request->promo_code;
            $promo_code_id = NULL;
            if (!empty($request->promo_code)) {
                $common_controller = new CommonController();
                $check_promo_code = $common_controller->checkPromoCode($request);
                if (isset($check_promo_code['status']) && $check_promo_code['status'] == true) {
                    $promocode = $check_promo_code['promo_code'];
                    $promo_code_id = $promocode->id;
                } else {
                    return $check_promo_code;
                }
            }

            $packaging_preference_ids = !empty($request->packaging_preferences) ? json_decode($request->packaging_preferences, true) : [];
            $return_cart = $this->getCartData($request->cart_id, true, $promocode, "", $request, $packaging_preference_ids);
            $bs = $return_cart->BusinessSegment;
            $wallet_balance_low = ($bs->wallet_amount <= 0 || empty($bs->wallet_amount));
            if ($wallet_balance_low && $bs->Merchant->Configuration->check_wallet_for_order_receiving == 1) {
                return $this->failedResponse(trans("$string_file.store_is_closed"));
            }
            //when product is in stock and its variants is in cart but not enabled then return out of stock for variant
            foreach ($return_cart->product_details as $product_variant) {
                if ($product_variant['variant_status'] == 2)
                    return $this->successResponse(trans("$string_file.cart_out_of_stock"), $return_cart);
            }

            // if cart status is out of stock then return cart response in place order api
            if ($return_cart->cart_out_of_stock == true) {
                return $this->successResponse(trans("$string_file.cart_out_of_stock"), $return_cart);
            }

            $service_type_id = $request->service_type_id;
            //                $this->getSegmentService($return_cart->segment_id,$request->merchant_id,'id');
            
            if ($return_cart->ServiceType->type == 1) {
                // Check driver pricecard is exist or not
                $price_card_object = new PriceCardController();
                // dd($request->merchant_id, $return_cart->segment_id, $request->area, $service_type_id);
                $check_for_driver = $price_card_object->checkFoodGroceryPriceCard("DRIVER", $request->merchant_id, $return_cart->segment_id, $request->area, $service_type_id);
                if (!$check_for_driver) {
                    throw new \Exception(trans("$string_file.driver") . " " . trans("$string_file.price_card") . " " . trans("$string_file.data_not_found"));
                }
            }

            $order = new Order;
            $merchant_id = $request->merchant_id;
            $order->merchant_id = $merchant_id;
            $order->user_id = $user->id;
            $order->segment_id = $return_cart->segment_id;
            $order->order_status = 1; //order placed
            $order->country_area_id = $request->area;
            $order->payment_status = !empty($request->payment_status) ? $request->payment_status : 2;

            $order->service_type_id = $service_type_id;

            $cart_amount = $return_cart['receipt'];

            // Set Promocode in order table
            $order->promo_code_id = $promo_code_id;

            $final_amount = $cart_amount['final_amount'];
            if ($request->payment_method_id == 3) {
                $common_controller = new CommonController;
                $common_controller->checkUserWallet($user, $final_amount);
            }

            $order->cart_amount = $cart_amount['total_amount'];
            $order->discount_amount = $cart_amount['discount_amount'];
            $order->tax = $cart_amount['tax_amount_formatted'];
            $order->final_amount_paid = $final_amount;
            $order->delivery_amount = $cart_amount['delivery_charge_formatted'];
            $order->tip_amount = $request->tip_amount;
            $order->time_charges = isset($cart_amount['time_charges']) ? $cart_amount['time_charges'] : 0;

            if ($request->grocery_delivery_type == 2 && ($return_cart->grocery_delivery_configuration == 3 || $return_cart->grocery_delivery_configuration == 2)) {
                $order->service_time_slot_id = $request->service_time_slot_id;
                $order->service_time_slot_detail_id = $request->service_time_slot_detail_id;
            } else if ($request->grocery_delivery_type == 1 && ($return_cart->grocery_delivery_configuration == 3 || $return_cart->grocery_delivery_configuration == 1)) {
                $arr['user'] = ['price_card_detail_id' => $return_cart->price_card_detail_id, 'slab_amount' => $cart_amount['final_amount'], 'distance' => $return_cart->estimate_distance];
                $arr['driver'] = [];
                $order->bill_details = json_encode($arr);
            }

            $order->payment_method_id = $request->payment_method_id;
            $order->business_segment_id = $return_cart->business_segment_id;

            $order->price_card_id = $return_cart->price_card_id;

            $order->drop_latitude = $request->latitude;
            $order->drop_longitude = $request->longitude;
            $order->user_address_id = $request->user_address_id;
            $order->card_id = $request->card_id;

            $order->payment_option_id = $request->payment_option_id;
            $order->payment_status = !empty($request->payment_status) ? $request->payment_status : 2;

            $currentDate = now()->format('Y-m-d');
            $order->order_type = !empty($request->service_time_slot_id) && !empty($request->service_time_slot_detail_id)  ? (($request->order_date == $currentDate) ? 1 : 2) : 1;


            // we should store drop location if get address id
            $drop_location = $request->address;
            if (empty($request->address)) {
                $drop_location = "";
                if (!empty($request->user_address_id)) {
                    $user_address = UserAddress::Find($request->user_address_id);
                    $drop_location = $user_address->house_name . ',' . $user_address->building . ',' . $user_address->address;
                }
            }

            // $order_date = !empty($request->order_date) ? $request->order_date : date('Y-m-d');
            $order_date = !empty($request->order_date) ? Carbon::parse($request->order_date)->setTimeZone($bs->CountryArea->timezone)->format('Y-m-d') : Carbon::now()->setTimeZone($bs->CountryArea->timezone)->format('Y-m-d');

            $order->drop_location = $drop_location;
            $order->additional_notes = "";
            $order->estimate_amount = 0;
            $order->order_timestamp = time();
            $order->order_date = $order_date;
            $order->quantity = $cart_amount['quantity'];
            if ($request->hasFile('prescription_image')) {
                $image = $this->uploadImage('prescription_image', 'prescription_image', $merchant_id);
                $order->prescription_image = $image;
            }
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
                $product_obj->quantity = $product['quantity'];
                $product_obj->price = $product['product_price'];
                $product_obj->discount = 0;
                $product_obj->empty_bottle_quantity = isset($product['empty_bottle_quantity']) ? (int)$product['empty_bottle_quantity'] : 0;
                $product_obj->empty_bottle_price = isset($product['empty_bottle_price']) ? $product['empty_bottle_price'] : 0;
                $product_obj->total_empty_bottle_price = isset($product['total_empty_bottle_price']) ? $product['total_empty_bottle_price'] : 0;
                //                $product_obj->tax = $product['tax'];
                //                $product_obj->tax_amount = $product['tax_amount'];
                $product_obj->total_amount = $product['total_price'];
                $product_obj->save();

                // manage product inventory
                if ($product['manage_inventory'] == 1) {
                    $request->merge([
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
            $payment = new Payment();
            if ($request->payment_method_id != 1) {
                $array_param = array(
                    'order_id' => $order->id,
                    'payment_method_id' => $order->payment_method_id,
                    'amount' => $order->final_amount_paid,
                    'user_id' => $order->user_id,
                    'card_id' => $order->card_id,
                    'quantity' => $order->quantity,
                    'order_name' => $order->merchant_order_id,
                    'currency' => isset($order->User->Country) ? $order->User->Country->isoCode : "",
                    'booking_transaction' => $order->OrderTransaction,
                    'ewallet_user_otp_pin' => $request->otp_pin, // for amole payment gateway
                    'ewallet_pin_expire' => $request->pin_expire_date,
                    'phone_card_no' => $request->phone_card_no,
                );
                $payment_status = $payment->MakePayment($array_param);
                if (!$payment_status) {
                    $message = "Something Went Wrong";
                    return $this->failedResponse($message);
                } elseif ($payment_status) {
                    $order->payment_status = 1; // means payment done while order place
                    $order->save();
                }
            }

            $message = trans("$string_file.order_placed");
            // send notification to driver is configuration is set to direct driver
            $product_cart = ProductCart::Find($request->cart_id);
            $order->packaging_preferences = $product_cart->packaging_preferences;
            $order->save();
            if ($product_cart->ServiceType->type == 1) {
                $business_seg = BusinessSegment::select('id', 'order_request_receiver', 'segment_id', 'merchant_id', 'latitude', 'longitude', 'delivery_service')->Find($product_cart->business_segment_id);
                $arr_agency_id = []; // we can check
                $delivery_service = $business_seg->delivery_service;
                if ($delivery_service == 2) {
                    $arr_agency_id = $business_seg->DriverAgency->pluck('id')->toArray();
                }
                // instant order  will not affect request receiver condition
                // if order date is future then order request will go to restro, ir-respect who is request receiver
                if (!empty($business_seg->order_request_receiver) && $business_seg->order_request_receiver == 2 && $order_date == date("Y-m-d")) {
                    $request->merge([
                        'latitude' => $business_seg->latitude,
                        'longitude' => $business_seg->longitude,
                        'merchant_id' => $business_seg->merchant_id,
                        'segment_id' => $business_seg->segment_id,
                        'arr_agency_id' => $arr_agency_id
                    ]);
                    $this->orderAcceptNotification($request, $order);
                } else {
                    $message = trans("$string_file.later_order_placed");
                }
            }
            //            else
            //            {
            // send new order request to restaurant panel
            $this->sendPushNotificationToWeb($request, $order);
            // send push notification to store app
            $notification_type = 'ORDER_PLACED';
            if ($order->order_type == 2) {
                $notification_type = 'UPCOMING_ORDER_PLACED';
            }
            $data = array('order_id' => $order->id, 'order_number' => $order->merchant_order_id, 'notification_type' => $notification_type, 'segment_type' => $order->Segment->slag);
            $arr_param = array(
                'business_segment_id' => $order->business_segment_id,
                'data' => $data,
                'message' => trans("$string_file.new_order_driver_message"),
                'merchant_id' => $order->merchant_id,
                'title' => trans("$string_file.order_placed_title")
            );
            Onesignal::BusinessSegmentPushMessage($arr_param);
            //            }
            // delete cart
            $product_cart->delete();

            // Send mail to merchant as well as to restro
            $this->sendNewOrderMail($order);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        //update payment status here because due to non db commit row is not inserted in db and status will not change
        $payment->UpdateStatus(['order_id' => $order->id]);
        $data = ['order_id' => $order->id, 'order_status' => $order->order_status];
        return $this->successResponse($message, $data);
    }

    public function getOrders(Request $request)
    {
        $merchant_helper = new Merchant();
        $user = $request->user('api');
        $merchant_id = $user->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $req_param['merchant_id'] = $merchant_id;
        $config_status = $this->getOrderStatus($req_param);
        $currency = isset($user->Country) ? $user->Country->isoCode : '';
        $request_fields = [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {}),],
            'type' => 'required', // 1 for schedule 2 ongoing 3 for past
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
                $order_status = [2, 5, 8, 11]; // user, driver and admin cancelled order and Completed orders
            }
            $orders = Order::select('business_segment_id', 'country_area_id', 'merchant_id', 'id', 'merchant_order_id', 'payment_method_id', 'final_amount_paid', 'discount_amount', 'created_at', 'order_status', 'quantity', 'order_date')
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

            $orders = $orders->map(function ($order, $key) use ($currency, $config_status, $string_file, $merchant_helper) {
                $merchant_id = $order->merchant_id;
                $date = new DateTime($order->created_at);
                // $date = new DateTime($order->order_timestamp);
                // $date->setTimezone(new DateTimeZone($order->CountryArea->timezone));
                return [
                    'order_id' => $order->id,
                    'store_name' => $order->BusinessSegment->full_name,
                    'store_address' => $order->BusinessSegment->address,
                    'store_logo' => get_image($order->BusinessSegment->business_logo, 'business_logo', $merchant_id),
                    'order_date' => trans("$string_file.placed_at") . ' ' . $date->format('H:i D, d-m-Y'),
                    'deliver_on' => trans("$string_file.deliver_on") . ' ' . date('d-m-Y', strtotime($order->order_date)),
                    'total_items' => $order->quantity,
                    'currency' => "$currency",
                    // 'total_amount' => $order->final_amount_paid,
                    'total_amount' => $merchant_helper->PriceFormat($order->final_amount_paid, $merchant_id),
                    // 'discount_amount' => $order->discount_amount,
                    'discount_amount' => (!empty($order->discount_amount)) ? $merchant_helper->PriceFormat($order->discount_amount, $merchant_id) : $order->discount_amount,
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
        $merchant_helper = new Merchant();
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $merchant_id = $user->merchant_id;
        $req_param['merchant_id'] = $merchant_id;
        $config_status = $this->getOrderStatus($req_param);
        $currency = isset($user->Country) ? $user->Country->isoCode : '';
        $trip_calculation_method = $user->Merchant->Configuration->trip_calculation_method;
        $request_fields = [
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->where(function ($query) {})],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $order = Order::select('business_segment_id', 'country_area_id', 'price_card_id', 'merchant_id', 'id', 'merchant_order_id', 'drop_location', 'user_address_id', 'payment_method_id', 'promo_code_id', 'final_amount_paid', 'tax', 'discount_amount', 'cart_amount', 'delivery_amount', 'created_at', 'order_status', 'quantity', 'prescription_image', 'order_status_history', 'otp_for_pickup', 'service_type_id', 'tip_amount', 'packaging_preferences')
                ->with(['BusinessSegment' => function ($q) {
                    $q->addSelect('id', 'address');
                }])
                ->with(['OrderDetail' => function ($q) {
                    $q->addSelect('id', 'order_id', 'product_id', 'product_variant_id', 'weight_unit_id', 'quantity', 'price', 'discount', 'total_amount');
                }])
                ->with(['OrderDetail.Product' => function ($q) {}])
                ->with(['PaymentMethod' => function ($q) {
                    $q->addSelect('id', 'payment_method');
                }])
                ->where('id', $request->order_id)
                ->first();

            $time_charges_enable = false;
            $time_charges = "";
            $time_charges_placeholder = "";
            if (isset($user->Merchant->Configuration->user_time_charges) && $user->Merchant->Configuration->user_time_charges == 1) {
                $time_charges_enable = true;
            }
            if ($time_charges_enable == true && !empty($order->time_charges)) {
                $time_charges = $order->time_charges;
                $time_charges_enable = true;
                $time_charges_details = json_decode($order->PriceCard->time_charges_details, true);
                $time_charges_placeholder = $time_charges_details['charge_parameter'];
            } else {
                $time_charges_enable = false;
            }

            $order_cancel_status = false;
            $cancel_minutes = 0;
            $cancel_charges = 0;
            $order_eligible_for_cancel = [1, 6, 7];
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

            //            $date = new DateTime($order->created_at);
            $date = new DateTime($order->order_date);
            $date->setTimezone(new DateTimeZone($order->CountryArea->timezone));

            $merchant_id = $order->merchant_id;
            $total_empty_bottle_price = 0;
            $product_data = $order->OrderDetail->map(function ($product, $key) use ($merchant_id, $currency, $merchant_helper, &$total_empty_bottle_price) {
                $unit = isset($product->weight_unit_id) ? $product->WeightUnit->WeightUnitName : "";
                $total_empty_bottle_price += isset($product->empty_bottle_price) ? (int)$product->empty_bottle_price : 0;
                return [
                    'title' => $product->Product->Name($merchant_id),
                    'variant_title' => $product->ProductVariant->Name($merchant_id),
                    'image' => get_image($product->Product->product_cover_image, 'product_cover_image', $merchant_id, true, false),
                    'quantity' => $product->quantity,
                    'weight_unit' => $product->ProductVariant->weight . ' ' . $unit,
                    'food_type' => NULL,
                    // 'total_price' => round_number($product->price),
                    'total_price' => $merchant_helper->PriceFormat(round_number($product->ProductVariant->product_price), $merchant_id),
                    // 'product_price' => round_number($product->ProductVariant->product_price),
                    'product_price' => $merchant_helper->PriceFormat(round_number($product->ProductVariant->product_price), $merchant_id),
                    // 'total_product_price' => round_number($product->quantity * round_number($product->ProductVariant->product_price)),
                    'total_product_price' => $merchant_helper->PriceFormat(round_number($product->quantity * round_number($product->ProductVariant->product_price)), $merchant_id),

                    'arr_option' => [],
                    'empty_bottle_quantity' => isset($product->empty_bottle_quantity) ? (int)$product->empty_bottle_quantity : 0,
                    'empty_bottle_price' => isset($product->empty_bottle_price) ? $product->empty_bottle_price : 0,
                    'total_empty_bottle_price' => isset($product->total_empty_bottle_price) ? $product->total_empty_bottle_price : 0,
                ];
            });
            $cart_amount = round_number($order->cart_amount);
            $format_price = $order->Merchant->Configuration->format_price;
            $receipt_holder_data  = [
                'total_amount_formatted' => $merchant_helper->PriceFormat($cart_amount,  $merchant_id, $format_price, $trip_calculation_method),
                'tax_amount_formatted' => !empty($order->tax) ? $merchant_helper->PriceFormat($order->tax, $merchant_id) : "",
                'discount_amount' => $merchant_helper->PriceFormat($order->discount_amount, $merchant_id, $format_price, $trip_calculation_method),
                'delivery_charge_formatted' => $merchant_helper->PriceFormat($order->delivery_amount, $merchant_id, $format_price, $trip_calculation_method),
                'final_amount_formatted' => (string)$merchant_helper->PriceFormat($order->final_amount_paid, $merchant_id, $format_price, $trip_calculation_method),
                'tip_amount' => !empty($order->tip_amount) ? (string) $order->tip_amount : "",
                'time_charges' => "$time_charges"

            ];
            // p($order_cancel_status);
            $self_pickup = $order->ServiceType->type == 6 ? true : false;
            $preferences = !empty($order->packaging_preferences) ? json_decode($order->packaging_preferences, true) : [];
            $order_details =  [
                'order_id' => $order->id,
                'order_no' => $order->merchant_order_id,
                'pickup' => $order->BusinessSegment->address,
                'drop_off' => $order->drop_location,
                'order_date' => $date->format('H:i, d-m-Y'),
                'total_items' => $order->quantity,
                'currency' => "$currency",
                'option_amount' => "",
                'order_status' => $config_status[$order->order_status],
                'product_data' => $product_data,
                'time_charges_enable' => $time_charges_enable,
                'time_charges_placeholder' => $time_charges_placeholder,
                'prescription_image' => !empty($order->prescription_image) ? get_image($order->prescription_image, 'prescription_image', $merchant_id) : "",
                'otp_for_pickup' => $self_pickup && !empty($order->otp_for_pickup) ? $order->otp_for_pickup : "",
                'receipt' => [
                    // 'cart_amount' => "$cart_amount",
                    'cart_amount' => $merchant_helper->PriceFormat($cart_amount, $merchant_id),
                    // 'total_amount' => "$order->final_amount_paid",
                    'total_amount' => $merchant_helper->PriceFormat($order->final_amount_paid, $merchant_id),
                    // 'delivery_amount' => !empty($order->delivery_amount) ? "$order->delivery_amount" : "",
                    'delivery_amount' => !empty($order->delivery_amount) ? $merchant_helper->PriceFormat($order->delivery_amount, $merchant_id) : "",
                    'tip_amount' => !empty($order->tip_amount) ? (string) $order->tip_amount : "",
                    // 'tax' => !empty($order->tax) ? "$order->tax" : "",
                    'tax' => !empty($order->tax) ? $merchant_helper->PriceFormat($order->tax, $merchant_id) : "",
                    'time_charges' => "$time_charges",
                    // 'discount_amount' => "$order->discount_amount",
                    'discount_amount' => (!empty($order->discount_amount)) ? $merchant_helper->PriceFormat($order->discount_amount, $merchant_id) : "$order->discount_amount",
                    'empty_bottle_price' => $merchant_helper->TripCalculation($total_empty_bottle_price, $merchant_id, $trip_calculation_method),
                ],
                'cancel_receipt' => HolderController::userOrderCancelHolder($order, $string_file),
                'receipt_holder' => $this->getPriceDetailHolder($order, $string_file, $currency, $receipt_holder_data, $preferences),
                'arr_action' => [
                    'tracking' => in_array($order->order_status, [6, 7, 9, 10]) ? true : false,
                    'cancel_order' => $order_cancel_status,
                    'cancel_text' => trans("$string_file.order_cancel_warning", ["time" => $cancel_minutes, "amount" => (isset($order->CountryArea->Country) ? $order->CountryArea->Country->isoCode : "") . " " . $cancel_charges])
                ],
            ];
            return $this->successResponse(trans("$string_file.data_found"), $order_details);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function applyRemovePromoCode(Request $request)
    {
        $user = $request->user('api');
        $request_fields = [
            'cart_id' => ['required', 'integer', Rule::exists('product_carts', 'id')->where(function ($query) {}),],
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {}),],
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
                if (isset($check_promo_code['status']) && $check_promo_code['status'] == true) {
                    $promocode = $check_promo_code['promo_code'];
                } else {
                    return $check_promo_code;
                }
            }
            $return_cart = $this->getCartData($request->cart_id, true, $promocode, "", $request);
            // if cart status is out of stock then return cart response in place order api
            if ($return_cart->cart_out_of_stock == true) {
                return $this->successResponse(trans("$string_file.cart_out_of_stock"), $return_cart);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            $failed = $this->failedResponse($message);
            $failedData = json_decode(json_encode($failed), true)['original'];
            $failedData['data'] = (object)[];
            return $failedData;
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.promo_code_applied"), $return_cart);
    }

    public function savePackagingPreferences(Request $request)
    {
        $user = $request->user('api');
        $request_fields = [
            'cart_id' => ['required', 'integer', Rule::exists('product_carts', 'id')->where(function ($query) {}),],
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
            $return_cart = $this->getCartData($request->cart_id, true, NULL, "savePackagingPreferences", $request);
            if ($return_cart->cart_out_of_stock == true) {
                return $this->successResponse(trans("$string_file.cart_out_of_stock"), $return_cart);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            $failed = $this->failedResponse($message);
            $failedData = json_decode(json_encode($failed), true)['original'];
            $failedData['data'] = (object)[];
            return $failedData;
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.promo_code_applied"), $return_cart);
    }

    public function getEventList($request)
    {
        $events = Event::where("merchant_id", $request->merchant_id)->with("Segment")->whereHas("Segment", function ($k) use ($request) {
            $k->where("id", $request->segment_id);
        })->where("country_area_id", $request->area)->where("delete", "=", NULL)->get();

        $event_list = [];
        foreach ($events as $event) {
            array_push($event_list, array(
                "id" => $event->id,
                "title" => $event->Name($request->merchant_id),
                "image" => get_image($event->event_image, 'event', $request->merchant_id),
                "event_link" => $event->event_link
            ));
        }
        return $event_list;
    }

    public function getBrandList($request)
    {
        $brands = Brand::where("merchant_id", $request->merchant_id)->with("Segment")->whereHas("Segment", function ($k) use ($request) {
            $k->where("id", $request->segment_id);
        })->where("delete", "=", NULL)->get();

        $brand_list = [];
        foreach ($brands as $brand) {
            array_push($brand_list, array(
                "id" => $brand->id,
                "title" => $brand->Name($request->merchant_id),
                "image" => get_image($brand->brand_image, 'brand', $request->merchant_id)
            ));
        }
        return $brand_list;
    }

    public function addRemoveFavouriteProduct(Request $request)
    {
        $request_fields = [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {}),],
            'product_variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')->where(function ($query) {}),],
            'action' => ['required', 'in:ADD,REMOVE']

        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        DB::beginTransaction();
        try {
            if ($request->action == "ADD") {
                $user->FavouriteProduct()->attach($request->product_variant_id);
            } else {
                $user->FavouriteProduct()->detach($request->product_variant_id);
            }
            DB::commit();
            //            $request->request->add(['return_type' => 'favourite_product', 'pagination' => false]);
            //            $products = $this->getProducts($request);
            $string_file = $this->getStringFile($request->merchant_id);
            return $this->successResponse(trans("$string_file.success"), []); // $products
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function favouriteProducts(Request $request)
    {
        $request_fields = [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {}),],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $request->merge(['return_type' => 'modified_array', 'is_for_favourite' => true, 'pagination' => false]);
            $products = $this->getProducts($request);
            $string_file = $this->getStringFile($request->merchant_id);
            return $this->successResponse(trans("$string_file.data_found"), $products);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function product(Request $request)
    {
        $request_fields = [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {})],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where(function ($query) {})],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $request->merge(['return_type' => 'modified_array', 'pagination' => false, 'filtered_product_id' => $request->product_id]);
            $products = $this->getProducts($request);
            $string_file = $this->getStringFile($request->merchant_id);
            return $this->successResponse(trans("$string_file.data_found"), $products);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function subscribePlanList()
    {
        $plans = [
            'daily' => 'Daily Plan',
            'weekly' => 'Weekly Plan',
            'alternate' => 'Alternate Days Plan',
            'monthly' => 'Monthly Plan',
        ];
        return $this->successResponse('Subscription plans retrieved successfully.', $plans);
    }

    public function subscribeProduct(Request $request)
    {
        $request_fields = [
            'segment_id' => 'required|integer|exists:segments,id',
            'product_id' => 'required|integer|exists:product_variants,id',
            'selected_plan' => 'required|in:1,2,3,4',
            'start_date' => 'required|date_format:Y-m-d|after:today',
            'day_quantity' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $user = $request->user('api');
            if ($user->UserAddress->where('category', 1)->count() == 0) {
                return $this->failedResponse('Please add an home address before subscribing to a product.');
            }
            if (UserSubscriptionRecord::where('user_id', $user->id)
                ->where('product_id', $request->product_id)
                ->whereIn('status', [1, 2])
                ->exists()
            ) {
                return $this->failedResponse('You have already subscribed to this product.');
            }
            $product = ProductVariant::find( $request->product_id);
            if ($product->Product->subscription_enabled == 0) {
                return $this->failedResponse('This product is not available for subscription.');
            }
            if ($request->selected_plan == 2) {
                $dayQuantities = is_string($request->day_quantity)
                    ? json_decode($request->day_quantity, true)
                    : $request->day_quantity;
                $quantity = 0;
                foreach ($dayQuantities as $day) {
                    if ($quantity < $day['quantity']) {
                        $quantity = $day['quantity'];
                    }
                    if ($day['quantity'] < 1) {
                        return $this->failedResponse('Please select quantity greater than 0 for ' . $day['day']);
                    }
                }
                $product_price =  $product->ProductInventory->product_selling_price *   $quantity;
            } else {
                if ($request->day_quantity < 1) {
                    return $this->failedResponse('Please select quantity greater than 0');
                }
                $product_price =  $product->ProductInventory->product_selling_price * $request->day_quantity;
            }

            if ($user->wallet_balance < $product_price || $user->wallet_balance == null) {
                return $this->failedResponse('You do not have enough balance in your wallet to subscribe this product.');
            }

            $UserSubscriptionRecord = new UserSubscriptionRecord();
            $UserSubscriptionRecord->user_id = $user->id;
            $UserSubscriptionRecord->product_id = $request->product_id;
            $UserSubscriptionRecord->selected_plan = $request->selected_plan;
            $UserSubscriptionRecord->day_quantity = $request->selected_plan == 'weekly' ? json_encode($request->day_quantity) :  $request->day_quantity;
            $UserSubscriptionRecord->start_date = $request->start_date;
            $UserSubscriptionRecord->status = 1;
            $UserSubscriptionRecord->save();
            return $this->successResponse('Product subscribed successfully.', $UserSubscriptionRecord);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function SubscribedProductsDetails(Request $request)
    {
        $request_fields = [
            'subscription_id' => 'required|integer|exists:user_subscription_records,id',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $subscription = UserSubscriptionRecord::find($request->subscription_id);
        $product_cover_image = !empty($subscription->ProductVariant->Product->product_cover_image) ? get_image($subscription->ProductVariant->Product->product_cover_image, 'product_cover_image', $subscription->User->merchant_id) : "";
        $product_image = $subscription->ProductVariant->Product->ProductImage && $subscription->ProductVariant->Product->ProductImage->count() > 0 ? get_image($subscription->ProductVariant->Product->ProductImage[0]->product_image, 'product_image', $subscription->User->merchant_id) : $product_cover_image;
        $subscriptiondata =  [
            'subscription_id' => $subscription->id,
            'product_id' => $subscription->product_id,
            'product_name' => $subscription->ProductVariant->product_title,
            'weight_unit' => $subscription->ProductVariant->weight . ' ' . (!empty($subscription->ProductVariant->WeightUnit)  ? $subscription->ProductVariant->WeightUnit->WeightUnitName : ''),
            'price' => (isset($subscription->ProductVariant->Product->BusinessSegment->CountryArea->Country) ? $subscription->ProductVariant->Product->BusinessSegment->CountryArea->Country->isoCode : '') . $subscription->ProductVariant->product_price,
            'image' =>  $product_image,
            'selected_plan' => $subscription->selected_plan,
            'selected_plan_title' => $subscription->selected_plan == 1 ? 'Daily Plan' : ($subscription->selected_plan == 2 ? 'Weekly Plan' : ($subscription->selected_plan == 3 ? 'Alternate Days Plan' : 'Monthly Plan')),
            'day_quantity' => $subscription->day_quantity,
            'start_date' => $subscription->start_date,
            'status' => $subscription->status,
            'status_title' => $subscription->status == 1 ? 'Active' : ($subscription->status == 2 ? 'Paused' : 'Cancelled'),
            'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
            'subscribe_plan_list' => [['id' => 1, 'title' => 'Daily Plan'], ['id' => 2, 'title' => 'Weekly Plan'], ['id' => 3, 'title' =>  'Alternate Days Plan'], ['id' => 4, 'title' =>  'Monthly Plan']]
        ];

        return $this->successResponse('Subscribed product details retrieved successfully.',   $subscriptiondata);
    }
    public function getSubscribedProducts(Request $request)
    {
        $user = $request->user('api');
        $subscribedProducts = UserSubscriptionRecord::where('user_id', $user->id)->whereNot('status', 3) 
            ->get();

        if ($subscribedProducts->isEmpty()) {
            return $this->failedResponse('No subscribed products found.');
        }

        $subscribedProducts =  $subscribedProducts->map(function ($subscription) {
            $product_cover_image = !empty($subscription->ProductVariant->Product->product_cover_image) ? get_image($subscription->ProductVariant->Product->product_cover_image, 'product_cover_image', $subscription->User->merchant_id) : "";
            $product_image = $subscription->ProductVariant->Product->ProductImage && $subscription->ProductVariant->Product->ProductImage->count() > 0 ? get_image($subscription->ProductVariant->Product->ProductImage[0]->product_image, 'product_image', $subscription->User->merchant_id) : $product_cover_image;
            return [
                'subscription_id' => $subscription->id,
                'product_id' => $subscription->product_id,
                'product_name' => $subscription->ProductVariant->product_title,
                'weight_unit' => $subscription->ProductVariant->weight . ' ' . (!empty($subscription->ProductVariant->WeightUnit)  ? $subscription->ProductVariant->WeightUnit->WeightUnitName : ''),
                'price' => (isset($subscription->ProductVariant->Product->BusinessSegment->CountryArea->Country) ? $subscription->ProductVariant->Product->BusinessSegment->CountryArea->Country->isoCode : '') . $subscription->ProductVariant->product_price,
                'image' =>   $product_image,
                'selected_plan' => $subscription->selected_plan,
                'selected_plan_title' => $subscription->selected_plan == 1 ? 'Daily Plan' : ($subscription->selected_plan == 2 ? 'Weekly Plan' : ($subscription->selected_plan == 3 ? 'Alternate Days Plan' : 'Monthly Plan')),
                'day_quantity' => $subscription->day_quantity,
                'start_date' => $subscription->start_date,
                'status' => $subscription->status,
                'status_title' => $subscription->status == 1 ? 'Active' : ($subscription->status == 2 ? 'Paused' : 'Cancelled'),
                'created_at' => $subscription->created_at->format('Y-m-d H:i:s')
            ];
        });
        return $this->successResponse('Subscribed products retrieved successfully.', $subscribedProducts);
    }

    public function UpdateSubscribeProduct(Request $request)
    {
        $request_fields = [
            'subscription_id' => 'required|integer|exists:user_subscription_records,id',
            'status'          => 'required|in:1,2,3', // 1 = Active, 2 = Paused, 3 = Cancelled
            'selected_plan'   => 'nullable|in:1,2,3,4',
            'start_date'      => 'nullable|date_format:Y-m-d|after:today',
            'day_quantity'    => 'nullable', // Will be validated conditionally
        ];

        $validator = Validator::make($request->all(), $request_fields);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try {
            $user = $request->user('api');

            $subscription = UserSubscriptionRecord::find($request->subscription_id);

            if (!$subscription) {
                return $this->failedResponse('Subscription not found.');
            }

            if ($subscription->user_id !== $user->id) {
                return $this->failedResponse('You are not authorized to update this subscription.');
            }

            if ($subscription->status == 3) {
                return $this->failedResponse('Subscription is cancelled and cannot be updated.');
            }

            $product = ProductVariant::find( $subscription->product_id);
            if (!$product || $product->Product->subscription_enabled == 0) {
                return $this->failedResponse('This product is not available for subscription.');
            }

            // Handle plan & quantity update
            if ($request->has('selected_plan')) {
                $subscription->selected_plan = $request->selected_plan;

                if ($request->selected_plan == 2) {
                    $dayQuantities = is_string($request->day_quantity)
                        ? json_decode($request->day_quantity, true)
                        : $request->day_quantity;

                    if (!is_array($dayQuantities) || empty($dayQuantities)) {
                        return $this->failedResponse('Invalid or missing day quantities for weekly plan.');
                    }

                    $maxQuantity = 0;
                    foreach ($dayQuantities as $day) {
                        if (!isset($day['day']) || !isset($day['quantity']) || $day['quantity'] < 1) {
                            return $this->failedResponse('Each weekly day must have a valid quantity.');
                        }
                        $maxQuantity = max($maxQuantity, $day['quantity']);
                    }

                    $subscription->day_quantity = json_encode($dayQuantities);
                    $totalPrice = $product->ProductInventory->product_selling_price * $maxQuantity;
                } else {
                    if (!is_numeric($request->day_quantity) || $request->day_quantity < 1) {
                        return $this->failedResponse('Please select a quantity greater than 0.');
                    }
                    $subscription->day_quantity = $request->day_quantity;
                    $totalPrice = $product->ProductInventory->product_selling_price * $request->day_quantity;
                }

                if ($user->wallet_balance < $totalPrice || $user->wallet_balance === null) {
                    return $this->failedResponse('You do not have enough balance in your wallet to update the subscription.');
                }
            }

            // Optional: update start date
            if ($request->has('start_date')) {
                $subscription->start_date = $request->start_date;
            }

            // Status change
            if ($subscription->status != $request->status) {
                $subscription->status = $request->status;
            }

            $subscription->save();

            $msg = match ($subscription->status) {
                1 => "Subscription updated and activated successfully.",
                2 => "Subscription updated and paused successfully.",
                3 => "Subscription updated and cancelled successfully.",
                default => "Subscription updated.",
            };


            return $this->successResponse($msg, $subscription);
        } catch (\Exception $e) {
            return $this->failedResponse('An error occurred: ' . $e->getMessage());
        }
    }
}
