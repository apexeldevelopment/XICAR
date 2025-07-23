<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\HolderController;
use App\Models\BookingRating;
use App\Models\BookingRequestDriver;
use App\Models\BookingTransaction;
use App\Models\EmailConfig;
use App\Models\EmailTemplate;
use App\Models\FailBooking;
use App\Models\InfoSetting;
use App\Models\Onesignal;
use App\Models\Outstanding;
use App\Models\PriceCard;
use App\Models\PriceCardValue;
use App\Models\PricingParameter;
use Auth;
use App\Models\Driver;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\BookingConfiguration;
use App\Traits\BookingTrait;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Events\SendUserInvoiceMailEvent;
use View;
use App\Traits\MerchantTrait;
use App\Traits\AreaTrait;
use DB;
use App\Models\BookingDetail;
use App\Http\Controllers\Helper\GoogleController;
use App\Models\BookingCoordinate;
use App\Models\CountryArea;
use App\Http\Controllers\Helper\PolygenController;
use App\Http\Controllers\Helper\DistanceCalculation;
use App\Http\Controllers\PaymentMethods\Payment;
use App\Http\Controllers\Helper\ReferralController;
use App\Http\Controllers\Helper\PriceController;
use App\Http\Controllers\Helper\RewardPoint;
use App\Http\Controllers\Helper\SmsController;
use App\Models\SmsConfiguration;
use App\Http\Controllers\Api\CashbackController;
use App\Http\Controllers\Services\PoolController;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Exception;

class BookingController extends Controller
{
    use BookingTrait, MerchantTrait, AreaTrait;
    // common search blade
    public function __construct()
    {
        //        $query_string = \Request::getRequestUri();
        //        $query_string_arr = explode('/',$query_string);
        //        if (in_array("DELIVERY", $query_string_arr)) {
        //            $info_setting = InfoSetting::where('slug','DELIVERY_RIDE')->first();
        //        }else {
        //            $info_setting = InfoSetting::where('slug','TAXI_RIDE')->first();
        //        }
        //        view()->share('info_setting', $info_setting);
    }

    public function orderSearchView($request)
    {
        $data['arr_search'] = $request->all();

        $order_search = View::make('merchant.booking.ride-search')->with($data)->render();
        return $order_search;
    }

    // active bookings
    public function index(Request $request, $url_slug)
    {
        $checkPermission =  check_permission(1, "ride_management_$url_slug");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $merchant_id = get_merchant_id();
        $request->merge(['search_route' => route('merchant.activeride', $url_slug), 'request_from' => "ACTIVE", 'url_slug' => $url_slug]);
        //        $all_bookings = $this->ActiveBooking(true,'MERCHANT',$request);
        $all_bookings = $this->getBookings($request, $pagination = true, 'MERCHANT');
        //        if (!empty($all_bookings)){
        //            foreach ($all_bookings as $booking){
        //                if (!empty($booking->driver_id)){
        //                    $timezone = $booking->Driver->CountryArea->timezone;
        //                }else{
        //                    if(isset($booking->User->Country->CountryArea[0]->timezone)){
        //                        $timezone = $booking->User->Country->CountryArea[0]->timezone;
        //                    }else{
        //                        $timezone = 'Asia/Kolkata';
        //                    }
        //                }
        //                date_default_timezone_set($timezone);
        //                $date = date('Y-m-d');
        //                $currenct_time = date('H:i');
        //                $later_booking_date = set_date($booking->later_booking_date);
        //                if ($booking->booking_type == 1 && $date > $booking->updated_at){
        //                    Booking::where('id','=',$booking->id)->update(['booking_status'=> 1016]);
        //                }
        //                if ($booking->booking_type == 2 && ($date > $later_booking_date || ($date == $later_booking_date && $currenct_time > $booking->later_booking_time))){
        //                    Booking::where('id','=',$booking->id)->update(['booking_status'=> 1016]);
        //                }
        //            }
        //        }
        //        $bookings = $all_bookings;
        //        $bookings = $this->ActiveBookingNow();
        //        $later_bookings = $this->ActiveBookingLater();
        $cancelreasons = $this->CancelReason();
        $bookingConfig = BookingConfiguration::select('ride_otp')->where([['merchant_id', '=', $merchant_id]])->first();

        $search_view = $this->orderSearchView($request);
        $request->merge(["url_slug" => $url_slug]);
        $arr_search = $request->all();
        $string_file = $this->getStringFile($merchant_id);
        $arr_booking_status = $this->getBookingStatus($string_file);
        if ($url_slug == "DELIVERY") {
            $info_setting = InfoSetting::where('slug', 'DELIVERY_RIDE')->first();
        } else {
            $info_setting = InfoSetting::where('slug', 'TAXI_RIDE')->first();
        }
        return view('merchant.booking.active', compact('all_bookings', 'cancelreasons', 'bookingConfig', 'search_view', 'arr_search', 'arr_booking_status', 'info_setting'));
    }

    public function AutoCancel(Request $request, $url_slug)
    {
        $request->merge(['search_route'=>route('merchant.autocancel',$url_slug),'request_from'=>'AUTO_CANCEL','url_slug'=>$url_slug]);
        $bookings = $this->getBookings($request,$pagination = true, 'MERCHANT');
        $arr_search = $request->all();
        $data = [];
        if ($url_slug == "DELIVERY") {
            $info_setting = InfoSetting::where('slug', 'AUTO_CANCELLED_DELIVERY_RIDE')->first();
        } else {
            $info_setting = InfoSetting::where('slug', 'AUTO_CANCELLED_TAXI_RIDE')->first();
        }
        return view('merchant.booking.auto-cancel', compact('bookings', 'data', 'url_slug', 'arr_search', 'info_setting'));
    }

    public function SearchForAutoCancel(Request $request, $url_slug)
    {
        $query = $this->bookings(false, [1016], 'MERCHANT', $url_slug);
        if ($request->booking_id) {
            $query->where('merchant_booking_id', $request->booking_id);
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")
                    ->orWhere('email', 'LIKE', "%$keyword%")
                    ->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if ($request->date) {
            $query->whereDate('created_at', '=', $request->date);
        }
        if ($request->driver) {
            $keyword = $request->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        $arr_search = $request->all();
        $bookings = $query->paginate(25);
        $data = $request->all();
        return view('merchant.booking.auto-cancel', compact('bookings', 'data', 'url_slug', 'arr_search'));
    }

    public function AllRides(Request $request, $url_slug)
    {
        //        $bookings = $this->bookings(true, [1001, 1012, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1016]) ;
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $arr_booking_status = $this->getBookingStatus($string_file);
        $arr_area = $this->getMerchantCountryArea($this->getAreaList(false, true, [], null, null, false, false)->get());
        $request->merge(['search_route'=>route('merchant.all.ride',$url_slug),'request_from'=>"ALL",'arr_booking_status'=>$arr_booking_status,'url_slug'=>$url_slug,'arr_area'=>$arr_area]);
        $bookings = $this->getBookings($request,$pagination = true, 'MERCHANT');
        $search_view = $this->orderSearchView($request);
        $arr_search = $request->all();
        if ($url_slug == "DELIVERY") {
            $info_setting = InfoSetting::where('slug', 'ALL_DELIVERY_RIDE')->first();
        } else {
            $info_setting = InfoSetting::where('slug', 'ALL_TAXI_RIDE')->first();
        }
        return view('merchant.booking.all-ride', compact('bookings', 'search_view', 'arr_search', 'arr_booking_status', 'info_setting'));
    }

    //    public function SearchForAllRides(Request $request, $url_slug)
    //    {
    //        $query = $this->bookings(false, [1001, 1012, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1016],'MERCHANT',$url_slug);
    //        if ($request->booking_id) {
    //            $query->where('merchant_booking_id', $request->booking_id);
    //        }
    //        if ($request->booking_status) {
    //            $query->where('booking_status', $request->booking_status);
    //        }
    //        if ($request->rider) {
    //            $keyword = $request->rider;
    //            $query->WhereHas('User', function ($q) use ($keyword) {
    //                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
    //            });
    //        }
    //        if ($request->date) {
    //            $query->whereDate('created_at', '=', $request->date);
    //        }
    //        if ($request->driver) {
    //            $keyword = $request->driver;
    //            $query->WhereHas('Driver', function ($q) use ($keyword) {
    //                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
    //            });
    //        }
    //        $bookings = $query->paginate(25);
    //        $data = $request->all();
    //        return view('merchant.booking.all-ride', compact('bookings','data', 'url_slug'));
    //    }

    public function CancelBooking(Request $request, $url_slug)
    {
        $checkPermission =  check_permission(1, "ride_management_$url_slug");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $merchant_id = get_merchant_id();
        $arr_area = $this->getMerchantCountryArea($this->getAreaList(false, true, [], null, null, false, false)->get());
        $request->merge(['search_route'=>route('merchant.cancelride',$url_slug),'request_from'=>"CANCEL",'url_slug'=>$url_slug, 'arr_area' => $arr_area]);
//        $bookings = $this->bookings(true, [1006, 1007, 1008]);
        $bookings = $this->getBookings($request,$pagination = true, 'MERCHANT');
//        $bookings = $this->bookings(true, [1005]);
        $search_view = $this->orderSearchView($request);
        $arr_search = $request->all();
        $string_file = $this->getStringFile($merchant_id);
        $arr_booking_status = $this->getBookingStatus($string_file);
        if ($url_slug == "DELIVERY") {
            $info_setting = InfoSetting::where('slug', 'CANCELLED_DELIVERY_RIDE')->first();
        } else {
            $info_setting = InfoSetting::where('slug', 'CANCELLED_TAXI_RIDE')->first();
        }
        return view('merchant.booking.cancel', compact('bookings', 'search_view', 'arr_search', 'arr_booking_status', 'info_setting'));
    }

    //    public function SearchCancelBooking(Request $request)
    //    {
    //        $query = $this->bookings(false, [1006, 1007, 1008]);
    //        if ($request->booking_id) {
    //            $query->where('merchant_booking_id', $request->booking_id);
    //        }
    //        if ($request->rider) {
    //            $keyword = $request->rider;
    //            $query->WhereHas('User', function ($q) use ($keyword) {
    //                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
    //            });
    //        }
    //        if ($request->date) {
    //            $query->whereDate('created_at', '=', $request->date);
    //        }
    //        if ($request->driver) {
    //            $keyword = $request->driver;
    //            $query->WhereHas('Driver', function ($q) use ($keyword) {
    //                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
    //            });
    //        }
    //        $bookings = $query->paginate(25);
    //        $data = $request->all();
    //        return view('merchant.booking.cancel', compact('bookings','data'));
    //    }

    public function CompleteBooking(Request $request, $url_slug)
    {
        $checkPermission =  check_permission(1, "ride_management_$url_slug");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $arr_area = $this->getMerchantCountryArea($this->getAreaList(false, true, [], null, null, false, false)->get());
        $request->merge(['search_route' => route('merchant.completeride', $url_slug), 'request_from' => "COMPLETE", 'url_slug' => $url_slug, 'arr_area' => $arr_area]);
        $bookings = $this->getBookings($request, $pagination = true, 'MERCHANT');
        //        $bookings = $this->bookings(true, [1005]);
        $search_view = $this->orderSearchView($request);
        $arr_search = $request->all();
        if ($url_slug == "DELIVERY") {
            $info_setting = InfoSetting::where('slug', 'COMPLETED_DELIVERY_RIDE')->first();
        } else {
            $info_setting = InfoSetting::where('slug', 'COMPLETED_TAXI_RIDE')->first();
        }
        return view('merchant.booking.complete', compact('bookings', 'search_view', 'arr_search', 'info_setting'));
    }

    public function endRide(Request $request){
        try{
            
        $booking_id = $request->booking_id;
        $booking = Booking::find($request->booking_id);
        $driver = $booking->Driver;
        $latitude = $booking->Driver->current_latitude;
        $longitude = $booking->Driver->current_longitude;
        $merchant_id = $driver->merchant_id;
        
        $configuration = $driver->Merchant->BookingConfiguration;
        $config = $driver->Merchant->Configuration;
        $socket_enable = false;
        if ($config->lat_long_storing_at == 2) {
            $validator = Validator::make($request->all(), [
                //                    'booking_polyline' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
                //                    return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
            }
            $socket_enable = true;
        }
        $appConfig = $driver->Merchant->ApplicationConfiguration;
        $key = $configuration->google_key;

        $booking = Booking::with('PriceCard', 'BookingCoordinate')->find($booking_id);
        $string_file = $this->getStringFile(NULL, $booking->Merchant);
            if ($booking->Segment->slag == "DELIVERY") {
                self::deliveryDetailStore($request, $booking, $configuration->delivery_drop_otp);
            }

            if (isset($request->onride_waiting_time) && !empty($request->onride_waiting_time)) {
                $booking->onride_waiting_time = $request->onride_waiting_time;
                $booking->save();
                $booking = $booking->fresh();
            }

            if ($config->outside_area_ratecard == 1) {
                $area = CountryArea::find($booking->country_area_id);
                $ploygon = new PolygenController();
                $checkArea = $ploygon->CheckArea($request->latitude, $request->longitude, $area->AreaCoordinates);
                $price_card = PriceCard::select('id')->where(function ($query) use ($booking, $checkArea) {
                    $query->where([['merchant_id', '=', $booking->merchant_id], ['service_type_id', '=', $booking->service_type_id], ['country_area_id', '=', $booking->country_area_id], ['vehicle_type_id', '=', $booking->vehicle_type_id], ['status', '=', 1]]);
                    if (!$checkArea) {
                        $query->where('rate_card_scope', 2);
                    } else {
                        $query->where('rate_card_scope', 1);
                    }
                })->first();
                if (!empty($price_card)) {
                    $price_card_id = $price_card->id;
                    $booking->price_card_id = $price_card_id;
                    $booking->save();
                    $booking = $booking->fresh();
                }
            }

            $outstation_inside_City = (isset($configuration->outstation_inside_city) && $configuration->outstation_inside_city == 1) ? true : false;
            $bookingDetails = self::storeBookingDetails($request, $booking, $outstation_inside_City, $key, $socket_enable);

            $pricing_type = $booking->PriceCard->pricing_type;
            $price_card_id = $booking->price_card_id;
            $service_type_id = $booking->service_type_id;
            $service_type = $booking->ServiceType->type;

            $start_timestamp = $bookingDetails->start_timestamp;
            $endTimeStamp = $bookingDetails->end_timestamp;
            $seconds = $endTimeStamp - $start_timestamp;
            $hours = floor($seconds / 3600);
            $mins = floor($seconds / 60 % 60);
            $secs = floor($seconds % 60);
            $timeFormat = sprintf('%02d H %02d M', $hours, $mins, $secs);
            $rideTime = round(abs($endTimeStamp - $start_timestamp) / 60, 2);
            $from = $bookingDetails->start_latitude . "," . $bookingDetails->start_longitude;
            $to = $latitude . "," . $longitude;
            $coordinates = "";
            $bookingData = new BookingDataController();
            switch ($service_type) {
                case "1":
                    if (!empty($request->app_distance)) {
                        $distance = $request->app_distance;
                    } else {
                        $bookingcoordinates = BookingCoordinate::where([['booking_id', '=', $request->booking_id]])->first();
                        $pick = $booking->pickup_latitude . "," . $booking->pickup_longitude;
                        $drop = $booking->drop_latitude . "," . $booking->drop_longitude;
                        $distanceCalculation = new DistanceCalculation();
                        $booking_coordinates = isset($bookingcoordinates['coordinates']) ? $bookingcoordinates['coordinates'] : "";
                        $distance = $distanceCalculation->distance($from, $to, $pick, $drop, $booking_coordinates, $merchant_id, $key, "endRide$booking_id", $string_file);
                        $distance = round($distance);
                        if ($socket_enable == true) {
                            $coordinates = $this->decodeValue($bookingcoordinates['booking_polyline']);
                        } else {
                            $coordinates = $booking_coordinates;
                        }
                        //                    $coordinates = $bookingcoordinates['coordinates'];
                    }
                    break;
                case "5":
                    $units = ($booking->CountryArea->Country['distance_unit'] == 1) ? 'metric' : 'imperial';
                    $distance = GoogleController::GoogleShortestPathDistance($from, $to, $key, $units, 'metric', "endRide$booking_id", $string_file);
                    $distance = round($distance);
                    break;
                default:
                    $distance = $bookingDetails->end_meter_value - $bookingDetails->start_meter_value;
                    $distance = $distance * 1000;
            }

            if ($booking->Merchant->ApplicationConfiguration->mileage_reward == 1) {
                $this->saveUserRewardPoints($distance, $booking->User);
            }

            $merchant = new \App\Http\Controllers\Helper\Merchant();
            $tax_charge = 0;
            $hotel_amount = 0;
            $per_bag_charges = 0;
            $per_pat_charges = 0;
            switch ($pricing_type) {
                case "1":
                case "2":
                    // When estimate bill is equals to final bill
                    if($booking->Segment->id == 2){  //for Delivery 2
                        // When estimate bill is equals to final bill
                        if ($configuration->final_bill_calculation_delivery == 2) {
                            // dd('delivery_if');
                            $estimateIsFinalArr = $this->estimateIsFinalBillForAdmin($request,$appConfig,$booking,$bookingDetails,$bookingData,$merchant,$merchant_id,$string_file);
                            $total_payable = $estimateIsFinalArr['totalPayable'];
                            $bookingFee = $estimateIsFinalArr['bookingFee'];
                            $amount_for_commission = $estimateIsFinalArr['amountForComission'];
                            $billDetails = $estimateIsFinalArr['bill_details'];
                             $amount = $estimateIsFinalArr['amount'];
                            $BillDetails = $estimateIsFinalArr['BillDetails'];
                            
                        }else{
                            // dd('deliver_else');
                            $actualFinalArr = $this->actualFinalBill($appConfig,$request,$booking,$bookingDetails,$merchant_id,$distance,$rideTime,$from,$to,$coordinates,$bookingData,$merchant,$string_file);
                            // dd($actualFinalArr);
                            $total_payable = $actualFinalArr['totalPayable'];
                            $bookingFee = $actualFinalArr['bookingFee'];
                            $amount_for_commission = $actualFinalArr['amountForComission'];
                            $billDetails = $actualFinalArr['bill_details'];
                            $hotel_amount = $actualFinalArr['hotelAmount'];
                            $amount = $actualFinalArr['amount'];
                            $BillDetails = $actualFinalArr['BillDetails'];
                             $per_pat_charges = $actualFinalArr['per_pat_charges'];
                            $per_bag_charges = $actualFinalArr['per_bag_charges'];
                            
                        }
                    }else if($booking->Segment->id == 1){ //for taxi 1
                        // When estimate bill is equals to final bill
                        if ($configuration->final_bill_calculation == 2) {
                            $estimateIsFinalArr = $this->estimateIsFinalBillForAdmin($request,$appConfig,$booking,$bookingDetails,$bookingData,$merchant,$merchant_id,$string_file);
                            $total_payable = $estimateIsFinalArr['totalPayable'];
                            $bookingFee = $estimateIsFinalArr['bookingFee'];
                            $amount_for_commission = $estimateIsFinalArr['amountForComission'];
                            $billDetails = $estimateIsFinalArr['bill_details'];
                             $amount = $estimateIsFinalArr['amount'];
                            $BillDetails = $estimateIsFinalArr['BillDetails'];
                        }else{
                             $actualFinalArr = $this->actualFinalBill($appConfig,$request,$booking,$bookingDetails,$merchant_id,$distance,$rideTime,$from,$to,$coordinates,$bookingData,$merchant,$string_file);
                            // dd($actualFinalArr);
                            $total_payable = $actualFinalArr['totalPayable'];
                            $bookingFee = $actualFinalArr['bookingFee'];
                            $amount_for_commission = $actualFinalArr['amountForComission'];
                            $billDetails = $actualFinalArr['bill_details'];
                            $hotel_amount = $actualFinalArr['hotelAmount'];
                            $amount = $actualFinalArr['amount'];
                            $BillDetails = $actualFinalArr['BillDetails'];
                             $per_pat_charges = $actualFinalArr['per_pat_charges'];
                            $per_bag_charges = $actualFinalArr['per_bag_charges'];
                        }
                    }
                    
                    
                    // if ($configuration->final_bill_calculation == 2) {
                    //     $bill_details_data = [];
                    //     // $tip_And_toll = self::storeTipAndTollCharge($appConfig, $request, $booking, $bookingDetails);
                    //     $booking->fresh();
                    //     // $tip_amount = $tip_And_toll['tip_amount'];
                    //     if (empty($booking->bill_details)) {
                    //         throw new Exception("Bill details not found.");
                    //     }
                    //     $BillDetails = $this->EstimateBillDetailsBreakup($booking->bill_details, $booking->estimate_bill);
                    //     // $amount = $booking->estimate_bill;
                    //     $amount = $BillDetails['amount'];
                    //     $hotel_amount = $BillDetails['hotel_amount'];
                    //     if (!empty($booking->price_for_ride) && $booking->price_for_ride != 1) {
                    //         $BillDetails = $bookingData->checkPriceForRide($booking, $BillDetails);
                    //         $booking->bill_details = json_encode($BillDetails['bill_details'], true);
                    //         $booking->save();
                    //         $hotel_amount = 0;  // in case of maximum fare and fix fare
                    //     }

                    //     // $total_payable = $merchant->FinalAmountCal(($amount + $bookingDetails->tip_amount), $merchant_id);
                    //     $total_payable = $merchant->FinalAmountCal($amount, $merchant_id);
                    //     Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->update(['pay_status' => 1]);
                    //     $bookingFee = $BillDetails['booking_fee'];
                    //     $amount_for_commission = $BillDetails['subTotalWithoutDiscount'] - $bookingFee;
                    //     $billDetails = json_encode($BillDetails['bill_details'], true);
                    // } else {
                    //     $outstanding_amount = Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->sum('amount');
                    //     //                        $priceCard = PriceCard::find($price_card_id);
                    //     //                        date_default_timezone_set($priceCard->CountryArea->timezone);
                    //     // creating time zone issues

                    //     //this convertTimeToUSERzone function added to fix for night and peak charges
                    //     $bookingTime = convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone, $merchant_id, $booking->Merchant, 3);
                    //     $bookingDate = convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone, $merchant_id, $booking->Merchant, 2);
                    //     $finalBill = new PriceController();
                    //     $BillDetails = $finalBill->BillAmount([
                    //         'price_card_id' => $price_card_id,
                    //         'merchant_id' => $merchant_id,
                    //         'distance' => $distance,
                    //         'time' => $rideTime,
                    //         'booking_id' => $booking_id,
                    //         'user_id' => $booking->user_id,
                    //         'driver_id' => $booking->driver_id,
                    //         'booking_time' => $booking->booking_type == 2 ? $booking->later_booking_time : $bookingTime,
                    //         'booking_date' => $booking->booking_type == 2 ? $booking->later_booking_date : $bookingDate,
                    //         'waitTime' => $bookingDetails->wait_time,
                    //         'dead_milage_distance' => $bookingDetails->dead_milage_distance,
                    //         'outstanding_amount' => $outstanding_amount,
                    //         'number_of_rider' => $booking->number_of_rider,
                    //         'from' => $from,
                    //         'to' => $to,
                    //         'coordinates' => $coordinates,
                    //         'units' => $booking->CountryArea->Country['distance_unit'],
                    //         'manual_toll_charge' => isset($request->manual_toll_charge) ? $request->manual_toll_charge : '',
                    //         'hotel_id' => !empty($booking->hotel_id) ? $booking->hotel_id : NULL,
                    //         'additional_movers' => !empty($booking->additional_movers) ? $booking->additional_movers : NULL,
                    //     ]);
                    //     if ($booking->Merchant->BookingConfiguration->driver_booking_amount == 1) {
                    //         $amount = $request->ride_amount;
                    //         $amountCommission = $request->ride_amount;
                    //     } else {
                    //         $amount = $BillDetails['amount'];
                    //         $amountCommission = $BillDetails['subTotalWithoutDiscount'];
                    //     }

                    //     Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->update(['pay_status' => 1]);
                    //     // amount => Sub total without discount + extra charges(night time /peck time) + surge charge - promo + taxs + insurance + toll charges +  cancellation_amount_received
                    //     if (!empty($booking->price_for_ride)) {
                    //         $BillDetails = $bookingData->checkPriceForRide($booking, $BillDetails);
                    //         $amount = $BillDetails['amount'];
                    //         $amountCommission = $BillDetails['subTotalWithoutDiscount'];
                    //     }
                    //     $total_payable = $merchant->FinalAmountCal($amount, $merchant_id);

                    //     $bookingFee = $BillDetails['booking_fee'];
                    //     $amount_for_commission = $amountCommission - $bookingFee;

                    //     $newArray = $BillDetails['bill_details'];
                    //     $bookingDetails->promo_discount = $BillDetails['promo'];
                    //     if ($appConfig->tip_status == 1) {
                    //         if(!empty($bookingDetails->tip_amount)){
                                
                    //         $tip_parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => "Tip", 'parameterType' => "tip_amount", 'amount' => $bookingDetails->tip_amount, 'type' => "CREDIT", 'code' => "");
                    //         array_push($newArray, $tip_parameter);
                    //         }else{
                    //             $tip_parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => "Tip", 'parameterType' => "tip_amount", 'amount' => "0", 'type' => "CREDIT", 'code' => "");
                    //             array_push($newArray, $tip_parameter);
                    //         }
                    //     }

                    //     // No Of Bag Charges
                    //     if (isset($booking->Merchant->Configuration->chargable_no_of_bags) && $booking->Merchant->Configuration->chargable_no_of_bags == 1) {
                    //         if ($booking->no_of_bags > 0 && $booking->PriceCard->per_bag_charges > 0) {
                    //             $per_bag_charges = $booking->PriceCard->per_bag_charges * $booking->no_of_bags;
                    //             $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans("$string_file.bag_charges"), 'amount' => $per_bag_charges, 'type' => "CREDIT", 'code' => "");
                    //             array_push($newArray, $parameter);
                    //         }
                    //     }
                    //     // No Of Pat Charges
                    //     if (isset($booking->Merchant->Configuration->chargable_no_of_pats) && $booking->Merchant->Configuration->chargable_no_of_pats == 1) {
                    //         if ($booking->no_of_pats > 0 && $booking->PriceCard->per_pat_charges > 0) {
                    //             $per_pat_charges = $booking->PriceCard->per_pat_charges * $booking->no_of_pats;
                    //             $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans("$string_file.pat_charges"), 'amount' => $per_pat_charges, 'type' => "CREDIT", 'code' => "");
                    //             array_push($newArray, $parameter);
                    //         }
                    //     }

                    //     $billDetails = json_encode($newArray);
                    //     $hotel_amount = $BillDetails['hotel_amount'];
                    // }
                    $amount = $amount + $bookingDetails->tip_amount + $hotel_amount + $per_pat_charges + $per_bag_charges;

                    $booking_transaction_submit = BookingTransaction::updateOrCreate([
                        'booking_id' => $booking_id,
                    ], [
                        'date_time_details' => date('Y-m-d H:i:s'),
                        'sub_total_before_discount' => $amount_for_commission,
                        'surge_amount' => $BillDetails['surge'],
                        'extra_charges' => $BillDetails['extracharge'],
                        'discount_amount' => $BillDetails['promo'],
                        'tax_amount' => $BillDetails['total_tax'],
                        'tip' => $bookingDetails->tip_amount ? $bookingDetails->tip_amount : 0,
                        'insurance_amount' => $BillDetails['insurnce_amount'],
                        'cancellation_charge_received' => $BillDetails['cancellation_amount_received'],
                        'cancellation_charge_applied' => '0.0',
                        'toll_amount' => $BillDetails['toolCharge'],
                        'booking_fee' => $BillDetails['booking_fee'],
                        'cash_payment' => ($booking->PaymentMethod->payment_method_type == 1) ? $total_payable : '0.0',
                        'online_payment' => ($booking->PaymentMethod->payment_method_type == 1) ? '0.0' : $total_payable,
                        'customer_paid_amount' => $total_payable,
                        'rounded_amount' => round_number(($total_payable - $amount)),
                        'merchant_id' => $booking->merchant_id
                    ]);

                    $amount = $merchant->FinalAmountCal($amount, $merchant_id);
                    $bookingDetails->total_amount = $amount;

                    // company earning will deduct from driver account
                    $commission_data = CommonController::NewCommission($booking_id, $booking_transaction_submit['sub_total_before_discount']);

                    $booking_transaction_submit->company_earning = $commission_data['company_cut'];
                    $booking_transaction_submit->driver_earning = $commission_data['driver_cut'];

                    // revenue of driver
                    // Driver Commission + tip + toll
                    $booking_transaction_submit->driver_total_payout_amount = $commission_data['driver_cut'] + $booking_transaction_submit->tip + $booking_transaction_submit->toll_amount + $per_bag_charges + $per_pat_charges;

                    // revenue of merchant
                    // Company Commission + Tax Amt - Discount + Insurance Amt
                    $booking_transaction_submit->company_gross_total = $commission_data['company_cut'] + $booking_transaction_submit->tax_amount - $booking_transaction_submit->discount_amount + $booking_transaction_submit->insurance_amount + $booking_transaction_submit['cancellation_charge_received'];

                    // $booking_transaction_submit->trip_outstanding_amount = $merchant->TripCalculation(($booking_transaction_submit->driver_total_payout_amount + $booking_transaction_submit->amount_deducted_from_driver_wallet - $booking_transaction_submit->cash_payment), $merchant_id);
                    $booking_transaction_submit->trip_outstanding_amount = 0;
                    $booking_transaction_submit->commission_type = $commission_data['commission_type'];
                    if ($booking->hotel_id != '') {
                        $booking_transaction_submit->hotel_earning = $commission_data['hotel_cut'];
                    }
                    // $booking_transaction_submit->amount_deducted_from_driver_wallet = ($commission_data['commission_type'] == 1) ? $commission_data['company_cut'] : $merchant->TripCalculation('0.0', $merchant_id);     //Commission Type: 1:Prepaid (==OR==) 2:Postpaid
                    //                    $booking_transaction_submit->amount_deducted_from_driver_wallet = $commission_data['company_cut'];     //Commission Type: 1:Prepaid (==OR==) 2:Postpaid
                    $booking_transaction_submit->amount_deducted_from_driver_wallet = $booking_transaction_submit->company_gross_total;

                    // Instant settlement For Stripe Connect / Paystack Split
                    if ($booking->payment_method_id == 2 && ($booking->Driver->sc_account_status == 'active' || (isset($booking->Driver->paystack_account_status) && $booking->Driver->paystack_account_status == 'active'))) {
                        $booking_transaction_submit->instant_settlement = 1;
                    } else {
                        $booking_transaction_submit->instant_settlement = 0;
                    }
                    $booking_transaction_submit->save();

                    //Referral Calculation
                    //                    $billDetails = self::checkReferral($booking, $billDetails, $amount);

                    $bookingDetails->bill_details = $billDetails;
                    $bookingDetails->save();
                    $payment = new Payment();
                    if ($amount > 0) {
                        $currency = $booking->CountryArea->Country->isoCode;
                        //                      $currency = $booking->CountryArea->Country->isoCode;
                        $array_param = array(
                            'booking_id' => $booking->id,
                            'payment_method_id' => $booking->payment_method_id,
                            'amount' => $amount,
                            'user_id' => $booking->user_id,
                            'card_id' => $booking->card_id,
                            'currency' => $currency,
                            'quantity' => 1,
                            'order_name' => $booking->merchant_booking_id,
                            'payment_option_id' => $booking->payment_option_id, // payment Option ID
                            'booking_transaction' => $booking_transaction_submit,
                            'driver_sc_account_id' => $booking->Driver->sc_account_id,
                            'merchant_id' => $booking->merchant_id,
                            'driver_paystack_account_id' => $booking->Driver->paystack_account_id
                        );
                        $payment->MakePayment($array_param);
                        //                        $payment->MakePayment($booking->id, $booking->payment_method_id, $amount, $booking->user_id, $booking->card_id, $currency, $booking_transaction_submit, $booking->Driver->sc_account_id);
                        $booking = $booking->fresh();
                    } else {
                        $payment->UpdateStatus(['booking_id' => $booking->id]);
                    }

                    //Referral Calculation
                    $ref = new ReferralController();
                    $arr_params = array(
                        "segment_id" => $booking->segment_id,
                        "driver_id" => $booking->driver_id,
                        "user_id" => $booking->user_id,
                        "booking_id" => $booking->id,
                        "user_paid_amount" => $amount,
                        "driver_paid_amount" => $amount,
                        "check_referral_at" => "OTHER"
                    );
                    $ref->checkReferral($arr_params);

                    RewardPoint::incrementUserTripCount(User::find($booking->user_id));
                    if ($booking->User->outstanding_amount) {
                        User::where([['id', '=', $booking->user_id]])->update(['outstanding_amount' => NULL]);
                    }
                    if ($booking->Merchant->Configuration->cashback_module == 1) :
                        $cashback = new CashbackController();
                        $cashback->ProvideCashback($booking->country_area_id, $booking->service_type_id, $booking->vehicle_type_id, $booking, $amount);
                    endif;
                    break;
                case "3":
                    $amount = "";
                    break;
            }
            if ($service_type_id == 5) {
                $poolRide = new PoolController();
                $poolRide->DropPool($booking, $request);
            }

            $distance_unit = $booking->CountryArea->Country->distance_unit;
            $div = $distance_unit == 1 ? 1000 : 1609;
            $distance = round($distance / $div, 2) . ' ' . ($distance_unit == 1 ? 'Km' : 'mi');

            $booking->booking_status = 1005;
            if (isset($appConfig->user_rating_enable) && $appConfig->user_rating_enable != 1 && $pricing_type != 3 && $booking->payment_status == 1) {
                $booking->booking_closure = 1;
            }
            $booking->travel_distance = $distance;
            $booking->travel_time = $timeFormat;
            $booking->travel_time_min = $rideTime;
            $booking->final_amount_paid = $amount;
            $booking->bill_details = $billDetails;
            $booking->save();

            //reward point
            $reward = new RewardPoint();
            $reward->GlobalReward($booking->merchant_id, 1, $booking->User, $booking->User->country_id, null, 'TRIP_EXPENSE', ['amount' => $amount]);
            $reward->GlobalReward($booking->merchant_id, 2, $booking->Driver, null, $booking->Driver->country_area_id, 'COMMISSION_PAID', ['commission_amount' => $booking_transaction_submit->driver_earning]);

            $free_busy = 1;
            if ($service_type_id == 5) {
                $runningBooking = Booking::where([['service_type_id', 5], ['driver_id', $booking->driver_id]])->whereIn('booking_status', [1001, 1002, 1003, 1004])->latest()->first();
                if (empty($runningBooking)) {
                    $free_busy = 2;
                }
            } else {
                $free_busy = 2;
            }

            $driver = $booking->Driver;
            $driver->total_trips = $booking->Driver->total_trips + 1;
            $driver->free_busy = $free_busy;
            $driver->save();

            $user = User::find($booking->user_id);
            $user->total_trips = $user->total_trips + 1;
            $user->save();
            $booking = $booking->fresh();

            // if($config->cash_payment_method_option == '1' && $booking->booking_status == '1005' && $user->cash_method_avaliable == 'NO' && $booking->payment_method_id != 1 && $booking->segment_id == 1){
            //     $usre->enable_ride_count_value = $user->enable_ride_count_value-1;
            //     $user->save();
            //     if($user->enable_ride_count_value == 0){
            //         $user->cash_method_avaliable = 'YES';
            //         $user->enable_ride_count_value = null;
            //         $user->disable_ride_count_value = null;
            //         $user->save();
            //     }
            // }

            // insert booking status history
            $this->saveBookingStatusHistory($request, $booking, $booking->id);


            $ride_earning_type = 2; // commission based
            if ($driver->Merchant->Configuration->subscription_package == 1 && $driver->pay_mode == 1) {
                $ride_earning_type = 1;
                $this->SubscriptionPackageExpiryCheck($driver, $booking->segment_id);
                // $this->SubscriptionPackageExpiryCheck($driver,$booking);
            }
            $booking_transaction_submit->ride_type_earning = $ride_earning_type; //  subscription based
            $booking_transaction_submit->save();

            if (in_array($pricing_type, [1, 2])) {
                //                $data = $bookingData->BookingNotification($booking);
                //                $message = $bookingData->LanguageData($booking->merchant_id, 34);
                //                Onesignal::UserPushMessage($booking->user_id, $data, $message, 1, $booking->merchant_id);

                $SmsConfiguration = SmsConfiguration::select('ride_end_enable', 'ride_end_msg')->where([['merchant_id', '=', $merchant_id]])->first();
                if (!empty($SmsConfiguration) && $SmsConfiguration->ride_end_enable == 3) {
                    $sms = new SmsController();
                    $phone = $booking->User->UserPhone;
                    $sms->SendSms($merchant_id, $phone, null, 'RIDE_END', $booking->User->email);
                }
            }
            if ($booking->payment_status == 1) {
                try {
                    event(new SendUserInvoiceMailEvent($booking));
                    //                    event(new SendUserInvoiceMailEvent($booking, 'invoice'));
                } catch (\Exception $e) {
                    p($e->getMessage());
                }
            } else {
                // commented by amba
                //                $paymentMethods = $booking->CountryArea->PaymentMethod->toArray();
                //                $is_cash_available = in_array(1, array_column($paymentMethods, 'id'));
                // If booking payment method is card and merchant not have any case payment method then.
                //Editing by @Amba, out standing will be creating when user click on paylater from receipt screen
                //                if (isset($config->user_outstanding_enable) && $config->user_outstanding_enable == 1 && $booking->payment_method_id == 2 && $is_cash_available != 1) {
                //                    // Later pay before next ride.
                //                    CommonController::AddUserRideOutstading($booking->user_id, $booking->driver_id, $booking->id, $amount);
                //                    $payment->UpdateStatus(['booking_id' => $booking->id]);
                //                    BookingDetail::where([['booking_id', '=', $booking->id]])->update(['pending_amount' => $amount]);
                //                }
            }
            //            return $this->successResponse(trans('api.message15'), $booking);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('merchant.activeride', $booking->Segment->slag)->withErrors($e->getMessage());
        }
        
        DB::commit();
        $string_file = $this->getStringFile($booking->merchant_id);
        return redirect()->route('merchant.activeride', $booking->Segment->slag)->withSuccess(trans("$string_file.ride_end"));
    
}

public function storeBookingDetails($request, $booking, $outstation_inside_City, $key, $socket_enable = false)
{
    try {
        //            $service_type_id = $booking->service_type_id;
        $service_type = $booking->ServiceType->type;
        $bookingDetails = BookingDetail::where('booking_id', $booking->id)->first();
        $string_file = $this->getStringFile($booking->merchant_id);
        // if (in_array($service_type, array(2, 4)) && $outstation_inside_City == false) {
        //     $start_meter_value = $bookingDetails->start_meter_value;
        //     $customMessages = [
        //         'gt' => trans_choice(trans("$string_file.end_meter_warning"), 3, ['value' => $start_meter_value]),
        //     ];
            // $validator = Validator::make($request->all(), [
            //     'send_meter_image' => 'required',
            //     'send_meter_value' => 'required|numeric|gt:' . $start_meter_value,
            // ], $customMessages);
        //     if ($validator->fails()) {
        //         $errors = $validator->messages()->all();
        //         throw new Exception($errors[0]);
        //     }
        // }
        if (!empty($request->send_meter_image)) {
            $send_meter_image = $this->uploadBase64Image('send_meter_image', 'send_meter_image', $booking->merchant_id);
            $bookingDetails->end_meter_image = $send_meter_image;
        }
        $bookingDetails->end_meter_value = $request->send_meter_value;
        if ($outstation_inside_City == true) {
            $bookingDetails->end_meter_value = 1200;
        }
        $endAddress = GoogleController::GoogleLocation($request->latitude, $request->longitude, $key, 'endRide', $string_file);
        $endAddress = $endAddress ? $endAddress : 'Address Not found';
        $endTimeStamp = strtotime('now');
        $bookingDetails->end_timestamp = $endTimeStamp;
        $bookingDetails->end_latitude = $request->latitude;
        $bookingDetails->end_longitude = $request->longitude;
        $bookingDetails->end_location = $endAddress;
        $bookingDetails->accuracy_at_end = $request->accuracy;
        $bookingDetails->save();

        // booking polyline
        if ($socket_enable == true) {
            $booking_coordinates = $booking->BookingCoordinate;
            if (empty($booking->BookingCoordinate->id)) {
                $booking_coordinates = new BookingCoordinate;
                $booking_coordinates->booking_id = $booking->id;
            }
            $booking_coordinates->booking_polyline = $request->booking_polyline;
            $booking_coordinates->save();
        }

        return $bookingDetails;
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

public function EstimateBillDetailsBreakup($billDetails_array, $estimate_bill)
    {
        $billDetails_array = json_decode($billDetails_array, true);
        $promocode_array = array_filter($billDetails_array, function ($e) {
            if ((isset($e['parameterType']) && $e['parameterType'] == "PROMO CODE") || ($e['parameter'] == "Promotion")) {
                return (($e['parameterType'] == "PROMO CODE") || ($e['parameter'] == "Promotion"));
            }
        });

        $subscription_array = array_filter($billDetails_array, function ($e) {
            if (isset($e['parameterType']) && $e['parameterType'] == "Subscription") {
                return (($e['parameterType'] == "Subscription"));
            }
        });

        $promocode_discount = '0.0';
        if (!empty($promocode_array)) :
            $promocode_discount = array_sum(Arr::pluck($promocode_array, 'amount'));
        endif;

        $user_subscription_discount = '0.0';
        if (!empty($subscription_array)) :
            $user_subscription_discount = array_sum(Arr::pluck($subscription_array, 'amount'));
        endif;

        $cancellation_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameter', $e) ? $e['parameter'] == "Cancellation fee" : []);
        });
        $cancellation_amount_received = '0.0';
        if (!empty($cancellation_array)) :
            $cancellation_amount_received = array_sum(Arr::pluck($cancellation_array, 'amount'));
        endif;

        $distance_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameterType', $e) ? $e['parameterType'] == "1" : []);
        });
        $distance_amount_received = '0.0';
        if (!empty($distance_charge_array)) :
            $distance_amount_received = array_sum(Arr::pluck($distance_charge_array, 'amount'));
        endif;

        $min_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameterType', $e) ? $e['parameterType'] == "8" : []);
        });
        $min_amount_received = '0.0';
        if (!empty($min_charge_array)) :
            $min_amount_received = array_sum(Arr::pluck($min_charge_array, 'amount'));
        endif;

        $hr_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameterType', $e) ? $e['parameterType'] == "2" : []);
        });
        $hr_amount_received = '0.0';
        if (!empty($hr_charge_array)) :
            $hr_amount_received = array_sum(Arr::pluck($hr_charge_array, 'amount'));
        endif;

        $amount_without_spl_discount_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('simply_amount', $e) ? $e['simply_amount'] == "amount_without_spl_discount" : []);
        });
        $amount_without_spl_discount = '0.0';
        if (!empty($amount_without_spl_discount_array)) :
            $amount_without_spl_discount = array_sum(Arr::pluck($amount_without_spl_discount_array, 'amount'));
        endif;

        $surge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameter', $e) ? $e['parameter'] == "Surge-Charge" : []);
        });
        $surge = '0.0';
        if (!empty($surge_array)) :
            $surge = array_sum(Arr::pluck($surge_array, 'amount'));
        endif;

        $extra_charge = '0.0';
        $extra_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('extra_charges_amount', $e) ? $e['extra_charges_amount'] == "Extra-Charges" : []);
        });
        if (!empty($extra_charge_array)) :
            $extra_charge = array_sum(Arr::pluck($extra_charge_array, 'amount'));
        endif;

        $amount_without_discount = ($amount_without_spl_discount + $surge + $extra_charge + $distance_amount_received + $hr_amount_received + $min_amount_received);

        $toll_charge = '0.0';
        $toll_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameter', $e) ? $e['parameter'] == "TollCharges" : []);
        });
        if (!empty($toll_charge_array)) :
            $toll_charge = array_sum(Arr::pluck($toll_charge_array, 'amount'));
        endif;

        $tax_charge = '0.0';
        $tax_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('type', $e) ? $e['type'] == "TAXES" : []);
        });
        if (!empty($tax_charge_array)) :
            $tax_charge = array_sum(Arr::pluck($tax_charge_array, 'amount'));
        endif;


        $insurance_amount_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameter', $e) ? $e['parameter'] == "Insurance" : []);
        });
        $insurance_amount = '0.0';
        if (!empty($insurance_amount_array)) :
            $insurance_amount = array_sum(Arr::pluck($insurance_amount_array, 'amount'));
        endif;

        $bookingFeeArray = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameterType', $e) ? $e['parameterType'] == "17" : []);
        });
        $bookingFee = '0.0';
        if (!empty($bookingFeeArray)) :
            $bookingFee = array_sum(Arr::pluck($bookingFeeArray, 'amount'));
        endif;

        $hotel_amount_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameter', $e) ? $e['parameter'] == "Hotel Charges" : []);
        });
        $hotel_amount = '0.0';
        if (!empty($hotel_amount_array)) :
            $hotel_amount = array_sum(Arr::pluck($hotel_amount_array, 'amount'));
        endif;

        /**@ayush (user subscription)*/
        $estimate_bill -= min($estimate_bill, $user_subscription_discount);

        return [
            'bill_details' => $billDetails_array,
            'amount' => $estimate_bill >= $promocode_discount ? $estimate_bill - $promocode_discount : $estimate_bill,
            'promo' => $promocode_discount,
            'cancellation_amount_received' => $cancellation_amount_received,
            'subTotalWithoutSpecial' => $amount_without_spl_discount,
            'subTotalWithoutDiscount' => $amount_without_discount,
            'toolCharge' => $toll_charge,
            'surge' => $surge,
            'extracharge' => $extra_charge,
            'insurnce_amount' => $insurance_amount,
            'total_tax' => $tax_charge,
            'booking_fee' => $bookingFee,
            'hotel_amount' => $hotel_amount,
            'subscription_discount'=>$user_subscription_discount,
        ];
    }


    //    public function SerachCompleteBooking(Request $request)
    //    {
    //        $query = $this->bookings(false, [1005]);
    //        if ($request->booking_id) {
    //            $query->where('merchant_booking_id', $request->booking_id);
    //        }
    ////        if ($request->date) {
    ////            $query->whereDate('created_at', '=', $request->date);
    ////        }
    //
    //        if ($request->date) {
    //            $query->whereDate('created_at', '>=', $request->date);
    //        }
    //        if ($request->date1) {
    //            $query->whereDate('created_at', '<=', $request->date1);
    //        }
    //
    //        if ($request->rider) {
    //            $keyword = $request->rider;
    //            $query->WhereHas('User', function ($q) use ($keyword) {
    //                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
    //            });
    //        }
    //        if ($request->driver) {
    //            $keyword = $request->driver;
    //            $query->WhereHas('Driver', function ($q) use ($keyword) {
    //                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
    //            });
    //        }
    //        $bookings = $query->paginate(25);
    //        $data = $request->all();
    //        return view('merchant.booking.complete', compact('bookings','data'));
    //    }

    public function FailedBooking(Request $request, $url_slug)
    {
        $checkPermission =  check_permission(1, "ride_management_$url_slug");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $bookings = $this->failsBookings(true, 'MERCHANT', $url_slug);
        $data = [];
        if ($url_slug == "DELIVERY") {
            $info_setting = InfoSetting::where('slug', 'FAILED_DELIVERY_RIDE')->first();
        } else {
            $info_setting = InfoSetting::where('slug', 'FAILED_TAXI_RIDE')->first();
        }
        return view('merchant.booking.fail', compact('bookings', 'data', 'url_slug', 'info_setting'));
    }

    public function SearchFailedBooking(Request $request, $url_slug)
    {
        $query = $this->failsBookings(false, 'MERCHANT', $url_slug);
        if ($request->booking_id) {
            $query->where('id', $request->booking_id);
        }
        if ($request->date) {
            $query->whereDate('created_at', '=', $request->date);
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        $bookings = $query->paginate(25);
        $data = $request->all();
        return view('merchant.booking.fail', compact('bookings', 'data', 'url_slug'));
    }

    public function CancelBookingAdmin(Request $request)
    {
        $request->validate([
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->whereIn('booking_status', [1001, 1012, 1002, 1003, 1004]);
                }),
            ],
            'cancel_reason_id' => 'required|integer',
        ]);
        DB::beginTransaction();
        try {
            $booking = Booking::find($request->booking_id);
            $booking->cancel_reason_id = $request->cancel_reason_id;
            $booking->additional_notes = $request->description;
            $booking->booking_status = 1008;
            $booking->save();
            $this->saveBookingStatusHistory($request, $booking, $booking->id);
            BookingRequestDriver::where("booking_id", $request->booking_id)->update(["request_status" => 4]);
            $bookingData = new BookingDataController;
            $bookingData->bookingNotificationForUser($booking, "CANCEL_RIDE");
            $driver_id = $booking->driver_id;
            if (!empty($driver_id)) {
                $Driver = Driver::select('id', 'free_busy')->where([['id', '=', $driver_id]])->first();
                $Driver->free_busy = 2;
                $Driver->save();
                $bookingData->SendNotificationToDrivers($booking);
            }
           else{
                $string_file = $this->getStringFile($booking->merchant_id);
                $notification_type = "BOOKING_ACCEPTED_BY_OTHER_DRIVER";
                $title = trans("$string_file.ride") . ' ' . trans("$string_file.expired");
                $message = trans("$string_file.ride") . ' ' . trans("$string_file.expired");
                $notification_data['notification_type'] = $notification_type;
                $notification_data['segment_data'] = [];
                $notification_data['segment_type'] = $booking->Segment->slag;
                $notification_data['segment_sub_group'] = $booking->Segment->sub_group_for_app; // its segment sub group for app
                    $notification_data['segment_group_id'] = $booking->Segment->segment_group_id; // its segment group
                    $driver_ids = BookingRequestDriver::where("booking_id", $booking->id)->get()->pluck("driver_id")->toArray();
                    $arr_param = ['driver_id' => $driver_ids, 'data' => $notification_data, 'message' => $message, 'merchant_id' => $booking->merchant_id, 'title' => $title, 'large_icon' => ""];
                    Onesignal::DriverPushMessage($arr_param);
           }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('merchant.activeride', $booking->Segment->slag)->withErrors($e->getMessage());
        }
        DB::commit();
        $string_file = $this->getStringFile($booking->merchant_id);
        return redirect()->route('merchant.activeride', $booking->Segment->slag)->withSuccess(trans("$string_file.ride_cancelled"));
    }

    public function CompleteBookingAdmin(Request $request)
    {
        $request->validate([
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1004);
                }),
            ]
        ]);
        DB::beginTransaction();
        try {
            $booking = Booking::find($request->booking_id);
            $bookingDetails = $booking->BookingDetail;
            $booking_data = new \App\Http\Controllers\Api\BookingController();
            $BillDetails = $booking_data->EstimateBillDetailsBreakup($booking->bill_details, $booking->estimate_bill);
            $amount = $booking->estimate_bill;
            $total_payable = $amount;
            Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->update(['pay_status' => 1]);
            $bookingFee = $BillDetails['booking_fee'];
            $amount_for_commission = $BillDetails['subTotalWithoutDiscount'] - $bookingFee;
            $billDetails = json_encode($BillDetails['bill_details'], true);

            $amount = $amount + $bookingDetails->tip_amount;

            $booking_transaction_submit = BookingTransaction::updateOrCreate([
                'booking_id' => $booking->id,
            ], [
                'date_time_details' => date('Y-m-d H:i:s'),
                'sub_total_before_discount' => $amount_for_commission,
                'surge_amount' => $BillDetails['surge'],
                'extra_charges' => $BillDetails['extracharge'],
                'discount_amount' => $BillDetails['promo'],
                'tax_amount' => $BillDetails['total_tax'],
                'tip' => $bookingDetails->tip_amount,
                'insurance_amount' => $BillDetails['insurnce_amount'],
                'cancellation_charge_received' => $BillDetails['cancellation_amount_received'],
                'cancellation_charge_applied' => '0.0',
                'toll_amount' => $BillDetails['toolCharge'],
                'booking_fee' => $BillDetails['booking_fee'],
                'cash_payment' => ($booking->PaymentMethod->payment_method_type == 1) ? $total_payable : '0.0',
                'online_payment' => ($booking->PaymentMethod->payment_method_type == 1) ? '0.0' : $total_payable,
                'customer_paid_amount' => $total_payable,
                'rounded_amount' => round_number(($total_payable - $amount))
            ]);

            $bookingDetails->total_amount = $amount;

            // company earning will deduct from driver account
            $commission_data = CommonController::NewCommission($booking->id, $booking_transaction_submit['sub_total_before_discount']);

            $booking_transaction_submit->company_earning = $commission_data['company_cut'];
            $booking_transaction_submit->driver_earning = $commission_data['driver_cut'];

            // revenue of driver
            // Driver Commission + Discount Amt + tip + toll
            $booking_transaction_submit->driver_total_payout_amount = $commission_data['driver_cut'] + $booking_transaction_submit->tip + $booking_transaction_submit->toll_amount + $booking_transaction_submit->discount_amount;

            // revenue of merchant
            // Company Commission + Tax Amt - Discount + Insurance Amt
            $booking_transaction_submit->company_gross_total = $commission_data['company_cut'] + $booking_transaction_submit->tax_amount - $booking_transaction_submit->discount_amount + $booking_transaction_submit->insurance_amount + $booking_transaction_submit['cancellation_charge_received'];

            // $booking_transaction_submit->trip_outstanding_amount = $merchant->TripCalculation(($booking_transaction_submit->driver_total_payout_amount + $booking_transaction_submit->amount_deducted_from_driver_wallet - $booking_transaction_submit->cash_payment), $merchant_id);
            $booking_transaction_submit->trip_outstanding_amount = 0;
            $booking_transaction_submit->commission_type = $commission_data['commission_type'];
            if ($booking->hotel_id != '') {
                $booking_transaction_submit->hotel_earning = $commission_data['hotel_cut'];
            }
            // $booking_transaction_submit->amount_deducted_from_driver_wallet = ($commission_data['commission_type'] == 1) ? $commission_data['company_cut'] : $merchant->TripCalculation('0.0', $merchant_id);     //Commission Type: 1:Prepaid (==OR==) 2:Postpaid
            //                    $booking_transaction_submit->amount_deducted_from_driver_wallet = $commission_data['company_cut'];     //Commission Type: 1:Prepaid (==OR==) 2:Postpaid
            $booking_transaction_submit->amount_deducted_from_driver_wallet = $booking_transaction_submit->company_gross_total;

            // Instant settlement For Stripe Connect
            if ($booking->payment_method_id == 2 && isset($booking->Driver->sc_account_status) && $booking->Driver->sc_account_status == 'active') {
                $booking_transaction_submit->instant_settlement = 1;
            } else {
                $booking_transaction_submit->instant_settlement = 1;
            }
            $booking_transaction_submit->save();

            //Referral Calculation
            //                    $billDetails = self::checkReferral($booking, $billDetails, $amount);

            $bookingDetails->bill_details = $billDetails;
            $bookingDetails->save();


            //            $booking->cancel_reason_id = $request->cancel_reason_id;
            //            $booking->additional_notes = $request->description;
            $booking->final_amount_paid = $booking->estimate_bill;
            $booking->payment_status = 1;
            $booking->booking_status = 1005;
            $booking->bill_details = $billDetails;
            $booking->save();
            //            BookingRequestDriver::where("booking_id",$request->booking_id)->update(["request_status" => 4]);
            $bookingData = new BookingDataController;
            $bookingData->bookingNotificationForUser($booking, "COMPLETE_RIDE");

            $user = User::find($booking->user_id);
            $user->total_trips = $user->total_trips + 1;
            $user->save();

            $driver_id = $booking->driver_id;
            if (!empty($driver_id)) {
                $Driver = Driver::select('id', 'free_busy', 'total_trips')->where([['id', '=', $driver_id]])->first();
                $Driver->total_trips = $Driver->total_trips + 1;
                $Driver->free_busy = 2;
                $Driver->save();
                $bookingData->SendNotificationToDrivers($booking);
            }
            $booking = $booking->fresh();
            $booking_data->updateRideAmountInDriverWallet($booking, $booking_transaction_submit, $booking->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('merchant.activeride', $booking->Segment->slag)->withErrors($e->getMessage());
        }
        DB::commit();
        $string_file = $this->getStringFile($booking->merchant_id);
        return redirect()->route('merchant.activeride', $booking->Segment->slag)->withSuccess(trans("$string_file.ride_completed"));
    }

    public function requestRides($id)
    {
        $booking = Booking::find($id);
        $time = BookingConfiguration::where([['merchant_id', $booking->merchant_id]])->first();
        $time = $time->driver_request_timeout * 100 / 3;
        return view('merchant.manual.loader', compact('time', 'id'));
    }

    public function checkBookingStatusWaiting(Request $request)
    {
        $booking = Booking::find($request->booking_id);
        if (!empty($booking)) {
            if ($booking->booking_status == 1002) {
                return redirect()->route('merchant.ride-requests', $request->booking_id);
            } else {
                $time = BookingConfiguration::where([['merchant_id', $booking->merchant_id]])->first();
                $time = ($time->driver_request_timeout * 1000) / 60;
                $id = $request->booking_id;
                $time_check = session('timer_no');
                $time_check = $time_check + 1;
                $request->session()->put('timer_no', $time_check);
                $request->session()->save();
                if ($time_check == 4) {
                    $request->session()->put('timer_no', 0);
                    $request->session()->save();
                    return redirect()->route('merchant.ride-requests', $request->booking_id)->with('success', 'NO Drivers Accepted');
                } else {
                    return view('merchant.manual.loader', compact('time', 'id'));
                }
            }
        }
    }

    public function DriverRequest($id)
    {
        $booking = Booking::with(['BookingRequestDriver' => function ($query) {
            $query->with('Driver');
        }])->with('OneSignalLog')->findOrFail($id);
        return view('merchant.booking.request', compact('booking'));
    }

    public function BookingDetails(Request $request, $id)
    {
        $booking = Booking::with('User')->findOrFail($id);
        $string_file = $this->getStringFile($booking->merchant_id);
        $arr_booking_status = $this->getBookingStatus($string_file);
        $booking->map_image = $booking->map_image . "&zoom=12&size=600x300";
        $final_bill_calculation = $booking->Merchant->BookingConfiguration->final_bill_calculation;
        if ($booking->family_member_id != '') {
            $booking->FamilyMember = FamilyMember::find($booking->family_member_id);
        }
        return view('merchant.booking.detail', compact('booking', 'arr_booking_status', 'final_bill_calculation'));
    }

    public function Invoice(Request $request, $id)
    {
        // $booking_data = new BookingDataController;
        $request->merge(['booking_id' => $id]);
        // $data = $booking_data->bookingReceiptForDriver($request);
        //        p($data);
        $merchant = get_merchant_id(false);
        $booking = Booking::with('User', 'BookingDetail')->findOrFail($id);
        $price = json_decode($booking->BookingDetail->bill_details);
        if(empty($price)){
            $price = json_decode($booking->bill_details);
        }
        //        p($price);
        $holder = HolderController::PriceDetailHolder($price, $booking->id,NULL,'user', NULL,null,NULL,$is_admin= true);
        //        array_shift($holder);
        $booking->holder = $holder;
        $final_bill_calculation = $merchant->BookingConfiguration->final_bill_calculation;
        $booking->map_image = $booking->map_image . "&zoom=12&key=" . $booking->Merchant->BookingConfiguration->google_key_admin . '&size=800x500';
        return view('merchant.booking.invoice', compact('booking', 'final_bill_calculation'));
    }

    public function bookingInvoiceSend(Request $request, $id)
    {
        $booking = Booking::where([['id', '=', $id]])->first();
        $string_file = $this->getStringFile(NULL, $booking->Merchant);
        // $template = EmailConfig::where('merchant_id', '=', $booking->Merchant->id)->first();
        // $temp = EmailTemplate::where('merchant_id', '=', $booking->Merchant->id)->where('template_name', '=', 'invoice')->first();
        // if (!empty($template) && !empty($temp)) {
            event(new SendUserInvoiceMailEvent($booking, 'invoice'));
            return redirect()->back()->withSuccess(trans("$string_file.email_sent_successfully"));
        // } else {
            // return redirect()->back()->withErrors(trans("$string_file.some_thing_went_wrong"));
        // }
    }
    
    public function bookingInvoiceSendBrevo(Request $request, $id)
    {
        $booking = Booking::where([['id', '=', $id]])->first();
        $string_file = $this->getStringFile(NULL, $booking->Merchant);

        $email_listener = new emailTemplateController();
        $email_listener->SendTaxiInvoiceEmail($booking);

        // $template = EmailConfig::where('merchant_id', '=', $booking->Merchant->id)->first();
        // $temp = EmailTemplate::where('merchant_id', '=', $booking->Merchant->id)->where('template_name', '=', 'invoice')->first();
        // if (!empty($template) && !empty($temp)) {
//        event(new SendUserInvoiceMailEvent($booking, 'invoice'));
        return redirect()->back()->withSuccess(trans("$string_file.email_sent_successfully"));
        // } else {
        // return redirect()->back()->withErrors(trans("$string_file.some_thing_went_wrong"));
        // }
    }

    public function ActiveBookingTrack($id){
        $booking = Booking::where([['id', '=', $id]])->first();
        return view('merchant.booking.track', compact('booking'));
    }

    // Taxi based services Earning
    public function taxiServicesEarning(Request $request)
    {
        // p($request->all());
        $checkPermission =  check_permission(1, 'view_reports_charts');
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $cancel_charges = $merchant->cancel_charges;
        $arr_area = $this->getMerchantCountryArea($this->getAreaList(false, true, [], null, null, false, false)->get());

        // get segment list
        $arr_segment = get_merchant_segment(true, $merchant_id, 1, 1);
        $arr_segment_list = $arr_segment;
        $arr_segment = count($arr_segment) > 1 ? $arr_segment : [];

        //get all parameters of price cards
        $arr_parameter = PricingParameter::select('id', 'parameterType')
            ->whereHas('PriceCardValue', function ($q) use ($merchant_id, $request) {
                $q->whereHas('PriceCard', function ($q) use ($merchant_id, $request) {
                    $q->where('merchant_id', $merchant_id);
                    if (!empty($request->segment_id)) {
                        $q->where('segment_id', $request->segment_id);
                    }
                });
                $q->distinct('pricing_parameter_id');
            })->get();
        $arr_parameter = $arr_parameter->map(function ($item) {
            return [
                'id' => $item->id,
                'parameterType' => $item->parameterType,
                'name' => $item->ParameterName,
            ];
        });
        //        p($arr_parameter);
        $request->merge(['search_route' => route('merchant.taxi-services-report'), 'request_from' => "COMPLETE", 'merchant_id' => $merchant_id, "arr_area" => $arr_area]);
        $arr_rides = $this->getBookings($request, $pagination = true, 'MERCHANT');
        // total fun  don't work after modification in collection
        $total_rides = $arr_rides->total();
        $arr_rides_details = $arr_rides;
        $arr_rides_details = $arr_rides_details->map(function ($item) use($merchant_id){
            $bill_details = json_decode($item->BookingDetail->bill_details);
            $invoice_data = HolderController::PriceDetailHolder($bill_details, null, NULL, 'driver', $item->segment_id,null,$merchant_id,$is_admin= true);
            //          p($invoice_data);
            $item->invoice = $invoice_data;
            return $item;
        });
     
        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }
        $query = BookingTransaction::select(DB::raw('SUM(customer_paid_amount) as ride_amount'), DB::raw('SUM(company_earning) as merchant_earning'), DB::raw('SUM(driver_earning) as driver_earning'), DB::raw('SUM(business_segment_earning) as store_earning'), DB::raw('SUM(booking_fee) as booking_fee'))
            ->with(['Booking' => function ($q) use ($request, $merchant_id) {
                $q->where([['booking_status', '=', 1005], ['merchant_id', '=', $merchant_id]]);

                if (!empty($request->booking_id) && $request->booking_id) {
                    $q->where('merchant_booking_id', $request->booking_id);
                }
                if (!empty($request->segment_id)) {
                    $q->where('segment_id', $request->segment_id);
                }
                if ($request->start) {
                    $start_date = date('Y-m-d', strtotime($request->start));
                    $end_date = date('Y-m-d ', strtotime($request->end));
                    $q->whereBetween(DB::raw('DATE(created_at)'), [$start_date, $end_date]);
                }
            }])
            ->whereHas('Booking', function ($q) use ($request, $merchant_id, $permission_area_ids) {
                $q->where([['booking_status', '=', 1005], ['merchant_id', '=', $merchant_id]]);
                if (!empty($request->booking_id) && $request->booking_id) {
                    $q->where('merchant_booking_id', $request->booking_id);
                }
                if (!empty($request->segment_id)) {
                    $q->where('segment_id', $request->segment_id);
                }
                if ($request->start) {
                    $start_date = date('Y-m-d', strtotime($request->start));
                    $end_date = date('Y-m-d ', strtotime($request->end));
                    $q->whereBetween(DB::raw('DATE(created_at)'), [$start_date, $end_date]);
                }
                if (!empty($permission_area_ids)) {
                    $q->whereIn("country_area_id", $permission_area_ids);
                }
            });
        //            ->whereIn('booking_id',$arr_booking_id);
        $earning_summary = $query->first();
        $request->merge(['request_from' => "ride_earning", "arr_segment" => $arr_segment]);
        $search_view = $this->orderSearchView($request);
        $arr_search = $request->all();

        $currency = "";
        $info_setting = InfoSetting::where('slug', 'TAXI_LOGISTICS_SERVICE_EARNING')->first();
        return view('merchant.report.taxi-services.earning', compact('arr_rides', 'search_view', 'arr_search', 'earning_summary', 'total_rides', 'currency', 'info_setting', 'arr_parameter', 'arr_rides_details', 'cancel_charges', 'arr_segment_list'));
    }

    public function rateBooking(Request $request)
    {
        DB::beginTransaction();
        try {
            $booking = Booking::find($request->rating_booking_id);
            $string_file = $this->getStringFile($booking->merchant_id);
            if ($booking->payment_status == 1) {
                $booking->booking_closure = 1;
                $booking->save();
            } else {
                throw new \Exception(trans("$string_file.payment_pending"));
            }
            BookingRating::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'driver_rating_points' => $request->rating,
                    'driver_comment' => $request->comment
                ]
            );
            $avg = BookingRating::whereHas('Booking', function ($q) use ($booking) {
                $q->where('user_id', $booking->user_id);
            })->avg('driver_rating_points');
            $user = $booking->User;
            $user->rating = round($avg, 2);
            $user->save();
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->back()->with('success', trans("$string_file.rating_thanks"));
    }

    // master Invoice
    public function masterInvoice(Request $request)
    {
        // p($request->all());
        $merchant = get_merchant_id(false);
        $final_bill_calculation = $merchant->BookingConfiguration->final_bill_calculation;
        $url_slug  = $request->url_slug;
        $checkPermission =  check_permission(1, "ride_management_$url_slug");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }

        $request->merge(['request_from' => "COMPLETE", 'url_slug' => $url_slug]);
        $bookings = $this->getBookings($request, false, 'MERCHANT');
        return view('merchant.booking.master-invoice', compact('bookings', 'final_bill_calculation'));
    }
    public function multipleInvoice(Request $request)
    {
        // $booking_data = new BookingDataController;
        // $request->merge(['booking_id' => $id]);
        // $data = $booking_data->bookingReceiptForDriver($request);
        //        p($data);

        $merchant = get_merchant_id(false);
        $booking_config = $merchant->BookingConfiguration;
        $final_bill_calculation = $booking_config->final_bill_calculation;
        $google_key_admin = $booking_config->google_key_admin;
        $url_slug  = $request->url_slug;
        $checkPermission =  check_permission(1, "ride_management_$url_slug");
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }

        $request->merge(['request_from' => "COMPLETE", 'url_slug' => $url_slug]);
        $bookings = $this->getBookings($request, false, 'MERCHANT');
        $bookings = $bookings->map(function ($booking) use ($google_key_admin) {

            $price = json_decode($booking->BookingDetail->bill_details);
            $holder = HolderController::PriceDetailHolder($price, $booking->id);
            $booking->holder = $holder;
            $booking->map_image = $booking->map_image . "&zoom=12&key=" . $google_key_admin . '&size=600x300';
            return $booking;
        });
        // p($bookings);
        return view('merchant.booking.multiple-invoice', compact('bookings', 'final_bill_calculation'));
    }
}
