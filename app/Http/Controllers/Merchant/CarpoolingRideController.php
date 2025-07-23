<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Helper\Merchant as helperMerchant;
use App\Models\CarpoolingRide;
use App\Models\CarpoolingRideDetail;
use App\Models\CarpoolingRideUserDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Onesignal;
class CarpoolingRideController extends Controller
{
    public function upComingRideList()
    {

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $upcoming_ride = [];
        if ($carpooling_enable) {
            $upcoming_ride = CarpoolingRideUserDetail::with('CarpoolingRide', 'User', 'CarpoolingRideDetail')->where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 1]])->paginate(20);
        }
        $data = [];
        return view('merchant.car-pool-ride-management.up_coming-ride', compact('upcoming_ride', 'carpooling_enable', 'data'));
    }
    public function offerUpComingRideList()
    {

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $upcoming_ride = [];
        if ($carpooling_enable) {
            $upcoming_ride = CarpoolingRide::with('CountryArea', 'User', 'UserVehicle')->where([['merchant_id', '=', $merchant_id]])->whereIn('ride_status',[1,2])->paginate(20);
             $upcoming_ride_user = CarpoolingRideUserDetail::with('CarpoolingRide', 'User', 'CarpoolingRideDetail')->where([['merchant_id', '=', $merchant_id]])->whereIn('ride_status',[1,2])->paginate(20);

        }
        $data = [];
        return view('merchant.car-pool-ride-management.offer_up_coming-ride', compact('upcoming_ride_user','upcoming_ride', 'carpooling_enable', 'data'));
    }

    public function activeRideList()
    {
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $active_ride = [];
        if ($carpooling_enable) {
            $active_ride = CarpoolingRideUserDetail::with('CarpoolingRide', 'User', 'CarpoolingRideDetail')->where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 3]])->paginate(20);

        }
        $data = [];
        return view('merchant.car-pool-ride-management.active-ride', compact('active_ride', 'carpooling_enable', 'data'));
    }
    public function offerActiveRideList()
    {
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $active_ride = [];
        if ($carpooling_enable) {
            $active_ride = CarpoolingRide::with('CountryArea', 'User', 'UserVehicle')->where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 3]])->paginate(20);
        }
        $data = [];
        return view('merchant.car-pool-ride-management.offer_active-ride', compact('active_ride', 'carpooling_enable', 'data'));
    }

    public function cancelRideList()
    {
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $cancel_ride = [];
        if ($carpooling_enable) {
            $cancel_ride = CarpoolingRideUserDetail::with('CarpoolingRide', 'User', 'CarpoolingRideDetail')->where([['merchant_id', '=', $merchant_id]])->whereIn('ride_status',[5,6])->paginate(20);
        }
        $data = [];
        return view('merchant.car-pool-ride-management.cancel-ride', compact('cancel_ride', 'carpooling_enable', 'data'));
    }
    
    public function offerCancelRideList()
    {
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $cancel_ride = [];
        if ($carpooling_enable) {
            $cancel_ride = CarpoolingRide::with('CountryArea', 'User', 'UserVehicle')->where([['merchant_id', '=', $merchant_id]])->whereIn('ride_status',[5,6])->paginate(20);
        }
        $data = [];
        return view('merchant.car-pool-ride-management.offer_cancel-ride', compact('cancel_ride', 'carpooling_enable', 'data'));
    }
    public function completeRideList()
    {
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $complete_ride = [];
        if ($carpooling_enable) {
            $complete_ride = CarpoolingRideUserDetail::with('CarpoolingRide', 'User', 'CarpoolingRideDetail')->where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 4]])->paginate(20);
        }
        $data = [];
        return view('merchant.car-pool-ride-management.complete-ride', compact('complete_ride', 'carpooling_enable', 'data'));
    }
    
    public function offerCompleteRideList()
    {
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $complete_ride = [];
        if ($carpooling_enable) {
            $complete_ride =  CarpoolingRide::with('CountryArea', 'User', 'UserVehicle')->where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 4]])->paginate(20);
        }
        $data = [];
        return view('merchant.car-pool-ride-management.offer_complete-ride', compact('complete_ride', 'carpooling_enable', 'data'));
    }

    public function failedRideList()
    {
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $failed_ride = [];
        if ($carpooling_enable) {
            $failed_ride = CarpoolingRide::with('CountryArea', 'User', 'UserVehicle')->where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 6]])->paginate(20);
        }
        $data = [];
        return view('merchant.car-pool-ride-management.failed-ride', compact('failed_ride', 'carpooling_enable', 'data'));

    }

    public function autoCanceRideList()
    {
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $auto_cancel_ride = [];
        if ($carpooling_enable) {
            $auto_cancel_ride = CarpoolingRide::with('CountryArea', 'User', 'UserVehicle')->where('merchant_id', $merchant_id)->paginate(20);
        }
        $data = [];
        return view('merchant.car-pool-ride-management.auto-cancel-rides', compact('auto_cancel_ride', 'carpooling_enable', 'data'));

    }

    public function allRideList()
    {
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $all_ride = [];
        if ($carpooling_enable) {
            $all_ride = CarpoolingRide::with('CountryArea', 'User', 'UserVehicle')->where('merchant_id', $merchant_id)->whereIn('ride_status', [1, 3, 4, 5, 6])->paginate(20);
        }
        $data = [];
        return view('merchant.car-pool-ride-management.all-rides', compact('all_ride', 'carpooling_enable', 'data'));
    }

    public function upComingRideSearch(Request $request)
    {
        $query = $this->commonSearchUser($request->id, [1], $request->user, $request->date, $request->date1);

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $upcoming_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.up_coming-ride', compact('carpooling_enable', 'data', 'upcoming_ride'));
    }
     public function offerUpComingRideSearch(Request $request)
    {
        $query = $this->CommonSearch($request->id, [1], $request->user, $request->date, $request->date1);

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $upcoming_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.offer_up_coming-ride', compact('carpooling_enable', 'data', 'upcoming_ride'));
    }

    public function ActiveRideSearch(Request $request)
    {

        $query = $this->commonSearchUser($request->id, [3], $request->user, $request->date, $request->date1);

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $active_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.active-ride', compact('active_ride', 'data', 'carpooling_enable'));
    }
     public function offerActiveRideSearch(Request $request)
    {

        $query = $this->CommonSearch($request->id, [3], $request->user, $request->date, $request->date1);

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $active_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.offer_active-ride', compact('active_ride', 'data', 'carpooling_enable'));
    }


    public function CancelRideSearch(Request $request)
    {

        $query = $this->commonSearchUser($request->id, [5,6], $request->user, $request->date, $request->date1);

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $cancel_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.cancel-ride', compact('cancel_ride', 'data', 'carpooling_enable'));

    }
    public function offerCancelRideSearch(Request $request)
    {

        $query = $this->CommonSearch($request->id, [5,6], $request->user, $request->date, $request->date1);

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $cancel_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.offer_cancel-ride', compact('cancel_ride', 'data', 'carpooling_enable'));

    }

    public function CompleteRideSearch(Request $request)
    {
        $query = $this->commonSearchUser($request->id, [4], $request->user, $request->date, $request->date1);

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $complete_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.complete-ride', compact('complete_ride', 'data', 'carpooling_enable'));
    }
    public function offerCompleteRideSearch(Request $request)
    {
        $query = $this->CommonSearch($request->id, [4], $request->user, $request->date, $request->date1);

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $complete_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.complete-ride', compact('complete_ride', 'data', 'carpooling_enable'));
    }

    public function AutoCancelRideSearch(Request $request)
    {
        $query = $this->commonSearchUser($request->id, [4], $request->user, $request->date, $request->date1);
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $auto_cancel_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.auto-cancel-rides', compact('auto_cancel_ride', 'data', 'carpooling_enable'));
    }

    public function AllRideSearch(Request $request)
    {
        $query = $this->commonSearchUser($request->id, [5], $request->user, $request->date, $request->date1);
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $all_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.all-rides', compact('all_ride', 'data', 'carpooling_enable'));
    }

    public function FailedRideSearch(Request $request)
    {
        $query = $this->commonSearchUser($request->id, [6], $request->user, $request->date, $request->date1);
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $failed_ride = $query->paginate(20);
        $data = $request->all();
        return view('merchant.car-pool-ride-management.failed-ride', compact('failed_ride', 'data', 'carpooling_enable'));
    }

    public function CommonSearch($ride_id = null, $ride_status = [1, 2, 3, 4, 5], $user = null, $date = null, $date1 = null)
    {

        $merchant_id = get_merchant_id('false');
        $query = CarpoolingRide::where('merchant_id', $merchant_id)->whereIn('ride_status', $ride_status);

        if ($ride_id) {
            $query->where('id', $ride_id);
        }

        if ($date) {
            $query->whereDate('created_at', '>=', $date);
        }
        if ($date1) {
            $query->whereDate('created_at', '<=', $date1);
        }

        if ($user) {
            $keyword = $user;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")->orwhere('last_name', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }

        return $query;

    }
    public function commonSearchUser($ride_id = null, $ride_status = [1, 2, 3, 4, 5], $user = null, $date = null, $date1 = null){
        $merchant_id = get_merchant_id('false');
        $query = CarpoolingRideUserDetail::where('merchant_id', $merchant_id)->whereIn('ride_status', $ride_status);

        if ($ride_id) {
            $query->where('id', $ride_id);
        }

        if ($date) {
            $query->whereDate('created_at', '>=', $date);
        }
        if ($date1) {
            $query->whereDate('created_at', '<=', $date1);
        }

//        if ($user) {
//            $keyword = $user;
//            $query->WhereHas('User', function ($q) use ($keyword) {
//                $q->where('first_name', 'LIKE', "%$keyword%")->orwhere('last_name', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
//            });
//        }

        return $query;
    }
     public function changeStatus($id, $status=[1,2,5])
    {
        $validator = Validator::make(
            [
                'id' => $id,
                'status' => $status,
            ],
            [
                'id' => ['required'],
                'status' => ['required', 'integer'],
            ]);
        if ($validator->fails()) {
            return redirect()->back();
        }
        $merchant_id = get_merchant_id();
        $carpooling_ride = CarpoolingRide::findOrFail($id);
        $carpooling_ride->ride_status = 5;
        $carpooling_ride->save();
        return redirect()->route('merchant.carpool.offer_up_coming.rides')->withSuccess(trans("common.status") . ' ' . trans("common.updated"));
    }

}
