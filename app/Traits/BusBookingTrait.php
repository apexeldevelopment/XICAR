<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 28/11/23
 * Time: 2:48 PM
 */

namespace App\Traits;


use App\Models\Onesignal;
use App\Models\User;

trait BusBookingTrait
{
    use MerchantTrait;

    public function notifyBusBookingUser($bus_booking, $notification_type){
        $bus_booking_master = $bus_booking->BusBookingMaster;
        $item = $bus_booking_master->Segment;
        $large_icon = isset($item->Merchant[0]['pivot']->segment_icon) && !empty($item->Merchant[0]['pivot']->segment_icon) ? get_image($item->Merchant[0]['pivot']->segment_icon, 'segment', $bus_booking_master->merchant_id, true) : get_image($item->icon, 'segment_super_admin', NULL, false);

        $user = User::find($bus_booking->user_id);
        setLocal($user->language);

        $segment_name = !empty($item->Name($bus_booking_master->merchant_id)) ? $item->Name($bus_booking_master->merchant_id) : $item->slag;
        $string_file = $this->getStringFile($bus_booking_master->merchant_id);

        $lang_bus_booking = trans("$string_file.bus_booking");

        $title = "";
        $message = "";
        $type = "";
        // title and message of notification based on order status
        switch ($notification_type) {
            case "BUS_BOOKING_ASSIGN":
                $type = "BUS_BOOKING_ASSIGN";
                $title = $segment_name . ' ' . $lang_bus_booking . ' ' . trans("$string_file.assigned");
                $message = trans("$string_file.bus_booking_driver_assigned");
                break;
            case "BUS_BOOKING_START":
                $type = "BUS_BOOKING_START";
                $title = $segment_name . ' ' . $lang_bus_booking . ' ' . trans("$string_file.started");
                $message = trans("$string_file.bus_booking_started");
                break;
            case "BUS_BOOKING_END":
                $type = "BUS_BOOKING_END";
                $title = $segment_name . ' ' . $lang_bus_booking . ' ' . trans("$string_file.completed");
                $message = trans("$string_file.bus_booking_completed");
                break;
            case "BUS_BOOKING_MASTER_CANCEL":
                $type = "BUS_BOOKING_MASTER_CANCEL";
                $title = $segment_name . ' ' . $lang_bus_booking . ' ' . trans("$string_file.bus_cancelled");
                $message = trans("$string_file.bus_booking_bus_cancelled");
                break;
            case "BUS_BOOKING_NOTIFY":
                $type = "BUS_BOOKING_NOTIFY";
                $title = $segment_name . ' ' . $lang_bus_booking . ' ' . trans("$string_file.upcoming");
                $message = trans("$string_file.upcoming_bus_booking_notify");
                break;
            case "BUS_BOOKING_CONFIRM":
                $type = "BUS_BOOKING_CONFIRM";
                $title = $segment_name . ' ' . trans("$string_file.bus_booking") . ' ' . trans("$string_file.confirmed");
                $message = trans("$string_file.bus_booking_confirmed");
                break;
            case "BUS_BOOKING_DELIVERY_CONFIRM":
                $type = "BUS_BOOKING_DELIVERY_CONFIRM";
                $title = $segment_name . ' ' . trans("$string_file.package_delivery") . ' ' . trans("$string_file.confirmed");
                $message = trans("$string_file.bus_booking_delivery_confirmed");
                break;
            case "BUS_BOOKING_DELIVERY_START":
                $type = "BUS_BOOKING_DELIVERY_START";
                $title = $segment_name . ' ' . trans("$string_file.package_delivery") . ' ' . trans("$string_file.picked");
                $message = trans("$string_file.bus_booking_delivery_pickup_confirmed");
                break;
            case "BUS_BOOKING_DELIVERY_END":
                $type = "BUS_BOOKING_DELIVERY_END";
                $title = $segment_name . ' ' . trans("$string_file.package_delivery") . ' ' . trans("$string_file.delivered");
                $message = trans("$string_file.bus_booking_delivery_completed");
                break;
        }
        $data['notification_type'] = $type;
        $data['segment_type'] = $item->slag;
        $data['segment_sub_group'] = $item->sub_group_for_app;
        $data['segment_group_id'] = $item->segment_group_id;
        $data['segment_data'] = [
            'bus_booking_id' => $bus_booking->id,
        ];
        $arr_param['user_id'] = $bus_booking->user_id;
        $arr_param['data'] = $data;
        $arr_param['message'] = $message;
        $arr_param['merchant_id'] = $bus_booking_master->merchant_id;
        $arr_param['title'] = $title; // notification title
        $arr_param['large_icon'] = $large_icon;
        Onesignal::UserPushMessage($arr_param);
        setLocal();
    }

    public function notifyBusBookingDriver($bus_booking_master, $notification_type, $user_bus_booking = NULL, $user_bus_booking_id = NULL){
        if(!empty($bus_booking_master->driver_id)){
            $bus_booking_id = null;
            if(!empty($user_bus_booking)){
                $bus_booking_id = $user_bus_booking->id;
            }elseif(!empty($user_bus_booking_id)){
                $bus_booking_id = $user_bus_booking_id;
            }

            // get string file
            $string_file = $this->getStringFile($bus_booking_master->Merchant);

            $lang_bus_booking = trans("$string_file.bus_booking");

            $segment = $bus_booking_master->Segment;
            $segment_name = $segment->Name($bus_booking_master->merchant_id);
            $title = "";
            $message = "";
            $large_icon = isset($segment->Merchant[0]['pivot']->segment_icon) && !empty($segment->Merchant[0]['pivot']->segment_icon) ? get_image($segment->Merchant[0]['pivot']->segment_icon, 'segment', $bus_booking_master->merchant_id, true) : get_image($segment->icon, 'segment_super_admin', NULL, false);

            switch ($notification_type){
                case "USER_CANCEL":
                    $type = "USER_BUS_BOOKING_CANCEL";
                    $title = $segment_name . ' ' . $lang_bus_booking . ' ' . trans("$string_file.cancelled");
                    $message = trans("$string_file.user_bus_booking_cancelled");
                    break;
                case "BOOKING_ASSIGN":
                    $type = "BUS_BOOKING_ASSIGN";
                    $title = $segment_name . ' ' . $lang_bus_booking . ' ' . trans("$string_file.assigned");
                    $message = trans("$string_file.new_bus_booking_assigned");
                    break;
                case "BOOKING_NOTIFY":
                    $type = "BUS_BOOKING_NOTIFY";
                    $title = $segment_name . ' ' . $lang_bus_booking . ' ' . trans("$string_file.upcoming");
                    $message = trans("$string_file.upcoming_bus_booking_notify");
                    break;
                default:
                    $type = "";
            }
            $segment_data = ["bus_booking_master_id" => $bus_booking_master->id, "bus_booking_id" => $bus_booking_id];
            $data['notification_type'] = $type;
            $data['segment_type'] = $bus_booking_master->Segment->slag;
            $data['segment_sub_group'] = $bus_booking_master->Segment->sub_group_for_app; // its segment sub group for app
            $data['segment_group_id'] = $bus_booking_master->Segment->segment_group_id; // for handyman
            $data['segment_data'] = $segment_data;
            $arr_param = ['driver_id' => $bus_booking_master->driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $bus_booking_master->merchant_id, 'title' => $title, 'large_icon' => $large_icon];
            Onesignal::DriverPushMessage($arr_param);
        }
    }

    public function shippingPrice($request, $price_card, $tax = 0): array
    {
        $all_packages_total_price = 0;
        $packages = array_values(json_decode($request->package_details));

        $per_stop_charges = 0;
        if(!empty($price_card) && isset($request->pickup_point_id) && isset($request->drop_point_id)){
            $per_stop_charges = $price_card->package_delivery_base_fare;

            foreach($price_card->StopPointsPrice as $stop_price){
                if($stop_price->id == $request->pickup_point_id)
                    $per_stop_charges += $stop_price->pivot->price;
                elseif($stop_price->id == $request->drop_point_id)
                    break;
                else
                    $per_stop_charges += $stop_price->pivot->price;
            }
        }

        $price_details = array_map(function ($package) use ($price_card,$per_stop_charges, &$all_packages_total_price) {
            $volumetric_weight = 0;
            if (!empty($package->length) && !empty($package->width) && !empty($package->height)) {
                $volumetric_weight = ($package->length * $package->width * $package->height) / 5000;
            }
            $final_weight_to_consider = max($volumetric_weight, $package->weight);
            $amount =  (double)$final_weight_to_consider * $per_stop_charges * $package->quantity;
            $all_packages_total_price += $amount;
            $package->amount= round_number($amount, 2);
            return $package;
        }, $packages);

        $tax_amount =  ($all_packages_total_price * $tax)/100;
        return [
            "currency"=>$price_card->CountryArea->Country->isoCode,
            "all_packages_total_amount"=>round_number($all_packages_total_price, 2),
            "tax"=>round_number($tax_amount,2),
            "final_amount"=> round_number($all_packages_total_price+$tax_amount, 2),
            "package_amount_details"=>$price_details,
        ];
    }
}
