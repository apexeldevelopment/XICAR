<?php

namespace App\Http\Controllers\Helper;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\DistanceCalculation;

class GoogleController extends Controller
{
    public static function GoogleStaticImageAndDistance($pickuplat = null, $pickuplong = null, array $drop = null, $key = null, $units = 'metric',$string_file = "")
    {
       try{
           $start = $pickuplat . ',' . $pickuplong;
           $units = !empty($units) ? $units : 'metric';
           if (!empty($drop)):
               $count_combine = count($drop); //CHECK FOR SINGLE DROP or MULTIPLE DROP
               if ($count_combine > 1): // IF MULTIPLE DROP
                   $end = array_shift($drop); // fetch first element of the array, because zero element hold drop location value
                   $finish = $end['drop_latitude'] . ',' . $end['drop_longitude'];
                   $count_waypoints = count($drop); // CHECK FOR MULTIPLE WAYPOINTS or SINGLE WAYPOINT
                   if ($count_waypoints > 1):   // IF MULTIPLE WAYPOINTS
                       $multiple_waypoints = array();
                       for ($j = 0; $j < $count_waypoints; $j++) {
                           $lat_long = $drop[$j]['drop_latitude'] . ',' . $drop[$j]['drop_longitude'];
                           $multiple_waypoints[] = $lat_long;
                       }
                       $waypoints = implode("|", $multiple_waypoints);
                   else:   // IF SINGLE WAYPOINT
                       $waypoints = $drop[0]['drop_latitude'] . ',' . $drop[0]['drop_longitude'];
                   endif;
                   return self::GoogleStaticMultiplePointsImage($start, $finish, $waypoints, $key, $units,$string_file);
               else:   // IF SINGLE DROP
                   $end = array_pop($drop);
                   $finish = $end['drop_latitude'] . ',' . $end['drop_longitude'];
                   return self::GooglestaticsinglePointImage($start, $finish, $key, $units,2,'no',$string_file);
               endif;
           else:   // IF NO DROP LOCATION RECEIVED
               return self::GoogleStaticNoDropImage($start, $key);
           endif;
       }catch (\Exception $e)
       {
           throw new \Exception($e->getMessage());
       }
    }

    public static function GoogleStaticMultiplePointsImage($startpoint = null, $finishpoint = null, $waypoints = null, $key = null, $units = null,$string_file = "")
    {
        try{
            $url  = 'https://maps.googleapis.com/maps/api/directions/json?units=' . $units . '&origin=' . $startpoint . '&destination=' . $finishpoint . '&mode=driving&waypoints=' . $waypoints . '&key=' . $key;
            $log_data = [
                'request_type'=>'Direction Api',
                'data'=>$url,
                'additional_notes'=>'Direction Api for Image(GoogleStaticMultiplePointsImage)',
            ];
            google_api_log($log_data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $data = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($data, true);
            $status = $data['status'];
            if ($status != "OK") {
//                return array();
                $message = !empty($data['error_message']) ?  $data['error_message'] : trans("$string_file.google_key_not_working");
                throw new \Exception($message);
            }
            $total_distance = 0;
            $total_time = 0;
            foreach ($data['routes'][0]['legs'] as $eachdistance) {
                $total_distance += $eachdistance['distance']['value'];
                $total_time += $eachdistance['duration']['value'];
            }
            $total_distance_text = round($total_distance / 1000); //changes pending when distance unit change(KM/MILES)
            $total_time_minutes = round($total_time / 60);
            $total_time_text = $total_time_minutes.' mins';
            if($total_time_minutes > 60){
                $total_time_text = round($total_time_minutes / 60).' hr';
            }
            $points = $data['routes'][0]['overview_polyline']['points'];
            $image = "https://maps.googleapis.com/maps/api/staticmap?center=&maptype=roadmap&path=color:0x000000%7Cweight:10%7Cenc:" . urlencode($points) . "&markers=color:green%7Clabel:P%7C" . $startpoint . "&markers=color:red%7Clabel:D%7C" . $waypoints . "&markers=color:red%7Clabel:D%7C" . $finishpoint . "&key=" . $key;
            return ['total_distance' => $total_distance, 'total_distance_text' => $total_distance_text, 'total_time' => $total_time, 'total_time_minutes' => $total_time_minutes, 'total_time_text' => $total_time_text, 'image' => $image];
        }catch(\Exception $e)
        {
          throw new \Exception($e->getMessage());
        }

    }

    public static function GooglestaticsinglePointImage($startpoint = null, $finishpoint = null, $key = null, $units = null,$static_map = 2,$return_image = "no",$string_file = "")
    {
        try {
            $url  = 'https://maps.googleapis.com/maps/api/directions/json?units=' . $units . '&origin=' . $startpoint . '&destination=' . $finishpoint . '&mode=driving&key=' . $key;
            $log_data = [
                'request_type'=>'Direction Api',
                'data'=>$url,
                'additional_notes'=>'Direction Api for Image(GooglestaticsinglePointImage)',
            ];
            google_api_log($log_data);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "",
                CURLOPT_HTTPHEADER => array(
                    "Postman-Token: 5bf321ea-a304-47c9-82e9-deef8014cffc",
                    "cache-control: no-cache"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            $data = json_decode($response, true);
            $status = $data['status'];
//            if ($status != "OK") {
//                return array();
//            }
                if($status == "ZERO_RESULTS"){
                    $distanceCalculation = new DistanceCalculation();
                    $distance = $distanceCalculation->distanceCalculation($startpoint,$finishpoint);
                    return ['total_distance' => $distance, 'total_distance_text' => "", 'total_time' => 0, 'total_time_minutes' => 0, 'total_time_text' => "", 'image' => "",'poly_points'=>""];
                    
                }
            if ($status != "OK") {
//                return array();
                $message = !empty($data['error_message']) ?  $data['error_message'] : trans("$string_file.google_key_not_working");
                throw new \Exception($message);
            }
            $total_time_text = $data['routes'][0]['legs'][0]['duration']['text'];
            $timeSmall = $data['routes'][0]['legs'][0]['duration']['value'];
            $total_time_minutes = round($timeSmall / 60, 2);
            $total_distance_text = $data['routes'][0]['legs'][0]['distance']['text'];
            $distanceSmall = $data['routes'][0]['legs'][0]['distance']['value'];
            $points = $data['routes'][0]['overview_polyline']['points'];
            $image = "";
//        if($static_map == 1 && $return_image == 'yes' )
//           {
            // will attach google key at run time, because key may change at any time
            $image = "https://maps.googleapis.com/maps/api/staticmap?center=&maptype=roadmap&path=color:0x000000%7Cweight:10%7Cenc:" . urlencode($points) . "&markers=color:green%7Clabel:P%7C" . $startpoint . "&markers=color:red%7Clabel:D%7C" . $finishpoint;
//           }
            return ['total_distance' => $distanceSmall, 'total_distance_text' => $total_distance_text, 'total_time' => $timeSmall, 'total_time_minutes' => $total_time_minutes, 'total_time_text' => $total_time_text, 'image' => $image,'poly_points'=>$points];
        }catch (\Exception $e)
        {
            throw new \Exception($e->getMessage());
        }

    }

    public static function GoogleStaticNoDropImage($startpoint = null, $key = null)
    {
        $image = "http://maps.googleapis.com/maps/api/staticmap?maptype=roadmap&markers=color:red%7Clabel:P%7C" . $startpoint . "&key=" . $key;
        return ['total_distance' => 0, 'total_distance_text' => 0, 'total_time' => 0, 'total_time_minutes' => 0, 'total_time_text' => '0', 'image' => $image];
    }

    public static function GoogleShortestPathDistance($from, $to, $key, $units = 'metric',$calling_from= "",$string_file ="")
    {
        try{
            $url  = 'https://maps.googleapis.com/maps/api/directions/json?units=' . $units . '&origin=' . $from . '&destination=' . $to . '&alternatives=true&key=' . $key;
            $log_data = [
                'request_type'=>'Direction Api',
                'data'=>$url,
                'additional_notes'=>'Direction Api for Image(GoogleShortestPathDistance)('.$calling_from.')',
            ];
            google_api_log($log_data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $data = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($data);
            $status = $data->status;
            if ($status != "OK") {
                $message = !empty($data->error_message) ?  $data->error_message : trans("$string_file.google_key_not_working");
                throw new \Exception($message);
            }
            $routes = $data->routes;
            if(!empty($routes))
            {
                usort($routes, function($a, $b){ return intval($a->legs[0]->distance->value) - intval($b->legs[0]->distance->value); } );
                return $dist_inval = $routes[0]->legs[0]->distance->value;
            }
        }catch (\Exception $e)
        {
          throw new \Exception($e->getMessage());
        }
        return NULL;
    }

    public static function GoogleDistanceAndTime($from, $to, $key, $units = 'metric',$with_poly_points = false,$calling_fom ='',$string_file ="")
    {
        try{
            $url  = "https://maps.googleapis.com/maps/api/directions/json?units=" . $units . "&origin=$from&destination=$to&mode=driving&key=$key";
            $log_data = [
                'request_type'=>'Direction Api',
                'data'=>$url,
                'additional_notes'=>'Direction Api for Image(GoogleDistanceAndTime)('.$calling_fom.')',
            ];
            google_api_log($log_data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $data = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($data, true);
            $status = $data['status'];
            if ($status != "OK") {
                $message = !empty($data['error_message']) ?  $data['error_message'] : trans("$string_file.google_key_not_working");
                throw new \Exception($message);
//                return array('time' => "", 'distance' => "","poly_point"=>"");
            } else {
                $time = $data['routes'][0]['legs'][0]['duration']['text'];
                $time_in_min = $data['routes'][0]['legs'][0]['duration']['value'];
                $distance = $data['routes'][0]['legs'][0]['distance']['text'];
                $distance_in_meter = $data['routes'][0]['legs'][0]['distance']['value'];
                $return_data = array('time' => $time,'time_in_min'=>$time_in_min, 'distance' => $distance,"distance_in_meter"=>$distance_in_meter);
                if($with_poly_points == true)
                {
                    $points = $data['routes'][0]['overview_polyline']['points'];
                    $return_data['poly_point'] = $points;
                }
                return $return_data;
            }

        }catch (\Exception $e)
        {
            throw new \Exception($e->getMessage());
        }
    }

    public static function GoogleLocation($latitude, $longitude, $key,$calling_from ='',$string_file ="")
    {
        try{
            if (!empty($latitude) && !empty($longitude)) {
                $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . trim($latitude) . "," . trim($longitude) . "&key=" . $key;
                $log_data = [
                    'request_type'=>'GeoCode Api Google Controller',
                    'data'=>$url,
                    'additional_notes'=>'Geocode Api for address('.$calling_from.')',
                ];
                google_api_log($log_data);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_URL, $url);
                $result = curl_exec($ch);
                curl_close($ch);
                $output = json_decode($result);
                $status = $output->status;
                if ($status != "OK") {
                    $message = !empty($output->error_message) ?  $output->error_message : trans("$string_file.google_key_not_working");
                    throw new \Exception($message);
                }
                $status = $output->status;
                if($calling_from == "OUTSTATION_CITY")
                {
                    $address = isset($output->results[0]->address_components[4]) ? $output->results[0]->address_components[4]->long_name : $output->results[0]->formatted_address;
                }
                else
                {
                $address = isset($output->results[0]) ? $output->results[0]->formatted_address : '';
                }
                if (!empty($address)) {
                    return $address;
                } else {
                    return false;
                }
            } else {
                return false;
            }

        }catch (\Exception $e)
        {
          throw new \Exception($e->getMessage());
        }
    }

    public static function PolyLine($from, $to, $key,$calling_from="")
    {

        $url = "https://maps.googleapis.com/maps/api/directions/json?origin=$from&destination=$to&mode=driving&key=$key";
        $log_data = [
            'request_type'=>'Direction Api',
            'data'=>$url,
            'additional_notes'=>'Direction Api for Image(PolyLine)('.$calling_from.')',
        ];
        google_api_log($log_data);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Postman-Token: 47565396-00a5-4dc2-9511-2b04eadc3be6",
                "cache-control: no-cache"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        //        $data = file_get_contents("https://maps.googleapis.com/maps/api/directions/json?origin=$from&destination=$to&mode=driving&key=$key");
        $data = json_decode($response, true);
        $points = $data['routes'][0]['overview_polyline']['points'];

        // return empty waypoints if google through error
        if($data['status']== "NOT_FOUND")
        {
            return $points = '';
        }
        return $points;
    }

    public function SnapToRoad($newArray, $key)
    {
        if (!is_array($newArray) || empty($newArray)) {
                $newArray = [];
        }
        $totalLatLong = count($newArray);
        $new_array = array();
        if ($totalLatLong > 95) {
            $average = ceil($totalLatLong / 95);
            for ($i = 0; $i < $totalLatLong; $i = $i + $average) {
                $lat = $newArray[$i]['latitude'];
                $long = $newArray[$i]['longitude'];
                $new_array[] = $lat . "," . $long;
            }
            $path = implode("|", $new_array);
        } else {
            foreach ($newArray as $value) {
                $lat = $value['latitude'];
                $long = $value['longitude'];
                $new_array[] = $lat . "," . $long;
            }
            $path = implode("|", $new_array);
        }
        $url = 'https://roads.googleapis.com/v1/snapToRoads?path=' . $path . '&interpolate=true&key=' . $key;
        $log_data = [
            'request_type'=>'Direction Api',
            'data'=>$url,
            'additional_notes'=>'Direction Api for Image(SnapToRoad)',
        ];
        google_api_log($log_data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $geocode = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($geocode);
        if (!empty($output) && empty($output->error)) {
            $combine = array();
            foreach ($output->snappedPoints as $login) {
                $latitude = $login->location->latitude;
                $longitude = $login->location->longitude;
                $combine[] = $latitude . "," . $longitude;
            }
            return $combine;
        } else {
            return false;
        }
    }

    public function WayPointDistance($snapToRoad, $key, $units = 'metric')
    {
        $start = array_shift($snapToRoad);
        $finish = array_pop($snapToRoad);
        $finish = $finish ? $finish : $start;
        $count_snapToRoad = count($snapToRoad);
        if ($count_snapToRoad > 23) {
            $average_way = ceil($count_snapToRoad / 22);
            $new_array1 = array();
            for ($j = 0; $j < $count_snapToRoad; $j = $j + $average_way) {
                $lat_long = $snapToRoad[$j];
                $new_array1[] = $lat_long;
            }
            $waypoints = implode("|", $new_array1);
        } else {
            $waypoints = implode("|", $snapToRoad);
        }
        $url = 'https://maps.googleapis.com/maps/api/directions/json?units=' . $units . '&origin=' . $start . '&destination=' . $finish . '&waypoints=' . $waypoints . '&key=' . $key;
        $log_data = [
            'request_type'=>'Direction Api',
            'data'=>$url,
            'additional_notes'=>'Direction Api for Image(WayPointDistance)',
        ];
        google_api_log($log_data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);

        $output = json_decode($data);
        $distance = $output->routes[0]->legs;
//        foreach ($distance as $value) {
//            $array[] = [
//                'start' => $value->start_location->lat . "," . $value->start_location->lng,
//                'end' => $value->end_location->lat . "," . $value->end_location->lng,
//            ];
//        }
//        echo "<pre>";
//        print_r($array);
//        die();
        $a = array();
        foreach ($distance as $location) {
            $dist = $location->distance->value;
            $a[] = $dist;
        }
        $re = array_sum($a);
        return $re;
    }

    function mapLoad(Request $request)
    {
        $startpoint = $request->start_point;
        $finishpoint = $request->final_point;
        $waypoints = $request->way_point;
        $merchant_id = $request->merchant_id;
        $key = get_merchant_google_key($merchant_id,'api');

        $url = 'https://maps.googleapis.com/maps/api/directions/json?origin=' . $startpoint . '&destination=' . $finishpoint . '&mode=driving&waypoints=' . $waypoints . '&key=' . $key;
        $log_data = [
            'request_type'=>'Direction Api',
            'data'=>$url,
            'additional_notes'=>'Direction Api for Image(WayPointDistance)(mapLoad fun)',
        ];
        google_api_log($log_data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($data, true);
        return response()->json($data);
    }

    function staticMapLoad(Request $request)
    {
        $startpoint = $request->start_point;
        $finishpoint = $request->final_point;
        $merchant_id = $request->merchant_id;
        $key = get_merchant_google_key($merchant_id,'api');

        $data = self::GooglestaticsinglePointImage($startpoint, $finishpoint, $key, 'metric');
        $log_data = [
            'request_type'=>'Direction Api',
            'data'=>$data,
            'additional_notes'=>'Direction Api for static map image',
        ];
        google_api_log($log_data);
        return response()->json($data);
    }

    // arial distance b/w 2 points
    function arialDistance($lat1, $lon1, $lat2, $lon2, $unit = NULL,$string_file ="",$unit_mandatory = true, $distance_range = false) {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        // $unit = strtoupper($unit);

        if ($unit == 1) {
            $return_distance =  ($miles * 1.609344);
        }
        else {
            $return_distance = $miles;
        }
        
        if($distance_range){
            return $this->getArialDistanceRange($return_distance, $unit, $string_file);
        }
        
        $unit_lang = ($unit == 2 ? trans("$string_file.miles") : trans("$string_file.km"));
        $return_distance = round($return_distance,2);
        if($unit_mandatory == false)
        {
         return $return_distance;
        }
        $return_distance = $return_distance.' '.$unit_lang;
        return $return_distance;
    }
    
    

    function getArialDistanceRange($distance, $unit, $string_file="")
    {
        if (empty($distance)) {
            return 0;
        }
        $distance = ceil($distance / 2) * 2;
    
        if ($unit == 2) {
            return trans("$string_file.upto")." ".round($distance,2)." ". trans("$string_file.miles");  
        } else {
            return trans("$string_file.upto")." ".round($distance,2). trans("$string_file.km");
        }
    }


    /**
     * @throws \Exception
     */
    public static function computeRoutesForPolyline($merchant_id, $pickup_location, $drop_location, $key = NULL){
        $polyline = "";
        try{
            if(!empty($key)) {
                $data = array(
                    "origin" => array(
                        "address" => $pickup_location
                    ),
                    "destination" => array(
                        "address" => $drop_location
                    ),
                    "travelMode" => "DRIVE"
                );

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://routes.googleapis.com/directions/v2:computeRoutes',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        'X-Goog-Api-Key: ' . $key,
                        'X-Goog-FieldMask: routes.duration,routes.distanceMeters,routes.polyline,routes.legs.polyline,routes.legs.steps.polyline'
                    ),
                ));
                $resp = curl_exec($curl);
                curl_close($curl);

                $log_data = [
                    'request_type' => 'Google computeRoutes Api',
                    'merchant_id' => $merchant_id,
                    'data' => $resp,
                    'additional_notes' => 'computeRoutes for recalculation of polyline to update end location map (admin panel)',
                ];
                google_api_log($log_data);

                $response = json_decode($resp);
                if (isset($response->routes)) {
                    $polyline = $response->routes[0]->polyline->encodedPolyline;
                }
            }
        }
        catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }
        return $polyline;
    }



    public static function GoogleDistanceMatrix($coord_string, $dest, $key, $units = 'metric', $calling_fom ='')
    {
        try{
            $url  = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . $coord_string . "&destinations=$dest&mode=driving&key=$key";
            $log_data = [
                'request_type'=>'Direction Matrix Api',
                'data'=>$url,
                'additional_notes'=>'Direction Matrix Api for ('.$calling_fom.')',
            ];
            google_api_log($log_data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $data = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($data, true);
            $status = $data['status'];
            if ($status != "OK") {
                return null;
            } else {
                return $data;
            }

        }
        catch (\Exception $e)
        {
            throw new \Exception($e->getMessage());
        }
    }


    public static function GoogleShortestPathWithWaypointDistance($start, $finish, $key, $units = 'metric', $waypoints  = null, $calling_from = null){
        try{

            $multiple_waypoints = [];
            if(!empty($waypoints)){
                $wps = json_decode($waypoints, true);
            }
            foreach($wps as  $waypoint){
                $lat_long = $waypoint['drop_latitude'] . ',' . $waypoint['drop_longitude'];
                $multiple_waypoints[] = $lat_long;
            }
            $waypoints = implode("|", $multiple_waypoints);

            $url = "https://maps.googleapis.com/maps/api/directions/json?units=$units&origin=".urlencode($start)."&destination=".urlencode($finish)."&waypoints=".urlencode($waypoints)."&mode=driving&key=".$key;
            $log_data = [
                'request_type'=>'directions',
                'data'=>$url,
                'additional_notes'=>'GoogleShortestPathWithWaypointDistance for ('.$calling_from.')',
            ];
             google_api_log($log_data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $data = curl_exec($ch);
            curl_close($ch);

            $output = json_decode($data);
            $distance = $output->routes[0]->legs;

            $a = array();
            foreach ($distance as $location) {
                $dist = $location->distance->value;
                $a[] = $dist;
            }
            $re = array_sum($a);
            return $re;
        }
        catch (\Exception $e)
        {
            throw new \Exception($e->getMessage());
        }
    }
}
