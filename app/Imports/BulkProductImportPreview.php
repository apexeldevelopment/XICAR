<?php

namespace App\Imports;

use App\Models\BusinessSegment\Product;
use App\Models\Category;
use App\Models\WeightUnit;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Auth;
use DB;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkProductImportPreview implements SkipsEmptyRows, WithStartRow, ToArray
{
    use RemembersRowNumber, ImageTrait, MerchantTrait;

    protected $data = [];

    public function startRow(): int
    {
        return 2;
    }

    public function array(array $array)
    {
        $business_segment = get_business_segment(false);
        $merchant = $business_segment->Merchant;
        $segment_id = $business_segment->segment_id;
        $locale = \App::getLocale();

        $products = [];
        foreach($array as $key => $row){
            if(strtoupper($row[13]) == "NO"){
                $temp = [];
                array_filter($array, function($item) use($row, &$temp){
                    if(strtoupper($item[13]) == "YES" && $row[1] == $item[1]){
                        $temp[] = $this->productVariantData($item);
                    }
                });
                $product_image_name = "";
//                $spreadsheet = IOFactory::load(request()->file('import_file'));
//                $sheet        = $spreadsheet->getActiveSheet();
//                $drawings = $sheet->getDrawingCollection();
//                if(isset($drawings[$key])){
//                    $drawing = $drawings[$key]; // get current row data only
//                    $zipReader = fopen($drawing->getPath(), 'r');
//                    $imageContents = '';
//                    while (!feof($zipReader)) {
//                        $imageContents .= fread($zipReader, 1024);
//                    }
//                    fclose($zipReader);
//                    $extension = $drawing->getExtension();
//
//                    $upload_path = \Config::get('custom.product_cover_image');
//                    $alias = $merchant->alias_name . $upload_path['path'];
//                    $product_image_name = time() . "_" . uniqid() . "_product_cover_image_" . $extension;
//                    $filePath = $alias . $product_image_name;
//
//                    \Illuminate\Support\Facades\Storage::disk('s3')->put($filePath, $imageContents);
//                }
                /* @ayush  added row[1] (product sku_id) in checkProductName Function */
                $name_valid = $this->checkProductName($merchant->id, $business_segment->id, $locale, $row[2], $row[1]);
                $is_valid = true;
                $error_messages = [];
                if(!empty($name_valid)){
                    $is_valid = false;
                    array_push($error_messages, $name_valid);
                }
/*                @ayush (removed unique checks because we need to update or create)   */
//                $sku_valid = Product::where([['business_segment_id', '=', $business_segment->id], ['merchant_id', '=', $merchant->id], ['delete', '=', NULL], ['sku_id', '=', $row[1]]])->first();
//                if(!empty($sku_valid)){
//                    $is_valid = false;
//                    array_push($error_messages, "The sku id has already been taken.");
//                }
//                $temp_sku_ids = array_column($temp, "sku_id");
//                if(count(array_unique($temp_sku_ids)) < count($temp_sku_ids)){
//                    $is_valid = false;
//                    array_push($error_messages, "Duplicate product variant sku ids.");
//                }

//                $category = Category::find($row[0]);
                $category = Category::where([["id",$row[0]], ["merchant_id", $merchant->id]])->first();
                if(empty($category)){
                    $is_valid = false;
                    $error_messages[] = "The Category ID is not valid has already been taken.";
                }
                $products[] = array(
                    'business_segment_id' => $business_segment->id,
                    'merchant_id' => $merchant->id,
                    'segment_id' => $segment_id,
                    'category_id' => !empty($category) ? $category->id : "",
                    'category_name' => !empty($category) ? $category->Name($merchant->id) : "",
                    'sku_id' => $row[1],
                    'product_preparation_time' => $row[7],
                    'tax' => $row[8],
                    'sequence' => $row[9],
                    'status' => strtoupper($row[10]) == "ACTIVE" ? 1 : 2,
                    'food_type' => $row[11],
                    'product_cover_image' => trim($row[6]),// $product_image_name,
                    'manage_inventory' => strtoupper($row[12]) == "YES" ? 1 : 2,
                    'display_type' => strtoupper($row[5]) == "YES" ? 1 : null,

                    'name' => $row[2],
                    'description' => $row[3],
                    'ingredients' => $row[4],

                    'product_variants' => $temp,
                    'is_valid' => $is_valid,
                    'error_messages' => $error_messages,
                );
            }
        }
        $this->data[] = $products;
        return $products;
    }

    public function productVariantData($data){
        $weight = WeightUnit::find($data[17]);

        $error_messages = [];
        $is_valid = true;
        if (empty($weight)) {
            $is_valid = false;
            array_push($error_messages, "Invalid Weight Unit.");
        }

        return array(
            'sku_id' => $data[14],
            'product_title' => $data[15],
            'product_price' => $data[16],
            'weight_unit_id' => !empty($weight) ? $weight->id : NULL,
            'weight_text' => !empty($weight) ? $data[18]." ".$weight->WeightUnitName : $data[18]. " Invalid Unit",
            'weight' => $data[18],
            'is_title_show' => strtoupper($data[19]) == "YES" ? 1 : 0,
            'status' => strtoupper($data[20]) == "ACTIVE" ? 1 : 2,
            'current_stock' => $data[21],
            'product_cost' => $data[22],
            'product_selling_price' => $data[23],
            'is_valid' => $is_valid,
            'error_messages' => $error_messages,
        );
    }

    public function checkProductName($merchant_id, $business_segment_id, $locale, $product_name, $sku_id){
        $product_name = DB::table('language_products')->where(function ($query) use ($merchant_id, $locale, $product_name) {
            return $query->where([['language_products.merchant_id', '=', $merchant_id], ['language_products.locale', '=', $locale], ['language_products.name', '=', $product_name]]);
        })->join("products", "language_products.product_id", "=", "products.id")
            ->where('products.merchant_id', '=', $merchant_id)
            ->where('products.business_segment_id', '=', $business_segment_id)
            ->where('products.sku_id', '!=', $sku_id)
            ->where('products.delete', NULL)->first();
        if (!empty($product_name->id)) {
            $string_file = $this->getStringFile($merchant_id);
            return trans("$string_file.product_name_already_exist");
        }
        return "";
    }

    public function getData()
    {
        return $this->data;
    }
}
