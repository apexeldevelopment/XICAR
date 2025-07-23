<?php

namespace App\Http\Controllers\Merchant;

use App\Models\CarpoolingConfigCountry;
use App\Models\CarpoolingConfiguration;
use App\Models\Country;
use App\Traits\MerchantTrait;
use DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use PHPUnit\Util\Json;

class CarPoolingConfigurationController extends Controller
{
    use MerchantTrait;

    public function index()
    {
        $merchant_id = get_merchant_id('false');
        $carpool_payment_config = CarpoolingConfiguration::where('merchant_id', $merchant_id)->first();
        return view('merchant.carpool-configuration.carpool_payment_configuration', compact('carpool_payment_config'));
    }

    public function save(Request $request)
    {

        $merchant_id = get_merchant_id('false');
        DB::beginTransaction();
        try {
            CarpoolingConfiguration::updateOrCreate(
                ['merchant_id' => $merchant_id],
                [
                    'hold_money_before_ride_start' => $request->payment_duration_time,
                    'transfer_money_to_user' => $request->transfer_money_to_user,
                ]
            );
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return redirect()->back()->with('error', trans('common.error'));
        }
        DB::commit();
        return redirect()->back()->with('success', trans('common.success'));
    }


    public function CountryConfigCreate(){
        $merchant_id = get_merchant_id('false');
        $country_list = Country::where('merchant_id', $merchant_id)->get();
        return view('merchant.random.country_wise_carpooling_config',compact('country_list'));
    }
    public function countryConfig(Request $request){
        $country_data=CarpoolingConfigCountry::where('country_id',$request->id)->first();
        return response()->json($country_data);
        
    }
    public function StoreCountryCarpoolingConfig(Request $request)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = get_merchant_id('false');
        $validator = Validator::make($request->all(), [
            'country_id'=>['required',
                Rule::unique('carpooling_config_countries', 'country_id')->where(function ($query) use ($request, $merchant_id) {
                    return $query->where([['merchant_id', '=', $merchant_id]]);
                })->ignore($request->update_id)],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $insert = $request->all();
            unset($insert['_token']);
            $request= $insert;
            if($request['update_id'] == NULL) {
                $data = new CarpoolingConfigCountry();
                $data->merchant_id = $merchant_id;
                $data->country_id = $request['country_id'];
                $data->status=1;
                //$data->long_ride=$request['long_ride'];
                $data->short_ride=$request['short_ride'];
                $data->start_location_radius=$request['start_location_radius'];
                $data->drop_location_radius=$request['drop_location_radius'];
                $data->short_ride_time=$request['short_ride_time'];
                $data->long_ride_time=$request['short_ride_time'];
                $data->user_ride_start_time = $request['user_ride_start_time'];
                $data->user_document_reminder_time = $request['user_document_reminder_time'];
                $data->save();
            }
            else{
                $data=CarpoolingConfigCountry::find($request['update_id']);
                $data->merchant_id = $merchant_id;
                $data->country_id = $request['country_id'];
                $data->status=1;
                //$data->long_ride=$request['long_ride'];
                $data->short_ride=$request['short_ride'];
                $data->start_location_radius=$request['start_location_radius'];
                $data->drop_location_radius=$request['drop_location_radius'];
                $data->short_ride_time=$request['short_ride_time'];
                $data->long_ride_time=$request['short_ride_time'];
                $data->user_ride_start_time = $request['user_ride_start_time'];
                $data->user_document_reminder_time = $request['user_document_reminder_time'];
                $data->save();
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return redirect()->back()->with('errors',$message);
        }
        DB::commit();
        return redirect()->back()->with('success', trans("$string_file.success"));
    }
}
