<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;
use DB;

class BusBookingCheckout extends Model
{
//    use HasFactory;
//    protected $hidden = array('pivot', 'LanguageSingle', 'LanguageAny');
    protected $guarded = [];

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function BusStop()
    {
        return $this->belongsTo(BusStop::class, "bus_stop_id");
    }

    public function EndBusStop()
    {
        return $this->belongsTo(BusStop::class, "end_bus_stop_id");
    }

    public function PickupPoint()
    {
        return $this->belongsTo(BusPickupDropPoint::class, "pickup_point_id");
    }

    public function DropPoint()
    {
        return $this->belongsTo(BusPickupDropPoint::class, "drop_point_id");
    }

    public function ServiceTimeSlotDetail()
    {
        return $this->belongsTo(ServiceTimeSlotDetail::class);
    }

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }

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
//    public function Merchant()
//    {
//        return $this->belongsTo(Merchant::class);
//    }
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
//
//
}
