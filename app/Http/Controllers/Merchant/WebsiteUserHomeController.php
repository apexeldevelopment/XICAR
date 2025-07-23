<?php

namespace App\Http\Controllers\Merchant;

use App\Models\WebSiteHomePage;
use App\Models\WebsiteFeature;
use App\Models\WebsiteFeatureTranslation;
use App\Models\WebSiteHomePageTranslation;
use App\Models\WebsiteFeaturesComponents;
use App\Models\WebsiteFeaturesComponentsTranslation;
use Illuminate\Http\Request;
use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use DB;

class WebsiteUserHomeController extends Controller
{
    use ImageTrait,MerchantTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $checkPermission =  check_permission(1,'website_user_home');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);

        // total segments of merchant
       $arr_segments = $merchant->Segment;
       $total_segments = $arr_segments->count();

        $merchant_id = $merchant->id;
        ///p($merchant_id);
//        if($features = WebsiteFeature::where([['merchant_id',$merchant_id],['application','USER']])->limit('3')->get()->isEmpty()):
//            for($i = 0; $i<3; $i++){
//                $update_create = WebsiteFeature::Create([
//                    'merchant_id' => $merchant_id,
//                    'application'=>'USER'
//                ], [
//                    'feature_image' => null,
//                ]);
//            }
//        endif;
        $arr_component = [];
        $features_component = WebsiteFeaturesComponents::where([['merchant_id',$merchant_id],['application','USER']])->get();
        foreach ($features_component as $component)
        {
//            p($component);
            $arr_component[$component->segment_id] = [
                'id'=>$component->id,
                'banner_image' => !empty($component->banner_image) ? get_image($component->banner_image,'website_images') : NULL,
                'banner_title'=>$component->FeatureTitle,
                'banner_description'=>$component->FeatureDescription,
                ];
        }



//        $features_component = WebsiteFeaturesComponents::where([['merchant_id',$merchant_id],['application','USER']])->limit('5')->get();
//         p($features_component);
        $details = WebSiteHomePage::where([['merchant_id',$merchant_id]])->first();
        $features = WebsiteFeature::where([['merchant_id',$merchant_id],['application','USER']])->first();
        $add_status = true;
        $arr_feature_banner = NULL;
        $arr_feature_footer_right = NULL;
        $arr_feature_footer_left = NULL;




        if(!empty($details->id))
        {
            $add_status = false;
          $arr_feature_banner = !empty($details->WebsiteFeature->Banner) ? json_decode($details->WebsiteFeature->Banner,true) :[];
//          p($arr_feature_banner['title']);
          $arr_feature_footer_left = !empty($details->WebsiteFeature->FooterLeft) ? json_decode($details->WebsiteFeature->FooterLeft,true) :[];

          $arr_feature_footer_right = !empty($details->WebsiteFeature->FooterRight) ? json_decode($details->WebsiteFeature->FooterRight,true) :[];
//            p($arr_feature_footer_right );
        }
        $string_file = $this->getStringFile($merchant_id);
//        p($string_file);
        return view('merchant.website.user_headings',compact('details','features','arr_feature_banner','arr_feature_footer_left','arr_feature_footer_right','add_status','total_segments','arr_segments','merchant_id','arr_component'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$id = NULL)
    {
       // p($request->segment_banner_image[12]);
        $checkPermission =  check_permission(1,'website_user_home');
        if ($checkPermission['isRedirect']){
            return  $checkPermission['redirectBack'];
        }
        $locale = \App::getLocale();
        $merchant= get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);

        Validator::make($request->all(),[
            'bg_color_primary' => 'required',
            'bg_color_secondary' => 'required',
            'text_color_primary' => 'required',
            'text_color_secondary' => 'required',
//            'banner' => 'required',
        ],[

        ])->validate();

        DB::beginTransaction();
        try{
            $website = WebSiteHomePage::where([['merchant_id','=',$merchant_id]])->first();
            if(empty($website))
            {
                $website = new WebSiteHomePage;
                $website->merchant_id = $merchant_id;

                $website_feature = new WebsiteFeature;
                $website_feature->merchant_id = $merchant_id;
            }
            else
            {
                $website_feature = $website->WebsiteFeature;
            }
            $image = null;
            if($request->file('banner_image')):
                $image = $this->uploadImage('banner_image','website_images');
                $website->user_banner_image = $image;
            endif;
            if($request->hasFile('app_logo') && $request->file('app_logo')):
                $logo = $this->uploadImage('app_logo','website_images');
                $website->logo = $logo;
            endif;
            if($request->hasFile('footer_logo') && $request->file('footer_logo')):
                $logo = $this->uploadImage('footer_logo','website_images');
                $website->footer_logo = $logo;
            endif;
            if ($request->hasFile("image_1") && $request->file('image_1')) :
                $image_1 = $this->uploadImage("image_1", "website_images");
                $website->home_page_image_1 = $image_1;
            endif;
            if ($request->hasFile("image_2") && $request->file('image_2')) :
                $image_2 = $this->uploadImage("image_2", "website_images");
                $website->home_page_image_2 = $image_2;
            endif;

            if ($request->hasFile("icon_1") && $request->file('icon_1')) :
                $icon_1 = $this->uploadImage("icon_1", "website_images");
                $website->home_page_icon_1 = $icon_1;
            endif;
            if ($request->hasFile("icon_2") && $request->file('icon_2')) :
                $icon_2 = $this->uploadImage("icon_2", "website_images");
                $website->home_page_icon_2 = $icon_2;
            endif;
            if ($request->hasFile("icon_3") && $request->file('icon_3')) :
                $icon_3 = $this->uploadImage("icon_3", "website_images");
                $website->home_page_icon_3 = $icon_3;
            endif;
            if ($request->hasFile("icon_4") && $request->file('icon_4')) :
                $icon_4 = $this->uploadImage("icon_4", "website_images");
                $website->home_page_icon_4 = $icon_4;
            endif;
            if ($request->hasFile("qr_image_1") && $request->file('qr_image_1')) :
                $qr_image_1 = $this->uploadImage("qr_image_1", "website_images");
                $website->home_page_qr_image_1 = $qr_image_1;
            endif;
            if ($request->hasFile("qr_image_2") && $request->file('qr_image_2')) :
                $qr_image_2 = $this->uploadImage("qr_image_2", "website_images");
                $website->home_page_qr_image_2 = $qr_image_2;
            endif;

            if ($request->hasFile("extra_image_1") && $request->file('extra_image_1')) :
                $extra_image_1 = $this->uploadImage("extra_image_1", "website_images");
                $website->extra_image_1 = $extra_image_1;
            endif;

            if ($request->hasFile("extra_image_2") && $request->file('extra_image_2')) :
                $extra_image_2 = $this->uploadImage("extra_image_2", "website_images");
                $website->extra_image_2 = $extra_image_2;
            endif;
            // $website->home_page_icon_heading = $request->icon_heading;
            // $website->home_page_advert_header = $request->ad_heading;
            // $website->home_page_advert_content = $request->ad_content;
            
            
            // $website->home_page_icon_content_1 = $request->icon_1_heading;
            // $website->home_page_icon_content_2 = $request->icon_2_heading;
            // $website->home_page_icon_content_3 = $request->icon_3_heading;
            // $website->home_page_icon_content_4 = $request->icon_4_heading;
            

            if ($request->hasFile("feature_description_image") && $request->file('feature_description_image')) :
                $feature_description_image = $this->uploadImage("feature_description_image", "website_images");
                $website->feature_description_image = $feature_description_image;
            endif;
            
            
            // user login bg image
            if ($request->user_login_bg_image) {
                $user_login_bg_image = $this->uploadImage('user_login_bg_image' , 'website_images');
                $website->user_login_bg_image = $user_login_bg_image;
            }

            $website->bg_color_primary =  $request->bg_color_primary;
            $website->bg_color_secondary =  $request->bg_color_secondary;
            $website->text_color_primary =  $request->text_color_primary;
            $website->text_color_secondary =  $request->text_color_secondary;
            
            
            $website->android_user_url_link = $request->android_user_link;
            $website->android_driver_url_link = $request->android_driver_link;
            $website->ios_user_url_link = $request->ios_user_link;
            $website->ios_driver_url_link = $request->ios_driver_link;
            $website->save();
            
            $website->fresh();
            $websitehomedata = [
                'home_page_icon_heading' => $request->icon_heading,
                'home_page_advert_header' => $request->ad_heading,
                'home_page_advert_content' => $request->ad_content,

                'home_page_icon_content_1' => $request->icon_1_heading,
                'home_page_icon_content_2' => $request->icon_2_heading,
                'home_page_icon_content_3' => $request->icon_3_heading,
                'home_page_icon_content_4' => $request->icon_4_heading,

                'feature_details' => $request->feature_details,
                
                'service_heading' => $request->service_heading,
                'features_heading' => $request->features_heading,

                'bottom_about_us_heading' => $request->bottom_about_us_heading,
                'bottom_services_heading' => $request->bottom_services_heading,
                'bottom_privacy_policy_heading' => $request->bottom_privacy_policy_heading,
                'bottom_contact_us_heading' => $request->bottom_contact_us_heading,
                'bottom_terms_and_ser_heading' => $request->bottom_terms_and_ser_heading,

                'login_text' => $request->login_text,
                'signup_text' => $request->signup_text,
                
                'android_user_link_text' => $request->android_user_link_text,
                'android_driver_link_text' => $request->android_driver_link_text,
                'ios_user_link_text' => $request->ios_user_link_text,
                'ios_driver_link_text' => $request->ios_driver_link_text,
                
                
                'additional_header_1'=>$request->additional_header_1,
                'additional_header_content_1'=>$request->additional_header_content_1,
                'additional_header_2'=>$request->additional_header_2,
                'additional_header_content_2'=>$request->additional_header_content_2,

                'extra_content_1'=>$request->extra_content_1,
                'extra_content_2'=>$request->extra_content_2,
                'extra_content_3'=>$request->extra_content_3,
                'extra_content_4'=>$request->extra_content_4,
            ];
            $this->SaveLanguageWebSiteHomePage($website->id, $locale, $websitehomedata);

            $website_feature->web_site_home_page_id = $website->id;
            $website_feature->application = "USER";
            $website_feature->save();
            

            $this->SaveLanguageHomeWebsiteFeatures($request, $website_feature,$locale);

            // feature component
            $arr_segment = $request->arr_segment_id;
            foreach($arr_segment as $segment_id => $segment):
            $feature_component = WebsiteFeaturesComponents::where('segment_id',$segment_id)->where('web_site_home_page_id',$website->id)->first();
               if(empty($feature_component))
               {
                   $feature_component = new WebsiteFeaturesComponents;
               }
                $arr_banner_image = isset($request->segment_banner_image[$segment_id]) ? $request->segment_banner_image[$segment_id] : "";
                if($arr_banner_image):
                    $image = $this->uploadImage($arr_banner_image,'website_images',$merchant_id,'multiple');
                    $feature_component->banner_image = $image;
                    $feature_component->application = "USER";
                    $feature_component->segment_id = $segment_id;
                    $feature_component->merchant_id = $merchant_id;
                    $feature_component->web_site_home_page_id = $website->id;
                    $feature_component->save();
//                    p($feature_component);
                endif;
            $banner_title = isset($request->segment_banner_title[$segment_id]) ? $request->segment_banner_title[$segment_id] : "";
            $banner_description = isset($request->segment_banner_description[$segment_id]) ? $request->segment_banner_description[$segment_id] : "";
            $this->SaveLanguageHomeWebsiteFeatureComponent($banner_title,$banner_description, $feature_component,$locale);
            endforeach;


        }catch (\Exception $e)
        {
//            p($e->getMessage(),0);
//            p($e->getTraceAsString());
            DB::rollback();
          return redirect()->back()->withErrors($e->getMessage());
        }
//        p('end');
        DB::commit();

        return redirect()->route('website-user-home-headings.index')->withSuccess(trans("$string_file.saved_successfully"));
    }


    public function SaveLanguageHomeWebsiteFeatures($request, $website_feature,$locale)
    {

        WebsiteFeatureTranslation::updateOrCreate([
            'website_feature_id' => $website_feature->id, 'locale' => $locale
        ], [
            'app_title' => $request->app_title,
            'footer_title' => $request->footer_title,
            'banner' => !empty($request->banner) ? json_encode($request->banner) : json_encode([]),
            'footer_left_content' => !empty($request->footer_left_content) ? json_encode($request->footer_left_content) : json_encode([]),
            'footer_right_service' => !empty($request->service_content) ? json_encode($request->service_content) : json_encode([]),
        ]);
    }
    public function SaveLanguageHomeWebsiteFeatureComponent($banner_title,$banner_description, $feature_component,$locale)
    {

//        p($feature_component);
        WebsiteFeaturesComponentsTranslation::updateOrCreate(
            ['website_features_components_id' => $feature_component->id,'locale'=>$locale
            ], [
            'website_features_components_id' => $feature_component->id,
            'banner_title' => $banner_title,
            'banner_description'=>$banner_description,
            'locale'=>$locale
            ]
        );
    }

//    public function store(Request $request)
//    {
//        $checkPermission =  check_permission(1,'website_user_home');
//        if ($checkPermission['isRedirect']){
//            return  $checkPermission['redirectBack'];
//        }
//        //dd($request['data']);
//        $locale = \App::getLocale();
//        $merchant_id = get_merchant_id();
//
//        Validator::make($request->all(),[
//            'start_address_hint' => 'required',
//            'end_address_hint' => 'required',
//            'book_btn_title' => 'required',
//            'estimate_btn_title' => 'required',
//            'estimate_description' => 'required',
//            /*'features.*' => ['required',
//                Rule::exists('website_features','id')->where('merchant_id',$merchant_id)],*/
//        ],[
//            //'features.*.exists' => 'Some Data Invalid',
//        ])->validate();
//
//        $features_component = WebsiteFeaturesComponents::where([['merchant_id',$merchant_id]])->orderBy('position')->get();
//        //dd($request['data']);
//        foreach($request['data'] as $key => $image){
//            if($image){
//                if(isset($image['featre_compnt_image'])){
//                    $app_image = $this->uploadImage($image['featre_compnt_image'],'website_images',$merchant_id,'multiple');
//                }
//                else{ $app_image = ''; }
//                if($app_image != ""){ $app_image=$app_image; }else{ $app_image = $features_component[$key]['feature_image']; }
//                $update_img = WebsiteFeaturesComponents::updateOrCreate(['merchant_id' => $merchant_id,'position'=>$key], ['feature_image' => $app_image,'application'=>'USER','align'=>1]);
//                $inrt_id = $update_img->id;
//                $locale = \App::getLocale();
//                $update_detil = WebsiteFeaturesComponentsTranslation::updateOrCreate(['website_features_components_id' => $inrt_id,'locale'=>$locale], ['title' => $image['featre_compnt_title'],'description'=>$image['featre_compnt_description'],'locale'=>$locale]);
//            }
//        }
//
//        $android_link = $request->android_link;
//        $ios_link = $request->ios_link;
//        $image = null;
//        if($request->file('banner_image')):
//            $image = $this->uploadImage('banner_image','website_images');
//        else:
//            $update = WebSiteHomePage::where([['merchant_id',$merchant_id]])->first();
//            if(!empty($update)):
//                $image = $update['user_banner_image'];
//            endif;
//        endif;
//        if($request->hasFile('app_logo') && $request->file('app_logo')):
//            $logo = $this->uploadImage('app_logo','website_images');
//        else:
//            $updatelogo = WebSiteHomePage::where([['merchant_id',$merchant_id]])->first();
//            if(!empty($updatelogo)):
//                $logo = $updatelogo['logo'];
//            endif;
//        endif;
//        $websiteHomePage = WebSiteHomePage::where('merchant_id' , $merchant_id)->first();
//        if ($request->estimate_image) {
//            $estimate_image = $this->uploadImage('estimate_image' , 'website_images');
//        }
//        else {
//            $estimate_image = $websiteHomePage['user_estimate_image'];
//        }
//
//        // featured component main image
//        if ($request->featured_component_main_image) {
//            $featured_component_main_image = $this->uploadImage('featured_component_main_image' , 'website_images');
//        }
//        else {
//            $featured_component_main_image = $websiteHomePage['featured_component_main_image'];
//        }
//
//        // user login bg image
//        if ($request->user_login_bg_image) {
//            $user_login_bg_image = $this->uploadImage('user_login_bg_image' , 'website_images');
//        }
//        else {
//            $user_login_bg_image = $websiteHomePage['user_login_bg_image'];
//        }
//        $update = WebSiteHomePage::updateOrCreate(['merchant_id' => $merchant_id,], [
//            'user_banner_image' => $image,
//            'logo'=>$logo,
//            'android_link'=>$android_link,
//            'ios_link'=>$ios_link,
//            'user_estimate_image' => $estimate_image,
//            'featured_component_main_image' => $featured_component_main_image,
//            'footer_bgcolor' => $request->footer_bg_color,
//            'footer_text_color' => $request->footer_text_color,
//            'user_login_bg_image' => $user_login_bg_image
//        ]);
//        $image = null;
//        foreach($request->features as $key => $value):
//            $update_data = WebsiteFeature::findorfail($key);
//            $update_lang_data = $value;
//            $this->SaveLanguageHomeWebsiteFeatures(collect($update_lang_data), $update_data);
//        endforeach;
//        $update_lang_data = $request->only(['start_address_hint', 'end_address_hint', 'book_btn_title', 'estimate_btn_title', 'estimate_description']);
//        $this->SaveLanguageHomePage(collect($update_lang_data), $update);
//        return redirect()->back();
//    }

//    public function SaveLanguageHomeWebsiteFeatures(Collection $collection, WebsiteFeature $webfeature_data)
//    {
//        $collect_lang_data = $collection->toArray();
//        WebsiteFeatureTranslation::updateOrCreate([
//            'website_feature_id' => $webfeature_data['id'], 'locale' => \App::getLocale()
//        ], [
//            'title' => $collect_lang_data['title'],
//            'description' => $collect_lang_data['description'],
//        ]);
//    }

    public function SaveLanguageHomePage(Collection $collection, WebSiteHomePage $webhome_data)
    {
        $collect_lang_data = $collection->toArray();
        WebSiteHomePageTranslation::updateOrCreate([
            'web_site_home_page_id' => $webhome_data['id'], 'locale' => \App::getLocale()
        ], [
            'start_address_hint' => $collect_lang_data['start_address_hint'],
            'end_address_hint' => $collect_lang_data['end_address_hint'],
            'book_btn_title' =>$collect_lang_data['book_btn_title'],
            'estimate_btn_title' =>$collect_lang_data['estimate_btn_title'],
            'estimate_description'=>$collect_lang_data['estimate_description'],
        ]);
    }
    
    public function SaveLanguageWebSiteHomePage($web_site_home_page_id, $locale, $websitehomedata)
    {
        WebSiteHomePageTranslation::updateOrCreate(
            [
                'web_site_home_page_id' => $web_site_home_page_id, 'locale' => $locale
            ],
            [
                'home_page_icon_heading' => $websitehomedata['home_page_icon_heading'],
                'home_page_advert_header' => $websitehomedata['home_page_advert_header'],
                'home_page_advert_content' => $websitehomedata['home_page_advert_content'],

                'home_page_icon_content_1' => $websitehomedata['home_page_icon_content_1'],
                'home_page_icon_content_2' => $websitehomedata['home_page_icon_content_2'],
                'home_page_icon_content_3' => $websitehomedata['home_page_icon_content_3'],
                'home_page_icon_content_4' => $websitehomedata['home_page_icon_content_4'],

                'feature_details' => $websitehomedata['feature_details'],
                                'service_heading' => $websitehomedata['service_heading'],
                'features_heading' => $websitehomedata['features_heading'],

                'bottom_about_us_heading' => $websitehomedata['bottom_about_us_heading'],
                'bottom_services_heading' => $websitehomedata['bottom_services_heading'],
                'bottom_privacy_policy_heading' => $websitehomedata['bottom_privacy_policy_heading'],
                'bottom_contact_us_heading' => $websitehomedata['bottom_contact_us_heading'],
                'bottom_terms_and_ser_heading' => $websitehomedata['bottom_terms_and_ser_heading'],

                'login_text' => $websitehomedata['login_text'],
                'signup_text' => $websitehomedata['signup_text'],
                
                'android_user_link_text' => $websitehomedata['android_user_link_text'],
                'android_driver_link_text' => $websitehomedata['android_driver_link_text'],
                'ios_user_link_text' => $websitehomedata['ios_user_link_text'],
                'ios_driver_link_text' => $websitehomedata['ios_driver_link_text'],
                
                
                'additional_header_1'=>$websitehomedata['additional_header_1'],
                'additional_header_content_1'=>$websitehomedata['additional_header_content_1'],
                'additional_header_2'=>$websitehomedata['additional_header_2'],
                'additional_header_content_2'=>$websitehomedata['additional_header_content_2'],

                'extra_content_1'=>$websitehomedata['extra_content_1'],
                'extra_content_2'=>$websitehomedata['extra_content_2'],
                'extra_content_3'=>$websitehomedata['extra_content_3'],
                'extra_content_4'=>$websitehomedata['extra_content_4'],
            ]
        );
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
}
