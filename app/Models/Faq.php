<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;

class Faq extends Model
{
    use HasFactory;

    protected $hidden = array('LanguageSingle', 'LanguageAny');

    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(LanguageFaq::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageFaq::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function FaqType()
    {
        return $this->belongsTo(FaqType::class);
    }

    public function getNameAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->question;
        }
        return $this->LanguageSingle->question;
    }

    public function getDescriptionAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->answer;
        }
        return $this->LanguageSingle->answer;
    }
}
