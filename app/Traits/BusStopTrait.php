<?php

namespace App\Traits;

use App\Models\BusStop;
use Auth;
use App;
use DB;

trait BusStopTrait
{
    // get all stops points
    public function getStopPoints($merchant_id, $route_id = null, $format = "drop_down", $service_type_id = null, $not_included_ids = array())
    {
        $locale = App::getLocale();
        $query = BusStop::where([['merchant_id', '=', $merchant_id], ['status', '=', 1]])
            ->with(['LanguageBusStop' => function ($q) use ($merchant_id, $locale) {
                $q->where("locale", $locale)
                    ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
            }]);
        if ($route_id) {
            $query->whereHas('BusRoutePoints', function ($q) use ($route_id) {
                $q->where("bus_route_id", $route_id)
                    ->orderBy('sequence');
            });
        }
        if(!empty($service_type_id)){
            $query->where("service_type_id", $service_type_id);
        }
        if(!empty($not_included_ids)){
            $query->whereNotIn("id", $not_included_ids);
        }
        $arr_stops = $query->get();

        $stop_points = [];
        foreach ($arr_stops as $key => $stop) {
            if ($format == "drop_down") {
                $stop_points[$stop->id] = isset($stop->LanguageBusStop[0]) ? $stop->LanguageBusStop[0]->name : "";
            } else {
                // echo $key;
                // fetching by RouteID
                // need to keep stops sequence in saved order
                $stop_points[] = [
                    'id' => $stop->id,
                    'name' => isset($stop->LanguageBusStop[0]) ? $stop->LanguageBusStop[0]->name : "",
                ];
            }
        }

        return $stop_points;
    }


    // only stop points except start and end point
    public function getOnlyStopPointsOfRoute($merchant_id, $route_id)
    {
        $locale = App::getLocale();
        $query = BusStop::where([['merchant_id', '=', $merchant_id], ['status', '=', 1]])
            ->with(['LanguageBusStop' => function ($q) use ($merchant_id, $locale) {
                $q->where("locale", $locale)
                    ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
            }]);

        $query->whereHas('BusRoutePoints', function ($q) use ($route_id) {
            $q->where("bus_route_id", $route_id);
        })
        ->where('id','!=',DB::raw("(select start_point from bus_routes where id=".$route_id.")"))
        ->where('id','!=',DB::raw("(select end_point from bus_routes where id=".$route_id.")"));
        $arr_stops = $query->get();

        $stop_points = [];
        foreach ($arr_stops as $key => $stop) {
            // fetching by RouteID
            // need to keep stops sequence in saved order
            $stop_points[] = [
                'id' => $stop->id,
                'name' => $stop->Name,
            ];
        }
        return $stop_points;
    }
}
