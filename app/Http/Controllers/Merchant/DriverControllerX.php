<?php

namespace App\Http\Controllers\Merchant;

use App\Events\DriverSignupWelcome;
use App\Helpers\StripeConnectHelper;
use App\Http\Controllers\Api\SubscriptionPackageController;
use App\Models\Booking;
use App\Models\DriverAccount;
use App\Models\ApplicationConfiguration;
use App\Models\Configuration;
use App\Models\Country;
use App\Models\CountryArea;
use App\Models\DriverDocument;
use App\Models\DriverRideConfig;
use App\Models\DriverSubscriptionRecord;
use App\Models\DriverVehicle;
use App\Models\DriverVehicleDocument;
use App\Models\DriverWalletTransaction;
use App\Models\EmailConfig;
use App\Models\Merchant;
use App\Models\Onesignal;
use App\Models\ReferralDiscount;
use App\Models\ReferralDriverDiscount;
use App\Models\RejectReason;
use App\Models\ServiceType;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\VehicleMake;
use App\Models\VehicleType;
use App\Traits\AreaTrait;
use App\Traits\DriverTrait;
use Auth;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Traits\ImageTrait;
use DB;
use App\Traits\DriverVehicleTrait;
use App\Models\BookingConfiguration;
use Session;
use App\Traits\MailTrait;
use App\Http\Controllers\Helper\Merchant as MerchantHelper;
use View;
use App\Http\Controllers\Helper\AjaxController;

class DriverControllerX extends Controller
{
    use DriverTrait, AreaTrait, ImageTrait, DriverVehicleTrait,MailTrait;

    public function index()
    {
        $checkPermission =  check_permission(1,'view_drivers');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $config = get_merchant_id(false);
        $merchant_id = $config->id;
        $drivers = $this->getAllDriver();
        $pendingdrivers = $this->getAllPendingDriver(false);
        $pendingdrivers = count($pendingdrivers->get());
        $pendingdriversVehicle = $this->getAllPendingVehicles(false);
        $pendingdriversVehicle = count($pendingdriversVehicle->get());
        $rejecteddrivers = count($this->getAllRejectedDrivers(false)->get());
        $basicDriver = $this->getAllBasicDriver(false)->count();
        $config->driver_wallet_status = $config->Configuration->driver_wallet_status;
        $config->subscription_package = $config->Configuration->subscription_package;
        $config->gender = $config->ApplicationConfiguration->gender;
        $config->smoker = $config->ApplicationConfiguration->smoker;
        $config->driver_commission_choice = $config->ApplicationConfiguration->driver_commission_choice;
        $areas = $this->getAreaList(false)->get();
        $tempDocUploaded = $this->getAllTempDocUploaded(false)->count();
        $data = [];
        $data['merchant_id'] = $merchant_id;
        return view('merchant.driver.index', compact('drivers', 'rejecteddrivers', 'pendingdrivers', 'basicDriver', 'config', 'areas','tempDocUploaded','data','pendingdriversVehicle'));
    }

    public function DriverRides(Request $request, $id)
    {
        $merchant_id = get_merchant_id();
        $driver = Driver::where([['merchant_id', '=', $merchant_id]])->find($id);
        $bookings = Booking::where([['driver_id', '=', $id],['booking_status','=',1005]])->paginate(10);
        return view('merchant.driver.rides', compact('bookings', 'driver'));
    }

    public function driver_location(Request $request)
    {
        $driver_id = $request->driver_id;
        $driver = Driver::select('current_latitude', 'current_longitude')->find($driver_id);
        return $driver;
    }

    public function create()
    {
        $checkPermission =  check_permission(1,'create_drivers');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $countries = Country::where([['merchant_id', '=', $merchant_id]])->get();
        $area = $this->getAreaList(false);
        $areas = $area->get();
        $config = Merchant::find($merchant_id);
        $config->bank_details = $config->Configuration->bank_details_enable;
        $config->stripe_connect_enable = isset($config->Configuration->stripe_connect_enable) ? $config->Configuration->stripe_connect_enable : null;
        $config->driver_wallet_status = $config->Configuration->driver_wallet_status;
        $config->driver_address = $config->Configuration->driver_address;
        $config->gender = $config->ApplicationConfiguration->gender;
        $config->smoker = $config->ApplicationConfiguration->smoker;
        $account_types = $config->AccountType;
        $arr_commission_choice = $this->getDriverCommissionChoices($config);
        $segment_data['arr_segment'] = get_merchant_segment();
        $segment_data['selected'] = [];
        $segment_html = View::make('segment')->with($segment_data)->render();
        return view('merchant.driver.create', compact('areas', 'countries', 'config','account_types','segment_html','arr_commission_choice'));
    }
    public function store(Request $request)
    {
//        p($request->all());
        $merchant_id = get_merchant_id();
        $request->request->add(['phone' => $request->country . $request->phone]);
        $request->validate([
            'first_name' => 'required',
            'country' => 'required',
            'email' => ['required','email',
                Rule::unique('drivers', 'email')->where(function ($query) use ($merchant_id) {
                    return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                })],
            'phone' => ['required',
                Rule::unique('drivers', 'phoneNumber')->where(function ($query) use ($merchant_id) {
                    return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                })],
            'password' => 'required|confirmed|min:6|max:8',
            'area' => 'required|exists:country_areas,id',
            'image' => 'required|file'
        ]);
        $config = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
        $stripe_connect_enable = (isset($config->stripe_connect_enable) && $config->stripe_connect_enable == 1) ? true : false;
        DB::beginTransaction();
        try {
            $driver = new Driver();
            $driver_additional_data = NULL;
            if ($stripe_connect_enable) {
                $request->validate([
                    'address_postal_code' => 'required',
                    'address_line_1' => 'required',
                    'address_province' => 'required',
                    'city_name' => 'required',
                    'address_line_2' => 'required',
                    'account_holder_name' => 'required',
                    'account_number' => 'required',
                    'routing_number' => 'required',
                    'identity_document' => 'required|file',
                    'ssn' => 'required|unique:drivers,ssn',
                    'dob' => 'required'
                ]);
            }

            if($config->driver_address == 1) {
                $driver_additional_data = array("pincode" => $request->address_postal_code, "address_line_1" => $request->address_line_1, "province" => $request->address_province, "subhurb" => $request->address_suburb);
                if ($stripe_connect_enable) {
                    $driver_additional_data['city_name'] = $request->city_name;
                    $driver_additional_data['address_line_2'] = $request->address_line_2;
                }
                $driver_additional_data = json_encode($driver_additional_data, true);
            }

            $driver_store_data = [
                'merchant_id' => $merchant_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phoneNumber' => $request->phone,
                'driver_gender' => $request->driver_gender,
                'country_area_id' => $request->area,
                'password' => Hash::make($request->password),
                'profile_image' => $this->uploadImage('image', 'driver'),
                'bank_name' => $request->bank_name,
                'account_holder_name' => $request->account_holder_name,
                'account_number' => $request->account_number,
                'account_type_id' => $request->account_types,
                'online_code' => $request->online_transaction,
                'term_status' => 1,
                'last_ride_request_timestamp' => date("Y-m-d H:i:s"),
                'driver_referralcode' => $driver->GenrateReferCode(),
                'driver_additional_data' => $driver_additional_data,
                'subscription_wise_commission' => isset($request->subscription_wise_commission) ? $request->subscription_wise_commission : 2,// default commission based
            ];
            if($stripe_connect_enable) {
                $photo = $this->uploadImage('identity_document' , 'driver' , $merchant_id);
                $driver_store_data['sc_identity_photo'] = $photo;
                $driver_store_data['sc_identity_photo_status'] = 'pending';
                $driver_store_data['routing_number'] = $request->routing_number;
                $driver_store_data['sc_address_status'] = 'pending';
                $driver_store_data['ssn'] = $request->ssn;
                $driver_store_data['dob'] = formatted_date($request->dob);
                $driver_store_data['device_ip'] = $request->ip();
            }

            $driver = Driver::create($driver_store_data);
            $arr_segment = $request->input('segment');
            $driver->Segment()->sync($arr_segment);
            DriverRideConfig::create([
                'driver_id' => $driver->id,
                'smoker_type' => $request->smoker_type,
                'allow_other_smoker' => $request->allow_other_smoker,
            ]);
            # register driver to stripe connect
            if ($stripe_connect_enable) {
                $stripe_file = StripeConnectHelper::upload_file($request->identity_document);
                $verification_details = [
                    'photo_id_front' => $stripe_file->id
                ];
                StripeConnectHelper::create_driver_account($driver,$verification_details);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        DB::commit();
        return redirect()->route('merchant.driver.document.show', [$driver->id]);
    }

    public function EditDocument(Request $request, $id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $driver = Driver::with('DriverDocument')->where([['merchant_id', '=', $merchant_id], ['driver_delete', '=', NULL]])->findOrFail($id);
        return view('merchant.driver.edit-doc', compact('driver'));
    }

    public function NewDriver()
    {
        $drivers = $this->getAllBasicDriver();
        $areas = $this->getAreaList(false)->get();
        return view('merchant.driver.basic', compact('drivers', 'areas'));
    }

    public function NewDriverSearch(Request $request)
    {
        $query = $this->getAllBasicDriver(false);
        switch ($request->parameter) {
            case "1":
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "phoneNumber";
                break;
        }
        if ($request->keyword) {
            $query->where($parameter, 'like', '%' . $request->keyword . '%');
        }
        if ($request->area_id) {
            $query->where('country_area_id', '=', $request->area_id);
        }
        $drivers = $query->paginate(25);
        $areas = $this->getAreaList(false)->get();
        return view('merchant.driver.basic', compact('drivers', 'areas'));
    }

    public function PendingDriver()
    {
        $checkPermission =  check_permission(1,'pending_driver');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $checkPermission =  check_permission(1,'pending_drivers_approval');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $drivers = $this->getAllPendingDriver();
        return view('merchant.driver.pending', compact('drivers'));
    }

    public function TempDocPending(){
        $drivers = $this->getAllTempDocUploaded();
        return view('merchant.driver.pending', compact('drivers'));
    }

    public function AllVehicle()
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $vehicles = VehicleType::where([['merchant_id', '=', $merchant_id]])->get();
        $driver_vehicles = $this->getAllVehicles(true);
        $areas = $this->getAreaList(false)->get();
        $data = [];
        $data['merchant_id'] = $merchant_id;
        return view('merchant.drivervehicles.all_vehicles', compact('driver_vehicles', 'areas', 'vehicles','data'));
    }

    public function AllVehicleSearch(Request $request)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        switch ($request->parameter) {
            case "1":
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "phoneNumber";
                break;
        }
        $query = $this->getAllVehicles(false);
        if ($request->keyword) {
            $query->where($parameter, 'like', '%' . $request->keyword . '%');
        }
        if ($request->area_id) {
            $query->where('country_area_id', '=', $request->area_id);
        }
        if ($request->vehicletype) {
            $vehicletype = $request->vehicletype;
            $query->whereHas('DriverVehicles', function ($q) use ($vehicletype) {
                $q->where([['vehicle_type_id', '=', $vehicletype]]);
            })->with(['DriverVehicles' => function ($qq) use ($vehicletype) {
                $qq->where([['vehicle_type_id', '=', $vehicletype]]);
            }]);
        }
        if ($request->vehicleNumber) {
            $vehicleNumber = $request->vehicleNumber;
            $query->whereHas('DriverVehicles', function ($q) use ($vehicleNumber) {
                $q->where('vehicle_number', 'like', '%' . $vehicleNumber . '%');
            })->with(['DriverVehicles' => function ($qq) use ($vehicleNumber) {
                $qq->where('vehicle_number', 'like', '%' . $vehicleNumber . '%');
            }]);
        }
        $vehicles = VehicleType::where([['merchant_id', '=', $merchant_id]])->get();
        $driver_vehicles = $query->paginate(10);
        $areas = $this->getAreaList(false)->get();
        $data = $request->all();
        $data['merchant_id'] = $merchant_id;
        return view('merchant.drivervehicles.all_vehicles', compact('driver_vehicles', 'areas', 'vehicles','data'));
    }

    public function PendingVehicle()
    {
        $checkPermission =  check_permission(1,'view_pending_vehicle_apporvels');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $vehicles = VehicleType::where([['merchant_id', '=', $merchant_id]])->get();
        $driver_vehicles = $this->getAllPendingVehicles();
        $areas = $this->getAreaList(false)->get();
        $data = [];
        $data['merchant_id'] = $merchant_id;
        return view('merchant.drivervehicles.pending_vehicles', compact('driver_vehicles', 'areas', 'vehicles','data'));
    }

    public function PendingVehicleSearch(Request $request)
    {
        switch ($request->parameter) {
            case "1":
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "phoneNumber";
                break;
        }
        $query = $this->getAllPendingVehicles(false);
        if ($request->keyword) {
            $query->where($parameter, 'like', '%' . $request->keyword . '%');
        }
        if ($request->area_id) {
            $query->where('country_area_id', '=', $request->area_id);
        }
        if ($request->vehicletype) {
            $vehicletype = $request->vehicletype;
            $query->whereHas('DriverVehicles', function ($q) use ($vehicletype) {
                $q->where([['vehicle_type_id', '=', $vehicletype]]);
            })->with(['DriverVehicles' => function ($qq) use ($vehicletype) {
                $qq->where([['vehicle_type_id', '=', $vehicletype]]);
            }]);
        }
        if ($request->vehicleNumber) {
            $vehicleNumber = $request->vehicleNumber;
            $query->whereHas('DriverVehicles', function ($q) use ($vehicleNumber) {
                $q->where('vehicle_number', 'like', '%' . $vehicleNumber . '%');
            })->with(['DriverVehicles' => function ($qq) use ($vehicleNumber) {
                $qq->where('vehicle_number', 'like', '%' . $vehicleNumber . '%');
            }]);
        }
        $driver_vehicles = $query->paginate(10);
        $areas = $this->getAreaList(false)->get();
        $merchant_id = get_merchant_id();
        $vehicles = VehicleType::where([['merchant_id', '=', $merchant_id]])->get();
        $data = $request->all();
        $data['merchant_id'] = $merchant_id;
        return view('merchant.drivervehicles.pending_vehicles', compact('driver_vehicles', 'areas', 'vehicles','data'));
    }

    public function ShowDocument($id)
    {
        $merchant_id = get_merchant_id();
        $driver = Driver::with('DriverDocument')->where([['merchant_id', '=', $merchant_id]])->find($id)->toArray();
        $areas = CountryArea::with('Documents')->where('id', '=', $driver['country_area_id'])->first();
        return view('merchant.driver.create_document', compact('areas', 'id','driver'));
    }

    public function StoreDocument(Request $request, $id)
    {
        $merchant_id = get_merchant_id();
//        $validator = Validator::make($request->all(),[
//            'document' => 'required',
//            'document.*' => 'image|mimes:jpeg,jpg,png'
//        ]);
//
//        if ($validator->fails()){
//            $errors = $validator->messages()->all();
//            return redirect()->back()->with('error',$errors[0]);
//        }

        DB::beginTransaction();
                try {
                        $driver = Driver::where([['merchant_id', '=', $merchant_id]])->find($id);
//                        $images = $request->file('document');
                    $expiredate = $request->expiredate;
                    $images = $request->file('document');
                    $all_doc = $request->input('all_doc');
                    foreach ($all_doc as  $document_id) {
                        $image = isset($images[$document_id]) ? $images[$document_id] : null;
                        $expiry_date = isset($expiredate[$document_id]) ? $expiredate[$document_id] : NULL;
                        $driver_document =  DriverDocument::where([['driver_id',$id],['document_id',$document_id]])->first();
                        if(empty($driver_document->id))
                        {
                            $driver_document =  new DriverDocument;
                        }
                        $driver_document->driver_id = $id;
                        $driver_document->document_id = $document_id;
                        $driver_document->expire_date = $expiry_date;
                        $driver_document->document_verification_status = 2;
                        if(!empty($image))
                        {
                            $driver_document->document_file = $this->uploadImage($image,'driver_document',NULL,'multiple');
                        }
                        $driver_document->save();
                    }

//                        $status = 2; // means document are verified because
//                        $expiredate = $request->expiredate;
//                        foreach ($images as $key => $image) {
//                            $document_id = $key;
//                            $expiry_date = isset($expiredate[$key]) && !empty($expiredate[$key]) ? $expiredate[$key] : NULL;
//                            DriverDocument::create([
//                                'driver_id' => $id,
//                                'document_id' => $document_id,
//                                'document_file' => $this->uploadImage($image,'driver_document',NULL,'multiple'),
//                                'expire_date' => $expiry_date,
//                                'document_verification_status' => $status,
//                            ]);
//                        }
//                        $driver->signupStep = 1;
                        $driver->save();
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    p($message);
                    // Rollback Transaction
                    DB::rollback();
                }
                DB::commit();

        return redirect()->route('merchant.driver.vehicle.create', [$id]);
    }

    public function StoreEdit(Request $request, $id)
    {
        $request->validate([
//            'document' => 'required'
        ]);
                    $expiredate = $request->expiredate;
                    $images = $request->file('document');
                    $all_doc = $request->input('all_doc');
                    foreach ($all_doc as  $document_id) {
                        $image = isset($images[$document_id]) ? $images[$document_id] : null;
                        $expiry_date = isset($expiredate[$document_id]) ? $expiredate[$document_id] : NULL;
                        $driver_document =  DriverDocument::where([['driver_id',$id],['document_id',$document_id]])->first();
                        if(empty($driver_document->id))
                        {
                            $driver_document =  new DriverDocument;
                        }
                        $driver_document->driver_id = $id;
                        $driver_document->document_id = $document_id;
                        $driver_document->expire_date = $expiry_date;
                        $driver_document->document_verification_status = 2;
                        if(!empty($image))
                        {
                            $driver_document->document_file = $this->uploadImage($image,'driver_document',NULL,'multiple');
                        }
                        $driver_document->save();
                    }
        return redirect()->route('driver.index')->with('moneyAdded', trans('admin.editDocSucess'));
    }

    public function CreateVehicle($id)
    {
        $merchant_id = get_merchant_id();
        $driver = Driver::where([['merchant_id', '=', $merchant_id]])->find($id);
        $area_id = $driver->country_area_id;
        $vehicletypes = VehicleType::where([['merchant_id', '=', $merchant_id]])
            ->whereHas('CountryArea', function ($p) use ($area_id) {
                    $p->where('country_area_id',$area_id);
        })->get();

        $vehiclemakes = VehicleMake::where([['merchant_id', '=', $merchant_id]])->get();
        $docs = CountryArea::with('VehicleDocuments')->where('id', $driver->country_area_id)->first();
        $appConfig = ApplicationConfiguration::where('merchant_id','=',$merchant_id)->first();
        request()->session()->flash('message', trans('admin.driverAuto'));
        return view('merchant.driver.create_vehicle', compact('driver', 'vehicletypes', 'vehiclemakes', 'docs','appConfig'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreVehicle(Request $request, $id)
    {
        $merchant_id = get_merchant_id();
        $vehicle_id = $request->input('vehicle_id');
        $request->validate([
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'vehicle_make_id' => 'required',
            'vehicle_model_id' => 'required',
            'vehicle_number' => ['required',
                Rule::unique('driver_vehicles', 'vehicle_number')->where(function ($query) use ($merchant_id,$vehicle_id) {
                    return $query->where([['merchant_id', '=', $merchant_id],['id','!=',$vehicle_id],['driver_delete', '=', NULL]]);
                })],
            'vehicle_color' => 'required',
//            'document' => 'required',
//            'document.*' => 'image|mimes:jpeg,jpg,png',
//            'car_number_plate_image' => 'required',
//            'car_image' => 'required',
            'services' => 'required'
        ]);

        DB::beginTransaction();
                try {

                        $driver = Driver::where([['merchant_id', '=', $merchant_id]])->find($id);
                        $driver->signupStep = 3;
                        $driver->save();

                        $vehicle_active_status = get_driver_multi_existing_vehicle_status($id);
                        $vehicleMake_id = $this->vehicleMake($request->vehicle_make_id, $merchant_id);
                        $vehicle_seat = $request->vehicle_seat ? $request->vehicle_seat : 3;
                        $vehicleModel_id = $this->vehicleModel($request->vehicle_model_id, $merchant_id, $vehicleMake_id, $request->vehicle_type, $vehicle_seat);
                        $arr_data = [
                            'merchant_id' => $merchant_id,
                            'driver_id' => $id,
                            'owner_id' => $id,
                            'vehicle_type_id' => $request->vehicle_type_id,
                            'shareCode' => getRandomCode(10),
                            'vehicle_make_id' => $vehicleMake_id,
                            'vehicle_model_id' => $vehicleModel_id,
                            'vehicle_number' => $request->vehicle_number,
                            'vehicle_color' => $request->vehicle_color,
                            'vehicle_active_status' => $vehicle_active_status,
                            'vehicle_verification_status' => 1
                        ];
                        if(!empty($request->file('car_image')))
                        {
                            $arr_data['vehicle_image'] =  $this->uploadImage('car_image', 'vehicle_document');
                        }
                        if(!empty($request->file('car_number_plate_image')))
                        {
                            $arr_data['vehicle_number_plate_image'] = $this->uploadImage('car_number_plate_image', 'vehicle_document');
                        }
                        $vehicle = DriverVehicle::updateOrCreate(['id' => $vehicle_id, 'driver_id' => $id],$arr_data);
                        $vehicle->ServiceTypes()->sync($request->services);
                    $vehicle->Drivers()->attach($request->driver_id,['vehicle_active_status' => 2]);
                        $images = $request->file('document');
                        $expiredate = $request->expiredate;
                        $all_doc = $request->input('all_doc');
                    foreach ($all_doc as  $document_id) {
                        $image = isset($images[$document_id]) ? $images[$document_id] : null;
                        $expiry_date = isset($expiredate[$document_id]) ? $expiredate[$document_id] : NULL;
                        $driver_document =  DriverVehicleDocument::where([['driver_vehicle_id',$vehicle->id],['document_id',$document_id]])->first();
                        if(empty($driver_document->id))
                        {
                            $driver_document =  new DriverVehicleDocument;
                        }
                        $driver_document->driver_vehicle_id = $vehicle->id;
                        $driver_document->document_id = $document_id;
                        $driver_document->expire_date = $expiry_date;
                        $driver_document->document_verification_status = 2;
                        if(!empty($image))
                        {
                            $driver_document->document = $this->uploadImage($image,'vehicle_document',NULL,'multiple');
                        }
                        $driver_document->save();
                    }
//                        foreach ($images as $key => $image) {
//                            $document_id = $key;
//                            $expiry_date = isset($expiredate[$key]) ? $expiredate[$key] : NULL;
//                            DriverVehicleDocument::create([
//                                'driver_vehicle_id' => $vehicle->id,
//                                'document_id' => $document_id,
//                                'document' => $this->uploadImage($image, 'vehicle_document',NULL, 'multiple'),
//                                'expire_date' => $expiry_date,
//                                'document_verification_status' => 2,
//                            ]);
//                        }

                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    p($message);
                    // Rollback Transaction
                    DB::rollback();
                }
                DB::commit();

        return redirect()->route('driver.index');
    }

    public function EditVehicleDocument(Request $request, $id)
    {
        $drivervehicle = DriverVehicle::find($id);
        $documents = $drivervehicle->Driver->CountryArea->VehicleDocuments;
        return view('merchant.drivervehicles.edit-doc', compact('drivervehicle', 'documents'));
    }

    public function UpdateVehicleDocument(Request $request, $id)
    {
        $request->validate([
            'document' => 'required',
        ]);
        DB::beginTransaction();
                try {
                    $images = $request->file('document');
                    $expiredate = $request->expiredate;
                    foreach ($images as $key => $image) {
                        $document_id = $key;
                        $expiry_date = isset($expiredate[$key]) ? $expiredate[$key] : NULL;
                        DriverVehicleDocument::updateOrCreate([
                            'driver_vehicle_id' => $id,
                            'document_id' => $document_id,
                        ], [
                            'document' => $this->uploadImage($image, 'vehicle_document',NULL, 'multiple'),
                            'expire_date' => $expiry_date,
                            'document_verification_status' => 2,
                        ]);
                    }
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    p($message);
                    // Rollback Transaction
                    DB::rollback();
                }
                DB::commit();
        return redirect()->route('merchant.driver.allvehicles')->with('vehcile', trans('admin.editvehicleSucess'));
    }

    public function show($id)
    {
        $merchant = get_merchant_id(false);
        $rejectreasons = $merchant->RejectReason;
        $config = $merchant->Configuration;
        $application_config = $merchant->ApplicationConfiguration;
        $driver = Driver::select('id','first_name','last_name','phoneNumber','email','profile_image','country_area_id','wallet_money','account_holder_name',
            'merchant_driver_id','merchant_id','driver_additional_data','online_code')
                ->with(['DriverDocument' => function ($query) {
                 $query->with('Document');
                }])->where('id', $id)->first();
        $driver_config = $driver->DriverRideConfig;
        $driver_wallet = $driver->DriverWalletTransaction;
//        $result = check_driver_document($driver->id);
        $result = false;
//        p($result);
        $tempDocUploaded = $this->getAllTempDocUploaded(false,$driver->id)->count();
        $package_name = trans('admin.no_package_found');
        if(isset($config->subscription_wise_commission) && $config->subscription_wise_commission == 1){
            $package = DriverSubscriptionRecord::where([['driver_id','=',$driver->id],'status' => 2])->with('SubscriptionPackage')->first();
            if(!empty($package->SubscriptionPackage)){
                $package_name = $package->SubscriptionPackage->Name;
            }
        }
        $sharing_vehicles = DriverVehicle::whereHas('Drivers',function($q) use($driver){
            $q->where('driver_id','=',$driver->id);
        })->where('owner_id','!=',$driver->id)->get();
        //p($driver);
        return view('merchant.driver.show', compact('driver', 'rejectreasons', 'config','driver_wallet','driver_config', 'result','tempDocUploaded','package_name','sharing_vehicles','application_config'));
    }

    public function edit($id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $driver = Driver::where([['merchant_id', '=', $merchant_id], ['driver_delete', '=', NULL]])->findOrFail($id);
        $config = $merchant->ApplicationConfiguration;
        $configNew = $merchant->Configuration;
        $config->driver_wallet_status = $configNew->driver_wallet_status;
        $config->driver_address = $configNew->driver_address;
        $config->bank_details = $configNew->bank_details_enable;
        $config->stripe_connect_enable = isset($configNew->stripe_connect_enable) ? $configNew->stripe_connect_enable : null;
        $driver_additional_data = NULL;
        if($configNew->driver_address == 1 && $driver->driver_additional_data != ''){
            $driver_additional_data = (object)json_decode($driver->driver_additional_data, true);
        }
        $areas = CountryArea::where(['merchant_id' => $merchant_id,'country_id' => $driver->CountryArea->Country->id])->get();
        $arr_commission_choice = $this->getDriverCommissionChoices($merchant);
        return view('merchant.driver.edit', compact('driver', 'config', 'areas', 'driver_additional_data','arr_commission_choice'));
    }

    public function update(Request $request, $id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $request->request->add(['phone' => $request->phoneCode . $request->phone]);
        $request->validate([
            'first_name' => 'required',
            'email' => ['required','email',
                Rule::unique('drivers', 'email')->where(function ($query) use ($merchant_id) {
                    return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                })->ignore($id)],
            'phone' => ['required',
                Rule::unique('drivers', 'phoneNumber')->where(function ($query) use ($merchant_id) {
                    return $query->where([['driver_delete', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                })->ignore($id)],
            'password' => 'required_if:edit_password,1',
            // 'area' => 'required|exists:country_areas,id'
        ]);
        $config = $merchant->Configuration;
//            Configuration::where([['merchant_id', '=', $merchant_id]])->first();
        $stripe_connect_enable = (isset($config->stripe_connect_enable) && $config->stripe_connect_enable == 1) ? true : false;
        if($stripe_connect_enable){
            $request->validate([
                'ssn' => 'required|unique:drivers,ssn,' . $id,
                'dob' => 'required',
            ]);
        }

        $driver = Driver::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $is_subscription =  ($config->subscription_package == 1 && $merchant->ApplicationConfiguration->driver_commission_choice == 1) ? true : false;
        if($driver->free_busy == 1)
        {
            return redirect()->back()->with('error',trans('admin.driver_busy'));
        }
        elseif(($driver->subscription_wise_commission != $request->subscription_wise_commission) && $driver->login_logout == 1 && $is_subscription == true)
        {
            return redirect()->back()->with('error',trans('admin.driver_logout_warning'));
        }
        DB::beginTransaction();
        try {
            $driver_additional_data = NULL;
            $appConfig = $merchant->ApplicationConfiguration;
            // for stripe connect
            if ($stripe_connect_enable) {
                $valid = \validator($request->all() , [
                    'address_postal_code' => 'required',
                    'address_line_1' => 'required',
                    'address_province' => 'required',
                    'city_name' => 'required',
                    'address_line_2' => 'required',
                    'account_holder_name' => 'required',
                    'account_number' => 'required',
                    'routing_number' => 'required',
                ]);
                if ($valid->fails()) {
                    return redirect()->back()->withErrors($valid->errors());
                }
            }
            $driver->phoneNumber = $request->phone;
            $driver->first_name = $request->first_name;
            $driver->last_name = $request->last_name;
            $driver->email = $request->email;
            $driver->subscription_wise_commission = isset($request->subscription_wise_commission) ? $request->subscription_wise_commission : 2; // default commission based

            if($stripe_connect_enable) {
                if ($request->identity_document) {
                    $photo = $this->uploadImage('identity_document' , 'driver' , $merchant_id);
                    $driver->sc_identity_photo = $photo;
                    $driver->sc_identity_photo_status = 'pending';
                }
                $driver->ssn = $request->ssn;
                $driver->dob = formatted_date($request->dob);
            }

            if($request->hasFile('image')) {
                $driver->profile_image = $this->uploadImage('image','driver');
            }
            if($appConfig->gender == 1){
                $driver->driver_gender = $request->driver_gender;
            }
            if($appConfig->smoker == 1){
                DriverRideConfig::updateOrCreate(
                    ['driver_id' => $driver->id],
                    [
                        'smoker_type' => $request->smoker_type,
                        'allow_other_smoker' => $request->allow_other_smoker
                    ]);
            }

            if($config->driver_address == 1){
                $driver_additional_data = array("pincode" => $request->address_postal_code,"address_line_1" => $request->address_line_1,"province" => $request->address_province,"subhurb" => $request->address_suburb);
                if ($stripe_connect_enable) {
                    $driver_additional_data['city_name'] = $request->city_name;
                    $driver_additional_data['address_line_2'] = $request->address_line_2;
                }
                $driver_additional_data = json_encode($driver_additional_data, true);
                $driver->driver_additional_data = $driver_additional_data;
            }

            if($config->bank_details_enable == 1) {
                if ($stripe_connect_enable) {
                    $driver->routing_number = $request->routing_number;
                }
                else {
                    $driver->bank_name = $request->bank_name;
                    $driver->online_code = $request->online_transaction;
                }
                $driver->account_holder_name = $request->account_holder_name;
                $driver->account_number = $request->account_number;
            }

            if ($request->edit_password == 1) {
                $password = Hash::make($request->password);
                $driver->password = $password;
            }
            $driver->app_debug_mode = $request->app_debug_mode;
            $driver->save();

            # register driver to stripe connect
            if ($stripe_connect_enable) {
                $stripe_file = StripeConnectHelper::upload_file($request->identity_document);
                $verification_details = [
                    'photo_id_front' => $stripe_file->id
                ];
                StripeConnectHelper::create_driver_account($driver , $verification_details);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        DB::commit();
        return redirect()->route('driver.index')->with('success',trans('admin.message181'));
    }


    public function Wallet($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $driver = Driver::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $wallet_transactions = DriverWalletTransaction::where([['driver_id', '=', $id]])->paginate(25);
        return view('merchant.driver.wallet', compact('wallet_transactions', 'driver'));
    }

    public function VerifyDocument(Request $request, $id, $status)
    {
        $document = DriverDocument::findOrFail($id);
        $document->document_verification_status = $status;
        $document->save();
        return redirect()->back();
    }

    public function Reject(Request $request)
    {
        $request->validate([
            'reject_id' => 'required',
            'doc_id' => 'required',
        ]);
        $doc = DriverDocument::find($request->doc_id);
        $doc->document_verification_status = 3;
        $doc->reject_reason_id = $request->reject_id;
        $doc->save();
        return redirect()->back();
    }

    public function Vehicles($id)
    {
        $driver = Driver::with(['DriverVehicles' => function ($query) {
            $query->with('VehicleType', 'ServiceTypes');
        }])->findOrFail($id);
        return view('merchant.driver.vehicle', compact('driver'));
    }

    public function EditVehicle($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $Newconfig = Configuration::select('vehicle_ac_enable')->where([['merchant_id', '=', $merchant_id]])->first();
        $config = BookingConfiguration::select('baby_seat_enable','wheel_chair_enable')->where([['merchant_id', '=', $merchant_id]])->first();
        $config->vehicle_ac_enable = $Newconfig->vehicle_ac_enable;
        $vehicle = DriverVehicle::with('Driver')->findOrFail($id);
        // get only this vehicle type + country area and drive segment based services
        $driver_segment = array_pluck($vehicle->Driver->Segment,'id');
        $area_id = $vehicle->Driver->country_area_id;
        $helper = new  AjaxController;
        $service_types = $helper->get_vehicle_serices($area_id,$vehicle->vehicle_type_id,$driver_segment);
        return view('merchant.driver.edit-vehicle', compact('vehicle','config','service_types'));
    }

    public function UpdateVehicle(Request $request, $id = NULL)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $request->validate([
            'vehicle_number' => 'required',
            'vehicle_color' => 'required',
            'vehicle_number_plate' => 'mimes:jpeg,jpg,png',
            'vehicle_document' => 'mimes:jpeg,jpg,png',
        ]);

        DB::beginTransaction();
        try {
            $Newconfig = Configuration::select('vehicle_ac_enable')->where([['merchant_id', '=', $merchant_id]])->first();
            $config = BookingConfiguration::select('baby_seat_enable','wheel_chair_enable')->where([['merchant_id', '=', $merchant_id]])->first();

            $driver_vehicle = DriverVehicle::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);

            $driver_vehicle->vehicle_number = $request->vehicle_number;
            $driver_vehicle->vehicle_color = $request->vehicle_color;

            if ($request->hasFile('vehicle_number_plate')) {
                $driver_vehicle->vehicle_number_plate_image = $this->uploadImage('vehicle_number_plate','vehicle_document');
            }

            if ($request->hasFile('vehicle_image')) {
                $driver_vehicle->vehicle_image = $this->uploadImage('vehicle_image','vehicle_document');
            }

            if ($request->service_types){
                $old_service = array_pluck($driver_vehicle->ServiceTypes,'id');
                $allService = array_merge($old_service,$request->service_types);
                $driver_vehicle->ServiceTypes()->sync($allService);
            }

            if($Newconfig->vehicle_ac_enable == 1){
                $driver_vehicle->ac_nonac = $request->ac_nonac;
            }
            if($config->baby_seat_enable == 1){
                $driver_vehicle->baby_seat = $request->baby_seat;
            }
            if($config->wheel_chair_enable == 1){
                $driver_vehicle->wheel_chair = $request->wheel_chair;
            }
            $driver_vehicle->save();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        DB::commit();
        return redirect()->route('driver.index')->with('success',trans('admin.vehicle_updated_successfully'));
    }

    public function VehicleVerify($id, $status)
    {
        $merchant_id = NULL;
//        $merchant = Merchant::find($merchant_id);
        if ($status == 1) {
            $driver = Driver::findOrFail($id);
            if ($driver->FirstVehicle && ($driver->signupStep == 2 || $driver->signupStep == 3)) {
                $driver->FirstVehicle->vehicle_verification_status = 1;
                $driver->FirstVehicle->vehicle_active_status = get_driver_multi_existing_vehicle_status($driver->id);
                $driver->FirstVehicle->save();
                DriverVehicleDocument::where('driver_vehicle_id', $driver->FirstVehicle->id)->update(['document_verification_status' => 2]);
            }
            DriverDocument::where('driver_id', $driver->id)->update(['document_verification_status' => 2]);
            $driver->signupStep = 3; // Approve driver
            $driver->save();
            $ids = $driver->id;
            $merchant_id = $driver->merchant_id;
        } else {
            $vehicle = DriverVehicle::find($id);
            $vehicle->vehicle_verification_status = 1;
            $vehicle->save();
            DriverVehicleDocument::where('driver_vehicle_id', $vehicle->id)->update(['document_verification_status' => 2]);
            $ids = $vehicle->Driver->id;
            $merchant_id = $vehicle->Driver->merchant_id;
        }
        if (!empty($ids)) {
            $msg = $status == 1 ? trans('admin.driver_approved') : trans('admin.vehicle_approved', ['number' => $vehicle->vehicle_number]);
            $type = $status == 1 ? 13 : 15;
            Onesignal::DriverPushMessage($ids, [], $msg, $type, $merchant_id, 1);
        }
        return redirect()->back();
    }

    public function VehicleReject(Request $request)
    {
        // this function is called when click on reject button of either driver profile
        // or vehicle reject screen
        $request->validate([
            'driver_id' => 'required',
            'comment' => 'required',
        ]);
        $redirect = 'vehicle';
        $driver = Driver::find($request->driver_id);
        if ($request->pending_vehicle != 1) {
            $already_added_driver_vehicle = get_driver_verified_vehicle($driver->id, $request->driver_vehicle_id);
            if($already_added_driver_vehicle == 0) {
                $redirect = 'driver';
                $driver->signupStep = 2; // move to pending
                $driver->reject_driver = 2; // means reject the driver
            }
            $driver->admin_msg = $request->comment;
            $driver->save();
        }
        if ($request->driver_vehicle_id) {
            $vehicle = DriverVehicle::findOrFail($request->driver_vehicle_id);

            // if driver is owner of that vehicle then reject that driver when click on driver profile
            if($vehicle->owner_id == $request->driver_id)
            {
                $vehicle->vehicle_verification_status = 3;
                $vehicle->save();
                if (!empty($request->vehicle_documents)) {
                    DriverVehicleDocument::whereIn('id', $request->vehicle_documents)->update(['document_verification_status' => 3]);
                }
            }
        }
        if (!empty($request->document_id)) {
            DriverDocument::whereIn('id', $request->document_id)->update(['document_verification_status' => 3]);
        }
        if (!empty($driver->player_id)) {
            $type = $request->pending_vehicle == 1 ? 16 : 14;
            Onesignal::DriverPushMessage($driver->id, [], $request->comment, $type, $driver->merchant_id, 1);
        }
        if ($request->pending_vehicle != 1) {
            if($redirect == 'vehicle')
            {
            return redirect()->route('merchant.vehicle.rejected');
            }
            else{
                return redirect()->route('merchant.driver.rejected');
            }

        }
        return redirect()->back();
    }

    public function VehiclesDocumentReject(Request $request)
    {
        $request->validate([
            'reject_id' => 'required',
            'doc_id' => 'required',
        ]);
        $document = DriverVehicleDocument::findOrFail($request->doc_id);
        $document->document_verification_status = 3;
        $document->reject_reason_id = $request->reject_id;
        $document->save();
        return redirect()->back();
    }

    public function VehiclesDocument($id)
    {
        $vehicle = DriverVehicle::with(['DriverVehicleDocument'])->findOrFail($id);
        $driver = $vehicle->Driver->id;
        $result  = check_driver_document($driver,$type = 'vehicle',$id);
        return view('merchant.driver.vehicle-doc', compact('vehicle','result'));
    }

    public function VehiclesDocumentVerify($id, $status)
    {
        $document = DriverVehicleDocument::findOrFail($id);
        $document->document_verification_status = $status;
        $document->save();
        return redirect()->back();
    }

    public function AddMoney(Request $request)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $request->validate([
            'payment_method_id' => 'required|integer|between:1,2',
            'receipt_number' => 'required|string',
            'amount' => 'required|numeric',
            'driver_id' => 'required|exists:drivers,id'
        ]);
        $newAmount = new \App\Http\Controllers\Helper\Merchant();
        DriverWalletTransaction::create([
            'merchant_id' => $merchant_id,
            'driver_id' => $request->driver_id,
            'transaction_type' => 1,
            'payment_method' => $request->payment_method_id,
            'receipt_number' => $request->receipt_number,
            'amount' => sprintf("%0.2f", $request->amount),
            'platform' => 1,
            'description' => $request->description,
            'narration' => 1,
        ]);
        $driver = Driver::find($request->driver_id);
        $wallet_money = $driver->wallet_money + $request->amount;
        $driver->wallet_money = $newAmount->TripCalculation($wallet_money, $merchant_id);
        $driver->save();
//        $playerids = array($driver->player_id);
        $message = trans('api.money');
        $data = ['message' => $message];
        Onesignal::DriverPushMessage($driver->id, $data, $message, 3, $merchant_id);
        return success_response(trans('admin.message207'));
    }

    public function ShowSubscriptionPacks(Request $request, $driver_id = null)
    {
        $driver = Driver::findorfail($driver_id);
        $packages = SubscriptionPackage::where([['merchant_id', $driver->merchant_id], ['status', true]])
            ->whereHas('CountryArea', function ($query) use (&$driver) {
            $query->where('country_area_id', '=', $driver->CountryArea->id);
        })
            ->whereNotIn('id', function ($query) use ($driver_id) {
                $query->select('subscription_pack_id')->where('driver_id', $driver_id)->where('package_type',1)->from('driver_subscription_records');
            })
            ->select(['id', 'package_duration_id', 'max_trip', 'image', 'price','package_type'])
            ->get();
        $packages = $this->PaginateCollection($packages);

        return view('merchant.driver.show_subscription_packs', compact('driver', 'packages'));
    }
    public function Activated_Subscription(Request $request, $driver_id = null)
    {
        $driver = Driver::findorfail($driver_id);
        if ($driver->Merchant->Configuration->subscription_package != 1) {
            return redirect()->route('merchant.dashboard')->with('error',trans("common.permission_denied"));
        }

        $driver_all_packs = $driver->DriverSubscriptionRecord->sortByDesc('id');
        $driver_all_packs = $this->PaginateCollection($driver_all_packs);
        return view('merchant.driver.activated_subscription', compact('driver', 'driver_all_packs'));
    }

    public function Activate_Subscription_Wallet(Request $request, $id = null)
    {
        $driver = Driver::findorfail($id);
        $package = SubscriptionPackage::findorfail($request->package);
        if ($driver->wallet_money < $package->price):
            request()->session()->flash('error', trans('admin.driver_low_wallet'));
            return redirect()->route('driver.activated_subscription', $driver->id);
        endif;
        $driver->wallet_money = ($driver->wallet_money - $package->price);
        $driver->save();
        DriverWalletTransaction::create([
            'merchant_id' => $driver->merchant_id,
            'driver_id' => $driver->id,
            'transaction_type' => 2,
            'payment_method' => 3,
            'receipt_number' => rand(1111, 983939),
            'amount' => $package->price,
            'platform' => 2,
            'subscription_package_id' => $package->id,
            'description' => 'On Activation of Subscription Pack',
            'narration' => 4,
        ]);
        $request->request->add(['payment_method_id' => 3,
            'subscription_package_id' => $request->package,'driver_id'=>$id]);
        $package = new SubscriptionPackageController();
        $package->SavePackageDetails($request,true);
        request()->session()->flash('message', trans('admin.subspack_added'));
        return redirect()->route('driver.activated_subscription', $driver->id);
    }

    public function Activate_Subscription_Cash(Request $request, $id = null)
    {
        $request->request->add(['payment_method_id' => 1,
            'subscription_package_id' => $request->package,'driver_id'=>$id]);
        $package = new SubscriptionPackageController();
        $package->SavePackageDetails($request,true);
        request()->session()->flash('message', trans('admin.subspack_added'));
        return redirect()->route('driver.activated_subscription', $id);
    }
    public function PaginateCollection($items, $perPage = 5, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items); // Check Collection, or make Array into Collection
        return new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    public function ChangeStatus($id, $status)
    {
        $validator = Validator::make(
            [
                'id' => $id,
                'status' => $status,
            ],
            [
                'id' => ['required'],
                'status' => ['required', 'integer', 'between:1,2'],
            ]);
        if ($validator->fails()) {
            return redirect()->back();
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $driver = Driver::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        if ($status == 2){
            $booking = Booking::where('driver_id',$driver->id)->whereIn('booking_status',[1002,1003,1004])->latest()->first();
            if (empty($booking)){
                $driver->driver_admin_status = $status;
                $driver->online_offline = 2;
                $driver->login_logout = 2;
                $driver->save();
                $config = $driver->Merchant->Configuration;
                if (($config->existing_vehicle_enable == 1 && $config->demo != 1) || ($config->add_multiple_vehicle == 1 && $config->demo != 1)){
                    $driverVehicleDetails = DriverVehicle::whereHas('Drivers', function ($q) use ($driver) {
                        $q->where([['driver_id', '=', $driver->id], ['vehicle_active_status', '=', 1]]);
                    })->with(['Drivers' => function ($q) use ($driver) {
                        $q->where([['driver_id', '=', $driver->id], ['vehicle_active_status', '=', 1]]);
                    }])->first();
                    if (!empty($driverVehicleDetails)){
                        $drivers = $driverVehicleDetails->Drivers;
                        $vehicleActiveStatus = array();
                        foreach($drivers as $driverData){
                            $vehicleActiveStatus[] = $driverData->online_offline == 1 ? 1 : 2;
                        }
                        if(!in_array(1,$vehicleActiveStatus)){
                            $driverVehicleDetails->Drivers()->updateExistingPivot($driver->id, ['vehicle_active_status' => 2]);
                        }
                    }
                }
                $action = 'success';
                $msg = trans('admin.driver_deactivate');
            }else{
                $action = 'error';
                $msg = trans('admin.driver_on_ride_deact');
            }
        }else{
            $driver->driver_admin_status = $status;
            $driver->save();
            $action = 'success';
            $msg = trans('admin.driver_activate');
        }
//        $playerids = array($driver->player_id);
        $data = [];
        $message = $status == 2 ? trans('admin.message296') : trans('admin.message297');
        $type = 20; // 20 value set according to vishal's suggestion {done by @amba}
        Onesignal::DriverPushMessage($driver->id, $data, $message,$type, $merchant_id);
        return redirect()->back()->with($action,$msg);
    }

    public function Logout($id)
    {
        $merchant_id = get_merchant_id();
        $driver = Driver::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $booking = Booking::where('driver_id',$driver->id)->whereIn('booking_status',[1002,1003,1004])->latest()->first();
        if (empty($booking)){
            $driver->online_offline = 2;
            $driver->login_logout = 2;
            $driver->free_busy = 2;
            $driver->save();
            $config = $driver->Merchant->Configuration;
            if (($config->existing_vehicle_enable == 1 && $config->demo != 1) || ($config->add_multiple_vehicle == 1 && $config->demo != 1)){
                $driverVehicleDetails = DriverVehicle::whereHas('Drivers', function ($q) use ($driver) {
                    $q->where([['driver_id', '=', $driver->id], ['vehicle_active_status', '=', 1]]);
                })->with(['Drivers' => function ($q) use ($driver) {
                    $q->where([['driver_id', '=', $driver->id], ['vehicle_active_status', '=', 1]]);
                }])->first();
                if (!empty($driverVehicleDetails)){
                    $drivers = $driverVehicleDetails->Drivers;
                    $vehicleActiveStatus = array();
                    foreach($drivers as $driverData){
                        $vehicleActiveStatus[] = $driverData->online_offline == 1 ? 1 : 2;
                    }
                    if(!in_array(1,$vehicleActiveStatus)){
                        $driverVehicleDetails->vehicle_active_status = 2;
                        $driverVehicleDetails->save();
                    }
                }
            }

//            $playerids = array($driver->player_id);
            $data = [];
            $message = trans('admin.message298');
            Onesignal::DriverPushMessage($driver->id, $data, $message,6, $merchant_id);
            $action = 'success';
            $msg = trans('admin.driver_logout');
        }else{
            $action = 'error';
            $msg = trans('admin.driver_on_ride');
        }
        return redirect()->back()->with($action,$msg);
    }

    public function PersonalDocExpire()
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $currentDate = date("Y-m-d");
        $doc = DriverDocument::whereHas('Driver', function ($query) use ($merchant_id) {
            $query->where([['merchant_id', '=', $merchant_id]]);
        })->whereDate('expire_date', '<', $currentDate)->get();
        if (empty($doc->toArray())) {
            return redirect()->back();
        }
        $docment_ids = array_pluck($doc, 'id');
        $driver_ids = array_pluck($doc, 'driver_id');
        DriverDocument::whereIn('id', $docment_ids)->update(['document_verification_status' => 4]);
//        $playerids = Driver::whereIn('id', $driver_ids)->where([['player_id', '!=', null]])->get(['player_id']);
//        $playerids = array_pluck($playerids, 'player_id');
        $message = trans('admin.message359');
        $data = ['title' => $message];
        Onesignal::DriverPushMessage($driver_ids, $data, $message, 7, $merchant_id);
        return redirect()->back();
    }

    public function Serach(Request $request)
    {
        switch ($request->parameter) {
            case "1":
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "phoneNumber";
                break;
            case "4":
                $parameter = "vehicle_number";
                break;
        }
        $query = $this->getAllDriver(false);
        if($request->parameter == "4"){
            $vechicle_number = $request->keyword;
            $query->whereHas('DriverVehicles', function ($q) use ($vechicle_number) {
                $q->where([['vehicle_number', '=', $vechicle_number]]);
            })->with(['DriverVehicles' => function ($qq) use ($vechicle_number) {
                $qq->where([['vehicle_number', '=', $vechicle_number]]);
            }]);
        }else if($request->keyword) {
            $query->where($parameter, 'like', '%' . $request->keyword . '%');
        }
        if ($request->area_id) {
            $query->where('country_area_id', '=', $request->area_id);
        }
        if(isset($request->commission_type) && ($request->commission_type == 1 || $request->commission_type == 2)){
            $query->where('subscription_wise_commission', '=', $request->commission_type);
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $drivers = $query->paginate(25);
        $pendingdrivers = $this->getAllPendingDriver(false);
        $pendingdrivers = count($pendingdrivers->get());
        $pendingdriversVehicle = $this->getAllPendingVehicles(false);
        $pendingdriversVehicle = count($pendingdriversVehicle->get());
        $basicDriver = $this->getAllBasicDriver(false)->count();
        $config = ApplicationConfiguration::select('gender','driver_commission_choice')->where([['merchant_id', '=', $merchant_id]])->first();
        $configNew = Configuration::select('driver_wallet_status', 'subscription_package')->where([['merchant_id', '=', $merchant_id]])->first();
        $config->driver_wallet_status = $configNew->driver_wallet_status;
        $config->subscription_package = $configNew->subscription_package;

        $rejecteddrivers = count($this->getAllRejectedDrivers(false)->get());
        $areas = $this->getAreaList(false)->get();
        $tempDocUploaded = $this->getAllTempDocUploaded(false)->count();
        $data = $request->all();
        $data['merchant_id'] = $merchant_id;
        return view('merchant.driver.index', compact('drivers', 'rejecteddrivers', 'pendingdrivers', 'basicDriver', 'config', 'areas','tempDocUploaded','data','pendingdriversVehicle'));
    }

    public function PendingSerach(Request $request)
    {
        $request->validate([
            'keyword' => "required",
            'parameter' => "required|integer|between:1,4",
        ]);
        switch ($request->parameter) {
            case "1":
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "phoneNumber";
                break;
            case "4":
                $parameter = "vehicle_number";
                break;
        }
        $query = $this->getAllPendingDriver(false);
        if($request->parameter == "4"){
            $vechicle_number = $request->keyword;
            $query->whereHas('DriverVehicles', function ($q) use ($vechicle_number) {
                $q->where([['vehicle_number', '=', $vechicle_number]]);
            })->with(['DriverVehicles' => function ($qq) use ($vechicle_number) {
                $qq->where([['vehicle_number', '=', $vechicle_number]]);
            }]);
        }else if ($request->keyword) {
            $query->where($parameter, 'like', '%' . $request->keyword . '%');
        }
        $drivers = $query->paginate(20);
        return view('merchant.driver.pending', compact('drivers'));
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $request_from = isset($request->request_from) ? $request->request_from : NULL;
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $delete = Driver::where([['merchant_id', '=', $merchant_id]])->FindorFail($id);
        $bookings = Booking::where([['driver_id', '=', $id], ['booking_status', '=', 1012]])->get();
        if ($delete->free_busy != 1 && empty($bookings->toArray())):
            if($request_from == 'rejected')
            {
                $delete->delete();
            }
            else{
                $delete->driver_delete = 1;
                $delete->online_offline = 2;
                $delete->login_logout = 2;
                $delete->save();
                DriverVehicle::where([['owner_id','=',$delete->id],['driver_id','=',$delete->id]])->update(['vehicle_delete' => 1]);
            }
//            $playerids = array($delete->player_id);
            $data = ['booking_status' => '999'];
            $message = trans('admin.message728');
            Onesignal::DriverPushMessage($delete->id, $data, $message, 6, $merchant_id);
            echo trans('admin.message697');
        else:
            echo trans('admin.message698');
            return redirect()->route('driver.index')->with('ondriverdeletefail', 'Driver is on ride.');
        endif;
    }

    public function DeletePendingVehicle(Request $request, $id)
    {
        $vehicle_rides = Booking::where([['driver_vehicle_id', '=', $id]])->count();
        if ($vehicle_rides > 0) {
            $vehicle = DriverVehicle::find($id);
            $vehicle->vehicle_delete = 1;
            $vehicle->save();
            // return redirect()->back()->with('vehcile', trans('Vehicle Deleted Successfully'));
            echo trans('Vehicle Deleted Successfully');
        } else {
            $vehicle_docs = DriverVehicleDocument::where([['driver_vehicle_id', '=', $id]])->get();
            foreach ($vehicle_docs as $vehicle_doc) {
                $image_path = $vehicle_doc->document;
                if (File::exists($image_path)) {
                    File::delete($image_path);
                }
                $vehicle_doc->delete();
            }
            DriverVehicle::where([['id', '=', $id]])->delete();
            // return redirect()->back()->with('vehcile', trans('Vehicle Deleted Successfully'));
            echo trans('Vehicle Deleted Successfully');
        }
    }

    public function RejectedDriver()
    {
        $drivers = $this->getAllRejectedDrivers();
        return view('merchant.driver.rejected', compact('drivers'));
    }

    public function RejectedSearch(Request $request)
    {
        switch ($request->parameter) {
            case "1":
                $parameter = "first_name";
                break;
            case "2":
                $parameter = "email";
                break;
            case "3":
                $parameter = "phoneNumber";
                break;
            case "4":
                $parameter = "vehicle_number";
                break;
        }
        $query = $this->getAllRejectedDrivers(false);
        if($request->parameter == "4"){
            $vechicle_number = $request->keyword;
            $query->whereHas('DriverVehicles', function ($q) use ($vechicle_number) {
                $q->where([['vehicle_number', '=', $vechicle_number]]);
            })->with(['DriverVehicles' => function ($qq) use ($vechicle_number) {
                $qq->where([['vehicle_number', '=', $vechicle_number]]);
            }]);
        }else if($request->keyword) {
            $query->where($parameter, 'like', '%' . $request->keyword . '%');
        }
        $drivers = $query->paginate(25);
        return view('merchant.driver.rejected', compact('drivers'));
    }

    public function DisapproveDriver($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $driver = Driver::find($id);
        $driver->signupStep = 4;
        $driver->save();
//        $playerids = array($driver->player_id);
        $data = [];
        $message = trans('admin.admin_disapprove_message');
        Onesignal::DriverPushMessage($driver->id, $data, $message, 6, $merchant_id);
        return redirect()->back();
    }

    public function ApproveDriver($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $driver = Driver::find($id);
        $driver->signupStep = 3;
        $driver->save();
        $playerids = array($driver->player_id);
        $data = [];
        $vehicle = DriverVehicle::where([['owner_id', '=', $id]])->first();
        if (!empty($vehicle)) {
            $vehicle->vehicle_verification_status = 1;
            $vehicle->vehicle_active_status = 1;
            $vehicle->save();
            $vehicle->Drivers()->updateExistingPivot($driver->id, ['vehicle_active_status' => 1]);
        }
        $message = trans('admin.admin_approve_message');
        Onesignal::DriverPushMessage($driver->id, $data, $message, 6, $merchant_id);
        return redirect()->back();
    }

    public function delete(Request $request)
    {
        $delete = Driver::find($request->id);
        $delete->delete();
        echo trans('admin.message697');
    }

    public function BlockDrivers(Request $request)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $drivers = Driver::where([['merchant_id', '=', $merchant_id], ['driver_block_status', '=', 1]])->paginate(10);
        return view('merchant.driver.block_drivers', compact('drivers'));
    }

    public function driver_unblock(Request $request)
    {
        $driver = Driver::find($request->id);
        $driver->driver_block_status = 0;
        $driver->save();
        echo trans('admin.unblocked');
    }

    public function ExpireDocuments(Request $request)
    {
        return view('merchant.driver.expire_document');
    }

    public function Cronjob_DriverBlock(Request $request)
    {
        $driver = DriverAccount::with('Driver')->get();
        $driver = $driver->toArray();
        $driver_det = array();
        $id = array();
        $now_date = date('Y-m-d');
        foreach ($driver as $value) {
            $block_date = explode(' ', $value['block_date']);
            $block_date_new = $block_date[0];
            if ($now_date == $block_date_new) {
                $merchant_id = $value['merchant_id'];
                $driver_det = $value['driver_id'];
                $driver_id = $value['driver_id'];
                $details = Driver::find($driver_id);
                $details->driver_block_status = 1;
                $details->save();
                $id[] = $value['driver']['id'];
            }
        }
        if (!empty($driver_det)) {
            $data = array();
            $message = "Driver Blocked";
            $type = 190;
            Onesignal::DriverPushMessage($id, $data, $message, $type, $merchant_id);
            return redirect()->route('merchant.driver.block')->with('moneyAdded', 'Driver Block.');
        } else {
            return redirect()->route('merchant.driver.block')->with('moneyAdded', 'Driver not block.');
        }
    }

    public function editDriverVehicleDocument($id, $vehicle_id) {
        $merchant_id = get_merchant_id();
        $driver = Driver::with('DriverVehicle.DriverVehicleDocument')->where([['merchant_id', '=', $merchant_id], ['driver_delete', '=', NULL]])->findOrFail($id);
        return view('merchant.driver.edit-vehicle-doc', compact('driver', 'vehicle_id'));
    }

    public function storeDriverVehicleDocument(Request $request, $id, $vehicle_id)
    {
        $expiredate = $request->expiredate;
        $images = $request->file('document');
        $all_doc = $request->input('all_doc');
        foreach ($all_doc as  $document_id) {
            $image = isset($images[$document_id]) ? $images[$document_id] : null;
            $expiry_date = isset($expiredate[$document_id]) && !empty($expiredate[$document_id]) ? $expiredate[$document_id] : NULL;
            $driver_v_document =  DriverVehicleDocument::where([['driver_vehicle_id',$vehicle_id],['document_id',$document_id]])->first();
            if(empty($driver_v_document->id))
            {
                $driver_v_document =  new DriverVehicleDocument;
            }
            $driver_v_document->driver_vehicle_id = $vehicle_id;
            $driver_v_document->document_id = $document_id;
            $driver_v_document->expire_date = $expiry_date;
            $driver_v_document->document_verification_status = 2;
            if(!empty($image))
            {
                $driver_v_document->document = $this->uploadImage($image,'vehicle_document',NULL,'multiple');
            }
            $driver_v_document->save();
        }
        return redirect()->route('driver.show',$id)->withSuccess(trans('admin.editDocSucess'));
    }
    public function TempDocumentVerify($id){
        $driver = Driver::find($id);
        if (count($driver) > 0){
            $driverDocs = $driver->DriverDocument;
            foreach($driverDocs as $driverDoc){
                if($driverDoc->temp_document_file != null){
                    $driverDoc->temp_doc_verification_status = 2;
                    $driverDoc->save();
                }
            }
            $driverVehicleDocs = $driver->DriverVehicle[0]->DriverVehicleDocument;
            foreach($driverVehicleDocs as $driverVehicleDoc){
                if($driverVehicleDoc->temp_document_file != null){
                    $driverVehicleDoc->temp_doc_verification_status = 2;
                    $driverVehicleDoc->save();
                }
            }
//            $playerId[] = $driver->player_id;
            $merchant_id = $driver->merchant_id;
            $msg = trans('admin.temp_doc_approved');
            Onesignal::DriverPushMessage($driver->id, [], $msg, 13, $merchant_id, 1);
        }
        return redirect()->back()->with('success',trans('admin.doc_approved'));
    }

    public function referralEarning($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $driver = Driver::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $referralEarning = ReferralDriverDiscount::where(['merchant_id' => $merchant_id, 'driver_id' => $driver->id],['expire_status','!=',1],['payment_status', '!=', 1])->paginate(25);
        return view('merchant.driver.referral-earning', compact('referralEarning', 'driver'));
    }

    public function DriverRefer($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $driver = Driver::find($id);
        $referral_details = ReferralDiscount::where([['sender_id', '=', $id],['sender_type','=',2],['merchant_id','=',$merchant_id]])->latest()->paginate(10);
        foreach($referral_details as $refer){
            $receiverDetails = $refer->receiver_type == 1 ? User::find($refer->receiver_id) : Driver::find($refer->receiver_id);
            $phone = $refer->receiver_type == 1 ? $receiverDetails->UserPhone : $receiverDetails->phoneNumber;
            $receiverType = $refer->receiver_type == 1 ? 'User' : 'Driver';
            $refer->receiver_details = array(
                'id' => $receiverDetails->id,
                'name' => $receiverDetails->first_name.' '.$receiverDetails->last_name,
                'phone' => $phone,
                'email' => $receiverDetails->email );
            $refer->receiverType = $receiverType;
        }
        return view('merchant.driver.driver_refer', compact('referral_details', 'driver'));
    }

    public function MoveToPending(Request $request){
        $driver = Driver::find($request->driver_id);
        if (!empty($driver)){
            $driver->signupStep = 2;
            $driver->reject_driver = 1;
            $driver->save();

            $driverDocuments = $driver->DriverDocument;
            if (count($driverDocuments) > 0){
                foreach($driverDocuments as $document){
                    $document->document_verification_status = 1;
                    $document->save();
                }
            }
            $driverVehicles = DriverVehicle::with(['Drivers' => function($query) use($driver){
                $query->where('driver_id',$driver->id);
            }])->whereHas('Drivers',function($p) use($driver){
                $p->where('driver_id',$driver->id);
            })->latest()->get();

            if (count($driverVehicles) > 0){
                foreach ($driverVehicles as $driverVehicle){
                    $driverVehicle->vehicle_active_status = 2;
                    $driverVehicle->vehicle_verification_status = 2;
                    $driverVehicle->save();

                    $driverVehicle->Drivers()->updateExistingPivot($driver->id, ['vehicle_active_status' => 2]);

                    $vehicleDocuments = $driverVehicle->DriverVehicleDocument;
                    if (count($vehicleDocuments) > 0){
                        foreach ($vehicleDocuments as $vehicleDocument){
                            $vehicleDocument->document_verification_status = 1;
                            $vehicleDocument->save();
                        }
                    }
                }
            }
            return redirect()->back()->with('success',trans('admin.move_to_pending'));
        }else{
            return redirect()->back()->with('error',trans('admin.no_driver'));
        }
    }
    // assign
    public function AssignFreeSubscription(Request $request, $id = null)
    {
        request()->session()->flash('message', trans('admin.subspack_added'));
        // driver subscription code for free package assignment
        $existing_package = DriverSubscriptionRecord::
        where('package_type',1)
            ->where(function ($q){
                $q->where([['end_date_time','>=',date('Y-m-d H:i:s')],['status',1]]);
                $q->orWhere([['end_date_time',NULL],['start_date_time',NULL]]);
            })
            ->orderBy('id', 'DESC')
            ->first();
//             p($existing_package);
        if((!empty($existing_package->id) && ($request->package != $existing_package->subscription_pack_id) || $request->package == null))
        {
            $existing_package->status = 0; // make previous package disable
            $existing_package->save();
        }
        $merchant_id = get_merchant_id();
        if(!empty($request->package)) {
            $driver_pack = new DriverSubscriptionRecord;
            $pack = SubscriptionPackage::Find($request->package);
            $driver_pack->subscription_pack_id = $request->package;
            $driver_pack->driver_id = $id;
            $driver_pack->payment_method_id = null;
            $driver_pack->package_duration_id = $pack->package_duration_id;
            $driver_pack->price = $pack->price;
            $driver_pack->package_total_trips = $pack->max_trip;
            $driver_pack->package_type = $pack->package_type;
            $driver_pack->used_trips = 0;
            $driver_pack->start_date_time = NULL; //when driver make is active
            $driver_pack->end_date_time = NULL; //when driver make is active
            $driver_pack->status = 1;  // assigned
            $driver_pack->save();

            $msg = trans('api.free_subscription_notify_driver');
            $type = 17;
            Onesignal::DriverPushMessage($id, [], $msg, $type, $merchant_id, 1);
        }
        return redirect()->route('driver.activated_subscription', $id);
    }

    public function RejectedVehicle()
    {
//        if (!Auth::user('merchant')->can('view_rejected_vehicle')) {
//            abort(404, 'Unauthorized action.');
//        }
        $merchant_id = get_merchant_id();
        $query = DriverVehicle::with('Driver')->where([['merchant_id', '=', $merchant_id], ['vehicle_verification_status',3]])->orderBy('id','DESC');
        $vehicles = $query->paginate(20);
//        p($vehicles);
        return view('merchant.drivervehicles.vehicle-rejected', compact('vehicles'));
    }

    public function FindDriverLocationNotUpdate(){
        $merchant_id = get_merchant_id();
        $countryAreas = CountryArea::where([['merchant_id',$merchant_id],['status',1]])->get();
        $drivers = array();
        return view('merchant.driver.find_loc_not_update',compact('countryAreas','drivers'));
    }

    public function SearchDriverLocationNotUpdate(Request $request){
        $validator = Validator::make($request->all(),[
            'country_area' => 'required',
            'from_date' => 'required',
            'to_date' => 'required',
            'time' => 'required'
        ]);

        if ($validator->fails()){
            $msg = $validator->messages()->all();
            return redirect()->back()->with('error',$msg[0]);
        }

        $data = $request->all();
        $merchant_id = get_merchant_id();
        $countryArea = CountryArea::where([['merchant_id',$merchant_id],['id',$request->country_area]])->first();
        $timeZone = $countryArea->timezone ? $countryArea->timezone : "Asia/Kolkata";
        date_default_timezone_set($timeZone);
        $startDate = $request->to_date.' '.date('H:i:s');
        $endDate = $request->from_date.' '.$request->time.':00';
        $drivers = Driver::where([['last_location_update_time','>=',$endDate],['last_location_update_time','<=',$startDate]])->orderBy('last_location_update_time','desc')->paginate(15);
        $countryAreas = CountryArea::where([['merchant_id',$merchant_id],['status',1]])->get();
        return view('merchant.driver.find_loc_not_update',compact('drivers','countryAreas','data'));
    }

    public function DetailList()
    {
        if(Auth::user()->demo == 1)
        {
            $otp = 4194568863;//getRandomCode(10);
            Session::put('demo_otp',$otp);
            $drivers = [];
            return view('merchant.driver.driver-list', compact('drivers'));
        }
        return redirect()->route('merchant.dashboard')->with('error',trans("common.permission_denied"));
    }
    public function verfiyOtp(Request $request)
    {
        if(Auth::user()->demo == 1)
        {
            $session_otp = Session::get('demo_otp');
            $session_post = $request->otp;
            if ($session_otp == $session_post) {
                $merchant_id = Auth::user()->id;
                $drivers = Driver::where([['merchant_id', '=', $merchant_id],['taxi_company_id', '=', NULL], ['signupStep', '=', 3], ['driver_delete', '=', NULL]])->orderBy('created_at','DESC')->get();
                return view('merchant.driver.driver-list', compact('drivers'));
            }
        }
        return redirect()->route('merchant.dashboard')->with('error',trans("common.permission_denied"));

    }

//    public function send_custom_mail()
//    {
//        $otp = 1234;
//        $reciever_email = \Config::get('custom.static_email');
//        $mail = new PHPMailer(true);
//        $mail->SMTPDebug = 0;
//        $mail->Host = 'smtp.gmail.com';
//        $mail->SMTPAuth = true;
//        $mail->Username = 'demo6619@gmail.com';
//        $mail->Password = 'apporio95605';
//        $mail->SMTPSecure = 'ssl';
//        $mail->Port =465;
//        //Recipients
//        $mail->setFrom('demo6619@gmail.com');
//        $mail->addAddress($reciever_email);
//        //Content
//        $mail->Subject = 'Driver/User list Otp';
//        $mail->Body = $otp;
//        $mail->AltBody = 'Alt Body';
//        $mail->send();
//    }

    public function getDriverCommissionChoices($config)
    {
        $arr_choice = [];
        $config->subscription_module = ($config->Configuration->subscription_package == 1 && $config->ApplicationConfiguration->driver_commission_choice == 1) ? true : false;
        if($config->subscription_module == true)
        {
            $merchant_helper = new MerchantHelper;
            $arr_choice_data = $merchant_helper->DriverCommissionChoices($config); // send merchant
            foreach ($arr_choice_data as $choice)
            {
                $arr_choice[$choice['id']] = $choice['lang_data'];
            }
            $arr_choice = add_blank_option($arr_choice,trans("common.select"));
        }
        return $arr_choice;
    }

}