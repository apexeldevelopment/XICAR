<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripePayout extends Model
{
    use HasFactory;

    protected $fillable = ['stripe_account','amount','currency','merchant_id','status'];
}
