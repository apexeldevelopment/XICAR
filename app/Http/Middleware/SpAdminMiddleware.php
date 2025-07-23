<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 16/8/23
 * Time: 10:59 PM
 */

namespace App\Http\Middleware;


use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class SpAdminMiddleware
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
        $value = \Session::get('sp-token');
        $admin = Admin::first();
        $pass = base64_decode($value);
        if(!Hash::check($pass, $admin->password)){
            return redirect()->route("sp-admin");
        }
        return $next($request);
    }
}
