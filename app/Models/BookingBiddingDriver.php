<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingBiddingDriver extends Model
{
    use HasFactory;

    public function Booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function Driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
