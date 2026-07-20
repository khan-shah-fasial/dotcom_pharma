<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderFormHelpersTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_geonames_pincode_fields_are_mapped_to_locality_district_and_state(): void
    {
        Http::fake(function (Request $request) {
            $postalCode = $request->data()['postalcode'] ?? null;
            $locations = [
                '400001' => ['placeName' => 'Mumbai GPO', 'adminName2' => 'Mumbai', 'adminName1' => 'Maharashtra'],
                '110001' => ['placeName' => 'New Delhi GPO', 'adminName2' => 'New Delhi', 'adminName1' => 'Delhi'],
            ];

            return Http::response([
                'postalCodes' => [array_merge($locations[$postalCode], [
                    'countryCode' => 'IN',
                    'postalCode' => $postalCode,
                ])],
            ]);
        });

        $mumbai = get_location_by_postalcode('IN', '400001');
        $delhi = get_location_by_postalcode('IN', '110001');

        $this->assertSame('Mumbai GPO', $mumbai['city']);
        $this->assertSame('Mumbai', $mumbai['district']);
        $this->assertSame('Maharashtra', $mumbai['state']);
        $this->assertSame('New Delhi GPO', $delhi['city']);
        $this->assertSame('New Delhi', $delhi['district']);
        $this->assertSame('Delhi', $delhi['state']);
    }

    public function test_order_number_financial_year_changes_on_april_first(): void
    {
        Cache::put('business_settings', collect([
            (object) ['type' => 'order_brand_short_code', 'value' => 'DP', 'lang' => null],
        ]));

        $march = financial_year_order_code_parts(Carbon::parse('2027-03-31 23:59:59'), 'S');
        $april = financial_year_order_code_parts(Carbon::parse('2027-04-01 00:00:00'), 'P');
        $default = financial_year_order_code_parts(Carbon::parse('2026-07-20 12:00:00'));

        $this->assertSame('786-DP-O-26-27-', $default['prefix']);
        $this->assertSame('786-DP-S-26-27-', $march['prefix']);
        $this->assertSame('S', $march['document']);
        $this->assertSame('786-DP-P-27-28-', $april['prefix']);
        $this->assertSame('P', $april['document']);
    }
}
