<?php

namespace App\Http\Controllers\Api;

use App\Events\SendUserInvoiceMailEvent;
use App\Events\SendDriverInvoiceMailEvent;
//use App\Http\Controllers\Helper\Merchant;
use App\Http\Controllers\Helper\DistanceCalculation;
use App\Http\Controllers\Helper\MapBoxController;
use App\Http\Controllers\Helper\PolygenController;
use App\Http\Controllers\Helper\DistanceController;
use App\Http\Controllers\Helper\ReferralController;
use App\Http\Controllers\Helper\RewardPoint;
use App\Http\Controllers\Helper\ExtraCharges;
use App\Http\Controllers\Helper\Toll;
use App\Http\Controllers\Helper\TwilioMaskingHelper;
use App\Http\Controllers\Merchant\WhatsappController;
use App\Http\Resources\DeliveryCheckoutResource;
use App\Models\BookingBiddingDriver;
use App\Models\BookingTransaction;
use App\Models\DriverSubscriptionRecord;
use App\Models\PaymentOption;
use App\Http\Controllers\PaymentMethods\RandomPaymentController;
use App\Models\PaymentOptionsConfiguration;
use App\Models\UserCard;
use App\Models\UserSubscriptionRecord;
use App\Models\VehicleType;
use App\Traits\BookingTrait;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PaymentMethods\CancelPayment;
use App\Http\Controllers\PaymentMethods\Payment;
use App\Http\Controllers\Services\NormalController;
use App\Http\Controllers\Services\OutstationController;
use App\Http\Controllers\Services\PoolController;
use App\Http\Controllers\Services\RentalController;
use App\Http\Controllers\Services\TransferController;
use App\Models\ApplicationConfiguration;
use App\Models\Booking;
use App\Models\BookingCheckout;
use App\Models\BookingCheckoutPackage;
use App\Models\BookingConfiguration;
use App\Models\BookingCoordinate;
use App\Models\BookingDetail;
use App\Models\BookingRating;
use App\Models\BookingRequestDriver;
use App\Models\Configuration;
use App\Models\CountryArea;
use App\Models\Driver;
use App\Models\DriverCancelBooking;
use App\Models\DriverVehicle;
use App\Models\FavouriteDriver;
use App\Models\Merchant;
use App\Models\Onesignal;
use App\Models\PoolRideList;
use App\Models\PriceCard;
use App\Models\PromoCode;
use App\Models\QuestionUser;
use App\Models\Sos;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Helper\BookingDataController;
use App\Http\Controllers\Helper\HolderController;
use App\Http\Controllers\Helper\PriceController;
use App\Http\Controllers\Helper\GoogleController;
use App\Http\Controllers\Helper\FindDriverController;
use App\Http\Controllers\Helper\SmsController;
use App\Models\SmsConfiguration;
use App\Http\Controllers\Api\CashbackController;
use App\Traits\ImageTrait;
use App\Models\Outstanding;
use App\Http\Controllers\Helper\DriverRecords;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Models\BookingDeliveryDetails;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use App\Models\ServiceType;
use App\Http\Controllers\Helper\CommonController;
use App\Traits\PolylineTrait;
use App\Traits\DriverTrait;
use App\Http\Controllers\PaymentMethods\PayPhone\PayPhoneController;
use App\Models\CancelPolicy;
use App\Events\SendWhatsappNotificationEvent;
use App\Models\SegmentPriceCard;
use App\Models\HandymanOrder;
use App\Http\Controllers\Helper\Merchant as MerchantHelper;
use App\Http\Controllers\Api\HandymanOrderController as HandymanController;
use Illuminate\Support\Facades\Redis;

class BookingController extends Controller
{
    use ImageTrait, BookingTrait, ApiResponseTrait, MerchantTrait, PolylineTrait, DriverTrait;


    public function checkBookingStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $booking = Booking::find($request->booking_id);
        $booking_status = $booking->booking_status;
        $data = array('booking_id' => $booking->id, 'booking_status' => (string)$booking_status);
        $string_file = $this->getStringFile(NULL, $booking->Merchant);
        return $this->successResponse(trans("$string_file.booking_status"), $data);
    }

    public function changePaymentDuringRide(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->wherein('booking_status', [1002, 1003, 1004]);
                }),
            ],
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $booking_obj = new Booking;
            $booking = $booking_obj->getBookingBasicData($request);
            $old_payment_method = $booking->PaymentMethod->payment_method;
            $booking->payment_method_id = $request->payment_method_id;
            $booking->save();
            setLocal($booking->Driver->language);
            $string_file = $this->getStringFile(NULL, $booking->Merchant);
            $data = ['booking_id' => $booking->booking_id, 'booking_status' => $booking->booking_status];
            $title = trans("$string_file.payment_method_changed");
            $message = trans("$string_file.payment_method_changed", [
                'old_method' => $old_payment_method,
                'new_method' => $booking->PaymentMethod->payment_method
            ]);
            $notification_data['notification_type'] = "PAYMENT_CHANGE";
            $notification_data['segment_type'] = $booking->Segment->slag;
            $notification_data['segment_data'] = $data;
            $large_icon = $this->getNotificationLargeIconForBooking($booking);
            $arr_param = ['driver_id' => $booking->driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $booking->merchant_id, 'title' => $title, 'large_icon' => $large_icon];
            Onesignal::DriverPushMessage($arr_param);
            setLocal();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();

        $mes = trans("$string_file.payment_method_changed_successfully");
        return $this->successResponse($mes, []);
    }


    public function BookingStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                Rule::exists('bookings', 'id'),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $booking = Booking::select('id', 'booking_status')->find($request->booking_id);
        $message = CommonController::BookingStatus($booking->booking_status);
        return $this->successResponse($message, $booking);
    }

    public function BookingOtpVerify(Request $request)
    {
        if(!empty($request->timestampvalue)){
            $cacheKey = 'driver_booking_otp_verify_' . $request->timestampvalue;

            if (Cache::has($cacheKey)) {
                $response = Cache::get($cacheKey);
                $message = $response['message'];
                return $this->successResponse($message, []);
            }
        }

        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id'),
            ],
            'otp' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $booking = Booking::select('id', 'ride_otp', 'ride_otp_verify', 'merchant_id')->find($request->booking_id);
        $string_file = $this->getStringFile(NULL, $booking->Merchant);
        if ($booking->ride_otp == $request->otp) {
            $booking->ride_otp_verify = 3;
            $booking->save();
            if(!empty($request->timestampvalue)){
                Cache::put($cacheKey, ["message" => trans("$string_file.otp_verified")], 120);
            }
            return $this->successResponse(trans("$string_file.otp_verified"), []);
        }

        return $this->failedResponse(trans("$string_file.invalid_otp_try_again"));
    }


    public function MakePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required_without:outstanding_id|exists:bookings,id',
            'outstanding_id'=> 'required_without:booking_id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'card_id' => 'required_if:payment_method_id,2',
            'success' => 'required|integer',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $booking_obj = new Booking;
            $booking = $booking_obj->getBookingBasicData($request);
            $previous_payment_method = !empty($booking)? $booking->payment_method_id: "";
            $outstanding = Outstanding::where(['id' => $request->outstanding_id,'pay_status' => 0])->first();
            $string_file = !empty($booking)? $this->getStringFile(NULL, $booking->Merchant) : $this->getStringFile(NULL, $outstanding->user->Merchant);
            $is_payment_success = false;
            $status = false;
            if ($request->success == 1) { // PAYMENT DONE
                $is_payment_success = true;
                /*
                @Ayush
                 */
                if (isset($request->outstanding_id) && $request->outstanding_id != '' && $request->payment_method_id == 3) {
                    // $outstanding = Outstanding::where(['id' => $request->outstanding_id,'pay_status' => 0])->first();
                    if(!empty($outstanding)){
                        $amount = $outstanding->amount;
                        $wallet_balance = $outstanding->User->wallet_balance;
                        if ($wallet_balance < $amount) {
                            return $this->failedResponse(trans("$string_file.low_wallet_warning"));
                        }

                        $booking_id = !empty($outstanding->booking_id) ? $outstanding->booking_id : null;
                        $handyman_order_id = !empty($outstanding->handyman_order_id)? $outstanding->handyman_order_id: null;


                        $paramArray = array(
                            'user_id' => $outstanding->user_id,
                            'amount' => $amount,
                            'narration' => (!empty($booking->id)) ? 3 : 27,
                            'platform' => 2,
                            'payment_method' => 3,
                        );
                        if($booking_id != null){
                            $paramArray['booking_id']=$booking_id;
                        }
                        else{
                            $paramArray['handyman_order_id']=$handyman_order_id;
                        }

                        if(!empty($handyman_order_id)){
                            $order = HandymanOrder::find($handyman_order_id);
                            $handymanOrderController = new HandymanController();
                            if($order->payment_status != 1){
                                $request->merge(['order_id'=>$order->id, 'payment_method_id'=>$order->payment_method_id, 'amount'=>$amount ]);
                                $handymanOrderController->bookingPayment($request);
                            }
                        }

                        WalletTransaction::UserWalletDebit($paramArray);
                        Outstanding::where(['id' => $outstanding->id])->update(['pay_status' => 1]);
                    }
                }
                else if (isset($request->outstanding_id) && $request->outstanding_id != '') {
                    Outstanding::where(['id' => $request->outstanding_id, 'user_id' => $booking->user_id, 'reason' => '2', 'pay_status' => 0])->update(['pay_status' => 1]);
                    $currency = $booking->CountryArea->Country->isoCode;
                    $payment = new Payment();
                    $booking_transaction_submit = $booking->BookingTransaction;
                    $arr_req_param = [
                        'merchant_id' => $booking->merchant_id,
                        'booking_id' => $booking->id,
                        'payment_method_id' => $request->payment_method_id,
                        'amount' => $booking_transaction_submit->customer_paid_amount,
                        'card_id' => $request->card_id,
                        'user_id' => $booking->user_id,
                        'currency' => $currency,
                        'driver_sc_account_id' => $booking->Driver->sc_account_id,
                        'booking_transaction' => $booking_transaction_submit,
                        'request_from' => 'USER_MAKE_PAYMENT',
                    ];
                    $status = $payment->MakePayment($arr_req_param);
                } elseif ($request->payment_method_id == 6) {
                    $currency = $booking->CountryArea->Country->isoCode;
                    $payment = new Payment();
                    $booking_transaction_submit = $booking->BookingTransaction;
                    //p($booking_transaction_submit);
                    $arr_req_param = [
                        'merchant_id' => $booking->merchant_id,
                        'booking_id' => $booking->id,
                        'payment_method_id' => $request->payment_method_id,
                        'amount' => $booking_transaction_submit->customer_paid_amount,
                        'card_id' => $booking->card_id,
                        'user_id' => $booking->user_id,
                        'currency' => $currency,
                        'driver_sc_account_id' => $booking->Driver->sc_account_id,
                        'booking_transaction' => $booking_transaction_submit,
                    ];
                    $status = $payment->MakePayment($arr_req_param);


                    $booking->payment_status = 1; // refreshing the payment status
                    // give the commission to driver
                    $this->updateRideAmountInDriverWallet($booking, $booking_transaction_submit);
                } else {
                    if ($request->payment_method_id == 3) {
                        $amount = $booking->BookingTransaction->online_payment;
                        $wallet_balance = $booking->User->wallet_balance;
                        if ($wallet_balance < $amount) {
                            return $this->failedResponse(trans("$string_file.low_wallet_warning"));
                        }

                        $paramArray = array(
                            'user_id' => $booking->user_id,
                            'booking_id' => $booking->id,
                            'amount' => $amount,
                            'narration' => (!empty($booking->id)) ? 4 : 8,
                            'platform' => 2,
                            'payment_method' => 1,
                        );
                        WalletTransaction::UserWalletDebit($paramArray);
                    } else {
                        $merchant = new \App\Http\Controllers\Helper\Merchant();
                        if ($previous_payment_method != 1 && $request->payment_method_id == 1) {
                            $booking->BookingTransaction->cash_payment = $booking->BookingTransaction->online_payment;
                            $booking->BookingTransaction->online_payment = '0.0';
                            $booking->BookingTransaction->trip_outstanding_amount = $merchant->TripCalculation(($booking->BookingTransaction->driver_total_payout_amount + $booking->BookingTransaction->amount_deducted_from_driver_wallet - $booking->BookingTransaction->cash_payment), $booking->merchant_id);
                            $booking->BookingTransaction->save();
                        }
                    }

                    $booking->payment_status = 1;
                    $booking->payment_method_id = $request->payment_method_id;
                    $booking->save();

                    // update driver wallet because payment done from user screen after ride end,
                    $booking_transaction_submit = $booking->BookingTransaction;
                    $this->updateRideAmountInDriverWallet($booking, $booking_transaction_submit, $booking->id);
                }
                // send mail of payment
                if(!empty($booking)){
                    event(new SendUserInvoiceMailEvent($booking));
                }
            } elseif ($request->success == 2) { //PAYMENT FAILED
                $booking->payment_method_id = $request->payment_method_id;
                $booking->save();
                $is_payment_success = false;
                BookingDetail::where([['booking_id', '=', $request->booking_id]])->update(['payment_failure' => 2]);
            } else // PAYMENT PENDING AND NEED TO SEND REQUEST
            {
                // send request to payphone server if payphone is enabled
                // check user number is payment option is payphone
                if (!empty($request->payment_option_id) && $request->payment_method_id == 4) {
                    $option = PaymentOption::select('slug', 'id')->where('id', $request->payment_option_id)->first();
                    if (!empty($option) && $option->slug == 'PAYPHONE') {
                        $booking_transaction = $booking->BookingTransaction;
                        $tax1 = $booking_transaction->tax_amount;
                        $tax2 = $booking_transaction->tax_amount;
                        $amount = $booking_transaction->customer_paid_amount + $tax1 + $tax2;
                        $arr_payment_details = [];
                        $arr_payment_details['amount'] = [
                            'amount' => $amount,
                            'tax' => $booking_transaction->tax_amount,
                            'amount_with_tax' => $booking_transaction->tax_amount,
                            'amount_without_tax' => $booking_transaction->customer_paid_amount - ($tax1 + $tax2),
                        ];
                        $arr_payment_details['booking_id'] = $booking->id;
                        $payment_option_config = $option->PaymentOptionConfiguration;
                        $payphone = new PayPhoneController;
                        $payphone_response = $payphone->paymentRequest($request, $payment_option_config, $arr_payment_details);
                        DB::commit();
                        return $this->successResponse(trans("$string_file.success"), $payphone_response);
                    }
                }
            }

            // commit the change of db
            DB::commit();
            
            // IF PAYMENT DONE THEN SEND NOTIFICATION
            // Notification will not be sent in case of outstanding payment
            if ($status || $is_payment_success) {
                if (empty($request->outstanding_id) && !empty($booking->driver_id)) {
                    $title = trans("$string_file.payment_success");
                    $message = trans("$string_file.payment_done");
                    $data['notification_type'] = 'ONLINE_PAYMENT_RECEIVED';
                    $data['segment_type'] = $booking->Segment->slag;
                    $data['segment_data'] = ['id' => $booking->id, 'handyman_order_id' => NULL];
                    $arr_param = ['driver_id' => $booking->driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $booking->merchant_id, 'title' => $title, 'large_icon' => ""];
                    Onesignal::DriverPushMessage($arr_param);
                }
                return $this->successResponse(trans("$string_file.payment_done"));
            }
            return $this->failedResponse(trans("$string_file.payment_failed"));
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
            // return $e;
        }
    }

    public function ApplyPromoCode(Request $request)
    {

        $user = $request->user('api');
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $validator = [
            'checkout_id' => 'required|exists:booking_checkouts,id',
            'promo_code' => 'required|exists:promo_codes,promoCode',
        ];
        $promo_code_error_msg = trans("$string_file.invalid_promo_code");
        $message = [
            'promo_code.exists' => $promo_code_error_msg,
        ];
        $validator = Validator::make($request->all(), $validator, $message);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $checkout = BookingCheckout::find($request->checkout_id);
            $user_id = $checkout->user_id;
            $price_card_id = $checkout->price_card_id;
            $country_area_id = $checkout->country_area_id;
            $promocode = PromoCode::where([['segment_id', '=', $checkout->segment_id], ['country_area_id', '=', $country_area_id], ['promoCode', '=', $request->promo_code], ['deleted', '=', NULL], ['promo_code_status', '=', 1]])->first();
            if (empty($promocode)) {
                return $this->failedResponse($promo_code_error_msg);
            }
            $validity = $promocode->promo_code_validity;
            $start_date = $promocode->start_date;
            $end_date = $promocode->end_date;
            $currentDate = date("Y-m-d");
            if ($validity == 2 && ($currentDate < $start_date || $currentDate > $end_date)) {
                return $this->failedResponse(trans("$string_file.promo_code_expired_message"));
            }
            $promo_code_limit = $promocode->promo_code_limit;
            $total_useage = Booking::where([['promo_code', '=', $promocode->id], ['booking_status', '!=', 1016]])->count();
            if ($total_useage >= $promo_code_limit) {
                return $this->failedResponse(trans("$string_file.promo_code_expired_message"));
            }
            $promo_code_limit_per_user = $promocode->promo_code_limit_per_user;
//          $use_by_user = Booking::where([['promo_code', '=', $promocode->id], ['user_id', '=', $user_id], ['booking_status', '!=', 1016]])->count();
            $use_by_user = Booking::where('promo_code', $promocode->id)
                ->where('user_id', $user_id)
                ->whereNotIn('booking_status', [1016, 1001])
                ->count();

            if ($use_by_user >= $promo_code_limit_per_user) {
                return $this->failedResponse(trans("$string_file.user_limit_promo_code_expired"));
            }
            $applicable_for = $promocode->applicable_for;
            //$newUser = Booking::where([['user_id', '=', $user_id],['booking_status','=',1005]])->count();
            $newUser = User::find($user_id);

            if ($validity == 3) {
                $condition =  json_decode($promocode->additional_conditions);
                switch ($condition->promo_condition){
                    case "START_AT_SIGNUP":
                        $no_of_days = $condition->no_of_days;
                        $user_signup_date = \Carbon\Carbon::parse($newUser->created_at);
                        $expiry_date = $user_signup_date->copy()->addDays($no_of_days);
                        $is_expired = \Carbon\Carbon::now()->isAfter($expiry_date);
                        if($is_expired){
                            return $this->failedResponse(trans("common.promo_code_expired_message"));
                        }
                        break;
                    case "NUMBER_OF_RIDE_CONDITION":
                         $no_of_days = $condition->no_of_days;
                         if($no_of_days){
                             $user_signup_date = Carbon::parse($newUser->created_at);
                             $expiry_date = $user_signup_date->copy()->addDays($no_of_days);
                             $is_expired = Carbon::now()->isAfter($expiry_date);
                            if($is_expired){
                                return $this->failedResponse(trans("common.promo_code_expired_message"));
                            }
                         }else{
                           $numberOfRidesRequired = (int) $condition->no_of_ride;
                            $rideTime = (int) ($condition->ride_time ?? 0); // assume this is in minutes

                            // Time range: from `now - rideTime` to `now`
                            $now = Carbon::now();
                            $fromTime = $now->copy()->subMinutes($rideTime);

                            // Count bookings by user in that window
                            $bookingCount = Booking::where('user_id', $user_id)
                                ->whereBetween('created_at', [$fromTime, $now])
                                ->count();

                            if($bookingCount >= $numberOfRidesRequired){

                            }else{
                                return $this->failedResponse(trans("common.promo_code_not_applicable_in_ride_time"));
                            }
                         }
                         break;
                }
            }

            if ($applicable_for == 2 && $newUser->created_at < $promocode->updated_at) {
                return $this->failedResponse(trans("$string_file.promo_code_for_new_user"));
            }

            $order_minimum_amount = $promocode->order_minimum_amount;
            if (!empty($checkout->estimate_bill) && $checkout->estimate_bill < $order_minimum_amount) {
                $message = trans_choice("$string_file.promo_code_order_value", 3, ['AMOUNT' => $order_minimum_amount]);
                return $this->failedResponse(trans($message));
            }

            if (!is_null($promocode->promo_code_vehicle_type)) {
                $promocode_vehicle_type = json_decode($promocode->promo_code_vehicle_type);
                if(!in_array($checkout->vehicle_type_id,$promocode_vehicle_type))
                {
                    $message = "Promocode not valid for this vehicle type";
                    return $this->failedResponse(trans($message));
                }
            }

            /*(promo not allowed <= 0)*/
            if ($promocode->promo_code_value_type == 1) {
                $parameterAmount = $promocode->promo_code_value;
            } else {
                $promoMaxAmount = !empty($promocode->promo_percentage_maximum_discount) ? $promocode->promo_percentage_maximum_discount : 0;
                $parameterAmount = ($checkout->estimate_bill * $promocode->promo_code_value) / 100;
                $parameterAmount = (($parameterAmount > $promoMaxAmount) && ($promoMaxAmount > 0)) ? $promoMaxAmount : $parameterAmount;
            }

            if(($checkout->estimate_bill - $parameterAmount) <= 0){
                $message = trans_choice("$string_file.promo_code_order_value", 3, ['AMOUNT' => $order_minimum_amount]);
                return $this->failedResponse(trans($message));
            }

            $checkout->promo_code = $promocode->id;
            $checkout->save();

            $bookingDataObj = new BookingDataController();
            $promo_params = $bookingDataObj->feedPromoCodeValue($checkout);
            $checkout = $checkout->fresh();
            $checkout->discounted_amount = $promo_params['discounted_amount'];
            $checkout->save();

            if ($checkout->segment_id == 2) {
                $result = new DeliveryCheckoutResource($checkout);
            } else {
                $bookingData = new BookingDataController();
                $result = $bookingData->CheckOut($checkout);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.promo_code_applied"), $result);
    }
    /*
    @Ayush
    */
    public function AutomaticApplyPromoCode($checkout){
        $promocode = PromoCode::where([['segment_id', '=', $checkout->segment_id], ['country_area_id', '=', $checkout->country_area_id], ['deleted', '=', NULL], ['promo_code_status', '=', 1]])->get();
        if (!empty($promocode)) {
            $maximum_val = 0;
            $code_id = null;
            $code_name = null;
            foreach($promocode as $code){
                
                $validity = $code->promo_code_validity;
                $start_date = $code->start_date;
                $end_date = $code->end_date;
                $currentDate = date("Y-m-d");
                
                $promo_code_limit = $code->promo_code_limit;
                $total_useage = Booking::where([['promo_code', '=', $code->id], ['booking_status', '!=', 1016]])->count();
                
                $promo_code_limit_per_user = $code->promo_code_limit_per_user;
                $use_by_user = Booking::where('promo_code', $code->id)
                    ->where('user_id', $checkout->user_id)
                    ->whereNotIn('booking_status', [1016, 1001])
                    ->count();

                $applicable_for = $code->applicable_for;
                $newUser = User::find($checkout->user_id);
                
                $order_minimum_amount = $code->order_minimum_amount;

                /*(promo not allowed <= 0)*/
                if ($code->promo_code_value_type == 1) {
                    $parameterAmount = $code->promo_code_value;
                } else {
                    $promoMaxAmount = !empty($code->promo_percentage_maximum_discount) ? $code->promo_percentage_maximum_discount : 0;
                    $parameterAmount = ($checkout->estimate_bill * $code->promo_code_value) / 100;
                    $parameterAmount = (($parameterAmount > $promoMaxAmount) && ($promoMaxAmount > 0)) ? $promoMaxAmount : $parameterAmount;
                }

                if ($validity == 2 && ($currentDate < $start_date || $currentDate > $end_date)) {
                    return $checkout;
                }


                if ($validity == 3) {
                    $condition =  json_decode($code->additional_conditions);
                    switch ($condition->promo_condition){
                        case "START_AT_SIGNUP":
                            $no_of_days = $condition->no_of_days;
                            $user_signup_date = \Carbon\Carbon::parse($newUser->created_at);
                            $expiry_date = $user_signup_date->copy()->addDays($no_of_days);
                            $is_expired = \Carbon\Carbon::now()->isAfter($expiry_date);
                            if($is_expired){
                                return $checkout;
                            }
                            break;
                    }
                }

                if ($total_useage >= $promo_code_limit || $use_by_user >= $promo_code_limit_per_user ||
                    ($applicable_for == 2 && $newUser->created_at < $code->updated_at) || empty($checkout->estimate_bill) || $checkout->estimate_bill < $order_minimum_amount || (($checkout->estimate_bill - $parameterAmount) <= 0)) {
                    continue ;
                }
                if($maximum_val < $code->promo_code_value){
                    $code_id = $code->id;
                    $code_name = $code->promoCode;
                    $maximum_val = $code->promo_code_value;
                }
            }
            if(!empty($code_id)){
                $checkout->promo_code = $code_id;
                $checkout->save();
                if(isset($checkout->User->Country)){
                    $bookingDataObj = new BookingDataController();
                    $promo_params = $bookingDataObj->feedPromoCodeValue($checkout);
                    $checkout->discounted_amount = $promo_params['discounted_amount'];
                    $checkout->automatic_promo_applied	= 1;
                    $checkout->save();
                }
            }

            return $checkout;
        }
    }

    public function ApplyDefaultPromoCode($checkout){
        $code = PromoCode::where([['segment_id', '=', $checkout->segment_id], ['country_area_id', '=', $checkout->country_area_id], ['deleted', '=', NULL], ['promo_code_status', '=', 1], ['is_default_promo_code', '=', 1]])->first();
        if(empty($code))
            return $checkout;
        $validity = $code->promo_code_validity;
        $start_date = $code->start_date;
        $end_date = $code->end_date;
        $currentDate = date("Y-m-d");
        $maximum_val = 0;

        $promo_code_limit = $code->promo_code_limit;
        $total_useage = Booking::where([['promo_code', '=', $code->id], ['booking_status', '!=', 1016]])->count();

        $promo_code_limit_per_user = $code->promo_code_limit_per_user;
        $use_by_user = Booking::where('promo_code', $code->id)
            ->where('user_id', $checkout->user_id)
            ->whereNotIn('booking_status', [1016, 1001])
            ->count();

        $applicable_for = $code->applicable_for;
        $newUser = User::find($checkout->user_id);

        $order_minimum_amount = $code->order_minimum_amount;

        /*(promo not allowed <= 0)*/
        if ($code->promo_code_value_type == 1) {
            $parameterAmount = $code->promo_code_value;
        } else {
            $promoMaxAmount = !empty($code->promo_percentage_maximum_discount) ? $code->promo_percentage_maximum_discount : 0;
            $parameterAmount = ($checkout->estimate_bill * $code->promo_code_value) / 100;
            $parameterAmount = (($parameterAmount > $promoMaxAmount) && ($promoMaxAmount > 0)) ? $promoMaxAmount : $parameterAmount;
        }

        if ($validity == 2 && ($currentDate < $start_date || $currentDate > $end_date)) {
            return $checkout;
        }


        if ($validity == 3) {
            $condition =  json_decode($code->additional_conditions);
            switch ($condition->promo_condition){
                case "START_AT_SIGNUP":
                    $no_of_days = $condition->no_of_days;
                    $user_signup_date = \Carbon\Carbon::parse($newUser->created_at);
                    $expiry_date = $user_signup_date->copy()->addDays($no_of_days);
                    $is_expired = \Carbon\Carbon::now()->isAfter($expiry_date);
                    if($is_expired){
                        return $checkout;
                    }
                    break;
            }
        }

        if ($total_useage >= $promo_code_limit || $use_by_user >= $promo_code_limit_per_user ||
            ($applicable_for == 2 && $newUser->created_at < $code->updated_at) || empty($checkout->estimate_bill) || $checkout->estimate_bill < $order_minimum_amount || (($checkout->estimate_bill - $parameterAmount) <= 0)) {
            return $checkout;
        }
        if($maximum_val < $code->promo_code_value){
            $code_id = $code->id;
            $code_name = $code->promoCode;
            $maximum_val = $code->promo_code_value;
        }

        if(!empty($code_id)){
            $checkout->promo_code = $code_id;
            $checkout->save();
            $bookingDataObj = new BookingDataController();
            $promo_params = $bookingDataObj->feedPromoCodeValue($checkout);
            $checkout->discounted_amount = $promo_params['discounted_amount'];
            $checkout->default_promo_applied	= 1;
            $checkout->save();
        }

        return $checkout;

    }


    public function RemovePromoCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkout_id' => 'required|exists:booking_checkouts,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $checkout = BookingCheckout::find($request->checkout_id);
            $string_file = $this->getStringFile($checkout->merchant_id);
            if ($checkout->PromoCode) {
                $bill_details = json_decode($checkout->bill_details, true);
                $billDetailsParameterType = array_pluck($bill_details, 'parameterType');
                $index = in_array('PROMO CODE', $billDetailsParameterType) ? array_search('PROMO CODE', $billDetailsParameterType) : '';
                if ($index) {
                    unset($bill_details[$index]);
                    $checkout->bill_details = json_encode($bill_details);
                }
                $checkout->promo_code = null;
                $checkout->save();
            }
            if ($checkout->segment_id == 2) {
                $result = new DeliveryCheckoutResource($checkout);
            } else {
                $bookingData = new BookingDataController();
                $result = $bookingData->CheckOut($checkout);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.promo_remove"), $result);
    }

    public function paymentOption(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'checkout_id' => 'required_without:booking_id|integer|exists:booking_checkouts,id',
            // 'booking_id' => 'required_without:checkout_id|integer|exists:bookings,id',
            'outstanding_id' => 'required_without_all:checkout_id,booking_id|integer',
            'checkout_id' => 'required_without_all:booking_id,outstanding_id|integer|exists:booking_checkouts,id',
            'booking_id' => 'required_without_all:checkout_id,outstanding_id|integer|exists:bookings,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $ride_amount = NULL;
            $string_file = $this->getStringFile($request->user('api')->merchant->id);
            
            if (isset($request->outstanding_id) && $request->outstanding_id != '') {
                $outstanding = Outstanding::find($request->outstanding_id);
                if($outstanding->paid_status == 1)
                    return $this->failedResponse(trans("$string_file.already_cleared"));
                if ($outstanding->booking_id != NULL) {
                    $data = Booking::select('id', 'price_card_id', 'country_area_id', 'final_amount_paid', 'merchant_id')->with('PriceCard')->find($outstanding->booking_id);
                } else if($outstanding->handyman_order_id != NULL) {
                    $data = HandymanOrder::select('id', 'segment_price_card_id', 'country_area_id', 'final_amount_paid', 'merchant_id')->with('SegmentPriceCard')->find($outstanding->handyman_order_id);
                }
                
                $paymentMethod = $data->CountryArea->PaymentMethod->where("id", 3)->first();
                $currency = $data->CountryArea->Country->isoCode;
                $merchant_helper  =  new MerchantHelper();

                $user = User::find($outstanding->user_id);
                $config = Configuration::select('user_wallet_status')->where('merchant_id', $user->merchant_id)->first();
                $action = false;
                $msg = "";
                $wallet_balance =  $user->wallet_balance;
                $corporate_name =  "";
                
                $wallet = $wallet_balance ? $merchant_helper->PriceFormat($wallet_balance,  $user->merchant_id) : '0.00';
                $action  = true;
                if($wallet_balance < $outstanding->amount){
                    $action = false;
                    $msg = trans("$string_file.low_wallet_warning");
                }

                $paymentOPtion = $paymentMethod->MethodName($user->merchant_id) ? $paymentMethod->MethodName($user->merchant_id) : $paymentMethod->payment_method;
                $name = !empty($corporate_name) ? $corporate_name.' '.$paymentOPtion." (".$currency." ".$wallet.")" : $paymentOPtion." (".$currency." ".$wallet.")";
                $options = array(
                    'id' => $paymentMethod->id,
                    'name' => !empty($wallet)  ? $name : $paymentOPtion,
                    'card_id' => "",
                    'action' => $action,
                    'icon' => get_image($paymentMethod->payment_icon, 'payment_icon', $user->merchant_id, false),
                    'message' => $msg
                );
                
                return $this->successResponse("success", [$options]);
            }

            if ($request->booking_id) {
                $data = Booking::select('id', 'price_card_id', 'country_area_id', 'final_amount_paid', 'merchant_id')->with('PriceCard')->find($request->booking_id);
                $ride_amount = $data->final_amount_paid;
            } else {
                $data = BookingCheckout::select('id', 'price_card_id', 'country_area_id', 'estimate_bill')->with('PriceCard')->find($request->checkout_id);
                $ride_amount = $data->estimate_bill;
            }
            $paymentMethods = $data->CountryArea->PaymentMethod;
            $Payment_ids = array_pluck($paymentMethods, 'id');
            $wallet_option = in_array(3, $Payment_ids) ? true : false;
            $currency = $data->CountryArea->Country->isoCode;
            $creditOption = in_array(2, $Payment_ids) ? true : false;
            $bookingData = new BookingDataController();
            $options = $bookingData->PaymentOption($paymentMethods, $request->user('api')->id, $currency, $data->PriceCard->minimum_wallet_amount, $ride_amount, $request->is_business_trip);
            // $config = Configuration::where('merchant_id', $data->merchant_id)->first();
            if (isset($request->booking_id) && $request->booking_id != '') {
                $key_pay_later = array_search(6, array_column($options, 'id'));
                if (!empty($key_pay_later) && $key_pay_later >= 0) {
                    unset($options[$key_pay_later]);
                }
                // outstanding will clear only by online payment
                $user = $request->user('api');
                $out_standing_amount = Outstanding::where(['booking_id' => $request->booking_id, 'user_id' => $user->id, 'reason' => 2, 'pay_status' => 0])->sum('amount');
                if (!empty($out_standing_amount) && $out_standing_amount > 0) {
                    $key_cash = array_search(1, array_column($options, 'id'));
                    if (!empty($key_cash) && $key_cash >= 0) {
                        unset($options[$key_cash]);
                    }
                }
                $options = array_values($options);
            }

            if (isset($request->is_business_trip) && $request->is_business_trip == true) {
                $final_option = [];
                foreach ($options as $option) {
                    if ($option['id'] != 3) {
                        unset($option);
                    } else {
                        $final_option[] = $option;
                    }
                }
                $options = $final_option;
            }
            // $user = $request->user('api');
            // $key_cash = array_search('Cash',array_column($options, 'name'));
            // if(!empty($key_cash) && $user->cash_method_avaliable == 'NO' && $config->cash_payment_method_option == "1"){
            //     unset($options[$key_cash]);
            //     $options = array_values($options);
            // }
            return $this->successResponse("success", $options);
        } catch (Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        //        return response()->json(['result' => "1", 'message' => trans('admin.message534'), 'wallet_button' => $wallat_option, 'credit_button' => $creditOption, 'data' => $options]);
    }

    public function UserAutoCancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1001);
                }),
            ]
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        DB::beginTransaction();
        try {
            $booking = Booking::find($request->booking_id);
            $booking_status = $booking->booking_status;
            if ($booking_status == 1001) {
                $bookingData = new BookingDataController();
                $booking_status = 1016;
                $booking->booking_status = $booking_status;
                if ($booking->promo_code != null) {
                    $promo_code = PromoCode::find($booking->promo_code);
                    if (!empty($promo_code)) {
                        $booking->promo_code = null;
                    }
                }
                $booking->save();
                // inset booking status history
                $this->saveBookingStatusHistory($request, $booking, $booking->id);
                $cancelDriver = BookingRequestDriver::with(['Driver' => function ($q) {
                    $q->addSelect('id', 'last_ride_request_timestamp', 'id as driver_id');
                }])->where([['booking_id', '=', $request->booking_id]])->get();
                $cancelDriver = array_pluck($cancelDriver, 'Driver');
                foreach ($cancelDriver as $key => $value) {
                    $value->last_ride_request_timestamp = date("Y-m-d H:i:s", time() - 100);
                    $value->save();
                }
                $bookingData->SendNotificationToDrivers($booking, $cancelDriver);
            }
        } catch (Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        DB::commit();
        $string_file = $this->getStringFile($booking->merchant_id);
        return $this->successResponse(trans("$string_file.ride_cancelled"), array('booking_id' => $booking->id, 'booking_status' => "$booking_status"));
    }

    public function completeBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1005);
                    //                    $query->where('booking_closure', NULL);
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new Exception($errors[0]);
        }
        DB::beginTransaction();
        try {
            $booking_id = $request->id;
            $booking = Booking::with('BookingDetail', 'PriceCard')->find($booking_id);
            $string_file = $this->getStringFile(NULL, $booking->Merchant);
            if ($booking->PriceCard->pricing_type == 3 && $booking->booking_closure != 1) {
                $refresh_screen = true;
                $validator = Validator::make($request->all(), [
                    'input_values' => 'required',
                ]);
                if ($validator->fails()) {
                    $errors = $validator->messages()->all();
                    throw new Exception($errors[0]);
                }
                $bookingDetails = BookingDetail::where([['booking_id', '=', $booking_id]])->first();
                $newArray = json_decode($request->input_values, true);
                if (sizeof(array_filter($newArray)) == 0) {
                    //                    throw new Exception(trans('api.amount_zero'));
                }
                $amount = array_sum(array_pluck($newArray, 'amount'));
                $maximum_bill_amount = $booking->PriceCard->maximum_bill_amount;
                if ($amount > $maximum_bill_amount || $amount == 0) {
                    //                    throw new Exception(trans('api.message64'));
                }
                $subTotalWithoutDiscount = $amount;
                if (!empty($booking->PromoCode)) {
                    $bookingDetails->total_amount = $amount;
                    $type = $booking->PromoCode->promo_code_value_type;
                    if ($type == 1) {
                        $promoDiscount = $booking->PromoCode->promo_code_value;
                    } else {
                        $promoDiscount = ($amount * $booking->PromoCode->promo_code_value) / 100;
                    }
                    if ($promoDiscount < $amount) {
                        $amount = $amount - $booking->PromoCode->promo_code_value;
                    } else {
                        $amount = "0.00";
                    }
                    $bookingDetails->promo_discount = $promoDiscount;
                } else {
                    $bookingDetails->total_amount = $amount;
                    $bookingDetails->promo_discount = "0.00";
                }
                $payment = new Payment();
                if ($amount > 0) {
                    $array_param = array(
                        'booking_id' => $booking->id,
                        'payment_method_id' => $booking->payment_method_id,
                        'amount' => $amount,
                        'quantity' => 1,
                        'order_name' => $booking->merchant_booking_id,
                        'user_id' => $booking->user_id,
                        'card_id' => $booking->card_id,
                        'currency' => $booking->CountryArea->Country->isoCode,
                    );
                    $payment->MakePayment($array_param);
                    //                    $payment->MakePayment($booking->id, $booking->payment_method_id, $amount, $booking->user_id, $booking->card_id);
                } else {
                    $payment->UpdateStatus(['booking_id' => $booking->id]);
                }
                $bookingDetails->bill_details = $request->input_values;
                $bookingDetails->save();

                if (!empty($taxes_array)) :
                    $newArray = array_merge($newArray, $taxes_array);
                    $total_tax = array_sum(array_pluck($taxes_array, 'amount'));
                    $total_tax = sprintf('%0.2f', $total_tax);
                    $amount += $total_tax;
                endif;

                $booking->final_amount_paid = $amount;
                $booking->booking_closure = 1;
                $booking->save();
                $merchant_id = $booking->merchant_id;
                $user_id = $booking->user_id;
                $bookingData = new BookingDataController();
                $message = ""; //trans('api.notification_driver_input_fare');
                $data = $bookingData->BookingNotification($booking);
                Onesignal::UserPushMessage($user_id, $data, $message, 4, $merchant_id);
            } else {
                if ($booking->payment_status == 1) {
                    $booking->booking_closure = 1;
                    $booking->save();
                    $refresh_screen = false;
                } else {
                    throw new Exception(trans("$string_file.payment_pending"));
                }
            }
            $booking_data = new BookingDataController;
            $rating = BookingRating::updateOrCreate(
                ['booking_id' => $booking_id],
                [
                    'driver_rating_points' => $request->rating,
                    'driver_comment' => $request->comment
                ]
            );
            $user_id = $booking->user_id;
            $avg = BookingRating::whereHas('Booking', function ($q) use ($user_id) {
                $q->where('user_id', $user_id);
            })->avg('driver_rating_points');
            $user = $booking->User;
            $user->rating = round($avg, 2);
            $user->save();


            $request->request->add(['booking_id' => $booking->id]);
            $return_data = $booking_data->bookingReceiptForDriver($request);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        DB::commit();
        return ['message' => trans("$string_file.ride_completed"), 'data' => $return_data];
    }

    // when driver changed drop location
    public function DriverChangeAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
//                    $query->where([['total_drop_location', '<=', 1]]);
                }),
            ],
            'location' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            //                response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }

        $booking = Booking::find($request->booking_id);
        $area = $booking->CountryArea;
        //            ::find($booking->country_area_id);
        $merchant = $booking->Merchant;
        //            ::find($booking->merchant_id);

        $drop_no = isset($request->drop_stop_no) && !empty($request->drop_stop_no) ? $request->drop_stop_no : 0;
        return $this->AddDropAddress($request->booking_id, $request->latitude, $request->longitude, $request->location, $area, 2, $drop_no);
        //        if (!empty($area->DemoConfiguration) || ($merchant->Configuration->drop_outside_area == 1 && in_array($booking->service_type_id, [1, 2, 3, 5]))) {
        //            return $this->AddDropAddress($request->booking_id, $request->latitude, $request->longitude, $request->location, $area, 2);
        //        } else {
        //            return $this->AddDropAddress($request->booking_id, $request->latitude, $request->longitude, $request->location, $area, 2);
        //        }
    }

    // when user changed drop location
    public function UserChangeAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
//                    $query->where([['total_drop_location', '<=', 1]]);
                }),
            ],
            'location' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $booking = Booking::find($request->booking_id);
        $area = $booking->CountryArea;
        //            CountryArea::find($booking->country_area_id);
        $merchant = $booking->Merchant;
        //            Merchant::find($booking->merchant_id);
        $drop_no = isset($request->drop_stop_no) && !empty($request->drop_stop_no) ? $request->drop_stop_no : 0;
        return $this->AddDropAddress($request->booking_id, $request->latitude, $request->longitude, $request->location, $area, 1, $drop_no);
    }

    public function AddDropAddress($booking_id, $latitude, $longitude, $location, $area, $type, $drop_no = 0)
    {
        $booking = Booking::find($booking_id);
        $string_file = $this->getStringFile($booking->merchant_id);
        $ploygon = new PolygenController();
        $checkArea = $ploygon->CheckArea($latitude, $longitude, $area->AreaCoordinates);


        if ($checkArea) {
            if ($drop_no == 0) {
                $booking->drop_latitude = $latitude;
                $booking->drop_longitude = $longitude;
                $booking->drop_location = $location;
                $booking->save();
                // update booking details table
                $details = $booking->BookingDetail;
                $details->end_latitude = $latitude;
                $details->end_longitude = $longitude;
                $details->end_location = $location;
                $details->save();
            } else {
                $waypoint = json_decode($booking->waypoints, true);
                if (!empty($waypoint)) {
                    $key = array_search($drop_no, array_column($waypoint, 'stop'));
                    $waypoint[$key]['drop_latitude'] = $latitude;
                    $waypoint[$key]['drop_longitude'] = $longitude;
                    $waypoint[$key]['drop_location'] = $location;
                }
                $booking->waypoints = json_encode($waypoint);
                $booking->save();
            }

            if (!empty($booking->segment_id) && $booking->Segment->slug == "DELIVERY") {
                // if booking is from delivery then update delivery table.
                if ($drop_no == 0) {
                    $delivery_details = $booking->BookingDeliveryDetails;
                    $delivery_details->drop_latitude = $latitude;
                    $delivery_details->drop_longitude = $longitude;
                    $delivery_details->drop_location = $location;
                    $delivery_details->save();
                } else {
                    BookingDeliveryDetails::update(["drop_latitude" => $latitude, "drop_longitude" => $longitude, "drop_location" => $location])->where(["booking_id" => $booking->id, "stop_no" => $drop_no]);
                }
            }
            $merchant_id = $booking->merchant_id;
            $bookingDatata = new BookingDataController();

            $data['notification_type'] = 'DROP_CHANGED';
            $data['segment_type'] = $booking->Segment->slag;
            $data['segment_sub_group'] = $booking->Segment->sub_group_for_app; // its segment sub group for app
            $data['segment_group_id'] = $booking->Segment->segment_group_id; // for handyman
            $data['segment_data'] = [
                'booking_id' => $booking->id,
                'segment_slug' => $booking->Segment->slag
            ];

            if ($type == 1) {
                setLocal($booking->Driver->language);
                 if($booking->service_type_id == 1){
                    $units = ($booking->CountryArea->Country['distance_unit'] == 1) ? 'metric' : 'imperial';
                    $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
                    $from = $booking->pickup_latitude . "," . $booking->pickup_longitude;
                    $current_latitude = !empty($booking->Driver) ? $booking->Driver->current_latitude : '';
                    $current_longitude = !empty($booking->Driver) ? $booking->Driver->current_longitude : '';
                    $driverLatLong = $current_latitude . "," . $current_longitude;
                    $nearDriver = DistanceController::DistanceAndTime($from, $driverLatLong, $configuration->google_key, $units);
                    $estimate_driver_distance = $nearDriver['distance'];
                    $estimate_driver_time = $nearDriver['time'];
                    
                    $drop_locationArray = [
                        [
                            'drop_latitude'=> $latitude,
                            'drop_longitude'=> $longitude
                        ]
                    ];
                    
                    $to = "";
                    $lastLocation = "";
                    if (!empty($drop_locationArray)) {
                        $lastLocation = $bookingDatata->wayPoints($drop_locationArray);
                        $to = $lastLocation['last_location']['drop_latitude'] . "," . $lastLocation['last_location']['drop_longitude'];
                    }
                    $selected_map = getSelectedMap($booking->Merchant, "BOOKING_CHANGE_ADDRESS");
                    if($selected_map == "GOOGLE") {
                        $googleArray = GoogleController::GoogleStaticImageAndDistance($booking->pickup_latitude, $booking->pickup_longitude, $drop_locationArray, $configuration->google_key, $units, $string_file);
                    }
                    else{
                         $key = get_merchant_google_key($merchant_id,'api', "MAP_BOX");
                         $googleArray = MapBoxController::MapBoxStaticImageAndDistance($booking->pickup_latitude, $booking->pickup_longitude, $drop_locationArray, $key, $units,$string_file);
                     }
                     saveApiLog($booking->merchant_id, "directions" , "BOOKING_CHANGE_ADDRESS", $selected_map);
                    $time = $googleArray['total_time_text'];
                    $timeSmall = $googleArray['total_time_minutes'];
                    $distance = $googleArray['total_distance_text'];
                     $distanceSmall = $googleArray['total_distance'] / 1000;
                    $image = $googleArray['image'];
                    $bill_details = "";
                    $outstanding_amount = Outstanding::where(['user_id' => $booking->user_id,'reason' => 1,'pay_status' => 0])->sum('amount');
                    $pricecards = PriceCard::where([['status', '=', 1], ['country_area_id', '=', $booking->country_area_id], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $booking->service_type_id], ['vehicle_type_id', '=', $booking->vehicle_type_id]])->first();
                    $merchant = new MerchantHelper();
                    switch ($pricecards->pricing_type) {
                    case "1":
                    case "2":
                        $estimatePrice = new PriceController();
                        $fare = $estimatePrice->BillAmount([
                            'price_card_id' => $pricecards->id,
                            'merchant_id' => $merchant_id,
                            'distance' => $distanceSmall,
                            'time' => $timeSmall,
                            'booking_id' => 0,
                            'user_id' => $booking->user_id,
    //                        'booking_time' => date('H:i'),
                            'outstanding_amount' => $outstanding_amount,
                            'units' => $booking->CountryArea->Country['distance_unit'],
                            'from' => $from,
                            'to' => $to,
                            'total_drop_location' => $booking->total_drop_location,
                        ]);
                    // dd($distanceSmall,$timeSmall,$fare['amount']);
                        $amount = $merchant->FinalAmountCal($fare['amount'], $merchant_id);
                        $bill_details = json_encode($fare['bill_details']);
                        break;
                    case "3":
                        // @Bhuvanesh
                        // In case of Input by driver, all parameters amount will be 0, and will be calculate at the end of booking. - booking_close api.
                        $estimatePrice = new PriceController();
                        $fare = $estimatePrice->BillAmount([
                            'price_card_id' => $pricecards->id,
                            'merchant_id' => $merchant_id,
                            'distance' => $distanceSmall,
                            'time' => $timeSmall,
                            'booking_id' => 0,
                            'user_id' => $booking->user_id,
    //                        'booking_time' => date('H:i'),
                            'outstanding_amount' => $outstanding_amount,
                            'units' => $booking->CountryArea->Country['distance_unit'],
                            'from' => $from,
                            'to' => $to,
                        ]);
                        $amount = trans('api.message62');
                        $bill_details = json_encode($fare['bill_details']);
                        break;
                    }
                        
                        
                        $distance = get_calculate_distance_unit($booking->CountryArea->Country['distance_unit'], $distanceSmall, true);
                        $booking->estimate_bill = $amount;
                        $booking->estimate_time = $timeSmall;
                        $booking->estimate_distance = $distanceSmall;
                        
                        $booking->save();
                }
                $title = trans("$string_file.drop_location_changed");
                $message = trans("$string_file.user_changed_drop_location");
                $arr_param = ['driver_id' => $booking->driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => ""];
                Onesignal::DriverPushMessage($arr_param);
                setLocal();

                //to chheck the estimate distance 
                if($booking->service_type_id == 1){
                    $units = ($booking->CountryArea->Country['distance_unit'] == 1) ? 'metric' : 'imperial';
                    $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
                    $from = $booking->pickup_latitude . "," . $booking->pickup_longitude;
                    $current_latitude = !empty($booking->Driver) ? $booking->Driver->current_latitude : '';
                    $current_longitude = !empty($booking->Driver) ? $booking->Driver->current_longitude : '';
                    $driverLatLong = $current_latitude . "," . $current_longitude;
                    $nearDriver = DistanceController::DistanceAndTime($from, $driverLatLong, $configuration->google_key, $units);
                    $estimate_driver_distance = $nearDriver['distance'];
                    $estimate_driver_time = $nearDriver['time'];
                    
                    $drop_locationArray = [
                        [
                            'drop_latitude'=> $latitude,
                            'drop_longitude'=> $longitude
                        ]
                    ];
                    
                    $to = "";
                    $lastLocation = "";
                    if (!empty($drop_locationArray)) {
                        $lastLocation = $bookingDatata->wayPoints($drop_locationArray);
                        $to = $lastLocation['last_location']['drop_latitude'] . "," . $lastLocation['last_location']['drop_longitude'];
                    }
                    $selected_map = getSelectedMap($booking->Merchant, "BOOKING_CHANGE_ADDRESS");
                    if($selected_map == "GOOGLE") {
                        $googleArray = GoogleController::GoogleStaticImageAndDistance($booking->pickup_latitude, $booking->pickup_longitude, $drop_locationArray, $configuration->google_key, $units,$string_file);
                    }
                    else{
                        $key = get_merchant_google_key($merchant_id,'api', "MAP_BOX");
                        $googleArray = MapBoxController::MapBoxStaticImageAndDistance($booking->pickup_latitude, $booking->pickup_longitude, $drop_locationArray, $key, $units,$string_file);
                    }
                    saveApiLog($booking->merchant_id, "directions" , "BOOKING_CHANGE_ADDRESS", $selected_map);
                    $time = $googleArray['total_time_text'];
                    $timeSmall = $googleArray['total_time_minutes'];
                    $distance = $googleArray['total_distance_text'];
                    $distanceSmall = $googleArray['total_distance'];
                    $image = $googleArray['image'];
                    $bill_details = "";
                    $outstanding_amount = Outstanding::where(['user_id' => $booking->user_id,'reason' => 1,'pay_status' => 0])->sum('amount');
                    $pricecards = PriceCard::where([['status', '=', 1], ['country_area_id', '=', $booking->country_area_id], ['merchant_id', '=', $merchant_id], ['service_type_id', '=', $booking->service_type_id], ['vehicle_type_id', '=', $booking->vehicle_type_id]])->first();
                    $merchant = new MerchantHelper();
                    switch ($pricecards->pricing_type) {
                    case "1":
                    case "2":
                        $estimatePrice = new PriceController();
                        $fare = $estimatePrice->BillAmount([
                            'price_card_id' => $pricecards->id,
                            'merchant_id' => $merchant_id,
                            'distance' => $distanceSmall,
                            'time' => $timeSmall,
                            'booking_id' => 0,
                            'user_id' => $booking->user_id,
    //                        'booking_time' => date('H:i'),
                            'outstanding_amount' => $outstanding_amount,
                            'units' => $booking->CountryArea->Country['distance_unit'],
                            'from' => $from,
                            'to' => $to,
                            'total_drop_location' => $booking->total_drop_location,
                        ]);
                        $amount = $merchant->FinalAmountCal($fare['amount'], $merchant_id);
                        $bill_details = json_encode($fare['bill_details']);
                        break;
                    case "3":
                        // @Bhuvanesh
                        // In case of Input by driver, all parameters amount will be 0, and will be calculate at the end of booking. - booking_close api.
                        $estimatePrice = new PriceController();
                        $fare = $estimatePrice->BillAmount([
                            'price_card_id' => $pricecards->id,
                            'merchant_id' => $merchant_id,
                            'distance' => $distanceSmall,
                            'time' => $timeSmall,
                            'booking_id' => 0,
                            'user_id' => $booking->user_id,
    //                        'booking_time' => date('H:i'),
                            'outstanding_amount' => $outstanding_amount,
                            'units' => $booking->CountryArea->Country['distance_unit'],
                            'from' => $from,
                            'to' => $to,
                        ]);
                        $amount = trans('api.message62');
                        $bill_details = json_encode($fare['bill_details']);
                        break;
                    }
                        
                        
                        $distance = get_calculate_distance_unit($booking->CountryArea->Country['distance_unit'], $distanceSmall, true);
                        
                        $booking->estimate_bill = $amount;
                        $booking->save();
                }
            } else {
                setLocal($booking->User->language);
                $title = trans("$string_file.drop_location_changed");
                $message = trans("$string_file.driver_changed_drop_location");
                $arr_param['data'] = $data;
                $arr_param['user_id'] = $booking->user_id;
                $arr_param['message'] = $message;
                $arr_param['merchant_id'] = $merchant_id;
                $arr_param['title'] = $title; // notification title
                $arr_param['large_icon'] = "";
                Onesignal::UserPushMessage($arr_param);
                setLocal();
            }
            $booking = $bookingDatata->DriverBookingDetails($booking_id, $merchant_id);
            return $this->successResponse($message, $booking);
        } else {
            return $this->failedResponse(trans("$string_file.drop_location_out"));
        }
    }

    public function DriverRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1005);
                }),
            ],
            'rating' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $rating = BookingRating::updateOrCreate(
            ['booking_id' => $request->booking_id],
            [
                'driver_rating_points' => $request->rating,
                'driver_comment' => $request->comment
            ]
        );
        $booking = Booking::where([['id', '=', $request->booking_id]])->first();
        $user_id = $booking->user_id;
        $avg = BookingRating::whereHas('Booking', function ($q) use ($user_id) {
            $q->where('user_id', $user_id);
        })->avg('driver_rating_points');
        $user = User::find($user_id);
        $user->rating = round($avg, 2);
        $user->save();
        $string_file = $this->getStringFile(null, $user->Merchant);
        return response()->json(['result' => "1", 'message' => trans("$string_file.rating_thanks"), 'data' => $rating]);
    }

    // booking rating by user to driver
    public function bookingRating(Request $request)
    {
        DB::beginTransaction();
        try {
            $booking_id = $request->id;
            $booking = Booking::select('id', 'driver_id', 'booking_status', 'vehicle_type_id')->find($booking_id);
            if ($booking->booking_status != 1005) {
                // will change this error as required
                throw new Exception(trans("error"));
            }
            $rating = BookingRating::updateOrCreate(
                ['booking_id' => $booking_id],
                [
                    'user_rating_points' => $request->rating,
                    'user_comment' => $request->comment,
                    'driver_vehicle_rating_points' => $request->vehicle_rating,
                    'driver_vehicle_comment' => $request->vehicle_comment,
                ]
            );
            $driver_id = $booking->driver_id;
            $avg = BookingRating::whereHas('Booking', function ($q) use ($driver_id) {
                $q->where('driver_id', $driver_id);
            })->avg('user_rating_points');
            $driver = Driver::select('id', 'rating')->find($driver_id);
            $driver->rating = round($avg, 2);
            $driver->save();

            $vehicle_type_id = $booking->vehicle_type_id;
            $avg_vehicle_rating = BookingRating::whereHas('Booking', function ($q) use ($driver_id, $vehicle_type_id) {
                $q->where([['driver_id', $driver_id], ['vehicle_type_id', '=', $vehicle_type_id]]);
            })->avg('driver_vehicle_rating_points');
            $vehicle_type = $booking->VehicleType;
            $vehicle_type->rating = round($avg_vehicle_rating, 2);
            $vehicle_type->save();
        } catch (Exception $e) {
            DB::rollBack();
        }
        DB::commit();
        return ['booking_order_id' => $booking_id];
    }

    public function UserReceipt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1005);
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $pay_later_enable = false;
        $pay_later_text = false;
        $booking = Booking::with('BookingDetail', 'Driver', 'PriceCard')->find($request->booking_id);
        $booking_delivery_details = $booking->BookingDeliveryDetails;
        $string_file = $this->getStringFile(NULL, $booking->Merchant);

        $config = ApplicationConfiguration::where('merchant_id', $booking->merchant_id)->first();

        $show_status = false;
        $tip_status = false;
        if ($config->tip_status == 1) {
             $show_status = true;
            // if (!empty($booking->BookingDetail)) {

            //     if ($booking->BookingDetail->tip_amount > 0) {
            //         $show_status = false;
            //     }
            // }
            $tip_status = true;
        }

        $configuration = $booking->Merchant->Configuration;
        $out_standing_amount = Outstanding::where(['booking_id' => $booking->id, 'user_id' => $booking->user_id, 'reason' => 2, 'pay_status' => 0])->sum('amount');
        //        if (isset($config->user_outstanding_enable) && $config->user_outstanding_enable && $out_standing_amount > 0) {
        if (
            isset($configuration->user_outstanding_enable) && $configuration->user_outstanding_enable &&
            $booking->payment_status != 1
        ) {
            $pay_later_enable = true;
            $pay_later_text = trans("$string_file.pay_later_note");
        }
        $currency = $booking->CountryArea->Country->isoCode;
        $booking_closure = $booking->booking_closure;
        $appConfig = ApplicationConfiguration::select('favourite_driver_module', 'vehicle_rating_enable')->where([['merchant_id', '=', $booking->merchant_id]])->first();
        $payment_options = [];
        $rating_visibility = $booking->Merchant->ApplicationConfiguration->user_rating_enable == 1 ? true : false;
        if ($booking->PriceCard->pricing_type == 3 && $booking_closure != 1) {
            $fav_visibility = false;
            $bottom_button_color = 'f1f1f1';
            $text_color = '333333';
            $text = ""; //trans('api.message150');
            $action = "NO";
        } else {
            $rating_visibility = $booking->payment_status == 1 && $booking->Merchant->ApplicationConfiguration->user_rating_enable == 1 ? true : false;
            $text = $booking->payment_status == 1 ? trans("$string_file.complete") : trans("$string_file.select_payment_method");
            $action = $booking->payment_status == 1 ? "FINISH" : "SELECT_PAYMENT";
            if ($booking->payment_status != 1) {
                if ($booking->payment_method_id == 4 && $booking->BookingDetail->payment_failure != 2) {
                    $text = trans("$string_file.make_payment");
                    $action = "MAKE_PAYMENT";
                } elseif ($booking->payment_method_id == 2 && $booking->BookingDetail->payment_failure != 2) {
                    $payment_option_slug = $booking->UserCard->PaymentOption->slug;
                    if ($payment_option_slug == 'PayGate') {
                        $text = trans("$string_file.make_payment");
                        $action = "MAKE_PAYMENT";
                    }
                } else {
                    // @Bhuvanesh
                    // Where condition for cash payment & wallet method only
                    $paymentMethods = $booking->CountryArea->PaymentMethod->whereIn('id', [1, 3]);
                    $bookingData = new BookingDataController();
                    $payment_option = $bookingData->PaymentOption($paymentMethods, $request->user('api')->id, $currency, $booking->CountryArea->minimum_wallet_amount, $booking->final_amount_paid);

                    foreach ($payment_option as $val) {
                        // cash and wallet can be select again if payment was was failed with the same method
                        //                        might  user has done recharged after payment failed
                        if (($val['id'] != $booking->payment_method_id) || in_array($val['id'], [1, 3])) {
                            $payment_options[] = $val;
                        }
                    }
                }
            }
            $fav_visibility = $appConfig->favourite_driver_module == 1 ? true : false;
            $bottom_button_color = '333333';
            $text_color = 'ffffff';
        }
        if (!empty($booking->BookingDetail->bill_details)) {
            $price = json_decode($booking->BookingDetail->bill_details);
//            $price = json_decode($booking->bill_details);
            $holder = HolderController::PriceDetailHolder($price, $booking->id);
        } else {
            $holder = [];
        }

        $user = $request->user('api');
        $already_added = false;
        $user_favourite_drivers = $user->FavouriteDriver;
        if ($user_favourite_drivers->isNotEmpty()) :
            $user_favourite_drivers = array_pluck($user_favourite_drivers->toArray(), 'driver_id');
            $already_added = (in_array($booking->driver_id, $user_favourite_drivers)) ? true : false;
        endif;

        $holder_driver_favourite = array(
            'visibility' => $fav_visibility,
            'driver_data' => array(
                "driver_id" => $booking->driver_id,
                'already_added' => $already_added,
                "booking_id" => $request->booking_id,
                "text" => isset($booking->Driver->fullName) ? $booking->Driver->fullName : '',
                "image" => isset($booking->Driver->profile_image) ? $booking->Driver->profile_image : ''
            )
        );
        $holder_driver_vehicle_rating = array(
            'visibility' => $appConfig->vehicle_rating_enable == 1 ? $rating_visibility : false,
            'vehicle_data' => array(
                "booking_id" => $request->booking_id,
                "text" => !empty($booking->DriverVehicle) ? $booking->DriverVehicle->VehicleType->VehicleTypeName . '( ' . $booking->DriverVehicle->vehicle_color . ' )' : "",
                "image" => get_image($booking->VehicleType->vehicleTypeImage, 'vehicle', $booking->merchant_id, true, false),
            )
        );

        // multiple drop location arr
        $multi_destination = $booking->Merchant->BookingConfiguration->multi_destination == 1 ? true : false;
        $arr_mid_ride_location = [];
        if ($multi_destination) {
            $drop_location = $booking->waypoints;
            $multiple_location = json_decode($drop_location, true);
            if (!empty($multiple_location)) {
                foreach ($multiple_location as $location) {
                    // currently end location value doesn't exist and to get that we have to run google api
                    $arr_mid_ride_location[] = [
                        'address' => $location['drop_location'],
                    ];
                }
            }
        }
        $merchant_obj = new \App\Http\Controllers\Helper\Merchant();
        $payment_amount = $merchant_obj->FinalAmountCal($booking->final_amount_paid, $booking->merchant_id);
        // $payment_amount = $payment_amount == "" ? trans("$string_file.details") : $payment_amount;
        $payment_amount = $payment_amount == "" ? trans("$string_file.details") : $merchant_obj->PriceFormat($payment_amount, $booking->merchant_id);
        $service_text = ($booking->service_type_id) ? trans("$string_file.service_type") . ' : ' . $booking->ServiceType->serviceName : trans("$string_file.delivery_type") . $booking->deliveryType->name;
        $result = array(
            'holder_ride_info' => array(
                'circular_image' => get_image($booking->VehicleType->vehicleTypeImage, 'vehicle', $booking->merchant_id, true, false),
                'circular_image_visibility' => true,
                'circular_text_one' => $service_text,
                'circular_text' => $booking->VehicleType->VehicleTypeName,
                "circular_text_style" => "",
                "circular_text_color" => "333333",
                "circular_text_visibility" => true,
                "value_text" => $currency . " " . $payment_amount,
                "value_text_style" => "BOLD",
                "value_text_color" => "2ecc71",
                "value_text_visibility" => true,
                "left_text" => $booking->travel_distance,
                "left_text_style" => "BOLD",
                "left_text_color" => "333333",
                "left_text_visibility" => ($booking->merchant_id == 976) ? false : true,
                "right_text" => $booking->travel_time,
                "right_text_style" => "BOLD",
                "right_text_color" => "333333",
                "right_text_visibility" => ($booking->merchant_id == 976) ? false : true,
                "pick_locaion" => $booking->BookingDetail->start_location,
                "pick_location_visibility" => true,
                "drop_location" => $booking->BookingDetail->end_location,
                "drop_location_visibility" => true,
                'static_values' => $holder,
                'multiple_drop_location' => $arr_mid_ride_location,
                'card_id' => $booking->card_id,
            ),
            'holder_driver_rating' => array(
                'visibility' => $rating_visibility,
                'driver_data' => array(
                    "booking_id" => $request->booking_id,
                    "text" => $booking->Driver->first_name . " " . $booking->Driver->last_name,
                    "image" => $booking->Driver->profile_image
                )
            ),
            'holder_driver_favourite' => $holder_driver_favourite,
            'holder_bottom_button' => array(
                'bottom_button_color' => $bottom_button_color,
                'text_color' => $text_color,
                'text' => $text,
                'action' => $action,
                'payment_method_id' => $booking->payment_method_id,
                'payment_data' => $payment_options
            ),
            'holder_driver_vehicle_rating' => $holder_driver_vehicle_rating,
            'pay_later_enable' => $pay_later_enable,
            'pay_later_text' => $pay_later_text,
            'ride_tip' => [
                'visibility' => $show_status,
                'data' => [
                    'bottom_button_color' => $bottom_button_color,
                    'text_color' => $text_color,
                    'text' => trans("$string_file.tip"),
                    'action' => "TIP_BUTTON",
                    'payment_method_id' => $booking->payment_method_id,
                ]
            ],
            "proof_of_delivery"=>[
                 // "product_image"=> !empty($booking_delivery_details) ?  !empty($booking_delivery_details->receiver_image) ? get_image($booking_delivery_details->receiver_image,'booking_images', $booking->merchant_id): ""  : "",
                 "product_image"=> !empty($booking_delivery_details) ?  !empty($booking_delivery_details->receiver_image) ? get_image($booking_delivery_details->receiver_image,'booking_images', $booking->merchant_id): ""  : "",
                 "upload_delivery_image"=> !empty($booking_delivery_details) ?  !empty($booking_delivery_details->upload_delivery_image) ? get_image($booking_delivery_details->upload_delivery_image,'booking_images', $booking->merchant_id): ""  : "",
                'receiver_name'=> !empty($booking_delivery_details) ?  !empty($booking_delivery_details->receiver_name)  ? $booking_delivery_details->receiver_name: "" : "",
                "receiver_phone"=> !empty($booking_delivery_details) ?  !empty($booking_delivery_details->receiver_phone)  ? $booking_delivery_details->receiver_phone: "" : "",
            ],
        );
        return $this->successResponse(trans("$string_file.receipt"), $result);
    }

    public function driverReceipt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1005);
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            //            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $booking_data = new BookingDataController;
        $return_data = $booking_data->bookingReceiptForDriver($request);
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        return $this->successResponse(trans("$string_file.receipt"), $return_data);
    }

    public function cancelBookingByUSer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->wherein('booking_status', [1000, 1001, 1002, 1012, 1003, 1004, 1006]);
                }),
            ],
            'cancel_reason_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            //            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        DB::beginTransaction();
        try {
            $cancel_charges = $request->cancel_charges;
            $bookingData = new BookingDataController();
            $booking_id = $request->booking_id;
            $booking = Booking::with('Driver')->find($booking_id);
            $merchant_data = Merchant::find($booking->merchant_id);
            $string_file = $this->getStringFile(null, $booking->Merchant);
            $apply_charges = true;
            // $config = Configuration::where('merchant_id', $booking->merchant_id)->first();
            $free_km_for_cancel_charges = isset($merchant_data['free_distance_for_cancel_charges']) ? $merchant_data['free_distance_for_cancel_charges'] : 0;
            if (isset($merchant_data['cancel_charges_according_to_distance']) && $merchant_data['cancel_charges_according_to_distance'] == 1 && $booking->driver_id != '') {
                $bookingDetails = $booking->BookingDetail;
                $accept_lat = $bookingDetails->accept_latitude;
                $accept_long = $bookingDetails->accept_longitude;
                $driver_current_lat = $booking->Driver->current_latitude;
                $driver_current_long = $booking->Driver->current_longitude;
                $newDistance = new DistanceController();
                $driver_away_from_arrive = $newDistance->AerialDistanceBetweenTwoPoints($accept_lat, $accept_long, $driver_current_lat, $driver_current_long);
                if ($driver_away_from_arrive > $free_km_for_cancel_charges) {
                    $apply_charges = true;
                } else {
                    $apply_charges = false;
                }
            }
            // if($config->cash_payment_method_option == '1' && $booking->booking_status == '1003' && $user->cash_method_avaliable == 'YES' && $booking->payment_method_id == 1){
            //     $user = $request->user('api-user');
            //     $usre->disable_ride_count_value = $user->disable_ride_count_value+1;
            //     $user->save();
            //     if($config->disable_ride_count_value == $user->disable_ride_count_value){
            //         $user->cash_method_avaliable = 'NO';
            //         $user->enable_ride_count_value = $config->enable_ride_count_value;
            //         $user->save();
            //     }
            // }
            if (!empty($cancel_charges) && $cancel_charges > 0 && $apply_charges == true) {
                if ($merchant_data['cancel_outstanding'] == 1) :
                    $merchant = new \App\Http\Controllers\Helper\Merchant();
                    $payment = new CancelPayment();
                    $cancel_charges_received = $payment->MakePayment($booking, $booking->payment_method_id, $cancel_charges, $booking->user_id, $booking->card_id, $merchant_data->cancel_outstanding, $booking->driver_id);

                    // company earning will deduct from driver account
                    $commission_data = CommonController::NewCommission($booking_id, $cancel_charges);
                    $booking_transaction_submit = BookingTransaction::updateOrCreate([
                        'booking_id' => $booking_id,
                    ], [
                        'date_time_details' => date('Y-m-d H:i:s'),
                        'sub_total_before_discount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                        'surge_amount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                        'extra_charges' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                        'discount_amount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                        'tax_amount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                        'tip' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                        'insurance_amount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                        'cancellation_charge_received' => $merchant->TripCalculation(isset($cancel_charges_received) ? $cancel_charges_received : '0.0', $booking->merchant_id),
                        'cancellation_charge_applied' => $merchant->TripCalculation($cancel_charges, $booking->merchant_id),
                        'toll_amount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                        'cash_payment' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                        'online_payment' => $merchant->TripCalculation(isset($cancel_charges_received) ? $cancel_charges_received : '0.0', $booking->merchant_id),
                        'customer_paid_amount' => $merchant->TripCalculation(isset($cancel_charges_received) ? $cancel_charges_received : '0.0', $booking->merchant_id),
                        'company_earning' => $merchant->TripCalculation($commission_data['company_cut'], $booking->merchant_id),
                        'driver_earning' => $merchant->TripCalculation($commission_data['driver_cut'], $booking->merchant_id),
                        'amount_deducted_from_driver_wallet' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                        'driver_total_payout_amount' => $merchant->TripCalculation($commission_data['driver_cut'], $booking->merchant_id),
                        'trip_outstanding_amount' => $merchant->TripCalculation(($cancel_charges - (isset($cancel_charges_received) ? $cancel_charges_received : 0)), $booking->merchant_id),
                        'merchant_id' => $booking->merchant_id
                    ]);
                    if (!empty($booking->driver_id)) {
                        $paramArray = array(
                            'merchant_id' => $booking->merchant_id,
                            'driver_id' => $booking->driver_id,
                            'booking_id' => $booking->id,
                            'amount' => $cancel_charges,
                            'narration' => 11,
                            'platform' => 1,
                            'payment_method' => 2,
                        );
                        WalletTransaction::WalletCredit($paramArray);
                        //                    \App\Http\Controllers\Helper\CommonController::WalletCredit($booking->driver_id,$booking->id,$cancel_charges,11,1,2);
                    }
                endif;
            }
            $booking->booking_status = 1006;
            $booking->cancellation_charges_applied_after_assigned = 1;
            $booking->booking_closure = 1;
            $booking->final_amount_paid = sprintf("%0.2f", $cancel_charges);
            $booking->cancel_reason_id = $request->cancel_reason_id;
            $booking->save();
            // inset booking status history
            $this->saveBookingStatusHistory($request, $booking, $booking->id);

            //payment option is payu then void the authorisation
            if ($booking->payment_method_id == 2) {
                $user_card = UserCard::find($booking->card_id);
                if ($user_card->PaymentOption->slug == "PAYU") {
                    $locale = $request->header('locale');
                    $this->payuVoid($booking, $locale);
                }
            }

            if ($booking->service_type_id == 5 && $booking->driver_id != '') {
                $poolBooking = new PoolController();
                $poolBooking->CancelRide($booking, $request);
            }
            // clear the call masking session
            $config = Configuration::where('merchant_id', $booking->merchant_id)->first();
            if (isset($config->twilio_call_masking) && $config->twilio_call_masking == 1) {
                TwilioMaskingHelper::close_session($booking);
            }
            if (!empty($booking->Driver)) {
                $booking->Driver->free_busy = 2;
                $booking->Driver->save();

                //            $data = $bookingData->BookingNotification($booking);
                //            $notification_data['notification_type'] ="CANCEL_BOOKING";
                //            $notification_data['segment_type'] = $data['segment_type'];
                //            $notification_data['segment_data']= $data;
                //            $large_icon = $this->getNotificationLargeIconForBooking($booking);
                //            $arr_param = ['driver_id'=>$booking->driver_id,'data'=>$notification_data,'message'=>$message,'merchant_id'=>$booking->merchant_id,'title'=>$title,'large_icon'=>$large_icon];
                //            Onesignal::DriverPushMessage($arr_param);
                $booking_data_obj = new BookingDataController;
                $booking_data_obj->SendNotificationToDrivers($booking);
            }
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->failedResponse($exception->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.ride_cancelled"), $booking);
    }

    public function bookingAcceptReject(Request $request)
    {
        $driver = $request->user('api-driver');
        $driver_id = $driver->id;
        $booking_id = $request->id;
        $merchant_id = $driver->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $validator = Validator::make($request->all(), [
            'id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where(function ($q) {
                        $q->whereIn('booking_status', array(1000, 1001, 1012))->where('service_type_id', '!=', 5)->whereNull("is_fake_booking");
                    })->orWhere(function ($qq) {
                        // case when pool ride
                        $qq->whereIn('booking_status', array(1000, 1001, 1002))->where('service_type_id', '=', 5)->whereNull("is_fake_booking");
                    });
                }),
                Rule::exists('booking_request_drivers', 'booking_id')->where(function ($query) use ($driver_id, $booking_id) {
                    $query->where('driver_id', $driver_id);
                }),
            ],
        ], [
            'exists' => trans("$string_file.already_accepted"),
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new Exception($errors[0]);
        }

        DB::beginTransaction();
        try {
            $accepted_driver_count = BookingRequestDriver::where([['booking_id', '=', $booking_id], ['request_status', '=', 2]])->lockForUpdate()->count();
            if ($accepted_driver_count > 0) {
                throw new Exception(trans("$string_file.ride_already"));
            }
            //            $booking_obj = new Booking;
            //            $booking = $booking_obj->getBooking($booking_id);


            $booking = Booking::select('id', 'is_in_drive', 'offer_amount', 'user_id', 'final_amount_paid', 'family_member_id', 'booking_status_history', 'estimate_driver_distance', 'estimate_distance', 'travel_distance', 'merchant_booking_id', 'booking_status', 'vehicle_type_id', 'driver_vehicle_id', 'price_card_id', 'ride_otp', 'ride_otp_verify', 'total_drop_location', 'booking_type', 'ploy_points', 'payment_method_id', 'vehicle_type_id', 'driver_id', 'merchant_id', 'segment_id', 'pickup_location', 'country_area_id', 'driver_id', 'user_id', 'pickup_latitude', 'pickup_longitude', 'service_type_id', 'additional_notes', 'family_member_id', 'waypoints', 'drop_latitude', 'drop_longitude', 'drop_location', 'payment_status', 'estimate_bill', 'travel_time', 'country_area_id', 'booking_closure', 'later_booking_date', 'later_booking_time', 'unique_id', 'map_image', 'platform', 'user_masked_number', 'driver_masked_number', 'number_of_rider', 'is_pass_ride', 'pass_booking_id')
                ->with(['User' => function ($query) {
                    $query->select('id', 'country_id', 'merchant_id', 'first_name', 'last_name', 'UserPhone', 'email', 'rating', 'UserProfileImage', 'rating');
                }])
                ->with(['Driver' => function ($query) {
                    $query->select('id', 'first_name', 'current_latitude', 'country_area_id', 'current_longitude', 'last_location_update_time', 'driver_gender', 'last_name', 'email', 'phoneNumber', 'profile_image', 'rating', 'ats_id');
                }])
                ->with(['PaymentMethod' => function ($query) {
                    $query->select('id', 'payment_method', 'payment_icon');
                }])
                ->with(['VehicleType' => function ($query) {
                    $query->select('id', 'vehicleTypeImage', 'rating');
                }])
                ->with(['ServiceType' => function ($query) {
                    $query->select('id', 'serviceName', 'type');
                }])
                ->orderBy('created_at', 'DESC')
                ->lockForUpdate()->find($booking_id);

            // status == 1 means accept request
            if ($request->status == "ACCEPT") {
                if($driver->free_busy == 1 && ($driver->Merchant->BookingConfiguration->multiple_rides == 2 || empty($driver->Merchant->BookingConfiguration->multiple_rides)) ){
                    throw new Exception(trans("$string_file.existing_ride_order_error"));
                }
                // delete users checkout at accept screen
                BookingCheckout::where([['merchant_id', '=', $booking->merchant_id], [
                    'user_id', '=',
                    $booking->user_id
                ], [
                    'country_area_id', '=',
                    $booking->country_area_id
                ]])->delete();
                $driver->free_busy = ($driver->Merchant->BookingConfiguration->multiple_delivery_ride_screen == 1 && $booking->segment_id == 2)? 2: 1;
                $driver->save();
                $ployline = "";
                $booking->ride_otp_verify = 2;
                $message_otp = '';
                if ($booking->Merchant->BookingConfiguration->ride_otp == 1) :
                    $booking->ride_otp = rand(1000, 9999);
                    $booking->ride_otp_verify = 1;
                    // send otp on whatsapp message
                    if ($booking->platform == 3) {
                        $message_otp = '. ' . trans("$string_file.ride_start_otp") . ' ' . trans("$string_file.id") . ' #' . $booking->merchant_booking_id . ': ' . $booking->ride_otp;
                    }
                endif;
                $booking_status = $booking->booking_status;
                // If booking accept by taxi company driver
                if ($driver->taxi_company_id != NULL && $booking->Merchant->Configuration->company_admin == 1) {
                    $booking->taxi_company_id = $driver->taxi_company_id;
                }
                if ($booking->booking_status == 1000 && $booking->is_in_drive == 1 && !empty($booking->offer_amount)) {
                    $booking->price_for_ride_amount = $booking->offer_amount;
                    $booking->price_for_ride = 2;
                }
                $booking->booking_status = 1002;
                $booking->driver_vehicle_id = $request->driver_vehicle_id; // coming from store online config
                $booking->driver_id = $driver_id;
                $booking->unique_id = uniqid();
                $booking->ploy_points = $ployline;
                $booking->save();
                $merchant_id = $booking->merchant_id;

                // ride accepted driver's player id
                // inset booking status history
                $this->saveBookingStatusHistory($request, $booking, $booking->id);

                if ($booking->service_type_id == 5) {
                    $poolBooking = new PoolController();
                    $poolBooking->AcceptRide($booking, $request);
                }
//                BookingRequestDriver::where([['booking_id', '=', $booking_id], ['driver_id', '=', $driver_id]])->update(['request_status' => 2]);
                $existing = BookingRequestDriver::where('booking_id', $booking_id)
                    ->where('request_status', 2)
                    ->lockForUpdate()
                    ->first();

                if (!$existing) {
                    BookingRequestDriver::where('booking_id', $booking_id)
                        ->where('driver_id', $driver_id)
                        ->update(['request_status' => 2]);
                }

                $cancelDriver = BookingRequestDriver::with(['Driver' => function ($q) use ($driver) {
                    return $q->select("*", "id as driver_id")->where('player_id', '!=', $driver->player_id);
                }])
                    ->whereHas('Driver', function ($q) use ($driver) {
                        return $q->where('player_id', '!=', $driver->player_id);
                    })->where([['booking_id', '=', $booking_id], ['request_status', '=', 1]])->get();
                $cancelDriver = array_pluck($cancelDriver, 'Driver');
                $bookingData = new BookingDataController();

                foreach ($cancelDriver as $key => $value) {
                    $newDriver = Driver::select('id', 'last_ride_request_timestamp')->find($value->id);
                    $newDriver->last_ride_request_timestamp = date("Y-m-d H:i:s", time() - 100);
                    $newDriver->save();
                }
                //                $user_id = $booking->user_id;
                $pickup = [
                    [
                        'drop_latitude' => $booking->pickup_latitude,
                        'drop_longitude' => $booking->pickup_longitude,
                    ]
                ];
                $selected_map = getSelectedMap($driver->Merchant, "BOOKING_ACCEPT_REJECT");
                if($selected_map == "GOOGLE"){
                    $key = get_merchant_google_key($merchant_id,'api');
                    $google_array = GoogleController::GoogleStaticImageAndDistance($request->latitude, $request->longitude, $pickup, $key, 'metric', $string_file);
                }
                else{
                    $key = get_merchant_google_key($merchant_id,'api', "MAP_BOX");
                    $google_array = MapBoxController::MapBoxStaticImageAndDistance($request->latitude, $request->longitude, $pickup, $key,"metric",$string_file);
                }
                saveApiLog($merchant_id, "directions" , "BOOKING_ACCEPT_REJECT", $selected_map);
                BookingDetail::create([
                    'booking_id' => $booking_id,
                    'accept_timestamp' => strtotime('now'),
                    'accept_latitude' => $request->latitude,
                    'accept_longitude' => $request->longitude,
                    'accuracy_at_accept' => $request->accuracy,
                    'dead_milage_distance' => isset($google_array['total_distance']) ? $google_array['total_distance']/1000 : 0,
                    'on_accept_google_response' => isset($google_array) ? json_encode($google_array) : null,
                ]);

                // If ride is passing ride accepted, then send notification to old driver to complete that ride automatically.
                if (isset($booking->is_pass_ride) && $booking->is_pass_ride == 1 && !empty($booking->pass_booking_id)) {
                    $masterBooking = Booking::findOrFail($booking->pass_booking_id);

                    $large_icon = '';
                    $data = array(
                        'notification_type' => "PASS_RIDE_ACCEPTED",
                        'segment_type' => "TAXI",
                        'segment_data' => time(),
                        'notification_gen_time' => time(),
                    );
                    $title = trans("$string_file.pass_ride_accepted");
                    $arr_param = ['driver_id' => $masterBooking->driver_id, 'data' => $data, 'message' => $title, 'merchant_id' => $masterBooking->merchant_id, 'title' => $title, 'large_icon' => $large_icon];
                    Onesignal::DriverPushMessage($arr_param);
                }


                // set master id for pool ride
                $pool_booking = null;
                if ($booking->segment_id == 1 && $booking->service_type_id == 5) {
                    $pool_booking = Booking::select('id', 'user_id', 'driver_id', 'merchant_booking_id', 'master_booking_id')
                        ->orderBy('created_at')
                        ->where([['id', '!=', $booking_id], ['driver_id', '=', $driver->id]])
                        ->whereIn('booking_status', [1002, 1003, 1004])
                        ->first();
                }
                $booking->master_booking_id = $pool_booking && $pool_booking->master_booking_id ? $pool_booking->master_booking_id : $booking->merchant_booking_id;
                $booking->save();

                DB::commit();

                if (!empty($cancelDriver)) {
                    $bookingData->SendNotificationToDrivers($booking, $cancelDriver);
                }

                //                $message = $booking_status == 1001 ? $bookingData->LanguageData($booking->merchant_id, 27) : $bookingData->LanguageData($booking->merchant_id, 28);
                $bookingData = new BookingDataController();
                $notification_data = $bookingData->bookingNotificationForUser($booking, "ACCEPT_BOOKING", $message_otp);
                //                Onesignal::UserPushMessage($user_id, $notification_data, $message, 1, $booking->merchant_id);

                // mask the number
                $config = Configuration::where('merchant_id', $booking->merchant_id)->first();
                if (isset($config->twilio_call_masking) && $config->twilio_call_masking == 1) {
                    $expiry_in_seconds = 60 * 60 * 6; // half day
                    $booking = TwilioMaskingHelper::mask_numbers($booking, $expiry_in_seconds, $driver_id);
                }
                if(isset($config->whatsapp_notification) && $config->whatsapp_notification == 1){
                    SendWhatsappNotificationEvent::dispatch($booking->merchant_id, 1002, $booking);
                }
                $booking = $bookingData->driverBookingDetails($booking_id, true, $request);
                $SmsConfiguration = SmsConfiguration::select('ride_accept_enable')->where([['merchant_id', '=', $merchant_id]])->first();
                if (!empty($SmsConfiguration) && $SmsConfiguration->ride_accept_enable == 2) {
                    $sms = new SmsController();
                    $phone = $booking->User->UserPhone;
                    $sms->SendSms($merchant_id, $phone, null, 'RIDE_ACCEPT', $booking->User->email);
                }
                $message = trans("$string_file.accepted");
            } elseif ($request->status == "REJECT") {
                // status 2 means reject the booking
                BookingRequestDriver::where([['booking_id', '=', $booking_id], ['driver_id', '=', $driver_id]])->update(['request_status' => 3]);
                $driver->last_ride_request_timestamp = date("Y-m-d H:i:s", time() - 100);
                $driver->save();

                $limit = getSendDriverRequestLimit($booking);
                $bookingData = new BookingDataController();
                if ($limit == 1) {
                    $bookingData->sendRequestToNextDrivers($booking_id, 1, 'auto_upgrade');
                } elseif ($limit > 1) {
                    $bookingRequestDriversCount = BookingRequestDriver::where('booking_id', $booking_id)->count();
                    $noActionAndRejectDriversCount = BookingRequestDriver::where([['booking_id', $booking_id], ['request_status', 3]])->count();
                    if ($bookingRequestDriversCount == $noActionAndRejectDriversCount) {
                        $bookingData->sendRequestToNextDrivers($booking_id, 1, 'auto_upgrade');
                    }
                }
                DB::commit();

                $message = trans("$string_file.ride_rejected");
                $booking = [];
            }
            BookingRequestDriver::where([['booking_id', '=', $booking_id], ['driver_id', '=', $driver_id]])->update(['inside_function' => NULL]);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        return ['message' => $message, 'data' => $booking];
    }

    // partial booking
    public function acceptUpcomingBooking(Request $request)
    {
        $driver = $request->user('api-driver');
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required', 'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->whereIn('booking_status', array(1000, 1001))->where([['booking_type', '=', 2]]);
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $booking_id = $request->booking_id;
            $booking = Booking::select('id', 'is_in_drive', 'offer_amount', 'estimate_time', 'booking_status_history', 'later_booking_date', 'later_booking_time', 'merchant_booking_id', 'payment_method_id', 'vehicle_type_id', 'driver_id', 'merchant_id', 'segment_id', 'country_area_id', 'driver_id', 'user_id', 'service_package_id', 'service_type_id', 'is_geofence', 'base_area_id', 'auto_upgradetion', 'number_of_rider', 'total_drop_location', 'price_card_id', 'driver_vehicle_id', 'family_member_id', 'pickup_latitude', 'pickup_longitude', 'pickup_location', 'drop_latitude', 'drop_longitude', 'drop_location', 'booking_type', 'estimate_bill', 'additional_information', 'additional_notes', 'price_for_ride_amount', 'price_for_ride', 'booking_status')->find($booking_id);
            $string_file = $this->getStringFile($booking->merchant_id);
            $booking_later_booking_date_time = $booking->later_booking_date . " " . $booking->later_booking_time;
            $current_DateTime = convertTimeToUSERzone(date('Y-m-d H:i'), $booking->CountryArea->timezone, null, $booking->Merchant);

            //            if ($booking->later_booking_date <= date('Y-m-d') && $booking->later_booking_time <= date('H:i')) {
            if (strtotime($booking_later_booking_date_time) <= strtotime($current_DateTime)) {
                $booking_status = "1016";
                $booking->save();
                // inset booking status history
                $this->saveBookingStatusHistory($request, $booking, $booking->id);
                $data = array('booking_id' => $booking->id, 'booking_status' => $booking_status);
                return $this->failedResponse(trans("$string_file.ride_expired"), $data);
            }
            $driverPartial = Booking::where([['driver_id', '=', $driver->id], ['booking_status', '=', 1012]])->get();
            $time = $booking->Merchant->BookingConfiguration->partial_accept_hours;
            $partial_accept_before_minutes = $booking->Merchant->BookingConfiguration->partial_accept_before_hours;
            $bookingTimeString = $booking->later_booking_date . " " . $booking->later_booking_time;

            // convert to date object
            $bookingTime = new DateTime($bookingTimeString);
            $endbookingTime = new DateTime($bookingTimeString);

            // check for partial accept before hours
            //            $current_DateTime = new \DateTime();
            //            $minutes = (strtotime($bookingTime->format("Y-m-d H:i")) - strtotime($current_DateTime->format("Y-m-d H:i"))) / 60;

            // current time according to time zone
            $current_DateTime = convertTimeToUSERzone(date('Y-m-d H:i'), $booking->CountryArea->timezone, null, $booking->Merchant);
            $minutes = (strtotime($bookingTime->format("Y-m-d H:i")) - strtotime($current_DateTime)) / 60;
            if ($booking->Merchant->BookingConfiguration->ride_later_ride_allocation != 2 && $minutes > $partial_accept_before_minutes) {
                $config_time = date(getDateTimeFormat(2), strtotime('-' . $partial_accept_before_minutes . 'minutes', strtotime($bookingTime->format("Y-m-d H:i:s"))));
                $message = trans("$string_file.ride_later_accept_warning") . $config_time;
                return $this->failedResponse($message);
            }

            // add estimate time + time difference between ride
            $endbookingTime->modify("+{$booking->estimate_time}");
            $endbookingTime->modify("+{$time} mins");

            // convert to time string
            $bookingTime = $bookingTime->format("Y-m-d H:i");
            $endbookingTime = $endbookingTime->format("Y-m-d H:i");

            foreach ($driverPartial as $value) {
                $bookingtimestamp = $value->later_booking_date . " " . $value->later_booking_time;

                // Accepted ride time to date object
                $DateTime = new DateTime($bookingtimestamp);
                $oldDateTime = new DateTime($bookingtimestamp);
                if ($value->estimate_time) {
                    // add estimate time to date object
                    $oldDateTime->modify("+{$value->estimate_time}");
                }

                // add time difference between ride
                $oldDateTime->modify("+{$time} mins");
                // convert to time string
                $newDate = $DateTime->format("Y-m-d H:i");
                $oldDate = $oldDateTime->format("Y-m-d H:i");

                // Condition check for active ride or booking ride time and date conflicts
                if ($bookingTime >= $oldDate && $endbookingTime >= $newDate || $bookingTime <= $oldDate && $endbookingTime <= $newDate) {
                    continue;
                } else {
                    return $this->failedResponse(trans("$string_file.already_activated_booking", ['time' => $bookingtimestamp]));
                }
            }
            // If booking accept by taxi company driver
            if ($driver->taxi_company_id != NULL && $booking->Merchant->Configuration->company_admin == 1) {
                $booking->taxi_company_id = $driver->taxi_company_id;
            }
            if ($booking->booking_status == 1000 && $booking->is_in_drive == 1 && !empty($booking->offer_amount)) {
                $booking->price_for_ride_amount = $booking->offer_amount;
                $booking->price_for_ride = 2;
                $booking->estimate_bill = $booking->offer_amount;
            }
            $booking->booking_status = 1012;
            $booking->driver_vehicle_id = $request->driver_vehicle_id; // coming from store online config
            $booking->driver_id = $driver->id;
            $booking->save();

            // inset booking status history
            $this->saveBookingStatusHistory($request, $booking, $booking->id);
            $config = Configuration::where('merchant_id', $booking->merchant_id)->first();
            if (isset($config->whatsapp_notification) && $config->whatsapp_notification == 1) {
                SendWhatsappNotificationEvent::dispatch($booking->merchant_id, 999, $booking);
            }

            //$user_id = $booking->user_id;
            $bookingData = new BookingDataController();
            //$message = $bookingData->LanguageData($booking->merchant_id, 30);
            // $data = $bookingData->BookingNotification($booking);
            BookingRequestDriver::create([
                'booking_id' => $request->booking_id,
                'driver_id' => $driver->id,
                'distance_from_pickup' => 0,
                'request_status' => 1
            ]);
            $notification_data = $bookingData->bookingNotificationForUser($booking, "PARTIAL_ACCEPTED");
            //Onesignal::UserPushMessage($user_id, $data, $message, 1, $booking->merchant_id);
            $booking = $bookingData->DriverBookingDetails($booking_id, $booking->merchant_id, $request);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.ride_accepted"), $booking);
    }

    // in-drive booking counter offer
    public function bookingCounterOffer(Request $request)
    {
        $request_fields = [
            'booking_id' => ['required', 'integer', Rule::exists('bookings', 'id')->where(function ($query) {
                $query->where("booking_status", 1000)->where("is_in_drive", 1);
            }),],
            'driver_vehicle_id' => ['required', 'integer', Rule::exists('driver_vehicles', 'id')->where(function ($query) {
            }),],
            'amount' => ['required']
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);

            $booking = Booking::find($request->booking_id);

            /*
             * Checking the driver having ride or not
             */
            if ($booking->booking_type == 2) {
                $string_file = $this->getStringFile($booking->merchant_id);
                $booking_later_booking_date_time = $booking->later_booking_date . " " . $booking->later_booking_time;
                $current_DateTime = convertTimeToUSERzone(date('Y-m-d H:i'), $booking->CountryArea->timezone, null, $booking->Merchant);
                if (strtotime($booking_later_booking_date_time) <= strtotime($current_DateTime)) {
//                    $booking_status = "1016";
//                    $booking->save();
                    // inset booking status history
                    $this->saveBookingStatusHistory($request, $booking, $booking->id);
                    $data = array('booking_id' => $booking->id, 'booking_status' => "1016");
                    return $this->failedResponse(trans("$string_file.ride_expired"), $data);
                }
                $driverPartial = Booking::where([['driver_id', '=', $driver->id], ['booking_status', '=', 1012]])->get();
                $time = $booking->Merchant->BookingConfiguration->partial_accept_hours;
                $partial_accept_before_minutes = $booking->Merchant->BookingConfiguration->partial_accept_before_hours;
                $bookingTimeString = $booking->later_booking_date . " " . $booking->later_booking_time;

                // convert to date object
                $bookingTime = new DateTime($bookingTimeString);
                $endbookingTime = new DateTime($bookingTimeString);
                // current time according to time zone
                $current_DateTime = convertTimeToUSERzone(date('Y-m-d H:i'), $booking->CountryArea->timezone, null, $booking->Merchant);
                $minutes = (strtotime($bookingTime->format("Y-m-d H:i")) - strtotime($current_DateTime)) / 60;
                if ($booking->Merchant->BookingConfiguration->ride_later_ride_allocation != 2 && $minutes > $partial_accept_before_minutes) {
                    $config_time = date(getDateTimeFormat(2), strtotime('-' . $partial_accept_before_minutes . 'minutes', strtotime($bookingTime->format("Y-m-d H:i:s"))));
                    $message = trans("$string_file.ride_later_accept_warning") . $config_time;
                    return $this->failedResponse($message);
                }

                // add estimate time + time difference between ride
                $endbookingTime->modify("+{$booking->estimate_time}");
                $endbookingTime->modify("+{$time} mins");

                // convert to time string
                $bookingTime = $bookingTime->format("Y-m-d H:i");
                $endbookingTime = $endbookingTime->format("Y-m-d H:i");

                foreach ($driverPartial as $value) {
                    $bookingtimestamp = $value->later_booking_date . " " . $value->later_booking_time;
                    // Accepted ride time to date object
                    $DateTime = new DateTime($bookingtimestamp);
                    $oldDateTime = new DateTime($bookingtimestamp);
                    if ($value->estimate_time) {
                        // add estimate time to date object
                        $oldDateTime->modify("+{$value->estimate_time}");
                    }
                    // add time difference between ride
                    $oldDateTime->modify("+{$time} mins");
                    // convert to time string
                    $newDate = $DateTime->format("Y-m-d H:i");
                    $oldDate = $oldDateTime->format("Y-m-d H:i");
                    // Condition check for active ride or booking ride time and date conflicts
                    if ($bookingTime >= $oldDate && $endbookingTime >= $newDate || $bookingTime <= $oldDate && $endbookingTime <= $newDate) {
                        continue;
                    } else {
                        return $this->failedResponse(trans("$string_file.already_activated_booking", ['time' => $bookingtimestamp]));
                    }
                }
            }
            /*
             * End- Checking the driver having ride or not
             */

            $status = null;
            $description = !empty($request->description) ? $request->description : "";

            $booking_bidding_driver = BookingBiddingDriver::where(["booking_id" => $request->booking_id, "driver_id" => $driver->id])->first();
            if (empty($booking_bidding_driver)) {
                $booking_bidding_driver = new BookingBiddingDriver();
                $booking_bidding_driver->booking_id = $request->booking_id;
                $booking_bidding_driver->driver_id = $driver->id;
                $booking_bidding_driver->driver_vehicle_id = $request->driver_vehicle_id;
            }
            $booking_bidding_driver->amount = $request->amount;
            $booking_bidding_driver->status = 2;
            $booking_bidding_driver->description = $description;
            $booking_bidding_driver->save();

            /**send notification to user*/
            $bookingData = new BookingDataController();
            $booking->driver_quoted_counter_amt = $request->amount;
            $notification_data = $bookingData->bookingNotificationForUser($booking, "IN_DRIVE_BOOKING_COUNTER");

            DB::commit();
            return $this->successResponse(trans("$string_file.success"));
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
    }

    public function arrivedAtPickup(Request $request)
    {
        $merchant_id = $request->user('api-driver')->merchant_id;
        $validator = Validator::make($request->all(), [
            'id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1002);
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new Exception($errors[0]);
        }
        DB::beginTransaction();
        try {
            $booking_id = $request->id;
            $booking_obj = new Booking;
            $booking = $booking_obj->getBooking($booking_id);
            $booking->booking_status = 1003;
            $booking->save();
            $string_file = $this->getStringFile($booking->Merchant);
            // inset booking status history
            $this->saveBookingStatusHistory($request, $booking, $booking->id);

            $user_id = $booking->user_id;
            BookingDetail::where([['booking_id', '=', $booking_id]])->update([
                'arrive_timestamp' => strtotime('now'),
                'arrive_latitude' => $request->latitude,
                'arrive_longitude' => $request->longitude,
                'accuracy_at_arrive' => $request->accuracy,
            ]);
            $bookingData = new BookingDataController();
            // $message = $bookingData->LanguageData($booking->merchant_id, 31);
            $notification_data = $bookingData->bookingNotificationForUser($booking, "ARRIVED_AT_PICKUP");
            //Onesignal::UserPushMessage($user_id, $notification_data, $message, 1, $booking->merchant_id);
            $return_data = $bookingData->driverBookingDetails($booking, false, $request);
            $booing_config = BookingConfiguration::where('merchant_id', $booking->merchant_id)->first();

            $config = Configuration::where('merchant_id', $booking->merchant_id)->first();
            if (isset($config->whatsapp_notification) && $config->whatsapp_notification == 1) {
                SendWhatsappNotificationEvent::dispatch($merchant_id, 1003, $booking);
            }
            //            if ($booking->Segment->slag == "DELIVERY" && isset($booing_config->delivery_drop_otp) && ($booing_config->delivery_drop_otp == 1 || $booing_config->delivery_drop_otp == 2)) {
            //                // Store Delivery Details
            //                $this->storeDeliveryDetails($booking);
            //            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        DB::commit();
        return ['message' => trans("$string_file.arrived_pickup"), 'data' => $return_data];
    }

    // booking start by driver
    public function startBooking(Request $request)
    {
        $driver = $request->user('api-driver');
        
        $merchant_id = $driver->merchant_id;
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        $validator = Validator::make($request->all(), [
            'id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1003);
                }),
            ],
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new Exception($errors[0]);
        }
        DB::beginTransaction();
        try {
            $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
            $key = $configuration->google_key;
            $booking_id = $request->id;
            $booking_update = Booking::select('id', 'booking_status_history', 'booking_status')->Find($booking_id);
            $booking_update->booking_status = 1004;
            $booking_update->save();

            // inset booking status history
            $this->saveBookingStatusHistory($request, $booking_update, $booking_update->id);

            // get booking data
            $booking_obj = new Booking;
            $booking = $booking_obj->getBooking($booking_id);

            $country_area_id = $booking->country_area_id;
            $service_type_id = $booking->service_type_id;
            $service_type = $booking->ServiceType->type;
            if($configuration->rental_price_type == 1){
                // require for services that type is rental or outstation
                if (in_array($service_type, array(4))) {
                    $validator = Validator::make($request->all(), [
                        'send_meter_image' => 'required',
                        'send_meter_value' => 'required|numeric|min:0',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new Exception($errors[0]);
                    }
                }
            }else{
                // require for services that type is rental or outstation
                if (in_array($service_type, array(2, 4))) {
                    $validator = Validator::make($request->all(), [
                        'send_meter_image' => 'required',
                        'send_meter_value' => 'required|numeric|min:0',
                    ]);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new Exception($errors[0]);
                    }
                }
            }
            
            $price_card_id = $booking->price_card_id;
            $user_id = $booking->user_id;
            $selected_map = getSelectedMap($booking->Merchant, "START_BOOKING");
            if($selected_map == "GOOGLE"){
                $startAddress = GoogleController::GoogleLocation($request->latitude, $request->longitude, $key, 'rideStart', $string_file);
            }
            else{
                $key = get_merchant_google_key($merchant_id,'api', "MAP_BOX");
                $startAddress = MapBoxController::MapBoxLocation($request->latitude, $request->longitude, $key, 'rideStart', $string_file);
            }
            saveApiLog($booking->merchant_id, "geocode" , "START_BOOKING", $selected_map);
            $startAddress = $startAddress ? $startAddress : 'Address Not found';
            $bookingDetails = BookingDetail::where([['booking_id', '=', $booking_id]])->first();
            $newDistance = new DistanceController();
//            $dead_milage_distance = $newDistance->AerialDistanceBetweenTwoPoints($request->latitude, $request->longitude, $bookingDetails->accept_latitude, $bookingDetails->accept_longitude);
            $arriveTimeStemp = $bookingDetails->arrive_timestamp;
            $startTimeStemp = strtotime('now');
            $waitTime = round(abs($arriveTimeStemp - $startTimeStemp) / 60, 2);
            if($configuration->rental_price_type == 1){
                if (in_array($service_type, array(4))) {
                    $bookingDetails->start_meter_value = $request->send_meter_value;
                    if($request->multi_part == 1){
                        $additional_req = ['compress' => true,'custom_key' => 'product'];
                        $bookingDetails->start_meter_image = $this->uploadImage('send_meter_image', 'send_meter_image', $merchant_id,'single',$additional_req);
                    }else{
                        $bookingDetails->start_meter_image = $this->uploadBase64Image('send_meter_image', 'send_meter_image', $merchant_id);
                    }
                //                    $this->uploadImage('send_meter_image', 'service', $merchant_id);
                }
            }else{
                if (in_array($service_type, array(2, 4))) {
                    $bookingDetails->start_meter_value = $request->send_meter_value;
                    if($request->multi_part == 1){
                        $additional_req = ['compress' => true,'custom_key' => 'product'];
                        $bookingDetails->start_meter_image = $this->uploadImage('send_meter_image', 'send_meter_image', $merchant_id,'single',$additional_req);
                    }else{
                        $bookingDetails->start_meter_image = $this->uploadBase64Image('send_meter_image', 'send_meter_image', $merchant_id);
                    }
                //                    $this->uploadImage('send_meter_image', 'service', $merchant_id);
                }
            }
            
            if ($service_type_id == 5) {

                PoolRideList::where([['driver_id', '=', $booking->driver_id], ['booking_id', '=', $booking->id]])->update(['pickup' => 1]);
                $poolRide = new PoolController();
                $poolRide->savePoolHistory($request, $booking, 'pickup');
            }
            $bookingDetails->start_timestamp = $startTimeStemp;
//            $bookingDetails->dead_milage_distance = $dead_milage_distance;
            $bookingDetails->start_latitude = $request->latitude;
            $bookingDetails->start_longitude = $request->longitude;
            $bookingDetails->accuracy_at_start = $request->accuracy;
            $bookingDetails->start_location = $startAddress;
            $bookingDetails->wait_time = $waitTime;
            $bookingDetails->save();
            $config = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
            $bookingData = new BookingDataController();

            if (!empty($config->toll_api)) {
                $newTool = new Toll();
                $toolPrice = $newTool->checkToll($config->toll_api, $startAddress, $booking->drop_location, $booking->waypoints, $config->toll_key);
                if (is_array($toolPrice) && array_key_exists('cost', $toolPrice) && $toolPrice['cost'] > 0) {

                    $notification_data = $bookingData->bookingNotificationForUser($booking, "TOLL_ADDED");
                }
            }
            //send notification to user
            //            $notification_data['notification_type'] = "RIDE_START";
            $notification_data = $bookingData->bookingNotificationForUser($booking, "RIDE_START");
            // Onesignal::UserPushMessage($user_id, $notification_data, $message, 1, $booking->merchant_id);
            $return_data = $bookingData->driverBookingDetails($booking, false, $request);
            $SmsConfiguration = SmsConfiguration::select('ride_start_enable', 'ride_start_msg')->where([['merchant_id', '=', $merchant_id]])->first();
            if ($SmsConfiguration && $SmsConfiguration->ride_start_enable == 1) {
                $sms = new SmsController();
                $phone = $booking->User->UserPhone;
                $sms->SendSms($merchant_id, $phone, null, 'RIDE_START', $booking->User->email);
            }
            $commonController = new CommonController();
            $commonController->geofenceDequeue($request->latitude, $request->longitude, $driver, $country_area_id);
            $deliveryController = new DeliveryController();
            $deliveryController->sendOtpToReceiver($booking->id);
            $config = Configuration::where('merchant_id', $merchant_id)->first();
            if (isset($config->whatsapp_notification) && $config->whatsapp_notification == 1) {
                SendWhatsappNotificationEvent::dispatch($merchant_id, 1004, $booking);
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        DB::commit();
        return ['message' => trans("$string_file.ride_started"), 'data' => $return_data];
    }

    //     reach
    public function reachedAtMultiDrop(Request $request)
    {
        if(!empty($request->timestampvalue)){
            $cacheKey = 'driver_reached_at_multi_drop_' . $request->timestampvalue;

            if (Cache::has($cacheKey)) {
                $response = Cache::get($cacheKey);
                $message = $response['message'];
                $booking = $response['booking'];
                return $this->successResponse($message, $booking);
            }
        }

        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1004);
                }),
            ],
            'latitude' => 'required',
            'longitude' => 'required',
            'segment_slug' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::rollBack();
        try {
            $booking_id = $request->booking_id;
            $booking_obj = new Booking;
            $booking = $booking_obj->getBooking($booking_id);
            $string_file = $this->getStringFile(NULL, $booking->Merchant);
            $drop_location = $booking->waypoints;
            $multiple_location = json_decode($drop_location, true);
            $final = [];
            $done = '';
            $booking_config = $booking->Merchant->BookingConfiguration;
            $podOption = $booking->Merchant->ApplicationConfiguration->proof_of_delivery;
            $delivery_drop_otp = false;
            $delivery_drop_qr = false;
            if ($request->segment_slug == "DELIVERY" && isset($booking_config->delivery_drop_otp) && $booking->platform == 1) {
                // $validate_arr = [
                //     'receiver_otp' => 'required',
                // ];
                
                $validate_arr = [];
                if($booking->Merchant->ApplicationConfiguration->enter_crn_number == 1){
                    $validate_arr =[
                        'receiver_otp' => 'required',
                    ];
                }
                
                if ($booking_config->delivery_drop_otp == 1) {
                    $delivery_drop_otp = true;
                    $validate_arr = array_merge($validate_arr, array(
                        // 'receiver_otp' => 'required',
                        'receiver_name' => 'required',
                        'receiver_image' => ($podOption == 2 || $podOption == 3) ? 'required' : '',
                        'upload_delivery_image' => ($podOption == 1 || $podOption == 3) ? 'required' : '',
                    ));
                    $validator = Validator::make($request->all(), $validate_arr);
                }
                if ($booking_config->delivery_drop_otp == 2) {
                    $delivery_drop_qr = true;
                    $validator = Validator::make($request->all(), $validate_arr);
                }
                if ($delivery_drop_otp || $delivery_drop_qr) {
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        return $this->failedResponse($errors[0]);
                    }
                }
            }
            $done = true;
            foreach ($multiple_location as $key => $val) {
                if ($val['status'] == 1 && $done) :
                    $booking_delivery_detail = BookingDeliveryDetails::where(['booking_id' => $booking->id, 'stop_no' => $val['stop']])->first();
                    if (!empty($booking_delivery_detail)) {
                        $next_stop = $val['stop'] + 1;
                        $booking_delivery_detail_next = BookingDeliveryDetails::select('additional_notes', 'product_image_one', 'product_image_two')->where(['booking_id' => $booking->id, 'stop_no' => $next_stop])->first();
                        if (!empty($booking_delivery_detail_next)){
                            $request->merge(["additional_notes" => $booking_delivery_detail_next->additional_notes, 'product_image_one' => $booking_delivery_detail_next->product_image_one, 'product_image_two' => $booking_delivery_detail_next->product_image_two]);
                        }
                        if ($booking->platform == 1 && ($delivery_drop_otp || $delivery_drop_qr)) {
                            if ($request->receiver_otp == $booking_delivery_detail->opt_for_verify || $booking->Merchant->ApplicationConfiguration->enter_crn_number == 2) {
                                $booking_delivery_detail->otp_status = 1;
                            } else {
                                return $this->failedResponse(trans("$string_file.invalid_otp_try_again"), []);
                            }
                        }
                        $booking_delivery_detail->drop_status = 1;
                        $booking_delivery_detail->receiver_name = $request->receiver_user_name;
                        $booking_delivery_detail->receiver_phone = isset($request->receiver_name) ? $request->receiver_name : '';
                        if (!empty($request->receiver_image)) {
                            $booking_delivery_detail->receiver_image = $this->uploadImage('receiver_image', 'booking_images', $booking->merchant_id);
                        }
                        if(!empty($request->upload_delivery_image)){
                            if($request->multi_part == 1){
                                $additional_req = ['compress' => true,'custom_key' => 'product'];
                                $booking_delivery_detail->upload_delivery_image = $this->uploadImage('upload_delivery_image', 'booking_images', $booking->merchant_id,'single',$additional_req);
                            }else{
                                $booking_delivery_detail->upload_delivery_image = $this->uploadBase64Image('upload_delivery_image', 'booking_images', $booking->merchant_id);
                            }
                        }
                        $booking_delivery_detail->drop_latitude = $request->latitude;
                        $booking_delivery_detail->drop_latitude = $request->longitude;
                        $booking_delivery_detail->save();
                    }
                    $val['status'] = 2;
                    $val['end_latitude'] = $request->latitude;
                    $val['end_longitude'] = $request->longitude;
                    $val['end_time'] = date('Y-m-d H:i:s');
                    $done = false;
                endif;
                array_push($final, $val);
            }
            $updated_location = json_encode($final);
            $booking->waypoints = $updated_location;
            $booking->save();

            $bookingData = new BookingDataController();
            //$message = $bookingData->LanguageData($booking->merchant_id, 33);
            //        $data = $bookingData->BookingNotification($booking);
            //        Onesignal::UserPushMessage($booking->user_id, $data, $message, 1, $booking->merchant_id);

            $bookingData->bookingNotificationForUser($booking, "REACH_AT_DROP");
            $booking = $bookingData->DriverBookingDetails($request->booking_id, $booking->merchant_id, $request);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        if(!empty($request->timestampvalue)){
            Cache::put($cacheKey, ["message" => trans("$string_file.reached_drop"), "booking" => $booking], 120);
        }
        return $this->successResponse(trans("$string_file.reached_drop"), $booking);
    }

    public function addTip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id'),
            ],
            'tip_amount' => 'required|numeric|gt:0',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $booking = Booking::select("id", "country_area_id", "driver_id", "booking_status", "payment_status", "final_amount_paid", "bill_details", "segment_id", "merchant_id", "payment_method_id", "user_id")->Find($request->booking_id);
            $string_file = $this->getStringFile(NULL, $booking->Merchant);
            $bookingDetails = $booking->BookingDetail;
            if ($bookingDetails->tip_amount > 0) {
                $message = trans("$string_file.tip_already_added");
                return $this->failedResponse($message);
            }
            $bookingDetails->tip_amount = ($request->tip_amount > 0) ? $request->tip_amount : 0;
            $bookingDetails->save();
            if ($booking->booking_status == 1005 && $booking->payment_status == 1) {
                $tip_amount = $request->tip_amount;
                // make payment calls if payment method is not cash
                if ($booking->payment_method_id != 1) {
                    // make payment
                    $currency = $booking->CountryArea->Country->isoCode;
                    $array_param = array(
                        'booking_id' => NULL, // we don't want to update any status thats why booking id is going as null
                        'payment_method_id' => $booking->payment_method_id,
                        'amount' => $tip_amount,
                        'user_id' => $booking->user_id,
                        'card_id' => $booking->card_id,
                        'currency' => $currency,
                        'quantity' => 1,
                        'order_name' => $booking->merchant_booking_id,
                        'driver_sc_account_id' => $booking->Driver->sc_account_id
                    );

                    $payment = new Payment();
                    $payment->MakePayment($array_param);

                    // credit driver wallet
                    $paramArray = array(
                        'driver_id' => $booking->driver_id,
                        'booking_id' => $booking->id,
                        'order_id' => NULL,
                        'handyman_order_id' => NULL,
                        'amount' => $tip_amount,
                        'narration' => 16,
                    );
                    WalletTransaction::WalletCredit($paramArray);
                }

                // update booking amount
                // $booking->final_amount_paid = $booking->final_amount_paid + $tip_amount;
                // $booking->save();
                $arr_bill_details = json_decode($booking->bill_details, true);
                $updated_bill_details = [];
                foreach ($arr_bill_details as $bill_details) {
                    $new_bill_details = $bill_details;
                    if (isset($bill_details['parameterType']) && $bill_details['parameterType'] == "tip_amount") {
                        $new_bill_details['amount'] = $tip_amount;
                    }

                    $updated_bill_details[] = $new_bill_details;
                }
                $booking->final_amount_paid = $booking->final_amount_paid + $tip_amount;
                $booking->bill_details = json_encode($updated_bill_details);
                $booking->save();

                // booking details
                $bookingDetails->total_amount = $bookingDetails->total_amount + $tip_amount;
                $bill_details = json_decode($bookingDetails->bill_details, true);
                $updated_details = [];
                foreach ($bill_details as $details) {
                    $new_bill_details = $details;
                    if (isset($details['parameterType']) && $details['parameterType'] == "tip_amount") {
                        $new_bill_details['amount'] = $tip_amount;
                    }
                    $updated_details[] = $new_bill_details;
                }
                $bookingDetails->bill_details = json_encode($updated_details);
                $bookingDetails->save();

                // update total amount of driver in transaction
                $booking_transaction = $booking->BookingTransaction;
                $driver_existing_amount = $booking_transaction->driver_total_payout_amount;
                $existing_booking_amount = $booking_transaction->customer_paid_amount;
                $existing_online_payment = $booking_transaction->online_payment;
                $existing_cash_payment = $booking_transaction->cash_payment;

                $booking_transaction->tip = $tip_amount;
                $booking_transaction->driver_total_payout_amount = $driver_existing_amount + $tip_amount;
                $booking_transaction->customer_paid_amount = $existing_booking_amount + $tip_amount;
                if ($booking->payment_method_id == 1) {
                    $booking_transaction->cash_payment = $existing_cash_payment + $tip_amount;
                } else {
                    $booking_transaction->online_payment = $existing_online_payment + $tip_amount;
                }
                $booking_transaction->save();

                // tip credited notification
                setLocal($booking->Driver->language);
                $data = array('notification_type' => 'TIP_ADDED', 'segment_type' => $booking->Segment->slag, 'segment_data' => []);
                $arr_param = array(
                    'driver_id' => $booking->driver_id,
                    'data' => $data,
                    'message' => trans("$string_file.tip_credited_to_driver"),
                    'merchant_id' => $booking->merchant_id,
                    'title' => trans("$string_file.tip_credited")
                );
                Onesignal::DriverPushMessage($arr_param);
                setLocal();
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.tip_message"), []);
    }

    public function EstimateBillDetailsBreakup($billDetails_array, $estimate_bill)
    {
        $billDetails_array = json_decode($billDetails_array, true);
        $promocode_array = array_filter($billDetails_array, function ($e) {
            if ((isset($e['parameterType']) && $e['parameterType'] == "PROMO CODE") || ($e['parameter'] == "Promotion")) {
                return (($e['parameterType'] == "PROMO CODE") || ($e['parameter'] == "Promotion"));
            }
        });
        $promocode_discount = '0.0';
        if (!empty($promocode_array)) :
            $promocode_discount = array_sum(Arr::pluck($promocode_array, 'amount'));
        endif;

        $cancellation_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameter', $e) ? $e['parameter'] == "Cancellation fee" : []);
        });
        $cancellation_amount_received = '0.0';
        if (!empty($cancellation_array)) :
            $cancellation_amount_received = array_sum(Arr::pluck($cancellation_array, 'amount'));
        endif;

        $distance_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameterType', $e) ? $e['parameterType'] == "1" : []);
        });
        $distance_amount_received = '0.0';
        if (!empty($distance_charge_array)) :
            $distance_amount_received = array_sum(Arr::pluck($distance_charge_array, 'amount'));
        endif;

        $min_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameterType', $e) ? $e['parameterType'] == "8" : []);
        });
        $min_amount_received = '0.0';
        if (!empty($min_charge_array)) :
            $min_amount_received = array_sum(Arr::pluck($min_charge_array, 'amount'));
        endif;

        $hr_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameterType', $e) ? $e['parameterType'] == "2" : []);
        });
        $hr_amount_received = '0.0';
        if (!empty($hr_charge_array)) :
            $hr_amount_received = array_sum(Arr::pluck($hr_charge_array, 'amount'));
        endif;

        $amount_without_spl_discount_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('simply_amount', $e) ? $e['simply_amount'] == "amount_without_spl_discount" : []);
        });
        $amount_without_spl_discount = '0.0';
        if (!empty($amount_without_spl_discount_array)) :
            $amount_without_spl_discount = array_sum(Arr::pluck($amount_without_spl_discount_array, 'amount'));
        endif;

        $surge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameter', $e) ? $e['parameter'] == "Surge-Charge" : []);
        });
        $surge = '0.0';
        if (!empty($surge_array)) :
            $surge = array_sum(Arr::pluck($surge_array, 'amount'));
        endif;

        $extra_charge = '0.0';
        $extra_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('extra_charges_amount', $e) ? $e['extra_charges_amount'] == "Extra-Charges" : []);
        });
        if (!empty($extra_charge_array)) :
            $extra_charge = array_sum(Arr::pluck($extra_charge_array, 'amount'));
        endif;

        $amount_without_discount = ($amount_without_spl_discount + $surge + $extra_charge + $distance_amount_received + $hr_amount_received + $min_amount_received);

        $toll_charge = '0.0';
        $toll_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameter', $e) ? $e['parameter'] == "TollCharges" : []);
        });
        if (!empty($toll_charge_array)) :
            $toll_charge = array_sum(Arr::pluck($toll_charge_array, 'amount'));
        endif;

        $tax_charge = '0.0';
        $tax_charge_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('type', $e) ? $e['type'] == "TAXES" : []);
        });
        if (!empty($tax_charge_array)) :
            $tax_charge = array_sum(Arr::pluck($tax_charge_array, 'amount'));
        endif;


        $insurance_amount_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameter', $e) ? $e['parameter'] == "Insurance" : []);
        });
        $insurance_amount = '0.0';
        if (!empty($insurance_amount_array)) :
            $insurance_amount = array_sum(Arr::pluck($insurance_amount_array, 'amount'));
        endif;

        $bookingFeeArray = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameterType', $e) ? $e['parameterType'] == "17" : []);
        });
        $bookingFee = '0.0';
        if (!empty($bookingFeeArray)) :
            $bookingFee = array_sum(Arr::pluck($bookingFeeArray, 'amount'));
        endif;

        $hotel_amount_array = array_filter($billDetails_array, function ($e) {
            return (array_key_exists('parameter', $e) ? $e['parameter'] == "Hotel Charges" : []);
        });
        $hotel_amount = '0.0';
        if (!empty($hotel_amount_array)) :
            $hotel_amount = array_sum(Arr::pluck($hotel_amount_array, 'amount'));
        endif;


        return [
            'bill_details' => $billDetails_array,
            'amount' => $estimate_bill > $promocode_discount ? $estimate_bill - $promocode_discount : $estimate_bill,
            'promo' => $promocode_discount,
            'cancellation_amount_received' => $cancellation_amount_received,
            'subTotalWithoutSpecial' => $amount_without_spl_discount,
            'subTotalWithoutDiscount' => $amount_without_discount,
            'toolCharge' => $toll_charge,
            'surge' => $surge,
            'extracharge' => $extra_charge,
            'insurnce_amount' => $insurance_amount,
            'total_tax' => $tax_charge,
            'booking_fee' => $bookingFee,
            'hotel_amount' => $hotel_amount,
        ];
    }

    // public function SubscriptionPackageExpiryCheck(Driver $driver,$booking)
    // {
    //     // find free package if assigned to driver
    //     $free_package = DriverSubscriptionRecord::select('package_total_trips', 'id', 'used_trips')->where([['package_type', 1],['segment_id',$booking->segment_id], ['driver_id', $driver->id], ['status', 2], ['end_date_time', '>', date('Y-m-d H:i:s')]])->orderBy('id', 'DESC')->first();
    //     $free_package_exist = false;
    //     if (!empty($free_package->id) && $free_package->used_trips < $free_package->package_total_trips) {
    //         $free_package_exist = true;
    //         $free_package->used_trips = $free_package->used_trips + 1;
    //         $free_package->save();
    //     } else {
    //         // if there is no free package or used all free ride then check paid package of driver and deduct ride from that package
    //         // elseif($free_package_exist == false)
    //         $paid_package = DriverSubscriptionRecord::select('package_total_trips', 'id', 'used_trips')->where([['package_type', 2],['segment_id',$booking->segment_id], ['driver_id', $driver->id], ['status', 2], ['end_date_time', '>', date('Y-m-d H:i:s')]])->orderBy('id', 'DESC')->first();
    //         if (!empty($paid_package->id) && $paid_package->used_trips < $paid_package->package_total_trips) {
    //             $paid_package->used_trips = $paid_package->used_trips + 1;
    //             $paid_package->save();
    //         }
    //     }
    // if (($free_package_exist && $free_package->used_trips >= $free_package->package_total_trips) || (!empty($paid_package->id) && !$free_package_exist && $paid_package->used_trips >= $paid_package->package_total_trips)) :
    //     $driver->online_offline = 2;
    //     $driver->save();
    //     $data = [];
    //     setLocal($driver->language);
    /// code commented for string correction
    //            $message = trans('api.subscription_package_expire');
    //            Onesignal::DriverPushMessage($driver->id, $data, $message, 17, $driver->merchant_id);
    //     setLocal();
    // endif;
    // }

    // driver booking info, while ride is going
    public function driverBookingInfo(Request $request) // FOR DRIVER SIDE
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:bookings,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new Exception($errors[0]);
        }
        try {
            $bookingData = new BookingDataController();
            $return_data = $bookingData->driverBookingDetails($request->id, true, $request);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $return_data;
    }

    //
    public function bookingDetails(Request $request) // FOR USER SIDE
    {
        $user = $request->user('api');
        $merchant_id = $user->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $merchant_helper = new \App\Http\Controllers\Helper\Merchant();
        try {
            $booking_id = $request->booking_id;
            $booking_obj = new Booking;
            $booking = $booking_obj->getBooking($booking_id);
            if ($booking->booking_status == 1016) {
                return $this->failedResponse(trans("$string_file.ride_expired"));
            }
            $show_status = 2;
            $eta_pickup_and_dest = "";
            if (!empty($booking->BookingDetail)) {
                if ($booking->BookingDetail->tip_amount > 0) {
                    $show_status = 1;
                }
            }
            // Waiting time timer for user app
            $price_card = PriceCard::with(['PriceCardValues' => function ($query) {
                $query->with(['PricingParameter' => function ($q) {
                    $q->where('parameterType', 9);
                }]);
            }])->find($booking->price_card_id);
            $free_value = '';
            $arrive_timestamp = "";
            if ($booking->booking_status == 1003) {
                $detail = BookingDetail::where([['booking_id', '=', $booking->id]])->first();
                $arrive_timestamp = $detail->arrive_timestamp;
            }
            if (isset($price_card) && !empty($price_card->PriceCardValues)) {
                foreach ($price_card->PriceCardValues as $pcv) {
                    if (isset($pcv->PricingParameter)) {
                        $parameter = $pcv->PricingParameter;
                        if (!empty($parameter)) {
                            $free_value = $pcv->free_value;
                        }
                    }
                }
            }
            // $booking->free_waiting_time_enable = !empty($free_value) ? true : false;
            // $booking->free_waiting_time = $free_value;

            $merchant = Merchant::select('id')->find($merchant_id);
            if ($merchant->Configuration->twilio_call_masking == 1) {
                $booking->driver->phoneNumber = $booking->driver_masked_number ?? '';
            }
            $booking->PaymentMethod->payment_method = $booking->PaymentMethod->MethodName($merchant_id) ? $booking->PaymentMethod->MethodName($merchant_id) : $booking->PaymentMethod->payment_method;
            $app_config = $merchant->ApplicationConfiguration;
            $config = $merchant->BookingConfiguration;
            $vehicleDetails = Booking::VehicleDetail($booking);
            // $booking->vehicle_number_plate_image = $booking->DriverVehicle ? get_image($booking->DriverVehicle->vehicle_number_plate_image, 'vehicle_document', $merchant_id, true, false) : "";
            $ployline = $booking->ploy_points;
            $booking_status = $booking->booking_status;
            // $booking->share_able_link = "";
            $booking->otp_enable = $config->ride_otp;
            // $booking->driver->fullName = $booking->driver->first_name . " " . $booking->driver->last_name;
            $location_editable = false;
            $marker_type = "PICK";
            $marker_lat = $booking->pickup_latitude;
            $marker_long = $booking->pickup_longitude;
            $tip_status = false;

            $BookingData = new BookingDataController();
            $dropLocation = $BookingData->NextLocation($booking->waypoints, $string_file);
            $drop_stop_no = 0;
            if (!empty($dropLocation) && $dropLocation['last_location'] == 1) {
                $drop_latitude = $dropLocation['drop_latitude'];
                $drop_longitude = $dropLocation['drop_longitude'];
                $drop_location = $dropLocation['drop_location'];
                $drop_stop_no = $dropLocation['stop'];
            } else {
                $drop_latitude = $booking->drop_latitude;
                $drop_longitude = $booking->drop_longitude;
                $drop_location = $booking->drop_location;
            }
            $sos_visibility = false;
            $shareable = false;
            $cancelable = false;
            $share_able_link = "";
            switch ($booking_status) {
                case "1001":
                    $trip_status_text = trans("$string_file.accept_ride");
                    //                    $trip_status_text = $BookingData->LanguageData($merchant_id, 1);
                    $location = $booking->pickup_location;
                    $booking->cancelable = true;
                    $cancelable = true;
                    $location_color = "2ecc71";
                    $location_headline = trans("$string_file.pickup");
                    //                    $location_headline = $BookingData->LanguageData($merchant_id, 6);
                    break;
                case "1002":
                    $trip_status_text = trans("$string_file.arriving");
                    //                        $BookingData->LanguageData($merchant_id, 14);
                    //                        trans("$string_file.arriving");
                    $location = $booking->pickup_location;
                    $booking->cancelable = true;
                    $cancelable = true;
                    $location_color = "2ecc71";
                    $location_headline = trans("$string_file.pickup");
                    if (isset($booking->BookingDetail) && $merchant->BookingConfiguration->eta_pickup_and_dest == 1) {
                        $google_response = isset($booking->BookingDetail->on_accept_google_response)? json_decode($booking->BookingDetail->on_accept_google_response, true): null;
                        $eta_pickup_and_dest = isset($google_response)? $google_response['total_time_text'] : "";
                    }
                    //                    $location_headline = $BookingData->LanguageData($merchant_id, 6);
                    break;
                case "1003":
                    $tip_status = ($app_config->tip_status == 1) ? true : false;
                    $tip_already_paid = $show_status;
                    $location_editable = false;

                    if ($booking->Segment->slag == "DELIVERY" && isset($config->location_editable) && ($config->location_editable == 2 || $config->location_editable == 3) && $booking->is_in_drive != 1) {
                        $location_editable = $booking->total_drop_location <= 1 ? true : false;
                    }
                    elseif($booking->Segment->slag == "TAXI" && isset($config->location_editable) && ($config->location_editable == 1 || $config->location_editable == 3) && $booking->is_in_drive != 1){
                        $location_editable = ($booking->total_drop_location <= 1) ? true : false; // $booking->service_type_id == 1 &&
                    }
                    //                    $trip_status_text = trans("$string_file.driver_waiting_at_pickup");
                    //                        $BookingData->LanguageData($merchant_id, 15);
                    //                        trans("$string_file.driver_waiting_at_pickup");
                    $trip_status_text = trans("$string_file.driver_waiting_at_pickup");
                    //                    $trip_status_text = $BookingData->LanguageData($merchant_id, 15);
                    $location = $drop_location;
                    //                    $booking->cancelable = true;
                    $booking->cancelable = true;
                    $cancelable = true;
                    $location_color = "e74c3c";
                    $location_headline = trans("$string_file.drop");
                    //                    $location_headline = $BookingData->LanguageData($merchant_id, 7);
                    $sos_visibility = $app_config->sos_user_driver == 1 ? true : false;
                    $marker_type = "DROP";
                    $marker_lat = $drop_latitude;
                    $marker_long = $drop_longitude;
                    break;
                case "1004":
                    $tip_status = ($app_config->tip_status == 1) ? true : false;
                    $location_editable = false;
                    if($booking->Segment->slag == "TAXI" && isset($config->location_editable) && ($config->location_editable == 1 || $config->location_editable == 3) && $booking->is_in_drive != 1){
                        $location_editable = ($booking->total_drop_location <= 1) ? true : false; // $booking->service_type_id == 1 &&
                    }
                    $trip_status_text = trans("$string_file.ride_started");
                    //                    $trip_status_text = $BookingData->LanguageData($merchant_id, 16);
                    $location = $drop_location;
                    $booking->cancelable = false;
                    $cancelable = false;
                    $location_color = "e74c3c";
                    $location_headline = trans("$string_file.drop");
                    //                    $location_headline = $BookingData->LanguageData($merchant_id, 7);
                    $shareable = $booking->Segment->slag == 'TAXI' ? true : false;
                    $booking->share_able_link = $booking->unique_id ? route('ride.share', $booking->unique_id) : "";
                    $share_able_link = $booking->unique_id ? route('ride.share', $booking->unique_id) : "";
                    $sos_visibility = $app_config->sos_user_driver == 1 ? true : false;
                    $marker_type = trans("$string_file.drop");
                    //                    $marker_type = $BookingData->LanguageData($merchant_id, 7);
                    $marker_lat = $drop_latitude;
                    $marker_long = $drop_longitude;
                    if ($merchant->BookingConfiguration->eta_pickup_and_dest == 1 && empty($booking->BookingDetail->after_start_google_response)) {
                        $google_response = $this->EtaCalculation($booking);
                        $booking->BookingDetail->after_start_google_response = isset($google_response) ? json_encode($google_response) : null;
                        $booking->BookingDetail->save();
                    }
                    $eta_pickup_and_dest = !empty($booking->BookingDetail->after_start_google_response)? json_decode($booking->BookingDetail->after_start_google_response, true)['time'] : "";
                    break;
                case "1005":
                    $trip_status_text = trans("$string_file.ride_completed");
                    //                    $trip_status_text = $BookingData->LanguageData($merchant_id, 17);
                    $location = $drop_location;
                    $booking->cancelable = false;
                    $cancelable = false;
                    $location_color = "e74c3c";
                    $location_headline = trans("$string_file.drop");
                    //                    $location_headline = $BookingData->LanguageData($merchant_id, 7);
                    $marker_type = "DROP";
                    $marker_lat = $drop_latitude;
                    $marker_long = $drop_longitude;
                    break;
                case "1006":
                    $trip_status_text = trans("$string_file.user_cancel");
                    //                    $trip_status_text = $BookingData->LanguageData($merchant_id, 2);
                    $location = $booking->drop_location;
                    $location_color = "e74c3c";
                    $location_headline = "Drop";
                    break;
                case "1007":
                    $trip_status_text = trans("$string_file.driver_cancel");
                    //                    $trip_status_text = $BookingData->LanguageData($merchant_id, 10);
                    $location = $booking->drop_location;
                    $location_color = "e74c3c";
                    $location_headline = "Drop";
                    break;
                case "1008":
                    $trip_status_text = trans("$string_file.admin_cancel");
                    //                    $trip_status_text = $BookingData->LanguageData($merchant_id, 11);
                    $location = $booking->drop_location;
                    $location_color = "e74c3c";
                    $location_headline = "Drop";
                    break;
            }
            if ($tip_status == true && $booking->BookingDetail->tip_amount > 0) {
                $tip_status = false;
            }

            $location_object = array('trip_status_text' => $trip_status_text, 'location_headline' => $location_headline, 'location_text' => $location, 'location_color' => $location_color, 'location_editable' => $location_editable, "drop_stop_no" => $drop_stop_no);
            if($app_config->working_with_redis == 1){
                $driver_current_loc = getDriverCurrentLatLong($booking->Driver);
                $latitude = $driver_current_loc['latitude'] ?? "";
                $longitude = $driver_current_loc['longitude'] ?? "";
                $bearing = $driver_current_loc['bearing'] ?? "";
                $driver_marker_type = array('driver_marker_name' => explode_image_path($booking->VehicleType->vehicleTypeMapImage), 'driver_marker_type' => "CAR_ONE", 'driver_marker_lat' => $latitude, "driver_marker_long" => $longitude, 'driver_marker_bearing' => $bearing);
            }
            else{
                $driver_marker_type = array('driver_marker_name' => explode_image_path($booking->VehicleType->vehicleTypeMapImage), 'driver_marker_type' => "CAR_ONE", 'driver_marker_lat' => $booking->driver->current_latitude, "driver_marker_long" => $booking->driver->current_longitude, 'driver_marker_bearing' => $booking->driver->bearing);
            }
            //            $booking->location = $location_object;
            $marker = array('marker_type' => $marker_type, 'marker_lat' => $marker_lat, "marker_long" => $marker_long);
            $polydata = array('polyline_width' => '8', 'polyline_color' => "333333", 'polyline' => $ployline);

            $sos = Sos::AllSosList($merchant_id, 1, $booking->user_id);
            if ($merchant->Configuration->without_country_code_sos == 1) {
                $phoneCode = $booking->CountryArea->Country->phonecode;
                $booking->driver->phoneNumber = str_replace($phoneCode, '', $booking->driver->phoneNumber);
                $booking->User->UserPhone = str_replace($phoneCode, '', $booking->User->UserPhone);
            }
            $vehicle_color = isset($booking->DriverVehicle->vehicle_color) ? $booking->DriverVehicle->vehicle_color : "";

            $booing_config = BookingConfiguration::where('merchant_id', $booking->merchant_id)->first();
            $delivery_drop_otp = 0;
            $delivery_drop_qr = 0;
            $delivery_otp = '';
            if ($booking->Segment->slag == "DELIVERY" && isset($booing_config->delivery_drop_otp) && ($booing_config->delivery_drop_otp == 1 || $booing_config->delivery_drop_otp == 2)) {
                $delivery_details = BookingDeliveryDetails::where('booking_id', $booking->id)->orderBy('stop_no')->get();
                if (count($delivery_details) > 0) {
                    if ($booing_config->delivery_drop_otp == 1) {
                        $delivery_drop_otp = 1;
                    }
                    if ($booing_config->delivery_drop_otp == 2) {
                        $delivery_drop_qr = 1;
                    }
                    foreach ($delivery_details as $delivery_detail) {
                        if ($delivery_detail->drop_status == 0 && $delivery_detail->otp_status == 0 && $delivery_detail->stop_no != 0) {
                            if ($delivery_drop_otp) {
                                $delivery_otp = $delivery_detail->opt_for_verify;
                            }
                            if ($delivery_drop_qr) {
                                // creating qr code for scan with booking id stop id and otp
                                $delivery_otp = $booking->id . '_' . $delivery_detail->id . '_' . $delivery_detail->opt_for_verify;
                            }
                            break;
                        }
                    }
                    if ($delivery_otp == '') {
                        if ($delivery_drop_otp) {
                            $delivery_otp = $delivery_details[0]->opt_for_verify;
                        }
                        if ($delivery_drop_qr) {
                            $delivery_otp = $booking->id . '_' . $delivery_details[0]->id . '_' . $delivery_details[0]->opt_for_verify;
                        }
                    }
                }
            }
            // we are merging delivery and taxi otp so in case of delivery drop otp will be carried in ride_otp column of booking
            if ($booking->booking_status == 1004) {
                $booking->otp_enable = $delivery_drop_otp;
                // If qr code enable for delivery
                $booking->qr_enable = $delivery_drop_qr;
                $booking->ride_otp = $delivery_otp;
            }
            if($booking->Segment->slag == "TAXI"){

            }
            elseif($booking->Segment->slag == "DELIVERY"){

            }

            //Encrypt Decrypt
            $estBill = $booking->CountryArea->Country->isoCode.' '.$booking->estimate_bill;
            $bookOtp = $booking->ride_otp;
            $fname = $booking->Driver->first_name;
            $lname = $booking->Driver->last_name;
            $phone = $booking->Driver->phoneNumber;
            $email = $booking->Driver->email;
            $fullName = $booking->Driver->first_name . " " . $booking->Driver->last_name;
            $image = $booking->Driver->profile_image;
            if($image){
                $image = get_image($image, 'driver', $merchant_id, true, false);
            }
            if($merchant->Configuration->encrypt_decrypt_enable == 1){
                try {
                    $keys = getSecAndIvKeys();
                    $iv = $keys['iv'];
                    $secret = $keys['secret'];

                    if($estBill){
                        $estBill = encryptText($estBill , $secret, $iv);
                    }

                    if($bookOtp){
                        $bookOtp = encryptText($booking->ride_otp,$secret,$iv);
                    }else{
                        $booking->ride_otp = "0";
                        $bookOtp = encryptText($booking->ride_otp,$secret,$iv);
                    }

                    if(count($sos) > 0){
                        foreach($sos as $sosText){
                            $sosText->number = encryptText($sosText->number,$secret,$iv);
                        }
                        
                    }

                    if($booking->Driver){
                        if($fname){
                            $fname = encryptText($fname,$secret,$iv);
                        }
                        if($lname){
                            $lname = encryptText($lname,$secret,$iv);
                        }
                        if($phone){
                            $phone = encryptText($phone,$secret,$iv);
                        }
                        if($email){
                            $email = encryptText($email,$secret,$iv);
                        }
                        if($fullName){
                            $fullName = encryptText($fullName,$secret,$iv);
                        }
                        if($image){
                            $image = encryptText(get_image($image, 'driver', $merchant_id, true, false),$secret,$iv);
                        }else{
                            $image = encryptText(view_config_image("static-images/driver_prfile.png"),$secret,$iv);
                        }

                    }
                } catch (Exception $e) {
                    echo 'Error: ' . $e->getMessage();
                }
            }
            $return_data = [
                "qr_enable" => $booking->qr_enable,
                "id" => $booking->id,
                "segment_id" => $booking->Segment->id,
                // "estimate_price" => $booking->estimate_bill,
//                "estimate_price" => $estBill,
                "estimate_price" => ($booking->merchant_id != 976) ?$estBill : "",
                "delivery_otp" => $delivery_otp,
                "delivery_drop_otp" => $delivery_drop_otp,
                "drop_longitude" => $booking->drop_latitude,
                "drop_latitude" => $booking->drop_longitude,
                "otp_enable" => $booking->otp_enable,
                "ride_otp" => $bookOtp,
                "ploy_points" => $booking->ploy_points,
                "pickup_latitude" => $booking->pickup_latitude,
                "pickup_longitude" => $booking->pickup_longitude,
                "booking_status" => $booking->booking_status,
                "total_drop_location" => $booking->total_drop_location,
                "share_able_link" => $share_able_link,
                "cancelable" => $cancelable,
                "sos_visibility" => $sos_visibility,
                "shareable" => $shareable,
//                "tip_status" => $tip_status,
                "tip_status" => ($booking->merchant_id != 976) ? $tip_status: false,
                "location" => $location_object,
                "service_type" => [
                    'id' => $booking->service_type_id,
                    'serviceName' => $booking->ServiceType->ServiceName($booking->merchant_id),
                    'type' => $booking->ServiceType->type
                ],
                "sos" => $sos,
                "polydata" => $polydata,
                "still_marker" => $marker,
                "movable_marker" => $driver_marker_type,
                "address_changeable" => true,
                "vehicle_details" => $vehicleDetails,
                "waypoints" => !empty($booking->waypoints) ? json_decode($booking->waypoints, true) : [],
                "payment_editable" => $config->change_payment_method == 1 ? true : false,
                "tip_already_paid" => $show_status,
                "arrive_timestamp" => !empty($free_value) ? $arrive_timestamp : false,
                "free_waiting_time_enable" => !empty($free_value) ? true : false,
                "free_waiting_time" => $free_value,
                "driver" => [
                    'id' => $booking->driver_id,
                    'first_name' => $fname,
                    'last_name' => $lname,
                    'email' => $email,
                    'phoneNumber' => $phone,
                    'current_latitude' => $booking->Driver->current_latitude,
                    'current_longitude' => $booking->Driver->current_longitude,
                    'rating' => $booking->Driver->rating,
                    'merchant_id' => $booking->merchant_id,
                    'fullName' => $fullName,
                    'profile_image' => $image ? $image : view_config_image("static-images/driver_prfile.png"),
                ],
                "payment_method" => [
                    'id' => $booking->payment_method_id,
                    'payment_method' => $booking->PaymentMethod->MethodName($merchant_id) ? $booking->PaymentMethod->MethodName($merchant_id) : $booking->PaymentMethod->payment_method,
                    'payment_icon' => $booking->PaymentMethod->icon,
                ],
                'driver_vehicle' => [
                    'vehicle_color' => $vehicle_color,
                    "vehicle_number_plate_image" => $booking->DriverVehicle ? get_image($booking->DriverVehicle->vehicle_number_plate_image, 'vehicle_document', $merchant_id, true, false) : "",
                ],
                "vehicle_type" => [
                    'vehicleTypeImage' => get_image($booking->VehicleType->vehicleTypeImage, 'vehicle', $merchant_id, true, false),
                ],
                "user_kin_details" => !empty($user->user_kin_details)? json_decode($user->user_kin_details, true): [],
                "eta_pickup_and_dest"=> $eta_pickup_and_dest,
                "working_with_microservices"=> $app_config->working_with_microservices == 1,
            ];

            if($user->Merchant->ApplicationConfiguration->working_with_redis == 1){
                if(!empty($user->user_jwt_token)){
                    $jwt = $user->user_jwt_token;
                }
                else{
                    $jwt = getJwtToken($user->id, "multi-service-v3", "USER");
                    $user->user_jwt_token = $jwt;
                    $user->save();
                }
                $return_data['jwt_token'] = $jwt;
                $return_data['microservice_path'] = env('MICRO_SERVICE_APP_URL');
            }

        } catch (Exception $e) {
            return $this->failedResponse($e->getMessage());
        }

        return $this->successResponse(trans("$string_file.ride"), $return_data);
    }

    public function getFavouriteDrivers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkout' => 'required',
            //            'checkout_id' => ['required_if:checkout,1',
            //                Rule::exists('booking_checkouts', 'id')->where(function ($query) {
            //                    $query->where([['payment_method_id', '!=', 0]]);
            //                })],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }

        $checkout_status = $request->checkout_status;
        $user = $request->user('api');
        $user_id = $user->id;
        $checkout = BookingCheckout::find($request->checkout);
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $drivers = FavouriteDriver::whereHas('Driver', function ($q) use ($checkout, $checkout_status) {
            if ($checkout_status == 1) {
                $q->whereHas('DriverVehicle', function ($q) use ($checkout) {
                    $q->whereHas('ServiceTypes', function ($q) use ($checkout) {
                        $q->where('service_type_id', $checkout->service_type_id);
                    });
                    $q->where('vehicle_type_id', $checkout->vehicle_type_id);
                    $q->where('vehicle_active_status', 1);
                });
                $q->where(['online_offline' => 1, 'login_logout' => 1, 'free_busy' => 2, 'country_area_id' => $checkout->country_area_id]);
            }
            $q->where(function ($qq) {
                $qq->where('driver_delete', '=', NULL);
                $qq->where('driver_admin_status', '=', 1);
            });
        })
            ->where([['user_id', '=', $user_id],['segment_id', '=', $checkout->segment_id]])->get();
        if (empty($drivers) || $drivers->count() == 0) {
            return $this->failedResponse(trans("$string_file.no_favourite_driverss"));
        }
        foreach ($drivers as $value) {
            $value->fullName = $value->Driver->fullName;
            $value->phoneNumber = $value->Driver->phoneNumber;
            $value->profile_image = get_image($value->Driver->profile_image, 'driver', $value->Driver->merchant_id, true, false);
            $value->rating = $value->Driver->rating;
            $value->online_offline = $value->Driver->online_offline;
            $value->free_busy = $value->Driver->free_busy;
            if ($checkout_status == 1) {
                $value->vehicle_type = $value->Driver->DriverVehicle[0]->VehicleType->LanguageVehicleTypeSingle->vehicleTypeName;
                $value->base_fare = 10;
            }
        }
        return response()->json(['result' => "1", 'message' => trans("$string_file.ready_for_ride"), 'data' => $drivers]);
    }

    public function confirmBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'segment_id' => 'required|exists:segments,id',
            'checkout' => [
                'required', 'integer',
                Rule::exists('booking_checkouts', 'id')->where(function ($query) use ($request) {
                    $query->where([['segment_id', '=', $request->segment_id]]);
                    $query->where([['payment_method_id', '!=', 0]]);
                })
            ],
            'question_id' => 'nullable|exists:questions,id',
            'fav_driver_id' => 'nullable|exists:drivers,id',
            'answer' => 'required_with:question_id',
            'offer_amount' => 'required_if:is_in_drive,1',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $checkout = BookingCheckout::find($request->checkout);
            $booking_config = BookingConfiguration::select('multiple_rides')->where("merchant_id", $checkout->merchant_id)->first();
            $checkout->additional_notes = $request->additional_notes;
            if (isset($request->is_in_drive) && $request->is_in_drive == 1) {
                $checkout->is_in_drive = 1;
                $checkout->offer_amount = $request->offer_amount;
            }
            if(isset($request->for_additional_user_details) && $request->for_additional_user_details == true){
                $checkout->additional_user_details = json_encode(array("user_name" => $request->user_name, "user_number" => $request->user_number));
            }
            $checkout->save();
            $string_file = $this->getStringFile($checkout->merchant_id);

            // booking data object
            $bookingData = new BookingDataController();
            if (isset($request->family_member_id)) {
                $checkout->family_member_id = $request->family_member_id;
                $checkout->save();
            }

            if ($this->CheckWalletBalance($checkout) == 1) {
                // check transaction table
                if (!empty($checkout->card_id)) {
                    $transaction = DB::table("transactions")->select("id", "booking_id", "reference_id", "checkout_id")
                        ->where([["reference_id", "=", $checkout->user_id], ["card_id", "=", $checkout->card_id], ["checkout_id", "=", $checkout->id], ["merchant_id", "=", $checkout->merchant_id], ["status", "=", 1]])->first();
                    if (empty($transaction)) {
                        $request->request->add(['card_id' => $checkout->card_id]);
                        $this->checkPaymentGatewayStatus($request, $checkout, $string_file);
                    }
                }

                if (!empty($request->fav_driver_id)) {
                    $fav_driver = Driver::where('id', '=', $request->fav_driver_id)->where('online_offline', 1)->where('driver_delete', NULL)->first();
                    if (empty($fav_driver) || $fav_driver->free_busy == 1) {
                        throw new Exception(trans("$string_file.no_driver_available"));
                    } else {
                        $fav_driver->driver_id = $request->fav_driver_id;
                        $drivers = array($fav_driver);
                        $Bookingdata = $checkout->toArray();
                        unset($Bookingdata['id']);
                        unset($Bookingdata['user']);
                        unset($Bookingdata['created_at']);
                        unset($Bookingdata['updated_at']);
                        if(isset($Bookingdata['discounted_amount'])){
                            unset($Bookingdata['discounted_amount']);
                        }
                        if(isset($Bookingdata['automatic_promo_applied'])){
                            unset($Bookingdata['automatic_promo_applied']);
                        }
                        if(isset($Bookingdata['default_promo_applied'])){
                            unset($Bookingdata['default_promo_applied']);
                        }
                        if(isset($Bookingdata['user_subscription_record_id']) || array_key_exists('user_subscription_record_id', $Bookingdata) ){
                            $subscription_record = UserSubscriptionRecord::find($Bookingdata['user_subscription_record_id']);
                            if(!empty($subscription_record)){
                                $subscription_record->used_trips = $subscription_record->used_trips +1;
                                $subscription_record->save();
                            }
                            unset($Bookingdata['user_subscription_record_id']);
                        }
                        $Bookingdata['booking_timestamp'] = time();
                        $Bookingdata['booking_status'] = 1001;
                        $booking = Booking::create($Bookingdata);
                        // $newdriver = new FindDriverController();
                        // $newdriver->AssignRequest($drivers, $booking->id);
                        // $message = trans("$string_file.new_ride");
                        // //                        $message = $bookingData->LanguageData($booking->merchant_id, 25);
                        // $bookingData->SendNotificationToDrivers($booking, $drivers, $message);

                        // $arr_return = [
                        //     'message' => trans("$string_file.ride_booked"),
                        //     'data' => $booking
                        // ];
                        // $booking = $arr_return;
                        //return ['result' => "1", 'message' => trans("$string_file.ride_booked"), 'data' => $booking];
                    }
                } else {
                    if (!empty($request->question_id) && !empty($request->answer)) {
                        $QuestionUser = QuestionUser::where([['question_id', '=', $request->question_id], ['answer', '=', $request->answer], ['user_id', '=', $request->user('api')->id]])->first();
                        if (empty($QuestionUser)) {
                            //                            throw new Exception(trans('api.answerwrong'));
                        }
                    }
                    if(isset($checkout->user_subscription_record_id)){
                        $subscription_record = UserSubscriptionRecord::find($checkout->user_subscription_record_id);
                        $subscription_record->used_trips = $subscription_record->used_trips +1;
                        $subscription_record->save();
                    }
                    if($booking_config->multiple_rides == 2){
                        $on_going_rides = Booking::whereIn("booking_status", [1001,1002,1003, 1004])->where("user_id", $checkout->user_id)->count();
                        if($on_going_rides > 0){
                            return $this->failedResponse(trans("$string_file.ongoing_ride"));
                        }
                    }
                    switch ($checkout->ServiceType->type) {
                        case "1":
                            $newBooking = new NormalController();
                            if ($checkout->booking_type == 1) {
                                $booking = $newBooking->currentBookingAssign($checkout);
                            } else {
                                $booking = $newBooking->laterBookingAssign($checkout);
                            }
                            break;
                        case "2":
                            $newBooking = new RentalController();
                            if ($checkout->booking_type == 1) {
                                $booking = $newBooking->currentBookingAssign($checkout);
                            } else {
                                $booking = $newBooking->laterBookingAssign($checkout);
                            }
                            break;
                        //                    case "3":
                        //                        $newBooking = new TransferController();
                        //                        if ($checkout->booking_type == 1) {
                        //                            $booking = $newBooking->CurrentBookingAssign($checkout);
                        //                        } else {
                        //                            $booking = $newBooking->LeterBookingAssign($checkout);
                        //                        }
                        //                        break;
                        case "4":
                            $newBooking = new OutstationController();
                            if ($checkout->booking_type == 1) {
                                $booking = $newBooking->currentBookingAssign($checkout);
                            } else {
                                $booking = $newBooking->bookingAssign($checkout);
                            }
                            break;
                        case "5":
                            $newBooking = new PoolController();
                            $booking = $newBooking->Booking($checkout);
                    }
                    $merchant_id = $request->user('api')->merchant_id;
                    $SmsConfiguration = SmsConfiguration::select('ride_book_enable', 'ride_book_msg')->where([['merchant_id', '=', $merchant_id]])->first();
                    if (!empty($SmsConfiguration) && $SmsConfiguration->ride_book_enable == 4 && $SmsConfiguration->ride_book_msg) {
                        $sms = new SmsController();
                        $phone = $request->user('api')->UserPhone;
                        $sms->SendSms($merchant_id, $phone, null, 'RIDE_BOOK', $request->user('api')->email);
                    }
                }
            } elseif ($this->CheckWalletBalance($checkout) == 2) {
                return $this->failedResponse(trans("$string_file.low_wallet_warning"));
            } else {
                return $this->failedResponse(trans("$string_file.wallet_low_estimate"));
            }

            $data = $booking['data'];
            // update transaction table
            if (!empty($data->card_id)) {
                $transaction = DB::table("transactions")->select("id", "booking_id", "reference_id", "checkout_id")
                    ->where([["reference_id", "=", $data->user_id], ["card_id", "=", $data->card_id], ["checkout_id", "=", $checkout->id], ["merchant_id", "=", $data->merchant_id], ["status", "=", 1]])->first();
                if (!empty($transaction->id)) {
                    DB::table("transactions")->where("id", $transaction->id)->update(["booking_id" => $data->id]);
                } else {
                    $newArray = BookingCheckout::find($request->checkout);
                    $this->checkPaymentGatewayStatus($request, $newArray, $string_file);
                }
            }
            //            will delete checkout in accept api because we have to give retry button if driver don't accept ride
            //            $checkout->delete();
            // will delete checkout if it is in-drive checkout
            if ($checkout->is_in_drive == 1) {
                $checkout->delete();
            }
            DB::commit();

            if(!empty($request->fav_driver_id)){
                $newdriver = new FindDriverController();
                        $newdriver->AssignRequest($drivers, $booking->id);
                        $message = trans("$string_file.new_ride");
                        $bookingData->SendNotificationToDrivers($booking, $drivers, $message);

                        $arr_return = [
                            'message' => trans("$string_file.ride_booked"),
                            'data' => $booking
                        ];
                        $booking = $arr_return;
            }else{
                if(isset($booking['is_later_booking']) && $booking['is_later_booking'] && isset($booking['data']) && isset($booking['drivers']) && $booking['data'] && $booking['drivers']){
                    $bookingData = new BookingDataController();
                    $bookingData->SendNotificationToDrivers($booking['data'], $booking['drivers']);
                }elseif(isset($booking['data']) && isset($booking['drivers']) && $booking['data'] && $booking['drivers']){
                    // send booking request to driver entry
                    $findDriver = new FindDriverController();
                    $findDriver->AssignRequest($booking['drivers'],$booking['data']['id']);
                    $bookingData = new BookingDataController();
                    $bookingData->SendNotificationToDrivers($booking['data'], $booking['drivers']);
                }
                 
            }
        } catch (Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        // for clover payment capture
        if (!empty($request->payment_capture_id)) {
            DB::table('transactions')->where([['user_id', '=', $data->user_id], ['reference_id', '=', $request->payment_capture_id]])->update([
                'booking_id' => $data->id,
            ]);
        }


        $message = $booking['message'];
        $return_data = [
            'id' => $data->id,
            'booking_type' => $data->booking_type,
            'merchant_booking_id' => $data->merchant_booking_id,
            'ride_radius_increase_api_call_time' => 10
        ];

        // inset booking status history
        $this->saveBookingStatusHistory($request, $data, $data->id);

        // Send new ride request mail to merchant
        $bookingData->sendBookingMail($data);

        return $this->successResponse($message, $return_data);
    }

    public function inDriveBookingConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
            'driver_id' => 'required|integer|exists:drivers,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $booking = Booking::find($request->booking_id);
            $driver = Driver::find($request->driver_id);
            $string_file = $this->getStringFile(NULL, $user->Merchant);

            $accepted_driver_count = BookingRequestDriver::where([['booking_id', '=', $booking->id], ['request_status', '=', 2]])->get()->count();
            if ($accepted_driver_count > 0) {
                throw new Exception(trans("$string_file.ride_already"));
            }

            $bdr = BookingBiddingDriver::where([["booking_id", "=", $booking->id], ["driver_id", "=", $request->driver_id]])->first();
            if (!empty($bdr)) {
                $booking->driver_id = $request->driver_id;
                $booking->price_for_ride_amount = $bdr->status == 2 ? $bdr->amount : $booking->offer_amount;
                $booking->price_for_ride = 2;
                $booking->estimate_bill = $bdr->status == 2 ? $bdr->amount : $booking->offer_amount;
                $booking->save();


                if ($booking->booking_type == 1 && $driver->free_busy == 2 && $driver->online_offline == 1 && $driver->login_logout == 1) {
                    $driver->free_busy = 1;
                    $driver->save();

                    $message_otp = '';
                    if ($booking->Merchant->BookingConfiguration->ride_otp == 1) :
                        $booking->ride_otp = rand(1000, 9999);
                        $booking->ride_otp_verify = 1;
                        // send otp on whatsapp message
                        if ($booking->platform == 3) {
                            $message_otp = '. ' . trans("$string_file.ride_start_otp") . ' ' . trans("$string_file.id") . ' #' . $booking->merchant_booking_id . ': ' . $booking->ride_otp;
                        }
                    endif;

                    $booking->booking_status = 1002;
                    $booking->driver_vehicle_id = $bdr->driver_vehicle_id; // coming from store online config
                    $booking->driver_id = $driver->id;
                    $booking->unique_id = uniqid();
                    $booking->ploy_points = "";
                    $booking->save();

                    $this->saveBookingStatusHistory($request, $booking, $booking->id);

                    BookingRequestDriver::where([['booking_id', '=', $booking->id], ['driver_id', '=', $driver->id]])->update(['request_status' => 2]);
                    $cancelDriver = BookingRequestDriver::with(['Driver' => function ($q) use ($driver) {
                        return $q->select("*", "id as driver_id")->where('player_id', '!=', $driver->player_id);
                    }])->whereHas('Driver', function ($q) use ($driver) {
                        return $q->where('player_id', '!=', $driver->player_id);
                    })->where([['booking_id', '=', $booking->id], ['request_status', '=', 1], ['driver_id', '!=', $driver->id]])->get();
                    $cancelDriver = array_pluck($cancelDriver, 'Driver');
                    $bookingData = new BookingDataController();

                    foreach ($cancelDriver as $key => $value) {
                        $newDriver = Driver::select('id', 'last_ride_request_timestamp')->find($value->id);
                        $newDriver->last_ride_request_timestamp = date("Y-m-d H:i:s", time() - 100);
                        $newDriver->save();
                    }
                    //                $user_id = $booking->user_id;
                    BookingDetail::create([
                        'booking_id' => $booking->id,
                        'accept_timestamp' => strtotime('now'),
                        'accept_latitude' => $request->latitude,
                        'accept_longitude' => $request->longitude,
                        'accuracy_at_accept' => $request->accuracy,
                    ]);

                    if (!empty($cancelDriver)) {
                        $bookingData->SendNotificationToDrivers($booking, $cancelDriver);
                    }
                    $bookingData->SendNotificationToDrivers($booking, []);
                    // $notification_data = $bookingData->bookingNotificationForUser($booking, "ACCEPT_BOOKING", $message_otp);

                    $booking = $bookingData->driverBookingDetails($booking->id, true, $request);
                    $SmsConfiguration = SmsConfiguration::select('ride_accept_enable')->where([['merchant_id', '=', $user->merchant_id]])->first();
                    if (!empty($SmsConfiguration) && $SmsConfiguration->ride_accept_enable == 2) {
                        $sms = new SmsController();
                        $phone = $booking->User->UserPhone;
                        $sms->SendSms($user->merchant_id, $phone, null, 'RIDE_ACCEPT', $booking->User->email);
                    }
                } elseif ($booking->booking_type == 2 && $driver->login_logout == 1) {
                    $booking_later_booking_date_time = $booking->later_booking_date . " " . $booking->later_booking_time;
                    $current_DateTime = convertTimeToUSERzone(date('Y-m-d H:i'), $booking->CountryArea->timezone, null, $booking->Merchant);
                    if (strtotime($booking_later_booking_date_time) <= strtotime($current_DateTime)) {
                        $booking_status = "1016";
                        $booking->save();
                        // inset booking status history
                        $this->saveBookingStatusHistory($request, $booking, $booking->id);
                        $data = array('booking_id' => $booking->id, 'booking_status' => $booking_status);
                        return $this->failedResponse(trans("$string_file.ride_expired"), $data);
                    }

                    $driverPartial = Booking::where([['driver_id', '=', $driver->id]])->where('booking_type', 2)->whereIn("booking_status", [1002, 1003, 1004, 1012])->get();
                    $time = $booking->Merchant->BookingConfiguration->partial_accept_hours;
                    $partial_accept_before_minutes = $booking->Merchant->BookingConfiguration->partial_accept_before_hours;
                    $bookingTimeString = $booking->later_booking_date . " " . $booking->later_booking_time;

                    // convert to date object
                    $bookingTime = new DateTime($bookingTimeString);
                    $endbookingTime = new DateTime($bookingTimeString);

                    // check for partial accept before hours
                    //            $current_DateTime = new \DateTime();
                    //            $minutes = (strtotime($bookingTime->format("Y-m-d H:i")) - strtotime($current_DateTime->format("Y-m-d H:i"))) / 60;

                    // current time according to time zone
                    $current_DateTime = convertTimeToUSERzone(date('Y-m-d H:i'), $booking->CountryArea->timezone, null, $booking->Merchant);
                    $minutes = (strtotime($bookingTime->format("Y-m-d H:i")) - strtotime($current_DateTime)) / 60;
                    if ($booking->Merchant->BookingConfiguration->ride_later_ride_allocation != 2 && $minutes > $partial_accept_before_minutes) {
                        $config_time = date(getDateTimeFormat(2), strtotime('-' . $partial_accept_before_minutes . 'minutes', strtotime($bookingTime->format("Y-m-d H:i:s"))));
                        $message = trans("$string_file.ride_later_accept_warning") . $config_time;
                        return $this->failedResponse($message);
                    }

                    // add estimate time + time difference between ride
                    $endbookingTime->modify("+{$booking->estimate_time}");
                    $endbookingTime->modify("+{$time} mins");

                    // convert to time string
                    $bookingTime = $bookingTime->format("Y-m-d H:i");
                    $endbookingTime = $endbookingTime->format("Y-m-d H:i");

                    foreach ($driverPartial as $value) {
                        $bookingtimestamp = $value->later_booking_date . " " . $value->later_booking_time;

                        // Accepted ride time to date object
                        $DateTime = new DateTime($bookingtimestamp);
                        $oldDateTime = new DateTime($bookingtimestamp);
                        if ($value->estimate_time) {
                            // add estimate time to date object
                            $oldDateTime->modify("+{$value->estimate_time}");
                        }

                        // add time difference between ride
                        $oldDateTime->modify("+{$time} mins");
                        // convert to time string
                        $newDate = $DateTime->format("Y-m-d H:i");
                        $oldDate = $oldDateTime->format("Y-m-d H:i");

                        // Condition check for active ride or booking ride time and date conflicts
                        if ($bookingTime >= $oldDate && $endbookingTime >= $newDate || $bookingTime <= $oldDate && $endbookingTime <= $newDate) {
                            continue;
                        } else {
                            return $this->failedResponse(trans("$string_file.already_activated_booking", ['time' => $bookingtimestamp]));
                        }
                    }
                    // If booking accept by taxi company driver
                    if ($driver->taxi_company_id != NULL && $booking->Merchant->Configuration->company_admin == 1) {
                        $booking->taxi_company_id = $driver->taxi_company_id;
                    }
                    $booking->booking_status = 1012;
                    $booking->driver_vehicle_id = $bdr->driver_vehicle_id; // coming from store online config
                    $booking->driver_id = $driver->id;
                    $booking->save();

                    // inset booking status history
                    $this->saveBookingStatusHistory($request, $booking, $booking->id);

                    //$user_id = $booking->user_id;
                    $bookingData = new BookingDataController();
                    //$message = $bookingData->LanguageData($booking->merchant_id, 30);
                    // $data = $bookingData->BookingNotification($booking);
                    BookingRequestDriver::create([
                        'booking_id' => $request->booking_id,
                        'driver_id' => $driver->id,
                        'distance_from_pickup' => 0,
                        'request_status' => 1
                    ]);
                    $bookingData->SendNotificationToDrivers($booking, []);
                } else {
                    throw new \Exception(trans("$string_file.driver_not_available"));
                }
            } else {
                throw new \Exception(trans("$string_file.not_found"));
            }
            DB::commit();
            return $this->successResponse(trans("$string_file.success"));
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function CheckWalletBalance($data)
    {
        $merchantData = PriceCard::select('minimum_wallet_amount')->find($data->price_card_id);
        $set_amount = empty($merchantData['minimum_wallet_amount']) ? 0 : $merchantData['minimum_wallet_amount'];
        $user_amount = empty($data->User->wallet_balance) ? 0 : $data->User->wallet_balance;
        $config = Configuration::select('user_wallet_status')->where('merchant_id', '=', $data->merchant_id)->first();
        $merchantConfig = Merchant::select('cancel_amount_deduct_from_wallet')->find($data->merchant_id);
        if ($data) {
            switch ($data->payment_method_id) {
                case '3':
                    if ($data->corporate_id != null) {
                        if ($data->User->Corporate->wallet_balance >= $data->estimate_bill) {
                            return 1;
                        } else {
                            return 0;
                        }
                    } else {
                        if ($user_amount >= $data->estimate_bill) {
                            return 1;
                        } else {
                            return 0;
                        }
                    }
                    break;
                case '1':
                    if ($config->user_wallet_status == 1 && $merchantConfig->cancel_amount_deduct_from_wallet == 1) {
                        if ($user_amount >= $set_amount) {
                            return 1;
                        } else {
                            return 2;
                        }
                        break;
                    } else {
                        return 1;
                    }
                default:
                    return 1;
                    break;
            }
        }
    }

    public function checkPaymentGatewayStatus($request, $newArray, $string_file = "")
    {
        try {
            $card = UserCard::select('id', 'payment_option_id', 'card_number', 'token')->find($request->card_id);
            $payment_option = PaymentOptionsConfiguration::where([['payment_option_id', '=', $card->payment_option_id], ['merchant_id', '=', $newArray->merchant_id]])->first();
            if ($payment_option->PaymentOption->slug == "PAYU" && $payment_option->payment_step > 1) {
                $user = $request->user('api');
                $locale = $request->header('locale');
                $amount = $newArray->estimate_bill;
                // initiate payment card authorise
                $payment = new RandomPaymentController;
                $payment_data = $payment->payuPaymentAuthorization($user, $amount, $card, $payment_option, $locale);
                if (isset($payment_data['code']) && $payment_data['code'] == "SUCCESS" && $payment_data['transactionResponse']['state'] == "APPROVED") {
                    // entry in transactions table
                    DB::table('transactions')->insert([
                        'status' => 1, // for user
                        'reference_id' => $user->id,
                        'card_id' => $card->id,
                        'merchant_id' => $newArray->merchant_id,
                        'payment_option_id' => $card->payment_option_id,
                        'checkout_id' => $newArray->id,
                        'payment_transaction' => json_encode($payment_data),
                    ]);
                } elseif (isset($payment_data['code']) && $payment_data['code'] == "SUCCESS" && $payment_data['transactionResponse']['state'] == "DECLINED") {
                    // $payment->payuPaymentVoid($booking->User, $amount, $booking->UserCard,$payment_config,$locale,$transaction);
                    $message = isset($payment_data['transactionResponse']['paymentNetworkResponseErrorMessage']) ? $payment_data['transactionResponse']['paymentNetworkResponseErrorMessage'] : $payment_data['transactionResponse']['responseCode'];
                    throw new Exception(trans("$string_file.payment_failed") . ' : ' . $message);
                } else {
                    $message = isset($payment_data['error']) ? $payment_data['error'] : "";
                    throw new Exception(trans("$string_file.payment_failed") . ' : ' . $message);
                }
            }
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // its uses when payment method is selecting while checkout
    public function checkoutPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkout' => 'required|integer|exists:booking_checkouts,id',
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'payment_option_id' => 'required_if:payment_method_id,4',
            'card_id' => 'required_if:payment_option,2',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
            // return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        DB::beginTransaction();
        try {
            $string_file = $this->getStringFile($request->merchant_id);
            // check user number is payment option is payphone
            if (!empty($request->payment_option_id) && $request->payment_method_id == 4) {
                $option = PaymentOption::select('slug', 'id')->where('id', $request->payment_option_id)->first();
                if (!empty($option) && $option->slug == 'PAYPHONE') {
                    $payment_option_config = $option->PaymentOptionConfiguration;
                    $payphone = new PayPhoneController;
                    $payphone->validateUser($request, $payment_option_config, $string_file);
                }
            }
            $newArray = BookingCheckout::find($request->checkout);
            // check wallet balance with estimate
            $estimate_amount = $newArray->estimate_bill;
            if ($request->payment_method_id == 3) {
                if ($request->is_business_trip == true && !empty($newArray->User->Corporate)) {
                    if ($estimate_amount > $newArray->User->Corporate->wallet_balance) {
                        $message = trans_choice("$string_file.low_wallet_warning", 3, ['AMOUNT' => $estimate_amount]);
                        return $this->failedResponse($message);
                    }
                } elseif ($request->is_business_trip != true && ($estimate_amount > $newArray->User->wallet_balance)) {
                    $message = trans_choice("$string_file.low_wallet_warning", 3, ['AMOUNT' => $estimate_amount]);
                    return $this->failedResponse($message);
                }
            }

            // check card status on payu payment
            if (!empty($request->card_id)) {
                $this->checkPaymentGatewayStatus($request, $newArray);
            }
            $newArray->payment_method_id = $request->payment_method_id;
            // check card has enough balance for ride or not
            $newArray->payment_option_id = $request->payment_option_id;
            if (!empty($request->card_id)) {
                $card = new CardController();
                $response = $card->checkUserCardBalance($request->card_id, ($newArray->estimate_bill ?? 0) * 2);
                if (!$response['status']) {
                    return $this->failedResponse($response['message']);
                }
            }
            $newArray->card_id = !empty($request->card_id) ? $request->card_id : NULL;
            $newArray->save();
            DB::commit();
            $bookingData = new BookingDataController();
            if ($newArray->Segment->slag == "DELIVERY") {
                $result = new DeliveryCheckoutResource($newArray);
            } else {
                $result = $bookingData->CheckOut($newArray);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        return $this->successResponse(trans("$string_file.ready_for_ride"), $result);
        // return response()->json(['result' => "1", 'message' => trans("$string_file.ready_for_ride"), 'data' => $result]);
    }


    //    public function Checkout(Request $request)
    //    {
    //        $log_data = array(
    //            'request_type' => 'Checkout Request',
    //            'data' => $request->all(),
    //            'additional_notes' => 'checkout api'
    //        );
    //        booking_log($log_data);
    //        $user = $request->user('api');
    //        $config = Configuration::where('merchant_id', $request->user('api')->merchant_id)->first();
    //        if (isset($config->user_outstanding_enable) && $config->user_outstanding_enable == 1) {
    //            // Check for previous booking outstanding.
    //            $result = $this->checkBookingOutstanding($user->id);
    //            if (!empty($result)) {
    //                $string_file = $this->getStringFile(NULL, $user->Merchant);
    //                return $this->successResponse(trans("$string_file.data_found"), $result);
    //            }
    //        }
    //
    //        $validator = Validator::make($request->all(), [
    //            'segment_id' => 'required|integer|exists:segments,id',
    //            'area' => 'required|integer|exists:country_areas,id',
    //            'service_type' => 'required|integer|exists:service_types,id',
    //            'vehicle_type' => 'required_if:service_type,1|required_if:service_type,2,required_if:service_type,3|required_if:service_type,4',
    //            'pickup_latitude' => 'required',
    //            'pickup_longitude' => 'required',
    //            'pick_up_locaion' => 'required',
    //            'booking_type' => 'required|integer|in:1,2',
    //            'service_package_id' => 'required_if:service_type,2|required_if:service_type,3',
    //            'later_date' => 'required_if:booking_type,2',
    //            'later_time' => 'required_if:booking_type,2',
    //            'total_drop_location' => 'required|integer|between:0,4',
    //            'drop_location' => 'required_if:total_drop_location,1,2,3,4',
    //        ]);
    //        if ($validator->fails()) {
    //            $errors = $validator->messages()->all();
    //            return $this->failedResponse($errors[0]);
    //        }
    //        DB::beginTransaction();
    //        try {
    //            $service = ServiceType::select('id', 'type')->Find($request->service_type);
    //            switch ($service->type) {
    //                case "1":
    //                    $normalBooking = new NormalController();
    //                    if ($request->booking_type == 1) {
    //                        $booking = $normalBooking->CurrentBookingCheckout($request);
    //                    } else {
    //                        $booking = $normalBooking->LaterBookingCheckout($request);
    //                    }
    //                    break;
    //                case "2":
    //                    $rentalBooking = new RentalController();
    //                    if ($request->booking_type == 1) {
    //                        $booking = $rentalBooking->CurrentBookingCheckout($request);
    //                    } else {
    //                        $booking = $rentalBooking->LaterBookingCheckout($request);
    //                    }
    //                    break;
    //                case "3":
    //                    $transferBooking = new TransferController();
    //                    if ($request->booking_type == 1) {
    //                        $booking = $transferBooking->CurrentBookingCheckout($request);
    //                    } else {
    //                        $booking = $transferBooking->LaterBookingCheckout($request);
    //                    }
    //                    break;
    //                case "4":
    //                    $outstation = new OutstationController();
    //                    $booking = $outstation->CheckOut($request);
    //                    break;
    //                case "5":
    //                    $newBooking = new PoolController();
    //                    $booking = $newBooking->CreateCheckout($request);
    //            }
    //        } catch (\Exception $e) {
    //            DB::rollback();
    //            return $this->failedResponse($e->getMessage());
    //        }
    //        DB::commit();
    //
    //        $log_data = array(
    //            'request_type' => 'Checkout Response',
    //            'data' => $booking,
    //            'additional_notes' => 'checkout api'
    //        );
    //        booking_log($log_data);
    //        return $this->successResponse($booking['message'], $booking['data']);
    //    }

    // to book user ride with current and drop location
    // book now and book later now : 1, later : 2
    public function Checkout(Request $request)
    {
        //        @Note by Amba
        //        Checkout is creating in 2 steps and same function is calling for that
        //        because in step 2 user can change pickup location so need to recalculate distance and driver availability


        $validator = Validator::make($request->all(), [
            'segment_id' => 'required|integer|exists:segments,id',
            'area' => 'required|integer|exists:country_areas,id',
            'service_type' => 'required|integer|exists:service_types,id',
            'vehicle_type' => 'required_if:service_type,1|required_if:service_type,2,required_if:service_type,3|required_if:service_type,4',
            'pickup_latitude' => 'required',
            'pickup_longitude' => 'required',
            'pick_up_location' => 'required',
            'total_drop_location' => 'required|integer|between:0,4',
            'drop_location' => 'required_if:total_drop_location,1,2,3,4',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $log_data = array(
            'request_type' => 'Checkout Request',
            'data' => $request->all(),
            'additional_notes' => 'checkout api'
        );
        booking_log($log_data);
        $user = $request->user('api');
        $config = Configuration::where('merchant_id', $request->user('api')->merchant_id)->first();
        if (isset($config->user_outstanding_enable) && $config->user_outstanding_enable == 1) {
            // Check for previous booking outstanding.
            $result = $this->checkBookingOutstanding($user->id, $request->user('api')->merchant_id);
            if (!empty($result)) {
                $string_file = $this->getStringFile(NULL, $user->Merchant);
                return $this->successResponse(trans("$string_file.data_found"), $result);
            }
        }
        DB::beginTransaction();
        try {
            $service = ServiceType::select('id', 'type')->Find($request->service_type);
            switch ($service->type) {
                case "1":
                    //                    $validator = Validator::make($request->all(), [
                    //                        'segment_id' => 'required|integer|exists:segments,id',
                    //                        'area' => 'required|integer|exists:country_areas,id',
                    //                        'service_type' => 'required|integer|exists:service_types,id',
                    //                        'vehicle_type' => 'required_if:service_type,1|required_if:service_type,2,required_if:service_type,3|required_if:service_type,4',
                    //                        'pickup_latitude' => 'required',
                    //                        'pickup_longitude' => 'required',
                    //                        'pick_up_location' => 'required',
                    //                        'booking_type' => 'required|integer|in:1,2',
                    //                        'service_package_id' => 'required_if:service_type,2|required_if:service_type,3',
                    //                        'later_date' => 'required_if:booking_type,2',
                    //                        'later_time' => 'required_if:booking_type,2',
                    //                        'total_drop_location' => 'required|integer|between:0,4',
                    //                        'drop_location' => 'required_if:total_drop_location,1,2,3,4',
                    //                    ]);
                    //                    if ($validator->fails()) {
                    //                        $errors = $validator->messages()->all();
                    //                        return $this->failedResponse($errors[0]);
                    //                    }
                    $normalBooking = new NormalController();
                    //                    if ($request->booking_type == 1) {
                    $booking = $normalBooking->CurrentBookingCheckout($request);
                    //                    } else {
                    //                        $booking = $normalBooking->LaterBookingCheckout($request);
                    //                    }
                    break;
                case "2":
                    $rentalBooking = new RentalController();
                    if ($request->booking_type == 1) {
                        $booking = $rentalBooking->CurrentBookingCheckout($request);
                    } else {
                        $booking = $rentalBooking->LaterBookingCheckout($request);
                    }
                    break;
                case "3":
                    $transferBooking = new TransferController();
                    if ($request->booking_type == 1) {
                        $booking = $transferBooking->CurrentBookingCheckout($request);
                    } else {
                        $booking = $transferBooking->LaterBookingCheckout($request);
                    }
                    break;
                case "4":
                    $outstation = new OutstationController();
                    $booking = $outstation->CheckOut($request);
                    break;
                case "5":
                    $newBooking = new PoolController();
                    $booking = $newBooking->CreateCheckout($request);
            }
        } catch (Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        $log_data = array(
            'request_type' => 'Checkout Response',
            'data' => $booking,
            'additional_notes' => 'checkout api'
        );
        booking_log($log_data);
        return $this->successResponse($booking['message'], $booking['data']);
    }

    public function userTracking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $booking = Booking::select('id','segment_id', 'vehicle_type_id', 'total_drop_location', 'service_type_id', 'driver_id', 'merchant_id', 'booking_status', 'pickup_location', 'pickup_latitude', 'pickup_longitude', 'waypoints', 'drop_latitude', 'drop_longitude', 'drop_location', 'country_area_id', 'ploy_points', 'total_distance_estimated', 'direction_data_updated')
                ->with(['Driver' => function ($query) {
                    $query->select('id','merchant_id','first_name', 'last_name', 'email', 'phoneNumber', 'profile_image', 'rating', 'current_latitude', 'current_longitude', 'bearing');
                }, 'Segment'])
                ->find($request->booking_id);
            // $booking->Driver->fullName = $booking->Driver->fullName;
            $merchant = Merchant::select('id')->find($booking->merchant_id);
            $string_file = $this->getStringFile(NULL, $booking->Merchant);
            $app_config = $merchant->ApplicationConfiguration;
            $config = $merchant->BookingConfiguration;
            $booking_status = $booking->booking_status;
            $marker_type = "PICK";
            $marker_lat = $booking->pickup_latitude;
            $marker_long = $booking->pickup_longitude;
            $location_editable = false;
            $tip_status = false;
            $bookingData = new BookingDataController();
            $dropLocation = $bookingData->NextLocation($booking->waypoints, $string_file);
            if (!empty($dropLocation) && $dropLocation['last_location'] == 1) {
                $drop_latitude = $dropLocation['drop_latitude'];
                $drop_longitude = $dropLocation['drop_longitude'];
                $drop_location = $dropLocation['drop_location'];
            } else {
                $drop_latitude = $booking->drop_latitude;
                $drop_longitude = $booking->drop_longitude;
                $drop_location = $booking->drop_location;
            }
            $est_driver_time = "";
            $est_driver_distance = "";
            $est_driver_distance_in_meter = "";
            $poly_line = $booking->ploy_points;
            
            $driver = !empty($booking->Driver) ? $booking->Driver : NULL;
            $driver_vehicle = !empty($driver) ? $driver->DriverVehicle[0] : NULL;
            $driver_full_name = !empty($driver)? $driver->first_name.' '.$driver->last_name: "";
            $driver_image = !empty($driver) ? get_image($driver->profile_image, 'driver', $driver->merchant_id, true, false): "";
            $vehicle_type_name = !empty($driver_vehicle)? $driver_vehicle->VehicleType->getVehicleTypeNameAttribute(): "";
            $vehicle_color = !empty($driver_vehicle)? $driver_vehicle->vehicle_color: "";
            $vehicle_number = !empty($driver_vehicle)? $driver_vehicle->vehicle_number: "";
            $vehicle_image = !empty($driver_vehicle)? get_image($driver_vehicle->vehicle_image, 'vehicle_document', $booking->merchant_id, true, false): "";

            switch ($booking_status) {
                case "1001":
                    $trip_status_text = trans("$string_file.accept_ride");
                    //                    $trip_status_text = $bookingData->LanguageData($booking->merchant_id, 1);
                    $location = $booking->pickup_location;
                    $location_color = "2ecc71";
                    $location_headline = trans("$string_file.pickup");
                    //                    $location_headline = $bookingData->LanguageData($booking->merchant_id, 6);
                    $cancelable = true;
                    break;
                case "1002":
                    $trip_status_text = trans("$string_file.arrived_pickup");
                    //                    $trip_status_text = $bookingData->LanguageData($booking->merchant_id, 12);
                    $location = $booking->pickup_location;
                    $location_color = "2ecc71";
                    $location_headline = trans("$string_file.pickup");
                    //                    $location_headline = $bookingData->LanguageData($booking->merchant_id, 6);
                    $cancelable = true;
                    if ($merchant->BookingConfiguration->driver_eta_est_distance == 1) {
                        $eta = $this->EtaCalculation($booking);
                        $est_driver_time = $eta['time'];
                        $est_driver_distance = $eta['distance'];
                        $est_driver_distance_in_meter = $eta['distance_in_meter'];
                        // $poly_line = isset($eta['poly_point'])? $eta['poly_point'] : "";
                    }
                    $marker_lat = $booking->pickup_latitude;
                    $marker_long = $booking->pickup_longitude;
                    break;
                case "1003":
                    $tip_status = ($app_config->tip_status == 1) ? true : false;
                    $trip_status_text = trans("$string_file.started_from_pickup");
                    //                    $trip_status_text = $bookingData->LanguageData($booking->merchant_id, 13);
                    $location = $drop_location;
                    // $location_editable = ($booking->service_type_id == 1 && $booking->total_drop_location <= 1) ? true : false;
                    $location_editable = false;

                    if ($booking->Segment->slag == "DELIVERY" && isset($config->location_editable) && ($config->location_editable == 2 || $config->location_editable == 3)) {
                        $location_editable = $booking->total_drop_location <= 1 ? true : false;
                    }
                    elseif($booking->Segment->slag == "TAXI" && isset($config->location_editable) && ($config->location_editable == 1 || $config->location_editable == 3)){
                        $location_editable = ($booking->total_drop_location <= 1) ? true : false; // $booking->service_type_id == 1 &&
                    }
                    $location_color = "e74c3c";
                    $location_headline = trans("$string_file.drop");
                    //                    $location_headline = $bookingData->LanguageData($booking->merchant_id, 7);
                    $marker_type = "Drop";
                    $marker_lat = $drop_latitude;
                    $marker_long = $drop_longitude;
                    $cancelable = false;
                    break;
                case "1004":
                    $tip_status = ($app_config->tip_status == 1) ? true : false;
                    $trip_status_text = trans("$string_file.ride_completed");
                    //                    $trip_status_text = $bookingData->LanguageData($booking->merchant_id, 9);
                    // $location_editable = ($booking->service_type_id == 1 && $booking->total_drop_location <= 1) ? true : false;
                    $location = $drop_location;
                    $location_color = "e74c3c";
                    $location_headline = trans("$string_file.drop");
                    //                    $location_headline = $bookingData->LanguageData($booking->merchant_id, 7);
                    $cancelable = false;
                    $marker_type = "DROP";
                    $marker_lat = $drop_latitude;
                    $marker_long = $drop_longitude;
                    break;
                case "1005":
                    $trip_status_text = trans("$string_file.ride_completed");
                    //                    $trip_status_text = $bookingData->LanguageData($booking->merchant_id, 9);
                    $location = $drop_location;
                    $location_color = "e74c3c";
                    $location_headline = trans("$string_file.drop");
                    //                    $location_headline = $bookingData->LanguageData($booking->merchant_id, 7);
                    $cancelable = false;
                    $marker_type = "DROP";
                    $marker_lat = $drop_latitude;
                    $marker_long = $drop_longitude;
                    break;
                case "1006":
                    $trip_status_text = trans("$string_file.user_cancel");
                    //                    $trip_status_text = $bookingData->LanguageData($booking->merchant_id, 2);
                    $location = $booking->drop_location;
                    $location_color = "e74c3c";
                    $location_headline = trans("$string_file.drop");
                    //                    $location_headline = $bookingData->LanguageData($booking->merchant_id, 7);
                    $cancelable = false;
                    break;
                case "1007":
                    $trip_status_text = trans("$string_file.driver_cancel");
                    //                    $trip_status_text = $bookingData->LanguageData($booking->merchant_id, 10);
                    $location = $booking->drop_location;
                    $location_color = "e74c3c";
                    $location_headline = trans("$string_file.drop");
                    //                    $location_headline = $bookingData->LanguageData($booking->merchant_id, 7);
                    $cancelable = false;
                    break;
                case "1008":
                    $trip_status_text = trans("$string_file.admin_cancel");
                    //                    $trip_status_text = $bookingData->LanguageData($booking->merchant_id, 11);
                    $location = $booking->drop_location;
                    $location_color = "e74c3c";
                    $location_headline = trans("$string_file.drop");
                    //                    $location_headline = $bookingData->LanguageData($booking->merchant_id, 7);
                    $cancelable = false;
                    break;
            }

            $location_object = array('estimate_driver_time' => $est_driver_time, 'estimate_driver_distnace' => $est_driver_distance, 'trip_status_text' => $trip_status_text, 'location_headline' => $location_headline, 'location_text' => $location, 'location_color' => $location_color, 'location_editable' => $location_editable);

            $direction_data_updated = !empty($booking->direction_data_updated) ? json_decode($booking->direction_data_updated): "";
            $location_updates_object = array(
                "eta" => !empty($direction_data_updated) ? $direction_data_updated->time : $est_driver_time,
                "distance" =>  !empty($direction_data_updated) ? $direction_data_updated->distance: $est_driver_distance,
                "distance_in_meter" => !empty($direction_data_updated) ? $direction_data_updated->distance_in_meter: $est_driver_distance_in_meter,
                'time_in_min'=> !empty($direction_data_updated) ? $direction_data_updated->time_in_min: "",
                "driver_full_name" =>$driver_full_name,
                "vehicle" => $vehicle_type_name,
                "vehicle_color" => $vehicle_color,
                "vehicle_number" => $vehicle_number,
                "vehicle_image" => $vehicle_image,
                "driver_image"  => $driver_image,
                "location" => $location,
                "location_headline" => $location_headline,
                "total_distance_estimated" => empty($booking->total_distance_estimated) ? (int) $est_driver_distance_in_meter : (int)$booking->total_distance_estimated,
            );
            // Please don't remove commented code. its for transi
            //        $eta_call = false;
            //        if($eta_call == true)
            //        {
            //            $eta = $this->EtaCalculation($booking);
            //            $location_object = array('estimate_driver_time' => $eta['time'], 'estimate_driver_distance' => $eta['distance'], 'trip_status_text' => $trip_status_text, 'location_headline' => $location_headline, 'location_text' => $location, 'location_color' => $location_color, 'location_editable' => $location_editable);
            //        }
            $newArray = array();
            // live tacking notice at driver app and then save into db, user location is same as drive current location
//            $driver_marker_type = array('driver_marker_name' => explode_image_path($booking->VehicleType->vehicleTypeMapImage), 'driver_marker_type' => "CAR_ONE", 'driver_marker_lat' => $booking->Driver->current_latitude, "driver_marker_long" => $booking->Driver->current_longitude, 'driver_marker_bearing' => $booking->Driver->bearing, 'driver_marker_accuracy' => $booking->Driver->accuracy);
            if($app_config->working_with_redis == 1){
                $driver_current_loc = getDriverCurrentLatLong($booking->Driver);
                $driver_marker_type = array('driver_marker_name' => explode_image_path($booking->VehicleType->vehicleTypeMapImage), 'driver_marker_type' => "CAR_ONE", 'driver_marker_lat' => $driver_current_loc['latitude'], "driver_marker_long" => $driver_current_loc['longitude'], 'driver_marker_bearing' => $driver_current_loc['bearing'], 'driver_marker_accuracy' => $driver_current_loc['accuracy']);
            }
            else{
                $driver_marker_type = array('driver_marker_name' => explode_image_path($booking->VehicleType->vehicleTypeMapImage), 'driver_marker_type' => "CAR_ONE", 'driver_marker_lat' => $booking->Driver->current_latitude, "driver_marker_long" => $booking->Driver->current_longitude, 'driver_marker_bearing' => $booking->Driver->bearing, 'driver_marker_accuracy' => $booking->Driver->accuracy);
            }
            $marker = array('marker_type' => $marker_type, 'marker_lat' => $marker_lat, "marker_long" => $marker_long);
            $polydata = array('polyline_width' => '8', 'polyline_color' => "333333", 'polyline' => $poly_line);
            $newArray['stil_marker'] = $marker;
            $newArray['tip_status'] = $tip_status;
            $newArray['movable_marker_type'] = $driver_marker_type;
            $newArray['polydata'] = $polydata;
            $newArray['location'] = $location_object;
            $newArray['location_updates']= $location_updates_object;
            $newArray['cancelable'] = $cancelable;
            $newArray['booking_status'] = $booking->booking_status;
            $newArray['user_tracking_update_time'] = $app_config->user_tracking_update_time ?? 10;
        } catch (Exception $e) {
//            return $this->failedResponse($e->getMessage());
            return $this->failedResponseWithData($e->getMessage(), [$app_config->user_tracking_update_time ?? 10]);
            // throw $e;
        }
        return $this->successResponse(trans("$string_file.data_found"), $newArray);
    }

    public function EtaCalculation(Booking $booking)
    {
        $eta = array('time' => '', 'distance' => '', 'poly_point' => '');
        if ($booking->booking_status == 1002 || $booking->booking_status == 1004) :
            $configuration = BookingConfiguration::select('google_key', 'driver_eta_est_distance', 'polyline', 'eta_pickup_and_dest')->where([['merchant_id', '=', $booking->merchant_id]])->first();
            $app_config = ApplicationConfiguration::select('working_with_redis')->where([['merchant_id', '=', $booking->merchant_id]])->first();
            if ($configuration->driver_eta_est_distance == 1 || $configuration->eta_pickup_and_dest == 1) :

                $latitude= $booking->Driver->current_latitude;
                $longitude= $booking->Driver->current_longitude;

                if($app_config->working_with_redis == 1){
                    $driver_current_loc = getDriverCurrentLatLong($booking->Driver);
                    $latitude = $driver_current_loc['latitude'];
                    $longitude = $driver_current_loc['longitude'];
                }
                $driver_current_location = $latitude. ',' . $longitude;

                $lat_long = $booking->pickup_latitude . ',' . $booking->pickup_longitude;
                if($booking->booking_status == 1004){
                    $lat_long = $booking->drop_latitude . ',' . $booking->drop_longitude;
                }
                $units = ($booking->CountryArea->Country['distance_unit'] == 1) ? 'metric' : 'imperial';
                $poly_line = $configuration->polyline == 1 ? true : false;
//                $eta = GoogleController::GoogleDistanceAndTime($driver_current_location, $lat_long, $configuration->google_key, $units, $poly_line);

                $selected_map = getSelectedMap($booking->Merchant, "BOOKING_TRACKING");
                if($selected_map == "GOOGLE"){
                    $eta = GoogleController::GoogleDistanceAndTime($driver_current_location, $lat_long, $configuration->google_key, $units, $poly_line);
                }
                else{
                    $driver_current_location = $longitude. ',' . $latitude;
                    $lat_long = $booking->pickup_longitude . ',' . $booking->pickup_latitude;
                    if($booking->booking_status == 1004){
                        $lat_long = $booking->drop_longitude . ',' . $booking->drop_latitude;
                    }
                    $eta = MapBoxController::MapBoxDistanceAndTime($driver_current_location, $lat_long, $configuration->map_box_key);
                }
                saveApiLog($booking->merchant_id, "directions" , "BOOKING_TRACKING", $selected_map);

            endif;
        endif;
        return $eta;
    }


    // cancel booking by driver
    public function cancelBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'cancel_reason_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            throw new Exception($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant_id = $request->user('api-driver')->merchant_id;
            $driver = $request->user('api-driver');
            $booking_id = $request->id;
            $booking = Booking::find($booking_id);
            if ($booking->booking_status == 1007) {
                throw new Exception('Ride already cancelled');
            }
            $string_file = $this->getStringFile($booking->Merchant);
            $configuration = BookingConfiguration::where([['merchant_id', '=', $merchant_id]])->first();
            if ($booking->booking_status == '1012') {
                switch ($booking->service_type_id) {
                    case "1":
                        $config = $configuration->normal_ride_later_request_type;
                        $ride_later_radius = $configuration->normal_ride_later_radius;
                        $ride_later_request_driver = $configuration->normal_ride_later_request_driver;
                        break;
                    case "2":
                        $config = $configuration->rental_ride_later_request_type;
                        $ride_later_radius = $configuration->rental_ride_later_radius;
                        $ride_later_request_driver = $configuration->rental_ride_later_request_driver;
                        break;
                    case "3":
                        $config = $configuration->transfer_ride_later_request_type;
                        $ride_later_radius = $configuration->transfer_ride_later_radius;
                        $ride_later_request_driver = $configuration->transfer_ride_later_request_driver;
                        break;
                    case "4":
                        $config = $configuration->outstation_ride_later_request_type;
                        $ride_later_radius = $configuration->outstation_ride_now_radius;
                        $ride_later_request_driver = $configuration->outstaion_ride_now_request_driver;
                        break;
                    default:
                        $config = 1;
                        $ride_later_radius = 10;
                        $ride_later_request_driver = 10;
                        break;
                }
                if ($config == 1) {
                    DriverCancelBooking::create([
                        'booking_id' => $booking->id,
                        'driver_id' => $request->user('api-driver')->id,
                    ]);
                    BookingRequestDriver::where([['driver_id', '=', $driver->id], ['booking_id', '=', $booking->id]])->update(['request_status' => 4]);
                    $booking->booking_status = 1001;
                    $booking->driver_id = NULL;
                    $booking->save();
                    $findDriver = new FindDriverController();
                    $user_gender = $booking->gender;
                    $drivers = $findDriver->GetAllNearestDriver($booking->country_area_id, $booking->pickup_latitude, $booking->pickup_longitude, $ride_later_radius, $ride_later_request_driver, $booking->vehicle_type_id, $booking->service_type_id, '', '', $user_gender, $configuration->driver_request_timeout);
                    if (!empty($drivers)) {
                        $message = trans("$string_file.new_upcoming_ride");
                        $bookingData = new BookingDataController();
                        $bookingData->SendNotificationToDrivers($booking, $drivers, $message);
                    }
                }
                // charge driver wallet
                $today = new DateTime(date('Y-m-d H:i:s'));
                $today = $today->format("Y-m-d H:i:s");
                $today = convertTimeToUSERzone($today, $booking->CountryArea->timezone, $booking->merchant_id);
                $ride_later_cancel_hour = $configuration->ride_later_cancel_hour ? $configuration->ride_later_cancel_hour : 0;
                $bookingtimestamp = $booking['later_booking_date'] . " " . $booking->later_booking_time;
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
                if ($newDate <= $today) {
                    $paramArray = array(
                        'driver_id' => $driver->id,
                        'booking_id' => $booking->id,
                        'amount' => $configuration->ride_later_cancel_charge_in_cancel_hour,
                        'narration' => 8,
                    );
                    WalletTransaction::WalletDeduct($paramArray);
                    //                $current_driver = Driver::find($booking->driver_id);
                    //                    \App\Http\Controllers\Helper\CommonController::WalletDeduct($booking->driver_id,$booking->id,$configuration->ride_later_cancel_charge_in_cancel_hour,8);
                    //                $current_driver->wallet_money = $current_driver->wallet_money - $configuration->ride_later_cancel_charge_in_cancel_hour;
                    //                $current_driver->save();
                    //                WalletTransaction::driverWallet($current_driver, $configuration->ride_later_cancel_charge_in_cancel_hour, 2, $booking->id);
                }
            } else if ($booking->booking_status == '1002' && $configuration->accept_ride_transfer_after_cancelled == 1) {
                switch ($booking->service_type_id) {
                    case "1":
                        $config = $configuration->normal_ride_later_request_type;
                        $ride_later_radius = $configuration->normal_ride_later_radius;
                        $ride_later_request_driver = $configuration->normal_ride_later_request_driver;
                        break;
                    case "2":
                        $config = $configuration->rental_ride_later_request_type;
                        $ride_later_radius = $configuration->rental_ride_later_radius;
                        $ride_later_request_driver = $configuration->rental_ride_later_request_driver;
                        break;
                    case "3":
                        $config = $configuration->transfer_ride_later_request_type;
                        $ride_later_radius = $configuration->transfer_ride_later_radius;
                        $ride_later_request_driver = $configuration->transfer_ride_later_request_driver;
                        break;
                    case "4":
                        $config = $configuration->outstation_ride_later_request_type;
                        $ride_later_radius = $configuration->outstation_ride_now_radius;
                        $ride_later_request_driver = $configuration->outstaion_ride_now_request_driver;
                        break;
                }
                if ($config == 1) {
                    BookingRequestDriver::where([['driver_id', '=', $driver->id], ['booking_id', '=', $booking->id]])->update(['request_status' => 3]);
                    $cancelDriver = BookingRequestDriver::with(['Driver' => function ($q) use ($driver) {
                        return $q->select("*", "id as driver_id")->where('player_id', '!=', $driver->player_id);
                    }])
                        ->whereHas('Driver', function ($q) use ($driver) {
                            return $q->where('player_id', '!=', $driver->player_id);
                        })->where([['booking_id', '=', $booking_id], ['request_status', '=', 1]])->get();
                    $cancelDriver = array_pluck($cancelDriver, 'Driver');

                    foreach ($cancelDriver as $key => $value) {
                        $newDriver = Driver::select('id', 'last_ride_request_timestamp')->find($value->id);
                        $newDriver->last_ride_request_timestamp = date("Y-m-d H:i:s", time() - 100);
                        $newDriver->save();
                    }
                    $booking->booking_status = 1001;
                    $booking->driver_vehicle_id = NULL;
                    $booking->driver_id = NULL;
                    $booking->booking_timestamp = time();
                    $booking->save();

                    DB::commit();

                    $booking = Booking::find($booking_id);
                    $drivers = Driver::GetNearestDriver([
                        // 'booking_id'=>$booking->id,
                        'segment_id' => $booking->segment_id,
                        'taxi_company_id' => NULL,
                        'isManual' => false,
                        'area' => $booking->country_area_id,
                        'latitude' => $booking->pickup_latitude,
                        'longitude' => $booking->pickup_longitude,
                        'limit' => 10,
                        'service_type' => $booking->service_type_id,
                        'vehicle_type' => $booking->vehicle_type_id,
                        // 'distance_unit' => $request->distance_unit,
                        'distance' => $booking->ride_radius,
                        'user_gender' => $booking->gender,
                    ]);

                    foreach($drivers as $driver){
                        BookingRequestDriver::create([
                                'booking_id' => $booking->id,
                                'driver_id' => $driver->id,
                                'distance_from_pickup' => $driver->distance,
                                'request_status' => 1
                        ]);
                    }

                    if (!empty($drivers)) {
                        $message = trans("$string_file.new_upcoming_ride");
                        $bookingData = new BookingDataController();
                        $bookingData->SendNotificationToDrivers($booking, $drivers, $message);

                        $notification_data['notification_type'] = "FINDING_RIDE_AFTER_DRIVER_CANCEL";
                        $notification_data['segment_type'] = $booking->Segment->slag;
                        $notification_data['segment_group_id'] = $booking->Segment->segment_group_id;
                        $notification_data['segment_sub_group'] = $booking->Segment->sub_group_for_app;
                        $notification_data['segment_data'] = [
                                'booking_id' => $booking->id,
                            ];
                        $title = "Searching for a New Driver";
                        $msg = "Your previous driver canceled the ride. We're finding another driver for you.";
                        $arr_param = ['user_id' => $booking->user_id, 'data' => $notification_data, 'message' => $msg, 'merchant_id' => $booking->merchant_id, 'title' => $title, 'large_icon' => ""];
                        Onesignal::UserPushMessage($arr_param);
                    }
                    else{
                        $bookingData = new BookingDataController();
                        $bookingData->bookingNotificationForUser($booking, "CANCEL_RIDE");
                    }

                    $request->user('api-driver')->free_busy = 2;
                    $request->user('api-driver')->save();
                }
            } else {
                $bookingConfig = $booking->Merchant->BookingConfiguration;
                $limit_minute = $bookingConfig->driver_cancel_after_time;
                if ($bookingConfig->driver_cancel_ride_after_time == 1 && !empty($limit_minute)) {
                    $bookingDetails = $booking->BookingDetail;
                    $current_cancel_datetime = date('Y-m-d H:i:s', strtotime("now"));
                    $driver_arrive_datetime = date('Y-m-d H:i:s', strtotime($bookingDetails->arrive_timestamp));
                    $diff = date_diff(date_create($driver_arrive_datetime), date_create($current_cancel_datetime));
                    $diff_minute = $diff->i;
                    if ($diff_minute >= $limit_minute) {
                        $cancel_charges = $booking->PriceCard->cancel_amount;
                        $merchant_data = $booking->Merchant;
                        if ($merchant_data['cancel_outstanding'] == 1) :
                            $merchant = new \App\Http\Controllers\Helper\Merchant();
                            $payment = new CancelPayment();
                            $cancel_charges_received = $payment->MakePayment($booking, $booking->payment_method_id, $cancel_charges, $booking->user_id, $booking->card_id, $merchant_data->cancel_outstanding, $booking->driver_id);
                            $booking_transaction_submit = BookingTransaction::updateOrCreate([
                                'booking_id' => $booking_id,
                            ], [
                                'date_time_details' => date('Y-m-d H:i:s'),
                                'sub_total_before_discount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'surge_amount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'extra_charges' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'discount_amount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'tax_amount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'tip' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'insurance_amount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'cancellation_charge_received' => $merchant->TripCalculation(isset($cancel_charges_received) ? $cancel_charges_received : '0.0', $booking->merchant_id),
                                'cancellation_charge_applied' => $merchant->TripCalculation($cancel_charges, $booking->merchant_id),
                                'toll_amount' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'cash_payment' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'online_payment' => $merchant->TripCalculation(isset($cancel_charges_received) ? $cancel_charges_received : '0.0', $booking->merchant_id),
                                'customer_paid_amount' => $merchant->TripCalculation(isset($cancel_charges_received) ? $cancel_charges_received : '0.0', $booking->merchant_id),
                                'company_earning' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'driver_earning' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'amount_deducted_from_driver_wallet' => $merchant->TripCalculation('0.0', $booking->merchant_id),
                                'driver_total_payout_amount' => $merchant->TripCalculation($cancel_charges, $booking->merchant_id),
                                'trip_outstanding_amount' => $merchant->TripCalculation(($cancel_charges - (isset($cancel_charges_received) ? $cancel_charges_received : 0)), $booking->merchant_id),
                                //                            'merchant_id'=>$booking->merchant_id
                            ]);
                            if (!empty($booking->driver_id)) {
                                $paramArray = array(
                                    'merchant_id' => $booking->merchant_id,
                                    'driver_id' => $booking->driver_id,
                                    'booking_id' => $booking->id,
                                    'amount' => $cancel_charges,
                                    'narration' => 11,
                                    'platform' => 1,
                                    'payment_method' => 2,
                                );
                                WalletTransaction::WalletCredit($paramArray);
                                //                    \App\Http\Controllers\Helper\CommonController::WalletCredit($booking->driver_id,$booking->id,$cancel_charges,11,1,2);
                            }
                        endif;
                    }
                }

                $booking->booking_status = 1007;
                $booking->cancel_reason_id = $request->cancel_reason_id;
                $booking->save();
                // inset booking status history
                $this->saveBookingStatusHistory($request, $booking, $booking->id);

                //payment option is payu then void the authorisation
                if ($booking->payment_method_id == 2) {
                    $user_card = UserCard::find($booking->card_id);
                    if ($user_card->PaymentOption->slug == "PAYU") {
                        $locale = $request->header('locale');
                        $this->payuVoid($booking, $locale);
                    }
                }
                $request->user('api-driver')->free_busy = 2;
                $request->user('api-driver')->save();
                $bookingData = new BookingDataController();
                $bookingData->bookingNotificationForUser($booking, "CANCEL_RIDE");
            }
            if ($booking->service_type_id == 5 && !empty($booking->driver_id)) {
                $poolBooking = new PoolController();
                $poolBooking->CancelRide($booking, $request);
            }
            DriverRecords::penaltyDriver($driver);

            // clear the call masking session
            $config = Configuration::where('merchant_id', $booking->merchant_id)->first();
            if (isset($config->twilio_call_masking) && $config->twilio_call_masking == 1) {
                TwilioMaskingHelper::close_session($booking);
            }

            // Debit Driver wallet according to cancel policy
            if ($request->cancel_policy_id && !empty($request->cancel_policy_id)) {

                $cancel_policy = CancelPolicy::where([['segment_id', '=', $booking->segment_id], ['service_type', '=', $booking->booking_type], ['country_area_id', '=', $booking->country_area_id]])->first();
                $free_time = $cancel_policy->free_time;
                // current time
                $current_time_in_min = str_pad(floor(time() / 60), 2, "0", STR_PAD_LEFT);

                $arr_status = json_decode($booking->booking_status_history, true);
                $debit_driver_wallet = false;

                if ($cancel_policy->service_type == 1) {

                    foreach ($arr_status as $status) {
                        if ($status['booking_status'] == 1002) {
                            // add free time with accepted and then check
                            $ride_accepted_time = str_pad(floor($status['booking_timestamp'] / 60), 2, "0", STR_PAD_LEFT) + $free_time;
                        }
                    }
                    if ($current_time_in_min > $ride_accepted_time) {
                        $debit_driver_wallet = true;
                    }
                } else {
                    // later date is in user time zone
                    $booking_later_booking_date_time = $booking->later_booking_date . " " . $booking->later_booking_time;
                    $utc_time = convertTimeToUTCzone($booking_later_booking_date_time, $booking->CountryArea->timezone);
                    $ride_start_time = str_pad(floor(strtotime($utc_time) / 60), 2, "0", STR_PAD_LEFT); // convert date to seconds and then in minutes
                    // check free time
                    $cancel_free_time = $ride_start_time - $free_time;
                    if ($current_time_in_min > $cancel_free_time) {
                        $debit_driver_wallet = true;
                    }
                }
                // p($debit_driver_wallet);
                if ($debit_driver_wallet) {

                    $driverPayment = new CommonController();
                    $new_array_param['booking_id'] = $booking->id;
                    $new_array_param['driver_id'] = $booking->driver_id;
                    $new_array_param['amount'] = $cancel_policy->cancellation_charges;
                    $new_array_param['wallet_status'] = 'DEBIT';
                    $new_array_param['narration'] = 26;
                    // p($new_array_param);
                    $driverPayment->DriverRideAmountCredit($new_array_param);
                }
            }
            $config = Configuration::where('merchant_id', $merchant_id)->first();
            if (isset($config->whatsapp_notification) && $config->whatsapp_notification == 1) {
                SendWhatsappNotificationEvent::dispatch($merchant_id, 1016, $booking);
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
        DB::commit();
        return ['message' => trans("$string_file.ride_cancelled"), 'data' => []];
    }

    // void request of payment for payu
    public function payuVoid($booking, $locale)
    {
        $user_card = UserCard::find($booking->card_id);
        $payment_config = PaymentOptionsConfiguration::where([['payment_option_id', '=', $user_card->payment_option_id], ['merchant_id', '=', $booking->merchant_id]])->first();
        $payment = new RandomPaymentController();
        $transaction = [];
        if ($payment_config->payment_step > 1) {
            $transaction = DB::table("transactions")->select("id", "payment_transaction")->where([["reference_id", "=", $booking->user_id], ["card_id", "=", $booking->card_id], ["booking_id", "=", $booking->id], ["status", "=", 1]])->first();
            $transaction = !empty($transaction->payment_transaction) ? json_decode($transaction->payment_transaction, true) : [];
        }
        $payment_data = $payment->payuPaymentVoid($booking->User, $booking->final_amount_paid, $booking->UserCard, $payment_config, $locale, $transaction);
    }


    // driver update payment status when user pay cash to driver and user phone goes to off/hang
    public function driverRidePaymentStatus(Request $request)
    {
        $status = 0;
        $string_file = $this->getStringFile($request->merchant_id);
        $message = trans("$string_file.data_not_found");
        $validator = Validator::make($request->all(), [
            //            'booking_id' => 'required',
            'latitude' => 'required',
            'segment_slug' => 'required',
            //            'accuracy' => 'required',
            'booking_order_id' => 'required',
            'longitude' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            $this->failedResponse($errors[0]);
            //            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $merchant = new \App\Http\Controllers\Helper\Merchant();
        DB::beginTransaction();
        try {
            $booking = Booking::with('BookingDetail', 'User', 'Merchant')->where('payment_status', '!=', 1)->where('booking_status', 1005)->find($request->booking_order_id);
            if (isset($booking->id) && !empty($booking->id)) {
                $previous_payment_method = $booking->payment_method_id;
                $booking->payment_method_id = 1; // its case in which driver receive amount only in cash
                $booking->payment_status = 1; // means payment done successfully
                $booking->save();

                if (isset($booking->BookingDetail->id)) {
                    $booking->BookingDetail->pending_amount = 0;
                    $booking->BookingDetail->payment_failure = 2;
                    $booking->BookingDetail->save();
                }

                if (isset($booking->BookingTransaction->id) && $previous_payment_method != 1) {
                    $booking->BookingTransaction->cash_payment = $booking->BookingTransaction->online_payment;
                    $booking->BookingTransaction->online_payment = '0.0';
                    $booking->BookingTransaction->trip_outstanding_amount = $merchant->TripCalculation(($booking->BookingTransaction->driver_total_payout_amount + $booking->BookingTransaction->amount_deducted_from_driver_wallet - $booking->BookingTransaction->cash_payment), $booking->merchant_id);
                    $booking->BookingTransaction->instant_settlement = 0;
                    $booking->BookingTransaction->save();
                }
                $status = 1;
                $message = trans("$string_file.success");

                // send amount accepted by driver notification to user
                if ($status == 1) {
                    $bookingData = new BookingDataController();
                    // $message = $bookingData->LanguageData($booking->merchant_id, 31);
                    $notification_data = $bookingData->bookingNotificationForUser($booking, "END_RIDE");
                    event(new SendUserInvoiceMailEvent($booking, 'invoice'));
                }

                if(isset($booking->BookingTransaction) && !empty($booking->BookingTransaction)){
                    $this->updateRideAmountInDriverWallet($booking, $booking->BookingTransaction, $booking->id);
                }

            } else {
                // if user already paid through cash
                $booking = Booking::where('payment_status', '=', 1)->where('payment_method_id', '=', 1)->where('booking_status', 1005)->find($request->booking_order_id);
                if (isset($booking->id) && !empty($booking->id)) {
                    $status = 1;
                    $message = trans("$string_file.success");
                }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return $this->failedResponse($message);
        }
        DB::commit();
        if ($status == 0) {
            return $this->failedResponse($message);
        } else {
            $booking_data = new BookingDataController;
            $request->request->add(['booking_id' => $request->booking_order_id]);
            $return_data = $booking_data->bookingReceiptForDriver($request);
            return $this->successResponse($message, $return_data);
        }
        //        return response()->json(['result' => "$status", 'message' => $message]);
    }

    public function getNextRadiusDriver(Request $request)
    {
        $bookingData = new BookingDataController();
        $result = $bookingData->sendRequestToNextDrivers($request->booking_id, 1);
        return $result;
    }

    public function findDrivers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkout' => [
                'required', 'integer',
                Rule::exists('booking_checkouts', 'id')->where(function ($query) {
                    $query->where([['payment_method_id', '!=', 0]]);
                })
            ],
            'question_id' => 'nullable|exists:questions,id',
            'fav_driver_id' => 'nullable|exists:drivers,id',
            'answer' => 'required_with:question_id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
        }
        $checkout = BookingCheckout::find($request->checkout);
        if ($this->CheckWalletBalance($checkout) == true) {
        }
    }

//    public function RidePauseResume(Request $request)
//    {
//        $driver = $request->user('api-driver');
//        $merchant_id = $driver->merchant_id;
//        $validator = Validator::make($request->all(), [
//            'booking_id' => [
//                'required',
//                'integer',
//                Rule::exists('bookings', 'id')->where(function ($query) {
//                    $query->where('booking_status', 1004);
//                }),
//            ],
//            'latitude' => 'required',
//            'longitude' => 'required',
//            'type' => 'required' // 1 for pause, 2 for resume
//        ]);
//        if ($validator->fails()) {
//            $errors = $validator->messages()->all();
//            return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
//        }
//        $booking_id = $request->booking_id;
//        $booking = Booking::find($booking_id);
//        $generalConfiguration = Configuration::where([['merchant_id', '=', $merchant_id]])->first();
//        $date_time = date("Y-m-d H:i:s");
//        $startTimeStemp = strtotime($date_time);
//        $price_card = DB::table('price_card_values as pvc')->join('pricing_parameters as pp', 'pvc.pricing_parameter_id', '=', 'pp.id')->where([['pvc.price_card_id', '=', $booking->price_card_id], ['pp.parameterType', '=', 18]])->get();
//        $type = 0;
//        $message = trans('api.no_action');
//        $bookingData = new BookingDataController();
//        if (isset($generalConfiguration->onride_waiting_button) && $generalConfiguration->onride_waiting_button == 1 && count($price_card) > 0) {
//            if ($request->type == 2) {
//                if ($booking->onride_waiting_type == 1) {
//                    $message = trans('api.already_ride_pause');
//                    return response()->json(['result' => "0", 'message' => $message, 'type' => $request->type]);
//                }
//                $booking->onride_pause_timestamp = $date_time;
//                $booking->onride_waiting_type = 1;
//                $booking->save();
//                $message = trans('api.ride_pause');
//                //send notification to user
//                $data = $bookingData->BookingNotification($booking);
//                $notif_message = trans('api.user_ride_pause');
//                Onesignal::UserPushMessage($booking->user_id, $data, $notif_message, 33, $booking->merchant_id);
//                $type = 1;
//            } elseif ($request->type == 1) {
//                if ($booking->onride_waiting_type == 2) {
//                    $message = trans('api.already_ride_start');
//                    return response()->json(['result' => "0", 'message' => $message, 'type' => $request->type]);
//                }
//                $arriveTimeStemp = strtotime($booking->onride_pause_timestamp);
//                $booking->onride_pause_timestamp = $date_time;
//                $waitTime = round(abs($arriveTimeStemp - $startTimeStemp) / 60, 2);
//                $booking->onride_waiting_time += $waitTime;
//                $booking->onride_waiting_type = 2;
//                $booking->save();
//                $message = trans('api.ride_start');
//                $data = $bookingData->BookingNotification($booking);
//                $notif_message = trans('api.user_ride_resume');
//                Onesignal::UserPushMessage($booking->user_id, $data, $notif_message, 33, $booking->merchant_id);
//                $type = 2;
//            }
//        }
//        return response()->json(['result' => "1", 'message' => $message, 'type' => $type]);
//    }

    public function checkBookingOutstanding($user_id,$merchant_id = NULL)
    {
        $merchant_helper = new \App\Http\Controllers\Helper\Merchant();
        $outstanding = Outstanding::where(['user_id' => $user_id, 'pay_status' => 0, 'reason' => 2])->first();
        if ($outstanding) {
            $booking = Booking::with('BookingDetail')->findOrFail($outstanding->booking_id);
            $data['pickup_location'] = $booking->BookingDetail->start_location;
            $data['drop_location'] = $booking->BookingDetail->end_location;
            $data['booking_id'] = (string)$booking->id;
            // $data['amount'] = (string)$outstanding->amount;
            $data['amount'] = (isset($merchant_id)) ? (string)$merchant_helper->PriceFormat($outstanding->amount, $merchant_id) : (string)$outstanding->amount;
            $data['iso_code'] = $booking->CountryArea->Country->isoCode;
            $data['outstanding_id'] = $outstanding->id;
            $data['pay_later_payment'] = true;
            return $data;
        }
        return [];
    }


    // get list of ongoing bookings of driver
    public function getOngoingBookings(Request $request)
    {
        $data = [];
        $merchant_helper = new \App\Http\Controllers\Helper\Merchant();
        try {
            $booking_obj = new Booking;
            $bookings = $booking_obj->getDriverOngoingBookings($request);
            $string_file = $this->getStringFile($request->merchant_id);
            foreach ($bookings as $booking) {

                if ($booking->service_type_id == 5) {
                    $poolRide = new PoolController();
                    $poolDetails = $poolRide->DecideForPickOrDrop($booking->driver_id);
                    if ($poolDetails) {
                        $booking_obj = new Booking;
                        $booking = $booking_obj->getBooking($poolDetails['booking_id']);
                    }
                    // get all rides of current driver


                }

                $merchant_segment = $booking->Segment->Merchant->where('id', $booking->merchant_id);
                $merchant_segment = collect($merchant_segment->values());
                $booking_info = [
                    'id' => $booking->id,
                    'status' => $booking->booking_status,
                    'segment_name' => $booking->Segment->Name($booking->merchant_id) . ' ' . trans("$string_file.ride"),
                    'segment_slug' => $booking->Segment->slag,
                    'segment_group_id' => $booking->segment_group_id,
                    'segment_sub_group' => $booking->Segment->sub_group_for_app,
                    'number' => $booking->merchant_booking_id,
                    'master_booking_id' => $booking->master_booking_id ? $booking->master_booking_id : $booking->merchant_booking_id,
                    'segment_service' => $booking->ServiceType->ServiceName($booking->merchant_id),
                    'time' => $booking->booking_timestamp,
                    'segment_image' => isset($merchant_segment[0]['pivot']->segment_icon) && !empty($merchant_segment[0]['pivot']->segment_icon) ? get_image($merchant_segment[0]['pivot']->segment_icon, 'segment', $booking->merchant_id, true, false) : get_image($booking->Segment->icon, 'segment_super_admin', NULL, false, false),
                    'pool_ride' => $booking->service_type_id == 5 ? true : false,
                ];
                $user_info = [
                    'user_name' => $booking->User->first_name . ' ' . $booking->User->last_name,
                    'user_image' => get_image($booking->User->UserProfileImage, 'user', $booking->merchant_id),
                    'user_phone' => $booking->User->UserPhone,
                    'user_rating' => "4.5",
                ];

                $pick_details = [
                    'lat' => $booking->pickup_latitude,
                    'lng' => $booking->pickup_longitude,
                    'address' => $booking->pickup_location,
                    'icon' => view_config_image("static-images/pick-icon.png"),

                ];
                $drop_details = [
                    'lat' => $booking->drop_latitude,
                    'lng' => $booking->drop_longitude,
                    'address' => $booking->drop_location,
                    'icon' => view_config_image("static-images/drop-icon.png"),
                ];
                $payment_details = [
                    'payment_mode' => $booking->PaymentMethod->payment_method,
                    // 'amount' => $booking->User->Country->isoCode . ' ' . $booking->estimate_bill,
                    'amount' => (isset($booking->User->Country) ? $booking->User->Country->isoCode : "") . ' ' . $merchant_helper->PriceFormat($booking->estimate_bill, $booking->merchant_id),

                    'paid' => false
                ];

                $data[] = [
                    'info' => $booking_info,
                    'user_info' => $user_info,
                    'pick_details' => $pick_details,
                    'drop_details' => $drop_details,
                    'payment_details' => $payment_details,
                ];
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            return $message;
        }
        return $data;
    }

    // get list of pool bookings
    public function getPoolDetails(Request $request)
    {
        $driver = $request->user('api-driver');
        $validator = Validator::make($request->all(), [
            'master_booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'master_booking_id')->where(function ($query) {
                }),
            ],

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        $data = [];
        $pool_data = [];
        try {
            $booking_obj = new Booking;
            $bookings = $booking_obj->getDriverPoolBookings($request);
            // p($bookings);
            $string_file = $this->getStringFile($request->merchant_id);
            // master_booking
            $driver_pool_history = [];
            // p($bookings[0]);
            if (isset($bookings[0])) {
                $booking = $bookings[0];
                $driver_pool_history = $booking->pool_history ? json_decode($booking->pool_history, true) : [];
                // p($driver_pool_history);
                $poolRide = new PoolController();
                $poolDetails = $poolRide->DecideForPickOrDrop($booking->driver_id);
                if ($poolDetails) {
                    $booking_obj = new Booking;
                    $booking = $booking_obj->getBooking($poolDetails['booking_id']);
                }

                $merchant_segment = $booking->Segment->Merchant->where('id', $booking->merchant_id);
                $merchant_segment = collect($merchant_segment->values());
                $pick_details = [
                    // 'lat' => $booking->pickup_latitude,
                    // 'lng' => $booking->pickup_longitude,
                    'address' => $booking->pickup_location,
                    // 'icon' => view_config_image("static-images/pick-icon.png"),
                ];
                $drop_details = [
                    // 'lat' => $booking->drop_latitude,
                    // 'lng' => $booking->drop_longitude,
                    'address' => $booking->drop_location,
                    // 'icon' => view_config_image("static-images/drop-icon.png"),
                ];
                if ($booking->booking_status == 1002) {
                    $location = $pick_details;
                    $action = 'pickup';
                    $string_key = trans("$string_file.pickup");
                } else {
                    $location = $drop_details;
                    $action = 'drop_off';
                    $string_key = trans("$string_file.drop_off");
                }

                $booking_info = [
                    'id' => $booking->id,
                    'status' => $booking->booking_status,
                    // 'segment_name' => $booking->Segment->Name($booking->merchant_id) . ' ' . trans("$string_file.ride"),
                    'segment_slug' => $booking->Segment->slag,
                    // 'segment_group_id' => $booking->Segment->segment_group_id,
                    // 'segment_sub_group' => $booking->Segment->sub_group_for_app,
                    'number' => $booking->merchant_booking_id,
                    'master_booking_id' => $booking->master_booking_id ? $booking->master_booking_id : $booking->merchant_booking_id,
                    'segment_service' => $booking->ServiceType->ServiceName($booking->merchant_id),
                    'user' => $booking->User->first_name . ' ' . $booking->User->last_name,
                    // 'time' => $booking->booking_timestamp,
                    // 'segment_image' => isset($merchant_segment[0]['pivot']->segment_icon) && !empty($merchant_segment[0]['pivot']->segment_icon) ? get_image($merchant_segment[0]['pivot']->segment_icon, 'segment', $booking->merchant_id, true, false) : get_image($booking->Segment->icon, 'segment_super_admin', NULL, false, false),
                    // 'pool_ride' => $booking->service_type_id == 5 ? true : false,
                    'location' => $location
                ];
                // $user_info = [
                //     'user_name' => $booking->User->first_name . ' ' . $booking->User->last_name,
                //     'user_image' => get_image($booking->User->UserProfileImage, 'user', $booking->merchant_id),
                //     'user_phone' => $booking->User->UserPhone,
                //     'user_rating' => "4.5",
                // ];


                // $payment_details = [
                //     'payment_mode' => $booking->PaymentMethod->payment_method,
                //     'amount' => $booking->User->Country->isoCode . ' ' . $booking->estimate_bill,
                //     'paid' => false
                // ];

                $now = [
                    'action' => $action,
                    'ride_info' => $booking_info,
                    'string_key' => $string_key
                ];
                // [
                // 'info' => $booking_info,
                // 'user_info' => $user_info,
                // 'pick_details' => $pick_details,
                // 'drop_details' => $drop_details,
                // 'payment_details' => $payment_details,
                // ];

                // p($now);
                $pool_history = [];
                if (count($driver_pool_history) > 0) {

                    $arr_booking_id = array_column($driver_pool_history, 'booking_id');
                    $booking = $booking->whereIn('id', $arr_booking_id);
                    foreach ($bookings as $booking) {
                        // $merchant_segment = $booking->Segment->Merchant->where('id', $booking->merchant_id);
                        // $merchant_segment = collect($merchant_segment->values());
                        $booking_info = [
                            'id' => $booking->id,
                            // 'status' => $booking->booking_status,
                            // 'segment_name' => $booking->Segment->Name($booking->merchant_id) . ' ' . trans("$string_file.ride"),
                            'segment_slug' => $booking->Segment->slag,
                            // 'segment_group_id' => $booking->Segment->segment_group_id,
                            // 'segment_sub_group' => $booking->Segment->sub_group_for_app,
                            'number' => $booking->merchant_booking_id,
                            'master_booking_id' => $booking->master_booking_id ? $booking->master_booking_id : $booking->merchant_booking_id,
                            'user' => $booking->User->first_name . ' ' . $booking->User->last_name
                            // 'segment_service' => $booking->ServiceType->ServiceName($booking->merchant_id),
                            // 'time' => $booking->booking_timestamp,
                            // 'segment_image' => isset($merchant_segment[0]['pivot']->segment_icon) && !empty($merchant_segment[0]['pivot']->segment_icon) ? get_image($merchant_segment[0]['pivot']->segment_icon, 'segment', $booking->merchant_id, true, false) : get_image($booking->Segment->icon, 'segment_super_admin', NULL, false, false),
                            // 'pool_ride' => $booking->service_type_id == 5 ? true : false,
                        ];
                        // $user_info = [
                        //     'user_name' => $booking->User->first_name . ' ' . $booking->User->last_name,
                        //     'user_image' => get_image($booking->User->UserProfileImage, 'user', $booking->merchant_id),
                        //     'user_phone' => $booking->User->UserPhone,
                        //     'user_rating' => "4.5",
                        // ];

                        // $pick_details = [
                        //     'lat' => $booking->pickup_latitude,
                        //     'lng' => $booking->pickup_longitude,
                        //     'address' => $booking->pickup_location,
                        //     'icon' => view_config_image("static-images/pick-icon.png"),

                        // ];
                        // $drop_details = [
                        //     'lat' => $booking->drop_latitude,
                        //     'lng' => $booking->drop_longitude,
                        //     'address' => $booking->drop_location,
                        //     'icon' => view_config_image("static-images/drop-icon.png"),
                        // ];
                        // $payment_details = [
                        //     'payment_mode' => $booking->PaymentMethod->payment_method,
                        //     'amount' => $booking->User->Country->isoCode . ' ' . $booking->estimate_bill,
                        //     'paid' => false
                        // ];

                        $pool_history[$booking->id] = $booking_info;
                        // [
                        //      $booking_info,

                        //     // 'pick_details' => $pick_details,
                        //     // 'drop_details' => $drop_details,
                        //     // 'payment_details' => $payment_details,
                        // ];
                    }
                    // set pool data

                    foreach ($driver_pool_history as $history) {
                        $key = $history['action'];
                        $pool_data[] = [
                            'action' => $key,
                            'ride_info' => $pool_history[$history['booking_id']],
                            'string_key' => trans("$string_file.$key")
                        ];
                    }

                    // p($pool_data);
                    // $data = [
                    //     'now' => $now,
                    //     'history' => $pool_data,
                    // ];
                }
                array_push($pool_data, $now);
            }
            $data = [
                'driver_pool_status' => $driver->pool_ride_active ? $driver->pool_ride_active : 2,
                'pool_data' => $pool_data
            ];
        } catch (Exception $e) {
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        return $this->successResponse(trans("$string_file.success"), $data);
    }

    public function endBooking(Request $request)
    {

        if(!empty($request->timestampvalue)){
            $cacheKey = 'driver_end_booking' . $request->timestampvalue;

            if (Cache::has($cacheKey)) {
                $response = Cache::get($cacheKey);
                $message = $response['message'];
                $return_data = $response['return_data'];
                return $this->successResponse($message, $return_data);
            }
        }

        $booking_transaction_submit = NULL;
        DB::beginTransaction();
        try {
            $billDetails = '';
            $driver = $request->user('api-driver');
            $merchant_id = $driver->merchant_id;
            $validator = Validator::make($request->all(), [
                'booking_id' => [
                    'required',
                    'integer',
                    Rule::exists('bookings', 'id')->where(function ($query) {
                        $query->where('booking_status', 1004);
                    }),
                ],
                'latitude' => 'required',
                'longitude' => 'required',
                'tip_amount' => 'nullable|numeric',
            ]);
            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return $this->failedResponse($errors[0]);
            }
            $configuration = $driver->Merchant->BookingConfiguration;
            $config = $driver->Merchant->Configuration;

            $socket_enable = false;
            if ($config->lat_long_storing_at == 2) {
                $validator = Validator::make($request->all(), [
                    //                    'booking_polyline' => 'required',
                ]);
                if ($validator->fails()) {
                    $errors = $validator->messages()->all();
                    return $this->failedResponse($errors[0]);
                    //                    return response()->json(['result' => "0", 'message' => $errors[0], 'data' => []]);
                }
                $socket_enable = true;
            }

            $appConfig = $driver->Merchant->ApplicationConfiguration;
            $booking_id = $request->booking_id;
            $key = $configuration->google_key;

            $booking = Booking::with('PriceCard', 'BookingCoordinate')->find($booking_id);

            $string_file = $this->getStringFile(NULL, $booking->Merchant);
            //&& isset($configuration->delivery_drop_otp) && ($configuration->delivery_drop_otp == 1 || $configuration->delivery_drop_otp == 2)
            // this configuration is for delivery segment
            if ($booking->Segment->slag == "DELIVERY") {
                self::deliveryDetailStore($request, $booking, $configuration->delivery_drop_otp);
            }



            if (isset($request->onride_waiting_time) && !empty($request->onride_waiting_time)) {
                $booking->onride_waiting_time = $request->onride_waiting_time;
                $booking->save();
                $booking = $booking->fresh();
            }

            if ($config->outside_area_ratecard == 1) {
                $area = CountryArea::find($booking->country_area_id);
                $ploygon = new PolygenController();
                $checkArea = $ploygon->CheckArea($request->latitude, $request->longitude, $area->AreaCoordinates);
                $price_card = PriceCard::select('id')->where(function ($query) use ($booking, $checkArea) {
                    $query->where([['merchant_id', '=', $booking->merchant_id], ['service_type_id', '=', $booking->service_type_id], ['country_area_id', '=', $booking->country_area_id], ['vehicle_type_id', '=', $booking->vehicle_type_id], ['status', '=', 1]]);
                    if (!$checkArea) {
                        $query->where('rate_card_scope', 2);
                    } else {
                        $query->where('rate_card_scope', 1);
                    }
                })->first();
                if (!empty($price_card)) {
                    $price_card_id = $price_card->id;
                    $booking->price_card_id = $price_card_id;
                    $booking->save();
                    $booking = $booking->fresh();
                }
            }

            $outstation_inside_City = (isset($configuration->outstation_inside_city) && $configuration->outstation_inside_city == 1) ? true : false;
            $bookingDetails = self::storeBookingDetails($request, $booking, $outstation_inside_City, $key, $socket_enable);

            $pricing_type = $booking->PriceCard->pricing_type;
            $price_card_id = $booking->price_card_id;
            $service_type_id = $booking->service_type_id;
            $service_type = $booking->ServiceType->type;

            $start_timestamp = $bookingDetails->start_timestamp;
            $endTimeStamp = $bookingDetails->end_timestamp;
            $seconds = $endTimeStamp - $start_timestamp;
            $hours = floor($seconds / 3600);
            $mins = floor($seconds / 60 % 60);
            $secs = floor($seconds % 60);
            $timeFormat = sprintf('%02d H %02d M', $hours, $mins, $secs);
            $rideTime = round(abs($endTimeStamp - $start_timestamp) / 60, 2);
            $from = $bookingDetails->start_latitude . "," . $bookingDetails->start_longitude;
            $to = $request->latitude . "," . $request->longitude;
            $coordinates = "";
            $bookingData = new BookingDataController();
            switch ($service_type) {
                case "1":
                    if (!empty($request->app_distance)) {
                        $distance = $request->app_distance;
                    } else {
                        $bookingcoordinates = BookingCoordinate::firstOrNew(['booking_id' => $request->booking_id]);
                        if(!empty($request->updated_booking_coordinates)){
                            $bookingcoordinates->coordinates = $request->updated_booking_coordinates;
                            $bookingcoordinates->save();
                            \Log::channel('debugger_v1')->emergency(["booking_coordinate_saved" => $request->booking_id, "coordinates"=>$request->updated_booking_coordinates]);
                        }
                        $pick = $booking->pickup_latitude . "," . $booking->pickup_longitude;
                        $drop = $booking->drop_latitude . "," . $booking->drop_longitude;
                        $distanceCalculation = new DistanceCalculation();
                        $booking_coordinates = isset($bookingcoordinates['coordinates']) ? $bookingcoordinates['coordinates'] : "";
                        $distance = $distanceCalculation->distance($from, $to, $pick, $drop, $booking_coordinates, $merchant_id, $key, "endRide$booking_id", $string_file, null, $booking->id);
//                        $distance = round($distance);
                        if ($socket_enable == true) {
                            $coordinates = $this->decodeValue($bookingcoordinates['booking_polyline']);
                        } else {
                            $coordinates = $booking_coordinates;
                        }
                        //                    $coordinates = $bookingcoordinates['coordinates'];
                    }
                    break;
                case "5":
                    $units = ($booking->CountryArea->Country['distance_unit'] == 1) ? 'metric' : 'imperial';
                    $selected_map = getSelectedMap($booking->Merchant, "END_RIDE");
                    if($selected_map == "GOOGLE") {
                        $distance = GoogleController::GoogleShortestPathDistance($from, $to, $key, $units, 'metric', "endRide$booking_id", $string_file);
                    }else{
                        $key = get_merchant_google_key($booking->merchant_id,'api', "MAP_BOX");
                        $from = $bookingDetails->start_longitude . "," . $bookingDetails->start_latitude;
                        $to = $request->longitude . "," . $request->latitude;
                        $distance = MapBoxController::MapboxShortestPathDistance($from, $to, $key, $units, 'metric', "endRide$booking_id", $string_file);
                    }
                    saveApiLog($booking->merchant_id, "directions" , "END_RIDE", $selected_map);
                    $distance = round($distance);
                    break;
                case "2":
                    if($booking->Merchant->BookingConfiguration->rental_price_type == 1 && empty($bookingDetails->end_meter_value) && empty($bookingDetails->start_meter_value)){
                        $distance = 0;
                    }else{
                        $distance = $bookingDetails->end_meter_value - $bookingDetails->start_meter_value;
                        $distance = $distance * 1000;
                    }
                default:
                    $distance = $bookingDetails->end_meter_value - $bookingDetails->start_meter_value;
                    $distance = $distance * 1000;
            }

            if ($booking->Merchant->ApplicationConfiguration->mileage_reward == 1) {
                $this->saveUserRewardPoints($distance, $booking->User);
            }

            $merchant = new \App\Http\Controllers\Helper\Merchant();
            $tax_charge = 0;
            $hotel_amount = 0;
            $per_bag_charges = 0;
            $per_pat_charges = 0;
            if($booking->service_type_id == 2 && $booking->Merchant->BookingConfiguration->rental_price_type == 1 && $booking->Segment->id == 1){
                    $estimateIsFinalArr = $this->estimateIsFinalBill($request,$appConfig,$booking,$bookingDetails,$bookingData,$merchant,$merchant_id,$string_file);
                            $total_payable = $estimateIsFinalArr['totalPayable'];
                            $bookingFee = $estimateIsFinalArr['bookingFee'];
                            $amount_for_commission = $estimateIsFinalArr['amountForComission'];
                            $billDetails = $estimateIsFinalArr['bill_details'];
                            $hotel_amount = $estimateIsFinalArr['hotelAmount'];
                            $amount = $estimateIsFinalArr['amount'];
                            $BillDetails = $estimateIsFinalArr['BillDetails'];
                            
                            $amount = $amount + $bookingDetails->tip_amount + $hotel_amount + $per_pat_charges + $per_bag_charges;

                    $booking_transaction_submit = BookingTransaction::updateOrCreate([
                        'booking_id' => $booking_id,
                    ], [
                        'date_time_details' => date('Y-m-d H:i:s'),
                        'sub_total_before_discount' => $amount_for_commission,
                        'surge_amount' => $BillDetails['surge'],
                        'extra_charges' => $BillDetails['extracharge'],
                        'discount_amount' => $BillDetails['promo'],
                        'tax_amount' => $BillDetails['total_tax'],
                        'tip' => $bookingDetails->tip_amount,
                        'insurance_amount' => $BillDetails['insurnce_amount'],
                        'cancellation_charge_received' => $BillDetails['cancellation_amount_received'],
                        'cancellation_charge_applied' => '0.0',
                        'toll_amount' => $BillDetails['toolCharge'],
                        'booking_fee' => $BillDetails['booking_fee'],
                        'cash_payment' => ($booking->PaymentMethod->payment_method_type == 1) ? $total_payable : '0.0',
                        'online_payment' => ($booking->PaymentMethod->payment_method_type == 1) ? '0.0' : $total_payable,
                        'customer_paid_amount' => $total_payable,
                        'rounded_amount' => round_number(($total_payable - $amount)),
                        'merchant_id' => $booking->merchant_id
                    ]);

                    $amount = $merchant->FinalAmountCal($amount, $merchant_id);
                    $bookingDetails->total_amount = $amount;

                    // company earning will deduct from driver account
                    $commission_data = CommonController::NewCommission($booking_id, $booking_transaction_submit['sub_total_before_discount']);

                    $booking_transaction_submit->company_earning = $commission_data['company_cut'];
                    $booking_transaction_submit->driver_earning = $commission_data['driver_cut'];

                    // revenue of driver
                    // Driver Commission + tip + toll
                    $booking_transaction_submit->driver_total_payout_amount = $commission_data['driver_cut'] + $booking_transaction_submit->tip + $booking_transaction_submit->toll_amount + $per_bag_charges + $per_pat_charges;

                    // revenue of merchant
                    // Company Commission + Tax Amt - Discount + Insurance Amt
                    $booking_transaction_submit->company_gross_total = $commission_data['company_cut'] + $booking_transaction_submit->tax_amount - $booking_transaction_submit->discount_amount + $booking_transaction_submit->insurance_amount + $booking_transaction_submit['cancellation_charge_received'];

                    // $booking_transaction_submit->trip_outstanding_amount = $merchant->TripCalculation(($booking_transaction_submit->driver_total_payout_amount + $booking_transaction_submit->amount_deducted_from_driver_wallet - $booking_transaction_submit->cash_payment), $merchant_id);
                    $booking_transaction_submit->trip_outstanding_amount = 0;
                    $booking_transaction_submit->commission_type = $commission_data['commission_type'];
                    if ($booking->hotel_id != '') {
                        $booking_transaction_submit->hotel_earning = $commission_data['hotel_cut'];
                    }
                    // $booking_transaction_submit->amount_deducted_from_driver_wallet = ($commission_data['commission_type'] == 1) ? $commission_data['company_cut'] : $merchant->TripCalculation('0.0', $merchant_id);     //Commission Type: 1:Prepaid (==OR==) 2:Postpaid
                    //                    $booking_transaction_submit->amount_deducted_from_driver_wallet = $commission_data['company_cut'];     //Commission Type: 1:Prepaid (==OR==) 2:Postpaid
                    $booking_transaction_submit->amount_deducted_from_driver_wallet = $booking_transaction_submit->company_gross_total;

                    // Instant settlement For Stripe Connect / Paystack Split
                    if ($booking->payment_method_id == 2 && ($booking->Driver->sc_account_status == 'active' || (isset($booking->Driver->paystack_account_status) && $booking->Driver->paystack_account_status == 'active'))) {
                        $booking_transaction_submit->instant_settlement = 1;
                    } else {
                        $booking_transaction_submit->instant_settlement = 0;
                    }
                    $booking_transaction_submit->save();

                    //Referral Calculation
                    //                    $billDetails = self::checkReferral($booking, $billDetails, $amount);

                    $bookingDetails->bill_details = $billDetails;
                    $bookingDetails->save();
                    $payment = new Payment();
                    if ($amount > 0) {
                        $currency = $booking->CountryArea->Country->isoCode;
                        //                      $currency = $booking->CountryArea->Country->isoCode;
                        $array_param = array(
                            'booking_id' => $booking->id,
                            'payment_method_id' => $booking->payment_method_id,
                            'amount' => $amount,
                            'user_id' => $booking->user_id,
                            'card_id' => $booking->card_id,
                            'currency' => $currency,
                            'quantity' => 1,
                            'order_name' => $booking->merchant_booking_id,
                            'payment_option_id' => $booking->payment_option_id, // payment Option ID
                            'booking_transaction' => $booking_transaction_submit,
                            'driver_sc_account_id' => $booking->Driver->sc_account_id,
                            'merchant_id' => $booking->merchant_id,
                            'driver_paystack_account_id' => $booking->Driver->paystack_account_id
                        );
                        $payment->MakePayment($array_param);
                        //                        $payment->MakePayment($booking->id, $booking->payment_method_id, $amount, $booking->user_id, $booking->card_id, $currency, $booking_transaction_submit, $booking->Driver->sc_account_id);
                        $booking = $booking->fresh();
                    } else {
                        $payment->UpdateStatus(['booking_id' => $booking->id]);
                    }

                    //Referral Calculation
                    $ref = new ReferralController();
                    $arr_params = array(
                        "segment_id" => $booking->segment_id,
                        "driver_id" => $booking->driver_id,
                        "user_id" => $booking->user_id,
                        "booking_id" => $booking->id,
                        "user_paid_amount" => $amount,
                        "driver_paid_amount" => $amount,
                        "check_referral_at" => "OTHER"
                    );
                    $ref->checkReferral($arr_params);

                    RewardPoint::incrementUserTripCount(User::find($booking->user_id));
                    if ($booking->User->outstanding_amount) {
                        User::where([['id', '=', $booking->user_id]])->update(['outstanding_amount' => NULL]);
                    }
                    if ($booking->Merchant->Configuration->cashback_module == 1) :
                        $cashback = new CashbackController();
                        $cashback->ProvideCashback($booking->country_area_id, $booking->service_type_id, $booking->vehicle_type_id, $booking, $amount);
                    endif;
            }else{
                switch ($pricing_type) {
                case "1":
                case "2":
                    // When estimate bill is equals to final bill
                    //Aman Changes for delivery and taxi
                     if($booking->Segment->id == 2){ //for delivery
                        // When estimate bill is equals to final bill
                        if ($configuration->final_bill_calculation_delivery == 2) {
                            $estimateIsFinalArr = $this->estimateIsFinalBill($request,$appConfig,$booking,$bookingDetails,$bookingData,$merchant,$merchant_id,$string_file);
                            $total_payable = $estimateIsFinalArr['totalPayable'];
                            $bookingFee = $estimateIsFinalArr['bookingFee'];
                            $amount_for_commission = $estimateIsFinalArr['amountForComission'];
                            $billDetails = $estimateIsFinalArr['bill_details'];
                            $hotel_amount = $estimateIsFinalArr['hotelAmount'];
                            $amount = $estimateIsFinalArr['amount'];
                            $BillDetails = $estimateIsFinalArr['BillDetails'];

                        }else{
                            $actualFinalArr = $this->actualFinalBill($appConfig,$request,$booking,$bookingDetails,$merchant_id,$distance,$rideTime,$from,$to,$coordinates,$bookingData,$merchant,$string_file);
                            $total_payable = $actualFinalArr['totalPayable'];
                            $bookingFee = $actualFinalArr['bookingFee'];
                            $amount_for_commission = $actualFinalArr['amountForComission'];
                            $billDetails = $actualFinalArr['bill_details'];
                            $hotel_amount = $actualFinalArr['hotelAmount'];
                            $amount = $actualFinalArr['amount'];
                            $BillDetails = $actualFinalArr['BillDetails'];
                            $per_pat_charges = $actualFinalArr['per_pat_charges'];
                            $per_bag_charges = $actualFinalArr['per_bag_charges'];

                        }
                    }else if($booking->Segment->id == 1){ //for taxi
                        // When estimate bill is equals to final bill
                        if ($configuration->final_bill_calculation == 2 || empty($booking->BookingCoordinate->coordinates)) {
                            $estimateIsFinalArr = $this->estimateIsFinalBill($request,$appConfig,$booking,$bookingDetails,$bookingData,$merchant,$merchant_id,$string_file);
                            $total_payable = $estimateIsFinalArr['totalPayable'];
                            $bookingFee = $estimateIsFinalArr['bookingFee'];
                            $amount_for_commission = $estimateIsFinalArr['amountForComission'];
                            $billDetails = $estimateIsFinalArr['bill_details'];
                            $hotel_amount = $estimateIsFinalArr['hotelAmount'];
                            $amount = $estimateIsFinalArr['amount'];
                            $BillDetails = $estimateIsFinalArr['BillDetails'];
                        }else{
                             $actualFinalArr = $this->actualFinalBill($appConfig,$request,$booking,$bookingDetails,$merchant_id,$distance,$rideTime,$from,$to,$coordinates,$bookingData,$merchant,$string_file);
                            $total_payable = $actualFinalArr['totalPayable'];
                            $bookingFee = $actualFinalArr['bookingFee'];
                            $amount_for_commission = $actualFinalArr['amountForComission'];
                            $billDetails = $actualFinalArr['bill_details'];
                            $hotel_amount = $actualFinalArr['hotelAmount'];
                            $amount = $actualFinalArr['amount'];
                            $BillDetails = $actualFinalArr['BillDetails'];
                            $per_pat_charges = $actualFinalArr['per_pat_charges'];
                            $per_bag_charges = $actualFinalArr['per_bag_charges'];
                        }
                    }
                    // if ($configuration->final_bill_calculation == 2) {
                    //     $bill_details_data = [];
                    //     $tip_And_toll = self::storeTipAndTollCharge($appConfig, $request, $booking, $bookingDetails);
                    //     $booking->fresh();
                    //     $tip_amount = $tip_And_toll['tip_amount'];
                    //     if (empty($booking->bill_details)) {
                    //         throw new Exception("Bill details not found.");
                    //     }
                    //     $BillDetails = $this->EstimateBillDetailsBreakup($booking->bill_details, $booking->estimate_bill);
                    //     // $amount = $booking->estimate_bill;
                    //     if ($booking->Merchant->BookingConfiguration->corporate_price_card == 1) {
                    //         $booking_data_helper = new BookingDataController();
                    //         $BillDetails = $booking_data_helper->getCorporatePriceBilling($BillDetails, $booking->price_card_id, $booking->corporate_id);
                    //     }
                    //     $amount = $BillDetails['amount'];
                    //     $hotel_amount = $BillDetails['hotel_amount'];

                    //     if (!empty($booking->price_for_ride) && $booking->price_for_ride != 1) {
                    //         $BillDetails = $bookingData->checkPriceForRide($booking, $BillDetails);
                    //         $booking->bill_details = json_encode($BillDetails['bill_details'], true);
                    //         $booking->save();
                    //         $hotel_amount = 0;  // in case of maximum fare and fix fare
                    //     }

                    //     $total_payable = $merchant->FinalAmountCal(($amount + $bookingDetails->tip_amount), $merchant_id);
                    //     Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->update(['pay_status' => 1]);
                    //     $bookingFee = $BillDetails['booking_fee'];
                    //     $amount_for_commission = $BillDetails['subTotalWithoutDiscount'] - $bookingFee;
                    //     $billDetails = json_encode($BillDetails['bill_details'], true);
                    // } else {
                    //     $outstanding_amount = Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->sum('amount');
                    //     //                        $priceCard = PriceCard::find($price_card_id);
                    //     //                        date_default_timezone_set($priceCard->CountryArea->timezone);
                    //     // creating time zone issues

                    //     //this convertTimeToUSERzone function added to fix for night and peak charges
                    //     $bookingTime = convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone, $merchant_id, $booking->Merchant, 3);
                    //     $bookingDate = convertTimeToUSERzone($booking->created_at, $booking->CountryArea->timezone, $merchant_id, $booking->Merchant, 2);
                    //     $finalBill = new PriceController();
                    //     $BillDetails = $finalBill->BillAmount([
                    //         'price_card_id' => $price_card_id,
                    //         'merchant_id' => $merchant_id,
                    //         'distance' => $distance,
                    //         'time' => $rideTime,
                    //         'booking_id' => $booking_id,
                    //         'user_id' => $booking->user_id,
                    //         'driver_id' => $booking->driver_id,
                    //         'booking_time' => $booking->booking_type == 2 ? $booking->later_booking_time : $bookingTime,
                    //         'booking_date' => $booking->booking_type == 2 ? $booking->later_booking_date : $bookingDate,
                    //         'waitTime' => $bookingDetails->wait_time,
                    //         'dead_milage_distance' => $bookingDetails->dead_milage_distance,
                    //         'outstanding_amount' => $outstanding_amount,
                    //         'number_of_rider' => $booking->number_of_rider,
                    //         'from' => $from,
                    //         'to' => $to,
                    //         'coordinates' => $coordinates,
                    //         'units' => $booking->CountryArea->Country['distance_unit'],
                    //         'manual_toll_charge' => isset($request->manual_toll_charge) ? $request->manual_toll_charge : '',
                    //         'hotel_id' => !empty($booking->hotel_id) ? $booking->hotel_id : NULL,
                    //         'additional_movers' => !empty($booking->additional_movers) ? $booking->additional_movers : NULL,
                    //     ]);
                    //     //different corporate booking price
                    //     if ($booking->Merchant->BookingConfiguration->corporate_price_card == 1) {
                    //         $booking_data_helper = new BookingDataController();
                    //         $BillDetails = $booking_data_helper->getCorporatePriceBilling($BillDetails, $booking->price_card_id, $booking->corporate_id);
                    //     }

                    //     if ($booking->Merchant->BookingConfiguration->driver_booking_amount == 1) {
                    //         $amount = $request->ride_amount;
                    //         $amountCommission = $request->ride_amount;
                    //     } else {
                    //         $amount = $BillDetails['amount'];
                    //         $amountCommission = $BillDetails['subTotalWithoutDiscount'];
                    //     }

                    //     Outstanding::where(['user_id' => $booking->user_id, 'reason' => '1', 'pay_status' => 0])->update(['pay_status' => 1]);
                    //     // amount => Sub total without discount + extra charges(night time /peck time) + surge charge - promo + taxs + insurance + toll charges +  cancellation_amount_received
                    //     if (!empty($booking->price_for_ride)) {
                    //         $BillDetails = $bookingData->checkPriceForRide($booking, $BillDetails);
                    //         $amount = $BillDetails['amount'];
                    //         $amountCommission = $BillDetails['subTotalWithoutDiscount'];
                    //     }
                    //     $total_payable = $merchant->FinalAmountCal(($amount + $bookingDetails->tip_amount), $merchant_id);

                    //     $bookingFee = $BillDetails['booking_fee'];
                    //     $amount_for_commission = $amountCommission - $bookingFee;

                    //     $newArray = $BillDetails['bill_details'];
                    //     $bookingDetails->promo_discount = $BillDetails['promo'];
                    //     if ($appConfig->tip_status == 1) {
                    //         $tip_parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => "Tip", 'parameterType' => "tip_amount", 'amount' => $bookingDetails->tip_amount, 'type' => "CREDIT", 'code' => "");
                    //         array_push($newArray, $tip_parameter);
                    //     }

                    //     // No Of Bag Charges
                    //     if (isset($booking->Merchant->Configuration->chargable_no_of_bags) && $booking->Merchant->Configuration->chargable_no_of_bags == 1) {
                    //         if ($booking->no_of_bags > 0 && $booking->PriceCard->per_bag_charges > 0) {
                    //             $per_bag_charges = $booking->PriceCard->per_bag_charges * $booking->no_of_bags;
                    //             $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans("$string_file.bag_charges"), 'amount' => $per_bag_charges, 'type' => "CREDIT", 'code' => "");
                    //             array_push($newArray, $parameter);
                    //         }
                    //     }
                    //     // No Of Pat Charges
                    //     if (isset($booking->Merchant->Configuration->chargable_no_of_pats) && $booking->Merchant->Configuration->chargable_no_of_pats == 1) {
                    //         if ($booking->no_of_pats > 0 && $booking->PriceCard->per_pat_charges > 0) {
                    //             $per_pat_charges = $booking->PriceCard->per_pat_charges * $booking->no_of_pats;
                    //             $parameter = array('price_card_id' => $price_card_id, 'booking_id' => $booking_id, 'parameter' => trans("$string_file.pat_charges"), 'amount' => $per_pat_charges, 'type' => "CREDIT", 'code' => "");
                    //             array_push($newArray, $parameter);
                    //         }
                    //     }

                    //     $billDetails = json_encode($newArray);
                    //     $hotel_amount = $BillDetails['hotel_amount'];
                    // }
                    $amount = $amount + $bookingDetails->tip_amount + $hotel_amount + $per_pat_charges + $per_bag_charges;

                    $booking_transaction_submit = BookingTransaction::updateOrCreate([
                        'booking_id' => $booking_id,
                    ], [
                        'date_time_details' => date('Y-m-d H:i:s'),
                        'sub_total_before_discount' => $amount_for_commission,
                        'surge_amount' => $BillDetails['surge'],
                        'extra_charges' => $BillDetails['extracharge'],
                        'discount_amount' => $BillDetails['promo'],
                        'tax_amount' => $BillDetails['total_tax'],
                        'tip' => $bookingDetails->tip_amount,
                        'insurance_amount' => $BillDetails['insurnce_amount'],
                        'cancellation_charge_received' => $BillDetails['cancellation_amount_received'],
                        'cancellation_charge_applied' => '0.0',
                        'toll_amount' => $BillDetails['toolCharge'],
                        'booking_fee' => $BillDetails['booking_fee'],
                        'cash_payment' => ($booking->PaymentMethod->payment_method_type == 1) ? $total_payable : '0.0',
                        'online_payment' => ($booking->PaymentMethod->payment_method_type == 1) ? '0.0' : $total_payable,
                        'customer_paid_amount' => $total_payable,
                        'rounded_amount' => round_number(($total_payable - $amount)),
                        'merchant_id' => $booking->merchant_id
                    ]);

                    $amount = $merchant->FinalAmountCal($amount, $merchant_id);
                    $bookingDetails->total_amount = $amount;

                    // company earning will deduct from driver account
                    $commission_data = CommonController::NewCommission($booking_id, $booking_transaction_submit['sub_total_before_discount']);

                    $booking_transaction_submit->company_earning = $commission_data['company_cut'];
                    $booking_transaction_submit->driver_earning = $commission_data['driver_cut'];

                    // revenue of driver
                    // Driver Commission + tip + toll
                    $booking_transaction_submit->driver_total_payout_amount = $commission_data['driver_cut'] + $booking_transaction_submit->tip + $booking_transaction_submit->toll_amount + $per_bag_charges + $per_pat_charges;

                    // revenue of merchant
                    // Company Commission + Tax Amt - Discount + Insurance Amt
                    $booking_transaction_submit->company_gross_total = $commission_data['company_cut'] + $booking_transaction_submit->tax_amount - $booking_transaction_submit->discount_amount + $booking_transaction_submit->insurance_amount + $booking_transaction_submit['cancellation_charge_received'];

                    // $booking_transaction_submit->trip_outstanding_amount = $merchant->TripCalculation(($booking_transaction_submit->driver_total_payout_amount + $booking_transaction_submit->amount_deducted_from_driver_wallet - $booking_transaction_submit->cash_payment), $merchant_id);
                    $booking_transaction_submit->trip_outstanding_amount = 0;
                    $booking_transaction_submit->commission_type = $commission_data['commission_type'];
                    if ($booking->hotel_id != '') {
                        $booking_transaction_submit->hotel_earning = $commission_data['hotel_cut'];
                    }
                    // $booking_transaction_submit->amount_deducted_from_driver_wallet = ($commission_data['commission_type'] == 1) ? $commission_data['company_cut'] : $merchant->TripCalculation('0.0', $merchant_id);     //Commission Type: 1:Prepaid (==OR==) 2:Postpaid
                    //                    $booking_transaction_submit->amount_deducted_from_driver_wallet = $commission_data['company_cut'];     //Commission Type: 1:Prepaid (==OR==) 2:Postpaid
                    $booking_transaction_submit->amount_deducted_from_driver_wallet = $booking_transaction_submit->company_gross_total;

                    // Instant settlement For Stripe Connect / Paystack Split
                    if ($booking->payment_method_id == 2 && ($booking->Driver->sc_account_status == 'active' || (isset($booking->Driver->paystack_account_status) && $booking->Driver->paystack_account_status == 'active'))) {
                        $booking_transaction_submit->instant_settlement = 1;
                    } else {
                        $booking_transaction_submit->instant_settlement = 0;
                    }
                    $booking_transaction_submit->save();

                    //Referral Calculation
                    //                    $billDetails = self::checkReferral($booking, $billDetails, $amount);

                    $bookingDetails->bill_details = $billDetails;
                    $bookingDetails->save();
                    $payment = new Payment();
                    if ($amount > 0) {
                        $currency = $booking->CountryArea->Country->isoCode;
                        //                      $currency = $booking->CountryArea->Country->isoCode;
                        $array_param = array(
                            'booking_id' => $booking->id,
                            'payment_method_id' => $booking->payment_method_id,
                            'amount' => $amount,
                            'user_id' => $booking->user_id,
                            'card_id' => $booking->card_id,
                            'currency' => $currency,
                            'quantity' => 1,
                            'order_name' => $booking->merchant_booking_id,
                            'payment_option_id' => $booking->payment_option_id, // payment Option ID
                            'booking_transaction' => $booking_transaction_submit,
                            'driver_sc_account_id' => $booking->Driver->sc_account_id,
                            'merchant_id' => $booking->merchant_id,
                            'driver_paystack_account_id' => $booking->Driver->paystack_account_id
                        );
                        $payment->MakePayment($array_param);
                        //                        $payment->MakePayment($booking->id, $booking->payment_method_id, $amount, $booking->user_id, $booking->card_id, $currency, $booking_transaction_submit, $booking->Driver->sc_account_id);
                        $booking = $booking->fresh();
                    } else {
                        $payment->UpdateStatus(['booking_id' => $booking->id]);
                    }

                    RewardPoint::incrementUserTripCount(User::find($booking->user_id));
                    if ($booking->User->outstanding_amount) {
                        User::where([['id', '=', $booking->user_id]])->update(['outstanding_amount' => NULL]);
                    }
                    if ($booking->Merchant->Configuration->cashback_module == 1) :
                        $cashback = new CashbackController();
                        $cashback->ProvideCashback($booking->country_area_id, $booking->service_type_id, $booking->vehicle_type_id, $booking, $amount);
                    endif;
                    break;
                case "3":
                    $amount = "";
                    break;
            }
            }
            if ($service_type_id == 5) {
                $poolRide = new PoolController();
                $poolRide->DropPool($booking, $request);
            }

            $distance_unit = $booking->CountryArea->Country->distance_unit;
            $div = $distance_unit == 1 ? 1000 : 1609;
            $distance = round($distance / $div, 2) . ' ' . ($distance_unit == 1 ? 'Km' : 'mi');

            $booking->booking_status = 1005;
            if (isset($appConfig->user_rating_enable) && $appConfig->user_rating_enable != 1 && $pricing_type != 3 && $booking->payment_status == 1) {
                $booking->booking_closure = 1;
            }
            // dd($amount);
            $booking->travel_distance = (empty($booking->BookingCoordinate->coordinates) && $configuration->final_bill_calculation_delivery != 2) ? $booking->estimate_distance : $distance;
            $booking->travel_time =  (empty($booking->BookingCoordinate->coordinates) && $configuration->final_bill_calculation_delivery != 2) ? $booking->estimate_time : $timeFormat;
            $booking->travel_time_min = $rideTime;
            $booking->final_amount_paid = $amount;
            $booking->bill_details = $billDetails;
            $booking->save();

            //Referral Calculation
            $ref = new ReferralController();
            $arr_params = array(
                "segment_id" => $booking->segment_id,
                "driver_id" => $booking->driver_id,
                "user_id" => $booking->user_id,
                "booking_id" => $booking->id,
                "user_paid_amount" => $amount,
                "driver_paid_amount" => $amount,
                "check_referral_at" => "OTHER"
            );
            $ref->checkReferral($arr_params);

            //reward point
            $reward = new RewardPoint();
            $reward->GlobalReward($booking->merchant_id, 1, $booking->User, $booking->User->country_id, null, 'TRIP_EXPENSE', ['amount' => $amount]);
            $reward->GlobalReward($booking->merchant_id, 2, $booking->Driver, null, $booking->Driver->country_area_id, 'COMMISSION_PAID', ['commission_amount' => $booking_transaction_submit->driver_earning]);

            $free_busy = 1;
            if ($service_type_id == 5) {
                $runningBooking = Booking::where([['service_type_id', 5], ['driver_id', $booking->driver_id]])->whereIn('booking_status', [1001, 1002, 1003, 1004])->latest()->first();
                if (empty($runningBooking)) {
                    $free_busy = 2;
                }
            } else {
                $free_busy = 2;
            }

            $driver = $request->user('api-driver');
            $driver->total_trips = $request->user('api-driver')->total_trips + 1;
            $driver->free_busy = $free_busy;
            $driver->save();

            $user = User::find($booking->user_id);
            $user->total_trips = $user->total_trips + 1;
            $user->save();
            $booking = $booking->fresh();

            // if($config->cash_payment_method_option == '1' && $booking->booking_status == '1005' && $user->cash_method_avaliable == 'NO' && $booking->payment_method_id != 1 && $booking->segment_id == 1){
            //     $usre->enable_ride_count_value = $user->enable_ride_count_value-1;
            //     $user->save();
            //     if($user->enable_ride_count_value == 0){
            //         $user->cash_method_avaliable = 'YES';
            //         $user->enable_ride_count_value = null;
            //         $user->disable_ride_count_value = null;
            //         $user->save();
            //     }
            // }

            // insert booking status history
            $this->saveBookingStatusHistory($request, $booking, $booking->id, $driver->Merchant->ApplicationConfiguration->endlocation_polyline_calculate);


            $ride_earning_type = 2; // commission based
            if ($driver->Merchant->Configuration->subscription_package == 1 && $driver->pay_mode == 1) {
                $ride_earning_type = 1;
                $this->SubscriptionPackageExpiryCheck($driver, $booking->segment_id);
                // $this->SubscriptionPackageExpiryCheck($driver,$booking);
            }
            $booking_transaction_submit->ride_type_earning = $ride_earning_type; //  subscription based
            $booking_transaction_submit->save();

            if (in_array($pricing_type, [1, 2])) {
                //                $data = $bookingData->BookingNotification($booking);
                //                $message = $bookingData->LanguageData($booking->merchant_id, 34);
                //                Onesignal::UserPushMessage($booking->user_id, $data, $message, 1, $booking->merchant_id);

                $SmsConfiguration = SmsConfiguration::select('ride_end_enable', 'ride_end_msg')->where([['merchant_id', '=', $merchant_id]])->first();
                if (!empty($SmsConfiguration) && $SmsConfiguration->ride_end_enable == 3) {
                    $sms = new SmsController();
                    $phone = $booking->User->UserPhone;
                    $sms->SendSms($merchant_id, $phone, null, 'RIDE_END', $booking->User->email);
                }
            }
            // dd(json_decode($booking->Driver->driver_additional_data,true));
            if ($booking->payment_status == 1) {
                try {
                    event(new SendUserInvoiceMailEvent($booking));
                    // event(new SendDriverInvoiceMailEvent($booking));
                    //                    event(new SendUserInvoiceMailEvent($booking, 'invoice'));
                    $config = Configuration::where('merchant_id', $merchant_id)->first();
                    if (isset($config->whatsapp_notification) && $config->whatsapp_notification == 1) {
                        SendWhatsappNotificationEvent::dispatch($merchant_id, 1005, $booking);
                    }
                } catch (Exception $e) {
                    p($e->getMessage());
                }
            } else {
                // commented by amba
                //                $paymentMethods = $booking->CountryArea->PaymentMethod->toArray();
                //                $is_cash_available = in_array(1, array_column($paymentMethods, 'id'));
                // If booking payment method is card and merchant not have any case payment method then.
                //Editing by @Amba, out standing will be creating when user click on paylater from receipt screen
                //                if (isset($config->user_outstanding_enable) && $config->user_outstanding_enable == 1 && $booking->payment_method_id == 2 && $is_cash_available != 1) {
                //                    // Later pay before next ride.
                //                    CommonController::AddUserRideOutstading($booking->user_id, $booking->driver_id, $booking->id, $amount);
                //                    $payment->UpdateStatus(['booking_id' => $booking->id]);
                //                    BookingDetail::where([['booking_id', '=', $booking->id]])->update(['pending_amount' => $amount]);
                //                }
            }
            //            return $this->successResponse(trans('api.message15'), $booking);
        } catch (Exception $e) {
            DB::rollBack();
            $message = $e->getMessage();
            return $this->failedResponse($message);
        }
        DB::commit();

        // clear the call masking session
        if (isset($config->twilio_call_masking) && $config->twilio_call_masking == 1) {
            TwilioMaskingHelper::close_session($booking);
        }

        // send notification after commit
        $notification_data = $bookingData->bookingNotificationForUser($booking, "END_RIDE");
        $booking_data = new BookingDataController;
        $return_data = $booking_data->bookingReceiptForDriver($request);
        // call
        $this->updateRideAmountInDriverWallet($booking, $booking_transaction_submit, $booking->id);
        // for tip adding to wallet of driver
        if ($booking->booking_status == 1005 && $booking->payment_status == 1 && isset($bookingDetails->tip_amount) && !empty($bookingDetails->tip_amount)) {
                $tip_amount = $bookingDetails->tip_amount;
                // make payment calls if payment method is not cash
                if ($booking->payment_method_id != 1) {
                    // credit driver wallet
                    $paramArray = array(
                        'driver_id' => $booking->driver_id,
                        'booking_id' => $booking->id,
                        'order_id' => NULL,
                        'handyman_order_id' => NULL,
                        'amount' => $tip_amount,
                        'narration' => 16,
                    );
                    WalletTransaction::WalletCredit($paramArray);
                }
                // update total amount of driver in transaction
                $booking_transaction = $booking->BookingTransaction;
                $driver_existing_amount = $booking_transaction->driver_total_payout_amount;
                $existing_booking_amount = $booking_transaction->customer_paid_amount;
                $existing_online_payment = $booking_transaction->online_payment;
                $existing_cash_payment = $booking_transaction->cash_payment;
                $booking_transaction->tip = $tip_amount;
                $booking_transaction->driver_total_payout_amount = $driver_existing_amount + $tip_amount;
                $booking_transaction->customer_paid_amount = $existing_booking_amount + $tip_amount;
                if ($booking->payment_method_id == 1) {
                    $booking_transaction->cash_payment = $existing_cash_payment + $tip_amount;
                } else {
                    $booking_transaction->online_payment = $existing_online_payment + $tip_amount;
                }
                $booking_transaction->save();
                // tip credited notification
                setLocal($booking->Driver->language);
                $data = array('notification_type' => 'TIP_ADDED', 'segment_type' => $booking->Segment->slag, 'segment_data' => []);
                $arr_param = array(
                    'driver_id' => $booking->driver_id,
                    'data' => $data,
                    'message' => trans("$string_file.tip_credited_to_driver"),
                    'merchant_id' => $booking->merchant_id,
                    'title' => trans("$string_file.tip_credited")
                );
                Onesignal::DriverPushMessage($arr_param);
                setLocal();
        }
        if(!empty($request->timestampvalue)){
            Cache::put($cacheKey, ["message" => trans("$string_file.success"), "return_data" => $return_data], 120);
        }
        return $this->successResponse(trans("$string_file.success"), $return_data);
    }
    public function updateRideAmountInDriverWallet($booking = NULL, $booking_transaction = NULL, $booking_id = NULL)
    {
        // Make the payment of driver instant
        /** This code will be executed when payment is done (wallet or card payment)
         ***/
        if ($booking->payment_status == 1) {
            $driverPayment = new CommonController();
            $array_param = array(
                'booking_id' => $booking->id,
                'driver_id' => $booking->driver_id,
                //            'amount' => $amount,
                'payment_method_type' => $booking->PaymentMethod->payment_method_type,
                //            'discount_amount' => $booking_transaction_submit->discount_amount,
                //            'tax_amount' => $booking_transaction_submit->tax_amount,
                //            'cancellation_amount_received' => $BillDetails['cancellation_amount_received'],
            );
            if (empty($booking) && !empty($booking_id)) {
                $booking = Booking::find($booking_id);
                $booking_transaction = $booking->BookingTransaction;
            }
            //    || $booking->PaymentMethod->payment_method_id == 3
            // debit drive wallet for merchant commission
            if ($booking_transaction->ride_type_earning == 1) // subscription based
            {
                if ($booking->payment_method_id != 1 && $booking->payment_method_id != 5) // cash or swipe card payment
                {
                    $array_param['amount'] = $booking_transaction->customer_paid_amount; // all amount paid by user because driver already bought subscription package
                    $array_param['wallet_status'] = 'CREDIT';
                    $array_param['narration'] = 6;
                    $driverPayment->DriverRideAmountCredit($array_param);
                }
            } else // commission based
            {
                if ($booking->payment_method_id == 1 || $booking->payment_method_id == 5) //swipe card payment
                {
                    $array_param['amount'] = $booking_transaction->company_gross_total;
                    $array_param['wallet_status'] = 'DEBIT';
                    $array_param['narration'] = 3;
                } else // cash and online payment like card, payment gateway, wallet.
                {
                    $array_param['amount'] = $booking_transaction->driver_earning;
                    $array_param['wallet_status'] = 'CREDIT';
                    $array_param['narration'] = 6;
                }
                $driverPayment->DriverRideAmountCredit($array_param);

                // If driver payment slited with payment gateway like stripe connect and paytack, then debit credited amount
                if ($booking_transaction->instant_settlement == 1) {
                    $new_array_param['booking_id'] = $booking->id;
                    $new_array_param['driver_id'] = $booking->driver_id;
                    $new_array_param['amount'] = $booking_transaction->driver_total_payout_amount;
                    $new_array_param['wallet_status'] = 'DEBIT';
                    $new_array_param['narration'] = 25;
                    $driverPayment->DriverRideAmountCredit($new_array_param);
                }
            }
        }
    }

    // $delivery_drop_condition - it should be 1 for otp and 2 for qr code
    public function deliveryDetailStore($request, $booking, $delivery_drop_condition = 1)
    {
        $podOption = $booking->Merchant->ApplicationConfiguration->proof_of_delivery; // 1=> upload image,2=> Signature , 3=> Both

        $validate_arr = [];
        if ($booking->platform == 1) {
            // $validate_arr = [
            //     'receiver_otp' => 'required',
            // ];

            $validate_arr = [];
                if($booking->Merchant->ApplicationConfiguration->enter_crn_number == 1){
                    $validate_arr['receiver_otp'] = 'required';
                }

            if ($delivery_drop_condition == 1) {
                $validate_arr = array_merge($validate_arr, array(
                    //                'receiver_otp' => 'required',
                    'receiver_name' => 'required',
                    'receiver_image' => ($podOption == 2 || $podOption == 3) ? 'required' : '',
                    'upload_delivery_image' => ($podOption == 1 || $podOption == 3) ? 'required' : '',
                ));
                $validator = Validator::make($request->all(), $validate_arr);
            }
            if ($delivery_drop_condition == 2) {
                $validator = Validator::make($request->all(), $validate_arr);
            }
            if(!empty($delivery_drop_condition)){
                if ($validator->fails()) {
                    $errors = $validator->messages()->all();
                    throw new Exception($errors[0]);
                }
            }
        }


        $booking_delivery_detail = BookingDeliveryDetails::where(['booking_id' => $booking->id, 'otp_status' => 0, 'drop_status' => 0])->orderBy('stop_no', 'DESC')->first();
        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(NULL, $driver->Merchant);
        if (!empty($booking_delivery_detail)) {
            if ($booking->platform == 1 && $delivery_drop_condition == 1){
                if ($request->receiver_otp == $booking_delivery_detail->opt_for_verify || $booking->Merchant->ApplicationConfiguration->enter_crn_number == 2) {
                    $booking_delivery_detail->otp_status = 1;
                } else {
                    throw new Exception(trans("$string_file.invalid_otp_try_again"));
                }
            }
            $booking_delivery_detail->drop_status = 1;
            $booking_delivery_detail->receiver_name = $request->receiver_user_name;
            $booking_delivery_detail->receiver_phone = isset($request->receiver_name) ? $request->receiver_name : '';
                if (!empty($request->receiver_image)) {
                    if($request->multi_part == 1){
                        $additional_req = ['compress' => true,'custom_key' => 'product'];
                        $booking_delivery_detail->receiver_image = $this->uploadImage('receiver_image', 'booking_images', $booking->merchant_id,'single',$additional_req);
                    }else{
                        $booking_delivery_detail->receiver_image = $this->uploadBase64Image('receiver_image', 'booking_images', $booking->merchant_id);
                    }
            }
            if(!empty($request->upload_delivery_image)){
                if($request->multi_part == 1){
                    $additional_req = ['compress' => true,'custom_key' => 'product'];
                    $booking_delivery_detail->upload_delivery_image = $this->uploadImage('upload_delivery_image', 'booking_images', $booking->merchant_id,'single',$additional_req);
                }else{
                    $booking_delivery_detail->upload_delivery_image = $this->uploadBase64Image('upload_delivery_image', 'booking_images', $booking->merchant_id);
                }
            }
            //                    $this->uploadImage('receiver_image', 'booking_images', $booking->merchant_id);
            $booking_delivery_detail->drop_latitude = $request->latitude;
            $booking_delivery_detail->drop_longitude = $request->longitude;
            $booking_delivery_detail->save();
        }
    }

    public function storeBookingDetails($request, $booking, $outstation_inside_City, $key, $socket_enable = false)
    {
        try {
            //            $service_type_id = $booking->service_type_id;
            $service_type = $booking->ServiceType->type;
            $bookingDetails = BookingDetail::where('booking_id', $booking->id)->first();
            $string_file = $this->getStringFile($booking->merchant_id);
            if($booking->Merchant->BookingConfiguration->rental_price_type == 1){
                if (in_array($service_type, array(4)) && $outstation_inside_City == false) {
                    $start_meter_value = $bookingDetails->start_meter_value;
                    $customMessages = [
                        'gt' => trans_choice(trans("$string_file.end_meter_warning"), 3, ['value' => $start_meter_value]),
                    ];
                    $validator = Validator::make($request->all(), [
                        'send_meter_image' => 'required',
                        'send_meter_value' => 'required|numeric|gt:' . $start_meter_value,
                    ], $customMessages);
                    if ($validator->fails()) {
                        $errors = $validator->messages()->all();
                        throw new Exception($errors[0]);
                    }
                }
            }else{
                if (in_array($service_type, array(2, 4)) && $outstation_inside_City == false) {
                $start_meter_value = $bookingDetails->start_meter_value;
                $customMessages = [
                    'gt' => trans_choice(trans("$string_file.end_meter_warning"), 3, ['value' => $start_meter_value]),
                ];
                $validator = Validator::make($request->all(), [
                    'send_meter_image' => 'required',
                    'send_meter_value' => 'required|numeric|gt:' . $start_meter_value,
                ], $customMessages);
                if ($validator->fails()) {
                    $errors = $validator->messages()->all();
                    throw new Exception($errors[0]);
                }
            }
            }

            if (!empty($request->send_meter_image)) {
                if($request->multi_part == "1"){
                    $additional_req = ['compress' => true,'custom_key' => 'product'];
                    $send_meter_image = $this->uploadImage('send_meter_image', 'send_meter_image', $booking->merchant_id,'single',$additional_req);
                }else{
                    $send_meter_image = $this->uploadBase64Image('send_meter_image', 'send_meter_image', $booking->merchant_id);
                }

                $bookingDetails->end_meter_image = $send_meter_image;
            }
            $bookingDetails->end_meter_value = isset($request->send_meter_value) && !empty($request->send_meter_value)? $request->send_meter_value : null;
            if ($outstation_inside_City == true) {
                $bookingDetails->end_meter_value = 1200;
            }
            $selected_map = getSelectedMap($booking->Merchant, "END_RIDE");
            if($selected_map == "GOOGLE"){
                $endAddress = GoogleController::GoogleLocation($request->latitude, $request->longitude, $key, 'endRide', $string_file);
            }
            else{
                $key = get_merchant_google_key($booking->merchant_id,'api', "MAP_BOX");
                $endAddress = MapBoxController::MapboxLocation($request->latitude, $request->longitude, $key, 'endRide', $string_file);
            }
            saveApiLog($booking->merchant_id, "geocode" , "END_RIDE", $selected_map);
            $endAddress = $endAddress ? $endAddress : 'Address Not found';
            $endTimeStamp = strtotime('now');
            $bookingDetails->end_timestamp = $endTimeStamp;
            $bookingDetails->end_latitude = $request->latitude;
            $bookingDetails->end_longitude = $request->longitude;
            $bookingDetails->end_location = $endAddress;
            $bookingDetails->accuracy_at_end = $request->accuracy;
            $bookingDetails->save();
            // booking polyline
            if ($socket_enable == true) {
                $booking_coordinates = $booking->BookingCoordinate;
                if (empty($booking->BookingCoordinate->id)) {
                    $booking_coordinates = new BookingCoordinate;
                    $booking_coordinates->booking_id = $booking->id;
                }
                $booking_coordinates->booking_polyline = $request->booking_polyline;
                $booking_coordinates->save();
            }

            return $bookingDetails;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function storeTipAndTollCharge($appConfig, $request, $bookingObj, $bookingDetails)
    {
        try {
            $bill_details_data = [];
            $booking = Booking::find($bookingObj->id);
            if (!empty($booking->bill_details)) {
                $bill_details_data = json_decode($booking->bill_details, true);
            }
            $toll_charge = isset($request->manual_toll_charge) ? $request->manual_toll_charge : 0;
            // Update Toll Amount
            foreach ($bill_details_data as $key => $value) {
                if (array_key_exists('parameter', $value) && $value['parameter'] == "TollCharges") {
                    $bill_details_data[$key]['amount'] = $toll_charge;
                }
            }
            // Add Tip amount in bill details if tip exist in booking
            $hasTip = false;
            $tip_amount = 0;
            if ($appConfig->tip_status == 1) {
                $tip_amount = (!empty($bookingDetails->tip_amount) && ($bookingDetails->tip_amount != null)) ? $bookingDetails->tip_amount : '0.0';
                foreach ($bill_details_data as $key => $value) {
                    if (array_key_exists('parameter', $value) && $value['parameter'] == "Tip") {
                        $hasTip = true;
                        $bill_details_data[$key]['amount'] = $tip_amount;
                        break;
                    }
                }

                if ($hasTip) {}else{
                    $tip_parameter = array('price_card_id' => $bookingObj->price_card_id, 'booking_id' => $bookingObj->id, 'parameter' => "Tip", 'parameterType' => "tip_amount", 'amount' => $tip_amount, 'type' => "CREDIT", 'code' => "");
                    array_push($bill_details_data, $tip_parameter);
                }
            }

            $booking->bill_details = json_encode($bill_details_data, true);
            $booking->save();
            $bookingDetails->bill_details = json_encode($bill_details_data, true);
+            $bookingDetails->save();
            return array('tip_amount' => $tip_amount, 'toll_amount' => $toll_charge);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    //only for chap project
    public function getBookingsDetail(Request $request)
    {
        $string_file = $this->getStringFile($request->merchant_id);
        $booking_status = $this->getBookingStatus($string_file);
        $request->request->add(['start' => $request->start_date, 'end' => $request->end_date]);
        $all_bookings = $this->getBookings($request, $pagination = false, 'MERCHANT');
        $bookings_array = $all_bookings->map(function ($item) use ($booking_status) {
            $driver_license_no = '';
            $vehicle_registration_no = '';
            if (!empty($item->DriverVehicle)) {
                $driver_license_document = $item->DriverVehicle->DriverVehicleDocument->where('document_id', 742);
                $driver_license_no = !empty($driver_license_document) && !empty($driver_license_document[0]->document_number) ? $driver_license_document[0]->document_number : '';

                $vehicle_registration_document = $item->DriverVehicle->DriverVehicleDocument->where('document_id', 829);
                $vehicle_registration_no = !empty($driver_license_document) && !empty($vehicle_registration_document[0]->document_number) ? $vehicle_registration_document[0]->document_number : '';
            }
            $origin_coordinates = !empty($item->BookingDetail) && !empty($item->BookingDetail->start_latitude) ? (substr($item->BookingDetail->start_latitude, 0, 11) . ',' . substr($item->BookingDetail->start_longitude, 0, 10)) : (substr($item->pickup_latitude, 0, 11) . ',' . substr($item->pickup_longitude, 0, 10));
            $end_coordinates = !empty($item->BookingDetail) && !empty($item->BookingDetail->start_latitude) ? (substr($item->BookingDetail->end_latitude, 0, 11) . ',' . substr($item->BookingDetail->end_longitude, 0, 10)) : (substr($item->drop_latitude, 0, 11) . ',' . substr($item->drop_longitude, 0, 10));

            return [
                'trip_id' => (string)$item->id,
                'origin_coordinates' => $origin_coordinates,
                'end_coordinates' => $end_coordinates,
                'start_time' => !empty($item->BookingDetail) && !empty($item->BookingDetail->start_timestamp) ? date('H:i:s', $item->BookingDetail->start_timestamp) : '',
                'end_time' => !empty($item->BookingDetail) && !empty($item->BookingDetail->end_timestamp) ? date('H:i:s', $item->BookingDetail->end_timestamp) : '',
                'total_fare_amount' => $item->payment_status == 1 ? $item->final_amount_paid : $item->estimate_bill,
                'trip_distance' => $item->payment_status == 1 ? explode(" ", $item->travel_distance)[0] * 1000 : explode(" ", $item->estimate_distance)[0] * 1000,
                'rating' => !empty($item->Driver) ? $item->Driver->rating : 0,
                'driver_earning' => !empty($item->driver_cut) ? $item->driver_cut : 0,
                'driver_license_no' => $driver_license_no,
                'vehicle_registration_no' => $vehicle_registration_no,
                'trip_status' => $booking_status[$item->booking_status],
            ];
        });

        return response()->json($bookings_array);
    }

    public function sendReminderToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required', 'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                    $query->where('booking_status', 1002);
                })
            ],
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        $booking = Booking::find($request->booking_id);
        $estimate_driver_time = explode(' ', $booking->estimate_driver_time)[0];
        $bookingDetail = $booking->BookingDetail;
        $now_strtotime = strtotime('now');
        $accept_strtotime = $bookingDetail->accept_timestamp;
        $date1 = date_create(date('Y-m-d H:i:s', $accept_strtotime));
        $date2 = date_create(date('Y-m-d H:i:s', $now_strtotime));
        $diff = date_diff($date1, $date2);
        $diff_minute = $diff->i;

        $data['notification_type'] = 'DRIVER_ARRIVING';
        $data['segment_type'] = $booking->Segment->slag;
        $data['segment_sub_group'] = $booking->Segment->sub_group_for_app; // its segment sub group for app
        $data['segment_group_id'] = $booking->Segment->segment_group_id; // for handyman
        $data['segment_data'] = [
            'booking_id' => $booking->id,
            'segment_slug' => $booking->Segment->slag
        ];

        $string_file = $this->getStringFile($booking->merchant_id);
        setLocal($booking->User->language);
        $arr_param['data'] = $data;
        $arr_param['user_id'] = $booking->user_id;
        $arr_param['merchant_id'] = $booking->merchant_id;
        $arr_param['large_icon'] = "";
        $title = trans("$string_file.driver") . ' ' . trans("$string_file.arriving");
        $arr_param['title'] = $title; // notification title

        $msg = "Notification Not Sent!";
        if (($estimate_driver_time - $diff_minute) == 5) {
            $message = trans("$string_file.driver") . " Will Reach in 5 Minutes";
            $arr_param['message'] = $message;
            Onesignal::UserPushMessage($arr_param);
            $msg = "Notification Sent!";
        } elseif (($estimate_driver_time - $diff_minute) == 2) {
            $message = trans("$string_file.driver") . " Will Reach in 2 Minutes";
            $arr_param['message'] = $message;
            Onesignal::UserPushMessage($arr_param);
            $msg = "Notification Sent!";
        } elseif (($estimate_driver_time - $diff_minute) == 1) {
            $message = trans("$string_file.driver") . " Arriving Soon";
            $arr_param['message'] = $message;
            Onesignal::UserPushMessage($arr_param);
            $msg = "Notification Sent!";
        }
        setLocal();
        return $this->successResponse($msg);
    }

    public function saveUserRewardPoints($meterDistance, $user)
    {
        $points = round($meterDistance / 100, 2);
        $user->reward_points += $points;
        $user->save();
    }

    public function checkoutAdditionalInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkout_id' => ['required', 'exists:booking_checkouts,id'],
            'wheel_chair_enable' => 'required',
            'baby_seat_enable' => 'required',
            'gender_match' => 'required',
            'gender' => 'required_if:gender_match,1',
            'no_seat_check' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $string_file = $this->getStringFile(NULL, $user->Merchant);

            $configuration = BookingConfiguration::where([['merchant_id', '=', $user->merchant_id]])->first();

            $booking_data = BookingCheckout::find($request->checkout_id);
            $booking_data->additional_information = $request->additional_information;
            $booking_data->additional_notes = $request->additional_notes;
            $booking_data->bags_weight_kg = isset($request->bags_weight_kg) ? $request->bags_weight_kg : NULL;
            $booking_data->no_of_bags = isset($request->no_of_bags) ? $request->no_of_bags : NULL;
            $booking_data->no_of_pats = isset($request->no_of_pats) ? $request->no_of_pats : NULL;
            $booking_data->no_of_person = isset($request->no_of_person) ? $request->no_of_person : NULL;
            $booking_data->no_of_children = isset($request->no_of_children) ? $request->no_of_children : NULL;
            $booking_data->gender = ($request->gender_match == 1) ? $request->gender : NULL;
            $booking_data->wheel_chair_enable = ($request->wheel_chair_enable == 1) ? $request->wheel_chair_enable : NULL;
            $booking_data->baby_seat_enable = ($request->baby_seat_enable == 1) ? $request->baby_seat_enable : NULL;
            $booking_data->save();

            $remain_ride_radius_slot = array();
            $limit = $configuration->normal_ride_now_request_driver;
            if (!empty($configuration->driver_ride_radius_request)) {
                $ride_radius = json_decode($configuration->driver_ride_radius_request, true);
                $remain_ride_radius_slot = $ride_radius;
            }

            $drivers = Driver::GetNearestDriver([
                'area' => $booking_data->country_area_id,
                'segment_id' => $booking_data->segment_id,
                'latitude' => $booking_data->pickup_latitude,
                'longitude' => $booking_data->pickup_longitude,
                'distance' => !empty($remain_ride_radius_slot) ? $remain_ride_radius_slot[0] : null,
                'limit' => $limit,
                'service_type' => $booking_data->service_type_id,
                'vehicle_type' => $booking_data->vehicle_type_id,
                'gender_match' => $request->gender_match,
                'user_gender' => $request->gender,
                'ac_nonac' => $request->ac_nonac,
                'wheel_chair' => $request->wheel_chair_enable,
                'baby_seat' => $request->baby_seat_enable,
            ]);
            DB::commit();
            if (!empty($drivers) && $drivers->count() > 0) {
                return $this->successResponse(trans("$string_file.success"), []);
            } else {
                return $this->failedResponse(trans("$string_file.no_driver_available_with_filter"), []);
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->failedResponse($exception->getMessage());
        }
    }

    // Driver Save Booking Additional Info
    public function saveBookingAdditionalInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => ['required', 'exists:bookings,id'],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $driver = $request->user('api-driver');
            $string_file = $this->getStringFile(NULL, $driver->Merchant);

            $booking_data = Booking::find($request->booking_id);
            if (isset($request->bags_weight_kg) && !empty($request->bags_weight_kg)) {
                $booking_data->bags_weight_kg = $request->bags_weight_kg;
            }
            if (isset($request->no_of_bags) && !empty($request->no_of_bags)) {
                $booking_data->no_of_bags = $request->no_of_bags;
            }
            if (isset($request->no_of_pats) && !empty($request->no_of_pats)) {
                $booking_data->no_of_pats = $request->no_of_pats;
            }
            if (isset($request->no_of_person) && !empty($request->no_of_person)) {
                $booking_data->no_of_person = $request->no_of_person;
            }
            if (isset($request->no_of_children) && !empty($request->no_of_children)) {
                $booking_data->no_of_children = $request->no_of_children;
            }
            if (isset($request->gender) && !empty($request->gender)) {
                $booking_data->gender = $request->gender;
            }
            if (isset($request->wheel_chair_enable) && !empty($request->wheel_chair_enable)) {
                $booking_data->wheel_chair_enable = $request->wheel_chair_enable;
            }
            if (isset($request->baby_seat_enable) && !empty($request->baby_seat_enable)) {
                $booking_data->baby_seat_enable = $request->baby_seat_enable;
            }
            $booking_data->save();
            DB::commit();

            $notification_data['notification_type'] = "BOOKING_UPDATE";
            $notification_data['segment_type'] = $booking_data->Segment->slag;
            $notification_data['segment_group_id'] = $booking_data->Segment->segment_group_id;
            $notification_data['segment_sub_group'] = $booking_data->Segment->sub_group_for_app; // its segment sub group for app
            $notification_data['segment_data'] = ['booking_id' => $booking_data->id,];
            $title = trans("$string_file.booking") . " " . trans("$string_file.additional_info") . " " . trans("$string_file.updated");
            $message = trans("$string_file.booking") . " " . trans("$string_file.additional_info") . " " . trans("$string_file.updated");
            $arr_param = ['user_id' => $booking_data->user_id, 'data' => $notification_data, 'message' => $message, 'merchant_id' => $booking_data->merchant_id, 'title' => $title, 'large_icon' => ""];
            Onesignal::UserPushMessage($arr_param);

            return $this->successResponse(trans("$string_file.success"), []);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function passBooking(Request $request)
    {
        $driver = $request->user('api-driver');
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) use ($driver) {
                    $query->where(array("booking_status" => "1004", "driver_id" => $driver->id));
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $string_file = $this->getStringFile($request->merchant_id);
            $booking = Booking::find($request->booking_id);
            $configuration = BookingConfiguration::where([['merchant_id', '=', $booking->merchant_id]])->first();

            if (!empty($configuration->driver_ride_radius_request)) {
                $remain_ride_radius_slot = json_decode($configuration->driver_ride_radius_request, true);
            } else {
                $remain_ride_radius_slot = array();
            }

            // Search For Driver
            $req_parameter = [
                'merchant_id' => $request->merchant_id,
                'area' => $booking->country_area_id,
                'segment_id' => $booking->segment_id,
                'latitude' => $driver->current_latitude,
                'longitude' => $driver->current_longitude,
                'distance' => !empty($remain_ride_radius_slot) ? $remain_ride_radius_slot[0] : $configuration->normal_ride_now_radius,
                'limit' => $configuration->normal_ride_now_request_driver,
                'service_type' => $booking->service_type_id,
                'vehicle_type' => $booking->vehicle_type_id,
                'drop_lat' => $booking->drop_latitude,
                'drop_long' => $booking->drop_longitude,
                'user_gender' => $booking->User->user_gender
            ];

            // get nearest drivers
            $drivers = Driver::GetNearestDriver($req_parameter);

            if (empty($drivers)) {
                throw new \Exception(trans("$string_file.no_driver_available"));
            }

            // Create Temparary Booking Creation
            $drop_locationArray = array(array(
                "stop" => "0",
                "drop_latitude" => $booking->drop_latitude,
                "drop_longitude" => $booking->drop_longitude,
                "drop_location" => $booking->drop_location,
                "status" => "1"
            ));
            $distance_unit = $booking->CountryArea->Country['distance_unit'];
            $units = $distance_unit == 1 ? 'metric' : 'imperial';
            $selected_map = getSelectedMap($booking->Merchant, "BOOKING_PASS");
            if($selected_map == "GOOGLE"){
                $googleArray = GoogleController::GoogleStaticImageAndDistance($driver->current_latitude, $driver->current_longitude, $drop_locationArray, $configuration->google_key, $units, $string_file);
            }
            else{
                $key = get_merchant_google_key($booking->merchant_id,'api', "MAP_BOX");
                $googleArray = MapBoxController::MapBoxStaticImageAndDistance($driver->current_latitude, $driver->current_longitude, $drop_locationArray, $key, $units, $string_file);
            }
            saveApiLog($booking->merchant_id, "directions" , "BOOKING_PASS", $selected_map);
            $time = $googleArray['total_time_text'];
            $timeSmall = $googleArray['total_time_minutes'];
            $distance = $googleArray['total_distance_text'];
            $distanceSmall = $googleArray['total_distance'];

            $from = $driver->current_latitude . "," . $driver->current_longitude;
            $to = $booking->drop_latitude . "," . $booking->drop_longitude;
            $estimatePrice = new PriceController();
            $fare = $estimatePrice->BillAmount([
                'price_card_id' => $booking->price_card_id,
                'merchant_id' => $booking->merchant_id,
                'distance' => $distanceSmall,
                'time' => $timeSmall,
                'booking_id' => 0,
                'user_id' => $booking->user_id,
                'units' => $distance_unit,
                'from' => $from,
                'to' => $to,
            ]);
            $merchant = new \App\Http\Controllers\Helper\Merchant();
            $amount = $merchant->FinalAmountCal($fare['amount'], $booking->merchant_id);
            $bill_details = json_encode($fare['bill_details']);
            $rideData = array(
                'distance' => $distance,
                'time' => $time,
                'bill_details' => $bill_details,
                'amount' => $amount,
                'estimate_driver_distance' => $distance,
                'estimate_driver_time' => $time,
                'estimate_distance' => $distance,
                'estimate_time' => $time,
                'auto_upgradetion' => 2
            );
            $booking_checkout = new BookingCheckout();
            $booking_checkout->user_id = $booking->user_id;
            $booking_checkout->merchant_id = $booking->merchant_id;
            $booking_checkout->segment_id = $booking->segment_id;
            $booking_checkout->corporate_id = $booking->corporate_id;
            $booking_checkout->country_area_id = $booking->country_area_id;
            $booking_checkout->service_type_id = $booking->service_type_id;
            $booking_checkout->vehicle_type_id = $booking->vehicle_type_id;
            $booking_checkout->service_package_id = $booking->service_package_id;
            $booking_checkout->price_card_id = $booking->price_card_id;
            $booking_checkout->is_geofence = $booking->is_geofence;
            $booking_checkout->base_area_id = $booking->base_area_id;
            $booking_checkout->pickup_latitude = $driver->current_latitude;
            $booking_checkout->pickup_longitude = $driver->current_longitude;
            $booking_checkout->pickup_location = $booking->pickup_location;
            $booking_checkout->booking_type = 1;
            $booking_checkout->total_drop_location = 0;
            $booking_checkout->map_image = $googleArray['image'];
            $booking_checkout->drop_latitude = $booking->drop_latitude;
            $booking_checkout->drop_longitude = $booking->drop_longitude;
            $booking_checkout->drop_location = $booking->drop_location;
            $booking_checkout->waypoints = "[]";
            $booking_checkout->estimate_distance = $rideData['distance'];
            $booking_checkout->estimate_time = $rideData['time'];
            $booking_checkout->estimate_bill = $rideData['amount'];
            $booking_checkout->auto_upgradetion = $rideData['auto_upgradetion'];
            $booking_checkout->later_booking_date = null;
            $booking_checkout->later_booking_time = null;
            $booking_checkout->return_date = null;
            $booking_checkout->return_time = null;
            $booking_checkout->number_of_rider = $booking->number_of_rider;
            $booking_checkout->bill_details = $rideData['bill_details'];
            $booking_checkout->promo_code = null;
            $booking_checkout->estimate_driver_distance = $rideData['estimate_driver_distance'];
            $booking_checkout->estimate_driver_time = $rideData['estimate_driver_time'];
            $booking_checkout->ac_nonac = NULL;
            $booking_checkout->wheel_chair_enable = NULL;
            $booking_checkout->baby_seat_enable = NULL;
            $booking_checkout->payment_method_id = $booking->payment_method_id;
            $booking_checkout->save();
        // p($booking_checkout);

            $booking_data = $booking_checkout->toArray();
            unset($booking_data['id']);
            unset($booking_data['user']);
            unset($booking_data['created_at']);
            unset($booking_data['updated_at']);
            $booking_data['booking_timestamp'] = time();
            $booking_data['booking_status'] = 1001;
            $booking_data['pass_booking_id'] = $booking->id;
            $booking_data['is_pass_ride'] = 1;
            $new_booking = Booking::create($booking_data);

            $req_parameter = [
                'area' => isset($new_booking->country_area_id) ? $new_booking->country_area_id : null,
                'segment_id' => isset($new_booking->segment_id) ? $new_booking->segment_id : null,
                'latitude' => isset($new_booking->pickup_latitude) ? $new_booking->pickup_latitude : null,
                'longitude' => isset($new_booking->pickup_longitude) ? $new_booking->pickup_longitude : null,
                'distance' => !empty($remain_ride_radius_slot) ? $remain_ride_radius_slot[0] : null,
                'limit' => isset($new_booking_config->normal_ride_now_request_driver) ? $configuration->normal_ride_now_request_driver : null,
                'service_type' => isset($new_booking->service_type_id) ? $new_booking->service_type_id : null,
                'vehicle_type' => isset($new_booking->vehicle_type_id) ? $new_booking->vehicle_type_id : null,
                'baby_seat' => isset($new_booking->baby_seat_enable) ? $new_booking->baby_seat_enable : null,
                'user_gender' => isset($new_booking->gender) ? $new_booking->gender : null,
                'drop_lat' => isset($new_booking->drop_latitude) ? $new_booking->drop_latitude : null,
                'drop_long' => isset($new_booking->drop_longitude) ? $new_booking->drop_longitude : null,
                'booking_id' => isset($new_bookingId) ? $new_bookingId : null,
                'wheel_chair' => isset($new_booking->wheel_chair_enable) ? $new_booking->wheel_chair_enable : null,
                'payment_method_id' => isset($new_booking->payment_method_id) ? $new_booking->payment_method_id : null,
                'estimate_bill' => isset($new_booking->estimate_bill) ? $new_booking->estimate_bill : null,
                'ac_nonac' => isset($new_booking->ac_nonac) ? $new_booking->ac_nonac : null,
                'string_file' => $string_file,
            ];
            $drivers = Driver::GetNearestDriver($req_parameter);
            if (empty($drivers) || (!empty($drivers) && $drivers->count() == 0)) {
                throw new \Exception(trans("$string_file.no_driver_available"));
            } else {
                $findDriver = new FindDriverController();
                $findDriver->AssignRequest($drivers, $new_booking->id);
                $bookingData = new BookingDataController();
                $bookingData->SendNotificationToDrivers($new_booking, $drivers);
            }

            DB::commit();
            $data = array("pass_booking_id" => $new_booking->id);
            return $this->successResponse(trans("$string_file.success"), $data);
        } catch (\Exception $exception) {
            DB::rollBack();
            // p($exception->getTraceAsString());
            return $this->failedResponse($exception->getMessage());
        }
    }

    public function passBookingCancel(Request $request)
    {
        $driver = $request->user('api-driver');
        $validator = Validator::make($request->all(), [
            'pass_booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $string_file = $this->getStringFile($request->merchant_id);
            $booking = Booking::find($request->pass_booking_id);
            $status = "";
            $message = trans("$string_file.success");
            if ($booking->booking_status == 1001) {
                $booking->booking_status = 1008;
                $booking->save();

                $this->saveBookingStatusHistory($request, $booking, $booking->id);
                BookingRequestDriver::where("booking_id", $request->booking_id)->update(["request_status" => 4]);
                $bookingData = new BookingDataController;
                $bookingData->bookingNotificationForUser($booking, "CANCEL_RIDE");
                $driver_id = $booking->driver_id;
                if (!empty($driver_id)) {
                    $Driver = Driver::select('id', 'free_busy')->where([['id', '=', $driver_id]])->first();
                    $Driver->free_busy = 2;
                    $Driver->save();
                    $bookingData->SendNotificationToDrivers($booking);
                }

                $status = "CANCELLED";
            } elseif (in_array($booking->booking_status, array(1006, 1007, 1008, 1016, 1018))) {
                $status = "CANCELLED";
            } else {
                $status = "NOT_CANCELLED";
                if ($booking->booking_status == 1002) {
                    $message = trans("$string_file.ride_accepted");
                } elseif ($booking->booking_status == 1003) {
                    $message = trans("$string_file.arrived_for_picup");
                } elseif ($booking->booking_status == 1004) {
                    $message = trans("$string_file.ride_started");
                } elseif ($booking->booking_status == 1005) {
                    $message = trans("$string_file.ride_completed");
                }
            }
            DB::commit();
            $data = array("status" => $status, "message" => $message);
            return $this->successResponse(trans("$string_file.success"), $data);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->failedResponse($exception->getMessage());
        }
    }
//@ayush - in case of cash payment we need to ask for confirmation else create outstanding
    public function confirmation(Request $request){
        $driver = $request->user('api-driver');
        $validator = Validator::make($request->all(), [
            'booking_id' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')->where(function ($query) {
                }),
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try{
            $booking= Booking::find($request->booking_id);
            $string_file = $this->getStringFile(NULL, $booking->Merchant);
            if(!empty($booking)) $msg = "$string_file.booking_not_found";

            if (isset($booking->id) && !empty($booking->id)) {
                $amount = $booking->final_amount_paid;
                $booking->payment_method_id = 1; // its case in which driver receive amount only in cash
                $booking->payment_status = 1; // means payment done successfully
                $booking->save();

                if (isset($booking->BookingDetail->id)) {
                    $booking->BookingDetail->pending_amount = $amount;
                    $booking->BookingDetail->payment_failure = 1; //payment is not recived
                    $booking->BookingDetail->save();
                }
                $finalAmountPaid = (float)$booking->final_amount_paid;
                if(isset($booking->Merchant->Configuration->outstanding_extra_charge) && $booking->Merchant->Configuration->outstanding_extra_charge == 1){
                    $outExtraCharge = (float)$booking->Merchant->Configuration->outstanding_extra_charge;
                    $outstanding = Outstanding::create([
                        'user_id' => $booking->user_id,
                        'driver_id' => $booking->driver_id,
                        'booking_id' => $booking->id,
                        'amount' => $finalAmountPaid + $outExtraCharge ,
                        'extra_charge_amount' => $outExtraCharge,
                        'reason' => 2,
                    ]);
                }else{
                    $outstanding = Outstanding::create([
                        'user_id' => $booking->user_id,
                        'driver_id' => $booking->driver_id,
                        'booking_id' => $booking->id,
                        'amount' => $finalAmountPaid,
                        'reason' => 2,
                    ]);
                }
                $status = 1;
                $msg = trans("$string_file.success");
                $bookingData = new BookingDataController();
                $notification_data = $bookingData->bookingNotificationForUser($booking, "END_RIDE");
            }
            DB::commit();
            if ($status == 0) {
                return $this->failedResponse($msg);
            } else {
                $booking_data = new BookingDataController;
                $request->merge(['booking_id' => $request->booking_id]);
                $return_data = $booking_data->bookingReceiptForDriver($request);
                return $this->successResponse($msg, $return_data);
            }
        }
        catch(\Exception $e){
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
    }
}
