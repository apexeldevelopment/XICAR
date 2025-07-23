<?php

namespace App\Http\Controllers\Helper;

use App\Models\Booking;
use App\Models\Configuration;
//use App\Http\Controllers\Helper\Merchant;
use App\Models\DriverVehicle;
use App\Models\Hotel;
use App\Models\HotelWalletTransaction;
use App\Models\MerchantNavigationDrawer;
use App\Models\PaymentOptionsConfiguration;
use App\Models\PaymentOptionTranslation;
use App\Models\TaxiCompaniesWalletTransaction;
use App\Models\TaxiCompany;
use App\Models\User;
use App\Models\Driver;
use App\Models\DriverWalletTransaction;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantNavDrawer;
use App\Models\PaymentConfiguration;
use App\Models\Onesignal;
use App\Models\CountryArea;
use App\Models\Outstanding;
use App\Models\Country;
use App\Models\UserWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CommonController extends Controller
{

    public static function settleUserOutstanding($user_id, $merchant_id)
    {
        $user = User::find($user_id);
        $user_outstandings = $user->outstandings;
        $payment_config = PaymentConfiguration::select('outstanding_payment_to')->where('merchant_id', $merchant_id)->first();
        if ($user_outstandings->count() > 0 && $payment_config && $payment_config->outstanding_payment_to == 2) {
            foreach ($user_outstandings as $outstand) {
                $driverx = Driver::find($outstand->driver_id);
                $driverx->wallet_money = sprintf('%0.2f', $driverx->wallet_money + $outstand->amount);
                $driverx->save();
            }
            // clear user outstanding
            Outstanding::where('user_id', $user_id)->delete();
        }
    }

    public static function BookingStatus($booking_status, $string_file = "")
    {
        switch ($booking_status) {
            case "1000":
                $booking_text = trans("$string_file.in_drive_booking");
                break;
            case "1001":
                $booking_text = trans("$string_file.new_ride");
                break;
            case "1002":
                $booking_text = trans("$string_file.accepted");
                break;
            case "1012": //PARTIAL
                $booking_text = trans("$string_file.partial_accepted");
                break;
            case "1003":
                $booking_text = trans("$string_file.arrived");
                break;
            case "1004":
                $booking_text = trans("$string_file.started");
                break;
            case "1005":
                $booking_text = trans("$string_file.completed");
                break;
            case "1006":
                $booking_text = trans("$string_file.user_cancelled");
                break;
            case "1007":
                $booking_text = trans("$string_file.driver_cancelled");
                break;
            case "1008":
                $booking_text = trans("$string_file.admin_cancelled");
                break;
            case "1018":
                $booking_text = trans("$string_file.expired_by_cron"); //'Expired by cron (rider later case)',
                break;
        }
        return $booking_text;
    }

    public static function UserHistoryBookingStatus($booking_status, $string_file = "")
    {
        switch ($booking_status) {
            case "1000":
                $booking_text = trans("$string_file.in_drive_booking");
                break;
            case "1001":
                $booking_text = trans("$string_file.new_ride");
                break;
            case "1002":
                $booking_text = trans("$string_file.accepted");
                break;
            case "1012": //PARTIAL
                $booking_text = trans("$string_file.partial_accepted");
                break;
            case "1003":
                $booking_text = trans("$string_file.arrived");
                break;
            case "1004":
                $booking_text = trans("$string_file.started");
                break;
            case "1005":
                $booking_text = trans("$string_file.completed");
                break;
            case "1006":
                $booking_text = trans("$string_file.user_cancelled");
                break;
            case "1007":
                $booking_text = trans("$string_file.driver_cancelled");
                break;
            case "1008":
                $booking_text = trans("$string_file.admin_cancelled");
                break;
            case "1018":
                $booking_text = trans("$string_file.auto_expired");
                break;
        }
        return $booking_text;
    }

    public static function DriverHistoryBookingStatus($booking_status, $string_file = "")
    {
        switch ($booking_status) {
            case "1000":
                $booking_text = trans("$string_file.in_drive_booking");
                break;
            case "1001":
                $booking_text = trans("$string_file.new_ride");
                break;
            case "1002":
                $booking_text = trans("$string_file.accepted");
                break;
            case "1012": //PARTIAL
                $booking_text = trans("$string_file.partial_accepted");
                break;
            case "1003":
                $booking_text = trans("$string_file.arrived");
                break;
            case "1004":
                $booking_text = trans("$string_file.started");
                break;
            case "1005":
                $booking_text = trans("$string_file.completed");
                break;
            case "1006":
                $booking_text = trans("$string_file.user_cancelled");
                break;
            case "1007":
                $booking_text = trans("$string_file.driver_cancelled");
                break;
            case "1008":
                $booking_text = trans("$string_file.admin_cancelled");
                break;
            case "1018":
                $booking_text = trans("$string_file.auto_expired");
                break;
        }
        return $booking_text;
    }

    public static function PolyLine($from, $to, $key)
    {
        $from = urlencode($from);
        $to = urlencode($to);
        $data = file_get_contents("https://maps.googleapis.com/maps/api/directions/json?origin=$from&destination=$to&mode=driving&key=$key");
        $data = json_decode($data, true);
        $points = $data['routes'][0]['overview_polyline']['points'];
        return $points;
    }

    //    public static function GoogleLocation($latitude, $longitude, $key)
    //    {
    //        if (!empty($latitude) && !empty($longitude)) {
    //            $geocodeFromLatLong = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . trim($latitude) . ',' . trim($longitude) . '&key=' . $key);
    //            $output = json_decode($geocodeFromLatLong);
    //            $status = $output->status;
    //            $address = ($status == "OK") ? $output->results[0]->formatted_address : '';
    //            if (!empty($address)) {
    //                return $address;
    //            } else {
    //                return false;
    //            }
    //        } else {
    //            return false;
    //        }
    //    }

    public static function GoogleAddress($latitude, $longitude, $key)
    {
        $geocodeFromLatLong = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . trim($latitude) . ',' . trim($longitude) . '&key=' . $key);
        $output = json_decode($geocodeFromLatLong);
        $status = $output->status;
        $log_data = [
            'request_type' => 'GeoCode Api Common Controller',
            'data' => $geocodeFromLatLong,
            'additional_notes' => 'Geocode Api for address',
        ];
        google_api_log($log_data);
        $address = ($status == "OK") ? $output->results[0]->formatted_address : '';
        $city = "";
        if (!empty($address)) {
            foreach ($output->results as $result) {
                foreach ($result->address_components as $addressPart) {
                    if ((in_array('locality', $addressPart->types)) && (in_array('political', $addressPart->types)))
                        $city = $addressPart->long_name;
                }
            }
            if (empty($city)) {
                foreach ($output->results as $result) {
                    foreach ($result->address_components as $addressPart) {
                        if ((in_array('administrative_area_level_2', $addressPart->types)) && (in_array('political', $addressPart->types)))
                            $city = $addressPart->long_name;
                    }
                }
            }

            $country_code = "";
            foreach ($output->results as $result) {
                foreach ($result->address_components as $addressPart) {
                    if ((in_array('country', $addressPart->types)) && (in_array('political', $addressPart->types)))
                        $country_code = $addressPart->short_name;
                }
            }

            $city = $city ? $city : 'CITY_NOT_FOUND';
            $newResult = array('address' => $address, 'city' => $city, 'country_code' => $country_code);
            return $newResult;
        } else {
            return false;
        }
    }

    public static function AerialDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371)
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

    public static function GoogleStaticImage($pickup, $drop, $key)
    {
        $from = urlencode($pickup);
        $to = urlencode($drop);
        $data = file_get_contents("https://maps.googleapis.com/maps/api/directions/json?origin=$from&destination=$to&mode=driving&key=$key");
        $data = json_decode($data, true);
        $status = $data['status'];
        if ($status != "OK") {
            return $data['error_message'];
        }
        $points = $data['routes'][0]['overview_polyline']['points'];
        $image = "https:maps.googleapis.com/maps/api/staticmap?center=&zoom=15&maptype=roadmap&path=weight:10%7Cenc:" . $points . "&sensor=false";
        return $image;
    }

    public static function Marchant($public_key, $secret_key)
    {
        return Merchant::where([['merchantPublicKey', '=', $public_key], ['merchantSecretKey', '=', $secret_key]])->first()->toArray();
    }
    public static function MerchantObj($public_key, $secret_key)
    {
        return Merchant::where([['merchantPublicKey', '=', $public_key], ['merchantSecretKey', '=', $secret_key]])->first();
    }

    public static function buildTree(array $elements, $parentId = 0)
    {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = self::buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    //    function countryList(Request $request)
    //    {
    //        $merchant_id = $request->merchant_id;
    //        $iso_code = $request->iso_code;
    //        $country =   Country::with(['CountryArea'=>function($q) {
    //            $q->select('country_id','id');
    //            $q->where('is_geofence',2);
    //        }])->whereHas('CountryArea')
    //            ->select('id', 'isoCode', 'phonecode', 'distance_unit', 'maxNumPhone', 'minNumPhone')->where('phonecode', $iso_code)->where('merchant_id', $merchant_id)->where('country_status', 1)->first();
    //
    //        if (!empty($country->id)) {
    //            $country->CountryArea->transform(function ($item, $key) {
    //                $item->AreaName = $item->CountryAreaName;
    //                return $item;
    //            });
    //            return response()->json(['result' => "1", 'message' => '', 'data' => $country]);
    //        }
    //        return response()->json(['result' => "0", 'message' => trans('api.no_country'), 'data' => []]);
    //    }

    public static function AddUserRideOutstading($user_id, $driver_id, $amount, $booking_id = NULL, $handyman_order_id = NULL)
    {
        \DB::beginTransaction();
        try {
            $outstanding_data['user_id'] = $user_id;
            $outstanding_data['booking_id'] = $booking_id;
            $outstanding_data['handyman_order_id'] = $handyman_order_id;
            $outstanding_data['driver_id'] = $driver_id;
            $outstanding_data['amount'] = $amount;
            $outstanding_data['reason'] = !empty($booking_id) ? 2 : 3; // 2 for ride 3 for handyman order
            $outstanding_data['pay_status'] = 0;
            $outstanding_submit = new Outstanding($outstanding_data);
            $outstanding_submit->save(); //if there is not error/exception in the above code, it'll commit
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();     //if there is an error/exception in the above code before commit, it'll rollback
        }
    }

    public function geofenceEnqueue(Request $request)
    {
        $driver = $request->user('api-driver');
        $validator = validator($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
            'type' => 'required|between:1,2'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0]]);
        }
        $geofence_queue_text = trans('api.not_in_geofence_queue_area');
        $geofence_queue_color_code = '#FF0000';

        $config = Configuration::where('merchant_id', $driver->merchant_id)->first();
        if (isset($config->geofence_module) && $config->geofence_module == 1) {
            if ($driver->online_offline == 1 && $driver->login_logout == 1 && $driver->free_busy == 2) {
                $driverArea = CountryArea::find($driver->country_area_id);
                $checkGeofenceArea = $this->findGeofenceArea($request->latitude, $request->longitude, $driverArea->id, $driver->merchant_id);
                if (!empty($checkGeofenceArea) && isset($checkGeofenceArea->RestrictedArea->queue_system) && $checkGeofenceArea->RestrictedArea->queue_system == 1) {
                    if ($request->type == 1) {
                        $driverQueue = GeofenceAreaQueue::where(function ($query) use ($driver, $driverArea, $checkGeofenceArea) {
                            $query->where([
                                ['merchant_id', '=', $driver->merchant_id],
                                ['country_area_id', '=', $driverArea->id],
                                ['geofence_area_id', '=', $checkGeofenceArea['id']],
                                ['driver_id', '=', $driver->id],
                                ['queue_status', '=', '1'] // Check if already in queue
                            ]);
                        })->whereDate('created_at', date('Y-m-d'))->get();
                        if (count($driverQueue) <= 0) {
                            $existingQueue = GeofenceAreaQueue::where(function ($query) use ($driver, $driverArea, $checkGeofenceArea) {
                                $query->where([['merchant_id', '=', $driver->merchant_id], ['country_area_id', '=', $driverArea->id], ['geofence_area_id', '=', $checkGeofenceArea['id']]]);
                            })->orderBy('queue_no', 'desc')->whereDate('created_at', date('Y-m-d'))->first();
                            if (!empty($existingQueue)) {
                                $newQueue = GeofenceAreaQueue::create(
                                    [
                                        'merchant_id' => $driver->merchant_id,
                                        'country_area_id' => $driverArea->id,
                                        'geofence_area_id' => $checkGeofenceArea['id'],
                                        'driver_id' => $driver->id,
                                        'queue_no' => ($existingQueue['queue_no'] + 1),
                                        'queue_status' => 1,
                                        'entry_time' => date('Y-m-d H:i:s')
                                    ]
                                );
                            } else {
                                $newQueue = GeofenceAreaQueue::create(
                                    [
                                        'merchant_id' => $driver->merchant_id,
                                        'country_area_id' => $driverArea->id,
                                        'geofence_area_id' => $checkGeofenceArea['id'],
                                        'driver_id' => $driver->id,
                                        'queue_no' => 1,
                                        'queue_status' => 1,
                                        'entry_time' => date('Y-m-d H:i:s')
                                    ]
                                );
                            }
                            $geofence_queue_text = $checkGeofenceArea->LanguageSingle->AreaName . ' Queue On - ' . $newQueue->queue_no;
                            $geofence_queue_color_code = '#008000';
                            return response()->json(['result' => '1', 'type' => '1', 'message' => trans('api.now_in_queue'), 'queue_no' => $newQueue->queue_no, 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
                        } else {
                            $driverQueue = GeofenceAreaQueue::where(function ($query) use ($driver, $driverArea, $checkGeofenceArea) {
                                $query->where([
                                    ['merchant_id', '=', $driver->merchant_id],
                                    ['country_area_id', '=', $driverArea->id],
                                    ['geofence_area_id', '=', $checkGeofenceArea['id']],
                                    ['driver_id', '=', $driver->id],
                                    ['queue_status', '=', '1'] // Check if already in queue
                                ]);
                            })->whereDate('created_at', date('Y-m-d'))->first();
                            $geofence_queue_text = $checkGeofenceArea->LanguageSingle->AreaName . ' Queue On - ' . $driverQueue->queue_no;
                            $geofence_queue_color_code = '#008000';
                            return response()->json(['result' => '1', 'type' => '1', 'queue_no' => $driverQueue->queue_no, 'message' => trans('api.already_in_queue'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
                        }
                    } elseif ($request->type == 2) {
                        $this->geofenceDequeue($request->latitude, $request->longitude, $driver, $checkGeofenceArea->id);
                        $geofence_queue_text = $checkGeofenceArea->LanguageSingle->AreaName . ' Queue Off';
                        $geofence_queue_color_code = '#FF0000';
                        return response()->json(['result' => '1', 'type' => '2', 'message' => trans('api.removed_from_queue'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
                    }
                } else {
                    return response()->json(['result' => '0', 'message' => trans('api.not_in_geofence_queue_area'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
                }
            } else {
                return response()->json(['result' => '0', 'message' => trans('api.not_eligible'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
            }
        } else {
            return response()->json(['result' => '0', 'message' => trans('api.geofence_not_enable'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
        }
    }

    public function geofenceDequeue($lat, $long, $driver, $geofence_area_id)
    {
        $config = Configuration::where('merchant_id', $driver->merchant_id)->first();
        if (isset($config->geofence_module) && $config->geofence_module == 1) {
            $geofenceArea = CountryArea::with('RestrictedArea')->where([['is_geofence', '=', 1], ['id', '=', $geofence_area_id]])->first();
            if (!empty($geofenceArea) && isset($geofenceArea->RestrictedArea->queue_system) && $geofenceArea->RestrictedArea->queue_system == 1) {
                $existingQueue = GeofenceAreaQueue::where([
                    ['merchant_id', '=', $driver->merchant_id],
                    ['country_area_id', '=', $driver->country_area_id],
                    ['geofence_area_id', '=', $geofence_area_id],
                    ['driver_id', '=', $driver->id],
                    ['queue_status', '=', '1'] // Check if already in queue
                ])->whereDate('created_at', date('Y-m-d'))->first();
                if (!empty($existingQueue)) {
                    $existingQueue->queue_status = 2;
                    $existingQueue->exit_time = date('Y-m-d H:i:s');
                    $existingQueue->save();
                }
            }
        }
    }

    public function findGeofenceArea($lat, $long, $base_area_id, $merchant_id)
    {
        $geofenceAreas = CountryArea::with('RestrictedArea')->whereHas('RestrictedArea', function ($query) use ($base_area_id) {
            $query->whereRaw(DB::raw("find_in_set($base_area_id,base_areas)"));
        })->get();
        $checkGeofenceArea = [];
        if (!empty($geofenceAreas)) {
            foreach ($geofenceAreas as $geofenceArea) {
                $checkGeofenceArea = $this->GeofenceArea($lat, $long, $merchant_id, $geofenceArea->id);
                if (!empty($checkGeofenceArea)) {
                    $geofenceAreaFound = CountryArea::with('RestrictedArea')->find($checkGeofenceArea['id']);
                    return $geofenceAreaFound;
                }
            }
        }
        return $checkGeofenceArea;
    }

    public static function NewCommission($booking_id, $amount, $discount_amount = 0.0)
    {
        try {
            $merchant = new \App\Http\Controllers\Helper\Merchant();
            $booking = Booking::with(['PriceCard' => function ($query) {
                $query->with('PriceCardCommission');
            }, 'PaymentMethod'])->find($booking_id);
            $merchant_id = $booking->merchant_id;
            $payment_method_type = $booking->PaymentMethod->payment_method_type;
            $commsion = $booking->PriceCard->PriceCardCommission;
            $commission_method = '';
            $commission_type = '';
            $commsion_amount = '';
            $hotel_commission_type = '';
            $hotel_commission_method = '';
            $hotel_commission_amount = '';
            // If taxi driver commission not set in price card then merchant driver commission apply on taxi driver
            if ($booking->taxi_company_id != '' && $commsion->taxi_commission_method != '' && $commsion->taxi_commission_type != '' && $commsion->taxi_commission != '') {
                $commission_method = $commsion->taxi_commission_method;
                $commission_type = $commsion->taxi_commission_type;
                $commsion_amount = $commsion->taxi_commission;
            } else {
                $commission_method = $commsion->commission_method;
                $commission_type = $commsion->commission_type;
                $commsion_amount = $commsion->commission;
            }
            $hotel_cut = '';
            if ($booking->hotel_id != '' && $booking->hotel_id != NULL) {
                $hotel_commission_type = $commsion->hotel_commission_type;
                $hotel_commission_method = $commsion->hotel_commission_method;
                $hotel_commission_amount = $commsion->hotel_commission;
                $hotel_cut = 0.0;
                // if ($hotel_commission_type == 2) {
                    if ($hotel_commission_method != '' && $hotel_commission_amount != '') {
                        if ($hotel_commission_method == 1) {  // 1:Flat commission per Ride (==OR==) 2:Percentage of Net Bill (before tax)
                            $hotel_cut = round($hotel_commission_amount, 2);
                        } else {
                            $hotel_cut = ($amount * $hotel_commission_amount) / 100;
                            $hotel_cut = round($hotel_cut, 2);
                        }
                        WalletTransaction::HotelWalletAdded($booking->hotel_id, $booking_id, $hotel_cut, trans('api.ride_commission'), trans('api.ride_commission'));
                    }
                // } else {
                //     $hotel_cut = $hotel_commission_amount;
                //     $amount -= $hotel_cut;
                //     WalletTransaction::HotelWalletAdded($booking->hotel_id, $booking_id, $hotel_cut, trans('api.ride_commission'), trans('api.ride_commission'));
                // }
            }

            if ($commission_method == 1) {  // 1:Flat commission per Ride (==OR==) 2:Percentage of Net Bill (before tax)
                if ($commsion_amount > $amount) {
                    $company_cut = $amount;
                    $driver_cut = "0.00";
                } else {
                    $company_cut = $commsion_amount;
                    $driver_cut = $amount - $company_cut;
                }
            } else {
                $company_cut = ($amount * $commsion_amount) / 100;
                $driver_cut = $amount - $company_cut;
            }
            if ($booking->Driver->pay_mode == 1) :
                $driver_cut += $company_cut;
                $company_cut -= $company_cut; //Just to not Interfare with format of $company_cut
            endif;
            $booking->company_cut = round_number($company_cut);
            $booking->driver_cut = round_number($driver_cut);
            $booking->save();
            $driver_id = $booking->driver_id;
            $driver = Driver::find($driver_id);

            if ($booking->taxi_company_id != '') {
                $walletTransaction = new WalletTransaction();
                $walletTransaction::TaxiComapnyWalletDeduct($booking->taxi_company_id, $booking_id, $company_cut, 1);
            }
            //            else {
            //                if ($booking->Driver->subscription_wise_commission == 2 || $booking->Driver->subscription_wise_commission == 0) {
            //                    $paramArray = array(
            //                        'driver_id' => $driver_id,
            //                        'booking_id' => $booking_id,
            //                        'amount' => $company_cut,
            //                        'narration' => 3,
            //                    );
            //                    WalletTransaction::WalletDeduct($paramArray);
            //                    if(isset($booking->BookingTransaction->tax_amount) && $booking->BookingTransaction->tax_amount > 0){
            //                        $paramArray = array(
            //                            'driver_id' => $driver_id,
            //                            'booking_id' => $booking_id,
            //                            'amount' => $booking->BookingTransaction->tax_amount,
            //                            'narration' => 17,
            //                        );
            //                        WalletTransaction::WalletDeduct($paramArray);
            //                    }
            //                    self::WalletDeduct($driver_id, $booking_id, $company_cut,3);
            //                }
            //            }
            $driver->total_earnings = round_number(($driver->total_earnings + $driver_cut));
            $driver->total_comany_earning = round_number(($driver->total_comany_earning + $company_cut));
            $driver->save();
            return [
                'company_cut' => round_number($company_cut),
                'driver_cut' => round_number($driver_cut),
                'hotel_cut' => round_number($hotel_cut),
                'commission_type' => $commission_type,
                'payment_method_type' => $payment_method_type,
            ];
        } catch (\Exception $e) {
            throw new \Exception('New Commission : ' . $e->getMessage());
        }
    }

    //$booking_id, $driver_id, $amount, $payment_method_type,$discount_amount,$cancellation_amount_received
    public function DriverRideAmountCredit($array_param)
    {

        $booking_id = isset($array_param['booking_id']) ? $array_param['booking_id'] : NULL;
        $order_id = isset($array_param['order_id']) ? $array_param['order_id'] : NULL;
        $handyman_order_id = isset($array_param['handyman_order_id']) ? $array_param['handyman_order_id'] : NULL;
        $driver_id = isset($array_param['driver_id']) ? $array_param['driver_id'] : NULL;
        $wallet_status = isset($array_param['wallet_status']) ? $array_param['wallet_status'] : NULL;
        $amount = isset($array_param['amount']) ? $array_param['amount'] : NULL;
        $narration = isset($array_param['narration']) ? $array_param['narration'] : NULL;
        //        $payment_method_type = isset($array_param['payment_method_type']) ? $array_param['payment_method_type'] : NULL;

        if ($wallet_status == "CREDIT") {
            $paramArray = array(
                'driver_id' => $driver_id,
                'booking_id' => $booking_id,
                'order_id' => $order_id,
                'handyman_order_id' => $handyman_order_id,
                'amount' => $amount,
                'narration' => $narration,
            );
            WalletTransaction::WalletCredit($paramArray);
        }
        if ($wallet_status == "DEBIT") {
            $paramArray = array(
                'driver_id' => $driver_id,
                'booking_id' => $booking_id,
                'order_id' => $order_id,
                'handyman_order_id' => $handyman_order_id,
                'amount' => $amount,
                'narration' => $narration,
            );
            WalletTransaction::WalletDeduct($paramArray);
        }
    }

    public static function filteredPaymentOptions($payment_options, $merchant_id, $payment_option_for = 1,$merchant = null){
        $is_payment_option_type_column_exist = Schema::hasColumn("payment_options_configurations", "payment_option_for");
        foreach ($payment_options as $key => $option) {
            $option['payment_type'] = empty($option['payment_type']) ? "" : $option['payment_type'];
            //commented by navdeep - because this is not required to put orWhere & due to this getting invalid data in response
//            $payment_option_config = PaymentOptionsConfiguration::where(function ($k) use ($merchant_id, $option) {
//                $k->where(array("merchant_id" => $merchant_id, "payment_gateway_provider" => $option['slug']))->orWhere(array("merchant_id" => $merchant_id, "payment_option_id" => $option['id']));
//            })->where(function ($q) use ($is_payment_option_type_column_exist, $payment_option_for) {
//                if ($is_payment_option_type_column_exist) {
//                    $q->whereIn("payment_option_for", [$payment_option_for, 3]);
//                }
//            })->first();
            $paymentOptionTranslation = PaymentOptionTranslation::where(array("merchant_id" => $merchant_id, "payment_option_id" => $option['id']))->first();
            if(isset($paymentOptionTranslation)){
                $option['payment_option_translation'] = $paymentOptionTranslation->name;
            }
            else{
                $option['payment_option_translation'] = "";
            }

            $payment_option_config = PaymentOptionsConfiguration::where(array("merchant_id" => $merchant_id, "payment_option_id" => $option['id']))
                ->where(function ($q) use ($is_payment_option_type_column_exist, $payment_option_for) {
                    if ($is_payment_option_type_column_exist) {
                        $q->whereIn("payment_option_for", [$payment_option_for, 3]);
                    }
                })->first();
            // A payment option configuration, That is empty or who doesn't belongs to payment option for, then remove that.
            if (empty($payment_option_config)) {
                unset($payment_options[$key]);
                continue;
            }

            //Encrypt Decrypt 
            if(!empty($payment_option_config) && isset($payment_option_config)){
                $apiSecretKey = $payment_option_config->api_secret_key;
                $apiPublicKey = $payment_option_config->api_public_key;
                if(isset($merchant) && !empty($merchant) && $merchant->Configuration->encrypt_decrypt_enable == 1){
                    try {
                        $keys = getSecAndIvKeys();
                        $iv = $keys['iv'];
                        $secret = $keys['secret'];

                        if($apiSecretKey){
                            $apiSecretKey = encryptText($apiSecretKey,$secret,$iv);
                        }
        
                        if($apiPublicKey){
                            $apiPublicKey = encryptText($apiPublicKey,$secret,$iv);
                        }
        

                    } catch (Exception $e) {
                        echo 'Error: ' . $e->getMessage();
                    }
                }

            }

            if ($option['slug'] == "OZOH") {
                $payment_option_config = PaymentOptionsConfiguration::where([["merchant_id", $merchant_id], ["payment_option_id", '=', $option['id']]])->first();
                //Encrypt Decrypt
                if(isset($merchant) && !empty($merchant) && $merchant->Configuration->encrypt_decrypt_enable == 1){
                    $apiSecretKey = $payment_option_config->api_secret_key;
                    $apiPublicKey = $payment_option_config->api_public_key;
                    try {
                        $keys = getSecAndIvKeys();
                        $iv = $keys['iv'];
                        $secret = $keys['secret'];

                        if($apiSecretKey){
                            $apiSecretKey = encryptText($apiSecretKey,$secret,$iv);
                        }
        
                        if($apiPublicKey){
                            $apiPublicKey = encryptText($apiPublicKey,$secret,$iv);
                        }
        

                    } catch (Exception $e) {
                        echo 'Error: ' . $e->getMessage();
                    }
                }

                $arr_details =  json_decode($option['params'], true);
                $arr_details['payment_redirect_url'] = route('api.ozo-payment-success');
                $arr_details['callback_url'] = route('api.ozo-payment-notification');
                $updated_details = json_encode($arr_details);
                $option['params'] = $updated_details;
                $arr_details["save_card"] = false;
                $arr_details['api_secret_key'] = $apiSecretKey;
                $arr_details['api_public_key'] = $apiPublicKey;
                $arr_details['auth_token'] = $payment_option_config->api_public_key;
                $arr_details['gateway_condition'] = $payment_option_config->gateway_condition; // 1 LIVE 2 SANDBOX
                $option['params_arr'] = $arr_details;
            } elseif ($option['slug'] == "MaxiCash") {
                $arr_details =  json_decode($option['params'], true);
                $arr_details['success_url'] = route('api.maxi-cash-success');
                $arr_details['cancel_url'] = route('api.maxi-cash-cancel');
                $arr_details['failure_url'] = route('api.maxi-cash-failure');
                $arr_details['notify_url'] = route('api.maxi-cash-notification');
                $updated_details = json_encode($arr_details);
                $option['params'] = $updated_details;
                $arr_details["save_card"] = false;
                $option['params_arr'] = $arr_details;
            } elseif ($option['slug'] == "PayGate") {
                $arr_details['payment_redirect_url'] = route('api.get-paygate-webview');
                $arr_details['payment_redirect_url_driver'] = route('api.get-paygate-webview-driver');
                $updated_details = $arr_details;
                $updated_details["save_card"] = false;
                $option['params_arr'] = $updated_details;
            } elseif ($option['slug'] == "PAYFAST") {
                // {"sandbox_url":"https://sandbox.payfast.co.za/eng/process","live_url":"https://www.payfast.co.za/eng/process"}
                $extra_data = json_decode($payment_option_config->additional_data, true);
                $arr_details['payment_success_url'] = route('payfast-success');
                $arr_details['payment_fail_url'] = route('payfast-fail');
                $live_url = isset($extra_data['live_url']) ? $extra_data['live_url'] : "";
                $sandbox_url = isset($extra_data['sandbox_url']) ? $extra_data['sandbox_url'] : "";
                $arr_details['payment_url'] = $payment_option_config->gateway_condition == 1 ? $live_url : $sandbox_url;
                $arr_details['api_secret_key'] = $apiSecretKey ? $apiSecretKey : "";
                $arr_details['api_public_key'] = $apiPublicKey ? $apiPublicKey : "";
                $arr_details['gateway_condition'] = $payment_option_config->gateway_condition; // 1 LIVE 2 SANDBOX
                $updated_details = $arr_details;
                $option['params_arr'] = $updated_details;
            } elseif ($option['slug'] == "FLUTTERWAVE") {
                $extra_data = json_decode($payment_option_config->additional_data, true);
                $encrypted_key = isset($extra_data['api_encrypted_key']) ? $extra_data['api_encrypted_key'] : (!empty($payment_option_config->auth_token) ? $payment_option_config->auth_token : "");
                $arr_details =  json_decode($option['params'], true);
                $arr_details['api_secret_key'] = $apiSecretKey ? $apiSecretKey : "";
                $arr_details['api_public_key'] = $apiPublicKey ? $apiPublicKey : "";
                $arr_details["api_encrypted_key"] = $encrypted_key;
                $arr_details["save_card"] = false;
                $arr_details["is_live"] = $payment_option_config->gateway_condition == 1 ? true : false;
                $option['params_arr'] = $arr_details;
            } elseif ($option['slug'] == "FATOORAH") {
                $extra_data = json_decode($payment_option_config->additional_data, true);
                //    $arr_details['payment_success_url'] = route('payfast-success');
                //    $arr_details['payment_fail_url'] = route('payfast-fail');
                $live_url = isset($extra_data['live_url']) ? $extra_data['live_url'] : "";
                $sandbox_url = isset($extra_data['sandbox_url']) ? $extra_data['sandbox_url'] : "";
                $arr_details['payment_url'] = $payment_option_config->gateway_condition == 1 ? $live_url : $sandbox_url;
                $arr_details['api_secret_key'] = $apiSecretKey ? $apiSecretKey : "";
                $arr_details['api_public_key'] = $apiPublicKey ? $apiPublicKey : "";
                $arr_details['gateway_condition'] = $payment_option_config->gateway_condition; // 1 LIVE 2 SANDBOX
                $updated_details = $arr_details;
                $option['params_arr'] = $updated_details;
            } elseif ($option['slug'] == "PAYBOX") {
                $extra_data = json_decode($payment_option_config->additional_data, true);
                $arr_details['payment_success_url'] = route('paybox-success');
                $arr_details['payment_fail_url'] = route('paybox-fail');
                $arr_details['payment_method_id'] = 4;
                $live_url = isset($extra_data['live_url']) ? $extra_data['live_url'] : "";
                $sandbox_url = isset($extra_data['sandbox_url']) ? $extra_data['sandbox_url'] : "";
                $arr_details['payment_url'] = $payment_option_config->gateway_condition == 1 ? $live_url : $sandbox_url;
                $arr_details['api_secret_key'] = $apiSecretKey ? $apiSecretKey : "";
                $arr_details['api_public_key'] = $apiPublicKey ? $apiPublicKey : "";
                $arr_details['gateway_condition'] = $payment_option_config->gateway_condition; // 1 LIVE 2 SANDBOX
                $updated_details = $arr_details;
                $option['params_arr'] = $updated_details;
            } elseif ($option['slug'] == "PAYGO") {
                $extra_data = json_decode($payment_option_config->additional_data, true);
                $arr_details['payment_method_id'] = 4;
                $live_url = isset($extra_data['live_url']) ? $extra_data['live_url'] : "";
                $sandbox_url = isset($extra_data['sandbox_url']) ? $extra_data['sandbox_url'] : "";
                $arr_details['payment_url'] = $payment_option_config->gateway_condition == 1 ? $live_url : $sandbox_url;
                $arr_details['api_secret_key'] = $apiSecretKey ? $apiSecretKey : "";
                $arr_details['api_public_key'] = $apiPublicKey ? $apiPublicKey : "";
                $arr_details['gateway_condition'] = $payment_option_config->gateway_condition; // 1 LIVE 2 SANDBOX
                $arr_details['sub_options'] = [
                    "card" => "Card", //trans("$string_file.card"),
                    "mtn" => "MTN", //trans("$string_file.mtn"),
                    "zamtel" => "Zamtel", //trans("$string_file.zamtel"),
                    "airtel_money" => "Airtel Money", //trans("$string_file.airtel_money"),
                ];
                $updated_details = $arr_details;
                $option['params_arr'] = $updated_details;
            } elseif ($option['slug'] == "KPAY") {
                $arr_details['payment_method_id'] = 4;
                $arr_details['sub_options'] = [
                    "cc" => "Visa/Master Card", //trans("$string_file.card"),
                    "momo" => "MTN/Airtel Money", //trans("$string_file.mtn"),
                ];
                $updated_details = $arr_details;
                $option['params_arr'] = $updated_details;
            } else {
                if (!empty($payment_option_config)) {
                    if (!empty($option['params'])) {
                        $updated_details =  json_decode($option['params'], true);
                        foreach ($updated_details as $key => &$updated_detail) {
                            if (isset($payment_option_config->$key) && !empty($payment_option_config->$key)) {
                                $updated_details[$key] = $payment_option_config->$key;
                                //Encrypt Decrypt
                                if(isset($merchant) && !empty($merchant) && $merchant->Configuration->encrypt_decrypt_enable == 1){
                                    try {
                                        $keys = getSecAndIvKeys();
                                        $iv = $keys['iv'];
                                        $secret = $keys['secret'];

                                        if($key == 'api_secret_key'){
                                            $updated_details[$key] = encryptText($payment_option_config->$key, $secret,$iv);
                                        }
                                        if($key == 'api_public_key'){
                                            $updated_details[$key] = encryptText($payment_option_config->$key, $secret,$iv);
                                        }

                                    } catch (Exception $e) {
                                        echo 'Error: ' . $e->getMessage();
                                    }
                                }

                            }
                        }
                        if(isset($updated_details['auth_token']) && $updated_details['auth_token'] == "Customer Account Id"){
                            $updated_details['auth_token'] = "";
                        }
                    }
                }
                // $option['params'] = json_encode($updated_details);
                $updated_details["save_card"] = false;
                $updated_details["is_live"] = !empty($payment_option_config->gateway_condition) && $payment_option_config->gateway_condition == 1 ? true : false;
                $option['params_arr'] = $updated_details;
            }
        }
        return $payment_options;
    }

    public function setLanguage($user_id, $type)
    {
        try {
            $user = NULL;
            if ($type == 1) {
                $user = User::find($user_id);
            } elseif ($type == 2) {
                $user = Driver::find($user_id);
            }
            if (!empty($user)) {
                $req_locale = request()->header("locale");
                $set_locale = !empty($req_locale) ? $req_locale : "en";
                $user->language = $set_locale;
                $user->save();
            }
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    // driver agency wallet transaction
    public function DriverAgencyWalletAmount($array_param)
    {


        $order_id = isset($array_param['order_id']) ? $array_param['order_id'] : NULL;
        $driver_agency_id = isset($array_param['driver_agency_id']) ? $array_param['driver_agency_id'] : NULL;
        // p($driver_agency_id);
        $wallet_status = isset($array_param['wallet_status']) ? $array_param['wallet_status'] : NULL;
        $amount = isset($array_param['amount']) ? $array_param['amount'] : NULL;
        $narration = isset($array_param['narration']) ? $array_param['narration'] : NULL;
        //        $payment_method_type = isset($array_param['payment_method_type']) ? $array_param['payment_method_type'] : NULL;

        if ($wallet_status == "CREDIT") {
            $paramArray = array(
                'driver_agency_id' => $driver_agency_id,
                'order_id' => $order_id,
                'amount' => $amount,
                'narration' => $narration,
            );
            WalletTransaction::driverAgencyWalletCredit($paramArray);
        }
        if ($wallet_status == "DEBIT") {
            $paramArray = array(
                'driver_agency_id' => $driver_agency_id,
                'order_id' => $order_id,
                'amount' => $amount,
                'narration' => $narration,
            );
            WalletTransaction::driverAgencyWalletDebit($paramArray);
        }
    }

    public static function getNavigationMenu($merchant_id){
        try{
            $merchant = Merchant::find($merchant_id);
            $menus_data = [];
            array_push($menus_data, array(
                "id" => "",
                "image" => get_image($merchant->BusinessLogo,'business_logo',$merchant->id,true),
                "name" => "",
                "sequence" => 0,
                "type" => "HEADER",
                "value" => "", //"YOUR ONE STOP ALCOHOL SERVICE",
                "sub_menus" => [],
                "text_colour" => "",
                "text_style" => ""
            ));
            $menus = MerchantNavigationDrawer::with("MerchantNavigationDrawerConfig")->where("merchant_id", $merchant_id)->orderBy("sequence")->get();
            if(!empty($menus)){
                foreach($menus as $menu){
                    $sub_menus = [];
                    if($menu->type == "PARENT_MENU"){
                        foreach($menu->MerchantNavigationDrawerConfig as $sub_menu){
                            array_push($sub_menus, array(
                                "id" => $sub_menu->id,
                                "name" => $sub_menu->Name,
                                "sequence" => $sub_menu->sequence,
                                "type" => $sub_menu->type,
                                "value" => !empty($sub_menu->value) ? $sub_menu->value : "",
                            ));
                        }
                    }
                    array_push($menus_data, array(
                        "id" => $menu->id,
                        "image" => !empty($menu->icon) ? get_image($menu->icon, "drawericons", $merchant_id) : "",
                        "name" => $menu->Name,
                        "sequence" => $menu->sequence,
                        "type" => $menu->type,
                        "value" => !empty($menu->value) ? $menu->value : "",
                        "sub_menus" => $sub_menus,
                        "data" => $menu->extra_data,
                        "text_colour" => "",
                        "text_style" => ""
                    ));
                }
            }
            return $menus_data;
        }catch (\Exception $exception){
            throw new \Exception($exception->getMessage());
        }
    }




    //places api helpers
    public static function searchViaMapbox($keyword, $booking_config, $user, $country_code, $location)
    {
        // Reorder to longitude,latitude for Mapbox
        list($lat, $lon) = explode(',', $location);
        $proximity = "$lon,$lat";
        
        $url = "https://api.mapbox.com/search/searchbox/v1/suggest?q=$keyword&access_token={$booking_config->map_box_key}&proximity={$proximity}&session_token=" . uniqid();

        if ($user->Merchant->ApplicationConfiguration->restrict_country_wise_searching == 1 && $user->Merchant->demo != 1) {
            $url .= "&country=$country_code";
        }

        $response = self::makeCurlRequest($url);
        if (empty($response) || empty($response->suggestions)) {
            return [];
        }

        $response_data = [];
        foreach($response->suggestions as $obj){
            if(isset($obj->full_address)){
                $data['description']= $obj->full_address;
                $data['place_id']= $obj->mapbox_id;
                $data['main_text']=$obj->place_formatted;
                $data['map']= "MAP_BOX";
                $response_data[] = $data;
            }
        }
        return $response_data;
    }

    public static function searchViaHereMaps($keyword, $booking_config)
    {
        $url = "https://autocomplete.search.hereapi.com/v1/autocomplete?q={$keyword}&apiKey={$booking_config->here_map_key}";
        $response = self::makeCurlRequest($url);
        if (empty($response) || empty($response->items)) {
            return [];
        }
        $response_data = [];
        foreach($response->items as $obj){
            if(isset($obj->title)){
                $data['description']= $obj->title;
                $data['place_id']= $obj->id;
                $data['main_text']=$obj->address->label;
                $data['map']= "HERE_MAP";
                $response_data[] = $data;
            }
        }
        return $response_data;

    }

    public static function searchViaGoogle($keyword, $language, $radius, $location, $booking_config, $user, $country_code)
    {
        $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?input=$keyword&language=$language&key={$booking_config->google_key}&radius=$radius&location=$location&sessiontoken={$user->id}.{$user->merchant_id}";

        if ($user->Merchant->ApplicationConfiguration->restrict_country_wise_searching == 1 && $user->Merchant->demo != 1) {
            $url .= "&components=country:$country_code";
        }

        $response = self::makeCurlRequest($url);

        if (!$response || $response->status !== "OK") {
            return [];
        }

        $response_data = [];
        foreach($response->predictions as $obj){
            $data['description']= $obj->description;
            $data['place_id']= $obj->place_id;
            $data['main_text']=$obj->structured_formatting->main_text;
            $data['map']= "GOOGLE";
            $response_data[] = $data;
        }
        return $response_data;

    }

    private static function makeCurlRequest($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
        ]);

        $response = curl_exec($curl);
        // dd($url, $response);
        curl_close($curl);

        return json_decode($response);
    }

    public static function storeSearchablePlace($merchant_id, $keyword, $country_id, $response_data)
    {
        \App\Models\SearchablePlace::create([
            'merchant_id' => $merchant_id,
            'keyword'     => $keyword,
            'country_id'  => $country_id,
            'response'    => json_encode($response_data)
        ]);
    }

    public function getRenewableSubscriptionDetails($driver, $vehicle_type_id): array
    {
        $renewable_subscription_price =0;
        $driverEarnings = 0;
        $package = \App\Models\RenewableSubscription::where([
            ['merchant_id', '=', $driver->merchant_id],
            ['country_area_id', '=', $driver->country_area_id],
            ['vehicle_type_id', '=', $vehicle_type_id]
        ])->first();

        $driverRenewableEarning = $driver->DriverRenewableSubscriptionRecord()->orderBy('id', 'DESC')->first();
        if (empty($driverRenewableEarning)) {
            $startDate = Carbon::createFromTimestamp($driver->renewable_subscription_trail_datetime, $driver->CountryArea->timezone)->startOfDay();
        } else {
            $startDate = Carbon::createFromTimestamp($driverRenewableEarning->timestamp, $driver->CountryArea->timezone)->startOfDay();
        }
        $endDate = Carbon::now($driver->CountryArea->timezone)->endOfDay();

        $driverEarnings = Booking::where('driver_id', $driver->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['BookingTransaction' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->get();

        $totalEarnings = $driverEarnings->sum(function ($booking) {
            return isset($booking->BookingTransaction) ? (float) $booking->BookingTransaction->driver_earning : 0;
        });
        $bookingCount = $driverEarnings->count();
        $values  = isset($package) ? $package->RenewableSubscriptionValue : [];

        if($totalEarnings != 0){
            $max_fare = 0;
            $max_fee = 0;
            foreach ($values as $value) {
                $max_fare = max($max_fare, $value->max_fare);
                $max_fee = max($max_fee, $value->subscription_fee);
                if($totalEarnings >= $value->min_fare && $totalEarnings <= $value->max_fare ){
                    $renewable_subscription_price = $value->subscription_fee;
                }
            }
            if ($totalEarnings > $max_fare) $renewable_subscription_price = $max_fee;
        }
        $last_renew_date = "";
        if(!empty($driverRenewableEarning) || !empty($driver->renewable_subscription_trail_datetime)){
            $last_renew_date = !empty($driverRenewableEarning) ? Carbon::createFromTimestamp($driverRenewableEarning->timestamp)->format('d M Y') : Carbon::createFromTimestamp($driver->renewable_subscription_trail_datetime, "UTC")->format('d M Y');
        }

        return [
            'renewable_subscription_price' => $renewable_subscription_price,
            'last_renew_date' => $last_renew_date,
            'bookingCount'=> $bookingCount,
            'totalEarnings'=>$totalEarnings
        ];
    }
}

