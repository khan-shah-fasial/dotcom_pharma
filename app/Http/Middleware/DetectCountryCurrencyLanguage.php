<?php

namespace App\Http\Middleware;

use App\Utility\LocationUtility;
use Closure;
use Illuminate\Http\Request;

class DetectCountryCurrencyLanguage
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('admin/*') || $request->is('api/*')) {
            return $next($request);
        }

        // Avoid mutating session for API-style or background requests.
        if ($request->expectsJson()) {
            return $next($request);
        }

        $session = $request->session();

        if ($session->get('location_override')) {
            return $next($request);
        }

        if ($session->has('country_id') && $session->has('currency_code') && $session->has('locale')) {
            return $next($request);
        }

        $ipData = (array) (function_exists('getLocationFromIP') ? getLocationFromIP() : []);
        $session->put('ip_data', $ipData);

        $country = LocationUtility::matchEnabledCountryFromIpData($ipData) ?: LocationUtility::getFallbackCountry();
        if (!$country) {
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
}

