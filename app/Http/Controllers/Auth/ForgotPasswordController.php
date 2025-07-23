<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Auth;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\MailTrait;

class ForgotPasswordController extends Controller
{
    use MailTrait;
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    public function showLinkRequestForm($name = null)
    {
        $merchant = Merchant::where([['alias_name', '=', $name], ['merchantStatus', '=', 1]])->first();
        if (!empty($merchant)) {
            setS3Config($merchant);
            // app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            return view('merchant.forgot',compact('merchant'));
        } else {
            return view('apporio');
        }
       
    }

    public function sendEmail(Request $request,$name)
    {
        $validator = Validator::make($request->all(),
        [
                'email' => ['required', 'string', 'email', 'max:255'],
        ]);
        if($validator->fails()){
            return redirect()->back()->withErrors($validator->messages())->withInput();
        }
        DB::beginTransaction();
        try {
            $merchant = Merchant::where([['alias_name', '=', $name], ['merchantStatus', '=', 1],['email', '=', $request->email]])->first();
            if(empty($merchant)){
                throw new \Exception("Please try again");
            }
            $token = Password::getRepository()->create($merchant);
            $url = route('password.reset',['alias_name'=>$name,'token'=>$token,'email'=>$merchant->email]);
            $html = view('merchant.mail.email',compact('url'));
            $this->sendMail(null,$merchant->email,$html,'forgot_password');
            
        }catch(\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage())->withInput();
        }
        DB::commit();
        return redirect()->back()->with('success','We have emailed your password reset link!');
    }

    // use SendsPasswordResetEmails;

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
