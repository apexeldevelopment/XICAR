<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;
use DB;

class BusRoute extends Model
{
    use HasFactory;
    protected $hidden = array('pivot', 'LanguageSingle', 'LanguageAny');
    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(LanguageBusRoute::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageBusRoute::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getNameAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->title;
        }
        return $this->LanguageSingle->title;
    }

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }

    public function StartPoint()
    {
        return $this->belongsTo(BusStop::class, 'start_point');
    }

    public function EndPoint()
    {
        return $this->belongsTo(BusStop::class, 'end_point');
    }

    public function StopPoints()
    {
        return $this->belongsToMany(BusStop::class, 'bus_routes_bus_stops', 'bus_route_id')->withPivot('sequence', 'time')->orderBy('sequence');
    }

    public function LanguageBusRoute()
    {
        return $this->hasMany(LanguageBusRoute::class);
    }

    public function busRoute($id)
    {
        return BusRoute::find($id);
    }

    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function ServiceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    // public function getNearestRoute($request)
    // {
    //     try{
    //         $radius = 5;
    //         $latitude = $request->latitude;
    //         $longitude = $request->longitude;
    //          $query = BusRoute::select("*")
    //         ->join('bus_routes_bus_stops as brbs', 'bus_routes.id', '=', 'brbs.bus_route_id')
    //         ->join('bus_routes_bus_stops as brbs2', 'brbs.id', '=', 'brbs2.id')
    //         ->join('bus_stops as bs', 'brbs.bus_stop_id', '=', 'bs.id')
    //         ->addSelect(DB::raw('( ' . $radius . ' * acos( cos( radians(' . $latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) ) AS pick_top'))
    //         // ->where('country_area_id', $request->area_id)
    //         ->where('brbs.sequence', '<>', 'brbs2.sequence')
    //         ->get();
    //         p($query);
    //     }catch(\Exception $e){
    //         throw new \Exception($e->getMessage());
    //     }


    // }

    // public function getNearestRoute($request, $user)
    // {
    //     try {
    //         $radius = 10;
    //         $earth_radius = 6371;
    //         $merchant_id = $user->merchant_id;
    //         $latitude = $request->latitude;
    //         $drop_latitude = $request->drop_latitude;
    //         $longitude = $request->longitude;
    //         $drop_longitude = $request->drop_longitude;
    //         $locale = App::getLocale();
    //         // DISTINCT
    //         $query = BusRoute::select("bus_routes.*", "start_point.latitude AS start_latitude", "start_point.longitude AS start_longitude",  "end_point.latitude AS end_latitude", "end_point.longitude AS end_longitude")
    //             ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $latitude . ')) * cos(radians(start_point.latitude)) * cos( radians(start_point.longitude) -  radians(' . $longitude . ') ) + sin(radians(' . $latitude . ')) * sin(radians(start_point.latitude)))) AS pickup_distance'))
    //             ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $drop_latitude . ')) * cos(radians(end_point.latitude)) * cos( radians(end_point.longitude) - radians(' . $drop_longitude . ') ) + sin(radians(' . $drop_latitude . ')) *sin(radians(end_point.latitude)))) AS drop_distance'))
    //             ->join('bus_routes_bus_stops AS route_stop_point', 'route_stop_point.bus_route_id', '=', 'bus_routes.id')
    //             ->join('bus_stops AS start_point', 'route_stop_point.bus_stop_id', '=', 'start_point.id')
    //             ->join('bus_stops AS end_point', 'route_stop_point.end_bus_stop_id', '=', 'end_point.id')
    //             ->with(['LanguageBusRoute' => function ($q) use ($merchant_id, $locale) {
    //                 $q->where("locale", $locale)
    //                     ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
    //             }])
    //             ->groupBy('route_stop_point.route_id')
    //             ->having('pickup_distance','<',$radius)
    //             ->orHaving('drop_distance','<',$radius)
    //             ->orderBy('pickup_distance')
    //             ->orderBy('drop_distance')
    //             ->get();
    //         return $query;
    //         // p($query);
    //         // $query = DB::select("SELECT  r.id AS route_id, r.start_point AS start_point_id, start_point.latitude AS start_latitude, start_point.longitude AS start_longitude, r.end_point AS end_point_id, end_point.latitude AS end_latitude, end_point.longitude AS end_longitude,
    //         //     (
    //         //         6371 *
    //         //         acos(
    //         //             cos(radians(pickup.latitude)) *
    //         //             cos(radians(start_point.latitude)) *
    //         //             cos(
    //         //                 radians(start_point.longitude) -
    //         //                 radians(pickup.longitude)
    //         //             ) +
    //         //             sin(radians(pickup.latitude)) *
    //         //             sin(radians(start_point.latitude))
    //         //         )
    //         //     ) AS pickup_distance,
    //         //     (
    //         //         6371 *
    //         //         acos(
    //         //             cos(radians(drop.latitude)) *
    //         //             cos(radians(end_point.latitude)) *
    //         //             cos(
    //         //                 radians(end_point.longitude) -
    //         //                 radians(drop.longitude)
    //         //             ) +
    //         //             sin(radians(drop.latitude)) *
    //         //             sin(radians(end_point.latitude))
    //         //         )
    //         //     ) AS drop_distance
    //         // FROM
    //         //     bus_routes AS r
    //         // INNER JOIN
    //         //     bus_stops AS start_point ON r.start_point = start_point.id
    //         // INNER JOIN
    //         //     bus_stops AS end_point ON r.end_point = end_point.id
    //         // CROSS JOIN
    //         //     (SELECT
    //         //         26.2843 AS latitude,
    //         //         73.0164 AS longitude
    //         //     ) AS `pickup`
    //         // CROSS JOIN
    //         //     (SELECT
    //         //         28.3070693 AS latitude,
    //         //         76.6510198 AS longitude
    //         //     ) AS `drop`

    //         // ORDER BY
    //         //     pickup_distance, drop_distance");


    //     } catch (\Exception $e) {
    //         throw new \Exception($e->getMessage());
    //     }
    // }

    public function getNearestRoute($request, $user)
    {
        try {
            $radius = 10;
            $earth_radius = $user->Country->distance_unit == 2 ? 3958.756 : 6371;
            $distance_text = $user->Country->distance_unit == 2 ? "m" : "km";
            if (isset($request->service_types_type)) {
                if ($request->service_types_type == 1) {
                    $radius = 2;
                }
            }
            $merchant_id = $user->merchant_id;
            $latitude = $request->latitude;
            $drop_latitude = $request->drop_latitude;
            $longitude = $request->longitude;
            $drop_longitude = $request->drop_longitude;
            $service_type_id = $request->service_type_id;
            $segment_id = $request->segment_id;
            $locale = App::getLocale();
            // DISTINCT
            $query = BusRoute::select("bus_routes.*", "start_point.latitude AS start_latitude", "start_point.longitude AS start_longitude",  "end_point.latitude AS end_latitude", "end_point.longitude AS end_longitude")
                ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $latitude . ')) * cos(radians(start_point.latitude)) * cos( radians(start_point.longitude) -  radians(' . $longitude . ') ) + sin(radians(' . $latitude . ')) * sin(radians(start_point.latitude)))) AS pickup_distance'))
                ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $drop_latitude . ')) * cos(radians(end_point.latitude)) * cos( radians(end_point.longitude) - radians(' . $drop_longitude . ') ) + sin(radians(' . $drop_latitude . ')) *sin(radians(end_point.latitude)))) AS drop_distance'))
                ->join('bus_routes_bus_stops AS route_stop_point', 'route_stop_point.bus_route_id', '=', 'bus_routes.id')
                ->join('bus_stops AS start_point', 'route_stop_point.bus_stop_id', '=', 'start_point.id')
                ->join('bus_stops AS end_point', 'route_stop_point.bus_stop_id', '=', 'end_point.id')
                ->with(['LanguageBusRoute' => function ($q) use ($merchant_id, $locale) {
                    $q->where("locale", $locale)
                        ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
                }])
                ->where("bus_routes.merchant_id",'=',$merchant_id)
                ->where("bus_routes.segment_id",'=',$segment_id)
                ->where("bus_routes.service_type_id",'=',$service_type_id)
                ->groupBy('route_stop_point.bus_route_id')
                ->having('pickup_distance','<',$radius)
                ->orHaving('drop_distance','<',$radius)
                ->orderBy('pickup_distance')
                ->orderBy('drop_distance')
                ->get();
//            p($query->toArray());
            return $query;
            // $query = DB::select("SELECT  r.id AS route_id, r.start_point AS start_point_id, start_point.latitude AS start_latitude, start_point.longitude AS start_longitude, r.end_point AS end_point_id, end_point.latitude AS end_latitude, end_point.longitude AS end_longitude,
            //     (
            //         6371 *
            //         acos(
            //             cos(radians(pickup.latitude)) *
            //             cos(radians(start_point.latitude)) *
            //             cos(
            //                 radians(start_point.longitude) -
            //                 radians(pickup.longitude)
            //             ) +
            //             sin(radians(pickup.latitude)) *
            //             sin(radians(start_point.latitude))
            //         )
            //     ) AS pickup_distance,
            //     (
            //         6371 *
            //         acos(
            //             cos(radians(drop.latitude)) *
            //             cos(radians(end_point.latitude)) *
            //             cos(
            //                 radians(end_point.longitude) -
            //                 radians(drop.longitude)
            //             ) +
            //             sin(radians(drop.latitude)) *
            //             sin(radians(end_point.latitude))
            //         )
            //     ) AS drop_distance
            // FROM
            //     bus_routes AS r
            // INNER JOIN
            //     bus_stops AS start_point ON r.start_point = start_point.id
            // INNER JOIN
            //     bus_stops AS end_point ON r.end_point = end_point.id
            // CROSS JOIN
            //     (SELECT
            //         26.2843 AS latitude,
            //         73.0164 AS longitude
            //     ) AS `pickup`
            // CROSS JOIN
            //     (SELECT
            //         28.3070693 AS latitude,
            //         76.6510198 AS longitude
            //     ) AS `drop`

            // ORDER BY
            //     pickup_distance, drop_distance");


        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getNewNearestRoute($request, $user)
    {
        try {
            $radius = 10;
            $earth_radius = $user->Country->distance_unit == 2 ? 3958.756 : 6371;
            $distance_text = $user->Country->distance_unit == 2 ? "m" : "km";
            if (isset($request->service_types_type)) {
                if ($request->service_types_type == 1) {
                    $radius = 3;
                    if($user->Merchant->demo == 1){
                        $radius = 150;
                    }
                }
            }
            $merchant_id = $user->merchant_id;
            $latitude = $request->latitude;
            $drop_latitude = $request->drop_latitude;
            $longitude = $request->longitude;
            $drop_longitude = $request->drop_longitude;
            $service_type_id = $request->service_type_id;
            $segment_id = $request->segment_id;
            $get_details = isset($request->get_details) ? $request->get_details : false;
            $bus_route_id = isset($request->bus_route_id) ? $request->bus_route_id : null;
//            $locale = App::getLocale();

            $query = DB::table("bus_routes_bus_stops as brbs")
                ->select("brbs.end_bus_stop_id", "brbs.bus_route_id", "brbs.sequence")
                ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $drop_latitude . ')) * cos(radians(end_point.latitude)) * cos( radians(end_point.longitude) - radians(' . $drop_longitude . ') ) + sin(radians(' . $drop_latitude . ')) *sin(radians(end_point.latitude)))) AS drop_distance'))
                ->join('bus_stops as end_point', 'brbs.end_bus_stop_id', '=', 'end_point.id')
                ->where("end_point.merchant_id", '=', $merchant_id)
                ->where("end_point.segment_id", '=', $segment_id)
                ->where("end_point.service_type_id", '=', $service_type_id);
            if(!empty($bus_route_id)){
                $query->orWhere("brbs.bus_route_id", $bus_route_id);
            }
            $nearest_end_points = $query->having('drop_distance', '<', $radius)
                ->orderBy('drop_distance')
                ->get();
            // p($nearest_end_points);

            $nearest_route_ids = $nearest_end_points->unique("bus_route_id")->pluck("bus_route_id")->toArray();
            // p($nearest_route_ids);
            $nearest_end_points = $nearest_end_points->toArray();
            $nearest_end_points = json_decode(json_encode($nearest_end_points), true);

            $nearest_start_points = DB::table("bus_routes_bus_stops as brbs")
                ->select("brbs.bus_stop_id", "brbs.bus_route_id", "brbs.sequence","brbs.end_bus_stop_id")
                ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $latitude . ')) * cos(radians(start_point.latitude)) * cos( radians(start_point.longitude) -  radians(' . $longitude . ') ) + sin(radians(' . $latitude . ')) * sin(radians(start_point.latitude)))) AS pickup_distance'))
                ->join('bus_stops AS start_point', 'brbs.bus_stop_id', '=', 'start_point.id')
                ->where("start_point.merchant_id", '=', $merchant_id)
                ->where("start_point.segment_id", '=', $segment_id)
                ->where("start_point.service_type_id", '=', $service_type_id)
                ->whereIn("bus_route_id", $nearest_route_ids)
                // ->having('pickup_distance', '<', $radius)
                ->orderBy('pickup_distance')
                ->groupBy('bus_route_id')
                ->get()->toArray();
            // p($nearest_start_points);
            $nearest_start_points = json_decode(json_encode($nearest_start_points), true);
            $new_nearest_start_points = [];
            foreach ($nearest_start_points as $nearest_start_point) {
                $key = array_search($nearest_start_point['bus_route_id'], array_column($nearest_end_points, 'bus_route_id'));
                // if($nearest_start_point['bus_route_id']==22){
                    // p($nearest_start_point['end_bus_stop_id'].'   '.$nearest_end_points[$key]['end_bus_stop_id']);
                    // p($nearest_end_points[$key]['sequence'].'  '.$nearest_start_point['sequence']);
                // }
                // if($nearest_end_points[$key]['sequence']>$nearest_start_point['sequence'])
                {
                    $nearest_start_point['pickup_distance'] = round_number($nearest_start_point['pickup_distance']) . " " . $distance_text;
                    $nearest_start_point['drop_distance'] = round_number($nearest_end_points[$key]['drop_distance']) . " " . $distance_text;
                    $nearest_start_point['end_bus_stop_id'] = $nearest_end_points[$key]['end_bus_stop_id'];
    
                    $nearest_start_point['route_title'] = BusRoute::find($nearest_start_point['bus_route_id'])->Name;
                    $nearest_start_point['pickup_stop_point'] = BusStop::find($nearest_start_point['bus_stop_id'])->Name;
                    $nearest_start_point['drop_stop_point'] = BusStop::find($nearest_start_point['end_bus_stop_id'])->Name;
                    if($get_details){
                        $points = DB::table("bus_routes_bus_stops")->select("bus_stop_id","end_bus_stop_id","time","sequence")
                            ->where("bus_route_id", $nearest_start_point['bus_route_id'])
                            ->where(function($q) use($nearest_start_point){
                                $q->where("bus_stop_id", $nearest_start_point['bus_stop_id'])
                                    ->orWhere("end_bus_stop_id", $nearest_start_point['end_bus_stop_id']);
                            })->get()->toArray();
                        $points = json_decode(json_encode($points), true);
    //                    p($points);
    //                    $nearest_start_point['travel_time'] = 0;
                        $nearest_start_point['number_of_buses'] = 0;
    //                    $nearest_start_point['route_points'] = [];///$points;
                        $nearest_start_point['time_duration'] = "09:00-18:00";
                        
                    }    
                    $new_nearest_start_points[] = $nearest_start_point;
                }
            }
            // p($new_nearest_start_points);
            // return $nearest_start_points;
            return $new_nearest_start_points;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    // get route and their stop points
    //   public function getRoute($request)
    // {
    //     try {
    //         $route_id = $request->route_id;
    //         $merchant_id = $request->merchant_id;
    //         $locale = App::getLocale();
    //         $query = BusRoute::where([['id', '=', $request->route_id], ['status', '=', 1]])
    //             ->with(['LanguageBusRoute' => function ($q) use ($merchant_id, $locale) {
    //                 $q->where("locale", $locale)
    //                     ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
    //             }])
    //             ->with(['StopPoints' => function ($q) use ($route_id) {
    //                 $q->where("bus_route_id", $route_id);
    //             }])
    //             ->whereHas('StopPoints', function ($q) use ($route_id) {
    //                 $q->where("bus_route_id", $route_id);
    //             });

    //         $route = $query->first();
    //         $route->StopPoints = $route->StopPoints->where('id', '!=', $route->start_point);
    //         return $route;
    //     } catch (\Throwable $th) {
    //         throw $th->getMessage();
    //     }
    // }

    public function getRoute($request, $user)
    {
        try {
            $locale = App::getLocale();
//            $radius = 10;
            $earth_radius = $user->Country->distance_unit == 2 ? 3958.756 : 6371;
//            $distance_text = $user->Country->distance_unit == 2 ? "m" : "km";
//            if (isset($request->service_types_type)) {
//                if ($request->service_types_type == 1) {
//                    $radius = 2;
//                }
//            }

            $merchant_id = $user->merchant_id;
            $latitude = $request->latitude;
            $drop_latitude = $request->drop_latitude;
            $longitude = $request->longitude;
            $drop_longitude = $request->drop_longitude;

            return BusStop::select("bus_stops.id", "bus_stops.latitude AS start_latitude", "bus_stops.longitude AS start_longitude", "end_point.latitude AS end_latitude", "end_point.longitude AS end_longitude", "route_stop_point.bus_route_id", "route_stop_point.sequence")
                ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $latitude . ')) * cos(radians(bus_stops.latitude)) * cos( radians(bus_stops.longitude) -  radians(' . $longitude . ') ) + sin(radians(' . $latitude . ')) * sin(radians(bus_stops.latitude)))) AS pickup_distance'))
                ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $drop_latitude . ')) * cos(radians(end_point.latitude)) * cos( radians(end_point.longitude) - radians(' . $drop_longitude . ') ) + sin(radians(' . $drop_latitude . ')) *sin(radians(end_point.latitude)))) AS drop_distance'))
                ->join('bus_routes_bus_stops AS route_stop_point', 'route_stop_point.bus_stop_id', '=', 'bus_stops.id')
                ->join('bus_stops AS end_point', 'route_stop_point.bus_stop_id', '=', 'end_point.id')
                ->with(['LanguageBusStop' => function ($q) use ($merchant_id, $locale) {
                    $q->where("locale", $locale)
                        ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
                }])
                ->orderBy('route_stop_point.sequence')
                ->orderBy('drop_distance')
                ->where('route_stop_point.bus_route_id', $request->bus_route_id)->get();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getBusStopList()
    {
        $stops = "";
        $stops .= isset($this->StartPoint->LanguageSingle)? $this->StartPoint->LanguageSingle->name . " | ": $this->StartPoint->LanguageAny->name." | ";
        if ($this->StopPoints->count() > 1) {
            $mid_stops = $this->StopPoints->slice(1);
            foreach ($mid_stops as $stop_point) {
                $stops .= isset($stop_point->LanguageSingle)? $stop_point->LanguageSingle->name . " | " : $stop_point->LanguageAny->name." | ";
            }
        }
        $stops .= isset($this->EndPoint->LanguageSingle)? $this->EndPoint->LanguageSingle->name :  $this->EndPoint->LanguageAny->name;
        return $stops;
    }

//    public function BusRouteSchedule(){
//        return $this->hasMany(BusRouteSchedule::class);
//    }


    public function BusBookingMaster()
    {
        return $this->hasMany(BusBookingMaster::class);
    }
}
