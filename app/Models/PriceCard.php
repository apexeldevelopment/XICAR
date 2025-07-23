<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceCard extends Model
{
    protected $hidden = array('pivot', 'VehicleType', 'PriceCardValues');

    protected $guarded = [];

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }

    public function ServiceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function VehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

//    public function PaymentMethod()
//    {
//        return $this->belongsToMany(PaymentMethod::class);
//    }

    public function PriceCardValues()
    {
        return $this->hasMany(PriceCardValue::class);
    }

    public function OutstationPackage()
    {
        return $this->belongsTo(OutstationPackage::class, 'service_package_id','id');
    }

    public function PriceCardCommission()
    {
        return $this->hasOne(PriceCardCommission::class);
    }

    public function ServicePackage()
    {
        return $this->belongsTo(ServicePackage::class,'service_package_id','id');
    }

    public function ExtraCharges()
    {
        return $this->hasMany(ExtraCharge::class);
    }

    //Code merged by @Amba
    public function deliveryType()
    {
        return $this->belongsTo(DeliveryType::class);
    }

    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function PriceCardDetail()
    {
        return $this->hasMany(PriceCardDetail::class);
    }

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function CarpoolingRideDetail(){
        return $this->hasMany(CarpoolingRideDetail::class);
    }

    public function CarpoolingPriceCardCancelCharge()
    {
        return $this->hasOne(CarpoolingPriceCardCancelCharge::class);
    }


    public function DistanceSlab()
    {
        return $this->belongsTo(DistanceSlab::class);
    }

    public function BaseFarePriceCardSlab()
    {
        return $this->belongsTo(PriceCardSlab::class,'base_fare_price_card_slab_id');
    }

    public function CompetitorPriceCard(){
        return $this->hasOne(CompetitorPriceCard::class);
    }
}
