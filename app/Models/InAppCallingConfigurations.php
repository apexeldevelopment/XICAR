<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InAppCallingConfigurations extends Model
{
    protected $guarded =[];

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function InAppCallings(){
        return $this->belongsTo(InAppCallings::class,'in_app_calling_id');
    }
}
