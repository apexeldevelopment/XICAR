<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandymanBiddingOrder extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->merchant_order_id = $model->NewBookigId($model->merchant_id);
            return $model;
        });
    }

    public function NewBookigId($merchantID)
    {
        $booking = HandymanBiddingOrder::where([['merchant_id', '=', $merchantID]])->orderBy('id','DESC')->first();
        if (!empty($booking)) {
            return $booking->merchant_order_id + 1;
        } else {
            return 1;
        }
    }

    public function Merchant()
    {
        return $this->belongsTo(Merchant:: class);
    }

    public function PaymentMethod()
    {
        return $this->belongsTo(PaymentMethod:: class);
    }

    public function User()
    {
        return $this->belongsTo(User:: class);
    }

    public function Segment()
    {
        return $this->belongsTo(Segment:: class);
    }
    public function CountryArea()
    {
        return $this->belongsTo(CountryArea:: class);
    }
    function ServiceTimeSlotDetail()
    {
        return $this->belongsTo(ServiceTimeSlotDetail::class);
    }

    public function SegmentPriceCard()
    {
        return $this->belongsTo(SegmentPriceCard:: class);
    }

    public function Driver()
    {
        return $this->belongsToMany(Driver::class, 'handyman_bidding_order_driver');
    }
    public function AllReceivedDrivers()
    {
        return $this->belongsToMany(Driver::class, 'handyman_bidding_order_driver')->wherePivotIn("status",[0,1,2,3,4])->withPivot("status","amount","description")->orderBy('amount','ASC');
    }
    public function ActionedDrivers()
    {
        return $this->belongsToMany(Driver::class, 'handyman_bidding_order_driver')->wherePivotIn("status",[1,2,4])->withPivot("status","amount","description")->orderBy('amount','ASC');
    }

    public function ActionedDriver($driver_id)
    {
        return $this->belongsToMany(Driver::class, 'handyman_bidding_order_driver')->wherePivot('driver_id',$driver_id)->withPivot("status","amount","description");
    }

    public function AcceptedDriver()
    {
        return $this->belongsToMany(Driver::class, 'handyman_bidding_order_driver')->wherePivotIn("status",[4])->withPivot("status","amount","description");
    }

    public function RejectedDriver(){
        return $this->belongsToMany(Driver::class, 'handyman_bidding_order_driver')->wherePivotIn("status",[3])->withPivot("status","amount","description");
    }

    public function CancelReason()
    {
        return $this->belongsTo(CancelReason::class);
    }

    public function SiteVisitors()
    {
        return $this->belongsToMany(Driver::class, 'handyman_bidding_order_site_visitors');
    }

    public function SiteVisitorRequestDrivers()
    {
        return $this->belongsToMany(Driver::class, 'handyman_bidding_order_site_visitors')->wherePivotIn("status",[0, 1])->withPivot("status","description");
    }


    public function AcceptedVisitRequestDrivers()
    {
        return $this->belongsToMany(Driver::class, 'handyman_bidding_order_site_visitors')->wherePivotIn("status",[1])->withPivot("status","description");
    }

    public function SiteVisitorRequestDriver($driver_id)
    {
        return $this->belongsToMany(Driver::class, 'handyman_bidding_order_site_visitors')->wherePivot('driver_id',$driver_id)->withPivot("status","description");
    }
}
