<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\PolygenController;
use App\Http\Controllers\Helper\PriceController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Resources\ActiveCarpoolingResource;
use App\Http\Resources\CarpoolingResource;
use App\Models\BookingConfiguration;
use App\Models\CancelReason;
use App\Models\CarpoolingConfigCountry;
use App\Models\CarpoolingCoordinate;
use App\Models\CarpoolingOfferRideCheckout;
use App\Models\CarpoolingOfferRideCheckoutDetail;
use App\Models\CarpoolingRide;
use App\Models\CarpoolingRideCheckout;
use App\Models\CarpoolingRideDetail;
use App\Models\CarpoolingRideUserDetail;
use App\Models\Country;
use App\Models\CountryArea;
use App\Models\Onesignal;
use App\Models\PriceCard;
use App\Models\ReferralDiscount;
use App\Models\User;
use App\Models\UserDocument;
use App\Traits\ApiResponseTrait;
use App\Traits\AreaTrait;
use App\Traits\CarpoolingTrait;
use App\Traits\MerchantTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CarpoolingController extends Controller
{
    use ApiResponseTrait, AreaTrait, MerchantTrait, CarpoolingTrait;

    public function rideSearch(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'pickup_latitude' => 'required',
            'pickup_longitude' => 'required',
            'pickup_location' => 'required',
            'drop_latitude' => 'required',
            'drop_longitude' => 'required',
            'drop_location' => 'required',
            'no_of_seats' => 'required',
            'ride_timestamp' => 'required',
            'return_ride_timestamp' => 'required_if:return_ride,=,1',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $polylinePoints = [];
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);

            if ($request->pickup_latitude == $request->drop_latitude && pickup_longitude == $request->drop_longitude) {
                throw new Exception("Pickup and drop locations cannot be the same.");
            }

            if ($user->wallet_balance < 0) {
                return $this->failedResponse("$string_file.booked_ride");
            } else {
                $request->request->add([
                    "latitude" => $request->pickup_latitude,
                    "longitude" => $request->pickup_longitude,
                    "merchant_id" => $user->merchant_id,
                    "currency_symbol" => $user->Country->isoCode,
                    "not_user_id" => $user->id,
                    "user_gender" => $user->user_gender,
                ]);

                $date = date('Y-m-d', $request->ride_timestamp);
                $this->getAreaByLatLong($request);

                $route_polyline = DB::table('carpooling_ride_details')
                    ->select('carpooling_ride_details.*', 'carpooling_rides.*')
                    ->join('carpooling_rides', 'carpooling_ride_details.carpooling_ride_id', '=', 'carpooling_rides.id')
                    ->where(DB::raw('DATE(carpooling_ride_details.ride_datetime)'), '=', $date)
                    ->where('carpooling_rides.user_id', '!=', $user->id)
                    ->where('merchant_id', $user->merchant_id)
                    ->get();

                if (count($route_polyline) == 0) {
                    return $this->failedResponse(trans("common.no") . ' ' . trans("$string_file.ride") . " " . trans("common.available"));
                }

                $stop_points_details = [];
                $flag = "";

                $route_notin_polyine = [];
                $nearestPickup = [];
                $nearestDrop = [];

                foreach ($route_polyline as $ride) {

                    if ($ride->route_polyline !== NULL) {
                        $polylinePoints = $this->decodePolyline($ride->route_polyline);
                        // Find nearest pickup and drop-off points
                        $nearestPickupPoint = $this->findNearestPointOnPolyline($polylinePoints, $request->pickup_latitude, $request->pickup_longitude);
                        $nearestDropPoint = $this->findNearestPointOnPolyline($polylinePoints, $request->drop_latitude, $request->drop_longitude);

                        if (count($nearestPickupPoint) > 0 && count($nearestDropPoint) > 0) {
                            $route_notin_polyine[$ride->id] = "match";
                            $nearestPickupPoint = min($nearestPickupPoint);
                            $nearestDropPoint = min($nearestDropPoint);
                            $nearestPickup[] = $nearestPickupPoint;
                            $nearestDrop[] = $nearestDropPoint;

                            // not airel distance want distance and time according to routes (roads);
                            // $distancebtwlatlng  = $this->distance($nearestPickupPoint['lat'],$nearestPickupPoint['lng'],$nearestDropPoint['lat'],$nearestDropPoint['lng']);
                            $from = $nearestPickupPoint['lat'] . "," . $nearestPickupPoint['lng'];
                            $to = $nearestDropPoint['lat'] . "," . $nearestDropPoint['lng'];
                            $google_array = GoogleController::GoogleDistanceAndTime($from, $to, $user->Merchant->BookingConfiguration->google_key);
                            $distancebtwlatlng = round_number($google_array['distance_in_meter'] / 1000, 0);

                            // Get pickup and drop-off addresses using Google Maps API
                            $pickup_address = GoogleController::GoogleLocation($nearestPickupPoint['lat'], $nearestPickupPoint['lng'], $user->Merchant->BookingConfiguration->google_key, '', $string_file);
                            $drop_address = GoogleController::GoogleLocation($nearestDropPoint['lat'], $nearestDropPoint['lng'], $user->Merchant->BookingConfiguration->google_key, '', $string_file);
                            $ride_start_time = date("Y-m-d H:i:s", $ride->ride_timestamp);
                            $ride_start_time = convertTimeToUSERzone($ride_start_time, $user->Merchant->CountryArea[0]->timezone, $user->Merchant->id, null, 1, 1);
                            $datetime = Carbon::parse($ride_start_time);
                            $ride_start_time = $datetime->format('H:i');

                            // Calculate time between start and pickup points
                            $queryParamsPickup = [
                                'origin' => $ride->from_latitude . ',' . $ride->from_longitude,
                                'destination' => $nearestPickupPoint['lat'] . ',' . $nearestPickupPoint['lng'],
                            ];

                            $timeToPickup = GoogleController::GoogleDistanceAndTime($queryParamsPickup['origin'], $queryParamsPickup['destination'], $user->Merchant->BookingConfiguration->google_key, 'metric', true, '', $string_file);
                            $datetime = Carbon::parse($ride_start_time); // Parse the datetime string into a Carbon instance
                            $datetime = $datetime->addSeconds($timeToPickup['time_in_min']);
                            $timeToPickupdis = $timeToPickup['distance'];
                            $timePickup = $datetime->format('H:i');

                            // Calculate time between pickup and drop-off points
                            $queryParamsDrop = [
                                'origin' => $nearestPickupPoint['lat'] . ',' . $nearestPickupPoint['lng'],
                                'destination' => $nearestDropPoint['lat'] . ',' . $nearestDropPoint['lng'],
                            ];
                            $timePickupToDrop = GoogleController::GoogleDistanceAndTime($queryParamsDrop['origin'], $queryParamsDrop['destination'], $user->Merchant->BookingConfiguration->google_key, 'metric', true, '', $string_file);
                            $datetime = Carbon::parse($timePickup); // Parse the datetime string into a Carbon instance
                            $datetime = $datetime->addSeconds($timePickupToDrop['time_in_min']);
                            $timePickupToDropdis = $timePickupToDrop['distance'];
                            $timePickupDrop = $datetime->format('H:i');
                            $queryParamsEnd = [
                                'origin' => $nearestDropPoint['lat'] . ',' . $nearestDropPoint['lng'],
                                'destination' => $ride->to_latitude . ',' . $ride->to_longitude,
                            ];
                            $timeDropToEnd = GoogleController::GoogleDistanceAndTime($queryParamsEnd['origin'], $queryParamsEnd['destination'], $user->Merchant->BookingConfiguration->google_key, 'metric', true, '', $string_file);
                            $datetime = Carbon::parse($timePickupDrop); // Parse the datetime string into a Carbon instance
                            $datetime = $datetime->addSeconds($timeDropToEnd['time_in_min']);
                            $timeDropEnd = $datetime->format('H:i');
                            $timeDropToEnddis = $timeDropToEnd['distance'];

                            if (round(floatval($ride->from_latitude), 2) > round(floatval($ride->to_latitude), 2)) {

                                if ((round(floatval($ride->from_latitude), 2) >= round($nearestPickupPoint['lat'], 2)) && (round($nearestPickupPoint['lat'], 2) >= round($nearestDropPoint['lat'], 2))) {

                                    $flag = "forward";
                                } else {

                                    $flag = "reverse";
                                }
                            } else {

                                if ((round(floatval($ride->from_latitude), 2) <= round($nearestPickupPoint['lat'], 2)) && (round($nearestPickupPoint['lat'], 2) <= round($nearestDropPoint['lat'], 2))) {

                                    $flag = "forward";
                                } else {
                                    $flag = "reverse";
                                }
                            }


                            // Add stop points details for this ride
                            $stop_points_details[$ride->id] = [
                                "start" => [
                                    "lat" => $ride->from_latitude,
                                    "lng" => $ride->from_longitude,
                                    "location" => $ride->from_location,
                                    "time" => $ride_start_time,
                                    "distance" => "0 km",
                                    "min" => 0,
                                ],
                                "pickup" => [
                                    "lat" => $nearestPickupPoint['lat'],
                                    "lng" => $nearestPickupPoint['lng'],
                                    "location" => $pickup_address,
                                    "time" => $timePickup,
                                    "distance" => $timeToPickupdis,
                                    "min" => $timeToPickup['time_in_min'],
                                ],
                                "drop" => [
                                    "lat" => $nearestDropPoint['lat'],
                                    "lng" => $nearestDropPoint['lng'],
                                    "location" => $drop_address,
                                    "time" => $timePickupDrop,
                                    "distance" => $timePickupToDropdis,
                                    "min" => $timePickupToDrop['time_in_min'],
                                ],
                                "end" => [
                                    "lat" => $ride->to_latitude,
                                    "lng" => $ride->to_longitude,
                                    "location" => $ride->to_location,
                                    "time" => $timeDropEnd,
                                    "distance" => $timeDropToEnddis,
                                    "min" => $timeDropToEnd['time_in_min'],
                                ],
                                "distancebtwlatlng" => $distancebtwlatlng,
                                "direction" => $flag,

                            ];
                        } else {

                            $route_notin_polyine[$ride->id] = "notmatch";
                        }
                    }
                }


                if ((isset($nearestPickup) && empty($nearestPickup)) || (isset($nearestDrop) && empty($nearestDrop))) {

                    return $this->failedResponse(trans("common.no") . ' ' . trans("$string_file.ride") . " " . trans("common.available"));
                }


                $list_of_available_rides = CarpoolingRideDetail::searchCarpoolingRides($request, $nearestPickup[0], $nearestDrop[0], $stop_points_details, $route_notin_polyine);

                $arr = [];
                foreach ($list_of_available_rides as &$value) {

                    if ($value['route_points']['direction'] !== "reverse") {
                        $value['total_charges'] = $user->Country->isoCode . " " . round_number($value['total_charge_according_distance'], 1);
                        array_push($arr, $value);
                    }


                }


                $message = trans("common.data_found");
                if (empty($arr) || empty($nearestPickup[0]) || empty($nearestDrop[0])) {

                    return $this->failedResponse(trans("common.no") . ' ' . trans("$string_file.ride") . " " . trans("common.available"));
                }

            }
        } catch (\Exception $e) {
            // p($e->getTraceAsString());
            return $e;
        }
        // dd($message);
        return $this->successResponse($message, $arr);
    }

    public function decodePolyline($encoded)
    {
        $length = strlen($encoded);
        $index = 0;
        $points = [];
        $lat = 0;
        $lng = 0;

        while ($index < $length) {
            $shift = 0;
            $result = 0;

            do {
                $bit = ord(substr($encoded, $index++)) - 63;
                $result |= ($bit & 0x1f) << $shift;
                $shift += 5;
            } while ($bit >= 0x20);

            $deltaLat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lat += $deltaLat;

            $shift = 0;
            $result = 0;

            do {
                $bit = ord(substr($encoded, $index++)) - 63;
                $result |= ($bit & 0x1f) << $shift;
                $shift += 5;
            } while ($bit >= 0x20);

            $deltaLng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lng += $deltaLng;

            $points[] = ['lat' => ($lat / 1e5), 'lng' => ($lng / 1e5)];
        }

        return $points;
    }

    public function findNearestPointOnPolyline($polylinePoints, $userLat, $userLng)
    {

        $dis = [];
        foreach ($polylinePoints as $point) {
            $lat = $point['lat'];
            $lng = $point['lng'];

            // Calculate distance between polyline point and user's location
            $distance = $this->distance($lat, $lng, $userLat, $userLng);

            if ($distance <= 20) {
                $dis[] = array(
                    "distance" => $distance,
                    "lat" => $lat,
                    "lng" => $lng
                );
            }


        }
        return $dis;
    }

    public function distance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; //
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        return $distance;
    }

    public function rideCheckout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'carpooling_ride_id' => ['required', 'integer', Rule::exists('carpooling_rides', 'id')],
            'pickup_id' => ['required', 'integer', Rule::exists('carpooling_ride_details', 'id')],
            'drop_id' => ['required', 'integer', Rule::exists('carpooling_ride_details', 'id')],
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'pickup_latitude' => 'required',
            'pickup_longitude' => 'required',
            'pickup_location' => 'required',
            'drop_latitude' => 'required',
            'drop_longitude' => 'required',
            'drop_location' => 'required',
            'no_of_seats' => 'required',
            'ride_timestamp' => 'required',
            'female_ride' => 'required',
            'ac_ride' => 'required',
            'payment_action' => "required",
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {

            $user = $request->user('api');

            $string_file = $this->getStringFile($user->merchant_id);
            $request->request->add([
                "latitude" => $request->pickup_latitude,
                "longitude" => $request->pickup_longitude,
                "merchant_id" => $user->merchant_id,
                "currency_symbol" => $user->Country->isoCode,
                "not_user_id" => $user->id
            ]);
            $this->getAreaByLatLong($request);
            $date = date('Y-m-d', $request->ride_timestamp);
            $route_polyline = DB::table('carpooling_ride_details')
                ->select('carpooling_ride_details.*', 'carpooling_rides.*')
                ->join('carpooling_rides', 'carpooling_ride_details.carpooling_ride_id', '=', 'carpooling_rides.id')
                ->where(DB::raw('DATE(carpooling_ride_details.ride_datetime)'), '=', $date)
                ->where('carpooling_rides.user_id', '!=', $user->id)
                ->where("merchant_id", $user->merchant_id)
                ->get();

            $stop_points_details = [];
            $flag = "";

            $route_notin_polyine = [];
            $nearestPickup = [];
            $nearestDrop = [];

            foreach ($route_polyline as $ride) {
                if ($ride->route_polyline !== NULL) {
                    $polylinePoints = $this->decodePolyline($ride->route_polyline);
                    // dd($polylinePoints);

                    // Find nearest pickup and drop-off points
                    $nearestPickupPoint = $this->findNearestPointOnPolyline($polylinePoints, $request->pickup_latitude, $request->pickup_longitude);
                    $nearestDropPoint = $this->findNearestPointOnPolyline($polylinePoints, $request->drop_latitude, $request->drop_longitude);


                    if (count($nearestPickupPoint) > 0 && count($nearestDropPoint) > 0) {
                        $route_notin_polyine[$ride->id] = "match";
                        $nearestPickupPoint = min($nearestPickupPoint);
                        $nearestDropPoint = min($nearestDropPoint);
                        $nearestPickup[] = $nearestPickupPoint;
                        $nearestDrop[] = $nearestDropPoint;
                        // $distancebtwlatlng  = $this->distance($nearestPickupPoint['lat'],$nearestPickupPoint['lng'],$nearestDropPoint['lat'],$nearestDropPoint['lng']);
                        // not airel distance want distance and time according to routes (roads);
                        // $distancebtwlatlng  = $this->distance($nearestPickupPoint['lat'],$nearestPickupPoint['lng'],$nearestDropPoint['lat'],$nearestDropPoint['lng']);
                        $from = $nearestPickupPoint['lat'] . "," . $nearestPickupPoint['lng'];
                        $to = $nearestDropPoint['lat'] . "," . $nearestDropPoint['lng'];
                        $google_array = GoogleController::GoogleDistanceAndTime($from, $to, $user->Merchant->BookingConfiguration->google_key);
                        $distancebtwlatlng = round_number($google_array['distance_in_meter'] / 1000, 0);


                        // Get pickup and drop-off addresses using Google Maps API
                        $pickup_address = GoogleController::GoogleLocation($nearestPickupPoint['lat'], $nearestPickupPoint['lng'], $user->Merchant->BookingConfiguration->google_key, '', $string_file);
                        $drop_address = GoogleController::GoogleLocation($nearestDropPoint['lat'], $nearestDropPoint['lng'], $user->Merchant->BookingConfiguration->google_key, '', $string_file);
                        $ride_start_time = date("Y-m-d H:i:s", $ride->ride_timestamp);
                        $ride_start_time = convertTimeToUSERzone($ride_start_time, $user->Merchant->CountryArea[0]->timezone, $user->Merchant->id, null, 1, 1);
                        $datetime = Carbon::parse($ride_start_time);
                        $ride_start_time = $datetime->format('H:i');

                        // Calculate time between start and pickup points
                        $queryParamsPickup = [
                            'origin' => $ride->from_latitude . ',' . $ride->from_longitude,
                            'destination' => $nearestPickupPoint['lat'] . ',' . $nearestPickupPoint['lng'],
                        ];

                        $timeToPickup = GoogleController::GoogleDistanceAndTime($queryParamsPickup['origin'], $queryParamsPickup['destination'], $user->Merchant->BookingConfiguration->google_key, 'metric', true, '', $string_file);
                        $datetime = Carbon::parse($ride_start_time); // Parse the datetime string into a Carbon instance
                        $datetime = $datetime->addSeconds($timeToPickup['time_in_min']);
                        $timeToPickupdis = $timeToPickup['distance'];
                        $timePickup = $datetime->format('H:i');

                        // Calculate time between pickup and drop-off points
                        $queryParamsDrop = [
                            'origin' => $nearestPickupPoint['lat'] . ',' . $nearestPickupPoint['lng'],
                            'destination' => $nearestDropPoint['lat'] . ',' . $nearestDropPoint['lng'],
                        ];
                        $timePickupToDrop = GoogleController::GoogleDistanceAndTime($queryParamsDrop['origin'], $queryParamsDrop['destination'], $user->Merchant->BookingConfiguration->google_key, 'metric', true, '', $string_file);
                        $datetime = Carbon::parse($timePickup); // Parse the datetime string into a Carbon instance
                        $datetime = $datetime->addSeconds($timePickupToDrop['time_in_min']);
                        $timePickupToDropdis = $timePickupToDrop['distance'];
                        $timePickupDrop = $datetime->format('H:i');
                        $queryParamsEnd = [
                            'origin' => $nearestDropPoint['lat'] . ',' . $nearestDropPoint['lng'],
                            'destination' => $ride->to_latitude . ',' . $ride->to_longitude,
                        ];
                        $timeDropToEnd = GoogleController::GoogleDistanceAndTime($queryParamsEnd['origin'], $queryParamsEnd['destination'], $user->Merchant->BookingConfiguration->google_key, 'metric', true, '', $string_file);
                        $datetime = Carbon::parse($timePickupDrop); // Parse the datetime string into a Carbon instance
                        $datetime = $datetime->addSeconds($timeDropToEnd['time_in_min']);
                        $timeDropEnd = $datetime->format('H:i');
                        $timeDropToEnddis = $timeDropToEnd['distance'];

                        if (floatval($ride->from_latitude) > floatval($ride->to_latitude)) {

                            if ((floatval($ride->from_latitude) >= $nearestPickupPoint['lat']) && ($nearestPickupPoint['lat'] >= $nearestDropPoint['lat'])) {

                                $flag = "forward";
                            } else {

                                $flag = "reverse";
                            }
                        } else {

                            if (floatval($ride->from_latitude) <= $nearestPickupPoint['lat'] && $nearestPickupPoint['lat'] <= $nearestDropPoint['lat']) {
                                $flag = "forward";
                            } else {
                                $flag = "reverse";
                            }
                        }


                        // Add stop points details for this ride
                        $stop_points_details[$ride->id] = [
                            "start" => [
                                "lat" => $ride->from_latitude,
                                "lng" => $ride->from_longitude,
                                "location" => $ride->from_location,
                                "time" => $ride_start_time,
                                "distance" => "0 km",
                            ],
                            "pickup" => [
                                "lat" => $nearestPickupPoint['lat'],
                                "lng" => $nearestPickupPoint['lng'],
                                "location" => $pickup_address,
                                "time" => $timePickup,
                                "distance" => $timeToPickupdis,
                            ],
                            "drop" => [
                                "lat" => $nearestDropPoint['lat'],
                                "lng" => $nearestDropPoint['lng'],
                                "location" => $drop_address,
                                "time" => $timePickupDrop,
                                "distance" => $timePickupToDropdis,
                            ],
                            "end" => [
                                "lat" => $ride->to_latitude,
                                "lng" => $ride->to_longitude,
                                "location" => $ride->to_location,
                                "time" => $timeDropEnd,
                                "distance" => $timeDropToEnddis,
                            ],
                            "distancebtwlatlng" => $distancebtwlatlng,
                            "direction" => $flag,

                        ];
                    } else {

                        $route_notin_polyine[$ride->id] = "notmatch";
                    }
                }
            }
            $pickup_time = CarpoolingRideDetail::find($request->pickup_id);
            $drop_time = CarpoolingRideDetail::find($request->drop_id);
            $is_ride_available = CarpoolingRideDetail::searchCarpoolingRides($request, $nearestPickup[0], $nearestDrop[0], $stop_points_details, $route_notin_polyine);

            if (!empty($is_ride_available)) {
                $user_ride_checkout = CarpoolingRideCheckout::updateOrCreate(
                    ['user_id' => $request->user('api')->id],
                    [
                        'carpooling_ride_id' => $request->carpooling_ride_id,
                        'merchant_id' => $user->merchant_id,
                        'pickup_id' => $request->pickup_id,
                        'drop_id' => $request->drop_id,
                        'segment_id' => $request->segment_id,
                        'pickup_latitude' => $request->pickup_latitude,
                        'pickup_longitude' => $request->pickup_longitude,
                        'pickup_location' => $request->pickup_location,
                        'drop_latitude' => $request->drop_latitude,
                        'drop_longitude' => $request->drop_longitude,
                        'drop_location' => $request->drop_location,
                        'booked_seats' => $request->no_of_seats,
                        'ride_timestamp' => $pickup_time->ride_timestamp,
                        'end_timestamp' => $drop_time->end_timestamp,
                        'ac_ride' => $request->ac_ride,
                        'female_ride' => $request->female_ride,
                        'payment_action' => $request->payment_action,
                    ]);

                $this->calculateBillAmount($user_ride_checkout);

            } else {
                return $this->failedResponse(trans("$string_file.selected") . ' ' . trans("$string_file.ride") . ' ' . trans("$string_file.not_available_for_you"));

            }
            // $return_data = array("carpooling_ride_checkout_id" => $user_ride_checkout->id,"price_card" => $price_card );
            $return_data = array("carpooling_ride_checkout_id" => $user_ride_checkout->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.check") . trans("common.out") . ' ' . trans("common.added_successfully"), $return_data);
    }

    public function rideConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'carpooling_ride_checkout_id' => ['required', 'integer', Rule::exists('carpooling_ride_checkouts', 'id')],
            'payment_status' => 'required',
            'payment_action' => [
                'required',
                Rule::in([1, 2, 3]),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile($request->merchant_id);
            $checkout = CarpoolingRideCheckout::find($request->carpooling_ride_checkout_id);
            $checkout = $checkout->toArray();
            unset($checkout['id']);
            unset($checkout['segment_id']);
            unset($checkout['created_at']);
            unset($checkout['updated_at']);
            unset($checkout['return_ride']);

            $carpooling_ride = CarpoolingRide::find($checkout['carpooling_ride_id']);
            $carpooling_ride_details = CarpoolingRideDetail::find($checkout['pickup_id']);
            if (empty($carpooling_ride_details)) {
                return $this->failedResponse(trans('common.data_not_found'));
            }
            //driver hold amount
            //$total_amount=$carpooling_ride_details->final_charges*$checkout['booked_seats'];
            // $ride_half_amount = round($total_amount / 2); // 50% of ride balance have user wallet case
            // if ($carpooling_ride->User->wallet_balance >= $ride_half_amount){
            // $user_hold = new UserHold();
            // $user_hold->user_id = $carpooling_ride->user_id;
            // $user_hold->carpooling_ride_id = $checkout['carpooling_ride_id'];
            // $user_hold->amount = $ride_half_amount;
            // $user_hold->is_user_offer_ride = 1;
            // $user_hold->status = 0;// success
            // $user_hold->save();
            // $paramArray = array(
            //             'user_id' =>  $carpooling_ride->user_id,
            //             'carpooling_ride_id' => $carpooling_ride->id,
            //             'amount' => $user_hold->amount,
            //             'narration' => 4,
            //         );
            // WalletTransaction::UserWalletDebit($paramArray);

            // paynow (payment_status 1)

            $total_amount = $this->calculateSeatAmount($checkout, $checkout['booked_seats']);
            $message = " ";
            $otp = 2018;

            if ($request->payment_action == 1) {
                if ($user->wallet_balance >= $total_amount) {
                    $checkout['payment_status'] = $request->payment_status;
                    $checkout['carpooling_ride_detail_id'] = $checkout['pickup_id'];
                    $checkout['end_ride_id'] = $checkout['drop_id'];
                    $checkout['payment_action'] = $request->payment_action;   // payment action

                    //hold user amount
                    // $user_hold = new UserHold();
                    // $user_hold->user_id = $checkout['user_id'];
                    // $user_hold->carpooling_ride_id = $checkout['carpooling_ride_id'];
                    // $user_hold->amount = $total_amount;
                    // $user_hold->status = 0;// success
                    // $user_hold->save();
                    // $paramArray = array(
                    //     'user_id' => $user->id,
                    //     'carpooling_ride_id' => $checkout['carpooling_ride_id'],
                    //     'amount' => $user_hold->amount,
                    //     'narration' => 4,
                    // );
                    // WalletTransaction::UserWalletDebit($paramArray);


                    if (!empty($carpooling_ride)) {
                        $seats = $carpooling_ride->available_seats - $checkout['booked_seats'];
                        $carpooling_ride->available_seats = $seats;
                        $carpooling_ride->booked_seats = $carpooling_ride->booked_seats + $checkout['booked_seats'];
                        // $carpooling_ride->save();

                    }

                    $carpooling_ride_details->booked_seats = $checkout['booked_seats'];
                    $carpooling_ride_details->save();
                    $checkout['ride_status'] = 2;//booked seats
                    $checkout['ride_booking_otp'] = mt_rand(1111, 9999);
                    $booking = CarpoolingRideUserDetail::Create($checkout);
                    $count = CarpoolingRideUserDetail::where('user_id', '=', $user->id)->count();
                    $user = User::where('id', '=', $user->id)->first();
                    $user->total_trips = $count;
                    $user->save();
                    $return_param = array(
                        "id" => $carpooling_ride->id,
                        "timestamp" => time(),
                        "driver_name" => $carpooling_ride->User->first_name,
                        'slug' => 'RIDE_BOOKED_BY_USER',
                    );
                    $logs_history = json_decode($carpooling_ride->carpooling_logs, true);
                    $passenger_info = array("passenger_name" => $booking->User->first_name);
                    array_push($return_param, $passenger_info);
                    array_push($logs_history, $return_param);
                    $carpooling_ride->carpooling_logs = json_encode($logs_history);
                    $booking->carpooling_logs = json_encode($logs_history);
                    $booking->save();
                    $carpooling_ride->save();
                    $paramArray = array(
                        'user_id' => $checkout['user_id'],
                        'amount' => $total_amount,
                        'narration' => 5,
                        'carpooling_ride_user_detail_id' => $booking->id,
                        'carpooling_ride_id' => $checkout['carpooling_ride_id'],
                    );
                    WalletTransaction::UserWalletDebit($paramArray);
                    $data = [];
                    $message = trans("$string_file.debit_transaction_amount", ['user_name' => $booking->User->first_name, 'amount' => $total_amount]);
                    $title = trans("$string_file.amount_debited");
                    $notification_type = 'Wallet_Notification';
                    $this->sendNotificationToUser($data, $message, $title, $notification_type, $booking->user_id, $booking->merchant_id);
                    $data = ['ride_status' => 2, 'ride_id' => $carpooling_ride->id, 'booking_id' => (int)$carpooling_ride->id];
                    $message = trans("$string_file.confirm_ride", ['drivername' => $carpooling_ride->User->first_name, 'passengername' => $booking->User->first_name, 'seatnumber' => $booking->booked_seats, 'ID' => $carpooling_ride->id]);
                    $title = 'Booking notification';
                    $notification_type = 'Booking_Request';
                    $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride->user_id, $carpooling_ride->merchant_id);
                } else {
                    return $this->failedResponse(trans("common.wallet") . " " . trans("common.is") . " " . trans("common.low") . " " . trans("common.for") . " " . trans("common.this") . " " . trans("$string_file.ride"));
                }
            } // pay-later case and partially book ride case  payment type 2

            elseif ($request->payment_action == 2) {

                // $ride_half_amount = round($total_amount / 2); // 50% of ride balance have user wallet case
                // if ($user->wallet_balance >= $ride_half_amount) {
                $carpooling_ride_user_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $checkout['carpooling_ride_id']], ['user_id', '=', $checkout['user_id']], ['payment_action', '=', 2]])->count();
                if (empty($carpooling_ride_user_details)) {
                    $checkout['ride_status'] = 1; // partial accepted
                    $checkout['payment_status'] = $request->payment_status;
                    $checkout['carpooling_ride_detail_id'] = $checkout['pickup_id'];
                    $checkout['end_ride_id'] = $checkout['drop_id'];
                    $checkout['payment_method_id'] = 1;   // payment method casho
                    $checkout['payment_action'] = $request->payment_action;   // payment action
                    // $user_hold = new UserHold();
                    // $user_hold->user_id = $checkout['user_id'];
                    // $user_hold->carpooling_ride_id = $checkout['carpooling_ride_id'];
                    // $user_hold->amount = $ride_half_amount;
                    // $user_hold->status = 0;
                    // $user_hold->save();
                    $checkout['ride_booking_otp'] = mt_rand(1111, 9999);
                    $booking = CarpoolingRideUserDetail::Create($checkout);
                    $count = CarpoolingRideUserDetail::where('user_id', '=', $user->id)->count();
                    $user = User::where('id', '=', $user->id)->first();
                    $user->total_trips += $count;
                    $user->save();
                    //  $this->carpoolingRideUserLog($booking,$slug="RIDE_REQUESTED");
                    $this->carpoolingRideLog($carpooling_ride, $slug = "RIDE_REQUESTED_BY_USER_FOR_CASH", $booking);
                    $data = ['ride_status' => 1, 'ride_id' => $carpooling_ride->id, 'booking_id' => (int)$carpooling_ride->id];
                    $message = trans("$string_file.partial_ride_confirm", ['drivername' => $carpooling_ride->User->first_name, 'passengername' => $booking->User->first_name, 'seatnumber' => $booking->booked_seats, 'ID' => $carpooling_ride->id]);
                    $title = 'Booking notification';
                    $notification_type = 'Booking_Request';
                    $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride->user_id, $carpooling_ride->merchant_id);
                } else {
                    return $this->failedResponse(trans("$string_file.cash_ride_schedule"));
                }
            }
            // else{
            //     return $this->failedResponse(trans("common.wallet") . " " . trans("common.is") . " " . trans("common.low") . " " . trans("common.for") . " " . trans("common.this") . " " . trans("$string_file.ride"));
            // }
            //}
            elseif ($request->payment_action == 3) {
                $carpooling_ride_user_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $checkout['carpooling_ride_id']], ['user_id', '=', $checkout['user_id']], ['payment_action', '=', 3]])->count();
                //p($carpooling_ride_user_details);
                if (empty($carpooling_ride_user_details)) {
                    $ride_amount = $total_amount;
                    if ($user->wallet_balance >= $ride_amount) {
                        $checkout['ride_status'] = 1; // partial accepted
                        $checkout['payment_status'] = $request->payment_status;
                        $checkout['carpooling_ride_detail_id'] = $checkout['pickup_id'];
                        $checkout['end_ride_id'] = $checkout['drop_id'];
                        $checkout['payment_action'] = $request->payment_action;   // payment action
                        // $user_hold = new UserHold();
                        // $user_hold->user_id = $checkout['user_id'];
                        // $user_hold->carpooling_ride_id = $checkout['carpooling_ride_id'];
                        // $user_hold->amount = $ride_half_amount;
                        // $user_hold->status = 0;
                        // $user_hold->save();
                        $checkout['ride_booking_otp'] = mt_rand(1111, 9999);
                        $booking = CarpoolingRideUserDetail::Create($checkout);
                        $count = CarpoolingRideUserDetail::where('user_id', '=', $user->id)->count();
                        $user = User::where('id', '=', $user->id)->first();
                        $user->total_trips = $count;
                        $user->save();
                        //$this->carpoolingRideUserLog($booking,$slug="RIDE_REQUESTED");
                        $this->carpoolingRideLog($carpooling_ride, $slug = "RIDE_REQUESTED_BY_USER_FOR_WALLET", $booking);
                        $data = ['ride_status' => 1, 'ride_id' => $carpooling_ride->id, 'booking_id' => (int)$carpooling_ride->id];
                        $message = trans("$string_file.partial_ride_confirm", ['drivername' => $carpooling_ride->User->first_name, 'passengername' => $booking->User->first_name, 'seatnumber' => $booking->booked_seats, 'ID' => $carpooling_ride->id]);
                        $title = 'Booking notification';
                        $notification_type = 'Booking_Request';
                        $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride->user_id, $carpooling_ride->merchant_id);

                    } else {
                        return $this->failedResponse(trans("common.wallet") . " " . trans("common.is") . " " . trans("common.low") . " " . trans("common.for") . " " . trans("common.this") . " " . trans("$string_file.ride"));
                    }
                } else {
                    return $this->failedResponse(trans("$string_file.wallet_ride_schedule"));
                }
            }

            CarpoolingRideCheckout::where('id', $request->carpooling_ride_checkout_id)->delete();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.ride") . ' ' . trans("$string_file.booked_successfully"));
    }

    public function sendNotificationToUser($data, $message, $title, $notification_type, $user_ids, $merchant_id)
    {
        $data['notification_type'] = $notification_type;
        // $data['segment_type'] = $segment_details->slag;
        // $data['segment_sub_group'] = $segment_details->sub_group_for_app;
        // $data['segment_group_id'] = $segment_details->segment_group_id;
        // $data['segment_data'] = $segment_details->data;
        $arr_param['user_id'] = $user_ids;
        $arr_param['data'] = $data;
        $arr_param['segment_data'] = $data;
        $arr_param['message'] = $message;
        $arr_param['merchant_id'] = $merchant_id;
        $arr_param['title'] = $title; // notification title
        $arr_param['large_icon'] = null;
        Onesignal::UserPushMessage($arr_param);
    }

    public function offerRideCheckout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'pickup_latitude' => 'required',
            'pickup_longitude' => 'required',
            'pickup_location' => 'required',
            'drop_latitude' => 'required',
            'drop_longitude' => 'required',
            'drop_location' => 'required',
            'no_of_seats' => 'required',
            'ride_timestamp' => 'required',
            'ac_ride' => 'required',
            'female_ride' => 'required',
            'payment_type' => 'required',
            'return_ride' => 'required',
            'return_ride_timestamp' => 'required_if:return_ride,1',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile($user->merchant_id);
            if ($request->ride_timestamp <= time()) {
                return $this->failedResponse(trans("$string_file.future_ride_only"));
            }

            $area = PolygenController::Area($request->pickup_latitude, $request->pickup_longitude, $user->merchant_id);
            CarpoolingOfferRideCheckoutDetail::with(["CarpoolingOfferRideCheckout" => function ($query) use ($user) {
                $query->where("user_id", $user->id);
            }])->delete();
            CarpoolingOfferRideCheckout::where("user_id", $user->id)->delete();

            //check user default vehicle
            $userId = $user->id;
            $default_vehicle = DB::table('user_vehicles')
                ->whereIn('user_id', function ($query) use ($userId) {
                    $query->select('user_id')
                        ->from('users')
                        ->where('user_id', $userId)
                        ->where('active_default_vehicle', 1);
                })
                ->where('vehicle_verification_status', 2)
                ->limit(1)
                ->first();


            if (empty($default_vehicle)) {
                $message = trans("common.do_not_have_any_default_vehicle");
                return $this->failedResponse($message);
            }

            if (isset($default_vehicle->no_of_seats) && $default_vehicle->no_of_seats < $request->no_of_seats) {
                return $this->failedResponse(trans("$string_file.no_of_booked_seats_should_be_less_then_equals_to_vehicle_seats"));
            }

            $user_offer_ride_checkout = CarpoolingOfferRideCheckout::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'user_vehicle_id' => $default_vehicle->id,
                    'segment_id' => $request->segment_id,
                    'merchant_id' => $user->merchant_id,
                    'country_area_id' => !empty($area['id']) ? $area['id'] : NULL,
                    'start_latitude' => $request->pickup_latitude,
                    'start_longitude' => $request->pickup_longitude,
                    'start_location' => $request->pickup_location,
                    'end_latitude' => $request->drop_latitude,
                    'end_longitude' => $request->drop_longitude,
                    'end_location' => $request->drop_location,
                    'available_seats' => $request->no_of_seats,
                    'ac_ride' => $request->ac_ride,
                    'female_ride' => $request->female_ride,
                    'payment_type' => $request->payment_type,
                    'ride_timestamp' => $request->ride_timestamp,
                    'return_ride' => $request->return_ride,
                    'return_ride_timestamp' => $request->return_ride_timestamp,
                    'route_polyline' => $request->route_polyline
                ]);

            if (isset($request->drop_points) && !empty($request->drop_points)) {

                $drop_points = json_decode($request->drop_points, true);
                $drop_no = 1;
                $previous_point = array("latitude" => $request->pickup_latitude, "longitude" => $request->pickup_longitude, "location" => $request->pickup_location);
                foreach ($drop_points as $drop_point) {
                    CarpoolingOfferRideCheckoutDetail::create(array(
                        "carpooling_offer_ride_checkout_id" => $user_offer_ride_checkout->id,
                        "from_latitude" => $previous_point['latitude'],
                        "from_longitude" => $previous_point['longitude'],
                        "from_location" => $previous_point['location'],
                        "to_latitude" => $drop_point['latitude'],
                        "to_longitude" => $drop_point['longitude'],
                        "to_location" => $drop_point['location'],
                        "estimate_charges" => "",
                        "estimate_distance" => "",
                        "map_image" => "",
                        "final_charges" => "",
                        "drop_no" => $drop_no++,
                        "ride_timestamp" => $request->ride_timestamp,
                        'route_polyline' => $request->route_polyline
                    ));
                    $previous_point = $drop_point;
                }
                CarpoolingOfferRideCheckoutDetail::create(array(
                    "carpooling_offer_ride_checkout_id" => $user_offer_ride_checkout->id,
                    "from_latitude" => $previous_point['latitude'],
                    "from_longitude" => $previous_point['longitude'],
                    "from_location" => $previous_point['location'],
                    "to_latitude" => $request->drop_latitude,
                    "to_longitude" => $request->drop_longitude,
                    "to_location" => $request->drop_location,
                    "estimate_charges" => "",
                    "estimate_distance" => "",
                    "map_image" => "",
                    "final_charges" => "",
                    "drop_no" => $drop_no,
                    "ride_timestamp" => $request->ride_timestamp,
                    'route_polyline' => $request->route_polyline
                ));
            } else {

                CarpoolingOfferRideCheckoutDetail::create(array(
                    "carpooling_offer_ride_checkout_id" => $user_offer_ride_checkout->id,
                    "from_latitude" => $request->pickup_latitude,
                    "from_longitude" => $request->pickup_longitude,
                    "from_location" => $request->pickup_location,
                    "to_latitude" => $request->drop_latitude,
                    "to_longitude" => $request->drop_longitude,
                    "to_location" => $request->drop_location,
                    "estimate_charges" => "",
                    "estimate_distance" => "",
                    "map_image" => "",
                    "final_charges" => "",
                    "drop_no" => "1",
                    "ride_timestamp" => $request->ride_timestamp,
                    'route_polyline' => $request->route_polyline
                ));
            }
            $configuration = BookingConfiguration::select('google_key')->where("merchant_id", $user->merchant_id)->first();
            if (empty($user->country_id)) {
                throw new \Exception(trans("common.country") . " " . trans("common.data_not_found"));
            }
            $country = Country::select("distance_unit")->find($user->country_id);
            $drop_points_details = CarpoolingOfferRideCheckoutDetail::where("carpooling_offer_ride_checkout_id", $user_offer_ride_checkout->id)->orderby('drop_no', 'ASC')->get();


            $start_timestamp = $request->ride_timestamp;
            foreach ($drop_points_details as $drop_points_detail) {
                $drop_path = [];
                array_push($drop_path,
                    array(
                        "stop" => "1",
                        "drop_latitude" => $drop_points_detail->to_latitude,
                        "drop_longitude" => $drop_points_detail->to_longitude,
                        "drop_location" => $drop_points_detail->to_location,
                        "status" => "1"
                    )
                );
                $google_response = GoogleController::GoogleStaticImageAndDistance($drop_points_detail->from_latitude, $drop_points_detail->from_longitude, $drop_path, $configuration->google_key);
                $distance = ($country->distance_unit == 1) ? round($google_response['total_distance'] / 1000) : round($google_response['total_distance'] / 1609);
                $distance_text = ($country->distance_unit == 1) ? $distance . ' km' : $distance . ' m';

                $request->request->add(['latitude' => $drop_points_detail->from_latitude, 'longitude' => $drop_points_detail->from_longitude]);
                $this->getAreaByLatLong($request);

                $estimate_array = $this->estimateCalculate($user->merchant_id, $request->area, $request->segment_id, $distance);
                // dd($estimate_array);

                CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $user_offer_ride_checkout->id], ["drop_no", "=", $drop_points_detail->drop_no]])->update(
                    array(
                        "price_card_id" => $estimate_array['price_card_id'],
                        "estimate_distance" => $distance,
                        "estimate_distance_text" => $distance_text,
                        "map_image" => $google_response['image'],
                        "estimate_charges" => $estimate_array['total_amount'],
                        "bill_details" => json_encode($estimate_array),
                        "ride_timestamp" => $start_timestamp,
                        "end_timestamp" => $start_timestamp + round($google_response['total_time_minutes'] * 60),
                    )
                );

                //                $start_timestamp = $start_timestamp + ($google_response['total_time_minutes'] * 60) + (2 * 60); // Add minute difference in stops
            }

            $drop_point_for_map = CarpoolingOfferRideCheckoutDetail::select("drop_no as stop", "to_latitude as drop_latitude", "to_longitude as drop_longitude", "to_location as drop_location", DB::raw('1 as status'))->where("carpooling_offer_ride_checkout_id", $user_offer_ride_checkout->id)->orderBy("stop")->get()->toArray();

            $google_response_complete = GoogleController::GoogleStaticImageAndDistance($request->pickup_latitude, $request->pickup_longitude, $drop_point_for_map, $configuration->google_key);
            $user_offer_ride_checkout->map_image = $google_response_complete['image'];
            $user_offer_ride_checkout->save();

            $drop_points_details = CarpoolingOfferRideCheckoutDetail::select("drop_no", "from_location", "to_location", "estimate_charges", "ride_timestamp", "end_timestamp")->where("carpooling_offer_ride_checkout_id", $user_offer_ride_checkout->id)->orderBy("drop_no")->get()->toArray();

            // $drop_details=[];
            // if(!empty( $drop_points_details)){
            //     foreach( $drop_points_details as $value){
            //         $drop_details=array(
            //         'drop_no'=>$value['drop_no'],
            //         'from_location'=>$value['from_location'],
            //         'to_location'=>$value['to_location'],
            //         'estimate_charges'=>$user->Country->isoCode." ".$value['estimate_charges']
            //         );
            //     }
            // }
            $return_data = array(
                'offer_ride_checkout_id' => $user_offer_ride_checkout->id,
                'drop_points_details' => $drop_points_details,
                'map_image' => $google_response_complete['image'],
                'currency' => $user->Country->isoCode,
                'ride_dateTime' => date('d F Y H:i:s', $request->ride_timestamp),
                'max_amount' => 50,
                'min_amount' => 50,
                'add_minus_limit' => 10,

            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        $message = trans("common.check") . trans("common.out") . " " . trans("common.created") . " " . trans("common.successfully");
        return $this->successResponse($message, $return_data);
    }

    public function offerRideDeleteDrop(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_ride_checkout_id' => ['required', 'integer', Rule::exists('carpooling_offer_ride_checkouts', 'id')->where(function ($query) {
            })],
            'drop_no' => ['required', 'integer',],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $configuration = BookingConfiguration::select('google_key')->where("merchant_id", $user->merchant_id)->first();
            $country = Country::select("distance_unit")->find($user->country_id);
            $offer_ride_checkout = CarpoolingOfferRideCheckout::find($request->offer_ride_checkout_id);
            $total_drop_count = CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id]])->count();
            $string_file = $this->getStringFile($offer_ride_checkout->merchant_id);
            if ($request->drop_no == 1) {
                $drop_no = 1;
                $deleted_point = CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id], ["drop_no", "=", $request->drop_no]])->first();
                $offer_ride_checkout->start_latitude = $deleted_point->to_latitude;
                $offer_ride_checkout->start_longitude = $deleted_point->to_longitude;
                $offer_ride_checkout->start_location = $deleted_point->to_location;
                $deleted_point->delete();
                $reorder_drop_points = CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id]])->get();
                foreach ($reorder_drop_points as $reorder_drop_point) {
                    $reorder_drop_point->drop_no = $drop_no;
                    $reorder_drop_point->update();
                    $drop_no++;
                }
            } elseif ($request->drop_no == $total_drop_count) {
                $deleted_point = CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id], ["drop_no", "=", $request->drop_no]])->first();
                $offer_ride_checkout->end_latitude = $deleted_point->to_latitude;
                $offer_ride_checkout->end_longitude = $deleted_point->to_longitude;
                $offer_ride_checkout->end_location = $deleted_point->to_location;
                $deleted_point->delete();
            } elseif ($request->drop_no > 1) {
                $previous_point = CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id], ["drop_no", "=", ($request->drop_no - 1)]])->first();
                $deleted_point = CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id], ["drop_no", "=", $request->drop_no]])->first();

                $previous_point->to_latitude = $deleted_point->to_latitude;
                $previous_point->to_longitude = $deleted_point->to_longitude;
                $previous_point->to_location = $deleted_point->to_location;

                $deleted_point->delete();

                $drop_path = array(array(
                    "stop" => "1",
                    "drop_latitude" => $previous_point->to_latitude,
                    "drop_longitude" => $previous_point->to_longitude,
                    "drop_location" => $previous_point->to_location,
                    "status" => "1"
                ));

                $google_response = GoogleController::GoogleStaticImageAndDistance($previous_point->from_latitude, $previous_point->from_longitude, $drop_path, $configuration->google_key);

                $distance = ($country->distance_unit == 1) ? round($google_response['total_distance'] / 1000) : round($google_response['total_distance'] / 1609);
                $distance_text = ($country->distance_unit == 1) ? $distance . ' km' : $distance . ' m';

                $request->request->add(['latitude' => $previous_point->from_latitude, 'longitude' => $previous_point->from_longitude]);
                $this->getAreaByLatLong($request);

                $estimate_array = $this->estimateCalculate($user->merchant_id, $request->area, $offer_ride_checkout->segment_id, $distance);

                $previous_point->estimate_distance = $distance;
                $previous_point->estimate_distance_text = $distance_text;
                $previous_point->map_image = $google_response['image'];
                $previous_point->estimate_charges = $estimate_array['total_amount'];
                $previous_point->bill_details = json_encode($estimate_array);
                $previous_point->end_timestamp = $previous_point->ride_timestamp + ($google_response['total_time_minutes'] * 60);
                $previous_point->save();

                $drop_no = 1;
                $reorder_drop_points = CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $offer_ride_checkout->id]])->get();
                foreach ($reorder_drop_points as $reorder_drop_point) {
                    $reorder_drop_point->drop_no = $drop_no;
                    $reorder_drop_point->update();
                    $drop_no++;
                }
            }
            $drop_point_for_map = CarpoolingOfferRideCheckoutDetail::select("drop_no as stop", "to_latitude as drop_latitude", "to_longitude as drop_longitude", "to_location as drop_location", DB::raw('1 as status'))->where("carpooling_offer_ride_checkout_id", $offer_ride_checkout->id)->orderBy("stop")->get()->toArray();
            $google_response_complete = GoogleController::GoogleStaticImageAndDistance($offer_ride_checkout->start_latitude, $offer_ride_checkout->start_longitude, $drop_point_for_map, $configuration->google_key);
            $offer_ride_checkout->map_image = $google_response_complete['image'];
            $offer_ride_checkout->save();
            $drop_points_details = CarpoolingOfferRideCheckoutDetail::select("drop_no", "from_location", "to_location", "estimate_charges")->where("carpooling_offer_ride_checkout_id", $offer_ride_checkout->id)->orderBy("drop_no")->get()->toArray();
            $return_data = array(
                'offer_ride_checkout_id' => $offer_ride_checkout->id,
                'drop_points_details' => $drop_points_details,
                'map_image' => $google_response_complete['image']
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.drop") . ' ' . trans("common.point") . ' ' . trans("common.deleted_successfully"), $return_data);
    }

    public function offerRideModifyCharges(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_ride_checkout_id' => ['required', 'integer', Rule::exists('carpooling_offer_ride_checkouts', 'id')->where(function ($query) {
            })],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile($user->merchant_id);
            $user_offer_ride_checkout = CarpoolingOfferRideCheckout::find($request->offer_ride_checkout_id);
            if (!empty($request->additional_notes)) {
                $user_offer_ride_checkout->additional_notes = $request->additional_notes;
                $user_offer_ride_checkout->save();
            }
            if (isset($request->updated_amount) && !empty($request->updated_amount)) {
                $updated_amount_arr = json_decode($request->updated_amount, true);
                foreach ($updated_amount_arr as $updated_amount) {
                    CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $user_offer_ride_checkout->id], ["drop_no", "=", $updated_amount["drop_no"]]])->update(array(
                        "final_charges" => $updated_amount["amount"],

                    ));
                }
            } else {
                $drop_points = CarpoolingOfferRideCheckoutDetail::where([["carpooling_offer_ride_checkout_id", "=", $user_offer_ride_checkout->id]])->get();
                foreach ($drop_points as $drop_point) {
                    $drop_point->final_charges = $drop_point->estimate_charges;
                    $drop_point->save();
                }
            }
            if ($user_offer_ride_checkout->return_ride == 1 && !empty($user_offer_ride_checkout->return_ride_timestamp)) {
                $this->addReturnRouteData($user_offer_ride_checkout);
            }
            $offer_checkout_response = $this->offerRideCheckoutResponse($user_offer_ride_checkout, $string_file);
        } catch (\Exception $e) {
            DB::rollBack();
            p($e->getTraceAsString());
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.check") . trans("common.out") . ' ' . trans("$string_file.confirmation"), $offer_checkout_response);
    }

    public function offerRideConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_ride_checkout_id' => ['required', 'integer', Rule::exists('carpooling_offer_ride_checkouts', 'id')->where(function ($query) {
            })],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile($user->merchant_id);
            $offer_checkout = CarpoolingOfferRideCheckout::find($request->offer_ride_checkout_id);
            $offer_checkout_detail = CarpoolingOfferRideCheckoutDetail::where("carpooling_offer_ride_checkout_id", $request->offer_ride_checkout_id)->first();
            unset($offer_checkout->id);
            unset($offer_checkout->created_at);
            unset($offer_checkout->updated_at);
            $start_time = $offer_checkout_detail->ride_timestamp;
            $end_time = $offer_checkout_detail->end_timestamp;
            $is_user_offer_ride = CarpoolingRide::whereHas("CarpoolingRideDetail", function ($q) use ($start_time, $end_time) {
                $q->whereBetween("ride_timestamp", [$start_time, $end_time]);
                $q->orWhereBetween("end_timestamp", [$start_time, $end_time]);
            })->where("user_id", $offer_checkout->user_id)->whereIn('ride_status', array(1, 2, 3, 4))->get()->count();

            if ($is_user_offer_ride > 0) {
                return $this->failedResponse(trans("$string_file.ride_offered_time"));
            } else {
                $offer_ride = CarpoolingRide::create($offer_checkout->toArray());
                $offer_checkout_details = CarpoolingOfferRideCheckoutDetail::where("carpooling_offer_ride_checkout_id", $request->offer_ride_checkout_id)->orderBy("drop_no")->get();
                foreach ($offer_checkout_details as $offer_checkout_detail) {
                    unset($offer_checkout_detail->id);
                    unset($offer_checkout_detail->carpooling_offer_ride_checkout_id);
                    unset($offer_checkout_detail->created_at);
                    unset($offer_checkout_detail->updated_at);
                    $details = $offer_checkout_detail->toArray();
                    $details['carpooling_ride_id'] = $offer_ride->id;
                    $details['ride_datetime'] = date('Y-m-d h:i:s', $start_time);
                    CarpoolingRideDetail::insert($details);

                }
                $temp = CarpoolingRideDetail::where([['carpooling_ride_id', '=', $offer_ride->id]])->count();
                $offer_ride->no_of_stops = $temp - 1;
                $count = CarpoolingRide::where('user_id', '=', $user->id)->count();
                $user = User::where('id', '=', $user->id)->first();
                $user->total_offer_rides = $count;
                $user->save();
                $this->carpoolingRideLog($offer_ride, $slug = "RIDE_CREATE");
                $offer_ride->save();
                $offer_checkout->delete();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.offer") . " " . trans("$string_file.ride") . ' ' . trans("common.successfully"));
    }

    public function offerRides(Request $request)
    {
        $user = $request->user('api');
        $user_id = $user->id;
        DB::beginTransaction();
        try {
            $upcoming_ride_details = CarpoolingRide::select('id', 'user_vehicle_id', 'start_location', 'end_location', 'ride_status', 'ride_timestamp', 'return_ride', 'return_ride_timestamp', 'ac_ride', 'female_ride', 'payment_type', 'available_seats', 'booked_seats', 'no_of_stops')
                ->where([['user_id', '=', $user->id], ['merchant_id', '=', $user->merchant_id]])->whereIn('ride_status', [1, 2])->latest()->get();
            $newArray = $upcoming_ride_details->toArray();
            $upcoming_ride_data = array("data" => []);
            if (!empty($newArray)) {
                foreach ($newArray as &$value) {
                    $carpooling_details = CarpoolingRideDetail::where('carpooling_ride_id', '=', $value['id'])->whereIn('ride_status', [1, 2, 3, 4]);
                    $carpooling_ride_requests = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $value['id']], ['ride_status', '=', 1]])->whereIn('payment_action', array(2, 3))->count();
                    //$carpooling_ride_details= $carpooling_details->get();
                    $value['ride_amount'] = $user->Country->isoCode . ' ' . $carpooling_details->sum('final_charges');
                    $value['user_requests'] = $carpooling_ride_requests;
                    $value['payment_method_text'] = $value['payment_type'] == 1 ? trans("common.cash_online") : trans("common.online");


                    //$value['no_of_stops']=$carpooling_details->no_of_stops;
                    //$value['no_of_stops']=$carpooling_details->no_of_stops;

                    // foreach($carpooling_details as $carpooling_details_value){
                    //     $upcoming_ride[] = array(
                    //         'id' => $value['id'],
                    //         'start_location' => $value['start_location'],
                    //         'end_location' => $value['end_location'],
                    //         'request'=> [],
                    //         'ac_ride' => $value['ac_ride'] == 1 ? "Yes" : "No",
                    //         'female_ride' => $value['female_ride'] == 1 ? "Yes" : "No",
                    //         'no_of_stop'=>$value['no_of_stops'] == 1 ? 1: 0,
                    //         'payment_type' => $value['payment_type'] == 1 ? "Cash Only" : "Online Payment",
                    //         'ride_date' => date('M j', $value['ride_timestamp']),
                    //         'ride_time'=>$value['ride_timestamp'],
                    //         'ride_amount'=>$user->Country->isoCode.' '.$carpooling_details_value['final_charges'],
                    //         'ride_status'=>$value['ride_status'],

                    //     );

                    // }
                    //         $temp = CarpoolingRideDetail::where([['carpooling_ride_id','=',$value['id']],['is_return','!=',1]])->get();
                    //   p($temp);
                }

            }
            $upcoming_ride_data = array('data' => $newArray);


            $ongoing_ride_details = CarpoolingRide::select('id', 'user_vehicle_id', 'ride_status', 'start_location', 'end_location', 'ride_timestamp', 'return_ride', 'payment_type', 'return_ride_timestamp', 'ac_ride', 'female_ride', 'available_seats', 'booked_seats', 'no_of_stops')
                ->where([['user_id', '=', $user->id], ['merchant_id', '=', $user->merchant_id], ['ride_status', '=', 3]])->latest()->get();
            $ongoing_ride_data = array("data" => []);
            $newArray = $ongoing_ride_details->toArray();
            if (!empty($newArray)) {
                $ongoing_ride = [];
                foreach ($newArray as &$value) {
                    $carpooling_details = CarpoolingRideDetail::where([['carpooling_ride_id', '=', $value['id']]])->whereIn('ride_status', [1, 2, 3, 4]);
                    // $carpooling_ride_details= $carpooling_details->get();
                    $carpooling_ride_user_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $value['id']], ['ride_status', '=', 1]])->whereIn('payment_action', array(1, 2, 3))->get()->toArray();
                    $value['ride_amount'] = $user->Country->isoCode . ' ' . $carpooling_details->sum('final_charges');
                    $value['user_requests'] = 0;
                    $value['payment_method_text'] = $value['payment_type'] == 1 ? trans("common.cash_online") : trans("common.online");
                    //$value['per_seat_amount']=$this->calculateSeatAmount( $carpooling_ride_user_details,$carpooling_ride_user_details->booked_seats);

                    //p($carpooling_details);
                    // foreach($carpooling_details as $total_amount){
                    //     $upcoming_ride[] = array(
                    //         'id' => $value['id'],
                    //         'start_location' => $value['start_location'],
                    //         'end_location' => $value['end_location'],
                    //         'request'=>$request,
                    //         'ac_ride' => $value['ac_ride'] == 1 ? "Yes" : "No",
                    //         'female_ride' => $value['female_ride'] == 1 ? "Yes" : "No",
                    //         //'no_of_stop'=>$value['no_of_stops'] == 1 ? "yes": 0,

                    //         'ride_date' => date('M j', $value['ride_timestamp']),
                    //          'ride_time'=>$value['ride_timestamp'],
                    //         'ride_amount'=>$user->Country->isoCode.' '.$carpooling_details_value->sum('final_charges');
                    //         'ride_status'=>$value['ride_status'],

                    //     );

                    // }
                }
                $ongoing_ride_data = array('data' => $newArray);

            }
            $return_data = [
                'upcoming_ride' => $upcoming_ride_data,
                'ongoing_ride' => $ongoing_ride_data,

            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $return_data);

    }

    public function takenRides(Request $request)
    {
        $user = $request->user('api');
        DB::beginTransaction();
        try {
            $upcoming_ride_details = CarpoolingRideUserDetail::where([['user_id', '=', $user->id], ['merchant_id', '=', $user->merchant_id]])->whereIn('ride_status', [1, 2])->limit(10)->latest()->get();
            $user_rating = CarpoolingRideUserDetail::where([['user_id', '=', $user->id], ['ride_status', '=', 4]]);
            $upcoming_ride = [];
            if (!empty($upcoming_ride_details)) {
                foreach ($upcoming_ride_details as $value) {
                    $upcoming_ride[] = array(
                        'id' => $value->id,
                        'unique_id' => $value->carpooling_ride_id . "-" . $value->id,
                        'start_location' => $value->pickup_location,
                        'end_location' => $value->drop_location,
                        'ac_ride' => $value->ac_ride == true ? "1" : "0",
                        'ride_date' => $value->ride_timestamp,
                        'end_ride_date' => $value->end_timestamp,
                        'offer_user_name' => $value->CarpoolingRide->User->first_name,
                        'offer_user_rating' => $value->CarpoolingRide->User->driver_rating,
                        'otp' => $value->ride_booking_otp,
                        'booked_seat' => $value->booked_seats,
                        'female_ride' => $value->CarpoolingRide->female_ride == true ? " 1" : "0",
                        // 'final_charges' => $value->CarpoolingRide->User->Country->isoCode . ' ' . ($this->calculateSeatAmount($value, $value->booked_seats)) / $value->booked_seats,
                        'final_charges' => $value->CarpoolingRide->User->Country->isoCode . ' ' . round_number($value->ride_amount / $value->booked_seats, 1),
                        'profile_image' => get_image($value->CarpoolingRide->User->UserProfileImage, 'user', $user->merchant_id),
                        'is_ride_confirm' => ($value->ride_status == 2),
                        'ride_status' => $value->ride_status,

                    );
                }
            }
            $ongoing_ride_details = CarpoolingRideUserDetail::where([['user_id', '=', $user->id], ['merchant_id', '=', $user->merchant_id], ['ride_status', '=', 3]])->limit(10)->latest()->get();
            $ongoing_ride = [];
            if (!empty($ongoing_ride_details)) {
                foreach ($ongoing_ride_details as $value) {
                    $ongoing_ride[] = array(
                        'id' => $value->id,
                        'unique_id' => $value->carpooling_ride_id . "-" . $value->id,
                        'start_location' => $value->pickup_location,
                        'end_location' => $value->drop_location,
                        'ac_ride' => $value->ac_ride == true ? "1" : "0",
                        'ride_date' => $value->ride_timestamp,
                        'end_ride_date' => $value->end_timestamp,
                        'offer_user_name' => $value->CarpoolingRide->User->first_name,
                        'offer_user_rating' => $value->CarpoolingRide->User->driver_rating,
                        'otp' => $value->ride_booking_otp,
                        'booked_seat' => $value->booked_seats,
                        'female_ride' => $value->female_ride == true ? " 1" : "0",
                        // 'final_charges' => $value->CarpoolingRide->User->Country->isoCode . ' ' . ($this->calculateSeatAmount($value, $value->booked_seats)) / $value->booked_seats,
                        'final_charges' => $value->CarpoolingRide->User->Country->isoCode . ' ' . round_number($value->ride_amount / $value->booked_seats, 1),
                        'profile_image' => get_image($value->CarpoolingRide->User->UserProfileImage, 'user', $user->merchant_id),
                        'is_ride_confirm' => ($value->ride_status == 3),
                        'ride_status' => $value->ride_status,
                    );
                }
            }

            $return = [
                'upcoming_ride' => $upcoming_ride,
                'ongoing_ride' => $ongoing_ride,


            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.success"), $return);
    }

    public function offerRideDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ride_id' => ['required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile($user->merchant_id);
            $carpooling_ride = CarpoolingRide::find($request->ride_id);
            $user_rating = CarpoolingRideUserDetail::where([['user_id', '=', $carpooling_ride->user_id], ['carpooling_ride_id', '=', $carpooling_ride->id]])->whereIn('ride_status', array(2, 3, 4, 5, 6));
            //$booked_seats=CarpoolingRideUserDetail::where('carpooling_ride_id','=',$carpooling_ride->id)->first();
            // p($booked_seats);
            // seat configuration type share here, later we will use it
            $can_ride_start = true;
            $can_ride_start_text = "";
            $before_time_stamp = $carpooling_ride->ride_timestamp;
            $configuration = CarpoolingConfigCountry::where([['country_id', '=', $user->country_id], ['merchant_id', '=', $user->merchant_id]])->first();
            $carpooling_ride_user_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id], ['ride_status', '=', 4]]);
            //$completed_rides= $carpooling_ride_user_details->count();
            if (!empty($configuration)) {
                $user_ride_start_time = $configuration['user_ride_start_time'];
                if ($user_ride_start_time > 0) {
                    $before_time_stamp = $carpooling_ride->ride_timestamp - $user_ride_start_time * 60;
                }
            }
            if (time() < $before_time_stamp) {
                $can_ride_start = true;
                $can_ride_start_text = "You can not start the ride before " . date("Y-m-d H:i:s", $before_time_stamp);
            }

            $offer_user_data = array(
                'id' => $carpooling_ride->user_id,
                'name' => $carpooling_ride->User->first_name,
                'phone' => $carpooling_ride->User->UserPhone,
                'email' => $carpooling_ride->User->email,
                'image' => get_image($carpooling_ride->User->UserProfileImage, 'user', $user->merchant_id),
                'rating' => round($user_rating->avg('driver_rating'), 1),
                'ride_status' => $carpooling_ride->ride_status,
            );
            $offer_user_vehicle_details = array(
                'id' => $carpooling_ride->user_vehicle_id,
                'vehicle_name' => $carpooling_ride->UserVehicle->VehicleType->VehicleTypeName,
                'vehicle_image' => get_image($carpooling_ride->UserVehicle->vehicle_image, 'user_vehicle_document', $user->merchant_id),
                'vehicle_color' => $carpooling_ride->UserVehicle->vehicle_color,
                'vehicle_number' => $carpooling_ride->UserVehicle->vehicle_number,
            );
            $other_data = array(
                'carpooling_ride_id' => $carpooling_ride->id,
                'ride_timestamp' => $carpooling_ride->ride_timestamp,
                'can_ride_start' => $can_ride_start,
                'can_ride_start_text' => $can_ride_start_text,
                'ac_ride' => ($carpooling_ride->ac_ride == 1),
                'only_females' => $carpooling_ride->female_ride == 1 ? true : false,
                'booked_seats' => $carpooling_ride->booked_seats,
                'no_of_stops' => $carpooling_ride->no_of_stops,
                'total_seats' => $carpooling_ride->available_seats + $carpooling_ride->booked_seats,
                'available_seats' => $carpooling_ride->available_seats,
                'booked_seats' => $carpooling_ride->booked_seats,
                'return_ride' => ($carpooling_ride->return_ride == 1),
                'offer_user' => $offer_user_data,
                'offer_user_vehicle' => $offer_user_vehicle_details,
                'payment_type' => $carpooling_ride->payment_type == 1 ? trans("common.cash_online") : trans("common.online"),
                'instructions' => $carpooling_ride->additional_notes,
                //'amount'=>$carpooling_ride->User->Country->isoCode." ".$this->calculateSeatAmount($carpooling_ride->id,$carpooling_ride->booked_seats),

            );
            $ride_details = CarpoolingRideDetail::where([['is_return', '=', NULL], ['carpooling_ride_id', '=', $request->ride_id]])->orderBy('drop_no')->get();
            $cancelReasons = CancelReason::Reason($user->merchant_id, 2, $carpooling_ride->segment_id);
            $total_charges = 0;
            $ride_details_value = [];
            if (!empty($ride_details)) {
                $first_drop = $ride_details[0];
                array_push($ride_details_value, array(
                    'id' => $first_drop->id,
                    'drop_no' => 0,
                    'location' => $first_drop->from_location,
                    'ride_timestamp' => $first_drop->ride_timestamp,
                    'estimate_distance' => NULL,
                    'final_charges' => NULL,
                ));

                foreach ($ride_details as $value) {


                    $ride_details_value[] = array(
                        'id' => $value->id,
                        'drop_no' => $value->drop_no,
                        'location' => $value->to_location,
                        'ride_timestamp' => $value->end_timestamp,
                        'estimate_distance' => $value->estimate_distance,
                        'final_charges' => $carpooling_ride->User->Country->isoCode . ' ' . $value->final_charges,
                    );
                    $total_charges += $value->final_charges;
                    // not want all total_seatcharges
                    // $other_data['per_seat_charge'] = $carpooling_ride->User->Country->isoCode . ' ' . $this->seatCharges($request->ride_id, $value);
                    $other_data['per_seat_charge'] = $carpooling_ride->User->Country->isoCode . ' ' . $value->final_charges;
                    $other_data['cancel_amount'] = $carpooling_ride->User->Country->isoCode . " " . round($this->cancelOfferCondition($request->ride_id), 2);
                }


            }
            //  $other_data['cancel_amount'] = $this->cancelAmount($ride_details);
            $other_data['cancel_reason'] = $cancelReasons;
            $other_data['total_amount'] = $carpooling_ride->User->Country->isoCode . ' ' . $total_charges;
            $other_data['user_wallet_amount'] = $carpooling_ride->User->Country->isoCode . ' ' . $user->wallet_balance;
            $other_data['ride_details_list'] = $ride_details_value;

            $requested_user = [];
            $carpooling_ride_request = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id], ['ride_status', '=', 1]])->whereIn('payment_action', array(2, 3))->whereNull("cancel_reason_text")->get();
            foreach ($carpooling_ride_request as $value) {
                if ($carpooling_ride->available_seats >= $value->booked_seats) {
                    $requested_user[] = array(
                        'carpooling_ride_user_detail_id' => $value->id,
                        'request_user_id' => $value->user_id,
                        'request_user_name' => $value->User->first_name,
                        'request_user_image' => get_image($value->User->UserProfileImage, 'user', $user->merchant_id),
                        'request_user_rating' => round($user_rating->avg('user_rating'), 2),
                    );
                } else {
                    $value->ride_status = 7;
                    $value->save();
                    $data = ['ride_status' => 7, 'ride_id' => $value->id];
                    $message = trans("$string_file.auto_reject", ['passengername' => $value->User->first_name]);
                    $title = "Auto Reject";
                    $notification_type = "AUTO_REJECT";
                    $this->sendNotificationToUser($data, $message, $title, $notification_type, $value->user_id, $value->merchant_id);
                }
            }
            $accept_user = [];

            $carpooling_request_accept = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id]])->whereIn('ride_status', array(2, 3, 5, 6))->whereNull("cancel_reason_text")->get();
            if (!empty($carpooling_request_accept)) {
                foreach ($carpooling_request_accept as $value) {
                    $accept_user[] = array(
                        'carpooling_ride_user_detail_id' => $value->id,
                        'unique_id' => $value->carpooling_ride_id . "-" . $value->id,
                        'accept_user_id' => $value->user_id,
                        'accept_user_name' => $value->User->first_name,
                        'accept_user_image' => get_image($value->User->UserProfileImage, 'user', $user->merchant_id),
                        'accept_user_rating' => $value->User->passenger_rating,
                        'ride_status' => $value->ride_status,
                        'cancel_reason' => $value->cancel_reason_id ? $value->CancelReason->ReasonName : $value->cancel_reason_text,
                    );
                }
            }
            $other_data['wallet_balance'] = $carpooling_ride->User->Country->isoCode . ' ' . $user->wallet_balance;
            $other_data['request_users'] = $requested_user;
            $other_data['accept_users'] = $accept_user;
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.success"), $other_data);
    }

    public function takenRideDetails(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'ride_id' => ['required', 'integer', Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $taken_user_details = CarpoolingRideUserDetail::find($request->ride_id);
            $cancelReasons = CancelReason::Reason($user->merchant_id, 1, $taken_user_details->CarpoolingRide->segment_id);
            $user_rating = CarpoolingRideUserDetail::where([['user_id', '=', $user->id], ['ride_status', '=', 4]]);
            $ride_details = CarpoolingRideDetail::find($taken_user_details->carpooling_ride_detail_id);
            $taken_vehicle_details = [];
            $taken_ride_data = [];
            $offer_user_detail = [];
            if (!empty($taken_user_details)) {
                $taken_ride_data = array(
                    'id' => $taken_user_details->id,
                    'ride_id' => $taken_user_details->carpooling_ride_id,
                    'pickup_location' => $taken_user_details->pickup_location,
                    'drop_location' => $taken_user_details->drop_location,
                    'ride_time' => $taken_user_details->ride_timestamp,
                    'end_ride_time' => round($taken_user_details->end_timestamp),
                    'promo_code' => $taken_user_details->promo_code,
                    // 'ride_amount' => $taken_user_details->CarpoolingRide->User->Country->isoCode . " " . $this->calculateSeatAmount($taken_user_details, $taken_user_details->booked_seats),
                    'ride_amount' => $taken_user_details->CarpoolingRide->User->Country->isoCode . " " . round_number($taken_user_details->ride_amount, 1),
                    'otp' => $taken_user_details->ride_booking_otp,
                    'payment_status' => $taken_user_details->payment_action,
                    'ac_ride' => $taken_user_details->ac_ride == true ? "1" : "2",
                    'booked_seat' => $taken_user_details->booked_seats,
                    'available_seats' => $taken_user_details->CarpoolingRide->available_seats,
                    'female_ride' => $taken_user_details->female_ride == 1 ? true : false,
                    'is_ride_confirm' => $taken_user_details->CarpoolingRide->ride_status == 2 ? true : false,
                    'instructions' => $taken_user_details->CarpoolingRide->additional_notes,
                    'cancel_amount' => $taken_user_details->User->Country->isoCode . " " . round($this->canceltakenCondition($request->ride_id), 2),

                );
                $taken_vehicle_details = array(
                    'id' => $taken_user_details->CarpoolingRide->user_vehicle_id,
                    'vehicle_type_name' => $taken_user_details->CarpoolingRide->UserVehicle->vehicleType->vehicleTypeName,
                    'vehicle_color' => $taken_user_details->CarpoolingRide->UserVehicle->vehicle_color,
                    'vehicle_number' => $taken_user_details->CarpoolingRide->UserVehicle->vehicle_number,
                    'vehicle_image' => get_image($taken_user_details->CarpoolingRide->UserVehicle->vehicle_image, 'user_vehicle_document', $user->merchant_id),
                );

                $offer_user_detail = array(
                    'id' => $taken_user_details->CarpoolingRide->user_id,
                    'name' => $taken_user_details->CarpoolingRide->User->first_name,
                    'phone' => $taken_user_details->CarpoolingRide->User->UserPhone,
                    'email' => $taken_user_details->CarpoolingRide->User->email,
                    'image' => get_image($taken_user_details->CarpoolingRide->User->UserProfileImage, 'user', $user->merchant_id),
                    'ride_status' => $taken_user_details->ride_status,
                );
            }
            $accept_user = [];

            // Get other riders list, without current user
            $carpooling_request_accept = CarpoolingRideUserDetail::where([['user_id', '!=', $user->id], ['carpooling_ride_id', '=', $taken_user_details->CarpoolingRide->id], ['ride_status', '=', 2]])->get();
            if (!empty($carpooling_request_accept)) {
                foreach ($carpooling_request_accept as $value) {
                    $accept_user[] = array(
                        'accept_user_id' => $value->User->id,
                        'accept_user_name' => $value->User->first_name,
                        'accept_user_image' => get_image($value->User->UserProfileImage, 'user', $user->merchant_id),
                        'accept_user_rating' => $value->User->passenger_rating,
                        'ride_status' => $value->ride_status,
                        'cancel_reason' => $value->cancel_reason_id ? $value->CancelReason->ReasonName : $value->cancel_reason_text,
                    );
                }
            }
            $return_data[] = [
                'taken_ride_details' => $taken_ride_data,
                'take_vehicle_details' => $taken_vehicle_details,
                'offer_user_detail' => $offer_user_detail,
                'cancel_reason' => $cancelReasons,
                'accept_users' => $accept_user,

            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.success"), $return_data);
    }

    public function CancelOfferRide(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ride_id' => 'required', 'integer',
            Rule::exists('carpooling_rides', 'id')->where(function ($query) {
                $query->wherein('ride_status', [1, 2]);
            }),
            'cancel_reason_id' => 'integer',
            'other_reason' => 'max:100',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile($user->merchant_id);
            $carpooling_ride = CarpoolingRide::find($request->ride_id);
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
                // $carpooling_user_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id]])->get();
                // if(!empty( $carpooling_user_details)){
                // $paramArray = array(
                //     'user_id' => $carpooling_ride->user_id,
                //     'carpooling_ride_id' => $carpooling_ride->id,
                //     'amount' =>   $cancel_amount,
                //     'narration' => 5,
                // );
                // WalletTransaction::UserWalletDebit($paramArray);
                // }
                // $user_hold =UserHold::where([['user_id', '=',  $carpooling_ride->user_id],['carpooling_ride_id', '=',  $carpooling_ride->id],['is_user_offer_ride','=',1]])->first();
                //   // p($user_hold);
                //   if(!empty( $user_hold)){
                //     $user_hold->status = 1;// return
                //     $user_hold->save();
                //     $newarray = array(
                //         'user_id' => $carpooling_ride->user_id,
                //         'amount' => $user_hold->amount,
                //         'transaction_type' => 1,
                //         'carpooling_ride_id' =>$carpooling_ride->id,
                //     );
                //  WalletTransaction::UserWalletCredit($newarray);
                //   }
                if (empty($request->cancel_reason_id)) {
                    $carpooling_ride->cancel_reason_text = $request->other_reason;
                } else {
                    $carpooling_ride->cancel_reason_id = $request->cancel_reason_id;
                }
                $carpooling_ride->ride_status = 5; // offer user cancel
                $carpooling_ride->cancel_amount = $cancel_amount;
                $offer_ride_details->ride_status = 5;
                $carpooling_ride->cancel_reason_id = $request->cancel_reason_id;
                $return_param = array(
                    "id" => $carpooling_ride->id,
                    "timestamp" => time(),
                    "driver_name" => $carpooling_ride->User->first_name,
                    'slug' => 'CANCEL_DRIVER',
                );
                $logs_history = json_decode($carpooling_ride->carpooling_logs, true);
                array_push($logs_history, $return_param);
                $carpooling_ride->carpooling_logs = json_encode($logs_history);
                $carpooling_ride->save();
                $offer_ride_details->save();
                $carpooling_ride_user_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id]])->whereIn('ride_status', array(1, 2))->get();
                if (!empty($carpooling_ride_user_details)) {
                    foreach ($carpooling_ride_user_details as $value) {
                        $value->ride_status = 5; //ride is cancel by offer user
                        $value->cancel_reason_id = $request->cancel_reason_id;
                        $value->carpooling_logs = json_encode($logs_history);
                        $value->save();
                        if ($value->payment_action == 1) {
                            $refund_amount = $this->calculateBillAmount($value)->total_amount;
                            $newArray = array(
                                'user_id' => $value->user_id,
                                'amount' => $refund_amount,
                                'transaction_type' => 1,
                                'carpooling_ride_id' => $value->carpooling_ride_id,
                                'carpooling_ride_user_detail_id' => $value->id
                            );
                            WalletTransaction::UserWalletCredit($newArray);
                            $data = [];
                            $message = trans("$string_file.credit_refund_amount", ['user_name' => $value->User->first_name, 'amount' => $refund_amount]);
                            $title = trans("$string_file.amount_credited");
                            $notification_type = 'Wallet_Notification';
                            $this->sendNotificationToUser($data, $message, $title, $notification_type, $value->user_id, $value->merchant_id);
                        }
                        $paramArray = array(
                            'user_id' => $carpooling_ride->user_id,
                            'carpooling_ride_id' => $carpooling_ride->id,
                            'amount' => $cancel_amount,
                            'carpooling_ride_user_detail_id' => $value->id,
                            'narration' => 5,
                        );
                        WalletTransaction::UserWalletDebit($paramArray);
                        // $user_hold =UserHold::where([['user_id', '=',  $value->user_id],['carpooling_ride_id', '=',  $value->carpooling_ride_id],['is_user_offer_ride','=',0]])->get();
                        //   //  p($user_hold);
                        //   if(!empty($user_hold)){
                        //       foreach($user_hold as $val){
                        //     $user_hold->status = 1;// return
                        //     $user_hold->save();
                        //     $newarray = array(
                        //         'user_id' => $value->user_id,
                        //         'amount' => $val->amount,
                        //         'transaction_type' => 1,
                        //         'carpooling_ride_id' =>$value->carpooling_ride_id,
                        //     );
                        //  WalletTransaction::UserWalletCredit($newarray);
                        // }


                        $data = [];
                        $message = trans("$string_file.debit_cancel_amount", ['user_name' => $carpooling_ride->User->first_name, 'amount' => $cancel_amount]);
                        $title = trans("$string_file.amount_debited");
                        $notification_type = 'Wallet_Notification';
                        $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride->user_id, $carpooling_ride->merchant_id);

                        // send notification to all take ride users
                        $data = ['ride_status' => 5, 'ride_id' => $carpooling_ride->id, 'cancel_charge' => $cancel_amount];
                        $message = trans("$string_file.driver_cancel_ride", ['passengername' => $value->User->first_name, 'drivername' => $carpooling_ride->User->first_name, 'ID' => $carpooling_ride->id]);
                        $title = 'Cancel notification';
                        $notification_type = 'Driver_Cancel_Request';
                        $this->sendNotificationToUser($data, $message, $title, $notification_type, $value->user_id, $value->merchant_id);
                    }
                }
            } else {
                $message = trans("common.configuration_not_found");
                return $this->failedResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.success"), $user->Country->isoCode . ' ' . $cancel_amount);

    }

    public function cancelTakenRide(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'ride_id' => 'required', 'integer',
            Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
                $query->wherein('ride_status', [1, 2]);
            }),
            'cancel_reason_id' => 'integer',
            'other_reason' => 'max:100',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile($user->merchant_id);
            $carpooling_ride_user_details = CarpoolingRideUserDetail::find($request->ride_id);
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
                if ($carpooling_ride_user_details->payment_action == 1) {
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
                // p($carpooling_ride_user_details);
                if (empty($request->cancel_reason_id)) {
                    $carpooling_ride_user_details->cancel_reason_text = $request->other_reason;
                } else {
                    $carpooling_ride_user_details->cancel_reason_id = $request->cancel_reason_id;
                }
                $carpooling_ride_user_details->ride_status = 6;
                $carpooling_ride_user_details->cancel_amount = $total_amount;
                $carpooling_ride_user_details->cancel_refund_amount = $this->calculateBillAmount($carpooling_ride_user_details)->total_amount;
                $return_param = array(
                    "id" => $carpooling_ride_user_details->carpooling_ride_id,
                    "timestamp" => time(),
                    "driver_name" => $carpooling_ride_user_details->CarpoolingRide->User->first_name,
                    'slug' => 'CANCEL_PASSENGER',
                );
                //p(  $return_param );
                $logs_history = json_decode($carpooling_ride_user_details->carpooling_logs, true);
                //p(   $logs_history );
                $passenger_info = array("passenger_name" => $carpooling_ride_user_details->User->first_name);
                array_push($return_param, $passenger_info);
                array_push($logs_history, $return_param);
                $carpooling_ride_user_details->carpooling_logs = json_encode($logs_history);
                $carpooling_ride_user_details->save();

                $carpooling_ride = CarpoolingRide::find($carpooling_ride_user_details->carpooling_ride_id);
                $carpooling_ride->carpooling_logs = json_encode($logs_history);
                //seat calculation part
                $carpooling_ride->available_seats += $carpooling_ride_user_details->booked_seats;
                $carpooling_ride->booked_seats -= $carpooling_ride_user_details->booked_seats;
                //  $carpooling_ride->cancel_booked_seats += $carpooling_ride_user_details->booked_seats;
                $carpooling_ride->save();
                $carpooling_ride_details->booked_seats -= $carpooling_ride_user_details->booked_seats;
                $carpooling_ride_details->save();
                // now debit cancel amount
                $paramArray = array(
                    'user_id' => $carpooling_ride_user_details->user_id,
                    'amount' => $total_amount,
                    'narration' => 5,
                    'carpooling_ride_user_detail_id' => $carpooling_ride_user_details->id,
                    'carpooling_ride_id' => $carpooling_ride_user_details->carpooling_ride_id,
                );
                WalletTransaction::UserWalletDebit($paramArray);
                if (!empty($carpooling_ride_user_details)) {
                    if ($carpooling_ride_user_details->payment_action == 1) {
                        $refund_amount = $this->calculateBillAmount($carpooling_ride_user_details)->total_amount;
                        $newArray = array(
                            'user_id' => $carpooling_ride_user_details->user_id,
                            'amount' => $refund_amount,
                            'transaction_type' => 1,
                            'carpooling_ride_user_detail_id' => $carpooling_ride_user_details->id,
                            'carpooling_ride_id' => $carpooling_ride_user_details->carpooling_ride_id,
                        );
                        WalletTransaction::UserWalletCredit($newArray);
                        $data = [];
                        $message = trans("$string_file.credit_refund_amount", ['user_name' => $carpooling_ride_user_details->User->first_name, 'amount' => $refund_amount]);
                        $title = trans("$string_file.amount_credited");
                        $notification_type = 'Wallet_Notification';
                        $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride_user_details->user_id, $carpooling_ride_user_details->merchant_id);

                    }
                }
                //     $user_hold =UserHold::where([['user_id', '=', $carpooling_ride_user_details->user_id],['carpooling_ride_id', '=', $carpooling_ride_user_details->carpooling_ride_id],['is_user_offer_ride','=',0]])->get();

                //     if(!empty($user_hold)){
                //     foreach($user_hold as $value){
                //   $value->status = 1;// return
                //   $value->save();
                //     $holdarray = array(
                //         'user_id' => $value->user_id,
                //         'amount' => $value->amount,
                //         'transaction_type' => 1,
                //         'carpooling_ride_id' =>$value->carpooling_ride_id,
                //     );
                //     WalletTransaction::UserWalletCredit($holdarray);
                //   }}
                //     //user wallet credit case
                //     $newarray = array(
                //         'user_id' => $carpooling_ride_user_details->user_id,
                //         'amount' => $offer_user_cut,
                //         'transaction_type' => 1,
                //         'carpooling_ride_id' => $carpooling_ride_user_details->carpooling_ride_id,
                //     );
                //  WalletTransaction::UserWalletCredit($newarray);
                $data = [];
                $message = trans("$string_file.debit_cancel_amount", ['user_name' => $carpooling_ride_user_details->User->first_name, 'amount' => $total_amount]);
                $title = trans("$string_file.amount_debited");
                $notification_type = 'Wallet_Notification';
                $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride_user_details->user_id, $carpooling_ride_user_details->merchant_id);
                $data = ['ride_status' => 6, 'ride_id' => $carpooling_ride->id, 'cancel_charge' => $total_amount, "booking_id" => $carpooling_ride->id];
                $message = trans("$string_file.passenger_cancel_ride", ['drivername' => $carpooling_ride->User->first_name, 'passengername' => $carpooling_ride_user_details->User->first_name, 'ID' => $carpooling_ride->id]);
                $title = 'Cancel notification';
                $notification_type = 'User_Cancel_Request';
                $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride->user_id, $user->merchant_id);
            } else {
                $message = trans("common.configuration_not_found");
                return $this->failedResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.success"), $user->Country->isoCode . ' ' . $total_amount);
    }

    public function offerRideCancelReason(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => 'required|exists:segments,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant_id = $request->user('api')->merchant_id;
            $cancelReasons = CancelReason::Reason($merchant_id, 2, $request->segment_id);
            if (empty($cancelReasons->toArray())) {
                return $this->failedResponse(trans("common.data_not_found"));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans('common.success'), $cancelReasons);
    }

    // every user validate using otp match before ride

    public function takenRideCancelReason(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => 'required|exists:segments,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant_id = $request->user('api')->merchant_id;
            $cancelReasons = CancelReason::Reason($merchant_id, 3, $request->segment_id);
            if (empty($cancelReasons->toArray())) {
                return $this->failedResponse(trans("common.data_not_found"));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $cancelReasons);
    }

    // api for show users who onboard on every pickup points

    public function RideStart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_ride_id' => 'required|exists:carpooling_rides,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant_id = $request->user('api')->merchant_id;
            $string = $this->getStringFile($merchant_id);
            $carpooling_ride = CarpoolingRide::find($request->offer_ride_id);
            $carpooling_ride->ride_status = $request->ride_status;
            $carpooling_ride->save();
            $carpooling_ride_details = CarpoolingRideDetail::where('carpooling_ride_id', $carpooling_ride->id)->get();
            foreach ($carpooling_ride_details as $value) {
                $carpooling_user_details = CarpoolingRideUserDetail::where('carpooling_ride_detail_id', $value->id)->get();
                foreach ($carpooling_user_details as $v) {
                    $data[] = array(
                        'id' => $v->id,
                        'pick_up_id' => $v->CarpoolingRideDetail->id,
                        'drop_point_id' => $v ? $v->end_ride_id : 'NULL',
                        'user_name' => $v->User->first_name . " " . $v->User->last_name,
                        'pickup_location' => $v->pickup_location,
                        'drop_location' => $v->drop_location,
                        'payment_status' => $v->payment_time == 1 ? 'online_paymment' : 'cash',
                        'eta' => date('y-m-d , h:i:s', $v->CarpoolingRideDetail->ride_timestamp),
                        'final_charges' => $v->CarpoolingRideDetail->final_charges,
                    );
                    $v->ride_status = 3; // ride active
                    $v->save();
                    $param = array('ride_id' => $v->id, 'ride_status' => $v->ride_status, "booking_id" => $v->id);
                    // ride start send notification to all taken ride users
                    $message = trans("$string.ride") . " " . trans("common.start") . " " . trans("common.successfully");
                    $arr_param = ['user_id' => $v->user_id, 'data' => $param, 'message' => $message, 'merchant_id' => $v->merchant_id, 'title' => 'en'];
                    Onesignal::UserPushMessage($arr_param);

                }
            }
            $data = [
                'details' => $data,
            ];
            $message = trans("$string.ride") . " " . trans("common.start") . " " . trans("common.successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse($message, $data);
    }

    public function RideReach(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'ride_id' => 'required|exists:carpooling_ride_user_details,id',
            'status' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant_id = $request->user('api')->merchant_id;
            $string = $this->getStringFile($merchant_id);
            $carpooling_ride_details = CarpoolingRideUserDetail::find($request->ride_id);
            $carpooling_ride_details->ride_status = $request->status;
            $carpooling_ride_details->save();
            $param = ['ride_status' => $carpooling_ride_details->ride_status];
            $message = trans("$string.ride") . " " . trans("common.reach") . " " . trans("common.successfully");
            $arr_param = ['user_id' => $carpooling_ride_details->user_id, 'data' => $param, 'message' => $message, 'title' => "en", 'merchant_id' => $carpooling_ride_details->merchant_id];
            $carpooling_details = CarpoolingRideDetail::find($carpooling_ride_details->id);
            if (empty($carpooling_details)) {
                return $this->failedResponse(trans("common.data_not_found"));
            }
            $carpooling_details->ride_status = $carpooling_ride_details->ride_status;
            $carpooling_details->save();
            $data = [
                'id' => $carpooling_ride_details->id,
                'pickup_id' => $carpooling_ride_details->carpooling_ride_detail_id,
                'drop_id' => $carpooling_ride_details->end_ride_id,
            ];
            $message = trans("common.success");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse($message, $data);
    }

    public function nextStep(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'next_ride_id' => 'required',
            'status' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant_id = $request->user('api')->merchant_id;
            $string = $this->getStringFile($merchant_id);
            $carpooling_user_details = CarpoolingRideUserDetail::where('carpooling_ride_detail_id', $request->next_ride_id)->get();
            if (empty($carpooling_user_details)) {
                return $this->failedResponse(trans("common.data_not_found"));
            }
            foreach ($carpooling_user_details as $value) {
                $carpooling_ride_details = CarpoolingRideDetail::where('id', $value->carpooling_ride_detail_id)->get();
                foreach ($carpooling_ride_details as $v) {
                    $v->ride_status = 3;//ride is activate
                    $v->save();
                }
                $value->ride_status = $request->status;
                $value->save();
                $data = ['ride_status' => $request->status];
                $message = trans("$string.ride") . " " . trans("common.start") . " " . trans("common.successfully");
                $arr_param = ['user_id' => $value->user_id, 'data' => $data, 'message' => $message, 'title' => "en", 'merchant_id' => $value->merchant_id];
                Onesignal::UserPushMessage($arr_param);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.success"));
    }

    public function RideStartOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ride_id' => 'required', 'integer',
            Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
                $query->whereIn('ride_status', [1]);
            }),
            'otp' => 'required|max:4',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $merchant_id = $user->merchant_id;
            $string = $this->getStringFile($merchant_id);
            // check user details data exist or not
            $carpooling_user_details = CarpoolingRideUserDetail::find($request->user_ride_id);
            // now check otp
            if ($carpooling_user_details->ride_booking_otp != $request->otp) {
                $message = trans("common.otp") . " " . trans("common.is") . " " . trans("common.not") . " " . trans("common.match");
                return $this->failedResponse($message);
            }
            $data = ['ride_status' => $carpooling_user_details->ride_status];
            $message = trans("$string.ride") . " " . trans("common.start") . " " . trans("common.successfully");
            $arr_param = ['user_id' => $user->_id, 'data' => $data, 'message' => $message, 'title' => "en", 'merchant_id' => $merchant_id];
            Onesignal::UserPushMessage($arr_param);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        $message = trans("$string.ride") . " " . trans("common.start") . " " . trans("common.successfully");
        return $this->successResponse($message);
    }

    public function PickupUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_ride_id' => 'required', 'integer',
            Rule::exists('carpooling_rides', 'id')->where(function ($query) {
                $query->whereIn('ride_status', [1]);
            }),
            'ride_detail_id' => 'required', 'integer',
            Rule::exists('carpooling_ride_details', 'id')->where(function ($query) {
                $query->whereIn('ride_status', [1]);
            }),
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $merchant_id = $user->merchant_id;
            $string = $this->getStringFile($merchant_id);
            $carpooling_users = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $request->offer_ride_id], ['carpooling_ride_detail_id', '=', $request->ride_detail_id]])->whereIn('ride_status', [1, 2])->get();
            if (empty($carpooling_users)) {
                $message = trans("common.data_not_found");
                return $this->failedResponse($message);
            }
            foreach ($carpooling_users as $users) {
                $user_details[] = array(
                    'id' => $users->id,
                    'carpooling_ride_detail_id' => $users->carpooling_ride_detail_id,
                    'carpooling_ride_id' => $users->carpooling_ride_id,
                    'user_id' => $users->user_id,
                    'user_name' => $users->User->first_name . " " . $users->User->last_name,
                    'pickup' => $users->pickup_location,
                    'drop' => $users->drop_location,
                    'eta' => date('Y-m-d, h:i:s', $users->ride_timestamp),
                    'ride_amount' => $users->CarpoolingRideDetail->final_charges,
                    'booked_seats' => $users->booked_seats,
                );

            }
            $message = trans("common.list") . " " . trans("common.user") . " " . trans("common.of") . " " . trans("common.this") . " " . trans("common.point");
            $data = [
                'users_detail' => $user_details,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse($message, $data);

    }

    public function pastOfferRide(Request $request)
    {
        $user = $request->user('api');
        $user_id = $user->id;
        DB::beginTransaction();
        try {
            $past_ride_details = CarpoolingRide::select('id', 'user_vehicle_id', 'start_location', 'end_location', 'ride_timestamp', 'ride_status', 'return_ride', 'return_ride_timestamp', 'ac_ride', 'female_ride', 'available_seats', 'booked_seats', 'payment_type', 'booked_seats', 'no_of_stops')
                ->where([['user_id', '=', $user->id], ['merchant_id', '=', $user->merchant_id]])->whereIn('ride_status', [4, 5])->latest()->get();

            $newArray = $past_ride_details->toArray();
            if (!empty($newArray)) {
                foreach ($newArray as &$value) {
                    $carpooling_details = CarpoolingRideDetail::where('carpooling_ride_id', '=', $value['id'])->whereIn('ride_status', [4, 5]);
                    $value['ride_amount'] = $user->Country->isoCode . ' ' . $carpooling_details->sum('final_charges');
                    // $value['payment_method'] = $value['payment_type'] == 1 ? "Cash Also" : "Cash / Online Payment" ;
                    $value['payment_method'] = $value['payment_type'] == 1 ? trans("common.cash_online") : trans("common.online");
                    $value['total_seats'] = $value['available_seats'] + $value['booked_seats'];
                }


            }

            $data = [
                'past_ride_data' => $newArray,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $data);
    }

    public function pastTakenRide(Request $request)
    {
        $user = $request->user('api');
        DB::beginTransaction();
        try {
            $past_ride_details = CarpoolingRideUserDetail::where([['user_id', '=', $user->id], ['merchant_id', '=', $user->merchant_id], ['user_id', '=', $user->id]])->whereIn('ride_status', [4, 5, 6, 7])->limit(10)->latest()->get();
            $newArray = $past_ride_details->toArray();
            $past_ride = [];
            if (!empty($past_ride_details)) {
                foreach ($past_ride_details as $value) {
                    $past_ride[] = array(
                        'id' => $value->id,
                        'unique_id' => $value->carpooling_ride_id . "-" . $value->id,
                        'start_location' => $value->pickup_location,
                        'end_location' => $value->drop_location,
                        'ac_ride' => $value->ac_ride == true ? "1" : "0",
                        'ride_date' => $value->ride_timestamp,
                        'end_ride_date' => $value->end_timestamp,
                        'booked_taken_seats' => $value->booked_seats,
                        'offer_user_name' => $value->CarpoolingRide->User->first_name,
                        'offer_user_rating' => (string)round_number($value->CarpoolingRide->User->driver_rating),
                        'female_ride' => $value->female_ride == true ? " 1" : "0",
                        // 'final_charges' => $user->Country->isoCode . ' ' . $this->calculateBillAmount($value)->total_amount / $value->booked_seats,
                        'final_charges' => $user->Country->isoCode . ' ' . round_number($value->ride_amount / $value->booked_seats, 1),
                        'profile_image' => get_image($value->CarpoolingRide->User->UserProfileImage, 'user', $user->merchant_id),
                        'ride_status' => $value->ride_status,

                    );
                }
            }
            $data = [
                'past_take_ride' => $past_ride,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $data);
    }

    public function PastOfferRideSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to_date' => 'required',
            'from_date' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $query = $this->CommonSearch($user->id, $user->merchant_id, [4], $request->from_date, $request->to_date);
            $filter = $query->limit(10)->get();
            $data = [
                'search_data' => $filter,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $data);
    }

    public function CommonSearch($user_id, $merchant_id, $ride_status = [4], $date, $date1)
    {

        $query = CarpoolingRide::where([['user_id', '=', $user_id], ['merchant_id', '=', $merchant_id]])->whereIn('ride_status', $ride_status);
        if ($date) {
            $query->whereDate('created_at', '>=', $date);
        }
        if ($date1) {
            $query->whereDate('created_at', '<=', $date1);
        }
        return $query;
    }

    public function takeRideSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to_date' => 'required',
            'from_date' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $query = $this->SeachPastTakeRide($user->id, $user->merchant_id, [4], $request->from_date, $request->to_date);
            $filter = $query->limit(20)->get();
            $data = [
                'search_data' => $filter,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $data);
    }

    //view details

    public function SeachPastTakeRide($user_id, $merchant_id, $ride_status = [4], $date, $date1)
    {
        $query = CarpoolingRideUserDetail::where([['user_id', '=', $user_id], ['merchant_id', '=', $merchant_id]])->whereIn('ride_status', $ride_status);
        if ($date) {
            $query->whereDate('created_at', '>=', $date);
        }
        if ($date1) {
            $query->whereDate('created_at', '<=', $date1);
        }
        return $query;
    }

    public function pastOfferRideDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ride_id' => ['required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
                $query->whereIn('ride_status', array(4, 5));
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $carpooling_ride = CarpoolingRide::find($request->ride_id);
            $carpooling_ride_user_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id], ['ride_status', '=', 4]]);
            $user_rating = CarpoolingRideUserDetail::where([['user_id', '=', $user->id], ['ride_status', '=', 4]]);
            //$completed_rides= $carpooling_ride_user_details->count();

            $offer_user_data = array(
                'id' => $carpooling_ride->user_id,
                'name' => $carpooling_ride->User->first_name,
                'phone' => $carpooling_ride->User->UserPhone,
                'email' => $carpooling_ride->User->email,
                'image' => get_image($carpooling_ride->User->UserProfileImage, 'user', $user->merchant_id),
                'rating' => round($carpooling_ride_user_details->avg('driver_rating'), 1),
            );
            $offer_user_vehicle_details = array(
                'id' => $carpooling_ride->user_vehicle_id,
                'vehicle_name' => $carpooling_ride->UserVehicle->VehicleType->VehicleTypeName,
                'vehicle_image' => get_image($carpooling_ride->UserVehicle->vehicle_image, 'user_vehicle_document', $user->merchant_id),
                'vehicle_color' => $carpooling_ride->UserVehicle->vehicle_color,
                'vehicle_number' => $carpooling_ride->UserVehicle->vehicle_number,
            );
            $other_data = array(
                'carpooling_ride_id' => $carpooling_ride->id,
                'ride_timestamp' => $carpooling_ride->ride_timestamp,
                'ac_ride' => ($carpooling_ride->ac_ride == 1),
                'only_females' => $carpooling_ride->female_ride == 1 ? true : false,
                'booked_seats' => $carpooling_ride->booked_seats,
                'no_of_stops' => $carpooling_ride->no_of_stops,
                'total_seats' => $carpooling_ride->available_seats,
                'return_ride' => ($carpooling_ride->return_ride == 1),
                'offer_user' => $offer_user_data,
                'offer_user_vehicle' => $offer_user_vehicle_details,
                // 'payment_type' => $carpooling_ride->payment_type == 1 ? "Cash Also" : "Online Payment",
                'payment_type' => $carpooling_ride->payment_type == 1 ? trans("common.cash_online") : trans("common.online"),
                'instructions' => $carpooling_ride->additional_notes,
            );
            $ride_details = CarpoolingRideDetail::where([['is_return', '=', NULL], ['carpooling_ride_id', '=', $request->ride_id]])->orderBy('drop_no')->get();
            $cancelReasons = CancelReason::Reason($user->merchant_id, 2, $carpooling_ride->segment_id);
            $total_charges = 0;
            $ride_details_value = [];
            if (!empty($ride_details)) {
                $first_drop = $ride_details[0];
                array_push($ride_details_value, array(
                    'id' => $first_drop->id,
                    'drop_no' => 0,
                    'location' => $first_drop->from_location,
                    'ride_timestamp' => $first_drop->ride_timestamp,
                    'estimate_distance' => NULL,
                    'final_charges' => NULL,
                ));

                foreach ($ride_details as $value) {


                    $ride_details_value[] = array(
                        'id' => $value->id,
                        'drop_no' => $value->drop_no,
                        'location' => $value->to_location,
                        'ride_timestamp' => $value->end_timestamp,
                        'estimate_distance' => $value->estimate_distance,
                        'final_charges' => $user->Country->isoCode . ' ' . $value->final_charges,
                    );
                    $total_charges += $value->final_charges;

                }

            }
            $accept_user = [];
            // $cancel_user=[];
            $carpooling_request_accept = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id]])->whereIn('ride_status', array(4, 5, 6))->get();
            if (!empty($carpooling_request_accept)) {
                foreach ($carpooling_request_accept as $value) {
                    $accept_user[] = array(
                        'carpooling_ride_user_detail_id' => $value->id,
                        'accept_user_id' => $value->user_id,
                        'unique_id' => $value->carpooling_ride_id . "-" . $value->id,
                        'accept_user_name' => $value->User->first_name,
                        'accept_user_image' => get_image($value->User->UserProfileImage, 'user', $user->merchant_id),
                        'accept_user_rating' => $value->User->passenger_rating,
                        'ride_status' => $value->ride_status,
                        'cancel_reason' => $value->cancel_reason_id ? $value->CancelReason->ReasonName : $value->cancel_reason_text,
                    );
                }
            }
            // $carpooling_cancel_user = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id]])->whereIn('ride_status',array(5,6))->get();
            // if (!empty($carpooling_cancel_user )) {
            //     foreach ($carpooling_cancel_user as $value) {
            //         $cancel_user[] = array(
            //             'carpooling_ride_user_detail_id' => $value->id,
            //             'cancel_user_id' => $value->user_id,
            //             'unique_id'=>$value->carpooling_ride_id."-".$value->id,
            //             'accept_user_name' => $value->User->first_name ,
            //             'accept_user_image' => get_image($value->User->UserProfileImage, 'user', $user->merchant_id),
            //             'accept_user_rating' => round($user_rating->avg('user_rating'),2),
            //         );
            //     }
            // }

            //$other_data['cancel_users']=$cancel_user;
            $other_data['accept_users'] = $accept_user;
            $other_data['total_amount'] = $user->Country->isoCode . ' ' . round_number($total_charges, 1);
            $other_data['ride_details_list'] = $ride_details_value;

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.success"), $other_data);
    }


    // send notification to user

    public function pastTakenRideDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'carpooling_ride_user_detail_id' => ['required', 'integer', Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
                $query->whereIn('ride_status', array(4, 5, 6, 7));
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $taken_user_details = CarpoolingRideUserDetail::find($request->carpooling_ride_user_detail_id);
            //p(  $taken_user_details);
            $cancelReasons = CancelReason::Reason($user->merchant_id, 1, $taken_user_details->CarpoolingRide->segment_id);
            $user_rating = CarpoolingRideUserDetail::where([['user_id', '=', $user->id], ['ride_status', '=', 4]]);
            //$this->SetTimeZone($taken_user_details->CarpoolingRide->country_area_id);
            $taken_vehicle_details = [];
            $taken_ride_data = [];
            $offer_user_detail = [];
            if (!empty($taken_user_details)) {
                $taken_ride_data = array(
                    'id' => $taken_user_details->id,
                    'ride_id' => $taken_user_details->carpooling_ride_id,
                    'pickup_location' => $taken_user_details->pickup_location,
                    'drop_location' => $taken_user_details->drop_location,
                    'ride_time' => $taken_user_details->ride_timestamp,
                    'end_ride_time' => round($taken_user_details->end_timestamp),
                    'promo_code' => $taken_user_details->promo_code,
                    'ride_amount' => $taken_user_details->CarpoolingRide->User->Country->isoCode . " " . $this->calculateBillAmount($taken_user_details)->total_amount,
                    'payment_status' => $taken_user_details->payment_action,
                    'ac_ride' => $taken_user_details->CarpoolingRide->ac_ride == true ? "1" : "2",
                    'booked_seat' => $taken_user_details->booked_seats,
                    'female_ride' => $taken_user_details->CarpoolingRide->female_ride == 1 ? true : false,
                    'instructions' => $taken_user_details->CarpoolingRide->additional_notes,
                    'ride_status' => $taken_user_details->ride_status,
                    'is_user_rated' => $taken_user_details->driver_rating == 0 ? false : true,
                    'cancel_reason' => $taken_user_details->cancel_reason_id ? $taken_user_details->CancelReason->ReasonName : $taken_user_details->cancel_reason_text,

                );

                $taken_vehicle_details = array(
                    'id' => $taken_user_details->CarpoolingRide->user_vehicle_id,
                    'vehicle_type_name' => $taken_user_details->CarpoolingRide->UserVehicle->vehicleType->vehicleTypeName,
                    'vehicle_color' => $taken_user_details->CarpoolingRide->UserVehicle->vehicle_color,
                    'vehicle_number' => $taken_user_details->CarpoolingRide->UserVehicle->vehicle_number,
                    'vehicle_image' => get_image($taken_user_details->CarpoolingRide->UserVehicle->vehicle_image, 'user_vehicle_document', $user->merchant_id),
                );

                $offer_user_detail = array(
                    'id' => $taken_user_details->CarpoolingRide->user_id,
                    'name' => $taken_user_details->CarpoolingRide->User->first_name,
                    'phone' => $taken_user_details->CarpoolingRide->User->UserPhone,
                    'email' => $taken_user_details->CarpoolingRide->User->email,
                    'image' => get_image($taken_user_details->CarpoolingRide->User->UserProfileImage, 'user', $user->merchant_id),
                );
            }
            $accept_user = [];

            // Get other riders list, without current user
            // $carpooling_request_accept = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $taken_user_details->CarpoolingRide->id],['ride_status','=',4]])->get();
            // if (!empty($carpooling_request_accept)) {
            //     foreach ($carpooling_request_accept as $value) {
            //         $accept_user[] = array(
            //             'accept_user_id' => $value->User->id,
            //             'unique_id' => $value->carpooling_ride_id . "-" . $value->id,
            //             'accept_user_name' => $value->User->first_name,
            //             'accept_user_image' => get_image($value->User->UserProfileImage, 'user', $user->merchant_id),
            //             'accept_user_rating' => round($user_rating->avg('user_rating'), 2),
            //             'ride_status' => $value->ride_status,
            //             'cancel_reason' => $value->cancel_reason_id ? $value->CancelReason->ReasonName : $value->cancel_reason_text,
            //         );
            //     }
            // }
            $return_data[] = [
                'taken_ride_details' => $taken_ride_data,
                'take_vehicle_details' => $taken_vehicle_details,
                'offer_user_detail' => $offer_user_detail,
                'cancel_reason' => $cancelReasons,
                'accept_users' => $accept_user,

            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.success"), $return_data);
    }

    public function mainScreen(Request $request)
    {
        $data = new CarpoolingResource($request->user('api'));
        return $this->successResponse(trans('carpooling.carpooling') . ' ' . trans('common.details'), $data);
    }

    public function viewUserDetail(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(function ($query) {
            }),],
            'type' => [
                'required',
                Rule::in(['user', 'driver']),
            ],
            'ride_id' => ['required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
            }),],
            'carpooling_ride_user_detail_id' => ['integer', Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {

            }),],

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = User::find($request->user_id);
            $user_rating = CarpoolingRideUserDetail::where([['user_id', '=', $user->id], ['ride_status', '=', 4]]);
            $carpooling_ride = CarpoolingRide::find($request->ride_id);
            // p($carpooling_ride);
            $carpooling_ride_user_details = CarpoolingRideUserDetail::find($request->carpooling_ride_user_detail_id);
            //p(  $carpooling_ride_user_details);
            $carpooling_ride_details = CarpoolingRideDetail::where('carpooling_ride_id', '=', $carpooling_ride->id)->first();
            $data = [];
            $this->SetTimeZone($carpooling_ride->country_area_id);
            $current_timestamp = strtotime('now');
            $ride_timestamp = $carpooling_ride_details->ride_timestamp;
            $before_time_stamp_min = $ride_timestamp - 1800;

            $before_time_stamp_hour = $ride_timestamp - 86400;
            $time_diff = $current_timestamp - $ride_timestamp;
            $min = round(abs($time_diff) / 60);
            $hour = round(abs($time_diff) / 3600);
            // ride with in city case
            $data['id'] = $user->id;
            $data['name'] = $user->first_name;
            $data['profileimage'] = get_image($user->UserProfileImage, 'user', $user->merchant_id);
            $data['document_verification_status'] = 0;
            $data['member_since'] = $user->created_at->timestamp;
            $total_user_doc = UserDocument::where([['user_id', '=', $user->id]])->count();
            $total_user_verify_doc = UserDocument::where([['document_verification_status', '=', 2], ['user_id', '=', $user->id]])->count();
            if ($total_user_doc == $total_user_verify_doc) {
                $data['document_verification_status'] = 1;
            }
            if ($request->type == 'user') {
                $data['rating'] = $user->passenger_rating;
                $data['total_rides'] = CarpoolingRideUserDetail::where([['user_id', '=', $user->id], ['ride_status', '=', 4]])->count();
                $data['ride_details'] = [];
                $ride_details = CarpoolingRideUserDetail::where([['user_id', '=', $carpooling_ride_user_details->user_id], ['id', '=', $carpooling_ride_user_details->id]])->first();
                if (!empty($ride_details)) {
                    $data['ride_details']['id'] = $ride_details->id;
                    $data['ride_details']['start_timestamp'] = $ride_details->ride_timestamp;
                    $data['ride_details']['end_timestamp'] = $ride_details->end_timestamp;
                    $data['ride_details']['no_of_seats'] = $ride_details->booked_seats;
                    // $return_data = $this->calculateBillAmount($ride_details);
                    $data['ride_details']['total_amount'] = $user->Country->isoCode . " " . round_number($ride_details->ride_amount, 1);
                    $data['ride_details']['start_location'] = $ride_details->pickup_location;
                    $data['ride_details']['drop_location'] = $ride_details->drop_location;
                    $data['ride_details']['payment_type'] = $ride_details->payment_action;
                    if ($carpooling_ride_details->estimate_distance <= 50) {
                        if ($min >= 30) {
                            $data['ride_details']['phone'] = "";
                            $data['ride_details']['phone_number_visiblity'] = "You can not see the contact details before " . date("Y-m-d H:i:s", $before_time_stamp_min);
                        } else {
                            if ($carpooling_ride_user_details->ride_status == 1) {
                                $data['ride_details']['phone'] = "";
                                $data['ride_details']['phone_number_visiblity'] = "Ride Is Not Yet Accepted, You can not see the contact details";
                            } elseif ($carpooling_ride_user_details->ride_status == 2 || $carpooling_ride_user_details->ride_status == 3) {
                                $data['ride_details']['phone'] = $user->UserPhone;
                            } elseif ($carpooling_ride_user_details->ride_status == 4) {
                                $data['ride_details']['phone'] = "";
                                $data['ride_details']['phone_number_visiblity'] = "Ride Is Completed";

                            } else {
                                $data['ride_details']['phone'] = "";
                                $data['ride_details']['phone'] = " Ride Is Cancelled";
                            }
                        }
                    } else {
                        if ($hour >= 24) {
                            $data['ride_details']['phone'] = "";
                            $data['ride_details']['phone_number_visiblity'] = "You can not see the contact details before " . date("Y-m-d H:i:s", $before_time_stamp_hour);
                        } else {
                            if ($carpooling_ride_user_details->ride_status == 1) {
                                $data['ride_details']['phone'] = "";
                                $data['ride_details']['phone_number_visiblity'] = " Ride Is Not Yet Accepted, You can not see the contact details";
                            } elseif ($carpooling_ride_user_details->ride_status == 2 || $carpooling_ride_user_details->ride_status == 3) {
                                $data['ride_details']['phone'] = $user->UserPhone;
                            } elseif ($carpooling_ride_user_details->ride_status == 4) {
                                $data['ride_details']['phone'] = "";
                                $data['ride_details']['phone_number_visiblity'] = " Ride Is Completed";

                            } else {
                                $data['ride_details']['phone'] = "";
                                $data['ride_details']['phone'] = "Ride Is Cancelled";
                            }
                        }
                    }
                }
            } elseif ($request->type == 'driver') {
                $data['rating'] = $user->driver_rating;
                $data['total_rides'] = CarpoolingRide::where([['user_id', '=', $user->id], ['ride_status', '=', 4]])->count();
                if ($carpooling_ride_details->estimate_distance <= 50) {
                    if ($min >= 30) {
                        $data['phone'] = "";
                        $data['phone_number_visiblity'] = "You can not see the contact details before " . date("Y-m-d H:i:s", $before_time_stamp_min);
                    } else {
                        if (empty($carpooling_ride_user_details)) {
                            $data['phone'] = "";
                            $data['phone_number_visiblity'] = "Ride Is Not Yet Requested, You can not see the contact details";
                        } elseif ($carpooling_ride_user_details->ride_status == 1) {
                            $data['phone'] = "";
                            $data['phone_number_visiblity'] = "Ride Is Not Yet Accepted, You can not see the contact details";
                        } elseif ($carpooling_ride_user_details->ride_status == 2 || $carpooling_ride_user_details->ride_status == 3) {
                            $data['phone'] = $user->UserPhone;
                        } elseif ($carpooling_ride_user_details->ride_status == 4) {
                            $data['phone'] = "";
                            $data['phone_number_visiblity'] = "Ride Is Completed";

                        } else {
                            $data['phone'] = "";
                            $data['phone'] = " Ride Is Cancelled";
                        }
                    }
                } else {
                    if ($hour >= 24) {
                        $data['phone'] = "";
                        $data['phone_number_visiblity'] = "You can not see the contact details before " . date("Y-m-d H:i:s", $before_time_stamp_hour);
                    } else {
                        if (empty($carpooling_ride_user_details)) {
                            $data['phone'] = "";
                            $data['phone_number_visiblity'] = " Ride Is Not Yet Requested, You can not see the contact details";
                        } elseif ($carpooling_ride_user_details->ride_status == 1) {
                            $data['phone'] = "";
                            $data['phone_number_visiblity'] = "Ride Is Not Yet Accepted, You can not see the contact details";
                        } elseif ($carpooling_ride_user_details->ride_status == 2 || $carpooling_ride_user_details->ride_status == 3) {
                            $data['phone'] = $user->UserPhone;
                        } elseif ($carpooling_ride_user_details->ride_status == 4) {
                            $data['phone'] = "";
                            $data['phone_number_visiblity'] = " Ride Is Completed";

                        } else {
                            $data['phone'] = "";
                            $data['phone'] = "Ride Is Cancelled";
                        }
                    }
                }
            } else {
                return $this->failedResponse('Invalid Type');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $data);
    }

    public function takenRideRequest(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'carpooling_ride_user_detail_id' => 'required', 'integer', Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
            }),
            'action' => 'required', Rule::in(['accept', 'reject']),
            'ride_id' => 'required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
            }),
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $carpooling_ride = CarpoolingRide::find($request->ride_id);
            $string_file = $this->getStringFile($carpooling_ride->merchant_id);
            $carpooling_ride_user_detail = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id], ['payment_status', '=', 0]])->find($request->carpooling_ride_user_detail_id);
            $carpooling_ride_details = CarpoolingRideDetail::find($carpooling_ride_user_detail->carpooling_ride_detail_id);
            if (!empty($carpooling_ride_user_detail)) {
                if ($request->action == 'accept') {
                    if ($carpooling_ride->available_seats >= $carpooling_ride_user_detail->booked_seats) {
                        $carpooling_ride_user_detail->ride_status = 2;
                        $carpooling_ride_user_detail->save();
                        // $this->carpoolingRideUserLog($carpooling_ride_user_detail,$slug="RIDE_ACCEPTED_BY_DRIVER");
                        $this->carpoolingRideLog($carpooling_ride, $slug = "RIDE_ACCEPTED", $carpooling_ride_user_detail);
                        $seats = $carpooling_ride->available_seats - $carpooling_ride_user_detail->booked_seats;
                        $carpooling_ride->available_seats = $seats;
                        $carpooling_ride->booked_seats = $carpooling_ride->booked_seats + $carpooling_ride_user_detail->booked_seats;
                        $carpooling_ride->save();
                        $carpooling_ride_details->booked_seats = $carpooling_ride->booked_seats + $carpooling_ride_user_detail->booked_seats;
                        $carpooling_ride_details->save();
                        $message = trans("$string_file.your_ride_confirmed_by_driver");
                        $data = ['ride_status' => 2, 'ride_id' => $carpooling_ride->id];
                        $title = 'Request Notification';
                        $notification_type = 'Ride_Accept_Request';
                        $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride_user_detail->user_id, $carpooling_ride_user_detail->merchant_id);
                    } else {
                        return $this->failedResponse("Number of seats you requested is not available");
                    }
                } elseif ($request->action == 'reject') {
                    $carpooling_ride_user_detail->ride_status = 8;
                    $carpooling_ride_user_detail->reject_refund_amount = $this->calculateBillAmount($carpooling_ride_user_detail)->total_amount;
                    $carpooling_ride_user_detail->save();
                    //$this->carpoolingRideUserLog($carpooling_ride_user_detail,$slug="RIDE_REJECTED_BY_DRIVER");
                    $this->carpoolingRideLog($carpooling_ride, $slug = "RIDE_REJECTED", $carpooling_ride_user_detail);
                    $message = trans("$string_file.your_ride_request_rejected_by_driver");
                    $data = ['ride_status' => 8, 'ride_id' => $carpooling_ride->id];
                    $title = 'Request Notification';
                    $notification_type = 'Ride_Reject_Request';
                    $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride_user_detail->user_id, $carpooling_ride_user_detail->merchant_id);
                } else {
                    return $this->failedResponse('Invalid Type');
                }
                // $carpooling_ride_user_detail->save();

                $this->sendNotificationToUser(['ride_id' => $carpooling_ride->id], $message, 'Ride Action', 'RIDE_REQUEST', $carpooling_ride_user_detail->user_id, $carpooling_ride->merchant_id);
            } else {
                return $this->failedResponse('User ride detail not found.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'));
    }

    // public function startOfferRide(Request $request)
    // {
    //     $user = $request->user('api');
    //     $validator = Validator::make($request->all(), [
    //         'ride_id' => 'required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
    //         }),
    //         'latitude' => 'required',
    //         'longitude' => 'required',
    //     ]);
    //     if ($validator->fails()) {
    //         $errors = $validator->messages()->all();
    //         return $this->failedResponse($errors[0]);
    //     }
    //     DB::beginTransaction();
    //     try {
    //         $carpooling_detail = CarpoolingRide::find($request->ride_id);
    //         $ride_details = CarpoolingRideDetail::where([['carpooling_ride_id', '=', $carpooling_detail->id]])->whereIn('ride_status', array(1, 2))->orderBy("drop_no");
    //         //p($ride_details );
    //         if (!empty($ride_details)) {
    //             $carpooling_detail->update(['ride_status' => 3]);
    //             $ride_details->update(['ride_status' => 3]);
    //         }
    //         foreach ($ride_details as $value) {
    //             $user_ride_details = CarpoolingRideUserDetail::where([['carpooling_ride_detail_id', '=', $value->id]])->whereIn('ride_status', array(1, 2));
    //             if (!empty($user_ride_details)) {
    //                 $user_ride_details->update(['ride_status' => 3]);
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return $this->failedResponse($e->getMessage());
    //     }
    //     DB::commit();
    //     return $this->successResponse(trans('common.success'));
    // }

    public function offerRideList(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'ride_detail_id' => 'required', 'integer', Rule::exists('carpooling_ride_details', 'id')->where(function ($query) {
            }),
            'ride_id' => 'required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
            }),
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $carpooling_detail = CarpoolingRide::find($request->ride_id);
            $user_details = CarpoolingRideUserDetail::where([['user_id', '=', $user->id], ['carpooling_ride_id', '=', $carpooling_detail->id]])->get();
            $ongoing_trip_details = [];
            if (!empty($user_details)) {
                foreach ($user_details as $value) {
                    $ongoing_trip_details[] = array(
                        'id' => $value->id,
                        'name' => $value->User->first_name . " " . $value->User->last_name,
                        'start_location' => $value->pickup_location,
                        'end_location' => $value->drop_location,
                        'ride_time' => $value->ride_timestamp,
                        'payment_type' => $value->CarpoolingRide->payment_type,
                        'final_amount' => $user->Country->isoCode . " " . $value->CarpoolingRideDetail->final_charges,
                        'profile_image' => get_image($value->CarpoolingRide->User->UserProfileImage, 'user', $user->merchant_id),
                        'UserPhone' => $value->User->UserPhone,
                    );
                }
            }
            $ride_details = CarpoolingRideDetail::where('carpooling_ride_id', '=', $request->ride_detail_id)->get();
            $ride_detail_list = [];
            if (!empty($ride_details)) {
                foreach ($ride_details as $value) {
                    $ride_detail_list[] = array(
                        'id' => $value->id,
                        'from_location' => $value->from_location,
                        'to_location' => $value->to_location,
                        'ride_time' => $value->ride_timestamp,
                        'female_ride' => $value->CarpoolingRide->female_ride,
                        'ac_ride' => $value->CarpoolingRide->ac_ride
                    );
                }
            }

            $return_data = [
                'ride_details' => $ride_detail_list,
                'user_data' => $ongoing_trip_details,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $return_data);
    }


    // public function pickUpUserRide(Request $request)
    // {
    //     $user = $request->user('api');
    //     $validator = Validator::make($request->all(), [
    //         'ride_id' => 'required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
    //         }),
    //         'latitude' => 'required',
    //         'longitude' => 'required',
    //     ]);
    //     if ($validator->fails()) {
    //         $errors = $validator->messages()->all();
    //         return $this->failedResponse($errors[0]);
    //     }
    //     DB::beginTransaction();
    //     try {
    //         $carpooling_detail = CarpoolingRide::find($request->ride_id);
    //         $user_ride_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_detail->id]])->get();


    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return $this->failedResponse($e->getMessage());
    //     }
    //     DB::commit();
    //     return $this->successResponse(trans('common.success'), $return_data);
    // }

    public function offerRidePickup(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile($request->merchant_id);
        $validator = Validator::make($request->all(), [
            'user_ride_id' => 'required', 'integer',
            Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
            }),
            'otp' => 'required|max:4',

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant_id = $user->merchant_id;
            $string = $this->getStringFile($merchant_id);
            $ongoing_trip_details = [];
            $carpooling_user_details = CarpoolingRideUserDetail::find($request->user_ride_id);
            if (!empty($carpooling_user_details)) {
                if ($carpooling_user_details->ride_booking_otp != $request->otp) {
                    $message = trans("common.otp") . " " . trans("common.is") . " " . trans("common.not") . " " . trans("common.match");
                    return $this->failedResponse($message);
                } elseif ($carpooling_user_details->ride_booking_otp == $request->otp) {
                    $ongoing_trip_details = array(
                        'id' => $carpooling_user_details->id,
                        'name' => $carpooling_user_details->User->first_name . " " . $carpooling_user_details->User->last_name,
                        'start_location' => $carpooling_user_details->pickup_location,
                        'end_location' => $carpooling_user_details->drop_location,
                        'ride_time' => $carpooling_user_details->ride_timestamp,
                        'payment_type' => $carpooling_user_details->CarpoolingRide->payment_type,
                        'final_amount' => $carpooling_user_details->User->Country->isoCode . " " . $carpooling_user_details->CarpoolingRideDetail->final_charges,
                        'profile_image' => get_image($carpooling_user_details->CarpoolingRide->User->UserProfileImage, 'user', $user->merchant_id),
                        'UserPhone' => $carpooling_user_details->User->UserPhone,
                    );
                    $carpooling_user_details->ride_status = 3;
                    $return_param = array(
                        "id" => $carpooling_user_details->carpooling_ride_id,
                        "timestamp" => time(),
                        "driver_name" => $carpooling_user_details->CarpoolingRide->User->first_name,
                        'slug' => 'PASSENGER_RIDE_START',
                    );
                    $logs_history = json_decode($carpooling_user_details->carpooling_logs, true);
                    $passenger_info = array("passenger_name" => $carpooling_user_details->User->first_name);
                    array_push($return_param, $passenger_info);
                    array_push($logs_history, $return_param);
                    $carpooling_user_details->carpooling_logs = json_encode($logs_history);
                    $carpooling_user_details->save();


                    //  if($carpooling_user_details->payment_action==1||$carpooling_user_details->payment_action==3){
                    //             $newArray = array(
                    //             'user_id' =>  $carpooling_user_details->CarpoolingRide->user_id,
                    //             'amount' => $this->calculateBillAmount($carpooling_user_details)->total_amount,
                    //             'transaction_type' => 1,
                    //             'carpooling_ride_id' => $carpooling_user_details->CarpoolingRide->id,
                    //             );
                    //           WalletTransaction::UserWalletCredit($newArray);
                    //             }
                    if ($carpooling_user_details->payment_action == 3) {
                        $amount = $this->calculateBillAmount($carpooling_user_details)->total_amount;
                        $paramArray = array(
                            'user_id' => $carpooling_user_details->user_id,
                            'amount' => $amount,
                            'transaction_type' => 2,
                            'carpooling_ride_user_detail_id' => $carpooling_user_details->id,
                            'carpooling_ride_id' => $carpooling_user_details->carpooling_ride_id,
                        );
                        WalletTransaction::UserWalletDebit($paramArray);
                        $data = [];
                        $message = trans("$string_file.debit_transaction_amount", ['user_name' => $carpooling_user_details->User->first_name, 'amount' => $amount]);
                        $title = trans("$string_file.amount_debited");
                        $notification_type = 'Wallet_Notification';
                        $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_user_details->user_id, $carpooling_user_details->merchant_id);
                    }
                    //  if( $carpooling_user_details->payment_action==2){
                    //      $paramArray = array(
                    //         'user_id' =>  $carpooling_user_details->user_id,
                    //         'amount' => $this->calculateBillAmount($carpooling_user_details)->commission+$this->calculateBillAmount($carpooling_user_details)->service_charges,
                    //         'transaction_type' => 2,
                    //         'carpooling_ride_id' => $carpooling_user_details->carpooling_ride_id,
                    //         );
                    //       WalletTransaction::UserWalletDebit($paramArray);
                    // }


                } else {
                    return $this->failedResponse($e->getMessage());
                }
            }
            $ride_details = CarpoolingRideDetail::where('carpooling_ride_id', '=', $carpooling_user_details->carpooling_ride_id)->get();
            $ride_detail_list = [];
            if (!empty($ride_details)) {
                foreach ($ride_details as $value) {
                    $ride_detail_list[] = array(
                        'id' => $value->id,
                        'from_location' => $value->from_location,
                        'to_location' => $value->to_location,
                        'ride_time' => $value->ride_timestamp,
                        'female_ride' => $value->CarpoolingRide->female_ride,
                        'ac_ride' => $value->CarpoolingRide->ac_ride
                    );
                    if ($carpooling_user_details->payment_action == 2 || $carpooling_user_details->payment_action == 3) {
                        $booking_fee = $this->totalCommission($value) * $carpooling_user_details->booked_seats;
                        $debitArray = array(
                            'user_id' => $value->CarpoolingRide->user_id,
                            'amount' => $booking_fee,
                            'carpooling_ride_user_detail_id' => $carpooling_user_details->id,
                            'transaction_type' => 2,
                            'carpooling_ride_id' => $value->carpooling_ride_id,
                        );
                        WalletTransaction::UserWalletDebit($debitArray);
                        $data = [];
                        $message = trans("$string_file.booking_amount", ['user_name' => $value->CarpoolingRide->User->first_name, 'amount' => $booking_fee]);
                        $title = trans("$string_file.amount_debited");
                        $notification_type = 'Wallet_Notification';
                        $this->sendNotificationToUser($data, $message, $title, $notification_type, $value->CarpoolingRide->user_id, $value->CarpoolingRide->merchant_id);
                    }
                }
            }

            $return_data = [
                'ride_details' => $ride_detail_list,
                'user_data' => $ongoing_trip_details,
            ];
            $data = ['ride_status' => 3, 'ride_id' => $carpooling_user_details->carpooling_ride_id, 'booking_id' => (int)$carpooling_user_details->carpooling_ride_id];
            $message = trans("$string_file.passenger_ride_start", ['passengername' => $carpooling_user_details->User->first_name]);
            $title = 'Passenger Ride Start notification';
            $notification_type = 'Passenger_Ride_Start';
            $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_user_details->user_id, $user->merchant_id);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();

        $message = trans("$string.ride") . " " . trans("common.start") . " " . trans("common.successfully");
        return $this->successResponse($message, $return_data);
    }

    public function offerRideDrop(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile($request->merchant_id);
        $validator = Validator::make($request->all(), [
            'ride_id' => [
                'required',
                'integer',
                Rule::exists('carpooling_rides', 'id')->where(function ($query) {
                    $query->where([['ride_status', '=', 3]]);
                }),
            ],
            'carpooling_ride_user_detail_id' => [
                'required',
                'integer',
                Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
                    $query->whereIn('ride_status', [3, 4]);;
                }),
            ],
            'rating' => 'sometimes',
            'comment' => 'max:50',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $carpooling_ride = CarpoolingRide::find($request->ride_id);
            $carpooling_ride_user_details = CarpoolingRideUserDetail::find($request->carpooling_ride_user_detail_id);
            $carpooling_ride_user_details->ride_status = 4;
            $carpooling_ride_user_details->user_rating = $request->rating;
            $carpooling_ride_user_details->user_comment = $request->comment;

            //$carpooling_ride->booked_seats = $carpooling_ride->booked_seats - $carpooling_ride_user_details->booked_seats;
            $carpooling_ride->available_seats = $carpooling_ride->available_seats + $carpooling_ride_user_details->booked_seats;
            $carpooling_ride->save();
            // $paramArray = array(
            //     'user_id' =>  $carpooling_ride_user_details->user_id,
            //     'carpooling_ride_id' => $carpooling_ride_user_details->carpooling_ride_id,
            //     'amount' => $carpooling_ride_user_details->total_amount,
            //     'narration' => 5,
            // );
            // WalletTransaction::UserWalletDebit($paramArray);
            //     $user_hold =UserHold::where([['user_id', '=', $carpooling_ride_user_details->user_id],['carpooling_ride_id', '=', $carpooling_ride_user_details->carpooling_ride_id]])->first();
            //   // p( $user_hold);
            //     $user_hold->status = 1;// return
            //     $user_hold->save();
            //     $newarray = array(
            //         'user_id' => $carpooling_ride_user_details->user_id,
            //         'amount' => $user_hold->amount,
            //         'transaction_type' => 1,
            //         'carpooling_ride_id' =>$carpooling_ride_user_details->carpooling_ride_id,
            //     );
            //     WalletTransaction::UserWalletCredit($newarray);
            $return_param = array(
                "id" => $carpooling_ride_user_details->carpooling_ride_id,
                "timestamp" => time(),
                "driver_name" => $carpooling_ride_user_details->CarpoolingRide->User->first_name,
                'slug' => 'PASSENGER_RIDE_END',
            );
            $logs_history = json_decode($carpooling_ride_user_details->carpooling_logs, true);
            $passenger_info = array("passenger_name" => $carpooling_ride_user_details->User->first_name);
            array_push($return_param, $passenger_info);
            array_push($logs_history, $return_param);
            $carpooling_ride_user_details->carpooling_logs = json_encode($logs_history);
            $carpooling_ride_user_details->save();
//            $this->checkReferral($carpooling_ride_user_details,$carpooling_ride_user_details->total_amount);
            $user_rating = CarpoolingRideUserDetail::where([['user_id', '=', $carpooling_ride_user_details->user_id], ['ride_status', '=', 4]]);
            $user = User::where('id', '=', $carpooling_ride_user_details->user_id)->first();
            $user->passenger_rating = round($user_rating->avg('user_rating'), 1);
            $user->save();
            $data = ['ride_status' => 4, 'ride_id' => $carpooling_ride_user_details->id, 'booking_id' => (int)$carpooling_ride->id];
            $message = trans("$string_file.passenger_ride_end", ['passengername' => $carpooling_ride_user_details->User->first_name]);
            $title = "Ride Completed";
            $notification_type = "END_TAKEN_RIDE";
            $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride_user_details->user_id, $carpooling_ride_user_details->merchant_id);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'));

    }

    // public function takenRideRating(Request $request)
    // {
    //     $user = $request->user('api');
    //     $validator = Validator::make($request->all(), [
    //         'ride_id' => 'required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
    //         }),
    //         'carpooling_ride_user_detail_id' => 'required', 'integer', Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
    //         }),
    //         'rating' => 'required',
    //         'comment' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         $errors = $validator->messages()->all();
    //         return $this->failedResponse($errors[0]);
    //     }
    //     DB::beginTransaction();
    //     try {
    //         $carpooling_detail = CarpoolingRide::find($request->ride_id);
    //         $user_ride_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_detail->id]])->get();


    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return $this->failedResponse($e->getMessage());
    //     }
    //     DB::commit();
    //     return $this->successResponse(trans('common.success'), $return_data);
    // }

    public function startOfferRide(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile($request->merchant_id);
        $validator = Validator::make($request->all(), [
            'ride_id' => 'required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
            }),
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $carpooling_ride = CarpoolingRide::find($request->ride_id);
            $radius = 6367;
            $carpooling_config_country = CarpoolingConfigCountry::where('country_id', '=', $carpooling_ride->User->Country->id)->first();
            $ride_details = CarpoolingRideDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id]])->whereIn('ride_status', array(1, 2))->orderBy("drop_no")->first();;
            // $pickup_distance=DB::table("carpooling_rides")->addSelect(DB::raw('IFNULL( ( acos( cos( radians(' . $request->latitude . ') ) * cos( radians( ' . $carpooling_ride->start_latitude . ') ) * cos( radians( ' . $carpooling_ride->start_longituude . ' ) - radians(' . $request->longitude . ') ) + sin( radians(' . $request->latitude . ') ) * sin( radians( ' . $carpooling_ride->start_longitude . ') ) ) ), 0)'))->get();
            $pickup_distance = DB::table("carpooling_rides")->where('id', $carpooling_ride->id)->addSelect(DB::raw(' ' . $radius . '*(  acos( cos( radians(' . $request->latitude . ') ) * cos( radians( start_latitude ) ) * cos( radians( start_longitude ) - radians(' . $request->longitude . ') ) + sin( radians(' . $request->latitude . ') ) * sin( radians( start_latitude ) ) ) )AS pickup_distance'));

            $can_driver_start = $pickup_distance->having('pickup_distance', '<=', $carpooling_config_country->start_location_radius)->take(1)->get();
            if (!empty($can_driver_start)) {
                if (!empty($ride_details)) {
                    $carpooling_ride->ride_status = 3;
                    $ride_details->ride_status = 3;
                    $ride_details->save();
                    $return_param = array(
                        "id" => $carpooling_ride->id,
                        "timestamp" => time(),
                        "driver_name" => $carpooling_ride->User->first_name,
                        'slug' => 'RIDE_START',
                    );
                    $logs_history = json_decode($carpooling_ride->carpooling_logs, true);
                    array_push($logs_history, $return_param);
                    $carpooling_ride->carpooling_logs = json_encode($logs_history);
                    $carpooling_ride->save();
                    CarpoolingCoordinate::updateOrCreate(
                        ['carpooling_ride_id' => $request->ride_id], [
                        'start_location' => $request->latitude . "," . $request->longitude,
                    ]);
                    $pickup_users = CarpoolingRideUserDetail::where([['pickup_id', '=', $ride_details->id]])->where('ride_status', 2)->get()->pluck('user_id')->toArray();
                    if (!empty($pickup_users)) {
                        $data = ['ride_status' => 3, 'ride_id' => $carpooling_ride->id, 'booking_id' => (int)$carpooling_ride->id];
                        $title = 'Ride Start';
                        $notification_type = 'RIDE_START';
                        //$message = "Driver start the ride, Please arrive at pickup location.";
                        //$this->carpoolingRideUserLog($booking,$slug="RIDE_STARTED_BY_USER");

                        $message = trans("$string_file.start_ride_text");
                        $this->sendNotificationToUser($data, $message, $title, $notification_type, $pickup_users, $carpooling_ride->merchant_id);
                    }
                } else {
                    return $this->failedResponse("$string_file.not_found");
                }
            } else {
                return $this->failedResponse("$string_file.can_driver_start");
            }

            $data = new ActiveCarpoolingResource($carpooling_ride);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'));
    }

    public function endUserRide(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile($request->merchant_id);
        $validator = Validator::make($request->all(), [
            'ride_id' => 'required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
            }),
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $carpooling_ride = CarpoolingRide::find($request->ride_id);
            $ride_details = CarpoolingRideDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id], ['ride_status', '=', 3]])->get();
            //p( $ride_details);
            if (!empty($ride_details)) {
                foreach ($ride_details as $value) {
                    $carpooling_ride->update(['ride_status' => 4]);
                    $value->update(['ride_status' => 4]);

                }
            }
            $user_ride_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id], ['ride_status', '=', 4]])->get();
            if (!empty($user_ride_details)) {
                foreach ($user_ride_details as $value) {
                    if ($value->payment_action == 1) {
                        $amount = $this->calculateBillAmount($value)->ride_amount;
                        $newArray = array(
                            'user_id' => $value->CarpoolingRide->user_id,
                            'amount' => $amount,
                            'transaction_type' => 1,
                            'carpooling_ride_user_detail_id' => $value->id,
                            'carpooling_ride_id' => $value->CarpoolingRide->id,
                        );
                        WalletTransaction::UserWalletCredit($newArray);
                        $data = [];
                        $message = trans("$string_file.credit_transaction_amount", ['user_name' => $value->CarpoolingRide->User->first_name, 'amount' => $amount]);
                        $title = trans("$string_file.amount_credited");
                        $notification_type = 'Wallet_Notification';
                        $this->sendNotificationToUser($data, $message, $title, $notification_type, $value->CarpoolingRide->user_id, $value->CarpoolingRide->merchant_id);
                    }
                    if ($value->payment_action == 3) {
                        $amount = $this->calculateBillAmount($value)->total_amount;
                        $newArray = array(
                            'user_id' => $value->CarpoolingRide->user_id,
                            'amount' => $amount,
                            'transaction_type' => 1,
                            'carpooling_ride_user_detail_id' => $value->id,
                            'carpooling_ride_id' => $value->CarpoolingRide->id,
                        );
                        WalletTransaction::UserWalletCredit($newArray);
                        $data = [];
                        $message = trans("$string_file.credit_transaction_amount", ['user_name' => $value->CarpoolingRide->User->first_name, 'amount' => $amount]);
                        $title = trans("$string_file.amount_credited");
                        $notification_type = 'Wallet_Notification';
                        $this->sendNotificationToUser($data, $message, $title, $notification_type, $value->CarpoolingRide->user_id, $value->CarpoolingRide->merchant_id);
                    }
                }
            }
            // p($user_ride_details);
            $carpooling_ride->total_amount = $user_ride_details->sum('total_amount');
            $carpooling_ride->driver_earning = $user_ride_details->sum('ride_amount');
            $carpooling_ride->company_commission = $user_ride_details->sum('commission');
            $carpooling_ride->service_charges = $user_ride_details->sum('service_charges');
            $return_param = array(
                "id" => $carpooling_ride->id,
                "timestamp" => time(),
                "driver_name" => $carpooling_ride->User->first_name,
                'slug' => 'RIDE_END',
            );
            $logs_history = json_decode($carpooling_ride->carpooling_logs, true);
            array_push($logs_history, $return_param);
            //P($logs_history);
            $carpooling_ride->carpooling_logs = json_encode($logs_history);
            $carpooling_ride->save();
//            if($carpooling_ride->total_amount>=0){
//                $this->checkReferral( $carpooling_ride, $carpooling_ride->total_amount);
//            }
            $data = ['ride_status' => 4, 'ride_id' => $carpooling_ride->id, 'booking_id' => (int)$carpooling_ride->id];
            $title = 'Ride Complete';
            $notification_type = 'DRIVER_RIDE_END';
            $message = trans("$string_file.ride_completed_text", ['drivername' => $carpooling_ride->User->first_name, 'ID' => $carpooling_ride->id]);
            $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride->user_id, $carpooling_ride->merchant_id);


        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'));
    }
    //  public function walletHoldHistory(Request $request){
    //     $user = $request->user('api');
    //     DB::beginTransaction();
    //     try {

    //         $user_hold_amount=UserHold::where('user_id','=',$user->id);
    //         $total_amount=$user_hold_amount->get()->sum('amount');
    //         $carpooling_ride_user_details=CarpoolingRideUserDetail::where('user_id','=',$user->id)->get();
    //         $offer_hold_amount=[];
    //         $offer_hold=UserHold::where([['user_id','=',$user->id],['is_user_offer_ride','=',1]])->get();
    //         foreach($offer_hold as $value){
    //             $offer_hold_amount[] = array(
    //                 'ride_id'=>$value->carpooling_ride_id,
    //                 'amount' =>$user->Country->isoCode." ".$value->amount,
    //             );
    //         }
    //         $taken_hold_amount=[];
    //         $taken_hold=UserHold::where([['user_id','=',$user->id],['is_user_offer_ride','=',0]])->get();
    //         foreach($taken_hold as $value){
    //             foreach( $carpooling_ride_user_details as $val){
    //             $taken_hold_amount[] = array(
    //                 'ride_id'=> $val->id,
    //                 'amount' =>$user->Country->isoCode." ".$value->amount,
    //             );
    //         }
    //         }
    //         $return_data = [
    //             'Total_amount'=>$user->Country->isoCode." ".$total_amount,
    //             'Offer_hold_amount' => $offer_hold_amount,
    //             'Taken_hold_amount' => $taken_hold_amount,

    //         ];
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return $this->failedResponse($e->getMessage());
    //     }
    //     DB::commit();
    //     return $this->successResponse(trans('common.success'),$return_data);
    // }

    public function offerRideRating(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'ride_id' => 'required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
            }),
            'carpooling_ride_user_detail_id' => 'required', 'integer', Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
            }),
            'rating' => 'required',
            'comment' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $carpooling_detail = CarpoolingRide::find($request->ride_id);
            $user_ride_details = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $carpooling_detail->id], ['ride_status', '=', 4], ['id', '=', $request->carpooling_ride_user_detail_id]])->get();
            foreach ($user_ride_details as $value) {
                $value->driver_rating = $request->rating;
                $value->driver_comment = $request->comment;
                $value->save();
                $user_rating = CarpoolingRide::where([['user_id', '=', $carpooling_detail->user_id], ['ride_status', '=', 4]]);
                $user = User::where('id', '=', $carpooling_detail->user_id)->first();
                $user->driver_rating = round($value->avg('driver_rating'), 1);
                $user->save();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'));
    }

    public function offerDropUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ride_id' => ['required', 'integer', Rule::exists('carpooling_rides', 'id')->where(function ($query) {
            }),],
            'carpooling_ride_user_detail_id' => 'integer', Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
            }),

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $carpooling_ride = CarpoolingRide::find($request->ride_id);
        $data = new ActiveCarpoolingResource($carpooling_ride);
        return $this->successResponse(trans('carpooling.carpooling') . ' ' . trans('common.details'), $data);
    }

    public function ApplyRemovePromoCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'carpooling_ride_checkout_id' => 'required|exists:carpooling_ride_checkouts,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $check_promo_code = $this->CheckPromoCode($request);
            if ($request->promo_code) {
                if (isset($check_promo_code['status']) && $check_promo_code['status'] == true) {
                    $promocode = $check_promo_code['promo_code'];
                    $return_price = $this->calculateBillAmount($request, $promocode);
                    return $this->successResponse(trans("common.promo_code_applied"), $return_price);
                }
            }
            return $this->calculateBillAmount($request);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'));
    }

    public function offerRideTakenCancel(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'ride_id' => 'required', 'integer',
            Rule::exists('carpooling_ride', 'id')->where(function ($query) {
                $query->where('ride_status', '=', 3);
            }),
            'carpooling_ride_user_detail_id' => 'required', 'integer',
            Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
                $query->where('ride_status', '=', 2);
            }),
            'cancel_reason_id' => 'integer',
            'other_reason' => 'max:100',
            'current_time' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $carpooling_ride = CarpoolingRide::find($request->ride_id);
            $string_file = $this->getStringFile($user->merchant_id);
            $carpooling_ride_user_details = CarpoolingRideUserDetail::where([['id', '=', $request->carpooling_ride_user_detail_id], ['carpooling_ride_id', '=', $carpooling_ride->id], ['ride_status', '=', 2]])->first();
            //  p( $carpooling_ride_user_details);
            $company_cut = 0;
            $carpooling_ride_details = CarpoolingRideDetail::where([['carpooling_ride_id', '=', $carpooling_ride->id], ['ride_status', '=', 3]])->get();
            $carpooling_config_country = CarpoolingConfigCountry::where('country_id', '=', $carpooling_ride->User->Country->id)->first();
            $time = $carpooling_config_country->user_ride_start_time * 60;
            $cancel_time = $carpooling_ride + $time;

            // if($carpooling_config_country)
            if ($request->current_time >= $time) {
                if (!empty($carpooling_ride_user_details)) {
                    foreach ($carpooling_ride_details as $value) {
                        $company_cut = $this->cancelAmount($value) * $carpooling_ride_user_details->booked_seats;
                        $cancel_amount = round($company_cut);
                        $value->booked_seats -= $carpooling_ride_user_details->booked_seats;
                        $value->save();
                    }
                }
                if (empty($request->cancel_reason_id)) {
                    $carpooling_ride_user_details->cancel_reason_text = $request->other_reason;
                } else {
                    $carpooling_ride_user_details->cancel_reason_id = $request->cancel_reason_id;
                }
                $carpooling_ride_user_details->ride_status = 5;
                $carpooling_ride = CarpoolingRide::find($carpooling_ride_user_details->carpooling_ride_id);
                $carpooling_ride->available_seats += $carpooling_ride_user_details->booked_seats;
                $carpooling_ride->booked_seats -= $carpooling_ride_user_details->booked_seats;
                //$carpooling_ride->cancel_booked_seats += $carpooling_ride_user_details->booked_seats;
                $carpooling_ride->save();
                $return_param = array(
                    "id" => $carpooling_ride_user_details->id,
                    "timestamp" => time(),
                    "driver_name" => $carpooling_ride_user_details->CarpoolingRide->User->first_name,
                    'slug' => 'CANCEL_DRIVER',
                );
                $logs_history = json_decode($carpooling_ride_user_details->carpooling_logs, true);
                array_push($logs_history, $return_param);
                $carpooling_ride_user_details->carpooling_logs = json_encode($logs_history);
                $carpooling_ride_user_details->cancel_amount = $cancel_amount;
                $carpooling_ride_user_details->cancel_refund_amount = $this->calculateBillAmount($carpooling_ride_user_details)->total_amount;
                $carpooling_ride_user_details->save();
                //user wallet debit case
                $paramArray = array(
                    'user_id' => $carpooling_ride_user_details->user_id,
                    'amount' => $cancel_amount,
                    'narration' => 5,
                    'carpooling_ride_user_detail_id' => $carpooling_ride_user_details->id,
                    'carpooling_ride_id' => $carpooling_ride_user_details->carpooling_ride_id,
                );
                WalletTransaction::UserWalletDebit($paramArray);
                $data = [];
                $message = trans("$string_file.debit_cancel_amount", ['user_name' => $carpooling_ride_user_details->User->first_name, 'amount' => $cancel_amount]);
                $title = trans("$string_file.amount_debited");
                $notification_type = 'Wallet_Notification';
                $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride_user_details->user_id, $carpooling_ride_user_details->merchant_id);

                //user wallet credit case
                $refund_amount = $this->calculateBillAmount($carpooling_ride_user_details)->total_amount;
                $newArray = array(
                    'user_id' => $carpooling_ride_user_details->user_id,
                    'amount' => $refund_amount,
                    'transaction_type' => 1,
                    'carpooling_ride_user_detail_id' => $carpooling_ride_user_details->id,
                    'carpooling_ride_id' => $carpooling_ride_user_details->carpooling_ride_id,
                );
                WalletTransaction::UserWalletCredit($newArray);
                $data = [];
                $message = trans("$string_file.credit_refund_amount", ['user_name' => $carpooling_ride_user_details->User->first_name, 'amount' => $refund_amount]);
                $title = trans("$string_file.amount_credited");
                $notification_type = 'Wallet_Notification';
                $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride_user_details->user_id, $carpooling_ride_user_details->merchant_id);
                $data = ['ride_status' => 5, 'ride_id' => $carpooling_ride_user_details->carpooling_ride_id, 'cancel_charge' => $cancel_amount, 'booking_id' => (int)$carpooling_ride->id];
                $message = trans("$string_file.driver_cancel_ride", ['passengername' => $carpooling_ride_user_details->User->first_name, 'drivername' => $carpooling_ride->User->first_name, 'ID' => $carpooling_ride->id . "-" . $value->id]);
                $title = 'Cancel notification';
                $notification_type = 'Driver_Cancel_Request';
                $this->sendNotificationToUser($data, $message, $title, $notification_type, $carpooling_ride_user_details->user_id, $carpooling_ride_user_details->merchant_id);
            } else {
                return $this->failedResponse("$string_file.cancel_time", ['time' => date('H:i A', $cancel_time)]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $user->Country->isoCode . ' ' . $cancel_amount);
    }

    public function userReceipt(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'carpooling_ride_user_detail_id' => ['required', 'integer',
                Rule::exists('carpooling_ride_user_details', 'id')->where(function ($query) {
                    $query->whereIn('ride_status', array(4, 5, 6));
                }),]

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $carpooling_ride_user_detail = CarpoolingRideUserDetail::find($request->carpooling_ride_user_detail_id);
            //p( $carpooling_ride_user_detail);
            if (!empty($carpooling_ride_user_detail)) {
                $user_receipt = $this->userReceiptHolder($carpooling_ride_user_detail);
            }
            $return_data = ['User_Receipt' => $user_receipt];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $return_data);
    }

    public function driverReceipt(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'ride_id' => ['required', 'integer',
                Rule::exists('carpooling_rides', 'id')->where(function ($query) {
                    $query->whereIn('ride_status', array(4, 5));
                }),]

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $carpooling_ride = CarpoolingRide::find($request->ride_id);
            //p($carpooling_ride);
            //$carpooling_ride_user_detail=CarpoolingRideUserDetail::where('carpooling_ride_id','=',$carpooling_ride->id)->whereIn('ride_status',array(4,5,6,8))->get()
            if (!empty($carpooling_ride)) {
                $driver_receipt = $this->driverReceiptHolder($carpooling_ride);
            }
            $return_data = ['Driver_Receipt' => $driver_receipt];
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $return_data);
    }

    public function customerSupport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required', 'integer',
            Rule::exists('countries', 'id')->where(function ($query) {
            }),

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $country = Country::find($request->country_id);
            $country_area = CountryArea::where('country_id', '=', $country->id)->get();
            $support_area = [];
            if (!empty($country_area)) {
                foreach ($country_area as $value) {
                    $support_area[] = array(
                        'id' => $value->id,
                        'country' => $value->Country->CountryName,
                        'service_area' => $value->LanguageSingle->AreaName,
                        'whatsapp_no' => $value->whatsapp,
                        'customer_support_no' => $value->customer_support_number,
                        'email' => $value->email,
                    );
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans('common.success'), $support_area);
    }

    public function checkReferral($booking, $amount)
    {
        $priceController = new PriceController();
        $receiverReferDiscount = $priceController->Refer($booking->user_id, 1);

        if (!empty($receiverReferDiscount)) {
            $sender = ReferralDiscount::where([['id', '=', $receiverReferDiscount['id']]])->first();
            $sender_id = $sender->sender_id;
            $senderReferDiscount = $priceController->senderRefer($sender_id, 1);
            $receiver_amount = $priceController->getReferCalculation($receiverReferDiscount, $amount, $booking->id);
            //p($receiver_amount);
            // $sender_amount=$priceController->getReferCalculation($senderReferDiscount, $amount, $booking->id);
            if (!empty($receiver_amount)) {
                $receiverReferDiscount->referral_available = 2;
                $receiverReferDiscount->save();
                return true;
            }
        }
    }


}
