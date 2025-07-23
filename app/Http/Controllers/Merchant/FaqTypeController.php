<?php

namespace App\Http\Controllers\Merchant;

use Auth;
use App;
use App\Models\FaqType;
use App\Models\LanguageFaqType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;

class FaqTypeController extends Controller
{
    use MerchantTrait;
    public function __construct()
    {
        $info_setting = App\Models\InfoSetting::where('slug', 'FAQ_TYPE')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
//        $checkPermission = check_permission(1, 'view_cms');
//        if ($checkPermission['isRedirect']) {
//            return $checkPermission['redirectBack'];
//        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $faq_types = FaqType::where([['merchant_id', '=', $merchant_id]])->latest()->paginate(25);
        return view('merchant.faq_types.index', compact('faq_types'));
    }

    public function create($id = null)
    {
        $merchant = get_merchant_id(false);

        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);
        $data = [];
        if (!empty($id)) {
            $data = FaqType::where('id',$id)->first();
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");
        } else {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title . ' ' . trans("$string_file.faq").' '.trans("$string_file.type");
        $return['faq_types'] = [
            'data' => $data,
            'submit_url' => route('merchant.faq_type.save', $id),
            'title' => $title,
            'submit_button' => $submit_button,
            'status' => get_status(true, $string_file)
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.faq_types.form')->with($return);
    }

    public function save(Request $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                Rule::unique('language_faq_types', 'title')->where(function ($query) use ($merchant_id, $id) {
                    return $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', App::getLocale()]])->where(function ($qq) use ($id) {
                        if (!empty($id)) {
                            $qq->where('faq_type_id', '!=', $id);
                        }
                    });
                })
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $faq_type = FaqType::find($id);
            } else {
                $faq_type = new FaqType;
                $faq_type->merchant_id = $merchant_id;
                $faq_type->status = 1;
            }



            $faq_type->save();
            $this->SaveLanguageFaqType($merchant_id, $faq_type->id, $request->title);
            DB::commit();
            return redirect()->route('merchant.faq_types')->withSuccess(trans("$string_file.saved_successfully"));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function SaveLanguageFaqType($merchant_id, $id, $title)
    {
        LanguageFaqType::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'faq_type_id' => $id
        ], [
            'title' => $title,
        ]);
    }

    public function ChangeStatus($id, $status)
    {
        $faq_type = FaqType::FindorFail($id);
        $string_file = $this->getStringFile(NULL, $faq_type->Merchant);
        if (!empty($faq_type->id)) :
            $faq_type->status = $status;
            $faq_type->save();
            return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
        else :
            return redirect()->back()->withSuccess(trans("$string_file.some_thing_went_wrong"));
        endif;
    }
}
