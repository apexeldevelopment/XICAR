<?php
//
//namespace App\Models;
//
//use Illuminate\Support\Facades\DB;
//use App\Http\Controllers\Helper\CommonController;
//use Illuminate\Notifications\Notifiable;
//use Illuminate\Foundation\Auth\User as Authenticatable;
//use Laravel\Passport\HasApiTokens;
//use DateTime;
//use App\Models\BusinessSegment\Order;
//use \App\Models\Segment;
//use DateTimeZone;
//
//class BusDriver extends Authenticatable
//{
//    use Notifiable, HasApiTokens;
//
//    protected $hidden = ['pivot'];
//
//    protected $guarded = [];
//    // socket true means get drivers lat long from node+mongodb
//    public static $socket = true;
//
//    protected static function boot()
//    {
//        parent::boot();
//        static::creating(function ($model) {
//            $model->merchant_driver_id = $model->NewDriverId($model->merchant_id);
//            return $model;
//        });
//    }
//
//    public function NewDriverId($merchantID)
//    {
//        $driver = BusDriver::where([['merchant_id', '=', $merchantID]])->latest()->first();
//        if (!empty($driver)) {
//            return $driver->merchant_driver_id + 1;
//        } else {
//            return 1;
//        }
//    }
//
//    public function OutStandings()
//    {
//        return $this->hasMany(OutStanding::class);
//    }
//
//
//
//    public function DriverVehicle()
//    {
//        return $this->belongsToMany(DriverVehicle::class)->wherePivot('vehicle_active_status', 1);
//    }
//
//    // public function DriverDocument()
//    // {
//    //     return $this->hasMany(DriverDocument::class);
//    // }
//    public function BusDriverDocument()
//    {
//        return $this->hasMany(BusDriverDocument::class);
//    }
//
//    public function Merchant()
//    {
//        return $this->belongsTo(Merchant::class);
//    }
//
//    public function CountryArea()
//    {
//        return $this->belongsTo(CountryArea::class);
//    }
//
//    public function DriverVehicles()
//    {
//        return $this->hasMany(DriverVehicle::class);
//    }
//
//
//    public function AccountType()
//    {
//        return $this->belongsTo(AccountType::class);
//    }
//
//
//
//    public function Segment()
//    {
//        return $this->belongsToMany(Segment::class);
//    }
//
//
//
//    public function ServiceType()
//    {
//        return $this->belongsToMany(ServiceType::class, 'driver_service_type', 'driver_id')->withPivot('segment_id');
//    }
//
//    public function ServiceTypeOnline()
//    {
//        return $this->belongsToMany(ServiceType::class, 'driver_online', 'driver_id')->withPivot('segment_id', 'driver_vehicle_id');
//    }
//
//
//    public function Country()
//    {
//        return $this->belongsTo(Country::class);
//    }
//    public function Vehicle()
//    {
//        return $this->hasMany(DriverVehicle::class);
//    }
//
//    public function findForPassport($user_cred = null)
//    {
//        if (!empty($_SERVER['HTTP_PUBLICKEY']) && !empty($_SERVER['HTTP_SECRETKEY'])) {
//            $merchant = CommonController::MerchantObj($_SERVER['HTTP_PUBLICKEY'], $_SERVER['HTTP_SECRETKEY']);
//            $driver_login = $merchant->ApplicationConfiguration->driver_login;
//            $merchant_id = $merchant['id'];
//        }
//        if ($driver_login == "EMAIL") {
//            return Driver::where([['merchant_id', '=', $merchant_id], ['email', '=', $user_cred], ['driver_admin_status', '=', 1]])->latest()->first();
//        } else {
//            return Driver::where([['merchant_id', '=', $merchant_id], ['phoneNumber', '=', $user_cred], ['driver_admin_status', '=', 1]])->latest()->first();
//        }
//    }
//
//    public function GenrateReferCode()
//    {
//        $code = getRandomCode();
//        if ($this->CheckReferCode($code)) {
//            return $this->GenrateReferCode();
//        }
//        return $code;
//    }
//
//    public function CheckReferCode($referCode)
//    {
//        return static::where([['driver_referralcode', '=', $referCode]])->exists();
//    }
//
//    public static function Logout($player, $merchant_id)
//    {
//        Driver::where([['merchant_id', '=', $merchant_id], ['player_id', '=', $player]])->update(['online_offline' => 2, 'login_logout' => 2]);
//    }
//}
