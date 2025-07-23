<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Helper\HolderController;
use App\Models\FailBooking;
use App\Models\InfoSetting;
use App\Models\Onesignal;
use Auth;
use App\Models\Driver;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\BookingConfiguration;
use App\Traits\DeliveryBookingTrait;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Events\SendUserInvoiceMailEvent;
use App\Models\VehicleDeliveryPackage;
use App\Models\VehicleType;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;
use App;
use App\Models\CountryArea;

class VehicleDeliveryPackageController extends Controller
{
    use MerchantTrait,ImageTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','vehicle_delivery_package')->first();
        view()->share('info_setting', $info_setting);
    }
    
    public function index(){
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = $merchant->id;
        $packages = $merchant->VehicleDeliveryPackage;
        return view('delivery.delivery-package.index',compact('packages','string_file'));
    }


    public function add(Request $request, $id = NULL){
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        try {
            $package = NULL;
            // $vehicleType = VehicleType::where('merchant_id',$merchant_id)->get();
            $areas = CountryArea::with(['VehicleType' => function ($q) {
                $q->where('admin_delete', NULL);
            }])->where('merchant_id', $merchant_id)->get();
            // dd($areas);
            $segment_group_id = [1, 3, 4]; // With Carpooling, Bus Booking
            $arr_segment_services = $this->getMerchantSegmentServices($merchant_id, '', $segment_group_id, [], NULL, false, [], NULL);
           
            if(!empty($id))
            {
                $package = VehicleDeliveryPackage::find($id);
            }

            if ($arr_segment_services) {
                $vehicleType = get_merchant_vehicle($merchant->VehicleType);
            }else {
                return redirect()->back()->withErrors(trans("$string_file.no_segment"));
            }
            return view('delivery.delivery-package.edit', compact('package','string_file','vehicleType','areas'));
        }catch(\Exception $e)
        {
            return redirect()->back()->withErrors($e->getMessage());
        }

    }

    public function update(Request $request, $id = NULL){
        // dd($request->all(),get_merchant_id(false));
        $locale = App::getLocale();
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        if(empty($id))
        {
                $request->validate([
                    'package_name' => ['required','max:191',
                        Rule::unique('language_vehicle_delivery_packages', 'package_name')
                            ->where(function ($query) use ($merchant_id, $locale, $id) {
                                // Join with vehicle_delivery_packages to filter by merchant_id
                                return $query->where('locale', $locale)
                                             ->whereIn('vehicle_delivery_package_id', function ($subQuery) use ($merchant_id) {
                                                 $subQuery->select('id')
                                                          ->from('vehicle_delivery_packages')
                                                          ->where('merchant_id', $merchant_id);
                                             })
                                             ->where('vehicle_delivery_package_id', '!=', $id); // Exclude current record in update
                            })
                    ],
                    'vehicle_type' => 'required',
                    'dead_weight'=> 'required',
                    'image' => 'required',
                    'package_length' => 'required_if:engine_type,1',
                    'package_width' => 'required_if:engine_type,1',
                    'package_height' => 'required_if:engine_type,1',
                    'area'=>'required',
                    'engine_type'=> 'required'
                ]);
        }else{
             $request->validate([
                    'vehicle_type' => 'required',
                    'dead_weight'=> 'required',
                    'engine_type'=> 'required',
                    'package_length' => 'required_if:engine_type,1',
                    'package_width' => 'required_if:engine_type,1',
                    'package_height' => 'required_if:engine_type,1',
                    'area'=>'required',
                ]);
        }
        $volumetric_capacity = (float)$request->package_length * (float)$request->package_width * (float)$request->package_height;
            $package = VehicleDeliveryPackage::updateOrCreate([
                    'id' => $id,
                    'merchant_id' => $merchant_id,
                    'vehicle_type_id'=> $request->vehicle_type,
                    'country_area_id' => $request->area,
                ],[
                'package_length' => $request->package_length,
                    'engine_type'=> $request->engine_type,
                'package_width' => $request->package_width,
                'package_height' => $request->package_height,
                'weight' => $request->dead_weight,
                'volumetric_capacity' => $volumetric_capacity,
                
            ]);

            if($request->hasFile('image')){
                $package->package_image = isset($request->image) && !empty($request->image) ? $this->uploadImage('image', 'vehicle_delivery_package_image',$merchant_id) : "";
                $package->save();
            }

            $this->SaveLanguageVehicleDeliveryPackage($package->id, $request->package_name);
            

            return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
        
    }

    public function SaveLanguageVehicleDeliveryPackage($delivery_package_id, $packageName)
    {
        App\Models\LanguageVehicleDeliveryPackage::updateOrCreate(['locale' => App::getLocale(), 'vehicle_delivery_package_id' => $delivery_package_id
        ], [
            'package_name'=> $packageName
        ]);
    }
}