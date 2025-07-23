<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\DriverRedeemedReward;
use App\Models\DriverRewardPoint;
use App\Models\RewardGift;
use App\Models\UserRedeemedReward;
use App\Models\UserRewardPoint;
use App\Traits\ApiResponseTrait;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RewardGiftController extends Controller
{
    use ImageTrait, ApiResponseTrait;
    public function getRewardGiftList(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required'
        ]);

        if ($validator->fails()){
            $msg = $validator->errors();
            return $this->failedResponse($msg[0]);
        }

        $user_driver = $request->type == 1 ? $request->user('api') : $request->user('api-driver');
        $country_id = $request->type == 1 ? $user_driver->country_id : $user_driver->CountryArea->Country->id;
        $merchant_id = $user_driver->merchant_id;
        $reward_list = RewardGift::where([['merchant_id','=',$user_driver->merchant_id],['country_id','=',$country_id],['application','=',$request->type],['delete_status','=',NULL]])->get();
        $reward_list = array_map(function($item) use ($merchant_id){
            $data['id'] = $item['id'];
            $data['name'] = $item['name'];
            $data['image'] = get_image($item['image'],'reward_gift',$merchant_id);
            $data['reward_points'] = $item['reward_points'];
            $data['rides'] = $item['rides'];
            $data['amount'] = $item['amount'];
            $data['comment'] = $item['comment'];
            return $data;
        },$reward_list->toArray());

        if ($request->type == 1){
            $get_total_points = UserRewardPoint::where([['merchant_id','=',$merchant_id],['user_id','=',$user_driver->id]])->whereIn('status',[1,2])->sum('remain_reward_point');
        }else{
            $get_total_points = DriverRewardPoint::where([['merchant_id','=',$merchant_id],['driver_id','=',$user_driver->id]])->whereIn('status',[1,2])->sum('remain_reward_point');
        }


        return response()->json(['result' => '1','message' => 'Reward List','data' => $reward_list,'total_trips' => $user_driver->total_trips-$user_driver->use_reward_trip_count,'reward_points' => $get_total_points]);
    }

    public function RedeemRewardGift(Request $request){
        $validator = Validator::make($request->all(),[
            // 'reward_gift_id' => 'required',
            'redeem_points' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()){
            $msg = $validator->errors();
            return response()->json(['result' => '0','message' => $msg[0]]);
        }

        $get_available_reward = $this->getAvailableRewardPoints($request);
        if($request->redeem_points > $get_available_reward){
            return response()->json(['result' => '0','message' => "You Have Only ".$get_available_reward." Reward Points to Redeem!"]);
        }
        // $reward_gift = RewardGift::find($request->reward_gift_id);
        // if (empty($reward_gift)){
        //     return response()->json(['result' => '0','message' => 'No Data Found']);
        // }

        if ($request->type == 1){
            // $this->RedeemUserReward($reward_gift,$request->user('api'));
            $this->RedeemUserReward($request->redeem_points,$request->user('api'));
        }else{
            // $this->RedeemDriverReward($reward_gift,$request->user('api-driver'));
            $this->RedeemDriverReward($request->redeem_points,$request->user('api-driver'));
        }
        return response()->json(['result' => '1','message' => 'Reward Gift Redeemed']);
    }

    public function RedeemUserReward($redeem_points,$user){
        DB::beginTransaction();
        try {
            // $pending_points = $reward_gift->reward_points;
            $pending_points = $redeem_points;
            // $rides = $reward_gift->rides;
            $rides = 0;
            // dd($pending_points,$rides);
            $user_reward_points = UserRewardPoint::where([['merchant_id','=',$user->merchant_id],['user_id','=',$user->id]])->whereIn('status',[1,2])->orderBy('expire_date','ASC')->get();
            // p('pending point -> '.$pending_points,0);
            foreach ($user_reward_points as $reward_point){
                if($pending_points > 0){
                    if($reward_point->status == 2){
                        $use_reward = $pending_points >= $reward_point->remain_reward_point ? $reward_point->remain_reward_point : $pending_points;
                    }else{
                        $use_reward = $pending_points >= $reward_point->reward_points ? $reward_point->reward_points : $pending_points;
                    }
                    $total_use = $reward_point->used_reward_point+$use_reward;
                    // p('use reward -> '.$use_reward,0);
                    // p('total use -> '.$total_use,0);
                    // p('remain reward point -> '.($reward_point->reward_points-$total_use),0);
                    $reward_point->used_reward_point = $total_use;
                    $reward_point->remain_reward_point = $reward_point->reward_points-$total_use;
                    $reward_point->status = $total_use == $reward_point->reward_points ? 3 : 2;
                    $reward_point->save();
                    $pending_points -= $use_reward;
                    // p('pending point -> '.$pending_points,0);
                    // p('----',0);
                }
            }
            // die('end');
            $user->use_reward_trip_count = $user->use_reward_trip_count+$rides;
            // $user->wallet_balance += $redeem_points;
            $user->save();

            $receipt = "Reward Redeemed!";
            $paramArray = array(
                'user_id' => $user->id,
                'booking_id' => NULL,
                'amount' => $redeem_points,
                'narration' => 2,
                'platform' => 2,
                'payment_method' => 2,
                'receipt' => $receipt,
                'transaction_id' => NULL,
                'notification_type' => 3
            );
            WalletTransaction::UserWalletCredit($paramArray);

            // UserRedeemedReward::create([
            //     'merchant_id' => $user->merchant_id,
            //     'user_id' => $user->id,
            //     'reward_gift_id' => $reward_gift->id
            // ]);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['result' => '1','message' => $e->getMessage()]);
        }
        DB::commit();
    }

    public function RedeemDriverReward($redeem_points,$driver){
        DB::beginTransaction();
        try {
            // $pending_points = $reward_gift->reward_points;
            $pending_points = $redeem_points;
            // $rides = $reward_gift->rides;
            $rides = 0;
            $user_reward_points = DriverRewardPoint::where([['merchant_id','=',$driver->merchant_id],['driver_id','=',$driver->id]])->whereIn('status',[1,2])->orderBy('expire_date','ASC')->get();
            foreach ($user_reward_points as $reward_point){
                if($pending_points > 0){
                    if($reward_point->status == 2){
                        $use_reward = $pending_points >= $reward_point->remain_reward_point ? $reward_point->remain_reward_point : $pending_points;
                    }else{
                        $use_reward = $pending_points >= $reward_point->reward_points ? $reward_point->reward_points : $pending_points;
                    }
                    $total_use = $reward_point->used_reward_point+$use_reward;
                    $reward_point->used_reward_point = $total_use;
                    $reward_point->remain_reward_point = $reward_point->reward_points-$total_use;
                    $reward_point->status = $total_use == $reward_point->reward_points ? 3 : 2;
                    $reward_point->save();
                    $pending_points -= $use_reward;
                }
            }
            $driver->use_reward_trip_count = $driver->use_reward_trip_count+$rides;
            // $driver->wallet_money += $redeem_points;
            $driver->save();

            $receipt = "Reward Redeemed!";
            $paramArray = array(
                'driver_id' => $driver->id,
                'booking_id' => NULL,
                'amount' => $redeem_points,
                'narration' => 2,
                'platform' => 2,
                'payment_method' => 2,
                'receipt' => $receipt,
                'transaction_id' => NULL,
                'notification_type' => 3
            );
            WalletTransaction::WalletCredit($paramArray);

            // DriverRedeemedReward::create([
            //     'merchant_id' => $driver->merchant_id,
            //     'driver_id' => $driver->id,
            //     'reward_gift_id' => $reward_gift->id
            // ]);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['result' => '1','message' => $e->getMessage()]);
        }
        DB::commit();
    }

    public function checkEligibleRewardGift(Request $request){
        $validator = Validator::make($request->all(),[
            'reward_gift_id' => 'required',
            'type' => 'required'
        ]);

        if ($validator->fails()){
            $msg = $validator->errors();
            return response()->json(['result' => '0','message' => $msg[0]]);
        }

        $reward_gift = RewardGift::find($request->reward_gift_id);
        if (empty($reward_gift)){
            return response()->json(['result' => '0','message' => 'No Data Found']);
        }

        if ($request->type == 1){
            $user = $request->user('api');
            // dd($user);
            $total_trips = $user->total_trips-$user->use_reward_trip_count;
            $get_total_points = UserRewardPoint::where([['merchant_id','=',$user->merchant_id],['user_id','=',$user->id]])->whereIn('status',[1,2])->sum('remain_reward_point');
        }else{
            $driver = $request->user('api-driver');
            $total_trips = $driver->total_trips-$driver->use_reward_trip_count;
            $get_total_points = DriverRewardPoint::where([['merchant_id','=',$driver->merchant_id],['driver_id','=',$driver->id]])->whereIn('status',[1,2])->sum('remain_reward_point');
        }
        // dd($get_total_points,$reward_gift->reward_points,$total_trips,$reward_gift->rides);
        $reward_status = 2;
        if (!empty($reward_gift->reward_points)){
            $reward_status = $reward_gift->reward_points <= $get_total_points ? 1 : 2;
        }else{
            $reward_status = 1;
        }

        $ride_status = 2;
        if(!empty($reward_gift->rides)){
            $ride_status = $reward_gift->rides <= $total_trips ? 1 : 2;
        }else{
            $ride_status = 1;
        }

        $result = ($reward_status == 1 && $ride_status == 1) ? '1' : '0';
        $message = ($reward_status == 1 && $ride_status == 1) ? 'Eligible For Gift' : 'You are not eligible for Reward Gift yet. Please complete the required criteria first!';
        return response()->json(['result' => $result, 'message' => $message]);
    }

    public function getRewardHistory(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
        ]);

        if ($validator->fails()){
            $msg = $validator->errors();
            return response()->json(['result' => '0','message' => $msg[0]]);
        }

        if ($request->type == 1){
            $user = $request->user('api');
            $reward_data = UserRewardPoint::where([['merchant_id','=',$user->merchant_id],['user_id','=',$user->id]])->orderBy('expire_date','ASC')->get();
            $total_rewards = UserRewardPoint::where([['merchant_id','=',$user->merchant_id],['user_id','=',$user->id]])->whereIn('status',[1,2])->sum('remain_reward_point');
            $total_trips = $user->total_trips-$user->use_reward_trip_count;
        }else{
            $driver = $request->user('api-driver');
            $reward_data = DriverRewardPoint::where([['merchant_id','=',$driver->merchant_id],['driver_id','=',$driver->id]])->orderBy('expire_date','ASC')->get();
            $total_rewards = DriverRewardPoint::where([['merchant_id','=',$driver->merchant_id],['driver_id','=',$driver->id]])->whereIn('status',[1,2])->sum('remain_reward_point');
            $total_trips = $driver->total_trips-$driver->use_reward_trip_count;
        }
        $reward_data = $reward_data->map(function ($key){
            $key->used_reward_point = !empty($key->used_reward_point) ? $key->used_reward_point : "0";
            return $key;
        });
        
        return response()->json(['result' => '1','message' => 'Reward Point List','data' => $reward_data,'total_rewards' => $total_rewards,'total_rewards_formattted' => number_format($total_rewards,4),'total_trips' => $total_trips]);
    }

    public function getRedeemedGifts(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
        ]);

        if ($validator->fails()){
            $msg = $validator->errors();
            return response()->json(['result' => '0','message' => $msg[0]]);
        }

        if ($request->type == 1){
            $user = $request->user('api');
            $redeemed_rewards = UserRedeemedReward::where([['merchant_id','=',$user->merchant_id],['user_id','=',$user->id]])->orderBy('id','DESC')->get();
        }else{
            $driver = $request->user('api-driver');
            $redeemed_rewards = DriverRedeemedReward::where([['merchant_id','=',$driver->merchant_id],['driver_id','=',$driver->id]])->orderBy('id','DESC')->get();
        }
        $data = [];
        foreach($redeemed_rewards as $key => $reward){
            $data[$key] = [
                'id' => $reward->id,
                'name' => $reward->RewardGift->name,
                'image' => get_image($reward->RewardGift->image,'reward_gift',$reward->merchant_id),
                'amount' => $reward->RewardGift->amount,
                'note' => $reward->notes,
                'date' => date('Y-m-d',strtotime($reward->created_at)),
            ];
        }
        return response()->json(['result' => '1','message' => 'Redeemed Rewards List','data' => $data]);
    }

    public function getAvailableRewardPoints($request){
        if ($request->type == 1){
            $id = $request->user('api')->id;
            $merchant_id = $request->user('api')->merchant_id;
            $get_total_points = UserRewardPoint::where([['merchant_id','=',$merchant_id],['user_id','=',$id]])->whereIn('status',[1,2])->sum('remain_reward_point');
        }else{
            $id = $request->user('api-driver')->id;
            $merchant_id = $request->user('api-driver')->merchant_id;
            $get_total_points = DriverRewardPoint::where([['merchant_id','=',$merchant_id],['driver_id','=',$id]])->whereIn('status',[1,2])->sum('remain_reward_point');
        }
        return $get_total_points;
    }
}
