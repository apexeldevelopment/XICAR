<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardGift extends Model
{
    protected $guarded = [];

    public function Country(){
        return $this->belongsTo(Country::class);
    }
}
