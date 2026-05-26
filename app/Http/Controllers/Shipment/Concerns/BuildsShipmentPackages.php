<?php

namespace App\Http\Controllers\Shipment\Concerns;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;

trait BuildsShipmentPackages
{
    protected function buildPackageFromOrder(Order $order): array
    {
        $totalWeight = 0.0;
        $totalLength = 0.0;
        $totalWidth = 0.0;
        $totalHeight = 0.0;

        foreach ($order->orderDetails as $detail) {
            $product = $detail->product;
            $qty = (int) $detail->quantity;

            if (!$product || $qty < 1) {
                continue;
            }

            $stock = !empty($detail->variation)
                ? $product->stocks->where('variant', $detail->variation)->first()
                : null;

            $totalLength += (float) ($stock->length ?? $product->length ?? 10) * $qty;
            $totalWidth += (float) ($stock->width ?? $product->width ?? 10) * $qty;
            $totalHeight += (float) ($stock->height ?? $product->height ?? 10) * $qty;
            $totalWeight += (float) ($stock->weight ?? $product->weight ?? 0.21) * $qty;
        }

        return $this->formatPackage($totalWeight, $totalLength, $totalWidth, $totalHeight);
    }

    protected function buildPackageFromCart(): array
    {
        $carts = auth()->check()
            ? Cart::with('product.stocks')->where('user_id', auth()->id())->get()
            : collect(session('cart', []));

        $totalWeight = 0.0;
        $totalLength = 0.0;
        $totalWidth = 0.0;
        $totalHeight = 0.0;

        foreach ($carts as $item) {
            $product = $item->product ?? null;
            $qty = (int) ($item->quantity ?? $item->qty ?? 1);

            if (!$product || $qty < 1) {
                continue;
            }

            $variant = $item->variation ?? $item->variant ?? null;
            $stock = $variant ? $product->stocks->where('variant', $variant)->first() : null;

            $totalLength += (float) ($stock->length ?? $product->length ?? 10) * $qty;
            $totalWidth += (float) ($stock->width ?? $product->width ?? 10) * $qty;
            $totalHeight += (float) ($stock->height ?? $product->height ?? 10) * $qty;
            $totalWeight += (float) ($stock->weight ?? $product->weight ?? 0.21) * $qty;
        }

        return $this->formatPackage($totalWeight, $totalLength, $totalWidth, $totalHeight);
    }

    protected function formatPackage(float $weight, float $length, float $width, float $height): array
    {
        if ($weight <= 0) {
            $weight = 0.21;
        }

        $boxLength = $length > 0 ? $length : 10;
        $boxWidth = $width > 0 ? $width : 10;
        $boxHeight = $height > 0 ? $height : 10;
        $volumetricWeight = round(($boxLength * $boxWidth * $boxHeight) / 5000, 2);

        return [
            'total_physical_weight' => number_format($weight, 2, '.', ''),
            'box_length' => number_format($boxLength, 2, '.', ''),
            'box_breadth' => number_format($boxWidth, 2, '.', ''),
            'box_height' => number_format($boxHeight, 2, '.', ''),
            'volumetric_weight' => number_format($volumetricWeight, 2, '.', ''),
            'charged_weight' => number_format($weight, 2, '.', ''),
        ];
    }

    protected function resolveRateContext($orderOrRequest): array
    {
        if ($orderOrRequest instanceof Request) {
            $addressId = $orderOrRequest->input('address_id');
            $address = $addressId ? Address::find($addressId) : null;

            return [
                'to_pincode' => $orderOrRequest->input('to_pincode') ?: ($address->postal_code ?? $address->zip ?? null),
                'payment_type' => $orderOrRequest->input('payment_type', 'prepaid'),
                'package' => $this->buildPackageFromCart(),
                'address' => $address ? $address->toArray() : [],
            ];
        }

        $address = json_decode($orderOrRequest->shipping_address ?? '{}', true) ?: [];

        return [
            'to_pincode' => $address['postal_code'] ?? $address['zipcode'] ?? null,
            'payment_type' => 'prepaid',
            // 'payment_type' => ($orderOrRequest->payment_type == 'cash_on_delivery') ? 'cod' : 'prepaid',
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
}
