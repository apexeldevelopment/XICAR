<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\BusSeatDetail;
use App\Models\BusService;
use App\Models\BusPolicy;
use App\Models\BusTraveller;
use App\Models\Configuration;
use Illuminate\Http\Request;
use App\Models\VehicleMake;
use App\Models\VehicleType;
use Auth;
use App\Models\BusDriver;
use App\Models\Bus;
use App\Traits\BusDriverTrait;
use App\Traits\BusTrait;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;
use App\Traits\AreaTrait;
use App\Models\InfoSetting;
use App\Models\DriverConfiguration;
use App\Models\ApplicationConfiguration;
use App\Models\CountryArea;
use App\Models\Segment;
use App\Models\BusDocument;
use App\Models\Document;
use Illuminate\Support\Facades\Config;
use View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;


class BusController extends Controller
{
    use BusDriverTrait, MerchantTrait, AreaTrait, ImageTrait, BusTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'USER')->first();
        view()->share('info_setting', $info_setting);
    }

    /**
     * All Vehicles / Buses
     */
    public function index(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $vehicle_types = VehicleType::where([['merchant_id', '=', $merchant_id], ['admin_delete', '=', NULL]])->get();
        $request->merge(['verification_status' => 'verified']);
        $buses = $this->getBuses(true, $request);
        $areas = $this->getMerchantCountryArea($this->getAreaList(false)->get());
        $arr_search = $request->all();

        $info_setting = InfoSetting::where('slug', 'DRIVER_VEHICLE')->first();
        $vehicle_model_expire = $merchant->Configuration->vehicle_model_expire;
        $bus_types = Config::get("custom.bus_type");
        $bus_design_types = Config::get("custom.bus_design_types");
        return view('merchant.bus-booking.bus.index', compact('buses', 'areas', 'vehicle_types', 'arr_search', 'info_setting', 'vehicle_model_expire', 'bus_types', 'bus_design_types'));
    }

    public function getBuses($pagination = true, $request = NULL)
    {
        $merchant_id = get_merchant_id();
        $buses = Bus::where('merchant_id', '=', $merchant_id)->where('vehicle_delete', '=', NULL)->latest()->paginate(20);
        return $buses;
    }


    public function VehicleType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_area_id' => 'required',
            'segment_id' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $errors;
            exit();
        }
        $arr_vehicle = $this->getVehicleType($request);
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        echo "<option value=''>" . trans("$string_file.select") . "</option>";
        foreach ($arr_vehicle as $vehicle) {
            echo "<option value='" . $vehicle->id . "'>" . $vehicle->VehicleTypeName . "</option>";
        }
    }

    public function getVehicleType(Request $request)
    {

        $data = CountryArea::with(['VehicleType' => function ($query) use ($request) {
            $query->where('admin_delete', NULL);
            $query->where('segment_id', $request->segment_id);
        }])
            ->find($request->country_area_id);
        return $data->VehicleType->unique();
    }

    // add vehicle
    public function add(Request $request, $bus_id = NULL, $calling_from = "")
    {
        $merchant = get_merchant_id(false);
        $config = $merchant->Configuration;
        $booking_config = $merchant->BookingConfiguration;
        $merchant_id = $merchant->id;
        $vehicle_model_expire = $config->vehicle_model_expire;

        //segment id for bus booking
        $segment = new Segment;
        $segment_id = $segment->BusSegment()->id;

        $string_file = $this->getStringFile(NULL, $merchant);
        $areas = $this->getMerchantCountryArea($this->getAreaList(false, true)->get(), null, 1);
        $arr_areas = add_blank_option($areas, trans("$string_file.service_area"));

        $vehiclemakes = VehicleMake::where([['merchant_id', '=', $merchant_id], ['admin_delete', '=', NULL]])->get();
        $driver_config = $merchant->DriverConfiguration;
        $appConfig = $merchant->ApplicationConfiguration;

        // driver vehicle
        $bus = NULL;
        $selected_bus_services = [];
        $selected_traveller_id = NULL;
        $vehicletypes = [];
        $country_area_id = NULL;
        $vehicle_models = [];
        if (!empty($bus_id)) {
            $bus = Bus::Find($bus_id);
            $country_area_id = $bus->country_area_id; //$merchant->country_area_id;
            $request->merge(['country_area_id' => $country_area_id, 'segment_id' => $segment_id]);
            $vehicletypes = $this->getVehicleType($request);
            $selected_bus_services = $bus->BusService->pluck("id")->toArray();
            $selected_traveller_id = isset($bus->BusTraveller)? $bus->BusTraveller->id: NULL;
        }

        $vehicle_type = isset($bus->vehicle_type_id) ? $bus->vehicle_type_id : NULL;
        $vehicle_doc_segment = $this->busDocSegment($country_area_id, $vehicle_type, $bus_id, $merchant);
        $request_from = $calling_from == "d-list" ? "vehicle_list" : "driver_list";
        $baby_seat_enable = $booking_config->baby_seat_enable == 1 ? true : false;
        $wheel_chair_enable = $booking_config->wheel_chair_enable == 1 ? true : false;
        $vehicle_ac_enable = $config->vehicle_ac_enable == 1 ? true : false;
        $info_setting = InfoSetting::where('slug', 'DRIVER_VEHICLE')->first();
        $bus_types = Config::get("custom.bus_type");
        $bus_design_types = Config::get("custom.bus_design_types");
        $bus_services = BusService::where("merchant_id", $merchant_id)->orderBy("sequence")->get();
        $bus_travellers = BusTraveller::where("merchant_id", $merchant_id)->orderBy("id", "desc")->get();
        return view('merchant.bus-booking.bus.create_vehicle', compact('vehicletypes', 'vehiclemakes', 'vehicle_doc_segment', 'appConfig', 'driver_config', 'bus', 'request_from', 'baby_seat_enable', 'wheel_chair_enable', 'vehicle_ac_enable', 'info_setting', 'vehicle_model_expire', 'arr_areas', 'segment_id', 'bus_types', 'bus_design_types', 'bus_services', 'selected_bus_services', 'selected_traveller_id', 'bus_travellers'));
    }

    public function busDocSegment($country_area_id, $vehicle_type, $bus_id = NULL, $merchant = null)
    {

        if ($vehicle_type && $country_area_id) {
            $docs = CountryArea::with(['VehicleDocuments' => function ($q) use ($vehicle_type) {
                $q->addSelect('documents.id', 'expire_date', 'documentNeed', 'document_number_required');
                $q->where('documentStatus', 1);
                $q->where('vehicle_type_id', $vehicle_type);
            }])
                ->where('id', $country_area_id)
                ->first();

            $area = CountryArea::with(['VehicleType' => function ($q) use ($vehicle_type, $country_area_id) {
                $q->where('country_area_id', $country_area_id);
                $q->where('vehicle_type_id', $vehicle_type);
            }])
                ->where('id', $country_area_id)
                ->first();
            $arr_services = $area->VehicleType->map(function ($item) {
                return $item['pivot']->service_type_id;
            });
            $arr_services = $arr_services->toArray();
            // p($arr_services);
            $data['docs'] = $docs;
            // driver vehicle
            $vehicle_details = NULL;
            if (!empty($bus_id)) {
                $vehicle_details = Bus::Find($bus_id);
            }
            $arr_segment_services = [];
            $data['arr_segment_services'] = $arr_segment_services;
            $data['vehicle_details'] = $vehicle_details;
            $vehicle_doc_segment = View::make('merchant.bus-booking.bus.vehicle-document-segment')->with($data)->render();
        } else {
            $vehicle_doc_segment = "";
        }
        return $vehicle_doc_segment;
    }

    public function busDocServiceSegment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_area_id' => 'required',
            'vehicle_type_id' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $errors;
            exit();
        }
        try {
            $merchant = get_merchant_id(false);
            echo $this->busDocSegment($request->country_area_id, $request->vehicle_type_id, NULL, $merchant);
        } catch (\Throwable $th) {
            echo $th->getMessage();
        }
    }

    public function uploadDocument($custom_document_key, $all_doc_id, $arr_doc_file, $doc_expire_date, $document_number, $segment_id = NULL, $bus_id = NULL)
    {
        $merchant_id = get_merchant_id();
        foreach ($all_doc_id as $document_id) {
            $image = isset($arr_doc_file[$document_id]) ? $arr_doc_file[$document_id] : null;
            $expiry_date = isset($doc_expire_date[$document_id]) ? $doc_expire_date[$document_id] : NULL;
            $doc_number = isset($document_number[$document_id]) ? $document_number[$document_id] : NULL;
            if ($custom_document_key == "vehicle_document") {
                //                p($custom_document_key);
                $driver_document = BusDocument::where([['bus_id', $bus_id], ['document_id', $document_id]])->first();
                if (empty($driver_document->id)) {
                    $driver_document = new BusDocument;
                }
                $unique_document = BusDocument::where([['bus_id', '!=', $bus_id]])->where(function ($q) use ($doc_number, $document_id) {
                    $q->where('document_number', '=', $doc_number)->Where('document_number', '!=', '');
                })->count();
            }

            $doc_info = Document::find($document_id);
            $string_file = $this->getStringFile($doc_info->Merchant);
            $doc_name = $doc_info->DocumentName;
            // if required document not uploaded
            if ($doc_info->documentNeed == 1 && empty($image) && empty($driver_document->id)) {
                throw new \Exception(trans("$string_file.upload_document") . ' ' . $doc_name);
            }
            // if expire date is mandatory but not inserted
            if ($doc_info->expire_date == 1 && empty($expiry_date)) {
                throw new \Exception(trans("$string_file.select_expire_date_of") . ' ' . $doc_name);
            }
            // if document number is mandatory but not entered or duplicate
            if ($doc_info->document_number_required == 1) {
                if (!empty($doc_number)) {
                    if ($unique_document > 0) {
                        throw new \Exception('Document Number already exist');
                    }
                } else {
                    throw new \Exception(trans("$string_file.please_enter_document_number") . $doc_name);
                }
                $driver_document->document_number = $document_number[$document_id];
            }

            $driver_document->document_id = $document_id;
            $driver_document->bus_id = $bus_id;
            $driver_document->expire_date = $expiry_date;
            $driver_document->document_verification_status = 2;

            if (!empty($image)) {
                $driver_document->document = $this->uploadImage($image, $custom_document_key, NULL, 'multiple');
            }
            $driver_document->save();
        }
        return true;
    }

    // save vehicle
    public function save(Request $request, $vehicle_id = NULL)
    {
        $merchant_id = get_merchant_id();
        $vehicle_id = $request->input('vehicle_id');
        $request_fields = [
            'country_area_id' => 'required_without:vehicle_id',
            'vehicle_type_id' => 'required_without:vehicle_id',
            'vehicle_make_id' => 'required_without:vehicle_id',
            'vehicle_model_id' => 'required_without:vehicle_id',
            'vehicle_register_date' => 'required_if:vehicle_model_expire,==,1',
            'bus_name' => 'required',
            'vehicle_expire_date' => 'required_if:vehicle_model_expire,==,1',
            'vehicle_number' => [
                'required',
                Rule::unique('buses', 'vehicle_number')->where(function ($query) use ($merchant_id, $vehicle_id) {
                    return $query->where([['merchant_id', '=', $merchant_id], ['id', '!=', $vehicle_id], ['vehicle_delete', '=', NULL]]);
                })
            ],
            'vehicle_color' => 'required',
            'car_number_plate_image' => 'required_without:vehicle_id',
            'car_image' => 'required_without:vehicle_id',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        $string_file = $this->getStringFile($merchant_id);
        if ($request->vehicle_model_expire == 1) {
            if ($request->vehicle_expire_date < $request->vehicle_register_date) {
                return redirect()->back()->withErrors(trans("$string_file.model_expire_date_error"));
            }
            $model_age = date_diff(date_create($request->vehicle_expire_date), date_create($request->vehicle_register_date));
            if ($model_age->y == 0) {

                return redirect()->back()->withErrors(trans("$string_file.model_expire_date_diff"));
            }
        }
        DB::beginTransaction();
        try {
            $arr_data2 = [];
            $arr_data1 = [
                'bus_name' => $request->bus_name,
                // 'traveller_name' => isset($request->traveller_name) ? $request->traveller_name : "",
                'vehicle_number' => $request->vehicle_number,
                'vehicle_color' => $request->vehicle_color,
                'baby_seat' => $request->baby_seat,
                'wheel_chair' => $request->wheel_chair,
                'ac_nonac' => $request->ac_nonac,
                'vehicle_register_date' => isset($request->vehicle_register_date) ? $request->vehicle_register_date : NULL,
                'vehicle_expire_date' => isset($request->vehicle_expire_date) ? $request->vehicle_expire_date : NULL,
            ];
            if (empty($vehicle_id)) {
                $vehicleMake_id = $this->vehicleMake($request->vehicle_make_id, $merchant_id);
                $vehicle_seat = $request->vehicle_seat ? $request->vehicle_seat : 3;
                $vehicleModel_id = $this->vehicleModel($request->vehicle_model_id, $merchant_id, $vehicleMake_id, $request->vehicle_type, $vehicle_seat);
                $arr_data2 = [
                    'country_area_id' => $request->country_area_id,
                    'merchant_id' => $merchant_id,
                    'vehicle_type_id' => $request->vehicle_type_id,
                    'shareCode' => getRandomCode(10),
                    'vehicle_make_id' => $vehicleMake_id,
                    'vehicle_model_id' => $vehicleModel_id,
                    //                    'vehicle_active_status' => 1, // be default vehicle will be active
                    'vehicle_verification_status' => 2, // means verified
                ];
            }
            $arr_data = array_merge($arr_data1, $arr_data2);
            if (!empty($request->file('car_image'))) {
                $arr_data['vehicle_image'] = $this->uploadImage('car_image', 'vehicle_document');
            }
            if (!empty($request->file('car_number_plate_image'))) {
                $arr_data['vehicle_number_plate_image'] = $this->uploadImage('car_number_plate_image', 'vehicle_document');
            }
            if (isset($request->type)) {
                $arr_data['type'] = $request->type;
            }
            if (isset($request->design_type)) {
                $arr_data['design_type'] = $request->design_type;
            }
            if (isset($request->bus_traveller_id)) {
                $arr_data['bus_traveller_id'] = $request->bus_traveller_id;
            }
            $arr_data['additional_info'] = $request->additional_info;

            $vehicle = Bus::updateOrCreate(['id' => $vehicle_id], $arr_data);
            $vehicle->BusService()->sync($request->bus_services);

            $all_doc = $request->input('all_doc');
            if (!empty($all_doc)) {
                $images = $request->file('document');
                $expiredate = $request->expiredate;
                $document_number = $request->document_number;
                $custom_key = "vehicle_document";
                // upload document
                $this->uploadDocument($custom_key, $all_doc, $images, $expiredate, $document_number, NULL, $vehicle->id);
            }
        } catch (\Exception $e) {
            DB::rollback();
            p($e->getMessage());
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->route('merchant.bus_booking.bus.index')->withSuccess(trans("$string_file.saved_successfully"));
    }

    // Show Bus
    public function show($id)
    {
        $bus = Bus::findOrFail($id);
        $config = Configuration::where([["merchant_id", '=', $bus->merchant_id]])->select("vehicle_model_expire")->first();
        $vehicle_model_expire = $config->vehicle_model_expire;
        return view('merchant.bus-booking.bus.show', compact('bus', 'vehicle_model_expire'));
    }

    // Bus Seat Config
    public function seatConfig($id)
    {
        $bus = Bus::with("BusSeatDetail")->findOrFail($id);
        $bus_types = Config::get("custom.bus_type");
        $bus_design_types = Config::get("custom.bus_design_types");
        return view('merchant.bus-booking.bus.seat_config', compact('bus', 'bus_types', 'bus_design_types'));
    }

    // Save Bus Seat Config
    public function seatConfigSave(Request $request, $id)
    {
        $request_fields = [
            'bus_id' => 'required',
            'lower_seats' => 'required',
            'upper_seats' => 'required_if:type,LOWER_UPPER'
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            return redirect()->back()->withInput($request->input())->withErrors($validator->messages()->all());
        }
        DB::beginTransaction();
        try {
            $merchant_id = get_merchant_id();
            $string_file = $this->getStringFile($merchant_id);

            $bus = Bus::where("merchant_id", $merchant_id)->findOrFail($id);
            $arr = [];
            for ($i = 1; $i <= $request->lower_seats; $i++) {
                array_push($arr, array("bus_id" => $bus->id, "type" => "LOWER", "seat_no" => $i, "sequence" => $i));
            }
            if($bus->type == "LOWER_UPPER"){
                for ($i = $request->lower_seats+1; $i <= $request->lower_seats+$request->upper_seats; $i++) {
                    array_push($arr, array("bus_id" => $bus->id, "type" => "UPPER", "seat_no" => "U".$i, "sequence" => $i));
                }
            }
            BusSeatDetail::insert($arr);
            DB::commit();
            return redirect()->route('merchant.bus_booking.bus.index')->withSuccess(trans("$string_file.saved_successfully"));
        } catch (\Exception $exception) {
            DB::rollback();
            return redirect()->back()->withErrors($exception->getMessage());
        }
    }
    
    public function busPolicySave(Request $request)
    {
        $request_fields = [
            'bus_id' => 'required|int',
            'policy_name' => 'required',
            'description' => 'required'
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            return redirect()->back()->withInput($request->input())->withErrors($validator->messages()->all());
        }
        try{
                $merchant_id = get_merchant_id(false);
                $string_file = $this->getStringFile($merchant_id);
                
                    $insert_arr = [
                        
                        "bus_id"     =>$request->bus_id,
                        "policy_name"=>$request->policy_name,
                        "description"=>$request->description
                        
                        ];
                        BusPolicy::insert($insert_arr);
                 return redirect()->route('merchant.bus_booking.bus.index')->withSuccess(trans("$string_file.saved_successfully"));
        } catch(\Exception $exception) {
            return redirect()->back()->withErrors($exception->getMessage());
            }
            

    }
//
//
//
//    /**
//     * Driver Vehicle
//     */
//    public function driverAllVehicle(Request $request)
//    {
//        $merchant = get_merchant_id(false);
//        $merchant_id = $merchant->id;
//        $vehicles = VehicleType::where([['merchant_id', '=', $merchant_id], ['admin_delete', '=', NULL]])->get();
//        $request->merge(['verification_status' => 'verified']);
//        $driver_vehicles = $this->getBuses(true, $request);
//        $areas = $this->getMerchantCountryArea($this->getAreaList(false)->get());
//        $arr_search = $request->all();
//        //        $arr_search['merchant_id'] = $merchant_id;
//        $info_setting = InfoSetting::where('slug', 'DRIVER_VEHICLE')->first();
//        $vehicle_model_expire = $merchant->Configuration->vehicle_model_expire;
//        return view('merchant.drivervehicles.all_vehicles', compact('driver_vehicles', 'areas', 'vehicles', 'arr_search', 'info_setting', 'vehicle_model_expire'));
//    }
//
//
//
//    /**
//     * Display a listing of the resource.
//     *
//     * @return \Illuminate\Http\Response
//     */
//    // public function index()
//    // {
//    //     return view('merchant.bus.index');
//    // }
//
//    /**
//     * Show the form for creating a new resource.
//     *
//     * @return \Illuminate\Http\Response
//     */
//    public function create()
//    {
//        return view('merchant.bus.create');
//    }
//
//    /**
//     * Store a newly created resource in storage.
//     *
//     * @param  \Illuminate\Http\Request $request
//     * @return \Illuminate\Http\Response
//     */
//    public function store(Request $request)
//    {
//        //
//    }
//
//    /**
//     * Display the specified resource.
//     *
//     * @param  int $id
//     * @return \Illuminate\Http\Response
//     */
//    public function show($id)
//    {
//        //
//    }
//
//    /**
//     * Show the form for editing the specified resource.
//     *
//     * @param  int $id
//     * @return \Illuminate\Http\Response
//     */
//    public function edit($id)
//    {
//        //
//    }
//
//    /**
//     * Update the specified resource in storage.
//     *
//     * @param  \Illuminate\Http\Request $request
//     * @param  int $id
//     * @return \Illuminate\Http\Response
//     */
//    public function update(Request $request, $id)
//    {
//        //
//    }
//
//    /**
//     * Remove the specified resource from storage.
//     *
//     * @param  int $id
//     * @return \Illuminate\Http\Response
//     */
//    public function destroy($id)
//    {
//        //
//    }
//
//    public function addBus()
//    {
//        return view('merchant.bus.addbus');
//    }
//
//    public function storeBus(Request $request)
//    {
//        //
//    }
//
//    public function addRoute()
//    {
//        return view('merchant.bus.addroute');
//    }
}
