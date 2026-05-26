<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shipping_methods')) {
            return;
        }

        $now = now();
        $hasModes = Schema::hasColumn('shipping_methods', 'modes');
        $hasCreatedAt = Schema::hasColumn('shipping_methods', 'created_at');
        $hasUpdatedAt = Schema::hasColumn('shipping_methods', 'updated_at');
        $methods = [
            [
                'name' => 'Blue Dart',
                'slug' => 'bluedart',
                'is_active' => 1,
                'modes' => json_encode(['prepaid', 'cod']),
            ],
            [
                'name' => 'DHL',
                'slug' => 'dhl',
                'is_active' => 1,
                'modes' => json_encode(['prepaid', 'cod']),
            ],
            [
                'name' => 'Delhivery',
                'slug' => 'delhivery',
                'is_active' => 1,
                'modes' => json_encode(['prepaid', 'cod']),
            ],
        ];

        foreach ($methods as $method) {
            if (!$hasModes) {
                unset($method['modes']);
            }

            $exists = DB::table('shipping_methods')->where('slug', $method['slug'])->exists();

            if ($exists) {
                $updates = $method;
                unset($updates['slug']);
                if ($hasUpdatedAt) {
                    $updates['updated_at'] = $now;
                }

                DB::table('shipping_methods')
                    ->where('slug', $method['slug'])
                    ->update($updates);
                continue;
            }

            if ($hasCreatedAt) {
                $method['created_at'] = $now;
            }
            if ($hasUpdatedAt) {
                $method['updated_at'] = $now;
            }

            DB::table('shipping_methods')->insert($method);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('shipping_methods')) {
            return;
        }

        DB::table('shipping_methods')->whereIn('slug', ['bluedart', 'dhl', 'delhivery'])->delete();
    }
};
