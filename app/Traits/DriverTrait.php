<?php

namespace App\Traits;

use App\Http\Controllers\Api\DriverController;
use App\Models\Driver;
use App\Models\DriverVehicle;
use Auth;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Models\DriverDocument;
use App\Models\DriverSubscriptionRecord;
use App\Models\DriverVehicleDocument;
use App\Models\DriverSegmentDocument;
use App\Models\CountryArea;



trait DriverTrait
{
    // its getting list of merchant drivers
    public function getAllDriver($pagination = true, $request = NULL, $for_taxi_company = false, $for_driver_agency = false, $for_agent = false, $per_page = NULL)
    {
        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }
        $taxi_company_id = NULL;
        $driver_agency_id = NULL;
        $merchant_id = NULL;
        $agent_id = NULL;
        if ($for_taxi_company) {
            $taxi_company = get_taxicompany();
            $merchant_id = $taxi_company->merchant_id;
            $taxi_company_id = $taxi_company->id;
        } elseif ($for_driver_agency) {
            $driver_agency = get_driver_agency(false);
            $merchant_id = $driver_agency->merchant_id;
            $driver_agency_id = $driver_agency->id;
        } elseif ($for_agent) {
            $agent = get_agent(false);
            $merchant_id = $agent->merchant_id;
            $agent_id = $agent->id;
        } else {
            $merchant_id = get_merchant_id();
        }
        $query = Driver::where([['merchant_id', '=', $merchant_id], ['driver_delete', '=', NULL]])
            ->where(function ($q) use ($request, $permission_area_ids) {
                if (isset($request->request_from) && $request->request_from == "basic_signup") {
                    $q->where('signupStep', '<', 8);
                } elseif (isset($request->request_from) && $request->request_from == "pending_approval") {
                    $q->where('signupStep', '=', 8);
                    $q->where('reject_driver', '=', 1);
                    $q->where('is_approved', '=', 2);
                }elseif (isset($request->request_from) && $request->request_from == "pending_training") {
                    $q->where('signupStep', '=', 8);
                    $q->where('reject_driver', '=', 1);
                    $q->whereIn('in_training',  [1,3]);
                    $q->where('is_approved', '=', 1);
                } elseif (isset($request->request_from) && $request->request_from == "rejected_driver") {
                    $q->where('signupStep', '=', 8);
                    $q->where('reject_driver', '=', 2); // means rejected
                } elseif (isset($request->request_from) && $request->request_from == "rejected_driver_temporary") {
                    $q->whereHas('DriverDocument', function ($query) {
                        $query->where([['temp_doc_verification_status', '=', 3]]);
                    });
                    $q->orWhereHas('DriverVehicles', function ($query) {
                        $query->whereHas('DriverVehicleDocument', function ($inner_query) {
                            $inner_query->where([['temp_doc_verification_status', '=', 3]]);
                        });
                    });
                    $q->orWhereHas('DriverSegmentDocument', function ($query) {
                        $query->where([['temp_doc_verification_status', '=', 3]]);
                    });
                } else {
                    $q->where('signupStep', '=', 9);
                }
                if (!empty($permission_area_ids)) {
                    $q->whereIn("country_area_id", $permission_area_ids);
                }
                if (!empty($request->country_id)) {
                    $q->where('country_id', '=', $request->country_id);
                }
                if (isset($request->agent_id) && !empty($request->agent_id)) {
                    $q->where('agent_id', '=', $request->agent_id);
                }
                if(isset($request->segment_group_id) && !empty($request->segment_group_id)){
                    $q->where('segment_group_id','=',$request->segment_group_id);
                }
                if(isset($request->lt_gt) && !empty($request->lt_gt)){
                    if($request->lt_gt == 'lt'){
                        $q->where('wallet_money','<',0);
                    }elseif($request->lt_gt == 'gt'){
                        $q->where('wallet_money','>=',0);
                    }
                }
                if(isset($request->wallet_money_filter)){
                    $q->where(DB::raw('CAST(wallet_money AS FLOAT)'), '<', $request->wallet_money_filter);
                }
            })
            ->orderBy('created_at', 'DESC');
        if ($for_taxi_company) {
            $query->where("taxi_company_id", $taxi_company_id);
        } else {
            $query->where("taxi_company_id", NULL);
        }
        if ($for_driver_agency) {
            $query->where("driver_agency_id", $driver_agency_id);
        } elseif (isset($request->request_from) && $request->request_from == "merchant_driver_agency") {
            $query->where("driver_agency_id", '!=', NULL);
        } else {
            $query->where("driver_agency_id", NULL);
        }
        if($for_agent){
            $query->where("agent_id", $agent_id);
        }
        if (isset($request->driver_status) && $request->driver_status != "") {
            switch ($request->driver_status) {
                case "active":
                    $query->where([['driver_admin_status', '!=', 2]]);
                    break;
                case "busy":
                    $query->where([['online_offline', '=', 1], ['login_logout', '=', 1], ['free_busy', '=', 1]])->whereNull('driver_delete');
                    break;
                case "free":
                    $query->where([['online_offline', '=', 1], ['login_logout', '=', 1], ['free_busy', '=', 2]])->whereNull('driver_delete');
                    break;
                case "inactive":
                    $query->where([['driver_admin_status', '=', 2]]);
                    break;
                case "login":
                    $query->where([['login_logout', '=', 1]])->whereNull('driver_delete');
                    break;
                case "logout":
                    $query->where([['login_logout', '=', 2]])->whereNull('driver_delete');
                    break;
                case "offline":
                    $query->where([['login_logout', '=', 1], ['online_offline', '=', 2]])->whereNull('driver_delete');
                    break;
                case "online":
                    $query->where([['login_logout', '=', 1], ['online_offline', '=', 1]])->whereNull('driver_delete');
                    break;
            }
        }
        if (!empty($request->area_id) || !empty($request->parameter) || !empty($request->segment_id) || !empty($request->vehicle_type_id)) {
            switch ($request->parameter) {
                case "1":
                    //                    $parameter = "first_name";
                    $parameter = DB::raw('CONCAT_WS(" ", first_name, last_name)');
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
            if ($request->parameter == "4" || !empty($request->vehicle_type_id)) {
                $vechicle_number = $request->keyword;
                $vechicle_type_id = $request->vehicle_type_id;
                $query->whereHas('DriverVehicles', function ($q) use ($vechicle_number,$vechicle_type_id) {
                    // $q->where([['vehicle_number', '=', $vechicle_number]]);
                    if(!empty($vechicle_number)){
                        $q->where('vehicle_number', 'like', "%$vechicle_number%");
                    }
                    if(!empty($vechicle_type_id)){
                        $q->where([['vehicle_type_id', '=', $vechicle_type_id]]);
                    }
                })->with(['DriverVehicles' => function ($qq) use ($vechicle_number,$vechicle_type_id) {
                    // $qq->where([['vehicle_number', '=', $vechicle_number]]);
                    //   $qq->where('vehicle_number', 'like', "%$vechicle_number%");
                    // $qq->orWhere([['vehicle_type_id', '=', $vechicle_type_id]]);
                    if(!empty($vechicle_number)){
                        $qq->where('vehicle_number', 'like', "%$vechicle_number%");
                    }
                    if(!empty($vechicle_type_id)){
                        $qq->where([['vehicle_type_id', '=', $vechicle_type_id]]);
                    }
                }]);
            } else if ($request->keyword) {
                $query->where($parameter, 'like', '%' . $request->keyword . '%');
            }
            if ($request->area_id) {
                $query->where('country_area_id', '=', $request->area_id);
            }
            if (!empty($request->segment_id)) {
                $arr_segment_id = $request->segment_id;
                $query->whereHas('Segment', function ($q) use ($arr_segment_id) {
                    $q->whereIn('segment_id', $arr_segment_id);
                });
            }
        }
        if(empty($per_page)){
            $per_page = 20;
        }
        $drivers = $pagination == true ? $query->paginate($per_page) : $query->get();
        //        p($drivers);
        return $drivers;
    }


    public function getDriverWithStatus($pagination = true, $request = NULL, $per_page = NULL)
    {
        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }
        $merchant_id = get_merchant_id();

        $query = Driver::where([['merchant_id', '=', $merchant_id]])
            ->where(function ($q) use ($request, $permission_area_ids) {
                if(isset($request->segment_group_id) && !empty($request->segment_group_id)){
                    $q->where('segment_group_id','=',$request->segment_group_id);
                }
                if (!empty($permission_area_ids)) {
                    $q->whereIn("country_area_id", $permission_area_ids);
                }
            })
            ->orderBy('created_at', 'DESC');
        if (isset($request->driver_status) && $request->driver_status != "") {
            switch ($request->driver_status) {
                case "active":
                    $query->where([['driver_admin_status', '!=', 2]]);
                    break;
                case "busy":
                    $query->where([['online_offline', '=', 1], ['login_logout', '=', 1], ['free_busy', '=', 1]])->whereNull('driver_delete');
                    break;
                case "free":
                    $query->where([['online_offline', '=', 1], ['login_logout', '=', 1], ['free_busy', '=', 2]])->whereNull('driver_delete');
                    break;
                case "inactive":
                    $query->where([['driver_admin_status', '=', 2]]);
                    break;
                case "login":
                    $query->where([['login_logout', '=', 1]])->whereNull('driver_delete');
                    break;
                case "logout":
                    $query->where([['login_logout', '=', 2]])->whereNull('driver_delete');
                    break;
                case "offline":
                    $query->where([['login_logout', '=', 1], ['online_offline', '=', 2]])->whereNull('driver_delete');
                    break;
                case "online":
                    $query->where([['login_logout', '=', 1], ['online_offline', '=', 1]])->whereNull('driver_delete');
                    break;
            }
        }
        if (!empty($request->area_id) || !empty($request->parameter) || !empty($request->segment_id) || !empty($request->vehicle_type_id)) {
            switch ($request->parameter) {
                case "1":
                    //                    $parameter = "first_name";
                    $parameter = DB::raw('CONCAT_WS(" ", first_name, last_name)');
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
            if ($request->parameter == "4" || !empty($request->vehicle_type_id)) {
                $vechicle_number = $request->keyword;
                $vechicle_type_id = $request->vehicle_type_id;
                $query->whereHas('DriverVehicles', function ($q) use ($vechicle_number,$vechicle_type_id) {
                    // $q->where([['vehicle_number', '=', $vechicle_number]]);
                    $q->where('vehicle_number', 'like', "%$vechicle_number%");
                    $q->orWhere([['vehicle_type_id', '=', $vechicle_type_id]]);
                })->with(['DriverVehicles' => function ($qq) use ($vechicle_number,$vechicle_type_id) {
                    // $qq->where([['vehicle_number', '=', $vechicle_number]]);
                    $qq->where('vehicle_number', 'like', "%$vechicle_number%");
                    $qq->orWhere([['vehicle_type_id', '=', $vechicle_type_id]]);
                }]);
            } else if ($request->keyword) {
                $query->where($parameter, 'like', '%' . $request->keyword . '%');
            }
            if ($request->area_id) {
                $query->where('country_area_id', '=', $request->area_id);
            }
            if (!empty($request->segment_id)) {
                $arr_segment_id = $request->segment_id;
                $query->whereHas('Segment', function ($q) use ($arr_segment_id) {
                    $q->whereIn('segment_id', $arr_segment_id);
                });
            }
        }
        if(empty($per_page)){
            $per_page = 20;
        }
        $drivers = $pagination == true ? $query->paginate($per_page) : $query->get();
        return $drivers;
    }

    public function getAllVehicles($pagination = true, $request = NULL)
    {
        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }
        // status 2 means verified vehicles
        $verification_status = isset($request['verification_status']) && $request['verification_status'] == "pending" ? 1 : 2;
        $taxi_company_id = NULL;
        $agent_id = NULL;
        if (isset($request['for_taxi_company']) && $request['for_taxi_company'] == true) {
            $taxi_company = get_taxicompany();
            $merchant_id = $taxi_company->merchant_id;
            $taxi_company_id = $taxi_company->id;
        } elseif (isset($request['for_agent']) && $request['for_agent'] == true) {
            $agent = get_agent();
            $merchant_id = $agent->merchant_id;
            $agent_id = $agent->id;
        } else {
            $merchant_id = get_merchant_id();
        }
        $query = Driver::whereHas('DriverVehicles', function ($q) use ($verification_status) {
            $q->where('vehicle_verification_status', $verification_status);
            $q->where('vehicle_delete', '=', NULL)->latest();
        })->with(['DriverVehicles' => function ($qe) use ($verification_status) {
            $qe->where('vehicle_verification_status', $verification_status);
            $qe->where('vehicle_delete', '=', NULL);
        }])->where([['merchant_id', '=', $merchant_id], ['driver_delete', '=', NULL]]);
        if (!empty($request->keyword) || !empty($request->vehicletype) || !empty($request->area_id) || !empty($request->parameter) || !empty($request->vehicleNumber)) {
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
            if (!empty($request->keyword)) {
                $query->where($parameter, 'like', '%' . $request->keyword . '%');
            }
            if (!empty($request->area_id)) {
                $query->where('country_area_id', '=', $request->area_id);
            }
            if (!empty($request->vehicletype)) {
                $vehicletype = $request->vehicletype;
                $query->whereHas('DriverVehicles', function ($q) use ($vehicletype) {
                    $q->where([['vehicle_type_id', '=', $vehicletype]]);
                })->with(['DriverVehicles' => function ($qq) use ($vehicletype) {
                    $qq->where([['vehicle_type_id', '=', $vehicletype]]);
                }]);
            }
            if (!empty($request->vehicleNumber)) {
                $vehicleNumber = $request->vehicleNumber;
                $query->whereHas('DriverVehicles', function ($q) use ($vehicleNumber) {
                    $q->where('vehicle_number', 'like', '%' . $vehicleNumber . '%');
                })->with(['DriverVehicles' => function ($qq) use ($vehicleNumber) {
                    $qq->where('vehicle_number', 'like', '%' . $vehicleNumber . '%');
                }]);
            }
        }
        if (!empty($taxi_company_id)) {
            $query->where('taxi_company_id', '=', $taxi_company_id);
        } else {
            $query->where('taxi_company_id', '=', NULL);
        }
        if (!empty($agent_id)) {
            $query->where('agent_id', '=', $agent_id);
        }
        if (!empty($permission_area_ids)) {
            $query->whereIn("country_area_id", $permission_area_ids);
        }
        $vehicles = $pagination == true ? $query->latest()->paginate(10) : $query->get();
        return $vehicles;
    }

    public function getAllTempDocUploaded($pagination = true, $driver_id = null, $request = NULL)
    {
        $taxicompany = get_taxicompany();
        if (!empty($taxicompany)) {
            $taxicompany_id = $taxicompany->id;
            $merchant_id = $taxicompany->merchant_id;
        } else {
            $taxicompany_id = NULL;
            $merchant_id = get_merchant_id();
        }
        $currentDate = date('Y-m-d');
        $where = [['temp_document_file', '!=', null], ['temp_doc_verification_status', '=', 1], ['temp_expire_date', '>', $currentDate]];
        /*$driverVehicleDocumentWith = ['DriverVehicleDocument' => function ($o) use ($where) {
            $o->where($where);
        }];*/
        if ($driver_id == null) {
            $lastWhere = [['merchant_id', '=', $merchant_id], ['taxi_company_id', '=', $taxicompany_id]];
        } else {
            $lastWhere = [['merchant_id', '=', $merchant_id], ['id', '=', $driver_id], ['taxi_company_id', '=', $taxicompany_id]];
        }

        $searched_vehicle_number = "";
        if (!empty($request->parameter) && $request->parameter == "4") {
            $searched_vehicle_number = $request->keyword;
        }
        $query = Driver::
            /*with(['DriverDocument' => function ($query) use ($where) {
            $query->where($where);
        }, 'DriverVehicles' => function ($d_v) use ($where, $driverVehicleDocumentWith,$searched_vehicle_number) {
            if (!empty($searched_vehicle_number)) {
                $d_v->where([['vehicle_number', '=', $searched_vehicle_number]]);
            }
            $d_v->with($driverVehicleDocumentWith)->whereHas('DriverVehicleDocument', function ($p) use ($where) {
                $p->where($where);
            });
        }])->*/whereHas('DriverDocument', function ($q) use ($lastWhere, $where, $driver_id) {
                if (!empty($driver_id)) {
                    $q->where('driver_documents.driver_id', $driver_id);
                }
                $q->where($where);
                $q->whereHas('Driver', function ($inner) use ($lastWhere) {
                    $inner->where($lastWhere);
                });
            })->orWhereHas('DriverVehicles', function ($q) use ($lastWhere, $where, $searched_vehicle_number, $driver_id) {
                if (!empty($searched_vehicle_number)) {
                    $q->where([['vehicle_number', '=', $searched_vehicle_number]]);
                }
                if (!empty($driver_id)) {
                    $q->where('driver_vehicles.driver_id', $driver_id);
                }
                $q->whereHas('DriverVehicleDocument', function ($dvd) use ($lastWhere, $where) {
                    $dvd->where($where);
                    $dvd->whereHas('DriverVehicle', function ($dv) use ($lastWhere) {
                        $dv->whereHas('Driver', function ($inner) use ($lastWhere) {
                            $inner->where($lastWhere);
                        });
                    });
                });
            })->orWhereHas('DriverSegmentDocument', function ($q) use ($lastWhere, $where, $driver_id) {
                if (!empty($driver_id)) {
                    $q->where('driver_id', $driver_id);
                }
                $q->where($where);
                $q->whereHas('Driver', function ($inner) use ($lastWhere) {
                    $inner->where($lastWhere);
                });
            })->orderBy('updated_at', 'DESC');
        if (!empty($request->area_id) || !empty($request->parameter) || !empty($request->segment_id)) {
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
            if ($request->keyword) {
                $query->where($parameter, 'like', '%' . $request->keyword . '%');
            }
            if ($request->area_id) {
                $query->where('country_area_id', '=', $request->area_id);
            }
            if (!empty($request->segment_id)) {
                $arr_segment_id = $request->segment_id;
                $query->whereHas('Segment', function ($q) use ($arr_segment_id) {
                    $q->whereIn('segment_id', $arr_segment_id);
                });
            }
        }
        return $pagination == true ? $query->latest()->paginate(10) : $query;
    }

    public function getDriverExpiredDocuments($id, $merchant_id, $vehicle_id = null)
    {
        $whereIn = [1, 3, 4];
        $driverVehicleDocumentWith = ['DriverVehicleDocument' => function ($o) use ($whereIn) {
            $o->whereIn('document_verification_status', $whereIn);
        }];
        $driver = Driver::where('merchant_id', $merchant_id)->where('id', $id)->with(['DriverDocument' => function ($query) use ($whereIn) {
            $query->whereIn('document_verification_status', $whereIn)
                ->where('status', '=', 1);
        }, 'DriverVehicle' => function ($d_v) use ($driverVehicleDocumentWith, $whereIn) {
            $d_v->with($driverVehicleDocumentWith)->whereHas('DriverVehicleDocument', function ($p) use ($whereIn) {
                $p->whereIn('document_verification_status', $whereIn);
            });
        }])->whereHas('DriverDocument', function ($q) use ($id, $whereIn) {
            $q->where('driver_documents.driver_id', $id)->where('status', '=', 1)->whereIn('document_verification_status', $whereIn);
        })->orWhereHas('DriverVehicle', function ($r) use ($driverVehicleDocumentWith, $id, $whereIn, $vehicle_id) {
            if (!empty($vehicle_id)) {
                $r->where('driver_vehicles.id', $vehicle_id);
            }
            $r->where('driver_vehicles.driver_id', $id)
                ->with($driverVehicleDocumentWith)->whereHas('DriverVehicleDocument', function ($s) use ($whereIn) {
                    $s->whereIn('document_verification_status', $whereIn)->where('status', '=', 1);
                });
        })->first();
        return $driver;
    }

    public function getDriverBookingRequestData($request, $pagignation = true)
    {
        $merchant_id = get_merchant_id();
        $from = $request->from;
        $to = $request->to;
        $keyword = '';
        if ($request->parameter != '') {
            switch ($request->parameter) {
                case "1":
                    $parameter = "drivers.first_name";
                    break;
                case "2":
                    $parameter = "email";
                    break;
                case "3":
                    $parameter = "phoneNumber";
                    break;
            }
            $keyword = $request->keyword;
        }

        $query = Driver::with(['BookingRequestDriver' => function ($q) use ($from, $to) {
            $q->select(
                '*',
                \DB::raw('COUNT(*)AS total_trip'),
                \DB::raw('SUM(CASE WHEN request_status = 1 THEN 1 ELSE 0 END )AS no_response'),
                \DB::raw('SUM(CASE WHEN request_status = 2 THEN 1 ELSE 0 END )AS accepted'),
                \DB::raw('SUM(CASE WHEN request_status = 3 THEN 1 ELSE 0 END )AS reject')
            );
            if (!empty($from) && !empty($to)) {
                $q->whereBetween(DB::raw('DATE(created_at)'), array($from, $to));
            }
            $q->groupBy('driver_id');
        }])->whereHas('BookingRequestDriver', function ($query) use ($from, $to) {
            if (!empty($from) && !empty($to)) {
                $query->whereBetween(DB::raw('DATE(created_at)'), array($from, $to));
            }
        })->where([['merchant_id', '=', $merchant_id]]);

        if (!empty($keyword)) {
            $query->where($parameter, 'like', "%$keyword%");
        }
        if ($pagignation == true) {
            $drivers = $query->latest()->paginate(25);
        } else {
            $drivers = $query->latest()->get();
        }
        return $drivers;
    }

    //get setting of driver which were configured while online processing
    public function getDriverOnlineConfig($driver, $return = 'status')
    {
        $online_configuration['driver_vehicle_id'] = [];
        $online_configuration['segment_id'] = [];
        $online_configuration['service_type_id'] = [];
        $online_configuration['status'] = [];
        $online_work_configuration = $driver->ServiceTypeOnline;
        $return_status = false;
        $vehicle_id = null;
        $existing_enable = has_driver_multiple_or_existing_vehicle($driver->id, $driver->merchant_id);
        $online_configuration['status'] = $return_status;
        $merchant_id = $driver->merchant_id;
        $socket_data = [];
        $online_configuration['socket_data'] = [];
        $online_configuration['vehicle_type_id'] = NULL;
        if ($online_work_configuration->count() > 0) {
            $return_status = true;
            if ($return == 'all' || $return == 'online_details') {
                foreach ($online_work_configuration as $segment) {
                    $arr_data[] = [
                        'driver_vehicle_id' => $segment->pivot->driver_vehicle_id,
                        'segment_id' => $segment->pivot->segment_id,
                        'service_type_id' => $segment->pivot->service_type_id,
                    ];
                }
                $online_configuration['status'] = $return_status;
                $online_configuration['driver_vehicle_id'] = array_column($arr_data, 'driver_vehicle_id');
                $online_configuration['segment_id'] = array_column($arr_data, 'segment_id');
                $online_configuration['service_type_id'] = array_column($arr_data, 'service_type_id');

                $arr_service_type_id = [];
                if (isset($online_configuration['driver_vehicle_id'][0]) && !empty($online_configuration['driver_vehicle_id'][0])) {
                    $driver_vehicle = DriverVehicle::find($online_configuration['driver_vehicle_id'][0]);
                    $online_configuration['vehicle_type_id'] = $driver_vehicle->vehicle_type_id;
                    $arr_segment_pivot = $driver_vehicle->ServiceTypes->map(function ($inner_item) {
                        return $inner_item->pivot->toArray();
                    });
                    $arr_segment_pivot = $arr_segment_pivot->toArray();
                    $arr_service_type_id = array_column($arr_segment_pivot, 'service_type_id');
                }
                $driver_crt_object = new DriverController();
                $segment_services = $driver_crt_object->segmentServices($driver->id, $merchant_id, $arr_service_type_id);
                if (!empty($segment_services)) {
                    foreach ($segment_services as $segment_service) {
                        $temp_services = [];
                        foreach ($segment_service['arr_service'] as $service) {
                            array_push($temp_services, array("id" => $service['id'], "serviceName" => $service['serviceName']));
                        }
                        array_push($socket_data, array("id" => $segment_service['segment_id'], "name" => $segment_service['name'], "arr_service" => $temp_services));
                    }
                }
                $online_configuration['socket_data'] = $socket_data;
            }
        }
        if ($return == 'status') {
            return $return_status;
        } elseif ($return == 'online_details') {
            $vehicle_detail = "";
            $image = "";
            $pool_status = false;
            $arr_segment_name = [];
            $arr_segment_slug = [];
            $arr_service_name = [];
            if ($return_status == true) {
                if (isset($online_configuration['driver_vehicle_id'][0]) && $driver->segment_group_id == 1) {
                    $vehicle_id =  $online_configuration['driver_vehicle_id'][0];
                    // $driver_vehicle = $existing_enable ? $driver->DriverVehicle->where('id', $vehicle_id) : $driver->DriverVehicles->where('id', $vehicle_id);
                    if($existing_enable){
                        $driver_vehicle = $driver->DriverVehicle->where('id', $vehicle_id);
                        if(count($driver_vehicle) == 0){
                            $driver_vehicle = $driver->DriverVehicles->where('id', $vehicle_id);
                        }
                    }else{
                        $driver_vehicle = $driver->DriverVehicles->where('id', $vehicle_id);
                    }
                    $driver_vehicle = collect($driver_vehicle->values());
                    $driver_vehicle_number = !empty($driver_vehicle->vehicle_number)? $driver_vehicle->vehicle_number : "";

                    $driver_vehicle = $driver_vehicle[0];
                    $vehicle_make = !empty($driver_vehicle->VehicleMake) ? $driver_vehicle->VehicleMake->VehicleMakeName : "";
                    $vehicle_model = !empty($driver_vehicle->VehicleModel) ? $driver_vehicle->VehicleModel->VehicleModelName : "";

                    $vehicle_details =  $vehicle_make . ' ' . $vehicle_model . '(' . $driver_vehicle->vehicle_number . ')';
                    if(empty($vehicle_make) && empty($vehicle_model) && empty($driver_vehicle->vehicle_number)){
                        $vehicle_details = $driver_vehicle->VehicleType->VehicleTypeName;
                    }

                    $vehicle_detail  = $vehicle_details;
                    $image = !empty($driver_vehicle->VehicleType)? get_image($driver_vehicle->VehicleType->vehicleTypeImage, 'vehicle', $driver->merchant_id): "";
                    $pool_status = $driver_vehicle->VehicleType->pool_status ? $driver_vehicle->VehicleType->pool_status : false;
                }
                $arr_segment_id =  $online_configuration['segment_id'];
                $driver_segment =   $driver->Segment->whereIn('id', $arr_segment_id)->unique();
                $driver_segment = collect($driver_segment->values());
                foreach ($driver_segment as $segment) {
                    if (empty($image) && $driver->segment_group_id == 2) {
                        $image = isset($segment->MerchantSegment($driver->merchant_id)['pivot']->segment_icon) && !empty($segment->MerchantSegment($driver->merchant_id)['pivot']->segment_icon) ? get_image($segment->MerchantSegment($driver->merchant_id)['pivot']->segment_icon, 'segment', $driver->merchant_id, true) : get_image($segment->icon, 'segment_super_admin', NULL, false);
                    }
                    $arr_segment_name[] = !empty($segment->Name($merchant_id)) ? $segment->Name($merchant_id) : $segment->slag;
                    $arr_segment_slug[] = $segment->slag;
                }
                $arr_service_type_id =  $online_configuration['service_type_id'];
                $driver_services =   $driver->ServiceType->whereIn('id', $arr_service_type_id)->unique();
                $driver_services = collect($driver_services->values());
                foreach ($driver_services as $service) {
                    $arr_service_name[] =  $service->ServiceName($merchant_id);
                }
            }

            $details = [
                'image' => $image,
                'vehicle_detail' => $vehicle_detail,
                'pool_status' => $pool_status && in_array(5,$arr_service_type_id) ? true:false,
                'driver_vehicle_id' => $vehicle_id,
                'segment_name' => !empty($arr_segment_name) ? implode(',', $arr_segment_name) : "",
                'segment_slug' => !empty($arr_segment_slug) ? implode(',', $arr_segment_slug) : "",
                'service_type' => !empty($arr_service_name) ? implode(',', $arr_service_name) : "",
                'slider_type' => "ONLINE-OFFLINE", //ONLINE-OFFLINE or GO-TO-HOME
            ];
            //            $online_configuration = [];
            $online_configuration['status'] = $return_status;
            $online_configuration['detail'] = $details;
            //            $return_data['status'] = $return_status;
            //            $return_data['detail'] = $details;
            //            $return_data['socket_data'] = $socket_data;
            //            return $return_data;
        }
        return $online_configuration;
    }

    protected function getStripeRelatedDocumentList($stripe_connect_config, $driver)
    {
        $stripe_doc_list = [];
        $driver_documents = DriverDocument::where([['driver_id', '=', $driver->id]])->get()->toArray();
        $index = array_search($stripe_connect_config->personal_document_id, array_column($driver_documents, 'document_id'));
        if ($index != NULL || $index == 0) {
            $url = get_image($driver_documents[$index]['document_file'], 'driver_document', $driver->merchant_id);
            $personal_document = $url;
            $personal_document_number = $driver_documents[$index]['document_number'];
            $stripe_doc_list['personal_document'] = array('image' => $personal_document, 'doc_number' => $personal_document_number, 'image_name' => $driver_documents[$index]['document_file']);
        } else {
            $stripe_doc_list['personal_document'] = array('image' => NULL, 'doc_number' => NULL, 'image_name' => NULL);
            //            throw new \Exception('Personal Document Not Found');
        }
        $index = array_search($stripe_connect_config->photo_front_document_id, array_column($driver_documents, 'document_id'));
        if ($index != NULL || $index == 0) {
            $url = get_image($driver_documents[$index]['document_file'], 'driver_document', $driver->merchant_id);
            $photo_front_document = $url;
            $photo_front_document_number = $driver_documents[$index]['document_number'];
            $stripe_doc_list['photo_front_document'] = array('image' => $photo_front_document, 'doc_number' => $photo_front_document_number, 'image_name' => $driver_documents[$index]['document_file']);
        } else {
            $stripe_doc_list['photo_front_document'] = array('image' => NULL, 'doc_number' => NULL, 'image_name' => NULL);
            //            throw new \Exception('Photo ID Front Document Not Found');
        }
        $index = array_search($stripe_connect_config->photo_back_document_id, array_column($driver_documents, 'document_id'));
        if ($index != NULL || $index == 0) {
            $url = get_image($driver_documents[$index]['document_file'], 'driver_document', $driver->merchant_id);
            $photo_back_document = $url;
            $photo_back_document_number = $driver_documents[$index]['document_number'];
            $stripe_doc_list['photo_back_document'] = array('image' => $photo_back_document, 'doc_number' => $photo_back_document_number, 'image_name' => $driver_documents[$index]['document_file']);
        } else {
            $stripe_doc_list['photo_back_document'] = array('image' => NULL, 'doc_number' => NULL, 'image_name' => NULL);
            //            throw new \Exception('Photo ID Back Document Not Found');
        }
        $index = array_search($stripe_connect_config->additional_document_id, array_column($driver_documents, 'document_id'));
        if ($index != NULL || $index == 0) {
            $url = get_image($driver_documents[$index]['document_file'], 'driver_document', $driver->merchant_id);
            $additional_document = $url;
            $additional_document_number = $driver_documents[$index]['document_number'];
            $stripe_doc_list['additional_document'] = array('image' => $additional_document, 'doc_number' => $additional_document_number, 'image_name' => $driver_documents[$index]['document_file']);
        } else {
            $stripe_doc_list['additional_document'] = array('image' => NULL, 'doc_number' => NULL, 'image_name' => NULL);
            //            throw new \Exception('Additional Document Not Found');
        }
        return $stripe_doc_list;
    }

    protected function getStripeRelatedDocuments($stripe_connect_config, $driver)
    {
        $stripe_doc_list = [];
        $driver_documents = DriverDocument::where([['driver_id', '=', $driver->id]])->get()->toArray();
        $index = array_search($stripe_connect_config->personal_document_id, array_column($driver_documents, 'document_id'));
        if ($index != NULL || $index == 0) {
            $url = get_image($driver_documents[$index]['document_file'], 'driver_document', $driver->merchant_id);
            $info = pathinfo($url);
            $contents = file_get_contents($url);
            $basename = explode("?", $info['basename'])[0];
            $file = '/tmp/' . $basename;
            file_put_contents($file, $contents);
            $personal_document = new UploadedFile($file, $basename);
            $personal_document_number = $driver_documents[$index]['document_number'];
            $stripe_doc_list['personal_document'] = array('image' => $personal_document, 'doc_number' => $personal_document_number, 'image_name' => $driver_documents[$index]['document_file']);
        } else {
            throw new \Exception('Personal Document Not Found');
        }
        $index = array_search($stripe_connect_config->photo_front_document_id, array_column($driver_documents, 'document_id'));
        if ($index != NULL || $index == 0) {
            $url = get_image($driver_documents[$index]['document_file'], 'driver_document', $driver->merchant_id);
            $info = pathinfo($url);
            $contents = file_get_contents($url);
            $basename = explode("?", $info['basename'])[0];
            $file = '/tmp/' . $basename;
            file_put_contents($file, $contents);
            $photo_front_document = new UploadedFile($file, $basename);
            $photo_front_document_number = $driver_documents[$index]['document_number'];
            $stripe_doc_list['photo_front_document'] = array('image' => $photo_front_document, 'doc_number' => $photo_front_document_number, 'image_name' => $driver_documents[$index]['document_file']);
        } else {
            throw new \Exception('Photo ID Front Document Not Found');
        }
        $index = array_search($stripe_connect_config->photo_back_document_id, array_column($driver_documents, 'document_id'));
        if ($index != NULL || $index == 0) {
            $url = get_image($driver_documents[$index]['document_file'], 'driver_document', $driver->merchant_id);
            $info = pathinfo($url);
            $contents = file_get_contents($url);
            $basename = explode("?", $info['basename'])[0];
            $file = '/tmp/' . $basename;
            file_put_contents($file, $contents);
            $photo_back_document = new UploadedFile($file, $basename);
            $photo_back_document_number = $driver_documents[$index]['document_number'];
            $stripe_doc_list['photo_back_document'] = array('image' => $photo_back_document, 'doc_number' => $photo_back_document_number, 'image_name' => $driver_documents[$index]['document_file']);
        } else {
            throw new \Exception('Photo ID Back Document Not Found');
        }
        $index = array_search($stripe_connect_config->additional_document_id, array_column($driver_documents, 'document_id'));
        if ($index != NULL || $index == 0) {
            $url = get_image($driver_documents[$index]['document_file'], 'driver_document', $driver->merchant_id);
            $info = pathinfo($url);
            $contents = file_get_contents($url);
            $basename = explode("?", $info['basename'])[0];
            $file = '/tmp/' . $basename;
            file_put_contents($file, $contents);
            $additional_document = new UploadedFile($file, $basename);
            $additional_document_number = $driver_documents[$index]['document_number'];
            $stripe_doc_list['additional_document'] = array('image' => $additional_document, 'doc_number' => $additional_document_number, 'image_name' => $driver_documents[$index]['document_file']);
        } else {
            throw new \Exception('Additional Document Not Found');
        }
        return $stripe_doc_list;
    }

    public  function getDriverSummary($request, $for_taxi_company = false)
    {
        $merchant = get_merchant_id(false);
        if($merchant->Configuration->driver_training == 1){
            $query_string = 'COUNT(CASE WHEN signupStep = "8" AND reject_driver = "1" AND is_approved = "2"   THEN 1  END) AS pending';
        }
        else{
            $query_string = 'COUNT(CASE WHEN signupStep = "8" AND reject_driver = "1" AND (in_training = "2" OR in_training = "3") AND is_approved = "2"   THEN 1  END) AS pending';
        }
        $query = Driver::select(
            DB::raw('COUNT(*)AS all_drivers'),
            DB::raw('COUNT(CASE WHEN signupStep = "9"  THEN 1  END) AS approved'),
            DB::raw('COUNT(CASE WHEN signupStep = "8" AND reject_driver = "1" AND in_training = "1" AND is_approved = "1"  THEN 1  END) AS pending_training'),
            DB::raw($query_string),
            DB::raw('COUNT(CASE WHEN signupStep = "8" AND reject_driver = "2"  THEN 1  END) AS rejected'),
            DB::raw('COUNT(CASE WHEN signupStep <= "7"  THEN 1  END) AS basic_signup')
        )->where([['merchant_id', '=', $request->merchant_id], ['driver_delete', NULL]]);

        if ($for_taxi_company) {
            $query->where("taxi_company_id", $request->taxi_company_id);
        } else {
            $query->where("taxi_company_id", NULL);
        }
        if (!empty($request->area_id) || !empty($request->parameter) || !empty($request->segment_id) || !empty($request->country_id)) {
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
            if ($request->parameter == "4") {
                $vechicle_number = $request->keyword;
                $query->whereHas('DriverVehicles', function ($q) use ($vechicle_number) {
                    $q->where([['vehicle_number', '=', $vechicle_number]]);
                })->with(['DriverVehicles' => function ($qq) use ($vechicle_number) {
                    $qq->where([['vehicle_number', '=', $vechicle_number]]);
                }]);
            } else if ($request->keyword) {
                $query->where($parameter, 'like', '%' . $request->keyword . '%');
            }
            if ($request->area_id) {
                $query->where('country_area_id', '=', $request->area_id);
            }
            if ($request->country_id) {
                $query->where('country_id', '=', $request->country_id);
            }
            if (!empty($request->segment_id)) {
                $arr_segment_id = $request->segment_id;
                $query->whereHas('Segment', function ($q) use ($arr_segment_id) {
                    $q->whereIn('segment_id', $arr_segment_id);
                });
            }
        }
        if (!empty($request->driver_agency_id)) {
            $query->where('driver_agency_id', '=', $request->driver_agency_id);
        }
        $driver_summary =   $query->first();
        return $driver_summary;
    }

    public function SubscriptionPackageExpiryCheck($driver, $segment_id)
    {
        // find free package if assigned to driver
        $free_package = DriverSubscriptionRecord::select('package_total_trips', 'id', 'used_trips')->where([['package_type', 1], ['segment_id', $segment_id], ['driver_id', $driver->id], ['status', 2], ['end_date_time', '>', date('Y-m-d H:i:s')]])->orderBy('id', 'DESC')->first();
        // $free_package_exist = false;
        if (!empty($free_package->id) && $free_package->used_trips < $free_package->package_total_trips) {
            // $free_package_exist = true;
            $free_package->used_trips = $free_package->used_trips + 1;
            $free_package->save();
        } else {
            // if there is no free package or used all free ride then check paid package of driver and deduct ride from that package
            // elseif($free_package_exist == false)
            $paid_package = DriverSubscriptionRecord::select('package_total_trips', 'id', 'used_trips')->where([['package_type', 2], ['segment_id', $segment_id], ['driver_id', $driver->id], ['status', 2], ['end_date_time', '>', date('Y-m-d H:i:s')]])->orderBy('id', 'DESC')->first();
            if (!empty($paid_package->id) && $paid_package->used_trips < $paid_package->package_total_trips) {
                $paid_package->used_trips = $paid_package->used_trips + 1;
                $paid_package->save();
            }
        }
        return true;
    }
    
    public function checkForPendingDriverDocs($driver_id, $vehicle_id){
        
        $driver = Driver::find($driver_id);
        $driver_id = $driver->id;
        $query = CountryArea::where('id', $driver->country_area_id);
     
        $query->with([
            'Documents' => function ($p) {
                $p->addSelect('documents.id', 'expire_date as expire_status', 'documentNeed as document_mandatory', 'document_number_required');
                $p->where('documentStatus', 1);
            }
        ]);
        
        $query->with(['VehicleDocuments' => function ($q) {
            $q->addSelect('documents.id', 'expire_date as expire_status', 'documentNeed as document_mandatory', 'document_number_required');
            $q->where('documentStatus', 1);
        }]);
       
        $area_document = $query->first();
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $status_name = driver_document_status($string_file);
        \Config::get('custom.driver_document_status');
        $merchant_id = $driver->merchant_id;

        // $personal_document_data = $area_document->Documents;
              $country_area = CountryArea::findOrFail($driver->country_area_id);
           if(isset($driver->Merchant->ApplicationConfiguration->local_citizen_foreigner_documents) && $driver->Merchant->ApplicationConfiguration->local_citizen_foreigner_documents == 1){ 
                    $all_required_docs = $country_area->documents
                    ->filter(function ($doc) {
                        return $doc->pivot->document_type == 1 && $doc->documentNeed == 1;
                    })
                    ->pluck('id')
                    ->toArray();
                }else{
                 $all_required_docs = $area_document->Documents()->where("documentNeed", 1)->wherePivot('country_area_id', '=', $driver->country_area_id)->pluck("documents.id")->toArray();              
                }
        // $all_required_docs = $area_document->Documents()->where("documentNeed", 1)->wherePivot('country_area_id', '=', $driver->country_area_id)->pluck("documents.id")->toArray();
        $driver_personal_doc = DriverDocument::where([['driver_id', '=', $driver->id], ['status', '=', 1]])->get();
        $driver_personal_doc = array_column($driver_personal_doc->toArray(), NULL, 'document_id');
        $driver_personal_doc_id = array_keys($driver_personal_doc);
        // dd('hhh', $all_required_docs, $driver_personal_doc_id );
        $vehicle = $driver->DriverVehicle->where('id', $vehicle_id)->first();
        $vehicle_documents = $area_document->VehicleDocuments()->where("documentNeed", 1)->wherePivot('vehicle_type_id', '=', $vehicle->vehicle_type_id)->pluck("documents.id")->toArray();
        $driver_vehicle_doc = DriverVehicleDocument::where([['status', '=', 1], ['driver_vehicle_id', '=', $vehicle->id]])->get();
        $driver_vehicle_doc = array_column($driver_vehicle_doc->toArray(), NULL, 'document_id');
        $driver_vehicle_doc_id = array_keys($driver_vehicle_doc);

        $personal_docs_pending  = (isset($driver_personal_doc_id) && isset($all_required_docs) && count($all_required_docs) <= count($driver_personal_doc_id));
        $vechilcle_docs_pending = (isset($driver_vehicle_doc_id) && isset($vehicle_documents) && count($vehicle_documents) <= count($driver_vehicle_doc_id));
        // dd($all_required_docs, $driver_personal_doc_id ,$vehicle_documents $driver_vehicle_doc_id);
        
        return ($personal_docs_pending && $vechilcle_docs_pending);
    }
}
