<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InfoSetting;
use Auth;
use App;
use App\Models\BusService;
use App\Models\LanguageBusService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;


class BusServiceController extends Controller
{
    use MerchantTrait, ImageTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'bus_service')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $checkPermission =  check_permission(1, 'bus_routes_BUS_BOOKING');
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }

        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $bus_services = BusService::where('merchant_id', $merchant_id)->latest()->paginate(25);
        $status = get_status(true, $string_file);
        return view('merchant.bus-booking.bus_service.index', compact('bus_services','status'));
    }

    /**
     * Add Edit form of duration
     */
    public function add($id = null)
    {
        $merchant = get_merchant_id(false);

        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);
        $data = [];
        if (!empty($id)) {
            $data = BusService::where('id',$id)->first();
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");
        } else {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title . ' ' . trans("$string_file.bus_service");
        $return['bus_service'] = [
            'data' => $data,
            'submit_url' => route('bus_booking.save_service', $id),
            'title' => $title,
            'submit_button' => $submit_button,
            'status' => get_status(true, $string_file)
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.bus-booking.bus_service.form')->with($return);
    }

    /***
     * Save/update function of duration
     */
    public function save(Request $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                Rule::unique('language_bus_services', 'title')->where(function ($query) use ($merchant_id, $id) {
                    return $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', App::getLocale()]])->where(function ($qq) use ($id) {
                        if (!empty($id)) {
                            $qq->where('bus_service_id', '!=', $id);
                        }
                    });
                })
            ],
            'icon' => 'required_without:bus_service_id|file',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $bus_service = BusService::find($id);
            } else {
                $bus_service = new BusService;
                $bus_service->merchant_id = $merchant_id;
            }
            $bus_service->is_general_info = $request->is_general_info;
            $bus_service->sequence = $request->sequence;
            $bus_service->status = 1;
            if($request->hasFile("icon")){
                $bus_service->icon = $this->uploadImage('icon', 'bus_service');
            }
            $bus_service->save();
            $this->SaveLanguageBusService($merchant_id, $bus_service->id, $request->title, $request->description);
            DB::commit();
            return redirect()->route('bus_booking.services')->withSuccess(trans("$string_file.saved_successfully"));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function SaveLanguageBusService($merchant_id, $id, $title, $description)
    {
        LanguageBusService::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'bus_service_id' => $id
        ], [
            'title' => $title,
            'description' => $description
        ]);
    }
}
