<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App;

class BonsBankToBankQrGateway extends Model
{
     use HasFactory;

    protected $hidden = array('LanguageSingle', 'LanguageAny');

    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(BonsBankToBankQrGatewayLanguage::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(BonsBankToBankQrGatewayLanguage::class)->where([['locale', '=', App::getLocale()]]);
    }
    
     public function getBankNameAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->bank_name;
        }
        return $this->LanguageSingle->bank_name;
    }

    public function getAccountNameAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->account_name;
        }
        return $this->LanguageSingle->account_name;
    }
    
    public function Merchant(){
        $this->belongsTo(Merchant::class);
    }
}