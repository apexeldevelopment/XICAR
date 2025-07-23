<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarpoolingRide extends Model
{
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->merchant_ride_id = $model->newRideId($model->merchant_id);
            return $model;
        });
    }

    public function newRideId($merchant_id)
    {
        $ride = CarpoolingRide::where([['merchant_id', '=', $merchant_id]])->orderBy('id', 'DESC')->first();
        if (!empty($ride)) {
            return $ride->merchant_ride_id + 1;
        } else {
            return 1;
        }
    }

    public function CarpoolingRideDetail()
    {
        return $this->hasMany(CarpoolingRideDetail::class);
    }

    public function Country()
    {
        return $this->hasMany(Country::class)->where('country_status', '=', 1)->orderBy('sequance', 'ASC');
    }

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class, 'country_area_id');
    }

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function UserVehicle()
    {
        return $this->belongsTo(UserVehicle::class, 'user_vehicle_id');
    }

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function Segment()
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }

    public function UserHold()
    {
        return $this->hasMany(UserHold::class, 'carpooling_ride_id');
    }

    public function CarpoolingRideUserDetail()
    {
        return $this->hasMany(CarpoolingRideUserDetail::class);
    }

    public function CancelReason()
    {
        return $this->belongsTo(CancelReason::class);
    }
    public function CarpoolingCoordinate()
    {
        return $this->hasOne(CarpoolingCoordinate::class);
    }
}
