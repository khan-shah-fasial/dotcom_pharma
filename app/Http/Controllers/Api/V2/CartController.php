<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Utility\CartUtility;
use App\Utility\NagadUtility;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected function cartOwnerIdentity(Cart $cart): array
    {
        return $cart->user_id
            ? ['user_id' => $cart->user_id]
            : ['temp_user_id' => $cart->temp_user_id];
    }

    protected function refreshAppliedCouponForCarts($carts): void
    {
        $couponApplied = collect($carts)->firstWhere('coupon_applied', 1);
        if (!$couponApplied || empty($couponApplied->coupon_code)) {
            return;
        }

        $coupon = Coupon::where('code', $couponApplied->coupon_code)->first();
        $ownerId = $couponApplied->owner_id;
        $cartQuery = $couponApplied->user_id
            ? Cart::where('user_id', $couponApplied->user_id)
            : Cart::where('temp_user_id', $couponApplied->temp_user_id);

        (clone $cartQuery)->where('owner_id', $ownerId)->update([
            'discount' => 0,
            'coupon_code' => null,
            'coupon_applied' => 0,
        ]);

        if (!$coupon) {
            return;
        }

        $activeOwnerCarts = (clone $cartQuery)->where('owner_id', $ownerId)->active()->get();
        $couponResult = coupon_cart_discount_allocations($coupon, $activeOwnerCarts, json_decode($coupon->details));

        foreach ($activeOwnerCarts as $cartItem) {
            $lineDiscount = $couponResult['allocations'][(int) $cartItem->id] ?? 0;
            if ($lineDiscount <= 0) {
                continue;
            }

            $cartItem->discount = $lineDiscount;
            $cartItem->coupon_code = $coupon->code;
            $cartItem->coupon_applied = 1;
            $cartItem->save();
        }
    }

    protected function schemeCartQuery(Cart $paidCart)
    {
        $query = Cart::where('product_id', $paidCart->product_id)
            ->where('variation', $paidCart->variation)
            ->where('is_scheme', 1);

        foreach ($this->cartOwnerIdentity($paidCart) as $column => $value) {
            $query->where($column, $value);
        }

        return $query;
    }

    protected function paidCartQuery(Cart $paidCart)
    {
        $query = Cart::where('product_id', $paidCart->product_id)
            ->where('variation', $paidCart->variation)
            ->where('is_scheme', 0);

        foreach ($this->cartOwnerIdentity($paidCart) as $column => $value) {
            $query->where($column, $value);
        }

        return $query;
    }

    protected function getSchemePreview(Cart $paidCart, Product $product, $stock, int $newPaidQty, $newPaidBatchId): array
    {
        $paidCarts = $this->paidCartQuery($paidCart)
            ->when($paidCart->exists, function ($query) use ($paidCart) {
                $query->where('id', '!=', $paidCart->id);
            })
            ->get();

        $totalPaidQty = $paidCarts->sum('quantity') + $newPaidQty;
        $reservations = [];
        foreach ($paidCarts as $cart) {
            if ($cart->batch_id) {
                $reservations[(int) $cart->batch_id] = ($reservations[(int) $cart->batch_id] ?? 0) + (int) $cart->quantity;
            }
        }
        if ($newPaidBatchId) {
            $reservations[(int) $newPaidBatchId] = ($reservations[(int) $newPaidBatchId] ?? 0) + $newPaidQty;
        }

        $minQty = optional($stock)->min_qty ?? $product->min_qty ?? 1;
        $schemeQty = calculate_scheme_qty($totalPaidQty, $minQty, (int) (optional($stock)->scheme ?? 0));

        return [
            'scheme_qty' => $schemeQty,
            'allocation' => allocate_scheme_free_batches($stock, $schemeQty, $reservations),
        ];
    }

    protected function syncSchemeCartLinesForStock(Cart $paidCart, Product $product, $stock): bool
    {
        $this->schemeCartQuery($paidCart)->delete();

        $paidCarts = $this->paidCartQuery($paidCart)->get();
        $totalPaidQty = (int) $paidCarts->sum('quantity');
        $minQty = optional($stock)->min_qty ?? $product->min_qty ?? 1;
        $schemeQty = calculate_scheme_qty($totalPaidQty, $minQty, (int) (optional($stock)->scheme ?? 0));

        if ($schemeQty <= 0) {
            return true;
        }

        $reservations = [];
        foreach ($paidCarts as $cart) {
            if ($cart->batch_id) {
                $reservations[(int) $cart->batch_id] = ($reservations[(int) $cart->batch_id] ?? 0) + (int) $cart->quantity;
            }
        }

        $allocation = allocate_scheme_free_batches($stock, $schemeQty, $reservations);
        if (!$allocation['success']) {
            return false;
        }

        foreach ($allocation['allocations'] as $row) {
            $identity = array_merge($this->cartOwnerIdentity($paidCart), [
                'variation' => $paidCart->variation,
                'product_id' => $paidCart->product_id,
                'batch_id' => $row['batch_id'],
                'is_scheme' => 1,
            ]);

            $schemeCart = Cart::firstOrNew($identity);
            $schemeCart->quantity = (int) $row['quantity'];
            $schemeCart->owner_id = $product->user_id;
            $schemeCart->price = 0;
            $schemeCart->before_productandbatch_discount = 0;
            $schemeCart->mrp_price = 0;
            $schemeCart->sale_price = 0;
            $schemeCart->tax = 0;
            $schemeCart->shipping_cost = 0;
            $schemeCart->discount = 0;
            $schemeCart->coupon_code = '';
            $schemeCart->coupon_applied = 0;
            $schemeCart->status = $paidCart->status ?? 1;
            $schemeCart->address_id = $paidCart->address_id ?? 0;
            $schemeCart->product_referral_code = null;
            $schemeCart->save();
        }

        return true;
    }

    public function summary(Request $request)
    {
        // $user  = auth()->user();
        $user  = $request->user_id != null ? User::where('id', $request->user_id)->first() : null;
        $items = ($user != null) ?
                Cart::where('user_id', $user->id)->active()->get() :
                ($request->has('temp_user_id') ? Cart::where('temp_user_id', $request->temp_user_id)->active()->get() : [] );

        if ($items->isEmpty()) {
            return response()->json([
                'sub_total' => format_price(0.00),
                'tax' => format_price(0.00),
                'shipping_cost' => format_price(0.00),
                'discount' => format_price(0.00),
                'grand_total' => format_price(0.00),
                'grand_total_value' => 0.00,
                'coupon_code' => "",
                'coupon_applied' => false,
            ]);
        }

        $sum = 0.00;
        $subtotal = 0.00;
        $tax = 0.00;
        foreach ($items as $cartItem) {
            $product = Product::find($cartItem['product_id']);
            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
            $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
        }

        $shipping_cost = $items->sum('shipping_cost');
        $discount = $items->sum('discount');
        $sum = ($subtotal + $tax + $shipping_cost) - $discount;
        $couponAppliedItem = $items->firstWhere('coupon_applied', 1);

        return response()->json([
            'sub_total' => single_price($subtotal),
            'tax' => single_price($tax),
            'shipping_cost' => single_price($shipping_cost),
            'discount' => single_price($discount),
            'grand_total' => single_price($sum),
            'grand_total_value' => convert_price($sum),
            'coupon_code' => $couponAppliedItem ? $couponAppliedItem->coupon_code : "",
            'coupon_applied' => $couponAppliedItem != null,
        ]);
    }

    public function count(Request $request)
    {
        $user_id = $request->user_id;
        $temp_user_id = $request->temp_user_id;
        $items  = ($user_id != null) ?
                    Cart::where('user_id', $user_id)->active()->get() :
                    ($temp_user_id != null ? Cart::where('temp_user_id', $temp_user_id)->active()->get() : [] );

        return response()->json([
            'count' => sizeof($items),
            'status' => true,
        ]);
    }

    public function getList(Request $request)
    {
        $user_id = $request->user_id;
        $temp_user_id = $request->temp_user_id;

        $owner_ids = ($user_id != null) ?
            Cart::where('user_id', $user_id)->active()->select('owner_id')->groupBy('owner_id')->pluck('owner_id')->toArray() :
            ($temp_user_id != null ? Cart::where('temp_user_id', $temp_user_id)->active()->select('owner_id')->groupBy('owner_id')->pluck('owner_id')->toArray() : [] );


        $currency_symbol = currency_symbol();
        $shops = [];
        $sub_total = 0.00;
        $grand_total = 0.00;
        if (!empty($owner_ids)) {
            foreach ($owner_ids as $owner_id) {
                $shop = array();
                $shop_items_raw_data = ($user_id != null) ?
                    Cart::where('user_id', $user_id)->where('owner_id', $owner_id)->active()->get()->toArray() :
                    ($temp_user_id != null ? Cart::where('temp_user_id', $temp_user_id)->where('owner_id', $owner_id)->active()->get()->toArray() : [] );
                $shop_items_data = array();
                if (!empty($shop_items_raw_data)) {
                    foreach ($shop_items_raw_data as $shop_items_raw_data_item) {
                        $product = Product::where('id', $shop_items_raw_data_item["product_id"])->first();
                        $price = cart_product_price($shop_items_raw_data_item, $product, false, false) * intval($shop_items_raw_data_item["quantity"]);
                        $tax = cart_product_tax($shop_items_raw_data_item, $product, false);
                        $shop_items_data_item["id"] = intval($shop_items_raw_data_item["id"]);
                        $shop_items_data_item["status"] = intval($shop_items_raw_data_item["status"]);
                        $shop_items_data_item["owner_id"] = intval($shop_items_raw_data_item["owner_id"]);
                        $shop_items_data_item["user_id"] = intval($shop_items_raw_data_item["user_id"]);
                        $shop_items_data_item["product_id"] = intval($shop_items_raw_data_item["product_id"]);
                        $shop_items_data_item["product_name"] = $product->getTranslation('name');
                        $shop_items_data_item["auction_product"] = $product->auction_product;
                        $shop_items_data_item["product_thumbnail_image"] = uploaded_asset($product->thumbnail_img);
                        $shop_items_data_item["variation"] = $shop_items_raw_data_item["variation"];
                        $shop_items_data_item["price"] = (float) cart_product_price($shop_items_raw_data_item, $product, false, false);
                        $shop_items_data_item["currency_symbol"] = $currency_symbol;
                        $shop_items_data_item["tax"] = (float) cart_product_tax($shop_items_raw_data_item, $product, false);
                        $shop_items_data_item["price"] = single_price($price);
                        $shop_items_data_item["currency_symbol"] = $currency_symbol;
                        $shop_items_data_item["tax"] = single_price($tax);
                        $shop_items_data_item["shipping_cost"] = (float) $shop_items_raw_data_item["shipping_cost"];
                        $shop_items_data_item["quantity"] = intval($shop_items_raw_data_item["quantity"]);
                        $shop_items_data_item["is_scheme"] = (bool) ($shop_items_raw_data_item["is_scheme"] ?? false);
                        $cartStock = $product->stocks->where('variant', $shop_items_raw_data_item['variation'])->first();
                        $shop_items_data_item["lower_limit"] = intval($cartStock->min_qty ?? $product->min_qty ?? 1);
                        if (!empty($shop_items_raw_data_item['batch_id']) && $cartStock) {
                            $cartBatch = $cartStock->batches()->where('id', $shop_items_raw_data_item['batch_id'])->first();
                            $shop_items_data_item["upper_limit"] = $cartBatch && is_batch_usable_for_sale($cartBatch)
                                ? intval($cartBatch->qty)
                                : 0;
                        } elseif ($cartStock) {
                            $cartStock->load('batches');
                            $validBatches = valid_batches_for_stock($cartStock, true);
                            $shop_items_data_item["upper_limit"] = $cartStock->batches->isNotEmpty()
                                ? intval($validBatches->sum('qty'))
                                : intval(optional($cartStock)->qty);
                        } else {
                            $shop_items_data_item["upper_limit"] = 0;
                        }

                        $sub_total += $price + $tax;
                        $shop_items_data[] = $shop_items_data_item;
                    }
                }

                $grand_total += $sub_total;
                $shop_data = Shop::where('user_id', $owner_id)->first();
                if ($shop_data) {
                    $shop['name'] = translate($shop_data->name);
                    $shop['owner_id'] = (int) $owner_id;
                    $shop['sub_total'] = single_price($sub_total);
                    $shop['cart_items'] = $shop_items_data;
                } else {
                    $shop['name'] = translate("Inhouse");
                    $shop['owner_id'] = (int) $owner_id;
                    $shop['sub_total'] = single_price($sub_total);
                    $shop['cart_items'] = $shop_items_data;
                }
                $shops[] = $shop;
                $sub_total = 0.00;
            }
        }

        return response()->json([
            "grand_total" => single_price($grand_total),
            "data" => $shops
        ]);
    }

    public function add(Request $request)
    {
        $user_id =  $request->user_id != null ? $request->user_id : null;
        $temp_user_id =   $request->temp_user_id != null ? $request->temp_user_id : null;
        if($user_id != null) {
            $carts = Cart::where('user_id', $user_id)->active()->get();
        }
        else {
            if($temp_user_id == null){
                $temp_user_id = bin2hex(random_bytes(10));
            }
            $carts = Cart::where('temp_user_id', $temp_user_id)->active()->get();
        }

        $check_auction_in_cart = CartUtility::check_auction_in_cart($carts);
        $product = Product::findOrFail($request->id);

        if ($check_auction_in_cart && $product->auction_product == 0) {
            return response()->json([
                'result' => false,
                'temp_user_id' => $temp_user_id,
                'message' => translate('Remove auction product from cart to add this product.')
            ], 200);
        }
        if ($check_auction_in_cart == false && count($carts) > 0 && $product->auction_product == 1) {
            return response()->json([
                'result' => false,
                'temp_user_id' => $temp_user_id,
                'message' => translate('Remove other products from cart to add this auction product.')
            ], 200);
        }

        $variant = $request->variant;
        $tax = 0;
        $quantity = $request->quantity;

        $product_stock = $product->stocks->where('variant', $variant)->first();
        if (!$product_stock) {
            return response()->json([
                'result' => false,
                'temp_user_id' => $temp_user_id,
                'message' => translate("Stock out")
            ], 200);
        }

        $minQty = optional($product_stock)->min_qty ?? $product->min_qty ?? 1;
        $batchId = $request->input('batch_id', null);
        $selectedBatch = null;
        if ($batchId && $product_stock) {
            $selectedBatch = $product_stock->batches()->where('id', $batchId)->first();
            if (!$selectedBatch || is_batch_expired($selectedBatch)) {
                return response()->json([
                    'result' => false,
                    'temp_user_id' => $temp_user_id,
                    'message' => translate("Stock out")
                ], 200);
            }
        }

        if ($quantity < $minQty) {
            return response()->json([
                'result' => false,
                'temp_user_id' => $temp_user_id,
                'message' => translate("Minimum") . " {$minQty} " . translate("item(s) should be ordered")
            ], 200);
        }

        if($user_id != null) {
            $cart = Cart::firstOrNew([
                'variation' => $variant,
                'user_id' => $user_id,
                'product_id' => $request['id'],
                'batch_id' => $batchId,
                'is_scheme' => 0
            ]);
        } else {
            $cart = Cart::firstOrNew([
                'variation' => $variant,
                'temp_user_id' => $temp_user_id,
                'product_id' => $request['id'],
                'batch_id' => $batchId,
                'is_scheme' => 0
            ]);
        }


        $variant_string = $variant != null && $variant != "" ? translate("for") . " ($variant)" : "";

        if ($cart->exists && $product->digital == 0) {
            if ($product->auction_product == 1 && ($cart->product_id == $product->id)) {
                return response()->json([
                    'result' => false,
                    'message' => translate('This auction product is already added to your cart.')
                ], 200);
            }
            if ($product->digital == 1 && ($cart->product_id == $product->id)) {
                return response()->json([
                    'result' => false,
                    'temp_user_id' => $temp_user_id,
                    'message' => translate('Already added this product')
                ]);
            }
            $quantity = $cart->quantity + $request['quantity'];

            if ($quantity < $minQty) {
                return response()->json([
                    'result' => false,
                    'temp_user_id' => $temp_user_id,
                    'message' => translate("Minimum") . " {$minQty} " . translate("item(s) should be ordered")
                ], 200);
            }
        }

        if ($selectedBatch) {
            $availableQty = (int) ($selectedBatch->qty ?? 0);
        } else {
            $product_stock->load('batches');
            $validBatches = valid_batches_for_stock($product_stock, true);
            $availableQty = $product_stock->batches->isNotEmpty() ? (int) $validBatches->sum('qty') : (int) optional($product_stock)->qty;
        }

        if ($product->digital == 0 && $availableQty < $quantity) {
            return response()->json([
                'result' => false,
                'temp_user_id' => $temp_user_id,
                'message' => $availableQty == 0
                    ? translate("Stock out")
                    : translate("Only") . " {$availableQty} " . translate("item(s) are available") . " {$variant_string}"
            ], 200);
        }

        $schemePreview = $this->getSchemePreview($cart, $product, $product_stock, (int) $quantity, $batchId);
        $schemeQty = (int) $schemePreview['scheme_qty'];
        if ($product->digital == 0 && $schemeQty > 0 && !$schemePreview['allocation']['success']) {
            return response()->json([
                'result' => false,
                'temp_user_id' => $temp_user_id,
                'message' => translate("Stock out")
            ], 200);
        }

        if ($selectedBatch) {
            $resolvedPrice = resolvePrice($product, $product_stock, $selectedBatch, $request->quantity);
            $price = (float) ($resolvedPrice['price'] ?? 0);
            $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $price);
            $salePrice = (float) ($resolvedPrice['sale_price'] ?? $price);
            $mrpPrice = $selectedBatch->mrp_price ?? $product_stock->mrp_price ?? $product->mrp_price;
        } else {
            $resolvedPrice = resolvePrice($product, $product_stock, null, $request->quantity);
            $price = (float) ($resolvedPrice['price'] ?? 0);
            $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $price);
            $salePrice = (float) ($resolvedPrice['sale_price'] ?? $price);
            $mrpPrice = $product_stock->mrp_price ?? $product->mrp_price;
        }
        $tax = CartUtility::tax_calculation($product, $salePrice);
        try {
            \DB::transaction(function () use ($cart, $product, $price, $tax, $quantity, $mrpPrice, $salePrice, $batchId, $product_stock, $beforeProductAndBatchDiscount) {
                CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity, $mrpPrice, $salePrice, $batchId, false, $beforeProductAndBatchDiscount);

                if (!$this->syncSchemeCartLinesForStock($cart, $product, $product_stock)) {
                    throw new \RuntimeException('Unable to allocate scheme stock.');
                }
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'result' => false,
                'temp_user_id' => $temp_user_id,
                'message' => translate("Stock out")
            ], 200);
        }

        if (NagadUtility::create_balance_reference($request->cost_matrix) == false) {
            return response()->json(['result' => false, 'message' => 'Cost matrix error']);
        }

        return response()->json([
            'result' => true,
            'temp_user_id' => $temp_user_id,
            'message' => translate('Product added to cart successfully')
        ]);
    }
    public function changeQuantity(Request $request)
    {
        $cart = Cart::find($request->id);
        if ($cart != null) {
            $product = Product::find($cart->product_id);
            if ($product->auction_product == 1) {
                return response()->json(['result' => false, 'message' => translate('Maximum available quantity reached')], 200);
            }
            if ((bool) $cart->is_scheme) {
                return response()->json(['result' => false, 'message' => translate('Scheme quantity cannot be changed')], 200);
            }
            $stock = $cart->product->stocks->where('variant', $cart->variation)->first();
            $minQty = optional($stock)->min_qty ?? $product->min_qty ?? 1;
            $batch = $cart->batch_id && $stock ? $stock->batches()->where('id', $cart->batch_id)->first() : null;
            if ($batch && is_batch_expired($batch)) {
                $batch = null;
                $availableQty = 0;
            } elseif ($batch) {
                $availableQty = (int) ($batch->qty ?? 0);
            } elseif ($stock) {
                $stock->load('batches');
                $validBatches = valid_batches_for_stock($stock, true);
                $availableQty = $stock->batches->isNotEmpty() ? (int) $validBatches->sum('qty') : (int) optional($stock)->qty;
            } else {
                $availableQty = 0;
            }

            $schemePreview = $this->getSchemePreview($cart, $product, $stock, (int) $request->quantity, $cart->batch_id);

            if ($availableQty >= (int) $request->quantity && $schemePreview['allocation']['success']) {
                $resolvedPrice = resolvePrice($product, $stock, $batch, (int) $request->quantity);
                $price = (float) ($resolvedPrice['price'] ?? 0);
                $salePrice = (float) ($resolvedPrice['sale_price'] ?? $price);
                $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $price);
                $tax = CartUtility::tax_calculation($product, $salePrice);

                $cart->update([
                    'quantity' => $request->quantity,
                    'price' => $price,
                    'before_productandbatch_discount' => $beforeProductAndBatchDiscount,
                    'sale_price' => $salePrice,
                    'tax' => $tax,
                ]);
                $this->syncSchemeCartLinesForStock($cart->fresh(), $product, $stock);
                $ownerCarts = $cart->user_id
                    ? Cart::where('user_id', $cart->user_id)->get()
                    : Cart::where('temp_user_id', $cart->temp_user_id)->get();
                $this->refreshAppliedCouponForCarts($ownerCarts);

                return response()->json(['result' => true, 'message' => translate('Cart updated')], 200);
            } else {
                return response()->json(['result' => false, 'message' => translate('Maximum available quantity reached')], 200);
            }
        }

        return response()->json(['result' => false, 'message' => translate('Something went wrong')], 200);
    }

    public function process(Request $request)
    {
        $cart_ids = explode(",", $request->cart_ids);
        $cart_quantities = explode(",", $request->cart_quantities);

        if (!empty($cart_ids)) {
            $i = 0;
            foreach ($cart_ids as $cart_id) {
                $cart_item = Cart::where('id', $cart_id)->first();
                if (!$cart_item || (bool) ($cart_item->is_scheme ?? false)) {
                    $i++;
                    continue;
                }
                $product = Product::where('id', $cart_item->product_id)->first();

                $stockEntry = $cart_item->product->stocks->where('variant', $cart_item->variation)->first();
                $stockMinQty = $stockEntry->min_qty ?? $product->min_qty ?? 1;

                if ($stockMinQty > $cart_quantities[$i]) {
                    return response()->json(['result' => false, 'message' => translate("Minimum") . " {$stockMinQty} " . translate("item(s) should be ordered for") . " {$product->name}"], 200);
                }

                $stock = optional($stockEntry)->qty;
                if (!empty($cart_item->batch_id) && $stockEntry) {
                    $batch = $stockEntry->batches()->where('id', $cart_item->batch_id)->first();
                    $stock = ($batch && !is_batch_expired($batch)) ? (int) ($batch->qty ?? 0) : 0;
                } elseif ($stockEntry) {
                    $stockEntry->load('batches');
                    $validBatches = valid_batches_for_stock($stockEntry, true);
                    $stock = $stockEntry->batches->isNotEmpty() ? (int) $validBatches->sum('qty') : (int) optional($stockEntry)->qty;
                }
                $variant_string = $cart_item->variation != null && $cart_item->variation != "" ? " ($cart_item->variation)" : "";
                if (($stock ?? 0) >= $cart_quantities[$i] || $product->digital == 1) {
                    $schemePreview = $this->getSchemePreview($cart_item, $product, $stockEntry, (int) $cart_quantities[$i], $cart_item->batch_id);
                    if ($product->digital != 1 && !$schemePreview['allocation']['success']) {
                        return response()->json(['result' => false, 'message' => translate("Maximum available quantity reached")], 200);
                    }
                    $selectedBatch = null;
                    if (!empty($cart_item->batch_id) && $stockEntry) {
                        $selectedBatch = $stockEntry->batches()->where('id', $cart_item->batch_id)->first();
                    }
                    $resolvedPrice = resolvePrice($product, $stockEntry, $selectedBatch, (int) $cart_quantities[$i]);
                    $price = (float) ($resolvedPrice['price'] ?? 0);
                    $salePrice = (float) ($resolvedPrice['sale_price'] ?? $price);
                    $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $price);
                    $tax = CartUtility::tax_calculation($product, $salePrice);

                    $cart_item->update([
                        'quantity' => $cart_quantities[$i],
                        'price' => $price,
                        'before_productandbatch_discount' => $beforeProductAndBatchDiscount,
                        'sale_price' => $salePrice,
                        'tax' => $tax,
                    ]);
                    $this->syncSchemeCartLinesForStock($cart_item->fresh(), $product, $stockEntry);
                } else {
                    if ($stock == 0) {
                        return response()->json(['result' => false, 'message' => translate("No item is available for") . " {$product->name}{$variant_string}," . translate("remove this from cart")], 200);
                    } else {
                        return response()->json(['result' => false, 'message' => translate("Only") . " {$stock} " . translate("item(s) are available for") . " {$product->name}{$variant_string}"], 200);
                    }
                }

                $i++;
            }

            $firstCart = Cart::where('id', $cart_ids[0])->first();
            if ($firstCart) {
                $ownerCarts = $firstCart->user_id
                    ? Cart::where('user_id', $firstCart->user_id)->get()
                    : Cart::where('temp_user_id', $firstCart->temp_user_id)->get();
                $this->refreshAppliedCouponForCarts($ownerCarts);
            }

            return response()->json(['result' => true, 'message' => translate('Cart updated')], 200);
        } else {
            return response()->json(['result' => false, 'message' => translate('Cart is empty')], 200);
        }
    }

    public function destroy($id)
    {
        $cart = Cart::find($id);
        if ($cart && !(bool) $cart->is_scheme) {
            $product = Product::find($cart->product_id);
            $stock = $product ? $product->stocks()->where('variant', $cart->variation)->first() : null;
            Cart::destroy($id);
            if ($product && $stock && $this->paidCartQuery($cart)->exists()) {
                $this->syncSchemeCartLinesForStock($cart, $product, $stock);
            } else {
                $this->schemeCartQuery($cart)->delete();
            }
            $ownerCarts = $cart->user_id
                ? Cart::where('user_id', $cart->user_id)->get()
                : Cart::where('temp_user_id', $cart->temp_user_id)->get();
            $this->refreshAppliedCouponForCarts($ownerCarts);
        } else {
            Cart::destroy($id);
        }
        return response()->json(['result' => true, 'message' => translate('Product is successfully removed from your cart')], 200);
    }

    public function guestCustomerInfoCheck(Request $request){
        $user = addon_is_activated('otp_system') ?
                User::where('email', $request->email)->orWhere('phone','+'.$request->phone)->first() :
                User::where('email', $request->email)->first();

        return response()->json([
            'result' => ($user != null) ? true : false
        ]);
    }

    public function updateCartStatus(Request $request)
    {
        $product_ids = $request->product_ids;
        $user_id = $request->user_id;
        $temp_user_id = $request->temp_user_id;
        $carts  = ($user_id != null) ?
                    Cart::where('user_id', $user_id)->get() :
                    ($temp_user_id != null ? Cart::where('temp_user_id', $temp_user_id)->get() : [] );

        $carts->toQuery()->update(['status' => 0]);
        if($product_ids != null){
            $carts->toQuery()->whereIn('product_id', $product_ids)->where('is_scheme', 0)->update(['status' => 1]);
            $activePaidCarts = $carts->whereIn('product_id', $product_ids)->where('is_scheme', 0);
            foreach ($activePaidCarts as $paidCart) {
                $schemeQuery = $carts->toQuery()
                    ->where('product_id', $paidCart->product_id)
                    ->where('variation', $paidCart->variation)
                    ->where('is_scheme', 1);

                if ($paidCart->user_id) {
                    $schemeQuery->where('user_id', $paidCart->user_id);
                } else {
                    $schemeQuery->where('temp_user_id', $paidCart->temp_user_id);
                }

                $schemeQuery->update(['status' => 1]);
            }
        }

        return response()->json([
            'result' => true,
            'message' => translate('Cart status updated successfully')
        ]);
    }
}
