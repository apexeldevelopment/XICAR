<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\CancelReason;
use App\Models\CarpoolingRideDetail;
use App\Models\CarpoolingRideUserDetail;
use App\Traits\CarpoolingTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveCarpoolingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($data)
    {
        
        $user = request()->user('api');
            $offer_user_data = array(
                'id' => $this->user_id,
                'name' => $this->User->first_name . " " . $this->User->last_name,
                'phone' => $this->User->UserPhone,
                'email' => $this->User->email,
                'image' => get_image($this->User->UserProfileImage, 'user', $this->merchant_id),
                'rating' => $this->User->rating,
            );
            $offer_user_vehicle_details = array(
                'id' => $this->user_vehicle_id,
                'vehicle_name' => $this->UserVehicle->VehicleType->VehicleTypeName,
                'vehicle_image' => get_image($this->UserVehicle->vehicle_image, 'user_vehicle_document', $this->merchant_id),
                'vehicle_color' => $this->UserVehicle->vehicle_color,
                'vehicle_number' => $this->UserVehicle->vehicle_number,
            );
            $other_data = array(
                'carpooling_ride_id' => $this->id,
                'ride_timestamp' => $this->ride_timestamp,
                'ride_start_time_set_config' => null,
                'ac_ride' => ($this->ac_ride == 1),
                'only_females' => false,
                'booked_seats' => $this->booked_seats,
                'no_of_stops' => $this->no_of_stops,
                'available_seats' => $this->available_seats,
                'return_ride' => ($this->return_ride == 1),
                'offer_user' => $offer_user_data,
                'offer_user_vehicle' => $offer_user_vehicle_details,
                'payment_type' => $this->payment_type == 1 ? "Cash Only" : "Online Payment",
            );
            $ride_details = CarpoolingRideDetail::where([['is_return', '=', NULL], ['carpooling_ride_id', '=', $this->id]])->orderBy('drop_no')->get();
           $cancelReasons = CancelReason::Reason($user->merchant_id, 2, $this->segment_id);
            $total_charges = 0;
            $ride_details_value = [];
            if (!empty($ride_details)) {
                $first_drop = $ride_details[0];
                array_push($ride_details_value, array(
                    'id' => $first_drop->id,
                    'drop_no' => 0,
                    'from_location' => $first_drop->from_location,
                    'from_latitude' =>$first_drop->from_latitude,
                    'from_longitude' =>$first_drop->from_longitude,
                    'ride_timestamp' => $first_drop->ride_timestamp,
                    
                ));
                foreach ($ride_details as $value) {
                    $ride_details_value[] = array(
                        'id' => $value->id,
                        'drop_no' => $value->drop_no,
                        'to_location' => $value->to_location,
                        'to_latitude' =>$value->to_latitude,
                        'to_longitude' =>$value->to_longitude,
                        'ride_timestamp' => $value->ride_timestamp,
                    );
                    $total_charges += $value->final_charges;

                }
            }
            $other_data['cancel_reason'] = $cancelReasons;
            $other_data['total_amount'] = $user->Country->isoCode . ' ' . $total_charges;
            $other_data['ride_details_list'] = $ride_details_value;
            $carpooling_ride_detail = CarpoolingRideDetail::where([['carpooling_ride_id', '=', $this->id],['ride_status', '=', 3],['is_return', '=', NULL]])->orderBy('drop_no')->first();
            $pickup_user = [];
            $drop_user = [];
            if(!empty($carpooling_ride_detail)){  // ['pickup_latitude','=',$this->start_latitude],['pickup_longitude','=',$this->start_longitude]
                $carpooling_pickup_user = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $this->id],['pickup_id','=',$carpooling_ride_detail->id]])->whereIn('ride_status',array(2,3,4,5))->get();
               //p($carpooling_pickup_user);
                if (!empty($carpooling_pickup_user)){
                foreach ($carpooling_pickup_user as $value) {
                    $pickup_user[] = array(
                        'user_id' => $value->user_id,
                        'pickup_user_id' => $value->id,
                        'unique_id'=>$value->carpooling_ride_id."-".$value->id,
                        'pickup_user_name' => $value->User->first_name,
                        'pickup_user_image' => get_image($value->User->UserProfileImage, 'user', $user->merchant_id),
                        'pickup_user_rating' => $value->User->rating,
                        'pickup_user_distance' =>$value->CarpoolingRideDetail->estimate_distance,
                        'start_location'=>$value->pickup_location,
                        'end_location'=>$value->drop_location,
                        'ride_time'=>$value->ride_timestamp,
                        'end_ride_time'=>$value->end_timestamp,
                        'payment_type'=>$value->CarpoolingRide->payment_type,
                        'payment_action'=>$value->payment_action,
                        'final_amount'=>$value->User->Country->isoCode." ".round($value->total_amount,3),
                        'UserPhone'=>$value->User->UserPhone,
                        'ride_status'=>$value->ride_status,
                        'no_of_seats'=>$value->booked_seats,
                                
                    );
                    // $carpooling_user_details=CarpoolingRideUserDetail::find(request()->carpooling_ride_user_detail_id);
                    // if(request()->action =="cancel"){
                    //     $carpooling_user_details->ride_status = 5;
                    //     $carpooling_user_details->save();
                    //  }
                    //   elseif(request()->action=="end"){
                    //     $carpooling_user_details->ride_status = 4;
                    //     $carpooling_user_details->save();
                    //     }
                    }
                }
                
            
            
                // ['drop_latitude','=',$this->end_latitude],['drop_longitude','=',$this->end_longitude]
                $carpooling_drop_user = CarpoolingRideUserDetail::where([['carpooling_ride_id', '=', $this->id],['drop_id','=',$carpooling_ride_detail->id],['ride_status', '=', 8]])->get();
              //p($carpooling_drop_user);
                if (!empty($carpooling_drop_user)) {
                    foreach ($carpooling_drop_user as $value) {
                        $drop_user[] = array(
                            
                            'user_id' => $value->user_id,
                            'drop_user_id'=>$value->id,
                            'drop_user_name' => $value->User->first_name . " " . $value->User->last_name,
                            'drop_user_image' => get_image($value->User->UserProfileImage, 'user', $user->merchant_id),
                            'drop_user_rating' => $value->User->rating,
                            'drop_user_distance' =>$value->CarpoolingRideDetail->estimate_distance,
                           'start_location'=>$value->pickup_location,
                            'end_location'=>$value->drop_location,
                            'ride_time'=>$value->ride_timestamp,
                            'end_ride_time'=>$value->end_timestamp,
                            'payment_type'=>$value->CarpoolingRide->payment_type,
                            'payment_action'=>$value->payment_action,
                            'final_amount'=>$value->User->Country->isoCode." ".$value->CarpoolingRideDetail->final_charges,
                            'UserPhone'=>$value->User->UserPhone,
                            'ride_status'=>$value->ride_status,
                        );
                         if(request()->carpooling_ride_user_detail_id = $value->id){
                        if(request()->action=="end"){
                        $value->ride_status = 4;
                        $value->save();
                        }
                     } 
                    }
                }    
            }

            $other_data['pickup_users'] = $pickup_user;
            $other_data['drop_users'] = $drop_user;
    

        return [
            'Active_User_Ride' => $other_data,
            
        ];
    }
}