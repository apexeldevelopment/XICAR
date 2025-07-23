<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;
use App;

class TaxiCompanyCashout extends Authenticatable
{
	
	protected $guarded = [];
	
	protected $hidden = [
        'password', 'remember_token','pivot',
    ];
	
	public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function TaxiCompany()
    {
        return $this->belongsTo(TaxiCompany::class);
    }
}