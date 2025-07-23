<?php

namespace App\Http\Middleware;
use App\Traits\MerchantTrait;
use Closure;

class Timezone
{
    use MerchantTrait;
    public function handle($request, Closure $next)
    {
        $user = $request->user('api-driver');
        $string_file = $this->getStringFile($user->merchant_id);
        $msg = "Unauthorized Request";
        if ($request->user('api-driver')->driver_admin_status == 2 || $request->user('api-driver')->driver_delete == 1 || $request->user('api-driver')->login_logout == 2) {
            if($request->user('api-driver')->driver_admin_status == 2){
                $msg = trans("$string_file.driver_account_deactivated");
            }
            else if($request->user('api-driver')->driver_delete == 1){
                $msg = trans("$string_file.driver_account_deleted");
            }
            elseif($request->user('api-driver')->login_logout == 2){
                $msg = trans("$string_file.another_device_login");
            }
            return response()->json(['version' => 'NA','result' => "999", 'message' => $msg, 'data' => []]);
        }
//        if (in_array($request->user('api-driver')->CountryArea->timezone, \DateTimeZone::listIdentifiers())) {
//            date_default_timezone_set($request->user('api-driver')->CountryArea->timezone);
//        }
        $request->request->add([
            'merchant_id' => $user->merchant_id,
        ]);
        return $next($request);
    }
}
