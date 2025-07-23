<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarpoolingConfigCountry extends Model
{
    protected $guarded = [];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function Country()
    {
        return $this->belongsTo(Country::class);
    }
}
