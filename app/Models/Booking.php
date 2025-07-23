<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $guarded = [];

    //protected $hidden = ['User'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->merchant_booking_id = $model->NewBookigId($model->merchant_id);
            return $model;
        });
    }

    public function NewBookigId($merchantID)
    {
        $booking = Booking::where([['merchant_id', '=', $merchantID]])->lockForUpdate()->orderBy('id', 'DESC')->first();
        if (!empty($booking)) {
            return $booking->merchant_booking_id + 1;
        } else {
            return 1;
        }
    }

    public function OutStanding()
    {
        return $this->hasOne(OutStanding::class);
    }
    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function SosRequest()
    {
        return $this->hasMany(SosRequest::class);
    }

    public function Chat()
    {
        return $this->hasMany(Chat::class);
    }

    public function DriverVehicle()
    {
        return $this->belongsTo(DriverVehicle::class);
    }

    public function ServicePackage()
    {
        return $this->belongsTo(ServicePackage::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function PaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }

    public function ServiceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function VehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function BookingRequestDriver()
    {
        return $this->hasMany(BookingRequestDriver::class);
    }

    public function PriceCard()
    {
        return $this->belongsTo(PriceCard::class);
    }

    public function CancelReason()
    {
        return $this->belongsTo(CancelReason::class);
    }

    public function BookingTransaction()
    {
        return $this->hasOne(BookingTransaction::class);
    }

    public function BookingDetail()
    {
        return $this->hasOne(BookingDetail::class);
    }
    public function BookingRating()
    {
        return $this->hasOne(BookingRating::class);
    }

    public function PromoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promo_code');
    }

    public static function VehicleDetail($booking)
    {
        //$booking = Booking::with('VehicleType', 'DriverVehicle')->find($booking_id);
        $newObj = array();
        if (!empty($booking)) {
            $image = isset($booking->VehicleType) ? $booking->VehicleType->vehicleTypeImage : $booking->DriverVehicle->VehicleType->vehicleTypeImage;
            $newObj['service'] = ($booking->ServiceType) ? $booking->ServiceType->ServiceName($booking->merchant_id) : $booking->ServiceType->serviceName;
            $newObj['vehicle'] = isset($booking->VehicleType) ? $booking->VehicleType->VehicleTypeName : $booking->DriverVehicle->VehicleType->VehicleTypeName;
            $newObj['vehicleTypeImage'] = get_image($image, 'vehicle', $booking->merchant_id);
            $newObj['vehicle_number'] = $booking->DriverVehicle ? $booking->DriverVehicle->vehicle_number : "";
            $newObj['vehicle_color'] = $booking->DriverVehicle ? $booking->DriverVehicle->vehicle_color : "";
            $newObj['vehicle_image'] = $booking->DriverVehicle && $booking->DriverVehicle->vehicle_image ? get_image($booking->DriverVehicle->vehicle_image, 'vehicle_document', $booking->merchant_id) : view_config_image("static-images/car-pool.png");
            $newObj['vehicle_make'] = $booking->DriverVehicle && $booking->DriverVehicle->VehicleMake ? $booking->DriverVehicle->VehicleMake->VehicleMakeName : "";
            $newObj['vehicle_model'] = $booking->DriverVehicle && $booking->DriverVehicle->VehicleModel ? $booking->DriverVehicle->VehicleModel->VehicleModelName : "";
            $newObj['vehicle_body_number'] = isset($booking->DriverVehicle) ? $booking->DriverVehicle->vehicle_body_number : "";
        }
        return $newObj;
    }

    public static function UpcomingBookings($area, $latitude, $longitude, $vehicle_type_id, $service_type_id, $distance, $driver_id = null, $driver_area_notification = 2, $ride_later_ride_allocation = 1, $date = NULL){
        $bookings = [];
        if (!empty($area) && !empty($latitude) && !empty($longitude) && !empty($vehicle_type_id) && !empty($service_type_id) && !empty($distance) && !empty($driver_id)) {
            $bookings_query = Booking::where([['vehicle_type_id', '=', $vehicle_type_id], ['booking_status', '=', 1001], ['booking_type', '=', 2]]);
            if($ride_later_ride_allocation != 2){
                $bookings_query->select(DB::raw('*,( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( pickup_latitude ) ) * cos( radians( pickup_longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( pickup_latitude ) ) ) ) AS distance'))
                    ->having('distance', '<', $distance);
            }
            //                ->where(DB::raw('CONCAT_WS(" ", later_booking_date, later_booking_time)'), '>=', date('Y-m-d H:i'))
            //                ->where([['later_booking_date', '>=', date('Y-m-d')], ['country_area_id', '=', $area], ['vehicle_type_id', '=', $vehicle_type_id], ['booking_status', '=', 1001], ['booking_type', '=', 2]])
            $bookings_query->where(function ($q) use ($driver_id, $driver_area_notification, $area) {
                $q->where('driver_id', null);
                if (!empty($driver_id)) {
                    $q->orwhere('driver_id', $driver_id);
                }
                if ($driver_area_notification == 2) {
                    $q->where('country_area_id', $area);
                }
            })
            ->where('booking_status', 1001)
            ->whereIn("service_type_id", $service_type_id)
            ->whereNotIn('id', function ($query) use ($driver_id) {
                $query->select('booking_id')->where('driver_id', $driver_id)->from('driver_cancel_bookings');
            });
            if (!empty($date)){
                $bookings_query->where('later_booking_date', $date);
            }
            $bookings = $bookings_query->get();
        }
        return $bookings;
    }

    public static function InDriveBookings($area, $latitude, $longitude, $vehicle_type_id, $service_type_id, $distance, $driver_id = null, $driver_area_notification = 2, $ride_later_ride_allocation = 1, $date = NULL){
        $bookings = [];
        if (!empty($area) && !empty($latitude) && !empty($longitude) && !empty($vehicle_type_id) && !empty($service_type_id) && !empty($distance) && !empty($driver_id)) {
            $bookings_query = Booking::where([['vehicle_type_id', '=', $vehicle_type_id], ['booking_status', '=', 1000]]);
            if($ride_later_ride_allocation != 2){
                $bookings_query->select(DB::raw('*,( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( pickup_latitude ) ) * cos( radians( pickup_longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( pickup_latitude ) ) ) ) AS distance'))
                    ->having('distance', '<', $distance);
            }
            //                ->where(DB::raw('CONCAT_WS(" ", later_booking_date, later_booking_time)'), '>=', date('Y-m-d H:i'))
            //                ->where([['later_booking_date', '>=', date('Y-m-d')], ['country_area_id', '=', $area], ['vehicle_type_id', '=', $vehicle_type_id], ['booking_status', '=', 1001], ['booking_type', '=', 2]])
            $bookings_query->where(function ($q) use ($driver_id, $driver_area_notification, $area) {
                $q->where('driver_id', null);
                if (!empty($driver_id)) {
                    $q->orwhere('driver_id', $driver_id);
                }
                if ($driver_area_notification == 2) {
                    $q->where('country_area_id', $area);
                }
            })
                ->where('booking_status', 1000)
                ->whereIn("service_type_id", $service_type_id)
                ->whereNotIn('id', function ($query) use ($driver_id) {
                    $query->select('booking_id')->where('driver_id', $driver_id)->from('driver_cancel_bookings');
                });
            if (!empty($date)){
                $bookings_query->where('later_booking_date', $date);
            }
            $bookings = $bookings_query->orderBy("created_at","DESC")->get();
        }
        return $bookings;
    }

    // Code merged by @Amba

    public function packages()
    {
        return $this->hasMany(BookingPackage::class);
    }

    public function BookingCoordinate()
    {
        return $this->hasOne(BookingCoordinate::class);
    }
    public function OneSignalLog()
    {
        return $this->hasOne(OneSignalLog::class);
    }
    public function FamilyMember()
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function DeliveryPackage()
    {
        return $this->hasMany(DeliveryPackage::class);
    }

    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function UserCard()
    {
        return $this->belongsTo(UserCard::class, 'card_id');
    }

    public function getBooking($booking_id)
    {
        $booking = Booking::select('id', 'user_id', 'is_in_drive', 'offer_amount', 'final_amount_paid', 'family_member_id', 'booking_status_history', 'estimate_driver_distance', 'estimate_distance', 'travel_distance', 'merchant_booking_id', 'booking_status', 'vehicle_type_id', 'driver_vehicle_id', 'price_card_id', 'ride_otp', 'ride_otp_verify', 'total_drop_location', 'booking_type', 'ploy_points', 'payment_method_id', 'vehicle_type_id', 'driver_id', 'merchant_id', 'segment_id', 'pickup_location', 'country_area_id', 'driver_id', 'user_id', 'pickup_latitude', 'pickup_longitude', 'service_type_id', 'additional_notes', 'family_member_id', 'waypoints', 'drop_latitude', 'drop_longitude', 'drop_location', 'payment_status', 'estimate_bill', 'travel_time', 'country_area_id', 'booking_closure', 'later_booking_date', 'later_booking_time', 'unique_id', 'map_image', 'platform', 'master_booking_id', 'additional_information','bags_weight_kg','no_of_bags','no_of_pats','no_of_person','no_of_children','gender','wheel_chair_enable','baby_seat_enable','additional_user_details','created_at')
            ->with(['User' => function ($query) {
                $query->select('id', 'country_id', 'merchant_id', 'signup_status','first_name', 'last_name', 'UserPhone', 'email', 'rating', 'UserProfileImage', 'rating');
            }])
            ->with(['Driver' => function ($query) {
                $query->select('id', 'first_name', 'current_latitude', 'country_area_id', 'current_longitude', 'last_location_update_time', 'driver_gender', 'last_name', 'email', 'phoneNumber', 'profile_image', 'rating', 'ats_id', 'calling_button', 'tracking_freeze_enable','kin_details');
            }])
            ->with(['PaymentMethod' => function ($query) {
                $query->select('id', 'payment_method', 'payment_icon');
            }])
            ->with(['VehicleType' => function ($query) {
                $query->select('id', 'vehicleTypeImage', 'vehicleTypeMapImage', 'rating');
            }])
            ->with(['ServiceType' => function ($query) {
                $query->select('id', 'serviceName', 'type');
            }])
            ->orderBy('created_at', 'DESC')
            ->find($booking_id);
        return $booking;
    }

    public function getDriverOngoingBookings($request)
    {
        $driver_id = $request->user('api-driver')->id;
        $bookings = Booking::select('id', 'merchant_booking_id', 'booking_timestamp', 'estimate_bill', 'booking_status', 'booking_type', 'payment_method_id', 'vehicle_type_id', 'driver_id', 'merchant_id', 'segment_id', 'pickup_location', 'user_id', 'pickup_latitude', 'pickup_longitude', 'service_type_id', 'drop_latitude', 'drop_longitude', 'drop_location')
            ->with(['User' => function ($query) {
                $query->select('id', 'merchant_id', 'country_id', 'first_name', 'last_name', 'UserPhone', 'email', 'rating', 'UserProfileImage');
            }])
            ->with(['PaymentMethod' => function ($query) {
                $query->select('id', 'payment_method', 'payment_icon');
            }])
            ->with(['ServiceType' => function ($query) {
                $query->select('id', 'serviceName');
            }])
            ->whereIn('booking_status', array(1002, 1003, 1004, 1005))
            ->where('booking_closure', NULL)
            ->where('driver_id', $driver_id)
            ->orderBy('created_at', 'DESC')
            ->groupBy('master_booking_id')
            ->get();
        return $bookings;
    }

    public function getDriverBooking($request)
    {
        $driver_id = $request->user('api-driver')->id;
        $request_type = $request->request_type;
        $query = Booking::select('id', 'driver_id', 'user_id', 'later_booking_date', 'later_booking_time', 'drop_latitude', 'drop_longitude', 'drop_location', 'pickup_latitude', 'pickup_longitude', 'pickup_location', 'merchant_booking_id', 'merchant_id', 'segment_id', 'service_package_id', 'service_type_id', 'vehicle_type_id', 'driver_vehicle_id', 'booking_timestamp', 'booking_status')
            ->with('BookingDetail')
            ->where([['driver_id', '=', $driver_id]])
            ->orderBy('created_at', 'DESC');
        if ($request_type == "PAST") {
            $query->where(function ($q) {
                $q->whereIn('booking_status', array(1006, 1007, 1008, 1018));
                $q->orWhere([['booking_status', '=', 1005], ['booking_closure', '=', 1]]);
            });
            if (isset($request->segment_id)) {
                $query->whereHas('Segment', function ($q) use ($request) {
                    $q->where('id', $request->segment_id);
                });
            }
            $bookings = $query->latest()->paginate(10);
        } elseif ($request_type == "ACTIVE") {
            $query->whereIn('booking_status', array(1002, 1003, 1004, 1005));
            $query->where('booking_closure', NULL);
            $query->orderBy('created_at', 'DESC');
            $bookings = $query->get();
        } elseif ($request_type == "SCHEDULE") {
            $query->whereIn('booking_status', array(1012));
            $query->where('booking_type', 2);
            $bookings = $query->latest()->paginate(10);
        }

        return $bookings;
    }

    public function getUserBooking($request)
    {
        $user_id = $request->user('api')->id;
        $segment_id = $request->segment_id;
        $request_type = $request->request_type;
        $query = Booking::select('id', 'map_image', 'is_in_drive', 'offer_amount', 'estimate_bill', 'country_area_id', 'driver_id', 'user_id', 'later_booking_date', 'later_booking_time', 'drop_latitude', 'drop_longitude', 'drop_location', 'pickup_latitude', 'pickup_longitude', 'pickup_location', 'merchant_booking_id', 'merchant_id', 'segment_id', 'service_package_id', 'service_type_id', 'vehicle_type_id', 'driver_vehicle_id', 'booking_timestamp', 'booking_status', 'payment_method_id', 'created_at')
            ->with('BookingDetail')
            ->where([['user_id', '=', $user_id], ['segment_id', '=', $segment_id]])
            ->orderBy('created_at', 'DESC');
        if ($request_type == "PAST") {
            $query->where(function ($q) {
                $q->whereIn('booking_status', array(1006, 1007, 1008, 1018));
                $q->orWhere(function ($qq) {
                    $qq->where('booking_status', array(1005));
                    $qq->where('booking_closure', 1);
                });
            });
        } elseif ($request_type == "ACTIVE") {
            $query->where(function ($q) {
                $q->whereIn('booking_status', array(1002, 1003, 1004, 1005, 1001, 1012));
                $q->where('booking_closure', NULL);
            });
            // $bookings = $query->get();
        }elseif ($request_type == "IN_DRIVE"){
            $query->where('booking_status', 1000);
        }
        $query->whereNull("is_fake_booking");
        $bookings = $query->latest()->paginate(10);
        //         elseif($request_type == "SCHEDULE")
        //         {
        // //            1002,
        //             $query->whereIn('booking_status', array(1012));
        //             $query->where('booking_type', 2);
        //             $bookings = $query->latest()->paginate(10);
        //         }
        return $bookings;
    }

    public function getBookingBasicData($request)
    {
        $booking = Booking::select('id', 'merchant_id', 'segment_id', 'user_id', 'driver_id', 'country_area_id', 'booking_status', 'payment_method_id','vehicle_type_id')
            ->with(['Segment' => function ($q) {
                $q->addSelect('id', 'slag', 'name', 'icon');
                $q->with(['Merchant' => function ($q) { }]);
            }])->with('VehicleType')
            ->find($request->booking_id);
        return $booking;
    }

    //    public function BookingDeliveryDetails()
    //    {
    //        return $this->hasMany(BookingDeliveryDetails::class);
    //    }



    // Note : it has hasMany relation
    //it has many relation
    public function BookingDeliveryDetail()
    {
        return $this->hasMany(BookingDeliveryDetails::class, 'booking_id')->orderBy('stop_no');
    }

    // it get single stop
    public function BookingDeliveryDetails()
    {
        return $this->hasOne(BookingDeliveryDetails::class, 'booking_id')->orderBy('stop_no');
    }

    // Get all Pool Bookings
    public function getDriverPoolBookings($request)
    {
        $driver_id = $request->user('api-driver')->id;
        $bookings = Booking::select('id', 'merchant_booking_id', 'booking_timestamp', 'estimate_bill', 'booking_status', 'booking_type', 'payment_method_id', 'vehicle_type_id', 'driver_id', 'merchant_id', 'segment_id', 'pickup_location', 'user_id', 'pickup_latitude', 'pickup_longitude', 'service_type_id', 'drop_latitude', 'drop_longitude', 'drop_location','pool_history')
            // select('id','merchant_booking_id','booking_status','merchant_id','user_id','pickup_latitude','pickup_longitude','drop_latitude','drop_longitude','drop_location')
            ->with(['User' => function ($query) {
                $query->select('id', 'merchant_id', 'country_id', 'first_name', 'last_name', 'UserPhone', 'email', 'rating', 'UserProfileImage');
            }])
            // ->with(['PaymentMethod' => function ($query) {
            //     $query->select('id', 'payment_method', 'payment_icon');
            // }])
            // ->with(['ServiceType' => function ($query) {
            //     $query->select('id', 'serviceName');
            // }])
            // ->whereIn('booking_status', array(1002, 1003, 1004,1005))
            // ->where('booking_closure',NULL)
            ->where('driver_id', $driver_id)
            ->orderBy('created_at', 'ASC')
            ->where('master_booking_id', $request->master_booking_id)
            ->whereIn('booking_status', array(1002, 1003, 1004, 1005))
            ->get();
        return $bookings;
    }

    public function BookingBiddingDriver(){
        return $this->hasMany(BookingBiddingDriver::class);
    }

    public function Hotel(){
        return $this->hasMany(Hotel::class);
    }

    public function SosRequests()
    {
        return $this->hasMany(AllSosRequest::class);
    }

   public function googleMapImage()
    {
        // $pickupLat = $this->pickup_latitude;
        // $pickupLng = $this->pickup_longitude;
        // $dropLat   = $this->drop_latitude;
        // $dropLng   = $this->drop_longitude;
        $apiKey    = $this->Merchant->BookingConfiguration->google_key;

        // $directionsUrl = "https://maps.googleapis.com/maps/api/directions/json?" .
        //     "origin={$pickupLat},{$pickupLng}" .
        //     "&destination={$dropLat},{$dropLng}" .
        //     "&mode=driving" .
        //     "&key={$apiKey}";

        // $response = file_get_contents($directionsUrl);
        // $data = json_decode($response, true);

        // $encodedPolyline = '';
        // if (!empty($data['routes'][0]['overview_polyline']['points'])) {
        //     $encodedPolyline = $data['routes'][0]['overview_polyline']['points'];
        // }

        // $center = "{$pickupLat},{$pickupLng}";

        // return "https://maps.googleapis.com/maps/api/staticmap?" .
        //     "center={$center}" .
        //     "&zoom=15" .
        //     "&size=1000x1000" .
        //     "&maptype=roadmap" .
        //     "&markers=color:green|label:P|{$pickupLat},{$pickupLng}" .
        //     "&markers=color:red|label:D|{$dropLat},{$dropLng}" .
        //     "&path=enc:{$encodedPolyline}" .
        //     "&key={$apiKey}";

        return $this->map_image.'&size=400x400&key='.$apiKey;
    }

}
