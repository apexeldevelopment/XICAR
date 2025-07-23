<?php

namespace App\Models\LaundryOutlet;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class LaundryService extends Model
{
    use HasFactory;

    public  function Category()
    {
        return $this->belongsTo(Category::class,'category_id');
    }

    public function langData($merchant_id = NULL)
    {
        $locale = App::getLocale();
        $service = $this->hasOne(LanguageLaundryServices::class, 'laundry_service_id')
            ->where('locale', $locale)
            ->where([['merchant_id', '=', $merchant_id]])->first();
        if(empty($service))
        {
            $service = $this->hasOne(LanguageLaundryServices::class, 'laundry_service_id')
                ->where([['merchant_id', '=', $merchant_id]])->first();
        }
        if (!empty($service->id)) {
            return $service;
        }
    }

    public function Name($merchant_id = NULL)
    {
        $locale = App::getLocale();
        $service = $this->hasOne(LanguageLaundryServices::class, 'laundry_service_id')
            ->where('locale', $locale)
            ->where([['merchant_id', '=', $merchant_id]])->first();
        if(empty($service))
        {
            $service = $this->hasOne(LanguageLaundryServices::class, 'laundry_service_id')
                ->where([['merchant_id', '=', $merchant_id]])->first();
        }
        if (!empty($service->id)) {
            return $service->name;
        }
    }

    public function LanguageLaundryServices()
    {
        return $this->hasMany(LanguageLaundryServices::class);
    }

    public function LaundryOutlet()
    {
        return $this->belongsTo(LaundryOutlet::class);
    }
}
