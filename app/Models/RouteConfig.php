<?php
//
//namespace App\Models;
//
//use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
//use App;
//
//class RouteConfig extends Model
//{
//    use HasFactory;
//    protected $hidden = array('pivot', 'LanguageSingle', 'LanguageAny');
//    protected $guarded = [];
//
//    public function LanguageAny()
//    {
//        return $this->hasOne(LanguageRouteConfig::class);
//    }
//
//    public function LanguageSingle()
//    {
//        return $this->hasOne(LanguageRouteConfig::class)->where([['locale', '=', App::getLocale()]]);
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
//    // public function getBusRouteAttribute()
//    // {
//    //     if (empty($this->LanguageSingle)) {
//    //         return $this->LanguageAny();
//    //     }
//    //     return $this->LanguageSingle();
//    // }
//
//    public function Merchant()
//    {
//        return $this->belongsTo(Merchant::class);
//    }
//
//    public function CountryArea()
//    {
//        return $this->belongsTo(CountryArea::class);
//    }
//    public function VehicleType()
//    {
//        return $this->belongsTo(VehicleType::class);
//    }
//    // public function StartPoint()
//    // {
//    //     return $this->belongsTo(BusStop::class,'start_point');
//    // }
//    public function BusRoute()
//    {
//        return $this->belongsTo(BusRoute::class,'bus_route_id');
//    }
//    public function StopPointsTime()
//    {
//        return $this->belongsToMany(BusStop::class,'route_configs_bus_stops','route_config_id')->withPivot('time');
//    }
//
//    public function LanguageRouteConfig()
//    {
//        return $this->hasMany(LanguageRouteConfig::class);
//    }
//}
