<?php

namespace Tests\Unit;

use App\Http\Controllers\Shipment\BluedartController;
use App\Models\OrderShipment;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BluedartControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('local');
        config(['courier_providers.bluedart.environment' => 'sandbox']);
    }

    /** Controller with keys and settings in memory instead of .env / business_settings. */
    protected function controller(array $settings = []): BluedartController
    {
        $settings += [
            'client_id' => 'cid', 'client_secret' => 'secret',
            'login_id' => 'LOGIN', 'licence_key' => 'LIC', 'customer_code' => 'CUST',
            'origin_pincode' => '400070', 'product_code' => 'D', 'sub_product_code' => 'P',
            'rate_card' => json_encode([
                'zones' => ['metro' => ['first' => 60, 'additional' => 50]],
                'fuel_pct' => 0, 'cod_flat' => 50, 'cod_pct' => 0, 'gst_pct' => 0,
            ]),
        ];

        return new class($settings) extends BluedartController {
            public function __construct(private array $settings) {}

            protected function cfg(string $key, $default = null)
            {
                return array_key_exists($key, $this->settings) ? $this->settings[$key] : $default;
            }

            public function saveLabelFor(string $awb, $content): ?string
            {
                return $this->saveLabel($awb, $content);
            }
        };
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

    protected function rateRequest(string $pin = '110001', string $paymentType = 'prepaid'): Request
    {
        return Request::create('/', 'GET', [
            'to_pincode' => $pin,
            'payment_type' => $paymentType,
            'package' => ['charged_weight' => '1.20', 'box_length' => '20', 'box_breadth' => '15', 'box_height' => '10'],
        ]);
    }

    protected function fakeLogin(): array
    {
        return ['apigateway-sandbox.bluedart.com/in/transportation/token/v1/login' => Http::response(['JWTToken' => 'jwt-1'])];
    }

    public function test_environment_picks_one_host(): void
    {
        $this->assertSame('https://apigateway-sandbox.bluedart.com', $this->controller()->host());

        config(['courier_providers.bluedart.environment' => 'production']);
        $this->assertSame('https://apigateway.bluedart.com', $this->controller()->host());
    }

    public function test_token_is_fetched_with_client_credentials_and_cached(): void
    {
        Http::fake($this->fakeLogin());
        $c = $this->controller();

        $this->assertSame('jwt-1', $c->token());
        $this->assertSame('jwt-1', $c->token());

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpRequest $r) => $r->hasHeader('ClientID', 'cid') && $r->hasHeader('clientSecret', 'secret'));
    }

    public function test_expired_token_is_renewed_once_on_401(): void
    {
        Http::fakeSequence('apigateway-sandbox.bluedart.com/in/transportation/token/v1/login')
            ->push(['JWTToken' => 'old'])
            ->push(['JWTToken' => 'new']);
        Http::fakeSequence('apigateway-sandbox.bluedart.com/in/transportation/transit/*')
            ->push(['message' => 'Unauthorized'], 401)
            ->push(['GetDomesticTransitTimeForPinCodeandProductResult' => ['IsError' => false, 'ExpectedDateDelivery' => '2026-11-21']]);

        $result = $this->controller()->rates($this->rateRequest());

        $this->assertTrue($result['success']);
        $this->assertSame('2026-11-21', $result['data'][0]['eta']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'transit') && $r->hasHeader('JWTToken', 'new'));
    }

    public function test_rates_price_comes_from_rate_card(): void
    {
        Http::fake($this->fakeLogin() + [
            '*/transit/*' => Http::response(['GetDomesticTransitTimeForPinCodeandProductResult' => ['IsError' => false, 'ExpectedDateDelivery' => '2026-11-21']]),
        ]);

        // Delhi is metro; 1.2 kg = 3 slabs -> 60 + 2 x 50 = 160
        $result = $this->controller()->rates($this->rateRequest('110001'));

        $this->assertTrue($result['success']);
        $this->assertSame(160.0, $result['data'][0]['price']);
        $this->assertSame('metro', $result['data'][0]['meta']['zone']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'transit') && $r['pPinCodeTo'] === '110001' && $r['pSubProductCode'] === 'P');
    }

    public function test_cod_rates_use_cod_sub_product_and_fee(): void
    {
        Http::fake($this->fakeLogin() + ['*/transit/*' => Http::response(['GetDomesticTransitTimeForPinCodeandProductResult' => ['IsError' => false]])]);

        $result = $this->controller()->rates($this->rateRequest('110001', 'cod'));

        $this->assertSame(210.0, $result['data'][0]['price']); // 160 + Rs.50 COD minimum
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'transit') && $r['pSubProductCode'] === 'C');
    }

    public function test_unserviceable_pincode_is_refused(): void
    {
        Http::fake($this->fakeLogin() + [
            '*/transit/*' => Http::response(['GetDomesticTransitTimeForPinCodeandProductResult' => ['IsError' => true, 'ErrorMessage' => 'Invalid Pincode']]),
        ]);

        $result = $this->controller()->rates($this->rateRequest('999999'));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('does not deliver', $result['message']);
    }

    public function test_transit_outage_still_prices_but_missing_rate_card_does_not(): void
    {
        Http::fake($this->fakeLogin() + ['*/transit/*' => Http::response([], 500)]);

        $this->assertTrue($this->controller()->rates($this->rateRequest('110001'))['success']);

        $result = $this->controller()->rates($this->rateRequest('302001')); // rest of India: no rate in card
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('rate card', $result['message']);
    }

    public function test_label_saved_from_base64_or_byte_array(): void
    {
        $pdf = "%PDF-1.4 test label";
        $c = $this->controller();

        $path = $c->saveLabelFor('12345678901', base64_encode($pdf));
        $this->assertSame('shipping-labels/bluedart/12345678901.pdf', $path);
        Storage::disk('local')->assertExists($path);

        $path = $c->saveLabelFor('22222222222', array_map('ord', str_split($pdf)));
        $this->assertSame($pdf, Storage::disk('local')->get($path));

        $this->assertNull($c->saveLabelFor('33333333333', base64_encode('not a pdf')));
    }

    public function test_tracking_status_is_parsed_into_stages(): void
    {
        $c = $this->controller();
        $make = fn ($status, $type) => ['ShipmentData' => ['Shipment' => [['Status' => $status, 'StatusType' => $type]]]];

        $this->assertSame('manifested', $c->parseTracking($make('SHIPMENT DATA RECEIVED', 'UD'))['stage']);
        $this->assertSame('in_transit', $c->parseTracking($make('SHIPMENT PICKED UP', 'PU'))['stage']);
        $this->assertSame('in_transit', $c->parseTracking($make('IN TRANSIT', 'IT'))['stage']);
        $this->assertSame('out_for_delivery', $c->parseTracking($make('SHIPMENT OUT FOR DELIVERY', 'UD'))['stage']);
        $this->assertSame('delivered', $c->parseTracking($make('SHIPMENT DELIVERED', 'DL'))['stage']);
        $this->assertSame('returned', $c->parseTracking($make('RTO DELIVERED', 'RT'))['stage']);
        $this->assertSame('Shipment Picked Up', $c->parseTracking($make('SHIPMENT PICKED UP', 'PU'))['status']);
        $this->assertNull($c->parseTracking(['ShipmentData' => ['Error' => 'Invalid AWB']]));
    }

    public function test_track_updates_shipment_with_licence_key_query(): void
    {
        Http::fake($this->fakeLogin() + [
            '*/tracking/v1*' => Http::response(['ShipmentData' => ['Shipment' => [['Status' => 'IN TRANSIT', 'StatusType' => 'IT']]]]),
        ]);

        $shipment = $this->shipment('12345678901');
        $result = $this->controller()->track($shipment);

        $this->assertTrue($result['success']);
        $this->assertSame('In Transit', $shipment->courier_status);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'tracking/v1') && $r['numbers'] === '12345678901' && $r['lickey'] === 'LIC');
    }

    public function test_cancel_marks_cancelled_only_when_blue_dart_confirms(): void
    {
        Http::fake($this->fakeLogin());
        Http::fakeSequence('*/CancelWaybill')
            ->push(['CancelWaybillResult' => ['IsError' => true, 'Status' => [['StatusCode' => 'Fail', 'StatusInformation' => 'Shipment already picked up']]]])
            ->push(['CancelWaybillResult' => ['IsError' => false, 'Status' => [['StatusCode' => 'Valid', 'StatusInformation' => 'Waybill cancelled']]]]);

        $shipment = $this->shipment('12345678901');

        $first = $this->controller()->cancel($shipment);
        $this->assertFalse($first['success']);
        $this->assertSame('Shipment already picked up', $first['message']);
        $this->assertSame('created', $shipment->status);

        $this->assertTrue($this->controller()->cancel($shipment)['success']);
        $this->assertSame('cancelled', $shipment->status);
    }

    public function test_connection_check_logs_in_and_counts_priced_zones(): void
    {
        Http::fake($this->fakeLogin() + [
            '*/transit/*' => Http::response(['GetDomesticTransitTimeForPinCodeandProductResult' => ['IsError' => false]]),
        ]);

        $result = $this->controller()->testConnection();

        $this->assertTrue($result['success']);
        $this->assertSame('SANDBOX', $result['environment']);
        $this->assertStringContainsString('1 of 5 zones', $result['message']);
    }

    public function test_connection_check_names_missing_keys_and_failed_login(): void
    {
        $this->assertStringContainsString('BLUEDART_LICENCE_KEY', $this->controller(['licence_key' => null])->testConnection()['message']);

        Http::fake(['*/token/v1/login' => Http::response(['message' => 'Unauthorized'], 401)]);
        $result = $this->controller()->testConnection();
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('login failed', $result['message']);
    }
}
