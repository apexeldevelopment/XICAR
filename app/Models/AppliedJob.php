<?php

namespace App\Models;

use App;
use Illuminate\Database\Eloquent\Model;

class AppliedJob extends Model
{
    protected $guarded = [];


    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }
    public function JobVacancy()
    {
        return $this->belongsTo(JobVacancy::class);
    }
}
