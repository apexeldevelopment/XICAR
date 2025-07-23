<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarpoolingRideUserDetail extends Model
{
    protected $guarded = [];

    public function CarpoolingRideDetail()
    {
        return $this->belongsTo(CarpoolingRideDetail::class);
    }
    public function User(){
        return $this->belongsTo(User::class);
    }
    public function CarpoolingRide(){
        return $this->belongsTo(CarpoolingRide::class);
    }
    public function UserHold(){
        return $this->hasMany(UserHold::class,'user_id');
    }
    public function CancelReason()
    {
        return $this->belongsTo(CancelReason::class);
    }
}
