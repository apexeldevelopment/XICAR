<?php

namespace App\Http\Controllers\Helper;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Merchant;
use DateTime;
use App\Models\Onesignal;

class DriverRequestsController extends Controller
{
    //

    public function RequestForHomelocation()
    {
    }

    public function LogoutLocationExpiredDrivers($merchant_id)
    {
        $merchant = Merchant::find($merchant_id);
        $drivers = Driver::where("merchant_id",$merchant_id)->where("driver_delete",NULL)->where("online_offline", 1)->get();
        $minute = $merchant->DriverConfiguration->inactive_time ?? null;
        $location_expired_drivers = [];
        if ($minute > 0) {
            $date = new DateTime('now', new \DateTimeZone('UTC'));
            $date->modify("-$minute minutes");
            $location_updated_last_time = $date->format('Y-m-d H:i:s');
        }
        $updated_last_time = new DateTime($location_updated_last_time);
        $list =[];
        if($merchant->ApplicationConfiguration->working_with_redis == 1){
            foreach ($drivers as $driver) {
                $sub_list =[];
                $driver_data = getDriverCurrentLatLong($driver);
                $updated_date_time = new DateTime($driver_data['timestamp']);
                if ($updated_date_time < $updated_last_time) {
                    $sub_list['id']= $driver->id;
                    $sub_list['merchant_id']= $driver->merchant_id;
                    $sub_list['name']= $driver->first_name." ".$driver->last_name;
                    $sub_list['phone']= $driver->phoneNumber;
                    $sub_list['service_area']= $driver->CountryArea->getCountryAreaNameAttribute();
                    array_push($list,$sub_list );
                    $location_expired_drivers[] = $driver->id;
//                    $driver->login_logout = 2;
//                    $driver->save();
                }
            }
        }
        $arr_param['driver_id']= $location_expired_drivers;
        $arr_param['merchant_id']= $merchant_id;
        $arr_param['data']= [];
        $arr_param['message']= "Dear partner you have been logged out since we are unable to fetch your location, To start Earning again, please login immediately !";
        $arr_param['title']= "Location Expired";
        Onesignal::DriverPushMessage($arr_param);
        p($list);


    }
}
