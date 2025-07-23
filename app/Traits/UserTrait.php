<?php

namespace App\Traits;

use App\Models\User;
use App\Models\UserDevice;
use Auth;

trait UserTrait
{
    public function getAllActiveUserPlayerIds()
    {
        $merchant = Auth::user('merchant')->load('CountryArea');
        $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
        $users = UserDevice::whereHas('User', function ($q) use($merchant_id){
            $q->where([['merchant_id', '=', $merchant_id], ['user_delete', '=', NULL]]);
        })->where('player_id','!=', '')->pluck('player_id')->toArray();
        return $users;
    }


    public function getAllUsers($pagination = true, $request = NULL,$per_page = NULL)
    {
        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }
        $merchant_id = get_merchant_id();
        $query = User::where([['merchant_id', '=', $merchant_id]])
            ->where(function ($q) use ($request, $permission_area_ids) {
                if (!empty($permission_area_ids)) {
                    $q->whereIn("country_area_id", $permission_area_ids);
                }
                if (!empty($request->country_id)) {
                    $q->where('country_id', '=', $request->country_id);
                }
                if(!empty($request->lt_gt)){
                    if($request->lt_gt == 'lt'){
                        $q->where('wallet_balance','<',0);
                    }elseif($request->lt_gt == 'gt'){
                        $q->where('wallet_balance','>=',0);
                    }
                }
                if(isset($request->wallet_money_filter)){
                    $q->where(DB::raw('CAST(wallet_balance AS FLOAT)'), '<', $request->wallet_money_filter);
                }
            })
            ->orderBy('created_at', 'DESC');
        if (!empty($request->area_id) || !empty($request->parameter)) {
            switch ($request->parameter) {
                case "1":
                    //                    $parameter = "first_name";
                    $parameter = \DB::raw('CONCAT_WS(" ", first_name, last_name)');
                    break;
                case "2":
                    $parameter = "email";
                    break;
                case "3":
                    $parameter = "UserPhone";
                    break;
            }
            if ($request->keyword) {
                $query->where($parameter, 'like', '%' . $request->keyword . '%');
            }
            if ($request->area_id) {
                $query->where('country_area_id', '=', $request->area_id);
            }
            if(empty($per_page)){
                $per_page = 20;
            }
        }
        return $pagination ? $query->paginate($per_page) : $query->get();
    }

}
