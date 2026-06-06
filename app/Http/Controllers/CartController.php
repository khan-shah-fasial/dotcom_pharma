<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Carrier;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Carbon\Carbon;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\ProductBatch;
use Auth;
use App\Utility\CartUtility;
use Session;
use Cookie;

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

        $ownerCartsQuery = (clone $cartQuery)->where('owner_id', $ownerId);
        $ownerCartsQuery->update([
            'discount' => 0,
            'coupon_code' => null,
            'coupon_applied' => 0,
        ]);

        if (!$coupon) {
            return;
        }

        $activeOwnerCarts = (clone $cartQuery)->where('owner_id', $ownerId)->active()->get();
        $userCoupon = ($coupon->type === 'welcome_base' && auth()->user())
            ? auth()->user()->userCoupon
            : null;
        $couponResult = coupon_cart_discount_allocations($coupon, $activeOwnerCarts, json_decode($coupon->details), $userCoupon);

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
            ->where('is_scheme', 1);

        if ($paidCart->id_variant) {
            $query->where('id_variant', $paidCart->id_variant);
        } else {
            $query->where('variation', $paidCart->variation);
        }

        foreach ($this->cartOwnerIdentity($paidCart) as $column => $value) {
            $query->where($column, $value);
        }

        return $query;
    }

    protected function paidCartQuery(Cart $paidCart)
    {
        $query = Cart::where('product_id', $paidCart->product_id)
            ->where('is_scheme', 0);

        if ($paidCart->id_variant) {
            $query->where('id_variant', $paidCart->id_variant);
        } else {
            $query->where('variation', $paidCart->variation);
        }

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
        $schemePerMinQty = (int) (optional($stock)->scheme ?? 0);
        $schemeQty = calculate_scheme_qty($totalPaidQty, $minQty, $schemePerMinQty);
        $allocation = allocate_scheme_free_batches($stock, $schemeQty, $reservations);

        return [
            'scheme_qty' => $schemeQty,
            'allocation' => $allocation,
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
                'id_variant' => $paidCart->id_variant,
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

    protected function deleteSchemeCartLine(Cart $paidCart): void
    {
        $this->schemeCartQuery($paidCart)->delete();
    }

    protected function calculateDisplayLineTotal($product, float $unitSalePrice, int $quantity): float
    {
        $safeQty = max(1, (int) $quantity);
        $displayUnitPrice = round($unitSalePrice, 2);
        $displayUnitTax = 0.0;

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $displayUnitTax += ($displayUnitPrice * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $displayUnitTax += (float) $product_tax->tax;
            }
        }

        $displayUnitPriceWithTax = round($displayUnitPrice + $displayUnitTax, 2);
        return round($displayUnitPriceWithTax * $safeQty, 2);
    }

    public function index(Request $request)
    {
        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            if ($request->session()->get('temp_user_id')) {
                Cart::where('temp_user_id', $request->session()->get('temp_user_id'))
                    ->update(
                        [
                            'user_id' => $user_id,
                            'temp_user_id' => null
                        ]
                    );

                Session::forget('temp_user_id');
            }
            $carts = Cart::where('user_id', $user_id)->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = ($temp_user_id != null) ? Cart::where('temp_user_id', $temp_user_id)->get() : [];
        }
        if (count($carts) > 0) {
            $carts->toQuery()->update(['shipping_cost' => 0]);
            $carts = $carts->fresh();
        }

        return view('frontend.view_cart', compact('carts'));
    }

    public function showCartModal(Request $request)
    {
        $product = Product::find($request->id);
        return view('frontend.partials.cart.addToCart', compact('product'));
    }

    public function showCartModalAuction(Request $request)
    {
        $product = Product::find($request->id);
        return view('auction.frontend.addToCartAuction', compact('product'));
    }

    public function addToCart(Request $request)
    {
        $authUser = auth()->user();
        if($authUser != null) {
            $user_id = $authUser->id;
            $data['user_id'] = $user_id;
            $carts = Cart::where('user_id', $user_id)->get();
        } else {
            if($request->session()->get('temp_user_id')) {
                $temp_user_id = $request->session()->get('temp_user_id');
            } else {
                $temp_user_id = bin2hex(random_bytes(10));
                $request->session()->put('temp_user_id', $temp_user_id);
            }
            $data['temp_user_id'] = $temp_user_id;
            $carts = Cart::where('temp_user_id', $temp_user_id)->get();
        }

        $check_auction_in_cart = CartUtility::check_auction_in_cart($carts);
        $product = Product::find($request->id);
        $carts = array();

        if($check_auction_in_cart && $product->auction_product == 0) {
            return array(
                'status' => 0,
                'cart_count' => count($carts),
                'modal_view' => view('frontend.partials.cart.removeAuctionProductFromCart')->render(),
                'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
            );
        }

        $quantity = $request['quantity'];

        //check the color enabled or disabled for the product
        $cartVariantData = CartUtility::create_cart_variant_data($product, $request->all());
        $str = $cartVariantData['variant'];
        $product_stock = $product->stocks()->where('variant', $str)->where('is_hidden', 0)->first();
        if (!$product_stock) {
            return array(
                'status' => 0,
                'cart_count' => count($carts),
                'modal_view' => view('frontend.partials.outOfStockCart')->render(),
                'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
            );
        }
        $idVariant = $product_stock->id_variant ?? $cartVariantData['id_variant'];

        // Get batch ID from request if provided
        $batchId = $request->input('batch_id', null);
        $selectedBatch = null;
        
        // Load batches relationship if product_stock exists
        if ($product_stock) {
            try {
                $product_stock->load('batches');
                
                if ($batchId) {
                    // Validate batch belongs to this stock using relationship
                    $selectedBatch = $product_stock->batches()->where('id', $batchId)->first();
                    
                    if ($selectedBatch) {
                        // Use batch quantity for validation
                        $availableQty = $selectedBatch->qty ?? 0;
                        if (is_batch_expired($selectedBatch)) {
                            return array(
                                'status' => 0,
                                'cart_count' => count($carts),
                                'modal_view' => view('frontend.partials.outOfStockCart')->render(),
                                'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
                            );
                        }
                    } else {
                        return array(
                            'status' => 0,
                            'cart_count' => count($carts),
                            'modal_view' => view('frontend.partials.outOfStockCart')->render(),
                            'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
                        );
                    }
                } else {
                    // No batch selected, use total from all batches or stock
                    $batches = valid_batches_for_stock($product_stock, true);
                    $availableQty = $product_stock->batches->isNotEmpty() ? $batches->sum('qty') : ($product_stock->qty ?? 0);
                }
            } catch (\Exception $e) {
                // If there's an error loading batches, fallback to stock quantity
                \Log::error('Error loading batches in CartController: ' . $e->getMessage());
                $availableQty = $product_stock->qty ?? 0;
                $batchId = null;
                $selectedBatch = null;
            }
        } else {
            $availableQty = 0;
        }

        $minQty = optional($product_stock)->min_qty ?? $product->min_qty ?? 1;
        if ($quantity < $minQty) {
            return array(
                'status' => 0,
                'cart_count' => count($carts),
                'modal_view' => view('frontend.partials.minQtyNotSatisfied', ['min_qty' => $minQty])->render(),
                'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
            );
        }

        if($authUser != null) {
            $user_id = $authUser->id;
            $cartIdentity = [
                'user_id' => $user_id,
                'product_id' => $request['id'],
                'batch_id' => $batchId,
                'is_scheme' => 0,
            ];
            $cartIdentity[$idVariant ? 'id_variant' : 'variation'] = $idVariant ?: $str;
            $cart = Cart::firstOrNew($cartIdentity);
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $cartIdentity = [
                'temp_user_id' => $temp_user_id,
                'product_id' => $request['id'],
                'batch_id' => $batchId,
                'is_scheme' => 0,
            ];
            $cartIdentity[$idVariant ? 'id_variant' : 'variation'] = $idVariant ?: $str;
            $cart = Cart::firstOrNew($cartIdentity);
        }

        $cart->variation = $str;
        $cart->id_variant = $idVariant;

        if ($cart->exists && $product->digital == 0) {
            if ($product->auction_product == 1 && ($cart->product_id == $product->id)) {
                return array(
                    'status' => 0,
                    'cart_count' => count($carts),
                    'modal_view' => view('frontend.partials.cart.auctionProductAlredayAddedCart')->render(),
                    'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
                );
            }
            $quantity = $cart->quantity + $request['quantity'];

            if ($quantity < $minQty) {
                return array(
                    'status' => 0,
                    'cart_count' => count($carts),
                    'modal_view' => view('frontend.partials.minQtyNotSatisfied', ['min_qty' => $minQty])->render(),
                    'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
                );
            }
        }

        if ($selectedBatch) {
            $totalAvailableQty = (int) ($selectedBatch->qty ?? 0);
        } elseif ($product_stock) {
            $product_stock->load('batches');
            $batches = valid_batches_for_stock($product_stock, true);
            $totalAvailableQty = $product_stock->batches->isNotEmpty() ? (int) $batches->sum('qty') : (int) ($product_stock->qty ?? 0);
        } else {
            $totalAvailableQty = 0;
        }

        if ($product->digital == 0 && $totalAvailableQty < $quantity) {
            return array(
                'status' => 0,
                'cart_count' => count($carts),
                'modal_view' => view('frontend.partials.outOfStockCart')->render(),
                'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
            );
        }

        $schemePreview = $this->getSchemePreview($cart, $product, $product_stock, (int) $quantity, $batchId);
        $schemeQty = (int) $schemePreview['scheme_qty'];
        if ($schemeQty > 0 && !$schemePreview['allocation']['success']) {
            return array(
                'status' => 0,
                'cart_count' => count($carts),
                'modal_view' => view('frontend.partials.outOfStockCart')->render(),
                'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
            );
        }

        // Use batch data for pricing if batch is selected
        // Note: $batchId is already set above from request
        if ($selectedBatch) {
            $resolvedPrice = resolvePrice($product, $product_stock, $selectedBatch, $request->quantity);
            $price = (float) ($resolvedPrice['price'] ?? 0);
            $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $price);
            $salePrice = (float) ($resolvedPrice['sale_price'] ?? $price);
            $mrpPrice = $selectedBatch->mrp_price ?? $product_stock->mrp_price ?? $product->mrp_price;
            // Ensure batchId is set
            $batchId = $selectedBatch->id;
        } else {
            $resolvedPrice = resolvePrice($product, $product_stock, null, $request->quantity);
            $price = (float) ($resolvedPrice['price'] ?? 0);
            $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $price);
            $salePrice = (float) ($resolvedPrice['sale_price'] ?? $price);
            $mrpPrice = $product_stock->mrp_price ?? $product->mrp_price;
            // Clear batchId if no batch selected
            $batchId = null;
        }

        $tax = CartUtility::tax_calculation($product, $salePrice);

        try {
            \DB::transaction(function () use ($cart, $product, $price, $tax, $quantity, $mrpPrice, $salePrice, $batchId, $product_stock, $beforeProductAndBatchDiscount) {
                CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity, $mrpPrice, $salePrice, $batchId, false, $beforeProductAndBatchDiscount);
                $cart->notify_date = Carbon::now()->addHour(); // First reminder in 1 hours
                $cart->save();

                if (!$this->syncSchemeCartLinesForStock($cart, $product, $product_stock)) {
                    throw new \RuntimeException('Unable to allocate scheme stock.');
                }
            });
        } catch (\RuntimeException $e) {
            return array(
                'status' => 0,
                'cart_count' => count($carts),
                'modal_view' => view('frontend.partials.outOfStockCart')->render(),
                'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
            );
        }

        if($authUser != null) {
            $user_id = $authUser->id;
            $carts = Cart::where('user_id', $user_id)->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = Cart::where('temp_user_id', $temp_user_id)->get();
        }

        $addedQuantity = (int) $request['quantity'];
        $addedTotalDisplay = $this->calculateDisplayLineTotal($product, (float) $salePrice, $addedQuantity);

        return array(
            'status' => 1,
            'cart_count' => count($carts),
            'modal_view' => view('frontend.partials.cart.addedToCart', compact('product', 'cart'))
                ->with('added_quantity', $addedQuantity)
                ->with('scheme_quantity', $schemeQty)
                ->with('added_total_display', $addedTotalDisplay)
                ->render(),
            'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
        );
    }

    //removes from Cart
    public function removeFromCart(Request $request)
    {
        $cart = Cart::find($request->id);
        if ($cart && !(bool) $cart->is_scheme) {
            $product = Product::find($cart->product_id);
            $product_stock = $product
                ? $product->stocks()
                    ->when($cart->id_variant, function ($query) use ($cart) {
                        $query->where('id_variant', $cart->id_variant);
                    }, function ($query) use ($cart) {
                        $query->where('variant', $cart->variation);
                    })
                    ->first()
                : null;
            Cart::destroy($request->id);
            if ($product && $product_stock && $this->paidCartQuery($cart)->exists()) {
                $this->syncSchemeCartLinesForStock($cart, $product, $product_stock);
            } else {
                $this->deleteSchemeCartLine($cart);
            }
        } else {
            Cart::destroy($request->id);
        }
        $authUser = auth()->user();
        if ($authUser != null) {
            $user_id = $authUser->id;
            $carts = Cart::where('user_id', $user_id)->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = Cart::where('temp_user_id', $temp_user_id)->get();
        }
        $this->refreshAppliedCouponForCarts($carts);
        $carts = auth()->user() != null
            ? Cart::where('user_id', Auth::user()->id)->get()
            : Cart::where('temp_user_id', $request->session()->get('temp_user_id'))->get();

        return array(
            'cart_count' => count($carts),
            'cart_view' => view('frontend.partials.cart.cart_details', compact('carts'))->render(),
            'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
        );
    }

    //updated the quantity for a cart item
    public function updateQuantity(Request $request)
    {
        $cartItem = Cart::findOrFail($request->id);
        if ((bool) $cartItem->is_scheme) {
            if (auth()->user() != null) {
                $carts = Cart::where('user_id', Auth::user()->id)->get();
            } else {
                $temp_user_id = $request->session()->get('temp_user_id');
                $carts = Cart::where('temp_user_id', $temp_user_id)->get();
            }
            return array(
                'cart_count' => count($carts),
                'cart_view' => view('frontend.partials.cart.cart_details', compact('carts'))->render(),
                'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
            );
        }

        if ($cartItem['id'] == $request->id) {
            $product = Product::find($cartItem['product_id']);
            $product_stock = $product
                ? $product->stocks()
                    ->when($cartItem->id_variant, function ($query) use ($cartItem) {
                        $query->where('id_variant', $cartItem->id_variant);
                    }, function ($query) use ($cartItem) {
                        $query->where('variant', $cartItem['variation']);
                    })
                    ->where('is_hidden', 0)
                    ->first()
                : null;

            if ($product_stock) {
                $product_stock->load('batches');
                $validBatches = valid_batches_for_stock($product_stock, true);
                $availableQuantity = $product_stock->batches->isNotEmpty() ? (int) $validBatches->sum('qty') : (int) ($product_stock->qty ?? 0);
                $minQty = $product_stock->min_qty ?? $product->min_qty ?? 1;
                
                // Get batch if batch_id exists
                $batchId = $cartItem['batch_id'] ?? null;
                $selectedBatch = null;
                $price = 0;
                $salePrice = 0;
                
                if ($batchId) {
                    $product_stock->load('batches');
                    $selectedBatch = $product_stock->batches()->where('id', $batchId)->first();
                    if ($selectedBatch) {
                        if (is_batch_expired($selectedBatch)) {
                            $selectedBatch = null;
                            $availableQuantity = 0;
                        } else {
                            $availableQuantity = (int) ($selectedBatch->qty ?? 0);
                            $resolvedPrice = resolvePrice($product, $product_stock, $selectedBatch, $request->quantity);
                            $price = (float) ($resolvedPrice['price'] ?? 0);
                            $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $price);
                            $salePrice = (float) ($resolvedPrice['sale_price'] ?? $price);
                        }
                    } else {
                        $availableQuantity = 0;
                    }
                }
                
                if (!$selectedBatch) {
                    $resolvedPrice = resolvePrice($product, $product_stock, null, $request->quantity);
                    $price = (float) ($resolvedPrice['price'] ?? 0);
                    $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $price);
                    $salePrice = (float) ($resolvedPrice['sale_price'] ?? $price);
                }

                $schemePreview = $this->getSchemePreview($cartItem, $product, $product_stock, (int) $request->quantity, $batchId);
                $requestedStockRequired = (int) $request->quantity;

                $canUpdateQuantity = $availableQuantity >= $requestedStockRequired && $schemePreview['allocation']['success'];

                if ($canUpdateQuantity) {
                    if ($request->quantity >= $minQty) {
                        $cartItem['quantity'] = $request->quantity;
                    }

                    if ($selectedBatch) {
                        $resolvedPrice = resolvePrice($product, $product_stock, $selectedBatch, $cartItem['quantity']);
                        $price = (float) ($resolvedPrice['price'] ?? 0);
                        $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $price);
                        $salePrice = (float) ($resolvedPrice['sale_price'] ?? $price);
                    } else {
                        $resolvedPrice = resolvePrice($product, $product_stock, null, $cartItem['quantity']);
                        $price = (float) ($resolvedPrice['price'] ?? 0);
                        $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $price);
                        $salePrice = (float) ($resolvedPrice['sale_price'] ?? $price);
                    }

                    // Recalculate tax based on updated price
                    $tax = CartUtility::tax_calculation($product, $salePrice);
                    
                    $cartItem['price'] = $price;
                    $cartItem->before_productandbatch_discount = $beforeProductAndBatchDiscount;
                    $cartItem->mrp_price = $selectedBatch ? ($selectedBatch->mrp_price ?? $product_stock->mrp_price ?? $product->mrp_price) : ($product_stock->mrp_price ?? $product->mrp_price);
                    $cartItem->sale_price = $salePrice;
                    $cartItem->tax = $tax; // Store recalculated tax
                    $cartItem->save();
                    $this->syncSchemeCartLinesForStock($cartItem, $product, $product_stock);
                }
            }
        }

        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $carts = Cart::where('user_id', $user_id)->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = Cart::where('temp_user_id', $temp_user_id)->get();
        }
        $this->refreshAppliedCouponForCarts($carts);
        $carts = auth()->user() != null
            ? Cart::where('user_id', Auth::user()->id)->get()
            : Cart::where('temp_user_id', $request->session()->get('temp_user_id'))->get();

        return array(
            'cart_count' => count($carts),
            'cart_view' => view('frontend.partials.cart.cart_details', compact('carts'))->render(),
            'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
        );
    }

    public function updateCartStatus(Request $request)
    {
        $cart_ids = $request->cart_id ?? $request->product_id;
        $cartIdColumn = $request->has('cart_id') ? 'id' : 'product_id';

        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $carts = Cart::where('user_id', $user_id)->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = Cart::where('temp_user_id', $temp_user_id)->get();
        }

        $coupon_applied = $carts->toQuery()->where('coupon_applied', 1)->first();
        if($coupon_applied != null){
            $owner_id = $coupon_applied->owner_id;
            $coupon_code = $coupon_applied->coupon_code;
            $user_carts = $carts->toQuery()->where('owner_id', $owner_id)->get();
            $user_carts->toQuery()->update(
                [
                    'discount' => 0.00,
                    'coupon_code' => null,
                    'coupon_applied' => 0
                ]
            );
        }

        $carts->toQuery()->update(['status' => 0]);
        if($cart_ids != null){
            if($coupon_applied != null){
                $active_user_carts = $user_carts->toQuery()->whereIn($cartIdColumn, $cart_ids)->where('is_scheme', 0)->get();
                if (count($active_user_carts) > 0) {
                    $coupon = Coupon::where('code', $coupon_code)->first();
                    $userCoupon = ($coupon && $coupon->type === 'welcome_base' && auth()->user())
                        ? auth()->user()->userCoupon
                        : null;
                    $couponResult = $coupon
                        ? coupon_cart_discount_allocations($coupon, $active_user_carts, json_decode($coupon->details), $userCoupon)
                        : ['discount' => 0, 'allocations' => []];

                    foreach ($active_user_carts as $activeCart) {
                        $lineDiscount = $couponResult['allocations'][(int) $activeCart->id] ?? 0;
                        if ($lineDiscount <= 0) {
                            continue;
                        }

                        $activeCart->discount = $lineDiscount;
                        $activeCart->coupon_code = $coupon_code;
                        $activeCart->coupon_applied = 1;
                        $activeCart->save();
                    }
                }
            }

            $carts->toQuery()->whereIn($cartIdColumn, $cart_ids)->where('is_scheme', 0)->update(['status' => 1]);
            $carts->toQuery()->where('is_scheme', 1)->update(['status' => 0]);
            $activePaidCarts = $carts->whereIn($cartIdColumn, $cart_ids)->where('is_scheme', 0);
            foreach ($activePaidCarts as $paidCart) {
                $schemeQuery = $carts->toQuery()
                    ->where('product_id', $paidCart->product_id)
                    ->where('is_scheme', 1);

                if ($paidCart->id_variant) {
                    $schemeQuery->where('id_variant', $paidCart->id_variant);
                } else {
                    $schemeQuery->where('variation', $paidCart->variation);
                }

                if ($paidCart->user_id) {
                    $schemeQuery->where('user_id', $paidCart->user_id);
                } else {
                    $schemeQuery->where('temp_user_id', $paidCart->temp_user_id);
                }

                $schemeQuery->update(['status' => 1]);
            }
        }
        $carts = $carts->fresh();

        return view('frontend.partials.cart.cart_details', compact('carts'))->render();
    }
}
