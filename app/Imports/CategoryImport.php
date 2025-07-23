<?php

namespace App\Imports;


use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Auth;
use DB;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;
use App\Models\LangName;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;


class CategoryImport implements ToModel, WithStartRow, WithValidation

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

          
            $merchant = get_merchant_id(false);
            // $string_file = $this->getStringFile(NULL, $merchant);
            $merchant_id = $merchant->id;
            $locale = \App::getLocale();
            $category_name = $row[3];

            // load categry file
            $spreadsheet = IOFactory::load(request()->file('category_import_sheet'));
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
            $upload_path = \Config::get('custom.category');
            $alias = $merchant->alias_name . $upload_path['path'];
            $name = time() . "_" . uniqid() . "_category_" . $extension;
            $filePath = $alias . $name;

            \Illuminate\Support\Facades\Storage::disk('s3')->put($filePath, $imageContents);

            $category_data = DB::table('lang_names')->where(function ($query) use ($merchant_id, $locale, $category_name) {
                return $query->where([['lang_names.merchant_id', '=', $merchant_id], ['lang_names.locale', '=', $locale], ['lang_names.name', '=', $category_name]]);
            })->join("categories", "lang_names.dependable_id", "=", "categories.id")

                ->where('categories.merchant_id', '=', $merchant_id)
                ->where('categories.delete', NULL)->first();

            if (!empty($category_data->id)) {
                $category = Category::find($category_data->id);
            } else {

                $category = new Category();
                $category->merchant_id = $merchant_id;
            }
            $category->category_parent_id = !empty($row[1]) ? $row[1] : 0;
            $category->sequence = $row[5];
            $category->status = $row[4];

            $category->category_image = $name; // image name

            $category->save();

            $arr_segments = [$row[2]];
            $category->Segment()->sync($arr_segments);

            // sync language of category
            $category_locale =  $category->LangCategorySingle;
            if (!empty($category_locale->id)) {
                $category_locale->name = $category_name;
                $category_locale->save();
            } else {
                $language_data = new LangName([
                    'merchant_id' => $category->merchant_id,
                    'locale' => $locale,
                    'name' => $category_name
                ]);

                $category->LangCategory()->save($language_data);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new \Exception($message);
        }
        return $category;
    }
}
