<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PayPay\OpenPaymentAPI\Controller\Wallet;

class WalletReconciliation extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }

    public function Driver(){
        return $this->belongsTo(Driver::class);
    }



}
