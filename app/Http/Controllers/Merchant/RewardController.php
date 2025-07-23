<?php

namespace App\Http\Controllers\Merchant;

use App\Models\CountryArea;
use App\Models\Country;
use App\Models\RewardSystem;
use Auth;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\RewardPoint;
use App\Models\ApplicationConfiguration;
use Illuminate\Support\Facades\DB;
use App\Models\InfoSetting;

class RewardController extends Controller
{
    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','REWARD_POINT')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $checkPermission =  check_permission(1,'reward_points');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        // $app_config = ApplicationConfiguration:: where('merchant_id', $merchant_id)->first();
        // if ($app_config->reward_points != 1) {
        //     die('unauthorized');
        // }

        $merchant = Merchant::find($merchant_id);
        $rewards = $merchant->rewardPoints;
        $reward_system = RewardSystem::where('merchant_id',$merchant_id)->paginate(10);
        $data = [];
        return view('merchant.reward.index', compact('rewards','reward_system','data'));
    }

    public function create()
    {
        $checkPermission =  check_permission(1,'reward_points');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $country_areas = CountryArea:: where('merchant_id', $merchant_id)->get();
        $countries = Country::where('merchant_id', $merchant_id)->get();
        return view('merchant.reward.create', compact('country_areas','countries'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $app_config = ApplicationConfiguration:: where('merchant_id', $merchant_id)->first();
        if ($app_config->reward_points != 1) {
            die('unauthorized');
        }

        $request->validate([
            'user_registration_reward' => 'required|numeric',
            'driver_registration_reward' => 'required|numeric',
            'country_area' => 'required',
            'user_referral_reward' => 'required|numeric',
            'driver_referral_reward' => 'required|numeric',
            'trips_count' => 'required|numeric',
            'max_redeem' => 'required|numeric',
            'value_equals' => 'required|numeric',
        ]);

        // create reward point
        $reward = RewardPoint:: updateOrCreate([
            'merchant_id' => $merchant_id,
        ], [
            'registration_enable' => $request->registration_enable,
            'country_area_id' => $request->country_area,
            'user_registration_reward' => ($request->user_registration_reward) ? $request->user_registration_reward : 0,
            'driver_registration_reward' => ($request->driver_registration_reward) ? $request->driver_registration_reward : 0,
            'referral_enable' => $request->referral_enable,
            'user_referral_reward' => ($request->user_referral_reward) ? $request->user_referral_reward : 0,
            'driver_referral_reward' => ($request->driver_referral_reward) ? $request->driver_referral_reward : 0,
            'trips_count' => $request->trips_count,
            'max_redeem' => $request->max_redeem,
            'value_equals' => $request->value_equals,
            'active' => 1
        ]);

        if ($reward) {
            return redirect()->back()->with('reward', __('admin.reward.added'));
        }

        return redirect()->back()->withInput()->with('reward', __('admin.swr'));
    }

    public function edit($id)
    {
        $checkPermission =  check_permission(1,'reward_points');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        // $app_config = ApplicationConfiguration:: where('merchant_id', $merchant_id)->first();
        // if ($app_config->reward_points != 1) {
        //     die('unauthorized');
        // }
        $country_areas = CountryArea:: where('merchant_id', $merchant_id)->get();
        $countries = Country::where('merchant_id', $merchant_id)->get();
        $reward_system = RewardSystem::find($id);
        $reward = RewardPoint:: where('merchant_id', $merchant_id)->where('id', $id)->first();
        return view('merchant.reward.edit', compact('reward', 'country_areas','reward_system','countries'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $app_config = ApplicationConfiguration:: where('merchant_id', $merchant_id)->first();
        if ($app_config->reward_points != 1) {
            die('unauthorized');
        }

        $request->validate([
            'country_area' => 'required',
            'trips_count' => 'required|numeric',
            'max_redeem' => 'required|numeric',
            'value_equals' => 'required|numeric',
        ]);
        
        

        // create reward point
        $reward = RewardPoint:: where('id', $id)->where('merchant_id', $merchant_id)->update([
            'registration_enable' => $request->registration_enable,
            'country_area_id' => $request->country_area,
            'user_registration_reward' => ($request->user_registration_reward) ? $request->user_registration_reward : 0,
            'driver_registration_reward' => ($request->driver_registration_reward) ? $request->driver_registration_reward : 0,
            'referral_enable' => $request->referral_enable,
            'user_referral_reward' => ($request->user_referral_reward) ? $request->user_referral_reward : 0,
            'driver_referral_reward' => ($request->driver_referral_reward) ? $request->driver_referral_reward : 0,
            'trips_count' => $request->trips_count,
            'max_redeem' => $request->max_redeem,
            'value_equals' => $request->value_equals,
            'active' => $request->active,
        ]);

        if ($reward) {
            return redirect()->back()->with('reward', __('admin.reward.updated'));
        }

        return redirect()->back()->withInput()->with('reward', __('admin.swr'));
    }

    public function destroy($id)
    {
        $reward = RewardSystem::find($id);
        if ($reward->delete()) {
            return redirect()->back()->with('reward', __('admin.deleted.successfully'));
        }
        return redirect()->back()->with('reward', __('admin.swr'));
    }

    public function SaveRewardSystem(Request $request){
        DB::beginTransaction();
        try {
            // dd($request->trips_select);
            $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
            $submit_data = [
                'merchant_id' => $merchant_id,
                'country_id' => !empty($request->country) ? $request->country : null,
                'country_area_id' => !empty($request->country_area) ? $request->country_area : null,
                'application' => $request->application,
            ];
            if ($request->rating == 1){
                $submit_data['rating_reward'] = $request->rating;
                $submit_data['rating_points'] = $request->rating_reward;
                $submit_data['rating_expire_in_days'] = $request->rating_expire_in_days;
            }
            if ($request->writing_comment == 1){
                $submit_data['comment_reward'] = $request->writing_comment;
                $submit_data['comment_min_words'] = $request->comment_min_words;
                $submit_data['comment_points'] = $request->comment_reward;
                $submit_data['comment_expire_in_days'] = $request->comment_expire_in_days;
            }
            if ($request->referral == 1){
                $submit_data['referral_reward'] = $request->referral;
                $submit_data['referral_points'] = $request->referral_reward;
                $submit_data['referral_expire_in_days'] = $request->referral_expire_in_days;
            }
            if ($request->trips_select == 1){
                $submit_data['trip_expense_reward'] = $request->trips_select;
                $submit_data['amount_per_points'] = $request->per_point_amount;
                $submit_data['expenses_expire_in_days'] = $request->expenses_expire_in_days;
            }
            if($request->trips_select == 3){
                $submit_data['trip_expense_reward'] = $request->trips_select;
                $submit_data['amount_per_points'] = $request->per_point_amount;
                $submit_data['expenses_expire_in_days'] = $request->expenses_expire_in_days;
                $submit_data['expense_amount'] = (float)$request->trip_expense_amount;
                $submit_data['point_against_trips'] = $request->point_against_trips;
            }
            if($request->trips_select == 4){
                $submit_data['trip_expense_reward'] = $request->trips_select;
                $submit_data['amount_per_points'] = $request->per_point_amount;
                $submit_data['expenses_expire_in_days'] = $request->expenses_expire_in_days;
                $submit_data['no_of_trips'] = (float)$request->no_of_trips;
                $submit_data['point_against_trips'] = $request->point_against_trips;
                $submit_data['trips_type'] = $request->trips_type;
            }
            if ($request->online_time == 1){
                $submit_data['online_time_reward'] = $request->online_time;
                $submit_data['points_per_hour'] = $request->hours_per_point;
                $submit_data['online_time_expire_in_days'] = $request->online_time_expire_in_days;
            }
            if ($request->commission_paid == 1){
                $submit_data['commission_paid_reward'] = $request->commission_paid;
                $submit_data['commission_amount_per_point'] = $request->comission_amount_per_point;
                $submit_data['commission_expire_in_days'] = $request->commission_paid_expire_in_days;
            }
            if ($request->peak_hours == 1){
                $array_data = [];
                foreach ($request->slab_from as $key => $value){
                    $array_data[$key] = [
                        'slab_from' => $value,
                        'slab_to' => $request->slab_to[$key],
                        'peak_points_collection' => $request->peak_points_collection[$key]
                    ];
                }
                $submit_data['peak_hours'] = $request->peak_hours;
                $submit_data['slab_data'] = json_encode($array_data);
            }
            // dd($submit_data);
            RewardSystem::create($submit_data);
        }catch (\Exception $e){
            DB::rollBack();
            return redirect()->back()->withInput()->with('reward', $e->getMessage());
        }
        DB::commit();
        return redirect()->back()->with('reward', __('admin.reward.added'));
    }

    public function UpdateRewardSystem(Request $request,$id){
        DB::beginTransaction();
        try {
            // $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
            // $submit_data = [
            //     'merchant_id' => $merchant_id,
            //     'country_id' => !empty($request->country) ? $request->country : null,
            //     'country_area_id' => !empty($request->country_area) ? $request->country_area : null,
            //     'application' => $request->application,
            // ];
            $reward_system = RewardSystem::find($id);
            // dd($reward_system);

            $reward_system->rating_reward = $request->rating;
            $reward_system->rating_points = $request->rating == 1 ? $request->rating_reward : NULL;
            $reward_system->rating_expire_in_days = $request->rating == 1 ? $request->rating_expire_in_days : NULL;

            $reward_system->comment_reward = $request->writing_comment;
            $reward_system->comment_min_words = $request->writing_comment == 1 ? $request->comment_min_words : NULL;
            $reward_system->comment_points = $request->writing_comment == 1 ? $request->comment_reward : NULL;
            $reward_system->comment_expire_in_days = $request->writing_comment == 1 ? $request->comment_expire_in_days : NULL;

            $reward_system->referral_reward = $request->referral;
            $reward_system->referral_points = $request->referral == 1 ? $request->referral_reward : NULL;
            $reward_system->referral_expire_in_days = $request->referral == 1 ? $request->referral_expire_in_days : NULL;

            $reward_system->trip_expense_reward = $request->trips_select;
            $reward_system->no_of_trips = $request->trips_select == 4 ? $request->no_of_trips : NULL;
            $reward_system->expense_amount = $request->trips_select == 3 ? (float)$request->trip_expense_amount : NULL;
            $reward_system->point_against_trips = $request->point_against_trips;
            $reward_system->trips_type = $request->trips_type;
            // $reward_system->amount_per_points = $request->trips_select == 1 ? $request->per_point_amount : NULL;
            // $reward_system->expenses_expire_in_days = $request->trips_select == 1 ? $request->expenses_expire_in_days : NULL;
            $reward_system->amount_per_points = $request->per_point_amount;
            // $reward_system->reward_value = (float)$request->point_against_trips * (float)$request->per_point_amount;
            $reward_system->expenses_expire_in_days = $request->expenses_expire_in_days;
            $reward_system->status = $request->status;
            $reward_system->online_time_reward = $request->online_time;
            $reward_system->points_per_hour = $request->online_time == 1 ? $request->hours_per_point : NULL;
            $reward_system->online_time_expire_in_days = $request->online_time == 1 ? $request->online_time_expire_in_days : NULL;

            $reward_system->commission_paid_reward = $request->commission_paid;
            $reward_system->commission_amount_per_point = $request->commission_paid == 1 ? $request->comission_amount_per_point : NULL;
            $reward_system->commission_expire_in_days = $request->commission_paid == 1 ? $request->commission_paid_expire_in_days : NULL;

            $array_data = [];
            if ($request->peak_hours == 1){
                foreach ($request->slab_from as $key => $value){
                    $array_data[$key] = [
                        'slab_from' => $value,
                        'slab_to' => $request->slab_to[$key],
                        'peak_points_collection' => $request->peak_points_collection[$key]
                    ];
                }
            }

            $reward_system->peak_hours = $request->peak_hours;
            $reward_system->slab_data = $request->peak_hours == 1 ? json_encode($array_data) : NULL;
            // dd($reward_system);
            $reward_system->save();
            // RewardSystem::create($submit_data);
        }catch (\Exception $e){
            DB::rollBack();
            return redirect()->back()->withInput()->with('reward', $e->getMessage());
        }
        DB::commit();
        return redirect()->back()->with('reward', __('admin.reward.added'));
    }
}
