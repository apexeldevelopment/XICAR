<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BusinessSegment\BusinessSegment;

class HandymanBookingCart extends Model
{
    //
    public function SegmentPriceCard()
    {
        return $this->belongsTo(SegmentPriceCard::class);
    }
    public function User()
    {
        return $this->belongsTo(User::class);
    }
    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }
    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function Driver()
    {
        return $this->belongsTo(Driver::class);
    }
    public function ServiceTimeSlot()
    {
        return $this->belongsTo(ServiceTimeSlot::class);
    }

    public function ServiceTimeSlotDetail()
    {
        return $this->belongsTo(ServiceTimeSlotDetail::class);
    }
    public function PromoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }
}
