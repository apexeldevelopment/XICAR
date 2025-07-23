<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardSystem extends Model
{
    protected $guarded = [];

    public function Country(){
        return $this->belongsTo(Country::class);
    }

    public function CountryArea(){
        return $this->belongsTo(CountryArea::class);
    }
}
