<?php

namespace App\Http\Controllers\Merchant;

use App\Models\InfoSetting;
use Auth;
use App;
use App\Models\CancelReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
class CancelReasonController extends Controller
{
    use MerchantTrait;
    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'CANCEL_REASON')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $merchant_id = get_merchant_id();
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY','TAXI','HANDYMAN','CARPOOLING'],$all_food_grocery_clone);
        foreach($all_segments as &$segment){
            $segment = "cancel_reason_".$segment;
        }
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $permission_segments = get_permission_segments(1,true);
        $cancelreasons = CancelReason::whereHas('Segment',function($query) use($permission_segments){
            $query->whereIn('slag',$permission_segments);
        })->where([['merchant_id', '=', $merchant_id]])->paginate(15);
        $merchant_segments = get_permission_segments(1,false,get_merchant_segment());
        return view('merchant.cancelreason.index', compact('cancelreasons','merchant_segments'));
    }

    public function create()
    {
        $merchant_id = get_merchant_id();
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY','TAXI','HANDYMAN','CARPOOLING'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_segments = get_permission_segments(1,false,get_merchant_segment());
        return view('merchant.cancelreason.add', compact('merchant_segments'));
    }

    public function store(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY','TAXI','HANDYMAN','CARPOOLING'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $request->validate([
            'reason_for' => 'required|integer',
            'reason' => 'required',
        ]);
        foreach($request->segment_id as $segment_id){
            $cancel_reason = CancelReason::create([
                'merchant_id' => $merchant_id,
                'segment_id' => $segment_id,
                'reason_type' => $request->reason_for,
                'reason_type_for' => $request->reason_type_for,
                'reason_status' => 1,
            ]);
            $this->SaveLanguageCancel($merchant_id, $cancel_reason->id, $request->reason);
        }
        return redirect()->route("cancelreason.index")->withSuccess(trans("$string_file.added_successfully"));
    }

    public function SaveLanguageCancel($merchant_id, $cancel_reason_id, $reason)
    {
        App\Models\LanguageCancelReason::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'cancel_reason_id' => $cancel_reason_id
        ], [
            'reason' => $reason,
        ]);
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
            ]);
        if ($validator->fails()) {
            return redirect()->back();
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $cancelreason = CancelReason::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $cancelreason->reason_status = $status;
        $cancelreason->save();
        return redirect()->route('cancelreason.index')->with('success', "Status Updated");
    }

    public function edit($id)
    {
        $merchant_id = get_merchant_id();
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY','TAXI','HANDYMAN','CARPOOLING'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        $cancelreason = CancelReason::where([['merchant_id', '=', $merchant_id]])->findorFail($id);
        $other_cancel_reason_related_segments = CancelReason::with('Segment')
            ->where('merchant_id', $merchant_id)
            ->where('reason_type', $cancelreason->reason_type)
            ->where('segment_id', '!=', $cancelreason->segment_id)
            ->get()
            ->pluck('Segment')
            ->flatten()
            ->toArray();
        $merchant_segments = get_merchant_segment();
        return view('merchant.cancelreason.edit', compact('cancelreason','merchant_segments', 'other_cancel_reason_related_segments'));
    }

    public function update(Request $request, $id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY','TAXI','HANDYMAN','CARPOOLING'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $request->validate([
            'reason' => 'required',
        ]);
        $cancelreasons = CancelReason::where([['merchant_id', '=', $merchant_id]])->findorFail($id);
        $cancelreasons->segment_id = $request->segment_id;
        $cancelreasons->save();
        $this->SaveLanguageCancel($merchant_id, $cancelreasons->id, $request->reason);
        $other_cancel_reason_related_segments = isset($request->other_segments) ? array_keys($request->other_segments) : [];
        foreach($other_cancel_reason_related_segments as $segment_id){
            $other_cancelreasons = CancelReason::where([['merchant_id', '=', $merchant_id], ["reason_type", "=", $cancelreasons->reason_type], ["segment_id", "=", $segment_id]])->first();
            $this->SaveLanguageCancel($merchant_id, $other_cancelreasons->id, $request->reason);
        }
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function Search(Request $request)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $query = CancelReason::where([['merchant_id', '=', $merchant_id]]);
        if ($request->reason) {
            $keyword = $request->reason;
            $query->WhereHas('LanguageSingle', function ($q) use ($keyword) {
                $q->where('reason', 'LIKE', "%$keyword%");
            });
        }
        if ($request->reason_for) {
            $query->where('reason_type', $request->reason_for);
        }
        $cancelreasons = $query->paginate(25);
        return view('merchant.cancelreason.index', compact('cancelreasons'));
    }

    public function destroy($id)
    {
        //
    }
}
