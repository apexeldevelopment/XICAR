<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class Country extends Model
{
    protected $guarded = [];

    protected $hidden = ['LanguageCountrySingle', 'LanguageCountryAny'];

    public function CountryArea()
    {
        return $this->hasMany(CountryArea::class);
    }

    public function LanguageCountryAny()
    {
        return $this->hasOne(LanguageCountry::class);
    }

    public function LanguageCountrySingle()
    {
        return $this->hasOne(LanguageCountry::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getCountryNameAttribute()
    {
        if (empty($this->LanguageCountrySingle)) {
            return $this->LanguageCountryAny->name;
        }
        return $this->LanguageCountrySingle->name;
    }

    public function getCurrencyNameAttribute()
    {
        if (empty($this->LanguageCountrySingle)) {
            return $this->LanguageCountryAny->currency;
        }
        return $this->LanguageCountrySingle->currency;
    }

    public function getAdditionalTitleAttribute()
    {
        if (empty($this->LanguageCountrySingle)) {
            return $this->LanguageCountryAny->parameter_name;
        }
        return $this->LanguageCountrySingle->parameter_name;
    }

    public function getAdditionalPlaceholderAttribute()
    {
        if (empty($this->LanguageCountrySingle)) {
            return $this->LanguageCountryAny->placeholder;
        }
        return $this->LanguageCountrySingle->placeholder;
    }
    public function documents()
    {
        return $this->belongsToMany(Document::class,'country_document')->withPivot('document_type');
    }

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function paymentoption()
    {
        return $this->belongsToMany(PaymentOptionsConfiguration::class,'country_payment','country_id')->withPivot('payment_options_configuration_id','country_id');
    }

    public function paymentcashout()
    {
        return $this->belongsToMany(PaymentOptionsConfiguration::class,'country_cashout_payment');
    }

    public function operator()
    {
        return $this->belongsToMany(PaymentOptionsConfiguration::class,'country_payment_operator');
    }

    public function CarpoolingConfigCountry()
    {
        return $this->hasMany(CarpoolingConfigCountry::class);
    }
    
}
