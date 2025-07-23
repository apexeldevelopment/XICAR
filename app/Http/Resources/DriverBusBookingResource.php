<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 7/11/23
 * Time: 12:26 PM
 */

namespace App\Http\Resources;

use App\Models\BusBooking;
use App\Traits\MerchantTrait;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class DriverBusBookingResource extends JsonResource
{
    use MerchantTrait;

    public function toArray($data)
    {
        $string_file = $this->getStringFile($this->merchant_id);
        $bus_booking_status = Config::get("custom.bus_booking_status");
        $stop_status_history = json_decode($this->stop_status_history);
        $stop_status_history = array_reverse($stop_status_history);
        $stop_ids = array_column($stop_status_history, 'stop_id');

        $stop_points = [];
        $time = Carbon::parse($this->ServiceTimeSlotDetail->slot_time_text);
        $time_route_formated = $time->format('H:i A');
        foreach($this->BusRoute->StopPoints as $stopPoint){
             $key = array_search($stopPoint->id, $stop_ids);

            $travelled = false;
            $travelled_timestamp = '';
            $in_passenger_count = 0;
            $out_passenger_count = 0;
            $total_passenger_count = 0;
            $in_package_count = 0;
            $out_package_count = 0;
            $total_package_count = 0;
            if($key!=''){
                $travelled = ($stop_status_history[$key]->is_travelled==1)?true:false;
                $travelled_timestamp = $stop_status_history[$key]->travelled_timestamp;
                $in_passenger_count = isset($stop_status_history[$key]->in_passenger_count)?$stop_status_history[$key]->in_passenger_count:0;
                $out_passenger_count = isset($stop_status_history[$key]->out_passenger_count)?$stop_status_history[$key]->out_passenger_count:0;
                $total_passenger_count = isset($stop_status_history[$key]->total_passenger_count)?$stop_status_history[$key]->total_passenger_count:0;
                $in_package_count = isset($stop_status_history[$key]->in_package_count)?$stop_status_history[$key]->in_package_count:0;
                $out_package_count = isset($stop_status_history[$key]->out_package_count)?$stop_status_history[$key]->out_package_count:0;
                $total_package_count = isset($stop_status_history[$key]->total_package_count)?$stop_status_history[$key]->total_package_count:0;
            }
            $newTime = $time->addMinutes($stopPoint->pivot->time);
            $time_route_formated = $newTime->format('H:i');
            $stop_points[] = array(
                "id" => $stopPoint->id,
                "name" => $stopPoint->Name,
                "start_time" => $time_route_formated,
                "in_passenger_count" => $in_passenger_count,
                "out_passenger_count" => $out_passenger_count,
                "total_passenger_count" => $total_passenger_count,
                "in_package_count" => $in_package_count,
                "out_package_count" => $out_package_count,
                "total_package_count" => $total_package_count,
                "travelled" => $travelled,
                "latitude" => $stopPoint->latitude,
                "longitude" => $stopPoint->longitude,
                "address" => $stopPoint->address,
            );
        }
       
         $key = array_search($this->BusRoute->EndPoint->id, $stop_ids);
        $travelled = false;
            $travelled_timestamp = '';
            if($key!=''){
                $travelled = ($stop_status_history[$key]->is_travelled==1)?true:false;
                $travelled_timestamp = $stop_status_history[$key]->travelled_timestamp;
            }

            $start_date_time = $this->booking_date." ".$this->ServiceTimeSlotDetail->slot_time_text;

            $minutes_to_add = 0;
            foreach($this->BusRoute->StopPoints->slice(1) as $point){
                $minutes_to_add += $point->pivot->time;
            }
            $minutes_to_add += $this->BusRoute->StopPoints->last()->pivot->time;
    
            $ent_date_time = new \DateTime($start_date_time);
            $ent_date_time->add(new \DateInterval('PT' . $minutes_to_add . 'M'));
    
            $hours = floor($minutes_to_add / 60); // Get the number of whole hours
            $minutes = $minutes_to_add % 60; // Get the remainder of the hours
    
            $duration = sprintf ("%02d:%02d", $hours, $minutes);
    
            date_default_timezone_set($this->Driver->CountryArea->timezone);
            $time_left_text = \Carbon\Carbon::parse($ent_date_time)->diffForhumans();

        $key = array_search($this->BusRoute->EndPoint->id, $stop_ids);
        if($key!=''){
            $travelled = $stop_status_history[$key]->is_travelled==1;
            $in_passenger_count = $stop_status_history[$key]->in_passenger_count ?? 0;
            $out_passenger_count = $stop_status_history[$key]->out_passenger_count ?? 0;
            $total_passenger_count = $stop_status_history[$key]->total_passenger_count ?? 0;
            $in_package_count = $stop_status_history[$key]->in_package_count ?? 0;
            $out_package_count = $stop_status_history[$key]->out_package_count ?? 0;
            $total_package_count = $stop_status_history[$key]->total_package_count ?? 0;
        }

            $stop_points[] = array(
                "id" => $this->BusRoute->EndPoint->id,
                "name" => $this->BusRoute->EndPoint->Name,
                "start_time" => $ent_date_time->format("H:i"),
                "in_passenger_count" => $in_passenger_count,
                "out_passenger_count" => $out_passenger_count,
                "total_passenger_count" => $total_passenger_count,
                "in_package_count" => $in_package_count,
                "out_package_count" => $out_package_count,
                "total_package_count" => $total_package_count,
                "travelled" => $travelled,
                "latitude" => $stopPoint->latitude,
                "longitude" => $stopPoint->longitude,
                "address" => $stopPoint->address,
            );

        $bottom_button_text = "";
        $bottom_button_is_active = false;
        $bottom_button_is_disable = true;

        if($this->status == 1){ // New Booking
            $bottom_button_text = "Start Journey";
            $bottom_button_is_active = true;
            $bottom_button_is_disable = false;
        }elseif($this->status == 2){
            $bottom_button_text = "End Journey";
            $bottom_button_is_active = true;
            $bottom_button_is_disable = false;
        }

        $in_passengers = [];
        $out_passengers = [];
        $in_package = [];
        $out_package = [];
        if(isset($this->find_bus_stop_id) && !empty($this->find_bus_stop_id)){
            $bookings = BusBooking::where("bus_booking_master_id", $this->id)->where(function($q){
                $q->where("bus_stop_id", $this->find_bus_stop_id)->orWhere("end_bus_stop_id", $this->find_bus_stop_id);
            })->get();
            foreach($bookings as $booking){

                $passengers = [];
                foreach($booking->BusBookingDetail as $detail){
                    array_push($passengers, array(
                        "name" => $detail->name,
                        "age" => $detail->age,
                        "gender" => $detail->gender == 1 ? trans("$string_file.male") : trans("$string_file.female"),
                        "seat" => $detail->BusSeatDetail->seat_no,
                    ));
                }

                $packages =[];
                if($booking->booking_for == 2){
                    foreach($booking->BusBookingPackageDetail as $package){
                        $packages[] = array(
                            "id" => $package->id,
                            "name" => $package->name,
                            "quantity" => $package->quantity,
                            "length" => round_number($package->length, 2),
                            "width" =>  round_number($package->width, 2),
                            "height" => round_number($package->height, 2),
                            "weight" => round_number($package->weight, 2),
                            "amount" => round_number($package->amount, 2),
                        );
                    }
                }

                switch ($booking->booking_for){
                    case 1:
                        if($booking->bus_stop_id == $this->find_bus_stop_id) {
                            array_push($in_passengers, array(
                                "id" => $booking->id,
                                "booking_id" => $booking->merchant_bus_booking_id,
                                "user_name" => $booking->User->UserName,
                                "user_phone" => $booking->User->UserPhone,
                                "total_seats" => $booking->total_seats,
                                "total_amount" => ($booking->PaymentMethod->payment_method_type == 1) ? $booking->total_amount : "",
                                "payment_method" => $booking->PaymentMethod->MethodName($this->merchant_id),
                                "passengers" => $passengers
                            ));
                        }else{
                            array_push($out_passengers, array(
                                "id" => $booking->id,
                                "booking_id" => $booking->merchant_bus_booking_id,
                                "user_name" => $booking->User->UserName,
                                "user_phone" => $booking->User->UserPhone,
                                "total_seats" => $booking->total_seats,
                                "total_amount" => ($booking->PaymentMethod->payment_method_type == 1) ? $booking->total_amount : "",
                                "payment_method" => $booking->PaymentMethod->MethodName($this->merchant_id),
                                "passengers" => $passengers
                            ));
                        }
                        break;
                    case 2:
                        if($booking->bus_stop_id == $this->find_bus_stop_id) {
                            array_push($in_package, array(
                                "id" => $booking->id,
                                "booking_id" => $booking->merchant_bus_booking_id,
                                "user_name" => $booking->User->UserName,
                                "user_phone" => $booking->User->UserPhone,
                                "total_packages" => count($booking->BusBookingPackageDetail),
                                "total_amount" => ($booking->PaymentMethod->payment_method_type == 1) ? $booking->total_amount : "",
                                "payment_method" => $booking->PaymentMethod->MethodName($this->merchant_id),
                                "package_receiver_details"=>!empty($booking->package_receiver_details)? json_decode($booking->package_receiver_details): (object)"",
                                "package_details" => $packages,
                            ));
                        }else{
                            array_push($out_package, array(
                                "id" => $booking->id,
                                "booking_id" => $booking->merchant_bus_booking_id,
                                "user_name" => $booking->User->UserName,
                                "user_phone" => $booking->User->UserPhone,
                                "total_packages" => count($booking->BusBookingPackageDetail),
                                "total_amount" => ($booking->PaymentMethod->payment_method_type == 1) ? $booking->total_amount : "",
                                "payment_method" => $booking->PaymentMethod->MethodName($this->merchant_id),
                                "package_receiver_details"=>!empty($booking->package_receiver_details)? json_decode($booking->package_receiver_details): (object)"",
                                "package_details" => $packages
                            ));
                        }
                        break;
                }
            }
        }
        
        $bus_stop_detail = array(
            "in_passengers" => $in_passengers,
            "out_passengers" => $out_passengers
        );
        $bus_package_detail = array(
            "in_package" => $in_package,
            "out_package" => $out_package
        );

        return array(
            "booking_details" => array(
                "id" => $this->id,
                "from_address" => $this->BusRoute->StartPoint->Name,
                "to_adderss" => $this->BusRoute->EndPoint->Name,
                "distance"   => round_number($this->haversineGreatCircleDistance($this->BusRoute->StartPoint->latitude,$this
->BusRoute->StartPoint->longitude,$this->BusRoute->EndPoint->latitude,$this->BusRoute->EndPoint->longitude,$earthRadius = 6371),2),
                "date" => $this->booking_date,
                "end_date" => $ent_date_time->format("Y-m-d"),
                "time" => date("H:i", strtotime($this->ServiceTimeSlotDetail->slot_time_text)),
                "end_time" => $ent_date_time->format("H:i"),
                "duration" => $duration,
                "status" => $this->status,
                "status" => $bus_booking_status[$this->status],
                "booking_status" => $this->status,
                "time_left_text" => $time_left_text, //"1 Hour Left"
            ),
            "bus_details" => array(
                "bus_name" => $this->Bus->busName($this->Bus),
                // "traveller_name" => isset($this->Bus->traveller_name) ? $this->Bus->traveller_name : "",
                "traveller_name" => isset($this->Bus->BusTraveller) ? $this->Bus->BusTraveller->getNameAttribute() : "Traveller Name",
                "traveller_description" => isset($this->Bus->BusTraveller) ? $this->Bus->BusTraveller->getDescriptionAttribute() : "Traveller Description",
                "ac_nonac" => $this->Bus->ac_nonac == 1 ? trans("$string_file.ac") : trans("$string_file.non_ac"),
                "total_seats" => count($this->Bus->BusSeatDetail),
            ),
            "bottom_button" => array(
                "text" => $bottom_button_text,
                "is_active" => $bottom_button_is_active,
                "is_disable" => $bottom_button_is_disable
            ),
            "stop_points" => $stop_points,
            "bus_stop_details" => $bus_stop_detail,
            "bus_package_detail"=>$bus_package_detail,
        );
    }

    public function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius) {
            // Convert from degrees to radians
        
            $latFrom = deg2rad(round($latitudeFrom,2));
            $lonFrom = deg2rad(round($longitudeFrom,2));
            $latTo = deg2rad(round($latitudeTo,2));
            $lonTo = deg2rad(round($longitudeTo,2));
        
            // Haversine formula
            $lonDelta = $lonTo - $lonFrom;
            $latDelta = $latTo - $latFrom;
        
            $a = sin($latDelta / 2) * sin($latDelta / 2) +
                 cos($latFrom) * cos($latTo) *
                 sin($lonDelta / 2) * sin($lonDelta / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            return $earthRadius * $c; // Distance in kilometers
        }
}
