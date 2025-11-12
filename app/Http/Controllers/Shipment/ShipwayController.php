<?php

namespace App\Http\Controllers\Shipment;

use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\Address;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Exception;
use App\Models\Cart;

class ShipwayController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | HTTP helpers
    |--------------------------------------------------------------------------
    */
    protected function authHeader(): string
    {
        $useremail = env('SHIPWAY_USEREMAIL');
        $secret    = env('SHIPWAY_SECRET_KEY');
        $token     = base64_encode($useremail . ':' . $secret);

        Log::debug('[Shipway][Auth] Generated Basic Auth header', [
            'useremail' => $useremail,
            'secret_present' => !empty($secret),
        ]);

        return 'Basic ' . $token;
    }

    protected function httpBase()
    {
        return Http::withHeaders([
            'Authorization' => $this->authHeader(),
            'Content-Type'  => 'application/json',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Warehouse
    |--------------------------------------------------------------------------
    | Call Shipway, return the default warehouse (or first one).
    */
    protected function getDefaultWarehouse(): ?array
    {
        $res = $this->httpBase()->get('https://app.shipway.com/api/getwarehouses');
        $json = $res->json();

        if (!($json['success'] ?? 0)) {
            return null;
        }

        // message is an object keyed by warehouse_id
        $warehouses = $json['message'] ?? [];
        if (empty($warehouses)) {
            return null;
        }

        // find default
        foreach ($warehouses as $wh) {
            if (($wh['default'] ?? '0') == '1') {
                return $wh;
            }
        }

        // else just return first
        return reset($warehouses);
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Build package from order
    |--------------------------------------------------------------------------
    | You said: "multiply the information with purchase qty"
    | so we sum L/W/H/Weight per line * qty.
    */
    protected function buildPackageFromOrder(Order $order): array
    {
        Log::info('[Shipway][BuildPackage] Start building package metrics', ['order_id' => $order->id]);

        $totalWeight = 0.0;
        $totalLength = $totalWidth = $totalHeight = 0.0;
        $boxL = $boxW = $boxH = 0.0;

        $count = $order->orderDetails->count();
        Log::debug('[Shipway][BuildPackage] Found order details', ['count' => $count]);

        foreach ($order->orderDetails as $detail) {
            $product = $detail->product;
            $qty = (int) $detail->quantity;

            // Get dimensions (priority: variant → product → default)
            $stock = (!empty($detail->variation) && $product)
                ? $product->stocks->where('variant', $detail->variation)->first()
                : null;

            $length = (float) ($stock->length ?? $product->length ?? 10);
            $width  = (float) ($stock->width  ?? $product->width  ?? 10);
            $height = (float) ($stock->height ?? $product->height ?? 10);
            $weight = (float) ($stock->weight ?? $product->weight ?? 0.21);

            $totalLength += $length * $qty;
            $totalWidth += $width * $qty;
            $totalHeight += $height * $qty;
            $totalWeight += $weight * $qty;
        }

        if ($totalWeight <= 0) {
            Log::warning('[Shipway][BuildPackage] total physical weight <= 0, defaulting to 0.21kg');
            $totalWeight = 0.21;
        }

        // ensure fallback box dimensions
        $boxL = $totalLength > 0 ? $totalLength : 10;
        $boxW = $totalWidth > 0 ? $totalWidth : 10;
        $boxH = $totalHeight > 0 ? $totalHeight : 10;

        // compute volumetric and chargeable weight
        $volW = round(($boxL * $boxW * $boxH) / 5000.0, 2);
        $charged = $totalWeight;
        // $charged = (float) round(max($totalWeight, $volW), 2);
        
        $package = [
            'order_id'              => $order->id,
            'sum_length'            => number_format($totalLength, 2, '.', ''),
            'sum_width'             => number_format($totalWidth, 2, '.', ''),
            'sum_height'            => number_format($totalHeight, 2, '.', ''),
            'total_physical_weight' => number_format($totalWeight, 2, '.', ''),
            'box_length'            => number_format($boxL, 2, '.', ''),
            'box_breadth'           => number_format($boxW, 2, '.', ''),
            'box_height'            => number_format($boxH, 2, '.', ''),
            'volumetric_weight'     => number_format($volW, 2, '.', ''),
            'charged_weight'        => number_format($charged, 2, '.', ''),
        ];

        Log::info('[Shipway][BuildPackage] Final package computed', $package);

        return $package;
    }

    
    /*
    |--------------------------------------------------------------------------
    | 3. Carrier rates
    |--------------------------------------------------------------------------
    | Later you will show them to the user. For now we just pick first.
    */
    protected function getCarrierRates(
        string $fromPincode,
        string $toPincode,
        string $paymentType,
        array $pkg
    ): array {
        // ---- Normalize/cast inputs
        $weightKg = (float) ($pkg['charged_weight'] ?? $pkg['total_physical_weight'] ?? 0.21);
        $length   = (float) ($pkg['box_length']   ?? 10);
        $breadth  = (float) ($pkg['box_breadth']  ?? 10);
        $height   = (float) ($pkg['box_height']   ?? 10);

        $endpoint = 'https://app.shipway.com/api/getshipwaycarrierrates';
        $params = [
            'fromPincode' => trim($fromPincode),
            'toPincode'   => trim($toPincode),
            'paymentType' => $paymentType, // 'prepaid' or 'cod'
            'weight'      => $weightKg,    // KG (float)
            'length'      => $length,
            'breadth'     => $breadth,
            'height'      => $height,
        ];

        // ---- IN: log request context
        \Log::debug('[Shipway][Rates][IN]', [
            'endpoint'    => $endpoint,
            'params'      => $params,
            'pkg'         => $pkg, // original package for audit
        ]);

        $t0 = microtime(true);

        try {
            $res = $this->httpBase()->get($endpoint, $params);
        } catch (\Throwable $e) {
            \Log::error('[Shipway][Rates][EXCEPTION]', [
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
                'params'  => $params,
                'elapsed_ms' => (int) round((microtime(true) - $t0) * 1000),
            ]);
            return [];
        }

        $elapsedMs = (int) round((microtime(true) - $t0) * 1000);
        $status    = $res->status();
        $headers   = $res->headers();          // array<string,array<string>>
        $bodyRaw   = $res->body();
        $stats     = method_exists($res, 'handlerStats') ? $res->handlerStats() : null;

        // ---- Parse JSON (may fail if Shipway sends non-JSON on errors)
        $json = null;
        try { $json = $res->json(); } catch (\Throwable $e) { /* ignore */ }

        $ok = false;
        if (is_array($json)) {
            $ok = (($json['success'] ?? null) === 'success')
            || (($json['success'] ?? false) === true)
            || (($json['status']  ?? null) === 'success');
        }

        // ---- OUT: log response summary + safe body snippet
        \Log::debug('[Shipway][Rates][OUT]', [
            'status'      => $status,
            'ok'          => $ok,
            'elapsed_ms'  => $elapsedMs,
            'headers'     => collect($headers)->map(fn($v) => implode(';', (array)$v))->all(),
            'stats'       => $stats,
            'body_snippet'=> mb_substr($bodyRaw ?? '', 0, 1000),
            'json_keys'   => is_array($json) ? array_keys($json) : gettype($json),
        ]);

        if (!$ok || !is_array($json)) {
            \Log::warning('[Shipway][Rates][FAIL]', [
                'reason' => 'not_ok_or_invalid_json',
                'status' => $status,
                'json'   => $json,
            ]);
            return [];
        }

        // ---- Extract rate_card (primary) or message (fallback) and log sample
        $list = $json['rate_card'] ?? ($json['message'] ?? []);
        if (!is_array($list)) $list = [];

        \Log::debug('[Shipway][Rates][LIST]', [
            'count'  => count($list),
            'sample' => array_slice($list, 0, 3),
        ]);

        return $list;
    }


    /*
    |--------------------------------------------------------------------------
    | 4. Create shipment
    |--------------------------------------------------------------------------
    */
    public function create(Order $order, array $extra = [])
    {
        Log::info('[Shipway][Create] Starting shipment creation', [
            'order_id' => $order->id,
            'order_code' => $order->code,
        ]);

        $method = ShippingMethod::where('slug', 'shipway')->first();
        if (!$method) {
            Log::warning('[Shipway][Create] Shipping method not found (slug=shipway)');
        }

        $addr = json_decode($order->shipping_address ?? '{}', true) ?: [];
        Log::debug('[Shipway][Create] Shipping address parsed', $addr);

        // 1) get default warehouse
        $warehouse = $this->getDefaultWarehouse();
        $warehouseId  = $extra['warehouse_id']        ?? ($warehouse['warehouse_id'] ?? null);
        $fromPincode  = $warehouse['pincode']         ?? null;
        $returnWhId   = $extra['return_warehouse_id'] ?? $warehouseId;

        // 2) Build package metrics
        $package = $this->buildPackageFromOrder($order);

        // 3) figure out destination pincode
        $toPincode = $addr['postal_code'] ?? $addr['zipcode'] ?? null;

        // 4) payment type for rate api
        $paymentType = ($order->payment_type == 'cash_on_delivery') ? 'cod' : 'prepaid';

        // 5) get carrier rates
        $selectedCarrierId = $extra['carrier_id'] ?? null;
        if (!$selectedCarrierId && $fromPincode && $toPincode) {
            $rates = $this->getCarrierRates($fromPincode, $toPincode, $paymentType, $package);

            if (!empty($rates)) {
                // for now pick the first one
                $first = $rates[0];
                $selectedCarrierId = $first['carrier_id'] ?? null;
            }
        }

        // Convert weight (kg → grams) and ensure float type
        $orderWeightGrams = isset($package['charged_weight']) ? round($package['charged_weight'] * 1000, 2) : round(($package['total_physical_weight'] ?? 0) * 1000, 2);
        
        // Prepare product list
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
        Log::debug('[Shipway][Create] Product list built', ['count' => count($products)]);

        // Payload assembly
        $payload = [
            "order_id"             => $order->code,
            "carrier_id"           => $selectedCarrierId ?? '',
            "warehouse_id"         => $warehouseId ?? '',
            "return_warehouse_id"  => $returnWhId ?? '',
            "products"             => $products,
            "discount"             => 0,
            "shipping"             => 0,
            "order_total"          => (string) $order->grand_total,
            "gift_card_amt"        => 0,
            "taxes"                => 0,
            "payment_type"         => $order->payment_type == 'cash_on_delivery' ? 'C' : 'P',
            "email"                => $addr['email'] ?? 'customer@example.com',
            "billing_address"      => $addr['address'] ?? '',
            "billing_city"         => $addr['city'] ?? '',
            "billing_state"        => $addr['state'] ?? '',
            "billing_country"      => $addr['country'] ?? 'India',
            "billing_firstname"    => $addr['name'] ?? '',
            "billing_phone"        => $addr['phone'] ?? '',
            "billing_zipcode"      => $addr['postal_code'] ?? '',
            "shipping_address"     => $addr['address'] ?? '',
            "shipping_city"        => $addr['city'] ?? '',
            "shipping_state"       => $addr['state'] ?? '',
            "shipping_country"     => $addr['country'] ?? 'India',
            "shipping_firstname"   => $addr['name'] ?? '',
            "shipping_phone"       => $addr['phone'] ?? '',
            "shipping_zipcode"     => $addr['postal_code'] ?? '',
            "order_weight"          => $orderWeightGrams, // float in grams ✅
            // "order_weight"         => $package['charged_weight'] ?? $package['total_physical_weight'],
            "box_length"           => $package['box_length'] ?? "",
            "box_breadth"          => $package['box_breadth'] ?? "",
            "box_height"           => $package['box_height'] ?? "",
            "order_date"           => now()->format('Y-m-d H:i:s'),
        ];
        Log::debug('[Shipway][Create] Final payload ready', $payload);

        try {
            $response = $this->httpBase()->post('https://app.shipway.com/api/v2orders', $payload);
            $data = $response->json();
            Log::info('[Shipway][Create] API response received', [
                'status' => $response->status(),
                'success' => $data['success'] ?? null,
            ]);
            Log::debug('[Shipway][Create] Full API response', $data);
        } catch (Exception $e) {
            Log::error('[Shipway][Create] API call failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $awb         = $data['awb_response']['AWB'] ?? null;
        // $labelUrl    = $data['awb_response']['shipping_url'] ?? null;
        $carrierId   = $data['awb_response']['carrier_id'] ?? $payload['carrier_id'];
        $trackingUrl = $awb ? "https://track.shipway.com/t/{$awb}" : null;

        Log::info('[Shipway][Create] Extracted response data', [
            'awb' => $awb,
            'carrier_id' => $carrierId,
            'tracking_url' => $trackingUrl,
        ]);

        OrderShipment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'shipping_method_id' => $method?->id,
                'shipping_id'        => $awb,
                'tracking_url'       => $trackingUrl,
                'shipping_type'      => $extra['shipping_type'] ?? null,
                'raw_response'       => json_encode($data),
                'status'             => ($data['success'] ?? false) ? 'created' : 'error',
            ]
        );

        Log::info('[Shipway][Create] Shipment record updated', ['order_id' => $order->id]);

        return $data;
    }

    /**
     * Build package from the current user's cart (or session cart).
     */
    protected function buildPackageFromCart(): array
    {
        // Adjust this to your cart source if different
        $carts = auth()->check() ? Cart::with('product.stocks')->where('user_id', auth()->id())->get() : collect(session('cart', [])); // fallback for guests, adapt if needed

        $totalWeight = 0.0;
        $totalLength = $totalWidth = $totalHeight = 0.0;

        foreach ($carts as $item) {
            $product = $item->product ?? null;
            $qty     = (int) ($item->quantity ?? $item->qty ?? 1);

            if (!$product) continue;

            $stock = null;
            if (!empty($item->variation ?? $item->variant)) {
                $variantKey = $item->variation ?? $item->variant;
                $stock = $product->stocks->where('variant', $variantKey)->first();
            }

            $length = (float) ($stock->length ?? $product->length ?? 10);
            $width  = (float) ($stock->width  ?? $product->width  ?? 10);
            $height = (float) ($stock->height ?? $product->height ?? 10);
            $weight = (float) ($stock->weight ?? $product->weight ?? 0.21);

            $totalLength += $length * $qty;
            $totalWidth  += $width  * $qty;
            $totalHeight += $height * $qty;
            $totalWeight += $weight * $qty;
        }

        if ($totalWeight <= 0) $totalWeight = 0.21;

        $boxL = $totalLength > 0 ? $totalLength : 10;
        $boxW = $totalWidth  > 0 ? $totalWidth  : 10;
        $boxH = $totalHeight > 0 ? $totalHeight : 10;

        $volW   = round(($boxL * $boxW * $boxH) / 5000.0, 2);
        $charge = $totalWeight; // or max($totalWeight, $volW) if you want volumetric charging

        return [
            'total_physical_weight' => number_format($totalWeight, 2, '.', ''),
            'box_length'            => number_format($boxL, 2, '.', ''),
            'box_breadth'           => number_format($boxW, 2, '.', ''),
            'box_height'            => number_format($boxH, 2, '.', ''),
            'volumetric_weight'     => number_format($volW, 2, '.', ''),
            'charged_weight'        => number_format($charge, 2, '.', ''),
        ];
    }

    /**
     * Shipway get rates (works with Request on checkout OR Order later).
     * If Request: expects one of {address_id} or {to_pincode}, optional {payment_type}.
     */
    public function rates($orderOrRequest, array $extra = [])
    {
        // === 1) Resolve destination pincode & payment type ===
        $toPincode   = null;
        $paymentType = 'prepaid';
        
        if ($orderOrRequest instanceof Request) {
            $provider   = $orderOrRequest->input('provider');
            $addressId  = $orderOrRequest->input('address_id');
            $toPincode  = $orderOrRequest->input('to_pincode') ?: null;
            $paymentType = $orderOrRequest->input('payment_type', 'prepaid');

            if (!$toPincode && $addressId) {
                // Pull from Address model
                $addr = Address::find($addressId);
                if ($addr) {
                    $toPincode = $addr->postal_code ?? $addr->zip ?? null;
                }
            }

            if (!$toPincode) {
                return ['success' => false, 'data' => [], 'message' => 'to_pincode or address_id required'];
            }

            // Build package from cart
            $package = $this->buildPackageFromCart();

        } else {
            // If called with Order (later flow) — your previous implementation can live here.
            /** @var \App\Models\Order $order */
            $order = $orderOrRequest;

            $addr = json_decode($order->shipping_address ?? '{}', true) ?: [];
            $toPincode = $addr['postal_code'] ?? $addr['zipcode'] ?? null;
            $paymentType = ($order->payment_type == 'cash_on_delivery') ? 'cod' : 'prepaid';
            $package = $this->buildPackageFromOrder($order);

            if (!$toPincode) {
                return ['success' => false, 'data' => [], 'message' => 'Order missing destination pincode'];
            }
        }

        // === 2) From pincode (warehouse) ===
        $warehouse = $this->getDefaultWarehouse();
        if (!$warehouse || empty($warehouse['pincode'])) {
            return ['success' => false, 'data' => [], 'message' => 'No warehouse pincode'];
        }
        $fromPincode = $warehouse['pincode'];

        // defensive logs
        Log::debug('Rates inputs', [
            'provider' => $provider,
            'fromPincode' => $fromPincode,
            'toPincode' => $toPincode,
            'paymentType' => $paymentType,
            'package' => $package,
        ]);

        // === 3) Call Shipway ===
        $rates = $this->getCarrierRates($fromPincode, $toPincode, $paymentType, $package);

        // === 4) Normalize (name + charges) ===
        $out = [];
        foreach ($rates as $r) {
            $price = null;
            if (isset($r['delivery_charge'])) $price = (float) $r['delivery_charge'];
            elseif (isset($r['total_amount'])) $price = (float) $r['total_amount'];
            elseif (isset($r['rate']))         $price = (float) $r['rate'];

            $out[] = [
                'id'         => 'shipway:' . (string) ($r['carrier_id'] ?? ''),
                'carrier_id' => (int) ($r['carrier_id'] ?? 0),
                'provider' => 'shipway',
                'name'     => (string) ($r['courier_name'] ?? $r['carrier_name'] ?? 'Carrier'),
                'eta'      => null,
                'price'    => $price,
                'meta'     => [
                    'rto_charge'     => $r['rto_charge']     ?? null,
                    'charged_weight'  => $r['charged_weight'] ?? null,
                    'zone'            => $r['zone']           ?? null,
                ],
                'raw'      => $r,
            ];
        }

        return ['success' => true, 'data' => $out];
    }

}
