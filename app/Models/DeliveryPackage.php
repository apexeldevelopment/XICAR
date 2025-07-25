<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryPackage extends Model
{
    protected $guarded = [];

    public function DeliveryProduct(){
        return $this->belongsTo(DeliveryProduct::class);
    }

    public function Booking(){
        return $this->belongsTo(Booking::class);
    }

    public function BookingDeliveryDetails(){
        return $this->belongsTo(BookingDeliveryDetails::class, 'booking_delivery_detail_id');
    }
}
