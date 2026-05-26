<?php

namespace App\Http\Controllers\Shipment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Shipment\Concerns\BuildsShipmentPackages;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DhlController extends Controller
{
    use BuildsShipmentPackages;

    protected function cfg(string $key, $default = null)
    {
        return config("courier_providers.dhl.{$key}", $default);
    }

    protected function hasCredentials(): bool
    {
        return (bool) (
            $this->cfg('api_key')
            && $this->cfg('api_secret')
            && $this->cfg('account_number')
            && $this->cfg('origin_postal_code')
            && $this->cfg('origin_country')
        );
    }

    protected function endpoint(string $path): string
    {
        return rtrim((string) $this->cfg('api_base_url'), '/') . '/' . ltrim($path, '/');
    }

    protected function http()
    {
        return Http::withBasicAuth($this->cfg('api_key'), $this->cfg('api_secret'))
            ->acceptJson()
            ->asJson();
    }

    public function rates($orderOrRequest, array $extra = [])
    {
        if (!$this->hasCredentials()) {
            return ['success' => false, 'data' => [], 'message' => 'DHL credentials are not configured'];
        }

        $context = $this->resolveRateContext($orderOrRequest);
        if (empty($context['to_pincode'])) {
            return ['success' => false, 'data' => [], 'message' => 'to_pincode or address_id required'];
        }

        $address = $context['address'] ?? [];
        $countryCode = $this->countryCode($address['country'] ?? 'IN');
        $payload = [
            'customerDetails' => [
                'shipperDetails' => [
                    'postalCode' => (string) $this->cfg('origin_postal_code'),
                    'cityName' => (string) $this->cfg('origin_city', ''),
                    'countryCode' => (string) $this->cfg('origin_country', 'IN'),
                ],
                'receiverDetails' => [
                    'postalCode' => (string) $context['to_pincode'],
                    'cityName' => (string) ($address['city'] ?? ''),
                    'countryCode' => $countryCode,
                ],
            ],
            'accounts' => [[
                'typeCode' => 'shipper',
                'number' => (string) $this->cfg('account_number'),
            ]],
            'plannedShippingDateAndTime' => now()->addDay()->format('Y-m-d\TH:i:s \G\M\TP'),
            'unitOfMeasurement' => 'metric',
            'isCustomsDeclarable' => $countryCode !== (string) $this->cfg('origin_country', 'IN'),
            'monetaryAmount' => [[
                'typeCode' => 'declaredValue',
                'value' => 1,
                'currency' => (string) $this->cfg('currency', 'INR'),
            ]],
            'packages' => [[
                'weight' => (float) ($context['package']['charged_weight'] ?? 0.21),
                'dimensions' => [
                    'length' => (float) ($context['package']['box_length'] ?? 10),
                    'width' => (float) ($context['package']['box_breadth'] ?? 10),
                    'height' => (float) ($context['package']['box_height'] ?? 10),
                ],
            ]],
        ];

        try {
            $response = $this->http()->post($this->endpoint('/rates'), $payload);
            $json = $response->json() ?: [];

            if (!$response->successful()) {
                Log::warning('[DHL][Rates] API failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 1000),
                ]);
                return ['success' => false, 'data' => [], 'message' => 'DHL rates unavailable'];
            }
        } catch (\Throwable $e) {
            Log::error('[DHL][Rates] Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'data' => [], 'message' => 'DHL rates unavailable'];
        }

        $products = $json['products'] ?? $json['product'] ?? [];
        if (!is_array($products)) {
            $products = [];
        }

        $rates = [];
        foreach ($products as $index => $product) {
            $productCode = (string) ($product['productCode'] ?? $product['globalProductCode'] ?? $product['localProductCode'] ?? $this->cfg('default_product_code', 'P'));
            $price = $this->extractPrice($product);

            $rates[] = [
                'id' => 'dhl:' . $productCode,
                'carrier_id' => $productCode,
                'provider' => 'dhl',
                'name' => (string) ($product['productName'] ?? $product['localProductName'] ?? $product['name'] ?? 'DHL Express'),
                'eta' => $product['deliveryCapabilities']['estimatedDeliveryDateAndTime']
                    ?? $product['deliveryCapabilities']['estimatedDeliveryDate']
                    ?? null,
                'price' => $price,
                'meta' => [
                    'product_code' => $productCode,
                    'charged_weight' => $context['package']['charged_weight'] ?? null,
                    'currency' => $this->cfg('currency', 'INR'),
                ],
                'raw' => $product,
            ];
        }

        return ['success' => !empty($rates), 'data' => $rates];
    }

    public function create(Order $order, array $extra = [])
    {
        $method = ShippingMethod::where('slug', 'dhl')->first();

        if (!$this->hasCredentials()) {
            $data = ['success' => false, 'message' => 'DHL credentials are not configured'];
            $this->storeShipment($order, $method, null, null, $data, 'error', $extra);
            return $data;
        }

        $addr = json_decode($order->shipping_address ?? '{}', true) ?: [];
        $package = $this->buildPackageFromOrder($order);
        $countryCode = $this->countryCode($addr['country'] ?? 'IN');
        $productCode = (string) ($order->shipping_courier_id ?: $this->cfg('default_product_code', 'P'));
        [$shipperAddress1, $shipperAddress2] = array_pad($this->splitAddress($this->cfg('origin_address')), 2, '');
        [$receiverAddress1, $receiverAddress2] = array_pad($this->splitAddress($addr['address'] ?? ''), 2, '');

        $payload = [
            'plannedShippingDateAndTime' => now()->addDay()->format('Y-m-d\TH:i:s \G\M\TP'),
            'pickup' => ['isRequested' => false],
            'productCode' => $productCode,
            'accounts' => [[
                'typeCode' => 'shipper',
                'number' => (string) $this->cfg('account_number'),
            ]],
            'customerDetails' => [
                'shipperDetails' => [
                    'postalAddress' => [
                        'postalCode' => (string) $this->cfg('origin_postal_code'),
                        'cityName' => (string) $this->cfg('origin_city', ''),
                        'countryCode' => (string) $this->cfg('origin_country', 'IN'),
                        'addressLine1' => $shipperAddress1,
                        'addressLine2' => $shipperAddress2,
                    ],
                    'contactInformation' => [
                        'email' => (string) $this->cfg('shipper_email', ''),
                        'phone' => (string) $this->cfg('shipper_phone', ''),
                        'companyName' => (string) $this->cfg('shipper_name', ''),
                        'fullName' => (string) $this->cfg('shipper_name', ''),
                    ],
                ],
                'receiverDetails' => [
                    'postalAddress' => [
                        'postalCode' => (string) ($addr['postal_code'] ?? $addr['zipcode'] ?? ''),
                        'cityName' => (string) ($addr['city'] ?? ''),
                        'countryCode' => $countryCode,
                        'addressLine1' => $receiverAddress1,
                        'addressLine2' => $receiverAddress2,
                    ],
                    'contactInformation' => [
                        'email' => (string) ($addr['email'] ?? ''),
                        'phone' => (string) ($addr['phone'] ?? ''),
                        'companyName' => (string) ($addr['name'] ?? ''),
                        'fullName' => (string) ($addr['name'] ?? ''),
                    ],
                ],
            ],
            'content' => [
                'packages' => [[
                    'weight' => (float) ($package['charged_weight'] ?? 0.21),
                    'dimensions' => [
                        'length' => (float) ($package['box_length'] ?? 10),
                        'width' => (float) ($package['box_breadth'] ?? 10),
                        'height' => (float) ($package['box_height'] ?? 10),
                    ],
                ]],
                'isCustomsDeclarable' => $countryCode !== (string) $this->cfg('origin_country', 'IN'),
                'description' => 'Order ' . $order->code,
                'declaredValue' => (float) $order->grand_total,
                'declaredValueCurrency' => (string) $this->cfg('currency', 'INR'),
            ],
            'getRateEstimates' => false,
        ];

        try {
            $response = $this->http()->post($this->endpoint('/shipments'), $payload);
            $data = $response->json() ?: ['raw' => $response->body()];
        } catch (\Throwable $e) {
            Log::error('[DHL][Create] Exception', ['error' => $e->getMessage()]);
            $data = ['success' => false, 'message' => $e->getMessage()];
        }

        $trackingNumber = $data['shipmentTrackingNumber']
            ?? $data['trackingNumber']
            ?? $data['shipmentIdentificationNumber']
            ?? null;
        $trackingUrl = $data['trackingUrl']
            ?? ($trackingNumber ? 'https://www.dhl.com/in-en/home/tracking/tracking-express.html?submit=1&tracking-id=' . urlencode($trackingNumber) : null);
        $success = !empty($trackingNumber) && empty($data['error']);

        $this->storeShipment($order, $method, $trackingNumber, $trackingUrl, $data, $success ? 'created' : 'error', $extra);

        return $data;
    }

    protected function extractPrice(array $product): ?float
    {
        $price = $product['totalPrice'][0]['price']
            ?? $product['totalPrice'][0]['totalPrice']
            ?? $product['totalPrice'][0]['value']
            ?? $product['totalPrice'][0]['priceBreakdown'][0]['price']
            ?? null;

        return $price === null ? null : (float) $price;
    }

    protected function countryCode(?string $country): string
    {
        $country = strtoupper(trim((string) $country));

        if (strlen($country) === 2) {
            return $country;
        }

        return $country === 'INDIA' || $country === '' ? 'IN' : $country;
    }

    protected function storeShipment(Order $order, ?ShippingMethod $method, ?string $trackingNumber, ?string $trackingUrl, array $data, string $status, array $extra): void
    {
        OrderShipment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'shipping_method_id' => $method?->id,
                'shipping_id' => $trackingNumber,
                'tracking_url' => $trackingUrl,
                'shipping_type' => $extra['shipping_type'] ?? null,
                'raw_response' => json_encode($data),
                'status' => $status,
            ]
        );
    }
}
