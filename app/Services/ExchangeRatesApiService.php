<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExchangeRatesApiService
{
    public function refresh(): array
    {
        $apiKey = trim((string) config('services.exchangeratesapi.key'));
        $baseUrl = rtrim((string) config('services.exchangeratesapi.base'), '/');

        if ($apiKey === '') {
            throw new RuntimeException('EXCHANGERATE_API_KEY is not configured.');
        }

        $systemCurrency = Currency::find((int) get_setting('system_default_currency'));
        if (!$systemCurrency || !$this->validCurrencyCode($systemCurrency->code)) {
            throw new RuntimeException('The system default currency is not configured with a valid ISO currency code.');
        }

        $currencies = Currency::query()
            ->whereNotNull('code')
            ->get()
            ->filter(fn (Currency $currency) => $this->validCurrencyCode($currency->code));

        $symbols = $currencies
            ->pluck('code')
            ->push($systemCurrency->code)
            ->map(fn ($code) => strtoupper((string) $code))
            ->unique()
            ->sort()
            ->values();

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->retry(2, 300, null, false)
                ->get("{$baseUrl}/latest", [
                    'access_key' => $apiKey,
                    'symbols' => $symbols->implode(','),
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Unable to connect to exchangeratesapi.io.');
        }

        if (!$response->ok()) {
            $providerMessage = trim((string) (
                data_get($response->json(), 'error.message')
                ?: data_get($response->json(), 'error.info')
            ));
            $providerMessage = $providerMessage !== ''
                ? ': ' . mb_substr($providerMessage, 0, 250)
                : '.';

            throw new RuntimeException(
                "The forex provider returned HTTP {$response->status()}{$providerMessage}"
            );
        }

        $payload = $response->json();
        if (($payload['success'] ?? false) !== true || !is_array($payload['rates'] ?? null)) {
            $providerMessage = trim((string) (
                data_get($payload, 'error.message')
                ?: data_get($payload, 'error.info')
            ));
            $providerMessage = $providerMessage !== '' ? mb_substr($providerMessage, 0, 250) : 'Invalid response received.';

            throw new RuntimeException("The forex provider rejected the request: {$providerMessage}");
        }

        $apiBase = strtoupper((string) ($payload['base'] ?? 'EUR'));
        $rates = collect($payload['rates'])
            ->mapWithKeys(fn ($rate, $code) => [strtoupper((string) $code) => (float) $rate])
            ->filter(fn ($rate) => $rate > 0)
            ->all();
        $rates[$apiBase] = 1.0;

        $systemCode = strtoupper((string) $systemCurrency->code);
        if (!isset($rates[$systemCode])) {
            throw new RuntimeException("The forex provider did not return the system currency {$systemCode}.");
        }

        $providerAsOf = isset($payload['timestamp']) && is_numeric($payload['timestamp'])
            ? Carbon::createFromTimestampUTC((int) $payload['timestamp'])
            : now('UTC');
        $refreshedAt = now();

        $currencyUpdates = 0;
        $countryUpdates = 0;

        DB::transaction(function () use (
            $currencies,
            $rates,
            $apiBase,
            $systemCode,
            $refreshedAt,
            &$currencyUpdates,
            &$countryUpdates
        ) {
            foreach ($currencies as $currency) {
                $code = strtoupper((string) $currency->code);
                $rate = self::calculateCrossRate($rates, $apiBase, $systemCode, $code);

                if ($rate === null) {
                    continue;
                }

                $currency->exchange_rate = $rate;
                $currency->save();
                $currencyUpdates++;
            }

            Country::query()
                ->with('defaultCurrency:id,code')
                ->whereNotNull('default_currency_id')
                ->chunkById(200, function ($countries) use (
                    $rates,
                    $apiBase,
                    $systemCode,
                    $refreshedAt,
                    &$countryUpdates
                ) {
                    foreach ($countries as $country) {
                        $currencyCode = strtoupper((string) optional($country->defaultCurrency)->code);
                        $rate = self::calculateCrossRate($rates, $apiBase, $systemCode, $currencyCode);

                        if ($rate === null) {
                            continue;
                        }

                        $country->forex_rate = $rate;
                        $country->forex_base_currency_code = $systemCode;
                        $country->forex_rate_updated_at = $refreshedAt;
                        $country->save();
                        $countryUpdates++;
                    }
                });
        });

        Cache::forget('system_default_currency');
        Cache::forget('active_countries');

        return [
            'api_base_currency' => $apiBase,
            'system_currency' => $systemCode,
            'provider_timestamp' => $providerAsOf->toIso8601String(),
            'refreshed_at' => $refreshedAt->toIso8601String(),
            'currencies_updated' => $currencyUpdates,
            'countries_updated' => $countryUpdates,
        ];
    }

    public static function calculateCrossRate(
        array $rates,
        string $apiBase,
        string $systemCurrency,
        string $countryCurrency
    ): ?float {
        $apiBase = strtoupper($apiBase);
        $systemCurrency = strtoupper($systemCurrency);
        $countryCurrency = strtoupper($countryCurrency);

        if ($countryCurrency === '') {
            return null;
        }

        $rates[$apiBase] = 1.0;
        $systemRate = (float) ($rates[$systemCurrency] ?? 0);
        $countryRate = (float) ($rates[$countryCurrency] ?? 0);

        if ($systemRate <= 0 || $countryRate <= 0) {
            return null;
        }

        return round($systemRate / $countryRate, 8);
    }

    private function validCurrencyCode($code): bool
    {
        return is_string($code) && preg_match('/^[A-Za-z]{3}$/', trim($code)) === 1;
    }
}
