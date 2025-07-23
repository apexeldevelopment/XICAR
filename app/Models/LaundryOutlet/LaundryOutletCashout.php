<?php

namespace App\Models\LaundryOutlet;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOutletCashout extends Model
{
    use HasFactory;

    public function LaundryOutlet(){
        return $this->belongsTo(LaundryOutlet::class);
    }

    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }
}
