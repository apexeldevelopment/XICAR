<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class AllSosRequest extends Model
{
    use HasFactory;
    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    public function User()
    {
        return $this->belongsTo(User::class);
    }
    public function Driver()
    {
        return $this->belongsTo(Driver::class);
    }
    public function Booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
