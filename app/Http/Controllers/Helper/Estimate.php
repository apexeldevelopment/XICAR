<?php

namespace App\Http\Controllers\Helper;


use App\Models\BookingConfiguration;
use App\Models\CountryArea;
use App\Models\Driver;
use App\Models\PriceCard;
use App\Models\VehicleType;
use App\Traits\AreaTrait;
use App\Http\Controllers\Helper\ExtraCharges;
use App\Http\Controllers\Helper\Merchant;
use App\Models\ApplicationConfiguration;

class Estimate
{
    use AreaTrait;
    public function Eta($merchant_id, $service_type, $area, $pickup_latitude, $pickup_longitude, $vehicle_type = null,$segment_id = NULL,$drop_location = NULL,$is_taxi = NULL)
    {
        $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
        $app_config = ApplicationConfiguration::Select('working_with_redis')->where([['merchant_id', '=', $merchant_id]])->first();
        if (!empty($configuration->driver_ride_radius_request)) {
            $ride_radius = json_decode($configuration->driver_ride_radius_request, true);
            $remain_ride_radius_slot = $ride_radius;
        } else {
            $remain_ride_radius_slot = array();
        }

        switch ($service_type) {
            case 1:
                $configuration->normal_ride_now_radius ? $configuration->normal_ride_now_radius : 0;
                $configuration->normal_ride_now_request_driver ? $configuration->normal_ride_now_request_driver : 0;
                break;
            case 2:
                $configuration->normal_ride_now_radius = $configuration->rental_ride_now_radius ? $configuration->rental_ride_now_radius : 0;
                $configuration->normal_ride_now_request_driver = $configuration->rental_ride_now_request_driver ? $configuration->rental_ride_now_request_driver : 0;
                break;
            case 3:
                $configuration->normal_ride_now_radius = $configuration->transfer_ride_now_radius ? $configuration->transfer_ride_now_radius : 0;
                $configuration->normal_ride_now_request_driver = $configuration->transfer_ride_now_request_driver ? $configuration->transfer_ride_now_request_driver : 0;
                break;
            case 4:
                $configuration->normal_ride_now_radius = $configuration->outstation_ride_now_radius ? $configuration->outstation_ride_now_radius : 0;
                $configuration->normal_ride_now_request_driver = $configuration->outstation_ride_now_request_driver ? $configuration->outstation_ride_now_request_driver : 0;
                break;
        }
        $eta = [];
        $user_gender = NULL;
        $distance = !empty($remain_ride_radius_slot) ? $remain_ride_radius_slot[0] : $configuration->normal_ride_now_radius;
        if ($service_type != 5) {
            $findDriver = new FindDriverController();
//            $drivers = $findDriver->GetAllNearestDriver($area, $pickup_latitude, $pickup_longitude, $distance, $configuration->normal_ride_now_request_driver, $vehicle_type, $service_type, '', '', $user_gender = null, $configuration->driver_request_timeout,$segment_id);
            $drivers = Driver::GetNearestDriver([
                'area'=>$area,
                'segment_id'=>$segment_id,
                'latitude'=>$pickup_latitude,
                'longitude'=>$pickup_longitude,
                'distance'=>$distance,
                'limit'=>$configuration->normal_ride_now_request_driver,
                'service_type'=>$service_type,
                'vehicle_type'=>$vehicle_type,
                'user_gender'=>$user_gender,
                'call_google_api'=>false,
            ]);

            if (empty($drivers)) {
                $areaDetails = CountryArea::select('id', 'auto_upgradetion')->find($area);
                if ($areaDetails->auto_upgradetion != 1) {
                    return $eta;
                }
                $vehicleDetail = VehicleType::select('id', 'vehicleTypeRank')->find($vehicle_type);
                if(!empty($vehicleDetail) && $vehicleDetail->count() > 0){
                    $drivers = $findDriver->GetAutoUpgradeDriver($area, $pickup_latitude, $pickup_longitude, $configuration->normal_ride_now_radius, $configuration->normal_ride_now_request_driver, $service_type, $vehicleDetail->vehicleTypeRank, $user_gender, $configuration->driver_request_timeout);
                }
                //$drivers = $findDriver->GetAutoUpgradeDriver($vehicle_type, $pickup_latitude, $pickup_longitude, $configuration->normal_ride_now_radius, $configuration->normal_ride_now_request_driver, $service_type, $vehicleDetail->vehicleTypeRank, $user_gender, $configuration->driver_request_timeout);
            }
            else{
                if($configuration->driver_eta_est_distance == 1){
                    $current_latitude = isset($drivers['0']) ? $drivers['0']->current_latitude : "";
                    $current_longitude = isset($drivers['0']) ? $drivers['0']->current_longitude : "";
                    if ($app_config->working_with_redis == 1 && isset($drivers[0])) {
                        $merchant_id = $drivers[0]->merchant_id ?? null;
                        $driver_id = $drivers[0]->id ?? null;
                        if ($merchant_id && $driver_id) {
                            $driver_current_loc = getDriverCurrentLatLong($drivers[0]);
                            $current_latitude = $driver_current_loc['latitude'];
                            $current_longitude = $driver_current_loc['longitude'];
                        }
                    }
                    $driver_current_location = $current_latitude . ',' . $current_longitude;
                    $pick_lat_long = $pickup_latitude . "," . $pickup_longitude;
                    $driverLatLong = $current_latitude . "," . $current_longitude;
                    $countryArea = CountryArea::find($area);
                    $units = ($countryArea->Country['distance_unit'] == 1) ? 'metric' : 'imperial';
                    $poly_line = $configuration->polyline == 1 ? true : false;
                    $eta = GoogleController::GoogleDistanceAndTime($driver_current_location, $pick_lat_long, $configuration->google_key, $units, $poly_line);
                    return $eta;
                }
                
            }
        }
        if ($service_type == 5) {
            if (empty($configuration->pool_radius)) {
                return $eta;
            }
            $pricecards = PriceCard::where([['country_area_id', '=', $area], ['service_type_id', '=', $service_type]])->get();
            if (empty($pricecards->toArray())) {
                return $eta;
            }
            $vehicle_type_id = array_pluck($pricecards, 'vehicle_type_id');
            $findDriver = new FindDriverController();
            $drivers = $findDriver->checkPoolDriver($area, $pickup_latitude, $pickup_longitude, $configuration->pool_radius, $configuration->pool_now_request_driver, 1, $vehicle_type_id, 1, $configuration->driver_request_timeout);
            if (empty($drivers)) {
                return $eta;
            }
        }
        if(empty($drivers) || $drivers->count() == 0)
        {
            return $eta;
        }
        $from = $pickup_latitude . "," . $pickup_longitude;
        $current_latitude = isset($drivers['0']) ? $drivers['0']->current_latitude : "";
        $current_longitude = isset($drivers['0']) ? $drivers['0']->current_longitude : "";
        $driverLatLong = $current_latitude . "," . $current_longitude;
        $estimate = []; // DistanceController::DistanceAndTime($from, $driverLatLong, $configuration->google_key);
        if(!empty($drop_location)){
            $drop_location_array = json_decode($drop_location, true);
            $drop_lat = isset($drop_location_array[0]['drop_latitude']) ? $drop_location_array[0]['drop_latitude'] : '';
            $drop_long = isset($drop_location_array[0]['drop_longitude']) ? $drop_location_array[0]['drop_longitude'] : '';
            $to = $drop_lat.','.$drop_long;
            $estimate_drop = DistanceController::DistanceAndTime($from, $to, $configuration->google_key);
            if($is_taxi){
                $estimate['time_sec'] = $estimate_drop['time_sec'];
            }else{
                $estimate['time_sec'] = $estimate_drop['time'];
            }
        }
        // $estimate_driver_distance = $nearDriver['distance'];
//        $estimate_driver_time = $nearDriver['time'];
//        $time_in_sec = $nearDriver['time_sec'];
        return $estimate;
    }

//    public function Estimate($merchant_id, $service_type, $area, $pickup_latitude, $pickup_longitude, $drop_location = null, $vehicle_type = null, $currency = null,$package_id= null)
//    {
////        p($package_id);
//        $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
//        $amount = $currency . " 0.00";
//        if ($service_type != 5) {
//            $pricecards = PriceCard::where([['country_area_id', '=', $area], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $service_type], ['vehicle_type_id', '=', $vehicle_type]])
//                ->where(function($q) use ($package_id){
//                    if(!empty($package_id)){
//                        $q->where('package_id',$package_id);
//                    }
//                })
//                ->first();
////            p($pricecards->toArray());
//            if (empty($pricecards)) {
//                return $amount;
//            }
//        }
//        if ($service_type == 5) {
//            $pricecards = PriceCard::where([['country_area_id', '=', $area], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $service_type]])->first();
//            if (empty($pricecards)) {
//                return $amount;
//            }
//        }
//        $drop_locationArray = [];
//        if (!empty($drop_location)) {
//            //$drop_locationArray[] = array('drop_latitude' => $drop_latitude, 'drop_longitude' => $drop_longitude);
//            $drop_locationArray = json_decode($drop_location, true);
//        }
//        $googleArray = GoogleController::GoogleStaticImageAndDistance($pickup_latitude, $pickup_longitude, $drop_locationArray, $configuration->google_key);
//        if (empty($googleArray)) {
//            return $amount;
//        }
//        $timeSmall = $googleArray['total_time_minutes'];
//        $distanceSmall = $googleArray['total_distance'];
//        $merchant = new Merchant();
//        switch ($pricecards->pricing_type) {
//            case "1":
//            case "2":
//                $estimatePrice = new PriceController();
//                $fare = $estimatePrice->BillAmount([
//                    'price_card_id' => $pricecards->id,
//                    'merchant_id' => $merchant_id,
//                    'distance' => $distanceSmall,
//                    'time' => $timeSmall,
//                    'booking_id' => NULL,
//                    'user_id' => isset(request()->user('api')->id) ? request()->user('api') : NULL,
//                    //'user_id' => request()->user('api')->id, // commented for static website
//                    'booking_time' => date('H:i'),
//                    'units' => CountryArea::find($area)->Country['distance_unit']
//                ]);
//                $amount = $currency . " " . $merchant->FinalAmountCal($fare['amount'],$merchant_id);
//                break;
//            case "3":
//                $amount = trans('api.message62');;
//                break;
//        }
//        return $amount;
//    }

    public function Estimate($merchant_id, $service_type, $area, $pickup_latitude, $pickup_longitude, $drop_location = null, $vehicle_type = null, $currency = null,$package_id= null,$googleArray = [],$drop_location_outside_area = null)
    {
        $amount = $currency . " 0.00";
        $merchant_helper = new Merchant();
        if ($service_type != 5) {
            $drop_lat = '';
            $drop_long = '';
            if(!empty($drop_location)){
                $drop_location_array = json_decode($drop_location, true);
                $drop_lat = isset($drop_location_array[0]['drop_latitude']) ? $drop_location_array[0]['drop_latitude'] : '';
                $drop_long = isset($drop_location_array[0]['drop_longitude']) ? $drop_location_array[0]['drop_longitude'] : '';
            }
            $merchant = \App\Models\Merchant::find($merchant_id);
            $countryArea = [];
            $dropCountryArea = [];
            $countryAreaGeofence = false;
            $dropCountryAreaGeofence = false;
            if(isset($merchant->Configuration->geofence_module) &&  $merchant->Configuration->geofence_module == 1 && $drop_lat != '' && $drop_long != ''){
                $countryArea = CountryArea::find($area);
                $countryAreaGeofence = ($countryArea->is_geofence == 1) ? true : false;
                $dropCountryArea = $this->checkGeofenceArea($drop_lat,$drop_long,'drop',$merchant_id);
                $dropCountryAreaGeofence = (!empty($dropCountryArea) && $dropCountryArea->is_geofence == 1) ? true : false;
            }

            if($countryAreaGeofence){
                $pricecards = PriceCard::where([['country_area_id', '=', $area], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $service_type], ['vehicle_type_id', '=', $vehicle_type]])
                    ->where(function($q) use ($package_id){
                        if(!empty($package_id)){
                            $q->where('service_package_id',$package_id);
                        }
                    })
                    ->first();
            }elseif($dropCountryAreaGeofence){
                $pricecards = PriceCard::where([['country_area_id', '=', $dropCountryArea->id], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $service_type], ['vehicle_type_id', '=', $vehicle_type]])
                    ->where(function($q) use ($package_id){
                        if(!empty($package_id)){
                            $q->where('service_package_id',$package_id);
                        }
                    })
                    ->first();
                if(empty($pricecards)){
                    $pricecards = PriceCard::where([['country_area_id', '=', $area], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $service_type], ['vehicle_type_id', '=', $vehicle_type]])
                    ->where(function($q) use ($package_id){
                        if(!empty($package_id)){
                            $q->where('service_package_id',$package_id);
                        }
                    })
                    ->first();
                }
            }else{
                $where = [['status', '=', 1],['country_area_id', '=', $area], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $service_type], ['vehicle_type_id', '=', $vehicle_type]];
                if($merchant->Configuration->drop_outside_area == 1 && $merchant->Configuration->outside_area_ratecard == 1 && $drop_location_outside_area == 1){
                    array_push($where,['rate_card_scope','=',2]);
                }
                $pricecards = PriceCard::where($where)
                    ->where(function($q) use ($package_id){
                        if(!empty($package_id)){
                            $q->where('service_package_id',$package_id);
                        }
                    })
                    ->first();
            }
            if (empty($pricecards)) {
                return $amount;
            }
        }
        if ($service_type == 5) {
            $pricecards = PriceCard::where([['country_area_id', '=', $area], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $service_type]])->first();
            if (empty($pricecards)) {
                return $amount;
            }
        }
//        $drop_locationArray = [];
//        if (!empty($drop_location)) {
//            //$drop_locationArray[] = array('drop_latitude' => $drop_latitude, 'drop_longitude' => $drop_longitude);
//            $drop_locationArray = json_decode($drop_location, true);
//        }
////        $googleArray = ;
        if (empty($googleArray)) {
            return $amount;
        }
        $timeSmall = $googleArray['total_time_minutes'];
        $distanceSmall = $googleArray['total_distance'];
        $merchant = new Merchant();
        switch ($pricecards->pricing_type) {
            case "1":
            case "2":
                $estimatePrice = new PriceController();
                $fare = $estimatePrice->BillAmount([
                    'price_card_id' => $pricecards->id,
                    'merchant_id' => $merchant_id,
                    'distance' => $distanceSmall,
                    'time' => $timeSmall,
                    'booking_id' => NULL,
                    'user_id' => isset(request()->user('api')->id) ? request()->user('api') : NULL,
                    //'user_id' => request()->user('api')->id, // commented for static website
//                    'booking_time' => date('H:i'),
                    'units' => CountryArea::find($area)->Country['distance_unit']
                ]);
                // $amount = $currency . " " . $merchant->FinalAmountCal($fare['amount'],$merchant_id);
                $amount = $currency . " " . $merchant_helper->PriceFormat($merchant->FinalAmountCal($fare['amount'], $merchant_id), $merchant_id);
                break;
            case "3":
                $amount = trans('api.message62');;
                break;
        }
        return $amount;
    }


    public function getEstimateCompetitorPrice($merchant_id,$service_type , $area, $vehicle_type, $googleArray, $drop_location){
        $pricecard = PriceCard::with("CompetitorPriceCard")->where([['country_area_id', '=', $area], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $service_type], ['vehicle_type_id', '=', $vehicle_type]])->first();
        if(empty($pricecard->CompetitorPriceCard) || empty($drop_location)) return "";
        $competitor_price_card = $pricecard->CompetitorPriceCard;

        $amount = 0;
        $free_distance = $competitor_price_card->distance_included_in_base_fare;
        $free_time = $competitor_price_card->time_included_in_base_fare;
        $distance_charges = $competitor_price_card->distance_charges;
        $wait_time_charges = $competitor_price_card->wait_time_charges;
        $time_included_in_wait_charges = $competitor_price_card->time_included_in_wait_charges;
        $time_charges = $competitor_price_card->time_charges;

        $distance = isset($googleArray['total_distance'])? $googleArray['total_distance']/1000 : 0;
        $waitmin = isset($googleArray['total_time_minutes'])? $googleArray['total_time_minutes'] : 0;

        //base fare
        $amount += $competitor_price_card->base_fare;
        
        
        //distance charges parameter
        if ($distance > $free_distance) {
            $extra_distance = $distance - $free_distance;
            $parameterAmount = $extra_distance * $distance_charges;
            $amount = $amount + $parameterAmount;
        }

        //wait time charges
        if ($waitmin > $time_included_in_wait_charges) {
            $extra = $waitmin - $time_included_in_wait_charges;
            $parameterAmount = $wait_time_charges * $extra;
            $amount = $amount + $parameterAmount;
        }

        if($time_charges){
            $parameterAmount = $waitmin * $time_charges;
            $amount = $amount + $parameterAmount;
        }

        return (!empty($amount) && $amount > 0) ? $pricecard->CountryArea->Country->isoCode.' '.round_number($amount, 2): "";
    }

}
