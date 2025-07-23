<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InfoSetting;
use Auth;
use App;
use App\Models\BusStop;
use App\Models\LanguageBusStop;
use App\Models\BusPickupDropPoint;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Traits\MerchantTrait;
use App\Traits\BusStopTrait;
use App\Traits\BusTrait;
use Form;

class BusStopController extends Controller
{

    use BusStopTrait, MerchantTrait, BusTrait;
    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'bus_stops')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $checkPermission =  check_permission(1, 'bus_stops_BUS_BOOKING');
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }

        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $bus_stops = BusStop::with("Segment","ServiceType")->where('merchant_id', $merchant_id)->latest()->paginate(25);
        $status = get_active_status("web", $string_file);
        return view('merchant.bus-booking.stops.index', compact('bus_stops', 'status'));
    }

    /**
     * Add form
     */
    public function add()
    {
        $merchant = get_merchant_id(false);
        // $merchant_id = $merchant->id;
        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);
        $pre_title = trans("$string_file.add");
        $submit_button = trans("$string_file.save");
        $title = $pre_title . ' ' . trans("$string_file.bus_stops");
        $service_types = App\Models\ServiceType::whereHas("Segment", function($q){
            $q->where("slag", "BUS_BOOKING");
        })->with("Segment")->get();

        $service_type_arr = $this->getMerchantBusSegmentServices($merchant->id);
        $pickup_drop_points = BusPickupDropPoint::where("merchant_id", $merchant->id)->get();
        $pickup_drop_points_arr = [];
        foreach($pickup_drop_points as $point){
            $pickup_drop_points_arr[$point->id] = $point->Name;
        }
        $return['bus_stop'] = [
            'submit_url' => route('bus_booking.save_bus_stops'),
            'title' => $title,
            'arr_status' => add_blank_option(get_active_status("web", $string_file), trans("$string_file.select")),
            'submit_button' => $submit_button,
            'service_type_arr' => $service_type_arr,
            'pickup_drop_points_arr' => $pickup_drop_points_arr
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.bus-booking.stops.form')->with($return);
    }

    /**
     * Edit form
     */
    public function edit($id)
    {
        $merchant = get_merchant_id(false);
        // $merchant_id = $merchant->id;
        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);
        $data = BusStop::findorfail($id);
        $pre_title = trans("$string_file.edit");
        $submit_button = trans("$string_file.update");

        $title = $pre_title . ' ' . trans("$string_file.bus_stops");

        $pickup_drop_points = BusPickupDropPoint::where("merchant_id", $merchant->id)->get();
        $pickup_drop_points_arr = [];
        foreach($pickup_drop_points as $point){
            $pickup_drop_points_arr[$point->id] = $point->Name;
        }
        $return['bus_stop'] = [
            'data' => $data,
            'submit_url' => route('bus_booking.update_bus_stops', $id),
            'title' => $title,
            'arr_status' => add_blank_option(get_status(true, $string_file), trans("$string_file.select")),
            'submit_button' => $submit_button,
            'pickup_drop_points_arr' => $pickup_drop_points_arr
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.bus-booking.stops.edit')->with($return);
    }

    /***
     * Save function
     */
    public function save(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            'stop_name' => [
                'required', 'max:80',
                Rule::unique('language_bus_stops', 'name')->where(function ($query) use ($merchant_id) {
                    return $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', App::getLocale()]]);
                })
            ],
            'service_type_id' => 'required|exists:service_types,id',
            'status' => 'required|between:1,2',
            'latitude' => 'required',
            'longitude' => 'required',
            'address' => 'required',

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $service_type = App\Models\ServiceType::find($request->service_type_id);

            $stop = new BusStop;
            $stop->merchant_id = $merchant_id;
            $stop->segment_id = $service_type->segment_id;
            $stop->service_type_id = $service_type->id;
            $stop->latitude = $request->latitude;
            $stop->latitude = $request->latitude;
            $stop->longitude = $request->longitude;
            $stop->address = $request->address;
            $stop->status = $request->status;
            $stop->save();
            $this->SaveLanguageStops($merchant_id, $stop->id, $request->stop_name);

            if(isset($request->pickup_drop_point_ids) && !empty($request->pickup_drop_point_ids)){
                $stop->BusPickupDropPoint()->sync($request->pickup_drop_point_ids);
            }
        } catch (\Exception $e) {
            DB::rollback();
            $message = $e->getMessage();
            return redirect()->back()->withErrors($message);
        }
        DB::commit();
        return redirect()->route('bus_booking.bus_stops')->withSuccess(trans("$string_file.saved_successfully"));
    }

    /***
     * Update function
     */
    public function update(Request $request, $id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            'stop_name' => [
                'required', 'max:80',
                Rule::unique('language_bus_stops', 'name')->where(function ($query) use ($merchant_id, $id) {
                    return $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', App::getLocale()]])->where(function ($qq) use ($id) {
                        if (!empty($id)) {
                            $qq->where('bus_stop_id', '!=', $id);
                        }
                    });
                })
            ],
            'status' => 'required|between:1,2',
            'latitude' => 'required',
            'longitude' => 'required',
            'address' => 'required',

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $stop = BusStop::Find($id);
            $stop->latitude = $request->latitude;
            $stop->longitude = $request->longitude;
            $stop->address = $request->address;
            $stop->merchant_id = $merchant_id;
            $stop->status = $request->status;
            $stop->save();
            $this->SaveLanguageStops($merchant_id, $stop->id, $request->stop_name);

            if(isset($request->pickup_drop_point_ids) && !empty($request->pickup_drop_point_ids)){
                $stop->BusPickupDropPoint()->sync($request->pickup_drop_point_ids);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return redirect()->back()->withErrors($message);
        }
        DB::commit();
        return redirect()->route('bus_booking.bus_stops')->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function ChangeStatus($id, $status)
    {
        $validator = Validator::make(
            [
                'id' => $id,
                'status' => $status,
            ],
            [
                'id' => ['required'],
                'status' => ['required', 'integer', 'between:1,2'],
            ]
        );
        if ($validator->fails()) {
            return redirect()->back();
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        if ($merchant->demo == 1) {
            return redirect()->back()->withErrors(trans("$string_file.demo_warning_message"));
        }
        $merchant_id = $merchant->id;
        $BusStop = BusStop::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $BusStop->status = $status;
        $BusStop->save();
        return redirect()->back()->withSuccess(trans("$string_file.status_updated"));
    }

    public function SaveLanguageStops($merchant_id, $id, $BusStopname)
    {
        LanguageBusStop::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'bus_stop_id' => $id
        ], [
            'name' => $BusStopname,
        ]);
    }

    public function getStops(Request $request){
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $bus_stops = BusStop::where(array("merchant_id" => $merchant->id, "service_type_id" => $request->service_type_id))->get();
        $options = "";
        if (count($bus_stops) == 0) {
            $options .= "<option value=''>".trans("$string_file.no_bus_stops")."</option>";
        } else {
            foreach($bus_stops as $bus_stop){
                $options .= "<option value='".$bus_stop->id."'>$bus_stop->Name</option>";
            }
        }
        echo $options;
    }
}
