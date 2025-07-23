<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 7/11/23
 * Time: 5:15 PM
 */

namespace App\Http\Controllers\Merchant;


use App\Http\Controllers\Controller;
use App\Models\BusBooking;
use App\Models\BusBookingMaster;
use App\Models\BusDriverMapping;
use App\Models\Driver;
use App\Traits\BusBookingTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use DB;
use Illuminate\Support\Facades\Validator;
use App\Models\BusBookingRating;

class BusBookingController extends Controller
{
    use MerchantTrait, BusBookingTrait;

    public function index(Request $request){
        $merchant_id = get_merchant_id();
        $bus_booking_masters = BusBookingMaster::where("merchant_id", $merchant_id)->whereIn("status",[1,2])->latest()->paginate(20);
        $bus_booking_status = Config::get("custom.bus_booking_status");
        $data = $request->all();
        $auto_assign_count = $bus_booking_masters->whereNull("driver_id")->count();
        return view("merchant.bus-booking.bookings.active", compact("bus_booking_masters", "bus_booking_status", "data", "auto_assign_count"));
    }

    public function pastBookings(Request $request){
        $merchant_id = get_merchant_id();
        $bus_booking_masters = BusBookingMaster::where("merchant_id", $merchant_id)->whereIn("status",[3,4,5])->latest()->paginate(20);
        $bus_booking_status = Config::get("custom.bus_booking_status");
        $data = $request->all();
        return view("merchant.bus-booking.bookings.past", compact("bus_booking_masters", "bus_booking_status", "data"));
    }

    public function autoAssign(Request $request){
        DB::beginTransaction();
        try{
            $merchant_id = get_merchant_id();
            $string_file = $this->getStringFile($merchant_id);
            $bus_booking_masters = BusBookingMaster::where(["merchant_id" => $merchant_id, "status" => 1])->whereNull("driver_id")->get();
            $not_assigned_bookings = [];
            $added_count = 0;
            foreach($bus_booking_masters as $booking_master){
                $bus_driver = BusDriverMapping::where([["bus_id", "=", $booking_master->bus_id], ["bus_route_id", "=", $booking_master->bus_route_id], ["service_time_slot_detail_id", "=", $booking_master->service_time_slot_detail_id]])->first();
                if(!empty($bus_driver)){
                    $booking_master->driver_id = $bus_driver->driver_id;
                    $booking_master->save();

                    $this->notifyBusBookingDriver($booking_master, "BOOKING_ASSIGN");

                    foreach($booking_master->BusBooking as $busBooking){
                        $this->notifyBusBookingUser($busBooking, "BUS_BOOKING_ASSIGN");
                    }

                    $added_count++;
                }else{
                    array_push($not_assigned_bookings, $booking_master->id);
                }
            }
            DB::Commit();
            $message = "";
            $error = false;
            if($added_count > 0){
                if(empty($not_assigned_bookings)){
                    $message = trans("$string_file.auto_booking_assign")." ".trans("$string_file.successfully");
                }else{
                    $message = trans("$string_file.partial_booking_assign"). " ". trans("$string_file.not_saved_for"). " ".implode(",", $not_assigned_bookings);
                }
            }else{
                $error = true;
                $message = trans("$string_file.failed")." ".trans("$string_file.not_saved_for"). " ".implode(",", $not_assigned_bookings);
            }
            if($error){
                return redirect()->route("merchant.bus_booking.active.index")->withErrors($message);
            }else{
                return redirect()->route("merchant.bus_booking.active.index")->withSuccess($message);
            }
        }catch (\Exception $exception){
            DB::rollback();
            return redirect()->back()->withErrors($exception->getMessage());
        }
    }

    public function details($bus_booking_id){
        $merchant_id = get_merchant_id();
        $bus_booking = BusBookingMaster::where("merchant_id", $merchant_id)->findOrFail($bus_booking_id);
        $bus_booking_status = Config::get("custom.bus_booking_status");
        $bus_type_show = Config::get("custom.bus_type_show");
        $drivers = [];
        if(empty($bus_booking->driver_id)){
            $drivers = Driver::where("merchant_id", $merchant_id)
                ->whereHas("Segment", function($q) use($bus_booking){
                    $q->where("id", $bus_booking->segment_id);
                })
                ->whereHas("ServiceType", function($q) use($bus_booking){
                    $q->where("service_type_id", $bus_booking->service_type_id);
                })
                ->get()->pluck("fullName","id")->toArray();
        }
        return view("merchant.bus-booking.bookings.detail", compact("bus_booking", "bus_booking_status", "bus_type_show", "drivers"));
    }

    public function manualAssign(Request $request){
        $validator = Validator::make($request->all(), [
            'bus_booking_id' => 'required|exists:bus_booking_masters,id',
            'driver_id'=>'required|exists:drivers,id',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages()->all());
        }
        DB::beginTransaction();
        try{
            $merchant_id = get_merchant_id();
            $string_file = $this->getStringFile($merchant_id);
            $booking_master = BusBookingMaster::find($request->bus_booking_id);
            $booking_master->driver_id = $request->driver_id;
            $booking_master->save();

            $this->notifyBusBookingDriver($booking_master, "BOOKING_ASSIGN");

            foreach($booking_master->BusBooking as $busBooking){
                $this->notifyBusBookingUser($busBooking, "BUS_BOOKING_ASSIGN");
            }

            DB::Commit();
            return redirect()->back()->withSuccess(trans("$string_file.booking_assign")." ".trans("$string_file.successfully"));
        }catch (\Exception $exception){
            DB::rollback();
            return redirect()->back()->withErrors($exception->getMessage());
        }
    }

    public function cancelBooking(Request $request){
        return redirect()->back()->withErrors("We are still working on this.");
    }
    
    public function bookingRatings(Request $request){
        try{
            $merchant = get_merchant_id(false);
            $merchant_id = $merchant->id;
            $query = BusBookingRating::whereHas('BusBooking', function ($q) use ($request) {
                if (!empty($request->bus_id)) {
                    $q->where('bus_id','=',$request->bus_id);
                }
                $q->whereHas('User', function($query) use($request) {
                    
                    if(!empty($request->user_details)){
                        $query->where([['UserPhone', 'LIKE', $request->user_details]]);
                        $query->orWhere([['email', 'LIKE', $request->user_details]]);
                        $query->orWhere([['first_name', 'LIKE', $request->user_details]]);
                    }

                    if(!empty($request->user_id)){
                        $query->where([['id', '=', $request->user_id]]);
                    }
                    $query->where([['user_delete', '=', NULL]]);
                });
            });
            if (!empty($request->booking_master_id)) {
                $query->where('bus_booking_master_id','=',$request->booking_master_id);
            }
            if (!empty($request->booking_id)) {
                $query->where('bus_booking_id','=',$request->bus_booking_id);
            }

            
            $booking_ratings = $query->orderBy('created_at','DESC')->paginate(25);
            // p($booking_ratings);

            // $ratings = $booking_ratings->map(function ($item) use ( $merchant_id) {
                
            //     return [
            //         'id' => $item->id,
            //         'order_id' => $item->order_id,
            //         'merchant_order_id' => ($item->booking_id!="")?$item->Booking->merchant_booking_id:$item->Order->merchant_order_id,
            //         'merchant_id' => $item->Order->merchant_id,
            //         'user_rating_points' => $item->user_rating_points,
            //         'user_comment' => $item->user_comment,
            //         'driver_rating_points' => $item->driver_rating_points,
            //         'driver_comment' => $item->driver_comment,
            //         'product_quality_points' => !empty($item->product_quality_points)?$item->product_quality_points:"",
            //         'packaging_goods_points' => !empty($item->packaging_goods_points)?$item->packaging_goods_points:"",
            //         'product_accuracy_points' => !empty($item->product_accuracy_points)?$item->product_accuracy_points:"",
            //         'preparation_time_points' => !empty($item->preparation_time_points)?round($item->preparation_time_points,1):"",
            //         'rating_image_1' => $rating_images[0]->image ?? "",
            //         'rating_image_2' => $rating_images[1]->image ?? "",
            //         'created_at' => date('Y-m-d H:i:s',strtotime($item->created_at)),
            //         'feedback_id' => !empty($item->FeedbackType)?$item->FeedbackType->feedback_type:"",
            //         'user_id' => $item->Order->User->id,
            //         'user_name' => $item->Order->User->first_name.' '.$item->Order->User->last_name,
            //         'user_phone' => $item->Order->User->UserPhone,
            //         'user_email' => $item->Order->User->email,
            //         'driver_id' => (!empty($item->Order->Driver))?$item->Order->Driver->id:"",
            //         'driver_name' => (!empty($item->Order->Driver))?$item->Order->Driver->first_name.' '.$item->Order->Driver->last_name:"",
            //         'driver_phone' => (!empty($item->Order->Driver))?$item->Order->Driver->phoneNumber:"",
            //         'driver_email' => (!empty($item->Order->Driver))?$item->Order->Driver->email:"",
            //     ];
            // });
            $arr_search = $request->all();
            $data = [];
        }catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

            return view('merchant.bus-booking.rating',compact('booking_ratings','arr_search'));
    }
    
    public function addProviderComment(Request $request){
        DB::beginTransaction();
        try{
            $rating = BusBookingRating::find($request->rating_id);
            $rating->provider_comments = $request->provider_comment;
            $rating->save();
            $message = "Comment added Successfully";
            DB::Commit();
        }catch (\Exception $exception){
             DB::rollback();
            return redirect()->route("merchant.bus_booking.rating.index")->withErrors($exception->getMessage());
        }
        return redirect()->route("merchant.bus_booking.rating.index")->withSuccess($message);
    }


    public function getMasterBookings(Request $request){
        $merchant_id = get_merchant_id();
        $query = BusBookingMaster::with(['Bus','BusBooking' => function ($q) use ($request) {
            if (!empty($request->bus_booking_id)) {
                $q->where('id', $request->bus_booking_id);
            }
        }])->where("merchant_id", $merchant_id);
        if (!empty($request->bus_id)) {
            $query->where('bus_id', '=', $request->bus_id);
        }
        if (!empty($request->booking_master_id)) {
            $query->where('id', '=', $request->booking_master_id);
        }
        if (!empty($request->bus_booking_id)) {
            $query->whereHas('BusBooking', function ($q) use ($request) {
                $q->where('id', '=', $request->bus_booking_id);
            });
        }
        $bus_booking_masters = $query->orderBy("id", "desc")->paginate(20);
        $bus_booking_status = Config::get("custom.bus_booking_status");
        $data = $request->all();
        return view("merchant.bus-booking.bookings.bus_booking_master", compact("bus_booking_masters", "bus_booking_status", "data",));
    }


    public function getBusBooking(Request $request, $bus_booking_master_id){
        $merchant_id = get_merchant_id();
        $bus_bookings = BusBooking::where("merchant_id", $merchant_id)
            ->where("bus_booking_master_id", $bus_booking_master_id);

        if (!empty($request->booking_id)) {
            $bus_bookings->where("merchant_bus_booking_id", $request->booking_id);
        }

        if (!empty($request->name)) {
            $bus_bookings->whereHas('user', function ($query) use ($request) {
                $query->where('first_name', 'like', '%' . $request->first_name . '%');
            });
        }
        if (!empty($request->last_name)) {
            $bus_bookings->whereHas('user', function ($query) use ($request) {
                $query->where('last_name', 'like', '%' . $request->last_name . '%');
            });
        }
        if (!empty($request->phone)) {
            $bus_bookings->whereHas('user', function ($query) use ($request) {
                $query->where('userPhone', 'like', '%' . $request->phone . '%');
            });
        }
        $paginated_bookings = $bus_bookings->paginate(20);
        $data = $request->all();
        return view("merchant.bus-booking.bookings.bus_booking", compact("paginated_bookings", 'data'));
    }
}
