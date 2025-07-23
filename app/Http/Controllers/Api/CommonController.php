<?php

namespace App\Http\Controllers\Api;

use App\Events\SosEmailNotification;
use App\Http\Controllers\Controller;
use App\Events\CustomerSupportEvent;
use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\Merchant;
use App\Http\Controllers\Helper\PolygenController;
use App\Http\Controllers\Helper\PriceController;
use App\Models\AllSosRequest;
use App\Models\Booking;
use App\Models\BookingConfiguration;
use App\Models\BookingRating;
use App\Models\BookingRequestDriver;
use App\Models\BusinessSegment\Order;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\CancelReason;
use App\Models\CmsPage;
use App\Models\Configuration;
use App\Models\Corporate;
use App\Models\Country;
use App\Models\CountryArea;
use App\Models\ChildTerm;
use App\Models\CustomerSupport;
use App\Models\Driver;
use App\Models\DriverDetail;
use App\Models\DriverVehicle;
use App\Models\FavouriteLocation;
use App\Models\GeofenceAreaQueue;
use App\Models\HandymanOrder;
use App\Models\MerchantWebsiteString;
use App\Models\Onesignal;
use App\Models\OutstationPackage;
use App\Models\PaymentConfiguration;
use App\Models\PaymentOptionsConfiguration;
use App\Models\PriceCard;
use App\Models\PricingParameter;
use App\Models\PromoCode;
use App\Models\RestrictedArea;
use App\Models\ReferralDiscount;
use App\Models\SearchablePlace;
use App\Models\Segment;
use App\Models\ServiceTimeSlot;
use App\Models\ServiceType;
use App\Models\SosRequest;
use App\Models\CancelRate;
use App\Models\UserWalletTransaction;
use App\Models\VehicleType;
use App\Models\RewardPoint;
use App\Models\WalletCouponCode;
use App\Http\Controllers\LaundryOutlet\LaundryOrderController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Traits\AreaTrait;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Models\PaymentMethod;
use App\Models\UserAddress;
use App\Traits\DriverTrait;
use App\Models\DriverConfiguration;
use App\Models\ApplicationString;
use App\Models\ApplicationStringLanguage;
use App\Models\Merchant as MerchantModel;
use DateTime;
use App\Events\WebPushNotificationEvent;
use App\Http\Controllers\Helper\GetStringCommon;
use App\Models\ApplicationMerchantString;
use App\Models\SegmentPriceCard;
use App\Models\WalletRechargeRequest;
use App\Traits\LocationTrait;
use App\Traits\ImageTrait;
use App\Models\FaqType;
use App\Models\Faq;
use App\Models\InAppCallingConfigurations;
use Illuminate\Support\Facades\Cache;
use App\Models\BonsBankToBankQrGateway;


class CommonController extends Controller
{
    use AreaTrait, ApiResponseTrait, MerchantTrait, DriverTrait, LocationTrait, ImageTrait;

    public function DriverRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $start_date = $request->start_date . " " . '00:00:00';
        $end_date = $request->end_date . " " . '23:59:59';
        $driver = $request->user('api-driver');
        $averageRating = BookingRating::whereHas('Booking', function ($query) use ($driver) {
            $query->with(['Driver'])->where([['driver_id', '=', $driver->id]]);
        })->where([['created_at', '>=', $start_date], ['created_at', '<=', $end_date]])->avg('user_rating_points');
        $averageRating = $averageRating ? $averageRating : '0.0';
        return response()->json(['result' => "1", 'message' => 'Average Rating', 'data' => ['rating' => sprintf("%0.1f", $averageRating), 'name' => $driver->fullName, 'image' => $driver->profile_image]]);
    }

    public function UserRaing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $start_date = $request->start_date . " " . '00:00:00';
        $end_date = $request->end_date . " " . '23:59:59';
        $user = $request->user('api');
        $averageRating = BookingRating::whereHas('Booking', function ($query) use ($user) {
            $query->where([['user_id', '=', $user->id]]);
        })->where([['created_at', '>=', $start_date], ['created_at', '<=', $end_date]])->avg('driver_rating_points');
        $averageRating = $averageRating ? $averageRating : '0.0';
        return response()->json(['result' => "1", 'message' => 'Average Rating', 'data' => ['rating' => sprintf("%0.1f", $averageRating), 'name' => $user->UserName, 'image' => $user->UserProfileImage]]);
    }

    public function CountryList(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $countries = Country::where([['country_status', '=', 1], ['merchant_id', '=', $merchant_id]])->with('LanguageCountrySingle')->latest()->get();
        if (empty($countries->toArray())) {
            return response()->json(['result' => "0", 'message' => trans("$string_file.data_not_found"), 'data' => []]);
        }
        foreach ($countries as $key => $country) {
            $country->name = $country->CountryName;
            $country->currency = $country->CurrencyName;
        }
        return response()->json(['result' => "1", 'message' => trans('api.message169'), 'data' => $countries]);
    }

    // for all segments
    public function cancelReason(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //            'booking_id' => 'required|exists:bookings,id',
            'segment_id' => 'required|exists:segments,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $user = $request->user('api');

        $merchant_id = $user->merchant_id;
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $payment_config = PaymentConfiguration::select('cancel_rate_table_enable')->where('merchant_id', $merchant_id)->first();
        $cancelReasons = CancelReason::Reason($merchant_id, 1, $request->segment_id);
        if (empty($cancelReasons->toArray())) {
            return $this->failedResponse(trans("$string_file.data_not_found"));
        }
        $charges = "0";

        // tutu changes
        try {
            $code = "";
            $booking = Booking::find($request->booking_id);
            if (!empty($booking->id) && !empty($booking->BookingDetail)) {
                if ($booking->PriceCard->cancel_charges == 1) {
                    $acceptTime = $booking->BookingDetail->accept_timestamp;
                    $current = strtotime('now');
                    $canceTime = strtotime("+{$booking->PriceCard->cancel_time} minutes", $acceptTime);
                    if ($current > $canceTime && $payment_config->cancel_rate_enable == 1) {
                        $cancel_rate = CancelRate::where('merchant_id', $merchant_id)
                            ->where([
                                ['start_range', '<=', $booking->estimate_bill],
                                ['end_range', '>=', $booking->estimate_bill]
                            ])->first();

                        if ($cancel_rate) {
                            if ($cancel_rate->charge_type == 1) {
                                $charges = $cancel_rate->charges;
                            } else {
                                $charge_value = ($booking->estimate_bill * $cancel_rate->charges) / 100;
                                $charges = round($charge_value, 1);
                            }
                        }
                    } else if ($current > $canceTime && $booking->PriceCard->cancel_amount > 0) {

                        $charges = $booking->PriceCard->cancel_amount;
                    }
                }
                $code = $booking->CountryArea->Country->isoCode;
            }
            $countCancellationChargeAppliedBooking = Booking::where(['booking_status'=>1006,'cancellation_charges_applied_after_assigned'=>1,'user_id'=>$user->id])->count();
            $arrData = [];
            if($user->cancellation_charge_card_payment == 1 && $countCancellationChargeAppliedBooking > 2){
                $message = "Cancellation Charge has been already applied two times Now Only Card Payment is applied for this user";
                $payment_method = 2; //card
                $arrData = ['message'=> $message,'payment_method'=> $payment_method,'card_enable'=> true];
            }
            $return_data = ['response_data' => $cancelReasons, 'code' => $code, 'cancel_charges' => $charges,'cancellation_charge_method'=> (object)$arrData];
            $segment_group = Segment::select("segment_group_id")->where("id", $request->segment_id)->first();

            if($segment_group->segment_group_id == 2){ //in case of handyman only
                $segment_price_card = SegmentPriceCard::where("segment_id" , $request->segment_id)->where("merchant_id", $merchant_id)->first();
                $message = trans("$string_file.handyman_cancel_charges")." ".$segment_price_card->handyman_cancellation_charge;
                $return_data = ['response_data' => $cancelReasons, 'code' => $code, 'cancel_charges' => (string)$segment_price_card->handyman_cancellation_charge, "message"=>$message];
            }
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.success"), $return_data);
    }

    public function driverCancelReason(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => 'required|exists:segments,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $user = $request->user('api-driver');
            $merchant_id = $user->merchant_id;
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $cancelReasons = CancelReason::Reason($merchant_id, 2, $request->segment_id);
            if (empty($cancelReasons->toArray())) {
                return $this->failedResponse(trans("$string_file.data_not_found"));
            }

            if($user->Merchant->BookingConfiguration->ride_later_ride_allocation == 2){
                $cancel_charges = 0;
                $booking = Booking::find($request->booking_id);
                if (!empty($booking)){
                    $today = new DateTime(date('Y-m-d H:i:s'));
                    $today = $today->format("Y-m-d H:i:s");
                    $today = convertTimeToUSERzone($today,$booking->CountryArea->timezone,$booking->merchant_id);

                    $ride_later_cancel_hour = $user->Merchant->BookingConfiguration->ride_later_cancel_hour;
                    $ride_later_cancel_hour = !empty($ride_later_cancel_hour) ? $ride_later_cancel_hour : 0;
                    $bookingtimestamp = $booking->later_booking_date. " " . $booking->later_booking_time;
                    $DateTime = new DateTime($bookingtimestamp);
                    $totmin = $ride_later_cancel_hour * 60;
                    $min = $totmin % 60;
                    $hour = explode('.', ($totmin / 60));
                    if ($hour[0] != 0) {
                        $str = $min != 0 ? "-{$hour[0]} hours -{$min} minutes" : "-{$hour[0]} hours";
                    } else {
                        $str = $min != 0 ? "-{$min} minutes" : '-0 minutes';
                    }

                    $DateTime->modify($str);
                    $newDate = $DateTime->format("Y-m-d H:i:s");
                    if ($newDate <= $today){
                        $cancel_charges = $user->Merchant->BookingConfiguration->ride_later_cancel_charge_in_cancel_hour;
                    }
                }
                return response()->json(['version' => "1.5","result" => "1", 'message' => trans("$string_file.success"), 'data' => $cancelReasons, 'cancel_charges' => !empty($cancel_charges) ? $cancel_charges : 0]);
            }
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.success"), $cancelReasons);
    }

    public function CheckBookingTime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1012);
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $booking = Booking::find($request->booking_id);
        $today = new \DateTime();
        $expires = new \DateTime($booking->later_booking_date);
        $config = Configuration::where([['merchant_id', '=', $booking->merchant_id]])->first();
        if ($today <= $expires) {
            if ($today < $expires) {
                return response()->json(['result' => "1", 'message' => trans('api.message142')]);
            }
            $bookingTimestamp = strtotime($booking->later_booking_time) + $config->ride_later_time_before;
            $currentTimestamp = strtotime("now");
            if ($bookingTimestamp >= $currentTimestamp) {
                return response()->json(['result' => "1", 'message' => trans('api.message142')]);
            }
            $time = $config->ride_later_time_before;
            $hours = floor($time / 60);
            $minutes = ($time % 60);
            return response()->json(['result' => "0", 'message' => trans_choice('api.minutes_ago', 3, ['hours' => $hours, 'min' => $minutes]), 'data' => []]);
        } else {
            $time = $config->ride_later_time_before;
            $hours = floor($time / 60);
            $minutes = ($time % 60);
            return response()->json(['result' => "0", 'message' => trans_choice('api.minutes_ago', 3, ['hours' => $hours, 'min' => $minutes]), 'data' => []]);
        }
    }

    public function Customer_Support(Request $request)
    {
        $merchant_id = $request->user('api')->merchant_id;
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $created_customer_support = CustomerSupport::create([
            'merchant_id' => $merchant_id,
            'application' => 1,
            'email' => $request->email,
            'name' => $request->name,
            'phone' => $request->phone,
            'query' => $request->message,
        ]);
        $config = Configuration::select('email_functionality')->where([['merchant_id', '=', $merchant_id]])->first();
        if ($config->email_functionality == 1) {
            event(new CustomerSupportEvent($created_customer_support));
        }
        $string_file = $this->getStringFile($merchant_id);
        return $this->successResponse(trans("$string_file.customer_support_response"), []);
    }

    public function Driver_Customer_Support(Request $request)
    {
        $merchant_id = $request->user('api-driver')->merchant_id;
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $customer_supports = new CustomerSupport();
        $customer_supports->merchant_id = $merchant_id;
        $customer_supports->application = 2;
        $customer_supports->email = $request->email;
        $customer_supports->name = $request->name;
        $customer_supports->phone = $request->phone;
        $customer_supports->query = $request->message;
        $customer_supports->save();

        $config = Configuration::select('email_functionality')->where([['merchant_id', '=', $merchant_id]])->first();
        if ($config->email_functionality == 1) {
            event(new CustomerSupportEvent($customer_supports));
        }
        $string_file = $this->getStringFile($merchant_id);
        return $this->successResponse(trans("$string_file.customer_support_response"), []);
    }

    public function DriverCmsPage(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $validator = Validator::make($request->all(), [
            'slug' => [
                'required',
                Rule::exists('cms_pages', 'slug')->where(function ($query) use ($merchant_id) {
                    $query->where(['merchant_id' => $merchant_id, 'application' => 2]);
                }),
            ],
            // 'country_id' => 'required_if:slug,terms_and_Conditions',
        ], [
            'exists' => trans("$string_file.data_not_found"),
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            // return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        try {
            $message = '';
            if ($request->slug == 'terms_and_Conditions') {
                $message = trans("$string_file.terms_conditions");
                // $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 2], ['slug', '=', $request->slug], ['country_id', '=', $request->country_id]])->first();
                if (!$request->has("country_id") && empty($request->country_id)) {
                    $default_country_id = Country::select('id')->where('merchant_id', $merchant_id)->where('sequance', 1)->first();
                    $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 2], ['slug', '=', $request->slug], ['country_id', '=', $default_country_id]])->first();
                    if (!isset($page)) {
                        $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 2], ['slug', '=', $request->slug]])->first();
                    }
                } else {
                    $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 2], ['slug', '=', $request->slug], ['country_id', '=', $request->country_id]])->first();
                }
            } else {
                $message = trans("$string_file.cms_pages");
                $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 2], ['slug', '=', $request->slug]])->first();
            }
            if (empty($page)) {
                return $this->failedResponse($message);
                //                    response()->json(['result' => "0", 'message' => $message, 'data' => []]);
            }
            $page_data = array(
                'title' => $page->CmsPageTitle,
                'description' => $page->CmsPageDescription,
                'content_type' => $page->content_type
            );
            // $page->title = $page->CmsPageTitle;
            // $page->description = $page->CmsPageDescription;
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse($message, $page_data);
        //        return response()->json(['result' => "1", 'message' => $message, 'data' => $page]);
    }

    public function UserCmsPage(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $customMessages = [
            'exists' => trans("$string_file.data_not_found"),
        ];
        if ($request->slug == 'child_terms') :
            $validator = Validator::make($request->all(), [
                'slug' => [
                    'required',
                    Rule::exists('child_terms', 'slug')->where(function ($query) use ($merchant_id) {
                        $query->where(['merchant_id' => $merchant_id, 'application' => 1]);
                    }),
                ],
                'country_id' => 'required',
            ], $customMessages);
        else :
            $validator = Validator::make($request->all(), [
                'slug' => [
                    'required',
                    Rule::exists('cms_pages', 'slug')->where(function ($query) use ($merchant_id) {
                        $query->where(['merchant_id' => $merchant_id, 'application' => 1]);
                    }),
                ],
                // 'country_id' => 'required_if:slug,terms_and_Conditions',
            ], $customMessages);
        endif;
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            //            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        if ($request->slug == 'terms_and_Conditions') {
            // $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 1], ['slug', '=', $request->slug], ['country_id', '=', $request->country_id]])->first();
            if (!$request->has("country_id") && empty($request->country_id)) {
                $default_country_id = Country::select('id')->where('merchant_id', $merchant_id)->where('sequance', 1)->first();
                $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 1], ['slug', '=', $request->slug], ['country_id', '=', $default_country_id]])->first();
                if (!isset($page)) {
                    $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 1], ['slug', '=', $request->slug]])->first();
                }
            } else {
                $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 1], ['slug', '=', $request->slug], ['country_id', '=', $request->country_id]])->first();
            }
        } elseif ($request->slug == 'child_terms') {
            $page = ChildTerm::where([['merchant_id', '=', $merchant_id], ['application', '=', 1], ['slug', '=', $request->slug], ['country_id', '=', $request->country_id]])->first();
        } else {
            $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 1], ['slug', '=', $request->slug], ['slug', '=', $request->slug]])->first();
        }
        if (empty($page)) {
            return $this->failedResponse(trans("$string_file.cms_pages"));
            //            return response()->json(['result' => "0", 'message' => trans('api.message50'), 'data' => []]);
        }
        if ($request->slug == 'child_terms') :
            $page->title = $page->Title;
            $page->description = $page->Description;
        else :
            $page->title = $page->CmsPageTitle;
            $page->description = $page->CmsPageDescription;
        endif;
        return $this->successResponse(trans("$string_file.cms_pages"), $page);
        //            response()->json(['result' => "1", 'message' => trans("$string_file.cms_pages"), 'data' => $page]);
    }

    public function StoreCmsPage(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $customMessages = [
            'exists' => trans("$string_file.data_not_found"),
        ];
        if ($request->slug == 'child_terms') :
            $validator = Validator::make($request->all(), [
                'slug' => [
                    'required',
                    Rule::exists('child_terms', 'slug')->where(function ($query) use ($merchant_id) {
                        $query->where(['merchant_id' => $merchant_id, 'application' => 3]);
                    }),
                ],
                // 'country_id' => 'required',
            ], $customMessages);
        else :
            $validator = Validator::make($request->all(), [
                'slug' => [
                    'required',
                    Rule::exists('cms_pages', 'slug')->where(function ($query) use ($merchant_id) {
                        $query->where(['merchant_id' => $merchant_id, 'application' => 3]);
                    }),
                ],
                // 'country_id' => 'required_if:slug,terms_and_Conditions',
            ], $customMessages);
        endif;
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            //            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }

            $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 3], ['slug', '=', $request->slug]])->first();

        if (empty($page)) {
            return $this->failedResponse(trans("$string_file.cms_pages"));
        }

            $page->title = $page->CmsPageTitle;
            $page->description = $page->CmsPageDescription;
        return $this->successResponse(trans("$string_file.cms_pages"), $page);
    }

    public function DriverSosRequest(Request $request)
    {
        $user = $request->user('api-driver');
        $merchant_id = $user->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
            'number' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $booking = Booking::find($request->booking_id);
        $sos_request = SosRequest::create([
            'merchant_id' => $merchant_id,
            'country_area_id' => $booking->country_area_id,
            'application' => 1,
            'booking_id' => $request->booking_id,
            'number' => $request->number,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        $player_id = array_pluck($user->Merchant->ActiveWebOneSignals->where('status', 1), 'player_id');
        $message = trans("$string_file.sos");
        $title = trans("$string_file.sos_request");
        $onesignal_redirect_url = route('merchant.sos.requests');
        Onesignal::MerchantWebPushMessage($player_id, [], $message, $title, $user->merchant_id, $onesignal_redirect_url);
        //need to add mail job
        SosEmailNotification::dispatch($booking, $request->location_name, "USER");
        return response()->json(['result' => "1", 'message' => trans("$string_file.sos_request"), 'data' => $sos_request]);
    }

    public function getStringForStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required',
            'app' => 'required',
            'loc' => 'required',
            'version' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse([$errors[0]]);
        }

        $baseVersion = $request->base_version ? $request->base_version : 0.0;
        $platform = $request->platform;
        $app = $request->app;
        $merchant_id = $request->merchant_id;
        // dd($merchant->Language);
        $string_file = $this->getStringFile($merchant_id);
        $loc = !empty($request->loc) ? $request->loc: 'en';
        $version = $request->version;
        if($request->calling_from == 'IOS'){
            $merchant = MerchantModel::find($merchant_id);
            $languages = $merchant->Language;
            $result = "0";
            foreach($languages as $language){
                $datas = GetStringCommon::getStringFromVersion($merchant_id,$language->locale,$version,$platform,$app)->get();
                if (!empty($datas->toArray())){
                    $getAllMerchantString = $datas;
                    $string_latest_version = ApplicationMerchantString::select('version')->where([['merchant_id','=',$merchant_id],['locale','=',$loc]])->orderBy('updated_at', 'DESC')->first();
                    $a = array();
                    foreach ($getAllMerchantString as $data){
                        $a[$data->string_key] = $data->ApplicationMerchantString[0]['string_value'];
                    }
                    $return_data[] = [
                        'locale' => $language->locale,
                        'latest_version' => $string_latest_version->version,
                        'string' => $a,
                    ];
                    $result = "1";
                }
                else{
                    $return_data[] = [
                        'locale' => $language->locale,
                        'latest_version' => "0.0",
                        'string' => [],
                    ];
                }
            }
            if($result == "1"){
                return $this->successResponse(trans("$string_file.success"),$return_data);
            }else{
                return $this->failedResponse(trans("$string_file.success"),$return_data);
            }
        }else{
            $datas = GetStringCommon::getStringFromVersion($merchant_id,$loc,$version,$platform,$app)->get();
            if (!empty($datas->toArray())){
                $getAllMerchantString = $datas;
                //                $this->getStringFromVersion($merchant_id,$loc,$baseVersion,$platform,$app)->get();
                $string_latest_version = ApplicationMerchantString::select('version')->where([['merchant_id','=',$merchant_id],['locale','=',$loc]])->orderBy('updated_at', 'DESC')->first();
                $a = array();
                foreach ($getAllMerchantString as $data){
                    $a[$data->string_key] = $data->ApplicationMerchantString[0]['string_value'];
                }
                $return_data = [
                    'locale' => $request->loc,
                    'latest_version' => $string_latest_version->version,
                    'string' => $a,
                ];
                return $this->successResponse(trans("$string_file.success"),$return_data);
                //            return response()->json(['result' => "1",'message'=> trans('api.update_string'),'locale' => $request->loc, 'data' => $a,'latest_version' => $string_latest_version->version]);
            }else{
                $return_data = [
                    'locale' => $request->loc,
                    'latest_version' => "0.0",
                    'string' => [],
                ];
                return $this->failedResponse(trans("$string_file.success"),$return_data);
                //            return response()->json(['result' => "0",'message'=> trans('api.uptodate'),'data' => [],'latest_version' => 0.0]);
            }
        }
    }

    public function SosRequest(Request $request)
    {
        $user = $request->user('api');
        $merchant_id = $user->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
            'number' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $booking = Booking::find($request->booking_id);
        $sos_request = SosRequest::create([
            'merchant_id' => $merchant_id,
            'application' => 1,
            'country_area_id' => $booking->country_area_id,
            'booking_id' => $request->booking_id,
            'number' => $request->number,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        $player_id = array_pluck($user->Merchant->ActiveWebOneSignals->where('status', 1), 'player_id');
        $message = trans("$string_file.sos");
        $title = trans("$string_file.sos_request");
        $onesignal_redirect_url = route('merchant.sos.requests');
        Onesignal::MerchantWebPushMessage($player_id, [], $message, $title, $user->merchant_id, $onesignal_redirect_url);
        //need to add mail job
        SosEmailNotification::dispatch($booking, $request->location_name, "DRIVER");
        return response()->json(['result' => "1", 'message' => trans("$string_file.sos_request"), 'data' => $sos_request]);
    }

    public function Pricecard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'area' => 'required|integer|exists:country_areas,id',
            'segment_id' => 'required|integer|exists:segments,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $area_id = $request->area;
        $area = CountryArea::find($area_id);
        $currency = $area->Country->isoCode;
        $merchant_id = $area->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $segment_id = $request->segment_id;
        $services = ServiceType::with(['PriceCard' => function ($query) use ($area_id, $segment_id) {
            $query->where([['country_area_id', '=', $area_id], ['segment_id', '=', $segment_id]]);
            $query->where('status', 1);
        }])->whereHas('PriceCard', function ($q) use ($area_id, $segment_id) {
            $q->where([['country_area_id', '=', $area_id], ['segment_id', '=', $segment_id]]);
            $q->where('status', 1);
        })->where([['id', '!=', 4]])->get();
        if (count($services) == 0) {
            return $this->failedResponse(trans("$string_file.data_not_found"));
        }
        if ($area->Country->distance_unit == 1) {
            $distance_unit = trans("$string_file.km");
        } else {
            $distance_unit = trans("$string_file.miles");
        }
        $return_services = [];
        $arr_vehicle_return = [];
        foreach ($services as $key => $value) {
            $service_id = $value->id;
            $vehicle_type = [];
            $arr_vehicle_return = [];
            if ($service_id == 2 || $service_id == 4) {
                $vehicle_type = VehicleType::whereHas('PriceCard', function ($query) use ($area_id, &$service_id) {
                    $query->where([['country_area_id', '=', $area_id], ['service_type_id', '=', $service_id]]);
                    $query->where('status', 1);
                })->with(['PriceCard' => function ($q) use ($area_id, &$service_id) {
                    $q->where([['country_area_id', '=', $area_id], ['service_type_id', '=', $service_id]])->with('ServicePackage');
                    $q->where('status', 1);
                }])->get();
                if (!empty($vehicle_type->toArray())) {
                    foreach ($vehicle_type as $keys => $valuess) {
                        $price_card = $valuess->PriceCard;
                        $price_card_values = array();
                        foreach ($price_card as $nKey => $nValue) {
                            $pricing_type = $nValue->pricing_type;
                            $parameter_price = $currency . " " . $nValue->base_fare;
                            $description = "Free Distance " . $nValue->free_distance . " " . $distance_unit . " Free Time " . $nValue->free_time . " Mintues";
                            if ($pricing_type == 3) {
                                $parameter_price = trans('api.message129');
                                $description = trans('api.message128');
                            }

                            $price_card_values[] = array(
                                "parameter_price" => $parameter_price,
                                "pricing_parameter" => !empty($nValue->service_package_id) ? $nValue->ServicePackage->PackageName : "",
                                "description" => $description
                            );
                        }
                        unset($valuess->PriceCard);
                        $return_vehicle['price_card_values'] = $price_card_values;
                        $return_vehicle['vehicleTypeName'] = $valuess->VehicleTypeName;
                        $return_vehicle['vehicleTypeDescription'] = $valuess->VehicleTypeDescription;
                        $return_vehicle['vehicleTypeImage'] = get_image($valuess->vehicleTypeImage, 'vehicle', $merchant_id);
                        $arr_vehicle_return[] = $return_vehicle;
                    }
                }
            } else {
                foreach ($value->PriceCard as $login) {
                    $price_card_values = $login->PriceCardValues;
                    $return_price_card = [];
                    foreach ($price_card_values as $values) {
                        $parameter_price = $currency . " " . $values->parameter_price;
                        $parameterType = $values->PricingParameter->parameterType;
                        //                        switch ($parameterType) {
                        //                            case "1":
                        //                                $description = trans('api.message78');
                        //                                break;
                        //                            case "2":
                        //                                $description = trans('api.message79');
                        //                                break;
                        //                            case "3":
                        //                                $description = trans('api.message80');
                        //                                break;
                        //                            case "4":
                        //                                $description = trans('api.message81');
                        //                                break;
                        //                            case "6":
                        //                                $description = trans('api.message81');
                        //                                break;
                        //                            case "7":
                        //                                $description = trans('admin.toll');
                        //                                break;
                        //                            case "8":
                        //                                $description = trans('admin.message4');
                        //                                break;
                        //                            case "9":
                        //                                $description = trans('admin.message219');
                        //                                break;
                        //                            case "10":
                        //                                $description = trans('admin.message220');
                        //                                break;
                        //                            default:
                        //                                $description = "";
                        //                        }
                        $arr_param_desc = get_price_parameter($string_file);
                        $description = isset($arr_param_desc[$parameterType]) ? $arr_param_desc[$parameterType] : "";
                        $return_price_card[] = array(
                            'parameter_price' => $parameter_price,
                            'pricing_parameter' => $values->PricingParameter->ParameterApplication,
                            "description" => $description,
                        );
                    }
                    $base_fare = $login->base_fare;
                    if (!empty($base_fare)) {
                        $parameterBase = PricingParameter::where([['parameterType', '=', 10], ['merchant_id', '=', $merchant_id]])->first();
                        $name = $parameterBase->ParameterApplication;

                        $newBase = array(
                            'parameter_price' => $currency . " " . $login->base_fare,
                            'pricing_parameter' => $name,
                            "description" => "Free Distance " . $login->free_distance . " " . $distance_unit . " Free Time " . $login->free_time . " Mintues",
                        );
                    }

                    array_push($return_price_card, $newBase);
                    $return_vehicle['price_card_values'] = $return_price_card;
                    $return_price_card = array_push($return_price_card, $newBase);
                    $return_vehicle['vehicleTypeName'] = $login->VehicleType->VehicleTypeName;
                    $return_vehicle['vehicleTypeDescription'] = $login->VehicleType->VehicleTypeDescription;
                    $return_vehicle['vehicleTypeImage'] = get_image($login->VehicleType->vehicleTypeImage, 'vehicle', $merchant_id);
                    $arr_vehicle_return[] = $return_vehicle;
                }
            }
            unset($value->PriceCard);
            $return_services[] = ['serviceName' => $value->serviceName, 'vehicle_type' => $arr_vehicle_return];
        }
        return $this->successResponse(trans("$string_file.success"), $return_services);
    }



    //Fav location module is merged with add address in Account/userController
    // There is no use of this module
    public function saveFavouriteLocation(Request $request)
    {
        return $this->successResponse("success");
//        $validator = Validator::make($request->all(), [
//            'latitude' => 'required',
//            'longitude' => 'required',
//            'location' => 'required',
//            'category' => 'required|integer',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return $this->failedResponse($errors[0]);
//            //                response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
//        }
//        $user = $request->user('api');
//        $user_id = $user->id;
//        $string_file = $this->getStringFile(NULL, $user->Merchant);
//        if ($request->category == 3) : //Other category
//            $location = UserAddress::create([
//                'user_id' => $user_id,
//                'latitude' => $request->latitude,
//                'longitude' => $request->longitude,
//                'address' => $request->location,
//                'category' => $request->category,
//                'address_title' => $request->other_name,
//            ]);
//        else :
//            $location = UserAddress::updateOrCreate(
//                ['user_id' => $user_id, 'category' => $request->category],
//                ['latitude' => $request->latitude, 'longitude' => $request->longitude, 'address' => $request->location, 'category' => $request->category, 'address_title' => $request->other_name]
//            );
//        endif;
//        return $this->successResponse(trans("$string_file.success"), $location);
        //            response()->json(['result' => "1", 'message' => trans('api.locationadded'), 'data' => $location]);
    }

    public function viewFavouriteLocation(Request $request)
    {
        return $this->successResponse("success");
//        $validator = Validator::make($request->all(), [
//            //            'category' => 'required|integer',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return $this->failedResponse($errors[0]);
//        }
//        $user = $request->user('api');
//        $user_id = $user->id;
//        $string_file = $this->getStringFile(NULL, $user->Merchant);
//        $locations = UserAddress::where([['user_id', '=', $user_id]])->whereIn('category', [1, 2, 3])->get();
//        return $this->successResponse(trans("$string_file.success"), $locations);
    }

    public function deleteFavouriteLocation(Request $request)
    {
        return $this->successResponse("success");
//        $validator = Validator::make($request->all(), [
//            'id' => 'required|exists:user_addresses,id',
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return $this->failedResponse($errors[0]);
//        }
//        UserAddress::where('id', '=', $request->id)->delete();
//        $string_file = $this->getStringFile($request->merchant_id);
//        return $this->successResponse(trans("$string_file.deleted"), []);
    }





    //    I think this function is not using
    //    public function estimate(Request $request)
    //    {
    //        $validator = Validator::make($request->all(), [
    //            'service_type' => 'required|integer|exists:service_types,id',
    //            'pickup_latitude' => 'required',
    //            'pickup_longitude' => 'required',
    //            'drop_location' => 'required_if:total_drop_location,1,2,3,4',
    //        ]);
    //        if ($validator->fails()) {
    //            $errors = $validator->messages()->all();
    //            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
    //        }
    //        $driver = $request->user('api-driver');
    //        $configuration = BookingConfiguration::where([['merchant_id', '=', $driver->merchant_id]])->first();
    //        $otp_manual_dispatch = $configuration->otp_manual_dispatch == 1 ? true : false;
    //
    //        $driver_Vehicle = DriverVehicle::with(['Drivers' => function ($q) use ($driver) {
    //            $q->where([['driver_id', '=', $driver->id], ['vehicle_active_status', '=', 1]]);
    //        }])->whereHas('Drivers', function ($query) use ($driver) {
    //            $query->where([['driver_id', '=', $driver->id], ['vehicle_active_status', '=', 1]]);
    //        })->first();
    //
    //        if (empty($driver_Vehicle)) {
    //            return response()->json(['result' => "0", 'message' => "No Vehicle Added", 'data' => []]);
    //        }
    //        $merchant_id = $driver->merchant_id;
    //        $pricecards = PriceCard::where([['country_area_id', '=', $driver->country_area_id], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $request->service_type], ['vehicle_type_id', '=', $driver_Vehicle->vehicle_type_id]])->first();
    //        if (empty($pricecards)) {
    //            return ['result' => "0", 'message' => trans('api.no_price_card'), 'data' => []];
    //        }
    //        $drop_locationArray = json_decode($request->drop_location, true);
    //        $googleArray = GoogleController::GoogleStaticImageAndDistance($request->pickup_latitude, $request->pickup_longitude, $drop_locationArray, $configuration->google_key);
    //        if (empty($googleArray)) {
    //            return ['result' => "0", 'message' => trans("$string_file.google_key_not_working"), 'data' => []];
    //        }
    //        $time = $googleArray['total_time_text'];
    //        $distance = $googleArray['total_distance_text'];
    //
    //        $timeSmall = $googleArray['total_time_minutes'];
    //        $distanceSmall = $googleArray['total_distance'];
    //
    //        // Calculate bill estimate
    //        $estimatePrice = new PriceController();
    //        $fare = $estimatePrice->BillAmount([
    //            'price_card_id' => $pricecards->id,
    //            'merchant_id' => $driver->merchant_id,
    //            'distance' => $distanceSmall,
    //            'time' => $timeSmall,
    //            'booking_id' => 0,
    //            'booking_time' => date('H:i'),
    //            'units' => $pricecards->CountryArea->Country->distance_unit
    //        ]);
    //
    //        $merchant = new Merchant();
    //        $fare['amount'] = $merchant->FinalAmountCal($fare['amount'], $driver->merchant_id);
    //
    //        // $newArray = PriceController::CalculateBill($pricecards->id, $distanceSmall, $timeSmall, 0, 0,0,0,$pricecards->CountryArea->Country->distance_unit);
    //        // $newArray = array_filter($newArray, function ($e) {
    //        //     return ($e['type'] == "CREDIT");
    //        // });
    //        //$amount = $pricecards->CountryArea->Country->isoCode . sprintf("%0.2f", array_sum(array_pluck($newArray, 'amount')));
    //
    //        $amount = $pricecards->CountryArea->Country->isoCode . sprintf("%0.2f", $fare['amount']);
    //        return response()->json(['result' => "1", 'message' => "estimate", 'data' => array('time' => $time, 'amount' => $amount, 'distance' => $distance), 'otp_manual_dispatch' => $otp_manual_dispatch]);
    //    }

    public function AddWalletMoneyCoupon(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'coupon_code' => [
                'required',
                Rule::exists('wallet_coupon_codes', 'coupon_code')->where(function ($query) use ($user) {
                    $query->where([['country_id', '=', $user->country_id]]);
                })
            ]
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $coupon = WalletCouponCode::where([['merchant_id', $user->merchant_id], ['country_id', $user->country_id], ['coupon_code', $request->coupon_code]])->first();
        $paramArray = array(
            'user_id' => $user->id,
            'booking_id' => NULL,
            'amount' => $coupon->amount,
            'narration' => 3,
            'platform' => 1,
            'payment_method' => 1,
            'receipt' => $coupon->coupon_code,
        );
        WalletTransaction::UserWalletCredit($paramArray);
        //        \App\Http\Controllers\Helper\CommonController::UserWalletCredit($user->id, NULL, $coupon->amount, 3, 1, 1, $coupon->coupon_code);
        //        $user->wallet_balance = $user->wallet_balance + $coupon->amount;
        //        $user->save();
        //        $moneyAdded = UserWalletTransaction::create([
        //            'merchant_id' => $user->merchant_id,
        //            'user_id' => $user->id,
        //            'platfrom' => 1,
        //            'amount' => $user->wallet_balance,
        //            'type' => 1,
        //            'payment_method' => "Coupon",
        //            'receipt_number' => $coupon->coupon_code,
        //            'description' => "Wallet money added with Coupon" . $coupon->coupon_code
        //        ]);
        //        return response()->json(['result' => "1", 'message' => trans("$string_file.money_added_in_wallet"), 'data' => $moneyAdded]);
    }

    public function redeemPoints(Request $request)
    {
        $validate = validator($request->all(), [
            'reward_points' => 'required|numeric',
        ]);

        if ($validate->fails()) {
            return response()->json(['result' => '0', 'message' => __('api.validation.failed'), 'data' => []]);
        }

        $user = $request->user('api');
        $reward_points_data = RewardPoint::where('merchant_id', $user->merchant_id)
            ->where('country_area_id', $user->country_area_id)
            ->where('active', 1)
            ->first();
        //      dd($reward_points_data);

        if (!$reward_points_data) {
            return response()->json([
                'result' => 0,
                'message' => __('api.reward.notfound'),
                'data' => []
            ]);
        }

        $usable_reward_points = $user->usable_reward_points;

        if ($user) {
            if ($request->reward_points > $user->reward_points || $request->reward_points > $usable_reward_points) {
                return response()->json([
                    'result' => 0,
                    'message' => __('api.points.exceeded'),
                    'data' => []
                ]);
            }

            // recharge user wallet
            $recharge_amount = $request->reward_points / $reward_points_data->value_equals;
            //            $user->wallet_balance = (double)$user->wallet_balance + $recharge_amount;
            $user->reward_points = $user->reward_points - $request->reward_points;
            $user->usable_reward_points = $user->usable_reward_points - $request->reward_points;
            //        $user->use_reward_count = 0;
            $user->save();

            // make wallet transaction
            //            WalletTransaction::userWallet($user, $recharge_amount, 1);
            $paramArray = array(
                'user_id' => $user->id,
                'booking_id' => NULL,
                'amount' => $recharge_amount,
                'narration' => 1,
                'platform' => 2,
                'payment_method' => 2,
            );
            WalletTransaction::UserWalletCredit($paramArray);
            //            \App\Http\Controllers\Helper\CommonController::UserWalletCredit($user->id,NULL,$recharge_amount,1,2,2);

            return response()->json([
                'result' => 1,
                'message' => __('api.success'),
                'data' => []
            ]);
        }


        return response()->json(['result' => '0', 'message' => __('api.user.notfound'), 'data' => []]);
    }

    public function driverRedeemPoints(Request $request)
    {
        $validate = validator($request->all(), [
            'reward_points' => 'required|numeric',
        ]);

        if ($validate->fails()) {
            return response()->json(['result' => '0', 'message' => __('api.validation.failed'), 'data' => []]);
        }

        $driver = $request->user('api-driver');
        $reward_points_data = RewardPoint::where([
            ['merchant_id', '=', $driver->merchant_id],
            ['country_area_id', '=', $driver->country_area_id],
            ['active', '=', 1]
        ])->first();

        if (!$reward_points_data) {
            return response()->json([
                'result' => 0,
                'message' => __('api.reward.notfound'),
                'data' => []
            ]);
        }

        $usable_reward_points = $driver->usable_reward_points;

        if ($driver) {
            if ($request->reward_points > $driver->reward_points || $request->reward_points > $usable_reward_points) {
                return response()->json([
                    'result' => 0,
                    'message' => __('api.points.exceeded'),
                    'data' => []
                ]);
            }

            // recharge user wallet
            $recharge_amount = $request->reward_points / $reward_points_data->value_equals;
            //            $driver->wallet_balance = (double)$driver->wallet_balance + $recharge_amount;
            $driver->reward_points = $driver->reward_points - $request->reward_points;
            //      $driver->use_reward_count = 0;
            $driver->usable_reward_points = $driver->usable_reward_points - $request->reward_points;
            $driver->save();
            $paramArray = array(
                'driver_id' => $driver->id,
                'booking_id' => NULL,
                'amount' => $recharge_amount,
                'narration' => 9,
                'platform' => 1,
                'payment_method' => 2,
            );
            WalletTransaction::WalletCredit($paramArray);
            //            \App\Http\Controllers\Helper\CommonController::WalletCredit($driver->id, NULL, $recharge_amount, 9, 1, 2);
            // make wallet transaction
            //      WalletTransaction::driverWallet($driver , $recharge_amount , 1 );

            return response()->json([
                'result' => 1,
                'message' => __('api.success'),
                'data' => []
            ]);
        }


        return response()->json(['result' => '0', 'message' => __('api.user.notfound'), 'data' => []]);
    }


    public function getNetworkCode(Request $request)
    {
        //        $user = ($request->for == "user") ? $request->user('api') : $request->user('api-driver');
        $paymentConfig = PaymentOptionsConfiguration::where([['merchant_id', $request->merchant_id]])->first();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://xchange.korbaweb.com/api/v1.0/collection_network_options/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n    \"client_id\": \"$paymentConfig->auth_token\"\r\n}",
            CURLOPT_HTTPHEADER => array(
                "authorization: HMAC $paymentConfig->api_public_key:79201d66e586712d736334bb63861cfe6fac39851dc0a709214d9598e4c0d5fc",
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 72da0f74-9b01-46d9-97d6-2edef4867c7d"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return response()->json(['result' => '0', 'message' => $err, 'data' => []]);
        }
        $re = json_decode($response);
        return response()->json([
            'result' => 1,
            'message' => ('api.success'),
            'data' => $re
        ]);
    }

    //    public function geofenceEnqueue(Request $request){
    //        $driver = $request->user('api-driver');
    //        $validator = validator($request->all() , [
    //            'latitude' => 'required',
    //            'longitude' => 'required',
    //            'type' => 'required|between:1,2'
    //        ]);
    //        if ($validator->fails()) {
    //            $errors = $validator->messages()->all();
    //            return response()->json(['result' => "0", 'message' => $errors[0]]);
    //        }
    //        $geofence_queue_text = trans('api.not_in_geofence_queue_area');
    //        $geofence_queue_color_code = '#FF0000';
    //
    //        $config = Configuration::where('merchant_id',$driver->merchant_id)->first();
    //        if(isset($config->geofence_module) && $config->geofence_module == 1){
    //            if($driver->online_offline == 1 && $driver->login_logout == 1 && $driver->free_busy == 2){
    //                $driverArea = CountryArea::find($driver->country_area_id);
    //                $checkGeofenceArea = $this->findGeofenceArea($request->latitude, $request->longitude,$driverArea->id,$driver->merchant_id);
    //                if(!empty($checkGeofenceArea) && isset($checkGeofenceArea->RestrictedArea->queue_system) && $checkGeofenceArea->RestrictedArea->queue_system == 1){
    //                    if($request->type == 1){
    //                        $driverQueue = GeofenceAreaQueue::where(function($query) use($driver,$driverArea, $checkGeofenceArea){
    //                            $query->where([
    //                                ['merchant_id','=',$driver->merchant_id],
    //                                ['country_area_id','=',$driverArea->id],
    //                                ['geofence_area_id','=',$checkGeofenceArea['id']],
    //                                ['driver_id','=',$driver->id],
    //                                ['queue_status','=','1'] // Check if already in queue
    //                            ]);
    //                        })->whereDate('created_at',date('Y-m-d'))->get();
    //                        if(count($driverQueue) <= 0){
    //                            $existingQueue = GeofenceAreaQueue::where(function($query) use($driver,$driverArea, $checkGeofenceArea){
    //                                $query->where([['merchant_id','=',$driver->merchant_id],['country_area_id','=',$driverArea->id],['geofence_area_id','=',$checkGeofenceArea['id']]]);
    //                            })->orderBy('queue_no','desc')->whereDate('created_at',date('Y-m-d'))->first();
    //                            if(!empty($existingQueue)){
    //                                $newQueue = GeofenceAreaQueue::create(
    //                                    ['merchant_id' => $driver->merchant_id,
    //                                        'country_area_id' => $driverArea->id,
    //                                        'geofence_area_id' => $checkGeofenceArea['id'],
    //                                        'driver_id' => $driver->id,
    //                                        'queue_no' => ($existingQueue['queue_no'] + 1),
    //                                        'queue_status' => 1,
    //                                        'entry_time' => date('Y-m-d H:i:s')]);
    //                            }else{
    //                                $newQueue = GeofenceAreaQueue::create(
    //                                    ['merchant_id' => $driver->merchant_id,
    //                                        'country_area_id' => $driverArea->id,
    //                                        'geofence_area_id' => $checkGeofenceArea['id'],
    //                                        'driver_id' => $driver->id,
    //                                        'queue_no' => 1,
    //                                        'queue_status' => 1,
    //                                        'entry_time' => date('Y-m-d H:i:s')]);
    //                            }
    //                            $geofence_queue_text = $checkGeofenceArea->LanguageSingle->AreaName.' Queue On - '.$newQueue->queue_no;
    //                            $geofence_queue_color_code = '#008000';
    //                            return response()->json(['result' => '1', 'type' => '1', 'message' => trans('api.now_in_queue'), 'queue_no' => $newQueue->queue_no,'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
    //                        }else{
    //                            $driverQueue = GeofenceAreaQueue::where(function($query) use($driver,$driverArea, $checkGeofenceArea){
    //                                $query->where([
    //                                    ['merchant_id','=',$driver->merchant_id],
    //                                    ['country_area_id','=',$driverArea->id],
    //                                    ['geofence_area_id','=',$checkGeofenceArea['id']],
    //                                    ['driver_id','=',$driver->id],
    //                                    ['queue_status','=','1'] // Check if already in queue
    //                                ]);
    //                            })->whereDate('created_at',date('Y-m-d'))->first();
    //                            $geofence_queue_text = $checkGeofenceArea->LanguageSingle->AreaName.' Queue On - '.$driverQueue->queue_no;
    //                            $geofence_queue_color_code = '#008000';
    //                            return response()->json(['result' => '1', 'type' => '1', 'message' => trans('api.already_in_queue'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
    //                        }
    //                    }elseif($request->type == 2){
    //                        $this->geofenceDequeue($request->latitude, $request->longitude,$driver,$checkGeofenceArea->id);
    //                        $geofence_queue_text = $checkGeofenceArea->LanguageSingle->AreaName.' Queue Off';
    //                        $geofence_queue_color_code = '#FF0000';
    //                        return response()->json(['result' => '1', 'type' => '2', 'message' => trans('api.removed_from_queue'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
    //                    }
    //                }else{
    //                    return response()->json(['result' => '0', 'message' => trans('api.not_in_geofence_queue_area'),'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
    //                }
    //            }else{
    //                return response()->json(['result' => '0', 'message' => trans('api.not_eligible'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
    //            }
    //        }else{
    //            return response()->json(['result' => '0', 'message' => trans('api.geofence_not_enable'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
    //        }
    //    }
    //
    //    public function geofenceDequeue($lat, $long, $driver, $geofence_area_id){
    //        $config = Configuration::where('merchant_id',$driver->merchant_id)->first();
    //        if(isset($config->geofence_module) && $config->geofence_module == 1){
    //            $geofenceArea = CountryArea::with('RestrictedArea')->where([['is_geofence','=',1],['id','=',$geofence_area_id]])->first();
    //            if(!empty($geofenceArea) && isset($geofenceArea->RestrictedArea->queue_system) && $geofenceArea->RestrictedArea->queue_system == 1){
    //                $existingQueue = GeofenceAreaQueue::where([
    //                    ['merchant_id','=',$driver->merchant_id],
    //                    ['country_area_id', '=', $driver->country_area_id],
    //                    ['geofence_area_id','=',$geofence_area_id],
    //                    ['driver_id','=',$driver->id],
    //                    ['queue_status','=','1'] // Check if already in queue
    //                ])->whereDate('created_at',date('Y-m-d'))->first();
    //                if(!empty($existingQueue)){
    //                    $existingQueue->queue_status = 2;
    //                    $existingQueue->exit_time = date('Y-m-d H:i:s');
    //                    $existingQueue->save();
    //                }
    //            }
    //        }
    //    }

    public function geofenceQueueInOut(Request $request)
    {
        $driver = $request->user('api-driver');

        $validator = validator($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
            'type' => 'required|between:1,2'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            //            return response()->json(['result' => "0", 'message' => $errors[0]]);
        }
        $geofence_queue_text = trans('api.not_in_geofence_queue_area');
        $geofence_queue_color_code = '#FF0000';

        $config = Configuration::where('merchant_id', $driver->merchant_id)->first();
        if (isset($config->geofence_module) && $config->geofence_module == 1) {
            if ($driver->online_offline == 1 && $driver->login_logout == 1 && $driver->free_busy == 2) {
                $driverArea = CountryArea::find($driver->country_area_id);
                $checkGeofenceArea = $this->findGeofenceArea($request->latitude, $request->longitude, $driverArea->id, $driver->merchant_id);
                if (!empty($checkGeofenceArea) && isset($checkGeofenceArea->RestrictedArea->queue_system) && $checkGeofenceArea->RestrictedArea->queue_system == 1) {
                    if ($request->type == 1) {
                        $driverQueue = GeofenceAreaQueue::where(function ($query) use ($driver, $driverArea, $checkGeofenceArea) {
                            $query->where([
                                ['merchant_id', '=', $driver->merchant_id],
                                ['country_area_id', '=', $driverArea->id],
                                ['geofence_area_id', '=', $checkGeofenceArea['id']],
                                ['driver_id', '=', $driver->id],
                                ['queue_status', '=', '1'] // Check if already in queue
                            ]);
                        })->whereDate('created_at', date('Y-m-d'))->get();
                        if (count($driverQueue) <= 0) {
                            $existingQueue = GeofenceAreaQueue::where(function ($query) use ($driver, $driverArea, $checkGeofenceArea) {
                                $query->where([['merchant_id', '=', $driver->merchant_id], ['country_area_id', '=', $driverArea->id], ['geofence_area_id', '=', $checkGeofenceArea['id']]]);
                            })->orderBy('queue_no', 'desc')->whereDate('created_at', date('Y-m-d'))->first();
                            if (!empty($existingQueue)) {
                                $newQueue = GeofenceAreaQueue::create(
                                    [
                                        'merchant_id' => $driver->merchant_id,
                                        'country_area_id' => $driverArea->id,
                                        'geofence_area_id' => $checkGeofenceArea['id'],
                                        'driver_id' => $driver->id,
                                        'queue_no' => ($existingQueue['queue_no'] + 1),
                                        'queue_status' => 1,
                                        'entry_time' => date('Y-m-d H:i:s')
                                    ]
                                );
                            } else {
                                $newQueue = GeofenceAreaQueue::create(
                                    [
                                        'merchant_id' => $driver->merchant_id,
                                        'country_area_id' => $driverArea->id,
                                        'geofence_area_id' => $checkGeofenceArea['id'],
                                        'driver_id' => $driver->id,
                                        'queue_no' => 1,
                                        'queue_status' => 1,
                                        'entry_time' => date('Y-m-d H:i:s')
                                    ]
                                );
                            }
                            $geofence_queue_text = $checkGeofenceArea->LanguageSingle->AreaName . ' Queue On - ' . $newQueue->queue_no;
                            $geofence_queue_color_code = '#008000';
                            $result = array(
                                'queue_no' => $newQueue->queue_no,
                                'geofence_queue_text' => $geofence_queue_text,
                                'geofence_queue_color' => $geofence_queue_color_code,
                                'type' => 1
                            );
                            $message = trans('api.now_in_queue');
                            //                            return response()->json(['result' => '1', 'type' => '1', 'message' => trans('api.now_in_queue'), 'queue_no' => $newQueue->queue_no,'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
                        } else {
                            $driverQueue = GeofenceAreaQueue::where(function ($query) use ($driver, $driverArea, $checkGeofenceArea) {
                                $query->where([
                                    ['merchant_id', '=', $driver->merchant_id],
                                    ['country_area_id', '=', $driverArea->id],
                                    ['geofence_area_id', '=', $checkGeofenceArea['id']],
                                    ['driver_id', '=', $driver->id],
                                    ['queue_status', '=', '1'] // Check if already in queue
                                ]);
                            })->whereDate('created_at', date('Y-m-d'))->first();
                            $geofence_queue_text = $checkGeofenceArea->LanguageSingle->AreaName . ' Queue On - ' . $driverQueue->queue_no;
                            $geofence_queue_color_code = '#008000';
                            $result = array(
                                'queue_no' => $driverQueue->queue_no,
                                'geofence_queue_text' => $geofence_queue_text,
                                'geofence_queue_color' => $geofence_queue_color_code,
                                'type' => '1'
                            );
                            $message = trans('api.already_in_queue');
                            //                            return response()->json(['result' => '1', 'type' => '1', 'message' => trans('api.already_in_queue'), 'queue_no' => $driverQueue->queue_no, 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
                        }
                    } elseif ($request->type == 2) {
                        $result = $this->geofenceDequeue($request->latitude, $request->longitude, $driver, $checkGeofenceArea->id);
                        if (!$result) {
//                            return response()->json(['result' => '0', 'message' => "You Can't Exit Before 15 Minute."]);
                            return $this->failedResponse("You Can't Exit Before 1 Minute.");
                        }
                        $geofence_queue_text = $checkGeofenceArea->LanguageSingle->AreaName . ' Queue Off';
                        $geofence_queue_color_code = '#FF0000';
                        $result = array(
                            'geofence_queue_text' => $geofence_queue_text,
                            'geofence_queue_color' => $geofence_queue_color_code,
                            'type' => '2'
                        );
                        $message = trans('api.removed_from_queue');
                        //                        return response()->json(['result' => '1', 'type' => '2', 'message' => trans('api.removed_from_queue'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
                    } else {
                        return $this->failedResponse(trans('api.invalid_type'));
                    }
                    return $this->successResponse($message, $result);
                } else {
                    return $this->failedResponse(trans('api.not_in_geofence_queue_area'), array('geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code));
                    //                    return response()->json(['result' => '0', 'message' => trans('api.not_in_geofence_queue_area'),'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
                }
            } else {
                return $this->failedResponse(trans('api.not_eligible'), array('geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code));
                //                return response()->json(['result' => '0', 'message' => trans('api.not_eligible'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
            }
        } else {
            return $this->failedResponse(trans('api.geofence_not_enable'), array('geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code));
            //            return response()->json(['result' => '0', 'message' => trans('api.geofence_not_enable'), 'geofence_queue_text' => $geofence_queue_text, 'geofence_queue_color' => $geofence_queue_color_code]);
        }
    }

    public function geofenceDequeue($lat, $long, $driver, $geofence_area_id)
    {
        $config = Configuration::where('merchant_id', $driver->merchant_id)->first();
        if (isset($config->geofence_module) && $config->geofence_module == 1) {
            $geofenceArea = CountryArea::with('RestrictedArea')->where([['is_geofence', '=', 1], ['id', '=', $geofence_area_id]])->first();
            if (!empty($geofenceArea) && isset($geofenceArea->RestrictedArea->queue_system) && $geofenceArea->RestrictedArea->queue_system == 1) {
                $existingQueue = GeofenceAreaQueue::where([
                    ['merchant_id', '=', $driver->merchant_id],
                    ['country_area_id', '=', $driver->country_area_id],
                    ['geofence_area_id', '=', $geofence_area_id],
                    ['driver_id', '=', $driver->id],
                    ['queue_status', '=', '1'] // Check if already in queue
                ])->whereDate('created_at', date('Y-m-d'))->first();
                if (!empty($existingQueue)) {
                    $existingQueueTime = strtotime($existingQueue->created_at->toDateTimeString());
                    $currentTime = strtotime(Carbon::now()->toDateTimeString());
                    $total_diff_mint = ($currentTime - $existingQueueTime) / 60;
                    if ($total_diff_mint < 1) {
                        return false;
                    } else {
                        $existingQueue->queue_status = 2;
                        $existingQueue->exit_time = date('Y-m-d H:i:s');
                        $existingQueue->save();
                        return true;
                    }
                }
            }
        }
    }

    public function findGeofenceArea($lat, $long, $base_area_id, $merchant_id)
    {
        $geofenceAreas = CountryArea::with('RestrictedArea')->whereHas('RestrictedArea', function ($query) use ($base_area_id) {
            $query->whereRaw(DB::raw("find_in_set($base_area_id,base_areas)"));
        })->get();
        $checkGeofenceArea = [];
        if (!empty($geofenceAreas)) {
            foreach ($geofenceAreas as $geofenceArea) {
                $checkGeofenceArea = $this->GeofenceArea($lat, $long, $merchant_id, $geofenceArea->id);
                if (!empty($checkGeofenceArea)) {
                    $geofenceAreaFound = CountryArea::with('RestrictedArea')->find($checkGeofenceArea['id']);
                    return $geofenceAreaFound;
                }
            }
        }
        return $checkGeofenceArea;
    }

    public function geofenceInOut(Request $request)
    {
        $validator = validator($request->all(), [
            'type' => 'required', // 1 - In, 2 - Out
            'geofence_area_id' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $geofence_area = CountryArea::find($request->geofence_area_id);
            $driver = $request->user('api-driver');
            if ($request->type == 1) {
                $message = trans('api.welcome_to_geofence_area') . ' : ' . $geofence_area->CountryAreaName;
                $title = trans('api.welcome_to') . ' : ' . $geofence_area->CountryAreaName;
            } elseif ($request->type == 2) {
                $message = trans('api.thanks_for_visit_geofence_area') . ' : ' . $geofence_area->CountryAreaName;
                $title = trans('api.exit_from') . ' : ' . $geofence_area->CountryAreaName;
            } else {
                return $this->failedResponse(trans('api.invalid_type'));
            }
            $large_icon = '';
            $data = array(
                'notification_type' => "GEOFENCE",
                'segment_type' => "TAXI",
                'segment_data' => time(),
                'notification_gen_time' => time(),
            );
            $arr_param = ['driver_id' => $driver->id, 'data' => $data, 'message' => $message, 'merchant_id' => $driver->merchant_id, 'title' => $title, 'large_icon' => $large_icon];
            Onesignal::DriverPushMessage($arr_param);
            return $this->successResponse(trans('api.geofence_updated_successfully'));
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function SaveGuestUserInfo(Request $request)
    {
        $string_file = $this->getStringFile($request->merchant_id);
        $validator = validator($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            'merchant_id' => 'required',
            'secret_key' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0]]);
        }
        try {
            $merchant = DB::table('merchants')->where(['id' => $request->merchant_id, 'merchantSecretKey' => $request->secret_key])->first();
            if (!empty($merchant)) {
                DB::table('guest_users')->insert(
                    ['merchant_id' => $merchant->id, 'name' => $request->name, 'phone' => $request->phone]
                );
                return response()->json(['result' => "1", 'message' => trans('api.guest_record_saved_successfully')]);
            } else {
                return response()->json(['result' => "0", 'message' => trans("$string_file.merchant_not_found")]);
            }
        } catch (\Exception $e) {
            $errors = $e->getMessage();
            return response()->json(['result' => "0", 'message' => $errors]);
        }
    }

    function getPaymentMethod(Request $request)
    {
        $validator = validator($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0]]);
        }
        $paymentMethods = NULL;
        $user = $request->user('api');
        $string_file = $this->getStringFile($request->merchant_id);
        try {
            $this->getAreaByLatLong($request, $string_file);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }

        if (!empty($request->area)) {
            $service_area = CountryArea::where('id', $request->area)->first();
            if ($request->payment_type == "ADVANCE_PAYMENT") {
                $paymentMethods = $service_area->PaymentMethod->where('id', '!=', 1);
            } elseif (!empty($request->outstanding_id)) {
                $paymentMethods = $service_area->PaymentMethod->whereNotIn('id', [1, 6]);
            } elseif ($request->payment_type == "PAYLATER") {
                $paymentMethods = $service_area->PaymentMethod;
            } else {
                $paymentMethods = $service_area->PaymentMethod->where('id', '!=', 6);
            }
        }
        $bookingData = new BookingDataController();
        $options = $bookingData->PaymentOption($paymentMethods, $user->id, null, NULL);
        return $this->successResponse(trans("$string_file.data_found"), $options);
    }

    public function segmentSubGroup($segment_slug, $segment_group_id = 2)
    {
        $sub_group = "";
        $segment_sub_group = \Config::get('custom.segment_sub_group');
        foreach ($segment_sub_group as $sub_group_key => $group) {
            $in_array = in_array($segment_slug, $group);
            if ($in_array == true) {
                $sub_group = $sub_group_key;
                break;
            }
        }
        if (empty($sub_group) && $segment_group_id == 2) {
            $sub_group = 'handyman_order';
        }
        return $sub_group;
    }

    // common apis for all segments

    /*
     *  get booking or order information on driver screen
    */
    public function bookingOrderInfo(Request $request)
    {
        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];

        $request->request->add(['id' => $request->booking_order_id]);
        $string_file = $this->getStringFile($request->merchant_id);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {

            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingController;
                    $return_data = $booking->driverBookingInfo($request);
                    break;
                case "order":
                    $order = new OrderController;
                    $return_data = $order->getOrderInformation($request);
                    break;
                case "laundry_outlet":
                    $order = new LaundryOrderController;
                    $return_data = $order->getLaundryOrderInformation($request);
                    break;

                case "handyman_order":
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction

            return $this->failedResponse($message);
        }
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }


    /*
    *  update status of booking or order
   */
    public function bookingOrderAcceptReject(Request $request)
    {
        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
            'status' => 'required|in:ACCEPT,REJECT',
            'latitude' => 'required',
            'longitude' => 'required',
        ];
        //        [
        //            'id' => [
        //                'required',
        //                'integer',
        //                Rule::exists('orders', 'id')->where(function ($query) {
        //                    $query->whereIn('booking_status', array(1));
        //                }),
        //                Rule::exists('booking_request_drivers')->where(function ($query) use ($driver_id) {
        //                    $query->where('driver_id', $driver_id);
        //                }),
        //            ],
        //        ], [
        //        'exists' => trans('api.ride_already'),
        //    ]


        $request->request->add(['id' => $request->booking_order_id]);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        if(!empty($request->timestampvalue)){
            $cacheKey = 'booking_order_accept_reject_' . $request->timestampvalue;

            if (Cache::has($cacheKey)) {
                $response = Cache::get($cacheKey);
                $message = $response['message'];
                $return_data = $response['return_data'];
                return $this->successResponse($message, $return_data);
            }
        }

        $return_data = [];
        try {

            $message = "";
            $driver = $request->user('api-driver');
            $driver_config = DriverConfiguration::where("merchant_id", $driver->merchant_id)->first();
            $string_file = $this->getStringFile(Null, $driver->Merchant);
            if ($driver->free_busy == 1) {
                if (isset($driver_config->delivery_busy_driver_accept_ride) && $driver_config->delivery_busy_driver_accept_ride == 1) {
                    // Skip this case
                } else {
                    // creating issue in pool ride
                    // return $this->failedResponse(trans("$string_file.existing_ride_order_error"));
                }
            }
            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    // $this->waitForAccept($request->booking_order_id, $request->user('api-driver')->id);
                    $booking = new BookingController;
                    $response = $booking->bookingAcceptReject($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "order":
                    $order = new OrderController;
                    //$this->waitOrderForAccept($request->booking_order_id,$driver->id);
                    $response = $order->orderAcceptReject($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "laundry_outlet":
                    $order = new LaundryOrderController;
                    $response = $order->orderAcceptReject($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "handyman_order":
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // p($message);
            // Rollback Transaction

            return $this->failedResponse($message);
        }
        if(!empty($request->timestampvalue)){
            Cache::put($cacheKey, ["message" => $message, "return_data" => $return_data], 120);
        }
        return $this->successResponse($message, $return_data);
    }


    /*
    *  get booking or order information on driver screen
   */
    public function sliderData(Request $request)
    {
        $request_fields = [];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {
            $driver = $request->user('api-driver');
            $return_data['working_with_microservices'] = $driver->Merchant->ApplicationConfiguration->working_with_microservices == 1;
            $return_data['microservice_path'] = "";
            $return_data['jwt_token'] = "";
            if($driver->Merchant->ApplicationConfiguration->working_with_redis == 1){
                if(!empty($driver->DriverDetail)){
                    $jwt = $driver->DriverDetail->driver_jwt_token;
                }
                else{
                    $jwt = getJwtToken($driver->id, "multi-service-v3", "DRIVER");
                    $driver_details = new DriverDetail();
                    $driver_details->driver_id = $driver->id;
                    $driver_details->driver_jwt_token = $jwt;
                    $driver_details->save();
                }
                $return_data['jwt_token'] = $jwt;
                $return_data['microservice_path'] = env('MICRO_SERVICE_APP_URL');
            }
            if (!empty($request->player_id)) {
                $driver->player_id = ($request->player_id) == 'null' ? NULL : $request->player_id;
                $driver->device = $request->device;
                $driver->save();
            }
            // Set language for notification
            $commonObj = new \App\Http\Controllers\Helper\CommonController();
            $commonObj->setLanguage($driver->id, 2);

            $string_file = $this->getStringFile(NULL, $driver->Merchant);
            if ($driver->Merchant->Configuration->driver_wallet_status == 1 && !empty($driver->CountryArea->minimum_wallet_amount) && !empty($driver->wallet_money) && $driver->wallet_money < $driver->CountryArea->minimum_wallet_amount) {
                $message = trans_choice("$string_file.low_wallet_warning", 3, ['AMOUNT' => $driver->CountryArea->minimum_wallet_amount]);
                return $this->failedResponseWithData($message, $return_data);
                //                return $this->failedResponse(trans("$string_file.low_wallet_warning"));
            }
            $data = [];
            $return_online_config = (object)[];
            // $pool_status = false;
            $online_config = $this->getDriverOnlineConfig($driver, 'online_details');
            if ($driver->segment_group_id == 1 || $driver->segment_group_id == 2 ) {
                $booking = new BookingController;
                $return_data1 = $booking->getOngoingBookings($request);
                $order = new OrderController;
                $return_data2 = $order->getOngoingOrders($request);
                // $laundry_order = new LaundryOrderController;
                // $return_data3 = $laundry_order->getOngoingOrders($request);
                $return_data3 = [];
                if(in_array("LAUNDRY_OUTLET", $driver->Merchant->Segment->pluck("slag")->toArray())){
                    $laundry_order = new LaundryOrderController;
                    $return_data3 = $laundry_order->getOngoingOrders($request);
                }
                $data = array_merge($return_data1, $return_data2, $return_data3);

                $return_online_config = $online_config['detail'];
            }
            $ride_acco_to_gender = $driver->Merchant->ApplicationConfiguration->gender == 1 ? (($driver->driver_gender == NULL || $driver->driver_gender == 1) ? false : true) : false;
            $return_data['driver_mode'] = $data;
            $return_data['driver_mode_count'] = count($data);
            $return_data['driver_free_busy_status'] = $driver->free_busy;
            $return_data['driver_online_offline_status'] = $driver->online_offline;
            $return_data['driver_kin_details_submitted'] = !empty($driver->kin_details);
            $return_data['driver_area_downgrade_config'] = ($driver->Merchant->Configuration->manual_downgrade_enable == 1 && $driver->CountryArea->manual_downgradation == 1);
            $return_data['term_status'] = $driver->term_status;
            $return_data['rides_according_to_gender'] = $ride_acco_to_gender;
            $return_data['rider_gender_choice'] = (string)$driver->rider_gender_choice;
            $return_data['driver_gender'] = $driver->driver_gender;
            $return_data['work_set'] = $return_online_config;
            $return_data['pay_mode'] = $driver->pay_mode == 1 ? "SUBSCRIPTION_BASED" : "COMMISSION_BASED";
            $return_data['is_super_driver'] = isset($driver->is_super_driver) && $driver->is_super_driver == 1 ? true : false;
            $return_data['is_business_profile'] = !empty($driver->business_name) ? true : false;
            $return_data['business_name'] = !empty($driver->business_name) ? $driver->business_name : "";
            // $return_data['pool_enable_status'] = (int) $driver->pool_ride_active;

            // if pacakge is active for any segment then it will show as active
            $all_segment_subscritption = true;
            $renewable_subscription_details= [
                "renewable_subscription_price"=> 0,
                "last_renew_date" => "",
                "totalEarnings"=> 0
            ];
            $subscription_segment_message = "";
            $subscription_status = false;
            $subscription_message = trans("$string_file.subscription_error");
            if ($driver->pay_mode == 1) {
                if($driver->Merchant->Configuration->subscription_package_type == 1){
                    $active_subscription = $driver->DriverActiveSubscriptionRecord->count();
                    $arr_packages_segment = $driver->DriverActiveSubscriptionRecord->count() > 0 ? $driver->DriverActiveSubscriptionRecord->pluck('segment_id')->toArray() : [];
                    $arr_online_config_segment = $driver->ServiceTypeOnline->count() > 0 ? $driver->ServiceTypeOnline->pluck('segment_id')->toArray() : [];
                    //p($arr_online_config_segment);
                    if (count(array_diff($arr_online_config_segment, $arr_packages_segment)) > 0) {
                        $all_segment_subscritption = false;
                        $subscription_segment_message = trans("$string_file.segment_subscription_error");
                    }
                    if ($active_subscription > 0) {
                        $subscription_status = true;
                        $subscription_message = "";
                    }
                }
                else{
                    if ($driver->hasActiveRenewableSubscriptionRecord()) {
                        $subscription_status = true;
                        $subscription_message = "";
                        $all_segment_subscritption = false;

                    }
                    else{
                        $all_segment_subscritption = false;
                        $work_config = $this->getDriverOnlineConfig($driver, 'online_details');
                        $vehicle_type_id  = $work_config['vehicle_type_id'];
                        $common_controller = new \App\Http\Controllers\Helper\CommonController();
                        $renewable_subscription_details= $common_controller->getRenewableSubscriptionDetails($driver, $vehicle_type_id);
                    }
                }
            }

            $return_data['active_subscription'] = ['status' => $subscription_status, "message" => $subscription_message, "details" => [
                "all_segment_subscritption" => $all_segment_subscritption,
                "subscription_segment_message" => $subscription_segment_message,
                "renewable_subscription_price"=> round_number($renewable_subscription_details['renewable_subscription_price'], 0),
                "renewable_subscription_heading"=>trans("$string_file.renewable_subscription_heading", ['date' => $renewable_subscription_details['last_renew_date']]),
                "renewable_subscription_message"=>trans("$string_file.renewable_subscription_message"),
                "driver_previous_earning"=>round_number($renewable_subscription_details['totalEarnings'], 0),
                "currency"=> $driver->Country->isoCode,
            ]];

            $config = $driver->Merchant->BookingConfiguration;
            $upcoming_bookings = 0;
            if ($config->ride_later_ride_allocation == 2){
                $online_work_set = $this->getDriverOnlineConfig($driver, 'all');
                if (!empty($online_work_set)){
                    $driver_vehicle_id = $online_work_set['driver_vehicle_id'];
                    $driver_vehicle_id = isset($driver_vehicle_id[0]) ? $driver_vehicle_id[0] : NULL;
                    $service_type_id = $online_work_set['service_type_id'];
                    $driver_vehicle = $driver->Vehicle->where('id', $driver_vehicle_id);
                    $vehicle_type_id = NULL;
                    foreach ($driver_vehicle as $vehicle) {
                        if (!empty($vehicle->id)) {
                            $vehicle_type_id = $vehicle->VehicleType->id;
                            break;
                        }
                    }
                    $driver_area_notification = isset($driver->Merchant->Configuration->driver_area_notification) ? $driver->Merchant->Configuration->driver_area_notification : 2;
                    $upcoming_bookings = Booking::UpcomingBookings($driver->country_area_id, $driver->current_latitude, $driver->current_longitude, $vehicle_type_id, $service_type_id, $config->normal_ride_later_radius, $driver->id, $driver_area_notification, $config->ride_later_ride_allocation, $request->date);
                    $upcoming_bookings = count($upcoming_bookings);
                }
            }
            $return_data['upcoming_bookings'] = $upcoming_bookings;

            $return_data['geofence_queue'] = array(
                'enable' => false,
                'active' => false,
                'text' => trans("$string_file.not_in_geofence_queue_area"),
                'color' => '#FF0000'
            );
            if(isset($driver->Merchant->Configuration->geofence_module) && $driver->Merchant->Configuration->geofence_module == 1){
                if(isset($driver->country_area_id) && $driver->country_area_id != ''){
                    $base_area_id = $driver->country_area_id;
                    $geofenceAreas = CountryArea::with('RestrictedArea')->whereHas('RestrictedArea',function($query) use($base_area_id){
                        $query->whereRaw(DB::raw("find_in_set($base_area_id,base_areas)"));
                    })->get();
                    if(!empty($geofenceAreas)){
                        $return_data['geofence_queue']['enable'] = true;
                        $commonController = new CommonController();
                        $checkGeofenceArea = $commonController->findGeofenceArea($driver->current_latitude, $driver->current_longitude,$base_area_id,$driver->merchant_id);
                        if(!empty($checkGeofenceArea) && isset($checkGeofenceArea->RestrictedArea->queue_system) && $checkGeofenceArea->RestrictedArea->queue_system == 1){
                            $driverQueue = GeofenceAreaQueue::where(function($query) use($base_area_id, $checkGeofenceArea, $driver){
                                $query->where([
                                    ['merchant_id','=',$driver->merchant_id],
                                    ['country_area_id','=',$base_area_id],
                                    ['geofence_area_id','=',$checkGeofenceArea['id']],
                                    ['driver_id','=',$driver->id],
                                    ['queue_status','=','1'] // Check if already in queue
                                ]);
                            })->whereDate('created_at',date('Y-m-d'))->first();
                            if(empty($driverQueue)){
                                $return_data['geofence_queue']['active'] = false;
                                $return_data['geofence_queue']['text'] = $checkGeofenceArea->LanguageSingle->AreaName.' Queue Off';
                                $return_data['geofence_queue']['color'] = '#FF0000';
                            }else{
                                $return_data['geofence_queue']['active'] = true;
                                $return_data['geofence_queue']['text'] = $checkGeofenceArea->LanguageSingle->AreaName.' Queue On - '.$driverQueue->queue_no;
                                $return_data['geofence_queue']['color'] = '#008000';
                            }
                        }
                    }
                }
            }
            $return_data['handyman_availability'] = [
                'visibility' => $this->getTimeSlotAvailability($driver),
                'text' => trans("$string_file.no_slot_active_for_current_time"),
            ];
            $return_data['cms_pages'] = CmsPage::where([ ['merchant_id', '=', $driver->Merchant->id], ['application', '=', 2]])
                                                            ->where(function ($query) use($driver) {
                                                                $query->where('country_id', $driver->country_id)
                                                                    ->orWhereNull('country_id');
                                                            })->pluck('slug')
                                                                ->toArray();
            $driverPhone = $driver->phoneNumber;
            $firstName =  $driver->first_name;
            $lastName =  $driver->last_name;
            $email =  $driver->email;
            $driverImage = "";
            if(isset($driver->profile_image)){
                $driverImage = get_image($driver->profile_image, 'driver', $driver->merchant_id, true, false);
            }
            // if($driver->Merchant->Configuration->encrypt_decrypt_enable == 1){
            //     try {
            //         $keys = getSecAndIvKeys();
            //         $iv = $keys['iv'];
            //         $secret = $keys['secret'];

            //         if($driverPhone){
            //             $driverPhone = encryptText($driverPhone,$secret,$iv);
            //         }
            //         if($firstName){
            //             $firstName = encryptText($firstName,$secret,$iv);
            //         }
            //         if($lastName){
            //             $lastName = encryptText($lastName,$secret,$iv);
            //         }
            //         if($email){
            //             $email = encryptText($email,$secret,$iv);
            //         }
            //         if($driverImage){
            //             $driverImage = encryptText($driverImage,$secret,$iv);
            //         }
            //     } catch (Exception $e) {
            //         echo 'Error: ' . $e->getMessage();
            //     }
            // }

            $return_data['driver_data'] = [
                'id' => $driver->id,
                'phone' =>$driverPhone,
                'name'=> $firstName . $lastName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' =>$email,
                'image'=> $driverImage
            ];

            //for mpesa check transaction
            $driverTimezone = $driver->CountryArea->timezone;
            $startOfDay = Carbon::now($driverTimezone)->startOfDay()->timezone('UTC');
            $endOfDay = Carbon::now($driverTimezone)->endOfDay()->timezone('UTC');
            
            $transaction = DB::table('transactions')->where('driver_id', $driver->id)->whereBetween('created_at', [$startOfDay, $endOfDay])->first();
            $return_data['payment_transaction_id'] = "";
            if(!empty($transaction)){
                $return_data['payment_transaction_id'] = $transaction->payment_transaction_id;
            }
            
            //for driver online time
            $driver_online_today = \App\Models\DriverOnlineTime::where([['driver_id', $driver->id]])->whereBetween('created_at', [Carbon::now()->setTime(0, 0, 0)->format('Y-m-d H:i:s'), Carbon::now()->setTime(23, 59, 59)->format('Y-m-d H:i:s')])->first();
            $return_data['driver_online_time'] = "";
            if(!empty($driver_online_today)){
                $return_data['driver_online_time'] = $driver_online_today->hours. ' hrs ' . $driver_online_today->minutes . ' min';
            }
            
            return $this->successResponse(trans("$string_file.data_found"), $return_data);
        } catch (\Exception $e) {
            throw $e;
            $message = $e->getMessage();
            // Rollback Transaction
            return $this->failedResponseWithData($message, $return_data);
        }
    }


    /*
    *  driver arrived at pickup location
   */
    public function arrivedAtPickup(Request $request)
    {

        if(!empty($request->timestampvalue)){
            $cacheKey = 'arrived_at_pickup_' . $request->timestampvalue;

            if (Cache::has($cacheKey)) {
                $response = Cache::get($cacheKey);
                $message = $response['message'];
                $return_data = $response['return_data'];
                return $this->successResponse($message, $return_data);
            }
        }

        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
            // 'status' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];

        $request->request->add(['id' => $request->booking_order_id]);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {

            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingController;
                    $response = $booking->arrivedAtPickup($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "order":
                    $order = new OrderController;
                    $response = $order->arrivedAtPickup($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "handyman_order":
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        if(!empty($request->timestampvalue)){
            Cache::put($cacheKey, ["message" => $message, "return_data" => $return_data], 120);
        }
        return $this->successResponse($message, $return_data);
    }

    /*
    *  driver arrived at pickup location
   */
    public function orderInProcess(Request $request)
    {
        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
            'status' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];

        $request->request->add(['id' => $request->booking_order_id]);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {

            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingController;
                    $response = $booking->BookingAccept($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "order":
                    $order = new OrderController;
                    $response = $order->OrderInProcess($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "handyman_order":
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse($message, $return_data);
    }


    /*
    *  driver arrived at pickup location and now driver either pick order or start ride
   */
    public function bookingOrderPicked(Request $request)
    {
        if(!empty($request->timestampvalue)){
            $cacheKey = 'booking_order_picked_' . $request->timestampvalue;

            if (Cache::has($cacheKey)) {
                $response = Cache::get($cacheKey);
                $message = $response['message'];
                $return_data = $response['return_data'];
                return $this->successResponse($message, $return_data);
            }
        }

        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
            //            'status' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];

        $request->request->add(['id' => $request->booking_order_id]);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {

            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingController;
                    $response = $booking->startBooking($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "order":
                    $order = new OrderController;
                    $response = $order->pickedOrder($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "handyman_order":
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }

        if(!empty($request->timestampvalue)){
            Cache::put($cacheKey, ["message" => $message, "return_data" => $return_data], 120);
        }
        return $this->successResponse($message, $return_data);
    }


    /*
  * driver  delivered order
   */
    public function deliverOrder(Request $request)
    {
        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
            //            'status' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];

        $request->request->add(['id' => $request->booking_order_id]);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {

            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingController;
                    $response = $booking->BookingAccept($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "order":
                    $order = new OrderController;
                    $response = $order->deliverOrder($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "handyman_order":
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse($message, $return_data);
    }


    /*
    * get booking order payment info
   */
    public function bookingOrderPaymentInfo(Request $request)
    {
        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
        ];

        $request->request->add(['id' => $request->booking_order_id]);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $return_data = [];
        try {
            $string_file = $this->getStringFile($request->merchant_id);
            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingController;
                    $response = $booking->BookingAccept($request);
                    $return_data = $response['data'];
                    break;
                case "order":
                    $order = new OrderController;
                    $response = $order->orderPaymentInfo($request);
                    $return_data = $response['data'];
                    break;
                case "handyman_order":
                    break;
                case "laundry_outlet":
                    $order = new LaundryOrderController;
                    $return_data = $order->orderPaymentInfo($request);
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    /*
    * update booking order payment status
   */
    public function updateBookingOrderPaymentStatus(Request $request)
    {
        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
            'payment_status' => 'required|in:0,1', //0 means pending, 1 means paid, 3 means failed
        ];

        $request->request->add(['id' => $request->booking_order_id]);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $return_data = [];
        try {
            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                //                case "booking":
                //                    $booking = new BookingController;
                //                    $response = $booking->BookingAccept($request);
                //                    break;
                case "order":
                    $order = new OrderController;
                    $response = $order->updateOrderPaymentStatus($request);
                    break;
                case "handyman_order":
                    break;
                case "laundry_outlet":
                    $order = new LaundryOrderController;
                    $response = $order->updateLaundryOrderPaymentStatus($request);
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse(trans('api.payment_status_updated'), $return_data);
    }

    /*
    *  driver completed ride, delivered order
   */
    public function completeBookingOrder(Request $request)
    {
        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
        ];

        $request->request->add(['id' => $request->booking_order_id]);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {
            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingController;
                    $response = $booking->completeBooking($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "order":
                    $order = new OrderController;
                    $response = $order->completeOrder($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "handyman_order":
                    break;
                case "laundry_outlet":
                    $order = new LaundryOrderController;
                    $response = $order->completeOrder($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
            }
            //            $return_data['data']['driver_offline']  = true; // means driver online
            $return_data['data']['driver_online'] = true; // means driver online
            $driver = $request->user('api-driver');
            if ($driver->Merchant->Configuration->driver_wallet_status == 1 && $driver->wallet_money < $driver->CountryArea->minimum_wallet_amount) {
                //                $return_data['data']['driver_offline']  = false; // means driver offline
                $return_data['data']['driver_online'] = false; // means driver offline
                $driver->online_offline = 2;
                $driver->save();
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse($message, $return_data);
    }


    /*
    *  driver cancel ride, order
   */
    public function cancelBookingOrder(Request $request)
    {
        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];

        $request->merge(['id' => $request->booking_order_id]);
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {

            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingController;
                    $response = $booking->cancelBooking($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "order":
                    $order = new OrderController;
                    $response = $order->cancelOrder($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "laundry_outlet":
                    $driverController = new \App\Http\Controllers\LaundryOutlet\Api\DriverController();
                    $response = $driverController->cancelOrder($request);
                    $message = $response['message'];
                    $return_data = $response['data'];
                    break;
                case "handyman_order":
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse($message, $return_data);
    }

    /*
   *  driver get active booking order
  */
    public function getActiveBookingOrder(Request $request)
    {
        $request_fields = [
            //            'segment_slug' => 'required',
        ];

        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {
            $string_file = $this->getStringFile($request->merchant_id);
            $booking = new BookingHistoryController;
            $return_data1 = $booking->getActiveBooking($request);

            $order = new OrderController;
            $return_data2 = $order->getActiveOrders($request);

            $return_data = array_merge($return_data1, $return_data2);
            if (empty($return_data)) {
                return $this->failedResponse(trans("$string_file.no_live_data"));
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }


    /*
  *  driver get past booking order
 */
    public function getPastBookingOrder(Request $request)
    {
        $request_fields = [
            'segment_slug' => 'required',
            'segment_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        $message = "";
        try {
            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingHistoryController;
                    $data = $booking->getPastBooking($request);
                    $return_data = $data['data'];
                    $message = $data['message'];
                    break;
                case "order":
                    $order = new OrderController;
                    $data = $order->getPastOrders($request);
                    $return_data = $data['data'];
                    $message = $data['message'];
                    break;
                case "handyman_order":
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse($message, $return_data);
    }

    /*
  *  driver get details of  booking/order
 */
    public function getBookingOrderDetails(Request $request)
    {
        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {
            $request->request->add(['id' => $request->booking_order_id]);
            $string_file = $this->getStringFile($request->merchant_id);
            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingHistoryController;
                    $return_data = $booking->getBookingDetails($request);
                    break;
                case "order":
                    $order = new OrderController;
                    $return_data = $order->getOrderDetails($request);
                    break;
                case "handyman_order":
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse(trans("$string_file.data_found"), $return_data);
    }

    function googleDirectionData(Request $request)
    {
        $request_fields = [
            'from_latitude' => 'required',
            'from_longitude' => 'required',
            'to_latitude' => 'required',
            'to_longitude' => 'required',
            'segment_slug' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $return_data = [];
        try {
            if (isset($request->is_user) && $request->is_user == true) {
                $user = $request->user('api');
            } else {
                $user = $request->user('api-driver');
            }
            $string_file = $this->getStringFile($request->merchant_id);
            $poly_line = $user->Merchant->BookingConfiguration->polyline;
            if ($poly_line == 1) {
                $key = $user->Merchant->BookingConfiguration->google_key;
                $from = $request->from_latitude . ',' . $request->from_longitude;
                $to = $request->to_latitude . ',' . $request->to_longitude;
                $units = ($user->Country['distance_unit'] == 1) ? 'metric' : 'imperial';
                $google = new GoogleController;

                // if($request->segment_slug == "FOOD" || $request->segment_slug == "GROCERY"){
                //     $return_data = [];
                // }
                // else{
                $return_data = $google->GoogleDistanceAndTime($from, $to, $key, $units, true, 'googleDirectionData', $string_file);
                saveApiLog($user->merchant_id, "directions" , "DIRECTION_DATA", "GOOGLE");
                if (!empty($request->booking_id)) {
                    $booking = Booking::select('id', 'ploy_points')->find($request->booking_id);
                    $booking->ploy_points = isset($return_data['poly_point']) ? $return_data['poly_point'] : "";
                    if(empty($booking->total_distance_estimated) || $booking->booking_status == 1003){
                        $booking->total_distance_estimated = $return_data['distance_in_meter'];
                    }
                    $booking->direction_data_updated = json_encode($return_data);
                    $booking->save();
                    $log_data =[
                        'booking_id'=>$request->booking_id,
                        'cron_fn'=>"direction-data",
                        'timestamp' => time(),
                    ];
                    \Log::channel('direction_data')->emergency($log_data);

                }
                elseif(!empty($request->booking_order_id)){
                    $order = Order::find($request->booking_order_id);
                    $order->poly_points =  isset($return_data['poly_point']) ? $return_data['poly_point'] : "";
                    $order->save();

                    $log_data =[
                        'order_id'=>$request->order_id,
                        'cron_fn'=>"direction-data",
                        'timestamp' => time(),
                    ];
                    \Log::channel('direction_data')->emergency($log_data);
                }
                // }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        if(count($return_data) > 0){
            return $this->successResponse(trans("$string_file.data_found"), $return_data);
         }else{
             return $this->successResponse(trans("$string_file.data_found"), (object)$return_data);
         }
    }

    // user rating to driver
    public function rateToDriverByUser(Request $request)
    {
        $string_file = $this->getStringFile($request->merchant_id);
        $request_fields = [
            'segment_slug' => 'required',
            'booking_order_id' => 'required',
            'rating' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {
            $request->request->add(['id' => $request->booking_order_id]);
            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "booking":
                    $booking = new BookingController;
                    $return_data = $booking->bookingRating($request);
                    break;
                case "order":
                    $order = new OrderController;
                    $return_data = $order->orderRating($request);
                    break;
                case "handyman_order":
                    $order = new HandymanOrderController;
                    $return_data = $order->handymanOrderRating($request);
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse(trans("$string_file.rating_thanks"), $return_data);
    }

        public function getGeofenceArea(Request $request){
            $driver = $request->user('api-driver');
            try{
                $areas = $this->getGeofenceAreaList(false,$driver->merchant_id, $driver->country_area_id);
                $areas = $areas->get();
                if(!empty($areas)){
                    $areas = $areas->map(function ($item, $key)
                    {
                        return array(
                            'id' => $item->id,
                            'area_name' => $item->CountryAreaName,
                            'queue_system' => (isset($item->RestrictedArea->queue_system) && $item->RestrictedArea->queue_system == 1) ? true : false,
                            'coordinates' => json_decode($item->AreaCoordinates,true),
                        );
                    });
                }
                return $this->successResponse(trans('admin.geofence_area'),$areas);
            }catch(\Exception $e){
                return $this->failedResponse($e->getMessage());
            }
        }

    public function checkUserWallet($user, $amount)
    {
        try {
            $string_file = $this->getStringFile($user->merchant_id);
            if (!empty($user->id)) {
                if ($user->wallet_balance < $amount) {
                    $message = trans_choice("$string_file.low_wallet_warning", 3, ['AMOUNT' => $amount]);
                    //                    $message = trans("$string_file.low_wallet_warning");
                    throw new \Exception($message);
                }
            }
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /*
      *  driver get earning details of  booking/order
     */
    public function getBookingOrderAccountDetails(Request $request)
    {
        $request_fields = [
            'segment_id' => 'required',
            'date' => 'required_if:is_search,0',
            'from_date' => 'required_if:is_search,1',
            'to_date' => 'required_if:is_search,1',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $return_data = [];
        try {
            $segment = Segment::find($request->segment_id);
            $request->request->add(['segment_slug' => $segment->slag]);
            $segment_slug = $request->segment_slug;
            $string_file = $this->getStringFile($request->merchant_id);
            $sub_group = $this->segmentSubGroup($segment_slug, $segment->segment_group_id);
            $driver_earning = new DriverEarningController();
            switch ($sub_group) {
                case "booking":
                    $return_data = $driver_earning->DriverBookingAccountEarnings($request);
                    break;
                case "order":
                    $return_data = $driver_earning->DriverOrderAccountEarnings($request);
                    break;
                case "handyman_order":
                    $return_data = $driver_earning->DriverHandymanAccountEarnings($request);
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse(trans("$string_file.earning"), $return_data);
    }

    // tip from tracking screen
    public function addTip(Request $request)
    {
        $request_fields = [
            'id' => 'required',
            'tip_amount' => 'required',
            'segment_slug' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $message = "";
        try {
            $segment_slug = $request->segment_slug;
            $sub_group = $this->segmentSubGroup($segment_slug);
            switch ($sub_group) {
                case "order":
                    $order = new OrderController;
                    $message = $order->orderTip($request);
                    break;
                case "handyman_order":
                    $order = new HandymanOrderController;
                    $message = $order->handymanOrderRating($request);
                    break;
            }
            return $this->successResponse($message);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
    }

    public function checkPromoCode($request, $is_handyman = false)
    {
        $user = $request->user('api');
        $string_file = $this->getStringFile($request->merchant_id);
        $user_id = $user->id;
        $promo_code = $request->promo_code;
        $merchant_id = $request->merchant_id;
        $business_segment_id = $request->business_segment_id;
        $area = $request->area;
        $promocode = PromoCode::where([['segment_id', '=', $request->segment_id],['promoCode', '=', $promo_code],['merchant_id', '=', $merchant_id], ['promo_code_status', '=', 1]])->whereNull('deleted')
            ->where(function($q) use ($area){
                if(!empty($area)){
                    $q->where('country_area_id', $area);
                }
            })
            ->where(function ($q) use ($business_segment_id) {
                if(!empty($business_segment_id)){
                    $q->where('business_segment_id', $business_segment_id)->orWhere('business_segment_id', NULL);
                }
            })
            ->first();

        if (empty($promocode)) {
            throw new \Exception(trans("$string_file.invalid_promo_code"));
            // return $this->failedResponse(trans("$string_file.invalid_promo_code"));
        }
        $validity = $promocode->promo_code_validity;
        $start_date = $promocode->start_date;
        $end_date = $promocode->end_date;
        $currentDate = date("Y-m-d");
        if ($validity == 2 && ($currentDate < $start_date || $currentDate > $end_date)) {
            throw new \Exception(trans("$string_file.promo_code_expired_message"));
            // return $this->failedResponse(trans("$string_file.promo_code_expired_message"));
        }

        // first delivery free
        if ($promo_code == "FIRSTDELFREE") {
            $total_orders = Order::select('id', 'promo_code_id', 'user_id')->where([['user_id', '=', $user->id], ['segment_id', '=', $request->segment_id]])
                ->whereIn('order_status', [1, 6, 7, 9, 10, 11])->get();
            if ($total_orders && $total_orders->count() > 0) {
                throw new \Exception(trans("$string_file.first_del_free_promo_code"));
            }
        }

        $promo_code_limit = $promocode->promo_code_limit;
        $total_usage = Order::select('id', 'promo_code_id', 'user_id')->where([['promo_code_id', '=', $promocode->id]])
            ->whereIn('order_status', [1, 6, 7, 9, 10, 11])->get();
        $all_uses = !empty($total_usage) ? $total_usage->count() : 0;
        if (!empty($all_uses)) {
            if ($all_uses >= $promo_code_limit) {
                throw new \Exception(trans("$string_file.user_limit_promo_code_expired"));
            }
            $promo_code_limit_per_user = $promocode->promo_code_limit_per_user;
            $used_by_user = $total_usage->where('user_id', $user_id)->count();
            if ($used_by_user >= $promo_code_limit_per_user) {
                throw new \Exception(trans("$string_file.user_limit_promo_code_expired"));
            }
        }
        $applicable_for = $promocode->applicable_for;
        if ($applicable_for == 2 && $user->created_at < $promocode->updated_at) {
            throw new \Exception(trans("$string_file.promo_code_for_new_user"));
            // return $this->failedResponse(trans("$string_file.promo_code_for_new_user"));
        }
        $order_minimum_amount = $promocode->order_minimum_amount;
        if (!empty($request->order_amount) && $request->order_amount < $order_minimum_amount) {
            $message = trans_choice("$string_file.promo_code_order_value", 3, ['AMOUNT' => $order_minimum_amount]);
            throw new \Exception($message);
        }
        return array('status' => true, 'promo_code' => $promocode);
    }

    public function waitForAccept($booking_id, $driver_id)
    {
        $booking = Booking::find($booking_id);
        if ($booking->booking_status == 1001) {
            $booking_requests = BookingRequestDriver::where([['booking_id', '=', $booking_id], ['inside_function', '=', 1]])->first();
            if (!empty($booking_requests)) {
                sleep(1);
                $this->waitForAccept($booking_id, $driver_id);
            } else {
                $booking_requests = BookingRequestDriver::where([['booking_id', '=', $booking_id], ['request_status', '=', 2]])->get()->count();
                if ($booking_requests == 0) {
                    BookingRequestDriver::where([['booking_id', '=', $booking_id], ['driver_id', '=', $driver_id]])->update(['inside_function' => 1]);
                }
            }
        }
    }

    public function getPaymentGateway(Request $request)
    {
        try {
            $string_file = $this->getStringFile($request->merchant_id);
            $payment_gateways = PaymentOptionsConfiguration::where("merchant_id", $request->merchant_id)->get();
            $payment_gateways = $payment_gateways->map(function ($item) {
                return array(
                    "payment_gateway_provider" => $item->payment_gateway_provider,
                    "data" => array(
                        "api_secret_key" => $item->api_secret_key,
                        "api_public_key" => $item->api_public_key,
                        "auth_token" => $item->auth_token,
                        "tokenization_url" => !empty($item->tokenization_url) ? $item->tokenization_url : "",
                        "payment_redirect_url" => !empty($item->payment_redirect_url) ? $item->payment_redirect_url : "",
                        "callback_url" => !empty($item->callback_url) ? $item->callback_url : "",
                        "gateway_condition" => $item->gateway_condition,
                        "payment_step" => $item->payment_step,
                        "additional_data" => !empty($item->additional_data) ? $item->additional_data : "",
                    )
                );
            });
            return $this->successResponse(trans("$string_file.success"), $payment_gateways);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function getSubAdmin(Request $request)
    {
        $request_fields = [
            'public_key' => 'required',
            'secret_key' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $merchant = \App\Models\Merchant::where([['merchantPublicKey', '=', $request->public_key], ['merchantSecretKey', '=', $request->secret_key]])->first();
            if (empty($merchant)) {
                return $this->failedResponse('Account Not Found');
            }
            $string_file = $this->getStringFile($merchant->id);
            $get_subAdmin = \App\Models\Merchant::where([['parent_id', '=', $merchant->id]])->get();
            $sub_admin = $get_subAdmin->map(function ($item) {
                return array(
                    "name" => $item->merchantFirstName . ' ' . $item->merchantLastName,
                    "phone" => $item->merchantPhone,
                    "email" => $item->email,
                    "address" => $item->merchantAddress,
                );
            });
            return $this->successResponse(trans("$string_file.success"), $sub_admin);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function getUsers(Request $request)
    {
        $request_fields = [
            'public_key' => 'required',
            'secret_key' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $merchant = \App\Models\Merchant::where([['merchantPublicKey', '=', $request->public_key], ['merchantSecretKey', '=', $request->secret_key]])->first();
            if (empty($merchant)) {
                return $this->failedResponse('Account Not Found');
            }
            $string_file = $this->getStringFile($merchant->id);
            $get_users = \App\Models\User::where([['merchant_id', '=', $merchant->id], ['user_delete', '=', NULL]])->get();
            $users = $get_users->map(function ($item) {
                $referral_discount = ReferralDiscount::where([['merchant_id', '=', $item->merchant_id], ['receiver_id', '=', $item->id], ['receiver_type', '=', 'USER']])->first();
                $sender = '';
                $sponsor_code = '';
                $sponsor_name = '';
                if (!empty($referral_discount)) {
                    $sender = $referral_discount->sender_type == 'USER' ? User::find($referral_discount->sender_id) : Driver::find($referral_discount->sender_id);
                    $sponsor_code = !empty($sender) ? ($referral_discount->sender_type == 'USER' ? $sender->ReferralCode : $sender->driver_referralcode) : '';
                    $sponsor_name = !empty($sender) ? $sender->first_name . ' ' . $sender->last_name : '';
                }
                return array(
                    "id" => $item->user_merchant_id,
                    "name" => $item->first_name . ' ' . $item->last_name,
                    "phone" => $item->UserPhone,
                    "email" => $item->email,
                    "password" => $item->password,
                    "total_trips" => !empty($item->total_trips) ? $item->total_trips : 0,
                    "wallet_balance" => !empty($item->wallet_balance) ? $item->wallet_balance : '0',
                    "ReferralCode" => $item->ReferralCode,
                    "rating" => !empty($item->rating) ? $item->rating : '0',
                    "sponsor_code" => $sponsor_code,
                    "sponsor_name" => $sponsor_name
                );
            });
            return $this->successResponse(trans("$string_file.success"), $users);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function getDriver(Request $request)
    {
        $request_fields = [
            'public_key' => 'required',
            'secret_key' => 'required',
            'group' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $merchant = \App\Models\Merchant::where([['merchantPublicKey', '=', $request->public_key], ['merchantSecretKey', '=', $request->secret_key]])->first();
            if (empty($merchant)) {
                return $this->failedResponse('Account Not Found');
            }
            $string_file = $this->getStringFile($merchant->id);
            $group = $request->group;
            if ($group == 1) {
                $get_drivers = \App\Models\Driver::with('Booking', 'Order')->where([['merchant_id', '=', $merchant->id], ['driver_delete', '=', NULL], ['segment_group_id', '=', $request->group]])->get();
            } else {
                $get_drivers = \App\Models\Driver::with('HandymanOrder')->where([['merchant_id', '=', $merchant->id], ['driver_delete', '=', NULL], ['segment_group_id', '=', $request->group]])->get();
            }

            $drivers = $get_drivers->map(function ($item) use ($group) {
                $booking_arr = [];
                if ($group == 1) {
                    $bookings = $item->Booking;
                    if (!empty($bookings)) {
                        foreach ($bookings as $booking) {
                            $booking_arr[] = [
                                "rider_id" => $booking->driver_id,
                                "rider_referCode" => "",
                                "transaction_id" => $booking->id,
                                "customer_name" => $booking->User->first_name . ' ' . $booking->User->last_name,
                                "booking_date_time" => convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone, $booking->merchant_id, null, 1),
                                "booking_amount" => $booking->final_amount_paid,
                                "tax" => '',
                                "total_booking_amount" => $booking->final_amount_paid,
                                "complete_date_time" => $booking->booking_status == 1005 ? convertTimeToUSERzone($booking->updated_at, $booking->CountryArea->timezone, $booking->merchant_id, null, 1) : '',
                                "cancel_date_time" => !in_array($booking->booking_status, [1005, 1004, 1002, 1003, 1001]) ? convertTimeToUSERzone($booking->updated_at, $booking->CountryArea->timezone, $booking->merchant_id, null, 1) : '',
                                "status" => $booking->booking_status == 1005 ? "Completed" : "Pending",
                                "ride_from" => "Delivery",
                            ];
                        }
                    }
                    $orders = $item->Order;
                    if (!empty($orders)) {
                        foreach ($orders as $booking) {
                            $booking_arr[] = [
                                "rider_id" => $booking->driver_id,
                                "rider_referCode" => "",
                                "transaction_id" => $booking->id,
                                "customer_name" => $booking->User->first_name . ' ' . $booking->User->last_name,
                                "booking_date_time" => convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone, $booking->merchant_id, null, 1),
                                "booking_amount" => $booking->final_amount_paid,
                                "tax" => '',
                                "total_booking_amount" => $booking->final_amount_paid,
                                "complete_date_time" => $booking->order_status == 11 ? convertTimeToUSERzone($booking->updated_at, $booking->CountryArea->timezone, $booking->merchant_id, null, 1) : '',
                                "cancel_date_time" => in_array($booking->order_status, [2, 3, 12, 5, 8]) ? convertTimeToUSERzone($booking->updated_at, $booking->CountryArea->timezone, $booking->merchant_id, null, 1) : '',
                                "status" => $booking->is_order_completed == 1 ? "Completed" : "Pending",
                                "ride_from" => "Grocery",
                            ];
                        }
                    }
                } else {
                    $bookings = $item->HandymanOrder;
                    foreach ($bookings as $booking) {
                        $booking_arr[] = [
                            "rider_id" => $booking->driver_id,
                            "rider_referCode" => "",
                            "transaction_id" => $booking->id,
                            "customer_name" => $booking->User->first_name . ' ' . $booking->User->last_name,
                            "booking_date_time" => convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone, $booking->merchant_id, null, 1),
                            "booking_amount" => $booking->final_amount_paid,
                            "tax" => '',
                            "total_booking_amount" => $booking->final_amount_paid,
                            "complete_date_time" => $booking->order_status == 7 ? convertTimeToUSERzone($booking->updated_at, $booking->CountryArea->timezone, $booking->merchant_id, null, 1) : '',
                            "cancel_date_time" => $booking->order_status == 3 ? convertTimeToUSERzone($booking->updated_at, $booking->CountryArea->timezone, $booking->merchant_id, null, 1) : '',
                            "status" => $booking->is_order_completed == 1 ? "Completed" : "Pending",
                            "ride_from" => "Handyman",
                        ];
                    }
                }

                return array(
                    "id" => $item->id,
                    "name" => $item->first_name . ' ' . $item->last_name,
                    "phone" => $item->phoneNumber,
                    "email" => $item->email,
                    "total_trips" => !empty($item->total_trips) ? $item->total_trips : 0,
                    "wallet_money" => !empty($item->wallet_money) ? $item->wallet_money : '0',
                    "total_earnings" => !empty($item->total_earnings) ? $item->total_earnings : '0',
                    "ReferralCode" => $item->driver_referralcode,
                    "rating" => !empty($item->rating) ? $item->rating : '0',
                    "last_location_update_time" => !empty($item->last_location_update_time) ? $item->last_location_update_time : NULL,
                    "booking_data" => $booking_arr
                );
            });
            return $this->successResponse(trans("$string_file.success"), $drivers);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function storeList(Request $request)
    {
        $request_fields = [
            'public_key' => 'required',
            'secret_key' => 'required',
            // 'group' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try {
            $merchant = \App\Models\Merchant::where([['merchantPublicKey', '=', $request->public_key], ['merchantSecretKey', '=', $request->secret_key]])->first();
            if (empty($merchant)) {
                return $this->failedResponse('Account Not Found');
            }
            $string_file = $this->getStringFile($merchant->id);

            $business_segment = BusinessSegment::whereHas('Segment', function ($q) {
                $q->where('slag', 'GROCERY');
            })
                ->where([['merchant_id', '=', $merchant->id]])
                ->orderBy('created_at', 'DESC')->get();
            $stores = $business_segment->map(function ($item) {
                return array(
                    'id' => $item->id,
                    'full_name' => $item->full_name,
                    'contact_number' => $item->phone_number,
                    'email' => $item->email,
                    'address' => $item->address,
                );
            });
            return $this->successResponse(trans("$string_file.success"), $stores);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function getStoreBookings(Request $request)
    {
        $request_fields = [
            'public_key' => 'required',
            'secret_key' => 'required',
            'store_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $merchant = \App\Models\Merchant::where([['merchantPublicKey', '=', $request->public_key], ['merchantSecretKey', '=', $request->secret_key]])->first();
            if (empty($merchant)) {
                return $this->failedResponse('Account Not Found');
            }
            $string_file = $this->getStringFile($merchant->id);
            $segment = Segment::where('slag', 'GROCERY')->first();
            $request->request->add(['merchant_id' => $merchant->id, 'segment_id' => $segment->id, 'business_segment_id' => $request->store_id]);
            $order = new Order;
            $all_orders = $order->getOrders($request, false);
            $all_orders = $all_orders->map(function ($item) use ($string_file) {
                $currency = $item->CountryArea->Country->isoCode;
                $tax_amount = !empty($item->tax) ? $item->tax : 0;
                return array(
                    'store_id' => $item->business_segment_id,
                    'transaction_id' => $item->merchant_order_id,
                    'customer_name' => $item->User->UserName,
                    'booking_date_time' => convertTimeToUSERzone($item->created_at, $item->CountryArea->timezone, null, $item->Merchant),
                    'subtotal' => $currency . ' ' . $item->cart_amount,
                    'tax' => $currency . ' ' . $item->$tax_amount,
                    'delivery_charge' => $currency . ' ' . $item->delivery_amount,
                    'total_amount' => $currency . ' ' . $item->final_amount_paid,
                    'complete_date_time' => $item->is_order_completed == 1 ? convertTimeToUSERzone($item->updated_at, $item->CountryArea->timezone, null, $item->Merchant) : '',
                    'cancel_date_time' => $item->is_order_completed != 1 ? convertTimeToUSERzone($item->updated_at, $item->CountryArea->timezone, null, $item->Merchant) : '',
                    'status' => $item->order_status == 11 ? trans("$string_file.complete") : trans("$string_file.pending") . ' / ' . trans("$string_file.cancel"),
                );
            });
            return $this->successResponse(trans("$string_file.success"), $all_orders);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    // Get Strings for App
    public function getAppStrings(Request $request)
    {
        $request_fields = [
            'application' => 'required',
            'string_group_name' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $all_strings = ApplicationString::where([['application', '=', $request->application], ['string_group_name', '=', $request->string_group_name]])
                ->with(['ApplicationStringLanguage' => function ($q) {
                    $q->where('locale', 'en');
                }])
                ->whereHas('ApplicationStringLanguage', function ($q) {
                    $q->where('locale', 'en');
                })->get();
            $all_strings = $all_strings->map(function ($string) {
                return [
                    'key' => $string['string_key'],
                    'value' => $string->ApplicationStringLanguage[0]['string_value'],
                ];
            });
            return $this->successResponse("success", $all_strings);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    // Set Strings for App
    public function setAppStrings(Request $request)
    {
        $request_fields = [
            'application' => 'required',
            'string_group_name' => 'required',
            'key_value' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $arr_key_value = json_decode($request->key_value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->failedResponse(json_last_error_msg());
            }

            foreach ($arr_key_value as $key_value) {
                if (!empty($key_value['string_key']) && !empty($key_value['string_value'])) {
                    $app_string = ApplicationString::updateOrCreate(
                        [
                            'string_group_name' => $request->string_group_name,
                            'application' => $request->application,
                            'string_key' => $key_value['string_key']
                        ],
                        [
                            'platform' => 'android',
                            'string_group_name' => $request->string_group_name,
                            'application' => $request->application,
                            'string_key' => $key_value['string_key']
                        ]
                    );
                    $app_string_id = $app_string->id;
                    ApplicationStringLanguage::updateOrCreate(
                        [
                            'application_string_id' => $app_string_id,
                            'locale' => 'en'
                        ],
                        [
                            'string_value' => $key_value['string_value'],
                            'application_string_id' => $app_string_id,
                            'locale' => 'en'
                        ]
                    );
                } else {
                    throw new \Exception("some keys and values are blank");
                }
            }
            return $this->successResponse("success", []);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function getPaymentOptions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            'payment_option_for' => 'nullable|integer|IN:1,2', // 1 for Debit, 2 for Credit
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $user = ($request->for == "USER") ? request()->user('api') : request()->user('api-driver');
            $string_file = $this->getStringFile($user->merchant_id);

            $payment_options = $user->Merchant->PaymentOption;

            $having_payment_methods = $user->Merchant->PaymentMethod->whereIn("id", [2, 4]);
            if (count($having_payment_methods) >= 1) {
                $country = Country::find($user->country_id);
                if (isset($country->payment_option_ids) && !empty($country->payment_option_ids)) {
                    $payment_options = $this->PaymentOption->whereIn("id", explode(",", $country->payment_option_ids))->values();
                }
            }
            $payment_option_for = isset($request->payment_option_for) && !empty($request->payment_option_for) ? $request->payment_option_for : 1;
            $arr_payment_option = \App\Http\Controllers\Helper\CommonController::filteredPaymentOptions($payment_options, $user->merchant_id, $payment_option_for);
            $arr_payment_option = $arr_payment_option->values();

            return $this->successResponse(trans("$string_file.success"), $arr_payment_option);
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    // get merchant details
    public function getSecretKeys(Request $request)
    {
        $request_fields = [
            'access_pin' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $merchant = MerchantModel::select('id', 'email', 'merchantPublicKey', 'merchantSecretKey', 'BusinessName', 'BusinessLogo')->where([['access_pin', '=', $request->access_pin], ['merchantStatus', '=', 1]])->first();

            if (!$merchant) {
                return $this->failedResponse('Invalid pin, please try again');
            }
            $merchant->BusinessLogo = get_image($merchant->BusinessLogo, 'business_logo', $merchant->id, true);
            $merchant->ios_show_screen_user = true;
            // $request->access_pin == 67589432 && $request->calling_from == 'USER' ? false : true;
            $merchant->ios_show_screen_driver = true;
            //$request->access_pin == 67589432  && $request->calling_from == 'DRIVER' ?  false : true;
            $merchant->primary_color_driver = $merchant->ApplicationTheme && $merchant->ApplicationTheme->primary_color_driver ? $merchant->ApplicationTheme->primary_color_driver : "#E33A45";
            $merchant->driver_app_logo = $merchant->ApplicationTheme && $merchant->ApplicationTheme->driver_app_logo ? get_image($merchant->ApplicationTheme->driver_app_logo, 'driver_app_theme', $merchant->id) : url('/basic-images/driver_preview.png');
            $merchant->primary_color_user = $merchant->ApplicationTheme && $merchant->ApplicationTheme->primary_color_user ? $merchant->ApplicationTheme->primary_color_user : "#E33A45";
            $merchant->user_app_logo = $merchant->ApplicationTheme && $merchant->ApplicationTheme->user_app_logo ? get_image($merchant->ApplicationTheme->user_app_logo, 'user_app_theme', $merchant->id) : url('/basic-images/user_preview.png');
            $merchant->primary_color_store = $merchant->ApplicationTheme && $merchant->ApplicationTheme->primary_color_store ? $merchant->ApplicationTheme->primary_color_store : "#E33A45";
            $merchant->store_app_logo = $merchant->ApplicationTheme && $merchant->ApplicationTheme->store_app_logo ? get_image($merchant->ApplicationTheme->store_app_logo, 'business_logo', $merchant->id) : url('/basic-images/user_preview.png');
            $merchant->login_signup_ui = isset($merchant->ApplicationConfiguration->login_signup_ui) ? (string)$merchant->ApplicationConfiguration->login_signup_ui : "1";
            unset($merchant->ApplicationTheme);
            unset($merchant->ApplicationConfiguration);
            return $this->successResponse('Success', $merchant);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function getEncryptionKey(Request $request)
    {
        try {
            $timestamp = time();
            $encryption_key = \Illuminate\Support\Facades\Crypt::encrypt($timestamp);
            $data = array("encryption_key" => $encryption_key);
            return $this->successResponse('Success', $data);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function getDecryptionKey(Request $request)
    {
        try {
            validate_encryption_key();
            return $this->successResponse('Success');
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function getWebsiteString(Request $request)
    {
        try {
            $merchant = \App\Models\Merchant::find($request->merchant_id);
            $locale = \Illuminate\Support\Facades\App::getLocale();
            $merchant_website_string = MerchantWebsiteString::where(["merchant_id" => $merchant->id, "locale" => $locale])->first();
            $data = [];
            if (!empty($merchant_website_string)) {
                $alias = $merchant->alias_name . "/string_files/";
                $file_name = $alias . $merchant_website_string->file_name;
                setS3Config($merchant);
                $data = \Illuminate\Support\Facades\Storage::disk('s3')->get($file_name);
//                return \Illuminate\Support\Facades\Storage::disk('s3')->get($file_name);
            }
            $response = new Response($data, 200);
            $response->header('Content-Type', 'application/json');
            return $response;
        } catch (\Exception $exception) {
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function testMerchantNotification(){
        $title = "Ride Request";
        $message = "Ride Request Message";
        $data = [];
        $booking = Booking::find(14712);
        //$message = ($event->booking->booking_type == 1) ? trans('admin.new_normal_ride_now_booked') : trans('admin.new_normal_ride_later_booked');
        event(new WebPushNotificationEvent(354, $data, 1, 1, $booking, "all_in_one", "RIDE")); //type defines situation,like 1: New Ride Booking
    }

    public function getTimeSlotAvailability($driver){
        $action = false;
        if ($driver->Merchant->ApplicationConfiguration->time_slot_unavail_popup == 1){
            date_default_timezone_set($driver->CountryArea->timezone);
            $current_day = date('w');
            $current_time = date('H:i:s');
            $driver_segment_ids = array_column($driver->Segment->toArray(),'id');
            $service_slot = ServiceTimeSlot::where([['merchant_id','=',$driver->merchant_id],['country_area_id','=',$driver->country_area_id],['day','=',$current_day]])->whereIn('segment_id',$driver_segment_ids)->get();
            $service_slot_ids = array_column($service_slot->toArray(),'id');
            $time_slot = $driver->ServiceTimeSlotDetail->whereIn('service_time_slot_id',$service_slot_ids);
            foreach($time_slot as $slot){
                if(!($current_time >= $slot->from_time && $current_time <= $slot->to_time)){
                    $action = true;
                }
            }
        }
        return $action;
    }
    
    
    public function inAppCalling(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'for' => 'required|IN:USER,DRIVER',
            // 'call_to' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try {
            $user = ($request->for == "USER") ? request()->user('api') : request()->user('api-driver');
            if (!isset($user)) {
                return $this->failedResponse($request->for." Not Found !");
            }
            $configs = $user->Merchant->Configuration;
            $string_file = $this->getStringFile($user->merchant_id);
            $in_app_call_status = $configs->in_app_call;
            $in_app_call_config = InAppCallingConfigurations::where('merchant_id', $user->merchant_id)->first();

            if ($in_app_call_status == 0 || empty($in_app_call_config)) {
                return $this->failedResponse(trans("$string_file.in_app_call_disabled"));
            }
            $response = null;
            
            switch($in_app_call_config->provider_slug){
                case "TWILIO":
                    
                    if(empty($in_app_call_config->api_key) || empty($in_app_call_config->auth_token) || empty($in_app_call_config->calling_number)){
                        return $this->failedResponse(trans("$string_file.in_app_call_disabled"));
                    }
                    
                    $curl = curl_init();
                    $creds = base64_encode($in_app_call_config->api_key . ":" . $in_app_call_config->auth_token);
                    $data = [
                        "Url" => route('in-app-call-redirect', ['mobile' => $request->call_to]),
                        "To" => ($request->for == "USER") ? $user->UserPhone : $user->phoneNumber,
                        "From" => $in_app_call_config->calling_number
                    ];
                    
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://api.twilio.com/2010-04-01/Accounts/$in_app_call_config->api_key/Calls.json",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => http_build_query($data),
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/x-www-form-urlencoded',
                            'Authorization: Basic ' . $creds
                        ),
                    ));

                    $response = curl_exec($curl);
                    curl_close($curl);
                    break;
                    
                case "AFRICATALKING":
                    if(empty($in_app_call_config->api_key) || empty($in_app_call_config->auth_token) || empty($in_app_call_config->calling_number)){
                        return $this->failedResponse(trans("$string_file.in_app_call_disabled"));
                    }
                    $caller =  ($request->for == "USER") ? $user->UserPhone : $user->phoneNumber;
                    $data = [
                        'username' => $in_app_call_config->auth_token,
                        'to' => $request->call_to.','.$caller,
                        'from' => $in_app_call_config->calling_number
                    ];
                    
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                      CURLOPT_URL => 'https://voice.africastalking.com/call',
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => '',
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 0,
                      CURLOPT_FOLLOWLOCATION => true,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => 'POST',
                      CURLOPT_POSTFIELDS =>  http_build_query($data),
                      CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Content-Type: application/x-www-form-urlencoded',
                        'apiKey: '.$in_app_call_config->api_key
                      ),
                    ));
                    
                    $response = curl_exec($curl);
                    curl_close($curl);
                    break;
                case "AGORA":
                    $call_status = $request->call_status;
                    $booking = Booking::find($request->booking_id);
                    $channel_id = $request->channel_id; 
                    
                    $call_start_title =  trans("$string_file.incoming")." ".trans("$string_file.call");
                    $call_start_message = trans("$string_file.you")." ".trans("$string_file.incoming")." ".trans("$string_file.call")." ".trans("$string_file.from")." ".$booking->Driver->first_name;
                    $call_end_title =  trans("$string_file.call")." ".trans("$string_file.ended");
                    $call_end_message = trans("$string_file.you")." ".trans("$string_file.call")." ".trans("$string_file.ended");
                        
                    $notification_data['notification_type'] = "INCOMING_CALL";
                    $notification_data['segment_type'] = $booking->Segment->slag;
                    $notification_data['segment_group_id'] = $booking->Segment->segment_group_id;
                    $notification_data['segment_sub_group'] = $booking->Segment->sub_group_for_app;
                    $notification_data['segment_data'] = [
                        'booking_id' => $booking->id,
                        'channel_id' => $request->channel_id,
                    ];

                    if($call_status == "INITIATE" && $request->for != "USER"){
                        $notification_data['notification_type'] = "INCOMING_CALL";
                        $arr_param = ['user_id' => $booking->user_id, 'data' => $notification_data, 'message' => $call_start_message, 'merchant_id' => $booking->merchant_id, 'title' => $call_start_title, 'large_icon' => ""];
                        Onesignal::UserPushMessage($arr_param);
                    }
                    else if($call_status == "HANG_UP" && $request->for != "USER"){
                        $notification_data['notification_type'] = "CALL_ENDED";
                        $arr_param = ['user_id' => $booking->user_id, 'data' => $notification_data, 'message' => $call_end_message, 'merchant_id' => $booking->merchant_id, 'title' => $call_end_title, 'large_icon' => ""];
                        Onesignal::UserPushMessage($arr_param);
                    }
                    else if($call_status == "INITIATE" && $request->for == "USER"){
                        $notification_data['notification_type'] = "INCOMING_CALL";
                        $arr_param = ['driver_id' => $booking->driver_id, 'data' => $notification_data, 'message' => $call_start_message, 'merchant_id' => $booking->merchant_id, 'title' => $call_start_title, 'large_icon' => ""];
                        Onesignal::DriverPushMessage($arr_param);
                    }
                    else if($call_status == "HANG_UP" && $request->for == "USER"){
                        $notification_data['notification_type'] = "CALL_ENDED";
                        $arr_param = ['driver_id' => $booking->driver_id, 'data' => $notification_data, 'message' => $call_end_message, 'merchant_id' => $booking->merchant_id, 'title' => $call_end_title, 'large_icon' => ""];
                        Onesignal::DriverPushMessage($arr_param);
                    }


                    $response = json_encode([
                        "notification_sent"=> true,
                    ]);
                    break;
            }

            $res = json_decode($response);
            return $this->successResponse(trans("$string_file.call_initiated"), $res);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }


//    @ayush (Auto complete search based on map option on priority)
    public function searchPlaces(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keyword'   => 'required',
            'language'  => 'required|min:2|max:2',
            'location'  => 'required',
            'for'       => 'required|in:USER,DRIVER'
        ]);

        if ($validator->fails()) {
            return $this->failedResponse($validator->messages()->first());
        }

        try {
            $user = ($request->for == "USER") ? $request->user('api') : $request->user('api-driver');
            $country_code = $user->Country->country_code;
            $keyword = urlencode($request->keyword);
            $language = $request->language ?? 'en';
            $radius = 500;
            $location = $request->location;
            $country_id = $user->country_id;
            $merchant_id = $user->merchant_id;

            $string_file = $this->getStringFile($merchant_id);
            $config = $user->Merchant->ApplicationConfiguration;
            $booking_config = $user->Merchant->BookingConfiguration;

            $cachedPlaces = SearchablePlace::where('keyword', 'LIKE', "{$keyword}%")
                ->where("merchant_id", $merchant_id)
                ->where("country_id", $country_id)
                ->take(5)
                ->get();

            $responses = [];
            if ($cachedPlaces->isNotEmpty()) {
                $responses = $cachedPlaces->map(function ($place) {
                    $response['keyword'] = $place->keyword;
                    $response['google_response'] = json_decode($place->response);
                    return $response;
                });
                return $this->successResponse(trans("$string_file.success"), $responses);
            }

            // Maps According to priority
            $common_helper =  new \App\Http\Controllers\Helper\CommonController();

            if(!empty($booking_config->map_box_key) && $config->map_box_enable == 1){
                $responses = $common_helper::searchViaMapbox($keyword, $booking_config, $user, $country_code, $location);
                saveApiLog($merchant_id, 'search/searchbox', "SEARCH_PLACES", "MAP_BOX");
            }
            

            if (empty($responses) && !empty($booking_config->here_map_key) && $config->here_map_enable == 1) {
                $responses = $common_helper::searchViaHereMaps($keyword, $booking_config);
                saveApiLog($merchant_id, 'autocomplete', "SEARCH_PLACES", "HERE_MAP");
            }

            if (empty($responses) && !empty($booking_config->google_key)) {
                $responses = $common_helper::searchViaGoogle($keyword, $language, $radius, $location, $booking_config, $user, $country_code);
                saveApiLog($merchant_id, "autocomplete" , "DIRECTION_DATA", "GOOGLE");
            }

            if (empty($responses)) {
                return $this->failedResponse("No results found from any providers.");
            }

            if($user->Merchant->demo != 1)
                $common_helper::storeSearchablePlace($merchant_id, $keyword, $country_id, $responses);

            return $this->successResponse(trans("$string_file.success"), [['keyword' => $keyword, 'google_response' => $responses]]);
        } catch (\Exception $e) {

            \Log::channel('places_api')->emergency([
                "exception" =>$e->getMessage(),
                "time" => time(),
                "request_body"=>  $request->all(),
            ]);
            return $this->failedResponse($e->getMessage());
        }
    }

    public function DriverFaq(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $validator = Validator::make($request->all(), [
            'slug' => [
                'required',
                Rule::exists('cms_pages', 'slug')->where(function ($query) use ($merchant_id) {
                    $query->where(['merchant_id' => $merchant_id, 'application' => 2]);
                }),
            ],
            'country_id' => 'required_if:slug,terms_and_Conditions',
        ], [
            'exists' => trans("$string_file.data_not_found"),
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            // return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        try {
            $message = '';
            if ($request->slug == 'terms_and_Conditions') {
                $message = trans("$string_file.terms_conditions");
                $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 2], ['slug', '=', $request->slug], ['country_id', '=', $request->country_id]])->first();
            } else {
                $message = trans("$string_file.cms_pages");
                $page = CmsPage::where([['merchant_id', '=', $merchant_id], ['application', '=', 2], ['slug', '=', $request->slug]])->first();
            }
            if (empty($page)) {
                return $this->failedResponse($message);
                //                    response()->json(['result' => "0", 'message' => $message, 'data' => []]);
            }
            $page_data = array(
                'title' => $page->CmsPageTitle,
                'description' => $page->CmsPageDescription,
                'content_type' => $page->content_type
            );
            // $page->title = $page->CmsPageTitle;
            // $page->description = $page->CmsPageDescription;
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse($message, $page_data);
        //        return response()->json(['result' => "1", 'message' => $message, 'data' => $page]);
    }

    public function UserFaq(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $customMessages = [
            'exists' => trans("$string_file.data_not_found"),
        ];
        $faqTypes = FaqType::where('merchant_id',$merchant_id)->where('status',1)->get();
        $default_faq_type[] = [
                "id" => 0,
                "name"=> "All",
            ];
        $faqTypes = $faqTypes->map(function ($item) {
                return array(
                    "id" => $item->id,
                    "name"=> $item->getNameAttribute(),
                );
            });
        $faqTypes = array_merge($default_faq_type,$faqTypes->toArray());
        $query = Faq::where([['merchant_id','=',$merchant_id],['status','=',1],['application','=',1]]);
        if(!empty($request->faq_type) && $request->faq_type!=0){
            $query->where('faq_type_id',$request->faq_type);
        }
        $faqs = $query->get();
        
        $faqs = $faqs->map(function ($item) {
                return array(
                    "id" => $item->id,
                    "question"=> $item->getNameAttribute(),
                    "answer"=> $item->getDescriptionAttribute(),
                );
            });
        $data = [
                'faq_type' => $faqTypes,
                'faqs' => $faqs
            ];
        $faqs = $faqs->toArray();
        $next_page_url = !empty($faqs['next_page_url']) ? $faqs['next_page_url'] : "";
        $current_page = !empty($faqs['current_page']) ? $faqs['current_page'] : 0;

        $response =[
            'current_page'=>$current_page,
            'next_page_url'=>$next_page_url,
            'response_data'=>$data
        ];
            
        return $this->successResponse(trans("$string_file.success"), $response);
    }

    public function walletRechageRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'for'=> 'required|in:USER,DRIVER'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user('api-driver');
            if (!isset($user)) {
                return $this->failedResponse($request->for." Not Found !");
            }
            $merchant_id = $user->Merchant->id;
            $string_file = $this->getStringFile($user->merchant_id);
            $existing_request = WalletRechargeRequest::where("merchant_id", $merchant_id)
                ->when($request->for === "USER", function ($query) use ($user) {
                    return $query->where("user_id", $user->id);
                })
                ->when($request->for === "DRIVER", function ($query) use ($user) {
                    return $query->where("driver_id", $user->id);
                })
                ->where("request_status", 0)
                ->first();
            if(!empty($existing_request)){
                $existing_request->amount_requested = $request->amount;
                $existing_request->comment = $request->comment;
                $existing_request->save();
            }
            else{
                WalletRechargeRequest::create([
                    "merchant_id"=>$merchant_id,
                    "driver_id"=>($request->for != "USER")? $user->id : Null,
                    "user_id"=>($request->for == "USER")? $user->id : Null,
                    "amount_requested"=> $request->amount,
                    "comment" => $request->comment,
                    "request_status"=>0,
                ]);
            }

        }
        catch (\Exception $e){
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"), []);
    }


    public function sos(Request $request){
        $validator = Validator::make($request->all(), [
            'latitude' => 'required',
            'longitude' => 'required',
            'for'=> 'required|in:USER,DRIVER',
            'booking_id' => 'required',
            'application'=> 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $user = ($request->for == "USER") ? request()->user('api') : request()->user('api-driver');
            if (!isset($user)) {
                return $this->failedResponse($request->for." Not Found !");
            }
            $merchant_id = $user->Merchant->id;
            $string_file = $this->getStringFile($user->merchant_id);
            $driver_count = isset($user->Merchant->BookingConfigurations->sos_driver_count)? $user->Merchant->BookingConfigurations->sos_driver_count: 5;
            $radius = isset($user->Merchant->BookingConfigurations->sos_driver_radius)? $user->Merchant->BookingConfigurations->sos_driver_radius: 50;
            $query = AllSosRequest::with("Booking")
                ->where("merchant_id", $merchant_id);
            if($request->for == "USER"){
                $query->where("user_id", $user->id);
            }
            else{
                $query->where("user_id", $user->id);
            }
            $query->where('status', 1)
                ->orderby("id", "desc")
                ->first();
            $existing_request = $query->first();
            if(empty($existing_request) && $request->action == "COMPLETED"){
                return $this->failedResponse(trans("$string_file.not_found"));
            }
            if(empty($existing_request)){
                $sos_request = new AllSosRequest();
                $sos_request->merchant_id = $merchant_id;
                $sos_request->user_id = ($request->for == "USER") ? $user->id: null;
                $sos_request->driver_id = ($request->for == "DRIVER") ? $user->id : null;
                $sos_request->sos_latitude = $request->latitude;
                $sos_request->sos_longitude = $request->longitude;
                $sos_request->application = $request->application;
                $sos_request->booking_id  = $request->booking_id;
                $sos_request->created_at = date('Y-m-d H:i:s');
                $sos_request->updated_at = date('Y-m-d H:i:s');
                $sos_request->save();
            }
            $booking = Booking::with('CountryArea', 'VehicleType', 'Driver', 'BookingDetail')->find($request->booking_id);
            $drivers = $this->getSosRequestsDrivers($merchant_id, $request->latitude, $request->longitude, $driver_count, $radius, $booking->driver_id);
            $data = [];
            $data['name']= $user->first_name." ".$user->last_name;
            $data['contact']= ($request->for == "USER")? $user->UserPhone : $user->phoneNumber;
            $data['vehicleTypeName']= $booking->VehicleType->LanguageVehicleTypeSingle == "" ? $booking->VehicleType->LanguageVehicleTypeAny->vehicleTypeName : $booking->VehicleType->LanguageVehicleTypeSingle->vehicleTypeName;
            $data['vehicleModelName']= $booking->DriverVehicle? $booking->DriverVehicle->VehicleModel->VehicleModelName: "";
            $data['vehicleColor'] = $booking->DriverVehicle ? $booking->DriverVehicle->vehicle_color : "";
            $data['vehicle_number']= $booking->DriverVehicle ? $booking->DriverVehicle->vehicle_number : "";
            $data['for']= $request->for;
            $data['latitude']= $request->latitude;
            $data['longitude']= $request->longitude;
            $notification_data = array(
                'notification_type' => "SOS",
                'notification_gen_time' => time(),
                'segment_data'=>$data,
                'segment_type'=>""
            );
            $message = trans("$string_file.sos");
            $title = trans("$string_file.sos_request");
            $arr_params = [
                'driver_id' =>$drivers,
                'data' => $notification_data,
                'message' => $message,
                'merchant_id' => $merchant_id,
                'title' => $title,
                'large_icon' => "",
            ];
            Onesignal::DriverPushMessage($arr_params);
            $player_id = array_pluck($user->Merchant->ActiveWebOneSignals->where('status', 1), 'player_id');
            $message = trans("$string_file.sos");
            $title = trans("$string_file.sos_request");
            $onesignal_redirect_url = route('merchant.sos.requests');
            Onesignal::MerchantWebPushMessage($player_id, [], $message, $title, $user->merchant_id, $onesignal_redirect_url);

        }
        catch (\Exception $e){
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("common.sos_message_for_customers"),[]);
    }
    
    // send new booking notification to driver
    public function BookingNotificationApi(Request $request)
    {
        $request_fields = [
            //            'password' => 'required',
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
        $driver = $request->user('api-driver');
        $merchant_id = $driver->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $bookingId = $request->booking_id;
        $details = Booking::find($bookingId);
        if(isset($details) && !empty($details) && $details->booking_status == 1002){
                return $this->failedResponse("Ride Already Accepted By Other Driver");
        }
        $merchant_helper = new Merchant();
        if ($details->Segment->slag == 'DELIVERY' && !empty($details->BookingDeliveryDetails)) {
            $additional_notes = !empty($details->BookingDeliveryDetails->additional_notes) ? $details->BookingDeliveryDetails->additional_notes : "";
        } else {
            $additional_notes = !empty($details->additional_notes) ? $details->additional_notes : "";
        }
        $additional_information = !empty($details->additional_information) ? $details->additional_information : "";
        $vehicle = $details->VehicleType;
        $vehicleTypeName = $vehicle->VehicleTypeName;
        $driver_request_timeout = 0;
        $mulple_stop = false;
        $dropCount = "";
        $stops = [];
        $drop_locations = [];
        $drop_location =
            [
                'address'=>$details->drop_location,
                'lat'=>(string)$details->drop_latitude,
                'lng'=>(string)$details->drop_longitude,
            ];
        array_push($drop_locations,$drop_location);

        if ($details->booking_status == 1001) {
            $config = BookingConfiguration::select('driver_request_timeout')->where([['merchant_id', '=', $details->merchant_id]])->first();
            $driver_request_timeout = $config->driver_request_timeout * 1000;
            $mulple_stop = (!empty($details->waypoints) && count(json_decode($details->waypoints, true)) > 0) ? true : false;
            $count = (!empty($details->waypoints) && count(json_decode($details->waypoints, true)) > 0) ? count(json_decode($details->waypoints, true)) : 0;
            $dropCount = trans('multipleStop', ['number' => $count]);
            $stops = json_decode($details->waypoints, true);

            if($mulple_stop){
                $all_location = [];
             foreach ($stops as $stop){
                 $all_location = [
                     'address' => $stop['drop_location'],
                     'lat' => (string) $stop['drop_latitude'],
                     'lng' => (string) $stop['drop_longitude'],
                 ];
                 array_push($drop_locations,$all_location);
             }

            }
        }
        $receiver_details = [];
        if (!empty($details->receiver_details)) {
            $receiver_details = [json_decode($details->receiver_details, true)];
        }

        $productDetails = [];
        if (!empty($details->DeliveryPackage)) {
            $deliveryPackages = $details->DeliveryPackage;
            foreach ($deliveryPackages as $deliveryPackage) {
                $productDetails[] = array(
                    'id' => $deliveryPackage->id,
                    'merchant_id' => $deliveryPackage->merchant_id,
                    'product_name' => $deliveryPackage->DeliveryProduct->ProductName,
                    'weight_unit' => $deliveryPackage->DeliveryProduct->WeightUnit->WeightUnitName,
                    'quantity' => $deliveryPackage->quantity
                );
            }
        }

        if (!empty($productDetails)) {
            $arr_packages = [];
            $arr_packages['items'] = $productDetails;
        } else {
            $arr_packages = (object) [];
        }

        // $estimate_bill = $details->CountryArea->Country->isoCode . ' ' . $details->estimate_bill;
        $estimate_bill = $details->CountryArea->Country->isoCode . ' ' . $merchant_helper->PriceFormat($details->estimate_bill, $details->merchant_id);
        if ($details->Merchant->Configuration->homescreen_estimate_fare == 2) {
            $estimate_bill = $this->getPriceRange($details->estimate_bill, $details->CountryArea->Country->isoCode);
        }

        elseif ($details->Merchant->Configuration->homescreen_estimate_fare == 3) {
            $estimate_bill = $this->getPriceRangeForEstimate($details->estimate_bill, $details->CountryArea->Country->isoCode, 3);
        }

        if($details->is_in_drive == 1 && !empty($details->offer_amount)){
            // $estimate_bill = $details->CountryArea->Country->isoCode . ' ' . $details->offer_amount;
            $estimate_bill = $details->CountryArea->Country->isoCode . ' ' . $merchant_helper->PriceFormat($details->offer_amount, $details->merchant_id);
        }

        $merchant_id = $details->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $description = $details->estimate_distance . ' ' . $details->estimate_time;
        //        .' '.$details->estimate_bill;

        $additional_details = [];
        if($details->Merchant->ApplicationConfiguration->gender == 1 && !empty($details->gender)){
            array_push($additional_details, array(
                "label" => trans("$string_file.gender_match"),
                "value" => $details->gender == 1 ? trans("$string_file.male") : trans("$string_file.female")
            ));
        }
        if($details->Merchant->BookingConfiguration->wheel_chair_enable == 1 && !empty($details->wheel_chair_enable)){
            array_push($additional_details, array(
                "label" => trans("$string_file.wheel_chair"),
                "value" => $details->wheel_chair_enable == 1 ? true : false
            ));
        }
        if($details->Merchant->Configuration->no_of_person == 1 && !empty($details->no_of_person)){
            array_push($additional_details, array(
                "label" => trans("$string_file.no_of_person"),
                "value" => $details->no_of_person
            ));
        }
        if($details->Merchant->Configuration->no_of_children == 1 && !empty($details->no_of_children)){
            array_push($additional_details, array(
                "label" => trans("$string_file.no_of_children"),
                "value" => $details->no_of_children
            ));
        }
        if($details->Merchant->Configuration->no_of_bags == 1 && !empty($details->no_of_bags)){
            array_push($additional_details, array(
                "label" => trans("$string_file.no_of_bags"),
                "value" => $details->no_of_bags
            ));
        }
        if($details->Merchant->Configuration->no_of_pats == 1 && !empty($details->no_of_pats)){
            array_push($additional_details, array(
                "label" => trans("$string_file.no_of_pats"),
                "value" => $details->no_of_pats
            ));
        }

        $volumetric_capacity_calculation_divisor_value = isset($details->Merchant->BookingConfiguration->volumetric_capacity_calculation) ? (float)$details->Merchant->BookingConfiguration->volumetric_capacity_calculation : 4000.00;

        $vehicleDeliveryPackage = [];
        
         $user_id = $details->User->id;
        $avg = "";
        if($user_id){
            $avg = BookingRating::whereHas('Booking', function ($q) use ($user_id) {
                $q->where('user_id', $user_id);
            })->avg('driver_rating_points');
        }
        

        $data = [
            'timer' => ($driver_request_timeout > 0) ? $driver_request_timeout : 60000,
            'cancel_able' => true,
            'id' => $details->id,
            'is_in_drive' => ($details->is_in_drive == 1) ? true : false,
            'offer_value' => $details->CountryArea->Country->isoCode . ' ' . $details->offer_amount,
            'status' => $details->booking_status,
            'later_booking_date' => !empty($details->later_booking_date) ? $details->later_booking_date : "",
            'later_booking_time' => !empty($details->later_booking_time) ? merchant_time_format($details->Merchant, $details->later_booking_time) : "",
            'generated_time' => (int) $details->booking_timestamp,
            'segment_type' => $details->Segment->slag,
            'highlights' => [
                'number' => $details->merchant_booking_id,
                'price' => $estimate_bill,
                'price_visibility'=> $details->Merchant->BookingConfiguration->request_show_price == 1 ? true : false,
                'name' => $details->Segment->Name($details->merchant_id) . ' ' . trans("$string_file.ride"),
                'service_type' =>$details->service_type_id ? $details->ServiceType->ServiceName($details->merchant_id) : "",
                'payment_mode' => $details->PaymentMethod->MethodName($details->merchant_id) ? $details->PaymentMethod->MethodName($details->merchant_id) : $details->PaymentMethod->payment_method,
                'payment_mode_visibility'=> $details->Merchant->BookingConfiguration->request_payment_method == 1 ? true : false,
                'description' => $description,
                'description_visibility'=> $details->Merchant->BookingConfiguration->request_distance == 1 ? true : false,
                'offer_ride_note' => trans("$string_file.offer_ride_driver_message"),
                'vehicle_type' => $details->vehicle_type_id ? $details->vehicleType->VehicleTypeName : "",
            ],
            'pickup_details' => [
                'header' => trans("$string_file.pickup_location"),
                'locations' => [
                    [
                        'address' => $details->pickup_location,
                        'lat' => (string) $details->pickup_latitude,
                        'lng' => (string) $details->pickup_longitude,
                    ]
                ],
            ],
            'drop_details' => [
                'header' => trans("$string_file.drop_off_location"),
                'locations' => $drop_locations,
                'drop_location_visibility'=> $details->Merchant->BookingConfiguration->drop_location_request == 1 ? true : false,
            ],
            'customer_details' => [
                [
                    "name" => $details->User->UserName,
                    "email" => isset($details->User->email) ? $details->User->email : "",
                    "phone" => $details->User->UserPhone,
                    "image" => !empty($details->User->UserProfileImage) ? get_image($details->User->UserProfileImage, 'user', $merchant_id, true, false) : "",
                    "customer_details_visibility"=> $details->Merchant->BookingConfiguration->request_customer_details == 1 ? true : false,
                    "totalTrips"=> (string)$details->User->total_trips,
                    "rating"=>number_format($avg,2)
                    
                ]
            ],
            'receiver_details' => $receiver_details,
            'package_details' => $arr_packages,
            'additional_notes' => !empty($additional_notes) ? [$additional_notes] : [],
            'additional_information' => !empty($additional_information) ? [json_decode($additional_information, true)] : [],
            'additional_details' => $additional_details,
            'corporate' => !empty($details->User->corporate_id) ? $details->User->Corporate->corporate_name : "",
            'additional_movers' => !empty($details->additional_movers) ? $details->additional_movers : 0,
            'outstation_type' => !empty($details->outstation_ride_type) ? ($details->outstation_ride_type == 1 ? trans("$string_file.one_way") : trans("$string_file.round_trip")) : '',
            'vehicle_delivery_packages'=> $vehicleDeliveryPackage
        ];
        
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        $api_version = "1.5";
        if(isset(request()->merchant_id)){
            $merchant_id = request()->merchant_id;
            $version_management = \App\Models\VersionManagement::where('merchant_id',$merchant_id)->first();
            $api_version = !empty($version_management->id) ? "$version_management->api_version" : $api_version;
        }
        return response()->json(['version' => $api_version,"result" => "1", 'message' => trans("$string_file.data_found"), 'segment_data' => $data,'segment_group_id'=>$details->Segment->segment_group_id,'segment_sub_group'=>$details->Segment->sub_group_for_app,'segment_type'=>$details->Segment->slag]);
    }

    public function getPriceRange($amount,$currency){
        if (empty($amount)) {
            return '';
        }
        $amount = trim(str_replace($currency, '', $amount));
        if ($amount < 500) {
            $price_range = $currency . " 100 - 500";
        } elseif ($amount >= 500 && $amount < 1000) {
            $price_range = $currency . " 500 - 1000";
        } elseif ($amount >= 1000 && $amount < 1500) {
            $price_range = $currency . " 1000 - 1500";
        } elseif ($amount >= 1500 && $amount < 2000) {
            $price_range = $currency . " 1500 - 2000";
        } elseif ($amount >= 2000 && $amount < 2500) {
            $price_range = $currency . " 2000 - 2500";
        } elseif ($amount >= 2500 && $amount < 3000) {
            $price_range = $currency . " 2500 - 3000";
        } elseif ($amount >= 3000 && $amount < 3500) {
            $price_range = $currency . " 3000 - 3500";
        } elseif ($amount >= 3500 && $amount < 4000) {
            $price_range = $currency . " 3500 - 4000";
        } elseif ($amount >= 4000 && $amount < 4500) {
            $price_range = $currency . " 4000 - 4500";
        } elseif ($amount >= 4500 && $amount < 5000) {
            $price_range = $currency . " 4500 - 5000";
        } elseif ($amount >= 5000 && $amount < 5500) {
            $price_range = $currency . " 5000 - 5500";
        } elseif ($amount >= 5500 && $amount < 6000) {
            $price_range = $currency . " 5500 - 6000";
        } elseif ($amount >= 6000 && $amount < 6500) {
            $price_range = $currency . " 6000 - 6500";
        } else {
            $price_range = "More Than 7000";
        }
        return $price_range;
    }


    public function getPriceRangeForEstimate($amount, $currency, $type = 3)
    {
        if (empty($amount)) {
            return '';
        }
        $amount = trim(str_replace($currency, '', $amount));
        if ($type == 3) {
            $amount_next_range = $amount * 1.10;
            $price_range = $currency . " ".round_number($amount, 0)." - ".round_number($amount_next_range, 0);
        }
        return $price_range;
    }

    public function getBonsBankDetails(Request $request){
        if($request->type == 1){
            $driver = $request->user('api');
        }else{
            $driver = $request->user('api-driver');
        }
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $merchant_id = $driver->merchant_id;
        $data  = [];

        try {
            $bonsQrPayment = BonsBankToBankQrGateway::where('merchant_id', $driver->merchant_id)->first();
            if ($bonsQrPayment) {
                $transaction_id = 'TRANS_' . $driver->id . '_' . time();

                $data = [
                    'id' => $bonsQrPayment->id,
                    'account_name' => $bonsQrPayment->AccountName,
                    'bank_name' => $bonsQrPayment->BankName,
                    'qr_image' => get_image($bonsQrPayment->qr_image, 'bons_qr_image', $merchant_id),
                    'transaction_id' => $transaction_id,
                ];

                return $this->SuccessResponse("$string_file.data_found", $data);
            } else {
                return $this->FailedResponse("$string_file.not_found");
            }
        }
        catch(\Exception $e){
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
    }
    
    public function submitBonsBankDetails(Request $request){
        
        $validator = Validator::make($request->all(),[
            'amount' => 'required',
            'transaction_id'=> 'required',
            'document_image'=> 'required|file',
            'bons_qr_id'=> 'required',
            'payment_option_id'=>'required',
            'type'=>'required'
        ]);

        if ($validator->fails()){
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        
        if($request->type == 1){
            $driver = $request->user('api');
        }else{  
            $driver = $request->user('api-driver');
        }
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $merchant_id = $driver->merchant_id;
        $transaction_id = $request->transaction_id;
        $image = "";
        if ($request->hasFile('document_image')) {
            $image = $this->uploadImage('document_image', 'bons_qr_image', $merchant_id);
        }
        $data  = [];

        try {
            $transaction = DB::table('transactions')->where('payment_transaction_id',$transaction_id)->first();
            if($transaction){
                return $this->failedResponse("$string_file.transaction_already_available");
            }
            DB::table('transactions')->insert([
                'user_id' => $request->type == 1 ? $driver->id : NULL,
                'driver_id' => $request->type == 2 ? $driver->id : NULL,
                'merchant_id' => $merchant_id,
                'payment_transaction_id'=> $transaction_id,
                'amount' => $request->amount,
                'payment_option_id' => $request->payment_option_id ?? 135,  //payment option id
                'request_status'=> 1,
                'status'=> $request->type,
                'reference_id'=> $request->bons_qr_id,
                'checkout_id'=> $image,
                'status_message'=> 'PENDING',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return $this->SuccessResponse("$string_file.payment_done_need_approval",$data);
        }
        catch(\Exception $e){
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        
    }
}
