<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Country;
use App\Models\Driver;
use App\Models\LanguageFaq;
use App\Models\Onesignal;
use App\Models\User;
use Auth;
use App;
use App\Models\Faq;
use App\Models\FaqType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;

class FaqController extends Controller
{
    use MerchantTrait;
    public function __construct()
    {
        $info_setting = App\Models\InfoSetting::where('slug', 'FAQ')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
//        $checkPermission = check_permission(1, 'view_cms');
//        if ($checkPermission['isRedirect']) {
//            return $checkPermission['redirectBack'];
//        }
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $faqs = Faq::with('FaqType')->where([['merchant_id', '=', $merchant_id]])->latest()->paginate(25);
        return view('merchant.faq.index', compact('faqs'));
    }

    public function create($id = null)
    {
        $merchant = get_merchant_id(false);

        $is_demo = $merchant->demo == 1 ? true : false;
        $string_file = $this->getStringFile(NULL, $merchant);
        $data = [];
        if (!empty($id)) {
            $data = Faq::where('id',$id)->first();
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");
        } else {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title . ' ' . trans("$string_file.faq");
        $faq_types = FaqType::where([['merchant_id', '=', $merchant->id],['status','=',1]])->latest()->get();
        $return['faq'] = [
            'data' => $data,
            'submit_url' => route('merchant.faq.save', $id),
            'title' => $title,
            'submit_button' => $submit_button,
            'status' => get_status(true, $string_file),
            'faq_types' => $faq_types,
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.faq.form')->with($return);
    }

    public function save(Request $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = Validator::make($request->all(), [
            'question' => [
                'required',
                Rule::unique('language_faqs', 'question')->where(function ($query) use ($merchant_id, $id) {
                    return $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', App::getLocale()]])->where(function ($qq) use ($id) {
                        if (!empty($id)) {
                            $qq->where('faq_id', '!=', $id);
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
                $faq = Faq::find($id);
            } else {
                $faq = new Faq;
                $faq->merchant_id = $merchant_id;
                $faq->application = $request->application;
                $faq->faq_type_id = $request->faq_type;
                $faq->status = 1;
            }

            $faq->save();
            $this->SaveLanguageFaq($merchant_id, $faq->id, $request->question, $request->answer);
            DB::commit();
            return redirect()->route('merchant.faq')->withSuccess(trans("$string_file.saved_successfully"));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function SaveLanguageFaq($merchant_id, $id, $question, $answer)
    {
        LanguageFaq::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'faq_id' => $id
        ], [
            'question' => $question,
            'answer' => $answer,
        ]);
    }

    public function ChangeStatus($id, $status)
    {
        $faq = Faq::FindorFail($id);
        $string_file = $this->getStringFile(NULL, $faq->Merchant);
        if (!empty($faq->id)) :
            $faq->status = $status;
            $faq->save();
            return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
        else :
            return redirect()->back()->withSuccess(trans("$string_file.some_thing_went_wrong"));
        endif;
    }
}
