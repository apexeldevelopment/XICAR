<?php

namespace App\Http\Controllers\Api\BusBooking;

use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Controllers\Services\BusServiceController;
use App\Http\Resources\BusBookingCheckoutResource;
use App\Http\Resources\BusBookingResource;
use App\Models\BusBooking;
use App\Models\BusBookingCheckout;
use App\Models\BusChatSupport;
use App\Models\BusPriceCard;
use App\Traits\AreaTrait;
use App\Traits\BannerTrait;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Models\ServiceType;
use App\Traits\BusBookingTrait;
use App\Models\BusBookingRating;
use App\Models\BusBookingMaster;
use App\Models\Bus;
use App\Traits\ImageTrait;

class BusBookingController extends Controller
{
    use AreaTrait, ApiResponseTrait, MerchantTrait, BannerTrait, BusBookingTrait,ImageTrait;

    public function homeScreen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            // call area trait to get id of area
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $this->getAreaByLatLong($request, $string_file);

            $booking_days_before = 0;
            if(isset($user->Merchant->BusConfiguration->booking_days_before) && !empty($user->Merchant->BusConfiguration->booking_days_before)){
                $booking_days_before = $user->Merchant->BusConfiguration->booking_days_before;
            }

             //Active Booking Counts
            $Active_bookings = BusBooking::where("user_id", $user->id)->get();
            $total_bookings_count = 0;

            foreach($Active_bookings as $data){
                if($data->status = 1 && $data->status = 2)
                {
                    $total_bookings_count++;
                }
            }
            $request->merge(['calling_from' => "home_screen"]);
            $data = $this->getHomeScreen($request, $string_file);
            $return_data['response_data'] = $data['data'];
            $return_data['service_type_id'] = $data['service_type_id'];
            $return_data['booking_days_before'] = $booking_days_before;
            $return_data['no_of_active_booking'] = $total_bookings_count;
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    function getHomeScreen($request, $string_file)
    {
        $user = $request->user('api');
        $merchant_id = $request->merchant_id;
        $segment_id = $request->segment_id;
        $calling_from = $request->calling_from;
        $response = [];

        $config = $user->Merchant->Configuration;

        if ($calling_from == "home_screen") {
            $holder_item = ["SERVICE_TYPES", "SEARCH", "BANNER", "BOOKING", "OFFERS"];
            if($user->Merchant->ApplicationConfiguration->bus_booking_package_delivery == 1)
                $holder_item[] = "PACKAGE_DELIVERY";
            if($user->Merchant->advertisement_module != 1){
                if (($key = array_search("BANNER", $holder_item)) !== false) {
                    unset($holder_item[$key]);
                }
            }
        }

        // Get Services
        $consider_service_types = true;
        $consider_service_type_id = isset($request->service_type_id) ? (int)$request->service_type_id : null;
        $service_type_cell = [];
        if(in_array("SERVICE_TYPES", $holder_item)){
            $arr_services = $this->getMerchantSegmentServices($merchant_id, 'api', 4, [$segment_id], $request->area, false, [], null, 'ALL');
            $arr_services = collect($arr_services);
            $service_type_cell['cell_title'] = "SERVICE_TYPE_CELL";
            $service_type_cell['cell_title_text'] = "Service Types";
            $services = [];
            foreach($arr_services as $service){
                if(empty($consider_service_type_id)){
                    $consider_service_type_id = $service['arr_services'][0]['id'];
                }
//                if(count($service['arr_services']) == 1){
//                    $consider_service_types = false;
//                }


                foreach($service['arr_services'] as $service){

                    if ($service["type"] == 1) {
                        $is_seat_selection = $user->Merchant->ApplicationConfiguration->intracity_seat_selection == 1;
                    } else{
                        $is_seat_selection = $user->Merchant->ApplicationConfiguration->intercity_seat_selection == 1;
                    }
                    $services[] = array(
                        "id" => $service['id'],
                        "segment_id" => $service['segment_id'],
                        "title" => $service['locale_service_name'],
                        "type" => $service['type'] == 1 ? "INTRACITY" : "INTERCITY",
                        "description" => $service['locale_service_description'],
                        "image" => !empty($service['service_icon']) ? $service['service_icon'] : get_image("1684758443_646b5fab96501_banners.jpg", 'banners', $merchant_id),
                        "is_selected" => $consider_service_type_id == $service["id"],
                        "is_seat_selection" => $is_seat_selection
                    );
                }
            }
            $service_type_cell['cell_contents'] = $services;
        }
        if(!empty($service_type_cell) && $consider_service_types){
            array_push($response, $service_type_cell);
        }


        if (in_array("PACKAGE_DELIVERY", $holder_item)) {
            $package_cell['cell_title'] = "PACKAGE_DELIVERY_CELL";
            $package_cell['cell_title_text'] = "package_delivery_cell";
            $package_cell['cell_contents'] = [
                [
                    "for_delivery"=> false,
                    "title"=> "normal_booking",
                    "name"=> trans("$string_file.normal"),
                ],
                [
                    "for_delivery"=> true,
                    "title"=> "package_booking",
                    "name"=> trans("$string_file.package_delivery"),
                ]
            ];
            $response[] = $package_cell;
        }

        // Get Search Bar
        if(in_array("SEARCH", $holder_item)){
            $search_bar['cell_title'] = "SEARCH_CELL";
            $search_bar['cell_title_text'] = "Search Bar";
            $search_bar['cell_contents'] = array(
                array("title" => "Search Bar")
            );
            array_push($response, $search_bar);
        }

        // Get banner list for holder
        if (in_array("BANNER", $holder_item)) {
            $request->merge(['merchant_id' => $merchant_id, 'home_screen' => NULL, 'segment_id' => $segment_id, 'banner_for' => 1]);
            $arr_banner = $this->getMerchantBanner($request);
            $banner_res['cell_title'] = 'BANNER_CELL';
            $banner_res['cell_contents'] = $arr_banner->map(function ($item, $key) use ($merchant_id) {
                return array(
                    'id' => $item->id,
                    'title' => $item->banner_name,
                    'image' => get_image($item->banner_images, 'banners', $merchant_id),
                    'action_type' => !empty($item->action_type) ? $item->action_type : "",
                    'redirect_url' => !empty($item->redirect_url) ? $item->redirect_url : "",
                );
            });
            array_push($response, $banner_res);
        }

        //  Get bookings
        if (in_array("BOOKING", $holder_item)) {
            $booking_cell['cell_title'] = "BOOKING_CELL";
            $booking_cell['cell_title_text'] = "Bookings";

                $booking_data = [];
                $bus_booking_status = Config::get("custom.bus_booking_status");
                $currency = $user->Country->isoCode;
                $bus_bookings = BusBooking::where("user_id", $user->id)->latest()->take(3)->get();
                foreach($bus_bookings as $booking){
                    $cordinates = json_decode($booking->coordinates,true);
                    array_push($booking_data, array(
                        "id" => $booking->id,
                        "booking_id" => "#$booking->id",
                        "status" => $bus_booking_status[$booking->status],
                        "from_address" => $booking->BusStop->Name,
                        "to_adderss" => $booking->EndBusStop->Name,
                        "amount" => $currency." ".$booking->total_amount,
                        "date" => $booking->BusBookingMaster->booking_date,
                        "from_latitude" => $cordinates['latitude'],
                        "from_longitude" => $cordinates['longitude'],
                        "to_latitude" => $cordinates['drop_latitude'],
                        "to_longitude" => $cordinates['drop_longitude']
                    ));

                }

            $booking_cell['cell_contents'] = $booking_data;
            array_push($response, $booking_cell);
        }

        //  Get offers
        if (in_array("OFFERS", $holder_item)) {
            $offer_cell['cell_title'] = "OFFER_CELL";
            $offer_cell['cell_title_text'] = "Offers";
            $offer_cell['cell_contents'] = array(
                array(
                    "id" => 12,
                    "title" => "Offer Test 1",
                    "image" => get_image("1697815191_65329a97d4969_banners.png", 'banners', $merchant_id),
                ),
                array(
                    "id" => 13,
                    "title" => "Offer Test 2",
                    "image" => get_image("1697815291_65329afbe45dc_banners.png", 'banners', $merchant_id),
                ),
                array(
                    "id" => 14,
                    "title" => "Offer Test 3",
                    "image" => get_image("1697815314_65329b12807c7_banners.png", 'banners', $merchant_id),
                )
            );
            array_push($response, $offer_cell);
        }

        return array("data" => $response, "service_type_id" => $consider_service_type_id);
    }

    public function checkout(Request $request)
    {

        //will remove after some time
        if(empty($request->booking_for)){
            $request = $request->merge(['booking_for' => "PASSENGER"]);
        }

        $validator = Validator::make($request->all(), [
            "segment_id" => 'required|exists:segments,id',
            "service_type_id" => 'required|exists:service_types,id',
            'bus_route_id' => 'required|integer|exists:bus_routes,id',
            'bus_id' => 'required|integer|exists:buses,id',
            'service_time_slot_detail_id' => 'required|integer|exists:service_time_slot_details,id',
            // 'email' => 'required|email',
            'phone_number' => 'required|numeric',
            "latitude" => 'required|string',
            "longitude" => 'required|string',
            "drop_latitude" => 'required|string',
            "drop_longitude" => 'required|string',

            'bus_stop_id' => 'required|integer|exists:bus_stops,id',
            'end_bus_stop_id' => 'required|integer|exists:bus_stops,id',

//            'number_of_rider' => 'required|integer',
//            'booking_for' => 'required|in:PASSENGER,PACKAGE',
            'number_of_rider' => 'required_if:booking_for,PASSENGER|integer',
            // 'total_amount' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $service_type = ServiceType::find($request->service_type_id);
        $request->merge(["service_types_type" => $service_type->type]);
        $validator = Validator::make($request->all(), [
            "booking_date" => 'required_if:service_types_type,2|date|date_format:Y-m-d|after_or_equal:'.date('Y-m-d'), // if service type is intercity
        ],['booking_date.required_if' => "Booking date is required"]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try {
            
            $user = $request->user('api');
            $string_file = $this->getStringFile($user->merchant_id);
            $this->getAreaByLatLong($request, $string_file, $user->Merchant);
            if(!empty($request->seat_details) && $request->booking_for == "PASSENGER")
            {
    
                foreach (json_decode($request->seat_details, true) as $item){
                    $validator = Validator::make($item, [
                        "bus_seat_detail_id" => "required|exists:bus_seat_details,id",
                        "name" => "required",
                        "age" => "required|integer",
                        "gender" => "required|integer", // 1 or 2
                        "seat_charges" => "required",
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        return $this->failedResponse(trans("$string_file.invalid_seat_details"." ".$errors[0]));
                    }
                }
            }
            
            switch ($request->service_types_type){
                case 1:
                    $checkout = BusServiceController::IntracityCheckout($request, $user, $string_file);
                    break;
                case 2:
                    $checkout = BusServiceController::IntercityCheckout($request, $user, $string_file);
                    break;
                default:
                    throw new \Exception(trans("$string_file.invalid_service_type"));
            }
        } catch (Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        $return_data = new BusBookingCheckoutResource($checkout);
        return $this->successResponse(trans("$string_file.success"), $return_data);
    }

    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "bus_booking_checkout_id" => 'required|exists:bus_booking_checkouts,id',
            "payment_method_id" => 'required|exists:payment_methods,id',
        ],[
            "payment_method_id.not_in" => "Payment Method not exist"
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $bus_booking_checkout = BusBookingCheckout::find($request->bus_booking_checkout_id);
        $service_type = ServiceType::find($bus_booking_checkout->service_type_id);
        $request->merge(["service_types_type" => $service_type->type]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $string_file = $this->getStringFile($user->merchant_id);
        DB::beginTransaction();
        try {
            if ($request->payment_method_id == 3) {
                $common_controller = new \App\Http\Controllers\Api\CommonController();
                $common_controller->checkUserWallet($user, $bus_booking_checkout->total_amount);
            }
            switch ($request->service_types_type){
                case 1:
    //                    $confirm_response = BusServiceController::IntracityConfirm($bus_booking_checkout, $request, $user, $string_file);
                    $busService_controller = New BusServiceController();
                    $confirm_response = $busService_controller->IntracityConfirm($bus_booking_checkout, $request, $user, $string_file);
                    // $confirm_response = BusServiceController::IntercityConfirm($bus_booking_checkout, $request, $user, $string_file);
                    break;
                case 2:
                    $busService_controller = New BusServiceController();
                    $confirm_response = $busService_controller->IntercityConfirm($bus_booking_checkout, $request, $user, $string_file);
                    // $confirm_response = BusServiceController::IntercityConfirm($bus_booking_checkout, $request, $user, $string_file);
                    break;
                default:
                    throw new \Exception(trans("$string_file.invalid_service_type"));
            }
            // After confirm the booking, Deleting the bus booking checkout
            $bus_booking_checkout->delete();
        } catch (Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"), $confirm_response);
    }


    public function getBookings(Request $request){
        $validator = Validator::make($request->all(), [
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'service_type_id' => ['required', 'integer', Rule::exists('service_types', 'id')->where(function ($query) {
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            // call area trait to get id of area
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $bus_bookings = BusBooking::where(["merchant_id" => $user->merchant_id, "user_id" => $user->id])
                // ->whereHas("BusBookingMaster", function($q) use($request){  //pls do not remove this comment (Booking are not service based now)
                //     $q->where(["segment_id" => $request->segment_id, "service_type_id" => $request->service_type_id]);
                // })
                ->where(function ($q) use($request){
                    if(isset($request->type) && $request->type == "ACTIVE"){
                        $q->whereIn("status", [1,2]);
                    }elseif(isset($request->type) && $request->type == "PAST"){
                        $q->whereIn("status", [3,4,5]);
                    }
                })
                ->latest()
                ->get();

            $currency = $user->Country->isoCode;
            $bus_booking_status = Config::get("custom.bus_booking_status");
            $bus_booking_arr = [];
            foreach($bus_bookings as $bus_booking){
//                $points_name = DB::table('bus_pickup_drop_point_bus_stop as bpdpbs')
//                            ->leftJoin('language_bus_stops as lbs', 'lbs.bus_stop_id', '=', 'bpdpbs.bus_stop_id')
//                            ->whereIn('bpdpbs.bus_pickup_drop_point_id', [$bus_booking->pickup_point_id, $bus_booking->drop_point_id])
//                            ->select('lbs.name')
//                            ->get();

//                $pickup_point =  isset($points_name[0]->name) ? $points_name[0]->name : "";
//                $drop_point   =  isset($points_name[1]->name) ? $points_name[1]->name : "";

                $bus_booking_arr[] = array(
                    "id" => $bus_booking->id,
                    "booking_id" => "#$bus_booking->id",
                    "status" => $bus_booking_status[$bus_booking->status],
                    "booking_status" => $bus_booking->status,
//                    "from_address" => $pickup_point,
//                    "to_adderss" => $drop_point,
                    "from_address" => $bus_booking->PickupPoint->address,
                    "to_adderss" => $bus_booking->DropPoint->address,
                    "amount" => $currency . " " . $bus_booking->total_amount,
                    "date" => $bus_booking->BusBookingMaster->booking_date,
                    "booked_seats" => $bus_booking->total_seats,
                    "bus_name" => $bus_booking->BusBookingMaster->Bus->busName($bus_booking->BusBookingMaster->Bus),
                    "booking_for"=>$bus_booking->booking_for,
                    "service_type"=>$bus_booking->BusBookingMaster->ServiceType->type,
                    "service_type_text"=>$bus_booking->BusBookingMaster->ServiceType->ServiceName($bus_booking->merchant_id),
                );
            }
            return $this->successResponse(trans("$string_file.data_found"), $bus_booking_arr);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }
    public function getBooking(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => ['required', 'integer', Rule::exists('bus_bookings', 'id')->where(function ($query) {
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $user = $request->user('api');

            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $bus_booking = BusBooking::where(["merchant_id" => $user->merchant_id, "user_id" => $user->id])
                ->with("BusBookingMaster")
                ->with("BusBookingDetail")
                ->with("BusBookingRating")
                ->find($request->bus_booking_id);

            $bus_booking_detail = new BusBookingResource($bus_booking);
            return $this->successResponse(trans("$string_file.data_found"), $bus_booking_detail);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function cancelBooking(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => ['required', 'integer', Rule::exists('bus_bookings', 'id')->where(function ($query) {
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $bus_booking = BusBooking::where(["merchant_id" => $user->merchant_id, "user_id" => $user->id])->find($request->bus_booking_id);
            $error = true;
            switch ($bus_booking->status){
                case 1:
                    $bus_booking->status = 4;
                    $bus_booking->save();

                    $price_card = BusPriceCard::where([
                        ["merchant_id","=",$user->merchant_id],
                        ["country_area_id","=",$user->merchant_id],
                        ["vehicle_type_id","=",$user->merchant_id],
                        ["bus_route_id","=",$user->merchant_id],
                    ])->first();

                    $amount_to_be_credit = $bus_booking->total_amount;
                    $amount_to_be_debit = 0;

                    if(!empty($price_card) && $price_card->cancel_charges == 1){
                        $amount_to_be_debit = $price_card->cancel_amount;
                    }

                    if($amount_to_be_credit > 0){
                        $paramArray = array(
                            'merchant_id' => $user->merchant_id,
                            'user_id' => $user->id,
                            'bus_booking_id' => $bus_booking->id,
                            'amount' => $amount_to_be_credit,
                            'narration' => 11,
                            'platform' => 1,
                            'payment_method' => 2,
                        );
                        WalletTransaction::UserWalletCredit($paramArray);
                    }

                    $message = trans("$string_file.success");
                    $error = false;
                    $this->notifyBusBookingDriver($bus_booking->BusBookingMaster, "USER_CANCEL", $bus_booking);
                    break;
                case 2:
                    $message = trans("$string_file.booking_started_cant_cancel");
                    break;
                case 3:
                    $message = trans("$string_file.booking_completed_cant_cancel");
                    break;
                default:
                    $message = trans("$string_file.booking_already_cancelled");
            }
            DB::commit();
            if($error){
                return $this->failedResponse($message);
            }else{
                return $this->successResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
    }

    public function busRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => ['required', 'integer', Rule::exists('bus_bookings', 'id')->where(function ($query) {
            }),],
            'rating' => ['required', 'numeric'],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $bus_booking = BusBooking::where(["merchant_id" => $user->merchant_id, "user_id" => $user->id])->find($request->bus_booking_id);
            $bus_rating = BusBookingRating::where('bus_booking_id',$request->bus_booking_id)->first();
            if(empty($bus_rating)){
                $bus_rating = New BusBookingRating();
                $bus_rating->bus_booking_id = $request->bus_booking_id;
                $bus_rating->bus_booking_master_id = $bus_booking->bus_booking_master_id;
            }
            $bus_rating->user_rating = $request->rating;
            $bus_rating->user_comment = isset($request->comments) ? $request->comments : "";
            $bus_rating->reasons = isset($request->reasons) ? $request->reasons : "";
            $rating_images = "";
            if($request->hasFile('rating_image_one') || $request->hasFile('rating_image_two') || $request->hasFile('rating_image_three') || $request->hasFile('rating_image_four')) {
                $images = [];
                $additional_req = array('compress' => false);
                if($request->hasFile('rating_image_one')) {
                    $rating_image_1 = $this->uploadImage($request->file('rating_image_one'), 'bus_booking_rating_image', $user->merchant_id, 'multiple', $additional_req);
                    $rating_images .= $rating_image_1.',';
                }
                if($request->hasFile('rating_image_two')) {
                    $rating_image_2 = $this->uploadImage($request->file('rating_image_two'), 'bus_booking_rating_image', $user->merchant_id, 'multiple', $additional_req);
                    $rating_images .= $rating_image_2.',';
                }
                if($request->hasFile('rating_image_three')) {
                    $rating_image_3 = $this->uploadImage($request->file('rating_image_three'), 'bus_booking_rating_image', $user->merchant_id, 'multiple', $additional_req);
                    $rating_images .= $rating_image_3.',';
                }
                if($request->hasFile('rating_image_four')) {
                    $rating_image_4 = $this->uploadImage($request->file('rating_image_four'), 'bus_booking_rating_image', $user->merchant_id, 'multiple', $additional_req);
                    $rating_images .= $rating_image_4.',';
                }
            }
            $rating_images = rtrim($rating_images,",");
            $bus_rating->rating_images = $rating_images;
            $bus_rating->save();

            $avg = BusBookingRating::whereHas('BusBookingMaster', function ($q) use ($bus_booking) {
                $q->where('bus_id', $bus_booking->BusBookingMaster->bus_id);
            })->avg('user_rating');
            $bus = $bus_booking->BusBookingMaster->Bus;
            $bus->rating = round($avg, 2);
            $bus->save();

            DB::commit();
            return $this->successResponse(trans("$string_file.success"));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
    }

    public function getBusReviews(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_id' => ['required', 'integer', Rule::exists('bus_booking_masters', 'bus_id')->where(function ($query) {
            }),],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            // $bus_booking = BusBooking::where(["merchant_id" => $user->merchant_id, "user_id" => $user->id])->find($request->bus_id);
            // $booking_ratings = BusBookingRating::select('bus_booking_ratings.*')->addSelect(DB::raw('COUNT(id) as total_rating'),DB::raw('AVG(user_rating) as avg_rating'))->whereHas('BusBooking', function ($q) use ($request, $user) {
            //     $q->where('merchant_id', $user->merchant_id)->where('bus_booking_master_id',$request->bus_id);
            // })->get();

            // $bus_booking_master = BusBookingMaster::find($request->bus_id);
            // $bus = $bus_booking_master->Bus;
            $bus = Bus::find($request->bus_id);
            $avg_rating = $bus->rating;
            $booking_ratings = BusBookingRating::whereHas('BusBookingMaster', function ($q) use ($request, $user) {
                $q->where('merchant_id', $user->merchant_id)->where('bus_id',$request->bus_id);
            })->get();

            // $review_count = BusBookingRating::where('bus_booking_master_id',$request->bus_id)->where('user_comment','!=',"")->count();
            $review_count = BusBookingRating::where('user_comment','!=',"")->whereHas('BusBookingMaster', function ($q) use ($request, $user) {
                $q->where('merchant_id', $user->merchant_id)->where('bus_id',$request->bus_id);
            })->count();
            // p($booking_ratings);
            if($booking_ratings->isEmpty()){
                return $this->failedResponse('No data found');
            }
            $total_ratings = count($booking_ratings);
            $data = [
                        'rating_count' => $total_ratings,
                        'reviews_count' => $review_count,
                        'avg_rating' => $avg_rating,
                    ];
            $five_star_count = 0;
            $four_star_count = 0;
            $three_star_count = 0;
            $two_star_count = 0;
            $one_star_count = 0;
            foreach($booking_ratings as $booking_rating){
                $images = [];
                if($booking_rating->user_rating=='1.0'){
                    $one_star_count += 1;
                }elseif($booking_rating->user_rating=='2.0' || $booking_rating->user_rating=='1.5'){
                    $two_star_count += 1;
                }elseif($booking_rating->user_rating=='3.0' || $booking_rating->user_rating=='2.5'){
                    $three_star_count += 1;
                }elseif($booking_rating->user_rating=='4.0' || $booking_rating->user_rating=='3.5'){
                    $four_star_count += 1;
                }elseif($booking_rating->user_rating=='5.0' || $booking_rating->user_rating=='4.5'){
                    $five_star_count += 1;
                }

                if(!empty($booking_rating->rating_images)){
                    $rating_imgs = explode(",",$booking_rating->rating_images);
                    foreach($rating_imgs as $rating_img){
                        $images[] = get_image($rating_img, 'bus_booking_rating_image', $user->merchant_id, true, true);
                    }
                }
                $data['reviews'][] = [
                                    'id' => $booking_rating->id,
                                    'user_name' => $booking_rating->BusBooking->User->first_name.' '.$booking_rating->BusBooking->User->last_name,
                                    'user_image' => get_image($booking_rating->BusBooking->User->UserProfileImage, 'user', $booking_rating->BusBooking->merchant_id, true, false),
                                    'date' => convertTimeToUSERzone($booking_rating->created_at, $booking_rating->BusBooking->CountryArea->timezone, $booking_rating->BusBooking->merchant_id),
                                    'rating' => !empty($booking_rating->user_rating)?$booking_rating->user_rating:"",
                                    'review' =>!empty($booking_rating->user_comment)?$booking_rating->user_comment:"",
                                    'images' => $images,
                                    'reasons' => !empty($booking_rating->reasons)?json_decode($booking_rating->reasons):[],
                                    'provider_comments' => !empty($booking_rating->provider_comments)?$booking_rating->provider_comments:'',
                                ];
            }
            // p($total_ratings);
            $data['rating_cell'] = [
                                            'five_star_percent' =>  (($five_star_count/$total_ratings) * 100),
                                            'four_star_percent' => (($four_star_count/$total_ratings) * 100),
                                            'three_star_percent' =>  (($three_star_count/$total_ratings) * 100),
                                            'two_star_percent' =>  (($two_star_count/$total_ratings) * 100),
                                            'one_star_percent' =>  (($one_star_count/$total_ratings) * 100),
                                        ];

            return $this->successResponse(trans("$string_file.success"), $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
    }


    public function applyRemovePromoCode(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            "bus_booking_checkout_id" => 'required|exists:bus_booking_checkouts,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $bus_booking_checkout = BusBookingCheckout::find($request->bus_booking_checkout_id);
            $service_type = ServiceType::find($bus_booking_checkout->service_type_id);
            $request->merge(["service_types_type" => $service_type->type]);
            $string_file = $this->getStringFile($user->merchant_id);

            $check_promo_code = $this->checkPromoCode($request);
            $discount_amount = 0;
            $promocode = "";
            $checkout = BusBookingCheckout::find($request->bus_booking_checkout_id);
            $message = 'Promo Code removed';
            if ($request->promo_code) {
                if (isset($check_promo_code['status']) && $check_promo_code['status'] == true) {
                    $promo_code = $check_promo_code['promo_code'];


                    if (!empty($promo_code->id)) {
                        if ($promo_code->promoCode == "FIRSTDELFREE") {
        //                    $discount_amount = $delivery_charge;
                        } else {
                            $promo_details = $promo_code;
                            // flat discount promo_code_value_type ==1
                            $discount_amount = $promo_details->promo_code_value;
                            if ($promo_details->promo_code_value_type == 2) {
                                // percentage discount promo_code_value_type == 2
                                $promoMaxAmount = $promo_details->promo_percentage_maximum_discount;
                                $discount_amount = ($checkout->total_amount * $discount_amount) / 100;
                                $discount_amount = !empty($promoMaxAmount) && ($discount_amount > $promoMaxAmount) ? $promoMaxAmount : $discount_amount;
                            }
                        }
                        $promocode = $promo_code->promoCode;
                    }
                    $checkout->discount_amount  = $discount_amount;
                    $checkout->promocode = $promocode;
                    $message = trans("common.promo_code_applied");
                }
            }


        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        // p($checkout);
        $return_data = new BusBookingCheckoutResource($checkout);
        return $this->successResponse($message, $return_data);
    }

    public function shippingPriceEstimate(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            "package_details" => 'required',
            "bus_route_id" => 'required',
            "bus_id" => 'required',
            'pickup_point_id'=>'required',
            'drop_point_id' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $string_file = $this->getStringFile(null, $user->Merchant);
        $bus = Bus::find($request->bus_id);

        $price_card = BusPriceCard::where(array(
            "merchant_id" => $user->merchant_id,
            "bus_route_id" => $request->bus_route_id,
            "vehicle_type_id" => $bus->vehicle_type_id,
            "country_area_id" => $bus->country_area_id,
        ))->first();

        $tax = $bus->CountryArea->tax;

        if(!isset($price_card)){
            return $this->failedResponse(trans("$string_file.price_card_not_found"));
        }
        return $this->successResponse(trans("$string_file.success"),$this->shippingPrice($request, $price_card, $tax));
    }


    public function busChatSupport(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile(null, $user->Merchant);
        $bus_chat_supports = BusChatSupport::where("merchant_id",$user->merchant_id)->get();
        $data = [];
        $types = config::get("custom.bus_chat_support_types");
        foreach ($bus_chat_supports as $support){
            $data[] = [
                "title"=>$support->getNameAttribute(),
                "subtitle"=> $support->getSubtitleAttribute(),
                "type" => $types[$support->type],
                "chat_support"=> $support->chat_support,
                "icon"=> get_image($support->icon, 'bus_chat_support', $support->merchant_id, true, false),
            ];
        }
        return $this->successResponse(trans("$string_file.success"),$data);
    }
}
