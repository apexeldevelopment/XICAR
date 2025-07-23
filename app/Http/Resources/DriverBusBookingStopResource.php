<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 7/11/23
 * Time: 12:36 PM
 */

namespace App\Http\Resources;

use App\Models\BusBooking;
use App\Models\BusStop;
use App\Traits\MerchantTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverBusBookingStopResource extends JsonResource
{
    use MerchantTrait;

    public function toArray($data)
    {
        $string_file = $this->getStringFile($this->merchant_id);

        $pickups = [];
        $drops = [];
        $stopPoint = BusStop::find($this->detail_bus_stop_id);
        $bus_bookings = BusBooking::where("bus_booking_master_id", $this->id)->where(function ($q) use($stopPoint){
            $q->where("bus_stop_id", $stopPoint->id);
        })->get();
        foreach($bus_bookings as $bus_booking){
            array_push($pickups, array(
                "booking_id" => $bus_booking->id,
                "name" => $bus_booking->User->UserName,
                "phone" => $bus_booking->User->userPhone,
                "total_seats" => $bus_booking->total_seats,
            ));
        }

        $bus_bookings = BusBooking::where("bus_booking_master_id", $this->id)->where(function ($q) use($stopPoint){
            $q->where("end_bus_stop_id", $stopPoint->id);
        })->get();
        foreach($bus_bookings as $bus_booking){
            array_push($drops, array(
                "booking_id" => $bus_booking->id,
                "name" => $bus_booking->User->UserName,
                "phone" => $bus_booking->User->userPhone,
                "total_seats" => $bus_booking->total_seats,
            ));
        }

        return array(
            "booking_details" => array(
                "id" => $this->id,
                "from_address" => $this->BusRoute->StartPoint->Name,
                "to_adderss" => $this->BusRoute->EndPoint->Name,
            ),
            "bus_details" => array(
                "bus_name" => $this->Bus->busName($this->Bus),
                // "traveller_name" => isset($this->Bus->traveller_name) ? $this->Bus->traveller_name : "",
                "traveller_name" => isset($this->Bus->BusTraveller) ? $this->Bus->BusTraveller->getNameAttribute() : "Traveller Name",
                "traveller_description" => isset($this->Bus->BusTraveller) ? $this->Bus->BusTraveller->getDescriptionAttribute() : "Traveller Description",
                "ac_nonac" => $this->Bus->ac_nonac == 1 ? trans("$string_file.ac") : trans("$string_file.non_ac"),
            ),
            "bus_bookings" => array(
                "pickups" => $pickups,
                "drops" => $drops,
            )
        );
    }
}
