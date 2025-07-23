<?php

namespace App\Traits;

use Auth;
use App\Models\PriceCard;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use App\Models\CarpoolingRideDetail;
use App\Models\CarpoolingRide;
use App\Models\CountryArea;
use App\Models\CarpoolingRideCheckout;
use App\Models\CarpoolingOfferRideCheckoutDetail;
use App\Models\PriceCardCommission;
use App\Models\CarpoolingRideUserDetail;
use App\Models\CarpoolingConfigCountry;
use App\Traits\MerchantTrait;

use DB;


trait CarpoolingTrait
{

    public function estimateCalculate($merchant_id, $country_area_id, $segment_id, $distance)
    {
        $price_card = PriceCard::where([['merchant_id', $merchant_id], ['segment_id', $segment_id], ['country_area_id', $country_area_id]])->first();
        $return_param = array(
            "price_card_id" => "",
            "distance_charges" => 0,
            "service_charges" => 0,
            "total_amount" => 0
        );
        if (!empty($price_card)) {
            $return_param = array(
                "price_card_id" => $price_card->id,
                "distance" => $distance,
                "distance_charges" => ($distance * $price_card->distance_charges),
                "service_charges" => $price_card->service_charges,
                "total_amount" => ($distance * $price_card->distance_charges) + (($price_card->service_charges * $distance * $price_card->distance_charges) / 100)
            );
        } else {
            throw new \Exception("Price Not Found");
        }
        return $return_param;
    }

    public function carpoolingRideLog($offer_ride, $slug, $carpooling_ride_user_detail = null)
    {

        $carpooling_ride = CarpoolingRide::where([['id', '=', $offer_ride->id]])->first();
        // p( $carpooling_ride);
        if (!empty($carpooling_ride)) {
            $return_param = array(
                "id" => $carpooling_ride->id,
                "timestamp" => time(),
                "driver_name" => $carpooling_ride->User->first_name,
                'slug' => $slug,
            );
            if (empty($carpooling_ride_user_detail)) {
                $carpooling_ride->carpooling_logs = json_encode([$return_param]);
                $carpooling_ride->save();
            } elseif ($carpooling_ride_user_detail->ride_status == 1) {
                $logs_history = json_decode($carpooling_ride->carpooling_logs, true);
                $passenger_info = array("passenger_name" => $carpooling_ride_user_detail->User->first_name);
                array_push($return_param, $passenger_info);
                array_push($logs_history, $return_param);
                $carpooling_ride->carpooling_logs = json_encode($logs_history);
                $carpooling_ride_user_detail->carpooling_logs = json_encode($logs_history);
                $carpooling_ride_user_detail->save();
                $carpooling_ride->save();
            } elseif ($carpooling_ride_user_detail->ride_status == 2) {
                $logs_history = json_decode($carpooling_ride->carpooling_logs, true);
                $passenger_info = array("passenger_name" => $carpooling_ride_user_detail->User->first_name);
                array_push($return_param, $passenger_info);
                array_push($logs_history, $return_param);
                $carpooling_ride->carpooling_logs = json_encode($logs_history);
                $carpooling_ride_user_detail->carpooling_logs = json_encode($logs_history);
                $carpooling_ride_user_detail->save();
                $carpooling_ride->save();
            } elseif ($carpooling_ride_user_detail->ride_status == 8) {
                $logs_history = json_decode($carpooling_ride->carpooling_logs, true);
                $passenger_info = array("passenger_name" => $carpooling_ride_user_detail->User->first_name);
                array_push($return_param, $passenger_info);
                array_push($logs_history, $return_param);
                $carpooling_ride->carpooling_logs = json_encode($logs_history);
                $carpooling_ride_user_detail->carpooling_logs = json_encode($logs_history);
                $carpooling_ride_user_detail->save();
                $carpooling_ride->save();
            }

        }

    }
    // public function carpoolingRideUserLog($offer_ride,$slug)
    // {
    //     $carpooling_ride_user_detail= CarpoolingRideUserDetail::where([['id','=',$offer_ride->id]])->first();
    //   // p( $carpooling_ride);
    //      if(!empty($carpooling_ride_user_detail))
    //     {
    //          $return_param = array(
    //             "id" => $carpooling_ride_user_detail->id,
    //             "timestamp" =>time() ,
    //             "passenger_name"=>$carpooling_ride_user_detail->User->first_name,
    //             'slug'=>$slug,
    //         );
    //         if($carpooling_ride_user_detail->ride_status==1)
    //         {
    //             $carpooling_ride_user_detail->carpooling_logs=json_encode([$return_param]);
    //             $carpooling_ride_user_detail->save();
    //         }
    //          if($carpooling_ride_user_detail->ride_status==1)
    //         {
    //             $carpooling_ride_user_detail->carpooling_logs=json_encode([$return_param]);
    //             $carpooling_ride_user_detail->save();
    //         }
    //         else
    //         {
    //             $logs_history = json_decode($carpooling_ride_user_detail->carpooling_logs, true);
    //             array_push($logs_history, $return_param);
    //             $carpooling_ride_user_detail->carpooling_logs = json_encode($logs_history);
    //             $carpooling_ride_user_detail->save();
    //         }
    //     }

    // }


    public function addReturnRouteData($user_offer_ride_checkout)
    {
        CarpoolingOfferRideCheckoutDetail::with(["CarpoolingOfferRideCheckout" => function ($query) use ($user_offer_ride_checkout) {
            $query->where("user_id", $user_offer_ride_checkout->user_id);
        }])->where("is_return", "=", 1)->delete();
        $total_points = CarpoolingOfferRideCheckoutDetail::where("carpooling_offer_ride_checkout_id", $user_offer_ride_checkout->id)->count();
        $return_drop_points = CarpoolingOfferRideCheckoutDetail::where("carpooling_offer_ride_checkout_id", $user_offer_ride_checkout->id)->orderBy('drop_no', "DESC")->get();
        $drop_no = 1;
        foreach ($return_drop_points as $return_drop_point) {
            $new_drop_point = $return_drop_point->replicate();
            $from_latitude = $new_drop_point->from_latitude;
            $from_longitude = $new_drop_point->from_longitude;
            $from_location = $new_drop_point->from_location;
            $new_drop_point->from_latitude = $new_drop_point->to_latitude;
            $new_drop_point->from_longitude = $new_drop_point->to_longitude;
            $new_drop_point->from_location = $new_drop_point->to_location;
            $new_drop_point->to_latitude = $from_latitude;
            $new_drop_point->to_longitude = $from_longitude;
            $new_drop_point->to_location = $from_location;
            $new_drop_point->drop_no = $drop_no + $total_points;
            $new_drop_point->ride_timestamp = $user_offer_ride_checkout->return_ride_timestamp;
            $new_drop_point->is_return = 1;
            $new_drop_point->save();
            $drop_no++;
        }
    }

    public function offerRideCheckoutResponse($offer_ride_checkout, $string_file)
    {
        //$checkout_details = CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id]]);
        //$one_way_amount = $checkout_details->whereNull('is_return')->sum('final_charges');
        //$departure_route = $checkout_details->select("drop_no", "from_location", "to_location", "final_charges","eta")->whereNull('is_return')->orderBy("drop_no")->get()->toArray();
        $return = [];
        $departure_route = CarpoolingOfferRideCheckoutDetail::where([['is_return', '=', NULL], ["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id]])->orderBy('drop_no')->get();
        $one_way_amount = $departure_route->sum('final_charges');
        $first_drop = $departure_route[0];
        $departure_route_value = [];
        if (!empty($departure_route)) {
            $first_drop = $departure_route[0];
            array_push($departure_route_value, array(
                'id' => $first_drop->id,
                'drop_no' => 0,
                'location' => $first_drop->from_location,
                'ride_timestamp' => $first_drop->ride_timestamp,
                'estimate_distance' => NULL,
                'final_charges' => NULL,
            ));
            foreach ($departure_route as $value) {
                $departure_route_value[] = array(
                    'id' => $value->id,
                    'drop_no' => $value->drop_no,
                    'location' => $value->to_location,
                    'ride_timestamp' => $value->end_timestamp,
                    'estimate_distance' => $value->estimate_distance,
                    'final_charges' => $offer_ride_checkout->user->Country->isoCode . ' ' . $value->final_charges,
                );

            }
        }
        $departure = array(
            "ride_date" => $offer_ride_checkout->ride_timestamp,
            "available_seats" => $offer_ride_checkout->available_seats,
            "available_seat_text" => $offer_ride_checkout->available_seats . " " . trans("$string_file.seats") . " " . trans("common.available"),
            "total_amount" => $offer_ride_checkout->User->Country->isoCode . ' ' . $one_way_amount,

            "routes" => $departure_route_value
        );
        if ($offer_ride_checkout->return_ride) {
            //$return_route = CarpoolingOfferRideCheckoutDetail::select("drop_no", "from_location", "to_location", "final_charges","eta")->where([["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id]])->where('is_return', '=', '1')->orderBy("drop_no")->get()->toArray();
            $return_route = CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id], ['is_return', '=', '1']])->orderBy("drop_no")->get();
            $return_route_value = [];
            if (!empty($return_route)) {
                $last_drop = $return_route[0];
                array_push($return_route_value, array(
                    'id' => $last_drop->id,
                    'drop_no' => 0,
                    'location' => $last_drop->from_location,
                    'ride_timestamp' => $last_drop->ride_timestamp,
                    'estimate_distance' => NULL,
                    'final_charges' => NULL,
                ));
                foreach ($return_route as $value) {
                    $return_route_value[] = array(
                        'id' => $value->id,
                        'drop_no' => $value->drop_no,
                        'location' => $value->to_location,
                        'ride_timestamp' => $value->end_timestamp,
                        'estimate_distance' => $value->estimate_distance,
                        'final_charges' => $offer_ride_checkout->user->Country->isoCode . ' ' . $value->final_charges,
                    );

                }
            }
            $return = $departure;

            $return["routes"] = $return_route_value;
            $return["ride_date"] = $offer_ride_checkout->return_ride_timestamp;
        }
        return array("offer_ride_checkout_id" => $offer_ride_checkout->id, "is_return" => $offer_ride_checkout->return_ride, 'departure' => $departure ? $departure : ['result' => 0, 'data' => []], 'return' => $return ? $return : ['result' => 0, 'data' => []]);
    }

    public function calculateSeatAmount($available_ride, $arr_request)
    {
        $carpooling_ride_details = CarpoolingRideDetail::find($available_ride['pickup_id']);
        //p( $carpooling_ride_details);
        $final_charges = $carpooling_ride_details->final_charges;
        // p($final_charges);
        $ride_amount = $arr_request * $final_charges;
        $price_card = PriceCardCommission::where('price_card_id', '=', $carpooling_ride_details->price_card_id)->first();
        // $return_price['service_charges'] = ($price_card->PriceCard->service_charges*$ride_amount/100);
        $return_price['ride_amount'] = $ride_amount;
        $return_price['commission'] = 0;
        if (!empty($price_card)) {
            if ($price_card->commission_method == 1) {
                $return_price['commission'] = $price_card->commission * $arr_request;
            } else {
                $return_price['commission'] = ($return_price['ride_amount'] * $price_card->commission) / 100;
            }
            $return_price['net_amount'] = $ride_amount + $return_price['commission'];
            $return_price['service_charges'] = ($price_card->PriceCard->service_charges * $return_price['net_amount']) / 100;
            $return_price['total_amount'] = $return_price['net_amount'] + $return_price['service_charges'];
        }
        $discount_amount = 0;
        $after_discount_amount = $ride_amount - $discount_amount;
        $total_amount = $return_price['commission'] + $after_discount_amount + $return_price['service_charges'];
        //p( $total_amount);
        return $total_amount;

    }


    public function calculateBillAmount($checkout, $promo_code = null)
    {
        //p($checkout);
        $no_of_seats = $checkout->booked_seats;
        $carpooling_ride_details = CarpoolingRideDetail::find($checkout->pickup_id);
        // p($carpooling_ride_details );
        $final_charges = CarpoolingRideDetail::whereBetween('id', [$checkout->pickup_id, $checkout->drop_id])->get()->sum('final_charges');
        //  p($final_charges);
        $ride_amount = $no_of_seats * $final_charges;
        $price_card = PriceCardCommission::where('price_card_id', '=', $carpooling_ride_details->price_card_id)->first();
        // $return_price['service_charges'] = ($price_card->PriceCard->service_charges*$ride_amount)/100;
        $return_price['ride_amount'] = $ride_amount;
        $return_price['commission'] = 0;
        if (!empty($price_card)) {
            if ($price_card->commission_method == 1) {
                $return_price['commission'] = $price_card->commission * $no_of_seats;
            } else {
                $return_price['commission'] = ($return_price['ride_amount'] * $price_card->commission) / 100;
            }
            $return_price['net_amount'] = $ride_amount + $return_price['commission'];
            $return_price['service_charges'] = ($price_card->PriceCard->service_charges + $return_price['net_amount']) / 100;
            $return_price['total_amount'] = $return_price['net_amount'] + $return_price['service_charges'];
        }
        if (!empty($promo_code)) {
            $checkout->promo_code_id = $promo_code->id;
        }

        $checkout->ride_amount = $ride_amount;
        $checkout->commission = $return_price['commission'];
        $checkout->discount_amount = 0;
        $after_discount_amount = $return_price['total_amount'] - $checkout->discount_amount;
        $checkout->service_charges = $return_price['service_charges'];
        $checkout->promo_code_id = null;
        $checkout->total_amount = $return_price['total_amount'];
        $checkout->driver_payable_amount = $ride_amount;
        $checkout->merchant_amount = $return_price['commission'];
        $checkout->save();
        return $checkout;
    }

    public function companyCommission($carpooling_ride_details)
    {
        $final_charges = $carpooling_ride_details->final_charges;
        $no_of_seats = $carpooling_ride_details->booked_seats;
        $ride_amount = $no_of_seats * $final_charges;
        $price_card = PriceCardCommission::where('price_card_id', '=', $carpooling_ride_details->price_card_id)->get();
        if (!empty($price_card)) {
            foreach ($price_card as $value) {
                $price['service_charges'] = ($value->PriceCard->service_charges * $ride_amount) / 100;
                $price['commission'] = 0;
                $price['ride_amount'] = $ride_amount;

                if ($value->commission_method == 1) {
                    $price['commission'] = $value->commission * $no_of_seats;
                } else {
                    $price['commission'] = ($price['ride_amount'] * $value->commission) / 100;
                }
                $price['net_amount'] = $price['commission'];
                $price['service_charges'] = ($value->PriceCard->service_charges * $price['net_amount']) / 100;
                $total_amount = $price['net_amount'] + $price['service_charges'];
                $commission_amount = $price['net_amount'];
                //p($total_amount);
            }

        }
        return $commission_amount;
    }

    public function totalCommission($carpooling_ride_details)
    {
        $final_charges = $carpooling_ride_details->final_charges;
        //$no_of_seats=$carpooling_ride_details->booked_seats;
        $ride_amount = $final_charges;
        $price_card = PriceCardCommission::where('price_card_id', '=', $carpooling_ride_details->price_card_id)->get();
        if (!empty($price_card)) {
            foreach ($price_card as $value) {
                // $price['service_charges'] = ($value->PriceCard->service_charges*$ride_amount)/100;
                $price['commission'] = 0;
                $price['ride_amount'] = $ride_amount;

                if ($value->commission_method == 1) {
                    $price['commission'] = $value->commission;
                } else {
                    $price['commission'] = ($price['ride_amount'] * $value->commission) / 100;
                }

                $price['net_amount'] = $price['commission'] + $ride_amount;
                $price['service_charges'] = ($value->PriceCard->service_charges * $price['net_amount']) / 100;
                $total_amount = $price['commission'] + $price['service_charges'];
            }

        }
        //p($total_amount);
        return $total_amount;
    }


    public function cancelAmount($carpooling_ride_details)
    {
        $ride_amount = $carpooling_ride_details->final_charges;;
        // p($ride_amount);
        $total_amount = 0;
        $price_card = PriceCardCommission::where('price_card_id', '=', $carpooling_ride_details->price_card_id)->get();
        if (!empty($price_card)) {
            foreach ($price_card as $value) {
                $price['commission'] = 0;
                $price['ride_amount'] = $ride_amount;
                if ($value->commission_method == 1) {
                    $price['commission'] = $value->commission;
                } else {
                    $price['commission'] = ($price['ride_amount'] * $value->commission) / 100;
                }
                $price['net_amount'] = $price['commission'];
                $price['service_charges'] = ($value->PriceCard->service_charges * $price['net_amount']) / 100;
                $total_amount = $price['net_amount'] + $price['service_charges'];


                // p($total_amount);
            }

        }
        return $total_amount;
    }

    public function seatCharges($ride_id, $ride_details)
    {
        $carpooling_ride = CarpoolingRide::find($ride_id);
        $carpooling_ride_details = CarpoolingRideDetail::find($ride_details->id);
        $ride_amount = $carpooling_ride_details->final_charges;
        $price_card = PriceCardCommission::where('price_card_id', '=', $carpooling_ride_details->price_card_id)->first();
        $return_price['ride_amount'] = $ride_amount;
        $return_price['commission'] = 0;
        if (!empty($price_card)) {
            if ($price_card->commission_method == 1) {
                $return_price['commission'] = $price_card->commission;
            } else {
                $return_price['commission'] = ($return_price['ride_amount'] * $price_card->commission) / 100;
            }
            $return_price['net_amount'] = $ride_amount + $return_price['commission'];
            $return_price['service_charges'] = ($price_card->PriceCard->service_charges + $return_price['net_amount']) / 100;
            $return_price['total_amount'] = $return_price['net_amount'] + $return_price['service_charges'];
        }
        $discount_amount = 0;
        $after_discount_amount = $ride_amount - $discount_amount;
        $total_amount = $return_price['commission'] + $after_discount_amount + $return_price['service_charges'];
        return $total_amount;

    }

    public function checkPromoCode($request)
    {
        $user = $request->user('api');
        $user_id = $user->id;
        $promo_code = $request->promo_code;
        $merchant_id = $request->merchant_id;
        $promocode = PromoCode::where([['promoCode', '=', $promo_code], ['merchant_id', '=', $merchant_id], ['deleted', '=', NULL], ['promo_code_status', '=', 1]])->first();
        if (empty($promocode)) {
            return $this->failedResponse(trans("common.invalid") . ' ' . trans("common.promo") . ' ' . trans("common.code"));
        }
        $validity = $promocode->promo_code_validity;
        $start_date = $promocode->start_date;
        $end_date = $promocode->end_date;
        $currentDate = date("Y-m-d");
        if ($validity == 2 && ($currentDate < $start_date || $currentDate > $end_date)) {
            return $this->failedResponse(trans("common.promo_code_expired_message"));
        }
        $promo_code_limit = $promocode->promo_code_limit;
        $total_usage = CarpoolingRideDetail::where([['promo_code_id', '=', $promocode->id], ['ride_status', '=', 4]])->get();
        if (!empty($total_usage->toArray())) {
            if (count($total_usage->toArray()) >= $promo_code_limit) {
                return $this->failedResponse(trans("common.promo_code_expired_message"));
            }
            $promo_code_limit_per_user = $promocode->promo_code_limit_per_user;
            $used_by_user = $total_usage->where('user_id', $user_id)->count();
            if ($used_by_user >= $promo_code_limit_per_user) {
                return $this->failedResponse(trans("common.user_limit_promo_code_expired"));
            }
        }
        $applicable_for = $promocode->applicable_for;
        if ($applicable_for == 2 && $user->created_at < $promocode->updated_at) {
            return $this->failedResponse(trans("common.promo_code_for_new_user"));
        }
        return array('status' => true, 'promo_code' => $promocode);
    }

    public function driverReceiptHolder($carpooling_ride)
    {
        // p($carpooling_ride_user_detail);
        //$carpooling_ride_user_detail=CarpoolingRideUserDetail::where('carpooling_ride_id','=',$carpooling_ride->id)->whereIn('ride_status',array(4,5,6,8))->get();
        $reject_seats = DB::table("carpooling_ride_user_details")->where([['carpooling_ride_id', $carpooling_ride->id], ['ride_status', '=', 8]])->sum('booked_seats');
        $booked_seats = DB::table("carpooling_ride_user_details")->where([['carpooling_ride_id', $carpooling_ride->id], ['ride_status', '=', 4]])->sum('booked_seats');
        $cancel_seats_passenger = DB::table("carpooling_ride_user_details")->where([['carpooling_ride_id', $carpooling_ride->id], ['ride_status', '=', 6]])->sum('booked_seats');
        $cancel_seat_driver = DB::table("carpooling_ride_user_details")->where([['carpooling_ride_id', $carpooling_ride->id], ['ride_status', '=', 5]])->sum('booked_seats');
        $wallet_action = DB::table("carpooling_ride_user_details")->where([['carpooling_ride_id', $carpooling_ride->id]])->first();
        $no_of_booking = DB::table("carpooling_ride_user_details")->where([['carpooling_ride_id', $carpooling_ride->id], ['ride_status', '=', 4]])->count();
        // p($wallet_action);
        $end_point = CarpoolingRideDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id]])->orderBy('drop_no', 'DESC')->first();
        $this->SetTimeZone($carpooling_ride->country_area_id);
        $currency = $carpooling_ride->User->Country->isoCode;
        $wallet_amount = DB::table('carpooling_ride_user_details')->where([['carpooling_ride_id', '=', $carpooling_ride->id], ['ride_status', '=', 4]])->whereIn('payment_action', [1, 3])->sum('total_amount');;
        $cash_amount = DB::table('carpooling_ride_user_details')->where([['carpooling_ride_id', '=', $carpooling_ride->id], ['ride_status', '=', 4], ['payment_action', '=', 2]])->sum('total_amount');
        //$string_file = $this->getStringFile($carpooling_ride->merchant_id);
        return array(
            "header" => array(
                "left_text" => "",
                "center_text" => trans("common.driver_receipt"),
                "right_text" => "",
            ),
            "body" => array(
                "ride_details" => array(
                    array(
                        "left_text" => trans("common.trip_id") . "#" . $carpooling_ride->id,
                        "center_text" => "",
                        "right_text" => $carpooling_ride->User->first_name . " " . $carpooling_ride->User->last_name
                    ),
                    array(
                        "left_text" => trans("common.trip_start"),
                        "center_text" => "",
                        "right_text" => date("d/m/Y H:i:s", $carpooling_ride->ride_timestamp)
                    ),
                    array(
                        "left_text" => trans("common.trip_end"),
                        "center_text" => "",
                        "right_text" => date("d/m/Y H:i:s", $end_point->end_timestamp)
                    ),
                    array(
                        "left_text" => trans("common.pickup_location"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride->start_location
                    ),
                    array(
                        "left_text" => trans("common.drop_location"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride->end_location
                    ),

                    array(
                        "left_text" => trans("common.no_of_booking"),
                        "center_text" => "",
                        "right_text" => (string) $no_of_booking,
                    ),
                    array(
                        "left_text" => trans("common.no_of_seats_offered"),
                        "center_text" => "",
                        "right_text" => (string) $carpooling_ride->available_seats,
                    ),
                    array(
                        "left_text" => trans("common.cancellation_by_driver"),
                        "center_text" => "",
                        "right_text" => (string) $cancel_seat_driver,
                    ),
                    array(
                        "left_text" => trans("common.cancellation_by_passenger"),
                        "center_text" => "",
                        "right_text" => (string) $cancel_seats_passenger,
                    ),
                    array(
                        "left_text" => trans("common.rejection_by_driver"),
                        "center_text" => "",
                        "right_text" => (string) $reject_seats,
                    ),
                    array(
                        "left_text" => trans("common.no_of_seat_booked"),
                        "center_text" => "",
                        "right_text" => (string) $booked_seats,
                    ),
                ),
                "bill_details" => array(
                    // array(
                    //     "left_text" => "Wallet Amount",
                    //     "center_text" => "",
                    //     "right_text" =>   $currency." ".$carpooling_ride->User->wallet_balance,
                    // ),
                    array(
                        "left_text" => trans("common.cash_amount"),
                        "center_text" => "",
                        "right_text" => $currency . " " . $cash_amount
                    ),
                    array(
                        "left_text" => trans("common.wallet_amount"),
                        "center_text" => "",
                        "right_text" => $currency . " " . $wallet_amount
                    ),

                    array(
                        "left_text" => trans("common.invoice_to_passenger"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride->ride_status == 5 ? $currency . " " . "0" : $currency . " " . round($carpooling_ride->total_amount, 3)
                    ),

                    array(
                        "left_text" => $carpooling_ride->ride_status == 4 ? trans("common.booking_fees") : trans("common.cancel_amount"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride->ride_status == 5 ? $currency . " " . $carpooling_ride->cancel_amount : $currency . " " . $carpooling_ride->company_commission
                    ),
                    array(
                        "left_text" => $carpooling_ride->ride_status == 4 ? trans("common.tax") : " ",
                        "center_text" => "",
                        "right_text" => $carpooling_ride->ride_status == 5 ? " " : $currency . " " . $carpooling_ride->service_charges
                    )
                ),
            ),
            "footer" => array(
                "left_text" => trans("common.total_earning"),
                "center_text" => "",
                "right_text" => $carpooling_ride->ride_status == 5 ? $currency . " " . "0" : $currency . " " . $carpooling_ride->driver_earning,
            ),

        );
    }

    public function userReceiptHolder($carpooling_ride_user_details)
    {
        // $payment_options = \Config::get('custom.carpooling_payment_options');
        // $method= $payment_options[$carpooling_ride_user_details->payment_action];
        if ($carpooling_ride_user_details->payment_action == 1) {
            $method = trans("common.wallet");
        } elseif ($carpooling_ride_user_details->payment_action == 2) {
            $method = trans("common.cash");
        } else {
            $method = trans("common.wallet_at_pickup");
        }
        $end_point = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride_user_details->carpooling_ride_id], ['carpooling_ride_detail_id', '=', $carpooling_ride_user_details->carpooling_ride_detail_id]])->first();
        $carpooling_details = $this->calculateBillAmount($carpooling_ride_user_details);
        $carpooling_ride = CarpoolingRide::find($carpooling_ride_user_details->carpooling_ride_id);
        $currency = $carpooling_ride->User->Country->isoCode;
        $this->SetTimeZone($carpooling_ride->country_area_id);
        return array(
            "header" => array(
                "left_text" => "",
                "center_text" => trans("common.passenger_receipt"),
                "right_text" => "",
            ),
            "body" => array(
                "ride_details" => array(
                    array(
                        "left_text" => trans("common.trip_id") . "#" . $carpooling_ride_user_details->carpooling_ride_id . "-" . $carpooling_ride_user_details->id,
                        "center_text" => "",
                        "right_text" => $carpooling_ride_user_details->User->first_name . " " . $carpooling_ride_user_details->User->last_name
                    ),
                    array(
                        "left_text" => trans("common.trip_start"),
                        "center_text" => "",
                        "right_text" => date("d/m/Y H:i:s", $carpooling_ride_user_details->ride_timestamp)
                    ),
                    array(
                        "left_text" => trans("common.end"),
                        "center_text" => "",
                        "right_text" => date("d/m/Y H:i:s", $carpooling_ride_user_details->end_timestamp)
                    ),
                    array(
                        "left_text" => trans("common.start_location"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride_user_details->pickup_location
                    ),
                    array(
                        "left_text" => trans("common.end_location"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride_user_details->drop_location
                    ),
                    array(
                        "left_text" => trans("common.no_of_seats"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride_user_details->booked_seats
                    ),
                    array(
                        "left_text" => trans("common.per_seat_charges"),
                        "center_text" => "",
                        "right_text" => $currency . " " . $carpooling_ride_user_details->total_amount / $carpooling_ride_user_details->booked_seats
                    )
                ),
                "bill_details" => array(
                    array(
                        "left_text" => trans("common.ride_amount"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride_user_details->ride_status == 6 ? $currency . " " . "0" : $currency . " " . $carpooling_ride_user_details->ride_amount
                    ),
                    array(
                        "left_text" => trans("common.booking_fees"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride_user_details->ride_status == 6 ? $currency . " " . "0" : $currency . " " . $carpooling_details->commission
                    ),

                    array(
                        "left_text" => trans("common.state_charges"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride_user_details->ride_status == 6 ? $currency . " " . "0" : $currency . " " . $carpooling_details->service_charges
                    ),

                    array(
                        "left_text" => $carpooling_ride_user_details->ride_status == 6 ? trans("common.cancel_amount") : trans("common.total_amount"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride_user_details->ride_status == 6 ? $currency . " " . $carpooling_ride_user_details->cancel_amount : $currency . " " . $carpooling_ride_user_details->total_amount
                    ),
                    array(
                        "left_text" => trans("common.discount_amount"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride_user_details->ride_status == 6 ? $currency . " " . "0" : $currency . " " . $carpooling_details->discount_amount
                    ),
                    array(
                        "left_text" => trans("common.amount_payable"),
                        "center_text" => "",
                        "right_text" => $carpooling_ride_user_details->ride_status == 6 ? $currency . " " . $carpooling_ride_user_details->cancel_amount : $currency . " " . ($carpooling_ride_user_details->total_amount - $carpooling_ride_user_details->discount)
                    ),
                ),
            ),
            "footer" => array(
                "left_text" => trans("common.payment_method"),
                "center_text" => "",
                "right_text" => $method,
            ),
        );
    }

    public function cancelOfferCondition($ride_id)
    {
        $carpooling_ride = CarpoolingRide::find($ride_id);
        // p($carpooling_ride );
        $distance_find = DB::table("carpooling_ride_details")->where('carpooling_ride_id', $carpooling_ride->id)->sum('estimate_distance');
        // p($distance_find);
        $offer_ride_details = CarpoolingRideDetail::where('carpooling_ride_id', $carpooling_ride->id)->first();
        // $carpooling_ride_user_details=CarpoolingRideUserDetail::where([['carpooling_ride_id','=',$carpooling_ride->id]])->get();
        $carpooling_config = PriceCard::with('CarpoolingPriceCardCancelCharge')->find($offer_ride_details->price_card_id);
        // p($carpooling_config->CarpoolingPriceCardCancelCharge);
        if (empty($carpooling_config->CarpoolingPriceCardCancelCharge)) {
            $message = trans("common.configuration_not_found");
            return $this->failedResponse($message);
        }
        $carpooling_config_country = CarpoolingConfigCountry::where('country_id', '=', $carpooling_ride->User->Country->id)->first();
        $current_timestamp = strtotime('now');
        $ride_timestamp = $carpooling_ride->ride_timestamp;
        $time_diff = $current_timestamp - $ride_timestamp;
        $min = round(abs($time_diff) / 60);
        $hour = round(abs($time_diff) / 3600);
        $company_cut = 0;
        // ride with in city case
        if (!empty($carpooling_config_country)) {
            if ($carpooling_ride->CarpoolingRideUserDetail->payment_action = 1) {
                //short ride
                if ($distance_find <= $carpooling_config_country->short_ride) {
                    if ($min <= $carpooling_config_country->short_ride_time) {
                        $company_cut = $this->cancelAmount($offer_ride_details) * $carpooling_ride->booked_seats;
                    } else {
                        $company_cut = 0;
                    }
                } else {
                    if ($hour <= $carpooling_config_country->long_ride_time) {
                        $company_cut = $this->cancelAmount($offer_ride_details) * $carpooling_ride->booked_seats;
                    } else {
                        $company_cut = 0;
                    }
                }
                $cancel_amount = round($company_cut);

            } //paylater case payment_action 2 and 3
            else {
                if ($distance_find <= $carpooling_config_country->short_ride) {
                    if ($min <= $carpooling_config_country->short_ride_time) {
                        $company_cut = $this->cancelAmount($offer_ride_details) * $carpooling_ride->booked_seats;
                    } else {
                        $company_cut = 0;
                    }
                } else {
                    if ($hour <= $carpooling_config_country->long_ride_time) {
                        $company_cut = $this->cancelAmount($offer_ride_details) * $carpooling_ride->booked_seats;
                    } else {
                        $company_cut = 0;
                    }
                }
                $cancel_amount = round($company_cut);

            }
            return $cancel_amount;
        } else {
            $message = trans("common.configuration_not_found");
            throw new \Exception($message);
        }

    }

    public function canceltakenCondition($ride_id)
    {
        $carpooling_ride_user_details = CarpoolingRideUserDetail::find($ride_id);
        $carpooling_ride_details = CarpoolingRideDetail::find($carpooling_ride_user_details->carpooling_ride_detail_id);
        //p($carpooling_ride_details);
        $carpooling_config = PriceCard::with('CarpoolingPriceCardCancelCharge')->find($carpooling_ride_details->price_card_id);
        //p($carpooling_config->CarpoolingPriceCardCancelCharge);
        if (empty($carpooling_config->CarpoolingPriceCardCancelCharge)) {
            $message = trans("common.configuration") . " " . trans("common.is") . " " . trans("common.found");
            return $this->failedResponse($message);
        }
        $carpooling_config_country = CarpoolingConfigCountry::where('country_id', '=', $carpooling_ride_user_details->User->Country->id)->first();
        $current_timestamp = strtotime('now');
        $ride_timestamp = $carpooling_ride_details->ride_timestamp;
        $time_diff = $current_timestamp - $ride_timestamp;
        $min = round(abs($time_diff) / 60);
        $hour = round(abs($time_diff) / 3600);
        // p($hour );
        $company_cut = 0;
        //paynow case payment_action = 1
        if (!empty($carpooling_config_country)) {
            if ($carpooling_ride_user_details->payment_action = 1) {
                // ride with in city case
                if ($carpooling_ride_details->estimate_distance <= $carpooling_config_country->short_ride) {
                    if ($min <= $carpooling_config_country->short_ride_time) {
                        $company_cut = $this->cancelAmount($carpooling_ride_details) * $carpooling_ride_user_details->booked_seats;
                    } else {
                        $company_cut = 0;
                    }
                } // long ride case
                else {
                    if ($hour <= $carpooling_config_country->long_ride_time) {
                        $company_cut = $this->cancelAmount($carpooling_ride_details) * $carpooling_ride_user_details->booked_seats;

                    } else {
                        $company_cut = 0;
                    }
                }
                $total_amount = round($company_cut);
                // $remaining_amount=$carpooling_ride_user_details->total_amount- $total_amount;
                //p( $total_amount);

            } else {
                //paylater case payment_action 2 and 3
                if ($carpooling_ride_details->estimate_distance <= $carpooling_config_country->short_ride) {
                    if ($min <= $carpooling_config_country->short_ride_time) {
                        $company_cut = $this->cancelAmount($carpooling_ride_details) * $carpooling_ride_user_details->booked_seats;
                    } else {
                        $company_cut = 0;
                    }
                } // long ride case
                else {
                    if ($hour <= $carpooling_config_country->long_ride_time) {
                        $company_cut = $this->cancelAmount($carpooling_ride_details) * $carpooling_ride_user_details->booked_seats;
                        // p($company_cut);
                    } else {
                        $company_cut = 0;
                    }
                }
                $total_amount = round($company_cut);
                //  p($total_amount);


            }
            return $total_amount;
        } else {
            $message = trans("common.configuration_not_found");
            return $this->failedResponse($message);
        }

    }


    public function SetTimeZone($areaID)
    {
        $area = CountryArea::find($areaID);
        if (!empty($area)) {
            date_default_timezone_set($area->timezone);
        }
    }

}
