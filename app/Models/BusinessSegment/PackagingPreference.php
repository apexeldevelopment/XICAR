<?php

namespace App\Models\BusinessSegment;

use App\Models\LanguageCountry;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagingPreference extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function BusinessSegment()
    {
        return $this->belongsTo(BusinessSegment::class);
    }

    public function LanguagePackagingPreferenceAny()
    {
        return $this->hasOne(LangPackagingPreference::class);
    }

    public function LanguagePackagingPreferenceSingle()
    {
        return $this->hasOne(LangPackagingPreference::class)->where([['locale', '=', \App::getLocale()]]);
    }

    public function getPackagingPreferenceDescriptionAttribute()
    {
        if (empty($this->LanguagePackagingPreferenceSingle)) {
            return $this->LanguagePackagingPreferenceAny->name;
        }
        return $this->LanguagePackagingPreferenceSingle->description;
    }

}
