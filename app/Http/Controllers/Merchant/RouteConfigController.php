<?php
//
//namespace App\Http\Controllers\Merchant;
//
//use App\Http\Controllers\Controller;
//use Illuminate\Http\Request;
//use App\Models\InfoSetting;
//use Auth;
//use App;
//use App\Models\RouteConfig;
//use App\Models\LanguageRouteConfig;
//use App\Traits\AreaTrait;
//use Illuminate\Support\Facades\Validator;
//use Illuminate\Validation\Rule;
//use DB;
//use App\Traits\MerchantTrait;
//use App\Traits\BusStopTrait;
//use App\Traits\BusRouteTrait;
//use App\Traits\RouteConfigTrait;
//
//
//class RouteConfigController extends Controller
//{
//
//    use BusStopTrait, BusRouteTrait, RouteConfigTrait, MerchantTrait, AreaTrait;
//    public function __construct()
//    {
//        $info_setting = InfoSetting::where('slug', 'bus_routes')->first();
//        view()->share('info_setting', $info_setting);
//    }
//
//    public function index()
//    {
//        $checkPermission =  check_permission(1, 'view_documents');
//        if ($checkPermission['isRedirect']) {
//            return  $checkPermission['redirectBack'];
//        }
//
//        $merchant = get_merchant_id(false);
//        $merchant_id = $merchant->id;
//        $string_file = $this->getStringFile(NULL, $merchant);
//        $route_configs = RouteConfig::where('merchant_id', $merchant_id)->latest()->paginate(25);
//        $status = get_status(true, $string_file);
//        return view('merchant.bus-booking.route-config.index', compact('route_configs', 'status'));
//    }
//
//    /**
//     * Add Edit form of Route Config
//     */
//    public function add(Request $request, $id = null)
//    {
//        $merchant = get_merchant_id(false);
//
//        $is_demo = $merchant->demo == 1 ? true : false;
//        $string_file = $this->getStringFile(NULL, $merchant);
//        // $selected_stop_points = [];
//        $data = [];
//        $route_id = null;
//        if (!empty($id)) {
//            $data = RouteConfig::findorfail($id);
//            $pre_title = trans("$string_file.edit");
//            $submit_button = trans("$string_file.update");
//            $route_id = $data->bus_route_id;
//            // $selected_stop_points = $data->stop_points_time ? json_decode($data->stop_points_time, true) : [];
//        } else {
//            $pre_title = trans("$string_file.add");
//            $submit_button = trans("$string_file.save");
//        }
//        $title = $pre_title . ' ' . trans("$string_file.route_config");
//
//        $return['route_config'] = [
//            'data' => $data,
//            // 'arr_routes' => $this->getRoutes($merchant->id),
//            'arr_stop_points' => $route_id ? $this->getOnlyStopPointsOfRoute($merchant->id, $route_id) : [],
//            'arr_area' => $this->getMerchantCountryArea($this->getAreaList(false, true, [], null, null, false, false)->get()),
//            'submit_url' => route('bus_booking.save_route_config', $id),
//            'title' => $title,
//            // 'selected_stop_points' => $selected_stop_points,
//            'arr_status' => add_blank_option(get_status(true, $string_file), trans("$string_file.select")),
//            'submit_button' => $submit_button,
//        ];
//        $return['is_demo'] = $is_demo;
//
//        return view('merchant.bus-booking.route-config.form')->with($return);
//    }
//    /***
//     * Save/update function of duration
//     */
//    public function save(Request $request, $id = NULL)
//    {
//        // p($request->all());
//        $merchant = get_merchant_id(false);
//        $merchant_id = $merchant->id;
//        $string_file = $this->getStringFile(NULL, $merchant);
//        $validator = Validator::make($request->all(), [
//            'title' => 'required',
//            'country_area_id' => 'required_if:route_config_id,==,null',
//            // 'vehicle_type_id' => 'required_if:route_config_id,==,null',
//            'bus_route_id' => 'required_if:route_config_id,==,null',
//            'status' => 'required|between:1,2',
//            'stop_time.*' => 'required',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return redirect()->back()->withInput($request->input())->withErrors($errors);
//        }
//        DB::beginTransaction();
//        try {
//            if (!empty($id)) {
//                $route_config = RouteConfig::Find($id);
//            } else {
//                $route_config = new RouteConfig;
//                $route_config->merchant_id = $merchant_id;
//                // $route->vehicle_type_id = $request->vehicle_type_id;
//                $route_config->bus_route_id = $request->bus_route_id;
//                $route_config->country_area_id = $request->country_area_id;
//            }
//            // $arr_stop_time = [];
//            // foreach ($request->stop_time as $key => $time) {
//            //     $arr_stop_time[] = ['bus_stop_id' => $key, 'time' => $time];
//            // }
//
//            $route_config->status = $request->status;
//
//            // $route->stop_points_time = json_encode($arr_stop_time); // bus stop id
//            $route_config->save();
//
//
//            // storing stop point times
//            $route_config->StopPointsTime()->detach();
//            foreach ($request->stop_time as $bus_stop_id => $time) {
//                // p($time);
//                $route_config->StopPointsTime()->attach($route_config->id, ['bus_stop_id' => $bus_stop_id, 'time' => $time]);
//            }
//
//            // $stop_points = $request->stop_points; // arr of bus stop id
//            // $route->RouteConfigStopPoints()->sync();
//            $this->SaveLanguageRouteConfig($merchant_id, $route_config->id, $request->title);
//        } catch (\Exception $e) {
//            $message = $e->getMessage();
//            DB::rollback();
//            return redirect()->back()->withErrors($message);
//        }
//        DB::commit();
//        return redirect()->route('bus_booking.route_config')->withSuccess(trans("$string_file.saved_successfully"));
//    }
//    public function ChangeStatus($id, $status)
//    {
//        $validator = Validator::make(
//            [
//                'id' => $id,
//                'status' => $status,
//            ],
//            [
//                'id' => ['required'],
//                'status' => ['required', 'integer', 'between:1,2'],
//            ]
//        );
//        if ($validator->fails()) {
//            return redirect()->back();
//        }
//        $merchant = get_merchant_id(false);
//        $string_file = $this->getStringFile(NULL, $merchant);
//        if ($merchant->demo == 1) {
//            return redirect()->back()->withErrors(trans("$string_file.demo_warning_message"));
//        }
//        $merchant_id = $merchant->id;
//        $RouteConfig = RouteConfig::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
//        $RouteConfig->status = $status;
//        $RouteConfig->save();
//        return redirect()->back()->withSuccess(trans("$string_file.status_updated"));
//    }
//
//    public function SaveLanguageRouteConfig($merchant_id, $id, $title)
//    {
//        LanguageRouteConfig::updateOrCreate([
//            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'route_config_id' => $id
//        ], [
//            'title' => $title,
//        ]);
//    }
//
//    public function getRouteConfigByArea(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'country_area_id' => ['required']
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            p($errors);
//            // return redirect()->back()->withInput($request->input())->withErrors($errors);
//        }
//        $merchant = get_merchant_id(false);
//
//        $string_file = $this->getStringFile();
//        $arr_routes =  $this->getRouteConfig($merchant->id, $request->country_area_id);
//
//
//        if (empty($arr_routes)) {
//            echo "<option value=''>" . trans("$string_file.select") . "</option>";
//        } else {
//            echo "<option value=''>" . trans("$string_file.route_config") . "</option>";
//            foreach ($arr_routes as $value) {
//                $title =  isset($value->LanguageRouteConfig[0]) ? $value->LanguageRouteConfig[0]->title : "";
//                echo "<option id='" . $value->id . "' value='" . $value->id . " 'data-route-id='" . $value->bus_route_id . "'>" . $title . "</option>";
//            }
//        }
//        // return $arr_routes;
//    }
//
//    public function getRouteConfigById(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'route_config_id' => ['required']
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            echo $errors[0];
//        }
//        $merchant = get_merchant_id(false);
//
//        $string_file = $this->getStringFile();
//        $route_config = RouteConfig::find($request->route_config_id);
//        // $arr_routes =  $this->getRouteConfig($merchant->id, $request->country_area_id);
//
//        $arr_stop_points = $route_config->StopPointsTime->map(function ($item) {
//
//            return [
//                'bus_stop_id'=>$item->id,
//                'stop_name'=>$item->LanguageSingle->name,
//                'time'=>$item->pivot->time,
//            ];
//        });
//
//        $route = $route_config->BusRoute;
//        return ['start_point'=>$route->StartPoint->LanguageAny->name,'stop_points'=>$arr_stop_points,'end_point'=>$route->EndPoint->LanguageAny->name];
//
//        // return $arr_routes;
//    }
//}
