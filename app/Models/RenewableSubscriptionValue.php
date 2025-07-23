<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewableSubscriptionValue extends Model
{
    use HasFactory;
    protected $guarded =[];
    protected $fillable = [];


    public function RenewableSubscription(){
        return $this->belongsTo(RenewableSubscription::class);
    }
}
