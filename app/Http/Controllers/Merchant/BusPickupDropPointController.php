<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InfoSetting;
use Auth;
use App;
use App\Models\BusPickupDropPoint;
use App\Models\LanguageBusPickupDropPoint;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Traits\MerchantTrait;


class BusPickupDropPointController extends Controller
{
    use MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'bus_pickup_drop_point')->first();
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
        $pickup_drop_points = BusPickupDropPoint::where('merchant_id', $merchant_id)->latest()->paginate(25);
        return view('merchant.bus-booking.pickup_drop_point.index', compact('pickup_drop_points'));
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
            $data = BusPickupDropPoint::where('id',$id)->first();
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");
        } else {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title . ' ' . trans("$string_file.bus_pickup_drop_point");
        $return['bus_pickup_drop_point'] = [
            'data' => $data,
            'submit_url' => route('bus_booking.save_bus_pickup_drop_points', $id),
            'title' => $title,
            'submit_button' => $submit_button,
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.bus-booking.pickup_drop_point.form')->with($return);
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
                Rule::unique('language_bus_pickup_drop_points', 'title')->where(function ($query) use ($merchant_id, $id) {
                    return $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', App::getLocale()]])->where(function ($qq) use ($id) {
                        if (!empty($id)) {
                            $qq->where('bus_pickup_drop_point_id', '!=', $id);
                        }
                    });
                })
            ],
            'latitude' => 'required',
            'longitude' => 'required',
            'address' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $pickup_drop_point = BusPickupDropPoint::find($id);
            } else {
                $pickup_drop_point = new BusPickupDropPoint;
                $pickup_drop_point->merchant_id = $merchant_id;
            }
            $pickup_drop_point->address = $request->address;
            $pickup_drop_point->latitude = $request->latitude;
            $pickup_drop_point->longitude = $request->longitude;
            $pickup_drop_point->status = 1;
            $pickup_drop_point->save();
            $this->SaveLanguageBusPickupDropPoint($merchant_id, $pickup_drop_point->id, $request->title);
            DB::commit();
            return redirect()->route('bus_booking.bus_pickup_drop_points')->withSuccess(trans("$string_file.saved_successfully"));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function SaveLanguageBusPickupDropPoint($merchant_id, $id, $title)
    {
        LanguageBusPickupDropPoint::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'bus_pickup_drop_point_id' => $id
        ], [
            'title' => $title,
        ]);
    }
}
