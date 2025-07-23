<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusDocument extends Model
{
    protected $guarded = [];

    protected $hidden = ['Document'];

    public function Document()
    {
        return $this->belongsTo(Document::class);
    }
//    public function DriverVehicle()
//    {
//        return $this->belongsTo(DriverVehicle::class);
//    }
}
