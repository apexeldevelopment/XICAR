<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantHomeScreenHolder extends Model
{
    use HasFactory;
    public $fillable = ['merchant_id', 'home_screen_holder_id','sequence'];
}
