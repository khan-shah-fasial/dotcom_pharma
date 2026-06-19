<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\BusinessSetting;

class CountryController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:shipping_country_setting'])->only('index', 'updateStatus', 'updateDefaults', 'updateSystemDefaultCountry');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_country = $request->sort_country;
        $country_queries = Country::query()->with(['defaultCurrency', 'defaultLanguage']);
        if($request->sort_country) {
            $country_queries->where('name', 'like', "%$sort_country%");
        }
        $countries = $country_queries->orderBy('status', 'desc')->paginate(15);

        $active_currencies = Currency::where('status', 1)->orderBy('name')->get();
        $active_languages = Language::where('status', 1)->orderBy('name')->get();

        $system_default_currency_id = null;
        if (function_exists('get_setting')) {
            $maybe = get_setting('system_default_currency');
            $system_default_currency_id = is_numeric($maybe) ? (int) $maybe : null;
        }

        $default_language_code = env('DEFAULT_LANGUAGE', 'en');
        $default_language_id = Language::where('status', 1)->where('code', $default_language_code)->value('id');
        if (!$default_language_id) {
            $default_language_id = Language::where('status', 1)->orderBy('name')->value('id');
        }

        $system_default_country = BusinessSetting::where('type', 'system_default_country')->first();
        $system_default_country_id = $system_default_country && is_numeric($system_default_country->value)
            ? (int) $system_default_country->value
            : null;

        $enabled_countries = Country::query()->isEnabled()->orderBy('name')->get();

        return view('backend.setup_configurations.countries.index', compact(
            'countries',
            'sort_country',
            'active_currencies',
            'active_languages',
            'system_default_currency_id',
            'default_language_id',
            'system_default_country_id',
            'enabled_countries'
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function updateStatus(Request $request){
        $country = Country::findOrFail($request->id);
        $country->status = $request->status;
        if($country->save()){
            Cache::forget('active_countries');
            return 1;
        }
        return 0;
    }

    public function updateDefaults(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:countries,id',
            'default_currency_id' => 'nullable|integer|exists:currencies,id',
            'default_language_id' => 'nullable|integer|exists:languages,id',
            'regional_language_ids' => 'nullable|array',
            'regional_language_ids.*' => 'integer|distinct|exists:languages,id',
        ]);

        $country = Country::findOrFail($request->id);
        $country->default_currency_id = $request->default_currency_id ?: null;
        $country->default_language_id = $request->default_language_id ?: null;
        $country->regional_language = array_values(array_map('intval', $request->input('regional_language_ids', []) ?: []));
        $country->save();

        Cache::forget('active_countries');
        Cache::forget('business_settings');

        return 1;
    }

    public function updateSystemDefaultCountry(Request $request)
    {
        $request->validate([
            'country_id' => 'required|integer|exists:countries,id',
        ]);

        $country = Country::query()->isEnabled()->findOrFail($request->country_id);

        BusinessSetting::updateOrCreate(
            ['type' => 'system_default_country', 'lang' => null],
            ['value' => (string) $country->id]
        );

        Cache::forget('business_settings');

        return 1;
    }
}
