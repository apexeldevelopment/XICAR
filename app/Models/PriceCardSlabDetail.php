<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 16/5/23
 * Time: 6:02 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class PriceCardSlabDetail extends Model
{
    protected $guarded = [];

    public function PriceCardSlab()
    {
        return $this->hasMany(PriceCardSlab::class);
    }
}
