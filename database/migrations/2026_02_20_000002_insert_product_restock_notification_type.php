<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $defaultLang = env('DEFAULT_LANGUAGE', 'en');

        // Insert notification type if not exists
        $typeId = DB::table('notification_types')->where('type', 'product_restock')->value('id');

        if (!$typeId) {
            $typeId = DB::table('notification_types')->insertGetId([
                'user_type'    => 'customer',
                'type'         => 'product_restock',
                'name'         => 'Product Restock',
                'image'        => null,
                'default_text' => '[[product_name]] is back with [[variant_count]] variant(s) restocked: [[variant_names]].',
                'status'       => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        } else {
            DB::table('notification_types')
                ->where('id', $typeId)
                ->update([
                    'default_text' => '[[product_name]] is back with [[variant_count]] variant(s) restocked: [[variant_names]].',
                    'updated_at'   => $now,
                ]);
        }

        // Ensure translation row exists
        $hasTranslation = DB::table('notification_type_translations')
            ->where('notification_type_id', $typeId)
            ->where('lang', $defaultLang)
            ->exists();

        if (!$hasTranslation) {
            DB::table('notification_type_translations')->insert([
                'notification_type_id' => $typeId,
                'lang'                 => $defaultLang,
                'name'                 => 'Product Restock',
                'default_text'         => '[[product_name]] is back with [[variant_count]] variant(s) restocked: [[variant_names]].',
            ]);
        }
    }

    public function down(): void
    {
        // Keep the notification type; safe rollback not required.
    }
};
