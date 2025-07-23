<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandymanCommissionDetail extends Model
{
    public $fillable = ['handyman_commission_id', 'service_type_id','amount'];
    public function ServiceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function HandymanCommission()
    {
        return $this->belongsTo(HandymanCommission::class);
    }
}