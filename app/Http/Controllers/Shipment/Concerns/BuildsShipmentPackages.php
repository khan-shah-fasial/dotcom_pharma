<?php

namespace App\Http\Controllers\Shipment\Concerns;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Support\ShipmentPackage;
use Illuminate\Http\Request;

trait BuildsShipmentPackages
{
    protected function buildPackageFromOrder(Order $order): array
    {
        // Box weight/dimensions entered on the order win over item estimates.
        $orderWeight = (float) ($order->weight_kg ?? 0) ?: ((float) ($order->weight_grams ?? 0) / 1000);
        $orderLength = (float) ($order->length_cm ?? 0);
        $orderWidth = (float) ($order->width_cm ?? 0);
        $orderHeight = (float) ($order->height_cm ?? 0);

        if ($orderWeight > 0 && $orderLength > 0 && $orderWidth > 0 && $orderHeight > 0) {
            return ShipmentPackage::format($orderWeight, $orderLength, $orderWidth, $orderHeight);
        }

        $order->loadMissing('orderDetails.product.stocks.dimensionSheet');

        $lines = [];
        foreach ($order->orderDetails as $detail) {
            $product = $detail->product;
            $qty = (int) $detail->quantity;

            if (!$product || $qty < 1) {
                continue;
            }

            $stock = !empty($detail->variation)
                ? $product->stocks->where('variant', $detail->variation)->first()
                : null;

            $lines[] = ShipmentPackage::line($stock, $product, $qty);
        }

        $package = ShipmentPackage::fromLines($lines);

        return $orderWeight > 0
            ? ShipmentPackage::format($orderWeight, (float) $package['box_length'], (float) $package['box_breadth'], (float) $package['box_height'])
            : $package;
    }

    protected function buildPackageFromCart(): array
    {
        $carts = auth()->check()
            ? Cart::with('product.stocks.dimensionSheet')->where('user_id', auth()->id())->get()
            : collect(session('cart', []));

        $lines = [];
        foreach ($carts as $item) {
            $product = $item->product ?? null;
            $qty = (int) ($item->quantity ?? $item->qty ?? 1);

            if (!$product || $qty < 1) {
                continue;
            }

            $variant = $item->variation ?? $item->variant ?? null;
            $stock = $variant ? $product->stocks->where('variant', $variant)->first() : null;

            $lines[] = ShipmentPackage::line($stock, $product, $qty);
        }

        return ShipmentPackage::fromLines($lines);
    }

    protected function formatPackage(float $weight, float $length, float $width, float $height): array
    {
        return ShipmentPackage::format($weight, $length, $width, $height);
    }

    protected function resolveRateContext($orderOrRequest): array
    {
        if ($orderOrRequest instanceof Request) {
            $addressId = $orderOrRequest->input('address_id');
            $address = $addressId ? Address::find($addressId) : null;

            return [
                'to_pincode' => $orderOrRequest->input('to_pincode') ?: ($address->postal_code ?? $address->zip ?? null),
                'payment_type' => $orderOrRequest->input('payment_type', 'prepaid'),
                'package' => is_array($orderOrRequest->input('package'))
                    ? $orderOrRequest->input('package')
                    : $this->buildPackageFromCart(),
                'address' => $address ? $address->toArray() : [],
            ];
        }

        $address = json_decode($orderOrRequest->shipping_address ?? '{}', true) ?: [];

        return [
            'to_pincode' => $address['postal_code'] ?? $address['zipcode'] ?? null,
            'payment_type' => $this->isCodOrder($orderOrRequest) ? 'cod' : 'prepaid',
            'package' => $this->buildPackageFromOrder($orderOrRequest),
            'address' => $address,
        ];
    }

    protected function splitAddress(?string $address): array
    {
        $address = trim((string) $address);

        return [
            mb_substr($address, 0, 30),
            mb_substr($address, 30, 30),
            mb_substr($address, 60, 30),
        ];
    }

    protected function orderItems(Order $order): array
    {
        $items = [];

        foreach ($order->orderDetails as $detail) {
            $product = $detail->product;
            $items[] = [
                'name' => $product ? $product->getTranslation('name') : 'Product',
                'sku' => (string) ($product->sku ?? $detail->product_id),
                'quantity' => (int) $detail->quantity,
                // order_details.price is the line total (unit price x quantity)
                'price' => (int) $detail->quantity > 0 ? round((float) $detail->price / (int) $detail->quantity, 3) : (float) $detail->price,
                'line_total' => (float) $detail->price,
                'hsn' => $this->productHsn($product),
            ];
        }

        return $items;
    }

    /**
     * The product's HSN as chosen from the HSN master (digits only), or ''.
     */
    protected function productHsn($product): string
    {
        $hsn = preg_replace('/\D+/', '', (string) ($product->product_hsn ?? ''));

        return strlen($hsn) >= 4 ? substr($hsn, 0, 8) : '';
    }

    /**
     * HSN for couriers that take one code per shipment: the HSN of the
     * highest-value line, else ''.
     */
    protected function primaryHsn(Order $order): string
    {
        return (string) collect($this->orderItems($order))
            ->filter(fn ($item) => $item['hsn'] !== '')
            ->sortByDesc(fn ($item) => $item['line_total'])
            ->pluck('hsn')
            ->first();
    }

    /**
     * ISO 3166 alpha-2 code from a code, a countries.id or a country name ('' if unknown).
     */
    protected function countryIso($value): string
    {
        static $byName = null;
        static $byId = null;

        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^[A-Za-z]{2}$/', $value)) {
            return strtoupper($value);
        }

        if ($byName === null) {
            try {
                $rows = \Illuminate\Support\Facades\DB::table('countries')->get(['id', 'code', 'name']);
            } catch (\Throwable $e) {
                $rows = collect();
            }
            $byName = $rows->mapWithKeys(fn ($c) => [strtolower(trim($c->name)) => strtoupper((string) $c->code)])->all();
            $byId = $rows->mapWithKeys(fn ($c) => [(string) $c->id => strtoupper((string) $c->code)])->all();
        }

        if (ctype_digit($value)) {
            return $byId[$value] ?? '';
        }

        return $byName[strtolower($value)] ?? (strtolower($value) === 'india' ? 'IN' : '');
    }

    /**
     * Goods value for declared value / COD fee: order total, or the cart at checkout.
     */
    protected function orderValue($orderOrRequest): float
    {
        if ($orderOrRequest instanceof Order) {
            return (float) $orderOrRequest->grand_total;
        }

        try {
            $carts = auth()->check()
                ? Cart::where('user_id', auth()->id())->get()
                : Cart::where('temp_user_id', session('temp_user_id'))->get();

            return (float) $carts->sum(fn ($cart) => (float) $cart->price * (int) $cart->quantity);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    protected function isCodOrder(Order $order): bool
    {
        $type = str_replace(['_', ' '], '-', strtolower(trim((string) $order->payment_type)));

        return in_array($type, ['cash-on-delivery', 'cod'], true);
    }
}
