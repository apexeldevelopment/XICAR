<?php

namespace App\Http\Controllers\Merchant;

use Illuminate\Http\Request;
use App\Models\PaymentOptionsConfiguration;
use App\Models\PaymentOption;
use App\Models\Country;
use App\Models\Merchant;
use App\Models\SmsConfiguration;
use Auth;
use App\Models\VersionManagement;
use App\Models\Configuration;
use App\Http\Controllers\Controller;
use DB;

class GatewayController extends Controller
{
  public function paypal(){
    $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
    $paypal_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_gateway_provider','=','PAYPAL']])->first();
    $config = Configuration::where('merchant_id',$merchant_id)->first();
    return view("merchant.random.paypal",compact('paypal_config','config'));
  }
  public function paypal_store(Request $request){
    $merchant_id = get_merchant_id();
    $config = Configuration::where('merchant_id',$merchant_id)->first();
     $validate_rule = [
        'public_key' => 'required',
        'secret_key' => 'required',
    ];
        $request->validate($validate_rule);
        $paypal_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_gateway_provider','=','PAYPAL']])->first();
         $paypal_config->api_public_key = $request->public_key;
         $paypal_config->api_secret_key = $request->secret_key;
         $paypal_config->save();
        VersionManagement::updateVersion($merchant_id);
        return redirect()->back()->withSuccess(trans("common.added_successfully"));
  
      
  }
  public function stripe(){
    $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
    $stripe_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_gateway_provider','=','STRIPE']])->first();
    $config = Configuration::where('merchant_id',$merchant_id)->first();
    return view("merchant.random.stripe",compact('stripe_config','config'));
      
  }
  public function stripe_store(Request $request){
    $merchant_id = get_merchant_id();
    $config = Configuration::where('merchant_id',$merchant_id)->first();
     $validate_rule = [
        'public_key' => 'required',
        'secret_key' => 'required',
    ];
        $request->validate($validate_rule);
        $stripe_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_gateway_provider','=','STRIPE']])->first();
        $stripe_config->api_public_key = $request->public_key;
        $stripe_config->api_secret_key = $request->secret_key;
        $stripe_config->save();
        VersionManagement::updateVersion($merchant_id);
        return redirect()->back()->withSuccess(trans("common.added_successfully"));
    
  }
  public function monetbil(){
    $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
    $monetbil_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_gateway_provider','=','MONETBIL']])->first();
    $config = Configuration::where('merchant_id',$merchant_id)->first();
    return view("merchant.random.monetbil",compact('monetbil_config','config'));  
  }
  public function monetbil_store(Request $request){
    $merchant_id = get_merchant_id();
    $config = Configuration::where('merchant_id',$merchant_id)->first();
    $validate_rule = [
        'public_key' => 'required',
        'secret_key' => 'required',
    ];
        $request->validate($validate_rule);
        $monetbil_config = PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_gateway_provider','=','MONETBIL']])->first();
        $monetbil_config->api_public_key = $request->public_key;
        $monetbil_config->api_secret_key = $request->secret_key;
        $monetbil_config->save();
        VersionManagement::updateVersion($merchant_id);
        return redirect()->back()->withSuccess(trans("common.added_successfully"));    
  }
 
  public function intouchOperator(){
    $merchant_id = get_merchant_id();
    $intouch_config = PaymentOption::where([['slug','=','INTOUCHGROUP']])->first();
    $intouch_operator_config= PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$intouch_config->id]])->paginate(10);
    return view("merchant.random.intouch.operator",compact('intouch_operator_config'));    
  }
  public function intouchOperatorAdd(){
    return view("merchant.random.intouch.add_operator");      
  }
  public function intouchOperatorStore(Request $request){
    $merchant_id = get_merchant_id();
    $validate_rule = [
        'operator' => 'required',
        'cash_in' => 'required',
        'cash_out'=>'required',
    ];   
    $request->validate($validate_rule);
    $intouch_config = PaymentOption::where([['slug','=','INTOUCHGROUP']])->first();
    $payment_option=  PaymentOptionsConfiguration::where('id', $intouch_config->id)->first();
    if(empty( $payment_option)){
     PaymentOptionsConfiguration::create([
    'payment_option_id'=>$intouch_config->id,
    'merchant_id'=> $merchant_id,
    'operator'=>$request->operator,
    'payment_gateway_provider'=>$intouch_config->slug,
    'api_public_key'=>$request->cash_in,
    'api_secret_key'=>$request->cash_out
    ]);
    }else{
     PaymentOptionsConfiguration::create([
    'payment_option_id'=>$intouch_config->id,
    'merchant_id'=> $merchant_id,
    'operator'=>$request->operator,
    'api_public_key'=>$request->cash_in,
    'api_secret_key'=>$request->cash_out
    ]); 
    }
    
    return redirect()->route('merchant.gateway.intouch.operator')->withSuccess(trans("common.added_successfully")); 
  }
  public function intouchOperatorDelete($id){
    $intouch_operator_config= PaymentOptionsConfiguration::find($id);
    $intouch_operator_config->delete();   
    return redirect()->back()->withSuccess(trans("common.deleted_successfully")); 
  }
  public function intouch(){
     $merchant_id = get_merchant_id();
     $country_lit = Country::where('merchant_id', $merchant_id)->get();
     $intouch_data=DB::table('intouch_configuration')->where('merchant_id', $merchant_id)->paginate(15);
     $intouch_operator= PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_gateway_provider','=','INTOUCHGROUP']])->get();
     return view("merchant.random.intouch.index",compact('intouch_data','country_lit','intouch_operator'));  
     
  }
  public function intouch_create(){
    $merchant_id = get_merchant_id('false');
    $country_list = Country::where('merchant_id', $merchant_id)->get();
    $intouch_config = PaymentOption::where([['slug','=','INTOUCHGROUP']])->first();
    $intouch_operator_config= PaymentOptionsConfiguration::where([['merchant_id', '=', $merchant_id],['payment_option_id','=',$intouch_config->id]])->get();
    return view('merchant.random.intouch.create',compact('country_list','intouch_operator_config'));  
  }
  public function intouch_store(Request $request){
       $merchant_id = get_merchant_id();
        $validate_rule = [
        'country_id' => 'required',
        'partner_id' => 'required',
        'operator_name'=>'required|array|min:1',
        'login_api'=>'required',
        'password_api'=>'required',
        'agency_code'=>'required',
         ];  
       $request->validate($validate_rule);
        DB::beginTransaction();
        try {
        DB::table('intouch_configuration')->insert([
        'merchant_id'=>$merchant_id,
        'country_id'=>$request->country_id,
        'partner_id'=>$request->partner_id,
        'login_api'=>$request->login_api,
        'password_Api'=>$request->password_api,
        'agency_code'=>$request->agency_code,
        ]);
         $country = Country::find($request->country_id);
         $country->operator()->sync($request->input('operator_name'));
        }catch (\Exception $e) {
            DB::rollBack();
            return redirect()->withSuccess(trans("country already added"));  
        }
        DB::commit(); 
        return redirect()->route('merchant.gateway.intouch')->withSuccess(trans("common.added_successfully"));  
    }
  
    public function intouch_edit($id){
    $merchant_id = get_merchant_id('false');
    $intouch_operator= DB::table('intouch_configuration')->find($id);
    $country = Country::where('id', $intouch_operator->country_id)->first();
    $merchant = Merchant::find($merchant_id);
    $payment_options=$merchant->PaymentOptionsConfiguration;
    return view('merchant.random.intouch.edit',compact('country','intouch_operator','payment_options'));   
    }
    
    public function intouch_update(Request $request, $id){
        $merchant_id = get_merchant_id();
        $validate_rule = [
        'country_id' => 'required',
        'partner_id' => 'required',
        'operator_name'=>'required|array|min:1',
        'login_api'=>'required',
        'password_api'=>'required',
        'agency_code'=>'required',
         ];  
        $request->validate($validate_rule);
        DB::beginTransaction();
        try {
        DB::table('intouch_configuration')->where('id',$id)->update([
        'merchant_id'=>$merchant_id,
        'country_id'=>$request->country_id,
        'partner_id'=>$request->partner_id,
        'login_api'=>$request->login_api,
        'password_Api'=>$request->password_api,
        'agency_code'=>$request->agency_code,
        ]);
         $country = Country::find($request->country_id);
         $country->operator()->sync($request->input('operator_name'));
        }catch (\Exception $e) {
        DB::rollBack();
        return redirect()->withSuccess(trans("common.country already added"));  
    }
    DB::commit(); 
    return redirect()->route('merchant.gateway.intouch')->withSuccess(trans("common.updated_successfully"));   

    }
    
   public function intouch_delete($id){
    $intouch_data=DB::table('intouch_configuration')->where('id',$id);
    $data=$intouch_data->first();
    $country_operator=DB::table('country_payment_operator')->where('country_id',$data->country_id);
    $country_operator->delete();
    $intouch_data->delete();   
    return redirect()->back()->withSuccess(trans("common.deleted_successfully"));  
   }
  public function twilio(){
    $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
    $twilio_config = SmsConfiguration::where([['merchant_id', '=', $merchant_id],['sms_provider','=','TWILLIO']])->first();
    $config = Configuration::where('merchant_id',$merchant_id)->first();
    return view("merchant.random.twilio",compact('twilio_config','config'));  
  }
  public function twilio_store(Request $request){
    $merchant_id = get_merchant_id();
    $config = Configuration::where('merchant_id',$merchant_id)->first();
    $validate_rule = [
        'api_key' => 'required',
        'auth_token' => 'required',
        'sender_number'=>'required'
    ];
        $request->validate($validate_rule);
        $twilio_config = SmsConfiguration::where([['merchant_id', '=', $merchant_id],['sms_provider','=','TWILLIO']])->first();
        $twilio_config->api_key = $request->api_key;
        $twilio_config->auth_token = $request->auth_token;
        $twilio_config->sender_number=$request->sender_number;
        $twilio_config->save();
        VersionManagement::updateVersion($merchant_id);
        return redirect()->back()->withSuccess(trans("common.added_successfully"));    
  }
}
