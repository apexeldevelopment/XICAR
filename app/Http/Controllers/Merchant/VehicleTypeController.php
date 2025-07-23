<?php

namespace App\Http\Controllers\Merchant;

use App\Models\InfoSetting;
use App\Models\LanguageVehicleType;
use Auth;
use App;
use DB;
use App\Models\VehicleType;
use App\Models\Merchant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Http\Requests\VehicleTypeRequest;
use App\Models\DriverVehicle;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Helper\Merchant as helperMerchant;

class VehicleTypeController extends Controller
{
    use MerchantTrait,ImageTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','VEHICLE_TYPE')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index(Request $request)
    {
        $checkPermission =  check_permission(1,'view_vehicle_type');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $arr_vehicle_type = [];
        $vehicle_type = $request->vehicle_type;
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $vehicle_model_expire = $merchant->Configuration->vehicle_model_expire;
        $query = VehicleType::where([['merchant_id', '=', $merchant_id],['admin_delete', '=', NULL]]);
        if(!empty($vehicle_type))
        {
            $query->with(['LanguageVehicleTypeSingle'=>function($q) use($vehicle_type,$merchant_id){
                $q->where('vehicleTypeName',$vehicle_type)->where('merchant_id',$merchant_id);
            }])->whereHas('LanguageVehicleTypeSingle',function($q) use($vehicle_type,$merchant_id){
                $q->where('vehicleTypeName',$vehicle_type)->where('merchant_id',$merchant_id);
            });
        }

        $vehicles =  $query->latest()->paginate(10);
        $segment = array_pluck($merchant->Segment, 'slag');
        $one_vehicle_segment = array('2' => 'FOOD','3' => 'GROCERY');
        sort($segment);
        sort($one_vehicle_segment);
        $one_type_check = false;
        if ($segment == $one_vehicle_segment){
            $one_type_check = true;
        }

        $arr_vehicle_type['search_route'] = route('vehicletype.index');
        $arr_vehicle_type['arr_search'] = $request->all();
        $arr_vehicle_type['merchant_id'] = $merchant_id;
        $arr_vehicle_type['vehicle_type'] = $vehicle_type;
        $gallery_images = get_config_image("image_gallery");
        $images_arr = [];
        foreach($gallery_images as $image){
            array_push($images_arr, view_config_image($image));
        }
        $images_arr = json_encode($images_arr);
//p($arr_vehicle_type);
       // $delivery_types = DeliveryType::where('merchant_id' , $merchant_id)->get();
        return view('merchant.vehicletype.index', compact('vehicles', 'merchant', 'segment', 'one_type_check','vehicle_model_expire','arr_vehicle_type','images_arr'));
    }

    public function create(){
        $checkPermission =  check_permission(1,'create_vehicle_type');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $vehicle_model_expire = $merchant->Configuration->vehicle_model_expire;
        $seat_capacity_config = $merchant->Configuration->seats_capacity;
        $engine_type_enable = $merchant->ApplicationConfiguration->engine_based_vehicle_type;
        $merchant_segment = helperMerchant::MerchantSegments(1);
        // $gallery_images = get_config_image("image_gallery");
        // $images_arr = [];
        // foreach($gallery_images as $image){
        //     $filename = basename($image);
        //     array_push($images_arr, view_config_image($image));
        // }
        // $vehicle_images = Storage::disk('s3')->allFiles('all-in-one/vehicle');
        // foreach($vehicle_images as $image){
        //     $filename = basename($image);
        //     array_push($images_arr, view_config_image($image));
        // }
        // $images_arr = json_encode(array_unique($images_arr));
        $gallery_images = get_config_image("image_gallery");
        $images_arr = [];
        $vehicle_images = Storage::disk('s3')->allFiles('all-in-one/vehicle');
        foreach ($gallery_images as $key1 => $image1) {
            $basename1 = basename($image1);
            foreach ($vehicle_images as $key2 => $image2) {
                $basename2 = basename($image2);
                if ($basename1 === $basename2) {
                    // Remove common image from array1 and array2
                    unset($gallery_images[$key1]);
                    unset($vehicle_images[$key2]);
                    // Add common image to array3
                    $images_arr[] = view_config_image($image1);
                    // Break the inner loop since a match is found
                    break;
                }
            }
        }
        foreach($gallery_images as $image){
            array_push($images_arr, view_config_image($image));
        }
        foreach($vehicle_images as $image){
            array_push($images_arr, view_config_image($image));
        }
        $images_arr = json_encode($images_arr);
        return view('merchant.vehicletype.create', compact('merchant','vehicle_model_expire','images_arr','seat_capacity_config','merchant_segment', 'engine_type_enable'));
    }

    public function store(VehicleTypeRequest $request)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = $merchant->id;
        DB::beginTransaction();
        try {
            $volumetric = $request->volumetric_capacity ? $request->volumetric_capacity : NULL;
            // Check if the volumetric value contains a hyphen
            if ($request->volumetric_capacity && strpos($request->volumetric_capacity, '-') === false) {
                // If no hyphen is found, create a range from 0 to the given value
                $volumetric = "0-$volumetric";
            }
            $vehicle_type = VehicleType::create([
                'merchant_id' => $merchant_id,
                'vehicleTypeImage' => isset($request->vehicle_image) && !empty($request->vehicle_image) ? $this->uploadImage('vehicle_image', 'vehicle') : $this->copyFile($request->gallery_image,'image-gallery','all-in-one/vehicle'),
                'is_gallery_image_upload'=> isset($request->vehicle_image) && !empty($request->vehicle_image) ? 2 : 1,
                'vehicleTypeDeselectImage' => "",
                'vehicleTypeMapImage' => $request->vehicle_map_image,
                'vehicleTypeRank' => $request->vehicle_rank,
                'pool_enable' => $request->pool_enable,
                'in_drive_enable' => isset($request->in_drive_enable) ? $request->in_drive_enable : 2,
                'sequence' => $request->sequence,
                'ride_now' => $request->ride_now,
                'ride_later' => $request->ride_later,
                'model_expire_year' => $request->model_expire_year,
                'passenger_seat_capacity' => $request->passenger_seat_capacity ? $request->passenger_seat_capacity : NULL,
                'package_weight_range' => $request->max_package_weight_range ? $request->max_package_weight_range : NULL,
                'volumetric_capacity'=> $volumetric,
                'engine_type'=>!empty($request->engine_type)? $request->engine_type : 1,
//                'delivery_type_id' => $request->delivery_name
            ]);
            $this->SaveLanguageVehicle($merchant_id, $vehicle_type->id, $request->vehicle_name, $request->description);

        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $checkPermission =  check_permission(1,'edit_vehicle_type');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $vehicle = VehicleType::where([['merchant_id', '=', $merchant_id]])->find($id);
        $segment = array_pluck($merchant->Segment, 'slag');
        $vehicle_model_expire = $merchant->Configuration->vehicle_model_expire;
        $seat_capacity_config = $merchant->Configuration->seats_capacity;
        $engine_type_enable = $merchant->ApplicationConfiguration->engine_based_vehicle_type;
        $is_demo = $merchant->demo == 1 ? true : false;
       // $delivery_types = DeliveryType::where('merchant_id' , $merchant_id)->get();
       $gallery_images = get_config_image("image_gallery");
        $images_arr = [];
        $vehicle_images = Storage::disk('s3')->allFiles('all-in-one/vehicle');
        foreach ($gallery_images as $key1 => $image1) {
            $basename1 = basename($image1);
            foreach ($vehicle_images as $key2 => $image2) {
                $basename2 = basename($image2);
                if ($basename1 === $basename2) {
                    // Remove common image from array1 and array2
                    unset($gallery_images[$key1]);
                    unset($vehicle_images[$key2]);
                    // Add common image to array3
                    $images_arr[] = view_config_image($image1);
                    // Break the inner loop since a match is found
                    break;
                }
            }
        }
        foreach($gallery_images as $image){
            array_push($images_arr, view_config_image($image));
        }
        foreach($vehicle_images as $image){
            array_push($images_arr, view_config_image($image));
        }
        $images_arr = json_encode($images_arr);
        return view('merchant.vehicletype.edit', compact('vehicle', 'merchant', 'segment','vehicle_model_expire','is_demo','images_arr','seat_capacity_config', 'engine_type_enable'));
    }

    public function update(Request $request, $id)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = $merchant->id;
        $locale = App::getLocale();
        $request->validate([
            'vehicle_name' => ['required', 'max:255',
                Rule::unique('language_vehicle_types', 'vehicleTypeName')->where(function ($query) use ($merchant_id, &$locale, &$id) {
                    $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', $locale], ['vehicle_type_id', '!=', $id]]);
                })],
            'vehicle_rank' => 'required|integer',
            'description' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $vehicle = VehicleType::where([['merchant_id', '=', $merchant_id]])->find($id);
            if ($vehicle->pool_enable == 1 && empty($request->pool_enable)) {

                $getDriverIds = DriverVehicle::whereHas('Drivers', function ($q) {
                    $q->where([['vehicle_active_status', '=', 1]]);
                })->with(['Driver' => function ($q) {
                    $q->where([['pool_ride_active', '=', 1]]);
                }])->where([['vehicle_type_id', '=', $id]])->get();

                if (!empty($getDriverIds->toArray())) {
                    App\Models\Driver::whereIn('id', array_pluck($getDriverIds, 'driver_id'))->update(['pool_ride_active' => 2]);
                }
            }
            $vehicle->vehicleTypeStatus = 1;
            $vehicle->pool_enable = $request->pool_enable;
            $vehicle->engine_type = !empty($request->engine_type)? $request->engine_type : 1;
            $vehicle->in_drive_enable = isset($request->in_drive_enable) ? $request->in_drive_enable : 2;
            $vehicle->ride_now = $request->ride_now;
            $vehicle->ride_later = $request->ride_later;
            $vehicle->vehicleTypeRank = $request->vehicle_rank;
            $vehicle->model_expire_year = $request->model_expire_year;
            if($request->passenger_seat_capacity){
                $vehicle->passenger_seat_capacity = $request->passenger_seat_capacity ? $request->passenger_seat_capacity : NULL;
            }
            if($vehicle->engine_type == 1 && $request->max_package_weight_range){
                $vehicle->package_weight_range = $request->max_package_weight_range ? $request->max_package_weight_range : NULL;
            }

           
            if($vehicle->engine_type == 1 && $request->volumetric_capacity){
                $volumetric = $request->volumetric_capacity;
                // Check if the volumetric value contains a hyphen
                if (strpos($volumetric, '-') === false) {
                    // If no hyphen is found, create a range from 0 to the given value
                    $volumetric = "0-$volumetric";
                }
                $vehicle->volumetric_capacity =  $volumetric;
            }
           if ($request->hasFile('vehicle_image') || $request->gallery_image) {
                // $vehicle->vehicleTypeImage = $this->uploadImage('vehicle_image', 'vehicle');
                $vehicle->vehicleTypeImage = isset($request->vehicle_image) && !empty($request->vehicle_image) ? $this->uploadImage('vehicle_image', 'vehicle') : $this->copyFile($request->gallery_image,'image-gallery','all-in-one/vehicle');
                if(!isset($request->vehicle_image) && empty($request->vehicle_image)){
                    $vehicle->is_gallery_image_upload = 1;
                }elseif(isset($request->vehicle_image) && !empty($request->vehicle_image)){
                    $vehicle->is_gallery_image_upload = 2;
                }
            }
            if ($request->hasFile('vehicle_deselected_image')) {
                // $vehicle->vehicleTypeDeselectImage = $this->uploadImage('vehicle_deselected_image', 'vehicle');
                $vehicle->vehicleTypeDeselectImage = isset($request->vehicle_image) && !empty($request->vehicle_image) ? $this->uploadImage('vehicle_deselected_image', 'vehicle') : $this->copyFile($request->gallery_image,'image-gallery','all-in-one/vehicle');
            }
            $vehicle->vehicleTypeMapImage = $request->vehicleTypeMapImage;
            $vehicle->sequence = $request->sequence;
            //$vehicle->delivery_type_id = $request->delivery_name;
            

            $vehicle->save();
            $this->SaveLanguageVehicle($merchant_id, $id, $request->vehicle_name, $request->description);

        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function SaveLanguageVehicle($merchant_id, $vehicle_type_id, $name, $description)
    {
        LanguageVehicleType::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'vehicle_type_id' => $vehicle_type_id
        ], [
            'vehicleTypeName' => $name,
            'vehicleTypeDescription' => $description,
        ]);
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $delete = VehicleType::FindorFail($id);
        $string_file = $this->getStringFile(NULL, $delete->Merchant);
        if (!empty($delete->id)):
                $delete->admin_delete = 1;
                $delete->save();
            echo trans("$string_file.data_deleted_successfully");
        else:
            echo trans("$string_file.some_thing_went_wrong");
        endif;
    }

    public function updateStatus($id, $status)
    {
        $vehicle_type = VehicleType::FindorFail($id);
        $string_file = $this->getStringFile(NULL, $vehicle_type->Merchant);
        if (!empty($vehicle_type->id)):
            $vehicle_type->vehicleTypeStatus = $status;
            $vehicle_type->save();
            return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
        else:
            return redirect()->back()->withSuccess(trans("$string_file.some_thing_went_wrong"));
        endif;
    }
}
