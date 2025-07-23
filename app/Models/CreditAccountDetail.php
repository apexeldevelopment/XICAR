<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditAccountDetail extends Model
{
    protected $guarded = [];

    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }

    public function User(){
        return $this->belongsTo(User::class);
    }

    public function Driver(){
        return $this->belongsTo(Driver::class);
    }

    public function PaymentOption(){
        return $this->belongsTo(PaymentOption::class);
    }

    public function PaymentOptionsConfiguration(){
        return $this->belongsTo(PaymentOptionsConfiguration::class);
    }
}
