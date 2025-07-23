<?php

namespace App\Models\LaundryOutlet;

use App\Http\Controllers\Helper\CommonController;
use App\Models\Category;
use App\Models\Country;
use App\Models\CountryArea;
use App\Models\Merchant;
use App\Models\MerchantWebOneSignal;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\App;

/**
 * @ayush
 * Laundry Module */
class LaundryOutlet extends Authenticatable
{
    use HasFactory;
    protected $table = 'laundry_outlets';
    protected $guarded = [];

    protected $hidden = [
        'password', 'remember_token', 'pivot',
    ];

    public function findForPassport($user_cred = null)
    {
        if (!empty($_SERVER['HTTP_PUBLICKEY']) && !empty($_SERVER['HTTP_SECRETKEY'])) {
            $merchant = CommonController::Marchant($_SERVER['HTTP_PUBLICKEY'], $_SERVER['HTTP_SECRETKEY']);
            $merchant_id = $merchant['id'];
        }
        return LaundryOutlet::where([['merchant_id', '=', $merchant_id], ['email', '=', $user_cred], ['status', '=', 1]])
            ->first();
    }

    public function Merchant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function Country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function CountryArea(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CountryArea::class);
    }

    public function Segment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function Onesignal()
    {
        return $this->hasOne(LaundryOutletOnesignal::class);
    }

    public function LaundryService(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LaundryService::class);
    }

    public  function webOneSignalPlayerId(){
        return $this->hasMany(MerchantWebOneSignal::class);
    }

    public function LaundryOutletCashout(){
        return $this->hasMany(LaundryOutletCashout::class);
    }

    public function LaundryOutletConfiguration(){
        return $this->hasOne(LaundryOutletConfiguration::class);
    }

}
