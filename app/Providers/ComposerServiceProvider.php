<?php

namespace App\Providers;

use App\Models\Merchant;
use Auth;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\Helper\Merchant as helperMerchant;
use App\Traits\MerchantTrait;

class ComposerServiceProvider extends ServiceProvider
{
    use MerchantTrait;
    public function boot()
    {
        view()->composer('merchant.layouts.nav', function ($view) {
            $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
            $languages = Merchant::with('Language')->find($merchant_id);
//            echo($languages); die;
            $default_language = isset($languages->Configuration->default_language) ? $languages->Configuration->default_language : "";
            $view->with(['languages' => $languages->language, 'default_language' => $default_language]);
        });

        view()->composer('merchant.layouts.sidebar', function ($view) {
            $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
            $merchant = Merchant::find($merchant_id);
            $add_info = array(
                'cashback' => $merchant->Configuration->cashback_module,
                'wallet_promo_code' => $merchant->Configuration->wallet_promo_code,
            );
            $merchant_segment = helperMerchant::MerchantSegments(1);
            $arr_segment_group =   $this->segmentGroup($merchant_id,$return_type = "drop_down","");
            $merchant_segment_group = isset($arr_segment_group['arr_group']) ?  array_keys($arr_segment_group['arr_group']) : [];

            $handyman_apply_promocode = $this->merchantHandymanPromocode($merchant_id);
            $view->with(['add_info' => $add_info, 'service_types' => $merchant->Service, 'config' => $merchant->Configuration, 'app_config' => $merchant->ApplicationConfiguration, 'booking_config' => $merchant->BookingConfiguration,'merchant_segment' => $merchant_segment, 'merchant_segment_group' => $merchant_segment_group, 'handyman_apply_promocode' => $handyman_apply_promocode]);
        });

        view()->composer('merchant.layouts.footer', function ($view) {
            $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
            $merchant = Merchant::find($merchant_id);
            $view->with(['merchant' => $merchant]);
        });

        // for business segment
        view()->composer('business-segment.element.nav', function ($view) {
            $merchant_id = NULL;
            if(Auth::guard('business-segment')->check())
            {
                $merchant_id = !empty(Auth::user('business-segment')) && !empty(Auth::user('business-segment')->merchant_id) ? Auth::user('business-segment')->merchant_id : NULL;
            }
            $languages = Merchant::with('Language')->find($merchant_id);
            $view->with(['languages' => $languages->language]);
        });

        // for taxi company
        view()->composer('taxicompany.element.nav', function ($view) {
            $merchant_id = NULL;
            if(Auth::guard('taxicompany')->check())
            {
                $merchant_id = !empty(Auth::user('taxicompany')) && !empty(Auth::user('taxicompany')->merchant_id) ? Auth::user('taxicompany')->merchant_id : NULL;
            }
            $languages = Merchant::with('Language')->find($merchant_id);
            $view->with(['languages' => $languages->language]);
        });

        view()->composer('*', function ($view) {
            $merchant_id = NULL;
            if(Auth::guard('merchant')->check())
            {
                if(!empty(Auth::user('merchant')) && Auth::user('merchant')->parent_id != 0)
                {
                    $merchant_id =    Auth::user('merchant')->parent_id;
                }
                else
                {
                    $merchant_id =    !empty(Auth::user('merchant')) ? Auth::user('merchant')->id : NULL;
                }
            }
            elseif(Auth::guard('taxicompany')->check())
            {
                $merchant_id = !empty(Auth::user('taxicompany')) && Auth::user('taxicompany')->merchant_id ? Auth::user('taxicompany')->merchant_id : NULL;
            }
            elseif(Auth::guard('corporate')->check())
            {
                $merchant_id = !empty(Auth::user('corporate')) && !empty(Auth::user('corporate')->merchant_id) ? Auth::user('corporate')->merchant_id : NULL;
            }
            elseif(Auth::guard('hotel')->check())
            {
                $merchant_id = !empty(Auth::user('hotel')) && !empty(Auth::user('hotel')->merchant_id) ? Auth::user('hotel')->merchant_id : NULL;
            }
            elseif(Auth::guard('business-segment')->check())
            {
                $merchant_id = !empty(Auth::user('business-segment')) && !empty(Auth::user('business-segment')->merchant_id) ? Auth::user('business-segment')->merchant_id : NULL;
            }
            elseif(Auth::guard('laundry_outlet')->check())
            {
                $merchant_id = !empty(Auth::user('laundry_outlet')) && !empty(Auth::user('laundry_outlet')->merchant_id) ? Auth::user('laundry_outlet')->merchant_id : NULL;
            }
            $string_file = $this->getStringFile($merchant_id);
            $demo_special_permission = $this->demoSpecialPermission(NULL, $merchant_id);
            $view->with(['string_file' => $string_file,'is_demo' => $demo_special_permission['is_demo'],'edit_permission'=>$demo_special_permission['edit_permission'],'export_permission'=>$demo_special_permission['export_permission'],'delete_permission'=>$demo_special_permission['delete_permission'],'change_status_permission'=>$demo_special_permission['change_status_permission']]);
        });

        view()->composer('developer.layouts.sidebar', function ($view) {
            $merchant = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant') : Auth::user('merchant');
            $view->with(['merchant' => $merchant]);
        });

        view()->composer('developer.layouts.footer', function ($view) {
            $merchant = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant') : Auth::user('merchant');
            $view->with(['merchant' => $merchant]);
        });

        // for Laundry Outlets
        view()->composer('laundry-outlet.element.nav', function ($view) {
            $merchant_id = NULL;
            if(Auth::guard('laundry_outlet')->check())
            {
                $merchant_id = !empty(Auth::user('laundry_outlet')) && !empty(Auth::user('laundry_outlet')->merchant_id) ? Auth::user('laundry_outlet')->merchant_id : NULL;
            }
            $languages = Merchant::with('Language')->find($merchant_id);
            $view->with(['languages' => $languages->language]);
        });
    }
    public function register()
    {

    }
}
