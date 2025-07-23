<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 16/5/23
 * Time: 6:00 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceCardSlab extends Model
{
    protected $guarded = [];

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }

    public function PriceCardSlabDetail()
    {
        return $this->hasMany(PriceCardSlabDetail::class);
    }
}
