<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class HandymanCategory extends Model
{
    protected $guarded = [];

    protected $hidden = ['LanguageAny', 'LanguageSingle'];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function LanguageAny()
    {
        return $this->hasOne(LanguageHandymanCategory::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageHandymanCategory::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getCategoryAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->category;
        }
        return $this->LanguageSingle->category;
    }

    public function getDescriptionAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->description;
        }
        return $this->LanguageSingle->description;
    }

    public function ServiceTypes()
    {
        return $this->belongsToMany(ServiceType::class);
    }
}
