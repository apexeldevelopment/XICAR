<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\ReferralController;
use App\Http\Controllers\Helper\SmsController;
use App\Http\Controllers\Helper\FindDriverController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Controllers\Helper\Merchant as MerchantHelper;
use App\Http\Resources\UserConfiguration;
use App\Models\Application;
use App\Models\Booking;
use App\Models\BookingCheckout;
use App\Models\BookingConfiguration;
use App\Models\ApplicationConfiguration;
use App\Models\Configuration;
use App\Models\Country;
use App\Models\Document;
use App\Models\Driver;
use App\Models\Onesignal;
use App\Models\Outstanding;
use App\Models\SmsConfiguration;
use App\Models\UserCard;
use App\Traits\AreaTrait;
use Illuminate\Support\Facades\DB;
use App\Models\FavouriteDriver;
use App\Models\FamilyMember;
use App\Models\Merchant;
use App\Models\PromotionNotification;
use App\Models\UserDevice;
use App\Models\UserDocument;
use App\Models\User;
use App\Models\UserWalletTransaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Events\UserForgotPasswordEmailOtpEvent;
use App\Events\UserSignupEmailOtpEvent;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use App\Traits\ApiResponseTrait;
use App\Models\UserVehicleDocument;
use App\Models\UserVehicle;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    use ImageTrait, ApiResponseTrait, MerchantTrait, AreaTrait;

    public function checkDriver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkout_id' => 'required',
            'gender_match' => 'required',
        ]);
    }

//    public function CheckSeats(Request $request)
//    {
//        $result = 0;
//        $message = trans('api.nodriverAvailabe');
//        $user = $request->user('api');
//        $string_file = $this->getStringFile(NULL, $user->Merchant);
//        $validator = Validator::make($request->all(), [
//            'checkout_id' => ['required', 'exists:booking_checkouts,id'],
//            'wheel_chair_enable' => 'required',
//            'baby_seat_enable' => 'required',
//            'gender_match' => 'required',
//            'gender' => 'required_if:gender_match,1',
//            'no_seat_check' => 'required',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
//        }
//        $no_of_person = $request->no_of_person;
//        $no_of_children = $request->no_of_children;
//        $total_seats = $no_of_children + $no_of_person;
//        $checkout_id = $request->checkout_id;
//        $booking_data = BookingCheckout::find($checkout_id);
//        $booking_data->additional_information = $request->additional_information;
//        $booking_data->additional_notes = $request->additional_notes;
//        $merchant_id = $booking_data->merchant_id;
//        $country_area_id = $booking_data->country_area_id;
//        $service_type_id = $booking_data->service_type_id;
//        $vehicle_type_id = $booking_data->vehicle_type_id;
//        $latitude = $booking_data->pickup_latitude;
//        $longitude = $booking_data->pickup_longitude;
//        $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
////        $distance = $configuration->normal_ride_now_radius;
//        $limit = $configuration->normal_ride_now_request_driver;
//        if (!empty($booking_config->driver_ride_radius_request)) {
//            $ride_radius = json_decode($booking_config->driver_ride_radius_request, true);
//            if ($limit == 1) {
//                if (!empty($booking->ride_radius)) {
//                    $booking_ride_radius = explode(',', $booking->ride_radius);
//                    $remain_ride_radius_slot[] = $booking_ride_radius[0];
//                } else {
//                    $remain_ride_radius_slot = $ride_radius;
//                }
//            } elseif ($limit > 1) {
//                if (!empty($booking->ride_radius)) {
//                    $booking_ride_radius = explode(',', $booking->ride_radius);
//                    $remain_ride_radius = array_diff($ride_radius, $booking_ride_radius);
//                    $remain_ride_radius_slot = array_values($remain_ride_radius);
//                } else {
//                    $remain_ride_radius_slot = $ride_radius;
//                }
//            }
//        } else {
//            $remain_ride_radius_slot = array();
//        }
//
//        if ($request->gender_match == 1) {
//            $booking_data->gender = $request->gender;
//            $booking_data->save();
//        }
//        // pool case
//        if ($request->no_seat_check == 1) {
////             $booking_data->gender = $request->gender;
////             $booking_data->save();
//        }
//        if ($request->wheel_chair_enable == 1) {
//            $booking_data->wheel_chair_enable = $request->wheel_chair_enable;
//            $booking_data->save();
//        }
//
//        if ($request->baby_seat_enable == 1) {
//            $booking_data->baby_seat_enable = $request->baby_seat_enable;
//            $booking_data->save();
//        }
//        $drivers = Driver::GetNearestDriver([
//            'area' => $country_area_id,
//            'segment_id' => $booking_data->segment_id,
//            'latitude' => $latitude,
//            'longitude' => $longitude,
//            'distance' => !empty($remain_ride_radius_slot) ? $remain_ride_radius_slot[0] : null,
//            'limit' => $limit,
//            'service_type' => $service_type_id,
//            'vehicle_type' => $vehicle_type_id,
//            'user_gender' => $request->gender,
//            'ac_nonac' => $request->ac_nonac,
//            'wheel_chair' => $request->wheel_chair_enable,
//            'baby_seat' => $request->baby_seat_enable,
//        ]);
//        if (!empty($drivers) && $drivers->count() > 0) {
//            $booking_data->bags_weight_kg = $request->bags_weight_kg;
//            $booking_data->no_of_bags = $request->no_of_bags;
//            $booking_data->no_of_person = $request->no_of_person;
//            $booking_data->no_of_children = $request->no_of_children;
//            $booking_data->save();
//            $result = 1;
//            $message = trans("$string_file.success");
//        }
//        if ($result == 1) {
//            return $this->successResponse($message, []);
//        } else {
//            $string_file = $this->getStringFile($booking_data->merchant_id);
//            $message = trans("$string_file.no_driver_available_with_filter");
//            return $this->failedResponse($message, []);
//        }
//    }

//    public function AddFamilyMember(Request $request)
//    {
//        $user = $request->user('api');
//        $validator = Validator::make($request->all(), [
//            'name' => 'required',
//            'phoneNumber' => 'required|unique:family_members',
//            'age' => 'required|integer',
//            'child_terms' => 'required|between:1,1',
//            'gender' => 'required|between:1,2',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
//        }
//        $family = new FamilyMember();
//        $family->user_id = $user->id;
//        $family->name = $request->name;
//        $family->phoneNumber = $request->phoneNumber;
//        $family->gender = $request->gender;
//        $family->age = $request->age;
//        $family->save();
//        return response()->json(['result' => "1", 'message' => trans('api.familyMemberAdded'), 'data' => []]);
//
//    }
//
//    public function DeleteFamilyMember(Request $request)
//    {
//        //$user = $request->user('api');
//        $validator = Validator::make($request->all(), [
//            'id' => 'required|integer',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
//        }
//        //$family_mem = FamilyMember::where([['user_id',$user->id]])->get();
//        $family = FamilyMember::find($request->id);
//        $family->delete();
//        return response()->json(['result' => "1", 'message' => trans('api.familyMemberDeleted'), 'data' => []]);
//
//    }
//
//    public function ListFamilyMember(Request $request)
//    {
//        $user = $request->user('api');
//        $family = FamilyMember::where([['user_id', $user->id]])->get();
//        return response()->json(['result' => "1", 'message' => trans('api.familyMemberList'), 'data' => $family]);
//
//    }
//
//    public function check_wheelChair(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'checkout_id' => 'required|integer',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
//        }
//        $findDriver = new FindDriverController();
//        $checkout_id = $request->checkout_id;
//        $booking_data = BookingCheckout::find($checkout_id);
//        $country_area_id = $booking_data->country_area_id;
//        $service_type_id = $booking_data->service_type_id;
//        $vehicle_type_id = $booking_data->vehicle_type_id;
//        $latitude = $booking_data->pickup_latitude;
//        $longitude = $booking_data->pickup_longitude;
//        $merchant_id = $booking_data->merchant_id;
//        $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
//        $distance = $configuration->normal_ride_now_radius;
//        $limit = $configuration->normal_ride_now_request_driver;
//        $drivers = Driver::GetDriversWheelChair($latitude, $longitude, $distance, $vehicle_type_id, $service_type_id, $limit);
//
//        if (!empty($drivers->toArray())) {
//            $booking_data->wheel_chair_enable = 1;
//            $booking_data->save();
//            return response()->json(['result' => "1", 'message' => trans('api.driverAvailabe'), 'data' => []]);
//        } else {
//            return response()->json(['result' => "0", 'message' => trans('api.nodriverAvailabe'), 'data' => []]);
//        }
//
//    }

//    public function check_babySeat(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'checkout_id' => 'required|integer',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
//        }
//        $findDriver = new FindDriverController();
//        $checkout_id = $request->checkout_id;
//        $booking_data = BookingCheckout::find($checkout_id);
//        $country_area_id = $booking_data->country_area_id;
//        $service_type_id = $booking_data->service_type_id;
//        $vehicle_type_id = $booking_data->vehicle_type_id;
//        $latitude = $booking_data->pickup_latitude;
//        $longitude = $booking_data->pickup_longitude;
//        $merchant_id = $booking_data->merchant_id;
//        $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
//        $distance = $configuration->normal_ride_now_radius;
//        $limit = $configuration->normal_ride_now_request_driver;
//        $drivers = Driver::GetDriversBabySeat($latitude, $longitude, $distance, $vehicle_type_id, $service_type_id, $limit);
//
//        if (!empty($drivers->toArray())) {
//            $booking_data->baby_seat_enable = 1;
//            $booking_data->save();
//            return response()->json(['result' => "1", 'message' => trans('api.driverAvailabe'), 'data' => []]);
//        } else {
//            return response()->json(['result' => "0", 'message' => trans('api.nodriverAvailabe'), 'data' => []]);
//        }
//
//    }

    public function Referral(Request $request)
    {
        $user = $request->user('api');
        $merchant_id = $user->merchant_id;
        $request_for = $request->device;
        $string_file = $this->getStringFile($merchant_id);
        $ref = new ReferralController();
        $data = $ref->getReferralDetailsForApp("USER",$user->id, $request_for);
//        $referral = ReferralSystem::where([['merchant_id', '=', $merchant_id], ['country_id', '=', $country_id], ['application', '=', 0]])->first();
//        $application = Application::where([['merchant_id', '=', $merchant_id]])->first();
//        $iosUser = $application ? $application->ios_user_link : "";
//        $androidUser = $application ? $application->android_user_link : "";
//        $all_user = new BookingDataController();
//        $BusinessName = $merchant->BusinessName;
//        $code = $user->ReferralCode;
//        $msg = trans("$string_file.user_referral_msg");
////        $msg = $all_user->LanguageData($merchant_id, 29);
//        $heading = trans("$string_file.refer_code");
//        $description = trans("$string_file.refer_code_message");
//        if (empty($referral)) {
//            $a = array(
//                "refer_image" => "refer.png",
//                "refer_heading" => $heading,
//                "refer_explanation" => $description,
//                "start_date" => "",
//                "end_date" => "",
//                "refer_code" => $user->ReferralCode,
//                "refer_status" => "DEACTIVE",
//                "refer_offer" => "",
//                "sharing_text" => sprintf($msg, $BusinessName, $code, $iosUser, $androidUser)
//            );
//            return $this->successResponse(trans("$string_file.success"), $a);
////            return response()->json(['result' => "1", 'message' => trans('api.wrongpassword'), 'data' => $a]);
//        }
//        $refer_status = $referral->status = 1 ? trans('api.message92') : trans('api.message92');
//        switch ($referral->offer_type) {
//            case "1":
//                $offer_type = trans("$string_file.free_ride");
//                break;
//            case "2":
//                $offer_type = trans("$string_file.discount");
//                break;
//            case "3":
//                $offer_type = trans("$string_file.fixed_amount");
//                break;
//        }
//        $data = array(
//            "refer_image" => "refer.png",
//            "refer_heading" => $heading,
//            "refer_explanation" => $description,
//            "start_date" => $referral->start_date,
//            "end_date" => $referral->end_date,
//            "refer_code" => $user->ReferralCode,
//            "refer_status" => $refer_status,
//            "refer_offer" => $referral->offer_value . " " . $offer_type,
//            "sharing_text" => sprintf($msg, $BusinessName, $code, $iosUser, $androidUser)
//        );
        return $this->successResponse(trans("$string_file.success"), $data);
//        return response()->json(['result' => "1", 'message' => trans('api.wrongpassword'), 'data' => $data]);
    }

    public function AddMoneyWallet(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
//                response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }

        $paramArray = array(
            'user_id' => $user->id,
            'booking_id' => NULL,
            'amount' => $request->amount,
            'narration' => 2,
            'platform' => 2,
            'payment_method' => 2,
            'payment_option_id' => $request->payment_option_id,
            'transaction_id' => 2,
        );
        WalletTransaction::UserWalletCredit($paramArray);
        return $this->successResponse(trans("$string_file.money_added_in_wallet"));
    }

    public function WalletTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filter' => 'required|integer|between:1,3',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
//            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $merchant_helper = new MerchantHelper();
        if ($request->filter == 3) {
            $filter = array(1, 2, 3, 4);
        } else {
            $filter = ($request->filter == 2) ? array($request->filter) : array(1, 4);
        }
        $user = $request->user('api');
        $user_id = $user->id;
        $string_file = $this->getStringFile($user->merchant_id);
        $transaction = UserWalletTransaction::where([['user_id', '=', $user_id]])->whereIn('type', $filter)->orderBy('id', 'DESC')->paginate(10);
//        $newArray = $transaction->toArray();
        if ($transaction->count() == 0) {
            $newArray = $transaction->toArray();
            $result = array('wallet_balance' => "0.00", 'recent_transactoin' => []);
            return response()->json(['result' => "1", 'message' => trans("$string_file.wallet_transaction"), 'total_pages' => $newArray['last_page'], 'current_page' => $newArray['current_page'], 'data' => $result]);
        }

        foreach ($transaction as $value) {
            $type = $value->type;
            switch ($type) {
                case "1": // Credit
                    $transaction_value = trans("$string_file.credit");
                    $transaction_name = trans("$string_file.money_added_in_wallet");
                    $value_color = "2ecc71";
                    $image = view_config_image("static-images/dollar1.png");
                    break;
                case "2": // Debit
                    $transaction_value = trans("$string_file.debit");
                    $transaction_name = trans("$string_file.deductible_amount");
                    $value_color = "e74c3c";
                    $image = view_config_image("static-images/dollar.png");
                    break;
                case "3": // Transfered
                    $transaction_value = trans("$string_file.debit");
                    $transaction_name = trans("$string_file.amount_transferred");
                    $value_color = "e74c3c";
                    $image = view_config_image("static-images/dollar.png");
                    break;
                case "4": //Cashback
                    $transaction_value = trans("$string_file.credit");
                    $transaction_name = trans("$string_file.money_added_in_wallet") . '(' . trans("$string_file.cashback") . ')';
                    $value_color = "2ecc71";
                    $image = view_config_image("static-images/dollar1.png");
                    break;
            }
            $timezone = "";
            $date = $value->created_at;
            if(!empty($value->booking_id))
            {
                $timezone = $value->Booking->CountryArea->timezone;
            }
            elseif(!empty($value->order_id))
            {
                $timezone = $value->Order->CountryArea->timezone;
            }
            elseif(!empty($value->handyman_order_id))
            {
                $timezone = $value->HandymanOrder->CountryArea->timezone;
            }
            if(!empty($timezone))
            {
                $date = convertTimeToUSERzone($value->created_at, $timezone,null,$user->Merchant);
            }
             else
            {
                if(!empty($user->country_area_id))
                {
                 $timezone =  $user->CountryArea->timezone;
                }
               $date = convertTimeToUSERzone($date, $timezone,null,$user->Merchant);
            }

             $amount_received_from = "";
             $amount_transferred_to = "";
             if(!empty($value->wallet_transfer_id) && $value->narration == 12 && !empty($value->WalletTransfer))
             {
                 $amount_received_from = $value->WalletTransfer->first_name.' '.$value->WalletTransfer->last_name.' ('
                     .$value->WalletTransfer->UserPhone.')';
                 $amount_received_from = trans("$string_file.amount_received_from").' '.$amount_received_from;
             }
             elseif(!empty($value->wallet_transfer_id) && $value->narration == 13 && !empty($value->WalletTransfer))
             {
                 $amount_transferred_to = $value->WalletTransfer->first_name.' '.$value->WalletTransfer->last_name.' ('
                     .$value->WalletTransfer->UserPhone.')';
                 $amount_transferred_to = trans("$string_file.amount_transferred_to").' '.$amount_transferred_to;
             }

            $newArray = $transaction->toArray();
            $next_page_url = $newArray['next_page_url'];
            $next_page_url = $next_page_url == "" ? "" : $next_page_url;
            $data[] = array(
                'transaction_name' => $transaction_name,
                'type' => $transaction_value,
                // 'amount' => $value->amount,
                'amount' => $merchant_helper->PriceFormat($value->amount, $user->merchant_id),
                'date' => $date,
                'value_color' => $value_color,
                'icon' => $image,
                // 'amount_received_from'=>$amount_received_from,   //commented both because of wrong value getting from relation
                // 'amount_transferred_to'=>$amount_transferred_to,
                'amount_received_from'=>$value->description,
                'amount_transferred_to'=>$value->description,
                // 'description'=>$value->description,
            );
        }
        $result = array('wallet_balance' =>$merchant_helper->PriceFormat($user->wallet_balance, $user->merchant_id),'tap_customer_token' => !empty($user->tap_user_customer_token) ? (string)$user->tap_user_customer_token : "", 'recent_transactoin' => $data);
        return response()->json(['result' => "1", 'message' => trans("$string_file.success"), 'next_page_url' =>
            $next_page_url, 'total_pages' => $newArray['last_page'], 'current_page' => $newArray['current_page'], 'data' => $result]);
    }

    public function PromotionNotification(Request $request)
    {
        $arr_notifications = [];
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);

        $user_created_date = date('Y-m-d H:i:s', strtotime($user->created_at));
        $user_id = $user->id;
        $merchant_id = $user->merchant_id;
        $notifications = PromotionNotification::
        where(function ($q) {
            $q->where('expiry_date', '>=', date('Y-m-d'));
            $q->orWhere('expiry_date', NULL);
        })
            ->where(function ($q) use ($user_id) {
                $q->where('user_id', $user_id);
                $q->orWhere('user_id', NULL);
            })
            ->where([['show_promotion', '=', 1], ['merchant_id', '=', $merchant_id], ['application', '=', 2]])
            ->orderBy('created_at', 'DESC')
            ->where('created_at', '>=', $user_created_date)
            ->get();
        if (empty($notifications->toArray())) {
            return $this->failedResponse(trans("$string_file.data_not_found"));
        }
        foreach ($notifications as $key => $value) {
            $value->image = !empty($value->image) ? get_image($value->image, 'promotions', $value->merchant_id) : "";
            $value->created_at = convertTimeToUSERzone($value->created_at, $user->CountryArea->timezone ,null,$user->Merchant);
            $arr_notifications[] = $value;
        }
        return $this->successResponse(trans("$string_file.success"), $arr_notifications);
    }

    public function favouriteDriver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|integer',
            'segment_id' => 'required|integer',
            'action' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $user = $request->user('api');
        $user_id = $user->id;
        $string_file = $this->getStringFile();
        if ($request->action == 1) // add / update
        {
            FavouriteDriver::updateOrCreate(
                ['user_id' => $user_id, 'driver_id' => $request->driver_id],
                ['segment_id' => $request->segment_id]
            );
        } elseif ($request->action == 2) // delete
        {
//            $driver = (object)[];
            FavouriteDriver::where([['user_id', '=', $user_id], ['driver_id', '=', $request->driver_id], ['segment_id', '=', $request->segment_id]])->delete();
        }
        return response()->json(['result' => "1", 'message' => trans("$string_file.favourite"), 'data' => []]);
    }

    public function getFavouriteDriver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $user_id = $user->id;
        $merchant_id = $user->merchant_id;
        $arr_driver = FavouriteDriver::select('id', 'driver_id')->where([['user_id', '=', $user_id], ['segment_id', '=', $request->segment_id]
        ])->with(['Driver' => function ($q) {
            $q->select("id", "first_name", "last_name", "phoneNumber", "email", "profile_image", "rating");
        }])
            ->get();
        $arr_driver = $arr_driver->map(function ($item) use ($merchant_id) {
            return [
                'driver_id' => $item->Driver->id,
                'first_name' => $item->Driver->first_name,
                'last_name' => $item->Driver->last_name,
                'phone_number' => $item->Driver->phoneNumber,
                'rating' => $item->Driver->rating,
                'profile_image' => get_image($item->Driver->profile_image, 'driver', $merchant_id),
            ];
        });
        return $this->successResponse(trans("$string_file.data_found"), $arr_driver);
    }

    public function Location(Request $request)
    {
        $user = $request->user('api');
        $merchant_id = $user->merchant_id;
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $config = BookingConfiguration::select('google_key')->where([['merchant_id', '=', $merchant_id]])->first();
        $result = CommonController::GoogleAddress($request->latitude, $request->longitude, $config->google_key);
        if (empty($result)) {
            return $this->failedResponse(trans("$string_file.google_key_not_working"));
        }
        return $this->successResponse(trans("$string_file.location"), $result);
    }

    public function Otp(Request $request)
    {
        $merchant_id = $request->merchant_id;
        // Encrypt Decrypt Module
        $merchant = Merchant::Find($merchant_id);
        if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($request->user_name){
                    $request->merge(['user_name'=> decryptText($request->user_name,$secret,$iv)]);
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        ($request->for == 'EMAIL') ? $request->request->add(['email' => $request->user_name]) : $request->request->add(['phone' => $request->user_name]);
        $validator = Validator::make($request->all(), [
            'type' => 'required|integer',
            'for' => [
                'required', 'string',
                Rule::in(['EMAIL', 'PHONE']),
            ],
            'phone' => 'required_unless:for,EMAIL|regex:/^[0-9+]+$/',
            'email' => 'required_unless:for,PHONE|email',
//            'phone' => ['required_unless:for,EMAIL',
//                Rule::unique('users', 'UserPhone')->where(function ($query) use ($merchant_id) {
//                    return $query->where([['user_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
//                })],
//            'email' => ['required_unless:for,PHONE',
//                Rule::unique('users', 'email')->where(function ($query) use ($merchant_id) {
//                    return $query->where([['user_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
//                })],
            //'user_name'=>'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $app_config = ApplicationConfiguration::where([['merchant_id', $merchant_id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        $common_message = trans("$string_file.otp_sent_to_your");
        $message = "";
        $action = "";
        $is_number_registered = false;
        if ($request->for == "PHONE") {
            $phone = $request->phone;
            $email = "";
            $message = $common_message . ' ' . trans("$string_file.phone");
        } elseif ($request->for == "EMAIL") {
            $phone = "";
            $email = $request->email;
            $message = $common_message . ' ' . trans("$string_file.email");
        }
        if ($request->type == 1) // signup
        {
            $action = 'USER_SIGN_OTP';
            $fields['phone'] = ['required_unless:for,EMAIL',
                Rule::unique('users', 'UserPhone')->where(function ($query) use ($merchant_id) {
                    return $query->where([['user_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                })];

            $fields['email'] = ['required_unless:for,PHONE',
                Rule::unique('users', 'email')->where(function ($query) use ($merchant_id) {
                    return $query->where([['user_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                })];

            // custom messages
            $customMessages = [
                'phone.required_unless' => trans("$string_file.phone_required"),
                'phone.unique' => trans("$string_file.phone_already_used"),
        
                'email.required_unless' => trans("$string_file.email_required"),
                'email.unique' => trans("$string_file.email_already_used"),
            ];

            $validator = Validator::make($request->all(), $fields,$customMessages);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }

        } elseif ($request->type == 2) // forgot password
        {
            $action = 'USER_FORGOT_PASSWORD_OTP';
            if ($request->for == 'PHONE') {
                $validationMsg = trans("$string_file.phone_number_is_not_registered");
                $fields['user_name'] = ['required', 'regex:/^[0-9+]+$/',
                    Rule::exists('users', 'UserPhone')->where(function ($query) use ($merchant_id) {
                        return $query->where([['user_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                    })];

                    $customMessages = [
                        'user_name.required' => trans("$string_file.phone_required"),
                        'user_name.regex' => trans("$string_file.phone_valid"),
                        'user_name.exists' => $validationMsg,
                    ];
            } else {
                $validationMsg = trans("$string_file.email_is_not_registered");
                $fields['user_name'] = ['required', 'email',
                    Rule::exists('users', 'email')->where(function ($query) use ($merchant_id) {
                        return $query->where([['user_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                    })];

                    $customMessages = [
                        'user_name.required' => trans("$string_file.email_required"),
                        'user_name.email' => trans("$string_file.email_valid"),
                        'user_name.exists' => $validationMsg,
                    ];

            }

            $validator = Validator::make($request->all(), $fields, $customMessages);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }
        } elseif ($request->type == 3) {
            $action = 'USER_LOGIN_OTP';
             if($request->for == "PHONE"){
                $user = User::where([['merchant_id', $merchant_id], ['UserPhone', $request->user_name], ['user_delete', "=", NULL]])->first();
                if($user){
                    $is_number_registered = true;
                }
            }
        }
        // for tezgo customisation
        // if((!empty($app_config) && $app_config->otp_from_firebase == 1) && (!empty($request->otp_firebase) && $request->otp_firebase == 1) )
        //otp from firebase
        if((!empty($app_config) && $app_config->otp_from_firebase == 1) || (!empty($request->otp_firebase) && $request->otp_firebase == 1) )
        {
            return $this->successResponse(trans("$string_file.success"), []);
        }
        $auto_fill = false;
        $otp = mt_rand(111111, 999999);
        if (isset($app_config->auto_fill_otp) && $app_config->auto_fill_otp == 1) {
            $auto_fill = true;
        } else {
            $SmsConfiguration = SmsConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
            if(!empty($SmsConfiguration->sms_provider) || !empty($email)){
                $auto_fill = false;
            }
        }
        if($request->for == 'EMAIL'){
            event(new UserSignupEmailOtpEvent($merchant_id, $request->email, $otp));
        }else{
                $sms = new SmsController();
                $sms->SendSms($merchant_id, $phone, $otp, $action, $email);
        }

        $default_otp_enable = false;
        $default_otp = "";
        if (isset($app_config->default_otp) && $app_config->default_otp == 1) {
            $default_otp_enable = true;
            $default_otp = "2022";
        }

        //Encrypt and decrypt
        if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($otp){
                    $otp = encryptText($otp,$secret,$iv);
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        return $this->successResponse($message, array('auto_fill' => $auto_fill, 'otp' => (string)$otp, 'default_otp_enable' => $default_otp_enable, 'default_otp' => $default_otp,'is_number_registered'=> $is_number_registered));
    }

    public function Configuration(Request $request)
    {
        // $a = 'cGI\/gZWJqqY9dx1T1XPYjw==';
        // $secret = 'p9Nf8xLqzB1wKv3rjY5Tg4D2H7VbXs6C';
        // $iv = "1a2b3c4d5e6f7890";
        // dd(decryptText($a,$secret,$iv));
        $user_id = isset($request->user_id) ? $request->user_id : '';
        $string_file = $this->getStringFile($request->merchant_id);
        if (!empty($user_id)) {
            $customMessages = [
//                'player_id.required' => trans("$string_file.invalid_player_id"),
//                'player_id.min' => trans("$string_file.invalid_player_id"),
            ];
            $validator = Validator::make($request->all(), [
                'unique_no' => 'required',
                'package_name' => 'required',
//                'player_id' => 'required|string|min:32',
                'apk_version' => 'required',
                'device' => 'required|integer|between:1,2',
                'operating_system' => 'required',
            ], $customMessages);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }
        }
        try {
            $merchant_id = $request->merchant_id;
            $merchant = Merchant::with("Configuration", "Application","ApplicationTheme", "ApplicationConfiguration", "BookingConfiguration", "Language")->find($merchant_id);

            $config = $merchant->Configuration;

            // add user player id in user_devices id
            $user_id = isset($request->user_id) ? $request->user_id : '';
            $user_card = true;
            if (!empty($user_id)) {
                $user = User::find($user_id);
                if(empty($user)){
                    return response()->json(['version'=>'NA','result' => "999", 'message' => "Unauthenticated", 'data' => []], 200);
                }
                if (isset($config->user_signup_card_store_enable) && $config->user_signup_card_store_enable == 1) {
                    $cardList = UserCard::where([['user_id', '=', $user_id]])->get();
                    if (count($cardList) > 0) {
                        $user_card = false;
                    }
                }
            }
            $merchant->user_card = $user_card;
            $corporateEnable = $config->corporate_admin == 1 ? true : false; // $merchant->Configuration->corporate_admin == 1 ? true : false;
            $merchant->corporate_enable = $corporateEnable;
            $device_data = array('user_id' => $user_id, 'unique_number' => $request->unique_no, 'package_name' => $request->package_name, 'apk_version' => $request->apk_version, 'language_code' => $request->language_code, 'manufacture' => $request->manufacture, 'model' => $request->model, 'device' => $request->device, 'operating_system' => $request->operating_system, 'player_id' => $request->player_id);
            $merchant->logged_user_id = $user_id;
            save_user_device_player_id($device_data);
            $return_data = new UserConfiguration($merchant);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        $message = trans("$string_file.success");
        return $this->successResponse($message, $return_data);
    }

    public function getCountryDocuments(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile(null,$user->Merchant);
        if (empty($user->country_id)) {
            return response()->json(['result' => "0", 'message' => trans("$string_file.no_country"), 'data' => []]);
        }
//        if ($request->document_for == 'CARPOOLING') {
//
//            $user_data = Country::with(['documents' => function ($q) use ($user) {
//                $q->where([['country_id', '=', $user->country_id], ['check_user', '=', 1]]);
//            }])->find($user->country_id);
//            $data = [];
//            foreach ($user_data->documents as $value) {
//                $data[] = $value;
//
//            }
//            $offer_user_doc = Country::with(['documents' => function ($q) use ($user) {
//                $q->where([['country_id', '=', $user->country_id], ['check_offer_user', '=', 1]]);
//            }])->find($user->country_id);
//            $data1 = [];
//            foreach ($offer_user_doc as $v) {
//                $data1[] = $v;
//            }
//            $documentList = array(
//                'user_document' => $data,
//                'offer_user_document' => $data1,
//            );
//            return $this->successResponse(trans('common.success'), $documentList);
//        }
        $merchant = Merchant::with("Configuration",  "ApplicationConfiguration", "BookingConfiguration")->find($request->merchant_id);
        $config = $merchant->ApplicationConfiguration;
        $country = Country::find($user->country_id);

        $documentList = $country->documents()
            ->where('documentStatus', 1)
            ->wherePivot('document_type', isset($config->local_citizen_foreigner_documents) && $config->local_citizen_foreigner_documents == 1 ? $request->id : 1)
            ->get();
        if (empty($documentList->toArray())) {
            return response()->json(['result' => "0", 'message' => "No Document", 'data' => []]);
        }
        foreach ($documentList as $key => $doc) {
            $userDoc = UserDocument::where([['document_id', '=', $doc->id], ['user_id', '=', $user->id]])->first();
            if (!empty($userDoc)) {
                $verfication_status = (string)$userDoc['document_verification_status'];
            } else {
                $verfication_status = "0";
            }
            // $doc->documentname = $doc->LanguageSingle->documentname;
            $doc->documentname = $doc->LanguageAny->documentname;
            $doc->document_verification_status = $verfication_status;
        }
        if ($user->total_document > $user->approved_document) {
            $user->signup_status = 1;
        }
        $user->total_document = count($documentList);
        $user->save();
        return response()->json(['result' => "1", 'message' => trans("$string_file.documents"), 'data' =>
            $documentList]);
    }

    public function addDocument(Request $request)
    {
        DB::beginTransaction();
        try{
//            $auto_verify = true;
            $user = $request->user('api');
            $auto_verify = $user->Country->document_auto_verify == 2 ? false : true;
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $merchant_id = $user->merchant_id;
            $user_id = $user->id;
            $validator = Validator::make($request->all(), [
                'document_id' => ['required',
                    Rule::unique('user_documents', 'document_id')->where(function ($query) use ($user_id) {
                        return $query->where([['user_id', '=', $user_id], ['document_verification_status', '!=', 3]]);
                    })],
                'document_image' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                throw new \Exception($errors[0]);
//                return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
            }
            $document = Document::find($request->document_id);
            if (isset($document->document_number_required) && $document->document_number_required == 1) {
                $validator = Validator::make($request->all(), [
                    'document_number' => [
                        'required',
                        Rule::unique('user_documents', 'document_number')->where(function ($query) {
                            $query->where([['document_number', '!=', ''], ['status', '=', 1]]);
                        })
                    ],
                ]);
                if ($validator->fails()) {
                    $errors = $validator->messages()->all();
                    throw new \Exception($errors[0]);
//                    return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
                }
            }
            $doc = UserDocument::where([['user_id', '=', $user_id], ['document_id', '=', $request->document_id]])->first();
            if (empty($doc)) {
                $request->file('document_image');
                if ($request->api_type == 1) {
                    $image = $this->uploadBase64Image('document_image', 'user_document', $merchant_id);;
                } else {
                    $image = $this->uploadImage('document_image', 'user_document', $merchant_id);
                }
                $doc = UserDocument::create([
                    'user_id' => $user_id,
                    'document_id' => $request->document_id,
                    'document_file' => $image,
                    'document_verification_status' => ($auto_verify) ? 2 : 1,
                    'expire_date' => $request->expire_date,
                    'document_number' => $request->document_number,
                ]);

                $approved_document = $user->approved_document;
                $user->signup_status = 2;
                if($auto_verify){
                    $approved_document += 1;
                }
                if ($user->total_document == $approved_document){
                    $user->signup_status = 3;
                    // if ($auto_verify) {

                    // }
                }
                $user->approved_document = $approved_document;
                $user->save();
                DB::commit();
                return $this->successResponse(trans("$string_file.success"), $doc);
//                return response()->json(['result' => "1", 'message' => trans("$string_file.success"), 'data' => $doc]);
            }
            $image = $this->uploadImage('document_image', 'user_document', $merchant_id);
            $doc->document_file = $image;
            $doc->document_verification_status = ($auto_verify) ? 2 : 1;
            $doc->expire_date = $request->expire_date;
            $doc->document_number = $request->document_number;
            $doc->save();
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            return $this->failedResponse($exception->getMessage());
        }
        return $this->successResponse(trans("$string_file.success"), $doc);
//        return response()->json(['result' => "1", 'message' => trans("$string_file.success"), 'data' => $doc]);
    }

    public function UserTermUpdate(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile(null,$user->Merchant);
        $user->term_status = 0;
        $user->save();
        return response()->json(['result' => "1", 'message' => trans("$string_file.success"), 'data' => []]);
    }

//    public function CopySignUp(Request $request)
//    {
//
//        // foreach ($user as $users) {
//        //     $first_name = $users['user_name'];
//        //     $user_phone = $users['user_phone'];
//        //     $user_email = $users['user_email'];
//        //     $referral_code = $users['referral_code'];
//        //     $request->password = '12345678';
//        //     $password = '$2y$10$tdy.ATOKtv40bDhKsUAFa.7E1g2dGB9UGmxW2JTzQAVwk7uarXS7W';
//        //     $gender = $request->user_gender == 0 ? NULL : $request->user_gender;
//        //     //$password = Hash::make($request->password);
//        //     $user = new User();
//        //     $User = User::create([
//        //         'merchant_id' => 70,
//        //         'country_id' => 116,
//        //         'first_name' => $first_name,
//        //         'last_name' => '',
//        //         'UserPhone' => $user_phone,
//        //         'email' => $user_email,
//        //         'user_gender' => '',
//        //         'password' => $password,
//        //         'UserSignupType' => 1,
//        //         'UserSignupFrom' => 1,
//        //         'ReferralCode' => $referral_code,
//        //         'user_type' => 2,
//        //         'smoker_type' => '',
//        //         'allow_other_smoker' => ''
//        //     ]);
//
//        // }
//        return 'Ho gyi Hawabaji';
//    }

    // tutu changes
//    public function rewardPoints(Request $request)
//    {
//        $user = $request->user('api');
//        $app_config = ApplicationConfiguration::where('merchant_id', $user->merchant_id)->first();
//        if ($app_config->reward_points != 1) {
//            return response()->json([
//                'result' => 0,
//                'message' => __('api.unauthorized')
//            ]);
//        }
//
//
//        $reward_points_data = \App\Models\RewardPoint:: where('merchant_id', $user->merchant_id)
//            ->where('country_area_id', $user->country_area_id)
//            ->where('active', 1)
//            ->first();
//
//
//        if (!$reward_points_data) {
//            return response()->json([
//                'result' => 0,
//                'message' => __('api.reward.notfound'),
//                'data' => []
//            ]);
//        }
//
//
//        return response()->json([
//            'result' => 1,
//            'message' => __('api.reward.data'),
//            'data' => [
//                'usable_reward_points' => $user->usable_reward_points,
//                'reward_points' => $user->reward_points
//            ]
//        ]);
//
//    }

    //Check User/Driver
    public function CheckUser(Request $request)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile($user->merchant_id);
        $validator = Validator::make($request->all(), [
            'search_by' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
//            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        } else {
            if($request->type == "DRIVER")
            {
                $search = $request->search_by;
                $user = Driver::where('merchant_id', '=', $request->merchant_id)
                    ->where('driver_delete', '=', NULL)
                    ->where(function ($query) use ($search) {
                        $query->where('email', '=', $search)
                            ->orWhere('phoneNumber', '=', $search);
                    })->first();
            }
            else
            {
                $search = $request->search_by;
                $user = User::where('merchant_id', '=', $request->merchant_id)
                    ->where('user_delete', '=', NULL)
                    ->where(function ($query) use ($search) {
                        $query->where('email', '=', $search)
                            ->orWhere('UserPhone', '=', $search);
                    })->first();
            }

            if ($user) {
                return $this->successResponse(trans("$string_file.success"),$user);
//                return response()->json(['result' => "1", 'data' => $user]);
            } else {
                return $this->failedResponse(trans("$string_file.user")." ".trans("$string_file.data_not_found"));
//                return response()->json(['result' => "0", 'message' => trans('api-x.userNotFound'), 'data' => []]);
            }
        }
    }

    //Transfer wallet money
    public function TransferWalletMoney(Request $request)
    {
        $sender = $request->user('api');
        $string_file = $this->getStringFile(NULL,$sender->Merchant);
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer',
            'amount' => 'required|integer',

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        } else {
            if ($request->amount <= $sender->wallet_balance) {
                if($request->type == "DRIVER")
                {

                    $receiver = Driver::find($request->receiver_id);
                    if($sender->country_id != $receiver->country_id)
                    {
                        return $this->failedResponse(trans("$string_file.sender_receiver_currency_must_same"));
                    }
                    //Credit amount into receiver's wallet // that is driver
                    $paramArray = array(
                        'merchant_id' => $sender->merchant_id,
                        'driver_id' => $receiver->id,
                        'booking_id' => NULL,
                        'amount' => $request->amount,
                        'narration' => 23,
                        'platform' => 1,
                        'payment_method' => 2,
                        'sender' => $sender->first_name . ' ' . $sender->last_name,
                    );
                    WalletTransaction::WalletCredit($paramArray);


                }
                else
                {
                    $receiver = User::find($request->receiver_id);
                    if($sender->country_id != $receiver->country_id)
                    {
                        return $this->failedResponse(trans("$string_file.sender_receiver_currency_must_same"));
                    }
                    //Credit amount into receiver's wallet
                    $paramArray = array(
                        'merchant_id' => $sender->merchant_id,
                        'user_id' => $receiver->id,
                        'booking_id' => NULL,
                        'amount' => $request->amount,
                        'narration' => 12,
                        'platform' => 2,
                        'payment_method' => 2,
                        'transaction_id' => NULL,
                        'sender' => $sender->first_name . ' ' . $sender->last_name,
                        'wallet_transfer_id'=>$sender->id // save the sender id
                    );
                    WalletTransaction::UserWalletCredit($paramArray);

                }
                //Debit amount from sender's wallet
                $paramArray = array(
                    'user_id' => $sender->id,
                    'booking_id' => NULL,
                    'amount' => $request->amount,
                    'narration' => 13,
                    'platform' => 2,
                    'payment_method' => 2,
                    'transaction_id' => NULL,
                    'transaction_type' => 3,
                    'receiver' => $receiver->first_name . ' ' . $receiver->last_name,
                    'wallet_transfer_id'=>$receiver->id // save receiver id
                );
                WalletTransaction::UserWalletDebit($paramArray);

                return $this->successResponse(trans("$string_file.amount_transferred"));
            } else {
                 return $this->failedResponse(trans("$string_file.wallet_insufficient_amount"));
            }
        }
    }

    public function SignupValidation(Request $request){
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        
        //Encrypt and Decrypt
        $merchant = Merchant::Find($merchant_id);
        if($merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($request->email){
                    $email = decryptText($request->email,$secret,$iv);
                    $request->merge(['email'=> $email]);
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        if (!empty($request->email)){
            $validator = Validator::make($request->all(), ['email' => ['email',
                Rule::unique('users', 'email')->where(function ($query) use ($merchant_id) {
                    return $query->where([['user_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                })]
            ], [
                'email.unique' => trans("$string_file.email_already_used"),
            ]);

            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }
        }

        try{
            $ref = new ReferralController();
            $ref->checkForReferral($request->referral_code,$merchant_id,$request->country_id,$request->area_id,'USER');
        }catch (\Exception $e){
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.validate"),[]);
    }

    public function AccountDelete(Request $request){
        $user = $request->user('api');
        $id = $user->id;
        $merchant = $user->Merchant;
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = $merchant->id;
        $bookings = Booking::whereIn('booking_status', [1001, 1012, 1002, 1003, 1004])->where('user_id', $request->id)->first();
        $orders = \App\Models\BusinessSegment\Order::whereIn('order_status', [1, 4, 6, 7, 9, 10])->where('user_id', $request->id)->first();
        $laundry_order = \App\Models\LaundryOutlet\LaundryOutletOrder::whereIn('order_status', [1, 6, 10, 7, 9, 13, 15, 16, 17])->where('user_id', $request->id)->first();
        $handyman_order = \App\Models\HandymanOrder::whereIn('order_status', [1, 4, 6, 10])->where('user_id', $request->id)->first();
        $invalid_wallet_money  = $user->wallet_balance < 0;
        $outstanding_count = Outstanding::where("user_id", $user->id)->where("pay_status", 0)->count();
        if (empty($bookings) && !$invalid_wallet_money && $outstanding_count == 0) {
            $bookings = Booking::where([['user_id', '=', $id]])->first();
//            $user = User::where([['merchant_id', '=', $merchant_id]])->FindorFail($id);
//            $playerids = $user->UserDevice()->get()->pluck('player_id')->toArray();
            // if (empty($bookings)):

            //old commented
//            if (empty($bookings) && empty($orders) && empty($laundry_order) && empty($handyman_order)) :
//                if (!empty($user->UserDevice())) {
//                    $user->UserDevice()->delete();
//                }
//                $user->delete();
//            else:
//                $user->user_delete = 1;
//                $user->save();
//            endif;
//
//            UserDocument::where([['user_id', '=', $user->id]])->update(['status' => 2]);
//            UserVehicleDocument::whereHas('UserVehicle', function ($q) use ($id) {
//                $q->where('user_id', $id);
//            })->update(['status' => 2]);
//
//            UserVehicle::where([['owner_id', '=', $user->id], ['driver_id', '=', $user->id]])->update(['vehicle_delete' => 1]);

            //new soft delete (and can be restored from admin panel)
            if (empty($bookings) && empty($orders) && empty($laundry_order) && empty($handyman_order)) :
                $user->user_delete = 1;
                $user->save();
            endif;

            if($request->cancel_reason_id){
                $user->cancel_reason_id = $request->cancel_reason_id;
            }
            $user->account_cancel_reason = $request->account_cancel_reason;
            $user->save();

            setLocal($user->language);
            $message = trans("$string_file.account_has_been_deleted");
            $title = trans("$string_file.account_deleted");
            $data['notification_type'] = "ACCOUNT_DELETED";
            $data['segment_type'] = "";
            $data['segment_data'] = ['user_id' => $id];
            $arr_param = ['user_id' => $id, 'data' => $data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => ''];
            Onesignal::UserPushMessage($arr_param);
            setLocal();

            return $this->successResponse(trans("$string_file.account_has_been_deleted"));
        }
        elseif($invalid_wallet_money || $outstanding_count >= 0){
            return $this->failedResponse(trans("$string_file.account_delete_fail_due_to_amount"));
        }
        else{
            return $this->failedResponse(trans("$string_file.some_thing_went_wrong"));
        }
    }

    public function RedeemRewardPoints(Request $request){
        $user = $request->user('api');
        $string_file = $this->getStringFile($user->merchant_id);
        $validator = Validator::make($request->all(), [
            'reward_points' => 'required|integer',
            // 'amount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        if($user->reward_points >= $request->reward_points){
            DB::beginTransaction();
            try{
                $user->reward_points -=  $request->reward_points;
                $user->save();

                $paramArray = array(
                    'merchant_id' => $user->merchant_id,
                    'user_id' => $user->id,
                    'booking_id' => NULL,
                    'amount' => $request->reward_points,
                    'narration' => 18,
                    'platform' => 2,
                    'payment_method' => 2,
                    'transaction_id' => NULL,
//                'sender' => $sender->first_name . ' ' . $sender->last_name,
//                'wallet_transfer_id'=>$sender->id // save the sender id
                );
                WalletTransaction::UserWalletCredit($paramArray);
            }catch (\Exception $e) {
                DB::rollBack();
                $message = $e->getMessage();
                return $this->failedResponse($message);
            }
            DB::commit();
            return $this->successResponse(trans("$string_file.redeem_reward_point"));
        }else{
            return $this->failedResponse(trans("$string_file.no_reward_point"));
        }
    }


    public function BankDetailsUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_name' => 'required|string',
            'account_holder_name' => 'required|string',
            'account_number' => 'required|string',
            'account_type' => 'required|string',
            'bank_institution_number'=>'string',
            'routing_number'=>'string',
            'iban_number'=>'string',
            'swift_bic_code'=>'string',
            'bank_address' =>'required|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $string_file = $this->getStringFile($user->merchant_id);
        DB::beginTransaction();
        try {
            $user->bank_name = $request->bank_name;
            $user->account_type_id = $request->account_type;
            $user->online_code = $request->online_code;
            $user->account_holder_name = $request->account_holder_name;
            $user->account_number = $request->account_number;
            $user->bank_institution_number=$request->bank_institution_number;
            $user->routing_number=$request->routing_number;
            $user->iban_number=$request->iban_number;
            $user->swift_bic_code=$request->swift_bic_code;
            $user->bank_address=$request->bank_address;
            $user->save();
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
//        $user_data = new DriverLoginResource($user);
        DB::commit();
        $message = trans("$string_file.bank") . ' ' . trans("$string_file.details") . ' ' . trans("$string_file.updated_successfully");
        return $this->successResponse($message);
//        return response()->json(['result' => "1", 'message' => trans('api.message103'), 'data' => $driver]);
    }

     // this is old fun, but app delover is using old api so using this one
    public function FavouriteDrivers(Request $request)
    {
        $user_id = $request->user('api')->id;
        $segment_id = $request->segment_id;
        $drivers = FavouriteDriver::with('Driver')->where([['user_id', '=', $user_id]])->where(function($p) use ($segment_id){
            if(!empty($segment_id)){
                $p->where('segment_id',$segment_id);
            }
        })->get();
        if (empty($drivers->toArray())) {
            return response()->json(['result' => "0", 'message' => trans('api.message118'), 'data' => []]);
        }
        foreach ($drivers as $driver) {
            $driver->driver_name = $driver->Driver->fullName;
            $driver->driver_image = get_image($driver->Driver->profile_image,'driver', $driver->Driver->merchant_id);
            $driver->driver_email = $driver->Driver->email;
            $driver->driver_rating = $driver->Driver->rating;
            $driver->driver_phone = $driver->Driver->phoneNumber;
        }
        return response()->json(['result' => "1", 'message' => trans('api.message27'), 'data' => $drivers]);
    }

    public function getCountryAreasegments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $merchant_id = $user->merchant_id;
        $string_file = $this->getStringFile($user->Merchant);
        $this->getAreaByLatLong($request, $string_file);
        $arr_services = $this->getMerchantSegmentServices($merchant_id, 'api', 1, [], $request->area);
        $arr_services = collect($arr_services);
        return $this->successResponse("$string_file.success", $arr_services);
    }
    
    public function userAdditionalDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'details_for' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $data  = [];
        DB::beginTransaction();
        try{
            switch($request->details_for){
                case "PASSWORD_AUTHENTICATION":
                    $pass = $request->password;
                    if(Hash::check($pass, $user->password)){
                        return $this->successResponse(trans("$string_file.success"));
                    }
                    return $this->failedResponse(trans("$string_file.invalid_password"));
                    break;
            }
        }
        catch(\Exception $e){
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"));
    }


    public function FastLaneConfiguration(Request $request)
    {
        $merchant   = Merchant::with('ApplicationConfiguration')->find($request->merchant_id);
        $raw_config  = $merchant->ApplicationConfiguration->fastlane_config_user ?? null;

        if (empty($raw_config)) return (object)[];
        $data = is_array($raw_config) ? $raw_config : (json_decode($raw_config, true) ?: (object)[]);

        $files = [
            'json_path',
            'logo_path',
            'logo_round_path',
            'notification_path',
            'google_service_plist',
            'jks_file',
            'icon_folder_name',
            'splash_screen',
        ];

        foreach ($files as $key) {
            $downloadable = true;
            if($key == "splash_screen") $downloadable = false;
            if (!empty($data[$key]))  $data[$key] = get_image( $data[$key], 'fastlane', $merchant->id, true,  true,  '', '', $downloadable );
        }

        return $data;
    }
}
