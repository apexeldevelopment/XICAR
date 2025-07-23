<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;

class BusPickupDropPoint extends Model
{
    protected $hidden = array('LanguageSingle', 'LanguageAny');

    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(LanguageBusPickupDropPoint::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageBusPickupDropPoint::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getNameAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->title;
        }
        return $this->LanguageSingle->title;
    }

    public function BusStop()
    {
        return $this->belongsToMany(BusStop::class);
    }
}
