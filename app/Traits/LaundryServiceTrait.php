<?php

namespace App\Traits;

use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\FindDriverController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\BookingTransaction;
use App\Models\Category;
use App\Models\Driver;
use App\Models\LaundryOutlet\LaundryOutletOrder;
use App\Models\LaundryOutlet\LaundryService;
use App\Models\Onesignal;
use App\Models\ServiceTimeSlotDetail;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Helper\Merchant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Matrix\Exception;

trait LaundryServiceTrait
{
    public function getCategory($merchant_id, $segment_id = null, $type = "parent", $parent_id = NULL,$calling_from = "web"): array
    {
        $categories = Category::select('id')
            ->where(function ($query) use ($type, $parent_id) {
                if ($type == 'parent') {
                    $query->where('category_parent_id', '=', 0);
                } elseif ($type == 'child') {
                    $query->where('category_parent_id', '=', $parent_id);
                }
            })->with('Segment')->whereHas('Segment', function ($q) use ($segment_id) {
                if(!empty($segment_id))
                {
                    $q->where('segment_id', $segment_id);
                }
            })
            ->where('merchant_id', $merchant_id)
            ->where('delete', NULL)
            ->get();
        $arr_category = [];
        if($calling_from =="app")
        {
            foreach ($categories as $category) {
                $arr_category[] =
                    ["key"=>$category->id,"value"=>$category->Name($merchant_id)];
            }
        }
        else
        {
            foreach ($categories as $category) {
                $arr_category[$category->id] = $category->Name($merchant_id);
            }
        }
        return $arr_category;
    }

    public function getLaundryServices($request)
    {
        $user = $request->user('api');
        $trip_calculation_method = $user->Merchant->Configuration->trip_calculation_method;
        $format_price = $user->Merchant->Configuration->format_price;
        $currency = isset($user->Country) ? $user->Country->isoCode : "";
        $merchant_id = $request->merchant_id;
        $segment_id = $request->segment_id;
        $sub_category_id = $request->sub_category_id;
        $category_id = !empty($sub_category_id) ? $sub_category_id : $request->category_id;
        $display_type = !empty($request->display_type) ? $request->display_type : NULL;
        $laundry_outlet_id = !empty($request->laundry_outlet_id) ? $request->laundry_outlet_id : NULL;
        $filtered_product_id = !empty($request->filtered_product_id) ? $request->filtered_product_id : NULL;
        $merchant_helper = new Merchant();
        $query = LaundryService::with(['Category' => function ($qq) use ($merchant_id, $category_id) {
            if (!empty($category_id)) {
                $qq->where(function ($qqq) use ($category_id) {
                    $qqq->where('id', $category_id);
                    $qqq->orWhere('category_parent_id', $category_id);
                });
            }
            else {
                $qq->where([['merchant_id', '=', $merchant_id], ['status', '=', 1], ['delete', '=', NULL]]);
            }
        }]);
        $query->where([['merchant_id', '=', $merchant_id], ['delete', '=', NULL]])
            ->where(function ($qq) use ($laundry_outlet_id) {
                if (!empty($laundry_outlet_id)) {
                    if (is_array($laundry_outlet_id)) {
                        $qq->whereIn('laundry_outlet_id', $laundry_outlet_id);
                    } else {
                        $qq->where('laundry_outlet_id', $laundry_outlet_id);
                    }
                }
            })
            ->where(function ($qq) use ($display_type) {
                if (!empty($display_type)) {
                    $qq->where('display_type', $display_type);
                }
            })
            ->where(function ($qq) use ($category_id) {
                if (!empty($category_id)) {
                    $qq->where('category_id', $category_id);
                }
            });
        $services = [];
        if($request->return_type == 'single_service_detail'){
            $item = $query->where([['status', '=', 1], [ 'id',$laundry_outlet_id ]])
                ->first();
            $service_lang = $item->langData($merchant_id);

            $service_cover_image = !empty($item->service_cover_image) ? get_image($item->service_cover_image,'laundry_service_cover_image',$merchant_id) : "";
            $service_image = !empty($item->service_image) ? get_image($item->service_image, 'laundry_service_image', $merchant_id) : $service_cover_image;
            $service_cover_image = !empty($service_cover_image)?$service_cover_image:$service_image;

            $price = custom_number_format($item->price, $trip_calculation_method);

            $services[]= [
                'id' => $item->id,
                'laundry_service_id' => $item->laundry_outlet_id,
                'price' => $price,
                'formatted_price' => ($format_price != 1) ? $merchant_helper->PriceFormat($price, $merchant_id, $format_price, $trip_calculation_method) : "",
                'title' =>  $service_lang->name,
                'service_description' => !empty($service_lang->description) ? $service_lang->description : "",
                'currency' => "$currency",
                'image' => $service_cover_image,
                'service_image'=>$service_image,
                'service_availability' => $item->status == 1,
                'sequence' =>$item->sequence
            ];
        }

        if($request->return_type == "modified_array")
        {
            $services = $query->where([['status', '=', 1]])
                ->orderBy(DB::raw('RAND()'))
                ->get();
            $services = $services->map(function ($item, $key) use($merchant_id,$currency,$trip_calculation_method, $merchant_helper, $format_price)
            {
                $service_lang = $item->langData($merchant_id);

                $service_cover_image = !empty($item->service_cover_image) ? get_image($item->service_cover_image,'laundry_service_cover_image',$merchant_id) : "";
                $service_image = !empty($item->service_image) ? get_image($item->service_image, 'laundry_service_image', $merchant_id) : $service_cover_image;
                $service_cover_image = !empty($service_cover_image)?$service_cover_image:$service_image;

                $price = custom_number_format($item->price, $trip_calculation_method);

                return array(
                    'id' => $item->id,
                    'laundry_service_id' => $item->id,
                    'price' => $price,
                    'formatted_price' => ($format_price != 1) ? $merchant_helper->PriceFormat($price, $merchant_id, $format_price, $trip_calculation_method) : "",
                    'title' =>  $service_lang->name,
                    'service_description' => !empty($service_lang->description) ? $service_lang->description : "",
                    'currency' => "$currency",
                    'image' => $service_cover_image,
                    'service_image'=>$service_image,
                    'service_availability' => $item->status == 1,
                    'sequence' =>$item->sequence
                );
            });
            if(!empty($services)){
                $services = $services->sortBy('sequence')->values();
            }

        }
        return $services;
    }

    public function getLaundryOrderStatus($req_param): array
    {
        if (isset($req_param['string_file'])) {
            $string_file =  $req_param['string_file'];
        } else {
            $merchant_id = $req_param['merchant_id'];
            $string_file =  $this->getStringFile($merchant_id);
        }
        $store_string = trans("$string_file.laundry_outlet");

        $order_string = trans("$string_file.order");
        $cancelled_string = trans("$string_file.cancelled");
        $rejected_string = trans("$string_file.rejected");
        $accepted_string = trans("$string_file.accepted");
        $arrived_string = trans("$string_file.arrived");
        $picked_string = trans("$string_file.picked");
        $pickup_string = trans("$string_file.pick_up");
        $delivered_string = trans("$string_file.delivered");
        $completed_string = trans("$string_file.completed");
        $by_string = trans("$string_file.by");
        $user_string = trans("$string_file.user");
        $driver_string = trans("$string_file.driver");
        $auto_string = trans("$string_file.auto");
        $expired_string = trans("$string_file.expired");
        $at_string = trans("$string_file.at");
        $in_string = trans("$string_file.in");
        $process_string = trans("$string_file.process");
        $admin_string = trans("$string_file.admin");
        $pending = trans("$string_file.pending");
        $verification = trans("$string_file.verification");
        $ready = trans("$string_file.ready");
        $to = trans("$string_file.to");
        $deliver = trans("$string_file.deliver");
        $done = trans("$string_file.done");
        $done = trans("$string_file.done");
        $delay_order = trans("$string_file.delay_order");
        return   array(
            '1' => trans("$string_file.new") . ' ' . $order_string,
            '2' => $cancelled_string . ' ' . $by_string . ' ' . $user_string,
            '3' => $rejected_string . ' ' . $by_string . ' ' . $store_string,
            '4' => $accepted_string . ' ' . $by_string . ' ' . $store_string,
            '5' => $cancelled_string . ' ' . $by_string . ' ' . $driver_string,
            '6' => $accepted_string . ' ' .$by_string. ' '.  $driver_string,
            '7' => $arrived_string . ' ' .$at_string. ' '.  $store_string,
            '8' => $cancelled_string . ' ' . $by_string.' '.$driver_string,
            '9' => $order_string . ' ' . $in_string.' '.$process_string,
            '10' => $order_string  . ' '.$picked_string, //picked from store
            '11' => $order_string . ' ' . $completed_string ,
            '12' => $auto_string.' '.$expired_string,
            '13'=> $verification.' '.$done,
            '14'=> $order_string.' '.$completed_string,
            '15'=> $accepted_string . ' '.$pickup_string.' ' .$by_string. ' '.  $driver_string,
            '16'=> $order_string . ' '.$delivered_string,
            '17'=> $order_string . ' '.$delivered_string,
            '20'=> $delay_order,
        );
    }

    public function saveLaundryOrderStatusHistory($request, $order_obj, $laundry_outlet_order_id= NULL): bool
    {
        if (!empty($order_obj->id)) {
            $order = $order_obj;
        } else {
            $order = LaundryOutletOrder::select('id', 'order_status', 'order_status_history')->Find($laundry_outlet_order_id);
        }

        
        if (!empty($order->id)) {
            $new_status = [
                'order_status' => $order->order_status,
                'order_timestamp' => time(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ];
            if (empty($order->order_status_history)) {
                $order->order_status_history = json_encode([$new_status]);
                $order->save();
            } else {
                $status_history = json_decode($order->order_status_history, true);
                $status_history[] = $new_status;
                $order->order_status_history = json_encode($status_history);
                $order->save();
            }
        }
        return true;
    }

    public function LaundryOrderPickupNotification($request, $order) //used by manual assign
    {

        $laundry_outlet_order_id = $order->id;
        $string_file = $this->getStringFile(NULL, $order->Merchant);
        
        $request->merge(['latitude'=>$order->drop_latitude,'longitude'=>$order->drop_longitude, //user's location
        'merchant_id'=>$order->merchant_id,'segment_id'=>$order->segment_id,'user_id'=>$order->user_id
            ,'service_type_id'=>$order->service_type_id,'driver_vehicle_id'=>$order->driver_vehicle_id, 'driver_id'=>$request->driver_id
        ]);
        
        $arr_driver = Driver::getDeliveryCandidate($request);

        $arr_driver_id = array_pluck($arr_driver, 'id');
        $notify_driver_id = [];

        if(is_null($request->driver_id)){
            $notify_driver_id =  $arr_driver_id;
        }else{

            foreach ($request->driver_id as $key => $value) {
                if(in_array($value, $arr_driver_id)){
                    $notify_driver_id[] = $value; 
                }
            }
        }
        if (!empty($notify_driver_id)) {
            $request->request->add(['id' => $laundry_outlet_order_id, 'notification_type' => "NEW_ORDER"]);
            $this->NotifyDriver($request, $notify_driver_id, $order);
            
            // entry in request table
            $findDriver = new FindDriverController();
            $findDriver->AssignRequest($arr_driver, null, null, null, $laundry_outlet_order_id);
            return trans("$string_file.order_assign_request");
        } else {
            throw new \Exception(trans("$string_file.seems_drivers_not_ready"));
        }
    }

    public function NotifyUser($order, $message = ''): void
    {
        $order_status = $order->order_status;
        $user_id = $order->user_id;
        $data['notification_type'] = "LAUNDRY_ORDER";
        $data['segment_type'] = $order->Segment->slag;
        $data['segment_sub_group'] = $order->Segment->sub_group_for_app;
        $data['segment_group_id'] = $order->Segment->segment_group_id;
        $data['segment_data'] = [
            'laundry_outlet_order_id' => $order->id,
            'order_status' => $order_status,
            'service_type' => $order->ServiceType->type,
            'type' => in_array($order_status, [1,6,7,9,13,10,15,16]) ? 2 : 3, // 3 means past 2 means active
        ];
        $order_id = "#".$order->merchant_order_id;
        $merchant_id = $order->merchant_id;
        $outlet = $order->LaundryOutlet->full_name;
        $driver_name = "";
        if (!empty($order->driver_id)) {
            $driver_name = $order->Driver->first_name . $order->Driver->last_name;
        }
        $item = $order->Segment;
        $user = User::find($user_id);
        setLocal($user->language);

        $segment_name = !empty($item->Name($merchant_id)) ? $item->Name($merchant_id) : $item->slag;
        $string_file = $this->getStringFile($merchant_id);

        $lang_order = trans("$string_file.order");
        $order_delivered = false;
        $title = "";
        $message = "";
        switch ($order_status) {
            case "1":
                $title = trans("$string_file.new") . ' ' . $segment_name . ' ' . $lang_order;
                $message = $lang_order.' '.trans("$string_file.placed");
                break;
            case "2":
            case "3":
            case "5":
            case "8":
                $title = $segment_name . ' ' . $lang_order . ' ' . trans("$string_file.cancelled");
                $message = trans("$string_file.order_cancelled")." ".$order_id;
                break;
            case "6":
                $title = $segment_name . ' ' . $lang_order . ' ' . trans("$string_file.accepted");
                $message = trans_choice("$string_file.order_accepted_by_driver", 3, ['ID' => $order->merchant_order_id, 'delivery' => $driver_name]);
                break;
            case "7":
                $title = $segment_name . ' ' . $lang_order;
                $message = $lang_order." ".$order_id. " ".trans("$string_file.arrived").' '.trans("$string_file.at").' '.trans("$string_file.store");
                break;
            case "9": // order in process
                $title = $segment_name . ' ' . $lang_order . ' ' .trans("$string_file.processed");
                $message = $segment_name . ' ' . $lang_order .' '.$order_id.' ' .trans("$string_file.has").' '.trans("$string_file.been").' '.trans("$string_file.processed");
                break;
            case "10": // order picked
                $title = $segment_name . ' ' . $lang_order . ' ' . trans("$string_file.picked");
                $message = trans_choice("$string_file.order_picked_message", 3, ['successfully' => $outlet]);
                break;
            case "12": // order auto Expired
                $title = $segment_name . ' ' . $lang_order . ' ' . $order_id . ' ' . trans("$string_file.cancelled");
                $message = trans_choice("$string_file.order_request_expired_message", 3, ['ID' => $order->merchant_order_id]);
                break;
            case "13":
                $title = $segment_name . ' ' . $lang_order . ' ' .$order_id ;
                $message = trans("$string_file.assigned").' '.trans("$string_file.to").' '.trans("$string_file.driver");
                break;
            case "16":
                $title = $segment_name . ' ' . $lang_order;
                $message = $lang_order." ".$order_id. " ".trans("$string_file.picked").' '.trans("$string_file.from").' '.trans("$string_file.laundry_outlet");
                break;
            case "17":
                $title = $segment_name . ' ' . $lang_order;
                $message = $lang_order." ".$order_id. " ".trans("$string_file.delivered").' '.trans("$string_file.to").' '.trans("$string_file.user");
                break;
            case "14":
                $title = $segment_name . ' ' . $lang_order . ' ' .$order_id ;
                $message = trans("$string_file.order").' '.trans("$string_file.completed");
                break;
            case "20":
                $title = $segment_name . ' ' . $lang_order . ' ' .$order_id ;
                $message = trans("$string_file.delay_order");
            break;    
        }
        $data['segment_data']['order_delivered'] = $order_delivered;
        $arr_param['user_id'] = $user_id;
        $arr_param['data'] = $data;
        $arr_param['message'] = $message;
        $arr_param['merchant_id'] = $merchant_id;
        $arr_param['title'] = $title; // notification title
        $arr_param['large_icon'] = "";
        Onesignal::UserPushMessage($arr_param);
        setLocal();
    }

    public function NotifyDriver($request, $arr_driver_id, $order): bool
    {
        $data['notification_type'] = $request->notification_type;
        $data['segment_type'] = $order->Segment->slag;
        $data['segment_sub_group'] = $order->Segment->sub_group_for_app; // its segment sub group for app
        $data['segment_group_id'] = $order->Segment->segment_group_id; // for handyman
        $order_status = $order->order_status;
        $order_id = "#".$order->merchant_order_id;
        $item = $order->Segment;

        $outlet = $order->LaundryOutlet->full_name;
        $order_number = $order->merchant_order_id;
        $merchant_id = $order->merchant_id;
        $time_format = $order->Merchant->Configuration->time_format;

        $large_icon = isset($item->Merchant[0]['pivot']->segment_icon) && !empty($item->Merchant[0]['pivot']->segment_icon) ? get_image($item->Merchant[0]['pivot']->segment_icon, 'segment', $merchant_id, true) : get_image($item->icon, 'segment_super_admin', NULL, false);
        $segment_name = !empty($item->Name($merchant_id)) ? $item->Name($merchant_id) : $item->slag;

        $segment_data = [];
        $time = "";
        $service_time_slot_detail = ServiceTimeSlotDetail::find($order->service_time_slot_detail_id);
        if (!empty($service_time_slot_detail)) {
            $start = strtotime($service_time_slot_detail->from_time);
            $start = $time_format == 2  ? date("H:i", $start) : date("h:i a", $start);
            $end = strtotime($service_time_slot_detail->to_time);
            $end =  $time_format == 2  ? date("H:i", $end) : date("h:i a", $end);
            $time = $start . "-" . $end;
        }

        $title = "";
        $message = "";
        if (!is_array($arr_driver_id)) {
            $arr_driver_id = [$arr_driver_id];
        }
        foreach ($arr_driver_id as $driver_id) {
            $driver = Driver::find($driver_id);
            setLocal($driver->language);

            // get string file
            $string_file = $this->getStringFile($merchant_id);
            $lang_order = trans("$string_file.order");
            $order_title = $order->Segment->Name($order->merchant_id) . ' ' . $lang_order;
            if ($order_status != 6) {
                $segment_data = [
                    "id" => $order->id,
                    "master_booking_id" => $order->id,
                    "generated_time" => $order->order_timestamp,
                    "highlights" => [
                        'name' => $order_title,
                        'number' => $order_number,
                        'payment_mode' => $order->PaymentMethod->MethodName($merchant_id) ? $order->PaymentMethod->MethodName($merchant_id) : $order->PaymentMethod->payment_method,
                        'description' => trans("$string_file.total") . ' ' . $order->quantity . ' ' . trans("$string_file.items"),
                        'price' => (isset($order->CountryArea->Country) ? $order->CountryArea->Country->isoCode : "") . ' ' . $order->final_amount_paid,
                        'service_type' => $order->ServiceType->ServiceName($merchant_id)
                    ],
                    "pickup_details" => [
                        "header" => $order->LaundryOutlet->full_name,
                        "locations" => [[
                            "lat" => $order->LaundryOutlet->latitude,
                            "lng" => $order->LaundryOutlet->longitude,
                            "address" => $order->LaundryOutlet->address,
                        ]],
                    ],
                    "drop_details" => [
                        "header" => $order->User->first_name . ' ' . $order->User->last_name,
                        "locations" => [[
                            "lat" => $order->drop_latitude,
                            "lng" => $order->drop_longitude,
                            "address" => $order->drop_location,
                        ]],
                    ],
                    "timer" => 60 * 1000,

                    "cancel_able" => true,
                    "status" => $order_status,
                    'customer_details' => [
                        [
                            "customer_details_visibility"=> false
                        ]
                    ],
                    'package_details' => (object) [],
                    'segment_type' => $order->Segment->slag,
                    'timing' => date(('d/M (D)'), strtotime($order->order_date)) . " " . $time,
                    'additional_notes'=> []
                ]; // notification data
            }

            switch ($order_status) {
                case "1":
                    $title = trans("$string_file.new") . ' ' . $segment_name . ' ' . $lang_order;
                    $message = trans("$string_file.new_order_driver_message");
                    break;
                case "2":
                case "5":
                case "8":
                    $title = $segment_name . ' ' . $lang_order . ' ' . trans("$string_file.cancelled");
                    $message = trans("$string_file.order_cancelled")." ".$order_id;
                    break;
                case "6":
                case "15":
                    $title = $segment_name . ' ' . $lang_order;
                    $message = $lang_order." ".$order_id. " ".trans("$string_file.accepted");
                    break;
                case "7":
                    $title = $segment_name . ' ' . $lang_order;
                    $message = $lang_order." ".$order_id. " ".trans("$string_file.delivered").' '.trans("$string_file.at").' '.trans("$string_file.store");
                    break;
                case "10":
                    $title = $segment_name . ' ' . $lang_order;
                    $message = $lang_order." ".$order_id. " ".trans("$string_file.has").' '.trans("$string_file.been").' '.trans("$string_file.accepted");
                    break;
                case "16":
                    $title = $segment_name . ' ' . $lang_order;
                    $message = $lang_order." ".$order_id. " ".trans("$string_file.picked").' '.trans("$string_file.from").' '.trans("$string_file.laundry_outlet");
                    break;
                case "17":
                    $title = $segment_name . ' ' . $lang_order;
                    $message = $lang_order." ".$order_id. " ".trans("$string_file.delivered").' '.trans("$string_file.to").' '.trans("$string_file.user");
                    break;
                case "14":
                    $title = $segment_name . ' ' . $lang_order;
                    $message = $lang_order.' '.trans("$string_file.completed");
                    break;
            }
            $data['segment_data'] = $segment_data;
            $arr_param = ['driver_id' => $driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => $large_icon];
            Onesignal::DriverPushMessage($arr_param);
        }
        setLocal();
        return true;
    }

    public function acceptLaundryOutletOrder($request, $outlet)
    {
        $validator = Validator::make($request->all(), [
            'laundry_outlet_order_id' => [
                'required',
                'integer',
                Rule::exists('laundry_outlet_orders', 'id')->where(function ($query) {
                    $query->where('order_status', 1);
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new \Exception($errors[0]);
        }
        DB::beginTransaction();
        try {
            $order = LaundryOutletOrder::Find($request->laundry_outlet_order_id);
            $merchant_id = $order->merchant_id;
            $string_file = $this->getStringFile($merchant_id);
            if (!empty($order)) {
                $request_status = 7; //Order in Processing
                if ($order->order_status == 1) {
                    $order->order_status = $request_status;
                    $order->save();

                    $message = trans("$string_file.order_accepted_by_laundry");
                    if($order->ServiceType->type == 6){ //not calling LaundryOrderTransaction because this is called in accept reject in type == 1 case
                        $this->LaundryOrderTransaction($request, $order);
                    }
                    $this->saveLaundryOrderStatusHistory($request, $order);
                } else {
                    $message = trans("$string_file.order_not_found");
                    throw new \Exception($message);
                }
            } else {
                $message = trans("$string_file.not_found");
                throw new \Exception($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw new Exception($e->getMessage());
        }
        DB::commit();
        /**send notification to user*/
        $this->NotifyUser($order, $message);
        return $message;
    }



    public function cancelOrderByLaundryOutlet($request, $outlet): string
    {
        DB::beginTransaction();
        try {
            $order = LaundryOutletOrder::Find($request->laundry_outlet_order_id);
            $string_file = $this->getStringFile(NULL, $order->Merchant);
            if (!empty($order->id)) {
                $merchant_id = $outlet->merchant_id;
                $string_file = $this->getStringFile($merchant_id);
                $request->request->add(['id' => $order->id, 'notification_type' => "CANCEL_ORDER", 'latitude' => $outlet->latitude, 'longitude' => $outlet->longitude]);
                $order->order_status = 3;
                /**
                 * if payment done when order placed then credit to user wallet
                 **/
                if ($order->payment_status == 1) {
                    $amount = $order->final_amount_paid;
                    $order->refund = 1;
                    $paramArray = [
                        'laundry_outlet_order_id' => $order->id,
                        'amount' => $amount,
                        'user_id' => $order->user_id,
                        'narration' => 2,
                    ];
                    WalletTransaction::UserWalletCredit($paramArray);
                }
                $order->save();

                // save status history
                $this->saveLaundryOrderStatusHistory($request, $order);

                if (!empty($order->driver_id)) {
                    $driver = $order->Driver;
                    $driver->free_busy = 2; // driver is free now
                    $driver->save();

                    $arr_driver_id = [$order->driver_id];
                    $this->NotifyDriver($request, $arr_driver_id, $order);
                }
            } else {
                $message = trans("$string_file.not_found");
                throw new \Exception($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception($e->getMessage());
        }
        DB::commit();
        /**send notification to user*/
        $this->NotifyUser($order);
        return  trans_choice("$string_file.order_cancelled_by_message", ['ID' => $order->merchant_order_id, '.' => $order->LaundryOutlet->full_name]);
    }

    public function LaundryOrderProcessing($request, $outlet): string
    {
        $validator = Validator::make($request->all(), [
            'id' => [
                'required',
                'integer',
                Rule::exists('laundry_outlet_orders', 'id')->where(function ($query) {
                    $query->whereIn('order_status', [7]); //can be only after arriving at store
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new \Exception($errors[0]);
        }
        DB::beginTransaction();
        try {
            $order = LaundryOutletOrder::Find($request->id);
            $merchant_id = $order->merchant_id;
            $string_file = $this->getStringFile($merchant_id);
            if (!empty($order)) {
                $order_number = $order->merchant_order_id;
                $outlet_name = $outlet->full_name;
                if (($order->order_status == 7)  && !empty($order->id)) {

                    $order->estimate_delivery_time = carbon::parse($request->estimate_delivery_time)->format("Y-m-d H:i");
//
                    if ($order->ServiceType->type == 6) // self pickup service
                    {
                        $order->otp_for_pickup =  rand(1000, 9999);
                        $order->order_status = 9; //send for manual verification at outlet from user
                    }
                    else{
                        $order->order_status = 13; // goes to pending order delivery
                    }
                    $order->save();
                    $this->saveLaundryOrderStatusHistory($request, $order);

                } else {
                    $message = trans("$string_file.order_not_found");
                    throw new \Exception($message);
                }
            } else {
                $message = trans("$string_file.not_found");
                throw new \Exception($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw new Exception($e->getMessage());
        }
        DB::commit();
        /**send notification to user*/
        $this->NotifyUser($order);
        // send new order request to restaurant panel
        $success_message = trans_choice("$string_file.order_in_process_driver_message", 3, ['ID' => $order_number, '.' => $outlet_name]);
        return $success_message;
    }


    public function LaundryOrderOTPVerification($request): array
    {
        $validator = Validator::make($request->all(), [
            'laundry_outlet_order_id' => [
                'required',
                'integer',
                Rule::exists('laundry_outlet_orders', 'id')->where(function ($query) {
                    $query->whereIn('order_status', [6,9,16]); //6 for driver 9 for outlet //16 for drop driver
                }),
            ],
            'otp' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new \Exception($errors[0]);
        }
        DB::beginTransaction();
        try {
            $order = LaundryOutletOrder::find($request->laundry_outlet_order_id);
            $string_file = $this->getStringFile($order->merchant_id);
            if (!empty($order)) {
                $order_history = array_column(json_decode($order->order_status_history, true), 'order_status');
                $merchant_id = $order->merchant_id;
                $string_file = $this->getStringFile($merchant_id);
                if (in_array(9, $order_history) || in_array(6, $order_history)) {
                    if($order->user_confirmed_otp_for_pickup == 2 && $order->order_status == 16){ //when driver Ask Otp for user final delivery
                        if ($order->otp_for_pickup == $request->otp) {
                            $order->user_confirmed_otp_for_pickup = 1;
                            $order->otp_for_pickup = NULL;
                            $order->order_status = 17;
                            $order->save();

                            $this->NotifyUser($order);
                            $request=$request->merge(["notification_type"=>"OTP_VERIFY"]);
                            $this->NotifyDriver($request, $order->driver_id, $order);
                            $this->saveLaundryOrderStatusHistory($request, $order);
                        }
                    }
                    elseif ($order->user_confirmed_otp_for_pickup == 2 && $order->order_status == 9) {  //when user Visits Laundry Outlet
                        if ($order->otp_for_pickup == $request->otp) {
                            $order->user_confirmed_otp_for_pickup = 1;
                            $order->otp_for_pickup = NULL;
                            if($order->ServiceType->type == 1){
                                $order->order_status = 15; // Driver has visited store and pickup done
                            }
                            else{
                                $order->order_status = 13; // User Has visited Store and Provided Otp for his delivery for pickup
                            }
                            $order->save();
                            $this->sendPushNotificationToLaundryOutlet($order, null, "OTP_VERIFY");
                            $this->saveLaundryOrderStatusHistory($request, $order);
                        } else {
                            throw new \Exception(trans("$string_file.invalid_otp_try_again"));
                        }
                    }
                    elseif($order->driver_confirmed_otp_for_pickup == 2 && $order->order_status == 6){ // when Driver has visited to User and picked up clothes from user
                        if ($order->otp_for_pickup == $request->otp) {
                            $order->driver_confirmed_otp_for_pickup = 1;
                            $order->otp_for_pickup = NULL;
                            $order->order_status = 10;
                            $order->save();

                            $this->NotifyUser($order);
                            $request=$request->merge(["notification_type"=>"OTP_VERIFY"]);
                            $this->NotifyDriver($request, $order->driver_id, $order);
                            $this->saveLaundryOrderStatusHistory($request, $order);
                        }
                        else {
                            throw new \Exception(trans("$string_file.invalid_otp_try_again"));
                        }
                    }
                    else {
                        throw new \Exception(trans("$string_file.otp_already_verify"));
                    }
                } else {
                    throw new \Exception(trans("$string_file.process_order_before_verification"));
                }
            } else {
                $message = trans("$string_file.not_found");
                throw new \Exception($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception($e->getMessage());
        }
        DB::commit();
        return [
            'message' => trans("$string_file.otp_verified"),
            'order' => $order,
        ];
    }



    public function LaundryOrderTransaction($request, $order,$order_earning_type = 2)
    {
        if (!empty($order->id)) {

            $order_transaction = BookingTransaction::where([['laundry_outlet_order_id', '=', $order->id]])->first();
            if (empty($order_transaction)) {
                $order_transaction = new BookingTransaction;
                $order_transaction->merchant_id = $order->merchant_id;
            }

            $driver_commission = 0;
            if (in_array($order->order_status, [6,7,15])) {  // 6->driver accepted 7->self case 15->drop driver accepted
                $before_discount = $order->cart_amount + $order->delivery_amount + $order->tax;
                $order_transaction->laundry_outlet_order_id = $order->id;
                $order_transaction->date_time_details = date('Y-m-d H:i:s');
                $order_transaction->sub_total_before_discount = $before_discount;
                $order_transaction->discount_amount = $order->discount_amount;
                $order_transaction->tax_amount = $order->tax;
                $order_transaction->booking_fee = $order->cart_amount;
                $order_transaction->tip = $order->tip_amount;
                $order_transaction->cash_payment = $order->final_amount_paid;
                $order_transaction->customer_paid_amount = $order->final_amount_paid;
                $order_transaction->commission_type = $order->LaundryOutlet->commission_type;
                if ($order->ServiceType->type == 1) // home delivery (two drivers involved in home delivery case , checked accordingly)
                {
                    $bill_details = ($order->order_status == 6) ? json_decode($order->bill_details, true) : json_decode($order->drop_bill_details, true);
                    $driver_bill = $bill_details['driver'] ?? [];
                    if (!empty($driver_bill)) {
                        $driver_commission = $driver_bill['pick_up_fee'] + $driver_bill['drop_off_fee'] + $driver_bill['slab_amount'];
                    }
                    $delivery_boy_total_commission = $driver_commission + $order->tip_amount + $order->discount_amount;
                    if($order->order_status ==  6){
                        $order_transaction->driver_total_payout_amount = $delivery_boy_total_commission;
                    }
                    else{
                        $order_transaction->drop_driver_total_payout_amount = $delivery_boy_total_commission;
                    }
                }
            } elseif ($order->order_status == 14) {
                // merchant vs outlet commission
                $commission = $order->LaundryOutlet->commission;
                $cart_amount = $order->cart_amount;

                // merchant commission amount
                $merchant_cart_commission_amount = 0;
                if ($order->LaundryOutlet->commission_method == 1) { // Flat Commission
                    if ($cart_amount >= $commission) {
                        $merchant_cart_commission_amount = $commission;
                    } else {
                        $merchant_cart_commission_amount = $cart_amount;
                    }
                } elseif ($order->LaundryOutlet->commission_method == 2) { // percentage
                    $merchant_cart_commission_amount = ($commission * $cart_amount) / 100;
                }

                //outlet commission amount
                $outlet_commission_amount = $cart_amount - $merchant_cart_commission_amount;
                $merchant_total_commission = (($merchant_cart_commission_amount + $order->delivery_amount) - $order->discount_amount);

                // for self pickup driver commission will be zero
                $order_transaction->company_earning = $merchant_cart_commission_amount;
                $order_transaction->laundry_outlet_earning = $outlet_commission_amount;

                // total paid amount to driver merchant and business segment
                $order_transaction->company_gross_total = ($merchant_total_commission); // including delivery charge
                $order_transaction->laundry_outlet_total_payout_amount = $outlet_commission_amount + $order->tax;
                $order_transaction->ride_type_earning = $order_earning_type; // it's to check earning of driver by commission or subscription
            }
            if($order->ServiceType->type == 1){
                if($order->order_status == 6){
                    $order_transaction->driver_earning = $driver_commission;
                }
                else{
                    $order_transaction->drop_driver_earning = $driver_commission;
                }
            }
            $order_transaction->save();

            return $order_transaction;
        }
        return false;
    }

    public function LaundryOrderSettlement(LaundryOutletOrder $order): void
    {
        DB::beginTransaction();
        try {
            // If payment done
            if ($order->payment_status == 1 || ($order->Merchant->Configuration->laundry_billing == 1 && $order->ServiceType->type != 6)) {
                $order_transaction = BookingTransaction::where('laundry_outlet_order_id', $order->id)->first();
                $customer_paid_amount = $order_transaction->customer_paid_amount;
                $driver_commission = $order->order_status == 7 ? $order_transaction->driver_total_payout_amount : $order_transaction->drop_driver_total_payout_amount;
                $outlet_cart_commission_amount = $order_transaction->laundry_outlet_total_payout_amount;
                $merchant_earning = $order_transaction->company_earning;
                
                if ($order->ServiceType->type == 1) // home delivery by delivery boy
                {
                    $array_param = array(
                        'laundry_outlet_order_id' => $order->id,
                        'driver_id' => $order->drop_driver_id,
                        'payment_method_type' => $order->PaymentMethod->payment_method_type,
                    );

                    if ($order->payment_method_id == 1 || $order->payment_method_id == 5) // cash or swipe card payment
                    {
                        // Debit Driver wallet if customer paid via cash or swipe card
                        $array_param['amount'] = $customer_paid_amount;
                        $array_param['wallet_status'] = 'DEBIT';
                        $array_param['narration'] = 13;
                        $array_param['driver_id'] = $order->order_status == 7 ? $order->driver_id : $order->drop_driver_id;
                        $this->makeDriverWalletTransaction($order,$array_param);
                    }

                    // Credit Driver wallet with commission
                    $array_param['amount'] = $driver_commission;
                    $array_param['wallet_status'] = 'CREDIT';
                    $array_param['narration'] = 14;
                    $array_param['driver_id'] = $order->order_status == 7 ? $order->driver_id : $order->drop_driver_id;
                    $this->makeDriverWalletTransaction($order,$array_param);

                    $paramArray = array(
                        'laundry_outlet_id' => $order->laundry_outlet_id,
                        'laundry_outlet_order_id' => $order->id,
                        'amount' => $outlet_cart_commission_amount,
                        'narration' => 2,
                    );
                    WalletTransaction::LaundryOutletWalletCredit($paramArray);
                } elseif ($order->ServiceType->type == 6) // self pick by user
                {
                    if ($order->payment_method_id == 1 || $order->payment_method_id == 5) // cash or swipe card payment
                    {
                        $paramArray = array(
                            'laundry_outlet_id' => $order->laundry_outlet_id,
                            'booking_id' => null,
                            'laundry_outlet_order_id' => $order->id,
                            'amount' => $merchant_earning,
                            'narration' => 6,
                        );
                        WalletTransaction::LaundryOutletWalletDebit($paramArray);
                    } else // online payment like card, payment gateway, wallet.
                    {
                        $paramArray = array(
                            'laundry_outlet_id' => $order->laundry_outlet_id,
                            'laundry_outlet_order_id' => $order->id,
                            'amount' => $outlet_cart_commission_amount,
                            'narration' => 2,
                        );
                        WalletTransaction::LaundryOutletWalletCredit($paramArray);
                    }
//
//                    //check commsion from store when self pickup
//                    if ($order->LaundryOutlet->commission_method == 1) {
//                        $commission_cut = round($order->LaundryOutlet->commission, 2);
//                    } else {
//                        $commission_cut_per = ($order->final_amount_paid * $order->LaundryOutlet->commission) / 100;
//                        $commission_cut = round($commission_cut_per, 2);
//                    }
//
//                    if ($order->LaundryOutlet->wallet_amount < $commission_cut) {
//                        throw new \Exception(trans('admin.wallet_balance_low'));
////                        return redirect()->back()->withErrors(trans('admin.wallet_balance_low'));
//                    }
//                    $paramArray = array(
//                        'laundry_outlet_id' => $order->LaundryOutlet->id,
//                        'booking_id' => null,
//                        'amount' => $commission_cut,
//                        'narration' => 6,
//                        'laundry_outlet_order_id' => $order->id ?? ""
//                    );
//                    WalletTransaction::LaundryOutletWalletDebit($paramArray);
                }
            } else {
                // when 1st driver drops order items at store
                if($order->payment_status != 1 && $order->ServiceType->type == 1 && $order->order_status == 7 && $order->Merchant->Configuration->laundry_billing == 2){
                    $order_transaction = BookingTransaction::where('laundry_outlet_order_id', $order->id)->first();
                    $array_param['laundry_outlet_order_id'] = $order->id;
                    $array_param['driver_id'] = $order->driver_id;
                    $array_param['amount'] = $order_transaction->driver_total_payout_amount;
                    $array_param['wallet_status'] = 'CREDIT';
                    $array_param['narration'] = 14;
                    $this->makeDriverWalletTransaction($order,$array_param);
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        DB::commit();
    }

    public function sendPushNotificationToLaundryOutlet($order = NULL, $cashout_req = null, $for = "ORDER"): bool
    {
        $outlet = !empty($order) ? $order->LaundryOutlet : $cashout_req->LaundryOutlet;
        $merchant_id = $outlet->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $player_id = array_pluck($outlet->webOneSignalPlayerId->where('status', 1), 'player_id');
        $segment_name = !empty($order->Segment->Name($merchant_id)) ? $order->Segment->Name($merchant_id) : $order->Segment->slag;
        $data = ['id' => $order->id];
        $message="";
        $title="";
        $onesignal_redirect_url="";
        $lang_order = trans("$string_file.order");

        if(!empty($order) && $for == "ORDER"){
            $onesignal_redirect_url = route('laundry-outlet.today-order');
            // upcoming orders
            $order_date = $order->order_date;
            if (!empty($order_date) && $order_date > date("Y-m-d")) {
                $onesignal_redirect_url = route('laundry-outlet.upcoming-order');
            }
            $title = trans("$string_file.new") . ' ' . $segment_name . ' ' . $lang_order;
            $message = trans("$string_file.new_order_driver_message");
        }
        elseif(!empty($order) && $for == "OTP_VERIFY"){
            $title = trans("$string_file.otp") . ' ' . trans("$string_file.verified") . ' '.$lang_order.' #' . $order->merchant_order_id;
            $message = trans("$string_file.otp")." ".trans("$string_file.verified")." ".trans("$string_file.successfully");
            $onesignal_redirect_url = trans("laundry-outlet.pending-order-delivery");
        }
        elseif(!empty($order) && $for == "ORDER_COMPLETED"){
            $onesignal_redirect_url = route('laundry-outlet.completed-order');
            $title = $segment_name . ' ' . $lang_order. "#". $order->merchant_order_id;
            $message = trans("$string_file.order") . ' ' . trans("$string_file.completed");
        }
        elseif(!empty($order) && $for == "DELAY"){
            $onesignal_redirect_url = route('laundry-outlet.pending-order-delivery');
            $title = $segment_name . ' ' . $lang_order. "#". $order->merchant_order_id;
            $message = trans("$string_file.delay_drop");
        }
        elseif($cashout_req){
            $onesignal_redirect_url = route('laundry-outlet.cashouts');
            $title = trans("$string_file.new") . ' ' . trans("$string_file.cashout_request") . ' #' . $cashout_req->id;
            $message = trans("$string_file.new")." ".trans("$string_file.cashout_request")." ".trans("$string_file.raised");
            $data = ['cashout_id' => $cashout_req->id];
        }
        Onesignal::MerchantWebPushMessage($player_id, $data, $message, $title, $merchant_id, $onesignal_redirect_url, "LAUNDRY_OUTLET_ORDER");
        return true;
    }

    public function LaundryServiceStatus($order): array
    {
        // Decode order status history
        
        $string_file = $this->getStringfile(null, $order->Merchant);
        $return_status = [];
        $order_status_history = json_decode($order->order_status_history);


        if (!empty($order->delay_date_time)) {
                $new_estimate_drop_date_time = \Carbon\Carbon::parse($order->delay_date_time)->format('d-m-Y h:i A');
            } else {
                $new_estimate_drop_date_time = '';
            }
    
        // Define all status groups with default values
        if($order->ServiceType->type == 1){
            $status_definitions = [
                1 => [trans("$string_file.order_has_placed")],
                6 => [trans("$string_file.order_accepted_by_laundry"), trans("$string_file.waiting_to_assign_driver"), trans("$string_file.driver_assigned")],
                10 => [trans("$string_file.order_picked_by_driver")],
                7 => [trans("$string_file.arrived_at_outlet")],
                13 => [trans("$string_file.in_process")],
                15 => [trans("$string_file.dispatched")],
                16 => [trans("$string_file.out_for_delivery")],
                14 => [trans("$string_file.completed")],
               
            ];
            
            if (!empty($order->delay_date_time)) {
        $new_status_definitions = [];
        foreach ($status_definitions as $key => $value) {
            $new_status_definitions[$key] = $value;
            if ($key == 13) {
                $new_status_definitions[20] = [trans("$string_file.delay_order").". New drop estimate datetime [".$new_estimate_drop_date_time."]"];
            }
        }
        $status_definitions = $new_status_definitions;
    }
            
        }
        else{
            $status_definitions = [
                1 => [trans("$string_file.order_has_placed")],
                7 => [trans("$string_file.arrived_at_outlet")],
                9 => [trans("$string_file.pending_pickup_verification")],
                13 => [trans("$string_file.in_process")],
                14 => [trans("$string_file.completed")],
                20 => [trans("$string_file.delay_order")],
            ];
            
            
            if (!empty($order->delay_date_time)) {
        $new_status_definitions = [];
        foreach ($status_definitions as $key => $value) {
            $new_status_definitions[$key] = $value;
            if ($key == 15) {
                $new_status_definitions[13] = [trans("$string_file.delay_order").". New drop estimate datetime [".$new_estimate_drop_date_time."]"];
            }
        }
        $status_definitions = $new_status_definitions;
    }
        }


        // Initialize the $arr_status array with default structure
        $arr_status = [];
        foreach ($status_definitions as $key => $statuses) {
            foreach ($statuses as $status_text) {
                $arr_status[$key][] = [
                    "status_text" => $status_text,
                    "order_timestamp" => "",
                    "status" => false,
                ];
            }
        }
        

        // Update $arr_status based on $order_status_history
        foreach ($order_status_history as $history) {
            $arr_status[$history->order_status][0]['order_timestamp'] = (string)$history->order_timestamp;
            $arr_status[$history->order_status][0]['status'] = true;
            if($history->order_status == 6){
                $arr_status[$history->order_status][1]['order_timestamp'] = (string)$history->order_timestamp;
                $arr_status[$history->order_status][1]['status'] = true;
            }
            if($history->order_status == 6 && !empty($order->driver_id)){
                $arr_status[$history->order_status][2]['order_timestamp'] = (string)$history->order_timestamp;
                $arr_status[$history->order_status][2]['status'] = true;
            }
        }
        $status_arr = array_values($arr_status);

        foreach($status_arr as $arr){
            foreach($arr as $k => $v){
                array_push($return_status, $v);
            }
        }
        return $return_status;
    }


    public function LaundryPriceDetailHolderArray($receipt): array
    {
        $result = [];
        foreach ($receipt as $key => $value) {
            $result[] = [
                "highlighted_text" => ucfirst(str_replace('_', ' ', $key)),
                "highlighted_text_color" => "333333",
                "highlighted_style" => "BOLD",
                "highlighted_visibility" => true,
                "small_text" => $key,
                "small_text_color" => "333333",
                "small_text_style" => "",
                "small_text_visibility" => false,
                "value_text" => $value,
                "value_text_color" => "333333",
                "value_text_style" => "",
                "value_textvisibility" => true,
            ];
        }
        return $result;
    }


    function makeDriverWalletTransaction($order,$array_param): bool
    {
        $driverPayment = new CommonController();
        $driverPayment->DriverRideAmountCredit($array_param);
        return true;
    }

}