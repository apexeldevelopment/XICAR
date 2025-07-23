<?php
//
//namespace App\Http\Controllers\Api\BusBooking;
//
//use App\Events\SendUserInvoiceMailEvent;
//
////use App\Http\Controllers\Helper\Merchant;
//use App\Http\Controllers\Helper\DistanceCalculation;
//use App\Http\Controllers\Helper\PolygenController;
//use App\Http\Controllers\Helper\DistanceController;
//use App\Http\Controllers\Helper\ReferralController;
//use App\Http\Controllers\Helper\RewardPoint;
//use App\Http\Controllers\Helper\ExtraCharges;
//use App\Http\Controllers\Helper\Toll;
//use App\Http\Controllers\Helper\TwilioMaskingHelper;
//use App\Http\Controllers\Merchant\WhatsappController;
//use App\Http\Resources\DeliveryCheckoutResource;
//use App\Models\BookingTransaction;
//use App\Models\DriverSubscriptionRecord;
//use App\Models\PaymentOption;
//use App\Http\Controllers\PaymentMethods\RandomPaymentController;
//use App\Models\PaymentOptionsConfiguration;
//use App\Models\UserCard;
//use App\Traits\BookingTrait;
//use DateTime;
//use Exception;
//use Illuminate\Support\Arr;
//use Illuminate\Support\Facades\DB;
//use App\Http\Controllers\PaymentMethods\CancelPayment;
//use App\Http\Controllers\PaymentMethods\Payment;
//use App\Http\Controllers\Services\NormalController;
//use App\Http\Controllers\Services\OutstationController;
//use App\Http\Controllers\Services\PoolController;
//use App\Http\Controllers\Services\RentalController;
//use App\Http\Controllers\Services\TransferController;
//use App\Models\ApplicationConfiguration;
//use App\Models\Booking;
//use App\Models\BookingCheckout;
//use App\Models\BookingCheckoutPackage;
//use App\Models\BookingConfiguration;
//use App\Models\BookingCoordinate;
//use App\Models\BookingDetail;
//use App\Models\BookingRating;
//use App\Models\BookingRequestDriver;
//use App\Models\Configuration;
//use App\Models\CountryArea;
//use App\Models\Driver;
//use App\Models\DriverCancelBooking;
//use App\Models\DriverVehicle;
//use App\Models\FavouriteDriver;
//use App\Models\Merchant;
//use App\Models\Onesignal;
//use App\Models\PoolRideList;
//use App\Models\PriceCard;
//use App\Models\PromoCode;
//use App\Models\QuestionUser;
//use App\Models\Sos;
//use App\Models\User;
//use App\Models\UserDevice;
//use Illuminate\Http\Request;
//use App\Http\Controllers\Controller;
//use Illuminate\Support\Facades\Validator;
//use Illuminate\Validation\Rule;
//use App\Http\Controllers\Helper\BookingDataController;
//use App\Http\Controllers\Helper\HolderController;
//use App\Http\Controllers\Helper\PriceController;
//use App\Http\Controllers\Helper\GoogleController;
//use App\Http\Controllers\Helper\FindDriverController;
//use App\Http\Controllers\Helper\SmsController;
//use App\Models\SmsConfiguration;
//use App\Http\Controllers\Api\CashbackController;
//use App\Traits\ImageTrait;
//use App\Models\Outstanding;
//use App\Http\Controllers\Helper\DriverRecords;
//use App\Http\Controllers\Helper\WalletTransaction;
//use App\Models\BookingDeliveryDetails;
//use App\Traits\ApiResponseTrait;
//use App\Traits\MerchantTrait;
//use App\Models\ServiceType;
//use App\Http\Controllers\Helper\CommonController;
//
//use App\Traits\PolylineTrait;
//use App\Traits\DriverTrait;
//use App\Http\Controllers\PaymentMethods\PayPhone\PayPhoneController;
//use App\Models\BusPriceCard;
//
//class ShuttleController extends Controller
//{
//    use ImageTrait, BookingTrait, ApiResponseTrait, MerchantTrait, PolylineTrait, DriverTrait;
//
//
//    public function checkout($request)
//    {
//        // $validator = Validator::make($request->all(), [
//        //     'segment_id' => 'required|integer|exists:segments,id',
//        //     'area' => 'required|integer|exists:country_areas,id',
//        //     'pickup_stop_id' => 'required|integer|exists:bus_stops,id',
//        //     'drop_stop_id' => 'required|integer|exists:bus_stops,id',
//        //     'drop_stop_id' => 'required|integer|exists:bus_stops,id',
//        //     'bus_id' => 'required|integer|exists:buses,id',
//        //     'time_slot' => 'required|integer|exists:service_time_slot_details,id', // pickup time slot
//        //     'booking_date' => 'required',
//        //     'number_of_rider' => 'required|integer',
//        // ]);
//        // if ($validator->fails()) {
//        //     $errors = $validator->messages()->all();
//        //     return $this->failedResponse($errors[0]);
//        // }
//
//
//        // $user = $request->user('api');
//
//        DB::beginTransaction();
//        try {
//
//            // find price card of route bus
//            $price_card = BusPriceCard::whereHas('RouteConfig',function($q){
//
//
//
//            })->find();
//
//
//        } catch (Exception $e) {
//            DB::rollback();
//            return $this->failedResponse($e->getMessage());
//        }
//        DB::commit();
//
//
//        return $this->successResponse($booking['message'], $booking['data']);
//    }
//}
