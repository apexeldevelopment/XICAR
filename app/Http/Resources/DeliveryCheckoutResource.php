<?php

namespace App\Http\Resources;

use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\HolderController;
use App\Models\AdvertisementBanner;
use App\Models\DeliveryCheckoutDetail;
use App\Models\DeliveryProduct;
use App\Models\MerchantFarePolicy;
use App\Models\PaymentOptionsConfiguration;
use App\Models\SmsConfiguration;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Controllers\Helper\PolygenController;
use App\Models\CountryArea;
use Illuminate\Support\Facades\App;
use App\Traits\MerchantTrait;
use App\Http\Controllers\Helper\Merchant;

class DeliveryCheckoutResource extends JsonResource
{
    use MerchantTrait;
    public function toArray($data)
    {
        $merchant_helper = new Merchant();
        $format_price = $this->Merchant->Configuration->format_price;
        $trip_calculation_method = $this->Merchant->Configuration->trip_calculation_method;
        
        $currency = $this->CountryArea->Country->isoCode;
        // $estimate_bill = $currency . " " . $this->estimate_bill;
        $estimate_bill = $currency . " " . $this->estimate_bill;
        $string_file = $this->getStringFile(NULL,$this->Merchant);
        $bookingDataController = new BookingDataController();
        $SelectedPaymentMethod = $this->SelectedPaymentMethod = $bookingDataController->PaymentMethod($this->id, $string_file);
        $additional_mover = $this->Merchant->BookingConfiguration->additional_mover;
        $promo_heading = trans("$string_file.apply_coupon");
        $promo_code = "";
        $discounted_amount = "";
        $bill_details = $this->bill_details;
        

        
        if (!empty($this->promo_code)) {
            $promo_code = $this->PromoCode->promoCode;
            $promo_heading = trans("$string_file.coupon_applied");
            // $discounted_amount = isset($this->discounted_amount) ? $this->discounted_amount : "";
            $discounted_amount = isset($this->discounted_amount) ? $merchant_helper->PriceFormat($this->discounted_amount, $this->merchant_id, $format_price, $trip_calculation_method) : "";
            $estimate_bill = $discounted_amount = $currency . " " . $discounted_amount;
        }

        $estimate_receipt = [];
        if (!empty($bill_details)) {
            $price = json_decode($bill_details, true);
            $estimate_receipt = HolderController::PriceDetailHolder($price, null, $currency,'user',$this->segment_id,"delivery_checkout", $this->merchant_id);
        }

        $delivery_product_pricing = false;
        if(isset($this->Merchant->Configuration->delivery_product_pricing) && $this->Merchant->Configuration->delivery_product_pricing){
            $delivery_product_pricing = true;
        }

        $return_array = [];
        $return_array['id'] = $this->id;
        $return_array['currency'] = $currency;
        $return_array['estimate_bill'] = $estimate_bill;
        $return_array['estimate_receipt'] = $estimate_receipt;
        $return_array['SelectedPaymentMethod'] = $SelectedPaymentMethod;
        $return_array['vehicle_details']['id'] = $this->VehicleType->id;
        $return_array['vehicle_details']['name'] = $this->VehicleType->VehicleTypeName;
        $return_array['vehicle_details']['weight'] = '';
        $return_array['vehicle_details']['icon'] = get_image($this->VehicleType->vehicleTypeImage, 'vehicle', $this->merchant_id,true,false);

        $return_array['request_type']['type'] = ((int)$this->booking_type == 1) ? trans("$string_file.request_normal") : trans("$string_file.request_later");
        $return_array['request_type']['time'] = ($this->booking_type == 1) ? '' : $this->later_booking_time;
        $return_array['request_type']['date'] = ($this->booking_type == 1) ? '' : $this->later_booking_date;

        $return_array['location']['pickup']['visible'] = true;
        $return_array['location']['pickup']['address']['name'] = $this->pickup_location;
        $return_array['location']['pickup']['address']['latitude'] = $this->pickup_latitude;
        $return_array['location']['pickup']['address']['longitude'] = $this->pickup_longitude;

        $return_array['location']['drop']['visible'] = ($this->drop_latitude) ? true : false;
        $return_array['location']['drop']['address']['name'] = $this->drop_location;
        $return_array['location']['drop']['address']['latitude'] = (string)$this->drop_latitude;
        $return_array['location']['drop']['address']['longitude'] = (string)$this->drop_longitude;

        $return_array['packages'] = [];
        $return_array['additional_mover_charge'] = !empty($this->PriceCard->additional_mover_charge) ? (string)$this->PriceCard->additional_mover_charge : (string)0;

        $products = DeliveryProduct::where([['merchant_id','=',$this->merchant_id],['status','=',1]])->get();
        $product_list = [];
        foreach ($products as $product){
            $product_list[] = array(
                'id' => $product->id,
                'segment_id' => $product->segment_id,
                'merchant_id' => $product->merchant_id,
                'product_name' => $product->ProductName,
                'weight_unit' => $product->WeightUnit->WeightUnitName,
                // 'price' => $product->price,
                'price' => (!empty($product->price)) ? $merchant_helper->PriceFormat($product->price, $this->merchant_id, $format_price, $trip_calculation_method) : $product->price,
                'delivery_product_image' =>  !empty($product->delivery_product_image) ? get_image($product->delivery_product_image, 'delivery_product_image',$this->merchant_id,true,false) : "",
                'description' => !empty($product->Description) ? $product->Description : ""

            );
        }
        $return_array['product_list'] = $product_list;

        $delivery_drop_details = [];
        $delivery_checkout_details = DeliveryCheckoutDetail::where([['booking_checkout_id','=',$this->id]])->orderBy('stop_no')->get();
        $delivery_checkout_detail_pending = DeliveryCheckoutDetail::where([['booking_checkout_id','=',$this->id],['details_fill_status','=',0]])->get()->count();
        $volumetric_capacity_calculation_divisor_value = isset($this->Merchant->BookingConfiguration->volumetric_capacity_calculation) ? (float)$this->Merchant->BookingConfiguration->volumetric_capacity_calculation : 1.00;
        $volumetric_capacity = "";
        $no_of_box = "";
        $weight = "";
        $length = "";
        $width = "";
        $height = "";
        $package_name = "";
        $vehicleDeliveryPackage = [];
        if(count($delivery_checkout_details) > 0){
            foreach($delivery_checkout_details as $delivery_checkout_detail){
                array_push($delivery_drop_details, array(
                    'id' => $delivery_checkout_detail->id,
                    'stop_no' => $delivery_checkout_detail->stop_no,
                    'drop_location' => $delivery_checkout_detail->drop_location,
                    'drop_latitude' => $delivery_checkout_detail->drop_latitude,
                    'drop_longitude' => $delivery_checkout_detail->drop_longitude,
                    'receiver_name' => ($delivery_checkout_detail->receiver_name != null) ? $delivery_checkout_detail->receiver_name : "",
                    'receiver_phone' => ($delivery_checkout_detail->receiver_phone != null) ? $delivery_checkout_detail->receiver_phone : "",
                    'receiver_image' => ($delivery_checkout_detail->receiver_image != null) ? $delivery_checkout_detail->receiver_image : "",
                    'additional_notes' => ($delivery_checkout_detail->additional_notes != null) ? $delivery_checkout_detail->additional_notes : "",
                    'product_data' => ($delivery_checkout_detail->product_data != null) ? json_decode($delivery_checkout_detail->product_data,true) : [],
                    'product_image_one' => ($delivery_checkout_detail->product_image_one != null) ? get_image($delivery_checkout_detail->product_image_one, 'product_image', $this->merchant_id,true,false) : "",
                    'product_image_two' => ($delivery_checkout_detail->product_image_two != null) ? get_image($delivery_checkout_detail->product_image_two, 'product_image', $this->merchant_id,true,false) : "",
                    'details_fill_status' => ($delivery_checkout_detail->details_fill_status == 1),
                ));
               $jsonData = $delivery_checkout_detail->vehicle_delivery_package_data;
                if(!empty($jsonData)){
                    $vehicle_delivery_data = json_decode($jsonData,true);
                    foreach($vehicle_delivery_data as $vehicle_delivery_array){
                        $no_of_box = $vehicle_delivery_array['no_of_box'];
                        $volCap = (float)$vehicle_delivery_array['length'] * (float)$vehicle_delivery_array['width'] * (float)$vehicle_delivery_array['height'];
                        $volumetric_capacity = $volCap/$volumetric_capacity_calculation_divisor_value;
                        $totalVolCapacity = (float)$volumetric_capacity * (float)$no_of_box;
                        $weight = $vehicle_delivery_array['weight'];
                        $length = $vehicle_delivery_array['length'];
                        $width = $vehicle_delivery_array['width'];
                        $height = $vehicle_delivery_array['height'];
                        $package_name = $vehicle_delivery_array['product_name'];

                        $vehicleDeliveryPackage[] = [
                            'volumetric_capacity' => (string)$totalVolCapacity,
                            'height'=> $height,
                            'width'=> $width,
                            'length'=> $length,
                            'weight'=> $weight,
                            'no_of_box'=> $no_of_box,
                            'product_name'=> $package_name,
                            'vehicle_delivery_package_id'=> isset($delivery_checkout_detail->vehicle_delivery_package_id) ? $delivery_checkout_detail->vehicle_delivery_package_id : ""
                        ];
                    }
                }
            }
         }
        $return_array['vehicle_delivery_packages'] = $vehicleDeliveryPackage;
        $return_array['delivery_drop_details_pending'] = ($delivery_checkout_detail_pending > 0) ? true : false;
        $return_array['delivery_drop_details'] = $delivery_drop_details;
        $return_array['promo_code'] = $promo_code;
        $return_array['discounted_amount'] = (string)$discounted_amount;
        $return_array['promo_heading'] = $promo_heading;
        $return_array['additional_mover_enable'] = $additional_mover == 1 ? true : false;
        $return_array['delivery_product_pricing'] = $delivery_product_pricing;
        return $return_array;
    }
}
