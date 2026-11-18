<?php

namespace App\Http\Controllers\Shipment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Shipment\Concerns\BuildsShipmentPackages;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\OrderDeliveryStatusService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DelhiveryController extends Controller
{
    use BuildsShipmentPackages;

    protected function cfg(string $key, $default = null)
    {
        $setting = function_exists('get_setting') ? get_setting('delhivery_' . $key) : null;

        if ($setting !== null && $setting !== '') {
            return $setting;
        }

        if (!in_array($key, ['api_base_url', 'api_token'], true)) {
            return $default;
        }

        return config("courier_providers.delhivery.{$key}", $default);
    }

    protected function hasCredentials(): bool
    {
        return (bool) (
            $this->cfg('api_token')
            && $this->pickupCfg('name')
        );
    }

    protected function hasRateCredentials(): bool
    {
        return (bool) (
            $this->cfg('api_token')
            && $this->cfg('origin_pincode')
        );
    }

    /**
     * One host for every call so staging and production never mix.
     */
    public function host(): string
    {
        $hosts = (array) config('courier_providers.delhivery.hosts', []);
        $environment = strtolower(trim((string) config('courier_providers.delhivery.environment')));

        if (isset($hosts[$environment])) {
            return rtrim($hosts[$environment], '/');
        }

        $parts = parse_url((string) $this->cfg('api_base_url'));
        if (!empty($parts['host'])) {
            return ($parts['scheme'] ?? 'https') . '://' . $parts['host'];
        }

        return rtrim($hosts['staging'] ?? 'https://staging-express.delhivery.com', '/');
    }

    protected function url(string $path): string
    {
        return $this->host() . '/' . ltrim($path, '/');
    }

    protected function http()
    {
        return Http::withHeaders([
            'Authorization' => 'Token ' . $this->cfg('api_token'),
        ])->acceptJson();
    }

    public function rates($orderOrRequest, array $extra = [])
    {
        Log::info('[Delhivery][Rates] Starting rate lookup');

        if (!$this->hasCredentials()) {
            Log::warning('[Delhivery][Rates] Credentials missing', [
                'host' => $this->host(),
                'api_token_present' => !empty($this->cfg('api_token')),
                'pickup_location_present' => !empty($this->pickupCfg('name')),
                'client_name_present' => !empty($this->cfg('client_name')),
                'pickup_address_present' => !empty($this->pickupCfg('add')),
                'pickup_city_present' => !empty($this->pickupCfg('city')),
                'pickup_pincode_present' => !empty($this->pickupCfg('pin_code')),
                'pickup_phone_present' => !empty($this->pickupCfg('phone')),
            ]);
            return ['success' => false, 'data' => [], 'message' => 'Delhivery credentials are not configured'];
        }

        $context = $this->resolveRateContext($orderOrRequest);
        if (empty($context['to_pincode'])) {
            Log::warning('[Delhivery][Rates] Destination pincode missing');
            return ['success' => false, 'data' => [], 'message' => 'to_pincode or address_id required'];
        }

        $service = $this->serviceability((string) $context['to_pincode']);
        if ($service['checked'] && !$service['serviceable']) {
            return ['success' => false, 'data' => [], 'message' => 'Delhivery does not deliver to pincode ' . $context['to_pincode']];
        }
        if ($service['checked'] && ($context['payment_type'] ?? 'prepaid') === 'cod' && !$service['cod']) {
            return ['success' => false, 'data' => [], 'message' => 'Delhivery cash on delivery is not available for pincode ' . $context['to_pincode']];
        }

        $accountContext = $this->resolveAccountContext($orderOrRequest);

        Log::debug('[Delhivery][Rates] Context resolved', [
            'origin_pincode' => $this->cfg('origin_pincode'),
            'to_pincode' => $context['to_pincode'],
            'payment_type' => $context['payment_type'] ?? null,
            'account_type' => $accountContext['account_type'],
            'user_id' => $accountContext['user_id'],
            'user_subtype' => $accountContext['user_subtype'],
            'package' => $context['package'] ?? null,
            'dimensions' => $this->dimensionLog($context['package'] ?? []),
            'host' => $this->host(),
        ]);

        $rates = $this->getInvoiceRates($context);
        if (!empty($rates)) {
            Log::info('[Delhivery][Rates] Rates returned', [
                'count' => count($rates),
                'providers' => collect($rates)->pluck('name')->all(),
            ]);
            return ['success' => true, 'data' => $rates];
        }

        Log::warning('[Delhivery][Rates] Falling back to default charge', [
            'default_charge' => $this->cfg('default_charge'),
            'origin_pincode_present' => !empty($this->cfg('origin_pincode')),
        ]);

        // A Rs.0 fallback would show free shipping at checkout; only use a real configured charge.
        if ((float) $this->cfg('default_charge', 0) <= 0) {
            return ['success' => false, 'data' => [], 'message' => 'Delhivery rates unavailable'];
        }

        return [
            'success' => true,
            'data' => [$this->fallbackRate($context)],
        ];
    }

    /**
     * Pincode serviceability, cached for 12 hours.
     * 'checked' is false when the API could not be reached, so checkout is not blocked by an outage.
     */
    public function serviceability(string $pincode): array
    {
        $pincode = preg_replace('/\D+/', '', $pincode);
        $unknown = ['checked' => false, 'serviceable' => true, 'prepaid' => true, 'cod' => true, 'pickup' => true, 'remarks' => ''];

        if ($pincode === '' || !$this->cfg('api_token')) {
            return $unknown;
        }

        $cacheKey = 'delhivery:pincode:' . md5($this->host()) . ':' . $pincode;
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = $this->http()->timeout(10)->get($this->url('c/api/pin-codes/json/'), ['filter_codes' => $pincode]);
        } catch (\Throwable $e) {
            Log::warning('[Delhivery][Pincode] Exception', ['pincode' => $pincode, 'error' => $e->getMessage()]);
            return $unknown;
        }

        if (!$response->successful()) {
            Log::warning('[Delhivery][Pincode] API failed', ['pincode' => $pincode, 'status' => $response->status()]);
            return $unknown;
        }

        $result = $this->parseServiceability($response->json() ?: []);
        Cache::put($cacheKey, $result, now()->addHours(12));

        return $result;
    }

    public function parseServiceability(array $json): array
    {
        $code = $json['delivery_codes'][0]['postal_code'] ?? null;

        if (!is_array($code)) {
            return ['checked' => true, 'serviceable' => false, 'prepaid' => false, 'cod' => false, 'pickup' => false, 'remarks' => 'Pincode not serviceable'];
        }

        $yes = fn ($value) => strtoupper((string) $value) === 'Y';
        $remarks = trim((string) ($code['remarks'] ?? ''));
        $embargo = stripos($remarks, 'embargo') !== false;
        $prepaid = $yes($code['pre_paid'] ?? null) && !$embargo;
        $cod = $yes($code['cod'] ?? null) && !$embargo;

        return [
            'checked' => true,
            'serviceable' => $prepaid || $cod,
            'prepaid' => $prepaid,
            'cod' => $cod,
            'pickup' => $yes($code['pickup'] ?? null),
            'remarks' => $remarks,
        ];
    }

    protected function getInvoiceRates(array $context): array
    {
        if (!$this->hasRateCredentials()) {
            Log::warning('[Delhivery][Rates] Rate credentials missing', [
                'api_token_present' => !empty($this->cfg('api_token')),
                'origin_pincode_present' => !empty($this->cfg('origin_pincode')),
            ]);
            return [];
        }

        $rates = [];
        foreach (['E' => 'Express', 'S' => 'Surface'] as $mode => $label) {
            $raw = $this->fetchInvoiceRate($mode, $context);
            $price = $this->extractInvoicePrice($raw);

            if ($price === null) {
                continue;
            }

            $rates[] = [
                'id' => 'delhivery:' . strtolower($label),
                'carrier_id' => $mode,
                'provider' => 'delhivery',
                'name' => 'Delhivery ' . $label,
                'eta' => null,
                'price' => $price,
                'meta' => [
                    'mode' => $mode,
                    'pickup_location' => $this->pickupCfg('name'),
                    'charged_weight' => $context['package']['charged_weight'] ?? null,
                ],
                'raw' => $raw,
            ];
        }

        return $rates;
    }

    protected function fetchInvoiceRate(string $mode, array $context): array
    {
        $weightKg = (float) ($context['package']['charged_weight'] ?? 0.21);
        $weightGrams = max(1, (int) ceil($weightKg * 1000));
        $params = [
            'md' => $mode,
            'cgm' => $weightGrams,
            'o_pin' => (string) $this->cfg('origin_pincode'),
            'd_pin' => (string) $context['to_pincode'],
            'ss' => 'Delivered',
            'pt' => ($context['payment_type'] ?? 'prepaid') === 'cod' ? 'COD' : 'Pre-paid',
        ];

        Log::debug('[Delhivery][Rates][IN]', [
            'endpoint' => $this->url('api/kinko/v1/invoice/charges/.json'),
            'params' => $params,
            'dimensions' => $this->dimensionLog($context['package'] ?? []),
        ]);

        $startedAt = microtime(true);

        try {
            $response = $this->http()->get($this->url('api/kinko/v1/invoice/charges/.json'), $params);

            Log::debug('[Delhivery][Rates][OUT]', [
                'mode' => $mode,
                'status' => $response->status(),
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'body_snippet' => mb_substr($response->body(), 0, 1000),
            ]);

            if (!$response->successful()) {
                Log::warning('[Delhivery][Rates] Invoice API failed', [
                    'mode' => $mode,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 1000),
                ]);
                return [];
            }

            return $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('[Delhivery][Rates] Invoice API exception', [
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    protected function extractInvoicePrice(array $raw): ?float
    {
        $rate = isset($raw[0]) && is_array($raw[0]) ? $raw[0] : $raw;
        $configuredField = (string) $this->cfg('price_field', 'total_amount');

        $price = $rate[$configuredField]
            ?? $rate['total_amount']
            ?? $rate['Total_amount']
            ?? $rate['totalAmount']
            ?? $rate['total_charge']
            ?? $rate['gross_amount']
            ?? $rate['charge_DL']
            ?? $rate['data']['total_amount']
            ?? $rate['data']['Total_amount']
            ?? null;

        Log::debug('[Delhivery][Rates] Price extracted', [
            'configured_field' => $configuredField,
            'price' => $price,
            'available_fields' => array_keys($rate),
        ]);

        return $price === null || $price === '' ? null : (float) $price;
    }

    protected function fallbackRate(array $context): array
    {
        $price = $this->cfg('default_charge');
        $price = $price === null || $price === '' ? 0 : (float) $price;

        return [
                'id' => 'delhivery:standard',
                'carrier_id' => 'standard',
                'provider' => 'delhivery',
                'name' => 'Delhivery',
                'eta' => null,
                'price' => $price,
                'meta' => [
                    'pickup_location' => $this->pickupCfg('name'),
                    'charged_weight' => $context['package']['charged_weight'] ?? null,
                ],
                'raw' => [
                    'fixed_charge' => $price,
                ],
        ];
    }

    public function create(Order $order, array $extra = [])
    {
        $order->loadMissing('user.details');
        $accountContext = $this->resolveAccountContext($order);

        Log::info('[Delhivery][Create] Starting shipment creation', [
            'order_id' => $order->id,
            'order_code' => $order->code,
            'account_type' => $accountContext['account_type'],
            'user_id' => $accountContext['user_id'],
            'user_subtype' => $accountContext['user_subtype'],
        ]);

        $method = ShippingMethod::where('slug', 'delhivery')->first();

        if (!$this->hasCredentials()) {
            $data = ['success' => false, 'message' => 'Delhivery credentials are not configured'];
            Log::warning('[Delhivery][Create] Credentials missing', [
                'host' => $this->host(),
                'api_token_present' => !empty($this->cfg('api_token')),
                'pickup_location_present' => !empty($this->pickupCfg('name')),
                'client_name_present' => !empty($this->cfg('client_name')),
                'pickup_address_present' => !empty($this->pickupCfg('add')),
                'pickup_city_present' => !empty($this->pickupCfg('city')),
                'pickup_pincode_present' => !empty($this->pickupCfg('pin_code')),
                'pickup_phone_present' => !empty($this->pickupCfg('phone')),
            ]);
            $this->storeShipment($order, $method, null, null, $data, 'error', $extra);
            return $data;
        }

        $payload = $this->buildOrderPayload($order, $extra);
        $payloadErrors = $this->validateShipmentPayload($payload);

        if (!empty($payloadErrors)) {
            $data = [
                'success' => false,
                'message' => 'Delhivery shipment payload is missing required fields.',
                'errors' => $payloadErrors,
            ];

            Log::warning('[Delhivery][Create] Payload missing required fields', [
                'order_id' => $order->id,
                'account_type' => $accountContext['account_type'],
                'errors' => $payloadErrors,
            ]);

            $this->storeShipment($order, $method, null, null, $data, 'error', $extra);
            return $data;
        }

        $formPayload = [
            'format' => 'json',
            'data' => json_encode($payload),
        ];

        Log::debug('[Delhivery][Create][IN]', [
            'endpoint' => $this->url('api/cmu/create.json'),
            'pickup_location' => $payload['pickup_location']['name'] ?? null,
            'pickup_pincode' => $this->pickupCfg('pin_code'),
            'client_name' => $payload['shipments'][0]['client'] ?? null,
            'pickup_payload' => $payload['pickup_location'] ?? [],
            'client_name_present' => !empty($payload['shipments'][0]['client'] ?? null),
            'shipment_count' => count($payload['shipments'] ?? []),
            'order' => $payload['shipments'][0]['order'] ?? null,
            'pin' => $payload['shipments'][0]['pin'] ?? null,
            'payment_mode' => $payload['shipments'][0]['payment_mode'] ?? null,
            'shipping_mode' => $payload['shipments'][0]['shipping_mode'] ?? null,
            'checkout_payment_type' => $order->payment_type,
            'checkout_payment_type_normalized' => str_replace(['_', ' '], '-', strtolower(trim((string) $order->payment_type))),
            'account_type' => $payload['shipments'][0]['shipment_mode'] ?? 'B2C',
            'consignee_gst_present' => !empty($payload['shipments'][0]['consignee_gst_tin'] ?? null),
            'seller_gst_present' => !empty($payload['shipments'][0]['seller_gst_tin'] ?? null),
            'client_gst_present' => !empty($payload['shipments'][0]['client_gst_tin'] ?? null),
            'hsn_code_present' => !empty($payload['shipments'][0]['hsn_code'] ?? null),
            'ewaybill_present' => !empty($payload['shipments'][0]['ewaybill'] ?? null) || !empty($payload['shipments'][0]['ewbn'] ?? null),
            'weight' => $payload['shipments'][0]['weight'] ?? null,
            'shipment_length' => $payload['shipments'][0]['shipment_length'] ?? null,
            'shipment_width' => $payload['shipments'][0]['shipment_width'] ?? null,
            'shipment_height' => $payload['shipments'][0]['shipment_height'] ?? null,
            'dimensions' => [
                'manifest_weight_grams' => $payload['shipments'][0]['weight'] ?? null,
                'manifest_weight_kg' => isset($payload['shipments'][0]['weight'])
                    ? ((float) $payload['shipments'][0]['weight']) / 1000
                    : null,
                'weight_grams_for_rates' => isset($payload['shipments'][0]['weight'])
                    ? (int) $payload['shipments'][0]['weight']
                    : null,
                'length_cm' => $payload['shipments'][0]['shipment_length'] ?? null,
                'width_cm' => $payload['shipments'][0]['shipment_width'] ?? null,
                'height_cm' => $payload['shipments'][0]['shipment_height'] ?? null,
            ],
        ]);

        Log::debug('[Delhivery][Create][PAYLOAD_KEYS]', [
            'root_keys' => array_keys($payload),
            'shipment_keys' => array_keys($payload['shipments'][0] ?? []),
            'pickup_keys' => array_keys($payload['pickup_location'] ?? []),
            'client_name' => $payload['shipments'][0]['client'] ?? null,
            'pickup_location_name' => $payload['pickup_location']['name'] ?? null,
        ]);

        Log::debug('[Delhivery][Create][FORM_PAYLOAD]', [
            'format' => $formPayload['format'],
            'data' => $this->redactedPayloadForLog($payload),
        ]);

        $startedAt = microtime(true);
        try {
            $response = $this->http()->asForm()->post($this->url('api/cmu/create.json'), $formPayload);
            $data = $response->json() ?: ['raw' => $response->body()];
            $data = $this->normalizeApiFailure($data);

            Log::debug('[Delhivery][Create][OUT]', [
                'status' => $response->status(),
                'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'body_snippet' => mb_substr($response->body(), 0, 1000),
            ]);

            if (!$response->successful()) {
                Log::warning('[Delhivery][Create] API failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 1000),
                ]);
            }

            if (!empty($data['error'])) {
                Log::warning('[Delhivery][Create] API returned error', [
                    'status' => $response->status(),
                    'message' => $data['message'] ?? null,
                    'remark' => $data['rmk'] ?? null,
                    'integration_hint' => $data['integration_hint'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[Delhivery][Create] Exception', ['error' => $e->getMessage()]);
            $data = ['success' => false, 'message' => $e->getMessage()];
        }

        $waybill = $this->extractWaybill($data);
        $trackingUrl = $waybill ? 'https://www.delhivery.com/track/package/' . urlencode($waybill) : null;
        $success = $this->isSuccessfulResponse($data, $waybill);

        Log::info('[Delhivery][Create] Parsed response', [
            'waybill' => $waybill,
            'tracking_url' => $trackingUrl,
            'success' => $success,
        ]);

        $this->storeShipment($order, $method, $waybill, $trackingUrl, $data, $success ? 'created' : 'error', $extra);

        return $data;
    }

    protected function buildOrderPayload(Order $order, array $extra = []): array
    {
        $addr = json_decode($order->shipping_address ?? '{}', true) ?: [];
        $package = $this->buildPackageFromOrder($order);
        $paymentMode = $this->shipmentPaymentMode($order);
        $grandTotal = (float) $order->grand_total;
        $isBusiness = $this->isBusinessUser($order->user);
        $businessFields = $isBusiness ? $this->businessShipmentFields($order) : [];
        $shipmentOrderCode = $this->shipmentOrderCode($order);
        $defaultHsnCode = $this->cleanValue($this->cfg('default_hsn_code'));
        $ewaybillNumber = $this->resolveEwaybillNumber($order, $extra);

        $shipment = array_merge([
            'order' => $shipmentOrderCode,
            'name' => $this->cleanValue($addr['name'] ?? ''),
            'add' => $this->cleanValue($addr['address'] ?? ''),
            'city' => $this->cleanValue($addr['city'] ?? ''),
            'state' => $this->cleanValue($addr['state'] ?? ''),
            'country' => $this->cleanValue($addr['country'] ?? 'India'),
            'pin' => (string) ($addr['postal_code'] ?? $addr['zipcode'] ?? ''),
            'phone' => (string) ($addr['phone'] ?? ''),
            'address_type' => $this->cleanValue($this->cfg('address_type', 'home')),
            'payment_mode' => $paymentMode,
            'total_amount' => $grandTotal,
            'cod_amount' => $paymentMode === 'COD' ? $grandTotal : 0,
            'quantity' => $this->totalQuantity($order),
            'weight' => $this->manifestWeightGrams($package),
            'shipment_width' => (float) ($package['box_breadth'] ?? 10),
            'shipment_height' => (float) ($package['box_height'] ?? 10),
            'shipment_length' => (float) ($package['box_length'] ?? 10),
            'products_desc' => $this->cleanValue($this->productsDescription($order)),
            'hsn_code' => $defaultHsnCode,
            'shipping_mode' => $this->cleanValue($this->cfg('shipping_mode', 'Express')),
            'return_pin' => '',
            'return_city' => '',
            'return_phone' => '',
            'return_add' => '',
            'return_address' => '',
            'return_state' => '',
            'return_country' => '',
            'return_name' => '',
            'seller_add' => $this->cleanValue($this->cfg('seller_address', '')),
            'seller_name' => $this->cleanValue($this->cfg('seller_name', '')),
            'seller_inv' => $shipmentOrderCode,
            'ewaybill' => $ewaybillNumber,
            'ewbn' => $ewaybillNumber,
            'waybill' => '',
            'order_date' => null,
            'fragile_shipment' => false,
            'dangerous_good' => false,
            'plastic_packaging' => false,
        ], $businessFields);

        $clientName = $this->cleanValue($this->cfg('client_name'));
        if ($clientName !== '' && $this->shouldSendClientName()) {
            $shipment['client'] = $clientName;
        }

        if ($isBusiness) {
            $shipment['shipment_mode'] = 'B2B';
        }

        $pickupLocation = [
            'name' => $this->cleanValue($this->pickupCfg('name')),
        ];

        return [
            'shipments' => [$shipment],
            'pickup_location' => $pickupLocation,
        ];
    }

    protected function resolveAccountContext($orderOrRequest): array
    {
        $user = $this->resolveUser($orderOrRequest);
        $isBusiness = $this->isBusinessUser($user);

        return [
            'account_type' => $isBusiness ? 'B2B' : 'B2C',
            'is_business' => $isBusiness,
            'user_id' => $user?->id,
            'user_type' => $user?->user_type,
            'user_subtype' => $user?->user_subtype,
        ];
    }

    protected function resolvePickupLocation(): array
    {
        $pickup = $this->latestActivePickupLocation();

        return [
            'name' => $this->cleanValue($pickup['name'] ?? ''),
            'add' => $this->cleanValue($pickup['add'] ?? $pickup['address'] ?? ''),
            'city' => $this->cleanValue($pickup['city'] ?? ''),
            'pin_code' => !empty($pickup['pin_code'] ?? $pickup['pincode'] ?? $pickup['pin'] ?? null)
                ? (int) ($pickup['pin_code'] ?? $pickup['pincode'] ?? $pickup['pin'])
                : null,
            'country' => $this->cleanValue($pickup['country'] ?? 'India'),
            'phone' => (string) ($pickup['phone'] ?? ''),
        ];
    }

    protected function pickupCfg(string $key)
    {
        return $this->resolvePickupLocation()[$key] ?? null;
    }

    protected function latestActivePickupLocation(): array
    {
        $raw = function_exists('get_setting') ? get_setting('delhivery_pickup_locations') : null;
        $locations = $raw ? json_decode($raw, true) : null;

        if (!is_array($locations)) {
            return [];
        }

        if (isset($locations['name'])) {
            $locations = [$locations];
        }

        $activeLocations = collect($locations)
            ->filter(function ($location) {
                $active = $location['is_active'] ?? $location['active'] ?? $location['status'] ?? true;
                return $active === true || $active === 1 || $active === '1' || strtolower((string) $active) === 'active';
            })
            ->sortByDesc(function ($location) {
                return $location['created_at'] ?? $location['updated_at'] ?? $location['id'] ?? 0;
            })
            ->values();

        return $activeLocations->first() ?: [];
    }

    protected function resolveUser($orderOrRequest): ?User
    {
        if ($orderOrRequest instanceof Order) {
            return $orderOrRequest->user;
        }

        return auth()->user();
    }

    protected function isBusinessUser(?User $user): bool
    {
        if (!$user || (string) $user->user_type !== 'customer') {
            return false;
        }

        $subtype = strtolower(trim((string) $user->user_subtype));

        return $subtype !== '' && $subtype !== 'customer';
    }

    protected function taxShipmentFields(): array
    {
        return [
            'seller_gst_tin' => $this->cleanValue($this->cfg('seller_gst_tin')),
            'client_gst_tin' => $this->cleanValue($this->cfg('client_gst_tin')),
        ];
    }

    protected function businessShipmentFields(Order $order): array
    {
        $user = $order->user;
        $details = $user?->details;
        $consigneeGst = $this->cleanValue($user?->gst_no ?: $details?->gst_no);

        return [
            'consignee_gst_tin' => $consigneeGst,
            'invoice_reference' => $this->shipmentOrderCode($order),
        ];
    }

    protected function shipmentPaymentMode(Order $order): string
    {
        // Default follows the order: COD orders must be booked as COD or the cash is never collected.
        $configuredMode = trim((string) $this->cfg('shipment_payment_mode', 'match-order'));
        $normalizedMode = strtolower(str_replace(['_', ' '], '-', $configuredMode));

        if (in_array($normalizedMode, ['cod', 'cash-on-delivery'], true)) {
            return 'COD';
        }

        if (in_array($normalizedMode, ['match-order', 'order'], true)) {
            $orderPaymentType = strtolower(trim((string) $order->payment_type));
            $normalizedOrderPaymentType = str_replace(['_', ' '], '-', $orderPaymentType);

            return in_array($normalizedOrderPaymentType, ['cash-on-delivery', 'cod'], true)
                ? 'COD'
                : 'Prepaid';
        }

        if (in_array($normalizedMode, ['prepaid', 'pre-paid'], true)) {
            return 'Prepaid';
        }

        return $configuredMode !== '' ? $configuredMode : 'Prepaid';
    }

    protected function manifestWeightGrams(array $package): int
    {
        $weightKg = (float) ($package['charged_weight'] ?? 0.21);

        return max(1, (int) ceil($weightKg * 1000));
    }

    protected function shipmentOrderCode(Order $order): string
    {
        if ($this->cfg('order_reference_mode', 'id') === 'code') {
            return preg_replace('/\s+/', '', (string) $order->code) ?: (string) $order->id;
        }

        return 'DP' . (string) $order->id;
    }

    protected function shouldSendClientName(): bool
    {
        $value = $this->cfg('send_client_name', false);

        return $value === true || $value === 1 || $value === '1' || strtolower((string) $value) === 'true';
    }

    protected function validateShipmentPayload(array $payload): array
    {
        $shipment = $payload['shipments'][0] ?? [];
        $errors = [];

        foreach ([
            'name' => 'consignee name',
            'order' => 'order ID',
            'phone' => 'consignee phone number',
            'add' => 'consignee address',
            'pin' => 'consignee pincode',
            'payment_mode' => 'payment mode',
        ] as $field => $label) {
            if (empty($shipment[$field])) {
                $errors[$field] = $label . ' is required for Delhivery shipment creation.';
            }
        }

        if (empty($payload['pickup_location']['name'])) {
            $errors['pickup_location.name'] = 'pickup location name is required for Delhivery shipment creation.';
        }

        $totalAmount = (float) ($shipment['total_amount'] ?? 0);
        if ($totalAmount > 50000 && empty($shipment['ewaybill']) && empty($shipment['ewbn'])) {
            $errors['ewaybill'] = 'E-waybill number is required for Delhivery shipments above Rs.50,000.';
        }

        return $errors;
    }

    protected function resolveEwaybillNumber(Order $order, array $extra = []): string
    {
        foreach ([
            $extra['ewaybill'] ?? null,
            $extra['ewbn'] ?? null,
            $extra['eway_bill'] ?? null,
            $order->ewaybill ?? null,
            $order->ewbn ?? null,
            $order->eway_bill ?? null,
            $order->eway_bill_no ?? null,
            $order->ewaybill_no ?? null,
        ] as $candidate) {
            $candidate = $this->cleanValue($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    protected function normalizeApiFailure(array $data): array
    {
        $remark = (string) ($data['rmk'] ?? $data['remark'] ?? $data['message'] ?? '');

        if (!empty($data['error']) && stripos($remark, "'NoneType' object has no attribute 'end_date'") !== false) {
            $data['message'] = 'Delhivery rejected the shipment because the configured staging client/pickup contract could not be resolved. Confirm the API token, exact client name, exact pickup location, and active contract date in Delhivery One.';
            $data['integration_hint'] = 'This is Delhivery API internal contract metadata failure, not a Laravel exception.';
        }

        if (!empty($data['error']) && stripos($remark, 'shipment list contains no data') !== false) {
            $data['message'] = 'Delhivery could not read any valid shipments from the create request. Confirm that the API token, client name, and pickup location all belong to the same Delhivery environment.';
            $data['integration_hint'] = 'On the live track.delhivery.com endpoint, staging token/client values will not work. Delhivery also requires the client field to exactly match the client name shared with the live credentials.';
        }

        return $data;
    }

    protected function redactedPayloadForLog(array $payload): string
    {
        $redacted = $payload;

        foreach ($redacted['shipments'] ?? [] as $index => $shipment) {
            foreach (['phone', 'seller_gst_tin', 'client_gst_tin', 'consignee_gst_tin'] as $field) {
                if (!empty($redacted['shipments'][$index][$field])) {
                    $redacted['shipments'][$index][$field] = '[redacted]';
                }
            }
        }

        if (!empty($redacted['pickup_location']['phone'])) {
            $redacted['pickup_location']['phone'] = '[redacted]';
        }

        return json_encode($redacted, JSON_UNESCAPED_SLASHES);
    }

    protected function productsDescription(Order $order): string
    {
        return collect($this->orderItems($order))
            ->map(function ($item) {
                return $item['name'] . ' x ' . $item['quantity'];
            })
            ->implode(', ');
    }

    protected function totalQuantity(Order $order): int
    {
        return max(1, (int) $order->orderDetails->sum('quantity'));
    }

    protected function dimensionLog(array $package): array
    {
        $weightKg = (float) ($package['charged_weight'] ?? $package['total_physical_weight'] ?? 0);

        return [
            'weight_kg' => $weightKg,
            'weight_grams_for_rates' => $weightKg > 0 ? (int) ceil($weightKg * 1000) : null,
            'length_cm' => isset($package['box_length']) ? (float) $package['box_length'] : null,
            'width_cm' => isset($package['box_breadth']) ? (float) $package['box_breadth'] : null,
            'height_cm' => isset($package['box_height']) ? (float) $package['box_height'] : null,
            'volumetric_weight_kg' => isset($package['volumetric_weight']) ? (float) $package['volumetric_weight'] : null,
        ];
    }

    protected function cleanValue($value): string
    {
        $value = trim((string) $value);
        return preg_replace('/[&#%;\\\\]+/', ' ', $value) ?: '';
    }

    protected function extractWaybill(array $data): ?string
    {
        $candidates = [
            $data['waybill'] ?? null,
            $data['awb'] ?? null,
            $data['packages'][0]['waybill'] ?? null,
            $data['packages'][0]['awb'] ?? null,
            $data['ShipmentData'][0]['Shipment']['AWB'] ?? null,
            $data['ShipmentData'][0]['Shipment']['waybill'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!empty($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }

    protected function isSuccessfulResponse(array $data, ?string $waybill): bool
    {
        if ($waybill) {
            return true;
        }

        $success = $data['success'] ?? $data['status'] ?? null;
        return $success === true || $success === 'true' || $success === 'success';
    }

    /**
     * Download the shipping label PDF and keep a copy in storage/app/shipping-labels.
     */
    public function label(OrderShipment $shipment): array
    {
        $waybill = (string) $shipment->shipping_id;
        if ($waybill === '') {
            return ['success' => false, 'message' => 'This shipment has no waybill yet.'];
        }

        if ($shipment->label_path && Storage::disk('local')->exists($shipment->label_path)) {
            return ['success' => true, 'path' => $shipment->label_path];
        }

        try {
            $response = $this->http()->timeout(20)->get($this->url('api/p/packing_slip'), ['wbns' => $waybill, 'pdf' => 'true']);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('[Delhivery][Label] Exception', ['waybill' => $waybill, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not reach Delhivery for the label.'];
        }

        $link = $json['packages'][0]['pdf_download_link'] ?? null;
        if (!$response->successful() || !$link) {
            Log::warning('[Delhivery][Label] No PDF link', ['waybill' => $waybill, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            return ['success' => false, 'message' => 'Delhivery did not return a label for waybill ' . $waybill . '.'];
        }

        try {
            $pdf = Http::timeout(30)->get($link);
        } catch (\Throwable $e) {
            Log::error('[Delhivery][Label] Download exception', ['waybill' => $waybill, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not download the label PDF.'];
        }

        if (!$pdf->successful() || $pdf->body() === '') {
            return ['success' => false, 'message' => 'Could not download the label PDF.'];
        }

        $path = 'shipping-labels/delhivery/' . preg_replace('/[^A-Za-z0-9_-]/', '', $waybill) . '.pdf';
        Storage::disk('local')->put($path, $pdf->body());
        $shipment->label_path = $path;
        $shipment->save();

        return ['success' => true, 'path' => $path];
    }

    /**
     * Fetch the latest courier status and store it on the shipment.
     */
    public function track(OrderShipment $shipment): array
    {
        $waybill = (string) $shipment->shipping_id;
        if ($waybill === '') {
            return ['success' => false, 'message' => 'This shipment has no waybill yet.'];
        }

        try {
            $response = $this->http()->timeout(15)->get($this->url('api/v1/packages/json/'), ['waybill' => $waybill]);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('[Delhivery][Track] Exception', ['waybill' => $waybill, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not reach Delhivery for tracking.'];
        }

        $tracking = $this->parseTracking($json);
        if (!$response->successful() || !$tracking) {
            Log::warning('[Delhivery][Track] No tracking data', ['waybill' => $waybill, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            return ['success' => false, 'message' => 'Delhivery returned no tracking for waybill ' . $waybill . '.'];
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
                Log::error('[Delhivery][Track] Order status update failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        return ['success' => true, 'order_status' => $orderStatus] + $tracking;
    }

    /**
     * stage is one of: manifested, in_transit, out_for_delivery, delivered, returned, cancelled, unknown.
     */
    public function parseTracking(array $json): ?array
    {
        $data = $json['ShipmentData'][0]['Shipment'] ?? null;
        if (!is_array($data)) {
            return null;
        }

        $statusBlock = $data['Status'] ?? [];
        $status = trim((string) ($statusBlock['Status'] ?? ''));
        $type = strtoupper(trim((string) ($statusBlock['StatusType'] ?? '')));
        $lower = strtolower($status);

        $stage = match (true) {
            $type === 'DL' && str_contains($lower, 'rto') => 'returned',
            $type === 'DL' => 'delivered',
            $type === 'RT' => 'returned',
            $type === 'CN' || str_contains($lower, 'cancel') => 'cancelled',
            str_contains($lower, 'dispatched') || str_contains($lower, 'out for delivery') => 'out_for_delivery',
            in_array($lower, ['manifested', 'not picked', 'open', 'scheduled'], true) => 'manifested',
            in_array($type, ['UD', 'PU', 'PP'], true) => 'in_transit',
            default => 'unknown',
        };

        return [
            'status' => $status !== '' ? $status : 'Unknown',
            'status_type' => $type,
            'stage' => $stage,
            'location' => (string) ($statusBlock['StatusLocation'] ?? ''),
            'updated_at' => (string) ($statusBlock['StatusDateTime'] ?? ''),
            'instructions' => (string) ($statusBlock['Instructions'] ?? ''),
            'expected_delivery' => (string) ($data['ExpectedDeliveryDate'] ?? $data['PromisedDeliveryDate'] ?? ''),
        ];
    }

    /**
     * Cancel the waybill with Delhivery. Only possible before it is picked up.
     */
    public function cancel(OrderShipment $shipment): array
    {
        $waybill = (string) $shipment->shipping_id;
        if ($waybill === '') {
            return ['success' => false, 'message' => 'This shipment has no waybill to cancel.'];
        }

        try {
            $response = $this->http()->asJson()->timeout(15)->post($this->url('api/p/edit'), [
                'waybill' => $waybill,
                'cancellation' => 'true',
            ]);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('[Delhivery][Cancel] Exception', ['waybill' => $waybill, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not reach Delhivery to cancel.'];
        }

        $flag = $json['status'] ?? null;
        $ok = $response->successful() && ($flag === true || in_array(strtolower((string) $flag), ['true', 'success'], true));
        $remark = (string) ($json['remark'] ?? $json['error'] ?? $json['message'] ?? '');

        Log::info('[Delhivery][Cancel] Response', ['waybill' => $waybill, 'ok' => $ok, 'remark' => $remark]);

        if (!$ok) {
            return ['success' => false, 'message' => $remark !== '' ? $remark : 'Delhivery did not cancel waybill ' . $waybill . '.'];
        }

        $shipment->status = 'cancelled';
        $shipment->courier_status = 'Cancelled';
        $shipment->last_synced_at = now();
        $shipment->save();

        return ['success' => true, 'message' => $remark !== '' ? $remark : 'Shipment cancelled.'];
    }

    /**
     * Ask Delhivery to collect packages from the active pickup location.
     */
    public function requestPickup(string $date, string $time, int $packageCount): array
    {
        $location = $this->pickupCfg('name');
        if (!$this->cfg('api_token') || !$location) {
            return ['success' => false, 'message' => 'Delhivery token or pickup location is not configured.'];
        }

        $payload = [
            'pickup_location' => $location,
            'pickup_date' => $date,
            'pickup_time' => strlen($time) === 5 ? $time . ':00' : $time,
            'expected_package_count' => max(1, $packageCount),
        ];

        try {
            $response = $this->http()->asJson()->timeout(15)->post($this->url('fm/request/new/'), $payload);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('[Delhivery][Pickup] Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not reach Delhivery to book the pickup.'];
        }

        Log::info('[Delhivery][Pickup] Response', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);

        $pickupId = $json['pickup_id'] ?? $json['pickupId'] ?? null;
        if (!$response->successful() || !$pickupId) {
            $error = $json['error']['message'] ?? $json['error'] ?? $json['message'] ?? null;
            $error = is_array($error) ? json_encode($error) : $error;

            return ['success' => false, 'message' => $error ? (string) $error : 'Delhivery did not confirm the pickup.'];
        }

        return [
            'success' => true,
            'message' => 'Pickup booked. Pickup ID ' . $pickupId . '.',
            'pickup_id' => $pickupId,
        ];
    }

    protected function storeShipment(Order $order, ?ShippingMethod $method, ?string $waybill, ?string $trackingUrl, array $data, string $status, array $extra): void
    {
        OrderShipment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'shipping_method_id' => $method?->id,
                'shipping_id' => $waybill,
                'tracking_url' => $trackingUrl,
                'shipping_type' => $extra['shipping_type'] ?? null,
                'raw_response' => json_encode($data),
                'status' => $status,
            ]
        );

        if ($waybill && $status === 'created' && blank($order->tracking_code)) {
            $order->tracking_code = $waybill;
            $order->save();
        }

        Log::info('[Delhivery][Create] Shipment record updated', [
            'order_id' => $order->id,
            'shipping_method_id' => $method?->id,
            'shipping_id' => $waybill,
            'status' => $status,
        ]);
    }
}
