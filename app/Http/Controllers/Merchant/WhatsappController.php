<?php

namespace App\Http\Controllers\Merchant;

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

class WhatsappController extends Controller
{
    use ManualDispatchTrait;
    public $sid,$token,$from;
    use MerchantTrait,BookingTrait,AreaTrait;
//    function __construct()
//    {
//        $this->sid      = env('TWILIO_AUTH_SID');
//        $this->token    = env('TWILIO_AUTH_TOKEN');
//        $this->from     = env('TWILIO_WHATSAPP_FROM');
//    }

    function setParam($request = NULL , $merchant_id = NULL)
    {
        if(!empty($request))
        {
            $this->sid      = $request->AccountSid;
            $merchant_whatsapp = MerchantWhatsApp::where('sid',$request->AccountSid)->first();
            $this->token    = $merchant_whatsapp->token;
            $this->from     = $merchant_whatsapp->from;
            $request->request->add(['merchant_id'=>$merchant_whatsapp->merchant_id]);
            return $request;
        }
    }

    // public function newMessage(Request $request){

    //     // Log::alert('new message');
    //     // Log::alert($request->all());


    //     $log_data = array(
    //             'request_type' => "whatsapp_new_message",
    //             'request_data' => $request->all(),
    //             'additional_notes' => "",
    //             'hit_time' => date('Y-m-d H:i:s')
    //         );
    //         \Log::channel('whatsapp_booking')->emergency($log_data);

    //     $data               =   [];
    //     $message_from       =   mobileNumber(explode(':', $request->From)[1]);
    //     $from_country_code  =   $message_from->getCountryCode();
    //     $from_phone         =   $message_from->getNationalNumber();
    //     $data['from']       =   $request->to;
    //     $messageData = [];
    //     if(!Country::where('phone_code', $from_country_code)->exists()){
    //         $messageData['Body']   =  __('common.service_not_available');
    //         return response("<Response><Message>".$messageData['Body']."</Message></Response>");
    //     }
    //     $check_number       =   $this->VerifyUser($from_country_code,$from_phone);
    //     if(!$check_number){
    //         $message    =   $this->createAccount($request->all(), $from_country_code, $from_phone);
    //     }else{
    //         if($check_number->block_for_whatsapp == '1'){
    //             return response("<Response></Response>");
    //         }
    //         if(UserType::where('user_id',$check_number->id)->count()){
    //             return response("<Response><Message>Your last login found in driver application so please login with cablinks application for the cab.</Message></Response>");
    //         }
    //         $message    =   $this->CreateBooking($request->all(),$check_number);
    //     }
    //     if($message == ''){
    //         return response("<Response>Error</Response>");
    //     }

    //     return response("<Response><Message>".$message."</Message></Response>");
    // }

    public function newMessage(Request $request)
    {
        $this->setParam($request);
        // return response("<Response><Message>HiJJ</Message></Response>");
        DB::beginTransaction();
        try {
            $log_data = array(
                'request_type' => "whatsapp_new_message",
                'request_data' => $request->all(),
                'hit_time' => date('Y-m-d H:i:s')
            );
            \Log::channel('whatsapp_booking')->emergency($log_data);
            $data               =   [];
            $merchant_id = $request->merchant_id;
            $string_file  = $this->getStringFile($merchant_id);
            $messageData = [];
            $message_from       =   mobileNumber(explode(':', $request->From)[1]);
            $from_country_code  =   $message_from->getCountryCode();
            $from_phone         =   $message_from->getNationalNumber();
            $data['from']       =   $request->to;
            if(!Country::where('phonecode', '+'.$from_country_code)->where('merchant_id',$merchant_id)->exists()){
//                return response("<Response><Message>ji</Message></Response>");
                $messageData['Body']   =  __("$string_file.service_not_available");
                return response("<Response><Message>".$messageData['Body']."</Message></Response>");
            }

            $phone_number = explode(':', $request->From)[1];
            $check_number =   $this->VerifyUser($phone_number);

            if(!$check_number){
                $message    =   $this->createAccount($request->all(), $from_country_code, $from_phone,$string_file);
            }else{
                // return response("<Response><Message>Na</Message></Response>");
                $message    =   $this->CreateBooking($request->all(),$check_number,$string_file);
//                // p($message);
//                return response("<Response><Message>".$message."</Message></Response>");
            }
//            if($message == ''){
//                return response("<Response><Message>Data Not found </Message></Response>");
//            }

        } catch (\Exception $e) {
            DB::rollback();
            return response("<Response><Message>".$e->getMessage()."</Message></Response>");
        }
        DB::commit();
        return response("<Response><Message>".$message."</Message></Response>");
    }

    public function messageStatus(Request $request)
    {
        try{
        
        $user = $this->verifyUser($request);
        $number = preg_replace('/^whatsapp:/', '', $request->To);
        $merchant = MerchantWhatsApp::where('from', $number)->where('is_active', 1)->first();
        if(empty($merchant))
        {
            \Log::channel('whatsapp_booking')->emergency("merchant is not active for whatsapp booking");
            return "merchant is not active for whatsapp booking";
        }
        if(!empty($user))
        {
            $configuration = BookingConfiguration::where('merchant_id', $merchant->merchant_id)->first();
             \Log::channel('whatsapp_booking')->emergency($configuration);
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
                    // $user = $this->verifyUser($request);
                    \Log::channel('whatsapp_booking')->emergency($merchant->merchant_id);
                    \Log::channel('whatsapp_booking')->emergency($user->Merchant);
                    if ($merchant->merchant_id == $user->Merchant->id) {
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
                    \Log::channel('whatsapp_booking')->emergency($driver_data);
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
                         \Log::channel('whatsapp_booking')->emergency($request);
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
                    $is_otp      = $user->Merchant->BookingConfiguration->ride_otp;
                         \Log::channel('whatsapp_booking')->emergency($is_otp);
                    if($book_data->booking_status == 1002)
                    {
                    
                        $message = "🎉 Great news! Your booking has been confirmed! 🚖\n\n";
                        $message .= "🔹 **Booking ID**: {$book_data->id}\n";
                        $message .= "🔹 **Driver**: {$driver_name}\n";
                        // Show OTP only if configured
                        if ($is_otp == 1) {
                            $message .= "🔹 **OTP**: {$otp}\n";
                        }
                        $message .= "\n";
                        $message .= "📍 **Pickup Location**: {$book_data->pickup_location}\n";
                        $message .= "🏁 **Drop-off Location**: {$book_data->drop_location}\n\n";
                        $message .= "📡 Track your driver in real-time here: " . route('ride.share', $book_data->unique_id) . "\n\n";
                        $message .= "Thank you for riding with us! 😊 We're here if you need anything along the way.";  
                        $data['msg'] = $message;
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
    
    public function CreateBooking($data,$user,$string_file = ""){

        $data['latitude'] = isset($data['Latitude']) ? $data['Latitude'] : "";
        $data['longitude'] = isset($data['Latitude']) ? $data['Latitude'] : "";
        try {
            $booking = BookingCheckout::where('user_id',$user->id)->where('merchant_id',$user->merchant_id)
                ->first();

            // p($booking);
            $key = $user->Merchant->BookingConfiguration->google_key;
            $google_con = new GoogleController;
            if(!empty($booking->id))
            {
                $area = CountryArea::whereHas('VehicleType',function ($q) use($booking) {
                    $q->where('country_area_id',$booking->country_area_id);
                    $q->where('segment_id',$booking->segment_id);
                })->where([['id','=',$booking->country_area_id]])
                    ->first();
                //   p($area);
                $vehicle_type = $area->VehicleType->unique();
                // return $vehicle_type;
                if(empty($area))
                {
                    return trans("$string_file.configuration_not_found");
                }
                $arr_vehicle_type = "";
                // Body has delete tag
                //
                if(isset($data['Body']) && trim(strtolower($data['Body'])) == 'delete')
                {
                    $booking->delete();
                    return trans("$string_file.checkout_deleted");
                }
                if(empty($booking->drop_location))
                {
                    if(isset($data['Latitude']) && !empty($data['Latitude']))
                    {
                        $drop_location = $google_con->GoogleLocation($data['Latitude'], $data['Longitude'], $key,$calling_from ='',$string_file);
                        $booking->drop_location  =   $drop_location;
                        $booking->drop_latitude  =   $data['Latitude'];
                        $booking->drop_longitude =   $data['Longitude'];
                        $booking->save();
                        $arr_vehicle_type = "";
                        foreach ($vehicle_type as $vehicle)
                        {
                            if(!empty($vehicle->id))
                            {
                                $name = !empty($vehicle->VehicleTypeName) ? $vehicle->VehicleTypeName : "";
                                // return $vehicle->VehicleTypeName;
                                $sep = ' ';
                                $arr_vehicle_type = $arr_vehicle_type.' '.$vehicle->id.' for '.$name.$sep;
                            }
                        }
                        // return $arr_vehicle_type;
                        $message = __("$string_file.please_select_vehicle_type");
                        $message = $message.$sep.$arr_vehicle_type;
                        return    $message;
                    }
                    else
                    {
                        return    __("$string_file.destination_required");
                    }
                }
                elseif(empty($booking->vehicle_type_id) && ! in_array($data['Body'],array_pluck($vehicle_type,'id')))
                {
                    foreach ($vehicle_type as $vehicle)
                    {
                        $arr_vehicle_type = $arr_vehicle_type.' '.$vehicle->id.' for '.$vehicle->VehicleTypeName . " ";
                    }
                    $message = __("$string_file.please_select_vehicle_type");
                    return    $message. " ".$arr_vehicle_type;
                }
                elseif(isset($data['Body']))
                {
                    if((empty($booking->vehicle_type_id) && in_array($data['Body'],array_pluck($vehicle_type,'id'))) || (!empty($booking->vehicle_type_id) && trim(strtolower($data['Body'])) == 'retry'))
                    {
                        // check estimate distance
                        // check driver availability
                        // then create booking
                        if(in_array($data['Body'],array_pluck($vehicle_type,'id')))
                        {
                            $booking->vehicle_type_id = $data['Body'];
                            $booking->save();
                        }
                        //
                        $pricecards = PriceCard::where([['status', '=', 1], ['country_area_id', '=', $booking->country_area_id], ['merchant_id', '=', $booking->merchant_id], ['service_type_id', '=', $booking->service_type_id], ['vehicle_type_id', '=', $booking->vehicle_type_id]])->first();
                        $configuration = BookingConfiguration::where([['merchant_id', '=', $booking->merchant_id]])->first();

                        if (!empty($configuration->driver_ride_radius_request)) {
                            $remain_ride_radius_slot = json_decode($configuration->driver_ride_radius_request, true);
                        } else {
                            $remain_ride_radius_slot = array();
                        }
                        $req_parameter = [
                            'area' => $booking->country_area_id,
                            'segment_id' => $booking->segment_id,
                            'latitude' => $booking->pickup_latitude,
                            'longitude' => $booking->pickup_longitude,
                            'distance' => isset($remain_ride_radius_slot[0]) ? $remain_ride_radius_slot[0] : 10000,
                            'limit' => $configuration->normal_ride_now_request_driver,
                            'service_type' => $booking->service_type_id,
                            'vehicle_type' => $booking->vehicle_type_id,
                            'drop_lat' => $booking->pickup_latitude,
                            'drop_long' => $booking->drop_longitude
                        ];
                        // p($req_parameter);
                        $drivers = Driver::GetNearestDriver($req_parameter);
                        // p($drivers);
                        if (empty($drivers) || $drivers->count() == 0) {
                            return trans("$string_file.no_driver_found");
                        }

                        $from = $booking->pickup_latitude . "," . $booking->pickup_longitude;
                        $current_latitude = !empty($drivers[0]) ? $drivers[0]->current_latitude : '';
                        $current_longitude = !empty($drivers[0]) ? $drivers[0]->current_longitude : '';
                        $driverLatLong = $current_latitude . "," . $current_longitude;
                        $units = (CountryArea::find($booking->country_area_id)->Country['distance_unit'] == 1) ? 'metric' : 'imperial';
                        $nearDriver = DistanceController::DistanceAndTime($from, $driverLatLong, $configuration->google_key, $units);
                        $estimate_driver_distance = $nearDriver['distance'];
                        $estimate_driver_time = $nearDriver['time'];
                        $drop_locationArray[0] = [
                            'drop_location'=>$booking->drop_location,
                            'drop_latitude'=>$booking->drop_latitude,
                            'drop_longitude'=>$booking->drop_longitude,
                        ];
                        $googleArray = GoogleController::GoogleStaticImageAndDistance($booking->pickup_latitude, $booking->pickup_longitude, $drop_locationArray, $configuration->google_key, $units,"",$string_file);

                        $to = "";
                        $time = $googleArray['total_time_text'];
                        $timeSmall = $googleArray['total_time_minutes'];
                        $distance = $googleArray['total_distance_text'];
                        $distanceSmall = $googleArray['total_distance'];
                        $image = $googleArray['image'];
                        $bill_details = "";
                        $merchant = new Merchant();
                        switch ($pricecards->pricing_type) {
                            case "1":
                            case "2":
                                $estimatePrice = new PriceController();
                                $fare = $estimatePrice->BillAmount([
                                    'price_card_id' => $pricecards->id,
                                    'merchant_id' => $booking->merchant_id,
                                    'distance' => $distanceSmall,
                                    'time' => $timeSmall,
                                    'booking_id' => 0,
                                    'user_id' => $booking->user_id,
                                    'booking_time' => date('H:i'),
                                    'units' => $units,
                                    'from' => $from,
                                    'to' => $to,
                                ]);
                                $amount = $merchant->FinalAmountCal($fare['amount'], $booking->merchant_id);
                                $bill_details = json_encode($fare['bill_details']);
                                break;
                            case "3":
                                // @Bhuvanesh
                                // In case of Input by driver, all parameters amount will be 0, and will be calculate at the end of booking. - booking_close api.
                                $estimatePrice = new PriceController();
                                $fare = $estimatePrice->BillAmount([
                                    'price_card_id' => $pricecards->id,
                                    'merchant_id' => $booking->merchant_id,
                                    'distance' => $distanceSmall,
                                    'time' => $timeSmall,
                                    'booking_id' => 0,
                                    'user_id' => $booking->user_id,
                                    'booking_time' => date('H:i'),
                                    'units' => $units,
                                    'from' => $from,
                                    'to' => $to,
                                ]);
                                $amount = trans('api.message62');
                                $bill_details = json_encode($fare['bill_details']);
                                break;
                        }
                        $booking->bill_details = $bill_details;
                        $booking->estimate_driver_distance = $estimate_driver_distance;
                        $booking->estimate_driver_time = $estimate_driver_time;
                        $booking->estimate_distance = $distance;
                        $booking->estimate_time = $time;
                        $booking->estimate_bill = $amount;
                        $booking->save();

                        $new_status = [
                            'booking_status'=>$booking->booking_status,
                            'booking_timestamp'=>time(),
                            'latitude'=>$booking->pickup_latitude,
                            'longitude'=>$booking->pickup_longitude,
                        ];

                        $booking_data = new Booking;
                        $booking_data->booking_type = 1;
                        $booking_data->segment_id = $booking->segment_id;
                        $booking_data->merchant_id = $booking->merchant_id;
                        $booking_data->service_type_id = $booking->service_type_id;
                        $booking_data->vehicle_type_id = $booking->vehicle_type_id;
                        $booking_data->bill_details = $bill_details;
                        $booking_data->estimate_driver_distance = $estimate_driver_distance;
                        $booking_data->estimate_driver_time = $estimate_driver_time;
                        $booking_data->estimate_distance = $distance;
                        $booking_data->estimate_time = $time;
                        $booking_data->estimate_bill = $amount;
                        $booking_data->country_area_id = $booking->country_area_id;
                        $booking_data->payment_method_id = $booking->payment_method_id;
                        $booking_data->booking_status = 1001;
                        $booking_data->booking_status_history = json_encode([$new_status]);
                        $booking_data->user_id = $booking->user_id;
                        $booking_data->pickup_latitude = $booking->pickup_latitude;
                        $booking_data->pickup_longitude = $booking->pickup_longitude;
                        $booking_data->pickup_location = $booking->pickup_location;
                        $booking_data->drop_latitude = $booking->drop_latitude;
                        $booking_data->drop_longitude = $booking->drop_longitude;
                        $booking_data->drop_location = $booking->drop_location;
                        $booking_data->booking_timestamp = time();
                        $booking_data->price_card_id = $pricecards->id;
                        $booking_data->platform = 3;
                        $booking_data->save();


                        // $data->request->add(['latitude'=>$booking_data->pickup_latitude,'longitude'=>$booking_data->pickup_longitude]);
                        $this->saveBookingStatusHistory((object)$data,$booking_data, NULL);

                        $findDriver = new FindDriverController();
                        $findDriver->AssignRequest($drivers, $booking_data->id);
                        $bookingData = new BookingDataController();
                        $bookingData->SendNotificationToDrivers($booking_data, $drivers);
                        // p('hoo');
                        // delete booking checkout
                        $booking->delete();
                        return trans("$string_file.ride_confirming");
                    }
                    elseif(trim(strtolower($data['Body'])) == 'cancel')
                    {
                        $ongoing_booking = Booking::select('id','merchant_booking_id','user_id','driver_id','merchant_id','booking_status','booking_status_history','segment_id','vehicle_type_id','country_area_id','payment_method_id')->where('user_id',$user->id)->whereIn('booking_status',[1001,1002,1003,1004])->first();
                        if(!empty($ongoing_booking->id) && ($ongoing_booking->booking_status == 1001 || $ongoing_booking->booking_status == 1002))
                        {
                            $ongoing_booking->booking_status = 1016; // cancelled by user
                            $ongoing_booking->save();
                            $message = trans("$string_file.ride_id").' #'.$booking->merchant_booking_id.'. '.trans("$string_file.ride_cancelled");
//                            $this->sendWhatsApp($ongoing_booking->User->UserPhone,$message);
                            return $message;
                        }
//                        elseif(!empty($ongoing_booking->id) && ($ongoing_booking->booking_status == 1003 || $ongoing_booking->booking_status == 1004))
//                        {
//                            $message = trans("$string_file.ride_cant_cancelled");
//                            return $message;
//                        }
                        //     foreach ($vehicle_type as $vehicle)
                        //     {
                        //         $arr_vehicle_type = $arr_vehicle_type.' '.$vehicle->id.' for '.$vehicle->VehicleTypeName.'';
                        //     }
                        //     $message = __('common.please_select_vehicle_type');
                        //     return    $message.' '.$arr_vehicle_type;
//                        return    trans("$string_file.ride_confirming");

                    }
                    elseif(!empty($booking->drop_location) && !empty($booking->pickup_location) && !empty($booking->vehicle_type_id))
                    {
                        return trans("$string_file.checkout_exist_retry_to_book");
                    }
                    else
                    {
                        $ongoing_booking = Booking::where('user_id',$user->id)->whereIn('booking_status',[1001,1002,1003,1004,1005])->where('booking_closure','!=',1)->first();
                        if(!empty($ongoing_booking->id))
                        {
                            return trans("$string_file.user_running_ride");
                        }
                    }
                }
                elseif(!isset($data['Body']) && !empty($booking->drop_location) && !empty($booking->pickup_location) && !empty($booking->vehicle_type_id))
                {
                    return trans("$string_file.checkout_exist_retry_to_book");
                }
            }
            else
            {
                $ongoing_booking = Booking::select('id','booking_status','driver_id','user_id','merchant_id','booking_status_history','segment_id','vehicle_type_id','country_area_id','payment_method_id')->where('user_id',$user->id)->whereIn('booking_status',[1001,1002,1003,1004])->first();
                if(!empty($ongoing_booking->id))
                {
                    if(trim(strtolower($data['Body'])) == 'cancel')
                    {
                        if(!empty($ongoing_booking->driver_id) && $ongoing_booking->booking_status != 1001)
                        {
                            $driver = $ongoing_booking->Driver;
                            $driver->free_busy = 2; // make free
                            $driver->save();

                            $ongoing_booking->booking_status = 1006;
                            $ongoing_booking->save();

                            $this->saveBookingStatusHistory((object)$data,$ongoing_booking, NULL);
                            // cancelled ride by user
                            $bookingData = new BookingDataController();
                            $bookingData->SendNotificationToDrivers($ongoing_booking, $drivers = []);

                        }
                        if($ongoing_booking->booking_status == 1001)
                        {
                            $cancelDriver = BookingRequestDriver::with(['Driver' => function ($q) {
                                $q->addSelect('id', 'last_ride_request_timestamp', 'id as driver_id');
                            }])->where([['booking_id', '=', $ongoing_booking->id]])->get();
                            $cancelDriver = array_pluck($cancelDriver, 'Driver');
                            foreach ($cancelDriver as $key => $value) {
                                $value->last_ride_request_timestamp = date("Y-m-d H:i:s", time() - 100);
                                $value->save();
                            }
                            $ongoing_booking->booking_status = 1016;
                            $ongoing_booking->save();
                            $this->saveBookingStatusHistory((object)$data, $ongoing_booking, $ongoing_booking->id);
                            $bookingData = new BookingDataController();
                            // send notification to close driver screen
                            $bookingData->SendNotificationToDrivers($ongoing_booking, $cancelDriver);
                        }
                        return trans("$string_file.ride_cancelled");
                    }
                    elseif($ongoing_booking->booking_status == 1001)
                    {
                        return trans("$string_file.ride_confirming");
                    }
                    else
                    {
                        return trans("$string_file.user_running_ride");
                    }
                }
                if(isset($data['Latitude']) && !empty($data['Latitude']))
                {
                    $pickup_location = $google_con->GoogleLocation($data['Latitude'], $data['Longitude'], $key, $calling_from = '',$string_file);
                    $area = PolygenController::Area($data['Latitude'], $data['Longitude'], $user->merchant_id);
                    if(empty($area))
                    {
                        return    __('common.no_service_area');
                    }
                    $area_id = $area['id'];
                    $bookingData = [
                        'pickup_location' => $pickup_location,
                        'pickup_latitude' => $data['Latitude'],
                        'pickup_longitude' => $data['Longitude'],
                        'user_id' => $user->id,
                        'payment_method_id' => 1,
                        'service_type_id' => 1,
                        'segment_id' => 1,
                        'merchant_id' => $user->merchant_id,
                        'country_area_id' => $area_id,
                    ];
                    // create checkout
                    BookingCheckout::create($bookingData);
                    return    __("$string_file.destination_required");
                }
                else
                {
                    return    __("$string_file.pickup_required");
                }
            }
        }catch (Exception $e)
        {

            return $e->getMessage();
        }



        /******* Old code ************/
//         $booking                    =   Booking::with('driver.user')
//             ->where('customer_id', $user->id)
//             ->whereNotIn('booking_status', ["cancelledByRider", "cancelledByDriver","Completed"])
//             ->where('destination_address','!=','1')->first();
//         if(!$booking && isset($data['Latitude'])){
//                 $messageData['From']    =   $data['To'];
//                 $booking                =   Booking::where('customer_id',$user->id)->where('destination_address','1')->where('booking_status','Pending')->where('whatsapp','1')->where('created_at','>',Carbon::now()->subMinute(2)->toDateTimeString())->first();
//                 if($booking){
//                     $locate             =   googleLocation($data['Latitude'], $data['Longitude']);
//                     $booking->destination_address   =   $locate[0];
//                     $booking->destination_latitude  =   $data['Latitude'];
//                     $booking->destination_longitude =   $data['Longitude'];
//                     $drivers            =   $this->driverlist($booking->source_latitude, $booking->source_longitude);
//                     if(count($drivers) == 0){
//                         $booking->delete();
//                         return    __('common.no_driver_found');
//                     }

//                     $googleDistance     =   googleDistance($booking->source_latitude, $booking->source_longitude,$booking->destination_latitude, $booking->destination_longitude);
//                     $distance           =   $googleDistance->distance->value? $googleDistance->distance->value/1000:1/1000;
//                     $time               =   $googleDistance->duration->value? $googleDistance->duration->value/60:1/60;
//                     $standard           =   '';
//                     $premium            =   '';
//                     $cartype            =   getCarType($locate[1]);
//                     $distance_charge    =   0;
//                     $time_charge        =   0;
//                     $total_charge       =   0;

//                     foreach($cartype as $rates){
//                         $distance_charge    =   (float)$rates['per_km_price'] * (float) $distance;
//                         $time_charge        =   (float) $rates['timecharge_per_minute'] * (float) $time;
//                         $total_charge       =   $distance_charge + $time_charge + $rates['service_charge'] + (float)$rates['base_fare'];
//                         $transaction_charge =   ($total_charge * (float)$rates['transaction_fee'])/100;
//                         $total_charge       =   $total_charge + $transaction_charge;
//                         $rates['booking_id']    =   $booking->id;
//                         $rates['distance_charge']   =   $distance_charge;
//                         $rates['time_charge']       =   $time_charge;
//                         $rates['total_charge']      =   $total_charge;
//                         if($rates['cartype_id'] == 1){
//                             $premium        =   number_format($total_charge,2);
//                             $rates['cartype_id']        =   '1';
//                             TempVehicleType::create($rates);
//                         }elseif($rates['cartype_id'] == 2){
//                             $standard       =   number_format($total_charge,2);
//                             $rates['cartype_id']    =   '2';
//                             TempVehicleType::create($rates);
//                         }
//                     }
//                     $Currency    =   Country::with('currency')->where('iso', $locate[2])->first();
//                     $Currency    =  $Currency->currency->symbol;
//                     Log::info([$premium,$standard]);
//                     $messageData['Body']=   'Please select the vehicle type by sending.
// 1 for Premium (Estimated Fare :'. $Currency.$premium. ')
// 2 for standard (Estimated Fare :' . $Currency.$standard.')';
//                     $booking->save();
//                     return $messageData['Body'];
//                 }else{
//                     $bookingData    =   [
//                         'source_address'        =>  googleLocation($data['Latitude'], $data['Longitude'])[0],
//                         'source_latitude'       =>  $data['Latitude'],
//                         'source_longitude'      =>  $data['Longitude'],
//                         'customer_id'           =>  $user->id,
//                         'destination_address'   =>  '1',
//                         'payment_type'          =>  1,
//                         'whatsapp'              =>  1,
//                         'booking_status'        =>  'Pending',
//                         'reference_id'          =>  strrev(time()),
//                     ];
//                     Booking::create($bookingData);
//                     return __('common.send_destination');
//                 }
//             }else{
//                 if($data['Body']){

//                     $message    =   trim($data['Body']);

//                     if(strtolower($message) == 'cancel' && $booking && $booking->booking_status == "ontheway"){
//                         if($booking->driver_id){
//                             $booking->booking_status    =   'cancelledByRider';
//                             $booking->status            =   2;
//                             $booking->save();
//                             Driver::where('id', $booking->driver_id)->update(['is_active' => 1]);
//                             Pusher::trigger('private-Driver_' . $booking->driver_id, 'RideCancel', 'Ride Canceled.');
//                             $notification_message   =   notificationmessage('cancel_ride', $user->first_name . ' ' . $user->last_name);
//                             $data                   =   ['key' => 'user_profile', 'type' => 'cancel', 'username' => $user->name];
//                             WebPushHelpers::sendWebPush($booking->driver->user_id, $notification_message,'',$data,'bookings_push');
//                             $last_booking   =   Booking::where('customer_id',$booking->customer_id)->has('driver')->where('booking_status','cancelledByRider')->where('created_at','>',Carbon::now()->subHour(1)->toDateTimeString())->count();
//                             if($last_booking >= 2){
//                                 $user->block_for_whatsapp   =   '1';
//                                 $user->save();
//                                 return __('common.ride_cancel')."

// ".__('common.user_blocked');
//                             }
//                             return 'Ride cancelled successfully';
//                         }
//                     }elseif($booking && $booking->driver_id && $booking->booking_status == 'ontheway' ){
//                         return 'Driver is on the way to pick you.';
//                     }
//                     elseif($booking && $booking->driver_id && $booking->booking_status  !=  "ontheway"){
//                         return '';
//                     }
//                     if(strlen($message) == 1 && $booking){
//                         if((int) $message == 1){
//                             $cartype_id = 1;
//                         }
//                         elseif((int) $message == 2 && $booking){
//                             $cartype_id = 2;
//                         }else{
//                             if($booking){
//                                 return 'Please Reply with 1 for premium and 2 for standard cab.';
//                             }else{

//                             }
//                         }
//                         $cartype            =   TempVehicleType::whereBookingId($booking->id)->whereCartypeId($cartype_id)->first();
//                         if(!$cartype){
//                             return __('common.chull_machi_to');
//                         }
//                         $distance_charge    =   0;
//                         $time_charge        =   0;
//                         $total_charge       =   0;
//                         $drivers            =   $this->driverlist($booking->source_latitude, $booking->source_longitude, $cartype_id);
//                         if (count($drivers) == 0) {
//                             $booking->delete();
//                             return __('common.no_driver_found');
//                         }
//                         $this->sendBookingToDriver($booking,$cartype,$user,$drivers[$cartype_id], $cartype['total_charge']);
//                         TempVehicleType::where('booking_id',$booking->id)->delete();
//                         return __('common.waiting_for_accept');
//                     }else{
//                         if ($booking) {
//                             return  __('common.waiting_for_accept');
//                         }
//                     }
//                 }
//                 if ($booking) {
//                     return  __('common.waiting_for_accept');
//                 }

//                 $messageData['Body']    =   'Hello '. $user->first_name.', Please send your source location for booking cab for you.';
//             }
//         return  $messageData['Body'];
    }

    public function driverlist($lat,$lng, $car_type=0)
    {
        $latitude       =   $lat;
        $longitude      =   $lng;
        $cartype_id     =   $car_type;
        // Log::info([$lat,$lng,$cartype_id]);
        $miles = drivercatch() ?? 10;
        $query = "SELECT *,d.id as driver_id , (3956 * 2 * ASIN(SQRT( POWER(SIN(( " . $latitude . " - `latitude`) *  pi()/180 / 2), 2) +COS( " . $latitude . " * pi()/180) * COS(`latitude` * pi()/180) * POWER(SIN(( " . $longitude . " - `longitude`) * pi()/180 / 2), 2) ))) as distance  from " . DB::getTablePrefix() . "users u JOIN " . DB::getTablePrefix() . "user_types ut on ut.user_id = u.id JOIN " . DB::getTablePrefix() . "drivers d on d.user_id = u.id JOIN " . DB::getTablePrefix() . "vehicle_histories vh on vh.user_id = u.id where user_type = 3 and is_approved = 1 and is_active = 1 and is_available = 1 ";
        if($cartype_id){
            $query  =   $query."and cartype_id = '".$cartype_id."' " ;
        }
        $query      =   $query." having  distance <= " . $miles . " order by distance, Rand()";
        Log::info($query);
        $results    =   collect(DB::select(DB::raw($query)));
        return $results->groupBy('cartype_id')->toArray();
    }

    public function sendBookingToDriver($booking,$cartype,$user,$drivers, $total_charge=''){
        $booking->base_fare             =   $cartype['base_fare'];
        $booking->service_charge        =   $cartype['service_charge'];
        $booking->transaction_fee       =   $cartype['transaction_fee'];
        $booking->per_km_charges        =   $cartype['per_km_price'];
        $booking->timecharge_per_minute =   $cartype['timecharge_per_minute'];
        if($total_charge != ''){
            $booking->estimated_fare    =   $total_charge;
        }
        $booking->minute_charges        =   $cartype['price_per_minute'];
        $booking->save();
        $driverobj                      =   new Driver();
        $onlinedriver                   =   $driverobj->driveronlinecheck(1);
        $counter    =   0;
        Log::info($drivers);
        foreach ($drivers as $driver) {
            $user           =   User::with('totalride')->findorfail($user->id);
            $drvr           =   Driver::where('id', $driver->driver_id)->first();
            $driver_choice  =   DriverChoice::where('user_id', $driver->user_id)->whereStatus(true)->first();
            if ($driver_choice) {
                $destination_distance   =   $this->getMinimumDistance($booking->destination_latitude, $booking->destination_longitude, json_decode($driver_choice->route));
                if ($destination_distance > env('DRIVER_CHOICE_MIN_DISTANCE', 500)) {
                    continue;
                }
            }
            if (!in_array($driver->driver_id, $onlinedriver)) {
                $new_notification       =   new DriverRideInfo($user, $booking, $driver->driver_id, $user->totalride->count());
                WebPushHelpers::sendWebPush($drvr->user_id, '', '', (array) $new_notification->getnotificationdata(), 'bookings_push');
                Log::alert('notification send');
            }
            $counter++;
            $BookedDriver               =   new BookedDriver();
            $BookedDriver->driver_id    =   $driver->driver_id;
            $BookedDriver->booking_id   =   $booking->id;
            $BookedDriver->save();
            $drvr->is_active            =   0;
            $drvr->save();
            if (in_array($driver->driver_id, $onlinedriver)) {
                Log::info('event send');
                event(new DriverRideInfo($user, $booking, $driver->driver_id, $user->totalride->count()));
            }
        }
    }
    public function getMinimumDistance($lat, $long, $routes)
    {
        $distances          =   [];
        foreach ($routes as $latlong) {
            $distances[]    =   distance($lat, $long, $latlong->latitude, $latlong->longitude, "K");
        }
        return min($distances) * 1000;
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
