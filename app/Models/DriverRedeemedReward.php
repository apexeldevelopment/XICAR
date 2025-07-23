<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverRedeemedReward extends Model
{
    protected $guarded = [];

    public function RewardGift(){
        return $this->belongsTo(RewardGift::class);
    }

    public function Driver(){
        return $this->belongsTo(Driver::class);
    }
}
