<?php

namespace App\Http\Controllers\Auth;

use App\Models\Merchant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Session;

class UserLoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        // dd('inside');
        $this->middleware('guest:user')->except('logout');;
    }

    public function username()
    {
        return 'phone';
    }
    public function showLoginForm($name = null)
    {
        // Session::flush();
        $merchant = Merchant::where([['alias_name', '=', $name], ['merchantStatus', '=', 1]])->first();
        if (!empty($merchant)) {
            setS3Config($merchant);

            return view('merchant.user-login', compact('merchant'));
        } else {
            return view('apporio');
        }
    }

    public function login(Request $request)
    {
        // p('in');
        $this->validate($request, [
            'phone' => 'required',
            'password' => 'required|min:5',
           
           
        ]);
        if (Auth::guard('user')->attempt(['UserPhone' => $request->phone, 'password' => $request->password, 'merchant_id' => $request->merchant_id, 'user_delete' => null], $request->remember)) {
            // logout
            // p(Auth::user('user'));
            // $alias = Auth::user('user')->Merchant->alias_name;
            return redirect()->route('user.details');
        }
        $this->sendFailedLoginResponse($request);
    }

    public function logout()
    {
        // clear locale sessions
        Session::flush();
        $alias_name = Auth::user('user')->Merchant->alias_name;
        Auth::guard('user')->logout();
        return redirect()->route('user.login', $alias_name);
    }


    // set custom guard
    protected function guard()
    {
        return Auth::guard('merchant');
    }
}
