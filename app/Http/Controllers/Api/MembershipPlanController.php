<?php
namespace App\Http\Controllers\BusinessSegment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingTransaction;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\BusinessSegment\Order;
use App\Models\BusinessSegment\Product;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Validator;
use DB;
use App\Traits\OrderTrait;
use App\Models\MerchantMembershipPlan;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Traits\ApiResponseTrait;
class MembershipPlanController extends Controller
{
    use MerchantTrait,ApiResponseTrait;
    public function checkMembership(){
        $bs = get_business_segment(false);
        $merchant_id = $bs->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        if($bs->order_based_on == 2){
            if(empty($bs->wallet_money)){
                return response()->json([
                    'status' => 'error',
                    'message' => trans("$string_file.buy_subscription"),
                    'id'=> $bs->id
                ]);
            }elseif($bs->subscription_expired == 1 || $bs->subscription_expired == 0){
                return response()->json([
                    'status' => 'error',
                    'message' =>  trans("$string_file.subscription_expired_to_renew"),
                    'id'=> $bs->id
                ]);
            }
            
        }
    }
    public function purchaseMembership(){
        $bs = get_business_segment(false);
        $merchant_id = $bs->merchant_id;
        $membershipPlan = $bs->Merchant->MerchantMembershipPlan;
        if($bs->order_based_on == 2 && ($bs->membership_plan_id || $bs->membership_plan_id !=0) && ($bs->subscription_expired == 2)){
            $membershipPlanId = $bs->membership_plan_id;
            $storePlan = MerchantMembershipPlan::find($membershipPlanId);
            $period = $storePlan->period;  //in days
            $subscriptionDate = $bs->subscription_date_timestamp;
            $date = \Carbon\Carbon::createFromTimestamp($subscriptionDate);
            $expiryDateForSubscription = date('Y-m-d H:i:s', $date->addDays($period)->timestamp);
            $currentTimestamp = time();
            $all_orders = Order::select('id','business_segment_id','merchant_id','service_time_slot_detail_id','order_timestamp','country_area_id','driver_id','order_status','segment_id','user_id','order_date')
                            ->whereIn('order_status', [1])->where('merchant_id',$merchant_id)->where('order_timestamp','>',$subscriptionDate)->get();
            $countOrders = count($all_orders);
            return view('business-segment.membership-plan.membership-form', compact('membershipPlanId', 'membershipPlan','countOrders','expiryDateForSubscription'));
        }else{
            return view('business-segment.membership-plan.membership-form', compact('membershipPlan'));
        }
        
    }
    public function savePurchaseMembershipPlan(Request $request){
        $validator = Validator::make($request->all(), [
            'membership_plan_id' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        $bs = get_business_segment(false);
        $merchant_id = $bs->merchant_id;
        $membershipPlanId = $bs->membership_plan_id;
        $membershipPlan = $bs->Merchant->MerchantMembershipPlan;
        $string_file = $this->getStringFile($merchant_id);
        if(($bs->membership_plan_id || $bs->membership_plan_id !=0) && ($bs->subscription_expired == 2)){
            $storePlan = MerchantMembershipPlan::find($membershipPlanId);
            $period = $storePlan->period;  //in days
            $subscriptionDate = $bs->subscription_date_timestamp;
            $date = \Carbon\Carbon::createFromTimestamp($subscriptionDate);
            $expiryDateForSubscription = date('Y-m-d H:i:s', $date->addDays($period)->timestamp);
            $currentTimestamp = time();
            $all_orders = Order::select('id','business_segment_id','merchant_id','service_time_slot_detail_id','order_timestamp','country_area_id','driver_id','order_status','segment_id','user_id','order_date')
                            ->whereIn('order_status', [1])->where('merchant_id',$merchant_id)->where('order_timestamp','>',$subscriptionDate)->get();
            $countOrders = count($all_orders);
            return view('business-segment.membership-plan.membership-form', compact('membershipPlanId', 'membershipPlan','countOrders','expiryDateForSubscription'));
        }else{
            if($bs->wallet_amount > $request->price){
                $bs->membership_plan_id = $request->membership_plan_id;
                $bs->subscription_date_timestamp = time();
                $bs->subscription_expired = 2;  //1:expired subscription 2: not expired
                $bs->save();
                $paramArray = array(
                    'business_segment_id' => $bs->id,
                    'order_id' => NULL,
                    'amount' => $request->price,
                    'narration' => 6,
                    'platform' => 1,
                    'payment_method' => 'WALLET',
                    'receipt' => 'RECEIPT_' . time(),
                    'action_merchant_id' => $merchant_id
                );
                WalletTransaction::BusinessSegmntWalletDebit($paramArray);
                $membershipPlan = $bs->Merchant->MerchantMembershipPlan;
                $membershipPlanId = $bs->membership_plan_id;
                $storePlan = MerchantMembershipPlan::find($membershipPlanId);
                $period = $storePlan->period;  //in days
                $subscriptionDate = $bs->subscription_date_timestamp;
                $date = \Carbon\Carbon::createFromTimestamp($subscriptionDate);
                $expiryDateForSubscription = date('Y-m-d H:i:s', $date->addDays($period)->timestamp);
                $currentTimestamp = time();
                $all_orders = Order::select('id','business_segment_id','merchant_id','service_time_slot_detail_id','order_timestamp','country_area_id','driver_id','order_status','segment_id','user_id','order_date')
                                ->whereIn('order_status', [1])->where('merchant_id',$merchant_id)->where('order_timestamp','>',$subscriptionDate)->get();
                $countOrders = count($all_orders);
                $bs->order_based_on = 2;
                $bs->save();
                return view('business-segment.membership-plan.membership-form', compact('membershipPlan','membershipPlanId','countOrders','expiryDateForSubscription'));
            }else{
                return redirect()->back()->withErrors(trans("$string_file.low_wallet_warning"));
            }
        }
        
        
    }
    public function getMembershipPlanDetails(Request $request)
    {
        $planId = $request->plan_id;
        $plan = MerchantMembershipPlan::find($planId);
        if ($plan) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'period' => $plan->period,
                    'order_limit' => $plan->number_of_order,
                    'price' => $plan->price,
                    'max_amount_valid'=> $plan->max_amount_valid
                ]
            ]);
        } else {
            return response()->json(['success' => false]);
        }
    }
  
}