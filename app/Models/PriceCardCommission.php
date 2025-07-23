<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceCardCommission extends Model
{
    protected $guarded = [];

    public function PriceCard()
    {
        return $this->belongsTo(PriceCard::class);
    }
}
