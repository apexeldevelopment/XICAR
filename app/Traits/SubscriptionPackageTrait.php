<?php

namespace App\Traits;

use App\Models\LangRenewableSubscription;
use App\Models\RenewableSubscription;
use App\Models\VehicleType;
use Illuminate\Support\Facades\DB;
use App\Models\CountryArea;
use App\Models\LangSubscriptionPack;
use App\Models\Merchant;
use App\Models\PackageDuration;
use App\Models\ServiceType;
use App\Models\SubscriptionPackage;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use App\Http\Controllers\Eats\Helper\HelperFunctionsController;
use App\Traits\ImageTrait;
use App\Models\Onesignal;

trait SubscriptionPackageTrait
{
    use ImageTrait;
    public function SavePackage(Request $request)
    {
        DB::beginTransaction();
                try {
                    $lang_data = $request->only(['name', 'description']);
                    if(!empty($request->id))
                    {
                        $package_submit =  SubscriptionPackage::Find($request->id);
                    }
                    else
                    {
                        $package_submit =  new SubscriptionPackage;
                    }
                    $package_submit->merchant_id = get_merchant_id();
                    $package_submit->price = $request->package_type == 2 ? $request->price : 0;
                    $package_submit->max_trip = $request->max_trip;
                    $package_submit->segment_id = $request->segment_id;
                    $package_submit->country_area_id = $request->country_area_id;
                    $package_submit->package_duration_id = $request->package_duration;
                    $package_submit->package_type = $request->package_type;
                    $package_submit->package_for = $request->for;
                    $package_submit->amount = $request->amount;
                    $package_submit->price_type = $request->price_type;
                    $package_submit->expire_date = !empty($request->expire_date) ? $request->expire_date : NULL;
                    if($request->hasFile('image') && $request->file('image') instanceof UploadedFile):
                        $package_submit->image = $this->uploadImage('image', 'package');
                    endif;
                    if($package_submit->save()):
                        $this->saveLangPackages(collect($lang_data), $package_submit);
//                        if ($request->has('services')) {
//                            $package_submit->ServiceType()->sync($request->input('services'));
//                        } else {
//                            $package_submit->ServiceType()->detach();
//                        }

                        /* A area will has only one pack, thats why shifting area id to subscription table
                         */
                        // if ($request->has('areas')) {
                        //     $package_submit->CountryArea()->sync($request->input('areas'));
                        // } else {
                        //     $package_submit->CountryArea()->detach();
                        // }
                    endif;
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    p($message);
                    // Rollback Transaction
                    DB::rollback();
                    return false;
                }
                DB::commit();
                return true;
    }
    
    public function getAllMerchantAreas($pagination = true)
    {
        $merchant = Auth::user('merchant');
        $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
        $query = CountryArea::where([['merchant_id',$merchant_id],['status',true]]);
        $allareas = $pagination == true ? $query->paginate($query) : $query;
        return $allareas;
    }

    public function getAllVehicleTypes($pagination = true)
    {
        $merchant = Auth::user('merchant');
        $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
        $query = VehicleType::where([['merchant_id',$merchant_id]]);
        $allvehicles = $pagination == true ? $query->paginate($query) : $query;
        return $allvehicles;
    }

    private function saveRenewableLang(Collection $collection, RenewableSubscription $packages_lang_data)
    {
        try {
            $collect_lang_data = $collection->toArray();
            $update_lang_pro = LangRenewableSubscription::where([['renewable_subscription_id','=',$packages_lang_data->id],['locale', '=', \App::getLocale()]])->first();
            if(!empty($update_lang_pro)){
                $update_lang_pro['name'] = $collect_lang_data['name'];
                $update_lang_pro['description'] = $collect_lang_data['description'];
                $update_lang_pro->save();
            }else{
                $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
                $language_pro = new LangRenewableSubscription([
                    'renewable_subscription_id' => $packages_lang_data->id,
                    'merchant_id' => $merchant_id,
                    'locale' => \App::getLocale(),
                    'name' => $collect_lang_data['name'],
                    'description' => $collect_lang_data['description'],
                ]);

                $packages_lang_data->LangRenewableSubscription()->save($language_pro);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            dd($message);
        }
    }

//    public function UpdatePackage(Request $request,$id)
//    {
//
//        DB::beginTransaction();
//                try {
//                    $update = $this->getAllPackages(false)->FindorFail($id);
//                    $update_lang_data = $request->only(['name', 'description']);
//                    if($request->hasFile('image')):
//                        $packdata['image'] = $this->uploadImage('image','package');
//                    endif;
//                    $packdata['price'] = $request->price;
//                    $packdata['max_trip'] = $request->max_trip;
//                    $packdata['package_duration_id'] = $request->package_duration;
//                    $this->saveLangPackages(collect($update_lang_data), $update);
//                    if($update->fill($packdata)->save())
//                    {
//                        if ($request->has('services')) {
//                            $update->ServiceType()->sync($request->input('services'));
//                        } else {
//                            $update->ServiceType()->detach();
//                        }
//
//                        if ($request->has('areas')) {
//                            $update->CountryArea()->sync($request->input('areas'));
//                        } else {
//                            $update->CountryArea()->detach();
//                        }
//
//                    }
//
//                } catch (\Exception $e) {
//                    $message = $e->getMessage();
//                    p($message);
//                    // Rollback Transaction
//                    DB::rollback();
//                    return false;
//                }
//                DB::commit();
//        return true;
//    }

    private function saveLangPackages(Collection $collection, SubscriptionPackage $packages_lang_data)
    {
        DB::beginTransaction();
                try {
                    $collect_lang_data = $collection->toArray();
                    $update_lang_pro = LangSubscriptionPack::where([['subscription_package_id','=',$packages_lang_data->id],['locale', '=', \App::getLocale()]])->first();
                    if(!empty($update_lang_pro)){
                        //print_r($update_lang_pro->toArray());
                        $update_lang_pro['name'] = $collect_lang_data['name'];
                        $update_lang_pro['description'] = $collect_lang_data['description'];
                        $update_lang_pro->save();
                    }else{
                        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
                        $language_pro = new LangSubscriptionPack([
                            'subscription_package_id' => $packages_lang_data->id,
                            'merchant_id' => $merchant_id,
                            'locale' => \App::getLocale(),
                            'name' => $collect_lang_data['name'],
                            'description' => $collect_lang_data['description'],
                        ]);

                        $packages_lang_data->LangPackages()->save($language_pro);
                    }
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    p($message);
                    // Rollback Transaction
                    DB::rollback();
                }
                DB::commit();

    }

    public function getAllPackages($pagination = true)
    {
        $merchant = Auth::user('merchant');
        $merchant_id = get_merchant_id();
        $query = SubscriptionPackage::with(['LangSubscriptionPackageSingle','PackageDuration'])->where([['admin_delete','0'], ['subscription_packages.merchant_id', '=', $merchant_id]]);
        $allpackages = $pagination == true ? $query->paginate(25) : $query;
        return $allpackages;
    }

    public function getAllMerchantServices($pagination = true)
    {
        $merchant = Auth::user('merchant');
        $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
        $merchant_detail = Merchant::findorfail($merchant_id);          
        $query = $merchant_detail->ServiceType;
        //$helper = new HelperFunctionsController();
        //$allservices = $pagination == true ? $helper->paginate($query) : $query;
        return $query;
    }

    public function getPackagesDuration($pagination = true)
    {
        $query = PackageDuration::with(['LangPackageDurationSingleApi'])->orderBy('sequence','asc')->where('merchant_id',get_merchant_id());
        $alldurations = $pagination == true ? $query->paginate(25) : $query;
        return $alldurations;
    }

    public function sendSubscriptionNotificationtoBs($merchantPlan,$bs,$maxAmount=NULL){

        $plan_type = $merchantPlan->plan_type;
        $message = "";
        // title and message of notification based on plan type(period based or order based)
        switch ($plan_type) {
            case "1":
                $title = trans("$string_file.subscription_time_expired");
                $message = trans("$string_file.subscription_time_expired");
                break;
            case "2":
                $title = trans("$string_file.subscription_order_expired");
                $message = trans("$string_file.subscription_order_expired");
                break;
        }

        if($maxAmount > $merchantPlan->max_amount_valid){
            $title = trans("$string_file.plan_amount_exceeded");
            $message = trans("$string_file.plan_amount_exceeded");
        }
        //send onesignal message to restro
        $data = array('order_id' => '', 'order_number' => '', 'notification_type' => 'SUBSCRIPTION_EXPIRED', 'segment_type' => '', 'order_number' => '');
        $arr_param = array(
            'business_segment_id' => $bs->business_segment_id,
            'data' => $data,
            'message' => $message,
            'merchant_id' => $merchantPlan->merchant_id,
            'title' => $tilte
        );
        Onesignal::BusinessSegmentPushMessage($arr_param);
    }

}