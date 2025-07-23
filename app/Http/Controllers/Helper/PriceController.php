<?php

namespace App\Http\Controllers\Helper;

use App\Models\Booking;
use App\Models\BookingConfiguration;
use App\Models\Configuration;
use App\Models\Driver;
use App\Http\Controllers\Helper\Merchant;
use App\Models\HandymanBiddingOrder;
use App\Models\HandymanOrder;
use App\Models\PriceCard;
use App\Models\PriceCardCommission;
use App\Models\PriceCardSlab;
use App\Models\PriceCardSlabDetail;
use App\Models\PricingParameter;
use App\Models\HandymanCommission;
use App\Http\Controllers\Controller;
use App\Models\ReferralCompanyDiscount;
use App\Models\ReferralDiscount;
use App\Models\ReferCommissionFare;
use App\Models\PaymentConfiguration;
use App\Models\DriverReferralDiscount;
use App\Models\ReferralDriverDiscount;
use App\Models\ReferralUserDiscount;
use App\Models\User;
use Illuminate\Support\Arr;
use App\Traits\MerchantTrait;

class PriceController extends Controller
{
    use MerchantTrait;
    public function Insurce(PriceCard $priceCard, $amount, $booking_id)
    {
        $InsurnceAmount = $priceCard->insurnce_type == 1 ? $priceCard->insurnce_value : $amount * $priceCard->insurnce_value;
        if ($InsurnceAmount < 1) {
            return [];
        } else {
            $name = "Insurance Fee";
            $PricingParameter = PricingParameter::where([['merchant_id', '=', $priceCard->merchant_id], ['parameterType', '=', 17]])->first();
            if (!empty($PricingParameter)) {
                $name = $PricingParameter->id;
            }
            $amountFormat = new Merchant();
            $amount = $amountFormat->TripCalculation($InsurnceAmount, $priceCard->merchant_id);
            $parameter[] = array('price_card_id' => $priceCard->id, 'booking_id' => $booking_id, 'parameter' => $name, 'amount' => $amount, 'type' => "CREDIT", 'code' => "");
        }
    }

    public function BillAmount($data = array())
    {
        $amountFormat = new Merchant();
        $price_card_id = array_key_exists("price_card_id", $data) ? $data['price_card_id'] : 0;
        $merchant_id = array_key_exists("merchant_id", $data) ? $data['merchant_id'] : 0;
        $distance = array_key_exists("distance", $data) ? $data['distance'] : 0;
        $time = array_key_exists("time", $data) ? $data['time'] : 0;
        $booking_id = array_key_exists("booking_id", $data) ? $data['booking_id'] : NULL;
        $waitTime = array_key_exists("waitTime", $data) ? $data['waitTime'] : 0;
        $dead_milage_distance = array_key_exists("dead_milage_distance", $data) ? $data['dead_milage_distance'] : 0;
        $outstanding_amount = array_key_exists("outstanding_amount", $data) ? $data['outstanding_amount'] : 0;
        $number_of_rider = array_key_exists("number_of_rider", $data) ? $data['number_of_rider'] : 0;
        $user_id = array_key_exists("user_id", $data) ? $data['user_id'] : NULL;
        $driver_id = array_key_exists("driver_id", $data) ? $data['driver_id'] : NULL;
        $units = array_key_exists("units", $data) ? $data['units'] : 1;

        $hotel_id = array_key_exists("hotel_id", $data) ? $data['hotel_id'] : NULL;
        $additional_movers = array_key_exists("additional_movers", $data) ? $data['additional_movers'] : 0;
        $total_drop_location = array_key_exists("total_drop_location", $data) ? $data['total_drop_location'] : 1;

        $priceCard = PriceCard::find($price_card_id);
        $booking = !empty($booking_id) ? Booking::find($booking_id) : null;
        $total_drop_location = !empty($booking) ? $booking->total_drop_location : $total_drop_location;

        $utc_timzone = date_default_timezone_get(); // get UTC time zone
        date_default_timezone_set($priceCard->CountryArea->timezone);
        $booking_time = (array_key_exists("booking_time", $data) && !empty($data['booking_time'])) ? $data['booking_time'] : date('H:i:s');
        $booking_date = (array_key_exists("booking_date", $data) && !empty($data['booking_date'])) ? $data['booking_date'] : date('Y-m-d');
        date_default_timezone_set($utc_timzone);

        $newArray = self::CalculateBill($price_card_id, $distance, $time, $booking_id, $waitTime, $dead_milage_distance, $outstanding_amount, $units, $booking_date, $booking_time);
        $merchant = \App\Models\Merchant::find($merchant_id);
        $carditnewArray = array_filter($newArray, function ($e) {
            return ($e['type'] == "CREDIT");
        });

        $amount = array_sum(array_pluck($carditnewArray, 'amount'));
        
        $bookingFeeArray = array_filter($newArray, function ($e) {
            return (array_key_exists('parameterType', $e) ? $e['parameterType'] == "19" : []);
        });
        $bookingFee = '0.0';
        if (!empty($bookingFeeArray)):
            $bookingFee = array_sum(Arr::pluck($bookingFeeArray, 'amount'));
        endif;
//        if (!empty($bookingFee)){
//            $amount = $amount+$bookingFee;
//            $parameter = array('subTotal' => $amount, 'price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans('api.booking_fee'), 'parameterType' => "17", 'amount' => (string)$bookingFee, 'type' => "CREDIT", 'code' => "");
//            array_push($newArray, $parameter);
//        }

        $priceCardValues = $priceCard->PriceCardValues;
        foreach ($priceCardValues as $priceCardValue){
            if ($priceCardValue->PricingParameter->parameterType == 16){ // Minimum fair
                if($amount < $priceCardValue->parameter_price){
                    $amount = $priceCardValue->parameter_price;
                    $total_payable = $amount - $bookingFee;
                    $billData = array();
                    foreach($newArray as $billDetail){
                        if(array_key_exists('amount',$billDetail)){
                            if(array_key_exists('parameterType',$billDetail)){
                                if($billDetail['parameterType'] == 10 ){
                                    $billDetail['amount'] = $total_payable;
                                }elseif($billDetail['parameterType'] != 19){ //Booking Fee
                                    $billDetail['amount'] = "0.00";
                                }
                            }
                        }
                        if(isset($billDetail['subTotal'])){
                            $billDetail['subTotal'] = $total_payable;
                        }
                        $billData[]=$billDetail;
                    }
                    $newArray=$billData;
                }
            }elseif($priceCardValue->PricingParameter->parameterType == 11){
                if(isset($priceCardValue->discount_value_type)){
                    $disValueType = $priceCardValue->discount_value_type;
                        $amountCredit = array_sum(array_pluck($carditnewArray, 'amount'));
                        if($amountCredit > $priceCardValue->parameter_price){
                            if($disValueType == 2){ // for fixed discount
                                $debitnewArray = array_filter($newArray, function ($e) {
                                    return ($e['type'] == "DEBIT");
                                });
                                
                                $amountDebit = array_sum(array_pluck($debitnewArray, 'amount'));
                                
                                $amount = $amountCredit-$amountDebit;
                            }
                            elseif($disValueType == 1){ //for percentage discount
                                $discPer = $priceCardValue->parameter_price;
                                $debitAmount = $amountCredit * ($discPer/100);
                                
                                $amount = $amountCredit - $debitAmount;
                            }
                        }
                        
                }
            
            }
        }

        if ($number_of_rider > 1) {
            $number_of_rider = $number_of_rider - 1;
            $amount += ($priceCard->extra_sheet_charge * $number_of_rider);
        }
        // additional_mover_charges
        if ($additional_movers > 0){
            $string_file = $this->getStringFile($merchant_id);
            $additional_mover_charge = $additional_movers * $priceCard->additional_mover_charge;
            $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans("$string_file.additional_mover_charges"), 'amount' => $additional_mover_charge, 'type' => "CREDIT", 'code' => "");
            array_push($newArray, $parameter);
            $amount += $additional_mover_charge;
        }

        // additional drop price
        if ($total_drop_location > 1){
            $string_file = $this->getStringFile($merchant_id);
            $way_points = $total_drop_location - 1;
            $additional_stop_charges = $way_points * $priceCard->additional_stop_charges;
            $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans("$string_file.additional_stop_charges"), 'amount' => $additional_stop_charges, 'type' => "CREDIT", 'code' => "");
            array_push($newArray, $parameter);
            $amount += $additional_stop_charges;
        }

//        $booking = !empty($booking_id) ? Booking::find($booking_id) : [];
//        // No Of Bag Charges
//        if (!empty($booking) && isset($priceCard->Merchant->Configuration->chargable_no_of_bags) && $priceCard->Merchant->Configuration->chargable_no_of_bags == 1){
//            $string_file = $this->getStringFile($merchant_id);
//            if($booking->no_of_bags > 0 && $priceCard->per_bag_charges > 0){
//                $per_bag_charges = $priceCard->per_bag_charges * $booking->no_of_bags;
//                $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans("$string_file.bag_charges"), 'amount' => $per_bag_charges, 'type' => "CREDIT", 'code' => "");
//                array_push($newArray, $parameter);
//                $amount += $per_bag_charges;
//            }
//        }
//
//        // No Of Pat Charges
//        if (!empty($booking) && isset($priceCard->Merchant->Configuration->chargable_no_of_pats) && $priceCard->Merchant->Configuration->chargable_no_of_pats == 1){
//            $string_file = $this->getStringFile($merchant_id);
//            if($booking->no_of_pats > 0 && $priceCard->per_pat_charges > 0){
//                $per_pat_charges = $priceCard->per_pat_charges * $booking->no_of_pats;
//                $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans("$string_file.pat_charges"), 'amount' => $per_pat_charges, 'type' => "CREDIT", 'code' => "");
//                array_push($newArray, $parameter);
//                $amount += $per_pat_charges;
//            }
//        }

        $AmountWithOutDiscountAndSpecial = $amount;
        $AmountWithOutDiscount = $amount;
        $toolCharge = 0;
        $surge = 0;
        $Extracharge = 0;
        $insurnce_amount = 0;
        $total_tax = 0;

        if ($priceCard->sub_charge_status == 1 && $priceCard->sub_charge_value > 0) {
            $surge = $priceCard->sub_charge_type == 1 ? $priceCard->sub_charge_value : bcdiv($AmountWithOutDiscountAndSpecial, $priceCard->sub_charge_value, 2);
            $surge = $amountFormat->TripCalculation($surge, $merchant_id);
            $amount += $surge;
            $AmountWithOutDiscount += $surge;
            $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => "Surge-Charge", 'amount' => $surge, 'type' => "CREDIT", 'code' => "");
            array_push($newArray, $parameter);
        }

        $newExtraCharge = new ExtraCharges();
        $timeCharge = $newExtraCharge->nightchargeEstimate($price_card_id, $booking_id, $AmountWithOutDiscountAndSpecial, $booking_date, $booking_time);
        if (!empty($timeCharge)) {
            $Extracharge = array_sum(array_pluck($timeCharge, 'amount'));
            $Extracharge = $amountFormat->TripCalculation($Extracharge, $merchant_id);
            $amount += $Extracharge;
            $AmountWithOutDiscount += $Extracharge;
            $newArray = array_merge($newArray, $timeCharge);
        }

        $promoDiscount = "0.00";
        if (!empty($booking->PromoCode)) {
            $code = $booking->PromoCode->promoCode;
            if ($booking->PromoCode->promo_code_value_type == 1) {
                $promoDiscount = $booking->PromoCode->promo_code_value;
            } else {
                $promoMaxAmount = !empty($booking->PromoCode->promo_percentage_maximum_discount) ? $booking->PromoCode->promo_percentage_maximum_discount : 0;
                $promoDiscount = ($amount * $booking->PromoCode->promo_code_value) / 100;
                $promoDiscount = (($promoDiscount > $promoMaxAmount) && ($promoMaxAmount > 0)) ? $promoMaxAmount : $promoDiscount;
            }
            $amount = $amount > $promoDiscount ? $amount - $promoDiscount : '0.00';
            $amount = $amountFormat->TripCalculation($amount, $merchant_id);
//            $parameter = array('subTotal' => $booking->PromoCode->id, 'price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => $code, 'parameterType' => "PROMO CODE", 'amount' => (string)$promoDiscount, 'type' => "DEBIT", 'code' => $code, 'freeValue' => $booking->PromoCode->promo_code_value);
            $parameter = array('subTotal' => $booking->PromoCode->id, 'price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => "promo_code", 'parameterType' => "PROMO CODE", 'amount' => (string)$promoDiscount, 'type' => "DEBIT", 'code' => $code, 'freeValue' => $booking->PromoCode->promo_code_value);
            array_push($newArray, $parameter);
        }

         // pricing calculation according to distance slab like from 1 to 5 km charges will be 10 rs per km
         if (!empty($priceCard->distance_slab_id) && isset($priceCard->distance_slab_id)) {
            $totalDistanceCharges = 0;
           $DistanceSlab = $priceCard->DistanceSlab;
           $left_distance = $units == 1 ? ($distance / 1000) : ($distance / 1609.34);
           foreach(json_decode($DistanceSlab->details) as $key=>$data){
               if(($data->to - $data->from) <= $left_distance){
                   $chargable_distance = $data->to - $data->from;
                   $totalDistanceCharges += $chargable_distance * $data->fare;
                   $left_distance -= $chargable_distance;
               }else{
                   $totalDistanceCharges += $left_distance * $data->fare;
                   $left_distance -= $left_distance;
                   break;
               }
           }
           $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => "Distance Charges", 'amount' => (string)$totalDistanceCharges, 'type' => "CREDIT", 'code' => "");
           array_push($newArray, $parameter);
           $amount += $totalDistanceCharges;
       }

        $taxes_array = $this->CalculateTaxes($price_card_id, ($amount - $outstanding_amount), $booking_id);

        if (!empty($taxes_array)):
            if(isset($booking) && !empty($booking) && $booking->Merchant->ApplicationConfiguration->driver_vat_configuration == 1 && isset($booking->Driver->vat_number) && $booking->Driver->is_vat_liable == 2){
                $newArray = array_merge($newArray, $taxes_array);
                $total_tax = array_sum(array_pluck($taxes_array, 'amount'));
                $arrTax = [];
                $billData = [];
                $baseAmount = "";
                $subTotal = "";
                foreach($newArray as $billDetail){
                    if(array_key_exists('amount',$billDetail)){
                        if(array_key_exists('parameterType',$billDetail)){
                            if($billDetail['parameterType'] == 10){
                                $billDetail['subTotal'] += $total_tax;
                                $billDetail['amount'] += $total_tax;
                            }elseif($billDetail['parameterType'] == 13){
                                $billDetail['amount'] = "0.00";
                            }
                        }
                        $billData[]=$billDetail;
                    }
                    $newArray=$billData;
                }
                $amount += $total_tax;
                // dd($newArray,$amount);
                
            }else{
                $newArray = array_merge($newArray, $taxes_array);
                $total_tax = array_sum(array_pluck($taxes_array, 'amount'));
                $total_tax = sprintf('%0.2f', $total_tax);
                $amount += $total_tax;
            }
        endif;

        if (!empty($booking) && $booking->insurnce == 1) {
            $insurnce_amount = $priceCard->insurnce_type == 1 ? $priceCard->insurnce_value : ($priceCard->insurnce_value * $amount) / 100;
            $amount += $insurnce_amount;
            $parameter = array('subTotal' => $amount, 'price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => 'Insurance', 'parameterType' => "insurance", 'amount' => (string)$insurnce_amount, 'type' => "DEBIT", 'code' => "", 'freeValue' => "");
            array_push($newArray, $parameter);
        }
        $hotel_amount = 0;
        $hotel_amount = 0;
        if(!empty($hotel_id)){
            $price_card_commission = PriceCardCommission::where('price_card_id',$priceCard->id)->first();
            // dd($price_card_commission,$price_card_commission->hotel_commission_method == 1);
            // if Extra Hotel charges added
            if($price_card_commission->hotel_commission_method == 1){
                $hotel_amount = $price_card_commission->hotel_commission;
                // dd($hotel_amount);
                $amount += $hotel_amount;
                $parameter = array('subTotal' => round($amount,2), 'price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => 'Hotel Charges', 'parameterType' => "hotel_charge", 'amount' => (string)$hotel_amount, 'type' => "CREDIT", 'code' => "", 'freeValue' => "");
                array_push($newArray, $parameter);
            }
        }

        if (!empty($merchant->Configuration->toll_api) && array_key_exists('from', $data) && array_key_exists('to', $data)) {
            if($merchant->Configuration->toll_api ==1){
                $newTool = new Toll();
                $coordinates = array_key_exists('coordinates', $data) ? $data['coordinates'] : "";
                $toolPrice = $newTool->checkToll($merchant->Configuration->toll_api, $data['from'], $data['to'], $coordinates, $merchant->Configuration->toll_key);
                if (is_array($toolPrice) && array_key_exists('cost', $toolPrice)) {
                    if ($toolPrice['cost'] > 0) {
                        $toolCharge = $toolPrice['cost'];
                        $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => "TollCharges", 'amount' => $toolCharge, 'type' => "CREDIT", 'code' => "");
                        array_push($newArray, $parameter);
                        $amount += $toolCharge;
                    }
                }
            }else if($merchant->Configuration->toll_api == 2 || $merchant->Configuration->toll_api == 3){
                $manual_toll_charge = array_key_exists("manual_toll_charge", $data) ? (!empty($data['manual_toll_charge']) ? $data['manual_toll_charge'] : 0) : 0;
                $toolCharge = $manual_toll_charge;
                $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => "TollCharges", 'amount' => $toolCharge, 'type' => "CREDIT", 'code' => "");
                array_push($newArray, $parameter);
                $amount += $toolCharge;
            }
        }

        /**@ayush (User Subscription Package )*/
        if (!empty($booking)  && $merchant->ApplicationConfiguration->user_subscription_package == 1) {
            $subscriptionDetails = json_decode($booking->bill_details);
            $filteredDetails = array_values(array_filter($subscriptionDetails, function ($detail) {
                return $detail->parameter === "Subscription";
            }));
            if(isset($filteredDetails[0])){
                $discounted_amt = min($amount, (float)$filteredDetails[0]->amount);
                $filteredDetails[0]->booking_id = $booking->id;
                $amount -= $discounted_amt;
                array_push($newArray, (array)$filteredDetails[0]);
            }
        }

        if(!empty($booking) && isset($merchant->Configuration->delivery_product_pricing) && $merchant->Configuration->delivery_product_pricing == 1){
            $deliveryPriceDetails = json_decode($booking->bill_details);
                $filteredDetails = array_values(array_filter($deliveryPriceDetails, function ($detail) {
                    return $detail->parameter === "Delivery Price Charges";
                }));
                if(isset($filteredDetails[0])){
                    $amt = $filteredDetails[0]->amount;
                    $amount += $amt;
                    array_push($newArray, (array)$filteredDetails[0]);
                }
        }
        
        if(empty($booking) && !empty($merchant) && isset($merchant->Configuration->delivery_product_pricing) && $merchant->Configuration->delivery_product_pricing == 1){
            $parameter = array('price_card_id' => $price_card_id, 'booking_id' => "", 'parameter' => "Delivery Price Charges", 'amount' => 0.0, 'type' => "CREDIT", 'code' => "");
            array_push($newArray, $parameter);
        }

        $cancellation_array = array_filter($newArray, function ($e) {
            return ($e['parameter'] == "Cancellation fee");
        });
        $cancellation_amount_received = '0.0';
        if (!empty($cancellation_array)):
            $cancellation_amount_received = array_sum(array_pluck($cancellation_array, 'amount'));
            // $amount += $cancellation_amount_received;
        endif;
        
        return [
            'bill_details' => $newArray,
            'amount' => $amount,
            'promo' => $promoDiscount,
            'cancellation_amount_received' => $cancellation_amount_received,
            'subTotalWithoutSpecial' => $AmountWithOutDiscountAndSpecial,
            'subTotalWithoutDiscount' => ($AmountWithOutDiscount-$cancellation_amount_received),
            'toolCharge' => $toolCharge,
            'surge' => $surge,
            'extracharge' => $Extracharge,
            'insurnce_amount' => $insurnce_amount,
            'total_tax' => $total_tax,
            'booking_fee' => $bookingFee,
            'hotel_amount' => $hotel_amount,
        ];
    }

    public static function CalculateBill($price_card_id, $distance, $time, $booking_id, $waitmin = 0, $dead_milage_distance = 0, $outstand = 0, $units = 1, $booking_date = null, $booking_time = null)
    {
        $merchant = new Merchant();
        $distance = get_calculate_distance_unit($units, $distance);
        $booking = !empty($booking_id) ? Booking::find($booking_id) : NULL;
        $hour = $time / 60;
        $price_card = PriceCard::with(['PriceCardValues' => function ($query) {
            $query->with(['PricingParameter' => function ($q) {
                $q->orderBy('parameterType', 'ASC');
            }]);
        }])->find($price_card_id);
        $pricing_type = $price_card->pricing_type;
        $base_fare = $price_card->base_fare;
        $free_distance = $price_card->free_distance;
        $free_time = $price_card->free_time;
        $unit_name = "km";
        $parameter = [];
        // @Bhuvanesh
        // pricing type 3  is INPUT BY DRIVER
        $parameter_price = 0;
        if ($pricing_type == 1 || $pricing_type == 2 || $pricing_type == 3) {
            $price_card_values = $price_card->PriceCardValues;
            $subTotal = 0;
            $descDistanceTime = 0;
            if(!empty($base_fare) || (isset($price_card->base_fare_price_card_slab_id) && !empty($price_card->base_fare_price_card_slab_id))){
                $newArray = PricingParameter::whereHas('PricingType', function ($query) use ($pricing_type) {
                    $query->where('price_type', $pricing_type);
                })->whereHas('Segment', function($query) use($price_card){
                    $query->whereIn('segment_id', [$price_card->segment_id]);
                })->where([['parameterType', '=', 10], ['merchant_id', '=', $price_card->merchant_id]])->first();
                if (!empty($newArray)) {
                    if (!empty($base_fare)) {
                        $subTotal += $base_fare;
                        $subTotal = $merchant->TripCalculation($subTotal, $price_card->merchant_id);
                        $calc = ($free_time / 60);
                        $parameter[] = array('simply_amount' => "amount_without_spl_discount", 'subTotal' => $subTotal, 'price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => $newArray->id, 'parameterType' => $newArray->parameterType, 'amount' => $base_fare, 'description_distanceTime'=> $free_distance.' km , '.number_format($free_time, 2, '.', "") .' min', 'type' => "CREDIT", 'code' => "");
                    }else{
                        $base_fare_price_card_slab = self::getPriceCardSlabPrice($price_card->base_fare_price_card_slab_id, null, $booking_date, $booking_time);
                        $free_distance = $base_fare_price_card_slab['free_distance'];
                        $free_time = $base_fare_price_card_slab['free_time'];
                        $subTotal += $base_fare_price_card_slab['amount'];
                        $subTotal = $merchant->TripCalculation($subTotal, $price_card->merchant_id);
                        $calc = ($free_time / 60);
                        $parameter[] = array('simply_amount' => "amount_without_spl_discount", 'subTotal' => $subTotal, 'price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => $newArray->id, 'parameterType' => $newArray->parameterType, 'amount' => $base_fare_price_card_slab['amount'], 'description_distanceTime'=> $free_distance.' km , '.number_format($free_time, 2, '.', "") .' min','type' => "CREDIT", 'code' => "");
                    }
                }
            }
            foreach ($price_card_values as $value) {
                $disatnceValue = 1;
                $code = "";
                $parameterAmount = 0.0;
                $parameter_price = (float)$value->parameter_price;
                $pricing_parameter = $value->PricingParameter;
                $parameterType = $pricing_parameter->parameterType;
                $parameterName = $pricing_parameter->id;
                $free_value = $value->free_value;
                $type = "CREDIT";
                $parameterAmount = 0.0;
                $extra = 0.0;
                $extra_distance = 0.0;
                $unit_name = "km";
                if($units == 2) 
                    $unit_name = "miles";
                else if($units == 3)
                    $unit_name = "meters";
                if ($parameterType == 13 || $parameterType == 12 || $parameterType == 16) {
                    continue;
                }
                switch ($parameterType) {
                    case "1":
                        if ($distance > $free_distance) {
                            $extra_distance = $distance - $free_distance;
                            $descDistanceTime = $extra_distance;
                            if(isset($value->price_card_slab_id) && !empty($value->price_card_slab_id)){
                                $parameterAmount = self::getPriceCardSlabPrice($value->price_card_slab_id, $extra_distance, $booking_date, $booking_time);
                            }else{
                                $parameterAmount = $descDistanceTime * $parameter_price;
                            }
                        } else {
                            $parameterAmount = "0.00";
                        }
                        $subTotal += $parameterAmount;
                        $unit_name = $unit_name;
                        break;
                    case "2":
                        if ($hour > $free_time) {
                            $extra = $hour - $free_time;
                            $descDistanceTime = $extra;
                            $parameterAmount = $parameter_price * $descDistanceTime;
                        } else {
                            $parameterAmount = "0.00";
                        }
                        $subTotal += $parameterAmount;
                        
                        $unit_name = 'min';
                        break;
                    case "6":
                        if ($dead_milage_distance > $free_value) {
                            $extra = $dead_milage_distance - $free_value;
                            $descDistanceTime = $extra;
                            $parameterAmount = $descDistanceTime * $parameter_price;
                        } else {
                            $parameterAmount = "0.00";
                        }
                        $subTotal += $parameterAmount;
                        
                        break;
                    case "8":
                        if ($time > $free_time) {
                            $extra = $time - $free_time;
                            $descDistanceTime = $extra;
                            if(isset($value->price_card_slab_id) && !empty($value->price_card_slab_id)){
                                $parameterAmount = self::getPriceCardSlabPrice($value->price_card_slab_id, $extra, $booking_date, $booking_time);
                            }else{
                                $parameterAmount = $parameter_price * $descDistanceTime;
                            }
                        } else {
                            $parameterAmount = "0.00";
                        }
                        $subTotal += $parameterAmount;
                        
                        $unit_name = 'min';
                        break;
                    case "9":
                        if ($waitmin > $free_value) {
                            $extra = $waitmin - $free_value;
                            $descDistanceTime = $extra;
                            $parameterAmount = $parameter_price * $descDistanceTime;
                        } else {
                            $descDistanceTime = $waitmin;
                            $parameterAmount = "0.00";
                        }
                        $subTotal += $parameterAmount;
                        
                        $unit_name = 'min';
                        break;
                    case "14":
                        $descDistanceTime = $distance;
                        $parameterAmount = $free_value == 1 ? $parameter_price : $descDistanceTime * $parameter_price;
                        $subTotal += $parameterAmount;
                        break;
                    case "15":
                        $days = $hour > 0 ? ceil($hour / 24) : 1;
                        $totalDistance = $days * $free_value;
                        if ($totalDistance > $distance) {
                            $parameterAmount = $days * $parameter_price;
                        } else {
                            $extra = $distance - $totalDistance;
                            $descDistanceTime = $extra;
                            $parameterAmount = ($days * $parameter_price) + ($disatnceValue * $descDistanceTime);
                        }
                        $subTotal += $parameterAmount;
                        
                        break;
                    case "17":
                        $parameterAmount = $parameter_price;
                        $subTotal += $parameterAmount;
                        break;
                    case "19":
                        $parameterAmount = $parameter_price;
                        $subTotal += $parameterAmount;
                        break;
                    case "18":
                        if(!empty($booking)){
                            $configuration = BookingConfiguration::where([['merchant_id', '=', $price_card->merchant_id]])->first();
                            if($configuration->final_bill_calculation == 1 || $configuration->final_bill_calculation_delivery == 1){ //If Bill mathod equals to actual
                                if($booking->onride_waiting_time > 0){
                                    $parameterAmount = $parameter_price * $booking->onride_waiting_time;
                                } else {
                                    $parameterAmount = "0.00";
                                }
                                $subTotal += round($parameterAmount,2);
                            }
                        }
                        break;
                    case "20":
                        if ($waitmin > $free_value) {
                            $parameterAmount = $parameter_price;
                        } else {
                            $parameterAmount = "0.00";
                        }
                        $subTotal += $parameterAmount;
                        break;
                    case "21":
                        if(!empty($booking_id)){
                            $booking = BookingCheckout::where("merchant_id",$price_card->merchant_id)->find($booking_id);
                            if(empty($booking)){
                                $booking = Booking::where("merchant_id",$price_card->merchant_id)->find($booking_id);
                            }
                            $drop_point = $booking->total_drop_location-1;
                            if($drop_point > 0){
                                $parameterAmount = $parameter_price * $drop_point;
                            }else{
                                $parameterAmount = "0.00";
                            }
                            $subTotal += round($parameterAmount,2);
                        }
                        break;
                        case "23":
                        $parameterAmount = $parameter_price;
                        $type = "CREDIT";
                        if($value->additional_fare_value_type == 1){
                            $parameterAmount = $subTotal * ($parameterAmount/100);
                            $descDistTimeSet = number_format($parameterAmount, 2, '.', '');
                            $subTotal += $parameterAmount;
                        }else{
                            $subTotal += $parameterAmount;
                        }
                        break;
                    default:
                        // $parameterAmount = $parameter_price;
                        // if ($parameterType == 11) {
                        //     $type = "CREDIT";
                        // }
                        // $subTotal += $parameterAmount;

                        //changes for discount $parameterType == 11

                        $parameterAmount = $parameter_price;
                        $descDistTimeSet = "";
                        if ($parameterType == 11) {
                            $descDistTimeSet = number_format($parameterAmount, 2, '.', '');
                            $type = "DEBIT";
                            if($value->discount_value_type == 1){
                                $parameterAmount = $subTotal * ($parameterAmount/100);
                                $descDistTimeSet = number_format($parameterAmount, 2, '.', '');
                                $subTotal -= $parameterAmount;
                                }else{
                                    $subTotal -= $parameterAmount;
                                }
                        }else{
                            $subTotal += $parameterAmount;
                        }
                }
                // pricing type 3  is INPUT BY DRIVER
                if($pricing_type == 3){
                    $parameterAmount = 0;
                    $subTotal = 0;
                }
                // else{
                //     if($parameterType != 17){
                //         $parameterAmount = $merchant->TripCalculation($parameterAmount, $price_card->merchant_id);
                //     }
                // }
                $outstand = $merchant->TripCalculation($outstand, $price_card->merchant_id);
                $parameter[] = array('subTotal' => $subTotal, 'price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => $parameterName, 'parameterType' => $parameterType, 'amount' => (string)$parameterAmount, 'description_distanceTime'=>isset($descDistTimeSet) ? $descDistTimeSet : (number_format($descDistanceTime, 2, '.', '').' '.$unit_name.' * '.(string)$parameter_price),'type' => $type, 'code' => $code, 'freeValue' => $parameter_price);
            }
            if ($outstand > 0) {
                $subTotal += $outstand;
                $parameter[] = array('subTotal' => $subTotal, 'price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => "Cancellation fee", 'amount' => $outstand, 'type' => "CREDIT", 'code' => "", 'freeValue' => $parameter_price);
            }
            return $parameter;
        } else {
            return trans('api.message62');
        }
    }

    public function CalculateTaxes($price_card_id, $amount, $booking_id = 0)
    {
        $price_card = PriceCard::with(['PriceCardValues' => function ($query) {
            $query->whereHas('PricingParameter', function ($param) {
                $param->where('parameterType', 13);
            })->with(['PricingParameter' => function ($q) {
                $q->orderBy('parameterType', 'ASC');
            }]);
        }])->find($price_card_id);

        if (!empty($price_card)):
            $pricing_type = $price_card->pricing_type;
            $parameter = array();
            if ($pricing_type == 1 || $pricing_type == 2) {
                $price_card_values = $price_card->PriceCardValues;
                $subTotal = $amount;
                foreach ($price_card_values as $value) {
                    $code = "";
                    $parameter_price = $value->parameter_price;
                    $pricing_parameter = $value->PricingParameter;
                    $parameterType = $pricing_parameter->parameterType;
                    $parameterName = $pricing_parameter->id;
                    $parameterValueType = isset($value->value_type) && !empty($value->value_type) ? $value->value_type : 1;
                    if($parameterValueType == 1){ // Percentage
                        if ($subTotal > 0 && $parameter_price > 0) {
//                            $parameterAmount = ($subTotal * $parameter_price) / 100;
                            // Bhuvanesh - Above line commented because, all taxes calculate only on sub total amount
                            $parameterAmount = ($amount * $parameter_price) / 100;
                        } else {
                            $parameterAmount = "0.00";
                        }
                    }else{ // Flat charges
                        $parameterAmount = $parameter_price;
                    }
                    $subTotal = $subTotal + $parameterAmount;
                    $parameter[] = array('subTotal' => $subTotal, 'price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => $parameterName, 'parameterType' => $parameterType, 'amount' => (string)$parameterAmount, 'type' => 'TAXES', 'code' => $code, 'freeValue' => $parameter_price);
                }
            }
            return $parameter;
        endif;
        return [];
    }

//    public function Refer($id, $type)
//    {
//        $refer = ReferralDiscount::where([['receiver_id', '=', $id], ['receiver_type', '=', $type], ['referral_available', '=', 1]])->first();
//        if (!empty($refer)) {
//            return $refer;
//        }
//    }
//
//    public function getSenderRefer($id,$type){
//        $refer = ReferralDiscount::where([['sender_id', '=', $id], ['sender_type', '=', $type],['offer_type','=',4],['sender_get_ride','!=', 0],['referral_available', '=', 1]])->first();
//        if (!empty($refer)) {
//            return $refer;
//        }
//    }
//
//    public function getReferCalculation($data, $amount,$booking_id)
//    {
//        switch ($data->offer_type) {
//            case "1":
//                $referAmount = $data->offer_value;
//                $amount = $this->getReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                return $amount;
//                break;
//            case "2":
//                $referAmount = ($amount * $data->offer_value) / 100;
//                $amount = $this->getReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                return $amount;
//                break;
//            case "3":
//                $commission_slabs = json_decode($data->offer_value, true);
//                $referAmount = 0;
//                foreach ($commission_slabs as $commission_slab){
//                    if (($commission_slab['start_range'] <= $amount) && ($amount <= $commission_slab['end_range'])){
//                        $referAmount = $commission_slab['commission'];
//                        break;
//                    }
//                }
//                if ($data->limit == 0){
//                    // For unlimited discount
//                    $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                }
//                if ($data->limit == 1){
//                    if (!empty($data->limit_usage) && $data->limit_usage > 0){
//                        $limit = $data->limit_usage;
//                        $no_of_day = $data->no_of_day;
//                        // Day count after signup
//                        if (!empty($data->day_count) && $data->day_count == 1){
//                            if ($data->receiver_type == 1){
//                                $getData = User::find($data->receiver_id);
//                            }else{
//                                $getData = Driver::find($data->receiver_id);
//                            }
//                            $first_date = $getData->created_at;
//                            $last_date = date('Y-m-d',strtotime($first_date. '+'.$no_of_day.' days'));
//                            if (date('Y-m-d') < $last_date){
//                                $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                                $limit--;
//                                if($limit == 0){
//                                    $data->referral_available = 2;
//                                }
//                            }else{
//                                $limit = 0;
//                                $data->referral_available = 2;
//                            }
//                            $data->limit_usage = $limit;
//                            $data->save();
//                        }elseif (!empty($data->day_count) && $data->day_count == 2){
//                            // Day count after first ride
//                            $keyWord = $data->receiver_type == 1 ? 'user_id' : 'driver_id';
//                            $getData = Booking::where([[$keyWord,'=',$data->receiver_id],['booking_status','=',1005]])->oldest()->first();
//                            if (!empty($getData)){
//                                $first_date = $getData->updated_at;
//                                $last_date = date('Y-m-d',strtotime($first_date. '+'.$no_of_day.' days'));
//                                if (date('Y-m-d') < $last_date){
//                                    $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                                    $limit--;
//                                    if($limit == 0){
//                                        $data->referral_available = 2;
//                                    }
//                                }else{
//                                    $limit = 0;
//                                    $data->referral_available = 2;
//                                }
//                                $data->limit_usage = $limit;
//                                $data->save();
//                            }
//                        }elseif(empty($data->day_count) && $data->day_count == NULL){
//                            $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                            $limit--;
//                            if($limit == 0){
//                                $data->referral_available = 2;
//                            }
//                            $data->limit_usage = $limit;
//                            $data->save();
//                        }
//                    }
//                    else{
//                        $no_of_day = $data->no_of_day;
//                        if (!empty($data->day_count) && $data->day_count == 1){
//                            if ($data->receiver_type == 1){
//                                $getData = User::find($data->receiver_id);
//                            }else{
//                                $getData = Driver::find($data->receiver_id);
//                            }
//                            $first_date = $getData->created_at;
//                            $last_date = date('Y-m-d',strtotime($first_date. '+'.$no_of_day.' days'));
//                            if (date('Y-m-d') < $last_date){
//                                $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                            }else{
//                                $data->referral_available = 2;
//                                $data->save();
//                            }
//                        }
//                        if (!empty($data->day_count) && $data->day_count == 2){
//                            $keyWord = $data->receiver_type == 1 ? 'user_id' : 'driver_id';
//                            $getData = Booking::where([[$keyWord,'=',$data->receiver_id],['booking_status','=',1005]])->oldest()->first();
//                            if (!empty($getData)){
//                                $first_date = $getData->updated_at;
//                                $last_date = date('Y-m-d',strtotime($first_date. '+'.$no_of_day.' days'));
//                                if (date('Y-m-d') < $last_date){
//                                    $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                                }else{
//                                    $data->referral_available = 2;
//                                    $data->save();
//                                }
//                            }
//                        }
//                    }
//                }
//                return $amount;
//                break;
//            case "4":
//                $referAmount = $amount;
//                $amount = $this->getReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                return $amount;
//                break;
//        }
//    }
//
//    public function getReferralOfferCalculation($data,$referAmount,$amount,$booking_id){
//        if ($data->limit == 0){
//            $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//        }
//        if ($data->limit == 1){
//            if (!empty($data->limit_usage) && $data->limit_usage > 0){
//                $limit = $data->limit_usage;
//                $no_of_day = $data->no_of_day;
//                if (!empty($data->day_count) && $data->day_count == 1){
//                    if ($data->receiver_type == 1){
//                        $getData = User::find($data->receiver_id);
//                    }else{
//                        $getData = Driver::find($data->receiver_id);
//                    }
//                    $first_date = $getData->created_at;
//                    $last_date = date('Y-m-d',strtotime($first_date. '+'.$no_of_day.' days'));
//                    if (date('Y-m-d') <= $last_date){
//                        $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                        $limit--;
//                        if($limit == 0){
//                            $data->referral_available = 2;
//                        }
//                    }else{
//                        $limit = 0;
//                        $data->referral_available = 2;
//                    }
//                    $data->limit_usage = $limit;
//                    $data->save();
//                }elseif (!empty($data->day_count) && $data->day_count == 2){
//                    $keyWord = $data->receiver_type == 1 ? 'user_id' : 'driver_id';
//                    $getData = Booking::where([[$keyWord,'=',$data->receiver_id],['booking_status','=',1005]])->oldest()->first();
//                    if (!empty($getData)){
//                        $first_date = $getData->updated_at;
//                        $last_date = date('Y-m-d',strtotime($first_date. '+'.$no_of_day.' days'));
//                        if (date('Y-m-d') < $last_date){
//                            $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                            $limit--;
//                            if($limit == 0){
//                                $data->referral_available = 2;
//                            }
//                        }else{
//                            $limit = 0;
//                            $data->referral_available = 2;
//                        }
//                        $data->limit_usage = $limit;
//                        $data->save();
//                    }
//                }elseif(empty($data->day_count) && $data->day_count == NULL){
//                    $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                    $limit--;
//                    if($limit == 0){
//                        $data->referral_available = 2;
//                    }
//                    $data->limit_usage = $limit;
//                    $data->save();
//                }
//            }else{
//                $no_of_day = $data->no_of_day;
//                if (!empty($data->day_count) && $data->day_count == 1){
//                    if ($data->receiver_type == 1){
//                        $getData = User::find($data->receiver_id);
//                    }else{
//                        $getData = Driver::find($data->receiver_id);
//                    }
//                    $first_date = $getData->created_at;
//                    $last_date = date('Y-m-d',strtotime($first_date. '+'.$no_of_day.' days'));
//                    if (date('Y-m-d') < $last_date){
//                        $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                    }else{
//                        $data->referral_available = 2;
//                        $data->save();
//                    }
//                }
//                if (!empty($data->day_count) && $data->day_count == 2){
//                    $keyWord = $data->receiver_type == 1 ? 'user_id' : 'driver_id';
//                    $getData = Booking::where([[$keyWord,'=',$data->receiver_id],['booking_status','=',1005]])->oldest()->first();
//                    if (!empty($getData)){
//                        $first_date = $getData->updated_at;
//                        $last_date = date('Y-m-d',strtotime($first_date. '+'.$no_of_day.' days'));
//                        if (date('Y-m-d') < $last_date){
//                            $amount = $this->ReferralOfferCalculation($data,$referAmount,$amount,$booking_id);
//                        }else{
//                            $data->referral_available = 2;
//                            $data->save();
//                        }
//                    }
//                }
//            }
//        }
//        return $amount;
//    }
//
//    public function ReferralOfferCalculation($data,$referAmount,$amount,$booking_id = 0){
//        $isReferralNull = false;
//        $walletTransaction = new WalletTransaction();
//        if ($data->offer_applicable == 1 || $data->offer_applicable == 3) {
//            if ($data->sender_type == 1) {
//                if ($data->offer_type == 4){
//                    $amount = $amount - $referAmount;
//                    $isReferralNull = false;
//                }else{
//                    $senderData = User::find($data->sender_id);
//                    //                    $balance = $senderData->wallet_balance + $referAmount;
//                    //                    $senderData->wallet_balance = $balance;
//                    //                    $senderData->save();
//                    //                    $walletTransaction->userWallet($senderData, $referAmount, 1,$booking_id);
//                    $paramArray = array(
//                        'user_id' => $senderData->id,
//                        'booking_id' => $booking_id,
//                        'amount' => $referAmount,
//                        'narration' => 1,
//                        'platform' => 2,
//                        'payment_method' => 1,
//                    );
//                    WalletTransaction::UserWalletCredit($paramArray);
////                    CommonController::UserWalletCredit($senderData->id,$booking_id,$referAmount,1,2,1);
//
//                    $isReferralNull = true;
//                }
//                $this->UserReferDiscount($data->sender_id,$referAmount,$booking_id);
//            }elseif ($data->sender_type == 2) {
//                if ($data->offer_type != 4){
//                    $this->DriverReferDiscount($data->sender_id, $referAmount, $booking_id);
//                    $isReferralNull = true;
//                }
//            }elseif ($data->sender_type == 0 && $data->sender_id == 0){
//                if ($data->offer_type != 4){
//                    $default_code = $data->getReferralSystem->default_code;
//                    $merchant_id = $data->getReferralSystem->merchant_id;
//                    if ($default_code == 1){
//                        $this->CompanyReferDiscount($data->id,$referAmount,$booking_id,$merchant_id);
//                    }
//                    $isReferralNull = true;
//                }
//            }
//        }
//        // Offer for  Receiver and Both
//        if ($data->offer_applicable == 2 || $data->offer_applicable == 3) {
//            if ($data->receiver_type == 1) {
//                if($data->offer_type == 1){
//                    if ($amount < $referAmount){
//                        $referAmount = $amount;
//                        $amount = 0;
//                    }else{
//                        $amount = $amount - $referAmount;
//                    }
//                }else if($data->offer_type != 4){
//                    $amount = $amount - $referAmount;
//                }
//                $this->UserReferDiscount($data->receiver_id,$referAmount,$booking_id);
//                $isReferralNull = false;
//            } elseif ($data->receiver_type == 2) {
//                if ($data->offer_type != 4){
//                    $this->DriverReferDiscount($data->receiver_id, $referAmount, $booking_id);
//                    $isReferralNull = true;
//                }
//            }
//        }
//        $refer_amount = $isReferralNull ? NULL : $referAmount ;
//        return array('amount'=> $amount,'refer_amount'=> $refer_amount);
//    }
//
//    public function DriverReferDiscount($id, $amount, $booking_id)
//    {
//        $senderData = Driver::find($id);
//        ReferralDriverDiscount::create([
//            'merchant_id' => $senderData->merchant_id,
//            'booking_id' => $booking_id,
//            'driver_id' => $id,
//            'amount' => $amount,
//            'payment_status' => 0,
//            'expire_status' => 0
//        ]);
//    }
//
//    public function CompanyReferDiscount($referral_discount_id, $amount,$booking_id,$merchant_id)
//    {
//        ReferralCompanyDiscount::create([
//            'merchant_id' => $merchant_id,
//            'referral_discount_id' => $referral_discount_id,
//            'booking_id' => $booking_id,
//            'amount' => $amount
//        ]);
//    }
//
//    public function UserReferDiscount($id,$amount,$booking_id){
//        $userData = User::select('merchant_id')->find($id);
//        if (!empty($userData)){
//            ReferralUserDiscount::create([
//                'merchant_id' => $userData->merchant_id,
//                'booking_id' => $booking_id,
//                'user_id' => $id,
//                'amount' => $amount,
//            ]);
//        }
//    }
//
//    public function DriverRefer($driver_id)
//    {
//        $refer = DriverReferralDiscount::where([['driver_id', '=', $driver_id], ['referral_available', '=', 1]])->oldest()->first();
//        if (!empty($refer)) {
//            return $refer;
//        }
//    }

//    public function getDriverReferEarning($merchant_id,$driver_id,$from,$to){
//        $data = ReferralDriverDiscount::where([['merchant_id','=',$merchant_id],['driver_id','=',$driver_id]])->whereBetween('created_at',array($from,$to))->sum('amount');
//        return $data;
//    }

    public static function getPriceCardSlabPrice($price_card_slab_id, $calculation_value = null, $booking_date = null, $booking_time = null){
        if(empty($booking_date) || empty($booking_time)){
            date_default_timezone_set("Asia/Kolkata");
        }
        if(!empty($booking_date)){
            $current_date = $booking_date;
            $current_day = strtoupper(date("D", strtotime($current_date)));
        }else{
            $current_date = date("Y-m-d");
            $current_day = strtoupper(date("D"));
        }
        if(!empty($booking_time)){
            $current_date_time = $booking_date." ".$booking_time;
        }else{
            $current_date_time = date("Y-m-d H:i:s");
        }

        $price_card_slab = PriceCardSlab::find($price_card_slab_id);

        $return_array = false;
        $free_distance = 0;
        $free_time = 0;
        $amount = 0;

        $initial_calculation_value = $calculation_value;

        if(!empty($price_card_slab)){
            $return_array = $price_card_slab->type == "BASE_FARE" ? true : false;

            $slab_details = PriceCardSlabDetail::where("price_card_slab_id",$price_card_slab_id)->where(function($q)use($current_day){
                $q->whereRaw("find_in_set('".$current_day."', week_days)")->orWhereNull("week_days");
            })->get();

            $slab_detail = [];
            foreach($slab_details as $slab){
                $from_date_time = $current_date." ".$slab->from_time.":00";
                $to_date_time = $current_date." ".$slab->to_time.":00";
                if($slab->from_time > $slab->to_time){
                    $to_date_time = date('Y-m-d', strtotime($current_date. ' + 1 days'))." ".$slab->to_time.":00";
                }
                if($from_date_time <= $current_date_time && $to_date_time >= $current_date_time){
                    $slab_detail = $slab;
                    break;
                }
            }

            if(!empty($slab_detail)){
                $details = json_decode($slab_detail->details, true);
                foreach($details as $slab){
                    if($price_card_slab->type == "BASE_FARE"){
                        $amount = $slab['base_fare'];
                        $free_distance = $slab['free_distance'];
                        $free_time = $slab['free_time'];
                    }elseif($price_card_slab->type == "DISTANCE"){
                        if($calculation_value > 0){
                            if($initial_calculation_value > $slab['distance_to']){
                                $amount += $slab['distance_to']/$slab['unit']*$slab['charges'];
                                $calculation_value -= $slab['distance_to'];
                            }else{
                                $amount += $calculation_value/$slab['unit']*$slab['charges'];
                                $calculation_value = 0;
                            }
                        }
                    }elseif($price_card_slab->type == "RIDE_TIME"){
                        if($calculation_value > 0){
                            if($calculation_value > $slab['to']){
                                $val = ceil($slab['to']/$slab['unit']);
                                $amount += $val/$slab['unit']*$slab['charges'];
                                $calculation_value -= $slab['to'];
                            }else{
                                $val = ceil($calculation_value/$slab['unit']);
                                $amount += $val*$slab['charges'];
                                $calculation_value = 0;
                            }
                        }
                    }
                }
            }
        }
        if($return_array){
            return array("amount" => $amount, "free_distance" => $free_distance, "free_time" => $free_time);
        }else{
            return $amount;
        }
    }

    public function getHandymanBillingDetails($handyman_order_id, $calling_from = 'FINAL_ORDER', $driver_id = NULL){
        if ($calling_from == 'FINAL_ORDER'){
            $handyman_order = HandymanOrder::find($handyman_order_id);
            if (!empty($handyman_order)){
                $bill_details = json_decode($handyman_order->bill_details,true);
                if (!empty($bill_details)){
                    $final_bill = [];
                    $string_file = $this->getStringFile($handyman_order->merchant_id);
                    $currency = $handyman_order->User->Country->isoCode;
                    foreach($bill_details as $bill_detail){
                        $key_name = $string_file.'.'.$bill_detail['name'];
                        $final_bill[] = [
                            'name' => trans("$key_name"),
                            'value' => $currency.' '.$bill_detail['value'],
                            'bold' => $bill_detail['bold'],
                            'type' => $bill_detail['type'],
                        ];
                    }
                    return $final_bill;
                }else{
                    $bill = [
                        [
                            'name' => 'base_fare',
                            'value' => $handyman_order->cart_amount,
                            'bold' => false,
                            'type' => 'CREDIT',
                        ],
                        [
                            'name' => 'tax',
                            'value' => $handyman_order->tax,
                            'bold' => false,
                            'type' => 'CREDIT',
                        ],
                    ];

                    if (!empty($handyman_order->promo_code_id)){
                        array_push($bill,[
                            'name' => 'discount',
                            'value' => $handyman_order->discount_amount,
                            'bold' => false,
                            'type' => 'DEBIT',
                        ]);
                    }

                    array_push($bill,[
                        'name' => 'total',
                        'value' => $handyman_order->total_booking_amount,
                        'bold' => true,
                        'type' => 'CREDIT',
                    ]);

                    $handyman_order->bill_details = json_encode($bill);
                    $handyman_order->save();
                    $handyman_order->refresh();
                    $this->getHandymanBillingDetails($handyman_order->id);
                }
            }
        }else{
            $handyman_order = HandymanBiddingOrder::find($handyman_order_id);
            $currency = $handyman_order->User->Country->isoCode;
            $string_file = $this->getStringFile($handyman_order->merchant_id);
            $actioned_driver = $handyman_order->ActionedDriver($driver_id)->first();
            $user_offer_price = !empty($handyman_order->user_offer_price) ? $handyman_order->user_offer_price : "0.0";
            $bidding_amount = !empty($actioned_driver->pivot->amount) ? $actioned_driver->pivot->amount : $user_offer_price;
            // $total = $bidding_amount + $handyman_order->tax;
             $total = $bidding_amount;
             
            $handyman_commission = HandymanCommission::where([['merchant_id','=',$handyman_order->merchant_id],['country_area_id','=',$handyman_order->country_area_id],['segment_id','=',$handyman_order->segment_id]])->first();
            
            $tax_amount = 0;
            if(!empty($handyman_commission) && !empty($handyman_commission->tax)){
                $tax_per = $handyman_commission->tax;
                $tax_amount = ($bidding_amount * $tax_per) / 100;
                if ($handyman_order->Merchant->Configuration->tax_calculation_flow == 2) {
                    $bidding_amount = $bidding_amount - $tax_amount;
                }
            }
            
            $bill_details = [
                [
                    'name' => trans("$string_file.base_fare"),
                    'value' => $currency.' '.$bidding_amount,
                    'bold' => false,
                    'type' => 'CREDIT',
                ],
                [
                     'name' => trans("$string_file.tax"),
                    // 'value' => $currency.' '.$handyman_order->tax,
                     'value' => $currency.' '.round_number($tax_amount, 2), 
                    'bold' => false,
                    'type' => 'CREDIT',
                ],
            ];

            if (!empty($handyman_order->promo_code_id)){
                array_push($bill_details,[
                    'name' => trans("$string_file.discount"),
                    'value' => $currency.' '.$handyman_order->discount_amount,
                    'bold' => false,
                    'type' => 'DEBIT',
                ]);

                $total -= $handyman_order->discount_amount;
            }
            
            //show tax included already
            if(!empty($tax_amount)){
                $total = ($handyman_order->Merchant->Configuration->tax_calculation_flow == 2)? $total - $tax_amount : $total + $tax_amount;
            }
            
            if (empty($driver_id)){
                $total = $handyman_order->total_booking_amount > 0 ? $currency.' '.round_number($handyman_order->total_booking_amount + $tax_amount, 2) : $currency.' '.$total;
            }else{
                $total = $currency.' '.$total;
            }

            array_push($bill_details,[
                'name' => trans("$string_file.total"),
                'value' => $total,
                'bold' => true,
                'type' => 'CREDIT',
            ]);

            return $bill_details;
        }
    }
}
