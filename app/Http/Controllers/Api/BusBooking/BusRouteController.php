<?php

namespace App\Http\Controllers\Api\BusBooking;


use App\Http\Controllers\Helper\GoogleController;

use App\Models\BookingConfiguration;
use App\Models\BusBookingDetail;
use App\Models\BusBookingMaster;
use App\Models\BusPickupDropPoint;
use App\Models\BusPriceCard;
use App\Models\BusSeatDetail;
use App\Models\BusService;
use App\Models\BusStop;
use App\Models\BusPolicy;
use App\Models\Configuration;
use App\Models\ServiceTimeSlotDetail;
use App\Models\ServiceType;
use App\Traits\BusTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Traits\AreaTrait;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Traits\BusRouteTrait;
use App\Models\Bus;
use App\Models\BusRoute;
use App\Models\BusRouteMapping;
use DB;
use App\Models\BusBooking;
use function Symfony\Component\Console\Style\success;
use Carbon\Carbon;


class BusRouteController extends Controller
{
    use AreaTrait, ApiResponseTrait, MerchantTrait, BusRouteTrait, BusTrait;

    /**
     * Get available routes and buses
     */
    public function getRouteAndBuses(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            "segment_id" => 'required|exists:segments,id',
            "service_type_id" => 'required|exists:service_types,id',
            "latitude" => 'required|string',
            "longitude" => 'required|string',
            "drop_latitude" => 'required|string',
            "drop_longitude" => 'required|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $service_type = ServiceType::find($request->service_type_id);
       
        $request->merge(["service_types_type" => $service_type->type]);
        $validator = Validator::make($request->all(), [
            "booking_date" => 'required_if:service_types_type,2|date|date_format:Y-m-d|after_or_equal:' . date('Y-m-d'), // if service type is intercity
        ], ['booking_date.required_if' => "Booking date is required"]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $merchant = $user->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        try {
            $route = new BusRoute;
            $route_mapping = new BusRouteMapping;
            $request->merge([
                "get_details" => true
            ]);
            $arr_available_routes = $route->getNewNearestRoute($request, $user);
            $arr_available_buses = [];
            $bus_stop_id = null;
            $end_bus_stop_id = null;

            foreach($arr_available_routes as &$arr_available_route){
                $request->merge([
                    "bus_route_id" => $arr_available_route["bus_route_id"],
                    "bus_stop_id" => $arr_available_route["bus_stop_id"],
                    "end_bus_stop_id" => $arr_available_route["end_bus_stop_id"],
                ]);
                
                $arr_available_route['number_of_buses'] = count($route_mapping->routeBuses($request, $user));
            }
            $booking_date = isset($request->booking_date) ? $request->booking_date : null;

            // Intracity
            if ($request->service_types_type == 1 && count($arr_available_routes) >= 1) {
                $bus_stop_id = $arr_available_routes[0]["bus_stop_id"];
                $end_bus_stop_id = $arr_available_routes[0]["end_bus_stop_id"];
                $request->merge([
                    "bus_route_id" => $arr_available_routes[0]["bus_route_id"],
                    "bus_stop_id" => $arr_available_routes[0]["bus_stop_id"],
                    "end_bus_stop_id" => $arr_available_routes[0]["end_bus_stop_id"],
                ]);
                $route_time_slot_buses = $route_mapping->routeBuses($request, $user);
                $arr_available_buses = $this->busesResponse($route_time_slot_buses, $string_file, $booking_date, $request);
                
            } elseif ($request->service_types_type == 2) { // Intercity
                if (!empty($arr_available_routes)) {
                    $bus_stop_id = $arr_available_routes[0]["bus_stop_id"];
                    $end_bus_stop_id = $arr_available_routes[0]["end_bus_stop_id"];
                }
                foreach ($arr_available_routes as &$arr_available_route) {
                    $request->merge([
                        "bus_route_id" => $arr_available_route["bus_route_id"],
                        "bus_stop_id" => $arr_available_route["bus_stop_id"],
                        "end_bus_stop_id" => $arr_available_route["end_bus_stop_id"],
                    ]);
                    $route_time_slot_buses = $route_mapping->routeBuses($request, $user);
                    $temp = $this->busesResponse($route_time_slot_buses, $string_file, $booking_date, $request);
                    if (!empty($temp)) {
                        $arr_available_buses = array_merge($arr_available_buses, $temp);
                    }
                }
               
            }
            $arr_available_buses = $this->sortBusesData($request, $arr_available_buses);
            /*FOR SORT THE BUS DATA ACCORDING TO CURRENT AND PREVIOUS TIME*/
            // $sortBusesData  = [];
            // $current_time_utc = strtotime(gmdate("Y-m-d H:i:s"));
            // foreach($arr_available_buses as $data)
            // {
            //      $initialTime = Carbon::createFromFormat('H:i', $data['slot_time']);
            //      $start_time_route_formated = $initialTime->format('H:i A');
            //      $previous_time = strtotime($start_time_route_formated);
            //     if ($current_time_utc > $previous_time) {
            //         continue;
            //     }
            //     $sortBusesData[]=$data;
            // }
            $return = array(
                "routes" => $arr_available_routes,
                "buses" => $arr_available_buses,
                "bus_stop_id" => $bus_stop_id,
                "end_bus_stop_id" => $end_bus_stop_id
            );
          
            return $this->successResponse(trans("$string_file.data_found"), $return);
        } catch (\Throwable $th) {
            return $this->failedResponse($th->getMessage());
        }
    }

    /**
     * Route Details
     */
    public function getRouteDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "segment_id" => 'required|exists:segments,id',
            "service_type_id" => 'required|exists:service_types,id',
            "bus_route_id" => 'required|integer',
            "bus_stop_id" => 'required|integer',
            "end_bus_stop_id" => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $merchant = $user->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        try {
            $bus_route = BusRoute::find($request->bus_route_id);
            $arr_stop_points = [];
          
            if(!empty($request->service_time_slot_detail_id))
            {
                $service_time_slot_detail = ServiceTimeSlotDetail::find($request->service_time_slot_detail_id);
                $start_time               = $service_time_slot_detail->slot_time_text;
                $bus_routes_stops         = DB::table('bus_routes_bus_stops')
                                                ->where('bus_stop_id', '>=', $request->bus_stop_id)
                                                ->where('end_bus_stop_id', '<=',$request->end_bus_stop_id)
                                                ->where('bus_route_id', '=', $request->bus_route_id)
                                                ->orderBy("sequence", 'ASC')
                                                ->get()->toArray();
            
               
                $i = 0;
                $start_time_route = $start_time;
                $initialTimeFormat = Carbon::createFromFormat('H:i', $start_time);
                $start_time_route_formated = $initialTimeFormat->format('H:i A');
            }
            
            foreach ($bus_route->StopPoints as $stopPoint) {
                if(!empty($request->service_time_slot_detail_id))
                {
                    if($i != 0)
                    {
                    // Parse the initial time using Carbon
                    $initialTime = Carbon::createFromFormat('H:i', $start_time_route);
                    
                    // Add minutes to the initial time
                    $newTime = $initialTime->addMinutes($bus_routes_stops[$i]->time);
                    // Format the new time as 24-hour format
                    $start_time_route = $newTime->format('H:i');
                    $start_time_route_formated = $newTime->format('H:i A');
                    }
                }

                array_push($arr_stop_points, array(
                    'id' => $stopPoint->id,
                    'name' => $stopPoint->Name,
                    'is_it_pickup_point' => $request->bus_stop_id == $stopPoint->id ? true : false,
                    'is_it_drop_point' => $request->end_bus_stop_id == $stopPoint->id ? true : false,
                    'latitude' => $stopPoint->latitude,
                    'longitude' => $stopPoint->longitude,
                    'address' => $stopPoint->address,
                    'schedule_time'=>!empty($request->service_time_slot_detail_id)?$start_time_route_formated:NULL,
                ));
                if(!empty($request->service_time_slot_detail_id))
                {
                    $i++;
                }
                
            }

            array_push($arr_stop_points, array(
                'id' => $bus_route->EndPoint->id,
                'name' => $bus_route->EndPoint->Name,
                'is_it_pickup_point' => $request->bus_stop_id == $bus_route->EndPoint->id ? true : false,
                'is_it_drop_point' => $request->end_bus_stop_id == $bus_route->EndPoint->id ? true : false,
                'latitude' => $bus_route->EndPoint->latitude,
                'longitude' => $bus_route->EndPoint->longitude,
                'address' => $bus_route->EndPoint->address,
                'schedule_time'=>!empty($request->service_time_slot_detail_id)?$start_time_route_formated:NULL,
            ));
            
            $current_latitude = "";
            $current_longitude = "";
            $booking = BusBooking::find($request->bus_booking_id);
            if(isset($booking)){
                switch($booking->BusBookingMaster->status){
                    case 1:
                        $current_latitude = $booking->BusBookingMaster->BusRoute->StartPoint->latitude;
                        $current_longitude = $booking->BusBookingMaster->BusRoute->StartPoint->longitude;
                        break;
                    case 2:
                        $current_latitude = $booking->BusBookingMaster->Driver->current_latitude;
                        $current_longitude = $booking->BusBookingMaster->Driver->current_longitude;
                        break;
                    case 3:
                        $current_latitude = $booking->BusBookingMaster->BusRoute->EndPoint->latitude;
                        $current_longitude = $booking->BusBookingMaster->BusRoute->EndPoint->longitude;
                        break;
                }
            }
            
            $return = [
                'bus_route' => array(
                    'id' => $bus_route->id,
                    'route_title' => $bus_route->Name,
                    'current_latitude'=>$current_latitude,
                    'current_longitude'=>$current_longitude
                    
                ),
                'stop_points' => $arr_stop_points
            ];
        } catch (\Throwable $th) {
            return $this->failedResponse($th->getMessage());
        }
        return $this->successResponse(trans("$string_file.data_found"), $return);
    }

    /**
     * Get Route buses details with time slot
     */
    public function getRouteBuses(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "segment_id" => 'required|exists:segments,id',
            "service_type_id" => 'required|exists:service_types,id',
            "bus_route_id" => 'required|integer',
            "latitude" => 'required|string',
            "longitude" => 'required|string',
            "drop_latitude" => 'required|string',
            "drop_longitude" => 'required|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $service_type = ServiceType::find($request->service_type_id);
        $request->merge(["service_types_type" => $service_type->type]);
        $validator = Validator::make($request->all(), [
            "booking_date" => 'required_if:service_types_type,2|date|date_format:Y-m-d|after_or_equal:' . date('Y-m-d'), // if service type is intercity
        ], ['booking_date.required_if' => "Booking date is required"]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $merchant = $user->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        try {
            $booking_date = isset($request->booking_date) ? $request->booking_date : null;
            $bus_route = BusRoute::find($request->bus_route_id);
            $route_mapping = new BusRouteMapping;
            $route_time_slot_buses = $route_mapping->routeBuses($request, $user);
            $day_data = $this->busesResponse($route_time_slot_buses, $string_file, $booking_date, $request);
            $return = [
                'bus_route' => array(
                    'id' => $bus_route->id,
                    'route_title' => $bus_route->Name
                ),
                'arr_buses_data' => $day_data
            ];
        } catch (\Throwable $th) {
            return $this->failedResponse($th->getMessage());
        }
        return $this->successResponse(trans("$string_file.data_found"), $return);
    }

    /**
     * Get Bus Stop Pickup and Drop Points
     */
    public function getBusStopPickupDrops(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "bus_stop_id" => 'required|exists:bus_stops,id',
            "end_bus_stop_id" => 'required|exists:bus_stops,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $merchant = $user->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        $service_type = ServiceType::find($request->service_type_id);
        $service_time_slot_detail = ServiceTimeSlotDetail::find($request->service_time_slot_detail_id);

        try {
            
            if($service_type->type == 2)
            {
                $bus_pickup_points = BusPickupDropPoint::where("merchant_id", $merchant_id)
                ->with(['BusStop' => function ($q) use ($request) {

                    $q->with(["BusRoutePoints"=>function($q) use ($request){
                        $q->where([["bus_route_id","=", $request->bus_route_id]]);

                        $q->with(["BusBookingMaster"=>function($q) use ($request){
                            $q->where([
                                ["bus_route_id","=", $request->bus_route_id],
                                ["bus_id", "=", $request->bus_id],
                                ["booking_date", "=", $request->booking_date],
                                ["service_time_slot_detail_id", "=",$request->service_time_slot_detail_id]
                            ]);
                        }]);

                    }]);

                    $q->where("id", $request->bus_stop_id);
                }])->whereHas("BusStop", function ($q) use ($request) {
                    $q->where("id", $request->bus_stop_id);
                })->get();

                $bus_drop_points = BusPickupDropPoint::where("merchant_id", $merchant_id)
                ->with(['BusStop' => function ($q) use ($request) {

                    $q->with(["BusRoutePoints"=>function($q) use ($request){
                        $q->where([["bus_route_id","=", $request->bus_route_id]]);

                        $q->with(["BusBookingMaster"=>function($q) use ($request){
                            $q->where([
                                ["bus_route_id","=", $request->bus_route_id],
                                ["bus_id", "=", $request->bus_id],
                                ["booking_date", "=", $request->booking_date],
                                ["service_time_slot_detail_id", "=",$request->service_time_slot_detail_id]
                            ]);
                        }]);

                    }]);

                    $q->where("id", $request->end_bus_stop_id);
                }])->whereHas("BusStop", function ($q) use ($request) {
                    $q->where("id", $request->end_bus_stop_id);
                })->get();
            }
            else
            {
                $bus_pickup_points = BusPickupDropPoint::where("merchant_id", $merchant_id)
                        ->with(['BusStop' => function ($query) use ($request) {
                            $query->with(["BusRoutePoints"=>function($q) use ($request){
                                $q->where([["bus_route_id","=", $request->bus_route_id]]);

                                $q->with(["BusBookingMaster"=>function($q) use ($request){
                                    $q->where([
                                        ["bus_route_id","=", $request->bus_route_id],
                                        ["bus_id", "=", $request->bus_id],
                                        ["booking_date", "=", $request->booking_date],
                                        ["service_time_slot_detail_id", "=",$request->service_time_slot_detail_id]
                                    ]);
                                }]);

                            }]);
                            $query->whereBetween('id', [$request->bus_stop_id, $request->end_bus_stop_id]);
                        }])
                        ->whereHas('BusStop', function ($query) use ($request) {
                            $query->whereBetween('id', [$request->bus_stop_id, $request->end_bus_stop_id]);
                        })
                        ->get();

                $bus_drop_points = BusPickupDropPoint::where("merchant_id", $merchant_id)
                    ->with(['BusStop' => function ($query) use ($request) {

                        $query->with(["BusRoutePoints"=>function($q) use ($request){
                            $q->where([["bus_route_id","=", $request->bus_route_id]]);

                            $q->with(["BusBookingMaster"=>function($q) use ($request){
                                $q->where([
                                    ["bus_route_id","=", $request->bus_route_id],
                                    ["bus_id", "=", $request->bus_id],
                                    ["booking_date", "=", $request->booking_date],
                                    ["service_time_slot_detail_id", "=",$request->service_time_slot_detail_id]
                                ]);
                            }]);

                        }]);

                        $query->whereBetween('id', [$request->bus_stop_id, $request->end_bus_stop_id]);
                    }])
                    ->whereHas('BusStop', function ($query) use ($request) {
                        $query->whereBetween('id', [$request->bus_stop_id, $request->end_bus_stop_id]);
                    })
                    ->get();
            }

            $bus_pickup_points_arr = [];
            $bus_pickup_stop_minutes =0;
            foreach ($bus_pickup_points as $point) {
                $stop_status_history = isset($point->BusStop[0]->BusRoutePoints[0]->BusBookingMaster[0]) ? json_decode($point->BusStop[0]->BusRoutePoints[0]->BusBookingMaster[0]->stop_status_history): null;
                $stop_ids = isset($stop_status_history)? array_column($stop_status_history, 'stop_id'): null;
                $key = isset($stop_ids)? array_search($point->BusStop[0]->id, $stop_ids): null;

                $travelled = false;
                $bus_pickup_stop_minutes += isset($point->BusStop[0]->BusRoutePoints[0] )? $point->BusStop[0]->BusRoutePoints[0]->pivot->time : 0;
                $date_time = isset($service_time_slot_detail) ? Carbon::parse($request->booking_date." ".$service_time_slot_detail->to_time)->addMinutes($bus_pickup_stop_minutes)->format('d M, D H:i a'): "";
                if(isset($key)){
                    $travelled = $stop_status_history[$key]->is_travelled == 1;
                }

                $bus_pickup_points_arr[] = array(
                    "id" => $point->id,
                    "name" => $point->Name,
                    "address" => $point->address,
                    "latitude" => $point->latitude,
                    "longitude" => $point->longitude,
                    "is_travelled" => $travelled,
                    "date_time"=>$date_time
                );
            }

            $bus_drop_points_arr = [];
            $bus_drop_stop_minutes = 0;
            foreach ($bus_drop_points as $point) {

                $stop_status_history = isset($point->BusStop[0]->BusRoutePoints[0]->BusBookingMaster[0]) ? json_decode($point->BusStop[0]->BusRoutePoints[0]->BusBookingMaster[0]->stop_status_history): null;
                $stop_ids = isset($stop_status_history)? array_column($stop_status_history, 'stop_id'): null;
                $key = isset($stop_ids)? array_search($point->BusStop[0]->id, $stop_ids): null;

                $travelled = false;
                $bus_drop_stop_minutes += isset($point->BusStop[0]->BusRoutePoints[0] )? $point->BusStop[0]->BusRoutePoints[0]->pivot->time : 0;
                $date_time = isset($service_time_slot_detail) ? Carbon::parse($request->booking_date." ".$service_time_slot_detail->to_time)->addMinutes($bus_drop_stop_minutes)->format('d M, D H:i a'): "";

                if(isset($key)){
                    $travelled = $stop_status_history[$key]->is_travelled == 1;
                }

                $bus_drop_points_arr[] = array(
                    "id" => $point->id,
                    "name" => $point->Name,
                    "address" => $point->address,
                    "latitude" => $point->latitude,
                    "longitude" => $point->longitude,
                    "is_travelled" => $travelled,
                    "date_time"=>$date_time,
                );
            }
            $return = array(
                'pickup_arr' => $bus_pickup_points_arr,
                'drop_arr' => $bus_drop_points_arr
            );
        } catch (\Throwable $th) {
            return $this->failedResponse($th->getMessage());
        }
        return $this->successResponse(trans("$string_file.data_found"), $return);
    }

    /**
     * Get Bus Details with Seat Available
     */
    public function getBusDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "segment_id" => 'required|exists:segments,id',
            "service_type_id" => 'required|exists:service_types,id',
            "bus_route_id" => 'required|integer|exists:bus_routes,id',
            "bus_id" => 'required|integer|exists:buses,id',
            "bus_stop_id" => 'required|integer|exists:bus_stops,id',
            "end_bus_stop_id" => 'required|integer|exists:bus_stops,id',
            "service_time_slot_detail_id" => 'required|integer|exists:service_time_slot_details,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $service_type = ServiceType::find($request->service_type_id);
        $request->merge(["service_types_type" => $service_type->type]);
        $validator = Validator::make($request->all(), [
            "booking_date" => 'required_if:service_types_type,2|date|date_format:Y-m-d|after_or_equal:' . date('Y-m-d'), // if service type is intercity
        ], ['booking_date.required_if' => "Booking date is required"]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $merchant = $user->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
        try {
            $return = [];

            $bus = Bus::where("id", $request->bus_id)->whereHas("BusRouteMapping", function($q) use($request){
                $q->where("bus_route_id", $request->bus_route_id);
                $q->where("service_time_slot_detail_id", $request->service_time_slot_detail_id);
            })->with(["BusRouteMapping" => function($q) use($request){
                $q->where("bus_route_id", $request->bus_route_id);
                $q->where("service_time_slot_detail_id", $request->service_time_slot_detail_id);
            }])->first();
            
            
            $price_card = BusPriceCard::where(array(
                "merchant_id" => $merchant_id,
                "bus_route_id" => $request->bus_route_id,
                "vehicle_type_id" => $bus->vehicle_type_id,
            ))->with("StopPointsPrice")->first();
          
            $per_seat_charges = "";
            $per_seat_tax    = "";
            if(!empty($price_card) && isset($request->bus_stop_id) && isset($request->end_bus_stop_id)){
                $per_seat_charges = $price_card->base_fare;
                foreach($price_card->StopPointsPrice as $stop_price){
                    if($stop_price->bus_stop_id != $request->end_bus_stop_id){
                        $per_seat_charges += $stop_price->pivot->price;
                    }else{
                        break;
                    }
                    
                }
                if($price_card->BusRoute->EndPoint->id == $request->end_bus_stop_id){
                    $per_seat_charges += $price_card->end_stop_fare;
                }
            }

            if(!empty($bus)){
                $seat_type = Config::get("custom.bus_type_show");
                $tax_per = DB::table('country_areas')
                                ->whereIn('id', [$bus->country_area_id])
                                ->get();
               
                $per_seat_tax = (floatval($per_seat_charges) * floatval($tax_per[0]->tax))/100;   
                $booked_seats = BusBookingDetail::whereHas("BusBooking", function($q) use($request){
                    $q->whereHas("BusBookingMaster", function($l) use($request){
                        $l->where("segment_id", $request->segment_id);
                        $l->where("service_type_id", $request->service_type_id);
                        $l->where("bus_id", $request->bus_id);
                        $l->where("bus_route_id", $request->bus_route_id);
                        $l->where("booking_date", $request->booking_date);
                        $l->where("service_time_slot_detail_id", $request->service_time_slot_detail_id);
                    });
                })->select("bus_seat_detail_id","gender")->get()->toArray();
             
            
                $seat_details = $bus->BusSeatDetail->map(function($q) use($booked_seats, $seat_type, $per_seat_charges,$per_seat_tax){
//                    Available, Reserved, Occupied
                    $seat_nos = array_column($booked_seats, 'bus_seat_detail_id');
                    $status = "Available";
                    $is_female = false;
                    if(in_array($q->id, $seat_nos)){
                        $status = "Occupied";
                        $key = array_search($q->id, $seat_nos);
                        $is_female = ($booked_seats[$key]['gender']==2)?true:false;
                    }
                   
                    return array(
                        "bus_seat_detail_id" => $q->id,
                        "seat_no" => $q->seat_no,
                        "sequence" => $q->sequence,
                        "type" => $seat_type[$q->type],
                        "type_slug" => $q->type,
                        "status" => $status,
                        "per_seat_charges" => floatval($per_seat_charges),
                        "per_seat_tax"     => floatval($per_seat_tax),
                        "is_female" => $is_female,
                    );
                });

               
                $service_time_slot_detail = ServiceTimeSlotDetail::find($request->service_time_slot_detail_id);

                $bus_route = BusRoute::find($request->bus_route_id);
                $start_stop = DB::table("bus_routes_bus_stops")
                    ->where("bus_route_id", $bus_route->id)->where("bus_stop_id", $request->bus_stop_id)->first();
                $end_stop = DB::table("bus_routes_bus_stops")
                    ->where("bus_route_id", $bus_route->id)->where("end_bus_stop_id", $request->end_bus_stop_id)->first();
                $orderBy = $start_stop->sequence > $end_stop->sequence ? "DESC" : "ASC";
                $bus_routes_stops = DB::table("bus_routes_bus_stops")
                    ->where(function($query) use($start_stop, $end_stop){
                        $query->where("id", $start_stop->id)->orWhere("id", $end_stop->id);
                    })
                    ->where("bus_route_id", $bus_route->id)->orderBy("sequence", $orderBy)->get()->toArray();
                $route = [];
                foreach($bus_routes_stops as $point){
                    $point = BusStop::find($point->bus_stop_id);
                    array_push($route, array(
                        "id" => $point->id,
                        "name" => $point->Name,
                        "lat" => $point->latitude,
                        "long" => $point->longitude,
                        "address" => $point->address,
                    ));
                }

                $bus_stop = BusStop::find($request->bus_stop_id);
                $end_bus_stop = BusStop::find($request->end_bus_stop_id);

                if($start_stop->id == $end_stop->id){
                    array_push($route, array(
                        "id" => $end_bus_stop->id,
                        "name" => $end_bus_stop->Name,
                        "lat" => $end_bus_stop->latitude,
                        "long" => $end_bus_stop->longitude,
                        "address" => $end_bus_stop->address,
                    ));
                }

                $services = [];
                $general_info = [];
                foreach($bus->BusService as $bus_service){
                    if($bus_service->is_general_info == 2){
                        array_push($services, array(
                            "title" => $bus_service->Name,
                            "description" => $bus_service->Description,
                            "icon" => get_image($bus_service->icon, "bus_service", $bus_service->merchant_id)
                        ));
                    }else{
                        array_push($general_info, array(
                            "title" => $bus_service->Name,
                            "description" => $bus_service->Description,
                            "icon" => get_image($bus_service->icon, "bus_service", $bus_service->merchant_id)
                        ));
                    }
                }

                $bus_design_types = Config::get("custom.bus_design_types");
                $bus_types = Config::get("custom.bus_type");

                // Calculate start to end point time in minutes
                $minutes_to_add = 0;
                if(isset($request->bus_route_id) && isset($request->end_bus_stop_id) && isset($request->bus_stop_id)){
                    $bus_route = BusRoute::find($request->bus_route_id);
                    $start_stop = DB::table("bus_routes_bus_stops")
                        ->where("bus_route_id", $bus_route->id)->where("bus_stop_id", $request->bus_stop_id)->first();
                    $end_stop = DB::table("bus_routes_bus_stops")
                        ->where("bus_route_id", $bus_route->id)->where("end_bus_stop_id", $request->end_bus_stop_id)->first();
                    $orderBy = $start_stop->sequence > $end_stop->sequence ? "DESC" : "ASC";
                    $bus_routes_stops = DB::table('bus_routes_bus_stops')
                                                ->where('bus_stop_id', '>=', $request->bus_stop_id)
                                                ->where('end_bus_stop_id', '<=',$request->end_bus_stop_id)
                                                ->where('bus_route_id', '=', $request->bus_route_id)
                                                ->orderBy("sequence", 'ASC')
                                                ->get()->toArray();
                    foreach($bus_routes_stops as $point){
                        if($point->end_bus_stop_id != $request->end_bus_stop_id){
                            $minutes_to_add += $point->time;
                        }
                    }
                }

                // Time Calculation
                $start_date_time = $request->booking_date." ".$service_time_slot_detail->slot_time_text;

                $ent_date_time = new \DateTime($start_date_time);
                $ent_date_time->add(new \DateInterval('PT' . $minutes_to_add . 'M'));

                $hours = floor($minutes_to_add / 60); // Get the number of whole hours
                $minutes = $minutes_to_add % 60; // Get the remainder of the hours

                $duration = sprintf ("%02d:%02d", $hours, $minutes);

                $available_seats = $seat_details->where("status","Available")->count();
                $bus_policy_details = BusPolicy::select("id","policy_name","description")->where("bus_id",$request->bus_id)->get();

                $return = array(
                    "main_info" => array(
                        "currency" => $user->Country->isoCode,
//                        "booking_date" => $request->booking_date,
//                        "booking_time" => $service_time_slot_detail->slot_time_text,
                        "start_date" => $request->booking_date,
                        "start_time" => date("H:i", strtotime($service_time_slot_detail->slot_time_text)),
                        "end_date" => $ent_date_time->format("Y-m-d"),
                        "end_time" => $ent_date_time->format("H:i"),
                        'travel_time' => $duration,
                        "available_seats" => $available_seats,

                        "route" => $route,
                        "start_point" => $bus_stop->Name,
                        "end_point" => $end_bus_stop->Name,
                    ),
                    "bus_details" => array(
                        "bus_id" => $bus->id,
                        "bus_name" => $bus->busName($bus),
                        "type" => $bus->type,
                        "type_text" => $bus_types[$bus->type],
                        "design_type" => $bus->design_type,
                        "design_type_text" => $bus_design_types[$bus->design_type],
                        // "traveller_name" => $bus->traveller_name,
                        "traveller_name" => isset($bus->BusTraveller)? $bus->BusTraveller->getNameAttribute() : "Traveller Name",
                        "traveller_description" => isset($bus->BusTraveller)? $bus->BusTraveller->getDescriptionAttribute() : "Traveller Description",
                        "ac_nonac" => $bus->ac_nonac == 1 ? trans("$string_file.ac") : trans("$string_file.non_ac"),
                        "bus_type" => $bus->design_type == 1 ? trans("$string_file.seater") : trans("$string_file.sleeper_seater"),
                    ),
                    "bus_policy_details" => $bus_policy_details,
                    "services" => $services,
                    "general_info" => $general_info,
                    "additional_info" => $bus->additional_info,
                    "seat_details" => $this->prepareBusSeatArray($bus->design_type, $seat_details->toArray()),
                );
            }
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.data_found"), $return);
    }

    /*
     * Get Bus Services
     */
    public function getBusServices(Request $request){
        try{
            $user = $request->user('api');
            $merchant = $user->Merchant;
            $merchant_id = $merchant->id;
            $string_file = $this->getStringFile($merchant_id);
            $services = BusService::where([["merchant_id", '=', $merchant_id],["is_general_info",'!=',1]])->orderBy("sequence")->get();
            $bus_services = [];
            foreach($services as $service){
                array_push($bus_services, array(
                    "id" => $service->id,
                    "name" => $service->Name,
                    "icon" => get_image($service->icon,'bus_service',$merchant_id),
                ));
            }
            return $this->successResponse(trans("$string_file.success"), $bus_services);
        }catch (\Exception $exception){
            return $this->failedResponse($exception->getMessage());
        }
    }

    /**
     * HELPER Functions
     */
    public function busesResponse($route_time_slot_buses, $string_file, $booking_date = null, $request = [])
    {
        
        if ($booking_date == null) {
            $booking_date = date("Y-m-d");
        }
        $current_day = date("w"); //current day num
        $arr_days = get_days($string_file);
        $bus_obj = new Bus;
        $day_data = [];

        // Calculate start to end point time in minutes
        $minutes_to_add = 0;
        
        if(isset($request->bus_route_id) && isset($request->end_bus_stop_id) && isset($request->bus_stop_id)){
            $bus_route = BusRoute::find($request->bus_route_id);
            $start_stop = DB::table("bus_routes_bus_stops")
                ->where("bus_route_id", $bus_route->id)->where("bus_stop_id", $request->bus_stop_id)->first();
            $end_stop = DB::table("bus_routes_bus_stops")
                ->where("bus_route_id", $bus_route->id)->where("end_bus_stop_id", $request->end_bus_stop_id)->first();
            
            $orderBy = $start_stop->sequence > $end_stop->sequence ? "DESC" : "ASC";
          
            $bus_routes_stops = DB::table('bus_routes_bus_stops')
                                ->where('bus_stop_id', '>=', $start_stop->bus_stop_id)
                                ->where('end_bus_stop_id', '<=',$end_stop->end_bus_stop_id)
                                ->where('bus_route_id', '=', $bus_route->id)
                                ->orderBy("sequence", $orderBy)
                                ->get()->toArray();
            
            foreach($bus_routes_stops as $point){
//                if($point->end_bus_stop_id != $request->end_bus_stop_id){
                    $minutes_to_add += $point->time;
//                }
            }
        
        }

        foreach ($route_time_slot_buses as $key1 => $time_slot) {
            foreach ($time_slot->ServiceTimeSlotDetail as $key2 => $time_slot_details) {
                foreach ($time_slot_details->BusRouteMapping as $key3 => $busMap) {
                    //$bus = Bus::find($busMap->bus_id);
                    $bus = $busMap->Bus;
                    if(!empty($bus)){
                        $price_card = BusPriceCard::where(array(
                            "merchant_id" => $request->merchant_id,
                            "bus_route_id" => $busMap->bus_route_id,
                            "vehicle_type_id" => $bus->vehicle_type_id,
                        ))->with("StopPointsPrice")->first();
                        
                        $per_seat_charges = 0;
                        if(!empty($price_card) && isset($request->bus_stop_id) && isset($request->end_bus_stop_id)){
                            $per_seat_charges = $price_card->base_fare;
                            foreach($price_card->StopPointsPrice as $stop_price){
                                if($stop_price->bus_stop_id != $request->end_bus_stop_id){
                                    $per_seat_charges += $stop_price->pivot->price;
                                }else{
                                    break;
                                }
                            }
                            if($price_card->BusRoute->EndPoint->id == $request->end_bus_stop_id){
                                $per_seat_charges += $price_card->end_stop_fare;
                            }
                        }

                        // Time Calculation
                        $start_date_time = $booking_date." ".$time_slot_details->slot_time_text;

                        $ent_date_time = new \DateTime($start_date_time);
                        $ent_date_time->add(new \DateInterval('PT' . $minutes_to_add . 'M'));
                        $hours = floor($minutes_to_add / 60); // Get the number of whole hours
                        $minutes = $minutes_to_add % 60; // Get the remainder of the hours

                        $duration = sprintf ("%02d:%02d", $hours, $minutes);
                        // $current_time_utc = strtotime(gmdate("Y-m-d H:i:s"));

                       
                        //  // Parse the initial time using Carbon
                        // $initialTime = Carbon::createFromFormat('H:i', $time_slot_details->slot_time_text);
                        // // Format the new time as 24-hour format
                        // $start_time_route = $initialTime->format('H:i');
                        // $start_time_route_formated = $initialTime->format('H:i A');
                        // // Specify the previous time (for example, 6 AM UTC)
                        // $previous_time = strtotime($start_time_route_formated);
                        // // Check if the current time is greater than the previous time
                        // if ($current_time_utc > $previous_time) {
                        //     echo "hii";
                        //   continue;
                        // }
                        

                        $day_data[] = [
                            'bus_id' => $busMap->bus_id,
                            'bus_route_id' => $busMap->bus_route_id,
                            'service_time_slot_detail_id' => $time_slot_details->id,
                            'day' => $time_slot->day,
                            'day_text' => $this->dayText($time_slot->day, $current_day, $arr_days, $string_file),
                            'bus_name' => $bus_obj->busName($busMap->Bus),
                            // 'traveller_name' => isset($busMap->Bus->traveller_name) ? $busMap->Bus->traveller_name : "",
                            "traveller_name" => isset($busMap->Bus->BusTraveller) ? $busMap->Bus->BusTraveller->getNameAttribute() : "Traveller Name",
                            "traveller_description" => isset($busMap->Bus->BusTraveller) ? $busMap->Bus->BusTraveller->getDescriptionAttribute() : "Traveller Description",
                            'seat_type' => $busMap->Bus->ac_nonac == 1 ? trans("$string_file.ac") : trans("$string_file.non_ac"),
                            'slot_time' => date("H:i", strtotime($time_slot_details->slot_time_text)),
                            'start_date' => $booking_date,
                            'start_time' => date("H:i", strtotime($time_slot_details->slot_time_text)),
                            'end_date' => $ent_date_time->format("Y-m-d"),
                            'end_time' => $ent_date_time->format("H:i"),
                            'travel_time' => $duration,
                            'rating' => !empty($busMap->Bus->rating)? $busMap->Bus->rating : 0.00,
                            'total_seat'=>$busMap->Bus->BusSeatDetail->count(),
                            'available_seats' => $this->getAvailableSeats($busMap->bus_id, $busMap->bus_route_id, $booking_date, $time_slot_details->id),
                            'per_seat_charges' => $per_seat_charges,
                            'total_stops'=> count($bus_routes_stops)-1,
                        ];
                    }
                }
            }
        }
        return $day_data;
    }

    public function getAvailableSeats($bus_id, $bus_route_id, $booking_date, $service_time_slot_detail_id)
    {
        $booked_seats = BusBookingDetail::whereHas("BusBooking", function($q) use($bus_id, $bus_route_id, $booking_date, $service_time_slot_detail_id){
            $q->whereHas("BusBookingMaster", function($l) use($bus_id, $bus_route_id, $booking_date, $service_time_slot_detail_id){
                $l->where("bus_id", $bus_id);
                $l->where("bus_route_id", $bus_route_id);
                $l->where("booking_date", $booking_date);
                $l->where("service_time_slot_detail_id", $service_time_slot_detail_id);
            });
        })->get()->count();
        $total_seats = BusSeatDetail::where("bus_id",$bus_id)->get()->count();
        return $total_seats-$booked_seats;
    }

    public function dayText($day, $current_day, $arr_days, $string_file)
    {
        $day_text = "";
        if ($day == $current_day) {
            $day_text = trans("$string_file.today");
        } elseif ($day == $current_day + 1) {
            $day_text = trans("$string_file.tomorrow");
        } else {
            $day_text = $arr_days[$day];
        }
        return $day_text;
    }
    
    public function sortBusesData($request, $arr_available_buses){
        //  ["RELEVANCE", "PRICE_HIGH_TO_LOW", "PRICE_LOW_TO_HIGH", "MOST_POPULAR", "EARLY_DEPARTURE", "LATE_DEPARTURE"]
        if(isset($request->sort_by) && !empty($request->sort_by)){
            if(!empty($arr_available_buses)){
                $return  = [];
                switch($request->sort_by){
                    // case "RELEVANCE":
                    //     break;
                    // case "PRICE_HIGH_TO_LOW":
                    //     break;
                    // case "PRICE_LOW_TO_HIGH":
                    //     break;
                    // case "MOST_POPULAR":
                    //     break;
                    // case "EARLY_DEPARTURE":
                    //     break;
                    // case "LATE_DEPARTURE":
                    //     break;
                    default:
                        $return = $arr_available_buses;
                }
                return $return;
            }else{
                return $arr_available_buses;
            }    
        }else{
            return $arr_available_buses;
        }
    }
}
