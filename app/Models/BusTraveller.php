<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusTraveller extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function Buses()
    {
        return $this->hasMany(Bus::class);
    }



    public function LanguageAny()
    {
        return $this->hasOne(LanguageBusTraveller::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageBusTraveller::class)->where([['locale', '=', \App::getLocale()]]);
    }

    public function getNameAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->title;
        }
        return $this->LanguageSingle->title;
    }

    public function getDescriptionAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->description;
        }
        return $this->LanguageSingle->description;
    }
}
