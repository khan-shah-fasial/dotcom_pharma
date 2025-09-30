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

        // If either search or type is provided, reverse the query
        if ($this->search !== null || $this->type !== null || $this->published_status !== null) {

            // echo "Generating filtered export...\n";

            // Base query on Product joined with product_stocks
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

            // echo "default Generating filtered export...\n";

            // Base query on ProductStock
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

        // $stocks = ProductStock::join('products', 'product_stocks.product_id', '=', 'products.id')
        //     ->select(
        //         'product_stocks.id as stock_id',
        //         'product_stocks.product_id',
        //         'products.name as product_name',
        //         'product_stocks.sku',
        //         'product_stocks.variant'
        //     )
        //     ->get();

        $data = $stocks->map(function ($stock) {
            $variantDetails = '';

            if ($stock->variant) {
                // Split variant by '-'
                $parts = explode('-', $stock->variant);
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
                $variantDetails = implode(' / ', $details);
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
