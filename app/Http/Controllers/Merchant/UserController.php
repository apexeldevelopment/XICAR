<?php

namespace App\Http\Controllers\Merchant;

use App\Events\UserSignupWelcome;
use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Requests\UserRequest;
use App\Traits\BookingTrait;
use App\Traits\MerchantTrait;
use App\Http\Controllers\Helper\Merchant as helperMerchant;
use App\Models\AccountType;
use App\Models\ApplicationConfiguration;
use App\Models\BookingConfiguration;
use App\Models\Booking;
use App\Models\Configuration;
use App\Models\Corporate;
use App\Models\Country;
use App\Models\CountryArea;
use App\Models\CountryAreaVehicleType;
use App\Models\Document;
use App\Models\Driver;
use App\Models\InfoSetting;
use App\Models\Merchant;
use App\Models\Onesignal;
use App\Models\RejectReason;
use App\Models\TaxiCompany;
use App\Models\UserAddress;
use App\Models\UserDocument;
use App\Models\HandymanOrder;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserHold;
use App\Models\UserVehicle;
use App\Models\UserVehicleDocument;
use App\Models\UserWalletTransaction;
use App\Models\ReferralDiscount;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Traits\ImageTrait;
use App\Traits\OrderTrait;
use App\Traits\HandymanTrait;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use DB;
use Session;
use App\Models\WalletRechargeRequest;


class UserController extends Controller
{
    use ImageTrait, MerchantTrait, BookingTrait, OrderTrait, HandymanTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'USER')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $checkPermission =  check_permission(1, 'view_rider');
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $merchant = Merchant::find($merchant_id);
        $config = $merchant->Configuration;
        $countries = $merchant->Country;
        $config->user_document = $merchant->ApplicationConfiguration->user_document;
        $appconfig = $merchant->ApplicationConfiguration;
        $config->gender = $appconfig->gender;
        $permission_country_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
            if (!empty($permission_area_ids)) {
                $permission_country_ids = CountryArea::whereIn("id", $permission_area_ids)->get()->pluck("country_id")->toArray();
                if (!empty($permission_country_ids)) {
                    $permission_country_ids = array_unique($permission_country_ids);
                }
            }
        }
//        $users = User::where([['merchant_id', '=', $merchant_id], ['user_delete', '=', NULL], ['signup_status', '!=', 1]])->where(function ($q) use ($permission_country_ids) {
//            if (!empty($permission_country_ids)) {
//                $q->where("country_id", $permission_country_ids);
//            }
//        })->latest()->paginate(10);
        $usersQuery = User::where('merchant_id', $merchant_id)
            ->whereNull('user_delete')
            ->where('signup_status', '!=', 1);

        $deletedUsersQuery = User::where('merchant_id', $merchant_id)
            ->where('user_delete', 1);

        if (!empty($permission_country_ids)) {
            $usersQuery->whereIn('country_id', (array) $permission_country_ids);
            $deletedUsersQuery->whereIn('country_id', (array) $permission_country_ids);
        }

        $users = $usersQuery->latest()->paginate(10);
        $deleted_users_count = $deletedUsersQuery->count();
        $pending_user = User::where([['merchant_id', '=', $merchant_id], ['user_delete', '=', NULL], ['signup_status', '=', 1]])->count();
        $data = [];
        $data['export_search'] = [];
        $data['merchant_id'] = $merchant_id;

        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $pending_vehicle_users = 0;
        $rejected_user_vehicles = 0;
        $user_carpooling = User::whereHas('CarpoolingRide',function ($q) {
            $q->whereIn('ride_status',array(1,2,3,4,5,6));})->where([['merchant_id', '=', $merchant_id], ['taxi_company_id', '=', NULL], ['user_delete', '=', NULL]])->get();
        $no_of_rides=$user_carpooling->count();
        //p( $no_of_rides);
        if ($carpooling_enable) {
            $pending_vehicle_users = User::whereHas('UserVehicles', function ($q) {
                $q->where('vehicle_verification_status', 1);
            })->where([['merchant_id', '=', $merchant_id], ['taxi_company_id', '=', NULL], ['user_delete', '=', NULL]])->count();
            $rejected_user_vehicles = User::whereHas('UserVehicles', function ($q) {
                $q->where('vehicle_verification_status', 3);
            })->where([['merchant_id', '=', $merchant_id], ['taxi_company_id', '=', NULL], ['user_delete', '=', NULL]])->count();
        }

        return view('merchant.user.index', compact('users', 'config', 'pending_user', 'countries', 'merchant', 'data', 'carpooling_enable', 'pending_vehicle_users', 'rejected_user_vehicles', 'deleted_users_count'));
    }

    public function create()
    {
        $checkPermission =  check_permission(1, 'create_rider');
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $countries = Country::where([['merchant_id', '=', $merchant_id]])->get();
        $config = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
        $corporates = Corporate::where([['merchant_id', '=', $merchant_id]])->get();
        $appConfig = ApplicationConfiguration::where('merchant_id', '=', $merchant_id)->first();
        $account_types = AccountType::where('merchant_id', $merchant_id)->get();
        $bookConfig = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
        return view('merchant.user.create', compact('corporates', 'countries', 'config', 'appConfig', "account_types","bookConfig"));
    }

    public function store(UserRequest $request)
    {
        DB::beginTransaction();
        try {
            $merchant = get_merchant_id(false);
            $config = Configuration::where([['merchant_id', '=', $merchant->id]])->first();
            $appConfig = ApplicationConfiguration::where('merchant_id', '=', $merchant->id)->first();
            $string_file = $this->getStringFile(NULL, $merchant);
            $merchant_id = $merchant->id;
            $country = explode("|", $request->country);
            $kin_details = null;
            if($config->kin_person_details_on_signup == 1){
                $kin_details_arr[] = [
                    "kin_name" => $request->kin_person_name,
                    "kin_phone_number" => $request->kin_person_phone,
                    "kin_address" => "",
                ];
                $kin_details = json_encode($kin_details_arr);
            }
            $user = new User();
            $user = User::create([
                'merchant_id' => $merchant_id,
                'country_id' => $country[0],
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'UserPhone' => $request->phone,
                'email' => $request->user_email,
                'password' => Hash::make($request->password),
                'UserSignupType' => 1,
                'UserSignupFrom' => 2,
                'ReferralCode' => $user->GenrateReferCode(),
                'UserProfileImage' => $this->uploadImage('profile', 'user'),
                'user_type' => $request->rider_type,
                'user_gender' => $request->user_gender,
                'corporate_id' => $request->corporate_id,
                'corporate_email' => $request->corporate_email,
                'smoker_type' => $request->smoker_type,
                'allow_other_smoker' => $request->allow_other_smoker,
                'cancellation_charge_card_payment'=> $request->cancellation_charge_card_payment,
                'user_kin_details' => $kin_details,
            ]);

            if (isset($config->user_bank_details_enable) && $config->user_bank_details_enable == 1) {
                $user->bank_name = $request->bank_name;
                $user->account_holder_name = $request->account_holder_name;
                $user->account_number = $request->account_number;
                $user->account_type_id = $request->account_types;
                $user->online_code = $request->online_transaction;
                $user->save();
            }
            $country_document = Country::find($country[0]);
            $country_document = $country_document->documents->count();
            // Commit Transaction
            DB::commit();
            $message = trans("common.user") . ' ' . trans("common.saved_successfully");
            if ($appConfig->user_document == 1) {
                if ($country_document > 0) {
                    return redirect()->route('user.upload.document', $user->id)->withSuccess(trans("common.user") . ' ' . trans("common.saved_successfully"));
                } else {
                    $message += " " . trans("common.and") . ' ' . trans("common.country") . ' ' . trans("common.document") . ' ' . trans("common.data_not_found");
                }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        // Commit Transaction
        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function show($id)
    {
        $merchant_id = get_merchant_id();
        $user = User::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $bookings = Booking::where([['user_id', '=', $id]])->whereIn('booking_status', [1005])->orderBy("created_at", 'desc')->paginate(5);

        $appConfig = ApplicationConfiguration::where('merchant_id', '=', $merchant_id)->first();
        $config = Configuration::where('merchant_id', '=', $merchant_id)->first();
        $vehicle_details = isset($user->UserVehicles[0]) ? $user->UserVehicles[0] : NULL;
        $sharing_vehicles = UserVehicle::whereHas('Users', function ($q) use ($user) {
            $q->where('user_id', '=', $user->id);
        })->where('owner_id', '!=', $user->id)->get();
        $rejectreasons = RejectReason::where([['merchant_id', '=', $merchant_id], ['status', '=', 1]])->get();
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $string_file = $this->getStringFile($merchant_id);
        $arr_booking_status = $this->getBookingStatus($string_file);
        return view('merchant.user.show', compact('user', 'bookings', 'appConfig', 'arr_booking_status', 'merchant_segment', 'sharing_vehicles', 'rejectreasons', 'vehicle_details','config'));
    }

    public function FavouriteLocation($id)
    {
        $merchant_id = get_merchant_id();
        $user = User::where([['merchant_id', '=', $merchant_id]])->with('UserAddress')->findOrFail($id);
        return view('merchant.user.favourite', compact('user'));
    }

    public function FavouriteDriver($id)
    {
        $merchant_id = get_merchant_id();
        $user = User::where([['merchant_id', '=', $merchant_id]])->with(['FavouriteDriver' => function ($query) {
            $query->with(['Driver' => function ($q) {
                $q->where(function ($qq) {
                    $qq->where('driver_delete', '=', NULL);
                    $qq->where('driver_admin_status', '=', 1);
                });
                $q->with('CountryArea');
            }]);
            $query->whereHas('Driver', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('driver_delete', '=', NULL);
                    $qq->where('driver_admin_status', '=', 1);
                });
            });
        }])->findOrFail($id);
        return view('merchant.user.favourite-drivers', compact('user'));
    }

    public function edit($id)
    {
        $checkPermission =  check_permission(1, 'edit_rider');
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $config = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
        $user = User::where([['merchant_id', '=', $merchant_id], ['user_delete', '=', NULL]])->findOrFail($id);
        $appConfig = ApplicationConfiguration::where('merchant_id', '=', $merchant_id)->first();
        $account_types = AccountType::where('merchant_id', $merchant_id)->get();
        $countries = Country::where([['merchant_id', '=', $merchant_id]])->get();
        $bookConfig = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
        return view('merchant.user.edit', compact('countries', 'user', 'config', 'appConfig', 'account_types','bookConfig'));
    }

    public function update(Request $request, $id)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = $merchant->id;
        $request->validate([
            'first_name' => "required",
            'user_email' => "required|email|max:255|unique:users,email," . $id . ",id,merchant_id," . $merchant_id . ",user_delete,NULL",
            'user_phone' => "required|regex:/^[0-9+]+$/|unique:users,UserPhone," . $id . ",id,merchant_id," . $merchant_id . ",user_delete,NULL",
            'password' => 'required_if:edit_password,1'
        ]);

        DB::beginTransaction();

        try {

            $config = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
            $user = User::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->UserPhone = $request->isd.$request->user_phone;
            $user->email = $request->user_email;
            $user->user_gender = $request->user_gender;
            $user->smoker_type = $request->smoker_type;
            $user->allow_other_smoker = $request->allow_other_smoker;
            $user->cancellation_charge_card_payment = $request->cancellation_charge_card_payment;
            if ($request->edit_password == 1) {
                $user->password = Hash::make($request->password);
            }
            if ($request->hasFile('profile')) {
                $user->UserProfileImage = $this->uploadImage('profile','user');
            }
            if (isset($config->user_bank_details_enable) && $config->user_bank_details_enable == 1) {
                $user->bank_name = $request->bank_name;
                $user->account_holder_name = $request->account_holder_name;
                $user->account_number = $request->account_number;
                $user->account_type_id = $request->account_types;
                $user->online_code = $request->online_transaction;
            }
            $kin_details = null;
            if($config->kin_person_details_on_signup == 1){
                $kin_details_arr[] = [
                    "kin_name" => $request->kin_person_name,
                    "kin_phone_number" => $request->kin_person_phone,
                    "kin_address" => "",
                ];
                $kin_details = json_encode($kin_details_arr);
            }
            $user->user_kin_details = $kin_details;
            $user->save();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        // Commit Transaction
        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function ChangeStatus($id, $status)
    {
        $validator = Validator::make(
            [
                'id' => $id,
                'status' => $status,
            ],
            [
                'id' => ['required'],
                'status' => ['required', 'integer', 'between:1,2'],
            ]
        );
        if ($validator->fails()) {
            return redirect()->back();
        }

        $user = User::findOrFail($id);
        $merchant = $user->Merchant;
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = $merchant->id;
        $user->UserStatus = $status;
        $user->save();
        $data = ['status' => $status];
        setLocal($user->language);
        $message = $status == 2 ? trans("$string_file.account_has_been_inactivated") : trans("$string_file.account_has_been_activated");
        $title = trans("$string_file.account_inactivated");
        $data['notification_type'] = "ACCOUNT_INACTIVATED";
        $data['segment_type'] = "";
        $data['segment_data'] = ['user_id' => $id];
        $arr_param = ['user_id' => $id, 'data' => $data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => ''];
        Onesignal::UserPushMessage($arr_param);
        setLocal();
        return redirect()->route('users.index')->withSuccess(trans("$string_file.status_updated"));
    }

    public function Wallet($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $user = User::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $user_hold_amount=[];
        $wallet_transactions=[];
        $hold_amount=[];
        $user=User::where('merchant_id',$merchant_id)->findOrFail($id);
        if($carpooling_enable){
            $wallet_transactions = UserWalletTransaction::with('User')->where([['user_id', '=', $id]])->paginate(25);
            $user_hold_amount=UserHold::with('User','CarpoolingRide')->where([['user_id','=',$id],['status','=',0]])->paginate(20);
            $hold_amount=DB::table("user_holds")->where([['user_id','=',$id],['status','=',0]])->sum('amount');//pending amount
        }else {
            $wallet_transactions = UserWalletTransaction::with('User')->where([['user_id', '=', $id]])->paginate(25);
        }
        return view('merchant.user.wallet', compact('wallet_transactions', 'user', 'carpooling_enable','user_hold_amount','hold_amount'));
    }

    public function AddWalletMoney(Request $request)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = $merchant->id;
        $validator = Validator::make($request->all(), [
            'add_money_user_id' => 'required',
            'transaction_type' => 'required',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|integer|between:1,2',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        // $request->validate([
        //     'add_money_user_id' => 'required',
        //     'amount' => 'required|numeric',
        //     'payment_method' => 'required|integer|between:1,2',
        // ]);
        //        $amount = number_format((float)$request->amount, 2, '.', '');
        $paramArray = array(
            'user_id' => $request->add_money_user_id,
            'booking_id' => NULL,
            'amount' => $request->amount,
            'narration' => 1,
            'platform' => 1,
            'payment_method' => $request->payment_method,
            'receipt' => $request->receipt_number,
            'action_merchant_id' => Auth::user('merchant')->id,
            'description'=> $request->description
        );
        if ($request->transaction_type == 1) {
            WalletTransaction::UserWalletCredit($paramArray);
            if(!empty($request->user_wallet_recharge_request_id)){
                $recharge_request = WalletRechargeRequest::find($request->user_wallet_recharge_request_id);
                $recharge_request->request_status = 1;
                $recharge_request->save();
            }
        } else {
            $paramArray['narration'] = 14;
            WalletTransaction::UserWalletDebit($paramArray);
        }
        //        CommonController::UserWalletCredit($request->add_money_user_id,NULL,$request->amount,1,1,$request->payment_method,$request->receipt_number);
        //        $user = User::findOrFail($request->add_money_user_id);
        //        $wallet = $user->wallet_balance;
        //        $total = $wallet + $amount;
        //        $user->wallet_balance = number_format((float)$total, 2, '.', '');;
        //        $user->save();
        //        UserWalletTransaction::create([
        //            'merchant_id' => $merchant_id,
        //            'user_id' => $request->add_money_user_id,
        //            'platfrom' => 1,
        //            'amount' => sprintf("%0.2f", $request->amount),
        //            'payment_method' => $request->payment_method,
        //            'receipt_number' => $request->receipt_number,
        //            'description' => $request->description,
        //            'type' => 1,
        //        ]);
        //        $message = trans('api.money');
        //        $data = ['message' => $message];
        //        Onesignal::UserPushMessage($request->add_money_user_id, $data, $message, 3, $merchant_id);
        if(!empty($request->user_wallet_recharge_request_id)){
            return redirect()->route('wallet.recharge.requests')->withSuccess(trans("$string_file.money_added_successfully"));
        }
        return redirect()->route('users.index')->withSuccess(trans("$string_file.money_added_successfully"));
    }

    public function Serach(Request $request)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        switch ($request->parameter) {
            case "1":
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "UserPhone";
                break;
        }
        $query = User::where([['merchant_id', '=', $merchant_id], ['user_delete', '=', NULL]]);
        $deletedUsersQuery = User::where('merchant_id', $merchant_id)->where('user_delete', 1);

        if ($request->keyword) {
            $query->where($parameter, 'like', '%' . $request->keyword . '%');
        }
        if ($request->country_id) {
            $query->where('country_id', '=', $request->country_id);
            $deletedUsersQuery->where('country_id', '=', $request->country_id);
        }
        $deleted_users_count = $deletedUsersQuery->count();
        $users = $query->paginate(25);
        $merchant = Merchant::find($merchant_id);
        $config = $merchant->Configuration;
        $countries = $merchant->Country;
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        $config->user_document = $merchant->ApplicationConfiguration->user_document;
        $pending_user = User::where([['merchant_id', '=', $merchant_id], ['user_delete', '=', NULL], ['signup_status', '=', 1]])->count();
        $pending_vehicle_users = 0;
        $rejected_user_vehicles = 0;
        if ($carpooling_enable) {
            $pending_vehicle_users = User::whereHas('UserVehicles', function ($q) {
                $q->where('vehicle_verification_status', 1);
            })->where([['merchant_id', '=', $merchant_id], ['taxi_company_id', '=', NULL], ['user_delete', '=', NULL]])->count();
            $rejected_user_vehicles = User::whereHas('UserVehicles', function ($q) {
                $q->where('vehicle_verification_status', 3);
            })->where([['merchant_id', '=', $merchant_id], ['taxi_company_id', '=', NULL], ['user_delete', '=', NULL]])->count();
        }
        $data = $request->all();
        $data['merchant_id'] = $merchant_id;
        $data['export_search'] = $request->all();
        return view('merchant.user.index', compact('countries', 'users', 'config', 'pending_user', 'merchant', 'data','carpooling_enable','pending_vehicle_users','rejected_user_vehicles', 'deleted_users_count'));
    }

//    public function destroy($id)
//    {
//        $merchant = get_merchant_id(false);
//        $string_file = $this->getStringFile(NULL, $merchant);
//        $merchant_id = $merchant->id;
//        $bookings = Booking::whereIn('booking_status', array(1001, 1012, 1002, 1003, 1004))->where([['user_id', '=', $id]])->first();
//        $orders = \App\Models\BusinessSegment\Order::where([['user_id', '=', $id]])->first();
//        $laundry_order = \App\Models\LaundryOutlet\LaundryOutletOrder::where([['user_id', '=', $id]])->first();
//        $handyman_order = \App\Models\HandymanOrder::where([['user_id', '=', $id]])->first();
//        if (empty($bookings)) :
//            $bookings = Booking::where([['user_id', '=', $id]])->first();
//            $user = User::where([['merchant_id', '=', $merchant_id]])->FindorFail($id);
//            //            $playerids = $user->UserDevice()->get()->pluck('player_id')->toArray();
//
//            setLocal($user->language);
//            $message = trans("$string_file.account_has_been_deleted");
//            $title = trans("$string_file.account_deleted");
//            $data['notification_type'] = "ACCOUNT_DELETED";
//            $data['segment_type'] = "";
//            $data['segment_data'] = ['user_id' => $id];
//            $arr_param = ['user_id' => $id, 'data' => $data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => ''];
//            Onesignal::UserPushMessage($arr_param);
//            setLocal();
//
//            if (empty($bookings) && empty($orders) && empty($laundry_order) && empty($handyman_order)) :
//                if (!empty($user->UserDevice())) {
//                    $user->UserDevice()->delete();
//                }
//                $user->delete();
//            else :
//                $user->user_delete = 1;
//                $user->save();
//            endif;
//            //            $data = ['booking_status' => '999'];
//            //            $message = trans('admin.message728');
//            //            Onesignal::UserPushMessage($id, $data, $message, 6, $merchant_id);
//            echo $message;
//        else :
//            echo trans("$string_file.user_running_ride");
//            //echo trans('admin.message694');
//        endif;
//    }

    // @ayush (user soft delete only, can be restored by admin )
    public function destroy(Request $request)
    {
        try {
            $merchant = get_merchant_id(false);
            $string_file = $this->getStringFile(NULL, $merchant);
            $merchant_id = $merchant->id;

            $bookings = Booking::whereIn('booking_status', [1001, 1012, 1002, 1003, 1004])->where('user_id', $request->id)->first();
            $orders = \App\Models\BusinessSegment\Order::whereIn('order_status', [1, 4, 6, 7, 9, 10])->where('user_id', $request->id)->first();
            $laundry_order = \App\Models\LaundryOutlet\LaundryOutletOrder::whereIn('order_status', [1, 6, 10, 7, 9, 13, 15, 16, 17])->where('user_id', $request->id)->first();
            $handyman_order = \App\Models\HandymanOrder::whereIn('order_status', [1, 4, 6, 10])->where('user_id', $request->id)->first();

            if (empty($bookings) && empty($orders) && empty($laundry_order) && empty($handyman_order)) {
                $user = User::where('merchant_id', $merchant_id)->findOrFail($request->id);

                setLocal($user->language);
                $message = trans("$string_file.account_has_been_deleted");
                $title = trans("$string_file.account_deleted");

                $data = [
                    'notification_type' => "ACCOUNT_DELETED",
                    'segment_type' => "",
                    'segment_data' => ['user_id' => $request->id]
                ];
                $arr_param = [
                    'user_id' => $request->id,
                    'data' => $data,
                    'message' => $message,
                    'merchant_id' => $merchant_id,
                    'title' => $title,
                    'large_icon' => ''
                ];
                Onesignal::UserPushMessage($arr_param);
                setLocal();

                // Soft delete user
                $user->user_delete = 1;
                $user->save();

                return response()->json([
                    'status' => 'success',
                    'message' => $message
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => trans("$string_file.user_running_ride")
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deletedUsers(Request $request)
    {
        $checkPermission =  check_permission(1, 'view_rider');
        if ($checkPermission['isRedirect']) {
            return  $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $merchant = Merchant::find($merchant_id);
        $permission_country_ids = [];
        $countries = $merchant->Country;
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
            if (!empty($permission_area_ids)) {
                $permission_country_ids = CountryArea::whereIn("id", $permission_area_ids)->get()->pluck("country_id")->toArray();
                if (!empty($permission_country_ids)) {
                    $permission_country_ids = array_unique($permission_country_ids);
                }
            }
        }
        $users = User::where([['merchant_id', '=', $merchant_id], ['user_delete', '=', 1]])->where(function ($q) use ($permission_country_ids) {
            if (!empty($permission_country_ids)) {
                $q->where("country_id", $permission_country_ids);
            }
        })->latest()->paginate(10);
        $data = [];
        $data['export_search'] = [];
        $data['merchant_id'] = $merchant_id;
        return view('merchant.user.deleted_users', compact('users', 'countries', 'data'));
    }


    public function restoreUser(Request $request)
    {
        try {
            $user = User::find($request->id);

            if (!$user) {
                return response()->json(["message" => "User not found"], 404);
            }

            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $user->user_delete = NULL;
            $user->save();

            return response()->json(["message" => "User successfully restored."]);
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], 500);
        }
    }

    public function showDocuments($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $user = User::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $rejectReasons = RejectReason::where([['merchant_id', '=', $merchant_id], ['status', '=', 1]])->get();
        return view('merchant.user.document', compact('user', 'rejectReasons'));
    }

    public function ChangeDocumentStatus(Request $request)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $request->validate([
            'id' => 'required',
            'status' => 'required',
            'reject_reason_id' => 'required_if:status,3'
        ]);
        $userdocument = UserDocument::findorfail($request->id);
        // status 2 for approved documents 3 for reject
        $bool = UserDocument::where('id', $request->id)->update([
            'document_verification_status' => $request->status,
            'reject_reason_id' => $request->reject_reason_id
        ]);
        if ($request->status == 2 && $bool) {
            $user = User::findorfail($userdocument->user_id);
            $compare = (int) $user->approved_document + 1;
            if ($user->total_document == $compare) {
                $user->signup_status = 2;
            }
            $user->approved_document = $user->approved_document + 1;
            $user->save();
        }

        if ($bool) {
            return redirect()->back()->withSuccess(trans("$string_file.added_successfully"));
        }
        return redirect()->back()->withErrors(trans("$string_file.some_thing_went_wrong"));
    }

    public function AlldocumentStatus(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'status' => 'required',
            'reject_reason_id' => 'required_if:status,3'
        ]);

        $userdocument = UserDocument::where('user_id', '=', $request->id)->update(['document_verification_status' => $request->status]);

        $user = User::findorfail($request->id);
        $user->signup_status = 3;
        $user->approved_document = $user->total_document;
        $user->save();


        if ($userdocument) {
            return redirect()->back()->with('document-message', trans('admin.documentAdded'));
        }
        return redirect()->back()->with('document-message', trans('admin.documentNotAdded'));
    }

    public function UserRefer($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $user = User::find($id);
        $referral_details = ReferralDiscount::where([['sender_id', '=', $id], ['sender_type', '=', "USER"], ['merchant_id', '=', $merchant_id]])->latest()->paginate(10);
        foreach ($referral_details as $refer) {
            $receiverDetails = $refer->receiver_type == "USER" ? User::find($refer->receiver_id) : Driver::find($refer->receiver_id);
            $phone = $refer->receiver_type == "USER" ? $receiverDetails->UserPhone : $receiverDetails->phoneNumber;
            $receiverType = $refer->receiver_type == "USER" ? 'User' : 'Driver';
            $refer->receiver_details = array(
                'id' => $receiverDetails->id,
                'name' => $receiverDetails->first_name . ' ' . $receiverDetails->last_name,
                'phone' => $phone,
                'email' => $receiverDetails->email
            );
            $refer->receiverType = $receiverType;
        }
        return view('merchant.user.user_refer', compact('referral_details', 'user'));
    }

    public function PendingRiderList()
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $merchant = Merchant::find($merchant_id);
        $config = $merchant->Configuration;
        $config->user_document = $merchant->ApplicationConfiguration->user_document;
        $appconfig = $merchant->ApplicationConfiguration;
        $config->gender = $appconfig->gender;
        $users = User::where([['merchant_id', '=', $merchant_id], ['user_delete', '=', NULL], ['signup_status', '=', 1]])->latest()->paginate(10);
        return view('merchant.user.pending_rider', compact('users', 'config'));
    }

    public function PendingSearch(Request $request)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $request->validate([
            'keyword' => "required",
            'parameter' => "required|integer|between:1,3",
        ]);
        switch ($request->parameter) {
            case "1":
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "UserPhone";
                break;
        }
        $query = User::where([['merchant_id', '=', $merchant_id], ['user_delete', '=', NULL], ['signup_status', '=', 1]]);
        if ($request->keyword) {
            $query->where($parameter, 'like', '%' . $request->keyword . '%');
        }
        $users = $query->paginate(25);
        $merchant = Merchant::find($merchant_id);
        $config = $merchant->Configuration;
        $config->user_document = $merchant->ApplicationConfiguration->user_document;

        return view('merchant.user.pending_rider', compact('users', 'config'));
    }

    public function uploadDocument(Request $request, $id)
    {
        try {
            $merchant_id = get_merchant_id();
            $user = User::where([['merchant_id', '=', $merchant_id], ['id', '=', $id]])->first();
            $uploaded_documents = UserDocument::where('user_id', $id)->get()->toArray();
            $documents = $user->Country->Documents;
            return view("merchant.user.upload_document", compact("user", "documents", "uploaded_documents"));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function saveDocument(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $merchant_id = get_merchant_id();
            $user = User::where('merchant_id', $merchant_id)->findOrFail($id);
            $doc_expire_date = $request->expiredate;
            $arr_doc_file = $request->file('document');
            $all_doc = $request->input('all_doc');
            $document_number = $request->document_number;
            $custom_document_key = "user_document";
            $this->uploadDocs($id, $custom_document_key, $all_doc, $arr_doc_file, $doc_expire_date, $document_number);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->route('users.show', $user->id)->withSuccess(trans('common.document') . ' ' . trans('common.uploaded') . ' ' . trans('common.successfully'));
    }

    public function uploadDocs($user_id, $custom_document_key, $all_doc_id, $arr_doc_file, $doc_expire_date, $document_number)
    {
        foreach ($all_doc_id as $document_id) {
            $image = isset($arr_doc_file[$document_id]) ? $arr_doc_file[$document_id] : null;
            $expiry_date = isset($doc_expire_date[$document_id]) ? $doc_expire_date[$document_id] : NULL;
            $doc_number = isset($document_number[$document_id]) ? $document_number[$document_id] : NULL;
            if ($custom_document_key == "user_document") {
                $user_document = UserDocument::where([['user_id', $user_id], ['document_id', $document_id]])->first();
                if (empty($user_document->id)) {
                    $user_document = new UserDocument;
                }
                $unique_document = UserDocument::where([['user_id', '!=', $user_id]])->where(function ($q) use ($doc_number, $document_id) {
                    $q->where('document_number', '=', $doc_number)->Where('document_number', '!=', '');
                })->count();
            }

            $doc_info = Document::find($document_id);
            $doc_name = $doc_info->DocumentName;
            // if required document not uploaded
            if ($doc_info->documentNeed == 1 && empty($image) && empty($user_document->id)) {
                throw new \Exception(trans('admin.please_upload_document') . $doc_name);
            }
            // if expire date is mandatory but not inserted
            if ($doc_info->expire_date == 1 && empty($expiry_date)) {
                throw new \Exception(trans('admin.please_select_expire_date') . $doc_name);
            }
            // if document number is mandatory but not entered or duplicate
            if ($doc_info->document_number_required == 1) {
                if (!empty($doc_number)) {
                    if ($unique_document > 0) {
                        throw new \Exception('Document Number already exist');
                    }
                } else {
                    throw new \Exception(trans('admin.please_enter_document_number') . $doc_name);
                }
                $user_document->document_number = $document_number[$document_id];
            }

            $user_document->document_id = $document_id;
            $user_document->expire_date = $expiry_date;
            $user_document->document_verification_status = 2;
            $user_document->user_id = $user_id;
            if (!empty($image)) {
                $user_document->document_file = $this->uploadImage($image, $custom_document_key, NULL, 'multiple');
            }
            $user_document->save();
        }
        return true;
    }

    public function pendingVehicleUser()
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $carpooling_enable = in_array('CARPOOLING', $merchant_segment) ? true : false;
        if (!$carpooling_enable) {
            return redirect()->back()->withErrors(trans("common.invalid") . ' ' . trans("common.action"));
        }
        $users = User::whereHas('UserVehicles', function ($q) {
            $q->where('vehicle_verification_status', 1);
        })->where([['merchant_id', '=', $merchant_id], ['taxi_company_id', '=', NULL], ['user_delete', '=', NULL]])->latest()->paginate(10);
        return view('merchant.user.pending_vehicle', compact('users'));
    }

    public function verifyUserVehicle(Request $request, $vehicle_id, $status)
    {
        try {
            $user_vehicle = UserVehicle::find($vehicle_id);
            $user = User::find($user_vehicle->user_id);
            $merchant_id = $user->merchant_id;
            $ids = $user->id;
            $user_vehicle->vehicle_verification_status = 2;
            $user_vehicle->save();
            UserVehicleDocument::where('user_vehicle_id', $user_vehicle->id)->update(['document_verification_status' => 2]);
            // send notification to user
//            $string_file = $this->getStringFile($user->merchant_id);
            $msg = trans('admin_x.vehicle_approved', ['number' => $user_vehicle->vehicle_number]);
            $title = trans('admin_x.vehicle_approved_title', ['number' => $user_vehicle->vehicle_number]);
            $notification_data['notification_type'] = "VEHICLE_APPROVED";
            $notification_data['segment_type'] = "CARPOOLING";
            $notification_data['segment_data'] = [];
            $arr_param = ['user_id' => $ids, 'data' => $notification_data, 'message' => $msg, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => NULL];
            Onesignal::UserPushMessage($arr_param);
            return redirect()->back()->withSuccess($msg);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function rejectUserVehicle(Request $request)
    {
        $request->validate([
            'user_vehicle_id' => 'required',
            'user_id' => 'required',
            'comment' => 'required',
        ]);
        DB::beginTransaction();
        try {
            $user = User::find($request->user_id);
            if ($request->user_vehicle_id) {
                $vehicle = UserVehicle::findOrFail($request->user_vehicle_id);
                // if user is owner of that vehicle then reject that user when click on user profile
                if ($vehicle->owner_id == $request->user_id) {
                    $vehicle->vehicle_verification_status = 3;
                    $vehicle->save();
                    if (!empty($request->vehicle_documents)) {
                        UserVehicleDocument::whereIn('id', $request->vehicle_documents)->update(['document_verification_status' => 3]);
                    }
                }
            }
            // send reject notification
            $msg = trans('admin_x.vehicle_rejected', ['number' => $vehicle->vehicle_number]);
            $title = trans('admin_x.vehicle_rejected', ['number' => $vehicle->vehicle_number]);
            $notification_data['notification_type'] = "VEHICLE_REJECTED";
            $notification_data['segment_type'] = "CARPOOLING";
            $notification_data['segment_data'] = [];
            $arr_param = ['user_id' => $request->user_id, 'data' => $notification_data, 'message' => $msg, 'merchant_id' => $user->merchant_id, 'title' => $title, 'large_icon' => NULL];
            Onesignal::UserPushMessage($arr_param);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->back()->with("success", $msg);
    }

    public function userVehicleDocument(Request $request)
    {
        $request->validate([
            'user_vehicle_id' => 'required',
        ]);
        try {
            $html = "";
            $user_vehicle = UserVehicle::find($request->user_vehicle_id);
            foreach ($user_vehicle->UserVehicleDocument as $document) {
                $doc_name = $document->Document->documentname;
                $html .= "<div class='checkbox-custom checkbox-primary'>
                            <input type='checkbox' id='doc-$document->id' value='$document->id' name='vehicle_documents[]'>>
                            <label for='doc-$document->id'>$doc_name</label>
                          </div>";
            }
            return $html;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function RejectedVehicle()
    {
        $merchant_id = get_merchant_id();
        $query = UserVehicle::with('User')->where([['merchant_id', '=', $merchant_id], ['vehicle_verification_status', 3]])->orderBy('id', 'DESC');
        $vehicles = $query->paginate(20);
        return view('merchant.user.vehicle-rejected', compact('vehicles'));
    }

    public function UserAddress($id){
        $merchant_id=get_merchant_id('false');
        $user = User::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $user_address=UserAddress::where('user_id',$user->id)->paginate(20);
        return view('merchant.user.user_address',compact('user_address'));

    }

    /** Play store User delete  Start */
    public function showDetails(Request $request)
    {

        $user = Auth::user('user');
        if ($user->id) {
            // p($user);
            $merchant = $user->Merchant;
            setS3Config($merchant);
            return view('merchant.user-details', compact('user', 'merchant'));
        } else {
            return redirect()->back()->withErrors('Something went wrong, please try again');
        }
    }


    public function userDelete(Request $request)
    {
        $user = Auth::user('user');
        if ($user->id) {
            $alias = $user->Merchant->alias_name;
            $user->user_delete = 1;
            $user->save();
            Session::flush();
            return redirect()->route('user.login', $alias)->withSuccess('Your account has been deleted successfully');
        } else {
            return redirect()->back()->withErrors('Something went wrong, please try again');
        }
    }

    /** Play store User delete  End */

    public function getDeviceDetails(Request $request)
    {
        $validator = Validator::make($request->toArray(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);
        if ($validator->fails()) {
            $msg = $validator->messages()->all();
            return array("status" => "error", "message" => $msg[0]);
        }
        try {
            $merchant_id = get_merchant_id();
            $user = User::find($request->user_id);
            $name = $user->UserName;
            $string_file = $this->getStringFile($merchant_id);
            $device_details = [];
            foreach($user->UserDevice as $device){
                array_push($device_details, array(
                    "device" => $device->device,
                    "player_id" => $device->player_id,
                    "apk_version" => $device->apk_version,
                    "model" => $device->model,
                    "operating_system" => $device->operating_system,
                    "package_name" => $device->package_name,
                ));
            }

            $data['string_file'] = $string_file;
            $data['device_details'] = $device_details;

            $html_view = \Illuminate\Support\Facades\View::make('merchant.report.device-detail-table')->with($data)->render();
            return array("status" => "success", "message" => "", "data" => array("name" => $name, "view" => $html_view));
        } catch (\Exception $e) {
            return array("status" => "error", "message" => $e->getMessage());
        }
    }

    public function userJobs(Request $request, $job_type, $id)
    {
        $user = User::find($id);
        $segment_group_id = $user->Segment;
        $bookings = [];
        $food_grocery_orders = [];
        $handyman_orders = [];
        if (!in_array($job_type, ['booking', 'order', 'handyman-order'])) {
            return redirect()->back()->withErrors(trans('admin.invalid_request'));
        }

        if ($segment_group_id == 1) {
            if ($job_type == "booking") {
                $bookings = Booking::where([['user_id', '=', $id]])->paginate(20);
            } elseif ($job_type == "order") {
                $food_grocery_orders = Order::where([['user_id', '=', $id]])->paginate(20);
            }
        } else {
            $handyman_orders = HandymanOrder::where([['user_id', '=', $id]])->paginate(20);
        }

        $string_file = $this->getStringFile($user->merchant_id);
        $req_param['string_file'] = $string_file;
        $arr_status = $this->getOrderStatus($req_param);
        $booking_status = $this->getBookingStatus($string_file);
        $handyman_status = $this->getHandymanBookingStatus($req_param, $string_file);
        return view('merchant.user.jobs', compact('bookings', 'booking_status', 'user', 'food_grocery_orders', 'arr_status', 'handyman_orders', 'handyman_status', 'job_type'));
    }
}
