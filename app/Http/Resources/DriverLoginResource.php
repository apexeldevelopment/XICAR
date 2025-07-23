<?php

namespace App\Http\Resources;

use App\Http\Controllers\Helper\Merchant;
use App\Models\Booking;
use App\Models\DriverRideConfig;
use App\Models\PaymentOption;
use App\Models\PaymentOptionsConfiguration;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\DriverTrait;
use DB;

class DriverLoginResource extends JsonResource
{
    use DriverTrait;

    public function toArray($data)
    {
        $online_config = $this->getDriverOnlineConfig($this, 'online_details');
        $driver_address = $this->ActiveAddress;

        //Encrypt and Decrypt
        $bankName = $this->bank_name;
        $accHolderName = $this->account_holder_name;
        $accNumber = $this->account_number;
        $fname = $this->first_name;
        $lname = $this->last_name;
        $email = $this->email;
        $phone = $this->phoneNumber;
        $image = $this->profile_image;
        if($image){
            $image = get_image($this->profile_image, 'driver', $this->merchant_id, true, false, 'driver');
        }
        if($this->Merchant->Configuration->encrypt_decrypt_enable == 1){
            try {
                $keys = getSecAndIvKeys();
                $iv = $keys['iv'];
                $secret = $keys['secret'];
                
                if($this->bank_name){
                    $bankName = encryptText($this->bank_name,$secret,$iv);
                }
                
                if($this->account_holder_name){
                    $accHolderName = encryptText($this->account_holder_name,$secret,$iv);
                }

                if($this->account_number){
                    $accNumber = encryptText($this->account_number,$secret,$iv);
                }
                
               if($this->first_name){
                   $fname = encryptText($this->first_name,$secret,$iv);
               }

               if($this->last_name){
                   $lname = encryptText($this->last_name,$secret,$iv);
               }

               if($this->email){
                   $email = encryptText($this->email,$secret,$iv);
               }
               
               if($this->phoneNumber){
                   $phone = encryptText($this->phoneNumber,$secret,$iv);
               }

               if($image){
                   $image = encryptText(get_image($this->profile_image, 'driver', $this->merchant_id, true, false, 'driver'),$secret,$iv);
               }
                
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        }
        if (!empty($driver_address->id)) {
            $address = [
                'id' => $driver_address->id,
                'name' => $driver_address->address_name,
                'location' => $driver_address->location,
                'latitude' => $driver_address->latitude,
                'longitude' => $driver_address->longitude,
                'radius' => $driver_address->radius,
            ];
        } else {
            $address = [
                'id' => NULL,
                'name' => "",
                'location' => "",
                'latitude' => "",
                'longitude' => "",
                'radius' => NULL,
            ];
        }
        $bank_details = [];
        if (isset($this->Merchant->Configuration->bank_details_enable) && $this->Merchant->Configuration->bank_details_enable == 1) {
            $account_type = "";
            if (!empty($this->account_type_id)) {
                $account_type = $this->AccountType->Name;
            }
            $bank_details = array(
                'bank_name' => isset($bankName) ? $bankName : "",
                'account_type' => $account_type,
                'account_type_id' => isset($this->account_type_id) ? $this->account_type_id : "",
                'online_code' => isset($this->online_code) ? $this->online_code : "",
                'account_holder_name' => isset($accHolderName) ? $accHolderName : "",
                'account_number' => isset($accNumber) ? $accNumber : "",
                'transaction_code_text' => isset($this->Country) ? $this->Country->transaction_code : "Transaction Code"
            );
        } else {
            $bank_details = array(
                'bank_name' => "",
                'account_type' => "",
                'account_type_id' => "",
                'online_code' => "",
                'account_holder_name' => "",
                'account_number' => "",
                'transaction_code_text' => ""
            );
        }

        $segment_group_id = $this->segment_group_id;
        if($segment_group_id == 4)
        {
            $segments_id  = DB::table('segments')
                            ->where('segment_group_id', $segment_group_id)
                            ->first();
        }
        $home_address = false;
        if ($segment_group_id == 1) {
            $segments = array_pluck($this->Segment, 'slag');
            $home_address = (in_array('TAXI', $segments) || in_array('DELIVERY', $segments)) && $this->Merchant->BookingConfiguration->home_address_enable == 1 ? true : false;
        }
        $driver_radius = new \ArrayObject();
        $driver_radius["min_radius"] = 0;
        $driver_radius["max_radius"] = 0;
        $driver_radius["enable"] = false;
        $driver_radius["default_radius"] = 0;
        if (isset($this->Merchant->Configuration->driver_limit) && $this->Merchant->Configuration->driver_limit == 1 && $segment_group_id == 1) {
            $configuration = isset($this->Merchant->BookingConfiguration) ? $this->Merchant->BookingConfiguration : $this->Merchant->BookingConfiguration;
            $max_radius = isset($configuration->normal_ride_now_radius) ? $configuration->normal_ride_now_radius : 10;
            if (!empty($configuration->driver_ride_radius_request)) {
                $remain_ride_radius_slot = json_decode($configuration->driver_ride_radius_request, true);
                $max_radius_slot = isset($remain_ride_radius_slot[2]) ? $remain_ride_radius_slot[2] : $remain_ride_radius_slot[0];
                $max_radius = !empty($max_radius_slot) ? $max_radius_slot : $max_radius;
            }
            $driver_radius["max_radius"] = $max_radius;
            $driver_ride_config = DriverRideConfig::where('driver_id',$this->id)->first();
            $default_radius = !empty($driver_ride_config) ? $driver_ride_config->radius : 0;
            $driver_radius["enable"] = true;
            $driver_radius["default_radius"] = ($default_radius > 0) ? $default_radius : $max_radius;
        }
        return [
            'id' => $this->id,
            'segment_id' => !empty($segments_id) ? (string)$segments_id->id : "" ,
            'signup_step' => $this->signupStep,
            'country_code' => $this->Country->country_code,
            'country_area_id' => $this->country_area_id,
            'country_id' => $this->country_id,
            'segment_group_id' => (int)$segment_group_id,
            'online_enable' => $this->signupStep == 9 ? true : false,
            'profile_image' => $image,
            'cover_image' => isset($this->cover_image) && !empty($this->cover_image) ? get_image($this->cover_image, 'driver', $this->merchant_id, true, false, 'driver') : "",
            'first_name' => $fname,
            'last_name' => !empty($lname) ? $lname : "",
            'email' => !empty($email) ? $email : "",
            'phone_number' => !empty($phone) ? $phone : "",
            'online_config_status' => $online_config['status'],
            'work_set' => $online_config['detail'],
            'driver_address' => $address,
            'bank_details' => $bank_details,
            'home_address_enable' => $home_address,
            'home_location_active' => $this->home_location_active, // 1 active, 2 inactive
            'driver_radius' => $driver_radius,
            'socket_data' => $online_config['socket_data'],
            'driver_gender' => $this->driver_gender,
            'citizen_type' => $this->citizen_type,
        ];
    }
}
