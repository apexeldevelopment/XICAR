<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusSeatDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function Bus(){
        return $this->belongsTo(Bus::class);
    }
}
