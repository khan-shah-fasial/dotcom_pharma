<?php

namespace App\Support;

use App\Models\Country;
use App\Utility\LocationUtility;

class HeaderLiveContext
{
    public static function resolve(): array
    {
        $country = self::resolveCountry();
        $currency = get_system_currency();
        $baseCurrency = get_system_default_currency();
        $ipData = (array) session('ip_data', []);

        $areaName = trim((string) session('live_area_name', ''));
        if ($areaName === '') {
            $areaName = self::areaFromIpData($ipData);
        }
        if ($areaName === '') {
            $areaName = (string) (session('country') ?: optional($country)->name ?: '');
        }

        return [
            'area_name' => $areaName,
            'country_name' => (string) (session('country') ?: optional($country)->name ?: ''),
            'country_code' => (string) (session('country_code') ?: optional($country)->code ?: ''),
            'timezone' => (string) (optional($country)->timezone ?: config('app.timezone', 'UTC')),
            'currency_code' => (string) optional($currency)->code,
            'currency_symbol' => (string) optional($currency)->symbol,
            'forex_label' => self::buildForexLabel($country, $currency, $baseCurrency),
            'live_area_url' => route('location.live-area'),
            'csrf_token' => csrf_token(),
        ];
    }

    protected static function resolveCountry(): ?Country
    {
        $countryId = session('country_id');
        if ($countryId) {
            $country = Country::query()->isEnabled()->with('defaultCurrency')->find($countryId);
            if ($country) {
                return $country;
            }
        }

        return LocationUtility::getFallbackCountry();
    }

    protected static function areaFromIpData(array $ipData): string
    {
        $parts = array_filter([
            $ipData['city'] ?? $ipData['cityName'] ?? null,
            $ipData['region'] ?? $ipData['state'] ?? $ipData['regionName'] ?? null,
        ], fn ($value) => is_string($value) && trim($value) !== '');

        return implode(', ', array_map('trim', $parts));
    }

    protected static function buildForexLabel(?Country $country, $currency, $baseCurrency): ?string
    {
        if (!$currency || !$baseCurrency) {
            return null;
        }

        $currencyCode = strtoupper((string) $currency->code);
        $baseCode = strtoupper((string) $baseCurrency->code);

        if ($currencyCode !== $baseCode && (float) $currency->exchange_rate > 0) {
            return sprintf(
                '1 %s = %s %s',
                $currencyCode,
                self::formatRate((float) $currency->exchange_rate),
                $baseCode
            );
        }

        $usdCurrency = \App\Models\Currency::query()
            ->where('code', 'USD')
            ->where(function ($query) {
                $query->where('status', 1)->orWhere('code', 'CNY');
            })
            ->first();

        if ($usdCurrency && $baseCode !== 'USD' && (float) $usdCurrency->exchange_rate > 0) {
            return sprintf(
                '1 USD = %s %s',
                self::formatRate((float) $usdCurrency->exchange_rate),
                $baseCode
            );
        }

        if (
            $country
            && $country->relationLoaded('defaultCurrency')
            && $country->defaultCurrency
            && $country->forex_rate
            && $country->forex_base_currency_code
        ) {
            return sprintf(
                '1 %s = %s %s',
                strtoupper((string) $country->defaultCurrency->code),
                self::formatRate((float) $country->forex_rate),
                strtoupper((string) $country->forex_base_currency_code)
            );
        }

        return null;
    }

    protected static function formatRate(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 6, '.', ''), '0'), '.');
    }
}
