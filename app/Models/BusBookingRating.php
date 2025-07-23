<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusBookingRating extends Model
{
    protected $guarded = [];

    public function BusBooking()
    {
        return $this->belongsTo(BusBooking::class);
    }
    
    public function BusBookingMaster()
    {
        return $this->belongsTo(BusBookingMaster::class);
    }
}
