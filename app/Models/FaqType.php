<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;

class FaqType extends Model
{
    use HasFactory;

    protected $hidden = array('LanguageSingle', 'LanguageAny');

    protected $guarded = [];

    public function LanguageAny()
    {
        return $this->hasOne(LanguageFaqType::class);
    }

    public function LanguageSingle()
    {
        return $this->hasOne(LanguageFaqType::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getNameAttribute()
    {
        if (empty($this->LanguageSingle)) {
            return $this->LanguageAny->title;
        }
        return $this->LanguageSingle->title;
    }
}
