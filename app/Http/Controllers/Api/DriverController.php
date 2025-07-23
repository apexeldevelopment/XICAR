<?php

namespace App\Http\Controllers\Api;

use App\Events\DriverForgotPasswordEmailOtpEvent;
use App\Events\DriverSignupEmailOtpEvent;
use App\Events\DriverSignupWelcome;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\DriverRecords;
use App\Http\Controllers\Helper\PolygenController;
use App\Http\Controllers\Helper\ReferralController;
use App\Http\Controllers\Helper\SmsController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Resources\DriverConfiguration;
use App\Http\Resources\MainScreenResource;
use App\Http\Resources\DriverResource;
use App\Http\Resources\DriverLoginResource;
use App\Http\Resources\DriverDeliveryResource;
use App\Models\Application;
use App\Models\ApplicationConfiguration;
use App\Models\Booking;
use App\Models\BookingConfiguration;
use App\Models\BookingCoordinate;
use App\Models\BookingRequestDriver;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\Document;
use App\Models\DriverGallery;
use App\Models\DriverMovementLog;
use App\Events\SendMovingStatusNotification;
use App\Models\DriverWalletTransaction;
use App\Models\GeofenceAreaQueue;
use App\Models\HandymanOrder;
use App\Models\PaymentConfiguration;
use App\Models\Configuration;
use App\Models\AccountType;
use App\Models\CountryArea;
use App\Models\DemoConfiguration;
use App\Models\Driver;
use App\Models\DriverConfiguration as DriverConfigurationModel;
use App\Models\DriverAddress;
use App\Models\DriverDocument;
use App\Models\DriverRideConfig;
use App\Models\DriverVehicle;
use App\Models\DriverVehicleDocument;
use App\Models\Merchant;
use App\Models\Onesignal;
use App\Models\PriceCard;
use App\Models\PromotionNotification;
use App\Models\DriverReferralDiscount;
use App\Models\SegmentPriceCardDetail;
use App\Models\ServiceTimeSlot;
use App\Models\ServiceTimeSlotDetail;
use App\Models\ServiceType;
use App\Models\SmsConfiguration;
use App\Models\User;
use App\Models\UserWalletTransaction;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\LaundryOutlet\LaundryOutletOrder;
use App\Traits\BookingTrait;
use App\Traits\DriverTrait;
use App\Traits\MerchantTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Passport\Client;
use DB;
use App\Traits\ImageTrait;
use App\Http\Controllers\Helper\RewardPoint;
use App\Models\DriverSubscriptionRecord;
use App\Traits\AreaTrait;
use App\Traits\LocationTrait;
use App\Traits\ApiResponseTrait;
use App\Models\SegmentGroup;
use App\Models\Segment;
use App\Models\DriverSegmentDocument;
use App\Models\SegmentPriceCard;
use App\Models\BusinessSegment\Order;
use Lcobucci\JWT\Parser as JwtParser;
use App\Models\HandymanOrderDetail;
use DateTime;
use DateTimeZone;
use App\Models\EmailConfig;
use App\Models\EmailTemplate;
use App\Traits\MailTrait;
use View;
use App\Http\Controllers\PaymentMethods\Paystack\Paystack;
use App\Jobs\ProcessLocationApi;
use App\Events\sendDriverMissedRideNotification;
use App\Models\BonsBankToBankQrGateway;

class DriverController extends Controller
{
    use ImageTrait, DriverTrait, AreaTrait, BookingTrait, ApiResponseTrait, MerchantTrait, MailTrait, LocationTrait;
    public function CurrentLocation(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $merchant_id = $driver->merchant_id;
        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $config = BookingConfiguration::select('google_key')->where([['merchant_id', '=', $merchant_id]])->first();
        $result = \App\Http\Controllers\Helper\CommonController::GoogleAddress($request->latitude, $request->longitude, $config->google_key);
        if (empty($result)) {
            return $this->failedResponse(trans("$string_file.google_key_not_working"));
        }
        return $this->successResponse(trans("success"), $result);
    }

    public function ForgotPassword(Request $request)
    {
        $merchant_id = $request->merchant_id;
        
        //Encrypt Decrypt
        $merchant = Merchant::find($merchant_id);
        $iv = "";
        $secret = "";
        if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];

                if($request->password){
                    $pass = decryptText($request->password,$secret,$iv);
                    $request->merge(['password'=> $pass]);

                }
                
                if($request->phone){
                    $phone = decryptText($request->phone,$secret,$iv);
                    $request->merge(['phone'=> $phone]);

                }
                
            }catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        $fields = [
            'password' => 'required|string',
            'for' => 'required|string',
        ];
        if ($request->for == 'PHONE') {
            $fields['phone'] = [
                'required', 'regex:/^[0-9+]+$/',
                Rule::exists('drivers', 'phoneNumber')->where(function ($query) use ($merchant_id) {
                    return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id], ['driver_admin_status', '=', 1]]);
                })
            ];
        } else {
            $fields['phone'] = [
                'required', 'email',
                Rule::exists('drivers', 'email')->where(function ($query) use ($merchant_id) {
                    return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id], ['driver_admin_status', '=', 1]]);
                })
            ];
        }

        $validator = Validator::make($request->all(), $fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $keyword = $request->for == 'PHONE' ? 'phoneNumber' : 'email';
        $driver = Driver::where([['merchant_id', '=', $merchant_id], [$keyword, '=', $request->phone], ['driver_delete', '=', NULL]])->first();
        $driver->password = Hash::make($request->password);
        $driver->save();
        $message = trans("password") . ' ' . trans("updated") . ' ' . trans("successfully");
        $data = [];
        // return response()->json(['result' => "1", 'message' => trans("password") . ' ' . trans("updated") . ' ' . trans("successfully"), 'data' => []]);
        return $this->successResponse($message, $data);
    }

    public function AutoUpgradetion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required_if:action,STORE|integer|between:1,2',
            'action' => [
                'required',
                Rule::in(['GET', 'STORE']),
            ]
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0], []);
        }
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile($driver->merchant_id);
        $auto_upgradetion = $driver->CountryArea->auto_upgradetion;
        if ($auto_upgradetion != 1) {
            $message = trans("$string_file.not_available_in_your_location");
            return $this->failedResponse($message, []);
        }
        $auto_upgradetion_status = false;
        if ($request->action == 'GET') {
            $driver_ride_config = DriverRideConfig::where([
                'driver_id' => $driver->id
            ])->first();
            if (!empty($driver_ride_config)) {
                $auto_upgradetion_status = ($driver_ride_config->auto_upgradetion == 1);
            }
        } elseif ($request->action == 'STORE') {
            $driver_ride_config = DriverRideConfig::updateOrCreate([
                'driver_id' => $driver->id
            ], [
                'auto_upgradetion' => $request->status
            ]);
            $auto_upgradetion_status = ($request->status == 1);
        }
        $message = trans("$string_file.auto_upgradation");
        $message .= $request->status == 1 ? ' ' . trans("$string_file.on") : ' ' . trans("$string_file.off");
        return $this->successResponse($message, ['auto_upgradetion_status' => $auto_upgradetion_status]);
    }

    public function ManualDowngradation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required_if:action,STORE|integer|between:1,2',
            'action' => [
                'required',
                Rule::in(['GET', 'STORE']),
            ]
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0], []);
        }
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile($driver->merchant_id);
        $vehicle_types = [];
        if ($request->action == 'STORE' && $request->status == 1) {
            $vehicle_types = json_decode($request->vehicle_types);
            if (!is_array($vehicle_types) || empty($vehicle_types)) {
                $message = trans("$string_file.error");
                return $this->failedResponse($message, []);
            }
            //            array_push($vehicle_types, $driver->DriverVehicle[0]);
        }
        $manual_downgradation = $driver->CountryArea->manual_downgradation;
        $manual_downgradation_status = false;
        if ($manual_downgradation != 1) {
            $message = trans("$string_file.manual_downgradation_not_available_in_your_location");
            return $this->successResponse($message, ['manual_downgradation_status' => $manual_downgradation_status]);
        }
        if ($request->action == 'GET') {
            $driver_ride_config = DriverRideConfig::where([
                'driver_id' => $driver->id
            ])->first();
            if (!empty($driver_ride_config)) {
                $manual_downgradation_status = $driver_ride_config->manual_downgradation == 1;
            }
        } elseif ($request->action == 'STORE') {
            DriverRideConfig::updateOrCreate([
                'driver_id' => $driver->id
            ], [
                'manual_downgradation' => $request->status
            ]);
            if ($request->status == 1) {
                $driver->ManualDowngradedVehicleTypes()->sync($vehicle_types);
            } else {
                $driver->ManualDowngradedVehicleTypes()->detach();
            }
            $manual_downgradation_status = $request->status == 1;
        }
        $message = trans("$string_file.manual_downgradation");
        $message .= $request->status == 1 ? ' ' . trans("$string_file.activated") : ' ' . trans("$string_file.deactivated");
        return $this->successResponse($message, ['manual_downgradation_status' => $manual_downgradation_status]);
    }

    public function ManualDowngradeVehicleTypeList(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile($driver->merchant_id);
        $merchant_id = $driver->merchant_id;
        $config = Configuration::where('merchant_id', $merchant_id)->first();
        $driver_vehicle = $driver->DriverVehicle[0];
        $vehicle_type_arr = [];
        if (!empty($driver_vehicle)) {
            $vehicleServices = $driver->CountryArea->VehicleType->where('id', $driver_vehicle->vehicle_type_id);
            $service_arr = [];
            foreach ($vehicleServices as $value) {
                // downgrade only for taxi customisation
                if ($value->pivot->segment_id == 1) {
                    array_push($service_arr, $value->pivot->service_type_id);
                }
            }
            $vehicle_type_list = DB::table('vehicle_types')->select('vehicle_types.id', 'language_vehicle_types.vehicleTypeName')->Join('country_area_vehicle_type', 'vehicle_types.id', '=', 'country_area_vehicle_type.vehicle_type_id')
                ->join('language_vehicle_types', 'vehicle_types.id', '=', 'language_vehicle_types.vehicle_type_id')->WhereIn('country_area_vehicle_type.service_type_id', $service_arr)
                ->where([['country_area_vehicle_type.country_area_id', '=', $driver->country_area_id], ['vehicle_types.vehicleTypeRank', '<', $driver_vehicle->VehicleType->vehicleTypeRank]])->distinct()
                ->get();
            if ($config->manual_downgrade_enable == 1 && $driver->CountryArea->manual_downgradation == 1) {
                if (!empty($vehicle_type_list)) {
                    foreach ($vehicle_type_list as $vehicle_type) {
                        array_push($vehicle_type_arr, array('vehicle_type_id' => $vehicle_type->id, 'vehicleTypeName' => $vehicle_type->vehicleTypeName));
                    }
                } else {
                    return response()->json(['result' => "0", 'message' => 'No vehicle available to downgrade. Your vehicle is already in the lowest category.', 'data' => []]);
                }
            } else {
                return response()->json(['result' => "0", 'message' => trans("$string_file.manual_downgradation_not_available_in_your_location"), 'data' => []]);
            }
        } else {
            return response()->json(['result' => "0", 'message' => trans("$string_file.data_not_found"), 'data' => []]);
        }
        return response()->json(['result' => "1", 'message' => trans("$string_file.vehicles"), 'data' =>
        $vehicle_type_arr]);
    }

    //    public function getAddress(Request $request)
    //    {
    //        $driver = $request->user('api-driver');
    //        $driver_id = $driver->id;
    //        $data = DriverAddress::where([['driver_id', '=', $driver_id]])->get();
    //        $string_file = $this->getStringFile(NULL,$driver->Merchant);
    //        if (empty($data->toArray())) {
    //            return response()->json(['result' => "0", 'message' => trans("$string_file.data_not_found"), 'data' => []]);
    //        }
    //        $newArray = array();
    //        foreach ($data as $values) {
    //            $status = $values->address_status == 1 ? true : false;
    //            $newArray[] = array(
    //                "id" => $values->id,
    //                "address_name" => $values->address_name,
    //                "address_value" => $values->location,
    //                "check_visibility" => $status
    //            );
    //        }
    //        return response()->json(['result' => "1", 'message' => trans('api.message95'), 'data' => $newArray]);
    //    }

    public function addAddress(Request $request)
    {
        $driver = $request->user('api-driver');
        $driver_id = $request->user('api-driver')->id;
        $validator = Validator::make($request->all(), [
            'address_name' => 'required|string',
            //            'location' => ['required',
            //                Rule::unique('driver_addresses', 'location')->where(function ($query) use ($driver_id, $request) {
            //                    return $query->where([['driver_id', $driver_id], ['id', '!=', $request->driver_address_id], ['id', '!=', $request->driver_address_id]]);
            //                })],
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'address_type' => 'required',
            //  'segment_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $string_file = $this->getStringFile($driver->merchant_id);
            $area = PolygenController::Area($request->latitude, $request->longitude, $driver->merchant_id);
            if (empty($area)) {
                throw new \Exception(trans("$string_file.no_service_area"));
            }
            if ($driver->country_area_id != $area['id']) {
                return $this->failedResponse(trans("$string_file.your_work_area_must_inside_service_area"));
            }
            $id = $request->driver_address_id;
            if (!empty($id)) {
                $address = DriverAddress::Find($id);
            } else {
                $address = new DriverAddress;
                $address->address_status = $request->address_type == 2 ? 2 : 1; // inactive in case of home location
            }
            $address->driver_id = $driver_id;
            $address->segment_id = $request->segment_id;
            $address->address_name = $request->address_name;
            $address->location = $request->location;
            $address->latitude = $request->latitude;
            $address->longitude = $request->longitude;
            $address->radius = $request->radius;
            $address->address_type = $request->address_type;
            $address->save();
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.saved_successfully"), $address);
    }

    public function DeleteHomeLocation(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile($driver->merchant_id);
        $driver_id = $driver->id;
        $validator = Validator::make($request->all(), [
            'address_id' => [
                'required', 'integer',
                Rule::exists('driver_addresses', 'id')->where(function ($query) use ($driver_id) {
                    return $query->where('driver_id', $driver_id);
                })
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        DriverAddress::where([['id', '=', $request->address_id]])->delete();
        $data = DriverAddress::where([['driver_id', '=', $driver_id]])->get();
        if (empty($data->toArray())) {
            return response()->json(['result' => "1", 'message' => trans('admin.message316'), 'data' => []]);
        }
        $newArray = array();
        foreach ($data as $values) {
            $status = $values->address_status == 1 ? true : false;
            $newArray[] = array(
                "id" => $values->id,
                "address_name" => $values->address_name,
                "address_value" => $values->location,
                "check_visibility" => $status
            );
        }
        return response()->json(['result' => "1", 'message' => trans("$string_file.success"), 'data' => $newArray]);
    }

    public function homeAddressStatus(Request $request)
    {
        $driver = $request->user('api-driver');
        $driver_id = $driver->id;
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|between:1,2',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $driver_segment = $driver->Segment->unique();
        $configed_segment = array_pluck($driver_segment, "slag");
        $not_in_segment = ['TAXI','DELIVERY'];
        if (empty(array_intersect($configed_segment, $not_in_segment))){
            return $this->failedResponse(trans("$string_file.driver_home_location"));
        }
        $address = DriverAddress::where([['driver_id', '=', $driver_id], ['address_type', '=', 2]])->first(); // home address
        // p($address);
        if (!empty($address->id)) {
            if ($request->status == 1) {
                if (empty($address)) {
                    return $this->failedResponse(trans("$string_file.home_location_not_found"));
                } elseif ($address->address_status == 1) {
                    return $this->failedResponse(trans("$string_file.home_location_already_activated"));
                }
            }
            $driver->home_location_active = $request->status;
            $driver->save();
            $address->address_status = $request->status;
            $address->save();
            $pre_message = trans("$string_file.home_location");
            $message = $request->status == 1 ? $pre_message . ' ' . trans("$string_file.activated") : $pre_message . ' ' . trans("$string_file.deactivated");
            //        $status = $request->status == 1 ? true : false;
            return $this->successResponse($message, []);
        } else {
            return $this->failedResponse(trans("$string_file.home_location_not_found"));
        }
    }


    public function PromotionNotification(Request $request)
    {
        try {
            $arr_notifications = [];
            $driver = $request->user('api-driver');
            $driver_created_date = date('Y-m-d H:i:s', strtotime($driver->created_at));
            $driver_id = $driver->id;
            $country_area_id = $driver->country_area_id;
            $merchant_id = $driver->merchant_id;
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $notifications = PromotionNotification::where(function ($q) {
                $q->where('expiry_date', '>=', date('Y-m-d'));
                $q->orWhere('expiry_date', NULL);
            })
                ->where(function ($q) use ($driver_id) {
                    $q->where('driver_id', $driver_id);
                    $q->orWhere('driver_id', NULL);
                })
                ->where(function ($q) use ($country_area_id) {
                    $q->where('country_area_id', $country_area_id);
                    $q->orWhere('country_area_id', NULL);
                })
                ->where([['show_promotion', '=', 1], ['merchant_id', '=', $merchant_id], ['application', '=', 1]])
                //                 where([['expiry_date', '>=', date('Y-m-d')], ['merchant_id', '=', $merchant_id], ['application', '=', 1], ['country_area_id', '=', null], ['driver_id', '=', NULL], ['show_promotion', '=', 1]])
                //                ->orWhere([['expiry_date', '>=', date('Y-m-d')], ['show_promotion', '=', 1], ['merchant_id', '=', $merchant_id], ['application', '=', 1], ['country_area_id', '=', null], ['driver_id', '=', $driver_id]])
                //                ->orWhere([['expiry_date', '>=', date('Y-m-d')], ['show_promotion', '=', 1], ['merchant_id', '=', $merchant_id], ['application', '=', 1], ['country_area_id', '=', $country_area_id], ['driver_id', '=', NULL]])
                ->orderBy('created_at', 'DESC')
                ->where('created_at', '>=', $driver_created_date)
                ->get();
            if (empty($notifications->toArray())) {
                return $this->failedResponse(trans("$string_file.data_not_found"));
                //                return response()->json(['result' => "0", 'message' => trans("$string_file.data_not_found"), 'data' => []]);
            }
            foreach ($notifications as $key => $value) {
                $date = new DateTime($value->created_at);
                $date->setTimezone(new DateTimeZone($driver->CountryArea->timezone));
                $c_date = $date->format('Y-m-d H:i');
                $arr_notifications[] = array(
                    'title' => $value->title,
                    'message' => $value->message,
                    'url' => isset($value->url) ? $value->url : "",
                    'image' => isset($value->image) && !empty($value->image) ? get_image($value->image, 'promotions', $value->merchant_id, true, false) : "",
                    'created_at' => $c_date,
                );
            }
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.success"), $arr_notifications);
    }

    // get heat map booking
    public function heatMap(Request $request)
    {
        $drivers = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $drivers->Merchant);
        $country_area_id = $drivers->country_area_id;
        $bookings = Booking::select('pickup_latitude', 'pickup_longitude')->where([['country_area_id', '=', $country_area_id]])->get();

        $bookings_data = $bookings->map(function ($item) {
            return [
                'pickup_latitude' => $item->pickup_latitude,
                'pickup_longitude' => $item->pickup_longitude,
                'segment_type' => "BOOKING",
            ];
        });
        $orders = Order::where('orders.country_area_id', '=', $country_area_id)
            ->where('bs.country_area_id', '=', $country_area_id)
            ->join('business_segments as bs', 'orders.business_segment_id', '=', 'bs.id')
            ->select('latitude as pickup_latitude', 'longitude as pickup_longitude')
            ->get();

        $orders_data = $orders->map(function ($item) {
            return [
                'pickup_latitude' => $item->pickup_latitude,
                'pickup_longitude' => $item->pickup_longitude,
                'segment_type' => "ORDER",
            ];
        });
        // p($bookings_data);
        $return_data = array_merge($orders_data->toArray(), $bookings_data->toArray());
        //   $bookings_data->merge($orders_data);
        //   p($return_data);
        if (empty($return_data)) {

            return $this->failedResponse(trans("$string_file.no_rides_in_service_area"));
        }
        return $this->successResponse(trans("$string_file.success"), $return_data);
    }


    public function MainScreen(Request $request)
    {
        $driver = $request->user('api-driver');
        $driver->vehicleId = isset($request->vehicle_id) ? $request->vehicle_id : '';
        return new MainScreenResource($driver);
    }

    public function editProfile(Request $request)
    {
        //return $request->all();
        $driver = $request->user('api-driver');
        $driver_id = $driver->id;
        $merchant_id = $driver->merchant_id;
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        DB::beginTransaction();
        try {
            if (isset($request->first_name)) {
                $data['first_name'] = $request->first_name;
            }
            if (isset($request->last_name)) {
                $data['last_name'] = $request->last_name;
            }
            if (isset($request->email)) {
                $data['email'] = $request->email;
            }
            if (isset($request->phone)) {
                $data['phoneNumber'] = $request->phone;
            }
            if (isset($request->profile_image)) {
                if($request->multi_part == "1"){
                    $additional_req = ['compress' => true,'custom_key' => 'product'];
                    $profile_image = $this->uploadImage('profile_image', 'driver', $merchant_id,'single',$additional_req);
                    $data['profile_image'] = $profile_image;
                }else{
                    $profile_image = $this->uploadBase64Image('profile_image', 'driver', $merchant_id);
                    $data['profile_image'] = $profile_image;
                }
            }
            if (isset($request->cover_image) && !empty($request->cover_image)) {
                if($request->multi_part == "1"){
                    $additional_req = ['compress' => true,'custom_key' => 'product'];
                    $cover_image = $this->uploadImage('cover_image', 'driver', $merchant_id,'single',$additional_req);
                    $data['cover_image'] = $cover_image;
                }else{
                    $cover_image = $this->uploadBase64Image('cover_image', 'driver', $merchant_id);
                    $data['cover_image'] = $cover_image;
                }
            }

            if (isset($request->driver_gender)) {
                $driver->driver_gender = $request->driver_gender;
            }
            if ($request->driver_address_enable == 1) {
                $driver->driver_additional_data = $request->driver_additional_data;
            }
            // if ($driver->free_busy == 2) {
            //     $driver->pay_mode = $request->driver_commission_type;
            // }
            if ($request->smoker == 1) {
                $smoker = DriverRideConfig::updateOrCreate(
                    ['driver_id' => $driver->id],
                    [
                        'smoker_type' => $request->smoker_type,
                        'allow_other_smoker' => $request->allow_other_smoker
                    ]
                );
                $driver->smoker_type = $smoker->smoker_type;
                $driver->allow_other_smoker = $smoker->allow_other_smoker;

            }
            if (isset($request->old_password) && isset($request->new_password)) {
                if (Hash::check($request->old_password, $driver->password)) {
                    $driver->password = Hash::make($request->new_password);
                } else {
                    $message = trans("$string_file.invalid_password");
                    return $this->failedResponse($message);
                }
            }
            $existChanges = DB::table('driver_approve_details')->where(['driver_id'=>$driver->id,'merchant_id'=> $merchant_id,'is_approve'=> 0,'is_reject'=>0])->count() > 0;
            
            if(isset($data) && !$existChanges){
                $dataToInsert =  array(
                    'driver_id'      => $driver->id,
                    'merchant_id'    => $merchant_id,
                    'driver_details' => json_encode($data),
                    'is_approve'     => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s')
                );
                DB::table('driver_approve_details')->insert($dataToInsert);
            }else{
                if($existChanges && (!empty($request->phone) || !empty($request->first_name) || !empty($request->last_name) || !empty($request->email) || !empty($request->profile_image) || !empty($request->cover_image)) ){
                    $message = trans("$string_file.driver_changes_exist");
                    return $this->failedResponse($message);
                }
            }
            $driver->save();
            $driver = new DriverLoginResource($driver);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        if(isset($data)){
            return $this->successResponse(trans("$string_file.profile_changes_updated"), $driver);
        }else{
            return $this->successResponse(trans("$string_file.profile_updated"), $driver);
        }
    }

    public function BankDetailsUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string',
            'account_holder_name' => 'required|string',
            'account_number' => 'required|string',
            'account_type' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            //            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $driver = $request->user('api-driver');
        DB::beginTransaction();
        try {
            $driver->bank_name = $request->bank_name;
            $driver->account_type_id = $request->account_type;
            $driver->online_code = $request->online_code;
            $driver->account_holder_name = $request->account_holder_name;
            $driver->account_number = $request->account_number;
            $driver->save();
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        $driver_data = new DriverLoginResource($driver);
        DB::commit();
        return $this->successResponse("success", array('driver' => $driver_data));
    }

    public function Booking(Request $request)
    {
        $driver = $request->user('api-driver');
        $merchant_id = $driver->merchant_id;
        $driver_id = $driver->id;
        $string_file = $this->getStringFile($merchant_id);
        $bookings = Driver::with(['Booking' => function ($query) use ($driver_id) {
            $query->where('driver_id', $driver_id);
        }])->first();
        if (empty($bookings)) {
            return response()->json(['result' => "0", 'message' => trans("$string_file.data_not_found"), 'data' => []]);
        }
        return response()->json(['result' => "1", 'message' => trans("$string_file.success"), 'data' => $bookings]);
    }

    public function OnlineOffline(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in([1, 2])],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            $this->failedResponse($errors[0]);
        }
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        
        //OnlineOffline // pending approval case
        
        if ($driver->signupStep != 9) {
            return $this->failedResponse(trans("$string_file.profile_review_text"));
        }

        // offline case
        if ($driver->free_busy == 1 && $request->status == 2) {
            return $this->failedResponse(trans("$string_file.running_job_error"));
        }
        // in case of online
        $wallet_money = !empty($driver->wallet_money) ? $driver->wallet_money : "0";
        $taxiCompanyEnable = $driver->Merchant->Configuration->company_admin == 1 ? true : false;
        if ($request->status == 1 && $driver->Merchant->Configuration->driver_wallet_status == 1 && $driver->CountryArea->minimum_wallet_amount != "" && ($wallet_money == "" || $wallet_money < $driver->CountryArea->minimum_wallet_amount)) {
            // if($taxiCompanyEnable == false && empty($driver->taxi_company_id)){
            if(empty($driver->taxi_company_id)){
                return $this->failedResponse(trans("$string_file.low_wallet_warning"));
            }
        }
        $existing_enable = has_driver_multiple_or_existing_vehicle($driver->id, $driver->merchant_id);
        $merchant_id = $driver->merchant_id;
        $online_work_set = false;
        $vehicle_type_id = NULL;
        $socket_data = [];

        $driver_stats = [];
        if(isset($driver->Merchant->Configuration->show_driver_stats_on_screen) && $driver->Merchant->Configuration->show_driver_stats_on_screen == 1){
            $date = date("Y-m-d");
            $bookings = Booking::where(["driver_id" => $driver->id, "booking_status" => 1005])->where(function($q) use($date){
                $q->where(["booking_type" => 2, "later_booking_date" => $date]);
                $q->orWhere(function($k) use($date){
                    $k->where(["booking_type" => 1])->whereDate("created_at", $date);
                });
            })->get();

            $distance_unit = $driver->CountryArea->Country->distance_unit;
            $distance_unit_text = $distance_unit == 1 ? "Km" : "mi";

            $distance = 0;
            $time = 0;
            foreach($bookings as $booking){
                $temp = explode(" ", $booking->travel_distance);
                $distance += $temp[0];
                $temp = explode(" ", $booking->travel_time);
                $h = isset($temp[0]) ? $temp[0] : 0;
                $m = isset($temp[2]) ? $temp[2] : 0;
                $time += $m;
                $time += $h*60;
            }
            $h = round($time/60);
            $m = $time%60;

            $driver_stats = array(
                "rides_done_today" => $bookings->count(),
                "total_earning_today" => $bookings->sum("driver_cut"),
                "travel_distance_today" => round($distance, 2)." ".$distance_unit_text,
                "travel_time_today" =>  "$h H $m M"
            );
        }
        
        if ($request->status == 1) {
            $online_configuration = $this->getDriverOnlineConfig($driver, 'all');
            $socket_data = $online_configuration["socket_data"];
            $vehicle_type_id = $online_configuration["vehicle_type_id"];
            $active_vehicle_id = NULL;
            $online_work_set = $online_configuration['status'];
            if ($online_work_set == 1) {
                if ($driver->segment_group_id == 1) {
                    $assigned_vehicle_id = $online_configuration['driver_vehicle_id'][0];
                    
                    if ($existing_enable){
                        $vehicle = DriverVehicle::find($assigned_vehicle_id);
                        $activated_vehicle = [];
                        foreach($vehicle->Drivers as $pivot_driver){
                            if($pivot_driver['pivot']->vehicle_active_status == 1){
                                $activated_vehicle[] = $pivot_driver['pivot']->driver_id;
                            }
                        }
                        if(empty($activated_vehicle)){
                            DB::table('driver_driver_vehicle')->where([['driver_id', "=", $driver->id], ['driver_vehicle_id', "=", $assigned_vehicle_id]])->update(['vehicle_active_status' => 1]);
                            $active_vehicle_id = $assigned_vehicle_id;
                        }else{
                            if($activated_vehicle[0] != $driver->id){
                                $driver = Driver::find($activated_vehicle[0]);
                                $message = "Vehicle Already Online by ".$driver->first_name.' '.$driver->last_name;
                                return $this->failedResponse($message);
                            }else{
                                $active_vehicle_id = $assigned_vehicle_id;
                            }
                        }
                    }else{
                        $active_vehicle_id = $assigned_vehicle_id;
                    }
                }

                //                $expired_or_pending_doc = driver_all_document_status($driver->id, $active_vehicle_id);
                //                if ($expired_or_pending_doc == true) {
                //                    $message = trans('$string_file.document_status_mainscreen');
                //                    return $this->failedResponse($message);
                //                }
                $document_verification_pending = get_driver_document_details($driver->id, 'status', 'any', 1, $active_vehicle_id);
                if($existing_enable){
                    $document_verification_expired = false;
                    $document_verification_expired = get_driver_document_details($driver->id, 'status', 'any', 4, $active_vehicle_id);
                    if($document_verification_expired){
                        $message = trans("$string_file.active_vehicle_document_expired");
                        return $this->failedResponse($message);
                    }
                }
                //p($document_verification_pending.'jj');
                if ($document_verification_pending == true) {
                    // p('in');
                    $message = trans("$string_file.document_upload_under_review_message");
                    return $this->failedResponse($message);
                }
              
                if(!$this->checkForPendingDriverDocs($driver->id, $active_vehicle_id) && empty($driver->Merchant->demo)){
                    $message = trans("$string_file.either_vehicle_or_personal_docs_pending");
                    return $this->failedResponse($message);
                }

                // whether all documents are uploaded or not which is configured in area
                $pending_document_status = check_driver_document($driver->id, 'any', $active_vehicle_id);
                if (!$pending_document_status) {
                    $message = trans("$string_file.document_status_mainscreen");
                    return $this->failedResponse($message);
                }

                if ($driver->Merchant->Configuration->subscription_package == 1 && $driver->pay_mode == 1) {
                    $active_package = DriverSubscriptionRecord::select('end_date_time', 'package_total_trips', 'id', 'used_trips')->where([['driver_id', $driver->id], ['status', 2]])->orderBy('id', 'DESC')->first();
                    // date_default_timezone_set($this->CountryArea->timezone);
                    if (empty($active_package->id)) :
                    //                        $message = trans('api.subscription_package_not_bought');
                    //                        return $this->failedResponse($message);
                    else :
                        if (($active_package->package_total_trips <= $active_package->used_trips) || (strtotime("now") > strtotime($active_package->end_date_time))) :
                        //                            $message = trans('api.subscription_package_expire');
                        //                            return $this->failedResponse($message);

                        endif;
                    endif;
                }
            } else {
                $message = trans("$string_file.online_work_config_not_set");
                return $this->failedResponse($message);
            }

            //@ayush
            //added dvla tax due date check
            $active_vehicle = DriverVehicle::find($active_vehicle_id);
            if(!empty($active_vehicle->taxdue_date_dvla)){
                $previous_date =  new DateTime(date($active_vehicle->taxdue_date_dvla));
                $current_date =  new DateTime(date("Y-m-d"));
                if($current_date > $previous_date){
                    $res = json_decode($this->getDvlaDetails($active_vehicle->vehicle_number, $driver->Merchant));
                    $active_vehicle->taxdue_date_dvla = $res->taxDueDate;
                    $active_vehicle->save();
                    $vehicle_tax_due_date = new DateTime(date($active_vehicle->taxdue_date_dvla));
                    if($current_date > $vehicle_tax_due_date){
                        $message = trans("$string_file.tax_due_dvla");
                        return $this->failedResponse($message);
                    }
                }
            }
        } // offline case
        elseif ($request->status == 2) {
            if ($driver->segment_group_id == 1) {
                $online_configuration = $this->getDriverOnlineConfig($driver, 'all');
                $assigned_vehicle_id = $online_configuration['driver_vehicle_id'][0];
                if ($existing_enable){
                    DB::table('driver_driver_vehicle')->where([['driver_id', "=", $driver->id], ['driver_vehicle_id', "=", $assigned_vehicle_id]])->update(['vehicle_active_status' => 2]);
                }
            }
        }
        $driver->online_offline = $request->status;
        // only for development

        if($request->status == 1){
            if($driver->Merchant->Configuration->random_notifications_to_driver == 1){
                $time_diff_in_min = $driver->Merchant->Configuration->online_offline_notification_diff_time ?? 60;
                $current_time = time();
                $diff_in_minutes = ($current_time - $driver->online_timestamp) / 60;
                if ($diff_in_minutes > $time_diff_in_min) {
                    $count = random_int(1, 5);
                    $msg = trans("$string_file.you_have_missed_rides",['count' => $count]);
                    $title = trans("$string_file.missed_rides");
                    sendDriverMissedRideNotification::dispatch($driver->id, $driver->merchant_id, $msg, $title);
                }
            }
            $driver->online_timestamp = time();
        }
        $driver->current_latitude = $request->latitude; // update login status
        $driver->current_longitude = $request->longitude; // update login status
        $driver->save();
        $message = $request->status == 1 ? trans("$string_file.online") : trans("$string_file.offline");
        $newDriverRecord = new DriverRecords();
        $request->status == 1 ? $newDriverRecord->OnlineTimeRecord($driver->id, $merchant_id) : $newDriverRecord->OfflineTimeRecord($driver->id, $merchant_id);

        $return_data = [
            'driver_id' => $driver->id,
            'online_offline' => $request->status,
            'online_config_status' => $online_work_set,
            'online_config_message' => $online_work_set == 0 ? trans("$string_file.online_work_config_not_set") : "",
            'socket_data' => $socket_data,
            'vehicle_type_id' => $vehicle_type_id, // required in socket tag
            'driver_stats' => $driver_stats
        ];
        return $this->successResponse($message, $return_data);
    }

    // update current location of driver
    public function Location(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
            'bearing' => 'required',
            'accuracy' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            if (!empty($request->unique_number)) {
                $driver = Driver::where('unique_number', $request->unique_number)->first();
            } else {
                $driver = $request->user('api-driver');
            }
            $string_file  = $this->getStringFile($driver->merchant_id);
            /*
             * @ayush
             * process Queue for loaction webhook
             * */
            // if($driver->Merchant->Configuration->driver_movement_status == 1) {
            //     $driver->moving_location_distance = $this->AerialDistance($driver->current_latitude, $driver->current_longitude, $request['latitude'], $request['longitude']);
            //     $driver->save();
            //     if(!empty($request->booking_id)){
            //         $driver_movement_logs = new DriverMovementLog();
            //         $driver_movement_logs->driver_id = $driver->id;
            //         $driver_movement_logs->booking_id = $request->booking_id;
            //         $driver_movement_logs->distance_in_meter = $driver->moving_location_distance;
            //         $driver_movement_logs->timestamp = time();
            //         $driver_movement_logs->created_at = Carbon::now($driver->CountryArea->timezone);
            //         $driver_movement_logs->save();

            //         $lastTenMinutes = Carbon::now($driver->CountryArea->timezone)->subMinutes(10);
            //         $totalDistance = DriverMovementLog::where('driver_id', $driver->id)
            //             ->where('created_at', '>=', $lastTenMinutes)
            //             ->sum('distance_in_meter');

            //         if ($totalDistance < 100) {
            //             // Perform the required action
            //             $booking = Booking::find($request->booking_id);
            //             $title =  trans("$string_file.ride_movement_title");
            //             $message =  trans("$string_file.ride_movement_message");
            //             SendMovingStatusNotification::dispatch($driver->id, $driver->merchant_id, $booking, $message, $title);
            //         }
            //     }
            // }
            $this->saveLocation($request->all(),$driver, $string_file );
            // ProcessLocationApi::dispatch($request, $driver, $string_file)->onQueue('location_api');

        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.location"), []);
    }

    public function RedeemRewardPoints(Request $request){
        $user = $request->user('api-driver');
        $string_file = $this->getStringFile($user->merchant_id);
        $validator = Validator::make($request->all(), [
            'reward_points' => 'required|integer',
            // 'amount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        if($user->reward_points >= $request->reward_points){
            DB::beginTransaction();
            try{
                $user->reward_points -=  $request->reward_points;
                $user->save();

                $paramArray = array(
                    'merchant_id' => $user->merchant_id,
                    'driver_id' => $user->id,
                    'booking_id' => NULL,
                    'amount' => $request->reward_points,
                    'narration' => 18,
                    'platform' => 2,
                    'payment_method' => 2,
                    'transaction_id' => NULL,
//                'sender' => $sender->first_name . ' ' . $sender->last_name,
//                'wallet_transfer_id'=>$sender->id // save the sender id
                );
                WalletTransaction::WalletCredit($paramArray);
            }catch (\Exception $e) {
                DB::rollBack();
                $message = $e->getMessage();
                return $this->failedResponse($message);
            }
            DB::commit();
            return $this->successResponse(trans("$string_file.redeem_reward_point"));
        }else{
            return $this->failedResponse(trans("$string_file.no_reward_point"));
        }
    }

    public function Logout(Request $request)
    {
        DB::beginTransaction();
        try {
            // check logout condition
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            if ($driver->free_busy == 1) {
                return $this->failedResponse(trans("$string_file.running_job_error"));
            }

            $existing_enable = has_driver_multiple_or_existing_vehicle($driver->id, $driver->merchant_id);
            if ($driver->segment_group_id == 1) {
                $online_configuration = $this->getDriverOnlineConfig($driver, 'all');
                if (isset($online_configuration['driver_vehicle_id']) && !empty($online_configuration['driver_vehicle_id'])){
                    $assigned_vehicle_id = $online_configuration['driver_vehicle_id'][0];
                    if ($existing_enable){
                        DB::table('driver_driver_vehicle')->where([['driver_id', "=", $driver->id], ['driver_vehicle_id', "=", $assigned_vehicle_id]])->update(['vehicle_active_status' => 2]);
                    }
                }
            }

            $newDriverRecord = new DriverRecords();
            ($driver->online_offline == 1) ? $newDriverRecord->OfflineTimeRecord($driver->id, $driver->merchant_id) : '';
            $driver->online_offline = 2;
            $driver->login_logout = 2;
            $driver->free_busy = 2;
            $driver->save();
            $access_token_id = $driver->access_token_id;
            \DB::table('oauth_access_tokens')->where('id', '=', $access_token_id)->delete();
            \DB::table('oauth_refresh_tokens')->where('access_token_id', '=', $access_token_id)->delete();
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.logout"));
    }

    public function Otp(Request $request)
    {
        $merchant_id = $request->merchant_id;

        // Encrypt Decrypt Module
        $merchant = Merchant::Find($merchant_id);
        if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($request->user_name){
                    $request->merge(['user_name'=> decryptText($request->user_name,$secret,$iv)]);
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        ($request->for == 'EMAIL') ? $request->request->add(['email' => $request->user_name]) : $request->request->add(['phone' => $request->user_name]);
        $validator = Validator::make($request->all(), [
            'type' => 'required|integer', // 1 signup // 2 forgot password // 3
            'for' => [
                'required', 'string',
                Rule::in(['EMAIL', 'PHONE']),
            ],
            'phone' => 'required_unless:for,EMAIL|regex:/^[0-9+]+$/',
            'email' => 'required_unless:for,PHONE|email',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $merchant_id = $request->merchant_id;
        $app_config = ApplicationConfiguration::where([['merchant_id', $merchant_id]])->first();
        $driver_email_otp_while_phone = $app_config->driver_email_otp_while_phone;
        $string_file = $this->getStringFile(NULL, $merchant);
        $common_message = trans("$string_file.otp_sent_to_your");
        $both_message = $common_message . ' ' . trans("$string_file.phone") . ' & ' . ' ' . trans("$string_file.email");
        $phone_message = $common_message . ' ' . trans("$string_file.phone");
        $email_message = $common_message . ' ' . trans("$string_file.email");

        $action = "";
        $email = "";
        $phone = "";
        $field = "";
        $is_number_registered = false;
        if ($request->for == "PHONE") {
            $phone = $request->phone;
            $email = "";
            $field = "phoneNumber";
        } elseif ($request->for == "EMAIL") {
            $phone = "";
            $email = $request->email;
            $field = "email";
        }
        if ($request->type == 1) // signup
        {
            $customMessages = [
                'phone.required_unless' => trans("$string_file.phone_required"),
                'phone.unique' => trans("$string_file.phone_already_used"),
        
                'email.required_unless' => trans("$string_file.email_required"),
                'email.unique' => trans("$string_file.email_already_used"),
            ];
            $action = "DRIVER_SIGNUP";
            $validator = Validator::make($request->all(), [
                'phone' => [
                    'required_unless:for,EMAIL',
                    Rule::unique('drivers', 'phoneNumber')->where(function ($query) use ($merchant_id) {
                        return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                    })
                ],
                'email' => [
                    'required_unless:for,PHONE',
                    Rule::unique('drivers', 'email')->where(function ($query) use ($merchant_id) {
                        return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                    })
                ],
            ],$customMessages);

            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }
        } elseif ($request->type == 2) // forgot password
        {
            $action = "DRIVER_FORGOT_PASSWORD";
            $driver = Driver::where([['merchant_id', $merchant_id], [$field, $request->user_name], ['driver_delete', "=", NULL], ['driver_admin_status', "=", 1]])->first();
            if (empty($driver)) {
                return $this->failedResponse(trans("$string_file.phone_number_is_not_registered"));
            }
        } elseif ($request->type == 3) { 
            if($request->for == "PHONE"){
            $driver = Driver::where([['merchant_id', $merchant_id], [$field, $request->user_name], ['driver_delete', "=", NULL], ['driver_admin_status', "=", 1]])->first();
                if($driver){
                    $is_number_registered = true;
                }
            }
        }

        // for tezgo customisation
        // if((!empty($app_config) && $app_config->otp_from_firebase == 1) && (!empty($request->otp_firebase) && $request->otp_firebase == 1) )
        //otp from firebase
        if ((!empty($app_config) && $app_config->otp_from_firebase == 1) || (!empty($request->otp_firebase) && $request->otp_firebase == 1)) {
            return $this->successResponse(trans("$string_file.success"), array('auto_fill' => false, 'otp' => "", 'default_otp_enable' => false, 'default_otp' => ""));
        }

        $auto_fill = false;
        $otp = mt_rand(111111, 999999);
        if (isset($app_config->auto_fill_otp) && $app_config->auto_fill_otp == 1) {
            $auto_fill = true;
        } else {
            $SmsConfiguration = SmsConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
            if (!empty($SmsConfiguration->sms_provider) || !empty($email)) {
                $auto_fill = false;
            }
        }

        if($request->for == 'EMAIL'){
            event(new DriverSignupEmailOtpEvent($merchant_id, $request->email, $otp));
        }else{
            if (!$auto_fill) {
                $sms = new SmsController();
                $sms->SendSms($merchant_id, $phone, $otp, $action, $email);
            }
        }

        $default_otp_enable = false;
        $default_otp = "";
        if (isset($app_config->default_otp) && $app_config->default_otp == 1) {
            $default_otp_enable = true;
            $default_otp = "2022";
        }
         //Encrypt and decrypt
         if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($otp){
                    $otp = encryptText($otp,$secret,$iv);
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        return $this->successResponse($email_message, array('auto_fill' => $auto_fill, 'otp' => (string) $otp, 'default_otp_enable' => $default_otp_enable, 'default_otp' => $default_otp,'is_number_registered'=> $is_number_registered));
    }

    function updateDriverStatusDocument($driver_id, $vehicle_id, $doc_type)
    {
        $check_driver = Driver::Find($driver_id);
        $segment = $check_driver->Merchant->Segment->toArray();
        $status = get_driver_auto_verify_status($driver_id, 'final_status', 'vehicle');
        if ($doc_type == 'vehicle') {
            $already_added_driver_vehicle = get_driver_verified_vehicle($driver_id, $vehicle_id);
            // have to change this logic 20200406
            if ($already_added_driver_vehicle == 0) {
                $check_driver_uploaded_document = check_driver_document($driver_id, 'vehicle', $vehicle_id, null, 1, 'reject');
                $reject_case = $check_driver->reject_driver == 2 && $check_driver_uploaded_document == true ? true : false;
                // if all mandatory document are uploaded for vehicle
                // if ($check_driver->signupStep == 5 || $reject_case == true) {
                //                if (($check_driver->signupStep == 5 || $check_driver->reject_driver == 2) && $check_driver_uploaded_document == true) {
                if (($check_driver->signupStep == 5) && $check_driver_uploaded_document == true) {
                    $check_driver->signupStep = 6; // uploaded all vehicle mandatory document
                    $check_driver->online_offline = 2; // put driver offline
                    if ($reject_case == true) {
                        $check_driver->reject_driver = 1; // put driver pending and removed from reject mode
                    }
                }
                $check_driver->save();
            }

            $driver_vehicle = DriverVehicle::findOrFail($vehicle_id);
            //            $driver_vehicle->vehicle_active_status = 1;//get_driver_multi_existing_vehicle_status($driver_id);
            $driver_vehicle->vehicle_verification_status = $status;
            $driver_vehicle->save();
        } elseif ($doc_type == 'personal') {
            // true means all mandatory documents are uploaded
            $check_driver_uploaded_document = check_driver_document($driver_id, 'personal', null, 1, 'reject');
            if($check_driver->segment_group_id == 4)
            {
                $check_driver->signupStep = 9; // move driver into pending mode
                $check_driver->save();
            } 
            // if all mandatory document are uploaded
            // $hasSegmentGroupId4 = !empty(array_filter($segment, function ($item) {
            //     return isset($item['segment_group_id']) && $item['segment_group_id'] == 4;
            // }));
           
            // if($hasSegmentGroupId4){
            //     $check_driver->signupStep = 9; // move driver into pending mode
            //     $check_driver->save();
            // }
            // //            if (($check_driver->signupStep == 3 || $check_driver->reject_driver == 2) && $check_driver_uploaded_document == true) {
            if (($check_driver->signupStep == 3 ) && $check_driver_uploaded_document == true) {
                $check_driver->signupStep = 4; // uploaded all personal mandatory document
                $check_driver->online_offline = 2; // put driver offline
                if ($check_driver->reject_driver == 2 && $check_driver_uploaded_document == true) {
                    $check_driver->reject_driver = 1; // put driver offline
                }
                $check_driver->save();
            }
        }
    }

    public function addVehicleDocument($request)
    {
        $driver_id = $request->driver_id;
        $driver_detail = $driver = Driver::findOrFail($driver_id);
        $driver_id = $driver_detail->id;
        $driver_vehicle_id = $request->driver_vehicle_id;
        $status = get_driver_auto_verify_status($driver_id, 'final_status', 'doc');
        $vehicleDoc = DriverVehicleDocument::where([['driver_vehicle_id', '=', $driver_vehicle_id], ['document_id', '=', $request->document_id]])->first();
        $reuploadDocEnable = $driver->Merchant->ApplicationConfiguration->reupload_document;
        if (!empty($vehicleDoc->id) && ($vehicleDoc->document_verification_status == 4 || $vehicleDoc->document_verification_status == 3) && $status != 2)  // means uploading expired/rejected document
        {
            $driver_vehicle = $vehicleDoc->DriverVehicle;
            $driver_vehicle->vehicle_verification_status = 1; // moving vehicle into pending mode
            $driver_vehicle->save();
            $anyVerified = false;
            if($driver->Merchant->Configuration->add_multiple_vehicle == 1){
                $driverAllVehicle = DriverVehicle::where('driver_id',$driver->id)->get();
                $anyVerified = false;
                $unverifiedVehicles = [];
                foreach($driverAllVehicle as $vehicle){
                    $vehicleVerified = false;
                    foreach($vehicle->DriverVehicleDocument as $vehicleDocs){
                        
                        if($vehicle->vehicle_verification_status == 2 && $vehicleDocs->document_verification_status == 2){
                            $anyVerified = true;
                            $vehicleVerified = true;
                            break;
                        }
                    }
                    if (!$vehicleVerified) {
                        $unverifiedVehicles[] = $vehicle;
                    }
                }
            }
            
            if($driver->Merchant->Configuration->add_multiple_vehicle == 1 && $anyVerified){ //bypass the condtion in mutiple vehicle case
                
            }else{
                $driver_detail->signupStep = 8; // move driver into pending mode
                $driver_detail->is_approved = 2;
                if($reuploadDocEnable == 1){
                    $driver->reject_driver = 1;
                }
                $driver_detail->save();
            }        
                    
        }
        $merchant_id = $request->merchant_id;
        if ($request->type == 1) {
            $doc = DriverVehicleDocument::where([['document_id', $request->document_id], ['driver_vehicle_id', $driver_vehicle_id]])->first();
            if (empty($doc->id)) {
                $doc = new DriverVehicleDocument;
                if ($driver->signupStep == 9 && $status == 1) {
                    $anyVerified = false;
                    if($driver->Merchant->Configuration->add_multiple_vehicle == 1){
                        $driverAllVehicle = DriverVehicle::where('driver_id',$driver->id)->get();
                        $anyVerified = false;
                        $unverifiedVehicles = [];
                        foreach($driverAllVehicle as $vehicle){
                            $vehicleVerified = false;
                            foreach($vehicle->DriverVehicleDocument as $vehicleDocs){
                                
                                if($vehicle->vehicle_verification_status == 2 && $vehicleDocs->document_verification_status == 2){
                                    $anyVerified = true;
                                    $vehicleVerified = true;
                                    break;
                                }
                            }
                            if (!$vehicleVerified) {
                                $unverifiedVehicles[] = $vehicle;
                            }
                        }
                    }
                    if($driver->Merchant->Configuration->add_multiple_vehicle == 1 && $anyVerified){ //bypass the condtion in mutiple vehicle case
                        
                    }else{
                        $driver->signupStep = 8; // move the driver into pending mode in case of new document upload in case if auto-verification is disabled
                        $driver->is_approved = 2;
                        $driver->save();
                    }
                }
            }
            if($request->multi_part == 1){
                $additional_req = ['compress' => true,'custom_key' => 'product'];
                $doc->document = $this->uploadImage('document_image', 'vehicle_document', $merchant_id,'single',$additional_req);
            }else{
                $doc->document = $this->uploadBase64Image('document_image', 'vehicle_document', $merchant_id);
            }
            $doc->document_verification_status = $status;
            $doc->expire_date = !empty($request->expire_date) ? $request->expire_date : NULL;
        } else { // temp document
            $doc = DriverVehicleDocument::where([['driver_vehicle_id', '=', $driver_vehicle_id], ['document_id', '=', $request->document_id]])->first();
            $doc->temp_doc_verification_status = $status;
            if (!empty($request->expire_date) && isset($doc->expire_date) && $request->expire_date <= $doc->expire_date) {
                return false;
            }
            if($request->multi_part == 1){
                $additional_req = ['compress' => true,'custom_key' => 'product'];
                $doc->temp_document_file = $this->uploadImage('document_image', 'vehicle_document', $merchant_id,'single',$additional_req);
            }else{
                $doc->temp_document_file = $this->uploadBase64Image('document_image', 'vehicle_document', $merchant_id);
            }
            
            $doc->temp_expire_date = !empty($request->expire_date) ? $request->expire_date : NULL;
        }
        if (!empty($doc)) {
            $doc->document_number = isset($request->document_number) ? $request->document_number : null;
            $doc->document_id = $request->document_id;
            $doc->status = 1;
            $doc->driver_vehicle_id = $driver_vehicle_id;
            $doc->save();
        }

        if ($request->type == 1) {
            $driver_vehicle = DriverVehicle::find($request->driver_vehicle_id);
            if (!empty($vehicleDoc) && $vehicleDoc->document_verification_status == 4) {
                $remain = $driver_vehicle->total_expire_document - 1;
                if ($remain < 1) {
                    $driver_vehicle->vehicle_verification_status = $status;
                }
                $driver_vehicle->total_expire_document = $remain;
                $driver_vehicle->save();
            }
            // auto verify case
            $auto_verify = get_driver_auto_verify_status($driver_id);
            if ($auto_verify == 1) {
                $uploaded = 0;
                $driver_detail->DriverVehicles->where('id', $driver_vehicle_id)->map(function ($item, $key) use (&$uploaded) {
                    $uploaded += $item->DriverVehicleDocument->count();
                });
                $total_area_vehicle_documents = 0;
                if (!empty($driver_detail->CountryArea)) {
                    $total_area_vehicle_documents = count($driver_detail->CountryArea->VehicleDocuments->toArray());
                }
                if ($total_area_vehicle_documents == $uploaded) {
                    //                $driver_vehicle = DriverVehicle::find($request->driver_vehicle_id);
                    //                $driver_vehicle['vehicle_active_status'] = get_driver_multi_existing_vehicle_status($driver_id);
                    //                $driver_vehicle->save();
                    if ($driver_detail->signupStep == 5) {
                        $driver_detail->signupStep = 6;
                    }
                    $driver_detail->save();
                } else {
                    $this->updateDriverStatusDocument($driver_id, $driver_vehicle_id, 'vehicle');
                }
            } else {
                $this->updateDriverStatusDocument($driver_id, $driver_vehicle_id, 'vehicle');
            }
        }
        return true;
    }

    public function addSegmentDocument($request)
    {
        $driver = $request->user('api-driver');
        $driver_id = $request->driver_id;
        $segment_id = $request->segment_id;
        $status = get_driver_auto_verify_status($driver_id, 'final_status', 'doc');
        $merchant_id = $request->merchant_id;
        $doc = DriverSegmentDocument::where([['document_id', $request->document_id], ['driver_id', '=', $driver_id], ['segment_id', $segment_id]])->first();
        $reuploadDocEnable = $driver->Merchant->ApplicationConfiguration->reupload_document;
        if (!empty($doc->id) && ($doc->document_verification_status == 4 || $doc->document_verification_status == 3) && $status != 2) // means uploading expired/rejected document
        {
            $driver = $doc->Driver;
            $driver->signupStep = 8; // move driver into pending mode
            $driver->is_approved = 2;
            if($reuploadDocEnable == 1){
                $driver->reject_driver = 1;
            }
            $driver->save();
        }

        if ($request->type == 1) {
            if (empty($doc->id)) {
                $doc = new DriverSegmentDocument;
                if ($driver->signupStep == 9 && $status == 1) {
                    $driver->signupStep = 8; // move the driver into pending mode in case of new document upload in case if auto-verification is disabled
                    $driver->is_approved = 2;
                    $driver->save();
                }
            }
            if($request->multi_part == 1){
                $additional_req = ['compress' => true,'custom_key' => 'product'];
                $doc->document_file = $this->uploadImage('document_image', 'segment_document', $merchant_id,'single',$additional_req);
            }else{
                $doc->document_file = $this->uploadBase64Image('document_image', 'segment_document', $merchant_id);
            }
            $doc->document_verification_status = $status;
            $doc->expire_date = !empty($request->expire_date) ? $request->expire_date : NULL;
        } else {
            $doc->temp_doc_verification_status = $status;
            if (!empty($request->expire_date) && isset($doc->expire_date) && $request->expire_date <= $doc->expire_date) {
                return false;
            }
            if($request->multi_part == 1){
                $additional_req = ['compress' => true,'custom_key' => 'product'];
                $doc->temp_document_file = $this->uploadImage('document_image', 'segment_document', $merchant_id,'single',$additional_req);
            }else{
                $doc->temp_document_file = $this->uploadBase64Image('document_image', 'segment_document', $merchant_id);
            }
            
            $doc->temp_expire_date = !empty($request->expire_date) ? $request->expire_date : NULL;
        }
        if (!empty($doc)) {
            $doc->document_number = isset($request->document_number) ? $request->document_number : null;
            $doc->document_id = $request->document_id;
            $doc->status = 1;
            $doc->driver_id = $driver_id;
            $doc->segment_id = $segment_id;

            $doc->save();
        }
        return true;
    }

    public function addPersonalDocument($request)
    {
        $driver = $request->user('api-driver');
        $driver_id = $request->driver_id;
        $merchant_id = $request->merchant_id;
        $status = get_driver_auto_verify_status($driver_id, 'final_status', 'doc');
        $doc = DriverDocument::where([['document_id', $request->document_id], ['driver_id', $driver_id]])->first();
        $reuploadDocEnable = $driver->Merchant->ApplicationConfiguration->reupload_document;
        if (!empty($doc->id) && ($doc->document_verification_status == 4 || $doc->document_verification_status == 3) && $status != 2) // means uploading expired/rejected document
        {
            $driver = $doc->Driver;
            $driver->signupStep = 8; // move driver into pending mode
            $driver->is_approved = 2;
            if($reuploadDocEnable == 1){
                $driver->reject_driver = 1;
            }
            $driver->save();
        }
        if ($request->type == 1) {
            if (empty($doc->id)) {
                $doc = new DriverDocument;
                if ($driver->signupStep == 9 && $status == 1) {
                    $driver->signupStep = 8; // move the driver into pending mode in case of new document upload in case if auto-verification is disabled
                    $driver->is_approved = 2;
                    $driver->save();
                }
            }
            if($request->multi_part == 1){
                $additional_req = ['compress' => true,'custom_key' => 'product'];
                $doc->document_file = $this->uploadImage('document_image', 'driver_document', $merchant_id,'single',$additional_req);
            }else{
                $doc->document_file = $this->uploadBase64Image('document_image', 'driver_document', $merchant_id);
            }
            $doc->document_verification_status = $status;
            $doc->expire_date = !empty($request->expire_date) ? $request->expire_date : NULL;
        } else {
            $doc->temp_doc_verification_status = $status;
            if (!empty($request->expire_date) && isset($doc->expire_date) && $request->expire_date <= $doc->expire_date) {
                return false;
            }
            if($request->multi_part == 1){
                $additional_req = ['compress' => true,'custom_key' => 'product'];
                $doc->temp_document_file = $this->uploadImage('document_image', 'driver_document', $merchant_id,'single',$additional_req);
            }else{
                $doc->temp_document_file = $this->uploadBase64Image('document_image', 'driver_document', $merchant_id);
            }
            $doc->temp_expire_date = !empty($request->expire_date) ? $request->expire_date : NULL;
        }
        if (!empty($doc)) {
            $doc->document_number = isset($request->document_number) ? $request->document_number : null;
            $doc->driver_id = $driver_id;
            $doc->document_id = $request->document_id;
            $doc->status = 1;

            $doc->save();
            if ($request->type == 1) {
                $driver_vehicle_id = isset($doc->Driver->DriverVehicle[0]->id) ? $doc->Driver->DriverVehicle[0]->id : NULL;
                $this->updateDriverStatusDocument($driver_id, $driver_vehicle_id, 'personal');
            }
            return true;
        }
    }

    public function addDocument(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        //Encrypt and Decrypt
        $merchant = $driver->Merchant;
         if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($request->document_number){
                    $docNumber = decryptText($request->document_number,$secret,$iv);
                    $request->merge(['document_number'=> $docNumber]);
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        $validator = Validator::make($request->all(), [
            'driver_id' => [
                'required',
                Rule::exists('drivers', 'id')->where(function ($query) use ($request) {
                    $query->where('merchant_id', $request->merchant_id);
                }),
            ],
            'document_id' => 'required',
            'document_number_required' => 'required',
            'document_for' => 'required|in:PERSONAL,SEGMENT,VEHICLE',
            'expire_date' => 'required_if:expire_status,1',
            'document_image' => 'required',
            'type' => 'required|integer', //1 for normal 2 for temporary
            'driver_vehicle_id' => 'required_if:document_for,VEHICLE',
            'segment_id' => 'required_if:document_for,SEGMENT',
            //     [Rule::exists('driver_vehicles', 'id')->where(function ($query) use ($request) {
            //     $query->where('merchant_id', $request->merchant_id);
            //   })]

            'document_number' => [
                'required_if:document_number_required,1',
                Rule::unique('driver_documents', 'document_number')->where(function ($query) use($request){
                    $query->where([['document_number', '!=', ''], ['status', '=', 1]])
                        ->whereExists(function ($subQuery) use ($request) {
                          $subQuery->select(DB::raw(1))
                              ->from('drivers')
                              ->whereColumn('drivers.id', 'driver_documents.driver_id')
                              ->where('drivers.merchant_id', $request->merchant_id);
                      });
                })
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        if($merchant->id == 976){
            \Log::channel('debugger')->emergency(['merchant_id'=> 976,'doc_for'=>$request->document_for,'doc'=>"add_doc"]);
        }
        if ($request->document_for == 'PERSONAL') {
            $file_uploaded = $this->addPersonalDocument($request);
            if($merchant->id == 976){
                \Log::channel('debugger')->emergency(['merchant_id'=> 976,'file'=>$file_uploaded,'doc'=>"add_doc"]);
            }
            if ($file_uploaded) {
                $return_data = [];
                return $this->successResponse(trans("$string_file.success"), $return_data);
            } else {
                return $this->failedResponse(trans("$string_file.expiry_date_greater_than_already_uploaded_documents"));
            }
        } elseif ($request->document_for == 'VEHICLE') {
            $file_uploaded = $this->addVehicleDocument($request);
            if($merchant->id == 976){
                \Log::channel('debugger')->emergency(['merchant_id'=> 976,'file'=>$file_uploaded,'doc'=>"add_doc"]);
            }
            if ($file_uploaded) {
                $return_data = [];
                return $this->successResponse(trans("$string_file.success"), $return_data);
            } else {
                return $this->failedResponse(trans("$string_file.expiry_date_greater_than_already_uploaded_documents"));
            }
        } elseif ($request->document_for == 'SEGMENT') {
            $file_uploaded = $this->addSegmentDocument($request);
            if($merchant->id == 976){
                \Log::channel('debugger')->emergency(['merchant_id'=> 976,'file'=>$file_uploaded,'doc'=>"add_doc"]);
            }
            if ($file_uploaded) {
                $return_data = [];
                return $this->successResponse(trans("$string_file.success"), $return_data);
            } else {
                return $this->failedResponse(trans("$string_file.expiry_date_greater_than_already_uploaded_documents"));
            }
        }
        return $this->failedResponse(trans("$string_file.error"));
    }

    public function getDocumentList(Request $request)
    {
        $rules = [
            'document_for' => 'required|in:PERSONAL,VEHICLE,SEGMENT,ALL',
        ];
        
        $driver = $request->user('api-driver');
        $local_citizen_foreigner_documents =  $driver->Merchant->ApplicationConfiguration->local_citizen_foreigner_documents;
        if ($local_citizen_foreigner_documents == 1) {
            $rules['id'] = 'required_if:document_for,PERSONAL|in:1,2';
        }
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $driver_id = $driver->id;
        $vehicle_documents = [];
        $personal_document = [];
        $arr_vehicle_document_list = [];
        $arr_segment_document_list = [];
        $segment_document = [];
        $segment_group_id = $driver->segment_group_id;
        $country_area_id = $driver->country_area_id;
        $query = CountryArea::where('id', $country_area_id);
        $reminder_days = $driver->Merchant->Configuration->reminder_doc_expire;
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $currentDate = date('Y-m-d');
        $reminder_last_date = date('Y-m-d', strtotime('+' . $reminder_days . ' days'));
        $existing_enable = has_driver_multiple_or_existing_vehicle($driver->id, $driver->merchant_id);
        // dd($existing_enable);

        if ($request->document_for == 'PERSONAL') {
          
            $query->with([
                'Documents' => function ($p) {
                    $p->addSelect('documents.id', 'expire_date as expire_status', 'documentNeed as document_mandatory', 'document_number_required');
                    $p->where('documentStatus', 1);
                }
            ]);
        }
        if ($request->document_for == 'VEHICLE') {
            $query->with(['VehicleDocuments' => function ($q) {
                $q->addSelect('documents.id', 'expire_date as expire_status', 'documentNeed as document_mandatory', 'document_number_required');
                $q->where('documentStatus', 1);
            }]);
        }
        if ($request->document_for == 'SEGMENT') {
            $query->with(['Segment' => function ($q) use ($segment_group_id,$driver_id) {
                $q->where('segment_group_id', $segment_group_id)
                ->whereHas('Driver', function ($qq) use ($driver_id) {
                 $qq->where('driver_id', $driver_id);
                });
            }]);
            $query->with(['SegmentDocument' => function ($q) {
                $q->addSelect('documents.id', 'expire_date as expire_status', 'documentNeed as document_mandatory', 'document_number_required');
                $q->where('documentStatus', 1);
            }]);
        }

        $area_document = $query->first();
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $status_name = driver_document_status($string_file);
        \Config::get('custom.driver_document_status');
        // $vehicle_status_name = \Config::get('custom.vehicle_status');
        $merchant_id = $request->merchant_id;
        if($merchant_id == 976){
            \Log::channel('debugger')->emergency(['merchant_id'=> 976,'doc_for'=>$request->document_for]);
        }
        if ($request->document_for == 'PERSONAL') {
           
            $personal_document_data = $area_document->Documents;
            // status 1 means document active in that area
            $driver_personal_doc = DriverDocument::where([['driver_id', '=', $driver->id], ['status', '=', 1]])->get();
            $driver_personal_doc = array_column($driver_personal_doc->toArray(), NULL, 'document_id');
            $driver_personal_doc_id = array_keys($driver_personal_doc);
            foreach ($personal_document_data as $key => $value) {
                if ($local_citizen_foreigner_documents == 1) {
                    if ($request->id == 1) {
                        if ($value->pivot->document_type != 1) {
                            continue;
                        }
                    } elseif ($request->id == 2) {
                        if ($value->pivot->document_type != 2) {
                            continue;
                        }
                    }
                }

                $document_id = $value->id;
                $doc_image = "";
                $temp_doc_image = "";
                $temp_doc_status = 0;
                $temp_verification_status_text = '';
                $verification_status = 0;
                $verification_status_text = $status_name[$verification_status];
                $expire_date_text = '';
                if (in_array($document_id, $driver_personal_doc_id)) {
                    $verification_status = $driver_personal_doc[$document_id]['document_verification_status'] ?? 0;
                    $verification_status_text = $status_name[$verification_status];
                    $doc_image = get_image($driver_personal_doc[$document_id]['document_file'], 'driver_document', $request->merchant_id, true, false);
                    $temp_document_file = $driver_personal_doc[$document_id]['temp_document_file'];
                    $temp_doc_image = !empty($temp_document_file) ? get_image($temp_document_file, 'driver_document', $request->merchant_id, true, false) : '';
                    $temp_document_status = $driver_personal_doc[$document_id]['temp_doc_verification_status'];
                    switch ($temp_document_status) {
                        case 1:
                            $temp_verification_status_text = trans("$string_file.pending_for_verification");
                            break;
                        case 2:
                            $temp_verification_status_text = trans("$string_file.verified");
                            break;
                        case 3:
                            $temp_verification_status_text = trans("$string_file.rejected");
                            break;
                        case 4:
                            $temp_verification_status_text = trans("$string_file.expired");
                            break;
                    }
                    $expire_date = $driver_personal_doc[$document_id]['expire_date'];
                    $expire_date_text = $expire_date ?? '';
                    if ($expire_date >= $currentDate && $expire_date <= $reminder_last_date && $verification_status == 2) {
                        $verification_status_text = trans("$string_file.expire_date") . ' ' . $expire_date;
                        if (empty($temp_document_file) || $temp_document_status == 3 || $temp_document_status == 4) {
                            $temp_doc_status = 1;
                        } elseif ($temp_document_status == 1) {
                            $temp_doc_status = 2;
                        }
                    }
                }
                $personal_document[] = [
                    'id' => $value->id,
                    'expire_status' => $value->expire_status,
                    'document_mandatory' => $value->document_mandatory,
                    'document_number_required' => $value->document_number_required,
                    "document_file" => !empty($doc_image) ? $doc_image : get_image('stub_document'),
                    'document_verification_status' => $verification_status_text,
                    'document_status_int' => $verification_status,
                    'documentname' => $value->DocumentName,
                    'expire_date' => $expire_date_text,
                    'temp_doc_status' => $temp_doc_status,
                    'temp_document_file' => $temp_doc_image,
                    'temp_document_verification_status' => $temp_verification_status_text,
                ];

            }
          
        }
        if ($request->document_for == 'VEHICLE') {
            if ($existing_enable){
                $driver_vehicle_id = $request->driver_vehicle_id;
                if (empty($driver_vehicle_id)){
                    $online_configuration = $this->getDriverOnlineConfig($driver, 'all');
                    if(count($online_configuration['driver_vehicle_id']) == 0){
                        return $this->failedResponse(trans("$string_file.either_vehicle_or_personal_docs_pending"));
                    }
                    $assigned_vehicle_id = $online_configuration['driver_vehicle_id'][0];
                    $arr_vehicle = $driver->DriverVehicle->where('id', $assigned_vehicle_id);
                }else{
                    $arr_vehicle = $driver->DriverVehicle->where('id', $driver_vehicle_id);
                }
            }else{
                $driver_vehicle_id = $request->driver_vehicle_id;
                $arr_vehicle = DriverVehicle::where([['owner_id', '=', $driver->id]])
                    ->where(function ($q) use ($driver_vehicle_id) {
                        if (!empty($driver_vehicle_id)) {
                            $q->where('id', $driver_vehicle_id);
                        }
                    })
                    ->get();
            }
            foreach ($arr_vehicle as $vehicle) {
                if (!empty($vehicle->id)) {
                    $vehicle_type = $vehicle->vehicle_type_id;
                    $vehicle_documents = $area_document->VehicleDocuments()->wherePivot('vehicle_type_id', '=', $vehicle_type)->get();
                    $driver_vehicle_doc = DriverVehicleDocument::where([['status', '=', 1], ['driver_vehicle_id', '=', $vehicle->id]])->get();
                    $driver_vehicle_doc = array_column($driver_vehicle_doc->toArray(), NULL, 'document_id');
                    $driver_vehicle_doc_id = array_keys($driver_vehicle_doc);
                    $arr_vehicle_doc_list = [];
                    foreach ($vehicle_documents as $keys => $values) {
                        if ($vehicle_type == $values['pivot']->vehicle_type_id) {
                            $document_id = $values->id;
                            $doc_image = "";
                            $temp_verification_status_text = '';
                            $temp_doc_image = "";
                            $temp_doc_status = 0;
                            $verification_status = 0;
                            $verification_status_text = $status_name[$verification_status];
                            $expire_date_text = '';
                            if (in_array($document_id, $driver_vehicle_doc_id)) {
                                $verification_status = $driver_vehicle_doc[$document_id]['document_verification_status'] ?? 0;
                                $verification_status_text = $status_name[$verification_status];
                                $doc_image = get_image($driver_vehicle_doc[$document_id]['document'], 'vehicle_document', $request->merchant_id, true, false);
                                $temp_document_file = $driver_vehicle_doc[$document_id]['temp_document_file'];
                                $temp_doc_image = !empty($temp_document_file) ? get_image($temp_document_file, 'vehicle_document', $request->merchant_id, true, false) : '';
                                $temp_document_status = $driver_vehicle_doc[$document_id]['temp_doc_verification_status'];
                                switch ($temp_document_status) {
                                    case 1:
                                        $temp_verification_status_text = trans("$string_file.pending_for_verification");
                                        break;
                                    case 2:
                                        $temp_verification_status_text = trans("$string_file.verified");
                                        break;
                                    case 3:
                                        $temp_verification_status_text = trans("$string_file.rejected");
                                        break;
                                    case 4:
                                        $temp_verification_status_text = trans("$string_file.expired");
                                        break;
                                }
                                $expire_date = $driver_vehicle_doc[$document_id]['expire_date'];
                                $expire_date_text = $expire_date ?? '';
                                if ($expire_date >= $currentDate && $expire_date <= $reminder_last_date && $verification_status == 2) {
                                    $verification_status_text = trans("$string_file.expire_date") . ' ' . $expire_date;
                                    if (empty($temp_document_file) || $temp_document_status == 3 || $temp_document_status == 4) {
                                        $temp_doc_status = 1;
                                    } elseif ($temp_document_status == 1) {
                                        $temp_doc_status = 2;
                                    }
                                }
                            }
                            $arr_vehicle_doc_list[] = [
                                "id" => $values->id,
                                // "expire_status" => $values->expire_status,
                                // "document_mandatory" => $values->document_mandatory,
                                "expire_status" => $values->expire_date,
                                "document_mandatory" => $values->documentNeed,
                                "document_number_required" => $values->document_number_required,
                                "document_file" => !empty($doc_image) ? $doc_image : get_image('stub_document'),
                                "documentname" => $values->DocumentName,
                                "document_verification_status" => $verification_status_text,
                                "document_status_int" => $verification_status,
                                'expire_date' => $expire_date_text,
                                "temp_doc_status" => $temp_doc_status,
                                'temp_document_file' => $temp_doc_image,
                                'temp_document_verification_status' => $temp_verification_status_text,
                            ];
                        }
                    }
                    $arr_vehicle_document_list[] = [
                        'vehicle_id' => $vehicle->id,
                        'vehicle_type' => $vehicle->VehicleType->VehicleTypeName,
                        'vehicle_type_image' => get_image($vehicle->VehicleType->vehicleTypeImage, 'vehicle', $request->merchant_id, true, false),
                        'vehicle_number' => $vehicle->vehicle_number,
                        'vehicle_status' => $status_name[$vehicle->vehicle_verification_status],
                        'document_list' => $arr_vehicle_doc_list,
                    ];
                }
            }
        }
        if ($request->document_for == 'SEGMENT') {
            // status 1 means document active in that area
            $arr_segment = $area_document->Segment;
            $segment_documents = $area_document->SegmentDocument;
            foreach ($arr_segment as $segment) {
                if (!empty($segment->id)) {
                    $segment_id = $segment->id;
                    $driver_segment_doc = DriverSegmentDocument::where([['status', '=', 1], ['driver_id', '=', $request->driver_id], ['segment_id', '=', $segment_id]])->get();
                    $driver_segment_doc = array_column($driver_segment_doc->toArray(), NULL, 'document_id');
                    $driver_segment_doc_id = array_keys($driver_segment_doc);
                    $arr_segment_doc_list = [];
                    foreach ($segment_documents as $keys => $values) {
                        $segment_doc_id = $values['pivot']->segment_id;
                        if ($segment_id == $segment_doc_id) {
                            $document_id = $values->id;
                            $doc_image = "";
                            $temp_doc_image = "";
                            $temp_doc_status = 0;
                            $temp_verification_status_text = '';
                            $verification_status = 0;
                            $verification_status_text = $status_name[$verification_status];
                            $expire_date_text = '';
                            if (in_array($document_id, $driver_segment_doc_id)) {
                                $verification_status = $driver_segment_doc[$document_id]['document_verification_status'] ?? 0;
                                $verification_status_text = $status_name[$verification_status];
                                $doc_image = get_image($driver_segment_doc[$document_id]['document_file'], 'segment_document', $request->merchant_id, true, false);
                                $temp_document_file = $driver_segment_doc[$document_id]['temp_document_file'];
                                $temp_doc_image = !empty($temp_document_file) ? get_image($temp_document_file, 'segment_document', $request->merchant_id, true, false) : '';
                                $temp_document_status = $driver_segment_doc[$document_id]['temp_doc_verification_status'];
                                switch ($temp_document_status) {
                                    case 1:
                                        $temp_verification_status_text = trans("$string_file.pending_for_verification");
                                        break;
                                    case 2:
                                        $temp_verification_status_text = trans("$string_file.verified");
                                        break;
                                    case 3:
                                        $temp_verification_status_text = trans("$string_file.rejected");
                                        break;
                                    case 4:
                                        $temp_verification_status_text = trans("$string_file.expired");
                                        break;
                                }
                                $expire_date = $driver_segment_doc[$document_id]['expire_date'];
                                $expire_date_text = $expire_date ?? '';
                                if ($expire_date >= $currentDate && $expire_date <= $reminder_last_date && $verification_status == 2) {
                                    $verification_status_text = trans("$string_file.expire_date") . ' ' . $expire_date;
                                    if (empty($temp_document_file) || $temp_document_status == 3 || $temp_document_status == 4) {
                                        $temp_doc_status = 1;
                                    } elseif ($temp_document_status == 1) {
                                        $temp_doc_status = 2;
                                    }
                                }
                            }
                            $arr_segment_doc_list[] = [
                                "id" => $values->id,
                                "expire_status" => $values->expire_status,
                                "document_mandatory" => $values->document_mandatory,
                                "document_number_required" => $values->document_number_required,
                                "document_file" => !empty($doc_image) ? $doc_image : get_image('stub_document'),
                                "documentname" => $values->DocumentName,
                                "document_verification_status" => $verification_status_text,
                                "document_status_int" => $verification_status,
                                'expire_date' => $expire_date_text,
                                "temp_doc_status" => $temp_doc_status,
                                'temp_document_file' => $temp_doc_image,
                                'temp_document_verification_status' => $temp_verification_status_text,
                            ];
                        }
                    }
                    $arr_segment_document_list[] = [
                        'segment_id' => $segment->id,
                        'checkable' => true,
                        'name' => !empty($segment->Name($merchant_id)) ? $segment->Name($merchant_id) : $segment->slag, // $segment->slag;
                        'icon' => !empty($segment->segment_icon) ? get_image($segment->segment_icon, 'segment', $request->merchant_id, true, false) : get_image($segment->segment_icon, 'segment', $request->merchant_id, false, false),
                        'document_list' => $arr_segment_doc_list,
                    ];
                }
            }
        }
        if($merchant_id == 976){
            \Log::channel('debugger')->emergency(['merchant_id'=> 976,'doc_for'=>$request->document_for,'personal_doc' => $personal_document, 'vehicle_doc' => $arr_vehicle_document_list, 'segment_doc' => $arr_segment_document_list]);
        }
        $return_data = array('personal_doc' => $personal_document, 'vehicle_doc' => $arr_vehicle_document_list, 'segment_doc' => $arr_segment_document_list);
        return $this->successResponse(trans("$string_file.documents"), $return_data);
    }

    public function getSegmentDocumentData($segment_documents, $driver_segment_doc, $segment_id, $merchant_id)
    {
        $string_file = $this->getStringFile($merchant_id);
        $status_name = driver_document_status($string_file);
        $verification_status = 0;
        $driver_segment_doc = array_column($driver_segment_doc->toArray(), NULL, 'document_id');
        $driver_segment_doc_id = array_keys($driver_segment_doc);
        $arr_segment_doc_list = [];
        foreach ($segment_documents as $keys => $values) {
            // p($values);
            $segment_doc_id = $values['pivot']->segment_id;
            if ($segment_id == $segment_doc_id) {
                $document_id = $values->id;
                $image = '';
                $verification_status = 0;
                if (in_array($document_id, $driver_segment_doc_id)) {
                    $image = $driver_segment_doc[$document_id]['document_file'];
                    $image = get_image($image, 'segment_document', $merchant_id, true, false);
                    $verification_status = isset($driver_segment_doc[$document_id]['document_verification_status']) ? $driver_segment_doc[$document_id]['document_verification_status'] : 0;
                }
                $arr_segment_doc_list[] = [
                    "id" => $values->id,
                    "expire_status" => $values->expire_status,
                    "document_mandatory" => $values->document_mandatory,
                    "document_number_required" => $values->document_number_required,
                    "document_file" => !empty($image) ? $image : get_image('stub_document'),
                    "documentname" => $values->DocumentName,
                    "document_verification_status" => $status_name[$verification_status],
                    "document_status_int" => $verification_status,
                ];
            }
        }
        // p('in');
        return $arr_segment_doc_list;
    }
    public function Driver(Request $request)
    {
        $driverDetail = $request->user('api-driver');
        $string_file = $this->getStringFile($driverDetail->merchant_id);
        $access_token_id = $request->user('api-driver')->token()->id;
        $driverDetail->access_token_id = $access_token_id;
        $driverDetail->save();
        $multi_existing = has_driver_multiple_or_existing_vehicle($driverDetail->id);
        if ($multi_existing == true && $driverDetail->signupStep != 2) {
            $driverDetail->vehicleId = '';
        } else {
            $status = $multi_existing == true ? 2 : 1;
            $driverVehicle = DriverVehicle::whereHas('Drivers', function ($query) use ($driverDetail, $status) {
                $query->where([['id', $driverDetail->id], ['vehicle_active_status', $status]]);
            })->first();
            $driverDetail->vehicleId = isset($driverVehicle) ? $driverVehicle->id : '';
        }
        $data = new DriverResource($driverDetail);
        return response()->json(['result' => "1", 'message' => trans("$string_file.details"), 'data' => $data]);
    }

    public function DriverDetails(Request $request)
    {
        $driverDetail = $request->user('api-driver');
        $access_token_id = $request->user('api-driver')->token()->id;
        $driverDetail->access_token_id = $access_token_id;
        $driverDetail->save();
        $driver_id = $driverDetail->id;
        $Driver = Driver::with('DriverVehicles')->where('id', $driver_id)->first();
        $totalEarning = Booking::select('driver_cut')->Where([['driver_id', '=', $driver_id], ['booking_closure', '=', 1]])->get();
        $totalRides = count($totalEarning);
        $earning = array(
            'total_ride' => $totalRides,
            'total_earning' => sprintf("%0.2f", array_sum(array_pluck($totalEarning, 'driver_cut'))),
            'rating' => $Driver->rating . "/" . $totalRides . " Users"
        );
        $Driver->totalEarning = $earning;
        $driverConfig = DriverRideConfig::select('latitude', 'longitude', 'radius')->where('driver_id', $driver_id)->first();
        $Driver->driverConfig = $driverConfig;
        $any_document_expire = false;
        $docment = DriverDocument::where([['driver_id', '=', $driver_id], ['document_verification_status', '=', 4]])->first();
        if (!empty($docment->id)) {
            $any_document_expire = true;
        }
        $Driver->any_document_expire = $any_document_expire;
        $address = DriverAddress::where([['driver_id', '=', $driver_id], ['address_status', '=', 1]])->first();
        if (empty($address)) {
            $location = "";
        } else {
            $location = $address->location;
        }
        $smoker_type = "";
        $allow_other_smoker = "";

        if ($request->smoker == 1) {
            $smoker = $driverDetail->DriverRideConfig;
            if ($smoker) {
                $smoker_type = $smoker->smoker_type;
                $allow_other_smoker = $smoker->allow_other_smoker;
            }
        }
        $Driver->smoker_type = $smoker_type;
        $Driver->allow_other_smoker = $allow_other_smoker;

        $Driver->selected_address = $location;
        $Driver->profile_image = get_image($Driver->profile_image, 'driver', $Driver->merchant_id, true, false);
        foreach ($Driver->DriverVehicles as $vehicel) {
            $vehicel->vehicle_image = get_image($vehicel->vehicle_image, 'vehicle_document', $Driver->merchant_id, true, false);
            $vehicel->vehicle_number_plate_image = get_image($vehicel->vehicle_number_plate_image, 'vehicle_document', $Driver->merchant_id, true, false);
        }
        return response()->json(['result' => "1", 'message' => "Driver Details", 'data' => $Driver]);
    }

    public function LoginOtp(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $request_fields = [
            'phone' => 'required',
            'login_otp' => 'required',
            'player_id' => 'required_without:website|string|min:32',
        ];
        $validator = Validator::make(
            $request->all(),
            $request_fields,
            [
                'phone.exists' => trans("$string_file.phone_number_is_not_registered"),
                'player_id.required' => trans("$string_file.invalid_player_id"),
                'player_id.min' => trans("$string_file.invalid_player_id")
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]); 
        }

        DB::beginTransaction();
        try{
            $parameter = $request->driver_login_type == "EMAIL" ? "email" : "phoneNumber";
            $driver = Driver::where([[$parameter, '=', $request->phone], ['merchant_id', '=', $merchant_id]])->latest()->first();
            if (empty($driver)) {
                $msg = $request->driver_login_type == "EMAIL" ? trans("$string_file.email_is_not_registered") : trans("$string_file.phone_number_is_not_registered");
                return $this->failedResponse($msg);
            }
            if ($driver->driver_admin_status == 2 || $driver->driver_delete == 1) {
                $msg = $driver->driver_delete == 1 ? trans("$string_file.account_has_been_deleted") : trans("$string_file.account_has_been_inactivated");
                return $this->failedResponse($msg);
            }

            $client = Client::where([['user_id', '=', $merchant_id], ['password_client', '=', 1]])->first();
            Config::set('auth.guards.api.provider', 'driverOtp');
            $request->request->add([
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => "$driver->id",
                'password' => '',
                'scope' => '',
            ]);
            $token_generation_after_login = Request::create(
                'oauth/token',
                'POST'
            );
            $collect_response = \Route::dispatch($token_generation_after_login)->getContent();
            $collectArray = json_decode($collect_response);
            if (isset($collectArray->error)) {
                return $this->failedResponse(trans("$string_file.failed_cred"));
                //            return response()->json(['result' => "0", 'message' => trans('auth.failed'), 'data' => []]);
            }
            Driver::Logout($request->player_id, $merchant_id); // logout driver from other devices
            $driverDetails = Driver::where([[$parameter, '=', $request->phone], ['merchant_id', '=', $merchant_id]])->latest()->first();
            if ($driverDetails->login_logout == 1 && $driverDetails->player_id != $request->player_id) {
                if (has_driver_multiple_or_existing_vehicle($driverDetails->id)) {
                    $recentBooking = Booking::where('driver_id', $driverDetails->id)->whereIn('booking_status', [1002, 1003, 1004])->latest()->first();
                    if (!empty($recentBooking->driver_id)) {
                        //                    return response()->json(['result' => "0", 'message' => trans('api.driver_not_login'), 'data' => []]);
                    } else {
                        $driverDetails->online_offline = 2;
                        $driverDetails->free_busy = 2;
                        $driverDetails->save();
                        // no need to maintain vehicle status on online time
                    }
                }
                $access_token_id = $driverDetails->access_token_id;
                \DB::table('oauth_access_tokens')->where('id', '=', $access_token_id)->delete();
                \DB::table('oauth_refresh_tokens')->where('access_token_id', '=', $access_token_id)->delete();
                //            $playerids = array($driverDetails->player_id);
                $data = [];
                $title = trans("$string_file.session_expired");
                $message = trans("$string_file.session_expire_message");
                $data['notification_type'] = "LOGOUT";
                $data['segment_type'] = "";
                $data['segment_data'] = [];
                $arr_param = ['driver_id' => $driverDetails->id, 'merchant_id' => $merchant_id, 'message' => $message, 'title' => $title, 'data' => $data, 'large_icon' => ""];
                Onesignal::DriverPushMessage($arr_param);
            }

            // update unique number
            if (!empty($request->unique_number)) {
                $driverDetails->unique_number = $request->unique_number;
            }
            if (isset($request->device) && !empty($request->device)) {
                $driverDetails->device = $request->device;
            }
            $driverDetails->player_id = $request->player_id;
            $driverDetails->device = $request->device;
            $driverDetails->login_logout = 1; // update login status
            $driverDetails->ats_id = $request->ats_id; // update device ats id of driver
            $driverDetails->save(); // update driver
            //        $this->UpdatePlayerId($merchant_id, $request->phone, $request->player_id, $parameter);
            $taxi_company = false;
            if ($driverDetails->taxi_company_id != NULL) {
                $taxi_company = true;
            }

            $push_notification = get_merchant_notification_provider($merchant_id, $driverDetails->id, 'driver');
            $return_data = array(
                'driver' => new DriverLoginResource($driverDetails),
                'access_token' => $collectArray->access_token,
                'push_notification' => $push_notification,
            );

            DB::commit();
            return $this->successResponse(trans("$string_file.success"), $return_data);
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function AccountTypes(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $driver_account_types = AccountType::where([['merchant_id', $merchant_id], ['status', true]])->get(['id']);
        if ($driver_account_types->isNotEmpty()) :
            $account_types = $driver_account_types->map(function ($item, $key) {
                $item->name = $item->name;
                return $item->only(['id', 'name']);
            })->values();
            return response()->json(['result' => "1", 'message' => trans("$string_file.data_found"), 'account_types' => $account_types]);
        endif;
        return response()->json(['result' => "0", 'message' => trans("$string_file.data_not_found"), 'account_types' => []]);
    }

    public function Configuration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'apk_version' => 'required',
            'device' => 'required|integer|between:1,2',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $driver_id = NULL;

        if (isset($request->driver_id) && !empty($request->driver_id) && !empty($request->player_id)) {
            $driver = Driver::findOrFail($request->driver_id);
            $driver->player_id = $request->player_id;
            $driver->device = $request->device;
            $driver->save();
            $driver_id = $driver->id;
        }
        $merchant_id = $request->merchant_id;
        $merchant = Merchant::find($merchant_id);
        $string_file = $this->getStringFile(NULL, $merchant);
        if (empty($merchant->Configuration) || empty($merchant->ApplicationConfiguration) || empty($merchant->BookingConfiguration)) {
            return response()->json(['result' => "0", 'message' => trans("$string_file.configuration_not_found"), 'data' => []]);
        }
        $merchant->logged_driver_id = $driver_id;
        $data = new DriverConfiguration($merchant);
        if ($merchant_id == 333) {
            if ((float)$request->apk_version <= 2.7  && $request->device == "1") {
                $custom_msg = "Veuillez mettre à jour vos applications depuis
                Playstore.\nPlease Update your apps from
                playstore.";
                return $this->failedResponse($custom_msg, $data);
            }
        }
        return $this->successResponse(trans("$string_file.data_found"), $data);
    }

    public function DriverTermUpdate(Request $request)
    {
        $driverDetail = $request->user('api-driver');
        $driverDetail->term_status = 0;
        $driverDetail->save();
        $string_file = $this->getStringFile(null, $driverDetail->Merchant);
        return response()->json(['result' => "1", 'message' => trans("$string_file.terms_conditions"), 'data' => []]);
    }

    //    public function limitDriver(Request $request)
    public function driverSetRadius(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //            'latitude' => 'required',
            //            'longitude' => 'required',
            'radius' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $driver = $request->user('api-driver');
            DriverRideConfig::updateOrCreate(['driver_id' => $driver->id], [
                //                'latitude' => $request->latitude,
                //                'longitude' => $request->longitude,
                'radius' => $request->radius,
            ]);
            DB::commit();
            return $this->successResponse('Radius set successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
    }



    // tutu changes
    public function withdrawWallet(Request $request)
    {
        $valid = validator($request->all(), [
            'amount' => 'required|numeric'
        ]);

        if ($valid->fails()) {
            return response()->json([
                'result' => 0,
                'message' => __('api.validation.failed')
            ]);
        }

        $driver = $request->user('api-driver');

        if ($request->amount > $driver->wallet_money) {
            return response()->json([
                'result' => 0,
                'message' => __('api.exceeded.amount')
            ]);
        }


        $payment_config = PaymentConfiguration::where('merchant_id', $driver->merchant_id)->first();
        if ($payment_config && $payment_config->wallet_withdrawal_enable == 1) {

            if ($request->amount > $payment_config->wallet_withdrawal_min_amount) {
                return response()->json([
                    'result' => 0,
                    'message' => __('api.exceeded.amount')
                ]);
            }

            // deduct from driver wallet
            //            $driver->wallet_money = $driver->wallet_money - $request->amount;
            $driver->outstand_amount = (float) $driver->outstand_amount + $request->amount;
            $driver->save();

            $paramArray = array(
                'driver_id' => $driver->id,
                'booking_id' => null,
                'amount' => $request->amount,
                'narration' => NULL,
            );
            WalletTransaction::WalletDeduct($paramArray);
            return response()->json([
                'result' => 1,
                'message' => __('api.withdraw.success'),
                'data' => []
            ]);
        }

        return response()->json([
            'result' => 0,
            'message' => __('api.configuration.notfound')
        ]);
    }

    // tutu changes
    public function rewardPoints(Request $request)
    {
        $driver = $request->user('api-driver');
        $app_config = ApplicationConfiguration::where('merchant_id', $driver->merchant_id)->first();
        if ($app_config->reward_points != 1) {
            return response()->json([
                'result' => 0,
                'message' => __('api.unauthorized')
            ]);
        }

        $reward_points_data = \App\Models\RewardPoint::where('merchant_id', $driver->merchant_id)
            ->where('country_area_id', $driver->country_area_id)
            ->where('active', 1)
            ->first();


        if (!$reward_points_data) {
            return response()->json([
                'result' => 0,
                'message' => __('api.reward.notfound'),
                'data' => []
            ]);
        }
        return response()->json([
            'result' => 1,
            'message' => __('api.reward.data'),
            'data' => [
                'usable_reward_points' => $driver->usable_reward_points,
                'reward_points' => $driver->reward_points
            ]
        ]);
    }

    //get driver's all documents for website on frontend
    public function driverDocument(Request $request)
    {
        //        $customMessages = [
        //            'country_area_id' => trans('api.country_area_id'),
        //        ];
        $validator = Validator::make($request->all(), [
            'country_area_id' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        // country_are_document for personal document
        $country_area = CountryArea::with('Documents', 'VehicleDocuments')->find($request->country_area_id);
        $merchant_id = $country_area->merchant_id;
        $document_list = $country_area->Documents;
        $all_document_list = $document_list->map(function ($item, $key) use ($merchant_id) {
            return array(
                'document_id' => $item->id,
                'document_name' => $item->getDocumentAttribute->documentname,
                'document_file' => get_image($item->document_file, 'driver_document', $merchant_id, true, false),
            );
        });
        // document_country_are for vehicle document
        $vehicle_document_list = $country_area->VehicleDocuments;
        $all_vehicle_document_list = $vehicle_document_list->map(function ($item, $key) use ($merchant_id) {
            return array(
                'document_id' => $item->id,
                'document_name' => $item->getDocumentAttribute->documentname,
                'document_file' => get_image($item->document_file, 'vehicle_document', $merchant_id, true, false),
            );
        });

        $data = [];
        $data['personal_doc'] = [];
        $data['vehicle_doc'] = [];
        if (count($all_document_list) > 0 || count($all_vehicle_document_list) > 0) {
            $status = 1;
            $data['personal_doc'] = $all_document_list;
            $data['vehicle_doc'] = $all_vehicle_document_list;
        }
        return response()->json(['result' => $status, 'data' => $data]);
    } //get driver's all documents

    //    public function sendMoneyToUser(Request $request)
    //    {
    //        $validator = Validator::make($request->all(), [
    //            'booking_id' => 'required',
    //            'amount' => 'required',
    //        ]);
    //        if ($validator->fails()) {
    //            $errors = $validator->messages()->all();
    //            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
    //        }
    //        $driver = $request->user('api-driver');
    //        if ($driver->wallet_money < $request->amount) {
    //            return response()->json(['result' => "0", 'message' => trans('api.send_money_error'), 'data' => []]);
    //        }
    //
    //        $booking = Booking::find($request->booking_id);
    //        $receipt = "Send Money To " . $booking->User->first_name;
    //        $paramArray = array(
    //            'driver_id' => $driver->id,
    //            'booking_id' => $request->booking_id,
    //            'amount' => $request->amount,
    //            'narration' => 7,
    //            'platform' => 2,
    //            'payment_method' => 3,
    //            'receipt' => $receipt,
    //        );
    //        WalletTransaction::WalletDeduct($paramArray);
    ////        \App\Http\Controllers\Helper\CommonController::WalletDeduct($driver->id,$request->booking_id,$request->amount,7,2,3,$receipt);
    ////        $driver->wallet_money = $driver->wallet_money-$request->amount;
    ////        $driver->save();
    ////        $driverWallet = DriverWalletTransaction::create([
    ////            'merchant_id' => $driver->merchant_id,
    ////            'driver_id' => $driver->id,
    ////            'transaction_type' => 2,
    ////            'payment_method' => 3,
    ////            'receipt_number' => $request->booking_id,
    ////            'amount' => sprintf("%0.2f", $request->amount),
    ////            'platform' => 2,
    ////            'description' => "Send Money To ".$booking->User->first_name,
    ////            'narration' => 3,
    ////        ]);
    ////        $data = ['message' => $driverWallet->description];
    ////        Onesignal::DriverPushMessage($driver->id,$data,$driverWallet->description,3,$driver->merchant_id);
    //
    //        $user = $booking->User;
    //        $paramArray = array(
    //            'user_id' => $user->id,
    //            'booking_id' => $request->booking_id,
    //            'amount' => $request->amount,
    //            'narration' => 6,
    //            'platform' => 2,
    //            'payment_method' => 1,
    //            'receipt' => $receipt
    //        );
    //        WalletTransaction::UserWalletCredit($paramArray);
    ////        \App\Http\Controllers\Helper\CommonController::UserWalletCredit($user->id,$request->booking_id,$request->amount,6,2,1,$receipt);
    ////        $user->wallet_balance = $user->wallet_balance+$request->amount;
    ////        $user->save();
    ////        UserWalletTransaction::create([
    ////            'merchant_id' => $user->merchant_id,
    ////            'user_id' => $user->id,
    ////            'platfrom' => 2,
    ////            'amount' => $request->amount,
    ////            'receipt_number' => "Application",
    ////            'type' => 1,
    ////        ]);
    ////        $msg = trans('api.money_received_from').' '.$driver->first_name;
    ////        $data = ['message' => $msg];
    ////        Onesignal::UserPushMessage($user->id,$data,$msg,3,$user->merchant_id);
    //        return response()->json(['result' => "1", 'message' => "Money Send Successfully"]);
    //    }

    public function getServiceTimeSlot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')],
            //            'calling_from' => 'required',
            'auto_assign' => 'required_if:calling_from,==,user',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            if ($request->calling_from == 'user') {
                $slot_type = "selected"; // for user app
            } else {
                $slot_type = "all"; // for driver app
            }
            $string_file = $this->getStringFile($request->merchant_id);
            $request->merge(['slot_type' => $slot_type]);
            $return_data = ServiceTimeSlot::getServiceTimeSlot($request, $string_file);
            return $this->successResponse(trans("$string_file.data_found"), $return_data);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    // driver Signup step 1
    public function RegStepOne(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        if($request->timestampvalue){
        $driver = Driver::where('timestampvalue', $request->timestampvalue)->where(function ($query) use ($request) {
            $query->where('email', $request->email)->orWhere('phoneNumber', $request->phone);
        })->first();
        if ($driver) {
            return $this->successResponse(trans("$string_file.success"), [['driver_id' => $driver->id]]);
        }
        }
        //Encrypt Decrypt
        $merchant = Merchant::find($merchant_id);
        $iv = "";
        $secret = "";
        if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];

                if($request->email){
                    $email = decryptText($request->email,$secret,$iv);
                    $request->merge(['email'=> $email]);

                }
                
                if($request->phone){
                    $phone = decryptText($request->phone,$secret,$iv);
                    $request->merge(['phone'=> $phone]);

                }
                if($request->first_name){
                    $fname = decryptText($request->first_name,$secret,$iv);
                    $request->merge(['first_name'=> $fname]);

                }

                if($request->last_name){
                    $lname = decryptText($request->last_name,$secret,$iv);
                    $request->merge(['last_name'=> $lname]);

                }
                if($request->password){
                    $password = decryptText($request->password,$secret,$iv);
                    $request->merge(['password'=> $password]);
                }
                
            }catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        $request_fields = [
            'first_name' => 'required',
            //            'last_name' => 'required',
            'email' => [
                'required_if:driver_email_enable,1',
                Rule::unique('drivers', 'email')->where(function ($query) use ($merchant_id) {
                    return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id], ['email', '!=', NULL]]);
                })
            ],
            'phone' => [
                'required_if:driver_phone_enable,1',
                Rule::unique('drivers', 'phoneNumber')->where(function ($query) use ($merchant_id) {
                    return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id], ['phoneNumber', '!=', NULL]]);
                })
            ],
            'password' => 'required|min:8',
            
            //            'ats_id' => 'required',
        ];
        if ($request->requested_from != 'web') {
            $request_fields['player_id'] = 'required';
        }
        $validator = Validator::make($request->all(), $request_fields, [
            'phone.unique' => trans("$string_file.number_already_used"),
            'email.unique' => trans("$string_file.email_already_used"),
            'password.min' => trans("$string_file.password_min_length")
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {
            $create_fields = [
                'merchant_id' => $merchant_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phoneNumber' => $request->phone,
                'country_id' => $request->country_id,
                'password' => Hash::make($request->password),
                'signupStep' => 1,
                'player_id' => ($request->player_id) == 'null' ? NULL : $request->player_id,
                'ats_id' => $request->ats_id,
                'device' => $request->device,
                'business_name' => $request->business_name,
                'kin_details'=> $request->driver_kin_details,
                'timestampvalue' => $request->timestampvalue,
            ];
            $driver = Driver::create($create_fields);
            if($merchant_id == 976){
                \Log::channel('debugger')->emergency(['merchant_id'=> 976,'driver'=>$driver->id,'step'=> 1]);
            }
            //Send email
            event(new DriverSignupWelcome($driver->id));
            //            $temp = EmailTemplate::where('merchant_id', '=', $merchant_id)->where('template_name', '=', "welcome")->first();
            //            $merchant = Merchant::Find($merchant_id);
            //            $data['temp'] = $temp;
            //            $data['merchant'] = $merchant;
            //            $data['driver'] = $driver;
            //            $email_html = View::make('mail.driver-welcome')->with($data)->render();
            //            $configuration = EmailConfig::where('merchant_id', '=', $merchant_id)->first();
            //            $response = $this->sendMail($configuration, $driver->email, $email_html, 'welcome_email', $merchant->BusinessName, NULL, $merchant->email);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        $return_data[] = array('driver_id' => $driver->id);
        /*return message is not using at app side*/
        return $this->successResponse(trans("$string_file.success"), $return_data);
    }

    // driver Signup step 2
    public function RegStepTwo(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $merchant = Merchant::find($merchant_id);
        $string_file = $this->getStringFile($merchant_id);

        //Encrypt and Decrypt
        if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($request->postal_code){
                    $postal = decryptText($request->postal_code,$secret,$iv);
                    $request->merge(['postal_code'=> $postal]);
                }
                if($request->address_line_1){
                    $addLine1 = decryptText($request->address_line_1,$secret,$iv);
                    $request->merge(['address_line_1'=> $addLine1]);
                }
                if($request->address_line_2){
                    $addLine2 = decryptText($request->address_line_2,$secret,$iv);
                    $request->merge(['address_line_2'=> $addLine2]);
                }
                
                if($request->city_name){
                    $cityName = decryptText($request->city_name,$secret,$iv);
                    $request->merge(['city_name'=> $cityName]);
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        $request_fields = [
            'country_area_id' => 'required',
            'driver_id' => 'required',
            'profile_image' => 'required',
            'smoker_type' => 'required_if:smoker,1|between:1,2',
            // 'pay_mode' => 'required_if:driver_commission_choice,1|between:1,2',
            // 'network_code'=>'required_if:network_code_visibility,1',
            'referral_code' => 'required_if:referral_code_mandatory_driver_signup,1',
            'is_vat'=> 'nullable',
            'vat_number'=> $merchant->ApplicationConfiguration->driver_vat_configuration == 1 ? 'required' : 'nullable',
            'is_vat_liable'=> $merchant->ApplicationConfiguration->driver_vat_configuration == 1 ? 'required' : 'nullable',
        ];
        if ($request->requested_from != 'web') {
            $request_fields['driver_gender'] = 'required_if:gender,1|between:1,2';
        }

        //        if($merchant->Configuration->bank_details_enable == 1){
        //            $request_fields['bank_name'] = 'required';
        //            $request_fields['account_holder_name'] = 'required';
        //            $request_fields['account_number'] = 'required';
        //            $request_fields['online_code'] = 'required';
        //            $request_fields['account_type_id'] = 'required';
        //        }

        $validator = Validator::make($request->all(), $request_fields);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {
            
            $driver = Driver::find($request->driver_id);
            //            if ($request->referral_code) {
            //                $rewardPoint = new RewardPoint;
            //                $offer = $rewardPoint->getOfferDetails($request->referral_code, $request->merchant_id, $driver->country_id, 2);
            //                if ($offer == false) {
            //                    $string_file = $this->getStringFile($merchant_id);
            //                    return $this->failedResponse(trans("$string_file.invalid_referral") . '' . trans("$string_file.code"));
            //                }
            //            }
            if($request->multi_part == 1){
                $additional_req = ['compress' => true,'custom_key' => 'product'];
                $image = $this->uploadImage('profile_image', 'driver', $merchant_id,'single',$additional_req);
            }else{
                $image = $this->uploadBase64Image('profile_image', 'driver', $merchant_id);
            }
            
            $driver->country_area_id = $request->country_area_id;
            //            $driver->driver_address = $request->address_line;
            //            $driver->city = $request->city;
            //            $driver->postal_code = $request->postal_code;
            $driver->profile_image = $image;
            $driver->website_link = $request->website_link;
            $driver->signupStep = 2;
            $driver->is_vat= $request->is_vat; 
            $driver->vat_number= $request->vat_number;
            $driver->is_vat_liable = $request->is_vat_liable;
            if (!empty($request->term_status)) {
                $driver->term_status = $request->term_status;
            }
            if ($request->driver_address_enable == 1) {
                if(isset($request->driver_additional_data) && !empty($request->driver_additional_data)){
                    $driver_additional_data = $request->driver_additional_data;
                }else{
                    $driver_additional_data = json_encode(array("postal_code" => $request->postal_code, "address_line_1" => $request->driver_address, "city_name" => $request->city));
                }
                $driver->driver_additional_data = $driver_additional_data;
            }
            $driver->driver_referralcode = $driver->GenrateReferCode();
            if ($driver->Merchant->ApplicationConfiguration->driver_commission_choice == 0 && $driver->Merchant->Configuration->subscription_package == 1) {
                $driver->pay_mode = 1;
            } else {
                $driver->pay_mode = ($request->pay_mode == 0) ? 2 : $request->pay_mode;
            }
            $driver->network_code = $request->network_code;
            $driver->driver_gender = $request->driver_gender == 0 ? NULL : $request->driver_gender;
            if (!empty($request->bank_name) && !empty($request->account_holder_name)) {
                $driver->bank_name = $request->bank_name;
                $driver->account_holder_name = $request->account_holder_name;
                $driver->account_number = $request->account_number;
                $driver->online_code = $request->online_code;
                $driver->account_type_id = $request->account_type_id;
            }
            if($request->has('citizen_type')){
              $driver->citizen_type = $request->citizen_type;
            }
            $driver->save();
            if ($request->smoker == 1) {
                $smoker = DriverRideConfig::create([
                    'driver_id' => $driver->id,
                    'smoker_type' => $request->smoker_type,
                    'allow_other_smoker' => $request->allow_other_smoker,
                ]);
            }
            $ref = new ReferralController();
            $ref->giveReferral($request->referral_code, $driver, $driver->merchant_id, $driver->country_id, $driver->country_area_id, "DRIVER");
            $arr_params = array(
                "driver_id" => $driver->id,
                "check_referral_at" => "SIGNUP"
            );
            if($merchant_id == 976){
                \Log::channel('debugger')->emergency(['merchant_id'=> 976,'driver'=>$driver->id,'step'=> 2]);
            }
            $ref->checkReferral($arr_params);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        $return_data[] = array('driver_id' => $driver->id, 'signupStep' => $driver->signupStep);

        /*return message is not using at app side*/
        return $this->successResponse(trans("$string_file.success"), $return_data);
    }

    // login of driver
    public function Login(Request $request)
    {
        // player id will be mandatory after singup, because notification configuration (firebase/onesignal)
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);

        //Encrypt Decrypt
        $merchant = Merchant::find($merchant_id);
        if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($request->password){
                    $password = decryptText($request->password,$secret,$iv);
                    $request->merge(['password'=> $password]);
                }
                
                if($request->phone){
                    $phone = decryptText($request->phone,$secret,$iv);
                    $request->merge(['phone'=> $phone]);
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        $request_fields = [
            'password' => 'required',
            'phone' => 'required',
            //            'ats_id' => 'required',
            //            'player_id' => 'required_without:website|string|min:32'
        ];
        $validator = Validator::make(
            $request->all(),
            $request_fields,
            [
                // 'phone.exists' => trans("$string_file.phone_number_is_not_registered"),
                //                'player_id.required' => trans("$string_file.invalid_player_id"),
                //                'player_id.min' => trans("$string_file.invalid_player_id")
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $parameter = $request->driver_login_type == "EMAIL" ? "email" : "phoneNumber";
            // dd(Driver::where([[$parameter, '=', $request->phone]])->first(),$request->phone,$parameter);
            $driver = Driver::where([[$parameter, '=', $request->phone], ['merchant_id', '=', $merchant_id], ['driver_delete', '=', NULL]])->latest()->first();
            if (empty($driver)) {
                $msg = $request->driver_login_type == "EMAIL" ? trans("$string_file.email_is_not_registered") : trans("$string_file.phone_number_is_not_registered");
                //            $msg = $msg.' '.trans("$string_file.is") .' '.trans("$string_file.not_registered");
                //            $msg = $request->driver_login_type == "EMAIL" ? trans('api.email_not') : trans('api.phone_not');
                return $this->failedResponse($msg);
            }
            if ($driver->driver_admin_status == 2 || $driver->driver_delete == 1) {
                $msg = $driver->driver_delete == 1 ? trans("$string_file.account_has_been_deleted") : trans("$string_file.account_has_been_inactivated");
                return $this->failedResponse($msg);
            }

            $client = Client::where([['user_id', '=', $merchant_id], ['password_client', '=', 1]])->first();
            Config::set('auth.guards.api.provider', 'drivers');
            $request->request->add([
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => $request->phone,
                'password' => $request->password,
                'scope' => '',
            ]);
            $token_generation_after_login = Request::create(
                'oauth/token',
                'POST'
            );
            $collect_response = \Route::dispatch($token_generation_after_login)->getContent();
            $collectArray = json_decode($collect_response);
            if (isset($collectArray->error)) {
                return $this->failedResponse(trans("$string_file.failed_cred"));
            }
    //        $access_token_id = (new Parser())->parse($collectArray->access_token)->claims()->get('jti');
            $access_token_id = app(JwtParser::class)->parse($collectArray->access_token)->claims()->get('jti');
    //        Driver::Logout($request->player_id, $merchant_id); // logout driver from other devices
            $driverDetails = Driver::where([[$parameter, '=', $request->phone], ['merchant_id', '=', $merchant_id]])->latest()->first();
            if ($driverDetails->login_logout == 1 && $driverDetails->player_id != $request->player_id) {
                $access_tokenId = $driverDetails->access_token_id;
                \DB::table('oauth_access_tokens')->where('id', '=', $access_tokenId)->delete();
                \DB::table('oauth_refresh_tokens')->where('access_token_id', '=', $access_tokenId)->delete();

                $data = [];
                $title = trans("$string_file.session_expired");
                $message = trans("$string_file.session_expire_message");
                $data['notification_type'] = "LOGOUT";
                $data['segment_type'] = "";
                $data['segment_data'] = [];
                $arr_param = ['driver_id' => $driverDetails->id, 'merchant_id' => $merchant_id, 'message' => $message, 'title' => $title, 'data' => $data, 'large_icon' => ""];
                Onesignal::DriverPushMessage($arr_param);
                //$data['notification_gen_time'] = time();
                // Onesignal::DriverPushMessage($driverDetails->id, $data, $message, NULL, $merchant_id,NULL,$title);
            }

            // update unique number
            if (!empty($request->unique_number)) {
                $driverDetails->unique_number = $request->unique_number;
            }
            if (isset($request->device) && !empty($request->device)) {
                $driverDetails->device = $request->device;
            }
            if (isset($request->apk_version) && !empty($request->apk_version)) {
                $driverDetails->apk_version = $request->apk_version;
            }
            if (isset($request->model) && !empty($request->model)) {
                $driverDetails->model = $request->model;
            }
            if (isset($request->operating_system) && !empty($request->operating_system)) {
                $driverDetails->operating_system = $request->operating_system;
            }
            if (isset($request->package_name) && !empty($request->package_name)) {
                $driverDetails->package_name = $request->package_name;
            }
            $driverDetails->player_id = $request->player_id;
            $driverDetails->login_logout = 1; // update login status
            if ($driverDetails->segment_group_id == 2) {
                $driverDetails->online_offline = 1; // By default online
            }
            $driverDetails->access_token_id = $access_token_id; // update login status
            $driverDetails->ats_id = $request->ats_id; // update ats id of driver device
            $driverDetails->save(); // update driver
            $taxi_company = false;
            if ($driverDetails->taxi_company_id != NULL) {
                $taxi_company = true;
            }

            $push_notification = get_merchant_notification_provider($merchant_id, $driverDetails->id, 'driver');
            $return_data = array(
                'driver' => new DriverLoginResource($driverDetails),
                'access_token' => $collectArray->access_token,
                'push_notification' => $push_notification,
            );
            $message = trans("$string_file.success");
            DB::commit();
            return $this->successResponse($message, $return_data);
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function RegStepThree(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $request_fields = [
            'driver_id' => 'required',
            'segment_group_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {
            $segment_group = $request->segment_group_id;
            $driver = Driver::Find($request->driver_id);
            $driver->segment_group_id = $segment_group;
            $driver->signupStep = 3; // in case of handyman vehicle step will be skipped
            $driver->save();
            if($merchant_id == 976){
                
            \Log::channel('debugger')->emergency(['merchant_id'=> 976,'driver'=>$driver->id,'step'=> 3]);
            }
            
            $login_cred = [];
            if($driver->Merchant->ApplicationConfiguration->driver_bypass_relogin_after_signup == 1){
                $driver->login_logout = 1; // update login status
                $driver->save();
                $login_cred = $this->getLoginCreds($driver, $request);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        $return_data[] = array('driver_id' => $driver->id, 'signupStep' => $driver->signupStep);
        // if($merchant_id == 976){
        //     return $this->successResponse(trans("$string_file.success"), $return_data);
        // }
        return array(
            "version"=> "19.30",
            "result" => "1",
            "message" => "Success",
            "login_cred" => $login_cred,
            "data" => $return_data,
        );
    }
    
    
    
    public function getLoginCreds($driver, $request){
        try{
            $client = Client::where([['user_id', '=', $driver->merchant_id], ['password_client', '=', 1]])->first();
            Config::set('auth.guards.api.provider', 'driverOtp');
            $request->request->add(["publicKey" => $driver->Merchant->merchantPublicKey, "secretKey" => $driver->Merchant->merchantSecretKey]);
            $request->request->add([
                'merchant_id' => $driver->merchant_id,
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => "$driver->id",
                'password' => '',
                'scope' => '',
            ]);
            $token_generation_after_login = Request::create(
                'oauth/token',
                'POST'
            );
            $collect_response = \Route::dispatch($token_generation_after_login)->getContent();

            $collectArray = json_decode($collect_response);
            if (isset($collectArray->error)) {
                return new \Exception("Authentication error");
            }

            $push_notification = get_merchant_notification_provider($driver->merchant_id, $driver->id, 'driver');
            return array(
                'driver' => new DriverLoginResource($driver),
                'access_token' => $collectArray->access_token,
                'push_notification' => $push_notification,
            );
        }
        catch(\Exception $e){
            return new \Exception($e->getMessage());
        }
    }

    public function RegStepFive(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $request_fields = [
            'driver_id' => 'required',
        ];
        $driver = Driver::select('id', 'segment_group_id', 'merchant_id')->Find($request->driver_id);
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        if ($driver->segment_group_id == 2) {
            $request_fields['arr_segment'] = 'required';
        }
        $validator = Validator::make($request->all(), $request_fields);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {

            if ($driver->segment_group_id == 2) {
                $arr_segment_id = json_decode($request->arr_segment, true);
                $driver->Segment()->sync($arr_segment_id);
            }
            $driver->signupStep = 5; // registration completed, put driver in pending mode
            $driver->save();
            if($merchant_id == 976){
                
            \Log::channel('debugger')->emergency(['merchant_id'=> 976,'driver'=>$driver->id,'step'=> 5]);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        event(new WebPushNotificationEvent($driver->merchant_id, [], 3, null, null, $string_file));
        $return_data[] = array('driver_id' => $driver->id, 'signupStep' => $driver->signupStep);
        return $this->successResponse(trans("$string_file.document_upload_under_review_message"), $return_data);
    }


    public function getMainScreenConfig(Request $request)
    {
        $driver = $request->user('api-driver');
        $driver_id = $driver->id;
        $merchant_id = $driver->merchant_id;
        DB::beginTransaction();
        try {
            // get string file name
            $string_file = $this->getStringFile($merchant_id, $driver->Merchant);
            $manage_string = trans("$string_file.manage");

            $driver_segment_group = $driver->segment_group_id;
            $signup_step = $driver->signupStep;

            $step_name7 = "";
            $step_description7 = "";
            $step_status7 = 1;
            $button_display7 = false;

            //$arr_step_status = ["1"=>Need to add/upload/manage,"2"=>"added/uploded","3"=>"rejected","4"=>"Approval Pending","5"=>"Approved"];

            //step 1+2+3
            $step_status123 = ($signup_step == 9 ? 5 : 4);
            $registration = [
                'step_name' => trans("$string_file.registration_successful"),
                'step_description' => "", //trans("$string_file.registration_text"),
                'step_status' => $step_status123,
                'step_type' => 'MANAGE_REGISTRATION',
                'button_display' => false,
                'button_text' => "",
            ];

            // step 4
            if ($signup_step == 9) {
                $step_status4 = 5;
            } elseif ($signup_step >= 4) {
                $step_status4 = 4;
            } else {
                $step_status4 = 1;
            }
            //            $step_status4 = $signup_step == 9 ? 5 : ($signup_step >= 4 ? 4 : 1);

            if ($step_status4 == 5) {
                $button_display4 = false;
            } elseif ($signup_step == 3) {
                $button_display4 = true;
            } else {
                $button_display4 = false;
            }
            //            $button_display4 = ($step_status4 == 5 ? false : ($signup_step == 3 ? true : false));
            $manage_personal_document = [
                'step_name' => trans("$string_file.upload_personal_document"),
                'step_description' => trans("$string_file.personal_document_text"),
                'step_status' => $step_status4,
                'step_type' => 'MANAGE_PERSONAL_DOCUMENT',
                'button_display' => $button_display4,
                'button_text' => trans("$string_file.upload_document"),
            ];

            $manage_vehicle = []; //step 5
            $manage_vehicle_document = []; // step 6
            $manage_time_slot = []; // step 8

            if ($driver_segment_group == 1) {

                $vehicle_string = trans($string_file . ".vehicle");
                $document_string = trans("$string_file.document");
                $upload_string = trans("$string_file.upload");
                //step 5
                if ($signup_step == 9) {
                    $step_status5 = 5;
                } elseif ($signup_step >= 5) {
                    $step_status5 = 4;
                } else {
                    $step_status5 = 1;
                }
                //                $step_status5 = $signup_step == 9 ? 5 : ($signup_step >= 5 ? 4 : 1);
                if ($step_status5 == 5) {
                    $button_display5 = false;
                } elseif ($signup_step == 4) {
                    $button_display5 = true;
                } else {
                    $button_display5 = false;
                }
                //                $button_display5 = ($step_status5 == 5 ? false : $signup_step == 4 ? true : false);
                $manage_vehicle = [
                    'step_name' => trans("$string_file.add") . ' ' . $vehicle_string,
                    'step_description' => trans($string_file . ".add_vehicle_text"),
                    'step_status' => $step_status5,
                    'step_type' => 'MANAGE_VEHICLE',
                    'button_display' => $button_display5,
                    'button_text' => $manage_string . ' ' . $vehicle_string,
                ];

                // step 6
                if ($signup_step == 9) {
                    $step_status6 = 5;
                } elseif ($signup_step >= 6) {
                    $step_status6 = 4;
                } else {
                    $step_status6 = 1;
                }
                // dd($signup_step,$step_status6);
                //                $step_status6 = $signup_step == 9 ? 5 : ($signup_step >= 6 ? 4 : 1);
                if ($step_status6 == 5) {
                    $button_display6 = false;
                } elseif ($signup_step == 5) {
                    $button_display6 = true;
                } else {
                    $button_display6 = false;
                }
                //                $button_display6 = $step_status6 == 5 ? false : ($signup_step == 5 ? true : false);
                $manage_vehicle_document = [
                    // 'step_name' => $vehicle_string . ' ' . $document_string,
                    'step_name' => trans("$string_file.vehicle_document"),
                    'step_description' => trans($string_file . '.vehicle_document_text'),
                    'step_status' => $step_status6,
                    'step_type' => 'MANAGE_VEHICLE_DOCUMENT',
                    'button_display' => $button_display6,
                    'button_text' => trans("$string_file.upload_vehicle_document"),
                ];

                // step 7
                $step_name7 = trans($string_file . ".segment_services");
                $step_description7 = trans($string_file . ".segment_services_text");
                if ($signup_step == 9) {
                    $step_status7 = 5;
                } elseif ($signup_step >= 7) {
                    $step_status7 = 4;
                } else {
                    $step_status7 = 1;
                }
                //                $step_status7 = $signup_step == 9 ? 5 : ($signup_step >= 7 ? 4 : 1);
                //                $button_display7 = $step_status7 == 5 ? false : ($signup_step == 6 ? true : false);
                if ($step_status7 == 5) {
                    $button_display7 = false;
                } elseif ($signup_step == 6) {
                    $button_display7 = true;
                } else {
                    $button_display7 = false;
                }
            } elseif ($driver_segment_group == 2) {
                // step 7
                $step_name7 = trans($string_file . ".handyman_segment_services");
                $step_description7 = trans($string_file . ".handyman_segment_services_text");
                //                $step_status7 = $signup_step == 9 ? 5 : ($signup_step >= 7 ? 4 : 1);
                if ($signup_step == 9) {
                    $step_status7 = 5;
                } elseif ($signup_step >= 7) {
                    $step_status7 = 4;
                } else {
                    $step_status7 = 1;
                }
                //                $button_display7 = $step_status7 == 5 ? false : ($signup_step == 4 ? true : false);
                if ($step_status7 == 5) {
                    $button_display7 = false;
                } elseif ($signup_step == 4) {
                    $button_display7 = true;
                } else {
                    $button_display7 = false;
                }

                // step 8
                //                $step_status8 = $signup_step == 9 ? 5 : ($signup_step >= 8 ? 4 : 1);
                if ($signup_step == 9) {
                    $step_status8 = 5;
                } elseif ($signup_step >= 8) {
                    $step_status8 = 4;
                } else {
                    $step_status8 = 1;
                }
                //                $button_display8 = $step_status8 == 5 ? false : ($signup_step == 7 ? true : false);
                if ($step_status8 == 5) {
                    $button_display8 = false;
                } elseif ($signup_step == 7) {
                    $button_display8 = true;
                } else {
                    $button_display8 = false;
                }
                $manage_time_slot = [
                    'step_name' => trans("$string_file.add_your_availability"),
                    'step_description' => trans($string_file . ".time_slot_text"),
                    'step_status' => $step_status8,
                    'step_type' => 'MANAGE_AVAILABILITY_SLOT',
                    'button_display' => $button_display8,
                    'button_text' => trans("$string_file.add_time_slots"),
                ];
            }

            // step 7
            $manage_segment_services = [
                'step_name' => $step_name7,
                'step_description' => $step_description7,
                'step_status' => $step_status7,
                'step_type' => 'MANAGE_SEGMENT_SERVICES',
                'button_display' => $button_display7,
                'button_text' => $manage_string . ' ' . trans("$string_file.services"),
            ];

            $configuration_holders = [];
            if ($driver_segment_group == 1) {
                $configuration_holders = [$registration, $manage_personal_document, $manage_vehicle, $manage_vehicle_document, $manage_segment_services];
            } elseif ($driver_segment_group == 2) {
                $configuration_holders = [$registration, $manage_personal_document, $manage_segment_services, $manage_time_slot];
            }elseif($driver_segment_group == 4)
            {
                $configuration_holders = [$registration, $manage_personal_document];
            }


           // If required stripe connect details / Paystack Bank Details (We can use this step to get additional details on signup
            $has_pending_additional_detail = false;
            $required_additional_informations = get_merchant_required_additional_information_on_signup($driver->merchant_id, null, $driver->CountryArea);
            foreach($required_additional_informations as $required_additional_information){
                if ($required_additional_information["required"]) {
                $pending_additional_details = false;
                switch ($required_additional_information["requirement"]) {
                    case "STRIPE_CONNECT":
                        if ($driver->sc_account_status != "active") {
                            $pending_additional_details = true;
                        }
                        break;
                    case "PAYSTACK_SPLIT":
                        if ($driver->paystack_account_status != "active") {
                            $pending_additional_details = true;
                        }
                        break;
                    case "DRIVER_GUARANTOR_DETAILS":
                        if (empty($driver->driver_guarantor_details)) {
                            $pending_additional_details = true;
                        }
                        break;
                    case "DRIVER_SSN_NUMBER":
                        if (empty($driver->driver_ssn_number)) {
                            $pending_additional_details = true;
                        }
                        break;
                    default:
                        $pending_additional_details = false;
                }
                
                if ($pending_additional_details) {
                    $has_pending_additional_detail = true; // mark global flag
                }
        
                $step_status = $pending_additional_details ? 6 : 4;

                // default true if pending and step is 8
                $button_display8 = ($signup_step == 8 && $pending_additional_details) ? true : false;
        
                // Special case for stripe account that is created but not active
                if ($signup_step == 8 && $required_additional_information["requirement"] == "STRIPE_CONNECT" && !empty($driver->sc_account_id)) {
                    $button_display8 = false;
                }
        
                $button_text = trans("$string_file.check_status");
        
                $step_verification = $pending_additional_details
                    ? $required_additional_information['step_pending_message']
                    : $required_additional_information['step_verified_message'];
        
                $manage_required_additional_information = [
                    'step_name'        => $required_additional_information["step_name"],
                    'step_description' => $step_verification,
                    'step_status'      => $step_status,
                    'step_type'        => $required_additional_information["step_slug"] ?? 'MANAGE_ADDITIONAL_INFORMATION',
                    'button_display'   => $button_display8,
                    'button_text'      => $button_text,
                ];

                // once registered on wasl, will display on status of wasl
                array_push($configuration_holders, $manage_required_additional_information);

            } else {
                if ($signup_step == 8) {
                    $this->addRejectedDocumentKey($driver, $string_file, $configuration_holders);
                }
            }
            }
            if ($has_pending_additional_detail == false && $signup_step == 8) {
                $this->addRejectedDocumentKey($driver, $string_file, $configuration_holders);
            }
            // if ($required_additional_information["required"]) {
            //     $pending_additional_details = false;
            //     switch ($required_additional_information["requirement"]) {
            //         case "STRIPE_CONNECT":
            //             if ($driver->sc_account_status != "active") {
            //                 $pending_additional_details = true;
            //             }
            //             break;
            //         case "PAYSTACK_SPLIT":
            //             if ($driver->paystack_account_status != "active") {
            //                 $pending_additional_details = true;
            //             }
            //             break;
            //         case "DRIVER_GUARANTOR_DETAILS":
            //             if (empty($driver->driver_guarantor_details)) {
            //                 $pending_additional_details = true;
            //             }
            //             break;
            //         case "DRIVER_SSN_NUMBER":
            //             if (empty($driver->driver_ssn_number)) {
            //                 $pending_additional_details = true;
            //             }
            //             break;
            //         default:
            //             $pending_additional_details = false;
            //     }

            //     $button_display8 = false;
            //     $step_status = 6;
            //     if ($signup_step == 8) {
            //         // If it is stripe connect and driver registered at least single time, then display button will be false.
            //         if($required_additional_information["requirement"] == "STRIPE_CONNECT" && !empty($driver->sc_account_id)){
            //             $button_display8 = false;
            //         }
            //         else{
            //             $button_display8 = true;
            //         }
            //     }

            //     $button_text = $required_additional_information["step_name"];
            //     $step_verification = $required_additional_information["step_description"];
            //     if ($pending_additional_details) {
            //         $button_text = trans("$string_file.check_status");
            //         $step_verification = $required_additional_information['step_pending_message'];
            //     } else {
            //         $button_text = trans("$string_file.check_status");
            //         $step_verification = $required_additional_information['step_verified_message'];
            //         $button_display8 = false;

            //         $step_status = 4;
            //     }

            //     $manage_required_additional_information = [
            //         'step_name' => $required_additional_information["step_name"],
            //         'step_description' => $step_verification,
            //         'step_status' => $step_status,
            //         'step_type' => $required_additional_information["step_slug"] ?? 'MANAGE_ADDITIONAL_INFORMATION',
            //         'button_display' => $button_display8,
            //         'button_text' => $button_text,
            //     ];

            //     // once registered on wasl, will display on status of wasl
            //     array_push($configuration_holders, $manage_required_additional_information);

            //     if ($pending_additional_details == false && $signup_step == 8) {
            //         $this->addRejectedDocumentKey($driver, $string_file, $configuration_holders);
            //     }
            // } else {
            //     if ($signup_step == 8) {
            //         $this->addRejectedDocumentKey($driver, $string_file, $configuration_holders);
            //     }
            // }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();

        $driver_vehicle = DriverVehicle::where([['owner_id', '=', $driver->id],['vehicle_delete', '=', NULL]])->first();
        $return_data['signup_step'] = $signup_step;
        $return_data['online_enable'] = $signup_step == 9 ? true : false;
        $return_data['configuration'] = $configuration_holders;
        $return_data['driver_vehicle_id'] = !empty($driver_vehicle) ? "$driver_vehicle->id" : "";
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }
    
    public function addRejectedDocumentKey($driver, $string_file,&$configuration_holders){
        $reuploadDocEnable = $driver->Merchant->ApplicationConfiguration->reupload_document;
        $driver_id = $driver->id;
                    $personal_doc_rejected = false;
                    $vehicle_doc_rejected = false;
                    $segment_doc_rejected = false;
                        $filteredDriverDocuments = $driver->DriverDocument
                            ->filter(function ($document) {
                                return $document->document_verification_status == 3;
                            })
                            ->map(function ($document) {
                                return [
                                    'id' => $document->id,
                                    'document_id' => $document->document_id,
                                    
                                ];
                            });
                            
                            if(count($filteredDriverDocuments) > 0){
                                $personal_doc_rejected = true;
                            }
                            
                            
                            if($driver->segment_group_id == 1){
                                $vehicle_details = isset($driver->DriverVehicles[0]) ? $driver->DriverVehicles[0] : NULL;
                                if(isset($vehicle_details)){
                                     $filteredVehicleDocuments = [];
                                        $filteredVehicleDocuments = $vehicle_details->DriverVehicleDocument
                                            ->filter(function ($document) {
                                                return $document->document_verification_status == 3;
                                            })
                                            ->map(function ($document) use($driver){
                                                return [
                                                    'id' => $document->id,
                                                    'document_id' => $document->document_id,
                                                    'reject_reason'=> $driver->admin_msg
                                                ];
                                            })->toArray();
                                            
                                    if(count($filteredVehicleDocuments) > 0){
                                        $vehicle_doc_rejected = true;
                                    }
                                }
                            }else{
                                $arr_segment = Segment::select('id', 'name', 'slag')->whereHas('Driver', function ($q) use ($driver_id) {
                                    $q->where('driver_id', $driver_id);
                                })
                                ->with(['DriverSegmentDocument' => function ($qq) use ($driver_id) {
                                    $qq->where('driver_id', $driver_id);
                                }])
                                ->get();
                                $filteredSegmentDocuments = [];
                                foreach($arr_segment as $segment){
                                    $filteredSegmentDocuments = $segment->DriverSegmentDocument
                                        ->filter(function ($document) {
                                            return $document->document_verification_status == 3;
                                        })
                                        ->map(function ($document) use($driver){
                                            return [
                                                'id' => $document->id,
                                                'document_id' => $document->document_id,
                                                'reject_reason'=> $driver->admin_msg
                                            ];
                                        })->toArray();
                                }
                                
                                if(count($filteredSegmentDocuments) > 0){
                                    $segment_doc_rejected = true;
                                }
                            }
                    if($reuploadDocEnable == 1 && ( $personal_doc_rejected == true || $vehicle_doc_rejected == true || $segment_doc_rejected == true)){
                        $profile_under_review_holder = [
                            'step_name' => trans("$string_file.profile_rejected"),
                            'step_description' => trans("$string_file.profile_rejected_text") .' '.$driver->admin_msg,
                            'step_status' => 1,
                            'step_type' => 'PROFILE_REVIEW',
                            'button_display' => true,
                            'button_text' => trans("$string_file.reupload"),
                            'personal_doc_rejected'=> $personal_doc_rejected,
                            'vehicle_doc_rejected'=>$vehicle_doc_rejected,
                            'segment_doc_rejected'=> $segment_doc_rejected
                        ];
                    }else{
                        $profile_under_review_holder = [
                            'step_name' => trans("$string_file.profile_review"),
                            'step_description' => trans("$string_file.profile_review_text"),
                            'step_status' => ($driver->is_approved == 1)? 4 : 1,
                            'step_type' => 'PROFILE_REVIEW',
                            'button_display' => ($driver->is_approved == 2 && $driver->reject_driver == 2 ) ? true : false,
                            'button_text' => ($driver->is_approved == 2 && $driver->reject_driver == 2 ) ? $driver->admin_msg : '',
                            'personal_doc_rejected'=> $personal_doc_rejected,
                            'vehicle_doc_rejected'=>$vehicle_doc_rejected,
                            'segment_doc_rejected'=> $segment_doc_rejected
                        ];
                    }
                    
                    array_push($configuration_holders, $profile_under_review_holder);
                    if($driver->Merchant->Configuration->driver_training == 1 && ($driver->in_training == 1 || $driver->in_training == 3)){
                        $profile_under_review_holder['step_status'] = 4;
                        $training_holder = [
                            'step_name' => "Driver training pending",
                            'step_description' => "Pending driver training",
                            'step_status' => 1,
                            'step_type' => 'PROFILE_REVIEW',
                            'button_display' => false,
                            'button_text' => ''
                        ];
                        array_push($configuration_holders, $training_holder);
                    }

                    if($driver->Merchant->Configuration->driver_ssn_number_enable == 1 && empty($driver->driver_ssn_number)){
                        $ssn_holder = [
                            'step_name' => "Driver ssn pending",
                            'step_description' => "Pending driver ssn number",
                            'step_status' => 1,
                            'step_type' => 'PENDING_SSN_NUMBER',
                            'button_display' => false,
                            'button_text' => ''
                        ];
                        array_push($configuration_holders, $ssn_holder);
                    }
    }

    public function getSegmentServicesConfig(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $currency = $driver->Country->isoCode;
        $merchant_id = $driver->merchant_id;
        $request_fields = [
            'segment_id' => 'required|exists:segments,id',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {

            $driver_segment_group = $driver->segment_group_id;
            $country_area_id = $driver->country_area_id;
            $arr_services = [];
            $price_type_config = "";
            $segment_id = $request->segment_id;
            $price_card_owner = !empty($driver->Merchant->HandymanConfiguration) ? $driver->Merchant->HandymanConfiguration->price_card_owner_config : NULL;
            $price_type = !empty($driver->Merchant->HandymanConfiguration) && $driver->Merchant->HandymanConfiguration->price_type_config == "FIXED" ? 1 : 2;
            $request->request->add([
                'merchant_id' => $merchant_id, 'area' => $country_area_id,
                'driver_id' => $driver->id, 'segment_group_id' => $driver_segment_group
            ]);
            $segment = Segment::with(['SegmentPriceCard' => function ($q) use ($segment_id, $merchant_id, $driver, $price_card_owner, $country_area_id) {
                $q->addSelect('id', 'segment_id', 'amount', 'minimum_booking_amount', 'price_type', 'amount');
                $q->where('segment_id', $segment_id);
                $q->where('status', 1);
                $q->where('delete', NULL);
                $q->where('merchant_id', $merchant_id);
                if ($price_card_owner == 2) {
                    $q->where("driver_id", $driver->id);
                }
                $q->where('country_area_id', $country_area_id);
            }])->find($segment_id);
            //            $segment_merchant = $segment->Merchant[0]['pivot'];
            //            $price_card_owner = $segment_merchant->price_card_owner;
            //            $arr_price_type = [];
            $mandatory_doc_pending = false;
            $arr_segment_doc_list = [];
            if ($driver_segment_group == 1) { } elseif ($driver_segment_group == 2) {
                // p($driver);
                //                $arr_price_type = get_price_card_type('api',$string_file);
                $segment_documents = CountryArea::with(['SegmentDocument' => function ($q) use ($segment_id) {
                    $q->addSelect('documents.id', 'expire_date as expire_status', 'documentNeed as document_mandatory', 'document_number_required');
                    $q->where('documentStatus', 1);
                    $q->where('segment_id', $segment_id);
                }])
                    // ->whereHas('SegmentDocument',function($q){
                    //         $q->where('documentNeed',1);
                    //     })
                    //                    ->first();
                    ->find($driver->country_area_id);
                $segment_mandatory_document = $segment_documents->SegmentDocument->count();
                $driver_segment_doc = DriverSegmentDocument::where([['driver_id', '=', $driver->id], ['segment_id', '=', $segment_id]])
                    // ->whereHas('Document',function($q){
                    //     $q->where('documentNeed',1);
                    // })
                    ->get();
                // p($driver_segment_doc);
                $driver_document_uploaded_count = $driver_segment_doc->count();
                $request->merge(['price_card_owner' => $price_card_owner]);
                $mandatory_doc_pending = $driver_document_uploaded_count < $segment_mandatory_document;
                $arr_segment_doc_list = $this->getSegmentDocumentData($segment_documents->SegmentDocument, $driver_segment_doc, $segment_id, $merchant_id);
                $price_type_config = $driver->Merchant->HandymanConfiguration->price_type_config;
            }
            $price_card_id = NULL;
            //            $price_type = "";
            $minimum_booking_amount = "0";
            $hourly_amount = "0";
            if (isset($segment->SegmentPriceCard) && !empty($segment->SegmentPriceCard->id)) {
                $price_card_id = $segment->SegmentPriceCard->id;
                //                $price_type = $segment->SegmentPriceCard->price_type;
                $minimum_booking_amount = !empty($segment->SegmentPriceCard->minimum_booking_amount) ? $segment->SegmentPriceCard->minimum_booking_amount : 0;
                $hourly_amount = !empty($segment->SegmentPriceCard->amount) ? $segment->SegmentPriceCard->amount : 0;
            }

            $request->request->add(['segment_price_card_id' => $price_card_id]);
            $arr_services = $this->getSegmentServices($request, "driver");
            //            if (empty($arr_services) || (!empty($arr_services) && $arr_services->count() == 0)) {
            if (empty($arr_services) || (!empty($arr_services) && count($arr_services) == 0)) { // now services is
                // coming in array format
                $string_file = $this->getStringFile($merchant_id, $driver->Merchant);
                return $this->failedResponse(trans($string_file . '.services_price_card'));
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();

        $arr_type = get_price_card_type('', $price_type_config, $string_file);
        $return_data = [
            'segment_id' => $segment->id,
            'price_type' => $price_type,
            'price_type_text' => !empty($price_type) && isset($arr_type[$price_type])  ? $arr_type[$price_type] : "",
            'minimum_booking_amount' => $minimum_booking_amount,
            'currency' => $currency,
            'hourly_amount' => "$hourly_amount",
            'segment_group_id' => $driver_segment_group,
            'name' => !empty($segment->Name($merchant_id)) ? $segment->Name($merchant_id) : $segment->slag,
            'price_card_owner' => $price_card_owner,
            'price_type_config' => $price_type_config,
            'mandatory_doc_pending' => $mandatory_doc_pending,
            'segment_doc_list' => $arr_segment_doc_list,
            'arr_services' => $arr_services,
        ];
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    public function saveSegmentConfig(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $merchant_id = $driver->merchant_id;
        $driver_vehicle_id = $request->driver_vehicle_id;
        $request_fields = [
            'segment_id' => 'required|exists:segments,id',
            'arr_service' => 'required',
            //            'price_card_owner' => 'required',
            'price_type' => 'required_if:price_card_owner,2',
        ];
        $validator = Validator::make($request->all(), $request_fields);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $arr_service = json_decode($request->arr_service, true);
        $segment_id = $request->segment_id;

        DB::beginTransaction();
        try {
            // update driver signup step
            $driver->Segment()->wherePivot('segment_id', $segment_id)->detach();
            $driver->Segment()->attach($segment_id);
            $driver->ServiceType()->wherePivot('segment_id', $segment_id)->detach();
            foreach ($arr_service as $service) {
                $driver->ServiceType()->attach($service['service_type_id'], ['segment_id' => $segment_id]);
            }

            $driver_segment_group = $driver->segment_group_id;
            if ($driver_segment_group == 1) {
                // step 7 and 8 are by passed
                if ($driver->signupStep == 6) {
                    $auto_verify = get_driver_auto_verify_status($driver->id);
                    $signup_status = $auto_verify == 1 ? 9 : 8;
                    if($driver->Merchant->Configuration->driver_training == 1){
                        $signup_status = 8;
                        $driver->in_training = 1;
                    }
                    // if($driver->Merchant->Configuration->driver_guarantor_details == 1 && $driver->CountryArea->need_driver_guarantor_details == 1){
                    //     $signup_status = 8;
                    // }
                    $driver->signupStep = $signup_status;
                    $driver->save();

                    if ($auto_verify) {
                        $ref = new ReferralController();
                        $arr_params = array(
                            "driver_id" => $driver->id,
                            "check_referral_at" => "COMPLETE-SIGNUP"
                        );
                        $ref->checkReferral($arr_params);
                    }
                }
                $vehicle = DriverVehicle::Find($driver_vehicle_id);
                $vehicle->ServiceTypes()->wherePivot('segment_id', $segment_id)->detach();

                foreach ($arr_service as $service) {
                    $vehicle->ServiceTypes()->attach($service['service_type_id'], ['segment_id' => $segment_id]);
                }
            } elseif ($driver_segment_group == 2) {
                if ($driver->signupStep == 4) {
                    $driver->signupStep = 7;
                    $driver->save();
                }
                // if price card owner is driver/service provider then save price card
                $segment = Segment::select('id')->with(['Merchant' => function ($q) use ($segment_id, $merchant_id) {
                    $q->addSelect('id');
                    $q->where('segment_id', $segment_id);
                    $q->where('merchant_id', $merchant_id);
                }])->find($segment_id);
                //                if (isset($segment->Merchant[0]->pivot->price_card_owner) && $segment->Merchant[0]->pivot->price_card_owner == 2) {
                if ($request->price_card_owner == 2) {
                    // make ohter price type delete
                    $old_price_type = $request->price_type == 1 ? 2 : 1;
                    $old_price_card = SegmentPriceCard::where([['driver_id', '=', $driver->id], ['segment_id', '=', $request->segment_id], ['price_type', '=', $old_price_type]])->first();
                    if (!empty($old_price_card->id)) {
                        $old_price_card->status = 1;
                        $old_price_card->delete = 1;
                        $old_price_card->save();
                    }
                    $price_card = SegmentPriceCard::updateOrCreate(
                        [
                            'driver_id' => $driver->id, 'segment_id' => $request->segment_id, 'price_type' => $request->price_type
                        ],
                        [
                            'driver_id' => $driver->id,
                            'amount' => $request->hourly_amount,
                            'country_area_id' => $driver->country_area_id,
                            'segment_id' => $segment_id,
                            'merchant_id' => $driver->merchant_id,
                            'price_type' => isset($request->price_type) ? $request->price_type : 1,
                            'status' => 1,
                            'delete' => NULL,
                            'minimum_booking_amount' => $request->minimum_booking_amount,
                        ]
                    );
                    // fixed type prices
                    if ($request->price_type == 1 || $request->price_type == "FIXED") {
                        foreach ($arr_service as $service) {
                            $price_card_details = SegmentPriceCardDetail::updateOrCreate(
                                [
                                    'segment_price_card_id' => $price_card->id, 'service_type_id' => $service['service_type_id']
                                ],
                                [
                                    'service_type_id' => $service['service_type_id'],
                                    'amount' => $service['price'],
                                    'segment_price_card_id' => $price_card->id,
                                ]
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        if ($driver->Merchant->Configuration->admin_alert_on_driver_reg == 1) {
            $player_id = array_pluck($driver->Merchant->ActiveWebOneSignals->where('status', 1), 'player_id');
            $title = trans("$string_file.new_driver_registered");
            $message = $title;
            $onesignal_redirect_url = route('merchant.driver.pending.show');
            Onesignal::MerchantWebPushMessage($player_id, [], $message, $title, $driver->merchant_id, $onesignal_redirect_url);
        }
        return $this->successResponse(trans("$string_file.success"), []);
    }

    public function getSegmentList(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $driver_id = $driver->id;
        $merchant_id = $driver->merchant_id;
        $driver_segment_group = $driver->segment_group_id;
        $existing_vehicle_enable = $driver->Merchant->Configuration->existing_vehicle_enable;
        $driver_vehicle_id = $request->driver_vehicle_id;

        if(empty($driver_vehicle_id)){
            $driver_vehicle = DriverVehicle::where([['owner_id', '=', $driver->id]])->first();
            $driver_vehicle_id = !empty($driver_vehicle) ? $driver_vehicle->id : NULL;
        }

        $calling_for = '';
        if ($driver_segment_group == 2) {
            $request_fields['calling_for'] = 'required|in:TIME_SLOT,SEGMENT';
            $calling_for = $request->calling_for;
            $validator = Validator::make($request->all(), $request_fields);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }
        }

        try {
            $vehicle = null;
            $return_data['arr_segment'] = [];
            $return_data['vehicle'] = [];
            $configured_segment_id = [];
            if ($driver_segment_group == 1) {
                //                if(empty($vehicle->vehicle_type_id))
                //                {
                //                    //,"$string_file.complete_registration"
                //                    return $this->failedResponse("Your registration is pending, please complete that before proceeding further");
                //                }
                $vehicle = DriverVehicle::select('id', 'vehicle_type_id', 'vehicle_model_id', 'vehicle_color', 'vehicle_number', 'vehicle_color')
                    ->where(function ($q) use ($driver_vehicle_id, $driver_id, $existing_vehicle_enable) {
                        if (!empty($driver_vehicle_id)) {
                            $q->where('id', $driver_vehicle_id);
                        }
                        if ($existing_vehicle_enable != 1){
                            $q->where('driver_id', $driver_id);
                        }
                        $q->orderBy('created');
                    })
                    ->first();
                $area = $driver->CountryArea;
                $vehicle_type = $area->VehicleType->where('id', $vehicle->vehicle_type_id);
                $arr_segment_id = $vehicle_type->map(function ($item) {
                    return $item->pivot->segment_id;
                });

                $arr_segment_id = array_unique(json_decode($arr_segment_id, true));
                $arr_segment = $area->Segment->whereIn('id', $arr_segment_id)->whereNotIn("slag", "CARPOOLING");
                // reindex the keys
                $arr_segment = collect($arr_segment->values());
                $configured_segment = $vehicle->ServiceTypes;
                if ($configured_segment->count() > 0) {
                    $configured_segment_id = $configured_segment->map(function ($item) {
                        return $item->pivot->segment_id;
                    });
                    $configured_segment_id = array_unique(json_decode($configured_segment_id, true));
                }

                $return_data['vehicle'] = [[
                    'driver_vehicle_id' => $vehicle->id,
                    'vehicle_number' => !empty($vehicle->vehicle_number)? $vehicle->vehicle_number: "",
                    'vehicle_color' => !empty($vehicle->vehicle_color)? $vehicle->vehicle_color : "",
                    'vehicle_model' => !empty($vehicle->VehicleModel) ? $vehicle->VehicleModel->VehicleModelName : "",
                    'vehicle_type' => $vehicle->VehicleType->VehicleTypeName,
                    'image' => !empty($vehicle->VehicleType) ? get_image($vehicle->VehicleType->vehicleTypeImage, 'vehicle', $merchant_id, true, false) : "",
                ]];
            } else {

                if ($calling_for == 'TIME_SLOT') {
                    $arr_segment = $driver->Segment;
                    $configured_segment = $driver->ServiceTimeSlotDetail;
                    $configured_segment_id = $configured_segment->map(function ($item) {
                        return $item->pivot->segment_id;
                    });
                    $configured_segment_id = array_unique(json_decode($configured_segment_id, true));
                } else {
                    $area = $driver->CountryArea;
                    $arr_segment = $area->Segment->where('segment_group_id', $driver_segment_group);
                    $arr_segment = collect($arr_segment->values());
                    $configured_segment = $driver->Segment;
                    $configured_segment_id = $configured_segment->map(function ($item) {
                        return $item->pivot->segment_id;
                    });
                    $configured_segment_id = array_unique(json_decode($configured_segment_id, true));
                }
            }
            if ($arr_segment->count() > 0) {
                $price_card_owner_config = !empty($driver->Merchant->HandymanConfiguration) ? $driver->Merchant->HandymanConfiguration->price_card_owner_config : NULL;
                $query = Segment::with(['Merchant' => function ($q) use ($merchant_id) {
                    $q->where('id', $merchant_id);
                    $q->select('id');
                }])
                    ->whereHas('Merchant', function ($q) use ($merchant_id) {
                        $q->where('merchant_id', $merchant_id);
                    })
                    ->whereIn('id', array_pluck($arr_segment, 'id'));
                //in case of handyman driver
                if ($price_card_owner_config == 1 && $driver_segment_group == 2) {
                    $query->with(['SegmentPriceCard' => function ($q) use ($merchant_id) {
                        $q->where('merchant_id', $merchant_id);
                        $q->select('id');
                    }])
                        ->whereHas('SegmentPriceCard', function ($q) use ($merchant_id) {
                            $q->where('merchant_id', $merchant_id);
                        });
                }
                $arr_segment = $query->get();

                $return_data['arr_segment'] = $arr_segment->map(function ($item, $key) use ($merchant_id, $configured_segment_id) {
                    return [
                        'id' => $item->id,
                        'segment_name' => !empty($item->Name($merchant_id)) ? $item->Name($merchant_id) : $item->slag,
                        'icon' => isset($item->Merchant[0]['pivot']->segment_icon) && !empty($item->Merchant[0]['pivot']->segment_icon) ? get_image($item->Merchant[0]['pivot']->segment_icon, 'segment', $merchant_id, true, false) : get_image($item->icon, 'segment_super_admin', NULL, false, false),
                        'selected' => in_array($item->id, $configured_segment_id) ? true : false,
                        'segment_slug' => $item->slag,
                    ];
                });
            } else {
                return $this->failedResponse(trans("$string_file.no_segment"));
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }

        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    public function saveServiceTimeSlot(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $merchant_id = $driver->merchant_id;
        $request_fields = [
            'segment_id' => 'required|exists:segments,id',
            'arr_slot_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $arr_slot_details_id = json_decode($request->arr_slot_id, true);
        $segment_id = $request->segment_id;

        DB::beginTransaction();
        try {
            $driver_segment_group = $driver->segment_group_id;
            if ($driver_segment_group == 2) {
                // update driver signup step
                if ($driver->signupStep == 7) {
                    $auto_verify = get_driver_auto_verify_status($driver->id);
                    $signup_status = $auto_verify == 1 ? 9 : 8;
                    $driver->signupStep = $signup_status;
                    $driver->save();

                    if ($auto_verify) {
                        $ref = new ReferralController();
                        $arr_params = array(
                            "driver_id" => $driver->id,
                            "check_referral_at" => "COMPLETE-SIGNUP"
                        );
                        $ref->checkReferral($arr_params);
                    }
                }
                $driver->ServiceTimeSlotDetail()->wherePivot('segment_id', $segment_id)->detach();
                foreach ($arr_slot_details_id as $slot_details_id) {
                    $driver->ServiceTimeSlotDetail()->attach($slot_details_id['id'], ['segment_id' => $segment_id]);
                }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"), []);
    }

    public function getOnlineConfig(Request $request)
    {
        $driver = $request->user('api-driver');
        $pay_mode = $driver->pay_mode;
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        try {
            $driver_segment_group = $driver->segment_group_id;
            $driver_id = $driver->id;
            $merchant_id = $driver->merchant_id;
            $return_data = [];
            $online_configuration = $this->getDriverOnlineConfig($driver, 'all');
            if ($driver_segment_group == 1) {
                if($driver->Merchant->Configuration->add_multiple_vehicle == 1){
                    $arr_vehicle = DriverVehicle::select('id', 'vehicle_number', 'vehicle_color', 'vehicle_model_id', 'vehicle_type_id', 'vehicle_make_id', 'vehicle_verification_status')
                    ->with(['ServiceTypes' => function ($q) use ($driver_id) {
                        $q->addSelect('id');
                    }])
                    ->whereHas('Drivers', function ($qq) use ($driver_id) {
                        $qq->where('driver_id', $driver_id);
                    })
                    ->whereIn('vehicle_verification_status', [1,2])
                    ->get();
                }else{
                    $arr_vehicle = DriverVehicle::select('id', 'vehicle_number', 'vehicle_color', 'vehicle_model_id', 'vehicle_type_id', 'vehicle_make_id', 'vehicle_verification_status')
                    ->with(['ServiceTypes' => function ($q) use ($driver_id) {
                        $q->addSelect('id');
                    }])
                    ->whereHas('Drivers', function ($qq) use ($driver_id) {
                        $qq->where('driver_id', $driver_id);
                    })
                    ->where([['vehicle_verification_status', '=', 2]])
                    ->get();
                }

                $arr_active_booking_order = ['driver_vehicle_id' => [], 'service_type_id' => []];

                $current_and_upcoming_booking = Booking::select('id', 'service_type_id', 'driver_vehicle_id')->where([['driver_id', '=', $driver_id], ['booking_closure', '=', NULL]])
                    ->whereIn('booking_status', [1002, 1012, 1003, 1004, 1005])
                    ->get();
                $current_order = Order::select('id', 'service_type_id', 'driver_vehicle_id')->where([['driver_id', '=', $driver_id], ['is_order_completed', '=', 2]])
                    ->whereIn('order_status', [6, 7, 9, 10, 11])
                    ->get();
                $arr_order = $current_order->toArray();
                $arr_booking = $current_and_upcoming_booking->toArray();
                if (!empty($arr_order) || !empty($arr_booking)) {
                    // p($arr_order,0);
                    // p($arr_booking,0);
                    $active_data = array_merge($arr_order, $arr_booking);
                    // p($active_data,0);
                    $booking_order_vehicle = array_unique(array_column($active_data, 'driver_vehicle_id'));
                    $booking_order_service = array_unique(array_column($active_data, 'service_type_id'));
                    $arr_active_booking_order = ['driver_vehicle_id' => $booking_order_vehicle, 'service_type_id' => $booking_order_service];
                    // p($arr_active_booking_order,0);
                }
                $return_data = $arr_vehicle->map(function ($item) use ($driver_id, $merchant_id, $online_configuration, $arr_active_booking_order, $pay_mode,$driver) {
                    $arr_segment_pivot = $item->ServiceTypes->map(function ($inner_item) {
                        return $inner_item->pivot->toArray();
                    });
                    $arr_segment_pivot = $arr_segment_pivot->toArray();
                    $arr_service_type_id = array_column($arr_segment_pivot, 'service_type_id');
                    $area_docs_count = $driver->CountryArea->VehicleDocuments()->wherePivot('vehicle_type_id', '=', $item->vehicle_type_id)->count();
                    $uploaded_docs_count = $item->DriverVehicleDocument->whereIn('document_verification_status', [2])->count();
                    $configured_segment = $item->ServiceTypes;
                    if ($configured_segment->count() > 0) {
                    $configured_segment_id = $configured_segment->map(function ($segment_item) {
                    return $segment_item->pivot->segment_id;
                    });
                    $configured_segment_id = array_unique(json_decode($configured_segment_id, true));
                    }
                    $segment_services = $this->segmentServices($driver_id, $merchant_id, $arr_service_type_id, $online_configuration, $arr_active_booking_order, $pay_mode,$driver);

                    return [
                        'driver_vehicle_id' => $item->id,
                        'vehicle_number' => !empty($item->vehicle_number)? $item->vehicle_number : "",
                        'vehicle_color' => !empty($item->vehicle_color)? $item->vehicle_color : "",
                        'vehicle_model' => !empty($item->VehicleModel->VehicleModelName)? $item->VehicleModel->VehicleModelName : "",
                        'vehicle_type' => !empty($item->VehicleType)? $item->VehicleType->VehicleTypeName : "",
                        'vehicle_make'=>!empty($item->VehicleMake) ? $item->VehicleMake->VehicleMakeName : "",
                        'image' => !empty($item->VehicleType) ? get_image($item->VehicleType->vehicleTypeImage, 'vehicle', $merchant_id, true, false): "",
                        'selected' => !empty($online_configuration['driver_vehicle_id']) && in_array($item->id, $online_configuration['driver_vehicle_id']) ? true : false,
                        'uncheck_enable' => !empty($arr_active_booking_order['driver_vehicle_id']) && in_array($item->id, $arr_active_booking_order['driver_vehicle_id']) ? false : true,
                        'doc_uploaded' => $uploaded_docs_count >= $area_docs_count ? true : false,
                        'segment_config_set' => !empty($configured_segment_id) ? true : false,
                        'arr_segment' => $segment_services,
                    ];
                });
            } elseif ($driver_segment_group == 2) {
                // made it equal to vehicle mode for app symmetry
                $current_order = HandymanOrderDetail::select('service_type_id')
                    ->whereHas('HandymanOrder', function ($q) use ($driver_id) {
                        $q->where([['driver_id', '=', $driver_id], ['is_order_completed', '=', 2]])
                            ->whereIn('order_status', [4, 6]);
                    })->get();
                $active_data = $current_order->toArray();
                $booking_order_service = array_unique(array_column($active_data, 'service_type_id'));
                $arr_active_booking_order = ['service_type_id' => $booking_order_service];
                $return_data[] = [
                    'driver_vehicle_id' => 0,
                    'vehicle_number' => "",
                    'vehicle_color' => "",
                    'vehicle_model' => "",
                    'vehicle_type' => "",
                    'vehicle_make'=>"",
                    'image' => "",
                    'selected' => "",
                    'uncheck_enable' => "",
                    'arr_segment' => $this->segmentServices($driver_id, $merchant_id, [], $online_configuration, $arr_active_booking_order, $pay_mode, $driver),
                ];
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        $return['online_config_status'] = $online_configuration['status'];
        $return['segment_group_id'] = $driver_segment_group;
        $return['online_config'] = $return_data;
        return $this->successResponse(trans("$string_file.data_found"), $return);
    }

    public function segmentServices($driver_id, $merchant_id, $arr_service_type_id = [], $online_configuration = [], $arr_active_booking_order = [], $pay_mode = NULL, $driver = NULL)
    {
        $arr_segment = Segment::select('id', 'slag', 'name', 'icon')
            ->with(['ServiceType' => function ($q) use ($driver_id) {
                $q->addSelect('id', 'segment_id', 'serviceName');
                if (!empty($arr_service_type_id)) {
                    $q->whereIn('id', $arr_service_type_id);
                }
                $q->whereHas('Driver', function ($qq) use ($driver_id) {
                    $qq->where('driver_id', $driver_id);
                });
            }])
            // code will be used to get subsciption package of segment for current driver
            ->with(['SubscriptionPackage' => function ($q) use ($merchant_id, $driver_id) {
                $q->where('merchant_id', $merchant_id);
                $q->whereHas('DriverSubscriptionRecord', function ($qq) use ($driver_id) {
                    $qq->where('driver_id', $driver_id)->where('status', 2)
                        ->whereColumn('used_trips', '<', 'package_total_trips');
                });
            }])
            ->whereHas('ServiceType', function ($q) use ($driver_id, $arr_service_type_id) {
                if (!empty($arr_service_type_id)) {
                    $q->whereIn('id', $arr_service_type_id);
                }
                $q->whereHas('Driver', function ($qq) use ($driver_id) {
                    $qq->where('driver_id', $driver_id);
                });
            })
            ->with(['Merchant' => function ($q) use ($merchant_id) {
                $q->where('id', $merchant_id);
                $q->select('id');
            }])
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->where('merchant_id', $merchant_id);
            })
            ->get();

        $return_segment = $arr_segment->map(function ($item) use ($merchant_id, $online_configuration, $arr_active_booking_order, $pay_mode, $driver) {

            $subscription = "";
            
            if ($pay_mode == 1) {
                $subscription = "SUBSCRIPTION_PENDING";
                if($driver->Merchant->Configuration->subscription_package_type == 1){
                    if ($item->SubscriptionPackage->count() > 0) {
                        $subscription = "SUBSCRIPTION_OKAY";
                    }
                }
                else{
                    if ($driver->hasActiveRenewableSubscriptionRecord()) {
                        $subscription = "SUBSCRIPTION_OKAY";
                    }
                }
            }

            $services = $item->ServiceType->map(function ($item_inner) use ($merchant_id, $online_configuration, $arr_active_booking_order) {
                return array(
                    'id' => $item_inner->id,
                    'serviceName' => $item_inner->ServiceName($merchant_id),
                    'uncheck_enable' => !empty($arr_active_booking_order['service_type_id']) && in_array($item_inner->id, $arr_active_booking_order['service_type_id']) ? false : true,
                    'selected' => !empty($online_configuration['service_type_id']) && in_array($item_inner->id, $online_configuration['service_type_id']) ? true : false,
                );
            });

            return [
                'segment_id' => $item->id,
                'selected' => !empty($online_configuration['segment_id']) && in_array($item->id, $online_configuration['segment_id']) ? true : false,
                'name' => !empty($item->Name($merchant_id)) ? $item->Name($merchant_id) : $item->slag,
                'icon' => isset($item->Merchant[0]['pivot']->segment_icon) && !empty($item->Merchant[0]['pivot']->segment_icon) ? get_image($item->Merchant[0]['pivot']->segment_icon, 'segment', $merchant_id, true, false) : get_image($item->icon, 'segment_super_admin', NULL, false, false),
                'arr_service' => $services,
                'subscription' => $subscription
            ];
        });
        return $return_segment;
    }

    // save online config
    public function saveOnlineConfig(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $existing_enable = has_driver_multiple_or_existing_vehicle($driver->id, $driver->merchant_id);
        $merchant_id = $driver->merchant_id;
        $driver_segment_group = $driver->segment_group_id;
        $subscription_package_type = $driver->Merchant->Configuration->subscription_package_type; //1->subscription 2->renewable subscription
        $driver_vehicle_id = null;
        $request_fields = [
            'arr_segment_services' => 'required',
        ];
        if ($driver_segment_group == 1) {
            $request_fields['driver_vehicle_id'] = 'required|exists:driver_vehicles,id';
            $driver_vehicle_id = $request->driver_vehicle_id;
        }
        $validator = Validator::make($request->all(), $request_fields);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        if($subscription_package_type == 1){
            if ($driver->pay_mode == 1 && $driver->DriverActiveSubscriptionRecord && $driver->DriverActiveSubscriptionRecord->count() == 0) {
                return $this->failedResponse(trans("$string_file.subscription_error"));
            }
        }
        else{
            if ($driver->pay_mode == 1 && !$driver->hasActiveRenewableSubscriptionRecord()) {
                return $this->failedResponse(trans("$string_file.subscription_error"));
            }
        }
        // dd($driver);
        if ($driver->free_busy == 1) {
            return $this->failedResponse(trans("$string_file.running_job_config_error"));
        }
        $driver_id = $driver->id;
        $driver_segment_group_id = $driver->segment_group_id;
        $arr_segment_services = json_decode($request->arr_segment_services, true);
        DB::beginTransaction();
        try {
            if($subscription_package_type == 1){
                if ($driver->pay_mode == 1) {
                    $arr_packages_segment = $driver->DriverActiveSubscriptionRecord->pluck('segment_id')->toArray();
                    $arr_online_config_segment = array_pluck($arr_segment_services, 'segment_id');
    
                    if (count(array_diff($arr_online_config_segment, $arr_packages_segment)) > 0) {
                        return $this->failedResponse(trans("$string_file.some_subscription_package_not_active"));
                    }
                }
            }
            // update driver online work configuration
            $driver->ServiceTypeOnline()->wherePivot('driver_id', $driver_id)->detach();
            $pool_service_exist = false;
            foreach ($arr_segment_services as $segment) {
                foreach ($segment['arr_service'] as $service) {
                    if ($service['service_type_id'] == 5) {
                        $pool_service_exist = true;
                    }
                    $driver->ServiceTypeOnline()->attach($service['service_type_id'], ['segment_id' => $segment['segment_id'], 'driver_id' => $driver_id, 'driver_vehicle_id' => $driver_vehicle_id]);
                }
            }
            if ($driver_segment_group_id == 1 && !empty($driver_vehicle_id)) {

                // check for pool ride
                // Check if vehicle enabled for pool
                $driver_vehicle = DriverVehicle::select('id', 'vehicle_type_id', 'vehicle_model_id')->where('id', $driver_vehicle_id)->first();
                if ($driver_vehicle->VehicleType->pool_enable) {
                    $pool_ride_active = NULL;
                    $avail_seats = NULL;
                    $status_for_pool = NULL;
                    if ($pool_service_exist) {
                        $pool_ride_active = 1;
                        $avail_seats = !empty($driver_vehicle->VehicleModel)? $driver_vehicle->VehicleModel->vehicle_seat : NULL;
                        $status_for_pool = 1;
                    }
                    $driver->pool_ride_active = $pool_ride_active;
                    $driver->avail_seats = $avail_seats;
                    $driver->occupied_seats = 0;
                    $driver->status_for_pool = $status_for_pool;
                    $driver->pick_exceed = NULL;
                    $driver->pool_user_id = NULL;
                    $driver->save();
                }


                // $driver->DriverVehicle->where('driver_id',$driver_id)->update(['vehicle_active_status'=>2]);
                // $driver->DriverVehicle->where('driver_id',$driver_id)->where('driver_vehicle_id',$driver_vehicle_id)->update(['vehicle_active_status'=>1]);
                //                $vehicle =  DriverVehicle::select('driver_id','vehicle_number')->find($driver_vehicle_id);
                //                if($vehicle->driver_id != $driver_id) // existing vehicle case
                //                {
                //                    // check for existing vehicle
                //                  $vehicle_already_live_by_owner =   DB::table('driver_driver_vehicle')
                //                        ->where([['driver_id', "=", $vehicle->driver_id],['driver_vehicle_id', "=", $driver_vehicle_id],['vehicle_active_status' ,'=', 1]])->count();
                //                  if($vehicle_already_live_by_owner > 0)
                //                  {
                //                      $string_file = $this->getStringFile($merchant_id);
                //                      $message = trans_choice("$string_file.vehicle_lived_by_owner_warning", 3, ['VEHICLE' => $vehicle->vehicle_number, 'PHONE' => $vehicle->Driver->phoneNumber]);
                //                      return $this->failedResponse($message);
                //                  }
                //
                //                }
                //first inactive all vehicles
                DB::table('driver_driver_vehicle')->where([['driver_id', "=", $driver_id]])->update(['vehicle_active_status' => 2]);
                // then active selected vehicle
                if (!empty($driver->ServiceTypeOnline()) && $driver->ServiceTypeOnline()->count() > 0 && $existing_enable == false) {
                    DB::table('driver_driver_vehicle')->where([['driver_id', "=", $driver_id], ['driver_vehicle_id', "=", $driver_vehicle_id]])->update(['vehicle_active_status' => 1]);
                }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        $online_config = $this->getDriverOnlineConfig($driver, 'online_details');
        $return_data['work_set'] = $online_config['detail'];
        $return_data['socket_data'] = $online_config['socket_data'];
        return $this->successResponse(trans("$string_file.success"), $return_data);
    }


    // get driver signup/enrolled segment list
    public function getEnrolledSegments(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $merchant_id = $driver->merchant_id;
        $return_data = [];
        try {
            //$arr_segment = $driver->Segment;
            $arr_segment = Segment::with(['Merchant' => function ($a) use ($merchant_id) {
                $a->where('id', $merchant_id);
            }])
                ->whereHas('Merchant', function ($q) use ($merchant_id) {
                    $q->where('id', $merchant_id);
                })->whereHas('Driver', function ($qq) use ($driver) {
                    $qq->where('driver_id', $driver->id);
                })
                ->orderby('id')
                ->get();
            if ($arr_segment->count() > 0) {
                $return_data = $arr_segment->map(function ($item, $key) use ($merchant_id) {
                    return [
                        'id' => $item->id,
                        'segment_slug' => $item->slag,
                        'segment_name' => !empty($item->Name($merchant_id)) ? $item->Name($merchant_id) : $item->slag,
                        'icon' => isset($item->Merchant[0]['pivot']->segment_icon) && !empty($item->Merchant[0]['pivot']->segment_icon) ? get_image($item->Merchant[0]['pivot']->segment_icon, 'segment', $merchant_id, true, false) : get_image($item->icon, 'segment_super_admin', NULL, false, true),
                    ];
                });
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        if(count($return_data) > 0){
            return $this->successResponse(trans("$string_file.data_found"), $return_data);
        }
        else{
            return $this->failedResponse(trans("$string_file.data_not_found"), $return_data);
        }
    }

    public function demoLogin(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $request_fields = [
            'unique_number' => 'required|string',
            'player_id' => 'required|string|min:32',
        ];
        $validator = Validator::make($request->all(), $request_fields);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {
            $demo = DemoConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
            if (empty($demo)) {
                $message = trans("$string_file.demo_configuration_not_found");
                return $this->failedResponse($message);
            }
            Driver::where([['unique_number', '=', $request->unique_number], ['merchant_id', '=', $merchant_id]])->delete();
            $segment_group_id = NULL;
            if (isset($request->segment_group_id) && !empty($request->segment_group_id)) {
                $segment_group_id = $request->segment_group_id;
            } else {
                $merchant_segment_group = $this->segmentGroup($merchant_id, "", $string_file);
                $merchant_segment_group_ids = [];
                if (!empty($merchant_segment_group)) {
                    $merchant_segment_group = $merchant_segment_group->toArray();
                    $merchant_segment_group_ids = array_pluck($merchant_segment_group, 'id');
                } else {
                    $message = trans("$string_file.segment_group_not_found");
                    return $this->failedResponse($message);
                }
                if (in_array(1, $merchant_segment_group_ids) && in_array(2, $merchant_segment_group_ids)) {
                    $message = trans("$string_file.segment_group_id_required");
                    return $this->failedResponse($message);
                }
                $segment_group_id = $merchant_segment_group_ids[0];
            }
            $driverObj = new Driver();
            $wallet_amount = 5000;
            $create_fields = [
                'merchant_id' => $merchant_id,
                'first_name' => !empty($request->name) ? $request->name : "Demo",
                'last_name' => !empty($request->last_name) ? $request->last_name : "Driver",
                'email' => !empty($request->email) ? $request->email : $request->unique_number . "@driver.com",
                'phoneNumber' => !empty($request->phone) ? $request->phone : time(),
                'country_id' => $demo->CountryArea->country_id,
                'password' => "",
                'player_id' => $request->player_id,
                'unique_number' => $request->unique_number,
                'last_ride_request_timestamp' => date("Y-m-d H:i:s"),
                'country_area_id' => $demo->country_area_id,
                'ats_id' => $demo->ats_id, // for socket
                'profile_image' => "",
                'signupStep' => 9,
                'driver_referralcode' => $driverObj->GenrateReferCode(),
                'pay_mode' => 2,
                'network_code' => $request->network_code,
                'driver_gender' => NULL,
                'segment_group_id' => $segment_group_id,
            ];
            $driver = Driver::create($create_fields);
            $paramArray = array(
                'driver_id' => $driver->id,
                'booking_id' => NULL,
                'amount' => $wallet_amount,
                'narration' => 1,
            );
            WalletTransaction::WalletCredit($paramArray);
            $area = CountryArea::where('id', $demo->country_area_id)->with([
                'Documents' => function ($p) {
                    $p->addSelect('documents.id', 'expire_date as expire_status', 'documentNeed as document_mandatory', 'document_number_required');
                    $p->where('documentStatus', 1);
                }
            ])->with(['VehicleDocuments' => function ($q) {
                $q->addSelect('documents.id', 'expire_date as expire_status', 'documentNeed as document_mandatory', 'document_number_required');
                $q->where('documentStatus', 1);
            }])->first();

            $personal_documents = $area->Documents;
            if (!empty($personal_documents)) {
                foreach ($personal_documents as $personal_document) {
                    $doc = new DriverDocument;
                    $doc->document_file = NULL;
                    $doc->document_verification_status = 2;
                    $doc->document_number = time();
                    $doc->driver_id = $driver->id;
                    $doc->document_id = $personal_document->id;
                    $doc->status = 1;
                    $doc->expire_date = date('Y-m-d', strtotime('+5 years'));
                    $doc->save();
                }
            }

            if ($segment_group_id == 1) {
                $vehicle = DriverVehicle::create([
                    'driver_id' => $driver->id,
                    'owner_id' => $driver->id,
                    'merchant_id' => $merchant_id,
                    'vehicle_type_id' => $demo->vehicle_type_id,
                    'shareCode' => getRandomCode(10),
                    'vehicle_make_id' => $demo->vehicle_make_id,
                    'vehicle_model_id' => $demo->vehicle_model_id,
                    'vehicle_number' => "Demo",
                    'vehicle_color' => "Demo",
                    'vehicle_image' => "",
                    'vehicle_number_plate_image' => "",
                    'ac_nonac' => NULL,
                    'baby_seat' => NULL,
                    'vehicle_additional_data' => NULL,
                    'vehicle_verification_status' => 2,
                ]);
                // only maintain status of pivot table
                $vehicle->Drivers()->attach($driver->id, ['vehicle_active_status' => 1]);

                $vehicle_type = $area->VehicleType->where('id', $demo->vehicle_type_id);
                $arr_segment_id = $vehicle_type->map(function ($item) {
                    return $item->pivot->segment_id;
                });

                $arr_segment_id = array_unique(json_decode($arr_segment_id, true));
                $arr_segments = $area->Segment->whereIn('id', $arr_segment_id);
                // reindex the keys
                $arr_segments = collect($arr_segments->values());

                foreach ($arr_segments as $arr_segment) {
                    $query = ServiceType::select('service_types.id', 'service_types.serviceName')
                        ->whereHas('MerchantServiceType', function ($q) use ($merchant_id) {
                            $q->where('merchant_id', $merchant_id);
                            $q->orderBy('sequence');
                        })
                        ->whereHas('CountryArea', function ($q) use ($merchant_id, $demo) {
                            $q->where('country_area_id', $demo->country_area_id);
                        })
                        ->where('segment_id', $arr_segment->id);
                    $arr_services = $query->get();
                    // update driver signup step
                    $driver->Segment()->wherePivot('segment_id', $arr_segment->id)->detach();
                    $driver->Segment()->attach($arr_segment->id);
                    $driver->ServiceType()->wherePivot('segment_id', $arr_segment->id)->detach();
                    foreach ($arr_services as $service) {
                        $driver->ServiceType()->attach($service['id'], ['segment_id' => $arr_segment->id]);
                    }
                    $driver->signupStep = 9;
                    $driver->save();

                    $vehicle->ServiceTypes()->wherePivot('segment_id', $arr_segment->id)->detach();
                    foreach ($arr_services as $service) {
                        $vehicle->ServiceTypes()->attach($service['id'], ['segment_id' => $arr_segment->id]);
                        $driver->ServiceTypeOnline()->attach($service['id'], ['segment_id' => $arr_segment->id, 'driver_id' => $driver->id, 'driver_vehicle_id' => $vehicle->id]);
                    }
                }

                $vehicle_documents = $area->VehicleDocuments;
                if (!empty($vehicle_documents)) {
                    foreach ($vehicle_documents as $vehicle_document) {
                        $doc = new DriverVehicleDocument;
                        $doc->document = NULL;
                        $doc->document_verification_status = 2;
                        $doc->document_number = time();
                        $doc->document_id = $vehicle_document->id;
                        $doc->status = 1;
                        $doc->driver_vehicle_id = $vehicle->id;
                        $doc->expire_date = date('Y-m-d', strtotime('+5 years'));
                        $doc->save();
                    }
                }
            } elseif ($segment_group_id == 2) {
                $country_area_id = $driver->country_area_id;
                $arr_segments = Segment::select('id')->whereHas('CountryArea', function ($q) use ($country_area_id, $merchant_id) {
                    $q->where('country_area_id', $country_area_id);
                    $q->where('merchant_id', $merchant_id);
                })->where('segment_group_id', $segment_group_id)->get();

                $address = new DriverAddress;
                $address->driver_id = $driver->id;
                $address->segment_id = NULL;
                $address->address_name = "Office";
                $address->location = "Tower A, Spaze iTech Park, Sohna - Gurgaon Road, Block S, Sector 49, Gurugram, Haryana";
                $address->latitude = 28.414083;
                $address->longitude = 77.042157;
                $address->radius = 500;
                $address->address_status = 1;
                $address->address_type = 1;
                $address->save();

                foreach ($arr_segments as $arr_segment) {
                    $segment_id = $arr_segment->id;
                    $query = ServiceType::select('service_types.id', 'service_types.serviceName')
                        ->whereHas('MerchantServiceType', function ($q) use ($merchant_id) {
                            $q->where('merchant_id', $merchant_id);
                            $q->orderBy('sequence');
                        })
                        ->whereHas('CountryArea', function ($q) use ($merchant_id, $demo) {
                            $q->where('country_area_id', $demo->country_area_id);
                        })
                        ->where('segment_id', $arr_segment->id);
                    $arr_services = $query->get();

                    // update driver signup step
                    $driver->Segment()->wherePivot('segment_id', $arr_segment->id)->detach();
                    $driver->Segment()->attach($arr_segment->id);
                    $driver->ServiceType()->wherePivot('segment_id', $arr_segment->id)->detach();
                    foreach ($arr_services as $service) {
                        $driver->ServiceType()->attach($service['id'], ['segment_id' => $arr_segment->id]);
                    }
                    $driver->signupStep = 9;
                    $driver->save();

                    foreach ($arr_services as $service) {
                        $driver->ServiceTypeOnline()->attach($service['id'], ['segment_id' => $arr_segment->id, 'driver_id' => $driver->id, 'driver_vehicle_id' => NULL]);
                    }
                    // if price card owner is driver/service provider then save price card
                    $segment = Segment::select('id')->with(['Merchant' => function ($q) use ($arr_segment, $merchant_id) {
                        $q->addSelect('id');
                        $q->where('segment_id', $arr_segment->id);
                        $q->where('merchant_id', $merchant_id);
                    }])->find($arr_segment->id);
                    if (isset($segment->Merchant[0]->pivot->price_card_owner) && $segment->Merchant[0]->pivot->price_card_owner == 2) {
                        foreach ($arr_services as $service) {
                            $price = SegmentPriceCard::updateOrCreate(
                                [
                                    'driver_id' => $driver->id, 'service_type_id' => $service['service_type_id']
                                ],
                                [
                                    'driver_id' => $driver->id,
                                    'amount' => rand(100, 500),
                                    'service_type_id' => $service->id,
                                    'country_area_id' => $driver->country_area_id,
                                    'segment_id' => $arr_segment->id,
                                    'merchant_id' => $driver->merchant_id,
                                    'price_type' => 1,
                                ]
                            );
                        }
                    }
                    $service_time_slot_details = ServiceTimeSlotDetail::whereHas('ServiceTimeSlot', function ($q) use ($merchant_id, $country_area_id, $segment_id) {
                        $q->where([['segment_id', '=', $segment_id], ['merchant_id', '=', $merchant_id], ['country_area_id', '=', $country_area_id]]);
                    })->get();
                    if (!empty($service_time_slot_details)) {
                        foreach ($service_time_slot_details as $service_time_slot_detail) {
                            $driver->ServiceTimeSlotDetail()->attach($service_time_slot_detail->id, ['segment_id' => $segment_id]);
                        }
                    }
                    $segment_documents = $area->SegmentDocument->where('segment_id', $arr_segment->id);
                    if (!empty($segment_documents)) {
                        foreach ($segment_documents as $segment_document) {
                            $doc = new DriverSegmentDocument;
                            $doc->document_file = NULL;
                            $doc->document_verification_status = 2;
                            $doc->document_number = time();
                            $doc->document_id = $segment_document->id;
                            $doc->status = 1;
                            $doc->driver_id = $driver->id;
                            $doc->segment_id = $arr_segment->id;
                            $doc->expire_date = date('Y-m-d', strtotime('+5 years'));
                            $doc->save();
                        }
                    }
                }
            } else {
                $message = "Invalid segment group.";
                return $this->failedResponse($message);
            }

            $driver->login_logout = 1;
            $driver->driver_gender = NULL;
            $driver->online_offline = 1;
            $driver->player_id = $request->player_id;
            $driver->save();
            // Generate token for login
            $client = Client::where([['user_id', '=', $merchant_id], ['password_client', '=', 1]])->first();
            Config::set('auth.guards.api.provider', 'demo');
            $request->request->add([
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => $request->unique_number,
                'password' => "",
                'scope' => '',
            ]);
            $token_generation_after_login = Request::create(
                'oauth/token',
                'POST'
            );
            $collect_response = \Route::dispatch($token_generation_after_login)->getContent();
            $collectArray = json_decode($collect_response);
            if (isset($collectArray->error)) {
                return $this->failedResponse($collectArray->message);
            }
            $push_notification = get_merchant_notification_provider($request->merchant_id, $driver->id, 'driver');
            $return_data = array(
                'driver' => new DriverLoginResource($driver),
                'access_token' => $collectArray->access_token,
                'push_notification' => $push_notification
            );
            $message = trans("$string_file.success");
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        return $this->successResponse($message, $return_data);
    }

    public function getDriverGallery(Request $request)
    {
        $string_file = $this->getStringFile($request->merchant_id);
        try {
            $driver = new Driver;
            $arr_return = $driver->getDriverGallery($request);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.data_found"), $arr_return);
    }

    public function saveDriverGallery(Request $request)
    {
        $request_fields = [
            'image' => 'required',
            'segment_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile($driver->Merchant);
            $driver_id = $driver->id;
            $merchant_id = $driver->merchant_id;
            $segment_id = $request->segment_id;
            $image = $request->image;
            //            $arr_images = json_decode($arr_images,true);
            //            foreach($arr_images as $image)
            //            {
            if($request->multi_part == 1){
                $additional_req = ['compress' => true,'custom_key' => 'product'];
                $image = $this->uploadImage("image", 'driver_gallery', $merchant_id,'single',$additional_req);
            }else{
                $image = $this->uploadBase64Image("image", 'driver_gallery', $merchant_id);
            }
            $gallery = new DriverGallery;
            $gallery->driver_id = $driver_id;
            $gallery->segment_id = $segment_id;
            $gallery->image_title = $image;
            $gallery->save();
            //            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"), []);
    }

    public function deleteDriverGallery(Request $request)
    {
        $request_fields = [
            'image_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {
            $driver = $request->user('api-driver');
            $id = $request->image_id;
            $string_file = $this->getStringFile($driver->Merchant);
            $gallery = DriverGallery::Find($id);
            if (!empty($gallery->id)) {
                $gallery->delete();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        $arr_return = $driver->getDriverGallery($request);
        return $this->successResponse(trans("$string_file.success"), $arr_return);
    }

    public function developModeVerification(Request $request)
    {
        $request_fields = [
            'password' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $merchant_id = $request->merchant_id;
        $merchant = Merchant::find($merchant_id);
        $string_file = $this->getStringFile(NULL, $merchant);
        if (empty($merchant)) {
            $msg = trans("$string_file.configuration_not_found");
            return $this->failedResponse($msg);
        }
        if (!Hash::check($request->password, $merchant->password)) {
            $msg1 = trans("$string_file.invalid_password");
            return $this->failedResponse($msg1);
        }
        return $this->successResponse(trans("$string_file.data_found"));
    }

    public function checkDocumentStatus(Request $request)
    {
        $request_fields = [
            //            'password' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $merchant_id = $request->merchant_id;
        $driver_vehicle_id = $request->driver_vehicle_id;
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        try {
            $driver_type = $driver->segment_group_id;
            $check_per_exp_doc = DriverDocument::select('id', 'document_id', 'driver_id', 'document_file', 'document_verification_status')
                ->whereHas('Document', function ($q) {
                    $q->where('expire_date', 1);
                })
                ->where('document_verification_status', 4)
                ->where('status', 1)
                ->where('driver_id', $driver->id)->get();

            $arr_personal_expired_doc = [];
            $arr_vehicle_expired_doc = [];
            $arr_handyman_expired_doc = [];
            $arr_all_document_expired = [];
            if ($driver_type == 1 && !empty($driver_vehicle_id)) {
                $arr_vehicle_exp_doc = DriverVehicleDocument::select('id', 'driver_vehicle_id', 'document_id', 'document', 'document_verification_status')
                    ->whereHas('Document', function ($q) {
                        $q->where('expire_date', 1);
                    })->where('document_verification_status', 4)
                    ->where('status', 1)
                    ->where('driver_vehicle_id', $driver_vehicle_id)->get();
            }

            if ($driver_type == 2) {
                $arr_handyman_exp_doc = DriverSegmentDocument::select('id', 'segment_id', 'document_id', 'driver_id', 'document_file', 'document_verification_status')
                    ->whereHas('Document', function ($q) {
                        $q->where('expire_date', 1);
                    })->where('document_verification_status', 4)
                    ->where('status', 1)
                    ->where('driver_id', $driver->id)->get();
            }
            if (!empty($check_per_exp_doc) || !empty($arr_vehicle_exp_doc) || !empty($arr_handyman_exp_doc)) {
                $arr_status = driver_document_status($string_file);
                // personal document
                if (!empty($check_per_exp_doc) && $check_per_exp_doc->count() > 0) {
                    foreach ($check_per_exp_doc as $personal) {
                        $verification_status = isset($arr_status[$personal->document_verification_status]) ? $arr_status[$personal->document_verification_status] : "";
                        $arr_personal_expired_doc[] = [
                            'id' => $personal->document_id,
                            'doc_name' => $personal->Document->DocumentName,
                            'document_file' => get_image($personal->document_file, 'driver_document', $merchant_id, true),
                            'type' => "PERSONAL",
                            'document_verification_status' => $verification_status,
                            'document_number_required' => $personal->Document->document_number_required == 1 ? true : false,
                            'mandatory' => $personal->Document->documentNeed == 1 ? true : false,
                            'expiry' => $personal->Document->expire_date == 1 ? true : false,
                            'segment_id' => NULL,
                            'vehicle_id' => NULL,
                        ];
                    }
                    if (!empty($arr_personal_expired_doc)) {
                        $arr_all_document_expired = array_merge($arr_all_document_expired, $arr_personal_expired_doc);
                    }
                }
                // vehicle expired document
                if (!empty($arr_vehicle_exp_doc) && $arr_vehicle_exp_doc->count() > 0) {
                    foreach ($arr_vehicle_exp_doc as $vehicle) {
                        $verification_status = isset($arr_status[$vehicle->document_verification_status]) ? $arr_status[$vehicle->document_verification_status] : "";
                        $arr_vehicle_expired_doc[] = [
                            'id' => $vehicle->document_id,
                            'doc_name' => $vehicle->Document->DocumentName,
                            'document_file' => get_image($vehicle->document_file, 'driver_document', $merchant_id, true),
                            'type' => "VEHICLE",
                            'document_verification_status' => $verification_status,
                            'document_number_required' => $vehicle->Document->document_number_required == 1 ? true : false,
                            'mandatory' => $vehicle->Document->documentNeed == 1 ? true : false,
                            'expiry' => $vehicle->Document->expire_date == 1 ? true : false,
                            'segment_id' => NULL,
                            'vehicle_id' => $vehicle->driver_vehicle_id,
                        ];
                    }

                    if (!empty($arr_vehicle_expired_doc)) {
                        $arr_all_document_expired = array_merge($arr_all_document_expired, $arr_vehicle_expired_doc);
                        if($driver->Merchant->Configuration->add_multiple_vehicle == 1){
                            $arr_all_document_expired = [];
                        }
                    }
                }

                // segment expired document
                if (!empty($arr_handyman_exp_doc) && $arr_handyman_exp_doc->count() > 0) {
                    foreach ($arr_handyman_exp_doc as $handyman) {
                        $verification_status = isset($arr_status[$handyman->document_verification_status]) ? $arr_status[$handyman->document_verification_status] : "";
                        $arr_handyman_expired_doc[] = [
                            'id' => $handyman->document_id,
                            'doc_name' => $handyman->Document->DocumentName,
                            'document_file' => get_image($handyman->document_file, 'driver_document', $merchant_id, true),
                            'type' => "SEGMENT",
                            'document_verification_status' => $verification_status,
                            'document_number_required' => $handyman->Document->document_number_required == 1 ? true : false,
                            'mandatory' => $handyman->Document->documentNeed == 1 ? true : false,
                            'expiry' => $handyman->Document->expire_date == 1 ? true : false,
                            'segment_id' => $handyman->segment_id,
                            'vehicle_id' => NULL,
                        ];
                    }

                    if (!empty($arr_handyman_expired_doc)) {
                        $arr_all_document_expired = array_merge($arr_all_document_expired, $arr_handyman_expired_doc);
                    }
                }
            }
            $will_going_expire = [];
            $arr_action = (object) [];
            $display = false;
            $description = "";
            if (empty($arr_all_document_expired)) {
                $driver_id = $driver->id;
                $reminder_days = $driver->Merchant->Configuration->reminder_doc_expire;
                $expire_document = new \App\Http\Controllers\Merchant\ExpireDocumentController();
                $currentDate = date('Y-m-d');
                $reminder_last_date = date('Y-m-d', strtotime('+' . $reminder_days . ' days'));
                $will_going_expire = $expire_document->getDocumentGoingToExpire($currentDate, $reminder_last_date, $merchant_id, $driver_id);

                if (!empty($will_going_expire)) {
                    $personal_doc = $will_going_expire->DriverDocument;
                    $segment_doc = $will_going_expire->DriverSegmentDocument;
                    $vehicle_doc = $will_going_expire->DriverVehicles;
                    $arr_action = [
                        'personal' => $personal_doc->count() > 0 ? true : false,
                        'vehicle' => $vehicle_doc->count() > 0 ? true : false,
                        'segment' => $segment_doc->count() > 0 ? true : false,
                    ];
                    $display = true;
                    $description = trans("$string_file.document_expire_warning");
                }
            }
            $arr_return = ['arr_expired_docs' => $arr_all_document_expired, 'document_will_expire' => ['description' => $description, 'display' => $display, 'arr_action' => $arr_action]];
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.data_found"), $arr_return);
    }

    public function DriverReferral(Request $request)
    {
        $driver = $request->user('api-driver');
        $request_for = $request->device;
        $ref = new ReferralController();
        $data = $ref->getReferralDetailsForApp("DRIVER", $driver->id, $request_for);
        $merchant_id = $driver->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        return $this->successResponse(trans("$string_file.success"), $data);
    }

    public function SignupValidation(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);

        //Encrypt and Decrypt
        $merchant = Merchant::Find($merchant_id);
        if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($request->email){
                    $email = decryptText($request->email,$secret,$iv);
                    $request->merge(['email'=> $email]);
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        
        if (!empty($request->email)) {
            $validator = Validator::make($request->all(), [
                'email' => [
                    'email',
                    Rule::unique('drivers', 'email')->where(function ($query) use ($merchant_id) {
                        return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id], ['email', '!=', NULL]]);
                    })
                ],
            ], [
                'email.unique' => trans("$string_file.email_already_used"),
            ]);

            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }
        }

        try {
            $ref = new ReferralController();
            $ref->checkForReferral($request->referral_code, $merchant_id, $request->country_id, $request->area_id, 'DRIVER');
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.validate"), []);
    }

    public function changeRideGender(Request $request)
    {
        $valid = validator($request->all(), [
            'rider_gender_choice' => 'required'
        ]);

        if ($valid->fails()) {
            $errors = $valid->messages()->all();
            return response()->json(['result' => '0', 'message' => $errors[0], 'data' => []]);
        }
        $driver = $request->user('api-driver');
        $driver->rider_gender_choice = $request->rider_gender_choice;
        $driver->save();
        $msg = $request->rider_gender_choice == 0 ? __('api.remove_rider_gen') : __('api.change_rider');
        return response()->json(['result' => '1', 'message' => $msg]);
    }

    // This api created by Yamini to handle bank details on signup
    public function BankDetailsSave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string',
            'account_holder_name' => 'required|string',
            'account_number' => 'required|string',
            'account_type' => 'nullable|string',
            'online_code' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $driver = Driver::Find($request->driver_id);
        DB::beginTransaction();
        try {
            if (isset($request->segment_group_id) && !empty($request->segment_group_id)) {
                $segment_group = $request->segment_group_id;
            } else {
                $segment_group = 1;
            }
            $driver->bank_name = $request->bank_name;
            $driver->account_type_id = $request->account_type;
            $driver->online_code = $request->online_code;
            $driver->account_holder_name = $request->account_holder_name;
            $driver->account_number = $request->account_number;
            $driver->bank_address = $request->account_number;
            $driver->bank_post_code = $request->account_number;
            $driver->segment_group_id = $segment_group;
            $driver->signupStep = 3; // in case of handyman vehicle step will be skipped
            $driver->save();
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        $driver_data = new DriverLoginResource($driver);
        DB::commit();
        return $this->successResponse(trans('api.message104'), array('driver' => $driver_data));
        //        return response()->json(['result' => "1", 'message' => trans('api.message103'), 'data' => $driver]);
    }

    //Check User/Driver
    public function CheckDriver(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile($driver->merchant_id);
        $validator = Validator::make($request->all(), [
            'search_by' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            //            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        } else {
            if ($request->type == "DRIVER") {
                $search = $request->search_by;
                $driver = Driver::where('merchant_id', '=', $request->merchant_id)
                    ->where('driver_delete', '=', NULL)
                    ->where(function ($query) use ($search) {
                        $query->where('email', '=', $search)
                            ->orWhere('phoneNumber', '=', $search);
                    })->first();
            } else {
                $search = $request->search_by;
                $driver = User::where('merchant_id', '=', $request->merchant_id)
                    ->where('user_delete', '=', NULL)
                    ->where(function ($query) use ($search) {
                        $query->where('email', '=', $search)
                            ->orWhere('UserPhone', '=', $search);
                    })->first();
            }

            if ($driver) {
                $phone_key = $request->type == "DRIVER" ? "phoneNumber" : "UserPhone";
                $phone_value = $request->type == "DRIVER" ? $driver->phoneNumber : $driver->UserPhone;
                $data = array(
                    "id" => $driver->id,
                    "first_name" => $driver->first_name,
                    "last_name" => $driver->last_name,
                    "email" => $driver->email,
                    "$phone_key" => $phone_value,
                );
                return $this->successResponse(trans("$string_file.success"),$data);
            } else {
                return $this->failedResponse(trans("$string_file.user")." ".trans("$string_file.data_not_found"));
            }
        }
    }

    //Transfer wallet money
    public function TransferWalletMoney(Request $request)
    {
        $sender = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $sender->Merchant);
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer',
            'amount' => 'required|numeric|gt:0',

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        } else {
            if ($request->amount <= $sender->wallet_money) {
                if ($request->type == "DRIVER") {

                    $receiver = Driver::find($request->receiver_id);
                    if ($sender->country_id != $receiver->country_id) {
                        return $this->failedResponse(trans("$string_file.sender_receiver_currency_must_same"));
                    }
                    //Credit amount into receiver's wallet // that is driver
                    $paramArray = array(
                        'merchant_id' => $sender->merchant_id,
                        'driver_id' => $receiver->id,
                        'booking_id' => NULL,
                        'amount' => $request->amount,
                        'narration' => 23,
                        'platform' => 1,
                        'payment_method' => 2,
                        'sender' => $sender->first_name . ' ' . $sender->last_name,
                    );
                    WalletTransaction::WalletCredit($paramArray);
                } else {
                    $receiver = User::find($request->receiver_id);
                    if ($sender->country_id != $receiver->country_id) {
                        return $this->failedResponse(trans("$string_file.sender_receiver_currency_must_same"));
                    }
                    //Credit amount into receiver's wallet
                    $paramArray = array(
                        'merchant_id' => $sender->merchant_id,
                        'user_id' => $receiver->id,
                        'booking_id' => NULL,
                        'amount' => $request->amount,
                        'narration' => 12,
                        'platform' => 2,
                        'payment_method' => 2,
                        'transaction_id' => NULL,
                        'sender' => $sender->first_name . ' ' . $sender->last_name,
                        'wallet_transfer_id' => $sender->id // save the sender id
                    );
                    WalletTransaction::UserWalletCredit($paramArray);
                }
                //Debit amount from sender's wallet
                $paramArray = array(
                    'driver_id' => $sender->id,
                    'booking_id' => NULL,
                    'amount' => $request->amount,
                    'narration' => 13,
                    'platform' => 2,
                    'payment_method' => 2,
                    'transaction_id' => NULL,
                    'transaction_type' => 3,
                    'receiver' => $receiver->first_name . ' ' . $receiver->last_name,
                    'wallet_transfer_id' => $receiver->id // save receiver id
                );
                WalletTransaction::WalletDeduct($paramArray);

                return $this->successResponse(trans("$string_file.amount_transferred"));
            } else {
                return $this->failedResponse(trans("$string_file.wallet_insufficient_amount"));
            }
        }
    }

    public function AccountDelete(Request $request)
    {
        $delete = $request->user('api-driver');
        $id = $delete->id;
        $merchant_id = $delete->merchant_id;
        $string_file = $this->getStringFile(NULL, $delete->Merchant);
        $bookings = Booking::where([['driver_id', '=', $id]])->whereIn('booking_status', [1002, 1003, 1004])->get();

        if ($delete->free_busy != 1 && empty($bookings->toArray())) {

            $delete->driver_delete = 1;
            $delete->online_offline = 2;
            $delete->login_logout = 2;
            $driverDetail = $delete->DriverDetail;
            if($request->cancel_reason_id){
                $driverDetail->cancel_reason_id = $request->cancel_reason_id;
            }
            $driverDetail->account_cancel_reason = $request->account_cancel_reason;
            $driverDetail->save();
            $delete->save();


            // make document inactive
            DriverDocument::where([['driver_id', '=', $delete->id]])->update(['status' => 2]);
            DriverVehicleDocument::whereHas('DriverVehicle', function ($q) use ($id) {
                $q->where('driver_id', $id);
            })->update(['status' => 2]);

            DriverVehicle::where([['owner_id', '=', $delete->id], ['driver_id', '=', $delete->id]])->update(['vehicle_delete' => 1]);

            setLocal($delete->language);
            $data = ['booking_status' => '999'];
            $message = trans("$string_file.account_has_been_deleted");
            $title = trans("$string_file.account_deleted");
            $arr_param = ['driver_id' => $delete->id, 'data' => $data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title];
            Onesignal::DriverPushMessage($arr_param);
            setLocal();

            return $this->successResponse(trans("$string_file.account_deleted"));
        } else {
            return $this->failedResponse(trans("$string_file.some_thing_went_wrong"));
        }
    }

    // Enabled/Disabled Pool Request from driver app
    public function PoolOnOff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|integer|min:1|max:2',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $driverdata = $request->user('api-driver');
        $driverdata->pool_ride_active = 1;
        $string_file = $this->getStringFile($driverdata->merchant_id);
        $message = trans("$string_file.pool_enabled");
        if ($request->status == 2) {
            $message = trans("$string_file.pool_disabled");
            $driverdata->pool_ride_active = 2;
        }

        $driverdata->save();

        return $this->successResponse($message, []);
        // $message = $request->pool_status == 1 ? trans('api.poolon') : trans('api.pooloff');
        // return response()->json(['result' => "1", 'message' => $message, 'data' => []]);
    }

    public function DvlaVehivehicleDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'registrationNumber' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $string_file = $this->getStringFile($request->merchant_id);
        $merchant = Merchant::find($request->merchant_id);

        $res = $this->getDvlaDetails($request->registrationNumber, $merchant);

        if ($res === false) {
            return $this->failedResponse(trans("$string_file.unknown")." ".trans("$string_file.error"));
        }

        $response = json_decode($res);
        if(isset($response->errors[0]->status))
            return $this->failedResponse($response->errors[0]->detail);
        return $this->successResponse(trans("$string_file.success"), $response);
    }


    public function availableBooking(Request $request){
        $validator = Validator::make($request->all(), [
            'segment_id' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $data  = [];

        try {
            $segment_id = $request->segment_id;
            $distance = $request->distance;

            $driverLatitude = $driver->current_latitude;
            $driverLongitude = $driver->current_longitude;

            $distance_unit = $driver->CountryArea->Country->distance_unit;
//            $ride_radius = json_decode($driver->merchant->BookingConfiguration->driver_ride_radius_request, true)[0];
//            $radius = $distance_unit == 2 ? 3958.756 : 6367;

            $bookings = Booking::with('Segment')
                ->whereRaw("
                    6371 * acos(
                        cos(radians(?)) * cos(radians(pickup_latitude)) * 
                        cos(radians(pickup_longitude) - radians(?)) + 
                        sin(radians(?)) * sin(radians(pickup_latitude))
                    ) <= ?
                ", [$driverLatitude, $driverLongitude, $driverLatitude, $distance])
                ->where('segment_id', $segment_id)
                ->where('booking_status', 1001)
                ->where('country_area_id', $driver->country_area_id)
                ->where("merchant_id", $driver->merchant_id)
                ->get();

            $bookingDataController = new BookingDataController();
            foreach($bookings as $booking_request){
                $booking_request_driver = BookingRequestDriver::where("booking_id", $booking_request->id)
                                            ->where("driver_id", $driver->id)
                                            ->where("request_status", 3)
                                            ->first();
                if(empty($booking_request_driver)){
                   $data[] = $bookingDataController->BookingNotification($booking_request,NULL);
                }
            }
        }
        catch(\Exception $e){
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.success"), $data);
    }
    
    public function deliveryAvailableBooking(Request $request): \Illuminate\Http\JsonResponse
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);

        try {
            $distance = $request->distance ?? 20;
            $driverLatitude = $driver->current_latitude;
            $driverLongitude = $driver->current_longitude;

            $orders = Order::select('orders.id','user_id', 'merchant_order_id as number' ,'business_segment_id', DB::raw("null as laundry_outlet_id") , 'service_type_id', 'drop_location', 'payment_method_id', 'drop_latitude as latitude', 'drop_longitude as longitude', 'order_status', 'country_area_id', 'merchant_id', 'segment_id', 'final_amount_paid', DB::raw("'BUSINESS_SEGMENT' as type"), DB::raw("null as pickup_location"), 'additional_notes', 'brd.created_at', 'otp_for_pickup', 'confirmed_otp_for_pickup', 'driver_vehicle_id',  DB::raw("null as travel_distance"), DB::raw("null as estimate_distance"), DB::raw("null as estimate_time"), DB::raw("null as drop_latitude"), DB::raw("null as drop_longitude"), DB::raw("null as receiver_details"), DB::raw("null as waypoints"), 'prescription_image', 'delivery_mode')
                ->join("booking_request_drivers as brd", "brd.order_id", "=", "orders.id")
                ->where("brd.driver_id", $driver->id)
                ->whereIn("request_status", [1,2,3])
                ->whereNotIn('order_status', [2, 3, 12, 11, 5, 8])
                ->where('country_area_id', $driver->country_area_id)
                ->where("merchant_id", $driver->merchant_id)->get();
                // dd(DB::getQueryLog());
                
            $laundry_outlet_orders = LaundryOutletOrder::with(['LaundryOutlet'])
                ->select('laundry_outlet_orders.id','user_id', 'merchant_order_id as number', DB::raw("null as business_segment_id"),'laundry_outlet_id', 'service_type_id','drop_location', 'payment_method_id', 'drop_latitude as latitude', 'drop_longitude as longitude', 'order_status', 'country_area_id', 'merchant_id', 'segment_id', 'final_amount_paid', DB::raw("'LAUNDRY_OUTLET' as type"), DB::raw("null as pickup_location"), 'additional_notes', 'brd.created_at', 'travel_distance', DB::raw("null as travel_distance"), DB::raw("null as estimate_distance"), DB::raw("null as estimate_time"), DB::raw("null as drop_latitude"), DB::raw("null as drop_longitude"), DB::raw("null as receiver_details"), DB::raw("null as waypoints"), 'order_item_images')
                ->join("booking_request_drivers as brd", "brd.laundry_outlet_order_id", "=", "laundry_outlet_orders.id")
                ->where("brd.driver_id", $driver->id)
                ->whereIn("request_status", [1,2])
                ->whereNotIn("order_status", [2, 3, 7,9,12, 11, 5, 8, 14,17])
                ->where("country_area_id", $driver->country_area_id)
                ->where("merchant_id", $driver->merchant_id)
                ->groupBy('laundry_outlet_orders.id', 'user_id', 'merchant_order_id', 'laundry_outlet_id', 'service_type_id', 'drop_location', 'payment_method_id', 'drop_latitude', 'drop_longitude', 'order_status', 'country_area_id', 'merchant_id', 'segment_id', 'final_amount_paid', 'additional_notes')
                ->get();

            $bookings = Booking::with("BookingDetail")
                ->select('bookings.id', 'user_id','merchant_booking_id as number',  DB::raw("null as business_segment_id"), DB::raw("null as laundry_outlet_id"), 'service_type_id', 'drop_location', 'payment_method_id', 'pickup_latitude as latitude', 'pickup_longitude as longitude', 'booking_status as order_status', 'country_area_id', 'merchant_id', 'segment_id',  DB::raw("null as final_amount_paid"), DB::raw("'TAXI_DELIVERY' as type"), 'pickup_location', 'additional_notes', 'bookings.created_at',DB::raw("null as travel_distance") ,  'estimate_distance', 'estimate_time', 'drop_latitude', 'drop_longitude', 'receiver_details','waypoints', 'estimate_bill')
                ->whereRaw("
                6371 * acos(
                    cos(radians(?)) * cos(radians(pickup_latitude)) *
                    cos(radians(pickup_longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(pickup_latitude))
                ) <= ?
            ", [$driverLatitude, $driverLongitude, $driverLatitude, $distance])
                ->where("segment_id", 2)
                ->whereNotIn('booking_status', [1005 , 1006, 1007, 1008, 1016 , 1018])
                ->where('country_area_id', $driver->country_area_id)
                ->where("merchant_id", $driver->merchant_id)
                ->get();

            $combinedOrders = $orders->toBase()
            ->merge($laundry_outlet_orders->toBase())
            ->merge($bookings->toBase());

            $data = (object)[
                'combined_orders' => $combinedOrders,
                'merchant_id' => $driver->merchant_id,
                'driver_id' => $driver->id,
            ];
            $return_response = new DriverDeliveryResource($data);
        }
        catch(\Exception $e){
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.success"), $return_response);
    }

    public function driverAdditionalDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'details_for' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $data  = [];
        DB::beginTransaction();
        try{
            switch($request->details_for){
                case "GUARANTOR_DETAILS":
                    $additional_req = ['compress' => true,'custom_key' => 'product'];
                    $image_name = $this->uploadImage('guarantor_image', 'driver_additional_details', $driver->merchant_id,'single',$additional_req);
                    $guarantor_details = [
                        "name" => $request->name,
                        "phone" => $request->phone,
                        "description"=>$request->description,
                        "image"=>$image_name
                    ];
                    $driver->driver_guarantor_details = json_encode($guarantor_details);
                    $auto_verify = get_driver_auto_verify_status($driver->id);
                    $driver->signupStep =  $auto_verify == 1 ? 9 : 8;
                    $driver->reject_driver = 1;
                    $driver->save();
                    break;
                case "SSN_NUMBER":
                        if(!empty($request->driver_ssn_number)){
                            $driver->driver_ssn_number = $request->driver_ssn_number;
                            $driver->signupStep = 8;
                            $driver->save();
                        }else{
                            return $this->failedResponse(trans("$string_file.invalid"));
                        }
                        break;
                case "PASSWORD_AUTHENTICATION":
                    $pass = $request->password;
                    if(Hash::check($pass, $driver->password)){
                        return $this->successResponse(trans("$string_file.success"));
                    }
                    return $this->failedResponse(trans("$string_file.invalid_password"));
                    break;
                case "DRIVER_KIN_RESUBMIT":
                    if(!empty($request->driver_kin_details)){
                        $driver->kin_details = $request->driver_kin_details;
                        $driver->save();
                    }
                    else{
                        return $this->failedResponse(trans("$string_file.invalid"));
                    }
                    break;
                case "SOS_v1_NOTIFICATION":

                    break;
            }
        }
        catch(\Exception $e){
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"));
    }


    //  @ayush fastlane response for driver
    public function FastLaneConfiguration(Request $request)
    {
        $merchant   = Merchant::with('ApplicationConfiguration')->find($request->merchant_id);
        $raw_config  = $merchant->ApplicationConfiguration->fastlane_config_driver ?? null;

        if (empty($raw_config)) return (object)[];
        $data = is_array($raw_config) ? $raw_config : (json_decode($raw_config, true) ?: (object)[]);

        $files = [
            'json_path',
            'logo_path',
            'logo_round_path',
            'notification_path',
            'google_service_plist',
            'jks_file',
            'icon_folder_name',
            'splash_screen',
        ];

        foreach ($files as $key) {
            $downloadable = true;
            if($key == "splash_screen") $downloadable = false;
            if (!empty($data[$key]))  $data[$key] = get_image( $data[$key], 'fastlane', $merchant->id, true,  true,  '', '', $downloadable );
        }

        return $data;
    }

}
