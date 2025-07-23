<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Request;

class SmsConfiguration extends Model
{
    protected $guarded =[];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function SmsGateways(){
        return $this->belongsTo(SmsGateways::class,'smsgateway_id');
    }
}
