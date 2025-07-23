<?php

namespace App\Http\Controllers\Merchant;

use Carbon\Carbon;
use App\Models\Driver;
use App\Models\InfoSetting;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use App\Exports\CustomExport;
use App\Traits\MerchantTrait;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Models\LangSubscriptionPack;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\RenewableSubscription;
use App\Traits\SubscriptionPackageTrait;
use Illuminate\Support\Facades\Validator;
use App\Models\RenewableSubscriptionValue;
use App\Http\Controllers\Helper\AjaxController;

class SubscriptionController extends Controller
{
    use MerchantTrait, SubscriptionPackageTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'SUBSCRIPTION_PACKAGE')->first();
        view()->share('info_setting', $info_setting);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $packages = $this->getAllPackages(true);
        return view('merchant.subscriptionpack.index', compact('packages'));
    }

    public function Change_Status($id = null, $status = null)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $change = $this->getAllPackages(false)->FindorFail($id);
        $change->status = $status;
        $change->save();

        return redirect()->route('subscription.index')->withSuccess(trans("$string_file.status_updated"));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $delete = $this->getAllPackages(false)->FindorFail($id);
        $delete->status = 0;
        $delete->admin_delete = 1;
        $delete->save();
        request()->session()->flash('error', trans('admin.subspack_deleted'));
        echo trans("$string_file.deleted");
    }

    /**
     * Add Edit form of Subecription package
     */
    public function add(Request $request, $id = null)
    {
        $merchant  = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        $data = null;
        $arr_duration = [];
        $arr_area = [];
        $selected_services = [];
        $all_services = $this->getAllMerchantServices(false);
        $all_durations = $this->getPackagesDuration(false)->get();
        foreach ($all_durations  as $durations) {
            $arr_duration[$durations->id] = $durations->NameAccMerchant;
        }
        $all_areas = $this->getAllMerchantAreas(false)->get();
        foreach ($all_areas  as $areas) {
            $arr_area[$areas->id] = $areas->CountryAreaName;
        }
        $package_type = \Config::get('custom.package_type');

        $arr_segments = [];
        if (!empty($id)) {
            $data = SubscriptionPackage::findorfail($id);
            if ($data->status != 1) {
                request()->session()->flash('error', trans('admin.deactivated_package'));
                return redirect()->route('subscription.index');
            }
            // $selected_services = $data->ServiceType()->pluck('service_type_id')->all();
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.save");
            $ajax = new AjaxController;
            $request->area_id = $data->country_area_id;
            $arr_segments = $ajax->getCountryAreaSegment($request, 'dropdown');
        } else {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.update");
        }
        $title = $pre_title . ' ' . trans("$string_file.package");
        $return = [
            'package_edit' => $data,
            'submit_url' => url('merchant/admin/subscription/save/' . $id),
            'title' => $title,
            'package_type' => $package_type,
            'submit_button' => $submit_button,
            'arr_area' => $arr_area,
            'all_durations' => add_blank_option($arr_duration, trans("$string_file.select")),
            'all_services' => $all_services,
            'selected_services' => $selected_services,
            'arr_segments' => $arr_segments,
            'subscription_creation_for' => $merchant->ApplicationConfiguration->subscription_creation_for,
        ];
        return view('merchant.subscriptionpack.form')->with($return);
    }
    /***
     * Save/update function of duration
     */
    public function save(Request $request, $id = NULL)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $request->validate([
            'name' => [
                'required',
                'max:255',
            ],
            'description' => 'required',
            'price' => 'required_if:package_type,==,2',
            'max_trip' => 'required',
            //            'services' => 'required|exists:service_types,id',
            'country_area_id' => 'required|exists:country_areas,id',
            'package_duration' => [
                'required',
                Rule::exists('package_durations', 'id')
            ],
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|',
            'package_type' => 'required',
            'segment_id' => 'required',
        ]);
        $request->id = $id;
        $return = $this->SavePackage($request);
        if ($return) :
            return redirect()->route('subscription.index')->withSuccess(trans("$string_file.saved_successfully"));
        else :
            return redirect()->route('subscription.index')->withErrors(trans("$string_file.some_thing_went_wrong"));
        endif;
    }

    public function getRenewableSubscriptionList()
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $subscription_list = RenewableSubscription::where("merchant_id", $merchant_id)->paginate(20);

        return view('merchant.subscriptionpack.renewable_subscription_list', compact('subscription_list',));
    }

    public function addRenewableSubscription(Request $request, $id = NULL)
    {
        $merchant  = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        $all_areas = $this->getAllMerchantAreas(false)->get();
        foreach ($all_areas  as $areas) {
            $arr_area[$areas->id] = $areas->CountryAreaName;
        }
        $all_vehicle_types = $this->getAllVehicleTypes(false)->get();
        foreach ($all_vehicle_types  as $vehicle_types) {
            $arr_vehicle_types[$vehicle_types->id] = $vehicle_types->getVehicleTypeNameAttribute();
        }
        $subscription = NULL;
        if (!empty($id)) {
            $subscription = RenewableSubscription::with('RenewableSubscriptionValue')->where("merchant_id", $merchant_id)->where("id", $id)->first();;
        }
        return view('merchant.subscriptionpack.renewable_subscription', compact('subscription', 'arr_area', 'string_file', 'arr_vehicle_types'));
    }

    public function storeRenewableSubscription(Request $request, $id = NULL)
    {
        $validator = \Validator::make($request->all(), [
            'country_area_id' => ['required'],
            'vehicle_type_id' => ['required'],
            'name' => [
                'required',
                'max:255',
            ],
            'description' => [
                'max:255',
            ],
            'min_fare' => ['required'],
            'max_fare' => ['required'],
            'subscription_fee' => ['required'],
            'slab_count' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $slab_count = $request->slab_count;
            $merchant_id = get_merchant_id();
            $string_file = $this->getStringFile($merchant_id);

            $subscription = RenewableSubscription::where("merchant_id", $merchant_id);
            $renewable_subscription = !empty($id) ? $subscription->where("id", $id)->first() : new RenewableSubscription();

            $exists = RenewableSubscription::where('merchant_id', $merchant_id)
                ->where('country_area_id', $request->country_area_id)
                ->where('vehicle_type_id', $request->vehicle_type_id)
                ->when(!empty($id), function ($query) use ($id) {
                    return $query->where('id', '!=', $id);
                })
                ->exists();
            if ($exists) {
                return back()->withErrors('A renewable subscription with the same country area and vehicle type already exists.');
            }

            if (!empty($id)) {
                RenewableSubscriptionValue::where('renewable_subscription_id', $renewable_subscription->id)->delete();
            }

            $renewable_subscription->merchant_id = $merchant_id;
            $renewable_subscription->country_area_id = $request->country_area_id;
            $renewable_subscription->vehicle_type_id = $request->vehicle_type_id;
            $renewable_subscription->save();
            $lang_data = $request->only(['name', 'description']);
            $this->saveRenewableLang(collect($lang_data), $renewable_subscription);

            for ($i = 0; $i < $slab_count; $i++) {
                $renewable_subscription_value = new RenewableSubscriptionValue();
                $renewable_subscription_value->renewable_subscription_id = $renewable_subscription->id;
                $renewable_subscription_value->renewable_subscription_id = $renewable_subscription->id;
                $renewable_subscription_value->min_fare = $request->min_fare[$i];
                $renewable_subscription_value->max_fare = $request->max_fare[$i];
                $renewable_subscription_value->subscription_fee = $request->subscription_fee[$i];
                $renewable_subscription_value->save();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route("merchant.renewable.subscription")->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->route("merchant.renewable.subscription")->with(trans("$string_file.success"));
    }



    public function SubscriptionReport(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);

        // Get filter inputs
        $start_date = $request->input('start');
        $end_date = $request->input('end');
        $vehicle_type = $request->input('vehicletype');
        $entries = $request->input('entries', 20); // Default to 20 entries per page

        // Build the query
        $subscriptiondata = Driver::select(
            'drs.id',
            'trs.reference_id',
            'trs.updated_at as transaction_date',
            'drs.transaction_id',
            'trs.payment_transaction_id',
            'drivers.id as driver_id',
            'drivers.phoneNumber',
            DB::raw("CONCAT(drivers.first_name, ' ', drivers.last_name) as driver_name"),
            DB::raw("CASE 
                        WHEN drs.payment_method_id = 3 THEN 'Wallet'
                        WHEN drs.payment_method_id = 4 THEN 'Mpesa'
                     END as payment_method"),
            'lvt.vehicleTypeName as vehicle_category',
            DB::raw("CONVERT_TZ(drs.created_at, '+00:00', '+03:00') as transaction_date"),
            DB::raw("CONVERT_TZ(drs.subscription_for_date, '+00:00', '+03:00') as subscription_date"),
            DB::raw('ROUND(drs.earned, 0) as earning'),
            'drs.subscription_fee',
            DB::raw("'' as payment_reference")
        )
            ->join('driver_vehicles', 'driver_vehicles.driver_id', '=', 'drivers.id')
            ->join('driver_renewable_subscription_records as drs', 'drivers.id', '=', 'drs.driver_id')
            ->join('renewable_subscriptions as rs', 'rs.id', '=', 'drs.renewable_subscription_id')
            ->join('vehicle_types as vt', 'vt.id', '=', 'rs.vehicle_type_id')
            ->join('language_vehicle_types as lvt', 'lvt.vehicle_type_id', '=', 'vt.id')
            ->leftJoin('transactions as trs', function ($join) {
                $join->on(DB::raw('trs.payment_transaction_id COLLATE utf8mb4_unicode_ci'), '=', 'drs.transaction_id');
            })
            ->distinct("drs.id")
            ->where('drivers.merchant_id', $merchant_id)
            ->whereColumn('driver_vehicles.vehicle_type_id', 'rs.vehicle_type_id');

        // Apply filters
        if ($request->filled(['start', 'end'])) {
            // Parse input as +03:00 and convert to UTC
            $start_datetime = \Carbon\Carbon::parse($request->start . ' 00:00:00', '+03:00')
                ->setTimezone('UTC')
                ->timestamp;

            $end_datetime = \Carbon\Carbon::parse($request->end . ' 23:59:59', '+03:00')
                ->setTimezone('UTC')
                ->timestamp;

            $subscriptiondata->whereBetween('drs.timestamp', [$start_datetime, $end_datetime]);
        }


        if (!empty($vehicle_type)) {
            $subscriptiondata->where('rs.vehicle_type_id', $vehicle_type);
        }
        // dd( $subscriptiondata->get()[0]);
        // Check if export is requested
        if ($request->has('report') && $request->report === 'excel') {
            $exportData = $subscriptiondata->get(); // Fetch all data for export
            $export = [];

            foreach ($exportData as $item) {
                $export[] = [
                    $item->id,
                    $item->driver_id,
                    $item->payment_method,
                    $item->driver_name,
                    $item->phoneNumber,
                    $item->vehicle_category,
                    $item->subscription_date != null ?  \Carbon\Carbon::parse($item->subscription_date)->format('d-m-Y') : '',
                    $item->earning,
                    $item->subscription_fee,
                    $item->transaction_date != null ?  \Carbon\Carbon::parse($item->transaction_date)->format('d-m-Y') : '',
                    $item->reference_id,
                ];
            }

            $heading = [
                trans("$string_file.subscription") . ' ' . trans("$string_file.id"),
                trans("$string_file.driver") . ' ' . trans("$string_file.id"),
                trans("$string_file.payment_method"),
                trans("$string_file.driver") . ' ' . trans("$string_file.name"),
                trans("$string_file.driver") . ' ' . trans("$string_file.phone"),
                trans("$string_file.vehicle") . ' ' . trans("$string_file.category"),
                trans("$string_file.subscription") . ' ' . trans("$string_file.for") . ' ' . trans("$string_file.date"),
                trans("$string_file.earning") . ' ' . trans("$string_file.on") . ' ' . trans("$string_file.that"),
                trans("$string_file.subscription") . ' ' . trans("$string_file.amount"),
                trans("$string_file.payment") . ' ' . trans("$string_file.date"),
                trans("$string_file.payment") . ' ' . trans("$string_file.reference") . ' ' . trans("$string_file.number"),
            ];

            $file_name = 'subscription-report-' . time() . '.csv';
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CustomExport($heading, $export), $file_name);
        }

        // Paginate with the selected number of entries
        $subscriptiondata = $subscriptiondata->orderBy('drs.id', 'desc')->paginate($entries)->appends($request->all());

        // Fetch vehicle types for the filter dropdown
        $vehicles = VehicleType::where([['merchant_id', '=', $merchant_id], ['admin_delete', '=', NULL]])->get();

        return view('merchant.subscriptionpack.subscription_report', compact('subscriptiondata', 'vehicles'));
    }
}
