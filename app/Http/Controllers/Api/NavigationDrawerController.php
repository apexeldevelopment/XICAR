<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 3/5/23
 * Time: 5:22 PM
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\MerchantTrait;
use App\Models\Merchant;
use App\Traits\ApiResponseTrait;
use App\Traits\ImageTrait;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NavigationDrawerController extends Controller
{
    use ApiResponseTrait, MerchantTrait, ImageTrait;

    public function getNavigationDrawer(Request $request){
        $request_fields = [
            'request_for' => 'required|in:USER,DRIVER'
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $merchant = Merchant::with("Configuration","ApplicationConfiguration")->find($request->merchant_id);
            $data = [];

            $arr_nevigation = [];
            $menu_options = [];
            if (count($merchant->NavigationDrawer) > 0) {
                foreach ($merchant->NavigationDrawer as $nevigation) {
                    $check_status = true;
                    //check wallet user enabled from merchant
                    if ($nevigation['slug'] == 'wallet-activity' && $merchant->Configuration->user_wallet_status != 1) {
                        $check_status = false;
                    }
                    //check favourite driver enabled from merchant
                    if ($nevigation['slug'] == 'favourite-driver' && $merchant->ApplicationConfiguration->favourite_driver_module != 1) {
                        $check_status = false;
                    }
                    //check SOS for user/driver enabled from merchant
                    if ($nevigation['slug'] == 'emergency-contacts' && $merchant->ApplicationConfiguration->sos_user_driver != 1) {
                        $check_status = false;
                    }
                    if ($check_status == true) {
                        $menu_options[$nevigation['slug']] = $nevigation['name'];
                        // exclude logout option
                        if ($nevigation['slug'] != "logout") {
                            $arr_nevigation[] = $this->getNavItem($nevigation['name'], $nevigation['slug']);
                        }
                    }
                }
            }
//                $header = array(
//                    "title" => "",
//                    "secondary_text" => "",
//                    "image" => "uploads/logo/2022/11/jcd7kn6b2fb8.png",
//                    "background_color" => "#ffffff",
//                    "text_color" => "#000000",
//                    "secondary_text_color" => "#000000"
//                );
//                $logout_button = array(
//                    "background_color" => "#ffffff",
//                    "text_color" => "#0a0a0a",
//                    "text_size" => "1rem",
//                    "logout_icon" => "bi bi-power",
//                    "icon_size" => "2rem",
//                    "icon_color" => "#0a0a0a",
//                    "button_text" => "Logout"
//                );
            $data = array(
                "id" => "",
                "name" => "menu",
                "merchant_id" => $merchant->id,
                "config" => json_decode($merchant->navigation_drawer_data),
//                        array(
//                        array(
//                            "header" => $header,
//                            "navItems" => $arr_nevigation,
//                            "drawer_background" => "#ffffff",
//                            "logout_button" => array_key_exists('logout',$menu_options) ? $logout_button : array()
//                        )
//                    ),
                "menu_options" => $menu_options
            );
            return $this->successResponse('Success',$data);
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage());
        }
    }

    public function saveNavigationDrawer(Request $request){
        $request_fields = [
            'request_for' => 'required|in:USER,DRIVER',
            'data' => 'required'
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $merchant = Merchant::find($request->merchant_id);
            $data = json_decode($request->data,true);
            if(isset($data['header']['image']) && !empty($data['header']['image'])){
                $request->merge(['business_logo' => $data['header']['image']]);
                $data['header']['image'] = $this->uploadBase64Image('business_logo', 'business_logo', $merchant->id);
            }
            $merchant->navigation_drawer_data = json_encode($data);
            $merchant->save();
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse('Success');
    }

    public function getNavItem($title, $action){
        return array(
            "uid" => "",
            "type" => "tab",
            "icon" => "bi bi-person-circle",
            "title" => $title,
            "text_color" => "#0a0a0a",
            "icon_color" => "#0a0a0a",
            "background_color" => "#ffffff",
            "screen_data" => "",
            "screen_name" => $action,
            "subMenu" => [],
            "toShowSubMenu" => false,
            "subMenutype" => "customlist"
        );
    }

    public function getNavigationDrawerConfig(Request $request){
        $request_fields = [
            'request_for' => 'required|in:USER,DRIVER'
        ];
        $validator = Validator::make($request->all(), $request_fields);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try {
            $data = [];
            $merchant = Merchant::find($request->merchant_id);
            if(isset($merchant->navigation_drawer_data) && !empty($merchant->navigation_drawer_data)){
                $navigation_drawer_data = json_decode($merchant->navigation_drawer_data, true);
//                $navigation_drawer_data = $navigation_drawer_data[0];
                $navItems = [];
                if(isset($navigation_drawer_data['header']) && !empty($navigation_drawer_data['header'])){
                    if(isset($navigation_drawer_data['header']['image'])){
                        $navigation_drawer_data['header']['image'] = get_image($navigation_drawer_data['header']['image'],'business_logo',$merchant->id);
                    }
                    array_push($navItems,array(
                        "drawer_name" => "DRAWER_HEADER",
                        "drawer_definition" => $navigation_drawer_data['header']
                    ));
                }
                if(isset($navigation_drawer_data['navItems'])){
                    foreach($navigation_drawer_data['navItems'] as $navigation_drawer_item){
//                        unset($navigation_drawer_item['uid']);
//                        unset($navigation_drawer_item['type']);
//                        unset($navigation_drawer_item['toShowSubMenu']);
                        $icon_string = $navigation_drawer_item['icon'];
                        $icon_name = explode(" ",$icon_string);
                        $icon = str_replace($icon_name[0],"",$icon_name[1]);
                        $navigation_drawer_item['icon'] = view_config_image("icons/$icon");
                        array_push($navItems,array(
                            "drawer_name" => "DRAWER_ITEMS_TILE",
                            "drawer_definition" => $navigation_drawer_item
                        ));
                    }
                }
                $data['drawer_backgroud'] = isset($navigation_drawer_data['drawer_background']) ? $navigation_drawer_data['drawer_background'] : "#ffffff";
                $data['data'] = $navItems;
                $data['logout_button'] = $navigation_drawer_data['logout_button'];
            }
            return $this->successResponse("Success",$data);
        }catch (\Exception $exception){
            return $this->failedResponse($exception->getMessage());
        }
    }
}
