<?php

namespace App\Http\Controllers\Merchant;

use App\Models\CountryArea;
use App\Models\Country;
use Auth;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\RewardGift;
use App\Models\ApplicationConfiguration;
use Illuminate\Support\Facades\DB;
use App\Models\InfoSetting;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;

class RewardGiftController extends Controller
{
    use ImageTrait,MerchantTrait;
    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','REWARD_GIFT')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index(){
        // $checkPermission =  check_permission(0,'reward_gift');
        // if ($checkPermission['isRedirect']){
        //     return  $checkPermission['redirectBack'];
        // }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $merchant = Merchant::find($merchant_id);
        $reward_gifts = RewardGift::where(['merchant_id'=>$merchant->id,'delete_status'=> NULL])->get();

        return view('merchant.reward_gift.index', compact('reward_gifts','merchant_id'));

    }

    public function create()
    {
        // $checkPermission =  check_permission(0,'reward_gift');
        // if ($checkPermission['isRedirect']){
        //     return  $checkPermission['redirectBack'];
        // }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $countries = Country::where('merchant_id', $merchant_id)->get();
        return view('merchant.reward_gift.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'application' => 'required',
            'country' => 'required',
            'name' => 'required',
            'image' => 'required|file',
            'reward_points' => 'required',
            'trips' => 'required',
            'amount' => 'required',
        ]);
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;

        $reward_gift = new RewardGift();
        $reward_gift->application = $request->application;
        $reward_gift->merchant_id = $merchant_id;
        $reward_gift->country_id = $request->country;
        $reward_gift->name = $request->name;
        $reward_gift->reward_points = $request->reward_points;
        $reward_gift->rides = $request->trips;
        $reward_gift->amount = $request->amount;
        $reward_gift->comment = $request->comment;
        if($request->hasFile('image')){
            $reward_gift->image = $this->uploadImage('image','reward_gift',$merchant_id);
        }

        $reward_gift->save();

        return redirect()->back()->with('reward', __('admin.reward.added'));

    }

    public function edit($id){
        // $checkPermission =  check_permission(0,'reward_gift');
        // if ($checkPermission['isRedirect']){
        //     return  $checkPermission['redirectBack'];
        // }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $countries = Country::where('merchant_id', $merchant_id)->get();
        $reward_gift = RewardGift::where('merchant_id', $merchant_id)->where('id', $id)->first();
        return view('merchant.reward_gift.edit', compact('countries','reward_gift','merchant_id'));
    }

    public function update(Request $request,$id){
        $request->validate([
            'application' => 'required',
            'country' => 'required',
            'name' => 'required',
            'reward_points' => 'required',
            'trips' => 'required',
            'amount' => 'required',
            'status'=>'required',
        ]);
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;

        if($id){
            $reward_gift = RewardGift::find($id);

            $reward_gift->application = $request->application;
            $reward_gift->merchant_id = $merchant_id;
            $reward_gift->country_id = $request->country;
            $reward_gift->name = $request->name;
            $reward_gift->reward_points = $request->reward_points;
            $reward_gift->rides = $request->trips;
            $reward_gift->amount = $request->amount;
            $reward_gift->comment = $request->comment;
            $reward_gift->status = $request->status;
            if($request->hasFile('image')){
                $reward_gift->image = $this->uploadImage('image','reward_gift',$merchant_id);
            }

            $reward_gift->save();

        }

        if($reward_gift){
            return redirect()->back()->with('reward', __('admin.reward.updated'));
        }

        return redirect()->back()->withInput()->with('reward', __('admin.swr'));
    }

    public function delete($id)
    {
        $reward = RewardGift::find($id);
        if ($reward) {
            $reward->delete_status = 1;
            $reward->save();
            return redirect()->back()->with('reward', __('admin.deleted.successfully'));
        }
        return redirect()->back()->with('reward', __('admin.swr'));
    }
}