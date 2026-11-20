<?php

namespace App\Http\Controllers\Shipment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Shipment\Concerns\BuildsShipmentPackages;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\ShippingMethod;
use App\Services\OrderDeliveryStatusService;
use App\Support\BluedartRateCard;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Blue Dart API Gateway.
 *
 * Going live = put live keys in .env and set BLUEDART_ENV=production.
 * Endpoints used (all under the same host):
 *   GET  in/transportation/token/v1/login                                         (JWT from ClientID/clientSecret)
 *   POST in/transportation/transit/v1/GetDomesticTransitTimeForPinCodeandProduct  (serviceability + ETA)
 *   POST in/transportation/waybill/v1/GenerateWayBill                              (AWB + label PDF)
 *   POST in/transportation/waybill/v1/CancelWaybill
 *   GET  in/transportation/tracking/v1                                            (tracking)
 * Prices come from the admin rate card (Blue Dart has no price API).
 */
class BluedartController extends Controller
{
    use BuildsShipmentPackages;

    protected const SECRET_KEYS = ['api_base_url', 'jwt_token', 'client_id', 'client_secret', 'login_id', 'licence_key', 'customer_code'];

    /**
     * Credentials stay in .env; shipper/origin details come from
     * Admin > Setup > Shipping > Courier APIs, falling back to config.
     */
    protected function cfg(string $key, $default = null)
    {
        if (!in_array($key, self::SECRET_KEYS, true) && function_exists('get_setting')) {
            $setting = get_setting('bluedart_' . $key);
            if ($setting !== null && $setting !== '') {
                return $setting;
            }
        }

        $value = config("courier_providers.bluedart.{$key}");

        return $value === null || $value === '' ? $default : $value;
    }

    protected function usesClientCredentials(): bool
    {
        return (bool) ($this->cfg('client_id') && $this->cfg('client_secret'));
    }

    public function hasCredentials(): bool
    {
        return ($this->usesClientCredentials() || (bool) $this->cfg('jwt_token'))
            && $this->cfg('login_id')
            && $this->cfg('licence_key')
            && $this->cfg('customer_code');
    }

    /**
     * One host for every call so sandbox and production never mix.
     */
    public function host(): string
    {
        $hosts = (array) config('courier_providers.bluedart.hosts', []);
        $environment = strtolower(trim((string) config('courier_providers.bluedart.environment')));

        if (isset($hosts[$environment])) {
            return rtrim($hosts[$environment], '/');
        }

        $parts = parse_url((string) $this->cfg('api_base_url'));
        if (!empty($parts['host'])) {
            return ($parts['scheme'] ?? 'https') . '://' . $parts['host'];
        }

        return rtrim($hosts['sandbox'] ?? 'https://apigateway-sandbox.bluedart.com', '/');
    }

    public function isSandbox(): bool
    {
        return str_contains($this->host(), 'sandbox');
    }

    /**
     * Logs in and runs a transit-time lookup from the pickup pincode to itself.
     * Nothing is booked.
     */
    public function testConnection(): array
    {
        $env = $this->isSandbox() ? 'SANDBOX' : 'LIVE';

        $missing = array_keys(array_filter([
            'BLUEDART_CLIENT_ID / BLUEDART_CLIENT_SECRET' => !$this->usesClientCredentials() && !$this->cfg('jwt_token'),
            'BLUEDART_LOGIN_ID' => !$this->cfg('login_id'),
            'BLUEDART_LICENCE_KEY' => !$this->cfg('licence_key'),
            'BLUEDART_CUSTOMER_CODE' => !$this->cfg('customer_code'),
            'origin pincode' => !$this->cfg('origin_pincode'),
        ]));
        if ($missing) {
            return ['success' => false, 'environment' => $env, 'message' => 'Missing: ' . implode(', ', $missing) . '.'];
        }

        if (!$this->token(true)) {
            return ['success' => false, 'environment' => $env, 'message' => 'Blue Dart login failed on ' . $this->host() . '. Check the Client ID and secret are for the ' . strtolower($env) . ' environment.'];
        }

        $pin = (string) $this->cfg('origin_pincode');
        try {
            $response = $this->send('POST', 'in/transportation/transit/v1/GetDomesticTransitTimeForPinCodeandProduct', [
                'pPinCodeFrom' => $pin,
                'pPinCodeTo' => $pin,
                'pProductCode' => (string) $this->cfg('product_code', 'D'),
                'pSubProductCode' => (string) $this->cfg('sub_product_code', 'P'),
                'pPudate' => $this->blueDartDate(),
                'pPickupTime' => '16:00',
                'profile' => $this->profile(),
            ], 15);
        } catch (\Throwable $e) {
            return ['success' => false, 'environment' => $env, 'message' => 'Logged in, but the transit lookup failed: ' . $e->getMessage()];
        }

        if (!$response->successful()) {
            return ['success' => false, 'environment' => $env, 'message' => 'Logged in, but Blue Dart answered HTTP ' . $response->status() . ' to a transit lookup. Check the Login ID and Licence Key.'];
        }

        $data = $response->json('GetDomesticTransitTimeForPinCodeandProductResult') ?? ($response->json() ?: []);
        if (filter_var($data['IsError'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return ['success' => false, 'environment' => $env, 'message' => 'Logged in, but Blue Dart said: ' . ($data['ErrorMessage'] ?? 'error') . '. Check the Login ID, Licence Key and product code.'];
        }

        $card = \App\Support\BluedartRateCard::fromSetting($this->cfg('rate_card'));
        $priced = collect(\App\Support\BluedartRateCard::ZONES)->keys()->filter(fn ($z) => is_numeric($card['zones'][$z]['first'] ?? null) && (float) $card['zones'][$z]['first'] > 0)->count();

        return [
            'success' => true,
            'environment' => $env,
            'message' => 'Connected. Rate card has prices for ' . $priced . ' of 5 zones.',
        ];
    }

    protected function endpoint(string $path): string
    {
        return $this->host() . '/' . ltrim($path, '/');
    }

    protected function tokenCacheKey(): string
    {
        return 'bluedart:jwt:' . md5($this->host() . '|' . $this->cfg('client_id'));
    }

    /**
     * JWT from ClientID/clientSecret, cached and renewed automatically.
     * Falls back to a fixed BLUEDART_JWT_TOKEN when no client credentials are set.
     */
    public function token(bool $fresh = false): ?string
    {
        if (!$this->usesClientCredentials()) {
            return $this->cfg('jwt_token');
        }

        if ($fresh) {
            Cache::forget($this->tokenCacheKey());
        }

        $cached = Cache::get($this->tokenCacheKey());
        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'ClientID' => (string) $this->cfg('client_id'),
                'clientSecret' => (string) $this->cfg('client_secret'),
            ])->acceptJson()->timeout(15)->get($this->endpoint('in/transportation/token/v1/login'));
        } catch (\Throwable $e) {
            Log::error('[BlueDart][Token] Exception', ['error' => $e->getMessage()]);
            return null;
        }

        $token = $response->json('JWTToken') ?? $response->json('jwtToken') ?? $response->json('token');
        if (!$response->successful() || !$token) {
            Log::warning('[BlueDart][Token] Login failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 300)]);
            return null;
        }

        // Renewed well before Blue Dart's expiry; a 401 also forces a renewal.
        Cache::put($this->tokenCacheKey(), $token, now()->addHours(12));

        return $token;
    }

    /**
     * Calls the API with the JWT header, renewing the token once on 401.
     */
    protected function send(string $method, string $path, array $data = [], int $timeout = 20): Response
    {
        $call = function (?string $token) use ($method, $path, $data, $timeout) {
            $request = Http::withHeaders(['JWTToken' => (string) $token])->acceptJson()->timeout($timeout);

            return $method === 'GET'
                ? $request->get($this->endpoint($path), $data)
                : $request->asJson()->post($this->endpoint($path), $data);
        };

        $response = $call($this->token());

        if ($response->status() === 401 && $this->usesClientCredentials()) {
            $response = $call($this->token(true));
        }

        return $response;
    }

    protected function profile(): array
    {
        return [
            'Api_type' => 'S',
            'LicenceKey' => $this->cfg('licence_key'),
            'LoginID' => $this->cfg('login_id'),
        ];
    }

    protected function blueDartDate(): string
    {
        return '/Date(' . (now()->timestamp * 1000) . ')/';
    }

    // ---------------------------------------------------------------- rates

    public function rates($orderOrRequest, array $extra = [])
    {
        if (!$this->hasCredentials() || !$this->cfg('origin_pincode')) {
            return ['success' => false, 'data' => [], 'message' => 'Blue Dart credentials are not configured'];
        }

        $context = $this->resolveRateContext($orderOrRequest);
        if (empty($context['to_pincode'])) {
            return ['success' => false, 'data' => [], 'message' => 'to_pincode or address_id required'];
        }

        $isCod = ($context['payment_type'] ?? 'prepaid') === 'cod';
        $productCode = (string) $this->cfg('product_code', 'D');
        $subProductCode = $isCod ? 'C' : (string) $this->cfg('sub_product_code', 'P');

        $transit = $this->transitTime((string) $context['to_pincode'], $productCode, $subProductCode);
        if ($transit['checked'] && !$transit['serviceable']) {
            return ['success' => false, 'data' => [], 'message' => 'Blue Dart does not deliver to pincode ' . $context['to_pincode'] . ($transit['message'] ? ' (' . $transit['message'] . ')' : '')];
        }

        $chargedWeight = (float) ($context['package']['charged_weight'] ?? 0.5);
        $zone = BluedartRateCard::zone((string) $this->cfg('origin_pincode'), (string) $context['to_pincode']);
        $quote = BluedartRateCard::price(
            BluedartRateCard::fromSetting($this->cfg('rate_card')),
            $zone,
            $chargedWeight,
            $isCod,
            $this->orderValue($orderOrRequest)
        );

        $price = $quote['total'] ?? null;
        if ($price === null && is_numeric($this->cfg('default_rate')) && (float) $this->cfg('default_rate') > 0) {
            $price = (float) $this->cfg('default_rate');
        }

        if ($price === null) {
            Log::warning('[BlueDart][Rates] No rate card price', ['zone' => $zone]);
            return ['success' => false, 'data' => [], 'message' => 'Blue Dart rate card is not set for this zone'];
        }

        return [
            'success' => true,
            'data' => [[
                'id' => 'bluedart:' . $productCode,
                'carrier_id' => $productCode,
                'provider' => 'bluedart',
                'name' => 'Blue Dart',
                'eta' => $transit['eta'],
                'price' => $price,
                'meta' => [
                    'product_code' => $productCode,
                    'sub_product_code' => $subProductCode,
                    'zone' => $zone,
                    'charged_weight' => $context['package']['charged_weight'] ?? null,
                    'breakdown' => $quote,
                ],
                'raw' => $transit['raw'],
            ]],
        ];
    }

    /**
     * Serviceability + expected delivery date. 'checked' is false when the API
     * could not be reached, so checkout is not blocked by an outage.
     */
    public function transitTime(string $toPincode, string $productCode, string $subProductCode): array
    {
        $result = ['checked' => false, 'serviceable' => true, 'eta' => null, 'message' => '', 'raw' => []];

        try {
            $response = $this->send('POST', 'in/transportation/transit/v1/GetDomesticTransitTimeForPinCodeandProduct', [
                'pPinCodeFrom' => (string) $this->cfg('origin_pincode'),
                'pPinCodeTo' => $toPincode,
                'pProductCode' => $productCode,
                'pSubProductCode' => $subProductCode,
                'pPudate' => $this->blueDartDate(),
                'pPickupTime' => '16:00',
                'profile' => $this->profile(),
            ], 10);
        } catch (\Throwable $e) {
            Log::warning('[BlueDart][Transit] Exception', ['error' => $e->getMessage()]);
            return $result;
        }

        if (!$response->successful()) {
            Log::warning('[BlueDart][Transit] API failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            return $result;
        }

        $json = $response->json() ?: [];
        $data = $json['GetDomesticTransitTimeForPinCodeandProductResult'] ?? $json;
        $isError = filter_var($data['IsError'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'checked' => true,
            'serviceable' => !$isError,
            'eta' => $data['ExpectedDateDelivery'] ?? null,
            'message' => (string) ($data['ErrorMessage'] ?? ''),
            'raw' => $data,
        ];
    }

    // --------------------------------------------------------------- create

    public function create(Order $order, array $extra = [])
    {
        $method = ShippingMethod::where('slug', 'bluedart')->first();

        if (!$this->hasCredentials()) {
            $data = ['success' => false, 'message' => 'Blue Dart credentials are not configured'];
            $this->storeShipment($order, $method, null, null, $data, 'error', $extra);
            return $data;
        }

        $addr = json_decode($order->shipping_address ?? '{}', true) ?: [];
        $package = $this->buildPackageFromOrder($order);
        [$addr1, $addr2, $addr3] = $this->splitAddress($addr['address'] ?? '');
        [$shipperAddr1, $shipperAddr2, $shipperAddr3] = $this->splitAddress($this->cfg('shipper_address'));
        $productCode = (string) ($order->shipping_courier_id ?: $this->cfg('product_code', 'D'));
        $isCod = $this->isCodOrder($order);
        // Blue Dart: sub product 'C' = COD (collect CollectableAmount), 'P' = prepaid.
        $subProductCode = $isCod ? 'C' : (string) $this->cfg('sub_product_code', 'P');

        $payload = [
            'Request' => [
                'Consignee' => [
                    'ConsigneeAddress1' => $addr1,
                    'ConsigneeAddress2' => $addr2,
                    'ConsigneeAddress3' => $addr3,
                    'ConsigneeAddressType' => 'R',
                    'ConsigneeEmailID' => $addr['email'] ?? '',
                    'ConsigneeMobile' => $addr['phone'] ?? '',
                    'ConsigneeName' => $addr['name'] ?? '',
                    'ConsigneePincode' => $addr['postal_code'] ?? $addr['zipcode'] ?? '',
                    'ConsigneeTelephone' => '',
                ],
                'Returnadds' => [
                    'ReturnAddress1' => $shipperAddr1,
                    'ReturnAddress2' => $shipperAddr2,
                    'ReturnAddress3' => $shipperAddr3,
                    'ReturnContact' => $this->cfg('shipper_name'),
                    'ReturnEmailID' => '',
                    'ReturnMobile' => $this->cfg('shipper_mobile'),
                    'ReturnPincode' => $this->cfg('origin_pincode'),
                    'ReturnTelephone' => '',
                ],
                'Services' => [
                    'AWBNo' => '',
                    'ActualWeight' => $package['total_physical_weight'],
                    'CollectableAmount' => $isCod ? (float) $order->grand_total : 0,
                    'DeclaredValue' => (float) $order->grand_total,
                    'Commodity' => [],
                    'CreditReferenceNo' => (string) $order->code,
                    'Dimensions' => [[
                        'Breadth' => $package['box_breadth'],
                        'Count' => 1,
                        'Height' => $package['box_height'],
                        'Length' => $package['box_length'],
                    ]],
                    'InvoiceNo' => (string) $order->code,
                    'PDFOutputNotRequired' => false,
                    'PickupDate' => $this->blueDartDate(),
                    'PickupTime' => '1600',
                    'PieceCount' => '1',
                    'ProductCode' => $productCode,
                    'ProductType' => 0,
                    'RegisterPickup' => false,
                    'SubProductCode' => $subProductCode,
                    'itemdtl' => $this->itemDetails($order),
                    'noOfDCGiven' => 0,
                ],
                'Shipper' => [
                    'CustomerAddress1' => $shipperAddr1,
                    'CustomerAddress2' => $shipperAddr2,
                    'CustomerAddress3' => $shipperAddr3,
                    'CustomerCode' => $this->cfg('customer_code'),
                    'CustomerEmailID' => '',
                    'CustomerMobile' => $this->cfg('shipper_mobile'),
                    'CustomerName' => $this->cfg('shipper_name'),
                    'CustomerPincode' => $this->cfg('origin_pincode'),
                    'CustomerTelephone' => '',
                    'IsToPayCustomer' => false,
                    'OriginArea' => $this->cfg('origin_area'),
                    'Sender' => $this->cfg('sender'),
                    'VendorCode' => '',
                ],
            ],
            'Profile' => $this->profile(),
        ];

        try {
            $response = $this->send('POST', 'in/transportation/waybill/v1/GenerateWayBill', $payload, 30);
            $data = $response->json() ?: ['raw' => mb_substr($response->body(), 0, 2000), 'http_status' => $response->status()];
        } catch (\Throwable $e) {
            Log::error('[BlueDart][Create] Exception', ['error' => $e->getMessage()]);
            $data = ['message' => $e->getMessage()];
        }

        $result = $data['GenerateWayBillResult'] ?? $data;
        $awb = $result['AWBNo'] ?? null;
        $isError = filter_var($result['IsError'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $success = !empty($awb) && !$isError;
        $message = $this->statusMessage($result) ?: ($data['message'] ?? ($success ? 'Shipment created' : 'Blue Dart did not create the waybill'));

        // The label PDF comes back inside the create response; keep it as a file, not in the DB.
        $labelPath = $success ? $this->saveLabel((string) $awb, $result['AWBPrintContent'] ?? null) : null;
        unset($data['GenerateWayBillResult']['AWBPrintContent'], $data['AWBPrintContent']);

        $trackingUrl = $awb ? 'https://www.bluedart.com/web/guest/trackdartresult?trackFor=0&trackNo=' . urlencode($awb) : null;
        $this->storeShipment($order, $method, $success ? $awb : null, $success ? $trackingUrl : null, $data, $success ? 'created' : 'error', $extra, $labelPath);

        Log::info('[BlueDart][Create] Result', ['order_id' => $order->id, 'awb' => $awb, 'success' => $success, 'message' => $message]);

        return ['success' => $success, 'message' => $message, 'awb' => $awb, 'label_saved' => (bool) $labelPath];
    }

    protected function statusMessage(array $result): string
    {
        return collect($result['Status'] ?? [])
            ->map(fn ($status) => is_array($status) ? trim(($status['StatusInformation'] ?? '') ?: ($status['StatusCode'] ?? '')) : (string) $status)
            ->filter()
            ->implode('; ');
    }

    /**
     * AWBPrintContent arrives as base64 text or as a JSON byte array.
     */
    protected function saveLabel(string $awb, $content): ?string
    {
        if (empty($content)) {
            return null;
        }

        if (is_array($content)) {
            $bytes = pack('C*', ...array_map('intval', $content));
        } else {
            $bytes = base64_decode((string) $content, true) ?: (string) $content;
        }

        if (!str_starts_with($bytes, '%PDF')) {
            Log::warning('[BlueDart][Label] Content is not a PDF', ['awb' => $awb]);
            return null;
        }

        $path = 'shipping-labels/bluedart/' . preg_replace('/[^A-Za-z0-9_-]/', '', $awb) . '.pdf';
        Storage::disk('local')->put($path, $bytes);

        return $path;
    }

    // ------------------------------------------------- label / track / cancel

    public function label(OrderShipment $shipment): array
    {
        if ($shipment->label_path && Storage::disk('local')->exists($shipment->label_path)) {
            return ['success' => true, 'path' => $shipment->label_path];
        }

        return ['success' => false, 'message' => 'Blue Dart returns the label only when the waybill is created, and none was saved for this AWB. Print it from the Blue Dart portal.'];
    }

    public function track(OrderShipment $shipment): array
    {
        $awb = (string) $shipment->shipping_id;
        if ($awb === '') {
            return ['success' => false, 'message' => 'This shipment has no waybill yet.'];
        }

        try {
            $response = $this->send('GET', 'in/transportation/tracking/v1', [
                'handler' => 'tnt',
                'action' => 'custawbquery',
                'loginid' => (string) $this->cfg('login_id'),
                'awb' => 'awb',
                'numbers' => $awb,
                'format' => 'json',
                'lickey' => (string) $this->cfg('licence_key'),
                'verno' => 1,
                'scan' => 1,
            ], 15);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('[BlueDart][Track] Exception', ['awb' => $awb, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not reach Blue Dart for tracking.'];
        }

        $tracking = $this->parseTracking($json);
        if (!$response->successful() || !$tracking) {
            Log::warning('[BlueDart][Track] No tracking data', ['awb' => $awb, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            return ['success' => false, 'message' => 'Blue Dart returned no tracking for AWB ' . $awb . '.'];
        }

        $shipment->courier_status = mb_substr($tracking['status'], 0, 100);
        $shipment->last_synced_at = now();
        if ($tracking['stage'] === 'cancelled') {
            $shipment->status = 'cancelled';
        }
        $shipment->save();

        $orderStatus = null;
        $order = $shipment->order;
        if ($order) {
            try {
                $orderStatus = app(OrderDeliveryStatusService::class)->advanceFromCourier($order, $tracking['stage'], $tracking['status']);
            } catch (\Throwable $e) {
                Log::error('[BlueDart][Track] Order status update failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        return ['success' => true, 'order_status' => $orderStatus] + $tracking;
    }

    /**
     * stage is one of: manifested, in_transit, out_for_delivery, delivered, returned, cancelled, unknown.
     */
    public function parseTracking(array $json): ?array
    {
        $shipments = $json['ShipmentData']['Shipment'] ?? null;
        $data = is_array($shipments) ? (isset($shipments[0]) ? $shipments[0] : $shipments) : null;
        if (!is_array($data) || (empty($data['Status']) && empty($data['StatusType']))) {
            return null;
        }

        $status = trim((string) ($data['Status'] ?? ''));
        $type = strtoupper(trim((string) ($data['StatusType'] ?? '')));
        $upper = strtoupper($status);

        $stage = match (true) {
            str_contains($upper, 'CANCEL') => 'cancelled',
            $type === 'RT' || str_contains($upper, 'RTO') || str_contains($upper, 'RETURN') => 'returned',
            $type === 'DL' || str_contains($upper, 'DELIVERED') => 'delivered',
            str_contains($upper, 'OUT FOR DELIVERY') => 'out_for_delivery',
            str_contains($upper, 'DATA RECEIVED') || str_contains($upper, 'MANIFEST') || str_contains($upper, 'NOT PICKED') => 'manifested',
            in_array($type, ['PU', 'IT', 'UD'], true) || str_contains($upper, 'PICKED') || str_contains($upper, 'TRANSIT') => 'in_transit',
            default => 'unknown',
        };

        $date = trim(($data['StatusDate'] ?? '') . ' ' . ($data['StatusTime'] ?? ''));

        return [
            'status' => $status !== '' ? ucwords(strtolower($status)) : 'Unknown',
            'status_type' => $type,
            'stage' => $stage,
            'location' => (string) ($data['Scans']['ScanDetail'][0]['ScannedLocation'] ?? $data['Destination'] ?? ''),
            'updated_at' => $date,
            'instructions' => '',
            'expected_delivery' => (string) ($data['ExpectedDeliveryDate'] ?? ''),
        ];
    }

    public function cancel(OrderShipment $shipment): array
    {
        $awb = (string) $shipment->shipping_id;
        if ($awb === '') {
            return ['success' => false, 'message' => 'This shipment has no waybill to cancel.'];
        }

        try {
            $response = $this->send('POST', 'in/transportation/waybill/v1/CancelWaybill', [
                'Request' => ['AWBNo' => $awb],
                'Profile' => $this->profile(),
            ], 15);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('[BlueDart][Cancel] Exception', ['awb' => $awb, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not reach Blue Dart to cancel.'];
        }

        $result = $json['CancelWaybillResult'] ?? $json;
        $ok = $response->successful() && array_key_exists('IsError', $result) && !filter_var($result['IsError'], FILTER_VALIDATE_BOOLEAN);
        $message = $this->statusMessage($result);

        Log::info('[BlueDart][Cancel] Response', ['awb' => $awb, 'ok' => $ok, 'message' => $message]);

        if (!$ok) {
            return ['success' => false, 'message' => $message !== '' ? $message : 'Blue Dart did not cancel AWB ' . $awb . '.'];
        }

        $shipment->status = 'cancelled';
        $shipment->courier_status = 'Cancelled';
        $shipment->last_synced_at = now();
        $shipment->save();

        return ['success' => true, 'message' => $message !== '' ? $message : 'Shipment cancelled.'];
    }

    // -------------------------------------------------------------- helpers

    protected function itemDetails(Order $order): array
    {
        return collect($this->orderItems($order))->map(function ($item) {
            return [
                'CGSTAmount' => 0,
                'HSCode' => $item['hsn'],
                'IGSTAmount' => 0,
                'Instruction' => '',
                'InvoiceDate' => now()->format('Y-m-d'),
                'InvoiceNumber' => '',
                'ItemID' => $item['sku'],
                'ItemName' => $item['name'],
                'ItemValue' => $item['price'],
                'Itemquantity' => $item['quantity'],
                'PlaceofSupply' => '',
                'ProductDesc1' => $item['name'],
                'ProductDesc2' => '',
                'ReturnReason' => '',
                'SGSTAmount' => 0,
                'SKUNumber' => $item['sku'],
                'SellerGSTNNumber' => '',
                'SellerName' => $this->cfg('shipper_name'),
                'TaxableAmount' => $item['line_total'],
                'TotalValue' => $item['line_total'],
            ];
        })->all();
    }

    protected function storeShipment(Order $order, ?ShippingMethod $method, ?string $awb, ?string $trackingUrl, array $data, string $status, array $extra, ?string $labelPath = null): void
    {
        OrderShipment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'shipping_method_id' => $method?->id,
                'shipping_id' => $awb,
                'tracking_url' => $trackingUrl,
                'label_path' => $labelPath,
                'shipping_type' => 'Blue Dart',
                'raw_response' => json_encode($data),
                'status' => $status,
                'courier_status' => $status === 'created' ? 'Waybill generated' : null,
                'last_synced_at' => $status === 'created' ? now() : null,
            ]
        );

        if ($awb && $status === 'created' && blank($order->tracking_code)) {
            $order->tracking_code = $awb;
            $order->save();
        }
    }
}
