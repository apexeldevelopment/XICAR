<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverRenewableSubscriptionRecord extends Model
{
    use HasFactory;
    protected $guarded =[];
    protected $fillable =[];
    public function RenewableSubscription(){
        return $this->belongsTo(RenewableSubscription::class);
    }

    public function Driver()
    {
        return $this->belongsTo(Driver::class);
    }


    public function PaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
