<?php

namespace App\Http\Controllers\Merchant;

use App\Models\AdvertisementBanner;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\BusinessSegment\Product;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\InfoSetting;
use App\Models\Merchant;
use App\Models\HomeScreenHolder;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class AdvertisementBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    use ImageTrait,MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','ADVERTISE_BANNER')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $checkPermission = check_permission(1, 'view_banner');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $arr_segment = get_merchant_segment();
        $status =  get_status(true,$string_file);
        $merchant_id = get_merchant_id();
        $permission_segments = get_permission_segments(1,true);
        $banners = AdvertisementBanner::where([['merchant_id', '=', $merchant_id], ['is_deleted', '=', NULL]])
            ->orderBy('sequence')
            ->orderBy('updated_at')
            ->paginate(10);
        return view('merchant.advertisement_banner.index', compact('banners','arr_segment','status'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id = null)
    {
        $merchant = get_merchant_id(false);
        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL,$merchant);
        $checkPermission = check_permission(1, 'add_banner');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $banner = null;
        $arr_business_segment = [];
        $home_screen_holders = [];
        $arr_segment = add_blank_option(get_merchant_segment(true,$merchant->id,array(1, 4)),trans("$string_file.select"));
        $arr_active_status =  get_active_status("web",$string_file);
        foreach ($merchant->HomeScreenHolder as $item) {
            // if (isset($item['slug']) && stripos($item['slug'], 'BOTTOM_BANNER') !== false) {
            //     $home_screen_holders[] = $item;
            // }

            $home_screen_holders = $item->where('slug','LIKE','%BANNER%')->whereNot('slug','BANNER')->get();
        }
        if($id != null){
            $banner = AdvertisementBanner::Find($id);
            if(empty($banner)){
                return redirect()->back()->with('error','Banner not found.');
            }
            if($banner->business_segment_id != ''){
                $arr_business_segment = BusinessSegment::where([['merchant_id','=', $banner->merchant_id],['segment_id','=',$banner->segment_id]])->pluck('full_name','id')->toArray();
                $arr_business_segment = add_blank_option($arr_business_segment,trans("$string_file.select"));
            }
        }
        $config = Configuration::where("merchant_id", $merchant->id)->first();
        // $action_types = add_blank_option(array("URL" => "Web Url","BUSINESS SEGMENT"=>"Open Business Segment","SEGMENT"=> "Open Segment"));
        $action_types = add_blank_option(array("URL" => "Web Url"));
        $categories = [];
        $products = [];
        if(isset($config->category_type_view) && $config->category_type_view == 1){
            $action_types['CATEGORY'] = "Category";
            $action_types['PRODUCT'] = "Product";
            $categories = [];

            $products_list = Product::where('merchant_id',$merchant->id)->where('delete','=',NULL)->get();
            foreach($products_list as $product){
                $products[$product->id] = $product->Name($merchant->id);
            }
            $categories_list = Category::where('merchant_id',$merchant->id)->where('delete','=',NULL)->get();
            foreach($categories_list as $category){
                $categories[$category->id] = $category->Name($merchant->id);
            }
        }

        $adv_banner = !empty($merchant->advertisement_banner) ? explode(',',$merchant->advertisement_banner) : [];
        $banner_for = [];
        if(in_array(1,$adv_banner)){
            $banner_for["1"] = "User";
        }
        if(in_array(2,$adv_banner)){
            $banner_for["2"] = "Driver";
        }
        if(in_array(1,$adv_banner) && in_array(2,$adv_banner)){
            $banner_for["4"] = "All";
        }
        return view('merchant.advertisement_banner.create', compact('banner','arr_segment','arr_active_status','arr_business_segment','is_demo','action_types','products','categories','banner_for','home_screen_holders'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id = null)
    {
        $width = Config('custom.image_size.banner.width');
        $height = Config('custom.image_size.banner.height');
        $merchant_id = get_merchant_id();
        $request->validate([
            'name' => ['required',
                Rule::unique('advertisement_banners', 'name')->where(function ($query) use ($merchant_id) {
                    return $query->where([['is_deleted', '=', NULL], ['merchant_id', '=', $merchant_id]]);
                })->ignore($id)],
            'image' => 'required_if:id,!=,null',
            'validity' => 'required',
            'activate_date' => 'required',
            'sequence' => 'required',
            'status' => 'required',
            'banner_for' => 'required',
            'home_screen' => 'required',
            'segment_id' => 'required_if:home_screen,==,2',
            'expire_date' => 'required_if:validity,==,2',
            // 'banner_type_id'=> 'required_if:home_screen,==,1',
        ],[
            'expire_date.required_if' => 'Expire date required',
            'image.dimensions' => 'Please upload valid size image'
        ]);
//        |image|mimes:jpeg,png,jpg,gif,svg|dimensions:min_width='.$width.',min_height='.$height
        DB::beginTransaction();
        try
        {
            $merchant = get_merchant_id(false);
            $merchant_id = $merchant->id;
            $string_file = $this->getStringFile(NULL,$merchant);
            $banner = AdvertisementBanner::updateOrCreate(['id' => $id],[
                'merchant_id' => $merchant_id,
                'name' => $request->name,
                'redirect_url' => isset($request->redirect_url) && $request->action_type == "URL" ? $request->redirect_url : NULL,
                'validity' => $request->validity,
                'activate_date' => $request->activate_date,
                'expire_date' => $request->expire_date,
                'sequence' => $request->sequence,
                'status' => $request->status,
                'home_screen' => $request->home_screen,
                'segment_id' => $request->segment_id,
                'business_segment_id' => $request->business_segment_id,
                'banner_for' => $request->banner_for,
                'action_type' => $request->action_type,
                'category_id' => isset($request->category_id) && $request->action_type == "CATEGORY" ? $request->category_id : NULL,
                'product_id' => isset($request->product_id) && $request->action_type == "PRODUCT" ? $request->product_id : NULL,
            ]);

            if (Schema::hasColumn('advertisement_banners', 'home_screen_holder_id')) {
                $banner->home_screen_holder_id = $request->home_screen == 1 ? $request->banner_type_id : 1;
            }
            if($request->hasFile('image')){
                $additional_req = ['compress'=>true,'custom_key'=>'banner'];
                $image = $this->uploadImage('image','banners',$merchant_id,'single',$additional_req);
                $banner->image = $image;
                $banner->save();
                $banner->fresh();

                $imagesize = @getimagesize(get_image($banner->image,'banners',$merchant_id));
                $banner->image_height = isset($imagesize) && is_array($imagesize)  ? $imagesize[1] : null;
                $banner->image_width = isset($imagesize) && is_array($imagesize)  ? $imagesize[0] : null;
                $banner->save();
            }else{
                $banner->save();
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        // Commit Transaction
        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.banner_saved_successfully"));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function ChangeStatus($id, $status)
    {
        $validator = Validator::make(
            [
                'id' => $id,
                'status' => $status,
            ],
            [
                'id' => ['required'],
                'status' => ['required', 'integer', 'between:1,2'],
            ]);
        if ($validator->fails()) {
            return redirect()->back();
        }
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        if($merchant->demo == 1)
        {
            return redirect()->back()->withErrors(trans("$string_file.demo_warning_message"));
        }
        $banner = AdvertisementBanner::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $banner->status = $status;
        $banner->save();
        return redirect()->back()->withSuccess(trans("$string_file.status_updated"));
    }

    public function Delete(Request $request)
    {
        $checkPermission = check_permission(1, 'delete_banner');
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $id=$request->id;
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $banner = AdvertisementBanner::where([['merchant_id', '=', $merchant_id]])->FindorFail($id);
        if (!empty($banner)):
            $banner->is_deleted = 1;
            $banner->save();
            echo trans("$string_file.deleted_successfully");
        endif;
    }
}
