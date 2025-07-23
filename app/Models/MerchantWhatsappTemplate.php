<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantWhatsappTemplate extends Model
{
    use HasFactory;

    protected $table = 'merchant_whatsapp_templates';

    protected $fillable = [
        'merchant_id',
        'event',
        'template_name',
        'template_language',
        'template_variables',
    ];

    protected $casts = [
        'merchant_id' => 'integer',
        'event' => 'integer',
    ];

    public $timestamps = true;

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }
}
