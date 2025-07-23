<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\InfoSetting;
use App\Models\LanguageBusTraveller;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use App\Models\BusTraveller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use DB;

class BusTravellerController extends Controller
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
        // $checkPermission =  check_permission(1, 'bus_traveller_BUS_BOOKING');
        // if ($checkPermission['isRedirect']) {
        //     return  $checkPermission['redirectBack'];
        // }

        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $bus_travellers = BusTraveller::where('merchant_id', $merchant_id)->latest()->paginate(25);
        return view('merchant.bus-booking.bus_traveller.index', compact('bus_travellers'));
    }

    /**
     * Add Edit form of duration
     */
    public function add($id = null)
    {
        $merchant = get_merchant_id(false);

        $is_demo = $merchant->demo == 1;
        $string_file = $this->getStringFile(NULL, $merchant);
        $data = [];
        if (!empty($id)) {
            $data = BusTraveller::where('id',$id)->first();
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.update");
        } else {
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title . ' ' . trans("$string_file.bus_traveller");
        $return['bus_traveller'] = [
            'data' => $data,
            'submit_url' => route('bus_booking.save_traveller', $id),
            'title' => $title,
            'submit_button' => $submit_button,
            'status' => get_status(true, $string_file)
        ];
        $return['is_demo'] = $is_demo;
        return view('merchant.bus-booking.bus_traveller.form')->with($return);
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
            'name' => [
                'required',
                Rule::unique('language_bus_travellers', 'title')->where(function ($query) use ($merchant_id, $id) {
                    return $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', \App::getLocale()]])->where(function ($qq) use ($id) {
                        if (!empty($id)) {
                            $qq->where('bus_traveller_id', '!=', $id);
                        }
                    });
                })
            ],
            'image' => 'required_without:bus_traveller_id|file',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $bus_traveller = BusTraveller::find($id);
            } else {
                $bus_traveller = new BusTraveller;
                $bus_traveller->merchant_id = $merchant_id;
            }
            $bus_traveller->status = 1;
            if($request->hasFile("image")){
                $bus_traveller->image = $this->uploadImage('image', 'bus_traveller');
            }
            $bus_traveller->tax = $request->tax;
            $bus_traveller->tax_method = $request->tax_method;
            $bus_traveller->save();
            $this->SaveLanguageBusTraveller($merchant_id, $bus_traveller->id, $request->name, $request->description);
            DB::commit();
            return redirect()->route('bus_booking.traveller')->withSuccess(trans("$string_file.saved_successfully"));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function SaveLanguageBusTraveller($merchant_id, $id, $title, $description)
    {
        LanguageBusTraveller::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => \App::getLocale(), 'bus_traveller_id' => $id
        ], [
            'title' => $title,
            'description' => $description
        ]);
    }
}
