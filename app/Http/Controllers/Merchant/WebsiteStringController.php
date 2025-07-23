<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 27/9/23
 * Time: 10:32 AM
 */

namespace App\Http\Controllers\Merchant;


use App\Http\Controllers\Controller;
use App\Models\InfoSetting;
use App\Models\MerchantWebsiteString;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WebsiteStringController extends Controller
{
    use MerchantTrait;

    public function add(){
        $merchant = get_merchant_id(false);
        $info_setting = InfoSetting::where('slug', 'LANGUAGE_STRING')->first();
        $merchant_website_strings = MerchantWebsiteString::where(array("merchant_id" => $merchant->id, "locale" => App::getLocale()))->first();
        $string_content = "";
        if(empty($merchant_website_strings)){
            $merchant_website_strings = MerchantWebsiteString::where(array("merchant_id" => $merchant->id, "locale" => "en"))->first();
        }
        if(!empty($merchant_website_strings)){
            $alias = $merchant->alias_name . "/string_files/";
            $file_name = $alias . $merchant_website_strings->file_name;
            $string_content = \Illuminate\Support\Facades\Storage::disk('s3')->get($file_name);
        }
        return view('merchant.random.website_strings', compact('info_setting', 'merchant_website_strings', 'string_content'));
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'string_data' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput()->withErrors($errors);
        }
        DB::beginTransaction();
        try {
            $merchant = get_merchant_id(false);
            $string_file = $this->getStringFile(null, $merchant);
            $locale = App::getLocale();

            // Create a new UserFile record
            $merchant_website_string = MerchantWebsiteString::where(["merchant_id" => $merchant->id, "locale" => $locale])->first();
            if(empty($merchant_website_string)){
                $merchant_website_string = new MerchantWebsiteString();
                $merchant_website_string->merchant_id = $merchant->id;
                $merchant_website_string->locale = $locale;
                $merchant_website_string->file_name = 'website_string_file_' . time() . '.json';
            }
//            $merchant_website_string->content = $request->string_data;
            $merchant_website_string->save();

            // Delete the file from S3
            $alias = $merchant->alias_name. "/string_files/";
            $file_name = $alias.$merchant_website_string->file_name;

            Storage::disk('s3')->delete($file_name);

            Storage::disk('s3')->put($file_name, $request->string_data);

            DB::commit();
            return redirect()->back()->withSuccess(trans("$string_file.success"));
        }catch (\Exception $exception){
            DB::rollBack();
            return redirect()->back()->withErrors($exception->getMessage());
        }
    }
}
