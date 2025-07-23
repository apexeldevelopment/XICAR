<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewableSubscription extends Model
{
    use HasFactory;
    protected $fillable =[];
    protected $guarded = [];
    protected $hidden = ['LangSubscriptionPackageAny', 'LangSubscriptionPackageSingle'];



    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }
    public function RenewableSubscriptionValue(){
        return $this->hasMany(RenewableSubscriptionValue::class);
    }
    public function DriverRenewableSubscriptionRecord()
    {
        return $this->hasMany(DriverRenewableSubscriptionRecord::class,'renewable_subscription_id');
    }

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class,'country_area_id');
    }

    public function VehicleType()
    {
        return $this->belongsTo(VehicleType::class,'vehicle_type_id');
    }

    public function LangRenewableSubscriptionAny()
    {
        return $this->hasOne(LangRenewableSubscription::class);
    }

    public function LangRenewableSubscriptionSingle()
    {
        return $this->hasOne(LangRenewableSubscription::class)->where([['locale', '=', \App::getLocale()]]);
    }

    public function LangRenewableSubscription()
    {
        return $this->hasMany(LangRenewableSubscription::class);
    }

    public function getNameAttribute()
    {
        if (empty($this->LangRenewableSubscriptionSingle)) {
            return $this->LangRenewableSubscriptionAny->name;
        }
        return $this->LangRenewableSubscriptionSingle->name;
    }

    public function getDescriptionAttribute()
    {
        if (empty($this->LangRenewableSubscriptionSingle)) {
            return $this->LangRenewableSubscriptionAny->description;
        }
        return $this->LangRenewableSubscriptionSingle->description;
    }

}
