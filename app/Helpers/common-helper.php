<?php

// print or echo function
use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\GetString;
use App\Models\CountryArea;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\DriverSegmentDocument;
use App\Models\DriverVehicle;
use App\Models\DriverVehicleDocument;
use App\Models\LaundryOutlet\LaundryService;
use App\Models\Merchant;
use App\Models\BookingConfiguration;
use App\Http\Controllers\Helper\GoogleController;
use App\Models\Merchant as MerchantModel;
use App\Models\Sos;
use App\Models\UserDevice;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\BusinessSegment\BusinessSegment;
use App\Http\Controllers\Helper\Merchant as helperMerchant;
use Spatie\Permission\Models\Permission;
use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Models\BusinessSegment\Product;
use App\Models\BusinessSegment\ProductVariant;
use Illuminate\Support\Facades\Redis;
use Lcobucci\JWT\Configuration as JWTConfiguration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

//use DateTime;


function p($p, $exit = 1)
{
    echo '<pre>';
    print_r($p);
    echo '</pre>';
    if ($exit == 1) {
        exit;
    }
}


/**
 * Set S3 Configuration Dyanmically
 */

function setS3Config($merchant)
{
    if(!empty($merchant->parent_id)){
        $merchant = Merchant::find($merchant->parent_id);
    }

    $file_system = json_decode($merchant->file_system_config, true);
    if(empty($file_system)){
        abort(409);
    }
    $s3 = $file_system['s3'];
    $s3Data = [
        'driver' => $s3['driver'],
        'key' => $s3['access_key'],
        'secret' => $s3['secret_key'],
        'region' => $s3['region'],
        'bucket' => $s3['bucket'],
        'url' => $s3['url'],
    ];
    \Config::set('filesystems.disks.s3', $s3Data);
}



// functions to get images from s3 bucket
function get_image($file_name = '', $custom_key = '', $merchant_id = NULL, $merchant = true, $signed_url = true, $session ="",$extra="", $download = false)
{
    $return_file = '';
    $alias = '';
    if (!empty($file_name)) {
        if ($file_name == 'stub_document') {
            $return_file = 'static-images/stub_document.png';
        } else {
            $upload_path = \Config::get('custom.' . $custom_key);
            if ($merchant) {
                $id = $merchant_id ? $merchant_id : get_merchant_id();
                $merchant = Merchant::Find($id);
                $alias = $merchant->alias_name;
                $file = $alias . $upload_path['path'] . $file_name;
                if($extra == 'gallery_image'){
                    $file = $file_name;
                }
            } else {
                $file = $upload_path['path'] . $file_name;
            }
            $return_file = $file;
        }
    } else {
        $return_file = 'static-images/no-image.png';
    }

    // return simple url
    //    if($signed_url == false)
    //    {
    //        $return_file = env('AWS_BUCKET_URL') . $return_file;
    //        return $return_file;
    //    }

    $minutes = 600;
    if (!empty($session)) {
        if ($session == "driver" || $session == "user" || $session == "bs" || $session == "email") {
            $minutes = 10080; // 3 months
        }
    }

    // return signed url
    $sharedConfig = [
        'region' => \Config::get('filesystems.disks.s3.region'), //'ap-south-1',//env('AWS_DEFAULT_REGION'),
        'version' => 'latest',
        'credentials' => [
            'driver' => \Config::get('filesystems.disks.s3.driver'),
            'key' => \Config::get('filesystems.disks.s3.key'), //env('AWS_ACCESS_KEY_ID'),
            'secret' => \Config::get('filesystems.disks.s3.secret'), //env('AWS_SECRET_ACCESS_KEY'),
            'region' => \Config::get('filesystems.disks.s3.region'), //env('AWS_DEFAULT_REGION'),
            'bucket' => \Config::get('filesystems.disks.s3.bucket'), //env('AWS_BUCKET'),
            'url' => \Config::get('filesystems.disks.s3.url')
        ]
    ];

    $s3Client = new S3Client($sharedConfig);
    $options = [
        'Bucket' => \Config::get('filesystems.disks.s3.bucket'),
        'Key' => $return_file
    ];
    if($download){
      $options['ResponseContentDisposition'] = 'attachment; filename="' . basename($file_name) . '"';
    }
    $cmd = $s3Client->getCommand('GetObject', $options);

    $request = $s3Client->createPresignedRequest($cmd, "+$minutes minutes");
    $presignedUrl = (string) $request->getUri();
    return $presignedUrl;
}

function check_file_extension($url)
{

    $url =  explode('.', $url);
    $is_pdf = false;
    if (isset($url[1]) && $url[1] == 'pdf') {
        $is_pdf = true;
    }
    return $is_pdf;
}


function explode_image_path($file_name)
{
    if (!empty($file_name)) {
        $image = explode('/', $file_name);
        return end($image);
    }
    return '';
}
//
//function view_config_image($file_name)
//{
//    if (!empty($file_name)) {
//        return env('AWS_BUCKET_URL') . $file_name;
//    } else {
//        return env('AWS_BUCKET_URL') . 'static-images/no-image.png';
//    }
//}

function view_config_image($return_file)
{
    if (empty($return_file)) {
        $return_file = 'static-images/no-image.png';
    }

    $minutes = 10080; // 3 months

    // return signed url
    $sharedConfig = [
        'region' => \Config::get('filesystems.disks.s3.region'), //'ap-south-1',//env('AWS_DEFAULT_REGION'),
        'version' => 'latest',
        'credentials' => [
            'driver' => \Config::get('filesystems.disks.s3.driver'),
            'key' => \Config::get('filesystems.disks.s3.key'), //env('AWS_ACCESS_KEY_ID'),
            'secret' => \Config::get('filesystems.disks.s3.secret'), //env('AWS_SECRET_ACCESS_KEY'),
            'region' => \Config::get('filesystems.disks.s3.region'), //env('AWS_DEFAULT_REGION'),
            'bucket' => \Config::get('filesystems.disks.s3.bucket'), //env('AWS_BUCKET'),
            'url' => \Config::get('filesystems.disks.s3.url')
        ]
    ];

    $s3Client = new S3Client($sharedConfig);
    $cmd = $s3Client->getCommand('GetObject', [
        'Bucket' => \Config::get('filesystems.disks.s3.bucket'),
        'Key' => $return_file
    ]);

    $request = $s3Client->createPresignedRequest($cmd, "+$minutes minutes");
    $presignedUrl = (string)$request->getUri();
    return $presignedUrl;
}

function get_config_image($dir)
{
    $upload_path = \Config::get('custom.' . $dir);
    $files = \Storage::disk('s3')->files($upload_path['path']);
    return $files;
}

function find_the_merchant($id)
{
    $merchant = Merchant::Find($id);
    return $merchant;
}

// delete image from s3
function delete_image($image, $dir = 'images', $merchant_id = null)
{
    $upload_path = \Config::get('custom.' . $dir);
    $id = $merchant_id ? $merchant_id : get_merchant_id();
    $merchant = Merchant::Find($id);
    $alias = $merchant->alias_name;
    $filePath = $alias . $upload_path['path'] . $image;
    // its returning 1 in case of success
    return Storage::disk('s3')->delete($filePath);
}



//functions to get images from gsc
//function get_image($file_name = '', $custom_key = '', $merchant_id = NULL, $merchant = true,$signed_url = true,$session = "")
//{
//    if (!empty($file_name)) {
//        if($file_name == 'stub_document')
//        {
//            $return_file = 'static-images/stub_document.png';
//        }
//        else
//        {
//            $upload_path = \Config::get('custom.' . $custom_key);
//            if ($merchant) {
//                $id = $merchant_id ? $merchant_id : get_merchant_id();
//                $merchant = Merchant::Find($id);
//                $alias = $merchant->alias_name;
//                $file = $alias. $upload_path['path'] . $file_name;
//            } else {
//                $file = $upload_path['path'] . $file_name;
//            }
//            $return_file = $file;
//        }
//    } else {
//        $return_file = 'static-images/no-image.png';
//    }
//    $duration = 604800;
//    $url = Storage::disk('gcs'/* following your filesystem configuration */)
//        ->getAdapter()
//        ->getBucket()
//        ->object($return_file)
//        ->signedUrl(new \DateTime('+ ' . $duration . 'seconds'));
//    return $url;
//}
//
//function view_config_image($file_name)
//{
//    if (!empty($file_name)) {
//        $return_file = $file_name;
//    } else {
//        $return_file ='static-images/no-image.png';
//    }
//    $duration = 604800;
//    $url = Storage::disk('gcs'/* following your filesystem configuration */)
//        ->getAdapter()
//        ->getBucket()
//        ->object($return_file)
//        ->signedUrl(new \DateTime('+ ' . $duration . 'seconds'));
//    return $url;
//}
//
//function get_config_image($dir)
//{
////    $upload_path = \Config::get('custom.' . $dir);
////    $files = \Storage::disk('s3')->files($upload_path['path']);
//    $files = [
//        "mapicon/ambulance.png"=>"mapicon/ambulance.png",
//        "mapicon/auto-rickshaw.png"=>"mapicon/auto-rickshaw.png",
//        "mapicon/car.png"=>"mapicon/car.png",
//        "mapicon/taxi_en_mapa.png"=>"mapicon/taxi_en_mapa.png",
//        "mapicon/yellow_car.png"=>"mapicon/yellow_car.png",
//        "mapicon/yellow_car_luxury.png"=>"mapicon/yellow_car_luxury.png",
//        "mapicon/new_car_icon.jpeg"=>"mapicon/new_car_icon.jpeg",
//    ];
//    return $files;
//}
//function explode_image_path($file_name)
//{
//    if (!empty($file_name)) {
//        $image = explode('/', $file_name);
//        return end($image);
//    }
//    return '';
//}

function get_merchant_id($return_id = true)
{
    if (Auth::guard('merchant')->check()) {
        if ($return_id == true) { // return only id of merchant
            return Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        } else { // return object of loggedin user
            return Auth::user('merchant')->parent_id != 0 ? Merchant::Find(Auth::user('merchant')->parent_id) : Auth::user('merchant');
        }
    }
}

function get_merchant_parent($return_id = true, $merchant_id = NULL, $merchant = NULL){
    if(empty($merchant)){
        $merchant = Merchant::find($merchant_id);
    }
    if ($return_id == true) { // return only id of merchant
        return $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
    } else { // return object of loggedin user
        return $merchant->parent_id != 0 ? Merchant::Find($merchant->parent_id) : $merchant;
    }
}

function get_business_segment($business_segment_id = true)
{
    if (Auth::guard('business-segment')->check()) {
        if ($business_segment_id == true) {
            return Auth::user('business-segment')->parent_id != 0 ? Auth::user('business-segment')->parent_id : Auth::user('business-segment')->id;
        } else {
            return Auth::user('business-segment')->parent_id != 0 ? BusinessSegment::Find(Auth::user('business-segment')->parent_id) : Auth::user('business-segment');
        }
    }
}

//function get_logged_user($guard = 'merchant',$return_id = true)
//{
//    if($guard == "merchant" && Auth::guard('merchant')->check())
//    {
//        if($return_id == true)
//        {// return only id of merchant
//            return Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
//        }
//        else
//        {// return object of loggedin user
//            return Auth::user('merchant')->parent_id != 0 ? Merchant::Find(Auth::user('merchant')->parent_id): Auth::user('merchant');
//        }
//    }
//    elseif($guard == "business-segment" && Auth::guard('business-segment')->check())
//    {
//        if(Auth::guard('business-segment')->check()){
//            if($return_id == true)
//            {
//                return Auth::user('business-segment')->parent_id != 0 ? Auth::user('business-segment')->parent_id : Auth::user('business-segment')->id;
//            }
//            else{
//                return Auth::user('business-segment')->parent_id != 0 ? BusinessSegment::Find(Auth::user('business-segment')->parent_id) : Auth::user('business-segment');
//            }
//        }
//    }
//}

//function get_merchant_config()
//{
//    $taxi_company = get_taxicompany();
//    if(!empty($taxi_company)){
//        $merchant_id = $taxi_company->merchant_id;
//    }else{
//        $merchant_id = get_merchant_id();
//    }
//    $data = [];
//    if ($merchant_id) {
//        $data = Configuration::where('merchant_id', $merchant_id)->first()->toArray();
//    }
//    return $data;
//}

function get_merchant_google_key($merchant_id = NULL, $request_from = 'admin_backend', $provider = "GOOGLE")
{
    if (empty($merchant_id)) {
        $taxi_company = get_taxicompany();
        $hotel = get_hotel();
        $corporate = get_corporate();
        if (!empty($taxi_company)) {
            $merchant_id = $taxi_company->merchant_id;
        } elseif (!empty($hotel)) {
            $merchant_id = $hotel->merchant_id;
        } elseif (!empty($corporate)) {
            $merchant_id = $corporate->merchant_id;
        } else {
            $merchant_id = get_merchant_id();
        }
    }
    $data = BookingConfiguration::select('google_key', 'google_key_admin', 'map_box_key')->where('merchant_id', $merchant_id)->first();
    if($provider == "GOOGLE"){
        if ($request_from == 'admin_backend') {
            return !empty($data['google_key_admin']) ? $data['google_key_admin'] : '';
        } else {
            return !empty($data['google_key']) ? $data['google_key'] : '';
        }
    }
    elseif($provider == "MAP_BOX"){
        return !empty($data['map_box_key']) ? $data['map_box_key'] : '';
    }

}

function getSelectedMap($merchant, $api_slug)
{
    $map_config = $merchant->MapConfiguration->where("api_slug", $api_slug)->first();
    if(empty($map_config)){
        return "GOOGLE";
    }
    return ($map_config->map_type == 1) ? "GOOGLE" : "MAP_BOX";
}

function saveApiLog($merchant_id, $provider_api_slug, $api_slug, $selected_map)
{
    try {
        $date = now()->format('Y-m-d');
        $key = "api_usage:{$merchant_id}:{$date}";
        $field = "{$selected_map}:{$api_slug}:{$provider_api_slug}";
        Redis::hincrby($key, $field, 1);
        Redis::expire($key, 60 * 60 * 24 * 2); // 2 days
    } catch (\Exception $e) {
        \Log::channel('debugger_v1')->emergency(["saveApiLog_exception" => $e->getMessage()]);
    }
}

function corporate_get_merchant_google_key()
{
    $merchant_id = Auth::user('corporate')->merchant_id;
    $data = BookingConfiguration::select('google_key_admin')->where('merchant_id', $merchant_id)->first();
    return !empty($data['google_key_admin']) ? $data['google_key_admin'] : '';
}


function redis_config()
{

    $redis = \Illuminate\Support\Facades\Redis::connection();
    $driver_id = 1;
    $redis->geoadd('drivers_trial', 77.0726, 28.4591, $driver_id);
    $driver_id = 2;
    $redis->geoadd('drivers_trial', 77.0726, 28.4591, $driver_id);
    //    $redis->geodist();
    $redis->pipeline(function ($pipe) {
        for ($i = 0; $i < 10; $i++) {
            $pipe->set("index_key:1", $i);
            $pipe->set("index_key:2", 45);
        }
    });


    if ($posts = $redis->get('drivers_trial:1')) {

        return p(json_decode($posts), 0);
    }
    if ($posts = $redis->get('index_key:2')) {

        p(json_decode($posts));
    }
}

//function update_booking_map_image_at_ride_end($booking_id, $latitude, $longitude, $booking_coordinates, $key)
//{
//    // Store ride end image.
//    if (!empty($booking_coordinates)) {
//        $drop_location_lat_long = json_decode($booking_coordinates, true);
//    }
//    $start = $latitude . ',' . $longitude;
//    if (count($drop_location_lat_long) > 1) {
//        $end = array_pop($drop_location_lat_long);
//        $finish = $end['latitude'] . ',' . $end['longitude'];
//        $count_waypoints = count($drop_location_lat_long); // CHECK FOR MULTIPLE WAYPOINTS or SINGLE WAYPOINT
//        $multiple_waypoints = array();
//        for ($j = 0; $j < $count_waypoints; $j++) {
//            $lat_long = $drop_location_lat_long[$j]['latitude'] . ',' . $drop_location_lat_long[$j]['longitude'];
//            $multiple_waypoints[] = $lat_long;
//        }
//        $waypoints = implode("|", $multiple_waypoints);
//        $data = GoogleController::GoogleStaticMultiplePointsImage($start, $finish, $waypoints, $key, "metric");
//        $image = $data['image'];
//        if (!empty($image)) {
//            $booking = Booking::Find($booking_id);
//            $booking->map_image = $image;
//            $booking->save();
//            return $image;
//        }
//    }
//}

function get_date($date)
{
    return date('d F Y', strtotime($date));
}

function set_date($date)
{
    $new_date = new DateTime($date);
    return $new_date->format('Y-m-d H:i:s');
}

function add_blank_option($arr_option = [], $blank_option = 'Select')
{
    $first_option = array('' => $blank_option);
    return $first_option + $arr_option;
}

function get_sos_list($merchant_id, $application, $id = null)
{
    if (!empty($merchant_id) && !empty($application)) {
        $list = Sos::where([['merchant_id', '=', $merchant_id], ['sosStatus', '=', 1], ['application', '=', $application]])
            ->where(function ($q) use ($id) {
                if (!empty($id)) {
                    $q->where('user_id', $id);
                }
                $q->orWhere('user_id', NULL);
            })
            ->get();
        return $list;
    }
    return [];
}

function success_response($message, $data = [])
{
    return response()->json(['result' => 1, 'message' => $message, 'data' => $data]);
}

function error_response($message, $data = [])
{
    return response()->json(['result' => 0, 'message' => $message]);
}

//GetReferCode
function getRandomCode($length = 5)
{
    $code = base_convert(sha1(uniqid(mt_rand())), 16, 36);
    $newCode = substr(str_replace(array('0', 'o', '1', 'i'), '', $code), 0, $length);
    $referCode = strtoupper($newCode);
    return $referCode;
}

function getTempDocUpload($expireDate, $currentDate, $reminderDate)
{
    if (strtotime($expireDate) > strtotime($currentDate) && strtotime($expireDate) < strtotime($reminderDate)) {
        return true;
    } else {
        return false;
    }
}


function booking_log($data)
{
    $log_data = array(
        'request_type' => $data['request_type'],
        'request_data' => $data['data'],
        'additional_notes' => $data['additional_notes'],
        'hit_time' => date('Y-m-d H:i:s')
    );
    \Log::channel('booking')->emergency($log_data);
}

function save_user_device_player_id($request)
{
    if (!empty($request['user_id'])) {
        //        \App\Models\User::where('id',$request['user_id'])->update(['unique_number' => $request['unique_number']]);
        $device_with_other_user = UserDevice::where('user_id', "!=", $request['user_id'])
            ->where('player_id', $request['player_id'])
            ->first();

        if (!empty($device_with_other_user->id)) {
            //delete player_id which had mapped with any other user device.
            $device_with_other_user->delete();
        }
        $device = UserDevice::where(['unique_number' => $request['unique_number'], 'package_name' => $request['package_name'], 'user_id' => $request['user_id']])->first();
        if (empty($device['id'])) {
            $device = new UserDevice;
            $device->user_id = $request['user_id'];
            $device->unique_number = $request['unique_number'];
            $device->apk_version = $request['apk_version'];
            $device->language_code = $request['language_code'];
            $device->manufacture = $request['manufacture'];
            $device->model = $request['model'];
            $device->device = $request['device'];
            $device->package_name = $request['package_name'];
            $device->operating_system = $request['operating_system'];
            $device->language_code = 'some language code';
        }
        $device->player_id = $request['player_id'];
        $device->save();
    }
}

function get_merchant_configuration($merchant_id = null)
{
    $taxi_company = get_taxicompany();
    $hotel = get_hotel();
    if (empty($merchant_id)) {
        if (!empty($taxi_company)) {
            $merchant_id = $taxi_company->merchant_id;
        } elseif (!empty($hotel)) {
            $merchant_id = $hotel->merchant_id;
        } else {
            $merchant_id = get_merchant_id();
        }
    }
    $config = MerchantModel::with('ApplicationConfiguration', 'BookingConfiguration')->where('id', $merchant_id)->first();
    return $config;
}

function get_package_type()
{
    return \Config::get('custom.package_type');
}

function get_driver_auto_verify_status($driver_id, $status = '', $for = 'doc')
{
    $auto_verify = $for == 'doc' ? 1 : 2;
    if (!empty($driver_id)) {
        $driver = Driver::Find($driver_id);
        if (isset($driver->Merchant->DriverConfiguration->auto_verify)) {
            $auto_verify = $driver->Merchant->DriverConfiguration->auto_verify;
            if ($status == 'final_status') {
                if ($for == 'doc') {
                    return $auto_verify == 1 ? 2 : 1; // 2 means verified // 1 means pending
                } elseif ($for == 'vehicle') {
                    //old status 1 means verified // 2 means pending
                    //new status 2 means verified // 1 means pending
                    return $auto_verify == 1 ? 2 : 1;
                }
            }
        }
        //        else{
        //            if($status == 'final_status')
        //            {
        //                if($for == 'doc')
        //                {
        //                    return   $auto_verify == 1 ? 2 : 1; // 2 means verified // 1 means pending
        //                }
        //                elseif($for =='vehicle')
        //                {
        //                    return   $auto_verify == 1 ? 1 : 2; // 1 means verified // 2 means pending
        //                }
        //            }
        //        }
    }
    return $auto_verify;
}


function get_driver_multi_existing_vehicle_status($driver_id)
{
    $vehicle_active_status = 1; // active
    if (has_driver_multiple_or_existing_vehicle($driver_id) == true) {
        $vehicle_active_status = 2; // inactive
    }
    return $vehicle_active_status;
}

function has_driver_multiple_or_existing_vehicle($driver_id = null, $merchant_id = null, $by = 'driver')
{
    $return = false;
    if ($by == 'merchant' && !empty($merchant_id)) {
        $data = Merchant::Find($merchant_id);
    } else {
        $driver = Driver::Find($driver_id);
        $data = $driver->Merchant;
    }
    if ($data->demo == 1 && $by == 'merchant') {
        return false;
    }
    if ($data->Configuration->existing_vehicle_enable == 1 || ($data->Configuration->add_multiple_vehicle == 1) && $data->demo != 1) {
        $return = true;
    }
    return $return;
}

function get_driver_document_details($driver_id, $return_type = 'status', $document_type = 'any', $document_status = 4, $vehicle_id = null)
{
    $return = false;
    $personal_document_count = [];
    $vehicle_document_count = [];
    if ($document_type == 'personal' || $document_type == 'any') {
        $personal_document_count = DB::table('driver_documents as dd')
            ->join('drivers as d', 'dd.driver_id', '=', 'd.id')
            ->join('documents as doc', 'dd.document_id', '=', 'doc.id')
            ->where(function ($q) use ($document_status) {
                if ($document_status == 4) {
                    $q->where('doc.expire_date', 1);
                }
            })
            ->where(function ($q) use ($document_status) {
                if ($document_status == 4) {
                    $q->where('dd.document_verification_status', $document_status); // 1 means pending, 4 means expired
                    $q->orWhere('dd.document_verification_status', 3); // rejected case
                } else {
                    $q->where('dd.document_verification_status', $document_status); // 1 means pending, 4 means expired
                }
            })
            ->where('dd.status', 1)
            ->where('d.id', $driver_id)
            ->select('dd.id')
            ->get()->toArray(); // driver_id
    }
    if ($document_type == 'vehicle' || $document_type == 'any') {
        $vehicle_document_count = DB::table('driver_vehicle_documents as dvd')
            ->join('driver_driver_vehicle as ddv', 'dvd.driver_vehicle_id', '=', 'ddv.driver_vehicle_id')
            ->join('driver_vehicles as dv', 'ddv.driver_vehicle_id', '=', 'dv.id')
            ->join('drivers as d', 'dv.driver_id', '=', 'd.id')
            ->join('documents as doc', 'dvd.document_id', '=', 'doc.id')
            // ->where('dvd.document_verification_status',$document_status)// 4 means expired
            ->where(function ($q) use ($document_status) {
                if ($document_status == 4) {
                    $q->where('doc.expire_date', 1);
                }
            })
            ->where(function ($q) use ($document_status) {
                if ($document_status == 4) {
                    $q->where('dvd.document_verification_status', $document_status); // 1 means pending, 4 means expired
                    $q->orWhere('dvd.document_verification_status', 3); // rejected case
                } else {
                    $q->where('dvd.document_verification_status', $document_status); // 1 means pending, 4 means expired
                }
            })
            ->where(function ($q) use ($vehicle_id) {
                if (!empty($vehicle_id)) {
                    $q->where('ddv.driver_vehicle_id', $vehicle_id);
                }
            })
            ->where('d.id', $driver_id)
            ->where('dvd.status', 1)
            ->select('dvd.id')
            ->get()->toArray();
    }
    if ((count($personal_document_count) > 0 || count($vehicle_document_count) > 0) && $return_type == 'status') {
        $return = true;
    }
    return $return;
}

function check_driver_document($driver_id, $type = 'any', $vehicle_id = null, $document_verification_status = null, $status = '')
{
    $driver = Driver::Find($driver_id);
    if ($driver->Merchant->demo != 1) {
        if (($type == 'vehicle' || $type == 'any') && $driver->segment_group_id == 1) {
            $driver_vehicle = !empty($vehicle_id) ? DriverVehicle::where('id', $vehicle_id)->get() : $driver->DriverVehicle;
            $driver_vehicle = collect($driver_vehicle->values());
            $vehicle_type_id = $driver_vehicle[0]->vehicle_type_id;
            $country_area_id = $driver->country_area_id;
            $country_area = CountryArea::select('id')->whereHas('VehicleDocuments', function ($q) use ($vehicle_type_id, $country_area_id) {
                $q->where('vehicle_type_id', $vehicle_type_id);
                $q->where('country_area_id', $country_area_id);
            })
                ->with(['VehicleDocuments' => function ($q) use ($vehicle_type_id, $country_area_id) {
                    $q->where('vehicle_type_id', $vehicle_type_id);
                    $q->where('country_area_id', $country_area_id);
                }])
                ->Find($country_area_id);
            // p($country_area);
            if (!empty($country_area)) {
                $country_area_vehicle_documents = !empty($country_area->VehicleDocuments) ? $country_area->VehicleDocuments : NULL;
                // p($country_area_vehicle_documents->count());
                //p($country_area_vehicle_documents);
                if (!empty($country_area_vehicle_documents)) {
                    // $vehicle_document = $country_area_vehicle_documents->where('documentNeed', 1)->pluck("id")->toArray();
                    $vehicle_document_ids = $country_area_vehicle_documents->where('documentNeed', 1)->pluck("id")->toArray();
                    if (count($vehicle_document_ids) > 0) {
                        if (isset($driver_vehicle)) {
                            if(isset($driver->Merchant->Configuration->add_multiple_vehicle) && $driver->Merchant->Configuration->add_multiple_vehicle == 1){ 
                                
                                $driver_vehicle_document = DriverVehicleDocument::whereHas('Document', function ($q) {
                                    $q->where('documentNeed', 1);
                                })->where('driver_vehicle_id', $vehicle_id);
                                
                                if($driver->signupstep == 9){
                                    $driver_vehicle_document->whereIn('document_verification_status', [1,3,4]);
                                }
                                
                                $driver_vehicle_document = $driver_vehicle_document
                                ->where(function ($q) use ($document_verification_status, $status) {
                                    if (!empty($document_verification_status)) {
                                        $q->where('document_verification_status', $document_verification_status);
                                        if ($status == 'reject' || $status == 'expired') {
                                            $q->orWhere('document_verification_status', 2);
                                        }
                                    }
                                });
                                
                                $driver_vehicle_document= $driver_vehicle_document->where('status', 1) // only active
                                ->pluck("document_id")
                                ->toArray();

                                if($driver->signupstep == 9){
                                    if (!empty(array_diff($vehicle_document_ids, $driver_vehicle_document))) {
                                        return false;
                                    }
                                }else{
                                    // return true;
                                    if (empty(array_diff($vehicle_document_ids, $driver_vehicle_document))) {
                                        return true;
                                    } else {
                                        return false;
                                    }

                                }
                                
                            }else{
                                $driver_vehicle_document = DriverVehicleDocument::whereHas('Document', function ($q) {
                                    $q->where('documentNeed', 1);
                                })->where('driver_vehicle_id', $vehicle_id)
                                    ->where(function ($q) use ($document_verification_status, $status) {
                                        if (!empty($document_verification_status)) {
                                            $q->where('document_verification_status', $document_verification_status);
                                            //                                    if ($reject == 'reject' && $document_verification_status == 1) {
                                            if ($status == 'reject' || $status == 'expired') {
                                                $q->orWhere('document_verification_status', 2);
                                            }
                                        }
                                    })
                                
                                ->where('status', 1) // only active
                                ->pluck("document_id")
                                ->toArray();
                                                                
                                if (!empty(array_diff($vehicle_document_ids, $driver_vehicle_document))) {
                                    return false;
                                }

                            }
                        }
                    }
                }
            }
        }

        if (($type == 'segment' || $type == 'any') && $driver->segment_group_id == 2) {
            $country_area = CountryArea::findOrFail($driver->country_area_id);
            $country_area_segment_documents = $country_area->SegmentDocument;
            if (!empty($country_area_segment_documents)) {
                $segment_document = $country_area_segment_documents->where('documentNeed', 1)->count();
                if ($segment_document > 0) {
                    $driver_segment_document_count = DriverSegmentDocument::whereHas('Document', function ($q) {
                        $q->where('documentNeed', 1);
                    })->where('driver_id', $driver->id)
                        ->where(function ($q) use ($document_verification_status, $status) {
                            if (!empty($document_verification_status)) {
                                $q->where('document_verification_status', $document_verification_status);

                                if ($status == 'reject' || $status == 'expired') {
                                    $q->orWhere('document_verification_status', 2);
                                }
                            }
                        })
                        ->where('status', 1) // only active
                        ->count();
                    if ($segment_document > $driver_segment_document_count) {
                        return false;
                    }
                }
            }
        }
        if ($type == 'personal' || $type == 'any') {
            $country_area = CountryArea::findOrFail($driver->country_area_id);
            $country_area_driver_documents = $country_area->documents;
            if (!empty($country_area_driver_documents)) {
                // $driver_document = $country_area_driver_documents->where('documentNeed', 1)->count();
                if(isset($driver->Merchant->ApplicationConfiguration->local_citizen_foreigner_documents) && $driver->Merchant->ApplicationConfiguration->local_citizen_foreigner_documents == 1){ 
                    $driver_document_ids = $country_area->documents
                    ->filter(function ($doc) use($driver) {
                        if($driver->citizen_type == 1){
                           return $doc->pivot->document_type == 1 && $doc->documentNeed == 1;
                        }else{
                           return $doc->pivot->document_type == 2 && $doc->documentNeed == 1;
                        }
                       
                    })
                    ->pluck('id')
                    ->toArray();
                }else{
                    $driver_document_ids = $country_area_driver_documents->where('documentNeed', 1)->pluck("id")->toArray();                
                }
                if ($driver_document_ids > 0) {
                    $driver_document_needs = DriverDocument::whereHas('Document', function ($q) {
                        $q->where('documentNeed', 1);
                    })->where('driver_id', $driver->id)
                        ->where(function ($q) use ($document_verification_status, $status) {
                            if (!empty($document_verification_status)) {
                                $q->where('document_verification_status', $document_verification_status);

                                if ($status == 'reject' || $status == 'expired') {
                                    $q->orWhere('document_verification_status', 2);
                                }
                            }
                        })
                        ->where('status', 1) // only active
                        ->get()
                        ->pluck("document_id")
                        ->toArray();
                        // ->count();
                    // if ($driver_document > $driver_document_count) {
                    //     return false;
                    // }
                    if (!empty(array_diff($driver_document_ids, $driver_document_needs))) {
                        return false;
                    }
                }
            }
        }
    }
    return true;
}

function check_user_document($user_id, $type = 'any', $vehicle_id = null, $document_verification_status = null, $status = '')
{
    $user = \App\Models\User::Find($user_id);
    if ($user->Merchant->demo != 1) {
        if ($type == 'vehicle' || $type == 'any') {
            $user_vehicle = !empty($vehicle_id) ? \App\Models\UserVehicle::where('id', $vehicle_id)->get() : $user->OwnerVehicle;
            $user_vehicle = collect($user_vehicle->values());
            $country_area = CountryArea::find($user->country_area_id);
            // p($country_area);
            if (!empty($country_area)) {
                $country_area_documents = !empty($country_area->Documents) ? $country_area->Documents : NULL;
                // p($country_area_documents->count());
                //p($country_area_documents);
                if (!empty($country_area_documents)) {
                    $vehicle_document = $country_area_documents->where('documentNeed', 1)->count();
                    if ($vehicle_document > 0) {
                        if (isset($driver_vehicle)) {
                            $user_vehicle_document = \App\Models\UserVehicleDocument::whereHas('Document', function ($q) {
                                $q->where('documentNeed', 1);
                            })
                                ->where('user_vehicle_id', $vehicle_id)
                                ->where(function ($q) use ($document_verification_status, $status) {
                                    if (!empty($document_verification_status)) {
                                        $q->where('document_verification_status', $document_verification_status);
                                        if ($status == 'reject' || $status == 'expired') {
                                            $q->orWhere('document_verification_status', 2);
                                        }
                                    }
                                })
                                ->where('status', 1) // only active
                                ->count();
                            if ($vehicle_document > $user_vehicle_document) {
                                return false;
                            }
                        }
                    }
                }
            }
        }

        if ($type == 'personal' || $type == 'any') {
            $country = \App\Models\Country::findOrFail($user->country_id);
            $country_user_documents = $country->documents;
            if (!empty($country_user_documents)) {
                $user_document = $country_user_documents->where('documentNeed', 1)->count();
                if ($user_document > 0) {
                    $user_document_count = \App\Models\UserDocument::whereHas('Document', function ($q) {
                        $q->where('documentNeed', 1);
                    })->where('user_id', $user->id)
                        ->where(function ($q) use ($document_verification_status, $status) {
                            if (!empty($document_verification_status)) {
                                $q->where('document_verification_status', $document_verification_status);

                                if ($status == 'reject' || $status == 'expired') {
                                    $q->orWhere('document_verification_status', 2);
                                }
                            }
                        })
                        ->where('status', 1) // only active
                        ->count();
                    if ($user_document > $user_document_count) {
                        return false;
                    }
                }
            }
        }
    }
    return true;
}

function driver_all_document_status($driver_id, $vehicle_id = null)
{
    $final_document_status = false;
    $driver = Driver::select('id', 'merchant_id')->find($driver_id);
    if ($driver->Merchant->demo != 1) {
        $pending_document_status = check_driver_document($driver_id, $type = 'any', $vehicle_id);
        $expired_document_status = get_driver_document_details($driver_id, 'status', 'any', 4, $vehicle_id);
        if ($expired_document_status == true || $pending_document_status == false) {
            $final_document_status = true;
        }
    }
    return $final_document_status;
}

function get_online_and_busy_drivers($merchant_id)
{
    return DB::table('drivers as d')->where('d.login_logout', 1)->where('d.free_busy', 1)
        ->where('d.online_offline', 1)->where('d.merchant_id', $merchant_id)->count();
}

function get_driver_verified_vehicle($driver_id, $vehicle_id)
{
    $active_vehicle_count = DB::table('drivers as d')
        ->join('driver_driver_vehicle as ddv', 'd.id', '=', 'ddv.driver_id')
        ->join('driver_vehicles as dv', 'ddv.driver_vehicle_id', '=', 'dv.id')
        ->where('d.id', $driver_id)
        ->where('dv.id', '!=', $vehicle_id)
        ->where('vehicle_verification_status', 2)->count();
    return $active_vehicle_count;
}

function get_verified_vehicle($driver_id)
{
    $verified_vehicle = DB::table('drivers as d')
        ->join('driver_driver_vehicle as ddv', 'd.id', '=', 'ddv.driver_id')
        ->join('driver_vehicles as dv', 'ddv.driver_vehicle_id', '=', 'dv.id')
        ->where('d.id', $driver_id)
        ->where('vehicle_verification_status', 2)
        ->select('dv.id')
        ->first();
    return $verified_vehicle;
}


function get_taxicompany($id = false)
{
    if (Auth::guard('taxicompany')->check()) {
        if($id){
            return Auth::user('taxicompany')->id;
        }
        else{
            return Auth::user('taxicompany');
        }
    } else {
        return null;
    }
}

function get_agent($id = false)
{
    if (Auth::guard('agent')->check()) {
        return (Auth::user('agent')->parent_id != 0) ? Auth::user('agent')->parent_id : (($id) ? Auth::user('agent')->id : Auth::user('agent'));
    } else {
        return null;
    }
}

function get_driver_agency($id = true)
{
    if (Auth::guard('driver-agency')->check()) {
        if ($id == true) { // return only id of merchant
            return Auth::user('driver-agency')->id;
        } else { // return object of loggedin user
            return Auth::user('driver-agency');
        }
    } else {
        return null;
    }
}

function get_taxicompany_wallet($id)
{
    if ($id != null) {
        $taxicompany = \App\Models\TaxiCompany::select('wallet_money')->find($id);
        return !empty($taxicompany) ? $taxicompany->wallet_money : null;
    } else {
        return null;
    }
}

function get_hotel($id = false)
{
    if (Auth::guard('hotel')->check()) {
        if(Auth::user('hotel')->parent_id != 0)
        {
            return Auth::user('hotel')->parent_id;
        }
        else
        {
            if($id)
            {
                return  Auth::user('hotel')->id;
            }
            else
            {
                return  Auth::user('hotel');
            }
        }
//        return (Auth::user('hotel')->parent_id != 0) ? Auth::user('hotel')->parent_id : (($id) ? Auth::user('hotel')->id : Auth::user('hotel'));
    } else {
        return null;
    }
}

function check_permission($type, $permission, $hasOne = false, $string_file = "")
{
    //type 1 = merchant, type 2 = corporate, type 3 = taxicompany
    switch ($type) {
        case 1:
            $redirect = 'merchant.dashboard';
            $authUser = 'merchant';
            break;
        case 2:
            $redirect = 'corporate.dashboard';
            $authUser = 'corporate';
            break;
        case 3:
            $redirect = 'taxicompany.dashboard';
            $authUser = 'taxicompany';
            break;
        default:
            $redirect = '/';
            $authUser = 'merchant';
            break;
    }
    $isRedirect = false;
    $redirectBack = '';
    //    $user = Auth::user($authUser);
    if (is_array($permission)) {
        if ($hasOne) {
            if (!Auth::user($authUser)->hasAnyPermission($permission)) {
                $redirectBack = Redirect::route($redirect)->withErrors(trans("all_in_one.permission_denied"));
                $isRedirect = true;
            }
        } else {
            if (!Auth::user($authUser)->hasAllPermissions($permission)) {
                $redirectBack = Redirect::route($redirect)->withErrors(trans("all_in_one.permission_denied"));
                $isRedirect = true;
            }
        }
    } else {
        if (!Auth::user($authUser)->can($permission)) {
            $redirectBack = Redirect::route($redirect)->withErrors(trans("all_in_one.permission_denied"));
            $isRedirect = true;
        }
    }
    return array('isRedirect' => $isRedirect, 'redirectBack' => $redirectBack);
}

function get_permission_segments($type = 1, $is_slag = false, $for_filter_segments = [])
{
    $return_segments = [];
    switch ($type) {
        case 1:
            $authUser = 'merchant';
            break;
        case 2:
            $authUser = 'corporate';
            break;
        case 3:
            $authUser = 'taxicompany';
            break;
        default:
            $authUser = 'merchant';
            break;
    }
    $segments = \App\Models\Segment::get()->pluck("slag")->toArray();
    if (!empty($segments)) {
        foreach ($segments as $segment) {
            if (Auth::user($authUser)->can($segment)) {
                array_push($return_segments, $segment);
            }
        }
        if (Auth::user($authUser)->can('HANDYMAN')) {
            $handyman_segments = \App\Models\Segment::where("segment_group_id", 2)->get()->pluck("slag")->toArray();
            $return_segments = array_merge($return_segments, $handyman_segments);
        }
    }
    $merchant_id = get_merchant_id();
    if (!empty($return_segments)) {
        $arr_segment = [];
        $return_segments = \App\Models\Segment::whereIn('slag', $return_segments)->get();
        foreach ($return_segments as $segment) {
            if ($is_slag) {
                $arr_segment[$segment['id']] = $segment->slag; // $segment->slag;
            } else {
                $arr_segment[$segment['id']] = !empty($segment->Name($merchant_id)) ? $segment->Name($merchant_id) : $segment->slag; // $segment->slag;
            }
        }
        $return_segments = $arr_segment;
    }
    if (!empty($for_filter_segments)) {
        foreach ($for_filter_segments as $key => $segment) {
            if (!in_array($segment, $return_segments)) {
                unset($for_filter_segments[$key]);
            }
        }
        $return_segments = $for_filter_segments;
    }
    return $return_segments;
}

function getRequestTimes($merchant_id)
{
    $bookingConfig = BookingConfiguration::where('merchant_id', $merchant_id)->first();
    $driverRequestTime = $bookingConfig->driver_request_timeout;
    $data = array(
        'user_request_timeout' => $driverRequestTime * 3,
        'ride_radius_increase_api_call_time' => $driverRequestTime
    );
    return $data;
}

function getSendDriverRequestLimit($booking)
{
    $booking_config = BookingConfiguration::where('merchant_id', $booking->merchant_id)->latest()->first();
    switch ($booking->service_type_id) {
        case '1':
            $limit = $booking->booking_type == 1 ? $booking_config->normal_ride_now_request_driver : $booking_config->normal_ride_later_request_driver;
            break;
        case '2':
            $limit = $booking->booking_type == 1 ? $booking_config->rental_ride_now_request_driver : $booking_config->rental_ride_later_request_driver;
            break;
        case '4':
            $limit = $booking->booking_type == 1 ? $booking_config->outstaion_ride_now_request_driver : $booking_config->outstation_request_driver;
            break;
        case '5':
            $limit = $booking->booking_type == 1 ? $booking_config->pool_now_request_driver : null;
            break;
        default:
            $limit = null;
            break;
    }
    return $limit;
}

function google_api_log($data)
{
    $log_data = array(
        'request_type' => $data['request_type'],
        'request_data' => $data['data'],
        'additional_notes' => $data['additional_notes'],
        'hit_time' => date('Y-m-d H:i:s')
    );
    \Log::channel('google_api')->emergency($log_data);
}


function map_box_api_log($data)
{
    $log_data = array(
        'request_type' => $data['request_type'],
        'request_data' => $data['data'],
        'additional_notes' => $data['additional_notes'],
        'hit_time' => date('Y-m-d H:i:s')
    );
    \Log::channel('map_box_api_log')->emergency($log_data);
}
//function get_merchant_notification_provider($merchant_id = null)
//{
//    $merchant_id = empty($merchant_id) ? get_merchant_id() : $merchant_id;
//    $return = NULL;
//    if (!empty($merchant_id)) {
//        $merchant = Merchant::find($merchant_id);
//        $fire_base = false;
//        // 1: onesignal, 2: firebase
//        if (isset($merchant->Configuration->push_notification_provider) && $merchant->Configuration->push_notification_provider == 2) {
//            $fire_base = true;
//        }
//        if (!empty($merchant->Onesignal)) {
//            $return = $merchant->Onesignal;
//            $return->fire_base = $fire_base;
//        }
//        return $return;
//    }
//}

function get_merchant_notification_provider($merchant_id = null, $id = null, $type = null, $return = 'status')
{
    $merchant_id = empty($merchant_id) ? get_merchant_id() : $merchant_id;
    if (!empty($merchant_id)) {
        $merchant =  Merchant::find($merchant_id);
        $fire_base = false;
        $notification_provider = 1;
        // 1: onesignal, 2: firebase, 3: both
        if (isset($merchant->Configuration->push_notification_provider) && $merchant->Configuration->push_notification_provider == 2) {
            $fire_base = true;
            $notification_provider = 2;
        } elseif (isset($merchant->Configuration->push_notification_provider) && $merchant->Configuration->push_notification_provider == 3) {
            $notification_provider = 3;
            if (!empty($id) && !empty($type)) {
                $arr_firebase_country = $merchant->Country->where('isoCode', 'EGP');
                $arr_firebase_country = array_pluck($arr_firebase_country, 'id');
                if ($type == 'user') {
                    $country = DB::table('users')->select('country_id')->where([['merchant_id', $merchant_id], ['id', $id]])->first();
                    $country_id = $country->country_id;
                } else {
                    $country = DB::table('drivers as d')
                        ->select('country_id')->where([['merchant_id', $merchant_id], ['d.id', $id]])->first();
                    $country_id = $country->country_id;
                }
                if (in_array($country_id, $arr_firebase_country)) {
                    $fire_base = true;
                }
            }
        }
        if ($return == 'full') {
            $return = $merchant->Onesignal;
        } else {
            $return = new stdClass;
        }
        $return->fire_base = $fire_base;
        $return->push_notification_provider = $notification_provider;
        return $return;
    }
}

function getAdditionalInfo()
{
    $data = array(
        'parameter_name' => 'Temperature',
        'display' => true
    );
    return $data;
}

function get_merchant_segment($with_taxi = true, $merchant_id = null, $segment_group_id = NULL, $sub_group_for_admin = NULL)
{
    if (empty($merchant_id)) {
        $merchant_id = get_merchant_id();
    }
    $segments = Merchant::with(['Segment' => function ($q) use ($merchant_id, $segment_group_id, $with_taxi, $sub_group_for_admin) {
        $q->select('id', 'slag', 'segment_id', 'name');
        if (!empty($segment_group_id)) {
            if(is_array($segment_group_id)){
                $q->whereIn('segment_group_id', $segment_group_id);
            }else{
                $q->where('segment_group_id', $segment_group_id);
            }
        }
        if ($with_taxi == false) {
            $q->whereNotIn('id', [1, 2]);
        }
        if (!empty($sub_group_for_admin)) {
            $q->where('sub_group_for_admin', $sub_group_for_admin);
        }
    }])
        ->whereHas('Segment', function ($q) use ($merchant_id, $with_taxi, $sub_group_for_admin) {
            $q->where('merchant_id', $merchant_id);
            if ($with_taxi == false) {
                $q->whereNotIn('id', [1, 2]);
            }
            if (!empty($sub_group_for_admin)) {
                $q->where('sub_group_for_admin', $sub_group_for_admin);
            }
        })
        ->select('id')
        ->first();
    $arr_segment = [];

    if (!empty($segments->Segment)) {
        foreach ($segments->Segment as $segment) {
            $arr_segment[$segment['id']] = !empty($segment->Name($merchant_id)) ? $segment->Name($merchant_id) : $segment->slag; // $segment->slag;
        }
    }
    return $arr_segment;
}

function get_merchant_country($arr_list)
{
    $arr_country = [];
    foreach ($arr_list as $country) {
        $arr_country[$country['id']] = $country['CountryName'];
    }
    return $arr_country;
}

function get_merchant_document($arr_list)
{
    $arr_document = [];
    foreach ($arr_list as $document) {
        $arr_document[$document['id']] = $document['DocumentName'];
    }
    return $arr_document;
}

function get_merchant_vehicle($arr_list)
{
    $arr_vehicle = [];
    foreach ($arr_list as $vehicle) {
        $arr_vehicle[$vehicle['id']] = $vehicle['VehicleTypeName'];
    }
    return $arr_vehicle;
}

function get_merchant_delivery_type($arr_list)
{
    $arr_delivery_type = [];
    foreach ($arr_list as $delivery_type) {
        $arr_delivery_type[$delivery_type['id']] = $delivery_type['name'];
    }
    return $arr_delivery_type;
}

function get_merchant_package($arr_list)
{
    $arr_package_type = [];
    foreach ($arr_list as $package_type) {
        $arr_package_type[$package_type['id']] = $package_type['PackageName'];
    }
    return $arr_package_type;
}

function get_bill_type()
{
    $arr_list = App\Models\BillPeriod::get();
    $arr_type = [];
    foreach ($arr_list as $type) {
        $arr_type[$type['id']] = $type['name'];
    }
    return $arr_type;
}

function get_status($order = true, $string_file = "")
{
    if ($order == false) {
        $return = array(
            '1' => trans("$string_file.no"),
            '2' => trans("$string_file.yes"),
        );
    } else {
        $return = array(
            '1' => trans("$string_file.yes"),
            '2' => trans("$string_file.no"),
        );
    }
    return $return;
}

function get_days($string_file = "")
{
    return
        array(
            '0' => trans("$string_file.sunday"),
            '1' => trans("$string_file.monday"),
            '2' => trans("$string_file.tuesday"),
            '3' => trans("$string_file.wednesday"),
            '4' => trans("$string_file.thursday"),
            '5' => trans("$string_file.friday"),
            '6' => trans("$string_file.saturday"),
        );
    //\Config::get('custom.days');
}

function get_enable($string_file = "")
{
    return array(
        '1' => trans("$string_file.enable"),
        '2' => trans("$string_file.disable"),
    );
    //        \Config::get('custom.status');
}

function get_service_vehicle($area_id, $service_type_id)
{
    $vehicle = [];
    if (!empty($area_id) && !empty($service_type_id)) {
        $vehicle = DB::table('country_area_vehicle_type')->where([['country_area_id', '=', $area_id], ['service_type_id', $service_type_id]])->select('vehicle_type_id')->get()->toArray();
        $vehicle = array_pluck($vehicle, 'vehicle_type_id');
    }
    return $vehicle;
}

function get_price_parameter($string_file, $on = "add")
{
    $merchant_type = [
        '' => trans("$string_file.select"),
        10 => trans("$string_file.base_fare_type"),
        1 => trans("$string_file.per_km_mile"),
        8 => trans("$string_file.per_minute"),
        2 => trans("$string_file.per_hour"),
        3 => trans("$string_file.standard"),
        9 => trans("$string_file.wait_type"),
        11 => trans("$string_file.discount"),
        12 => trans("$string_file.promo_code_discount"),
        13 => trans("$string_file.tax"),
        6 => trans("$string_file.dead_mileage"),
        23 => trans("$string_file.additional_fare")
    ];

    $super_admin_type = [
        14 => trans("$string_file.ac_charges"),
        15 => trans("$string_file.outstation_distance_charges"),
        17 => trans("$string_file.insurance"),
        18 => trans("$string_file.waiting_type_during_ride"),
        16 => trans("$string_file.minimum_fare_type"),
        19 => trans("$string_file.booking_fee_type"),
        20 => trans("$string_file.wait_type_fixed_charges"),
        21 => trans("$string_file.additional_drop_fair_type"),
    ];
    if ($on == "edit") {
        return $merchant_type + $super_admin_type;
    }

    return $merchant_type;
}

function merchant_price_type($merchant_rate_card)
{
    $data = [];
    if (!empty($merchant_rate_card)) {
        foreach ($merchant_rate_card as $value) {
            $data[$value->id] = $value->name;
        }
    }
    return $data;
}

function get_commission_type($string_file = "")
{
    return ["1" => trans("$string_file.prepaid"), "2" => trans("$string_file.postpaid")];
}

function get_commission_method($string_file = "")
{
    return ["1" => trans("$string_file.flat"), "2" => trans("$string_file.percentage")];
}

function get_on_off($string_file = "")
{
    return ["1" => trans("$string_file.on"), "0" => trans("$string_file.off")];
}

// its handyman segment price card type
function get_price_card_type($calling_from = '', $price_type_config = "BOTH", $string_file = "", $return_slug = false)
{
    $price_type = [];
    if ($price_type_config == "FIXED") {
        $price_type = $return_slug ? [1 => "FIXED"] : [1 => trans("$string_file.fixed")];
        //        $price_type = [1 => trans("$string_file.fixed")];
    } elseif ($price_type_config == "HOURLY") {
        $price_type = $return_slug ? [2 => "HOURLY"] : [2 => trans("$string_file.hourly")];
        //        $price_type = [2 => trans("$string_file.hourly")];
    } else {
        if ($return_slug) {
            $price_type = [1 => "FIXED", 2 => "HOURLY"];
        } else {
            $price_type = [1 => trans("$string_file.fixed"), 2 => trans("$string_file.hourly")];
        }
        //        $price_type = [1 => trans("$string_file.fixed"), 2 => trans("$string_file.hourly")];
    }
    if ($calling_from == 'api') {
        foreach ($price_type as $id => $value) {
            $arr_price_type[] = ['id' => $id, 'value' => $value];
        }
        return $arr_price_type;
    }
    return $price_type;
}

function get_segment_group()
{
    $arr_group = App\Models\SegmentGroup::get();
    $arr = [];
    foreach ($arr_group as $group) {
        $arr[$group->id] = $group->group_name;
    }
    return $arr;
    //    return array(
    //        '1' => 'Vehicle Based Services(Taxi, Delivery etc)',
    //        '2' => 'Helper/Helping Based Services(Plumber, Cleaning, Painting etc)',
    //    );
}

function formatted_date($date)
{
    return date('Y-m-d', strtotime($date));
}

function is_merchant_segment_exist($segment, $for_all = false)
{
    $merchant_segments = helperMerchant::MerchantSegments();
    $result = false;
    if ($for_all) {
        $resultObj = array_intersect($merchant_segments, $segment);
        if (count($segment) == count($resultObj)) {
            $result = true;
        }
    } else {
        $result = !empty(array_intersect($merchant_segments, $segment));
    }
    return $result;
}

//function get_merchant_segment_group(){
//    $segment_group = \App\Http\Controllers\Helper\Merchant::MerchantSegments(2);
//    return $segment_group;
//}

function round_number($num, $decimals = 2)
{
    return number_format((float) $num, $decimals, '.', '');
}

function get_product_status($calling_from = "web", $string_file = "")
{
    $ava = trans("$string_file.available");
    $not = trans("$string_file.not");
    if ($calling_from == "app") {
        $return = [['key' => 1, 'value' => $ava], ['key' => 2, 'value' => $not . ' ' . $ava]];
    } else {
        $return = array('1' => $ava, '2' => $not . ' ' . $ava);
    }
    return $return;
}

function get_active_status($calling_from = "web", $string_file = "")
{
    $act = trans("$string_file.active");
    $inact = trans("$string_file.inactive");
    if ($calling_from == "app") {
        $return = [['key' => 1, 'value' => $act], ['key' => 2, 'value' => $inact]];
    } else {
        $return = array('1' => $act, '2' => $inact);
    }
    return $return;
}

function get_food_type($string_file, $calling_from = "web")
{
    $veg = trans("$string_file.veg");
    $non_veg = trans("$string_file.non_veg");
    $including_egg = trans("$string_file.including_egg");
    //    ,['key'=>3, 'value'=>$including_egg]
    if ($calling_from == "app") {
        $return = [['key' => 1, 'value' => $veg], ['key' => 2, 'value' => $non_veg]];
    } else {
        $return = array('1' => $veg, '2' => $non_veg);
    }
    return $return;
}

function is_demo_data($string, $merchant_object = NULL, $merchant_id = NULL)
{
    if (empty($merchant_object) && !empty($merchant_id)) {
        $merchant_object = Merchant::select('demo','partner_id')->find($merchant_id);
    }
    // check merchant data
    $merchantLoggedIn = Auth::guard("merchant")->user();
    if(!empty($merchantLoggedIn)){
        if(!empty($merchantLoggedIn->parent_id) && $merchantLoggedIn->demo == 1){
            $return_string = "********" . substr($string, -2);
        }else{
            $return_string = $string;
        }
    } elseif (!empty($merchant_object) && $merchant_object->demo == 1 && !empty($merchant_object->partner_id)) {
        $return_string = "********" . substr($string, -2);
    } else {
        $return_string = $string;
    }
    return $return_string;
}

function get_narration_value($narration_for, $narration, $merchant_id, $id = NULL, $receipt = NULL, $amount = NULL, $user_name = NULL)
{

    $get_string = new GetString($merchant_id);
    $string_file = $get_string->getStringFileText();
    $description = "";
    // common strings
    $no_description = trans("$string_file.no_description");
    $description_admin = trans("$string_file.wallet_recharged_by_admin");
    $description_self_credit = trans("$string_file.wallet_recharged_successfully");
    switch ($narration_for) {
        case "DRIVER":
            switch ($narration) {
                case "1":
                    $description = $description_admin;
                    //                    trans('api.message44');
                    break;
                case "2":
                    $description = $description_self_credit;
                    //                $description = trans('api.message45');
                    break;
                case "3":
                    //trans("$string_file.company").' '.
                    //                    $description = trans("$string_file.commission_of_ride_id") . ' #' . $id;
                    $description = trans("$string_file.ride_amount_debited") . $id;
                    //                $description = trans('api.message46') .' '. $booking_id;
                    break;
                case "4":
                    // In this cash, booking id is package id
                    $subscription_package_id = $id;
                    $booking_id = NULL;
                    $description = trans("$string_file.bought_subscription_package") . ' ' . $subscription_package_id;
                    break;
                case "5":
                    $description = trans("$string_file.money_added_in_wallet") . '(' . trans("$string_file.cashback") . ')';
                    break;
                case "6":
                    $description = trans("$string_file.ride_amount_credited") . $id;
                    break;
                case "7":
                    $description = $receipt;
                    break;
                case "8":
                    $description = trans("$string_file.cancelled_ride_amount_debited") . $id;
                    break;
                case "9":
                    $description = trans('api.reward_point_redeem_credit');
                    break;
                case "10":
                    $description = trans("$string_file.cashout_amount_deducted");
                    break;
                case "11":
                    $description = trans("$string_file.cancelled_ride_amount_credited") . $id;
                    break;
                case "12":
                    $description = trans("$string_file.user_old_outstanding_deducted");
                    break;
                case "13":
                    $description = trans("$string_file.order_amount_debited") . $id;
                    //                    $description = trans("$string_file.order_commission_deducted");
                    break;
                case "14":
                    $description = trans("$string_file.order_amount_credited") . $id;
                    //                    $description = trans("$string_file.order_commission_received");
                    break;
                case "15":
                    $description = trans("$string_file.cashout_request_rejected_refund_amount");
                    break;
                case "16":
                    $description = trans("$string_file.tip_credited_to_driver");
                    break;
                case "17":
                    $description = trans("$string_file.tax_amount_deducted").$id;
                    break;
                case "18":
                    $description = trans("$string_file.amount_debited_by_admin");
                    break;
                case "19":
                    $description = trans("$string_file.cancelled_order_amount_credited") . $id;
                    break;
                case "20":
                    $description = trans("$string_file.booking_amount_debited") . $id;
                    break;
                case "21":
                    $description = trans("$string_file.booking_amount_credited") . $id;
                    break;
                case "22":
                    $description = trans("$string_file.referral_amount_credit");
                    break;
                case "23":
                    $description = trans("$string_file.you_have_received_amount_from", ['AMOUNT' => $amount, 'FROM' => $user_name]);
                    break;
                case "24":
                    $description = trans("$string_file.cashout_amount_debited");
                    break;
                case "25":
                    $description = trans("$string_file.tranaction_amount_settled_through_bank");
                    break;
                case "26":
                    $description = trans("$string_file.ride_cancel_charges");
                    break;
                case "27":
                    $description = trans("$string_file.handyman_cancel_charges_deducted");
                    break;
                case "28":
                    $description = trans("$string_file.cashout_request_accepted_message");
                    break;
                case "29":
                    $description = trans("$string_file.wallet")." ".trans("$string_file.reconcile");
                    break;
                default:
                    $description = $no_description;
            }
            break;
        case "USER":
            switch ($narration) {
                case "1":
                    $description = $description_admin;
                    break;
                case "2":
                    $description = trans("$string_file.wallet_recharged_successfully");
                    break;
                case "3":
                    $description = trans("$string_file.wallet_money_added_with_coupon") . ' ' . $receipt;
                    break;
                case "4":
                    $description = trans("$string_file.ride_amount_debited") . ' ' . $id;
                    break;
                case "5":
                    $description = trans("$string_file.cancelled_ride_amount_debited") . ' ' . $id;
                    break;
                case "6":
                    $description = $receipt;
                    break;
                case "7":
                    $description = $id;
                    $booking_id = NULL;
                    break;
                case "8":
                    $description = trans("$string_file.wallet_debited");
                    break;
                case "9":
                    $description = trans("$string_file.amount_received");
                    break;
                case "10":
                    $description = trans("$string_file.tip_amount_debited");
                    break;
                case "11":
                    $description = trans_choice("$string_file.food_order_refund", ['ID' => $id]);
                    break;
                    // wallet amount transfer
                case "12":
                    $description = trans("$string_file.you_have_received_amount_from", ['AMOUNT' => $amount, 'FROM' => $user_name]);
                    break;
                case "13":
                    $description = trans("$string_file.you_have_transferred_amount_to", ['AMOUNT' => $amount, 'TO' => $user_name]);
                    break;
                case "14":
                    $description = trans("$string_file.amount_debited_by_admin");
                    break;
                case "15":
                    $description = trans("$string_file.cancelled_order_amount_debited") . ' ' . $id;
                    break;
                case "16":
                    $description = trans("$string_file.referral_amount_credit");
                    break;
                case "17":
                    $description = trans("$string_file.cashout_amount_debited");
                    break;
                case "18":
                    $description = trans("$string_file.redeem_reward_point");
                    break;
                default:
                    $description = $no_description;
            }
            break;
        case "TAXI_COMPANY":
            break;
        case "HOTEL":
            break;
        case "BUSINESS_SEGMENT":
            switch ($narration) {
                case "1":
                    $description = $description_admin;
                    break;
                case "2":
                    $description = trans("$string_file.order_amount_added_by_admin");
                    break;
                case "3":
                    $description = trans("$string_file.order_commission_deducted") . $id;
                    break;
                case "4":
                    $description = trans("$string_file.cashout_amount_deducted");
                    break;
                case "5":
                    $description = trans("$string_file.cashout_request_rejected_refund_amount");
                    break;
                case "6":
                    $description = trans("$string_file.amount_debited_by_admin");
                    break;
                default:
                    $description = $no_description;
            }
            break;
    }
    return $description;
}

//$key = "AIzaSyC7lIIgBajzx409vxmmY_CJPcRvDb114w4";
//$booking_id = 440;
//$latitude = "-1.352772";
//$longitude = "36.7562829";
//$co = App\Models\BookingCoordinate::where('booking_id',440)->first();
////        p($co);
//$booking_coordinates = $co->coordinates;
////        p($booking_coordinates);
//$result = update_booking_map_image_at_ride_end($booking_id, $latitude, $longitude, $booking_coordinates, $key);
//p($result);

//function update_booking_map_image_at_ride_end($booking_id, $latitude, $longitude, $booking_coordinates, $key)
//{
//    if (!empty($booking_coordinates)) {
//        $drop_location_lat_long = json_decode($booking_coordinates, true);
//    }
//    $start = $latitude . ',' . $longitude;
//    if (count($drop_location_lat_long) > 1) {
//        $end = array_pop($drop_location_lat_long);
//        $finish = $end['latitude'] . ',' . $end['longitude'];
////        $count_waypoints = count($drop_location_lat_long);
//        $googleServices = new GoogleController();// CHECK FOR MULTIPLE WAYPOINTS or SINGLE WAYPOINT
//        $snapToRoad = $googleServices->SnapToRoad($drop_location_lat_long, $key);
////        $start = array_shift($snapToRoad);
////        $finish = array_pop($snapToRoad);
////        $finish = $finish ? $finish : $start;
//        $count_snapToRoad = count($snapToRoad);
//        if ($count_snapToRoad > 23) {
//            $average_way = ceil($count_snapToRoad / 22);
//            $new_array1 = array();
//            for ($j = 0; $j < $count_snapToRoad; $j = $j + $average_way) {
//                $lat_long = $snapToRoad[$j];
//                $new_array1[] = $lat_long;
//            }
////            p($new_array1);
//            $waypoints = implode("|", $new_array1);
//        } else {
//            $waypoints = implode("|", $snapToRoad);
//        }
//        //$multiple_waypoints = $googleServices->WayPointDistance($snapToRoad, $key);
////        p($waypoints);
////      p($multiple_waypoints);
////        $multiple_waypoints = array();
////        for ($j = 0; $j < $count_waypoints; $j++) {
////            $lat_long = $drop_location_lat_long[$j]['latitude'] . ',' . $drop_location_lat_long[$j]['longitude'];
////            $multiple_waypoints[] = $lat_long;
////        }
////        $waypoints = implode("|", $multiple_waypoints);
////        p($waypoints);
//        $data = GoogleController::GoogleStaticMultiplePointsImage($start, $finish, $waypoints, $key, "metric");
////        p($data);
//        $image = $data['image'];
//        p($image);
//        if (!empty($image)) {
//            $booking = Booking::Find($booking_id);
//            $booking->map_image = $image;
//            $booking->save();
//            return $image;
//        }
//    }
//}

function get_free_paid($string_file = "")
{
    return [
        "" => trans("$string_file.select"),
        "1" => trans("$string_file.free"),
        "2" => trans("$string_file.paid")
    ];
}

function get_optional_mandatory($string_file = "")
{
    return [
        "" => trans("$string_file.select"),
        "1" => trans("$string_file.optional"),
        "2" => trans("$string_file.mandatory")
    ];
}

function product_inventory_status($string_file = "")
{
    return array(
        '0' => trans("$string_file.not_added"),
        '1' => trans("$string_file.added"),
        '2' => trans("$string_file.partial_added"),
    );
}

function inventory_status($string_file = "")
{
    return array(
        '1' => trans("$string_file.yes"),
        '2' => trans("$string_file.no"),
    );
}

function driver_document_status($string_file = "")
{
    return array(
        '0' => trans("$string_file.pending"), //'PENDING',
        '1' => trans("$string_file.uploaded"), //'UPLOADED',
        '2' => trans("$string_file.approved"), //'APPROVED',
        '3' => trans("$string_file.rejected"), //'REJECTED',
        '4' => trans("$string_file.expired"), //'EXPIRED',
    );
}

function request_receiver($string_file)
{
    return [
        '1' => trans("$string_file.admin"),
        '2' => trans("$string_file.driver"),
    ];
}

function arr_driver_search_status($string_file)
{
    return array(
        "" => trans("$string_file.all"),
        "active" => trans("$string_file.active"),
        "busy" => trans("$string_file.busy"),
        "free" => trans("$string_file.free"),
        "inactive" => trans("$string_file.inactive"),
        "login" => trans("$string_file.login"),
        "logout" => trans("$string_file.logout"),
        "offline" => trans("$string_file.offline"),
        "online" => trans("$string_file.online")
    );
}

if (!function_exists('mobileNumber')) {
    function mobileNumber($swissNumberStr)
    {

        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            $swissNumberProto = $phoneUtil->parse($swissNumberStr, "SA");
            return $swissNumberProto;
        } catch (\libphonenumber\NumberParseException $e) {
            return $e->getMessage();
        }
    }
}

function translateLocalContent($data, $locale = 'en', $source = 'en')
{
    //    if($locale != $source){
    //        return GoogleTranslate::trans($data, $locale, 'en');
    //    }else{
    return $data;
    //    }
}

function convertTimeToUTCzone($str, $userTimezone, $format = 'Y-m-d H:i:s')
{

    $new_str = new DateTime($str, new DateTimeZone($userTimezone));
    $new_str->setTimeZone(new DateTimeZone('UTC'));
    return $new_str->format($format);
}

//this function converts string from UTC time zone to current user timezone
/**
 * @param int $return_type 1 for date and time, 2 for date only, 3 for time only
 */
function convertTimeToUSERzone($str, $userTimezone, $merchant_id = NULL, $merchant = [], $return_type = 1, $format_type_check = null)
{
    if (empty($userTimezone)) {
        $userTimezone = 'UTC';
    }
    if (empty($str)) {
        return '--';
    }
    $format_type = 1;
    if (!empty($merchant)) {
        $format_type = $merchant->datetime_format;
    } elseif (!empty($merchant_id)) {
        $merchant = Merchant::select('datetime_format')->Find($merchant_id);
        $format_type = $merchant->datetime_format;
    }
    if ($format_type_check == 1) {
        $format_type = 100; // for timestamp to convert date time only with format
    }
    $format = getDateTimeFormat($format_type, $return_type);
    $new_str = new DateTime($str, new DateTimeZone('UTC'));
    $new_str->setTimeZone(new DateTimeZone($userTimezone));
    return $new_str->format($format);
}

function getDateTimeFormat($format_type, $return_type = 1)
{
    $format = "Y-m-d H:i:s";
    switch ($format_type) {
        case 1:
            if ($return_type == 2) {
                $format = "Y-m-d";
            } elseif ($return_type == 3) {
                $format = "H:i:s";
            } else {
                $format = "Y-m-d H:i:s";
            }
            break;
        case 2:
            if ($return_type == 2) {
                $format = "D, F d";
            } elseif ($return_type == 3) {
                $format = "H:i:s";
            } else {
                $format = "D, F d, Y h:i A";
            }
            break;
        case 3:  // // "12:49 pm Apr 16, 2020",
            if ($return_type == 2) {
                $format = "M d, Y ";
            } elseif ($return_type == 3) {
                $format = "h:i A";
            } else {
                $format = "H:i A <\b\\r> M d, Y";
            }
            break;
        case 4:  // // "29-08-2023, Wednesday",
            if ($return_type == 2) {
                $format = "d-m-Y, l";
            } elseif ($return_type == 3) {
                $format = "h:i A";
            } else {
                $format = "H:i A <\b\\r> d-m-Y, l";
            }
            break;
        default:
            if ($return_type == 2) {
                $format = "Y-m-d";
            } elseif ($return_type == 3) {
                $format = "H:i:s";
            } elseif($return_type == 4) {
                $format = "h:i A";
            } else{
                $format = "Y-m-d H:i:s";
            }
            break;
    }
    return $format;
}

function getReferralSystemOfferCondition($string_file)
{
    return array(
        1 => trans("$string_file.limited"),
        2 => trans("$string_file.unlimited"),
        3 => trans("$string_file.signup") . " Only",
        4 => "Conditional (No of Driver register with no of rides)",
        5 => "Triggered after the driver is approved &&  After User's first Ride",
    );
}

function getReferralSystemDriverCondition($string_file)
{
    return array(
        1 => trans("$string_file.after") . " " . trans("$string_file.basic") . " " . trans("$string_file.signup"),
        2 => trans("$string_file.after") . " " . trans("$string_file.complete") . " " . trans("$string_file.signup"),
        3 => trans("$string_file.after") . " " . trans("$string_file.ride"),
    );
}

function setLocal($locale = null)
{
    $default_locale = "en";
    $req_locale = request()->header("locale");
    if (!empty($locale)) {
        $set_locale = $locale;
    } elseif (!empty($req_locale)) {
        $set_locale = $req_locale;
    } else {
        $set_locale = $default_locale;
    }
    App::SetLocale($set_locale);
}

function custom_number_format($amount, $trip_calculation_method = NULL, $merchant_id = NULL)
{

    switch ($trip_calculation_method) {
        case "1":
            $amount = (string) round((float)$amount); // response must be in string
            break;
        case "2":
            $amount = sprintf("%.2f", $amount);
            break;
        case "3":
            $amount = number_format(round($amount), 2, ".", '');
            break;
        case "4":
            $amount = sprintf('%.3f', $amount);
            break;
        default:
            $amount = sprintf("%.2f", $amount);
    }
    return $amount;
}

function get_merchant_required_additional_information_on_signup($merchant_id, $merchant_obj = NULL, $country_area = NULL)
{
    $result = [
        "required" => false,
        "requirement" => "",
        "step_name" => "No Name"
    ];
    if (!empty($merchant_id)) {
        $configuration = \App\Models\Configuration::where("merchant_id", $merchant_id)->first();
    } else {
        $configuration = \App\Models\Configuration::where("merchant_id", $merchant_obj->id)->first();
    }
    
    $get_string = new GetString($merchant_id);
    $string_file = $get_string->getStringFileText();
    $arr_result = [];
    if(isset($country_area) && ($configuration->driver_guarantor_details == 1 && $country_area->need_driver_guarantor_details == 1)){
        $result = [
            "required" => true,
            "requirement" => "DRIVER_GUARANTOR_DETAILS",
            "step_name" => "Driver Guarantor details",
            "step_description" => "Please Add Guarantor details ",
            "step_verified_message" => "Guarantor details Added",
            "step_pending_message" => "Guarantor details Pending",
            "step_slug"=> "GUARANTOR_DETAILS"
        ];
        
        array_push($arr_result,$result);
    }
    if($configuration->driver_ssn_number_enable == 1){
        $result = [
            "required" => true,
            "requirement" => "DRIVER_SSN_NUMBER",
            "step_name" => trans("$string_file.driver_ssn_number"),
            "step_description" => "Please Add". trans("$string_file.driver_ssn_number"),
            "step_verified_message" => trans("$string_file.driver_ssn_number") . "Added",
            "step_pending_message" => trans("$string_file.driver_ssn_number") . "Pending",
            "step_slug"=> "SSN_NUMBER"
        ];
        array_push($arr_result,$result);
    }
    
    if ($configuration->stripe_connect_enable == 1) {
        $result = [
            "required" => true,
            "requirement" => "STRIPE_CONNECT",
            "step_name" => "Stripe Registration",
            "step_description" => "Please register driver for Stripe",
            "step_verified_message" => "Stripe verification Done",
            "step_pending_message" => "Stripe verification is pending",
        ];
        
        array_push($arr_result,$result);
    } elseif ($configuration->paystack_split_payment_enable == 1) {
        $result = [
            "required" => true,
            "requirement" => "PAYSTACK_SPLIT",
            "step_name" => "Paystack Registration",
            "step_description" => "Please register driver for Paystack",
            "step_verified_message" => "Paystack verification Done",
            "step_pending_message" => "Paystack verification is pending",
        ];
        
        array_push($arr_result,$result);
    }
    return $arr_result;
}

function get_category_type_arr($string_file)
{
    return array("CAT" => trans("$string_file.category"), "EVENT" => trans("$string_file.weekday_events"), "EVENT_TWO" => trans("$string_file.weekend_events"));
}

function get_distance_units($string_file = null, $return_id_only = false)
{
    if($return_id_only){
        return array(1,2,3);
    }else{
        return array(
            1 => trans("$string_file.km"),
            2 => trans("$string_file.miles"),
            3 => trans("$string_file.meter")
        );
    }
}

function get_calculate_distance_unit($unit, $distance_meter, $display = false, $string_file = null){
    $text = "";
    switch($unit){
        case"2":
            $unit_value = 1609.34; // For Miles
            $text = $string_file ? trans("$string_file.miles") : "mi";
            break;
        case"3":
            $unit_value = 1; // For Meters
            $text = $string_file ? trans("$string_file.meter") : "meter";
            break;
        default:
            $unit_value = 1000; // For Kilometers
            $text = $string_file ? trans("$string_file.km") : "km";
    }
    return $display ? round_number(($distance_meter / $unit_value), 2)." ".$text : ($distance_meter / $unit_value);
}

function getUUID()
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function get_price_card_slab_types($string_file = null, $return_slug_only = false, $filtered_arr = [])
{
    if($return_slug_only){
        return array("BASE_FARE","DISTANCE","RIDE_TIME");
    }else{
        $arr = array(
            "DISTANCE" => trans("$string_file.distance"),
            "BASE_FARE" => trans("$string_file.base_fare"),
            "RIDE_TIME" => trans("$string_file.ride_time")
        );
        if(!empty($filtered_arr)){
            foreach($arr as $key => $item){
                if(!in_array($key, $filtered_arr)){
                    unset($arr[$key]);
                }
            }
        }
        return $arr;
    }
}

function get_corporate($id = false){
    if (Auth::guard('corporate')->check()) {
        if($id){
            return Auth::user('corporate')->id;
        }
        else{
            return Auth::user('corporate');
        }
    } else {
        return null;
    }
}

function validate_encryption_key($strict_check = false){
    // Check with UTC time only
    if(isset(request()->encryption_key)){
        $decryption_key = \Illuminate\Support\Facades\Crypt::decrypt(request()->encryption_key);
        $add_minutes = 5;
        $new_time = $decryption_key + 60 * $add_minutes;
        $last_datetime = date('Y-m-d H:i:s', $new_time);
        $current_time = date("Y-m-d H:i:s");
        if($current_time > $last_datetime){
            throw new \Exception("Encrption Key Expired");
        }
    }else{
        if($strict_check){
            throw new \Exception("Encrption Key Required");
        }
    }
}

function merchant_time_format($merchant, $time){
    if ($merchant->Configuration->time_format == 1){
        $converted_time = date('h:i:s',strtotime($time));
    }else{
        $converted_time = date('H:i:s',strtotime($time));
    }
    return $converted_time;
}

function calculate_original_amount($total_amount, $tax_rate)
{
    $tax_rate /= 100;
    # Calculate original value
    $original_value = $total_amount / (1 + $tax_rate);
    return $original_value;
}
function get_user_guide()
{
    $merchant_segment = helperMerchant::MerchantSegments(1);
    $guides = [];
    $user_guides = \App\Models\UserGuide::get();
    foreach($user_guides as $guide){
        if(in_array($guide->slug, $merchant_segment)){
            array_push($guides, array(
                "file" => get_image($guide->file, "user_guide",null, false),
                "name" => ucfirst(strtolower($guide->slug))." User Guide"
            ));
        }
    }
    return $guides;
}

//get merchant configuration array
function getMerchantConfigurationDetails(){
    try{
        $data = [
            [
                'slug'=>'countries',
                'name'=>'Countries',
                'priority'=>'required',
            ],
            [
                'slug'=>'vehicle_type',
                'name'=>'Vehicle Type',
                'priority'=>'required',
            ],
            [
                'slug'=>'vehicle_make',
                'name'=>'Vehicle Make',
                'priority'=>'required',
            ],
            [
                'slug'=>'vehicle_model',
                'name'=>'Vehicle Model',
                'priority'=>'required',
            ],
            [
                'slug'=>'pricing_parameter',
                'name'=>'Pricing Parameter',
                'priority'=>'required',
            ],
            [
                'slug'=>'document',
                'name'=>'Document',
                'priority'=>'optional',
            ],
            [
                'slug'=>'service_area',
                'name'=>'Service Area',
                'priority'=>'required',
            ],
            [
                'slug'=>'categories',
                'name'=>'Categories',
                'priority'=>'required',
            ],
            [
                'slug'=>'price_card',
                'name'=>'Price card',
                'priority'=>'required'
            ]
        ];
    
        return $data;
    }catch (\Exception $exception){
        throw new \Exception($exception->getMessage());
    }
}

function checkMerchantPriority($app_config,$grocery_clone,$food_clone,$merchant,$mchtArray,$merchant_segment_group,$merchant_segment){
    // dd($merchant->Country,$mchtArray['slug'],$merchant_segment_group,$merchant_segment);
    switch($mchtArray['slug']){
        case "countries":
            if(count($merchant->Country) > 0){
                return true;
            }
            return false;
            break;
        case "vehicle_type":
            if(in_array(1,$merchant_segment_group) || in_array(3, $merchant_segment_group)){
                if(count($merchant->VehicleType) > 0){
                    return true;
                }
                return false;
            }
            else{
                return null;
            }
            break;
        case "vehicle_make":
            if(in_array(1,$merchant_segment_group) || in_array(3, $merchant_segment_group)){
                if(count($merchant->VehicleMake) > 0){
                    return true;
                }
                return false;
            }
            else{
                return null;
            }
            break;
        case "vehicle_model":
            if(in_array(1,$merchant_segment_group) || in_array(3, $merchant_segment_group)){
                if(count($merchant->VehicleMake) > 0){
                    return true;
                }
                return false;
            }
            else{
                return null;
            }
            break;
        case "document":
            if(count($merchant->Document) > 0){
                return true;
            }
            return false;
            break;
        case "pricing_parameter":
            if(in_array('TAXI',$merchant_segment) || in_array('DELIVERY',$merchant_segment) || in_array('CARPOOLING',$merchant_segment)){
                if(count($merchant->Segment) > 0){
                    return true;
                }
                return false;
            }
            else{
                return null;
            }
            break;
        case "categories":
            if(((in_array('TAXI',$merchant_segment) && $app_config->home_screen_view == 1) || (in_array('TAXI',$merchant_segment) && in_array('DELIVERY',$merchant_segment) && $app_config->home_screen_view == 1) || (in_array('FOOD',$merchant_segment) || $grocery_clone || $food_clone))){
                 if(count($merchant->Segment) > 0){
                    return true;
                }
                return false;
            }
            else{
                return null;
            }
            break;
        case "service_area":
            if($merchant->CountryArea){
                return true;
            }
            return false;
            break;
        case "price_card":
            if(in_array(1,$merchant_segment_group) || in_array(2,$merchant_segment_group) || in_array(3,$merchant_segment_group) || $grocery_clone || $food_clone){
                if(count($merchant->Segment) > 0 && count($merchant->GetCountryArea) > 0){
                    return true;
                }
                else{
                    return false;
                }
            }
            else{
                return null;
            }
            break;
            
    }
}
function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function get_max_product_sku_id($merchant_id,$bs_segment){
    return Product::where('merchant_id',$merchant_id)->where('business_segment_id',$bs_segment)->orderBy('id', 'DESC')->select('sku_id')->first();
}

function get_max_product_variant_sku_id($merchant_id,$bs_segment){
    return ProductVariant::whereHas('Product',function($q) use($bs_segment,$merchant_id){
                    $q->where(['merchant_id'=> $merchant_id,'business_segment_id'=> $bs_segment]);
                })->orderBy('id', 'DESC')->select('sku_id','id')->first();
}

function get_max_laundry_outlet_sid($merchant_id,$outlet){
    return LaundryService::where('merchant_id',$merchant_id)->where('laundry_outlet_id',$outlet)->orderBy('id', 'DESC')->select('sid')->first();
}

function encryptText($textToEncrypt, $secretKey, $iv) {
    // Ensure the secret key length is valid for AES (16, 24, or 32 bytes)
    $keyLength = strlen($secretKey);
    if (!in_array($keyLength, [16, 24, 32])) {
        throw new Exception("Invalid key length: $keyLength. Key must be 16, 24, or 32 bytes long.");
    }

    // Ensure the IV length is 16 bytes for AES
    $ivLength = strlen($iv);
    if ($ivLength !== 16) {
        throw new Exception("Invalid IV length: $ivLength. IV must be 16 bytes long.");
    }

    // Encrypt the plaintext
    $cipher = "aes-256-cbc"; // AES-256-CBC mode
    $encrypted = openssl_encrypt($textToEncrypt, $cipher, $secretKey, OPENSSL_RAW_DATA, $iv);

    if ($encrypted === false) {
        throw new Exception('Encryption failed: ' . openssl_error_string());
    }

    // Encode the encrypted data to Base64
    return base64_encode($encrypted);
}

function decryptText($encryptedText, $secretKey, $iv) {
    // dd($encryptedText, $secretKey, $iv);
    // Ensure the secret key length is valid for AES (16, 24, or 32 bytes)
    $keyLength = strlen($secretKey);
    // dd($keyLength);
    if (!in_array($keyLength, [16, 24, 32])) {
        throw new Exception("Invalid key length: $keyLength. Key must be 16, 24, or 32 bytes long.");
    }

    // Ensure the IV length is 16 bytes for AES
    $ivLength = strlen($iv);
    if ($ivLength !== 16) {
        throw new Exception("Invalid IV length: $ivLength. IV must be 16 bytes long.");
    }

    // Decode the Base64 encoded encrypted text
    $encryptedData = base64_decode($encryptedText);
    if ($encryptedData === false) {
        throw new Exception('Base64 decoding failed.');
    }

    // Decrypt the data
    $cipher = "aes-256-cbc"; // AES-256-CBC mode
    // dd($cipher,$encryptedData,$ivLength,$keyLength);
    $decrypted = openssl_decrypt($encryptedData, $cipher, $secretKey, OPENSSL_RAW_DATA, $iv);

    if ($decrypted === false) {
        throw new Exception('Decryption failed: ' . openssl_error_string());
    }

    return $decrypted;
}

function generateSecretKey($keyString) {
    // Ensure the key length is valid for AES (16, 24, or 32 bytes)
    $keyLength = strlen($keyString);
    if (!in_array($keyLength, [16, 24, 32])) {
        throw new Exception("Invalid key length: $keyLength. Key must be 16, 24, or 32 characters long.");
    }

    // The key string is already in the correct format
    return $keyString;
}

function generateIV($ivString) {
    // Ensure the IV length is valid for AES (16 bytes)
    $ivLength = strlen($ivString);
    // dd($ivLength);
    if ($ivLength !== 16) {
        throw new Exception("Invalid IV length: $ivLength. IV must be 16 characters long.");
    }

    // The IV string is already in the correct format
    return $ivString;
}

function getSecAndIvKeys(){
    $keyString = "p9Nf8xLqzB1wKv3rjY5Tg4D2H7VbXs6C"; // 32-character iv key
    $secretKey = generateSecretKey($keyString);
    
    $ivString = "1a2b3c4d5e6f7890"; // 16-character iv key
    $iv = generateIV($ivString);

    return ['iv'=> $iv, 'secret'=> $secretKey];

}

//function generateIntegrityHash($url){
//    $fileContents = file_get_contents($url);
//    $hash = hash('sha384', $fileContents, true);
//    $base64Hash = base64_encode($hash);
//    return $base64Hash;
//}

/**
 * @ayush
 * Laundry Module
 * */
function get_laundry_outlet($laundry_outlet = true)
{
    if (Auth::guard('laundry_outlet')->check()) {
        if ($laundry_outlet == true) {
            return Auth::user('laundry_outlet')->parent_id != 0 ? Auth::user('laundry_outlet')->parent_id : Auth::user('laundry_outlet')->id;
        } else {
            return Auth::user('laundry_outlet')->parent_id != 0 ? \App\Models\LaundryOutlet\LaundryOutlet::Find(Auth::user('laundry_outlet')->parent_id) : Auth::user('laundry_outlet');
        }
    }
}

function saveLoginLogs($user, $ip, $type, $is_business_segment_direct_login = false){
    switch ($type){
        case "MERCHANT":
            \App\Models\LoginLog::create([
                'merchant_id' => $user->id,
                'user_name' => $user->merchantFirstName." ".$user->merchantLastName,
                'email' => $user->email,
                'ip_address' => $ip,
                'login_time' => now('Asia/Kolkata')->toDateTimeString(),
            ]);
            break;
        case "BUSINESS_SEGMENT":
            \App\Models\LoginLog::create([
                'business_segment_id' => $user->id,
                'user_name' => $user->full_name,
                'email' => $user->email,
                'ip_address' => $ip,
                'is_business_segment_direct_login' => ($is_business_segment_direct_login) ? 1 : 2,
                'login_time' => now('Asia/Kolkata')->toDateTimeString(),
            ]);
            break;
    }
}

function check_and_update_user_document_status($user){
    if($user->Merchant->ApplicationConfiguration->user_document == 1){
        $not_skip_flag = false;
        //personal doc
        $country = \App\Models\Country::find($user->country_id);
        $documentList = $country->documents;
        if (!empty($documentList)) {
            foreach ($documentList as $key => $doc) {
                $userDoc = \App\Models\UserDocument::where([['document_id', '=', $doc->id], ['user_id', '=', $user->id]])->first();
                if (empty($userDoc) && $doc->documentNeed == 1) {
                    $not_skip_flag = true;
                }
            }
        }
        if (!$not_skip_flag) {
            $user->signup_status = ($country->document_auto_verify == 1) ? 2 : 3;
            $user->approved_document = $user->total_document;
            $user->save();
        }
    }
}




function getDriverCurrentLatLong($driver): array
{
    $latitude= $driver->current_latitude;
    $longitude= $driver->current_longitude;
    $bearing = $driver->bearing;
    $accuracy = $driver->accuracy;
    $timestamp = $driver->last_location_update_time;

    $pattern = "driver_location:$driver->merchant_id:$driver->id";
    $driver_data = Redis::hgetall($pattern);
    if (isset($driver_data['latitude']) && isset($driver_data['longitude'])) {
        $latitude =  $driver_data['latitude'];
        $longitude = $driver_data['longitude'];
        $bearing = $driver_data['bearing'];
        $accuracy = $driver_data['accuracy'];
        $timestamp = $driver_data['timestamp'];
    }

    return ["latitude"=>$latitude, 'longitude'=>$longitude, 'bearing'=>$bearing, 'accuracy'=>$accuracy, 'timestamp'=>$timestamp];
}

function getJwtToken($id, $issued_by, $type): string
{
    $config = JWTConfiguration::forSymmetricSigner(
        new Sha256(),
        InMemory::plainText(env('JWT_SECRET'))
    );
    $now = new DateTimeImmutable();
    $token = $config->builder()
        ->issuedBy($issued_by)
        ->identifiedBy(bin2hex(random_bytes(16)), true)
        ->issuedAt($now)
        // ->expiresAt($now->modify('+1 hour'))
        ->relatedTo((string) $id)
        ->withClaim('type', $type)
        ->getToken($config->signer(), $config->signingKey());
    return $token->toString();
}