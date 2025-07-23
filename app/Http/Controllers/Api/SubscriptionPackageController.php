<?php

namespace App\Http\Controllers\Api;

use DB;
use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Driver;
use App\Models\Booking;
use App\Models\Onesignal;
use App\Traits\DriverTrait;
use Illuminate\Http\Request;
use App\Traits\MerchantTrait;
use App\Models\PackageDuration;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponseTrait;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Models\RenewableSubscription;
use App\Models\UserSubscriptionRecord;
use App\Models\DriverSubscriptionRecord;
use Illuminate\Support\Facades\Validator;
use App\Models\DriverRenewableSubscriptionRecord;
use App\Http\Controllers\Helper\WalletTransaction;

class SubscriptionPackageController extends Controller
{
    use MerchantTrait, ApiResponseTrait, DriverTrait;


    public function getSubscriptionPackageList(Request $request)
    {
        $calling_from = $request->calling_from;
        if (empty($calling_from)) $calling_from = "DRIVER";
        $user = ($calling_from == "USER") ? $request->user('api') : $request->user('api-driver');
        $segment_id = $request->segment_id;

        $arr_driver_segment = [];
        if ($calling_from == "DRIVER") {
            if ($user->Segment) {
                $arr_driver_segment = $user->Segment->map(function ($item) {
                    return $item->id;
                });
                $arr_driver_segment = $arr_driver_segment->toArray();
            }
        }

        $string_file = $this->getStringFile(null, $user->Merchant);
        $packages_query = SubscriptionPackage::select('id', 'max_trip', 'image', 'price', 'package_type', 'merchant_id', 'package_duration_id', 'expire_date', 'segment_id', 'price_type', "amount")
            ->with('PackageDuration')
            ->where([['merchant_id', $user->merchant_id], ['status', true]])
            ->whereHas('CountryArea', function ($query) use ($user) {
                $query->where('country_area_id', '=', $user->country_area_id);
            });
        if ($calling_from == "DRIVER") {
            $packages_query->with(["DriverSubscriptionRecord" => function ($q) use ($user, $segment_id) {
                $q->where('driver_id', $user->id)->where('segment_id', $segment_id)->whereNotIn('status', [1])->orderBy('status', 'DESC');
            }]);
        } else {
            $packages_query->with(["UserSubscriptionRecord" => function ($q) use ($user, $segment_id) {
                $q->where('user_id', $user->id)->where('segment_id', $segment_id)->whereNotIn('status', [1])->orderBy('status', 'DESC');
            }]);
        }

        $packages_query->where(function ($q) {
            $q->where('expire_date', '>=', date('Y-m-d'));
            $q->orWhere('expire_date', NULL);
        });
        if ($calling_from == "DRIVER") {
            $packages_query->whereIn('segment_id', $arr_driver_segment);
        }
        $packages_query->where('segment_id', $segment_id);

        $all_payment_methods = [];
        $packages = $packages_query->get();

        $arr_packages = [];

        foreach ($packages as $key => $package) {
            $text = trans("$string_file.buy_activate");
            $active_pack = (object)[];
            $status = 0;
            // if (!empty($package->DriverSubscriptionRecord->id)) {
            //     $text = $package->DriverSubscriptionRecord->status == 2  ? trans("$string_file.activated") : trans("$string_file.activate");

            //     $active = $package->DriverSubscriptionRecord;
            //     $status = $active->status;
            //     $carry_status = $active->carry_forward_sub_pack_id ? true : false;
            //     $active_pack = [
            //         'id' => $active->id,
            //         'used_trip' => $active->used_trip ? $active->used_trip : 0,
            //         'start_time' => $active->start_date_time ? $active->start_date_time : "",
            //         'end_time' => $active->end_date_time ? $active->end_date_time : "",
            //         'status' => $active->status,
            //         'total_trip_summary' => [
            //             "status" =>$carry_status,
            //             "total_trips" => $active->package_total_trips,
            //             "package_trips" => $package->max_trip,
            //             "carry_forwarded_trips" => $active->status == 2 ? $active->package_total_trips - $package->max_trip  : 0,
            //         ],
            //     ];
            // }
            $arr_packages[] = [
                'id' => $package->id,
                'name' => $package->name,
                'expire_date' => $package->expire_date ? $package->expire_date : "",
                'package_type' => $package->package_type,
                'description' => $package->description,
                'show_price' => $user->CountryArea->Country->isoCode . ' ' . $package->price,
                'package_duration_name' => $package->PackageDuration->getNameAccMerchantAttribute($user->merchant_id, $package->package_duration_id),
                'image' => get_image($package->image, 'package', $package->merchant_id),
                'segment_name' => $package->Segment->Name($package->merchant_id),
                'max_trip' => $package->max_trip,
                'text' => $text,
                'status' => $status,
                'pack_details' => $active_pack,
                'amount' => $package->amount,
                'price_type' => $package->price_type,
            ];
        }

        $all_payment_methods = $user->Merchant->PaymentMethod->where('id', '=', 3); // only wallet
        $payment_methods = $all_payment_methods->map(function ($item, $key) {
            return $item->only(['id', 'payment_method', 'payment_icon']);
        })->values();

        $data = ['all_packages' => $arr_packages, 'payment_methods' => $payment_methods];
        return $this->successResponse(trans("$string_file.success"), $data);
    }

    // Driver Subscription history
    public function getSubscriptionHistory(Request $request)
    {
        $calling_from = $request->calling_from;
        if (empty($calling_from)) $calling_from = "DRIVER";
        $user = ($calling_from == "USER") ? $request->user('api') : $request->user('api-driver');
        $package_for = ($calling_from == "DRIVER") ? 2 : 1;

        $string_file = $this->getStringFile(null, $user->Merchant);
        if ($package_for == 2) {
            $packages = DriverSubscriptionRecord::with('PackageDuration')
                ->with("SubscriptionPackage.PackageDuration")
                ->where('driver_id', $user->id)
                ->where('segment_id', $request->segment_id)
                ->whereIn('status', [3, 4])
                ->orderBy('status', 'DESC')
                ->get();
        } else {
            $packages = UserSubscriptionRecord::with('PackageDuration')
                ->with("SubscriptionPackage.PackageDuration")
                ->where('user_id', $user->id)
                ->where('segment_id', $request->segment_id)
                ->whereIn('status', [3, 4])
                ->orderBy('status', 'DESC')
                ->get();
        }

        $arr_packages = [];
        foreach ($packages as $active) {
            $package = $active->SubscriptionPackage;
            $text = $active->status == 3 ? trans("$string_file.expired") : trans("$string_file.carry_forwarded_to_next_package");
            $status = $active->status;
            $carry_status = false;
            $pack_name = "";
            if ($active->carry_forward_sub_pack_id) {
                $carry_status = true;
                $carry_pack = SubscriptionPackage::select('id')->find($active->carry_forward_sub_pack_id);
                $pack_name = $carry_pack->name;
            }
            $active_pack = [
                'id' => $active->id,
                'used_trip' => $active->used_trip ? $active->used_trip : 0,
                'start_time' => $active->start_date_time ? $active->start_date_time : "",
                'end_time' => $active->end_date_time ? $active->end_date_time : "",
                'status' => $active->status,
                'total_trip_summary' => [
                    "carry_forwarded" => $pack_name,
                    "status" => $carry_status,
                    "total_trips" => $active->package_total_trips,
                    "package_trips" => $package->max_trip,
                    "carry_forwarded_trips" => $active->status == 3 ? $active->package_total_trips - $package->max_trip : $active->package_total_trips - $package->used_trips,
                ],
            ];


            $arr_packages[] = [
                'id' => $package->id,
                'name' => $package->name,
                'expire_date' => $package->expire_date ? $package->expire_date : "",
                'package_type' => $package->package_type,
                'description' => $package->description,
                'show_price' => $user->CountryArea->Country->isoCode . ' ' . $package->price,
                'package_duration_name' => $package->PackageDuration->getNameAccMerchantAttribute($user->merchant_id, $package->package_duration_id),
                'image' => get_image($package->image, 'package', $package->merchant_id),
                'segment_name' => $package->Segment->Name($package->merchant_id),
                'max_trip' => $package->max_trip,
                'text' => $text,
                'status' => $status,
                'pack_details' => $active_pack,
                'amount' => $package->amount,
                'price_type' => $package->price_type,
            ];
        }

        $data = ['packages_history' => $arr_packages];
        return $this->successResponse(trans("$string_file.success"), $data);
    }

    // Driver Subscription history
    public function getActiveSubscription(Request $request)
    {
        $calling_from = $request->calling_from;
        if (empty($calling_from)) $calling_from = "DRIVER";
        $user = ($calling_from == "USER") ? $request->user('api') : $request->user('api-driver');
        $package_for = ($calling_from == "DRIVER") ? 2 : 1;

        $string_file = $this->getStringFile(null, $user->Merchant);
        if ($package_for == 2) {
            $packages = DriverSubscriptionRecord::with('PackageDuration')
                ->with("SubscriptionPackage.PackageDuration")
                ->where('driver_id', $user->id)
                ->whereIn('status', [1, 2])
                ->orderBy('status', 'DESC')
                ->where('segment_id', $request->segment_id)
                ->get();
        } else {
            $packages = UserSubscriptionRecord::with('PackageDuration')
                ->with("SubscriptionPackage.PackageDuration")
                ->where('user_id', $user->id)
                ->whereIn('status', [1, 2])
                ->orderBy('status', 'DESC')
                ->where('segment_id', $request->segment_id)
                ->get();
        }

        $arr_packages = [];
        foreach ($packages as $active) {
            $package = $active->SubscriptionPackage;
            $status = ($package_for == 2) ? $package->DriverSubscriptionRecord->status : $package->UserSubscriptionRecord->status;
            $text = $status == 2 ? trans("$string_file.activated") : trans("$string_file.activate");
            $status = $active->status;
            $carry_status = false;
            $pack_name = "";
            if ($active->carry_forward_sub_pack_id) {
                $carry_status = true;
                $carry_pack = SubscriptionPackage::select('id')->find($active->carry_forward_sub_pack_id);
                $pack_name = $carry_pack->name;
            }
            $active_pack = [
                'id' => $active->id,
                'used_trip' => $active->used_trips ? $active->used_trips : 0,
                'start_time' => $active->start_date_time ? date('d M Y', strtotime($active->start_date_time)) : "",
                'end_time' => $active->end_date_time ? date('d M Y', strtotime($active->end_date_time)) : "",
                'total_days' => $status == 2 ? date_diff(date_create($active->start_date_time), date_create($active->end_date_time))->format('%a') : "",
                'days_left' => $status == 2 ? date_diff(date_create(date("Y-m-d H:i:s")), date_create($active->end_date_time))->format('%a') : "",
                'status' => $active->status,
                'total_trip_summary' => [
                    "status" => $carry_status,
                    "carry_forwarded" => $pack_name,
                    "total_trips" => $active->package_total_trips,
                    "package_trips" => $package->max_trip,
                    "carry_forwarded_trips" => $active->status == 2 ? $active->package_total_trips - $package->max_trip : 0,
                ],
            ];


            $arr_packages[] = [
                'id' => $package->id,
                'name' => $package->name,
                'expire_date' => $package->expire_date ? $package->expire_date : "",
                'package_type' => $package->package_type,
                'description' => $package->description,
                'show_price' => $user->CountryArea->Country->isoCode . ' ' . $package->price,
                'package_duration_name' => $package->PackageDuration->getNameAccMerchantAttribute($user->merchant_id, $package->package_duration_id),
                'image' => get_image($package->image, 'package', $package->merchant_id),
                'segment_name' => $package->Segment->Name($package->merchant_id),
                'max_trip' => $package->max_trip,
                'text' => $text,
                'status' => $status,
                'pack_details' => $active_pack,
                'amount' => $package->amount,
                'price_type' => $package->price_type,
            ];
        }

        $data = ['active_packages' => $arr_packages];
        return $this->successResponse(trans("$string_file.success"), $data);
    }

    public function ViewPackages(Request $request)
    {

        $calling_from = $request->calling_from;
        if (empty($calling_from)) $calling_from = "DRIVER";
        $user = ($calling_from == "USER") ? $request->user('api') : $request->user('api-driver');
        $package_for = ($calling_from == "DRIVER") ? 2 : 1;

        $string_file = $this->getStringFile(null, $user->Merchant);
        if ($package_for == 2) {
            $free_packages = DriverSubscriptionRecord::select('subscription_pack_id')->where([['package_type', 1], ['driver_id', $user->id], ['status', 1], ['end_date_time', NULL]])
                ->whereHas('SubscriptionPackage', function ($q) {
                    $q->where('expire_date', '>=', date('Y-m-d'));
                    $q->orWhere('expire_date', NULL);
                })
                ->get()->toArray();
        } else {
            $free_packages = UserSubscriptionRecord::select('subscription_pack_id')->where([['package_type', 1], ['user_id', $user->id], ['status', 1], ['end_date_time', NULL]])
                ->whereHas('SubscriptionPackage', function ($q) {
                    $q->where('expire_date', '>=', date('Y-m-d'));
                    $q->orWhere('expire_date', NULL);
                })
                ->get()->toArray();
        }
        $arr_assigned_fee_package = array_column($free_packages, 'subscription_pack_id');
        $packages = SubscriptionPackage::with('PackageDuration')
            ->where([['merchant_id', $user->merchant_id], ['status', true], ['package_for', $package_for]])
            ->whereHas('CountryArea', function ($query) use (&$user) {
                $query->where('country_area_id', '=', $user->CountryArea->id);
            })
            ->where(function ($q) use ($arr_assigned_fee_package) {
                $q->where('package_type', 2); // paid
                if (!empty($arr_assigned_fee_package)) {
                    $q->orWhereIn('id', $arr_assigned_fee_package); // free assigned package of driver
                }
            })
            ->where(function ($q) {
                $q->where('expire_date', '>=', date('Y-m-d'));
                $q->orWhere('expire_date', NULL);
            })
            ->get(['id', 'max_trip', 'image', 'price', 'package_type', 'merchant_id', 'package_duration_id', 'expire_date']);
        if ($user->merchant_id == 82) {
            //for ultra taxi only
            $all_payment_methods = $user->Merchant->PaymentMethod->where('id', '=', 3);
        } else {
            $all_payment_methods = $user->Merchant->PaymentMethod->where('id', '!=', 1);
        }
        $payment_methods = $all_payment_methods->map(function ($item, $key) {
            return $item->only(['id', 'payment_method', 'payment_icon']);
        })->values();

        foreach ($packages as $key => $package) :
            $package->name = $package->name;
            $package->expire_date = !empty($package->expire_date) ? $package->expire_date : '';
            $package->package_type = $package->package_type;
            $package->description = $package->description;
            $package->show_price = $user->CountryArea->Country->isoCode . ' ' . $package->price;
            //            $package->package_duration_name = $package->PackageDuration->name;
            $package->package_duration_name = '';
            $duration = $package->PackageDuration->getNameAccMerchantAttribute($user->merchant_id, $package->package_duration_id);
            if (!empty($duration)) {
                $package->package_duration_name = $duration;
            }
            $package->image = get_image($package->image, 'package', $package->merchant_id);
            $package->service_type = [];

        endforeach;
        if ($package_for == 2) {
            $active_packages = DriverSubscriptionRecord::select('subscription_pack_id', 'payment_method_id', 'package_duration_id', 'package_total_trips', 'price', 'used_trips', 'start_date_time', 'end_date_time', 'status', 'package_type')
                ->with('SubscriptionPackage.ServiceType')
                ->where([['driver_id', $user->id], ['status', 2], ['end_date_time', '>=', date('Y-m-d H:i:s')]])
                ->get();
        } else {
            $active_packages = UserSubscriptionRecord::select('subscription_pack_id', 'payment_method_id', 'package_duration_id', 'package_total_trips', 'price', 'used_trips', 'start_date_time', 'end_date_time', 'status', 'package_type')
                ->with('SubscriptionPackage.ServiceType')
                ->where([['user_id', $user->id], ['status', 2], ['end_date_time', '>=', date('Y-m-d H:i:s')]])
                ->get();
        }
        foreach ($active_packages as $key => $active_package) :
            $active_package->name = $active_package->SubscriptionPackage->name;
            $active_package->package_type = $active_package->package_type;
            $active_package->description = $active_package->SubscriptionPackage->description;
            $active_package->show_price = $user->CountryArea->Country->isoCode . ' ' . $active_package->price;
            $active_package->active = true;
            $active_package->rides_left = $active_package->package_total_trips - $active_package->used_trips;
            //            $active_package->package_duration_name = $active_package->PackageDuration->name;
            $active_package->package_duration_name = '';
            $duration = $active_package->PackageDuration->getNameAccMerchantAttribute($user->merchant_id, $active_package->package_duration_id);
            if (!empty($duration)) {
                $active_package->package_duration_name = $duration;
            }
            $active_package->image = get_image($active_package->SubscriptionPackage->image, 'package', $user->merchant_id);
            $active_package->service_type = [];
        endforeach;
        $activated_any_pack = (!empty($active_packages->toArray()) && count($active_packages->toArray()) > 0) ? true : false;
        $data = ['activated_pack' => $activated_any_pack, 'active_pack_detail' => $active_packages, 'data' => $packages, 'payment_method' => $payment_methods];
        return $this->successResponse(trans("$string_file.success"), $data);
    }

    // public function ViewPackages(Request $request)
    // {

    //     $driver = $request->user('api-driver');
    //     $string_file = $this->getStringFile(null, $driver->Merchant);
    //     $free_packages = DriverSubscriptionRecord::select('subscription_pack_id')->where([['package_type', 1], ['driver_id', $driver->id], ['status', 1], ['end_date_time', NULL]])
    //         ->whereHas('SubscriptionPackage', function ($q) {
    //             $q->where('expire_date', '>=', date('Y-m-d'));
    //             $q->orWhere('expire_date', NULL);
    //         })
    //         ->get()->toArray();
    //     $arr_assigned_fee_package = array_column($free_packages, 'subscription_pack_id');
    //     $packages = SubscriptionPackage::with('PackageDuration')
    //         ->where([['merchant_id', $driver->merchant_id], ['status', true]])
    //         ->whereHas('CountryArea', function ($query) use (&$driver) {
    //             $query->where('country_area_id', '=', $driver->CountryArea->id);
    //         })
    //         ->where(function ($q) use ($arr_assigned_fee_package) {
    //             $q->where('package_type', 2); // paid
    //             if (!empty($arr_assigned_fee_package)) {
    //                 $q->orWhereIn('id', $arr_assigned_fee_package); // free assigned package of driver
    //             }
    //         })
    //         ->where(function ($q) {
    //             $q->where('expire_date', '>=', date('Y-m-d'));
    //             $q->orWhere('expire_date', NULL);
    //         })
    //         ->get(['id', 'max_trip', 'image', 'price', 'package_type', 'merchant_id', 'package_duration_id', 'expire_date']);
    //     if ($driver->merchant_id == 82) {
    //         //for ultra taxi only
    //         $all_payment_methods = $driver->Merchant->PaymentMethod->where('id', '=', 3);
    //     } else {
    //         $all_payment_methods = $driver->Merchant->PaymentMethod->where('id', '!=', 1);
    //     }
    //     $payment_methods = $all_payment_methods->map(function ($item, $key) {
    //         return $item->only(['id', 'payment_method', 'payment_icon']);
    //     })->values();

    //     foreach ($packages as $key => $package) :
    //         $package->name = $package->name;
    //         $package->expire_date = !empty($package->expire_date) ? $package->expire_date : '';
    //         $package->package_type = $package->package_type;
    //         $package->description = $package->description;
    //         $package->show_price = $driver->CountryArea->Country->isoCode . ' ' . $package->price;
    //         //            $package->package_duration_name = $package->PackageDuration->name;
    //         $package->package_duration_name = '';
    //         $duration = $package->PackageDuration->getNameAccMerchantAttribute($driver->merchant_id, $package->package_duration_id);
    //         if (!empty($duration)) {
    //             $package->package_duration_name = $duration;
    //         }
    //         $package->image = get_image($package->image, 'package', $package->merchant_id);
    //         $package->service_type = [];

    //     endforeach;
    //     $active_packages = DriverSubscriptionRecord::select('subscription_pack_id', 'payment_method_id', 'package_duration_id', 'package_total_trips', 'price', 'used_trips', 'start_date_time', 'end_date_time', 'status', 'package_type')
    //         ->with('SubscriptionPackage.ServiceType')
    //         ->where([['driver_id', $driver->id], ['status', 2], ['end_date_time', '>=', date('Y-m-d H:i:s')]])
    //         ->get();
    //     foreach ($active_packages as $key => $active_package) :
    //         $active_package->name = $active_package->SubscriptionPackage->name;
    //         $active_package->package_type = $active_package->package_type;
    //         $active_package->description = $active_package->SubscriptionPackage->description;
    //         $active_package->show_price = $driver->CountryArea->Country->isoCode . ' ' . $active_package->price;
    //         $active_package->active = true;
    //         $active_package->rides_left = $active_package->package_total_trips - $active_package->used_trips;
    //         //            $active_package->package_duration_name = $active_package->PackageDuration->name;
    //         $active_package->package_duration_name = '';
    //         $duration = $active_package->PackageDuration->getNameAccMerchantAttribute($driver->merchant_id, $active_package->package_duration_id);
    //         if (!empty($duration)) {
    //             $active_package->package_duration_name = $duration;
    //         }
    //         $active_package->image = get_image($active_package->SubscriptionPackage->image, 'package', $driver->merchant_id);
    //         $active_package->service_type = [];
    //     endforeach;
    //     $activated_any_pack = (!empty($active_packages->toArray()) && count($active_packages->toArray()) > 0) ? true : false;
    //     $data =  ['activated_pack' => $activated_any_pack, 'active_pack_detail' => $active_packages, 'data' => $packages, 'payment_method' => $payment_methods];
    //     return $this->successResponse(trans("$string_file.success"), $data);
    //     //        return response()->json(['result'=>"1", 'message'=>trans('api.driver'), 'activated_pack'=>$activated_any_pack, 'active_pack_detail'=>$active_packages, 'data'=>$packages, 'payment_method'=>$payment_methods]);
    // }

    // Activate package
    public function ActivatePackage(Request $request)
    {
        $calling_from = $request->calling_from;
        if (empty($calling_from)) $calling_from = "DRIVER";

        $user = ($calling_from == "USER") ? $request->user('api') : $request->user('api-driver');
        $package_for = ($calling_from == "DRIVER") ? 2 : 1;
        $string_file = $this->getStringFile(null, $user->Merchant);

        $validator = Validator::make($request->all(), [
            'subscription_package_id' => [
                'required',
                Rule::exists('subscription_packages', 'id')->where(function ($query) use (&$user, $package_for) {
                    $query->where([['merchant_id', $user->merchant_id], ['package_for', "=", $package_for], ['status', true], ['admin_delete', 0], ['deleted_at', null]]);
                }),
            ],
            'package_type' => 'required',
            'payment_method_id' => 'required_if:package_type,==,2',
            // 'payment_status' => [
            //     'required_unless:payment_method_id,3', 'string',
            //     Rule::in(['SUCCESS', 'FAIL']),
            // ],
        ]);
        if ($package_for == 2) {
            $request->request->add(['package' => $request->subscription_package_id, 'driver_id' => $user->id]);
        } else {
            $request->request->add(['package' => $request->subscription_package_id, 'user_id' => $user->id]);
        }

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            if ($request->payment_method_id == 3) {
                $package = SubscriptionPackage::where([['merchant_id', $user->merchant_id], ['package_for', "=", $package_for]])->findorfail($request->subscription_package_id);
                if ($package_for == 2) {
                    $this->CheckWalletActivation($user, null, $package);
                } else {
                    $this->CheckWalletActivation(null, $user, $package);
                }
                if ($request->payment_status == "FAIL") {
                    throw new \Exception(trans("$string_file.low_wallet_warning"));
                }
            }
            $this->SavePackageDetails($request);
            return $this->successResponse(trans("$string_file.subscription_activated"));
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
        // only supports wallet payment, if other method will be integrated then need to be handled

    }

    public function CheckWalletActivation(Driver $driver = null, User $user = null, SubscriptionPackage $package): bool
    {
        if (!empty($driver) && $driver->wallet_money >= $package->price) :
            $paramArray = array(
                'driver_id' => $driver->id,
                'booking_id' => null,
                'amount' => $package->price,
                'narration' => 4,
                'platform' => 1,
                'payment_method' => 3,
                'receipt' => rand(1111, 983939),
            );
            WalletTransaction::WalletDeduct($paramArray);
            request()->request->add(['payment_status' => 'SUCCESS']);
            return true;
        elseif (!empty($user) && $user->wallet_balance >= $package->price):
            $paramArray = array(
                'user_id' => $user->id,
                'booking_id' => null,
                'amount' => $package->price,
                'narration' => 4,
                'platform' => 1,
                'payment_method' => 3,
                'receipt' => rand(1111, 983939),
            );
            WalletTransaction::UserWalletDebit($paramArray);
            request()->request->add(['payment_status' => 'SUCCESS']);
            return true;
        else :
            request()->request->add(['payment_status' => 'FAIL']);
            return false;
        endif;
    }

    public function SubscriptionPackageDuration($package_duration_id)
    {
        $package_duration = PackageDuration::find($package_duration_id);
        $days = $package_duration->sequence; // number of days
        return ['start_date_time' => date('Y-m-d H:i:s'), 'end_date_time' => (new \DateTime(date('Y-m-d H:i:s')))->modify("+$days day")->format('Y-m-d H:i:s')];
    }

    public function SavePackageDetails(Request $request, $isWeb = false)
    {
        DB::beginTransaction();
        $driver_id = $request->driver_id;
        $user_id = $request->user_id;

        try {
            if (!empty($driver_id)) {
                $driver_record = new DriverSubscriptionRecord;
                $package = SubscriptionPackage::findorfail($request->package);
                $merchant_id = $package->merchant_id;
                $string_file = $this->getStringFile($merchant_id);
                $duration = new SubscriptionPackageController();
                $duration_data = $duration->SubscriptionPackageDuration($package->package_duration_id);

                $driver_record->package_type = $package->package_type;
                if (!$isWeb) {
                    // for paid package
                    if ($package->package_type != $request->package_type) {
                        throw new \Exception(trans("$string_file.subscription_failed"));
                    }
                }
                if ($package->package_type == 2) {
                    // get active package of driver
                    $active_pack = DriverSubscriptionRecord::where('driver_id', $request->driver_id)->where('package_type', 2)->where('segment_id', $package->segment_id)->where('status', 2)->where('end_date_time', ">=", date('Y-m-d H:i:s'))->orderBy('id', 'DESC')->first();
                    $driver_record->driver_id = $request->driver_id;
                    $driver_record->payment_method_id = $request->payment_method_id;
                    $driver_record->segment_id = $package->segment_id;
                    $driver_record->subscription_pack_id = $package->id;
                    $driver_record->package_duration_id = $package->package_duration_id;
                    $driver_record->package_total_trips = $package->max_trip;
                    $driver_record->price = $package->price;
                    $driver_record->start_date_time = $duration_data['start_date_time'];
                    $driver_record->end_date_time = $duration_data['end_date_time'];
                    $driver_record->used_trips = 0;
                    $driver_record->status = 2; // activate package
                    if (!empty($active_pack->id) && (strtotime($active_pack->end_date_time) >= strtotime("now"))) :
                        $carry_forward_trips = ($active_pack->package_total_trips - $active_pack->used_trips);
                        $driver_record->package_total_trips = $package->max_trip + $carry_forward_trips;
                        $driver_record->carry_forward_sub_pack_id = $active_pack->subscription_pack_id; //carry forward to current package //
                        $left_time = (strtotime($active_pack->end_date_time) - strtotime(date('Y-m-d H:i:s')));
                        $total_time = strtotime($duration_data['end_date_time']) + $left_time;
                        $end_date = date('Y-m-d H:i:s', $total_time);
                        $driver_record->end_date_time = $end_date;
                        $active_pack->status = 4; //carry forward to current package //
                        $active_pack->carry_forward_sub_pack_id = $package->id; //carry forward to current package //
                        $active_pack->save();
                    endif;
                    $driver_record->save();
                } elseif ($package->package_type == 1) {
                    $activated_package = DriverSubscriptionRecord::where([['driver_id', $request->driver_id], ['package_type', $package->package_type], ['subscription_pack_id', $request->package], ['status', 2]])->first();
                    if (!empty($activated_package->id)) {
                        throw new \Exception(trans("$string_file.subscription_already_activated"));
                        // return  ['result' => "0", 'message' => trans('api.already_activated')];
                    }

                    $driver_record = DriverSubscriptionRecord::where([['driver_id', $request->driver_id], ['package_type', $package->package_type], ['subscription_pack_id', $request->package], ['status', 1], ['end_date_time', NULL], ['start_date_time', NULL]])->first();
                    $driver_record->start_date_time = $duration_data['start_date_time'];
                    $driver_record->end_date_time = $duration_data['end_date_time'];
                    $driver_record->status = 2;
                    $driver_record->save();
                }
            } elseif (!empty($user_id)) {
                $user_record = new UserSubscriptionRecord();
                $package = SubscriptionPackage::findorfail($request->package);
                $merchant_id = $package->merchant_id;
                $string_file = $this->getStringFile($merchant_id);
                $duration = new SubscriptionPackageController();
                $duration_data = $duration->SubscriptionPackageDuration($package->package_duration_id);

                $user_record->package_type = $package->package_type;
                if (!$isWeb) {
                    // for paid package
                    if ($package->package_type != $request->package_type) {
                        throw new \Exception(trans("$string_file.subscription_failed"));
                    }
                }
                if ($package->package_type == 2) {
                    // get active package of driver
                    $active_pack = UserSubscriptionRecord::where('user_id', $request->user_id)->where('package_type', 2)->where('segment_id', $package->segment_id)->where('status', 2)->where('end_date_time', ">=", date('Y-m-d H:i:s'))->orderBy('id', 'DESC')->first();
                    $user_record->user_id = $request->user_id;
                    $user_record->payment_method_id = $request->payment_method_id;
                    $user_record->segment_id = $package->segment_id;
                    $user_record->subscription_pack_id = $package->id;
                    $user_record->package_duration_id = $package->package_duration_id;
                    $user_record->package_total_trips = $package->max_trip;
                    $user_record->price = $package->price;
                    $user_record->start_date_time = $duration_data['start_date_time'];
                    $user_record->end_date_time = $duration_data['end_date_time'];
                    $user_record->used_trips = 0;
                    $user_record->status = 2; // activate package
                    if (!empty($active_pack->id) && (strtotime($active_pack->end_date_time) >= strtotime("now"))) :
                        $carry_forward_trips = ($active_pack->package_total_trips - $active_pack->used_trips);
                        $user_record->package_total_trips = $package->max_trip + $carry_forward_trips;
                        $user_record->carry_forward_sub_pack_id = $active_pack->subscription_pack_id; //carry forward to current package //
                        $left_time = (strtotime($active_pack->end_date_time) - strtotime(date('Y-m-d H:i:s')));
                        $total_time = strtotime($duration_data['end_date_time']) + $left_time;
                        $end_date = date('Y-m-d H:i:s', $total_time);
                        $user_record->end_date_time = $end_date;
                        $active_pack->status = 4; //carry forward to current package //
                        $active_pack->carry_forward_sub_pack_id = $package->id; //carry forward to current package //
                        $active_pack->save();
                    endif;
                    $user_record->save();
                } elseif ($package->package_type == 1) {
                    $activated_package = UserSubscriptionRecord::where([['user_id', $request->user_id], ['package_type', $package->package_type], ['subscription_pack_id', $request->package], ['status', 2]])->first();
                    if (!empty($activated_package->id)) {
                        throw new \Exception(trans("$string_file.subscription_already_activated"));
                        // return  ['result' => "0", 'message' => trans('api.already_activated')];
                    }

                    $user_record = UserSubscriptionRecord::where([['user_id', $request->user_id], ['package_type', $package->package_type], ['subscription_pack_id', $request->package], ['status', 1], ['end_date_time', NULL], ['start_date_time', NULL]])->first();
                    $user_record->start_date_time = $duration_data['start_date_time'];
                    $user_record->end_date_time = $duration_data['end_date_time'];
                    $user_record->status = 2;
                    $user_record->save();
                }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();

            // p($message);
            // Rollback Transaction
            DB::rollback();
            throw new \Exception($message);
        }
        DB::commit();

        if ($isWeb) {


            //            setLocal($driver->language);
            $msg = trans("$string_file.paid_subscription_notify_driver");
            $title = trans("$string_file.activate_subscription");
            $data['notification_type'] = "BOUGHT_SUBSCRIPTION";
            $data['segment_type'] = "";
            $data['segment_data'] = [];
            $arr_param = ['driver_id' => $driver_id, 'data' => $data, 'message' => $msg, 'merchant_id' => $merchant_id, 'title' => $title, 'large_icon' => ''];
            Onesignal::DriverPushMessage($arr_param);
            setLocal();
            //            $merchant_id = get_merchant_id();
            //            $msg = trans('api.subscription_activated');
            //            $type = 17;
            //            Onesignal::DriverPushMessage($driver_id, [], $msg, $type, $merchant_id, 1);
        }
    }

    public function ActivateRenewableSubscription(Request $request)
    {
        $calling_from = $request->calling_from;
        if (empty($calling_from)) $calling_from = "DRIVER";

        $driver = $request->user('api-driver');
        $string_file = $this->getStringFile(null, $driver->Merchant);

        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required',
            'segment_id' => 'required'
        ]);
        $request->merge(['driver_id' => $driver->id]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant_id = $driver->merchant_id;
            $work_config = $this->getDriverOnlineConfig($driver, 'online_details');
            $vehicle_type_id  = $work_config['vehicle_type_id'];
            $string_file = $this->getStringFile($merchant_id);
            $driver_record = new DriverRenewableSubscriptionRecord();

            $common_controller = new \App\Http\Controllers\Helper\CommonController();
            $renewable_subscription_details= $common_controller->getRenewableSubscriptionDetails($driver, $vehicle_type_id);
            $price = $renewable_subscription_details['renewable_subscription_price'];

            $getDriverOnlineConfig = $this->getDriverOnlineConfig($driver, 'online_details');
            $active_pack = RenewableSubscription::where('merchant_id', $merchant_id)->where('vehicle_type_id',  $getDriverOnlineConfig['vehicle_type_id'])->first();
            if(! $active_pack){
                throw new \Exception(trans("$string_file.subscription_not_found"));
            }
            if($driver->hasActiveRenewableSubscriptionRecord() && $request->payment_method_id == 3){
                $message = "Subscription Already Active";
                return $this->successResponse($message, []);
            }
            
            $driverRenewableEarning = $driver->DriverRenewableSubscriptionRecord()->orderBy('id', 'DESC')->first();
            if (!empty($driverRenewableEarning) || !empty($driver->renewable_subscription_trail_datetime)) {
                  $last_renew_date = !empty($driverRenewableEarning) ? Carbon::createFromTimestamp($driverRenewableEarning->timestamp)->format('Y-m-d') : Carbon::createFromTimestamp($driver->renewable_subscription_trail_datetime, "UTC")->format('Y-m-d');
            }
            
            
            $driver_record->driver_id = $request->driver_id;
            $driver_record->payment_method_id = $request->payment_method_id;
            $driver_record->segment_id = $request->segment_id;
            $driver_record->renewable_subscription_id = $active_pack->id;
            $driver_record->subscription_fee = $price;
             $driver_record->subscription_for_date = $last_renew_date;
            $driver_record->earned = $renewable_subscription_details['totalEarnings'];
            $driver_record->ride_count = $renewable_subscription_details['bookingCount'];;
            $driver_record->transaction_id = $request->transaction_id;
            $driver_record->timestamp = time();
            $driver_record->save();


            if ($request->payment_method_id == 3) {
                $this->RenewableSubscriptionWalletActivation($driver, $price, $request->payment_method_id, $driver_record);
                if ($request->payment_status == "FAIL") {
                    throw new \Exception(trans("$string_file.low_wallet_warning"));
                }
            }
            else if($request->payment_method_id == 4){
                if ($request->payment_status == 1) {
                    $this->RenewableSubscriptionWalletActivation($driver, $price, $request->payment_method_id, $driver_record);
                }
                else{
                    throw new \Exception(trans("$string_file.payment_failed"));
                }
            }

            if($driver->renewable_subscription_trail == 1){
                $driver->renewable_subscription_trail = 2;
                $driver->save();
            }

        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.subscription_activated"));

    }

    public function RenewableSubscriptionWalletActivation(Driver $driver = null, $amount, $payment_method, $driver_record): bool
    {

        if (!empty($driver) && ((empty($driver->wallet_money) && $amount == 0) || $driver->wallet_money >= $amount) && $payment_method == 3) :
            $paramArray = array(
                'driver_id' => $driver->id,
                'booking_id' => null,
                'amount' => $amount,
                'narration' => 4,
                'platform' => 1,
                'payment_method' => 3,
                'receipt' => rand(1111, 983939),
                'renewable_subscription_record_id'=> $driver_record->id
            );
            WalletTransaction::WalletDeduct($paramArray);
            request()->merge(['payment_status' => 'SUCCESS']);
            return true;
        elseif($payment_method == 4):
            request()->merge(['payment_status' => 'SUCCESS']);
            return true;
        else :
            request()->merge(['payment_status' => 'FAIL']);
            return false;
        endif;
    }

    public function getRenewableSubscriptionHistory(Request $request){
        $user = $request->user('api-driver');
        $string_file = $this->getStringFile(null, $user->Merchant);
        $packages = \App\Models\DriverRenewableSubscriptionRecord::where('driver_id', $user->id)
            ->where('segment_id', $request->segment_id)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        $arr_packages = [];
        foreach ($packages as $active) {
            $startDate = \Carbon\Carbon::createFromTimestamp($active->timestamp, $user->CountryArea->timezone);
            $enddate = \Carbon\Carbon::createFromTimestamp($active->timestamp, $user->CountryArea->timezone)->endOfDay();

            $arr_packages[] = [
                'id' => $active->id,
                'start_date_time' => $startDate->toDateTimeString(),
                'end_date_time' => $enddate->toDateTimeString(),
                'payment_method' => 'wallet',
                'previous_earning' => !empty($active->earned)? $user->Country->isoCode." ".$active->earned: "0",
                'ride_count'=> !empty($active->ride_count)? (string) $active->ride_count: "0",
                'subscription_fee' => $active->Driver->Country->isoCode .' '.$active->subscription_fee,
            ];
        }

        $data = ['packages_history' => $arr_packages];
        return response()->json(['result' => "1", 'message' => trans("$string_file.success"), 'next_page_url' =>
            "",  'total_pages' => $packages->lastPage(), 'current_page' => $packages->currentPage(), 'data' => $data]);
    }
}
