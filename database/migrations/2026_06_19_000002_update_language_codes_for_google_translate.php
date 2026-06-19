<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $googleCodeMap = [
        'in-as' => 'as',
        'in-bfz' => 'bfz',
        'in-bhb' => 'bhb',
        'in-brx' => 'brx',
        'in-doi' => 'doi',
        'in-gbm' => 'gbm',
        'in-gon' => 'gon',
        'in-gu' => 'gu',
        'in-hi' => 'hi',
        'in-kn' => 'kn',
        'in-kfy' => 'kfy',
        'in-khr' => 'khr',
        'in-kok' => 'gom',
        'in-kru' => 'kru',
        'in-ks' => 'ks',
        'in-lus' => 'lus',
        'in-mai' => 'mai',
        'in-ml' => 'ml',
        'in-mni' => 'mni-Mtei',
        'in-mr' => 'mr',
        'in-or' => 'or',
        'in-pa' => 'pa',
        'in-raj' => 'raj',
        'in-sa' => 'sa',
        'in-sat' => 'sat',
        'in-sd' => 'sd',
        'in-tcy' => 'tcy',
        'in-te' => 'te',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('languages')) {
            return;
        }

        foreach ($this->googleCodeMap as $oldCode => $googleCode) {
            $hasOldCode = DB::table('languages')->where('code', $oldCode)->exists();
            $hasGoogleCode = DB::table('languages')->where('code', $googleCode)->exists();

            if ($hasOldCode && !$hasGoogleCode) {
                DB::table('languages')
                    ->where('code', $oldCode)
                    ->update(['code' => $googleCode]);
            }
        }

        Cache::forget('all_active_languages');
        Cache::forget('active_countries');
    }

    public function down(): void
    {
        if (!Schema::hasTable('languages')) {
            return;
        }

        foreach (array_reverse($this->googleCodeMap) as $oldCode => $googleCode) {
            $hasGoogleCode = DB::table('languages')->where('code', $googleCode)->exists();
            $hasOldCode = DB::table('languages')->where('code', $oldCode)->exists();

            if ($hasGoogleCode && !$hasOldCode) {
                DB::table('languages')
                    ->where('code', $googleCode)
                    ->update(['code' => $oldCode]);
            }
        }

        Cache::forget('all_active_languages');
        Cache::forget('active_countries');
    }
};
