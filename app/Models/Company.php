<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Company extends Model
{
    protected $fillable = [
        'code',
        'company_name',
        'full_address',
        'country_id',
        'state_id',
        'city_id',
        'district',
        'post',
        'village',
        'pincode',
        'contact_person',
        'designation',
        'mobile',
        'whatsapp',
        'email',
        'company_type',
        'logo',
        'stamp',
        'sign',
        'created_by',
    ];

    public const COMPANY_TYPES = [
        'Manufacturer',
        'Third Party Manufacturer',
        'C & F Agent',
        'Authorised Distributor',
        'Distributor',
        'Wholesaler',
        'Undercutter',
        'Retailer',
        'Hospital',
        'Clinic',
        'Doctor',
        'Practiner',
        'Govt.Supplier',
        'Broker',
        'Mediater',
        'Supplier/Vendor',
        'Self User',
        'Farmer',
        'Dairy',
        'NGO',
        'Milk Federation',
        'Govt.Institutes',
        'Medical College',
        'R & D Center',
        'Marketed By',
        'Manufactured By',
        'Import By',
    ];

    public static function locationColumnsReady(): bool
    {
        if (!Schema::hasTable('companies')) {
            return false;
        }

        foreach (['country_id', 'state_id', 'city_id', 'district', 'post', 'village', 'pincode'] as $column) {
            if (!Schema::hasColumn('companies', $column)) {
                return false;
            }
        }

        return true;
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'company_category')->withTimestamps();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function certificates()
    {
        return $this->hasMany(CompanyFile::class, 'owner_id')
            ->where('owner_type', CompanyFile::OWNER_COMPANY)
            ->where('kind', CompanyFile::KIND_CERTIFICATE)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function documents()
    {
        return $this->hasMany(CompanyFile::class, 'owner_id')
            ->where('owner_type', CompanyFile::OWNER_COMPANY)
            ->where('kind', CompanyFile::KIND_DOCUMENT)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public static function locationChoices(array $input): array
    {
        $countryId = $input['country_id'] ?? null;
        $stateId = $input['state'] ?? null;
        $district = filled($input['district'] ?? null) ? trim((string) $input['district']) : null;
        $cityId = $input['city'] ?? null;
        $post = filled($input['post'] ?? null) ? trim((string) $input['post']) : null;
        $village = filled($input['village'] ?? null) ? trim((string) $input['village']) : null;

        $states = collect();
        if ($countryId) {
            $states = State::query()
                ->where('status', 1)
                ->where('country_id', $countryId)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $cities = collect();
        if ($countryId && $stateId) {
            $cities = City::query()
                ->where('status', 1)
                ->where('state_id', $stateId)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $districts = ($countryId && $stateId)
            ? self::distinctAccountLocations('district_business', [
                'country_id_business' => $countryId,
                'state_id_business' => $stateId,
            ])
            : [];

        $posts = ($countryId && $stateId && $district && $cityId)
            ? self::distinctAccountLocations('post_business', [
                'country_id_business' => $countryId,
                'state_id_business' => $stateId,
                'district_business' => $district,
                'city_id_business' => $cityId,
            ])
            : [];

        $villages = ($countryId && $stateId && $district && $cityId && $post)
            ? self::distinctAccountLocations('village_business', [
                'country_id_business' => $countryId,
                'state_id_business' => $stateId,
                'district_business' => $district,
                'city_id_business' => $cityId,
                'post_business' => $post,
            ])
            : [];

        $pincodes = ($countryId && $stateId && $district && $cityId && $post && $village)
            ? self::distinctAccountLocations('pincode_business', [
                'country_id_business' => $countryId,
                'state_id_business' => $stateId,
                'district_business' => $district,
                'city_id_business' => $cityId,
                'post_business' => $post,
                'village_business' => $village,
            ])
            : [];

        return [
            'states' => $states,
            'districts' => $districts,
            'cities' => $cities,
            'posts' => $posts,
            'villages' => $villages,
            'pincodes' => $pincodes,
        ];
    }

    private static function distinctAccountLocations(string $column, array $filters): array
    {
        if (!Schema::hasTable('user_details') || !Schema::hasColumn('user_details', $column)) {
            return [];
        }

        $query = UserDetails::query()
            ->whereNotNull($column)
            ->where($column, '!=', '');

        foreach ($filters as $filterColumn => $value) {
            if ($value === null || $value === '' || !Schema::hasColumn('user_details', $filterColumn)) {
                continue;
            }

            if (in_array($filterColumn, ['country_id_business', 'state_id_business', 'city_id_business'], true)) {
                $name = null;
                if ($filterColumn === 'state_id_business') {
                    $name = State::query()->where('id', $value)->value('name');
                } elseif ($filterColumn === 'city_id_business') {
                    $name = City::query()->where('id', $value)->value('name');
                }

                $query->where(function ($inner) use ($filterColumn, $value, $name) {
                    $inner->where($filterColumn, $value);
                    if ($name) {
                        $inner->orWhereRaw('LOWER(TRIM(' . $filterColumn . ')) = ?', [strtolower(trim((string) $name))]);
                    }
                });
                continue;
            }

            $query->whereRaw('LOWER(TRIM(' . $filterColumn . ')) = ?', [strtolower(trim((string) $value))]);
        }

        return $query->pluck($column)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->map(function ($value) {
                $value = trim((string) $value);

                return ['id' => $value, 'name' => $value];
            })
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }
}
