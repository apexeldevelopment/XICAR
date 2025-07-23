<?php

namespace App\Models\LaundryOutlet;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LanguageLaundryServices extends Model
{
    use HasFactory;
    protected $fillable = [
        'merchant_id',
        'locale',
        'laundry_service_id',
        'laundry_outlet_id',
        'name',
        'description'
    ];
    public function LanguageName()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}
