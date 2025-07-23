<?php

namespace App\Imports;

//use App\Models\ImportUserFail;
use App\Models\BusinessSegment\LanguageProduct;
use App\Models\BusinessSegment\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Auth;
use DB;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;

class ProductImport implements ToModel, WithStartRow, WithValidation

{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     *
     */
    use ImageTrait, MerchantTrait;
    use RemembersRowNumber;
    public function startRow(): int
    {
        return 2;
    }

    public function rules(): array
    {
        return [
            //            '3' => Rule::unique('orders', 'po_number','do_number','line_item','material_code')
        ];
    }


    public function model(array $row)
    {
        try {

            $business_segment = get_business_segment(false);
            $merchant = $business_segment->Merchant;

            $spreadsheet = IOFactory::load(request()->file('product_import_sheet'));

            $sheet        = $spreadsheet->getActiveSheet();
            // $row_limit    = $sheet->getHighestDataRow();
            $drawings = $sheet->getDrawingCollection();
            $drawing = $drawings[($this->getRowNumber() - $this->startRow())]; // get current row data only

            $zipReader = fopen($drawing->getPath(), 'r');
            $imageContents = '';
            while (!feof($zipReader)) {
                $imageContents .= fread($zipReader, 1024);
            }
            fclose($zipReader);
            $extension = $drawing->getExtension();

            $upload_path = \Config::get('custom.product_cover_image');
            $alias = $merchant->alias_name . $upload_path['path'];
            $name = time() . "_" . uniqid() . "_product_cover_image_" . $extension;
            $filePath = $alias . $name;

            \Illuminate\Support\Facades\Storage::disk('s3')->put($filePath, $imageContents);

            // $i = 0;
            // foreach ($spreadsheet->getActiveSheet()->getDrawingCollection() as $drawing) {
            //     if ($drawing instanceof MemoryDrawing) {
            //         ob_start();
            //         call_user_func(
            //             $drawing->getRenderingFunction(),
            //             $drawing->getImageResource()
            //         );
            //         $imageContents = ob_get_contents();
            //         ob_end_clean();
            //         switch ($drawing->getMimeType()) {
            //             case MemoryDrawing::MIMETYPE_PNG:
            //                 $extension = 'png';
            //                 break;
            //             case MemoryDrawing::MIMETYPE_GIF:
            //                 $extension = 'gif';
            //                 break;
            //             case MemoryDrawing::MIMETYPE_JPEG:
            //                 $extension = 'jpg';
            //                 break;
            //         }
            //     } else {

            //         $zipReader = fopen($drawing->getPath(), 'r');
            //         $imageContents = '';
            //         while (!feof($zipReader)) {
            //             $imageContents .= fread($zipReader, 1024);
            //         }
            //         fclose($zipReader);
            //         $extension = $drawing->getExtension();
            //     }
            //     $upload_path = \Config::get('custom.product_cover_image');
            //     $alias = $merchant->alias_name . $upload_path['path'];
            //     $name = time() . "_" . uniqid() . "_product_cover_image_" . $extension;
            //     $filePath = $alias . $name;

            //     \Illuminate\Support\Facades\Storage::disk('s3')->put($filePath, $imageContents);
            // }



            // $string_file = $this->getStringFile(NULL, $business_segment->Merchant);
            $merchant_id = $business_segment->merchant_id;
            $business_segment_id = $business_segment->id;
            $segment_id = $business_segment->segment_id;
            $category_id = $row[0]; // category_id
            $sku = $row[1];
            $product_name = $row[2];
            $description = $row[3];
            $ingredient = $row[4];
            $cover_image = $name;
            $preparation_time = $row[6];
            $sequence = $row[7];
            $status = $row[8];
            $food_type = $row[9];
            $manage_inventory = $row[10];
            $locale = \App::getLocale();

            $arr_name = [
                'product_name' => $product_name,
                'product_description' => $description,
                'product_ingredients' => $ingredient,
            ];

            $product_name = DB::table('language_products')->where(function ($query) use ($merchant_id, $locale, $product_name) {
                return $query->where([['language_products.merchant_id', '=', $merchant_id], ['language_products.locale', '=', $locale], ['language_products.name', '=', $product_name]]);
            })->join("products", "language_products.product_id", "=", "products.id")
                ->where('products.merchant_id', '=', $merchant_id)
                ->where('products.business_segment_id', '=', $business_segment_id)
                ->where('products.delete', NULL)->first();

            if (!empty($product_name->id)) {
                $product = Product::find($product_name->id);
                // throw new \Exception(trans("$string_file.product_name_already_exist"));
            } else {
                $product = new Product();
                $product->business_segment_id = $business_segment_id;
                $product->merchant_id = $merchant_id;
                $product->segment_id = $segment_id;
            }

            $product->sku_id = $sku;
            $product->product_preparation_time = $preparation_time;
            $product->sequence = $sequence;
            $product->status = $status;
            $product->category_id = $category_id;
            $product->food_type = $food_type;
            $product->product_cover_image = $cover_image;
            //            $product->display_type = $request->display_type;
            $product->manage_inventory = $manage_inventory;
            $product->save();
            // save language data
            $this->saveLanguageData($arr_name, $product->merchant_id, $product, $locale);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new \Exception($message);
        }
        return $product;
    }


    // save name according to language
    public function saveLanguageData($arr_name, $merchant_id, $product, $locale)
    {
        LanguageProduct::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => $locale, 'product_id' => $product->id
        ], [
            'business_segment_id' => $product->business_segment_id,
            'name' => $arr_name['product_name'],
            'description' => $arr_name['product_description'],
            'ingredients' => $arr_name['product_ingredients'],
        ]);
    }
}
