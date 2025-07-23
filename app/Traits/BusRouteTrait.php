<?php

namespace App\Traits;

use App\Models\BusRoute;
use Auth;
use App;

trait BusRouteTrait
{
    public function getRoutes($merchant_id, $country_area_id = null)
    {
        $arr_routes = BusRoute::where([['merchant_id', '=', $merchant_id], ['status', '=', 1]])->where(function ($q) use ($country_area_id) {
            if (!empty($country_area_id)) {
                $q->where("country_area_id", $country_area_id);
            }
        })->get();
        $bus_routes = [];
        foreach ($arr_routes as $stop) {
            $bus_routes[$stop->id] = $stop->Name;
        }
        return $bus_routes;
    }


    function formatRouteStops($route_details)
    {
        // p($route_details);
        // $startPoint = $route_details->startPoint;
        // $start_point = [
        //     'id' => $startPoint->id,
        //     'name' => isset($startPoint->LanguageBusStop[0]) ? $startPoint->LanguageBusStop[0]->name : "",
        //     'is_it_pickup_point' => false,
        //     'is_it_drop_point' => false,
        //     'stop_latitude' => $startPoint->latitude,
        //     'stop_longitude' => $startPoint->longitude,
        // ];
        $arr_stop_points = $route_details->map(function ($stop_point) {
            return [
                'id' => $stop_point->id,
                'name' => isset($stop_point->LanguageBusStop[0]) ? $stop_point->LanguageBusStop[0]->name : "",
                'is_it_pickup_point' => false,
                'is_it_drop_point' => false,
                'stop_latitude' => $stop_point->latitude,
                'stop_longitude' => $stop_point->longitude,
            ];
        })->toArray();

        // $endPoint = $route_details->EndPoint;
        // $end_point = [
        //     'id' => $endPoint->id,
        //     'name' => isset($endPoint->LanguageBusStop[0]) ? $startPoint->LanguageBusStop[0]->name : "",
        //     'is_it_pickup_point' => false,
        //     'is_it_drop_point' => false,
        //     'stop_latitude' => $endPoint->latitude,
        //     'stop_longitude' => $endPoint->longitude,
        // ];

        // array_unshift($arr_stop_points, $start_point);
        // array_push($arr_stop_points, $end_point);

        return [
            // 'id' => $route_details->id,
            'stop_points' => $arr_stop_points
        ];
    }

}
