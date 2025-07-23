<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BusinessSegment\ProductVariant;

class UserSubscriptionRecord extends Model
{
    protected $guarded = [];
    public function User()
    {
        return $this->belongsTo(User::class);
    }
    public function ProductVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_id');
    }


    public function PackageDuration()
    {
        return $this->belongsTo(PackageDuration::class);
    }

    public function PaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function SubscriptionPackage()
    {
        return $this->belongsTo(SubscriptionPackage::class, 'subscription_pack_id');
    }
}
