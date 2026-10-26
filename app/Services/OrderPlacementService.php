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
use App\Models\ShippingMethod;
use App\Models\Transport;
use App\Models\Airport;
use App\Models\SeaPort;
use App\Support\DocumentType;
use App\Support\InvoiceType;
use App\Support\ShippingPath;
use App\Models\User;
use App\Utility\CartUtility;
use App\Utility\EmailUtility;
use App\Utility\NotificationUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

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

        if ($request->has('shipping_same_as_billing')) {
            $billingAddress = $this->resolveBackendAddress($customer, $request, Address::TYPE_BILLING, true);
            $shippingAddress = $request->boolean('shipping_same_as_billing')
                ? $billingAddress
                : $this->resolveBackendAddress($customer, $request, Address::TYPE_SHIPPING, true);
        } else {
            $shippingAddress = $this->resolveBackendAddress($customer, $request, Address::TYPE_SHIPPING, true);
            $billingAddress = $this->resolveBackendAddress($customer, $request, Address::TYPE_BILLING, false) ?: $shippingAddress;
        }

        $lines = $this->withPricingUser($customer, function () use ($customer, $request, $shippingAddress) {
            return $this->buildBackendLines($customer, (array) $request->input('items', []), $request, optional($shippingAddress)->id);
        });

        $this->assignBackendShippingCosts($lines, $request);
        if ($request->boolean('additional_discount_enabled')) {
            $this->applyBackendAdditionalDiscount(
                $lines,
                $request->input('additional_discount'),
                (string) $request->input('additional_discount_type', 'percent')
            );
        }

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
            'document_type' => $request->input('document_type'),
            'document_type_custom' => $request->input('document_type_custom'),
        ], $options);

        return $this->placeFromLines($customer, $lines, $request, $options);
    }

    public function summarizeBackendRequest(Request $request): array
    {
        $customer = $this->resolveApprovedCustomer($request->input('customer_id'));

        $lines = $this->withPricingUser($customer, function () use ($customer, $request) {
            return $this->buildBackendLines($customer, (array) $request->input('items', []), $request, $this->backendShippingAddressId($request));
        });

        $this->assignBackendShippingCosts($lines, $request);
        $discountType = (string) $request->input('additional_discount_type', 'percent');
        $discountValue = $request->input('additional_discount');
        if ($request->boolean('additional_discount_enabled')) {
            $this->applyBackendAdditionalDiscount(
                $lines,
                $discountValue,
                $discountType,
                $request->boolean('validate_additional_discount')
            );
        }

        $summary = $this->summarizeLines(
            $lines,
            DocumentType::stockEffect($request->input('document_type'), $request->input('document_type_custom')),
            $customer,
            $request
        );
        if ($request->boolean('additional_discount_enabled') && $summary['coupon_discount'] > 0) {
            $summary['additional_discount'] = [
                'discount_type' => $discountType,
                'discount_value' => (float) $discountValue,
            ];
        }
        $summary['wallet_reward_preview'] = get_gift_reward_preview((float) $summary['grand_total']);

        return $summary;
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
                'base_sale_price' => $request->input('base_sale_price'),
                'sale_price' => $request->input('sale_price'),
            ]], $request, $this->backendShippingAddressId($request));

            $summary = $this->summarizeLines(
                $lines,
                DocumentType::stockEffect($request->input('document_type'), $request->input('document_type_custom')),
                $customer,
                $request
            );

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
        // Ignore storefront-generated scheme rows because they are recalculated
        // server-side, but retain trusted manual free lines created by backend.
        $lines = $lines->filter(fn ($line) => !(bool) ($line['is_scheme'] ?? false)
            || (bool) ($line['is_manual_scheme'] ?? false))->values();

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

            $sellerOrderIndex = 0;
            foreach ($sellerProducts as $sellerProduct) {
                $sellerOptions = array_merge($options, ['seller_order_index' => $sellerOrderIndex++]);
                $order = $this->createSellerOrder($combinedOrder, $customer, $request, $sellerOptions, $shippingAddress, $billingAddress);
                $this->writeOrderDetails($order, collect($sellerProduct), $customer, $sellerOptions);

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
        $invoiceType = InvoiceType::forUser($customer);
        $isBackendOrder = ($options['source'] ?? null) === 'backend';
        $paymentType = $options['payment_type'] ?? $request->input('payment_option');
        if ($isBackendOrder) {
            $paymentType = $request->input('payment_type', $paymentType);
            if (!array_key_exists((string) $paymentType, InvoiceType::paymentTerms($invoiceType))) {
                $this->fail('payment_type', translate('Please select payment terms allowed for this customer type.'));
            }
        }

        $deliveryTerm = $request->input('transport_delivery_type');
        $isInternational = $invoiceType === InvoiceType::INTERNATIONAL;
        $hasShippingSection = !$isBackendOrder || $request->filled('shipping_method');
        $hasCarrierSection = !$isBackendOrder || $request->hasAny(['transport_id', 'transport_name', 'local_delivery_partner_id', 'local_delivery_partner_name', 'shipping_method_id', 'courier_service']);
        $hasSourceDestSection = !$isBackendOrder || $request->hasAny(['booked_to_id', 'booked_to_name', 'booked_from_id', 'booked_from_name', 'final_destination', 'loading_location_type', 'discharge_location_type']);
        $shippingChoice = $request->input('shipping_method') ?: 'courier';
        $usesPortLogistics = $isBackendOrder
            && $hasShippingSection
            && $hasSourceDestSection
            && $shippingChoice !== 'local'
            && in_array($request->input('fod_mode'), ['sea', 'air'], true);
        if ($isBackendOrder) {
            if ($deliveryTerm !== null && $deliveryTerm !== '' && !array_key_exists((string) $deliveryTerm, InvoiceType::deliveryTerms($invoiceType))) {
                $this->fail('transport_delivery_type', translate('Please select terms of delivery allowed for this customer type.'));
            }
            if ($usesPortLogistics) {
                $this->validatePortLogistics($request);
            }
        }

        $transport = null;
        $bookedTo = null;
        $bookedFrom = null;
        $localDeliveryPartner = null;
        $courierMethod = null;

        if ($hasShippingSection && $hasCarrierSection && $shippingChoice === 'transport') {
            $transport = $this->resolveTransport($request);
            if ($transport && $request->filled('fod_mode') && $transport->mode !== $request->input('fod_mode')) {
                $this->fail('transport_id', translate('The selected transport does not support this transport mode.'));
            }
            $transportAndBookedToAreSame = $this->transportAndBookedToAreSame($request, $transport);
            $bookedTo = $transportAndBookedToAreSame ? null : $this->resolveBookedLocation($request, $transport, 'booked_to_id', 'booked_to_name');
            $bookedFrom = $this->resolveBookedLocation($request, $transport, 'booked_from_id', 'booked_from_name');
            if (!$transport || ($hasSourceDestSection && !$usesPortLogistics && !$bookedTo && !$transportAndBookedToAreSame)) {
                $this->fail('booked_to_id', translate('Please select a transport provider and its booked-to destination.'));
            }
            if (!in_array($request->input('fod_mode'), ['air', 'sea', 'surface'], true)) {
                $this->fail('fod_mode', translate('Please select a transport mode.'));
            }
            if (!ShippingPath::isValidSubMode($request->input('fod_mode'), $request->input('transport_surface_mode'))) {
                $this->fail('transport_surface_mode', translate('Please select a valid mode for this transport path.'));
            }
            if (!$isBackendOrder && !in_array($deliveryTerm, ['door_delivery', 'our_warehouse_delivery', 'hand_delivery', 'transport_warehouse', 'transport_godown'], true)) {
                $this->fail('transport_delivery_type', translate('Please select a delivery type.'));
            }
        } elseif ($hasShippingSection && $hasCarrierSection && $shippingChoice === 'local') {
            $localDeliveryPartner = $this->resolveLocalDeliveryPartner($request);
            if (!$localDeliveryPartner) {
                $this->fail('local_delivery_partner_id', translate('Please select or enter a local delivery partner.'));
            }
        } elseif ($hasShippingSection && $hasCarrierSection) {
            $courierMethod = ShippingMethod::where('is_active', 1)->find($request->input('shipping_method_id'));
            if (!$courierMethod || !$request->filled('courier_service')) {
                $this->fail('courier_service', translate('Please select a courier provider and service.'));
            }
        }

        if ($isBackendOrder && $hasShippingSection) {
            if ($shippingChoice === 'local') {
                $request->merge(['fod_mode' => 'surface']);
            }
            if (!in_array($request->input('fod_mode'), ['air', 'sea', 'surface'], true)) {
                $this->fail('fod_mode', translate('Please select a transport mode.'));
            }
            if (!ShippingPath::isValidSubMode($request->input('fod_mode'), $request->input('transport_surface_mode'))) {
                $this->fail('transport_surface_mode', translate('Please select a valid mode for this transport path.'));
            }
        }

        $order = new Order;
        $order->combined_order_id = $combinedOrder->id;
        $order->user_id = $customer->id;
        $order->shipping_address = $combinedOrder->shipping_address;
        $order->billing_address = json_encode(!empty($billingAddress) ? $billingAddress : $shippingAddress);
        $order->additional_info = ($options['source'] ?? null) === 'backend'
            ? $this->capitalizeFirst($request->input('additional_info'))
            : $request->additional_info;
        $order->payment_type = $paymentType;
        $order->payment_status = ($options['payment_status'] ?? 'unpaid') === 'paid' ? 'paid' : 'unpaid';
        $order->shipping_choice = $shippingChoice;
        $order->shipping_by = $shippingChoice === 'courier'
            ? optional($courierMethod)->slug
            : ($shippingChoice === 'transport' ? optional($transport)->name : optional($localDeliveryPartner)->name);
        $order->fod_mode = $isBackendOrder
            ? $request->input('fod_mode')
            : ($shippingChoice === 'transport' ? $request->fod_mode : null);
        $order->shipping_courier_id = $shippingChoice === 'courier' ? $request->courier_service : null;
        $order->transport_id = optional($transport)->id;
        $order->booked_to_id = optional($bookedTo)->id;
        if (Schema::hasColumn($order->getTable(), 'booked_from_id')) {
            $order->booked_from_id = optional($bookedFrom)->id;
        }
        $order->local_delivery_partner_id = optional($localDeliveryPartner)->id;
        $order->transport_mode = $order->fod_mode;
        $order->transport_surface_mode = $isBackendOrder
            ? $request->input('transport_surface_mode')
            : (($shippingChoice === 'transport' && $request->fod_mode === 'surface') ? $request->transport_surface_mode : null);
        $normalizedDeliveryTerm = $deliveryTerm === 'transport_godown' ? 'transport_warehouse' : $deliveryTerm;
        $order->transport_delivery_type = $isBackendOrder || $shippingChoice === 'transport'
            ? $normalizedDeliveryTerm
            : null;
        $order->reverse_charge = $isBackendOrder && !$isInternational
            ? $this->nullableBoolean($request->input('reverse_charge'))
            : null;
        if ($isBackendOrder && Schema::hasColumn($order->getTable(), 'document_type')) {
            $storedDocumentType = DocumentType::store($request->input('document_type'), $request->input('document_type_custom'));
            $order->document_type = $storedDocumentType['document_type'];
            $order->document_type_custom = $storedDocumentType['document_type_custom'];
        }
        $order->loading_location_type = $usesPortLogistics ? $request->input('loading_location_type') : null;
        $order->loading_sea_port_id = $usesPortLogistics && $request->input('loading_location_type') === 'sea'
            ? $request->input('loading_sea_port_id')
            : null;
        $order->loading_airport_id = $usesPortLogistics && $request->input('loading_location_type') === 'air'
            ? $request->input('loading_airport_id')
            : null;
        $order->discharge_location_type = $usesPortLogistics ? $request->input('discharge_location_type') : null;
        $order->discharge_sea_port_id = $usesPortLogistics && $request->input('discharge_location_type') === 'sea'
            ? $request->input('discharge_sea_port_id')
            : null;
        $order->discharge_airport_id = $usesPortLogistics && $request->input('discharge_location_type') === 'air'
            ? $request->input('discharge_airport_id')
            : null;
        $order->final_destination = $isBackendOrder
            ? $this->nullableTrimmed($request->input('final_destination'))
            : null;
        $order->carrier_tax_number = $this->nullableTrimmed($request->input('carrier_tax_number'));
        $order->net_weight_kg = $request->filled('net_weight_kg') ? $request->input('net_weight_kg') : null;
        $order->gross_weight_kg = $request->filled('gross_weight_kg') ? $request->input('gross_weight_kg') : null;
        $order->total_volume_cbm = $request->filled('total_volume_cbm') ? $request->input('total_volume_cbm') : null;
        $this->assignInvoiceAdjustments($order, $request);
        $order->cases = $this->nullableTrimmed($request->input('cases'));
        $order->attached_file_name = $this->nullableTrimmed($request->input('attached_file_name'));
        $order->po_number = $this->nullableTrimmed($request->input('po_number'));
        $order->po_date = $request->input('po_date');
        $order->lr_number = $this->nullableTrimmed($request->input('lr_number'));
        $order->lr_date = $request->input('lr_date');
        $order->cc_attached_path = $request->input('cc_attached_path');
        $order->consignee_copy_status = $request->input('consignee_copy_status');
        $freightType = in_array($request->input('freight_type'), ['pre_paid', 'to_pay', 'fod'], true)
            ? $request->input('freight_type')
            : ($request->boolean('freight_paid') ? 'pre_paid' : null);
        $order->freight_type = $freightType;
        $order->freight_paid = $freightType === 'pre_paid';
        $shippingCostType = $request->input('shipping_cost_type');
        $order->free_shipping = $shippingCostType === 'free_shipping'
            || ($shippingCostType === null && $request->boolean('free_shipping'));
        $order->sales_person_id = $request->input('sales_executive_id') ?: $request->input('sales_person_id');
        $order->sales_executive_id = $order->sales_person_id;
        $order->sales_man_code = $this->nullableTrimmed(optional($customer->user_details)->salesman);
        $order->packed_by = $request->input('packed_by');
        $order->checked_by = $request->input('checked_by');
        $order->billing_by = $request->input('billing_by');

        $weightGrams = $request->filled('weight_grams') ? (float) $request->input('weight_grams') : null;
        $weightKg = $weightGrams !== null ? $weightGrams / 1000 : null;
        $order->weight_grams = $weightGrams;
        $order->weight_kg = $weightKg;
        $order->weight = $weightKg !== null ? rtrim(rtrim(number_format($weightKg, 6, '.', ''), '0'), '.') : null;

        $length = $request->filled('length_cm') ? (float) $request->input('length_cm') : null;
        $width = $request->filled('width_cm') ? (float) $request->input('width_cm') : null;
        $height = $request->filled('height_cm') ? (float) $request->input('height_cm') : null;
        $order->length_cm = $length;
        $order->width_cm = $width;
        $order->height_cm = $height;
        $extraCases = $this->normalizeExtraCases($request->input('extra_cases', []));
        $dimensionRows = [];
        if ($length !== null && $width !== null && $height !== null) {
            $dimensionRows[] = implode(' × ', [$length, $width, $height]) . ' CM';
        }
        foreach ($extraCases as $extraCase) {
            $dimensionRows[] = implode(' × ', [$extraCase['length_cm'], $extraCase['width_cm'], $extraCase['height_cm']]) . ' CM';
        }
        $order->dimensions = !empty($dimensionRows) ? implode(' | ', $dimensionRows) : null;
        if (Schema::hasColumn($order->getTable(), 'packing_cases')) {
            $order->packing_cases = !empty($extraCases) ? json_encode($extraCases) : null;
        }
        if (!$request->filled('total_volume_cbm') && (!empty($dimensionRows))) {
            $calculatedCbm = 0.0;
            if ($length !== null && $width !== null && $height !== null) {
                $calculatedCbm += ($length * $width * $height) / 1000000;
            }
            foreach ($extraCases as $extraCase) {
                $calculatedCbm += ($extraCase['length_cm'] * $extraCase['width_cm'] * $extraCase['height_cm']) / 1000000;
            }
            if ($calculatedCbm > 0) {
                $order->total_volume_cbm = round($calculatedCbm, 6);
            }
        }
        $order->delivery_viewed = '0';
        $order->payment_status_viewed = '0';
        $orderMoment = $request->filled('order_date') && $request->filled('order_time')
            ? Carbon::createFromFormat('Y-m-d H:i', $request->input('order_date') . ' ' . $request->input('order_time'), config('app.timezone'))
            : Carbon::now();
        $order->code = generate_financial_year_order_code(
            $orderMoment,
            $request->input('order_code_letter'),
            $request->input('order_company_code')
        );
        $order->order_date = $orderMoment->toDateString();
        $order->order_time = $orderMoment->format('H:i:s');
        $order->date = $orderMoment->timestamp;
        $order->save();

        storeIPLocation('orders', $order->id);

        return $order;
    }

    protected function nullableTrimmed($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function nullableBoolean($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
    }

    protected function capitalizeFirst($value): ?string
    {
        $value = $this->nullableTrimmed($value);
        if ($value === null) {
            return null;
        }

        return preg_replace_callback('/^(\s*)(\p{L})/u', function ($matches) {
            return $matches[1] . mb_strtoupper($matches[2], 'UTF-8');
        }, $value) ?: $value;
    }

    protected function writeOrderDetails(Order $order, Collection $sellerProduct, User $customer, array $options): void
    {
        $subtotal = 0;
        $tax = 0;
        $shipping = 0;
        $couponDiscount = 0;
        $affectedProductIds = [];
        $schemeGroupsWritten = [];
        $stockEffect = DocumentType::stockEffect(
            $order->document_type ?? ($options['document_type'] ?? null),
            $order->document_type_custom ?? ($options['document_type_custom'] ?? null)
        );
        $options['stock_effect'] = $stockEffect;
        $schemeAllocationsByGroup = $this->prepareSchemeAllocations($sellerProduct, $stockEffect);

        foreach ($sellerProduct as $cartItem) {
            $product = Product::find($cartItem['product_id']);
            if (!$product) {
                $this->fail('items', translate('Invalid product selected.'));
            }

            if ((bool) ($cartItem['is_manual_scheme'] ?? false)) {
                $manualSchemeDetail = $this->writeManualSchemeDetail($order, $cartItem, $product, $options);
                $shipping += (float) $manualSchemeDetail->shipping_cost;
                $affectedProductIds[] = (int) $product->id;
                $order->seller_id = $product->user_id;
                $order->shipping_type = $cartItem['shipping_type'] ?? 'home_delivery';
                continue;
            }

            if ((bool) ($cartItem['is_scheme'] ?? false)) {
                continue;
            }

            $unitSalePrice = $cartItem['sale_price'] ?? cart_product_price($cartItem, $product, false, false);
            $unitBasePrice = $cartItem['before_productandbatch_discount'] ?? $cartItem['price'] ?? $unitSalePrice;
            $quantity = (int) $cartItem['quantity'];

            $subtotal += $unitSalePrice * $quantity;
            $itemTax = round(($cartItem['tax'] ?? cart_product_tax($cartItem, $product, false)) * $quantity, 3);
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

                    if ($stockEffect === 'minus') {
                        if ($quantity > (int) $selectedBatch->qty) {
                            $this->fail('items', translate('The requested quantity is not available for ') . $product->getTranslation('name'));
                        }
                        $selectedBatch->qty -= $quantity;
                        $selectedBatch->save();
                        $this->syncStockQuantityFromBatches($productStock);
                        $affectedProductIds[] = (int) $product->id;
                    } elseif ($stockEffect === 'plus') {
                        $selectedBatch->qty += $quantity;
                        $selectedBatch->save();
                        $this->syncStockQuantityFromBatches($productStock);
                    }
                } else {
                    $lockedStock = ProductStock::where('id', $productStock->id)
                        ->where('is_hidden', 0)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedStock) {
                        $this->fail('items', translate('Selected product stock is not available for ') . $product->getTranslation('name'));
                    }

                    if ($stockEffect === 'minus') {
                        if ($quantity > (int) $lockedStock->qty) {
                            $this->fail('items', translate('The requested quantity is not available for ') . $product->getTranslation('name'));
                        }
                        $lockedStock->qty -= $quantity;
                        $lockedStock->save();
                        $affectedProductIds[] = (int) $product->id;
                    } elseif ($stockEffect === 'plus') {
                        $lockedStock->qty += $quantity;
                        $lockedStock->save();
                    }
                }
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
            $productDiscountAmount = round(max(0, (float) $unitBasePrice - (float) $unitSalePrice) * $quantity, 3);
            $allocatedAdditionalDiscount = round((float) ($cartItem['discount'] ?? 0), 3);
            $orderDetail->discount_amount = round($productDiscountAmount + $allocatedAdditionalDiscount, 3);
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

        $order->grand_total = round($subtotal + $tax + $shipping, 3);
        $couponCode = $sellerProduct
            ->filter(fn ($item) => (float) ($item['discount'] ?? 0) > 0 && !empty($item['coupon_code']))
            ->pluck('coupon_code')
            ->first();

        if ($couponDiscount > 0) {
            $order->coupon_discount = $couponDiscount;
            $order->grand_total = round($order->grand_total - $couponDiscount, 3);

            if ($couponCode != null) {
                $coupon = Coupon::where('code', $couponCode)->first();
                if ($coupon) {
                    $couponUsage = new CouponUsage;
                    $couponUsage->user_id = $customer->id;
                    $couponUsage->coupon_id = $coupon->id;
                    $couponUsage->save();
                }
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
        $stockEffect = $options['stock_effect'] ?? DocumentType::stockEffect($order->document_type, $order->document_type_custom);
        $schemeBatchForDeduction = !empty($allocation['batch_id'])
            ? ProductBatch::where('id', $allocation['batch_id'])->lockForUpdate()->first()
            : null;

        if ($stockEffect === 'minus') {
            if (!$schemeBatchForDeduction || is_batch_expired($schemeBatchForDeduction) || (int) $schemeBatchForDeduction->qty < (int) $allocation['quantity']) {
                $this->fail('items', translate('The requested scheme quantity is not available for ') . $product->getTranslation('name'));
            }

            $schemeBatchForDeduction->qty -= (int) $allocation['quantity'];
            $schemeBatchForDeduction->save();
        } elseif ($stockEffect === 'plus' && $schemeBatchForDeduction) {
            $schemeBatchForDeduction->qty += (int) $allocation['quantity'];
            $schemeBatchForDeduction->save();
        }

        $schemeBatch = $allocation['batch'] ?? ($allocation['batch_id'] ? ProductBatch::find($allocation['batch_id']) : null);
        $schemeDetail = new OrderDetail;
        $schemeDetail->order_id = $order->id;
        $schemeDetail->seller_id = $product->user_id;
        $schemeDetail->product_id = $product->id;
        $schemeDetail->variation = $productVariation;
        $schemeDetail->batch_id = $allocation['batch_id'] ?? null;
        $schemeDetail->price = 0;
        $schemeDetail->before_productandbatch_discount = 0;
        $schemeDetail->sale_price = 0;
        $schemeDetail->mrp_price = optional($schemeBatch)->mrp_price ?? $paidDetail->mrp_price;
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

        if ($schemeBatchForDeduction && $schemeBatchForDeduction->product_stock_id && $stockEffect !== 'none') {
            $this->syncStockQuantityFromBatches(ProductStock::find($schemeBatchForDeduction->product_stock_id));
        }
    }

    protected function writeManualSchemeDetail(Order $order, $cartItem, Product $product, array $options): OrderDetail
    {
        $stockEffect = $options['stock_effect'] ?? DocumentType::stockEffect($order->document_type, $order->document_type_custom);
        $quantity = max(1, (int) ($cartItem['quantity'] ?? 1));
        $productStock = $this->stockForLine($product, $cartItem);
        if (!$productStock) {
            $this->fail('items', translate('Selected product stock is not available for ') . $product->getTranslation('name'));
        }

        $batchId = (int) ($cartItem['batch_id'] ?? 0);
        $batch = null;
        if ($batchId > 0) {
            $batch = ProductBatch::where('id', $batchId)
                ->where('product_stock_id', $productStock->id)
                ->lockForUpdate()
                ->first();

            if (!$batch || is_batch_expired($batch)) {
                $this->fail('items', translate('Invalid batch selected for ') . $product->getTranslation('name'));
            }

            if ($stockEffect === 'minus') {
                if ($quantity > (int) $batch->qty) {
                    $this->fail('items', translate('The requested scheme quantity is not available for ') . $product->getTranslation('name'));
                }
                $batch->qty -= $quantity;
                $batch->save();
                $this->syncStockQuantityFromBatches($productStock);
            } elseif ($stockEffect === 'plus') {
                $batch->qty += $quantity;
                $batch->save();
                $this->syncStockQuantityFromBatches($productStock);
            }
        } else {
            $lockedStock = ProductStock::where('id', $productStock->id)
                ->where('is_hidden', 0)
                ->lockForUpdate()
                ->first();

            if (!$lockedStock) {
                $this->fail('items', translate('Selected product stock is not available for ') . $product->getTranslation('name'));
            }

            if ($stockEffect === 'minus') {
                if ($quantity > (int) $lockedStock->qty) {
                    $this->fail('items', translate('The requested scheme quantity is not available for ') . $product->getTranslation('name'));
                }
                $lockedStock->qty -= $quantity;
                $lockedStock->save();
            } elseif ($stockEffect === 'plus') {
                $lockedStock->qty += $quantity;
                $lockedStock->save();
            }
        }

        $orderDetail = new OrderDetail;
        $orderDetail->order_id = $order->id;
        $orderDetail->seller_id = $product->user_id;
        $orderDetail->product_id = $product->id;
        $orderDetail->variation = $cartItem['variation'] ?? null;
        $orderDetail->batch_id = $batchId ?: null;
        $orderDetail->price = 0;
        $orderDetail->before_productandbatch_discount = 0;
        $orderDetail->sale_price = 0;
        $orderDetail->mrp_price = $cartItem['mrp_price'] ?? ($batch->mrp_price ?? $productStock->mrp_price ?? $product->mrp_price);
        $orderDetail->discount_amount = 0;
        $orderDetail->tax = 0;
        $orderDetail->shipping_type = $cartItem['shipping_type'] ?? 'home_delivery';
        $orderDetail->product_referral_code = null;
        $orderDetail->shipping_cost = (float) ($cartItem['shipping_cost'] ?? 0);
        $orderDetail->is_scheme = 1;
        $orderDetail->quantity = $quantity;
        if (($options['payment_status'] ?? 'unpaid') === 'paid') {
            $orderDetail->payment_status = 'paid';
        }
        $orderDetail->save();

        return $orderDetail;
    }

    protected function buildBackendLines(User $customer, array $items, Request $request, $addressId = null): Collection
    {
        $lines = collect();
        $lineId = 1;
        $stockEffect = DocumentType::stockEffect($request->input('document_type'), $request->input('document_type_custom'));
        $taxRule = DocumentType::taxRule($request->input('document_type'), $request->input('document_type_custom'));
        $requiresStockCheck = $stockEffect === 'minus';

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['product_id'])) {
                continue;
            }

            $product = Product::with(['stocks.batches', 'taxes'])
                ->where('approved', '1')
                ->where('published', 1)
                ->find($item['product_id']);

            if (!$product || (int) ($product->digital ?? 0) === 1) {
                $this->fail('items', translate('Invalid product selected.'));
            }

            if ($taxRule !== null) {
                $isTaxFreeProduct = $this->isProductTaxFree($product);
                if ($taxRule === 'tax_free_only' && !$isTaxFreeProduct) {
                    $this->fail('items', translate('Bill Of Supply is used only for Tax Free (Exempted) goods. Taxable goods are not allowed in Bill Of Supply.'));
                }
                if ($taxRule === 'taxable_only' && $isTaxFreeProduct) {
                    $this->fail('items', translate('Exempted (Tax Free) goods are not allowed in Sales Invoice. Taxable and Exempted goods must be invoiced separately.'));
                }
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
                if ($requiresStockCheck && $quantity > (int) $batch->qty) {
                    $this->fail('items', translate('The requested quantity is not available for ') . $product->getTranslation('name'));
                }
            } else {
                $stock->load('batches');
                $validBatches = valid_batches_for_stock($stock, true);
                if ($validBatches->isNotEmpty()) {
                    $this->fail('items', translate('Please select a batch for ') . $product->getTranslation('name'));
                }
                $availableQty = $stock->batches->isNotEmpty() ? (int) $validBatches->sum('qty') : (int) ($stock->qty ?? 0);
                if ($requiresStockCheck && $quantity > $availableQty) {
                    $this->fail('items', translate('The requested quantity is not available for ') . $product->getTranslation('name'));
                }
            }

            $this->prepareStockForPricing($stock, $batch);
            $resolvedPrice = resolvePrice($product, $stock, $batch, $quantity);
            // Pharmaceutical invoices require three-decimal rates. Keep that
            // precision through the line calculation so a percentage discount
            // is applied to the full line value (for example 29.700 - 2% =
            // 29.106) instead of a prematurely rounded two-decimal unit rate.
            $unitPrice = round((float) ($resolvedPrice['price'] ?? 0), 3);
            $unitSalePrice = round((float) ($resolvedPrice['sale_price'] ?? $unitPrice), 3);
            $beforeProductAndBatchDiscount = round((float) ($resolvedPrice['before_productandbatch_discount'] ?? $unitSalePrice), 3);
            $mrpPrice = $batch ? ($batch->mrp_price ?? $stock->mrp_price ?? $product->mrp_price) : ($stock->mrp_price ?? $product->mrp_price);

            $hasManualBasePrice = array_key_exists('base_sale_price', $item)
                && $item['base_sale_price'] !== null
                && $item['base_sale_price'] !== '';
            $hasManualSalePrice = array_key_exists('sale_price', $item)
                && $item['sale_price'] !== null
                && $item['sale_price'] !== '';

            if ($hasManualBasePrice) {
                if (!is_numeric($item['base_sale_price']) || (float) $item['base_sale_price'] < 0) {
                    $this->fail('base_sale_price', translate('Sale rate must be zero or greater.'));
                }

                $manualBasePrice = round((float) $item['base_sale_price'], 3);
                $unitPrice = $manualBasePrice;
                $beforeProductAndBatchDiscount = $manualBasePrice;
                $unitSalePrice = $this->discountBackendBasePrice(
                    $product,
                    $batch,
                    $quantity,
                    $manualBasePrice,
                    $resolvedPrice
                );
            } elseif ($hasManualSalePrice) {
                if (!is_numeric($item['sale_price']) || (float) $item['sale_price'] < 0) {
                    $this->fail('sale_price', translate('Sale price must be zero or greater.'));
                }
                $unitSalePrice = round((float) $item['sale_price'], 3);
            }
            $isManualScheme = ($hasManualBasePrice || $hasManualSalePrice) && $unitSalePrice === 0.0;
            if ($isManualScheme) {
                $unitPrice = 0.0;
                $beforeProductAndBatchDiscount = 0.0;
            }

            // Tax is accumulated from the taxable line value. Six-decimal unit
            // precision prevents quantity multiplication from changing the GST.
            $tax = round(CartUtility::tax_calculation($product, $unitSalePrice), 6);

            $line = new Cart;
            $line->id = $lineId++;
            $line->status = 1;
            $line->owner_id = $product->user_id;
            $line->user_id = $customer->id;
            $line->address_id = $addressId;
            $line->product_id = $product->id;
            $line->stock_id = $stock->id;
            $line->variation = $stock->variant;
            $line->id_variant = $stock->id_variant;
            $line->batch_id = $batch ? $batch->id : null;
            $line->quantity = $quantity;
            $line->is_scheme = $isManualScheme ? 1 : 0;
            $line->is_manual_scheme = $isManualScheme ? 1 : 0;
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

        $this->prepareSchemeAllocations($lines, $stockEffect);

        return $lines;
    }

    protected function isProductTaxFree(Product $product): bool
    {
        if (\App\Models\TaxMaster::tableReady() && !empty($product->product_hsn)) {
            $hsnLookup = \App\Models\TaxMaster::lookupByHsn((string) $product->product_hsn);
            if ($hsnLookup && !empty($hsnLookup['kind'])) {
                return \App\Models\TaxMaster::zeroRateKind((string) $hsnLookup['kind']);
            }
        }

        $taxSum = 0.0;
        if ($product->relationLoaded('taxes') || $product->taxes) {
            $taxSum = (float) $product->taxes->sum(fn ($row) => (float) ($row->tax ?? 0));
        }
        if ($taxSum <= 0 && isset($product->tax)) {
            $taxSum = (float) $product->tax;
        }

        return $taxSum <= 0.0001;
    }

    public function normalizeExtraCases($rawCases): array
    {
        if (!is_array($rawCases)) {
            return [];
        }

        $clean = [];
        foreach ($rawCases as $row) {
            if (!is_array($row)) {
                continue;
            }
            $l = isset($row['length_cm']) && $row['length_cm'] !== '' ? (float) $row['length_cm'] : null;
            $w = isset($row['width_cm']) && $row['width_cm'] !== '' ? (float) $row['width_cm'] : null;
            $h = isset($row['height_cm']) && $row['height_cm'] !== '' ? (float) $row['height_cm'] : null;
            if ($l === null && $w === null && $h === null) {
                continue;
            }
            if ($l === null || $w === null || $h === null) {
                $this->fail('extra_cases', translate('Please enter length, width, and height for every additional case dimension row.'));
            }
            $clean[] = [
                'length_cm' => round($l, 2),
                'width_cm' => round($w, 2),
                'height_cm' => round($h, 2),
                'cbm' => round(($l * $w * $h) / 1000000, 6),
            ];
        }

        return $clean;
    }

    /**
     * Apply the configured product and batch discounts to a manually entered
     * backend rate. The Create Order field represents the pre-discount PTS
     * rate, matching the invoice calculation sheet.
     */
    protected function discountBackendBasePrice(
        Product $product,
        ?ProductBatch $batch,
        int $quantity,
        float $basePrice,
        array $resolvedPrice
    ): float {
        if ($basePrice <= 0) {
            return 0.0;
        }

        // A valid batch discount takes precedence over the product discount.
        if ($batch && isBatchDiscountValid($batch, $quantity)) {
            $batchDiscount = max(0, (float) ($batch->discount ?? 0));
            $discountedPrice = $batch->discount_type === 'percent'
                ? $basePrice - (($basePrice * min(100, $batchDiscount)) / 100)
                : $basePrice - $batchDiscount;

            return round(max(0, $discountedPrice), 3);
        }

        $discountedPrice = $basePrice;
        $productDiscountIsActive = (float) ($resolvedPrice['product_discount_percent'] ?? 0) > 0;

        if ($productDiscountIsActive && $product->discount_type === 'percent') {
            $productPercent = min(99.99, max(0, (float) ($product->discount ?? 0)));
            $discountedPrice = $basePrice - (($basePrice * $productPercent) / 100);
        } elseif ($productDiscountIsActive && $product->discount_type === 'amount') {
            $discountedPrice = max(0, $basePrice - max(0, (float) ($product->discount ?? 0)));
        }

        return round(max(0, $discountedPrice), 3);
    }

    protected function isMaharashtraCustomer(?User $customer, ?Request $request = null): bool
    {
        $userDetails = $customer?->user_details;
        $billingState = null;
        if ($customer && $request && $request->filled('billing_address_id')) {
            $billingAddr = Address::with('state')->where('user_id', $customer->id)->find($request->input('billing_address_id'));
            $billingState = optional($billingAddr?->state)->name;
        }
        $stateBusiness = optional($userDetails?->businessState)->name
            ?: ($billingState ?: optional($userDetails?->personalState)->name);

        return $stateBusiness && strcasecmp(trim((string) $stateBusiness), 'maharashtra') === 0;
    }

    protected function splitInvoice2Discount(?Product $product, ?ProductBatch $batch, float $rate, float $gap): array
    {
        $bdPercent = 0.0;
        $pdPercent = 0.0;
        $bdUnit = 0.0;
        $pdUnit = 0.0;

        if ($batch && (float) ($batch->batch_discount_percent ?? 0) > 0) {
            $bdPercent = min(100, max(0, (float) $batch->batch_discount_percent));
        } elseif ($batch && ($batch->discount_type ?? null) === 'percent' && (float) ($batch->discount ?? 0) > 0) {
            $bdPercent = min(100, max(0, (float) $batch->discount));
        } elseif ($rate > 0 && $batch && ($batch->discount_type ?? null) === 'amount' && (float) ($batch->discount ?? 0) > 0) {
            $bdUnit = max(0, (float) $batch->discount);
            $bdPercent = ($bdUnit / $rate) * 100;
        }

        if ($batch && (float) ($batch->product_discount_percent ?? 0) > 0) {
            $pdPercent = min(100, max(0, (float) $batch->product_discount_percent));
        } elseif ($product && ($product->discount_type ?? null) === 'percent' && (float) ($product->discount ?? 0) > 0) {
            $pdPercent = min(100, max(0, (float) $product->discount));
        } elseif ($rate > 0 && $product && ($product->discount_type ?? null) === 'amount' && (float) ($product->discount ?? 0) > 0) {
            $pdUnit = max(0, (float) $product->discount);
            $pdPercent = ($pdUnit / $rate) * 100;
        }

        if ($bdPercent > 0 && $bdUnit <= 0) {
            $bdUnit = $rate * $bdPercent / 100;
        }
        if ($pdPercent > 0 && $pdUnit <= 0) {
            $pdUnit = $rate * $pdPercent / 100;
        }

        if ($bdUnit > 0.0005 && $pdUnit > 0.0005) {
            return [
                'bd_percent' => $bdPercent,
                'pd_percent' => $pdPercent,
                'bd_unit' => $bdUnit,
                'pd_unit' => $pdUnit,
                'stacked' => true,
            ];
        }

        if ($gap <= 0.0005) {
            return ['bd_percent' => 0.0, 'pd_percent' => 0.0, 'bd_unit' => 0.0, 'pd_unit' => 0.0, 'stacked' => false];
        }

        if ($bdUnit > 0.0005) {
            if (abs($bdUnit - $gap) > 0.05) {
                $bdUnit = $gap;
                $bdPercent = $rate > 0 ? ($gap / $rate) * 100 : 0.0;
            }
            return ['bd_percent' => $bdPercent, 'pd_percent' => 0.0, 'bd_unit' => $bdUnit, 'pd_unit' => 0.0, 'stacked' => false];
        }

        $pdUnit = $gap;
        $pdPercent = $rate > 0 ? ($gap / $rate) * 100 : 0.0;

        return ['bd_percent' => 0.0, 'pd_percent' => $pdPercent, 'bd_unit' => 0.0, 'pd_unit' => $pdUnit, 'stacked' => false];
    }

    protected function formatInvoice2MonthYear($date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }
        try {
            return Carbon::parse($date)->format('m-y');
        } catch (\Throwable $exception) {
            return '—';
        }
    }

    protected function summarizeLines(Collection $lines, string $stockEffect = 'minus', ?User $customer = null, ?Request $request = null): array
    {
        $schemeAllocationsByGroup = $this->prepareSchemeAllocations($lines, $stockEffect);
        $schemeQtyByGroup = collect($schemeAllocationsByGroup)
            ->map(fn ($row) => collect($row['allocations'] ?? [])->sum('quantity'))
            ->all();

        $isMaharashtra = $this->isMaharashtraCustomer($customer, $request);

        $subtotal = 0;
        $tax = 0;
        $shipping = 0;
        $couponDiscount = 0;
        $productDiscount = 0;
        $finalTotal = 0;
        $manualSchemeQuantity = 0;
        $shownSchemeGroups = [];

        $productSubtotal = 0.0;
        $productwiseDiscount = 0.0;
        $batchwiseDiscount = 0.0;
        $mrpValueTotal = 0.0;
        $schemeDiscountValue = 0.0;
        $productGrossTotal = 0.0;
        $taxableTotal = 0.0;
        $sgstTotal = 0.0;
        $cgstTotal = 0.0;
        $igstTotal = 0.0;
        $taxTotal = 0.0;
        $paidQtyTotal = 0;
        $schemeQtyTotal = 0;

        $summaryLines = $lines->values()->map(function ($line, $index) use (
            &$subtotal,
            &$tax,
            &$couponDiscount,
            &$productDiscount,
            &$finalTotal,
            &$manualSchemeQuantity,
            &$shownSchemeGroups,
            &$productSubtotal,
            &$productwiseDiscount,
            &$batchwiseDiscount,
            &$mrpValueTotal,
            &$schemeDiscountValue,
            &$productGrossTotal,
            &$taxableTotal,
            &$sgstTotal,
            &$cgstTotal,
            &$igstTotal,
            &$taxTotal,
            &$paidQtyTotal,
            &$schemeQtyTotal,
            $schemeQtyByGroup,
            $isMaharashtra
        ) {
            $product = Product::with(['brand', 'main_category', 'stocks'])->find($line['product_id']);
            $detailBatch = !empty($line['batch_id']) ? ProductBatch::find($line['batch_id']) : null;
            $rawQty = (int) $line['quantity'];
            $isManualScheme = (bool) ($line['is_manual_scheme'] ?? false);
            $groupKey = (int) $line['product_id'] . '|' . (string) ($line['variation'] ?? '');

            if ($isManualScheme) {
                $paidQty = 0;
                $lineSchemeQuantity = $rawQty;
                $manualSchemeQuantity += $rawQty;
            } else {
                $paidQty = $rawQty;
                $lineSchemeQuantity = isset($shownSchemeGroups[$groupKey])
                    ? 0
                    : (int) ($schemeQtyByGroup[$groupKey] ?? 0);
                $shownSchemeGroups[$groupKey] = true;
            }
            $displayTotalQty = $paidQty + $lineSchemeQuantity;
            $paidQtyTotal += $paidQty;
            $schemeQtyTotal += $lineSchemeQuantity;

            $saleUnit = (float) ($line['sale_price'] ?? 0);
            $rate = (float) ($line['before_productandbatch_discount'] ?? $saleUnit);
            if ($isManualScheme && $paidQty === 0) {
                $rate = 0.0;
                $saleUnit = 0.0;
            }
            $chargedUnit = $saleUnit;
            $gap = max(0, $rate - $chargedUnit);
            $rawLineTax = round((float) ($line['tax'] ?? 0) * $paidQty, 3);
            $split = $this->splitInvoice2Discount($product, $detailBatch, $rate, $gap);
            if (!empty($split['stacked'])) {
                $chargedBase = $chargedUnit * max(0, $paidQty);
                $chargedGstPercent = $chargedBase > 0 ? ($rawLineTax / $chargedBase) * 100 : 0.0;
                $saleUnit = max(0, $rate - (float) $split['bd_unit'] - (float) $split['pd_unit']);
                $gap = (float) $split['bd_unit'] + (float) $split['pd_unit'];
            }
            $productValue = $rate * max(0, $paidQty);
            $discountAmount = $gap * max(0, $paidQty);
            $taxableAmount = max(0, $saleUnit * max(0, $paidQty));
            $lineTax = $rawLineTax;
            if (!empty($split['stacked'])) {
                $lineTax = $taxableAmount * ($chargedGstPercent ?? 0) / 100;
            }

            if ($isMaharashtra) {
                $sgst = $lineTax / 2;
                $cgst = $lineTax / 2;
                $igst = 0.0;
            } else {
                $sgst = 0.0;
                $cgst = 0.0;
                $igst = $lineTax;
            }
            $sgstPercent = $taxableAmount > 0 ? ($sgst / $taxableAmount) * 100 : 0.0;
            $cgstPercent = $taxableAmount > 0 ? ($cgst / $taxableAmount) * 100 : 0.0;
            $igstPercent = $taxableAmount > 0 ? ($igst / $taxableAmount) * 100 : 0.0;
            $gstPercent = $sgstPercent + $cgstPercent + $igstPercent;

            $matchingStock = $product?->stocks?->where('variant', $line['variation'])->first() ?? $product?->stocks?->first();
            $mrp = (float) ($line['mrp_price'] ?? optional($detailBatch)->mrp_price ?? optional($matchingStock)->mrp_price ?? $product?->unit_price ?? 0);
            $mrpValue = $mrp * max(0, $displayTotalQty);
            $gross = $taxableAmount + $lineTax;

            $productName = $product ? $product->getTranslation('name') : '';
            $brandName = null;
            if ($product && $product->brand) {
                $brandName = $product->brand->getTranslation('name') ?: $product->brand->name;
            }
            $description = $brandName && strcasecmp((string) $brandName, (string) $productName) !== 0
                ? $productName . ' (' . $brandName . ')'
                : $productName;
            $category = $product?->main_category ? $product->main_category->getTranslation('name') : null;
            $mfg = $this->formatInvoice2MonthYear(optional($detailBatch)->manufacturing_date);
            $expiry = $this->formatInvoice2MonthYear(
                optional($detailBatch)->product_exp_date ?? optional($matchingStock)->product_exp_date ?? $product?->product_exp_date
            );

            $lineShipping = (float) ($line['shipping_cost'] ?? 0);
            $lineCoupon = (float) ($line['discount'] ?? 0);
            $productFinalAmount = round(max(0, $gross - $lineCoupon), 3);
            $finalAmount = round($productFinalAmount + $lineShipping, 3);

            $subtotal += round($taxableAmount, 3);
            $tax += round($lineTax, 3);
            $couponDiscount += $lineCoupon;
            $productDiscount += round($discountAmount, 3);
            $finalTotal += $finalAmount;

            $productSubtotal += $productValue;
            $productwiseDiscount += (float) $split['pd_unit'] * max(0, $paidQty);
            $batchwiseDiscount += (float) $split['bd_unit'] * max(0, $paidQty);
            $mrpValueTotal += $mrpValue;
            $schemeDiscountValue += $mrp * max(0, $lineSchemeQuantity);
            $productGrossTotal += $gross;
            $taxableTotal += $taxableAmount;
            $sgstTotal += $sgst;
            $cgstTotal += $cgst;
            $igstTotal += $igst;
            $taxTotal += $lineTax;

            return [
                'index' => $index,
                'product_id' => (int) $line['product_id'],
                'product_name' => $productName,
                'description' => $description,
                'variation' => $line['variation'],
                'id_variant' => $line['id_variant'],
                'batch_id' => $line['batch_id'],
                'batch_name' => optional($detailBatch)->batch,
                'mfg' => $mfg,
                'expiry' => $expiry,
                'category' => $category,
                'hsn' => $product?->product_hsn,
                'quantity' => $paidQty,
                'total_qty' => $displayTotalQty,
                'scheme_quantity' => $lineSchemeQuantity,
                'is_manual_scheme' => $isManualScheme,
                'scheme_only' => $paidQty === 0 && $lineSchemeQuantity > 0,
                'mrp_price' => round($mrp, 3),
                'mrp_value' => round($mrpValue, 3),
                'price' => round((float) ($line['price'] ?? 0), 3),
                'rate' => round($rate, 3),
                'before_productandbatch_discount' => round($rate, 3),
                'product_value' => round($productValue, 3),
                'bd_unit' => round((float) $split['bd_unit'], 3),
                'pd_unit' => round((float) $split['pd_unit'], 3),
                'bd_percent' => round((float) $split['bd_percent'], 3),
                'pd_percent' => round((float) $split['pd_percent'], 3),
                'sale_price' => round($saleUnit, 3),
                'discounted_rate' => round($saleUnit, 3),
                'discount_amount' => round($discountAmount, 3),
                'taxable' => round($taxableAmount, 3),
                'sgst' => round($sgst, 3),
                'cgst' => round($cgst, 3),
                'igst' => round($igst, 3),
                'tax' => round($lineTax, 3),
                'gst' => round($lineTax, 3),
                'gst_amount' => round($lineTax, 3),
                'sgst_percent' => round($sgstPercent, 3),
                'cgst_percent' => round($cgstPercent, 3),
                'igst_percent' => round($igstPercent, 3),
                'gst_percent' => round($gstPercent, 3),
                'gross' => round($gross, 3),
                'gross_amount' => round($gross, 3),
                'coupon_discount' => round($lineCoupon, 3),
                'shipping_cost' => round($lineShipping, 3),
                'product_final_amount' => round($productFinalAmount, 3),
                'final_amount' => round($finalAmount, 3),
                'line_total' => round($finalAmount, 3),
            ];
        })->all();

        $shippingSummary = $this->summarizeInclusiveShipping($lines);
        $shippingBase = (float) $shippingSummary['base_amount'];
        $shippingGst = (float) $shippingSummary['gst_amount'];
        $shippingGross = (float) $shippingSummary['total_amount'];
        $shippingGstPercent = $shippingBase > 0 ? ($shippingGst / $shippingBase) * 100 : 0.0;
        if ($isMaharashtra) {
            $shippingSgst = $shippingGst / 2;
            $shippingCgst = $shippingGst / 2;
            $shippingIgst = 0.0;
            $shippingSgstPercent = $shippingGstPercent / 2;
            $shippingCgstPercent = $shippingGstPercent / 2;
            $shippingIgstPercent = 0.0;
        } else {
            $shippingSgst = 0.0;
            $shippingCgst = 0.0;
            $shippingIgst = $shippingGst;
            $shippingSgstPercent = 0.0;
            $shippingCgstPercent = 0.0;
            $shippingIgstPercent = $shippingGstPercent;
        }

        $sgstTotal += $shippingSgst;
        $cgstTotal += $shippingCgst;
        $igstTotal += $shippingIgst;
        $taxTotal += $shippingGst;
        $taxableTotal += $shippingBase;

        $packForwarding = $request ? (float) $request->input('pack_forwarding_amount', 0) : 0.0;
        $insuranceCharges = $request ? (float) $request->input('insurance_amount', 0) : 0.0;
        $otherCharges = $request ? (float) $request->input('other_charges_amount', 0) : 0.0;
        $amountwiseDiscount = $request ? (float) $request->input('amountwise_discount', 0) : 0.0;
        $crDbAdjustment = $request ? (float) $request->input('cr_db_adjustment', 0) : 0.0;

        $printedTaxable = round(
            $taxableTotal + $packForwarding + $insuranceCharges + $otherCharges - $amountwiseDiscount - $couponDiscount,
            3
        );
        $grandTotal = round($printedTaxable + $taxTotal + $crDbAdjustment, 3);
        $youSave = max(0, $mrpValueTotal - $productGrossTotal);
        $averageDiscount = $mrpValueTotal > 0 ? ($youSave / $mrpValueTotal) * 100 : 0.0;
        $schemePercent = $paidQtyTotal > 0 ? ($schemeQtyTotal / $paidQtyTotal) * 100 : 0.0;

        return [
            'lines' => $summaryLines,
            'is_maharashtra' => $isMaharashtra,
            'subtotal' => round($productSubtotal, 3),
            'net_product_subtotal' => round($subtotal, 3),
            'tax' => round($taxTotal, 3),
            'product_tax' => round($tax, 3),
            'shipping_tax' => round($shippingGst, 3),
            'shipping' => round($shippingBase, 3),
            'shipping_inclusive' => round($shippingGross, 3),
            'shipping_invoice_row' => [
                'base_amount' => round($shippingBase, 3),
                'sgst' => round($shippingSgst, 3),
                'cgst' => round($shippingCgst, 3),
                'igst' => round($shippingIgst, 3),
                'gst' => round($shippingGst, 3),
                'gross' => round($shippingGross, 3),
                'sgst_percent' => round($shippingSgstPercent, 3),
                'cgst_percent' => round($shippingCgstPercent, 3),
                'igst_percent' => round($shippingIgstPercent, 3),
                'gst_percent' => round($shippingGstPercent, 3),
            ],
            'shipping_lines' => $shippingSummary['lines'],
            'product_discount' => round($productwiseDiscount + $batchwiseDiscount, 3),
            'productwise_discount' => round($productwiseDiscount, 3),
            'batchwise_discount' => round($batchwiseDiscount, 3),
            'amountwise_discount' => round($amountwiseDiscount, 3),
            'coupon_discount' => round($couponDiscount, 3),
            'pack_forwarding' => round($packForwarding, 3),
            'insurance_charges' => round($insuranceCharges, 3),
            'other_charges' => round($otherCharges, 3),
            'cr_db_adjustment' => round($crDbAdjustment, 3),
            'mrp_value_total' => round($mrpValueTotal, 3),
            'you_save' => round($youSave, 3),
            'average_discount' => round($averageDiscount, 3),
            'scheme_discount_value' => round($schemeDiscountValue, 3),
            'scheme_percent' => round($schemePercent, 3),
            'sgst_total' => round($sgstTotal, 3),
            'cgst_total' => round($cgstTotal, 3),
            'igst_total' => round($igstTotal, 3),
            'table_taxable_total' => round($taxableTotal, 3),
            'table_tax_total' => round($taxTotal, 3),
            'table_gross_total' => round($taxableTotal + $taxTotal, 3),
            'taxable_value' => $printedTaxable,
            'scheme_quantity' => (int) array_sum($schemeQtyByGroup) + $manualSchemeQuantity,
            'grand_total' => $grandTotal,
            'total' => $grandTotal,
        ];
    }

    protected function summarizeInclusiveShipping(Collection $lines): array
    {
        $shippingLines = $lines
            ->groupBy(fn ($line) => (int) ($line['owner_id'] ?? 0))
            ->map(function (Collection $sellerLines, $sellerId) {
                $shippingInclusive = round((float) $sellerLines->sum(
                    fn ($line) => (float) ($line['shipping_cost'] ?? 0)
                ), 3);

                if ($shippingInclusive <= 0) {
                    return null;
                }

                // shipping_invoice_line() uses the GST rate that occurs on the
                // highest number of paid product lines, matching invoice logic.
                $productLines = $sellerLines->map(function ($line) {
                    $quantity = max(0, (int) ($line['quantity'] ?? 0));

                    return [
                        'is_scheme' => (bool) ($line['is_scheme'] ?? false),
                        'price' => round((float) ($line['sale_price'] ?? 0) * $quantity, 3),
                        'tax' => round((float) ($line['tax'] ?? 0) * $quantity, 3),
                    ];
                });

                $shippingLine = shipping_invoice_line(
                    $productLines,
                    $shippingInclusive,
                    '',
                    translate('Shipping'),
                    3
                );

                if (!$shippingLine) {
                    return null;
                }

                return array_merge($shippingLine, [
                    'seller_id' => (int) $sellerId,
                ]);
            })
            ->filter()
            ->values();

        return [
            'base_amount' => round((float) $shippingLines->sum('base_amount'), 3),
            'gst_amount' => round((float) $shippingLines->sum('gst_amount'), 3),
            'total_amount' => round((float) $shippingLines->sum('total_amount'), 3),
            'lines' => $shippingLines->all(),
        ];
    }

    protected function prepareSchemeAllocations(Collection $sellerProduct, string $stockEffect = 'minus'): array
    {
        $requiresStockCheck = $stockEffect === 'minus';
        $schemeAllocationsByGroup = [];
        $reservableItems = $sellerProduct->filter(function ($item) {
            return !(bool) ($item['is_scheme'] ?? false)
                || (bool) ($item['is_manual_scheme'] ?? false);
        });

        foreach ($reservableItems->groupBy(function ($item) {
            return (int) $item['product_id'] . '|' . (string) ($item['variation'] ?? '');
        }) as $groupKey => $groupItems) {
            $firstItem = $groupItems->first();
            $paidGroupItems = $groupItems->filter(fn ($item) => !(bool) ($item['is_scheme'] ?? false));
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

            if ($requiresStockCheck && $unbatchedQty > (int) $groupStock->qty) {
                $this->fail('items', translate('The requested quantity is not available for ') . $groupProduct->getTranslation('name'));
            }

            if ($requiresStockCheck) {
                foreach ($reservations as $reservedBatchId => $reservedQty) {
                    $reservedBatch = $groupStock->batches()->where('id', $reservedBatchId)->first();
                    if (!$reservedBatch || (int) $reservedBatch->qty < (int) $reservedQty) {
                        $this->fail('items', translate('The requested quantity is not available for ') . $groupProduct->getTranslation('name'));
                    }
                }
            }

            $minQty = $groupStock->min_qty ?? $groupProduct->min_qty ?? 1;
            $schemeQty = calculate_scheme_qty($paidGroupItems->sum('quantity'), $minQty, (int) ($groupStock->scheme ?? 0));
            $schemePreview = allocate_scheme_free_batches($groupStock, $schemeQty, $requiresStockCheck ? $reservations : []);
            if ($requiresStockCheck && !$schemePreview['success']) {
                $this->fail('items', translate('The requested scheme quantity is not available for ') . $groupProduct->getTranslation('name'));
            }
            $allocations = $schemePreview['allocations'];
            if (!$requiresStockCheck && $schemeQty > 0 && empty($allocations)) {
                $fallbackBatch = valid_batches_for_stock($groupStock, true)->first();
                $allocations = [[
                    'batch' => $fallbackBatch,
                    'batch_id' => $fallbackBatch ? (int) $fallbackBatch->id : ($paidGroupItems->first()['batch_id'] ?? null),
                    'quantity' => $schemeQty,
                ]];
            }

            $schemeAllocationsByGroup[$groupKey] = [
                'scheme_qty' => $schemeQty,
                'allocations' => $allocations,
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
        $couponResult = coupon_cart_discount_allocations(
            $coupon,
            $ownerLines,
            json_decode($coupon->details),
            $userCoupon,
            true
        );

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

    protected function applyBackendAdditionalDiscount(Collection $lines, $rawValue, string $discountType, bool $throwOnInvalid = true): void
    {
        foreach ($lines as $line) {
            $line->discount = 0;
            $line->coupon_code = null;
            $line->coupon_applied = 0;
        }

        if ($rawValue === null || $rawValue === '') {
            return;
        }
        if (!is_numeric($rawValue) || (float) $rawValue < 0) {
            if ($throwOnInvalid) {
                $this->fail('additional_discount', translate('Discount must be zero or greater.'));
            }
            return;
        }
        if (!in_array($discountType, ['percent', 'amount'], true)) {
            if ($throwOnInvalid) {
                $this->fail('additional_discount_type', translate('Please select a valid discount type.'));
            }
            return;
        }

        $discountValue = (float) $rawValue;
        if ($discountType === 'percent' && $discountValue > 100) {
            if ($throwOnInvalid) {
                $this->fail('additional_discount', translate('Percentage discount cannot exceed 100%.'));
            }
            return;
        }
        if ($discountValue <= 0) {
            if ($throwOnInvalid) {
                $this->fail('additional_discount', translate('Discount must be greater than zero.'));
            }
            return;
        }

        $paidLines = $lines->filter(fn ($line) => !(bool) ($line['is_scheme'] ?? false))->values();
        $productSubtotal = (float) $paidLines->sum(fn ($line) => cart_coupon_line_value($line));
        if ($productSubtotal <= 0) {
            return;
        }

        $discountAmount = $discountType === 'percent'
            ? ($productSubtotal * $discountValue) / 100
            : $discountValue;
        $discountAmount = round(min($productSubtotal, max(0, $discountAmount)), 3);
        $allocations = allocate_coupon_discount_by_line_value($paidLines, $discountAmount, 3);

        foreach ($paidLines as $line) {
            $lineDiscount = round((float) ($allocations[(int) $line->id] ?? 0), 3);
            if ($lineDiscount <= 0) {
                continue;
            }
            $line->discount = $lineDiscount;
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
        $shippingCostTaxModes = (array) $request->input('shipping_costs_tax_inclusive', []);
        $shippingItemsBySeller = [];
        $sellerIds = $lines
            ->map(fn ($line) => (int) ($line['owner_id'] ?? 0))
            ->filter()
            ->unique()
            ->values();

        if ($request->input('shipping_cost_type') === 'by_seller') {
            foreach ($sellerIds as $sellerId) {
                if (!array_key_exists($sellerId, $shippingCosts)) {
                    $this->fail('shipping_costs', translate('Please enter the sell amount for every seller.'));
                }
            }
        }

        foreach ((array) $request->input('shipping_items', []) as $shippingItem) {
            $amount = max(0, (float) ($shippingItem['amount'] ?? 0));
            if ($amount <= 0) {
                continue;
            }

            $sellerId = (int) ($shippingItem['seller_id'] ?? 0);
            if (!$lines->contains(fn ($line) => (int) ($line['owner_id'] ?? 0) === $sellerId)) {
                $this->fail('shipping_items', translate('A shipping item is assigned to an invalid seller.'));
            }

            $shippingItemsBySeller[$sellerId][] = [
                'amount' => $amount,
                // Courier APIs return a final charge inclusive of all GST.
                // Manual rows use the explicit checkbox selected by the user.
                'tax_inclusive' => ($shippingItem['source'] ?? null) === 'courier'
                    || filter_var($shippingItem['tax_inclusive'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        if ($request->input('shipping_cost_type') === 'free_shipping'
            || ($request->input('shipping_cost_type') === null && $request->boolean('free_shipping'))) {
            return;
        }
        $globalShipping = (float) $request->input('shipping_cost', 0);
        $groups = $lines->groupBy(fn ($line) => (int) ($line['owner_id'] ?? 0));

        foreach ($groups as $sellerId => $sellerLines) {
            $exclusiveCost = 0.0;
            $inclusiveCost = 0.0;

            if (array_key_exists($sellerId, $shippingCosts)) {
                $sellerCost = max(0, (float) $shippingCosts[$sellerId]);
                $sellerCostIsInclusive = filter_var(
                    $shippingCostTaxModes[$sellerId] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );
                if ($sellerCostIsInclusive) {
                    $inclusiveCost += $sellerCost;
                } else {
                    $exclusiveCost += $sellerCost;
                }
            } elseif ($groups->count() === 1) {
                // Preserve the legacy global shipping field as GST-exclusive.
                $exclusiveCost += max(0, $globalShipping);
            }

            foreach ($shippingItemsBySeller[$sellerId] ?? [] as $shippingItem) {
                if ($shippingItem['tax_inclusive']) {
                    $inclusiveCost += $shippingItem['amount'];
                } else {
                    $exclusiveCost += $shippingItem['amount'];
                }
            }

            $cost = round(
                $inclusiveCost + $this->shippingInclusiveFromBase($sellerLines, $exclusiveCost),
                3
            );

            $firstChargeableLine = $sellerLines->first(fn ($line) => !(bool) ($line['is_scheme'] ?? false)
                || (bool) ($line['is_manual_scheme'] ?? false));
            if ($firstChargeableLine) {
                $firstChargeableLine->shipping_cost = $cost;
            }
        }
    }

    /**
     * Convert a GST-exclusive shipping amount to its inclusive value. The GST
     * rate is selected automatically from the seller's paid product lines.
     */
    protected function shippingInclusiveFromBase(Collection $sellerLines, float $baseAmount): float
    {
        if ($baseAmount <= 0) {
            return 0.0;
        }

        $productLines = $sellerLines->map(function ($line) {
            $quantity = max(0, (int) ($line['quantity'] ?? 0));

            return [
                'is_scheme' => (bool) ($line['is_scheme'] ?? false),
                'price' => round((float) ($line['sale_price'] ?? 0) * $quantity, 3),
                'tax' => round((float) ($line['tax'] ?? 0) * $quantity, 3),
            ];
        });

        // The helper resolves the applicable GST rate from the product lines.
        // The probe amount does not affect the selected rate.
        $rateProbe = shipping_invoice_line($productLines, 100, '', translate('Shipping'), 3);
        $gstPercent = (float) ($rateProbe['gst_percent'] ?? 0);

        return round($baseAmount + (($baseAmount * $gstPercent) / 100), 3);
    }

    protected function backendShippingAddressId(Request $request)
    {
        return $request->boolean('shipping_same_as_billing')
            ? $request->input('billing_address_id')
            : $request->input('shipping_address_id');
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
            'contact_person' => $this->capitalizeFirst($request->input($prefix . '_contact_person')),
            'address' => $this->capitalizeFirst($request->input($prefix . '_address')),
            'village' => $this->capitalizeFirst($request->input($prefix . '_village')),
            'district' => $this->capitalizeFirst($request->input($prefix . '_district')),
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
            'contact_person' => $address->contact_person,
            'email' => $customer->email,
            'address' => $address->address,
            'village' => $address->village,
            'district' => $address->district,
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
        $query = $product->stocks()->with(['batches'])->where('is_hidden', 0);

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
        $query = $product->stocks()->with(['batches'])->where('is_hidden', 0);
        $hasStockIdentity = false;

        if (!empty($line['stock_id'])) {
            $hasStockIdentity = true;
            $stock = (clone $query)->where('id', $line['stock_id'])->first();
            if ($stock) {
                return $stock;
            }
        }

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

    protected function prepareStockForPricing(?ProductStock $stock, ?ProductBatch $batch = null): void
    {
        if (!$stock) {
            return;
        }

        if (!Schema::hasTable('wholesale_prices')) {
            $stock->setRelation('wholesalePrices', collect());
        }

        if ($batch) {
            $batch->setRelation('stock', $stock);
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
            'name' => $this->capitalizeFirst($name),
            'mode' => $request->input('fod_mode', 'surface'),
            'status' => 'inactive',
            'created_by' => Auth::id(),
        ]);
    }

    protected function resolveBookedTo(Request $request, ?Transport $transport): ?BookedTo
    {
        return $this->resolveBookedLocation($request, $transport, 'booked_to_id', 'booked_to_name');
    }

    protected function resolveBookedLocation(Request $request, ?Transport $transport, string $idKey, string $nameKey): ?BookedTo
    {
        if (!$transport) {
            return null;
        }

        $id = (int) $request->input($idKey);
        if ($id > 0) {
            $location = BookedTo::where('transport_id', $transport->id)->where('id', $id)->first();
            if ($location) {
                return $location;
            }
        }

        $name = trim((string) $request->input($nameKey));
        if ($name === '') {
            return null;
        }

        $location = BookedTo::where('transport_id', $transport->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();
        if ($location) {
            return $location;
        }

        return BookedTo::create([
            'transport_id' => $transport->id,
            'name' => $this->capitalizeFirst($name),
            'status' => 'inactive',
            'created_by' => Auth::id(),
        ]);
    }

    protected function validatePortLogistics(Request $request): void
    {
        $loadingType = $request->input('loading_location_type');
        if (!in_array($loadingType, ['sea', 'air'], true)) {
            $this->fail('loading_location_type', translate('Please select a sea port or airport of loading.'));
        }
        if ($loadingType === 'sea' && !SeaPort::where('status', 1)->whereKey($request->input('loading_sea_port_id'))->exists()) {
            $this->fail('loading_sea_port_id', translate('Please select an active sea port of loading.'));
        }
        if ($loadingType === 'air' && !Airport::where('status', 1)->whereKey($request->input('loading_airport_id'))->exists()) {
            $this->fail('loading_airport_id', translate('Please select an active airport of loading.'));
        }

        $dischargeType = $request->input('discharge_location_type');
        if (!in_array($dischargeType, ['sea', 'air'], true)) {
            $this->fail('discharge_location_type', translate('Please select a sea port or airport of discharge.'));
        }
        if ($dischargeType === 'sea' && !SeaPort::where('status', 1)->whereKey($request->input('discharge_sea_port_id'))->exists()) {
            $this->fail('discharge_sea_port_id', translate('Please select an active sea port of discharge.'));
        }
        if ($dischargeType === 'air' && !Airport::where('status', 1)->whereKey($request->input('discharge_airport_id'))->exists()) {
            $this->fail('discharge_airport_id', translate('Please select an active airport of discharge.'));
        }
    }

    protected function transportAndBookedToAreSame(Request $request, ?Transport $transport): bool
    {
        if (!$transport) {
            return false;
        }

        $bookedToName = trim((string) $request->input('booked_to_name'));
        if ($request->filled('booked_to_id')) {
            $bookedToName = (string) optional(BookedTo::find($request->input('booked_to_id')))->name;
        }

        return $bookedToName !== ''
            && mb_strtolower(trim($bookedToName), 'UTF-8') === mb_strtolower(trim((string) $transport->name), 'UTF-8');
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
            'name' => $this->capitalizeFirst($name),
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

    protected function assignInvoiceAdjustments(Order $order, Request $request): void
    {
        $table = $order->getTable();
        $textColumns = ['pm_accountant_name', 'eway_bill_number', 'eway_bill_date'];
        $columns = [
            'pm_accountant_name',
            'eway_bill_number',
            'eway_bill_date',
            'pack_forwarding_amount',
            'insurance_amount',
            'other_charges_amount',
            'amountwise_discount',
            'cr_db_adjustment',
        ];
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                continue;
            }
            $value = $request->input($column);
            if (in_array($column, $textColumns, true)) {
                $order->{$column} = ($value === null || $value === '') ? null : $value;
                continue;
            }
            $order->{$column} = ($value === null || $value === '') ? 0 : $value;
        }
    }
}
