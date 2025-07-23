<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserHold extends Model
{
    public function User(){
        return $this->belongsTo(User::class);
    }
    public function CarpoolingRide(){
        return $this->belongsTo(CarpoolingRide::class);
    }
    public function CarpoolingRideUserDetail(){
        return $this->belongsTo(CarpoolingRideUserDetail::class);
    }
}
