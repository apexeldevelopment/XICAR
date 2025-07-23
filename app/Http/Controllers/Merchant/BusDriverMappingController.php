<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InfoSetting;
use Auth;
use App;
use App\Models\BusDriverMapping;
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


class BusDriverMappingController extends Controller
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

        $bus_driver_mapping = BusDriverMapping::whereHas('Bus', function ($q) use ($merchant_id) {
            $q->where('merchant_id', $merchant_id);
        })
            ->whereHas('BusRoute', function ($q) use ($merchant_id) {
                $q->where('merchant_id', $merchant_id);
            })
            ->groupBy('bus_route_id')
            ->groupBy('bus_id')
            ->groupBy('driver_id')
            ->latest()->paginate(25);
        return view('merchant.bus-booking.bus-driver-mapping.index', compact('bus_driver_mapping'));
    }

    /**
     * Add Edit form of duration
     */
    public function add(Request $request, $bus_route_id = null, $bus_id = null, $driver_id = null)
    {
        $merchant = get_merchant_id(false);

        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);

        $data = null;
        $service_time_slot = "";
        if ($bus_route_id && $bus_id && $driver_id) {
            $data = BusDriverMapping::where([['bus_route_id', '=', $bus_route_id], ['bus_id', '=', $bus_id], ['driver_id', '=', $driver_id]])->get();
            $selected_slots = $data->pluck('service_time_slot_detail_id')->toArray();

            $data = isset($data[0]) ? $data[0] : null;

            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");


            $merchant_id = $merchant->id;
            $request->merge(['segment_id' => $data->BusRoute->segment_id, 'merchant_id' => $merchant_id, 'country_area_id' => $data->BusRoute->country_area_id, "bus_route_id" => $bus_route_id, "bus_id" => $bus_id]);
            $segment_time_slot = ServiceTimeSlot::busRouteBusTimeSlot($request, $string_file);
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
        $title = $pre_title . ' ' . trans("$string_file.bus_driver_mapping");

        $arr_buses = [];

        $return['bus_driver_mapping'] = [
            'data' => $data,
            'arr_routes' => $this->getRoutes($merchant->id),
            'submit_url' => route('bus_booking.bus_driver_mapping.save', $bus_route_id, $bus_id),
            'title' => $title,
            'arr_buses' => $arr_buses,
            'submit_button' => $submit_button,
            'arr_status' => get_status(true, $string_file),
            'service_time_slot' => $service_time_slot
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.bus-booking.bus-driver-mapping.form')->with($return);
    }
    /***
     * Save/update function of duration
     */
    public function save(Request $request, $bus_route_id = NULL, $bus_id = NULL)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            'bus_route_id' => [
                'required_if:bus_driver_mapping_id,==',

            ],
            'bus_id' => 'required_if:bus_driver_mapping_id,==',
            'arr_time_slot' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        $arr_time_slot = $request->input('arr_time_slot');
        DB::beginTransaction();
        try {
            BusDriverMapping::where([['bus_route_id', '=', $request->bus_route_id], ['bus_id', '=', $request->bus_id], ['driver_id', '=', $request->driver_id]])->whereNotIn('service_time_slot_detail_id', $arr_time_slot)->delete();
//            if ($bus_route_id && $bus_id && $request->driver_id != "") {
//                // remove all existing data and create new records
//                $existing_bus_driver_mapping = BusDriverMapping::where([['bus_route_id', '=', $request->bus_route_id], ['bus_id', '=', $request->bus_id], ['driver_id', '=', $request->driver_id]])->whereNotIn('service_time_slot_detail_id', $arr_time_slot)->get();
//            }
            $not_added_time_slots = [];
            $added_count = 0;
            foreach ($arr_time_slot  as $service_time_slot_detail_id) {
                $existing_bus_mapping = BusDriverMapping::where([['bus_route_id', '=', $request->bus_route_id], ['bus_id', '=', $request->bus_id], ['service_time_slot_detail_id', "=", $service_time_slot_detail_id]])->first();
                if(!empty($existing_bus_mapping)){
                    if($existing_bus_mapping->driver_id != $request->driver_id){
                        array_push($not_added_time_slots, $existing_bus_mapping->ServiceTimeSlotDetail->from_time);
                    }
                }else{
                    $route = new BusDriverMapping;
                    $route->bus_id = $request->bus_id;
                    $route->bus_route_id = $request->bus_route_id;
                    $route->driver_id = $request->driver_id;
                    $route->service_time_slot_detail_id = $service_time_slot_detail_id;
                    $route->status = 1;
                    $route->save();
                    $added_count++;
                }
            }
            DB::commit();
            $error = false;
            if($added_count > 0){
                if(empty($not_added_time_slots)){
                    $message = trans("$string_file.saved_successfully");
                }else{
                    $message = trans("$string_file.partial_added_successfully"). " ". trans("$string_file.not_saved_for"). " ".implode(",", $not_added_time_slots);
                }
            }else{
                if(empty($not_added_time_slots)){
                    $message = trans("$string_file.saved_successfully");
                }else{
                    $error = true;
                    $message = trans("$string_file.data_duplicacy")." ".trans("$string_file.not_saved_for"). " ".implode(",", $not_added_time_slots);
                }
            }
            if($error){
                return redirect()->route('bus_booking.bus_driver_mapping')->withErrors($message);
            }else{
                return redirect()->route('bus_booking.bus_driver_mapping')->withSuccess($message);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return redirect()->back()->withErrors($message);
        }
    }

     public function delete(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'bus_route_id' => 'required',
             'bus_id' => 'required',
             'driver_id' => 'required',
         ]);
         if ($validator->fails()) {
             $errors = $validator->messages()->all();
             return array("result" => 0, "message" => $errors[0]);
         }
         $merchant = get_merchant_id(false);
         $string_file = $this->getStringFile(NULL, $merchant);
         if ($merchant->demo == 1) {
             return array("result" => 0, "message" => trans("$string_file.demo_warning_message"));
         }
         $merchant_id = $merchant->id;
         $arr = array(
             'bus_route_id' => $request->bus_route_id,
             'bus_id' => $request->bus_id,
             'driver_id' => $request->driver_id,
         );
         BusDriverMapping::whereHas("BusRoute", function($q) use($merchant_id){
             $q->where("merchant_id", $merchant_id);
         })->where($arr)->delete();
         return array("result" => 1, "message" => trans("$string_file.deleted_successfully"));
     }

    public function getDrivers(Request $request){
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
        $bus_route = BusRoute::find($request->bus_route_id);
        $drivers = App\Models\Driver::where(["merchant_id" => $merchant->id, "segment_group_id" => 4, "signupStep" => 9, "country_area_id" => $bus_route->country_area_id])->get();
        $driver_html = "";
        if (empty($drivers)) {
            $driver_html = "<option value=''>" . trans("$string_file.no_drivers") . "</option>";
        } else {
            $driver_html .= "<option value=''>" . trans("$string_file.select") . "</option>";
            foreach ($drivers as $key => $value) {
                $driver_html .= "<option value='" . $value->id . "'>" . $value->first_name." ".$value->last_name." (".$value->phoneNumber . ")</option>";
            }
        }
        return $driver_html;
    }

    public function getBusRouteBusTimeSlot(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_route_id' => [
                'required'
            ],
            'bus_id' => [
                'required'
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $errors[0];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $bus_route = BusRoute::find($request->bus_route_id);

        $request->merge(['segment_id' => $bus_route->segment_id, 'merchant_id' => $bus_route->merchant_id, 'country_area_id' => $bus_route->country_area_id, "bus_route_id" => $request->bus_route_id, "bus_id" => $request->bus_id]);
        $segment_time_slot = ServiceTimeSlot::busRouteBusTimeSlot($request, $string_file);

        $data = [
            'segment_id' => $bus_route->segment_id,
            'segment_time_slot' => $segment_time_slot,
            'merchant_id' => $bus_route->merchant_id,
            'selected_slots' => []
        ];
        $service_time_slot =  View::make('merchant.bus-booking.bus-route-mapping.segment-time-slot')->with($data)->render();
        return  $service_time_slot;
    }
}
