<?php

namespace App\Traits;

use App\Models\BookingConfiguration;
use App\Models\BookingCoordinate;
use App\Models\CancelReason;
use App\Models\FailBooking;
use App\Models\Merchant;
use Auth;
use App\Models\Booking;
use Illuminate\Http\Request;
use DB;
use App\Models\Outstanding;
use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\PriceController;
use App\Http\Controllers\Helper\GoogleController;

trait BookingTrait
{
    public function ActiveBooking($pagination = true, $type = 'MERCHANT'){
        $merchant_id = '';
        $taxi_company_id = '';
        if($type == 'TAXICOMPANY'){
            $taxi_company = get_taxicompany();
            $merchant_id = $taxi_company->merchant_id;
            $taxi_company_id = $taxi_company->id;
        }else if($type == 'MERCHANT'){
            $merchant = Auth::user('merchant')->load('CountryArea');
            $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
        }
        $query = Booking::where('merchant_id', $merchant_id);
        if($type == 'TAXICOMPANY'){
            $query->where('taxi_company_id', $taxi_company_id);
        }
        $query = $query->whereIn('booking_status', [1001,1002,1003,1004,1012])->latest();
        $activeBooking = $pagination == true ? $query->paginate(25) : $query;
        return $activeBooking;
    }

    public function ActiveBookingNow($pagination = true, $type = 'MERCHANT',$url_slug = NULL)
    {
        if ($type == 'CORPORATE'){
            $corporate = Auth::user('corporate');
            $merchant = $corporate->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['corporate_id','=',$corporate->id], ['booking_type', '=', 1]];
        }else if($type == 'TAXICOMPANY'){
            $taxicompany = get_taxicompany();
            $merchant = $taxicompany->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['taxi_company_id','=',$taxicompany->id], ['booking_type', '=', 1]];
        }else if ($type == 'HOTEL'){
            $hotel = get_hotel();
            $merchant = $hotel->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['hotel_id','=',$hotel->id], ['booking_type', '=', 2]];
        }else{
            $merchant = Auth::user('merchant')->load('CountryArea');
            $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
            $where = [['merchant_id', '=', $merchant_id], ['taxi_company_id','=',NULL], ['booking_type', '=', 1]];
        }
        $query = Booking::where($where)->whereIn('booking_status', [1001, 1002, 1003, 1004]);
        if(!empty($url_slug)){
            $query->whereHas('Segment',function ($q) use($url_slug){
                $q->where('slag',$url_slug);
            });
        }
        $query = $query->latest();
        if (!empty($merchant->CountryArea->toArray())) {
            $area_ids = array_pluck($merchant->CountryArea, 'id');
            $query->whereIn('country_area_id', $area_ids);
        }
        $activeBooking = $pagination == true ? $query->paginate(25) : $query;
        return $activeBooking;
    }

    public function ActiveBookingLater($pagination = true,$type = 'MERCHANT',$url_slug = NULL)
    {
        if ($type == 'CORPORATE'){
            $corporate = Auth::user('corporate');
            $merchant = $corporate->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['corporate_id','=',$corporate->id], ['booking_type', '=', 2]];
        }else if ($type == 'TAXICOMPANY'){
            $taxicompany = get_taxicompany();
            $merchant = $taxicompany->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['taxi_company_id','=',$taxicompany->id], ['booking_type', '=', 2]];
        }else if ($type == 'HOTEL'){
            $hotel = get_hotel();
            $merchant = $hotel->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['hotel_id','=',$hotel->id], ['booking_type', '=', 2]];
        }else{
            $merchant = Auth::user('merchant')->load('CountryArea');
            $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
            $where = [['merchant_id', '=', $merchant_id], ['booking_type', '=', 2]];
        }
        $query = Booking::where($where)->whereIn('booking_status', [1001, 1012, 1002, 1003, 1004]);
        if(!empty($url_slug)){
            $query->whereHas('Segment',function ($q) use($url_slug){
                $q->where('slag',$url_slug);
            });
        }
        $query = $query->latest();
        if (!empty($merchant->CountryArea->toArray())) {
            $area_ids = array_pluck($merchant->CountryArea, 'id');
            $query->whereIn('country_area_id', $area_ids);
        }
        $activeBooking = $pagination == true ? $query->paginate(25) : $query;
        return $activeBooking;
    }

    public function SearchForActiveRide($url_slug)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $query = $this->ActiveBookingNow(false,'MERCHANT',$url_slug);
        if (request()->booking_id) {
            $query->where('merchant_booking_id', request()->booking_id);
        }
        if (request()->booking_status) {
            $query->where('booking_status', request()->booking_status);
        }
        if (request()->rider) {
            $keyword = request()->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if (request()->driver) {
            $keyword = request()->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        $bookings = $query->paginate(25);
        $later_bookings = $this->ActiveBookingLater(true,'MERCHANT',$url_slug);
        $cancelreasons = $this->CancelReason();
        $bookingConfig = BookingConfiguration::select('ride_otp')->where([['merchant_id', '=', $merchant_id]])->first();
        return view('merchant.booking.active', compact('bookingConfig', 'bookings', 'cancelreasons', 'later_bookings','url_slug'));
    }

    public function SearchForActiveLaterRide($url_slug)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $query = $this->ActiveBookingLater(false,'MERCHANT',$url_slug);
        if (request()->booking_id) {
            $query->where('merchant_booking_id', request()->booking_id);
        }
        if (request()->rider) {
            $keyword = request()->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if (request()->driver) {
            $keyword = request()->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        $later_bookings = $query->paginate(25);
        $bookings = $this->ActiveBookingNow(true,'MERCHANT',$url_slug);
        $cancelreasons = $this->CancelReason();
        $bookingConfig = BookingConfiguration::select('ride_otp')->where([['merchant_id', '=', $merchant_id]])->first();
        return view('merchant.booking.active', compact('bookingConfig', 'bookings', 'cancelreasons', 'later_bookings','url_slug'));
    }

    public function CancelReason($type = 'MERCHANT')
    {
        if ($type == 'CORPORATE'){
            $corporate = Auth::user('corporate');
            $merchant_id = $corporate->merchant_id;
        }else if ($type == 'TAXICOMPANY'){
            $taxicompany = get_taxicompany();
            $merchant_id = $taxicompany->merchant_id;
        }else{
            $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        }
        $cancelreasons = CancelReason::where([['merchant_id', '=', $merchant_id], ['reason_type', '=', 3]])->get();
        return $cancelreasons;
    }

    public function bookings($pagination = true, $booking_status = [],$type = 'MERCHANT',$url_slug = NULL)
    {
        if ($type == 'CORPORATE'){
            $corporate = Auth::user('corporate');
            $merchant = $corporate->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['corporate_id','=',$corporate->id]];
        }else if ($type == 'TAXICOMPANY'){
            $taxicompany = get_taxicompany();
            $merchant = $taxicompany->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['taxi_company_id','=',$taxicompany->id]];
        }else if ($type == 'HOTEL'){
            $hotel = get_hotel();
            $merchant = $hotel->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['hotel_id','=',$hotel->id]];
        }else{
            $merchant = Auth::user('merchant')->load('CountryArea');
            $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
            $where = [['merchant_id', '=', $merchant_id],['taxi_company_id', '=', NULL]];
        }
        $query = Booking::where($where)->whereIn('booking_status', $booking_status);
        if(!empty($url_slug)){
            $query->whereHas('Segment',function ($q) use($url_slug){
                $q->where('slag',$url_slug);
            });
        }
        $query = $query->latest();
        if (!empty($merchant->CountryArea->toArray())) {
            $area_ids = array_pluck($merchant->CountryArea, 'id');
            $query->whereIn('country_area_id', $area_ids);
        }
        $booking = $pagination == true ? $query->latest()->paginate(25) : $query;
        return $booking;
    }

    public function failsBookings($pagination = true,$type = 'MERCHANT')
    {
        if ($type == 'CORPORATE'){
            $corporate = Auth::user('corporate');
            $merchant = $corporate->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['corporate_id','=',$corporate->id]];
        }else{
            $merchant = Auth::user('merchant')->load('CountryArea');
            $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
            $where = [['merchant_id', '=', $merchant_id]];
        }
        $query = FailBooking::where($where)->latest();
        if (!empty($merchant->CountryArea->toArray())) {
            $area_ids = array_pluck($merchant->CountryArea, 'id');
            $query->whereIn('country_area_id', $area_ids);
        }
        $booking = $pagination == true ? $query->paginate(25) : $query;
        return $booking;
    }

    public function autoCancelRide()
    {
        $bookings = $this->bookings(false, [1016]);
        return $bookings;
    }

    public function getAllTransaction($pagination = true,$type = 'MERCHANT')
    {
        if ($type == 'CORPORATE'){
            $corporate = Auth::user('corporate');
            $merchant = $corporate->Merchant;
            $where = [['merchant_id', '=', $merchant->id],['corporate_id','=',$corporate->id],['booking_closure', '=', 1]];
        }else{
            $merchant = Auth::user('merchant')->load('CountryArea');
            $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
            $where = [['merchant_id', '=', $merchant_id],['taxi_company_id','=',NULL],['booking_closure', '=', 1]];
        }
        $query = Booking::where($where)->latest();
        if (!empty($merchant->CountryArea->toArray())) {
            $area_ids = array_pluck($merchant->CountryArea, 'id');
            $query->whereIn('country_area_id', $area_ids);
        }
        $transactions = $pagination == true ? $query->paginate(25) : $query;
        return $transactions;
    }

    public function allBookings($pagination = true)
    {
        $merchant = Auth::user('merchant')->load('CountryArea');
        $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
        $query = Booking::where([['merchant_id', '=', $merchant_id]])->latest();
        if (!empty($merchant->CountryArea->toArray())) {
            $area_ids = array_pluck($merchant->CountryArea, 'id');
            $query->whereIn('country_area_id', $area_ids);
        }
        $result = $pagination == true ? $query->paginate(25) : $query;
        return $result;
    }

    public function updateBookingCoordinates($coordinate,$booking_id)
    {
        $driverlocation = array('latitude' => $coordinate['latitude'], 'longitude' => $coordinate['longitude'], 'accuracy' => $coordinate['accuracy'], 'timestamp' => time());
        $locationArray = [];
        $getLocation = BookingCoordinate::where([['booking_id', '=', $booking_id]])->first();
        if (!empty($getLocation)) {
            $locationArray = json_decode($getLocation->coordinates, true);
        }
        array_push($locationArray, $driverlocation);
        $locationArray = json_encode($locationArray);
        BookingCoordinate::updateOrCreate(
            ['booking_id' => $booking_id],
            [
                'coordinates' => $locationArray,
            ]
        );
    }

    public function makeBookingExpire($all_bookings){
        foreach ($all_bookings as $booking) {
            if (!empty($booking->driver_id)) {
                $timezone = $booking->Driver->CountryArea->timezone;
            } else {
                if (isset($booking->User->Country->CountryArea[0]->timezone)) {
                    $timezone = $booking->User->Country->CountryArea[0]->timezone;
                } else {
                    $timezone = 'Asia/Kolkata';
                }
            }
//            date_default_timezone_set($timezone);
            $date = date('Y-m-d');
            $currenct_time = date('H:i');
            $later_booking_date = set_date($booking->later_booking_date);
            if ($booking->booking_type == 1 && $date > $booking->updated_at) {
                Booking::where('id', '=', $booking->id)->update(['booking_status' => 1016]);
            }
            if ($booking->booking_type == 2 && ($date > $later_booking_date || ($date == $later_booking_date && $currenct_time > $booking->later_booking_time))) {
                Booking::where('id', '=', $booking->id)->update(['booking_status' => 1016]);
            }
        }
    }

    function saveBookingStatusHistory($request,$booking_obj,$booking_id = NULL, $map_image_recalculation = NULL)
    {
        if(!empty($booking_obj->id))
        {
          $booking = $booking_obj;
            if(isset($booking_obj->trip_way))
            {
                unset($booking_obj->trip_way);
            }
        }
        else
        {
            $booking = Booking::select('id','booking_status','booking_status_history')->Find($booking_id);
        }
        if(!empty($booking->id))
        {
            $new_status = [
                    'booking_status'=>$booking->booking_status,
                    'booking_timestamp'=>time(),
                    'latitude'=>$request->latitude,
                    'longitude'=>$request->longitude,
                ];
            if(in_array($booking->booking_status,[1001]))
            {
                $booking->booking_status_history = json_encode([$new_status]);
                $booking->save();
            }
            else
            {
                $status_history = !empty(json_decode($booking->booking_status_history, true)) ? json_decode($booking->booking_status_history, true) : [];
                array_push($status_history, $new_status);
                $booking->booking_status_history = json_encode($status_history);
                $booking->save();
            }
            if($map_image_recalculation == 1){
                $key = $booking->Merchant->BookingConfiguration->google_key;
                $booking_details = $booking->BookingDetail;
//                $startpoint = $booking_details->start_latitude . ',' . $booking_details->start_longitude;
//                $finishpoint = $booking_details->end_latitude . ',' . $booking_details->end_longitude;
//                $polyline = GoogleController::computeRoutesForPolyline($booking->Merchant->id, $booking_details->start_location, $booking_details->end_location, $key);
//                $image = "https://maps.googleapis.com/maps/api/staticmap?center=&maptype=roadmap&path=color:0x000000%7Cweight:10%7Cenc:" . urlencode($polyline) . "&markers=color:green%7Clabel:P%7C" . $startpoint . "&markers=color:red%7Clabel:D%7C" . $finishpoint;
                $drop = [['drop_latitude'=>$booking_details->end_latitude, "drop_longitude"=>$booking_details->end_longitude]];
                $googleArray = GoogleController::GoogleStaticImageAndDistance($booking_details->start_latitude, $booking_details->start_longitude, $drop, $key);
                $booking->map_image = $googleArray['image'];

                $booking->save();
            }
        }
    }
    public function getNotificationLargeIconForBooking($booking)
    {
        $large_icon = "";
        if (!empty($booking->Segment))
        {
            $merchant_id = $booking->merchant_id;
            $item = $booking->Segment;
            $large_icon = isset($item->Merchant[0]['pivot']->segment_icon) && !empty($item->Merchant[0]['pivot']->segment_icon) ? get_image($item->Merchant[0]['pivot']->segment_icon, 'segment', $merchant_id, true) :
                get_image($item->icon, 'segment_super_admin', NULL, false);
        }
        return $large_icon;
    }

    public function getBookingStatus($string_file)
    {
        return array(
        '1001' => trans("$string_file.new_ride"),//ride booked
        '1002' => trans("$string_file.accepted_by_driver"),//
        '1003' => trans("$string_file.arrived_at_pickup"),
        '1004' => trans("$string_file.ride_started"),
        '1005' => trans("$string_file.ride_completed"),
        '1006' => trans("$string_file.ride_cancelled_by_user"),
        '1007' => trans("$string_file.ride_cancelled_by_driver"),
        '1008' => trans("$string_file.ride_cancelled_by_admin"),
        '1012' => trans("$string_file.partial_accepted"),
        '1016' => trans("$string_file.auto_cancelled"),
        '1018' => trans("$string_file.expired_by_cron"),//'Expired by cron (rider later case)',
    );
    }

    public function getBookings($request,$pagination = true, $type = 'MERCHANT'){
        $permission_area_ids = [];
        if(Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != ""){
            $permission_area_ids = explode(",",Auth::user()->role_areas);
        }
        $url_slug = isset($request->url_slug) ? $request->url_slug : "";
        $merchant_id = $request->merchant_id;
        $taxi_company_id = '';
        if($type == 'TAXICOMPANY'){
            $taxi_company = get_taxicompany();
            $merchant_id = $taxi_company->merchant_id;
            $taxi_company_id = $taxi_company->id;
        }else if($type == 'MERCHANT' && empty($merchant_id)){
            $merchant_id = get_merchant_id();
        }
        $query = Booking::where('merchant_id', $merchant_id)
        ->orderBy('created_at','DESC');
        if(!empty($url_slug)){
            $query->whereHas('Segment',function ($q) use($url_slug){
                $q->where('slag',$url_slug);
            });
        }
        if($type == 'TAXICOMPANY'){
            $query->where('taxi_company_id', $taxi_company_id);
        }
        // booking based on status
        if(!empty($request->request_from) && $request->request_from == "ACTIVE")
        {
            $query->where(function($q){
                $q->whereIn('booking_status', [1001,1002,1003,1004,1012]);
                // $q->orWhere([['booking_status','=',1005],['booking_closure','=',NULL]]);
            });
        }
        if(!empty($request->request_from) && $request->request_from == "CANCEL")
        {
        $query = $query->whereIn('booking_status', [1006, 1007, 1008]);
        }
        if(!empty($request->request_from) && $request->request_from == "AUTO_CANCEL")
        {
        $query = $query->whereIn('booking_status', [1016,1018]);
        }
        elseif(!empty($request->request_from) && $request->request_from == "COMPLETE")
        {
            $query = $query->where(function($q) use($request){
                // $q->where([['booking_status','=',1005],['booking_closure','=',1]]);
                $q->where([['booking_status','=',1005]]);
            });
        }
        // search params and conditions
        if (!empty($request->booking_type) && $request->booking_type) {
            $query->where('booking_type', $request->booking_type);
        }
        if (!empty($request->booking_status) && $request->booking_status) {
            $query->where('booking_status', $request->booking_status);
        }
        if (!empty($request->booking_id) && $request->booking_id) {
            $query->where('merchant_booking_id', $request->booking_id);
        }
        if (!empty($request->segment_id)) {
            $query->where('segment_id', $request->segment_id);
        }
        if (!empty($request->driver_id)) {
            $query->where('driver_id', $request->driver_id);
        }
        if (!empty($request->area_id)) {
            $query->where('country_area_id', $request->area_id);
        }
        if (!empty($request->rider) && $request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")
                    ->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if (!empty($request->driver) && $request->driver) {
            $keyword = $request->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->where(\DB::raw('concat(`first_name`," ", `last_name`)'), 'LIKE', "%$keyword%")
                    ->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        if ($request->start) {
            $start_date = date('Y-m-d',strtotime($request->start));
            $end_date = date('Y-m-d ',strtotime($request->end));
            $query->whereBetween(DB::raw('DATE(created_at)'), [$start_date,$end_date]);
        }
        if(!empty($permission_area_ids)){
            $query->whereIn("country_area_id",$permission_area_ids);
        }
        // p($query->toSql());
        $bookings = $pagination == true ? $query->paginate(25) : $query->get();

        return $bookings;
    }
    
    //Delivery and Taxi Final and estimate bill calculation 
    public function estimateIsFinalBill($request,$appConfig,$booking,$bookingDetails,$bookingData,$merchant,$merchant_id,$string_file){
        $bill_details_data = [];
        $tip_And_toll = self::storeTipAndTollCharge($appConfig, $request, $booking, $bookingDetails);
        $booking->refresh();
        $bookingDetails->refresh();
        $tip_amount = $tip_And_toll['tip_amount'];
        if (empty($booking->bill_details)) {
            throw new Exception("Bill details not found.");
        }
        $BillDetails = $this->EstimateBillDetailsBreakup($booking->bill_details, $booking->estimate_bill);
                        // $amount = $booking->estimate_bill;
        if ($booking->Merchant->BookingConfiguration->corporate_price_card == 1) {
            $booking_data_helper = new BookingDataController();
            $BillDetails = $booking_data_helper->getCorporatePriceBilling($BillDetails, $booking->price_card_id, $booking->corporate_id);
        }
        $amount = $BillDetails['amount'];
        $hotel_amount = 0.0;

        if (!empty($booking->price_for_ride) && $booking->price_for_ride != 1) {
            $BillDetails = $bookingData->checkPriceForRide($booking, $BillDetails);
            $booking->bill_details = json_encode($BillDetails['bill_details'], true);
            $booking->save();
            $hotel_amount = 0;  // in case of maximum fare and fix fare
        }

        $total_payable = $merchant->FinalAmountCal(($amount + $bookingDetails->tip_amount), $merchant_id);
        Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->update(['pay_status' => 1]);
        $bookingFee = $BillDetails['booking_fee'];
        $amount_for_commission = $BillDetails['subTotalWithoutDiscount'] - $bookingFee;
        $billDetails = json_encode($BillDetails['bill_details'], true);
        return ['totalPayable'=> $total_payable,'bookingFee'=> $bookingFee,'amountForComission'=>$amount_for_commission,'bill_details'=>$billDetails,'hotelAmount'=>$hotel_amount,'amount'=>$amount,'BillDetails'=> $BillDetails];
    }
    
    //Actual Final Bill Calculation
                
    public function actualFinalBill($appConfig,$request,$booking,$bookingDetails,$merchant_id,$distance,$rideTime,$from,$to,$coordinates,$bookingData,$merchant,$string_file){
        $price_card_id = $booking->price_card_id;
        $booking_id = $request->booking_id;
        $outstanding_amount = Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->sum('amount');
        // creating time zone issues
        $per_pat_charges = 0;
        $per_bag_charges = 0;
        //this convertTimeToUSERzone function added to fix for night and peak charges
        $bookingTime = convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone, $merchant_id, $booking->Merchant, 3);
        $bookingDate = convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone, $merchant_id, $booking->Merchant, 2);
        $finalBill = new PriceController();
        $BillDetails = $finalBill->BillAmount([
            'price_card_id' => $price_card_id,
            'merchant_id' => $merchant_id,
            'distance' => $distance,
            'time' => $rideTime,
            'booking_id' => $booking_id,
            'user_id' => $booking->user_id,
            'driver_id' => $booking->driver_id,
            'booking_time' => $booking->booking_type == 2 ? $booking->later_booking_time : $bookingTime,
            'booking_date' => $booking->booking_type == 2 ? $booking->later_booking_date : $bookingDate,
            'waitTime' => $bookingDetails->wait_time,
            'dead_milage_distance' => $bookingDetails->dead_milage_distance,
            'outstanding_amount' => $outstanding_amount,
            'number_of_rider' => $booking->number_of_rider,
            'from' => $from,
            'to' => $to,
            'coordinates' => $coordinates,
            'units' => $booking->CountryArea->Country['distance_unit'],
            'manual_toll_charge' => isset($request->manual_toll_charge) ? $request->manual_toll_charge : '',
            'hotel_id' => !empty($booking->hotel_id) ? $booking->hotel_id : NULL,
            'additional_movers' => !empty($booking->additional_movers) ? $booking->additional_movers : NULL,
        ]);
        //different corporate booking price
        if ($booking->Merchant->BookingConfiguration->corporate_price_card == 1) {
            $booking_data_helper = new BookingDataController();
            $BillDetails = $booking_data_helper->getCorporatePriceBilling($BillDetails, $booking->price_card_id, $booking->corporate_id);
        }
        // dd($booking->Merchant->BookingConfiguration);
        if ($booking->Merchant->BookingConfiguration->driver_booking_amount == 1) {
            $amount = $request->ride_amount;
            $amountCommission = $request->ride_amount;
        } else {
            $amount = $BillDetails['amount'];
            $amountCommission = $BillDetails['subTotalWithoutDiscount'];
        }
    
        Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->update(['pay_status' => 1]);
        // amount => Sub total without discount + extra charges(night time /peck time) + surge charge - promo + taxs + insurance + toll charges +  cancellation_amount_received
        if (!empty($booking->price_for_ride)) {
            $BillDetails = $bookingData->checkPriceForRide($booking, $BillDetails);
            $amount = $BillDetails['amount'];
            $amountCommission = $BillDetails['subTotalWithoutDiscount'];
        }
        $total_payable = $merchant->FinalAmountCal(($amount + $bookingDetails->tip_amount), $merchant_id);
    
        $bookingFee = $BillDetails['booking_fee'];
        $amount_for_commission = $amountCommission - $bookingFee;
    
        $newArray = $BillDetails['bill_details'];
        $bookingDetails->promo_discount = $BillDetails['promo'];
        if ($appConfig->tip_status == 1) {
            $tip_parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => "Tip", 'parameterType' => "tip_amount", 'amount' => $bookingDetails->tip_amount, 'type' => "CREDIT", 'code' => "");
            array_push($newArray, $tip_parameter);
        }
    
        // No Of Bag Charges
        if (isset($booking->Merchant->Configuration->chargable_no_of_bags) && $booking->Merchant->Configuration->chargable_no_of_bags == 1) {
            if ($booking->no_of_bags > 0 && $booking->PriceCard->per_bag_charges > 0) {
                $per_bag_charges = $booking->PriceCard->per_bag_charges * $booking->no_of_bags;
                $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans("$string_file.bag_charges"), 'amount' => $per_bag_charges, 'type' => "CREDIT", 'code' => "");
                array_push($newArray, $parameter);
            }
        }
        // No Of Pat Charges
        if (isset($booking->Merchant->Configuration->chargable_no_of_pats) && $booking->Merchant->Configuration->chargable_no_of_pats == 1) {
            if ($booking->no_of_pats > 0 && $booking->PriceCard->per_pat_charges > 0) {
                $per_pat_charges = $booking->PriceCard->per_pat_charges * $booking->no_of_pats;
                $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans("$string_file.pat_charges"), 'amount' => $per_pat_charges, 'type' => "CREDIT", 'code' => "");
                array_push($newArray, $parameter);
            }
        }
    
        $billDetails = json_encode($newArray);
        // $hotel_amount = $BillDetails['hotel_amount'];
        $hotel_amount = 0.0;
        return ['totalPayable'=> $total_payable,'bookingFee'=> $bookingFee,'amountForComission'=>$amount_for_commission,'bill_details'=>$billDetails,'hotelAmount'=>$hotel_amount,'amount'=>$amount,'BillDetails'=> $BillDetails,'per_pat_charges'=> $per_pat_charges,'per_bag_charges'=>$per_bag_charges];
        
    }    
    
    
    //Delivery and Taxi Final and estimate bill calculation for admin
    public function estimateIsFinalBillForAdmin($request,$appConfig,$booking,$bookingDetails,$bookingData,$merchant,$merchant_id,$string_file){
        $bill_details_data = [];
        $booking->fresh();
        if (empty($booking->bill_details)) {
            throw new Exception("Bill details not found.");
        }
        $BillDetails = $this->EstimateBillDetailsBreakup($booking->bill_details, $booking->estimate_bill);
                        // $amount = $booking->estimate_bill;
        if ($booking->Merchant->BookingConfiguration->corporate_price_card == 1) {
            $booking_data_helper = new BookingDataController();
            $BillDetails = $booking_data_helper->getCorporatePriceBilling($BillDetails, $booking->price_card_id, $booking->corporate_id);
        }
        $amount = $BillDetails['amount'];
        $hotel_amount = $BillDetails['hotel_amount'];

        if (!empty($booking->price_for_ride) && $booking->price_for_ride != 1) {
            $BillDetails = $bookingData->checkPriceForRide($booking, $BillDetails);
            $booking->bill_details = json_encode($BillDetails['bill_details'], true);
            $booking->save();
            $hotel_amount = 0;  // in case of maximum fare and fix fare
        }

        $total_payable = $merchant->FinalAmountCal(($amount + $bookingDetails->tip_amount), $merchant_id);
        Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->update(['pay_status' => 1]);
        $bookingFee = $BillDetails['booking_fee'];
        $amount_for_commission = $BillDetails['subTotalWithoutDiscount'] - $bookingFee;
        $billDetails = json_encode($BillDetails['bill_details'], true);
        
        return ['totalPayable'=> $total_payable,'bookingFee'=> $bookingFee,'amountForComission'=>$amount_for_commission,'bill_details'=>$billDetails,'amount'=>$amount,'BillDetails'=> $BillDetails];
    }

}
