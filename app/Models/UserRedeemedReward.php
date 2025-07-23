<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRedeemedReward extends Model
{
    protected $guarded = [];

    public function RewardGift(){
        return $this->belongsTo(RewardGift::class);
    }

    public function User(){
        return $this->belongsTo(User::class);
    }
}
