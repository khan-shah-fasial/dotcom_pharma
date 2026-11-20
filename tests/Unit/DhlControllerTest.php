<?php

namespace Tests\Unit;

use App\Http\Controllers\Shipment\DhlController;
use App\Models\Order;
use App\Models\OrderShipment;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DhlControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['courier_providers.dhl.environment' => 'test']);
    }

    protected function controller(array $settings = []): DhlController
    {
        $settings += [
            'api_key' => 'key', 'api_secret' => 'secret', 'account_number' => '123456789',
            'origin_postal_code' => '400070', 'origin_city' => 'Mumbai', 'origin_country' => 'IN',
            'origin_address' => 'Unit 201, Patel Industrial Estate, Sakinaka',
            'shipper_name' => 'Pharm Vet Easy', 'shipper_phone' => '9000000000', 'shipper_email' => 'ops@example.test',
            'shipper_gstin' => '27ABCDE1234F1Z5', 'shipper_iec' => 'ABCDE1234F', 'currency' => 'INR',
        ];

        return new class($settings) extends DhlController {
            public function __construct(private array $settings) {}

            protected function cfg(string $key, $default = null)
            {
                return array_key_exists($key, $this->settings) ? $this->settings[$key] : $default;
            }

            protected function countryIso($value): string
            {
                return ['india' => 'IN', 'united arab emirates' => 'AE', 'united states' => 'US'][strtolower(trim((string) $value))] ?? (strlen((string) $value) === 2 ? strtoupper($value) : '');
            }

            public function missing(array $payload): array
            {
                return $this->missingFields($payload);
            }

            public function saveDocumentFor(string $awb, string $type, $content): ?string
            {
                return $this->saveDocument($awb, $type, $content);
            }
        };
    }

    /** An order that never touches the database. */
    protected function order(string $country, array $address = []): Order
    {
        $order = new Order();
        $order->forceFill([
            'code' => 'DP-O-26-27-9',
            'grand_total' => 5000,
            'shipping_address' => json_encode($address + [
                'name' => 'Test Buyer', 'address' => '12 Test Street', 'city' => 'Dubai', 'state' => 'Dubai',
                'country' => $country, 'postal_code' => '00000', 'phone' => '+971500000000', 'email' => 'buyer@example.test',
            ]),
        ]);
        $order->setRelation('orderDetails', collect());

        return $order;
    }

    protected function shipment(string $awb): OrderShipment
    {
        $shipment = new class extends OrderShipment {
            public function save(array $options = [])
            {
                return true;
            }
        };
        $shipment->shipping_id = $awb;
        $shipment->status = 'created';

        return $shipment;
    }

    public function test_environment_picks_test_or_production_base(): void
    {
        $this->assertSame('https://express.api.dhl.com/mydhlapi/test', $this->controller()->base());

        config(['courier_providers.dhl.environment' => 'production']);
        $this->assertSame('https://express.api.dhl.com/mydhlapi', $this->controller()->base());
    }

    public function test_domestic_addresses_are_refused_without_calling_dhl(): void
    {
        Http::fake();

        $result = $this->controller()->rates($this->order('India'));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('international', $result['message']);
        Http::assertNothingSent();
    }

    public function test_international_rates_send_iso_country_and_customs_value(): void
    {
        Http::fake(['express.api.dhl.com/mydhlapi/test/rates' => Http::response(['products' => [[
            'productName' => 'EXPRESS WORLDWIDE',
            'productCode' => 'P',
            'totalPrice' => [
                ['currencyType' => 'PULCL', 'priceCurrency' => 'AED', 'price' => 150],
                ['currencyType' => 'BILLC', 'priceCurrency' => 'INR', 'price' => 3420.5],
            ],
            'deliveryCapabilities' => ['estimatedDeliveryDateAndTime' => '2026-11-24T23:59:00'],
        ]]])]);

        $result = $this->controller()->rates($this->order('United Arab Emirates'));

        $this->assertTrue($result['success']);
        $this->assertSame(3420.5, $result['data'][0]['price']);
        $this->assertSame('AE', $result['data'][0]['meta']['destination']);
        Http::assertSent(fn (HttpRequest $r) => $r['customerDetails']['receiverDetails']['countryCode'] === 'AE'
            && $r['isCustomsDeclarable'] === true
            && $r['monetaryAmount'][0]['value'] == 5000
            && $r->hasHeader('Authorization'));
    }

    public function test_rate_errors_show_dhl_message(): void
    {
        Http::fake(['*/rates' => Http::response(['title' => 'Bad request', 'detail' => '420504: The requested product(s) (P) not available'], 400)]);

        $result = $this->controller()->rates($this->order('United States'));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('420504', $result['message']);
    }

    public function test_shipment_payload_has_export_declaration_and_registration_numbers(): void
    {
        $payload = $this->controller()->buildShipmentPayload($this->order('United Arab Emirates'), json_decode($this->order('United Arab Emirates')->shipping_address, true), 'AE');

        $this->assertSame('AE', $payload['customerDetails']['receiverDetails']['postalAddress']['countryCode']);
        $this->assertTrue($payload['content']['isCustomsDeclarable']);
        $this->assertSame('DAP', $payload['content']['incoterm']);
        $this->assertSame('DP-O-26-27-9', $payload['content']['exportDeclaration']['invoice']['number']);
        $this->assertSame(['GST', 'IEC'], array_column($payload['customerDetails']['shipperDetails']['registrationNumbers'], 'typeCode'));
        $this->assertTrue($payload['outputImageProperties']['imageOptions'][1]['isRequested']);
        $this->assertFalse($payload['pickup']['isRequested']);
    }

    public function test_missing_shipper_details_are_named_before_calling_dhl(): void
    {
        $c = $this->controller(['shipper_iec' => '', 'shipper_phone' => '']);
        $order = $this->order('United States');

        $missing = $c->missing($c->buildShipmentPayload($order, json_decode($order->shipping_address, true), 'US'));

        $this->assertContains('shipper IEC (Courier APIs page)', $missing);
        $this->assertContains('shipper phone (Courier APIs page)', $missing);
        $this->assertContains('order items', $missing);
    }

    public function test_label_and_invoice_documents_are_saved(): void
    {
        $c = $this->controller();
        $pdf = base64_encode('%PDF-1.4 dhl');

        $this->assertSame('shipping-labels/dhl/1234567890.pdf', $c->saveDocumentFor('1234567890', 'label', $pdf));
        $this->assertSame('shipping-labels/dhl/1234567890-invoice.pdf', $c->saveDocumentFor('1234567890', 'invoice', $pdf));
        $this->assertNull($c->saveDocumentFor('1234567890', 'label', base64_encode('not a pdf')));
    }

    public function test_tracking_uses_latest_checkpoint(): void
    {
        $c = $this->controller();
        $make = fn (array $events) => ['shipments' => [['shipmentTrackingNumber' => '1234567890', 'events' => $events]]];

        $this->assertSame('manifested', $c->parseTracking($make([]))['stage']);
        $this->assertSame('in_transit', $c->parseTracking($make([['date' => '2026-11-20', 'time' => '10:00:00', 'typeCode' => 'PU', 'description' => 'Shipment picked up']]))['stage']);

        $delivered = $c->parseTracking($make([
            ['date' => '2026-11-22', 'time' => '14:00:00', 'typeCode' => 'OK', 'description' => 'Delivered'],
            ['date' => '2026-11-20', 'time' => '10:00:00', 'typeCode' => 'PU', 'description' => 'Shipment picked up'],
            ['date' => '2026-11-22', 'time' => '08:00:00', 'typeCode' => 'WC', 'description' => 'With delivery courier'],
        ]));
        $this->assertSame('delivered', $delivered['stage']);
        $this->assertSame('Delivered', $delivered['status']);

        $this->assertSame('out_for_delivery', $c->parseTracking($make([['date' => '2026-11-22', 'time' => '08:00:00', 'typeCode' => 'WC', 'description' => 'With delivery courier']]))['stage']);
        $this->assertNull($c->parseTracking(['status' => 404]));
    }

    public function test_track_updates_shipment(): void
    {
        Http::fake(['*/shipments/1234567890/tracking*' => Http::response(['shipments' => [['events' => [
            ['date' => '2026-11-21', 'time' => '09:00:00', 'typeCode' => 'AF', 'description' => 'Arrived at DHL Sort Facility'],
        ]]]])]);

        $shipment = $this->shipment('1234567890');
        $result = $this->controller()->track($shipment);

        $this->assertTrue($result['success']);
        $this->assertSame('Arrived at DHL Sort Facility', $shipment->courier_status);
    }

    public function test_cancel_marks_waybill_cancelled_locally(): void
    {
        Http::fake();
        $shipment = $this->shipment('1234567890');

        $result = $this->controller()->cancel($shipment);

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $shipment->status);
        Http::assertNothingSent();
    }

    public function test_connection_check_quotes_a_foreign_rate(): void
    {
        Http::fakeSequence('*/rates')
            ->push(['title' => 'Unauthorized'], 401)
            ->push(['products' => [['productCode' => 'P', 'totalPrice' => [['currencyType' => 'BILLC', 'price' => 2500]]]]]);

        $bad = $this->controller()->testConnection();
        $this->assertFalse($bad['success']);
        $this->assertSame('TEST', $bad['environment']);
        $this->assertStringContainsString('401', $bad['message']);

        $good = $this->controller(['shipper_iec' => ''])->testConnection();
        $this->assertTrue($good['success']);
        $this->assertStringContainsString('Exporter IEC', $good['message']);
        Http::assertSent(fn (HttpRequest $r) => ($r['customerDetails']['receiverDetails']['countryCode'] ?? null) === 'US');
    }
}
