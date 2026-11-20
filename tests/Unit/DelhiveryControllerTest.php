<?php

namespace Tests\Unit;

use App\Http\Controllers\Shipment\DelhiveryController;
use App\Models\OrderShipment;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DelhiveryControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'courier_providers.delhivery.environment' => 'staging',
            'courier_providers.delhivery.api_token' => 'test-token',
        ]);
    }

    /** Controller with settings supplied in memory instead of business_settings. */
    protected function controller(array $settings = []): DelhiveryController
    {
        return new class($settings) extends DelhiveryController {
            public function __construct(private array $settings) {}

            protected function cfg(string $key, $default = null)
            {
                return $this->settings[$key] ?? config("courier_providers.delhivery.{$key}", $default);
            }

            protected function pickupCfg(string $key)
            {
                return ['name' => 'Ware House-1-Patel Estate'][$key] ?? null;
            }
        };
    }

    /** Shipment that never touches the database. */
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

    protected function rateRequest(string $paymentType = 'prepaid'): Request
    {
        return Request::create('/', 'GET', [
            'to_pincode' => '110001',
            'payment_type' => $paymentType,
            'package' => ['charged_weight' => '0.50', 'box_length' => '10', 'box_breadth' => '10', 'box_height' => '10'],
        ]);
    }

    public function test_environment_picks_one_host_for_all_calls(): void
    {
        $this->assertSame('https://staging-express.delhivery.com', $this->controller()->host());

        config(['courier_providers.delhivery.environment' => 'production']);
        $this->assertSame('https://track.delhivery.com', $this->controller()->host());

        config(['courier_providers.delhivery.environment' => null]);
        $this->assertSame(
            'https://track.delhivery.com',
            $this->controller(['api_base_url' => 'https://track.delhivery.com/api/cmu/create.json'])->host()
        );
    }

    public function test_rates_use_staging_host_and_send_payment_type(): void
    {
        Http::fake([
            'staging-express.delhivery.com/c/api/pin-codes/*' => Http::response(['delivery_codes' => [['postal_code' => ['pre_paid' => 'Y', 'cod' => 'Y', 'pickup' => 'Y', 'remarks' => '']]]]),
            'staging-express.delhivery.com/api/kinko/*' => Http::response([['total_amount' => 85.5]]),
        ]);

        $result = $this->controller(['origin_pincode' => '400072'])->rates($this->rateRequest('cod'));

        $this->assertTrue($result['success']);
        $this->assertSame(85.5, $result['data'][0]['price']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'kinko') && $r['pt'] === 'COD' && $r['cgm'] === 500);
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'track.delhivery.com'));
    }

    public function test_rates_refuse_unserviceable_pincode(): void
    {
        Http::fake(['*/c/api/pin-codes/*' => Http::response(['delivery_codes' => []])]);

        $result = $this->controller(['origin_pincode' => '400072'])->rates($this->rateRequest());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('does not deliver', $result['message']);
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'kinko'));
    }

    public function test_rates_refuse_cod_where_only_prepaid_is_available(): void
    {
        Http::fake(['*/c/api/pin-codes/*' => Http::response(['delivery_codes' => [['postal_code' => ['pre_paid' => 'Y', 'cod' => 'N']]]])]);

        $result = $this->controller(['origin_pincode' => '400072'])->rates($this->rateRequest('cod'));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cash on delivery', $result['message']);
    }

    public function test_no_free_shipping_fallback_when_rates_fail(): void
    {
        Http::fake([
            '*/c/api/pin-codes/*' => Http::response([], 500),
            '*/api/kinko/*' => Http::response([], 500),
        ]);

        $result = $this->controller(['origin_pincode' => '400072', 'default_charge' => '0'])->rates($this->rateRequest());
        $this->assertFalse($result['success']);

        $result = $this->controller(['origin_pincode' => '400072', 'default_charge' => '120'])->rates($this->rateRequest());
        $this->assertTrue($result['success']);
        $this->assertSame(120.0, $result['data'][0]['price']);
    }

    public function test_tracking_status_is_parsed_into_stages(): void
    {
        $c = $this->controller();
        $make = fn ($status, $type) => ['ShipmentData' => [['Shipment' => ['Status' => ['Status' => $status, 'StatusType' => $type]]]]];

        $this->assertSame('manifested', $c->parseTracking($make('Manifested', 'UD'))['stage']);
        $this->assertSame('in_transit', $c->parseTracking($make('In Transit', 'UD'))['stage']);
        $this->assertSame('out_for_delivery', $c->parseTracking($make('Dispatched', 'UD'))['stage']);
        $this->assertSame('delivered', $c->parseTracking($make('Delivered', 'DL'))['stage']);
        $this->assertSame('returned', $c->parseTracking($make('RTO', 'DL'))['stage']);
        $this->assertSame('cancelled', $c->parseTracking($make('Canceled', 'CN'))['stage']);
        $this->assertNull($c->parseTracking(['Error' => 'No such waybill']));
    }

    public function test_track_updates_shipment(): void
    {
        Http::fake(['*/api/v1/packages/json/*' => Http::response([
            'ShipmentData' => [['Shipment' => ['Status' => ['Status' => 'In Transit', 'StatusType' => 'UD', 'StatusLocation' => 'Mumbai_Hub']]]],
        ])]);

        $shipment = $this->shipment('1234567890');
        $result = $this->controller()->track($shipment);

        $this->assertTrue($result['success']);
        $this->assertSame('In Transit', $shipment->courier_status);
        $this->assertNotNull($shipment->last_synced_at);
    }

    public function test_cancel_marks_shipment_cancelled_only_on_success(): void
    {
        Http::fakeSequence('*/api/p/edit')
            ->push(['status' => false, 'remark' => 'Shipment already picked up'])
            ->push(['status' => true, 'remark' => 'Shipment has been cancelled']);

        $shipment = $this->shipment('1234567890');

        $first = $this->controller()->cancel($shipment);
        $this->assertFalse($first['success']);
        $this->assertSame('created', $shipment->status);

        $second = $this->controller()->cancel($shipment);
        $this->assertTrue($second['success']);
        $this->assertSame('cancelled', $shipment->status);
        Http::assertSent(fn (HttpRequest $r) => $r['waybill'] === '1234567890' && $r['cancellation'] === 'true');
    }

    public function test_pickup_request_sends_location_and_returns_id(): void
    {
        Http::fake(['*/fm/request/new/' => Http::response(['pickup_id' => 98765, 'pickup_date' => '2026-11-19'])]);

        $result = $this->controller()->requestPickup('2026-11-19', '14:00', 3);

        $this->assertTrue($result['success']);
        $this->assertSame(98765, $result['pickup_id']);
        Http::assertSent(fn (HttpRequest $r) => $r['pickup_location'] === 'Ware House-1-Patel Estate'
            && $r['pickup_time'] === '14:00:00'
            && $r['expected_package_count'] === 3);
    }

    public function test_connection_check_reports_environment_and_bad_token(): void
    {
        Http::fakeSequence('staging-express.delhivery.com/c/api/pin-codes/*')
            ->push(['detail' => 'Invalid token'], 401)
            ->push(['delivery_codes' => [['postal_code' => ['pre_paid' => 'Y', 'cod' => 'Y']]]]);

        $bad = $this->controller(['origin_pincode' => '400070'])->testConnection();
        $this->assertFalse($bad['success']);
        $this->assertSame('SANDBOX', $bad['environment']);
        $this->assertStringContainsString('rejected the token', $bad['message']);

        $good = $this->controller(['origin_pincode' => '400070'])->testConnection();
        $this->assertTrue($good['success']);
        $this->assertStringContainsString('400070 is serviceable', $good['message']);
    }
}
