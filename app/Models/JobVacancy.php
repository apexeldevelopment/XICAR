<?php

namespace App\Models;

use App;
use Illuminate\Database\Eloquent\Model;

class JobVacancy extends Model
{
    protected $guarded = [];

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }
    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    // public function ServiceType()
    // {
    //     return $this->belongsToMany(ServiceType::class);
    // }

    // public function Corporate()
    // {
    //     return $this->belongsTo(Corporate::class, 'corporate_id', 'id');
    // }

    // public function PriceCard()
    // {
    //     return $this->belongsToMany(PriceCard::class);
    // }

    public function LanguageAny()
    {
        return $this->hasOne(JobVacancyTranslation::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(JobVacancyTranslation::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getPromoNameAttribute()
    {
        if (!empty($this->LanguageSingle)) {
            return $this->LanguageSingle->promo_code_name;
        }
        if (!empty($this->LanguageAny)){
            return $this->LanguageAny->promo_code_name;
        }
        return trans("common.promo").' '.trans("common.code");
    }

    // public function Booking()
    // {
    //     return $this->hasMany(Booking::class,'promo_code', 'id');
    // }

    // public function getTotalUsesAttribute()
    // {
    //     return $this->hasMany(Booking::class,'promo_code', 'id')->whereJob($this->id)->count();
    // }

    // public function PriceCardForPromo(){
    //     return $this->belongsToMany(PriceCard::class)->withPivot('price_card_id');
    // }

    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }
    public function AppliedJobs()
    {
        return $this->hasMany(AppliedJob::class);
    }
}
