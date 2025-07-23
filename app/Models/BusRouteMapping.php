<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;
use DB;
// use App\Models\ServiceTimeSlotDetail;

class BusRouteMapping extends Model
{
    use HasFactory;
//    protected $hidden = array('pivot', 'LanguageSingle', 'LanguageAny');
    protected $guarded = [];
//
//
//
//    // public function getNameAttribute()
//    // {
//    //     if (empty($this->LanguageSingle)) {
//    //         return $this->LanguageAny->name;
//    //     }
//    //     return $this->LanguageSingle->name;
//    // }
//
//
    public function Bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function BusRoute()
    {
        return $this->belongsTo(BusRoute::class);
    }

    // time slot mapping
    function ServiceTimeSlotDetail()
    {
        return $this->belongsTo(ServiceTimeSlotDetail::class, 'bus_route_mapping', 'service_time_slot_detail_id');
    }

    public function routeBuses($request, $user)
    {
        try {
            $route_id = $request->bus_route_id;
            $booking_day = date("w");
            if(isset($request->booking_date) && !empty($request->booking_date)){
                $booking_day = date("w", strtotime($request->booking_date));
            }elseif(isset($request->service_types_type) && $request->service_types_type == 1){
                $booking_day = [];
                array_push($booking_day, date("w"));
                array_push($booking_day, date("w", strtotime("+1 day")));
                array_push($booking_day, date("w", strtotime("+2 day")));
            }

            $arr_time_slot = ServiceTimeSlot::select('id','day')
                ->with(['ServiceTimeSlotDetail' => function ($q) use ($route_id, $request) {
                    $q->orderBy('from_time');
                    $q->addSelect('id','service_time_slot_id','from_time','slot_time_text');

                     $q->whereHas('BusRouteMapping', function ($qq) use ($route_id) {
                        $qq->where('bus_route_id', $route_id);
                    });

                    $q->whereDoesntHave('BusBookingMaster', function($qqq) use ($q, $route_id, $request) {
                        $qqq->where('booking_date', $request->booking_date);
                        $qqq->where('bus_route_id', $route_id);
                        $qqq->where('status', 3);
                    });


                    $q->with(['BusRouteMapping' => function ($qq) use ($route_id, $request) {
                        $qq->addSelect('id','service_time_slot_detail_id','bus_id','bus_route_id');
                        $qq->where('bus_route_id', $route_id);
                        $qq->with(['Bus' => function ($qq) use($request){
                            $qq->addSelect('id','vehicle_number','bus_name','vehicle_color','vehicle_type_id','vehicle_model_id','vehicle_number','vehicle_color','bus_name','bus_traveller_id','traveller_name','ac_nonac','type');
                            if(isset($request->bus_service_id) && !empty($request->bus_service_id)){
                                $qq->whereHas("BusService", function($k) use($request){
                                    $bus_service_id = explode(",",$request->bus_service_id);
                                    $k->whereIn("id", $bus_service_id)->where([["is_general_info", "!=", 1]]);
                                });
                            }

                            $qq->where(function ($k) use($request){
                                if(isset($request->is_ac) && $request->is_ac == 1){
                                    $k->where("ac_nonac", 1);
                                }
                                if(isset($request->is_non_ac) && $request->is_non_ac == 1){
                                    $k->orWhere("ac_nonac", 0);
                                }
                            });

                            $qq->where(function ($k) use($request){
                                if(isset($request->is_seater) && $request->is_seater == 1){
                                    $k->whereIn("type", ["LOWER", "LOWER_UPPER"]);
                                }
                                if(isset($request->is_sleeper) && $request->is_sleeper == 1){
                                    $k->orWhere("type", "LOWER_UPPER");
                                }
                            });


                        }]);
                    }]);
                }])
                ->whereHas('ServiceTimeSlotDetail', function ($qq) use ($route_id) {
                    $qq->orderBy('from_time');
                    $qq->whereHas('BusRouteMapping', function ($qqq) use ($route_id) {
                        $qqq->where('bus_route_id', $route_id);
                    });
                })->where(function($q) use($booking_day){
                    if(is_array($booking_day)){
                        $q->whereIn("day", $booking_day);
                    }else{
                        $q->where("day", $booking_day);
                    }
                })->get();

            return $arr_time_slot;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
