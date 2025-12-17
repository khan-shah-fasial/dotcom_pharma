<?php

namespace App\Services;

use AizPackages\CombinationGenerate\Services\CombinationService;
use App\Models\ProductStock;
use App\Utility\ProductUtility;
use App\Models\Product;

class ProductStockService
{
    public function store(array $data, $product)
    {
        $collection = collect($data);

        $options = ProductUtility::get_attribute_options($collection);
        
        //Generates the combinations of customer choice options
        $combinations = (new CombinationService())->generate_combination($options);
        
        $variant = '';
        if (count($combinations) > 0) {
            $product->variant_product = 1;
            $product->save();
            foreach ($combinations as $key => $combination) {
                $str = ProductUtility::get_combination_string($combination, $collection);
                $product_stock = new ProductStock();
                $product_stock->product_id = $product->id;

                $product_stock->mrp_price = request()->get('mrp_price_' . str_replace('.', '_', $str), null);
                // $product_stock->mrp_role_price = generateRoleBasedPrices(request()['mrp_price_' . str_replace('.', '_', $str)]); //mrp_price by role

                $product_stock->variant = $str;
                $product_stock->price = request()['price_' . str_replace('.', '_', $str)];
                $product_stock->role_price = generateRoleBasedPrices(request()['price_' . str_replace('.', '_', $str)]); //price by role

                // $product_stock->dimension = request()->get('dimension_' . str_replace('.', '_', $str), null);
                $product_stock->length = request()->get('length_' . str_replace('.', '_', $str), null);
                $product_stock->width = request()->get('width_' . str_replace('.', '_', $str), null);
                $product_stock->height = request()->get('height_' . str_replace('.', '_', $str), null);

                $product_stock->weight = request()->get('weight_' . str_replace('.', '_', $str), null);
                $product_stock->count = request()->get('count_' . str_replace('.', '_', $str), null);
                $product_stock->min_qty = request()->get('min_qty_' . str_replace('.', '_', $str), 1);
                $product_stock->product_exp_date = request()->get('product_exp_date_' . str_replace('.', '_', $str), null);

                $product_stock->sku = request()['sku_' . str_replace('.', '_', $str)];
                $product_stock->qty = request()['qty_' . str_replace('.', '_', $str)];

                $product_stock->coa = request()['coa_' . str_replace('.', '_', $str)];

                $product_stock->image = request()['img_' . str_replace('.', '_', $str)];
                $product_stock->save();


                // $product = Product::find($product->id);
                // Check if the product exists
                if ($product) {
                    $product->published = 0;
                    $product->save();
                }

            }
        } else {
            unset($collection['colors_active'], $collection['colors'], $collection['choice_no']);
            $qty = $collection['current_stock'];
            $price = $collection['unit_price'];

            $mrp_price = $collection['mrp_price'] ?? null;
            // $dimension = $collection['dimension'] ?? null;
            $length = $collection['length'] ?? null;
            $width = $collection['width'] ?? null;
            $height = $collection['height'] ?? null;

            $weight = $collection['weight'] ?? null;
            $count = $collection['count'] ?? null;
            $min_qty = $collection['min_qty'] ?? 1;
            $product_exp_date = $collection['product_exp_date'] ?? null;

            unset($collection['current_stock']);
            unset($collection['dimension']);
            unset($collection['mrp_price']);

            // $data = $collection->merge(compact('variant', 'qty', 'price', 'per_piece_price'))->toArray();

            $data = $collection->merge(compact('variant', 'qty', 'price', 'mrp_price', 'length', 'width', 'height', 'weight', 'count', 'min_qty', 'product_exp_date'))->toArray();

            ProductStock::create($data);

        }
    }

    public function product_duplicate_store($product_stocks , $product_new)
    {
        foreach ($product_stocks as $key => $stock) {
            $product_stock              = new ProductStock;
            $product_stock->product_id  = $product_new->id;
            $product_stock->variant     = $stock->variant;
            $product_stock->price       = $stock->price;
            $product_stock->mrp_price   = $stock->mrp_price;
            $product_stock->sku         = $stock->sku;
            $product_stock->qty         = $stock->qty;
            $product_stock->length      = $stock->length;
            $product_stock->width       = $stock->width;
            $product_stock->height      = $stock->height;
            $product_stock->weight      = $stock->weight;
            $product_stock->count       = $stock->count;
            $product_stock->min_qty     = $stock->min_qty;
            $product_stock->product_exp_date = $stock->product_exp_date;
            $product_stock->save();
        }
    }


    public function update(array $data, $product)
    {
        $collection = collect($data);

        $options = ProductUtility::get_attribute_options($collection);
        
        // Generates the combinations of customer choice options
        $combinations = (new CombinationService())->generate_combination($options);

        $variant = '';
        if (count($combinations) > 0) {
            $product->variant_product = 1;
            $product->save();

            foreach ($combinations as $key => $combination) {
                $str = ProductUtility::get_combination_string($combination, $collection);

                // Find existing product stock by variant or SKU
                $productStock = ProductStock::where('product_id', $product->id)
                    ->where('variant', $str)
                    ->first();

                if (!$productStock) {
                    // Optionally create new stock if not found
                    $productStock = new ProductStock();
                    $productStock->product_id = $product->id;
                    $productStock->variant = $str;
                }

                // Update the fields
                $productStock->mrp_price = request()->get('mrp_price_' . str_replace('.', '_', $str), null);
                // $productStock->mrp_role_price = generateRoleBasedPrices(request()['mrp_price_' . str_replace('.', '_', $str)]);

                // $productStock->price = request()['price_' . str_replace('.', '_', $str)];
                // $productStock->role_price = generateRoleBasedPrices(request()['price_' . str_replace('.', '_', $str)]);

                $productStock->length = request()->get('length_' . str_replace('.', '_', $str), null);
                $productStock->width = request()->get('width_' . str_replace('.', '_', $str), null);
                $productStock->height = request()->get('height_' . str_replace('.', '_', $str), null);

                $productStock->weight = request()->get('weight_' . str_replace('.', '_', $str), null);
                $productStock->count = request()->get('count_' . str_replace('.', '_', $str), null);
                $productStock->min_qty = request()->get('min_qty_' . str_replace('.', '_', $str), 1);
                $productStock->product_exp_date = request()->get('product_exp_date_' . str_replace('.', '_', $str), null);

                $productStock->sku = request()['sku_' . str_replace('.', '_', $str)];
                $productStock->qty = request()['qty_' . str_replace('.', '_', $str)];
                $productStock->coa = request()['coa_' . str_replace('.', '_', $str)];
                $productStock->image = request()['img_' . str_replace('.', '_', $str)];

                $productStock->save();

                // Update the product status if necessary
                // $product->published = 0;
                // $product->save();
            }
        } else {
            // Single variant case
            unset($collection['colors_active'], $collection['colors'], $collection['choice_no']);

            $variant = ''; // single variant

            $qty = $collection['current_stock'];
            $price = $collection['unit_price'];
            $mrp_price = $collection['mrp_price'] ?? null;
            $length = $collection['length'] ?? null;
            $width = $collection['width'] ?? null;
            $height = $collection['height'] ?? null;
            $weight = $collection['weight'] ?? null;
            $count = $collection['count'] ?? null;
            $min_qty = $collection['min_qty'] ?? 1;
            $product_exp_date = $collection['product_exp_date'] ?? null;

            unset($collection['current_stock']);
            // unset($collection['dimension']);
            unset($collection['mrp_price']);

            // Find existing stock entry if possible (based on SKU or product_id + variant)
            $sku = $collection['sku'] ?? null;
            $productStock = null;

            if ($sku) {
                $productStock = ProductStock::where('product_id', $product->id)
                    ->where('sku', $sku)
                    ->first();
            }

            if (!$productStock) {
                // If no existing stock, create one
                $productStock = new ProductStock();
                $productStock->product_id = $product->id;
                $productStock->variant = $variant;
                $productStock->sku = $sku;
            }

            // Update fields
            $productStock->qty = $qty;
            $productStock->price = $price;
            $productStock->mrp_price = $mrp_price;
            $productStock->length = $length;
            $productStock->width = $width;
            $productStock->height = $height;
            $productStock->weight = $weight;
            $productStock->count = $count;
            $productStock->min_qty = $min_qty;
            $productStock->product_exp_date = $product_exp_date;

            $productStock->save();
        }
    }

}
