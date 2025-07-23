<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolderSegment extends Model
{
    use HasFactory;
    public function HomeScreenHolder()
    {
        return $this->belongsTo(HomeScreenHolder::class);
    }
}
