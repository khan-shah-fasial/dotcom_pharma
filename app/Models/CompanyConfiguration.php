<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CompanyConfiguration extends Model
{
    public const BILLING_NAME = 'Dotcom Pharma';

    protected $fillable = [
        'singleton',
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

    public static function tableReady(): bool
    {
        return Schema::hasTable('company_configurations')
            && Schema::hasTable('company_configuration_category');
    }

    public static function current(): ?self
    {
        if (!static::tableReady()) {
            return null;
        }

        return static::query()->orderBy('id')->first();
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'company_configuration_category')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function certificates()
    {
        return $this->hasMany(CompanyFile::class, 'owner_id')
            ->where('owner_type', CompanyFile::OWNER_BILLING)
            ->where('kind', CompanyFile::KIND_CERTIFICATE)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function documents()
    {
        return $this->hasMany(CompanyFile::class, 'owner_id')
            ->where('owner_type', CompanyFile::OWNER_BILLING)
            ->where('kind', CompanyFile::KIND_DOCUMENT)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
