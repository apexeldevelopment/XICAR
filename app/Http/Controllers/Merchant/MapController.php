<?php

namespace App\Http\Controllers\Merchant;


use App\Models\InfoSetting;
use App\Models\MapMarker;
use App\Traits\BookingTrait;
use Auth;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MapController extends Controller
{
    use BookingTrait,MerchantTrait;

    public function HeatMap()
    {
        $checkPermission =  check_permission(1,'view_heat_map');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_segments = get_merchant_segment();
        if(count($merchant_segments) > 1){
            $merchant_segments = add_blank_option(get_merchant_segment(),trans("$string_file.all")." ".trans("$string_file.segment"));
        }
        $booking = $this->allBookings(false);
        $bookings = $booking->get(['pickup_latitude', 'pickup_longitude']);
        // p($bookings);
        $info_setting = InfoSetting::where('slug', 'HEAT_MAP')->first();
        return view('merchant.map.heat', compact('bookings','info_setting','merchant_segments'));
    }

    public function DriverMap()
    {
        $checkPermission =  check_permission(1,'view_driver_map');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_segments = get_merchant_segment();
        if(count($merchant_segments) > 1){
            $merchant_segments = add_blank_option(get_merchant_segment(),trans("$string_file.all")." ".trans("$string_file.segment"));
        }
        $info_setting = InfoSetting::where('slug', 'DRIVER_MAP')->first();
        return view('merchant.map.driver',compact('merchant_segments','info_setting'));
    }

    public function realTimeDriver()
    {
        $checkPermission =  check_permission(1,'view_driver_map');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_segments = get_merchant_segment();
        if(count($merchant_segments) > 1){
            $merchant_segments = add_blank_option(get_merchant_segment(),trans("$string_file.all")." ".trans("$string_file.segment"));
        }
        $info_setting = InfoSetting::where('slug', 'DRIVER_MAP')->first();
        return view('merchant.map.realtime',compact('merchant_segments','info_setting'));
    }

    public function MapMarker(){
        $merchant = get_merchant_id(false);
//        $string_file = $this->getStringFile(NULL,$merchant);
        $info_setting = InfoSetting::where('slug', 'DRIVER_MAP')->first();
        $map_marker = MapMarker::where([['merchant_id','=',$merchant->id]])->first();
        return view('merchant.map.map_marker',compact('map_marker','info_setting'));
    }

    public function SaveMapMarker(Request $request, $id = NULL){
        $validator = Validator::make($request->all(),[
            'pickup_map_marker' => 'required',
            'drop_map_marker' => 'required',
        ]);

        if ($validator->fails()){
            $message = $validator->messages();
            return redirect()->back()->withErrors($message);
        }

        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        if (!empty($id)){
            $map_marker = MapMarker::find($id);
        }else{
            $map_marker = new MapMarker();
            $map_marker->merchant_id = $merchant_id;
        }

        $map_marker->pickup_map_marker = $request->pickup_map_marker;
        $map_marker->drop_map_marker = $request->drop_map_marker;
        $map_marker->status = $request->status;
        $map_marker->save();
        return redirect()->back()->with('success', trans("$string_file.success"));
    }
}
