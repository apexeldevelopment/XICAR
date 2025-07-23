<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverMovementLog extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable =[];

    public function driver(){
        return $this->belongsTo(Driver::class);
    }
    public function Booking(){
        return $this->belongsTo(Booking::class);
    }
}
