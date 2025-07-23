<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Helper\Merchant as helperMerchant;
use App\Models\CarpoolingRide;
use App\Models\CarpoolingRideDetail;
use App\Models\CarpoolingRideUserDetail;
use App\Models\UserVehicle;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use App\Models\Merchant;
use App\Models\User;
use DB;
use App\Traits\CarpoolingTrait;
class CarpoolingOfferRideController extends Controller
{
    public function offerRideList(){
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id=get_merchant_id('false');
        if ($carpooling_enable){
            $offer_ride = CarpoolingRide::with('CountryArea','User','UserVehicle')->where('merchant_id',$merchant_id)->paginate(20);
        }
        $data=[];
        return view('merchant.car-pool-offer-rides.offer_rides',compact('offer_ride','carpooling_enable','data'));
    }
   public function takenRideList(){
        $merchant_segment = helperMerchant::MerchantSegments(1);
       $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id=get_merchant_id('false');
        
        if ($carpooling_enable){
            
            $taken_ride = CarpoolingRideUserDetail::where('merchant_id',$merchant_id)->paginate(20);
            // $this->SetTimeZone($taken_ride->CarpoolingRide->country_area_id);
        }
        $data=[];
        
        return view('merchant.car-pool-taken-rides.taken_rides',compact('taken_ride','carpooling_enable','data'));
   }
    public function offerRideDetails($id){

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id=get_merchant_id('false');
        $carpooling=[];
        if ($carpooling_enable){
            $carpooling = CarpoolingRide::with('User','UserVehicle')->where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        }
        $offer_ride_details= CarpoolingRideDetail::where([['carpooling_ride_id','=',$carpooling->id]])->paginate(20);
        $offer_ride_user_details=CarpoolingRideUserDetail::with('User')->where([['carpooling_ride_id','=',$carpooling->id],['merchant_id', '=', $merchant_id]])->first();
        return view('merchant.car-pool-offer-rides.offer_ride_details',compact('offer_ride_details','offer_ride_user_details','carpooling','carpooling_enable'));
    }
    public function UserOfferRideDetails($id){
        $user_ride_details=CarpoolingRideUserDetail::with('CarpoolingRide','User','CarpoolingRideDetail')->find($id);
        return view('merchant.car-pool-offer-rides.offer_ride_user_details',compact('user_ride_details'));
    }
    public function offerRideSearch(Request  $request){
        $offer = new CarpoolingRideController();
        $query = $offer->CommonSearch($request->id, [1,2,3,4,5,6], $request->user, $request->date, $request->date1);
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $merchant_id = get_merchant_id('false');
        $offer_ride = $query->paginate(20);

        $data = $request->all();
        return view('merchant.car-pool-offer-rides.offer_rides', compact('offer_ride', 'carpooling_enable','data'));
    }
    
    public function earningReport(Request $request){
        $checkPermission = check_permission(1, 'view_rider');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $merchant = Merchant::find($merchant_id);
        $config = $merchant->Configuration;
        $countries = $merchant->CountryArea;
        $ride=CarpoolingRide::with('CountryArea', 'User', 'UserVehicle')->where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 4]])->paginate(25);
        $data=[];
        $total_rides=$ride->count();
        $ride_amount=round($ride->sum('total_amount'),2);
        $merchant_amount=round($ride->sum('company_commission'),2);
        $driver_amount=round($ride->sum('driver_earning'),2);
        $data['merchant_id'] = $merchant_id;
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        return view('merchant.report.carpooling-services.index', compact('ride', 'countries','config', 'merchant', 'data','total_rides','ride_amount','merchant_amount','driver_amount','carpooling_enable'));
    }
    // public function earningReport(Request $request){
    // $checkPermission = check_permission(1, 'view_rider');
    //     if ($checkPermission['isRedirect']) {
    //         return $checkPermission['redirectBack'];
    //     }
    //     $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
    //     $merchant = Merchant::find($merchant_id);
    //     $config = $merchant->Configuration;
    //     $countries = $merchant->Country;
    //     $users = User::whereHas('CarpoolingRide',function ($q) {
    //      $q->where('ride_status', '=', '4');})->where([['merchant_id', '=', $merchant_id], ['taxi_company_id', '=', NULL], ['user_delete', '=', NULL]])->latest()->paginate(10);
    //     $data = [];
    //     $data['merchant_id'] = $merchant_id;
    //     $merchant_segment = helperMerchant::MerchantSegments(1);
    //     $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
      
          
    // }
    //  public function earningReport(Request $request){
    //     $id = $request->driver_id;
    //     $user = User::select('id','first_name','last_name','UserPhone','merchant_id')->find($id);
    //     $checkPermission =  check_permission(1,'view_reports_charts');
    //     if ($checkPermission['isRedirect'])
    //     {
    //         return  $checkPermission['redirectBack'];
    //     } 
    //     $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
    //     $merchant = Merchant::find($merchant_id);
    //     $config = $merchant->Configuration;
    //     $countries = $merchant->Country;
    //     $users = User::whereHas('CarpoolingRide',function ($q)use($request,$merchant_id) {
    //     $q->where('ride_status', '=', '4');})->where([['merchant_id', '=', $merchant_id], ['taxi_company_id', '=', NULL], ['user_delete', '=', NULL]])->latest()->paginate(10);
    //     $data = [];
    //     switch ($request->parameter) {
    //         case "1":
    //             $parameter = "first_name";
    //             break;
    //         case "2":
    //             $parameter = "email";
    //             break;
    //         case "3":
    //             $parameter = "Userphone";
    //             break;
    //         }
    //         if ($request->keyword) {
    //             $q->where($parameter, 'like', '%' . $request->keyword . '%');
    //         }
    //         if (!empty($request->ride_id) && $request->ride_id) {
    //             $q->where('ride_id', $request->ride_id);
    //         }
    //         if ($request->country_id) {
    //             $q->where('country_id', '=', $request->country_id);
    //         }
    //         if (!empty($request->country_area_id)) {
    //             $q->where('country_area_id', $request->country_area_id);
    //         }
         
    //         if ($request->start) {
    //             $start_date = date('Y-m-d',strtotime($request->start));
    //             $end_date = date('Y-m-d ',strtotime($request->end));
    //             $q->whereBetween(DB::raw('DATE(created_at)'), [$start_date,$end_date]);
    //         }
    //   // p($users);
    //     $arr_segment = get_merchant_segment(true,$merchant_id,1,1);
    //     $arr_segment = count($arr_segment) > 1 ? $arr_segment : [];
    //     $request->request->add(['request_from'=>"ride_earning","arr_segment"=>$arr_segment]);
    //     $arr_search = $request->all();
    //     $total_rides =  $users->count();
    //     $currency = "";
    //     $data['merchant_id'] = $merchant_id;
    //     $merchant_segment = helperMerchant::MerchantSegments(1);
    //     $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
    //     return view('merchant.report.carpooling-services.index', compact('users','total_rides','arr_search', 'countries','config', 'merchant', 'data','carpooling_enable'));
    //  }
    // public function earningReport(Request $request)
    // {
    //     $id = $request->driver_id;
    //     $driver = Driver::select('id','first_name','last_name','phoneNumber','merchant_id')->find($id);
    //     $checkPermission =  check_permission(1,'view_reports_charts');
    //     if ($checkPermission['isRedirect'])
    //     {
    //         return  $checkPermission['redirectBack'];
    //     }
    //     $merchant_id = get_merchant_id();
    //     $request->request->add(['search_route'=>route('merchant.driver-taxi-services-report',['driver_id'=>$id]),'request_from'=>"COMPLETE"]);
    //     $arr_rides = $this->getBookings($request,$pagination = true, 'MERCHANT');
    //     $query = BookingTransaction::select(DB::raw('SUM(customer_paid_amount) as ride_amount'),DB::raw('SUM(company_earning) as merchant_earning'),DB::raw('SUM(driver_earning) as driver_earning'),DB::raw('SUM(business_segment_earning) as store_earning'))
    //         ->with(['Booking'=>function($q) use($request,$merchant_id){
    //             $q->where([['booking_status','=',1005],['merchant_id','=',$merchant_id]]);
    //             if (!empty($request->booking_id) && $request->booking_id) {
    //                 $q->where('merchant_booking_id', $request->booking_id);
    //             }
    //             if (!empty($request->segment_id)) {
    //                 $q->where('segment_id', $request->segment_id);
    //             }
    //             if (!empty($request->driver_id)) {
    //                 $q->where('driver_id', $request->driver_id);
    //             }
    //             if ($request->start) {
    //                 $start_date = date('Y-m-d',strtotime($request->start));
    //                 $end_date = date('Y-m-d ',strtotime($request->end));
    //                 $q->whereBetween(DB::raw('DATE(created_at)'), [$start_date,$end_date]);
    //             }
    //         }])
    //         ->whereHas('Booking',function($q) use($request,$merchant_id){
    //             $q->where([['booking_status','=',1005],['merchant_id','=',$merchant_id]]);
    //             if (!empty($request->booking_id) && $request->booking_id) {
    //                 $q->where('merchant_booking_id', $request->booking_id);
    //             }
    //             if (!empty($request->segment_id)) {
    //                 $q->where('segment_id', $request->segment_id);
    //             }
    //             if (!empty($request->driver_id)) {
    //                 $q->where('driver_id', $request->driver_id);
    //             }
    //             if ($request->start) {
    //                 $start_date = date('Y-m-d',strtotime($request->start));
    //                 $end_date = date('Y-m-d ',strtotime($request->end));
    //                 $q->whereBetween(DB::raw('DATE(created_at)'), [$start_date,$end_date]);
    //             }
    //         });
    //     $earning_summary = $query->first();
    //     $arr_segment = get_merchant_segment(true,$merchant_id,1,1);
    //     $arr_segment = count($arr_segment) > 1 ? $arr_segment : [];
    //     $request->request->add(['request_from'=>"ride_earning","arr_segment"=>$arr_segment]);
    //     $ride_obj = new BookingController;
    //     
    //     $arr_search = $request->all();
    //     $total_rides = $arr_rides->count();
    //     $currency = "";
    //    

    // }
     public function Search(Request $request,$ride_status = [4])
     {

        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        //$query=CarpoolingRide::with('CountryArea', 'User', 'UserVehicle')->where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 4]])->paginate(20);
        $query = CarpoolingRide::whereHas('CarpoolingRideDetail',function ($q) {
        $q->where('ride_status', '=', '4');})->where([['merchant_id', '=', $merchant_id]]);
        // $users=$query->first();
        // //p($users);
        // switch ($request->parameter) {
        //     case "1":
        //         $parameter =$users->User->email;
        //         break;
        //     case "2":
        //         $parameter = $users->User->UserPhone;
        //         break;
        // }
        //  if ($request->email) {
        //     $users->where('email', '=', $request->email);
        // }
        //  if ($request->phonenumber) {
        //     $users->where('UserPhone', '=', $request->phonenumber);
        // }
        
        if ($request->ride_id) {
            $query->where('id', '=', $request->ride_id);
        }
        if ($request->keyword) {
            $keyword = $request->keyword;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->Where('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if ($request->country_id) {
            $query->where('country_area_id', '=', $request->country_id);
        }
        if($request->start) {
         $start_date=strtotime($request->start);
         $end_date=strtotime($request->end);
        $query->whereBetween('ride_timestamp', [$start_date,$end_date]);
        }
        $ride = $query->paginate(25);
        $merchant = Merchant::find($merchant_id);
        $config = $merchant->Configuration;
        $countries = $merchant->CountryArea;
       // p($countries);
        $total_rides=$ride->count();
        $ride_amount=round($ride->sum('total_amount'),2);
        $merchant_amount=round($ride->sum('company_commission'),2);
        $driver_amount=round($ride->sum('driver_earning'),2);
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $data = $request->all();
        return view('merchant.report.carpooling-services.index', compact('ride','countries','config', 'merchant', 'data','total_rides','ride_amount','merchant_amount','driver_amount', 'carpooling_enable'));
    }
     
}