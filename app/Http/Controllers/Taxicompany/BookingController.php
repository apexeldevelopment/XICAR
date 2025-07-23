<?php

namespace App\Http\Controllers\Taxicompany;

use App\Http\Controllers\Helper\HolderController;
use App\Models\EmailConfig;
use App\Models\EmailTemplate;
use App\Models\FailBooking;
use App\Models\Onesignal;
use Auth;
use App\Models\Driver;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\BookingConfiguration;
use App\Traits\BookingTrait;
use App\Traits\MerchantTrait;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Events\SendUserInvoiceMailEvent;

class BookingController extends Controller
{
    use BookingTrait,MerchantTrait;

    public function index()
    {
        $taxi_company = get_taxicompany();
        $merchant_id = $taxi_company->merchant_id;
        $all_bookings = $this->ActiveBooking(false, 'TAXICOMPANY')->get();
        if (!empty($all_bookings)){
            foreach ($all_bookings as $booking){
                if (!empty($booking->driver_id)){
                    $timezone = $booking->Driver->CountryArea->timezone;
                }else{
                    if(isset($booking->User->Country->CountryArea[0]->timezone)){
                        $timezone = $booking->User->Country->CountryArea[0]->timezone;
                    }else{
                        $timezone = 'Asia/Kolkata';
                    }
                }
//                date_default_timezone_set($timezone);
                $date = date('Y-m-d');
                $currenct_time = date('H:i');
                $later_booking_date = set_date($booking->later_booking_date);
                if ($booking->booking_type == 1 && $date > $booking->updated_at){
                    Booking::where('id','=',$booking->id)->update(['booking_status'=> 1016]);
                }
                if ($booking->booking_type == 2 && ($date > $later_booking_date || ($date == $later_booking_date && $currenct_time > $booking->later_booking_time))){
                    Booking::where('id','=',$booking->id)->update(['booking_status'=> 1016]);
                }
            }
        }
        $bookings = $this->ActiveBookingNow(true, 'TAXICOMPANY');
        $later_bookings = $this->ActiveBookingLater(true, 'TAXICOMPANY');
        $cancelreasons = $this->CancelReason('TAXICOMPANY');
        $bookingConfig = BookingConfiguration::select('ride_otp')->where([['merchant_id','=',$merchant_id]])->first();
        $string_file = $this->getStringFile($merchant_id);
        $arr_status = $this->getBookingStatus($string_file);
        return view('taxicompany.booking.active', compact('bookings', 'cancelreasons', 'later_bookings','bookingConfig','arr_status'));
    }

    public function AllRides()
    {
        $taxi_company = get_taxicompany();
        $merchant_id = $taxi_company->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $arr_status = $this->getBookingStatus($string_file);
        $bookings = $this->bookings(true, [1001, 1012, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1016], 'TAXICOMPANY');

        return view('taxicompany.booking.all-ride', compact('bookings','arr_status'));
    }

    public function CompleteBooking(){
        $taxi_company = get_taxicompany();
        $merchant_id = $taxi_company->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $arr_status = $this->getBookingStatus($string_file);
        $bookings = $this->bookings(true, [1005], 'TAXICOMPANY');
        return view('taxicompany.booking.complete', compact('bookings','arr_status'));
    }
    
    public function SearchCompleteBooking(Request $request){
        $query = $this->bookings(false, [1005],'TAXICOMPANY');
        if ($request->booking_id) {
            $query->where('merchant_booking_id', $request->booking_id);
        }
        if ($request->booking_status) {
            $query->where('booking_status', $request->booking_status);
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")->orwhere('last_name', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if ($request->date) {
            $query->whereDate('created_at', '=', $request->date);
        }
        if ($request->driver) {
            $keyword = $request->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")->orwhere('last_name', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        $bookings = $query->paginate(25);
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $arr_status = $this->getBookingStatus($string_file);
        return view('taxicompany.booking.complete', compact('bookings','arr_status'));
    }
    
    public function CancelBooking(){
        $taxi_company = get_taxicompany();
        $merchant_id = $taxi_company->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $arr_status = $this->getBookingStatus($string_file);
        $bookings = $this->bookings(true, [1006, 1007, 1008], 'TAXICOMPANY');
        
        return view('taxicompany.booking.cancel', compact('bookings','arr_status'));
        
    }
    
    public function SearchCancelBooking(Request $request){
        $query = $this->bookings(false, [1006,1007,1008],'TAXICOMPANY');
        if ($request->booking_id) {
            $query->where('merchant_booking_id', $request->booking_id);
        }
        if ($request->booking_status) {
            $query->where('booking_status', $request->booking_status);
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")->orwhere('last_name', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if ($request->date) {
            $query->whereDate('created_at', '=', $request->date);
        }
        if ($request->driver) {
            $keyword = $request->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")->orwhere('last_name', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        $bookings = $query->paginate(25);
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $arr_status = $this->getBookingStatus($string_file);
        return view('taxicompany.booking.cancel', compact('bookings','arr_status'));
    }

    public function DriverRequest($id)
    {
        $booking = Booking::with(['BookingRequestDriver' => function ($query) {
            $query->with('Driver');
        }])->with('OneSignalLog')->findOrFail($id);
        return view('taxicompany.booking.request', compact('booking'));
    }

    public function BookingDetails(Request $request, $id)
    {
        $taxi_company_id = get_taxicompany(true);
        $booking = Booking::with('User')->where([['taxi_company_id', '=', $taxi_company_id]])->findOrFail($id);
        $booking->map_image = $booking->map_image . "&zoom=12&size=600x300";
        if($booking->family_member_id != ''){
            $booking->FamilyMember = FamilyMember::find($booking->family_member_id);
        }
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $arr_booking_status = $this->getBookingStatus($string_file);
        return view('taxicompany.booking.detail', compact('booking','arr_booking_status'));
    }

    public function ActiveBookingTrack($id){
        $booking = Booking::where([['id', '=', $id]])->first();
        return view('taxicompany.booking.track', compact('booking'));
    }

    public function SearchForAllRides(Request $request)
    {
        $query = $this->bookings(false, [1001, 1012, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1016],'TAXICOMPANY');
        if ($request->booking_id) {
            $query->where('merchant_booking_id', $request->booking_id);
        }
        if ($request->booking_status) {
            $query->where('booking_status', $request->booking_status);
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")->orwhere('last_name', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if ($request->date) {
            $query->whereDate('created_at', '=', $request->date);
        }
        if ($request->driver) {
            $keyword = $request->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")->orwhere('last_name', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        $bookings = $query->paginate(25);
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $arr_status = $this->getBookingStatus($string_file);
        return view('taxicompany.booking.all-ride', compact('bookings','arr_status'));
    }

    public function driver_location(Request $request)
    {
        $driver_id = $request->driver_id;
        $driver = Driver::select('current_latitude', 'current_longitude')->find($driver_id);
        return $driver;
    }
}
