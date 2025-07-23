<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 17/10/23
 * Time: 5:42 PM
 */

namespace App\Http\Controllers\Services;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentMethods\Payment;
use App\Models\BusBooking;
use App\Models\BusBookingCheckout;
use App\Models\BusBookingDetail;
use App\Models\BusBookingMaster;
use App\Models\BusPriceCard;
use App\Models\Bus;
use App\Models\BusBookingPackageDetail;
use App\Models\Onesignal;
use App\Models\User;
use App\Traits\BusStopTrait;
use App\Traits\BusBookingTrait;
use App\Models\BusRoute;
use App\Models\ServiceTimeSlotDetail;
use Carbon\Carbon;
use DB;


class BusServiceController extends Controller
{
    use BusStopTrait, BusBookingTrait;
    public static function IntracityCheckout($request, $user, $string_file){
        try{

            $per_seat_charges = 0;
            $results = DB::table('bus_pickup_drop_point_bus_stop')
                ->whereIn('bus_pickup_drop_point_id', [$request->pickup_point_id, $request->drop_point_id])
                ->get();
            $pickup_point_id = isset($results[0]->bus_stop_id)?$results[0]->bus_stop_id:"";
            $drop_point_id   = isset($results[1]->bus_stop_id)?$results[1]->bus_stop_id:"";
            $bus = Bus::where("id", $request->bus_id)->whereHas("BusRouteMapping", function($q) use($request){
                $q->where("bus_route_id", $request->bus_route_id);
                $q->where("service_time_slot_detail_id", $request->service_time_slot_detail_id);
            })->with(["BusRouteMapping" => function($q) use($request){
                $q->where("bus_route_id", $request->bus_route_id);
                $q->where("service_time_slot_detail_id", $request->service_time_slot_detail_id);
            }])->first();

            $price_card = BusPriceCard::where(array(
                "merchant_id" => $request->merchant_id,
                "bus_route_id" => $request->bus_route_id,
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

            }
            $checkout = BusBookingCheckout::where(array(
                "merchant_id" => $user->merchant_id,
                "user_id" => $user->id,
                "segment_id" => $request->segment_id,
                "service_type_id" => $request->service_type_id,
                "bus_id" => $request->bus_id,
                "bus_route_id" => $request->bus_route_id,
                "booking_date" => $request->booking_date,
                "service_time_slot_detail_id" => $request->service_time_slot_detail_id,
            ))->first();
            if(empty($checkout)){
                $checkout = new BusBookingCheckout();
                $checkout->merchant_id = $user->merchant_id;
                $checkout->user_id = $user->id;
                $checkout->segment_id = $request->segment_id;
                $checkout->service_type_id = $request->service_type_id;
                $checkout->bus_id = $request->bus_id;
                $checkout->bus_route_id = $request->bus_route_id;
                $checkout->service_time_slot_detail_id = $request->service_time_slot_detail_id;
                $checkout->booking_date = $request->booking_date;
            }
            $checkout->country_area_id = $request->area;
            $checkout->bus_stop_id = $request->bus_stop_id;
            $checkout->end_bus_stop_id = $request->end_bus_stop_id;
            $checkout->pickup_point_id = $request->pickup_point_id;
            $checkout->drop_point_id = $request->drop_point_id;
//            $checkout->total_seats = $request->number_of_rider;
            $checkout->booking_for = ($request->booking_for == "PASSENGER") ? 1: 2;
            if($request->booking_for == "PACKAGE"){
                $checkout->package_receiver_details = $request->package_receiver_details;
                $checkout->total_seats = 0;
            }
            else{
                $checkout->package_receiver_details = null;
                $checkout->total_seats = $request->number_of_rider;
            }
            $checkout->user_email  = $request->email;
            $checkout->user_phone  = $request->phone_number;
            $checkout->coordinates = json_encode(array(
                "latitude" => $request->latitude,
                "longitude" => $request->longitude,
                "drop_latitude" => $request->drop_latitude,
                "drop_longitude" => $request->drop_longitude,
            ));
            $checkout->seat_details = isset($request->seat_details) ? $request->seat_details : NULL;
//            $checkout->total_amount = floatval($per_seat_charges);
            if($request->booking_for == "PACKAGE"){
                $price_array = (new BusServiceController)->calculateShipping($request, $user->merchant_id);
                $checkout->package_details = isset($price_array) ? json_encode($price_array) : "";
                $checkout->total_amount = $price_array['all_packages_total_amount'];
            }
            else{
                $checkout->total_amount = floatval($per_seat_charges) * $checkout->total_seats;
            }
            $checkout->save();
//            return array("bus_booking_checkout_id" => $checkout->id);

            $checkout->stop_points = (new BusServiceController)->getStopPointsAccoringToRoute($request);
            return $checkout;
        }catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }
    }

    public static function IntercityCheckout($request, $user, $string_file){
        try{
            $checkout = BusBookingCheckout::where(array(
                "merchant_id" => $user->merchant_id,
                "user_id" => $user->id,
                "segment_id" => $request->segment_id,
                "service_type_id" => $request->service_type_id,
                "bus_id" => $request->bus_id,
                "bus_route_id" => $request->bus_route_id,
                "booking_date" => $request->booking_date,
                "service_time_slot_detail_id" => $request->service_time_slot_detail_id,
            ))->first();
            if(empty($checkout)){
                $checkout = new BusBookingCheckout();
                $checkout->merchant_id = $user->merchant_id;
                $checkout->user_id = $user->id;
                $checkout->segment_id = $request->segment_id;
                $checkout->service_type_id = $request->service_type_id;
                $checkout->bus_id = $request->bus_id;
                $checkout->bus_route_id = $request->bus_route_id;
                $checkout->service_time_slot_detail_id = $request->service_time_slot_detail_id;
                $checkout->booking_date = $request->booking_date;
            }
            $checkout->country_area_id = $request->area;
            $checkout->bus_stop_id = $request->bus_stop_id;
            $checkout->end_bus_stop_id = $request->end_bus_stop_id;
            $checkout->pickup_point_id = $request->pickup_point_id;
            $checkout->drop_point_id = $request->drop_point_id;
//            $checkout->total_seats = count(json_decode($request->seat_details, true));
            if($request->booking_for == "PACKAGE"){
                $checkout->package_receiver_details = $request->package_receiver_details;
                $checkout->total_seats = 0;
            }
            else{
                $checkout->package_receiver_details = null;
                $checkout->total_seats = isset($request->seat_details)? count(json_decode($request->seat_details, true)): $request->number_of_rider;
            }
            $checkout->user_email  = $request->email;
            $checkout->user_phone  = $request->phone_number;
            $checkout->coordinates = json_encode(array(
                "latitude" => $request->latitude,
                "longitude" => $request->longitude,
                "drop_latitude" => $request->drop_latitude,
                "drop_longitude" => $request->drop_longitude,
            ));
            $checkout->seat_details = isset($request->seat_details) ? $request->seat_details : "";
            $checkout->booking_for = ($request->booking_for == "PASSENGER") ? 1: 2;
//            $checkout->total_amount = $request->total_amount;
            if($request->booking_for == "PACKAGE"){
                $price_array = (new BusServiceController)->calculateShipping($request, $user->merchant_id);
                $checkout->package_details = isset($price_array) ? json_encode($price_array) : "";
                $checkout->total_amount = $price_array['all_packages_total_amount'];
            }
            else{

                if(empty($request->total_amount)){
                    $bus = Bus::where("id", $request->bus_id)->whereHas("BusRouteMapping", function($q) use($request){
                        $q->where("bus_route_id", $request->bus_route_id);
                        $q->where("service_time_slot_detail_id", $request->service_time_slot_detail_id);
                    })->with(["BusRouteMapping" => function($q) use($request){
                        $q->where("bus_route_id", $request->bus_route_id);
                        $q->where("service_time_slot_detail_id", $request->service_time_slot_detail_id);
                    }])->first();
            
                    $price_card = BusPriceCard::where(array(
                        "merchant_id" => $request->merchant_id,
                        "bus_route_id" => $request->bus_route_id,
                        "vehicle_type_id" => $bus->vehicle_type_id,
                    ))->with("StopPointsPrice")->first();
        
                    $per_seat_charges = 0;
                    if(!empty($price_card) && isset($request->pickup_point_id) && isset($request->drop_point_id)){
                        $per_seat_charges = $price_card->base_fare;
                        foreach($price_card->StopPointsPrice as $stop_price){
                            if($stop_price->id == $request->pickup_point_id)
                                $per_seat_charges += $stop_price->pivot->price;
                            elseif($stop_price->id == $request->drop_point_id)
                                break;
                            else
                                $per_seat_charges += $stop_price->pivot->price;
                        }
                    }
                    $checkout->total_amount =  floatval($per_seat_charges) * $checkout->total_seats;
                }
                else{
                    $checkout->total_amount =  $request->total_amount;
                }
            }
            $checkout->save();
//            return array("bus_booking_checkout_id" => $checkout->id);

            $checkout->stop_points = (new BusServiceController)->getStopPointsAccoringToRoute($request);
            return $checkout;
        }catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }
    }

    public  function IntracityConfirm(BusBookingCheckout $bookingCheckout, $request, $user, $string_file){
        try{

            if(!empty($bookingCheckout->country_area_id))
            {
                $tax_percentage  = DB::table('country_areas')
                    ->whereIn('id', [$bookingCheckout->country_area_id])
                    ->get();
            }
            $tax = ($bookingCheckout->total_amount * $tax_percentage[0]->tax)/100;
            $amount_with_tax = $bookingCheckout->total_amount + $tax;
            $master_bus_booking = BusBookingMaster::where(array(
                "merchant_id" => $bookingCheckout->merchant_id,
                "segment_id" => $bookingCheckout->segment_id,
                "service_type_id" => $bookingCheckout->service_type_id,
                "bus_id" => $bookingCheckout->bus_id,
                "bus_route_id" => $bookingCheckout->bus_route_id,
                "service_time_slot_detail_id" => $bookingCheckout->service_time_slot_detail_id,
                "booking_date" => $bookingCheckout->booking_date,
            ))->first();
            if(empty($master_bus_booking)){
                // $stops = $this->getStopPoints($bookingCheckout->merchant_id, $bookingCheckout->bus_route_id);
                // $stop_status = [];
                // foreach ($stops as $key=>$stop){
                //     $stop_status[] = [
                //         'stop_id' => $key,
                //         'is_travelled' => 0, //0:no, 1:yes
                //         'travelled_timestamp' => '',
                //     ];
                // }

                $master_bus_booking = new BusBookingMaster();
                $master_bus_booking->merchant_id = $bookingCheckout->merchant_id;
                $master_bus_booking->segment_id = $bookingCheckout->segment_id;
                $master_bus_booking->service_type_id = $bookingCheckout->service_type_id;
                $master_bus_booking->bus_id = $bookingCheckout->bus_id;
                $master_bus_booking->bus_route_id = $bookingCheckout->bus_route_id;
                $master_bus_booking->service_time_slot_detail_id = $bookingCheckout->service_time_slot_detail_id;
                $master_bus_booking->booking_date = $bookingCheckout->booking_date;
                $master_bus_booking->save();

                $stop_status = [];
                foreach($master_bus_booking->BusRoute->StopPoints as $stopPoint){

                    array_push($stop_status, array(
                        "stop_id" => $stopPoint->id,
                        "is_travelled" => 0,
                        "travelled_timestamp" => "",
                        "in_passenger_count" => 0,
                        "out_passenger_count" => 0,
                        "total_passenger_count" => 0,
                        "in_package_count" => 0,
                        "out_package_count" => 0,
                        "total_package_count" => 0,
                    ));

                }

                array_push($stop_status, array(
                    "stop_id" => $master_bus_booking->BusRoute->EndPoint->id,
                    "is_travelled" => 0,
                    "travelled_timestamp" => "",
                    "in_passenger_count" => 0,
                    "out_passenger_count" => 0,
                    "total_passenger_count" => 0,
                    "in_package_count" => 0,
                    "out_package_count" => 0,
                    "total_package_count" => 0,
                ));
                //                $stop_status = array_reverse($stop_status);
                $master_bus_booking->stop_status_history = json_encode($stop_status);
                $master_bus_booking->save();
            }
            $contact_details = ["user_email"=>$bookingCheckout->user_email,"user_phone_number"=>$bookingCheckout->user_phone];
            $bus_booking = new BusBooking();
            $bus_booking->merchant_id = $bookingCheckout->merchant_id;
            $bus_booking->user_id = $user->id;
            $bus_booking->country_area_id = $bookingCheckout->country_area_id;
            $bus_booking->bus_booking_master_id = $master_bus_booking->id;
            $bus_booking->bus_stop_id = $bookingCheckout->bus_stop_id;
            $bus_booking->end_bus_stop_id = $bookingCheckout->end_bus_stop_id;
            $bus_booking->pickup_point_id = $bookingCheckout->pickup_point_id;
            $bus_booking->drop_point_id = $bookingCheckout->drop_point_id;
            $bus_booking->contact_details = json_encode($contact_details);
            $bus_booking->total_seats = $bookingCheckout->total_seats;
            $bus_booking->booking_for = $bookingCheckout->booking_for;
            $bus_booking->package_receiver_details = $bookingCheckout->package_receiver_details;
            $bus_booking->coordinates = $bookingCheckout->coordinates;
            $bus_booking->tax = $tax;
            $bus_booking->per_seat_charge = $bookingCheckout->total_amount;


            $discount_amount = 0;
            $promocode = "";
            if ($request->promo_code) {
                $check_promo_code = $this->checkPromoCode($request);
                if (isset($check_promo_code['status']) && $check_promo_code['status'] == true) {
                    $promo_code = $check_promo_code['promo_code'];

                    if (!empty($promo_code->id)) {
                        if ($promo_code->promoCode == "FIRSTDELFREE") {
                            //                    $discount_amount = $delivery_charge;
                        } else {
                            $promo_details = $promo_code;
                            // flat discount promo_code_value_type ==1
                            $discount_amount = $promo_details->promo_code_value;
                            if ($promo_details->promo_code_value_type == 2) {
                                // percentage discount promo_code_value_type == 2
                                $promoMaxAmount = $promo_details->promo_percentage_maximum_discount;
                                $discount_amount = ($bookingCheckout->total_amount * $discount_amount) / 100;
                                $discount_amount = !empty($promoMaxAmount) && ($discount_amount > $promoMaxAmount) ? $promoMaxAmount : $discount_amount;
                            }
                        }
                        $promocode = $promo_code->promoCode;
                        $bus_booking->promo_code_id = $promo_code->id;
                    }

                }
            }

            $bus_booking->total_amount = floatval($amount_with_tax);
            $bus_booking->discount = $discount_amount;
            $bus_booking->payment_method_id = $request->payment_method_id;
            $bus_booking->save();

            $notification_type = ($bus_booking->booking_for == 1)? "BUS_BOOKING_CONFIRM" : "BUS_BOOKING_DELIVERY_CONFIRM";
            $this->notifyBusBookingUser($bus_booking, $notification_type);

            $final_amount_paid = $amount_with_tax - $discount_amount;
            $passenger_count = 0;
            for ($seat = 1; $seat <= $bookingCheckout->total_seats; $seat++) {
                $passenger_count = $passenger_count+1;
            }
            // foreach(json_decode($bookingCheckout->seat_details, true) as $seat_detail){
            //     $detail = new BusBookingDetail();
            //     $detail->bus_booking_id = $bus_booking->id;
            //     $detail->bus_seat_detail_id = $seat_detail['bus_seat_detail_id'];
            //     $detail->name = $seat_detail['name'];
            //     $detail->age = $seat_detail['age'];
            //     $detail->gender = $seat_detail['gender'];
            //     $detail->amount = $seat_detail['seat_charges'];
            //     $detail->save();
            //     $passenger_count = $passenger_count+1;
            // }
            $package_count = 0;
            if($bus_booking->booking_for == 2){
                $package_details = json_decode($bookingCheckout->package_details);
                foreach($package_details->package_amount_details as $key => $package_detail){
                    $detail = new BusBookingPackageDetail();
                    $detail->bus_booking_id = $bus_booking->id;
                    $detail->name = $package_detail->name;
                    $detail->quantity = $package_detail->quantity;
                    $detail->length = $package_detail->length;
                    $detail->width = $package_detail->width;
                    $detail->height = $package_detail->height;
                    $detail->weight = $package_detail->weight;
                    $detail->amount = $package_detail->amount;
                    $detail->save();
                    $package_count = $package_count+1;
                }
            }
            else if(!empty($bookingCheckout->seat_details)){
                foreach(json_decode($bookingCheckout->seat_details, true) as $seat_detail){
                    $detail = new BusBookingDetail();
                    $detail->bus_booking_id = $bus_booking->id;
                    $detail->bus_seat_detail_id = $seat_detail['bus_seat_detail_id'];
                    $detail->name = $seat_detail['name'];
                    $detail->age = $seat_detail['age'];
                    $detail->gender = $seat_detail['gender'];
                    $detail->amount = $seat_detail['seat_charges'];
                    $detail->save();
                    $passenger_count = $passenger_count+1;
                }
            }


            $stop_status_history = json_decode($master_bus_booking->stop_status_history);
            $stop_ids = array_column($stop_status_history, 'stop_id');
            $in_key = array_search($bookingCheckout->bus_stop_id, $stop_ids);
            if($in_key!=""){
                $stop_status_history[$in_key]->in_passenger_count=$stop_status_history[$in_key]->in_passenger_count+$passenger_count;
                $stop_status_history[$in_key]->in_package_count=$stop_status_history[$in_key]->in_package_count+$package_count;

            }
            $out_key = array_search($bookingCheckout->end_bus_stop_id, $stop_ids);
            if($out_key!=""){
                $stop_status_history[$out_key]->out_passenger_count=$stop_status_history[$out_key]->out_passenger_count+$passenger_count;
                $stop_status_history[$out_key]->out_package_count=$stop_status_history[$out_key]->out_package_count+$package_count;
            }
            foreach($stop_status_history as $key=>$stop_status){
                if($key>$in_key && $key<=$out_key && ($key != count($stop_status_history)-1)){
                    $stop_status_history[$key]->total_passenger_count=$stop_status_history[$key]->total_passenger_count+$passenger_count;
                    $stop_status_history[$key]->total_package_count=$stop_status_history[$key]->total_package_count+$package_count;
                }
            }
            $master_bus_booking->stop_status_history = json_encode($stop_status_history);
            $master_bus_booking->save();
            // Make Payment
            $payment = new Payment();
            if ($bus_booking->payment_method_id != 1) {
                $array_param = array(
                    'bus_booking_id' => $bus_booking->id,
                    'payment_method_id' => $bus_booking->payment_method_id,
//                    'amount' => $bus_booking->total_amount,
                    'amount' => $final_amount_paid,
                    'user_id' => $user->id,
//                    'card_id' => $bus_booking->card_id,
                    'order_name' => $bus_booking->id,
                    'currency' => $user->Country->isoCode,
                );
                if($payment->MakePayment($array_param)){
                    $bus_booking->payment_status = 1;
                    $bus_booking->save();
                }else{
                    throw new \Exception(trans("$string_file.payment_failed"));
                }
            }
            return array("bus_booking_id" => $bus_booking->id);
        }catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }
    }

    public function IntercityConfirm(BusBookingCheckout $bookingCheckout, $request, $user, $string_file){
        try{
            if(!empty($bookingCheckout->country_area_id))
            {
                $tax_percentage  = DB::table('country_areas')
                    ->whereIn('id', [$bookingCheckout->country_area_id])
                    ->get();
            }
            $tax = ($bookingCheckout->total_amount * $tax_percentage[0]->tax)/100;
            $amount_with_tax = $bookingCheckout->total_amount + $tax;
            $master_bus_booking = BusBookingMaster::where(array(
                "merchant_id" => $bookingCheckout->merchant_id,
                "segment_id" => $bookingCheckout->segment_id,
                "service_type_id" => $bookingCheckout->service_type_id,
                "bus_id" => $bookingCheckout->bus_id,
                "bus_route_id" => $bookingCheckout->bus_route_id,
                "service_time_slot_detail_id" => $bookingCheckout->service_time_slot_detail_id,
                "booking_date" => $bookingCheckout->booking_date,
            ))->first();
            if(empty($master_bus_booking)){
                // $stops = $this->getStopPoints($bookingCheckout->merchant_id, $bookingCheckout->bus_route_id);
                // $stop_status = [];
                // foreach ($stops as $key=>$stop){
                //     $stop_status[] = [
                //         'stop_id' => $key,
                //         'is_travelled' => 0, //0:no, 1:yes
                //         'travelled_timestamp' => '',
                //     ];
                // }

                $master_bus_booking = new BusBookingMaster();
                $master_bus_booking->merchant_id = $bookingCheckout->merchant_id;
                $master_bus_booking->segment_id = $bookingCheckout->segment_id;
                $master_bus_booking->service_type_id = $bookingCheckout->service_type_id;
                $master_bus_booking->bus_id = $bookingCheckout->bus_id;
                $master_bus_booking->bus_route_id = $bookingCheckout->bus_route_id;
                $master_bus_booking->service_time_slot_detail_id = $bookingCheckout->service_time_slot_detail_id;
                $master_bus_booking->booking_date = $bookingCheckout->booking_date;
                $master_bus_booking->save();

                $stop_status = [];
                foreach($master_bus_booking->BusRoute->StopPoints as $stopPoint){

                    $stop_status[] = array(
                        "stop_id" => $stopPoint->id,
                        "is_travelled" => 0,
                        "travelled_timestamp" => "",
                        "in_passenger_count" => 0,
                        "out_passenger_count" => 0,
                        "total_passenger_count" => 0,
                        "in_package_count" => 0,
                        "out_package_count" => 0,
                        "total_package_count" => 0,
                    );

                }

                $stop_status[] = array(
                    "stop_id" => $master_bus_booking->BusRoute->EndPoint->id,
                    "is_travelled" => 0,
                    "travelled_timestamp" => "",
                    "in_passenger_count" => 0,
                    "out_passenger_count" => 0,
                    "total_passenger_count" => 0,
                    "in_package_count" => 0,
                    "out_package_count" => 0,
                    "total_package_count" => 0,
                );
//                $stop_status = array_reverse($stop_status);
                $master_bus_booking->stop_status_history = json_encode($stop_status);
                $master_bus_booking->save();
            }
            $contact_details = ["user_email"=>$bookingCheckout->user_email,"user_phone_number"=>$bookingCheckout->user_phone];
            $bus_booking = new BusBooking();
            $bus_booking->merchant_id = $bookingCheckout->merchant_id;
            $bus_booking->user_id = $user->id;
            $bus_booking->country_area_id = $bookingCheckout->country_area_id;
            $bus_booking->bus_booking_master_id = $master_bus_booking->id;
            $bus_booking->bus_stop_id = $bookingCheckout->bus_stop_id;
            $bus_booking->end_bus_stop_id = $bookingCheckout->end_bus_stop_id;
            $bus_booking->pickup_point_id = $bookingCheckout->pickup_point_id;
            $bus_booking->drop_point_id = $bookingCheckout->drop_point_id;
            $bus_booking->contact_details = json_encode($contact_details);
            $bus_booking->total_seats = $bookingCheckout->total_seats;
            $bus_booking->booking_for = $bookingCheckout->booking_for;
            $bus_booking->package_receiver_details = $bookingCheckout->package_receiver_details;
            $bus_booking->coordinates = $bookingCheckout->coordinates;
            $bus_booking->tax = $tax;
            $bus_booking->per_seat_charge = floatval($bookingCheckout->total_amount);


            $discount_amount = 0;
            $promocode = "";
            if ($request->promo_code) {
                $check_promo_code = $this->checkPromoCode($request);
                if (isset($check_promo_code['status']) && $check_promo_code['status'] == true) {
                    $promo_code = $check_promo_code['promo_code'];

                    if (!empty($promo_code->id)) {
                        if ($promo_code->promoCode == "FIRSTDELFREE") {
                            //                    $discount_amount = $delivery_charge;
                        } else {
                            $promo_details = $promo_code;
                            // flat discount promo_code_value_type ==1
                            $discount_amount = $promo_details->promo_code_value;
                            if ($promo_details->promo_code_value_type == 2) {
                                // percentage discount promo_code_value_type == 2
                                $promoMaxAmount = $promo_details->promo_percentage_maximum_discount;
                                $discount_amount = ($bookingCheckout->total_amount * $discount_amount) / 100;
                                $discount_amount = !empty($promoMaxAmount) && ($discount_amount > $promoMaxAmount) ? $promoMaxAmount : $discount_amount;
                            }
                        }
                        $promocode = $promo_code->promoCode;
                        $bus_booking->promo_code_id = $promo_code->id;
                    }

                }
            }

            $bus_booking->total_amount = floatval($amount_with_tax);
            $bus_booking->discount = $discount_amount;
            $bus_booking->payment_method_id = $request->payment_method_id;
            $bus_booking->save();
            $notification_type = ($bus_booking->booking_for == 1)? "BUS_BOOKING_CONFIRM" : "BUS_BOOKING_DELIVERY_CONFIRM";
            $this->notifyBusBookingUser($bus_booking, $notification_type);
            $final_amount_paid = $amount_with_tax - $discount_amount;
            $passenger_count = 0;
            $package_count = 0;
            if($bookingCheckout->booking_for == 1){
                if(!empty($bookingCheckout->seat_details)){
                    foreach(json_decode($bookingCheckout->seat_details, true) as $seat_detail){
                        $detail = new BusBookingDetail();
                        $detail->bus_booking_id = $bus_booking->id;
                        $detail->bus_seat_detail_id = $seat_detail['bus_seat_detail_id'];
                        $detail->name = $seat_detail['name'];
                        $detail->age = $seat_detail['age'];
                        $detail->gender = $seat_detail['gender'];
                        $detail->amount = $seat_detail['seat_charges'];
                        $detail->save();
                        $passenger_count = $passenger_count+1;
                    }
                }
            }
            else{
                $package_details = json_decode($bookingCheckout->package_details);
                foreach($package_details->package_amount_details as $key => $package_detail){
                    $detail = new BusBookingPackageDetail();
                    $detail->bus_booking_id = $bus_booking->id;
                    $detail->name = $package_detail->name;
                    $detail->quantity = $package_detail->quantity;
                    $detail->length = $package_detail->length;
                    $detail->width = $package_detail->width;
                    $detail->height = $package_detail->height;
                    $detail->weight = $package_detail->weight;
                    $detail->amount = $package_detail->amount;
                    $detail->save();
                    $package_count = $package_count+1;
                }
            }
            $stop_status_history = json_decode($master_bus_booking->stop_status_history);
            $stop_ids = array_column($stop_status_history, 'stop_id');
            $in_key = array_search($bookingCheckout->bus_stop_id, $stop_ids);
            if($in_key!=""){
                $stop_status_history[$in_key]->in_passenger_count=$stop_status_history[$in_key]->in_passenger_count+$passenger_count;
                $stop_status_history[$in_key]->in_package_count=$stop_status_history[$in_key]->in_package_count+$package_count;
            }
            $out_key = array_search($bookingCheckout->end_bus_stop_id, $stop_ids);
            if($out_key!=""){
                $stop_status_history[$out_key]->out_passenger_count=$stop_status_history[$out_key]->out_passenger_count+$passenger_count;
                $stop_status_history[$out_key]->out_package_count=$stop_status_history[$out_key]->out_package_count+$package_count;

            }
            foreach($stop_status_history as $key=>$stop_status){
                if($key>$in_key && $key<=$out_key && ($key != count($stop_status_history)-1) ){
                    $stop_status_history[$key]->total_passenger_count=$stop_status_history[$key]->total_passenger_count+$passenger_count;
                    $stop_status_history[$key]->total_package_count=$stop_status_history[$key]->total_package_count+$package_count;
                }
            }
            $master_bus_booking->stop_status_history = json_encode($stop_status_history);
            $master_bus_booking->save();
            // Make Payment
            $payment = new Payment();
            if ($bus_booking->payment_method_id != 1) {
                $array_param = array(
                    'bus_booking_id' => $bus_booking->id,
                    'payment_method_id' => $bus_booking->payment_method_id,
//                    'amount' => $bus_booking->total_amount,
                    'amount' => $final_amount_paid,
                    'user_id' => $user->id,
//                    'card_id' => $bus_booking->card_id,
                    'order_name' => $bus_booking->id,
                    'currency' => $user->Country->isoCode,
                );
                if($payment->MakePayment($array_param)){
                    $bus_booking->payment_status = 1;
                    $bus_booking->save();
                }else{
                    throw new \Exception(trans("$string_file.payment_failed"));
                }
            }
            return array("bus_booking_id" => $bus_booking->id);
        }catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }
    }


    public function calculateShipping($request, $merchant_id): array
    {
        $bus = BUS::find($request->bus_id);
        $price_card = BusPriceCard::where(array(
            "merchant_id" => $merchant_id,
            "bus_route_id" => $request->bus_route_id,
            "vehicle_type_id" => $bus->vehicle_type_id,
            "country_area_id" => $bus->country_area_id,
        ))->first();

        return self::shippingPrice($request, $price_card,$bus->CountryArea->tax);
    }

    public function getStopPointsAccoringToRoute($request){

        $bus_route = BusRoute::find($request->bus_route_id);
        $arr_stop_points = [];

        foreach ($bus_route->StopPoints as $stopPoint) {

            $arr_stop_points[] = array(
                'id' => $stopPoint->id,
                'name' => $stopPoint->Name,
                'is_it_pickup_point' => $request->bus_stop_id == $stopPoint->id ? true : false,
                'is_it_drop_point' => $request->end_bus_stop_id == $stopPoint->id ? true : false,
                'latitude' => $stopPoint->latitude,
                'longitude' => $stopPoint->longitude,
                'address' => $stopPoint->address,
            );
        }

        $arr_stop_points[] = array(
            'id' => $bus_route->EndPoint->id,
            'name' => $bus_route->EndPoint->Name,
            'is_it_pickup_point' => $request->bus_stop_id == $bus_route->EndPoint->id ? true : false,
            'is_it_drop_point' => $request->end_bus_stop_id == $bus_route->EndPoint->id ? true : false,
            'latitude' => $bus_route->EndPoint->latitude,
            'longitude' => $bus_route->EndPoint->longitude,
            'address' => $bus_route->EndPoint->address,
        );
        return  $arr_stop_points;
    }

}

