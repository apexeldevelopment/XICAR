<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapConfiguration extends Model
{
    use HasFactory;
    protected $guarded =[];

    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }
    public function ApiSlug(){
        return $this->belongsTo(ApiSlug::class);
    }
}
