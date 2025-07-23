<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;

class BusStop extends Model
{
    use HasFactory;
    protected $hidden = array('pivot','LanguageSingle','LanguageAny');
    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(LanguageBusStop::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageBusStop::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getNameAttribute()
    {

        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->name;
        }
        return $this->LanguageSingle->name;
    }

    public function getBusStopAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny();
        }
        return $this->LanguageSingle();
    }

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function LanguageBusStop()
    {
        return $this->hasMany(LanguageBusStop::class);
    }
    public function BusRoutePoints()
    {
        return $this->belongsToMany(BusRoute::class,'bus_routes_bus_stops','bus_stop_id')->withPivot('end_bus_stop_id','time','sequence')->orderBy('sequence');
    }
    public function StopPointsConfig()
    {
        return $this->belongsToMany(RouteConfig::class,'route_configs_bus_stops','bus_stop_id')->withPivot('time');
    }

    public function StopPointsPrice()
    {
        return $this->belongsToMany(BusPriceCard::class,'bus_price_card_bus_stops','bus_stop_id')->withPivot('price');
    }

    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function ServiceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function BusPickupDropPoint(){
        return $this->belongsToMany(BusPickupDropPoint::class);
    }
}
