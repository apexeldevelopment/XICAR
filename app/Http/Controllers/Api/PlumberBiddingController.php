<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 18/8/23
 * Time: 3:40 PM
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\FindDriverController;
use App\Http\Controllers\Helper\GoogleController;
use App\Models\BookingRating;
use App\Models\CancelReason;
use App\Models\Driver;
use App\Models\HandymanBiddingOrder;
use App\Models\HandymanOrder;
use App\Models\HandymanOrderDetail;
use App\Models\Onesignal;
use App\Models\SegmentPriceCard;
use App\Models\ServiceType;
use App\Traits\ApiResponseTrait;
use App\Traits\AreaTrait;
use App\Traits\HandymanTrait;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;

class PlumberBiddingController extends Controller
{
    use ApiResponseTrait, AreaTrait, MerchantTrait, HandymanTrait, ImageTrait;

    function getOrders(Request $request){
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL,$user->Merchant);
        $request_fields = [
            'type' => ['required','in:ACTIVE,ALL'],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $arr_orders = HandymanBiddingOrder::where([['user_id', '=', $user->id], ['merchant_id', '=', $user->merchant_id]])->where(function ($q) use ($request) {
                if (!empty($request->type)) {
                    if($request->type == "ACTIVE"){
                        $q->where('order_status', 1);
                    }elseif($request->type == "ALL"){
                        $q->whereIn('order_status', [2, 3]);
                    }
                }
            })->orderBy('id','DESC')->get();

        $time_format =  $user->Merchant->Configuration->time_format;
        $currency = $user->Country->isoCode;

        $arr_orders = $arr_orders->map(function ($item, $key)use ($user,$currency,$time_format,$string_file){
            $time = "";
            if(isset($item->ServiceTimeSlotDetail))
            {
                $start = strtotime($item->ServiceTimeSlotDetail->from_time);
                $start = $time_format == 2  ? date("H:i",$start) : date("h:i a",$start);
                $end = strtotime($item->ServiceTimeSlotDetail->to_time);
                $end =  $time_format == 2  ? date("H:i",$end) : date("h:i a",$end);
                $time = $start."-".$end;
            }

            $bidding_count = $item->ActionedDrivers->count();
            $lowest_bidding_amount = !empty($item->ActionedDrivers[0]) ? $item->ActionedDrivers[0]->pivot->amount : "0.00";

            $status_text = $this->getStatusString($item->order_status, $string_file);
            if($item->Driver->count() == $item->RejectedDriver->count()){
                $status_text = trans("$string_file.rejected_by_providers");
            }
            return array(
                'order_id' => $item->id,
                'segment_id' => $item->segment_id,
                'segment_name' => !empty($item->Segment->Name($item->merchant_id)) ? $item->Segment->Name($item->merchant_id) : $item->Segment->slag,
                'final_amount_paid' =>$item->final_amount_paid,
                'user_offer_price' =>$item->user_offer_price,
                'currency' =>$currency,
                'created_at' => date("Y-m-d H:i:s", strtotime($item->created_at)),
                'total_services' => $item->quantity,
                'order_status' => $status_text,
                'booking_date' => date('d M y',strtotime($item->booking_date)),
                'slot_time_text' =>$time,
                'service_type' => $this->getServiceTypes(json_decode($item->ordered_services), $item->merchant_id),
                'description' => !empty($item->description) ? $item->description : "",
                'bidding_count' => $bidding_count,
                'status' => $item->order_status,
                'payment_method' => $item->PaymentMethod->MethodName($item->merchant_id),
                "lowest_bidding_amount" => $lowest_bidding_amount,
            );
        });
        return $this->successResponse(trans("$string_file.data_found"), $arr_orders);
    }

    function getOrderDetail(Request $request){
        $user = $request->user('api');
        $currency = $user->Country->isoCode;
        $string_file = $this->getStringFile($user->merchant_id);
        $request_fields = [
            'handyman_bidding_order_id' => ['required', 'integer', Rule::exists('handyman_bidding_orders', 'id')->where(function ($query) {
            }),],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $item = HandymanBiddingOrder::where('user_id', $user->id)->where('id', $request->handyman_bidding_order_id)->first();

//        $time = "";
//        $time_format =  $user->Merchant->Configuration->time_format;
//        $cancel_reasons = CancelReason::Reason($item->merchant_id, 1,$item->segment_id);
        $order_status = $this->getStatusString($item->order_status, $string_file);
        $service_type = $this->getServiceTypes(json_decode($item->ordered_services), $item->merchant_id);

        $order = $this->getHandymanBiddingOrderDetail($item, $order_status, $service_type, $string_file, $request->driver_id);

//        if(isset($item->ServiceTimeSlotDetail))
//        {
//            $start = strtotime($item->ServiceTimeSlotDetail->from_time);
//            $start = $time_format == 2  ? date("H:i",$start) : date("h:i a",$start);
//            $end = strtotime($item->ServiceTimeSlotDetail->to_time);
//            $end =  $time_format == 2  ? date("H:i",$end) : date("h:i a",$end);
//            $time = $start."-".$end;
//        }
//
//        if ($item->order_status == 2){
//            $action_drivers = $item->AcceptedDriver;
//        }else{
//            $action_drivers = $item->ActionedDrivers;
//        }
//        $actioned_drivers = [];
//        $countCompletedOrder = 0;
//        foreach($action_drivers as $driver){
//            $status = "";
//            if($driver->pivot->status == 1){
//                $status = trans("$string_file.accepted");
//            }elseif($driver->pivot->status == 2){
//                $status = trans("$string_file.counter_offer");
//            }elseif($driver->pivot->status == 4){
//                $status = trans("$string_file.bidding_completed");
//            }
//            $rating = BookingRating::whereHas('HandymanOrder', function ($q) use ($driver){
//                $q->where('driver_id', $driver->id);
//            })
//                ->where('handyman_order_id','!=',NULL)
//                ->avg('user_rating_points');
//            $rating = isset($rating) ? number_format($rating,2) : $rating;
//            $completedOrder = HandymanOrder::where(['driver_id'=> $driver->id, 'order_status' => 7])->count();
//
//            if(isset($item->handyman_order_id)){
//                $countCompletedOrder = HandymanOrder::where(['id'=>$item->handyman_order_id,'is_order_completed'=> 1])->get()->count();
//            }
//
//            array_push($actioned_drivers, array(
//                "driver_id" => $driver->id,
//                "full_name" => $driver->fullName,
//                "description" => $driver->pivot->description,
//                "status" => $status,
//                "show_counter_offer" => $driver->pivot->status == 2 || $driver->pivot->status == 4 ? true : false,
//                "counter_offer" => $driver->pivot->status == 2 || $driver->pivot->status == 4 ? $driver->pivot->amount : "",
//                "rating" => $rating,
//                "job_done"=> $completedOrder,
//                "image" => get_image($driver->profile_image,'driver',$driver->merchant_id),
//            ));
//        }
//
//        $upload_images = [];
//        if(!empty($item->upload_images)){
//            foreach(json_decode($item->upload_images, true) as $image){
//                array_push($upload_images, get_image($image, "booking_images", $item->merchant_id));
//            }
//        }
//
//        $order = array(
//            'order_id' => $item->id,
//            'segment_id' => $item->segment_id,
//            'segment_name' => !empty($item->Segment->Name($item->merchant_id)) ? $item->Segment->Name($item->merchant_id) : $item->Segment->slag,
//            'drop_location' => $item->drop_location,
//            'currency' => $currency,
//            'created_at' => date("Y-m-d H:i:s", strtotime($item->created_at)),
//            'total_services' => $item->quantity,
//            'order_status' => $order_status,
//            'status_slug' => $item->order_status == 2 ? 'COMPLETE' : 'PENDING',
//            'handyman_order_id' => !empty($item->handyman_order_id) ? $item->handyman_order_id : "",
//            'booking_date' => date('d M y', strtotime($item->booking_date)),
//            'slot_time_text' => $time,
//            'payment_detail' => [
//                'payment_method_id' => $item->payment_method_id,
//                'payment_mode' =>!empty($item->payment_method_id) ?  $item->PaymentMethod->payment_method : "",
//                'show_final_amount_paid' => true,
//                'final_amount_paid' => $item->final_amount_paid,
//                'show_user_offer_price' => true,
//                'user_offer_price' => $item->user_offer_price,
//            ],
//            'cancel_reason' => $cancel_reasons,
//            'service_type' => $this->getServiceTypes(json_decode($item->ordered_services), $item->merchant_id),
//            'description' => !empty($item->description) ? $item->description : "",
//            'upload_images' => $upload_images,
//            'actioned_drivers' => $actioned_drivers
//        );
        return $this->successResponse(trans("$string_file.data_found"), $order);
    }

    function createOrder(Request $request){
        $user = $request->user('api');
        $request_fields = [
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where(function ($query) {
            }),],
            'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            }),],
            'service_time_slot_detail_id' => 'required|exists:service_time_slot_details,id',
            'minimum_booking_amount' => 'required',
            'segment_price_card_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'drop_location' => 'required',
            'booking_date' => 'required',
            'card_id' => 'required_if:payment_method_id,2',
//            'user_offer_price' => 'required',
            'service_details' => 'required',
//            'work_images' => 'nullable',
            'description' => 'nullable',
        ];
        $string_file = $this->getStringFile(NULL,$user->Merchant);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $this->getAreaByLatLong($request,$string_file);

            $same_user_bidding = HandymanBiddingOrder::where([['segment_id','=', $request->segment_id], ['service_time_slot_detail_id', '=', $request->service_time_slot_detail_id], ['merchant_id', '=', $user->merchant_id]])
                ->where('booking_date', $request->booking_date)->where([['user_id', '=', $user->id]])->whereIn('order_status', [1, 2])->count();
            if ($same_user_bidding > 0) {
                return $this->failedResponse(trans("$string_file.user_already_booked"));
            }

            if(!empty($request->provider_id)){
                $request->merge(['driver_ids' => [$request->provider_id]]);
            }
            $service_details = json_decode($request->service_details);
            $selected_services = [];
            foreach ($service_details as $service){
                array_push($selected_services, $service->service_type_id);
            }
            $request->merge(['selected_services'=>$selected_services]);
            $arr_plumbers = Driver::getNearestPlumbers($request);
            if ($arr_plumbers->count() == 0) {
                return $this->failedResponse(trans("$string_file.no_provider_available"));
            }
            $driver_ids = $arr_plumbers->pluck('id')->toArray();

            $arr_detail_ids = json_decode($request->service_details,true);
            $arr_detail_ids = array_column($arr_detail_ids,'segment_price_card_detail_id');
            $price_card = SegmentPriceCard::select('id','price_type','minimum_booking_amount','amount')
                ->with(['SegmentPriceCardDetail'=>function($q) use($arr_detail_ids) {
                    $q->whereIn('id',$arr_detail_ids);
                }])
                ->whereHas('SegmentPriceCardDetail',function($q) use($arr_detail_ids) {
                    $q->whereIn('id',$arr_detail_ids);
                })
                ->first();
            if (empty($price_card)) {
                throw new \Exception(trans("$string_file.price_card")." ".trans("$string_file.data_not_found"));
            }
            $request->merge(['price_card'=>$price_card, 'is_bidding_order' => true, 'user_offer_price' => $request->user_offer_price ]);
            $plumberController = new \App\Http\Controllers\Api\PlumberController();
            $cart_amount = $plumberController->getCartDataApp($request, null);
            $order = new HandymanBiddingOrder;
            $order->user_id = $user->id;
            $order->merchant_id = $user->merchant_id;
            $order->segment_id = $request->segment_id;
            $order->order_status = 1;
            $order->country_area_id = $request->area;
            $order->quantity = $cart_amount['total_quantity'];
            $order->card_id = $request->card_id;
            $order->tax_per = $cart_amount['tax_per'];
            $order->cart_amount = $cart_amount['total_amount'];  // service amount
            $tax_on_minimum_booking = ($price_card->minimum_booking_amount * $cart_amount['tax_per'])/100;

            $order->minimum_booking_amount = $price_card->minimum_booking_amount + $tax_on_minimum_booking;
            $order->total_booking_amount = $cart_amount['final_amount']; // final booking amount (service charges + tax)
            $order->final_amount_paid =  $cart_amount['final_amount'];
            $order->user_offer_price = isset($request->user_offer_price) ? $request->user_offer_price : null;

            if($price_card->price_type == 1)
            {
                $mini_amount =  $order->minimum_booking_amount; // tax is already included
                if($mini_amount > $order->total_booking_amount)
                {
                    // $order->final_amount_paid =  $mini_amount;
                    $order->tax = $tax_on_minimum_booking;
                }
                else
                {
                    // $order->final_amount_paid =  $order->total_booking_amount;
                    $order->tax = $cart_amount['tax'];
                }
            }
            else
            {
                //in case of hourly
                // $order->final_amount_paid =  $order->minimum_booking_amount;
                $order->tax = $tax_on_minimum_booking;
            }

            if($request->payment_method_id == 3) {
                $common_controller = new CommonController();
                $common_controller->checkUserWallet($user,$request->bidding_amount);
            }

            $order->payment_method_id = $request->payment_method_id;
            $order->booking_date = $request->booking_date;
            $order->segment_price_card_id = $price_card->id;
            $order->service_time_slot_detail_id = $request->service_time_slot_detail_id;
            $order->price_type = $price_card->price_type;

            $order->drop_latitude = $request->latitude;
            $order->drop_longitude = $request->longitude;
            $order->drop_location = !empty($request->drop_location) ? $request->drop_location : "";

            $order->additional_notes = $request->additional_notes;
            $order->booking_timestamp = time();
            $order->ordered_services = json_encode($cart_amount['ordered_services']);
            $order->description = $request->description;

            $upload_images = [];
            // if(isset($request->work_images) && !empty($request->work_images)){
            //     foreach($request->work_images as $image){
            //         array_push($upload_images, $this->uploadImage($image, 'booking_images', $user->merchant_id, 'multiple'));
            //     }
            // }
            if(!empty($request->work_image_one)){
                array_push($upload_images, $this->uploadImage($request->work_image_one, 'booking_images', $user->merchant_id, 'multiple'));
            }
            if(!empty($request->work_image_two)){
                array_push($upload_images, $this->uploadImage($request->work_image_two, 'booking_images', $user->merchant_id, 'multiple'));
            }
            if(!empty($request->work_image_three)){
                array_push($upload_images, $this->uploadImage($request->work_image_three, 'booking_images', $user->merchant_id, 'multiple'));
            }
            if(!empty($request->work_image_four)){
                array_push($upload_images, $this->uploadImage($request->work_image_four, 'booking_images', $user->merchant_id, 'multiple'));
            }
            $order->upload_images = json_encode($upload_images);
            $order->save();

            $order->Driver()->sync($driver_ids);
            DB::commit();
            // send notification once data saved  in db
            $request->merge(['notification_type' => 'NEW_BIDDING_ORDER']);
            $this->sendProviderNotification($request, $driver_ids, $order,$string_file);

            $data = [
                'order_id' => $order->id,
                'order_status' => $order->order_status
            ];
            
            return $this->successResponse(trans("$string_file.bidding_order_created"), $data);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
    }

    function acceptOrder(Request $request){
        $user = $request->user('api');
        $currency = $user->Country->isoCode;
        $string_file = $this->getStringFile($user->merchant_id);
        $request_fields = [
            'handyman_bidding_order_id' => ['required', 'integer', Rule::exists('handyman_bidding_orders', 'id')->where(function ($query) {
                $query->where("order_status", 1);
            }),],
            'driver_id' => ['required', 'integer', Rule::exists('drivers', 'id')],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try{

            $item = HandymanBiddingOrder::where('user_id', $user->id)->where('id', $request->handyman_bidding_order_id)->first();
            $other_user = HandymanOrder::where([['service_time_slot_detail_id', '=', $item->service_time_slot_detail_id], ['is_order_completed', '!=', 1], ['merchant_id', '=', $user->merchant_id]])
                ->where('booking_date', $item->booking_date)->where([['driver_id', '=', $request->driver_id]])->whereIn('order_status', [4, 6, 7])->count();
            // Check driver slog availability available
            if ($other_user > 0) {
                return $this->failedResponse(trans("$string_file.slot_already_booked"));
            }

            // Check driver radius warning
            $driver = Driver::find($request->driver_id);
            $user_lat = $item->drop_latitude;
            $user_long = $item->drop_longitude;
            $address = $driver->WorkShopArea; // workshop area of driver
            $driver_radius = $address->radius;
            $driver_latitude = $address->latitude;
            $driver_longitude = $address->longitude;
            $unit = $user->Country->distance_unit;
            $unit_lang = ($unit == 2 ? trans("$string_file.miles") : trans("$string_file.km"));
            $google = new GoogleController();
            $distance_from_user = $google->arialDistance($user_lat, $user_long, $driver_latitude, $driver_longitude,$unit,$string_file,false);
            if(ceil($distance_from_user) > $driver_radius) {
                return $this->failedResponse(trans_choice("$string_file.provider_radius_warning",3,['RANGE'=>$driver_radius.$unit_lang]));
            }

            //  Get Services calculation
            $arr_detail_ids = json_decode($item->ordered_services,true);
            $arr_detail_ids = array_column($arr_detail_ids,'segment_price_card_detail_id');
            $price_card = SegmentPriceCard::select('id','price_type','minimum_booking_amount','amount')
                ->with(['SegmentPriceCardDetail'=>function($q) use($arr_detail_ids) {
                    $q->whereIn('id',$arr_detail_ids);
                }])
                ->whereHas('SegmentPriceCardDetail',function($q) use($arr_detail_ids) {
                    $q->whereIn('id',$arr_detail_ids);
                })
                ->first();
            $request->merge(['service_details' => $item->ordered_services, 'price_card'=>$price_card]);
            $plumberController = new PlumberController();

            $bid_driver = $item->ActionedDrivers->where("id", $request->driver_id)->first();

            $driver_count_offer = null;
            $driver_offer_price = false;
            if(!empty($bid_driver) && $bid_driver->pivot->status == 2){ // If this is driver counter offer then
                $driver_count_offer = $bid_driver->pivot->amount;
                $driver_offer_price = true;
            }
            if($driver_offer_price){
                $request->merge(['is_bidding_order' => true, 'user_offer_price' => $driver_count_offer]);
            }else{
                $request->merge(['is_bidding_order' => true, 'user_offer_price' => $item->user_offer_price]);
            }
            $request->merge(['segment_id' => $item->segment_id, "area" => $item->country_area_id, 'merchant_id' => $item->merchant_id]);

            $promocode = NULL;
            $promo_code_id = NULL;
            if (isset($request->promo_code) && !empty($request->promo_code)) {
                $cart =  $plumberController->getCartDataApp($request);
                $request->request->add(['order_amount'=>$cart['total_amount']]);
                $common = new CommonController;
                $check_promo_code = $common->checkPromoCode($request, true);
                if(isset($check_promo_code['status']) && $check_promo_code['status'] == true) {
                    $promocode = $check_promo_code['promo_code'];
                    $promo_code_id = $promocode->id;
                }else{
                    return $check_promo_code;
                }
            }
            $cart_amount = $plumberController->getCartDataApp($request, $promocode);


            // Create Handyman Order
            $order = new HandymanOrder;
            $order->merchant_id = $item->merchant_id;
            $order->user_id = $user->id;
            $order->driver_id = $request->driver_id;
            $order->segment_id = $item->segment_id;
            $order->order_status = 4; //order placed
            $order->country_area_id = $item->country_area_id;
            $order->quantity = $cart_amount['total_quantity'];
            $order->card_id = $item->card_id;
            $order->tax_per = $cart_amount['tax_per'];
            $order->tax = $cart_amount['tax'];
            $order->cart_amount = $cart_amount['total_amount'];  // service amount
            $order->total_booking_amount = $cart_amount['final_amount']; // final booking amount (service charges + tax)
            $order->minimum_booking_amount = $cart_amount['total_amount'];

            $order->final_amount_paid = $cart_amount['final_amount'];

            // Promo code apply
            $order->promo_code_id = $promo_code_id;
            $order->discount_amount = $cart_amount['discount_amount'];

            /*******Check user wallet if payment method is wallet *********/
            $final_amount = $order->final_amount_paid;
            if($item->payment_method_id == 3)
            {
                $common_controller = new CommonController;
                $common_controller->checkUserWallet($user,$final_amount);
            }

            $order->payment_method_id = $item->payment_method_id;
            $order->min_booking_payment_method_id = $item->payment_method_id;
            $order->booking_date = $item->booking_date;
            $order->segment_price_card_id = $price_card->id;
            $order->service_time_slot_detail_id = $item->service_time_slot_detail_id;
            $order->price_type = $price_card->price_type;
            $order->hourly_amount = $price_card->amount;

            $order->drop_latitude = $item->drop_latitude;
            $order->drop_longitude = $item->drop_longitude;
            $order->drop_location = $item->drop_location;

            $order->additional_notes = $item->additional_notes;
            $order->booking_timestamp = time();
            $order->driver_id = $request->driver_id;

            $status_history[] = [
                'order_status'=> 4,
                'order_timestamp'=>time(),
                'latitude'=>$request->latitude,
                'longitude'=>$request->longitude,
            ];

            $order->order_status_history = json_encode($status_history);
            $order->save();

            foreach ($cart_amount['ordered_services'] as $service) {
                $service_obj = new HandymanOrderDetail();
                $service_obj->handyman_order_id = $order->id;
                $service_obj->service_type_id = $service['service_type_id'];

                if ($price_card->price_type == 1) // it will  insert in case of fixed price type
                {
                    $service_obj->segment_price_card_detail_id = $service['segment_price_card_detail_id'];
                    $service_obj->quantity = $service['quantity'];
                    $service_obj->price = $service['service_price']; // service price
                }
                $service_obj->discount = 0;
                $service_obj->total_amount = $service['price']; // total charges of service
                $service_obj->save();
            }
            $data = [
                'order_id' => $order->id,
                'order_status' => $order->order_status
            ];

            $item->handyman_order_id = $order->id;
            $item->order_status = 2;
            $item->save();

            $item->Driver()->sync([$request->driver_id => ['status' => 4]], false);

            // send notification once data saved  in db
            $request->merge(['notification_type' => 'NEW_ORDER']);
            $this->sendNotificationToProvider($request, $order->driver_id, $order, $string_file);

            $arr_driver_id = [$order->Driver];
            $findDriver = new FindDriverController();
            $findDriver->AssignRequest($arr_driver_id, null, null, $order->id);

            $pending_driver_ids = $item->ActionedDrivers->where("id","!=",$request->driver_id)->pluck("id")->toArray();
            if(!empty($pending_driver_ids)){
                // send notification once data saved  in db
                $request->merge(['notification_type' => 'BIDDING_ORDER_ACCEPTED_BY_OTHER_DRIVER']);
                $this->sendProviderNotification($request, $pending_driver_ids, $item,$string_file);
            }

            DB::commit();
            return $this->successResponse(trans("$string_file.handyman_order_placed"),$data);
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    function cancelDeleteOrder(Request $request){
        $user = $request->user('api');
        $string_file = $this->getStringFile($user->merchant_id);
        $request_fields = [
            'handyman_bidding_order_id' => ['required', 'integer', Rule::exists('handyman_bidding_orders', 'id')->where(function ($query) {
            }),],
            'action' => 'required|in:CANCEL,DELETE',
            'cancel_reason_id' => 'required_if:action,CANCEL'
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $order = HandymanBiddingOrder::where('user_id', $user->id)->where('id', $request->handyman_bidding_order_id)->first();
            if(!empty($order)){
                $order->order_status = $request->action == "CANCEL" ? 3 : 4;
                $order->cancel_reason_id = $request->cancel_reason_id;
                $order->save();

                $driver_ids = $order->Driver->pluck("id")->toArray();
                // send notification once data saved  in db
                $request->merge(['notification_type' => 'BIDDING_ORDER_CANCEL']);
                $this->sendProviderNotification($request, $driver_ids, $order,$string_file);
            }
            DB::commit();
            return $this->successResponse(trans("$string_file.success"));
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    // send notification to driver
    public function sendProviderNotification($request, $arr_driver_id, $order, $string_file = "")
    {
        $merchant_id = $order->merchant_id;
        $data['notification_type'] = $request->notification_type;
        $data['segment_type'] = "HANDYMAN"; //$order->Segment->slag;
        $data['segment_sub_group'] = $order->Segment->sub_group_for_app; // for handyman
        $data['segment_group_id'] = $order->Segment->segment_group_id; // for handyman
        $segment_name = $order->Segment->Name($merchant_id);
        $item = $order->Segment;
        $large_icon = $large_icon = isset($item->Merchant[0]['pivot']->segment_icon) && !empty($item->Merchant[0]['pivot']->segment_icon) ? get_image($item->Merchant[0]['pivot']->segment_icon, 'segment', $merchant_id, true) : get_image($item->icon, 'segment_super_admin', NULL, false);
        $data['segment_data'] = [
            "order_id" => $order->id,
        ];
        if (!is_array($arr_driver_id)) {
            $arr_driver_id = [$arr_driver_id];
        }
        foreach ($arr_driver_id as $driver_id) {
            $driver = Driver::find($driver_id);
            setLocal($driver->language);
            if ($request->notification_type == 'BIDDING_ORDER_CANCEL'){
                $title = $segment_name . ' ' . trans("$string_file.cancel") . ' ' . trans("$string_file.order");
                $message = trans("$string_file.booking_cancelled_by",['ID' => $order->id]);
                $message = $message.' '.$driver->first_name;
            }elseif ($request->notification_type == 'BIDDING_ORDER_ACCEPTED_BY_OTHER_DRIVER'){
                $title = $segment_name . ' ' . trans("$string_file.order_status"). ' ' . trans("$string_file.update");
                $message = trans("$string_file.order_already_assign");
//                $message = $message.' '.$driver->first_name;
            }else{
                $title = $segment_name . ' ' . trans("$string_file.new") . ' ' . trans("$string_file.order");
                $message = trans("$string_file.new_booking_driver_message");
            }
            $arr_param = ['driver_id' => $driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => $large_icon];
            Onesignal::DriverPushMessage($arr_param);
        }
        setLocal();
        return true;
    }

    public function ApplyRemovePromoCode(Request $request){
        $user = $request->user('api');
        $string_file = $this->getStringFile($user->merchant_id);
        $request_fields = [
            'handyman_bidding_order_id' => ['required', 'integer', Rule::exists('handyman_bidding_orders', 'id')->where(function ($query) {
                $query->where("order_status", 1);
            }),],
            'driver_id' => ['required', 'integer', Rule::exists('drivers', 'id')],
            'promo_code' => 'required',
            'action' => 'required'
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        DB::beginTransaction();
        try{
            $item = HandymanBiddingOrder::where('user_id', $user->id)->where('id', $request->handyman_bidding_order_id)->first();
            //  Get Services calculation
            $arr_detail_ids = json_decode($item->ordered_services,true);
            $arr_detail_ids = array_column($arr_detail_ids,'segment_price_card_detail_id');
            $price_card = SegmentPriceCard::select('id','price_type','minimum_booking_amount','amount')
                ->with(['SegmentPriceCardDetail'=>function($q) use($arr_detail_ids) {
                    $q->whereIn('id',$arr_detail_ids);
                }])
                ->whereHas('SegmentPriceCardDetail',function($q) use($arr_detail_ids) {
                    $q->whereIn('id',$arr_detail_ids);
                })
                ->first();
            $request->merge(['service_details' => $item->ordered_services, 'price_card'=>$price_card]);
            $plumberController = new PlumberController();

            $bid_driver = $item->ActionedDrivers->where("id", $request->driver_id)->first();
            $driver_count_offer = null;
            $driver_offer_price = false;
            if(!empty($bid_driver) && $bid_driver->pivot->status == 2){ // If this is driver counter offer then
                $driver_count_offer = $bid_driver->pivot->amount;
                $driver_offer_price = true;
            }
            if($driver_offer_price){
                $request->merge(['is_bidding_order' => true, 'user_offer_price' => $driver_count_offer]);
            }else{
                $request->merge(['is_bidding_order' => true, 'user_offer_price' => $item->user_offer_price]);
            }
            $request->merge(['segment_id' => $item->segment_id, "area" => $item->country_area_id, 'merchant_id' => $item->merchant_id]);

            $promocode = NULL;
            $promo_code_id = NULL;
            if ($request->action == 'APPLY'){
                if (isset($request->promo_code) && !empty($request->promo_code)) {
                    $cart =  $plumberController->getCartDataApp($request);
                    $request->merge(['order_amount'=>$cart['total_amount']]);
                    $common = new CommonController;
                    $check_promo_code = $common->checkPromoCode($request, true);
                    if(isset($check_promo_code['status']) && $check_promo_code['status'] == true) {
                        $promocode = $check_promo_code['promo_code'];
                        $promo_code_id = $promocode->id;
                    }else{
                        return $check_promo_code;
                    }
                }
                $cart_amount = $plumberController->getCartDataApp($request, $promocode);
                $item->total_booking_amount = $cart_amount['final_amount']; // final booking amount (service charges + tax)
                $item->final_amount_paid = $cart_amount['final_amount'];
                // Promo code apply
                $item->promo_code_id = $promo_code_id;
                $item->discount_amount = $cart_amount['discount_amount'];
            }else{
                //remove promo code
                $cart_amount = $plumberController->getCartDataApp($request);
                $item->total_booking_amount = $cart_amount['final_amount']; // final booking amount (service charges + tax)
                $item->final_amount_paid = $cart_amount['final_amount'];
                $item->promo_code_id = $promo_code_id;
                $item->discount_amount = $cart_amount['discount_amount'];
            }
            $item->save();

            $order_status = $this->getStatusString($item->order_status, $string_file);
            $service_types = $this->getServiceTypes(json_decode($item->ordered_services), $item->merchant_id);
            $order = $this->getHandymanBiddingOrderDetail($item, $order_status, $service_types, $string_file, $request->driver_id);
            DB::commit();
            return $this->successResponse(trans("$string_file.data_found"), $order);
        }catch (\Exception $exception){
            DB::rollback();
            return $this->failedResponse($exception->getMessage());
        }
    }

    protected function getStatusString($status, $string_file){
        switch ($status){
            case 1:
                $status = trans("$string_file.pending");
                break;
            case 3:
                $status = trans("$string_file.cancelled");
                break;
            case 4:
                $status = trans("$string_file.deleted");
                break;
            case 2:
                $status = trans("$string_file.bidding")." ".trans("$string_file.completed");
                break;
            default:
                $status = trans("$string_file.unknown");
        }
        return $status;
    }

    protected function getServiceTypes($service_details, $merchant_id){
        $arr_service = [];
        foreach ($service_details as $inner_item) {
            $service = ServiceType::find($inner_item->service_type_id);
            $arr_service[] = array(
                'id' => $inner_item->service_type_id,
                'name' => $service->serviceName($merchant_id),
                'amount' => $inner_item->price,
                'quantity' => $inner_item->quantity,
                'segment_price_card_detail_id' => (integer)$inner_item->segment_price_card_detail_id,
            );
        }
        return $arr_service;
    }
}
