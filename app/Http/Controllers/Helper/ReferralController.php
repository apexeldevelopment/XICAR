<?php

namespace App\Http\Controllers\Helper;


use App\Models\Application;
use App\Models\Booking;
use App\Models\BusinessSegment\Order;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\HandymanOrder;
use App\Models\ReferralDiscount;
use App\Models\ReferralDriverDiscount;
use App\Models\ReferralSystem;
use App\Models\ReferralUserDiscount;
use App\Models\User;
use App\Traits\MerchantTrait;
use App\Http\Controllers\Controller;
use DB;
use Carbon\Carbon;

class ReferralController extends Controller
{
    use MerchantTrait;

    private $segment_id = null;
    private $booking_id = null;
    private $order_id = null;
    private $handyman_order_id = null;
    private $check_referral_at = null;

    public function checkForReferral($referral_code, $merchant_id, $country_id, $country_area_id, $check_for = "USER", $check_both = true)
    {
        try {
            $string_file = $this->getStringFile($merchant_id);
            $config = Configuration::where("merchant_id",$merchant_id)->first();
            $whereCondition = [['merchant_id', '=', $merchant_id], ['country_id', '=', $country_id]];
            if(!empty($country_area_id) && $check_for == "DRIVER"){
                $whereCondition[] =['country_area_id','=',$country_area_id];
            }

            if (!empty($referral_code)) {
                // Drier which is completed all the signup steps
                $whereDriver = Driver::where($whereCondition)->where([['driver_referralcode', '=', $referral_code], ['signupStep', '=', 9], ['driver_delete', '=', NULL]])->exists();
                // $request->request->remove('api_token');
                unset($whereCondition[2]); // remove country area id in case of user referral code
                $whereUser = User::where($whereCondition)->where([['ReferralCode', '=', $referral_code], ['user_delete', '=', NULL]])->exists();
                // $condition = ($check_for == "USER") ? $whereUser : $whereDriver;
                // if (!$whereUser || !$whereDriver) {
                if(empty($whereDriver) && empty($whereUser)){
                    throw new \Exception(trans("$string_file.referral") . " " . trans("$string_file.code") . " " . trans("$string_file.invalid"));
                }
//                if($check_both && !$whereUser && !$whereDriver){
//                    throw new \Exception(trans("$string_file.referral") . " " . trans("$string_file.code") . " " . trans("$string_file.invalid"));
//                }
            } else {
                // if (
                //     ($config->referral_code_mandatory_user_signup == 1 && $check_for == "USER") ||
                //     ($config->referral_code_mandatory_driver_signup == 1 && $check_for == "DRIVER")
                // ) {
                //     throw new \Exception(trans("$string_file.referral") . " " . trans("$string_file.code") . " " . trans("$string_file.invalid"));
                // }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage()); // "Check Referral :- " .
        }
    }

    public function getOfferDetails($referral_code, $merchant_id, $country_id, $country_area_id, $check_for = "USER")
    {
        try {
            $offer_details = [];
            $sender_type = [];
            $sender_details = [];
            $whereCondition = [['merchant_id', '=', $merchant_id], ['country_id', '=', $country_id], ['country_area_id', '=', $country_area_id]];
            $driver = Driver::where($whereCondition)->where([['driver_referralcode', '=', $referral_code], ['signupStep', '=', 9], ['driver_delete', '=', NULL]])->first();
            unset($whereCondition[2]);
            $user = User::where($whereCondition)->where([['ReferralCode', '=', $referral_code], ['user_delete', '=', NULL]])->first();
            // Drier which is completed all the signup steps

            if (!empty($user)) {
                $offer_details = ReferralSystem::where($whereCondition)->where([['status', '=', 1]])
                    ->where(function($p){
                        $p->where('application',1)->orWhere('application',3);
                    })->latest()->first();
                $sender_type = "USER";
                $sender_details = $user;
            } elseif (!empty($driver)) {
                $offer_details = ReferralSystem::where($whereCondition)->where([['status', '=', 1]])
                    ->where(function($q){
                        $q->where('application',2)->orWhere('application',3);
                    })->latest()->first();
                $sender_type = "DRIVER";
                $sender_details = $driver;
            }
            return array("referral_system" => $offer_details, "sender_type" => $sender_type, "sender_details" => $sender_details);
        } catch (\Exception $e) {
            throw new \Exception("Get Offer Details :- " . $e->getMessage());
        }
    }

    public function giveReferralToUser($referral_code, $receiver_user, $merchant_id, $country_id, $country_area_id)
    {
        try {
            $this->checkForReferral($referral_code, $merchant_id, $country_id, $country_area_id, "USER");
            $offer_details = $this->getOfferDetails($referral_code, $merchant_id, $country_id, $country_area_id, 'USER');
            if (!empty($offer_details['referral_system']) && !empty($offer_details['sender_type']) && !empty($offer_details['sender_details'])) {
                ReferralDiscount::create([
                    'referral_system_id' => $offer_details['referral_system']->id,
                    'merchant_id' => $merchant_id,
                    'receiver_id' => $receiver_user->id,
                    'receiver_type' => "USER",
                    'sender_id' => $offer_details['sender_details']->id,
                    'sender_type' => $offer_details['sender_type'],
                    'offer_condition' => $offer_details['referral_system']->offer_condition,
                    'offer_applicable' => $offer_details['referral_system']->offer_applicable,
                    'start_date' => $offer_details['referral_system']->start_date,
                    'end_date' => $offer_details['referral_system']->end_date,
                    'offer_type' => $offer_details['referral_system']->offer_type,
                    'offer_value' => $offer_details['referral_system']->offer_value,
                    'maximum_offer_amount' => $offer_details['referral_system']->maximum_offer_amount,
                    'offer_condition_data' => $offer_details['referral_system']->offer_condition_data,
                    'offer_condition_data_initial' => $offer_details['referral_system']->offer_condition_data,
                    'referral_available' => 1,
                ]);
            } else {
                $log_data = array(
                    'data' => "ReferralCode : $referral_code, MerchantId : $merchant_id, Country : $country_id, CountryArea : $country_area_id",
                    'additional_notes' => "get Offer Details User Not Found"
                );
                $this->referralLog($log_data);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage()); // "Give Referral to User :- " .
        }
    }

    public function giveReferralToDriver($referral_code, $receiver_driver, $merchant_id, $country_id, $country_area_id)
    {
        try {
            $this->checkForReferral($referral_code, $merchant_id, $country_id, $country_area_id, "DRIVER");
            $offer_details = $this->getOfferDetails($referral_code, $merchant_id, $country_id, $country_area_id, 'DRIVER');
            if (!empty($offer_details['referral_system']) && !empty($offer_details['sender_type']) && !empty($offer_details['sender_details'])) {
                ReferralDiscount::create([
                    'referral_system_id' => $offer_details['referral_system']->id,
                    'merchant_id' => $merchant_id,
                    'receiver_id' => $receiver_driver->id,
                    'receiver_type' => "DRIVER",
                    'sender_id' => $offer_details['sender_details']->id,
                    'sender_type' => $offer_details['sender_type'],
                    'offer_condition' => $offer_details['referral_system']->offer_condition,
                    'offer_applicable' => $offer_details['referral_system']->offer_applicable,
                    'start_date' => $offer_details['referral_system']->start_date,
                    'end_date' => $offer_details['referral_system']->end_date,
                    'offer_type' => $offer_details['referral_system']->offer_type,
                    'offer_value' => $offer_details['referral_system']->offer_value,
                    'maximum_offer_amount' => $offer_details['referral_system']->maximum_offer_amount,
                    'offer_condition_data' => $offer_details['referral_system']->offer_condition_data,
                    'offer_condition_data_initial' => $offer_details['referral_system']->offer_condition_data,
                    'referral_available' => 1,
                ]);
            } else {
                $log_data = array(
                    'data' => "ReferralCode : $referral_code, MerchantId : $merchant_id, Country : $country_id, CountryArea : $country_area_id",
                    'additional_notes' => "get Offer Details Driver Not Found"
                );
                $this->referralLog($log_data);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage()); //"Give Referral to Driver :- " .
        }
    }

//   First Calling Function
    public function giveReferral($referral_code, $receiver, $merchant_id, $country_id, $country_area_id, $check_for)
    {
        try {
            if (!in_array($check_for, ["USER", "DRIVER"])) {
                throw new \Exception("Check Referral :- Invalid check for");
            }
            $config = Configuration::where("merchant_id",$merchant_id)->first();
            // p($merchant_id);
            if ($config->referral_code_enable == 1) {
                if ($check_for == "USER") {
                    $this->giveReferralToUser($referral_code, $receiver, $merchant_id, $country_id, $country_area_id);
                } elseif ($check_for == "DRIVER") {
                    $this->giveReferralToDriver($referral_code, $receiver, $merchant_id, $country_id, $country_area_id);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param $search_for_id - Search id for driver or user
     * @param $search_for - SENDER/RECEIVER
     * @param $type - USER/DRIVER
     * @return array
     */
    public function getReferral($search_for_id, $search_for, $type)
    {
        $referral_discount = [];
        if ($search_for == "RECEIVER") {
            $referral_discount = ReferralDiscount::where([['receiver_id', '=', $search_for_id], ['receiver_type', '=', $type], ['referral_available', '=', 1]])->first();
        } elseif ($search_for == "SENDER") {
            $referral_discount = ReferralDiscount::where([['sender_id', '=', $search_for_id], ['sender_type', '=', $type], ['referral_available', '=', 1]])->first();
        }
        if(!empty($referral_discount) && isset($referral_discount->getReferralSystem) && !empty($referral_discount->getReferralSystem) && $referral_discount->getReferralSystem->end_date >= date("Y-m-d") && $referral_discount->getReferralSystem->status == 1){
            return $referral_discount;
        }else{
            return [];
        }
        return $referral_discount;
    }

    // Second Calling Function
    public function checkReferral($arr_params)
    {
        $driver_id = isset($arr_params["driver_id"]) ? $arr_params["driver_id"] : NULL;
        $user_id = isset($arr_params["user_id"]) ? $arr_params["user_id"] : NULL;
        $user_paid_amount = isset($arr_params["user_paid_amount"]) ? $arr_params["user_paid_amount"] : 0;
        $driver_paid_amount = isset($arr_params["driver_paid_amount"]) ? $arr_params["driver_paid_amount"] : 0;
        $this->segment_id = isset($arr_params["segment_id"]) ? $arr_params["segment_id"] : NULL;
        $this->booking_id = isset($arr_params["booking_id"]) ? $arr_params["booking_id"] : NULL;
        $this->order_id = isset($arr_params["order_id"]) ? $arr_params["order_id"] : NULL;
        $this->handyman_order_id = isset($arr_params["handyman_order_id"]) ? $arr_params["handyman_order_id"] : NULL;
        $this->check_referral_at = isset($arr_params["check_referral_at"]) ? $arr_params["check_referral_at"] : "OTHER";

        try {
            $driverReferDiscount = $this->getReferral($driver_id, "RECEIVER", "DRIVER");
            if (!empty($driverReferDiscount)) {
                $referCalculationAmount = $this->getReferCalculation($driverReferDiscount, $driver_paid_amount, "DRIVER");
            }
            $userReferDiscount = $this->getReferral($user_id, "RECEIVER", "USER");
            if (!empty($userReferDiscount)) {
                $referCalculationAmount = $this->getReferCalculation($userReferDiscount, $user_paid_amount, "USER");
            }
//            p($referCalculationAmount);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getReferCalculation($referral_discount, $amount, $receiver_type = NULL)
    {
        $refer_amount = 0;
        $referral_offer_value = $referral_discount->offer_value;

        //for offer condition 5 we need user's specific offer value
        if($receiver_type=="USER" && $referral_discount->offer_condition == 5 ){
            $offer_condition_data = json_decode($referral_discount->offer_condition_data, true);
            $referral_offer_value = $offer_condition_data['user_offer_value'];
        }


        switch ($referral_discount->offer_type) {
            case "1":  // Fixed
                if ($amount > 0 && $amount < $referral_offer_value) {
                    $refer_amount = $amount;
                } else {
                    $refer_amount = $referral_offer_value;
                }
                break;
            case "2":  // Discount
                $refer_amount = ($amount * $referral_offer_value) / 100;
                if (!empty($referral_discount->maximum_offer_amount) && $referral_discount->maximum_offer_amount > 0) {
                    if ($refer_amount > $referral_discount->maximum_offer_amount) {
                        $refer_amount = $referral_discount->maximum_offer_amount;
                    }
                }
                break;
        }
        $amount = $this->getReferralOfferCalculation($referral_discount, $refer_amount, $amount);
        return $amount;
    }

    public function getReferralOfferCalculation($referral_discount, $refer_amount, $amount)
    {
        switch ($referral_discount->offer_condition) {
            case "1": // Limited
                if (!empty($this->check_referral_at) && $this->check_referral_at == "OTHER") {
                    if ($this->segment_id == null) {
                        throw new \Exception("Segment id required for check referral discount");
                    } else {
//                accessible key in offer_condition_data : day_limit, day_count, limit_usage
                        $eligible_segments_for_referral = [];
                        if (isset($referral_discount->getReferralSystem->Segment)) {
                            $eligible_segments_for_referral = $referral_discount->getReferralSystem->Segment->pluck("id")->toArray();
                        }
                        if ($this->segment_id != null && !empty($eligible_segments_for_referral) && in_array($this->segment_id, $eligible_segments_for_referral)) {
                            $offer_condition_data = json_decode($referral_discount->offer_condition_data, true);
                            $no_of_day = $offer_condition_data['day_limit'];
                            if (!empty($offer_condition_data['limit_usage']) && $offer_condition_data['limit_usage'] > 0) {
                                $limit = $offer_condition_data['limit_usage'];
                                // day_count : 1 - After Signup, 2 - After First Financial Transaction
                                if (!empty($offer_condition_data['day_count']) && $offer_condition_data['day_count'] == 1) {
                                    if ($referral_discount->receiver_type == "USER") {
                                        $getData = User::find($referral_discount->receiver_id);
                                    } else {
                                        $getData = Driver::find($referral_discount->receiver_id);
                                    }
                                    $first_date = $getData->created_at;
                                    $last_date = date('Y-m-d', strtotime($first_date . '+' . $no_of_day . ' days'));
                                    if (date('Y-m-d') <= $last_date) {
                                        $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                                        $limit--;
                                        if ($limit == 0) {
                                            $referral_discount->referral_available = 2;
                                        }
                                    } else {
                                        $limit = 0;
                                        $referral_discount->referral_available = 2;
                                    }
                                    $offer_condition_data['limit_usage'] = $limit;
                                    $referral_discount->offer_condition_data = json_encode($offer_condition_data);
                                    $referral_discount->save();
                                } elseif (!empty($offer_condition_data['day_count']) && $offer_condition_data['day_count'] == 2) {
                                    $keyWord = $referral_discount->receiver_type == "USER" ? 'user_id' : 'driver_id';
                                    $financial_data = $this->checkForFirstFinancialTransaction($keyWord, $referral_discount->receiver_id, $eligible_segments_for_referral);
                                    if (!empty($financial_data)) {
                                        $first_date = $financial_data['date'];
                                        $last_date = date('Y-m-d', strtotime($first_date . '+' . $no_of_day . ' days'));
                                        if (date('Y-m-d') < $last_date) {
                                            $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                                            $limit--;
                                            if ($limit == 0) {
                                                $referral_discount->referral_available = 2;
                                            }
                                        } else {
                                            $limit = 0;
                                            $referral_discount->referral_available = 2;
                                        }
                                        $offer_condition_data['limit_usage'] = $limit;
                                        $referral_discount->offer_condition_data = json_encode($offer_condition_data);
                                        $referral_discount->save();
                                    }
                                } else {
                                    $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                                    $limit--;
                                    if ($limit == 0) {
                                        $referral_discount->referral_available = 2;
                                    }
                                    $offer_condition_data['limit_usage'] = $limit;
                                    $referral_discount->offer_condition_data = json_encode($offer_condition_data);
                                    $referral_discount->save();
                                }
                            } else {
                                // day_count : 1 - After Signup, 2 - After First Financial Transaction
                                if (!empty($offer_condition_data['day_count']) && $offer_condition_data['day_count'] == 1) {
                                    if ($referral_discount->receiver_type == "USER") {
                                        $getData = User::find($referral_discount->receiver_id);
                                    } else {
                                        $getData = Driver::find($referral_discount->receiver_id);
                                    }
                                    $first_date = $getData->created_at;
                                    $last_date = date('Y-m-d', strtotime($first_date . '+' . $no_of_day . ' days'));
                                    if (date('Y-m-d') < $last_date) {
                                        $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                                    } else {
                                        $referral_discount->referral_available = 2;
                                        $referral_discount->save();
                                    }
                                } elseif (!empty($referral_discount['day_count']) && $referral_discount['day_count'] == 2) {
                                    $keyWord = $referral_discount->receiver_type == "USER" ? 'user_id' : 'driver_id';
                                    $financial_data = $this->checkForFirstFinancialTransaction($keyWord, $referral_discount->receiver_id, $eligible_segments_for_referral);
                                    if (!empty($financial_data)) {
                                        $first_date = $financial_data['date'];
                                        $last_date = date('Y-m-d', strtotime($first_date . '+' . $no_of_day . ' days'));
                                        if (date('Y-m-d') < $last_date) {
                                            $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                                        } else {
                                            $referral_discount->referral_available = 2;
                                            $referral_discount->save();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case "2": // Unlimited
                if (!empty($this->check_referral_at) && $this->check_referral_at == "OTHER") {
                    $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                }
                break;
            case "3": // Signup Only
                if (!empty($this->check_referral_at) && $this->check_referral_at == "SIGNUP") {
                    $amount = $refer_amount;
                    $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                    $referral_discount->referral_available = 2;
                    $referral_discount->save();
                }
                break;
            case "4": // Conditional (No of Driver register with no of rides)
                if (!empty($this->check_referral_at) && ($this->check_referral_at == "OTHER" || $this->check_referral_at == "SIGNUP" || $this->check_referral_at == "COMPLETE-SIGNUP")) {
                    $offer_condition_data = json_decode($referral_discount->offer_condition_data, true);
                    $conditional_no_driver = $offer_condition_data['conditional_no_driver'];
                    $conditional_no_services = $offer_condition_data['conditional_no_services'];
                    $conditional_driver_rule = $offer_condition_data['conditional_driver_rule'];
                    $eligible_segments_for_referral = [];
                    if (isset($referral_discount->getReferralSystem->Segment)) {
                        $eligible_segments_for_referral = $referral_discount->getReferralSystem->Segment->pluck("id")->toArray();
                    }
                    if (!empty($conditional_driver_rule)) {
                        // Get Total referred count of sender
                        $referred = ReferralDiscount::where([
                            ["sender_type", "=", $referral_discount->sender_type],
                            ["sender_id", "=", $referral_discount->sender_id],
                            ["merchant_id", "=", $referral_discount->merchant_id],
                        ])->orderBy("id")->get(); // ->limit($conditional_no_driver)
                        $total_referred = $referred->count();
                        if ($conditional_driver_rule == 1 && $this->check_referral_at == "SIGNUP") { // After Basic Signup
                            if (!empty($conditional_no_driver) && !empty($total_referred) && $total_referred >= $conditional_no_driver) {
                                $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                            }
                        } elseif ($conditional_driver_rule == 2 && $this->check_referral_at == "COMPLETE-SIGNUP") {  // After Complete Signup
                            if (!empty($conditional_no_driver) && !empty($total_referred) && $total_referred >= $conditional_no_driver) {
                                $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                            }
                        } elseif ($conditional_driver_rule == 3 && $this->check_referral_at == "OTHER") {   // After Complete No of services
                            if (!empty($conditional_no_driver) && !empty($total_referred) && $total_referred >= $conditional_no_driver) {
                                $service_eligibility = false;
                                $service_complete_referral = 0;
                                foreach($referred as $referred_obj){
                                    $keyWord = $referred_obj->receiver_type == "USER" ? 'user_id' : 'driver_id';
                                    $total_done_services = $this->checkNoOfServicesDone($keyWord, $referred_obj->receiver_id, $eligible_segments_for_referral);
//                                    p($total_done_services);
                                    if($total_done_services['total'] >= $conditional_no_services){
                                        $service_complete_referral += 1;
                                    }
//                                    if($total_done_services['total'] >= $conditional_no_services){
//                                        $service_eligibility = true;
//                                    }else{
//                                        $service_eligibility = false;
//                                        break;
//                                    }
                                    if($service_complete_referral >= $conditional_no_driver){
                                        $service_eligibility = true;
                                        break;
                                    }
                                }
//                                dd($service_eligibility);
                                if ($service_eligibility) {
                                    $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                                }
                            }
                        }
                    }
                }
                break;
            case "5":
                //@ayush
                //Triggered after the driver is approved && User has successfully completed their first ride
                // OTHER => end Booking
                // COMPLETE-SIGNUP => when driver approves
                //in case 5 user_offer_value is also be taken from driver

                $eligible_segments_for_referral = [];
                if (isset($referral_discount->getReferralSystem->Segment)) {
                    $eligible_segments_for_referral = $referral_discount->getReferralSystem->Segment->pluck("id")->toArray();
                }

                if (!empty($this->check_referral_at) && in_array($this->check_referral_at, ["COMPLETE-SIGNUP", "OTHER"])) {
                    $shouldSave = false;
                    $amount = $refer_amount;

                    if ($this->check_referral_at === "OTHER") {
                        $financial_data = $this->checkForFirstFinancialTransaction("user_id", $referral_discount->receiver_id, $eligible_segments_for_referral);
                        if (isset($financial_data['data'])) {
                            if (!empty($financial_data['data'])) {
                                $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                                $shouldSave = true;
                            }

                        }
                    } elseif ($this->check_referral_at === "COMPLETE-SIGNUP") {
                        $amount = $this->ReferralOfferCalculation($referral_discount, $refer_amount, $amount);
                        $shouldSave = true;
                    }

                    if ($shouldSave) {
                        $referral_discount->referral_available = 2;
                        $referral_discount->save();
                    }
                }
                break;
        }
        return $amount;
    }

    public function ReferralOfferCalculation($referral_discount, $refer_amount, $amount)
    {
        try {
            // offer_applicable = 1 : Sender,2 : Receiver,3 : Both
            $isReferralNull = false;
            // Either Sender or Both
            if (in_array($referral_discount->offer_applicable, [1, 3])) {
                if ($referral_discount->sender_type == "USER") {
                    $this->UserReferDiscount($referral_discount->sender_id, $refer_amount);
                    $this->referralAmountCreditToWallet("USER", $referral_discount->sender_id, $refer_amount,$referral_discount->referral_system_id);
                } elseif ($referral_discount->sender_type == "DRIVER") {
                    $this->DriverReferDiscount($referral_discount->sender_id, $refer_amount);
                    $this->referralAmountCreditToWallet("DRIVER", $referral_discount->sender_id, $refer_amount,$referral_discount->referral_system_id);
                }
            }
            // Either Receiver or Both
            if (in_array($referral_discount->offer_applicable, [2, 3])) {
                if ($referral_discount->receiver_type == "USER") {
                    $this->UserReferDiscount($referral_discount->receiver_id, $refer_amount);
                    $this->referralAmountCreditToWallet("USER", $referral_discount->receiver_id, $refer_amount,$referral_discount->referral_system_id);
                } elseif ($referral_discount->receiver_type == "DRIVER") {
                    $this->DriverReferDiscount($referral_discount->receiver_id, $refer_amount);
                    $this->referralAmountCreditToWallet("DRIVER", $referral_discount->receiver_id, $refer_amount,$referral_discount->referral_system_id);
                }
            }
            // $referral_discount->offer_condition == 5 (means driver to driver also driver to user ) code can be used
            if($referral_discount->offer_applicable == 4){
                if ($referral_discount->sender_type == "DRIVER" && $referral_discount->receiver_type == "USER") {
                    // $this->UserReferDiscount($referral_discount->receiver_id, $refer_amount);
                    // $this->referralAmountCreditToWallet("USER", $referral_discount->receiver_id, $refer_amount,$referral_discount->referral_system_id);
                    $this->DriverReferDiscount($referral_discount->sender_id, $refer_amount);
                    $this->referralAmountCreditToWallet("DRIVER", $referral_discount->sender_id, $refer_amount,$referral_discount->referral_system_id);
                } elseif ($referral_discount->sender_type == "DRIVER" && $referral_discount->receiver_type == "DRIVER") {
                    $this->DriverReferDiscount($referral_discount->sender_id, $refer_amount);
                    $this->referralAmountCreditToWallet("DRIVER", $referral_discount->sender_id, $refer_amount,$referral_discount->referral_system_id);
                }
            }
            return array('amount' => $amount, 'refer_amount' => $refer_amount);
        } catch (\Exception $e) {
            throw new \Exception("ReferralOfferCalculation : " . $e->getMessage());
        }
    }

    public function referralAmountCreditToWallet($send_to, $id, $refer_amount,$referral_system_id)
    {
        $paramArray = array(
            'booking_id' => $this->booking_id,
            'order_id' => $this->order_id,
            'handyman_order_id' => $this->handyman_order_id,
            'amount' => $refer_amount,
            'platform' => 2,
            'payment_method' => 1,
        );
        if($refer_amount > 0){
            if ($send_to == "USER") {
                $paramArray['user_id'] = $id;
                $paramArray['narration'] = 16;
                $paramArray['receipt'] = " ##".$referral_system_id;
                WalletTransaction::UserWalletCredit($paramArray);
            } elseif ($send_to == "DRIVER") {
                $paramArray['driver_id'] = $id;
                $paramArray['narration'] = 22;
                $paramArray['receipt'] = " ##".$referral_system_id;
                WalletTransaction::WalletCredit($paramArray);
            }
        }
    }

    public function DriverReferDiscount($id, $amount)
    {
        $senderData = Driver::find($id);
        ReferralDriverDiscount::create([
            'merchant_id' => $senderData->merchant_id,
            'driver_id' => $id,
            'booking_id' => $this->booking_id,
            'order_id' => $this->order_id,
            'handyman_order_id' => $this->handyman_order_id,
            'amount' => $amount,
            'payment_status' => 1,
            'expire_status' => 0
        ]);
    }

    public function UserReferDiscount($id, $amount)
    {
        $userData = User::find($id);
        if (!empty($userData)) {
            ReferralUserDiscount::create([
                'merchant_id' => $userData->merchant_id,
                'user_id' => $id,
                'booking_id' => $this->booking_id,
                'order_id' => $this->order_id,
                'handyman_order_id' => $this->handyman_order_id,
                'amount' => $amount,
            ]);
        }
    }

    public function checkForFirstFinancialTransaction($check_for, $id, $segment_list = [])
    {
        //$check_for   :   user_id, driver_id
        $transaction = [];
        $get_booking = Booking::where([[$check_for, '=', $id], ['booking_status', '=', 1005]])->whereIn("segment_id", $segment_list)->oldest()->first();
        $get_order = Order::where([[$check_for, '=', $id], ['order_status', '=', 11]])->whereIn("segment_id", $segment_list)->oldest()->first();
        $get_handyman_order = HandymanOrder::where([[$check_for, '=', $id], ['order_status', '=', 7]])->whereIn("segment_id", $segment_list)->oldest()->first();
        $booking_first_data = !empty($get_booking) ? $get_booking->created_at : null;
        $order_first_data = !empty($get_order) ? $get_order->created_at : null;
        $handyman_order_first_data = !empty($get_handyman_order) ? $get_handyman_order->created_at : null;
        $dates_arr = array_filter(array($booking_first_data, $order_first_data, $handyman_order_first_data));
        if (!empty($dates_arr)) {
            $earliest_date = min($dates_arr);
            if ($earliest_date == $booking_first_data) {
                $transaction["segment_id"] = $get_booking->segment_id;
                $transaction["date"] = $get_booking->created_at->toDateTimeString();
                $transaction["data"] = $get_booking;
            } elseif ($earliest_date == $order_first_data) {
                $transaction["segment_id"] = $get_order->segment_id;
                $transaction["date"] = $get_order->created_at->toDateTimeString();
                $transaction["data"] = $get_order;
            } elseif ($earliest_date == $handyman_order_first_data) {
                $transaction["segment_id"] = $get_handyman_order->segment_id;
                $transaction["date"] = $get_handyman_order->created_at->toDateTimeString();
                $transaction["data"] = $get_handyman_order;
            }
        }
        return $transaction;
    }

    public function checkNoOfServicesDone($check_for, $id, $segment_list = [])
    {
        //$check_for   :   user_id, driver_id
        $get_bookings = Booking::where(function($query) use($check_for, $id){
            $query->where([[$check_for, '=', $id], ['booking_status', '=', 1005]]);
            if($this->booking_id != ""){
                $query->orWhere([[$check_for, '=', $id], ['booking_status', '=', 1004], ['id', '=', $this->booking_id]]);
            }
        })->whereIn("segment_id", $segment_list)->get()->count();
        $get_orders = Order::where([[$check_for, '=', $id], ['order_status', '=', 11]])->whereIn("segment_id", $segment_list)->get()->count();
        $get_handyman_orders = HandymanOrder::where([[$check_for, '=', $id], ['order_status', '=', 7]])->whereIn("segment_id", $segment_list)->get()->count();
        $counts['bookings'] = $get_bookings;
        $counts['orders'] = $get_orders;
        $counts['handyman_orders'] = $get_handyman_orders;
        $counts['total'] = $get_bookings + $get_orders + $get_handyman_orders;
        return $counts;
    }

    protected function referralLog($data, $type = "info")
    {
        $log_data = array(
            'request_type' => 'Referral Request',
            'request_data' => $data['data'],
            'additional_notes' => $data['additional_notes'],
            'hit_time' => date('Y-m-d H:i:s')
        );
        if ($type == "info") {
            \Log::channel('referral_log')->info(json_encode($log_data));
        } else {
            \Log::channel('referral_log')->error(json_encode($log_data));
        }
    }

    public function getReferralDetailsForApp($check_for, $id, $request_for)
    {
        $user = ($check_for == "USER") ? User::find($id) : Driver::find($id);
        $application = ($check_for == "USER") ? 1 : 2;
        $referral_code_key = ($check_for == "USER") ? "ReferralCode" : "driver_referralcode";
        $merchant_id = $user->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $referral = ReferralSystem::where([['merchant_id', '=', $merchant_id], ['country_id', '=', $user->country_id], ['country_area_id', '=', $user->country_area_id], ['application', '=', $application], ['status', '=', 1]])->first();
        $application = Application::where([['merchant_id', '=', $merchant_id]])->first();
        if ($check_for == "USER") {
            $iosUser = $application ? $application->ios_user_link : "";
            $androidUser = $application ? $application->android_user_link : "";
            $msg = trans("$string_file.user_referral_msg");
            $message = str_replace("% S", "%s",$msg);
        } else {
            $iosUser = $application ? $application->ios_driver_link : "";
            $androidUser = $application ? $application->android_driver_link : "";
            $msg = trans("$string_file.driver_referral_msg");
            $message = str_replace("% S", "%s",$msg);
        }
        $heading = trans("$string_file.referral_code");
        $description = trans("$string_file.refer_code_message");
        $refer_offer = "--";

        //commenting because both devices needs both app url links for now
        // if($request_for == "ANDROID")
        //     $sharing_text= sprintf($message, $user->Merchant->BusinessName, $user->$referral_code_key, $androidUser, "");
        // elseif($request_for == "IOS")
        //     $sharing_text = sprintf($message, $user->Merchant->BusinessName, $user->$referral_code_key, $iosUser, "");
        // else
            $sharing_text = sprintf($message, $user->Merchant->BusinessName, $user->$referral_code_key, $androidUser, $iosUser);

        if (!empty($referral)) {
            switch ($referral->offer_type) {
                case "1":
//                    $offer_type = trans("$string_file.free_ride");
                    $currency = isset($referral->Country) ? $referral->Country->isoCode : $referral->CountryArea->Country->isoCode;
                    $refer_offer = $currency." ".$referral->offer_value;
                    break;
                case "2":
//                    $offer_type = trans("$string_file.discount");
                    $refer_offer = $referral->offer_value." % ".trans("$string_file.discount");
                    break;
                default:
//                    $offer_type = "--";
                    $refer_offer = "--";
            }
            $data = array(
                "refer_image" => "refer.png",
                "refer_heading" => $heading,
                "refer_explanation" => $description,
                "start_date" => $referral->start_date,
                "end_date" => $referral->end_date,
                "refer_code" => $user->$referral_code_key,
                "refer_status" => $referral->status = 1 ? "ACTIVE" : "INACTIVE",
//                "refer_offer" => $referral->offer_value . " " . $offer_type,
                "refer_offer" => $refer_offer,
                "sharing_text" => $sharing_text,
                "dynamic_link" => !empty($referral->firebase_url) ? $referral->firebase_url : "",
            );
        } else {
            $data = array(
                "refer_image" => "refer.png",
                "refer_heading" => $heading,
                "refer_explanation" => $description,
                "start_date" => "",
                "end_date" => "",
                "refer_code" => $user->$referral_code_key,
                "refer_status" => "DEACTIVE",
                "refer_offer" => "",
                "sharing_text" => $sharing_text,
                "dynamic_link" => "",
            );
        }
        return $data;
    }

    public function getDriverReferEarning($merchant_id, $driver_id, $from, $to)
    {
        $data = ReferralDriverDiscount::where([['merchant_id', '=', $merchant_id], ['driver_id', '=', $driver_id]])->whereBetween('created_at', array($from, $to))->sum('amount');
        return $data;
    }

    // public function getReferralDiscountExcelData($merchant_id){
    //     $referral_details = ReferralDiscount::where([['merchant_id','=',$merchant_id]])->groupBy('sender_id')->latest()->get();
    //     foreach ($referral_details as $referral_detail){
    //         $senderDetails = $referral_detail->sender_type == "USER" ? User::find($referral_detail->sender_id) : Driver::find($referral_detail->sender_id);
    //         if (!empty($senderDetails)){
    //             $phone = $referral_detail->sender_type == "USER" ? $senderDetails->UserPhone : $senderDetails->phoneNumber;
    //             $senderType = $referral_detail->sender_type == "USER" ? 'User' : 'Driver';
    //             $referral_detail->sender_details =  $senderDetails->first_name.' '.$senderDetails->last_name.' ('.$phone.') ('. $senderDetails->email.') (Type : '.$senderType.')';
    //             $referReceivers = ReferralDiscount::where([['merchant_id','=',$merchant_id],['sender_id','=',$referral_detail->sender_id]])->latest()->get();
    //             $receiverBasic = array();
    //             foreach ($referReceivers as $referReceiver){
    //                 $receiverDetails = $referReceiver->receiver_type == "USER" ? User::find($referReceiver->receiver_id) : Driver::find($referReceiver->receiver_id);
    //                 if (!empty($receiverDetails)){
    //                     $phone = $referReceiver->receiver_type == "USER" ? $receiverDetails->UserPhone : $receiverDetails->phoneNumber;
    //                     $receiverType = $referReceiver->receiver_type == "USER" ? 'User' : 'Driver';
    //                     $receiverBasic[] =  $receiverDetails->first_name.' '.$receiverDetails->last_name.' ('.$phone.') ('.$receiverDetails->email.') (Type : '.$receiverType.')';
    //                 }
    //             }
    //             $referral_detail->total_refer = count($receiverBasic);
    //             $referral_detail->receiver_details = implode(',',$receiverBasic);
    //         }
    //     }
    //     return $referral_details;
    // }

    public function getReferralDiscountExcelData($merchant_id,$request) {
        $start_date = $request['start'];
        $end_date = $request['end'];
        $referral_code = $request['referral_code'];
        $start = NULL;
        $end = NULL;
        if(!empty($start_date) && !empty($end_date)){
            $start = \Carbon\Carbon::createFromFormat('Y/m/d', $start_date)->startOfDay();
            $end = \Carbon\Carbon::createFromFormat('Y/m/d', $end_date)->endOfDay();
        }
        
    $referral_details = ReferralDiscount::where('merchant_id', $merchant_id)
        ->when(!empty($start) && !empty($end), function ($query) use ($start, $end) {
            return $query->whereBetween('created_at', [$start, $end]);
        })
        ->when($referral_code, function ($query) use ($referral_code) {
                $query->where(function ($q) use ($referral_code) {
                    $q->whereIn('sender_type', ['USER', 'DRIVER'])
                        ->where(function ($subQuery) use ($referral_code) {
                            $subQuery->whereExists(function ($userQuery) use ($referral_code) {
                                $userQuery->select(DB::raw(1))
                                    ->from('users')
                                    ->whereColumn('users.id', 'referral_discounts.sender_id')
                                    ->where('users.ReferralCode', $referral_code);
                            })->orWhereExists(function ($driverQuery) use ($referral_code) {
                                $driverQuery->select(DB::raw(1))
                                    ->from('drivers')
                                    ->whereColumn('drivers.id', 'referral_discounts.sender_id')
                                    ->where('drivers.driver_referralcode', $referral_code);
                            });
                        });
                });
            })
        ->groupBy('sender_id')
        ->latest()
        ->get();
        
    $string_file = $this->getStringFile($merchant_id);

    $finalData = [];

    foreach ($referral_details as $referral_detail) {
        $senderDetails = $referral_detail->sender_type == "USER" 
            ? User::find($referral_detail->sender_id) 
            : Driver::find($referral_detail->sender_id);

        if (!empty($senderDetails)) {
            $phone = $referral_detail->sender_type == "USER" 
                ? $senderDetails->UserPhone 
                : $senderDetails->phoneNumber;

            $senderType = $referral_detail->sender_type == "USER" ? 'User' : 'Driver';

            // Sender info
            $senderInfo = [
                'sender_id' => $referral_detail->sender_id,
                'sender_name' => $senderDetails->first_name . ' ' . $senderDetails->last_name,
                'sender_phone' => $phone,
                'sender_email' => $senderDetails->email,
                'sender_type' => $senderType,
                'referral_code'=> $referral_detail->sender_type == "USER" ? $senderDetails->ReferralCode : $senderDetails->driver_referralcode,
                 'start_date'=> $referral_detail->start_date,
                 'end_date'=> $referral_detail->end_date,
                 'created_at'=> convertTimeToUSERzone($referral_detail->created_at,$referral_detail->getReferralSystem->CountryArea->timezone,$referral_detail->merchant_id),
            ];
            

            // Get all receivers for this sender
            $referReceivers = ReferralDiscount::where([
                ['merchant_id', '=', $merchant_id],
                ['sender_id', '=', $referral_detail->sender_id]
            ])->latest()->get();

            // Loop through receivers and add each as a separate row
            foreach ($referReceivers as $referReceiver) {
                $receiverDetails = $referReceiver->receiver_type == "USER" 
                    ? User::find($referReceiver->receiver_id) 
                    : Driver::find($referReceiver->receiver_id);

                if (!empty($receiverDetails)) {
                    $receiverPhone = $referReceiver->receiver_type == "USER" 
                        ? $receiverDetails->UserPhone 
                        : $receiverDetails->phoneNumber;

                    $receiverType = $referReceiver->receiver_type == "USER" ? 'User' : 'Driver';
                    $signup_status = "---------";
                            if($receiverType != "USER"){
                                switch ($receiverDetails->signupStep){
                                    case "1":
                                    case "2":
                                        $signup_status = trans("$string_file.basic_signup_completed");
                                        break;
                                    case '3':
                                        $signup_status = trans("$string_file.personal")." ". trans("$string_file.document")." ".trans("$string_file.pending");
                                        break;
                                    case '4':
                                        $signup_status = trans("$string_file.vehicle")." ". trans("$string_file.not_added");
                                        break;
                                    case '5':
                                        $signup_status = trans("$string_file.vehicle")." ". trans("$string_file.document")." ".trans("$string_file.pending");
                                        break;
                                    case '6':
                                        $signup_status = trans("$string_file.vehicle")." ". trans("$string_file.services_configuration")." ".trans("$string_file.not_added");
                                        break;
                                    case '8':
                                        $signup_status = trans("$string_file.pending_driver_approval");
                                        if($receiverDetails->reject_driver == 2){
                                            $signup_status = trans("$string_file.rejected");
                                        }
                                        break;
                                    case 9:
                                        $signup_status = trans("$string_file.completed");
                                        break;
                                }

                                if($receiverDetails->signupStep == 8 && $receiverDetails->reject_driver == 1 && $receiverDetails->is_approved == 2){
                                    $signup_status = trans("$string_file.pending_driver_approval");
                                }
                                elseif($receiverDetails->signupStep == 8 && $receiverDetails->reject_driver == 1 && $receiverDetails->is_approved == 1 && ($receiverDetails->in_training == 1 || $receiverDetails->in_training == 3)){
                                    $signup_status = trans("$string_file.pending")." ".trans("$string_file.training");
                                }
                                elseif($receiverDetails->signupStep == 8 && $receiverDetails->reject_driver == 2){
                                    $signup_status = trans("$string_file.driver")." ".trans("$string_file.rejected");
                                }
                                if($receiverDetails->driver_delete == 1){
                                    $signup_status = trans("$string_file.driver")." ".trans("$string_file.deleted");
                                }
                            }
                    $receiverInfo = [
                        'receiver_id' => $referReceiver->receiver_id,
                        'receiver_name' => $receiverDetails->first_name . ' ' . $receiverDetails->last_name .'('. $receiverPhone .')',
                        'receiver_phone' => $receiverPhone,
                        'receiver_email' => $receiverDetails->email,
                        'receiver_type' => $receiverType,
                        'signup_status'=> $signup_status,
                        'deleted'=> $referReceiver->receiver_type == "DRIVER" ? ($receiverDetails->driver_delete == 1 ? "YES" : "NO") : "----",
                        'receiver_created_at'=>convertTimeToUSERzone($referReceiver->created_at,$referral_detail->getReferralSystem->CountryArea->timezone,$referral_detail->merchant_id,null, 2),
                        'updated_at'=> $referReceiver->receiver_type == "DRIVER" ? convertTimeToUSERzone($receiverDetails->updated_at,$referral_detail->getReferralSystem->CountryArea->timezone,$referral_detail->merchant_id, null, 2) : "----"
                    ];
                    // Add both sender & receiver as one row
                    $finalData[] = array_merge($senderInfo, $receiverInfo);
                }
            }
        }
    }

    // dd($finalData);
    return $finalData;
}


    public function getDriversWithoutReferall($merchant_id): array
    {
        $driverIds = Driver::where('merchant_id', $merchant_id)
            ->whereNull("driver_delete")
            ->whereDoesntHave('ReferralDiscounts', function ($query) {
                $query->where('receiver_type', 'DRIVER');
            })
            ->distinct()
            ->pluck("id");
        $drivers = Driver::whereIn('id', $driverIds)
            ->orderBy('id', 'DESC')
            ->get();
        $string_file = $this->getStringFile($merchant_id);
        $signup_status = "---------";
        $return_array = [];
        $driverInfo= [];
        foreach ($drivers as $driver){
            switch ($driver->signupStep){
                case "1":
                case "2":
                    $signup_status = trans("$string_file.basic_signup_completed");
                    break;
                case '3':
                    $signup_status = trans("$string_file.personal")." ". trans("$string_file.document")." ".trans("$string_file.pending");
                    break;
                case '4':
                    $signup_status = trans("$string_file.vehicle")." ". trans("$string_file.not_added");
                    break;
                case '5':
                    $signup_status = trans("$string_file.vehicle")." ". trans("$string_file.document")." ".trans("$string_file.pending");
                    break;
                case '6':
                    $signup_status = trans("$string_file.vehicle")." ". trans("$string_file.services_configuration")." ".trans("$string_file.not_added");
                    break;
                case '8':
                    $signup_status = trans("$string_file.pending_driver_approval");
                    if($driver->reject_driver == 2){
                        $signup_status = trans("$string_file.rejected");
                    }
                    break;
                case '9':
                    $signup_status = trans("$string_file.completed");
                    break;
            }
            $driverInfo[] = [
                "name"=> $driver->first_name." ".$driver->last_name,
                "phone"=>$driver->phoneNumber,
                "created_at"=>($driver->CountryArea) ? convertTimeToUSERzone($driver->created_at,$driver->CountryArea->timezone,$driver->merchant_id,null, 2): $driver->created_at->toDateString(),
                "signup_status" => $signup_status,
            ];
        }
        return array_merge($driverInfo, $return_array);
    }


    public function testReferralSystem()
    {
        DB::beginTransaction();
        try {
//            $ref_code_driver = Driver::find(539);
            // $driver_one = Driver::find(3777);
//            $driver_two = Driver::find(3778);
            // $driver_three = Driver::find(3779);
//            $this->giveReferral($ref_code_driver->driver_referralcode, $driver_one, $driver_one->merchant_id, $driver_one->country_id, $driver_one->country_area_id, "DRIVER");
//            $this->giveReferral($ref_code_driver->driver_referralcode, $driver_two, $driver_two->merchant_id, $driver_two->country_id, $driver_two->country_area_id, "DRIVER");
//            $this->giveReferral($ref_code_driver->driver_referralcode, $driver_three, $driver_three->merchant_id, $driver_three->country_id, $driver_three->country_area_id, "DRIVER");
            // $booking = Booking::find(6853);
            // $arr_params = array(
            //     "driver_id" => $driver_three->id,
            //     "user_id" => null,
            //     "booking_id" => null,
            //     "order_id" => null,
            //     "handyman_id" => null,
            //     "check_referral_at" => "OTHER",
            //     "user_paid_amount" => $booking->final_amount_paid,
            //     "driver_paid_amount" => $booking->company_cut,
            // );
            // $this->checkReferral($arr_params);
//
//            $referral = ReferralSystem::find(9);
//            $segment_ids = $referral->Segment->pluck("id")->toArray();
//            p($this->checkForFirstFinancialTransaction('user_id', 462, $segment_ids));
//
//            $ref_code_user = User::find(489);
//            $user = User::find(488);
//            $booking = Booking::find(57);
//            $this->giveReferral($ref_code_user->ReferralCode, $user, $user->merchant_id, $user->country_id, $user->country_area_id, "USER");
//            $arr_params = array(
//                "segment_id" => $booking->segment_id,
//                "driver_id" => $booking->driver_id,
//                "user_id" => $user->id,
//                "booking_id" => $booking->id,
//                "order_id" => null,
//                "handyman_id" => null,
//                "user_paid_amount" => $booking->final_amount_paid,
//                "driver_paid_amount" => $booking->company_cut,
//                "check_referral_at" => "OTHER"
//            );
//            $this->checkReferral($arr_params);
//            $arr_params = array(
//                "user_id" => $user->id,
//                "check_referral_at" => "SIGNUP"
//            );
//            $this->checkReferral($arr_params);
//            DB::commit();
            p("Finish");
        } catch (\Exception $e) {
            DB::rollBack();
            p($e->getMessage());
        }
    }
}
