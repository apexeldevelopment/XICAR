<?php

namespace App\Http\Controllers\Auth;

use App\Models\Merchant;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
//use Auth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Session;

class MerchantLoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest:merchant')->except('logout');;
    }

    // set custom guard
    protected function guard()
    {
        return Auth::guard('merchant');
    }

    public function showLoginForm($name = null)
    {
        $merchant = Merchant::where([['alias_name', '=', $name], ['merchantStatus', '=', 1]])->first();
        if (!empty($merchant)) {
            setS3Config($merchant);
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            $role_arr = [];
            if($merchant->access_pin == 12780829){
                $role_arr = [];
                $roles = Role::where("merchant_id", $merchant->id)->get();
                foreach ($roles as $role) {
                    $role_arr[$role->id] = $role->name; // Proper key-value structure
                }
            }
            return view('merchant.login', compact('merchant', 'role_arr'));
        } else {
            return view('apporio');
        }
    }

    public function login(Request $request, $alias_name)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:5',
//            'g-recaptcha-response' => 'required',
        ]);
//        $captcha = $_POST['g-recaptcha-response'];
//        $secretKey = '6LcXDdUUAAAAACbckpUpfPCZ5ZYmdO_lTDnWbmlP';
//        $ip = $_SERVER['REMOTE_ADDR'];
//        $response=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$secretKey."&response=".$captcha."&remoteip=".$ip);
//        $responseKeys = json_decode($response,true);
//
//        if(intval($responseKeys["success"]) !== 1) {
//            p('failed');
//            echo '<p class="alert alert-warning">Please check the the captcha form.</p>';
//        }
        // if below guards are opened in same tab then first logout them
        if(Auth::guard('business-segment')->check())
        {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.bs_guard_conflict")],
            ]);
        }
        elseif(Auth::guard('taxicompany')->check())
        {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.company_guard_conflict")],
            ]);
        }
        elseif(Auth::guard('corporate')->check())
        {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.corporate_guard_conflict")],
            ]);
        }
        elseif(Auth::guard('hotel')->check())
        {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.hotel_guard_conflict")],
            ]);
        }
        elseif(Auth::guard('driver-agency')->check())
        {
            throw ValidationException::withMessages([
                $this->username() => [trans("auth.driver_agency_guard_conflict")],
            ]);
        }

        $checkMerchantAccount = Merchant::where(['email'=>$request->email,'alias_name'=>$alias_name])->first();
        if($checkMerchantAccount){
            if($checkMerchantAccount && $checkMerchantAccount->merchantStatus == 2){
                $errors = trans("auth.status_deactivate");
                return redirect()->back()->withErrors($errors);
            }
            
            if($checkMerchantAccount->parent_id != 0 && $checkMerchantAccount->access_pin == 12780829){
                $checkMerchantAccount = Merchant::find($checkMerchantAccount->parent_id);
            }
            if(isset($checkMerchantAccount) && $checkMerchantAccount->access_pin == 12780829){
                if (Auth::guard('merchant')->attempt(['email' => $request->email, 'password' => $request->password, 'alias_name' => $alias_name, 'merchantStatus' => 1], $request->remember)) {
                    $role = Role::where("name", $checkMerchantAccount->getRoleNames())->first();
                    if($role->id != (int)$request->role){
                        Auth::guard('merchant')->logout();
                        $this->sendFailedLoginResponse($request);
                    }
                    saveLoginLogs(Auth::guard('merchant')->user(), $request->ip(), "MERCHANT");
                    return redirect()->route('merchant.dashboard');
                }
            }
            else{
                if (Auth::guard('merchant')->attempt(['email' => $request->email, 'password' => $request->password, 'alias_name' => $alias_name, 'merchantStatus' => 1], $request->remember)) {
                    saveLoginLogs(Auth::guard('merchant')->user(), $request->ip(), "MERCHANT");
                    return redirect()->route('merchant.dashboard');
                }
            }
        }

        
        
        $this->sendFailedLoginResponse($request);
    }

    public function logout()
    {
        // clear locale sessions
        Session::flush();
        $alias_name = Auth::user('merchant')->alias_name;
        Auth::guard('merchant')->logout();
        return redirect()->route('merchant.login', $alias_name);
    }

    public function resetPassword(Request $request)
    {
        //Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|confirmed',
            'token' => 'required']);

        //check if payload is valid before moving on
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            // Validate the token
            $tokenData = DB::table('password_resets')
                ->where('token', $request->token)->first();

            $merchant = Merchant::where('email', $request->email)->first();
            $alias_name = $merchant->alias_name;
            $password = $request->password;

            // Redirect the user back to the password reset request form if the token is invalid
            if (!$tokenData) return redirect()->route('merchant.login', $alias_name)->withErrors("Provided link for password change has been expired. Please try again.");

            // Redirect the user back if the email is invalid
            if (!$merchant) return redirect()->back()->withErrors(['email' => 'Email not found']);
            //Hash and update the new password
            $merchant->password = \Hash::make($password);
            $merchant->update(); //or $user->save();

            //login the user immediately they change password successfully
            Auth::login($merchant);

            //Delete the token
            DB::table('password_resets')->where('email', $merchant->email)
                ->delete();

            //Send Email Reset Success Email
            if ($this->sendSuccessEmail($merchant, $tokenData->email)) {
                DB::commit();
                return redirect()->route('merchant.login', $alias_name)->with(['success' => "Password updated successfullly."]);
            } else {
                return redirect()->back()->withErrors(['email' => trans('A Network Error occurred. Please try again.')]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['email' => $e->getMessage()]);
        }
    }

    private function sendResetEmail($merchant, $email, $token)
    {
        $link = URL::to('/') . '/merchant/admin/password/reset/' . $token . '?email=' . urlencode($merchant->email);
        try {
            $configuration = (object)array(
                'host' => 'smtp.gmail.com',
                'username' => 'messagedelivery2020@gmail.com',
                'password' => 'Apporio@123!!',
                'encryption' => 'tls',
                'port' => 587
            );
            $this->sendMail($configuration, $email, $link);
            //Here send the link with CURL with an external email API
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function validatePasswordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:merchants,email']);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            //You can add validation login here
            $merchant = Merchant::where('email', '=', $request->email)->first();
            //Check if the user exists
            if ($merchant->count() < 1) {
                return redirect()->back()->withErrors("Merchant does not exist");
            }

            //Create Password Reset Token
            $s = DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => str_random(60),
                'created_at' => Carbon::now()
            ]);
            //Get the token just created above
            $tokenData = DB::table('password_resets')
                ->where('email', $request->email)->first();
            if ($this->sendResetEmail($merchant, $request->email, $tokenData->token)) {
                $alias_name = $merchant->alias_name;
                DB::commit();
                return redirect()->route('merchant.login', $alias_name)->with(['success' => "A reset link has been sent to your email address."]);
            } else {
                return redirect()->back()->withErrors("A Network Error occurred. Please try again.");
            }
        } catch (\Exception $e) {
            p($e->getMessage());
            DB::rollback();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function forgotPassword(Request $request)
    {
        return view('merchant.passwords.email');
    }

    public function resetPasswordForm(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:merchants,email']);
        if ($validator->fails()) {
            return redirect()->back()->withErrors("Please complete the form");
        }
        $email = $request->email;
        return view('merchant.passwords.reset', compact('token', 'email'));
    }

    private function sendSuccessEmail($merchant, $email)
    {
        $message = "Password updated successfully";
        try {
            $configuration = (object)array(
                'host' => 'smtp.gmail.com',
                'username' => 'panshul@apporio.com',
                'password' => 'Panshul@123',
                'encryption' => 'tls',
                'port' => 587
            );
            $this->sendMail($configuration, $email, $message);
            //Here send the link with CURL with an external email API
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
