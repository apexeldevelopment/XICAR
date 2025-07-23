<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVehicle extends Model
{
    protected $guarded = [];

    protected $hidden = ['VehicleType', 'VehicleMake', 'VehicleModel', 'User', 'pivot'];

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function OwnerUser()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function Users()
    {
        return $this->belongsToMany(User::class)->withPivot('user_id', 'vehicle_active_status','user_default_vehicle');
    }

    public function VehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }

    public function VehicleMake()
    {
        return $this->belongsTo(VehicleMake::class);
    }

    public function VehicleModel()
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function UserVehicleDocument()
    {
        return $this->hasMany(UserVehicleDocument::class);
    }

    public function getUsers()
    {
        return $this->belongsToMany(User::class);
    }

    public function CarpoolingRide()
    {
        return $this->hasMany(CarpoolingRide::class);
    }
}
