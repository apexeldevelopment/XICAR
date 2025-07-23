<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusBookingMaster extends Model
{
    protected $guarded = [];

    public function Bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function BusRoute()
    {
        return $this->belongsTo(BusRoute::class);
    }

    public function ServiceTimeSlotDetail()
    {
        return $this->belongsTo(ServiceTimeSlotDetail::class);
    }

    public function Driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function ServiceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function BusBooking()
    {
        return $this->hasMany(BusBooking::class);
    }

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }
}
