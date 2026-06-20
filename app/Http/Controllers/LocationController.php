<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Utility\LocationUtility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function change(Request $request)
    {
        $validated = $request->validate([
            'country_id' => [
                'required',
                'integer',
                Rule::exists('countries', 'id')->where('status', 1),
            ],
            'currency_code' => [
                'nullable',
                'string',
                Rule::exists('currencies', 'code')->where(function ($query) {
                    $query->where('status', 1)->orWhere('code', 'CNY');
                }),
            ],
            'locale' => [
                'nullable',
                'string',
                Rule::exists('languages', 'code')->where('status', 1),
            ],
        ]);

        $country = Country::query()->isEnabled()->with(['defaultCurrency', 'defaultLanguage'])->findOrFail($validated['country_id']);

        $currency = null;
        if (!empty($validated['currency_code'])) {
            $currency = Currency::where('code', $validated['currency_code'])
                ->where(function ($query) {
                    $query->where('status', 1)->orWhere('code', 'CNY');
                })
                ->first();
        }
        $currency = $currency ?: LocationUtility::resolveCurrencyForCountry($country);

        $language = null;
        if (!empty($validated['locale'])) {
            $language = Language::where('code', $validated['locale'])->where('status', 1)->first();
        }
        $language = $language ?: LocationUtility::resolveLanguageForCountry($country);

        LocationUtility::applyToSession($request->session(), $country, $currency, $language);
        $request->session()->put('location_override', true);

        return response()->json(['success' => true]);
    }
}
