<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $guarded = [];
//
//    protected $hidden = ['VehicleType', 'VehicleMake', 'VehicleModel', 'ServiceTypes','Driver','pivot'];
//
//    public function Driver()
//    {
//        return $this->belongsTo(Driver::class);
//    }
//
//    public function OwnerDriver()
//    {
//        return $this->belongsTo(Driver::class, 'owner_id');
//    }
//
//    public function OwnerDetails()
//    {
//        return $this->hasOne(VehicleOwnerDetail::class, 'driver_vehicle_id');
//    }
//
//    public function Drivers()
//    {
//        return $this->belongsToMany(Driver::class)->withPivot('driver_id','vehicle_active_status');
//    }
//
    public function ServiceTypes()
    {
        return $this->belongsToMany(ServiceType::class)->withPivot('segment_id');
    }

    public function VehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function VehicleMake()
    {
        return $this->belongsTo(VehicleMake::class);
    }

    public function VehicleModel()
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function BusDocument()
    {
        return $this->hasMany(BusDocument::class);
    }

    public function BusSeatDetail()
    {
        return $this->hasMany(BusSeatDetail::class);
    }
//
//    public function getDrivers()
//    {
//        return $this->belongsToMany(Driver::class);
//    }
    public function Merchant()
    {
        return $this->belongsTo(Driver::class);
    }
//
//    public function routeMapping()
//    {
//        return $this->hasMany(BusRouteMapping::class);
//    }
    /**
     * @Country Area relation
     */
    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }

    /*
      buses by area
     */
    public function arrBuses($area_id)
    {
        return  Bus::where([['country_area_id','=',$area_id],['vehicle_verification_status','=',2]])->get();
    }

    /*
      get bus Name
     */
    public function busName($bus)
    {
        return  $bus->bus_name.' ('.$bus->vehicle_number.')';
    }

//    /*
//      buses by route id
//     */
//    public function routeBuses($route_id)
//    {
//        return  Bus::where([['vehicle_verification_status','=',2]])
//        ->whereHas('routeMapping',function($q) use($route_id){
//           $q->where([['route_id','=',$route_id],['status','=',1]]);
//        })
//        ->get();
//    }

    public function BusRouteMapping(){
        return $this->hasMany(BusRouteMapping::class);
    }

    public function BusService()
    {
        return $this->belongsToMany(BusService::class);
    }

    public function BusTraveller()
    {
        return $this->belongsTo(BusTraveller::class);
    }
}
