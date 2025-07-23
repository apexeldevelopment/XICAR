<?php

namespace App\Http\Controllers\Merchant;

use App\Models\InfoSetting;
use App\Models\Segment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\WeightUnit;
use App\Models\WeightUnitTranslation;
use Illuminate\Validation\Rule;
use Auth;
use App;
use View;
use App\Traits\MerchantTrait;
use Maatwebsite\Excel\Facades\Excel;
use DB;

class WeightUnitController extends Controller
{
    use MerchantTrait;
    public function __construct()
    {
        $info_setting = InfoSetting::where('slug','WEIGHT_UNIT')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $merchant_id = get_merchant_id();
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        
        $weightunits = WeightUnit::where([['merchant_id',$merchant_id]])->get();
        return view('merchant.weightunit.index',compact('weightunits'));
    }


    public function add(Request $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        try {
            $weightunit = NULL;
            $arr_selected_segment = [];
            if(!empty($id))
            {
                $weightunit = WeightUnit::find($id);
                $arr_selected_segment = array_pluck($weightunit->Segment,'id');
            }
            $arr_business = get_merchant_segment($with_taxi = true, null,$segment_group_id = 1);
            $arr_business = get_permission_segments(1, false, $arr_business);
            $segment_data['arr_segment'] = $arr_business;
            $segment_data['selected'] = $arr_selected_segment;
            $segment_html = View::make('segment')->with($segment_data)->render();
            $is_demo = $merchant->demo == 1 ? true : false;
            return view('merchant.weightunit.edit', compact('weightunit','segment_html','is_demo'));
        }catch(\Exception $e)
        {
            return redirect()->back()->withErrors($e->getMessage());
        }

    }

    public function save(Request $request, $id = NULL)
    {
        $locale = App::getLocale();
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $request->validate([
            'name' => ['required', 'max:255',
                Rule::unique('weight_unit_translations', 'name')->where(function ($query) use ($merchant_id, &$locale, &$id) {
                    $query->where([['merchant_id', '=', $merchant_id], ['locale', '=', $locale], ['weight_unit_id', '!=', $id]]);
                })],
            'description' => 'required',
            'segment' => 'required',
        ]);

          $weight_unit =  WeightUnit::updateOrCreate([
                'id' => $id,
                'merchant_id' => $merchant_id,
                'status' => 1,
            ]);
        $id = $weight_unit->id;

        // sync segment
        $weight_unit->Segment()->sync($request->segment);

        $this->SaveLanguageWeightunit($merchant_id, $id, $request->name, $request->description);
        return redirect()->route('weightunit.index')->withSuccess(trans("$string_file.saved_successfully"));
    }

    public function SaveLanguageWeightunit($merchant_id, $weight_unit_id, $name, $description)
    {
        App\Models\WeightUnitTranslation::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'weight_unit_id' => $weight_unit_id
        ], [
            'name' => $name,
            'description' => $description,
        ]);
    }

    public function bulkImport(){
        $excelData = [];
        return view('merchant.weightunit.bulk-import', compact('excelData'));
    }
    
    public function bulkImportPreview(Request $request){
        // $validator = Validator::make(
        //     $request->all(),
        //     ['import_file'  => 'required|mimes:xls,xlsx']
        // );
        // if ($validator->fails()) {
        //     $msg = $validator->messages()->all();
        //     return redirect()->back()->with('error', $msg[0]);
        // }
        try{
            $merchant_id = get_merchant_id();
            $string_file = $this->getStringFile($merchant_id);

            $path1 = $request->file('import_file')->store('temp');
            $path = storage_path('app') . '/' . $path1;
            $import = new App\Imports\WeightUnitImportPreview();
            Excel::import($import, $path);
            $data = $import->getData();
            session(['excel_data' => $data[0]]);
            $excelData = session('excel_data');
            return view('merchant.weightunit.bulk-import', compact('excelData','merchant_id'));
        }catch (\Exception $e){
            $message = $e->getMessage() . ',File : ' . $e->getFile() . ',Line : ' . $e->getLine();
            return redirect()->back()->withErrors($message);
        }
    }
    
    public function bulkImportSubmit(Request $request){
        $excelData = session('excel_data');
        if(empty($excelData)){
            return redirect()->back()->withErrors("Invalid data storage");
        }
        DB::beginTransaction();
        try {
            $merchant_id = get_merchant_id();
            $string_file = $this->getStringFile($merchant_id);
            foreach($excelData as $data){
                $name = $data['name'];
                $desc = $data['description'];
                $segment = explode(", ", $data['segment']);
                
                $weight_unit = WeightUnit::where(['merchant_id'=>$merchant_id])
                ->whereHas('WeightUnitTranslation',function($q) use($name,$desc){
                        $q->where(['name'=>$name,'description'=> $desc]);
                })
                ->whereHas('Segment',function($q) use($segment){
                    $q->whereIn('name',$segment);
                })
                ->get();
                $error_messages = [];
                if(count($weight_unit) > 0){
                    $weightUnit=[];
                    foreach($weight_unit as $unit){
                        if(!empty($unit->WeightUnitTranslation) && (
                            strcasecmp($name, ($unit->WeightUnitTranslation)['name']) === 0 ||
                            strcasecmp($desc, ($unit->WeightUnitTranslation)['description']) === 0 ||
                            (strcasecmp($desc, ($unit->WeightUnitTranslation)['description']) === 0 && strcasecmp($name, ($unit->LanguageSingle)['name']) === 0)
                        )){
                            array_push($error_messages, 'Same Name or Same Description is already taken');
                        }
                    
                        
                        if(!empty($unit->Segment)){
                            foreach($unit->Segment as $seg){
                                if(in_array($seg['name'],$segment)){
                                    array_push($error_messages, $seg['name'].'Segment is already taken');
                                }
                                
                            }
                        }
                        
                          
                    }
                }
                else{
                    $seg = Segment::whereIn('name',$segment)->get(['id']);
                    $weightUnit = new WeightUnit();
                    $weightUnit->merchant_id = $merchant_id;
                    $weightUnit->status = 1;
                    $weightUnit->save();
                    
                    $weightUnit->Segment()->sync($seg);
                   
                    $this->SaveLanguageWeightunit($merchant_id, $weightUnit->id, $name, $desc);
                    
                }
                
                
            }
            
            DB::commit();
            return redirect()->route('weightunit.index')->withSuccess(trans("$string_file.saved_successfully"));
        }catch (\Exception $e){
            $message = $e->getMessage() . ',File : ' . $e->getFile() . ',Line : ' . $e->getLine();
            return redirect()->back()->withErrors($message);
        }
    }
}
