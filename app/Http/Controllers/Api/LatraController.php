<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Helper\FindDriverController;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\PolygenController;
use App\Http\Controllers\Helper\Estimate;
use App\Models\BookingConfiguration;
use App\Models\Configuration;
use App\Models\CountryArea;
use App\Models\Segment;
use App\Models\ServiceType;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\PriceCard;
use App\Models\VehicleType;
use App\Models\RewardPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Traits\AreaTrait;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Models\ServicePackage;
use DB;
use App\Models\Category;

class LatraController extends Controller
{
    use ApiResponseTrait,MerchantTrait;
    public function getToken($authToken,$url){
        try {

            $formData = [
                'grant_type' => 'client_credentials',
                'scope' => 'read',
            ];

            $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url .'/oauth/token',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $formData,
                        CURLOPT_HTTPHEADER => ['Authorization: Basic ' .$authToken],
                    ));
            
                    $response = json_decode(curl_exec($curl));
                    curl_close($curl);

                    if($response === false){
                        return "";
                    }else{
                        return $response->access_token;
                    }
        }catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }


    }

    public function verifyVehicleLicense(Request $request){
        $validator = Validator::make($request->all(), [
            'vehicle_number' => 'required',
        ],[
           'vehicle_number.required'=> 'vehicle number is required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try {
        $string_file = $this->getStringFile($request->merchant_id);
        $merchant = Merchant::find($request->merchant_id);

        $authToken = !empty($merchant->ApplicationConfiguration->latra_auth_token) ? $merchant->ApplicationConfiguration->latra_auth_token : "";
        $systemUrl = !empty($merchant->ApplicationConfiguration->latra_system_url) ? $merchant->ApplicationConfiguration->latra_system_url : "";

        if($authToken && $systemUrl){
            $vehicleNo = $request->vehicle_number;

            $token = $this->getToken($authToken,$systemUrl);
            if(empty($token)){
                return $this->failedResponse('Invalid Token or Token not generated');
            }

            $url = $systemUrl .'/api/vehicles/verify-vehicle-license?vehicleNo='.$vehicleNo;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' .$token],
            ));
                
            $response = curl_exec($curl);
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response);
            if($response === false){
                $err = curl_error($curl);
                if($err){
                    // return response()->json(['result' => '0', 'message' => $err, 'data' => []]);
                    return $this->failedResponse($err);
                }
            }elseif(isset($xml->error)){
                $errorData = json_decode(json_encode($xml),true);
                if($xml === false){
                    //return response()->json(['result' => '0','message' => $response]);
                    return $this->failedResponse($response);
                }else{
                    //return response()->json(['result' => '0','message' => $errorData['error']]);
                    return $this->failedResponse($errorData['error']);
                }
            }else{
                $res=  json_decode($response,true);
                if(isset($res['status'])){
                    if($res['status'] == false){
                        // return response()->json(['result' => '0','data' => $res,'message' => trans("$string_file.vehicle_not_verified")]);
                        return $this->failedResponse(trans("$string_file.vehicle_not_verified"));
                    }else{
                        // return response()->json(['result' => '1','data' => $res,'message' => trans("$string_file.vehicle_verified")]);
                        return $this->successResponse(trans("$string_file.vehicle_verified"),$res);
                    }
                }
            }
        }else{
            return $this->failedResponse(trans("$string_file.unknown")." ".trans("$string_file.error"));
        }
        

    }catch (\Exception $e) {
        return $this->failedResponse($e->getMessage());
    }
    }
}