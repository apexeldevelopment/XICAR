<?php

namespace App\Models;

use App\Models\BusinessSegment\BusinessSegment;
use Illuminate\Database\Eloquent\Model;

class BusinessSegmentCard extends Model
{
    protected $guarded = [];

    public function BusinessSegment()
    {
        return $this->belongsTo(BusinessSegment::class);
    }

    public function PaymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }
}
