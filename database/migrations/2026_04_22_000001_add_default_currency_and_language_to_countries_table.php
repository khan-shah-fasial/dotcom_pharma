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
            if (!Schema::hasColumn('countries', 'default_currency_id')) {
                $table->unsignedInteger('default_currency_id')->nullable()->index()->after('status');
            }
            if (!Schema::hasColumn('countries', 'default_language_id')) {
                $table->unsignedInteger('default_language_id')->nullable()->index()->after('default_currency_id');
            }
        });

        // Foreign keys require InnoDB on MySQL/MariaDB.
        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        // Convert the table engine safely (no-op if already InnoDB).
        try {
            DB::statement('ALTER TABLE `countries` ENGINE=InnoDB');
        } catch (\Throwable $e) {
            // Ignore engine conversion failures; FK creation below will also fail and be ignored.
        }

        if (!Schema::hasTable('currencies') || !Schema::hasTable('languages')) {
            return;
        }

        // Add FK constraints if possible. Swallow errors if they already exist.
        try {
            Schema::table('countries', function (Blueprint $table) {
                $table
                    ->foreign('default_currency_id', 'countries_default_currency_id_fk')
                    ->references('id')
                    ->on('currencies')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('countries', function (Blueprint $table) {
                $table
                    ->foreign('default_language_id', 'countries_default_language_id_fk')
                    ->references('id')
                    ->on('languages')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('countries')) {
            return;
        }

        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            try {
                Schema::table('countries', function (Blueprint $table) {
                    $table->dropForeign('countries_default_currency_id_fk');
                });
            } catch (\Throwable $e) {
            }

            try {
                Schema::table('countries', function (Blueprint $table) {
                    $table->dropForeign('countries_default_language_id_fk');
                });
            } catch (\Throwable $e) {
            }
        }

        Schema::table('countries', function (Blueprint $table) {
            if (Schema::hasColumn('countries', 'default_language_id')) {
                $table->dropColumn('default_language_id');
            }
            if (Schema::hasColumn('countries', 'default_currency_id')) {
                $table->dropColumn('default_currency_id');
            }
        });
    }
};

