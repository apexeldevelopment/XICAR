<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WebsiteFeaturesComponents extends Model
{
    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(WebsiteFeaturesComponentsTranslation::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(WebsiteFeaturesComponentsTranslation::class)->where([['locale', '=', \App::getLocale()]]);
    }

    public function getFeatureTitleAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->banner_title;
        }
        return $this->LanguageSingle->banner_title;
    }

    public function getFeatureDescriptionAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->banner_description;
        }
        return $this->LanguageSingle->banner_description;
    }
}
