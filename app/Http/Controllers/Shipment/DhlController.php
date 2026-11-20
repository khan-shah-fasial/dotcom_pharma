<?php

namespace App\Http\Controllers\Shipment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Shipment\Concerns\BuildsShipmentPackages;
use App\Models\City;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\ShippingMethod;
use App\Services\OrderDeliveryStatusService;
use App\Support\ShipmentPackage;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * DHL Express (MyDHL API) - international shipments only.
 * Inside India, DHL Express delivers through Blue Dart, so domestic addresses are refused.
 *
 * Going live = live keys in .env + DHL_ENV=production.
 *   POST /rates                              rates for the destination country
 *   POST /shipments                          AWB + label + commercial invoice (export declaration)
 *   GET  /shipments/{awb}/tracking           tracking
 */
class DhlController extends Controller
{
    use BuildsShipmentPackages;

    protected const SECRET_KEYS = ['api_base_url', 'api_key', 'api_secret', 'account_number'];

    /**
     * MyDHL registrationNumbers typeCodes for the shipper. Confirm the IEC code with
     * DHL India during onboarding; both can be changed here without touching the payload.
     */
    public const GSTIN_TYPE_CODE = 'GST';
    public const IEC_TYPE_CODE = 'IEC';

    /**
     * Credentials stay in .env; shipper/origin details come from
     * Admin > Setup > Shipping > Courier APIs, falling back to config.
     */
    protected function cfg(string $key, $default = null)
    {
        if (!in_array($key, self::SECRET_KEYS, true) && function_exists('get_setting')) {
            $setting = get_setting('dhl_' . $key);
            if ($setting !== null && $setting !== '') {
                return $setting;
            }
        }

        $value = config("courier_providers.dhl.{$key}");

        return $value === null || $value === '' ? $default : $value;
    }

    public function hasCredentials(): bool
    {
        return (bool) (
            $this->cfg('api_key')
            && $this->cfg('api_secret')
            && $this->cfg('account_number')
            && $this->cfg('origin_postal_code')
        );
    }

    /**
     * Test and production share a host but differ by path, so this returns the full base.
     */
    public function base(): string
    {
        $bases = (array) config('courier_providers.dhl.bases', []);
        $environment = strtolower(trim((string) config('courier_providers.dhl.environment')));

        if (isset($bases[$environment])) {
            return rtrim($bases[$environment], '/');
        }

        $configured = (string) $this->cfg('api_base_url');

        return rtrim($configured !== '' ? $configured : ($bases['test'] ?? 'https://express.api.dhl.com/mydhlapi/test'), '/');
    }

    public function isSandbox(): bool
    {
        return str_ends_with($this->base(), '/test');
    }

    /**
     * Asks DHL for a rate to a fixed foreign address (New York). Nothing is booked.
     */
    public function testConnection(): array
    {
        $env = $this->isSandbox() ? 'TEST' : 'LIVE';

        $missing = array_keys(array_filter([
            'DHL_API_KEY' => !$this->cfg('api_key'),
            'DHL_API_SECRET' => !$this->cfg('api_secret'),
            'DHL_ACCOUNT_NUMBER' => !$this->cfg('account_number'),
            'origin postal code' => !$this->cfg('origin_postal_code'),
        ]));
        if ($missing) {
            return ['success' => false, 'environment' => $env, 'message' => 'Missing: ' . implode(', ', $missing) . '.'];
        }

        try {
            $response = $this->send('POST', 'rates', [
                'customerDetails' => [
                    'shipperDetails' => ['postalCode' => (string) $this->cfg('origin_postal_code'), 'cityName' => (string) $this->cfg('origin_city', ''), 'countryCode' => $this->originCountry()],
                    'receiverDetails' => ['postalCode' => '10001', 'cityName' => 'New York', 'countryCode' => 'US'],
                ],
                'accounts' => [['typeCode' => 'shipper', 'number' => (string) $this->cfg('account_number')]],
                'plannedShippingDateAndTime' => $this->plannedShippingDate(),
                'unitOfMeasurement' => 'metric',
                'isCustomsDeclarable' => true,
                'monetaryAmount' => [['typeCode' => 'declaredValue', 'value' => 1000, 'currency' => $this->currency()]],
                'packages' => [['weight' => 0.5, 'dimensions' => ['length' => 10, 'width' => 10, 'height' => 10]]],
            ], 20);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            return ['success' => false, 'environment' => $env, 'message' => 'Could not reach ' . $this->base() . ': ' . $e->getMessage()];
        }

        if ($response->status() === 401) {
            return ['success' => false, 'environment' => $env, 'message' => 'DHL rejected the API key/secret (HTTP 401). Check they are for the ' . strtolower($env) . ' environment.'];
        }
        if (!$response->successful()) {
            return ['success' => false, 'environment' => $env, 'message' => 'Keys accepted, but DHL said: ' . $this->errorMessage($json, 'HTTP ' . $response->status())];
        }

        $missingExport = array_keys(array_filter([
            'Exporter IEC' => !$this->cfg('shipper_iec'),
            'shipper address' => !$this->cfg('origin_address'),
            'shipper phone' => !$this->cfg('shipper_phone'),
        ]));

        return [
            'success' => true,
            'environment' => $env,
            'message' => 'Connected. ' . count((array) ($json['products'] ?? [])) . ' service(s) quoted to New York.' . ($missingExport ? ' Before booking, fill in: ' . implode(', ', $missingExport) . '.' : ''),
        ];
    }

    protected function endpoint(string $path): string
    {
        return $this->base() . '/' . ltrim($path, '/');
    }

    protected function send(string $method, string $path, array $data = [], int $timeout = 30): Response
    {
        $request = Http::withBasicAuth((string) $this->cfg('api_key'), (string) $this->cfg('api_secret'))
            ->acceptJson()
            ->timeout($timeout);

        return $method === 'GET'
            ? $request->get($this->endpoint($path), $data)
            : $request->asJson()->post($this->endpoint($path), $data);
    }

    protected function originCountry(): string
    {
        return strtoupper((string) $this->cfg('origin_country', 'IN'));
    }

    protected function currency(): string
    {
        return strtoupper((string) $this->cfg('currency', 'INR'));
    }

    /**
     * Next weekday at 11:00 local time, in DHL's "Y-m-d\TH:i:s GMT+hh:mm" format.
     */
    protected function plannedShippingDate(): string
    {
        $date = now()->addDay()->setTime(11, 0);
        while ($date->isWeekend()) {
            $date->addDay();
        }

        return $date->format('Y-m-d\TH:i:s \G\M\TP');
    }

    /**
     * DHL's error body: { title, detail, additionalDetails: [...] }.
     */
    protected function errorMessage(array $json, string $fallback): string
    {
        $parts = array_filter([
            $json['detail'] ?? $json['message'] ?? $json['title'] ?? null,
            ...array_map(fn ($d) => is_array($d) ? ($d['message'] ?? json_encode($d)) : (string) $d, (array) ($json['additionalDetails'] ?? [])),
        ]);

        return $parts ? implode(' ', $parts) : $fallback;
    }

    // ------------------------------------------------------------ addresses

    /**
     * Destination for the rate context: ISO country, city, postal code.
     */
    protected function destination(array $context): array
    {
        $address = $context['address'] ?? [];
        $country = $this->countryIso($address['country'] ?? $address['country_id'] ?? '');
        $city = $address['city'] ?? null;

        if ((!is_string($city) || $city === '') && !empty($address['city_id'])) {
            $city = optional(City::find($address['city_id']))->name;
        }

        return [
            'country' => $country,
            'city' => (string) ($city ?? ''),
            'postal_code' => (string) ($context['to_pincode'] ?? ''),
        ];
    }

    protected function isInternational(string $countryCode): bool
    {
        return $countryCode !== '' && $countryCode !== $this->originCountry();
    }

    // ---------------------------------------------------------------- rates

    public function rates($orderOrRequest, array $extra = [])
    {
        if (!$this->hasCredentials()) {
            return ['success' => false, 'data' => [], 'message' => 'DHL credentials are not configured'];
        }

        $context = $this->resolveRateContext($orderOrRequest);
        $destination = $this->destination($context);

        if (!$this->isInternational($destination['country'])) {
            return ['success' => false, 'data' => [], 'message' => 'DHL is offered for international addresses only. Use Blue Dart or Delhivery within India.'];
        }

        $package = $context['package'] ?? [];
        $payload = [
            'customerDetails' => [
                'shipperDetails' => [
                    'postalCode' => (string) $this->cfg('origin_postal_code'),
                    'cityName' => (string) $this->cfg('origin_city', ''),
                    'countryCode' => $this->originCountry(),
                ],
                'receiverDetails' => [
                    'postalCode' => $destination['postal_code'],
                    'cityName' => $destination['city'],
                    'countryCode' => $destination['country'],
                ],
            ],
            'accounts' => [[
                'typeCode' => 'shipper',
                'number' => (string) $this->cfg('account_number'),
            ]],
            'plannedShippingDateAndTime' => $this->plannedShippingDate(),
            'unitOfMeasurement' => 'metric',
            'isCustomsDeclarable' => true,
            'monetaryAmount' => [[
                'typeCode' => 'declaredValue',
                'value' => max(1, round($this->orderValue($orderOrRequest), 2)),
                'currency' => $this->currency(),
            ]],
            'packages' => [[
                'weight' => (float) ($package['charged_weight'] ?? 0.5),
                'dimensions' => [
                    'length' => (float) ($package['box_length'] ?? 10),
                    'width' => (float) ($package['box_breadth'] ?? 10),
                    'height' => (float) ($package['box_height'] ?? 10),
                ],
            ]],
        ];

        try {
            $response = $this->send('POST', 'rates', $payload);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('[DHL][Rates] Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'data' => [], 'message' => 'DHL rates unavailable'];
        }

        if (!$response->successful()) {
            Log::warning('[DHL][Rates] API failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 1000)]);
            return ['success' => false, 'data' => [], 'message' => $this->errorMessage($json, 'DHL rates unavailable')];
        }

        $rates = [];
        foreach ((array) ($json['products'] ?? []) as $product) {
            $productCode = (string) ($product['productCode'] ?? $this->cfg('default_product_code', 'P'));
            $price = $this->extractPrice($product);
            if ($price === null) {
                continue;
            }

            $rates[] = [
                'id' => 'dhl:' . $productCode,
                'carrier_id' => $productCode,
                'provider' => 'dhl',
                'name' => (string) ($product['productName'] ?? 'DHL Express'),
                'eta' => $product['deliveryCapabilities']['estimatedDeliveryDateAndTime'] ?? null,
                'price' => $price,
                'meta' => [
                    'product_code' => $productCode,
                    'destination' => $destination['country'],
                    'charged_weight' => $package['charged_weight'] ?? null,
                    'currency' => $this->currency(),
                ],
                'raw' => $product,
            ];
        }

        return $rates
            ? ['success' => true, 'data' => $rates]
            : ['success' => false, 'data' => [], 'message' => 'DHL returned no services for this destination'];
    }

    /**
     * Price in the billing currency (BILLC), falling back to the first total.
     */
    protected function extractPrice(array $product): ?float
    {
        $totals = (array) ($product['totalPrice'] ?? []);
        $billing = collect($totals)->firstWhere('currencyType', 'BILLC') ?? ($totals[0] ?? null);
        $price = $billing['price'] ?? null;

        return is_numeric($price) ? (float) $price : null;
    }

    // --------------------------------------------------------------- create

    public function create(Order $order, array $extra = [])
    {
        $method = ShippingMethod::where('slug', 'dhl')->first();

        if (!$this->hasCredentials()) {
            $data = ['success' => false, 'message' => 'DHL credentials are not configured'];
            $this->storeShipment($order, $method, null, null, $data, 'error');
            return $data;
        }

        $addr = json_decode($order->shipping_address ?? '{}', true) ?: [];
        $countryCode = $this->countryIso($addr['country'] ?? '');
        if (!$this->isInternational($countryCode)) {
            $data = ['success' => false, 'message' => 'DHL is for international shipments only. This order is going to ' . ($addr['country'] ?? 'an unknown country') . '; book it with Blue Dart or Delhivery.'];
            $this->storeShipment($order, $method, null, null, $data, 'error');
            return $data;
        }

        $payload = $this->buildShipmentPayload($order, $addr, $countryCode);
        $missing = $this->missingFields($payload);
        if ($missing) {
            $data = ['success' => false, 'message' => 'DHL needs: ' . implode(', ', $missing) . '.'];
            $this->storeShipment($order, $method, null, null, $data, 'error');
            return $data;
        }

        try {
            $response = $this->send('POST', 'shipments', $payload, 60);
            $json = $response->json() ?: ['raw' => mb_substr($response->body(), 0, 2000)];
        } catch (\Throwable $e) {
            Log::error('[DHL][Create] Exception', ['error' => $e->getMessage()]);
            $json = ['detail' => $e->getMessage()];
            $response = null;
        }

        $awb = $json['shipmentTrackingNumber'] ?? null;
        $success = $response && $response->successful() && !empty($awb);
        $message = $success ? 'Shipment created' : $this->errorMessage($json, 'DHL did not create the shipment');

        // Label and commercial invoice come back as base64 PDFs; keep them as files, not in the DB.
        $labelPath = null;
        if ($success) {
            foreach ((array) ($json['documents'] ?? []) as $document) {
                $type = strtolower((string) ($document['typeCode'] ?? ''));
                $path = $this->saveDocument((string) $awb, $type, $document['content'] ?? null);
                if ($type === 'label' && $path) {
                    $labelPath = $path;
                }
            }
        }
        unset($json['documents']);

        $trackingUrl = $awb ? 'https://www.dhl.com/in-en/home/tracking/tracking-express.html?submit=1&tracking-id=' . urlencode($awb) : null;
        $this->storeShipment($order, $method, $success ? $awb : null, $success ? $trackingUrl : null, $json, $success ? 'created' : 'error', $labelPath);

        Log::info('[DHL][Create] Result', ['order_id' => $order->id, 'awb' => $awb, 'success' => $success, 'message' => $message]);

        return ['success' => $success, 'message' => $message, 'awb' => $awb, 'label_saved' => (bool) $labelPath];
    }

    public function buildShipmentPayload(Order $order, array $addr, string $countryCode): array
    {
        $package = $this->buildPackageFromOrder($order);
        $lineItems = $this->exportLineItems($order);
        $declaredValue = round(array_sum(array_map(fn ($l) => $l['price'] * $l['quantity']['value'], $lineItems)), 2);
        [$shipperAddress1, $shipperAddress2] = array_pad($this->splitAddress($this->cfg('origin_address')), 2, '');
        [$receiverAddress1, $receiverAddress2] = array_pad($this->splitAddress($addr['address'] ?? ''), 2, '');
        $productCode = (string) ($order->shipping_courier_id ?: $this->cfg('default_product_code', 'P'));
        $invoiceDate = optional($order->created_at)->format('Y-m-d') ?: now()->format('Y-m-d');

        $registrationNumbers = array_values(array_filter([
            $this->cfg('shipper_gstin') ? ['typeCode' => self::GSTIN_TYPE_CODE, 'number' => (string) $this->cfg('shipper_gstin'), 'issuerCountryCode' => $this->originCountry()] : null,
            $this->cfg('shipper_iec') ? ['typeCode' => self::IEC_TYPE_CODE, 'number' => (string) $this->cfg('shipper_iec'), 'issuerCountryCode' => $this->originCountry()] : null,
        ]));

        $shipperDetails = [
            'postalAddress' => array_filter([
                'postalCode' => (string) $this->cfg('origin_postal_code'),
                'cityName' => (string) $this->cfg('origin_city', ''),
                'countryCode' => $this->originCountry(),
                'addressLine1' => $shipperAddress1,
                'addressLine2' => $shipperAddress2,
            ], fn ($v) => $v !== ''),
            'contactInformation' => [
                'email' => (string) $this->cfg('shipper_email', ''),
                'phone' => (string) $this->cfg('shipper_phone', ''),
                'companyName' => (string) $this->cfg('shipper_name', ''),
                'fullName' => (string) $this->cfg('shipper_name', ''),
            ],
            'typeCode' => 'business',
        ];
        if ($registrationNumbers) {
            $shipperDetails['registrationNumbers'] = $registrationNumbers;
        }

        return [
            'plannedShippingDateAndTime' => $this->plannedShippingDate(),
            'pickup' => ['isRequested' => false],
            'productCode' => $productCode,
            'accounts' => [[
                'typeCode' => 'shipper',
                'number' => (string) $this->cfg('account_number'),
            ]],
            'outputImageProperties' => [
                'encodingFormat' => 'pdf',
                'imageOptions' => [
                    ['typeCode' => 'label'],
                    ['typeCode' => 'invoice', 'isRequested' => true, 'invoiceType' => 'commercial'],
                ],
            ],
            'customerDetails' => [
                'shipperDetails' => $shipperDetails,
                'receiverDetails' => [
                    'postalAddress' => array_filter([
                        'postalCode' => (string) ($addr['postal_code'] ?? $addr['zipcode'] ?? ''),
                        'cityName' => (string) ($addr['city'] ?? ''),
                        'provinceName' => (string) ($addr['state'] ?? ''),
                        'countryCode' => $countryCode,
                        'addressLine1' => $receiverAddress1,
                        'addressLine2' => $receiverAddress2,
                    ], fn ($v) => $v !== ''),
                    'contactInformation' => [
                        'email' => (string) ($addr['email'] ?? ''),
                        'phone' => (string) ($addr['phone'] ?? ''),
                        'companyName' => (string) ($addr['name'] ?? ''),
                        'fullName' => (string) ($addr['name'] ?? ''),
                    ],
                    'typeCode' => 'business',
                ],
            ],
            'content' => [
                'packages' => [[
                    'weight' => max(0.1, (float) ($package['charged_weight'] ?? 0.5)),
                    'dimensions' => [
                        'length' => (float) ($package['box_length'] ?? 10),
                        'width' => (float) ($package['box_breadth'] ?? 10),
                        'height' => (float) ($package['box_height'] ?? 10),
                    ],
                ]],
                'isCustomsDeclarable' => true,
                'declaredValue' => $declaredValue,
                'declaredValueCurrency' => $this->currency(),
                'description' => mb_substr('Order ' . $order->code . ': ' . collect($lineItems)->pluck('description')->implode(', '), 0, 70),
                'incoterm' => (string) $this->cfg('incoterm', 'DAP'),
                'unitOfMeasurement' => 'metric',
                'exportDeclaration' => [
                    'lineItems' => $lineItems,
                    'invoice' => [
                        'number' => (string) $order->code,
                        'date' => $invoiceDate,
                    ],
                    'exportReason' => 'Sale',
                    'exportReasonType' => 'permanent',
                ],
            ],
            'getRateEstimates' => false,
        ];
    }

    /**
     * One export line per order item: unit price, quantity, HS code (from the product's HSN),
     * net/gross weight from the Weight And Dimension Master.
     */
    public function exportLineItems(Order $order): array
    {
        $order->loadMissing('orderDetails.product.stocks.dimensionSheet');
        $items = [];

        foreach ($order->orderDetails as $detail) {
            $product = $detail->product;
            $qty = max(1, (int) $detail->quantity);
            $stock = ($product && !empty($detail->variation))
                ? $product->stocks->where('variant', $detail->variation)->first()
                : null;
            $line = ShipmentPackage::line($stock, $product, $qty);
            $netGrams = is_numeric($stock->weight ?? null) && (float) $stock->weight >= ShipmentPackage::MIN_REAL_WEIGHT_G
                ? (float) $stock->weight
                : $line['weight'] * 1000;

            $items[] = [
                'number' => count($items) + 1,
                'description' => mb_substr($product ? $product->getTranslation('name') : 'Product', 0, 70),
                'price' => round((float) $detail->price / $qty, 2),
                'quantity' => ['value' => $qty, 'unitOfMeasurement' => 'PCS'],
                'commodityCodes' => array_values(array_filter([
                    ($hs = $this->productHsn($product)) !== '' ? ['typeCode' => 'outbound', 'value' => $hs] : null,
                ])),
                'exportReasonType' => 'permanent',
                'manufacturerCountry' => $this->originCountry(),
                'weight' => [
                    'netValue' => round($netGrams * $qty / 1000, 3),
                    'grossValue' => round($line['weight'] * $qty, 3),
                ],
            ];
        }

        return $items;
    }

    /**
     * Shipper/receiver fields DHL rejects when empty, as readable names.
     */
    protected function missingFields(array $payload): array
    {
        $checks = [
            'shipper city (Courier APIs page)' => data_get($payload, 'customerDetails.shipperDetails.postalAddress.cityName'),
            'shipper address (Courier APIs page)' => data_get($payload, 'customerDetails.shipperDetails.postalAddress.addressLine1'),
            'shipper phone (Courier APIs page)' => data_get($payload, 'customerDetails.shipperDetails.contactInformation.phone'),
            'shipper name (Courier APIs page)' => data_get($payload, 'customerDetails.shipperDetails.contactInformation.companyName'),
            'shipper IEC (Courier APIs page)' => collect(data_get($payload, 'customerDetails.shipperDetails.registrationNumbers', []))->firstWhere('typeCode', self::IEC_TYPE_CODE),
            'receiver city' => data_get($payload, 'customerDetails.receiverDetails.postalAddress.cityName'),
            'receiver address' => data_get($payload, 'customerDetails.receiverDetails.postalAddress.addressLine1'),
            'receiver phone' => data_get($payload, 'customerDetails.receiverDetails.contactInformation.phone'),
            'order items' => data_get($payload, 'content.exportDeclaration.lineItems'),
        ];

        return array_keys(array_filter($checks, fn ($value) => empty($value)));
    }

    protected function saveDocument(string $awb, string $type, $content): ?string
    {
        $bytes = is_string($content) ? base64_decode($content, true) : false;
        if ($bytes === false || !str_starts_with($bytes, '%PDF')) {
            return null;
        }

        $suffix = $type === 'label' ? '' : '-' . preg_replace('/[^a-z]/', '', $type);
        $path = 'shipping-labels/dhl/' . preg_replace('/[^A-Za-z0-9_-]/', '', $awb) . $suffix . '.pdf';
        Storage::disk('local')->put($path, $bytes);

        return $path;
    }

    // ------------------------------------------------- label / track / cancel

    public function label(OrderShipment $shipment): array
    {
        if ($shipment->label_path && Storage::disk('local')->exists($shipment->label_path)) {
            return ['success' => true, 'path' => $shipment->label_path];
        }

        return ['success' => false, 'message' => 'DHL returns the label only when the shipment is created, and none was saved for this AWB. Print it from MyDHL+.'];
    }

    public function track(OrderShipment $shipment): array
    {
        $awb = (string) $shipment->shipping_id;
        if ($awb === '') {
            return ['success' => false, 'message' => 'This shipment has no waybill yet.'];
        }

        try {
            $response = $this->send('GET', 'shipments/' . rawurlencode($awb) . '/tracking', [
                'trackingView' => 'all-checkpoints',
                'levelOfDetail' => 'all',
            ], 20);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('[DHL][Track] Exception', ['awb' => $awb, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not reach DHL for tracking.'];
        }

        $tracking = $this->parseTracking($json);
        if (!$response->successful() || !$tracking) {
            Log::warning('[DHL][Track] No tracking data', ['awb' => $awb, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            return ['success' => false, 'message' => $this->errorMessage($json, 'DHL returned no tracking for AWB ' . $awb . '.')];
        }

        $shipment->courier_status = mb_substr($tracking['status'], 0, 100);
        $shipment->last_synced_at = now();
        $shipment->save();

        $orderStatus = null;
        $order = $shipment->order;
        if ($order) {
            try {
                $orderStatus = app(OrderDeliveryStatusService::class)->advanceFromCourier($order, $tracking['stage'], $tracking['status']);
            } catch (\Throwable $e) {
                Log::error('[DHL][Track] Order status update failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        return ['success' => true, 'order_status' => $orderStatus] + $tracking;
    }

    /**
     * Latest checkpoint -> stage (manifested, in_transit, out_for_delivery, delivered, returned, unknown).
     * DHL event codes: PU picked up, WC with courier for delivery, OK delivered, RT returned to shipper.
     */
    public function parseTracking(array $json): ?array
    {
        $shipment = $json['shipments'][0] ?? null;
        if (!is_array($shipment)) {
            return null;
        }

        $events = collect((array) ($shipment['events'] ?? []))
            ->sortBy(fn ($e) => ($e['date'] ?? '') . ' ' . ($e['time'] ?? ''))
            ->values();
        $last = $events->last();

        if (!$last) {
            return [
                'status' => 'Shipment information received',
                'status_type' => '',
                'stage' => 'manifested',
                'location' => '',
                'updated_at' => '',
                'instructions' => '',
                'expected_delivery' => (string) ($shipment['estimatedDeliveryDate'] ?? ''),
            ];
        }

        $code = strtoupper((string) ($last['typeCode'] ?? ''));
        $description = trim((string) ($last['description'] ?? ''));

        $stage = match (true) {
            $code === 'OK' => 'delivered',
            $code === 'RT' || stripos($description, 'returned to shipper') !== false => 'returned',
            $code === 'WC' || stripos($description, 'with delivery courier') !== false => 'out_for_delivery',
            $code === 'PU' => 'in_transit',
            $code !== '' => 'in_transit',
            default => 'unknown',
        };

        return [
            'status' => $description !== '' ? $description : $code,
            'status_type' => $code,
            'stage' => $stage,
            'location' => (string) ($last['serviceArea'][0]['description'] ?? ''),
            'updated_at' => trim(($last['date'] ?? '') . ' ' . ($last['time'] ?? '')),
            'instructions' => '',
            'expected_delivery' => (string) ($shipment['estimatedDeliveryDate'] ?? ''),
        ];
    }

    /**
     * DHL Express has no AWB cancel call: a waybill that is never handed over is not billed.
     * Cancelling here marks it cancelled so the order can be booked again.
     */
    public function cancel(OrderShipment $shipment): array
    {
        if (blank($shipment->shipping_id)) {
            return ['success' => false, 'message' => 'This shipment has no waybill to cancel.'];
        }

        $shipment->status = 'cancelled';
        $shipment->courier_status = 'Cancelled';
        $shipment->last_synced_at = now();
        $shipment->save();

        return ['success' => true, 'message' => 'Marked as cancelled. DHL does not bill a waybill that is never handed over; do not give this parcel to DHL.'];
    }

    protected function storeShipment(Order $order, ?ShippingMethod $method, ?string $awb, ?string $trackingUrl, array $data, string $status, ?string $labelPath = null): void
    {
        OrderShipment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'shipping_method_id' => $method?->id,
                'shipping_id' => $awb,
                'tracking_url' => $trackingUrl,
                'label_path' => $labelPath,
                'shipping_type' => 'DHL Express',
                'raw_response' => json_encode($data),
                'status' => $status,
                'courier_status' => $status === 'created' ? 'Shipment created' : null,
                'last_synced_at' => $status === 'created' ? now() : null,
            ]
        );

        if ($awb && $status === 'created' && blank($order->tracking_code)) {
            $order->tracking_code = $awb;
            $order->save();
        }
    }
}
