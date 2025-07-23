<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Country;
use App\Models\Driver;
use App\Models\InfoSetting;

use App\Models\CancelPolicy;
use App\Models\CancelPolicyTranslation;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\MerchantTrait;
use App\Traits\AreaTrait;
use App\Http\Controllers\Helper\AjaxController;
use App;



class CancelPolicyController extends Controller
{
    use AreaTrait, MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'cancel_policy')->first();
        view()->share('info_setting', $info_setting);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $checkPermission = check_permission(1, 'view_cancel_policy');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = get_merchant_id();
        // $this->checkExpireCancelPolicy($merchant_id);
        $cancel_policies = CancelPolicy::where([['merchant_id', '=', $merchant_id], ['status', '!=', 4]])->orderBy('id', 'DESC')->paginate(10);
        return view('merchant.cancel-policy.index', compact('cancel_policies'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request,$id = NULL)
    {
        $checkPermission = check_permission(1, 'create_cancel_policy');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = get_merchant_id();
        $arr_areas = $this->getMerchantCountryArea($this->getAreaList(false, false, [])->get());;
        // $countries = Country::where([['merchant_id', '=', $merchant_id]])->get()->pluck("CountryName", "id")->toArray();
        $cancel_policy = [];

        $arr_segment = [];

        if (!empty($id)) {
            $cancel_policy = CancelPolicy::findOrFail($id);
//            p($cancel_policy);
            $ajax = new AjaxController;

            $request->merge(['area_id'=>$cancel_policy->country_area_id,'merchant_id'=>$cancel_policy->merchant_id,'segment_group_id'=>1]);
//           p($request->all());
            $arr_segment = $ajax->getCountryAreaSegment($request, 'dropdown');
        }

//        if (!empty($id)) {
//            return view('merchant.cancel-policy.edit', compact('cancel_policy', 'cancel_policy_segments'));
//        } else {
            return view('merchant.cancel-policy.create', compact('arr_areas', 'cancel_policy','arr_segment'));
//        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id = NULL)
    {

        $validator = Validator::make($request->all(),[
            'application' => 'required|integer',
            'country_area_id' => 'required|integer|exists:country_areas,id',
            'segment_id' => "required",
            'charge_type' => 'required|integer',
            'cancellation_charges' => 'required',
            'service_type' => 'required',
            'free_time' => 'required',
            'title' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }

        DB::beginTransaction();
        try {
            $merchant = get_merchant_id(false);
            $merchant_id = $merchant->id;
            $string_file = $this->getStringFile(NULL, $merchant);
            if (!empty($id)) {
                $cancel_policy = CancelPolicy::find($id);

            } else {
                $cancel_policy = CancelPolicy::where([
                    ["merchant_id", "=", $merchant_id],

                    ["segment_id", "=", $request->segment_id],
                    ["service_type", "=", $request->service_type],
                    ["country_area_id", "=", $request->country_area_id],
                    [
                        "application", "=", $request->application,
                        ],
                ])->whereIn("status", [1, 2])->first();

                if (!empty($cancel_policy)) {
                    return redirect()->back()->withInput()->withErrors(trans("$string_file.cancel_policy_already_exist_for_this"));
                }
                else{
                    $cancel_policy = new CancelPolicy();
                }
            }


//p($cancel_policy);

                    $cancel_policy->merchant_id = $merchant_id;
                    $cancel_policy->segment_id = $request->segment_id;
                    $cancel_policy->country_area_id = $request->country_area_id;
                    $cancel_policy->application = $request->application;
                    $cancel_policy->charge_type = $request->charge_type;
                    $cancel_policy->service_type = $request->service_type;
                    $cancel_policy->cancellation_charges = $request->cancellation_charges;
                    $cancel_policy->free_time = $request->free_time;
//                    $cancel_policyral->status = $request->status ? $request->status : 1;

                    $cancel_policy->save();

            $this->SaveLanguage($cancel_policy->id, $request, $merchant_id);
            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }

        return redirect()->route('cancel.policies')->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function SaveLanguage($id, $request, $merchant_id)
    {
        CancelPolicyTranslation::updateOrCreate([
            'cancel_policy_id' => $id, 'locale' => App::getLocale()
        ], [
            'merchant_id' => $merchant_id,
            'title' => $request->title,
            'description' => $request->description,

        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'cancel_policy_id' => 'required'
        ]);
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $cancel_policyral = CancelPolicy::where([['merchant_id', '=', $merchant_id]])->findOrFail($request->cancel_policy_id);

        $cancel_policyral->delete();
        return redirect()->back()->with('success', trans("$string_file.deleted_successfully"));
    }

    public function ChangeStatus($id, $status)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
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
        $cancel_policy = CancelPolicy::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $cancel_policy->status = $status;
//        p($cancel_policy);
        $cancel_policy->save();
        return redirect()->back()->with('success', trans("$string_file.status") . " " . trans("$string_file.changed") . " " . trans("$string_file.successfully"));
    }


}
