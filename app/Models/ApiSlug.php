<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiSlug extends Model
{
    use HasFactory;
    protected $guarded =[];

    public function MapConfiguration(){
        return $this->hasMany(MapConfiguration::class);
    }
}
