<?php

namespace App\Utility;

use App\Models\Cart;
use Cookie;
use App\Utility\ProductUtility;

class CartUtility
{

    public static function create_cart_variant($product, $request)
    {
        return self::create_cart_variant_data($product, $request)['variant'];
    }

    public static function create_cart_variant_data($product, $request): array
    {
        $str = null;
        $idParts = [];
        $hasUnresolvedAttribute = false;

        if (isset($request['color'])) {
            $str = $request['color'];
            $idParts[] = 'color_' . trim(preg_replace('/[^A-Za-z0-9_]/', '_', (string) $request['color']), '_');
        }

        if (isset($product->choice_options) && count(json_decode($product->choice_options)) > 0) {
            //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
            foreach (json_decode($product->choice_options) as $key => $choice) {
                $attributeKey = 'attribute_id_' . $choice->attribute_id;
                if (!isset($request[$attributeKey])) {
                    continue;
                }
                if ($str != null) {
                    $str .= '-' . str_replace(' ', '', $request[$attributeKey]);
                } else {
                    $str .= str_replace(' ', '', $request[$attributeKey]);
                }

                $valueId = ProductUtility::attribute_value_id($choice->attribute_id, $request[$attributeKey]);
                if ($valueId === null) {
                    $hasUnresolvedAttribute = true;
                }
                $idParts[] = $valueId;
            }
        }

        return [
            'variant' => $str,
            'id_variant' => $hasUnresolvedAttribute || empty($idParts) ? null : implode('-', $idParts),
        ];
    }

    public static function get_price($product, $product_stock, $quantity)
    {
        //$price = $product_stock->price;
        // IMPORTANT: role_price comes ONLY from batches, NOT from stock
        // Use batch-aware pricing helper which checks batches first, then falls back to product-level
        $price = getStockPriceByRole($product_stock, $product, false);
        if ($price === null || $price === 0) {
            // Fallback to product-level role_price (NOT stock-level)
            $price = getPriceByRole($product->role_price ?? null, $product_stock->price ?? 0); //price by role
        }
        if ($product->auction_product == 1) {
            $price = $product->bids->max('amount');
        }

        if ($product->wholesale_product) {
            $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $quantity)
                ->where('max_qty', '>=', $quantity)
                ->first();
            if ($wholesalePrice) {
                $price = $wholesalePrice->price;
            }
        }

        $price = self::discount_calculation($product, $price);
        return $price;
    }

    public static function get_price_from_batch($product, $batch, $quantity)
    {
        if (!$batch) {
            return 0;
        }
        
        // Calculate price from batch MRP using role_price
        $mrpPrice = $batch->mrp_price ?? 0;
        $rolePrice = $batch->role_price ?? null;
        
        // Load stock relationship if not loaded
        if (!$batch->relationLoaded('stock')) {
            $batch->load('stock');
        }
        
        $product_stock = $batch->stock;
        
        // Use role_price from batch if available, otherwise fallback to product-level role_price (NOT stock-level)
        if ($rolePrice) {
            $rolePriceArray = is_string($rolePrice) ? json_decode($rolePrice, true) : $rolePrice;
            if (is_array($rolePriceArray)) {
                $price = getPriceByRole($rolePriceArray, $mrpPrice);
            } else {
                // Invalid role_price format, fallback to product-level (NOT stock-level)
                $price = $product ? getPriceByRole($product->role_price ?? null, $product_stock->price ?? $mrpPrice) : $mrpPrice;
            }
        } else {
            // Batch has no role_price, fallback to product-level (NOT stock-level)
            $price = $product ? getPriceByRole($product->role_price ?? null, $product_stock->price ?? $mrpPrice) : $mrpPrice;
        }
        
        if ($product->auction_product == 1) {
            $price = $product->bids->max('amount');
        }

        if ($product->wholesale_product && $product_stock) {
            $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $quantity)
                ->where('max_qty', '>=', $quantity)
                ->first();
            if ($wholesalePrice) {
                $price = $wholesalePrice->price;
            }
        }

        $price = self::discount_calculation($product, $price);
        return $price;
    }

    public static function discount_calculation($product, $price)
    {
        $discount_applicable = false;

        if (
            $product->discount_start_date == null ||
            (strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date)
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }
        return $price;
    }

    public static function tax_calculation($product, $price)
    {
        $tax = 0;
        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }

        return $tax;
    }

    public static function save_cart_data($cart, $product, $price, $tax, $quantity, $mrpPrice = null, $salePrice = null, $batchId = null, $isScheme = false, $beforeProductAndBatchDiscount = null)
    {
        if ($cart->status === null) {
            $cart->status = 1;
        }
        $cart->quantity = $quantity;
        $cart->is_scheme = (bool) $isScheme;
        $cart->product_id = $product->id;
        $cart->owner_id = $product->user_id;
        $cart->price = $price;
        $cart->before_productandbatch_discount = (bool) $isScheme ? 0 : ($beforeProductAndBatchDiscount ?? $salePrice ?? $price);
        $cart->mrp_price = $mrpPrice;
        $cart->sale_price = $salePrice ?? $price;
        $cart->tax = $tax;
        $cart->batch_id = $batchId;
        $cart->product_referral_code = null;

        if (Cookie::has('referred_product_id') && Cookie::get('referred_product_id') == $product->id) {
            $cart->product_referral_code = Cookie::get('product_referral_code');
        }

        // Cart::create($data);
        $cart->save();
    }

    public static function check_auction_in_cart($carts)
    {
        foreach ($carts as $cart) {
            if ($cart->product->auction_product == 1) {
                return true;
            }
        }

        return false;
    }
}
