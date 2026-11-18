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
            ? Cart::with('product.stocks')->where('user_id', auth()->id())->get()
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
                'price' => (float) $detail->price,
            ];
        }

        return $items;
    }

    protected function isCodOrder(Order $order): bool
    {
        $type = str_replace(['_', ' '], '-', strtolower(trim((string) $order->payment_type)));

        return in_array($type, ['cash-on-delivery', 'cod'], true);
    }
}
