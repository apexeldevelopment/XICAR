<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentOptionsConfiguration extends Model
{
    protected $guarded = [];

    public function PaymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    
    public function country()
    {
        return $this->belongsToMany(Country::class,'country_payment','payment_options_configuration_id')->withPivot('payment_options_configuration_id','country_id');
    }
}
