<?php

namespace App\Http\Controllers\Api;

use App\Traits\AreaTrait;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\ManualDispatchController;
use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\DistanceController;
use App\Http\Controllers\Helper\FindDriverController;
use App\Http\Controllers\Helper\Merchant;
use App\Http\Controllers\Helper\PriceController;
use App\Http\Controllers\Api\HomeController;
use App\Models\BookingConfiguration;
use App\Models\BookingRequestDriver;
use App\Models\MerchantWhatsapp;
use App\Models\Outstanding;
use App\Models\PriceCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Driver;
use App\Models\DriverVehicle;
use App\Models\User;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Exception;
use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;
use Twilio\TwiML\MessagingResponse;
use App\Models\BookingCheckout;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\PolygenController;
use App\Models\CountryArea;
use Illuminate\Support\Facades\DB;
use App\Traits\MerchantTrait;
use App\Traits\BookingTrait;
use App\Http\Controllers\Helper\Merchant as helperMerchant;
use App\Traits\ManualDispatchTrait;

// use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    //
    public $sid, $token, $from;
    use MerchantTrait, BookingTrait;

    function setParam($request = NULL, $merchant_id = NULL)
    {
        if (!empty($request)) {
            $this->sid      = $request->AccountSid;
            $merchant_whatsapp = MerchantWhatsApp::where('sid', $request->AccountSid)->first();
            $this->token    = $merchant_whatsapp->token;
            $this->from     = $merchant_whatsapp->from;
            $request->request->add(['merchant_id' => $merchant_whatsapp->merchant_id]);
            // dd($request->all());
            return $request;
        }
    }

    public function receivedNewMessage(Request $request)
    {
        $request = $this->setParam($request);
        \Log::channel('whatsapp_api')->emergency($request);
    }

    public function messageStatus(Request $request)
    {
        try{
        
        $user = $this->verifyUser($request);
        $number = preg_replace('/^whatsapp:/', '', $request->To);
        $merchant = MerchantWhatsApp::where('from', $number)->where('is_active', 1)->first();
        if(empty($merchant))
        {
            return "merchant is inactive";
        }
        if(!empty($user))
        {
            $configuration = BookingConfiguration::where('merchant_id', $user->Merchant->id)->first();
        }
        $fromPhoneNumber = $request->input('From'); // Assuming this is the user's phone number
        $data['WaId']  = $fromPhoneNumber;
        $body = strtolower(trim($request->input('Body')));
        $buttonbody = strtolower(trim($request->input('ButtonPayload')));
        if (in_array($body, ['hi', 'hello', 'help','Create Account'])) {
        $this->clearSessionData($fromPhoneNumber);
        } 
        
        // Get session data
        $sessionData = $this->getSessionData($fromPhoneNumber);

        if (!$sessionData) {
            $sessionData = ['stage' => 'initial'];
        }
        
         $data['account_id'] = $merchant->sid;
         $data['auth_token'] = $merchant->token;
         $data['from']       = $merchant->from;
        switch ($sessionData['stage']) {
            case 'initial':
                if ($buttonbody == '1') {
                    // Existing user wants to book a ride
                    $data['templateSid'] = "HXb534bd41ade5b7139bfa597d82e12640";
                    $data['param']       = array("1" =>$user->first_name);
                    $this->sendWhatsApp($data);
                     $this->updateSessionData($fromPhoneNumber, ['stage' => 'awaiting_locations']);
                    
                } elseif ($buttonbody == '2') {
                    
                    // User wants to create a new account
                    $data['templateSid'] = "HX3fa8f86d900b9bb74592e30b8c465674";
                    $data['param']       = array();
                    $this->sendWhatsApp($data);
                    $this->updateSessionData($fromPhoneNumber, ['stage' => 'awaiting_name']);
                    // $this->updateSessionData($fromPhoneNumber, ['stage' => 'initiate_create_account']);
                } elseif ($body == 'help') {
                    // User asks for help
                    $data['templateSid'] = "HX16e9d8bbbaff9e82ee6cc8eacd474f7b";
                    $data['param']       = array("1" =>$user->first_name);
                    $this->sendWhatsApp($data);
                } else {
                     
                    // Handle other initial messages
                    $user = $this->verifyUser($request);
                    if (!empty($user)) {
                        $data['templateSid'] = "HXb534bd41ade5b7139bfa597d82e12640";
                        $data['param']       = array("1" =>$user->first_name);
                        $this->sendWhatsApp($data);
                        $this->updateSessionData($fromPhoneNumber, ['stage' => 'awaiting_locations']);
                    } else {
                        $data['templateSid'] = "HXc3bda9c4e2326a06bf2e5b1ad3326983";
                        $data['param']       = array();
                        $this->sendWhatsApp($data);
                    }
                }
                break;

            case 'awaiting_locations':
                 
                if (preg_match('/^from location:(.*)\nto location:(.*)$/is', $body, $matches)) {
                    // User provided locations via text
                    $fromLocation = trim($matches[1]);
                    $toLocation   = trim($matches[2]);
                    $fromLatLng    = GoogleController::getLatLong($fromLocation, $configuration->google_key);
                    $toLatLng      = GoogleController::getLatLong($toLocation, $configuration->google_key);
                    $areaPickup    = PolygenController::Area($fromLatLng['lat'], $fromLatLng['lng'], $user->merchant_id);
                    $areaDrop      = PolygenController::Area($toLatLng['lat'], $toLatLng['lng'], $user->merchant_id);
                } 
                elseif ($request->input('Latitude') && $request->input('Longitude')) {
                    // User provided locations via WhatsApp location pin
                    if (!isset($sessionData['from_location'])) {
                        
                        $fromLocation = $request->input('Address');
                        $fromLatLng   = array("lat" => $request->input('Latitude'),"lng" => $request->input('Longitude'));
                        $data_loc     = GoogleController::GoogleLocation($fromLatLng['lat'],$fromLatLng['lng'],$configuration->google_key);
                        $this->updateSessionData($fromPhoneNumber, ['stage' => 'awaiting_locations',
                                                                    'from_location' => $data_loc,
                                                                    'fromLatLng'    => $fromLatLng
                        ]);
                        $data['templateSid'] = "HX606271c9d2d334e1aab75b9a0ca2c8c5";
                        $data['param']       = array();
                        $this->sendWhatsApp($data);
                        return;
                    } else {
                       
                        $toLatLng     = array("lat" => $request->input('Latitude'),"lng" => $request->input('Longitude'));
                        $toLocation   = GoogleController::GoogleLocation($toLatLng['lat'], $toLatLng['lng'],$configuration->google_key);
                        $fromLocation = $sessionData['from_location'];
                        $fromLatLng   = $sessionData['fromLatLng'];
                        $areaPickup   = PolygenController::Area($fromLatLng['lat'], $fromLatLng['lng'], $user->merchant_id);
                        $areaDrop     = PolygenController::Area($toLatLng['lat'], $toLatLng['lng'], $user->merchant_id);
                        
                    }
                }

                if (!empty($fromLocation) && !empty($toLocation)) {
                    
                   
                    if(empty($areaPickup) || empty($areaDrop))
                    { 
                        $data['templateSid'] = "HX5749fdca06218227721026b129051cad";
                        $data['param']       = array();
                        $this->sendWhatsApp($data);
                        break;
                        
                    }

                    // $data['templateSid'] = "HX22d57b46eff12b1e49373bee2ed59320";
                    // $data['param']       = array("1" => $fromLocation,"2" => $toLocation);
                    // $this->sendWhatsApp($data);
        
                    $user = $this->verifyUser($request);
                    // $apiController = new HomeController();
                    // $apiRequest = new \Illuminate\Http\Request([
                    //     "latitude" => $fromLatLng['lat'],
                    //     "longitude" => $fromLatLng['lng'],
                    //     "segment_id" => 1
                    // ]);
                    // $vehicle_list_api = $apiController->userHomeScreen($apiRequest);
                    // $response = json_decode($vehicle_list_api);
                    
                    // \Log::channel('whatsapp_booking')->emergency($response->data);
                    $merchant_segment_list = $user->Merchant->Segment->pluck("slag")->toArray();
                     
                    if(in_array('TAXI',$merchant_segment_list))
                    {
                        if(count($user->Merchant->VehicleType) > 0)
                        {
                            $merchant_id  = $user->Merchant->id;
                            $areas        = $areaPickup['id'];
                             
                            $vehicle_list = CountryArea::select('vt.id','vt.vehicleTypeImage','vt.ride_now','vt.ride_later','vt.is_gallery_image_upload','vt.volumetric_capacity','vt.package_weight_range')
                                            ->addSelect('cavt.service_type_id','cavt.vehicle_type_id as id','country_areas.merchant_id','lvt.vehicleTypeName','lvt.vehicleTypeDescription')
                                            ->where('country_areas.merchant_id',$user->Merchant->id)
                                            ->where('country_areas.id',$areas)
                                            ->join('country_area_vehicle_type as cavt','cavt.country_area_id','=','country_areas.id')
                                            ->join('vehicle_types as vt','vt.id','=','cavt.vehicle_type_id')
                                            ->join('language_vehicle_types as lvt','vt.id','=','lvt.vehicle_type_id')
                                            ->join('price_cards as pc','vt.id','=','pc.vehicle_type_id')
                                            ->whereIn('cavt.service_type_id',[1])
                                            ->where('cavt.status',1) // Which vehicle type is active
                                            ->where('pc.country_area_id',$areas)
                                            ->where('pc.merchant_id',$user->Merchant->id)
                                            ->where('vt.admin_delete',NULL)
                                            ->where('vt.vehicleTypeStatus',1)
                                            ->whereIn('pc.service_type_id',[1])
                                            ->groupBy('cavt.vehicle_type_id')
                                            ->groupBy('cavt.service_type_id')
                                            ->orderBy('vt.sequence')
                                            ->get();
                             
                                               
                        }
                                            
                    }
                     
                    $vehicleTypes = [];
                    $count = 1;
                    $message = "Please select your vehicle type by replying with the corresponding number:\n";                 
                    foreach ($vehicle_list as $vehicle) 
                    {
                        $vehicleTypes[$vehicle->id] = $vehicle->vehicleTypeName;
                        $message .= "$count. $vehicle->vehicleTypeName\n";
                        $count++;                   
                    }
                    $data['msg'] = $message;
                    $this->sendWhatsApp($data);
                     $this->updateSessionData($fromPhoneNumber, [
                            'stage' => 'awaiting_vehicle_type',
                            'from_location' => $fromLocation,
                            'to_location' => $toLocation,
                            'from_latlng' => $fromLatLng,
                            'to_latlng' => $toLatLng,
                            'area_id'   => $areaPickup['id']
                        ]);
                } else {
                    
                    $data['msg'] = "Please provide both the From location and To location.";
                    $this->sendWhatsApp($data);
                    $this->updateSessionData($fromPhoneNumber, ['stage' => 'awaiting_locations']);
                }
                break;
            
            case 'awaiting_name':
                
                $sessionData['name'] = $body;
                $this->updateSessionData($fromPhoneNumber, ['stage' => 'initial', 'name' => $body]);
                      // Call the createAccount function
                $request = array(
                    "name" => $sessionData['name'],
                    "request" => $request->all(),
                    );
                $respo = $this->createAccount($request);
                 \Log::channel('whatsapp_booking')->emergency($respo);
                 if($respo)
                 {
                     $data['templateSid'] = "HXb7eaecdf6f32659407fe8795d94f13fe";
                     $data['param']       = array("1" =>$respo);
                 }else
                 {
                     $data['templateSid'] = "HX58f625905e1ff29ea0ff06b2447d20ce";
                 }
                
                $this->sendWhatsApp($data);
    
                     // Clear session data after account creation
                $this->clearSessionData($fromPhoneNumber);
                break;

                
            case 'awaiting_vehicle_type':
                    
                        $user = $this->verifyUser($request);
                        
                        $merchant_segment_list = $user->Merchant->Segment->pluck("slag")->toArray();
                         
                        if(in_array('TAXI',$merchant_segment_list))
                        {
                            if(count($user->Merchant->VehicleType) > 0)
                            {
                                $merchant_id  = $user->Merchant->id;
                                $areas        = $sessionData['area_id'];
                                
                                $vehicle_list = CountryArea::select('vt.id','vt.vehicleTypeImage','vt.ride_now','vt.ride_later','vt.is_gallery_image_upload','vt.volumetric_capacity','vt.package_weight_range')
                                                ->addSelect('cavt.service_type_id','cavt.vehicle_type_id as id','country_areas.merchant_id','lvt.vehicleTypeName','lvt.vehicleTypeDescription')
                                                ->where('country_areas.merchant_id',$user->Merchant->id)
                                                ->where('country_areas.id',$areas)
                                                ->join('country_area_vehicle_type as cavt','cavt.country_area_id','=','country_areas.id')
                                                ->join('vehicle_types as vt','vt.id','=','cavt.vehicle_type_id')
                                                ->join('language_vehicle_types as lvt','vt.id','=','lvt.vehicle_type_id')
                                                ->join('price_cards as pc','vt.id','=','pc.vehicle_type_id')
                                                ->whereIn('cavt.service_type_id',[1])
                                                ->where('cavt.status',1) // Which vehicle type is active
                                                ->where('pc.country_area_id',$areas)
                                                ->where('pc.merchant_id',$user->Merchant->id)
                                                ->where('vt.admin_delete',NULL)
                                                ->where('vt.vehicleTypeStatus',1)
                                                ->whereIn('pc.service_type_id',[1])
                                                ->groupBy('cavt.vehicle_type_id')
                                                ->groupBy('cavt.service_type_id')
                                                ->orderBy('vt.sequence')
                                                ->get();
                                                   
                            }
                                                
                        }
                         
                        $vehicleTypes = [];
                        $vehicleTypeName = [];
                        $count        = 1;
                        foreach ($vehicle_list as $vehicle) 
                        {
                            $vehicleTypeName[$vehicle->id] = $vehicle->vehicleTypeName;
                            $vehicleTypes[$count] = $vehicle->id;
                            $count++;                 
                        }
                   
                        \Log::channel('whatsapp_booking')->emergency($vehicleTypeName);
                if (array_key_exists($body, $vehicleTypes)) {
                   
                    $vehicleType = $vehicleTypeName[$vehicleTypes[$body]];
                    $vehicleTypeid = $vehicleTypes[$body];
                    $fromLocation = $sessionData['from_location'];
                    $toLocation = $sessionData['to_location'];
                    $fromLatLng = $sessionData['from_latlng'];
                    $toLatLng = $sessionData['to_latlng'];
                    $areaId = $sessionData['area_id'];
                
                    $request = array(
                        "area"            =>$areaId,
                        "latitude"        =>$fromLatLng['lat'], 
                        "longitude"       =>$fromLatLng['lng'],
                        "drop_lat"        =>$toLatLng['lat'],
                        "drop_long"       =>$toLatLng['lng'],
                        "service_type"    =>1,
                        "vehicle_type"    =>(int)$vehicleTypeid,
                        "merchant_id"     =>$user->merchant_id,
                        "segment_id"      =>1
                        );
                    
                    $price_data = PriceCard::where([['country_area_id', '=', $areaId], ['merchant_id', '=', $user->merchant_id], ['service_type_id', '=', 1], ['vehicle_type_id', '=', $request['vehicle_type']]])->first();
                    // Book the ride (you can add the actual booking logic here)
                    $driver_data = Driver :: GetNearestDriver($request);
                    if(empty($driver_data))
                    {
                        $data['msg'] = "🚗 Driver Not Found!\n\n"
                                     . "Oops! We couldn't find the driver details you were looking for. 😕\n"
                                     . "Don't worry, let's try again! 🙌\n\n"
                                     . "👉 Tip: Just reply with *Hi* to restart the process, and we'll help you out!\n\n"
                                     . "Looking forward to hearing from you! 😊";
                        $this->sendWhatsApp($data);
                    }
                    
                    $driverData = json_decode($driver_data);
                    
                     $request = array(
                        "manual_area"            =>$areaId,
                        "pickup_latitude" =>$fromLatLng['lat'], 
                        "pickup_longitude"=>$fromLatLng['lng'],
                        "drop_latitude"   =>$toLatLng['lat'],
                        "drop_longitude"  =>$toLatLng['lng'],
                        "service"         =>1,
                        "vehicle_type"    =>(int)$vehicleTypeid,
                        "merchant_id"     =>$user->merchant_id,
                        "segment_id"      =>1,
                        "user_id"         => $user->id,
                        "drop_location"   => $toLocation,
                        "pickup_location" => $fromLocation,
                        "booking_type"    => 1,
                        "payment_method_id" => 1,
                        "platform"     => 3,
                        );
                        
                    $booking = $this->placeManualDispatchBooking((object) $request, NULL, $user->merchant_id, $price_data->id, $driverData, $configuration->google_key);
                    $findDriver = new FindDriverController();
                    $findDriver->AssignRequest($driverData, $booking->id);
                    $message = "New Booking";
                    $bookingData = new BookingDataController();
                    $respo=$bookingData->SendNotificationToDrivers($booking, $driverData, $message);
                    $data['msg'] = "Hang tight! We're finding the perfect ride for you. It won’t be long now!";
                    $this->sendWhatsApp($data);
                    Sleep(15);
                    $book_data = Booking::find($booking->id);
                    $driver_name = $book_data->Driver->first_name.''.$book_data->Driver->last_name;
                    $otp         = !empty($book_data->ride_otp) ? $book_data->ride_otp : "";
        
                    if($book_data->booking_status == 1002)
                    {
                  
                        $data['msg'] = "🎉 Great news! Your booking has been confirmed! 🚖\n\n" .
                                   "🔹 **Booking ID**: {$book_data->id}\n" .
                                   "🔹 **Driver**: {$driver_name}\n" .
                                   "🔹 **OTP**: {$otp}\n\n" .
                                   "📍 **Pickup Location**: {$book_data->pickup_location}\n" .
                                   "🏁 **Drop-off Location**: {$book_data->drop_location}\n\n" .
                                   "📡 Track your driver in real-time here: " . route('ride.share', $book_data->unique_id) . "\n\n" .
                                   "Thank you for riding with us! 😊 We're here if you need anything along the way.";

                       
                        $this->sendWhatsApp($data);


                    }
                    elseif($book_data->booking_status == 1007 || $book_data->booking_status == 1016 || $book_data->booking_status == 1001)
                    {
                        
                        $data['msg'] = "We’re sorry, but we couldn't find a ride for you at the moment. Please try again in a few minutes, or adjust your search to find the best match!";
                        $this->sendWhatsApp($data);
                    }
                    // Clear session data after booking
                    $this->clearSessionData($fromPhoneNumber);
                } else {
                    
                     if(in_array('TAXI',$merchant_segment_list)){
                        if(count($user->Merchant->VehicleType) > 0){
                            $vehicle_list = $user->Merchant->VehicleType;
                           
                        }
                        return [];
                    }
                    $vehicleTypes = [];
                    foreach ($vehicle_list as $vehicle) {
                        $vehicleTypes[$vehicle->id] = $vehicle->VehicleTypeName;
                    }
                    $message = "Please select your vehicle type by replying with the corresponding number:\n";

                    foreach ($vehicleTypes as $id => $name) {
                        $message .= "$id. $name\n";
                    }

                    $data['msg'] = $message;
                    $this->sendWhatsApp($data);
                }
                break;

            default:
                $data['msg'] = "We didn't receive any message from you. Please send a command or type 'help' for assistance.";
                $this->sendWhatsApp($data);
                break;
        }
        }catch(Exception $e)
        {
            \Log::channel('whatsapp_booking')->emergency($e);
        }
    }

    public function sendWhatsApp($request)
    {
        \Log::channel('whatsapp_booking')->emergency("hii");
       \Log::channel('whatsapp_booking')->emergency($request);
        $twilio =   new Client($request['account_id'],$request['auth_token']);
        $whatsappNumber = $request['from']; // This can be dynamically set or retrieved from a configuration
        $from = "whatsapp:$whatsappNumber";
        
            if(!isset($request['msg']))
            {
            // Send a message using the Twilio API
            $twilioParams = [
                        'from' => $from,
                        'contentSid' => $request['templateSid'] // The SID of the template
                    ];
                    
                    // Include contentVariables only if it's provided
                    if (!empty($request['param'])) {
                        \Log::channel('whatsapp_booking')->emergency([$request['param'],json_encode($request['param'])]);
                        $twilioParams['contentVariables'] = json_encode($request['param']); // JSON-encoded parameters
                    }
                    
                    $twilio->messages->create(
                        $request['WaId'], // Recipient
                        $twilioParams
                    );
            }else{
                    
            $twilio->messages->create(
                              $request['WaId'],
                            
                            [
                                "from" => $from,
                                "body" => $request['msg'],
                            ]
                        ); 
            }
            
             
    }
    
    public function VerifyUser($phone){
        $user = User::where('UserPhone',"+" . $phone->WaId)->where('user_delete',NULL)->first();
        return $user;
    }
    
    public function createAccount($request){
        
        $number = preg_replace('/^whatsapp:/', '', $request['request']['To']);
        $merchantWhatsApp = DB::table('merchant_whatsapps')->where('from', $number)->first();
        if(!empty($merchantWhatsApp))
        {
            \Log::channel('whatsapp_booking')->emergency("hii");
            $user_name      =   explode(' ', $request['name']);
            if(isset($user_name[0])){
                    $user_data['first_name']    =   $user_name[0];
                    if(isset($user_name[1])){
                        $user_data['last_name'] =   $user_name[1];
                    }
                    $country_code               =   Country::where('merchant_id',$merchantWhatsApp->merchant_id)->first();
                    if($country_code){
                        $user_data['country_id']    =   $country_code->id;
                    }
                    $user_data['UserSignupFrom']    =   4;
                    $user_data['UserPhone']         =   '+'.$request['request']['WaId'];
                    $user_data['merchant_id']       =   $merchantWhatsApp->merchant_id;
                    $user_data['password']          =   Hash::make($request['request']['WaId']);
                    $user                           =   User::create($user_data);
                    \Log::channel('whatsapp_booking')->emergency($user_data);
                    return $user_data['first_name'];
                    
                }else{
                    return false;
                }
        }
        else
        {
            return false;
        }
        // $messageData    =  [];
        // if(!isset($data['Body'])){
        //     return   __("$string_file.user_account_not_found");
        // }else{
        //     if(strpos($data['Body'],'Name') !== false){
        //         $user_name      =   explode(' ', $data['Body']);
        //         if(isset($user_name[1])){
        //             $user_data['first_name']    =   $user_name[1];
        //             if(isset($user_name[2])){
        //                 $user_data['last_name'] =   $user_name[2];
        //             }
        //             $country_code               =   Country::where('phonecode',$code)->where('merchant_id',$data['merchant_id'])->first();
        //             if(empty($country_code))
        //             {
        //                 return   __("$string_file.country").' '.__("$string_file.data_not_found");
        //             }
        //             if($country_code){
        //                 $user_data['country_id']    =   $country_code->id;
        //             }
        //             $user_data['UserSignupFrom']    =   4;
        //             $user_data['UserPhone']         =   '+'.$code.$phone;
        //             $user_data['merchant_id']       =   $country_code->merchant_id;
        //             $user_data['password']          =   Hash::make($phone);
        //             $user                           =   User::create($user_data);
        //             return $this->CreateBooking($data,$user,$string_file);
        //         }else{
        //             return   __("$string_file.user_account_not_found");
        //         }
        //     }else{
        //         return __("$string_file.user_account_not_found");
        //     }
        // }
        // if(isset($messageData['Body'])){
        //     $this->sendWhatsApp($data['From'], $messageData);
        // }
        // return true;
    }

    private function getSessionData($fromPhoneNumber)
    {
        return Cache::get($fromPhoneNumber);
    }
    
    private function updateSessionData($fromPhoneNumber, $data)
    {
        Cache::put($fromPhoneNumber, array_merge(Cache::get($fromPhoneNumber, []), $data), 3600); // Store for 1 hour
    }
    
    private function clearSessionData($fromPhoneNumber)
    {
        Cache::forget($fromPhoneNumber);
    }
}
