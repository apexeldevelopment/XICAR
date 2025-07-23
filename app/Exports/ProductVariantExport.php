<?php

namespace App\Exports;

use App\Models\BusinessSegment\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Traits\MerchantTrait;


class ProductVariantExport  implements FromCollection, WithHeadings

{
    /**
     * @return \Illuminate\Support\Collection
     */
    use MerchantTrait;
    public function collection()
    {
        $business_segment = get_business_segment(false);
        $query = Product::where([['delete', '=', NULL], ['status', '=', 1], ['business_segment_id', '=', $business_segment->id]]);
        $arr_products = $query->get();
        $merchant_id = $business_segment->merchant_id;

        $result = array();
        foreach ($arr_products as $record) {
            $result[] = array(
                'id' => $record->id,
                'sku_id' => $record->sku_id,
                'product_name' => $record->Name($merchant_id),
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        $business_segment = get_business_segment(false);
        $merchant_id = $business_segment->merchant_id;
        $string_file = $this->getStringFile($merchant_id);
        return [
            trans("$string_file.id"),
            trans("$string_file.product_sku"),
            trans("$string_file.product_title"),
            trans("$string_file.variant_sku"),
            trans("$string_file.variant_title"),
            trans("$string_file.price"),
            trans("$string_file.weight_unit"),
            trans("$string_file.weight"),
            trans("$string_file.is_title_show"),
            trans("$string_file.status"),
            trans("$string_file.stock"),
        ];
    }
}
