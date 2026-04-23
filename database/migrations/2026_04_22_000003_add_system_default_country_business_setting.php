<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        $exists = DB::table('business_settings')
            ->where('type', 'system_default_country')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('business_settings')->insert([
            'type' => 'system_default_country',
            'value' => null,
            'lang' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        DB::table('business_settings')->where('type', 'system_default_country')->delete();
    }
};

