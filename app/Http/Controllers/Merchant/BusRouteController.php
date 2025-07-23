<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InfoSetting;
use Auth;
use App;
use App\Models\BusRoute;
use App\Models\Segment;
use App\Models\LanguageBusRoute;
use App\Traits\AreaTrait;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Traits\MerchantTrait;
use App\Traits\BusStopTrait;
use App\Traits\BusRouteTrait;
use App\Traits\BusTrait;


class BusRouteController extends Controller
{
    use BusStopTrait, MerchantTrait, AreaTrait, BusRouteTrait, BusTrait;
    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'bus_routes')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $checkPermission =  check_permission(1, 'bus_routes_BUS_BOOKING');
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }

        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $bus_routes = BusRoute::where('merchant_id', $merchant_id)->latest()->paginate(25);
        $status = get_active_status("web", $string_file);
        return view('merchant.bus-booking.routes.index', compact('bus_routes', 'status'));
    }

    /**
     * Add Edit form of duration
     */
    public function add($id = null)
    {
        $merchant = get_merchant_id(false);

        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);
        $data = [];
        if (!empty($id)) {
            $data = BusRoute::where('id',$id)->first();
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");
        } else {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title . ' ' . trans("$string_file.bus_routes");
        $days_full = Config::get("custom.days_full");
        $return['bus_route'] = [
            'data' => $data,
            'days_full' => $days_full,
            'arr_stop_points' => $this->getStopPoints($merchant->id),
            'arr_area' => $this->getMerchantCountryArea($this->getAreaList(false, true, [], null, null, false, false)->get()),
            'submit_url' => route('bus_booking.save_bus_routes', $id),
            'title' => $title,
            'arr_status' => add_blank_option(get_active_status("web", $string_file), trans("$string_file.select")),
            'submit_button' => $submit_button,
            'service_type_arr' => add_blank_option($this->getMerchantBusSegmentServices($merchant->id), trans("$string_file.select"))
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.bus-booking.routes.form')->with($return);
    }

    /***
     * Save/update function of duration
     */
    public function save(Request $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                Rule::unique('language_bus_routes', 'title')->where(function ($query) use ($merchant_id, $id) {
                    return $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', App::getLocale()]])->where(function ($qq) use ($id) {
                        if (!empty($id)) {
                            $qq->where('bus_route_id', '!=', $id);
                        }
                    });
                })
            ],
            'status' => 'required|between:1,2',
            'start_point' => 'required',
            'end_point' => 'required|different:start_point',
            'service_type_id' => 'required',
        //            'slab' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $route = BusRoute::Find($id);
            } else {
                $route = new BusRoute;
                $route->merchant_id = $merchant_id;
                $service_type = App\Models\ServiceType::find($request->service_type_id);
                $route->service_type_id = $service_type->id;
                $route->segment_id = $service_type->segment_id;

            }
            $route->country_area_id = $request->country_area_id;
            $route->start_point = $request->start_point; // bus stop id
            $route->end_point = $request->end_point; // bus stop id
            $route->status = $request->status;
            $route->save();

            if(empty($id)){
                $route->StopPoints()->attach($request->start_point, ['bus_stop_id' => $route->start_point,'end_bus_stop_id' => $route->end_point, 'sequence' => 1]);
            }

            $this->SaveLanguageStops($merchant_id, $route->id, $request->title);

            DB::commit();
            return redirect()->route('bus_booking.bus_routes')->withSuccess(trans("$string_file.saved_successfully"));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }
    /**
     * Add Edit Config form
     */
    public function addConfig($id)
    {
        $merchant = get_merchant_id(false);

        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);

        $data = BusRoute::where('id',$id)->first();
        $data->segment_service_type_name = $data->Segment->Name($data->merchant_id)." / ".$data->ServiceType->ServiceName($data->merchant_id);
        $title = trans("$string_file.bus_route")." ".trans("$string_file.config");
        $submit_button = trans("$string_file.save");

        $return['bus_route'] = [
            'data' => $data,
            'arr_stop_points' => $this->getStopPoints($merchant->id, null, "drop_down", $data->service_type_id, array($data->start_point, $data->end_point)),
            'submit_url' => route('bus_booking.save_bus_route_config', $id),
            'title' => $title,
            'arr_status' => add_blank_option(get_status(true, $string_file), trans("$string_file.select")),
            'submit_button' => $submit_button,
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.bus-booking.routes.config')->with($return);
    }

    /***
     * Save/update Config function
     */
    public function saveConfig(Request $request, $id)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);

        DB::beginTransaction();
        try {
            $route = BusRoute::Find($id);
            $route->is_configured = 1;
            $route->save();

            $stop_mapping = [];
            if(!empty($request->stop_points) && count($request->stop_points) == 1){
                array_push($stop_mapping, array("start_point" => $request->start_point, "end_point" => $request->stop_points[0], 'sequence' => 1, 'time' => 0));
                array_push($stop_mapping, array("start_point" => $request->stop_points[0], "end_point" => $request->end_point, 'sequence' => 2, 'time' => $request->end_time));
            }elseif(!empty($request->stop_points) && count($request->stop_points) > 1){
                $time = $request->stop_time;
                array_push($time, $request->end_time);
                array_push($stop_mapping, array("start_point" => $request->start_point, "end_point" => $request->stop_points[0], 'sequence' => 1, 'time' => $time[0]));
                $sequence = 1;
                foreach($request->stop_points as $key => $stop){
                    $b = isset($request->stop_points[$key+1]) ? $request->stop_points[$key+1] : $request->end_point;
                    array_push($stop_mapping, array("start_point" => $stop, "end_point" => $b, 'sequence' => $sequence+1, 'time' => $time[$key+1]));
                }
            }else{
                array_push($stop_mapping, array("start_point" => $request->start_point, "end_point" => $request->end_point, 'sequence' => 1, 'time' => $request->end_time));
            }
            // return $stop_mapping;
            // delete all stop points from route
            $route->StopPoints()->detach();
            $sequence = 1;
            foreach($stop_mapping as $item){
                $route->StopPoints()->attach($item['start_point'], ['bus_stop_id' => $item['start_point'],'end_bus_stop_id' => $item['end_point'], 'sequence' => $sequence++, 'time' => $item['time']]);
            }
            DB::commit();
            return redirect()->route('bus_booking.bus_routes')->withSuccess(trans("$string_file.saved_successfully"));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
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
        $BusRoute = BusRoute::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $BusRoute->status = $status;
        $BusRoute->save();
        return redirect()->back()->withSuccess(trans("$string_file.status_updated"));
    }

    public function SaveLanguageStops($merchant_id, $id, $title)
    {
        LanguageBusRoute::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'bus_route_id' => $id
        ], [
            'title' => $title,
        ]);
    }

    public function getBusRouteByArea(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_area_id' => ['required']
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            echo $errors[0];
        }
        $merchant = get_merchant_id(false);

        $string_file = $this->getStringFile();
        $arr_routes =  $this->getRoutes($merchant->id, $request->country_area_id);

        if (empty($arr_routes)) {
            echo "<option value=''>" . trans("$string_file.no_bus_route") . "</option>";
        } else {
            echo "<option value=''>" . trans("$string_file.bus_routes") . "</option>";
            foreach ($arr_routes as $key => $value) {
                echo "<option id='" . $key . "' value='" . $key . "'>" . $value . "</option>";
            }
        }
    }

    public function getBusStopsByRoute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bus_route_id' => [
                'required'
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            echo $errors[0];
        }

        $bus_route = new BusRoute;
        $route = $bus_route->busRoute($request->bus_route_id);

        $merchant = get_merchant_id(false);
        $arr_stop_points =  $this->getOnlyStopPointsOfRoute($merchant->id, $request->bus_route_id);

        return ['start_point' => $route->StartPoint->Name, 'stop_points' => $arr_stop_points, 'end_point' => $route->EndPoint->Name];
    }
}
