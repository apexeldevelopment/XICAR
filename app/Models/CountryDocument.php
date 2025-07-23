<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryDocument extends Model
{
    use HasFactory;
    protected $table = 'country_document';
    protected $fillable = ['country_id', 'document_id', 'document_type'];
    public $timestamps = false;
}
