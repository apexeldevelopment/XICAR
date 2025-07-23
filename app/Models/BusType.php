<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusType extends Model
{
    use HasFactory, SoftDeletes;

    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }

    public function BusTypeDetail(){
        return $this->hasMany(BusTypeDetail::class);
    }
}
