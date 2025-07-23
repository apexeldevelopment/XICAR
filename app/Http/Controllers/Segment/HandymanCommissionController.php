<?php

namespace App\Http\Controllers\Segment;

use App\Http\Controllers\Helper\AjaxController;
use App\Models\HandymanCommission;
use App\Models\InfoSetting;
use App\Traits\AreaTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\HandymanCommissionDetail;
use App\Models\CountryArea;
use View;
class HandymanCommissionController extends Controller
{
    use AreaTrait,MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','HANDYMAN_DRIVER_SERVICE_PRICE_CARD')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index(Request $request)
    {
        $checkPermission = check_permission(1, 'price_card_HANDYMAN');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $permission_area_ids = [];
        if(Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != ""){
            $permission_area_ids = explode(",",Auth::user()->role_areas);
        }
        $segment_list = get_merchant_segment(false,$merchant_id,2);
        $arr_commission = HandymanCommission::with('CountryArea','Segment')->where([['merchant_id', '=', $merchant_id]])
            ->where(function ($q) use ($request){
                if(!empty($request->country_area_id))
                {
                    $q->where('country_area_id',$request->country_area_id);
                }
                if(!empty($request->segment_id))
                {
                    $q->where('segment_id',$request->segment_id);
                }
            })
            ->whereHas('CountryArea',function($q) use($permission_area_ids){
                if(!empty($permission_area_ids)){
                    $q->whereIn("id",$permission_area_ids);
                }
            })
            ->paginate(25);
        $search_route =  route('merchant.segment.commission');
        $arr_search = $request->all();
        $country_area = $this->getMerchantCountryArea($merchant->CountryArea);
        return view('merchant.segment-commission.index', compact('arr_commission','segment_list','search_route','arr_search','country_area'));
    }

    public function add(Request $request, $id = null)
    {
        $checkPermission = check_permission(1, 'price_card_HANDYMAN');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $data = [];
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $commission = '';
        $segment_group_id = 2;
        $area_id = NULL;
        $is_demo = false;
        $arr_services = "";
        if(!empty($id))
        {
//            $commission = HandymanCommission::where([['status',true]])->findorfail($id);
            $commission = HandymanCommission::findorfail($id);
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");
            $area_id = $commission->country_area_id;
            $is_demo = $merchant->demo == 1 && $commission->country_area_id == 3 ? true : false;

            if($merchant->BookingConfiguration->handyman_commission_type == 2){
                $request->merge(['area_id'=>$area_id,'segment_id'=>$commission->segment_id,'calling_from'=>"controller","handyman_commission_id"=>$commission->id,'merchant_id'=>$merchant->id]);
                $arr_services = $this->getSegmentCommissionServices($request);
                if(empty($arr_services)){
                    $string_file = $this->getStringFile($merchant->id);
                    $service_not_found = trans("$string_file.either_service_or_segment_removed_from_service_area_please_check");
                    return redirect()->back()->withErrors($service_not_found);
                }
            }
        }
        else
        {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title.' '.trans("$string_file.commission");
        $areas = $this->getMerchantCountryArea($this->getAreaList(false,false)->get());
        $ajax = new AjaxController;
        $request->merge(['area_id'=>$area_id,'segment_group_id'=>$segment_group_id]);
        $arr_segment = $ajax->getCountryAreaSegment($request,'dropdown');
        $data = [
            'commission'=>$commission,
            'title'=>$title,
            'submit_button'=>$submit_button,
            'arr_areas'=>$areas,
            'arr_segment'=>$arr_segment,
            'arr_status'=>get_active_status("web",$string_file),
            'is_demo'=>$is_demo,
            'arr_services'=>$arr_services
        ];
        return view('merchant.segment-commission.form',compact('merchant','data'));
    }
    public function save(Request $request, $id = NULL)
    {
        $checkPermission = check_permission(1, 'price_card_HANDYMAN');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = $merchant->id;
        $segment_id = $request->segment_id;
        $country_area_id = $request->country_area_id;
        $validator = Validator::make($request->all(), [
            'country_area_id' => 'required|integer',
            'segment_id' => 'required|unique:handyman_commissions,segment_id,'.$id.',id,merchant_id,'.$merchant_id.',country_area_id,'.$country_area_id,
            'commission_method' => 'required',
//            'commission' => 'required',
            'status' => 'required',
            'tax' => 'nullable',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            if(!empty($id))
            {
                $commission = HandymanCommission::Find($id);
            }
            else{
                $commission = new HandymanCommission;
            }
            $commission->merchant_id = $merchant_id;
            $commission->segment_id = $segment_id;
            $commission->country_area_id = $country_area_id;
            $commission->commission_method = $request->commission_method;
            $commission->commission = $request->commission;
            $commission->tax = $request->tax ? $request->tax : 0;
            $commission->status = $request->status;
            $commission->commission_pricing_type = ($merchant->BookingConfiguration->handyman_commission_type == 1) ? 1 : 2;
            $commission->save();

            //@ayush (For config based commission charges on services )
            if($merchant->BookingConfiguration->handyman_commission_type == 2){

                $arr_service_amount = $request->service_amount;
                $arr_detail = $request->detail_id;
                foreach ($arr_service_amount as $service_type_id => $fixed_amount)
                {
                    if(!empty($id))
                    {
                        $detail_id = isset($arr_detail[$service_type_id]) ? $arr_detail[$service_type_id] : NULL;
                        $commission_detail = HandymanCommissionDetail::Find($detail_id);
                        if(empty($commission_detail)){
                            $commission_detail = new HandymanCommissionDetail;
                        }
                    }
                    else
                    {
                        $commission_detail = new HandymanCommissionDetail;
                    }
                    $commission_detail->handyman_commission_id  = $commission->id;
                    $commission_detail->service_type_id  = $service_type_id;
                    $commission_detail->amount  = $fixed_amount;
                    $commission_detail->save();
                }
            }

        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return redirect()->route('merchant.segment.commission')->withErrors($message);
        }
        DB::commit();
        return redirect()->route('merchant.segment.commission')->withSuccess(trans("$string_file.saved_successfully"));
    }


    //@ayush (Config Based HandymanCommission )
    public function getSegmentCommissionServices(Request  $request)
    {
        $area_id = $request->area_id;
        $merchant_id = !empty($request->merchant_id) ? $request->merchant_id : get_merchant_id();
        $segment_id = $request->segment_id;
        $calling_from = $request->calling_from;
        $handyman_commission_id = $request->handyman_commission_id;
        $areas = CountryArea::select('id','merchant_id')
            ->with(['ServiceTypes'=>function($q) use($area_id,$segment_id,$handyman_commission_id,$merchant_id){
                $q->whereHas('Merchant',function($qqq) use($merchant_id){
                    $qqq->where('merchant_id',$merchant_id);
                    $qqq->orderBy('sequence','ASC');
                });
                $q->where('country_area_service_type.segment_id',$segment_id);
                $q->with(['HandymanCommissionDetail'=>function($q) use($area_id,$handyman_commission_id){
                    $q->where('handyman_commission_id',$handyman_commission_id);
                }]);
            }])
            ->whereHas('ServiceTypes',function($q) use($area_id,$segment_id,$handyman_commission_id,$merchant_id){
                $q->whereHas('Merchant',function($qqq) use($merchant_id){
                    $qqq->where('merchant_id',$merchant_id);
                    $qqq->orderBy('sequence','ASC');
                });
                $q->where('country_area_service_type.segment_id',$segment_id);
                if(!empty($segment_price_card_id))
                {
                    $q->whereHas('HandymanCommissionDetail',function($q) use($area_id,$handyman_commission_id){
                        $q->where('handyman_commission_id',$handyman_commission_id);
                    });
                }
            })
            ->where([['id', '=', $area_id]])->first();
        if(!empty($areas)){
            if(isset($areas->ServiceTypes) && !empty($areas->ServiceTypes)){
                $arr_data['arr_services'] = $areas->ServiceTypes;
            }else{
                $arr_data['arr_services'] = [];
            }
            $arr_data['merchant_id'] = $areas->merchant_id;
            $service_view = View::make('merchant.segment-commission.services-amount')->with($arr_data)->render();
            if($calling_from == "controller")
            {
                return $service_view;
            }
            // calling from ajax
            echo $service_view;
        }else{
            echo "";
        }
    }
}
