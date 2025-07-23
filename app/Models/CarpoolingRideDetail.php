<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Traits\CarpoolingTrait;

class CarpoolingRideDetail extends Model
{
    protected $guarded = [];

    public function CarpoolingRide()
    {
        return $this->belongsTo(CarpoolingRide::class);
    }

    public function CarpoolingRideUserDetail()
    {
        return $this->hasMany(CarpoolingRideUserDetail::class);
    }

    public static function searchCarpoolingRides($arr_reqest,$nearPickupLatLng,$nearDropLatLng,$stop_points_details,$route_notin_polyine)
    {
        
     
            $pickupLatLng = $nearPickupLatLng;
            $dropLatLng   = $nearDropLatLng;
            
            if(count($pickupLatLng)<=0 || count($dropLatLng)<=0)
            {
                return [];
            }
            // query parameters
            $carpooling_ride_id = isset($arr_reqest['carpooling_ride_id']) ? $arr_reqest['carpooling_ride_id'] : NULL;
            $pickup_id = isset($arr_reqest['pickup_id']) ? $arr_reqest['pickup_id'] : NULL;
            $drop_id = isset($arr_reqest['drop_id']) ? $arr_reqest['drop_id'] : NULL;
            $segment_id = isset($arr_reqest['segment_id']) ? $arr_reqest['segment_id'] : NULL;
            $limit = isset($arr_reqest['limit']) ? $arr_reqest['limit'] : 10;
            $country_area_id = isset($arr_reqest['area']) ? $arr_reqest['area'] : null;
            $merchant_id = isset($arr_reqest['merchant_id']) ? $arr_reqest['merchant_id'] : null; 
            $distance_unit = isset($arr_reqest['distance_unit']) ? $arr_reqest['distance_unit'] : '';
            $longitude = isset($arr_reqest['pickup_longitude']) ? $arr_reqest['pickup_longitude'] : null;
            //p($longitude);
            $latitude = isset($arr_reqest['pickup_latitude']) ? $arr_reqest['pickup_latitude'] : null;
           // p($latitude);
            $ac_nonac = isset($arr_reqest['ac_ride']) ? $arr_reqest['ac_ride'] : null;
            $riders_num = isset($arr_reqest['no_of_seats']) ? $arr_reqest['no_of_seats'] : null;
            $distance = isset($arr_reqest['distance']) ? $arr_reqest['distance'] : 4;
            $radius = $distance_unit == 2 ? 3958.756 : 6367;
            $distance_text = $distance_unit == 2 ? "m" : "km";
            $drop_lat = isset($arr_reqest['drop_latitude']) ? $arr_reqest['drop_latitude'] : null;
            // p( $drop_lat);
            $drop_long = isset($arr_reqest['drop_longitude']) ? $arr_reqest['drop_longitude'] : null;
            // p( $drop_long);
            $not_user_id = isset($arr_reqest['not_user_id']) ? $arr_reqest['not_user_id'] : null;
            $user_gender = isset($arr_reqest['user_gender']) ? $arr_reqest['user_gender'] : null;
            //$female_ride = isset($arr_reqest['female_ride']) ? $arr_reqest['female_ride'] : null;
            $payment_type = isset($arr_reqest['payment_type_id']) ? $arr_reqest['payment_type_id'] : null;
            $ride_time = isset($arr_reqest['ride_timestamp']) ? $arr_reqest['ride_timestamp'] : null;
            $before_Time =  $ride_time-86400;
            $after_Time = $ride_time + (86400*2);
            $timeBetween = array($before_Time, $after_Time);
            $date_time   = date('Y-m-d',$ride_time);
            $currency_symbol = isset($arr_reqest['currency_symbol']) ? $arr_reqest['currency_symbol'] : "UU";
            // dd($pickupLatLng,$dropLatLng,$drop_long,$drop_lat);
           $query_drop =  DB::table('carpooling_ride_details AS crd')
                        ->select([
                            'crd.id AS drop_id',
                            'crd.carpooling_ride_id',
                            DB::raw('(6371 * ACOS(COS(RADIANS(' . $dropLatLng['lat'] . ')) * COS(RADIANS('.$drop_lat.')) * COS(RADIANS(' . $dropLatLng['lng'] . ') - RADIANS('.$drop_long.')) + SIN(RADIANS(' . $dropLatLng['lat'] . ')) * SIN(RADIANS('.$drop_lat.')))) AS drop_distance')
                        ])
                        ->leftJoin('carpooling_rides AS cr', 'cr.id', '=', 'crd.carpooling_ride_id')
                        ->where('cr.segment_id', $segment_id)
                        ->where('cr.merchant_id', $merchant_id)
                        ->where('cr.ride_status', 1)
                        ->where('cr.user_id', '!=', $not_user_id)
                        ->where(DB::raw('DATE(crd.ride_datetime)'), '=', $date_time)
                        ->groupBy('drop_id')
                        ->havingRaw('drop_distance <= 20')
                        ->orderByDesc('drop_distance')
                        ->get();
                    
            $near_by_drop_ids = $query_drop->toArray();
            $query = DB::table('carpooling_rides') 
                    ->select(
                    'carpooling_ride_details.id as pickup_id',
                    'carpooling_rides.available_seats',
                    'carpooling_rides.booked_seats',
                    'carpooling_ride_details.carpooling_ride_id as carpooling_ride_id',
                    'carpooling_rides.user_id',
                    'carpooling_rides.female_ride',
                    'carpooling_rides.ac_ride',
                    DB::raw('(6371 * ACOS(COS(RADIANS('.$pickupLatLng['lat'].')) * COS(RADIANS('.$latitude.')) * COS(RADIANS('.$longitude.') - RADIANS('.$pickupLatLng['lng'].')) + SIN(RADIANS('.$pickupLatLng['lat'].')) * SIN(RADIANS('.$latitude.')))) AS pickup_distance'),
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) AS full_name'),
                    'carpooling_ride_details.is_return as return_ride'
                )
                ->join('carpooling_ride_details', 'carpooling_ride_details.carpooling_ride_id', '=', 'carpooling_rides.id')
                ->join('users', 'users.id', '=', 'carpooling_rides.user_id')
                ->join('user_vehicles', 'user_vehicles.user_id', '=', 'users.id')
                ->where('carpooling_rides.segment_id', $segment_id)
                ->where('carpooling_rides.merchant_id', $merchant_id)
                ->where('user_vehicles.vehicle_verification_status', 2)
                ->whereNull('users.user_delete')
                ->where('carpooling_rides.available_seats', '>=', $riders_num)
                ->where('carpooling_rides.ride_status', 1)
                ->where('carpooling_rides.user_id', '!=', $not_user_id)
                ->where(DB::raw('DATE(carpooling_ride_details.ride_datetime)'), '=', $date_time)
                ->groupBy('pickup_id')
                ->havingRaw('pickup_distance <= 20')
                // ->orderByDesc('carpooling_rides.id')
               ->take($limit);
        
            $near_by_pickups = $query->get();
            $near_by_drops = $near_by_drop_ids;
           
            $near_by_pickups = $near_by_pickups->toArray();
            $near_by_pickups = json_decode(json_encode($near_by_pickups), true);
         
            $count = 0;
            foreach ($route_notin_polyine as $key=>$val) {
               
                if (isset($near_by_pickups[$count]['pickup_id']) && $val != "match") {
                    unset($near_by_pickups[$count]);
                }
                $count++;
            }
            // dd($near_by_pickups);
            foreach ($near_by_pickups as &$near_by_pickup) {
                $key = array_search($near_by_pickup['carpooling_ride_id'], array_column($near_by_drops, 'carpooling_ride_id'));;
                $near_by_pickup['pickup_distance'] = round_number($near_by_pickup['pickup_distance']) . " " . $distance_text;
                $near_by_pickup['drop_distance'] = round_number($near_by_drops[$key]->drop_distance) . " " . $distance_text;
                $near_by_pickup['drop_id'] = $near_by_drops[$key]->drop_id;
                
            }
            $data = [];
            $count = 0;
            foreach ($near_by_pickups as &$near_by_pickup) 
            {
               
                $route = CarpoolingRideDetail::where(function ($query) use ($near_by_pickup) {
                    $query->whereBetween('id', [$near_by_pickup['pickup_id'], $near_by_pickup['drop_id']]);
                })->orderBy("drop_no");
          
                // $percentage = ($stop_points_details[$near_by_pickup['pickup_id']]['distancebtwlatlng']/$route->sum('estimate_distance'))*100;
                // $total_amount = ($route->sum('final_charges')/100)*number_format($percentage, 2);
                $distance = $stop_points_details[$near_by_pickup['carpooling_ride_id']]['distancebtwlatlng'];
                $estimate_distance = $route->sum('estimate_distance');
                $final_charges = $route->sum('final_charges');
               
                if (is_numeric($distance) && is_numeric($estimate_distance) && $estimate_distance != 0) {
                    $percentage = ($distance / $estimate_distance) * 100;
                    $total_amount = ($final_charges / 100) * number_format($percentage, 2);
                    
                } else {
                    // Handle the error case where non-numeric values are encountered
                    $percentage = 0;
                    $total_amount = 0;
                    // Optionally log an error or throw an exception
                    // error_log('Non-numeric value encountered in distance or estimate_distance');
                }
        
                                             
                // // Format the price as needed (optional)
                $formatted_price = number_format($total_amount, 2);
                $near_by_pickup['total_estimate_distance'] = round_number($route->sum('estimate_distance')) . ' ' . $distance_text;
                $near_by_pickup['total_charges'] = $currency_symbol . ' ' . $route->sum('final_charges')*$riders_num;
                $near_by_pickup['route_points'] = $stop_points_details[$near_by_pickup['carpooling_ride_id']];
                $near_by_pickup['total_charge_according_distance'] = $total_amount * intval($riders_num);

                //$near_by_pickup['total_charges'] = $currency_symbol . ' ' . $this->calculateSeatAmount($riders_num,$pickup_id);
                $user = User::find($near_by_pickup['user_id']);
                $route_path = $route->select("drop_no", "from_location", "to_location","estimate_distance_text", "ride_timestamp", "end_timestamp")->get()->toArray();
                $near_by_pickup['profile_image'] = get_image($user->UserProfileImage, 'user', $user->merchant_id);
                $no_drop_points = 0;
                
                if(!empty($route_path)){
                    if (count($route_path) > 1) {
                        $first_point = $route_path[0];
                        $last_point = $route_path[array_key_last($route_path)];
                        //p($last_point);
                        $no_drop_points = count($route_path) - 1;
                        $near_by_pickup['from_location'] = $first_point['from_location'];
                        $near_by_pickup['to_location'] = $last_point['to_location'];
                        $near_by_pickup['start_ride_timestamp'] = $first_point['ride_timestamp'];
                        $near_by_pickup['end_ride_timestamp'] = $last_point['end_timestamp'];
                    } else {
                        $first_point = $route_path[0];
                        $near_by_pickup['from_location'] = $first_point['from_location'];
                        $near_by_pickup['to_location'] = $first_point['to_location'];
                        $near_by_pickup['start_ride_timestamp'] = $first_point['ride_timestamp'];
                        $near_by_pickup['end_ride_timestamp'] = $first_point['end_timestamp'];
                    }    
                }else{
                    $route_path = [];
                }
                $near_by_pickup['route'] = $route_path;
                $near_by_pickup['no_drop_points'] = $no_drop_points;
            
              
                if (!empty($route_path)) {
                    array_push($data, $near_by_pickup);
                   //return $data;
                }
                $count++;
            }
            if (empty($data)) {
                return [];
            }
            return $data;
       
        
    }
       

    public function PriceCard()
    {
        return $this->belongsTo(PriceCard::class, 'price_card_id');
    }
}