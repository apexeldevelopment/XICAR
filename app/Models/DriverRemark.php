<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverRemark extends Model
{
    use HasFactory;

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function Driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
