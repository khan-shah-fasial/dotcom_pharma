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

class BluedartController extends Controller
{
    use BuildsShipmentPackages;

    protected function cfg(string $key, $default = null)
    {
        return config("courier_providers.bluedart.{$key}", $default);
    }

    protected function hasCredentials(): bool
    {
        return (bool) (
            $this->cfg('jwt_token')
        ) && $this->cfg('login_id') && $this->cfg('licence_key') && $this->cfg('customer_code');
    }

    protected function endpoint(string $path): string
    {
        return rtrim((string) $this->cfg('api_base_url'), '/') . '/' . ltrim($path, '/');
    }

    protected function jwtToken(): ?string
    {
        return $this->cfg('jwt_token');
    }

    protected function http()
    {
        $token = $this->jwtToken();

        return Http::withHeaders([
            'JWTToken' => $token,
            'Content-Type' => 'application/json',
        ])->acceptJson();
    }

    protected function profile(): array
    {
        return [
            'Api_type' => 'S',
            'LicenceKey' => $this->cfg('licence_key'),
            'LoginID' => $this->cfg('login_id'),
        ];
    }

    public function rates($orderOrRequest, array $extra = [])
    {
        if (!$this->hasCredentials() || !$this->cfg('origin_pincode')) {
            return ['success' => false, 'data' => [], 'message' => 'Blue Dart credentials are not configured'];
        }

        $context = $this->resolveRateContext($orderOrRequest);
        if (empty($context['to_pincode'])) {
            return ['success' => false, 'data' => [], 'message' => 'to_pincode or address_id required'];
        }

        $productCode = (string) $this->cfg('product_code', 'D');
        $payload = [
            'pinCode' => (string) $this->cfg('origin_pincode'),
            'pinCodeTo' => (string) $context['to_pincode'],
            'pProductCode' => $productCode,
            'pSubProductCode' => (string) $this->cfg('sub_product_code', ''),
            'pPudate' => '/Date(' . (now()->timestamp * 1000) . ')/',
            'pPickupTime' => '1600',
            'profile' => $this->profile(),
        ];

        $raw = [];
        try {
            $response = $this->http()->post($this->endpoint('/in/transportation/time-finder/v1'), $payload);
            $raw = $response->json() ?: [];

            if (!$response->successful()) {
                Log::warning('[BlueDart][Rates] Transit API failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 1000),
                ]);
                return ['success' => false, 'data' => [], 'message' => 'Blue Dart rates unavailable'];
            }
        } catch (\Throwable $e) {
            Log::error('[BlueDart][Rates] Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'data' => [], 'message' => 'Blue Dart rates unavailable'];
        }

        $price = $this->cfg('default_rate') !== null ? (float) $this->cfg('default_rate') : null;

        return [
            'success' => true,
            'data' => [[
                'id' => 'bluedart:' . $productCode,
                'carrier_id' => $productCode,
                'provider' => 'bluedart',
                'name' => 'Blue Dart',
                'eta' => $raw['ExpectedDateDelivery'] ?? $raw['expectedDateDelivery'] ?? $raw['TransitTime'] ?? null,
                'price' => $price,
                'meta' => [
                    'product_code' => $productCode,
                    'sub_product_code' => $this->cfg('sub_product_code', ''),
                    'charged_weight' => $context['package']['charged_weight'] ?? null,
                ],
                'raw' => $raw,
            ]],
        ];
    }

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
                    'ActualWeight' => $package['charged_weight'],
                    'Commodity' => [],
                    'CreditReferenceNo' => (string) $order->code,
                    'Dimensions' => [[
                        'Breadth' => $package['box_breadth'],
                        'Count' => 1,
                        'Height' => $package['box_height'],
                        'Length' => $package['box_length'],
                    ]],
                    'PDFOutputNotRequired' => true,
                    'PickupDate' => '/Date(' . (now()->timestamp * 1000) . ')/',
                    'PickupTime' => '1600',
                    'PieceCount' => '1',
                    'ProductCode' => $productCode,
                    'ProductType' => 0,
                    'RegisterPickup' => false,
                    'SubProductCode' => (string) $this->cfg('sub_product_code', ''),
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
            $response = $this->http()->post($this->endpoint('/in/transportation/waybill/v1/GenerateWayBill'), $payload);
            $data = $response->json() ?: ['raw' => $response->body()];
        } catch (\Throwable $e) {
            Log::error('[BlueDart][Create] Exception', ['error' => $e->getMessage()]);
            $data = ['success' => false, 'message' => $e->getMessage()];
        }

        $awb = $data['GenerateWayBillResult']['AWBNo']
            ?? $data['AWBNo']
            ?? $data['awb']
            ?? $data['waybill']
            ?? null;
        $trackingUrl = $awb ? 'https://www.bluedart.com/web/guest/trackdartresult?trackFor=0&trackNo=' . urlencode($awb) : null;
        $success = !empty($awb) && empty($data['error']);

        $this->storeShipment($order, $method, $awb, $trackingUrl, $data, $success ? 'created' : 'error', $extra);

        return $data;
    }

    protected function itemDetails(Order $order): array
    {
        return collect($this->orderItems($order))->map(function ($item) {
            return [
                'CGSTAmount' => 0,
                'HSCode' => '',
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
                'TaxableAmount' => $item['price'],
                'TotalValue' => $item['price'] * $item['quantity'],
            ];
        })->all();
    }

    protected function storeShipment(Order $order, ?ShippingMethod $method, ?string $awb, ?string $trackingUrl, array $data, string $status, array $extra): void
    {
        OrderShipment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'shipping_method_id' => $method?->id,
                'shipping_id' => $awb,
                'tracking_url' => $trackingUrl,
                'shipping_type' => $extra['shipping_type'] ?? null,
                'raw_response' => json_encode($data),
                'status' => $status,
            ]
        );
    }
}
