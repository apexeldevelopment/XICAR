<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Brand extends Model
{
    protected $guarded = [];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class,'merchant_id');
    }

    public function Segment()
    {
        return $this->belongsToMany(Segment::class);
    }

    public function LangBrand()
    {
        return $this->morphMany(LangName::class, 'dependable');
    }

    public function LangBrandSingle()
    {
        return $this->morphOne(LangName::class, 'dependable')->where([['locale', '=', \App::getLocale()]]);
    }
    // multi-lang for brand
    public function Name($merchant_id = NULL)
    {
        $locale = App::getLocale();
        $brand = $this->morphOne(LangName::class, 'dependable')->where([['merchant_id', '=', $merchant_id]])
            ->where(function ($q) use ($locale) {
                $q->where('locale', $locale);
            })->first();
        if (!empty($brand->id)) {
            return $brand->name;
        }
        else
        {
            $brand = $this->morphOne(LangName::class, 'dependable')->where([['merchant_id', '=', $merchant_id]])
                ->where(function ($q) use ($locale) {
                    $q->where('locale', '!=', NULL);
                })->first();
            if (!empty($brand->id)) {
                return $brand->name;
            }
        }
        return "";
    }
}
