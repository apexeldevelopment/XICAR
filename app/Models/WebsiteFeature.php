<?php

namespace App\Models;

use App;
use Illuminate\Database\Eloquent\Model;

class WebsiteFeature extends Model
{
    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(WebsiteFeatureTranslation::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(WebsiteFeatureTranslation::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getTitleAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->app_title;
        }
        return $this->LanguageSingle->app_title;
    }

    public function getBannerAttribute()
    {
        if (empty($this->LanguageSingle)) {
           return $this->LanguageAny->banner;
//            return $arr_data;
        }
        return $this->LanguageSingle->banner;
    }
    public function getFooterLeftAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->footer_left_content;
        }
        return $this->LanguageSingle->footer_left_content;
    }
    public function getFooterRightAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->footer_right_service;
        }
        return $this->LanguageSingle->footer_right_service;
    }
}
