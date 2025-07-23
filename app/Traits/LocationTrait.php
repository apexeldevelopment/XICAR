<?php
// app/Traits/Loggable.php
namespace App\Traits;

use App\Http\Controllers\Helper\BookingDataController;
use App\Models\Booking;
use App\Models\BookingConfiguration;
use App\Models\BookingRequestDriver;
use App\Models\ApplicationConfiguration;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

trait LocationTrait
{
    use BookingTrait;
    public function saveLocation($request, $driver, $string_file){
        DB::beginTransaction();
        try{
            $app_config = ApplicationConfiguration::select('working_with_redis')->where('merchant_id', '=', $driver->merchant_id)->first();

            if($app_config->working_with_redis == 1){
                Redis::geoadd("locations_for_driver", $request['longitude'], $request['latitude'], "driver:{$driver->merchant_id}:{$driver->id}");
                Redis::hmset("driver_location:{$driver->merchant_id}:{$driver->id}", [
                    'latitude' => $request['latitude'],
                    'longitude' => $request['longitude'],
                    'bearing' => $request['bearing'],
                    'accuracy' => $request['accuracy'],
                    'timestamp' => now()->toDateTimeString(),
                    'driver_id' => $driver->id,
                    'merchant_id' => $driver->merchant_id,
                ]);
            }
            else{
                $driver->current_latitude = $request['latitude'];
                $driver->current_longitude = $request['longitude'];
                $driver->bearing = $request['bearing'];
                $driver->accuracy = $request['accuracy'];
                $driver->last_location_update_time = date('Y-m-d H:i:s');
                $driver->save();
            }

            if ($driver->free_busy == 1) {
                $bookings = Booking::where([['driver_id', '=', $driver->id]])->whereIn('booking_status', [1002, 1004])->latest()->first();
                if (!empty($bookings)) {
                    $merchant = BookingConfiguration::select('google_key')->where('merchant_id', '=', $driver->merchant_id)->first();
                    // call polyline if driver app is in background and user want to see tracking path
                    if ($merchant->polyline == 1 && (isset($request['app_in_background']) && $request['app_in_background'] == "1")) {
                        $bookingData = new BookingDataController();
                        $from = $driver->current_latitude . "," . $driver->current_longitude;
                        $bookingData->MakePolyLine($from, $bookings->id, $merchant->google_key);
                    }
                    //                if ($bookings->booking_status == 1004 && (isset($request->app_in_background) && $request->app_in_background == "1")){
                    if ($bookings->booking_status == 1004) {
                        // Update Booking Coordinates
                        $coordinate['latitude'] = $request['latitude'];
                        $coordinate['longitude'] = $request['longitude'];
                        $coordinate['accuracy'] = $request['accuracy'];
                        $this->updateBookingCoordinates($coordinate, $bookings->id);
                    }
                }
            }
            $checkbookings = BookingRequestDriver::whereHas('Booking', function ($query) {
                $query->where([['booking_status', '=', 1001], ['booking_type', '=', 1]]);
            })->where([['driver_id', '=', $driver->id], ['request_status', '=', 1]])->where('created_at', '>=', \Carbon\Carbon::now()->subSeconds(61))->latest()->first();
        }
        catch(\Exception $e){
            DB::rollBack();
            \Log::channel('location_queue')->info(['error_message_from_trait'=>$e->getMessage()]);
        }
        DB::commit();
        if (!empty($checkbookings)) :
            $newbooking = new BookingDataController();
            $data = $newbooking->BookingNotification($checkbookings->Booking);
        endif;
    }




    public function getBoundingBox($latitude, $longitude, $radiusKm) {
        $earthRadius = 6371; // Radius of Earth in kilometers
        $latChange = $radiusKm / $earthRadius * (180 / M_PI);
        $lonChange = $radiusKm / $earthRadius * (180 / M_PI) / cos(deg2rad($latitude));

        $minLat = $latitude - $latChange;
        $maxLat = $latitude + $latChange;
        $minLon = $longitude - $lonChange;
        $maxLon = $longitude + $lonChange;

        return [
            'minLat' => round($minLat, 5),
            'maxLat' => round($maxLat, 5),
            'minLon' => round($minLon, 5),
            'maxLon' => round($maxLon, 5),
        ];
    }
    
    
    public static function AerialDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
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

    public function getlastdropLocations($latitude, $longitude, $radiusKm, $record_count, $user){
        if(empty($radiusKm)){
            $radiusKm = 5;
        }
        if(empty($record_count)){
            $record_count = 5;
        }
        $min_max_coordinated = $this->getBoundingBox($latitude, $longitude, $radiusKm);

        $roundedBookings = Booking::select('drop_longitude', 'drop_latitude', 'drop_location')
            ->whereBetween(DB::raw('CAST(ROUND(pickup_latitude, 5) AS DECIMAL(10, 5))'), [$min_max_coordinated['minLat'], $min_max_coordinated['maxLat']])
            ->whereBetween(DB::raw('CAST(ROUND(pickup_longitude, 5) AS DECIMAL(10, 5))'), [$min_max_coordinated['minLon'], $min_max_coordinated['maxLon']])
            ->where([['booking_status', '=', 1005], ['segment_id', '=', 1], ['user_id', '=', $user->id]])
            ->orderBy('id', 'desc')
            ->limit($record_count)
            ->get();

        $data =[];
        foreach ($roundedBookings as $booking) {
            $data[] = [
                "drop_longitude" => $booking->drop_longitude,
                "drop_latitude" => $booking->drop_latitude,
                "drop_location"=>$booking->drop_location,
            ];
        }
        return $data;
    }


    public function getSosRequestsDrivers($merchant_id, $latitude, $longitude, $driver_count, $radius, $existing_driver){
        $min_max_coordinated = $this->getBoundingBox($latitude, $longitude, $radius);
        $drivers = Driver::whereBetween(DB::raw('CAST(ROUND(current_latitude, 5) AS DECIMAL(10, 5))'), [$min_max_coordinated['minLat'], $min_max_coordinated['maxLat']])
            ->whereBetween(DB::raw('CAST(ROUND(current_longitude, 5) AS DECIMAL(10, 5))'), [$min_max_coordinated['minLon'], $min_max_coordinated['maxLon']])
            ->where([['merchant_id', '=', $merchant_id]])
            ->whereNull('driver_delete')
            ->whereNotNull('player_id')
            ->orderBy('id', 'desc')
            ->limit($driver_count)
            ->pluck('id')
            ->toArray();
        return $drivers;
    }
}
