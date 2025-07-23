<?php

namespace App\Traits;

use App\Models\BusinessSegment\ProductInventory;
use App\Models\BusinessSegment\ProductInventoryLog;
use App\Models\BusinessSegment\ProductVariant;
use App\Models\Category;
use App\Models\WeightUnit;
use Auth;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Helper\Merchant;

trait ProductTrait
{

    public function getProducts($request)
    {
        //        p($request->all());
        $user = $request->user('api');
        $top_seller = !empty($request->top_seller) ? $request->top_seller : NULL;
        $trip_calculation_method = $user->Merchant->Configuration->trip_calculation_method;
        $format_price = $user->Merchant->Configuration->format_price;
        $currency = isset($user->Country) ? $user->Country->isoCode : "";
        $merchant_id = $request->merchant_id;
        $segment_id = $request->segment_id;
        $sub_category_id = $request->sub_category_id;
        $category_id = !empty($sub_category_id) ? $sub_category_id : $request->category_id;
        $display_type = !empty($request->display_type) ? $request->display_type : NULL;
        $business_segment_id = !empty($request->business_segment_id) ? $request->business_segment_id : NULL;
        $filtered_product_id = !empty($request->filtered_product_id) ? $request->filtered_product_id : NULL;
        $is_for_favourite = !empty($request->is_for_favourite) ? $request->is_for_favourite : false;
        $merchant_helper = new Merchant();

        $query = ProductVariant::select('id', 'weight', 'product_id', 'weight_unit_id', 'discount', 'product_title', 'product_price', 'status', 'is_title_show')
            ->with(['Product' => function ($q) use ($merchant_id, $segment_id, $category_id, $business_segment_id, $display_type) {
                $q->select('id', 'category_id', 'food_type', 'product_cover_image', 'manage_inventory', 'sequence', 'empty_bottle_return', 'bottle_price', 'subscription_enabled')
                    ->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id], ['delete', '=', NULL]]);
                $q->where(function ($qq) use ($business_segment_id) {
                    if (!empty($business_segment_id)) {
                        //                        $qq->where('business_segment_id', $business_segment_id);
                        if (is_array($business_segment_id)) {
                            $qq->whereIn('business_segment_id', $business_segment_id);
                        } else {
                            $qq->where('business_segment_id', $business_segment_id);
                        }
                    }
                });
                $q->where(function ($qq) use ($display_type) {
                    if (!empty($display_type)) {
                        $qq->where('display_type', $display_type);
                    }
                });
                $q->where(function ($qq) use ($category_id) {
                    if (!empty($category_id)) {
                        $qq->where('category_id', $category_id);
                    }
                });
                // $q->orderBy('updated_at','DESC');
                $q->with(['Category' => function ($qq) use ($merchant_id, $category_id) {
                    $qq->select('id', 'category_parent_id')
                        ->where(function ($qqq) use ($category_id) {
                            $qqq->where('id', $category_id);
                            $qqq->orWhere('category_parent_id', $category_id);
                        })
                        ->where([['merchant_id', '=', $merchant_id], ['status', '=', 1], ['delete', '=', NULL]]);
                }]);
            }])
            ->whereHas('Product', function ($q) use ($merchant_id, $segment_id, $category_id, $business_segment_id, $display_type, $filtered_product_id, $top_seller) {
                $q->select('id', 'category_id', 'food_type', 'product_cover_image')
                    ->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id], ['status', '=', 1], ['delete', '=', NULL]]);
                if ($top_seller) {
                    $q->whereHas("TopSellerProduct");
                }
                $q->where(function ($qq) use ($business_segment_id) {
                    if (!empty($business_segment_id)) {
                        $qq->where('business_segment_id', $business_segment_id);
                    }
                });
                $q->where(function ($qq) use ($display_type) {
                    if (!empty($display_type)) {
                        $qq->where('display_type', $display_type);
                    }
                });
                $q->where(function ($qq) use ($category_id) {
                    if (!empty($category_id)) {
                        $qq->where('category_id', $category_id);
                    }
                });
                $q->where(function ($qq) use ($filtered_product_id) {
                    if (!empty($filtered_product_id)) {
                        $qq->where('id', $filtered_product_id);
                    }
                });
                $q->orderBy('sequence');
                $q->orderBy('updated_at', 'DESC');
                if (!empty($category_id)) {
                    $q->whereHas('Category', function ($qq) use ($merchant_id, $category_id) {
                        $qq->select('id', 'category_parent_id')
                            ->where(function ($qqq) use ($category_id) {
                                $qqq->where('id', $category_id);
                                $qqq->orWhere('category_parent_id', $category_id);
                            })
                            ->where([['merchant_id', '=', $merchant_id], ['status', '=', 1], ['delete', '=', NULL]]);
                    });
                } else {

                    $q->whereHas('Category', function ($qq) use ($merchant_id) {
                        $qq->where([['merchant_id', '=', $merchant_id], ['status', '=', 1], ['delete', '=', NULL]]);
                    });
                }
                //                $q->with(['Category'=>function($qq) use($merchant_id,$category_id) {
                //                    $qq->select('id', 'category_parent_id')
                //                        ->where(function ($qqq) use ($category_id) {
                //                            $qqq->where('id', $category_id);
                //                            $qqq->orWhere('category_parent_id', $category_id);
                //                        })
                //                        ->where([['merchant_id', '=', $merchant_id], ['status', '=', 1], ['delete', '=', NULL]]);
                //                }]);
            })
            ->with(['WeightUnit' => function ($q) {
                $q->select('id');
            }])
            ->with(['ProductInventory' => function ($q) use ($merchant_id, $segment_id) {
                $q->select('id', 'product_variant_id', 'current_stock');
                $q->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id]]);
            }]);

        if ($is_for_favourite) {
            $query->whereHas('UserFavourite', function ($q) use ($user) {
                $q->where("user_id", $user->id);
            });
        }
        if (isset($request->product_variant_id) && !empty($request->product_variant_id)) {
            $query->where('id', $request->product_variant_id);
        }
        $products = [];  //products incase when needed a specefic product with product_id
        if ($request->return_type != 'single_product_detail') {
            $products = $query->where([['status', '=', 1]])
                ->orderBy(DB::raw('RAND()'))
                ->get();
        }

        if ($request->return_type == 'single_product_detail') {  //only when needed a specefic product with product_id
            $item = $query->where([['status', '=', 1], ['product_id', $request->product_id]])
                ->first();

            if (empty($item)) {
                return $products;
            }

            $unit = isset($item->WeightUnit->id) ? $item->WeightUnit->WeightUnitName : "";
            $product_lang = $item->Product->langData($merchant_id);
            $discounted_price = !empty($item->discount) &&  $item->discount > 0 ? ($item->product_price - $item->discount) : "";

            $product_cover_image = !empty($item->Product->product_cover_image) ? get_image($item->Product->product_cover_image, 'product_cover_image', $merchant_id) : "";
            $product_image = $item->Product->ProductImage && $item->Product->ProductImage->count() > 0 ? get_image($item->Product->ProductImage[0]->product_image, 'product_image', $merchant_id) : $product_cover_image;
            $product_cover_image = !empty($product_cover_image) ? $product_cover_image : $product_image;

            $price = custom_number_format($item->product_price, $trip_calculation_method);
            $discounted_price = !empty($discounted_price) ? custom_number_format($discounted_price, $trip_calculation_method) : "";

            $products[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'weight_unit_id' => $item->weight_unit_id,
                'stock_quantity' => isset($item->ProductInventory->id) ? $item->ProductInventory->current_stock : NULL,
                // 'price' => custom_number_format($item->product_price, $trip_calculation_method),
                // 'discount' => !empty($item->discount) &&  $item->discount > 0 ? custom_number_format($item->discount, $trip_calculation_method) : "",
                // 'discounted_price' => !empty($discounted_price) ? custom_number_format($discounted_price, $trip_calculation_method) : "",
                'price' => $price,
                'formatted_price' => ($format_price != 1) ? $merchant_helper->PriceFormat($price, $merchant_id, $format_price, $trip_calculation_method) : "",
                'discount' => !empty($item->discount) &&  $item->discount > 0 ? custom_number_format($item->discount, $trip_calculation_method) : "",
                'discounted_price' => $discounted_price,
                'formatted_discounted_price' => ($format_price != 1 &&  !empty($discounted_price)) ? $merchant_helper->PriceFormat($discounted_price, $merchant_id, $format_price, $trip_calculation_method) : "",
                // 'title' => $item->is_title_show == 1 ? $item->Name($merchant_id) : $product_lang->name,
                'title' => $item->is_title_show == 1 && !empty($item->Name($merchant_id)) ? $item->Product->Name($merchant_id) . '(' . $item->Name($merchant_id) . ')' : $item->Product->Name($merchant_id),
                'product_description' => !empty($product_lang->description) ? $product_lang->description : "",
                'ingredients' => !empty($product_lang->ingredients) ? $product_lang->ingredients : "",
                'currency' => "$currency",
                'manage_inventory' => $item->Product->manage_inventory,
                'weight_unit' => $item->weight . ' ' . $unit,
                'image' => $product_cover_image,
                'product_image' => $product_image,
                'product_availability' => ($item->status == 1) ? true : false,
                'sequence' => $item->Product->sequence,
                'bottle_return' => ($item->Product->empty_bottle_return == 1) ? true : false,
                'bottle_price' => strval($item->Product->bottle_price),
                'subscription_enabled' => ($item->Product->subscription_enabled == 1) ? true : false,
                'subscribe_plan_list' => [['id' => 1, 'title' => 'Daily Plan'], ['id' => 2, 'title' => 'Weekly Plan'], ['id' => 3, 'title' =>  'Alternate Days Plan'], ['id' => 4, 'title' =>  'Monthly Plan']]
            ];
        }

        if ($request->return_type == "modified_array") {
            foreach ($products as $key => $product) {
                if ($product->Product->manage_inventory == 1 && empty($product->ProductInventory)) {
                    $products->forget($key);
                }
            }
            $products = $products->values();
            $products = $products->map(function ($item, $key) use ($merchant_id, $currency, $trip_calculation_method, $merchant_helper, $format_price) {
                $unit = isset($item->WeightUnit->id) ? $item->WeightUnit->WeightUnitName : "";
                $product_lang = $item->Product->langData($merchant_id);
                $discounted_price = !empty($item->discount) &&  $item->discount > 0 ? ($item->product_price - $item->discount) : "";

                $product_cover_image = !empty($item->Product->product_cover_image) ? get_image($item->Product->product_cover_image, 'product_cover_image', $merchant_id) : "";
                $product_image = $item->Product->ProductImage && $item->Product->ProductImage->count() > 0 ? get_image($item->Product->ProductImage[0]->product_image, 'product_image', $merchant_id) : $product_cover_image;
                $product_cover_image = !empty($product_cover_image) ? $product_cover_image : $product_image;

                $price = custom_number_format($item->product_price, $trip_calculation_method);
                $discounted_price = !empty($discounted_price) ? custom_number_format($discounted_price, $trip_calculation_method) : "";

                return array(
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'weight_unit_id' => $item->weight_unit_id,
                    'stock_quantity' => isset($item->ProductInventory->id) ? $item->ProductInventory->current_stock : NULL,
                    // 'price' => custom_number_format($item->product_price, $trip_calculation_method),
                    // 'discount' => !empty($item->discount) &&  $item->discount > 0 ? custom_number_format($item->discount, $trip_calculation_method) : "",
                    // 'discounted_price' => !empty($discounted_price) ? custom_number_format($discounted_price, $trip_calculation_method) : "",
                    'price' => $price,
                    'formatted_price' => ($format_price != 1) ? $merchant_helper->PriceFormat($price, $merchant_id, $format_price, $trip_calculation_method) : "",
                    'discount' => !empty($item->discount) &&  $item->discount > 0 ? custom_number_format($item->discount, $trip_calculation_method) : "",
                    'discounted_price' => $discounted_price,
                    'formatted_discounted_price' => ($format_price != 1 &&  !empty($discounted_price)) ? $merchant_helper->PriceFormat($discounted_price, $merchant_id, $format_price, $trip_calculation_method) : "",
                    // 'title' => $item->is_title_show == 1 ? $item->Name($merchant_id) : $product_lang->name,
                    'title' => $item->is_title_show == 1 && !empty($item->Name($merchant_id)) ? $item->Product->Name($merchant_id) . '(' . $item->Name($merchant_id) . ')' : $item->Product->Name($merchant_id),
                    'product_description' => !empty($product_lang->description) ? $product_lang->description : "",
                    'ingredients' => !empty($product_lang->ingredients) ? $product_lang->ingredients : "",
                    'currency' => "$currency",
                    'manage_inventory' => $item->Product->manage_inventory,
                    'weight_unit' => $item->weight . ' ' . $unit,
                    'image' => $product_cover_image,
                    'product_image' => $product_image,
                    'product_availability' => ($item->status == 1) ? true : false,
                    'sequence' => $item->Product->sequence,
                    'empty_bottle_return' => ($item->Product->empty_bottle_return == 1) ? true : false,
                    'bottle_price' => $item->Product->bottle_price,
                    'subscription_enabled' => ($item->Product->subscription_enabled == 1) ? true : false,
                    'subscribe_plan_list' => [['id' => 1, 'title' => 'Daily Plan'], ['id' => 2, 'title' => 'Weekly Plan'], ['id' => 3, 'title' =>  'Alternate Days Plan'], ['id' => 4, 'title' =>  'Monthly Plan']]
                );
            });
            if (!empty($products)) {
                $products = $products->sortBy('sequence')->values();
            }
        }
        return $products;
    }

    public function manageProductVariantInventory($request)
    {
        $product_variant_id = isset($request->id) ? $request->id : NULL;
        // inventory object
        $product_inventory = ProductInventory::where('product_variant_id', $product_variant_id)->first();
        // inventory log object
        $product_inventory_log = new ProductInventoryLog();
        // current stock of variant
        $current_stock = $product_inventory->current_stock;

        $updated_current_stock = 0;
        if ($request->stock_type == 1) // stock in
        {
            $updated_current_stock = ($current_stock + $request->new_stock);
        } else {
            $updated_current_stock = ($current_stock - $request->new_stock);
            $product_inventory_log->stock_out_id = $request->stock_out_id;
        }

        $product_inventory->current_stock = $updated_current_stock;
        $product_inventory->save();

        $product_inventory_log->product_inventory_id = $product_inventory->id;
        $product_inventory_log->last_current_stock = $current_stock;
        $product_inventory_log->stock_type = $request->stock_type;
        $product_inventory_log->new_stock = $request->new_stock;
        $product_inventory_log->current_stock = $updated_current_stock;
        $product_inventory_log->product_cost = $product_inventory->product_cost;
        $product_inventory_log->product_selling_price = $product_inventory->product_selling_price;
        $product_inventory_log->save();
        return true;
    }


    public function getCategory($merchant_id, $segment_id = null, $type = "parent", $parent_id = NULL, $calling_from = "web")
    {
        $categories = Category::select('id')
            ->where(function ($query) use ($type, $parent_id) {
                if ($type == 'parent') {
                    $query->where('category_parent_id', '=', 0);
                } elseif ($type == 'child') {
                    $query->where('category_parent_id', '=', $parent_id);
                }
            })->with('Segment')->whereHas('Segment', function ($q) use ($segment_id) {
                if (!empty($segment_id)) {
                    $q->where('segment_id', $segment_id);
                }
            })
            ->where('merchant_id', $merchant_id)
            ->where('delete', NULL)
            ->get();
        $arr_category = [];
        if ($calling_from == "app") {
            foreach ($categories as $category) {
                $arr_category[] =
                    ["key" => $category->id, "value" => $category->Name($merchant_id)];
            }
        } else {
            foreach ($categories as $category) {
                $arr_category[$category->id] = $category->Name($merchant_id);
            }
        }
        return $arr_category;
    }


    public function getWeightUnit($merchant_id, $calling_from = "web", $segment_id = NULL)
    {
        $list = [];
        $arr_weight_unit = WeightUnit::whereHas('Segment', function ($q) use ($segment_id) {
            $q->where('segment_id', $segment_id);
        })->where([['merchant_id', '=', $merchant_id]])->get();
        if ($calling_from == "app") {
            foreach ($arr_weight_unit as $weight_unit) {
                $list[] = ["key" => $weight_unit->id, "value" => $weight_unit->WeightUnitName];
            }
        } else {
            foreach ($arr_weight_unit as $weight_unit) {
                $list[$weight_unit->id] = $weight_unit->WeightUnitName;
            }
        }

        return $list;
    }




    public function getPriceDetailHolder($product_cart, $string_file, $currency, $receipt_data, $packaging_preferences = [])
    {
        $holder = [];

        $holder[] = $this->makePriceDetailItem(trans("$string_file.cart_amount"), $currency, $receipt_data['total_amount_formatted']);
        $holder[] = $this->makePriceDetailItem(trans("$string_file.discount_amount"), $currency, $receipt_data['discount_amount']);
        $holder[] = $this->makePriceDetailItem(trans("$string_file.tax")." ".trans("$string_file.amount"), $currency, $receipt_data['tax_amount_formatted']);

        if ($product_cart->ServiceType->type == 1) {
            $holder[] = $this->makePriceDetailItem(trans("$string_file.delivery_charge"), $currency, $receipt_data['delivery_charge_formatted']);
        }

        if ((isset($product_cart->BusinessSegment->Merchant) && $product_cart->BusinessSegment->Merchant->Configuration->empty_bottle_return_enabled == 1)) {
            $holder[] = $this->makePriceDetailItem(trans("$string_file.empty_bottle_return"), $currency, $receipt_data['empty_bottle_price']);
        }

        if(isset($receipt_data['time_charges'])){
            $holder[] = $this->makePriceDetailItem(trans("$string_file.time_charges"), $currency, $receipt_data['time_charges']);
        }
        if(isset($receipt_data['tip_amount'])){
            $holder[] = $this->makePriceDetailItem(trans("$string_file.tip_amount"), $currency, $receipt_data['tip_amount']);
        }

        foreach ($packaging_preferences as $preference) {
            if ($preference['is_applied']) {
                $holder[] = $this->makePriceDetailItem($preference['description'], "", $preference['amount']);
            }
        }

        if (isset($receipt_data['final_amount_formatted']) || isset($receipt_data['final_amount'])) {
            if (isset($receipt_data['final_amount_formatted'])) {
                $holder[] = $this->makePriceDetailItem(trans("$string_file.grand") . " " . trans("$string_file.total"), $currency, $receipt_data['final_amount_formatted']);
            } else {
                $holder[] = $this->makePriceDetailItem(trans("$string_file.grand") . " " . trans("$string_file.total"), $currency, $receipt_data['final_amount']);
            }
        }

        return $holder;
    }

    private function makePriceDetailItem($highlighted_text, $currency, $value_text)
    {
        return [
            'highlighted_text' => $highlighted_text,
            "highlighted_text_color" => "333333",
            "highlighted_style" => "NORMAL",
            "highlighted_visibility" => true,
            "small_text" => "eee",
            "small_texot_clor" => "333333", // typo preserved as per original
            "small_text_style" => "",
            "small_text_visibility" => false,
            "value_text" => $currency . " " . $value_text,
            "value_text_color" => "333333",
            "value_text_style" => "",
            "value_textvisibility" => true,
            "description_text" => "",
        ];
    }
}
