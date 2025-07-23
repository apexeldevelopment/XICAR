<?php

namespace App\Http\Controllers\Helper;

use App\Models\Driver;
use App\Models\DriverRewardPoint;
use App\Models\ReferralDiscount;
use App\Models\ReferralSystem;
use App\Models\RewardSystem;
use App\Models\User;
use App\Models\UserRewardPoint;

class RewardPoint
{

    //Referral Module

    public static function giveReferralReward($sender, $type)
    {
//        if ($type == 1){
//            $reward_point_data = \App\Models\RewardPoint::where('merchant_id' , $sender->merchant_id)
//                ->where('country_area_id' , $sender->country_area_id)
//                ->where('active' , 1)->first();
//            if ($reward_point_data && $reward_point_data->referral_enable == 1) {
//                $sender->reward_points = $sender->reward_points + $reward_point_data->user_referral_reward;
//                $sender->save();
//            }
//        }
//        if ($type ==  2){
//            $reward_point_data = \App\Models\RewardPoint::where('merchant_id' , $sender->merchant_id)
//                ->where('country_area_id' , $sender->country_area_id)
//                ->where('active' , 1)->first();
//            if ($reward_point_data && $reward_point_data->referral_enable == 1) {
//                $sender->reward_points = $sender->reward_points + $reward_point_data->driver_referral_reward;
//                $sender->save();
//            }
//        }
        $reward_point_data = \App\Models\RewardPoint::where('merchant_id', $sender->merchant_id)
            ->where('country_area_id', $sender->country_area_id)
            ->where('active', 1)->first();
        if ($reward_point_data && $reward_point_data->referral_enable == 1) {
            if ($type == 1) {
                $sender->reward_points = $sender->reward_points + $reward_point_data->user_referral_reward;
                $sender->save();
            }
            if ($type == 2) {
                $sender->reward_points = $sender->reward_points + $reward_point_data->driver_referral_reward;
                $sender->save();
            }
        }
    }


    // tutu changes
    public static function giveUserReferralReward($user)
    {
        $reward_point_data = \App\Models\RewardPoint::where('merchant_id', $user->merchant_id)
            ->where('country_area_id', $user->country_area_id)
            ->where('active', 1)->first();
        if ($reward_point_data && $reward_point_data->referral_enable == 1) {
            $user->reward_points = $user->reward_points + $reward_point_data->user_referral_reward;
            $user->save();
        }
    }


    public static function giveDriverReferralReward($driver)
    {
        $reward_point_data = \App\Models\RewardPoint::where('merchant_id', $driver->merchant_id)
            ->where('country_area_id', $driver->country_area_id)
            ->where('active', 1)->first();
        if ($reward_point_data && $reward_point_data->referral_enable == 1) {
            $driver->reward_points = $driver->reward_points + $reward_point_data->driver_referral_reward;
            $driver->save();
        }
    }

    public static function giveUserRegistrationReward($user)
    {
        $reward_point_data = \App\Models\RewardPoint::where('merchant_id', $user->merchant_id)
            ->where('country_area_id', $user->country_area_id)
            ->where('active', 1)->first();
        if ($reward_point_data && $reward_point_data->registration_enable == 1) {
            $user->reward_points = $user->reward_points + $reward_point_data->user_registration_reward;
            $user->save();
        }
    }

    public static function incrementUserTripCount($user)
    {
        $reward_point_data = \App\Models\RewardPoint::where('merchant_id', $user->merchant_id)
            ->where('country_area_id', $user->country_area_id)
            ->where('active', 1)->first();
        if ($reward_point_data) {
            $user->use_reward_trip_count = $user->use_reward_trip_count + 1;
            if ($user->use_reward_trip_count == $reward_point_data->trips_count) {
//        $user->use_reward_count = $user->use_reward_count + 1;
                $user->usable_reward_points = $user->usable_reward_points + $reward_point_data->max_redeem;
                $user->use_reward_trip_count = 0;
            }
            $user->save();
        }
    }


    public static function giveDriverRegistrationReward($driver)
    {
        $reward_point_data = \App\Models\RewardPoint::where('merchant_id', $driver->merchant_id)
            ->where('country_area_id', $driver->country_area_id)
            ->where('active', 1)->first();
        if ($reward_point_data && $reward_point_data->registration_enable == 1) {
            $driver->reward_points = $driver->reward_points + $reward_point_data->driver_registration_reward;
            $driver->save();
        }
    }

//    public function getOfferDetails($referral_code,$merchant_id,$country_id, $type){
//        $where1 = ['country_id','=',$country_id];
//        $where2 = ['merchant_id','=',$merchant_id];
//        $where3 = ['delete_status','=',NULL];
//        $where4 = ['status', '=', 1];
//        // Type 1 for user and 2 for driver
//        switch ($type){
//            case '1':
//                if (ReferralSystem::where([['code_name','=',$referral_code],$where1,$where2,$where3])->exists()){
//                    $offer_details = ReferralSystem::where([['code_name','=',$referral_code],$where1,$where2,$where3,$where4])->whereIn('application',array(1,2,3))->first();
//                    $senderType = 0;
//                }elseif (User::where([['ReferralCode','=',$referral_code],$where1,$where2,['user_delete', '=', NULL]])->exists()) {
//                    $offer_details = ReferralSystem::where([['default_code', '=', 0], $where1,$where2,$where3,$where4])->whereIn('application', array(1, 3))->latest()->first();
//                    $senderType = 1;
//                }else{
//                    return false;
//                }
//               break;
//            case '2':
//                if (ReferralSystem::where([['code_name','=',$referral_code],$where1,$where2,$where3])->exists()){
//                    $offer_details = ReferralSystem::where([['code_name','=',$referral_code],$where1,$where2,$where3,$where4])->whereIn('application',array(1,2,3))->first();
//                    $senderType = 0;
//                }elseif (Driver::where([['driver_referralcode','=',$referral_code],['merchant_id','=',$merchant_id]])->exists()){
//                    $offer_details = ReferralSystem::where([['default_code','=',0],$where1,$where2,$where3,$where4])->whereIn('application',array(2,3))->latest()->first();
//                    $senderType = 2;
//                }else{
//                    return false;
//                }
//                break;
//        }
//        return array($offer_details,$senderType);
//    }
//
//    public function getSenderDetails($sender,$code,$country_id,$merchant_id){
//        switch ($sender){
//            case 1:
//                $sender_details = User::where([['ReferralCode','=',$code],['country_id','=',$country_id],['merchant_id','=',$merchant_id],['user_delete', '=', NULL]])->first();
//                return $sender_details;
//                break;
//            case 2:
//                $sender_details = Driver::where([['driver_referralcode','=',$code],['merchant_id','=',$merchant_id],['driver_delete','=',NULL]])->first();
//                return $sender_details;
//                break;
//            default:
//                return false;
//                break;
//        }
//    }
//
//    public function ReferralOffer($referOffer,$receiver_type,$refer_id,$sender_type,$refer_sender_id,$merchant_id)
//    {
//        $this->AddDiscount($merchant_id,$referOffer->id,$refer_id,$receiver_type,$refer_sender_id,$sender_type,$referOffer->offer_type, $referOffer->offer_value, 1,$referOffer->limit,$referOffer->no_of_limit,$referOffer->no_of_day,$referOffer->day_count,$referOffer->start_date,$referOffer->end_date,$referOffer->offer_applicable);
//    }
//
//    public function AddDiscount($merchant_id,$referral_offer_id,$user_id, $receiver_type, $sender_id, $sender_type, $referral_offer, $referral_offer_value, $referral_available,$limit,$limit_usage,$no_of_day,$day_count,$start_date,$end_date,$offer_applicable)
//    {
//        ReferralDiscount::create([
//            'referral_system_id' => $referral_offer_id,
//            'merchant_id' => $merchant_id,
//            'receiver_id' => $user_id,
//            'receiver_type' => $receiver_type,
//            'sender_id' => $sender_id,
//            'sender_type' => $sender_type,
//            'limit' => $limit,
//            'limit_usage' => $limit_usage,
//            'no_of_day' => $no_of_day,
//            'day_count' => $day_count,
//            'start_date' => $start_date,
//            'end_date' => $end_date,
//            'offer_applicable' => $offer_applicable,
//            'offer_type' => $referral_offer,
//            'offer_value' => $referral_offer_value,
//            'referral_available' => $referral_available,
//        ]);
//    }

    public function InsertReward($reward_system, $user_driver, $data = [], $calling_from = null){
        switch ($calling_from) {
            case 'RATING':
                if ($reward_system->rating_reward == 1) {
                    $expire_date = !empty($reward_system->rating_expire_in_days) ? $this->getDate($reward_system->rating_expire_in_days) : null;
                    if ($reward_system->application == 1) {
                        $this->UserRewardCredit($user_driver->merchant_id, $user_driver->id, $reward_system->rating_points, $expire_date,'RATING');
                    } else {
                        $this->DriverRewardCredit($user_driver->merchant_id, $user_driver->id, $reward_system->rating_points, $expire_date,'RATING');
                    }
                }
                break;
            case 'COMMENT':
                if ($reward_system->comment_reward == 1) {
                    if (!empty($data) && str_word_count($data['comment']) >= $reward_system->comment_min_words) {
                        $expire_date = !empty($reward_system->comment_expire_in_days) ? $this->getDate($reward_system->comment_expire_in_days) : null;
                        if ($reward_system->application == 1) {
                            $this->UserRewardCredit($user_driver->merchant_id, $user_driver->id, $reward_system->comment_points, $expire_date,'COMMENT');
                        } else {
                            $this->DriverRewardCredit($user_driver->merchant_id, $user_driver->id, $reward_system->comment_points, $expire_date,'COMMENT');
                        }
                    }
                }
                break;
            case 'REFERRAL':
                if ($reward_system->referral_reward == 1) {
                    $expire_date = !empty($reward_system->referral_expire_in_days) ? $this->getDate($reward_system->referral_expire_in_days) : null;
                    if ($reward_system->application == 1) {
                        $this->UserRewardCredit($user_driver->merchant_id, $user_driver->id, $reward_system->referral_points, $expire_date,'REFERRAL');
                    } else {
                        $this->DriverRewardCredit($user_driver->merchant_id, $user_driver->id, $reward_system->referral_points, $expire_date,'REFERRAL');
                    }
                }
                break;
            case 'TRIP_EXPENSE':
                if ($reward_system->trip_expense_reward == 1) {
                    $trip_amount = $data['amount'];
                    $amount_per_point = $reward_system->amount_per_points;
                    $points = $trip_amount*$amount_per_point;
                    if ($reward_system->peak_hours == 1) {
                        $points = $this->getPeakHoursReward($reward_system, $points);
                    }
                    $expire_date = !empty($reward_system->expenses_expire_in_days) ? $this->getDate($reward_system->expenses_expire_in_days) : null;
                    if ($reward_system->application == 1) {
                        $this->UserRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date,'TRIP_EXPENSE');
                    } else {
                        $this->DriverRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date,'TRIP_EXPENSE');
                    }
                }
                else if ($reward_system->trip_expense_reward == 3) {
                    $trip_amount = $data['amount'];
                    // dd($trip_amount,(float)$reward_system->expense_amount);
                    if($trip_amount >= (float)$reward_system->expense_amount){
                        $amount_per_point = $reward_system->amount_per_points;
                        $point_against_trips = $reward_system->point_against_trips;
                        // $points = intdiv($trip_amount, $amount_per_point);
                        $points = $point_against_trips*$amount_per_point;
                        // dd($trip_amount,$amount_per_point,$points);
                        if ($reward_system->peak_hours == 1) {
                            $points = $this->getPeakHoursReward($reward_system, $points);
                        }
                        $expire_date = !empty($reward_system->expenses_expire_in_days) ? $this->getDate($reward_system->expenses_expire_in_days) : null;
                        if ($reward_system->application == 1) {
                            $this->UserRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date,'TRIP_EXPENSE');
                        } else {
                            $this->DriverRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date,'TRIP_EXPENSE');
                        }
                    }
                }else if($reward_system->trip_expense_reward == 4){
                    if ($reward_system->trips_type == 1) { // one time case
                        if (((float)$user_driver->total_trips + 1.0) == (float)$reward_system->no_of_trips) {
                            $trip_amount = $data['amount'];
                            $amount_per_point = $reward_system->amount_per_points;
                            $point_against_trips = $reward_system->point_against_trips;
                            $points = (float)$point_against_trips * (float)$amount_per_point;
                            if ($reward_system->peak_hours == 1) {
                                $points = $this->getPeakHoursReward($reward_system, $points);
                            }
                            $expire_date = !empty($reward_system->expenses_expire_in_days) ? $this->getDate($reward_system->expenses_expire_in_days) : null;
                            if ($reward_system->application == 1) {
                                $this->UserRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date, 'TRIP_EXPENSE');
                            } else {
                                $this->DriverRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date, 'TRIP_EXPENSE');
                            }
                        }
                    } else if ($reward_system->trips_type == 2) { // recurring case
                        if (((float)$user_driver->total_trips+ 1.0) % (float)$reward_system->no_of_trips == 0 && (float)$user_driver->total_trips > 0) {
                            $trip_amount = $data['amount'];
                            $amount_per_point = $reward_system->amount_per_points;
                            $point_against_trips = $reward_system->point_against_trips;
                            $points = (float)$point_against_trips * (float)$amount_per_point;
                            if ($reward_system->peak_hours == 1) {
                                $points = $this->getPeakHoursReward($reward_system, $points);
                            }
                            $expire_date = !empty($reward_system->expenses_expire_in_days) ? $this->getDate($reward_system->expenses_expire_in_days) : null;
                            if ($reward_system->application == 1) {
                                $this->UserRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date, 'TRIP_EXPENSE');
                            } else {
                                $this->DriverRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date, 'TRIP_EXPENSE');
                            }
                        }
                    }
                    
                }
                break;
            case 'ONLINE_TIME':
                if ($reward_system->online_time_reward == 1) {
                    $online_time = $data['online_time'];
                    $points_per_hour = $reward_system->points_per_hour;
                    $points = intdiv($online_time, $points_per_hour);
                    $expire_date = !empty($reward_system->online_time_expire_in_days) ? $this->getDate($reward_system->online_time_expire_in_days) : null;
                    if ($reward_system->application == 1) {
                        $this->UserRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date,'ONLINE_TIME');
                    } else {
                        $this->DriverRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date,'ONLINE_TIME');
                    }
                }
                break;
            case 'COMMISSION_PAID':
                if ($reward_system->commission_paid_reward == 1) {
                    $commission_amount = $data['commission_amount'];
                    $amount_per_point = $reward_system->commission_amount_per_point;
                    $points = intdiv($commission_amount, $amount_per_point);
                    if ($reward_system->peak_hours == 1) {
                        $points = $this->getPeakHoursReward($reward_system, $points);
                    }
                    $expire_date = !empty($reward_system->commission_expire_in_days) ? $this->getDate($reward_system->commission_expire_in_days) : null;
                    if ($reward_system->application == 1) {
                        $this->UserRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date,'COMMISSION_PAID');
                    } else {
                        $this->DriverRewardCredit($user_driver->merchant_id, $user_driver->id, $points, $expire_date,'COMMISSION_PAID');
                    }
                }
                break;
        }
    }

    public function getDate($days){
        $date = date('Y-m-d');
        $newDate = date('Y-m-d', strtotime("$date +$days days"));
//        echo $date.'    --    '.$newDate;die();
        return $newDate;
    }

    public function UserRewardCredit($merchant_id, $user_id, $points, $expire_date,$calling_from){
        UserRewardPoint::create([
            'merchant_id' => $merchant_id,
            'user_id' => $user_id,
            'reward_points' => $points,
            'remain_reward_point' => $points,
            'expire_date' => $expire_date,
            'calling_from' => $calling_from,
            'status' => 1,
        ]);
    }

    public function DriverRewardCredit($merchant_id, $driver_id, $points, $expire_date,$calling_from){
        DriverRewardPoint::create([
            'merchant_id' => $merchant_id,
            'driver_id' => $driver_id,
            'reward_points' => $points,
            'remain_reward_point' => $points,
            'expire_date' => $expire_date,
            'calling_from' => $calling_from,
            'status' => 1,
        ]);
    }

    public function getPeakHoursReward($reward_system, $points){
        $time = date('H:i');
        $updated_point = $points;
        $slab_data = json_decode($reward_system->slab_data, true);
        foreach ($slab_data as $slab) {
            if ((strtotime($time) >= strtotime($slab['slab_from'])) && (strtotime($time) <= strtotime($slab['slab_to']))) {
                $updated_point = $slab['peak_points_collection'] * $points;
            }
        }
        return $updated_point;
    }

    public function checkRewardSystem($merchant_id,$application,$country_id = null,$country_area_id = null,$action = null){
        $query = RewardSystem::where([['merchant_id','=',$merchant_id],['application','=',$application]]);
        switch ($action){
            case 'RATING':
                $query->where('rating_reward',1);
                break;
            case 'COMMENT':
                $query->where('comment_reward',1);
                break;
            case 'REFERRAL':
                $query->where('referral_reward',1);
                break;
            case 'TRIP_EXPENSE':
               $query->where(function ($q) {
                   $q->where('trip_expense_reward', 1)
                      ->orWhere('trip_expense_reward', 3)
                      ->orWhere('trip_expense_reward', 4);
                });
                break;
            case 'ONLINE_TIME':
                $query->where('online_time_reward',1);
                break;
            case 'COMMISSION_PAID':
                $query->where('commission_paid_reward',1);
                break;
            case 'PEAK_HOURS':
                $query->where('peak_hours',1);
                break;
        }
        if (!empty($country_id)){
            $query->where('country_id',$country_id);
        }
        if (!empty($country_area_id)){
            $query->where('country_area_id',$country_area_id);
        }
        $reward_system = $query->first();
        return $reward_system;
    }

    public function GlobalReward($merchant_id,$application,$user_driver,$country_id = null,$country_area_id = null,$action = null,$data = [],$return_reward_system = false){
        $reward_system = $this->checkRewardSystem($merchant_id,$application,$country_id,$country_area_id,$action);
        if (!empty($reward_system)){
            if($return_reward_system){
                return $reward_system;
            }else{
                $this->InsertReward($reward_system,$user_driver,$data,$action);
            }
        }
    }
}
