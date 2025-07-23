<?php

namespace App\Models;

use App;
use Illuminate\Database\Eloquent\Model;

class VehicleDeliveryPackage extends Model
{
    protected $guarded = [];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function VehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }

    public function LanguageVehicleDeliveryPackageAny()
    {
        return $this->hasOne(LanguageVehicleDeliveryPackage::class,'vehicle_delivery_package_id');
    }

    public function LanguageVehicleDeliveryPackageSingle()
    {
        return $this->hasOne(LanguageVehicleDeliveryPackage::class,'vehicle_delivery_package_id')->where([['locale', '=', App::getLocale()]]);
    }

    public function getPackageNameAttribute()
    {
        if (empty($this->LanguageVehicleDeliveryPackageSingle)) {
            return !empty($this->LanguageMerchantMembershipPlanAny) ? $this->LanguageMerchantMembershipPlanAny->package_name : "";
        }
        return $this->LanguageVehicleDeliveryPackageSingle->package_name;
    }
}