<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusConfiguration extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }
}
