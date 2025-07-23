<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\BusChatSupport;
use App\Models\InfoSetting;
use App\Models\LanguageBusChatSupport;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use DB;

class BusChatSupportController extends Controller
{
    //
    use MerchantTrait, ImageTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'bus_service')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $bus_chat_support = BusChatSupport::where('merchant_id', $merchant_id)->latest()->paginate(25);
        return view('merchant.bus-booking.bus_chat_support.index', compact('bus_chat_support'));
    }

    /**
     * Add Edit form of duration
     */
    public function add($id = null)
    {
        $merchant = get_merchant_id(false);

        $string_file = $this->getStringFile(NULL, $merchant);
        $data = [];
        if (!empty($id)) {
            $data = BusChatSupport::where('id',$id)->first();
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");
        } else {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title . ' ' . trans("$string_file.bus_chat_support");
        $return['bus_chat_support'] = [
            'data' => $data,
            'submit_url' => route('bus_booking.save_chat-support', $id),
            'page_title' => $title,
            'submit_button' => $submit_button,
            'status' => get_status(true, $string_file)
        ];
        return view('merchant.bus-booking.bus_chat_support.form')->with($return);
    }

    /***
     * Save/update function of duration
     */
    public function save(Request $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        $validator = \Validator::make($request->all(), [
            'title' => [
                'required',
                Rule::unique('language_bus_chat_supports', 'title')->where(function ($query) use ($merchant_id, $id) {
                    return $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', \App::getLocale()]])->where(function ($qq) use ($id) {
                        if (!empty($id)) {
                            $qq->where('bus_chat_support_id', '!=', $id);
                        }
                    });
                })
            ],
            'subtitle' => 'required',
            'chat_support' => 'required',
            'icon' => 'required_without:bus_chat_support_id|file',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $bus_chat_support = BusChatSupport::find($id);
            } else {
                $bus_chat_support = new BusChatSupport();
                $bus_chat_support->merchant_id = $merchant_id;
            }
            if($request->hasFile("icon")){
                $bus_chat_support->icon = $this->uploadImage('icon', 'bus_chat_support');
            }
            $bus_chat_support->chat_support = $request->chat_support;
            $bus_chat_support->type = $request->type;
            $bus_chat_support->save();
            $this->SaveLanguageBusChatSupport($merchant_id, $bus_chat_support->id, $request->title, $request->subtitle);
            DB::commit();
            return redirect()->route('bus_booking.chat-support')->withSuccess(trans("$string_file.saved_successfully"));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function destroy($id)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        try{
            $bus_chat_support = BusChatSupport::find($id);
            $bus_chat_support->delete();
        }
        catch (\Exception $e){
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.data_deleted_successfully"));


    }

    public function SaveLanguageBusChatSupport($merchant_id, $id, $title, $subtitle)
    {
        LanguageBusChatSupport::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => \App::getLocale(), 'bus_chat_support_id' => $id
        ], [
            'title' => $title,
            'subtitle' => $subtitle
        ]);
    }
}
