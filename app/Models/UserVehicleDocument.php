<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVehicleDocument extends Model
{
    protected $hidden = ['Document'];

    protected $guarded = [];

    public function Document()
    {
        return $this->belongsTo(Document::class);
    }

    public function UserVehicle()
    {
        return $this->belongsTo(UserVehicle::class);
    }
}
