<?php

namespace App\Models\LaundryOutlet;

use App\Models\Merchant;
use App\Models\PriceCard;
use App\Models\Segment;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryServiceCart extends Model
{
    use HasFactory;
    protected $table = "laundry_service_carts";

    public function PriceCard()
    {
        return $this->belongsTo(PriceCard::class);
    }
    public function User()
    {
        return $this->belongsTo(User::class);
    }
    public function LaundryOutlet()
    {
        return $this->belongsTo(LaundryOutlet::class);
    }
    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }
    public function ServiceType()
    {
        return $this->belongsTo(ServiceType::class);
    }
}
