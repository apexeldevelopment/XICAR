<?php

namespace App\Http\Controllers\Segment;

use App\Models\InfoSetting;
use App\Models\Segment;
use App\Models\ServiceTimeSlotDetail;
use DateTime;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ServiceTimeSlot;
use App\Traits\AreaTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Traits\MerchantTrait;

class ServiceTimeSlotController extends Controller
{
    use AreaTrait,MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','SERVICE_TIME_SLOT')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index(Request $request)
    {
        $all_grocery_clone = \App\Models\Segment::where("sub_group_for_app",2)->orWhere('segment_group_id',4)->get()->pluck("slag")->toArray();
        $all_grocery_clone = array_merge(['HANDYMAN'],$all_grocery_clone);
        $checkPermission = check_permission(1, $all_grocery_clone,true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = $merchant->id;
        $time_format = $merchant->Configuration->time_format;
        $segment_list = get_merchant_segment(false,$merchant_id);
        $permission_segments = get_permission_segments(1,true);
        $permission_area_ids = [];
        if(Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != ""){
            $permission_area_ids = explode(",",Auth::user()->role_areas);
        }
        $arr_service_time_slot = ServiceTimeSlot::with('CountryArea','Segment')->whereHas('Segment',function($query) use($permission_segments){
            $query->whereIn('slag',$permission_segments);
        })
            ->join('merchant_segment','service_time_slots.segment_id','=','merchant_segment.segment_id')
//         ->whereHas('Merchant',function($q) use ($merchant_id){
//                 $q->whereHas('Segment',function($q) use ($merchant_id){
//                 $q->where('merchant_id', '=', $merchant_id);
//             });
//            })
            ->where('service_time_slots.merchant_id', '=', $merchant_id)
            ->where('merchant_segment.merchant_id', '=', $merchant_id)
            ->where(function ($q) use ($request, $permission_area_ids){
                if(!empty($request->country_area_id))
                {
                    $q->where('country_area_id',$request->country_area_id);
                }
                if(!empty($request->segment_id))
                {
                    $q->where('service_time_slots.segment_id',$request->segment_id);
                }
                if(!empty($permission_area_ids))
                {
                    $q->whereIn('country_area_id',$permission_area_ids);
                }
            })
            ->latest()->paginate(25);
//        $arr_day= \Config::get('custom.days');
        $arr_day = get_days($string_file);
        $search_route =  route('segment.service-time-slot');
        $arr_search = $request->all();
        $country_area = $this->getMerchantCountryArea($merchant->CountryArea);
        return view('merchant.service-time-slot.index', compact('arr_service_time_slot','arr_day','segment_list','search_route','arr_search','country_area','time_format'));
    }

    public function add(Request $request, $id = null)
    {
        $all_grocery_clone = \App\Models\Segment::where("sub_group_for_app",2)->orWhere('segment_group_id',4)->get()->pluck("slag")->toArray();
        $all_grocery_clone = array_merge(['HANDYMAN'],$all_grocery_clone);
        $checkPermission = check_permission(1, $all_grocery_clone,true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $data = [];
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $service_time_slot = '';
        $arr_services = [];
        $segment_group_id = NULL;
        $is_demo = false;
        $submit_button = trans("$string_file.save");
        $areas = $this->getMerchantCountryArea($this->getAreaList(false,false)->get());
        $arr_segment = get_merchant_segment(false,$merchant->id,$segment_group_id);
        $arr_segment = get_permission_segments(1,false,$arr_segment);
        $data = [
            'service_time_slot'=>$service_time_slot,
            'submit_button'=>$submit_button,
            'arr_areas'=>$areas,
            'arr_segment'=>$arr_segment,
            'arr_status'=>get_active_status("web",$string_file),
            'arr_day'=>\Config::get('custom.days'),
            'time_format'=>$merchant->Configuration->time_format,
        ];
        return view('merchant.service-time-slot.form',compact('merchant','data','is_demo'));
    }

    public function save(Request $request)
    {
        $all_grocery_clone = \App\Models\Segment::where("sub_group_for_app",2)->orWhere('segment_group_id',4)->get()->pluck("slag")->toArray();
        $all_grocery_clone = array_merge(['HANDYMAN'],$all_grocery_clone);
        $checkPermission = check_permission(1, $all_grocery_clone,true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = $merchant->id;
//        $segment_id = $request->segment_id;
        $country_area_id = $request->country_area_id;
        $validator = Validator::make($request->all(), [
            'country_area_id' => 'required',
            'segment_id' => 'required',
            'status' => 'required',
            'max_slot' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'time_format' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $start_time = $request->start_time;
            $end_time = $request->end_time;
            if($request->time_format == 1) // 12 hour
            {
                $start_time = date("H:i", strtotime($start_time)); // 24 hour format
                $end_time = date("H:i", strtotime($end_time)); // 24 hour format
            }
            $not_saved_segment_data = [];
            $saved_count = 0;
            foreach($request->segment_id as $segment_id){
                $check_slot_exist = ServiceTimeSlot::where([['segment_id','=',$segment_id],['country_area_id','=',$request->country_area_id]])->get();
                $segment = Segment::find($segment_id);
                if($check_slot_exist->count() > 0) {
                    array_push($not_saved_segment_data, $segment->name);
//                    return redirect()->back()->withErrors(trans("$string_file.time_slot_already_exist"));
                }else{
                    $saved_count += 1;
                    $arr_day = get_days($string_file);
                    foreach ($arr_day as $day_number =>$day)
                    {
                        $service_time_slot = new ServiceTimeSlot;
                        $service_time_slot->merchant_id = $merchant_id;
                        $service_time_slot->segment_id = $segment_id;
                        $service_time_slot->country_area_id = $country_area_id;
                        $service_time_slot->day = $day_number;
                        $service_time_slot->status = $request->status;
                        $service_time_slot->start_time = $start_time;
                        $service_time_slot->end_time = $end_time;
                        $service_time_slot->max_slot = $request->max_slot;
                        $service_time_slot->save();

                        /**
                         * (if auto_fill  == "on" )
                         * @ayush (auto fill configurations for segment except bus booking)
                         */
                        if(empty($request->auto_fill) || $segment->segment_group_id == 4){
                            $service_time_slot_detail = new ServiceTimeSlotDetail;
                            $service_time_slot_detail->service_time_slot_id = $service_time_slot->id;
                            $service_time_slot_detail->to_time = $end_time;
                            if($segment->segment_group_id == 4){ // In case of bus booking segment
                                $service_time_slot_detail->from_time = $end_time;
                                $service_time_slot_detail->slot_time_text = $request->end_time;
                            }else{
                                $service_time_slot_detail->from_time = $start_time;
                                $service_time_slot_detail->slot_time_text = $request->start_time.'-'.$request->end_time;
                            }
                            $service_time_slot_detail->save();
                        }
                        else if($request->auto_fill == "on" && $segment->segment_group_id != 4){
                                $x = (int)$request->max_slot;
                                $start = new DateTime($start_time);
                                $end = new DateTime($end_time);
                                $totalMinutes = $start->diff($end)->h * 60 + $start->diff($end)->i;
                                $slotDuration = (int)($totalMinutes / $x);

                                for ($i=0;  $i<$x; $i++) {
                                    $service_time_slot_detail = new ServiceTimeSlotDetail;
                                    $service_time_slot_detail->service_time_slot_id = $service_time_slot->id;

                                    $slotStart = clone $start;
                                    $slotStart->modify("+".($i * $slotDuration)." minutes");
                                    $service_time_slot_detail->from_time =  $slotStart;

                                    $slotEnd = clone $slotStart;
                                    $slotEnd->modify("+".$slotDuration." minutes");
                                    $service_time_slot_detail->to_time = $slotEnd;

                                    if($request->time_format == 1){
                                        $slot_text= $slotStart->format("h:ia") . '-' .  $slotEnd->format("h:ia");
                                    }
                                    else{
                                        $slot_text = $slotStart->format("H:i") . '-' . $slotEnd->format("H:i");
                                    }
                                    $service_time_slot_detail->slot_time_text = $slot_text;
                                    $service_time_slot_detail->save();
                                }
                        }
                    }
                }
            }
            DB::commit();
            if($saved_count > 0){
                if(!empty($not_saved_segment_data)){
                    $message = trans("$string_file.partial_added_successfully")." ".trans("$string_file.not_saved_for")." ".implode(",", $not_saved_segment_data);
                    return redirect()->route('segment.service-time-slot')->withSuccess($message);
                }else{
                    return redirect()->route('segment.service-time-slot')->withSuccess(trans("$string_file.added_successfully"));
                }
            }else{
                return redirect()->route('segment.service-time-slot')->withErrors(trans("$string_file.time_slot_already_exist"));
            }
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->input())->withErrors($e->getMessage());
        }
    }

    public function edit($id)
    {
        $all_grocery_clone = \App\Models\Segment::where("sub_group_for_app",2)->get()->pluck("slag")->toArray();
        $all_grocery_clone = array_merge(['HANDYMAN'],$all_grocery_clone);
        $checkPermission = check_permission(1, $all_grocery_clone,true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $data = [];
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $service_time_slot = '';
        $arr_services = [];
        $segment_group_id = NULL;

        $service_time_slot = ServiceTimeSlot::findorfail($id);
        $submit_button = trans("$string_file.update");
        $is_demo = $merchant->demo == 1 && $service_time_slot->country_area_id ? true : false;

        $areas = $this->getMerchantCountryArea($this->getAreaList(false,false)->get());
        $arr_segment = get_merchant_segment(false,$merchant->id,$segment_group_id);
        $arr_segment = get_permission_segments(1,false,$arr_segment);
        $data = [
            'service_time_slot'=>$service_time_slot,
            'submit_button'=>$submit_button,
            'arr_areas'=>$areas,
            'arr_segment'=>$arr_segment,
            'arr_status'=>get_active_status("web",$string_file),
            'arr_day'=>\Config::get('custom.days'),
            'time_format'=>$merchant->Configuration->time_format,
        ];
        return view('merchant.service-time-slot.edit',compact('merchant','data','is_demo'));
    }

    public function update(Request $request, $id)
    {
        $all_grocery_clone = \App\Models\Segment::where("sub_group_for_app",2)->get()->pluck("slag")->toArray();
        $all_grocery_clone = array_merge(['HANDYMAN'],$all_grocery_clone);
        $checkPermission = check_permission(1, $all_grocery_clone,true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = $merchant->id;
        $segment_id = $request->segment_id;
        $country_area_id = $request->country_area_id;
        $validator = Validator::make($request->all(), [
            'country_area_id' => 'required',
            'segment_id' => 'required',
            'status' => 'required',
            'max_slot' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'time_format' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $start_time = $request->start_time;
            $end_time = $request->end_time;
            if($request->time_format == 1) // 12 hour
            {
                $start_time = date("H:i", strtotime($start_time)); // 24 hour format
                $end_time = date("H:i", strtotime($end_time)); // 24 hour format
            }

            $service_time_slot = ServiceTimeSlot::Find($id);
            $service_time_slot->status = $request->status;
            $service_time_slot->start_time = $start_time;
            $service_time_slot->end_time = $end_time;
            $service_time_slot->max_slot = $request->max_slot;
            $service_time_slot->save();

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->input())->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->route('segment.service-time-slot')->withSuccess(trans("$string_file.added_successfully"));
    }

    public function getSlotDetail(Request $request, $id = null)
    {
        $data = [];
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $service_time_slot = '';
        $is_demo = false;
        if(!empty($id))
        {
             $service_time_slot = ServiceTimeSlot::with('ServiceTimeSlotDetail')->findorfail($id);
            $submit_button = trans("$string_file.update");

            $is_demo = $merchant->demo == 1 && $service_time_slot->country_are_id == 3 ? true :false;
        }
        else
        {
            $submit_button = trans("$string_file.save");
        }
        $data = [
            'service_time_slot'=>$service_time_slot,
            'submit_button'=>$submit_button,
            'arr_status'=>get_active_status("web",$string_file),
            'arr_day'=>\Config::get('custom.days'),
            'time_format'=>$merchant->Configuration->time_format,
        ];
        return view('merchant.service-time-slot.time-slot-detail',compact('merchant','data','is_demo'));
    }

    public function saveSlotDetail(Request $request)
    {
        $service_time_slot = ServiceTimeSlot::findorfail($request->service_time_slot_id);
        $request->merge(["segment_group_id" => $service_time_slot->Segment->segment_group_id]);
        $validator = Validator::make($request->all(), [
            'service_time_slot_id' => 'required',
            'start_time' => 'required',
            'end_time' => [Rule::requiredIf(function () use($request){
                return $request->input('segment_group_id') != 4;
            })],
            'time_format' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $merchant = get_merchant_id(false);
            $string_file = $this->getStringFile(NULL,$merchant);
            $arr_start_time = $request->start_time;
            $arr_end_time = $request->end_time;
            $arr_slot_time_text = $request->slot_time_text;
            $arr_slot_detail_id = $request->slot_detail_id;
            $service_time_slot_id = $request->service_time_slot_id;
            foreach($arr_start_time as $key=>$start_time)
            {
                if(!empty($start_time)) {
                    $start = $start_time ?? NULL;
                    $end = $arr_end_time[$key] ?? NULL;
                    $id = $arr_slot_detail_id[$key] ?? NULL;
                    if(!empty($id)) {
                        $service_time_slot_detail = ServiceTimeSlotDetail::Find($id);
                    } else {
                        $service_time_slot_detail = new ServiceTimeSlotDetail;
                    }

                    if($request->time_format == 1) {
                        $start = isset($start) ? date("H:i", strtotime($start)) : '00:00'; // 24 hour format
                        $end = isset($end) ? date("H:i", strtotime($end)) : '23:59'; // 24 hour format
                    }
                    if($start >= $end && $service_time_slot->Segment->segment_group_id != 4) {
                        return redirect()->back()->withErrors(trans("$string_file.start_time_must_not_be_greater_than_end_time"));
                    }
                    $service_time_slot_detail->service_time_slot_id = $service_time_slot_id;
                    if($service_time_slot->Segment->segment_group_id == 4){
                        $service_time_slot_detail->slot_time_text = $start;
                        $service_time_slot_detail->from_time = $start;
                        $service_time_slot_detail->to_time = $start;
                    }else{
                        $service_time_slot_detail->slot_time_text = $start.'-'.$end;
                        $service_time_slot_detail->from_time = $start;
                        $service_time_slot_detail->to_time = $end;
                    }
                    $service_time_slot_detail->save();
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->route('service-time-slot.detail',$service_time_slot_id)->withSuccess(trans("$string_file.added_successfully"));
    }
}
