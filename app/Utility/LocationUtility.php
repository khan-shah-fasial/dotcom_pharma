<?php

namespace App\Utility;

use App\Models\BusinessSetting;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use Illuminate\Contracts\Session\Session;

class LocationUtility
{
    protected static function isInvalidLanguage(?Language $language): bool
    {
        if (!$language) {
            return true;
        }
        $name = (string) ($language->name ?? '');
        $name = preg_replace('/\s+/u', ' ', trim($name));
        if ($name === '') {
            return true;
        }

        if (mb_strtolower($name) !== 'no') {
            return false;
        }

        $code = mb_strtolower(trim((string) ($language->code ?? '')));
        $app = mb_strtolower(trim((string) ($language->app_lang_code ?? '')));
        $allow = ['in', 'hi', 'mr', 'gu', 'bn', 'ar'];
        return !(in_array($code, $allow, true) || in_array($app, $allow, true));
    }

    public static function matchEnabledCountryFromIpData(array $ipData): ?Country
    {
        $query = Country::query()->isEnabled();

        $code = isset($ipData['country_code']) ? strtoupper(trim((string) $ipData['country_code'])) : '';
        if ($code !== '') {
            $country = (clone $query)->where('code', $code)->first();
            if ($country) {
                return $country;
            }
        }

        $name = isset($ipData['country']) ? trim((string) $ipData['country']) : '';
        if ($name !== '') {
            return (clone $query)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        }

        return null;
    }

    public static function getSystemDefaultCountry(): ?Country
    {
        // Prefer cached business settings helper if available.
        $raw = function_exists('get_setting')
            ? get_setting('system_default_country')
            : BusinessSetting::where('type', 'system_default_country')->value('value');

        $countryId = is_numeric($raw) ? (int) $raw : null;
        if (!$countryId) {
            return null;
        }

        return Country::query()->isEnabled()->find($countryId);
    }

    public static function getFallbackCountry(): ?Country
    {
        return self::getSystemDefaultCountry()
            ?: Country::query()->isEnabled()->orderBy('name')->first()
            ?: Country::query()->orderBy('name')->first();
    }

    public static function resolveCurrencyForCountry(Country $country): ?Currency
    {
        $country->loadMissing('defaultCurrency');

        $currency = $country->defaultCurrency;
        if ($currency && (int) $currency->status === 1) {
            return $currency;
        }

        $systemDefaultCurrencyId = function_exists('get_setting') ? (int) get_setting('system_default_currency') : null;
        if ($systemDefaultCurrencyId) {
            $currency = Currency::find($systemDefaultCurrencyId);
            if ($currency) {
                return $currency;
            }
        }

        return Currency::where('status', 1)->orderBy('id')->first();
    }

    public static function resolveLanguageForCountry(Country $country): ?Language
    {
        $country->loadMissing('defaultLanguage');

        $language = $country->defaultLanguage;
        if ($language && (int) $language->status === 1 && !self::isInvalidLanguage($language)) {
            return $language;
        }

        $defaultCode = env('DEFAULT_LANGUAGE', 'en');
        $language = Language::where('code', $defaultCode)->where('status', 1)->first();
        if ($language && !self::isInvalidLanguage($language)) {
            return $language;
        }

        $fallback = Language::where('status', 1)->orderBy('name')->get();
        return $fallback->first(function ($lang) {
            return !self::isInvalidLanguage($lang);
        });
    }

    public static function applyToSession(Session $session, Country $country, ?Currency $currency, ?Language $language): void
    {
        $session->put('country_id', (int) $country->id);
        $session->put('country', $country->name);
        $session->put('country_code', $country->code);

        if ($currency) {
            $session->put('currency_id', (int) $currency->id);
            $session->put('currency_code', $currency->code);
            $session->put('currency_symbol', $currency->symbol);
            $session->put('currency_exchange_rate', $currency->exchange_rate);
        }

        if ($language) {
            $session->put('language_id', (int) $language->id);
            $session->put('locale', $language->code);
            $session->put('langcode', $language->app_lang_code ?: $language->code);
        }
    }
}
