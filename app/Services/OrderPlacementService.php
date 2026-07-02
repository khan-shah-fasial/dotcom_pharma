<?php

namespace App\Services;

use App\Models\Address;
use App\Models\BookedTo;
use App\Models\Cart;
use App\Models\CombinedOrder;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\LocalDeliveryPartner;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductStock;
use App\Models\Transport;
use App\Models\User;
use App\Utility\CartUtility;
use App\Utility\EmailUtility;
use App\Utility\NotificationUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class OrderPlacementService
{
    public function placeFromCarts(User $customer, $carts, Request $request, array $options = []): CombinedOrder
    {
        $lines = collect($carts)->values();

        if ($lines->isEmpty()) {
            $this->fail('items', translate('Your cart is empty'));
        }

        $options = array_merge([
            'source' => 'checkout',
            'payment_type' => $request->input('payment_option'),
            'payment_status' => 'unpaid',
            'send_notification' => false,
        ], $options);

        return $this->placeFromLines($customer, $lines, $request, $options);
    }

    public function placeFromBackendRequest(Request $request, array $options = []): CombinedOrder
    {
        $customer = $this->resolveApprovedCustomer($request->input('customer_id'));
        $shippingAddress = $this->resolveBackendAddress($customer, $request, Address::TYPE_SHIPPING, true);
        $billingAddress = $this->resolveBackendAddress($customer, $request, Address::TYPE_BILLING, false) ?: $shippingAddress;

        $lines = $this->withPricingUser($customer, function () use ($customer, $request, $shippingAddress) {
            return $this->buildBackendLines($customer, (array) $request->input('items', []), $request, optional($shippingAddress)->id);
        });

        $this->assignBackendShippingCosts($lines, $request);
        $this->applyBackendCoupon($customer, $lines, trim((string) $request->input('coupon_code')));

        $paymentStatus = $request->input('payment_status') === 'paid' ? 'paid' : 'unpaid';
        $sendNotification = $request->has('send_order_notification')
            ? $request->boolean('send_order_notification')
            : true;

        $options = array_merge([
            'source' => 'backend',
            'shipping_address_model' => $shippingAddress,
            'billing_address_model' => $billingAddress,
            'payment_type' => $request->input('payment_type', 'manual'),
            'payment_status' => $paymentStatus,
            'send_notification' => $sendNotification,
        ], $options);

        return $this->placeFromLines($customer, $lines, $request, $options);
    }

    public function summarizeBackendRequest(Request $request): array
    {
        $customer = $this->resolveApprovedCustomer($request->input('customer_id'));

        $lines = $this->withPricingUser($customer, function () use ($customer, $request) {
            return $this->buildBackendLines($customer, (array) $request->input('items', []), $request, $request->input('shipping_address_id'));
        });

        $this->assignBackendShippingCosts($lines, $request);
        $this->applyBackendCoupon($customer, $lines, trim((string) $request->input('coupon_code')), false);

        return $this->summarizeLines($lines);
    }

    public function quoteBackendLine(Request $request): array
    {
        $customer = $this->resolveApprovedCustomer($request->input('customer_id'));

        return $this->withPricingUser($customer, function () use ($customer, $request) {
            $lines = $this->buildBackendLines($customer, [[
                'product_id' => $request->input('product_id'),
                'stock_id' => $request->input('stock_id'),
                'variation' => $request->input('variation'),
                'id_variant' => $request->input('id_variant'),
                'batch_id' => $request->input('batch_id'),
                'quantity' => $request->input('quantity', 1),
            ]], $request, $request->input('shipping_address_id'));

            $summary = $this->summarizeLines($lines);

            return $summary['lines'][0] ?? [];
        });
    }

    public function approvedCustomerQuery()
    {
        return User::query()
            ->where('user_type', 'customer')
            ->where('approval_status', 1);
    }

    public function resolveApprovedCustomer($customerId): User
    {
        $customer = $this->approvedCustomerQuery()->find($customerId);

        if (!$customer) {
            $this->fail('customer_id', translate('Please select an approved customer.'));
        }

        return $customer;
    }

    protected function placeFromLines(User $customer, Collection $lines, Request $request, array $options = []): CombinedOrder
    {
        $lines = $lines->filter(fn ($line) => !(bool) ($line['is_scheme'] ?? false))->values();

        if ($lines->isEmpty()) {
            $this->fail('items', translate('Your cart is empty'));
        }

        $combinedOrder = DB::transaction(function () use ($customer, $lines, $request, $options) {
            $address = $options['shipping_address_model']
                ?? Address::where('id', $lines[0]['address_id'] ?? null)->where('user_id', $customer->id)->first();
            $billingAddressModel = $options['billing_address_model']
                ?? ($request->filled('billing_address_id')
                    ? Address::where('id', $request->billing_address_id)->where('user_id', $customer->id)->first()
                    : $address);

            $shippingAddress = $this->buildAddressPayload($customer, $address);
            $billingAddress = $this->buildAddressPayload($customer, $billingAddressModel ?: $address);

            $combinedOrder = new CombinedOrder;
            $combinedOrder->user_id = $customer->id;
            $combinedOrder->shipping_address = json_encode($shippingAddress);
            $combinedOrder->save();

            $sellerProducts = [];
            foreach ($lines as $cartItem) {
                $product = Product::find($cartItem['product_id']);
                if (!$product) {
                    $this->fail('items', translate('Invalid product selected.'));
                }
                $sellerProducts[$product->user_id][] = $cartItem;
            }

            foreach ($sellerProducts as $sellerProduct) {
                $order = $this->createSellerOrder($combinedOrder, $customer, $request, $options, $shippingAddress, $billingAddress);
                $this->writeOrderDetails($order, collect($sellerProduct), $customer, $options);

                $combinedOrder->grand_total += $order->grand_total;
                $combinedOrder->save();
            }

            return $combinedOrder->fresh('orders.orderDetails');
        });

        $this->runAfterPlacementHooks($combinedOrder, $options);

        return $combinedOrder->fresh('orders.orderDetails');
    }

    protected function createSellerOrder(CombinedOrder $combinedOrder, User $customer, Request $request, array $options, array $shippingAddress, array $billingAddress): Order
    {
        $shippingChoice = $request->input('shipping_method') ?: 'courier';
        $transport = null;
        $bookedTo = null;
        $localDeliveryPartner = null;

        if ($shippingChoice === 'transport') {
            $transport = $this->resolveTransport($request);
            $bookedTo = $this->resolveBookedTo($request, $transport);
        } elseif ($shippingChoice === 'local') {
            $localDeliveryPartner = $this->resolveLocalDeliveryPartner($request);
        }

        $order = new Order;
        $order->combined_order_id = $combinedOrder->id;
        $order->user_id = $customer->id;
        $order->shipping_address = $combinedOrder->shipping_address;
        $order->billing_address = json_encode(!empty($billingAddress) ? $billingAddress : $shippingAddress);
        $order->additional_info = $request->additional_info;
        $order->payment_type = $options['payment_type'] ?? $request->input('payment_option');
        $order->payment_status = ($options['payment_status'] ?? 'unpaid') === 'paid' ? 'paid' : 'unpaid';
        $order->shipping_choice = $shippingChoice;
        $order->shipping_by = $shippingChoice === 'courier'
            ? (get_shipping_method_slug_by_id($request->shipping_method_id) ?? 'shipway')
            : ($shippingChoice === 'transport' ? optional($transport)->name : optional($localDeliveryPartner)->name);
        $order->fod_mode = $shippingChoice === 'transport' ? $request->fod_mode : null;
        $order->shipping_courier_id = $shippingChoice === 'courier' ? $request->courier_service : null;
        $order->transport_id = optional($transport)->id;
        $order->booked_to_id = optional($bookedTo)->id;
        $order->local_delivery_partner_id = optional($localDeliveryPartner)->id;
        $order->transport_mode = $shippingChoice === 'transport' ? $request->fod_mode : null;
        $order->transport_surface_mode = ($shippingChoice === 'transport' && $request->fod_mode === 'surface') ? $request->transport_surface_mode : null;
        $order->transport_delivery_type = $shippingChoice === 'transport' ? $request->transport_delivery_type : null;
        $order->delivery_viewed = '0';
        $order->payment_status_viewed = '0';
        $order->code = generate_financial_year_order_code();
        $order->date = strtotime('now');
        $order->save();

        storeIPLocation('orders', $order->id);

        return $order;
    }

    protected function writeOrderDetails(Order $order, Collection $sellerProduct, User $customer, array $options): void
    {
        $subtotal = 0;
        $tax = 0;
        $shipping = 0;
        $couponDiscount = 0;
        $affectedProductIds = [];
        $schemeGroupsWritten = [];
        $schemeAllocationsByGroup = $this->prepareSchemeAllocations($sellerProduct);

        foreach ($sellerProduct as $cartItem) {
            $product = Product::find($cartItem['product_id']);
            if (!$product) {
                $this->fail('items', translate('Invalid product selected.'));
            }

            if ((bool) ($cartItem['is_scheme'] ?? false)) {
                continue;
            }

            $unitSalePrice = $cartItem['sale_price'] ?? cart_product_price($cartItem, $product, false, false);
            $unitBasePrice = $cartItem['before_productandbatch_discount'] ?? $cartItem['price'] ?? $unitSalePrice;
            $quantity = (int) $cartItem['quantity'];

            $subtotal += $unitSalePrice * $quantity;
            $itemTax = ($cartItem['tax'] ?? cart_product_tax($cartItem, $product, false)) * $quantity;
            $tax += $itemTax;
            $couponDiscount += (float) ($cartItem['discount'] ?? 0);

            $productVariation = $cartItem['variation'];
            $productStock = $this->stockForLine($product, $cartItem);
            $batchId = $cartItem['batch_id'] ?? null;
            $selectedBatch = null;
            $groupKey = (int) $cartItem['product_id'] . '|' . (string) ($productVariation ?? '');

            if ($product->digital != 1 && !$productStock) {
                $this->fail('items', translate('Selected product stock is not available for ') . $product->getTranslation('name'));
            }

            if ($product->digital != 1 && $productStock) {
                if ($batchId) {
                    $selectedBatch = ProductBatch::where('id', $batchId)
                        ->where('product_stock_id', $productStock->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$selectedBatch || is_batch_expired($selectedBatch)) {
                        $this->fail('items', translate('Invalid batch selected for ') . $product->getTranslation('name'));
                    }

                    if ($quantity > (int) $selectedBatch->qty) {
                        $this->fail('items', translate('The requested quantity is not available for ') . $product->getTranslation('name'));
                    }

                    $selectedBatch->qty -= $quantity;
                    $selectedBatch->save();

                    $this->syncStockQuantityFromBatches($productStock);
                } else {
                    $lockedStock = ProductStock::where('id', $productStock->id)
                        ->where('is_hidden', 0)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedStock || $quantity > (int) $lockedStock->qty) {
                        $this->fail('items', translate('The requested quantity is not available for ') . $product->getTranslation('name'));
                    }

                    $lockedStock->qty -= $quantity;
                    $lockedStock->save();
                }

                $affectedProductIds[] = (int) $product->id;
            }

            $orderDetail = new OrderDetail;
            $orderDetail->order_id = $order->id;
            $orderDetail->seller_id = $product->user_id;
            $orderDetail->product_id = $product->id;
            $orderDetail->variation = $productVariation;
            $orderDetail->batch_id = $batchId;
            $orderDetail->price = $unitSalePrice * $quantity;
            $orderDetail->before_productandbatch_discount = $unitBasePrice;
            $orderDetail->sale_price = $unitSalePrice;
            $orderDetail->mrp_price = $cartItem['mrp_price'] ?? ($selectedBatch ? $selectedBatch->mrp_price : (optional($productStock)->mrp_price ?? $product->mrp_price));
            $orderDetail->discount_amount = round(max(0, (float) $unitBasePrice - (float) $unitSalePrice) * $quantity, 2);
            $orderDetail->tax = $itemTax;
            $orderDetail->shipping_type = $cartItem['shipping_type'] ?? 'home_delivery';
            $orderDetail->product_referral_code = $cartItem['product_referral_code'] ?? null;
            $orderDetail->shipping_cost = $cartItem['shipping_cost'] ?? 0;
            $orderDetail->is_scheme = 0;
            $orderDetail->quantity = $quantity;
            if (($options['payment_status'] ?? 'unpaid') === 'paid') {
                $orderDetail->payment_status = 'paid';
            }

            $shipping += $orderDetail->shipping_cost;

            if (addon_is_activated('club_point')) {
                $orderDetail->earn_point = $product->earn_point;
            }

            $orderDetail->save();

            foreach (($schemeGroupsWritten[$groupKey] ?? false) ? [] : ($schemeAllocationsByGroup[$groupKey]['allocations'] ?? []) as $allocation) {
                $schemeGroupsWritten[$groupKey] = true;
                $this->writeSchemeDetail($order, $orderDetail, $product, $productVariation, $cartItem, $allocation, $options);
            }
            $schemeGroupsWritten[$groupKey] = true;

            if ($productStock) {
                $this->syncStockQuantityFromBatches($productStock);
            }

            $product->num_of_sale += $quantity;
            $product->save();

            $order->seller_id = $product->user_id;
            $order->shipping_type = $cartItem['shipping_type'] ?? 'home_delivery';

            if (($cartItem['shipping_type'] ?? null) == 'pickup_point') {
                $order->pickup_point_id = $cartItem['pickup_point'] ?? null;
            }
            if (($cartItem['shipping_type'] ?? null) == 'carrier') {
                $order->carrier_id = $cartItem['carrier_id'] ?? null;
            }

            if ($product->added_by == 'seller' && $product->user && $product->user->seller != null) {
                $seller = $product->user->seller;
                $seller->num_of_sale += $quantity;
                $seller->save();
            }

            if (addon_is_activated('affiliate_system') && $orderDetail->product_referral_code) {
                $referredByUser = User::where('referral_code', $orderDetail->product_referral_code)->first();
                if ($referredByUser && class_exists('App\\Http\\Controllers\\AffiliateController')) {
                    $affiliateController = app()->make('App\\Http\\Controllers\\AffiliateController');
                    if (method_exists($affiliateController, 'processAffiliateStats')) {
                        $affiliateController->processAffiliateStats($referredByUser->id, 0, $orderDetail->quantity, 0, 0);
                    }
                }
            }
        }

        $order->grand_total = $subtotal + $tax + $shipping;
        $couponCode = $sellerProduct
            ->filter(fn ($item) => (float) ($item['discount'] ?? 0) > 0 && !empty($item['coupon_code']))
            ->pluck('coupon_code')
            ->first();

        if ($couponDiscount > 0 && $couponCode != null) {
            $order->coupon_discount = $couponDiscount;
            $order->grand_total -= $couponDiscount;

            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon) {
                $couponUsage = new CouponUsage;
                $couponUsage->user_id = $customer->id;
                $couponUsage->coupon_id = $coupon->id;
                $couponUsage->save();
            }
        }

        $order->quote_grand_total = $order->grand_total;
        $order->quote_currency_code = Session::get('currency_code');
        $order->quote_currency_exchange_rate = Session::get('currency_exchange_rate');
        $order->save();

        if (!empty($affectedProductIds)) {
            dispatch_low_stock_admin_notifications($affectedProductIds);
        }
    }

    protected function writeSchemeDetail(Order $order, OrderDetail $paidDetail, Product $product, $productVariation, $cartItem, array $allocation, array $options): void
    {
        $schemeBatchForDeduction = ProductBatch::where('id', $allocation['batch_id'])
            ->lockForUpdate()
            ->first();

        if (!$schemeBatchForDeduction || is_batch_expired($schemeBatchForDeduction) || (int) $schemeBatchForDeduction->qty < (int) $allocation['quantity']) {
            $this->fail('items', translate('The requested scheme quantity is not available for ') . $product->getTranslation('name'));
        }

        $schemeBatchForDeduction->qty -= (int) $allocation['quantity'];
        $schemeBatchForDeduction->save();

        $schemeBatch = $allocation['batch'] ?? ProductBatch::find($allocation['batch_id']);
        $schemeDetail = new OrderDetail;
        $schemeDetail->order_id = $order->id;
        $schemeDetail->seller_id = $product->user_id;
        $schemeDetail->product_id = $product->id;
        $schemeDetail->variation = $productVariation;
        $schemeDetail->batch_id = $allocation['batch_id'];
        $schemeDetail->price = 0;
        $schemeDetail->before_productandbatch_discount = 0;
        $schemeDetail->sale_price = 0;
        $schemeDetail->mrp_price = $schemeBatch->mrp_price ?? $paidDetail->mrp_price;
        $schemeDetail->tax = 0;
        $schemeDetail->shipping_type = $cartItem['shipping_type'] ?? 'home_delivery';
        $schemeDetail->product_referral_code = null;
        $schemeDetail->shipping_cost = 0;
        $schemeDetail->is_scheme = 1;
        $schemeDetail->quantity = (int) $allocation['quantity'];
        if (($options['payment_status'] ?? 'unpaid') === 'paid') {
            $schemeDetail->payment_status = 'paid';
        }
        $schemeDetail->save();

        if ($schemeBatchForDeduction->product_stock_id) {
            $this->syncStockQuantityFromBatches(ProductStock::find($schemeBatchForDeduction->product_stock_id));
        }
    }

    protected function buildBackendLines(User $customer, array $items, Request $request, $addressId = null): Collection
    {
        $lines = collect();
        $lineId = 1;

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['product_id'])) {
                continue;
            }

            $product = Product::with(['stocks.batches', 'stocks.wholesalePrices', 'taxes'])
                ->where('approved', '1')
                ->where('published', 1)
                ->find($item['product_id']);

            if (!$product || (int) ($product->digital ?? 0) === 1) {
                $this->fail('items', translate('Invalid product selected.'));
            }

            $stock = $this->resolveBackendStock($product, $item);
            if (!$stock) {
                $this->fail('items', translate('Selected product stock is not available.'));
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $minQty = $stock->min_qty ?? $product->min_qty ?? 1;
            if ($quantity < $minQty) {
                $this->fail('items', $product->getTranslation('name') . ': ' . translate('Minimum quantity is') . ' ' . $minQty);
            }

            $batch = null;
            $batchId = (int) ($item['batch_id'] ?? 0);
            if ($batchId > 0) {
                $batch = $stock->batches()->where('id', $batchId)->first();
                if (!$batch || is_batch_expired($batch)) {
                    $this->fail('items', translate('Invalid batch selected for ') . $product->getTranslation('name'));
                }
                if ($quantity > (int) $batch->qty) {
                    $this->fail('items', translate('The requested quantity is not available for ') . $product->getTranslation('name'));
                }
            } else {
                $stock->load('batches');
                $validBatches = valid_batches_for_stock($stock, true);
                $availableQty = $stock->batches->isNotEmpty() ? (int) $validBatches->sum('qty') : (int) ($stock->qty ?? 0);
                if ($quantity > $availableQty) {
                    $this->fail('items', translate('The requested quantity is not available for ') . $product->getTranslation('name'));
                }
            }

            $resolvedPrice = resolvePrice($product, $stock, $batch, $quantity);
            $unitPrice = (float) ($resolvedPrice['price'] ?? 0);
            $unitSalePrice = (float) ($resolvedPrice['sale_price'] ?? $unitPrice);
            $beforeProductAndBatchDiscount = (float) ($resolvedPrice['before_productandbatch_discount'] ?? $unitSalePrice);
            $mrpPrice = $batch ? ($batch->mrp_price ?? $stock->mrp_price ?? $product->mrp_price) : ($stock->mrp_price ?? $product->mrp_price);
            $tax = CartUtility::tax_calculation($product, $unitSalePrice);

            $line = new Cart;
            $line->id = $lineId++;
            $line->status = 1;
            $line->owner_id = $product->user_id;
            $line->user_id = $customer->id;
            $line->address_id = $addressId;
            $line->product_id = $product->id;
            $line->variation = $stock->variant;
            $line->id_variant = $stock->id_variant;
            $line->batch_id = $batch ? $batch->id : null;
            $line->quantity = $quantity;
            $line->is_scheme = 0;
            $line->price = $unitPrice;
            $line->before_productandbatch_discount = $beforeProductAndBatchDiscount;
            $line->mrp_price = $mrpPrice;
            $line->sale_price = $unitSalePrice;
            $line->tax = $tax;
            $line->shipping_cost = 0;
            $line->shipping_type = 'home_delivery';
            $line->discount = 0;
            $line->coupon_code = null;
            $line->coupon_applied = 0;
            $line->product_referral_code = null;
            $line->setRelation('product', $product);

            $lines->push($line);
        }

        if ($lines->isEmpty()) {
            $this->fail('items', translate('Please add at least one product.'));
        }

        $this->prepareSchemeAllocations($lines);

        return $lines;
    }

    protected function summarizeLines(Collection $lines): array
    {
        $schemeAllocationsByGroup = $this->prepareSchemeAllocations($lines);
        $schemeQtyByGroup = collect($schemeAllocationsByGroup)
            ->map(fn ($row) => collect($row['allocations'] ?? [])->sum('quantity'))
            ->all();

        $subtotal = 0;
        $tax = 0;
        $shipping = 0;
        $couponDiscount = 0;
        $productDiscount = 0;

        $summaryLines = $lines->values()->map(function ($line, $index) use (&$subtotal, &$tax, &$shipping, &$couponDiscount, &$productDiscount, $schemeQtyByGroup) {
            $product = Product::find($line['product_id']);
            $qty = (int) $line['quantity'];
            $lineSubtotal = (float) $line['sale_price'] * $qty;
            $lineTax = (float) ($line['tax'] ?? 0) * $qty;
            $lineShipping = (float) ($line['shipping_cost'] ?? 0);
            $lineCoupon = (float) ($line['discount'] ?? 0);
            $lineProductDiscount = round(max(0, (float) $line['before_productandbatch_discount'] - (float) $line['sale_price']) * $qty, 2);
            $groupKey = (int) $line['product_id'] . '|' . (string) ($line['variation'] ?? '');

            $subtotal += $lineSubtotal;
            $tax += $lineTax;
            $shipping += $lineShipping;
            $couponDiscount += $lineCoupon;
            $productDiscount += $lineProductDiscount;

            return [
                'index' => $index,
                'product_id' => (int) $line['product_id'],
                'product_name' => $product ? $product->getTranslation('name') : '',
                'variation' => $line['variation'],
                'id_variant' => $line['id_variant'],
                'batch_id' => $line['batch_id'],
                'batch_name' => optional(ProductBatch::find($line['batch_id']))->batch,
                'quantity' => $qty,
                'mrp_price' => round((float) ($line['mrp_price'] ?? 0), 2),
                'price' => round((float) ($line['price'] ?? 0), 2),
                'before_productandbatch_discount' => round((float) ($line['before_productandbatch_discount'] ?? 0), 2),
                'sale_price' => round((float) ($line['sale_price'] ?? 0), 2),
                'discount_amount' => $lineProductDiscount,
                'tax' => round($lineTax, 2),
                'coupon_discount' => round($lineCoupon, 2),
                'shipping_cost' => round($lineShipping, 2),
                'line_total' => round($lineSubtotal + $lineTax + $lineShipping - $lineCoupon, 2),
                'scheme_quantity' => (int) ($schemeQtyByGroup[$groupKey] ?? 0),
            ];
        })->all();

        return [
            'lines' => $summaryLines,
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'shipping' => round($shipping, 2),
            'product_discount' => round($productDiscount, 2),
            'coupon_discount' => round($couponDiscount, 2),
            'scheme_quantity' => (int) array_sum($schemeQtyByGroup),
            'grand_total' => round(max(0, $subtotal + $tax + $shipping - $couponDiscount), 2),
        ];
    }

    protected function prepareSchemeAllocations(Collection $sellerProduct): array
    {
        $schemeAllocationsByGroup = [];
        $paidSellerItems = $sellerProduct->filter(function ($item) {
            return !(bool) ($item['is_scheme'] ?? false);
        });

        foreach ($paidSellerItems->groupBy(function ($item) {
            return (int) $item['product_id'] . '|' . (string) ($item['variation'] ?? '');
        }) as $groupKey => $groupItems) {
            $firstItem = $groupItems->first();
            $groupProduct = Product::find($firstItem['product_id']);
            $groupStock = $groupProduct ? $this->stockForLine($groupProduct, $firstItem) : null;

            if (!$groupProduct) {
                $this->fail('items', translate('Invalid product selected.'));
            }

            if ($groupProduct->digital == 1) {
                $schemeAllocationsByGroup[$groupKey] = ['allocations' => []];
                continue;
            }

            if (!$groupStock) {
                $this->fail('items', translate('Selected product stock is not available for ') . $groupProduct->getTranslation('name'));
            }

            $reservations = [];
            $unbatchedQty = 0;
            foreach ($groupItems as $line) {
                if (!empty($line['batch_id'])) {
                    $lineBatch = $groupStock->batches()->where('id', $line['batch_id'])->first();
                    if (!$lineBatch || is_batch_expired($lineBatch)) {
                        $this->fail('items', translate('Invalid batch selected for ') . $groupProduct->getTranslation('name'));
                    }
                    $reservations[(int) $lineBatch->id] = ($reservations[(int) $lineBatch->id] ?? 0) + (int) $line['quantity'];
                } else {
                    $unbatchedQty += (int) $line['quantity'];
                }
            }

            if ($unbatchedQty > (int) $groupStock->qty) {
                $this->fail('items', translate('The requested quantity is not available for ') . $groupProduct->getTranslation('name'));
            }

            foreach ($reservations as $reservedBatchId => $reservedQty) {
                $reservedBatch = $groupStock->batches()->where('id', $reservedBatchId)->first();
                if (!$reservedBatch || (int) $reservedBatch->qty < (int) $reservedQty) {
                    $this->fail('items', translate('The requested quantity is not available for ') . $groupProduct->getTranslation('name'));
                }
            }

            $minQty = $groupStock->min_qty ?? $groupProduct->min_qty ?? 1;
            $schemeQty = calculate_scheme_qty($groupItems->sum('quantity'), $minQty, (int) ($groupStock->scheme ?? 0));
            $schemePreview = allocate_scheme_free_batches($groupStock, $schemeQty, $reservations);
            if (!$schemePreview['success']) {
                $this->fail('items', translate('The requested scheme quantity is not available for ') . $groupProduct->getTranslation('name'));
            }

            $schemeAllocationsByGroup[$groupKey] = [
                'scheme_qty' => $schemeQty,
                'allocations' => $schemePreview['allocations'],
            ];
        }

        return $schemeAllocationsByGroup;
    }

    protected function applyBackendCoupon(User $customer, Collection $lines, string $couponCode, bool $throwOnInvalid = true): void
    {
        foreach ($lines as $line) {
            $line->discount = 0;
            $line->coupon_code = null;
            $line->coupon_applied = 0;
        }

        if ($couponCode === '') {
            return;
        }

        $coupon = Coupon::where('code', $couponCode)->first();
        $userCoupon = null;
        $message = translate('This coupon is not applicable to your cart products!');

        if (!$coupon) {
            if ($throwOnInvalid) {
                $this->fail('coupon_code', translate('Invalid coupon code.'));
            }
            return;
        }

        if ($coupon->type === 'welcome_base') {
            $userCoupon = $customer->userCoupon;
            if (!$userCoupon || $userCoupon->expiry_date < strtotime(date('d-m-Y H:i:s'))) {
                if ($throwOnInvalid) {
                    $this->fail('coupon_code', translate('This coupon is expired.'));
                }
                return;
            }
        } else {
            $validDate = strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date;
            if (!$validDate) {
                if ($throwOnInvalid) {
                    $this->fail('coupon_code', translate('This coupon is expired.'));
                }
                return;
            }
        }

        if (CouponUsage::where('user_id', $customer->id)->where('coupon_id', $coupon->id)->exists()) {
            if ($throwOnInvalid) {
                $this->fail('coupon_code', translate('You already used this coupon!'));
            }
            return;
        }

        $ownerLines = $lines->filter(fn ($line) => (int) ($line['owner_id'] ?? 0) === (int) $coupon->user_id)->values();
        $couponResult = coupon_cart_discount_allocations($coupon, $ownerLines, json_decode($coupon->details), $userCoupon);

        if (($couponResult['discount'] ?? 0) <= 0) {
            if ($throwOnInvalid) {
                if (($couponResult['excluded_discounted_items_count'] ?? 0) > 0) {
                    $message = translate('Coupon discount is not applied to products that already have product or batch discounts.');
                }
                $this->fail('coupon_code', $message);
            }
            return;
        }

        foreach ($ownerLines as $line) {
            $lineDiscount = $couponResult['allocations'][(int) $line->id] ?? 0;
            if ($lineDiscount <= 0) {
                continue;
            }
            $line->discount = $lineDiscount;
            $line->coupon_code = $couponCode;
            $line->coupon_applied = 1;
        }
    }

    protected function assignBackendShippingCosts(Collection $lines, Request $request): void
    {
        foreach ($lines as $line) {
            $line->shipping_cost = 0;
            $line->shipping_type = 'home_delivery';
        }

        $shippingCosts = (array) $request->input('shipping_costs', $request->input('seller_shipping_costs', []));
        $globalShipping = (float) $request->input('shipping_cost', 0);
        $groups = $lines->groupBy(fn ($line) => (int) ($line['owner_id'] ?? 0));

        foreach ($groups as $sellerId => $sellerLines) {
            $cost = array_key_exists($sellerId, $shippingCosts)
                ? (float) $shippingCosts[$sellerId]
                : ($groups->count() === 1 ? $globalShipping : 0);

            $firstPaidLine = $sellerLines->first(fn ($line) => !(bool) ($line['is_scheme'] ?? false));
            if ($firstPaidLine) {
                $firstPaidLine->shipping_cost = max(0, $cost);
            }
        }
    }

    protected function resolveBackendAddress(User $customer, Request $request, string $type, bool $required): ?Address
    {
        $prefix = $type === Address::TYPE_BILLING ? 'billing' : 'shipping';
        $addressId = $request->input($prefix . '_address_id');

        if ($addressId) {
            $address = Address::where('user_id', $customer->id)->find($addressId);
            if (!$address) {
                $this->fail($prefix . '_address_id', translate('Invalid address selected.'));
            }
            return $address;
        }

        if (!$request->boolean('new_' . $prefix . '_address')) {
            if ($required) {
                $this->fail($prefix . '_address_id', translate('Please select or add an address.'));
            }
            return null;
        }

        foreach (['address', 'country_id', 'state_id', 'city_id', 'postal_code', 'phone'] as $field) {
            if (!$request->filled($prefix . '_' . $field)) {
                $this->fail($prefix . '_' . $field, translate('Please complete the address details.'));
            }
        }

        return Address::create([
            'user_id' => $customer->id,
            'type' => $type,
            'address' => $request->input($prefix . '_address'),
            'country_id' => $request->input($prefix . '_country_id'),
            'state_id' => $request->input($prefix . '_state_id'),
            'city_id' => $request->input($prefix . '_city_id'),
            'postal_code' => $request->input($prefix . '_postal_code'),
            'phone' => $request->input($prefix . '_phone'),
            'set_default' => 0,
        ]);
    }

    protected function buildAddressPayload(User $customer, ?Address $address): array
    {
        if ($address === null) {
            return [];
        }

        $payload = [
            'name' => $customer->name,
            'email' => $customer->email,
            'address' => $address->address,
            'country' => optional($address->country)->name,
            'state' => optional($address->state)->name,
            'city' => optional($address->city)->name,
            'postal_code' => $address->postal_code,
            'phone' => $address->phone,
        ];

        if ($address->latitude || $address->longitude) {
            $payload['lat_lang'] = $address->latitude . ',' . $address->longitude;
        }

        return $payload;
    }

    protected function resolveBackendStock(Product $product, array $item): ?ProductStock
    {
        $query = $product->stocks()->with(['batches', 'wholesalePrices'])->where('is_hidden', 0);

        if (!empty($item['stock_id'])) {
            return (clone $query)->where('id', $item['stock_id'])->first();
        }

        if (!empty($item['id_variant'])) {
            return (clone $query)->where('id_variant', $item['id_variant'])->first();
        }

        if (array_key_exists('variation', $item) && $item['variation'] !== '') {
            return (clone $query)->where('variant', $item['variation'])->first();
        }

        return $query->first();
    }

    protected function stockForLine(Product $product, $line): ?ProductStock
    {
        $query = $product->stocks()->with(['batches', 'wholesalePrices'])->where('is_hidden', 0);
        $hasStockIdentity = false;

        if (!empty($line['id_variant'])) {
            $hasStockIdentity = true;
            $stock = (clone $query)->where('id_variant', $line['id_variant'])->first();
            if ($stock) {
                return $stock;
            }
        }

        if (($line['variation'] ?? null) !== null && (string) $line['variation'] !== '') {
            $hasStockIdentity = true;
            $stock = (clone $query)->where('variant', $line['variation'])->first();
            if ($stock) {
                return $stock;
            }
        }

        if ($hasStockIdentity) {
            return null;
        }

        return $query->first();
    }

    protected function syncStockQuantityFromBatches(?ProductStock $stock): void
    {
        if (!$stock) {
            return;
        }

        $stock->load('batches');
        if ($stock->batches->isNotEmpty()) {
            $stock->qty = $stock->batches->sum('qty');
            $stock->save();
        }
    }

    protected function resolveTransport(Request $request): ?Transport
    {
        $id = (int) $request->input('transport_id');
        if ($id > 0) {
            $transport = Transport::find($id);
            if ($transport) {
                return $transport;
            }
        }

        $name = trim((string) $request->input('transport_name'));
        if ($name === '') {
            return null;
        }

        $transport = Transport::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($transport) {
            return $transport;
        }

        return Transport::create([
            'name' => $name,
            'status' => 'inactive',
            'created_by' => Auth::id(),
        ]);
    }

    protected function resolveBookedTo(Request $request, ?Transport $transport): ?BookedTo
    {
        if (!$transport) {
            return null;
        }

        $id = (int) $request->input('booked_to_id');
        if ($id > 0) {
            $bookedTo = BookedTo::where('transport_id', $transport->id)->where('id', $id)->first();
            if ($bookedTo) {
                return $bookedTo;
            }
        }

        $location = trim((string) $request->input('booked_to_name'));
        if ($location === '') {
            return null;
        }

        $bookedTo = BookedTo::where('transport_id', $transport->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($location)])
            ->first();
        if ($bookedTo) {
            return $bookedTo;
        }

        return BookedTo::create([
            'transport_id' => $transport->id,
            'name' => $location,
            'status' => 'inactive',
            'created_by' => Auth::id(),
        ]);
    }

    protected function resolveLocalDeliveryPartner(Request $request): ?LocalDeliveryPartner
    {
        $id = (int) $request->input('local_delivery_partner_id');
        if ($id > 0) {
            $partner = LocalDeliveryPartner::find($id);
            if ($partner) {
                return $partner;
            }
        }

        $name = trim((string) $request->input('local_delivery_partner_name'));
        if ($name === '') {
            return null;
        }

        $partner = LocalDeliveryPartner::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($partner) {
            return $partner;
        }

        return LocalDeliveryPartner::create([
            'name' => $name,
            'status' => 'inactive',
            'created_by' => Auth::id(),
        ]);
    }

    protected function runAfterPlacementHooks(CombinedOrder $combinedOrder, array $options): void
    {
        $combinedOrder->load('orders.orderDetails');

        foreach ($combinedOrder->orders as $order) {
            if (($options['payment_status'] ?? 'unpaid') === 'paid') {
                EmailUtility::order_email($order, 'paid');
                calculateCommissionAffilationClubPoint($order);
                finalize_referral_rewards_for_paid_order($order);
                app(WalletRewardService::class)->applyReward($order);
            }

            if (!empty($options['send_notification'])) {
                NotificationUtility::sendOrderPlacedNotification($order);
                $order->notified = 1;
                $order->save();
            }
        }
    }

    protected function withPricingUser(User $customer, callable $callback)
    {
        $hadPrevious = app()->bound('pricing_user');
        $previous = $hadPrevious ? app('pricing_user') : null;

        app()->instance('pricing_user', $customer);

        try {
            return $callback();
        } finally {
            app()->forgetInstance('pricing_user');
            if ($hadPrevious) {
                app()->instance('pricing_user', $previous);
            }
        }
    }

    protected function fail(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
