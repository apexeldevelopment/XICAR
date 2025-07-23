<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\Merchant as helperMerchant;
use App\Models\InfoSetting;
use App\Models\Merchant;
use App\Models\Segment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\LanguageMerchantMembershipPlan;
use App\Models\MerchantMembershipPlan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Traits\MerchantTrait;
use Auth;
use DB;
use App;
use Illuminate\Support\Facades\Validator;

class MembershipPlanController extends Controller
{
    use MerchantTrait;
    // public function __construct()
    // {
    //     $info_setting = App\Models\InfoSetting::where('slug', 'Membership_Plan')->first();
    //     view()->share('info_setting', $info_setting);
    // }

    public function index()
    {
        $merchant = get_merchant_id(false);
        $plan = $merchant->MerchantMembershipPlan;
        return view('merchant.membership_plan.index', compact('plan','merchant'));
    }

    public function create(){
        return view('merchant.membership_plan.create');
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'plan_title' => 'required',
            'plan_name' => 'required',
            'price' => 'required',
            'plan_type' => 'required',
            'period' => 'required',
            'description' => 'required',
            'max_amount_valid'=> 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }

        DB::beginTransaction();
        try {
            $merchant = get_merchant_id(false);
            $merchant_id = $merchant->id;
            $string_file = $this->getStringFile($merchant_id);

            $plan = new MerchantMembershipPlan();
            $plan->merchant_id = $merchant_id;
            $plan->plan_type = $request->plan_type;
            $plan->price = $request->price;
            $plan->period = $request->period;
            $plan->number_of_order = $request->number_order;
            $plan->max_amount_valid = $request->max_amount_valid;
            $plan->save();

            $this->SaveLanguageMembershipPlan($merchant_id, $plan->id, $request->plan_title, $request->plan_name,$request->description);

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->route('merchant.membershipPlan.index')->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function SaveLanguageMembershipPlan($merchant_id,$plan_id,$planTitle,$planName,$description){
        LanguageMerchantMembershipPlan::updateOrCreate([
            'locale' => App::getLocale(), 'merchant_membership_plan_id' => $plan_id
        ], [
            'plan_title' => $planTitle,
            'description' => $description,
            'plan_name'=> $planName
        ]);
    }

    public function edit($id){
        $plan = MerchantMembershipPlan::find($id);

        return view('merchant.membership_plan.edit', compact('plan'));
    }

    public function update(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'plan_title' => 'required',
            'plan_name' => 'required',
            'price' => 'required',
            'plan_type' => 'required',
            'period' => 'required',
            'description' => 'required',
            'number_order' => $request->plan_type == 2 ? 'required' : '',
            'max_amount_valid'=> 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }

        DB::beginTransaction();
        try {
            $merchant = get_merchant_id(false);
            $merchant_id = $merchant->id;
            $string_file = $this->getStringFile($merchant_id);

            $plan = MerchantMembershipPlan::find($id);
            $plan->merchant_id = $merchant_id;
            $plan->plan_type = $request->plan_type;
            $plan->price = $request->price;
            $plan->period = $request->period;
            $plan->number_of_order = $request->number_order;
            $plan->max_amount_valid = $request->max_amount_valid;
            $plan->save();

            $this->SaveLanguageMembershipPlan($merchant_id, $plan->id, $request->plan_title, $request->plan_name,$request->description);

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();
        return view('merchant.membership_plan.edit', compact('plan'));
    }

    public function delete($id){
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        $plan = MerchantMembershipPlan::find($id);
        $plan->delete();

        return redirect()->route('merchant.membershipPlan.index')->withSuccess(trans("$string_file.deleted"));

    }

}