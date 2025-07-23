<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InfoSetting;
use Auth;
use App;
use App\Models\BusRouteMapping;
use App\Models\BusRoute;
use App\Models\LanguageBusRoute;
use App\Models\Segment;
use App\Models\ServiceTimeSlot;
use App\Traits\AreaTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Traits\MerchantTrait;
use App\Traits\BusStopTrait;
use App\Traits\BusRouteTrait;
use App\Traits\BusTrait;
use View;


class BusRouteMappingController extends Controller
{

    use BusStopTrait, MerchantTrait, AreaTrait, BusRouteTrait, BusTrait;
    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'bus_routes')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $bus_route_mapping = BusRouteMapping::whereHas('Bus', function ($q) use ($merchant_id) {
            $q->where('merchant_id', $merchant_id);
        })
            ->whereHas('BusRoute', function ($q) use ($merchant_id) {
                $q->where('merchant_id', $merchant_id);
            })
            ->groupBy('bus_route_id')
            ->groupBy('bus_id')
            ->latest()->paginate(25);
        return view('merchant.bus-booking.bus-route-mapping.index', compact('bus_route_mapping'));
    }

    /**
     * Add Edit form of duration
     */
    public function add(Request $request, $bus_route_id = null, $bus_id = null)
    {
        $merchant = get_merchant_id(false);

        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);

        $data = null;
        $service_time_slot = "";
        if ($bus_route_id && $bus_id) {
            $data = BusRouteMapping::where([['bus_route_id', '=', $bus_route_id], ['bus_id', '=', $bus_id]])->get();

            $selected_slots = $data->pluck('service_time_slot_detail_id')->toArray();

            $data = isset($data[0]) ? $data[0] : null;

            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");


            $merchant_id = $merchant->id;
            $request->merge(['segment_id' => $data->BusRoute->segment_id, 'merchant_id' => $merchant_id, 'country_area_id' => $data->BusRoute->country_area_id]);
            $segment_time_slot = ServiceTimeSlot::areaTimeSlot($request, $string_file);
            //    p($segment_time_slot);
            $slot_data = [
                'segment_id' => $data->BusRoute->segment_id,
                'segment_time_slot' => $segment_time_slot,
                'merchant_id' => $merchant_id,
                'selected_slots' => $selected_slots
            ];
            $service_time_slot =  View::make('merchant.bus-booking.bus-route-mapping.segment-time-slot')->with($slot_data)->render();
        } else {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title . ' ' . trans("$string_file.bus_route_mapping");

        $arr_buses = [];

        // p($arr_stop_points);
        $return['route_bus_mapping'] = [
            'data' => $data,
            'arr_routes' => $this->getRoutes($merchant->id),
            'submit_url' => route('bus_booking.bus_route_mapping.save', $bus_route_id, $bus_id),
            'title' => $title,
            'arr_buses' => $arr_buses,
            // 'arr_status' => add_blank_option(get_status(true, $string_file), trans("$string_file.select")),
            'submit_button' => $submit_button,
            'arr_status' => get_status(true, $string_file),
            'service_time_slot' => $service_time_slot
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.bus-booking.bus-route-mapping.form')->with($return);
    }

    /***
     * Save/update function of duration
     */
    public function save(Request $request, $bus_route_id = NULL, $bus_id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            'bus_route_id' => ['required'],
            'bus_id' => ['required'],
            'arr_time_slot' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        $arr_time_slot = $request->input('arr_time_slot');
        DB::beginTransaction();
        try {
            BusRouteMapping::where([['bus_route_id', '=', $request->bus_route_id], ['bus_id', '=', $request->bus_id]])->delete();
            foreach ($arr_time_slot  as $service_time_slot_detail_id) {
                $route = BusRouteMapping::where([['bus_route_id', '=', $bus_route_id], ['bus_id', '=', $bus_id], ['service_time_slot_detail_id', '=', $service_time_slot_detail_id]])->first();
                if (!$route) {
                    $route = new BusRouteMapping;
                    $route->bus_id = $request->bus_id;
                    $route->bus_route_id = $request->bus_route_id;
                }
                $route->service_time_slot_detail_id = $service_time_slot_detail_id;
                $route->status = $request->status; // bus stop id
                $route->save();
            }
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->route('bus_booking.bus_route_mapping')->withSuccess(trans("$string_file.saved_successfully"));
    }

//    // public function ChangeStatus($id, $status)
//    // {
//    //     $validator = Validator::make(
//    //         [
//    //             'id' => $id,
//    //             'status' => $status,
//    //         ],
//    //         [
//    //             'id' => ['required'],
//    //             'status' => ['required', 'integer', 'between:1,2'],
//    //         ]
//    //     );
//    //     if ($validator->fails()) {
//    //         return redirect()->back();
//    //     }
//    //     $merchant = get_merchant_id(false);
//    //     $string_file = $this->getStringFile(NULL, $merchant);
//    //     if ($merchant->demo == 1) {
//    //         return redirect()->back()->withErrors(trans("$string_file.demo_warning_message"));
//    //     }
//    //     $merchant_id = $merchant->id;
//    //     $BusRoute = BusRoute::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
//    //     $BusRoute->status = $status;
//    //     $BusRoute->save();
//    //     return redirect()->back()->withSuccess(trans("$string_file.status_updated"));
//    // }
//    // public function SaveLanguageStops($merchant_id, $id, $title)
//    // {
//    //     LanguageBusRoute::updateOrCreate([
//    //         'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'bus_route_id' => $id
//    //     ], [
//    //         'title' => $title,
//    //     ]);
//    // }
//    // public function getBusRouteByArea(Request $request)
//    // {
//    //     $validator = Validator::make($request->all(), [
//    //         'country_area_id' => ['required']
//    //     ]);
//    //     if ($validator->fails()) {
//    //         $errors = $validator->messages()->all();
//    //         p($errors);
//    //         // return redirect()->back()->withInput($request->input())->withErrors($errors);
//    //     }
//    //     $merchant = get_merchant_id(false);
//
//    //     $string_file = $this->getStringFile();
//    //     $arr_routes =  $this->getRoutes($merchant->id, $request->country_area_id);
//
//    //     if (empty($arr_routes)) {
//    //         echo "<option value=''>" . trans("$string_file.no_service_area") . "</option>";
//    //     } else {
//    //         echo "<option value=''>" . trans("$string_file.bus_routes") . "</option>";
//    //         foreach ($arr_routes as $key => $value) {
//    //             echo "<option id='" . $key . "' value='" . $key . "'>" . $value . "</option>";
//    //         }
//    //     }
//    //     // return $arr_routes;
//    // }
//
    public function routeBuses(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bus_route_id' => [
                'required'
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $errors[0];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);

        $bus_route = new BusRoute;
        $route = $bus_route->busRoute($request->bus_route_id);

        $country_area_id = $route->country_area_id;
        $segment_id = $route->segment_id;
        $merchant_id = $merchant->id;
        $request->merge(['segment_id' => $segment_id, 'merchant_id' => $merchant_id, 'country_area_id' => $country_area_id]);
        $segment_time_slot = ServiceTimeSlot::areaTimeSlot($request, $string_file);

        $data = [
            'segment_id' => $segment_id,
            'segment_time_slot' => $segment_time_slot,
            'merchant_id' => $merchant_id,
            'selected_slots' => []
        ];
        $service_time_slot =  View::make('merchant.bus-booking.bus-route-mapping.segment-time-slot')->with($data)->render();
        $buses  = $this->arrBusDropDown($this->busesByArea($country_area_id),$string_file);
        return  ['buses' => $buses, 'service_time_slot' => $service_time_slot];
    }
}
