<?php
/**
 * @ayush (New Combined Delivery Screen)
 * Taxi Delivery
 * Business Segment
 * Laundry Outlet
 */

namespace App\Http\Resources;

use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\Merchant;
use App\Models\BookingConfiguration;
use App\Models\BookingDeliveryDetails;
use App\Models\BookingRating;
use App\Models\CancelPolicy;
use App\Models\PriceCard;
use App\Models\BookingRequestDriver;
use App\Traits\LaundryServiceTrait;
use App\Traits\OrderTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;


class DriverDeliveryResource extends JsonResource
{

    /**
     * Action Buttons
     * @var string
     */
    protected $action_buttons;
    protected $lang;

    /**
     * Constructor for resource
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->lang = $this->getStringFile($this->merchant_id);
        $this->initializeActionButtons();
    }

    private function initializeActionButtons(): void
    {
        $this->action_buttons = [
            'info' => $this->createButton("Info", "#FFFFFF", "#6586e5", "INFO"),
            'reject' => $this->createButton(trans("$this->lang.reject"), "#FFFFFF", "#e73d19", "REJECT"),
            'accept' => $this->createButton(trans("$this->lang.accept"), "#FFFFFF", "#046b2c", "ACCEPT"),
            'cancel' => $this->createButton(trans("$this->lang.cancel"), "#FFFFFF", "#e73d19", "CANCEL"),
            'pick' => $this->createButton(trans("$this->lang.pick"), "#FFFFFF", "#046b2c", "PICK_ORDER"),
            'pick_from_store' => $this->createButton(trans("$this->lang.pick"), "#FFFFFF", "#046b2c", "PICK_ORDER_STORE"),
            'deliver_to_store' => $this->createButton(trans("$this->lang.deliver") . " " . trans("{$this->lang}.to") . " " . trans("{$this->lang}.store"), "#FFFFFF", "#046b2c", "DELIVER_AT_STORE"),
            'deliver_order' => $this->createButton(trans("$this->lang.deliver") . " " . trans("{$this->lang}.order"), "#FFFFFF", "#046b2c", "COMPLETE_ORDER"),
            'arrive' => $this->createButton(trans("$this->lang.arrive"), "#FFFFFF", "#046b2c", "ARRIVE"),
            'start' => $this->createButton(trans("$this->lang.start"), "#FFFFFF", "#046b2c", "START"),
            'drop' => $this->createButton(trans("$this->lang.drop"), "#FFFFFF", "#046b2c", "RIDE_END"),
            'end_ride' => $this->createButton(trans("$this->lang.end_ride"), "#FFFFFF", "#046b2c", "RIDE_END"),
            'arrived_at_store' => $this->createButton(trans("$this->lang.arrive").' '.trans("$this->lang.at").' '.trans("$this->lang.store"), "#FFFFFF", "#046b2c", "ARRIVE_AT_STORE"),
            'pick_at_store' => $this->createButton(trans("$this->lang.pick").' '.trans("$this->lang.at").' '.trans("$this->lang.store"), "#FFFFFF", "#046b2c", "PICK_AT_STORE"),
            'complete_order' => $this->createButton(trans("$this->lang.complete").' '.trans("$this->lang.order"), "#FFFFFF", "#046b2c", "DELIVER_ORDER"),
        ];
    }


    /**
     * Creating a button configuration.
     *
     * @param string $text
     * @param string $textColor
     * @param string $backgroundColor
     * @param string $action
     * @return array
     */
    private function createButton(string $text, string $textColor, string $backgroundColor, string $action): array
    {
        return [
            'button_text' => $text,
            'button_text_colour' => $textColor,
            'button_background_colour' => $backgroundColor,
            'button_action' => $action,
        ];
    }

    private function getTaxiDeliveryActionButtons($order, $dropLocation): array
    {
        $order_status_map = [
            1001 => function () use ($order) {
                return [$this->action_buttons['info'], $this->action_buttons['reject'], $this->action_buttons['accept']];
            },
            1002 => function () use ($order) {
                return [$this->action_buttons['info'], $this->action_buttons['cancel'], $this->action_buttons['arrive']];
            },
            1003 => function () use ($order) {
                return [$this->action_buttons['info'], $this->action_buttons['start']];
            },
            1004 => function () use ($order, $dropLocation) {
                if (!empty($dropLocation) && $dropLocation['last_location'] == 1) {
                    $this->action_buttons['drop']['button_text'] = $dropLocation['text'];
                    return [$this->action_buttons['info'], $this->action_buttons['drop']];
                }
                return [$this->action_buttons['end_ride']];
            }
        ];

        return $order_status_map[$order->order_status]() ?? [];
    }

    private function getBusinessSegmentActionButtons($order) : array
    {
        $order_status_map = [
            1 => function () use ($order) {
                return [$this->action_buttons['info'], $this->action_buttons['reject'], $this->action_buttons['accept']];
            },
            6 => function () use ($order) {
               return [$this->action_buttons['info'], $this->action_buttons['cancel'], $this->action_buttons['arrived_at_store']];
            },
            7 => function () use ($order) {
                return [$this->action_buttons['info'],  $this->action_buttons['pick_at_store']];
            },
            9 => function () use ($order) {
                 return [$this->action_buttons['info'],  $this->action_buttons['pick_at_store']];
            },
            10 => function () use ($order) {
                 return [$this->action_buttons['info'],  $this->action_buttons['complete_order']];
            }
        ];
        return $order_status_map[$order->order_status]() ?? [];
    }

    private function getLaundryOutletActionButtons($order): array
    {
        $arr_location = [];
        $order_status_map = [
            1 => function () use (&$arr_location, $order) {
                $this->setLaundryPickupToDropLocation($arr_location, $order, true);
                return [$this->action_buttons['info'], $this->action_buttons['reject'], $this->action_buttons['accept']];
            },
            6 => function () use (&$arr_location, $order) {
                $this->setLaundryPickupToDropLocation($arr_location, $order, true);
                return [$this->action_buttons['info'], $this->action_buttons['cancel'], $this->action_buttons['pick']];
            },
            15 => function () use (&$arr_location, $order) {
                $this->setLaundryPickupToDropLocation($arr_location, $order, false);
                return [$this->action_buttons['info'], $this->action_buttons['cancel'], $this->action_buttons['pick_from_store']];
            },
            10 => function () use (&$arr_location, $order) {
                $this->setLaundryPickupToDropLocation($arr_location, $order, true);
                return [$this->action_buttons['deliver_to_store']];
            },
            13 => function () use (&$arr_location, $order) {
                $this->setLaundryPickupToDropLocation($arr_location, $order, false);
                return [$this->action_buttons['info'], $this->action_buttons['reject'], $this->action_buttons['accept']];
            },
            16 => function () use (&$arr_location, $order) {
                $this->setLaundryPickupToDropLocation($arr_location, $order, false);
                return [$this->action_buttons['deliver_order']];
            },
        ];

        $action_buttons = $order_status_map[$order->order_status]() ?? [];
        return ["action_buttons" => $action_buttons, "location_details" => $arr_location];
    }

    private function setLaundryPickupToDropLocation(array &$arr_location, $order, bool $pickupFirst): void
    {
        if ($pickupFirst) {
            $arr_location['address'] = $order->drop_location;
            $arr_location['pickup_lat'] = (string)$order->latitude;
            $arr_location['pickup_lng'] = (string)$order->longitude;
            $arr_location['drop_address'] = $order->LaundryOutlet->address;
            $arr_location['drop_lat'] = (string)$order->LaundryOutlet->latitude;
            $arr_location['drop_lng'] = (string)$order->LaundryOutlet->longitude;
        } else {
            $arr_location['address'] = $order->LaundryOutlet->address;
            $arr_location['pickup_lat'] = (string)$order->LaundryOutlet->latitude;
            $arr_location['pickup_lng'] = (string)$order->LaundryOutlet->longitude;
            $arr_location['drop_address'] = $order->drop_location;
            $arr_location['drop_lat'] = (string)$order->latitude;
            $arr_location['drop_lng'] = (string)$order->longitude;
        }
    }


    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */

    use OrderTrait, LaundryServiceTrait;

    public function toArray($request): array
    {
        $return_data = [];
        $types = [];
// dd($this->combined_orders);
        foreach ($this->combined_orders as $key => $order) {
            
           $bookingCondition = BookingRequestDriver::where("booking_id", $order->id)
                ->where(function($query) {
                    $query->where("request_status", 3)
                          ->where("driver_id", $this->driver_id)
                          ->orWhere(function($query) {
                              $query->where("request_status", 2)
                                    ->where("driver_id", '!=', $this->driver_id);
                          });
                })
                ->exists();

            if ($bookingCondition && $order->type != "LAUNDRY_OUTLET") {
                continue;
            }

            $drop_locations = $arr_packages = $store_details = $product_loaded_images = $action_buttons = $productDetails = $additional_delivery_details = $customer_details = [];
            $current_status = $status_text = null;
            $arr_cancel_policy = (object)[];
            $additional_notes = "";

            $config = BookingConfiguration::select('driver_request_timeout')->where([['merchant_id', '=', $order->merchant_id]])->first();
            $merchant_helper = new Merchant();

            $delivery_timeout = $config->driver_request_timeout * 1000;
            
            
            $driver_request_timeout = $order->Merchant->BookingConfiguration->driver_request_timeout;
            $generated_time = $order->booking_timestamp ?? (string)carbon::parse($order->created_at)->timestamp;
            $remaining_time = $generated_time - carbon::now()->timestamp <= $driver_request_timeout;
            if (!$remaining_time && ($order->order_status != 1 || $order->order_status != 1001)) {
                continue;
            }
            $highlights = [
                'number' => "",
                'number_format' => "",
                'price' => "",
                'price_visibility' => $order->Merchant->BookingConfiguration->request_show_price == 1,
                'name' => $order->Segment->Name($order->merchant_id) . ' ' . trans("$this->lang.ride"),
                'service_type' => $order->service_type_id ? $order->ServiceType->ServiceName($order->merchant_id) : "",
                'payment_mode' => $order->PaymentMethod->MethodName($order->merchant_id) ? $order->PaymentMethod->MethodName($order->merchant_id) : $order->PaymentMethod->payment_method,
                'payment_mode_visibility' => $order->Merchant->BookingConfiguration->request_payment_method == 1,
                'estimate_distance_time' => $order->estimate_distance . ' ' . $order->estimate_time,
                'description_visibility' => $order->Merchant->BookingConfiguration->request_distance == 1,
                'vehicle_type' => $order->vehicle_type_id ? $order->vehicleType->VehicleTypeName : "",
                'pickup_otp_prompt' => false,
                'otp_prompt_message' => "Provide Otp !",
                'otp_for_pickup' =>isset($order->otp_for_pickup)? $order->otp_for_pickup : "",
                "confirmed_otp_for_pickup"=>$order->confirmed_otp_for_pickup == 1,
                "delivery_mode"=> isset($order->delivery_mode)? $order->delivery_mode : 2,
            ];

            $customer_details[] = [

                "name" => $order->User->UserName,
                "email" => $order->User->email ?? "",
                "phone" => $order->User->UserPhone,
                "image" => !empty($order->User->UserProfileImage) ? get_image($order->User->UserProfileImage, 'user', $order->merchant_id, true, false) : "",
                "customer_details_visibility" => $order->Merchant->BookingConfiguration->request_customer_details == 1,
                "totalTrips" => (string)$order->User->total_trips,
                "rating" => ""

            ];

            $pickup_details = [
                'header' => trans("$this->lang.pickup_location"),
                'locations' => [
                    [
                        'address' => $order->pickup_location,
                        'lat' => (string)$order->latitude,
                        'lng' => (string)$order->longitude,
                    ]
                ],
            ];


            $cancel_policy = CancelPolicy::where([['segment_id', '=', $order->segment_id], ['country_area_id', '=', $order->country_area_id]])->first();
            $booking_later_booking_date_time = strtotime($order->order_date . " " . $order->later_booking_time);
            $free_time = !empty($cancel_policy) ? $cancel_policy->free_time : 0;
            $cancel_free_time = date('Y-m-d H:i:s', $booking_later_booking_date_time - ($free_time * 60));

            if ($cancel_policy && $cancel_policy->id) {
                $trans = $cancel_policy->PolicyTransalation($order->merchant_id);
                $arr_cancel_policy = [
                    'id' => $cancel_policy->id,
                    'free_time' => $cancel_policy->free_time,
                    'title' => $trans->title ? $trans->title : "",
                    'description' => $trans->description ? $trans->description : "",
                    'free_time_desc' => trans("$this->lang.free_cancel_till") . ' ' . $cancel_free_time
                ];
            }

        
            switch ($order->type) {
                // array_push($types, $order->type);
                case "TAXI_DELIVERY":
                    
                    $additional_notes = !empty($order->BookingDeliveryDetails->additional_notes) ? $order->BookingDeliveryDetails->additional_notes : "";
                    $user_id = $order->User->id;

                    //update basic details and action buttons
                    $estimate_bill = $order->CountryArea->Country->isoCode . ' ' . $merchant_helper->PriceFormat($order->estimate_bill, $order->merchant_id);
                    $booking_data_controller = new BookingDataController();
                    if ($order->Merchant->Configuration->homescreen_estimate_fare == 2) {
                        $estimate_bill = $booking_data_controller->getPriceRange($order->estimate_bill, $order->CountryArea->Country->isoCode);
                    }

                    $highlights['number'] = $order->number;
                    $highlights['number_format'] = "#".$order->number;
                    $highlights['price'] = $estimate_bill;

                    if ($order->order_status == 1003 && !empty($order->Merchant->BookingConfiguration->ride_otp == 1) && $order->platform == 1)
                        $highlights['pickup_otp_prompt'] = true;

                    $avg = "";
                    if ($user_id) {
                        $avg = BookingRating::whereHas('Booking', function ($q) use ($user_id) {
                            $q->where('user_id', $user_id);
                        })->avg('driver_rating_points');
                    }

                    $customer_details[0]['rating'] = $avg;
                    $current_status = $order->order_status;
                    $driver_request_timeout = $config->driver_request_timeout * 1000;

                    //handle multiple stops
                    $multiple_stop = !empty($order->waypoints) && count(json_decode($order->waypoints, true)) > 0;
                    $stops = json_decode($order->waypoints, true);
                    $booking_delivery_details = BookingDeliveryDetails::where([["booking_id", "=", $order->id]])->orderby("stop_no")->get();

                    $booking_data_controller = new BookingDataController();
                    $dropLocation = $booking_data_controller->NextLocation($order->waypoints, $this->lang);
                    $action_buttons = $this->getTaxiDeliveryActionButtons($order, $dropLocation);
                    $reached_at_multi_drop = !empty($dropLocation) && $dropLocation['last_location'] == 1;
                    $additional_delivery_details = [
                        "delivery_drop_otp" => $order->Merchant->BookingConfiguration->delivery_drop_otp == 1,
                        "delivery_drop_qr" => $order->Merchant->BookingConfiguration->delivery_drop_otp == 2,
                        "toll_enable" => $order->booking_status == 1004 ? $order->Merchant->Configuration->toll_api == 1 : "0",
                        "toll_enable_status" => $order->Merchant->Configuration->toll_api == 1,
                        "reached_at_multi_drop" => $reached_at_multi_drop,
                        'proof_of_delivery' => $order->Merchant->ApplicationConfiguration->proof_of_delivery,
                    ];

                    //initial drop location
                    $drop_location =
                        [
                            'address' => $order->drop_location,
                            'lat' => (string)$order->drop_latitude,
                            'lng' => (string)$order->drop_longitude,
                            'receiver_details' => [
                                'receiver_name' => $booking_delivery_details[0]->receiver_name,
                                'receiver_phone' => $booking_delivery_details[0]->receiver_phone,
                            ],
                        ];

                    if ($booking_delivery_details[0]->drop_status == 0) {
                        array_push($drop_locations, $drop_location);
                    }

                    //if had multiple stop send only those locations that are not reached till now
                    if ($multiple_stop) {
                        foreach ($stops as $stop) {
                            if ($booking_delivery_details[$stop['stop']]->drop_status == 0) {
                                $all_location = [
                                    'address' => $stop['drop_location'],
                                    'lat' => (string)$stop['drop_latitude'],
                                    'lng' => (string)$stop['drop_longitude'],
                                    'receiver_details' => [
                                        'receiver_name' => $booking_delivery_details[$stop['stop']]->receiver_name,
                                        'receiver_phone' => $booking_delivery_details[$stop['stop']]->receiver_phone,
                                    ],
                                ];
                                $drop_locations[] = $all_location;
                            }
                        }
                    }

                    //driver loadded images are added here
                    if (!empty($order->BookingDetail->product_loaded_images)) {
                        $productImages = json_decode($order->BookingDetail->product_loaded_images, true);
                        foreach ($productImages as $productImage) {
                            $product_loaded_images[] = get_image($productImage, 'product_loaded_images', $order->merchant_id, true);
                        }
                    }

                    //sending user uploaded delivery package images according to drop location reached status
                    $productImageData = [];
                    foreach ($booking_delivery_details as $delivery_details) {
                        if ($delivery_details->drop_status == 0) {
                            if (!empty($delivery_details->product_image_one)) {
                                $image1 = get_image($delivery_details->product_image_one, 'product_image', $order->merchant_id, true);
                                array_push($productImageData, $image1);
                            }
                            if (!empty($delivery_details->product_image_two)) {
                                $image2 = get_image($delivery_details->product_image_two, 'product_image', $order->merchant_id, true);
                                array_push($productImageData, $image2);
                            }
                        }

                    }

                    //sending user uploaded delivery package details according to drop location reached status
                    if (!empty($order->DeliveryPackage)) {
                        $deliveryPackages = $order->DeliveryPackage;
                        foreach ($deliveryPackages as $deliveryPackage) {
                            if ($deliveryPackage->BookingDeliveryDetails->drop_status == 0) {
                                $productDetails[] = array(
                                    'id' => $deliveryPackage->id,
                                    'product_name' => $deliveryPackage->DeliveryProduct->ProductName,
                                    'weight_unit' => $deliveryPackage->DeliveryProduct->WeightUnit->WeightUnitName,
                                    'quantity' => $deliveryPackage->quantity,

                                );
                            }

                        }
                    }

                    $arr_packages['items'] = !empty($productDetails) ? $productDetails : (object)[];
                    $arr_packages['images'] = !empty($productImageData) ? $productImageData : [];
                    $arr_packages['product_loaded_images'] = $product_loaded_images;

                    break;

                case "BUSINESS_SEGMENT":
                    $additional_notes = !empty($order->additional_notes) ? $order->additional_notes : "";
                    $highlights['number'] =  $order->number;
                    $highlights['number_format'] = "#".$order->number;
                    $highlights['price_visibility'] = true;
                    $highlights['price'] = $order->CountryArea->Country->isoCode . ' ' . $merchant_helper->PriceFormat($order->final_amount_paid, $order->merchant_id);
                    $highlights['vehicle_type'] = isset($order->DriverVehicle)? $order->DriverVehicle->VehicleType->VehicleTypeName : "";
                    $current_status = $order->order_status;



                    $store_details = [
                        "name" => $order->BusinessSegment->full_name,
                        "image" => get_image($order->BusinessSegment->business_logo, 'business_logo', $order->merchant_id),
                        "address" => $order->BusinessSegment->address,
                        "latitude" => $order->BusinessSegment->latitude,
                        "longitude" => $order->BusinessSegment->longitude,
                        "phone" => $order->BusinessSegment->phone_number,
                    ];


                    $product_list = $order->OrderDetail;
                    $productDetails= [];
                    if (!empty($product_list)) {
                        foreach ($product_list as $product) {
                            $productDetails[] = array(
                                'id' => $product->id,
                                'product_name' => $product->Product->Name($order->merchant_id),
                                'weight_unit' => isset($product->WeightUnit) ? $product->WeightUnit->WeightUnitName." ".$product->ProductVariant->weight : "",
                                'quantity' => $product->quantity,
                            );
                        }
                    }


                    $arr_packages['items'] = !empty($productDetails) ? $productDetails : (object)[];
                    $arr_packages['images'] = [];
                    if(!empty($order->prescription_image)){
                        $arr_packages['images'][]=  get_image($order->prescription_image, 'prescription_image', $merchant_id);
                    }
                    $arr_packages['product_loaded_images'] = [];


                    $pickup_details = [
                        'header' => trans("$this->lang.pickup_location"),
                        'locations' => [
                            [
                                'address' => $order->BusinessSegment->address,
                                'lat' => (string)$order->BusinessSegment->latitude,
                                'lng' => (string)$order->BusinessSegment->longitude,
                            ]
                        ],
                    ];

                    $drop_locations =[
                        [
                            'address' => $order->drop_location,
                            'lat' => (string)$order->latitude,
                            'lng' => (string)$order->longitude,
                            'receiver_details' => [
                                'receiver_name' => $order->User->first_name." ".$order->User->last_name,
                                'receiver_phone' => $order->User->UserPhone,
                            ],
                        ]
                    ];

                    $action_buttons = $this->getBusinessSegmentActionButtons($order);




                    // dd($product_data);

                    break;
                case "LAUNDRY_OUTLET":
                    $driver_request_timeout = $config->driver_request_timeout * 1000;
                    $additional_notes = !empty($order->additional_notes) ? $order->additional_notes : "";
                    $highlights['number'] =  $order->number;
                    $highlights['number_format'] = "#".$order->number;

                    // if(in_array($order->order_status, [1, 6, 10])){
                        if($order->Merchant->Configuration->laundry_billing == 1 && in_array($order->order_status, [1,6, 10])){
                            $highlights['price'] = $order->final_amount_paid;
                        }
                        else{
                            $price_card = PriceCard::where([['status', '=', 1], ['country_area_id', '=', $order->country_area_id], ['merchant_id', '=', $order->merchant_id], ['service_type_id', '=', $order->service_type_id], ['segment_id', '=', $order->segment_id], ['price_card_for', '=', 1]])->first();
                            $user_drop_location[0] = [
                                'drop_latitude' => $order->latitude,
                                'drop_longitude' => $order->longitude,
                                'drop_location' => "",
                            ];
                            $user_distance = GoogleController::GoogleStaticImageAndDistance($order->LaundryOutlet->latitude, $order->LaundryOutlet->longitude, $user_drop_location, $order->Merchant->BookingConfiguration->google_key, "", $this->lang);

                            if (empty($price_card)) {
                                throw new Exception(trans("$this->lang.no_price_card_for_area"));
                            }

                            $distance_charges = 0;
                            $driver_distance = $user_distance['total_distance'];
                            $delivery_charge_slabs = $price_card->PriceCardDetail->toArray();
                            $request->request->add(['for' => 1, 'distance' => $driver_distance, 'cart_amount' => null]);
                            $slab = $this->getDistanceSlab($request, $delivery_charge_slabs);
                            if (isset($slab['id']) && isset($slab['slab_amount'])) {
                                $distance_charges = $slab['slab_amount'];
                            }
                            $driver_commission = $price_card->pick_up_fee + $price_card->drop_off_fee + $distance_charges;
                            $highlights['price'] = round_number($driver_commission, 2);
                        }
                    // }


//                    if (in_array($order->order_status, [1, 6, 10])) {
//
//                        $price_card = PriceCard::where([['status', '=', 1], ['country_area_id', '=', $order->country_area_id], ['merchant_id', '=', $order->merchant_id], ['service_type_id', '=', $order->service_type_id], ['segment_id', '=', $order->segment_id], ['price_card_for', '=', 1]])->first();
//
//                        $user_drop_location[0] = [
//                            'drop_latitude' => $order->latitude,
//                            'drop_longitude' => $order->longitude,
//                            'drop_location' => "",
//                        ];
//                        $user_distance = GoogleController::GoogleStaticImageAndDistance($order->LaundryOutlet->latitude, $order->LaundryOutlet->longitude, $user_drop_location, $order->Merchant->BookingConfiguration->google_key, "", $this->lang);
//
//                        if (empty($price_card)) {
//                            throw new Exception(trans("$this->lang.no_price_card_for_area"));
//                        }
//
//                        $distance_charges = 0;
//                        $driver_distance = $user_distance['total_distance'];
//                        $delivery_charge_slabs = $price_card->PriceCardDetail->toArray();
//                        $request->request->add(['for' => 1, 'distance' => $driver_distance, 'cart_amount' => null]);
//                        $slab = $this->getDistanceSlab($request, $delivery_charge_slabs);
//                        if (isset($slab['id']) && isset($slab['slab_amount'])) {
//                            $distance_charges = $slab['slab_amount'];
//                        }
//                        $driver_commission = $price_card->pick_up_fee + $price_card->drop_off_fee + $distance_charges;
//                        $highlights['price'] = round_number($driver_commission, 2);
//                    } else {
//                        $highlights['price'] = $order->final_amount_paid;
//                    }

                    $highlights['price_visibility'] = true;
                    if (in_array($order->order_status, [6, 16])) {
                        $highlights['pickup_otp_prompt'] = true;
                    }

                    $location_and_action_buttons = $this->getLaundryOutletActionButtons($order);
                    $action_buttons = $location_and_action_buttons['action_buttons'];
                    $pickup_details['locations'] = [
                        [
                            'address' => $location_and_action_buttons['location_details']['address'],
                            'lat' => $location_and_action_buttons['location_details']['pickup_lat'],
                            'lng' => $location_and_action_buttons['location_details']['pickup_lng'],
                        ]
                    ];

                    $drop_locations = [
                        [
                            'address' => $location_and_action_buttons['location_details']['drop_address'],
                            'lat' => $location_and_action_buttons['location_details']['drop_lat'],
                            'lng' => $location_and_action_buttons['location_details']['drop_lng'],
                        ]
                    ];

                    $store_details = [
                        "name" => $order->LaundryOutlet->full_name,
                        "image" => get_image($order->LaundryOutlet->business_logo, 'laundry_outlet_logo', $order->merchant_id),
                        "address" => $order->LaundryOutlet->address,
                        "latitude" => $order->LaundryOutlet->latitude,
                        "longitude" => $order->LaundryOutlet->longitude,
                        "phone" => $order->LaundryOutlet->phone_number,
                    ];

                    if (!empty($order->order_item_images)) {
                        $productImages = json_decode($order->order_item_images, true);
                        foreach ($productImages as $productImage) {
                            $product_loaded_images[] = get_image($productImage, 'laundry_order_items', $order->merchant_id, true);
                        }
                    }

                    if (!empty($order->LaundryOutletOrderDetail)) {
                        $deliveryPackages = $order->LaundryOutletOrderDetail;
                        foreach ($deliveryPackages as $deliveryPackage) {
                            $productDetails[] = array(
                                'id' => $deliveryPackage->id,
                                'product_name' => $deliveryPackage->Service->Name($order->merchant_id),
                                'weight_unit' => "",
                                'quantity' => (string)$deliveryPackage->quantity,

                            );
                        }
                    }
                    $arr_packages['items'] = !empty($productDetails) ? $productDetails : (object)[];
                    $arr_packages['images'] = [];
                    $arr_packages['product_loaded_images'] = $product_loaded_images;
                    $current_status = $order->order_status;
                    $req_param['string_file'] = $this->lang;
                    $config_status = $this->getLaundryOrderStatus($req_param);
                    $status_text = $config_status[$current_status];

                    break;
                default;
            }

            $return_data[] = [
                'id' => $order->id,
                'timer' =>  $driver_request_timeout,
                'created_at' => carbon::parse($order->created_at)->format('Y-m-d H:i'),
                'generated_time' => $generated_time,
                'segment_id' => $order->segment_id,
                'segment_type' => $order->Segment->slag,
                'segment_group_id' => $order->Segment->segment_group_id,
                'segment_sub_group' => $order->Segment->sub_group_for_app,
                'current_status' => $current_status,
                'status_text' => $status_text,
                'highlights' => $highlights,
                'pickup_details' => $pickup_details,
                'drop_details' => [
                    'header' => trans("$this->lang.drop_off_location"),
                    'locations' => $drop_locations,
                    'drop_location_visibility' => $order->Merchant->BookingConfiguration->drop_location_request == 1,
                ],
                'additional_delivery_details' => (object)$additional_delivery_details,
                'customer_details' => $customer_details,
                'store_details' => $store_details,
                'package_details' => $arr_packages,
                'additional_notes' => !empty($additional_notes) ? [$additional_notes] : [],
                'additional_movers' => !empty($order->additional_movers) ? $order->additional_movers : 0,
                'action_buttons' => $action_buttons,
                'cancel_charges' => isset($cancel_policy),
                'arr_cancel_policy' => $arr_cancel_policy,
            ];
        }

        return $return_data;
    }


}
