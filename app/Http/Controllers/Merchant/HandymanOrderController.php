<?php

namespace App\Http\Controllers\Merchant;

use App\Events\SendUserHandymanInvoiceMailEvent;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\BookingRating;
use App\Models\BookingTransaction;
use App\Models\HandymanBiddingOrder;
use App\Models\InfoSetting;
use App\Models\Outstanding;
use App\Models\Segment;
use App\Models\HandymanCommission;
use App\Models\Driver;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HandymanOrder;
use App\Traits\HandymanTrait;
use App\Traits\MerchantTrait;
use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Api\PlumberBiddingController;
use App\Http\Controllers\Api\PlumberController;
use App\Http\Controllers\Helper\FindDriverController;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\PaymentMethods\Payment;
use App\Models\HandymanOrderDetail;
use App\Models\SegmentPriceCard;
use DB;
use Illuminate\Support\Facades\Auth;
use View;

class HandymanOrderController extends Controller
{
    // all orders
    use HandymanTrait, MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'HANDYMAN_ORDER')->first();
        view()->share('info_setting', $info_setting);
    }

    public function bookingSearchView($request)
    {
        $data['arr_search'] = $request->all();
        $booking_search = View::make('merchant.handyman-order.booking-search')->with($data)->render();
        return $booking_search;
    }

    public function orders(Request $request)
    {
        $checkPermission = check_permission(1, 'booking_management_HANDYMAN');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $handyman = new HandymanOrder;
        $arr_orders = $handyman->getSegmentOrders($request);
        $merchant = get_merchant_id(false);
        $booking_config = $merchant->BookingConfiguration;
        $req_param['merchant_id'] = $merchant->id;
        $string_file = $this->getStringFile($merchant->id);
        $arr_status = $this->getHandymanBookingStatus($req_param, $string_file);
        $segment_list = get_merchant_segment(false, $merchant->id, 2);
        $data = $request->all();
        $search_route = route('handyman.orders');
        $arr_search = $request->all();
        $request->merge(['search_route' => route("handyman.orders")]);
        $search_view = $this->bookingSearchView($request);
        return view('merchant.handyman-order.index', compact('arr_orders', 'data', 'arr_status', 'search_route', 'segment_list', 'arr_search', 'search_view', 'booking_config'));
    }

    public function orderDetail(Request $request, $order_id)
    {
        $checkPermission = check_permission(1, 'booking_management_HANDYMAN');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $order_obj = new HandymanOrder;
        $request->merge(['order_id' => $order_id]);
        $order = $order_obj->getOrder($request);
        $req_param['merchant_id'] = $order->merchant_id;
        $string_file = $this->getStringFile($order->merchant_id);
        $arr_status = $this->getHandymanBookingStatus($req_param, $string_file);
        return view('merchant.handyman-order.order-detail', compact('order', 'arr_status'));
    }

    // Taxi based services Earning
    public function handymanServicesEarning(Request $request)
    {
        $checkPermission = check_permission(1, 'view_reports_charts');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_id = get_merchant_id();
        $handyman = new HandymanOrder;
        $request->merge(['merchant_id' => $merchant_id, 'status' => 'COMPLETED']);
        $arr_bookings = $handyman->getSegmentOrders($request);
//        $arr_booking_id = array_pluck($arr_bookings,'id');
        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }
        \DB::enableQueryLog();
        $query = BookingTransaction::select(DB::raw('SUM(customer_paid_amount) as booking_amount'), DB::raw('SUM(company_earning) as merchant_earning'), DB::raw('SUM(driver_earning) as driver_earning'), DB::raw('SUM(tip) as tip'), DB::raw('SUM(tax_amount) as tax'), DB::raw('SUM(discount_amount) as discount'))
            ->with(['HandymanOrder' => function ($q) use ($request, $merchant_id) {
                // $q->where([['order_status','=',7],['merchant_id','=',$merchant_id]]);
                $status = [7, 11, 12];
                $q->where('merchant_id', $merchant_id);
                $q->whereIn('order_status', $status);
                if (!empty($request->order_id) && $request->order_id) {
                    $q->where('merchant_order_id', $request->order_id);
                }
                if (!empty($request->segment_id)) {
                    $q->where('segment_id', $request->segment_id);
                }
                if ($request->start) {
                    $start_date = date('Y-m-d', strtotime($request->start));
                    $end_date = date('Y-m-d ', strtotime($request->end));
                    $q->whereBetween(DB::raw('DATE(created_at)'), [$start_date, $end_date]);
                }
            }])
            ->whereHas('HandymanOrder', function ($q) use ($request, $merchant_id, $permission_area_ids) {
                // $q->where([['order_status','=',7],['merchant_id','=',$merchant_id]]);
                $status = [7, 11, 12];
                $q->where('merchant_id', $merchant_id);
                $q->whereIn('order_status', $status);
                if (!empty($request->booking_id) && $request->booking_id) {
                    $q->where('merchant_order_id', $request->booking_id);
                }
                if (!empty($request->segment_id)) {
                    $q->where('segment_id', $request->segment_id);
                }
                if ($request->start) {
                    $start_date = date('Y-m-d', strtotime($request->start));
                    $end_date = date('Y-m-d ', strtotime($request->end));
                    $q->whereBetween(DB::raw('DATE(created_at)'), [$start_date, $end_date]);
                }
                if (!empty($permission_area_ids)) {
                    $q->whereIn("country_area_id", $permission_area_ids);
                }
            });
        $earning_summary = $query->first();

        if (!empty($earning_summary->booking_amount)) {
            $earning_summary['merchant_total'] = $earning_summary['merchant_earning'] - $earning_summary['discount'];
            $earning_summary['tax'] = $earning_summary['tax'];
            $earning_summary['driver_total'] = $earning_summary['driver_earning'] + $earning_summary['tip'];
        }
        $arr_segment = get_merchant_segment(true, $merchant_id, 2);
        $request->merge(['request_from' => "booking_earning", "arr_segment" => $arr_segment]);
        $arr_search = $request->all();
        $total_bookings = $arr_bookings->total();
        $currency = "";
        $request->merge(['search_route' => route("merchant.handyman-services-report")]);
        $search_view = $this->bookingSearchView($request);
        $info_setting = InfoSetting::where('slug', 'HANDYMAN_SERVICE_EARNING')->first();
        // dd($arr_bookings);
        return view('merchant.report.handyman-services.earning', compact('arr_bookings', 'arr_search', 'earning_summary', 'total_bookings', 'currency', 'search_view', 'info_setting'));
    }

    public function sendInvoice(Request $request, $id)
    {
        $order = HandymanOrder::find($id);
        $string_file = $this->getStringFile($order->merchant_id);
        event(new SendUserHandymanInvoiceMailEvent($order));
        return redirect()->route('merchant.handyman.order.detail', $order->id)->withSuccess(trans("$string_file.email_sent_successfully"));
    }

    public function disputeOrder(Request $request)
    {
        $order = HandymanOrder::find($request->order_id);
        $string_file = $this->getStringFile($order->merchant_id);
        $order->is_dispute = $request->status;
        $order->order_status = $request->status == 1 ? 11 : 12;  //change status only after dispute approve
        if ($request->status == 1) {
            $order->dispute_settled_amount = $request->agreed_booking_amount;

            //tax on agreed amount
            $tax_per = (float)$order->tax_per;
            $tax_amount = ($tax_per / 100) * (float)$request->agreed_booking_amount;

            //update final_amount_paid and tax
            $order->final_amount_paid = (float)$request->agreed_booking_amount + $tax_amount;
            $order->tax_after_dispute = $tax_amount;
        }
        $order->save();

        $notification_type = null;
        if ($order->order_status == 11) {
            $request->merge(['notification_type' => "DISPUTE_RESSOLVED"]);
        } else if ($order->order_status == 12) {
            $request->merge(['notification_type' => "DISPUTE_REJECTED"]);
        }
        $this->sendHandymanNotificationToUser($request, $order, "", $string_file);

        if ($request->status == 1) {
            $this->storeHandymanOrderTransaction($order);
        }
        $outstanding = Outstanding::create([
            'user_id' => $order->user_id,
            'driver_id' => $order->driver_id,
            'handyman_order_id' => $order->id,
            'amount' => $order->final_amount_paid,
            'reason' => 3,
        ]);

        return redirect()->back()->withSuccess('Dispute Submitted!');
    }

    private function storeHandymanOrderTransaction($order)
    {
        try {
            $order = $order->fresh();
            $commission_data = self::HandymanOrderCommission($order);

            $tax = $order->tax;
            if (isset($order->tax_after_dispute)) {
                $tax = $order->tax_after_dispute;
            }
            $sub_total_before_discount = $order->final_amount_paid - $tax;
            $discount_amount = $order->discount_amount;
            $tax_amount = $tax;
            $cash_payment = ($order->PaymentMethod->payment_method_type == 1) ? $order->final_amount_paid : '0.0';
            $online_payment = ($order->PaymentMethod->payment_method_type == 1) ? '0.0' : $order->final_amount_paid;
            $customer_paid_amount = $order->final_amount_paid;
            $company_earning = $commission_data['company_cut'];
            $driver_earning = $commission_data['driver_cut'];
            $order_transaction = BookingTransaction::where('handyman_order_id', $order->id)->first();
            if (empty($order_transaction)) {
                $order_transaction = new BookingTransaction();
                $order_transaction->merchant_id = $order->merchant_id;
            }
            $driver_total_payout_amount = $commission_data['driver_cut'] + $order_transaction->discount_amount + $order_transaction->tip;
            $order_transaction->handyman_order_id = $order->id;
            $order_transaction->commission_type = 1;
            $order_transaction->sub_total_before_discount = $sub_total_before_discount;
            $order_transaction->discount_amount = $discount_amount;
            $order_transaction->tax_amount = $tax_amount;
            $order_transaction->cash_payment = $cash_payment;
            $order_transaction->online_payment = $online_payment;
            $order_transaction->customer_paid_amount = $customer_paid_amount;
            $order_transaction->company_earning = $company_earning;
            $order_transaction->driver_earning = $driver_earning;
            //            $order_transaction->amount_deducted_from_driver_wallet = $amount_deducted_from_driver_wallet;
            $order_transaction->driver_total_payout_amount = $driver_total_payout_amount;
            $order_transaction->company_gross_total = ($company_earning + $tax_amount - $discount_amount);
            $ride_earning_type = 2; // commission based
            if ($order->Driver->Merchant->Configuration->subscription_package == 1 && $order->Driver->pay_mode == 1) {
                $ride_earning_type = 1;
                // p('inn');
                // p($order->segment_id);
                // debit orders from package
                $this->SubscriptionPackageExpiryCheck($order->Driver, $order->segment_id);
            }
            // p('No');
            $order_transaction->ride_type_earning = $ride_earning_type;
            $order_transaction->save();
            return $order_transaction;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    private static function HandymanOrderCommission($order)
    {
        try {
            $order_commission = HandymanCommission::where([['merchant_id', '=', $order->merchant_id], ['country_area_id', '=', $order->country_area_id], ['segment_id', '=', $order->segment_id]])->first();
            // Commission on amount before discount and tax
            $tax = $order->tax;
            if (isset($order->tax_after_dispute)) {
                $tax = $order->tax_after_dispute;
            }
            $cart_amount = $order->final_amount_paid - $tax;
            $amount = $cart_amount; //$order->cart_amount;
            $commission_method = NULL;
            $commission_amount = NULL;

            //@ayush Service based commission
            if (isset($order_commission) && $order_commission->commission_pricing_type == 2) {
                $services = [];
                foreach ($order->HandymanOrderDetail as $order_detail)
                    array_push($services, $order_detail->service_type_id);

                $commission_details = $order_commission->HandymanCommissionDetail->whereIn("service_type_id", $services);

                foreach ($commission_details as $detail)
                    $commission_amount += $detail->amount;
            }

            if (!empty($order_commission)) {
                $commission_method = $order_commission->commission_method;
//                $commission_amount = $order_commission->commission;
                $commission_amount = (empty($commission_amount) && $order_commission->commission_pricing_type == 1) ? $order_commission->commission : $commission_amount;
            }
            if ($commission_method == 1) {  // 1:Flat commission per Ride (==OR==) 2:Percentage of Net Bill (before tax)
                if ($commission_amount > $amount) {
                    $company_cut = $amount;
                    $driver_cut = "0.00";
                } else {
                    $company_cut = $commission_amount;
                    $driver_cut = $amount - $company_cut;
                }
            } else {
                $company_cut = ($amount * $commission_amount) / 100;
                $driver_cut = $amount - $company_cut;
            }

            $driver = Driver::find($order->driver_id);
            $driver->total_earnings = round_number(($driver->total_earnings + $driver_cut));
            $driver->total_comany_earning = round_number(($driver->total_comany_earning + $company_cut));
            $driver->save();
            // tax will be paid to merchant
            $return_data = [
                'company_cut' => round_number($company_cut),
                'driver_cut' => round_number($driver_cut),
            ];
            return $return_data;
        } catch (\Exception $e) {
            throw new \Exception('Commission : ' . $e->getMessage());
        }
    }


    public function biddingSearchView($request)
    {
        $data['arr_search'] = $request->all();
        $booking_search = View::make('merchant.handyman-order.bidding-search-view')->with($data)->render();
        return $booking_search;
    }

    public function bidding(Request $request)
    {
        $checkPermission = check_permission(1, 'booking_management_HANDYMAN');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $orders = HandymanBiddingOrder::with("ServiceTimeSlotDetail")->where("merchant_id", $merchant->id);
        if (!empty($request->order_id)) {
            $orders->where("merchant_order_id", $request->order_id);
        }
        if (!empty($request->start) && !empty($request->end)) {
            $orders->whereBetween('created_at', [$request->start, $request->end]);
        }

        $arr_orders = $orders->orderBy("id", "desc")->paginate();
        $booking_config = $merchant->BookingConfiguration;
        $req_param['merchant_id'] = $merchant->id;
        $string_file = $this->getStringFile($merchant->id);
        $data = $request->all();
        $search_route = route('handyman.orders');
        $arr_search = $request->all();
        $request->merge(['search_route' => route("handyman.bidding")]);
        $search_view = $this->biddingSearchView($request);
        return view('merchant.handyman-order.bidding', compact('arr_orders', 'data', 'search_route', 'arr_search', 'search_view', 'booking_config'));

    }


    public function lowBalenceServiceProviders(Request $request)
    {
        $checkPermission = check_permission(1, 'view_drivers');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $request = $request->merge(['merchant_id' => $merchant_id, 'segment_group_id' => 2, "wallet_money_filter" => 0]);
        $drivers = $this->getAllDriver(true, $request);
        $driverController = new \App\Http\Controllers\Merchant\DriverController();
        $info_setting = InfoSetting::where('slug', 'DRIVER')->first();
        $config = $merchant;
        $config->gender = $config->ApplicationConfiguration->gender;
        $config->driver_wallet_status = $config->Configuration->driver_wallet_status;

        return view("merchant.low-balence-sp", compact('drivers', 'info_setting', 'config'));
    }


    public function updateDriverQuotedPrice(Request $request)
    {
        $handyman_bidding_counter_id = $request->id;
        $new_price = $request->price;
        $driver_id = $request->driver_id;
        if (empty($handyman_bidding_counter_id) || empty($new_price) || empty($driver_id)) {
            return response()->json(["message" => "failed", "status" => 2], 400);
        }
        DB::BeginTransaction();
        try {
            \DB::table("handyman_bidding_order_driver")
                ->where("handyman_bidding_order_id", $handyman_bidding_counter_id)
                ->where("driver_id", $driver_id)
                ->update([
                    "amount" => $new_price,
                ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                "message" => "Update failed: " . $e->getMessage(),
                "status" => 2
            ], 500);
        }
        DB::commit();
        return response()->json(["message" => "success", "status" => 1], 200);
    }

    public function biddingManualAssign(Request $request, $id)
    {
        $checkPermission = check_permission(1, 'booking_management_HANDYMAN');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $order = HandymanBiddingOrder::with("ServiceTimeSlotDetail")->where("merchant_id", $merchant->id)->where("id", $id)->first();
        $booking_config = $merchant->BookingConfiguration;
        return view('merchant.handyman-order.assign', compact('order', 'booking_config'));
    }

    public function getNearestProvider(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $id = $request->id;
        $order = HandymanBiddingOrder::with("ServiceTimeSlotDetail")->where("merchant_id", $merchant_id)->where("id", $id)->first();
        if ($order->order_status == 2) {
            return response()->json(["order_status" => trans("$string_file.booking")." ". trans("$string_file.created")." ".trans("$string_file.successfully"), "status" => 2]);
        }
        if ($order->order_status == 3) {
            return response()->json(["order_status" => trans("$string_file.cancelled"), "status" => 3]);
        }
        $selected_services = [];
        foreach (json_decode($order->ordered_services) as $service) {
            $selected_services[] = $service->service_type_id;
        }
        $request = $request->merge([
            'latitude' => $order->drop_latitude,
            'longitude' => $order->drop_longitude,
            'merchant_id' => $order->merchant_id,
            "selected_services" => $selected_services,
            "segment_id" => $order->segment_id,
            "service_time_slot_detail_id" => $order->service_time_slot_detail_id,
            "area_id" => $order->country_area_id,
        ]);
        $arr_driver = Driver::getNearestPlumbers($request);
        return response()->json(["order_status" => trans("$string_file.active"), "status" => 1, "arr_drivers" => $arr_driver]);
    }


    public function biddingOrderAssignToDriver(Request $request)
    {
        $item = HandymanBiddingOrder::find($request->order_id);
        $user = $item->User;
        $merchant = $item->Merchant;
        $string_file = $this->getStringFile(null, $merchant);
        $bid_amount = $request->bid_amount;
        DB::beginTransaction();
        try {
            $other_user = HandymanOrder::where([['service_time_slot_detail_id', '=', $item->service_time_slot_detail_id], ['is_order_completed', '!=', 1], ['merchant_id', '=', $user->merchant_id]])
                ->where('booking_date', $item->booking_date)->where([['driver_id', '=', $request->driver_id]])->whereIn('order_status', [4, 6, 7])->count();
            // Check driver slot availability available
            if ($other_user > 0) {
                return redirect()->back()->withErrors(trans("$string_file.slot_already_booked"));
            }
            // Check driver radius warning
            $driver = Driver::find($request->driver_id);
            $item->Driver()->sync([$driver->id => ['status' => 2, 'amount' => $bid_amount, 'description' => trans("$string_file.manual_assign")]], false);

            $user_lat = $item->drop_latitude;
            $user_long = $item->drop_longitude;
            $address = $driver->WorkShopArea; // workshop area of driver
            $driver_radius = $address->radius;
            $driver_latitude = $address->latitude;
            $driver_longitude = $address->longitude;
            $unit = $user->Country->distance_unit;
            $unit_lang = ($unit == 2 ? trans("$string_file.miles") : trans("$string_file.km"));
            $google = new GoogleController();
            $distance_from_user = $google->arialDistance($user_lat, $user_long, $driver_latitude, $driver_longitude, $unit, $string_file, false);
            if (ceil($distance_from_user) > $driver_radius) {
                return redirect()->back()->with(trans_choice("$string_file.provider_radius_warning", 3, ['RANGE' => $driver_radius . $unit_lang]));
            }

            //  Get Services calculation
            $arr_detail_ids = json_decode($item->ordered_services, true);
            $arr_detail_ids = array_column($arr_detail_ids, 'segment_price_card_detail_id');
            $price_card = SegmentPriceCard::select('id', 'price_type', 'minimum_booking_amount', 'amount')
                ->with(['SegmentPriceCardDetail' => function ($q) use ($arr_detail_ids) {
                    $q->whereIn('id', $arr_detail_ids);
                }])
                ->whereHas('SegmentPriceCardDetail', function ($q) use ($arr_detail_ids) {
                    $q->whereIn('id', $arr_detail_ids);
                })
                ->first();
            $request->merge(['service_details' => $item->ordered_services, 'price_card' => $price_card]);
            $plumberController = new PlumberController();

            $bid_driver = $item->ActionedDrivers->where("id", $request->driver_id)->first();

            $driver_count_offer = null;
            $driver_offer_price = false;
            if (!empty($bid_driver) && $bid_driver->pivot->status == 2) { // If this is driver counter offer then
                $driver_count_offer = $bid_driver->pivot->amount;
                $driver_offer_price = true;
            }
            if ($driver_offer_price) {
                $request->merge(['is_bidding_order' => true, 'user_offer_price' => $driver_count_offer]);
            } else {
                $request->merge(['is_bidding_order' => true, 'user_offer_price' => $item->user_offer_price]);
            }
            $request->merge(['segment_id' => $item->segment_id, "area" => $item->country_area_id, 'merchant_id' => $item->merchant_id]);
            $cart_amount = $plumberController->getCartDataApp($request);


            // Create Handyman Order
            $order = new HandymanOrder;
            $order->merchant_id = $item->merchant_id;
            $order->user_id = $user->id;
            $order->driver_id = $request->driver_id;
            $order->segment_id = $item->segment_id;
            $order->order_status = 4; //order placed
            $order->country_area_id = $item->country_area_id;
            $order->quantity = $cart_amount['total_quantity'];
            $order->card_id = $item->card_id;
            $order->tax_per = $cart_amount['tax_per'];
            $order->tax = $cart_amount['tax'];
            $order->cart_amount = $cart_amount['total_amount'];  // service amount
            $order->total_booking_amount = $cart_amount['final_amount']; // final booking amount (service charges + tax)
            $order->minimum_booking_amount = $cart_amount['total_amount'];
            $order->final_amount_paid = $cart_amount['final_amount'];
            $order->discount_amount = $cart_amount['discount_amount'];

            /*******Check user wallet if payment method is wallet *********/
            $final_amount = $order->final_amount_paid;
            if ($item->payment_method_id == 3) {
                $common_controller = new CommonController;
                $common_controller->checkUserWallet($user, $final_amount);
            }

            $order->payment_method_id = $item->payment_method_id;
            $order->min_booking_payment_method_id = $item->payment_method_id;
            $order->booking_date = $item->booking_date;
            $order->segment_price_card_id = $price_card->id;
            $order->service_time_slot_detail_id = $item->service_time_slot_detail_id;
            $order->price_type = $price_card->price_type;
            $order->hourly_amount = $price_card->amount;

            $order->drop_latitude = $item->drop_latitude;
            $order->drop_longitude = $item->drop_longitude;
            $order->drop_location = $item->drop_location;

            $order->additional_notes = $item->additional_notes;
            $order->booking_timestamp = time();
            $order->driver_id = $request->driver_id;

            $status_history[] = [
                'order_status' => 4,
                'order_timestamp' => time(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ];

            $order->order_status_history = json_encode($status_history);
            $order->save();

            foreach ($cart_amount['ordered_services'] as $service) {
                $service_obj = new HandymanOrderDetail();
                $service_obj->handyman_order_id = $order->id;
                $service_obj->service_type_id = $service['service_type_id'];

                if ($price_card->price_type == 1) // it will  insert in case of fixed price type
                {
                    $service_obj->segment_price_card_detail_id = $service['segment_price_card_detail_id'];
                    $service_obj->quantity = $service['quantity'];
                    $service_obj->price = $service['service_price']; // service price
                }
                $service_obj->discount = 0;
                $service_obj->total_amount = $service['price']; // total charges of service
                $service_obj->save();
            }

            $item->handyman_order_id = $order->id;
            $item->order_status = 2;
            $item->save();

            $item->Driver()->sync([$request->driver_id => ['status' => 4]], false);

            // send notification once data saved  in db
            $request->merge(['notification_type' => 'NEW_ORDER']);
            $this->sendNotificationToProvider($request, $order->driver_id, $order, $string_file);

            $arr_driver_id = [$order->Driver];
            $findDriver = new FindDriverController();
            $findDriver->AssignRequest($arr_driver_id, null, null, $order->id);

            $pending_driver_ids = $item->ActionedDrivers->where("id", "!=", $request->driver_id)->pluck("id")->toArray();
            if (!empty($pending_driver_ids)) {
                // send notification once data saved  in db
                $request->merge(['notification_type' => 'BIDDING_ORDER_ACCEPTED_BY_OTHER_DRIVER']);
                $plumberBiddingController = new PlumberBiddingController();
                $plumberBiddingController->sendProviderNotification($request, $pending_driver_ids, $item, $string_file);
            }

            DB::commit();
            return redirect()->back()->withSuccess("Success");

        } catch (\Exception $e) {
            DB::rollback();
            $message = $e->getMessage();
            return redirect()->back()->withErrors($message);
        }
    }

    public function markAsComplete(Request $request)
    {

        $checkPermission = check_permission(1, 'booking_management_HANDYMAN');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }

        DB::BeginTransaction();
        try {
            $order = HandymanOrder::find($request->order_id);
            $string_file = $this->getStringFile($order->merchant_id);
            $driver = $order->Driver;
            // check existing start bookings
            $ongoing_bookings = HandymanOrder::whereIn('order_status', [6])->where([['merchant_id', '=', $order->merchant_id], ['driver_id', '=', $driver->id]])->count();
            if ($ongoing_bookings > 0) {
                return redirect()->back()->withErrors(trans("$string_file.existing_booking_error"));
            }


            //if not started
            if ($order->order_status == 4 && !empty($order->id)) {
                $status_history = json_decode($order->order_status_history, true);
                $new_status = [
                    'order_status' => 6, //for start
                    'order_timestamp' => time(),
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ];
                $status_history[] = $new_status;
                $order->order_status = 6;
                $order->order_otp = NULL;

                $order->order_status_history = json_encode($status_history);
                $order->save();
            }


            //if not ended
            if ($order->order_status == 6 && !empty($order->id)) {
                $status_history = json_decode($order->order_status_history, true);
                $job_end_time = time();
                $new_status = [
                    'order_status' => 7,
                    'order_timestamp' => $job_end_time,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ];
                $status_history[] = $new_status;
                $order->order_status = 7;
                $order->order_status_history = json_encode($status_history);
                $extra_charges = 0;

                //check if order has hourly based services
                $start_time = NULL;
                if ($order->price_type == 2) {
                    foreach ($status_history as $status) {
                        if ($status['order_status'] == 6) {
                            $start_time = $status['order_timestamp'];
                            break;
                        }
                    }
                    $job_time = $job_end_time - $start_time;
                    $total_service_hours = ceil($job_time / 3600);
                    $cart_amount = $total_service_hours * $order->hourly_amount;
                    $tax_amount = ($cart_amount * $order->tax_per) / 100;
                    $final_paid = ($cart_amount - $order->discount_amount + $tax_amount);
                    $order->cart_amount = $cart_amount;
                    $order->total_booking_amount = $final_paid;
                    $order->total_service_hours = $total_service_hours;
                    // final amount of booking
                    if ($order->total_booking_amount > $order->minimum_booking_amount) {
                        $order->final_amount_paid = $order->total_booking_amount;
                        $order->tax = $tax_amount;
                    }
                }

                // If order amount is entered by user as bidding amount, then make final amount as bidding amount
                if (isset($order->bidding_amount_accepted) && $order->bidding_amount_accepted == 1) {
                    $order->final_amount_paid = $order->bidding_amount;
                }

                $order->final_amount_paid = $order->final_amount_paid + $extra_charges;
                $order->save();
                if($request->has("maintain_transaction")){
                    $final_amount_paid = $order->final_amount_paid;
                    $total_pending_amount = $final_amount_paid;
                    if ($order->minimum_booking_amount_payment_status == 1) {
                        $total_pending_amount = $total_pending_amount - $order->minimum_booking_amount;
                    }
                    $this->storeHandymanOrderTransaction($order);
                    if ($total_pending_amount >= 0) {
                        $order->payment_status = 1;
                        $payment = new Payment();
                        $currency = $order->CountryArea->Country->isoCode;
                        $array_param = array(
                            'handyman_order_id' => $order->id,
                            'payment_method_id' => $order->payment_method_id,
                            'amount' => $order->final_amount_paid,
                            'user_id' => $order->user_id,
                            'card_id' => $order->card_id,
                            'currency' => $currency,
                            'quantity' => $order->quantity,
                            'order_name' => $order->merchant_order_id,
                            'booking_transaction' => "",
                            'driver_sc_account_id' => "",
                        );
                        $payment->MakePayment($array_param);
                        $order->save();
                        $handyman_controller = new \App\Http\Controllers\Api\HandymanOrderController();
                        $handyman_controller->contributeCommission($order);
                    }
                    $order_data = $order->refresh();
                }
            }


            if (!empty($order)) {
                if ($order->order_status == 7 || $order->order_status == 11 || $order->order_status == 12) {
                    if (!empty($order->payment_status != 1)) {
                        if ($request->has("maintain_transaction")) {
                            $order->payment_status = 1;
                            $order->save();
                            $advance_payment = $order->advance_payment_of_min_bill;
                            if ($advance_payment != 1) {
                                $handyman_controller = new \App\Http\Controllers\Api\HandymanOrderController();
                                $handyman_controller->contributeCommission($order);
                            }
                        }
                    }
                }

                if (($order->order_status == 7 || $order->order_status == 11 || $order->order_status == 12) && !empty($order->id)) {
                    $order->is_order_completed = 1;
                    $order->admin_remarks = $request->remarks;
                    $order->save();
                    // change driver status
                    $driver = \App\Models\Driver::find($order->driver_id);
                    $driver->free_busy = 2;
                    $driver->save();

                    // Driver rate to user
                    if (isset($request->rating)) {
                        BookingRating::updateOrCreate(
                            ['handyman_order_id' => $order->id],
                            [
                                'driver_rating_points' => $request->rating,
                                'driver_comment' => "",
                            ]
                        );
                        $order = HandymanOrder::find($request->order_id);
                        $avg = BookingRating::whereHas('HandymanOrder', function ($q) use ($order) {
                            $q->where('driver_id', $order->driver_id);
                        })->avg('driver_rating_points');
                        $user = \App\Models\User::find($order->user_id);
                        $user->rating = round($avg, 2);
                        $user->save();
                    }
                }
                
                $request->merge(['notification_type' => 'ORDER_COMPLETE_BY_ADMIN']);
                $this->sendHandymanNotificationToUser($request, $order, "", $string_file);
            }
        } catch (\Exception $e) {
            DB::rollback();
            $message = $e->getMessage();
            throw $e;
            return redirect()->back()->withErrors($message);
        }
        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.success"));
    }
}
