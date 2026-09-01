<?php

namespace App\Http\Middleware;

use App\Utility\LocationUtility;
use Closure;
use Illuminate\Http\Request;

class DetectCountryCurrencyLanguage
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return $next($request);
        }

        $session = $request->session();

        if ($session->get('location_override')) {
            return $next($request);
        }

        $isAdmin = $request->is('admin/*');

        if ($session->has('country_id') && $session->has('country_code')) {
            $this->backfillDialCode($session);
            if ($isAdmin || ($session->has('currency_code') && $session->has('locale'))) {
                return $next($request);
            }
        }

        $ipData = (array) (function_exists('getLocationFromIP') ? getLocationFromIP() : []);
        $session->put('ip_data', $ipData);
        $this->storeDialCode($session, $ipData);

        $country = LocationUtility::matchEnabledCountryFromIpData($ipData) ?: LocationUtility::getFallbackCountry();
        if (!$country) {
            return $next($request);
        }

        // Admin screens should pick up the detected country for form defaults,
        // without changing the admin language or currency.
        if ($isAdmin) {
            $session->put('country_id', (int) $country->id);
            $session->put('country', $country->name);
            $session->put('country_code', $country->code);
            return $next($request);
        }

        $currency = LocationUtility::resolveCurrencyForCountry($country);
        $language = LocationUtility::resolveLanguageForCountry($country);

        LocationUtility::applyToSession($session, $country, $currency, $language);

        // Persist raw IP data via existing helper.
        if (function_exists('storeIPLocation')) {
            try {
                if (auth()->check()) {
                    storeIPLocation('users', (string) auth()->id());
                } else {
                    storeIPLocation('sessions', (string) $session->getId());
                }
            } catch (\Throwable $e) {
                // best-effort only
            }
        }

        return $next($request);
    }

    protected function storeDialCode($session, array $ipData): void
    {
        $dial = preg_replace('/\D+/', '', (string) ($ipData['calling_code'] ?? $ipData['country_calling_code'] ?? ''));
        if ($dial !== '') {
            $session->put('dial_code', $dial);
        }
    }

    protected function backfillDialCode($session): void
    {
        if ($session->has('dial_code') && $session->get('dial_code') !== '') {
            return;
        }
        $this->storeDialCode($session, (array) $session->get('ip_data', []));
    }
}
