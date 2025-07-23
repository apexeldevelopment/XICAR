<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCashout extends Model
{
    protected $guarded = [];
    public function User(){
        return $this->belongsTo(User::class);
    }
    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }
}
