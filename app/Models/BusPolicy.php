<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusPolicy extends Model
{
    use HasFactory;

    protected $table = 'bus_policy';

    protected $fillable = [
        'bus_id',
        'policy_name',
        'description',
    ];

    protected $casts = [
        'bus_id' => 'integer',
    ];

    public $timestamps = true;

    // public function merchant()
    // {
    //     return $this->belongsTo(Merchant::class, 'merchant_id');
    // }
}