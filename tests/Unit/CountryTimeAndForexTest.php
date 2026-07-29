<?php

namespace Tests\Unit;

use App\Models\Country;
use App\Services\ExchangeRatesApiService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CountryTimeAndForexTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cross_rate_is_calculated_against_the_system_currency(): void
    {
        $rates = [
            'EUR' => 1.0,
            'INR' => 90.0,
            'USD' => 1.2,
        ];

        $this->assertSame(
            75.0,
            ExchangeRatesApiService::calculateCrossRate($rates, 'EUR', 'INR', 'USD')
        );
        $this->assertSame(
            1.0,
            ExchangeRatesApiService::calculateCrossRate($rates, 'EUR', 'INR', 'INR')
        );
        $this->assertNull(
            ExchangeRatesApiService::calculateCrossRate($rates, 'EUR', 'INR', 'AED')
        );
    }

    public function test_country_local_time_uses_its_iana_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-29 12:00:00', 'UTC'));

        $country = new Country();
        $country->timezone = 'Asia/Kolkata';

        $this->assertSame('2026-07-29 17:30:00', $country->localDateTime()?->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Kolkata', $country->localDateTime()?->timezoneName);
    }

    public function test_invalid_country_timezone_does_not_generate_a_wrong_time(): void
    {
        $country = new Country();
        $country->timezone = 'Invalid/Timezone';

        $this->assertNull($country->localDateTime());
    }
}
