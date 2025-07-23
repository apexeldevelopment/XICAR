<?php

namespace App\Http\Controllers\Merchant;

use App\Models\InfoSetting;
use DB;
use App\Models\Merchant;
use App\Models\ServiceType;
use App;
use Auth;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use App\Models\Segment;


class ServiceTypeController extends Controller
{
    use ImageTrait, MerchantTrait;
    public function index()
    {
        $checkPermission =  check_permission(1, "view_service_types");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $merchant_segement_group_icons = Merchant::select("handyman_segement_group_icon")->where("id", $merchant_id)->first();
        $appConfig = $merchant->ApplicationConfiguration;
        $handyman_segment_creation = $merchant->Configuration->handyman_segment_creation ?? 2;
        $segment_services = $this->getMerchantSegmentServices($merchant_id, 'service_type_screen',[1,3,4]);
        $handyman_segment_services = $this->getMerchantSegmentServices($merchant_id, 'service_type_screen',2);
        $arr_segment_group =   $this->segmentGroup($merchant_id,$return_type = "drop_down","");
        $merchant_segment_group = isset($arr_segment_group['arr_group']) ?  array_keys($arr_segment_group['arr_group']) : [];
//            p($segment_services);
        $info_setting = InfoSetting::where('slug', 'SERVICE_TYPE_SETTINGS')->first();
        return view('merchant.service_types.index', compact('segment_services', 'info_setting','appConfig','merchant_segment_group', 'handyman_segment_services', 'merchant_segement_group_icons', 'merchant', 'handyman_segment_creation'));
    }

    public function add(Request $request, $segment_id, $id = NULL)
    {
        $checkPermission =  check_permission(1, "view_service_types");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $service = '';
        if (empty($id) && !in_array($segment_id, array_keys(get_merchant_segment(false)))) {
            return redirect()->route('merchant.serviceType.index')->withErrors(trans('admin.trying_to_add_invalid_segment'));
        }
        $merchant = get_merchant_id(false);
        $appConfig = $merchant->ApplicationConfiguration;

        if (!empty($id)) {
            $locale = App::getLocale();
            $service = $merchant->ServiceType->where('id', $id)->first();
            $service_locale = '';
            $merchant_id = $merchant->id;
            $service_description = "";

            if (!empty($service->ServiceName($merchant_id))) {
                $service_locale = $service->ServiceName($merchant_id);
            }
            if (!empty($service->ServiceDescription($merchant_id))) {
                $service_description = $service->ServiceDescription($merchant_id);
            }
            $service->service_locale_name = !empty($service_locale) ? $service_locale : $service->serviceName;
            $service->service_locale_description = $service_description;
        }
        // dd($service);
        $arr_segment = get_merchant_segment();
        $segment = $arr_segment[$segment_id];
        return view('merchant.service_types.form', compact('service', 'segment_id', 'segment','appConfig'));
    }
    
    

    public function saveSegemtGroupIcon(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        $request->validate([
            'handyman_grouping_icon' => 'required'
        ]);
        DB::beginTransaction();
        try {
            if ($request->hasFile("handyman_grouping_icon")) {
                $handyman_grouping_icon = $this->uploadImage('handyman_grouping_icon', 'segment_group_icons', $merchant_id);
                $merchant = Merchant::find($merchant_id);
                $merchant->handyman_segement_group_icon = $handyman_grouping_icon;
                $merchant->handyman_segement_group_name = $request->handyman_grouping_name;
                $merchant->save();
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            return redirect()->route('merchant.serviceType.index')->withErrors($message);
            DB::rollback();
        }
        DB::commit();
        return redirect()->route('merchant.serviceType.index')->withSuccess(trans("$string_file.saved_successfully"));
    }



    public function update(Request $request, $id = NULL)
    {
        $checkPermission =  check_permission(1, "edit_service_types");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $request->validate([
            'service' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $merchant = get_merchant_id(false);
            $merchant_id = $merchant->id;

            $string_file = $this->getStringFile(NULL, $merchant);

            if (empty($id)) {
                $exist_service =   DB::table('service_types')->select('id', 'owner','serviceName')->where([
                    ['serviceName', '=', $request->service], ['segment_id', '=', $request->segment_id]
                ])->where(function ($q) use ($id) {
                    if (!empty($id)) {
                        $q->where('id', '!=', $id);
                    }
                })
                    ->first();

                if (!empty($exist_service->id)) {
                    $merchantService = DB::table('merchant_service_type')->where('service_type_id',$exist_service->id)
                    ->where('merchant_id',$merchant_id)
                    ->where('segment_id',$request->segment_id)->first();
                    
                    if(empty($merchantService)){
                        DB::table('merchant_service_type')->insert([
                            ['service_type_id' => $exist_service->id, 'merchant_id' => $merchant_id, 'segment_id' => $request->segment_id, 'sequence' => $request->sequence]
                        ]);
                    }else{
                        return redirect()->route('merchant.serviceType.index')->withErrors(trans("$string_file.service_type_duplicate"));
                    }
                }else{
                    $service = new ServiceType;
                    $service->serviceName = $request->service;
                    $service->segment_id = $request->segment_id;
                    $service->serviceStatus = 1;
                    $service->owner_id = $merchant_id;
                    $service->owner = 2; // service added by merchant
                    $service->save();
                    // insert row in pivot table
                    DB::table('merchant_service_type')->insert([
                        ['service_type_id' => $service->id, 'merchant_id' => $merchant_id, 'segment_id' => $request->segment_id, 'sequence' => $request->sequence]
                    ]);
                }
                //                $merchant->ServiceType()->attach($service->id,['segment_id'=>$request->segment_id,'sequence'=>$request->sequence]);
                //                p($service);
            }
            App\Models\ServiceTranslation::updateOrCreate([
                'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'service_type_id' => $id
            ], [
                'name' => $request->service,
                'description' => $request->description,
            ]);
            $insert_param['sequence'] = $request->sequence;
            if ($request->hasFile('icon')) {
                $insert_param['service_icon'] = $this->uploadImage('icon', 'service');
            }
            DB::table('merchant_service_type')->where([['merchant_id', '=', $merchant_id], ['service_type_id', '=', $id], ['segment_id', '=', $request->segment_id]])->update($insert_param);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            //  p($message);
            return redirect()->route('merchant.serviceType.index')->withErrors($message);
            // Rollback Transaction
            DB::rollback();
        }
        DB::commit();

        return redirect()->route('merchant.serviceType.index')->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function editSegment($id)
    {
        $locale = App::getLocale();
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
//        if ($merchant->demo == 1) {
//            return redirect()->back()->withErrors(trans("$string_file.demo_user_cant_edited"));
//        }
        $segment = $merchant->Segment->where('id', $id)->first();
        //        p($segment);
        $segment->segment_locale_name = $segment->slag;
        $segment_locale = $segment->Name($merchant->id);
        if (!empty($segment_locale)) {
            $segment->segment_locale_name = $segment_locale;
        }
        return view('merchant.service_types.segment', compact('segment'));
    }

    public function updateSegment(Request $request, $id)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
//        if ($merchant->demo == 1) {
//            return redirect()->back()->withErrors(trans("$string_file.demo_user_cant_edited"));
//        }
        $merchant_id = $merchant->id;
        $request->validate([
            'segment' => 'required'
        ]);
        DB::beginTransaction();
        try {
            App\Models\SegmentTranslation::updateOrCreate([
                'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'segment_id' => $id
            ], [
                'name' => $request->segment,
            ]);
            $arr_update = ['sequence' => $request->sequence, 'is_coming_soon' => (isset($request->is_coming_soon) && !empty($request->is_coming_soon)) ? $request->is_coming_soon : 2];
            if ($request->hasFile('icon')) {
                $additional_req = ['compress' => true, 'custom_key' => 'segment'];
                $icon = $this->uploadImage('icon', 'segment', $merchant_id, 'single', $additional_req);
                $arr_update['segment_icon'] = $icon;
            }
            if($request->dynamic_url){
                $arr_update['dynamic_url'] = $request->dynamic_url;
            }
            DB::table('merchant_segment')->where('merchant_id', $merchant_id)
                ->where('segment_id', $id)->update($arr_update);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        DB::commit();

        return redirect()->route('merchant.serviceType.index')->withSuccess(trans("$string_file.saved_successfully"));
    }
    
    public function serviceImageDelete($id){
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $service = $merchant->ServiceType->where('id', $id)->first();
        if(isset($service['pivot']['service_icon'])){
            $insert_param['service_icon'] = NULL;
            DB::table('merchant_service_type')->where([['merchant_id', '=', $merchant_id], ['service_type_id', '=', $id]])->update($insert_param);
            return redirect()->route('merchant.serviceType.index')->withSuccess(trans("$string_file.image_removed_successfully"));
        }
    }

    public function changeStatus(Request $request, $id = null , $status = null)
    {
        $request->merge(['status'=>$status,'id'=>$id]);
        $validator = Validator::make($request->all(),[
            'id'=>'required|exists:service_types,id',
            'status' => 'integer|required|between:0,1'
        ],[
            'status.between' => trans('admin.invalid_status'),
            'id.exists' => trans('admin.cashback_addederror'),
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $errors[0];
        }
        try{
            $merchant_id = get_merchant_id();
            $string_file = $this->getStringFile($merchant_id);
            $service = ServiceType::find($request->id);
            $status = $request->status == 1 ? 1 : 0;
            $insert_param = array("is_recommended" => $status);
            DB::table('merchant_service_type')->where([['merchant_id', '=', $merchant_id], ['service_type_id', '=', $id], ['segment_id', '=', $service->segment_id]])->update($insert_param);
            return trans("$string_file.success");
        }catch (\Exception $exception){
            return $exception->getMessage();
        }
    }

    public function addSegment(){
        return view("merchant.segment.create");
    }

    public function saveSegment(Request $request){
//        p($request->all());
        $request->validate([
            // 'segment' => 'required|max:255|unique:segments,name',
            'icon' => 'required',
        ]);
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        if ($merchant->demo == 1) {
            return redirect()->back()->withErrors(trans("$string_file.demo_user_cant_edited"));
        }
        DB::beginTransaction();
        try{
            $cleansegment = preg_replace('/[^A-Za-z0-9]/', ' ', strtoupper($request->segment));
            $cleanseg = preg_replace('/\s+/', ' ', $cleansegment);
            $cleansegment = str_replace(' ','_',strtoupper($cleanseg));
            
            $segmentData = Segment::where('name',$cleanseg)->exists();
            if($segmentData){
                $segment = Segment::where('name',$cleanseg)->first();
                $segment_icon = "";
                if ($request->hasFile('icon')) {
                    $segment_icon = $this->uploadImage('icon', 'segment', $merchant->id);
                }
            }else{
                $segment_icon = "";
                if ($request->hasFile('icon')) {
                    $segment_icon = $this->uploadImage('icon', 'segment', $merchant->id);
                }
                
                $segment = new App\Models\Segment();
                $segment->icon = $segment_icon;
                $segment->name = $cleanseg;
                $segment->description = $cleanseg;
                $segment->slag = $cleansegment;
                $segment->sub_group_for_app = 5;
                $segment->sub_group_for_admin = 3;
                $segment->segment_group_id = 2;
                $segment->owner = 2;
                $segment->owner_id = $merchant->id;
                $segment->save();

                App\Models\SegmentTranslation::updateOrCreate([
                    'merchant_id' => $merchant->id, 'locale' => App::getLocale(), 'segment_id' => $segment->id
                ], [
                    'name' => $cleanseg,
                ]);
            }

            $arr_create = [
                'sequence' => $request->sequence,
                'is_coming_soon' => $request->is_coming_soon,
                'merchant_id' => $merchant->id,
                'segment_id' => $segment->id,
                'segment_icon' => $segment_icon,
                'dynamic_url'=> $request->dynamic_url
            ];

            DB::table('merchant_segment')->insert($arr_create);

            $service_type_name = "Normal ".$cleanseg;

            $service = ServiceType::where('serviceName',$service_type_name)->first();
            if($service){
                
            }else{
                $service = new ServiceType;
                $service->serviceName = $service_type_name;
                $service->segment_id = $segment->id;
                $service->serviceStatus = 1;
                $service->owner_id = $merchant->id;
                $service->owner = 2; // service added by merchant
                $service->save();
            }
            // insert row in pivot table
            DB::table('merchant_service_type')->insert([
                ['service_type_id' => $service->id, 'merchant_id' => $merchant->id, 'segment_id' => $segment->id, 'sequence' => $request->sequence]
            ]);

            App\Models\ServiceTranslation::updateOrCreate([
                'merchant_id' => $merchant->id, 'locale' => App::getLocale(), 'service_type_id' => $service->id
            ], [
                'name' => $service_type_name,
                'description' => $service_type_name,
            ]);
            $insert_param['sequence'] = $request->sequence;
            if ($request->hasFile('icon')) {
                $insert_param['service_icon'] = $this->uploadImage('icon', 'service');
            }
            DB::table('merchant_service_type')->where([['merchant_id', '=', $merchant->id], ['service_type_id', '=', $service->id], ['segment_id', '=', $segment->id]])->update($insert_param);

            DB::commit();
            return redirect()->back()->withSuccess(trans("$string_file.segment")." ".trans("$string_file.added_successfully"));
        }catch (\Exception $exception){
            DB::rollback();
            p($exception->getMessage());
        }
    }
}
