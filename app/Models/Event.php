<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Event extends Model
{
    protected $guarded = [];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class,'merchant_id');
    }

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class,'country_area_id');
    }

    public function Segment()
    {
        return $this->belongsToMany(Segment::class);
    }

    public function LangEvent()
    {
        return $this->morphMany(LangName::class, 'dependable');
    }

    public function LangEventSingle()
    {
        return $this->morphOne(LangName::class, 'dependable')->where([['locale', '=', \App::getLocale()]]);
    }
    // multi-lang for event
    public function Name($merchant_id = NULL)
    {
        $locale = App::getLocale();
        $event = $this->morphOne(LangName::class, 'dependable')->where([['merchant_id', '=', $merchant_id]])
            ->where(function ($q) use ($locale) {
                $q->where('locale', $locale);
            })->first();
        if (!empty($event->id)) {
            return $event->name;
        }
        else
        {
            $event = $this->morphOne(LangName::class, 'dependable')->where([['merchant_id', '=', $merchant_id]])
                ->where(function ($q) use ($locale) {
                    $q->where('locale', '!=', NULL);
                })->first();
            if (!empty($event->id)) {
                return $event->name;
            }
        }
        return "";
    }
}
