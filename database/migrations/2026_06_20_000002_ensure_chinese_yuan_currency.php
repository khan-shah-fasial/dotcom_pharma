<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('currencies') || DB::table('currencies')->where('code', 'CNY')->exists()) {
            return;
        }

        DB::table('currencies')->insert([
            'name' => 'Chinese Yuan',
            'symbol' => '¥',
            'exchange_rate' => 0.084,
            'status' => 1,
            'code' => 'CNY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::forget('all_active_currency');
    }

    public function down(): void
    {
        // Do not remove a currency that may already be in use by orders or country defaults.
    }
};
