<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeScreenHolder extends Model
{
    use HasFactory;
    protected $fillable = [];
    public function HolderSegment()
    {
        return $this->hasMany(HolderSegment::class, 'home_screen_holder_id', 'id');
    }
}
