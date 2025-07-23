<?php

namespace App\Http\Middleware;

use Closure;
use App;
use Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AdminS3
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        // if not set session or session value is blank
        //        if(!Session::has('locale') || (Session::has('locale') &&   empty(Session::get('locale'))))
        app('debugbar')->disable();
        $merchant = NULL;
        if (Auth::guard('merchant')->check()) {
            $merchant = Auth::user('merchant');
        } elseif (Auth::guard('hotel')->check()) {
            $merchant = Auth::user('hotel')->Merchant;
        } elseif (Auth::guard('franchise')->check()) {
            $merchant = Auth::user('franchise')->Merchant;
        } elseif (Auth::guard('taxicompany')->check()) {
            $merchant = Auth::user('taxicompany')->Merchant;
        } elseif (Auth::guard('corporate')->check()) {
            $merchant = Auth::user('corporate')->Merchant;
        } elseif (Auth::guard('business-segment')->check()) {
            $merchant = isset(Auth::user('business-segment')->Merchant) ? Auth::user('business-segment')->Merchant : NULL;
        } elseif (Auth::guard('driver-agency')->check()) {
            $merchant = Auth::user('driver-agency')->Merchant;
        } elseif (Auth::guard('agent')->check()) {
            $merchant = Auth::user('agent')->Merchant;
        } elseif (Auth::guard('laundry_outlet')->check()) {
            $merchant = Auth::user('laundry_outlet')->Merchant;
        }
        // Set S3 Details
        if (!empty($merchant->id)) {
             setS3Config($merchant);
        }
        return $next($request);
    }
}
