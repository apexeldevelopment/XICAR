<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarpoolingOfferRideCheckoutDetail extends Model
{
    protected $guarded = [];

    public function CarpoolingOfferRideCheckout()
    {
        return $this->belongsTo(CarpoolingOfferRideCheckout::class);
    }
}
