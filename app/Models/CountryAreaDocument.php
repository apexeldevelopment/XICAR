<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryAreaDocument extends Model
{
    use HasFactory;

    protected $table = 'country_area_document';
    protected $fillable = ['country_area_id', 'document_id', 'document_type'];
    public $timestamps = false;
}
