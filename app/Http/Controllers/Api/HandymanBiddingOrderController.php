<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 24/8/23
 * Time: 1:44 PM
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\GoogleController;
use App\Models\BookingRating;
use App\Models\HandymanBiddingOrder;
use App\Models\HandymanOrder;
use App\Models\ServiceType;
use App\Traits\ApiResponseTrait;
use App\Traits\HandymanTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;

class HandymanBiddingOrderController extends Controller
{
    use ApiResponseTrait, MerchantTrait, HandymanTrait;

    function getOrders(Request $request){
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL,$driver->Merchant);
        $request_fields = [
            'type' => ['required','in:ACTIVE,ALL'],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $arr_orders = HandymanBiddingOrder::where([['merchant_id', '=', $driver->merchant_id]])->where(function ($q) use ($request) {
            if (!empty($request->type)) {
                if($request->type == "ACTIVE"){
                    $q->where('order_status', 1);
                }elseif($request->type == "ACTIVE"){
                    $q->whereIn('order_status', [2, 3]);
                }
            }
        })->whereHas("Driver", function($query) use($driver){
            $query->where("id", $driver->id);
        })->orderBy('id','DESC')->get();

        $time_format =  $driver->Merchant->Configuration->time_format;
        $currency = $driver->Country->isoCode;

        $arr_orders = $arr_orders->map(function ($item, $key)use ($driver,$currency,$time_format,$string_file){
            $time = "";
            if(isset($item->ServiceTimeSlotDetail))
            {
                $start = strtotime($item->ServiceTimeSlotDetail->from_time);
                $start = $time_format == 2  ? date("H:i",$start) : date("h:i a",$start);
                $end = strtotime($item->ServiceTimeSlotDetail->to_time);
                $end =  $time_format == 2  ? date("H:i",$end) : date("h:i a",$end);
                $time = $start."-".$end;
            }

            $bidding_order_status = trans("$string_file.pending");
            $bidding_status = 0;
            $bidding_amount = "0.00";
            $bidding_actioned_driver = $item->ActionedDriver($driver->id)->first();
            if(!empty($bidding_actioned_driver)){
                $bidding_amount = !empty($bidding_actioned_driver->pivot->amount) ? $bidding_actioned_driver->pivot->amount : "0.00";
                $bidding_status = $item->order_status != 3 ? $bidding_actioned_driver->pivot->status : 3;
                $bidding_order_status = $this->getBiddingStatusString($bidding_status, $string_file);
            }

            return array(
                'order_id' => $item->id,
                'segment_id' => $item->segment_id,
                'segment_name' => !empty($item->Segment->Name($item->merchant_id)) ? $item->Segment->Name($item->merchant_id) : $item->Segment->slag,
                'final_amount_paid' =>$item->final_amount_paid,
                'user_offer_price' =>$item->user_offer_price,
                'currency' =>$currency,
                'total_services' => $item->quantity,
                'order_status' => $this->getStatusString($item->order_status, $string_file),
                'booking_date' => date('d M y',strtotime($item->booking_date)),
                'slot_time_text' =>$time,
                'service_type' => $this->getServiceTypes(json_decode($item->ordered_services), $item->merchant_id),
                'description' => !empty($item->description) ? $item->description : "",
                'status' => $item->order_status,
                'bidding_amount' => $bidding_amount,
                'bidding_order_status' => $bidding_order_status,
                'bidding_status' => $bidding_status,
            );
        });
        return $this->successResponse(trans("$string_file.data_found"), $arr_orders);
    }

    function getOrderDetail(Request $request){
        $driver = $request->user('api-driver');
        $currency = $driver->Country->isoCode;
        $string_file = $this->getStringFile($driver->merchant_id);
        $request_fields = [
            'handyman_bidding_order_id' => ['required', 'integer', Rule::exists('handyman_bidding_orders', 'id')->where(function ($query) {
            }),],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $item = HandymanBiddingOrder::where('id', $request->handyman_bidding_order_id)->with(["Driver" => function($q) use($driver){
            $q->where("id", $driver->id);
        }])->whereHas("Driver", function($query) use($driver){
            // $query->where("id", $driver->id);
        })->first();

        $time = "";
        $time_format =  $driver->Merchant->Configuration->time_format;
        $order_status = $this->getStatusString($item->order_status, $string_file);

        if(isset($item->ServiceTimeSlotDetail))
        {
            $start = strtotime($item->ServiceTimeSlotDetail->from_time);
            $start = $time_format == 2  ? date("H:i",$start) : date("h:i a",$start);
            $end = strtotime($item->ServiceTimeSlotDetail->to_time);
            $end =  $time_format == 2  ? date("H:i",$end) : date("h:i a",$end);
            $time = $start."-".$end;
        }

        $action_drivers = $item->ActionedDrivers;
        $site_visit_request_driver = $item->SiteVisitorRequestDriver($driver->id)->first();
        $actioned_drivers = [];
        foreach($action_drivers as $driver){
            $status = "";
            if($driver->pivot->status == 1){
                $status = trans("$string_file.accepted");
            }elseif($driver->pivot->status == 2){
                $status = trans("$string_file.counter_offer");
            }
            $rating = BookingRating::whereHas('HandymanOrder', function ($q) use ($driver){
                $q->where('driver_id', $driver->id);
            })
                ->where('handyman_order_id','!=',NULL)
                ->avg('user_rating_points');
            $rating = isset($rating) ? round($rating,2) : $rating;

            array_push($actioned_drivers, array(
                "driver_id" => $driver->id,
                "full_name" => $driver->fullName,
                "description" => "Description of the service provider",
                "status" => $status,
                "show_counter_offer" => $driver->pivot->status == 2 ? true : false,
                "counter_offer" => $driver->pivot->status == 2 ? "" : "",
                "rating" => $rating,
                "image" => get_image($item->profile_image,'driver',$driver->merchant_id)
            ));
        }

        $upload_images = [];
        if(!empty($item->upload_images)){
            foreach(json_decode($item->upload_images, true) as $image){
                array_push($upload_images, get_image($image, "booking_images", $item->merchant_id));
            }
        }

        $order = array(
            'order_id' => $item->id,
            'segment_id' => $item->segment_id,
            'segment_name' => !empty($item->Segment->Name($item->merchant_id)) ? $item->Segment->Name($item->merchant_id) : $item->Segment->slag,
            'drop_location' => $item->drop_location,
            'currency' => $currency,
            'total_services' => $item->quantity,
            'order_status' => $order_status,
            'numeric_order_status' => $item->order_status,
            'booking_date' => date(getDateTimeFormat(4, 2), strtotime($item->booking_date)),
            'slot_time_text' => $time,
            'payment_detail' => [
                'payment_method_id' => $item->payment_method_id,
                'payment_mode' =>!empty($item->payment_method_id) ?  $item->PaymentMethod->payment_method : "",
                'show_final_amount_paid' => true,
                'final_amount_paid' => $item->final_amount_paid,
                'show_user_offer_price' => true,
                'user_offer_price' => $item->user_offer_price,
            ],
            'service_type' => $this->getServiceTypes(json_decode($item->ordered_services), $item->merchant_id),
            'description' => !empty($item->description) ? $item->description : "",
            'upload_images' => $upload_images,
            'actioned_drivers' => $actioned_drivers,
            'user_detail' => [
                'user_name' => $item->User->first_name . ' ' . $item->User->last_name,
                'user_image' => get_image($item->User->UserProfileImage, 'user', $item->merchant_id),
                'user_phone' => $item->order_status >= 4 ? $item->User->UserPhone : "******",
                'display' => $item->order_status == 1 ? true : false,
            ],
            'address_detail' => array(
                'drop_location' => $item->drop_location,
                'drop_latitude' => $item->drop_latitude,
                'drop_longitude' => $item->drop_longitude,
            ),
            "visit_request_status"=> !empty($site_visit_request_driver)? $site_visit_request_driver->pivot->status : -1,
        );
        return $this->successResponse(trans("$string_file.data_found"), $order);
    }

    public function bidOrder(Request $request){
        $request_fields = [
            'handyman_bidding_order_id' => ['required', 'integer', Rule::exists('handyman_bidding_orders', 'id')->where(function ($query) {
                $query->where("order_status", 1);
            }),],
            'action' => ['required', Rule::in(["ACCEPT","REJECT","COUNTER"])],
            'amount' => ['required_if:action,COUNTER']
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);

            $order = HandymanBiddingOrder::find($request->handyman_bidding_order_id);

            //getting current active order for checking the same time slot
            if ($request->action != "REJECT"){
                $active_order = HandymanOrder::where([['driver_id', '=', $driver->id],['country_area_id', '=', $driver->country_area_id], ['is_order_completed', '!=', 1], ['merchant_id', '=', $driver->merchant_id]])->whereIn('order_status', [4,6])->first();
                if (!empty($active_order)){
                    if ($active_order->ServiceTimeSlotDetail->from_time == $order->ServiceTimeSlotDetail->from_time && $active_order->booking_date == $order->booking_date){
                        return $this->failedResponse(trans("$string_file.ongoing_active_order").' '.$active_order->ServiceTimeSlotDetail->from_time.' - '.$active_order->ServiceTimeSlotDetail->to_time);
                    }
                }

                $user_lat = $order->drop_latitude;
                $user_long = $order->drop_longitude;
                $address = $driver->WorkShopArea; // workshop area of driver
                $driver_radius = $address->radius;
                $driver_latitude = $address->latitude;
                $driver_longitude = $address->longitude;
                $unit = $order->CountryArea->Country->distance_unit;
                $unit_lang = ($unit == 2 ? trans("$string_file.miles") : trans("$string_file.km"));
                $google = new GoogleController();
                $distance_from_user = $google->arialDistance($user_lat, $user_long, $driver_latitude, $driver_longitude,$unit,$string_file,false);
                if(ceil($distance_from_user) > $driver_radius) {
                    return $this->failedResponse(trans_choice("$string_file.provider_radius_warning",3,['RANGE'=>$driver_radius.$unit_lang]));
                }
            }

            $status = null;
            $description = !empty($request->description) ? $request->description : "";
            $amount = null;
            if($request->action == "ACCEPT"){
                if ($driver->wallet_money != null && $driver->wallet_money < $driver->CountryArea->minimum_wallet_amount) {
                    $message = trans("$string_file.low_wallet_warning");
                    return $this->failedResponse($message);
                }
                $status = 1;
            }elseif($request->action == "COUNTER"){
                if ($driver->wallet_money != null && $driver->wallet_money < $driver->CountryArea->minimum_wallet_amount) {
                    $message = trans("$string_file.low_wallet_warning");
                    return $this->failedResponse($message);
                }
                $status = 2;
                $amount = $request->amount;
            }elseif($request->action == "REJECT"){
                $status = 3;
            }else{
                return $this->failedResponse(trans("$string_file.invaild_status"));
            }

            $order->Driver()->sync([$driver->id => ['status' => $status, 'amount' => $amount, 'description' => $description]], false);

            /**send notification to user*/
            if ($request->action != "REJECT"){
                $request->merge(['notification_type' => 'ORDER_BIDDING_UPDATE']);
                $this->sendHandymanNotificationToUser($request, $order, "", $string_file);
            }

            if($request->action == "REJECT"){
                $requested_driver = $order->Driver->count();
                $rejected_driver = $order->RejectedDriver->count();
                if($rejected_driver == $requested_driver){
                    $order->order_status = 3;
                    $order->save();
                }
            }

            DB::commit();
            return $this->successResponse(trans("$string_file.success"));
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
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
            case 2:
                $status = trans("$string_file.completed");
                break;
            default:
                $status = trans("$string_file.unknown");
        }
        return $status;
    }

    protected function getBiddingStatusString($status, $string_file){
        switch ($status){
            case 0:
                $status = trans("$string_file.pending");
                break;
            case 1:
                $status = trans("$string_file.accepted");
                break;
            case 2:
                $status = trans("$string_file.quote_sent");
                break;
            case 3:
                $status = trans("$string_file.rejected");
                break;
            case 4:
                $status = trans("$string_file.finalized");
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
                'quantity' => (string)$inner_item->quantity,
                'segment_price_card_detail_id' => (integer)$inner_item->segment_price_card_detail_id,
            );
        }
        return $arr_service;
    }
}
