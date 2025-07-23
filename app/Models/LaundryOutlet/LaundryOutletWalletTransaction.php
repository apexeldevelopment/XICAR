<?php

namespace App\Models\LaundryOutlet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOutletWalletTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function LaundryOutlet()
    {
        return $this->belongsTo(LaundryOutlet::class, 'laundry_outlet_id');
    }
        public function LaundryOutletOrder()
    {
        return $this->belongsTo(LaundryOutletOrder::class, 'laundry_outlet_order_id');
    }
}
