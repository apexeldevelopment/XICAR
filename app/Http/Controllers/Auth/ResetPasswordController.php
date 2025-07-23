<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Auth;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use DB;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    public function showResetForm(Request $request,$name,$token = null)
    {
        $merchant = Merchant::where([['alias_name', '=', $name], ['merchantStatus', '=', 1]])->first();
        if (!empty($merchant)) {
            return view('merchant.reset')->with(
                ['merchant'=>$merchant,'token' => $token, 'email' => $request->email]
            );
        } else {
            return view('apporio');
        }

       
    }

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    public function reset(Request $request,$name)
    {
        $validator = Validator::make($request->all(),
        [   
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);
        if($validator->fails()){
            return redirect()->back()->withErrors($validator->messages())->withInput();
        }
        DB::beginTransaction();
        try {
            $merchant = Merchant::where([['alias_name', '=', $name], ['merchantStatus', '=', 1],['email','=', $request->email]])->first();
            if(empty($merchant)){
                throw new \Exception("Invalid URL Access");
            }
            
            $token = DB::table('password_resets')->where('email',$merchant->email)->first();
            if(empty($token)){
                throw new \Exception("We not found password reset request for this email");
            }
            if (Hash::check($request->token,$token->token)) {
                
                    $merchant->password = Hash::make($request->password);
                    $merchant->remember_token = Str::random(60);
                    $merchant->save();
                    DB::table('password_resets')->where('email', $merchant->email)->delete();
               
            }else{
                throw new \Exception("Invalid Token");
            }
        }catch(\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage())->withInput();
        }
        DB::commit();
        return redirect()->route('merchant.login',$name)->withSuccess('password reset successfully');
    }


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }
}
