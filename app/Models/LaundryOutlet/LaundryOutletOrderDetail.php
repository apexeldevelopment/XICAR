<?php

namespace App\Models\LaundryOutlet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOutletOrderDetail extends Model
{
    use HasFactory;
    public function LaundryOutletOrder()
    {
        return $this->belongsTo(LaundryOutletOrder::class);
    }
    public function Service()
    {
        return $this->belongsTo(LaundryService::class, 'laundry_service_id');
    }
}
