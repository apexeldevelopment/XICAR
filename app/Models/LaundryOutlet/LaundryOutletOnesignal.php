<?php

namespace App\Models\LaundryOutlet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOutletOnesignal extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function LaundryOutlet(){
        return $this->belongsTo(LaundryOutlet::class);
    }
}
