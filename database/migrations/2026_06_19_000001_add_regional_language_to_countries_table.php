<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('countries')) {
            return;
        }

        Schema::table('countries', function (Blueprint $table) {
            if (!Schema::hasColumn('countries', 'regional_language')) {
                if (Schema::hasColumn('countries', 'default_language_id')) {
                    $table->json('regional_language')->nullable()->after('default_language_id');
                } else {
                    $table->json('regional_language')->nullable();
                }
            }
        });

        if (Schema::hasTable('country_regional_language')) {
            DB::table('country_regional_language')
                ->select('country_id', 'language_id')
                ->orderBy('country_id')
                ->get()
                ->groupBy('country_id')
                ->each(function ($languages, $countryId) {
                    $languageIds = $languages
                        ->pluck('language_id')
                        ->map(function ($languageId) {
                            return (int) $languageId;
                        })
                        ->values()
                        ->all();

                    DB::table('countries')
                        ->where('id', $countryId)
                        ->update(['regional_language' => json_encode($languageIds)]);
                });

            Schema::dropIfExists('country_regional_language');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('countries')) {
            return;
        }

        Schema::table('countries', function (Blueprint $table) {
            if (Schema::hasColumn('countries', 'regional_language')) {
                $table->dropColumn('regional_language');
            }
        });
    }
};
