<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;

class CancelPolicy extends Model
{
    use SoftDeletes;
    protected $guarded = [];


    public function LanguageAny()
    {
        return $this->hasOne(CancelPolicyTranslation::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(CancelPolicyTranslation::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function Country()
    {
        return $this->belongsTo(Country::class);
    }

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }

    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }


    public function PolicyTransalation($merchant_id = NULL)
    {
        $locale = App::getLocale();
        $policy = $this->hasOne(CancelPolicyTranslation::class, 'cancel_policy_id')->where(function($q) use ($locale){
            $q->where("locale", $locale)->orWhere("locale","en");
        })->where("merchant_id", $merchant_id)->orderByRaw("CASE WHEN locale = '".$locale."' THEN 1 ELSE 2 END")->first();


        if (!empty($policy)) {
            return $policy;
            // return $service->name;
        }
        return "";
    }

}
