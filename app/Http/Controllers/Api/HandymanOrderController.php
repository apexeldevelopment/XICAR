<?php

namespace App\Http\Controllers\Api;

use App\Events\SendUserHandymanInvoiceMailEvent;
use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\ReferralController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Controllers\PaymentMethods\Payment;
use App\Models\BookingRating;
use App\Models\BookingRequestDriver;
use App\Models\BookingTransaction;
use App\Models\BusinessSegment\Order;
use App\Models\Driver;
use App\Models\EmailConfig;
use App\Models\EmailTemplate;
use App\Models\HandymanChargeType;
use App\Models\HandymanCommission;
use App\Models\Onesignal;
use App\Models\Outstanding;
use App\Models\ReferralDiscount;
use App\Models\User;
use App\Traits\AreaTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Traits\HandymanTrait;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Models\HandymanOrder;
use DB;
use App\Traits\MailTrait;
use App\Traits\DriverTrait;
use View;
use App\Traits\ImageTrait;
use App\Models\DriverGallery;
use App\Models\LanguageHandymanChargeType;

class HandymanOrderController extends Controller
{
    //
    use HandymanTrait, ApiResponseTrait, AreaTrait, MailTrait, ImageTrait, MerchantTrait, DriverTrait;

    public function getOrders(Request $request)
    {
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $time_format = $driver->Merchant->Configuration->time_format;
        $merchant_id = $request->merchant_id;
        $request_fields = [
            // 'segment_id' => ['required', 'integer', Rule::exists('segments', 'id')->where(function ($query) {
            // }),],
            'type' => 'required|in:COMPLETED,CANCELLED,REJECTED,ALL,PENDING,TODAY,TOMORROW,THIS_WEEK',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try {
            //            $request->request->add(['area_id'=>$driver->country_area_id]);
            $req_param['merchant_id'] = $merchant_id;
            $arr_order_status = $this->getHandymanBookingStatus($req_param, $string_file);
            $arr_orders = $this->getHandymanOrders($request, $string_file);
            $utc_timzone = date_default_timezone_get();

            $arr_orders = $arr_orders->map(function ($item, $key) use ($merchant_id, $arr_order_status, $time_format, $string_file,$utc_timzone,$driver) {
                $status_text = isset($arr_order_status[$item->order_status]) ? $arr_order_status[$item->order_status] : "";
                $expiry_time_text = "";
                if ($item->order_status == 1) {
                    date_default_timezone_set($driver->CountryArea['timezone']);
                    $expiry_time_text = $this->calculateExpireTime(strtotime($item->ServiceTimeSlotDetail->from_time), $item->booking_timestamp, "", $string_file); // "1 hour 25 min";
                    date_default_timezone_set($utc_timzone);
                }
                $time = "";
                if (isset($item->ServiceTimeSlotDetail)) {
                    $start = strtotime($item->ServiceTimeSlotDetail->from_time);
                    $start = $time_format == 2 ? date("H:i", $start) : date("h:i a", $start);
                    $end = strtotime($item->ServiceTimeSlotDetail->to_time);
                    $end = $time_format == 2 ? date("H:i", $end) : date("h:i a", $end);
                    $time = $start . "-" . $end;
                }
                $segment_icon = isset($item->Segment->MerchantSegment($merchant_id)['pivot']['segment_icon']) ? $item->Segment->MerchantSegment($merchant_id)['pivot']['segment_icon'] : NULL;

                $dispute_image_urls = [];
                if (!empty($item->dispute_images)){
                    $images_arr = json_decode($item->dispute_images, true);
                    foreach ($images_arr as $image){
                        $dispute_image_urls[] = get_image($image, 'booking_images', $merchant_id);
                    }
                }

                return array(
                    'id' => $item->id,
                    'date' => date('d M,Y', strtotime($item->booking_date)),
                    'time' => $time,
                    'status' => $item->order_status,
                    'status_text' => $status_text,
                    'user_name' => $item->User->first_name . ' ' . $item->User->last_name,
                    'user_address' => $item->drop_location,
                    'segment_name' => $item->Segment->Name($merchant_id),
                    'segment_image' => !empty($segment_icon) ? get_image($segment_icon, 'segment', $item->merchant_id, true) : get_image($item->Segment->icon, 'segment_super_admin', NULL, false),
                    'expiry_time_text' => $item->order_status == 1 ? $expiry_time_text : "",
                    'is_disputed' => (!empty($item->dispute_images) && !empty($item->dispute_message)) ? true : false,
                    'dispute_message' => !empty($item->dispute_message) ? $item->dispute_message : "",
                    'dispute_images' => $dispute_image_urls,
                );
            });
            return $this->successResponse(trans("$string_file.data_found"), $arr_orders);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    //@ayush (get commission string for app config based)
    public static function getCommissionString($order, $service_charges,$currency, $string_file){
        $order_commission = HandymanCommission::where([['merchant_id', '=', $order->merchant_id], ['country_area_id', '=', $order->country_area_id], ['segment_id', '=', $order->segment_id]])->first();

        $commission_method = NULL;
        $commission_amount = NULL;
        $company_cut = NULL;

        //Service based commission
        if(isset($order_commission) && $order_commission->commission_pricing_type == 2){
            $services = [];
            foreach($order->HandymanOrderDetail as $order_detail)
                array_push($services, $order_detail->service_type_id);

            $commission_details = $order_commission->HandymanCommissionDetail->whereIn("service_type_id", $services);

            foreach( $commission_details as $detail)
                $commission_amount += $detail->amount;
        }

        if (!empty($order_commission)) {
            $commission_method = $order_commission->commission_method;
            $commission_amount = (empty($commission_amount) && $order_commission->commission_pricing_type == 1 )? $order_commission->commission : $commission_amount;
        }
        if(!empty($order->tax_after_dispute) && !empty($order->is_dispute) ){
            $service_charges_included_tax =  $order->final_amount_paid;
            $service_charges = round_number($service_charges_included_tax - $order->tax_after_dispute,2);
        }
        if ($commission_method == 1) {  // 1:Flat commission per Ride (==OR==) 2:Percentage of Net Bill (before tax)
            if ($commission_amount > $service_charges) {
                $company_cut = $service_charges;
            } else {
                $company_cut = $commission_amount;
            }
        } else {
            $company_cut = ($service_charges * $commission_amount) / 100;
        }

        return ['key' => "* ".trans("$string_file.comission_on")." ".$currency." ".$service_charges." ".trans("$string_file.comission_cut")." ".$currency." ".$company_cut, 'value' => "", 'color' => "757575", 'bold' => false];
    }

    // get order data function
    public function getOrderData($request, $order = NULL)
    {
        try {
            $driver = $request->user('api-driver');
            $time_format = $driver->Merchant->Configuration->time_format;
            $merchant_id = $driver->merchant_id;
            $string_file = $this->getStringFile($merchant_id);
            if (empty($order)) {
                $handyman = new HandymanOrder;
                $order = $handyman->getOrder($request);
            }
            $req_param['merchant_id'] = $merchant_id;
            $arr_order_status = $this->getHandymanBookingStatus($req_param, $string_file);
            $status_text = isset($arr_order_status[$order->order_status]) ? $arr_order_status[$order->order_status] : "";


            $currency = $driver->Country->isoCode;
            $arr_payment_details = [];
            if ($order->price_type == 2 && $order->order_status != 7) {
                $service_charges = $currency .' '.$order->hourly_amount . ' ' . trans("$string_file.hourly");
                $service_amount = ['key' => trans("$string_file.service_charges"), 'value' => $service_charges, 'color' => "757575", 'bold' => true];
                $payment_note = ['key' => trans("$string_file.note") . ' : ', 'value' => trans("$string_file.handyman_order_payment"), 'color' => "757575", 'bold' => true];
                array_push($arr_payment_details, $service_amount, $payment_note);
            } else {
                $service_charges_included_tax = $order->minimum_booking_amount > $order->total_booking_amount ? $order->minimum_booking_amount : $order->total_booking_amount;
                $service_charges = round_number($service_charges_included_tax - $order->tax,2);
                if(!empty($order->discount_amount)){
                    $service_charges = round_number($service_charges + $order->discount_amount,2);
                }

                $booking_amount_str = str_replace(':user_name', $driver->first_name, trans("$string_file.booking_amount"));
                $booking_amount_str = str_replace(':amount', $currency .' '.$service_charges, $booking_amount_str);
                $service_amount = [
                    'key' => ($order->payment_method_id == 1) ? $booking_amount_str : trans("$string_file.booking").' '.trans("$string_file.amount"),
                    'value' => $currency .' '.$service_charges, 'color' => "757575", 'bold' => true
                ];
                $service_tax = ['key' => trans("$string_file.tax"), 'value' => $currency .' '.$order->tax, 'color' => "757575", 'bold' => true];
                array_push($arr_payment_details, $service_amount, $service_tax);
                $additional_charges = [];
                if (!empty($order->extra_charges_details)) {
                    $arr_details = json_decode($order->extra_charges_details, true);
                    $locale = \App::getLocale();
                    foreach ($arr_details as $key => $value) {

                        $charge_type = LanguageHandymanChargeType::where([['handyman_charge_type_id', '=', $value['id']], ['merchant_id', '=', $order->merchant_id], ['locale', '=', $locale]])->first();
                        $additional_charges = [
                            'key' => $charge_type->charge_type, 'value' => $currency .' '.$value['amount'], 'color' => "757575", 'bold' => false
                        ];
                        array_push($arr_payment_details, $additional_charges);
                    }
                }
                if (!empty($order->promo_code_id)) {
                    $discount_amount = ['key' => trans("$string_file.discount"), 'value' => $currency .' '.$order->discount_amount, 'color' => "757575", 'bold' => true];
                    array_push($arr_payment_details, $discount_amount);
                }
                
                if (!empty($order->is_dispute)) {
                    
                    $previous_amount = ['key' => trans("$string_file.previous_booking_amount"), 'value' => $currency .' '.$service_charges, 'color' => "757575", 'bold' => true];
                    $service_tax = ['key' => trans("$string_file.tax"), 'value' => $currency .' '.$order->tax_after_dispute, 'color' => "757575", 'bold' => true];
                    $dispute_settled_amount = ['key' => trans("$string_file.dispute_settled_amount"), 'value' => $currency .' '.$order->dispute_settled_amount, 'color' => "757575", 'bold' => true];
                    if ($order->is_dispute == 1 && !empty($order->dispute_settled_amount)) {
                        $arr_payment_details = [
                            $previous_amount,
                            $dispute_settled_amount,
                            $service_tax,
                        ];
                    }
                }
                
                $total_amount = ['key' => trans("$string_file.total_amount"), 'value'
                => $currency .' '.$order->final_amount_paid, 'color' => "757575", 'bold' => true];
                array_push($arr_payment_details, $total_amount);

                $total_pending_amount = $order->final_amount_paid;
                $paid_amount = NULL;
                if ($order->minimum_booking_amount_payment_status == 1 && $order->advance_payment_of_min_bill == 1 && $order->payment_status != 1) {
                    $total_pending_amount = $total_pending_amount - $order->minimum_booking_amount;
                    $paid_amount = $order->minimum_booking_amount;
                } elseif ($order->payment_status == 1) {
                    $paid_amount = $order->final_amount_paid;
                }
                if ($paid_amount > 0) {
                    $paid_amount_data = ['key' => trans("$string_file.paid_amount"), 'value' => $currency .' '.$paid_amount, 'color' => "2ECC71", "bold" => false];
                    array_push($arr_payment_details, $paid_amount_data);
                }
                if ($total_pending_amount > 0 && $order->payment_status != 1) {
                    $pending_amount = ['key' => trans("$string_file.pending_amount"), 'value' => $currency .' '.$total_pending_amount, 'color' => "E74C3C", "bold" => false];
                    array_push($arr_payment_details, $pending_amount);
                }
                //commission_string
                $commission_details = $this->getCommissionString($order, $service_charges,$currency, $string_file);
                array_push($arr_payment_details, $commission_details);
            }

            $handyman_bidding_enable = false;
            if(isset($order->Merchant->Configuration->handyman_bidding_enable) && $order->Merchant->Configuration->handyman_bidding_enable == 1){
                $handyman_bidding_enable = true;
            }

            $arr_charge_types = [];
            $charge_type_config = false;

            $dispute_image_urls = [];
            if (!empty($order->dispute_images)){
                $images_arr = json_decode($order->dispute_images, true);
                foreach ($images_arr as $image){
                    $dispute_image_urls[] = get_image($image, 'booking_images', $merchant_id);
                }
            }

            $order_detail = array(
                'id' => $order->id,
                'number' => $order->merchant_order_id,
                'segment_id' => $order->segment_id,
                'amount' => $order->final_amount_paid,
                'currency' => $currency,
                'status' => $order->order_status,
                'status_text' => $status_text,
                'paid_status' => $order->payment_status == 1 ? true : false,
                'rating_mandatory' => false,
                'payment_method_id' => $order->PaymentMethod->id,
                'payment_mode' => !empty($order->PaymentMethod->MethodName($order->Merchant->id)) ? $order->PaymentMethod->MethodName($order->Merchant->id) :$order->PaymentMethod->payment_method,
                'order_start_otp' => $order->Merchant->Configuration->handyman_order_start_otp == 1 ? true : false,
                'order_otp' => $order->order_otp,
                'expiring_text' => "",
                'segment_name' => $order->Segment->Name($merchant_id),
                'segment_image' => isset($order->Segment->Merchant[0]['pivot']->icon) && !empty($order->Segment->Merchant[0]['pivot']->icon) ? get_image($order->Segment->Merchant[0]['pivot']->icon, 'segment', $order->merchant_id, true) : get_image($order->Segment->icon, 'segment_super_admin', NULL, false),
                'payment_details' => $arr_payment_details,
                'additional_notes' => !empty($order->additional_notes) ? $order->additional_notes : "",
                'handyman_bidding_enable' => $handyman_bidding_enable,
                'bidding_amount' => isset($order->bidding_amount) && !empty($order->bidding_amount) ? $order->bidding_amount : "", // User Entered Amount for bidding
                'is_disputed' => (!empty($order->dispute_images) && !empty($order->dispute_message)) ? true : false,
                'dispute_message' => !empty($order->dispute_message) ? $order->dispute_message : "",
                'dispute_images' => $dispute_image_urls,
            );
            $user_detail = array(
                'id' => $order->id,
                'user_name' => $order->User->first_name . ' ' . $order->User->last_name,
                'user_image' => get_image($order->User->UserProfileImage, 'user', $order->merchant_id),
                'user_phone' => $order->order_status >= 4 ? $order->User->UserPhone : "******",
                'rating' => !empty($order->User->rating) ? $order->User->rating : "0.0",
                'display' => ($order->Merchant->Configuration->handyman_customer_details_visible == 1) ? true: false,
            );
            $address_detail = array(
                'drop_location' => $order->drop_location,
                'drop_latitude' => $order->drop_latitude,
                'drop_longitude' => $order->drop_longitude,
            );
            $time = "";
            if (isset($order->ServiceTimeSlotDetail)) {
                $start = strtotime($order->ServiceTimeSlotDetail->from_time);
                $start = $time_format == 2 ? date("H:i", $start) : date("h:i a", $start);
                $end = strtotime($order->ServiceTimeSlotDetail->to_time);
                $end = $time_format == 2 ? date("H:i", $end) : date("h:i a", $end);
                $time = $start . "-" . $end;
            }
            $timing = array(
                'date' => date('d/M (D)', strtotime($order->booking_date)),
                'time' => $time,

            );
            $service_data = $order->HandymanOrderDetail->map(function ($item, $key) use ($merchant_id, $arr_order_status) {
                return array(
                    'id' => $item->service_type_id,
                    'service_name' => $item->ServiceType->ServiceName($merchant_id),
                    'quantity' => $item->quantity,
                );
            });

            $theme_config = $order->Merchant->ApplicationTheme;

            $action_step = [];
            if ($order->order_status == 1) {
                $user_detail['display'] = true;
                $action_step = [
                    [
                        'action_name' => trans("$string_file.accept"),
                        'action' => 'ACCEPT',
                        'action_color' => '2ECC71',
                    ],
                    [
                        'action_name' => trans("$string_file.reject"),
                        'action' => 'REJECT',
                        'action_color' => 'E74C3C',
                    ]
                ];
            } elseif ($order->order_status == 4) {
                $action_step = [
                    [
                        'action_name' => trans("$string_file.cancel"),
                        'action' => 'CANCEL',
                        'action_color' => 'E74C3C',
                    ],
                    [
                        'action_name' => trans("$string_file.start"),
                        'action' => 'START',
                        'action_color' => '2ECC71',
                    ]
                ];
            } elseif ($order->order_status == 6) {
                $action_step = [
                    [
                        'action_name' => trans("$string_file.end"),
                        'action' => 'END',
                        'action_color' => 'E74C3C',
                    ],
                    [
                        'action_name' => trans("$string_file.upload_image"),
                        'action' => 'UPLOAD_PHOTO',
                        'action_color' => !empty($theme_config) ? str_replace('#','',$theme_config->primary_color_driver) : '0091FF',
                    ]
                ];
                // charge types
                $handyman_config = $order->Merchant->HandymanConfiguration;
                if (!empty($handyman_config->additional_charges_on_booking) && $handyman_config->additional_charges_on_booking == 1) {
                    $charge_type_config = true;
                    $charge_type = new HandymanChargeType;
                    $arr_charge_types = $charge_type->ChargeType($merchant_id, $order->segment_id);
                }
            } elseif ($order->order_status == 7 && $order->payment_status != 1 && $order->is_order_completed != 1 && $order->payment_method_id == 1) {
                if ($order->Merchant->BookingConfiguration->handyman_booking_dispute == 1){
                    $action_step = [
                        [
                            'action_name' => trans("$string_file.confirm_payment"),
                            'action' => 'CONFIRM_PAYMENT',
                            'action_color' => !empty($theme_config) ? str_replace('#','',$theme_config->primary_color_driver) : '0091FF',
                        ],
                        [
                            'action_name' => trans("$string_file.dispute"),
                            'action' => 'DISPUTE',
                            'action_color' => 'E74C3C',
                        ],
                    ];
                }else{
                    $action_step = [
                        [
                            'action_name' => trans("$string_file.confirm_payment"),
                            'action' => 'CONFIRM_PAYMENT',
                            'action_color' => !empty($theme_config) ? str_replace('#','',$theme_config->primary_color_driver) : '0091FF',
                        ]
                    ];
                }
            } elseif ($order->order_status == 7 && $order->payment_status == 1 && $order->is_order_completed != 1) {
                $action_step = [
                    [
                        'action_name' => trans("$string_file.complete"),
                        'action' => 'COMPLETE',
                        'action_color' => '2ECC71',
                    ]
                ];
            }else if (($order->order_status == 11 || $order->order_status == 12) && $order->payment_status != 1 && $order->is_order_completed != 1 && $order->payment_method_id == 1) {
                $action_step = [
                    [
                        'action_name' => trans("$string_file.confirm_payment"),
                        'action' => 'CONFIRM_PAYMENT',
                        'action_color' => !empty($theme_config) ? str_replace('#', '', $theme_config->primary_color_driver) : '2ECC71',
                    ],
                ];
            }
             elseif (($order->order_status == 11 || $order->order_status == 12)&& $order->payment_status == 1 && $order->is_order_completed != 1) {
                $action_step = [
                    [
                        'action_name' => trans("$string_file.complete"),
                        'action' => 'COMPLETE',
                        'action_color' => '2ECC71',
                    ]
                ];
             }
            $return_data = [
                'order_detail' => $order_detail,
                'user_detail' => $user_detail,
                'address_detail' => $address_detail,
                'timing' => $timing,
                'service_data' => $service_data,
                'action' => $action_step,
                'charge_type_config' => $charge_type_config,
                'arr_charge_types' => $arr_charge_types
            ];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $return_data;
    }

    // get order details
    public function getOrder(Request $request)
    {
        $request_fields = [
            'order_id' => ['required', 'integer', Rule::exists('handyman_orders', 'id')->where(function ($query) { }),],
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $return_data = $this->getOrderData($request);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        $string_file = $this->getStringFile($request->merchant_id);
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    // order accept/ reject api
    public function acceptRejectOrder(Request $request)
    {
        $request_fields = [
            'order_id' => 'required',
            'status' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $driver = $request->user('api-driver');
            $request_status = $request->status;
            $handyman = new HandymanOrder;
            $order = $handyman->getOrder($request);
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            //getting current active order for checking the same time slot
            $active_order = HandymanOrder::where([['driver_id', '=', $driver->id],['country_area_id', '=', $driver->country_area_id], ['is_order_completed', '!=', 1], ['merchant_id', '=', $driver->merchant_id]])->whereIn('order_status', [4,6])->first();
            if (!empty($active_order)){
                if ($active_order->ServiceTimeSlotDetail->from_time == $order->ServiceTimeSlotDetail->from_time && $active_order->booking_date == $order->booking_date){
                    return $this->failedResponse(trans("$string_file.ongoing_active_order").' '.$active_order->ServiceTimeSlotDetail->from_time.' - '.$active_order->ServiceTimeSlotDetail->to_time);
                }
            }

            $booking_request = BookingRequestDriver::where([['handyman_order_id', "=", $order->id], ['driver_id', "=", $driver->id]])->first();
            if (empty($booking_request)) {
                $booking_request = new BookingRequestDriver;
                $booking_request->handyman_order_id = $order->id;
                $booking_request->driver_id = $driver->id;
            }
            $driver_request_status = null;
            $message = "";

            if ($order->order_status == 1 && !empty($order->id)) {
                // if driver id is not empty then reject order with status
                if ($request_status == 4 || ($request_status == 3 && $order->driver_id != NULL)) // accepting order request
                {
                    // Check driver minimum wallet amount
                    if ($driver->wallet_money != null && $driver->wallet_money < $driver->CountryArea->minimum_wallet_amount) {
                        $message = trans("$string_file.low_wallet_warning");
                        return $this->failedResponse($message);
                    }

                    $status_history = json_decode($order->order_status_history, true);
                    $order_status = [
                        'order_status' => $request_status,
                        'order_timestamp' => time(),
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                    ];
                    array_push($status_history, $order_status);
                    $driver_request_status = $request_status == 4 ? 2 : 3; // accepting or reject request
                    $order->order_status = $request_status;
                    $order->driver_id = $driver->id;

                    $order->order_status_history = json_encode($status_history);


                    if(!empty($order->bidding_amount) && $order->bidding_amount > 0){
                        $order->bidding_amount_accepted = 1;
                    }

                    $order->save();

                    /**send notification to user*/
                    $request->request->add(['notification_type' => 'ORDER']);
                    $this->sendHandymanNotificationToUser($request, $order, "", $string_file);
                } elseif ($request_status == 3) // rejecting or by passing  order request
                {
                    //                    $message = trans('api.order_rejected');
                    $driver_request_status = 3; //rejecting request
                }

                $booking_request->request_status = $driver_request_status;
                $booking_request->save();

                // return order details data
                $return_data = [];
                if ($request_status == 4) {
                    $return_data = $this->getOrderData($request, $order);
                }
            } else {

                $message = trans("$string_file.booking_not_found");
                return $this->failedResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            $s = $e->getTraceAsString();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse($message, $return_data);
    }

    // order cancel api
    public function cancelOrder(Request $request)
    {
        $request_fields = [
            'order_id' => 'required',
            'status' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'reason_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $request_status = $request->status; // 5 for cancel
            $handyman = new HandymanOrder;
            $order = $handyman->getOrder($request);
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile($driver->merchant_id);

            if ($order->order_status == 4 && !empty($order->id)) {
                $status_history = json_decode($order->order_status_history, true);
                $new_status = [
                    'order_status' => $request_status,
                    'order_timestamp' => time(),
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ];
                array_push($status_history, $new_status);
                $message = trans("$string_file.order_cancelled");
                $order->order_status = $request_status;

                // change driver status
                $driver->free_busy = 2;
                $driver->save();

                $order->order_status_history = json_encode($status_history);
                $order->cancel_reason_id = $request->reason_id;
                $order->save();

                /**send notification to user*/
                $request->request->add(['notification_type' => 'CANCEL_ORDER']);
                $this->sendHandymanNotificationToUser($request, $order, $message, $string_file);

                // return order details data
                $return_data = $this->getOrderData($request, $order);
            } else {

                $message = trans("$string_file.booking_not_found");
                return $this->failedResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse($message, $return_data);
    }

    // order start api
    public function startOrder(Request $request)
    {
        $request_fields = [
            'order_id' => 'required',
            'status' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $request_status = $request->status; // 6 for start
            $handyman = new HandymanOrder;
            $order = $handyman->getOrder($request);
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile($driver->merchant_id);

            // check existing start bookings
            $ongoing_bookings = HandymanOrder::whereIn('order_status', [6])->where([['merchant_id', '=', $driver->merchant_id], ['driver_id', '=', $driver->id]])->count();
            if ($ongoing_bookings > 0) {
                return $this->failedResponse(trans("$string_file.existing_booking_error"));
            }

            if ($order->order_status == 4 && !empty($order->id)) {
                $status_history = json_decode($order->order_status_history, true);
                $new_status = [
                    'order_status' => $request_status,
                    'order_timestamp' => time(),
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ];
                array_push($status_history, $new_status);
                $message = trans("$string_file.order_started");
                $order->order_status = $request_status;
                $order->order_otp = NULL;

                $order->order_status_history = json_encode($status_history);
                $order->save();

                // change driver status
                $driver->free_busy = 1;
                $driver->save();

                /**send notification to user*/
                $request->request->add(['notification_type' => 'ORDER']);
                $this->sendHandymanNotificationToUser($request, $order, $message, $string_file);

                // return order details data
                $return_data = $this->getOrderData($request, $order);
            } else {
                $message = trans("$string_file.booking_not_found");
                return $this->failedResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse($message, $return_data);
    }

    // end or finish job api
    public function endOrder(Request $request)
    {
        $request_fields = [
            'order_id' => 'required',
            //            'status' => 'required', // 7
            'latitude' => 'required',
            'longitude' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $request_status = 7; //$request->status; // 5 for cancel
            $handyman = new HandymanOrder;
            $order = $handyman->getOrder($request);
            $currency = $order->CountryArea->Country->isoCode;
            $driver = $request->user('api-driver');
            // p($driver);
            $string_file = $this->getStringFile($order->merchant_id);

            if ($order->order_status == 6 && !empty($order->id)) {
                $status_history = json_decode($order->order_status_history, true);
                $job_end_time = time();
                $new_status = [
                    'order_status' => $request_status,
                    'order_timestamp' => $job_end_time,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ];
                array_push($status_history, $new_status);
                $message = trans("$string_file.order_ended");

                //calculate final bill

                $order->order_status = $request_status;
                $order->order_status_history = json_encode($status_history);

                $extra_charges = 0;
                if (!empty($request->arr_chagres_type)) {
                    $arr_chagres_type = json_decode($request->arr_chagres_type, true);
                    $extra_charges = array_sum(array_column($arr_chagres_type, 'amount'));
                    if ($extra_charges > 0) {
                        $order->extra_charges = $extra_charges;
                        $order->extra_charges_details = $request->arr_chagres_type;

                        $request->request->add(['notification_type' => 'ADDITIONAL_CHARGES_APPLIED', 'additional_charges' => $currency . $extra_charges]);
                        $this->sendHandymanNotificationToUser($request, $order, "", $string_file);
                    }
                }

                //check if order has hourly based services
                $start_time = NULL;
                if ($order->price_type == 2) {
                    foreach ($status_history as $status) {
                        if ($status['order_status'] == 6) {
                            $start_time = $status['order_timestamp'];
                            break;
                        }
                    }
                    $job_time = $job_end_time - $start_time;
                    $total_service_hours = ceil($job_time / 3600);
                    $cart_amount = $total_service_hours * $order->hourly_amount;
                    $tax_amount = ($cart_amount * $order->tax_per) / 100;
                    $final_paid = ($cart_amount - $order->discount_amount + $tax_amount);
                    $order->cart_amount = $cart_amount;
                    $order->total_booking_amount = $final_paid;
                    $order->total_service_hours = $total_service_hours;
                    // final amount of booking
                    if ($order->total_booking_amount > $order->minimum_booking_amount) {
                        $order->final_amount_paid = $order->total_booking_amount;
                        $order->tax = $tax_amount;
                    }
                }

                // If order amount is entered by user as bidding amount, then make final amount as bidding amount
                if(isset($order->bidding_amount_accepted) && $order->bidding_amount_accepted == 1){
                    $order->final_amount_paid = $order->bidding_amount;
                }


                $order->final_amount_paid = $order->final_amount_paid + $extra_charges;
                $order->save();
                $final_amount_paid = $order->final_amount_paid;
                // total pending amount
                $total_pending_amount = $final_amount_paid;
                if ($order->minimum_booking_amount_payment_status == 1) {
                    $total_pending_amount = $total_pending_amount - $order->minimum_booking_amount;
                }
                $order_transaction = $this->storeHandymanOrderTransaction($order);
                // all payment done at place order
                if ($total_pending_amount == 0) {
                    $order->payment_status = 1;
                    $order->save();

                    $this->contributeCommission($order);
                }

                /**send booking ended notification to user*/
                $request->merge(['notification_type' => 'ORDER']);
                $this->sendHandymanNotificationToUser($request, $order, $message, $string_file);

                // pending payment notification to user
                if ($total_pending_amount > 0) {
                    $request->merge(['notification_type' => 'PENDING_PAYMENT', 'pending_amount' => $currency . $total_pending_amount]);
                    $this->sendHandymanNotificationToUser($request, $order, "", $string_file);
                }

                // return order details data
                $return_data = $this->getOrderData($request, $order);
                $order_data = $order->refresh();
                if ($order_data->payment_status == 1){
                    event(new SendUserHandymanInvoiceMailEvent($order_data));
                }
                //                $data['booking'] = $order;
                //                $temp = EmailTemplate::where('merchant_id', '=', $order->merchant_id)->where('template_name', '=', "invoice")->first();
                //                $data['temp'] = $temp;
                //                $invoice_html = View::make('mail.booking-invoice')->with($data)->render();
                //                $configuration = EmailConfig::where('merchant_id', '=', $order->merchant_id)->first();
                //                $response = $this->sendMail($configuration, $order->User->email, $invoice_html, 'booking_invoice', $order->Merchant->BusinessName,NULL,$order->Merchant->email);
            } else {
                $message = trans("$string_file.booking_not_found");
                return $this->failedResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse($message, $return_data);
    }

    // update order payment info
    public function updateOrderPaymentStatus(Request $request)
    {
        $request_fields = [
            'order_id' => 'required',
            'payment_status' => 'required', // 1
            'latitude' => 'required',
            'longitude' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile($driver->merchant_id);
        DB::beginTransaction();
        try {
            $request_status = $request->payment_status;
            $handyman = new HandymanOrder;
            $order = $handyman->getOrder($request);
            if (!empty($order)) {
                if ($order->order_status == 7 || $order->order_status == 11 || $order->order_status == 12) {
                    if (!empty($order->payment_status != 1) && $order->payment_method_id == 1) {

                        if($request_status == 2){
                            $order->payment_status = 1;  //payment status for order is gonna be 1 but user outstanding is going to create
                            $order->save();

                            Outstanding::create([
                                'user_id' => $order->user_id,
                                'driver_id' => $order->driver_id,
                                'handyman_order_id' => $order->id,
                                'amount' => $order->final_amount_paid,
                                'reason' => 3,
                            ]);
                        }
                        else{
                            $order->payment_status = $request_status;
                            $order->save();
                        }

                        // if advance payment is off & payment method is cash then settle down payment at in this step
                        $request->merge(['notification_type' => 'PAYMENT_CONFIRMED']);
                        $this->sendHandymanNotificationToUser($request, $order, "", $string_file);
                        $this->sendNotificationToProvider($request, $driver->id, $order, $string_file);
                        $advance_payment = $order->advance_payment_of_min_bill;
                        if ($advance_payment != 1) {
                            $this->contributeCommission($order);
                        }
                    } else {
                        $message = trans("$string_file.payment_done_already");
                        return $this->failedResponse($message);
                    }
                } else {
                    $message = trans("$string_file.booking_not_finished");
                    return $this->failedResponse($message);
                }
            } else {
                $message = trans("$string_file.booking_not_found");
                return $this->failedResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"), []);
    }

    // order complete
    public function completeOrder(Request $request)
    {
        $request_fields = [
            'order_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $return_data = [];
        DB::beginTransaction();
        try {
            $handyman = new HandymanOrder;
            $order = $handyman->getOrder($request);
            $string_file = $this->getStringFile($order->merchant_id);
            if (($order->order_status == 7 ||  $order->order_status == 11 || $order->order_status == 12) && !empty($order->id) && $order->payment_status == 1) {
                $order->is_order_completed = 1;
                $order->save();
                // change driver status
                $driver = $request->user('api-driver');
                $driver->free_busy = 2; // driver is free now
                $driver->save();

                // Driver rate to user
                if (isset($request->rating)) {
                    $rating = BookingRating::updateOrCreate(
                        ['handyman_order_id' => $request->order_id],
                        [
                            'driver_rating_points' => $request->rating,
                            'driver_comment' => $request->comment,
                        ]
                    );
                    $order = HandymanOrder::find($request->order_id);
                    $avg = BookingRating::whereHas('HandymanOrder', function ($q) use ($order) {
                        $q->where('driver_id', $order->driver_id);
                    })->avg('driver_rating_points');
                    $user = User::find($order->user_id);
                    $user->rating = round($avg, 2);
                    $user->save();
                }
                $return_data = $this->getOrderData($request, $order);
                $request->merge(['notification_type' => 'ORDER_COMPLETE']);
                $this->sendHandymanNotificationToUser($request, $order, "", $string_file);

                //Referral Calculation
                $ref = new ReferralController();
                $arr_params = array(
                    "segment_id" => $order->segment_id,
                    "driver_id" => $order->driver_id,
                    "user_id" => $order->user_id,
                    "handyman_order_id" => $order->id,
                    "user_paid_amount" => $order->final_amount_paid,
                    "driver_paid_amount" => $order->final_amount_paid,
                    "check_referral_at" => "OTHER"
                );
                $ref->checkReferral($arr_params);
            } else {
                $message = trans("$string_file.booking_not_found");
                return $this->failedResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.order_completed"), $return_data);
    }

    // order start api
    public function startOrderOTP(Request $request)
    {
        $request_fields = [
            'order_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {

            $handyman = new HandymanOrder;
            $order = $handyman->getOrder($request);
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);

             date_default_timezone_set($order->CountryArea->timezone);
             $current_date = date('Y-m-d');
             $date = new \DateTime($order->booking_date);
             $booking_date=  $date->format('Y-m-d');
             if($current_date < $booking_date  && $driver->Merchant->Configuration->handyman_provider_start_before_booking_date == 1){
                 return $this->failedResponse(trans("$string_file.cannot_start_before_date"));
             }

            if ($order->order_status == 4 && !empty($order->id)) {
                $random_otp = rand(10001, 99999);
                $message = trans("$string_file.otp_for_verification") . ' : ' . $random_otp;
                $order->order_otp = $random_otp;
                $order->save();

                /**send notification to user*/
                $request->request->add(['notification_type' => 'ORDER_OTP']);
                $this->sendHandymanNotificationToUser($request, $order, $message, $string_file);

                $return_data = [
                    'order_otp' => $order->order_otp,
                    'validity_time' => 30,
                ];
            } else {
                $message = trans("$string_file.booking_not_found");
                return $this->failedResponse($message);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse($message, $return_data);
    }

    // booking rating by driver
    public function handymanOrderRating(Request $request)
    {
        DB::beginTransaction();
        try {
            $order_id = $request->id;
            $order = HandymanOrder::select('id', 'driver_id', 'order_status')->find($order_id);
            if ($order->order_status != 7) {
                $string_file = $this->getStringFile($order->merchant_id);
                throw new \Exception(trans("$string_file.booking_not_found"));
            }
            $rating = BookingRating::updateOrCreate(
                ['handyman_order_id' => $order_id],
                [
                    'user_rating_points' => $request->rating,
                    'user_comment' => $request->comment,
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
        }
        DB::commit();
        return ['booking_order_id' => $order_id];
    }

    // Calling from user app
    public function providerRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => ['required', 'integer', Rule::exists('handyman_orders', 'id')->where(function ($query) { })],
            'rating' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $rating = BookingRating::updateOrCreate(
                ['handyman_order_id' => $request->order_id],
                [
                    'user_rating_points' => $request->rating,
                    'user_comment' => $request->comment,
                ]
            );
            $order = HandymanOrder::find($request->order_id);
            $avg = BookingRating::whereHas('HandymanOrder', function ($q) use ($order) {
                $q->where('driver_id', $order->driver_id);
            })->avg('user_rating_points');
            if (!empty($order->driver_id)) {
                $driver = Driver::find($order->driver_id);
                $driver->rating = round($avg, 2);
                $driver->save();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse("success", $rating);
    }

    private function storeHandymanOrderTransaction($order)
    {
        try {
            $order = $order->fresh();
            $commission_data = self::HandymanOrderCommission($order);
            $sub_total_before_discount = $order->final_amount_paid - $order->tax;
            $discount_amount = $order->discount_amount;
            $tax_amount = $order->tax;
            $cash_payment = ($order->PaymentMethod->payment_method_type == 1) ? $order->final_amount_paid : '0.0';
            $online_payment = ($order->PaymentMethod->payment_method_type == 1) ? '0.0' : $order->final_amount_paid;
            $customer_paid_amount = $order->final_amount_paid;
            $company_earning = $commission_data['company_cut'];
            $driver_earning = $commission_data['driver_cut'];
            $order_transaction = BookingTransaction::where('handyman_order_id', $order->id)->first();
            if (empty($order_transaction)) {
                $order_transaction = new BookingTransaction();
                $order_transaction->merchant_id =  $order->merchant_id;
            }
            $driver_total_payout_amount = $commission_data['driver_cut'] + $order_transaction->discount_amount + $order_transaction->tip;
            $order_transaction->handyman_order_id = $order->id;
            $order_transaction->commission_type = 1;
            $order_transaction->sub_total_before_discount = $sub_total_before_discount;
            $order_transaction->discount_amount = $discount_amount;
            $order_transaction->tax_amount = $tax_amount;
            $order_transaction->cash_payment = $cash_payment;
            $order_transaction->online_payment = $online_payment;
            $order_transaction->customer_paid_amount = $customer_paid_amount;
            $order_transaction->company_earning = $company_earning;
            $order_transaction->driver_earning = $driver_earning;
            //            $order_transaction->amount_deducted_from_driver_wallet = $amount_deducted_from_driver_wallet;
            $order_transaction->driver_total_payout_amount = $driver_total_payout_amount;
            $order_transaction->company_gross_total = ($company_earning + $tax_amount - $discount_amount);
            $ride_earning_type = 2; // commission based
            if ($order->Driver->Merchant->Configuration->subscription_package == 1 && $order->Driver->pay_mode == 1) {
                $ride_earning_type = 1;
                // p('inn');
                // p($order->segment_id);
                // debit orders from package
                $this->SubscriptionPackageExpiryCheck($order->Driver, $order->segment_id);
            }
            // p('No');
            $order_transaction->ride_type_earning = $ride_earning_type;
            $order_transaction->save();
            return $order_transaction;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private static function HandymanOrderCommission($order)
    {
        try {
            $order_commission = HandymanCommission::where([['merchant_id', '=', $order->merchant_id], ['country_area_id', '=', $order->country_area_id], ['segment_id', '=', $order->segment_id]])->first();
            // Commission on amount before discount and tax
            // $cart_amount = $order->final_amount_paid - $order->tax;
            $cart_amount = $order->cart_amount;
            $amount = $cart_amount; //$order->cart_amount;
            $commission_method = NULL;
            $commission_amount = NULL;

            //@ayush Service based commission
            if(isset($order_commission) && $order_commission->commission_pricing_type == 2){
                $services = [];
                foreach($order->HandymanOrderDetail as $order_detail)
                    array_push($services, $order_detail->service_type_id);

                $commission_details = $order_commission->HandymanCommissionDetail->whereIn("service_type_id", $services);

                foreach( $commission_details as $detail)
                    $commission_amount += $detail->amount;
            }

            if (!empty($order_commission)) {
                $commission_method = $order_commission->commission_method;
//                $commission_amount = $order_commission->commission;
                $commission_amount = (empty($commission_amount) && $order_commission->commission_pricing_type == 1 )? $order_commission->commission : $commission_amount;
            }
            if ($commission_method == 1) {  // 1:Flat commission per Ride (==OR==) 2:Percentage of Net Bill (before tax)
                if ($commission_amount > $amount) {
                    $company_cut = $amount;
                    $driver_cut = "0.00";
                } else {
                    $company_cut = $commission_amount;
                    $driver_cut = $amount - $company_cut;
                }
            } else {
                $company_cut = ($amount * $commission_amount) / 100;
                $driver_cut = $amount - $company_cut;
            }

            $driver = Driver::find($order->driver_id);
            $driver->total_earnings = round_number(($driver->total_earnings + $driver_cut));
            $driver->total_comany_earning = round_number(($driver->total_comany_earning + $company_cut));
            $driver->save();
            // tax will be paid to merchant
            $return_data = [
                'company_cut' => round_number($company_cut),
                'driver_cut' => round_number($driver_cut),
            ];
            return $return_data;
        } catch (\Exception $e) {
            throw new \Exception('Commission : ' . $e->getMessage());
        }
    }

    public function bookingPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => ['required', 'integer', Rule::exists('handyman_orders', 'id')->where(function ($query) { })],
            'amount' => 'required',
            'payment_method_id' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $order = HandymanOrder::find($request->order_id);
            $order->payment_method_id = $request->payment_method_id;
            if ($request->payment_method_id == 2) {
                $order->card_id = $request->card_id;
            }
            $order->save();
            $order->fresh();
            $string_file = $this->getStringFile(NULL, $order->Merchant);
            if ($request->payment_method_id == 3 && $request->payment_status == 1) {
                $common_controller = new \App\Http\Controllers\Api\CommonController;
                $common_controller->checkUserWallet($order->User, $request->amount);
                $payment = new Payment();
                $currency = $order->CountryArea->Country->isoCode;
                $array_param = array(
                    'handyman_order_id' => $order->id,
                    'payment_method_id' => $request->payment_method_id,
                    'amount' => $request->amount,
                    'user_id' => $order->user_id,
                    'card_id' => $order->card_id,
                    'currency' => $currency,
                    'quantity' => $order->quantity,
                    'order_name' => $order->merchant_order_id,
                    'booking_transaction' => "",
                    'driver_sc_account_id' => "", //$order->Driver->sc_account_id
                );
                $payment->MakePayment($array_param);
                
        }
            if ($order->payment_status != 1) {

                // if ($request->payment_method_id == 3) {
                //     $common_controller = new \App\Http\Controllers\Api\CommonController;
                //     $common_controller->checkUserWallet($order->User, $request->amount);
                // }
                //  if payment not done at app end then do at backend
                if ($request->payment_status != 1) {
                    $payment = new Payment();
                    $currency = $order->CountryArea->Country->isoCode;
                    $array_param = array(
                        'handyman_order_id' => $order->id,
                        'payment_method_id' => $request->payment_method_id,
                        'amount' => $request->amount,
                        'user_id' => $order->user_id,
                        'card_id' => $order->card_id,
                        'currency' => $currency,
                        'quantity' => $order->quantity,
                        'order_name' => $order->merchant_order_id,
                        'booking_transaction' => "",
                        'driver_sc_account_id' => "", //$order->Driver->sc_account_id
                    );
                    $payment->MakePayment($array_param);
                }
                $order->payment_status = 1;
                $order->save();

                $driver = $order->Driver;
                $driver->free_busy = 2; // driver is free now
                $driver->save();

                $order->fresh();
                $this->contributeCommission($order);

                // send payment notification
                $title = trans("$string_file.payment_success");
                $message = trans("$string_file.payment_done");

                $data['notification_type'] = 'PAYMENT_DONE';
                $data['segment_type'] = $order->Segment->slag;
                $data['segment_data'] = ['ride_id' => $order->id, 'handyman_order_id' => $order->id];
                //                $large_icon = $this->getNotificationLargeIconForBooking($booking);
                $arr_param = ['driver_id' => $order->driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $order->merchant_id, 'title' => $title, 'large_icon' => ""];
                Onesignal::DriverPushMessage($arr_param);

                $title = trans("$string_file.payment_success_title");
                $message = trans("$string_file.payment_success_message");

                $arr_param = ['user_id' => $order->user_id, 'data' => $data, 'message' => $message, 'merchant_id' => $order->merchant_id, 'title' => $title, 'large_icon' => ""];
                Onesignal::UserPushMessage($arr_param);
                $order = $order->refresh();
                if ($order->payment_status == 1){
                    event(new SendUserHandymanInvoiceMailEvent($order));
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.payment_done"), []);
    }

//   debit tax and amount induvidually 
//     public function contributeCommission($order)
//     {
//         $order_transaction = BookingTransaction::where('handyman_order_id', $order->id)->first();
//         $driver_cut = $order_transaction->driver_total_payout_amount;
// //        $merchant_cut = $order_transaction->company_gross_total;
//         $merchant_cut = $order_transaction->company_earning;
//         $tax = $order_transaction->tax_amount;
//         $driver_has = NULL;
//         $merchant_has = NULL;
//         $driverPayment = new CommonController();
//         if ($order_transaction->ride_type_earning == 1) // subscription based
//         {
//             if ($order->payment_method_id != 1 && $order->payment_method_id != 5) // cash or swipe card payment
//             {
//                 $array_param = array(
//                     'handyman_order_id' => $order->id,
//                     'driver_id' => $order->driver_id,
//                     'amount' => $order_transaction->customer_paid_amount, // all amount paid by user because driver already bought subscription package
//                     'narration' => 21,
//                     'wallet_status' => 'CREDIT',
//                 );
//                 $driverPayment->DriverRideAmountCredit($array_param);
//             }
//         } else // commission based
//         {
//             if ($order->payment_method_id == 1) {
//                 if ($order->minimum_booking_amount_payment_status == 1 && $order->advance_payment_of_min_bill == 1) {
//                     $driver_has = $order->final_amount_paid - $order->minimum_booking_amount;
//                 }else{
//                     $driver_has = $order->final_amount_paid;
//                 }
//             }else {
//                 $merchant_has = $order->final_amount_paid;
//             }

//             $wallet_debit = false;
//             $wallet_credit = false;
//             $debit_amount = 0;
//             $credit_amount = 0;
//             if ($driver_has > 0) {
//                 // debit wallet
//                 if ($driver_has > $driver_cut) {
//                     $wallet_debit = true;
//                     $debit_amount = $merchant_cut;
//                 } else {
//                     $wallet_credit = true;
//                     $credit_amount = $driver_cut - $driver_has;
//                 }
//             } else {
//                 $wallet_credit = true;
//                 $credit_amount = $driver_cut;
//             }
//             $array_param = [];
//             // debit diver wallet
//             if ($wallet_debit) {
//                 $array_param = array(
//                     'driver_id' => $order->driver_id,
//                     'handyman_order_id' => $order->id,
//                     'amount' => $debit_amount,
//                     'narration' => 20,
//                     'wallet_status' => 'DEBIT',
//                 );
//             }

//             // credit driver wallet
//             if ($wallet_credit) {
//                 $array_param = array(
//                     'handyman_order_id' => $order->id,
//                     'driver_id' => $order->driver_id,
//                     'amount' => $credit_amount,
//                     //                'payment_method_type' => $order->PaymentMethod->payment_method_type,
//                     //                'discount_amount' => $order->discount_amount ,
//                     'narration' => 21,
//                     'wallet_status' => 'CREDIT',
//                 );
//             }
//             $driverPayment->DriverRideAmountCredit($array_param);
//             //debit tax from wallet
//             if ($wallet_debit == true && !empty($tax)){
//                 $array_param = array(
//                     'driver_id' => $order->driver_id,
//                     'handyman_order_id' => $order->id,
//                     'amount' => $tax,
//                     'narration' => 17,
//                     'wallet_status' => 'DEBIT',
//                 );
//                 $driverPayment->DriverRideAmountCredit($array_param);
//             }
//         }
//         return true;
//     }


    public function contributeCommission($order)
    {
        $order_transaction = BookingTransaction::where('handyman_order_id', $order->id)->first();
        $driver_cut = $order_transaction->driver_total_payout_amount;
        $merchant_cut = $order_transaction->company_gross_total;
        $driver_has = NULL;
        $merchant_has = NULL;
        $driverPayment = new CommonController();
        if ($order_transaction->ride_type_earning == 1) // subscription based
        {
            if ($order->payment_method_id != 1 && $order->payment_method_id != 5) // cash or swipe card payment
            {
                $array_param = array(
                    'handyman_order_id' => $order->id,
                    'driver_id' => $order->driver_id,
                    'amount' => $order_transaction->customer_paid_amount, // all amount paid by user because driver already bought subscription package
                    'narration' => 21,
                    'wallet_status' => 'CREDIT',
                );
                $driverPayment->DriverRideAmountCredit($array_param);
            }
        } else // commission based
        {
            if ($order->payment_method_id == 1) {
                if ($order->minimum_booking_amount_payment_status == 1 && $order->advance_payment_of_min_bill == 1) {
                    $driver_has = $order->final_amount_paid - $order->minimum_booking_amount;
                }else{
                    $driver_has = $order->final_amount_paid;
                }
            }else {
                $merchant_has = $order->final_amount_paid;
            }

            $wallet_debit = false;
            $wallet_credit = false;
            $debit_amount = 0;
            $credit_amount = 0;
            if ($driver_has > 0) {
                // debit wallet
                if ($driver_has > $driver_cut) {
                    $wallet_debit = true;
                    $debit_amount = $merchant_cut;
                } else {
                    $wallet_credit = true;
                    $credit_amount = $driver_cut - $driver_has;
                }
            } else {
                $wallet_credit = true;
                $credit_amount = $driver_cut;
            }
            $array_param = [];
            // debit diver wallet
            if ($wallet_debit) {
                $array_param = array(
                    'driver_id' => $order->driver_id,
                    'handyman_order_id' => $order->id,
                    'amount' => $debit_amount,
                    'narration' => 20,
                    'wallet_status' => 'DEBIT',
                );
            }

            // credit driver wallet
            if ($wallet_credit) {
                $array_param = array(
                    'handyman_order_id' => $order->id,
                    'driver_id' => $order->driver_id,
                    'amount' => $credit_amount,
                    //                'payment_method_type' => $order->PaymentMethod->payment_method_type,
                    //                'discount_amount' => $order->discount_amount ,
                    'narration' => 21,
                    'wallet_status' => 'CREDIT',
                );
            }
            $driverPayment->DriverRideAmountCredit($array_param);
        }
        return true;
    }



    public function saveBookingImage(Request $request)
    {
        $request_fields = [
            'image' => 'required',
            'booking_id' => 'required',
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
            $merchant_id = $driver->merchant_id;
            //            $image = $this->uploadBase64Image("image", 'booking_image', $merchant_id);
            $booking_details = HandymanOrder::select('id', 'segment_id')->where('id', $request->booking_id)->first();
            if($request->multi_part == 1){
                $additional_req = ['compress' => true,'custom_key' => 'product'];
                $image = $this->uploadImage("image", 'driver_gallery', $merchant_id,'single',$additional_req);
            }else{
                $image = $this->uploadBase64Image("image", 'driver_gallery', $merchant_id);
            }
            
            $gallery = new DriverGallery;
            $gallery->driver_id = $driver->id;
            $gallery->segment_id = $booking_details->segment_id;
            $gallery->image_title = $image;
            $gallery->handyman_order_id = $booking_details->id;
            $gallery->save();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        $return_image = get_image($image, 'driver_gallery', $merchant_id, true);
        $uploaded_image = ['uploaded_image' => $return_image];
        return $this->successResponse(trans("$string_file.success"), $uploaded_image);
    }

    public function getBookingImage(Request $request)
    {
        $request_fields = [
            'booking_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try {
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            $merchant_id = $driver->merchant_id;
            $booking_details = DriverGallery::select('id', 'image_title')->where('handyman_order_id', $request->booking_id)->get();
            //            $images = $booking_details->booking_images;
            $arr_image = [];
            if (!empty($booking_details)) {
                foreach ($booking_details as $image) {
                    $arr_image[] = get_image($image->image_title, 'driver_gallery', $merchant_id);
                }
            }
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.success"), $arr_image);
    }


    // Api for hero super app
    public function bookingList(Request $request)
    {
        $request_fields = [
            'start_date' => 'required', // Y-m-d format
            'end_date' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $merchant_id = $request->merchant_id;
        $booking_obj = new HandymanOrder;
        $arr_bookings = $booking_obj->bookingList($request);
        $string_file = $this->getStringFile($merchant_id);
        $arr_orders = $arr_bookings->map(function ($item, $key) use ($merchant_id) {
            $code = "";
            $id = NULL;
            $name = "";
            $referral_sender = ReferralDiscount::where([['receiver_type', '=', 1], ['receiver_id', '=', $item->user_id]])->first();
            if (!empty($referral_sender->id)) {
                $sender = $referral_sender->Sender($referral_sender->sender_type);
                $id = $sender->id;
                $name = $sender->first_name . ' ' . $sender->last_name;
            }
            return array(
                'id' => $item->id,
                'segment_id' => $item->segment_id,
                'status' => $item->order_status,
                'user_name' => $item->User->first_name . ' ' . $item->User->last_name,
                'user_phone' => $item->User->UserPhone,
                'user_address' => $item->drop_location,
                'segment_name' => $item->Segment->Name($merchant_id),
                'booking_date' => $item->booking_date,
                'cart_amount' => $item->cart_amount,
                'tax' => $item->tax,
                'tip_amount' => !empty($item->tip_amount) ? $item->tip_amount : "0.0",
                'discount_amount' => !empty($item->discount_amount) ? $item->discount_amount : "0.0",
                //                'total_booking_amount'=>$item->total_booking_amount,
                'final_amount_paid' => $item->final_amount_paid,
                'provider_name' => $item->Driver->first_name . ' ' . $item->Driver->last_name,
                'provider_number' => $item->Driver->phoneNumber,
                'referral_code' => $code,
                'referral_owner' => [
                    'id' => $id,
                    'name' => $name,
                ],
            );
        });
        return $this->successResponse(trans("$string_file.data_found"), $arr_orders);
    }

    public function raiseConcern(Request $request){
        $request_fields = [
            'handyman_order_id' => 'required',
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
            $handyman_order = HandymanOrder::find($request->handyman_order_id);
//            if(!empty($handyman_order->dispute_message) && !empty($handyman_order->dispute_images)){
            if($handyman_order->order_status == 10){
                return $this->failedResponse('Order Already Disputed!');
            }

            $handyman_order->dispute_message = $request->dispute_msg;
            $upload_images = [];
            if(!empty($request->image_one)){
                array_push($upload_images, $this->uploadImage($request->image_one, 'booking_images', $handyman_order->merchant_id, 'multiple'));
            }
            if(!empty($request->image_two)){
                array_push($upload_images, $this->uploadImage($request->image_two, 'booking_images', $handyman_order->merchant_id, 'multiple'));
            }
            if(!empty($request->image_three)){
                array_push($upload_images, $this->uploadImage($request->image_three, 'booking_images', $handyman_order->merchant_id, 'multiple'));
            }
            if(!empty($request->image_four)){
                array_push($upload_images, $this->uploadImage($request->image_four, 'booking_images', $handyman_order->merchant_id, 'multiple'));
            }
            $handyman_order->dispute_images = json_encode($upload_images);
            $handyman_order->order_status = 10;
            $handyman_order->save();
            $request->request->add(['notification_type' => 'DISPUTE']);
            $this->sendHandymanNotificationToUser($request, $handyman_order, "", $string_file);

//            $outstanding = Outstanding::create([
//                'user_id' => $handyman_order->user_id,
//                'driver_id' => $handyman_order->driver_id,
//                'handyman_order_id' => $handyman_order->id,
//                'amount' => $handyman_order->total_booking_amount,
//                'reason' => 3,
//            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"));
    }

}
