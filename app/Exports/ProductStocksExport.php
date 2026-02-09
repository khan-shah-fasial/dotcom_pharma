<?php

namespace App\Exports;

use App\Models\ProductStock;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;


class ProductStocksExport implements FromCollection, WithHeadings
{

    protected $type;
    protected $search;
    protected $published_status;

    public function __construct($type = null, $search = null, $published_status = null)
    {
        $this->type = $type;
        $this->search = $search;
        $this->published_status = $published_status;
    }

    public function collection()
    {
        // Build variant details helper function
        $getVariantDetails = function($variant) {
            if (!$variant) {
                return '';
            }

            $parts = explode('-', $variant);
            $details = [];

            foreach ($parts as $part) {
                $part = trim($part);

                // Try to find attribute value
                $attrValue = AttributeValue::where('value', $part)->first();

                if ($attrValue) {
                    $attribute = Attribute::find($attrValue->attribute_id);
                    if ($attribute) {
                        $details[] = '('.$attribute->name.') - ' . $attrValue->value;
                    } else {
                        // If attribute missing, just keep value
                        $details[] = $attrValue->value;
                    }
                } else {
                    // If no attribute found, keep raw value (like date)
                    $details[] = $part;
                }
            }

            // Join all parts with ' / ' separator
            return implode(' / ', $details);
        };

        // If either search or type is provided, use filtered query (same as previous)
        if ($this->search !== null || $this->type !== null || $this->published_status !== null) {

            // Base query on Product joined with product_stocks (same as previous)
            $stocks = Product::join('product_stocks', 'products.id', '=', 'product_stocks.product_id')
                ->select(
                    'product_stocks.id as stock_id',
                    'product_stocks.product_id',
                    'products.name as product_name',
                    'product_stocks.sku',
                    'product_stocks.variant'
                );

            // Apply search filter
            if (!empty($this->search)) {
                $stocks = $stocks->where('products.name', 'like', '%' . $this->search . '%')
                    ->orWhere('product_stocks.sku', 'like', '%' . $this->search . '%');
            }

            // Apply type/order filter
            if (!empty($this->type)) {
                $var = explode(",", $this->type);
                if (count($var) == 2) {
                    $col_name = $var[0];
                    $query = $var[1];
                    $stocks = $stocks->orderBy($col_name, $query);
                }
            }

            if ($this->published_status != 'All') {
                $stocks = $stocks->where('published', $this->published_status);
            }

            $stocks = $stocks->get();

        } else {
            // Base query on ProductStock (same as previous)
            $stocks = ProductStock::join('products', 'product_stocks.product_id', '=', 'products.id')
                ->select(
                    'product_stocks.id as stock_id',
                    'product_stocks.product_id',
                    'products.name as product_name',
                    'product_stocks.sku',
                    'product_stocks.variant'
                )
                ->get();
        }

        // Now expand each stock to show its batches
        $data = new Collection();
        
        foreach ($stocks as $stock) {
            $variantDetails = $getVariantDetails($stock->variant);
            
            // Load batches for this stock
            $batches = ProductBatch::where('product_stock_id', $stock->stock_id)->get();
            
            if ($batches->count() > 0) {
                // Create one row per batch
                foreach ($batches as $batch) {
                    $data->push([
                        'product_id' => $stock->product_id,
                        'stock_id' => $stock->stock_id,
                        'batch_id' => $batch->id,
                        'sku' => $stock->sku,
                        'product_name' => $stock->product_name,
                        'variant_details' => $variantDetails,
                        'batch_code' => $batch->batch ?? '',
                        'purchase_price' => '', // empty column for manual entry
                        'pts_percentage' => '', // empty column for manual entry
                    ]);
                }
            } else {
                // Stock has no batches - create one row with empty batch fields
                $data->push([
                    'product_id' => $stock->product_id,
                    'stock_id' => $stock->stock_id,
                    'batch_id' => '',
                    'sku' => $stock->sku,
                    'product_name' => $stock->product_name,
                    'variant_details' => $variantDetails,
                    'batch_code' => '',
                    'purchase_price' => '', // empty column for manual entry
                    'pts_percentage' => '', // empty column for manual entry
                ]);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Stock ID',
            'Batch ID',
            'SKU',
            'Product Name',
            'Variant Details',
            'Batch Code',
            'Purchase Price',
            'PTS Percentage'
        ];
    }
}
