<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LaundryOutlet\LaundryOutlet;
use App\Models\Merchant;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Lang;
use URL;

class LaundryOutletLoginController extends Controller
{
    //
    // @ayush

    use AuthenticatesUsers;   //sendFailedLoginResponse method

    public function __construct()
    {
        $this->middleware('guest:laundry_outlet')->except('logout');;
    }


    public function logout()
    {
        // clear locale sessions
        Session::flush();
        $alias_name = Auth::user('laundry_outlet')->alias_name;
        $merchant_alias_name = Auth::user('laundry_outlet')->merchant->alias_name;
        Auth::guard('laundry_outlet')->logout();
        return redirect()->route('laundry_outlet.login', [$merchant_alias_name, $alias_name]);
    }

    public function showLoginForm($merchant_alias = null, $laundry_alias = null)
    {
        $valid_corporate = LaundryOutlet::where([['alias_name', '=', $laundry_alias], ['status', TRUE]])->first();
        $valid_merchant = Merchant::where([['alias_name', '=', $merchant_alias], ['merchantStatus', TRUE]])->first();
        if ($valid_corporate && !empty($valid_merchant)) {
            setS3Config($valid_merchant);
            return view('laundry-outlet.login', ['merchant' => $valid_merchant, 'laundry_outlet' => $valid_corporate]);
        } else {
            abort(404);
        }
    }


    public function login(Request $request)
    {
        $url_pattern = array_slice(explode('/', URL::previous()), -3, 1);
        $merchant_info = Merchant::where([['alias_name', '=', $url_pattern[0]]])->first();
        //Validate the form data
        $this->validate($request, [
            'email' => 'required|email',
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
        } elseif (Auth::guard('hotel')->check()) {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.hotel_guard_conflict")],
            ]);
        } elseif (Auth::guard('business-segment')->check()) {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.bs_guard_conflict")],
            ]);
        }
        elseif (Auth::guard('corporate')->check()) {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.bs_guard_conflict")],
            ]);
        }
        //Attempt the user log in
        if (Auth::guard('laundry_outlet')->attempt(['email' => $request->email, 'password' => $request->password, 'merchant_id' => $merchant_info->id, 'status' => 1], $request->remember)) {
            return redirect()->intended(route('laundry-outlet.dashboard'));
        }
        $this->sendFailedLoginResponse($request);
    }
}
