<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class MerchantNavigationDrawer extends Model
{
    use HasFactory;

    protected $hidden = ['LanguageMerchantNavigationDrawerSingle', 'LanguageMerchantNavigationDrawerAny', 'pivot'];

    protected $guarded = [];

    public function LanguageMerchantNavigationDrawerAny()
    {
        return $this->hasOne(LanguageMerchantNavigationDrawer::class, 'merchant_navigation_drawer_id');
    }

    public function LanguageMerchantNavigationDrawerSingle()
    {
        return $this->hasOne(LanguageMerchantNavigationDrawer::class, 'merchant_navigation_drawer_id')->where([['locale', '=', App::getLocale()]]);
    }

    public function getNameAttribute()
    {
        if (empty($this->LanguageMerchantNavigationDrawerSingle)) {
            return !empty($this->LanguageMerchantNavigationDrawerAny) ? $this->LanguageMerchantNavigationDrawerAny->name : "";
        }
        return $this->LanguageMerchantNavigationDrawerSingle->name;
    }

    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }

    public function MerchantNavigationDrawerConfig(){
        return $this->hasMany(MerchantNavigationDrawerConfig::class);
    }
}
