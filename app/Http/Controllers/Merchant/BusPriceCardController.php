<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InfoSetting;
use Auth;
use App;
use App\Models\BusPriceCard;
use App\Traits\AreaTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Traits\MerchantTrait;
use App\Traits\BusStopTrait;
use App\Traits\BusRouteTrait;


class BusPriceCardController extends Controller
{

    use BusStopTrait, BusRouteTrait, MerchantTrait, AreaTrait;
    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'bus_routes')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $checkPermission =  check_permission(1, 'price_card_BUS_BOOKING');
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }

        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $price_cards = BusPriceCard::where('merchant_id', $merchant_id)->latest()->paginate(25);
        $status = get_status(true, $string_file);
        return view('merchant.bus-booking.price-card.index', compact('price_cards', 'status'));
    }

    /**
     * Add Edit form of Route Config
     */
    public function add(Request $request, $id = null)
    {
        $merchant = get_merchant_id(false);
        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);
        // $selected_stop_points = [];
        $data = [];
        $bus_price_card_id = null;
        if (!empty($id)) {
            $data = BusPriceCard::findorfail($id);
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");
            $bus_price_card_id = $data->bus_route_id;
        } else {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title . ' ' . trans("$string_file.price_card");

        $vehicle_types = App\Models\VehicleType::where([['merchant_id', '=', $merchant->id],['admin_delete', '=', NULL]])->get();
        $vehicle_type_arr = [];
        foreach($vehicle_types as $vehicle_type){
            $vehicle_type_arr[$vehicle_type->id] = $vehicle_type->VehicleTypeName;
        }
        $return['price_card'] = [
            'data' => $data,
            'arr_area' => $this->getMerchantCountryArea($this->getAreaList(false, true)->get()),
            'vehicle_type_arr' => $vehicle_type_arr,
            'submit_url' => route('bus_booking.save_price_card', $id),
            'title' => $title,
            'arr_status' => add_blank_option(get_status(true, $string_file), trans("$string_file.select")),
            'submit_button' => $submit_button,
            'package_delivery_config'=>$merchant->ApplicationConfiguration->bus_booking_package_delivery == 1,
        ];
        $return['is_demo'] = $is_demo;

        return view('merchant.bus-booking.price-card.form')->with($return);
    }
    /***
     * Save/update function of duration
     */
    public function save(Request $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $request->merge(["bus_price_card_id" => $id]);
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            // 'title' => 'required',
            'country_area_id' => 'required_if:bus_price_card_id,==,null',
             'vehicle_type_id' => 'required_if:bus_price_card_id,==,null',
//            'route_config_id' => 'required_if:price_card_id,==,null',
            'bus_route_id' => 'required_if:bus_price_card_id,==,null',
            'status' => 'required|between:1,2',
            'stops_fare_.*' => 'required',
            'end_stop_fare' => 'required',
            'base_fare' => 'required',
            'cancel_time' => 'required_if:cancel_charges,==1',
            'cancel_amount' => 'required_if:cancel_charges,==1',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $bus_price_card = BusPriceCard::Find($id);
            } else {
                $bus_price_card = new BusPriceCard;
                $bus_price_card->merchant_id = $merchant_id;
                $bus_price_card->bus_route_id = $request->bus_route_id;
                $bus_price_card->country_area_id = $request->country_area_id;
                $bus_price_card->vehicle_type_id = $request->vehicle_type_id;
            }

            $bus_price_card->status = $request->status;
            $bus_price_card->start_stop_fare = $request->start_stop_fare;
            $bus_price_card->end_stop_fare = $request->end_stop_fare;
            $bus_price_card->base_fare = $request->base_fare;
            $bus_price_card->package_delivery_base_fare = $request->package_delivery_base_fare;

            $bus_price_card->cancel_charges = $request->cancel_charges;
            $bus_price_card->cancel_time = $request->cancel_time;
            $bus_price_card->cancel_amount = $request->cancel_amount;

            $bus_price_card->save();


            // store price card value
            $bus_price_card->StopPointsPrice()->detach();
            if(!empty($request->stops_fare)){
                foreach ($request->stops_fare as $bus_stop_id => $fare) {
                    $bus_price_card->StopPointsPrice()->attach($bus_price_card->id, ['bus_stop_id' => $bus_stop_id, 'price' => $fare]);
                }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return redirect()->back()->withErrors($message);
        }
        DB::commit();
        return redirect()->route('bus_booking.price_card')->withSuccess(trans("$string_file.saved_successfully"));
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
            return redirect()->back();
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        if ($merchant->demo == 1) {
            return redirect()->back()->withErrors(trans("$string_file.demo_warning_message"));
        }
        $merchant_id = $merchant->id;
        $BusPriceCard = BusPriceCard::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $BusPriceCard->status = $status;
        $BusPriceCard->save();
        return redirect()->back()->withSuccess(trans("$string_file.status_updated"));
    }
}
