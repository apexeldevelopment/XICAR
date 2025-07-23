<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;

class BusService extends Model
{
    use HasFactory;

    protected $hidden = array('LanguageSingle', 'LanguageAny');

    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(LanguageBusService::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageBusService::class)->where([['locale', '=', App::getLocale()]]);
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
