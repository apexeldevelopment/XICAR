<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 16/8/23
 * Time: 10:59 PM
 */

namespace App\Http\Middleware;


use App\Models\Merchant;

class DeveloperMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        $value = \Session::get('developer');
        $pin = base64_decode($value);
        $merchant = Merchant::where("access_pin", $pin)->first();
        if(empty($merchant)){
            return redirect()->route("developer");
        }
        return $next($request);
    }
}
