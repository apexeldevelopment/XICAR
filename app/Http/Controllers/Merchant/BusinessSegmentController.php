<?php

namespace App\Http\Controllers\Merchant;

use DB;
use App;
use View;
use Session;
use Validator;
use App\Models\Country;
use App\Models\Segment;
use App\Models\Merchant;
use App\Models\Onesignal;
use App\Traits\AreaTrait;
use App\Traits\ImageTrait;
use App\Traits\OrderTrait;
use App\Models\CountryArea;
use App\Models\InfoSetting;
use Illuminate\Http\Request;
use App\Models\Configuration;
use Illuminate\Validation\Rule;
use App\Models\BookingTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\BusinessSegment\Order;
use App\Models\MerchantStripeConnect;
//use App\Traits\MerchantTrait;
use App\Models\MerchantMembershipPlan;
use App\Models\BusinessSegment\Product;
use Illuminate\Database\Eloquent\Model;
use App\Models\DriverAgency\DriverAgency;
use App\Models\BusinessSegment\ProductVariant;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\BusinessSegment\LanguageProduct;
use App\Http\Controllers\Helper\PolygenController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Controllers\PaymentSplit\StripeConnect;
use App\Models\BusinessSegment\BusinessSegmentCashout;
use App\Models\BusinessSegment\LanguageProductVariant;
use App\Models\BusinessSegment\BusinessSegmentOnesignal;
use App\Models\BusinessSegment\BusinessSegmentConfigurations;

class  BusinessSegmentController extends Controller
{
    use ImageTrait, OrderTrait, AreaTrait;

    public function searchView($request, $arr_list = [])
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $data['arr_search'] = $request->all();
        $data['arr_area'] = $this->getMerchantCountryArea($arr_list, 0, 0, $string_file);
        $search = View::make('merchant.business-segment.search')->with($data)->render();
        return $search;
    }
    public function index(Request $request, $slug)
    {
        $checkPermission = check_permission(1, 'view_business_segment_' . $slug);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        $title = "";
        if ($slug == 'FOOD') {
            $title = trans($string_file . '.restaurants');
        } elseif ($slug == 'GROCERY') {
            $title = trans($string_file . '.stores');
        } else {
            $title = trans($string_file . '.stores');
        }
        $title = $title . ' ' . trans("$string_file.list");

        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }

        $business_segment['data'] = BusinessSegment::whereHas('Segment', function ($q) use ($slug) {
            $q->where('slag', $slug);
        })
            ->with('Merchant')
            ->where([['merchant_id', '=', $merchant_id]])
            ->where(function ($query) {
                $query->where('signup_status', 2)
                    ->orWhereNull('signup_status');
            })
            ->orderBy('created_at', 'DESC')
            ->where(function ($q) use ($request, $permission_area_ids) {
                if (!empty($request->country_area_id)) {
                    $q->where('country_area_id', $request->country_area_id);
                }
                if (!empty($request->full_name)) {
                    $q->where('full_name', 'LIKE', '%' . $request->full_name . '%');
                }
                if (!empty($request->email)) {
                    $q->where('email', $request->email);
                }
                if (!empty($request->phone_number)) {
                    $q->where('phone_number', $request->phone_number);
                }
                if (!empty($permission_area_ids)) {
                    $q->whereIn("country_area_id", $permission_area_ids);
                }
            })->where('is_deleted', 0)
            ->paginate(25);
        $business_segment['merchent_id'] =  $merchant_id ;
        $business_segment['slug'] = $slug;
        $business_segment['title'] = $title;
        $business_segment['arr_search'] = $request->all();
        $request->merge(['search_route' => route('merchant.business-segment', $slug), 'url_slug' => $slug]);
        $business_segment['search_view'] = $this->searchView($request, $merchant->CountryArea);
        $info_setting = InfoSetting::where('slug', 'BUSINESS_SEGMENT')->first();
        $business_segment['info_setting'] = $info_setting;
        $business_segment['stripe_connect_store_enable'] = $merchant->Configuration->stripe_connect_store_enable;
        return view('merchant.business-segment.index')->with($business_segment);
    }

    public function add(Request $request, $slug, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $checkPermission = check_permission(1, 'create_business_segment_' . $slug);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        /*declaration part*/
        $business_segment = NULL;
        $merchant_id = $merchant->id;
        $is_demo = false;
        $string_file = $this->getStringFile($merchant_id);
        $sub_group_for_app = null;
        $segment = Segment::where("slag", $slug)->first();
        if (isset($segment)) {
            $sub_group_for_app = $segment->sub_group_for_app;
        }
        if ($slug == 'FOOD') {
            $title = trans($string_file . '.restaurant');
        } elseif ($slug == 'GROCERY') {
            $title = trans($string_file . '.store');
        } else {
            $title = trans($string_file . '.store');
        }

        $save_url = route('merchant.business-segment.save', ['slug' => $slug]);
        $prefix = trans("$string_file.add");
        $arr_agency_id = [];
        if (!empty($id)) {
            $business_segment = BusinessSegment::Find($id);
            if (empty($business_segment->id)) {
                return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
            }
            if ($business_segment->delivery_service == 2) {
                $arr_agency_id = $business_segment->DriverAgency->pluck('id')->toArray();
            }
            $prefix = trans("$string_file.edit");
            $save_url = route('merchant.business-segment.save', ['slug' => $slug, 'id' => $id]);

            //            !empty($id) && in_array($id,[6,11,1211,1212,1213])
            if ($merchant->demo == 1 && $business_segment->country_area_id == 3) {
                $is_demo = true;
            }
        }
        $arr_segment = get_merchant_segment(false);
        //        $arr_country = $this->getMerchantCountry();
        $arr_country = $merchant->Country;
        $arr_day = get_days($string_file);
        $info_setting = InfoSetting::where('slug', 'BUSINESS_SEGMENT')->first();
        $arr_merchant_service_type = $merchant->ServiceType->pluck('type')->toArray();
        $data['data'] = [
            'arr_day' => $arr_day,
            'slug' => $slug,
            'countries' => $arr_country,
            'save_url' => $save_url,
            'title' => $prefix . ' ' . $title,
            'business_segment' => $business_segment,
            'segments' => $arr_segment,
            'request_receiver' => request_receiver($string_file),
            'arr_status' => get_active_status("web", $string_file),
            'is_popular' => get_status(true, $string_file), //\Config::get('custom.document_status'),
            'packaging_preference_enable' => get_status(true, $string_file),
            'self_pickup' => get_status(true, $string_file),
            'dine_in' => get_status(true, $string_file),
            'sub_group_for_app' => $sub_group_for_app,
            'packaging_preference_in_store' => $merchant->Configuration->packaging_preference_in_store == 1,
        ];
        $data['info_setting'] = $info_setting;
        $data['is_demo'] = $is_demo;
        $data['bank_details_admin_enable'] = !empty($merchant->Configuration->bank_details_admin_enable) ? $merchant->Configuration->bank_details_admin_enable : 2;
        $onesignal_config = BusinessSegmentOnesignal::where('business_segment_id', $id)->first();
        $data['onesignal_config'] = $onesignal_config;
        $driver_agency_config = !empty($merchant->Configuration->driver_agency) ? $merchant->Configuration->driver_agency : 0;
        $data['driver_agency_config'] = $driver_agency_config;
        $arr_agencies = [];
        if ($driver_agency_config == 1) {
            $driver_agencies = DriverAgency::where('merchant_id', $merchant_id)->where('status', 1)->get();
            foreach ($driver_agencies as $agency) {
                $arr_agencies[$agency->id] = $agency->name;
            }
        }
        $data['arr_agencies'] = $arr_agencies;
        $data['arr_agency_id'] = $arr_agency_id;
        $data['arr_merchant_service_type'] = $arr_merchant_service_type;
        $data['bs_slot_end_time_enable'] = $merchant->Configuration->bs_slot_end_time_enable;  //we have to remove when checked the condition fine for merchant serve on
        $data['grocery_instant_slot'] = [1 => trans($string_file . '.instant_delivery'), 3 => trans($string_file . '.both_instant_slot'), 2 => trans($string_file . '.time_slot_delivery')];
        $data['subscription_for_bs'] = !empty($merchant->ApplicationConfiguration->subscription_creation_for_bs) ? $merchant->ApplicationConfiguration->subscription_creation_for_bs : 4;
        $data['order_based_on'] = [1 => trans($string_file . '.commision_based'), 2 => trans($string_file . '.subscription_based')];
        $data['tax_transfer_to_enable'] = !empty($merchant->Configuration->tax_transfer_to_enable) ? $merchant->Configuration->tax_transfer_to_enable : 2;

        if (count($arr_country) > 0) {
            foreach ($arr_country as $country) {
                if (count($country->countryArea) > 0) {
                    $lat_long = json_decode(($country->countryArea)[0]->AreaCoordinates, true)[1];
                    $data['default_lat'] = $lat_long['latitude'];
                    $data['default_long'] = $lat_long['longitude'];
                    break;
                }
            }
        }
        return view('merchant.business-segment.form')->with($data);
    }

    /*Save or Update*/
    public function save(Request $request, $slug, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = $merchant->id;
        $arr_validate = [
            // 'full_name' => 'required |unique:business_segments,full_name,' . $id . ',id,merchant_id,' . $merchant_id,
            'full_name' => 'required',
            'email' => 'required|email|unique:business_segments,email,' . $id . ',id,merchant_id,' . $merchant_id,
            'phone_number' => 'required|unique:business_segments,phone_number,' . $id . ',id,merchant_id,' . $merchant_id,
            'password' => 'required_without:id',
            'business_logo' => 'required_without:id|mimes:jpeg,jpg,png',
            'login_background_image' => 'mimes:jpeg,png,jpg,gif,svg',
            'country_id' => 'required',
            //            'segment_id' => 'required',
            'address' => 'required',
            'landmark' => 'required',
            'open_time' => 'required',
            'close_time' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'order_request_receiver' => 'required',
            //            'commission_type' => 'required',
            'commission_method' => 'required',
            'commission' => 'required',
            //            'delivery_service' => 'required',
            //            'minimum_amount' => 'required_if:slug,==,FOOD',
            'delivery_time' => 'required_if:slug,==,FOOD',
            //            'minimum_amount_for' => 'required_if:slug,==,FOOD',
            'rating' => 'required',
            'business_profile_image' => 'mimes:jpeg,png,jpg,gif,svg',
            // 'delivery_service' => 'required',
            'driver_agency_id' => 'required_if:delivery_service,==,2',
            'grocery_configuration_instant_slot' => 'required_if:slug,==,GROCERY',
            //dimensions:width=800,height=230',
        ];

        $arr_msg = [];
        if (empty($id)) {
            $alias_name = str_slug($request->input('full_name'));
            $alias_exists = BusinessSegment::where([['alias_name', '=', $alias_name]])->first();
            if (!empty($alias_exists)) {
                $alias_name = $alias_name . '-' . $merchant->id;
            }
            $request->merge(['alias_name' => $alias_name]);
            // $arr_validate = array_merge(
            //     $arr_validate,
            //     array(
            //         'alias_name' => 'required|max:255|unique:business_segments',
            //     )
            // );
            // $arr_msg = array(
            //     'alias_name.unique' => 'A Business Segment with same name is alredy present, please choose other name.',
            // );
        }

        $validator = Validator::make($request->all(), $arr_validate, $arr_msg);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }

        $country_area_id = NULL;
        $arr_country_area = $merchant->CountryArea->where('country_id', $request->country_id)->where('status', 1);
        //        p($arr_country_area);
        foreach ($arr_country_area as $country_area) {
            $country_area_id = NULL;
            $ploygon = new PolygenController();
            $checkArea = $ploygon->CheckArea($request->latitude, $request->longitude, $country_area->AreaCoordinates);
            if (!empty($checkArea)) {
                $country_area_id = $country_area->id;
                break;
            }
        }
        if (empty($country_area_id)) {
            $errors = trans("$string_file.no_service_area");
            return redirect()->back()->withErrors($errors);
        }
        $sub_group_for_app = null;
        $segment = Segment::where("slag", $slug)->first();
        if (isset($segment)) {
            $sub_group_for_app = $segment->sub_group_for_app;
        }
        // Begin Transaction
        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $business_segment = BusinessSegment::Find($id);
            } else {
                $segment = $this->getSegment($slug);
                if (empty($segment->id)) {
                    $errors = [trans("$string_file.invalid_segment")];
                    return redirect()->back()->withInput($request->input())->withErrors($errors);
                }
                $business_segment = new BusinessSegment();
                $business_segment->alias_name = $alias_name;
                $business_segment->segment_id = $segment->id;
                $business_segment->merchant_id = $merchant_id;
                // $business_segment->delivery_service = 2;
            }

            $business_segment->country_id = $request->country_id;
            $business_segment->full_name = $request->full_name;
            $business_segment->phone_number = $request->phone_number;
            $business_segment->email = $request->email;
            $business_segment->address = $request->address;
            $business_segment->landmark = $request->landmark;
            $business_segment->open_time = json_encode($request->open_time);
            $business_segment->close_time = json_encode($request->close_time);
            if ($merchant->Configuration->bs_slot_end_time_enable == 1) {
                $business_segment->slot_end_time = (count($request->slot_end_time) == 7) ? json_encode($request->slot_end_time) : ["2", "2", "2", "2", "2", "2", "2"];
            }
            $business_segment->status = $request->status;
            $business_segment->latitude = $request->latitude;
            $business_segment->longitude = $request->longitude;
            $business_segment->is_popular = $request->is_popular;
            $business_segment->country_area_id = $country_area_id;
            //            $business_segment->commission_type = $request->commission_type;
            $business_segment->commission_method = $request->commission_method;
            $business_segment->commission = $request->commission;
            //            $business_segment->delivery_service = $request->delivery_service;
            $business_segment->order_request_receiver = $request->order_request_receiver;
            $business_segment->rating = $request->rating;
            $business_segment->tax_transfer_to = isset($request->tax_transfer_to) ? (int)$request->tax_transfer_to : 2;
            $business_segment->order_based_on = isset($request->order_based_on) && !empty($request->order_based_on) ? $request->order_based_on : 1;

            if ($slug == 'FOOD') {
                $business_segment->delivery_time = $request->delivery_time;
                $business_segment->minimum_amount = $request->minimum_amount;
                $business_segment->minimum_amount_for = $request->minimum_amount_for;
                $business_segment->dine_in = $request->dine_in;
            }
            $business_segment->packaging_preference_enable = $request->packaging_preference_enable;

            if ($sub_group_for_app == 2) { //for all $sub_group_for_app == 2 grocery or pharmacy
                $business_segment->grocery_configuration_instant_slot = $request->grocery_configuration_instant_slot;
            }


            if (!empty($request->password)) {
                $business_segment->password = Hash::make($request->password);
            }
            if (!empty($request->hasFile('business_logo'))) {
                $business_segment->business_logo = $this->uploadImage('business_logo', 'business_logo');
            }
            if (!empty($request->hasFile('login_background_image'))) {
                $business_segment->login_background_image = $this->uploadImage('login_background_image', 'business_login_background_image');
            }

            if (!empty($request->hasFile('business_profile_image'))) {
                $business_segment->business_profile_image = $this->uploadImage('business_profile_image', 'business_profile_image');
            }
            $bank_details = [
                'bank_name' => $request->bank_name,
                'account_holder_name' => $request->account_holder_name,
                'bank_code' => $request->bank_code,
                'account_number' => $request->account_number,
            ];
            $business_segment->bank_details = json_encode($bank_details);
            $business_segment->delivery_service = !empty($request->delivery_service) ? $request->delivery_service : 2;
            //            p($business_segment->bank_details);
            $business_segment->save();
            $arr_agencies = $request->delivery_service == 2 ? $request->driver_agency_id : [];
            $business_segment->DriverAgency()->sync($arr_agencies);
            //            p($business_segment);
            //create cofigurations for business segment
            $config = BusinessSegmentConfigurations::where('business_segment_id',  $business_segment->id)->first();
            if (empty($config)) {
                $config = new BusinessSegmentConfigurations;
                $config->business_segment_id = $business_segment->id;
                $config->save();
            }

            //create onesignal cofigurations for business segment
            $onesignal_config = BusinessSegmentOnesignal::where('business_segment_id',  $business_segment->id)->first();
            if (empty($onesignal_config)) {
                $onesignal_config = new BusinessSegmentOnesignal;
                $onesignal_config->business_segment_id = $business_segment->id;
            }
            if (!empty($request->application_key)) {
                $onesignal_config->application_key = $request->application_key;
            } else {
                $merchant_onesignal = Onesignal::where([['merchant_id', '=', $merchant_id]])->first();
                $onesignal_config->application_key = $merchant_onesignal->web_application_key;
            }
            $onesignal_config->save();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return redirect()->back()->withErrors($message);
            // Rollback Transaction
            DB::rollback();
        }
        // Commit Transaction
        DB::commit();
        return redirect()->route('merchant.business-segment', $slug)->with('success', trans("$string_file.added_successfully"));
    }

    function getMerchantCountry()
    {
        $merchant_id = get_merchant_id();
        $countries = Country::select('id', 'phonecode')->where('merchant_id', $merchant_id)->get()->toArray();
        $arr_country = [];
        foreach ($countries as $country) {
            $arr_country[$country['id']] = $country['phonecode'];
        }
        return $arr_country;
    }

    function getSegment($slug)
    {
        return Segment::select('id')->where('slag', $slug)->first();
    }

    public function statistics(Request $request, $slug, $id = NULL)
    {
        $checkPermission = check_permission(1, 'order_statistics_' . $slug);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $data = [];
        $order = new Order;
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $segment = Segment::where('slag', $slug)->first();
        $business_seg_list = BusinessSegment::where('segment_id', $segment->id)->where('merchant_id', $merchant_id)->pluck('full_name', 'id')->toArray();
        $business_seg = [];
        $data['business_summary'] = [];
        $data['summary'] = [];

        $merchant_name = $merchant->BusinessName;
        $segment_id = $segment->id;
        $currency = "";
        $request->merge(['merchant_id' => $merchant_id, 'segment_id' => $segment_id]);
        if ($id != NULL) {
            $business_seg = BusinessSegment::Find($id);
            $request->merge(['business_segment_id' => $id]);
            $business_income = BookingTransaction::select(DB::raw('SUM(customer_paid_amount) as order_amount'), DB::raw('SUM(company_earning) as merchant_earning'), DB::raw('SUM(driver_earning) as driver_earning'), DB::raw('SUM(business_segment_earning) as store_earning'))
                ->with(['Order' => function ($q) use ($merchant_id, $segment_id, $id) {
                    $q->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id], ['business_segment_id', '=', $id]])->get();
                }])
                ->whereHas('Order', function ($q) use ($merchant_id, $segment_id, $id) {
                    $q->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id], ['business_segment_id', '=', $id]]);
                })->where('order_id', '!=', NULL)
                ->first();
            $business_orders = Order::where([['business_segment_id', '=', $id]])->count();
            $data['business_summary'] = [
                'products' => !empty($business_seg) ? $business_seg->Product->count() : '---',
                'orders' => !empty($business_orders) ? $business_orders : '---',
                'income' => $business_income,
            ];
            $currency = $business_seg->Country->isoCode;
            //            $merchant_id = $business_seg->merchant_id;
        }
        //        else
        //        {
        //            $merchant = get_merchant_id(false);
        //            $merchant_id = $merchant->id;
        //            $merchant_name = $merchant->BusinessName;

        //        }
        // summery of merchant
        $segment_id = $segment->id;
        $products_query = Product::where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id]]);
        if ($id) {
            $products_query->where('business_segment_id', $id);
        }
        $merchant_products =     $products_query->count();
        $orders_query = Order::where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id]]);
        if ($id) {
            $orders_query->where('business_segment_id', $id);
        }
        $business_orders =     $orders_query->count();
        $income = BookingTransaction::select(DB::raw('SUM(customer_paid_amount) as order_amount'), DB::raw('SUM(company_earning) as merchant_earning'), DB::raw('SUM(driver_earning) as driver_earning'), DB::raw('SUM(business_segment_earning) as store_earning'))
            ->with(['Order' => function ($q) use ($merchant_id, $segment_id, $id) {
                $q->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id]]);
                if ($id) {
                    $q->where('business_segment_id', $id);
                }
            }])
            ->whereHas('Order', function ($q) use ($merchant_id, $segment_id, $id) {
                $q->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id]]);
                if ($id) {
                    $q->where('business_segment_id', $id);
                }
            })->where('order_id', '!=', NULL)
            ->first();
        $data['summary'] = [
            'products' => $merchant_products,
            'orders' => !empty($business_orders) ? $business_orders : 0,
            'income' => $income,
        ];
        $data['currency'] = $currency;
        $all_orders = $order->getOrders($request, true);
        $request->merge(['id' => $id]);
        $data['arr_orders'] = $all_orders;
        $req_param['merchant_id'] =  $merchant_id;
        $data['arr_status'] = $this->getOrderStatus($req_param);
        $data['title'] =  !empty($business_seg) ? $business_seg->full_name : '---';
        $data['id'] =  !empty($business_seg) ? $business_seg->id : NULL;
        $data['slug'] =  !empty($business_seg) ? $business_seg->Segment->slag : $segment->slag;
        $data['business_seg_list'] = $business_seg_list;
        $data['merchant_name'] = $merchant_name;
        //        p($data['summary']);
        $data['info_setting'] = InfoSetting::where('slug', 'ORDER')->first();
        return view('merchant.business-segment.statistics')->with($data);
    }

    public function cashoutRequest(Request $request)
    {
        try {
            $merchant_id = get_merchant_id();
            $permission_segments = get_permission_segments(1, true);
            $cashout_requests = BusinessSegmentCashout::whereHas('BusinessSegment', function ($query) use ($permission_segments) {
                $query->whereHas('Segment', function ($query) use ($permission_segments) {
                    $query->whereIn('slag', $permission_segments);
                });
            })->where('merchant_id', $merchant_id)->latest()->paginate(20);
            $info_setting = InfoSetting::where('slug', 'BUSINESS_SEGMENT_CASHOUT')->first();
            return view('merchant.business-segment.cashout.index', compact('cashout_requests', 'info_setting'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function cashoutChangeStatus(Request $request, $id)
    {
        try {
            $merchant_id = get_merchant_id();
            $cashout_request = BusinessSegmentCashout::with('BusinessSegment')->where('merchant_id', $merchant_id)->find($id);
            $info_setting = InfoSetting::where('slug', 'BUSINESS_SEGMENT_CASHOUT')->first();
            return view('merchant.business-segment.cashout.edit', compact('cashout_request', 'info_setting'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function cashoutChangeStatusUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cashout_status' => 'required',
            'action_by' => 'required',
            'transaction_id' => 'required',
            'comment' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withErrors($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant_id = get_merchant_id();
            $string_file = $this->getStringFile($merchant_id);
            $cashout_request = BusinessSegmentCashout::where('merchant_id', $merchant_id)->find($id);
            // if ($request->cashout_status == 2) {
            //     $paramArray = array(
            //         'business_segment_id' => $cashout_request->business_segment_id,
            //         'order_id' => NULL,
            //         'amount' => $cashout_request->amount,
            //         'narration' => 5,
            //     );
            //     WalletTransaction::BusinessSegmntWalletCredit($paramArray);
            // }
            $cashout_request->cashout_status = $request->cashout_status;
            $cashout_request->action_by = $request->action_by;
            $cashout_request->transaction_id = $request->transaction_id;
            $cashout_request->comment = $request->comment;
            $cashout_request->save();
            DB::commit();
            $return_message = "";
            if ($request->cashout_status == 0) {
                $return_message = trans("$string_file.cashout_request_pending");
            } elseif ($request->cashout_status == 1) {
                // $paramArray = array(
                //     'business_segment_id' => $cashout_request->business_segment_id,
                //     'booking_id' => null,
                //     'amount' => $cashout_request->amount,
                //     'narration' => 4,
                // );
                // WalletTransaction::BusinessSegmntWalletDebit($paramArray);
                $return_message = trans("$string_file.cashout_request_successfully");
            } elseif ($request->cashout_status == 2) {
                $return_message = trans("$string_file.cashout_request_rejected_refund_amount");
            }
            return redirect()->route('merchant.business-segment.cashout_request')->withSuccess($return_message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function orderDetail(Request $request, $id)
    {
        $order_obj = new Order;
        $request->merge(['id' => $id]);
        $order = $order_obj->getOrders($request);
        $business_segment = $order->BusinessSegment;
        $req_param['merchant_id'] = $order->merchant_id;
        $arr_status = $this->getOrderStatus($req_param);
        $hide_user_info_from_store = $order->Merchant->ApplicationConfiguration->hide_user_info_from_store;
        $info_setting = InfoSetting::where('slug', 'ORDER')->first();
        return view('merchant.business-segment.order-details', compact('order', 'arr_status', 'business_segment', 'hide_user_info_from_store', 'info_setting'));
    }


    // list all orders for merchant panel
    public function orders(Request $request, $slug, $id = NULL)
    {
        $checkPermission = check_permission(1, 'order_statistics_' . $slug);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $data = [];
        $order = new Order;
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $segment = Segment::where('slag', $slug)->first();
        $business_seg_list = BusinessSegment::where('segment_id', $segment->id)->where('merchant_id', $merchant_id)->pluck('full_name', 'id')->toArray();
        $business_seg = [];
        $data['business_summary'] = [];
        $data['summary'] = [];

        $merchant_name = $merchant->BusinessName;
        $segment_id = $segment->id;
        $currency = "";
        $request->merge(['merchant_id' => $merchant_id, 'segment_id' => $segment_id, 'id' => $id]);
        $all_orders = $order->getOrders($request, true);
        $data['arr_orders'] = $all_orders;
        $req_param['merchant_id'] =  $merchant_id;
        $data['arr_status'] = $this->getOrderStatus($req_param);
        $data['title'] =  !empty($business_seg) ? $business_seg->full_name : '---';
        $data['id'] =  !empty($business_seg) ? $business_seg->id : NULL;
        $data['slug'] =  !empty($business_seg) ? $business_seg->Segment->slag : $segment->slag;
        //        $data['business_seg_list'] = $business_seg_list;
        //        $data['merchant_name'] = $merchant_name;
        //        p($data['summary']);
        $data['arr_search'] = $request->all();
        $request->merge(['search_route' => route('merchant.business-segment.orders', $slug), 'url_slug' => $slug, 'arr_bs' => $business_seg_list]);
        $data['info_setting'] = InfoSetting::where('slug', 'ORDER')->first();
        $data['search_view'] = $this->orderSearchView($request, $merchant->CountryArea);
        //        $data['search_view']['arr_segment'] = $business_seg_list;
        return view('merchant.business-segment.orders')->with($data);
    }

    public function orderSearchView($request, $arr_list = [], $string_file = "")
    {
        //        $string_file = $this->getStringFile(NULL,$merchant);
        $data['arr_search'] = $request->all();
        $data['arr_area'] = $this->getMerchantCountryArea($arr_list, 0, 0, $string_file);

        $search = View::make('business-segment.order.order-search')->with($data)->render();
        //        p($search);
        return $search;
    }
    public function getBusinessSegment(Request $request)
    {
        $id = $request->id;
        $area_id = $request->area_id;
        $merchant_id = get_merchant_id();
        $business_segment = BusinessSegment::where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $id]])
            ->where(function ($q) use ($area_id) {
                if (!empty($area_id)) {
                    $q->where('country_area_id', $area_id);
                }
            })
            ->pluck('full_name', 'id')->toArray();
        return $business_segment;
    }

    /** Play store BusinessSegment delete  Start */
    public function showBusinessSegmentDetails(Request $request)
    {
        $user = Auth::user('business-segment-user');
        if ($user->id) {
            $merchant = $user->Merchant;
            setS3Config($merchant);
            return view('merchant.business-segment-details', compact('user', 'merchant'));
        } else {
            return redirect()->back()->withErrors('Something went wrong, please try again');
        }
    }


    public function businessSegmentDelete(Request $request)
    {
        $user = Auth::user('business-segment-user');
        if ($user->id) {
            $alias = $user->Merchant->alias_name;
            $user->status = 2;
            $user->save();
            Session::flush();
            return redirect()->route('business-segment.user.login', $alias)->withSuccess('Your account has been deleted successfully');
        } else {
            return redirect()->back()->withErrors('Something went wrong, please try again');
        }
    }
    /** Play store BusinessSegment delete  End */


    public function copyProduct(Request $request)
    {
        $copyFromBs = BusinessSegment::find($request->selectedId);

        if (count($copyFromBs->Product) > 0) {
            foreach ($copyFromBs->Product as $prod) {
                // dd($prod);
                $product = Product::where(['business_segment_id' => $request->bsId, 'sku_id' => $prod->sku_id])->first();
                if ($product) {

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Product already exists.'
                    ]);
                } else {
                    $copyProductToOtherBs = new Product();
                    $copyProductToOtherBs->merchant_id = $prod->merchant_id;
                    $copyProductToOtherBs->business_segment_id = $request->bsId;
                    $copyProductToOtherBs->category_id = $prod->category_id;
                    $copyProductToOtherBs->segment_id = $prod->segment_id;
                    $copyProductToOtherBs->sku_id = $prod->sku_id;
                    $copyProductToOtherBs->brand_id = $prod->brand_id;
                    $copyProductToOtherBs->product_cover_image = $prod->product_cover_image;
                    $copyProductToOtherBs->product_preparation_time = $prod->product_preparation_time;
                    $copyProductToOtherBs->tax = $prod->tax;
                    $copyProductToOtherBs->sequence = $prod->sequence;
                    $copyProductToOtherBs->status = $prod->status;
                    $copyProductToOtherBs->food_type = $prod->food_type;
                    $copyProductToOtherBs->display_type = $prod->display_type;
                    $copyProductToOtherBs->delete = $prod->delete;
                    $copyProductToOtherBs->manage_inventory = $prod->manage_inventory;
                    $copyProductToOtherBs->save();

                    LanguageProduct::updateOrCreate([
                        'merchant_id' => $prod->merchant_id,
                        'locale' => App::getLocale(),
                        'product_id' => $copyProductToOtherBs->id
                    ], [
                        'business_segment_id' => $prod->business_segment_id,
                        'name' => $prod->langData($prod->merchant_id)->name,
                        'description' => $prod->langData($prod->merchant_id)->description,
                        'ingredients' => $prod->langData($prod->merchant_id)->ingredients,
                    ]);

                    // dd($prod->ProductVariant);

                    if ($prod->ProductVariant) {
                        foreach ($prod->ProductVariant as $variant) {
                            $productVariant = new ProductVariant();
                            $productVariant->product_id = $copyProductToOtherBs->id;
                            $productVariant->sku_id = $variant->sku_id;
                            $productVariant->product_title = $variant->product_title;
                            $productVariant->product_price = $variant->product_price;
                            $productVariant->discount = $variant->discount;
                            $productVariant->weight_unit_id = $variant->weight_unit_id;
                            $productVariant->weight = $variant->weight;
                            $productVariant->is_title_show = $variant->is_title_show;
                            $productVariant->delete = $variant->delete;
                            $productVariant->deleted_at = $variant->deleted_at;
                            $productVariant->save();

                            LanguageProductVariant::updateOrCreate([
                                'merchant_id' => $prod->merchant_id,
                                'locale' => App::getLocale(),
                                'product_variant_id' => $productVariant->id
                            ], [
                                'business_segment_id' => $prod->business_segment_id,
                                'name' => $variant->Name($prod->merchant_id),
                            ]);
                        }
                    }
                }
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'There is no Product available ins selected restaurants '
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product copied successfully.'
        ]);
    }


    public function AddMoney(Request $request)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|integer|between:1,2',
            'receipt_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'transaction_type' => 'required',
            'receiver_id' => 'required|exists:business_segments,id'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return error_response($errors);
        }
        $business_segment = BusinessSegment::find($request->receiver_id);
        $paramArray = array(
            'business_segment_id' => $business_segment->id,
            'order_id' => NULL,
            'amount' => $request->amount,
            'narration' => 1,
            'platform' => 1,
            'payment_method' => $request->payment_method,
            'receipt' => $request->receipt_number,
            'action_merchant_id' => Auth::user('merchant')->id
        );
        if ($request->transaction_type == 1) {
            WalletTransaction::BusinessSegmntWalletCredit($paramArray);
        } else {
            $paramArray['narration'] = 6;
            WalletTransaction::BusinessSegmntWalletDebit($paramArray);
        }
        return success_response(trans('admin.message207'));
    }


    public function indexPendingDetails(Request $request, $slug)
    {
        $checkPermission = check_permission(1, 'view_business_segment_' . $slug);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        $title = "";
        if ($slug == 'FOOD') {
            $title = trans($string_file . '.restaurants');
        } elseif ($slug == 'GROCERY') {
            $title = trans($string_file . '.stores');
        } else {
            $title = trans($string_file . '.stores');
        }
        $title = $title . ' ' . trans($string_file . '.signup_pending') . ' ' . trans("$string_file.list");

        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }

        $business_segment['data'] = BusinessSegment::whereHas('Segment', function ($q) use ($slug) {
            $q->where('slag', $slug);
        })
            ->with('Merchant')
            ->where([['merchant_id', '=', $merchant_id]])
            ->where(function ($query) {
                $query->where('signup_status', 1);
            })
            ->orderBy('created_at', 'DESC')
            ->where(function ($q) use ($request, $permission_area_ids) {
                if (!empty($request->country_area_id)) {
                    $q->where('country_area_id', $request->country_area_id);
                }
                if (!empty($request->full_name)) {
                    $q->where('full_name', 'LIKE', '%' . $request->full_name . '%');
                }
                if (!empty($request->email)) {
                    $q->where('email', $request->email);
                }
                if (!empty($request->phone_number)) {
                    $q->where('phone_number', $request->phone_number);
                }
                if (!empty($permission_area_ids)) {
                    $q->whereIn("country_area_id", $permission_area_ids);
                }
            })
            ->paginate(25);
        $business_segment['slug'] = $slug;
        $business_segment['title'] = $title;
        $business_segment['arr_search'] = $request->all();
        $request->merge(['search_route' => route('merchant.business-segment', $slug), 'url_slug' => $slug]);
        $business_segment['search_view'] = $this->searchView($request, $merchant->CountryArea);
        $info_setting = InfoSetting::where('slug', 'BUSINESS_SEGMENT')->first();
        $business_segment['info_setting'] = $info_setting;
        return view('merchant.business-segment.indexSignupPending')->with($business_segment);
    }

    public function addPendingDetails(Request $request, $slug, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $checkPermission = check_permission(1, 'create_business_segment_' . $slug);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        /*declaration part*/
        $business_segment = NULL;
        $merchant_id = $merchant->id;
        $is_demo = false;
        $string_file = $this->getStringFile($merchant_id);
        $sub_group_for_app = null;
        $segment = Segment::where("slag", $slug)->first();
        if (isset($segment)) {
            $sub_group_for_app = $segment->sub_group_for_app;
        }
        if ($slug == 'FOOD') {
            $title = trans($string_file . '.restaurant');
        } elseif ($slug == 'GROCERY') {
            $title = trans($string_file . '.store');
        } else {
            $title = trans($string_file . '.store');
        }

        $save_url = route('merchant.business-segment.save-pending-details', ['slug' => $slug]);
        $prefix = trans("$string_file.add");
        $arr_agency_id = [];
        if (!empty($id)) {
            $business_segment = BusinessSegment::Find($id);
            if (empty($business_segment->id)) {
                return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
            }
            if ($business_segment->delivery_service == 2) {
                $arr_agency_id = $business_segment->DriverAgency->pluck('id')->toArray();
            }
            $prefix = trans("$string_file.edit") . ' ' . trans($string_file . '.signup_pending');
            $save_url = route('merchant.business-segment.save-pending-details', ['slug' => $slug, 'id' => $id]);

            //            !empty($id) && in_array($id,[6,11,1211,1212,1213])
            if ($merchant->demo == 1 && $business_segment->country_area_id == 3) {
                $is_demo = true;
            }
        }
        $arr_segment = get_merchant_segment(false);
        //        $arr_country = $this->getMerchantCountry();
        $arr_country = $merchant->Country;
        $arr_day = get_days($string_file);
        $info_setting = InfoSetting::where('slug', 'BUSINESS_SEGMENT')->first();
        $arr_merchant_service_type = $merchant->ServiceType->pluck('type')->toArray();
        $data['data'] = [
            'arr_day' => $arr_day,
            'slug' => $slug,
            'countries' => $arr_country,
            'save_url' => $save_url,
            'title' => $prefix . ' ' . $title,
            'business_segment' => $business_segment,
            'segments' => $arr_segment,
            'request_receiver' => request_receiver($string_file),
            'arr_status' => get_active_status("web", $string_file),
            'is_popular' => get_status(true, $string_file), //\Config::get('custom.document_status'),
            'self_pickup' => get_status(true, $string_file),
            'dine_in' => get_status(true, $string_file),
            'sub_group_for_app' => $sub_group_for_app,
        ];
        $data['info_setting'] = $info_setting;
        $data['is_demo'] = $is_demo;
        $data['bank_details_admin_enable'] = !empty($merchant->Configuration->bank_details_admin_enable) ? $merchant->Configuration->bank_details_admin_enable : 2;
        $onesignal_config = BusinessSegmentOnesignal::where('business_segment_id', $id)->first();
        $data['onesignal_config'] = $onesignal_config;
        $driver_agency_config = !empty($merchant->Configuration->driver_agency) ? $merchant->Configuration->driver_agency : 0;
        $data['driver_agency_config'] = $driver_agency_config;
        $arr_agencies = [];
        if ($driver_agency_config == 1) {
            $driver_agencies = DriverAgency::where('merchant_id', $merchant_id)->where('status', 1)->get();
            foreach ($driver_agencies as $agency) {
                $arr_agencies[$agency->id] = $agency->name;
            }
        }
        $data['arr_agencies'] = $arr_agencies;
        $data['arr_agency_id'] = $arr_agency_id;
        $data['arr_merchant_service_type'] = $arr_merchant_service_type;
        $data['bs_slot_end_time_enable'] = $merchant->Configuration->bs_slot_end_time_enable;  //we have to remove when checked the condition fine for merchant serve on
        $data['grocery_instant_slot'] = [1 => trans($string_file . '.instant_delivery'), 3 => trans($string_file . '.both_instant_slot'), 2 => trans($string_file . '.time_slot_delivery')];
        $data['subscription_for_bs'] = !empty($merchant->ApplicationConfiguration->subscription_creation_for_bs) ? $merchant->ApplicationConfiguration->subscription_creation_for_bs : 4;
        $data['order_based_on'] = [1 => trans($string_file . '.commision_based'), 2 => trans($string_file . '.subscription_based')];
        $data['tax_transfer_to_enable'] = !empty($merchant->Configuration->tax_transfer_to_enable) ? $merchant->Configuration->tax_transfer_to_enable : 2;
        if (count($arr_country) > 0) {
            foreach ($arr_country as $country) {
                if (count($country->countryArea) > 0) {
                    $lat_long = json_decode(($country->countryArea)[0]->AreaCoordinates, true)[1];
                    $data['default_lat'] = $lat_long['latitude'];
                    $data['default_long'] = $lat_long['longitude'];
                    break;
                }
            }
        }
        return view('merchant.business-segment.signup-pending')->with($data);
    }

    /*Save or Update*/
    public function savePendingDetails(Request $request, $slug, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = $merchant->id;
        $arr_validate = [
            // 'full_name' => 'required |unique:business_segments,full_name,' . $id . ',id,merchant_id,' . $merchant_id,
            'full_name' => 'required',
            'email' => 'required|email|unique:business_segments,email,' . $id . ',id,merchant_id,' . $merchant_id,
            'phone_number' => 'required|unique:business_segments,phone_number,' . $id . ',id,merchant_id,' . $merchant_id,
            'password' => 'required_without:id',
            'business_logo' => 'required_without:id|mimes:jpeg,jpg,png',
            'login_background_image' => 'mimes:jpeg,png,jpg,gif,svg',
            'country_id' => 'required',
            //            'segment_id' => 'required',
            'address' => 'required',
            'landmark' => 'required',
            'open_time' => 'required',
            'close_time' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'order_request_receiver' => 'required',
            //            'commission_type' => 'required',
            'commission_method' => 'required',
            'commission' => 'required',
            //            'delivery_service' => 'required',
            //            'minimum_amount' => 'required_if:slug,==,FOOD',
            'delivery_time' => 'required_if:slug,==,FOOD',
            //            'minimum_amount_for' => 'required_if:slug,==,FOOD',
            'rating' => 'required',
            'business_profile_image' => 'mimes:jpeg,png,jpg,gif,svg',
            // 'delivery_service' => 'required',
            'driver_agency_id' => 'required_if:delivery_service,==,2',
            'grocery_configuration_instant_slot' => 'required_if:slug,==,GROCERY',
            //dimensions:width=800,height=230',
        ];

        $arr_msg = [];
        if (empty($id)) {
            $alias_name = str_slug($request->input('full_name'));
            $alias_exists = BusinessSegment::where([['alias_name', '=', $alias_name]])->first();
            if (!empty($alias_exists)) {
                $alias_name = $alias_name . '-' . $merchant->id;
            }
            $request->merge(['alias_name' => $alias_name]);
            // $arr_validate = array_merge(
            //     $arr_validate,
            //     array(
            //         'alias_name' => 'required|max:255|unique:business_segments',
            //     )
            // );
            // $arr_msg = array(
            //     'alias_name.unique' => 'A Business Segment with same name is alredy present, please choose other name.',
            // );
        }

        $validator = Validator::make($request->all(), $arr_validate, $arr_msg);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }

        $country_area_id = NULL;
        $arr_country_area = $merchant->CountryArea->where('country_id', $request->country_id)->where('status', 1);
        //        p($arr_country_area);
        foreach ($arr_country_area as $country_area) {
            $country_area_id = NULL;
            $ploygon = new PolygenController();
            $checkArea = $ploygon->CheckArea($request->latitude, $request->longitude, $country_area->AreaCoordinates);
            if (!empty($checkArea)) {
                $country_area_id = $country_area->id;
                break;
            }
        }
        if (empty($country_area_id)) {
            $errors = trans("$string_file.no_service_area");
            return redirect()->back()->withErrors($errors);
        }
        $sub_group_for_app = null;
        $segment = Segment::where("slag", $slug)->first();
        if (isset($segment)) {
            $sub_group_for_app = $segment->sub_group_for_app;
        }
        // Begin Transaction
        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $business_segment = BusinessSegment::Find($id);
            } else {
                $segment = $this->getSegment($slug);
                if (empty($segment->id)) {
                    $errors = [trans("$string_file.invalid_segment")];
                    return redirect()->back()->withInput($request->input())->withErrors($errors);
                }
                $business_segment = new BusinessSegment();
                $business_segment->alias_name = $alias_name;
                $business_segment->segment_id = $segment->id;
                $business_segment->merchant_id = $merchant_id;
                // $business_segment->delivery_service = 2;
            }

            $business_segment->country_id = $request->country_id;
            $business_segment->full_name = $request->full_name;
            $business_segment->phone_number = $request->phone_number;
            $business_segment->email = $request->email;
            $business_segment->address = $request->address;
            $business_segment->landmark = $request->landmark;
            $business_segment->open_time = json_encode($request->open_time);
            $business_segment->close_time = json_encode($request->close_time);
            if ($merchant->Configuration->bs_slot_end_time_enable == 1) {
                $business_segment->slot_end_time = (count($request->slot_end_time) == 7) ? json_encode($request->slot_end_time) : ["2", "2", "2", "2", "2", "2", "2"];
            }
            $business_segment->status = $request->status;
            $business_segment->latitude = $request->latitude;
            $business_segment->longitude = $request->longitude;
            $business_segment->is_popular = $request->is_popular;
            $business_segment->country_area_id = $country_area_id;
            $business_segment->signup_status = 2;  //completed
            //            $business_segment->commission_type = $request->commission_type;
            $business_segment->commission_method = $request->commission_method;
            $business_segment->commission = $request->commission;
            //            $business_segment->delivery_service = $request->delivery_service;
            $business_segment->order_request_receiver = $request->order_request_receiver;
            $business_segment->rating = $request->rating;
            $business_segment->tax_transfer_to = isset($request->tax_transfer_to) ? (int)$request->tax_transfer_to : 2;
            $business_segment->order_based_on = isset($request->order_based_on) && !empty($request->order_based_on) ? $request->order_based_on : 1;

            if ($slug == 'FOOD') {
                $business_segment->delivery_time = $request->delivery_time;
                $business_segment->minimum_amount = $request->minimum_amount;
                $business_segment->minimum_amount_for = $request->minimum_amount_for;
                $business_segment->dine_in = $request->dine_in;
            }

            if ($sub_group_for_app == 2) { //for all $sub_group_for_app == 2 grocery or pharmacy
                $business_segment->grocery_configuration_instant_slot = $request->grocery_configuration_instant_slot;
            }


            if (!empty($request->password)) {
                $business_segment->password = Hash::make($request->password);
            }
            if (!empty($request->hasFile('business_logo'))) {
                $business_segment->business_logo = $this->uploadImage('business_logo', 'business_logo');
            }
            if (!empty($request->hasFile('login_background_image'))) {
                $business_segment->login_background_image = $this->uploadImage('login_background_image', 'business_login_background_image');
            }

            if (!empty($request->hasFile('business_profile_image'))) {
                $business_segment->business_profile_image = $this->uploadImage('business_profile_image', 'business_profile_image');
            }
            $bank_details = [
                'bank_name' => $request->bank_name,
                'account_holder_name' => $request->account_holder_name,
                'bank_code' => $request->bank_code,
                'account_number' => $request->account_number,
            ];
            $business_segment->bank_details = json_encode($bank_details);
            $business_segment->delivery_service = !empty($request->delivery_service) ? $request->delivery_service : 2;
            //            p($business_segment->bank_details);
            $business_segment->save();
            $arr_agencies = $request->delivery_service == 2 ? $request->driver_agency_id : [];
            $business_segment->DriverAgency()->sync($arr_agencies);
            //            p($business_segment);
            //create cofigurations for business segment
            $config = BusinessSegmentConfigurations::where('business_segment_id',  $business_segment->id)->first();
            if (empty($config)) {
                $config = new BusinessSegmentConfigurations;
                $config->business_segment_id = $business_segment->id;
                $config->save();
            }

            //create onesignal cofigurations for business segment
            $onesignal_config = BusinessSegmentOnesignal::where('business_segment_id',  $business_segment->id)->first();
            if (empty($onesignal_config)) {
                $onesignal_config = new BusinessSegmentOnesignal;
                $onesignal_config->business_segment_id = $business_segment->id;
            }
            if (!empty($request->application_key)) {
                $onesignal_config->application_key = $request->application_key;
            } else {
                $merchant_onesignal = Onesignal::where([['merchant_id', '=', $merchant_id]])->first();
                $onesignal_config->application_key = $merchant_onesignal->web_application_key;
            }
            $onesignal_config->save();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return redirect()->back()->withErrors($message);
            // Rollback Transaction
            DB::rollback();
        }
        // Commit Transaction
        DB::commit();
        $data = array('notification_type' => 'STORE_SIGNUP_APPROVED', 'segment_type' => $business_segment->Segment->slag);
        $arr_param = array(
            'business_segment_id' => $business_segment->id,
            'data' => $data,
            'message' => trans("$string_file.business_segment_signup__approved"),
            'merchant_id' => $business_segment->merchant_id,
            'title' => trans("$string_file.business_segment_signup__approved")
        );
        Onesignal::BusinessSegmentPushMessage($arr_param);
        return redirect()->route('merchant.business-segment.pending-details', $slug)->with('success', trans("$string_file.approved_successfully"));
    }




    public function getStripeConnectRequireDetails(Request $request)
    {
        $validator_array = array(
            'business_segment_id' => 'required|exists:business_segments,id',
        );
        $valid = validator($request->all(), $validator_array);
        if ($valid->fails()) {
            return error_response($valid->errors()->first());
        }
        $BusinessSegment = BusinessSegment::find($request->business_segment_id);
        $string_file = $this->getStringFile(null, $BusinessSegment->Merchant);
        $short_code = $BusinessSegment->CountryArea->Country->short_code;
        $required_array = array(
            array("key" => "ip_address", "display_text" => "IP Address", "display" => false, "type" => "text"),
            array("key" => "dob", "display_text" => "DOB", "display" => true, "type" => "select_dob"),
            //            array("key" => "identity_document", "display_text" => "Identity Document", "display" => true, "type" => "file"),
            array("key" => "postal_code", "display_text" => "Postal Code", "display" => true, "type" => "text"),
            array("key" => "address_line_1", "display_text" => "Address Line 1", "display" => true, "type" => "text"),
            array("key" => "city", "display_text" => "City", "display" => true, "type" => "text"),
        );
        switch ($short_code) {
            case 'US':
                array_push($required_array, array("key" => "account_number", "display_text" => "Account Number", "display" => true, "type" => "text"));
                array_push($required_array, array('key' => 'routing_number', "display_text" => "Routing Number", 'display' => true, 'type' => 'text'));
                array_push($required_array, array('key' => 'state', "display_text" => "State", 'display' => true, 'type' => 'text'));
                array_push($required_array, array('key' => 'address_line_2', "display_text" => "Address Line 2", 'display' => true, 'type' => 'text'));
                array_push($required_array, array("key" => "ssn", "display_text" => "SSN", "display" => true, "type" => "text"));
                break;
            case 'AU': // If contry is Australia
                array_push($required_array, array("key" => "account_number", "display_text" => "Account Number", "display" => true, "type" => "text"));
                array_push($required_array, array('key' => 'account_holder_name', "display_text" => "Account Holder Name", 'display' => true, 'type' => 'text'));
                array_push($required_array, array('key' => 'bsb_number', "display_text" => "BSB Number", 'display' => true, 'type' => 'text'));
                array_push($required_array, array('key' => 'abn', "display_text" => "ABN", 'display' => true, 'type' => 'text'));
                array_push($required_array, array('key' => 'state', "display_text" => "State", 'display' => true, 'type' => 'text'));
                array_push($required_array, array("key" => "ssn", "display_text" => "SSN", "display" => true, "type" => "text"));
                break;
            case 'LU':
                array_push($required_array, array("key" => "account_number", "display_text" => "Account Number (IBAN)", "display" => true, "type" => "text"));
                array_push($required_array, array("key" => "bsb_number", "display_text" => "BIC/Swift Code", "display" => true, "type" => "text"));
                break;
            case 'GB':
                array_push($required_array, array("key" => "account_number", "display_text" => "Account Number", "display" => true, "type" => "text"));
                array_push($required_array, array('key' => 'account_holder_name', "display_text" => "Account Holder Name", 'display' => true, 'type' => 'text'));
                array_push($required_array, array("key" => "ssn", "display_text" => "SSN", "display" => true, "type" => "text"));
                array_push($required_array, array("key" => "sort_code", "display_text" => "Sort Number", "display" => true, "type" => "number", "max" => 6));
                break;
            default:
                return $this->successResponse(trans("$string_file.stripe_not_support_in_your_country"));
        }
        return $this->successResponse("Success", $required_array);
    }


     public function stripeConnect($id)
    {
        try {
            $merchant_id = get_merchant_id();
            $driver = BusinessSegment::where([['merchant_id', '=', $merchant_id]])->find($id);
            $configuration = Configuration::select('stripe_connect_store_enable')->where('merchant_id', $merchant_id)->first();
            if ($configuration->stripe_connect_store_enable != 1) {
                return redirect()->back();
            }
            $merchant_stripe_config = MerchantStripeConnect::where('merchant_id', $merchant_id)->first();
            $stripe_docs_list = [];
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }
        return view('merchant.business-segment.stripe-connect', compact('id', 'driver', 'stripe_docs_list'));
    }


    public function SyncAllStripeConnect($id)
    {
        try {
            $businessSegments = BusinessSegment::where('merchant_id', $id)->whereNotNull('sc_account_id')->get();
            foreach ($businessSegments as  $businessSegment) {
                $status =  StripeConnect::retrieve_store_account_details($businessSegment->sc_account_id, $businessSegment->merchant_id);
                $businessSegment->is_stripe_connect = $status;
                $businessSegment->save();
            }
            return back()->with('success', 'Sync successfully.');
        } catch (\Exception $e) {
        return redirect()->back()->withErrors($e->getMessage());
        }
    }
    public function SyncStripeConnect($id)
    {
        try {
            $businessSegment = BusinessSegment::find($id);
            $status =  StripeConnect::retrieve_store_account_details($businessSegment->sc_account_id, $businessSegment->merchant_id);
            $businessSegment->is_stripe_connect = $status;
            $businessSegment->save();
            return back()->with('success', 'Sync successfully.');
        } catch (\Exception $e) {
           return redirect()->back()->withErrors($e->getMessage());
        }
    }
    public function DeleteStripeConnect($id)
    {
        try {
            $businessSegment = BusinessSegment::find($id);
            $status =  StripeConnect::delete_store_account_details($businessSegment->sc_account_id, $businessSegment->merchant_id);
            if ($status == false) {
                return $this->failedResponse('Failed to delete Stripe Connect account.');
            }
            $businessSegment->is_stripe_connect = 3;
            $businessSegment->sc_account_id = '';
            $businessSegment->save();
            return back()->with('success', 'Delete successfully.');
        } catch (\Exception $e) {
           return redirect()->back()->withErrors($e->getMessage());
        }
    }
}
