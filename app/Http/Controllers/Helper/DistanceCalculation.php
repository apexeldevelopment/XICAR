<?php
/**
 * Created by PhpStorm.
 * User: aamirbrar
 * Date: 2019-01-24
 * Time: 10:56
 */

namespace App\Http\Controllers\Helper;


use App\Models\DistanceSetting;

class DistanceCalculation
{
    public function distance($start_lat_long, $end_lat_long, $pick, $drop, $latlong, $merchant_id, $key, $calling_from = "", $string_file ="", $waypoints = NULL, $booking_id = NULL)
    {
        $newArray = !empty($latlong) ? json_decode($latlong, true): null;
        $settings = DistanceSetting::where([['merchant_id', '=', $merchant_id]])->oldest()->first();
        if (!empty($settings) && !empty($newArray) && count($newArray) > 2) { //commented because we are not getting coordinates from app end in somecases
            $distance_methods = json_decode($settings->distance_methods, true);
            foreach ($distance_methods as $value) {
                $method_id = $value['method_id'];
                switch ($method_id) {
                    case "1":
                        $response = $this->SnapToRoadWayPoint($newArray, $value['last_timestamp_difference'], $value['maximum_timestamp_difference'], $value['min_speed'], $value['max_speed'], $key);
                        if ($response != false) {
                            return $response;
                        }
                        break;
                    case "2":
//                        $response = $this->SnapToRoadAerial($newArray, $value['last_timestamp_difference'], $value['maximum_timestamp_difference'], $value['min_speed'], $value['max_speed'], $key);
                           $google_distance_check = false;
                           $start_end_coordinates = [
                                "start" => $start_lat_long,
                                "end" => $end_lat_long,
                            ];
                           if(isset($value['google_distance_check']) && $value['google_distance_check'] == "1"){
                                $google_distance_check = true;
                           }
                           $response = $this->SnapToRoadAerial($newArray, $value['last_timestamp_difference'], $value['maximum_timestamp_difference'], $value['min_speed'], $value['max_speed'], $key, $google_distance_check, $start_end_coordinates, NULL, $booking_id);
                        if ($response != false) {
                            return $response;
                        }
                        break;
                    case "3":
                        $response = $this->OnlyAerial($newArray, $value['last_timestamp_difference'], $value['maximum_timestamp_difference'], $value['min_speed'], $value['max_speed']);
                        if ($response != false) {
                            return $response;
                        }
                        break;
                    case "5":
                        $distance = GoogleController::GoogleShortestPathDistance($start_lat_long, $end_lat_long, $key,'metric',$calling_from,$string_file);
                        if ($distance != false) {
                            return $distance;
                        }
                        break;
                    case "6":
                        $distance = GoogleController::GoogleShortestPathDistance($pick, $drop, $key,'metric',$calling_from,$string_file);
                        if ($distance != false) {
                            return $distance;
                        }
                        break;
                    case "10":
                        $distance = GoogleController::GoogleShortestPathWithWaypointDistance($pick, $drop, $key,'metric',$waypoints, "DistanceCalculationMethod");
                        if ($distance != false) {
                            return $distance;
                        }
                        break;
                }
            }
        }
        $distance = GoogleController::GoogleShortestPathDistance($start_lat_long, $end_lat_long, $key,'metric',$calling_from);
        return $distance;
    }

    public function OnlyAerial($newArray, $last_timestamp_difference, $maximum_timestamp_difference, $min_speed, $max_speed)
    {

//        if (!empty($maximum_timestamp_difference)) {
//            $timediffernce = $this->CheckTimeDiffernce($newArray, $maximum_timestamp_difference);
//            if ($timediffernce == false) {
//                return false;
//            }
//        }

        $distance = $this->Aerial($newArray);
        if ($distance == false) {
            return false;
        }
        if (!empty($min_speed) && !empty($max_speed) && !is_null($min_speed) && !is_null($max_speed)) {
            $time = 10;
            $speed = $distance / $time;
            if ($speed < $min_speed || $speed > $max_speed) {
                return false;
            }
        }
        return $distance;
    }

    public function SnapToRoadAerial($newArray, $last_timestamp_difference, $maximum_timestamp_difference, $min_speed, $max_speed, $key, $google_distance_check = false, $start_end_coordinates = [], $waypoint = NULL, $booking_id = NULL)
    {
//        if (!empty($maximum_timestamp_difference)) {
//            $timediffernce = $this->CheckTimeDiffernce($newArray, $maximum_timestamp_difference);
//            if ($timediffernce == false) {
//                return false;
//            }
//        }

        $distance_log = [
            "aerial" => null,
            "GoogleDistanceAndTime" => null,
            "GoogleShortestPathWithWaypointDistance"=>null,
            "finalized"=> null,
        ];

        $googleServices = new GoogleController();
        $snapToRoad = $googleServices->SnapToRoad($newArray, $key);
        if ($snapToRoad == false) {
            return false;
        } else {
            foreach ($snapToRoad as $value) {
                $latlong = explode(',', $value);
                $AerialArray[] = array('latitude' => $latlong[0], 'longitude' => $latlong[1]);
            }
        }
        $distance = $this->Aerial($AerialArray);
        $distance_log['aerial'] = $distance;

        if($google_distance_check && empty($waypoint)){
            $google_distance_and_time = GoogleController::GoogleDistanceAndTime($start_end_coordinates['start'], $start_end_coordinates['end'], $key, 'metric', false, "GoogleDistanceAndTime(MaxDistance($booking_id))");
            if (!empty($google_distance_and_time['distance_in_meter'])) {
                $distance = max((float) $google_distance_and_time['distance_in_meter'], (float) $distance);
                $distance_log['GoogleDistanceAndTime'] = $google_distance_and_time['distance_in_meter'];
            }
        }
        else if($google_distance_check && !empty($waypoint)){
            $google_distance_and_time = GoogleController::GoogleShortestPathWithWaypointDistance($start_end_coordinates['start'], $start_end_coordinates['end'], $key, 'metric', $waypoint, "GoogleShortestPathWithWaypoint(MaxDistance($booking_id))");
            if (!empty($google_distance_and_time)) {
                $distance = max((float) $google_distance_and_time, (float) $distance);
                $distance_log['GoogleShortestPathWithWaypointDistance'] = $google_distance_and_time;
            }
        }

        $distance_log['finalized'] = $distance;
        if(!empty($booking_id)){
            $booking_details  = \App\Models\BookingDetail::where("booking_id", $booking_id)->first();

            if(!empty($booking_details)){
                $booking_details->distance_log = json_encode($distance_log);
                $booking_details->save();
            }
        }

        if ($distance == false) {
            return false;
        }
        if (!empty($min_speed) && !empty($max_speed) && !is_null($min_speed) && !is_null($max_speed)) {
            $time = 10;
            $speed = $distance / $time;
            if ($speed < $min_speed || $speed > $max_speed) {
                return false;
            }
        }
        return $distance;
    }

//    public function Aerial($snapToRoad)
//    {
//        $totalDistanceMeters = 0;
//        if(count($snapToRoad) > 2)
//        {
//            for ($i = 0; $i < (count($snapToRoad) - 1); $i++) {
//                $driver_lat_first = $snapToRoad[$i]['latitude'];
//                $driver_long_first = $snapToRoad[$i]['longitude'];
//                $driver_lat_second = $snapToRoad[$i + 1]['latitude'];
//                $driver_long_second = $snapToRoad[$i + 1]['longitude'];
//                $theta = $driver_long_first - $driver_long_second;
//                $dist = sin(deg2rad($driver_lat_first)) * sin(deg2rad($driver_lat_second)) + cos(deg2rad($driver_lat_first)) * cos(deg2rad($driver_lat_second)) * cos(deg2rad($theta));
//                $dist = acos($dist);
//                $dist = rad2deg($dist);
//                // $miles = $dist * 60 * 1.1515;
//                // $km = $miles * 1.609344;
//                // $km = round($km, 2);
//                // $dist2[] = $km;
//
//                $miles = $dist * 60 * 1.1515;
//                $kilometers = $miles * 1.609344;
//                $meters = $kilometers * 1000;
//
//                $totalDistanceMeters += $meters;
//            }
//            // $dist1 = array_sum($dist2);
//        }
//        return round($totalDistanceMeters, 2);
//    }


    public function Aerial($snapToRoad)
    {
        $totalDistanceMeters = 0;

        if (count($snapToRoad) > 2) {
            for ($i = 0; $i < (count($snapToRoad) - 1); $i++) {
                $lat1 = $snapToRoad[$i]['latitude'];
                $lon1 = $snapToRoad[$i]['longitude'];
                $lat2 = $snapToRoad[$i + 1]['latitude'];
                $lon2 = $snapToRoad[$i + 1]['longitude'];

                $theta = $lon1 - $lon2;

                $rawDist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
                    cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));

                // Clamp value between -1 and 1 to avoid NaN
                $clampedDist = min(1, max(-1, $rawDist));

                $dist = acos($clampedDist);
                $dist = rad2deg($dist);
                $miles = $dist * 60 * 1.1515;
                $kilometers = $miles * 1.609344;
                $meters = $kilometers * 1000;

                $totalDistanceMeters += $meters;
            }
        }
        return round($totalDistanceMeters, 2);
    }
    public function SnapToRoadWayPoint($newArray, $last_timestamp_difference, $maximum_timestamp_difference, $min_speed, $max_speed, $key)
    {

//        if (!empty($maximum_timestamp_difference) && !is_null($maximum_timestamp_difference)) {
//            $timediffernce = $this->CheckTimeDiffernce($newArray, $maximum_timestamp_difference);
//            if ($timediffernce == false) {
//                return false;
//            }
//        }
        $googleServices = new GoogleController();
        $snapToRoad = $googleServices->SnapToRoad($newArray, $key);
        if ($snapToRoad == false) {
            return false;
        }
        $distance = $googleServices->WayPointDistance($snapToRoad, $key);
        if ($distance == false) {
            return false;
        }
        if (!empty($min_speed) && !empty($max_speed) && !is_null($min_speed) && !is_null($max_speed)) {
            $time = 10;
            $speed = $distance / $time;
            if ($speed < $min_speed || $speed > $max_speed) {
                return false;
            }
        }
        return $distance;
    }

    public function CheckTimeDiffernce($newArray, $maximum_timestamp_difference)
    {
        foreach ($newArray as $value) {
            $timeStamp = $value['timeStamp'];
            $time = true;
            if ($timeStamp > $maximum_timestamp_difference) {
                return false;
            }
        }
        return $time;
    }

    public function distanceCalculation($pickup,$drop){
        list($lat1, $lng1) = explode(',', $pickup);
        list($lat2, $lng2) = explode(',', $drop);
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
}