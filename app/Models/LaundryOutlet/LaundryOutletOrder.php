<?php

namespace App\Models\LaundryOutlet;

use App\Models\BookingRating;
use App\Models\BookingTransaction;
use App\Models\CountryArea;
use App\Models\Driver;
use App\Models\DriverVehicle;
use App\Models\Merchant;
use App\Models\PaymentMethod;
use App\Models\PriceCard;
use App\Models\Segment;
use App\Models\ServiceTimeSlotDetail;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\UserAddress;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaundryOutletOrder extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->merchant_order_id = $model->NewBookigId($model->merchant_id);
            return $model;
        });
    }

    public function NewBookigId($merchantID)
    {
        $booking = LaundryOutletOrder::where([['merchant_id', '=', $merchantID]])->orderBy('id', 'DESC')->first();
        if (!empty($booking)) {
            return $booking->merchant_order_id + 1;
        } else {
            return 1;
        }
    }

    function LaundryOutletOrderDetail()
    {
        return $this->hasMany(LaundryOutletOrderDetail::class);
    }

    function LaundryService()
    {
        return $this->belongsTo(LaundryService::class);
    }

    function Driver()
    {
        return $this->belongsTo(Driver::class);
    }

//    function OldDriver()
//    {
//        return $this->belongsTo(Driver::class,'old_driver_id');
//    }

    function LaundryOutlet()
    {
        return $this->belongsTo(LaundryOutlet::class);
    }

    function PaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    function UserAddress()
    {
        return $this->belongsTo(UserAddress::class);
    }

    function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }

    function PriceCard()
    {
        return $this->belongsTo(PriceCard::class);
    }

    function Segment()
    {
        return $this->belongsTo(Segment::class);
    }

    function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    function ServiceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    function DriverVehicle()
    {
        return $this->belongsTo(DriverVehicle::class);
    }

    function OrderTransaction()
    {
        return $this->hasOne(BookingTransaction::class, 'laundry_outlet_order_id');
    }

    function ServiceTimeSlotDetail()
    {
        return $this->belongsTo(ServiceTimeSlotDetail::class);
    }

    function BookingRating()
    {
        return $this->hasOne(BookingRating::class, 'laundry_outlet_order_id');
    }

    function LaundryOrderTransaction()
    {
        return $this->hasOne(BookingTransaction::class, 'laundry_outlet_order_id');
    }

    function getOrders($request, $pagination = false)
    {
        $permission_area_ids = [];
        if (Auth::user() && isset(Auth::user()->role_areas) && Auth::user()->role_areas != "") {
            $permission_area_ids = explode(",", Auth::user()->role_areas);
        }
        $laundry_outlet_id = empty($request->laundry_outlet_id) && empty($request->id) ? get_laundry_outlet() : $request->get_laundry_outlet;
        $query = LaundryOutletOrder::select('id', 'country_area_id', 'laundry_outlet_id', 'merchant_id', 'driver_id', 'user_address_id', 'price_card_id', 'merchant_order_id', 'segment_id', 'payment_method_id', 'user_id', 'driver_id', 'order_status', 'quantity', 'cart_amount', 'final_amount_paid', 'delivery_amount', 'drop_location', 'created_at', 'updated_at', 'payment_status', 'additional_notes', 'order_status_history', 'order_timestamp', 'tax', 'user_confirmed_otp_for_pickup', 'otp_for_pickup', 'discount_amount', 'time_charges', 'is_order_completed', 'order_date', 'service_time_slot_detail_id', 'drop_latitude', 'drop_longitude', 'service_type_id', 'order_item_images', 'estimate_delivery_time','drop_date_time_slot','delay_date_time')
            ->with(['LaundryOutletOrderDetail' => function ($q) {
                $q->addSelect('id', 'laundry_outlet_order_id', 'laundry_service_id', 'quantity', 'price', 'total_amount');
            }])
            ->with(['User' => function ($q) {
                $q->addSelect('id', 'first_name', 'last_name', 'UserPhone', 'email', 'UserProfileImage');
            }])
            ->with(['Driver' => function ($q) {
                $q->addSelect('id', 'first_name', 'last_name', 'phoneNumber', 'email');
            }])
            ->with(['CountryArea' => function ($q) {
                $q->addSelect('id', 'merchant_id', 'country_id', 'timezone');
            }])
            ->with(['PriceCard' => function ($q) {
                $q->addSelect('id', 'base_fare');
            }]);


        if (!empty($request->merchant_id)) {
            $query->where('merchant_id', $request->merchant_id);
        }
        if (!empty($request->segment_id)) {
            $query->where('segment_id', $request->segment_id);
        }
        if ($request->order_id) {
            $query->where('merchant_order_id', $request->order_id);
        }
        if ($request->driver_id) {
            $query->where('driver_id', $request->driver_id);
        }
        if (!empty($laundry_outlet_id)) {
            $query->where([['laundry_outlet_id', '=', $laundry_outlet_id]]);
        }
        if ($request->is_order_completed) {
            $is_order_completed = $request->is_order_completed == "yes" ? 1 : 2;
            $query->where('is_order_completed', $is_order_completed);
        }
        if ($request->order_status) {
            $query->where('order_status', $request->order_status);
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('User', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")->orwhere('last_name', 'LIKE', "%$keyword%")
                    ->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
            });
        }
        if ($request->start) {
            $start_date = date('Y-m-d', strtotime($request->start));
            $end_date = date('Y-m-d ', strtotime($request->end));
            $query->whereBetween(DB::raw('DATE(created_at)'), [$start_date, $end_date]);
        }
        if ($request->driver) {
            $keyword = $request->driver;
            $query->WhereHas('Driver', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")->orwhere('last_name', 'LIKE', "%$keyword%")
                    ->orWhere('email', 'LIKE', "%$keyword%")->orWhere('phoneNumber', 'LIKE', "%$keyword%");
            });
        }
        // order status
        if ($request->status) {
            $status = $request->status;
            if ($status == "TODAY") {
                $arr_status = [1];
                $query->whereDate('order_date', date('Y-m-d'));
            }
            if ($status == "UPCOMING") {
                $arr_status = [1];
                $query->whereDate('order_date', '>', date('Y-m-d'));
            } elseif ($status == "ARRIVED") { //arrived at store 4691
                $arr_status = [7];
            } elseif ($status == "REJECTED") {
                $arr_status = [3];
            } elseif ($status == "CANCELLED") {
                $arr_status = [5, 8];
            } elseif ($status == "PICKUP_VERIFICATION") {
                $query->where(function ($q) {
                    $q->where([['otp_for_pickup', '!=', NULL], ['user_confirmed_otp_for_pickup', '=', 2], ['order_status', '=', 7]]);
                    $q->orWhere([['order_status', '=', 9], ['user_confirmed_otp_for_pickup', '=', 2]]);
                    $q->orWhere([['order_status', '=', 6], ['user_confirmed_otp_for_pickup', '=', 2]]);
                });
                $query->whereNotIn('order_status', [2, 3, 5, 8]);
                $arr_status = [];
            } elseif ($status == "ONTHEWAY") {
                $arr_status = [6, 9, 10, 7, 13, 15,16];
                $query->WhereHas('ServiceType', function ($q) {
                    $q->where('type', 1);
                });
            } elseif ($status == "DRIVER_ASSIGN_DELIVERY_PENDING") {
                $arr_status = [15];
                $query->whereHas('ServiceType', function ($q) { // only Delivery pickup orders
                    $q->where('type', 1);
                });
            } elseif ($status == "PENDING_ORDER_DELIVERY") {
                $arr_status = [13,20];
            } elseif ($status == "DELIVERED" || $status == "COMPLETED") {
                $arr_status = [14];
                $query->where('is_order_completed', 1);
            } elseif ($status == "ONGOING")// for app
            {
                $arr_status = [6, 7, 9, 10];
            } elseif ($status == "EXPIRED")// for app
            {
                $arr_status = [12];
            }

            if (!empty($arr_status)) {
                $query->whereIn('order_status', $arr_status);
            }
        }

        if (!empty($permission_area_ids)) {
            $query->whereIn("country_area_id", $permission_area_ids);
        }

        $query->orderBy('created_at', 'DESC');

        // send only signle order in paginate format
        if (!empty($request->id) && !empty($laundry_outlet_id) && $pagination == true) {
            $query->where('id', '=', $request->id);
        }

        if (!empty($request->id) && empty($laundry_outlet_id) && $pagination == false) {
            $query->where('id', '=', $request->id);
            $arr_orders = $query->first();
            $multiple_orders = false;
        } else if ($pagination == true) {
            $arr_orders = $query->paginate(25);
        } else {
            $arr_orders = $query->get();
        }
        return $arr_orders;

    }

    function User()
    {
        return $this->belongsTo(User::class);
    }

    public function getLaundryOrderInfo($request)
    {
        $order = LaundryOutletOrder::with(['User' => function ($q) {
            $q->addSelect('id', 'first_name', 'last_name', 'UserPhone', 'email', 'UserProfileImage', 'rating', 'language');
        }])
            ->with(['LaundryOutlet' => function ($q) {
                $q->addSelect('id', 'full_name', 'phone_number', 'address', 'latitude', 'longitude');
            }])
            ->with(['LaundryOutletOrderDetail' => function ($q) {
                $q->addSelect('id', 'laundry_outlet_order_id', 'laundry_service_id', 'price', 'quantity');
            }])
            ->with(['DriverVehicle' => function ($q) {
                $q->addSelect('id', 'vehicle_type_id', 'vehicle_model_id', 'vehicle_number');
            }])
            ->with(['CountryArea' => function ($q) {
                $q->addSelect('id', 'country_id', 'timezone');
                $q->with(['Country' => function ($qq) {
                    $qq->addSelect('id', 'isoCode');
                }]);
            }])
            ->with(['Segment' => function ($q) {
                $q->addSelect('id', 'slag', 'name', 'icon', 'segment_group_id', 'sub_group_for_app');
                $q->with(['Merchant' => function ($q) {
                }]);
            }])
            ->with(['Merchant' => function ($q) {
                $q->addSelect('id', 'string_file');
            }])
            ->find($request->id);
        return $order;

    }

    public function getDriverLaundryOrders($request)
    {
        $driver = $request->user('api-driver');
        $driver_id = $driver->id;
        $query = LaundryOutletOrder::
        with(['User' => function ($q) {
            $q->addSelect('id', 'first_name', 'last_name', 'UserPhone', 'email', 'UserProfileImage', 'rating');
        }])
            ->with(['LaundryOutlet', 'LaundryOutletOrderDetail'])
            ->with(['DriverVehicle' => function ($q) {
                $q->addSelect('id', 'vehicle_type_id', 'vehicle_make_id', 'vehicle_model_id', 'vehicle_number');
            }])
            ->with(['CountryArea' => function ($q) {
                $q->addSelect('id', 'country_id', 'timezone');
                $q->with(['Country' => function ($qq) {
                    $qq->addSelect('id', 'isoCode');
                }]);
            }])
            ->with(['Segment' => function ($q) {
                $q->addSelect('id', 'slag', 'name', 'icon', 'segment_group_id', 'sub_group_for_app');
            }])
            ->with(['Merchant' => function ($q) {
                $q->addSelect('id');
            }])
            ->with(['BookingRating' => function ($q) {
                $q->addSelect('user_rating_points');
            }])
            ->orderBy('updated_at', 'DESC');
        if ($request->segment_id) {
            $query->where('segment_id', $request->segment_id);
        }
        if ($request->order_type == "ONGOING") {
            $query->where([['is_order_completed', '!=', 1]])
                ->where([['driver_id', '=', $driver_id]])
                ->whereIn('order_status', [6,10,15,16,17]);
        } elseif ($request->order_type == "PAST") {
            $query->where(function ($q) use ($driver_id) {
                $q->where(function ($qq) {
                    $qq->where([['is_order_completed', '=', 1], ['order_status', '=', 14]]);
                    $qq->orWhereIn('order_status', [2, 8]);
                })
                    ->where(function ($qq) use ($driver_id) {
                        $qq->where('driver_id', $driver_id);
                        $qq->orWhere([['driver_id', '!=', $driver_id], ['old_driver_id', '=', $driver_id]]);
                    });
            });
        } elseif ($request->order_type == "COMPLETED") {
            $query->where('order_status', '=', 14)
                ->where([['driver_id', '=', $driver_id]]);
        }
        if ($request->pagination == true) {
            $orders = $query->paginate(10);
        } else {
            $orders = $query->get();
        }

        $orders->map(function ($order) {
            $date_obj = new DateTime($order->created_at);
            $date_obj->setTimezone(new DateTimeZone($order->CountryArea->timezone));
            $order['created_at'] = $date_obj->format('Y-m-d H:i:s');
            return $order;
        });
        return $orders;
    }


}
