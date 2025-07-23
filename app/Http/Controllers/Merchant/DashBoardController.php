<?php

namespace App\Http\Controllers\Merchant;

use App\Events\SendUserInvoiceMailEvent;
use App\Http\Controllers\Helper\DistanceCalculation;
use App\Http\Controllers\Helper\DistanceController;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\Merchant as helperMerchant;
use App\Http\Controllers\Helper\PriceController;
use App\Http\Controllers\Helper\Toll;
use App\Models\ApplicationTheme;
use App\Models\BusinessSegment\LanguageProductVariant;
use App\Models\CancelReason;
use App\Models\CarpoolingRide;
use App\Models\Configuration;
use App\Models\Country;
use App\Models\CustomerSupport;
use App\Models\InfoSetting;
use App\Models\SearchablePlace;
use App\Models\User;
use App\Models\DistanceMethod;
use App\Models\DistanceSetting;
use App\Models\HandymanOrder;
use App\Models\LanguageString;
use App\Models\LanguageStringTranslation;
use App\Models\Merchant;
use App\Models\Onesignal;
use App\Models\MerchantWebOneSignal;
use App\Models\VersionManagement;
use App\Traits\RatingTrait;
use Auth;
use App;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceType;
use App\Models\UserVehicle;
use App\Traits\BookingTrait;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ImageTrait;
use App\Traits\PolylineTrait;
use App\Traits\DriverTrait;
use Schema;
use Session;
use App\Traits\ContentTrait;
use App\Traits\MerchantTrait;
use Illuminate\Support\Collection;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Models\Driver;
use App\Models\WalletRechargeRequest;


class DashBoardController extends Controller
{
    use MerchantTrait, BookingTrait, RatingTrait, ImageTrait, PolylineTrait, DriverTrait, ContentTrait;

    public function mercadoPage(Request $request)
    {
        return view('merchant.random.mercado');
    }

    public function index(Request $request)
    {
        $merchant = get_merchant_id(false);
        Session::put('demo_otp', '');
        $merchant_id = $merchant->id;
        $merchant_segment_group = $this->segmentGroup($merchant_id, 'drop_down');
        $merchant_segment_group_config = isset($merchant_segment_group['arr_group']) ? array_keys($merchant_segment_group['arr_group']) : [];
        $request->merge(['merchant_id' => $merchant_id]);
        $driver = $this->getDriverSummary($request);
        $users = User::select('id')->where(array('user_delete' => NULL, 'merchant_id' => $merchant_id))->count();

        $cancelreasons = CancelReason::where([['merchant_id', '=', $merchant_id], ['reason_type', '=', 3]])->get();
        $merchant_segment = helperMerchant::MerchantSegments(1);
        $offer_users = [];
        $offer_ride = [];
        $on_going_ride = [];
        $user_ride = [];
        $upcoming_booking = [];
        $cancelride = [];
        $complete_ride = [];
        $carpooling_is_coming_soon = 2;
        if (in_array('CARPOOLING', $merchant_segment)) {
            $offer_users = User::whereHas('UserVehicles', function ($q) {
                $q->where('vehicle_delete', NULL);
            })->where([['merchant_id', '=', $merchant_id], ['taxi_company_id', '=', NULL], ['user_delete', '=', NULL]])->count();

            $offer_ride = CarpoolingRide::where([['merchant_id', '=', $merchant_id]])->whereIn('ride_status', [1, 3, 4, 5, 6])->count();

            $on_going_ride = App\Models\CarpoolingRideUserDetail::where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 3]])->count();

            $user_ride = App\Models\CarpoolingRideUserDetail::where([['merchant_id', '=', $merchant_id]])->whereIn('ride_status', [1, 2, 3, 4, 5, 6, 7])->count();

            $upcoming_booking = App\Models\CarpoolingRide::where([['merchant_id', '=', $merchant_id]])->whereIn('ride_status', [1, 2])->count();

            $cancelride = App\Models\CarpoolingRideUserDetail::where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 5]])->count();

            $complete_ride = App\Models\CarpoolingRideUserDetail::where([['merchant_id', '=', $merchant_id], ['ride_status', '=', 4]])->count();
            $carpoolingSegment = $merchant->Segment->where("slag", "CARPOOLING")->first();
            $carpooling_is_coming_soon = isset($carpoolingSegment)? $carpoolingSegment->pivot->is_coming_soon: 2;
        }

        $carpooling_states = (object)[
            'offered_user' => $offer_users,
            'on_going_ride' => $on_going_ride,
            'offer_ride' => $offer_ride,
            'user_ride' => $user_ride,
            'upcoming_ride' => $upcoming_booking,
            'cancel_ride' => $cancelride,
            'complete_ride' => $complete_ride,
            'carpooling_is_coming_soon' => $carpooling_is_coming_soon,
        ];


        $total_active_bus_bookings = 0;
        $total_bus_bookings = 0;
        $total_active_bus_booking_masters = 0;
        $total_upcoming_master_bus_bookings = 0;
        $total_bus_booking_masters = 0;
        $bus_booking_is_coming_soon = 2;

        if (in_array('BUS_BOOKING', $merchant_segment)) {
            $total_active_bus_bookings = App\Models\BusBooking::where([['merchant_id', '=', $merchant_id]])->where('status', 2)->count();

            $total_bus_bookings = App\Models\BusBooking::where([['merchant_id', '=', $merchant_id]])->whereIn('status', [1,2,3])->count();

            $total_active_bus_booking_masters = App\Models\BusBookingMaster::where([['merchant_id', '=', $merchant_id]])->where('status', 2)->count();

            $total_upcoming_master_bus_bookings = App\Models\BusBookingMaster::where([['merchant_id', '=', $merchant_id]])->where('status', 1)->count();

            $total_bus_booking_masters = App\Models\BusBookingMaster::where([['merchant_id', '=', $merchant_id]])->whereIn('status', [1, 2, 3])->count();

            $busBookingSegment = $merchant->Segment->where("slag", "BUS_BOOKING")->first();
            $bus_booking_is_coming_soon = isset($busBookingSegment)? $busBookingSegment->pivot->is_coming_soon: 2;
        }

        $bus_booking_states = (object)[
            'active_bus_bookings' => $total_active_bus_bookings,
            'bus_bookings' => $total_bus_bookings,
            'active_master_bus_bookings' => $total_active_bus_booking_masters,
            'upcoming_master_bus_bookings' => $total_upcoming_master_bus_bookings,
            'master_bus_bookings' => $total_bus_booking_masters,
            'bus_booking_is_coming_soon'=> $bus_booking_is_coming_soon,
        ];


        $site_states = (object)array(
            'users' => $users,
            'drivers' => $driver->approved,
            'totalCountry' => $merchant->Country->count(),
            'totalCountryArea' => $merchant->CountryArea->count(),
        );
        $countries = Country::where([['merchant_id', '=', $merchant->id], ['country_status', '=', 1]])->orderBy('sequance', 'ASC')->get();
        $reminder_days = Configuration::where('merchant_id', '=', $merchant_id)->select('reminder_doc_expire')->first();
        $expire_driver_doc = 0;
        $expire_class = new ExpireDocumentController;
        if (!empty($reminder_days)) {
            $currentDate = date('Y-m-d');
            $reminder_last_date = date('Y-m-d', strtotime('+' . $reminder_days->reminder_doc_expire . ' days'));
            $expire_driver_doc = $expire_class->getDocumentGoingToExpire($currentDate, $reminder_last_date, $merchant_id);
            $expire_driver_doc = $expire_driver_doc->count();
        }
        $taxi_states = App\Models\Segment::select('id', 'name', 'slag')
            ->with(['Booking' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id', 'booking_status', 'booking_closure')->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->where([['merchant_id', '=', $merchant_id]]);
                $q->where([['is_coming_soon', '=', 2]]);
            })
            ->with('Merchant', function ($q) use ($merchant_id) {
                $q->where([['merchant_id', '=', $merchant_id]]);
                $q->where([['is_coming_soon', '=', 2]]);
            })
            ->where('segment_group_id', 1)
            ->whereIn('id', [1, 2])
            ->get();
        $config= $merchant->Configuration;
        $taxi_states = $taxi_states->map(function ($segment) use ($config, $merchant_id) {
            $earning = DB::table('booking_transactions as bt')
                ->join('bookings as b', 'bt.booking_id', '=', 'b.id')
                ->where('b.segment_id', $segment->id)->where('b.merchant_id', $merchant_id)
                ->select('bt.id', 'bt.booking_id', 'b.segment_id', DB::raw('SUM(bt.discount_amount) as discount_amount'), DB::raw('SUM(bt.company_earning) as company_earning'), DB::raw('SUM(bt.booking_fee) as booking_fee'))
                ->first();

            $renewable_subscription_earning =0;
            $renewable_subscription_earning_wallet = 0;
            $renewable_subscription_earning_online = 0;
            $renewable_subscription_earning_active = false;
            if($config->subscription_package_type == 2 && $config->subscription_package == 1){
                $renewable_subscription_earning_active= true;
                $base = DB::table('driver_renewable_subscription_records as drs')
                    ->join('drivers as d', 'drs.driver_id', '=', 'd.id')
                    ->where('drs.segment_id', $segment->id)
                    ->where('d.merchant_id', $merchant_id);

                $renewable_subscription_earning        = (clone $base)
                    ->sum('drs.subscription_fee');

                $renewable_subscription_earning_online = (clone $base)
                    ->where('drs.payment_method_id', 4)
                    ->sum('drs.subscription_fee');

                $renewable_subscription_earning_wallet = (clone $base)
                    ->where('drs.payment_method_id', 3)
                    ->sum('drs.subscription_fee');

            }

            return (object)[
                'id' => $segment->id,
                'is_coming_soon' => isset($segment->Merchant[0]['pivot']->is_coming_soon) ? $segment->Merchant[0]['pivot']->is_coming_soon : NULL,
                'slag' => $segment->slag,
                'name' => $segment->Name($merchant_id),
                'total_earning' => ($renewable_subscription_earning_active && !empty($renewable_subscription_earning)) ? $renewable_subscription_earning : ($earning->company_earning + $earning->booking_fee),
                'total_discount' => round($earning->discount_amount, 2),
                'renewable_subscription_earning' => isset($renewable_subscription_earning)? $renewable_subscription_earning : 0,
                'renewable_subscription_earning_online' => isset($renewable_subscription_earning_online) ? $renewable_subscription_earning_online : 0,
                'renewable_subscription_earning_wallet' => isset($renewable_subscription_earning_online) ? $renewable_subscription_earning_wallet : 0,
                'ride' => (object)[
                    'all_rides' => $segment->Booking->count(),
                    'completed' => $segment->Booking->where('booking_status', 1005)->count(),
                    'ongoing' => $segment->Booking->whereIn('booking_status', [1001, 1012, 1002, 1003, 1004])->count(),
                    'cancelled' => $segment->Booking->whereIn('booking_status', [1006, 1007, 1008])->count(),
                    'auto_cancelled' => $segment->Booking->whereIn('booking_status', [1016, 1018])->count(),
                ]
            ];
        });
        $corporates = $merchant->Corporate->count();
        $home_delivery = App\Models\Segment::select('id', 'name', 'slag')
            ->with(['BusinessSegment' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id')->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->with(['Order' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id')->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->with(['Product' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id')->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->with(['Category' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id')->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->where([['merchant_id', '=', $merchant_id]]);
                $q->where([['is_coming_soon', '=', 2]]);
            })
            ->with('Merchant', function ($q) use ($merchant_id) {
                $q->where([['merchant_id', '=', $merchant_id]]);
                $q->where([['is_coming_soon', '=', 2]]);
            })
            ->where('segment_group_id', 1)
            ->whereIn('sub_group_for_app', [1, 2])
            ->orderBy('sub_group_for_app')
            ->get();

        $home_delivery->map(function ($segment) use ($merchant_id) {
            $earning = DB::table('booking_transactions as bt')
                ->join('orders as o', 'bt.order_id', '=', 'o.id')
                ->where('o.segment_id', $segment->id)->where('o.merchant_id', $merchant_id)
                ->select('bt.id', 'bt.booking_id', 'o.segment_id', DB::raw('SUM(bt.discount_amount) as discount_amount'), DB::raw('SUM(bt.company_gross_total) as company_earning'))
                ->first();

            $segment['total_earning'] = $earning->company_earning;
            $segment['total_discount'] = $earning->discount_amount;
            return $segment;
        });
        // handyman segments
        $handyman_booking_statistics = NULL;
        $handyman_booking_statistics = App\Models\Segment::select('id', 'name', 'slag')
            ->with(['HandymanOrder' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id', 'order_status')->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->where([['merchant_id', '=', $merchant_id]]);
                $q->where([['is_coming_soon', '=', 2]]);
            })
            ->with('Merchant', function ($q) use ($merchant_id) {
                $q->where([['merchant_id', '=', $merchant_id]]);
                $q->where([['is_coming_soon', '=', 2]]);
            })
            ->where('segment_group_id', 2)
            ->get();
        $handyman_booking_statistics = $handyman_booking_statistics->map(function ($segment) use ($merchant_id) {

            $earning = DB::table('booking_transactions as bt')
                ->join('handyman_orders as ho', 'bt.handyman_order_id', '=', 'ho.id')
                ->where('ho.segment_id', $segment->id)->where('ho.merchant_id', $merchant_id)
                ->select('bt.id', 'bt.booking_id', 'ho.segment_id', DB::raw('SUM(bt.discount_amount) as discount_amount'), DB::raw('SUM(bt.company_earning) as company_earning'))
                ->first();
            return (object)[
                'id' => $segment->id,
                'is_coming_soon' => $segment->Merchant[0]['pivot']->is_coming_soon,
                'slag' => $segment->slag,
                'name' => $segment->Name($merchant_id),
                'total_earning' => $earning->company_earning,
                'total_discount' => $earning->discount_amount,
                'booking' => (object)[
                    'all_bookings' => $segment->HandymanOrder->count(),
                    'completed' => $segment->HandymanOrder->where('order_status', 7)->count(),
                    'ongoing' => $segment->HandymanOrder->where('order_status', 6)->count(),
                    'cancelled' => $segment->HandymanOrder->whereIn('order_status', [2])->count(),
                ]
            ];
        });
        $total_earning = 0;
        $total_discount = 0;
        if (!empty($taxi_states)) {
            $total_earning = $total_earning + array_sum(array_pluck($taxi_states, 'total_earning'));
            $total_discount = $total_discount + array_sum(array_pluck($taxi_states, 'total_discount'));
        }
        if (!empty($home_delivery)) {
            $total_earning = $total_earning + array_sum(array_pluck($home_delivery, 'total_earning'));
            $total_discount = $total_discount + array_sum(array_pluck($home_delivery, 'total_discount'));
        }
        if (!empty($handyman_booking_statistics)) {
            $total_earning = $total_earning + array_sum(array_pluck($handyman_booking_statistics, 'total_earning'));
            $total_discount = $total_discount + array_sum(array_pluck($handyman_booking_statistics, 'total_discount'));
        }
        
        $handyman_disputed_order = HandymanOrder::where([['merchant_id','=',$merchant_id],['order_status','=',10]])->count();
        $handyman_service_providers_wallet_low = Driver::where([
            ['segment_group_id', 2],
            ['merchant_id', $merchant_id],

        ])
            ->whereNotNull('wallet_money')
            ->whereNull('driver_delete')
            ->where(DB::raw('CAST(wallet_money AS DOUBLE)'), '<', 0)
            ->count();
        $site_states->total_earning = $total_earning;
        $site_states->total_discount = round($total_discount, 2);
        $site_states->total_discount = $total_discount;
        $site_states->handyman_dispute_orders = $handyman_disputed_order;
        $site_states->handyman_service_providers_wallet_low = $handyman_service_providers_wallet_low;
        return view('merchant.home', compact('site_states', 'taxi_states', 'merchant', 'handyman_booking_statistics', 'merchant_id', 'expire_driver_doc', 'corporates', 'home_delivery', 'countries', 'merchant_segment_group_config', 'carpooling_states', 'bus_booking_states', 'handyman_service_providers_wallet_low'));
    }

    public function DashboardFilter(Request $request)
    {
        $merchant = get_merchant_id(false);
        Session::put('demo_otp', '');
        $merchant_id = $merchant->id;
        $request->merge(['merchant_id' => $merchant_id]);
        $driver = $this->getDriverSummary($request);
        $users = User::select('id')->where(array('taxi_company_id' => NULL, 'user_delete' => NULL, 'merchant_id' => $merchant_id))->count();
        if ($request->country_id) {
            $users = User::select('id')->where(array('taxi_company_id' => NULL, 'user_delete' => NULL, 'merchant_id' => $merchant_id, 'country_id' => $request->country_id))->count();
        }

        $site_states = (object)array(
            'users' => $users,
            'drivers' => $driver->approved,
            'totalCountry' => $merchant->Country->count(),
            'totalCountryArea' => $merchant->CountryArea->count(),
        );
        $countries = Country::where([['merchant_id', '=', $merchant->id], ['country_status', '=', 1]])->orderBy('sequance', 'ASC')->get();
        $reminder_days = Configuration::where('merchant_id', '=', $merchant_id)->select('reminder_doc_expire')->first();
        $expire_driver_doc = 0;
        $expire_class = new ExpireDocumentController;
        if (!empty($reminder_days)) {
            $currentDate = date('Y-m-d');
            $reminder_last_date = date('Y-m-d', strtotime('+' . $reminder_days->reminder_doc_expire . ' days'));
            $expire_driver_doc = $expire_class->getDocumentGoingToExpire($currentDate, $reminder_last_date, $merchant_id);
            $expire_driver_doc = $expire_driver_doc->count();
        }
        $taxi_states = App\Models\Segment::select('id', 'name', 'slag')
            ->with(['Booking' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id', 'booking_status', 'booking_closure'
                )->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->where([['merchant_id', '=', $merchant_id]]);
            })
            ->where('segment_group_id', 1)
            ->whereIn('id', [1, 2])
            ->get();
        $taxi_states = $taxi_states->map(function ($segment) use ($merchant_id) {
            $earning = DB::table('booking_transactions as bt')
                ->join('bookings as b', 'bt.booking_id', '=', 'b.id')
                ->where('b.segment_id', $segment->id)->where('b.merchant_id', $merchant_id)
                ->select('bt.id', 'bt.booking_id', 'b.segment_id', DB::raw('SUM(bt.discount_amount) as discount_amount'), DB::raw('SUM(bt.company_earning) as company_earning'))
                ->first();
            return (object)[
                'id' => $segment->id,
                'is_coming_soon' => isset($segment->Merchant[0]) ? $segment->Merchant[0]['pivot']->is_coming_soon : NULL,
                'slag' => $segment->slag,
                'name' => $segment->Name($merchant_id),
                'total_earning' => $earning->company_earning,
                'total_discount' => $earning->discount_amount,
                'ride' => (object)[
                    'all_rides' => $segment->Booking->count(),
                    'completed' => $segment->Booking->where('booking_status', 1005)->where('booking_closure', 1)->count(),
                    'ongoing' => $segment->Booking->whereIn('booking_status', [1001, 1012, 1002, 1003, 1004, 1005])->where('booking_closure', NULL)->count(),
                    'cancelled' => $segment->Booking->whereIn('booking_status', [1006, 1007, 1008])->count(),
                    'auto_cancelled' => $segment->Booking->whereIn('booking_status', [1016, 1018])->count(),
                ]
            ];
        });
        $corporates = $merchant->Corporate->count();
        $home_delivery = App\Models\Segment::select('id', 'name', 'slag')
            ->with(['BusinessSegment' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id')->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->with(['Order' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id')->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->with(['Product' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id')->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->with(['Category' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id')->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->where([['merchant_id', '=', $merchant_id]]);
            })
            ->where('segment_group_id', 1)
            ->whereIn('sub_group_for_app', [1, 2])
            ->orderBy('sub_group_for_app')
            ->get();

        $home_delivery->map(function ($segment) use ($merchant_id) {
            $earning = DB::table('booking_transactions as bt')
                ->join('orders as o', 'bt.order_id', '=', 'o.id')
                ->where('o.segment_id', $segment->id)->where('o.merchant_id', $merchant_id)
                ->select('bt.id', 'bt.booking_id', 'o.segment_id', DB::raw('SUM(bt.discount_amount) as discount_amount'), DB::raw('SUM(bt.company_gross_total) as company_earning'))
                ->first();

            $segment['total_earning'] = $earning->company_earning;
            $segment['total_discount'] = $earning->discount_amount;
            return $segment;
        });
        // handyman segments
        $handyman_booking_statistics = NULL;
        $handyman_booking_statistics = App\Models\Segment::select('id', 'name', 'slag')
            ->with(['HandymanOrder' => function ($q) use ($merchant_id) {
                $q->select('segment_id', 'id', 'order_status'
                )->where([['merchant_id', '=', $merchant_id]]);
            }])
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->where([['merchant_id', '=', $merchant_id]]);
            })
            ->where('segment_group_id', 2)
            ->get();
        $handyman_booking_statistics = $handyman_booking_statistics->map(function ($segment) use ($merchant_id) {

            $earning = DB::table('booking_transactions as bt')
                ->join('handyman_orders as ho', 'bt.handyman_order_id', '=', 'ho.id')
                ->where('ho.segment_id', $segment->id)->where('ho.merchant_id', $merchant_id)
                ->select('bt.id', 'bt.booking_id', 'ho.segment_id', DB::raw('SUM(bt.discount_amount) as discount_amount'), DB::raw('SUM(bt.company_earning) as company_earning'))
                ->first();
            return (object)[
                'id' => $segment->id,
                'is_coming_soon' => $segment->Merchant[0]['pivot']->is_coming_soon,
                'slag' => $segment->slag,
                'name' => $segment->Name($merchant_id),
                'total_earning' => $earning->company_earning,
                'total_discount' => $earning->discount_amount,
                'booking' => (object)[
                    'all_bookings' => $segment->HandymanOrder->count(),
                    'completed' => $segment->HandymanOrder->where('order_status', 7)->count(),
                    'ongoing' => $segment->HandymanOrder->where('order_status', 6)->count(),
                    'cancelled' => $segment->HandymanOrder->whereIn('order_status', [5, 2])->count(),
                ]
            ];
        });
        $total_earning = 0;
        $total_discount = 0;
        if (!empty($taxi_states)) {
            $total_earning = $total_earning + array_sum(array_pluck($taxi_states, 'total_earning'));
            $total_discount = $total_discount + array_sum(array_pluck($taxi_states, 'total_discount'));
        }
        if (!empty($home_delivery)) {
            $total_earning = $total_earning + array_sum(array_pluck($home_delivery, 'total_earning'));
            $total_discount = $total_discount + array_sum(array_pluck($home_delivery, 'total_discount'));
        }
        if (!empty($handyman_booking_statistics)) {
            $total_earning = $total_earning + array_sum(array_pluck($handyman_booking_statistics, 'total_earning'));
            $total_discount = $total_discount + array_sum(array_pluck($handyman_booking_statistics, 'total_discount'));
        }
        $site_states->total_earning = $total_earning;
        $site_states->total_discount = $total_discount;
        $data = $request->all();
        return view('merchant.home', compact('site_states', 'taxi_states', 'merchant', 'handyman_booking_statistics', 'merchant_id', 'expire_driver_doc', 'corporates', 'home_delivery', 'countries', 'data', 'merchant_segment_group_config'));
    }

    public function webPlayerIdSubscription(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $business_segment_id = $request->business_segment_id;
        $status = $request->status;
        $active = MerchantWebOneSignal::updateOrCreate(
            ['merchant_id' => $merchant_id, 'business_segment_id' => $business_segment_id, 'player_id' => $request->player_id],
            ['status' => $status, 'merchant_id' => $merchant_id, 'business_segment_id' => $business_segment_id, 'player_id' => $request->player_id]
        );
        echo "success";
    }

    public function Configuration()
    {
        $checkPermission = check_permission(1, 'view_configuration');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $languages = Merchant::with('Language')->find($merchant_id);
        $service_types = $languages->Service;
        $languages = $languages->language;
        $configuration = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
        return view('merchant.random.configuration', compact('service_types', 'configuration', 'languages'));
    }

    public function StoreConfiguration(Request $request)
    {
        $checkPermission = check_permission(1, 'edit_configuration');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $request->validate([
            'driver_wallet' => 'required|integer|between:1,2',
            'google_key' => 'required',
            'distance' => 'required|numeric',
            'number_of_driver' => 'integer',
            'report_issue_email' => 'required|email',
            'report_issue_phone' => 'required',
            'number_of_driver_user_map' => 'required|integer',
            'location_update_timeband' => 'required|integer',
            'tracking_screen_refresh_timeband' => 'required|integer',
            'ride_later_request' => 'integer|between:1,2',
            'ride_later_request_number_driver' => 'integer',
            'distance_ride_later' => 'integer',
            'android_user_maintenance_mode' => 'required|integer|between:1,2',
            'android_user_version' => 'required',
            'android_user_mandatory_update' => 'required|integer|between:1,2',
            'ios_user_maintenance_mode' => 'required|integer|between:1,2',
            'ios_user_version' => 'required',
            'ios_user_mandatory_update' => 'required|integer|between:1,2',
            'android_driver_maintenance_mode' => 'required|integer|between:1,2',
            'android_driver_mandatory_update' => 'required|integer|between:1,2',
            'ios_driver_maintenance_mode' => 'required|integer|between:1,2',
            'ios_driver_mandatory_update' => 'required|integer|between:1,2',
            'outstation_request_type' => 'integer|between:1,2',
            'home_screen' => 'required|integer|between:1,2',
            'pool_radius' => 'numeric',
            'driver_request_timeout' => 'integer',
            'outstation_time_before' => 'integer',
            'no_driver_outstation' => 'integer',
            'ride_later_time_before' => 'integer',
            'outstation_radius' => 'numeric',
            'pool_drop_radius' => 'numeric',
            'no_of_drivers' => 'integer',
            'maximum_exceed' => 'integer',
            'outstation_time' => 'integer',
            'android_driver_version' => 'required',
            'ios_driver_version' => 'required',
            'user_wallet_amount' => 'required',
            'driver_wallet_amount' => 'required',
            'ride_later_hours' => 'integer',
            'default_language' => 'required',
        ]);
        $userWallet = array();
        foreach ($request->user_wallet_amount as $value) {
            $userWallet[] = array('amount' => $value);
        }
        $driverWallet = array();
        foreach ($request->driver_wallet_amount as $value) {
            $driverWallet[] = array('amount' => $value);
        }
        Configuration::updateOrCreate(
            ['merchant_id' => $merchant_id],
            [
                'driver_wallet_status' => $request->driver_wallet,
                'google_key' => $request->google_key,
                'distance' => $request->distance,
                'number_of_driver' => $request->number_of_driver,
                'report_issue_email' => $request->report_issue_email,
                'report_issue_phone' => $request->report_issue_phone,
                'number_of_driver_user_map' => $request->number_of_driver_user_map,
                'location_update_timeband' => $request->location_update_timeband,
                'tracking_screen_refresh_timeband' => $request->tracking_screen_refresh_timeband,
                'ride_later_request' => $request->ride_later_request,
                'ride_later_request_number_driver' => $request->ride_later_request_number_driver,
                'distance_ride_later' => $request->distance_ride_later,
                'android_user_maintenance_mode' => $request->android_user_maintenance_mode,
                'android_user_version' => $request->android_user_version,
                'android_user_mandatory_update' => $request->android_user_mandatory_update,
                'ios_user_maintenance_mode' => $request->ios_user_maintenance_mode,
                'ios_user_version' => $request->ios_user_version,
                'ios_user_mandatory_update' => $request->ios_user_mandatory_update,
                'android_driver_maintenance_mode' => $request->android_driver_maintenance_mode,
                'android_driver_version' => $request->android_driver_version,
                'android_driver_mandatory_update' => $request->android_driver_mandatory_update,
                'ios_driver_maintenance_mode' => $request->ios_driver_maintenance_mode,
                'ios_driver_version' => $request->ios_driver_version,
                'ios_driver_mandatory_update' => $request->ios_driver_mandatory_update,
                'driver_request_timeout' => $request->driver_request_timeout,
                'ride_later_hours' => $request->ride_later_hours,
                'default_language' => $request->default_language,
                'ride_later_time_before' => $request->ride_later_time_before,
                'outstation_time' => $request->outstation_time,
                'outstation_request_type' => $request->outstation_request_type,
                'no_driver_outstation' => $request->no_driver_outstation,
                'outstation_time_before' => $request->outstation_time_before,
                'outstation_radius' => $request->outstation_radius,
                'home_screen' => $request->home_screen,
                'pool_radius' => $request->pool_radius,
                'pool_drop_radius' => $request->pool_drop_radius,
                'no_of_drivers' => $request->no_of_drivers,
                'maximum_exceed' => $request->maximum_exceed,
                'user_wallet_amount' => json_encode($userWallet, true),
                'driver_wallet_amount' => json_encode($driverWallet, true),
            ]
        );
        VersionManagement::updateVersion($merchant_id);
        return redirect()->back()->with('configuration', 'Updated');
    }

    public function Ratings()
    {
        $checkPermission = check_permission(1, 'ratings');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $ratings = $this->getAllRating();
        $data = [];
        return view('merchant.random.ratings', compact('ratings', 'data'));
    }

    public function RatingsDelivery()
    {
        $checkPermission = check_permission(1, 'ratings');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $ratings = $this->getAllRatingDelivery();
        return view('merchant.random.ratings', compact('ratings'));
    }


    public function SearchRating(Request $request)
    {
        $query = $this->getAllRating(false);
        if ($request->booking_id) {
            $keyword = $request->booking_id;
            $query->WhereHas('Booking', function ($q) use ($keyword) {
                $q->where('merchant_booking_id', $keyword);
            });
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('Booking', function ($q) use ($keyword) {
                $q->WhereHas('User', function ($qu) use ($keyword) {
                    $qu->where('UserName', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
                });
            });
        }
        if ($request->driver) {
            $driverKeyword = $request->driver;
            $query->WhereHas('Booking', function ($q) use ($driverKeyword) {
                $q->WhereHas('Driver', function ($qu) use ($driverKeyword) {
                    $qu->where('fullName', 'LIKE', "%$driverKeyword%")->orWhere('email', 'LIKE', "%$driverKeyword%")->orWhere('phoneNumber', 'LIKE', "%$driverKeyword%");
                });
            });
        }
        $ratings = $query->paginate(25);
        $data = $request->all();
        return view('merchant.random.ratings', compact('ratings', 'data'));
    }

    public function SearchRatingDelivery(Request $request)
    {
        $query = $this->getAllRatingDelivery(false);
        if ($request->booking_id) {
            $query->where('booking_id', $request->booking_id);
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('Booking', function ($q) use ($keyword) {
                $q->WhereHas('User', function ($qu) use ($keyword) {
                    $qu->where('UserName', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
                });
            });
        }
        if ($request->driver) {
            $driverKeyword = $request->driver;
            $query->WhereHas('Booking', function ($q) use ($driverKeyword) {
                $q->WhereHas('Driver', function ($qu) use ($driverKeyword) {
                    $qu->where('fullName', 'LIKE', "%$driverKeyword%")->orWhere('email', 'LIKE', "%$driverKeyword%")->orWhere('phoneNumber', 'LIKE', "%$driverKeyword%");
                });
            });
        }
        $ratings = $query->paginate(25);
        return view('merchant.random.ratings', compact('ratings'));
    }

    public function ServiceType()
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $services = ServiceType::where([['merchant_id', '=', $merchant_id]])->get();
        return view('merchant.service', compact('services'));
    }

    public function OneSignal()
    {
        $checkPermission = check_permission(1, 'view_onesignal');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $arr_food_grocery = get_merchant_segment(false, $merchant_id, 1, 2);
        $food_grocery = !empty($arr_food_grocery) ? count($arr_food_grocery) > 0 == true : false;
        $is_demo = $merchant->demo == 1 ? true : false;
        $onesignal = Onesignal::where([['merchant_id', '=', $merchant_id]])->first();
        $info_setting = InfoSetting::where('slug', 'PUSH_NOTIFICATION_CONFIGURATION')->first();
        return view('merchant.random.onesignal', compact('onesignal', 'info_setting', 'is_demo', 'food_grocery'));
    }

    public function UpdateOneSignal(Request $request)
    {
//        p($request->all());
        $checkPermission = check_permission(1, 'edit_onesignal');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = get_merchant_id();
        $config = get_merchant_notification_provider($merchant_id, null, null, 'full');
        $string_file = $this->getStringFile($merchant_id);
        $merchant_config = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
        $arr_fields = [];
        if (isset($config->fire_base) && $config->fire_base == true) {
            $request->validate([
                'pem_password_user' => 'required',
                'pem_password_driver' => 'required',
                'firebase_api_key_android' => 'required',
            ]);

            $arr_fields = [
                'firebase_api_key_android' => $request->firebase_api_key_android,
                'pem_password_driver' => $request->pem_password_driver,
                'pem_password_user' => $request->pem_password_user,
            ];
            /* upload pem file of user and driver*/
            if ($request->hasFile('firebase_ios_pem_user')) {
                $pem_file_user = $request->file('firebase_ios_pem_user');
//                p($pem_file_user);
                $user_filename = $pem_file_user->getClientOriginalName();
                $pem_file_user->move(public_path('pem-file'), $user_filename);
                $arr_fields['firebase_ios_pem_user'] = $user_filename;
            }

            if ($request->hasFile('firebase_ios_pem_driver')) {
                $pem_file_driver = $request->file('firebase_ios_pem_driver');
                $driver_filename = $pem_file_driver->getClientOriginalName();
                $pem_file_driver->move('pem-file', $driver_filename);
                $arr_fields['firebase_ios_pem_driver'] = $driver_filename;
            }

            if ($request->hasFile('firebase_project_file')) {
                $project_file = $request->file('firebase_project_file');
                $firebase_project_filename = $project_file->getClientOriginalName();
                $dst = storage_path().'/firebase/';
                $project_file->move($dst, $firebase_project_filename);
                $arr_fields['firebase_project_file'] = $firebase_project_filename;
            }

        } else {
            $validate_rule = [
                'user_application_key' => 'required',
                'user_rest_key' => 'required',
                'user_channel_id' => 'required',
            ];
            if (isset($merchant_config->driver_enable) && $merchant_config->driver_enable == 1) {
                $validate_rule = array_merge($validate_rule, [
                    'driver_application_key' => 'required',
                    'driver_rest_key' => 'required',
                    'driver_channel_id' => 'required',
                ]);
            }
            $request->validate($validate_rule);
            $arr_fields = [
                'user_application_key' => $request->user_application_key,
                'user_rest_key' => $request->user_rest_key,
                'user_channel_id' => $request->user_channel_id,
                'driver_application_key' => $request->driver_application_key,
                'driver_rest_key' => $request->driver_rest_key,
                'driver_channel_id' => $request->driver_channel_id,
                'web_application_key' => $request->web_application_key,
                'web_rest_key' => $request->web_rest_key,

                'business_segment_application_key' => $request->business_segment_application_key,
                'business_segment_rest_key' => $request->business_segment_rest_key,
                'business_segment_channel_id' => $request->business_segment_channel_id,
            ];
        }
        Onesignal::updateOrCreate(
            ['merchant_id' => $merchant_id],
            $arr_fields
        );
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function Customer_Support()
    {
        $checkPermission = check_permission(1, 'customer_support');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $customer_supports = CustomerSupport::where([['merchant_id', '=', $merchant_id]])->orderBy('created_at', 'DESC')->paginate(25);
        $info_setting = InfoSetting::where('slug', 'CUSTOMER_SUPPORT')->first();
        return view('merchant.random.customer_support', compact('customer_supports', 'info_setting'));
    }

    public function Customer_Support_Search(Request $request)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $query = CustomerSupport::where([['merchant_id', '=', $merchant_id]]);
        if ($request->application) {
            $query->where('application', $request->application);
        }
        if ($request->date) {
            $query->whereDate('created_at', '=', $request->date);
        }
        if ($request->name) {
            $query->where('name', 'LIKE', "%$request->name%");
        }
        $customer_supports = $query->paginate(25);
        $info_setting = InfoSetting::where('slug', 'CUSTOMER_SUPPORT')->first();
        return view('merchant.random.customer_support', compact('customer_supports', 'info_setting'));
    }

    public function DistnaceIndex()
    {
        $checkPermission = check_permission(1, 'view_distnace');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $distance = DistanceSetting::where([['merchant_id', '=', $merchant_id]])->first();
        return view('merchant.random.distance', compact('distance'));
    }

    public function DistnaceCreate()
    {
        $checkPermission = check_permission(1, 'edit_distnace');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $methods = DistanceMethod::get();
        return view('merchant.random.create-distance', compact('methods'));
    }

    public function DistnaceStore(Request $request)
    {
        $this->validate($request, [
            'method_id' => 'required'
        ]);
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $methods = $request->method_id;
        $methods = array_filter($methods);
        $distanceSettings = array();
        foreach ($methods as $key => $value) {
            $logic = $key;
            $method_id = $value;
            $distance_method = DistanceMethod::find($method_id);
            $method_name = $distance_method->method;
            switch ($logic) {
                case "0":
                    $distanceSettings[] = array(
                        'method_id' => $method_id,
                        'method_name' => $method_name,
                        'last_timestamp_difference' => $request->last_timestamp_difference_one,
                        'maximum_timestamp_difference' => $request->maximum_timestamp_difference_one,
                        'minimum_lat_long' => $request->minimum_lat_long_one,
                        'unknown_road' => $request->unknown_road_one,
                        'min_speed' => $request->min_speed_one,
                        'max_speed' => $request->max_speed_one
                    );
                    break;
                case "1":
                    $distanceSettings[] = array(
                        'method_id' => $method_id,
                        'method_name' => $method_name,
                        'last_timestamp_difference' => $request->last_timestamp_difference_second,
                        'maximum_timestamp_difference' => $request->maximum_timestamp_difference_second,
                        'minimum_lat_long' => $request->minimum_lat_long_second,
                        'unknown_road' => $request->unknown_road_second,
                        'min_speed' => $request->min_speed_second,
                        'max_speed' => $request->max_speed_second
                    );
                    break;
                case "2":
                    $distanceSettings[] = array(
                        'method_id' => $method_id,
                        'method_name' => $method_name,
                        'last_timestamp_difference' => $request->last_timestamp_difference_third,
                        'maximum_timestamp_difference' => $request->maximum_timestamp_difference_third,
                        'minimum_lat_long' => $request->minimum_lat_long_third,
                        'unknown_road' => $request->unknown_road_third,
                        'min_speed' => $request->min_speed_third,
                        'max_speed' => $request->max_speed_third
                    );
                    break;
                case "3":
                    $distanceSettings[] = array(
                        'method_id' => $method_id,
                        'method_name' => $method_name,
                        'last_timestamp_difference' => "",
                        'maximum_timestamp_difference' => "",
                        'minimum_lat_long' => "",
                        'unknown_road' => "",
                        'min_speed' => "",
                        'max_speed' => ""
                    );
                    break;
            }
        }
        DistanceSetting::updateOrCreate(
            ['merchant_id' => $merchant_id],
            [
                'distance_methods' => json_encode($distanceSettings, true),
            ]
        );
        return redirect()->route('merchant.distnace');
    }

    public function LanguageStrings()
    {
        $merchant_id = get_merchant_id();
        $string = [];
//            $this->langaugeString(App::getLocale());
        $language_strings = LanguageString::with('LanguageSingleMessage')->get();
        $exist_data = LanguageStringTranslation::where([['locale', '=', App::getLocale()], ['merchant_id', '=', $merchant_id]])->get();
        if ($exist_data->count() > 0) {
            $string = [];
        }
        $info_setting = App\Models\InfoSetting::where('slug', 'LANGUAGE_STRING')->first();
        return view('merchant.random.languagestrings', compact('language_strings', 'string', 'info_setting'));
    }

    public function UpdateLanguageString(Request $request)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        foreach ($request->name as $key => $value) {
            if ($value) {
                $this->SaveMessage($key, $merchant_id, $value);
            }
        }
        return redirect()->back()->with('languageString', trans('admin.languageString'));
    }

    public function SaveMessage($language_string_id, $merchant_id, $value)
    {
        LanguageStringTranslation::updateOrCreate([
            'language_string_id' => $language_string_id,
            'merchant_id' => $merchant_id,
            'locale' => App::getLocale()
        ], [
            'name' => $value
        ]);
    }

    public function SetLangauge(Request $request, $locle)
    {
        $request->session()->put('locale', $locle);
        return redirect()->back();
    }

    public function profile()
    {
        return view('merchant.random.edit-profile');
    }

    public function ProfileUpdate(Request $request)
    {
        $request->validate([
            'merchantFirstName' => "required",
            'merchantLastName' => "required",
            'merchantAddress' => "required",
            'merchantPhone' => 'required',
//            'login_background_image' => 'mimes:jpeg,png,jpg,gif,svg|dimensions:min_width=1280,min_height=980',
            'login_background_image' => 'mimes:jpeg,png,jpg,gif,svg',
            'password' => 'required_if:edit_password,1'
        ]);
//        $merchant = get_merchant_id(false);
        $merchant = $request->User();
        $merchant_id = ($merchant->parent_id == 0)? $merchant->id: $merchant->parent_id;
        $string_file = $this->getStringFile(NULL, $merchant);
        if ($request->hasFile('business_logo')) {
            $merchant->BusinessLogo = $this->uploadImage('business_logo', 'business_logo');
        }
        if ($request->hasFile('login_background_image')) {
//            $theme = $merchant->ApplicationTheme;
            $theme = ApplicationTheme::where("merchant_id", $merchant_id)->first();
            $theme->merchant_id = $merchant_id;
            $theme->login_background_image = $this->uploadImage('login_background_image', 'login_background');
            $theme->save();
        }
        $merchant->merchantFirstName = $request->merchantFirstName;
        $merchant->merchantLastName = $request->merchantLastName;
        $merchant->merchantAddress = $request->merchantAddress;
        $merchant->merchantPhone = $request->merchantPhone;
        if ($request->edit_password == 1) {
            $password = Hash::make($request->password);
            $merchant->password = $password;
        }
        $merchant->save();
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function OfferedUser()
    {
        $merchant_id = get_merchant_id('false');
        $user = User::where('merchant_id', $merchant_id)->get();
    }

    public function givePermissionToSuperAdmin(Request $request)
    {
        $roles = Role::get();
        foreach ($roles as $role) {
            if ($role->name == "Super Admin" . $role->merchant_id) {
                $permissions = Permission::where("permission_type", 1)->get()->pluck('id')->toArray();
                $role->syncPermissions($permissions);
            }
        }
        p("Done");
    }

    public function packageWiseOneSignal()
    {
        $checkPermission = check_permission(1, 'view_onesignal');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = get_merchant_id();
        $onesignals = App\Models\DefaultOnesignal::where([['merchant_id', '=', $merchant_id]])->latest()->paginate(15);
        $info_setting = InfoSetting::where('slug', 'PUSH_NOTIFICATION_CONFIGURATION')->first();
        return view('merchant.random.packagewise-onesignal', compact('onesignals', 'info_setting'));
    }

    public function addPackageWiseOneSignal($id = null)
    {
        $checkPermission = check_permission(1, 'view_onesignal');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = get_merchant_id(true);
        $onesignal = [];
        if ($id != null) {
            $onesignal = App\Models\DefaultOnesignal::where([['merchant_id', '=', $merchant_id]])->find($id);
        }
        $info_setting = InfoSetting::where('slug', 'PUSH_NOTIFICATION_CONFIGURATION')->first();
        return view('merchant.random.add-packagewise-onesignal', compact('onesignal', 'info_setting'));
    }

    public function savePacekageWiseOneSignal(Request $request, $id = null)
    {
        $merchant_id = get_merchant_id();
        $request->merge(["id" => $id]);
        $request->validate([
            'package_name' => "required_if:id,|max:255|unique:default_onesignals,package_name," . $id . ",id,merchant_id," . $merchant_id,
        ]);
        $string_file = $this->getStringFile($merchant_id);
        $arr_fields = [
            'user_application_key' => $request->user_application_key,
            'user_rest_key' => $request->user_rest_key,
            'user_channel_id' => $request->user_channel_id,
            'driver_application_key' => $request->driver_application_key,
            'driver_rest_key' => $request->driver_rest_key,
            'driver_channel_id' => $request->driver_channel_id,
            'web_application_key' => $request->web_application_key,
            'web_rest_key' => $request->web_rest_key,

            'business_segment_application_key' => $request->business_segment_application_key,
            'business_segment_rest_key' => $request->business_segment_rest_key,
            'business_segment_channel_id' => $request->business_segment_channel_id,
        ];
        if ($id == null) {
            $arr_fields['package_name'] = $request->package_name;
            $arr_fields['merchant_id'] = $merchant_id;
            App\Models\DefaultOnesignal::create($arr_fields);
        } else {
            App\Models\DefaultOnesignal::where("id", $id)->update($arr_fields);
        }

        return redirect()->route("merchant.packagewise.onesignal")->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function uploadUsers()
    {
        Config::set('database.connections.mysql', array(
            'driver' => 'mysql',
            'host' => '18.170.158.75',
            'port' => 3306,
            'database' => 'msprojectsappori_bforth',
            'username' => 'msprojectsappori_bforth',
            'password' => 'eT(Unf)tAd_k',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ));
        DB::purge('mysql');
        DB::reconnect('mysql');

        $users = User::where('user_delete', NULL)->get();
        $this->connectTargetDB();
        $merchant = Merchant::where('id', 596)->first();
        DB::beginTransaction();
        try {
            if (!empty($users)) {
                foreach ($users as $user) {
                    $user_arr = $user->toArray();
                    unset($user_arr['id']);
                    unset($user_arr['created_at']);
                    unset($user_arr['updated_at']);
                    $user_arr['merchant_id'] = $merchant->id;
                    $user_arr['country_id'] = $user->country_id == 287 ? 1457 : 1454;
                    $user_arr['country_area_id'] = NULL;
                    User::create($user_arr);
                }
            }
            DB::commit();
            return redirect()->back()->withSuccess("User Copied successfully");
        } catch (\Exception $exception) {
            DB::rollback();
            return redirect()->back()->withErrors($exception->getMessage());
        }
    }

    public function connectTargetDB()
    {
        Config::set('database.connections.mysql', array(
            'driver' => 'mysql',
            'host' => '18.170.158.75',
            'port' => env('DB_PORT', '3306'),
            'database' => 'msprojectsappori_carpooling',
            'username' => 'msprojec_ms_v2',
            'password' => '$6k&(dW=R7v4',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ));
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    public function uploadVehicleTypes()
    {
        ini_set('max_execution_time', '90000');
        $merch = new App\Http\Controllers\SpAdmin\MerchantController();
        $source_vehicle_types = $merch->getSourceVehicleTypes(137);
        $source_vehicle_makes = $merch->getSourceVehicleMakes(137);
        $source_vehicle_models = $merch->getSourceVehicleModels(137);

        $this->connectTargetDB();
        //inserting Vehicle Types data
        if (!empty($source_vehicle_types)) {
            $mapped_vehicle_type_array = $merch->uploadVehicleTypes($source_vehicle_types, 596);
            p($mapped_vehicle_type_array, 0);
            p('Vehicle Type Inserted.', 0);
        }

        //inserting Vehicle Makes data
        if (!empty($source_vehicle_makes)) {
            $mapped_vehicle_make_array = $merch->uploadVehicleMakes($source_vehicle_makes, 596);
            p($mapped_vehicle_make_array, 0);
            p('Vehicle Make Inserted.', 0);
        }

        //inserting Vehicle Models data
        if (!empty($source_vehicle_models) && !empty($mapped_vehicle_type_array) && !empty($mapped_vehicle_make_array)) {
            $mapped_vehicle_model_array = $merch->uploadVehicleModels($source_vehicle_models, 596, $mapped_vehicle_type_array, $mapped_vehicle_make_array);
            p($mapped_vehicle_model_array, 0);
            p('Vehicle Model Inserted.', 0);
        }
        die('done');
    }

    public function uploadDrivers()
    {
        ini_set('max_execution_time', '90000');
        $merch = new App\Http\Controllers\SpAdmin\MerchantController();
        $mapped_vehicle_type_array = [
            ['source_id' => 276, 'target_id' => 1207],
            ['source_id' => 307, 'target_id' => 1208],
            ['source_id' => 379, 'target_id' => 1209],
            ['source_id' => 380, 'target_id' => 1210],
            ['source_id' => 387, 'target_id' => 1211],
        ];

        $mapped_vehicle_make_array = [
            ['source_id' => 475, 'target_id' => 2923],
            ['source_id' => 500, 'target_id' => 2924],
            ['source_id' => 668, 'target_id' => 2925],
            ['source_id' => 669, 'target_id' => 2926],
            ['source_id' => 670, 'target_id' => 2927],
            ['source_id' => 671, 'target_id' => 2928],
            ['source_id' => 672, 'target_id' => 2929],
            ['source_id' => 673, 'target_id' => 2930],
        ];

        $mapped_vehicle_model_array = [
            ['source_id' => 2077, 'target_id' => 9525],
            ['source_id' => 2104, 'target_id' => 9526],
            ['source_id' => 2638, 'target_id' => 9527],
            ['source_id' => 2639, 'target_id' => 9528],
            ['source_id' => 2678, 'target_id' => 9529],
            ['source_id' => 2881, 'target_id' => 9530],
            ['source_id' => 2924, 'target_id' => 9531],
            ['source_id' => 6552, 'target_id' => 9532],
            ['source_id' => 6553, 'target_id' => 9533],
            ['source_id' => 6561, 'target_id' => 9534],
            ['source_id' => 6562, 'target_id' => 9535],
            ['source_id' => 6563, 'target_id' => 9536],
            ['source_id' => 6564, 'target_id' => 9537],
        ];

        $mapped_document_array = [
            ['source_id' => 232, 'target_id' => 1311],
            ['source_id' => 233, 'target_id' => 1310],
            ['source_id' => 244, 'target_id' => 1309],
            ['source_id' => 293, 'target_id' => 1308],
            ['source_id' => 294, 'target_id' => 1307],
            ['source_id' => 295, 'target_id' => 1306],
            ['source_id' => 296, 'target_id' => 1304],
            ['source_id' => 324, 'target_id' => 1303],
            ['source_id' => 325, 'target_id' => 1305],
        ];

        // DB::beginTransaction();
        try {
            $drivers = App\Models\Driver::where('driver_delete', NULL)->get();
            foreach ($drivers as $driver) {
                $driver_arr = $driver->toArray();
                unset($driver_arr['id']);
                unset($driver_arr['created_at']);
                unset($driver_arr['updated_at']);
                $driver_arr['merchant_id'] = 596;
                $driver_arr['country_id'] = $driver->country_id == 287 ? 1457 : 1454;
                $driver_arr['country_area_id'] = 1589;
                $driver_new = DB::connection('mysql2')->table('drivers')->insertGetId($driver_arr);

                if (!empty($driver->DriverDocument->toArray())) {
                    foreach ($driver->DriverDocument as $doc) {
                        $doc_arr = $doc->toArray();
                        unset($doc_arr['id']);
                        unset($doc_arr['created_at']);
                        unset($doc_arr['updated_at']);
                        $doc_arr['driver_id'] = $driver_new;
                        $doc_arr['document_id'] = $merch->getTargetId($doc->document_id, $mapped_document_array);
                        DB::connection('mysql2')->table('driver_documents')->insert($doc_arr);
                    }
                    // p('Driver Document Inserted.', 0);
                }

                if (!empty($driver->Segment->toArray())) {
                    foreach ($driver->Segment as $segment) {
                        DB::connection('mysql2')->table('driver_segment')->insert([
                            'driver_id' => $driver_new,
                            'segment_id' => $segment->id
                        ]);
                    }
                    // p('Driver Segment Inserted.', 0);
                }

                if (!empty($driver->DriverSegmentDocument->toArray())) {
                    foreach ($driver->DriverSegmentDocument as $driver_seg_doc) {
                        $segment_doc_arr = $driver_seg_doc->toArray();
                        unset($segment_doc_arr['id']);
                        unset($segment_doc_arr['created_at']);
                        unset($segment_doc_arr['updated_at']);
                        $segment_doc_arr['driver_id'] = $driver_new;
                        $segment_doc_arr['document_id'] = $merch->getTargetId($driver_seg_doc->document_id, $mapped_document_array);
                        DB::connection('mysql2')->table('driver_segment_documents')->insert($segment_doc_arr);
                    }
                    // p('Driver Segment Document Inserted.', 0);
                }

                if (!empty($driver->ServiceType->toArray())) {
                    foreach ($driver->ServiceType as $service_type) {
                        $driver_service_type = $this->fetchTargetServiceType($service_type->pivot->service_type_id);
                        if (!empty($driver_service_type)) {
                            DB::connection('mysql2')->table('driver_service_type')->insert([
                                'driver_id' => $driver_new,
                                'segment_id' => $service_type->pivot->segment_id,
                                'service_type_id' => $service_type->pivot->service_type_id,
                            ]);
                        }
                    }
                    // p('Driver ServiceType Inserted.', 0);
                }

                if (!empty($driver->DriverVehicles->toArray())) {
                    foreach ($driver->DriverVehicles as $vehicle) {
                        $driver_vehicle_arr = $vehicle->toArray();
                        unset($driver_vehicle_arr['id']);
                        unset($driver_vehicle_arr['created_at']);
                        unset($driver_vehicle_arr['updated_at']);
                        $driver_vehicle_arr['merchant_id'] = 596;
                        $driver_vehicle_arr['driver_id'] = $driver_new;
                        $driver_vehicle_arr['vehicle_type_id'] = $merch->getTargetId($vehicle->vehicle_type_id, $mapped_vehicle_type_array);
                        $driver_vehicle_arr['vehicle_make_id'] = $merch->getTargetId($vehicle->vehicle_make_id, $mapped_vehicle_make_array);
                        $driver_vehicle_arr['vehicle_model_id'] = $merch->getTargetId($vehicle->vehicle_model_id, $mapped_vehicle_model_array);
                        $driver_vehicle_id = DB::connection('mysql2')->table('driver_vehicles')->insertGetId($driver_vehicle_arr);

                        if (!empty($vehicle->ServiceTypes->toArray())) {
                            foreach ($vehicle->ServiceTypes as $serviceType) {
                                $driver_veh_service_type = $this->fetchTargetServiceType($serviceType->pivot->service_type_id);
                                if (!empty($driver_veh_service_type)) {
                                    DB::connection('mysql2')->table('driver_vehicle_service_type')->insert([
                                        'driver_vehicle_id' => $driver_vehicle_id,
                                        'service_type_id' => $serviceType->pivot->service_type_id,
                                        'segment_id' => $serviceType->pivot->segment_id,
                                    ]);
                                }
                            }
                            // p('Driver Vehicle ServiceType Inserted.', 0);
                        }

                        if (!empty($vehicle->Drivers->toArray())) {
                            DB::connection('mysql2')->table('driver_driver_vehicle')->insert([
                                'driver_id' => $driver_new,
                                'driver_vehicle_id' => $driver_vehicle_id,
                                'vehicle_active_status' => $vehicle->Drivers[0]->pivot->vehicle_active_status
                            ]);
                        }

                        if (!empty($vehicle->DriverVehicleDocument->toArray())) {
                            foreach ($vehicle->DriverVehicleDocument as $vehicle_doc) {
                                $vehicle_doc_arr = $vehicle_doc->toArray();
                                unset($vehicle_doc_arr['id']);
                                unset($vehicle_doc_arr['created_at']);
                                unset($vehicle_doc_arr['updated_at']);
                                $vehicle_doc_arr['driver_vehicle_id'] = $driver_vehicle_id;
                                $vehicle_doc_arr['document_id'] = $merch->getTargetId($vehicle_doc->document_id, $mapped_document_array);
                                DB::connection('mysql2')->table('driver_vehicle_documents')->insert($vehicle_doc_arr);
                            }
                            // p('Driver Vehicle Document Inserted.', 0);
                        }
                    }
                    // p('Driver Vehicle Inserted.', 0);
                }
                \Log::channel('driver_copy_data')->emergency(['driver_id' => $driver_new, 'message' => "Copied!"]);
            }
            die('copied done');
            // p('Driver Inserted successfully', 0);
            // DB::commit();
            // return redirect()->back()->withSuccess("Driver & Vehicle Copied successfully");
        } catch (\Exception $exception) {
            // DB::rollback();
            dd($exception);
            // return redirect()->back()->withErrors($exception->getMessage());
        }
    }

    public function fetchTargetServiceType($source_service_id)
    {
        $this->connectTargetDB();
        $service_type = ServiceType::find($source_service_id);
        Config::set('database.connections.mysql', array(
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ));
        DB::purge('mysql');
        DB::reconnect('mysql');
        return $service_type;
    }

    public function uploadBusinessSegments()
    {
        ini_set('max_execution_time', '90000');
        try {
            $businessSegments = App\Models\BusinessSegment\BusinessSegment::where([['merchant_id', '=', 137], ['country_id', '=', 287]])->get();
            if (!empty($businessSegments)) {
                foreach ($businessSegments as $businessSegment) {
                    $business_segment_arr = $businessSegment->toArray();
                    unset($business_segment_arr['id']);
                    unset($business_segment_arr['created_at']);
                    unset($business_segment_arr['updated_at']);
                    $business_segment_arr['merchant_id'] = 596;
                    $business_segment_arr['country_id'] = $businessSegment->country_id == 287 ? 1457 : 1454;
                    $business_segment_arr['country_area_id'] = 1589;
                    DB::connection('mysql2')->table('business_segments')->insert($business_segment_arr);
                }
            }
            die('update done');
        } catch (\Exception $exception) {
            // DB::rollback();
            dd($exception);
            // return redirect()->back()->withErrors($exception->getMessage());
        }
    }

    public function testToll(){
        $merchant = Merchant::find(555);
        $newTool = new Toll();
        $booking = App\Models\Booking::find(21465);
        $bookingDetails = App\Models\BookingDetail::where('booking_id', $booking->id)->first();
        $from = $bookingDetails->start_latitude . "," . $bookingDetails->start_longitude;
        $to = $bookingDetails->end_latitude . "," . $bookingDetails->end_longitude;
        $bookingcoordinates = App\Models\BookingCoordinate::where([['booking_id', '=', $booking->id]])->first();
        $coordinates = $bookingcoordinates['coordinates'];
        $toolPrice = $newTool->checkToll($merchant->Configuration->toll_api, $from, $to, $coordinates, $merchant->Configuration->toll_key);
        p($toolPrice);
    }


    public function walleRechargeRequests(Request $request)
    {
        $merchant = get_merchant_id(false);
        $status = 0;
        if($request->query("status")){
            $status = ($request->query("status") == "pending")? 0 : 1 ;
        }
        $wallet_recharge_requests= WalletRechargeRequest::where("merchant_id", $merchant->id)->where("request_status", $status)->paginate(10);
        $failed_recharge_request = WalletRechargeRequest::where("merchant_id", $merchant->id)->where("request_status", 2)->count();
        $pending_recharge_request = WalletRechargeRequest::where("merchant_id", $merchant->id)->where("request_status", 0)->count();
        $succeded_recharge_request = WalletRechargeRequest::where("merchant_id", $merchant->id)->where("request_status", 1)->count();
        $config = $merchant;
        $config->driver_wallet_status = $config->Configuration->driver_wallet_status;
        $config->user_wallet_status = $config->Configuration->user_wallet_status;
        return view("merchant.random.wallet-recharge-request", compact('wallet_recharge_requests', 'succeded_recharge_request', 'failed_recharge_request', 'pending_recharge_request', 'config'));
    }


    public function misReport(Request $request){

        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        //Acceptence Ratio
        $totalCount = App\Models\BookingRequestDriver::whereHas('booking', function ($query) use ($merchant_id) {
            $query->where('merchant_id', $merchant_id);
        })->count();
        $statusCounts = App\Models\BookingRequestDriver::whereHas('booking', function ($query) use ($merchant_id) {
            $query->where('merchant_id', $merchant_id);
        })
            ->select('request_status', DB::raw('COUNT(*) as count'))
            ->groupBy('request_status')
            ->pluck('count', 'request_status');

        $values = [];
        foreach ([1, 2, 3, 4] as $status) {
            $values[] = $totalCount > 0 ? round(($statusCounts[$status] ?? 0) * 100 / $totalCount, 2) : 0;
        }
        $acceptance_ratio = [
            'heading' => trans("$string_file.taxi_delivery_acceptance_statics"),
            'labels' => [trans("$string_file.accepted"), trans("$string_file.rejected"), trans("$string_file.request_in_process"), trans("$string_file.cancelled")],
            'values' => $values,
        ];

        // Taxi Stats
        $bookings = \App\Models\Booking::with("CountryArea")->where('merchant_id', $merchant_id)
            ->whereIn('segment_id', [1, 2])
            ->get();
        $totalCount = $bookings->count();
        $categories = [
//            'all_rides' => $totalCount,
            'completed' => $bookings->where('booking_status', 1005)->count(),
            'ongoing' => $bookings->whereIn('booking_status', [1001, 1012, 1002, 1003, 1004])->count(),
            'cancelled' => $bookings->whereIn('booking_status', [1006, 1007, 1008])->count(),
            'auto_cancelled' => $bookings->whereIn('booking_status', [1016, 1018])->count(),
        ];
        $data = collect($categories)->map(function ($count) use ($totalCount) {
            return $totalCount > 0 ? round(($count * 100) / $totalCount, 2) : 0;
        });
        $taxi_stats = [
            'heading' => trans("$string_file.taxi_delivery_statics"),
            'labels' => [ trans("$string_file.completed"), trans("$string_file.on_going_rides"), trans("$string_file.cancelled"), trans("$string_file.auto_cancelled")],
            'values' => $data->values()->toArray(),
        ];

        //Bookings According To Country Area
        $countryAreaData = [];
        foreach ($bookings as $booking) {
            $areaName = optional($booking->CountryArea)->CountryAreaName ?? 'Unknown';

            if (!isset($countryAreaData[$areaName])) {
                $countryAreaData[$areaName] = [
                    'completed' => 0,
                    'ongoing' => 0,
                    'cancelled' => 0,
                    'auto_cancelled' => 0,
                    'total' => 0
                ];
            }

            $countryAreaData[$areaName]['total'] += 1;

            if ($booking->booking_status == 1005) {
                $countryAreaData[$areaName]['completed'] += 1;
            } elseif (in_array($booking->booking_status, [1001, 1012, 1002, 1003, 1004])) {
                $countryAreaData[$areaName]['ongoing'] += 1;
            } elseif (in_array($booking->booking_status, [1006, 1007, 1008])) {
                $countryAreaData[$areaName]['cancelled'] += 1;
            } elseif (in_array($booking->booking_status, [1016, 1018])) {
                $countryAreaData[$areaName]['auto_cancelled'] += 1;
            }
        }

        foreach ($countryAreaData as $area => &$stats) {
            if ($stats['total'] > 0) {
                foreach (['completed', 'ongoing', 'cancelled', 'auto_cancelled'] as $key) {
                    $stats[$key] = round(($stats[$key] * 100) / $stats['total'], 2);
                }
            }
            unset($stats['total']); // Remove total count if only percentages are needed
        }

        $country_area_bookings = [
            'labels' => array_keys($countryAreaData),
            'datasets' => [
                [
                    'label' => trans("$string_file.completed").' '.trans("$string_file.rides"),
                    'data' => array_column($countryAreaData, 'completed'),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'pointBackgroundColor' => 'rgb(75, 192, 192)',
                ],
                [
                    'label' => trans("$string_file.on_going_rides"),
                    'data' => array_column($countryAreaData, 'ongoing'),
                    'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                    'borderColor' => 'rgb(255, 159, 64)',
                    'pointBackgroundColor' => 'rgb(255, 159, 64)',
                ],
                [
                    'label' => trans("$string_file.cancelled").' '.trans("$string_file.rides"),
                    'data' => array_column($countryAreaData, 'cancelled'),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'pointBackgroundColor' => 'rgb(255, 99, 132)',
                ],
                [
                    'label' => trans("$string_file.auto_cancelled"),
                    'data' => array_column($countryAreaData, 'auto_cancelled'),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'pointBackgroundColor' => 'rgb(54, 162, 235)',
                ]
            ]
        ];

        $earning = DB::table('booking_transactions as bt')
            ->join('bookings as b', 'bt.booking_id', '=', 'b.id')
            ->whereIn('b.segment_id', [1,2])->where('b.merchant_id', $merchant_id)
            ->select('bt.id', 'bt.booking_id', 'b.segment_id', DB::raw('SUM(bt.discount_amount) as discount_amount'), DB::raw('SUM(bt.company_earning) as company_earning'), DB::raw('SUM(bt.booking_fee) as booking_fee'))
            ->first();

        $earnings = [
            'heading' => trans("$string_file.earning"),
            'labels' => [  trans("$string_file.total_earning"), trans("$string_file.total_discount")],
            'values' => [$earning->company_earning + $earning->booking_fee, $earning->discount_amount],
        ];

        return view("merchant.report.mis_report", compact('acceptance_ratio', 'taxi_stats', 'country_area_bookings', 'earnings'));
    }

    public function getMapSearches(Request $request)
    {
        $merchant_id = get_merchant_id();
        $search_param = $request->all();
        $query = SearchablePlace::where("merchant_id",$merchant_id);
        if(!empty($search_param['map'])){
            $query->where('response', 'LIKE', '%'.$search_param['map'].'%');
        }
        if(!empty($search_param['date'])){
            $query->whereDate('created_at', $search_param['date']);
        }
        $searchable_places = $query->orderby("id", "desc")->paginate(20);
        $data = [];
        $data['merchant_id'] = $merchant_id;
        $info_setting = InfoSetting::where('slug', 'PROMOTIONAL_NOTIFICATION')->first();
        return view("merchant.random.map-searches", compact('searchable_places', 'data', 'info_setting', 'search_param'));
    }


    public function apiUsage(Request $request)
    {
        $merchant_id = get_merchant_id();
        $search_param = $request->all();
        $query = App\Models\ApiUsage::where("merchant_id",$merchant_id);
        if (!empty($search_param['date_start']) && !empty($search_param['date_end'])) {
            $query->whereBetween(DB::raw('DATE(created_at)'), [
                $search_param['date_start'],
                $search_param['date_end']
            ]);
        }
        $usages = $query->orderby("created_at", "desc")->paginate(20);
        $data = [];
        $data['merchant_id'] = $merchant_id;
        $info_setting = InfoSetting::where('slug', 'PROMOTIONAL_NOTIFICATION')->first();
        return view("merchant.random.api-usages", compact('usages', 'data', 'info_setting', 'search_param'));
    }

}
