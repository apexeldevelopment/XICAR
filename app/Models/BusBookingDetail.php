<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;
use DB;

class BusBookingDetail extends Model
{
//    use HasFactory;
//    protected $hidden = array('pivot', 'LanguageSingle', 'LanguageAny');
    protected $guarded = [];
//
//    public function LanguageAny()
//    {
//        return $this->hasOne(LanguageBusRoute::class);
//    }
//
//    public function LanguageSingle()
//    {
//        return $this->hasOne(LanguageBusRoute::class)->where([['locale', '=', App::getLocale()]]);
//    }
//
//    // public function getNameAttribute()
//    // {
//    //     if (empty($this->LanguageSingle)) {
//    //         return $this->LanguageAny->name;
//    //     }
//    //     return $this->LanguageSingle->name;
//    // }
//
//    public function getBusRouteAttribute()
//    {
//        if (empty($this->LanguageSingle)) {
//            return $this->LanguageAny();
//        }
//        return $this->LanguageSingle();
//    }
//
    public function BusBooking()
    {
        return $this->belongsTo(BusBooking::class);
    }

    public function BusSeatDetail()
    {
        return $this->belongsTo(BusSeatDetail::class);
    }

//
//    public function CountryArea()
//    {
//        return $this->belongsTo(CountryArea::class);
//    }
//    public function StartPoint()
//    {
//        return $this->belongsTo(BusStop::class,'start_point');
//    }
//    public function EndPoint()
//    {
//        return $this->belongsTo(BusStop::class,'end_point');
//    }
//    public function StopPoints()
//    {
//        return $this->belongsToMany(BusStop::class,'bus_routes_bus_stops','bus_route_id')->withPivot('sequence')->orderBy('sequence');
//    }
//    public function LanguageBusRoute()
//    {
//        return $this->hasMany(LanguageBusRoute::class);
//    }
//    public function busRoute($id)
//    {
//        return BusRoute::find($id);
//    }
//    public function Segment()
//    {
//        return $this->belongsTo(Segment::class);
//    }
//
//    // public function getNearestRoute($request)
//    // {
//    //     try{
//    //         $radius = 5;
//    //         $latitude = $request->latitude;
//    //         $longitude = $request->longitude;
//    //          $query = BusRoute::select("*")
//    //         ->join('bus_routes_bus_stops as brbs', 'bus_routes.id', '=', 'brbs.bus_route_id')
//    //         ->join('bus_routes_bus_stops as brbs2', 'brbs.id', '=', 'brbs2.id')
//    //         ->join('bus_stops as bs', 'brbs.bus_stop_id', '=', 'bs.id')
//    //         ->addSelect(DB::raw('( ' . $radius . ' * acos( cos( radians(' . $latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) ) AS pick_top'))
//    //         // ->where('country_area_id', $request->area_id)
//    //         ->where('brbs.sequence', '<>', 'brbs2.sequence')
//    //         ->get();
//    //         p($query);
//    //     }catch(\Exception $e){
//    //         throw new \Exception($e->getMessage());
//    //     }
//
//
//    // }
//
//    // public function getNearestRoute($request, $user)
//    // {
//    //     try {
//    //         $radius = 10;
//    //         $earth_radius = 6371;
//    //         $merchant_id = $user->merchant_id;
//    //         $latitude = $request->latitude;
//    //         $drop_latitude = $request->drop_latitude;
//    //         $longitude = $request->longitude;
//    //         $drop_longitude = $request->drop_longitude;
//    //         $locale = App::getLocale();
//    //         // DISTINCT
//    //         $query = BusRoute::select("bus_routes.*", "start_point.latitude AS start_latitude", "start_point.longitude AS start_longitude",  "end_point.latitude AS end_latitude", "end_point.longitude AS end_longitude")
//    //             ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $latitude . ')) * cos(radians(start_point.latitude)) * cos( radians(start_point.longitude) -  radians(' . $longitude . ') ) + sin(radians(' . $latitude . ')) * sin(radians(start_point.latitude)))) AS pickup_distance'))
//    //             ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $drop_latitude . ')) * cos(radians(end_point.latitude)) * cos( radians(end_point.longitude) - radians(' . $drop_longitude . ') ) + sin(radians(' . $drop_latitude . ')) *sin(radians(end_point.latitude)))) AS drop_distance'))
//    //             ->join('bus_routes_bus_stops AS route_stop_point', 'route_stop_point.bus_route_id', '=', 'bus_routes.id')
//    //             ->join('bus_stops AS start_point', 'route_stop_point.bus_stop_id', '=', 'start_point.id')
//    //             ->join('bus_stops AS end_point', 'route_stop_point.end_bus_stop_id', '=', 'end_point.id')
//    //             ->with(['LanguageBusRoute' => function ($q) use ($merchant_id, $locale) {
//    //                 $q->where("locale", $locale)
//    //                     ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
//    //             }])
//    //             ->groupBy('route_stop_point.route_id')
//    //             ->having('pickup_distance','<',$radius)
//    //             ->orHaving('drop_distance','<',$radius)
//    //             ->orderBy('pickup_distance')
//    //             ->orderBy('drop_distance')
//    //             ->get();
//    //         return $query;
//    //         // p($query);
//    //         // $query = DB::select("SELECT  r.id AS route_id, r.start_point AS start_point_id, start_point.latitude AS start_latitude, start_point.longitude AS start_longitude, r.end_point AS end_point_id, end_point.latitude AS end_latitude, end_point.longitude AS end_longitude,
//    //         //     (
//    //         //         6371 *
//    //         //         acos(
//    //         //             cos(radians(pickup.latitude)) *
//    //         //             cos(radians(start_point.latitude)) *
//    //         //             cos(
//    //         //                 radians(start_point.longitude) -
//    //         //                 radians(pickup.longitude)
//    //         //             ) +
//    //         //             sin(radians(pickup.latitude)) *
//    //         //             sin(radians(start_point.latitude))
//    //         //         )
//    //         //     ) AS pickup_distance,
//    //         //     (
//    //         //         6371 *
//    //         //         acos(
//    //         //             cos(radians(drop.latitude)) *
//    //         //             cos(radians(end_point.latitude)) *
//    //         //             cos(
//    //         //                 radians(end_point.longitude) -
//    //         //                 radians(drop.longitude)
//    //         //             ) +
//    //         //             sin(radians(drop.latitude)) *
//    //         //             sin(radians(end_point.latitude))
//    //         //         )
//    //         //     ) AS drop_distance
//    //         // FROM
//    //         //     bus_routes AS r
//    //         // INNER JOIN
//    //         //     bus_stops AS start_point ON r.start_point = start_point.id
//    //         // INNER JOIN
//    //         //     bus_stops AS end_point ON r.end_point = end_point.id
//    //         // CROSS JOIN
//    //         //     (SELECT
//    //         //         26.2843 AS latitude,
//    //         //         73.0164 AS longitude
//    //         //     ) AS `pickup`
//    //         // CROSS JOIN
//    //         //     (SELECT
//    //         //         28.3070693 AS latitude,
//    //         //         76.6510198 AS longitude
//    //         //     ) AS `drop`
//
//    //         // ORDER BY
//    //         //     pickup_distance, drop_distance");
//
//
//
//    //     } catch (\Exception $e) {
//    //         throw new \Exception($e->getMessage());
//    //     }
//    // }
//
//     public function getNearestRoute($request, $user)
//    {
//        try {
//            $radius = 10;
//            $earth_radius = 6371;
//            $merchant_id = $user->merchant_id;
//            $latitude = $request->latitude;
//            $drop_latitude = $request->drop_latitude;
//            $longitude = $request->longitude;
//            $drop_longitude = $request->drop_longitude;
//            $locale = App::getLocale();
//            // DISTINCT
//            $query = BusRoute::select("bus_routes.*", "start_point.latitude AS start_latitude", "start_point.longitude AS start_longitude",  "end_point.latitude AS end_latitude", "end_point.longitude AS end_longitude")
//                ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $latitude . ')) * cos(radians(start_point.latitude)) * cos( radians(start_point.longitude) -  radians(' . $longitude . ') ) + sin(radians(' . $latitude . ')) * sin(radians(start_point.latitude)))) AS pickup_distance'))
//                ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $drop_latitude . ')) * cos(radians(end_point.latitude)) * cos( radians(end_point.longitude) - radians(' . $drop_longitude . ') ) + sin(radians(' . $drop_latitude . ')) *sin(radians(end_point.latitude)))) AS drop_distance'))
//                ->join('bus_routes_bus_stops AS route_stop_point', 'route_stop_point.bus_route_id', '=', 'bus_routes.id')
//                ->join('bus_stops AS start_point', 'route_stop_point.bus_stop_id', '=', 'start_point.id')
//                ->join('bus_stops AS end_point', 'route_stop_point.bus_stop_id', '=', 'end_point.id')
//                ->with(['LanguageBusRoute' => function ($q) use ($merchant_id, $locale) {
//                    $q->where("locale", $locale)
//                        ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
//                }])
//                ->groupBy('route_stop_point.bus_route_id')
//                ->having('pickup_distance','<',$radius)
//                ->orHaving('drop_distance','<',$radius)
//                ->orderBy('pickup_distance')
//                ->orderBy('drop_distance')
//                ->get();
//            return $query;
//            // p($query);
//            // $query = DB::select("SELECT  r.id AS route_id, r.start_point AS start_point_id, start_point.latitude AS start_latitude, start_point.longitude AS start_longitude, r.end_point AS end_point_id, end_point.latitude AS end_latitude, end_point.longitude AS end_longitude,
//            //     (
//            //         6371 *
//            //         acos(
//            //             cos(radians(pickup.latitude)) *
//            //             cos(radians(start_point.latitude)) *
//            //             cos(
//            //                 radians(start_point.longitude) -
//            //                 radians(pickup.longitude)
//            //             ) +
//            //             sin(radians(pickup.latitude)) *
//            //             sin(radians(start_point.latitude))
//            //         )
//            //     ) AS pickup_distance,
//            //     (
//            //         6371 *
//            //         acos(
//            //             cos(radians(drop.latitude)) *
//            //             cos(radians(end_point.latitude)) *
//            //             cos(
//            //                 radians(end_point.longitude) -
//            //                 radians(drop.longitude)
//            //             ) +
//            //             sin(radians(drop.latitude)) *
//            //             sin(radians(end_point.latitude))
//            //         )
//            //     ) AS drop_distance
//            // FROM
//            //     bus_routes AS r
//            // INNER JOIN
//            //     bus_stops AS start_point ON r.start_point = start_point.id
//            // INNER JOIN
//            //     bus_stops AS end_point ON r.end_point = end_point.id
//            // CROSS JOIN
//            //     (SELECT
//            //         26.2843 AS latitude,
//            //         73.0164 AS longitude
//            //     ) AS `pickup`
//            // CROSS JOIN
//            //     (SELECT
//            //         28.3070693 AS latitude,
//            //         76.6510198 AS longitude
//            //     ) AS `drop`
//
//            // ORDER BY
//            //     pickup_distance, drop_distance");
//
//
//
//        } catch (\Exception $e) {
//            throw new \Exception($e->getMessage());
//        }
//    }
//
//
//     // get route and their stop points
//    //   public function getRoute($request)
//    // {
//    //     try {
//    //         $route_id = $request->route_id;
//    //         $merchant_id = $request->merchant_id;
//    //         $locale = App::getLocale();
//    //         $query = BusRoute::where([['id', '=', $request->route_id], ['status', '=', 1]])
//    //             ->with(['LanguageBusRoute' => function ($q) use ($merchant_id, $locale) {
//    //                 $q->where("locale", $locale)
//    //                     ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
//    //             }])
//    //             ->with(['StopPoints' => function ($q) use ($route_id) {
//    //                 $q->where("bus_route_id", $route_id);
//    //             }])
//    //             ->whereHas('StopPoints', function ($q) use ($route_id) {
//    //                 $q->where("bus_route_id", $route_id);
//    //             });
//
//    //         $route = $query->first();
//    //         $route->StopPoints = $route->StopPoints->where('id', '!=', $route->start_point);
//    //         return $route;
//    //     } catch (\Throwable $th) {
//    //         throw $th->getMessage();
//    //     }
//    // }
//
//     public function getRoute($request,$user)
//    {
//        try {
//            $route_id = $request->route_id;
//            $merchant_id = $request->merchant_id;
//            $locale = App::getLocale();
//
//            // $radius = 10;
//            $earth_radius = 6371;
//            $merchant_id = $user->merchant_id;
//            $latitude = $request->latitude;
//            $drop_latitude = $request->drop_latitude;
//            $longitude = $request->longitude;
//            $drop_longitude = $request->drop_longitude;
//
//            // $query = BusRoute::where([['id', '=', $request->route_id], ['status', '=', 1]])
//            //     ->with(['LanguageBusRoute' => function ($q) use ($merchant_id, $locale) {
//            //         $q->where("locale", $locale)
//            //             ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
//            //     }])
//            //     ->with(['StopPoints' => function ($q) use ($route_id) {
//            //         $q->where("bus_route_id", $route_id);
//            //     }])
//            //     ->whereHas('StopPoints', function ($q) use ($route_id) {
//            //         $q->where("bus_route_id", $route_id);
//            //     });
//
//            // $route = $query->first();
//            // $route->StopPoints = $route->StopPoints->where('id', '!=', $route->start_point);
//            // return $route;
//
//
//
//            $query = BusStop::select("bus_stops.id","bus_stops.latitude AS start_latitude", "bus_stops.longitude AS start_longitude",  "end_point.latitude AS end_latitude", "end_point.longitude AS end_longitude","route_stop_point.bus_route_id","route_stop_point.sequence")
//                ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $latitude . ')) * cos(radians(bus_stops.latitude)) * cos( radians(bus_stops.longitude) -  radians(' . $longitude . ') ) + sin(radians(' . $latitude . ')) * sin(radians(bus_stops.latitude)))) AS pickup_distance'))
//                ->addSelect(DB::raw('( ' . $earth_radius . ' * acos(cos(radians(' . $drop_latitude . ')) * cos(radians(end_point.latitude)) * cos( radians(end_point.longitude) - radians(' . $drop_longitude . ') ) + sin(radians(' . $drop_latitude . ')) *sin(radians(end_point.latitude)))) AS drop_distance'))
//                // ->addSelect(DB::raw('(CASE WHEN pickup_distance = MIN(pickup_distance) THEN true ELSE false END )AS stop_match'))
//                ->join('bus_routes_bus_stops AS route_stop_point', 'route_stop_point.bus_stop_id', '=', 'bus_stops.id')
//                ->join('bus_stops AS end_point', 'route_stop_point.bus_stop_id', '=', 'end_point.id')
//
//
//                ->with(['LanguageBusStop' => function ($q) use ($merchant_id, $locale) {
//                    $q->where("locale", $locale)
//                        ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
//                }])
//                // ->groupBy('route_stop_point.bus_route_id')
//                // ->having('pickup_distance','<',$radius)
//                // ->orHaving('drop_distance','<',$radius)
//                // ->orderBy('pickup_distance')
//                // ->orderBy('pickup_distance')
//                ->orderBy('route_stop_point.sequence')
//                ->orderBy('drop_distance')
//                ->where('route_stop_point.bus_route_id',$route_id);
//
//                $route = $query->get();
//                // p($route->toArray());
//            // $route->StopPoints = $route->StopPoints->where('id', '!=', $route->start_point);
//            return $route;
//
//                // p($route);
//        } catch (\Throwable $th) {
//            throw $th;
//        }
//    }

}
