<?php

namespace App\Http\Controllers\Services;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\DistanceController;
use App\Http\Controllers\Helper\FindDriverController;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\PriceController;
use App\Models\Booking;
use App\Models\BookingConfiguration;
use App\Models\Configuration;
use App\Models\CountryArea;
use App\Models\Driver;
use App\Models\Outstanding;
use App\Models\PoolRideList;
use App\Models\PriceCard;
use App\Models\UserSubscriptionRecord;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;

class PoolController extends Controller
{
    use MerchantTrait;
    public function CreateCheckout($request)
    {
        try {
            $validator = Validator::make($request->all(), [
                // 'area' => 'required',
                // 'service_type' => 'required',
                // 'pickup_latitude' => 'required',
                // 'pickup_longitude' => 'required',
                // 'total_drop_location' => 'required|integer|between:1,4',
                // 'drop_location' => 'required',
                // 'pick_up_location' => 'required',
                'number_of_rider' => 'required|integer',
            ]);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                throw  new \Exception($errors[0]);
                //            return ['result' => "0", 'message' => $errors[0], 'data' => []];
            }
            $user = $request->user('api');
            $merchant_id = $user->merchant_id;
            $string_file = $this->getStringFile($merchant_id);
            $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
            if (empty($configuration->pool_radius)) {
                throw new \Exception(trans("$string_file.pool_configuration_not_found"));
                //            return ['result' => "0", 'message' => trans('api.message168'), 'data' => []];
            }
            $pricecards = PriceCard::where([['status', '=', 1], ['country_area_id', '=', $request->area], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $request->service_type]])->get();
            $newBookingData = new BookingDataController();
            if (empty($pricecards->toArray())) {
                $newBookingData->failbooking($request, $merchant_id, $request->user('api')->id, 1);
                throw new \Exception(trans("$string_file.no_price_card_for_area"));
                //            return ['result' => "0", 'message' => trans("$string_file.no_price_card_for_area"), 'data' => []];
            }
            $user_gender = $user->user_gender;
            $vehicle_type_id = array_pluck($pricecards, 'vehicle_type_id');

            $drop_locationArray = json_decode($request->drop_location, true);
            $dropLocation = isset($drop_locationArray[0]) ? $drop_locationArray[0] : '';
            $drop_lat = isset($dropLocation['drop_latitude']) ? $dropLocation['drop_latitude'] : '';
            $drop_long = isset($dropLocation['drop_longitude']) ? $dropLocation['drop_longitude'] : '';

            $req_parameter = [
                'area' => $request->area,
                'segment_id' => $request->segment_id,
                'latitude' => $request->pickup_latitude,
                'longitude' => $request->pickup_longitude,
                'distance' => $configuration->pool_radius,
                'limit' => $configuration->pool_now_request_driver,
                'service_type' => $request->service_type,
                'vehicle_type' => $request->vehicle_type,
                'drop_lat' => $drop_lat,
                'drop_long' => $drop_long,
                'user_gender' => $request->user('api')->user_gender,
                'riders_num' => $request->number_of_rider,
                'call_google_api'=> false,
            ];

            // get nearest drivers
            $drivers = Driver::GetNearestDriver($req_parameter);


            // $findDriver = new FindDriverController();
            // $drivers = $findDriver->checkPoolDriver($request->area, $request->pickup_latitude, $request->pickup_longitude, $configuration->pool_radius, $configuration->pool_now_request_driver, $request->number_of_rider, $vehicle_type_id, $user_gender, $configuration->driver_request_timeout);
            if (!$drivers) {
                $newBookingData->failbooking($request, $merchant_id, $user->id, 2);
                throw  new \Exception(trans("$string_file.no_driver_available"));
                //            return ['result' => "0", 'message' => trans("$string_file.no_driver_available"), 'data' => []];
            }
            $vehicle_type_id = $drivers['0']->DriverVehicle[0]->vehicle_type_id;
            $pricecards = PriceCard::where([['country_area_id', '=', $request->area], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $request->service_type]])->first();
            $from = $request->pickup_latitude . "," . $request->pickup_longitude;
            $current_latitude = $drivers['0']->current_latitude;
            $current_longitude = $drivers['0']->current_longitude;
            $request->request->add(['vehicle_type' => $vehicle_type_id]);
            $driverLatLong = $current_latitude . "," . $current_longitude;
            $units = (CountryArea::find($request->area)->Country['distance_unit'] == 1) ? 'metric' : 'imperial';
            $nearDriver = DistanceController::DistanceAndTime($from, $driverLatLong, $configuration->google_key, $units);
            $estimate_driver_distance = $nearDriver['distance'];
            $estimate_driver_time = $nearDriver['time'];
            $drop_locationArray = json_decode($request->drop_location, true);
            $googleArray = GoogleController::GoogleStaticImageAndDistance($request->pickup_latitude, $request->pickup_longitude, $drop_locationArray, $configuration->google_key, $units);
            //        if (empty($googleArray)) {
            //            return ['result' => "0", 'message' => trans("$string_file.google_key_not_working"), 'data' => []];
            //        }
            $lastLocation = $newBookingData->wayPoints($drop_locationArray);
            $time = $googleArray['total_time_text'];
            $timeSmall = $googleArray['total_time_minutes'];
            $distance = $googleArray['total_distance_text'];
            $distanceSmall = $googleArray['total_distance'];
            $image = $googleArray['image'];
            $bill_details = "";
            $outstanding_amount = Outstanding::where(['user_id' => $request->user('api')->id, 'reason' => 1, 'pay_status' => 0])->sum('amount');
            switch ($pricecards->pricing_type) {
                case "1":
                case "2":
                    $estimatePrice = new PriceController();
                    $fare = $estimatePrice->BillAmount([
                        'price_card_id' => $pricecards->id,
                        'merchant_id' => $merchant_id,
                        'distance' => $distanceSmall,
                        'time' => $timeSmall,
                        'booking_id' => 0,
                        'user_id' => $request->user('api')->id,
                        'outstanding_amount' => $outstanding_amount,
                        'booking_time' => $request->later_time,
                        'number_of_rider' => $request->number_of_rider,
                    ]);
                    $amount = $fare['amount'];
                    $bill_details = json_encode($fare['bill_details']);
                    break;
                case "3":
                    $amount = trans('api.message62');;
                    break;
            }
            $rideData = array(
                'distance' => $distance,
                'time' => $time,
                'bill_details' => $bill_details,
                'amount' => $amount,
                'estimate_driver_distance' => $estimate_driver_distance,
                'estimate_driver_time' => $estimate_driver_time,
                'auto_upgradetion' => 2
            );
            $result = $newBookingData->CreateCheckout($request, $request->user('api')->id, $merchant_id, $pricecards->id, $image, $rideData, $lastLocation);
            return ['message' => trans("$string_file.ready_for_ride"), 'data' => $result];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function Booking($checkOut)
    {
        
        $merchant_id = $checkOut->merchant_id;
        $user_gender = $checkOut->gender;
        $string_file = "grocery_demo_all_in_one";
        //        $checkOut->additional_notes = $additional_notes;
        $vehicle_type_id = [$checkOut->vehicle_type_id];
        $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
        $findDriver = new FindDriverController();
        // $drivers = $findDriver->getPoolDriver($checkOut->country_area_id, $checkOut->pickup_latitude, $checkOut->drop_latitude, $checkOut->drop_longitude, $checkOut->pickup_longitude, $configuration->pool_radius, $configuration->pool_now_request_driver, $checkOut->number_of_rider, $vehicle_type_id, $configuration->pool_drop_radius, $user_gender, $configuration->driver_request_timeout);
        // // p($drivers);
        // if (empty($drivers)) {
            
        //     // return ['result' => "0", 'message' => trans("$string_file.no_driver_available"), 'data' => []];
        // }
        
        
         $req_parameter = [
                'area' => $checkOut->country_area_id,
                'segment_id' => $checkOut->segment_id,
                'latitude' => $checkOut->pickup_latitude,
                'longitude' => $checkOut->pickup_longitude,
                'distance' => $configuration->pool_radius,
                'limit' => $configuration->pool_now_request_driver,
                'service_type' => $checkOut->service_type_id,
                'vehicle_type' => $checkOut->vehicle_type_id,
                'drop_lat' => $checkOut->drop_latitude,
                'drop_long' => $checkOut->drop_longitude,
                'user_gender' => $checkOut->user_gender,
                'riders_num' => $checkOut->number_of_rider
            ];
// p($req_parameter);
            // get nearest drivers
            $drivers = Driver::GetNearestDriver($req_parameter);


            // $findDriver = new FindDriverController();
            // $drivers = $findDriver->checkPoolDriver($request->area, $request->pickup_latitude, $request->pickup_longitude, $configuration->pool_radius, $configuration->pool_now_request_driver, $request->number_of_rider, $vehicle_type_id, $user_gender, $configuration->driver_request_timeout);
            if (empty($drivers)) {
                throw  new \Exception(trans("$string_file.no_driver_available"));
                //            return ['result' => "0", 'message' => trans("$string_file.no_driver_available"), 'data' => []];
            }

        
        $Bookingdata = $checkOut->toArray();
        unset($Bookingdata['id']);
        unset($Bookingdata['service_type']);
        // unset($Bookingdata['bill_details']);
        unset($Bookingdata['created_at']);
        unset($Bookingdata['updated_at']);
        if(isset($Bookingdata['discounted_amount']))
            unset($Bookingdata['discounted_amount']);
        if(isset($Bookingdata['automatic_promo_applied']))
            unset($Bookingdata['automatic_promo_applied']);
        if(isset($Bookingdata['default_promo_applied']))
            unset($Bookingdata['default_promo_applied']);
        if(isset($Bookingdata['user_subscription_record_id']) || array_key_exists('user_subscription_record_id', $Bookingdata) ){
            $subscription_record = UserSubscriptionRecord::find($Bookingdata['user_subscription_record_id']);
            if(!empty($subscription_record)){
                $subscription_record->used_trips = $subscription_record->used_trips +1;
                $subscription_record->save();
            }
            unset($Bookingdata['user_subscription_record_id']);
        }
        $Bookingdata['booking_timestamp'] = time();
        $Bookingdata['booking_status'] = 1001;
        $Bookingdata['insurnce'] = request()->insurnce;
        $booking = Booking::create($Bookingdata);
        $findDriver->AssignRequest($drivers, $booking->id);
        $bookingData = new BookingDataController();
        // $message = $bookingData->LanguageData($booking->merchant_id, 25);
        $bookingData->SendNotificationToDrivers($booking, $drivers, "");
        // p($booking);
        return ['result' => "1", 'message' => trans("$string_file.ride_booked"), 'data' => $booking];
    }

    public function AcceptRide($booking_data, $request)
    {
        // assign master booking id

        $data['booking_id'] = $booking_data->id;
        $data['driver_id'] = $booking_data->driver_id;
        $data['user_id'] = $booking_data->user_id;
        $data['riders_number'] = $booking_data->number_of_rider;
        $data['pickup_lat'] = $booking_data->pickup_latitude;
        $data['pickup_long'] = $booking_data->pickup_longitude;
        $data['drop_lat'] = $booking_data->drop_latitude;
        $data['drop_long'] = $booking_data->drop_longitude;
        $data_save = new PoolRideList($data);
        $data_save->save();
        $driver = $request->user('api-driver');
        $driver->avail_seats = $driver->avail_seats - $booking_data->number_of_rider;
        $driver->occupied_seats = $driver->occupied_seats + $booking_data->number_of_rider;
        $driver->save();
        if (is_null($driver->pick_exceed)) :
            $driver->pick_exceed = 0;
            $driver->pool_user_id = $booking_data->user_id;
        elseif ($driver->pick_exceed == 0) :
            ++$driver->pick_exceed;
        else :
            $configuration = BookingConfiguration::select('pool_maximum_exceed')->where([['merchant_id', '=', $driver->merchant_id]])->first();
            ++$driver->pick_exceed;
            ($driver->pick_exceed == $configuration->pool_maximum_exceed) ? ($driver->status_for_pool = 2008) : ''; //Check Exceed Limit and set driver to unavailable
        endif;
        $driver->save();
    }

    public function DecideForPickOrDrop($driver_id = null)
    {
        $driver_data = Driver::findorfail($driver_id);
        if ($driver_data->occupied_seats) : //IF FOUND OCCUPIED, NON-EMPTY CAB
            $count_new_coming_pickups = PoolRideList::where([['driver_id', '=', $driver_data->id], ['pickup', '=', 0]])->whereHas('Booking', function ($query) {
                $query->wherein('booking_status', [1002, 1003, 1004]);
            })->get();
            if (!empty($count_new_coming_pickups->toArray())) : // CHECK FOR PICKUP, IF PICKUPS AVAILABLE
                foreach ($count_new_coming_pickups as $new_pickups) :
                    $get_distance = $this->distance($driver_data->current_latitude, $driver_data->current_longitude, $new_pickups->pickup_lat, $new_pickups->pickup_long); //FIND NEAREST PICKUP AMONG THEM
                    $collect[] = array("booking_id" => $new_pickups->booking_id, "driver_id" => $new_pickups->driver_id, "distance" => $get_distance);
                endforeach;
                $get_drops = PoolRideList::where([['driver_id', '=', $driver_data->id], ['dropped', '=', 0]])->whereHas('Booking', function ($query) {
                    $query->wherein('booking_status', [1002, 1003, 1004]);
                })->get(); // Collect Users Inside Cab
                if ($get_drops->isNotEmpty()) :
                    foreach ($get_drops as $drops) :
                        $get_distance = $this->distance($driver_data->current_latitude, $driver_data->current_longitude, $drops->drop_lat, $drops->drop_long); //FIND NEAREST DROP AMONG THEM
                        $collect[] = array("booking_id" => $drops->booking_id, "driver_id" => $drops->driver_id, "distance" => $get_distance);
                    endforeach;
                endif;
                foreach ($collect as $value) {
                    $distance[] = $value['distance'];
                }
                array_multisort($distance, SORT_ASC, $collect);
                $data = PoolRideList::where([['driver_id', '=', $driver_data->id], ['booking_id', '=', $collect[0]['booking_id']]])->first();
                if ($data->pickup == 0) :
                    $booking_data = Booking::findorfail($data->booking_id);
                    return ['booking_status' => $booking_data->booking_status, 'status' => 'Arrive', 'booking_id' => $data->booking_id, 'lat' => $data->pickup_lat, 'lng' => $data->pickup_long, 'location' => $booking_data->pickup_location]; //THIS IS PICKUP
                else :
                    $booking_data = Booking::findorfail($data->booking_id);
                    return ['booking_status' => $booking_data->booking_status, 'status' => 'Drop', 'booking_id' => $data->booking_id, 'lat' => $data->drop_lat, 'lng' => $data->drop_long, 'location' => $booking_data->drop_location]; //THIS IS DROP
                endif;
            else :
                //FIND DROPS FOR ONGOING RIDES
                $count_rides = PoolRideList::where([['driver_id', '=', $driver_data->id], ['dropped', '=', 0]])->whereHas('Booking', function ($query) {
                    $query->wherein('booking_status', [1002, 1003, 1004]);
                })->get();
                if ($count_rides->isNotEmpty()) :
                    if ($count_rides->count() == 1) : // IF CAB HAS ONLY ONE RIDER
                        //SHOW DROP LOCATION OF THIS ONE RIDE
                        $booking_data = Booking::findorfail($count_rides[0]->booking_id);
                        return ['booking_status' => $booking_data->booking_status, 'status' => 'Drop', 'booking_id' => $count_rides[0]->booking_id, 'lat' => $count_rides[0]->drop_lat, 'lng' => $count_rides[0]->drop_long, 'location' => $booking_data->drop_location]; //THIS IS DROP
                    else : // IF CAB HAS MORE THAN ONE RIDER
                        //FIND NEAREST DROP AMONG THEM
                        $get_drops = PoolRideList::where([['driver_id', '=', $driver_data->id], ['dropped', '=', 0]])->whereHas('Booking', function ($query) {
                            $query->wherein('booking_status', [1002, 1003, 1004]);
                        })->get();
                        //$arrange = array();
                        foreach ($get_drops as $drops) :
                            $get_distance = $this->distance($driver_data->current_latitude, $driver_data->current_longitude, $drops->drop_lat, $drops->drop_long); //FIND NEAREST DROP AMONG THEM
                            $collect[] = array("booking_id" => $drops->booking_id, "driver_id" => $drops->driver_id, "distance" => $get_distance);
                        endforeach;
                        foreach ($collect as $value) {
                            $distance[] = $value['distance'];
                        }
                        array_multisort($distance, SORT_ASC, $collect);
                        $data = PoolRideList::where([['driver_id', '=', $driver_data->id], ['dropped', '=', 0], ['booking_id', '=', $collect[0]['booking_id']]])->first();
                        $booking_data = Booking::findorfail($data->booking_id);
                        return ['booking_status' => $booking_data->booking_status, 'status' => 'Drop', 'booking_id' => $data->booking_id, 'lat' => $data->drop_lat, 'lng' => $data->drop_long, 'location' => $booking_data->drop_location]; //THIS IS DROP
                    endif;
                endif;
            endif;
        else : // OUR CAB IS EMPTY, NON OCCUPIED CAB
            $count_pickups = PoolRideList::where([['driver_id', '=', $driver_data->id], ['pickup', '=', 0]])->whereHas('Booking', function ($query) {
                $query->wherein('booking_status', [1002, 1003, 1004]);
            })->get();
            if ($count_pickups->isNotEmpty()) :
                if ($count_pickups->count() == 1) :
                    //SHOW PICKUP OF THIS ONE RIDE
                    $booking_data = Booking::findorfail($count_pickups[0]->booking_id);
                    return ['booking_status' => $booking_data->booking_status, 'status' => 'Arrive', 'booking_id' => $count_pickups[0]->booking_id, 'lat' => $count_pickups[0]->pickup_lat, 'lng' => $count_pickups[0]->pickup_long, 'location' => $booking_data->pickup_location]; //THIS IS PICKUP
                else :
                    //FIND NEAREST PICKUP AMONG THEM
                    $get_picks = PoolRideList::where([['driver_id', '=', $driver_data->id], ['pickup', '=', 0]])->get();
                    $arrange = array();
                    foreach ($get_picks as $picks) :
                        $get_distance = $this->distance($driver_data->current_latitude, $driver_data->current_longitude, $picks->pickup_lat, $picks->pickup_long); //FIND NEAREST PICK AMONG THEM
                        $collect[] = array("booking_id" => $picks->booking_id, "driver_id" => $picks->driver_id, "distance" => $get_distance);
                    endforeach;
                    foreach ($collect as $value) {
                        $distance[] = $value['distance'];
                    }
                    array_multisort($distance, SORT_ASC, $collect);
                    $data = PoolRideList::where([['driver_id', '=', $driver_data->id], ['pickup', '=', 0], ['booking_id', '=', $collect[0]['booking_id']]])->first();
                    $booking_data = Booking::findorfail($data->booking_id);
                    return ['booking_status' => $booking_data->booking_status, 'status' => 'Arrive', 'booking_id' => $data->booking_id, 'lat' => $data->pickup_lat, 'lng' => $data->pickup_long, 'location' => $booking_data->pickup_location]; //THIS IS PICKUP
                endif;
            endif;
        endif;
    }

    public function distance($latitudeFrom = null, $longitudeFrom = null, $latitudeTo = null, $longitudeTo = null, $unit = 'K')
    {
        $rad = M_PI / 180;
        //Calculate distance from latitude and longitude
        $theta = $longitudeFrom - $longitudeTo;
        $dist = sin($latitudeFrom * $rad)
            * sin($latitudeTo * $rad) + cos($latitudeFrom * $rad)
            * cos($latitudeTo * $rad) * cos($theta * $rad);
        $dist = (acos($dist) / $rad);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return (round(($miles * 1.609344), 2));
        } else if ($unit == "N") {
            return (round(($miles * 0.8684), 2));
        } else {
            return round($miles, 2);
        }
    }

    public function DropPool($booking, $request)
    {
        PoolRideList::where([['booking_id', '=', $request->booking_id]])->update(['dropped' => 1]);
        $driver = $request->user('api-driver');
        $driver->avail_seats = $driver->avail_seats + $booking->number_of_rider;
        $driver->occupied_seats = $driver->occupied_seats - $booking->number_of_rider;
        $driver->save();
        $this->savePoolHistory($request, $booking, 'drop_off');
        if ($driver->pool_user_id == $booking->user_id) :
            if ($driver->occupied_seats == 0) :
                //Set User_id NULL and Pick_exceed NULL
                $driver->pool_user_id = null;
                $driver->pick_exceed = null;
                $driver->status_for_pool = 0;
                $driver->save();
            else :
                $driver->pool_user_id = PoolRideList::where([['driver_id', '=', $driver->id], ['dropped', '=', 0]])->whereHas('Booking', function ($query) {
                    $query->wherein('booking_status', [1002, 1003, 1004]);
                })->oldest()->first()->user_id;
                $driver->pick_exceed = ((PoolRideList::where([['driver_id', '=', $driver->id], ['created_at', '>', $booking->created_at]])->count()) - 1) + (PoolRideList::where([['driver_id', '=', $driver->id], ['created_at', '>', $booking->created_at], ['dropped', true]])->count());
                $driver->save();
            endif;

        else : //Increase drop count and if limit matched, set driver to unavailable
            $configuration = BookingConfiguration::select('pool_maximum_exceed')->where([['merchant_id', '=', $driver->merchant_id]])->first();
            ++$driver->pick_exceed;
            ($driver->pick_exceed == $configuration->pool_maximum_exceed) ? ($driver->status_for_pool = 2008) : ''; //Check Exceed Limit and set driver to unavailable
            $driver->save();
        endif;
    }

    // Driver Action History of Rides
    public function savePoolHistory($request, $booking, $action = "pickup")
    {
        $master_booking = Booking::where('master_booking_id', $booking->master_booking_id)->orderBy('master_booking_id')->first();
        if (!empty($master_booking->id)) {
            $new_status = [
                'action' => $action,
                'booking_id' => $booking->id,
                'timestamp' => time(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ];
            if (!$master_booking->pool_history) {
                $master_booking->pool_history = json_encode([$new_status]);
                $master_booking->save();
            } else {
                $status_history = !empty(json_decode($master_booking->pool_history, true)) ? json_decode($master_booking->pool_history, true) : [];
                array_push($status_history, $new_status);
                $master_booking->pool_history = json_encode($status_history);
                $master_booking->save();
            }
        }
        return true;
    }

    public function CancelRide($booking_data, $request)
    {
        if ($booking_data->user_id != '' && $booking_data->driver_id != '') {
            $pool_list = PoolRideList::where([['user_id', '=', $booking_data->user_id], ['driver_id', '=', $booking_data->driver_id], ['booking_id', '=', $booking_data->id]]);
            $booking_data->Driver->avail_seats = $booking_data->Driver->avail_seats + $booking_data->number_of_rider;
            $booking_data->Driver->occupied_seats = $booking_data->Driver->occupied_seats - $booking_data->number_of_rider;
            $booking_data->Driver->save();
            if ($booking_data->Driver->pool_user_id == $booking_data->user_id) :
                if ($booking_data->Driver->occupied_seats == 0) :
                    //Set User_id NULL and Pick_exceed NULL
                    $booking_data->Driver->pool_user_id = null;
                    $booking_data->Driver->pick_exceed = null;
                    $booking_data->Driver->status_for_pool = 0;
                    $booking_data->Driver->save();
                else :
                    $booking_data->Driver->pool_user_id = PoolRideList::where([['driver_id', '=', $booking_data->Driver->id], ['dropped', '=', 0]])->whereHas('Booking', function ($query) {
                        $query->wherein('booking_status', [1002, 1003, 1004]);
                    })->oldest()->first()->user_id;
                    $booking_data->Driver->pick_exceed = ((PoolRideList::where([['driver_id', '=', $booking_data->Driver->id], ['created_at', '>', $booking_data->created_at]])->count()) - 1) + (PoolRideList::where([['driver_id', '=', $booking_data->Driver->id], ['created_at', '>', $booking_data->created_at], ['dropped', true]])->count());
                    $booking_data->Driver->save();
                endif;
            else : //Increase drop count and if limit matched, set driver to unavailable
                $configuration = BookingConfiguration::select('pool_maximum_exceed')->where([['merchant_id', '=', $booking_data->Driver->merchant_id]])->first();
                // ++$booking_data->Driver->pick_exceed;
                $booking_data->Driver->pick_exceed = ((PoolRideList::where([['driver_id', '=', $booking_data->Driver->id], ['created_at', '>', $booking_data->created_at]])->count()) - 1) + (PoolRideList::where([['driver_id', '=', $booking_data->Driver->id], ['created_at', '>', $booking_data->created_at], ['dropped', true]])->count());
                ($booking_data->Driver->pick_exceed == $configuration->pool_maximum_exceed) ? ($booking_data->Driver->status_for_pool = 2008) : ''; //Check Exceed Limit and set driver to unavailable
                $booking_data->Driver->save();
            endif;
            $pool_list->delete();
        }
    }
}
