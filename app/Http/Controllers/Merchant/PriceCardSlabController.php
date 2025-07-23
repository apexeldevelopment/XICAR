<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 16/5/23
 * Time: 6:18 PM
 */
namespace App\Http\Controllers\Merchant;

use App\Models\ApplicationConfiguration;
use App\Models\CarpoolingPriceCardCancelCharge;
use App\Models\Configuration;
use App\Models\CountryArea;
use App\Models\InfoSetting;
use App\Models\PriceCardDetail;
use App\Models\PriceCardSlab;
use App\Models\PriceCardSlabDetail;
use App\Models\Segment;
use App\Models\ServicePackage;
use App\Models\OutstationPackage;
use App\Models\ExtraCharge;
use App\Models\Merchant;
use App\Models\PriceCardCommission;
use App\Traits\AreaTrait;
use App\Traits\PriceTrait;
use App\Traits\MerchantTrait;
use Auth;
use App\Models\PriceCardValue;
use App\Models\PricingParameter;
use App\Models\PriceCard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use View;
use DB;
use App\Http\Controllers\Helper\AjaxController;

class PriceCardSlabController extends Controller
{
    use AreaTrait, PriceTrait, MerchantTrait;

    public function index(Request $request)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $area_id = null;
        if (isset($request->area) && !empty($request->area)) {
            $area_id = $request->area;
        }
        $price_card_slabs = PriceCardSlab::where("merchant_id", $merchant_id)->latest()->paginate();
        $areas = $this->getMerchantCountryArea($this->getAreaList(false, true)->get(), 1, 1,$string_file);
        $data = [];
        return view('merchant.price_card_slab.index', compact('price_card_slabs',"areas","area_id","data"));
    }

    public function add(Request $request, $id = null)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $price_card_slab = [];
        if (!empty($id)) {
            $price_card_slab = PriceCardSlab::where([['merchant_id','=',$merchant->id]])->findorfail($id);
            $pre_title = trans("$string_file.add");
            $submit_button = trans("$string_file.update");
        } else {
            $pre_title = trans("$string_file.edit");
            $submit_button = trans("$string_file.save");
        }
        $title = $pre_title.' '. trans("$string_file.price_card_slab");
        $areas = $this->getMerchantCountryArea($this->getAreaList(false, true)->get(), 1, 1,$string_file);
        return view('merchant.price_card_slab.form', compact('price_card_slab', 'title', 'submit_button','areas','id'));
    }

    public function save(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required_without:id',
            'country_area_id' => 'required_without:id',
            'type' => 'required_without:id',
            'slab' => 'required_with:id'
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $merchant = get_merchant_id(false);
            $merchant_id = $merchant->id;
            $string_file = $this->getStringFile($merchant_id);

            if (!empty($id)) {
                $price_card_slab = PriceCardSlab::findorfail($id);
            } else {
                $price_card_slab = PriceCardSlab::where([['merchant_id', $merchant_id], ['type', $request->type], ['country_area_id', $request->country_area_id]])->first();

//                if (!empty($price_card_slab->id)) {
//                    return redirect()->back()->withErrors(trans("$string_file.price_card_slab_already_exist"));
//                } else {
                    $price_card_slab = new PriceCardSlab();
                    $price_card_slab->merchant_id = $merchant_id;
                    $price_card_slab->country_area_id = $request->country_area_id;
                    $price_card_slab->type = $request->type;
//                }
            }
            $price_card_slab->name = $request->name;
            $price_card_slab->save();
            if(isset($request->slab) && !empty($request->slab)){
                PriceCardSlabDetail::where("price_card_slab_id",$price_card_slab->id)->delete();
                foreach($request->slab as $slab){
                    $price_card_slab_detail = new PriceCardSlabDetail();
                    $price_card_slab_detail->price_card_slab_id = $price_card_slab->id;
                    $price_card_slab_detail->from_time = $slab['from_time'];
                    $price_card_slab_detail->to_time = $slab['to_time'];
                    $price_card_slab_detail->week_days = implode(",", $slab['week_days']);
                    $temp = $slab;
                    unset($temp['from_time']);
                    unset($temp['to_time']);
                    unset($temp['week_days']);
                    $price_card_slab_detail->details = json_encode(array_values($temp));
                    $price_card_slab_detail->save();
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->getMessage());
        }
        DB::commit();
        return redirect()->route('merchant.pricecard.slabs')->withSuccess(trans("$string_file.saved_successfully"));
    }
}
