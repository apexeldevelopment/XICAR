<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 17/3/23
 * Time: 10:55 AM
 */

namespace App\Http\Controllers\Merchant;
use App\Models\ApplicationConfiguration;
use App\Models\InfoSetting;
use App\Models\LangName;
use App\Models\Event;
use App\Traits\AreaTrait;
use App\Traits\ImageTrait;
use App\Traits\ProductTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use validator;
use View;
use App\Traits\MerchantTrait;

class EventController extends Controller
{
    use ImageTrait, ProductTrait, MerchantTrait, AreaTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','EVENT')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index(Request  $request)
    {
        $event_name = $request->event_name;
        $merchant_id = get_merchant_id();
        $all_food_grocery_clone = \App\Models\Segment::whereIn("sub_group_for_app",[1,2])->get()->pluck("slag")->toArray();
        $all_segments = array_merge(['TAXI','DELIVERY'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $permission_segments = get_permission_segments(1,true);
        $query = Event::with(['Segment' => function($query) use($permission_segments){
            $query->whereIn('slag',$permission_segments);
        }])->whereHas("Segment",function($query) use($permission_segments){
            $query->whereIn('slag',$permission_segments);
        })
            ->where('merchant_id',$merchant_id)->where('delete','=',NULL);
        if(!empty($event_name))
        {
            $query->with(['LangEventSingle'=>function($q) use($event_name,$merchant_id){
                $q->where('name',"LIKE","%$event_name%")->where('merchant_id',$merchant_id);
            }])->whereHas('LangEventSingle',function($q) use($event_name,$merchant_id){
                $q->where('name',"LIKE","%$event_name%")->where('merchant_id',$merchant_id);
            });
        }
        $all_events = $query->paginate(15);
        $request->request->add(['merchant_id' => $merchant_id, 'segment_slug' => $permission_segments]);
        $event['data'] =$all_events;
        $event['event_name'] =$event_name;
        $event['search_route'] = route('merchant.events');
        $event['arr_search'] = $request->all();
        return view('merchant.event.index')->with($event);
    }

    public function add(Request $request, $id = NULL)
    {
        $all_food_grocery_clone = \App\Models\Segment::whereIn("sub_group_for_app",[1,2])->get()->pluck("slag")->toArray();
        $all_segments = array_merge(['TAXI','DELIVERY'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $event = NULL;
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $pre_title = trans("$string_file.add");
        $save_url = route('merchant.event.save');
        $arr_selected_segment = [];
        $is_demo = false;
        if (!empty($id)) {
            $event = Event::Find($id);
            $arr_selected_segment = array_pluck($event->Segment,'id');
            if (empty($event->id)) {
                return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
            }
            $pre_title = trans("$string_file.edit");
            $save_url = route('merchant.event.save', $id);
            $is_demo = $merchant->demo == 1 ? true : false;
        }
        $title = $pre_title.' '.trans("$string_file.event");
        $arr_businesss = get_merchant_segment($with_taxi = true, null,$segment_group_id = 1);
        // If there is no category view then remove taxi and delivery segment
        $app_config = ApplicationConfiguration::where("merchant_id",$merchant_id)->first();
        if(isset($app_config->home_screen_view) && $app_config->home_screen_view != 1){
            if(isset($arr_businesss[1])){
                unset($arr_businesss[1]);
            }
            if(isset($arr_businesss[2])){
                unset($arr_businesss[2]);
            }
        }
        $areas = $this->getMerchantCountryArea($this->getAreaList(false, false)->get());
        $arr_businesss = get_permission_segments(1, false, $arr_businesss);
        $segment_data['arr_segment'] = $arr_businesss;
        $segment_data['selected'] = $arr_selected_segment;
        $segment_html = View::make('segment')->with($segment_data)->render();
        $data['data'] = [
            'title' => $title,
            'save_url' => $save_url,
            'event' => $event,
            'arr_areas' => $areas,
            'segment_html'=>$segment_html,
            'arr_status'=>get_active_status("web",$string_file),
        ];
        $data['is_demo'] = $is_demo;
        return view('merchant.event.form')->with($data);
    }

    /*Save or Update*/
    public function save(Request $request, $id = NULL)
    {
        $width = Config('custom.image_size.category.width');
        $height = Config('custom.image_size.category.height');
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $locale = \App::getLocale();
        $validator = Validator::make($request->all(), [
            'country_area_id' => 'required|integer',
            'event_name' => 'required',
            'event_image' => 'sometimes|required|file|mimes:jpeg,png,jpg,gif,svg|dimensions:min_width='.$width.',min_height='.$height,
            'sequence' => 'required|integer',
            'event_link' => 'required',
            'status' => 'required',
            'segment'=>'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }

        $event_name = DB::table('lang_names')->where(function ($query) use ($merchant_id,$locale,$id,$request) {
            return $query->where([['lang_names.merchant_id', '=', $merchant_id], ['lang_names.locale', '=', $locale], ['lang_names.name', '=', $request->event_name]])
                ->where('lang_names.dependable_id','!=',$id);
        })->join("events","lang_names.dependable_id","=","events.id")
            ->where('events.id','!=',$id)
            ->where('events.merchant_id','=',$merchant_id)
            ->where('events.delete',NULL)->first();

        if (!empty($event_name->id)) {

            return redirect()->back()->withErrors(trans("$string_file.event_name_already_exist"));
        }
        // Begin Transaction
        DB::beginTransaction();

        try {
            if (!empty($id)) {
                $event = Event::Find($id);
            } else {
                $event = new Event();
            }

            $merchant_id = get_merchant_id();
            $event->merchant_id = $merchant_id;
            $event->country_area_id = $request->country_area_id;
            if (!empty($request->hasFile('event_image'))) {
                $additional_req = ['compress'=>true,'custom_key'=>'category'];
                $event->event_image = $this->uploadImage('event_image', 'event',$merchant_id,'single',$additional_req);
            }
            $event->sequence = $request->sequence;
            $event->event_link = $request->event_link;
            $event->status = $request->status;
            $event->save();

            // sync segment
            $event->Segment()->sync($request->segment);

            // sync language of category
            $category_locale =  $event->LangEventSingle;
            if(!empty($category_locale->id))
            {
                $category_locale->name = $request->event_name;
                $category_locale->save();
            }
            else
            {
                $language_data = new LangName([
                    'merchant_id' => $event->merchant_id,
                    'locale' => $locale,
                    'name' => $request->event_name]);

                $event->LangEvent()->save($language_data);
//
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            DB::rollback();
            return redirect()->route('merchant.events')->withErrors($message);
            // Rollback Transaction
        }
        // Commit Transaction
        DB::commit();
        return redirect()->route('merchant.events')->withSuccess(trans("$string_file.event_saved_successfully"));
    }
    public function destroy(Request $request)
    {
        $id = $request->id;
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        if($merchant->demo == 1)
        {
            echo trans("$string_file.demo_warning_message");
        }
        if(is_array($id)){
            $delete = Event::whereIn('id',$id)->update(['delete' => 1]);
        }else{
            $delete = Event::where('id',$id)->update(['delete' => 1]);
        }
    }

    public function updateStatus($id, $status)
    {
        $event = Event::FindorFail($id);
        $string_file = $this->getStringFile(NULL, $event->Merchant);
        if (!empty($event->id)):
            $event->status = $status;
            $event->save();
            return redirect()->route("merchant.events")->withSuccess(trans("$string_file.saved_successfully"));
        else:
            return redirect()->route("merchant.events")->withSuccess(trans("$string_file.some_thing_went_wrong"));
        endif;
    }
}
