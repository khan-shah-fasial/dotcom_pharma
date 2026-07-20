<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('brand_short_code', 20);
            $table->unsignedSmallInteger('financial_year_start');
            $table->unsignedBigInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(
                ['brand_short_code', 'financial_year_start'],
                'order_number_sequences_brand_fy_unique'
            );
        });

        if (Schema::hasTable('business_settings')) {
            $now = now();
            $values = ['value' => 'DP'];

            if (Schema::hasColumn('business_settings', 'lang')) {
                $values['lang'] = null;
            }
            if (Schema::hasColumn('business_settings', 'created_at')) {
                $values['created_at'] = $now;
            }
            if (Schema::hasColumn('business_settings', 'updated_at')) {
                $values['updated_at'] = $now;
            }

            if (!DB::table('business_settings')->where('type', 'order_brand_short_code')->exists()) {
                DB::table('business_settings')->insert(array_merge(
                    ['type' => 'order_brand_short_code'],
                    $values
                ));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_number_sequences');
    }
};
