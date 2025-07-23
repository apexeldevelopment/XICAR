<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class ApplicationTheme extends Model
{
    protected $guarded = [];

    protected $hidden = ['LanguageApplicationThemeSingle', 'LanguageApplicationThemeAny'];

    public function LanguageApplicationThemeAny()
    {
        return $this->hasOne(LanguageApplicationTheme::class);
    }

    public function LanguageApplicationThemeSingle()
    {
        return $this->hasOne(LanguageApplicationTheme::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getUserIntroTextAttribute()
    {
        if (empty($this->LanguageApplicationThemeSingle)) {
            if(empty($this->LanguageApplicationThemeAny)){
                return "[]";
            }
            return $this->LanguageApplicationThemeAny->user_intro_text;
        }
        return $this->LanguageApplicationThemeSingle->user_intro_text;
    }

    public function getDriverIntroTextAttribute()
    {
        if (empty($this->LanguageApplicationThemeSingle)) {
            if(empty($this->LanguageApplicationThemeAny)){
                return "[]";
            }
            return $this->LanguageApplicationThemeAny->driver_intro_text;
        }
        return $this->LanguageApplicationThemeSingle->driver_intro_text;
    }
}
