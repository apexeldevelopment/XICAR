<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitorPriceCard extends Model
{
    use HasFactory;
    protected $fillable = [];
    protected $guarded = [];

    public function PriceCard(){
        return $this->belongsTo(PriceCard::class);
    }

}
