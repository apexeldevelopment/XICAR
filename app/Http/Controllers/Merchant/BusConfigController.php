<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 12/2/24
 * Time: 4:54 PM
 */

namespace App\Http\Controllers\Merchant;


use App\Http\Controllers\Controller;
use App\Models\BusConfiguration;
use App\Traits\MerchantTrait;
use DB;
use Illuminate\Http\Request;

class BusConfigController extends Controller
{
    use MerchantTrait;

    public function index(){
        $merchant_id = get_merchant_id();
        $bus_booking_config = BusConfiguration::where("merchant_id", $merchant_id)->first();
        return view("merchant.bus-booking.config", compact("bus_booking_config"));
    }

    public function save(Request $request){
        DB::beginTransaction();
        try{
            $merchant = get_merchant_id(false);
            $string_file = $this->getStringFile(NULL, $merchant);

            BusConfiguration::updateOrCreate(["merchant_id" => $merchant->id], $request->toArray());

            DB::commit();
            return redirect()->back()->withSuccess(trans("$string_file.success"));
        }catch (\Exception $exception){
            DB::rollback();
            return redirect()->back()->withErrors($exception->getMessage());
        }
    }
}
