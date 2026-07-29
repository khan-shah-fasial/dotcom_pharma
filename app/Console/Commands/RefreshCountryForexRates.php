<?php

namespace App\Console\Commands;

use App\Services\ExchangeRatesApiService;
use Illuminate\Console\Command;
use Throwable;

class RefreshCountryForexRates extends Command
{
    protected $signature = 'forex:refresh-countries';

    protected $description = 'Refresh currency and Shipping Country forex rates from exchangeratesapi.io.';

    public function handle(ExchangeRatesApiService $service): int
    {
        try {
            $result = $service->refresh();
            $this->info(
                "Forex refresh complete: {$result['countries_updated']} countries and "
                . "{$result['currencies_updated']} currencies updated against {$result['system_currency']}."
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
