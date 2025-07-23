<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarpoolingCoordinate extends Model
{
    //
    protected $guarded = [];

    public function CarpoolingRide()
    {
        return $this->belongsTo(CarpoolingRide::class);
    }
}
