<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Helper\AjaxController;
use App\Http\Requests\PromoCodeRequest;
use App\Models\Booking;
use App\Models\Configuration;
use App\Models\Corporate;
use App\Models\InfoSetting;
use App\Models\PromoCode;
use App\Models\PromoCodeTranslation;
use App\Traits\AreaTrait;
use App\Traits\PromoTrait;
use App\Traits\MerchantTrait;
use App\Models\VehicleType;
use Auth;
use App;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use DB;

class PromoCodeController extends Controller
{
    use PromoTrait, AreaTrait, MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'PROMOCODE_MANAGEMENT')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $merchant = get_merchant_id(false);
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant->id);
        $all_segments = array_merge(['TAXI', 'DELIVERY', 'HANDYMAN'], $all_food_grocery_clone);
        $segment = array_pluck($merchant->Segment, 'slag');
        foreach($all_segments as &$segment){
            $segment = "promo_code_".$segment;
        }
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $promocodes = $this->getAllPromoCode();
        $ajax = new AjaxController;
        $segment_list = get_merchant_segment(true, null);
        $vehicle_list = VehicleType::where([['merchant_id', '=', $merchant->id]])->get();
        return view('merchant.promocode.index', compact('promocodes', 'segment_list','vehicle_list','segment'));
    }

    public function add(Request $request, $id = NULL)
    {
        $merchant_id = get_merchant_id();
        $merchant = get_merchant_id(false);
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $merchant_segment = array_pluck($merchant->Segment, 'slag');
        $all_segments = array_merge(['TAXI', 'DELIVERY', 'HANDYMAN'], $all_food_grocery_clone);
        $vehicle_list = VehicleType::where([['merchant_id', '=', $merchant_id]])->get();
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }


        $area = $this->getAreaList(false);
        $areas = $area->where('status', 1)->get();
        $promocode = NULL;
        $pricecards = [];
        $area_id = NULL;
        if (!empty($id)) {
            $promocode = PromoCode::whereHas('CountryArea', function ($q) {
                $q->where('status', 1);
            })->findOrFail($id);
            $pricecards = $promocode->CountryArea->PriceCard;
            $area_id = $promocode->country_area_id;
        }
        $corporates = Corporate::where([['merchant_id', '=', $merchant_id]])->get();
        $config = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
        //        $segment_list = add_blank_option(get_merchant_segment(true,null,1),'Select Segment');
        $ajax = new AjaxController;
        $handyman_apply_promocode = $this->merchantHandymanPromocode($merchant_id);
        if ($handyman_apply_promocode) {
            $request->merge(['area_id' => $area_id, 'merchant_id' => $merchant_id]);
        } else {
            $request->merge(['area_id' => $area_id, 'segment_group_id' => 1, 'merchant_id' => $merchant_id]);
        }
        $segment_list = $ajax->getCountryAreaSegment($request, 'dropdown');
        return view('merchant.promocode.create', compact('areas', 'corporates', 'config', 'promocode', 'pricecards', 'segment_list', 'handyman_apply_promocode','vehicle_list','merchant_segment'));
    }


    public function save(PromoCodeRequest $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['TAXI', 'DELIVERY', 'HANDYMAN'], $all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $string_file = $this->getStringFile(NULL, $merchant);
        DB::beginTransaction();
        try {

            if (!empty($id)) {
                $promocode = PromoCode::findOrFail($id);
            } else {
                // p('in');
                $promocode = new PromoCode;
                //            'corporate_id' => $request->corporate_id,
                //            'country_area_id' => $request->area,
                $promocode->merchant_id = $merchant_id;

                $promocode->country_area_id = $request->area;
                $promocode->segment_id = $request->segment_id;
                if (!empty($request->business_segment_id)) {
                    $promocode->business_segment_id = $request->business_segment_id;
                    $saved_promocode = PromoCode::where([['business_segment_id', '=', $request->business_segment_id], ['status', '=', 1], ['promo_code_validity','=',$request->promo_code_validity],['applicable_for','=',$request->applicable_for],['deleted', '=', NULL]])
                        ->where(function ($q) {
                            $q->where([['start_date', '=', NULL], ['end_date', '=', NULL]])
                                ->orWhere([['end_date', '>=', date('Y-m-d')]]);
                        })->first();

                    if (!empty($saved_promocode->id)) {
                            return redirect()->back()->withErrors(trans("$string_file.store_discount"));
                    }
                }
            }

            $is_default_promo_code = $request->is_default_promo_code;
            if($is_default_promo_code == 1){
                $exist = PromoCode::where([['merchant_id', '=', $merchant_id], ['deleted', '=', NULL], ['is_default_promo_code', '=', 1],  ['is_default_promo_code', '=', 1], ['segment_id', $request->segment_id]]);
                if(!empty($id)){
                    $exist = $exist->where("id" ,"!=" ,$id);
                }
                $exist = $exist->first();
                if(!empty($exist)){
                    return redirect()->back()->withErrors(trans("$string_file.default")." ".trans("$string_file.promo_code")." ".trans("$string_file.already")." ".trans("$string_file.exist")." ".trans("$string_file.for")." ".trans("$string_file.selected")." ".trans("$string_file.segment"));
                }
            }

            $promocode->corporate_id = $request->corporate_id;
            $promocode->promoCode = $request->promocode;
            $promocode->promo_code_description = $request->promo_code_description;
            $promocode->promo_code_value = $request->promo_code_value;
            $promocode->promo_code_value_type = $request->promo_code_value_type;
            $promocode->promo_code_validity = $request->promo_code_validity;
            $promocode->promo_code_limit = $request->promo_code_limit;
            $promocode->start_date = $request->start_date;
            $promocode->end_date = $request->end_date;
            $promocode->applicable_for = $request->applicable_for;
            $promocode->promo_code_limit_per_user = $request->promo_code_limit_per_user;
            $promocode->promo_percentage_maximum_discount = $request->promo_percentage_maximum_discount;
            $promocode->order_minimum_amount = $request->order_minimum_amount;
            $promocode->promo_code_vehicle_type = isset($request->promo_code_vehicle_type) ? json_encode($request->promo_code_vehicle_type) : $request->promo_code_vehicle_type;
            $promocode->is_default_promo_code = ($is_default_promo_code == 1)? 1: 2;
            if($request->promo_code_validity == 3){
                $condition = [
                    "promo_condition" => $request->promo_condition,
                    "no_of_days"=> $request->no_of_days,
                    "no_of_ride"=> $request->number_of_rides,
                    "ride_time"=> $request->ride_time
                ];
                $promocode->additional_conditions =  json_encode($condition);
            }
            $promocode->save();
            // p($promocode);

            $this->SaveLanguage($promocode->id, $request->promo_code_name);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();

        return redirect()->back()->withSuccess(trans("$string_file.promo_code_saved_successfully"));
    }

    public function SaveLanguage($id, $name)
    {
        PromoCodeTranslation::updateOrCreate([
            'promo_code_id' => $id, 'locale' => App::getLocale()
        ], [
            'promo_code_name' => $name,
        ]);
    }

    //    public function Search(Request $request)
    //    {
    //        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
    //        $query = PromoCode::where([['merchant_id', '=', $merchant_id]]);
    //        if ($request->code) {
    //            $query->where('promoCode', 'LIKE', "%$request->code%");
    //        }
    //        $promocodes = $query->paginate(25);
    //        return view('merchant.promocode.index', compact('promocodes'));
    //    }
    //
    //    public function edit($id)
    //    {
    //        $merchant_id = get_merchant_id();
    //        $promocode = PromoCode::findOrFail($id);
    //        $pricecards = $promocode->CountryArea->PriceCard;
    //        $corporates = Corporate::where([['merchant_id', '=', $merchant_id]])->get();
    //        $config = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
    //        return view('merchant.promocode.edit', compact('promocode', 'config', 'corporates', 'pricecards'));
    //    }
    //
    //    public function update(Request $request, $id)
    //    {
    //        //print_r($request->all()); die();
    //        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
    //        $request->validate([
    //            'price_card_ids' => 'required',
    //            'promo_code_name' => 'required',
    //            'promocode' => ['required',
    //                Rule::unique('promo_codes', 'promoCode')->where(function ($query) use ($merchant_id) {
    //                    return $query->where([['merchant_id', '=', $merchant_id], ['deleted', '=', 0]]);
    //                })->ignore($id)],
    //            'promo_code_description' => 'required',
    //            'promo_code_value' => "required",
    //            'promo_code_value_type' => "required|integer",
    //            'promo_code_validity' => "required|integer",
    //            'promo_code_limit' => "required|integer",
    //            'promo_code_limit_per_user' => "required|integer|lt:promo_code_limit",
    //            'applicable_for' => 'required',
    //            'promo_percentage_maximum_discount' => 'required_if:promo_code_value_type,2',
    //            'order_minimum_amount' => 'required',
    //        ]);
    //        //echo $request->promo_percentage_maximum_discount; die();
    //        $promocode = PromoCode::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
    //        $promocode->corporate_id = $request->corporate_id;
    //        $promocode->promoCode = $request->promocode;
    //        $promocode->promo_code_description = $request->promo_code_description;
    //        $promocode->promo_code_value = $request->promo_code_value;
    //        $promocode->promo_code_value_type = $request->promo_code_value_type;
    //        $promocode->promo_code_validity = $request->promo_code_validity;
    //        $promocode->promo_code_limit = $request->promo_code_limit;
    //        $promocode->start_date = $request->start_date;
    //        $promocode->end_date = $request->end_date;
    //        $promocode->applicable_for = $request->applicable_for;
    //        $promocode->promo_code_limit_per_user = $request->promo_code_limit_per_user;
    //        $promocode->promo_percentage_maximum_discount = $request->promo_percentage_maximum_discount;
    //        $promocode->order_minimum_amount = $request->order_minimum_amount;
    //
    //        $promocode->save();
    //        $promocode->PriceCard()->sync($request->price_card_ids);
    //        $this->SaveLanguage($promocode->id, $request->promo_code_name);
    //        return redirect()->back()->with('promoadded', 'Promo Code Updated Successfully');
    //    }

    public function destroy($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['TAXI', 'DELIVERY', 'HANDYMAN'], $all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $promocode = PromoCode::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $checkRide = Booking::where([['promo_code', '=', $id]])->get();
        if (!empty($checkRide->toArray())) {
            $promocode->deleted = 1;
            $promocode->save();
        } else {
            $promocode->delete();
        }
        return redirect()->route('promocode.index')->with('promoDeleted', 'Promocode Deleted Successfully');
        //        return redirect()->back()->with('success',trans('admin.referral_delete'));
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
            return redirect()->back()->withErrors('There is an error changing the status');
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $promocode = PromoCode::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $promocode->promo_code_status = $status;
        $promocode->save();
        return redirect()->route('promocode.index')->with('success', 'Promocode Status Updated');
        //        return redirect()->route('promocode.index');
    }
}
