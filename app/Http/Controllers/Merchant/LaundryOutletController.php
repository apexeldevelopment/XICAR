<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\PolygenController;
use App\Models\InfoSetting;
use App\Models\LaundryOutlet\LaundryOutlet;
use App\Models\LaundryOutlet\LaundryOutletOnesignal;
use App\Models\Onesignal;
use App\Models\Segment;
use App\Traits\AreaTrait;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LaundryOutletController extends Controller
{
    use ImageTrait, AreaTrait, MerchantTrait;
    //
    public function index(Request $request)
    {
        $checkPermission = check_permission(1, 'create_outlet');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        $title = trans($string_file . '.laundry_outlet');

        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }

        $outlets['data'] = LaundryOutlet::with('Merchant')
            ->where([['merchant_id', '=', $merchant_id]])
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
        $outlets['title'] = $title;
        $outlets['arr_search'] = $request->all();
        $info_setting = InfoSetting::where('slug', 'LAUNDRY_OUTLET')->first();
        $outlets['info_setting'] = $info_setting;
        return view('merchant.laundry-outlet.index')->with($outlets);
    }

    public function add(Request $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $checkPermission = check_permission(1, 'create_outlet');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $laundry_outlet = NULL;
        $merchant_id = $merchant->id;
        $is_demo = false;
        $string_file = $this->getStringFile($merchant_id);
        $title = trans($string_file . '.laundry_store');

        $save_url = route('merchant.laundry-outlet.save');
        $prefix = trans("$string_file.add");
        if (!empty($id)) {
            $laundry_outlet = LaundryOutlet::Find($id);
            if (empty($laundry_outlet->id)) {
                return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
            }
            $prefix = trans("$string_file.edit");
            $save_url = route('merchant.laundry-outlet.save', ['id' => $id]);

            if ($merchant->demo == 1 && $laundry_outlet->country_area_id == 3) {
                $is_demo = true;
            }
        }
        $arr_segment = get_merchant_segment(false);
        $arr_country = $merchant->Country;
        $arr_day = get_days($string_file);
        $info_setting = InfoSetting::where('slug', 'LAUNDRY_OUTLET')->first();
        $arr_merchant_service_type = $merchant->ServiceType->pluck('type')->toArray();
        $data['data'] = [
            'arr_day' => $arr_day,
            'countries' => $arr_country,
            'save_url' => $save_url,
            'title' => $prefix . ' ' . $title,
            'laundry_outlet' => $laundry_outlet,
            'segments' => $arr_segment,
            'arr_status' => get_active_status("web", $string_file),
            'is_popular' => get_status(true, $string_file),
            'self_pickup' => get_status(true,$string_file),
        ];
        $data['info_setting'] = $info_setting;
        $data['is_demo'] = $is_demo;
        $onesignal_config = LaundryOutletOnesignal::where('laundry_outlet_id', $id)->first();
        $data['onesignal_config'] = $onesignal_config;
        $data['arr_merchant_service_type']=$arr_merchant_service_type;
        if(count($arr_country) > 0){
            foreach($arr_country as $country){
                if(count($country->countryArea) > 0){
                    $lat_long = json_decode(($country->countryArea)[0]->AreaCoordinates,true)[1];
                    $data['default_lat'] = $lat_long['latitude'];
                    $data['default_long'] = $lat_long['longitude'];
                    break;
                }
            }
        }
        return view('merchant.laundry-outlet.form')->with($data);
    }

    /*Save or Update*/
    public function save(Request $request,$id = NULL)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = $merchant->id;
        $arr_validate = [
             'full_name' => 'required |unique:laundry_outlets,full_name,' . $id . ',id,merchant_id,' . $merchant_id,
            'email' => 'required|email|unique:laundry_outlets,email,' . $id . ',id,merchant_id,' . $merchant_id,
            'phone_number' => 'required|unique:laundry_outlets,phone_number,' . $id . ',id,merchant_id,' . $merchant_id,
            'password' => 'required_without:id',
            'business_logo' => 'required_without:id|mimes:jpeg,jpg,png',
            'login_background_image' => 'mimes:jpeg,png,jpg,gif,svg',
            'country_id' => 'required',
            'address' => 'required',
            'landmark' => 'required',
            'open_time' => 'required',
            'close_time' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'commission_method' => 'required',
            'commission' => 'required',
            'rating' => 'required',
            'business_profile_image' => 'mimes:jpeg,png,jpg,gif,svg',
            //dimensions:width=800,height=230',
        ];

        $arr_msg = [];
        if (empty($id)){
            $alias_name = str_slug($request->input('full_name'));
            $alias_exists = LaundryOutlet::where([['alias_name', '=', $alias_name]])->first();
            if(!empty($alias_exists)){
                $alias_name = $alias_name.'-'.$merchant->id;
            }
            $request->merge(['alias_name' => $alias_name]);
            $arr_validate = array_merge(
                $arr_validate,
                array(
                    'alias_name' => 'required|max:255|unique:laundry_outlets',
                )
            );
            $arr_msg = array(
                'alias_name.unique' => 'A Laundry Outlet with same name is already present, please choose other name.',
            );
        }

        $validator = Validator::make($request->all(), $arr_validate, $arr_msg);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        $country_area_id = NULL;
        $arr_country_area = $merchant->CountryArea->where('country_id', $request->country_id)->where('status', 1);
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
        // Begin Transaction
        DB::beginTransaction();
        try {
            $segment=  Segment::where('segment_group_id',1)->where('slag','LAUNDRY_OUTLET')->select('id')->first();
            if (!empty($id)) {
                $laundry_outlet = LaundryOutlet::Find($id);
            } else {
                $laundry_outlet = new LaundryOutlet();
                $laundry_outlet->alias_name = $alias_name;
                $laundry_outlet->merchant_id = $merchant_id;
            }

            $laundry_outlet->country_id = $request->country_id;
            $laundry_outlet->full_name = $request->full_name;
            $laundry_outlet->phone_number = $request->phone_number;
            $laundry_outlet->email = $request->email;
            $laundry_outlet->address = $request->address;
            $laundry_outlet->landmark = $request->landmark;
            $laundry_outlet->open_time = json_encode($request->open_time);
            $laundry_outlet->close_time = json_encode($request->close_time);
            $laundry_outlet->status = $request->status;
            $laundry_outlet->latitude = $request->latitude;
            $laundry_outlet->longitude = $request->longitude;
            $laundry_outlet->is_popular = !empty($request->is_popular)? $request->is_popular : 2;
            $laundry_outlet->country_area_id = $country_area_id;
//            $laundry_outlet->commission_type = $request->commission_type;
            $laundry_outlet->commission_method = $request->commission_method;
            $laundry_outlet->commission = $request->commission;
            $laundry_outlet->rating = $request->rating;
            $laundry_outlet->segment_id = $segment->id;

            $bank_details = [
                'bank_name' => $request->bank_name,
                'account_holder_name' => $request->account_holder_name,
                'bank_code' => $request->bank_code,
                'account_number' => $request->account_number,
            ];
            $laundry_outlet->bank_details = json_encode($bank_details);
            if (!empty($request->password)) {
                $laundry_outlet->password = Hash::make($request->password);
            }
            if (!empty($request->hasFile('business_logo'))) {
                $laundry_outlet->business_logo = $this->uploadImage('business_logo', 'laundry_outlet_logo');
            }
            if (!empty($request->hasFile('login_background_image'))) {
                $laundry_outlet->login_background_image = $this->uploadImage('login_background_image', 'laundry_outlet_login_background_image');
            }

            if (!empty($request->hasFile('business_profile_image'))) {
                $laundry_outlet->business_profile_image = $this->uploadImage('business_profile_image', 'laundry_outlet_profile_image');
            }
            $bank_details = [
                'bank_name' => $request->bank_name,
                'account_holder_name' => $request->account_holder_name,
                'bank_code' => $request->bank_code,
                'account_number' => $request->account_number,
            ];
            $laundry_outlet->bank_details = json_encode($bank_details);

            $laundry_outlet->save();
            // onesignal cofigurations for Laundry Outlets
            $onesignal_config = LaundryOutletOnesignal::where('laundry_outlet_id',  $laundry_outlet->id)->first();
            if (empty($onesignal_config)) {
                $onesignal_config = new LaundryOutletOnesignal;
                $onesignal_config->laundry_outlet_id = $laundry_outlet->id;
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
            DB::rollback();
        }
        DB::commit();
        return redirect()->route('laundry-outlet.index')->with('success', trans("$string_file.added_successfully"));
    }

//    function getMerchantCountry()
//    {
//        $merchant_id = get_merchant_id();
//        $countries = Country::select('id', 'phonecode')->where('merchant_id', $merchant_id)->get()->toArray();
//        $arr_country = [];
//        foreach ($countries as $country) {
//            $arr_country[$country['id']] = $country['phonecode'];
//        }
//        return $arr_country;
//    }
//
//    function getSegment($slug)
//    {
//        return Segment::select('id')->where('slag', $slug)->first();
//    }

//
//    public function getLaundyOutlet(Request $request)
//    {
//        $id = $request->id;
//        $area_id = $request->area_id;
//        $store = get_laundry_outlet(false);
//        $merchant_id = $store->merchant_id;
//
//        return  LaundryOutlet::whereHas('Segment', function ($query) use ($merchant_id, $id) {
//            $query->whereHas('Merchant', function ($query) use ($merchant_id) {
//                $query->where('id', $merchant_id);
//            })->where('id', $id);
//        })
//            ->where(function ($q) use ($area_id) {
//                if (!empty($area_id)) {
//                    $q->where('country_area_id', $area_id);
//                }
//            })
//            ->pluck('full_name', 'id')->toArray();
//    }

    //used by adversitisement banner
    public function getLaundryOutlets(Request $request)
    {
        $outlets = [];
        $merchant_id = get_merchant_id();
        $id = $request->query("id");
        if($id){
            $outlets = LaundryOutlet::where([['merchant_id', '=', $merchant_id], ['segment_id', $id]])
//                ->where(function ($q) use ($area_id) {
//                    if (!empty($area_id)) {
//                        $q->where('country_area_id', $area_id);
//                    }
//                })
                ->pluck('full_name', 'id')->toArray();
        }
        return $outlets;
    }
}
