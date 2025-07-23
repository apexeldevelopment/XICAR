<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
//use Auth;
use Illuminate\Support\Facades\Auth;
use App\Models\Merchant;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App;
use Illuminate\Validation\ValidationException;
use Lang;
use URL;
use App\Models\BusinessSegment\BusinessSegment;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;

class BusinessSegmentLoginController extends Controller
{
    use AuthenticatesUsers;
    // USE it to use sendFailedLoginResponse method
    public function __construct()
    {
        $this->middleware('guest:business-segment')->except('logout');;
    }

    public function showLoginForm($merchant_alias = null, $business_segment_alias = null)
    {
        $valid_merchant = Merchant::where([['alias_name', '=', $merchant_alias], ['merchantStatus', TRUE]])->first();
        if (!empty($valid_merchant->id)) {
            setS3Config($valid_merchant);
            $valid_business_segment = BusinessSegment::where([['alias_name', '=', $business_segment_alias], ['merchant_id', $valid_merchant->id]])->first();
            if (!empty($valid_business_segment->id) && !empty($valid_merchant->id)) {
                return view('business-segment.login', ['merchant' => $valid_merchant, 'business_segment' => $valid_business_segment]);
            } else {
                return view('apporio');
            }
        }
        return view('apporio');
    }

    public function directLogin(Request $request, $id = NULL){
        try {
            $decryptedId = \Illuminate\Support\Facades\Crypt::decrypt($id);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            abort(403, 'Invalid ID');
        }
        if(!empty($decryptedId)){
            $businessSegment = \App\Models\BusinessSegment\BusinessSegment::find($decryptedId);
            if($businessSegment && $businessSegment->is_deleted == 1){
                abort(403, 'Account is deleted.');
            }
            if($businessSegment && Auth::guard('business-segment')->loginUsingId($decryptedId)){
                setS3Config($businessSegment->Merchant);
                saveLoginLogs(Auth::guard('business-segment')->user(), $request->ip(), "BUSINESS_SEGMENT", true);
                return redirect()->intended(route('business-segment.dashboard'));
            }
        }
    }

    public function login(Request $request, $alias_name)
    {
        $url_pattern = array_slice(explode('/', URL::previous()), -3, 1);
        $merchant_info = Merchant::where([['alias_name', '=', $url_pattern[0]]])->first();
        //Validate the form data
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required|min:5'
        ]);

        // if below guards are opened in same tab then first logout them
        if (Auth::guard('merchant')->check()) {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.merchant_guard_conflict")],
            ]);
        } elseif (Auth::guard('taxicompany')->check()) {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.company_guard_conflict")],
            ]);
        } elseif (Auth::guard('corporate')->check()) {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.corporate_guard_conflict")],
            ]);
        } elseif (Auth::guard('hotel')->check()) {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.hotel_guard_conflict")],
            ]);
        } elseif (Auth::guard('driver-agency')->check()) {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.driver_agency_guard_conflict")],
            ]);
        }
        // --- Check if business segment is deleted ---
        $businessSegment = BusinessSegment::where(function($query) use ($request, $merchant_info, $alias_name) {
            $query->where('merchant_id', $merchant_info->id)
                  ->where('alias_name', $alias_name)
                  ->where(function($q) use ($request) {
                      $q->where('email', $request->email)
                        ->orWhere('phone_number', $request->email);
                  });
        })->first();
        if($businessSegment && $businessSegment->is_deleted == 1){
            abort(403, 'Account is deleted.');
        }
        //Attempt the user log in
        if (
            Auth::guard('business-segment')->attempt(['email' => $request->email, 'password' => $request->password, 'merchant_id' => $merchant_info->id, 'status' => 1, 'alias_name' => $alias_name], $request->remember)
            || Auth::guard('business-segment')->attempt(['phone_number' => $request->email, 'password' => $request->password, 'merchant_id' => $merchant_info->id, 'status' => 1, 'alias_name' => $alias_name], $request->remember)
        ) {
            saveLoginLogs(Auth::guard('business-segment')->user(), $request->ip(), "BUSINESS_SEGMENT");
            return redirect()->intended(route('business-segment.dashboard'));
        }
        $this->sendFailedLoginResponse($request);
    }

    public function logout()
    {
        // clear locale sessions
        Session::flush();
        $taxi_alias_name = Auth::user('business-segment')->alias_name;
        $merchant_alias_name = Auth::user('business-segment')->merchant->alias_name;
        Auth::guard('business-segment')->logout();
        return redirect()->route('business-segment.login', [$merchant_alias_name, $taxi_alias_name]);
    }

    protected function guard()
    {
        return Auth::guard('business-segment');
    }

    public function showUserLoginForm($merchant_alias = null)
    {
        $merchant = Merchant::where([['alias_name', '=', $merchant_alias], ['merchantStatus', TRUE]])->first();
        if (!empty($merchant->id)) {
            setS3Config($merchant);
            return view('merchant.business-segment-login', compact('merchant'));
        }
        return view('apporio');
    }

    public function UserLogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required|min:5',


        ]);
        if (Auth::guard('business-segment-user')->attempt(['email' => $request->email, 'password' => $request->password, 'merchant_id' => $request->merchant_id, 'status' => 1], $request->remember)
            || Auth::guard('business-segment-user')->attempt(['phone_number' => $request->email, 'password' => $request->password, 'merchant_id' => $request->merchant_id, 'status' => 1], $request->remember)){
            return redirect()->route('business-segment.user.details');
        }
        $this->sendFailedLoginResponse($request);
    }

    public function UserLogout()
    {
        // clear locale sessions
        Session::flush();
        $alias_name = Auth::user('business-segment-user')->Merchant->alias_name;
        Auth::guard('business-segment-user')->logout();
        return redirect()->route('business-segment.user.login', $alias_name);
    }
}
