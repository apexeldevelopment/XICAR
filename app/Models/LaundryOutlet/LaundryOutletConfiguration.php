<?php

namespace App\Models\LaundryOutlet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOutletConfiguration extends Model
{
    use HasFactory;

    public function LaundryOutlet()
    {
        return $this->belongsTo(LaundryOutlet::class);
    }
}
