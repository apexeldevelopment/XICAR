<?php

namespace App\Traits;

use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\DistanceController;
use App\Http\Controllers\Helper\FindDriverController;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\Merchant;
use App\Http\Controllers\Helper\PolygenController;
use App\Http\Controllers\Helper\PriceController;
use App\Models\Booking;
use App\Models\BookingConfiguration;
use App\Models\BookingDeliveryDetails;
use App\Models\CountryArea;
use App\Models\DeliveryPackage;
use App\Models\Driver;
use App\Models\Outstanding;
use App\Models\PriceCard;
use App\Models\PromoCode;
use App\Models\Segment;
use App\Models\User;
use Auth;
use DB;
use Illuminate\Http\Request;

trait ManualDispatchTrait{

    use MerchantTrait, ImageTrait, AreaTrait;

    public function checkManualArea($request){
        try {
            if ($request->service == 4) {
                $request->merge(['service_type' => $request->service, 'area_id' => $request->manual_area]);
                $area = $this->checkOutstationDropArea($request);
                return $area;
            }
            $string_file = $this->getStringFile($request->merchant_id);
            $area = $this->checkGeofenceArea($request->latitude, $request->longitude, 'pickup', $request->merchant_id);
            if (empty($area)) {
                $area = PolygenController::Area($request->latitude, $request->longitude, $request->merchant_id);
                if (empty($area)) {
                    $msg = trans("$string_file.no_service_area");
                    return array('result' => '0', 'message' => $msg);
                }
            }
            $area_id = $area['id'];
            if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
                $permission_area_ids = explode(",", Auth::user()->role_areas);
                if (!in_array($area_id, $permission_area_ids)) {
                    $msg = trans("$string_file.permission_denied");
                    return array('result' => '0', 'message' => $msg);
                }
            }
            $segment = Segment::find($request->segment_id);
            $area = CountryArea::with(['VehicleType' => function ($query) use ($segment) {
                $query->where('segment_id', $segment->id);
            }])->with(['ServiceTypes' => function ($query) use ($segment) {
//                $query->where('segment_id',$segment->id);
            }])->find($area_id);

            $string_file = $this->getStringFile($area->merchant_id);
            $vehicle_types = "<option value=''>" . trans("$string_file.vehicle_type") . "</option>";
            if (!empty($area->VehicleType)) {
                foreach ($area->VehicleType->unique() as $vehicle) {
                    $vehicle_types .= "<option value='" . $vehicle->id . "'>" . $vehicle->VehicleTypeName . "</option>";
                }
            }
            if (!empty($request->user_id)) {
                $user = User::find($request->user_id);
                $user->country_area_id = $area_id;
                $user->save();
            }
            $services = "<option value=''>" . trans("$string_file.select") . "</option>";
            if (!empty($area->ServiceTypes)) {
                foreach ($area->ServiceTypes as $serviceType) {
                    if ($serviceType->id != 5) {
                        $services .= "<option value='" . $serviceType->id . "'>" . $serviceType->serviceName . "</option>";
                    }
                }
            }
            return array('result' => '1', 'vehicle_types' => $vehicle_types, 'area_id' => $area_id);
        } catch (\Exception $e) {
            return array('result' => '0', 'message' => $e->getMessage());
        }
    }

    public function checkManualOutstationDropArea(Request $request)
    {
        $home = new HomeController();
        $area = $home->CheckDropLocation($request);
        return $area;
    }

    public function getEstimatePrice($request){
        if (!empty($request->merchant_id)){
            $merchant_id = $request->merchant_id;
        }else{
            $merchant_id = get_merchant_id();
        }

        $merchant = new Merchant();
        $where = [['country_area_id', '=', $request->area], ['service_type_id', '=', $request->service], ['vehicle_type_id', '=', $request->vehicle_type], ['segment_id', '=', $request->segment_id]];
        if ($request->service == 2) {
            $where[] = ['service_package_id', '=', $request->package_id];
        } elseif ($request->service == 4 && isset($request->outstation_type) && $request->outstation_type == 1) {
            $where[] = ['outstation_type', '=', 1];
        } elseif ($request->service == 4 && isset($request->outstation_type) && $request->outstation_type == 2) {
            $where[] = ['service_package_id', '=', $request->package_id];
            $where[] = ['outstation_type', '=', 2];
        }
        $string_file = $this->getStringFile($merchant_id);
        $areas = \App\Models\CountryArea::select('id','country_id','merchant_id','timezone','AreaCoordinates')
                ->with(['ServiceTypes'=>function($q) use($merchant_id){
                }])
                ->find($request->area);
        // $merchantData = Merchant::find($merchant_id);
        // $this->getAreaByLatLong($request,$string_file, $merchantData);
        $price = PriceCard::where($where)->first();
        if (empty($price)) {
            return array('result' => 0, 'message' => trans("$string_file.no_price_card_for_area"));
        } else {
            switch ($price->pricing_type) {
                case "1":
                case "2":
                    $estimatePrice = new PriceController();
                    $time = sprintf("%0.2f", $request->ride_time / 60);
                    $fare = $estimatePrice->BillAmount([
                        'price_card_id' => $price->id,
                        'merchant_id' => $price->merchant_id,
                        'distance' => $request->distance,
                        'time' => $time,
                        'booking_id' => NULL,
                        'booking_time' => date('H:i'),
                        'units' => $request->distance_unit
                    ]);
                    $amount = $fare['amount'];
                    break;
                case "3":
                    $amount = trans('api.message62');
                    break;
            }
            $amount = $merchant->FinalAmountCal($amount, $merchant_id);
            return array('result' => 1, 'price_card_id' => $price->id, 'amount' => $amount,'iso'=> $areas->Country->isoCode);
        }
    }

    public function getNearestDriverForManual($request){
        $type = isset($request->type) ? $request->type : NULL;
        $segment_id = 1;
        if (isset($request->segment_id) && !empty($request->segment_id)) {
            $segment_id = $request->segment_id;
        }

        if(!empty($request->merchant_id)){
            $merchant_id = $request->merchant_id;
        }else{
            $merchant_id = get_merchant_id();
        }

        $drivers = Driver::GetNearestDriver([
            'merchant_id' => $merchant_id,
            'segment_id' => $segment_id,
            'taxi_company_id' => !empty($request->taxi_company_id) ? $request->taxi_company_id : NULL,
            'isManual' => true,
            'area' => $request->manual_area,
            'latitude' => $request->pickup_latitude,
            'longitude' => $request->pickup_longitude,
            'limit' => 10,
            'service_type' => $request->service,
            'vehicle_type' => $request->vehicle_type,
            'distance_unit' => $request->distance_unit,
            'distance'=>isset($request->radius) ? $request->radius : $request->ride_radius,
            'driver_ids' => !empty($request->driver_id) ? [$request->driver_id] : [],
            'type' => $type,
        ]);

        return $drivers;
    }

    public function storeBookingDeliveryDetails($booking, $data)
    {
        DB::beginTransaction();
        try {
            $delivery_booking_details = BookingDeliveryDetails::create([
                'booking_id' => $booking->id,
                'stop_no' => 1,
                'drop_latitude' => $booking->drop_latitude,
                'drop_longitude' => $booking->drop_longitude,
                'drop_location' => $booking->drop_location,
                'receiver_name' => $data->receiver_name,
                'receiver_phone' => $data->receiver_phone,
                'additional_notes' => $data->note,
                'opt_for_verify' => mt_rand(1111, 9999),
            ]);

            if (!empty($data->product_image_one)) {
                $delivery_booking_details->product_image_one = $this->uploadImage('product_image_one', 'product_image', $booking->merchant_id);
            }
            if (!empty($data->product_image_two)) {
                $delivery_booking_details->product_image_two = $this->uploadImage('product_image_two', 'product_image', $booking->merchant_id);
            }

            if (!empty($data->delivery_product)) {
                $product_data = $data->delivery_product_data;
                $final_product_data = NULL;
                foreach ($data->delivery_product as $key => $product) {
                    $final_product_data[] = ['id' => $key, 'quantity' => $product_data[$key]];
                }
                $delivery_booking_details->product_data = json_encode($final_product_data, true);

                if (!empty($final_product_data)) {
                    $productData = $final_product_data;
                    foreach ($productData as $product) {
                        DeliveryPackage::updateOrCreate(['booking_id' => $booking->id, 'booking_delivery_detail_id' => $delivery_booking_details->id, 'delivery_product_id' => $product['id']], ['delivery_product_id' => $product['id'], 'quantity' => $product['quantity']]);
                    }
                }
            }
            $delivery_booking_details->save();
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            // return $this->failedResponse($e->getMessage());
        }
        DB::commit();
    }

    public function placeManualDispatchBooking($request, $muliLocation = [], $merchant_id, $pricecardid, $drivers, $key, $request_type = null){
        $driver_id = null;
        if ($request_type != null && $request_type == 'single') {
            $driver_id = $drivers[0]->driver_id;
        }
        $from = $request->pickup_latitude . "," . $request->pickup_longitude;
        if (!empty($drivers)) {
            $current_latitude = $drivers[0]->current_latitude;
            $current_longitude = $drivers[0]->current_longitude;
            $driverLatLong = $current_latitude . "," . $current_longitude;
            $nearDriver = DistanceController::DistanceAndTime($from, $driverLatLong, $key);
            $estimate_driver_distance = $nearDriver['distance'];
            $estimate_driver_time = $nearDriver['time'];
        } else {
            $estimate_driver_distance = "";
            $estimate_driver_time = "";
        }
        if (!empty($muliLocation)) {
            $tot_loc = count($muliLocation);
            $new_array[$tot_loc]['drop_location'] = $request->drop_location;
            $new_array[$tot_loc]['drop_latitude'] = $request->drop_latitude;
            $new_array[$tot_loc]['drop_longitude'] = $request->drop_longitude;
            $static_image = array_merge($muliLocation, $new_array);
            $googleArray = GoogleController::GoogleStaticImageAndDistance($request->pickup_latitude, $request->pickup_longitude, $static_image, $key);
        } else {
            $drop_locationArray = [];
            if (!empty($request->drop_latitude)) {
                $drop_locationArray[] = array('drop_latitude' => $request->drop_latitude, 'drop_longitude' => $request->drop_longitude);
            }
            $googleArray = GoogleController::GoogleStaticImageAndDistance($request->pickup_latitude, $request->pickup_longitude, $drop_locationArray, $key);
        }

        // Generate bill details
        $estimatePrice = new PriceController();
        $outstanding_amount = Outstanding::where('user_id', $request->user_id)->sum('amount');
        $newBookingData = new BookingDataController();
        $to = "";
        if (!empty($drop_locationArray)) {
            $lastLocation = $newBookingData->wayPoints($drop_locationArray);
            $to = $lastLocation['last_location']['drop_latitude'] . "," . $lastLocation['last_location']['drop_longitude'];
        }
        $fare = $estimatePrice->BillAmount([
            'price_card_id' => $pricecardid,
            'merchant_id' => $merchant_id,
            'distance' => $googleArray['total_distance'],
            'time' => $googleArray['total_time_minutes'],
            'booking_id' => 0,
            'user_id' => $request->user_id,
            'booking_time' => date('H:i'),
            'outstanding_amount' => $outstanding_amount,
            'units' => CountryArea::find($request->manual_area)->Country['distance_unit'],
            'from' => $from,
            'to' => $to,
        ]);

        if (isset($request->promo_code) && $request->promo_code) {
            $promoCode = PromoCode::find($request->promo_code);
            if (!empty($promoCode)) {
                $code = $promoCode->promoCode;
                if ($promoCode->promo_code_value_type == 1) {
                    $promoDiscount = $promoCode->promo_code_value;
                } else {
                    $promoDiscount = ($fare['amount'] * $promoCode->promo_code_value) / 100;
                    $promoMaxAmount = $promoCode->promo_percentage_maximum_discount;
                    $promoDiscount = ($promoDiscount > $promoMaxAmount) ? $promoMaxAmount : $promoDiscount;
                }
                $request->estimate_fare = $fare['amount'] > $promoDiscount ? $fare['amount'] - $promoDiscount : '0.00';
                $parameter = array('subTotal' => $request->estimate_fare, 'price_card_id' => $pricecardid, 'booking_id' => 0, 'parameter' => $code, 'parameterType' => "PROMO CODE", 'amount' => (string)$promoDiscount, 'type' => "DEBIT", 'code' => $code, 'freeValue' => $promoCode->promo_code_value);
                array_push($fare['bill_details'], $parameter);
            }
        }

        $bill_details = json_encode($fare['bill_details'], true);

        $additional_notes = NULL;
        if (isset($request->note)) {
            $additional_notes = $request->note;
        }

//        if(!empty($muliLocation)){
//            $muliLocation = json_encode($muliLocation, true);
//        }
        $muliLocation = json_encode($muliLocation, true);
        $booking = Booking::create([
            'merchant_id' => $merchant_id,
            'user_id' => $request->user_id,
            'segment_id' => $request->segment_id,
            'corporate_id' => isset($request->corporate_id)?$request->corporate_id:NULL,
            'driver_id' => $driver_id,
            'platform' => isset($request->platform)?$request->platform:2, // web
            'country_area_id' => $request->manual_area,
            'service_type_id' => $request->service,
            'vehicle_type_id' => $request->vehicle_type,
            'price_card_id' => $pricecardid,
            'pickup_latitude' => $request->pickup_latitude,
            'pickup_longitude' => $request->pickup_longitude,
            'drop_latitude' => $request->drop_latitude,
            'drop_longitude' => $request->drop_longitude,
            'booking_type' => $request->booking_type,
            'map_image' => $googleArray['image'],
            'drop_location' => $request->drop_location,
            'additional_notes' => $additional_notes,
            'pickup_location' => $request->pickup_location,
            'estimate_distance' => $googleArray['total_distance_text'],
            'estimate_time' => $googleArray['total_time_text'],
            'payment_method_id' => $request->payment_method_id,
            'estimate_bill' => isset($request->estimate_fare)?$request->estimate_fare:0,
            'booking_timestamp' => strtotime("now"),
            'booking_status' => 1001,
            'service_package_id' => isset($request->package)?$request->package:NULL,
            'later_booking_date' => isset($request->date) ? date("Y-m-d", strtotime($request->date)) : NULL,
            'later_booking_time' => isset($request->time)?$request->time:NULL,
            'return_date' => isset($request->retrun_date)?$request->retrun_date:NULL,
            'return_time' => isset($request->retrun_time)?$request->retrun_time:NULL,
            'estimate_driver_distance' => isset($estimate_driver_distance)?$estimate_driver_distance:NULL,
            'estimate_driver_time' => isset($estimate_driver_time)?$estimate_driver_time:NULL,
             'waypoints' => isset($muliLocation)?$muliLocation:NULL,
            'bill_details' => isset($bill_details)?$bill_details:NULL,
            'price_for_ride' => isset($request->price_for_ride)?$request->price_for_ride:NULL,
            'price_for_ride_amount' => isset($request->price_for_ride_value)?$request->price_for_ride_value:NULL,
            'promo_code' => isset($request->promo_code)?$request->promo_code:NULL,
            'is_fake_booking' => (isset($request->is_fake_booking) && $request->is_fake_booking == "on") ? 1 : NULL,
        ]);
        return $booking;
    }
}
