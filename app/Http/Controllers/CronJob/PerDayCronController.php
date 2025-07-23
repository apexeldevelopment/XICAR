<?php

namespace App\Http\Controllers\CronJob;

use App\Http\Controllers\Merchant\ExpireDocumentController;
use App\Http\Controllers\Merchant\ReferralSystemController;
use App\Models\BusinessSegment\BusinessSegmentConfigurations;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\CarpoolingConfiguration;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\DriverRenewableSubscriptionRecord;
use App\Models\DriverSubscriptionRecord;
use App\Models\DriverVehicle;
use App\Models\DriverVehicleDocument;
use App\Models\HandymanBiddingOrder;
use App\Models\HandymanOrder;
use App\Models\Merchant;
use App\Models\Onesignal;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ExpireDocument;
use App\Models\BusinessSegment\Order;
use DB;
use App\Models\User;
use App\Http\Controllers\Helper\WalletTransaction;
//use App\Traits\HandymanTrait;
use App\Traits\OrderTrait;
use Illuminate\Support\Facades\Redis;

class PerDayCronController extends Controller
{
    /*************** Document expire cron start **************/
    // call cron function
    use ExpireDocument, MerchantTrait,OrderTrait;

    public function document()
    {
//        $this->checkPersonalDocument();
//        $this->checkVehicleDocument();
        $this->getExpiredDocument();
        $this->getDocumentExpireReminder();
        $this->reminderNotificationForOrderDelivery(); //today's delivery reminder
        $this->expireAcceptedOrders(); //expire accepted orders which were not delivered on delivery date

        // User Documents
        $this->getExpiredUserDocument();
        $this->getDocumentUserExpireReminder();
        $this->OfflineExpiredRenewableSubscribedDrivers();
        $this->ApiUsageTrackingFromRedis();
    }

    public function getExpiredDocument()
    {
        $drivers = $this->getAllExpireDriversDocument(NULL, 2, false);
        if (!empty($drivers) && $drivers->count() > 0) {
            foreach ($drivers as $driver) {
                // personal document case
                $notification_status = false;
                if ($driver->DriverDocument->count() > 0) {
                    $notification_status = false;
                    foreach ($driver->DriverDocument as $driverDoc) {
                        if ($driverDoc->temp_document_file != null && ($driverDoc->temp_doc_verification_status == 1 || $driverDoc->temp_doc_verification_status == 2)) {
                            if($driverDoc->temp_doc_verification_status == 1) {
                                $driver->signupStep = 8; // pending mode when admin did not either approve or reject
                                $driver->is_approved = 2;
                                $driver->save();
                            }
                            $driverDoc->document_file = $driverDoc->temp_document_file;
                            $driverDoc->expire_date = $driverDoc->temp_expire_date;
                            $driverDoc->document_verification_status = $driverDoc->temp_doc_verification_status;
                            $driverDoc->temp_document_file = null;
                            $driverDoc->temp_expire_date = null;
                            $driverDoc->temp_doc_verification_status = null;
                            $driverDoc->save();
                        } else {
                            $notification_status = true;
                            $driverDoc->document_verification_status = 4;
                            $driverDoc->save();
                            $driver->online_offline = 2;
                            $driver->save();
                        }
                    }
                }

                if ($driver->segment_group_id == 2) {
                    // segment document case
                    if ($driver->DriverSegmentDocument->count() > 0) {
                        $notification_status = false;
                        foreach ($driver->DriverSegmentDocument as $driverDoc) {
                            if ($driverDoc->temp_document_file != null && ($driverDoc->temp_doc_verification_status == 1 || $driverDoc->temp_doc_verification_status == 2)) {
                                if($driverDoc->temp_doc_verification_status == 1) {
                                    $driver->signupStep = 8; // pending mode when admin did not either approve or reject
                                    $driver->is_approved= 2;
                                    $driver->save();
                                }
                                $driverDoc->document_file = $driverDoc->temp_document_file;
                                $driverDoc->expire_date = $driverDoc->temp_expire_date;
                                $driverDoc->document_verification_status = $driverDoc->temp_doc_verification_status;
                                $driverDoc->temp_document_file = null;
                                $driverDoc->temp_expire_date = null;
                                $driverDoc->temp_doc_verification_status = null;
                                $driverDoc->save();
                            } else {
                                $notification_status = true;
                                $driverDoc->document_verification_status = 4;
                                $driverDoc->save();
                                $driver->online_offline = 2;
                                $driver->save();
                            }
                        }
                    }
                } else {
                    // vehicle document
                    if ($driver->DriverVehicles->count() > 0) {
                        $notification_status = false;
                        foreach ($driver->DriverVehicles as $driverVehicle) {
                            foreach ($driverVehicle->DriverVehicleDocument as $driverDoc) {
                                if ($driverDoc->temp_document_file != null && ($driverDoc->temp_doc_verification_status == 1 || $driverDoc->temp_doc_verification_status == 2)) {
                                    if($driverDoc->temp_doc_verification_status == 1) {
                                        $driver->signupStep = 8; // pending mode when admin did not either approve or reject
                                        $driver->is_approved = 2;
                                        $driver->save();
                                    }
                                    $driverDoc->document = $driverDoc->temp_document_file;
                                    $driverDoc->expire_date = $driverDoc->temp_expire_date;
                                    $driverDoc->document_verification_status = $driverDoc->temp_doc_verification_status;
                                    $driverDoc->temp_document_file = null;
                                    $driverDoc->temp_expire_date = null;
                                    $driverDoc->temp_doc_verification_status = null;
                                    $driverDoc->save();
                                } else {
                                    $notification_status = true;
                                    $driverDoc->document_verification_status = 4;
                                    $driverDoc->save();
                                    $driver->online_offline = 2;
                                    $driver->save();
                                }
                            }
                        }
                    }
                }

                if ($notification_status == true) {
                    $string_file = $this->getStringFile($driver->merchant_id);
                    setLocal($driver->language);
                    $data['notification_type'] = "DOCUMENT_EXPIRED";
                    $data['segment_sub_group'] = NULL;
                    $data['segment_group_id'] = NULL;
                    $data['segment_type'] = "";
                    $data['segment_data'] = [];
                    $arr_param = ['driver_id' => $driver->id, 'data' => $data, 'message' => trans("$string_file.document_expired_error"), 'merchant_id' => $driver->merchant_id, 'title' => trans("$string_file.document_expired")];
                    $a = Onesignal::DriverPushMessage($arr_param);
                }
            }
        }
    }

    public function getDocumentExpireReminder()
    {
        $currentDate = date('Y-m-d');
        $merchants = Merchant::where('parent_id', '=', 0)->get();
        $expire_class = new ExpireDocumentController;
        foreach ($merchants as $merchant) {
            $reminder_days = Configuration::where('merchant_id', '=', $merchant->id)->select('reminder_doc_expire')->first();
            $reminder_last_date = date('Y-m-d', strtotime('+' . $reminder_days->reminder_doc_expire . ' days'));
            $drivers = $expire_class->getDocumentGoingToExpire($currentDate, $reminder_last_date, $merchant->id)->get();
            $Ids = array();
            foreach ($drivers as $driver) {
                if (!empty($driver->player_id) && $driver->player_id != null) {
                    $Ids[] = $driver->id; // send driver id
                }
            }
            if (count($Ids) > 0) {
                $string_file = $this->getStringFile($merchant->id);
                foreach($Ids as $id){
                    setLocal($driver->language);
                    $data['notification_type'] = "DOCUMENT_EXPIRE_REMINDER";
                    $data['segment_sub_group'] = NULL;
                    $data['segment_group_id'] = NULL;
                    $data['segment_type'] = "";
                    $data['segment_data'] = [];
                    $arr_param = ['driver_id' => $id, 'data' => $data, 'message' => trans("$string_file.document_expire_warning"), 'merchant_id' => $merchant->id, 'title' => trans("$string_file.document_expire")];
                    Onesignal::DriverPushMessage($arr_param);
                }
            }
        }
    }

    public function getExpiredUserDocument()
    {
        $users = $this->getAllExpireUserDocument(NULL, 2,false);

        if(!empty($users) && $users->count() > 0)
        {
            foreach ($users as $user)
            {
                $notification_status = false;

                // personal document case
                if($user->Merchant->ApplicationConfiguration->user_document == 1){
                    if($user->UserDocument->count() > 0)
                    {
                        $notification_status = false;
                        foreach ($user->UserDocument as $UserDoc)
                        {
                            if ($UserDoc->temp_document_file != null)
                            {
                                $UserDoc->document_file =$UserDoc->temp_document_file;
                                $UserDoc->expire_date = $UserDoc->temp_expire_date;
                                $UserDoc->document_verification_status = $UserDoc->temp_doc_verification_status;
                                $UserDoc->temp_document_file = null;
                                $UserDoc->temp_expire_date = null;
                                $UserDoc->temp_doc_verification_status = null;
                                $UserDoc->save();
                            }
                            else{
                                $notification_status = true;
                                $UserDoc->document_verification_status = 4;
                                $UserDoc->save();
                            }
                        }
                    }
                }

                // vehicle document
                if($user->Merchant->Segment->where("segment_group_id",3)->count() > 0){
                    if($user->UserVehicles->count() > 0)
                    {
                        $notification_status = false;
                        foreach ($user->UserVehicles as $userVehicle)
                        {
                            foreach ($userVehicle->UserVehicleDocument as $UserDoc)
                            {
                                if ($UserDoc->temp_document_file != null)
                                {
                                    $UserDoc->document = $UserDoc->temp_document_file;
                                    $UserDoc->expire_date = $UserDoc->temp_expire_date;
                                    $UserDoc->document_verification_status = $UserDoc->temp_doc_verification_status;
                                    $UserDoc->temp_document_file = null;
                                    $UserDoc->temp_expire_date = null;
                                    $UserDoc->temp_doc_verification_status = null;
                                    $UserDoc->save();
                                }
                                else{
                                    $notification_status = true;
                                    $UserDoc->document_verification_status = 4;
                                    $UserDoc->save();
                                }
                            }
                        }
                    }
                }

                if($notification_status == true)
                {
                    $data['notification_type'] = "DOCUMENT_EXPIRED";
                    $data['segment_sub_group'] = NULL;
                    $data['segment_group_id'] = NULL;
                    $data['segment_type'] = "";
                    $data['segment_data'] = [];
                    $arr_param = ['user_id' => $user->id, 'data' => $data, 'message' => trans("common.document_expired_error"), 'merchant_id' => $user->merchant_id, 'title' => trans("common.document").' '.trans("common.expired")];
                    Onesignal::UserPushMessage($arr_param);
                }
            }
        }
    }

    public function getDocumentUserExpireReminder()
    {
        $currentDate = date('Y-m-d');
        $merchants = Merchant::where('parent_id','=',0)->get();
        $expire_class = new ExpireDocumentController;
        foreach ($merchants as $merchant) {
            $reminder_days = Configuration::where('merchant_id','=',$merchant->id)->select('reminder_doc_expire')->first();
            $carpooling_config = CarpoolingConfiguration::where('merchant_id','=',$merchant->id)->select('user_document_reminder_time')->first();
            $vehicle_reminder_days = !empty($carpooling_config) ? $carpooling_config->user_document_reminder_time : $reminder_days->reminder_doc_expire;
            $reminder_last_date = date('Y-m-d',strtotime('+'.$reminder_days->reminder_doc_expire.' days'));
            $vehicle_reminder_last_date = date('Y-m-d',strtotime('+'.$vehicle_reminder_days.' days'));
            $users = $expire_class->getUserDocumentGoingToExpire($currentDate,$reminder_last_date,$vehicle_reminder_last_date,$merchant->id)->get();
            $Ids = array();
            foreach ($users as $user) {
                if (!empty($user->player_id) && $user->player_id != null){
                    $Ids[] = $user->id; // send driver id
                }
            }
            if(count($Ids) > 0)
            {
                $data['notification_type'] = "DOCUMENT_EXPIRE_REMINDER";
                $data['segment_sub_group'] = NULL;
                $data['segment_group_id'] = NULL;
                $data['segment_type'] = "";
                $data['segment_data'] = [];
                $arr_param = ['user_id' => $Ids, 'data' => $data, 'message' => trans("common.document_expire_warning"), 'merchant_id' => $merchant->id, 'title' => trans("common.document").' '.trans("common.expire")];
                Onesignal::UserPushMessage($arr_param);
            }
        }
    }
    /*************** Document expire cron end **************/


    /*************** Subscription Package expire cron start **************/
    public function subscriptionPackage()
    {
        $this->ExpireSubscriptionPackage();
    }

    public function ExpireSubscriptionPackage()
    {
       $active_packages = DriverSubscriptionRecord::select('id')->where([['status', '!=', 3], ['end_date_time', '<', date('Y-m-d H:i:s')]])->get();
       if ($active_packages->isNotEmpty()):
           DriverSubscriptionRecord::whereIn('id', $active_packages->toArray())
               ->update([
                   'status' => 3, // package expired
               ]);
       endif;
    }
    /*************** Subscription Package expire cron end **************/

    public function expireHandymanOrder()
    {
        $merchants = Merchant::whereHas("Segment", function ($query) {
            $query->where('segment_group_id', 2);
        })->get();
        if (!empty($merchants)) {
            foreach ($merchants as $merchant) {
                $handyman_orders = HandymanOrder::where([['merchant_id', '=', $merchant->id], ['booking_date', '<',date('Y-m-d')]])->whereIn('order_status',[1,4])->get();
                if (!empty($handyman_orders)) {
                    HandymanOrder::where([['merchant_id', '=', $merchant->id], ['booking_date', '<', date('Y-m-d')]])->whereIn('order_status',[1,4])->update(array('order_status' => 8));
                }
            }
        }
    }

    public function expireHandymanBiddingOrder()
    {
        $merchants = Merchant::whereHas("Segment", function ($query) {
            $query->where('segment_group_id', 2);
        })->get();
        if (!empty($merchants)) {
            foreach ($merchants as $merchant) {
                $handyman_bidding_orders = HandymanBiddingOrder::where([['merchant_id', '=', $merchant->id],['booking_date', '<',date('Y-m-d')], ['order_status', '=', 1]])->get();
                if (!empty($handyman_bidding_orders)) {
                    HandymanBiddingOrder::where([['merchant_id', '=', $merchant->id], ['booking_date', '<', date('Y-m-d')],['order_status', '=', 1]])->update(array('order_status' => 3));
                }
            }
        }
    }

    /*************** Referral System expire cron end **************/

    public function expireReferralSystem()
    {
        $ref_controller = new ReferralSystemController();
        $ref_controller->checkExpireReferralSystem();
    }

    /*************** Referral System expire cron end **************/


    // order delivery reminder for the day
    public function reminderNotificationForOrderDelivery()
    {
        DB::beginTransaction();
        try {
            $current_date = date('Y-m-d');
            $yesterday_date = date('Y-m-d',strtotime("-1 days"));
            $all_orders = Order::select('id','business_segment_id','merchant_id','service_time_slot_detail_id','order_timestamp','country_area_id','driver_id','order_status','segment_id','user_id','order_date','created_at')
                ->whereIn('order_status', [6,7,9,10])
                ->where(function($e) use($current_date,$yesterday_date){
                    $e->where('order_date','=',$current_date)
                    ->orWhere('order_date','=',$yesterday_date);
                })
                ->where('is_order_completed','!=',1)
//                ->where('segment_id','!=',3)
                ->get();
//            p($all_orders);
            if ($all_orders->isNotEmpty())
            {
                    foreach ($all_orders as $order)
                    {
                        $string_file = $this->getStringFile($order->merchant_id);
                        // reminder notification driver
                        $segment_data = [
                            'id'=>$order->id,
                            'order_status'=>$order->order_status,
                        ];
                        $data = array('order_id' => $order->id, 'notification_type' => 'ORDER_DELIVERY_REMINDER', 'segment_type' => $order->Segment->slag,'segment_data' => $segment_data);
                        $arr_param = array(
                            'driver_id' => $order->driver_id,
                            'data'=>$data,
                            'message'=>trans("$string_file.today_order_delivery_title"),
                            'merchant_id'=>$order->merchant_id,
                            'title' => trans("$string_file.today_order_delivery_message").' '.'#'.$order->merchant_order_id,
                        );
//                        p($arr_param);
                        Onesignal::DriverPushMessage($arr_param);
                    }
            }
            DB::commit();
        }catch (\Exception $e)
        {
            $message = $e->getMessage();
            DB::rollBack();
        }
    }

     // expire accepted orders which were not delivered on time
    public function expireAcceptedOrders()
    {
        DB::beginTransaction();
        try {
            $all_orders = Order::select('id','business_segment_id','merchant_id','service_time_slot_detail_id','order_timestamp','country_area_id','driver_id','order_status','segment_id','user_id','order_date','created_at')
                ->whereIn('order_status', [1,6,7,9,10])->get();
            if ($all_orders->isNotEmpty())
            {
                $order_ids = $all_orders->map(function ($item, $key) {
                    if($item->segment_id == 3) { // for food
                        $current_date =  date('Y-m-d');
                        $order_date = date('Y-m-d',strtotime($item->created_at));
                    }
                    else{
                        $current_date =  date('Y-m-d');
                        $order_date = $item->order_date;
                        // next date of order delivery date
                        $order_date = date("Y-m-d", strtotime("+1 day",strtotime($order_date)));
                    }
                    return ($current_date > $order_date  ) ? $item->id : null;
                })->filter()->values();
                // p($order_ids->toArray());
                if($order_ids->count() > 0)
                {
                    $log_data =[
                        'order_id'=>$order_ids->toArray(),
                        'hit_time'=> date('Y-m-d H:i:s'),
                        'request_type'=>"ongoing order expire"
                    ];
                    \Log::channel('per_day_cron_log')->emergency($log_data);

                    Order::whereIn('id', $order_ids->toArray())
                        ->update([
                            'order_status' => '12', //auto expired
                        ]);
                     $arr_orders = Order::
//                    select('id','merchant_id','driver_id','merchant_order_id','order_status','segment_id','user_id','payment_method_id','payment_option_id')->
                    whereIn('id', $order_ids->toArray())->get();
                    // p($arr_orders);
                    foreach ($arr_orders as $order)
                    {
                        // p($order);
                        // send notification to user like your order has been expired
                        $this->sendNotificationToUser($order);
// p($order);
                        // refund credit to user wallet if payment done while placing order
                        if(!empty($order->payment_method_id) && in_array($order->payment_method_id,[2,4,3]))
                        {
                            // p('z');
                            $user = User::select('wallet_balance','merchant_id','id')->where('id',$order->user_id)->first();
                            // p($user);
                            $user->wallet_balance = $user->wallet_balance + $order->final_amount_paid;
                            $user->save();
                            // p($user);
                            // send wallet credit notification
                            $paramArray = array(
                                'user_id' => $user->id,
                                'merchant_id' => $user->merchant_id,
                                'booking_id' => NULL,
                                'amount' => $order->final_amount_paid,
                                'order_id' => $order->id,
                                'narration' => 11,
                                'platform' => 2,
                                'payment_method' => $order->payment_method_id,
                                'payment_option_id' => $order->payment_option_id,
                                'transaction_id' => NULL
                            );
                            // p($paramArray);
                           WalletTransaction::UserWalletCredit($paramArray);
                        }

                        // make driver free once order expired
                        if ($order->order_status != 1 && isset($order->Driver)){
                            $driver = $order->Driver;
                            $driver->free_busy = 2;
                            $driver->save();
                        }
                        // p($driver);
                    }
                }
            }
            DB::commit();
        }catch (\Exception $e)
        {
            $message = $e->getMessage();
            DB::rollBack();
        }
    }

    public function closeLowWalletBalanceStore(): void
    {
        $business_segments = BusinessSegment::where("status", 1)->get();
        foreach ($business_segments as $bs){
            $wallet_balance_low = ($bs->wallet_amount <= 0|| empty($bs->wallet_amount));
            if($wallet_balance_low && $bs->Merchant->Configuration->check_wallet_for_order_receiving == 1){
                BusinessSegmentConfigurations::where("business_segment_id", $bs->id)
                    ->update([
                        "is_open" => 2
                    ]);
            }
        }
    }




    public function OfflineExpiredRenewableSubscribedDrivers()
    {
        DB::beginTransaction();
        try{
            $driver_ids = [];
            DriverRenewableSubscriptionRecord::with('Driver')->chunk(500, function ($records) use (&$driver_ids) {
                foreach ($records as $record) {
                    $driver = $record->Driver;
                    if ( $driver && $driver->login_logout == 1 && !$driver->hasActiveRenewableSubscriptionRecord() && $driver->free_busy != 1 && $driver->online_offline == 1) {
                        $driver_ids[] = $driver->id;
                        $driver->online_offline = 2;
                        $driver->save();
                    }
                }
            });
            $log_data =[
                'driver_ids'=>$driver_ids,
                'hit_time'=> date('Y-m-d H:i:s'),
                'request_type'=>"Offline Expired Renewable Subscribed Drivers"
            ];
            \Log::channel('per_day_cron_log')->emergency($log_data);
        }
        catch (\Exception $e){
            $message = $e->getMessage();
            DB::rollback();
            $log_data =[
                'exception'=>$message,
                'hit_time'=> date('Y-m-d H:i:s'),
                'request_type'=>"Offline Expired Renewable Subscribed Drivers"
            ];
            \Log::channel('per_day_cron_log')->emergency($log_data);
        }
        DB::commit();
    }



    public function ApiUsageTrackingFromRedis()
    {
        DB::beginTransaction();
        try{
            $keys = Redis::keys('api_usage:*:*');
            foreach ($keys as $key) {
                $parts = explode(':', $key);
                if (count($parts) !== 3) continue;

                [$static_key, $merchantId, $date] = $parts;
                $hash = Redis::hgetall($key);

                if (empty($hash)) continue;

                $structured = [];
                foreach ($hash as $field => $count) {
                    $segments = explode(':', $field);
                    if (count($segments) !== 3) continue;

                    [$mapType, $apiEndPoint, $providerEndPoint] = $segments;

                    $structured[] = [
                        'map_type'          => $mapType,
                        'api_end_point'     => $apiEndPoint,
                        'provider_end_point'=> $providerEndPoint,
                        'count'             => (int) $count,
                    ];
                }

                DB::table('api_usages')->updateOrInsert(
                    ['merchant_id' => $merchantId, 'date' => $date],
                    ['usage_record' => json_encode($structured), 'updated_at' => now()]
                );
            }
            $log_data =[
                'message'=> "imported data from redis !",
                'hit_time'=> date('Y-m-d H:i:s'),
                'request_type'=>"ApiUsageTrackingFromRedis"
            ];
            \Log::channel('per_day_cron_log')->emergency($log_data);
        }
        catch(Exception $e){
            $message = $e->getMessage();
            DB::rollback();
            $log_data =[
                'exception'=>$message,
                'hit_time'=> date('Y-m-d H:i:s'),
                'request_type'=>"ApiUsageTrackingFromRedis"
            ];
            \Log::channel('per_day_cron_log')->emergency($log_data);
        }
        DB::commit();
    }

}
