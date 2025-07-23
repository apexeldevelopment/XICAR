<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MapBoxController extends Controller
{
    //
    public static function MapBoxStaticImageAndDistance($pickuplat = null, $pickuplong = null, array $drop = null, $key = null, $units = 'metric', $string_file = "")
    {
        try {
            $start = $pickuplong . ',' . $pickuplat;
            $units = !empty($units) ? $units : 'metric';
            if (!empty($drop)):
                $count_combine = count($drop);
                if ($count_combine > 1):
                    $end = array_shift($drop);

                    $first = $end['drop_longitude'] . ',' . $end['drop_latitude'];
                    $last = "";
                    $count_waypoints = count($drop);

                    $all_coordinates = [];
                    $all_coordinates[] = $pickuplong . ',' . $pickuplat;
                    $all_coordinates[] = $first; // First drop

                    if ($count_waypoints > 0): // If more waypoints
                        foreach ($drop as $d) {
                            $coord = $d['drop_longitude'] . ',' . $d['drop_latitude'];
                            $last = $end['drop_longitude'] . ',' . $end['drop_latitude'];
                            $all_coordinates[] = $coord;
                        }
                    endif;
                    $coordinates = implode(';', $all_coordinates);
                    return self::MapboxStaticMultiplePointsImage($coordinates, $first, $last, $key, $string_file);
                else:   // IF SINGLE DROP
                    $end = array_pop($drop);
                    $finish = $end['drop_longitude'] . ',' . $end['drop_latitude'];
                    return self::MapBoxstaticsinglePointImage($start, $finish, $key, $units, 2, 'no', $string_file);
                endif;
            else:   // IF NO DROP LOCATION RECEIVED
                return self::MapBoxStaticNoDropImage($start, $key);
            endif;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function MapBoxstaticsinglePointImage($startpoint = null, $finishpoint = null, $key = null, $units = null, $string_file = "")
    {
        try {
            // $url = 'https://api.mapbox.com/directions/v5/mapbox/driving/' . $startpoint.';' .$finishpoint . '?access_token=' . $key."&geometries=geojson";
            $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$startpoint};{$finishpoint}?access_token={$key}";
            $log_data = [
                'request_type' => 'Direction Api (MAPBOX)',
                'data' => $url,
                'additional_notes' => 'Direction Api for Image(MapBoxstaticsinglePointImage)',
            ];
            map_box_api_log($log_data);

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
                    // "Postman-Token: 5bf321ea-a304-47c9-82e9-deef8014cffc",
                    // "cache-control: no-cache"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            $data = json_decode($response, true);
            $code = $data['code'] ?? null;
            if ($code != "Ok") {
                $message = !empty($data['error_message']) ? $data['error_message'] : trans("$string_file.mapbox_key_not_working");
                throw new \Exception($message);
            }

            $duration_seconds = $data['routes'][0]['legs'][0]['duration'];
            $total_time = $duration_seconds;
            $total_time_minutes = round($duration_seconds / 60, 2);
            $total_time_text = gmdate("i", $duration_seconds) . " mins";

            $distance_meters = $data['routes'][0]['legs'][0]['distance'];
            $total_distance = round($distance_meters / 1000, 2);
            $total_distance_text = $total_distance . " km";
            $points = $data['routes'][0]['geometry'];
//            $decoded = self::decodePolyline($points);
            $image = self::generateMapboxStaticImage($startpoint, $finishpoint, $points);

            return ['total_distance' => $distance_meters, 'total_distance_text' => $total_distance_text, 'total_time' => $total_time, 'total_time_minutes' => $total_time_minutes, 'total_time_text' => $total_time_text, 'image' => $image, 'poly_points' => $points];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }


    public static function MapBoxStaticMultiplePointsImage($coordinates = null, $first = null, $last = null, $key = null, $string_file = "")
    {
        try {
            $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$coordinates}?access_token={$key}";

            $log_data = [
                'request_type' => 'Direction Api (MAPBOX)',
                'data' => $url,
                'additional_notes' => 'Direction Api for Multiple Points (MapBox)',
            ];
            map_box_api_log($log_data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            $code = $data['code'] ?? null;

            if ($code !== "Ok") {
                $message = !empty($data['message']) ? $data['message'] : trans("$string_file.mapbox_key_not_working");
                throw new \Exception($message);
            }

            $total_distance = 0;
            $total_time = 0;

            foreach ($data['routes'][0]['legs'] as $leg) {
                $total_distance += $leg['distance'];
                $total_time += $leg['duration'];
            }

            $total_distance_text = round($total_distance / 1000, 2) . ' km';
            $total_time_minutes = round($total_time / 60, 2);
            $total_time_text = $total_time_minutes . ' mins';
            if ($total_time_minutes > 60) {
                $total_time_text = round($total_time_minutes / 60, 2) . ' hr';
            }

            $points = $data['routes'][0]['geometry'];
//            $decoded = self::decodePolyline($points);
            $image = self::generateMapboxStaticImage($first, $last, $points);


            return [
                'total_distance' => $total_distance,
                'total_distance_text' => $total_distance_text,
                'total_time' => $total_time,
                'total_time_minutes' => $total_time_minutes,
                'total_time_text' => $total_time_text,
                'image' => $image,
                'poly_points' => $points,
            ];

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    public static function MapBoxDistanceMatrix($coord_string, $dest, $key, $units = 'metric', $calling_fom = '')
    {

        try {
            $url = "https://api.mapbox.com/directions-matrix/v1/mapbox/driving/$coord_string;$dest?access_token=$key";
            $log_data = [
                'request_type' => 'Direction Matrix Api (MAP_BOX)',
                'data' => $url,
                'additional_notes' => 'Direction Matrix Api for MAP_BOX(' . $calling_fom . ')',
            ];
            map_box_api_log($log_data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $data = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($data, true);
            $status = $data['code'];
            if ($status != "Ok") {
                return null;
            } else {
                return $data;
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    public static function MapBoxStaticNoDropImage($startpoint = null, $key = null)
    {
        $marker = "pin-s-p+ff0000({$startpoint})";
        $zoom = 14;
        $width = 600;
        $height = 400;

        $image = "https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/{$marker}/{$startpoint},{$zoom}/{$width}x{$height}?access_token={$key}";
        return ['total_distance' => 0, 'total_distance_text' => 0, 'total_time' => 0, 'total_time_minutes' => 0, 'total_time_text' => '0', 'image' => $image];

    }


    public static function MapBoxDistanceAndTime($from, $to, $key, $units = 'metric', $with_poly_points = false, $calling_fom = '', $string_file = "")
    {
        try {
            $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$from};{$to}?access_token={$key}";
            $log_data = [
                'request_type' => 'Direction Api',
                'data' => $url,
                'additional_notes' => 'Direction Api for Image(MapBoxDistanceAndTime)(' . $calling_fom . ')',
            ];
            map_box_api_log($log_data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $data = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($data, true);
            $status = $data['code'];
            if ($status != "Ok") {
                $message = !empty($data['error_message']) ? $data['error_message'] : trans("$string_file.mapbox_key_not_working");
                throw new \Exception($message);
            } else {
                $duration_seconds = $data['routes'][0]['legs'][0]['duration'];
                $time = $duration_seconds;
                $time = round($duration_seconds / 60, 2);
                $time_in_min = gmdate("i", $duration_seconds) . " mins";

                $distance_meters = $data['routes'][0]['legs'][0]['distance'];
                $distance = round($distance_meters / 1000, 2);
                $distance_in_meter = $distance . " km";

                $return_data = array('time' => $time, 'time_in_min' => $time_in_min, 'distance' => $distance, "distance_in_meter" => $distance_in_meter);
                if ($with_poly_points == true) {
                    $points = $data['routes'][0]['geometry'];
                    $return_data['poly_point'] = $points;
                }
                return $return_data;
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    public static function MapboxLocation($latitude, $longitude, $key, $calling_from = '', $string_file = "")
    {
        try {
            if (!empty($latitude) && !empty($longitude)) {
                $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/" . trim($longitude) . "," . trim($latitude) . ".json?access_token=" . $key;
                $log_data = [
                    'request_type' => 'GeoCode Api Mapbox Controller',
                    'data' => $url,
                    'additional_notes' => 'Geocode Api for address (' . $calling_from . ')',
                ];
                map_box_api_log($log_data);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_URL, $url);
                $result = curl_exec($ch);
                curl_close($ch);

                $output = json_decode($result, true);
                if (isset($output['message'])) {
                    throw new \Exception($output['message']);
                }

                if (empty($output['features'])) {
                    $message = trans("$string_file.mapbox_key_not_working");
                    throw new \Exception($message);
                }

                if ($calling_from == "OUTSTATION_CITY") {
                    $address = $output['features'][0]['text'] ?? $output['features'][0]['place_name'];
                } else {
                    $address = $output['features'][0]['place_name'] ?? '';
                }

                return !empty($address) ? $address : false;

            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    public static function MapboxShortestPathDistance($from, $to, $key, $calling_from = "", $string_file = "")
    {
        try {

            $url = "https://api.mapbox.com/directions/v5/mapbox/driving/" . trim($from) . ";" . trim($to) .
                "?access_token=" . $key;

            $log_data = [
                'request_type' => 'Mapbox Directions API',
                'data' => $url,
                'additional_notes' => 'Directions API for shortest path distance (' . $calling_from . ')',
            ];
            map_box_api_log($log_data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $result = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($result);

            if (isset($data->message)) {
                throw new \Exception($data->message);
            }

            if ($data->code !== "Ok") {
                $message = !empty($data->message) ? $data->message : trans("$string_file.mapbox_key_not_working");
                throw new \Exception($message);
            }

            if (!empty($data->routes)) {
                $routes = $data->routes;
                usort($routes, function ($a, $b) {
                    return $a->distance - $b->distance;
                });
                return $routes[0]->distance;
            }

            return null;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    public static function decodePolyline($encoded)
    {
        $len = strlen($encoded);
        $index = 0;
        $points = [];
        $lat = 0;
        $lng = 0;

        while ($index < $len) {
            $shift = 0;
            $result = 0;
            do {
                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1F) << $shift;
                $shift += 5;
            } while ($b >= 0x20);
            $deltaLat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lat += $deltaLat;

            $shift = 0;
            $result = 0;
            do {
                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1F) << $shift;
                $shift += 5;
            } while ($b >= 0x20);
            $deltaLng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lng += $deltaLng;

            $points[] = [$lat / 1e5, $lng / 1e5];
        }

        return $points;
    }

//
//    public static function generateMapboxStaticImage($startpoint, $finishpoint, $decoded, $key)
//    {
//        $pathCoords = [];
//        foreach ($decoded as $point) {
//            $pathCoords[] = "{$point[1]},{$point[0]}";
//        }
//        $path = 'path-5+0000ff-1(' . implode(',', $pathCoords) . ')';
//
//        // Center the image on the midpoint of the route
//        $midpoint = $decoded[intval(count($decoded) / 2)];
//        $center = "{$midpoint[1]},{$midpoint[0]}"; // lng,lat
//
//        // Add start and end markers
//        $startMarker = "pin-s-a+00ff00({$decoded[0][1]},{$decoded[0][0]})";
//        $endMarker = "pin-s-b+ff0000({$decoded[count($decoded)-1][1]},{$decoded[count($decoded)-1][0]})";
//
//        $imageUrl = "https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/{$path},{$startMarker},{$endMarker}/{$center},9,0/600x400?access_token={$key}";
//
//        return $imageUrl;
//    }



    /**
     * Create a Mapbox Static‑Images URL that shows
     *   – a blue 5‑px path (polyline overlay, still URL‑encoded)
     *   – green “A” pin at $first  [lat, lng]
     *   – red   “B” pin at $last   [lat, lng]
     *   – auto‑fitted viewport     (so nothing is cropped)
     *
     * @param array  $first   [lat, lng]  start point
     * @param array  $last    [lat, lng]  finish point
     * @param string $poly    Encoded polyline from Directions API
     * @param string $token   Public Mapbox access token
     * @param int    $w       Image width  (default 600 px)
     * @param int    $h       Image height (default 400 px)
     * @return string         Ready‑to‑fetch PNG URL
     */
    public static function generateMapboxStaticImage(
        array  $first,
        array  $last,
        string $poly,
        int    $w = 600,
        int    $h = 400
    ): string {

        /* 1 ────── path overlay ────────────────
           Encoded polyline goes straight into the
           path overlay (precision‑5 by default). */
        $path = 'path-5+0000ff-1(' . rawurlencode($poly) . ')';

        /* 2 ────── markers ─────────────────────
           Remember: longitude,latitude order.    */
        $start = sprintf('pin-s-a+00ff00(%F,%F)', $first[1], $first[0]);
        $end   = sprintf('pin-s-b+ff0000(%F,%F)', $last[1],  $last[0]);

        /* 3 ────── assemble URL ────────────────
           “/auto/” makes Mapbox pick the centre
           & zoom so the route and both pins fit. */
        return sprintf(
            'https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/%s,%s,%s/auto/%dx%d@2x?access_token=',
            $path,
            $start,
            $end,
            $w,
            $h,
        );
    }


}
