<?php

namespace App\Imports;

use App\Models\WeightUnit;
use App\Models\WeightUnitTranslation;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Auth;
use DB;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use PhpOffice\PhpSpreadsheet\IOFactory;

class WeightUnitImportPreview implements SkipsEmptyRows, WithStartRow, ToArray
{
    use RemembersRowNumber, ImageTrait, MerchantTrait;

    protected $data = [];

    public function startRow(): int
    {
        return 2;
    }

    public function array(array $array)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $locale = \App::getLocale();

        $weightUnit = [];
        foreach($array as $key => $row){
            
            // $weight_trans = WeightUnitTranslation::where(['merchant_id'=>$merchant_id,'locale'=>$locale])->get();
            
            // $error_messages = [];
            // if(!empty($weight_trans)){
                // foreach($weight_trans as $weight){
                //     // dd($weight->name,$row[1],$weight->description,$row[2]);
                //     if(($weight->name == $row[1] && $weight->description == $row[2]) || $weight->name == $row[1] || $weight->description == $row[2]){
                //         array_push($error_messages,'The same name or same description is already taken');
                //     }
                //     else{
                        array_push($weightUnit, array(
                            'name'=>$row[1],
                            'description'=>$row[2],
                            'segment'=>$row[3]
                        ));
                //     }
                // }
            // }
            
        }
        
        $this->data[] = $weightUnit;
        return $weightUnit;
        
    }
    
    public function getData()
    {
        return $this->data;
    }
}
