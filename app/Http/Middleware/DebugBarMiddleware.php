<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 25/8/23
 * Time: 12:44 PM
 */

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;

class DebugBarMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){
        app('debugbar')->disable();

        $value = \Session::get('developer');
        $pin = base64_decode($value);
        $merchant = Merchant::where("access_pin", $pin)->first();
        if (!empty($merchant)) {
//            app('debugbar')->enable();
        }
        return $next($request);
    }
}
