<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 17/3/23
 * Time: 11:57 AM
 */

namespace App\Http\Controllers\Merchant;


use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\BusinessSegment\Product;
use App\Traits\MerchantTrait;
use DB;
use Illuminate\Http\Request;
use validator;

class HomeScreenDesignConfigController extends Controller
{
    use MerchantTrait;

    public function index()
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);

        $permission_segments = get_permission_segments(1,true);

        $brands = Brand::with(['Segment' => function($query) use($permission_segments){
            $query->whereIn('slag',$permission_segments);
        }])->whereHas("Segment",function($query) use($permission_segments){
            $query->whereIn('slag',$permission_segments);
        })->where('merchant_id',$merchant_id)->where('delete','=',NULL)->get();
        $brand_list = [];
        $top_brand_list = [];
        foreach($brands as $brand){
            $brand_list[$brand->id] = $brand->Name($merchant_id);
            if($brand->is_top_brand == 1){
                array_push($top_brand_list, $brand->id);
            }
        }

        $products = Product::where('merchant_id',$merchant_id)->where('delete','=',NULL)->get();
        $product_list = [];
        foreach($products as $product){
            $product_list[$product->id] = $product->Name($merchant_id);
        }

        $top_seller_products = $merchant->TopSellerProduct->pluck("id")->toArray();

        $data['title'] = trans("$string_file.home_screen_design_config");
        $data['save_url'] = route("merchant.home-screen.design-config.save");
        return view('merchant.home_screen_design_config.index', compact("brand_list", "top_brand_list", "product_list", "top_seller_products", "data"));
    }

    public function save(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);

        $validator = Validator::make($request->all(), [
            'top_seller_products' => 'required',
            'top_brands'=>'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }

        DB::beginTransaction();
        try {
            Brand::where("merchant_id", $merchant_id)->whereIn("id",$request->top_brands)->update(["is_top_brand" => 1]);

            $merchant->TopSellerProduct()->sync($request->top_seller_products);
        }catch (\Exception $e) {
            DB::rollback();
            $message = $e->getMessage();
            p($message);
            return redirect()->back()->withErrors($message);
        }
        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.home_screen_design_config_saved_successfully"));
    }
}
