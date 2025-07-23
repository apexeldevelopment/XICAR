<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 30/11/23
 * Time: 11:47 AM
 */

namespace App\Http\Controllers\Merchant;


use App\Models\InfoSetting;
use App\Models\LanguageMerchantNavigationDrawer;
use App\Models\MerchantNavigationDrawer;
use App\Models\MerchantNavigationDrawerConfig;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;

class NavigationDrawerConfigController
{
    use ImageTrait,MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'NAVIGATION_DRAWER')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $merchant_id = get_merchant_id();
        $merchant_navigation_drawer = MerchantNavigationDrawer::with("MerchantNavigationDrawerConfig")->where("merchant_id", $merchant_id)->orderBy("sequence")->get();
        return view('merchant.navigation-drawer-config.index', compact('merchant_navigation_drawer'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try{
            $merchant_id = get_merchant_id();
            $string_file = $this->getStringFile($merchant_id);

            $existing_menus = MerchantNavigationDrawer::where("merchant_id", $merchant_id)->get()->pluck("id")->toArray();
            // dd($request->slab);
            foreach($request->slab as $item){
                if(isset($item['menu_id']) && !empty($item['menu_id'])){
                    $menu = MerchantNavigationDrawer::find($item['menu_id']);

                    if(!empty($existing_menus)){
                        if (($key = array_search($item['menu_id'], $existing_menus)) !== false) {
                            unset($existing_menus[$key]);
                        }
                    }
                }else{
                    $menu = new MerchantNavigationDrawer();
                    $menu->merchant_id = $merchant_id;
                }
                $menu->sequence = isset($item['menu_sequence']) ? $item['menu_sequence'] : 1;
                $menu->extra_data = isset($item['menu_extra_data']) ? $item['menu_extra_data'] : NULL;
                $menu->type = $item['menu_type'];
//                $menu->name = $item['menu_name'];
                if (isset($item['menu_icon']) && !empty($item['menu_icon'])):
                    $menu->icon = $this->uploadImage($item['menu_icon'], 'drawericons', $merchant_id, 'multiple');
                endif;
                $menu->save();
                $this->SaveLanguageMerchantNavigationDrawer($merchant_id, $menu->id, $item['menu_name']);

                $existing_sub_menus = MerchantNavigationDrawerConfig::where("merchant_navigation_drawer_id", $menu->id)->get()->pluck("id")->toArray();

                switch ($menu->type){
                    case "REDIRECT_URL":
                        $menu->value = $item['redirect_url'];
                        break;
                    case "APP_LOCATION":
                        $menu->value = $item['app_location'];
                        break;
                    case "CMS_PAGE":
                        $menu->value = $item['cms_page'];
                        break;
                    case "PARENT_MENU":
                        $menu->value = null;
                        foreach($item['sub_menu'] as $sub_menu_item){
                            if(isset($sub_menu_item['sub_menu_id']) && !empty($sub_menu_item['sub_menu_id'])){
                                $sub_menu = MerchantNavigationDrawerConfig::find($sub_menu_item['sub_menu_id']);

                                if(!empty($existing_sub_menus)){
                                    if (($key = array_search($sub_menu_item['sub_menu_id'], $existing_sub_menus)) !== false) {
                                        unset($existing_sub_menus[$key]);
                                    }
                                }
                            }else{
                                $sub_menu = new MerchantNavigationDrawerConfig();
                                $sub_menu->merchant_navigation_drawer_id = $menu->id;
                            }
                            $sub_menu->sequence = $sub_menu_item['menu_sequence'];
//                            $sub_menu->name = $sub_menu_item['menu_name'];
                            $sub_menu->type = $sub_menu_item['menu_type'];
                            $sub_menu->value = $sub_menu_item['menu_type_value'];
                            $sub_menu->save();
                            $this->SaveLanguageMerchantNavigationDrawerConfig($merchant_id, $sub_menu->id, $sub_menu_item['menu_name']);
                        }
                        if(!empty($existing_sub_menus)){
                            MerchantNavigationDrawerConfig::whereIn("id",$existing_sub_menus)->delete();
                        }
                        break;
                }
                $menu->save();
            }
            if(!empty($existing_menus)){
                MerchantNavigationDrawer::whereIn("id",$existing_menus)->delete();
            }
            DB::commit();
            return redirect()->route("navigation-drawer-config.index")->withSuccess(trans("$string_file.success"));
        }catch (\Exception $exception){
            DB::rollback();
            return redirect()->back()->withErrors($exception->getMessage());
        }
    }

    public function SaveLanguageMerchantNavigationDrawer($merchant_id, $m_n_d_id, $name)
    {
        LanguageMerchantNavigationDrawer::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'merchant_navigation_drawer_id' => $m_n_d_id
        ], [
            'name' => $name
        ]);
    }

    public function SaveLanguageMerchantNavigationDrawerConfig($merchant_id, $m_n_d_c_id, $name)
    {
        LanguageMerchantNavigationDrawer::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'merchant_navigation_drawer_config_id' => $m_n_d_c_id
        ], [
            'name' => $name
        ]);
    }
}
