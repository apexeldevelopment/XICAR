<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\User;
use App\Models\Driver;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Category;
use App\Models\Merchant;
use App\Traits\SosTrait;
use App\Traits\AreaTrait;
use App\Traits\UserTrait;
use App\Models\WeightUnit;
use App\Traits\OrderTrait;
use App\Traits\PriceTrait;
use App\Traits\PromoTrait;
use App\Models\Transaction;
use App\Models\UserCashout;
use App\Models\VehicleMake;
use App\Models\VehicleType;
use App\Traits\DriverTrait;
use App\Traits\RatingTrait;
use App\Models\VehicleModel;
use App\Traits\BookingTrait;
use Illuminate\Http\Request;
use App\Exports\CustomExport;
use App\Models\Configuration;
use App\Models\DriverAccount;
use App\Models\DriverCashout;
use App\Models\HandymanOrder;
use App\Traits\HandymanTrait;
use App\Traits\MerchantTrait;
use App\Models\CarpoolingRide;
use App\Models\CustomerSupport;
use App\Models\DriverOnlineTime;
use App\Models\PricingParameter;
use App\Models\ReferralDiscount;
use Illuminate\Support\Facades\DB;
use App\Models\ReferralUserDiscount;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductVariantExport;
use App\Models\BusinessSegment\Order;
use App\Models\PromotionNotification;
use App\Models\UserWalletTransaction;
use App\Models\ReferralDriverDiscount;
use App\Models\BusinessSegment\Product;
use App\Models\DriverWalletTransaction;
use App\Models\ReferralCompanyDiscount;
use App\Models\ApplicationConfiguration;
use App\Models\BusinessSegment\ProductVariant;
use App\Models\BusinessSegment\ProductInventory;
use App\Http\Controllers\Helper\ReferralController;
use App\Http\Controllers\Merchant\TransactionController;

class ExcelController extends Controller
{
    use DriverTrait, BookingTrait, SosTrait, RatingTrait, PromoTrait, AreaTrait, PriceTrait, OrderTrait, HandymanTrait, UserTrait;

    public function UserExport(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        switch ($request->parameter) {
            case "1":
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "UserPhone";
                break;
        }
        // $users = User::where([['merchant_id', '=', $merchant_id]])->get();
        $query = User::where([['merchant_id', '=', $merchant_id]]);
        if ($request->keyword) {
            $query->where($parameter, 'like', '%' . $request->keyword . '%');
        }
        if ($request->country_id) {
            $query->where('country_id', '=', $request->country_id);
        }
        $users = $query->get();



        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($users) use($string_file){
        //            if ($users->user_type == 1) {
        //                $users->user_type = trans('admin.Corporate');
        //            } else {
        //                $users->user_type = trans('admin.Retail');
        //            }
        //
        //            if ($users->UserSignupType == 1) {
        //                $users->UserSignupType = trans('admin.normal');
        //            } elseif ($users->UserSignupType == 2) {
        //                $users->UserSignupType = trans('admin.Google');
        //            } elseif ($users->UserSignupType == 3) {
        //                $users->UserSignupType = trans('admin.Facebook');
        //            }
        //
        //            switch ($users->UserSignupFrom) {
        //                case 1:
        //                    $users->UserSignupFrom = trans("$string_file.application");
        //                    break;
        //                case 2:
        //                    $users->UserSignupFrom = trans("$string_file.admin");
        //                    break;
        //                case 3:
        //                    $users->UserSignupFrom = trans("$string_file.web");
        //                    break;
        //            }
        //
        //            if ($users->UserStatus == 1) {
        //                $users->UserStatus = trans("$string_file.active");
        //            } else {
        //                $users->UserStatus = trans("$string_file.inactive");
        //            }
        //        });
        //        $csvExporter->build($users,
        //            [
        //                'user_merchant_id' => trans("$string_file.user_id"),
        //                'email' => trans("$string_file.email"),
        //                'UserName' => trans("$string_file.name"),
        //                'UserPhone' => trans("$string_file.phone"),
        //                'wallet_balance' => trans("$string_file.wallet_money"),
        //                'ReferralCode' => trans("$string_file.referral_code"),
        //                'rating' => trans("$string_file.rating"),
        //                'created_at' => trans("$string_file.registered_date"),
        //                'user_type' => trans("$string_file.signup_details"),
        //                'UserSignupType' => trans("$string_file.signup_type"),
        //                'UserSignupFrom' => trans("$string_file.signup_from"),
        //                'UserStatus' => trans("$string_file.status")
        //            ]
        //        )->download('riders' . time() . '.csv');

        $export = [];
        foreach ($users as $user) {
            $user->user_type = ($user->user_type == 1) ? trans('admin.Corporate') : trans('admin.Retail');

            if ($user->UserSignupType == 1) {
                $user->UserSignupType = trans('admin.normal');
            } elseif ($user->UserSignupType == 2) {
                $user->UserSignupType = trans('admin.Google');
            } elseif ($user->UserSignupType == 3) {
                $user->UserSignupType = trans('admin.Facebook');
            }

            switch ($user->UserSignupFrom) {
                case 1:
                    $user->UserSignupFrom = trans("$string_file.application");
                    break;
                case 2:
                    $user->UserSignupFrom = trans("$string_file.admin");
                    break;
                case 3:
                    $user->UserSignupFrom = trans("$string_file.web");
                    break;
            }
            $user->UserStatus = ($user->UserStatus == 1) ? trans("$string_file.active") : trans("$string_file.inactive");

            array_push($export, array(
                $user->user_merchant_id,
                $user->email,
                $user->UserName,
                $user->UserPhone,
                $user->wallet_balance,
                $user->ReferralCode,
                $user->rating,
                $user->created_at,
                $user->user_type,
                $user->UserSignupType,
                $user->UserSignupFrom,
                $user->UserStatus
            ));
        }
        $heading = array(
            trans("$string_file.user_id"),
            trans("$string_file.email"),
            trans("$string_file.name"),
            trans("$string_file.phone"),
            trans("$string_file.wallet_money"),
            trans("$string_file.referral_code"),
            trans("$string_file.rating"),
            trans("$string_file.registered_date"),
            trans("$string_file.signup_details"),
            trans("$string_file.signup_type"),
            trans("$string_file.signup_from"),
            trans("$string_file.status")
        );
        $file_name = 'users-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function CorporateUserExport(Request $request)
    {
        $corporate = get_corporate();
        $merchant = $corporate->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        switch ($request->parameter) {
            case "1":
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "UserPhone";
                break;
        }

        $query = User::where([['merchant_id', '=', $merchant_id],['corporate_id','=',$corporate->id]]);
        if ($request->keyword) {
            $query->where($parameter, 'like', '%' . $request->keyword . '%');
        }
        if ($request->country_id) {
            $query->where('country_id', '=', $request->country_id);
        }
        $users = $query->get();

        $export = [];
        foreach($users as $user){
            $user->user_type = ($user->user_type == 1) ? trans('admin.Corporate') : trans('admin.Retail');

            if ($user->UserSignupType == 1) {
                $user->UserSignupType = trans('admin.normal');
            } elseif ($user->UserSignupType == 2) {
                $user->UserSignupType = trans('admin.Google');
            } elseif ($user->UserSignupType == 3) {
                $user->UserSignupType = trans('admin.Facebook');
            }

            switch ($user->UserSignupFrom) {
                case 1:
                    $user->UserSignupFrom = trans("$string_file.application");
                    break;
                case 2:
                    $user->UserSignupFrom = trans("$string_file.admin");
                    break;
                case 3:
                    $user->UserSignupFrom = trans("$string_file.web");
                    break;
            }
            $user->UserStatus = ($user->UserStatus == 1) ? trans("$string_file.active") : trans("$string_file.inactive");

            array_push($export, array(
                $user->user_merchant_id,
                $user->email,
                $user->UserName,
                $user->UserPhone,
                $user->wallet_balance,
                $user->ReferralCode,
                $user->rating,
                $user->created_at,
                $user->user_type,
                $user->UserSignupType,
                $user->UserSignupFrom,
                $user->UserStatus
            ));
        }
        $heading = array(
            trans("$string_file.user_id"),
            trans("$string_file.email"),
            trans("$string_file.name"),
            trans("$string_file.phone"),
            trans("$string_file.wallet_money"),
            trans("$string_file.referral_code"),
            trans("$string_file.rating"),
            trans("$string_file.registered_date"),
            trans("$string_file.signup_details"),
            trans("$string_file.signup_type"),
            trans("$string_file.signup_from"),
            trans("$string_file.status")
        );
        $file_name = 'corporate_users-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function CorporateAllRideExport(Request $request){
        $corporate = get_corporate();
        $merchant = $corporate->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $bookings = $this->bookings(true, [1001, 1012, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1016],'CORPORATE');
        $export = [];
        foreach($bookings as $booking){
            if($booking->booking_type == 1){
                $bookingType = trans("$string_file.ride_now");
            }else{
                $bookingType = trans("$string_file.ride_later");
            }
            
            $userDetail = $booking->User->UserName . " (" . $booking->User->UserPhone . ") (" . $booking->User->email . ")";
            
            if($booking->Driver){
                $driverDetail = $booking->Driver->first_name.' '.$booking->Driver->last_name . " (" . $booking->Driver->phoneNumber . ") (" . $booking->Driver->email . ")";
            }else{
                $driverDetail = trans("$string_file.not_assigned_yet");
            }
            
            if($booking->platform == 1){
                $booking->platform = trans("$string_file.application");
            }elseif($booking->platform == 2){
                $booking->platform = trans("$string_file.admin");
            }elseif($booking->platform == 3){
                $booking->platform = trans("$string_file.web");
            }
            
            $service_text = ($booking->ServiceType) ? $booking->ServiceType->serviceName : $booking->deliveryType->name;
            $serviceDetail = $booking->platform. "(" . $service_text . ") (" . $booking->VehicleType->VehicleTypeName . ")";
            
            $booking_status = $this->getBookingStatus($string_file);
            $bookingStatus = isset($booking_status[$booking->booking_status]) ? $booking_status[$booking->booking_status] : "";
            
            $createdAt = $booking->created_at->toDateString() . "(". $booking->created_at->toTimeString() .")";
            
            array_push($export,array(
                $booking->merchant_booking_id,
                $bookingType,
                $userDetail,
                $driverDetail,
                $serviceDetail,
                $booking->CountryArea->CountryAreaName,
                $booking->pickup_location . "(". $booking->drop_location .")",
                $bookingStatus,
                $booking->PaymentMethod->payment_method,
                $createdAt
            ));
        }
        
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.ride_type"),
            trans("$string_file.user_details"),
            trans("$string_file.driver_details"),
            trans("$string_file.service_detail"),
            trans("$string_file.service_area"),
            trans("$string_file.pickup_drop"),
            trans("$string_file.current_status"),
            trans("$string_file.payment"),
            trans("$string_file.created_at")
        );
        
        $file_name = 'corporate_all_rides-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function CorporateActiveRideExport(Request $request){
        $corporate = get_corporate();
        $merchant = $corporate->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $bookings = $this->bookings(true, [1001, 1012, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1016],'CORPORATE');

        $export = [];
        foreach($bookings as $booking){
            if($booking->booking_type == 1){
                $bookingType = trans("$string_file.ride_now");
            }else{
                $bookingType = trans("$string_file.ride_later");
            }
            $userDetail = $booking->User->UserName . " (" . $booking->User->UserPhone . ") (" . $booking->User->email . ")";
            if($booking->Driver){
                $driverDetail = $booking->Driver->first_name.' '.$booking->Driver->last_name . " (" . $booking->Driver->phoneNumber . ") (" . $booking->Driver->email . ")";
            }else{
                $driverDetail = trans("$string_file.not_assigned_yet");
            }

            if($booking->platform == 1){
                $booking->platform = trans("$string_file.application");
            }elseif($booking->platform == 2){
                $booking->platform = trans("$string_file.admin");
            }elseif($booking->platform == 3){
                $booking->platform = trans("$string_file.web");
            }

            $service_text = ($booking->ServiceType) ? $booking->ServiceType->serviceName : $booking->deliveryType->name;
            $rideDetail = $booking->platform. "(" . $service_text . ") (" . $booking->VehicleType->VehicleTypeName . ")";

            $createdAt = $booking->created_at->toDateString() . "(". $booking->created_at->toTimeString() .")";

            $estimated = $booking->CountryArea->Country->isoCode . "(" . $booking->estimate_bill .")";

            array_push($export,array(
                $booking->merchant_booking_id,
                $bookingType,
                $userDetail,
                $driverDetail,
                $booking->platform,
                $rideDetail,
                $booking->pickup_location . "(". $booking->drop_location .")",
                $booking->PaymentMethod->payment_method,
                $estimated,
                $createdAt
            ));
        }

        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.ride_type"),
            trans("$string_file.user_details"),
            trans("$string_file.driver_details"),
            trans("$string_file.request_from"),
            trans("$string_file.ride_details"),
            trans("$string_file.pickup_drop"),
            trans("$string_file.payment"),
            trans("$string_file.estimated"),
            trans("$string_file.created_at")
        );
        
        $file_name = 'corporate_active_rides-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);


    }

    public function CorporateCompletedRideExport(){
        $corporate = get_corporate();
        $merchant = $corporate->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $bookings = $this->bookings(true, [1005],'CORPORATE');

        $export = [];
        foreach($bookings as $booking){
            if($booking->booking_type == 1){
                $bookingType = trans("$string_file.ride_now");
            }else{
                $bookingType = trans("$string_file.ride_later");
            }
            $userDetail = $booking->User->UserName . " (" . $booking->User->UserPhone . ") (" . $booking->User->email . ")";
            if($booking->Driver){
                $driverDetail = $booking->Driver->first_name.' '.$booking->Driver->last_name . " (" . $booking->Driver->phoneNumber . ") (" . $booking->Driver->email . ")";
            }else{
                $driverDetail = trans("$string_file.not_assigned_yet");
            }

            if($booking->platform == 1){
                $booking->platform = trans("$string_file.application");
            }elseif($booking->platform == 2){
                $booking->platform = trans("$string_file.admin");
            }elseif($booking->platform == 3){
                $booking->platform = trans("$string_file.web");
            }

            $service_text = ($booking->ServiceType) ? $booking->ServiceType->serviceName : $booking->deliveryType->name;
            $rideDetail = $booking->platform. "(" . $service_text . ") (" . $booking->VehicleType->VehicleTypeName . ")";

            $createdAt = $booking->created_at->toDateString() . "(". $booking->created_at->toTimeString() .")";

            $estimated = $booking->CountryArea->Country->isoCode . "(" . $booking->final_amount_paid .")";

            array_push($export,array(
                $booking->merchant_booking_id,
                $bookingType,
                $userDetail,
                $driverDetail,
                $booking->platform,
                $rideDetail,
                $booking->pickup_location . "(". $booking->drop_location .")",
                $booking->PaymentMethod->payment_method,
                $estimated,
                $createdAt
            ));
        }

        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.ride_type"),
            trans("$string_file.user_details"),
            trans("$string_file.driver_details"),
            trans("$string_file.request_from"),
            trans("$string_file.ride_details"),
            trans("$string_file.pickup_drop"),
            trans("$string_file.payment"),
            trans("$string_file.bill_amount"),
            trans("$string_file.created_at")
        );
        
        $file_name = 'corporate_completed_rides-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function CorporateCancelRideExport(){
        $corporate = get_corporate();
        $merchant = $corporate->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $bookings = $this->bookings(true, [1006, 1007, 1008],'CORPORATE');

        $export = [];
        foreach($bookings as $booking){
            if($booking->booking_type == 1){
                $bookingType = trans("$string_file.ride_now");
            }else{
                $bookingType = trans("$string_file.ride_later");
            }
            $userDetail = $booking->User->UserName . " (" . $booking->User->UserPhone . ") (" . $booking->User->email . ")";

            $service_text = ($booking->ServiceType) ? $booking->ServiceType->serviceName : $booking->deliveryType->name;
            $rideDetail = $booking->platform. "(" . $service_text . ") (" . $booking->VehicleType->VehicleTypeName . ")";

            $createdAt = $booking->created_at->toDateString() . "(". $booking->created_at->toTimeString() .")";

            switch($booking->booking_status){
                case "1006":
                    $status = trans('admin.message48');
                    break;
                case "1007":
                    $status = trans('admin.message49');
                    break;
                case "1008":
                    $status = trans('admin.message50');
                    break;
            }
                

            array_push($export,array(
                $booking->merchant_booking_id,
                $bookingType,
                $userDetail,
                $rideDetail,
                $booking->pickup_location . "(". $booking->drop_location .")",
                $booking->CancelReason->ReasonName,
                $status,
                $createdAt
            ));
        }

        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.ride_type"),
            trans("$string_file.user_details"),
            trans("$string_file.ride_details"),
            trans("$string_file.pickup_drop"),
            trans("$string_file.cancel_reason"),
            trans("$string_file.current_status"),
            trans("$string_file.created_at")
        );
        
        $file_name = 'corporate_cancel_rides-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }
    public function CorporateFailedRideExport(){
        $corporate = get_corporate();
        $merchant = $corporate->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $bookings = $this->failsBookings(true,'CORPORATE');

        $export = [];
        foreach($bookings as $booking){
            if($booking->booking_type == 1){
                $bookingType = trans("$string_file.ride_now");
            }else{
                $bookingType = trans("$string_file.ride_later");
            }
            $userDetail = $booking->User->UserName . " (" . $booking->User->UserPhone . ") (" . $booking->User->email . ")";

            $service_text = ($booking->ServiceType) ? $booking->ServiceType->serviceName : $booking->deliveryType->name;
            $rideDetail = $booking->platform. "(" . $service_text . ") (" . $booking->VehicleType->VehicleTypeName . ")";

            $createdAt = $booking->created_at->toDateString() . "(". $booking->created_at->toTimeString() .")";
            
            if($booking->failreason == 1){
                $failReason = trans("$string_file.configuration_not_found");
            }else{
                $failReason = trans("$string_file.driver_not_found");
            }

            array_push($export,array(
                $booking->merchant_booking_id,
                $bookingType,
                $userDetail,
                $rideDetail,
                $booking->pickup_location . "(". $booking->drop_location .")",
                $failReason,
                $createdAt
            ));
        }

        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.ride_type"),
            trans("$string_file.user_details"),
            trans("$string_file.ride_details"),
            trans("$string_file.pickup_drop"),
            trans("$string_file.failed_reason"),
            trans("$string_file.created_at")
        );
        
        $file_name = 'corporate_cancel_rides-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }
    public function CorporateAutoCancelRideExport(){
        $corporate = get_corporate();
        $merchant = $corporate->Merchant;
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $bookings = $this->bookings(true, [1016],'CORPORATE');

        $export = [];
        foreach($bookings as $booking){
            if($booking->booking_type == 1){
                $bookingType = trans("$string_file.ride_now");
            }else{
                $bookingType = trans("$string_file.ride_later");
            }
            $userDetail = $booking->User->UserName . " (" . $booking->User->UserPhone . ") (" . $booking->User->email . ")";

            $service_text = ($booking->ServiceType) ? $booking->ServiceType->serviceName : $booking->deliveryType->name;
            $rideDetail = $booking->platform. "(" . $service_text . ") (" . $booking->VehicleType->VehicleTypeName . ")";

            $createdAt = $booking->created_at->toDateString() . "(". $booking->created_at->toTimeString() .")";
                

            array_push($export,array(
                $booking->merchant_booking_id,
                $bookingType,
                $userDetail,
                $rideDetail,
                $booking->pickup_location . "(". $booking->drop_location .")",
                $createdAt
            ));
        }

        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.ride_type"),
            trans("$string_file.user_details"),
            trans("$string_file.ride_details"),
            trans("$string_file.pickup_drop"),
            trans("$string_file.created_at")
        );
        
        $file_name = 'corporate_auto_ancel_rides-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function userWalletTransaction($id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $userwallettransactions = UserWalletTransaction::where([['user_id', '=', $id]])->get();
        if ($userwallettransactions->isEmpty()) :
            return redirect()->back()->with('nowallettransactionexport', 'No Ride data');
        endif;
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($userwallettransactions) use($string_file) {
//            $userwallettransactions->UserName = $userwallettransactions->User->UserName;
//            $userwallettransactions->email = $userwallettransactions->User->email;
//            $userwallettransactions->amount = $userwallettransactions->amount;
//
//            if ($userwallettransactions->type == 1) {
//                $userwallettransactions->type = trans("$string_file.credit");
//            } else {
//                $userwallettransactions->type = trans("$string_file.debit");
//            }
//
//            if ($userwallettransactions->payment_method == 1) {
//                $userwallettransactions->payment_method = trans("$string_file.cash");
//            } else {
//                $userwallettransactions->payment_method = trans("$string_file.non_cash");
//            }
//        });
//        $csvExporter->build($userwallettransactions,
//            [
//                'email' => trans("$string_file.email"),
//                'UserName' => trans("$string_file.name"),
//                'wallet_balance' => trans("$string_file.wallet_money"),
//                'type' => trans("$string_file.transaction_type"),
//                'payment_method' => trans("$string_file.payment_method"),
//                'amount' => trans("$string_file.amount"),
//                'platfrom' => trans("$string_file.narration"),
//                'receipt_number' => trans("$string_file.receipt_number"),
//                'created_at' => trans("$string_file.registered_date"),
//            ]
//        )->download('UserWalletTransaction_' . time() . '.csv');

        $export = [];
        foreach($userwallettransactions as $userwallettransaction){
            $userwallettransaction->UserName = $userwallettransaction->User->UserName;
            $userwallettransaction->email = $userwallettransaction->User->email;

            if ($userwallettransaction->type == 1) {
                $userwallettransaction->type = trans("$string_file.credit");
            } else {
                $userwallettransaction->type = trans("$string_file.debit");
            }

            if ($userwallettransaction->payment_method == 1) {
                $userwallettransaction->payment_method = trans("$string_file.cash");
            } else {
                $userwallettransaction->payment_method = trans("$string_file.non_cash");
            }
            array_push($export, array(
                $userwallettransaction->email,
                $userwallettransaction->UserName,
                $userwallettransaction->wallet_balance,
                $userwallettransaction->type,
                $userwallettransaction->payment_method,
                $userwallettransaction->amount,
                $userwallettransaction->platfrom,
                $userwallettransaction->receipt_number,
                $userwallettransaction->created_at,
            ));
        }
        $heading = array(
            trans("$string_file.email"),
            trans("$string_file.name"),
            trans("$string_file.wallet_money"),
            trans("$string_file.transaction_type"),
            trans("$string_file.payment_method"),
            trans("$string_file.amount"),
            trans("$string_file.narration"),
            trans("$string_file.receipt_number"),
            trans("$string_file.registered_date"),
        );
        $file_name = 'user-wallet-transaction-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function UserWalletReport(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $parameter = '';
        switch ($request->parameter) {
            case "1":
                $parameter = \DB::raw('concat(`first_name`, `last_name`)');
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "UserPhone";
                break;
        }
        $keyword = $request->keyword;
        $query = UserWalletTransaction::where([['merchant_id', '=', $merchant_id]]);
        if (!empty($keyword) && !empty($parameter)) {
            $query->WhereHas('User', function ($q) use ($keyword, $parameter) {
                $q->where($parameter, 'LIKE', '%' . $keyword . '%');
            });
        }
        $wallet_transactions = $query->get();
        if ($wallet_transactions->isEmpty()) :
            return redirect()->back()->with('nowallettransectionsexport', 'No data');
        endif;
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($wallet_transactions) use ($string_file) {
//            $wallet_transactions->rider = $wallet_transactions->User->UserName . " (" . $wallet_transactions->User->UserPhone . ") (" . $wallet_transactions->User->email . ")";
//            if ($wallet_transactions->type == 1 || $wallet_transactions->type == 4) :
//                $wallet_transactions->type = trans("$string_file.credit") . $cashback = ($wallet_transactions->type == 4) ? '( ' . trans('api.cashback') . ' )' : '';;
//            else :
//                $wallet_transactions->type = trans("$string_file.debit");
//            endif;
//            if ($wallet_transactions->platfrom == 1):
//                $wallet_transactions->platfrom = trans('admin.sub-admin');
//            else:
//                $wallet_transactions->platfrom = trans("$string_file.application");
//            endif;
//            $wallet_transactions->wallet_bal = $wallet_transactions->User->wallet_balance;
//        });
//        $csvExporter->build($wallet_transactions,
//            [
//                'rider' => trans("$string_file.user_details"),
//                'amount' => trans("$string_file.amount"),
//                'type' => trans("$string_file.transaction_type"),
//                'created_at' => trans('admin.message266'),
//                'platfrom' => trans('admin.message272'),
//                'receipt_number' => trans('admin.message478'),
//                'description' => trans("$string_file.description"),
//                'wallet_bal' => trans('admin.message513'),
//            ])->download('User_Wallet_Report_' . time() . '.csv');

        $export = [];
        foreach($wallet_transactions as $wallet_transaction){
            $wallet_transaction->rider = $wallet_transaction->User->UserName . " (" . $wallet_transaction->User->UserPhone . ") (" . $wallet_transaction->User->email . ")";
            if ($wallet_transaction->type == 1 || $wallet_transaction->type == 4):
                $wallet_transaction->type = trans("$string_file.credit").$cashback = ($wallet_transaction->type == 4) ? '( '.trans('api.cashback').' )':'';;
            else:
                $wallet_transaction->type = trans("$string_file.debit");
            endif;
            if ($wallet_transaction->platfrom == 1) :
                $wallet_transaction->platfrom = trans('admin.sub-admin');
            else :
                $wallet_transaction->platfrom = trans("$string_file.application");
            endif;
            $wallet_transaction->wallet_bal = $wallet_transaction->User->wallet_balance;

            array_push($export, array(
                $wallet_transaction->rider,
                $wallet_transaction->amount,
                $wallet_transaction->type,
                $wallet_transaction->created_at,
                $wallet_transaction->platfrom,
                $wallet_transaction->receipt_number,
                $wallet_transaction->description,
                $wallet_transaction->wallet_bal,
            ));
        }
        $heading = array(
            trans("$string_file.user_details"),
            trans("$string_file.amount"),
            trans("$string_file.transaction_type"),
            trans('admin.message266'),
            trans('admin.message272'),
            trans('admin.message478'),
            trans("$string_file.description"),
            trans('admin.message513')
        );
        $file_name = 'user_wallet_report_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function userRides($id)
    {
        $userrides = Booking::where([['user_id', '=', $id]])->get();
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $booking_status = $this->getBookingStatus($string_file);
        if ($userrides->isEmpty()) :
            return redirect()->back()->withErrors('No Ride data');
        endif;
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($userrides) use($booking_status) {
//            $userrides->user_id = $userrides->User->UserName;
//            $userrides->driver_id = $userrides->Driver ? $userrides->Driver->fullName : trans('admin.message273');
//            $userrides->payment_method_id = $userrides->PaymentMethod->payment_method;
//            $userrides->booking_status = isset($booking_status[$userrides->booking_status]) ? $booking_status[$userrides->booking_status] : "";
//            $userrides->country_area_id = $userrides->CountryArea->CountryAreaName;
//            $userrides->service_type_id = $userrides->ServiceType->serviceName;
//            $userrides->vehicle_type_id = $userrides->VehicleType->VehicleTypeName;
//
//        });
//        $csvExporter->build($userrides,
//            [
//                'id' => trans("$string_file.ride_id"),
//                'user_id' => trans("$string_file.user_name"),
//                'driver_id' => trans("$string_file.driver"),
//                'pickup_location' => trans("$string_file.pickup_location"),
//                'drop_location' => trans("$string_file.drop_off_location"),
//                'payment_method_id' => trans("$string_file.payment_method"),
//                'booking_status' => trans("$string_file.ride_status"),
//                'country_area_id' => trans("$string_file.service_area"),
//                'service_type_id' => trans("$string_file.service_type"),
//                'vehicle_type_id' => trans("$string_file.vehicle_type"),
//                'created_at' => trans("$string_file.date"),
//
//            ])->download('UserRides_' . time() . '.csv');

        $export = [];
        foreach($userrides as $userride){
            $userride->user_id = $userride->User->UserName;
            $userride->driver_id = $userride->Driver ? $userride->Driver->fullName : trans('admin.message273');
            $userride->payment_method_id = $userride->PaymentMethod->payment_method;
            $userride->booking_status = isset($booking_status[$userride->booking_status]) ? $booking_status[$userride->booking_status] : "";
            $userride->country_area_id = $userride->CountryArea->CountryAreaName;
            $userride->service_type_id = $userride->ServiceType->serviceName;
            $userride->vehicle_type_id = $userride->VehicleType->VehicleTypeName;

            array_push($export, array(
                $userride->id,
                $userride->user_id,
                $userride->driver_id,
                $userride->pickup_location,
                $userride->drop_location,
                $userride->payment_method_id,
                $userride->booking_status,
                $userride->country_area_id,
                $userride->service_type_id,
                $userride->vehicle_type_id,
                $userride->created_at,
            ));
        }
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.user_name"),
            trans("$string_file.driver"),
            trans("$string_file.pickup_location"),
            trans("$string_file.drop_off_location"),
            trans("$string_file.payment_method"),
            trans("$string_file.ride_status"),
            trans("$string_file.service_area"),
            trans("$string_file.service_type"),
            trans("$string_file.vehicle_type"),
            trans("$string_file.date"),
        );
        $file_name = 'user_rides_' . time() . '.csv';return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function DriverExport(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $drivers = $this->getAllDriver(false, $request);
        $config = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
        $appConfig = ApplicationConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
        $gender_enable = $config->gender;
        if ($drivers->isEmpty()) :
            return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($drivers) use($string_file) {
        //            $driver_vehicles = $drivers->DriverVehicles;
        //            $vehicle_type_name = [];
        //            foreach($driver_vehicles as $vehicle){
        //                $vehicle_type_name[] = $vehicle->VehicleType->VehicleTypeName;
        //            }
        //            $drivers->vehicle_types = implode(',',$vehicle_type_name);
        //            $drivers->country_area_id = $drivers->CountryArea->CountryAreaName;
        //            if (is_null($drivers->total_earnings)):
        //                $drivers->total_earnings = 0;
        //            endif;
        //            if (is_null($drivers->total_trips)):
        //                $drivers->total_trips = "None";
        //            endif;
        //            if (is_null($drivers->wallet_money)):
        //                $drivers->wallet_money = 0;
        //            endif;
        //            if ($drivers->driver_gender == 1) {
        //                $drivers->driver_gender = trans("$string_file.male");
        //            } elseif ($drivers->driver_gender = "") {
        //                $drivers->driver_gender = "---";
        //            } else {
        //                $drivers->driver_gender = trans("$string_file.female");
        //            }
        //
        //            if($drivers->login_logout == 1){
        //                $drivers->login_logout = trans("$string_file.login");
        //            }
        //            else{
        //                $drivers->login_logout = trans("$string_file.logout");
        //            }
        //
        //            if($drivers->online_offline == 1){
        //                $drivers->online_offline = trans("$string_file.online");
        //            }
        //            else{
        //                $drivers->online_offline = trans("$string_file.offline");
        //            }
        //            if($drivers->free_busy == 1){
        //                $drivers->free_busy = trans("$string_file.busy");
        //            }
        //            else{
        //                $drivers->free_busy = trans("$string_file.free");
        //            }
        //            if (is_null($drivers->bank_name)):trans("$string_file.free");
        //                $drivers->bank_name = "None";
        //            endif;
        //            if (is_null($drivers->account_holder_name)):
        //                $drivers->account_holder_name = "None";
        //            endif;
        //            if (is_null($drivers->account_number)):
        //                $drivers->account_number = "None";
        //            endif;
        //
        //        });
        //        if($gender_enable == 1)
        //        {
        //            $csvExporter->build($drivers,
        //                ['fullName' => trans("$string_file.driver"),
        //                    'email' => trans("$string_file.email"),
        //                    'country_area_id' => trans("$string_file.service_area"),
        //                    'phoneNumber' => trans("$string_file.phone"),
        //                    'driver_gender' => trans("$string_file.gender"),
        //                    'wallet_money' => trans("$string_file.wallet_money"),
        //                    'driver_referralcode' => trans("$string_file.referral_code"),
        //                    'online_offline' => trans("$string_file.online_offline"),
        //                    'free_busy' => trans("$string_file.free_busy"),
        //                    'login_logout' => trans("$string_file.login_logout"),
        //                    'total_trips' => trans("$string_file.total_rides"),
        //                    'total_earnings' => trans("$string_file.total_earning"),
        //                    'bank_name' => trans("$string_file.bank_name"),
        //                    'account_holder_name' => trans("$string_file.account_holder_name"),
        //                    'account_number' => trans("$string_file.account_number"),
        //                    'last_location_update_time' => trans("$string_file.last").' '.trans("$string_file.location").' '.trans("$string_file.updated"),
        //                    'vehicle_types' => trans("$string_file.vehicle").' '.trans("$string_file.type"),
        //                    'created_at' => trans("$string_file.registered_date"),
        //                ])->download('drivers_' . time() . '.csv');
        //        }
        //        else
        //        {
        //            $csvExporter->build($drivers,
        //                ['fullName' => trans("$string_file.driver"),
        //                    'email' => trans("$string_file.email"),
        //                    'country_area_id' => trans("$string_file.service_area"),
        //                    'phoneNumber' => trans("$string_file.phone"),
        //                    'wallet_money' => trans("$string_file.wallet_money"),
        //                    'driver_referralcode' => trans("$string_file.referral_code"),
        //                    'online_offline' => trans("$string_file.online_offline"),
        //                    'free_busy' => trans("$string_file.free_busy"),
        //                    'login_logout' => trans("$string_file.login_logout"),
        //                    'total_trips' => trans("$string_file.total_rides"),
        //                    'total_earnings' => trans("$string_file.total_earning"),
        //                    'bank_name' => trans("$string_file.bank_name"),
        //                    'account_holder_name' => trans("$string_file.account_holder_name"),
        //                    'account_number' => trans("$string_file.account_number"),
        //                    'created_at' => trans("$string_file.registered_date"),
        //                    'last_location_update_time' => trans("$string_file.last").' '.trans("$string_file.location").' '.trans("$string_file.updated"),
        //                    'vehicle_types' => trans("$string_file.vehicle").' '.trans("$string_file.type"),
        //                ])->download('drivers_' . time() . '.csv');
        //        }

        $export = [];
        foreach ($drivers as $driver) {
            $driver_vehicles = $driver->DriverVehicles;
            $vehicle_type_name = [];
            foreach ($driver_vehicles as $vehicle) {
                $vehicle_type_name[] = $vehicle->VehicleType->VehicleTypeName;
            }
            $driver->vehicle_types = implode(',', $vehicle_type_name);
            $driver->country_area_id = $driver->CountryArea->CountryAreaName;
            $driver->total_earnings = is_null($driver->total_earnings) ? 0 : $driver->total_earnings;
            $driver->total_trips = is_null($driver->total_trips) ? "None" : $driver->total_trips;
            $driver->wallet_money = is_null($driver->wallet_money) ? 0 : $driver->wallet_money;

            if ($driver->driver_gender == 1) {
                $driver->driver_gender = trans("$string_file.male");
            } elseif ($driver->driver_gender = "") {
                $driver->driver_gender = "---";
            } else {
                $driver->driver_gender = trans("$string_file.female");
            }

            $driver->login_logout = ($driver->login_logout == 1) ? trans("$string_file.login") : trans("$string_file.logout");
            $driver->online_offline = ($driver->online_offline == 1) ? trans("$string_file.online") : trans("$string_file.offline");
            $driver->free_busy = ($driver->free_busy == 1) ? trans("$string_file.busy") : trans("$string_file.free");

            $driver->bank_name = is_null($driver->bank_name) ? "None" : $driver->bank_name;
            $driver->account_holder_name = is_null($driver->account_holder_name) ? "None" : $driver->account_holder_name;
            $driver->account_number = is_null($driver->account_number) ? "None" : $driver->account_number;
            $driver->vat_number = is_null($driver->vat_number) ? "None" : $driver->vat_number;

            $temp = array(
                $driver->merchant_driver_id,
                $driver->fullName,
                $driver->email,
                $driver->country_area_id,
                $driver->phoneNumber,
                $driver->wallet_money,
                $driver->driver_referralcode,
                $driver->online_offline,
                $driver->free_busy,
                $driver->login_logout,
                $driver->total_trips,
                $driver->total_earnings,
                // $driver->bank_name,
                // $driver->account_holder_name,
                // $driver->account_number,
//                $driver->created_at,
                convertTimeToUSERzone($driver->created_at, $driver->CountryArea->timezone, null, $driver->Merchant),
                $driver->last_location_update_time,
                $driver->vehicle_types,
                // $driver->vat_number
            );
            if ($gender_enable == 1) {
                array_push($temp, $driver->driver_gender);
            }
            if($config->bank_details_enable == 1){
                    array_push($temp, $driver->bank_name);
                    array_push($temp, $driver->account_holder_name);
                    array_push($temp, $driver->account_holder_name);
            }
            if($appConfig->driver_vat_configuration == 1){
                array_push($temp, $driver->vat_number);
            }
            array_push($export, $temp);
        }
        $heading = array(
            trans("$string_file.id"),
            trans("$string_file.driver"),
            trans("$string_file.email"),
            trans("$string_file.service_area"),
            trans("$string_file.phone"),
            trans("$string_file.wallet_money"),
            trans("$string_file.referral_code"),
            trans("$string_file.online_offline"),
            trans("$string_file.free_busy"),
            trans("$string_file.login_logout"),
            trans("$string_file.total_rides"),
            trans("$string_file.total_earning"),
            // trans("$string_file.bank_name"),
            // trans("$string_file.account_holder_name"),
            // trans("$string_file.account_number"),
            trans("$string_file.registered_date"),
            trans("$string_file.last") . ' ' . trans("$string_file.location") . ' ' . trans("$string_file.updated"),
            trans("$string_file.vehicle") . ' ' . trans("$string_file.type"),
            // trans("$string_file.vat_number")
        );
        if ($gender_enable == 1) {
            array_push($heading, trans("$string_file.gender"));
        }
        if($config->bank_details_enable == 1){
                array_push($heading, trans("$string_file.bank_name"));
                array_push($heading, trans("$string_file.account_holder_name"));
                array_push($heading, trans("$string_file.account_number"));
        }
        if($appConfig->driver_vat_configuration == 1){
                array_push($heading, trans("$string_file.vat_number"));
            
        }
        $file_name = 'drivers_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function basicSignupDriver(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $request->merge(['request_from' => "basic_signup"]);
        $basicdrivers = $this->getAllDriver(false, $request);

        if ($basicdrivers->isEmpty()) :
            return redirect()->back()->withErrors('No basic drivers');
        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($basicdrivers) use($string_file) {
        //            if(!empty($basicdrivers->country_area_id))
        //            {
        //                $basicdrivers->country_area_id = $basicdrivers->CountryArea->CountryAreaName;
        //            }
        //            else
        //            {
        //                $basicdrivers->country_area_id = "";
        //            }
        //
        //            if (is_null($basicdrivers->total_earnings)):
        //                $basicdrivers->total_earnings = 0;
        //            endif;
        //            if (is_null($basicdrivers->total_trips)):
        //                $basicdrivers->total_trips = "None";
        //            endif;
        //
        //            if ($basicdrivers->driver_gender == 1) {
        //                $basicdrivers->driver_gender = trans("$string_file.male");
        //            } elseif ($basicdrivers->driver_gender = "") {
        //                $basicdrivers->driver_gender = "---";
        //            } else {
        //                $basicdrivers->driver_gender = trans("$string_file.female");
        //            }
        //
        //
        //        });
        //        $csvExporter->build($basicdrivers,
        //            [
        //                'fullName' => trans("$string_file.name"),
        //                'email' => trans("$string_file.email"),
        //                'country_area_id' => trans("$string_file.service_area"),
        //                'phoneNumber' => trans("$string_file.phone"),
        //                'driver_gender' => trans("$string_file.gender"),
        //                'total_trips' => trans("$string_file.total_rides"),
        //                'total_earnings' => trans("$string_file.total_earning"),
        //                'created_at' => trans("$string_file.registered_date"),
        //                'updated_at' => trans("$string_file.updated_at"),
        //            ])->download('basicdrivers_' . time() . '.csv');

        $export = [];
        foreach ($basicdrivers as $driver) {
            $driver->country_area_id = !empty($driver->country_area_id) ? $driver->CountryArea->CountryAreaName : "--";

            if ($driver->driver_gender == 1) {
                $driver->driver_gender = trans("$string_file.male");
            } elseif ($driver->driver_gender = "") {
                $driver->driver_gender = "---";
            } else {
                $driver->driver_gender = trans("$string_file.female");
            }

            array_push($export, array(
                $driver->fullName,
                $driver->email,
                $driver->country_area_id,
                $driver->phoneNumber,
                $driver->driver_gender,
                $driver->created_at,
                $driver->updated_at,
            ));
        }
        $heading = array(
            trans("$string_file.name"),
            trans("$string_file.email"),
            trans("$string_file.service_area"),
            trans("$string_file.phone"),
            trans("$string_file.gender"),
            trans("$string_file.registered_date"),
            trans("$string_file.updated_at"),
        );
        $file_name = 'basicdrivers_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function pendingDrivers(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $request->merge(['request_from' => "pending_approval"]);
        $pendingdrivers = $this->getAllDriver(false, $request);
        //        $pendingdrivers = $this->getAllPendingDriver(false)->get();
        if ($pendingdrivers->count() == 0) :
            return redirect()->back()->withErrors('No pending drivers');
        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($pendingdrivers) use($string_file) {
        //            $pendingdrivers->country_area_id = $pendingdrivers->CountryArea->CountryAreaName;
        //            if (is_null($pendingdrivers->total_earnings)):
        //                $pendingdrivers->total_earnings = 0;
        //            endif;
        //            if (is_null($pendingdrivers->total_trips)):
        //                $pendingdrivers->total_trips = "None";
        //            endif;
        //
        //            if ($pendingdrivers->driver_gender == 1) {
        //                $pendingdrivers->driver_gender = trans("$string_file.male");
        //            } elseif ($pendingdrivers->driver_gender = "") {
        //                $pendingdrivers->driver_gender = "---";
        //            } else {
        //                $pendingdrivers->driver_gender = trans("$string_file.female");
        //            }
        //
        //
        //        });
        //        $csvExporter->build($pendingdrivers,
        //            [
        //                'fullName' => trans("$string_file.name"),
        //                'email' => trans("$string_file.email"),
        //                'country_area_id' => trans("$string_file.service_area"),
        //                'phoneNumber' => trans("$string_file.phone"),
        //                'driver_gender' => trans("$string_file.gender"),
        //                'total_trips' => trans("$string_file.total_rides"),
        //                'total_earnings' => trans("$string_file.total_earning"),
        //                'created_at' => trans("$string_file.registered_date"),
        //                'updated_at' => trans("$string_file.updated_at"),
        //            ])->download('pendingdrivers_' . time() . '.csv');

        $export = [];
        foreach ($pendingdrivers as $driver) {
            $driver->country_area_id = !empty($driver->country_area_id) ? $driver->CountryArea->CountryAreaName : "--";
            $driver->total_earnings = is_null($driver->total_earnings) ? 0 : $driver->total_earnings;
            $driver->total_trips = is_null($driver->total_trips) ? "None" : $driver->total_trips;


            if ($driver->driver_gender == 1) {
                $driver->driver_gender = trans("$string_file.male");
            } elseif ($driver->driver_gender = "") {
                $driver->driver_gender = "---";
            } else {
                $driver->driver_gender = trans("$string_file.female");
            }

            array_push($export, array(
                $driver->fullName,
                $driver->email,
                $driver->country_area_id,
                $driver->phoneNumber,
                $driver->driver_gender,
                $driver->total_trips,
                $driver->total_earnings,
                convertTimeToUSERzone($driver->created_at,$driver->CountryArea->timezone,$driver->merchant_id, null, 2),
                convertTimeToUSERzone($driver->updated_at,$driver->CountryArea->timezone,$driver->merchant_id, null, 2)
            ));
        }
        $heading = array(
            trans("$string_file.name"),
            trans("$string_file.email"),
            trans("$string_file.service_area"),
            trans("$string_file.phone"),
            trans("$string_file.gender"),
            trans("$string_file.total_rides"),
            trans("$string_file.total_earning"),
            trans("$string_file.registered_date"),
            trans("$string_file.updated_at"),
        );
        $file_name = 'pendingdrivers_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function rejectedDriver(Request  $request)
    {
        //        $rejecteddrivers = $this->getAllRejectedDrivers(false)->get();
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $request->merge(['request_from' => "rejected_driver"]);
        $rejecteddrivers = $this->getAllDriver(false, $request);
        if ($rejecteddrivers->isEmpty()) :
            return redirect()->back()->with('rejecteddriversdownload', 'No rejected drivers');
        endif;
        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($rejecteddrivers) use($string_file){
        //            $rejecteddrivers->country_area_id = $rejecteddrivers->CountryArea->CountryAreaName;
        //            if (is_null($rejecteddrivers->total_earnings)):
        //                $rejecteddrivers->total_earnings = 0;
        //            endif;
        //            if (is_null($rejecteddrivers->total_trips)):
        //                $rejecteddrivers->total_trips = "None";
        //            endif;
        //
        //            if ($rejecteddrivers->driver_gender == 1) {
        //                $rejecteddrivers->driver_gender = trans("$string_file.male");
        //            } elseif ($rejecteddrivers->driver_gender = "") {
        //                $rejecteddrivers->driver_gender = "---";
        //            } else {
        //                $rejecteddrivers->driver_gender = trans("$string_file.female");
        //            }
        //
        //
        //        });
        //        $csvExporter->build($rejecteddrivers,
        //            [
        //                'fullName' => trans("$string_file.name"),
        //                'email' => trans("$string_file.email"),
        //                'country_area_id' => trans("$string_file.service_area"),
        //                'phoneNumber' => trans("$string_file.phone"),
        //                'driver_gender' => trans("$string_file.gender"),
        //                'total_trips' => trans("$string_file.total_rides"),
        //                'total_earnings' => trans("$string_file.total_earning"),
        //                'created_at' => trans("$string_file.registered_date"),
        //                'updated_at' => trans("$string_file.updated_at"),
        //            ])->download('rejecteddrivers_' . time() . '.csv');

        $export = [];
        foreach ($rejecteddrivers as $driver) {
            $driver->country_area_id = !empty($driver->country_area_id) ? $driver->CountryArea->CountryAreaName : "--";
            $driver->total_earnings = is_null($driver->total_earnings) ? 0 : $driver->total_earnings;
            $driver->total_trips = is_null($driver->total_trips) ? "None" : $driver->total_trips;


            if ($driver->driver_gender == 1) {
                $driver->driver_gender = trans("$string_file.male");
            } elseif ($driver->driver_gender = "") {
                $driver->driver_gender = "---";
            } else {
                $driver->driver_gender = trans("$string_file.female");
            }

            array_push($export, array(
                $driver->fullName,
                $driver->email,
                $driver->country_area_id,
                $driver->phoneNumber,
                $driver->driver_gender,
                $driver->total_trips,
                $driver->total_earnings,
                convertTimeToUSERzone($driver->created_at,$driver->CountryArea->timezone,$driver->merchant_id , null, 2),
                convertTimeToUSERzone($driver->updated_at,$driver->CountryArea->timezone,$driver->merchant_id, null, 2)
            ));
        }
        $heading = array(
            trans("$string_file.name"),
            trans("$string_file.email"),
            trans("$string_file.service_area"),
            trans("$string_file.phone"),
            trans("$string_file.gender"),
            trans("$string_file.total_rides"),
            trans("$string_file.total_earning"),
            trans("$string_file.registered_date"),
            trans("$string_file.updated_at"),
        );
        $file_name = 'rejecteddrivers_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function DriverWalletReport(Request $request)
    {
        $parameter = '';
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        switch ($request->parameter) {
            case "1":
                // $parameter = \DB::raw('concat("first_name", "last_name")');
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "phoneNumber";
                break;
        }
        $keyword = $request->keyword;
        $query = DriverWalletTransaction::where([['merchant_id', '=', $merchant_id]]);
        if (!empty($keyword) && !empty($parameter)) {
            $query->WhereHas('Driver', function ($q) use ($keyword, $parameter) {
                $q->where($parameter, 'LIKE', "%$keyword%");
            });
        }
        if (isset($request->driver_id) && $request->driver_id != "") {
            $query->where('driver_id', $request->driver_id);
        }
        $wallet_transactions = $query->get();
        if ($wallet_transactions->isEmpty()) :
            return redirect()->back()->with('nowallettransectionsexport', 'No data');
        endif;
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($wallet_transactions) use ($string_file) {
//            $wallet_transactions->driver = $wallet_transactions->Driver->fullName . " (" . $wallet_transactions->Driver->phoneNumber . ") (" . $wallet_transactions->Driver->email . ")";
//            if ($wallet_transactions->transaction_type == 1) :
//                $wallet_transactions->transaction_type = trans("$string_file.credit") . $cashback = ($wallet_transactions->narration == 5) ? '( ' . trans('admin.cashback') . ' )' : '';;
//            else :
//                $wallet_transactions->transaction_type = trans("$string_file.debit");
//            endif;
//            if ($wallet_transactions->payment_method == 1):
//                $wallet_transactions->payment_method = trans("$string_file.cash");
//            else:
//                $wallet_transactions->payment_method = trans("$string_file.non_cash");
//            endif;
//            switch ($wallet_transactions->platform):
//                case 1:
//                    $wallet_transactions->platform = trans("$string_file.admin");
//                    break;
//                case 2:
//                    $wallet_transactions->platform = trans("$string_file.application");
//                    break;
//                case 3:
//                    $wallet_transactions->platform = trans("$string_file.web");
//                    break;
//            endswitch;
//            $wallet_transactions->wallet_bal = $wallet_transactions->Driver->wallet_money;
//        });
//        $csvExporter->build($wallet_transactions,
//            [
//                'driver' => trans("$string_file.driver_details"),
//                'transaction_type' => trans("$string_file.transaction_type"),
//                'payment_method' => trans("$string_file.payment"),
//                'receipt_number' => trans("$string_file.receipt_no"),
//                'platform' => trans("$string_file.plateform"),
//                'amount' => trans("$string_file.amount"),
//                'created_at' => trans("$string_file.created"),
//                'description' => trans("$string_file.description"),
//                'wallet_bal' => trans("$string_file.wallet_money"),
//            ])->download('Driver_Wallet_Report_' . time() . '.csv');

        $export = [];
        foreach($wallet_transactions as $wallet_transaction){
            $wallet_transaction->driver = $wallet_transaction->Driver->fullName . " (" . $wallet_transaction->Driver->phoneNumber . ") (" . $wallet_transaction->Driver->email . ")";
            if ($wallet_transaction->transaction_type == 1):
                $wallet_transaction->transaction_type = trans("$string_file.credit").$cashback = ($wallet_transaction->narration == 5) ? '( '.trans('admin.cashback').' )':'';;
            else:
                $wallet_transaction->transaction_type = trans("$string_file.debit");
            endif;
            if ($wallet_transaction->payment_method == 1) :
                $wallet_transaction->payment_method = trans("$string_file.cash");
            else :
                $wallet_transaction->payment_method = trans("$string_file.non_cash");
            endif;
            switch ($wallet_transaction->platform):
                case 1:
                    $wallet_transaction->platform = trans("$string_file.admin");
                    break;
                case 2:
                    $wallet_transaction->platform = trans("$string_file.application");
                    break;
                case 3:
                    $wallet_transaction->platform = trans("$string_file.web");
                    break;
            endswitch;
            $wallet_transaction->wallet_bal = $wallet_transaction->Driver->wallet_money;

            array_push($export, array(
                $wallet_transaction->driver,
                $wallet_transaction->transaction_type,
                $wallet_transaction->payment_method,
                $wallet_transaction->receipt_number,
                $wallet_transaction->platform,
                $wallet_transaction->amount,
                $wallet_transaction->created_at,
                $wallet_transaction->description,
                $wallet_transaction->wallet_bal,
            ));
        }
        $heading = array(
            trans("$string_file.driver_details"),
            trans("$string_file.transaction_type"),
            trans("$string_file.payment"),
            trans("$string_file.receipt_no"),
            trans("$string_file.plateform"),
            trans("$string_file.amount"),
            trans("$string_file.created"),
            trans("$string_file.description"),
            trans("$string_file.wallet_money")
        );
        $file_name = 'driver_wallet_report_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function DriverAcceptanceReport(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $drivers = $this->getDriverBookingRequestData($request, false);
        if ($drivers->isEmpty()) :
            return redirect()->back()->with('nodriverexport', 'No data');
        endif;
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($drivers) {
//            $drivers->driver = $drivers->fullName . " (" . $drivers->phoneNumber . ") (" . $drivers->email . ")";
//            $drivers->tot_ride = $drivers->BookingRequestDriver[0]->total_trip;
//            $drivers->accepted_ride = $drivers->BookingRequestDriver[0]->accepted;
//            $drivers->no_res = $drivers->BookingRequestDriver[0]->no_response;
//            $drivers->rej_ride = $drivers->BookingRequestDriver[0]->reject;
//            $drivers->acceptance_rate = round( $drivers->accepted_ride/$drivers->tot_ride* 100) . " %";
//        });
//        $csvExporter->build($drivers,
//            [
//                'driver' => trans("$string_file.driver_details"),
//                'tot_ride' => trans('admin.message581'),
//                'accepted_ride' => trans('admin.message582'),
//                'no_res' => trans('admin.message583'),
//                'rej_ride' => trans('admin.message584'),
//                'acceptance_rate' => trans('admin.message585'),
//            ])->download('Driver-Request-Acceptance-Report-' . time() . '.csv');

        $export = [];
        foreach($drivers as $driver){
            $driver->driver = $driver->fullName . " (" . $driver->phoneNumber . ") (" . $driver->email . ")";
            $driver->tot_ride = $driver->BookingRequestDriver[0]->total_trip;
            $driver->accepted_ride = $driver->BookingRequestDriver[0]->accepted;
            $driver->no_res = $driver->BookingRequestDriver[0]->no_response;
            $driver->rej_ride = $driver->BookingRequestDriver[0]->reject;
            $driver->acceptance_rate = round( $driver->accepted_ride/$driver->tot_ride* 100) . " %";

            array_push($export, array(
                $driver->driver,
                $driver->tot_ride,
                $driver->accepted_ride,
                $driver->no_res,
                $driver->rej_ride,
                $driver->acceptance_rate,
            ));
        }
        $heading = array(
            trans("$string_file.driver_details"),
            trans('admin.message581'),
            trans('admin.message582'),
            trans('admin.message583'),
            trans('admin.message584'),
            trans('admin.message585'),
        );
        $file_name = 'driver_request_acceptance_report_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function earningExport()
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $carpooling_ride = CarpoolingRide::where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 4]])->get();
        if ($carpooling_ride->isEmpty()) :
            return redirect()->back()->with('noreporttoexport', 'No Ride data');
        endif;
        $csvExporter = new \Laracsv\Export();
        $csvExporter->beforeEach(function ($carpooling_ride) {
            $carpooling_ride->ride_id = $carpooling_ride->id;
            $carpooling_ride->payment_method = $carpooling_ride->payment_type == 1 ? "cash/wallet" : "wallet";
            $carpooling_ride->driver_name = $carpooling_ride->User->first_name . " " . $carpooling_ride->User->last_name;
            $carpooling_ride->email = $carpooling_ride->User->email;
            $carpooling_ride->phone = $carpooling_ride->User->UserPhone;
            $carpooling_ride->currency = $carpooling_ride->User->Country->isoCode;
            $carpooling_ride->total_amount = $carpooling_ride->total_amount;
            $carpooling_ride->driver_earning = $carpooling_ride->total_amount;
            $carpooling_ride->company_commission = $carpooling_ride->company_commission;
            $carpooling_ride->tax = $carpooling_ride->service_charges;
            $carpooling_ride->ride_date = date('Y-m-d H:i:s', $carpooling_ride->ride_timestamp);
        });
        $csvExporter->build(
            $carpooling_ride,
            [
                'ride_id' => trans("$string_file.ride") . ' ' . trans("common.id"),
                'payment_method' => trans("$string_file.payment") . ' ' . trans("$string_file.method"),
                'driver_name' => trans("common.driver") . ' ' . trans("common.name"),
                'email' => trans("common.email"),
                'phone' => trans("common.phone"),
                'currency' => trans("common.currency"),
                'total_amount' => trans("$string_file.ride") . ' ' . trans("common.amount"),
                'driver_earning' => trans("$string_file.driver") . ' ' . trans("common.earning"),
                'company_commission' => trans("common.merchant") . ' ' . trans("common.earning"),
                'tax' => trans("$string_file.tax") . ' ' . trans('common.amount'),
                'ride_date' => trans("common.ride") . ' ' . trans("common.date"),
            ]
        )->download('ride_earning_statistics' . time() . '.csv');
    }

    public function cashoutExport()
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $user_cashout = UserCashout::where([['merchant_id', '=', $merchant_id], ['cashout_status', '=', 0], ['action_by', '=', 'Bank Transfer']])->latest()->get();
        if ($user_cashout->isEmpty()) :
            return redirect()->back()->with('noreporttoexport', 'No Ride data');
        endif;
        $csvExporter = new \Laracsv\Export();
        $csvExporter->beforeEach(function ($user_cashout) {
            $user_cashout->id = $user_cashout->id;
            $user_cashout->user_id = $user_cashout->user_id;
            $user_cashout->user_name = $user_cashout->User->first_name . " " . $user_cashout->User->last_name;
            $user_cashout->currency = $user_cashout->User->Country->isoCode;
            $user_cashout->amount = $user_cashout->amount;
            $user_cashout->phone = $user_cashout->User->UserPhone;
            $user_cashout->transaction_id = $user_cashout->transaction_id;
            $user_cashout->status = $user_cashout->cashout_status == 0 ? "Pending" : "Completed";
            $user_cashout->created_at = $user_cashout->created_at;
        });
        $csvExporter->build(
            $user_cashout,
            [
                'id' => trans("common.id"),
                'user_id' => trans("common.user") . ' ' . trans("common.id"),
                'user_name' => trans("common.user") . ' ' . trans("common.name"),
                'currency' => trans("common.currency"),
                'amount' => trans("common.amount"),
                'phone' => trans("common.phone"),
                'transaction_id' => trans("common.transaction") . ' ' . trans("common.id"),
                'status' => trans("common.status"),
                'created_at' => trans("common.date"),
            ]
        )->download('cashout_statistics' . time() . '.csv');
    }

    public function driverCashoutExport(){
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $driver_cashout = DriverCashout::where([['merchant_id', '=', $merchant_id]])->latest()->get();
        if ($driver_cashout->isEmpty()) :
            return redirect()->back()->with('noreporttoexport', 'No Ride data');
        endif;
        
        $export = [];
        foreach($driver_cashout as $driver_cashout){
            $driver_cashout->driver_id = $driver_cashout->Driver->first_name." ".$driver_cashout->Driver->last_name;
            $driver_cashout->email = $driver_cashout->Driver->email;
            $driver_cashout->amount = $driver_cashout->amount;
            $driver_cashout->created_at = $driver_cashout->created_at;
            $driver_cashout->status = $driver_cashout->cashout_status == 0 ? "Pending" : "Completed";

            array_push($export, array(
                $driver_cashout->driver_id,
                $driver_cashout->email,
                $driver_cashout->amount,
                $driver_cashout->status,
                $driver_cashout->created_at
            ));
        }
        $heading = array(
            trans("$string_file.name"),
            trans("$string_file.email"),
            trans("$string_file.cashout_amount"),
            trans("$string_file.status"),
            trans("$string_file.requested_at"),
        );
        $file_name = 'driver_cashout_statistics_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
        
    }

    public function DriverOnlineTimeReport(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $query = DriverOnlineTime::has('Driver')->where([['merchant_id', '=', $merchant_id]]);
        if (!empty($request->driver_name)) {
            $query->WhereHas('Driver', function ($q) use ($request) {
                $q->where("first_name", 'LIKE', "%" . $request->driver_name . "%");
            });
        }
        if (!empty($request->email)) {
            $query->WhereHas('Driver', function ($q) use ($request) {
                $q->where('email', 'LIKE', "%$request->driver_name%");
            });
        }
        $driver_times = $query->latest()->get();
        if ($driver_times->isEmpty()) :
            return redirect()->back()->with('nodriveronlineexport', 'No data');
        endif;
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($driver_times) {
//           $driver_times->driver_id = $driver_times->Driver->first_name." ".$driver_times->Driver->last_name;
//            $driver_times->email = $driver_times->Driver->email;
//            $driver_times->online_time = $driver_times->time_intervals[0]['online_time'];
//            $driver_times->offline_time = $driver_times->time_intervals[0]['offline_time'];
//            $driver_times->tot_time = $driver_times->hours . " Hours " . $driver_times->minutes . " Minutes";
//        });
//        $csvExporter->build($driver_times,
//            [
//                'driver_id' => trans("$string_file.name"),
//                'email' => trans("$string_file.email"),
//                'online_time' => trans('admin.message772'),
//                'offline_time' => trans('admin.message773'),
//                'tot_time' => trans('admin.message774'),
//            ])->download('Driver_Online_Time_Report_' . time() . '.csv');

        $export = [];
        foreach($driver_times as $driver_time){
            $driver_time->driver_id = $driver_time->Driver->first_name." ".$driver_time->Driver->last_name;
            $driver_time->email = $driver_time->Driver->email;
            $driver_time->online_time = $driver_time->time_intervals[0]['online_time'];
            $driver_time->offline_time = $driver_time->time_intervals[0]['offline_time'];
            $driver_time->tot_time = $driver_time->hours . " Hours " . $driver_time->minutes . " Minutes";

            array_push($export, array(
                $driver_time->driver_id,
                $driver_time->email,
                $driver_time->online_time,
                $driver_time->offline_time,
                $driver_time->tot_time,
            ));
        }
        $heading = array(
            trans("$string_file.name"),
            trans("$string_file.email"),
            trans("$string_file.online_time_text"),
            trans("$string_file.offline_time_text"),
            trans("$string_file.total_login_hour"),
        );
        $file_name = 'driver_online_time_report_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function DriverAccounts()
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);

        $drivers = Driver::with(['DriverAccount' => function ($query) {
            $query->where([['status', '=', 1]]);
        }])->where([['merchant_id', '=', $merchant_id], ['total_earnings', '!=', NULL]])->get();
        if ($drivers->isEmpty()) :
            return redirect()->back()->withErrors('No data');
        endif;
        $csvExporter = new \Laracsv\Export();
        $csvExporter->beforeEach(function ($drivers) {
            $drivers->driver = $drivers->fullName . " (" . $drivers->phoneNumber . ") (" . $drivers->email . ")";
            if ($drivers->CountryArea->LanguageSingle) :
                $drivers->area = $drivers->CountryArea->LanguageSingle->AreaName;
            else :
                $drivers->area = $drivers->CountryArea->LanguageAny->AreaName;
            endif;
            $drivers->out_bill = sprintf("%0.2f", array_sum(array_pluck($drivers->DriverAccount, 'amount')));
            if ($drivers->outstand_amount) :
                $drivers->unbill_amount = sprintf("%0.2f", $drivers->outstand_amount);
            else :
                $drivers->unbill_amount = trans('admin.message470');
            endif;
            $drivers->tot_outstand = sprintf("%0.2f", array_sum(array_pluck($drivers->DriverAccount, 'amount')) + $drivers->outstand_amount);
            $drivers->total_earnings = sprintf("%0.2f", $drivers->total_earnings);
            $drivers->total_comany_earning = sprintf("%0.2f", $drivers->total_comany_earning);
        });
        $csvExporter->build(
            $drivers,
            [
                'driver' => trans("$string_file.driver_details"),
                'area' => trans("$string_file.area"),
                'out_bill' => trans('admin.message463'),
                'unbill_amount' => trans('admin.message464'),
                'tot_outstand' => trans('admin.message465'),
                'total_trips' => trans('admin.message277'),
                'total_earnings' => trans("$string_file.earning"),
                'total_comany_earning' => trans('admin.message283'),
                'wallet_money' => trans("$string_file.wallet_money"),
                'created_at' => trans("$string_file.registered_date"),
            ]
        )->download('Driver_Accounts_' . time() . '.csv');
    }

    public function DriverBills($id)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $driver = Driver::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $bills = DriverAccount::where([['merchant_id', '=', $merchant_id], ['driver_id', '=', $id]])->oldest()->get();
        if ($bills->isEmpty()) :
            return redirect()->back()->withErrors('No data');
        endif;
        $csvExporter = new \Laracsv\Export();
        $csvExporter->beforeEach(function ($bills, $string_file) {
            $bills->bill_period = $bills->from_date . " To " . $bills->to_date;
            if ($bills->status == 1) :
                $bills->status = trans("$string_file.un_settled");
            else :
                $bills->status = trans("$string_file.settled");
            endif;
            $bills->created_by = $bills->CreateBy->merchantFirstName . " (" . $bills->CreateBy->merchantPhone . ") (" . $bills->CreateBy->email . ")";
            if ($bills->settle_type) :
                if ($bills->settle_type == 1) :
                    $bills->settle_type = trans("$string_file.cash");
                else :
                    $bills->settle_type = trans("$string_file.non_cash");
                endif;
            else :
                $bills->settle_type = "----";
            endif;
            if ($bills->settle_by) :
                $bills->settle_by = $bills->SettleBy->merchantFirstName . " (" . $bills->SettleBy->merchantPhone . ") (" . $bills->SettleBy->email . ")";
            else :
                $bills->settle_by = "----";
            endif;
        });
        $csvExporter->build(
            $bills,
            [
                'created_at' => trans('admin.message471'),
                'bill_period' => trans('admin.message472'),
                'amount' => trans('admin.message275'),
                'status' => trans('admin.message474'),
                'created_by' => trans('admin.message473'),
                'referance_number' => trans('admin.message478'),
                'settle_type' => trans('admin.message477'),
                'settle_by' => trans('admin.message475'),
                'settle_date' => trans('admin.message476'),
            ]
        )->download($driver->fullName . '_Bill_' . time() . '.csv');
    }

    public function RideNow(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $url_slug = isset($request->url_slug) ? $request->url_slug : NULL;
        $ridenow = $this->ActiveBookingNow(false, "MERCHANT", $url_slug)->get();
        $booking_status = $this->getBookingStatus($string_file);
        if ($ridenow->isEmpty()) :
            return redirect()->back()->withErrors('noridenowexport', 'No Ride data');
        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($ridenow) use ($booking_status) {
        //            $ridenow->user_id = $ridenow->User->UserName;
        //            $ridenow->driver_id = $ridenow->Driver ? $ridenow->Driver->fullName : trans('admin.message273');
        //            $ridenow->payment_method_id = $ridenow->PaymentMethod->payment_method;
        //            $ridenow->booking_status = isset($booking_status[$ridenow->booking_status]) ? $booking_status[$ridenow->booking_status] : "";
        //            $ridenow->country_area_id = $ridenow->CountryArea->CountryAreaName;
        //            $ridenow->service_type_id = $ridenow->ServiceType->serviceName;
        //            $ridenow->vehicle_type_id = $ridenow->VehicleType->VehicleTypeName;
        //
        //        });
        //        $csvExporter->build($ridenow,
        //            [
        //                'id' => trans("$string_file.ride_id"),
        //                'user_id' => trans("$string_file.user_name"),
        //                'driver_id' => trans("$string_file.name"),
        //                'pickup_location' => trans("$string_file.pickup_location"),
        //                'drop_location' => trans("$string_file.drop_off_location"),
        //                'estimate_bill' => trans("$string_file.estimate_bill"),
        //                'estimate_distance' => trans('admin.message274'),
        //                'payment_method_id' => trans("$string_file.payment_method"),
        //                'booking_status' => trans("$string_file.ride_status"),
        //                'country_area_id' => trans("$string_file.service_area") ,
        //                'service_type_id' => trans("$string_file.service_type"),
        //                'vehicle_type_id' => trans("$string_file.vehicle_type"),
        //                'created_at' => trans("$string_file.date"),
        //
        //            ])->download('ridenow_' . time() . '.csv');

        $export = [];
        foreach ($ridenow as $ride) {
            $ride->user_id = $ride->User->UserName;
            $ride->driver_id = $ride->Driver ? $ride->Driver->fullName : trans('admin.message273');
            $ride->payment_method_id = $ride->PaymentMethod->payment_method;
            $ride->booking_status = isset($booking_status[$ride->booking_status]) ? $booking_status[$ride->booking_status] : "";
            $ride->country_area_id = $ride->CountryArea->CountryAreaName;
            $ride->service_type_id = $ride->ServiceType->serviceName;
            $ride->vehicle_type_id = $ride->VehicleType->VehicleTypeName;

            array_push($export, array(
                $ride->id,
                $ride->user_id,
                $ride->driver_id,
                $ride->pickup_location,
                $ride->drop_location,
                $ride->estimate_bill,
                $ride->estimate_distance,
                $ride->payment_method_id,
                $ride->booking_status,
                $ride->country_area_id,
                $ride->service_type_id,
                $ride->vehicle_type_id,
                $ride->created_at,
            ));
        }
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.user_name"),
            trans("$string_file.name"),
            trans("$string_file.pickup_location"),
            trans("$string_file.drop_off_location"),
            trans("$string_file.estimate_bill"),
            trans("$string_file.estimate_distance"),
            trans("$string_file.payment_method"),
            trans("$string_file.ride_status"),
            trans("$string_file.service_area"),
            trans("$string_file.service_type"),
            trans("$string_file.vehicle_type"),
            trans("$string_file.date")
        );
        $file_name = 'ridenow_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function RideLater()
    {
        $ridelater = $this->ActiveBookingLater(false)->get();
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        if ($ridelater->isEmpty()) :
            return redirect()->back()->withErrors('No Ride data');
        endif;
        $csvExporter = new \Laracsv\Export();
        $csvExporter->beforeEach(function ($ridelater) use ($string_file) {
            $ridelater->user_id = $ridelater->User->UserName;
            $ridelater->driver_id = $ridelater->Driver ? $ridelater->Driver->fullName : trans('admin.message273');
            $ridelater->payment_method_id = $ridelater->PaymentMethod->payment_method;
            if ($ridelater->booking_status == 1001) {
                $ridelater->booking_status = trans('admin.message38');
            } elseif ($ridelater->booking_status == 1012) {
                $ridelater->booking_status = trans("$string_file.partial_accepted");
            } elseif ($ridelater->booking_status == 1002) {
                $ridelater->booking_status = trans('admin.driver_accepted');
            } elseif ($ridelater->booking_status == 1003) {
                $ridelater->booking_status = trans('admin.driver_arrived');
            } elseif ($ridelater->booking_status == 1004) {
                $ridelater->booking_status = trans('admin.begin');
            }

            $ridelater->country_area_id = $ridelater->CountryArea->CountryAreaName;
            $ridelater->service_type_id = $ridelater->ServiceType->serviceName;
            $ridelater->vehicle_type_id = $ridelater->VehicleType->VehicleTypeName;
        });
        $csvExporter->build(
            $ridelater,
            [
                'id' => trans("$string_file.ride_id"),
                'user_id' => trans("$string_file.user_name"),
                'driver_id' => trans("$string_file.driver"),
                'pickup_location' => trans("$string_file.pickup_location"),
                'drop_location' => trans("$string_file.drop_off_location"),
                'estimate_bill' => trans("$string_file.estimate_bill"),
                'estimate_distance' => trans('admin.message274'),
                'payment_method_id' => trans("$string_file.payment_method"),
                'created_at' => trans("$string_file.date"),
                'booking_status' => trans("$string_file.ride_status"),
                'country_area_id' => trans("$string_file.service_area"),
                'service_type_id' => trans("$string_file.service_type"),
                'vehicle_type_id' => trans("$string_file.vehicle_type"),
            ]
        )->download('ridelater_' . time() . '.csv');
    }

    public function RideComplete(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $url_slug = isset($request->url_slug) ? $request->url_slug : NULL;
        //        $ridecomplete = $this->bookings(false, [1005], 'MERCHANT', $url_slug)->get();
        $request->merge(['request_from' => "COMPLETE", 'arr_booking_status' => $request->arr_booking_status, 'url_slug' => $request->url_slug]);
        $ridecomplete = $this->getBookings($request, false, 'MERCHANT');
        //        if ($ridecomplete->isEmpty()):
        //            return redirect()->back()->with('noridecompleteexport', 'No Ride data');
        //        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($ridecomplete) {
        //            $ridecomplete->user_id = $ridecomplete->User->UserName;
        //            $ridecomplete->driver_id = $ridecomplete->Driver->fullName;
        //            $ridecomplete->country_area_id = $ridecomplete->CountryArea->CountryAreaName;
        //            $ridecomplete->service_type_id = $ridecomplete->ServiceType->serviceName;
        //            $ridecomplete->vehicle_type_id = $ridecomplete->VehicleType->VehicleTypeName;
        //            $ridecomplete->payment_method_id = $ridecomplete->PaymentMethod->payment_method;
        //            if ($ridecomplete->booking_type == 1) {
        //                $ridecomplete->booking_type = trans('admin.ride_now');
        //            } else {
        //                $ridecomplete->booking_type = trans('admin.ride_later');
        //            }
        //        });
        //        $csvExporter->build($ridecomplete,
        //            [
        //                'id' => trans("$string_file.ride_id"),
        //                'user_id' => trans("$string_file.user_name"),
        //                'driver_id' => trans("$string_file.name"),
        //                'pickup_location' => trans("$string_file.pickup_location"),
        //                'drop_location' => trans("$string_file.drop_off_location"),
        //                'booking_type' => trans("$string_file.ride_type"),
        //                'final_amount_paid' => trans('admin.message448'),
        //                'country_area_id' => trans("$string_file.service_area") ,
        //                'service_type_id' => trans("$string_file.service_type"),
        //                'vehicle_type_id' => trans("$string_file.vehicle_type"),
        //                'payment_method_id' => trans("$string_file.payment_method"),
        //                'created_at' => trans("$string_file.date"),
        //            ])->download('ridecomplete_' . time() . '.csv');

        $export = [];
        foreach ($ridecomplete as $ride) {
            $ride->user_id = $ride->User->UserName;
            $ride->driver_id = $ride->Driver->fullName;
            $ride->country_area_id = $ride->CountryArea->CountryAreaName;
            $ride->service_type_id = $ride->ServiceType->serviceName;
            $ride->vehicle_type_id = $ride->VehicleType->VehicleTypeName;
            $ride->payment_method_id = $ride->PaymentMethod->payment_method;
            if ($ride->booking_type == 1) {
                $ride->booking_type = trans('admin.ride_now');
            } else {
                $ride->booking_type = trans('admin.ride_later');
            }

            array_push($export, array(
                $ride->id,
                $ride->user_id,
                $ride->driver_id,
                $ride->pickup_location,
                $ride->drop_location,
                $ride->booking_type,
                $ride->final_amount_paid,
                $ride->country_area_id,
                $ride->service_type_id,
                $ride->vehicle_type_id,
                $ride->payment_method_id,
                $ride->created_at,
            ));
        }
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.user_name"),
            trans("$string_file.name"),
            trans("$string_file.pickup_location"),
            trans("$string_file.drop_off_location"),
            trans("$string_file.ride_type"),
            trans("$string_file.final_amount_paid"),
            trans("$string_file.service_area"),
            trans("$string_file.service_type"),
            trans("$string_file.vehicle_type"),
            trans("$string_file.payment_method"),
            trans("$string_file.date")
        );
        $file_name = 'ridecomplete_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function CancelledRide(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $url_slug = isset($request->url_slug) ? $request->url_slug : NULL;
        $ridecancel = $this->bookings(false, [1006, 1007, 1008], 'MERCHANT', $url_slug)->get();
        if ($ridecancel->isEmpty()) :
            return redirect()->back()->with('noridecancelexport', 'No Ride data');
        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($ridecancel) use($string_file,$merchant_id) {
        //            $ridecancel->user_id = $ridecancel->User->UserName;
        //            $ridecancel->vehicle_type_id = $ridecancel->VehicleType->VehicleTypeName;
        //            $ridecancel->service_type_id = $ridecancel->ServiceType->ServiceName($merchant_id);
        //            $ridecancel->country_area_id = $ridecancel->CountryArea->CountryAreaName;
        //            $driver = "";
        //            if (!empty($ridecancel->Driver)) {
        //                $driver = $ridecancel->Driver->fullName;
        //            }
        //            $ridecancel->driver_id = $driver;
        //            switch ($ridecancel->booking_status) {
        //                case(1006):
        //                    $ridecancel->booking_status = trans("$string_file.ride_cancelled_by_user");
        //                    break;
        //                case(1007):
        //                    $ridecancel->booking_status = trans("$string_file.ride_cancelled_by_driver");
        //                    break;
        //                case(1008):
        //                    $ridecancel->booking_status =trans("$string_file.ride_cancelled_by_admin");
        //                    break;
        //            }
        //            if ($ridecancel->booking_type == 1) {
        //                $ridecancel->booking_type = trans("$string_file.now");
        //            } else {
        //                $ridecancel->booking_type = trans("$string_file.later");
        //            }
        //            $ridecancel->cancel_reason_id = $ridecancel->CancelReason->ReasonName;
        //        });
        //        $csvExporter->build($ridecancel,
        //            [
        //                'id' => trans("$string_file.ride_id"),
        //                'user_id' => trans("$string_file.user_name"),
        //                'driver_id' => trans("$string_file.name"),
        //                'pickup_location' => trans("$string_file.pickup_location"),
        //                'drop_location' => trans("$string_file.drop_off_location"),
        //                'booking_type' => trans("$string_file.ride_type"),
        //                'booking_status' => trans('admin.message450'),
        //                'country_area_id' => trans("$string_file.service_area") ,
        //                'service_type_id' => trans("$string_file.service_type"),
        //                'vehicle_type_id' => trans("$string_file.vehicle_type"),
        //                'cancel_reason_id' => trans('admin.message30'),
        //                'created_at' => trans("$string_file.date"),
        //            ])->download('ridecancelled_' . time() . '.csv');

        $export = [];
        foreach ($ridecancel as $ride) {
            $ride->user_id = $ride->User->UserName;
            $ride->vehicle_type_id = $ride->VehicleType->VehicleTypeName;
            $ride->service_type_id = $ride->ServiceType->ServiceName($merchant_id);
            $ride->country_area_id = $ride->CountryArea->CountryAreaName;
            $ride->driver_id = !empty($ride->Driver) ? $ride->Driver->fullName : "";
            switch ($ride->booking_status) {
                case (1006):
                    $ride->booking_status = trans("$string_file.ride_cancelled_by_user");
                    break;
                case (1007):
                    $ride->booking_status = trans("$string_file.ride_cancelled_by_driver");
                    break;
                case (1008):
                    $ride->booking_status = trans("$string_file.ride_cancelled_by_admin");
                    break;
            }
            $ride->booking_type = ($ride->booking_type == 1) ? trans("$string_file.now") : trans("$string_file.later");
            $ride->cancel_reason_id = $ride->CancelReason->ReasonName;

            array_push($export, array(
                $ride->id,
                $ride->user_id,
                $ride->driver_id,
                $ride->pickup_location,
                $ride->drop_location,
                $ride->booking_type,
                $ride->booking_status,
                $ride->country_area_id,
                $ride->service_type_id,
                $ride->vehicle_type_id,
                $ride->cancel_reason_id,
                $ride->created_at,
            ));
        }
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.user_name"),
            trans("$string_file.name"),
            trans("$string_file.pickup_location"),
            trans("$string_file.drop_off_location"),
            trans("$string_file.ride_type"),
            trans("$string_file.status"),
            trans("$string_file.service_area"),
            trans("$string_file.service_type"),
            trans("$string_file.vehicle_type"),
            trans("$string_file.cancel_reason"),
            trans("$string_file.date"),
        );
        $file_name = 'ridecancelled_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function FailedRide(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $ridefailed = $this->failsBookings(false)->get();
        if ($ridefailed->isEmpty()) :
            return redirect()->back()->with('noridefailedexport', 'No Ride data');
        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($ridefailed) {
        //            $ridefailed->user_id = $ridefailed->User->UserName;
        //            $ridefailed->booking_type = ($ridefailed->booking_type == 1) ? trans('admin.ride_now') : trans('admin.ride_later');
        //            $ridefailed->failreason = ($ridefailed->failreason == 1) ? trans('admin.message363') : trans('admin.message364');
        //        });
        //        $csvExporter->build($ridefailed,
        //            [
        //                'id' => trans("$string_file.ride_id"),
        //                'user_id' => trans("$string_file.name"),
        //                'pickup_location' => trans("$string_file.pickup_location"),
        //                'drop_location' => trans("$string_file.drop_off_location"),
        //                'booking_type' => trans("$string_file.ride_type"),
        //                'failreason' => trans('admin.message451'),
        //                'country_area_id' => trans("$string_file.service_area") ,
        //                'service_type_id' => trans("$string_file.service_type"),
        //                'vehicle_type_id' => trans("$string_file.vehicle_type"),
        //                'created_at' => trans("$string_file.date"),
        //            ])->download('ridefailed_' . time() . '.csv');

        $export = [];
        foreach ($ridefailed as $ride) {
            $ridefailed->user_id = $ridefailed->User->UserName;
            $ridefailed->booking_type = ($ridefailed->booking_type == 1) ? trans('admin.ride_now') : trans('admin.ride_later');
            $ridefailed->failreason = ($ridefailed->failreason == 1) ? trans('admin.message363') : trans('admin.message364');

            array_push($export, array(
                $ride->id,
                $ride->user_id,
                $ride->pickup_location,
                $ride->drop_location,
                $ride->booking_type,
                $ride->failreason,
                $ride->country_area_id,
                $ride->service_type_id,
                $ride->vehicle_type_id,
                $ride->created_at,
            ));
        }
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.name"),
            trans("$string_file.pickup_location"),
            trans("$string_file.drop_off_location"),
            trans("$string_file.ride_type"),
            trans("$string_file.reason"),
            trans("$string_file.service_area"),
            trans("$string_file.service_type"),
            trans("$string_file.vehicle_type"),
            trans("$string_file.date"),
        );
        $file_name = 'ridefailed_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function autocancelrides(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $autocancelrides = $this->autoCancelRide(false, [1016])->get();
        //        if ($autocancelrides->isEmpty()):
        //            return redirect()->back()->with('noautocancelrideexport', 'No Ride data');
        //        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($autocancelrides) {
        //            $autocancelrides->user_id = $autocancelrides->User->UserName;
        //            if ($autocancelrides->booking_type == 1) {
        //                $autocancelrides->booking_type = trans('admin.ride_now');
        //            } else {
        //                $autocancelrides->booking_type = trans('admin.ride_later');
        //            }
        //
        //            $autocancelrides->country_area_id = $autocancelrides->CountryArea->CountryAreaName;
        //            $autocancelrides->service_type_id = $autocancelrides->ServiceType->serviceName;
        //            $autocancelrides->vehicle_type_id = $autocancelrides->VehicleType->VehicleTypeName;
        //
        //        });
        //        $csvExporter->build($autocancelrides,
        //            [
        //                'id' => trans("$string_file.ride_id"),
        //                'user_id' => trans("$string_file.name"),
        //                'pickup_location' => trans("$string_file.pickup_location"),
        //                'drop_location' => trans("$string_file.drop_off_location"),
        //                'booking_type' => trans("$string_file.ride_type"),
        //                'created_at' => trans("$string_file.date"),
        //            ])->download('autocancelrides_' . time() . '.csv');

        $export = [];
        foreach ($autocancelrides as $ride) {
            $ride->user_id = $ride->User->UserName;
            $ride->booking_type = ($ride->booking_type == 1) ? trans('admin.ride_now') : trans('admin.ride_later');
            $ride->country_area_id = $ride->CountryArea->CountryAreaName;
            $ride->service_type_id = $ride->ServiceType->serviceName;
            $ride->vehicle_type_id = $ride->VehicleType->VehicleTypeName;

            array_push($export, array(
                $ride->id,
                $ride->user_id,
                $ride->pickup_location,
                $ride->drop_location,
                $ride->booking_type,
                $ride->created_at,
            ));
        }
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.name"),
            trans("$string_file.pickup_location"),
            trans("$string_file.drop_off_location"),
            trans("$string_file.ride_type"),
            trans("$string_file.date"),
        );
        $file_name = 'autocancelrides_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function allRides(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $request->merge(['request_from' => "ALL", 'arr_booking_status' => $request->arr_booking_status, 'url_slug' => $request->url_slug]);
        $allrides = $this->getBookings($request, false, 'MERCHANT');
        $booking_status = $this->getBookingStatus($string_file);
        if ($allrides->isEmpty()) :
            return redirect()->back()->withErrors('No Ride data');
        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($allrides) use($booking_status) {
        //            $allrides->user_id = $allrides->User->user_merchant_id;
        //            $allrides->driver_id = !empty($allrides->driver_id) ? $allrides->Driver->merchant_driver_id : NULL;
        //            $allrides->user_name = $allrides->User->UserName;
        //            $allrides->driver_name = !empty($allrides->diver_id) ? $allrides->Driver->fullName : trans('admin.message273');
        //            $allrides->payment_method_id = $allrides->PaymentMethod->payment_method;
        //            $allrides->booking_status = isset($booking_status[$allrides->booking_status]) ? $booking_status[$allrides->booking_status] : "";
        //            $allrides->country_area_id = $allrides->CountryArea->CountryAreaName;
        //            $allrides->service_type_id = $allrides->ServiceType->serviceName;
        //            $allrides->vehicle_type_id = $allrides->VehicleType->VehicleTypeName;
        //
        //        });
        //        $csvExporter->build($allrides,
        //            [
        //                'id' => trans("$string_file.ride_id"),
        //                'merchant_booking_id' => trans("$string_file.ride_id"),
        //                'user_id' => trans("$string_file.user_id"),
        //                'driver_id' => trans("$string_file.driver_id"),
        //                'user_name' => trans("$string_file.user_name"),
        //                'driver_name' => trans("$string_file.name"),
        //                'pickup_location' => trans("$string_file.pickup_location"),
        //                'drop_location' => trans("$string_file.drop_off_location"),
        //                'payment_method_id' => trans("$string_file.payment_method"),
        //                'booking_status' => trans("$string_file.ride_status"),
        //                'country_area_id' => trans("$string_file.service_area") ,
        //                'service_type_id' => trans("$string_file.service_type"),
        //                'vehicle_type_id' => trans("$string_file.vehicle_type"),
        //                'created_at' => trans("$string_file.date"),
        //
        //            ])->download('allrides_' . time() . '.csv');

        $export = [];
        foreach ($allrides as $ride) {
            $ride->user_id = $ride->User->user_merchant_id;
            $ride->driver_id = !empty($ride->driver_id) ? $ride->Driver->merchant_driver_id : NULL;
            $ride->user_name = $ride->User->UserName;
            $ride->driver_name = !empty($ride->diver_id) ? $ride->Driver->fullName : trans('admin.message273');
            $ride->payment_method_id = $ride->PaymentMethod->payment_method;
            $ride->booking_status = isset($booking_status[$ride->booking_status]) ? $booking_status[$ride->booking_status] : "";
            $ride->country_area_id = $ride->CountryArea->CountryAreaName;
            $ride->service_type_id = $ride->ServiceType->serviceName;
            $ride->vehicle_type_id = $ride->VehicleType->VehicleTypeName;

            array_push($export, array(
                $ride->id,
                $ride->merchant_booking_id,
                $ride->user_id,
                $ride->driver_id,
                $ride->user_name,
                $ride->driver_name,
                $ride->pickup_location,
                $ride->drop_location,
                $ride->payment_method_id,
                $ride->booking_status,
                $ride->country_area_id,
                $ride->service_type_id,
                $ride->vehicle_type_id,
                $ride->created_at,
            ));
        }
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.ride_id"),
            trans("$string_file.user_id"),
            trans("$string_file.driver_id"),
            trans("$string_file.user_name"),
            trans("$string_file.name"),
            trans("$string_file.pickup_location"),
            trans("$string_file.drop_off_location"),
            trans("$string_file.payment_method"),
            trans("$string_file.ride_status"),
            trans("$string_file.service_area"),
            trans("$string_file.service_type"),
            trans("$string_file.vehicle_type"),
            trans("$string_file.date"),
        );
        $file_name = 'allrides_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function SubAdmin(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $subadmin = Merchant::where([['parent_id', '=', $merchant_id]])->get();

        if ($subadmin->isEmpty()) :
            return redirect()->back()->with('nosubadminexport', 'No data');
        endif;
        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($subadmin) {
        //            $subadmin->merchantFirstName = $subadmin->merchantFirstName . ' ' . $subadmin->merchantLastName;
        //            $subadmin->role = $subadmin->roles->first()->display_name;
        //        });
        //        $csvExporter->build($subadmin,
        //            [
        //                'merchantFirstName' => trans("$string_file.name"),
        //                'email' => trans("$string_file.email"),
        //                'merchantPhone' => trans("$string_file.phone"),
        //                'role' => trans("$string_file.role"),
        //                'created_at' => trans("$string_file.created_at"),
        //            ])->download('Sub_Admins_' . time() . '.csv');

        $export = [];
        foreach ($subadmin as $admin) {
            $admin->merchantFirstName = $admin->merchantFirstName . ' ' . $admin->merchantLastName;
            $admin->role = $admin->roles->first() ? $admin->roles->first()->display_name : '';

            array_push($export, array(
                $admin->merchantFirstName,
                $admin->email,
                $admin->merchantPhone,
                $admin->role,
                $admin->created_at,
            ));
        }
        $heading = array(
            trans("$string_file.name"),
            trans("$string_file.email"),
            trans("$string_file.phone"),
            trans("$string_file.role"),
            trans("$string_file.created_at")
        );
        $file_name = 'sub_admins_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function Transactions(Request $request)
    {
        $merchant = Merchant::find($request->merchant_id);
        $string_file = $this->getStringFile($request->merchant_id);
        $query = $this->getAllTransaction(false);
        if ($request->date) {
            $query->whereDate('created_at', '>=', $request->date);
        }
        if ($request->date1) {
            $query->whereDate('created_at', '<=', $request->date1);
        }
        if ($request->booking_id) {
            $query->where('id', '=', $request->booking_id);
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where(\DB::raw("concat(`first_name`,' ', `last_name`)"), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if ($request->driver) {
            $keyword = $request->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->where(\DB::raw("concat(`first_name`,' ', `last_name`)"), 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        $transactions = $query->get();
        if ($transactions->isEmpty()) :
            return redirect()->back()->with('notransactionsexport', 'No data');
        endif;
        foreach ($transactions as $transaction) {
            $referAmount = 0;
            $companyDiscount = ReferralCompanyDiscount::where('booking_id', $transaction->id)->first();
            if (!empty($companyDiscount)) {
                $referAmount = $referAmount + $companyDiscount->amount;
            }

            $driverDiscount = ReferralDriverDiscount::where('booking_id', $transaction->id)->sum('amount');
            if (!empty($driverDiscount)) {
                $referAmount = $referAmount + $driverDiscount;
            }

            $userDiscount = ReferralUserDiscount::where('booking_id', $transaction->id)->sum('amount');
            if (!empty($userDiscount)) {
                $referAmount = $referAmount + $userDiscount;
            }
            $transaction->referral_discount = $referAmount;
            $transaction->merchant = $merchant;
        }
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($transactions) {
//            $transactions->user_id = isset($transactions->User) ? $transactions->User->first_name . " " . $transactions->User->last_name . " (" . $transactions->User->UserPhone . ") (" . $transactions->User->email . ")" : '';
//            $transactions->driver_id = isset($transactions->Driver) ? $transactions->Driver->first_name . " " . $transactions->Driver->last_name . " (" . $transactions->Driver->phoneNumber . ") (" . $transactions->Driver->email . ")" : '';
//            if ($transactions->booking_type == 1) :
//                $transactions->booking_type = trans("$string_file.now");
//            else :
//                $transactions->booking_type = trans("$string_file.later");
//            endif;
//            $transactions->area = $transactions->CountryArea->CountryAreaName;
//            $transactions->payment_method = $transactions->PaymentMethod->payment_method;
//            $transactions->tot_fare = $transactions->CountryArea->Country->isoCode . " " . $transactions->final_amount_paid;
//            $transactions->promo_dis = $transactions->CountryArea->Country->isoCode . " " . (isset($transactions['BookingTransaction']) ? ($transactions['BookingTransaction']['discount_amount']) : ($transactions['BookingDetail']['promo_discount']));
//            $cutAfterReferral = ($transactions->company_cut ? $transactions->company_cut : 0) - ($transactions->referral_discount ? $transactions->referral_discount : 0);
//            $transactions->company_cut_after_referral = $transactions->CountryArea->Country->isoCode . " " . $cutAfterReferral;
//            $transactions->company_cut = $transactions->CountryArea->Country->isoCode . " " . ($transactions->company_cut ? $transactions->company_cut : 0);
//            $transactions->driver_cut = $transactions->CountryArea->Country->isoCode . " " . $transactions->driver_cut;
//            $transactions->estimate_bill = $transactions->CountryArea->Country->isoCode . " " . $transactions->estimate_bill;
//            $transactions->surge_amount = $transactions->CountryArea->Country->isoCode . " " . ($transactions->surge_amount ? $transactions->surge_amount : 0);
//            $transactions->extra_charges = $transactions->CountryArea->Country->isoCode . " " . ($transactions->extra_charges ? $transactions->extra_charges : 0);
//            $transactions->tip = $transactions->CountryArea->Country->isoCode . " " . ($transactions->tip ? $transactions->tip : 0);
//            $transactions->insurance_amount = $transactions->CountryArea->Country->isoCode . " " . ($transactions->insurance_amount ? $transactions->insurance_amount : 0);
//            $transactions->toll_amount = $transactions->CountryArea->Country->isoCode . " " . ($transactions->toll_amount ? $transactions->toll_amount : 0);
//            $transactions->cancellation_charge_applied = $transactions->CountryArea->Country->isoCode . " " . ($transactions->cancellation_charge_applied ? $transactions->cancellation_charge_applied : 0);
//            $transactions->cancellation_charge_received = $transactions->CountryArea->Country->isoCode . " " . ($transactions->cancellation_charge_received ? $transactions->cancellation_charge_received : 0);
//            $transactions->referral_discount = $transactions->CountryArea->Country->isoCode . " " . ($transactions->referral_discount ? $transactions->referral_discount : 0);
//            $transactions->surge_amount = $transactions->CountryArea->Country->isoCode . " " . $transactions['BookingTransaction']['surge_amount'];
//            $transactions->extra_charges = $transactions->CountryArea->Country->isoCode . " " . $transactions['BookingTransaction']['extra_charges'];
//            $transactions->tip = $transactions->CountryArea->Country->isoCode . " " . $transactions['BookingTransaction']['tip'];
//            $transactions->insurance_amount = $transactions->CountryArea->Country->isoCode . " " . $transactions['BookingTransaction']['insurance_amount'];
//            $transactions->toll_amount = $transactions->CountryArea->Country->isoCode . " " . $transactions['BookingTransaction']['toll_amount'];
//            $transactions->cancellation_charge_applied = $transactions->CountryArea->Country->isoCode . " " . $transactions['BookingTransaction']['cancellation_charge_applied'];
//            $transactions->cancellation_charge_received = $transactions->CountryArea->Country->isoCode . " " . $transactions['BookingTransaction']['cancellation_charge_received'];
//
//            if(isset($transactions->merchant->BookingConfiguration->final_amount_to_be_shown)){
//                $rounded_amount = isset($transactions['BookingTransaction']['rounded_amount']) ? number_format($transactions['BookingTransaction']['rounded_amount'],2) : '0.00';
//                $transactions->round_off = $transactions->CountryArea->Country->isoCode . " " . $rounded_amount;
//            }
//        });
//
//        $basicArray = [
//            'id' => trans("$string_file.ride_id"),
//            'booking_type' => trans("$string_file.ride_type"),
//            'area' => trans("$string_file.service_area"),
//            'user_id' => trans("$string_file.user_details"),
//            'driver_id' => trans("$string_file.driver_details"),
//            'payment_method' => trans("$string_file.payment"),
//            'tot_fare' => trans("$string_file.total_amount"),
//            'promo_dis' => trans("$string_file.promo_discount"),
//            'company_cut' => trans("$string_file.company_earning"),
//            'driver_cut' => trans("$string_file.driver_earning"),
//            'travel_distance' => trans("$string_file.travelled_distance"),
//            'travel_time' => trans("$string_file.travelled_time"),
//            'estimate_bill' => trans("$string_file.estimate_bill"),
//            'referral_discount' => trans("$string_file.referral_discount"),
//            'company_cut_after_referral' => trans('admin.company_cut_after_referral'),
//            'created_at' => trans("$string_file.date"),
//        ];
//
//        if ($merchant->ApplicationConfiguration->sub_charge == 1){
//            $basicArray['surge_amount'] = trans('admin.SubCharge');
//        }
//        if ($merchant->ApplicationConfiguration->time_charges == 1){
//            $basicArray['extra_charges'] = trans('admin.message763');
//        }
//        if ($merchant->ApplicationConfiguration->tip_status == 1){
//            $basicArray['tip'] = trans('admin.tip_charge');
//        }
//        if ($merchant->BookingConfiguration->insurance_enable == 1){
//            $basicArray['insurance_amount'] = trans('admin.insurnce');
//        }
//        if ($merchant->Configuration->toll_api == 1){
//            $basicArray['toll_amount'] = trans("$string_file.toll_charge");
//        }
//        if ($merchant->cancel_charges == 1){
//            $basicArray['cancellation_charge_applied'] = trans('admin.message712');
//            $basicArray['cancellation_charge_received'] = trans('admin.cancel_charges_receive');
//        }
//        if(isset($merchant->BookingConfiguration->final_amount_to_be_shown)){
//            $basicArray['round_off'] = trans('admin.round_off');
//        }
//
//        $csvExporter->build($transactions,$basicArray)->download('Transactions_' . time() . '.csv');

        $export = [];
        foreach($transactions as $transaction){
            $transactions->user_id = isset($transaction->User) ? $transaction->User->first_name." ".$transaction->User->last_name . " (" . $transaction->User->UserPhone . ") (" . $transaction->User->email . ")" : '';
            $transaction->driver_id = isset($transaction->Driver) ? $transaction->Driver->first_name." ".$transaction->Driver->last_name . " (" . $transaction->Driver->phoneNumber . ") (" . $transaction->Driver->email . ")" : '';
            if ($transaction->booking_type == 1):
                $transaction->booking_type = trans("$string_file.now");
            else:
                $transaction->booking_type = trans("$string_file.later");
            endif;
            $transaction->area = $transaction->CountryArea->CountryAreaName;
            $transaction->payment_method = $transaction->PaymentMethod->payment_method;
            $transaction->tot_fare = $transaction->CountryArea->Country->isoCode . " " . $transaction->final_amount_paid;
            $transaction->promo_dis = $transaction->CountryArea->Country->isoCode . " " . (isset($transaction['BookingTransaction']) ? ($transaction['BookingTransaction']['discount_amount']) : ($transaction['BookingDetail']['promo_discount']));
            $cutAfterReferral = ($transaction->company_cut ? $transaction->company_cut : 0) - ($transaction->referral_discount ? $transaction->referral_discount : 0);
            $transaction->company_cut_after_referral = $transaction->CountryArea->Country->isoCode . " " . $cutAfterReferral;
            $transaction->company_cut = $transaction->CountryArea->Country->isoCode . " " . ($transaction->company_cut ? $transaction->company_cut : 0);
            $transaction->driver_cut = $transaction->CountryArea->Country->isoCode . " " . $transaction->driver_cut;
            $transaction->estimate_bill = $transaction->CountryArea->Country->isoCode . " " . $transaction->estimate_bill;
            $transaction->surge_amount = $transaction->CountryArea->Country->isoCode . " " . ($transaction->surge_amount ? $transaction->surge_amount : 0);
            $transaction->extra_charges = $transaction->CountryArea->Country->isoCode . " " . ($transaction->extra_charges ? $transaction->extra_charges : 0);
            $transaction->tip = $transaction->CountryArea->Country->isoCode . " " . ($transaction->tip ? $transaction->tip : 0);
            $transaction->insurance_amount = $transaction->CountryArea->Country->isoCode . " " . ($transaction->insurance_amount ? $transaction->insurance_amount : 0);
            $transaction->toll_amount = $transaction->CountryArea->Country->isoCode . " " . ($transaction->toll_amount ? $transaction->toll_amount : 0);
            $transaction->cancellation_charge_applied = $transaction->CountryArea->Country->isoCode . " " . ($transaction->cancellation_charge_applied ? $transaction->cancellation_charge_applied : 0);
            $transaction->cancellation_charge_received = $transaction->CountryArea->Country->isoCode . " " . ($transaction->cancellation_charge_received ? $transaction->cancellation_charge_received : 0);
            $transaction->referral_discount = $transaction->CountryArea->Country->isoCode . " " . ($transaction->referral_discount ? $transaction->referral_discount : 0);
            $transaction->surge_amount = $transaction->CountryArea->Country->isoCode . " " . $transaction['BookingTransaction']['surge_amount'];
            $transaction->extra_charges = $transaction->CountryArea->Country->isoCode . " " . $transaction['BookingTransaction']['extra_charges'];
            $transaction->tip = $transaction->CountryArea->Country->isoCode . " " . $transaction['BookingTransaction']['tip'];
            $transaction->insurance_amount = $transaction->CountryArea->Country->isoCode . " " . $transaction['BookingTransaction']['insurance_amount'];
            $transaction->toll_amount = $transaction->CountryArea->Country->isoCode . " " . $transaction['BookingTransaction']['toll_amount'];
            $transaction->cancellation_charge_applied = $transaction->CountryArea->Country->isoCode . " " . $transaction['BookingTransaction']['cancellation_charge_applied'];
            $transaction->cancellation_charge_received = $transaction->CountryArea->Country->isoCode . " " . $transaction['BookingTransaction']['cancellation_charge_received'];

            if (isset($transaction->merchant->BookingConfiguration->final_amount_to_be_shown)) {
                $rounded_amount = isset($transaction['BookingTransaction']['rounded_amount']) ? number_format($transaction['BookingTransaction']['rounded_amount'], 2) : '0.00';
                $transaction->round_off = $transaction->CountryArea->Country->isoCode . " " . $rounded_amount;
            }

            array_push($export, array(
                $transaction->id,
                $transaction->booking_type,
                $transaction->area,
                $transaction->user_id,
                $transaction->driver_id,
                $transaction->payment_method,
                $transaction->tot_fare,
                $transaction->promo_dis,
                $transaction->company_cut,
                $transaction->driver_cut,
                $transaction->travel_distance,
                $transaction->travel_time,
                $transaction->estimate_bill,
                $transaction->referral_discount,
                $transaction->company_cut_after_referral,
                $transaction->created_at,
            ));
            if ($merchant->ApplicationConfiguration->sub_charge == 1) {
                array_push($export, $transaction->surge_amount);
            }
            if ($merchant->ApplicationConfiguration->time_charges == 1) {
                array_push($export, $transaction->extra_charges);
            }
            if ($merchant->ApplicationConfiguration->tip_status == 1) {
                array_push($export, $transaction->tip);
            }
            if ($merchant->BookingConfiguration->insurance_enable == 1) {
                array_push($export, $transaction->insurance_amount);
            }
            if ($merchant->Configuration->toll_api == 1) {
                array_push($export, $transaction->toll_amount);
            }
            if ($merchant->cancel_charges == 1) {
                array_push($export, $transaction->cancellation_charge_applied);
                array_push($export, $transaction->cancellation_charge_received);
            }
            if (isset($merchant->BookingConfiguration->final_amount_to_be_shown)) {
                array_push($export, $transaction->round_off);
            }
        }
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.ride_type"),
            trans("$string_file.service_area"),
            trans("$string_file.user_details"),
            trans("$string_file.driver_details"),
            trans("$string_file.payment"),
            trans("$string_file.total_amount"),
            trans("$string_file.promo_discount"),
            trans("$string_file.company_earning"),
            trans("$string_file.driver_earning"),
            trans("$string_file.travelled_distance"),
            trans("$string_file.travelled_time"),
            trans("$string_file.estimate_bill"),
            trans("$string_file.referral_discount"),
            trans('admin.company_cut_after_referral'),
            trans("$string_file.date"),
            trans('admin.SubCharge'),
            trans('admin.message763'),
            trans('admin.tip_charge'),
            trans('admin.insurnce'),
            trans("$string_file.toll_charge"),
            trans('admin.message712'),
            trans('admin.cancel_charges_receive'),
            trans('admin.round_off'),
        );
        $file_name = 'transactions_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function PaymentTransactions()
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $transactions = Transaction::where('merchant_id', $merchant_id)->get();
        if ($transactions->isEmpty()) :
            return redirect()->back()->with('notransactionsexport', 'No data');
        endif;
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($transactions) use ($string_file) {
//            $transactions->payment_option_name = $transactions->PaymentOption->name ?? '-----';
//            switch ($transactions->status):
//                case 1:
//                    $transactions->status = trans("$string_file.user");
//                    break;
//                case 2:
//                    $transactions->status = trans("$string_file.driver");
//                    break;
//                case 3:
//                    $transactions->status = trans("$string_file.booking");
//                    break;
//                default:
//                    $transactions->status = '-----';
//                    break;
//            endswitch;
//            $transactions->user_id = isset($transactions->User) ? $transactions->User->user_merchant_id : '-----';
//            $transactions->user_details = isset($transactions->User) ? $transactions->User->first_name." ".$transactions->User->last_name . " (" . $transactions->User->UserPhone . ") (" . $transactions->User->email . ")" : '-----';
//            $transactions->driver_id = isset($transactions->Driver) ? $transactions->Driver->merchant_driver_id : '-----';
//            $transactions->driver_details = isset($transactions->Driver) ? $transactions->Driver->first_name." ".$transactions->Driver->last_name . " (" . $transactions->Driver->phoneNumber . ") (" . $transactions->Driver->email . ")" : '-----';
//            $transactions->booking_details = isset($transactions->Booking) ? $transactions->Booking->merchant_booking_id : '-----';
//            $transactions->amount =  $transactions->amount ?? '-----';
//            $transactions->payment_mode = $transactions->payment_mode ?? '-----';
//
//            switch ($transactions->request_status):
//                case 1:
//                    $transactions->request_status = trans("$string_file.pending");
//                    break;
//                case 2:
//                    $transactions->request_status = trans("$string_file.successful");
//                    break;
//                case 3:
//                    $transactions->request_status = trans("$string_file.failed");
//                    break;
//                case 4:
//                    $transactions->request_status = trans("$string_file.unknown");
//                    break;
//                default:
//                    $transactions->request_status = '-----';
//                    break;
//            endswitch;
//
//            $transactions->status_message = $transactions->status_message ?? '-----';
//        });
//
//        $basicArray = [
//            'id' => trans("$string_file.ride_id"),
//            'payment_option_name' => trans("$string_file.payment_gateway"),
//            'status' => trans("$string_file.type"),
//            'user_id' => trans("$string_file.user_id"),
//            'user_details' => trans("$string_file.user_details"),
//            'driver_id' => trans("$string_file.driver_id"),
//            'driver_details' => trans("$string_file.driver_details"),
//            'booking_details' => trans("$string_file.booking_details"),
//            'amount' => trans("$string_file.amount"),
//            'payment_transaction_id' => trans("$string_file.transaction_id"),
//            'reference_id' => trans("$string_file.gateway_reference_id"),
//            'payment_mode' => trans("$string_file.payment_method"),
//            'request_status' => trans("$string_file.payment_status"),
//            'status_message' => trans("$string_file.gateway_message"),
//        ];
//
//        $csvExporter->build($transactions,$basicArray)->download('PaymentTransactions_' . time() . '.csv');

        $export = [];
        foreach($transactions as $transaction){
            $transaction->payment_option_name = $transaction->PaymentOption->name ?? '-----';
            switch ($transaction->status):
                case 1:
                    $transaction->status = trans("$string_file.user");
                    break;
                case 2:
                    $transaction->status = trans("$string_file.driver");
                    break;
                case 3:
                    $transaction->status = trans("$string_file.booking");
                    break;
                default:
                    $transaction->status = '-----';
                    break;
            endswitch;
            $transaction->user_id = isset($transaction->User) ? $transaction->User->user_merchant_id : '-----';
            $transaction->user_details = isset($transaction->User) ? $transaction->User->first_name . " " . $transaction->User->last_name . " (" . $transaction->User->UserPhone . ") (" . $transaction->User->email . ")" : '-----';
            $transaction->driver_id = isset($transaction->Driver) ? $transaction->Driver->merchant_driver_id : '-----';
            $transaction->driver_details = isset($transaction->Driver) ? $transaction->Driver->first_name . " " . $transaction->Driver->last_name . " (" . $transaction->Driver->phoneNumber . ") (" . $transaction->Driver->email . ")" : '-----';
            $transaction->booking_details = isset($transaction->Booking) ? $transaction->Booking->merchant_booking_id : '-----';
            $transaction->amount =  $transaction->amount ?? '-----';
            $transaction->payment_mode = $transaction->payment_mode ?? '-----';

            switch ($transaction->request_status):
                case 1:
                    $transaction->request_status = trans("$string_file.pending");
                    break;
                case 2:
                    $transaction->request_status = trans("$string_file.successful");
                    break;
                case 3:
                    $transaction->request_status = trans("$string_file.failed");
                    break;
                case 4:
                    $transaction->request_status = trans("$string_file.unknown");
                    break;
                default:
                    $transaction->request_status = '-----';
                    break;
            endswitch;

            $transaction->status_message = $transaction->status_message ?? '-----';

            array_push($export, array(
                $transaction->id,
                $transaction->payment_option_name,
                $transaction->status,
                $transaction->user_id,
                $transaction->user_details,
                $transaction->driver_id,
                $transaction->driver_details,
                $transaction->booking_details,
                $transaction->amount,
                $transaction->payment_transaction_id,
                $transaction->reference_id,
                $transaction->payment_mode,
                $transaction->request_status,
                $transaction->status_message,
            ));
        }
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.payment_gateway"),
            trans("$string_file.type"),
            trans("$string_file.user_id"),
            trans("$string_file.user_details"),
            trans("$string_file.driver_id"),
            trans("$string_file.driver_details"),
            trans("$string_file.booking_details"),
            trans("$string_file.amount"),
            trans("$string_file.transaction_id"),
            trans("$string_file.gateway_reference_id"),
            trans("$string_file.payment_method"),
            trans("$string_file.payment_status"),
            trans("$string_file.gateway_message"),
        );
        $file_name = 'payment_transactions_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function SosRequests(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $version = 1;
        if($request->query("version") !== null && $request->query("version") == "2"){
            $version = 2;
        }
        $sosrequests = $this->getAllSosRequest(false, $version)->get();
        if ($sosrequests->isEmpty()) :
            return redirect()->back()->with('nososrequestsexport', 'No data');
        endif;
        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($sosrequests,$string_file) {
        //            if ($sosrequests->application == 1):
        //                $sosrequests->application = trans("$string_file.user");
        //            else:
        //                $sosrequests->application = trans("$string_file.driver");
        //            endif;
        //            $sosrequests->user_id = $sosrequests->Booking->User->UserName . ' ( ' . $sosrequests->Booking->User->UserPhone . ' )';
        //            $sosrequests->driver_id = $sosrequests->Booking->Driver->fullName . ' ( ' . $sosrequests->Booking->Driver->phoneNumber . ' )';
        //            $sosrequests->location = 'https://www.google.com/maps/place/' . $sosrequests->latitude . ',' . $sosrequests->longitude . ' ( ' . $sosrequests->latitude . ' ,' . $sosrequests->longitude . ' )';
        //            $sosrequests->area = $sosrequests->Booking->CountryArea->CountryAreaName;
        //            $sosrequests->service_type = $sosrequests->Booking->ServiceType->serviceName;
        //            $sosrequests->booking_time = $sosrequests->Booking->created_at;
        //        });
        //        $csvExporter->build($sosrequests,
        //            [
        //                'id' => trans("$string_file.ride_id"),
        //                'application' => trans("$string_file.application"),
        //                'user_id' => trans("$string_file.user_name"),
        //                'driver_id' => trans("$string_file.driver"),
        //                'area' => trans("$string_file.service_area"),
        //                'service_type' => trans("$string_file.service_type"),
        //                'location' => trans("$string_file.sos_location"),
        //                'number' => trans("$string_file.phone"),
        //                'created_at' => trans("$string_file.created_at"),
        //                'booking_time' => trans("$string_file.date"),
        //            ])->download('SOS_Requests_' . time() . '.csv');

        $export = [];
        foreach ($sosrequests as $request) {
            $request->application = ($request->application == 1) ? trans("$string_file.user") : trans("$string_file.driver");
            $request->user_id = $request->Booking->User->UserName . ' ( ' . $request->Booking->User->UserPhone . ' )';
            $request->driver_id = $request->Booking->Driver->fullName . ' ( ' . $request->Booking->Driver->phoneNumber . ' )';
            $request->location = 'https://www.google.com/maps/place/' . $request->latitude . ',' . $request->longitude . ' ( ' . $request->latitude . ' ,' . $request->longitude . ' )';
            $request->area = $request->Booking->CountryArea->CountryAreaName;
            $request->service_type = $request->Booking->ServiceType->serviceName;
            $request->booking_time = $request->Booking->created_at;

            $export[] = array(
                $request->id,
                $request->application,
                $request->user_id,
                $request->driver_id,
                $request->area,
                $request->service_type,
                $request->location,
                $request->number,
                $request->created_at,
                $request->booking_time
            );
        }
        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.application"),
            trans("$string_file.user_name"),
            trans("$string_file.driver"),
            trans("$string_file.service_area"),
            trans("$string_file.service_type"),
            trans("$string_file.sos_location"),
            trans("$string_file.phone"),
            trans("$string_file.created_at"),
            trans("$string_file.date")
        );
        $file_name = 'sos-requests-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function Ratings()
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $ratings = $this->getAllRating(false)->get();
        if ($ratings->isEmpty()) :
            return redirect()->back()->with('noratingsexport', 'No data');
        endif;
        $csvExporter = new \Laracsv\Export();
        $csvExporter->beforeEach(function ($ratings) {
            if (is_null($ratings->user_rating_points)) :
                $ratings->user_rating_points = "Not Yet";
            endif;
            if (is_null($ratings->driver_rating_points)) :
                $ratings->driver_rating_points = "Not Yet";
            endif;
            if (is_null($ratings->user_comment)) :
                $ratings->user_comment = "Not Yet";
            endif;
            if (is_null($ratings->driver_comment)) :
                $ratings->driver_comment = "Not Yet";
            endif;
            $ratings->user = $ratings->Booking->User->UserName . " (" . $ratings->Booking->User->UserPhone . ") (" . $ratings->Booking->User->email . ")";
            $ratings->driver = $ratings->Booking->Driver->fullName . " (" . $ratings->Booking->Driver->phoneNumber . ") (" . $ratings->Booking->Driver->email . ")";
        });
        $csvExporter->build(
            $ratings,
            [
                'id' => trans("$string_file.ride_id"),
                'user' => trans("$string_file.user_details"),
                'driver' => trans("$string_file.driver_details"),
                'user_rating_points' => trans("$string_file.rating_by_user"),
                'user_comment' => trans("$string_file.user_comments"),
                'driver_rating_points' => trans("$string_file.rating_by_driver"),
                'driver_comment' => trans("$string_file.driver_comments"),
            ]
        )->download('Ratings_' . time() . '.csv');
    }

    public function CustomerSupports(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $customer_supports = CustomerSupport::where([['merchant_id', '=', $merchant_id]])->get();
        //        if ($customer_supports->isEmpty()):
        //            return redirect()->back()->with('nocustomersupportsexport', 'No data');
        //        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($customer_supports) use($string_file) {
        //            if ($customer_supports->application == 1):
        //                $customer_supports->application = "Rider";
        //            endif;
        //            if ($customer_supports->application == 2):
        //                $customer_supports->application = "Driver";
        //            endif;
        //        });
        //        $csvExporter->build($customer_supports,
        //            [
        //                'application' => trans("$string_file.application"),
        //                'name' => trans("$string_file.name"),
        //                'email' => trans("$string_file.email"),
        //                'phone' => trans('admin.message306'),
        //                'query' => trans('admin.message380'),
        //                'created_at' => trans('admin.created_at'),
        //            ])->download('Customer_Supports_' . time() . '.csv');

        $export = [];
        foreach ($customer_supports as $customer_support) {
            if ($customer_supports->application == 1) :
                $customer_supports->application = "Rider";
            endif;
            if ($customer_supports->application == 2) :
                $customer_supports->application = "Driver";
            endif;

            array_push($export, array(
                $customer_support->application,
                $customer_support->name,
                $customer_support->email,
                $customer_support->phone,
                $customer_support->query,
                $customer_support->created_at,
            ));
        }

        $heading = array(
            trans("$string_file.application"),
            trans("$string_file.name"),
            trans("$string_file.email"),
            trans("$string_file.phone"),
            trans("$string_file.query"),
            trans("$string_file.created_at"),
        );
        $file_name = 'customer_supports_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function PromotionNotifications(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile();
        $query = PromotionNotification::where([['merchant_id', '=', $merchant_id]]);
        if ($request->title) {
            $query->where('title', $request->title);
        }
        if ($request->application) {
            $query->where('application', $request->application);
        }
        if ($request->date) {
            $query->whereDate('created_at', '=', $request->date);
        }
        $promotions = $query->get();
        if ($promotions->isEmpty()) :
            return redirect()->back()->with('nopromotionnotificationsexport', 'No data');
        endif;
        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($promotions) {
        //            if ($promotions->application == 2):
        //                $promotions->application = "Rider";
        //                if ($promotions->user_id == 0):
        //                    $promotions->user_id = "All Rider";
        //                    $promotions->driver_id = "-----";
        //                else:
        //                    $promotions->user_id = $promotions->User->UserName . " (" . $promotions->User->UserPhone . ") (" . $promotions->User->email . ")";
        //                    $promotions->driver_id = "-----";
        //                endif;
        //            endif;
        //            if ($promotions->application == 1):
        //                $promotions->application = "Driver";
        //                if ($promotions->driver_id == 0):
        //                    $promotions->driver_id = "All Driver";
        //                    $promotions->user_id = "-----";
        //                else:
        //                    $promotions->driver_id = $promotions->Driver->fullName . " (" . $promotions->Driver->phoneNumber . ") (" . $promotions->Driver->email . ")";
        //                    $promotions->user_id = "-----";
        //                endif;
        //            endif;
        //            if ($promotions->country_area_id):
        //                $promotions->country_area_id = $promotions->CountryArea->CountryAreaName;
        //            endif;
        //            if ($promotions->show_promotion == 1):
        //                $promotions->show_promotion = "Yes";
        //            else:
        //                $promotions->show_promotion = "No";
        //            endif;
        //        });
        //        $csvExporter->build($promotions,
        //            [
        //                'country_area_id' => trans("$string_file.service_area"),
        //                'title' => trans("$string_file.title"),
        //                'message' => trans("$string_file.message"),
        //                'url' => trans("$string_file.url"),
        //                'application' => trans("$string_file.application"),
        //                'user_id' => trans("$string_file.user_name"),
        //                'driver_id' => trans("$string_file.driver"),
        //                'created_at' => trans("$string_file.created_at"),
        //                'expiry_date' => trans("$string_file.expiry_date"),
        //            ])->download('Promotion_Notifications_' . time() . '.csv');

        $export = [];
        foreach ($promotions as $promotion) {
            if ($promotion->application == 2) :
                $promotion->application = "Rider";
                if ($promotion->user_id == 0) :
                    $promotion->user_id = "All Rider";
                    $promotion->driver_id = "-----";
                else :
                    $promotion->user_id = $promotion->User->UserName . " (" . $promotion->User->UserPhone . ") (" . $promotion->User->email . ")";
                    $promotion->driver_id = "-----";
                endif;
            endif;
            if ($promotion->application == 1) :
                $promotion->application = "Driver";
                if ($promotion->driver_id == 0) :
                    $promotion->driver_id = "All Driver";
                    $promotion->user_id = "-----";
                else :
                    $promotion->driver_id = $promotion->Driver->fullName . " (" . $promotion->Driver->phoneNumber . ") (" . $promotion->Driver->email . ")";
                    $promotion->user_id = "-----";
                endif;
            endif;
            if ($promotion->country_area_id) :
                $promotion->country_area_id = $promotion->CountryArea->CountryAreaName;
            endif;

            array_push($export, array(
                $promotion->country_area_id,
                $promotion->title,
                $promotion->message,
                $promotion->url,
                $promotion->application,
                $promotion->user_id,
                $promotion->driver_id,
                $promotion->created_at,
                $promotion->expiry_date,
            ));
        }

        $heading = array(
            trans("$string_file.service_area"),
            trans("$string_file.title"),
            trans("$string_file.message"),
            trans("$string_file.url"),
            trans("$string_file.application"),
            trans("$string_file.user_name"),
            trans("$string_file.driver"),
            trans("$string_file.created_at"),
            trans("$string_file.expiry_date")
        );
        $file_name = 'promotion_notifications_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function countriesExport()
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $countries = Country::where([['merchant_id', '=', $merchant_id]])->get();
        if ($countries->isEmpty()) :
            return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
        endif;
        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($countries) use($string_file) {
        //            $countries->name = $countries->LanguageCountryAny->name;
        //            if ($countries->distance_unit == 1) {
        //                $countries->distance_unit = trans("$string_file.km");
        //            } elseif ($countries->distance_unit == 2) {
        //                $countries->distance_unit = trans("$string_file.miles");
        //            }
        //        });
        //        $csvExporter->build($countries,
        //            [
        //                'name' => trans("$string_file.country"),
        //                'phonecode' => trans("$string_file.isd_code"),
        //                'isoCode' => trans("$string_file.iso_code"),
        //                'distance_unit' => trans("$string_file.distance_unit"),
        //
        //            ])->download('countries_' . time() . '.csv');

        $export = [];
        foreach ($countries as $country) {
            $country->name = $country->LanguageCountryAny->name;
            if ($country->distance_unit == 1) {
                $country->distance_unit = trans("$string_file.km");
            } elseif ($country->distance_unit == 2) {
                $country->distance_unit = trans("$string_file.miles");
            }

            array_push($export, array(
                $country->name,
                $country->phonecode,
                $country->isoCode,
                $country->distance_unit,
            ));
        }

        $heading = array(
            trans("$string_file.country"),
            trans("$string_file.isd_code"),
            trans("$string_file.iso_code"),
            trans("$string_file.distance_unit"),
        );
        $file_name = 'countries_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function ServiceAreaManagement()
    {
        $areas = $this->getAreaList(false, true);
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $areas = $areas->get();
        if ($areas->isEmpty()) :
            return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
        endif;
        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($areas) use($string_file) {
        //            $areas->coun = $areas->country->CountryName;
        //            $areas->area = $areas->CountryAreaName;
        //            $a = array();
        //            foreach ($areas->documents as $document):
        //                $a[] = $document->DocumentName;
        //            endforeach;
        //            $areas->doc_name = implode(',', $a);
        //
        //            $arr_segment = array();
        //            foreach ($areas->Segment as $segment):
        //                $arr_segment[] = $segment->Name();
        //            endforeach;
        //            $areas->segment = implode(',', $arr_segment);
        //            $areas->area_type = $areas->is_geofence == 1 ? "Geofence Area" : trans("$string_file.service_area");
        //
        //        });
        //        $csvExporter->build($areas,
        //            [
        //                'area' => trans("$string_file.service_area_name") ,
        //                'coun' => trans("$string_file.country_name") ,
        //                'segment' => trans("$string_file.segment"),
        //                'doc_name' => trans("$string_file.personal_document"),
        //                'area_type' => trans("$string_file.area_type"),
        //                'timezone' => trans("$string_file.timezone"),
        //                'minimum_wallet_amount' => trans("$string_file.minimum_wallet_amount"),
        //            ])->download('Service_Area_Management_' . time() . '.csv');

        $export = [];
        foreach ($areas as $area) {
            $area->coun = $area->country->CountryName;
            $area->area = $area->CountryAreaName;
            $a = array();
            foreach ($area->documents as $document) :
                $a[] = $document->DocumentName;
            endforeach;
            $area->doc_name = implode(',', $a);

            $arr_segment = array();
            foreach ($area->Segment as $segment) :
                $arr_segment[] = $segment->Name();
            endforeach;

            $area->segment = implode(',', $arr_segment);
            $area->area_type = $area->is_geofence == 1 ? "Geofence Area" : trans("$string_file.service_area");

            array_push($export, array(
                $area->area,
                $area->coun,
                $area->segment,
                $area->doc_name,
                $area->area_type,
                $area->timezone,
                $area->minimum_wallet_amount,
            ));
        }

        $heading = array(
            trans("$string_file.service_area_name"),
            trans("$string_file.country_name"),
            trans("$string_file.segment"),
            trans("$string_file.personal_document"),
            trans("$string_file.area_type"),
            trans("$string_file.timezone"),
            trans("$string_file.minimum_wallet_amount"),
        );
        $file_name = 'Service_Area_Management_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function BookingReport(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $query = $this->bookings(false, ['1005']);
        if ($request->booking_id) {
            $query->where('merchant_booking_id', $request->booking_id);
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->whereRaw("CONCAT(`first_name`, `last_name`) LIKE ? ", "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if ($request->driver) {
            $keyword = $request->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->whereRaw("CONCAT(`first_name`, `last_name`) LIKE ? ", "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }
        $bookings = $query->get();
        if ($bookings->isEmpty()) :
            return redirect()->back()->with('nobookingsexport', 'No data');
        endif;
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($bookings) {
//            $bookings->rider = $bookings->User->UserName . " (" . $bookings->User->UserPhone . ") (" . $bookings->User->email . ")";
//            $bookings->driver = $bookings->Driver->fullName . " (" . $bookings->Driver->phoneNumber . ") (" . $bookings->Driver->email . ")";
//            $bookings->loc = $bookings->BookingDetail->start_location . " To " . $bookings->BookingDetail->end_location;
//        });
//        $csvExporter->build($bookings,
//            [
//                'id' => trans("$string_file.id"),
//                'rider' => trans("$string_file.user_details"),
//                'driver' => trans("$string_file.driver_details"),
//                'loc' => trans("$string_file.ride_location"),
//                'created_at' => trans("$string_file.date"),
//            ])->download('Booking_Report_' . time() . '.csv');

        $export = [];
        foreach($bookings as $booking){
            $booking->rider = $booking->User->UserName . " (" . $booking->User->UserPhone . ") (" . $booking->User->email . ")";
            $booking->driver = $booking->Driver->fullName . " (" . $booking->Driver->phoneNumber . ") (" . $booking->Driver->email . ")";
            $booking->loc = $booking->BookingDetail->start_location . " To " . $booking->BookingDetail->end_location;

            array_push($export, array(
                $booking->id,
                $booking->rider,
                $booking->driver,
                $booking->loc,
                $booking->created_at,
            ));
        }

        $heading = array(
            trans("$string_file.id"),
            trans("$string_file.user_details"),
            trans("$string_file.driver_details"),
            trans("$string_file.ride_location"),
            trans("$string_file.date"),
        );
        $file_name = 'booking_report_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function BookingVarianceReport(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $query = $this->bookings(false, ['1005']);
        if ($request->booking_id) {
            $query->where('merchant_booking_id', $request->booking_id);
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->whereRaw("CONCAT(`first_name`, `last_name`) LIKE ? ", "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if ($request->driver) {
            $keyword = $request->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->whereRaw("CONCAT(`first_name`, `last_name`) LIKE ? ", "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }
        $bookings = $query->get();
        if ($bookings->isEmpty()) :
            return redirect()->back()->with('nobookingsexport', 'No data');
        endif;
        $csvExporter = new \Laracsv\Export();
        $csvExporter->beforeEach(function ($bookings) {
            $bookings->rider = $bookings->User->UserName . " (" . $bookings->User->UserPhone . ") (" . $bookings->User->email . ")";
            $bookings->driver = $bookings->Driver->fullName . " (" . $bookings->Driver->phoneNumber . ") (" . $bookings->Driver->email . ")";
            $bookings->loc = $bookings->BookingDetail->start_location . "  -----------  " . $bookings->BookingDetail->end_location;
            $bookings->travel_time_min = $bookings->travel_time_min . " " . trans("$string_file.min");
            $bookings->estimate_bill = $bookings->CountryArea->Country->isoCode . " " . $bookings->estimate_bill;
            $bookings->final_amount_paid = $bookings->CountryArea->Country->isoCode . " " . $bookings->final_amount_paid;
        });
        $csvExporter->build(
            $bookings,
            [
                'id' => trans("$string_file.id"),
                'rider' => trans("$string_file.user_details"),
                'driver' => trans("$string_file.driver_details"),
                'loc' => trans("$string_file.ride_location"),
                'created_at' => trans("$string_file.date"),
                'estimate_time' => trans("$string_file.estimate_time"),
                'travel_time_min' => trans("$string_file.travelled_time"),
                'estimate_distance' => trans("$string_file.estimated_distance"),
                'travel_distance' => trans("$string_file.travelled_distance"),
                'estimate_bill' => trans("$string_file.estimate_bill"),
                'final_amount_paid' => trans("$string_file.amount_paid"),
            ]
        )->download('Booking_Variance_Report_' . time() . '.csv');
    }

    public function PromoCode(Request $request)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $promocodes = $this->getAllPromoCode(false);
        $promocodes = $promocodes->get();
        if ($promocodes->isEmpty()) :
            return redirect()->back()->with('nopromocodeexport', 'No data');
        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($promocodes) use($string_file) {
        //            $promocodes->area = $promocodes->CountryArea->CountryAreaName;
        //            $b = array();
        //            foreach ($promocodes->ServiceType as $servicetype):
        //                $b[] = $servicetype->serviceName;
        //            endforeach;
        //            $promocodes->service_type = implode(',', $b);
        //            if ($promocodes->promo_code_value_type == 1):
        //                $promocodes->value = $promocodes->CountryArea->Country->isoCode . " " . $promocodes->promo_code_value;
        //            else:
        //                $promocodes->value = $promocodes->promo_code_value . " %";
        //            endif;
        //            if ($promocodes->promo_code_validity == 1):
        //                $promocodes->promo_code_validity = trans("$string_file.permanent");
        //            else:
        //                $promocodes->promo_code_validity = trans("$string_file.custom");
        //            endif;
        //            if ($promocodes->applicable_for == 1):
        //                $promocodes->applicable_for = trans("$string_file.all_users");
        //            elseif ($promocodes->applicable_for == 2):
        //                $promocodes->applicable_for = trans("$string_file.new_users");
        //            else:
        //                $promocodes->applicable_for = trans("$string_file.corporate_users");
        //            endif;
        //            if ($promocodes->promo_code_status == 1):
        //                $promocodes->promo_code_status = trans("$string_file.active");
        //            else:
        //                $promocodes->promo_code_status = trans("$string_file.inactive");
        //            endif;
        //        });
        //        $csvExporter->build($promocodes,
        //            [
        //                'promoCode' => trans("$string_file.promo_code"),
        //                'area' => trans("$string_file.service_area"),
        //                'service_type' => trans("$string_file.service_type"),
        //                'promo_code_description' => trans("$string_file.description"),
        //                'value' => trans("$string_file.discount"),
        //                'promo_code_validity' => trans("$string_file.validity"),
        //                'start_date' => trans("$string_file.start_date"),
        //                'end_date' => trans("$string_file.end_date"),
        //                'promo_code_limit' => trans("$string_file.limit"),
        //                'promo_code_limit_per_user' => trans("$string_file.limit_per_user"),
        //                'applicable_for' => trans("$string_file.applicable"),
        //                'promo_code_status' => trans("$string_file.status"),
        //            ])->download('PromoCode_' . time() . '.csv');

        $export = [];
        foreach ($promocodes as $promocode) {
            $promocode->area = $promocode->CountryArea->CountryAreaName;
            $b = array();
            foreach ($promocode->ServiceType as $servicetype) {
                $b[] = $servicetype->serviceName;
            }
            $promocode->service_type = implode(',', $b);
            $promocode->value = ($promocode->promo_code_value_type == 1) ? $promocode->CountryArea->Country->isoCode . " " . $promocode->promo_code_value : $promocode->promo_code_value . " %";
            $promocode->promo_code_validity = ($promocode->promo_code_validity == 1) ?  trans("$string_file.permanent") : trans("$string_file.custom");

            switch ($promocode->applicable_for) {
                case 1:
                    $promocode->applicable_for = trans("$string_file.all_users");
                    break;
                case 2:
                    $promocode->applicable_for = trans("$string_file.new_users");
                    break;
                default:
                    $promocode->applicable_for = trans("$string_file.corporate_users");
            }

            $promocode->promo_code_status = ($promocode->promo_code_status == 1) ? trans("$string_file.active") : trans("$string_file.inactive");

            array_push($export, array(
                'promoCode' => $promocode->promoCode,
                'area' => $promocode->area,
                'service_type' => $promocode->service_type,
                'promo_code_description' => $promocode->promo_code_description,
                'value' => $promocode->value,
                'promo_code_validity' => $promocode->promo_code_validity,
                'start_date' => $promocode->start_date,
                'end_date' => $promocode->end_date,
                'promo_code_limit' => $promocode->promo_code_limit,
                'promo_code_limit_per_user' => $promocode->promo_code_limit_per_user,
                'applicable_for' => $promocode->applicable_for,
                'promo_code_status' => $promocode->promo_code_status,
            ));
        }

        $heading = array(
            trans("$string_file.promo_code"),
            trans("$string_file.service_area"),
            trans("$string_file.service_type"),
            trans("$string_file.description"),
            trans("$string_file.discount"),
            trans("$string_file.validity"),
            trans("$string_file.start_date"),
            trans("$string_file.end_date"),
            trans("$string_file.limit"),
            trans("$string_file.limit_per_user"),
            trans("$string_file.applicable"),
            trans("$string_file.status")
        );
        $file_name = 'PromoCode_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function PriceCard()
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $pricecards = $this->getPriceList(false);
        $pricecards = $pricecards->get();
        if ($pricecards->isEmpty()) :
            return redirect()->back()->with('nopricecardexport', 'No data');
        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($pricecards) use ($string_file) {
        //            $pricecards->area = $pricecards->CountryArea->CountryAreaName;
        //            $pricecards->service = $pricecards->ServiceType->serviceName;
        //            $pricecards->vehicle = $pricecards->VehicleType->VehicleTypeName;
        //            if (empty($pricecards->package_id)):
        //                $pricecards->service_type_id = "----";
        //            else:
        //                if ($pricecards->service_type_id == 4):
        //                    $pricecards->service_type_id = $pricecards->OutstationPackage->PackageName;
        //                else:
        //                    $pricecards->service_type_id = $pricecards->Package->PackageName;
        //                endif;
        //            endif;
        //            switch ($pricecards->pricing_type):
        //                case 1:
        //                    $pricecards->pricing_type = trans("$string_file.variable");
        //                    break;
        //                case 2:
        //                    $pricecards->pricing_type = trans("$string_file.fixed");
        //                    break;
        //                case 3:
        //                    $pricecards->pricing_type = trans("$string_file.input_by_driver");
        //                    break;
        //            endswitch;
        //            $pricecards->base_fare = $pricecards->CountryArea->Country->isoCode . " " . $pricecards->base_fare;
        //            if ($pricecards->PriceCardCommission):
        //                if ($pricecards->PriceCardCommission->commission_type == 1):
        ////                    $pricecards->commission = trans('admin.prepaid');
        //                else:
        ////                    $pricecards->commission = trans('admin.postpaid');
        //                endif;
        //            else:
        //                $pricecards->commission = "----";
        //            endif;
        //            if ($pricecards->PriceCardCommission):
        //                switch ($pricecards->PriceCardCommission->commission_method):
        //                    case 1:
        //                        $pricecards->commission_method = trans("$string_file.flat");
        //                        break;
        //                    case 2:
        //                        $pricecards->commission_method = trans("$string_file.percentage");
        //                        break;
        //                endswitch;
        //            else:
        //                $pricecards->commission_method = "----";
        //            endif;
        //            if ($pricecards->PriceCardCommission):
        //                switch ($pricecards->PriceCardCommission->commission_method):
        //                    case 1:
        //                        $pricecards->commission_val = $pricecards->CountryArea->Country->isoCode . " " . $pricecards->PriceCardCommission->commission;
        //                        break;
        //                    case 2:
        //                        $pricecards->commission_val = $pricecards->PriceCardCommission->commission . " %";
        //                        break;
        //                endswitch;
        //            else:
        //                $pricecards->commission_val = "----";
        //            endif;
        //            if ($pricecards->sub_charge_status == 1):
        //                $pricecards->sub_charge_status = trans("$string_file.on");
        //            else:
        //                $pricecards->sub_charge_status = trans("$string_file.off");
        //            endif;
        //            if ($pricecards->sub_charge_type == 1):
        //                $pricecards->sub_charge_type = trans("$string_file.nominal");
        //            else:
        //                $pricecards->sub_charge_type = trans("$string_file.multiplier");
        //            endif;
        //            $pricecards->sub_charge_value = $pricecards->CountryArea->Country->isoCode . " " . $pricecards->sub_charge_value;
        //        });
        //        $csvExporter->build($pricecards,
        //            [
        //                'area' => trans("$string_file.service_area"),
        //                'service' => trans("$string_file.service_type"),
        //                'vehicle' => trans("$string_file.vehicle_type"),
        //                'service_type_id' =>trans("$string_file.service_type"),
        //                'pricing_type' => trans('admin.price_type'),
        //                'base_fare' => trans("$string_file.base_fare"),
        //                'commission_method' => trans("$string_file.commission_method"),
        //                'commission_val' => trans("$string_file.commission_value"),
        //                'sub_charge_status' => trans("$string_file.sub_charge_status"),
        //                'sub_charge_type' => trans("$string_file.sub_charge_type"),
        //                'sub_charge_value' => trans("$string_file.sub_charge_value"),
        //            ])->download('PriceCard_' . time() . '.csv');

        $export = [];
        foreach ($pricecards as $pricecard) {
            $pricecard->area = $pricecard->CountryArea->CountryAreaName;
            $pricecard->service = $pricecard->ServiceType->serviceName;
            $pricecard->vehicle = $pricecard->VehicleType->VehicleTypeName;
            if (empty($pricecard->service_package_id)) :
                $pricecard->service_package_id = "----";
            else :
                if ($pricecard->ServiceType->additional_support == 1) :
                    $pricecard->service_package_id = \App\Models\ServicePackage::Find($pricecard->service_package_id)->PackageName;
                else :
                    $pricecard->service_package_id = \App\Models\OutstationPackage::Find($pricecard->service_package_id)->PackageName;
                endif;
            endif;
            switch ($pricecard->pricing_type):
                case 1:
                    $pricecard->pricing_type = trans("$string_file.variable");
                    break;
                case 2:
                    $pricecard->pricing_type = trans("$string_file.fixed");
                    break;
                case 3:
                    $pricecard->pricing_type = trans("$string_file.input_by_driver");
                    break;
            endswitch;
            $pricecard->base_fare = $pricecard->CountryArea->Country->isoCode . " " . $pricecard->base_fare;
            if ($pricecard->PriceCardCommission) :
                switch ($pricecard->PriceCardCommission->commission_method):
                    case 1:
                        $pricecard->commission_method = trans("$string_file.flat");
                        break;
                    case 2:
                        $pricecard->commission_method = trans("$string_file.percentage");
                        break;
                endswitch;
            else :
                $pricecard->commission_method = "----";
            endif;
            if ($pricecard->PriceCardCommission) :
                switch ($pricecard->PriceCardCommission->commission_method):
                    case 1:
                        $pricecard->commission_val = $pricecard->CountryArea->Country->isoCode . " " . $pricecard->PriceCardCommission->commission;
                        break;
                    case 2:
                        $pricecard->commission_val = $pricecard->PriceCardCommission->commission . " %";
                        break;
                endswitch;
            else :
                $pricecard->commission_val = "----";
            endif;
            if ($pricecard->sub_charge_status == 1) :
                $pricecard->sub_charge_status = trans("$string_file.on");
            else :
                // $pricecard->sub_charge_status = trans("$string_file.off");
                $pricecard->sub_charge_status = "N/A";
            endif;
            if ($pricecard->sub_charge_type == 1) :
                $pricecard->sub_charge_type = trans("$string_file.nominal");
            elseif($pricecard->sub_charge_status != 1): 
               $pricecard->sub_charge_type =  "N/A";
            else :
                $pricecard->sub_charge_type = trans("$string_file.multiplier");
            endif;
            if($pricecard->sub_charge_value){
                $pricecard->sub_charge_value = $pricecard->CountryArea->Country->isoCode . " " . $pricecard->sub_charge_value;
            }else{
                $pricecard->sub_charge_value = "N/A";
            }

            array_push($export, array(
                $pricecard->area,
                $pricecard->service,
                $pricecard->vehicle,
                $pricecard->service_package_id,
                $pricecard->pricing_type,
                $pricecard->base_fare,
                $pricecard->commission_method,
                $pricecard->commission_val,
                $pricecard->sub_charge_status,
                $pricecard->sub_charge_type,
                $pricecard->sub_charge_value,
            ));
        }

        $heading = array(
            trans("$string_file.service_area"),
            trans("$string_file.service_type"),
            trans("$string_file.vehicle_type"),
            trans("$string_file.package"),
            trans("$string_file.price_type"),
            trans("$string_file.base_fare"),
            trans("$string_file.commission_method"),
            trans("$string_file.commission_value"),
            trans("$string_file.surcharge_status"),
            trans("$string_file.surcharge_type"),
            trans("$string_file.surcharge_value")
        );
        $file_name = 'PriceCard_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function vehicleTypes(Request $request)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = $merchant->id;
        $vehicle_type = $request->vehicle_type;
        $query = VehicleType::where([['merchant_id', '=', $merchant_id]]);
        if (!empty($vehicle_type)) {
            $query->with(['LanguageVehicleTypeSingle' => function ($q) use ($vehicle_type, $merchant_id) {
                $q->where('vehicleTypeName', $vehicle_type)->where('merchant_id', $merchant_id);
            }])->whereHas('LanguageVehicleTypeSingle', function ($q) use ($vehicle_type, $merchant_id) {
                $q->where('vehicleTypeName', $vehicle_type)->where('merchant_id', $merchant_id);
            });
        }
        $vehicle_types =   $query->where('admin_delete', NULL)->get();
        if ($vehicle_types->isEmpty()) :
            return redirect()->back()->with('novehicletypesexport', 'No Vehicle Types');
        endif;

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($vehicle_types) use($string_file) {
        //            $vehicle_types->name = $vehicle_types->VehicleTypeName;
        //            $vehicle_types->description = $vehicle_types->VehicleTypeDescription;
        //            $vehicle_types->serviceType = ($vehicle_types->DeliveryType) ? $vehicle_types->DeliveryType->name : ' - - - ';
        //            $vehicle_types->pool_enable =  ($vehicle_types->pool_enable == 1) ? trans("$string_file.yes") : trans("$string_file.no");
        //        });
        //        $csvExporter->build($vehicle_types,
        //            [
        //                'name' => trans("$string_file.name"),
        //                'description' => trans("$string_file.description"),
        //                'pool_enable' => trans("$string_file.pool_availability"),
        //
        //            ])->download('vehicle_types_' . time() . '.csv');

        $export = [];
        foreach ($vehicle_types as $vehicle_type) {
            $vehicle_type->name = $vehicle_type->VehicleTypeName;
            $vehicle_type->description = $vehicle_type->VehicleTypeDescription;
            $vehicle_type->serviceType = ($vehicle_type->DeliveryType) ? $vehicle_type->DeliveryType->name : ' - - - ';
            $vehicle_type->pool_enable =  ($vehicle_type->pool_enable == 1) ? trans("$string_file.yes") : trans("$string_file.no");

            array_push($export, array(
                $vehicle_type->name,
                $vehicle_type->description,
                $vehicle_type->pool_enable,
            ));
        }

        $heading = array(
            trans("$string_file.name"),
            trans("$string_file.description"),
            trans("$string_file.pool_availability")
        );
        $file_name = 'vehicle_types_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function Referral(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $ref = new ReferralController();
        $referral_details = $ref->getReferralDiscountExcelData($merchant_id,$request);
        //        $referral_details = ReferralDiscount::where([['merchant_id','=',$merchant_id],['sender_id','!=',0],['sender_type','!=',0]])->groupBy('sender_id')->latest()->get();
        //        foreach ($referral_details as $referral_detail){
        //            $senderDetails = $referral_detail->sender_type == 1 ? User::find($referral_detail->sender_id) : Driver::find($referral_detail->sender_id);
        //            if (!empty($senderDetails)){
        //                $phone = $referral_detail->sender_type == 1 ? $senderDetails->UserPhone : $senderDetails->phoneNumber;
        //                $senderType = $referral_detail->sender_type == 1 ? 'User' : 'Driver';
        //                $referral_detail->sender_details =  $senderDetails->first_name.' '.$senderDetails->last_name.' ('.$phone.') ('. $senderDetails->email.') (Type : '.$senderType.')';
        //                $referReceivers = ReferralDiscount::where([['merchant_id','=',$merchant_id],['sender_id','=',$referral_detail->sender_id]])->latest()->get();
        //                $receiverBasic = array();
        //                foreach ($referReceivers as $referReceiver){
        //                    $receiverDetails = $referReceiver->receiver_type == 1 ? User::find($referReceiver->receiver_id) : Driver::find($referReceiver->receiver_id);
        //                    if (!empty($receiverDetails)){
        //                        $phone = $referReceiver->receiver_type == 1 ? $receiverDetails->UserPhone : $receiverDetails->phoneNumber;
        //                        $receiverType = $referReceiver->receiver_type == 1 ? 'User' : 'Driver';
        //                        $receiverBasic[] =  $receiverDetails->first_name.' '.$receiverDetails->last_name.' ('.$phone.') ('.$receiverDetails->email.') (Type : '.$receiverType.')';
        //                    }
        //                }
        //                $referral_detail->total_refer = count($receiverBasic);
        //                $referral_detail->receiver_details = implode(',',$receiverBasic);
        //            }
        //        }

        if (count($referral_details) == 0) :
            return redirect()->back()->with('notransactionsexport', 'No data');
        endif;
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->build($referral_details, [
//            'sender_details' => trans("$string_file.sender"),
//            'receiver_details' => trans("$string_file.receiver"),
//            'total_refer' => trans('admin.total_refer'),
//            'created_at' => trans("$string_file.date")
//        ])->download('ReferralReports_' . time() . '.csv');
        $export = [];
        foreach($referral_details as $referral_detail){
            // array_push($export, array(
            //     $referral_detail->sender_details,
            //     $referral_detail->receiver_details,
            //     $referral_detail->total_refer,
            //     // $referral_detail->created_at,
            //     convertTimeToUSERzone($referral_detail->created_at,$referral_detail->getReferralSystem->CountryArea->timezone,$merchant->id),
            // ));
            
            array_push($export,array(
                $referral_detail['referral_code'] .'(' .$referral_detail['sender_name'] .')',
                $referral_detail['receiver_name'],
                $referral_detail['receiver_type'],
                $referral_detail['receiver_created_at'],
                $referral_detail['updated_at'],
                $referral_detail['signup_status'],
                $referral_detail['deleted']
                
            ));
        }

        $heading = array(
            // trans("$string_file.sender"),
            // trans("$string_file.receiver"),
            // trans('admin.total_refer'),
            // trans("$string_file.date")
            
              trans("$string_file.referral_code"),
            trans("$string_file.receiver").' '. trans("$string_file.name") .'('. trans("$string_file.phone") .')',
            trans("$string_file.sender_type"),
            trans("$string_file.used_date"),
            trans("$string_file.update_date"),
            trans("$string_file.signup").' '.trans("$string_file.status"),
            trans("$string_file.deleted")
        );
        $file_name = 'referral_reports_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }


    public function DriversWithoutReferral(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $ref = new ReferralController();
        $without_referral_drivers = $ref->getDriversWithoutReferall($merchant_id,$request);
        $heading = array(
            trans("$string_file.name"),
            trans("$string_file.phone")." ".trans("$string_file.number"),
            trans("$string_file.registered_date"),
            trans("$string_file.signup")." ".trans("$string_file.status"),
        );
        $file_name = 'drivers_without_referral_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $without_referral_drivers), $file_name);
    }

    // export earning of bs
    public function businessSegmentEarningExport(Request $request)
    {
        $business_seg = get_business_segment(false);
        $id = $business_seg->id;
        $merchant_id = $business_seg->merchant_id;
        $segment_id = $business_seg->segment_id;
        $string_file = $this->getStringFile($merchant_id);
        // $request->request->add(['status' => 'DELIVERED']);
        $order = new Order;
        $all_orders = $order->getOrders($request);
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($all_orders) use ($string_file) {
//            $additional_amount = "";
//            if(!empty($all_orders->tip_amount))
//            {
//                $additional_amount.=trans("$string_file.tip").' : '.$all_orders->tip_amount;
//            }
//            $all_orders->business_segment_earning = $all_orders->OrderTransaction->business_segment_earning;
//            $all_orders->company_earning = $all_orders->OrderTransaction->company_earning;
//            $all_orders->order_date =  trans("$string_file.at").' '.date('H:i',strtotime($all_orders->created_at)).', '.date_format($all_orders->created_at,'D, M d, Y');
//            $all_orders->additional_charges =  $additional_amount;
//        });
//
//        $csvExporter->build($all_orders,
//            [
//                'merchant_order_id' => trans("$string_file.id"),
//                'business_segment_earning' => trans("$string_file.business_segment_earning"),
//                'company_earning' => trans("$string_file.merchant_earning"),
//                'final_amount_paid' => trans("$string_file.order_amount"),
//                'cart_amount' => trans("$string_file.cart_amount"),
//                'tax' => trans("$string_file.tax"),
//                'delivery_amount' => trans("$string_file.delivery_amount"),
//                'order_date' => trans("$string_file.order_date"),
//            ]
//        )->download('business-segment-earning' . time() . '.csv');

        $export = [];
        foreach($all_orders as $order){
            $additional_amount = "";
            if (!empty($order->tip_amount)) {
                $additional_amount .= trans("$string_file.tip") . ' : ' . $order->tip_amount;
            }
            $order->business_segment_earning = 0.0;
            $order->company_earning = 0.0;
            $order->order_date =  trans("$string_file.at") . ' ' . date('H:i', strtotime($order->created_at)) . ', ' . date_format($order->created_at, 'D, M d, Y');
            $order->additional_charges =  $additional_amount;
            if(!empty($order->OrderTransaction)){
                $order->business_segment_earning = $order->OrderTransaction->business_segment_earning;
                $order->company_earning = $order->OrderTransaction->company_earning;
            }

            array_push($export, array(
                $order->merchant_order_id,
                $order->business_segment_earning,
                $order->company_earning,
                $order->final_amount_paid,
                $order->cart_amount,
                $order->tax,
                $order->delivery_amount,
                $order->order_date,
            ));
        }

        $heading = array(
            trans("$string_file.id"),
            trans("$string_file.business_segment_earning"),
            trans("$string_file.merchant_earning"),
            trans("$string_file.order_amount"),
            trans("$string_file.cart_amount"),
            trans("$string_file.tax"),
            trans("$string_file.delivery_amount"),
            trans("$string_file.order_date"),
        );
        $file_name = 'business_segment_earning' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }


    public function taxiServicesEarningExport(Request $request)
    {
        $request->merge(['request_from' => 'COMPLETE']);
        $arr_rides = $this->getBookings($request, false, 'MERCHANT');
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($arr_rides)  use($string_file){
        //            $arr_rides->driver_earning = $arr_rides->BookingTransaction->driver_earning;
        //            $arr_rides->company_earning = $arr_rides->BookingTransaction->company_earning;
        //            $arr_rides->discount_amount = $arr_rides->BookingTransaction->discount_amount;
        //            $arr_rides->sub_total_before_discount = $arr_rides->final_amount_paid + $arr_rides->BookingTransaction->discount_amount;
        //            $arr_rides->driver_name = $arr_rides->Driver->fullName ?? $arr_rides->Driver->first_name.' '.$arr_rides->Driver->last_name;
        //            $arr_rides->service_area = $arr_rides->CountryArea->CountryAreaName;
        //            $arr_rides->ride_date =  trans("$string_file.at").' '.date('H:i',strtotime($arr_rides->created_at)).', '.date_format($arr_rides->created_at,'D, M d, Y');
        //            $arr_rides->payment_method =  $arr_rides->PaymentMethod->MethodName($arr_rides->merchant_id);
        //            $arr_rides->user_detail =  $arr_rides->User->first_name.' '.$arr_rides->User->last_name;
        //        });
        //
        //        $csvExporter->build($arr_rides,
        //            [
        //                'merchant_booking_id' => trans("$string_file.ride_id"),
        //                'payment_method' => trans("$string_file.payment_method"),
        //                'user_detail' => trans("$string_file.user_details"),
        //                'driver_name' => trans("$string_file.driver_details"),
        //                'driver_earning' => trans("$string_file.driver_earning"),
        //                'company_earning' => trans("$string_file.merchant_earning"),
        //                'sub_total_before_discount' => trans("$string_file.sub_total_before_discount"),
        //                'discount_amount' => trans("$string_file.discount_amount"),
        //                'final_amount_paid' => trans("$string_file.ride_amount"),
        //                'service_area' => trans("$string_file.service_area"),
        //                'ride_date' => trans("$string_file.date"),
        //            ]
        //        )->download('taxi-services-earning' . time() . '.csv');

        $export = [];
        foreach ($arr_rides as $ride) {
           $helperMerchant = new \App\Http\Controllers\Helper\Merchant();
            $ride->driver_earning = !empty($ride->BookingTransaction->driver_earning) ? $helperMerchant->PriceFormat($helperMerchant->TripCalculation($ride->BookingTransaction->driver_earning, $ride->merchant_id),$ride->merchant_id) : 0;
            $ride->company_earning = !empty($ride->BookingTransaction->company_earning) ? $ride->BookingTransaction->company_earning : 0;
            $ride->discount_amount = !empty($ride->BookingTransaction->discount_amount) ? $ride->BookingTransaction->discount_amount : 0;
            $ride->sub_total_before_discount = $ride->final_amount_paid + (!empty($ride->BookingTransaction->discount_amount) ? $ride->BookingTransaction->discount_amount : 0 );
            $ride->driver_name = $ride->Driver->fullName ?? $ride->Driver->first_name . ' ' . $ride->Driver->last_name;
            $ride->service_area = $ride->CountryArea->CountryAreaName;
            $ride->payment_method =  $ride->PaymentMethod->MethodName($ride->merchant_id) ? $ride->PaymentMethod->MethodName($ride->merchant_id) : $ride->PaymentMethod->payment_method;
            $ride->user_detail =  $ride->User->first_name . ' ' . $ride->User->last_name;
            $created_at_string = convertTimeToUSERzone($ride->created_at, $ride->CountryArea->timezone, null, $ride->Merchant);
            $created_at = new \DateTime($created_at_string);
            $ride->ride_date = trans("$string_file.at") . ' ' . $created_at->format('H:i') . ', ' . $created_at->format('D, M d, Y');
            array_push($export, array(
                // $ride->Driver->id,
                $ride->merchant_booking_id,
                $ride->payment_method,
                $ride->user_detail,
                $ride->User->UserPhone,
                $ride->driver_name,
                $ride->Driver->phoneNumber,
                $ride->DriverVehicle->vehicle_number,
                $ride->driver_earning,
                $ride->company_earning,
                $ride->sub_total_before_discount,
                $ride->discount_amount,
                $ride->final_amount_paid,
                $ride->service_area,
                $ride->ride_date,
            ));
        }

        $heading = array(
            trans("$string_file.ride_id"),
            trans("$string_file.payment_method"),
            trans("$string_file.user_details"),
            trans("$string_file.user_phone"),
            trans("$string_file.driver_details"),
            trans("$string_file.driver_phone"),
            trans("$string_file.driver_vehicle_no"),
            trans("$string_file.driver_earning"),
            trans("$string_file.merchant_earning"),
            trans("$string_file.sub_total_before_discount"),
            trans("$string_file.discount_amount"),
            trans("$string_file.ride_amount"),
            trans("$string_file.service_area"),
            trans("$string_file.date"),
        );
        $file_name = 'taxi-services-earning' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    // handyman services
    public function handymanServicesEarningExport(Request $request)
    {

        $handyman = new HandymanOrder;
        $arr_bookings = $handyman->getSegmentOrders($request, false);
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($arr_bookings) use($string_file) {
        //            if(!empty($arr_bookings->HandymanOrderTransaction))
        //            {
        //            $arr_bookings->driver_earning = $arr_bookings->HandymanOrderTransaction->driver_earning;
        //            $arr_bookings->company_earning = $arr_bookings->HandymanOrderTransaction->company_earning;
        //            $arr_bookings->total_booking_amount =  $arr_bookings->final_amount_paid - $arr_bookings->tax;
        //            $arr_bookings->booking_date =  trans("$string_file.at").' '.date('H:i',strtotime($arr_bookings->created_at)).', '.date_format($arr_bookings->created_at,'D, M d, Y');
        //            $arr_bookings->sub_total_before_discount =$arr_bookings->final_amount_paid + $arr_bookings->HandymanOrderTransaction->discount_amount;
        //            $arr_bookings->discount_amount = $arr_bookings->HandymanOrderTransaction->discount_amount;
        //            $arr_bookings->driver_name = $arr_rides->Driver->fullName ?? $arr_bookings->Driver->first_name.' '.$arr_bookings->Driver->last_name;
        //            $arr_bookings->payment_method =  $arr_bookings->PaymentMethod->MethodName($arr_bookings->merchant_id);
        //            $arr_bookings->user_detail =  $arr_bookings->User->first_name.' '.$arr_bookings->User->last_name;
        //            }
        //        });
        //
        //        $csvExporter->build($arr_bookings,
        //            [
        //                'merchant_order_id' => trans("$string_file.booking_id"),
        //                'payment_method' => trans("$string_file.payment_method"),
        //                'user_detail' => trans("$string_file.user_details"),
        //                'driver_name' => trans("$string_file.driver_details"),
        //                'driver_earning' => trans("$string_file.driver_earning"),
        //                'company_earning' => trans("$string_file.merchant_earning"),
        //                'total_booking_amount' => trans("$string_file.booking_amount"),
        //                'tax' => trans("$string_file.tax"),
        //                'sub_total_before_discount' => trans("$string_file.sub_total_before_discount"),
        //                'discount_amount' => trans("$string_file.discount_amount"),
        //                'final_amount_paid' => trans("$string_file.total_amount"),
        //                'booking_date' => trans("$string_file.booking_date"),
        //
        //            ]
        //        )->download('handyman-services-earning' . time() . '.csv');

        $export = [];
        foreach ($arr_bookings as $booking) {
            $tax = (!empty($booking->tax_after_dispute)) ? $booking->tax_after_dispute : $booking->tax;
            if (!empty($booking->HandymanOrderTransaction)) {
                $booking->driver_earning = $booking->HandymanOrderTransaction->driver_earning;
                $booking->company_earning = $booking->HandymanOrderTransaction->company_earning;
                $booking->total_booking_amount =  $booking->final_amount_paid - $tax;
                $booking->booking_date =  trans("$string_file.at") . ' ' . date('H:i', strtotime($booking->created_at)) . ', ' . date_format($booking->created_at, 'D, M d, Y');
                $booking->sub_total_before_discount = $booking->final_amount_paid + $booking->HandymanOrderTransaction->discount_amount;
                $booking->discount_amount = $booking->HandymanOrderTransaction->discount_amount;
                $booking->driver_name = $arr_rides->Driver->fullName ?? $booking->Driver->first_name . ' ' . $booking->Driver->last_name;
                $booking->payment_method =  $booking->PaymentMethod->MethodName($booking->merchant_id);
                $booking->user_detail =  $booking->User->first_name . ' ' . $booking->User->last_name;
            }

            array_push($export, array(
                $booking->merchant_order_id,
                $booking->payment_method,
                $booking->user_detail,
                $booking->driver_name,
                $booking->driver_earning,
                $booking->company_earning,
                $booking->total_booking_amount,
                $tax,
                $booking->sub_total_before_discount,
                $booking->discount_amount,
                $booking->final_amount_paid,
                $booking->booking_date,
            ));
        }
        $heading = array(
            trans("$string_file.booking_id"),
            trans("$string_file.payment_method"),
            trans("$string_file.user_details"),
            trans("$string_file.driver_details"),
            trans("$string_file.driver_earning"),
            trans("$string_file.merchant_earning"),
            trans("$string_file.booking_amount"),
            trans("$string_file.tax"),
            trans("$string_file.sub_total_before_discount"),
            trans("$string_file.discount_amount"),
            trans("$string_file.total_amount"),
            trans("$string_file.booking_date")
        );
        $file_name = 'handyman-services-earning' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }


    // export earning of merchant
    public function orderEarningSummary(Request $request)
    {
        $request->merge(['status' => 'COMPLETED']);
        $order = new Order;
        $all_orders = $order->getOrders($request);
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($all_orders) use($string_file) {
        //            $additional_amount = "";
        //            if(!empty($all_orders->tip_amount))
        //            {
        //                $additional_amount.=trans("$string_file.tip").' : '.$all_orders->tip_amount;
        //            }
        //            $all_orders->business_segment_earning = $all_orders->OrderTransaction->business_segment_earning;
        //            $all_orders->company_earning = $all_orders->OrderTransaction->company_earning;
        //            $all_orders->order_date =  trans("$string_file.at").' '.date('H:i',strtotime($all_orders->created_at)).', '.date_format($all_orders->created_at,'D, M d, Y');
        //            $all_orders->additional_charges =  $additional_amount;
        //            $all_orders->sub_total_before_discount =$all_orders->final_amount_paid + $all_orders->OrderTransaction->discount_amount;
        //            $all_orders->discount_amount = $all_orders->OrderTransaction->discount_amount;
        //            $all_orders->driver_name = $arr_rides->Driver->fullName ?? $all_orders->Driver->first_name.' '.$all_orders->Driver->last_name;
        //            $all_orders->payment_method =  $all_orders->PaymentMethod->MethodName($all_orders->merchant_id);
        //            $all_orders->user_detail =  $all_orders->User->first_name.' '.$all_orders->User->last_name;
        //        });
        //
        //
        //        $csvExporter->build($all_orders,
        //            [
        //                'merchant_order_id' => trans("$string_file.id"),
        //                'payment_method' => trans("$string_file.payment_method"),
        //                'user_detail' => trans("$string_file.user_details"),
        //                'driver_name' => trans("$string_file.driver_details"),
        //                'business_segment_earning' => trans("$string_file.store_earning"),
        //                'company_earning' => trans("$string_file.merchant_earning"),
        //                'cart_amount' => trans("$string_file.cart_amount"),
        //                'tax' => trans("$string_file.tax"),
        //                'delivery_amount' => trans("$string_file.delivery_charge"),
        //                'sub_total_before_discount' => trans("$string_file.sub_total_before_discount"),
        //                'discount_amount' => trans("$string_file.discount_amount"),
        //                'final_amount_paid' => trans("$string_file.order_amount"),
        //                'order_date' => trans("$string_file.order_date"),
        //            ]
        //        )->download('merchant-order-earning' . time() . '.csv');

        $export = [];
        foreach ($all_orders as $order) {
            $additional_amount = "";
            // if (!empty($order->tip_amount)) {
            //     $additional_amount .= trans("$string_file.tip") . ' : ' . $order->tip_amount;
            // }
            $order->business_segment_earning = $order->OrderTransaction->business_segment_earning;
            $order->tax_amount = $order->OrderTransaction->tax_amount;
            $order->business_segment_earning_total = $order->OrderTransaction->business_segment_total_payout_amount;
            $order->company_earning = $order->OrderTransaction->company_earning;
            $order->company_delivery_amount = $order->delivery_amount;
            $order->company_discount_amount = $order->discount_amount;
            $order->merchant_earning_total = $order->OrderTransaction->company_gross_total;
            $order->driver_earning = $order->OrderTransaction->driver_earning;
            $order->driver_additional_charges = $order->OrderTransaction->tip;
            $order->driver_earning_total = $order->OrderTransaction->driver_total_payout_amount;
            $order->order_date =  trans("$string_file.at") . ' ' . date('H:i', strtotime($order->created_at)) . ', ' . date_format($order->created_at, 'D, M d, Y');
            $order->additional_charges =  $order->OrderTransaction->tip;
            $order->sub_total_before_discount = $order->final_amount_paid + $order->OrderTransaction->discount_amount;
            $order->discount_amount = $order->OrderTransaction->discount_amount;
            $order->driver_name = !empty($order->Driver) ? $order->Driver->first_name . ' ' . $order->Driver->last_name : "--";
            $order->payment_method = !empty($order->PaymentMethod->MethodName($order->merchant_id)) ? $order->PaymentMethod->MethodName($order->merchant_id) : $order->PaymentMethod->payment_method;
            $order->user_detail =  $order->User->first_name . ' ' . $order->User->last_name;
            array_push($export, array(
                $order->merchant_order_id,
                $order->payment_method,
                $order->user_detail,
                $order->driver_name,
                $order->cart_amount,
                $order->tax,
                $order->additional_charges,
                $order->delivery_amount,
                $order->sub_total_before_discount,
                $order->discount_amount,
                $order->final_amount_paid,
                $order->company_earning,
                $order->company_delivery_amount,
                $order->company_discount_amount,
                $order->merchant_earning_total,
                $order->business_segment_earning,
                $order->tax_amount,
                $order->business_segment_earning_total,
                $order->driver_earning,
                $order->driver_additional_charges,
                $order->driver_earning_total,
                $order->order_date,
            ));
        }

        $heading = array(
            trans("$string_file.id"),
            trans("$string_file.payment_method"),
            trans("$string_file.user_details"),
            trans("$string_file.driver_details"),
            trans("$string_file.cart_amount"),
            trans("$string_file.tax"),
            trans("$string_file.tip"),
            trans("$string_file.delivery_charge"),
            trans("$string_file.sub_total_before_discount"),
            trans("$string_file.discount_amount"),
            trans("$string_file.order_amount"),
            trans("$string_file.merchant_earning"),
            trans("$string_file.merchant_earning_delivery_charge"),
            trans("$string_file.merchant_earning_discount"),
            trans("$string_file.merchant_earning_total"),
            trans("$string_file.store_earning"),
            trans("$string_file.store_earning_tax_amount"),
            trans("$string_file.store_earning_total"),
            trans("$string_file.driver_earning"),
            trans("$string_file.driver_earning_donation_amount"),
            trans("$string_file.driver_earning_total"),
            trans("$string_file.created_at"),
        );
        $file_name = 'merchant-order-earning' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function VehicleMake(Request $request)
    {
        $merchant = get_merchant_id(false);
        $vehicle_make = $request->vehicle_make;
        $merchant_id = $merchant->id;
        $query = VehicleMake::where([['merchant_id', '=', $merchant->id]]);
        if (!empty($vehicle_make)) {
            $query->with(['LanguageVehicleMakeSingle' => function ($q) use ($vehicle_make, $merchant_id) {
                $q->where('vehicleMakeName', $vehicle_make)->where('merchant_id', $merchant_id);
            }])->whereHas('LanguageVehicleMakeSingle', function ($q) use ($vehicle_make, $merchant_id) {
                $q->where('vehicleMakeName', $vehicle_make)->where('merchant_id', $merchant_id);
            });
        }
        $vehiclemakes =  $query->where('admin_delete', NULL)->get();
        $string_file = $this->getStringFile(NULL, $merchant);

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($vehiclemakes) use($string_file) {
        //            $vehiclemakes->vehicle_make = $vehiclemakes->VehicleMakeName;
        //            $vehiclemakes->vehicle_make_desc = $vehiclemakes->VehicleMakeDescription;
        //            $vehiclemakes->vehicle_make_logo = get_image($vehiclemakes->vehicleMakeLogo,'vehicle');
        //            $vehiclemakes->status = $vehiclemakes->vehicleMakeStatus == 1 ? trans("$string_file.active") : trans("$string_file.inactive");
        //        });
        //
        //        $csvExporter->build($vehiclemakes,
        //            [
        //                'vehicle_make' => trans("$string_file.vehicle_make"),
        //                'vehicle_make_logo' => trans("$string_file.logo"),
        //                'vehicle_make_desc' => trans("$string_file.description"),
        //                'status' => trans("$string_file.status"),
        //            ]
        //        )->download('vehicle_make' . time() . '.csv');

        $export = [];
        foreach ($vehiclemakes as $vehiclemake) {
            $vehiclemake->vehicle_make = $vehiclemake->VehicleMakeName;
            $vehiclemake->vehicle_make_desc = $vehiclemake->VehicleMakeDescription;
            $vehiclemake->vehicle_make_logo = get_image($vehiclemake->vehicleMakeLogo, 'vehicle');
            $vehiclemake->status = $vehiclemake->vehicleMakeStatus == 1 ? trans("$string_file.active") : trans("$string_file.inactive");

            array_push($export, array(
                $vehiclemake->vehicle_make,
                $vehiclemake->vehicle_make_logo,
                $vehiclemake->vehicle_make_desc,
                $vehiclemake->status,
            ));
        }
        $heading = array(
            trans("$string_file.vehicle_make"),
            trans("$string_file.logo"),
            trans("$string_file.description"),
            trans("$string_file.status")
        );
        $file_name = 'vehicle_make' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function VehicleModel(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $vehicle_model = $request->vehicle_model;
        $query = VehicleModel::where([['merchant_id', '=', $merchant_id],['admin_delete', '=', NULL]])
            ->whereHas('VehicleType',function($q){
                $q->where('admin_delete',NULL);
            })
            ->whereHas('VehicleMake',function($q){
                $q->where('admin_delete',NULL);
            });
            
            if (!empty($vehicle_model)) {
                $query->with(['LanguageVehicleModelSingle' => function ($q) use ($vehicle_model, $merchant_id) {
                    $q->where('vehicleModelName', $vehicle_model)->where('merchant_id', $merchant_id);
                }])->whereHas('LanguageVehicleModelSingle', function ($q) use ($vehicle_model, $merchant_id) {
                    $q->where('vehicleModelName', $vehicle_model)->where('merchant_id', $merchant_id);
                });
            }
        $vehicleModels = $query->get();
        $string_file = $this->getStringFile(NULL, $merchant);

        $export = [];
        foreach ($vehicleModels as $vehicleModel) {
            $vehicleModel->vehicle_type = $vehicleModel->VehicleType->VehicleTypeName;
            $vehicleModel->vehicle_make = $vehicleModel->VehicleMake->VehicleMakeName;
            $vehicleModel->vehicle_model = $vehicleModel->VehicleModelName;
            $vehicleModel->vehicle_model_desc = $vehicleModel->VehicleModelDescription;
            $vehicleModel->seat = $vehicleModel->vehicle_seat;
            $vehicleModel->status = $vehicleModel->vehicleModelStatus == 1 ? trans("$string_file.active") : trans("$string_file.inactive");

            array_push($export, array(
                $vehicleModel->vehicle_type,
                $vehicleModel->vehicle_make,
                $vehicleModel->vehicle_model,
                $vehicleModel->vehicle_model_desc,
                $vehicleModel->seat,
                $vehicleModel->status,
            ));
        }
        $heading = array(
            trans("$string_file.vehicle_make"),
            trans("$string_file.vehicle_make"),
            trans("$string_file.vehicle_make"),
            trans("$string_file.description"),
            trans("$string_file.no_of_seat"),
            trans("$string_file.status")
        );
        $file_name = 'vehicle_model' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function OrderManagement(Request $request)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id =  $merchant->id;
        $request->merge(['merchant_id' => $merchant_id]);

        $order = new Order;
        $all_orders = $order->getOrders($request);
        $req_param['merchant_id'] = $merchant_id;
        $arr_status = $this->getOrderStatus($req_param);

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($all_orders) use($string_file,$arr_status) {
        //
        //            $all_orders->merchant_earning = NULL;
        //            $all_orders->store_earning = NULL;
        //            $all_orders->driver_earning = NULL;
        //            if(!empty($all_orders->OrderTransaction))
        //            {
        //                $all_orders->merchant_earning = $all_orders->OrderTransaction->company_earning;
        //                $all_orders->store_earning = $all_orders->OrderTransaction->business_segment_earning;
        //                $all_orders->driver_earning = $all_orders->OrderTransaction->driver_earning;
        //            }
        //
        //            $all_orders->payment_mode = $all_orders->PaymentMethod->payment_method;
        //            $all_orders->user_name = $all_orders->User->first_name.' '.$all_orders->User->last_name;
        //            $all_orders->user_contact = $all_orders->User->UserPhone.', '.$all_orders->User->email;
        //
        //            $all_orders->drive_name = "";
        //            $all_orders->driver_contact = "";
        //            if(!empty($all_orders->driver_id))
        //            {
        //                $all_orders->drive_name = $all_orders->Driver->first_name.' '.$all_orders->Driver->last_name;
        //                $all_orders->driver_contact = $all_orders->Driver->PhoneNumber.', '.$all_orders->Driver->email;
        //            }
        //
        //            $all_orders->store_name = $all_orders->BusinessSegment->full_name;
        //            $all_orders->store_contact = $all_orders->BusinessSegment->phone_number.', '.$all_orders->BusinessSegment->email;
        //            $product_details = "";
        //            foreach($all_orders->OrderDetail as $product)
        //            {
        //                if(!empty($product))
        //                {
        //                    $weight =  isset($product->ProductVariant->weight) ? $product->ProductVariant->weight : "";
        //                    $unit = isset($product->weight_unit_id) ? $product->WeightUnit->WeightUnitName : "";
        //                    $unit = !empty($weight)  ? $product->quantity.' x '.$weight.' '.$unit : $product->quantity.$unit;
        //                    $product_details .=$product->quantity.' '.$unit.' '.$product->Product->Name($all_orders->merchant_id).',';
        //                }
        //            }
        //            $all_orders->product_details = $product_details;
        //            $all_orders->order_status = $arr_status[$all_orders->order_status];
        //        });
        //
        //        $csvExporter->build($all_orders,
        //            [
        //                'merchant_order_id' => trans("$string_file.order_id") ,
        //                'final_amount_paid' => trans("$string_file.final_amount") ,
        //                'merchant_earning' => trans("$string_file.merchant_earning"),
        //                'store_earning' => trans("$string_file.store_earning"),
        //                'driver_earning' => trans("$string_file.driver_earning"),
        //                'payment_mode' => trans("$string_file.payment_method"),
        //                'cart_amount' => trans("$string_file.cart_amount"),
        //                'tax' => trans("$string_file.tax"),
        //                'tip' => trans("$string_file.tip"),
        //                'discount' => trans("$string_file.discount"),
        //                'user_name' => trans("$string_file.user_name"),
        //                'user_contact' => trans("$string_file.user_contact"),
        //                'drive_name' => trans("$string_file.driver"),
        //                'driver_contact' => trans("$string_file.driver_contact"),
        //                'product_details' => trans("$string_file.product_details"),
        //                'store_name' => trans("$string_file.store_name"),
        //                'store_contact' => trans("$string_file.store_contact"),
        //                'order_date' => trans("$string_file.order_date"),
        //                'order_status' => trans("$string_file.order_status"),
        //                'created_at' => trans("$string_file.created_at"),
        //            ])->download('merchant-orders-list-' . time() . '.csv');

        $export = [];
        foreach ($all_orders as $order) {
            $order->merchant_earning = NULL;
            $order->store_earning = NULL;
            $order->driver_earning = NULL;
            if (!empty($order->OrderTransaction)) {
                $order->merchant_earning = $order->OrderTransaction->company_earning;
                $order->store_earning = $order->OrderTransaction->business_segment_earning;
                $order->driver_earning = $order->OrderTransaction->driver_earning;
            }

            $order->payment_mode = $order->PaymentMethod->payment_method;
            $order->user_name = $order->User->first_name . ' ' . $order->User->last_name;
            $order->user_contact = $order->User->UserPhone . ', ' . $order->User->email;

            $order->drive_name = "";
            $order->driver_contact = "";
            if (!empty($order->driver_id)) {
                $order->drive_name = $order->Driver->first_name . ' ' . $order->Driver->last_name;
                $order->driver_contact = $order->Driver->PhoneNumber . ', ' . $order->Driver->email;
            }

            $order->store_name = $order->BusinessSegment->full_name;
            $order->store_contact = $order->BusinessSegment->phone_number . ', ' . $order->BusinessSegment->email;
            $product_details = "";
            foreach ($order->OrderDetail as $product) {
                if (!empty($product)) {
                    $weight =  isset($product->ProductVariant->weight) ? $product->ProductVariant->weight : "";
                    $unit = isset($product->weight_unit_id) ? $product->WeightUnit->WeightUnitName : "";
                    $unit = !empty($weight)  ? $product->quantity . ' x ' . $weight . ' ' . $unit : $product->quantity . $unit;
                    $product_details .= $product->quantity . ' ' . $unit . ' ' . $product->Product->Name($order->merchant_id) . ',';
                }
            }
            $order->product_details = $product_details;
            $order->order_status = $arr_status[$order->order_status];

            array_push($export, array(
                'merchant_order_id' => $order->merchant_order_id,
                'final_amount_paid' => $order->final_amount_paid,
                'merchant_earning' => $order->merchant_earning,
                'store_earning' => $order->store_earning,
                'driver_earning' => $order->driver_earning,
                'payment_mode' => $order->payment_mode,
                'cart_amount' => $order->cart_amount,
                'tax' => $order->tax,
                'tip' => $order->tip_amount,
                'discount' => $order->discount,
                'user_name' => $order->user_name,
                'user_contact' => $order->user_contact,
                'drive_name' => $order->drive_name,
                'driver_contact' => $order->driver_contact,
                'product_details' => $order->product_details,
                'store_name' => $order->store_name,
                'store_contact' => $order->store_contact,
                'order_date' => $order->order_date,
                'order_status' => $order->order_status,
                'created_at' => $order->created_at,
            ));
        }
        $heading = array(
            trans("$string_file.order_id"),
            trans("$string_file.final_amount"),
            trans("$string_file.merchant_earning"),
            trans("$string_file.store_earning"),
            trans("$string_file.driver_earning"),
            trans("$string_file.payment_method"),
            trans("$string_file.cart_amount"),
            trans("$string_file.tax"),
            trans("$string_file.tip"),
            trans("$string_file.discount"),
            trans("$string_file.user_name"),
            trans("$string_file.user_contact"),
            trans("$string_file.driver"),
            trans("$string_file.driver_contact"),
            trans("$string_file.product_details"),
            trans("$string_file.store_name"),
            trans("$string_file.store_contact"),
            trans("$string_file.order_date"),
            trans("$string_file.order_status"),
            trans("$string_file.created_at"),
        );
        $file_name = 'merchant-orders-list-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }


    // export categories
    public function categories(Request $request)
    {
        $category_name = $request->category;
        $merchant_id = $request->merchant_id;
        $permission_segments = get_permission_segments(1, true);
        $query = Category::with(['Segment' => function ($query) use ($permission_segments) {
            if (!empty($permission_segments)) {
                $query->whereIn('slag', $permission_segments);
            }
        }])->whereHas("Segment", function ($query) use ($permission_segments) {
            if (!empty($permission_segments)) {
                $query->whereIn('slag', $permission_segments);
            }
        })
            ->where('merchant_id', $merchant_id)->where('delete', '=', NULL);
        if (!empty($category_name)) {
            $query->with(['LangCategorySingle' => function ($q) use ($category_name, $merchant_id) {
                $q->where('name', $category_name)->where('merchant_id', $merchant_id);
            }])->whereHas('LangCategorySingle', function ($q) use ($category_name, $merchant_id) {
                $q->where('name', $category_name)->where('merchant_id', $merchant_id);
            });
        }
        $arr_categories = $query->get();
        $string_file = $this->getStringFile($merchant_id);

        //        $csvExporter = new \Laracsv\Export();
        //        $csvExporter->beforeEach(function ($arr_categories) use($string_file) {
        //            $parent_category_name = "";
        //            if (!empty($arr_categories->category_parent_id)) {
        //            $parent_category = Category::where('id', $arr_categories->category_parent_id)->first();
        //            if (!empty($parent_category->id))
        //                {
        //                      $parent_category_name =  $parent_category->Name($arr_categories->merchant_id) ;
        //                }
        //            }
        //           else
        //            {
        //                $parent_category_name =  trans("$string_file.none");
        //            }
        //            $arr_categories->category_name = $arr_categories->Name($arr_categories->merchant_id);
        //            $arr_categories->parent_category_name = $parent_category_name;
        //        });
        //
        //        $csvExporter->build($arr_categories,
        //            [
        //                'category_parent_id' => trans("$string_file.parent_category_id"),
        //                'parent_category_name' => trans("$string_file.parent_category"),
        //                'id' => trans("$string_file.category_id"),
        //                'category_name' => trans("$string_file.category"),
        //
        //            ])->download('merchant-category-list-' . time() . '.csv');

        $export = [];
        foreach ($arr_categories as $arr_category) {
            if (!empty($arr_category->category_parent_id)) {
                $parent_category = Category::where('id', $arr_category->category_parent_id)->first();
                $parent_category_name = !empty($parent_category->id) ? $parent_category->Name($arr_category->merchant_id) : "";
            } else {
                $parent_category_name =  trans("$string_file.none");
            }
            $arr_category->category_name = $arr_category->Name($arr_category->merchant_id);
            $arr_category->parent_category_name = $parent_category_name;

            array_push($export, array(
                $arr_category->category_parent_id,
                $arr_category->parent_category_name,
                $arr_category->id,
                $arr_category->category_name
            ));
        }

        $heading = array(
            trans("$string_file.parent_category_id"),
            trans("$string_file.parent_category"),
            trans("$string_file.category_id"),
            trans("$string_file.category"),
        );
        $file_name = 'merchant-category-list-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }


    // export weight unit
    public function weightUnit(Request $request)
    {
        $business_segment = get_business_segment(false);
        $merchant_id = $business_segment->merchant_id;
        $permission_segments[] = $business_segment->Segment->slag;
        $query = WeightUnit::with(['Segment' => function ($query) use ($permission_segments) {
            $query->whereIn('slag', $permission_segments);
        }])->whereHas("Segment", function ($query) use ($permission_segments) {
            $query->whereIn('slag', $permission_segments);
        })
            ->where('merchant_id', $merchant_id)

        ;

        $arr_units = $query->get();
        $string_file = $this->getStringFile($merchant_id);
//        $csvExporter = new \Laracsv\Export();
//        $csvExporter->beforeEach(function ($arr_units) use ($string_file) {
//            $arr_units->unit_name = $arr_units->WeightUnitName;
//        });
//        $csvExporter->build($arr_units,
//            [
//                'id' => trans("$string_file.id"),
//                'unit_name' => trans("$string_file.weight_unit"),
//            ])->download('merchant-weight_unit-list-' . time() . '.csv');

        $export = [];
        foreach($arr_units as $arr_unit){
            $arr_unit->unit_name = $arr_unit->WeightUnitName;
            array_push($export, array(
                $arr_unit->id,
                $arr_unit->unit_name
            ));
        }

        $heading = array(
            trans("$string_file.id"),
            trans("$string_file.weight_unit")
        );
        $file_name = 'merchant_weight_unit_list_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }


    // export product to import variant
    public function productForVariant(Request $request)
    {
        // $business_segment = get_business_segment(false);
        // $query = Product::where('business_segment_id',$business_segment->id);
        // $arr_products = $query->get();
        // $merchant_id = $business_segment->merchant_id;
        // $string_file = $this->getStringFile($merchant_id);
        // $csvExporter = new \Laracsv\Export();
        // $csvExporter->beforeEach(function ($arr_products) use($string_file,$merchant_id) {
        //     $arr_products->product_name = $arr_products->Name($merchant_id);
        // });



        return  Excel::download(new ProductVariantExport, 'product_variant_import_sheet.xlsx');

        // $csvExporter->build($arr_products,
        //     [
        //         'id' => trans("$string_file.id"),
        //         'sku_id' => trans("$string_file.product_sku"),
        //         'product_name' => trans("$string_file.product_title"),
        //         'c1' => trans("$string_file.variant_sku"),
        //         'c2' => trans("$string_file.variant_title"),
        //         'c3' => trans("$string_file.price"),
        //         'c4' => trans("$string_file.weight_unit"),
        //         'c5' => trans("$string_file.weight"),
        //         'c6' => trans("$string_file.is_title_show"),
        //         'c7' => trans("$string_file.status"),
        //         'c8' => trans("$string_file.stock"),
        //     ])->download('product_variant_import_sheet.xlsx');
    }

    // export handyman bookings
    public function exportHandymanBookings(Request $request)
    {
        $handyman = new HandymanOrder;
        $arr_bookings = $handyman->getSegmentOrders($request, false);
        //        p($arr_bookings);
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);

        //        $csvExporter = new \Laracsv\Export();
        $req_param['merchant_id'] = $merchant->id;
        $arr_status = $this->getHandymanBookingStatus($req_param, $string_file);
        $arr_price_type = get_price_card_type("web", "BOTH", $string_file);

        //        $csvExporter->beforeEach(function ($arr_bookings) use($string_file,$arr_status,$arr_price_type) {
        //
        //            $arr_bookings->driver_name = "";
        //            $arr_bookings->driver_phone = "";
        //            if(!empty($arr_bookings->driver_id))
        //            {
        //            $arr_bookings->driver_name = $arr_bookings->Driver->first_name.' '.$arr_bookings->Driver->last_name;
        //            $arr_bookings->driver_phone = $arr_bookings->Driver->phoneNumber;
        //            }
        //            $arr_bookings->user_name = $arr_bookings->User->first_name.' '.$arr_bookings->User->last_name;
        //            $arr_bookings->user_phone = $arr_bookings->User->UserPhone;
        //            $arr_bookings->segment = $arr_bookings->Segment->Name($arr_bookings->merchant_id);
        //            $arr_bookings->service_date = $arr_bookings->booking_date; // on that day service will be done
        //            $arr_bookings->booking_date = $arr_bookings->created_at;
        //            $arr_bookings->payment_method = $arr_bookings->PaymentMethod->MethodName($arr_bookings->merchant_id);
        //            $arr_bookings->cart_amount = $arr_bookings->cart_amount;
        //            $arr_bookings->tax = $arr_bookings->tax;
        //            $arr_bookings->minimum_booking_amount = $arr_bookings->minimum_booking_amount;
        //            $arr_bookings->final_amount_paid = $arr_bookings->final_amount_paid;
        //            $arr_bookings->request_status = isset($arr_status[$arr_bookings->order_status])?$arr_status[$arr_bookings->order_status]: "";
        //            $arr_bookings->service_location = $arr_bookings->drop_location;
        //            $arr_bookings->service_area = $arr_bookings->CountryArea->CountryAreaName;
        //            $arr_bookings->price_type = isset($arr_price_type[$arr_bookings->price_type]) ?  $arr_price_type[$arr_bookings->price_type] : "";
        //            $arr_services = ""; $order_details = $arr_bookings->HandymanOrderDetail;
        //            foreach($order_details as $details){
        //
        //                $service_name =   $details->ServiceType->serviceName;
        //                $arr_services.= $service_name.',';
        //            }
        //            $arr_bookings->services = $arr_services;
        //
        //            if(!empty($arr_bookings->HandymanOrderTransaction))
        //            {
        //                $arr_bookings->driver_earning = $arr_bookings->HandymanOrderTransaction->driver_earning;
        //                $arr_bookings->merchant_earning = $arr_bookings->HandymanOrderTransaction->company_earning;
        //            }
        //
        //        });

        //        $csvExporter->build($arr_bookings,
        //            [
        //                'merchant_order_id' => trans("$string_file.id"),
        //                'driver_name' => trans("$string_file.driver").' '.trans("$string_file.name"),
        //                'driver_phone' =>  trans("$string_file.driver").' '.trans("$string_file.phone"),
        //                'user_name' => trans("$string_file.user").' '.trans("$string_file.name"),
        //                'user_phone' => trans("$string_file.user").' '.trans("$string_file.phone"),
        //                'services' => trans("$string_file.services"),
        //                'segment' => trans("$string_file.segment"),
        //                'price_type' => trans("$string_file.price_type"),
        //                'service_date' => trans("$string_file.service_date"),
        //                'booking_date' => trans("$string_file.booking_date"),
        //                'payment_method' => trans("$string_file.payment_method"),
        //                'cart_amount' => trans("$string_file.cart_amount"),
        //                'tax' => trans("$string_file.tax"),
        //                'minimum_booking_amount' => trans("$string_file.minimum_booking_amount"),
        //                'final_amount_paid' => trans("$string_file.final_amount_paid"),
        //                'driver_earning' => trans("$string_file.driver_earning"),
        //                'merchant_earning' => trans("$string_file.merchant_earning"),
        //                'service_area' => trans("$string_file.service_area"),
        //                'request_status' => trans("$string_file.status"),
        //                'drop_location' => trans("$string_file.drop_location"),
        //            ]
        //        )->download('export-handyman-bookings' . time() . '.csv');

        $export = [];
        foreach ($arr_bookings as $arr_booking) {
            $tax = (!empty($arr_booking->tax_after_dispute)) ? $arr_booking->tax_after_dispute : $arr_booking->tax;
            $arr_booking->driver_name = !empty($arr_booking->driver_id) ? $arr_booking->Driver->first_name . ' ' . $arr_booking->Driver->last_name : "";
            $arr_booking->driver_phone = !empty($arr_booking->driver_id) ? $arr_booking->Driver->phoneNumber : "";

            $arr_booking->user_name = $arr_booking->User->first_name . ' ' . $arr_booking->User->last_name;
            $arr_booking->user_phone = $arr_booking->User->UserPhone;
            $arr_booking->segment = $arr_booking->Segment->Name($arr_booking->merchant_id);
            $arr_booking->service_date = $arr_booking->booking_date; // on that day service will be done
            $arr_booking->booking_date = $arr_booking->created_at;
            $arr_booking->payment_method = $arr_booking->PaymentMethod->MethodName($arr_booking->merchant_id);
            $arr_booking->cart_amount = $arr_booking->cart_amount;
            $arr_booking->tax = $tax;
            $arr_booking->dispute_settled_amount = $arr_booking->dispute_settled_amount;
            $arr_booking->final_amount_paid = $arr_booking->final_amount_paid;
            $arr_booking->request_status = isset($arr_status[$arr_booking->order_status]) ? $arr_status[$arr_booking->order_status] : "";
            $arr_booking->service_location = $arr_booking->drop_location;
            $arr_booking->service_area = $arr_booking->CountryArea->CountryAreaName;
            $arr_booking->price_type = isset($arr_price_type[$arr_booking->price_type]) ?  $arr_price_type[$arr_booking->price_type] : "";
            $arr_services = "";
            $order_details = $arr_booking->HandymanOrderDetail;
            foreach ($order_details as $details) {

                $service_name =   $details->ServiceType->serviceName;
                $arr_services .= $service_name . ',';
            }
            $arr_booking->services = $arr_services;

            if (!empty($arr_booking->HandymanOrderTransaction)) {
                $arr_booking->driver_earning = $arr_booking->HandymanOrderTransaction->driver_earning;
                $arr_booking->merchant_earning = $arr_booking->HandymanOrderTransaction->company_earning;
            }

            array_push($export, array(
                $arr_booking->merchant_order_id,
                $arr_booking->driver_name,
                $arr_booking->driver_phone,
                $arr_booking->user_name,
                $arr_booking->user_phone,
                $arr_booking->services,
                $arr_booking->segment,
                $arr_booking->price_type,
                $arr_booking->service_date,
                $arr_booking->booking_date,
                $arr_booking->payment_method,
                $arr_booking->cart_amount,
                $arr_booking->tax,
                $arr_booking->dispute_settled_amount,
                $arr_booking->final_amount_paid,
                $arr_booking->driver_earning,
                $arr_booking->merchant_earning,
                $arr_booking->service_area,
                $arr_booking->request_status,
                $arr_booking->drop_location,
            ));
        }

        $heading = array(
            trans("$string_file.id"),
            trans("$string_file.driver") . ' ' . trans("$string_file.name"),
            trans("$string_file.driver") . ' ' . trans("$string_file.phone"),
            trans("$string_file.user") . ' ' . trans("$string_file.name"),
            trans("$string_file.user") . ' ' . trans("$string_file.phone"),
            trans("$string_file.services"),
            trans("$string_file.segment"),
            trans("$string_file.price_type"),
            trans("$string_file.service_date"),
            trans("$string_file.booking_date"),
            trans("$string_file.payment_method"),
            trans("$string_file.cart_amount"),
            trans("$string_file.tax"),
            trans("$string_file.dispute_settled_amount"),
            trans("$string_file.minimum_booking_amount"),
            trans("$string_file.final_amount_paid"),
            trans("$string_file.driver_earning"),
            trans("$string_file.merchant_earning"),
            trans("$string_file.service_area"),
            trans("$string_file.status"),
            trans("$string_file.drop_location"),
        );
        $file_name = 'export-handyman-bookings' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }
    
    public function WalletBalanceReportExport(Request $request, $slug = NULL)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $config = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
        $gender_enable = $config->gender;
        $export = $heading = [];
        if($request->slug == "DRIVER"){
            $drivers = $this->getAllDriver(false, $request);
            if ($drivers->isEmpty()) :
                return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
            endif;

            foreach ($drivers as $driver) {
                $driver_vehicles = $driver->DriverVehicles;
                $vehicle_type_name = [];
                foreach ($driver_vehicles as $vehicle) {
                    $vehicle_type_name[] = $vehicle->VehicleType->VehicleTypeName;
                }
                $driver->vehicle_types = implode(',', $vehicle_type_name);
                $driver->country_area_id = $driver->CountryArea->CountryAreaName;
                $driver->total_earnings = is_null($driver->total_earnings) ? 0 : $driver->total_earnings;
                $driver->total_trips = is_null($driver->total_trips) ? "None" : $driver->total_trips;
                $driver->wallet_money = is_null($driver->wallet_money) ? 0 : $driver->wallet_money;

                if ($driver->driver_gender == 1) {
                    $driver->driver_gender = trans("$string_file.male");
                } elseif ($driver->driver_gender = "") {
                    $driver->driver_gender = "---";
                } else {
                    $driver->driver_gender = trans("$string_file.female");
                }

                $driver->login_logout = ($driver->login_logout == 1) ? trans("$string_file.login") : trans("$string_file.logout");
                $driver->online_offline = ($driver->online_offline == 1) ? trans("$string_file.online") : trans("$string_file.offline");
                $driver->free_busy = ($driver->free_busy == 1) ? trans("$string_file.busy") : trans("$string_file.free");

                $driver->bank_name = is_null($driver->bank_name) ? "None" : $driver->bank_name;
                $driver->account_holder_name = is_null($driver->account_holder_name) ? "None" : $driver->account_holder_name;
                $driver->account_number = is_null($driver->account_number) ? "None" : $driver->account_number;
                $driver->vat_number = is_null($driver->vat_number) ? "None" : $driver->vat_number;

                $temp = array(
                    $driver->fullName,
                    $driver->email,
                    $driver->country_area_id,
                    $driver->phoneNumber,
                    $driver->wallet_money,
                    $driver->total_earnings,
                    $driver->bank_name,
                    $driver->account_holder_name,
                    $driver->account_number,
                    $driver->created_at,
                    $driver->last_location_update_time,
                    $driver->vehicle_types,
                    $driver->vat_number
                );
                if ($gender_enable == 1) {
                    array_push($temp, $driver->driver_gender);
                }
                array_push($export, $temp);
            }
            $heading = array(
                trans("$string_file.driver"),
                trans("$string_file.email"),
                trans("$string_file.service_area"),
                trans("$string_file.phone"),
                trans("$string_file.wallet_money"),
                trans("$string_file.total_earning"),
                trans("$string_file.bank_name"),
                trans("$string_file.account_holder_name"),
                trans("$string_file.account_number"),
                trans("$string_file.registered_date"),
                trans("$string_file.last") . ' ' . trans("$string_file.location") . ' ' . trans("$string_file.updated"),
                trans("$string_file.vehicle") . ' ' . trans("$string_file.type"),
                trans("$string_file.vat_number")
            );

        }
        elseif($request->slug  == "USER"){
            $users = $this->getAllUsers(false, $request);
            if ($users->isEmpty()) :
                return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
            endif;

            foreach ($users as $user){
                $temp = array(
                    $user->first_name.$user->last_name,
                    $user->email,
                    $user->country_area_id,
                    $user->UserPhone,
                    $user->wallet_balance,
                );
                if ($gender_enable == 1) {
                    array_push($temp, $user->user_gender);
                }
                array_push($export, $temp);
            }
            $heading = array(
                trans("$string_file.user"),
                trans("$string_file.email"),
                trans("$string_file.service_area"),
                trans("$string_file.phone"),
                trans("$string_file.wallet_money"),
            );

        }
        elseif($request->slug  == "BUSINESS_SEGMENT"){
            $transaction_controller = new TransactionController();
            $bs = $transaction_controller->getAllBusinessSegments(false, $request);
            if ($bs->isEmpty()) :
                return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
            endif;
            foreach ($bs as $user){
                $temp = array(
                    $user->full_name,
                    $user->email,
                    $user->country_area_id,
                    $user->phone_number,
                    $user->wallet_amount,
                );
                array_push($export, $temp);
            }
            $heading = array(
                trans("$string_file.business_segment"),
                trans("$string_file.email"),
                trans("$string_file.service_area"),
                trans("$string_file.phone"),
                trans("$string_file.wallet_money"),
            );

        }
        if ($gender_enable == 1 && $slug != "BUSINESS_SEGMENT") {
            $heading[] = trans("$string_file.gender");
        }
        $file_name = 'wallet_balance_report_' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function bulkProductExport(Request $request)
    {
        $business_segment = get_business_segment(false);
        $merchant_id = $business_segment->merchant_id;
        $permission_segments[] = $business_segment->Segment->slag;
        $query = Product::with(['Segment' => function ($query) use ($permission_segments) {
            $query->whereIn('slag', $permission_segments);
        }])->whereHas("Segment", function ($query) use ($permission_segments) {
            $query->whereIn('slag', $permission_segments);
        })
            ->with(['ProductVariant' => function($qqq){
                $qqq->with(['ProductInventory']);
            }])
            ->where('merchant_id', $merchant_id)
            ->where('business_segment_id', $business_segment->id);
        $product_data = $query->get();
        $string_file = $this->getStringFile($merchant_id);
        $heading = array(
            "CATEGORY_ID",
            "SKU_ID",
            "PRODUCT_NAME",
            "PRODUCT_DESCRIPTION",
            "INGREDIENT",
            "DISPLAY_ON_HOME_SCREEN",
            "PRODUCT_COVER_IMAGE",
            "PRODUCT_PREPARATION_TIME_MINUTES",
            "TAX",
            "SEQUENCE",
            "STATUS",
            "FOOD_TYPE",
            "MANAGE_INVENTORY",
            "IS_VARIANT",
            "VARIANT_SKU",
            "VARIANT_TITLE",
            "PRICE",
            "WEIGHT_UNIT",
            "WEIGHT",
            "IS_TITLE_SHOW",
            "VARIANT_STATUS",
            "CURRENT_STOCK",
            "PRODUCT_COST",
            "SELLING_PRICE",
        );
        $export = [];
        foreach ($product_data as $product) {
             $product_data = array(
                $product->category_id,
                $product->sku_id,
                $product->langData($merchant_id)->name,
                $product->langData($merchant_id)->description,
                $product->langData($merchant_id)->ingredients,
                ($product->display_type == 1) ? "Yes": "NO",
                $product->product_cover_image,
                $product->product_preparation_time,
                $product->tax,
                $product->sequence,
                ($product->status == 1) ? "ACTIVE" : "INACTIVE",
                $product->food_type,
                $product->manage_inventory,
                "NO",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
                "",
            );
            $export[] = $product_data;
            foreach ($product->ProductVariant as $variant){
                $variants = array(
                    "",
                    $product->sku_id,
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "YES",
                    $variant->sku_id,
                    $variant->Name($merchant_id),
                    $variant->product_price,
                    $variant->weight_unit_id,
                    $variant->weight,
                    ($variant->is_title_show == 1)? "YES" : "NO",
                    ($variant->status == 1)? "ACTIVE" : "NO",
                    isset($variant->ProductInventory)? $variant->ProductInventory->current_stock : "",
                    isset($variant->ProductInventory)? $variant->ProductInventory->product_cost: "",
                    isset($variant->ProductInventory)? $variant->ProductInventory->product_selling_price: "",
                );
                $export[] = $variants;
            }
        }
        $file_name = 'bulk-product-export' . time() . '.csv';
         return Excel::download(new CustomExport($heading, $export), $file_name);
    }

    public function TaxiCompanyTransactionExport(){
        $taxi_company = get_taxicompany();
        $merchant = $taxi_company->Merchant;
        $merchant_id = $taxi_company->Merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        if(!empty($taxi_company)){
            $where = [['merchant_id', '=', $merchant_id],['taxi_company_id', '=', $taxi_company->id],['booking_closure', '=', 1]];
            $query = Booking::where($where)->latest();
            if (!empty($merchant->CountryArea->toArray())) {
                $area_ids = array_pluck($merchant->CountryArea, 'id');
                $query->whereIn('country_area_id', $area_ids);
            }
            $transactions = $query->get();
            $export = [];
            $s = 0;
            foreach ($transactions as $transaction) {
                $sn = ++$s;
                $rideId = $transaction->id;
                if($transaction->booking_type == 1){
                    $userWalletReport = trans("$string_file.ride_now");
                }else{
                    $userWalletReport = trans("$string_file.ride").' '.trans("$string_file.later");
                }
                
                $area = $transaction->CountryArea->CountryAreaName;
                $comType = "";
                if(isset($transaction['BookingTransaction']['commission_type']) && $transaction['BookingTransaction']['commission_type'] == 1){
                    $comType = trans("$string_file.pre_paid");
                }elseif(isset($transaction['BookingTransaction']['commission_type']) && $transaction['BookingTransaction']['commission_type'] == 2){
                    $comType = trans("$string_file.post_paid");
                }
                
                $userDetails = $transaction->User->UserName . " (" . $transaction->User->UserPhone . ") (" . $transaction->User->email . ")";
                $driverDetails = $transaction->Driver->first_name." ".$transaction->Driver->last_name . " (" . $transaction->Driver->phoneNumber . ") (" . $transaction->Driver->email.")";
                $paymentMethod = $transaction->PaymentMethod->payment_method;
                $tax = $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['tax_amount'];
                $totalAmount = "";
                $promoCodeDisc = "";
                $merchantEarning = "";
                $taxiCompEarn = "";
                if(!empty($transaction['BookingTransaction'])) {
                    $totalAmount = $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['customer_paid_amount'];
                    $promoCodeDisc = $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['discount_amount'];
                    $merchantEarning = $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['company_earning'];
                    $taxiCompEarn = $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['driver_earning'];
                    
                }else{
                    $totalAmount = $transaction->CountryArea->Country->isoCode." ".$transaction->final_amount_paid;
                    $promoCodeDisc = $transaction->CountryArea->Country->isoCode." ".$transaction['BookingDetail']['promo_discount'];
                    $merchantEarning = $transaction->CountryArea->Country->isoCode." ".$transaction->company_cut;
                    $taxiCompEarn = $transaction->CountryArea->Country->isoCode." ".$transaction->driver_cut;
                }
                $taxiCompPayout = $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['driver_total_payout_amount'];
                $taxiCompOutstanding = $transaction->CountryArea->Country->isoCode." ".$transaction['BookingTransaction']['trip_outstanding_amount'];
                $travelDist = $transaction->travel_distance;
                $travelTime = $transaction->travel_time;
                $estBill = $transaction->CountryArea->Country->isoCode." ".$transaction->estimate_bill;
                $date = $transaction->created_at->toDayDateTimeString();
                
                $temp = array(
                    $sn,
                    $rideId,
                    $userWalletReport,
                    $area,
                    $comType,
                    $userDetails,
                    $driverDetails,
                    $paymentMethod,
                    $totalAmount,
                    $promoCodeDisc,
                    $tax,
                    $merchantEarning,
                    $taxiCompEarn,
                    $taxiCompPayout,
                    $taxiCompOutstanding,
                    $travelDist,
                    $travelTime,
                    $estBill,
                    $date
                );
                
                array_push($export, $temp);
                
                 
            }
            
             $heading = array(
                        trans("$string_file.sn"),
                        trans("$string_file.ride_id"),
                        trans("$string_file.user_wallet_reports"),
                        trans("$string_file.area"),
                        trans("$string_file.commission_type"),
                        trans("$string_file.user_details"),
                        trans("$string_file.driver_details"),
                        trans("$string_file.payment"),
                        trans("$string_file.total_amount"),
                        trans("$string_file.promo_code_discount"),
                        trans("$string_file.tax"),
                        trans("$string_file.merchant_earning"),
                        trans("$string_file.taxi_company_earning"),
                        trans("$string_file.taxi_company_payout"),
                        trans("$string_file.taxi_company_outstanding"),
                        trans("$string_file.travelled_distance"),
                        trans("$string_file.travelled_time"),
                        trans("$string_file.estimate_bill"),
                        trans("$string_file.date"),
            );
            
            $file_name = 'taxicompany_transaction' . time() . '.csv';
            return Excel::download(new CustomExport($heading, $export), $file_name);
        }

}


       public function DriverVehicle($data)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        // Prepare the export data
        $export = [];
        foreach($data as $value){
           
         foreach($value->DriverVehicles as $vehicle){
            $status = 'Pending';
            if($vehicle->vehicle_verification_status == 2){
                $status = 'Verified';
            }
            elseif($vehicle->vehicle_verification_status == 3){
                $status = 'Rejected';
            }elseif($vehicle->vehicle_verification_status == 4){
                $status = 'Expired';
            }

            $export[] = [
                $vehicle->shareCode,
                $vehicle->vehicle_number,
                $vehicle->VehicleType->VehicleTypeName,
                $vehicle->VehicleMake->vehicleMakeName,
                $vehicle->VehicleModel->VehicleModelName,
                $value->fullName,
                $value->phoneNumber,
                $value->email,
//                \Carbon\Carbon::parse($value->created_at)->format('d-m-Y H:i:s'),
                convertTimeToUSERzone($value->created_at, $vehicle->Driver->CountryArea->timezone, null, $vehicle->Merchant),
                $status,
                get_image($vehicle->vehicle_image,'user_vehicle_document'),
                get_image($vehicle->vehicle_number_plate_image,'user_vehicle_document')

            ];
        }
    }

        // Define the headings
        $heading = [
            trans("$string_file.vehicle_id"),
            trans("$string_file.vehicle_number"),
            trans("$string_file.vehicle_type"),
            trans("$string_file.vehicle_make"),
            trans("$string_file.vehicle_model"),
            trans("$string_file.driver_name"),
            trans("$string_file.driver_phone_no"),
            trans("$string_file.driver_email"),
            trans("$string_file.created").' '.trans("$string_file.at"),
            trans("$string_file.status"),
            trans("$string_file.image"),
            trans("$string_file.number").trans("$string_file.plate").trans("$string_file.image"),
        ];

        // Use the CustomExport class for exporting
        $file_name = 'vehicle-master-report-' . time() . '.csv';
        return Excel::download(new CustomExport($heading, $export), $file_name);
    }
}
