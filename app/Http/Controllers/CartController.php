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
use App\Models\ProductBatch;
use Auth;
use App\Utility\CartUtility;
use Session;
use Cookie;

class CartController extends Controller
{
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
        $str = CartUtility::create_cart_variant($product, $request->all());
        $product_stock = $product->stocks()->where('variant', $str)->where('is_hidden', 0)->first();
        if (!$product_stock) {
            return array(
                'status' => 0,
                'cart_count' => count($carts),
                'modal_view' => view('frontend.partials.outOfStockCart')->render(),
                'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
            );
        }

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
                    } else {
                        // Batch not found or doesn't belong to this stock, use stock quantity
                        $availableQty = $product_stock->qty ?? 0;
                        $batchId = null; // Clear invalid batch_id
                        $selectedBatch = null;
                    }
                } else {
                    // No batch selected, use total from all batches or stock
                    $batches = $product_stock->batches;
                    $availableQty = $batches->isNotEmpty() ? $batches->sum('qty') : ($product_stock->qty ?? 0);
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
            $cart = Cart::firstOrNew([
                'variation' => $str,
                'user_id' => $user_id,
                'product_id' => $request['id']
            ]);
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $cart = Cart::firstOrNew([
                'variation' => $str,
                'temp_user_id' => $temp_user_id,
                'product_id' => $request['id']
            ]);
        }

        if ($cart->exists && $product->digital == 0) {
            if ($product->auction_product == 1 && ($cart->product_id == $product->id)) {
                return array(
                    'status' => 0,
                    'cart_count' => count($carts),
                    'modal_view' => view('frontend.partials.cart.auctionProductAlredayAddedCart')->render(),
                    'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
                );
            }
            // Check available quantity from batches or stock
            if ($product_stock) {
                $product_stock->load('batches');
                $batches = $product_stock->batches;
                $totalAvailableQty = $batches->isNotEmpty() ? $batches->sum('qty') : ($product_stock->qty ?? 0);
            } else {
                $totalAvailableQty = 0;
            }
            
            if ($totalAvailableQty < $cart->quantity + $request['quantity']) {
                return array(
                    'status' => 0,
                    'cart_count' => count($carts),
                    'modal_view' => view('frontend.partials.outOfStockCart')->render(),
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

        // Use batch data for pricing if batch is selected
        // Note: $batchId is already set above from request
        if ($selectedBatch) {
            $price = CartUtility::get_price_from_batch($product, $selectedBatch, $request->quantity);
            $mrpPrice = $selectedBatch->mrp_price ?? $product_stock->mrp_price ?? $product->mrp_price;
            // Ensure batchId is set
            $batchId = $selectedBatch->id;
        } else {
            $price = CartUtility::get_price($product, $product_stock, $request->quantity);
            $mrpPrice = $product_stock->mrp_price ?? $product->mrp_price;
            // Clear batchId if no batch selected
            $batchId = null;
        }
        
        $tax = CartUtility::tax_calculation($product, $price);
        $salePrice = $price;

        CartUtility::save_cart_data($cart, $product, $price, $tax, $quantity, $mrpPrice, $salePrice, $batchId);
        $cart->notify_date = Carbon::now()->addHour(); // First reminder in 1 hours
        $cart->save();

        if($authUser != null) {
            $user_id = $authUser->id;
            $carts = Cart::where('user_id', $user_id)->get();
        } else {
            $temp_user_id = $request->session()->get('temp_user_id');
            $carts = Cart::where('temp_user_id', $temp_user_id)->get();
        }

        return array(
            'status' => 1,
            'cart_count' => count($carts),
            'modal_view' => view('frontend.partials.cart.addedToCart', compact('product', 'cart'))->render(),
            'nav_cart_view' => view('frontend.partials.cart.cart')->render(),
        );
    }

    //removes from Cart
    public function removeFromCart(Request $request)
    {
        Cart::destroy($request->id);
        $authUser = auth()->user();
        if ($authUser != null) {
            $user_id = $authUser->id;
            $carts = Cart::where('user_id', $user_id)->get();
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

    //updated the quantity for a cart item
    public function updateQuantity(Request $request)
    {
        $cartItem = Cart::findOrFail($request->id);

        if ($cartItem['id'] == $request->id) {
            $product = Product::find($cartItem['product_id']);
            $product_stock = $product
                ? $product->stocks()->where('variant', $cartItem['variation'])->where('is_hidden', 0)->first()
                : null;

            if ($product_stock) {
                $quantity = $product_stock->qty;
                $minQty = $product_stock->min_qty ?? $product->min_qty ?? 1;
                
                // Get batch if batch_id exists
                $batchId = $cartItem['batch_id'] ?? null;
                $selectedBatch = null;
                $price = 0;
                
                if ($batchId) {
                    $product_stock->load('batches');
                    $selectedBatch = $product_stock->batches()->where('id', $batchId)->first();
                    if ($selectedBatch) {
                        $price = CartUtility::get_price_from_batch($product, $selectedBatch, $request->quantity);
                    }
                }
                
                if (!$selectedBatch) {
                    //$price = $product_stock->price;
                    // IMPORTANT: role_price comes ONLY from batches, NOT from stock
                    // Use batch-aware pricing helper which checks batches first, then falls back to product-level
                    $price = getStockPriceByRole($product_stock, $product, false);
                    if ($price === null || $price === 0) {
                        // Fallback to product-level role_price (NOT stock-level)
                        $price = getPriceByRole($product->role_price ?? null, $product_stock->price ?? 0); //price by role
                    }
                }

                //discount calculation
                $discount_applicable = false;

                if ($product->discount_start_date == null) {
                    $discount_applicable = true;
                } elseif (
                    strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                    strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
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

                if ($quantity >= $request->quantity) {
                    if ($request->quantity >= $minQty) {
                        $cartItem['quantity'] = $request->quantity;
                    }
                }

                if ($product->wholesale_product) {
                    $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $request->quantity)->where('max_qty', '>=', $request->quantity)->first();
                    if ($wholesalePrice) {
                        $price = $wholesalePrice->price;
                    }
                }

                // Recalculate tax based on updated price
                $tax = CartUtility::tax_calculation($product, $price);
                
                $cartItem['price'] = $price;
                $cartItem->mrp_price = $selectedBatch ? ($selectedBatch->mrp_price ?? $product_stock->mrp_price ?? $product->mrp_price) : ($product_stock->mrp_price ?? $product->mrp_price);
                $cartItem->sale_price = $price;
                $cartItem->tax = $tax; // Store recalculated tax
                $cartItem->save();
            }
        }

        if (auth()->user() != null) {
            $user_id = Auth::user()->id;
            $carts = Cart::where('user_id', $user_id)->get();
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

    public function updateCartStatus(Request $request)
    {
        $product_ids = $request->product_id;

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
            $coupon_discount = $user_carts->toQuery()->sum('discount');
            $user_carts->toQuery()->update(
                [
                    'discount' => 0.00,
                    'coupon_code' => '',
                    'coupon_applied' => 0
                ]
            );
        }

        $carts->toQuery()->update(['status' => 0]);
        if($product_ids != null){
            if($coupon_applied != null){
                $active_user_carts = $user_carts->toQuery()->whereIn('product_id', $product_ids)->get();
                if (count($active_user_carts) > 0) {
                    $active_user_carts->toQuery()->update(
                        [
                            'discount' => $coupon_discount / count($active_user_carts),
                            'coupon_code' => $coupon_code,
                            'coupon_applied' => 1
                        ]
                    );
                }
            }

            $carts->toQuery()->whereIn('product_id', $product_ids)->update(['status' => 1]);
        }
        $carts = $carts->fresh();

        return view('frontend.partials.cart.cart_details', compact('carts'))->render();
    }
}
