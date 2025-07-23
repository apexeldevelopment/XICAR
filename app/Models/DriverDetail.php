<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverDetail extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = [];


    public function Driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
