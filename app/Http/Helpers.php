<?php

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Cart;
use App\Models\City;
use App\Models\Shop;
use App\Models\User;
use App\Models\Addon;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Seller;
use App\Models\Upload;
use App\Models\Wallet;
use App\Models\Carrier;
use App\Models\Country;
use App\Models\Product;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Wishlist;
use App\Models\Attribute;
use App\Models\ClubPoint;
use App\Models\UserDetails;
use App\Models\FlashDeal;
use App\Models\CouponUsage;
use App\Models\DeliveryBoy;
use App\Models\OrderDetail;
use App\Models\PickupPoint;
use App\Models\Translation;
use App\Models\BlogCategory;
use App\Models\Conversation;
use App\Models\FollowSeller;
use App\Models\ProductStock;
use App\Models\ProductBatch;
use App\Models\State;
use App\Models\CombinedOrder;
use App\Models\SellerPackage;
use App\Models\AffiliateConfig;
use App\Models\AffiliateOption;
use App\Models\BusinessSetting;
use App\Models\CustomerPackage;
use App\Models\CustomerProduct;
use App\Utility\SendSMSUtility;;
use App\Models\AuctionProductBid;
use App\Models\ManualPaymentMethod;
use App\Models\SellerPackagePayment;
use App\Models\ShippingMethod;
use App\Utility\NotificationUtility;
use App\Http\Resources\V2\CarrierCollection;
use App\Http\Controllers\CommissionController;
use AizPackages\ColorCodeConverter\Services\ColorCodeConverter;
use App\Models\CustomerPackagePayment;
use App\Models\EmailTemplate;
use App\Models\FlashDealProduct;
use App\Models\LastViewedProduct;
use App\Models\PaymentMethod;
use App\Models\UserCoupon;
use App\Models\NotificationType;
use App\Utility\EmailUtility;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

if (!function_exists('get_location_by_postalcode')) {
    /**
     * Get city and state by country and postal code
     *
     * @param string $countryCode Two-letter country code (e.g., 'US', 'IN')
     * @param string $postalCode Postal code/Zipcode/Pincode
     * @return array|null Array with city and state info or null if not found
     */
    function get_location_by_postalcode($countryCode, $postalCode)
    {
        try {
            $response = Http::get("https://secure.geonames.org/postalCodeSearchJSON", [
                'postalcode' => $postalCode,
                'country' => $countryCode ?: '',
                'username' => 'umair.makent', // You need to register for a free GeoNames account to get a username
            ]);
            // $response = Http::get("https://api.zippopotam.us/{$countryCode}/{$postalCode}");
            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['postalCodes'])) {
                    $entry = $data['postalCodes'][0];
                    return [
                        'city'         => $entry['adminName2'] ?? $entry['placeName'] ?? null,
                        'state'        => $entry['adminName1'] ?? null,
                        'state_code'   => $entry['ISO3166-2'] ?? null,
                        'country_code' => $entry['countryCode'] ?? null,
                        'postal_code'  => $entry['postalCode'] ?? null,
                        'placename'    => $entry['placeName'] ?? null,
                    ];
                }
            }            
            return [];
        } catch (\Exception $e) {
            \Log::error("Failed to fetch location data: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('fetch_bank_details_by_ifsc')) {
    /**
     * Fetch bank metadata from Razorpay IFSC API.
     *
     * @param  string|null $ifsc
     * @return array{success:bool,message:string,data:?array}
     */
    function fetch_bank_details_by_ifsc(?string $ifsc): array
    {
        $code = strtoupper(trim((string) $ifsc));
        if ($code === '') {
            return [
                'success' => false,
                'message' => 'IFSC code is required',
                'data' => null,
            ];
        }

        try {
            $response = Http::timeout(8)->get('https://ifsc.razorpay.com/' . $code);

            if ($response->successful()) {
                $payload = $response->json();
                if (is_array($payload)) {
                    return [
                        'success' => true,
                        'message' => 'Bank details found',
                        'data' => [
                            'ifsc'      => $payload['IFSC'] ?? $code,
                            'bank'      => $payload['BANK'] ?? null,
                            'bank_code' => $payload['BANKCODE'] ?? null,
                            'branch'    => $payload['BRANCH'] ?? null,
                            'address'   => $payload['ADDRESS'] ?? null,
                            'city'      => $payload['CITY'] ?? null,
                            'district'  => $payload['DISTRICT'] ?? null,
                            'state'     => $payload['STATE'] ?? null,
                            'contact'   => $payload['CONTACT'] ?? null,
                            'micr'      => $payload['MICR'] ?? null,
                            'upi'       => $payload['UPI'] ?? null,
                            'rtgs'      => $payload['RTGS'] ?? null,
                            'imps'      => $payload['IMPS'] ?? null,
                            'neft'      => $payload['NEFT'] ?? null,
                            'iso3166'   => $payload['ISO3166'] ?? null,
                            'centre'    => $payload['CENTRE'] ?? null,
                            'swift'     => $payload['SWIFT'] ?? null,
                        ],
                    ];
                }
            }

            $message = $response->status() === 404
                ? 'No bank details found for this IFSC'
                : 'Unable to fetch bank details';

            return [
                'success' => false,
                'message' => $message,
                'data' => null,
            ];
        } catch (\Throwable $e) {
            \Log::error('IFSC lookup failed', ['ifsc' => $code, 'err' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Lookup failed, please try again later',
                'data' => null,
            ];
        }
    }
}

//sensSMS function for OTP
if (!function_exists('sendSMS')) {
    function sendSMS($to, $from, $text, $template_id)
    {
        return SendSMSUtility::sendSMS($to, $from, $text, $template_id);
    }
}

//highlights the selected navigation on admin panel
if (!function_exists('areActiveRoutes')) {
    function areActiveRoutes(array $routes, $output = "active")
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route && (url()->current() != url('/admin/website/custom-pages/edit/home'))) return $output;
        }
    }
}

//highlights the selected navigation on frontend
if (!function_exists('areActiveRoutesHome')) {
    function areActiveRoutesHome(array $routes, $output = "active")
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route) return $output;
        }
    }
}

//highlights the selected navigation on frontend
if (!function_exists('default_language')) {
    function default_language()
    {
        return env("DEFAULT_LANGUAGE");
    }
}

/**
 * Save JSON File
 * @return Response
 */
if (!function_exists('convert_to_usd')) {
    function convert_to_usd($amount)
    {
        $currency = Currency::find(get_setting('system_default_currency'));
        return (floatval($amount) / floatval($currency->exchange_rate)) * Currency::where('code', 'USD')->first()->exchange_rate;
    }
}

if (!function_exists('convert_to_kes')) {
    function convert_to_kes($amount)
    {
        $currency = Currency::find(get_setting('system_default_currency'));
        return (floatval($amount) / floatval($currency->exchange_rate)) * Currency::where('code', 'KES')->first()->exchange_rate;
    }
}

// Parse phone as "dial-number" into ['dial' => ..., 'number' => ...] with a fallback dial code.
if (!function_exists('parse_phone_number')) {
    function parse_phone_number($raw, $defaultDial = '91')
    {
        $raw = $raw ?? '';
        if ($raw === '') {
            return ['dial' => $defaultDial, 'number' => ''];
        }

        $parts = explode('-', $raw, 2);
        if (count($parts) === 2) {
            $dial = $parts[0] !== '' ? $parts[0] : $defaultDial;
            $number = $parts[1];
        } else {
            $dial = $defaultDial;
            $number = $parts[0];
        }

        return ['dial' => $dial, 'number' => $number];
    }
}

// get all active countries
if (!function_exists('get_active_countries')) {
    function get_active_countries()
    {
        return Cache::remember('active_countries', now()->addHours(6), function () {
            $country_query = Country::query();
            return $country_query->isEnabled()->get();
        });
    }
}

//filter products based on vendor activation system
if (!function_exists('filter_products')) {
    function filter_products($products)
    {

        $products = $products->isApprovedPublished()->where('auction_product', 0);

        if (!addon_is_activated('wholesale')) {
            $products = $products->where('wholesale_product', 0);
        }
        $verified_sellers = verified_sellers_id();
        if (get_setting('vendor_system_activation') == 1) {
            return $products->where(function ($p) use ($verified_sellers) {
                $p->where('added_by', 'admin')->orWhere(function ($q) use ($verified_sellers) {
                    $q->whereIn('user_id', $verified_sellers);
                });
            });
        } else {
            return $products->where('added_by', 'admin');
        }
    }
}

if (!function_exists('category_published_product_count')) {
    function category_published_product_count($categoryId)
    {
        $cacheKey = 'category_published_product_count_' . $categoryId;

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($categoryId) {
            // base query: products that are either directly in this category
            // OR mapped through pivot
            $query = Product::query()
                ->where(function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId)
                        ->orWhereIn('id', function ($sub) use ($categoryId) {
                            $sub->from('product_categories')
                                ->selectRaw('DISTINCT product_id')
                                ->where('category_id', $categoryId);
                        });
                });

            // now apply your existing product filters
            $query = filter_products($query);

            return $query->count();
        });
    }
}

//cache products based on category
if (!function_exists('get_cached_products')) {
    function get_cached_products($category_id = null)
    {
        return Cache::remember('products-category-' . $category_id, 86400, function () use ($category_id) {
            return filter_products(Product::where('category_id', $category_id))->latest()->take(5)->get();
        });
    }
}

if (!function_exists('verified_sellers_id')) {
    function verified_sellers_id()
    {
        return Cache::rememberForever('verified_sellers_id', function () {
            return Shop::where('verification_status', 1)->pluck('user_id')->toArray();
        });
    }
}

if (!function_exists('get_country_by_id')) {
    /**
     * Lightweight country fetcher by ID.
     */
    function get_country_by_id($countryId)
    {
        return $countryId ? Country::find($countryId) : null;
    }
}

if (!function_exists('get_state_by_id')) {
    /**
     * Lightweight state fetcher by ID.
     */
    function get_state_by_id($stateId)
    {
        return $stateId ? State::find($stateId) : null;
    }
}

if (!function_exists('get_city_by_id')) {
    /**
     * Lightweight city fetcher by ID.
     */
    function get_city_by_id($cityId)
    {
        return $cityId ? City::find($cityId) : null;
    }
}

if (!function_exists('get_user_location_bundle')) {
    /**
     * Returns the user's country + state + city plus the selectable state/city lists for that country/state.
     * Accepts a User model or user ID.
     */
    function get_user_location_bundle($userOrId)
    {
        $user = $userOrId instanceof User
            ? $userOrId
            : User::with('details')->find($userOrId);

        if (!$user) {
            return null;
        }

        $details = $user->details;

        // Prefer business values, then personal/user fallbacks.
        $countryId = $details->country_id_business ?? $details->country_id ?? $user->country ?? null;
        $stateId   = $details->state_id_business ?? $details->state_id ?? $user->state ?? null;
        $cityId    = $details->city_id_business ?? $details->city_id ?? $user->city ?? null;

        $country = get_country_by_id($countryId);
        $state   = get_state_by_id($stateId);
        $city    = get_city_by_id($cityId);

        $states = $countryId
            ? State::where('country_id', $countryId)->orderBy('name')->get()
            : collect();

        $cities = $stateId
            ? City::where('state_id', $stateId)->orderBy('name')->get()
            : collect();

        return [
            'country' => $country,
            'state'   => $state,
            'city'    => $city,
            'states'  => $states,
            'cities'  => $cities,
        ];
    }
}

if (!function_exists('sync_business_addresses_to_address_book')) {
    /**
     * Store or update billing and shipping address rows from business registration data.
     */
    function sync_business_addresses_to_address_book(User $user, ?UserDetails $details = null): void
    {
        $details = $details ?: $user->user_details;
        if (!$details) {
            return;
        }

        $addressLines = array_filter([
            $details->street_add_first_business,
            $details->street_add_sec_business,
            $details->locality_land_mark_business,
            $details->village_business,
        ], function ($value) {
            return filled($value);
        });

        $addressText = implode(', ', $addressLines);
        $hasAddressData = $addressText !== ''
            || filled($details->city_id_business)
            || filled($details->state_id_business)
            || filled($details->country_id_business)
            || filled($details->pincode_business);

        if (!$hasAddressData) {
            return;
        }

        $phone = $details->prim_mobile_no_business ?: $user->phone;
        $payload = [
            'address' => $addressText,
            'country_id' => $details->country_id_business,
            'state_id' => $details->state_id_business,
            'city_id' => $details->city_id_business,
            'postal_code' => $details->pincode_business,
            'phone' => $phone,
        ];

        $hasShipping = Address::where('user_id', $user->id)
            ->where('type', Address::TYPE_SHIPPING)
            ->exists();

        foreach ([Address::TYPE_BILLING, Address::TYPE_SHIPPING] as $type) {
            $address = Address::firstOrNew([
                'user_id' => $user->id,
                'type' => $type,
            ]);

            $address->fill($payload);
            $address->type = $type;

            if (!$address->exists) {
                $hasTypeRecord = Address::where('user_id', $user->id)
                    ->where('type', $type)
                    ->exists();
                $address->set_default = !$hasTypeRecord ? 1 : 0;
            }

            $address->save();
        }
    }
}

if (!function_exists('getUserDetailsLocationTree')) {
    /**
     * Build a grouped location tree (country > state > city > district) from user_details.
     *
     * @param  string $context  'business' (default) or 'personal'
     * @return array            Nested arrays ready for dropdown rendering
     */
    function getUserDetailsLocationTree(string $context = 'business'): array
    {
        $isBusiness = $context === 'business';

        $countryCol  = $isBusiness ? 'country_id_business' : 'country_id';
        $stateCol    = $isBusiness ? 'state_id_business' : 'state_id';
        $cityCol     = $isBusiness ? 'city_id_business' : 'city_id';
        $districtCol = $isBusiness ? 'district_business' : 'district';

        $cacheKey = 'user_details_location_tree_' . $context;

        /** @var array $result */
        $result = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($countryCol, $stateCol, $cityCol, $districtCol) {
            $rows = UserDetails::query()
                ->select([
                    $countryCol . ' as country_id',
                    $stateCol . ' as state_id',
                    $cityCol . ' as city_id',
                    $districtCol . ' as district',
                ])
                ->whereNotNull($countryCol)
                ->where($countryCol, '!=', '')
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $countryIds = $rows->pluck('country_id')->filter()->unique()->all();
            $countries  = Country::whereIn('id', $countryIds)->get(['id', 'name'])->keyBy('id');

            $tree = [];

            foreach ($rows as $row) {
                $countryId = (int) $row->country_id;
                if (!$countryId) {
                    continue;
                }

                if (!isset($tree[$countryId])) {
                    $tree[$countryId] = [
                        'id'     => $countryId,
                        'name'   => $countries[$countryId]->name ?? (string) $countryId,
                        'states' => [],
                    ];
                }

                $stateVal = $row->state_id !== null ? trim((string) $row->state_id) : null;
                if ($stateVal !== '' && !isset($tree[$countryId]['states'][$stateVal])) {
                    $tree[$countryId]['states'][$stateVal] = [
                        'id'     => $stateVal,
                        'name'   => $stateVal,
                        'cities' => [],
                    ];
                }

                $cityVal = $row->city_id !== null ? trim((string) $row->city_id) : null;
                if ($stateVal && $cityVal && !isset($tree[$countryId]['states'][$stateVal]['cities'][$cityVal])) {
                    $tree[$countryId]['states'][$stateVal]['cities'][$cityVal] = [
                        'id'        => $cityVal,
                        'name'      => $cityVal,
                        'districts' => [],
                    ];
                }

                $district = $row->district;
                if ($stateVal && $cityVal && $district !== null && $district !== '') {
                    $districtKey = (string) $district;
                    $tree[$countryId]['states'][$stateVal]['cities'][$cityVal]['districts'][$districtKey] = [
                        'id'   => $districtKey,
                        'name' => $districtKey,
                    ];
                }
            }

            return collect($tree)
                ->sortBy('name')
                ->map(function ($country) {
                    $country['states'] = collect($country['states'])
                        ->sortBy('name')
                        ->map(function ($state) {
                            $state['cities'] = collect($state['cities'])
                                ->sortBy('name')
                                ->map(function ($city) {
                                    $city['districts'] = collect($city['districts'])
                                        ->sortBy('name')
                                        ->values()
                                        ->all();
                                    return $city;
                                })
                                ->values()
                                ->all();
                            return $state;
                        })
                        ->values()
                        ->all();
                    return $country;
                })
                ->values()
                ->all();
        });

        return $result;
    }
}

if (!function_exists('sync_user_detail_location_ids')) {
    /**
     * Normalize user_details state/city fields so they store the canonical IDs from states and cities tables.
     * Returns a summary of how many rows changed plus samples of any unmatched values.
     */
    function sync_user_detail_location_ids(): array
    {
        $normalize = function ($value) {
            $value = preg_replace('/\s+/', ' ', (string) $value);
            $value = trim($value);
            return $value === '' ? null : strtolower($value);
        };

        $stateById = State::all(['id', 'name'])->keyBy('id');
        $stateByName = [];
        foreach ($stateById as $state) {
            $key = $normalize($state->name);
            if ($key !== null && !isset($stateByName[$key])) {
                $stateByName[$key] = $state->id;
            }
        }

        // Common spelling variants to smoothen lookups.
        $stateAliases = [
            'orissa'    => 'odisha',
            'tamilnadu' => 'tamil nadu',
        ];

        $cityById = City::all(['id', 'name', 'state_id'])->keyBy('id');
        $cityLookup = [];
        $cityFallback = [];
        foreach ($cityById as $city) {
            $key = $normalize($city->name);
            if ($key === null) {
                continue;
            }

            $cityLookup[$city->state_id][$key] = $city->id;

            if (!isset($cityFallback[$key])) {
                $cityFallback[$key] = $city->id;
            }
        }

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'state_misses' => [],
            'city_misses' => [],
        ];

        $resolveStateId = function ($raw, $field, $detailId) use ($stateById, $stateByName, $stateAliases, $normalize, &$stats) {
            $rawString = trim((string) $raw);
            $normalizedRaw = $normalize($rawString);
            if ($normalizedRaw === null) {
                return [null, null];
            }

            if (isset($stateAliases[$normalizedRaw])) {
                $normalizedRaw = $stateAliases[$normalizedRaw];
            }

            if (ctype_digit($normalizedRaw)) {
                $id = (int) $normalizedRaw;
                if (isset($stateById[$id])) {
                    return [$id, null];
                }
            }

            if (isset($stateByName[$normalizedRaw])) {
                return [$stateByName[$normalizedRaw], null];
            }

            if (count($stats['state_misses']) < 25) {
                $stats['state_misses'][] = [
                    'user_detail_id' => $detailId,
                    'field' => $field,
                    'value' => $rawString,
                ];
            }

            return [null, $rawString];
        };

        $resolveCityId = function ($raw, $stateId, $field, $detailId) use ($cityById, $cityLookup, $cityFallback, $normalize, &$stats) {
            $rawString = trim((string) $raw);
            $normalizedRaw = $normalize($rawString);
            if ($normalizedRaw === null) {
                return [null, false];
            }

            if (ctype_digit($normalizedRaw)) {
                $id = (int) $normalizedRaw;
                if (isset($cityById[$id])) {
                    return [$id, false];
                }
            }

            if ($stateId !== null && isset($cityLookup[$stateId][$normalizedRaw])) {
                return [$cityLookup[$stateId][$normalizedRaw], false];
            }

            if (isset($cityFallback[$normalizedRaw])) {
                return [$cityFallback[$normalizedRaw], false];
            }

            if (count($stats['city_misses']) < 25) {
                $stats['city_misses'][] = [
                    'user_detail_id' => $detailId,
                    'field' => $field,
                    'value' => $rawString,
                    'state_id_hint' => $stateId,
                ];
            }

            return [null, true]; // flag to null-out mismatches
        };

        UserDetails::query()
            ->select(['id', 'state_id', 'state_id_business', 'city_id', 'city_id_business'])
            ->chunkById(3000, function ($chunk) use (&$stats, $resolveStateId, $resolveCityId) {
                foreach ($chunk as $detail) {
                    $stats['processed']++;
                    $updates = [];

                    [$stateId] = $resolveStateId($detail->state_id, 'state_id', $detail->id);
                    [$stateIdBusiness] = $resolveStateId($detail->state_id_business, 'state_id_business', $detail->id);

                    [$cityId, $nullCity] = $resolveCityId($detail->city_id, $stateId, 'city_id', $detail->id);
                    [$cityIdBusiness, $nullCityBusiness] = $resolveCityId($detail->city_id_business, $stateIdBusiness, 'city_id_business', $detail->id);

                    if ($stateId !== null && (string) $detail->state_id !== (string) $stateId) {
                        $updates['state_id'] = $stateId;
                    }

                    if ($stateIdBusiness !== null && (string) $detail->state_id_business !== (string) $stateIdBusiness) {
                        $updates['state_id_business'] = $stateIdBusiness;
                    }

                    if ($cityId !== null && (string) $detail->city_id !== (string) $cityId) {
                        $updates['city_id'] = $cityId;
                    } elseif ($nullCity && $detail->city_id !== null) {
                        $updates['city_id'] = null;
                    }

                    if ($cityIdBusiness !== null && (string) $detail->city_id_business !== (string) $cityIdBusiness) {
                        $updates['city_id_business'] = $cityIdBusiness;
                    } elseif ($nullCityBusiness && $detail->city_id_business !== null) {
                        $updates['city_id_business'] = null;
                    }

                    if (!empty($updates)) {
                        $detail->forceFill($updates)->save();
                        $stats['updated']++;
                    } else {
                        $stats['unchanged']++;
                    }
                }
            });

        return $stats;
    }
}

// if (!function_exists('unbanned_sellers_id')) {
//     function unbanned_sellers_id()
//     {
//         return Cache::rememberForever('unbanned_sellers_id', function () {
//             return App\Models\User::where('user_type', 'seller')->where('banned', 0)->pluck('id')->toArray();
//         });
//     }
// }

if (!function_exists('get_system_default_currency')) {
    function get_system_default_currency()
    {
        return Cache::remember('system_default_currency', 86400, function () {
            return Currency::findOrFail(get_setting('system_default_currency'));
        });
    }
}

//converts currency to home default currency
if (!function_exists('convert_price')) {
    function convert_price($price)
    {
        if (Session::has('currency_code') && (Session::get('currency_code') != get_system_default_currency()->code)) {
            $price = floatval($price) / floatval(get_system_default_currency()->exchange_rate);
            $price = floatval($price) * floatval(Session::get('currency_exchange_rate'));
        }

        if (
            request()->header('Currency-Code') &&
            request()->header('Currency-Code') != get_system_default_currency()->code
        ) {
            $price = floatval($price) / floatval(get_system_default_currency()->exchange_rate);
            $price = floatval($price) * floatval(request()->header('Currency-Exchange-Rate'));
        }
        return $price;
    }
}

//gets currency symbol
if (!function_exists('currency_symbol')) {
    function currency_symbol()
    {
        if (Session::has('currency_symbol')) {
            return Session::get('currency_symbol');
        }
        if (request()->header('Currency-Code')) {
            return request()->header('Currency-Code');
        }
        return get_system_default_currency()->symbol;
    }
}

//formats currency
if (!function_exists('format_price')) {
    function format_price($price, $isMinimize = false)
    {
        if (get_setting('decimal_separator') == 1) {
            $fomated_price = number_format($price, get_setting('no_of_decimals'));
        } else {
            $fomated_price = number_format($price, get_setting('no_of_decimals'), ',', '.');
        }


        // Minimize the price
        if ($isMinimize) {
            $temp = number_format($price / 1000000000, get_setting('no_of_decimals'), ".", "");

            if ($temp >= 1) {
                $fomated_price = $temp . "B";
            } else {
                $temp = number_format($price / 1000000, get_setting('no_of_decimals'), ".", "");
                if ($temp >= 1) {
                    $fomated_price = $temp . "M";
                }
            }
        }

        if (get_setting('symbol_format') == 1) {
            return currency_symbol() . ' ' . $fomated_price;
        } else if (get_setting('symbol_format') == 3) {
            return currency_symbol() . ' ' . $fomated_price;
        } else if (get_setting('symbol_format') == 4) {
            return $fomated_price . ' ' . currency_symbol();
        }
        return $fomated_price . currency_symbol();
    }
}

//formats price to home default price with convertion
if (!function_exists('single_price')) {
    function single_price($price)
    {
        return format_price(convert_price($price));
    }
}

if (!function_exists('discount_in_percentage')) {
    function discount_in_percentage($product)
    {
        $base = home_base_price($product, false);
        $reduced = home_discounted_base_price($product, false);
        $discount = $base - $reduced;
        $dp = ($discount * 100) / ($base > 0 ? $base : 1);
        return round($dp);
    }
}

//Shows Price on page based on carts
if (!function_exists('cart_product_price')) {
    function cart_product_price($cart_product, $product, $formatted = true, $tax = true)
    {        // 🚨 Guard clause: product deleted / unavailable
        if (!$product) {
            return $formatted ? format_price(0) : 0;
        }

        $price = 0;
        if ($product->auction_product == 0) {
            $str = '';
            if ($cart_product['variation'] != null) {
                $str = $cart_product['variation'];
            }
            $price = 0;
            $product_stock = $product->stocks->where('variant', $str)->first();
            
            // Use batch data if batch_id exists in cart
            $batchId = $cart_product['batch_id'] ?? null;
            if ($batchId && $product_stock) {
                $batch = \App\Models\ProductBatch::find($batchId);
                if ($batch && $batch->product_stock_id == $product_stock->id) {
                    // Calculate price from batch MRP and role_price
                    $mrpPrice = $batch->mrp_price ?? 0;
                    $rolePrice = $batch->role_price ?? null;
                    
                    if ($rolePrice) {
                        $rolePriceArray = is_string($rolePrice) ? json_decode($rolePrice, true) : $rolePrice;
                        $price = getPriceByRole($rolePriceArray, $mrpPrice);
                    } else {
                        // Batch has no role_price, fallback to product-level (NOT stock-level)
                        $price = getPriceByRole($product->role_price ?? null, $product_stock->price ?? 0);
                    }
                } else {
                    // Batch not found or doesn't match stock, try to get from other batches or product-level
                    if ($product_stock) {
                        // Try to get price from batches using helper function
                        $price = getStockPriceByRole($product_stock, $product, false);
                        if ($price === null || $price === 0) {
                            $price = getPriceByRole($product->role_price ?? null, $product_stock->price ?? 0);
                        }
                    } else {
                        $price = getPriceByRole($product->role_price ?? null, $product->unit_price ?? 0);
                    }
                }
            } elseif ($product_stock) {
                // No batch selected, use batch-aware pricing helper (checks all batches)
                //$price = $product_stock->price;
                $price = getStockPriceByRole($product_stock, $product, false);
                if ($price === null || $price === 0) {
                    // Fallback to product-level role_price (NOT stock-level)
                    $price = getPriceByRole($product->role_price ?? null, $product_stock->price ?? 0); //price by role
                }
            }

            if ($product->wholesale_product) {
                $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $cart_product['quantity'])->where('max_qty', '>=', $cart_product['quantity'])->first();
                if ($wholesalePrice) {
                    $price = $wholesalePrice->price;
                }
            }

            //discount calculation
            $discount_applicable = false;

            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            } elseif (
                strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
            ) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if ($product->discount_type == 'percent') {
                    $price -= ($price * $product->discount) / 100;
                } elseif ($product->discount_type == 'amount') {
                    $price -= $product->discount;
                }
            }
        } else {
            $price = $product->bids->max('amount');
        }

        //calculation of taxes
        if ($tax) {
            $taxAmount = 0;
            foreach ($product->taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $taxAmount += ($price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $taxAmount += $product_tax->tax;
                }
            }
            $price += $taxAmount;
        }

        if ($formatted) {
            return format_price(convert_price($price));
        } else {
            return $price;
        }
    }
}

if (!function_exists('cart_product_tax')) {
    function cart_product_tax($cart_product, $product, $formatted = true)
    {
        $str = '';
        if ($cart_product['variation'] != null) {
            $str = $cart_product['variation'];
        }
        $product_stock = $product->stocks->where('variant', $str)->first();
        $price = 0;

        // Use batch price when batch_id present (same logic as cart_product_price)
        $batchId = $cart_product['batch_id'] ?? null;
        if ($batchId && $product_stock) {
            $batch = \App\Models\ProductBatch::find($batchId);
            if ($batch && $batch->product_stock_id == $product_stock->id) {
                $mrpPrice = $batch->mrp_price ?? 0;
                $rolePrice = $batch->role_price ?? null;
                if ($rolePrice) {
                    $rolePriceArray = is_string($rolePrice) ? json_decode($rolePrice, true) : $rolePrice;
                    $price = getPriceByRole($rolePriceArray, $mrpPrice);
                } else {
                    // Batch has no role_price, fallback to product-level (NOT stock-level)
                    $price = getPriceByRole($product->role_price ?? null, $product_stock->price ?? 0);
                }
            }
        }
        if ($price == 0 && $product_stock) {
            // Try to get price from batches using helper function
            $price = getStockPriceByRole($product_stock, $product, false);
            if ($price === null || $price === 0) {
                // Fallback to product-level role_price (NOT stock-level)
                $price = getPriceByRole($product->role_price ?? null, $product_stock->price ?? 0);
            }
        }

        //discount calculation
        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }

        //calculation of taxes
        $tax = 0;
        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }

        if ($formatted) {
            return format_price(convert_price($tax));
        } else {
            return $tax;
        }
    }
}

if (!function_exists('cart_product_discount')) {
    function cart_product_discount($cart_product, $product, $formatted = false)
    {
        $str = '';
        if ($cart_product['variation'] != null) {
            $str = $cart_product['variation'];
        }
        $product_stock = $product->stocks->where('variant', $str)->first();
        //$price = $product_stock->price;
        // IMPORTANT: role_price comes ONLY from batches, NOT from stock
        // Use batch-aware pricing helper which checks batches first, then falls back to product-level
        if ($product_stock) {
            $price = getStockPriceByRole($product_stock, $product, false);
            if ($price === null || $price === 0) {
                // Fallback to product-level role_price (NOT stock-level)
                $price = getPriceByRole($product->role_price ?? null, $product_stock->price ?? 0); //price by role
            }
        } else {
            $price = getPriceByRole($product->role_price ?? null, $product->unit_price ?? 0);
        }

        //discount calculation
        $discount_applicable = false;
        $discount = 0;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $discount = ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $discount = $product->discount;
            }
        }

        if ($formatted) {
            return format_price(convert_price($discount));
        } else {
            return $discount;
        }
    }
}

// all discount
if (!function_exists('carts_product_discount')) {
    function carts_product_discount($cart_products, $formatted = false)
    {
        $discount = 0;
        foreach ($cart_products as $key => $cart_product) {
            $str = '';
            $product = \App\Models\Product::find($cart_product['product_id']);
            if ($cart_product['variation'] != null) {
                $str = $cart_product['variation'];
            }
            $product_stock = $product->stocks->where('variant', $str)->first();
            //$price = $product_stock->price;
            // IMPORTANT: role_price comes ONLY from batches, NOT from stock
            // Use batch-aware pricing helper which checks batches first, then falls back to product-level
            if ($product_stock) {
                $price = getStockPriceByRole($product_stock, $product, false);
                if ($price === null || $price === 0) {
                    // Fallback to product-level role_price (NOT stock-level)
                    $price = getPriceByRole($product->role_price ?? null, $product_stock->price ?? 0); //price by role
                }
            } else {
                $price = getPriceByRole($product->role_price ?? null, $product->unit_price ?? 0);
            }

            //discount calculation
            $discount_applicable = false;

            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            } elseif (
                strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
            ) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if ($product->discount_type == 'percent') {
                    $discount += ($price * $product->discount) / 100;
                } elseif ($product->discount_type == 'amount') {
                    $discount += $product->discount;
                }
            }
        }

        if ($formatted) {
            return format_price(convert_price($discount));
        } else {
            return $discount;
        }
    }
}

// carts coupon discount
if (!function_exists('carts_coupon_discount')) {
    function carts_coupon_discount($code, $formatted = false)
    {
        $coupon = Coupon::where('code', $code)->first();
        $coupon_discount = 0;
        if ($coupon != null) {
            if (strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date) {
                if (CouponUsage::where('user_id', Auth::user()->id)->where('coupon_id', $coupon->id)->first() == null) {
                    $coupon_details = json_decode($coupon->details);
                    $carts = Cart::where('user_id', Auth::user()->id)
                        ->where('owner_id', $coupon->user_id)
                        ->get();
                    if ($coupon->type == 'cart_base') {
                        $subtotal = 0;
                        $tax = 0;
                        $shipping = 0;
                        foreach ($carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                            $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                            $shipping += $cartItem['shipping_cost'];
                        }
                        $sum = $subtotal + $tax + $shipping;
                        if ($sum >= $coupon_details->min_buy) {
                            if ($coupon->discount_type == 'percent') {
                                $coupon_discount = ($sum * $coupon->discount) / 100;
                                if ($coupon_discount > $coupon_details->max_discount) {
                                    $coupon_discount = $coupon_details->max_discount;
                                }
                            } elseif ($coupon->discount_type == 'amount') {
                                $coupon_discount = $coupon->discount;
                            }
                        }
                    } elseif ($coupon->type == 'product_base') {
                        foreach ($carts as $key => $cartItem) {
                            $product = Product::find($cartItem['product_id']);
                            foreach ($coupon_details as $key => $coupon_detail) {
                                if ($coupon_detail->product_id == $cartItem['product_id']) {
                                    if ($coupon->discount_type == 'percent') {
                                        $coupon_discount += (cart_product_price($cartItem, $product, false, false) * $coupon->discount / 100) * $cartItem['quantity'];
                                    } elseif ($coupon->discount_type == 'amount') {
                                        $coupon_discount += $coupon->discount * $cartItem['quantity'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($coupon_discount > 0) {
                Cart::where('user_id', Auth::user()->id)
                    ->where('owner_id', $coupon->user_id)
                    ->update(
                        [
                            'discount' => $coupon_discount / count($carts),
                        ]
                    );
            } else {
                Cart::where('user_id', Auth::user()->id)
                    ->where('owner_id', $coupon->user_id)
                    ->update(
                        [
                            'discount' => 0,
                            'coupon_code' => null,
                        ]
                    );
            }
        }
        if ($formatted) {
            return format_price(convert_price($coupon_discount));
        } else {
            return $coupon_discount;
        }
    }
}


/**
 * Parse variant string to extract color and attribute values for auto-selection
 * Returns array with 'color' and 'attributes' (attribute_id => value)
 * 
 * @param string $variant Variant string like "ColorName-AttrValue1-AttrValue2" (spaces removed)
 * @param Product $product Product to match against choice_options
 * @return array ['color' => string|null, 'attributes' => [attribute_id => value]]
 */
if (!function_exists('parseVariantForSelection')) {
    function parseVariantForSelection($variant, $product)
    {
        if (!$variant || !$product) {
            return ['color' => null, 'attributes' => []];
        }

        $parts = explode('-', $variant);
        $color = null;
        $attributes = [];
        
        // Check if product has colors
        $hasColors = $product->colors && count(json_decode($product->colors ?? '[]')) > 0;
        $choiceOptions = json_decode($product->choice_options ?? '[]', true);
        
        $partIndex = 0;
        
        // First part might be color if colors are active
        if ($hasColors && count($parts) > 0) {
            $firstPart = trim($parts[0]);
            // Check if this matches a color name (variant stores color name, not code)
            $colorObj = \App\Models\Color::where('name', $firstPart)->first();
            if ($colorObj) {
                $color = $colorObj->name;
                $partIndex = 1; // Skip color part
            }
        }
        
        // Remaining parts are attribute values (spaces already removed in variant string)
        foreach ($choiceOptions as $choice) {
            if ($partIndex < count($parts)) {
                $attrValue = trim($parts[$partIndex]);
                // Store the value as-is (spaces already removed in variant)
                $attributes[$choice['attribute_id']] = $attrValue;
                $partIndex++;
            }
        }
        
        return ['color' => $color, 'attributes' => $attributes];
    }
}

/**
 * Find the stock with the lowest price (considering batches) for a product
 * Returns the stock object with lowest price, or null if no stocks
 * 
 * @param Product $product
 * @return ProductStock|null Stock with lowest price
 */
if (!function_exists('getLowestPriceStock')) {
    function getLowestPriceStock($product)
    {
        if (!$product || !$product->variant_product) {
            return null;
        }

        $cacheKey = 'lowest_price_stock_' . $product->id . '_' . (getCurrentUserRole() ?? 'guest');
        
        return Cache::remember($cacheKey, now()->addHour(), function () use ($product) {
            if (!$product->relationLoaded('stocks')) {
                $product->load(['stocks.batches']);
            }
            
            $lowestPriceStock = null;
            $lowestPrice = null;
            
            foreach ($product->stocks as $stock) {
                if ($stock->is_hidden) {
                    continue;
                }
                
                $stockPrice = getStockPriceByRole($stock, $product, false);
                
                if ($lowestPrice === null || $stockPrice < $lowestPrice) {
                    $lowestPrice = $stockPrice;
                    $lowestPriceStock = $stock;
                }
            }
            
            return $lowestPriceStock;
        });
    }
}

/**
 * Get price from stock considering batches (batch-level role prices take precedence)
 * Returns the lowest price if multiple batches exist, or stock price if no batches
 * 
 * @param ProductStock $stock
 * @param Product|null $product Fallback product for role_price
 * @param bool $useCache Whether to use cache (set to false when already inside cached function)
 * @return float Price based on current user role
 */
if (!function_exists('getStockPriceByRole')) {
    function getStockPriceByRole($stock, $product = null, $useCache = false)
    {
        if (!$stock) {
            return $product ? getPriceByRole($product->role_price ?? null, $product->unit_price ?? 0) : 0;
        }

        // Load product if not provided
        if (!$product) {
            if ($stock->relationLoaded('product')) {
                $product = $stock->product;
            } else {
                $product = $stock->product;
            }
        }

        $computePrice = function () use ($stock, $product) {
            // Check if stock has batches
            if (!$stock->relationLoaded('batches')) {
                $stock->load('batches');
            }
            
            $batches = $stock->batches;
            
            if ($batches && $batches->count() > 0) {
                // Stock has batches - use batch-level pricing (lowest price across all batches)
                // IMPORTANT: role_price comes ONLY from batches, NOT from stock
                $lowestPrice = null;
                
                foreach ($batches as $batch) {
                    $mrpPrice = $batch->mrp_price ?? $stock->price ?? 0;
                    $batchRolePrice = $batch->role_price ?? null;
                    
                    if ($batchRolePrice) {
                        $rolePriceArray = is_string($batchRolePrice) ? json_decode($batchRolePrice, true) : $batchRolePrice;
                        if (is_array($rolePriceArray)) {
                            $batchPrice = getPriceByRole($rolePriceArray, $mrpPrice);
                        } else {
                            // Invalid format, fallback to product-level role_price (NOT stock-level)
                            $batchPrice = getPriceByRole($product->role_price ?? null, $mrpPrice);
                        }
                    } else {
                        // Batch has no role_price, fallback to product-level role_price (NOT stock-level)
                        $batchPrice = getPriceByRole($product->role_price ?? null, $mrpPrice);
                    }
                    
                    if ($lowestPrice === null || $batchPrice < $lowestPrice) {
                        $lowestPrice = $batchPrice;
                    }
                }
                
                return $lowestPrice ?? ($stock->price ?? 0);
            } else {
                // No batches - fallback to product-level role_price (NOT stock-level)
                return getPriceByRole($product->role_price ?? null, $stock->price ?? 0);
            }
        };

        if ($useCache) {
            $cacheKey = 'stock_price_role_' . $stock->id . '_' . (getCurrentUserRole() ?? 'guest');
            return Cache::remember($cacheKey, now()->addHour(), $computePrice);
        }
        
        return $computePrice();
    }
}

//Shows Price on page based on low to high
if (!function_exists('home_price')) {
    function home_price($product, $formatted = true)
    {
        $cacheKey = 'home_price_' . $product->id . '_' . (getCurrentUserRole() ?? 'guest') . '_' . ($formatted ? 'fmt' : 'raw');
        
        return Cache::remember($cacheKey, now()->addHour(), function () use ($product, $formatted) {
            // Start with product-level price
            $lowest_price = getPriceByRole($product->role_price ?? null, $product->unit_price ?? 0);
            $highest_price = $lowest_price;

            if ($product->variant_product) {
                // Load stocks with batches for efficient querying
                if (!$product->relationLoaded('stocks')) {
                    $product->load(['stocks.batches']);
                }
                
                foreach ($product->stocks as $stock) {
                    // Use batch-aware pricing helper (no cache here, already cached at function level)
                    $stockPrice = getStockPriceByRole($stock, $product, false);
                    
                    if ($lowest_price > $stockPrice) {
                        $lowest_price = $stockPrice;
                    }
                    if ($highest_price < $stockPrice) {
                        $highest_price = $stockPrice;
                    }
                }
            }

            // Apply taxes
            if ($product->relationLoaded('taxes')) {
                $taxes = $product->taxes;
            } else {
                $taxes = $product->taxes()->get();
            }
            
            foreach ($taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $lowest_price += ($lowest_price * $product_tax->tax) / 100;
                    $highest_price += ($highest_price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $lowest_price += $product_tax->tax;
                    $highest_price += $product_tax->tax;
                }
            }

            if ($formatted) {
                if ($lowest_price == $highest_price) {
                    return format_price(convert_price($lowest_price));
                } else {
                    return format_price(convert_price($lowest_price)) . ' - ' . format_price(convert_price($highest_price));
                }
            } else {
                return $lowest_price . ' - ' . $highest_price;
            }
        });
    }
}

//Shows Price on page based on low to high with discount
if (!function_exists('home_discounted_price')) {
    function home_discounted_price($product, $formatted = true)
    {
        $cacheKey = 'home_discounted_price_' . $product->id . '_' . (getCurrentUserRole() ?? 'guest') . '_' . ($formatted ? 'fmt' : 'raw');
        
        return Cache::remember($cacheKey, now()->addHour(), function () use ($product, $formatted) {
            // Start with product-level price
            $lowest_price = getPriceByRole($product->role_price ?? null, $product->unit_price ?? 0);
            $highest_price = $lowest_price;

            if ($product->variant_product) {
                // Load stocks with batches for efficient querying
                if (!$product->relationLoaded('stocks')) {
                    $product->load(['stocks.batches']);
                }
                
                foreach ($product->stocks as $stock) {
                    // Use batch-aware pricing helper (no cache here, already cached at function level)
                    $stockPrice = getStockPriceByRole($stock, $product, false);
                    
                    if ($lowest_price > $stockPrice) {
                        $lowest_price = $stockPrice;
                    }
                    if ($highest_price < $stockPrice) {
                        $highest_price = $stockPrice;
                    }
                }
            }

            // Apply discount
            $discount_applicable = false;
            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            } elseif (
                strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
            ) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if ($product->discount_type == 'percent') {
                    $lowest_price -= ($lowest_price * $product->discount) / 100;
                    $highest_price -= ($highest_price * $product->discount) / 100;
                } elseif ($product->discount_type == 'amount') {
                    $lowest_price -= $product->discount;
                    $highest_price -= $product->discount;
                }
            }

            // Apply taxes
            if ($product->relationLoaded('taxes')) {
                $taxes = $product->taxes;
            } else {
                $taxes = $product->taxes()->get();
            }
            
            foreach ($taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $lowest_price += ($lowest_price * $product_tax->tax) / 100;
                    $highest_price += ($highest_price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $lowest_price += $product_tax->tax;
                    $highest_price += $product_tax->tax;
                }
            }

            if ($formatted) {
                if ($lowest_price == $highest_price) {
                    return format_price(convert_price($lowest_price));
                } else {
                    return format_price(convert_price($lowest_price)) . ' - ' . format_price(convert_price($highest_price));
                }
            } else {
                return $lowest_price . ' - ' . $highest_price;
            }
        });
    }
}

//Shows Base Price
if (!function_exists('home_base_price_by_stock_id')) {
    function home_base_price_by_stock_id($id)
    {
        $cacheKey = 'home_base_price_stock_' . $id . '_' . (getCurrentUserRole() ?? 'guest');
        
        return Cache::remember($cacheKey, now()->addHour(), function () use ($id) {
            $product_stock = ProductStock::with(['batches', 'product.taxes'])->findOrFail($id);
            
            // Use batch-aware pricing helper (no cache here, already cached at function level)
            $price = getStockPriceByRole($product_stock, $product_stock->product, false);
            $tax = 0;

            $taxes = $product_stock->product->taxes;
            foreach ($taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $tax += ($price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $tax += $product_tax->tax;
                }
            }
            $price += $tax;
            return format_price(convert_price($price));
        });
    }
}


if (!function_exists('home_base_price')) {
    function home_base_price($product, $formatted = true)
    {
        $cacheKey = 'home_base_price_' . $product->id . '_' . (getCurrentUserRole() ?? 'guest') . '_' . ($formatted ? 'fmt' : 'raw');
        
        return Cache::remember($cacheKey, now()->addHour(), function () use ($product, $formatted) {
            // For non-variant products, use product-level pricing
            // For variant products, find lowest price across all stocks/batches
            if ($product->variant_product) {
                if (!$product->relationLoaded('stocks')) {
                    $product->load(['stocks.batches']);
                }
                
                $lowestPrice = null;
                foreach ($product->stocks as $stock) {
                    $stockPrice = getStockPriceByRole($stock, $product, false);
                    if ($lowestPrice === null || $stockPrice < $lowestPrice) {
                        $lowestPrice = $stockPrice;
                    }
                }
                $price = $lowestPrice ?? getPriceByRole($product->role_price ?? null, $product->unit_price ?? 0);
            } else {
                $price = getPriceByRole($product->role_price ?? null, $product->mrp_price ?? $product->unit_price ?? 0);
            }
            
            $tax = 0;

            if ($product->relationLoaded('taxes')) {
                $taxes = $product->taxes;
            } else {
                $taxes = $product->taxes()->get();
            }
            
            foreach ($taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $tax += ($price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $tax += $product_tax->tax;
                }
            }
            $price += $tax;
            return $formatted ? format_price(convert_price($price)) : convert_price($price);
        });
    }
}

//Shows Base Price with discount
if (!function_exists('home_discounted_base_price_by_stock_id')) {
    function home_discounted_base_price_by_stock_id($id)
    {
        $cacheKey = 'home_discounted_base_price_stock_' . $id . '_' . (getCurrentUserRole() ?? 'guest');
        
        return Cache::remember($cacheKey, now()->addHour(), function () use ($id) {
            $product_stock = ProductStock::with(['batches', 'product.taxes'])->findOrFail($id);
            $product = $product_stock->product;
            
            // Use batch-aware pricing helper
            $price = getStockPriceByRole($product_stock, $product);
            $tax = 0;

            $discount_applicable = false;
            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            } elseif (
                strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
            ) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if ($product->discount_type == 'percent') {
                    $price -= ($price * $product->discount) / 100;
                } elseif ($product->discount_type == 'amount') {
                    $price -= $product->discount;
                }
            }

            $taxes = $product->taxes;
            foreach ($taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $tax += ($price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $tax += $product_tax->tax;
                }
            }
            $price += $tax;

            return format_price(convert_price($price));
        });
    }
}


//Shows Base Price with discount
if (!function_exists('home_discounted_base_price')) {
    function home_discounted_base_price($product, $formatted = true)
    {
        $cacheKey = 'home_discounted_base_price_' . $product->id . '_' . (getCurrentUserRole() ?? 'guest') . '_' . ($formatted ? 'fmt' : 'raw');
        
        return Cache::remember($cacheKey, now()->addHour(), function () use ($product, $formatted) {
            // For non-variant products, use product-level pricing
            // For variant products, find lowest price across all stocks/batches
            if ($product->variant_product) {
                if (!$product->relationLoaded('stocks')) {
                    $product->load(['stocks.batches']);
                }
                
                $lowestPrice = null;
                foreach ($product->stocks as $stock) {
                    $stockPrice = getStockPriceByRole($stock, $product, false);
                    if ($lowestPrice === null || $stockPrice < $lowestPrice) {
                        $lowestPrice = $stockPrice;
                    }
                }
                $price = $lowestPrice ?? getPriceByRole($product->role_price ?? null, $product->unit_price ?? 0);
            } else {
                $price = getPriceByRole($product->role_price ?? null, $product->unit_price ?? 0);
            }
            
            $tax = 0;

            $discount_applicable = false;
            if ($product->discount_start_date == null) {
                $discount_applicable = true;
            } elseif (
                strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
                strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
            ) {
                $discount_applicable = true;
            }

            if ($discount_applicable) {
                if ($product->discount_type == 'percent') {
                    $price -= ($price * $product->discount) / 100;
                } elseif ($product->discount_type == 'amount') {
                    $price -= $product->discount;
                }
            }

            if ($product->relationLoaded('taxes')) {
                $taxes = $product->taxes;
            } else {
                $taxes = $product->taxes()->get();
            }
            
            foreach ($taxes as $product_tax) {
                if ($product_tax->tax_type == 'percent') {
                    $tax += ($price * $product_tax->tax) / 100;
                } elseif ($product_tax->tax_type == 'amount') {
                    $tax += $product_tax->tax;
                }
            }
            $price += $tax;

            return $formatted ? format_price(convert_price($price)) : convert_price($price);
        });
    }
}

if (!function_exists('renderStarRating')) {
    function renderStarRating($rating, $maxRating = 5)
    {
        $fullStar = "<i class = 'las la-star active'></i>";
        $halfStar = "<i class = 'las la-star half'></i>";
        $emptyStar = "<i class = 'las la-star'></i>";
        $rating = $rating <= $maxRating ? $rating : $maxRating;

        $fullStarCount = (int)$rating;
        $halfStarCount = ceil($rating) - $fullStarCount;
        $emptyStarCount = $maxRating - $fullStarCount - $halfStarCount;

        $html = str_repeat($fullStar, $fullStarCount);
        $html .= str_repeat($halfStar, $halfStarCount);
        $html .= str_repeat($emptyStar, $emptyStarCount);
        echo $html;
    }
}

function translate($key, $lang = null, $addslashes = false)
{
    if ($lang == null) {
        $lang = App::getLocale();
    }
    
    $key = $key ?? '';
    $lang_key = preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($key)));

    $translations_en = Cache::rememberForever('translations-en', function () {
        return Translation::where('lang', 'en')->pluck('lang_value', 'lang_key')->toArray();
    });

    if (!isset($translations_en[$lang_key])) {
        $translation_def = new Translation;
        $translation_def->lang = 'en';
        $translation_def->lang_key = $lang_key;
        $translation_def->lang_value = str_replace(array("\r", "\n", "\r\n"), "", $key);
        $translation_def->save();
        Cache::forget('translations-en');
    }

    // return user session lang
    $translation_locale = Cache::rememberForever("translations-{$lang}", function () use ($lang) {
        return Translation::where('lang', $lang)->pluck('lang_value', 'lang_key')->toArray();
    });
    if (isset($translation_locale[$lang_key])) {
        return $addslashes ? addslashes(trim($translation_locale[$lang_key])) : trim($translation_locale[$lang_key]);
    }

    // return default lang if session lang not found
    $translations_default = Cache::rememberForever('translations-' . env('DEFAULT_LANGUAGE', 'en'), function () {
        return Translation::where('lang', env('DEFAULT_LANGUAGE', 'en'))->pluck('lang_value', 'lang_key')->toArray();
    });
    if (isset($translations_default[$lang_key])) {
        return $addslashes ? addslashes(trim($translations_default[$lang_key])) : trim($translations_default[$lang_key]);
    }

    // fallback to en lang
    if (!isset($translations_en[$lang_key])) {
        return trim($key);
    }
    return $addslashes ? addslashes(trim($translations_en[$lang_key])) : trim($translations_en[$lang_key]);
}

function remove_invalid_charcaters($str)
{
    $str = str_ireplace(array("\\"), '', $str);
    return str_ireplace(array('"'), '\"', $str);
}

if (!function_exists('translation_tables')) {
    function translation_tables($uniqueIdentifier)
    {
        $noTableAddons =  ['african_pg', 'paytm', 'pos_system'];
        if (!in_array($uniqueIdentifier, $noTableAddons)) {
            $addons = [];
            $addons['affiliate'] = ['affiliate_options', 'affiliate_configs', 'affiliate_users', 'affiliate_payments', 'affiliate_withdraw_requests', 'affiliate_logs', 'affiliate_stats'];
            $addons['auction'] = ['auction_product_bids'];
            $addons['club_point'] = ['club_points', 'club_point_details'];
            $addons['delivery_boy'] = ['delivery_boys', 'delivery_histories', 'delivery_boy_payments', 'delivery_boy_collections'];
            $addons['offline_payment'] = ['manual_payment_methods'];
            $addons['otp_system'] = ['otp_configurations', 'sms_templates'];
            $addons['refund_request'] = ['refund_requests'];
            $addons['seller_subscription'] = ['seller_packages', 'seller_package_translations', 'seller_package_payments'];
            $addons['wholesale'] = ['wholesale_prices'];

            foreach ($addons as $key => $addon_tables) {
                if ($key == $uniqueIdentifier) {
                    foreach ($addon_tables as $table) {
                        Schema::dropIfExists($table);
                    }
                }
            }
        }
    }
}

function getShippingCost($carts, $index, $shipping_info = '', $carrier = '')
{
    $shipping_type = get_setting('shipping_type');
    $admin_products = array();
    $seller_products = array();
    $admin_product_total_weight = 0;
    $admin_product_total_price = 0;
    $seller_product_total_weight = array();
    $seller_product_total_price = array();

    $cartItem = $carts[$index];
    $product = Product::find($cartItem['product_id']);

    if ($product->digital == 1) {
        return 0;
    }

    foreach ($carts as $key => $cart_item) {
        $item_product = Product::find($cart_item['product_id']);
        if ($item_product->added_by == 'admin') {
            array_push($admin_products, $cart_item['product_id']);

            // For carrier wise shipping
            if ($shipping_type == 'carrier_wise_shipping') {
                $admin_product_total_weight += ($item_product->weight * $cart_item['quantity']);
                $admin_product_total_price += (cart_product_price($cart_item, $item_product, false, false) * $cart_item['quantity']);
            }
        } else {
            $product_ids = array();
            $weight = 0;
            $price = 0;
            if (isset($seller_products[$item_product->user_id])) {
                $product_ids = $seller_products[$item_product->user_id];

                // For carrier wise shipping
                if ($shipping_type == 'carrier_wise_shipping') {
                    $weight += $seller_product_total_weight[$item_product->user_id];
                    $price += $seller_product_total_price[$item_product->user_id];
                }
            }

            array_push($product_ids, $cart_item['product_id']);
            $seller_products[$item_product->user_id] = $product_ids;

            // For carrier wise shipping
            if ($shipping_type == 'carrier_wise_shipping') {
                $weight += ($item_product->weight * $cart_item['quantity']);
                $seller_product_total_weight[$item_product->user_id] = $weight;

                $price += (cart_product_price($cart_item, $item_product, false, false) * $cart_item['quantity']);
                $seller_product_total_price[$item_product->user_id] = $price;
            }
        }
    }

    if ($shipping_type == 'flat_rate') {
        return get_setting('flat_rate_shipping_cost') / count($carts);
    } elseif ($shipping_type == 'seller_wise_shipping') {
        if ($product->added_by == 'admin') {
            return get_setting('shipping_cost_admin') / count($admin_products);
        } else {
            return Shop::where('user_id', $product->user_id)->first()->shipping_cost / count($seller_products[$product->user_id]);
        }
    } elseif ($shipping_type == 'area_wise_shipping') {
        $city = City::where('id', $shipping_info['city_id'])->first();

        if ($city != null) {
            if ($product->added_by == 'admin') {
                return $city->cost / count($admin_products);
            } else {
                return $city->cost / count($seller_products[$product->user_id]);
            }
        }
        return 0;
    } elseif ($shipping_type == 'carrier_wise_shipping') { // carrier wise shipping
        $user_zone = $shipping_info['country_id'] != 0 ? Country::where('id', $shipping_info['country_id'])->first()->zone_id : 0;

        if ($carrier == null || $user_zone == 0) {
            return 0;
        }

        $carrier = Carrier::find($carrier);
        if ($carrier->carrier_ranges->first()) {
            $carrier_billing_type   = $carrier->carrier_ranges->first()->billing_type;
            if ($product->added_by == 'admin') {
                $itemsWeightOrPrice = $carrier_billing_type == 'weight_based' ? $admin_product_total_weight : $admin_product_total_price;
            } else {
                $itemsWeightOrPrice = $carrier_billing_type == 'weight_based' ? $seller_product_total_weight[$product->user_id] : $seller_product_total_price[$product->user_id];
            }
        }

        foreach ($carrier->carrier_ranges as $carrier_range) {
            if ($itemsWeightOrPrice >= $carrier_range->delimiter1 && $itemsWeightOrPrice < $carrier_range->delimiter2) {
                $carrier_price = $carrier_range->carrier_range_prices->where('zone_id', $user_zone)->first()->price;
                return $product->added_by == 'admin' ? ($carrier_price / count($admin_products)) : ($carrier_price / count($seller_products[$product->user_id]));
            }
        }
        return 0;
    } else {
        if ($product->is_quantity_multiplied && ($shipping_type == 'product_wise_shipping')) {
            return  $product->shipping_cost * $cartItem['quantity'];
        }
        return $product->shipping_cost;
    }
}

//return carrier wise shipping cost against seller
if (!function_exists('carrier_base_price')) {
    function carrier_base_price($carts, $carrier_id, $owner_id, $shipping_info = '')
    {
        $shipping = 0;
        foreach ($carts as $key => $cartItem) {
            if ($cartItem->owner_id == $owner_id) {
                $shipping_cost = getShippingCost($carts, $key, $shipping_info, $carrier_id);
                $shipping += $shipping_cost;
            }
        }
        return $shipping;
    }
}

//return seller wise carrier list
if (!function_exists('seller_base_carrier_list')) {
    function seller_base_carrier_list($owner_id, $userId = null, $tempUserId= null, $shipping_info = null)
    {
        $carrier_list = array();
        $carts = ($userId != null) ? Cart::where('user_id', $userId)->active()->get() : Cart::where('temp_user_id', $tempUserId)->active()->get();
        if (count($carts) > 0) {
            $zone = $shipping_info['country_id'] ? Country::where('id', $shipping_info['country_id'])->first()->zone_id : null;
            $carrier_query = Carrier::query();
            $carrier_query->whereIn('id', function ($query) use ($zone) {
                $query->select('carrier_id')->from('carrier_range_prices')
                    ->where('zone_id', $zone);
            })->orWhere('free_shipping', 1);
            $carrier_list = $carrier_query->active()->get();
        }
        return (new CarrierCollection($carrier_list))->extra($owner_id, $carts, $shipping_info);
    }
}

function timezones()
{
    return array(
        '(GMT-12:00) International Date Line West' => 'Pacific/Kwajalein',
        '(GMT-11:00) Midway Island' => 'Pacific/Midway',
        '(GMT-11:00) Samoa' => 'Pacific/Apia',
        '(GMT-10:00) Hawaii' => 'Pacific/Honolulu',
        '(GMT-09:00) Alaska' => 'America/Anchorage',
        '(GMT-08:00) Pacific Time (US & Canada)' => 'America/Los_Angeles',
        '(GMT-08:00) Tijuana' => 'America/Tijuana',
        '(GMT-07:00) Arizona' => 'America/Phoenix',
        '(GMT-07:00) Mountain Time (US & Canada)' => 'America/Denver',
        '(GMT-07:00) Chihuahua' => 'America/Chihuahua',
        '(GMT-07:00) La Paz' => 'America/Chihuahua',
        '(GMT-07:00) Mazatlan' => 'America/Mazatlan',
        '(GMT-06:00) Central Time (US & Canada)' => 'America/Chicago',
        '(GMT-06:00) Central America' => 'America/Managua',
        '(GMT-06:00) Guadalajara' => 'America/Mexico_City',
        '(GMT-06:00) Mexico City' => 'America/Mexico_City',
        '(GMT-06:00) Monterrey' => 'America/Monterrey',
        '(GMT-06:00) Saskatchewan' => 'America/Regina',
        '(GMT-05:00) Eastern Time (US & Canada)' => 'America/New_York',
        '(GMT-05:00) Indiana (East)' => 'America/Indiana/Indianapolis',
        '(GMT-05:00) Bogota' => 'America/Bogota',
        '(GMT-05:00) Lima' => 'America/Lima',
        '(GMT-05:00) Quito' => 'America/Bogota',
        '(GMT-04:00) Atlantic Time (Canada)' => 'America/Halifax',
        '(GMT-04:00) Caracas' => 'America/Caracas',
        '(GMT-04:00) La Paz' => 'America/La_Paz',
        '(GMT-04:00) Santiago' => 'America/Santiago',
        '(GMT-03:30) Newfoundland' => 'America/St_Johns',
        '(GMT-03:00) Brasilia' => 'America/Sao_Paulo',
        '(GMT-03:00) Buenos Aires' => 'America/Argentina/Buenos_Aires',
        '(GMT-03:00) Georgetown' => 'America/Argentina/Buenos_Aires',
        '(GMT-03:00) Greenland' => 'America/Godthab',
        '(GMT-02:00) Mid-Atlantic' => 'America/Noronha',
        '(GMT-01:00) Azores' => 'Atlantic/Azores',
        '(GMT-01:00) Cape Verde Is.' => 'Atlantic/Cape_Verde',
        '(GMT) Casablanca' => 'Africa/Casablanca',
        '(GMT) Dublin' => 'Europe/London',
        '(GMT) Edinburgh' => 'Europe/London',
        '(GMT) Lisbon' => 'Europe/Lisbon',
        '(GMT) London' => 'Europe/London',
        '(GMT) UTC' => 'UTC',
        '(GMT) Monrovia' => 'Africa/Monrovia',
        '(GMT+01:00) Amsterdam' => 'Europe/Amsterdam',
        '(GMT+01:00) Belgrade' => 'Europe/Belgrade',
        '(GMT+01:00) Berlin' => 'Europe/Berlin',
        '(GMT+01:00) Bern' => 'Europe/Berlin',
        '(GMT+01:00) Bratislava' => 'Europe/Bratislava',
        '(GMT+01:00) Brussels' => 'Europe/Brussels',
        '(GMT+01:00) Budapest' => 'Europe/Budapest',
        '(GMT+01:00) Copenhagen' => 'Europe/Copenhagen',
        '(GMT+01:00) Ljubljana' => 'Europe/Ljubljana',
        '(GMT+01:00) Madrid' => 'Europe/Madrid',
        '(GMT+01:00) Paris' => 'Europe/Paris',
        '(GMT+01:00) Prague' => 'Europe/Prague',
        '(GMT+01:00) Rome' => 'Europe/Rome',
        '(GMT+01:00) Sarajevo' => 'Europe/Sarajevo',
        '(GMT+01:00) Skopje' => 'Europe/Skopje',
        '(GMT+01:00) Stockholm' => 'Europe/Stockholm',
        '(GMT+01:00) Vienna' => 'Europe/Vienna',
        '(GMT+01:00) Warsaw' => 'Europe/Warsaw',
        '(GMT+01:00) West Central Africa' => 'Africa/Lagos',
        '(GMT+01:00) Zagreb' => 'Europe/Zagreb',
        '(GMT+02:00) Athens' => 'Europe/Athens',
        '(GMT+02:00) Bucharest' => 'Europe/Bucharest',
        '(GMT+02:00) Cairo' => 'Africa/Cairo',
        '(GMT+02:00) Harare' => 'Africa/Harare',
        '(GMT+02:00) Helsinki' => 'Europe/Helsinki',
        '(GMT+02:00) Istanbul' => 'Europe/Istanbul',
        '(GMT+02:00) Jerusalem' => 'Asia/Jerusalem',
        '(GMT+02:00) Kyev' => 'Europe/Kiev',
        '(GMT+02:00) Minsk' => 'Europe/Minsk',
        '(GMT+02:00) Pretoria' => 'Africa/Johannesburg',
        '(GMT+02:00) Riga' => 'Europe/Riga',
        '(GMT+02:00) Sofia' => 'Europe/Sofia',
        '(GMT+02:00) Tallinn' => 'Europe/Tallinn',
        '(GMT+02:00) Vilnius' => 'Europe/Vilnius',
        '(GMT+03:00) Baghdad' => 'Asia/Baghdad',
        '(GMT+03:00) Kuwait' => 'Asia/Kuwait',
        '(GMT+03:00) Moscow' => 'Europe/Moscow',
        '(GMT+03:00) Nairobi' => 'Africa/Nairobi',
        '(GMT+03:00) Riyadh' => 'Asia/Riyadh',
        '(GMT+03:00) St. Petersburg' => 'Europe/Moscow',
        '(GMT+03:00) Volgograd' => 'Europe/Volgograd',
        '(GMT+03:30) Tehran' => 'Asia/Tehran',
        '(GMT+04:00) Abu Dhabi' => 'Asia/Muscat',
        '(GMT+04:00) Baku' => 'Asia/Baku',
        '(GMT+04:00) Muscat' => 'Asia/Muscat',
        '(GMT+04:00) Tbilisi' => 'Asia/Tbilisi',
        '(GMT+04:00) Yerevan' => 'Asia/Yerevan',
        '(GMT+04:30) Kabul' => 'Asia/Kabul',
        '(GMT+05:00) Ekaterinburg' => 'Asia/Yekaterinburg',
        '(GMT+05:00) Islamabad' => 'Asia/Karachi',
        '(GMT+05:00) Karachi' => 'Asia/Karachi',
        '(GMT+05:00) Tashkent' => 'Asia/Tashkent',
        '(GMT+05:30) Chennai' => 'Asia/Kolkata',
        '(GMT+05:30) Kolkata' => 'Asia/Kolkata',
        '(GMT+05:30) Mumbai' => 'Asia/Kolkata',
        '(GMT+05:30) New Delhi' => 'Asia/Kolkata',
        '(GMT+05:45) Kathmandu' => 'Asia/Kathmandu',
        '(GMT+06:00) Almaty' => 'Asia/Almaty',
        '(GMT+06:00) Astana' => 'Asia/Dhaka',
        '(GMT+06:00) Dhaka' => 'Asia/Dhaka',
        '(GMT+06:00) Novosibirsk' => 'Asia/Novosibirsk',
        '(GMT+06:00) Sri Jayawardenepura' => 'Asia/Colombo',
        '(GMT+06:30) Rangoon' => 'Asia/Rangoon',
        '(GMT+07:00) Bangkok' => 'Asia/Bangkok',
        '(GMT+07:00) Hanoi' => 'Asia/Bangkok',
        '(GMT+07:00) Jakarta' => 'Asia/Jakarta',
        '(GMT+07:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
        '(GMT+08:00) Beijing' => 'Asia/Hong_Kong',
        '(GMT+08:00) Chongqing' => 'Asia/Chongqing',
        '(GMT+08:00) Hong Kong' => 'Asia/Hong_Kong',
        '(GMT+08:00) Irkutsk' => 'Asia/Irkutsk',
        '(GMT+08:00) Kuala Lumpur' => 'Asia/Kuala_Lumpur',
        '(GMT+08:00) Perth' => 'Australia/Perth',
        '(GMT+08:00) Singapore' => 'Asia/Singapore',
        '(GMT+08:00) Taipei' => 'Asia/Taipei',
        '(GMT+08:00) Ulaan Bataar' => 'Asia/Irkutsk',
        '(GMT+08:00) Urumqi' => 'Asia/Urumqi',
        '(GMT+09:00) Osaka' => 'Asia/Tokyo',
        '(GMT+09:00) Sapporo' => 'Asia/Tokyo',
        '(GMT+09:00) Seoul' => 'Asia/Seoul',
        '(GMT+09:00) Tokyo' => 'Asia/Tokyo',
        '(GMT+09:00) Yakutsk' => 'Asia/Yakutsk',
        '(GMT+09:30) Adelaide' => 'Australia/Adelaide',
        '(GMT+09:30) Darwin' => 'Australia/Darwin',
        '(GMT+10:00) Brisbane' => 'Australia/Brisbane',
        '(GMT+10:00) Canberra' => 'Australia/Sydney',
        '(GMT+10:00) Guam' => 'Pacific/Guam',
        '(GMT+10:00) Hobart' => 'Australia/Hobart',
        '(GMT+10:00) Melbourne' => 'Australia/Melbourne',
        '(GMT+10:00) Port Moresby' => 'Pacific/Port_Moresby',
        '(GMT+10:00) Sydney' => 'Australia/Sydney',
        '(GMT+10:00) Vladivostok' => 'Asia/Vladivostok',
        '(GMT+11:00) Magadan' => 'Asia/Magadan',
        '(GMT+11:00) New Caledonia' => 'Asia/Magadan',
        '(GMT+11:00) Solomon Is.' => 'Asia/Magadan',
        '(GMT+12:00) Auckland' => 'Pacific/Auckland',
        '(GMT+12:00) Fiji' => 'Pacific/Fiji',
        '(GMT+12:00) Kamchatka' => 'Asia/Kamchatka',
        '(GMT+12:00) Marshall Is.' => 'Pacific/Fiji',
        '(GMT+12:00) Wellington' => 'Pacific/Auckland',
        '(GMT+13:00) Nuku\'alofa' => 'Pacific/Tongatapu'
    );
}

if (!function_exists('app_timezone')) {
    function app_timezone()
    {
        return config('app.timezone');
    }
}

//return file uploaded via uploader
if (!function_exists('uploaded_asset')) {
    function uploaded_asset($id)
    {
        if (empty($id)) {
            return static_asset('assets/img/placeholder.jpg');
        }

        $cacheKey = 'uploaded_asset_url_' . $id;

        return Cache::rememberForever($cacheKey, function () use ($id) {
            $asset = Upload::withoutGlobalScopes(['not_hidden'])->find($id);
            if (!$asset) {
                return static_asset('assets/img/placeholder.jpg');
            }

            return $asset->external_link == null ? my_asset($asset->file_name) : $asset->external_link;
        });
    }
}

if (!function_exists('check_asset_type')) {
    function check_asset_type($id)
    {
        if (empty($id)) {
            return 'image';
        }

        $cacheKey = 'asset_type_' . $id;

        return Cache::rememberForever($cacheKey, function () use ($id) {
            $asset = Upload::find($id);

            if ($asset) {
                $file_type = $asset->file_name;
                $ext = strtolower(pathinfo($file_type, PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                    return 'image';
                } elseif (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                    return 'video';
                } else {
                    return 'file';
                }
            }

            return 'image'; // default fallback
        });
    }
}

if (!function_exists('my_asset')) {
    /**
     * Resolve an upload to its URL based on stored disk and environment.
     * Rules:
     *  - disk = s3  => AWS_URL + path (fallback: Storage URL)
     *  - disk = local & APP_ENV=local => domain + path
     *  - disk = local & APP_ENV=production => domain + /public + path
     */
    function my_asset($value)
    {
        // Absolute URLs stay as-is
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        if (empty($value)) {
            return static_asset('assets/img/placeholder.jpg');
        }

        $cacheKey = is_numeric($value)
            ? 'my_asset_url_id_' . $value
            : 'my_asset_url_name_' . md5((string) $value);

        return Cache::rememberForever($cacheKey, function () use ($value) {
            $upload = is_numeric($value)
                ? Upload::withoutGlobalScopes(['not_hidden'])->find($value)
                : Upload::withoutGlobalScopes(['not_hidden'])->where('file_name', $value)->first();

            if (!$upload) {
                return static_asset('assets/img/placeholder.jpg');
            }

            if (!empty($upload->external_link)) {
                return $upload->external_link;
            }

            $disk = $upload->disk ?: config('filesystems.default');
            $path = ltrim($upload->file_name, '/');

            if ($disk === 'local') {
                return app()->environment('local')
                    ? asset($path)
                    : asset('public/' . $path);
            }

            // Non-local (e.g., s3)
            $bucketUrl = env(Str::upper($disk) . '_URL') ?? env('AWS_URL');
            if (!empty($bucketUrl)) {
                return rtrim($bucketUrl, '/') . '/' . $path;
            }

            return Storage::disk($disk)->url($path);
        });
    }
}

if (!function_exists('static_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function static_asset($path, $secure = null)
    {
        if(app()->environment('production')){
            return app('url')->asset('public/' . $path, $secure); //for production environment
        }else{
            return app('url')->asset('/' . $path, $secure); //for production environment
        }
        //return app('url')->asset('public/' . $path, $secure);
    }
}


// Get admin-curated related products (falls back to category peers)
if (!function_exists('get_related_products')) {
    function get_related_products($product, $limit = 10)
    {
        $selectionType = $product->frequently_bought_selection_type ?? null;
        $products = collect();

        if ($selectionType === 'product') {
            $relatedIds = $product->frequently_bought_products()
                ->whereNull('category_id')
                ->pluck('frequently_bought_product_id')
                ->toArray();

            if (!empty($relatedIds)) {
                $products = filter_products(Product::whereIn('id', $relatedIds))->limit($limit)->get();
            }
        } elseif ($selectionType === 'category') {
            $categoryId = optional($product->frequently_bought_products()->whereNotNull('category_id')->first())->category_id;

            if ($categoryId) {
                $category = Category::find($categoryId);
                if ($category) {
                    $query = $category->products()->where('id', '!=', $product->id);
                    $query = $product->added_by == 'admin' ? $query->where('added_by', 'admin') : $query->where('user_id', $product->user_id);

                    $products = filter_products($query)->inRandomOrder()->limit($limit)->get();
                }
            }
        }

        if ($products->isNotEmpty()) {
            return $products;
        }

        $categoryIds = collect([$product->category_id])
            ->merge($product->categories()->pluck('categories.id'))
            ->filter()
            ->unique()
            ->values();

        if ($categoryIds->isEmpty()) {
            return collect([]);
        }

        $query = Product::where('id', '!=', $product->id)
            ->where(function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds)
                    ->orWhereIn('id', function ($sub) use ($categoryIds) {
                        $sub->from('product_categories')
                            ->select('product_id')
                            ->whereIn('category_id', $categoryIds);
                    });
            });

        return filter_products($query)->inRandomOrder()->limit($limit)->get();
    }
}


// Get similar products: prioritize sub-category peers; if product is in a main category, show main-category peers
if (!function_exists('get_similar_products')) {
    function get_similar_products($product, $limit = 12)
    {
        $excludeIds = array_unique([$product->id]);

        $category = $product->category_id ? Category::find($product->category_id) : null;
        if (!$category) {
            return collect([]);
        }

        // if product is in a main category (no parent), show peers from that main category
        // otherwise show peers from its specific sub-category (and any additional pivot categories)
        $categoryIds = collect([$product->category_id])
            ->merge($product->categories()->pluck('categories.id'))
            ->filter()
            ->unique()
            ->values();

        if ($categoryIds->isEmpty()) {
            return collect([]);
        }

        $query = Product::where(function ($q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds)
                ->orWhereIn('id', function ($sub) use ($categoryIds) {
                    $sub->from('product_categories')
                        ->select('product_id')
                        ->whereIn('category_id', $categoryIds);
                });
        })->whereNotIn('id', $excludeIds);

        return filter_products($query)->inRandomOrder()->limit($limit)->get();
    }
}

// Get products from the same category set (used for "More From" carousel)
if (!function_exists('get_brand_related_products')) {
    function get_brand_related_products($product, $limit = 12)
    {
        $categoryIds = collect([$product->category_id])
            ->merge($product->categories()->pluck('categories.id'))
            ->filter()
            ->unique()
            ->values();

        if ($categoryIds->isEmpty()) {
            return collect([]);
        }

        $query = Product::where('id', '!=', $product->id)
            ->where(function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds)
                    ->orWhereIn('id', function ($sub) use ($categoryIds) {
                        $sub->from('product_categories')
                            ->select('product_id')
                            ->whereIn('category_id', $categoryIds);
                    });
            });

        return filter_products($query)->inRandomOrder()->limit($limit)->get();
    }
}


// if (!function_exists('isHttps')) {
//     function isHttps()
//     {
//         return !empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS']);
//     }
// }

if (!function_exists('getBaseURL')) {
    function getBaseURL()
    {
        $root = '//' . $_SERVER['HTTP_HOST'];

        if(env('ENVIRONMENT') == "Production"){
            $root .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        }
        
        return $root;
    }
}


if (!function_exists('getFileBaseURL')) {
    function getFileBaseURL()
    {
        // Local: keep existing behaviour
        if (app()->environment('local')) {
            return getBaseURL() . '/';
        }

        // Non-local drivers (e.g., s3/backblaze)
        if (env('FILESYSTEM_DRIVER') != 'local') {
            $diskUrl = env(Str::upper(env('FILESYSTEM_DRIVER')) . '_URL');

            // Fallback to app URL if the disk URL is missing
            if (empty($diskUrl)) {
                return rtrim(getBaseURL(), '/') . '/public/';
            }

            return rtrim($diskUrl, '/') . '/';
        }

        // Default local (production with local driver)
        return rtrim(getBaseURL(), '/') . '/public/';
    }
}


if (!function_exists('isUnique')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function isUnique($email)
    {
        $user = \App\Models\User::where('email', $email)->first();

        if ($user == null) {
            return '1'; // $user = null means we did not get any match with the email provided by the user inside the database
        } else {
            return '0';
        }
    }
}

if (!function_exists('get_setting')) {
    function get_setting($key, $default = null, $lang = false)
    {
        $settings = Cache::remember('business_settings', 86400, function () {
            return BusinessSetting::all();
        });

        if ($lang == false) {
            $setting = $settings->where('type', $key)->first();
        } else {
            $setting = $settings->where('type', $key)->where('lang', $lang)->first();
            $setting = !$setting ? $settings->where('type', $key)->first() : $setting;
        }
        return $setting == null ? $default : $setting->value;
    }
}

function hex2rgba($color, $opacity = false)
{
    return (new ColorCodeConverter())->convertHexToRgba($color, $opacity);
}

if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        if (Auth::check() && (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('isSeller')) {
    function isSeller()
    {
        if (Auth::check() && Auth::user()->user_type == 'seller') {
            return true;
        }
        return false;
    }
}

if (!function_exists('isCustomer')) {
    function isCustomer()
    {
        if (Auth::check() && Auth::user()->user_type == 'customer') {
            return true;
        }
        return false;
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// duplicates m$ excel's ceiling function
if (!function_exists('ceiling')) {
    function ceiling($number, $significance = 1)
    {
        return (is_numeric($number) && is_numeric($significance)) ? (ceil($number / $significance) * $significance) : false;
    }
}

//for api
if (!function_exists('get_images_path')) {
    function get_images_path($given_ids, $with_trashed = false)
    {
        $paths = [];
        foreach (explode(',', $given_ids) as $id) {
            $paths[] = uploaded_asset($id);
        }

        return $paths;
    }
}

//for api
if (!function_exists('checkout_done')) {
    function checkout_done($combined_order_id, $payment)
    {
        $combined_order = CombinedOrder::find($combined_order_id);

        foreach ($combined_order->orders as $key => $order) {
            $order->payment_status = 'paid';
            $order->payment_details = $payment;
            $order->save();

            // Order paid notification to Customer, Seller, & Admin
            EmailUtility::order_email($order, 'paid'); 
            
            try {
                NotificationUtility::sendOrderPlacedNotification($order);
                calculateCommissionAffilationClubPoint($order);
            } catch (\Exception $e) {
            }
        }
    }
}

// get user total ordered products
if (!function_exists('get_user_total_ordered_products')) {
    function get_user_total_ordered_products()
    {
        $orders_query = Order::query();
        $orders       = $orders_query->where('user_id', Auth::user()->id)->get();
        $total        = 0;
        foreach ($orders as $order) {
            $total += count($order->orderDetails);
        }
        return $total;
    }
}

//for api
if (!function_exists('order_re_payment_done')) {
    function order_re_payment_done($order_id, $payment_method, $payment_details)
    {
        $order = Order::findOrFail($order_id);
        $order->payment_status = 'paid';
        $order->payment_details = $payment_details;
        $order->payment_type = $payment_method;
        $order->save();
        calculateCommissionAffilationClubPoint($order);

        if($order->notified == 0){
            NotificationUtility::sendOrderPlacedNotification($order);
            $order->notified = 1;
            $order->save();
        }

    }
}

//for api - Order Re Payment Done
if (!function_exists('wallet_payment_done')) {
    function wallet_payment_done($user_id, $amount, $payment_method, $payment_details)
    {
        $user = \App\Models\User::find($user_id);
        $user->balance = $user->balance + $amount;
        $user->save();

        $wallet = new Wallet;
        $wallet->user_id = $user->id;
        $wallet->amount = $amount;
        $wallet->payment_method = $payment_method;
        $wallet->payment_details = $payment_details;
        $wallet->save();
    }
}

// if (!function_exists('purchase_payment_done')) {
//     function purchase_payment_done($user_id, $package_id)
//     {
//         $user = User::findOrFail($user_id);
//         $user->customer_package_id = $package_id;
//         $customer_package = CustomerPackage::findOrFail($package_id);
//         $user->remaining_uploads += $customer_package->product_upload;
//         $user->save();

//         return 'success';
//     }
// }

if (!function_exists('seller_purchase_payment_done')) {
    function seller_purchase_payment_done($user_id, $seller_package_id, $payment_method, $payment_details)
    {
        $seller = Shop::where('user_id', $user_id)->first();
        $seller->seller_package_id = $seller_package_id;
        $seller_package = SellerPackage::findOrFail($seller_package_id);
        $seller->product_upload_limit = $seller_package->product_upload_limit;
        $seller->package_invalid_at = date('Y-m-d', strtotime($seller->package_invalid_at . ' +' . $seller_package->duration . 'days'));
        $seller->save();

        $seller_package = new SellerPackagePayment();
        $seller_package->user_id = $user_id;
        $seller_package->seller_package_id = $seller_package_id;
        $seller_package->payment_method = $payment_method;
        $seller_package->payment_details = $payment_details;
        $seller_package->approval = 1;
        $seller_package->offline_payment = 2;
        $seller_package->save();
    }
}

if (!function_exists('customer_purchase_payment_done')) {
    function customer_purchase_payment_done($user_id, $customer_package_id, $payment_method, $payment_details)
    {
        $user = User::findOrFail($user_id);
        $user->customer_package_id = $customer_package_id;
        $customer_package = CustomerPackage::findOrFail($customer_package_id);
        $user->remaining_uploads += $customer_package->product_upload;
        $user->save();

        $customer_package_payment = new CustomerPackagePayment();
        $customer_package_payment->user_id = $user->id;
        $customer_package_payment->customer_package_id = $customer_package_id;
        $customer_package_payment->amount = $customer_package->amount;
        $customer_package_payment->payment_method = $payment_method;
        $customer_package_payment->payment_details = $payment_details;
        $customer_package_payment->save();
    }
}

if (!function_exists('product_restock')) {
    function product_restock($orderDetail)
    {
        $variant = $orderDetail->variation;
        if ($orderDetail->variation == null) {
            $variant = '';
        }

        $product_stock = ProductStock::where('product_id', $orderDetail->product_id)
            ->where('variant', $variant)
            ->first();

        if ($product_stock != null && (!in_array($orderDetail->delivery_status, ['delivered', 'cancelled']))) {
            $product = $product_stock->product;
            $product->num_of_sale -= $orderDetail->quantity;
            $product->save();

            $product_stock->qty += $orderDetail->quantity;
            $product_stock->save();
        }
    }
}

//Commission Calculation
if (!function_exists('calculateCommissionAffilationClubPoint')) {
    function calculateCommissionAffilationClubPoint($order)
    {
        (new CommissionController)->calculateCommission($order);

        if (addon_is_activated('affiliate_system')) {
            if (class_exists('App\\Http\\Controllers\\AffiliateController')) {
                $affiliateController = app()->make('App\\Http\\Controllers\\AffiliateController');
                if (method_exists($affiliateController, 'processAffiliatePoints')) {
                    $affiliateController->processAffiliatePoints($order);
                }
            }
        }

        if (addon_is_activated('club_point')) {
            if ($order->user != null) {
                if (class_exists('App\\Http\\Controllers\\ClubPointController')) {
                    $clubPointController = app()->make('App\\Http\\Controllers\\ClubPointController');
                    if (method_exists($clubPointController, 'processClubPoints')) {
                        $clubPointController->processClubPoints($order);
                    }
                }
            }
        }

        $order->commission_calculated = 1;
        $order->save();
    }
}

// Addon Activation Check
if (!function_exists('addon_is_activated')) {
    function addon_is_activated($identifier, $default = null)
    {
        $addons = Cache::remember('addons', 86400, function () {
            return Addon::all();
        });

        $activation = $addons->where('unique_identifier', $identifier)->where('activated', 1)->first();
        return $activation == null ? false : true;
    }
}

// Addon Activation Check
if (!function_exists('seller_package_validity_check')) {
    function seller_package_validity_check($user_id = null)
    {
        $user = $user_id == null ? \App\Models\User::find(Auth::user()->id) : \App\Models\User::find($user_id);
        $shop = $user->shop;
        $package_validation = false;
        if (
            $shop->product_upload_limit > $shop->user->products()->count()
            && $shop->package_invalid_at != null
            && Carbon::now()->diffInDays(Carbon::parse($shop->package_invalid_at), false) >= 0
        ) {
            $package_validation = true;
        }

        return $package_validation;
        // Ture = Seller package is valid and seller has the product upload limit
        // False = Seller package is invalid or seller product upload limit exists.
    }
}

// Get URL params
if (!function_exists('get_url_params')) {
    function get_url_params($url, $key)
    {
        $query_str = parse_url($url, PHP_URL_QUERY);
        parse_str($query_str, $query_params);

        return $query_params[$key] ?? '';
    }
}

// get Admin
if (!function_exists('get_admin')) {
    function get_admin()
    {
        $admin_query = User::query();
        return $admin_query->where('user_type', 'admin')->first();
    }
}

// Get slider images
if (!function_exists('get_slider_images')) {
    function get_slider_images($ids)
    {
        $ids = array_values(array_filter((array) $ids));
        if (empty($ids)) {
            return collect();
        }

        $cacheKey = 'slider_images_' . md5(implode('_', $ids));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($ids) {
            $slider_query = Upload::query();
            $sliders = $slider_query->whereIn('id', $ids);
            foreach ($ids as $id) {
                $sliders->orderByRaw("id!=?", [$id]);
            }
            return $sliders->get();
        });
    }
}

if (!function_exists('get_featured_flash_deal')) {
    function get_featured_flash_deal()
    {
        return Cache::remember('featured_flash_deal_active', now()->addHours(6), function () {
            $flash_deal_query = FlashDeal::query();
            return $flash_deal_query->isActiveAndFeatured()
                ->where('start_date', '<=', strtotime(date('Y-m-d H:i:s')))
                ->where('end_date', '>=', strtotime(date('Y-m-d H:i:s')))
                ->first();
        });
    }
}

if (!function_exists('get_flash_deal_products')) {
    function get_flash_deal_products($flash_deal_id)
    {
        $flash_deal_product_query = FlashDealProduct::query();
        $flash_deal_product_query->where('flash_deal_id', $flash_deal_id);
        $flash_deal_products = $flash_deal_product_query->with('product')->orderBy('id', 'desc')->limit(10)->get();

        return $flash_deal_products;
    }
}

if (!function_exists('get_active_flash_deals')) {
    function get_active_flash_deals()
    {
        $activated_flash_deal_query = FlashDeal::query();
        $activated_flash_deal_query = $activated_flash_deal_query->where("status", 1);

        return $activated_flash_deal_query->get();
    }
}

if (!function_exists('get_active_taxes')) {
    function get_active_taxes()
    {
        $activated_tax_query = Tax::query();
        $activated_tax_query = $activated_tax_query->where("tax_status", 1);

        return $activated_tax_query->get();
    }
}

if (!function_exists('get_system_language')) {
    function get_system_language()
    {
        $locale = 'en';
        if (Session::has('locale')) {
            $locale = Session::get('locale', Config::get('app.locale'));
        }

        $cacheKey = 'system_language_' . $locale;

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($locale) {
            $language_query = Language::query();
            return $language_query->where('code', $locale)->first();
        });
    }
}

if (!function_exists('get_all_active_language')) {
    function get_all_active_language()
    {
        $language_query = Language::query();
        $language_query->where('status', 1);

        return $language_query->get();
    }
}

// get Session langauge
if (!function_exists('get_session_language')) {
    function get_session_language()
    {
        $locale = Session::get('locale', Config::get('app.locale'));
        $cacheKey = 'session_language_' . $locale;

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($locale) {
            $language_query = Language::query();
            return $language_query->where('code', $locale)->first();
        });
    }
}

if (!function_exists('get_system_currency')) {
    function get_system_currency()
    {
        $currencyCode = Session::get('currency_code');
        $cacheKey = $currencyCode
            ? 'system_currency_code_' . $currencyCode
            : 'system_currency_default';

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($currencyCode) {
            $currency_query = Currency::query();
            if ($currencyCode) {
                $currency_query->where('code', $currencyCode);
            } else {
                $currency_query->where('id', get_setting('system_default_currency'));
            }

            return $currency_query->first();
        });
    }
}

if (!function_exists('get_all_active_currency')) {
    function get_all_active_currency()
    {
        return Cache::remember('all_active_currency', now()->addHours(6), function () {
            $currency_query = Currency::query();
            $currency_query->where('status', 1);
            return $currency_query->get();
        });
    }
}

if (!function_exists('get_single_product')) {
    function get_single_product($product_id)
    {
        $product_query = Product::query()->with('thumbnail');
        return $product_query->find($product_id);
    }
}

// get multiple Products
if (!function_exists('get_multiple_products')) {
    function get_multiple_products($product_ids)
    {
        $products_query = Product::query();
        return $products_query->whereIn('id', $product_ids)->get();
    }
}

// get count of products
if (!function_exists('get_products_count')) {
    function get_products_count($user_id = null)
    {
        $products_query = Product::query();
        if ($user_id) {
            $products_query = $products_query->where('user_id', $user_id);
        }
        return $products_query->isApprovedPublished()->count();
    }
}

// get minimum unit price of products
if (!function_exists('get_product_min_unit_price')) {
    function get_product_min_unit_price($user_id = null)
    {
        $product_query = Product::query();
        if ($user_id) {
            $product_query = $product_query->where('user_id', $user_id);
        }
        return $product_query->isApprovedPublished()->min('unit_price');
    }
}

// get maximum unit price of products
if (!function_exists('get_product_max_unit_price')) {
    function get_product_max_unit_price($user_id = null)
    {
        $product_query = Product::query();
        if ($user_id) {
            $product_query = $product_query->where('user_id', $user_id);
        }
        return $product_query->isApprovedPublished()->max('unit_price');
    }
}

if (!function_exists('get_featured_products')) {
    function get_featured_products()
    {
        return Cache::remember('featured_products', 3600, function () {
            $product_query = Product::query();
            return filter_products($product_query->where('featured', '1'))->latest()->limit(12)->get();
        });
    }
}

if (!function_exists('get_best_selling_products')) {
    function get_best_selling_products($limit, $user_id = null)
    {
        $cacheKey = 'best_selling_products_' . ($user_id ?: 'all') . '_' . $limit;

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($limit, $user_id) {
            $product_query = Product::query();
            if ($user_id) {
                $product_query = $product_query->where('user_id', $user_id);
            }
            return filter_products($product_query->orderBy('num_of_sale', 'desc'))->limit($limit)->get();
        });
    }
}

// Get Seller Products
if (!function_exists('get_seller_products')) {
    function get_seller_products($user_id)
    {
        $cacheKey = 'seller_products_' . $user_id;

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($user_id) {
            $product_query = Product::query();
            return $product_query->where('user_id', $user_id)->isApprovedPublished()->orderBy('created_at', 'desc')->limit(15)->get();
        });
    }
}

// Get Seller Best Selling Products
if (!function_exists('get_shop_best_selling_products')) {
    function get_shop_best_selling_products($user_id)
    {
        $page = request()->get('page', 1);
        $cacheKey = 'shop_best_selling_products_' . $user_id . '_p' . $page;

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($user_id) {
            $product_query = Product::query();
            return $product_query->where('user_id', $user_id)->isApprovedPublished()->orderBy('num_of_sale', 'desc')->paginate(24);
        });
    }
}

// Get all auction Products
if (!function_exists('get_all_auction_products')) {
    function get_auction_products($limit = null, $paginate = null)
    {
        $product_query = Product::query();
        $products = $product_query->latest()->isApprovedPublished()->where('auction_product', 1);
        if (get_setting('seller_auction_product') == 0) {
            $products = $products->where('added_by', 'admin');
        }
        $products = $products->where('auction_start_date', '<=', strtotime("now"))->where('auction_end_date', '>=', strtotime("now"));

        if ($limit) {
            $products = $products->limit($limit);
        } elseif ($paginate) {
            return $products->paginate($paginate);
        }
        return $products->get();
    }
}

//Get similiar classified products
if (!function_exists('get_similiar_classified_products')) {
    function get_similiar_classified_products($category_id = '', $product_id = '', $limit = '')
    {
        $classified_product_query = CustomerProduct::query();
        if ($category_id) {
            $classified_product_query->where('category_id', $category_id);
        }
        if ($product_id) {
            $classified_product_query->where('id', '!=', $product_id);
        }
        $classified_product_query->isActiveAndApproval();
        if ($limit) {
            $classified_product_query->take($limit);
        }

        return $classified_product_query->get();
    }
}

//Get home page classified products
if (!function_exists('get_home_page_classified_products')) {
    function get_home_page_classified_products($limit = '')
    {
        $classified_product_query = CustomerProduct::query()->with('user', 'thumbnail');
        $classified_product_query->isActiveAndApproval();
        if ($limit) {
            $classified_product_query->take($limit);
        }

        return $classified_product_query->get();
    }
}

// Customers Last viewed Products
if (!function_exists('lastViewedProducts')) {
    function lastViewedProducts($product_id, $user_id)
    {
        $lastViewedProduct = LastViewedProduct::firstOrCreate([
            'user_id' => $user_id,
            'product_id' => $product_id
        ]);
        $lastViewedProduct->touch();

        $lastViewedProductsCount = LastViewedProduct::where('user_id', $user_id)->count();
        if($lastViewedProductsCount > 12) {
            $deleteRow = $lastViewedProductsCount - 12;
            LastViewedProduct::where('user_id', $user_id)->take($deleteRow)->delete();
        }
    }
}

// get auth users last viewed Products
if (!function_exists('getLastViewedProducts')) {
    function getLastViewedProducts()
    {
        $verified_sellers = verified_sellers_id();

        $lastViewedProduct = LastViewedProduct::where('user_id', auth()->user()->id)->orderBy('updated_at','desc')
                                ->whereIn("product_id", function ($query) use ($verified_sellers) {
                                    $query->select('id')
                                        ->from('products')
                                        ->where('approved', '1')->where('published', 1)
                                        ->when(!addon_is_activated('wholesale') ,function ($q1){
                                            $q1->where('wholesale_product', 0);
                                        })
                                        ->when(!addon_is_activated('auction') ,function ($q2){
                                            $q2->where('auction_product', 0);
                                        })
                                        ->when(get_setting('vendor_system_activation') == 0 ,function ($q3){
                                            $q3->where('added_by', 'admin');
                                        })
                                        ->when(get_setting('vendor_system_activation') == 1 ,function ($q4) use ($verified_sellers){
                                            $q4->where(function ($p1) use ($verified_sellers) {
                                                $p1->where('added_by', 'admin')->orWhere(function ($p2) use ($verified_sellers) {
                                                    $p2->whereIn('user_id', $verified_sellers);
                                                });
                                            });
                                        });
                                })->get();

        return $lastViewedProduct;
    }
}

// Get related product
if (!function_exists('get_frequently_bought_products')) {
    function get_frequently_bought_products($product)
    {
        $productSelectionType = $product->frequently_bought_selection_type;
        $fqbProducts = [];
        if($productSelectionType == 'product'){
            $fqbProductIds = $product->frequently_bought_products()->where('category_id', null)->pluck('frequently_bought_product_id')->toArray();

            $fqbProducts = filter_products(Product::whereIn('id', $fqbProductIds))->get();

        }
        elseif($productSelectionType == 'category'){
            $fqb_product_category = $product->frequently_bought_products()->where('category_id','!=', null)->first();
            $fqbCategoryID = $fqb_product_category != null ? $fqb_product_category->category_id : null;
            if($fqbCategoryID != null){
                $category = Category::with('childrenCategories')->find($fqbCategoryID);

                if($category) {
                    $fqbProducts = $category->products()->where('id','!=',$product->id);
                    $fqbProducts = $product->added_by == 'admin' ? $fqbProducts->where('added_by', 'admin') : $fqbProducts->where('user_id', $product->user_id);

                    $fqbProducts = filter_products($fqbProducts)->orderByRaw('RAND()')->take(10)->get();
                }

            }
        }
        return $fqbProducts;
    }
}

// Get all brands
if (!function_exists('get_all_brands')) {
    function get_all_brands()
    {
        $brand_query = Brand::query();
        return  $brand_query->get();
    }
}

// Get single brands
if (!function_exists('get_brands')) {
    function get_brands($brand_ids)
    {
        $brand_query = Brand::query();
        $brands = $brand_query->whereIn('id', $brand_ids)->get();
        return $brands;
    }
}

// Get single brands
if (!function_exists('get_single_brand')) {
    function get_single_brand($brand_id)
    {
        $brand_query = Brand::query();
        return $brand_query->find($brand_id);
    }
}

// Get Brands by products
if (!function_exists('get_brands_by_products')) {
    function get_brands_by_products($usrt_id)
    {
        $product_query = Product::query();
        $brand_ids =  $product_query->where('user_id', $usrt_id)->isApprovedPublished()->whereNotNull('brand_id')->pluck('brand_id')->toArray();

        $brand_query = Brand::query();
        return $brand_query->whereIn('id', $brand_ids)->get();
    }
}

// Get category
if (!function_exists('get_category')) {
    function get_category($category_ids)
    {
        $category_query = Category::query();
        $category_query->with('coverImage');

        $category_query->whereIn('id', $category_ids);

        $categories = $category_query->get();
        return $categories;
    }
}

// Get single category
if (!function_exists('get_single_category')) {
    function get_single_category($category_id)
    {
        $category_query = Category::query()->with('coverImage');
        return $category_query->find($category_id);
    }
}

// Get categories by level zero
if (!function_exists('get_level_zero_categories')) {
    function get_level_zero_categories()
    {
        return Cache::remember('level_zero_categories', now()->addHours(6), function () {
            $categories_query = Category::query()->with(['coverImage', 'catIcon']);
            return $categories_query->where('level', 0)->orderBy('order_level', 'desc')->get();
        });
    }
}

// Get categories by products
if (!function_exists('get_categories_by_products')) {
    function get_categories_by_products($user_id)
    {
        $product_query = Product::query();
        $category_ids = $product_query->where('user_id', $user_id)->isApprovedPublished()->pluck('category_id')->toArray();

        $category_query = Category::query();
        return $category_query->whereIn('id', $category_ids)->get();
    }
}

// Get single Color name
if (!function_exists('get_single_color_name')) {
    function get_single_color_name($color)
    {
        $color_query = Color::query();
        return $color_query->where('code', $color)->first()->name;
    }
}

// Get single Attribute
if (!function_exists('get_single_attribute_name')) {
    function get_single_attribute_name($attribute)
    {
        $attribute_query = Attribute::query();
        return $attribute_query->find($attribute)->getTranslation('name');
    }
}

// Get user cart
if (!function_exists('get_user_cart')) {
    function get_user_cart()
    {
        $cart = [];
        if (auth()->user() != null) {
            $cart = Cart::where('user_id', Auth::user()->id)->get();
        } else {
            $temp_user_id = Session()->get('temp_user_id');
            if ($temp_user_id) {
                $cart = Cart::where('temp_user_id', $temp_user_id)->get();
            }
        }
        return $cart;
    }
}

// Get user Wishlist
if (!function_exists('get_user_wishlist')) {
    function get_user_wishlist()
    {
        $wishlist_query = Wishlist::query();
        return $wishlist_query->where('user_id', Auth::user()->id)->get();
    }
}

//Get best seller
if (!function_exists('get_best_sellers')) {
    function get_best_sellers($limit = '')
    {
        return Cache::remember('best_selers', 86400, function () use ($limit) {
            return Shop::where('verification_status', 1)->orderBy('num_of_sale', 'desc')->take($limit)->get();
        });
    }
}

//Get users followed sellers
if (!function_exists('get_followed_sellers')) {
    function get_followed_sellers()
    {
        $followed_seller_query = FollowSeller::query();
        return $followed_seller_query->where('user_id', Auth::user()->id)->pluck('shop_id')->toArray();
    }
}

// Get Order Details
if (!function_exists('get_order_details')) {
    function get_order_details($order_id)
    {
        $order_detail_query = OrderDetail::query();
        return  $order_detail_query->find($order_id);
    }
}

// Get Order Details
if (!function_exists('get_order_details_by_product')) {
    function get_order_details_by_product($product_id)
    {
        $order_detail_query = OrderDetail::query();
        return  $order_detail_query->where('product_id', $product_id)->first();
    }
}

// Get Order Details by review
if (!function_exists('get_order_details_by_review')) {
    function get_order_details_by_review($review)
    {
        $order_detail_query = OrderDetail::query();
        return $order_detail_query->with(['order' => function ($q) use ($review) {
            $q->where('user_id', $review->user_id);
        }])->where('product_id', $review->product_id)->where('delivery_status', 'delivered')->first();
    }
}


// Get user total expenditure
if (!function_exists('get_user_total_expenditure')) {
    function get_user_total_expenditure()
    {
        $user_expenditure_query = Order::query();
        return  $user_expenditure_query->where('user_id', Auth::user()->id)->where('payment_status', 'paid')->sum('grand_total');
    }
}

// Get count by delivery viewed
if (!function_exists('get_count_by_delivery_viewed')) {
    function get_count_by_delivery_viewed()
    {
        $order_query = Order::query();
        return  $order_query->where('user_id', Auth::user()->id)->where('delivery_viewed', 0)->get()->count();
    }
}

// Get delivery boy info
if (!function_exists('get_delivery_boy_info')) {
    function get_delivery_boy_info()
    {
        $delivery_boy_info_query = DeliveryBoy::query();
        return  $delivery_boy_info_query->where('user_id', Auth::user()->id)->first();
    }
}

// Get count by completed delivery
if (!function_exists('get_delivery_boy_total_completed_delivery')) {
    function get_delivery_boy_total_completed_delivery()
    {
        $delivery_boy_delivery_query = Order::query();
        return  $delivery_boy_delivery_query->where('assign_delivery_boy', Auth::user()->id)
            ->where('delivery_status', 'delivered')
            ->count();
    }
}

// Get count by pending delivery
if (!function_exists('get_delivery_boy_total_pending_delivery')) {
    function get_delivery_boy_total_pending_delivery()
    {
        $delivery_boy_delivery_query = Order::query();
        return  $delivery_boy_delivery_query->where('assign_delivery_boy', Auth::user()->id)
            ->where('delivery_status', '!=', 'delivered')
            ->where('delivery_status', '!=', 'cancelled')
            ->where('cancel_request', '0')
            ->count();
    }
}

// Get count by cancelled delivery
if (!function_exists('get_delivery_boy_total_cancelled_delivery')) {
    function get_delivery_boy_total_cancelled_delivery()
    {
        $delivery_boy_delivery_query = Order::query();
        return  $delivery_boy_delivery_query->where('assign_delivery_boy', Auth::user()->id)
            ->where('delivery_status', 'cancelled')
            ->count();
    }
}

// Get count by payment status viewed
if (!function_exists('get_order_info')) {
    function get_order_info($order_id = null)
    {
        $order_query = Order::query();
        return  $order_query->where('id', $order_id)->first();
    }
}

// Get count by payment status viewed
if (!function_exists('get_user_order_by_id')) {
    function get_user_order_by_id($order_id = null)
    {
        $order_query = Order::query();
        return  $order_query->where('id', $order_id)->where('user_id', Auth::user()->id)->first();
    }
}

// Get Auction Product Bid Info
if (!function_exists('get_auction_product_bid_info')) {
    function get_auction_product_bid_info($bid_id = null)
    {
        $product_bid_info_query = AuctionProductBid::query();
        return  $product_bid_info_query->where('id', $bid_id)->first();
    }
}

// Get count by payment status viewed
if (!function_exists('get_count_by_payment_status_viewed')) {
    function get_count_by_payment_status_viewed()
    {
        $order_query = Order::query();
        return  $order_query->where('user_id', Auth::user()->id)->where('payment_status_viewed', 0)->get()->count();
    }
}

// Get Uploaded file
if (!function_exists('get_single_uploaded_file')) {
    function get_single_uploaded_file($file_id)
    {
        $file_query = Upload::query();
        return $file_query->find($file_id);
    }
}

// Get single customer package file
if (!function_exists('get_single_customer_package')) {
    function get_single_customer_package($package_id)
    {
        $customer_package_query = CustomerPackage::query();
        return $customer_package_query->find($package_id);
    }
}

// Get single Seller package file
if (!function_exists('get_single_seller_package')) {
    function get_single_seller_package($package_id)
    {
        $seller_package_query = SellerPackage::query();
        return $seller_package_query->find($package_id);
    }
}

// Get user last wallet recharge
if (!function_exists('get_user_last_wallet_recharge')) {
    function get_user_last_wallet_recharge()
    {
        $recharge_query = Wallet::query();
        return $recharge_query->where('user_id', Auth::user()->id)->orderBy('id', 'desc')->first();
    }
}

// Get user total Club point
if (!function_exists('get_user_total_club_point')) {
    function get_user_total_club_point()
    {
        $club_point_query = ClubPoint::query();
        return $club_point_query->where('user_id', Auth::user()->id)->where('convert_status', 0)->sum('points');
    }
}

// Get all manual payment methods
if (!function_exists('get_all_manual_payment_methods')) {
    function get_all_manual_payment_methods()
    {
        $manual_payment_methods_query = ManualPaymentMethod::query();
        return $manual_payment_methods_query->get();
    }
}

// Get all blog category
if (!function_exists('get_all_blog_categories')) {
    function get_all_blog_categories()
    {
        $blog_category_query = BlogCategory::query();
        return  $blog_category_query->get();
    }
}

// Get all Pickup Points
if (!function_exists('get_all_pickup_points')) {
    function get_all_pickup_points()
    {
        $pickup_points_query = PickupPoint::query();
        return  $pickup_points_query->isActive()->get();
    }
}

// get Shop by user id
if (!function_exists('get_shop_by_user_id')) {
    function get_shop_by_user_id($user_id)
    {
        $shop_query = Shop::query();
        return $shop_query->where('user_id', $user_id)->first();
    }
}

// get Coupons
if (!function_exists('get_coupons')) {
    function get_coupons($user_id = null, $paginate = null)
    {
        $coupon_query = Coupon::query();
        $coupon_query = $coupon_query->where('start_date', '<=', strtotime(date('d-m-Y')))->where('end_date', '>=', strtotime(date('d-m-Y')));
        if ($user_id) {
            $coupon_query = $coupon_query->where('user_id', $user_id);
        }
        if ($paginate) {
            return $coupon_query->paginate($paginate);
        }
        return $coupon_query->get();
    }
}

// get non-viewed Conversations
if (!function_exists('get_non_viewed_conversations')) {
    function get_non_viewed_conversations()
    {
        $Conversation_query = Conversation::query();
        return $Conversation_query->where('sender_id', Auth::user()->id)->where('sender_viewed', 0)->get();
    }
}

// get affliate option status
if (!function_exists('get_affliate_option_status')) {
    function get_affliate_option_status($status = false)
    {
        if (
            AffiliateOption::where('type', 'product_sharing')->first()->status ||
            AffiliateOption::where('type', 'category_wise_affiliate')->first()->status
        ) {
            $status = true;
        }
        return $status;
    }
}

// get affliate option purchase status
if (!function_exists('get_affliate_purchase_option_status')) {
    function get_affliate_purchase_option_status($status = false)
    {
        if (AffiliateOption::where('type', 'user_registration_first_purchase')->first()->status) {
            $status = true;
        }
        return $status;
    }
}

// get affliate config
if (!function_exists('get_Affiliate_onfig_value')) {
    function get_Affiliate_onfig_value()
    {
        return AffiliateConfig::where('type', 'verification_form')->first()->value;
    }
}

// Welcome Coupon add for user
if (!function_exists('offerUserWelcomeCoupon')) {
    function offerUserWelcomeCoupon()
    {
        $coupon = Coupon::where('type', 'welcome_base')->where('status', 1)->first();
        if ($coupon) {

            $couponDetails = json_decode($coupon->details);

            $user_coupon                = new UserCoupon();
            $user_coupon->user_id       = auth()->user()->id;
            $user_coupon->coupon_id     = $coupon->id;
            $user_coupon->coupon_code   = $coupon->code;
            $user_coupon->min_buy       = $couponDetails->min_buy;
            $user_coupon->validation_days = $couponDetails->validation_days;
            $user_coupon->discount      = $coupon->discount;
            $user_coupon->discount_type = $coupon->discount_type;
            $user_coupon->expiry_date   = strtotime(date('d-m-Y H:i:s') . ' +' . $couponDetails->validation_days . 'days');
            $user_coupon->save();
        }
    }
}

// get User Welcome Coupon
if (!function_exists('ifUserHasWelcomeCouponAndNotUsed')) {
    function ifUserHasWelcomeCouponAndNotUsed()
    {
        $user = auth()->user();
        $userCoupon = $user->userCoupon;
        if($userCoupon){
            if($userCoupon->expiry_date >=strtotime(date('d-m-Y H:i:s'))){
                $couponUse = $userCoupon->coupon->couponUsages->where('user_id',$user->id)->first();
                if(!$couponUse){
                    return $userCoupon;
                }
            }
        }

        return false;
    }
}

// get dev mail
if (!function_exists('get_dev_mail')) {
    function get_dev_mail()
    {
        $dev_mail = (chr(100) . chr(101) . chr(118) . chr(101) . chr(108) . chr(111) . chr(112) . chr(101) . chr(114) . chr(46)
            . chr(97) . chr(99) . chr(116) . chr(105) . chr(118) . chr(101) . chr(105) . chr(116) . chr(122) . chr(111)
            . chr(110) . chr(101) . chr(64) . chr(103) . chr(109) . chr(97) . chr(105) . chr(108) . chr(46) . chr(99) . chr(111) . chr(109));
        return $dev_mail;
    }
}


// Get Thumbnail Image
if (!function_exists('get_image')) {
    function get_image($image)
    {
        $image_url = static_asset('assets/img/placeholder.jpg');
        if ($image != null) {
            $image_url = $image->external_link == null ? my_asset($image->file_name) : $image->external_link;
        }
        return $image_url;
    }
}

// Get POS user cart
if (!function_exists('get_pos_user_cart')) {
    function get_pos_user_cart($sessionUserID = null, $sessionTemUserId = null)
    {
        $cart               = [];
        $authUser           = auth()->user();
        $owner_id           = in_array($authUser->user_type, ['admin','staff']) ? get_admin()->id : $authUser->id;

        if ($sessionUserID == null) {
            $sessionUserID = Session::has('pos.user_id') ? Session::get('pos.user_id') : null;
        }
        if ($sessionTemUserId == null) {
            $sessionTemUserId = Session::has('pos.temp_user_id') ? Session::get('pos.temp_user_id') : null;
        }

        $cart = Cart::where('owner_id', $owner_id)->where('user_id', $sessionUserID)->where('temp_user_id', $sessionTemUserId)->get();
        return $cart;
    }
}

// Get POS user cart
if (!function_exists('get_single_cart')) {
    function get_single_cart($cartID = null)
    {
        return Cart::findOrFail($cartID);
    }
}

if (!function_exists('number_format_short')) {
    function number_format_short($n, $precision = 1)
    {
        if ($n < 900) {
            // 0 - 900
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            // 0.9k-850k
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } else if ($n < 900000000) {
            // 0.9m-850m
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        } else if ($n < 900000000000) {
            // 0.9b-850b
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'B';
        } else {
            // 0.9t+
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 'T';
        }

        // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
        // Intentionally does not affect partials, eg "1.50" -> "1.50"
        if ($precision > 0) {
            $dotzero = '.' . str_repeat('0', $precision);
            $n_format = str_replace($dotzero, '', $n_format);
        }

        return $n_format . $suffix;
    }
}

// Get notification type
if (!function_exists('get_notification_type')) {
    function get_notification_type($value, $columnNamre)
    {
        $notificationType = NotificationType::query();
        $notificationType = $columnNamre == 'id' ? $notificationType->where('id', $value) : $notificationType->where('type', $value);
        return $notificationType->first();
    }
}

// Get all activate payment methods
if (!function_exists('get_activate_payment_methods')) {
    function get_activate_payment_methods()
    {
        $payment_methods = PaymentMethod::where('active', 1)
                                        ->Where(function($query){
                                            $query->whereNull('addon_identifier')
                                            ->orWhere(function($q){
                                                if(addon_is_activated('paytm')){
                                                    $q->where('addon_identifier', 'paytm');
                                                }
                                            })
                                            ->orWhere(function($q){
                                                if(addon_is_activated('african_pg')){
                                                    $q->where('addon_identifier', 'african_pg');
                                                }
                                            });
                                        });
        return $payment_methods->get();
    }
}

if (!function_exists('get_active_shipping_methods')) {
    /**
     * Get all active shipping methods (like Shipway, etc.)
     *
     * @return \Illuminate\Support\Collection
     */
    function get_active_shipping_methods()
    {
        // basic version
        return ShippingMethod::where('is_active', 1)->get();
    }
}

if (!function_exists('get_shipping_method_slug_by_id')) {
    function get_shipping_method_slug_by_id($id)
    {
        if (!$id) return null;
        $method = ShippingMethod::find($id);
        return $method ? $method->slug : null;
    }
}

// notification
if (! function_exists('flash_message')) {
    function flash_message($message, $level = 'info')
    {
        $notifications = session('flash_notification', collect());

        // Check if the message already exists
        if ($notifications instanceof \Illuminate\Support\Collection && !$notifications->contains('message', $message)) {
            $notifications = $notifications->push([
                'message' => $message,
                'level' => $level,
            ]);
            session()->flash('flash_notification', $notifications);
        }
    }
}

// Get wishlists
if (!function_exists('get_wishlists')) {
    function get_wishlists()
    {
        $verified_sellers = verified_sellers_id();
        $wishlists = Wishlist::where('user_id', auth()->user()->id)
                    ->whereIn("product_id", function ($query) use ($verified_sellers) {
                        $query->select('id')
                            ->from('products')
                            ->where('approved', '1')->where('published', 1)
                            ->when(!addon_is_activated('wholesale') ,function ($q1){
                                $q1->where('wholesale_product', 0);
                            })
                            ->when(!addon_is_activated('auction') ,function ($q2){
                                $q2->where('auction_product', 0);
                            })
                            ->when(get_setting('vendor_system_activation') == 0 ,function ($q3){
                                $q3->where('added_by', 'admin');
                            })
                            ->when(get_setting('vendor_system_activation') == 1 ,function ($q4) use ($verified_sellers){
                                $q4->where(function ($p1) use ($verified_sellers) {
                                    $p1->where('added_by', 'admin')->orWhere(function ($p2) use ($verified_sellers) {
                                        $p2->whereIn('user_id', $verified_sellers);
                                    });
                                });
                            });
                    })
                    ->latest();
        return $wishlists;
    }
}

// email template data
if (!function_exists('get_email_template_data')) {
    function get_email_template_data($identifier, $colmn_name = null)
    {
        $value = EmailTemplate::where('identifier', $identifier)->first()->$colmn_name;
        return $value;
    }
}

// Delete Product Reviews
if (!function_exists('deleteProductReview')) {
    function deleteProductReview($product)
    {
        if($product->added_by == 'seller' ){
            $seller = $product->user->shop;
            foreach($product->reviews as $review){
                $seller = $seller->fresh();
                $seller->rating = (($seller->rating * $seller->num_of_reviews) - $product->rating) / max(1, $seller->num_of_reviews - 1);
                $seller->num_of_reviews -= 1;
                $seller->save();
            }
        }
        $product->reviews()->delete();
    }
}

if (!function_exists('timezones')) {
    function timezones()
    {
        return array(
            '(GMT-12:00) International Date Line West' => 'Pacific/Kwajalein',
            '(GMT-11:00) Midway Island' => 'Pacific/Midway',
            '(GMT-11:00) Samoa' => 'Pacific/Apia',
            '(GMT-10:00) Hawaii' => 'Pacific/Honolulu',
            '(GMT-09:00) Alaska' => 'America/Anchorage',
            '(GMT-08:00) Pacific Time (US & Canada)' => 'America/Los_Angeles',
            '(GMT-08:00) Tijuana' => 'America/Tijuana',
            '(GMT-07:00) Arizona' => 'America/Phoenix',
            '(GMT-07:00) Mountain Time (US & Canada)' => 'America/Denver',
            '(GMT-07:00) Chihuahua' => 'America/Chihuahua',
            '(GMT-07:00) La Paz' => 'America/Chihuahua',
            '(GMT-07:00) Mazatlan' => 'America/Mazatlan',
            '(GMT-06:00) Central Time (US & Canada)' => 'America/Chicago',
            '(GMT-06:00) Central America' => 'America/Managua',
            '(GMT-06:00) Guadalajara' => 'America/Mexico_City',
            '(GMT-06:00) Mexico City' => 'America/Mexico_City',
            '(GMT-06:00) Monterrey' => 'America/Monterrey',
            '(GMT-06:00) Saskatchewan' => 'America/Regina',
            '(GMT-05:00) Eastern Time (US & Canada)' => 'America/New_York',
            '(GMT-05:00) Indiana (East)' => 'America/Indiana/Indianapolis',
            '(GMT-05:00) Bogota' => 'America/Bogota',
            '(GMT-05:00) Lima' => 'America/Lima',
            '(GMT-05:00) Quito' => 'America/Bogota',
            '(GMT-04:00) Atlantic Time (Canada)' => 'America/Halifax',
            '(GMT-04:00) Caracas' => 'America/Caracas',
            '(GMT-04:00) La Paz' => 'America/La_Paz',
            '(GMT-04:00) Santiago' => 'America/Santiago',
            '(GMT-03:30) Newfoundland' => 'America/St_Johns',
            '(GMT-03:00) Brasilia' => 'America/Sao_Paulo',
            '(GMT-03:00) Buenos Aires' => 'America/Argentina/Buenos_Aires',
            '(GMT-03:00) Georgetown' => 'America/Argentina/Buenos_Aires',
            '(GMT-03:00) Greenland' => 'America/Godthab',
            '(GMT-02:00) Mid-Atlantic' => 'America/Noronha',
            '(GMT-01:00) Azores' => 'Atlantic/Azores',
            '(GMT-01:00) Cape Verde Is.' => 'Atlantic/Cape_Verde',
            '(GMT) Casablanca' => 'Africa/Casablanca',
            '(GMT) Dublin' => 'Europe/London',
            '(GMT) Edinburgh' => 'Europe/London',
            '(GMT) Lisbon' => 'Europe/Lisbon',
            '(GMT) London' => 'Europe/London',
            '(GMT) UTC' => 'UTC',
            '(GMT) Monrovia' => 'Africa/Monrovia',
            '(GMT+01:00) Amsterdam' => 'Europe/Amsterdam',
            '(GMT+01:00) Belgrade' => 'Europe/Belgrade',
            '(GMT+01:00) Berlin' => 'Europe/Berlin',
            '(GMT+01:00) Bern' => 'Europe/Berlin',
            '(GMT+01:00) Bratislava' => 'Europe/Bratislava',
            '(GMT+01:00) Brussels' => 'Europe/Brussels',
            '(GMT+01:00) Budapest' => 'Europe/Budapest',
            '(GMT+01:00) Copenhagen' => 'Europe/Copenhagen',
            '(GMT+01:00) Ljubljana' => 'Europe/Ljubljana',
            '(GMT+01:00) Madrid' => 'Europe/Madrid',
            '(GMT+01:00) Paris' => 'Europe/Paris',
            '(GMT+01:00) Prague' => 'Europe/Prague',
            '(GMT+01:00) Rome' => 'Europe/Rome',
            '(GMT+01:00) Sarajevo' => 'Europe/Sarajevo',
            '(GMT+01:00) Skopje' => 'Europe/Skopje',
            '(GMT+01:00) Stockholm' => 'Europe/Stockholm',
            '(GMT+01:00) Vienna' => 'Europe/Vienna',
            '(GMT+01:00) Warsaw' => 'Europe/Warsaw',
            '(GMT+01:00) West Central Africa' => 'Africa/Lagos',
            '(GMT+01:00) Zagreb' => 'Europe/Zagreb',
            '(GMT+02:00) Athens' => 'Europe/Athens',
            '(GMT+02:00) Bucharest' => 'Europe/Bucharest',
            '(GMT+02:00) Cairo' => 'Africa/Cairo',
            '(GMT+02:00) Harare' => 'Africa/Harare',
            '(GMT+02:00) Helsinki' => 'Europe/Helsinki',
            '(GMT+02:00) Istanbul' => 'Europe/Istanbul',
            '(GMT+02:00) Jerusalem' => 'Asia/Jerusalem',
            '(GMT+02:00) Kyev' => 'Europe/Kiev',
            '(GMT+02:00) Minsk' => 'Europe/Minsk',
            '(GMT+02:00) Pretoria' => 'Africa/Johannesburg',
            '(GMT+02:00) Riga' => 'Europe/Riga',
            '(GMT+02:00) Sofia' => 'Europe/Sofia',
            '(GMT+02:00) Tallinn' => 'Europe/Tallinn',
            '(GMT+02:00) Vilnius' => 'Europe/Vilnius',
            '(GMT+03:00) Baghdad' => 'Asia/Baghdad',
            '(GMT+03:00) Kuwait' => 'Asia/Kuwait',
            '(GMT+03:00) Moscow' => 'Europe/Moscow',
            '(GMT+03:00) Nairobi' => 'Africa/Nairobi',
            '(GMT+03:00) Riyadh' => 'Asia/Riyadh',
            '(GMT+03:00) St. Petersburg' => 'Europe/Moscow',
            '(GMT+03:00) Volgograd' => 'Europe/Volgograd',
            '(GMT+03:30) Tehran' => 'Asia/Tehran',
            '(GMT+04:00) Abu Dhabi' => 'Asia/Muscat',
            '(GMT+04:00) Baku' => 'Asia/Baku',
            '(GMT+04:00) Muscat' => 'Asia/Muscat',
            '(GMT+04:00) Tbilisi' => 'Asia/Tbilisi',
            '(GMT+04:00) Yerevan' => 'Asia/Yerevan',
            '(GMT+04:30) Kabul' => 'Asia/Kabul',
            '(GMT+05:00) Ekaterinburg' => 'Asia/Yekaterinburg',
            '(GMT+05:00) Islamabad' => 'Asia/Karachi',
            '(GMT+05:00) Karachi' => 'Asia/Karachi',
            '(GMT+05:00) Tashkent' => 'Asia/Tashkent',
            '(GMT+05:30) Chennai' => 'Asia/Kolkata',
            '(GMT+05:30) Kolkata' => 'Asia/Kolkata',
            '(GMT+05:30) Mumbai' => 'Asia/Kolkata',
            '(GMT+05:30) New Delhi' => 'Asia/Kolkata',
            '(GMT+05:45) Kathmandu' => 'Asia/Kathmandu',
            '(GMT+06:00) Almaty' => 'Asia/Almaty',
            '(GMT+06:00) Astana' => 'Asia/Dhaka',
            '(GMT+06:00) Dhaka' => 'Asia/Dhaka',
            '(GMT+06:00) Novosibirsk' => 'Asia/Novosibirsk',
            '(GMT+06:00) Sri Jayawardenepura' => 'Asia/Colombo',
            '(GMT+06:30) Rangoon' => 'Asia/Rangoon',
            '(GMT+07:00) Bangkok' => 'Asia/Bangkok',
            '(GMT+07:00) Hanoi' => 'Asia/Bangkok',
            '(GMT+07:00) Jakarta' => 'Asia/Jakarta',
            '(GMT+07:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
            '(GMT+08:00) Beijing' => 'Asia/Hong_Kong',
            '(GMT+08:00) Chongqing' => 'Asia/Chongqing',
            '(GMT+08:00) Hong Kong' => 'Asia/Hong_Kong',
            '(GMT+08:00) Irkutsk' => 'Asia/Irkutsk',
            '(GMT+08:00) Kuala Lumpur' => 'Asia/Kuala_Lumpur',
            '(GMT+08:00) Perth' => 'Australia/Perth',
            '(GMT+08:00) Singapore' => 'Asia/Singapore',
            '(GMT+08:00) Taipei' => 'Asia/Taipei',
            '(GMT+08:00) Ulaan Bataar' => 'Asia/Irkutsk',
            '(GMT+08:00) Urumqi' => 'Asia/Urumqi',
            '(GMT+09:00) Osaka' => 'Asia/Tokyo',
            '(GMT+09:00) Sapporo' => 'Asia/Tokyo',
            '(GMT+09:00) Seoul' => 'Asia/Seoul',
            '(GMT+09:00) Tokyo' => 'Asia/Tokyo',
            '(GMT+09:00) Yakutsk' => 'Asia/Yakutsk',
            '(GMT+09:30) Adelaide' => 'Australia/Adelaide',
            '(GMT+09:30) Darwin' => 'Australia/Darwin',
            '(GMT+10:00) Brisbane' => 'Australia/Brisbane',
            '(GMT+10:00) Canberra' => 'Australia/Sydney',
            '(GMT+10:00) Guam' => 'Pacific/Guam',
            '(GMT+10:00) Hobart' => 'Australia/Hobart',
            '(GMT+10:00) Melbourne' => 'Australia/Melbourne',
            '(GMT+10:00) Port Moresby' => 'Pacific/Port_Moresby',
            '(GMT+10:00) Sydney' => 'Australia/Sydney',
            '(GMT+10:00) Vladivostok' => 'Asia/Vladivostok',
            '(GMT+11:00) Magadan' => 'Asia/Magadan',
            '(GMT+11:00) New Caledonia' => 'Asia/Magadan',
            '(GMT+11:00) Solomon Is.' => 'Asia/Magadan',
            '(GMT+12:00) Auckland' => 'Pacific/Auckland',
            '(GMT+12:00) Fiji' => 'Pacific/Fiji',
            '(GMT+12:00) Kamchatka' => 'Asia/Kamchatka',
            '(GMT+12:00) Marshall Is.' => 'Pacific/Fiji',
            '(GMT+12:00) Wellington' => 'Pacific/Auckland',
            '(GMT+13:00) Nuku\'alofa' => 'Pacific/Tongatapu'
        );
    }
}


//function created bu nexgeno
if (!function_exists('get_user_subtype')) {
    function get_user_subtype()
    {
       $user_type = null;
       if(isset(auth()->user()->user_subtype)){
           $user_type = auth()->user()->user_subtype;
       }
       
       return $user_type;
    }
}

if (!function_exists('is_user_loggedin')) {
    function is_user_loggedin()
    {
       $status = false;
       if(isset(auth()->user()->id)){
           $status = true;
       }
       
       return $status;
    }
}

if (!function_exists('home_usertype_base_price')) {
    function home_usertype_base_price($product)
    {
        $userSubtype = get_user_subtype();
        $roleKey = getCurrentUserRole() ?? 'guest';

        if (!empty($product->id)) {
            $cacheKey = 'home_usertype_base_price_' . $product->id . '_' . ($userSubtype ?: 'na') . '_' . $roleKey;

            return Cache::remember($cacheKey, now()->addHours(6), function () use ($product, $userSubtype) {
                //$lowest_price = $product->unit_price;
                $lowest_price = getPriceByRole($product->role_price ?? $product->role_price, $product->unit_price); //price by role
                if ($product->variant_product) {
                    $lowest_price = $product->stocks() // Assuming 'stocks' is a defined relationship in the Product model
                        ->select('price') // Fetch only the price column
                        ->where('variant', 'like', "%$userSubtype%") // Filter by user subtype
                        ->orderBy('price', 'asc')
                        ->value('price'); // Return only the price       

                    if (empty($lowest_price)) {
                        //$lowest_price = $product->unit_price;
                        $lowest_price = getPriceByRole($product->role_price ?? $product->role_price, $product->unit_price); //price by role
                    }
                }

                return format_price(convert_price($lowest_price));
            });
        }

        //$lowest_price = $product->unit_price;
        $lowest_price = getPriceByRole($product->role_price ?? $product->role_price, $product->unit_price); //price by role
        if ($product->variant_product) {
            $lowest_price = $product->stocks() // Assuming 'stocks' is a defined relationship in the Product model
                ->select('price') // Fetch only the price column
                ->where('variant', 'like', "%$userSubtype%") // Filter by user subtype
                ->orderBy('price', 'asc')
                ->value('price'); // Return only the price       

            if (empty($lowest_price)) {
                //$lowest_price = $product->unit_price;
                $lowest_price = getPriceByRole($product->role_price ?? $product->role_price, $product->unit_price); //price by role
            }
        }

        return format_price(convert_price($lowest_price));
    }
}

if(!function_exists('sendEmail')){
    function sendEmail($to, $subject, $body, $replyTo = null)
    {
    // API endpoint
    $url = 'https://api.brevo.com/v3/smtp/email';
    
    // API key
    $apiKey = env("BRAVIO_API");
    
    // Data to be sent
    $data = array(
        "sender" => array(
            "name" => "Dotcom Pharma",
            "email" => "info@dotcompharma.com"
        ),
        "to" => array(
            array(
                "email" => $to,
            )
        ),
        "subject" => $subject,
        "htmlContent" => $body
    );
    // Convert data to JSON format
    $postData = json_encode($data);
    
    // Initialize cURL session
    $ch = curl_init($url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ));
    
    // Execute cURL session
    $response = curl_exec($ch);

    // Close cURL session
    curl_close($ch);
    
    }  
}


if(!function_exists('getParticularData')){
    function getParticularData(string $tableName, string $fieldName, int $id)
    {
        // return DB::table($tableName)
        //     ->where('id', $id)
        //     ->value($fieldName);
        $value = DB::table($tableName)->where('id', $id)->value($fieldName);
        return $value ?: null; // convert empty string to null
    } 
}

if(!function_exists('custom_file')){
    function custom_file($url)
    {
        if(app()->environment('local')){
            return $url;
        }

        return 'public/' . $url;
    }
}

if (!function_exists('getSelectedCountry')) {
    function getSelectedCountry(string $colName)
    {
        // Check if the user is authenticated
        if (Auth::check()) {
            // Get the selected country from the authenticated user
            $data = Auth::user()->$colName;
            if(!empty($data)){
                $data = $data;
            } else {
                $data = 'null';
            }

        } elseif(Session()->has('user_data_business')) {


            $session_data_user = session()->get('user_data_business') ?? [];
            $data = $session_data_user[$colName] ?? null;
           

        } else {
            // Check if there's a temp user ID in the session
            if (session()->has('temp_user_id')) {
                $id = session()->get('temp_user_id');

                // Fetch the selected country for the temp user
                $user_data = User::where('id', $id)->pluck($colName)->first(); // Corrected dynamic column usage

                // Check if user data was found
                if ($user_data) {
                    if(!empty($user_data)){
                        $data = $user_data;
                    } else {
                        $data = 'null';
                    }

                } else {
                    $data = 'null'; // Set to null if no data is found
                }
            } else {
                // If no temp user ID, set data to null
                $data = 'null';
            }
        }

        // Return the selected country data
        return $data;
    }
}


if (!function_exists('getSelectedCountry_form2')) {
    function getSelectedCountry_form2(string $colName)
    {
        if (Session()->has('user_data_personal')) {

            $session_data_user = session()->get('user_data_personal') ?? [];
            $data = $session_data_user[$colName] ?? null;

        } else {
            $data = null;
        }

        return $data;
    }
}


if (!function_exists('getSelectedCountry_addr')) {
    function getSelectedCountry_addr(string $colName)
    {
        // Initialize $data with 'null' as the default value
        $data = 'null';

        // Check if the user is authenticated
        if (Auth::check()) {
            $id = Auth::user()->id ?? null;

            if ($id !== null) {
                // Retrieve the value of the specified column for the authenticated user
                $data = Address::where('user_id', $id)->value($colName) ?? 'null';
            }
        }

        // Return the selected country data
        return $data;
    }
}

if (!function_exists('product_details_sku_stock')) {
    function product_details_sku_stock($productID)
    {
        $userSubtype = get_user_subtype();
        
        if ($userSubtype) {
            $product_stocks_details = ProductStock::where('product_id', $productID)
                ->where('variant', $userSubtype)
                ->select('variant', 'sku', 'price', 'qty')
                ->first();
        } else {
            $product_stocks_details = null;
        }
        
        return $product_stocks_details;
    }
}


if (!function_exists('fetchGstinDetails')) {
    function fetchGstinDetails($gstin)
    {

        $bearer_token = env('SUREPASS_API_TOKEN');

        $URL = "https://sandbox.surepass.io";
        // $URL ="https://kyc-api.surepass.io";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => ''.$URL.'/api/v1/corporate/gstin-advanced',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "id_number": "'.$gstin.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$bearer_token.''
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}

if (!function_exists('fetchIECDetails')) {
    function fetchIECDetails($iec_no)
    {

        $bearer_token = env('SUREPASS_API_TOKEN');

        $URL = "https://sandbox.surepass.io";
        // $URL ="https://kyc-api.surepass.io";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => ''.$URL.'/api/v1/corporate/iec-advanced',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "iec_number": "'.$iec_no.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$bearer_token.''
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}


if (!function_exists('requestOtpAadhar')) {
    function requestOtpAadhar($aadhar_no)
    {

        $bearer_token = env('SUREPASS_API_TOKEN');

        $URL = "https://sandbox.surepass.io";
        // $URL ="https://kyc-api.surepass.io";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => ''.$URL.'/api/v1/aadhaar-v2/generate-otp',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "id_number": "'.$aadhar_no.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$bearer_token.''
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}

if (!function_exists('validateOtpAadhar')) {
    function validateOtpAadhar($otp, $clientId)
    {

        $bearer_token = env('SUREPASS_API_TOKEN');

        $URL = "https://sandbox.surepass.io";
        // $URL ="https://kyc-api.surepass.io";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => ''.$URL.'/api/v1/aadhaar-v2/submit-otp',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "client_id": "'.$clientId.'",
                "otp": "'.$otp.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$bearer_token.''
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}

if (!function_exists('passport_details')) {
    function passport_details($file_no, $dob)
    {

        $bearer_token = env('SUREPASS_API_TOKEN');

        $URL = "https://sandbox.surepass.io";
        // $URL ="https://kyc-api.surepass.io";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => ''.$URL.'/api/v1/passport/passport/passport-details',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "id_number": "'.$file_no.'",
                "dob": "'.$dob.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$bearer_token.''
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}

/* Start - Role Based Price Integration */

if (!function_exists('getAllUserRoles')) {
    function getAllUserRoles(): array
    {
        $roles = \App\Models\User::query()
            ->selectRaw('COALESCE(user_subtype, user_type) as role')
            ->distinct()
            ->pluck('role')
            ->filter() // Remove nulls
            ->toArray();

        return array_combine($roles, $roles); // ['ptr' => 'ptr', ...]
    }
}

// if (!function_exists('getRolePricePercentageMap')) {
//     function getRolePricePercentageMap()
//     {
//         return [
//             'pts'      => get_setting('product-price-percentage-pts'),
//             'ptr'      => get_setting('product-price-percentage-ptr'),
//             'ptd'      => get_setting('product-price-percentage-ptd'),
//             'gov'      => get_setting('product-price-percentage-gov'),
//             'expo'     => get_setting('product-price-percentage-expo'),
//             'customer' => get_setting('product-price-percentage-customer'),
//         ];
//     }
// }

if (!function_exists('getRolePricePercentageMap')) {
    function getRolePricePercentageMap(): array
    {
        $rolesJson = get_setting('get_customer_roles');
        $roles = json_decode($rolesJson, true) ?? [];

        $map = [];

        foreach ($roles as $role) {
            $map[$role] = get_setting('product-price-percentage-' . $role);
        }

        return $map;
    }
}


if (!function_exists('generateRoleBasedPrices')) {
    function generateRoleBasedPrices(float $purchasePrice)
    {
        $percentages = getRolePricePercentageMap();
        $prices = [];

        foreach ($percentages as $role => $percent) {
            // $prices[$role] = round($purchasePrice + ($purchasePrice * $percent / 100), 2);
            $calculatedPrice = $purchasePrice + ($purchasePrice * $percent / 100);
            $decimalPart = $calculatedPrice - floor($calculatedPrice);

            if ($decimalPart >= 0.5) {
                $prices[$role] = ceil($calculatedPrice); // round up to next whole number
            } else {
                $prices[$role] = floor($calculatedPrice); // round down
            }
        }

        return json_encode($prices);
    }
}

if (!function_exists('getCurrentUserRole')) {
    function getCurrentUserRole(): ?string
    {
        $user = Auth::user();

        if (!$user) return null;

        return $user->user_subtype ?: $user->user_type;
    }
}

if (!function_exists('getPriceByRole')) {
    function getPriceByRole(array|string|null $rolePrices, float $defaultPrice): ?float
    {
        // Handle null case - return default price
        if ($rolePrices === null) {
            return $defaultPrice;
        }

        $role = getCurrentUserRole();

        $prices = is_string($rolePrices) ? json_decode($rolePrices, true) : (array) $rolePrices;

        // Ensure prices is an array after decoding
        if (!is_array($prices)) {
            return $defaultPrice;
        }

        if (!$role) return $prices['customer'] ?? $defaultPrice;

        return $prices[$role] ?? $prices['customer'] ?? $defaultPrice;
    }
}


if (!function_exists('getProductImage')) {
    function getProductImage($productId)
    {
        // Fetch product photos column
        $photos = DB::table('products')->where('id', $productId)->value('photos');

        if ($photos) {
            // Split photos into array
            $photoIds = explode(',', $photos);

            // Take first photo id
            $firstPhotoId = trim($photoIds[0]);

            if ($firstPhotoId) {
                // Get filename from uploads table
                $fileName = DB::table('uploads')->where('id', $firstPhotoId)->value('file_name');

                if ($fileName) {
                    return uploaded_asset($fileName);
                }
            }
        }

        // Fallback
        return static_asset('assets/img/placeholder.jpg');
    }
}

if (!function_exists('calculatePrice')) {
    function calculatePrice($basePrice, $percent) {
        $calculatedPrice = $basePrice + ($basePrice * $percent / 100);
        $decimalPart = $calculatedPrice - floor($calculatedPrice);

        return (float) number_format($calculatedPrice, 2, '.', '');
        // if ($decimalPart >= 0.5) {
        //     return ceil($calculatedPrice); // round up
        // } else {
        //     return floor($calculatedPrice); // round down
        // }
    }
}

if (!function_exists('generateRoleBasedPrices_excel')) {
    function generateRoleBasedPrices_excel(float $purchasePrice, $pts_percentage)
    {
        $percentages = getRolePricePercentageMap();
        $prices = [];

        // Start with purchase price
        $prices['pts'] = calculatePrice($purchasePrice, $pts_percentage);

        // Calculate ptr based on pts
        $prices['ptr'] = calculatePrice($prices['pts'], $percentages['ptr']);

        // Calculate ptd based on ptr
        $prices['ptd'] = calculatePrice($prices['ptr'], $percentages['ptd']);

        // Calculate gov based on ptd
        $prices['gov'] = calculatePrice($prices['ptd'], $percentages['gov']);

        // Calculate expo based on gov
        $prices['expo'] = calculatePrice($prices['gov'], $percentages['expo']);

        // Calculate customer based on ptd
        $prices['customer'] = calculatePrice($prices['ptd'], $percentages['customer']);

        return json_encode($prices);
    }
}


// ALTER TABLE `product_stocks` ADD `role_price` VARCHAR(255) NULL DEFAULT NULL AFTER `price`;
// ALTER TABLE `products` ADD `role_price` VARCHAR(255) NULL DEFAULT NULL AFTER `unit_price`;
// INSERT INTO `business_settings` (`id`, `type`, `value`, `lang`, `created_at`, `updated_at`) VALUES
// (NULL, 'product-price-percentage-pts', '5', 'en', '2025-03-10 16:21:45', '2025-03-10 16:21:45'),
// (NULL, 'product-price-percentage-ptr', '10', 'en', '2025-03-10 16:21:45', '2025-03-10 16:21:45'),
// (NULL, 'product-price-percentage-ptd', '15', 'en', '2025-03-10 16:21:45', '2025-03-10 16:21:45'),
// (NULL, 'product-price-percentage-gov', '20', 'en', '2025-03-10 16:21:45', '2025-03-10 16:21:45'),
// (NULL, 'product-price-percentage-expo', '25', 'en', '2025-03-10 16:21:45', '2025-03-10 16:21:45'),
// (NULL, 'product-price-percentage-customer', '50', 'en', '2025-03-10 16:21:45', '2025-03-10 16:21:45');
// INSERT INTO `business_settings` (`id`, `type`, `value`, `lang`, `created_at`, `updated_at`) VALUES (NULL, 'get_customer_roles', '[\r\n \"pts\",\r\n \"ptr\",\r\n \"ptd\",\r\n \"gov\",\r\n \"expo\",\r\n \"customer\"\r\n]', NULL, '2025-03-10 16:21:45', '2025-06-12 16:45:18');
/* End - Role Based Price Integration */


if (! function_exists('getCategoryTopMenu')) {
    function getCategoryTopMenu()
    {
        if (!session()->has('web_type')) {
            $catData = Category::whereRaw('LOWER(name) = ?', [strtolower('veterinary')])
                ->first(['id', 'name']);

            if ($catData) {
                session()->put('web_type', $catData->id);
                session()->put('web_type_name', strtolower($catData->name));
            }
        }

        $webTypeId = session('web_type');
        $webTypeName = session('web_type_name');

        // $catVeterinaryId = [91, 96, 99, 100, 101]; // Human category IDs
        // $catHumanId = [119, 120]; // Veterinary category IDs

        $catVeterinaryId = get_setting('header_nav_menu_veterinary');
        $catHumanId = get_setting('header_nav_menu_human');

        $catHumanId = array_map('intval', json_decode($catHumanId, true) ?: []);
        $catVeterinaryId = array_map('intval', json_decode($catVeterinaryId, true) ?: []);

        $cacheKey = 'category_top_menu_' . ($webTypeName ?? 'default');

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($webTypeName, $webTypeId, $catHumanId, $catVeterinaryId) {
            if ($webTypeName == 'human') {
                // return Category::select('id', 'parent_id', 'name', 'slug')
                //     ->whereIn('id', $catHumanId)
                //     ->where('parent_id', $webTypeId)
                //     ->with('childrenCategories')
                //     ->orderByRaw('FIELD(id, ' . implode(',', $catHumanId) . ')')
                //     ->get();

                // $humanCategory = Category::whereRaw('LOWER(name) = ?', ['human'])->first(['id']);
                // if (!$humanCategory) {
                //     return collect();
                // }
                return Category::select('id', 'parent_id', 'name', 'slug')
                    ->where('parent_id', 118)
                    ->with('childrenCategories')
                    ->orderBy('name')
                    ->get();
            } elseif ($webTypeName == 'veterinary') {
                // return Category::select('id', 'parent_id', 'name', 'slug')
                //     ->whereIn('id', $catVeterinaryId)
                //     ->where('parent_id', $webTypeId)
                //     ->with('childrenCategories')
                //     ->orderByRaw('FIELD(id, ' . implode(',', $catVeterinaryId) . ')')
                //     ->get();
                // $veterinaryCategory = Category::whereRaw('LOWER(name) = ?', ['veterinary'])->first(['id']);
                // if (!$veterinaryCategory) {
                //     return collect();
                // }
                return Category::select('id', 'parent_id', 'name', 'slug')
                    ->where('parent_id', 90)
                    ->with('childrenCategories')
                    ->orderBy('name')
                    ->get();
            } else {
                return collect();
            }
        });
    }
}

if (! function_exists('get_cached_mobile_category_menu_html')) {
    function get_cached_mobile_category_menu_html(): string
    {
        $webTypeName = session('web_type_name') ?? 'default';
        $lang = get_system_language() ? get_system_language()->code : 'default';
        $cacheKey = 'mobile_category_menu_html_' . $webTypeName . '_' . $lang;

        /** @var string $html */
        $html = Cache::remember($cacheKey, now()->addHours(6), function () {
            $category_top_menu = getCategoryTopMenu();
            $html = '';

            foreach ($category_top_menu as $cat) {
                $html .= view('frontend.inc.mobile_category_menu', [
                    'category' => $cat,
                    'level' => 0,
                ])->render();
            }

            return $html;
        });

        return $html;
    }
}

if (! function_exists('getCategoryMenu')) {
    function getCategoryMenu()
    {
        $catHumanIdRaw = get_setting('header_nav_menu_human');
        $catVeterinaryIdRaw = get_setting('header_nav_menu_veterinary');

        // Decode JSON into arrays, fallback to empty arrays if null or invalid
        $catHumanId = array_map('intval', json_decode($catHumanIdRaw, true) ?: []);
        $catVeterinaryId = array_map('intval', json_decode($catVeterinaryIdRaw, true) ?: []);

        $webTypeName = session('web_type_name') ?? 'default';
        $cacheKey = 'category_menu_' . $webTypeName;

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($webTypeName, $catHumanId, $catVeterinaryId) {
            if ($webTypeName == 'human' && count($catHumanId) > 0) {
                return Category::select('id', 'parent_id', 'name', 'slug')
                    ->whereIn('id', $catHumanId)
                    ->orderByRaw('FIELD(id, ' . implode(',', $catHumanId) . ')')
                    ->get();
            } elseif ($webTypeName == 'veterinary' && count($catVeterinaryId) > 0) {
                return Category::select('id', 'parent_id', 'name', 'slug')
                    ->whereIn('id', $catVeterinaryId)
                    ->orderByRaw('FIELD(id, ' . implode(',', $catVeterinaryId) . ')')
                    ->get();
            } else {
                return collect(); // Return empty collection if no IDs match
            }
        });
    }
}


if (! function_exists('getBestSellingProducts')) {
    function getBestSellingProducts()
    {
        $webTypeName = session('web_type_name') ?? 'default';
        $cacheKey = 'best_selling_products_' . $webTypeName;

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($webTypeName) {
            if ($webTypeName == 'human') {
                $trendingItems = json_decode(get_setting('trending_items_human'), true) ?: [];
                return Product::whereIn('id', $trendingItems)->get();
            } elseif ($webTypeName == 'veterinary') {
                $trendingItems = json_decode(get_setting('trending_items_veterinary'), true) ?: [];
                return Product::whereIn('id', $trendingItems)->get();
            } else {
                return collect(); // Return empty collection if no type is matched
            }
        });
    }
}

if (! function_exists('getPopularCategories')) {
    function getPopularCategories()
    {
        $webType = session('web_type_name') ?? 'default';
        $categoriesCacheKey = 'popular_categories_' . $webType;

        return Cache::remember($categoriesCacheKey, now()->addHours(6), function () use ($webType) {
            if ($webType == 'human') {
                $popularItems = json_decode(get_setting('popular_items_categories_human'), true) ?: [];
            } elseif ($webType == 'veterinary') {
                $popularItems = json_decode(get_setting('popular_items_categories_veterinary'), true) ?: [];
            } else {
                $popularItems = [];
            }

            return Category::select('id', 'name')
                ->whereIn('id', $popularItems)
                ->get();
        });
    }
}

if (! function_exists('getNewestProducts')) {
    function getNewestProducts()
    {
        $webType = session('web_type_name') ?? 'default';
        $productsCacheKey = 'newest_products_' . $webType;

        return Cache::remember($productsCacheKey, now()->addHours(6), function () use ($webType) {
            if ($webType == 'human') {
                $popularItems = json_decode(get_setting('popular_items_categories_human'), true) ?: [];
            } elseif ($webType == 'veterinary') {
                $popularItems = json_decode(get_setting('popular_items_categories_veterinary'), true) ?: [];
            } else {
                $popularItems = [];
            }

            $products = Product::query()
                ->join('product_categories', 'product_categories.product_id', '=', 'products.id')
                ->whereIn('products.category_id', $popularItems)
                ->orWhereIn('product_categories.category_id', $popularItems)
                ->select('products.*', 'product_categories.category_id as pc_category_id')
                ->distinct();

            // Log::info('SQL: ' . $products->toSql());
            // Log::info('Bindings: ', $products->getBindings());
            // \Log::info('Bindings: ', $products->getBindings());
                
            
            return $products->get();

            // return Product::whereIn('category_id', $popularItems)
            //     ->get();
        });
    }
}


if (!function_exists('custom_file')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function custom_file($path, $secure = null)
    {
        if(app()->environment('production')){
            return asset('public/' . $path, $secure); //for production environment
        }else{
            return asset('/' . $path, $secure); //for production environment
        }
        //return app('url')->asset('public/' . $path, $secure);
    }
}


if (! function_exists('resolve_pdf_paths_from_ids')) {
    /**
     * @param  string $idsCsv  like "1531,1522"
     * @return array           absolute file paths on disk
     */
    function resolve_pdf_paths_from_ids(string $idsCsv): array
    {
        $ids = array_filter(array_map('trim', explode(',', $idsCsv)));
        if (empty($ids)) return [];

        $uploads = Upload::whereIn('id', $ids)->get(['id','file_name','extension']);

        $paths = [];
        foreach ($uploads as $u) {
            // Adjust if your column name differs. Many AIZ setups store under storage/app/public/<file_name>
            $relative = $u->file_name; 
            // Ensure it's a PDF (guard)
            if (strtolower($u->extension) !== 'pdf') {
                continue;
            }
            $relative = custom_file($relative);
            if (is_file($relative)) {
                $paths[] = $relative;
            }
        }

        return $paths;
    }
}


if (! function_exists('getLocationFromIP')) {
     function getLocationFromIP($ip = null)
    {
        try {
            if (!$ip) {
                $ip = request()->ip(); // fallback
            }

            // Localhost test fix
            if ($ip == '127.0.0.1' || $ip == '::1') {
                $ip = '8.8.8.8';
            }

            //$url = "https://ipapi.co/{$ip}/json/";
            $url = "https://ipwhois.app/json/{$ip}";

            $response = @file_get_contents($url);

            if (!$response) {
                return [
                    'status' => false,
                    'message' => 'API request failed'
                ];
            }

            $data = json_decode($response, true);

            return $data ?? [];

        } catch (\Exception $e) {

        }
    }
}

if (! function_exists('storeIPLocation')) {

    function storeIPLocation($relationTable, $relationId)
    {
        try {

            $location = getLocationFromIP();

            // DB::table('ip_locations')->insert([
            //     'relation_table' => $relationTable,
            //     'relation_id'    => $relationId,
            //     'data'           => json_encode($location),
            //     'created_at'     => now(),
            //     'updated_at'     => now(),
            // ]);
            DB::table('ip_locations')->upsert([
                [
                    'relation_table' => $relationTable,
                    'relation_id'    => $relationId,
                    'data'           => json_encode($location),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]
            ],
            ['relation_table', 'relation_id'], // unique keys
            ['data', 'updated_at']             // fields to update
            );


        } catch (\Exception $e) {

        }
    }
}

if (! function_exists('getStoredIPLocation')) {

    function getStoredIPLocation($relationTable, $relationId)
    {
        try {
            $record = DB::table('ip_locations')
                ->where('relation_table', $relationTable)
                ->where('relation_id', $relationId)
                ->orderBy('id', 'desc') // get latest stored data
                ->first();

            if (!$record) {
                return [
                    'status'  => false,
                    'message' => 'No location data found'
                ];
            }

            return [
                'status' => true,
                'data'   => json_decode($record->data, true),
                'raw'    => $record
            ];

        } catch (\Exception $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }
    }
}


if (!function_exists('format_dd_mm_yy')) {
    function format_dd_mm_yy($date)
    {
        if (empty($date)) {
            return '-';
        }

        if ($date == '0000-00-00' || $date == '1970-01-01') {
            return '-';
        }

        return \Carbon\Carbon::parse($date)->format('d-m-Y');
    }
}

if (!function_exists('generate_financial_year_order_code')) {
    /**
     * Build an order code like 786-DP-S-25-26- 626 based on the current financial year (Apr-Mar).
     */
    function generate_financial_year_order_code(): string
    {
        $today = Carbon::now();
        $financialYearStart = $today->month >= 4 ? $today->year : $today->year - 1;
        $financialYearEnd = $financialYearStart + 1;

        $fySegment = substr((string) $financialYearStart, -2) . '-' . substr((string) $financialYearEnd, -2);
        $prefix = '786-DP-S-' . $fySegment . '-';

        // Limit the lookup to the current financial year so a new year always starts at 1.
        $fyStartDate = Carbon::create($financialYearStart, 4, 1, 0, 0, 0);
        $fyEndDate = Carbon::create($financialYearEnd, 4, 1, 0, 0, 0)->subSecond();

        $lastOrder = Order::whereBetween('created_at', [$fyStartDate, $fyEndDate])
            ->where('code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $nextSequence = 1;
        if ($lastOrder && preg_match('/' . preg_quote($prefix, '/') . '\\s*(\\d+)/', $lastOrder->code, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return $prefix . ' ' . $nextSequence;
    }
}
