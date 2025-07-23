<?php

namespace App\Traits;

use App\Models\RouteConfig;
use Auth;
use App;

trait RouteConfigTrait
{
    /**
     * get Route Config List by area id
     */
    public function getRouteConfig($merchant_id, $area_id)
    {
        $locale = App::getLocale();
        $query = RouteConfig::select('id','bus_route_id')->where([['merchant_id', '=', $merchant_id], ['status', '=', 1], ['country_area_id', '=', $area_id]])
            ->with(['LanguageRouteConfig' => function ($q) use ($merchant_id, $locale) {
                $q->where("locale", $locale)
                    ->orWhere("locale", "en")->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '" . $locale . "' THEN 1 ELSE 2 END");
            }]);

        return $query->get();

        // $configs = [];
        // foreach ($arr_route_configs as $config) {
        //     $configs[$config->id] = isset($config->LanguageRouteConfig[0]) ? $config->LanguageRouteConfig[0]->title : "";
        // }
        // return $configs;
    }
}
