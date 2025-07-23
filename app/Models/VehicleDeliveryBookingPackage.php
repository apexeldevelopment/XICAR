<?php
namespace App\Models;
use App;
use Illuminate\Database\Eloquent\Model;
class VehicleDeliveryBookingPackage extends Model
{
    protected $guarded = [];
    public function Booking(){
        return $this->belongsTo(Booking::class);
    }
}