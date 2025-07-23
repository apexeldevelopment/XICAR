<?php

namespace App\Traits;

use App\Models\Bus;
use App\Models\LanguageVehicleMake;
use App\Models\LanguageVehicleModel;
use App\Models\Merchant;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Auth;
use Illuminate\Support\Facades\App;

trait BusTrait
{

    public function busesByArea($area_id)
    {
        $bus = new Bus;
        $arr_buses = $bus->arrBuses($area_id);
        $return_buses = [];
        $bus_obj = new Bus;
        foreach ($arr_buses as $bus) {
            $return_buses[$bus->id] = $bus_obj->busName($bus);
        }
        return $return_buses;
    }
    public function arrBusDropDown($arr_buses,$string_file)
    {
        $buses = "";
        if (empty($arr_buses)) {
            $buses = "<option value=''>" . trans("$string_file.no_service_area") . "</option>";
        } else {
            $buses .= "<option value=''>" . trans("$string_file.select") . "</option>";
            foreach ($arr_buses as $key => $value) {
                $buses .= "<option id='" . $key . "' value='" . $key . "'>" . $value . "</option>";
            }
        }
        return $buses;
    }



    public function vehicleModel($vehicle_model, $merchant_id, $vehicleMake_id, $vehicle_type, $vehicle_seat)
    {
        $vehcleModel = VehicleModel::select('id')->find($vehicle_model);
        if (empty($vehcleModel)) {
            $vehcleModel = VehicleModel::whereHas('LanguageVehicleModel', function ($query) use ($vehicle_model) {
                $query->where([['vehicleModelName', '=', $vehicle_model]]);
            })->where([['merchant_id', '=', $merchant_id], ['vehicle_type_id', '=', $vehicle_type], ['vehicle_make_id', '=', $vehicleMake_id]])->first();

            if (empty($vehcleModel)) {
                $vehcleModel = VehicleModel::create([
                    'merchant_id' => $merchant_id,
                    'vehicle_type_id' => $vehicle_type,
                    'vehicle_make_id' => $vehicleMake_id,
                    'vehicle_seat' => $vehicle_seat,
                ]);
                $this->SaveLanguageVehicleModel($merchant_id, $vehcleModel->id, $vehicle_model, $vehicle_model);
            }
        }
        return $vehcleModel->id;
    }

    public function SaveLanguageVehicleModel($merchant_id, $vehicle_model_id, $name, $description)
    {
        LanguageVehicleModel::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'vehicle_model_id' => $vehicle_model_id
        ], [
            'vehicleModelName' => $name,
            'vehicleModelDescription' => $description,
        ]);
    }

    public function vehicleMake($vehicleMake_id, $merchant_id)
    {
        $vehicleMake = VehicleMake::select('id')->find($vehicleMake_id);
        if (empty($vehicleMake)) {
            $checkMake = LanguageVehicleMake::where([['merchant_id', '=', $merchant_id], ['vehicleMakeName', '=', $vehicleMake_id]])->first();
            if (!empty($checkMake)) {
                $vehicleMake = VehicleMake::find($checkMake->vehicle_make_id);
            } else {
                $vehicleMake = VehicleMake::create([
                    'merchant_id' => $merchant_id,
                    'vehicleMakeLogo' => $vehicleMake_id,
                ]);
                $this->SaveLanguageVehicle($merchant_id, $vehicleMake->id, $vehicleMake_id, $vehicleMake_id);
            }
        }
        return $vehicleMake->id;
    }

    public function SaveLanguageVehicle($merchant_id, $vehicle_make_id, $name, $description)
    {
        LanguageVehicleMake::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(),
            'vehicleMakeName' => $name,
        ], [
            'vehicle_make_id' => $vehicle_make_id,
            'vehicleMakeDescription' => $description,
        ]);
    }

    public function getMerchantBusSegmentServices($merchant_id){
        $service_type_arr = [];
        $merchant = Merchant::with(["ServiceType" => function($q){
            $q->whereHas("Segment", function($k){
                $k->where("segment_group_id", 4);
            });
        }])->whereHas("ServiceType", function($k){
            $k->whereHas("Segment", function($e){
                $e->where("segment_group_id", 4);
            });
        })->find($merchant_id);
        if(!empty($merchant)){
            foreach($merchant->ServiceType as $service_type){
                $service_type_arr[$service_type->id] = $service_type->ServiceName($merchant->id)." (".$service_type->Segment->Name($merchant->id).")";
            }
        }
        return $service_type_arr;
    }

    public function prepareBusSeatArray($bus_design_type, $seat_details){
        $returns = array("lower" => [], "upper" => []);
        switch ($bus_design_type){
            case 1: // 2 x 2 seating
                $lower_seats = array_filter($seat_details, function ($var) {
                    return ($var['type_slug'] == 'LOWER');
                });
                $returns['lower'] = array_chunk($lower_seats, 4);
                break;
            case 2: // 1x2-1x2 Seating and Sleeper
                $lower_seats = array_filter($seat_details, function ($var) {
                    return ($var['type_slug'] == 'LOWER');
                });
                $upper_seats = array_filter($seat_details, function ($var) {
                    return ($var['type_slug'] == 'UPPER');
                });
                $returns['lower'] = array_chunk($lower_seats, 3);
                $returns['upper'] = array_chunk($upper_seats, 3);
                break;
            case 3: // 2x2-2x2 Seating and Sleeper
                $lower_seats = array_filter($seat_details, function ($var) {
                    return ($var['type_slug'] == 'LOWER');
                });
                $upper_seats = array_filter($seat_details, function ($var) {
                    return ($var['type_slug'] == 'UPPER');
                });
                $returns['lower'] = array_chunk($lower_seats, 4);
                $returns['upper'] = array_chunk($upper_seats, 4);
                break;
            case 4: // 2x2-1x2 Seating and Sleeper
                $lower_seats = array_filter($seat_details, function ($var) {
                    return ($var['type_slug'] == 'LOWER');
                });
                $upper_seats = array_filter($seat_details, function ($var) {
                    return ($var['type_slug'] == 'UPPER');
                });
                $returns['lower'] = array_chunk($lower_seats, 4);
                $returns['upper'] = array_chunk($upper_seats, 3);
                break;
            default:
                $lower_seats = array_filter($seat_details, function ($var) {
                    return ($var['type_slug'] == 'LOWER');
                });
                $returns['lower'] = array_chunk($lower_seats, 4);
        }
        return $returns;
    }
}
