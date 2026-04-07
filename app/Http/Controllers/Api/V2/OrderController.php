<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Address;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Models\ProductBatch;
use DB;
use \App\Utility\NotificationUtility;
use App\Models\CombinedOrder;
use App\Http\Controllers\AffiliateController;
use App\Services\WalletRewardService;

class OrderController extends Controller
{
    public function store(Request $request, $set_paid = false)
    {
        if (get_setting('minimum_order_amount_check') == 1) {
            $subtotal = 0;
            foreach (Cart::where('user_id', auth()->user()->id)->active()->get() as $key => $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
            }
            if ($subtotal < get_setting('minimum_order_amount')) {
                return $this->failed(translate("You order amount is less then the minimum order amount"));
            }
        }

        $cartItems = Cart::where('user_id', auth()->user()->id)->active()->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'combined_order_id' => 0,
                'result' => false,
                'message' => translate('Cart is Empty')
            ]);
        }

        $user = User::find(auth()->user()->id);

        $address = Address::where('id', $cartItems->first()->address_id)->first();
        $shippingAddress = [];
        if ($address != null) {
            $shippingAddress['name']        = $user->name;
            $shippingAddress['email']       = $user->email;
            $shippingAddress['address']     = $address->address;
            $shippingAddress['country']     = $address->country->name;
            $shippingAddress['state']       = $address->state->name;
            $shippingAddress['city']        = $address->city->name;
            $shippingAddress['postal_code'] = $address->postal_code;
            $shippingAddress['phone']       = $address->phone;
            if ($address->latitude || $address->longitude) {
                $shippingAddress['lat_lang'] = $address->latitude . ',' . $address->longitude;
            }
        }

        $combined_order = new CombinedOrder;
        $combined_order->user_id = $user->id;
        $combined_order->shipping_address = json_encode($shippingAddress);
        $combined_order->save();

        $seller_products = array();
        foreach ($cartItems as $cartItem) {
            $product_ids = array();
            $product = Product::find($cartItem['product_id']);
            if (isset($seller_products[$product->user_id])) {
                $product_ids = $seller_products[$product->user_id];
            }
            array_push($product_ids, $cartItem);
            $seller_products[$product->user_id] = $product_ids;
        }

        foreach ($seller_products as $seller_product) {
            $order = new Order;
            $order->combined_order_id = $combined_order->id;
            $order->user_id = $user->id;
            $order->shipping_address = $combined_order->shipping_address;

            $order->order_from = 'app';
            $order->payment_type = $request->payment_type;
            $order->delivery_viewed = '0';
            $order->payment_status_viewed = '0';
            $order->code = generate_financial_year_order_code();
            $order->date = strtotime('now');
            if ($set_paid) {
                $order->payment_status = 'paid';
            } else {
                $order->payment_status = 'unpaid';
            }

            $order->save();

            $subtotal = 0;
            $tax = 0;
            $shipping = 0;
            $coupon_discount = 0;

            //Order Details Storing
            foreach ($seller_product as $cartItem) {
                $product = Product::find($cartItem['product_id']);

                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                // Use stored tax from cart (calculated from batch/stock price at add-to-cart)
                $itemTax = ($cartItem['tax'] ?? cart_product_tax($cartItem, $product, false)) * $cartItem['quantity'];
                $tax += $itemTax;
                $coupon_discount += $cartItem['discount'];

                $product_variation = $cartItem['variation'];

                $product_stock = $product->stocks->where('variant', $product_variation)->first();
                
                // Get batch_id from cart if available
                $batchId = $cartItem['batch_id'] ?? null;
                $selectedBatch = null;
                
                // Stock validation and deduction
                if ($product->digital != 1 && $product_stock) {
                    if ($batchId) {
                        // Validate batch belongs to this stock and deduct from batch
                        $selectedBatch = $product_stock->batches()->where('id', $batchId)->first();
                        if (!$selectedBatch) {
                            $order->delete();
                            $combined_order->delete();
                            return response()->json([
                                'combined_order_id' => 0,
                                'result' => false,
                                'message' => translate('Invalid batch selected for ') . $product->name
                            ]);
                        }
                        
                        if ($cartItem['quantity'] > $selectedBatch->qty) {
                            $order->delete();
                            $combined_order->delete();
                            return response()->json([
                                'combined_order_id' => 0,
                                'result' => false,
                                'message' => translate('The requested quantity is not available for ') . $product->name
                            ]);
                        }
                        
                        $selectedBatch->qty -= $cartItem['quantity'];
                        $selectedBatch->save();
                        
                        // Update parent stock quantity (aggregate from batches)
                        $product_stock->load('batches');
                        $batches = $product_stock->batches;
                        $totalBatchQty = $batches->sum('qty');
                        $product_stock->qty = $totalBatchQty;
                        $product_stock->save();
                    } else {
                        // Fallback to stock validation if no batch
                        if ($cartItem['quantity'] > $product_stock->qty) {
                            $order->delete();
                            $combined_order->delete();
                            return response()->json([
                                'combined_order_id' => 0,
                                'result' => false,
                                'message' => translate('The requested quantity is not available for ') . $product->name
                            ]);
                        }
                        $product_stock->qty -= $cartItem['quantity'];
                        $product_stock->save();
                    }
                }

                $order_detail = new OrderDetail;
                $order_detail->order_id = $order->id;
                $order_detail->seller_id = $product->user_id;
                $order_detail->product_id = $product->id;
                $order_detail->variation = $product_variation;
                $order_detail->batch_id = $batchId;
                $order_detail->price = cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                $order_detail->sale_price = $cartItem['sale_price'] ?? cart_product_price($cartItem, $product, false, false);
                $order_detail->mrp_price = $cartItem['mrp_price'] ?? ($selectedBatch ? $selectedBatch->mrp_price : (optional($product_stock)->mrp_price ?? $product->mrp_price));
                // Use stored tax from cart so order_detail matches cart (batch-aware)
                $order_detail->tax = ($cartItem['tax'] ?? cart_product_tax($cartItem, $product, false)) * $cartItem['quantity'];
                $order_detail->shipping_type = $cartItem['shipping_type'];
                $order_detail->product_referral_code = $cartItem['product_referral_code'];
                $order_detail->shipping_cost = $cartItem['shipping_cost'];

                $shipping += $order_detail->shipping_cost;

                //End of storing shipping cost
                if (addon_is_activated('club_point')) {
                    $order_detail->earn_point = $product->earn_point;
                }

                $order_detail->quantity = $cartItem['quantity'];
                $order_detail->save();

                $product->num_of_sale = $product->num_of_sale + $cartItem['quantity'];
                $product->save();

                $order->seller_id = $product->user_id;
              
                $order->shipping_type = $cartItem['shipping_type'];
                if ($cartItem['shipping_type'] == 'pickup_point') {
                    $order->pickup_point_id = $cartItem['pickup_point'];
                }
                if ($cartItem['shipping_type'] == 'carrier') {
                    $order->carrier_id = $cartItem['carrier_id'];
                }

                if ($product->added_by == 'seller' && $product->user->seller != null) {
                    $seller = $product->user->seller;
                    $seller->num_of_sale += $cartItem['quantity'];
                    $seller->save();
                }

                if (addon_is_activated('affiliate_system')) {
                    if ($order_detail->product_referral_code) {
                        $referred_by_user = User::where('referral_code', $order_detail->product_referral_code)->first();

                        $affiliateController = new AffiliateController;
                        $affiliateController->processAffiliateStats($referred_by_user->id, 0, $order_detail->quantity, 0, 0);
                    }
                }
            }

            $order->grand_total = $subtotal + $tax + $shipping;

            if ($seller_product[0]->coupon_code != null) {
                $order->coupon_discount = $coupon_discount;
                $order->grand_total -= $coupon_discount;

                $coupon_usage = new CouponUsage;
                $coupon_usage->user_id = $user->id;
                $coupon_usage->coupon_id = Coupon::where('code', $seller_product[0]->coupon_code)->first()->id;
                $coupon_usage->save();
            }

            $combined_order->grand_total += $order->grand_total;

            if (strpos($request->payment_type, "manual_payment_") !== false) { // if payment type like  manual_payment_1 or  manual_payment_25 etc)

                $order->manual_payment = 1;
                $order->save();
            }

            $order->save();

            if ($set_paid) {
                app(WalletRewardService::class)->applyReward($order);
            }
        }
        $combined_order->save();

        Cart::where('user_id', auth()->user()->id)->active()->delete();

        if (
            $request->payment_type == 'cash_on_delivery'
            || $request->payment_type == 'wallet'
            || strpos($request->payment_type, "manual_payment_") !== false // if payment type like  manual_payment_1 or  manual_payment_25 etc
        ) {
            NotificationUtility::sendOrderPlacedNotification($order);
        }

        return response()->json([
            'combined_order_id' => $combined_order->id,
            'result' => true,
            'message' => translate('Your order has been placed successfully')
        ]);
    }

    public function order_cancel($id)
    {
        $order = Order::where('id', $id)->where('user_id', auth()->user()->id)->first();
        if ($order && ($order->delivery_status == 'pending' && $order->payment_status == 'unpaid')) {
            $order->delivery_status = 'cancelled';
            $order->save();

            foreach ($order->orderDetails as $key => $orderDetail) {
                $orderDetail->delivery_status = 'cancelled';
                $orderDetail->save();
                product_restock($orderDetail);
            }

            return $this->success(translate('Order has been canceled successfully'));
        } else {
            return  $this->failed(translate('Something went wrong'));
        }
    }
}
