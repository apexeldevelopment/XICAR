<?php

namespace App\Models;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchablePlace extends Model
{
    use HasFactory;
    protected $table = 'searchable_places';
    protected $guarded = [];
    public $timestamps = false;

    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function Country(){
        return $this->belongsTo(Country::class);
    }
}
