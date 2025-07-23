<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LangRenewableSubscription extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function LanguageName()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }

    public function RenewableSubscription()
    {
        return $this->belongsTo(RenewableSubscription::class);
    }
}

