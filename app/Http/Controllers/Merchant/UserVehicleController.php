<?php

namespace App\Http\Controllers\Merchant;

use DB;
use Session;
use App\Models\User;
use App\Models\Merchant;
use App\Traits\AreaTrait;
use App\Traits\ImageTrait;
use App\Models\CountryArea;
use App\Models\UserVehicle;
use App\Models\VehicleMake;
use App\Models\VehicleType;
use App\Models\UserDocument;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use App\Models\Configuration;
use App\Models\DriverVehicle;
use Illuminate\Validation\Rule;
use App\Models\UserVehicleDocument;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;


class UserVehicleController extends Controller
{
    use AreaTrait,ImageTrait;

    public function vehicleList($id)
    {

        $merchant_id = get_merchant_id('false');
        $user = User::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $user_vehicle = UserVehicle::with('vehicleType', 'vehicleModel', 'vehicleMake')->where([['user_id', '=', $id], ['merchant_id', '=', $merchant_id]])->paginate(20);
        return view('merchant.user.vehicle_list', compact('user_vehicle', 'user'));
    }

    public function vehicleAdd($user_id)
    {

        $merchant_id = get_merchant_id('false');
        $user = User::where([['id', '=', $user_id], ['merchant_id', '=', $merchant_id]])->first();
        $country_area = CountryArea::where('country_id', $user->country_id)->get();
        $vehicle_make = VehicleMake::where('merchant_id', $user->merchant_id)->get();
        return view('merchant.user.create_vehicle', compact('country_area', 'user', 'vehicle_make'));
    }

    public function saveVehicle(Request $request, $id = NULL)
    {
        $merchant_id = get_merchant_id('false');
        $validator = Validator::make($request->all(), [
            'area_id' => 'required|exists:country_areas,id',
            'vehicle_type_id' => 'required',
            'vehicle_make_id' => 'required',
            'vehicle_model_id' => 'required',
            'vehicle_number' => ['required',
                Rule::unique('user_vehicles', 'vehicle_number')->where(function ($query) use ($merchant_id) {
                    return $query->whereNull('vehicle_delete')->where([['merchant_id', '=', $merchant_id]]);
                })],
            'vehicle_color' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();

            return redirect()->back()->withErrors($errors);
        }

        DB::beginTransaction();
        try {
            $user = User::where([['id', '=', $id], ['merchant_id', '=', $merchant_id]])->first();
            $user->country_area_id = $request->area_id;
            $user->save();
            $vehicle_image = $this->uploadImage('vehicle_image', 'user_vehicle_document', $merchant_id);

            $vehicle_number_plate_image = $this->uploadImage('vehicle_number_plate_image', 'user_vehicle_document', $merchant_id);

            $vehicle = UserVehicle::create([
                'user_id' => $user->id,
                'owner_id' => $user->id,
                'merchant_id' => $merchant_id,
                'vehicle_type_id' => $request->vehicle_type_id,
                'shareCode' => getRandomCode(10),
                'vehicle_make_id' => $request->vehicle_make_id,
                'vehicle_model_id' => $request->vehicle_model_id,
                'vehicle_number' => $request->vehicle_number,
                'vehicle_color' => $request->vehicle_color,
                'vehicle_image' => $vehicle_image,
                'vehicle_number_plate_image' => $vehicle_number_plate_image,
                'ac_nonac' => $request->ac_nonac,
                'vehicle_verification_status' => 0,  //Pending with document
                'vehicle_register_date' => $request->vehicle_register_date
            ]);

            $vehicle->Users()->attach($user->id, ['vehicle_active_status' => 2]);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            DB::rollback();
            return redirect()->back()->with('error', $message);
        }

        DB::commit();
        return redirect()->route('merchant.user.vehicle_list', ['id' => $user->id])->with('success', 'Driver added successfully');

    }

    public function vehicleType(Request $request)
    {
        $country_area_id = $request->id;
        $merchant_id = get_merchant_id('false');
        $merchant = Merchant::Find($merchant_id);
        $merchant_segment = array_pluck($merchant->Segment, 'id');

        $merchant_service = array_pluck($merchant->ServiceType, 'id');

        $area_vehicle = CountryArea::select('id')->with(['VehicleType' => function ($q) use ($merchant_segment, $merchant_service, $merchant_id) {
            $q->addSelect('id', 'vehicle_type_id', 'vehicleTypeImage', 'vehicleTypeMapImage');
            $q->where('merchant_id', $merchant_id);
            $q->whereIn('segment_id', $merchant_segment);
            $q->whereIn('service_type_id', $merchant_service);
            $q->orderBy('vehicleTypeRank');
        }])->find($country_area_id);
        $return_data = (object)[];
        if (!empty($area_vehicle)) {
            $vehicleTypes = $area_vehicle->VehicleType->unique();
            $vehicleTypes = $vehicleTypes->map(function ($value) use ($merchant_id) {
                return [
                    'id' => $value->id,
                    'vehicleTypeName' => $value->VehicleTypeName,
                ];
            });
            $return_data = $vehicleTypes;
        }
        return response()->json($return_data);

    }

    public function VehicleModel(Request $request)
    {
        $request->validate([
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'vehicle_make_id' => 'required|exists:vehicle_makes,id',
        ]);
        $data = VehicleModel::where([['vehicle_type_id', '=', $request->vehicle_type_id], ['vehicle_make_id', '=', $request->vehicle_make_id]])->get();
        foreach ($data as $vehicle) {
            if (!empty($vehicle->LanguageVehicleModelSingle)) {
                $name = $vehicle->LanguageVehicleModelSingle->vehicleModelName;
            } else {
                $name = $vehicle->LanguageVehicleModelAny->vehicleModelName;
            }
            echo "<option value='" . $vehicle->id . "'>" . $name . "</option>";
        }
    }

    public function EditVehicle($id)
    {
        $merchant_id = get_merchant_id('false');
        $user_vehicle = UserVehicle::where([['id', '=', $id], ['merchant_id', '=', $merchant_id]])->first();
        return view('merchant.user.edit_vehicle', compact('user_vehicle'));
    }


    public function UpdateVehicle(Request $request, $id = NULL)
    {


        $merchant_id = get_merchant_id('false');
        $validator = Validator::make($request->all(), [
            'vehicle_number' => ['required',
                Rule::unique('user_vehicles', 'vehicle_number')->where(function ($query) use ($request, $merchant_id) {
                    return $query->whereNull('vehicle_delete')->where([['merchant_id', '=', $merchant_id]]);
                })->ignore($id)],
            'vehicle_color' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();

            return redirect()->back()->withErrors($errors);
        }
        DB::beginTransaction();
        try {


            $user_vehicle = UserVehicle::find($id);
            $user_vehicle->vehicle_number = $request->vehicle_number;
            $user_vehicle->vehicle_color = $request->vehicle_color;
            if ($request->hasFile('vehicle_image')) {
                $user_vehicle->vehicle_image = $this->uploadImage('vehicle_image', 'user_vehicle_document', $merchant_id);
            }
            if ($request->hasFile('vehicle_number_plate_image')) {
                $user_vehicle->vehicle_number_plate_image = $this->uploadImage('vehicle_number_plate_image', 'user_vehicle_document', $merchant_id);
            }
            $user_vehicle->save();

        } catch (\Exception $e) {
            $message = $e->getMessage();

            DB::rollback();
            return redirect()->back()->with('error', $message);
        }

        DB::commit();
        return redirect()->route('merchant.user.vehicle_list', ['id' => $user_vehicle->user_id])->with('success', 'Driver added successfully');

    }
    public function AllVehicle(Request $request)
    {
        $merchant_id = get_merchant_id();
        //$user_vehicles = UserVehicle::where([['merchant_id', '=', $merchant_id]])->latest()->paginate(10);
        $user_vehicles=UserVehicle::with('User')->where([['merchant_id', '=', $merchant_id], ['vehicle_verification_status', 2]])->whereNull('vehicle_delete')->latest()->paginate(10);
        // p($user_vehicles);
        //$request->request->add(['verification_status'=>'verified']);
        $areas = $this->getMerchantCountryArea($this->getAreaList(false)->get());
        $arr_search = $request->all();
//        $arr_search['merchant_id'] = $merchant_id;
        return view('merchant.uservehicles.all_vehicles', compact('user_vehicles', 'areas', 'arr_search'));
    }

    public function PendingVehicle(Request $request)
    {
        $checkPermission = check_permission(1, 'view_pending_vehicle_apporvels');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = get_merchant_id();
       // $user_vehicles = UserVehicle::where([['merchant_id', '=', $merchant_id]])->latest()->paginate(10);
        $user_vehicles=UserVehicle::with('User')->where([['merchant_id', '=', $merchant_id], ['vehicle_verification_status', 1]])->latest()->paginate(10);
        // $request->request->add(['verification_status'=>'pending']); // get pending vehicle

        $areas = $this->getMerchantCountryArea($this->getAreaList(false)->get());
       $arr_search = $request->all();
        return view('merchant.uservehicles.pending_vehicles', compact('user_vehicles', 'areas','arr_search'));
    }
    public function RejectedVehicle()
    {

        $merchant_id = get_merchant_id();
        $query = UserVehicle::with('User')->where([['merchant_id', '=', $merchant_id], ['vehicle_verification_status', 3]])->orderBy('id', 'DESC');
        $user_vehicles = $query->paginate(20);
        return view('merchant.uservehicles.vehicle-rejected', compact('user_vehicles'));
    }
    public function DeletedVehicle()
    {

        $merchant_id = get_merchant_id();
        $query = UserVehicle::with('User')->where([['merchant_id', '=', $merchant_id], ['vehicle_delete', 1]])->orderBy('id', 'DESC');
        $user_vehicles = $query->paginate(20);
        return view('merchant.uservehicles.vehicle-deleted', compact('user_vehicles'));
    }
    public function VehiclesDetail($id)
    {
        $vehicle = UserVehicle::with(['UserVehicleDocument'])->findOrFail($id);
       $user = $vehicle->User->id;

       $result = check_user_document($user, $type = 'vehicles', $id);

        return view('merchant.uservehicles.vehicle-details', compact('vehicle','result'));
    }
    
    public function UserVehicle(Request $request)
    {
        $merchant_id = get_merchant_id();
        $user_vehicles=UserVehicle::with('User')->where([['merchant_id', '=', $merchant_id], ['vehicle_verification_status', 2]])->whereNull('vehicle_delete')->latest()->paginate(10);
        $areas = $this->getMerchantCountryArea($this->getAreaList(false)->get());
        $arr_search = $request->all();
        return view('merchant.uservehicles.user_vehicles', compact('user_vehicles', 'areas', 'arr_search'));
    }
}
