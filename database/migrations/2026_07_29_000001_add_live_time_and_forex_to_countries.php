<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            if (!Schema::hasColumn('countries', 'timezone')) {
                $table->string('timezone', 64)->nullable()->after('capital');
            }
            if (!Schema::hasColumn('countries', 'forex_rate')) {
                $table->decimal('forex_rate', 20, 8)->nullable()->after('timezone');
            }
            if (!Schema::hasColumn('countries', 'forex_base_currency_code')) {
                $table->string('forex_base_currency_code', 3)->nullable()->after('forex_rate');
            }
            if (!Schema::hasColumn('countries', 'forex_rate_updated_at')) {
                $table->timestamp('forex_rate_updated_at')->nullable()->after('forex_base_currency_code');
            }
        });

        $primaryTimezoneOverrides = [
            'AR' => 'America/Argentina/Buenos_Aires',
            'AU' => 'Australia/Sydney',
            'AN' => 'America/Curacao',
            'BR' => 'America/Sao_Paulo',
            'BV' => 'Europe/Oslo',
            'CA' => 'America/Toronto',
            'CD' => 'Africa/Kinshasa',
            'CL' => 'America/Santiago',
            'CN' => 'Asia/Shanghai',
            'CY' => 'Asia/Nicosia',
            'DK' => 'Europe/Copenhagen',
            'EC' => 'America/Guayaquil',
            'ES' => 'Europe/Madrid',
            'FM' => 'Pacific/Pohnpei',
            'FR' => 'Europe/Paris',
            'GB' => 'Europe/London',
            'GL' => 'America/Nuuk',
            'HM' => 'Indian/Kerguelen',
            'ID' => 'Asia/Jakarta',
            'IN' => 'Asia/Kolkata',
            'KI' => 'Pacific/Tarawa',
            'KZ' => 'Asia/Almaty',
            'MH' => 'Pacific/Majuro',
            'MN' => 'Asia/Ulaanbaatar',
            'MX' => 'America/Mexico_City',
            'MY' => 'Asia/Kuala_Lumpur',
            'NL' => 'Europe/Amsterdam',
            'NO' => 'Europe/Oslo',
            'NZ' => 'Pacific/Auckland',
            'PG' => 'Pacific/Port_Moresby',
            'PF' => 'Pacific/Tahiti',
            'PS' => 'Asia/Hebron',
            'PT' => 'Europe/Lisbon',
            'RU' => 'Europe/Moscow',
            'UA' => 'Europe/Kyiv',
            'US' => 'America/New_York',
            'UZ' => 'Asia/Tashkent',
            'TP' => 'Asia/Dili',
            'XA' => 'Australia/Sydney',
            'XG' => 'Europe/London',
            'XJ' => 'Europe/Jersey',
            'XM' => 'Europe/Isle_of_Man',
            'XU' => 'Europe/Guernsey',
            'YU' => 'Europe/Belgrade',
        ];

        DB::table('countries')
            ->whereNull('timezone')
            ->orderBy('id')
            ->get(['id', 'code'])
            ->each(function ($country) use ($primaryTimezoneOverrides) {
                $iso2 = strtoupper(trim((string) $country->code));
                $timezone = $primaryTimezoneOverrides[$iso2] ?? null;

                if (!$timezone && preg_match('/^[A-Z]{2}$/', $iso2)) {
                    $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $iso2);
                    $timezone = $timezones[0] ?? null;
                }

                if ($timezone) {
                    DB::table('countries')->where('id', $country->id)->update([
                        'timezone' => $timezone,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            foreach ([
                'forex_rate_updated_at',
                'forex_base_currency_code',
                'forex_rate',
                'timezone',
            ] as $column) {
                if (Schema::hasColumn('countries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
