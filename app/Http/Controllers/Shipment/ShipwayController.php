<?php

namespace App\Http\Controllers\Shipment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\Http;

class ShipwayController extends Controller
{
    protected function authHeader(): string
    {
        $useremail = env('SHIPWAY_USEREMAIL');
        $secret    = env('SHIPWAY_SECRET_KEY');
        $token     = base64_encode($useremail . ':' . $secret);

        return 'Basic ' . $token;
    }

    
    /**
     * Build dimensions & weight from order lines.
     * - if variation: use product_stocks
     * - else: use products
     * - weight: sum(weight * qty)
     * - length/width/height: take max
     */
    protected function buildPackageFromOrder(Order $order): array
    {
        $totalWeight = 0;
        $maxLength   = 0;
        $maxWidth    = 0;
        $maxHeight   = 0;

        foreach ($order->orderDetails as $detail) {
            $product = $detail->product;
            $qty     = (int) $detail->quantity;

            $length = 0;
            $width  = 0;
            $height = 0;
            $weight = 0;

            // variation-based dimensions
            if (!empty($detail->variation) && $product && $product->relationLoaded('stocks') ?? true) {
                $stock = $product->stocks->where('variant', $detail->variation)->first();
                if ($stock) {
                    $length = (float) ($stock->length ?? 0);
                    $width  = (float) ($stock->width ?? 0);
                    $height = (float) ($stock->height ?? 0);
                    $weight = (float) ($stock->weight ?? 0);
                }
            }

            // fallback to product master
            if ($length == 0 && $width == 0 && $height == 0 && $weight == 0 && $product) {
                $length = (float) ($product->length ?? 0);
                $width  = (float) ($product->width ?? 0);
                $height = (float) ($product->height ?? 0);
                $weight = (float) ($product->weight ?? 0);
            }

            // apply quantity
            $lineWeight = $weight * $qty;

            // aggregate
            $totalWeight += $lineWeight;
            $maxLength = max($maxLength, $length);
            $maxWidth  = max($maxWidth,  $width);
            $maxHeight = max($maxHeight, $height);
        }

        // sensible fallbacks
        if ($totalWeight <= 0) $totalWeight = 100;
        if ($maxLength  <= 0) $maxLength  = 10;
        if ($maxWidth   <= 0) $maxWidth   = 10;
        if ($maxHeight  <= 0) $maxHeight  = 10;

        return [
            'order_weight' => (string) $totalWeight,
            'box_length'   => (string) $maxLength,
            'box_breadth'  => (string) $maxWidth,
            'box_height'   => (string) $maxHeight,
        ];
    }

    /**
     * Create shipment on Shipway for a given order
     *
     * @param \App\Models\Order $order
     * @param array $extra  // carrier_id, warehouse_id, etc.
     * @return array|null
     */
    public function create(Order $order, array $extra = [])
    {
        // get shipping method row (so we can save its id later)
        $method = ShippingMethod::where('slug', 'shipway')->first();

        $addr = json_decode($order->shipping_address ?? '{}', true) ?: [];

        // build products from order details
        $products = [];
        foreach ($order->orderDetails as $detail) {
            $products[] = [
                'product'          => $detail->product->getTranslation('name'),
                'price'            => (string) $detail->price,
                'product_code'     => (string) $detail->product_id,
                'product_quantity' => (string) $detail->quantity,
                'discount'         => '0',
                'tax_rate'         => '0',
                'tax_title'        => 'GST',
            ];
        }

        // dynamic package
        $package = $this->buildPackageFromOrder($order);

        $payload = [
            "order_id"             => $order->code,
            "carrier_id"           => $extra['carrier_id'] ?? '',
            "warehouse_id"         => $extra['warehouse_id'] ?? 54211,
            "return_warehouse_id"  => $extra['return_warehouse_id'] ?? 54211,
            "products"             => $products,
            "discount"             => 0,
            "shipping"             => 0,
            // "shipping"             => (string) $order->orderDetails->sum('shipping_cost'),
            "order_total"          => (string) $order->grand_total,
            "gift_card_amt"        => 0,
            "taxes"                => 0,
            "payment_type"         => $order->payment_type == 'cash_on_delivery' ? 'C' : 'P',
            "email"                => $addr['email'] ?? 'customer@example.com',
            "billing_address"      => $addr['address'] ?? '',
            "billing_address2"     => $addr['address2'] ?? '',
            "billing_city"         => $addr['city'] ?? '',
            "billing_state"        => $addr['state'] ?? '',
            "billing_country"      => $addr['country'] ?? 'India',
            "billing_firstname"    => $addr['name'] ?? '',
            "billing_lastname"     => '',
            "billing_phone"        => $addr['phone'] ?? '',
            "billing_zipcode"      => $addr['postal_code'] ?? '',
            "shipping_address"     => $addr['address'] ?? '',
            "shipping_address2"    => $addr['address2'] ?? '',
            "shipping_city"        => $addr['city'] ?? '',
            "shipping_state"       => $addr['state'] ?? '',
            "shipping_country"     => $addr['country'] ?? 'India',
            "shipping_firstname"   => $addr['name'] ?? '',
            "shipping_lastname"    => '',
            "shipping_phone"       => $addr['phone'] ?? '',
            "shipping_zipcode"     => $addr['postal_code'] ?? '',
            "order_weight"         => $package['order_weight'] ?? "",
            "box_length"           => $package['box_length'] ?? "",
            "box_breadth"          => $package['box_breadth'] ?? "",
            "box_height"           => $package['box_height'] ?? "",
            "order_date"           => now()->format('Y-m-d H:i:s'),
        ];

        // NOTE: if your Shipway endpoint is different (typo earlier 'v2orders'), update the URL.
        $response = Http::withHeaders([
            'Authorization' => $this->authHeader(),
            'Content-Type'  => 'application/json',
        ])->post('https://app.shipway.com/api/v2orders', $payload);

        $data = $response->json();

        // extract things we care about (defensive)
        $awb          = $data['awb_response']['AWB'] ?? null;
        $labelUrl     = $data['awb_response']['shipping_url'] ?? null;
        $carrierId    = $data['awb_response']['carrier_id'] ?? $payload['carrier_id'];
        $trackingUrl  = $awb ? "https://track.shipway.com/t/{$awb}" : null;

        // Persist shipment using the fields declared in your OrderShipment model
        OrderShipment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'shipping_method_id' => $method?->id ?? null,
                // 'shipping_id' in your model used to store the AWB / external shipment id
                'shipping_id'        => $awb,
                // your model expects 'tracking_url' (not tracking_number)
                'tracking_url'       => $trackingUrl,
                // keep the shipping_type if provided
                'shipping_type'      => $extra['shipping_type'] ?? null,
                // store the full raw response as JSON string (safe to inspect later)
                'raw_response'       => json_encode($data),
                // set status based on API success boolean (fallback to 'error' if absent/false)
                'status'             => ($data['success'] ?? false) ? 'created' : 'errors',
            ]
        );

        return $data;
    }
}
