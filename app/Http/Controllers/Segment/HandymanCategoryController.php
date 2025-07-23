<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 13/7/23
 * Time: 4:48 PM
 */

namespace App\Http\Controllers\Segment;

use App\Models\InfoSetting;
use App;
use App\Models\HandymanCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use DB;
use App\Traits\ImageTrait;

class HandymanCategoryController extends Controller
{
    use MerchantTrait, ImageTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'HANDYMAN_CATEGORY')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $merchant_id = get_merchant_id();
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY', 'TAXI', 'HANDYMAN'], $all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $permission_segments = get_permission_segments(1, true);
        $handyman_categories = HandymanCategory::whereHas('Segment', function ($query) use ($permission_segments) {
            $query->whereIn('slag', $permission_segments);
        })->where([['merchant_id', '=', $merchant_id]])->paginate(15);
        $merchant_segments = get_permission_segments(1, false, get_merchant_segment());
        return view('merchant.handyman-category.index', compact('handyman_categories', 'merchant_segments'));
    }

    public function add(Request $request, $id = null)
    {
        $all_grocery_clone = ['HANDYMAN'];
        $checkPermission = check_permission(1, $all_grocery_clone, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $segment_group_id = 2;
        $handyman_category = NULL;
        $selected_service_types = [];
        $service_types = [];
        if (!empty($id)) {
            $handyman_category = HandymanCategory::findorfail($id);
            $selected_service_types = $handyman_category->ServiceTypes->pluck("id")->toArray();
            $request->merge(["segment_id" => $handyman_category->segment_id, "calling_for" => "list"]);
            $ajax = new App\Http\Controllers\Helper\AjaxController();
            $service_types = $ajax->getMerchantSegmentServices($request);
            $submit_button = trans("$string_file.update");
        } else {
            $submit_button = trans("$string_file.save");
        }

        $arr_segment = get_merchant_segment(false, $merchant->id, $segment_group_id);
        $arr_segment = get_permission_segments(1, false, $arr_segment);
        $data = [
            'handyman_category' => $handyman_category,
            'submit_button' => $submit_button,
            'arr_segment' => $arr_segment,
            'arr_status' => get_active_status("web", $string_file),
            'service_types' => $service_types,
            'selected_service_types' => $selected_service_types
        ];
        return view('merchant.handyman-category.form', compact('merchant', 'data'));
    }

    public function save(Request $request, $id = NULL)
    {
        $all_grocery_clone = ['HANDYMAN'];
        $checkPermission = check_permission(1, $all_grocery_clone, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = $merchant->id;
        $segment_id = $request->segment_id;
        $validator = Validator::make($request->all(), [
            'segment_id' => 'required',
            'status' => 'required',
            'category' => 'required',
            'description' => 'required',
            'icon' => 'required_if:id,!=,null|image|mimes:jpeg,png,jpg,gif,svg',
            'service_types.*' => 'required|exists:service_types,id'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        //
        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $handyman_category = HandymanCategory::find($id);
            } else {
                $handyman_category = new HandymanCategory();
                $handyman_category->merchant_id = $merchant_id;
                $handyman_category->segment_id = $segment_id;
            }
            $handyman_category->status = $request->status;
            if(!empty($request->hasFile('icon'))){
                $handyman_category->icon = $this->uploadImage("icon", 'category', $merchant_id);
            }
            $handyman_category->save();
            $handyman_category->ServiceTypes()->sync($request->service_types);

            $this->SaveLanguage($merchant_id, $handyman_category->id, $request->category, $request->description);
        } catch (\Exception $e) {
            DB::rollback();
            p($e->getMessage());
            return redirect()->back()->withInput($request->input())->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->route('segment.handyman-category')->withSuccess(trans("$string_file.added_successfully"));
    }


    public function SaveLanguage($merchant_id, $handyman_category_id, $category, $description)
    {
        App\Models\LanguageHandymanCategory::updateOrCreate([
            'locale' => \Illuminate\Support\Facades\App::getLocale(), 'handyman_category_id' => $handyman_category_id
        ], [
            'category' => $category,
            'description' => $description
        ]);
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
            ]
        );
        if ($validator->fails()) {
            return redirect()->back();
        }
        $merchant_id = get_merchant_id();
        $handyman_category = HandymanCategory::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
        $handyman_category->status = $status;
        $handyman_category->save();
        return redirect()->route('merchant.handyman-category');
    }
}
