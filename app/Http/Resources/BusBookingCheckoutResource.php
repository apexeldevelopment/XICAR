<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 1/11/23
 * Time: 2:01 PM
 */

namespace App\Http\Resources;

use App\Http\Controllers\Helper\BookingDataController;
use App\Traits\MerchantTrait;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\BusPriceCard;
use App\Models\BusRouteMapping;
use App\Models\Bus;
use App\Models\CountryArea;
use DB;

class BusBookingCheckoutResource extends JsonResource
{
    use MerchantTrait;

    public function toArray($data)
    {

        $string_file = $this->getStringFile($this->merchant_id);
        $currency = $this->User->Country->isoCode;
        $bill_details = [];
        $per_seat_charges = 0;
        $charges_according_to_persons=0;
        $total_charges_with_tax=0;
        $with_tax = 0;
        if(!empty($data->area))
        {
            $tax_percentage  = DB::table('country_areas')
                ->whereIn('id', [$data->area])
                ->get();
        }
        $tax = 0;
        if($data->service_type_id == 5851)
        {
            $results = DB::table('bus_pickup_drop_point_bus_stop')
                ->whereIn('bus_pickup_drop_point_id', [$data->pickup_point_id, $data->drop_point_id])
                ->get();
            $pickup_point_id = isset($results[0]->bus_stop_id)?$results[0]->bus_stop_id:0;
            $drop_point_id   = isset($results[1]->bus_stop_id)?$results[1]->bus_stop_id:0;
            $bus = Bus::where("id", $data->bus_id)->whereHas("BusRouteMapping", function($q) use($data){
                $q->where("bus_route_id", $data->bus_route_id);
                $q->where("service_time_slot_detail_id", $data->service_time_slot_detail_id);
            })->with(["BusRouteMapping" => function($q) use($data){
                $q->where("bus_route_id", $data->bus_route_id);
                $q->where("service_time_slot_detail_id", $data->service_time_slot_detail_id);
            }])->first();

            $price_card = BusPriceCard::where(array(
                "merchant_id" => $data->merchant_id,
                "bus_route_id" => $data->bus_route_id,
                "vehicle_type_id" => $bus->vehicle_type_id,
            ))->with("StopPointsPrice")->first();

            if(!empty($price_card) && isset($pickup_point_id) && isset($drop_point_id)){
                $per_seat_charges = $price_card->base_fare;
                foreach($price_card->StopPointsPrice as $stop_price){
                    if($stop_price->id == $pickup_point_id)
                    {
                        $per_seat_charges += $stop_price->pivot->price;

                    }elseif($stop_price->id == $drop_point_id){
                        break;
                    }else{
                        $per_seat_charges += $stop_price->pivot->price;

                    }

                }
                if($price_card->BusRoute->EndPoint->id == $data->drop_point_id){
                    $per_seat_charges += $price_card->end_stop_fare;
                }
            }
            $charges_according_to_persons = $data->number_of_rider * $per_seat_charges;
            $tax = ($charges_according_to_persons * $tax_percentage[0]->tax)/100;

            $total_charges_with_tax = $charges_according_to_persons + $tax;
        }

        $tax = ($this->total_amount * $tax_percentage[0]->tax)/100;
        $with_tax = $this->total_amount + $tax;
        $seats_amount = isset($charges_according_to_persons) && $charges_according_to_persons !== 0
            ? $charges_according_to_persons
            : $this->total_amount;


        $seat_charges = [
            'name' => trans("$string_file.seat_charges"),
            'value' => $currency . ' ' . sprintf("%.2f", $seats_amount),
            'bold' => false,
        ];
        array_push($bill_details, $seat_charges);
        $tax = [
            'name' => trans("$string_file.tax"),
            'value' => $currency .' ' . sprintf("%.2f", $tax),
            'bold' => false,
        ];
        array_push($bill_details, $tax);
        $total = [
            'name' => trans("$string_file.total_charges"),
            'value' => $currency . ' ' . ($total_charges_with_tax ?: $with_tax),
            'bold' => true,
        ];
        array_push($bill_details, $total);

        $paymentMethods = $this->CountryArea->PaymentMethod;
        if(isset($this->User->Merchant->BusConfiguration) && $this->User->Merchant->BusConfiguration->cash_payment_enable  == 2){
            $paymentMethods= $paymentMethods->where("id","!=",1);
        }
//        $Payment_ids = array_pluck($paymentMethods, 'id');
//        $wallet_option = in_array(3, $Payment_ids) ? true : false;
//        $creditOption = in_array(2, $Payment_ids) ? true : false;
        $bookingData = new BookingDataController();
        $payment_methods = $bookingData->PaymentOption($paymentMethods, $this->user_id, $currency, 0, $this->total_amount);
        $packages = !empty($this->package_details) ? json_decode($this->package_details) : null; // for package delivery

        return array(
            "booking_details" => array(
                "id" => $this->id,
                "from_address" => $this->BusStop->Name,
                "to_adderss" => $this->EndBusStop->Name,
                "amount" => $currency." ".isset($charges_according_to_persons) && $charges_according_to_persons !== 0 ? strval($charges_according_to_persons) : strval($this->total_amount),
                "date" => date("Y-m-d", strtotime($this->booking_date)),
                "time" => date("H:i",strtotime($this->ServiceTimeSlotDetail->slot_time_text)),
                "total_seats" => $this->total_seats,
                "total_packages" => !empty($packages)? count($packages->package_amount_details) : 0, //for package delivery only
                "total_amount_with_tax" => strval($total_charges_with_tax) ?: strval($with_tax),
            ),
            "bus_details" => array(
                "bus_name" => $this->Bus->busName($this->Bus),
                // "traveller_name" => isset($this->Bus->traveller_name) ? $this->Bus->traveller_name : "",
                "traveller_name" => isset($this->Bus->BusTraveller) ? $this->Bus->BusTraveller->getNameAttribute() : "Traveller Name",
                "traveller_description" => isset($this->Bus->BusTraveller) ? $this->Bus->BusTraveller->getDescriptionAttribute() : "Traveller Description",
            ),
            "pickup_drop_details" => array(
                "pickup_point" => !empty($this->PickupPoint) ? $this->PickupPoint->Name : "",
                "drop_point" => !empty($this->DropPoint->Name) ? $this->DropPoint->Name : "",
            ),
            "seat_details" => !empty($this->seat_details) ? json_decode($this->seat_details) : [],
            "bill_details" => $bill_details,
            "payment_methods" => $payment_methods,
            "stop_points" => $this->stop_points,
        );
    }
}
