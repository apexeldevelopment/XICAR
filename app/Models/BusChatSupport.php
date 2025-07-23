<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusChatSupport extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function LanguageAny()
    {
        return $this->hasOne(LanguageBusChatSupport::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageBusChatSupport::class)->where([['locale', '=', \App::getLocale()]]);
    }

    public function getNameAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->title;
        }
        return $this->LanguageSingle->title;
    }

    public function getSubtitleAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->subtitle;
        }
        return $this->LanguageSingle->subtitle;
    }
}
