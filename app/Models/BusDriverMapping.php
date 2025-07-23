<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;
use DB;

class BusDriverMapping extends Model
{
    use HasFactory;
    protected $hidden = array('pivot', 'LanguageSingle', 'LanguageAny');
    protected $guarded = [];



//    // public function getNameAttribute()
//    // {
//    //     if (empty($this->LanguageSingle)) {
//    //         return $this->LanguageAny->name;
//    //     }
//    //     return $this->LanguageSingle->name;
//    // }
//
//
    public function Bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function BusRoute()
    {
        return $this->belongsTo(BusRoute::class);
    }

    public function Driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function ServiceTimeSlotDetail()
    {
        return $this->belongsTo(ServiceTimeSlotDetail::class);
    }
//
//
//    public function getNearestRoute($request,$user)
//    {
//        try {
//            $radius = 6371;
//            $merchant_id = $user->merchant_id;
//            $latitude = $request->latitude;
//            $drop_latitude = $request->drop_latitude;
//            $longitude = $request->longitude;
//            $drop_longitude = $request->drop_longitude;
//            $locale = App::getLocale();
//
//                 $query = BusRoute::select("bus_routes.*", "start_point.latitude AS start_latitude", "start_point.longitude AS start_longitude",  "end_point.latitude AS end_latitude", "end_point.longitude AS end_longitude")
//                ->addSelect(DB::raw('( ' . $radius . ' * acos(cos(radians('.$latitude.')) * cos(radians(start_point.latitude)) * cos( radians(start_point.longitude) -  radians('.$longitude.') ) + sin(radians('.$latitude.')) * sin(radians(start_point.latitude)))) AS pickup_distance'))
//                ->addSelect(DB::raw('( ' . $radius . ' * acos(cos(radians('.$drop_latitude.')) * cos(radians(end_point.latitude)) * cos( radians(end_point.longitude) - radians('.$drop_longitude.') ) + sin(radians('.$drop_latitude.')) *sin(radians(end_point.latitude)))) AS drop_distance'))
//                ->join('bus_routes_bus_stops AS route_stop_point', 'route_start_point.route_id', '=', 'routes.id')
//                ->join('bus_stops AS start_point', 'route_stop_point.stop_point_id', '=', 'start_point.id')
//                ->join('bus_stops AS end_point', 'route_stop_point.end_stop_point_id', '=', 'end_point.id')
//                 ->with(['LanguageBusRoute' => function ($q) use ($merchant_id, $locale) {
//                $q->where("locale", $locale)
//                    ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
//            }])
//                // ->crossJoin('(SELECT ' . $latitude . ' AS latitude,' . $longitude . ' AS longitude) AS `pickup`')
//                // ->crossJoin('(SELECT ' . $drop_latitude . ' AS latitude,' . $drop_longitude . ' AS longitude) AS `drop`')
//                ->orderBy('pickup_distance')
//                ->orderBy('drop_distance')
//                ->get();
//                return $query;
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
}
