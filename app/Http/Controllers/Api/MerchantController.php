<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 17/11/23
 * Time: 4:16 PM
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Traits\BookingTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MerchantController extends Controller
{
    use ApiResponseTrait, BookingTrait;

    public function getAuthToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grant_type' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            if ($request->grant_type == "KEYS") {
                $timestamp = time() . "_" . $request->merchant_id;
                $encryption_key = \Illuminate\Support\Facades\Crypt::encrypt($timestamp);
                return $this->successResponse("Success", array(
                    "valid_for" => "15 Minutes",
                    "token" => $encryption_key
                ));
            } else {
                return $this->failedResponse("Invalid grant type");
            }
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function getBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $token = \Illuminate\Support\Facades\Crypt::decrypt($request->token);
            $token = explode("_", $token);
            $add_minutes = 15;
            $new_time = $token[0] + 60 * $add_minutes;
            $last_datetime = date('Y-m-d H:i:s', $new_time);
            $current_time = date("Y-m-d H:i:s");

            if ($current_time > $last_datetime) {
                throw new \Exception("Token Expired");
            } elseif ($token[1] != $request->merchant_id) {
                throw new \Exception("Invalid Merchant");
            }
            $request->merge(['request_from' => "COMPLETE", 'url_slug' => "TAXI"]);
            $bookings = $this->getBookings($request, false);
            $booking_arr = [];
            foreach ($bookings as $booking) {
                if($booking->CountryArea->Country->isoCode == "TZS"){
                    $docNumber = "";
                    foreach($booking->Driver->DriverDocument as $doc){
                        if($doc->Document->LanguageSingle->locale == "en" && $doc->Document->LanguageSingle->documentname == "Driving license"){
                            $docNumber = $doc->document_number;
                        }
                    }
                    $distance = explode(" ",$booking->travel_distance);
                    $distance = isset($distance[0]) ? $distance[0]*1000 : 0;
                    $endLatitude = number_format($booking->BookingDetail->end_latitude, 8, '.', '');
                    $endLongitude = number_format($booking->BookingDetail->end_longitude, 8, '.', '');
                    array_push($booking_arr, array(
    //                    "id" => $booking->id,
    //                    "booking_id" => $booking->merchant_booking_id,
    //                    "pickup_location" => $booking->pickup_location,
    //                    "drop_location" => $booking->drop_location,
    //                    "user_name" => $booking->User->UserName,
    //                    "user_phone" => $booking->User->UserPhone,
    //                    "driver_name" => $booking->Driver->fullName,
    //                    "driver_phone" => $booking->Driver->phoneNumber,
    //                    "booking_charges" => $booking->CountryArea->Country->isoCode . $booking->final_amount_paid

                        "id" => $booking->id,
                        "trip_id" => (string) $booking->merchant_booking_id,
                        "origin_coordinates" => $booking->BookingDetail->start_latitude.",".$booking->BookingDetail->start_longitude,
                        "end_coordinates" => $endLatitude . "," . $endLongitude,
                        "start_time" => date('Y-m-d H:i:s', $booking->BookingDetail->start_timestamp),
                        "end_time" =>date('Y-m-d H:i:s', $booking->BookingDetail->end_timestamp),
                        // "booking_charges" => $booking->CountryArea->Country->isoCode . $booking->final_amount_paid,
                        "booking_charges" => $booking->final_amount_paid,
                        "trip_distance" => (integer)$distance,
                        "rating" => $booking->BookingRating->user_rating_points,
                        "driver_earning" => $booking->driver_cut,
                        // "driver_earning" => $booking->driver_cut,
                        "driver_license_no" => $docNumber,
                        "driver_vehicle_no" => $booking->DriverVehicle->vehicle_number,
                    ));
                }
            }
            return $this->successResponse("Success", $booking_arr);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }
}
