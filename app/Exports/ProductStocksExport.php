<?php

namespace App\Exports;

use App\Models\ProductStock;
use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;


class ProductStocksExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $stocks = ProductStock::join('products', 'product_stocks.product_id', '=', 'products.id')
            ->select(
                'product_stocks.id as stock_id',
                'product_stocks.product_id',
                'products.name as product_name',
                'product_stocks.sku',
                'product_stocks.variant'
            )
            ->get();

        $data = $stocks->map(function ($stock) {
            $variantDetails = '';

            if ($stock->variant) {
                if (strpos($stock->variant, '-') !== false) {
                    $parts = explode('-', $stock->variant);
                    if (count($parts) == 2) {
                        $first_value_code = trim($parts[0]);
                        $second_value_code = trim($parts[1]);

                        // Find attribute for first part
                        $attrValue1 = AttributeValue::where('value', $first_value_code)->first();
                        $attrValue2 = AttributeValue::where('value', $second_value_code)->first();

                        $details = [];

                        if ($attrValue1) {
                            $attribute1 = Attribute::find($attrValue1->attribute_id);
                            if ($attribute1) {
                                $details[] = '('.$attribute1->name.') - ' . $attrValue1->value;
                            }
                        }

                        if ($attrValue2) {
                            $attribute2 = Attribute::find($attrValue2->attribute_id);
                            if ($attribute2) {
                                $details[] = '('.$attribute2->name.') - ' . $attrValue2->value;
                            }
                        }

                        // Join with ' / ' separator
                        $variantDetails = implode(' / ', $details);
                    }
                } else {
                    $attribute_value_code = trim($stock->variant);
                    $attrValue = AttributeValue::where('value', $attribute_value_code)->first();

                    if ($attrValue) {
                        $attribute = Attribute::find($attrValue->attribute_id);
                        if ($attribute) {
                            $variantDetails = '('.$attribute->name . ') - ' . $attrValue->value;
                        }
                    }
                }
            }

            return [
                'product_id' => $stock->product_id,
                'stock_id' => $stock->stock_id,
                'sku' => $stock->sku,
                'product_name' => $stock->product_name,
                'variant_details' => $variantDetails,
                // 'mrp_price' => '', // empty column
                'selling_price' => '', // empty column
                'pts_percentage' => '', // empty column
            ];
        });

        return $data;
    }

    public function headings(): array
    {
        return ['Product ID', 'Stock ID', 'SKU', 'Product Name', 'Variant Details', 'Purchase Price', 'PTS Percentage'];
    }
}
