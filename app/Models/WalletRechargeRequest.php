<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletRechargeRequest extends Model
{
    use HasFactory;
    protected $table = 'wallet_recharge_requests';
    protected $guarded = [];
    public $timestamps = true;

    public function Driver()
    {
        return $this->belongsTo(Driver::class);
    }
    public function User()
    {
        return $this->belongsTo(User::class);
    }
    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
