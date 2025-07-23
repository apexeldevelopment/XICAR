<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;

class BusPriceCard extends Model
{
    use HasFactory;
    protected $hidden = array('pivot', 'LanguageSingle', 'LanguageAny');
    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(LanguageRouteConfig::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageRouteConfig::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }

    public function VehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

//    public function RouteConfig()
//    {
//        return $this->belongsTo(RouteConfig::class, 'route_config_id');
//    }

    public function BusRoute()
    {
        return $this->belongsTo(BusRoute::class);
    }

    public function LanguageRouteConfig()
    {
        return $this->hasMany(LanguageRouteConfig::class);
    }

    public function StopPointsPrice()
    {
        return $this->belongsToMany(BusStop::class, 'bus_price_card_bus_stops', 'bus_price_card_id')->withPivot("price");
    }
}
