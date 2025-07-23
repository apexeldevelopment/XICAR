<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 6/11/23
 * Time: 5:30 PM
 */

namespace App\Http\Controllers\Api\BusBooking;

use App\Http\Resources\BusBookingResource;
use App\Http\Resources\DriverBusBookingResource;
use App\Http\Resources\DriverBusBookingStopResource;
use App\Models\BusBooking;
use App\Models\BusBookingMaster;
use App\Traits\AreaTrait;
use App\Traits\BannerTrait;
use App\Traits\BusBookingTrait;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use DB;

class DriverBusBookingController extends Controller
{
    use AreaTrait, ApiResponseTrait, MerchantTrait, BannerTrait, BusBookingTrait;

    public function homeScreen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            // call area trait to get id of area
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $request->merge(['calling_from' => "home_screen", "area" => $driver->country_area_id]);
            $data = $this->getHomeScreen($request, $string_file);
            $return_data['response_data'] = $data['data'];
            $return_data["is_active_booking"] = BusBookingMaster::where([["driver_id", $driver->id], ['status', 2]])->count() > 0;
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    function getHomeScreen($request, $string_file)
    {
        $driver = $request->user('api-driver');
        $merchant_id = $request->merchant_id;
        $segment_id = $request->segment_id;
        $calling_from = $request->calling_from;
        $response = [];

        $config = $driver->Merchant->Configuration;

        if ($calling_from == "home_screen") {
            $holder_item = ["UPCOMING_BOOKING", "BOOKING_STATS", "BANNER", "BOOKING"];
            if($driver->Merchant->advertisement_module != 1){
                if (($key = array_search("BANNER", $holder_item)) !== false) {
                    unset($holder_item[$key]);
                }
            }
        }

        //  Get upcoming bookings
        if (in_array("UPCOMING_BOOKING", $holder_item)) {
            $booking_cell['cell_title'] = "UPCOMING_BOOKING_CELL";
            $booking_cell['cell_title_text'] = "Upcoming Bookings";
            $bus_bookings = BusBookingMaster::where("driver_id", $driver->id)->whereIn("status", [1,2])->latest()->take(3)->get();
//            if(!empty($bus_bookings) && count($bus_bookings) > 0){
            $booking_data = [];
            $bus_booking_status = Config::get("custom.bus_booking_status");
            foreach($bus_bookings as $booking){
                $start_date_time = $booking->booking_date." ".$booking->ServiceTimeSlotDetail->slot_time_text;

                $minutes_to_add = 0;
                foreach($booking->BusRoute->StopPoints->slice(1) as $point){
                    $minutes_to_add += $point->pivot->time;
                }
                $minutes_to_add += $booking->BusRoute->StopPoints->last()->pivot->time;

                $ent_date_time = new \DateTime($start_date_time);
                $ent_date_time->add(new \DateInterval('PT' . $minutes_to_add . 'M'));

                $hours = floor($minutes_to_add / 60); // Get the number of whole hours
                $minutes = $minutes_to_add % 60; // Get the remainder of the hours

                $duration = sprintf ("%02d:%02d", $hours, $minutes);

                date_default_timezone_set($driver->CountryArea->timezone);
                $time_left_text = \Carbon\Carbon::parse($ent_date_time)->diffForhumans();

                array_push($booking_data, array(
                    "id" => $booking->id,
                    "booking_id" => "#$booking->id",
                    "status_id" => $booking->status,
                    "status" => $bus_booking_status[$booking->status],
                    "route_name" => $booking->BusRoute->Name,
                    "from_address" => $booking->BusRoute->StartPoint->Name,
                    "to_adderss" => $booking->BusRoute->EndPoint->Name,
                    "date" => $booking->booking_date,
                    "end_date" => $ent_date_time->format("Y-m-d"),
                    "time" => date("H:i", strtotime($booking->ServiceTimeSlotDetail->slot_time_text)),
                    "end_time" => $ent_date_time->format("H:i"),
                    "duration" => $duration,
                    "number_of_bus_stops" =>  $booking->BusRoute->StopPoints->count(),
                    "number_of_passangers" => $booking->BusBooking->sum("total_seats"),
                    "time_left_text" => $time_left_text, //"1 Hour Left"
                    "service_type" => $booking->service_type_id == 5851 ? "INTRACITY" : "INTERCITY"
                ));
            }

            $booking_cell['cell_contents'] = $booking_data;
            array_push($response, $booking_cell);
        }

        if(in_array("BOOKING_STATS", $holder_item)){
            $booking_stats_detail = BusBookingMaster::selectRaw('
                    COUNT(*) as total_records,
                    COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as today_records,
                    COUNT(CASE WHEN MONTH(created_at) = ? THEN 1 END) as this_month_records
                ', [date("Y-m-d"), date("m")])->where("driver_id", $driver->id)
                ->first();

            $booking_stats['cell_title'] = 'BOOKING_STATS';
            $booking_stats['cell_title_text'] = 'Booking Stats';
            $booking_stats['cell_contents'] = array(
                array(
                    "bookings_of_the_day" => $booking_stats_detail->today_records,
                    "bookings_of_the_month" => $booking_stats_detail->this_month_records,
                    "total_bookings" => $booking_stats_detail->total_records
                ),
            );
            array_push($response, $booking_stats);
        }

        // Get banner list for holder
        if (in_array("BANNER", $holder_item)) {
            $request->merge(['merchant_id' => $merchant_id, 'home_screen' => NULL, 'segment_id' => $segment_id, 'banner_for' => 2]);
            $arr_banner = $this->getMerchantBanner($request);
            $banner_res['cell_title'] = 'BANNER_CELL';
            $banner_res['cell_title_text'] = 'Banners';
            $banner_res['cell_contents'] = $arr_banner->map(function ($item, $key) use ($merchant_id) {
                return array(
                    'id' => $item->id,
                    'title' => $item->banner_name,
                    'image' => get_image($item->banner_images, 'banners', $merchant_id),
                    'action_type' => !empty($item->action_type) ? $item->action_type : "",
                    'redirect_url' => !empty($item->redirect_url) ? $item->redirect_url : "",
                );
            });
            array_push($response, $banner_res);
        }

        //  Get bookings
        if (in_array("BOOKING", $holder_item)) {
            $booking_cell['cell_title'] = "BOOKING_CELL";
            $booking_cell['cell_title_text'] = "Bookings";
            $bus_bookings = BusBookingMaster::where("driver_id", $driver->id)->whereIn("status", [3,4])->latest()->take(3)->get();
//            if(!empty($bus_bookings) && count($bus_bookings) > 0){
            $booking_data = [];
            $bus_booking_status = Config::get("custom.bus_booking_status");
            // $total_active_booking_count = 0;
            foreach($bus_bookings as $booking){
                $booking_data[] = array(
                    "id" => $booking->id,
                    "booking_id" => "#$booking->id",
                    "status_id"=>$booking->status,
                    "status" => $bus_booking_status[$booking->status],
                    "from_address" => $booking->BusRoute->StartPoint->Name,
                    "to_adderss" => $booking->BusRoute->EndPoint->Name,
                    "date" => $booking->booking_date,
                    "time" => $booking->ServiceTimeSlotDetail->slot_time_text
                );
                
            }
            
            $booking_cell['cell_contents'] = $booking_data;
            array_push($response, $booking_cell);
        }
        return array("data" => $response);
    }

    public function getBookings(Request $request){
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            // call area trait to get id of area
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $bus_bookings = BusBookingMaster::where(["merchant_id" => $driver->merchant_id, "driver_id" => $driver->id])
                ->where(function ($q) use($request){
                    if(isset($request->type) && $request->type == "ACTIVE"){
                        $q->whereIn("status", [1,2]);
                    }elseif(isset($request->type) && $request->type == "PAST"){
                        $q->whereIn("status", [3,4]);
                    }
                    $q->where(["segment_id" => $request->segment_id]);
                })
                ->latest()
                ->get();
            $bus_booking_status = Config::get("custom.bus_booking_status");
            $bus_booking_arr = [];
            foreach($bus_bookings as $bus_booking){
                $bus_booking_arr[] = array(
                    "id" => $bus_booking->id,
                    "booking_id" => "#$bus_booking->id",
                    "status" => $bus_booking_status[$bus_booking->status],
                    "booking_status" => $bus_booking->status,
                    "start_point" => $bus_booking->BusRoute->StartPoint->Name,
                    "to_adderss" => $bus_booking->BusRoute->EndPoint->Name,
                    "route_name" => $bus_booking->BusRoute->Name,
                    "date" => $bus_booking->booking_date,
                    "bus_name" => $bus_booking->Bus->busName($bus_booking->Bus),
                    "time" => $bus_booking->ServiceTimeSlotDetail->slot_time_text,
                    "total_seats" => $bus_booking->Bus->total_seats,
                    "service_type" => $bus_booking->service_type_id == 5851 ? "INTRACITY" : "INTERCITY",
                );
            }
            return $this->successResponse(trans("$string_file.data_found"), $bus_booking_arr);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function getBooking(Request $request){

        $driver = $request->user('api-driver');
        
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => ['required', 'integer', Rule::exists('bus_booking_masters', 'id')->where(function ($query) use($driver){
                $query->where("driver_id", $driver->id);
            }),],
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {

            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $bus_booking = BusBookingMaster::where(["merchant_id" => $driver->merchant_id, "driver_id" => $driver->id])
                ->find($request->bus_booking_id);
            $bus_booking->find_bus_stop_id = isset($request->bus_stop_id) && !empty($request->bus_stop_id) ? $request->bus_stop_id : null;


            $bus_booking_detail = new DriverBusBookingResource($bus_booking);

            return $this->successResponse(trans("$string_file.data_found"), $bus_booking_detail);
        } catch (\Exception $e) {

            return $this->failedResponse($e->getMessage());
        }
    }

    public function getBookingStopDetail(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => ['required', 'integer', Rule::exists('bus_booking_masters', 'id')->where(function ($query) {
            }),],
            'bus_stop_id' => ['required', 'integer', Rule::exists('bus_stops', 'id')->where(function ($query) {
            }),]
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            // call area trait to get id of area
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $bus_booking = BusBookingMaster::where(["merchant_id" => $driver->merchant_id, "driver_id" => $driver->id])
                ->find($request->bus_booking_id);

            $bus_booking->detail_bus_stop_id = $request->bus_stop_id;
            $bus_booking_stop_detail = new DriverBusBookingStopResource($bus_booking);
            return $this->successResponse(trans("$string_file.data_found"), $bus_booking_stop_detail);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function startBooking(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => ['required', 'integer', Rule::exists('bus_booking_masters', 'id')->where(function ($query) {
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $bus_booking = BusBookingMaster::where(["merchant_id" => $driver->merchant_id, "driver_id" => $driver->id])
                ->find($request->bus_booking_id);
            $bus_booking->status = 2;
            $bus_booking->save();

            $bus_booking->find_bus_stop_id = isset($request->bus_stop_id) && !empty($request->bus_stop_id) ? $request->bus_stop_id : null;
            $bus_booking_detail = new DriverBusBookingResource($bus_booking);
            return $this->successResponse(trans("$string_file.success"), $bus_booking_detail);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function pickupDrop(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_booking_master_id' => ['required', 'integer', Rule::exists('bus_booking_masters', 'id')->where(function ($query) {
            }),],
            'bus_booking_id' => ['required', 'integer', Rule::exists('bus_bookings', 'id')->where(function ($query) {
            }),],
            'status' => ['required', Rule::in("PICKUP", "DROP"),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $bus_booking = BusBooking::where(["merchant_id" => $driver->merchant_id, "id" => $request->bus_booking_id])
                ->whereHas("BusBookingMaster", function($q) use($request, $driver){
                    $q->where("id", $request->bus_booking_master_id);
                    $q->where("driver_id", $driver->id);
                })
                ->first();
            if(empty($bus_booking)){
                return $this->failedResponse(trans("$string_file.invalid").' '.trans("$string_file.bus"));
            }
            $status = $bus_booking->status;
            if($request->status == "PICKUP"){
                $status = 2;
                $type = ($bus_booking->booking_for == 1) ? "BUS_BOOKING_START" :  "BUS_BOOKING_DELIVERY_START";
                if(!empty($request->payment_status) && !empty($bus_booking)){
                    $bus_booking->payment_status = $request->payment_status;
                    $bus_booking->save();
                }
            }elseif($request->status == "DROP"){
                $status = 3;
                $type = ($bus_booking->booking_for == 1) ? "BUS_BOOKING_END" :  "BUS_BOOKING_DELIVERY_END";
            }

            $bus_booking->status = $status;
            $bus_booking->save();

            $this->notifyBusBookingUser($bus_booking, $type);
            DB::commit();
            return $this->successResponse(trans("$string_file.success"));
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
    }

    public function endBooking(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => ['required', 'integer', Rule::exists('bus_booking_masters', 'id')->where(function ($query) {
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $bus_booking = BusBookingMaster::where(["merchant_id" => $driver->merchant_id, "driver_id" => $driver->id])
                ->find($request->bus_booking_id);
            $bus_booking->status = 3;
            $bus_booking->save();

            BusBooking::where("bus_booking_master_id", $bus_booking->id)
            ->whereIn("status", [1,2])
            ->update([
              "status" => 3,
            ]);
            
            $bus_booking->find_bus_stop_id = isset($request->bus_stop_id) && !empty($request->bus_stop_id) ? $request->bus_stop_id : null;
            $bus_booking_detail = new DriverBusBookingResource($bus_booking);
            return $this->successResponse(trans("$string_file.success"), $bus_booking_detail);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function getPassengerBooking(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => ['required', 'string', Rule::exists('bus_bookings', 'merchant_bus_booking_id')->where(function ($query) {
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $bus_booking = BusBooking::where(["merchant_id" => $driver->merchant_id, "merchant_bus_booking_id" => $request->bus_booking_id])
                ->first();
            $data = [];
            if(!empty($bus_booking)){
                $currency = $bus_booking->User->Country->isoCode;
                $seat_details = [];
                foreach($bus_booking->BusBookingDetail as $item){
                    $seat_details[] = array(
                        "name" => $item->name,
                        "age" => $item->age,
                        "gender" => $item->gender == 1 ? trans("$string_file.male") : trans("$string_file.female"),
                        "amount" => $item->amount,
                        "seat_id" => $item->bus_seat_detail_id,
                        "seat_number" => $item->BusSeatDetail->seat_no,
                    );
                }
                $bus_types = Config::get("custom.bus_type");
                $bus_details = array(
                    "bus_name" => $bus_booking->BusBookingMaster->Bus->busName($bus_booking->BusBookingMaster->Bus),
                    "traveller_name" => isset($bus_booking->BusBookingMaster->Bus->traveller_name) ? $bus_booking->BusBookingMaster->Bus->traveller_name : "",
                    "ac_nonac" => $bus_booking->BusBookingMaster->Bus->ac_nonac == 1 ? trans("$string_file.ac") : trans("$string_file.non_ac"),
                    "bus_type" => $bus_types[$bus_booking->BusBookingMaster->Bus->type],
                    "rating" => 5
                );
                $package_details = [];
                if($bus_booking->booking_for == 2 && !empty($bus_booking->BusBookingPackageDetail)){
                    foreach($bus_booking->BusBookingPackageDetail as $package){
                        $package_details[]=[
                            "id"=>$package->id,
                            "name"=>$package->name,
                            "quantity"=>$package->quantity,
                            "length" => round_number($package->length, 2),
                            "width" =>  round_number($package->width, 2),
                            "height" => round_number($package->height, 2),
                            "weight" => round_number($package->weight, 2),
                            "amount" => round_number($package->amount, 2),
                        ];
                    }
                }
                
                $bill_details = [];
                $charges_according_to_persons = $bus_booking->number_of_rider * $bus_booking->per_seat_charges;

                $seats_amount = isset($charges_according_to_persons) && $charges_according_to_persons !== 0
                                ? $charges_according_to_persons
                                : $bus_booking->total_amount;
                                
                $seat_charges = [
                    'name' => trans("$string_file.seat_charges"),
                    'value' => $currency . ' ' . sprintf("%.2f", $seats_amount),
                    'bold' => false,
                ];
                array_push($bill_details, $seat_charges);
                $tax = [
                    'name' => trans("$string_file.tax"),
                    'value' => $currency .' ' . sprintf("%.2f", $bus_booking->tax),
                    'bold' => false,
                ];
                array_push($bill_details, $tax);
                $total = [
                    'name' => trans("$string_file.total_charges"),
                    'value' => $currency . ' ' . ($bus_booking->total_amount),
                    'bold' => true,
                ];
                array_push($bill_details, $total);
                $data = array(
                    "bus_booking_id" => $bus_booking->id,
                    "bus_booking_master_id" => $bus_booking->bus_booking_master_id,
                    "name" => $bus_booking->User->UserName,
                    "phone" => $bus_booking->User->userPhone,
                    "total_seats" => $bus_booking->total_seats,
                    "from_address" => $bus_booking->BusStop->Name,
                    "to_adderss" => $bus_booking->EndBusStop->Name,
                    "amount" => $currency." ".$bus_booking->total_amount,
                    "bus_details" => $bus_details,
                    "seat_details" => $seat_details,
                    "total_no_passenger"=>$bus_booking->total_seats,
                    "user_contact_details"=>!empty($bus_booking->contact_details)? json_decode($bus_booking->contact_details): "",
                    "package_details" => $package_details,
                    "package_receiver_details"=> !empty($bus_booking)? json_decode($bus_booking->package_receiver_details): (object)[],
                    "booking_status"=> $bus_booking->status,
                    "payment_method"=> $bus_booking->PaymentMethod->payment_method,
                    "payment_method_id"=> $bus_booking->payment_method_id,
                    "bill_details"=> $bill_details,
                );
            }else{
                throw new \Exception(trans("$string_file.not_found"));
            }
            return $this->successResponse(trans("$string_file.success"), $data);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function busStopStatusUpdate(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => ['required', 'integer', Rule::exists('bus_booking_masters', 'id')->where(function ($query) {
            }),],
            'stop_id' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $bus_booking = BusBookingMaster::where(["merchant_id" => $driver->merchant_id, "driver_id" => $driver->id])
                ->find($request->bus_booking_id);
            $stops_history = json_decode($bus_booking->stop_status_history);

            foreach($stops_history as $key=>$stop){
                if($stop->is_travelled==0){
                    $stop->is_travelled = 1;
                    $stop->travelled_timestamp = time();
                    if($stop->stop_id==$request->stop_id){
                        break;
                    }
                }
            }

            // check if dropped old stop passengers (if not then change their status as 3(completed))
            foreach($bus_booking->BusBooking as $booking){
                $stop_point = $booking->DropPoint->BusStop->where("id", $request->stop_id)->first();
                if(!empty($stop_point) && $stop_point->id == $request->stop_id){
                    $booking->status = 3;
                    $booking->save();
                }
            }



            $bus_booking->stop_status_history = json_encode($stops_history);
            $bus_booking->save();

            $bus_booking->find_bus_stop_id = isset($request->bus_stop_id) && !empty($request->bus_stop_id) ? $request->bus_stop_id : null;
            $bus_booking_detail = new DriverBusBookingResource($bus_booking);
            return $this->successResponse(trans("$string_file.success"), $bus_booking_detail);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function masterBookings(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => ['required', 'integer', Rule::exists('bus_booking_masters', 'id')->where(function ($query) {
            }),]
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try{
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $master = BusBookingMaster::where("id", $request->bus_booking_id)->where("driver_id", $driver->id)->first();
            $bus_bookings = isset($master) ? $master->BusBooking: [];
            $bus_booking_status = Config::get("custom.bus_booking_status");

            $bus_booking_arr =[];
            foreach($bus_bookings as $booking){

                $in_passengers = [];
                $out_passengers = [];
                $in_package = [];
                $out_package = [];


                $passengers = [];
                foreach($booking->BusBookingDetail as $detail){
                    $passengers[] = array(
                        "name" => $detail->name,
                        "age" => $detail->age,
                        "gender" => $detail->gender == 1 ? trans("$string_file.male") : trans("$string_file.female"),
                        "seat" => $detail->BusSeatDetail->seat_no,
                    );
                }

                $packages =[];
                if($booking->booking_for == 2){
                    foreach($booking->BusBookingPackageDetail as $package){
                        $packages[] = array(
                            "id" => $package->id,
                            "name" => $package->name,
                            "quantity" => $package->quantity,
                            "length" => round_number($package->length, 2),
                            "width" => round_number($package->width, 2),
                            "height" => round_number($package->height, 2),
                            "weight" => round_number($package->weight, 2),
                            "amount" => round_number($package->amount, 2),
                        );
                    }
                }
                switch ($booking->booking_for){
                    case 1:
                        if($booking->bus_stop_id != $booking->end_bus_stop_id) {
                            $in_passengers[] = array(
                                "id" => $booking->id,
                                "booking_id" => $booking->merchant_bus_booking_id,
                                "user_name" => $booking->User->UserName,
                                "user_phone" => $booking->User->UserPhone,
                                "total_seats" => $booking->total_seats,
                                "total_amount" => ($booking->PaymentMethod->payment_method_type == 1) ? $booking->total_amount : "",
                                "payment_method" => $booking->PaymentMethod->MethodName($booking->merchant_id),
                                "passengers" => $passengers
                            );
                        }else{
                            $out_passengers[] = array(
                                "id" => $booking->id,
                                "booking_id" => $booking->merchant_bus_booking_id,
                                "user_name" => $booking->User->UserName,
                                "user_phone" => $booking->User->UserPhone,
                                "total_seats" => $booking->total_seats,
                                "total_amount" => ($booking->PaymentMethod->payment_method_type == 1) ? $booking->total_amount : "",
                                "payment_method" => $booking->PaymentMethod->MethodName($this->merchant_id),
                                "passengers" => $passengers
                            );
                        }
                        break;
                    case 2:
                        if($booking->bus_stop_id != $booking->end_bus_stop_id) {
                            $in_package[] = array(
                                "id" => $booking->id,
                                "booking_id" => $booking->merchant_bus_booking_id,
                                "user_name" => $booking->User->UserName,
                                "user_phone" => $booking->User->UserPhone,
                                "total_packages" => count($booking->BusBookingPackageDetail),
                                "total_amount" => ($booking->PaymentMethod->payment_method_type == 1) ? $booking->total_amount : "",
                                "payment_method" => $booking->PaymentMethod->MethodName($booking->merchant_id),
                                "package_receiver_details" => !empty($booking->package_receiver_details) ? json_decode($booking->package_receiver_details) : (object)"",
                                "package_details" => $packages,
                            );
                        }else{
                            $out_package[] = array(
                                "id" => $booking->id,
                                "booking_id" => $booking->merchant_bus_booking_id,
                                "user_name" => $booking->User->UserName,
                                "user_phone" => $booking->User->UserPhone,
                                "total_packages" => count($booking->BusBookingPackageDetail),
                                "total_amount" => ($booking->PaymentMethod->payment_method_type == 1) ? $booking->total_amount : "",
                                "payment_method" => $booking->PaymentMethod->MethodName($booking->merchant_id),
                                "package_receiver_details" => !empty($booking->package_receiver_details) ? json_decode($booking->package_receiver_details) : (object)"",
                                "package_details" => $packages
                            );
                        }
                        break;
                }

                $bus_booking_arr[] = array(
                    "id" => $booking->id,
                    "booking_id" => "#$booking->id",
                    "pick_point" => $booking->PickupPoint->address,
                    "drop_point" => $booking->DropPoint->address,
                    "status" => $bus_booking_status[$booking->status],
                    "booking_status" => $booking->status,
                    "service_type" => $booking->BusBookingMaster->ServiceType->ServiceName($booking->merchant_id),
                    "in_passengers" =>  $in_passengers,
                    "out_passengers" =>  $out_passengers,
                    "in_package"=> $in_package,
                    "out_package"=> $out_package,
                );
            }
            return $this->successResponse(trans("$string_file.data_found"), $bus_booking_arr);
        }
        catch (\Exception $e){
            // return $this->failedResponse($e->getMessage());
            throw $e;

        }
    }

}
