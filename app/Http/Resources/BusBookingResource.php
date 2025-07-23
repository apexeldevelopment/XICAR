<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 3/11/23
 * Time: 3:18 PM
 */

namespace App\Http\Resources;


use App\Traits\MerchantTrait;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use DB;



class BusBookingResource extends JsonResource
{
    use MerchantTrait;

    public function toArray($data)
    {
        $string_file = $this->getStringFile($this->merchant_id);
        $currency = $this->User->Country->isoCode;
        $country_id    = $this->User->Country->id;
        $tax_percentage = DB::table('country_areas')
            ->whereIn('country_id', [$country_id])
            ->get();
        $tax          = 0;
        $bill_details = [];
        $tax = ($this->per_seat_charge * $tax_percentage[0]->tax)/100;
        $seat_charges = [
            'name' => trans("$string_file.seat_charges"),
            'value' => $currency." ".sprintf("%.2f", $this->per_seat_charge),
            'bold' => false,
        ];
        array_push($bill_details, $seat_charges);
        $tax = [
            'name' => trans("$string_file.tax"),
            'value' => $currency." ".floatval($this->tax),
            'bold' => false,
        ];
        array_push($bill_details, $tax);
        $total = [
            'name' => trans("$string_file.total_charges"),
            'value' => $currency." ".floatval($this->total_amount),
            'bold' => true,
        ];
        array_push($bill_details, $total);
        $bus_booking_status = Config::get("custom.bus_booking_status");
        $seat_details = [];

        foreach($this->BusBookingDetail as $item){
            array_push($seat_details, array(
                "bus_booking_detail_id" => $item->id,
                "name" => $item->name,
                "age" => $item->age,
                "gender" => $item->gender == 1 ? trans("$string_file.male") : trans("$string_file.female"),
                "amount" => $item->amount,
                "seat_id" => $item->bus_seat_detail_id,
                "seat_number" => $item->BusSeatDetail->seat_no,
            ));
        }
        $package_details = [];
        if($this->booking_for == 2){
            foreach($this->BusBookingPackageDetail as $packages){
                $package_details[] =  [
                    "id" => $packages->id,
                    "bus_booking_id" => $packages->bus_booking_id,
                    "name" => $packages->name,
                    "quantity" => $packages->quantity,
                    "length" => round_number($packages->length,2),
                    "width" => round_number($packages->width, 2),
                    "height" => round_number($packages->height, 2),
                    "weight" => round_number($packages->weight, 2),
                    "amount" => round_number($packages->amount, 2),
                ];
            }
        }
        $start_date_time = $this->BusBookingMaster->booking_date." ".$this->BusBookingMaster->ServiceTimeSlotDetail->slot_time_text;

        $minutes_to_add = 0;
        $minutes_to_start = 0;
        foreach($this->BusBookingMaster->BusRoute->StopPoints->slice(1) as $point){
            $minutes_to_add += $point->pivot->time;
            if($this->pickup_point_id == $point->id){
                $minutes_to_start = $minutes_to_add;
            }
        }

        $minutes_to_add += $this->BusBookingMaster->BusRoute->StopPoints->last()->pivot->time;

        $ent_date_time = new \DateTime($start_date_time);
        $ent_date_time->add(new \DateInterval('PT' . $minutes_to_add . 'M'));

        $hours = floor($minutes_to_add / 60); // Get the number of whole hours
        $minutes = $minutes_to_add % 60; // Get the remainder of the hours

        $duration = sprintf ("%02d:%02d", $hours, $minutes);

        date_default_timezone_set($this->BusBookingMaster->BusRoute->CountryArea->timezone);
        $time_left_text = \Carbon\Carbon::parse($ent_date_time)->diffForhumans();

        $bus_location = NULL;
        if(isset($this->BusBookingMaster->Driver) && !empty($this->BusBookingMaster->Driver)){
            $bus_location = $this->BusBookingMaster->Driver;
        }
        $rating_images = [];
        if(isset($this->BusBookingRating) && !empty($this->BusBookingRating->rating_images)){
            $rating_imgs = explode(",",$this->BusBookingRating->rating_images);
            foreach($rating_imgs as $rating_img){
                $rating_images[] = get_image($rating_img, 'bus_booking_rating_image', $this->merchant_id, true, true);
            }
        }

        $arrival_time = Carbon::parse($this->BusBookingMaster->ServiceTimeSlotDetail->from_time);
        $start_from_pickup_time = $arrival_time->addMinutes($minutes_to_start);
        return array(
            "booking_details" => array(
                "id" => $this->id,
                "merchant_bus_booking_id" => $this->merchant_bus_booking_id,
                "bus_booking_master_id" => $this->bus_booking_master_id,
                "from_address" => $this->BusStop->Name,
                "to_adderss" => $this->EndBusStop->Name,
                "amount" => $currency." ".$this->total_amount,
                "date" => $this->BusBookingMaster->booking_date,
                "end_date" => $ent_date_time->format("Y-m-d"),
                "time" => $start_from_pickup_time->format("H:i"),
                "end_time" => $ent_date_time->format("H:i"),
                "duration" => $duration,
                "total_seats" => $this->total_seats,
                "total_packages" => count($this->BusBookingPackageDetail),
                "status"=>$this->status,
                "status" => $bus_booking_status[$this->status],
                "booking_status" => $this->status,
                "time_left_text" => $time_left_text, //"1 Hour Left",
                "booking_for"=>$this->booking_for,
            ),
            "user_contact_details" => json_decode($this->contact_details),
            "bus_details" => array(
                "bus_name" => $this->BusBookingMaster->Bus->busName($this->BusBookingMaster->Bus),
                // "traveller_name" => isset($this->BusBookingMaster->Bus->traveller_name) ? $this->BusBookingMaster->Bus->traveller_name : "",
                "traveller_name" => isset($this->BusBookingMaster->Bus->BusTraveller) ? $this->BusBookingMaster->Bus->BusTraveller->getNameAttribute() : "Traveller Name",
                "traveller_description" => isset($this->BusBookingMaster->Bus->BusTraveller) ? $this->BusBookingMaster->Bus->BusTraveller->getDescriptionAttribute() : "Traveller Description",
                "ac_nonac" => $this->BusBookingMaster->Bus->ac_nonac == 1 ? trans("$string_file.ac") : trans("$string_file.non_ac"),
                "bus_stop_id" =>  $this->bus_stop_id,
                "bus_route_id" => $this->BusBookingMaster->bus_route_id,
                "end_bus_stop_id" => $this->end_bus_stop_id,
            ),
            "pickup_drop_details" => array(
                "pickup_point" => !empty($this->PickupPoint->Name) ? $this->PickupPoint->Name : "",
                "drop_point" => !empty($this->DropPoint->Name) ? $this->DropPoint->Name : "",
            ),
            "seat_details" => $seat_details,
            "bill_details" => $bill_details,
            "package_details"=>$package_details,
            "payment_details" => array(
                "payment_method_id" => $this->payment_method_id,
                "payment_method" => !empty($this->payment_method_id) ? $this->PaymentMethod->MethodName($this->merchant_id) : "",
                "card_id" => null,
                "payment_status" => $this->payment_status,
            ),
            "bus_location" => array(
                "current_latitude" => !empty($bus_location) ? (string)$bus_location->current_latitude : "",
                "current_longitude" => !empty($bus_location) ? (string)$bus_location->current_longitude : "",
                "bearing" => !empty($bus_location) ? (string)$bus_location->bearing : "",
                "accuracy" => !empty($bus_location) ? (string)$bus_location->accuracy : "",
            ),
            "user_rating" => array(
                "rating_id" => isset($this->BusBookingRating)?$this->BusBookingRating->id: 0,
                "rating" => isset($this->BusBookingRating)?$this->BusBookingRating->user_rating:"",
                "review" => isset($this->BusBookingRating)?$this->BusBookingRating->user_comment:"",
                "images" => isset($this->BusBookingRating)?$rating_images:[],
                "can_rate" => isset($this->BusBookingRating)?false:true,
                "reasons" => isset($this->BusBookingRating)?json_decode($this->BusBookingRating->reasons,true):[],
                "provider_comment" => isset($this->BusBookingRating)?$this->BusBookingRating->provider_comments:"",
            )
        );
    }

}
