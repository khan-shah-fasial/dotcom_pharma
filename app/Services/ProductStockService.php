<?php

namespace App\Services;

use AizPackages\CombinationGenerate\Services\CombinationService;
use App\Models\ProductStock;
use App\Utility\ProductUtility;

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
                $product_stock->variant = $str;
                $product_stock->price = request()['price_' . str_replace('.', '_', $str)];
                $product_stock->role_price = generateRoleBasedPrices(request()['price_' . str_replace('.', '_', $str)]); //price by role

                $product_stock->per_piece_price = request()->get('per_piece_price_' . str_replace('.', '_', $str), null);

                $product_stock->sku = request()['sku_' . str_replace('.', '_', $str)];
                $product_stock->qty = request()['qty_' . str_replace('.', '_', $str)];
                $product_stock->image = request()['img_' . str_replace('.', '_', $str)];
                $product_stock->save();
            }
        } else {
            unset($collection['colors_active'], $collection['colors'], $collection['choice_no']);
            $qty = $collection['current_stock'];
            $price = $collection['unit_price'];
            $per_piece_price = $collection['per_piece_price'] ?? null;
            unset($collection['current_stock']);
            unset($collection['per_piece_price']);

            $data = $collection->merge(compact('variant', 'qty', 'price', 'per_piece_price'))->toArray();
            
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
            $product_stock->sku         = $stock->sku;
            $product_stock->qty         = $stock->qty;
            $product_stock->save();
        }
    }
}
