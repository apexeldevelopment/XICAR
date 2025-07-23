<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 17/3/23
 * Time: 9:13 AM
 */

namespace App\Http\Controllers\Merchant;
use App\Models\ApplicationConfiguration;
use App\Models\InfoSetting;
use App\Models\LangName;
use App\Models\Brand;
use App\Traits\ImageTrait;
use App\Traits\ProductTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use validator;
use View;
use App\Traits\MerchantTrait;


class BrandController extends Controller
{
    use ImageTrait, ProductTrait,MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','BRAND')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index(Request  $request)
    {
        $brand_name = $request->brand_name;
        $merchant_id = get_merchant_id();
        $all_food_grocery_clone = \App\Models\Segment::whereIn("sub_group_for_app",[1,2])->get()->pluck("slag")->toArray();
        $all_segments = array_merge(['TAXI','DELIVERY'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $permission_segments = get_permission_segments(1,true);
        $query = Brand::with(['Segment' => function($query) use($permission_segments){
            $query->whereIn('slag',$permission_segments);
        }])->whereHas("Segment",function($query) use($permission_segments){
            $query->whereIn('slag',$permission_segments);
        })
            ->where('merchant_id',$merchant_id)->where('delete','=',NULL);
        if(!empty($brand_name))
        {
            $query->with(['LangBrandSingle'=>function($q) use($brand_name,$merchant_id){
                $q->where('name',"LIKE","%$brand_name%")->where('merchant_id',$merchant_id);
            }])->whereHas('LangBrandSingle',function($q) use($brand_name,$merchant_id){
                $q->where('name',"LIKE","%$brand_name%")->where('merchant_id',$merchant_id);
            });
        }
        $all_brands = $query->paginate(15);
        $request->request->add(['merchant_id' => $merchant_id, 'segment_slug' => $permission_segments]);
        $brand['data'] =$all_brands;
        $brand['brand_name'] =$brand_name;
        $brand['search_route'] = route('merchant.brands');
        $brand['arr_search'] = $request->all();
        return view('merchant.brand.index')->with($brand);
    }

    public function add(Request $request, $id = NULL)
    {
        $all_food_grocery_clone = \App\Models\Segment::whereIn("sub_group_for_app",[1,2])->get()->pluck("slag")->toArray();
        $all_segments = array_merge(['TAXI','DELIVERY'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $brand = NULL;
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $pre_title = trans("$string_file.add");
        $save_url = route('merchant.brand.save');
        $arr_selected_segment = [];
        $is_demo = false;
        if (!empty($id)) {
            $brand = Brand::Find($id);
            $arr_selected_segment = array_pluck($brand->Segment,'id');
            if (empty($brand->id)) {
                return redirect()->back()->withErrors(trans("$string_file.data_not_found"));
            }
            $pre_title = trans("$string_file.edit");
            $save_url = route('merchant.brand.save', $id);
            $is_demo = $merchant->demo == 1 ? true : false;
        }
        $title = $pre_title.' '.trans("$string_file.brand");
        $arr_businesss = get_merchant_segment($with_taxi = true, null,$segment_group_id = 1);
        // If there is no category view then remove taxi and delivery segment
        $app_config = ApplicationConfiguration::where("merchant_id",$merchant_id)->first();
        if(isset($app_config->home_screen_view) && $app_config->home_screen_view != 1){
            if(isset($arr_businesss[1])){
                unset($arr_businesss[1]);
            }
            if(isset($arr_businesss[2])){
                unset($arr_businesss[2]);
            }
        }
        $arr_businesss = get_permission_segments(1, false, $arr_businesss);
        $segment_data['arr_segment'] = $arr_businesss;
        $segment_data['selected'] = $arr_selected_segment;
        $segment_html = View::make('segment')->with($segment_data)->render();
        $data['data'] = [
            'title' => $title,
            'save_url' => $save_url,
            'brand' => $brand,
            'segment_html'=>$segment_html,
            'arr_status'=>get_active_status("web",$string_file),
        ];
        $data['is_demo'] = $is_demo;
        return view('merchant.brand.form')->with($data);
    }

    /*Save or Update*/
    public function save(Request $request, $id = NULL)
    {
        $width = Config('custom.image_size.category.width');
        $height = Config('custom.image_size.category.height');
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $locale = \App::getLocale();
        $validator = Validator::make($request->all(), [
            'brand_name' => 'required',
            'brand_image' => 'sometimes|required|file|mimes:jpeg,png,jpg,gif,svg|dimensions:min_width='.$width.',min_height='.$height,
            'sequence' => 'required|integer',
            'status' => 'required',
            'segment'=>'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }

        $brand_name = DB::table('lang_names')->where(function ($query) use ($merchant_id,$locale,$id,$request) {
            return $query->where([['lang_names.merchant_id', '=', $merchant_id], ['lang_names.locale', '=', $locale], ['lang_names.name', '=', $request->brand_name]])
                ->where('lang_names.dependable_id','!=',$id);
        })->join("brands","lang_names.dependable_id","=","brands.id")
            ->where('brands.id','!=',$id)
            ->where('brands.merchant_id','=',$merchant_id)
            ->where('brands.delete',NULL)->first();

        if (!empty($brand_name->id)) {

            return redirect()->back()->withErrors(trans("$string_file.brand_name_already_exist"));
        }
        // Begin Transaction
        DB::beginTransaction();

        try {
            if (!empty($id)) {
                $brand = Brand::Find($id);
            } else {
                $brand = new Brand();
            }

            $merchant_id = get_merchant_id();
            $brand->sequence = $request->sequence;
            if (!empty($request->hasFile('brand_image'))) {
                $additional_req = ['compress'=>true,'custom_key'=>'category'];
                $brand->brand_image = $this->uploadImage('brand_image', 'brand',$merchant_id,'single',$additional_req);
            }
            $brand->status = $request->status;
            $brand->merchant_id = $merchant_id;
            $brand->save();

            // sync segment
            $brand->Segment()->sync($request->segment);

            // sync language of category
            $category_locale =  $brand->LangBrandSingle;
            if(!empty($category_locale->id))
            {
                $category_locale->name = $request->brand_name;
                $category_locale->save();
            }
            else
            {
                $language_data = new LangName([
                    'merchant_id' => $brand->merchant_id,
                    'locale' => $locale,
                    'name' => $request->brand_name]);

                $brand->LangBrand()->save($language_data);
//
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            DB::rollback();
            return redirect()->route('merchant.brands')->withErrors($message);
            // Rollback Transaction
        }
        // Commit Transaction
        DB::commit();
        return redirect()->route('merchant.brands')->withSuccess(trans("$string_file.brand_saved_successfully"));
    }
    public function destroy(Request $request)
    {
        $id = $request->id;
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        if($merchant->demo == 1)
        {
            echo trans("$string_file.demo_warning_message");
        }
        if(is_array($id)){
            $delete = Brand::whereIn('id',$id)->update(['delete' => 1]);
        }else{
            $delete = Brand::where('id',$id)->update(['delete' => 1]);
        }
    }

    public function updateStatus($id, $status)
    {
        $brand = Brand::FindorFail($id);
        $string_file = $this->getStringFile(NULL, $brand->Merchant);
        if (!empty($brand->id)):
            $brand->status = $status;
            $brand->save();
            return redirect()->route("merchant.brands")->withSuccess(trans("$string_file.saved_successfully"));
        else:
            return redirect()->route("merchant.brands")->withSuccess(trans("$string_file.some_thing_went_wrong"));
        endif;
    }
}
