<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Cart;
use App\Models\Product;
use App\Utility\CartUtility;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Delete cart items older than given hours.
     *
     * @param int $hours Default is 2 hours
     * @return int Number of deleted records
     */
    // public function deleteOldCartItems($hours = 2)
    // {
    //     return DB::table('carts')
    //         ->where('created_at', '<', Carbon::now()->subHours($hours))
    //         ->delete();
    // }


    /**
     * Delete all carts older than $hours, then re‑add them
     * with fresh prices — using only a local variable.
     *
     * @param int $hours
     * @return int  number of carts refreshed
     */
    public function refreshOldCartItems($hours = 2): int
    {
        // 1) Grab all “stale” carts into a local collection
        $threshold = Carbon::now()->subHours($hours);
        $oldCarts  = Cart::where('created_at', '<', $threshold)->get();

        if ($oldCarts->isEmpty()) {
            return 0;
        }

        // 2) Permanently delete them
        Cart::where('created_at', '<', $threshold)->delete();

        $refreshed = 0;

        // 3) Loop over the snapshot, recalc price & tax, re‑insert
        foreach ($oldCarts as $old) {
            // fetch product & variant
            $product = Product::find($old->product_id);
            $variant = $product
                ->stocks()
                ->when($old->id_variant, function ($query) use ($old) {
                    $query->where('id_variant', $old->id_variant);
                }, function ($query) use ($old) {
                    $query->where('variant', $old->variation);
                })
                ->first();

            if (! $product || ! $variant) {
                continue;
            }

            // recalc
            $batch = $old->batch_id && $variant
                ? $variant->batches()->where('id', $old->batch_id)->first()
                : null;
            $resolvedPrice = resolvePrice($product, $variant, $batch, $old->quantity);
            $newPrice = (float) ($resolvedPrice['price'] ?? 0);
            $newSalePrice = (float) ($resolvedPrice['sale_price'] ?? $newPrice);
            $newBeforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $newPrice);
            $newTax = CartUtility::tax_calculation($product, $newSalePrice);

            // re‑create exactly the same shape of row
            Cart::create([
                'status'               => $old->status,
                'owner_id'             => $old->owner_id,
                'user_id'              => $old->user_id,
                'temp_user_id'         => $old->temp_user_id,
                'address_id'           => $old->address_id,
                'product_id'           => $old->product_id,
                'variation'            => $old->variation,
                'id_variant'           => $old->id_variant ?? $variant->id_variant,
                'price'                => $newPrice,
                'before_productandbatch_discount' => $newBeforeProductAndBatchDiscount,
                'mrp_price'            => $old->mrp_price,
                'sale_price'           => $newSalePrice,
                'tax'                  => $newTax,
                'shipping_cost'        => $old->shipping_cost,
                'shipping_type'        => $old->shipping_type,
                'pickup_point'         => $old->pickup_point,
                'carrier_id'           => $old->carrier_id,
                'batch_id'             => $old->batch_id,
                'discount'             => $old->discount,
                'product_referral_code'=> $old->product_referral_code,
                'coupon_code'          => $old->coupon_code,
                'coupon_applied'       => $old->coupon_applied,
                'quantity'             => $old->quantity,
                'notify_attempt'       => $old->notify_attempt,
                'notify_date'          => now()->addHour(),      // reset notification
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            $refreshed++;
        }

        return $refreshed;
    }
}
