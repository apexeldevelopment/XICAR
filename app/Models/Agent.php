<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Agent extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password', 'remember_token','pivot',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function Country()
    {
        return $this->belongsTo(Country::class);
    }

    public function Driver()
    {
        return $this->hasMany(Driver::class)->where('driver_delete', '=', NULL);
    }

    public function AccountType()
    {
        return $this->belongsTo(AccountType::class);
    }
}
