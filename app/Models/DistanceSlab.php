<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistanceSlab extends Model
{
    protected $guarded = [];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    
}
