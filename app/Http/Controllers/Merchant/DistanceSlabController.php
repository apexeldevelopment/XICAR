<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Helper\AjaxController;
use App\Models\Configuration;
use App\Models\Corporate;
use App\Models\InfoSetting;
use App\Models\DistanceSlab;
use App\Traits\AreaTrait;
use App\Traits\MerchantTrait;
use Auth;
use App;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use DB;

class DistanceSlabController extends Controller
{
    use AreaTrait, MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','PROMOCODE_MANAGEMENT')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $merchant_id = get_merchant_id();
        $allDistanceSlabs = DistanceSlab::where([['merchant_id', '=', $merchant_id]])->latest()->paginate(25);
        return view('merchant.distance_slab.index', compact('allDistanceSlabs'));
    }

    public function add(Request $request, $id = NULL)
    {
        $merchant_id = get_merchant_id();
        $DistanceSlab = NULL;
        if(!empty($id))
        {
         $DistanceSlab = DistanceSlab::findOrFail($id);
        }
        return view('merchant.distance_slab.create', compact('id','DistanceSlab'));
    }


    public function save(Request $request,$id = NULL)
    {
        DB::beginTransaction();
        try {
            $merchant_id = get_merchant_id();

            if(!empty($id))
            {
             $DistanceSlab = DistanceSlab::findOrFail($id);
            }
            else
            {
                $DistanceSlab = new DistanceSlab;
                $DistanceSlab->merchant_id = $merchant_id;
            }
            $DistanceSlab->name = $request->name;
            $DistanceSlab->details = json_encode($request->distance_content);
            $DistanceSlab->status = 1;
            $DistanceSlab->save();
        }catch(\Exception $e)
        {
            DB::rollBack();
        }
        DB::commit();
        $string_file = $this->getStringFile(NULL,$DistanceSlab->Merchant);
        return redirect()->back()->withSuccess(trans("$string_file.distance_slab_saved_successfully"));
    }
}
