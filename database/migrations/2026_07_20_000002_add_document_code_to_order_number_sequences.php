<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_number_sequences', function (Blueprint $table) {
            $table->dropUnique('order_number_sequences_brand_fy_unique');
            $table->string('document_code', 5)->default('O')->after('brand_short_code');
            $table->unique(
                ['brand_short_code', 'document_code', 'financial_year_start'],
                'order_number_sequences_brand_document_fy_unique'
            );
        });

        if (Schema::hasTable('business_settings')
            && !DB::table('business_settings')->where('type', 'order_document_code')->exists()) {
            $values = [
                'type' => 'order_document_code',
                'value' => 'O',
            ];

            if (Schema::hasColumn('business_settings', 'lang')) {
                $values['lang'] = null;
            }
            if (Schema::hasColumn('business_settings', 'created_at')) {
                $values['created_at'] = now();
            }
            if (Schema::hasColumn('business_settings', 'updated_at')) {
                $values['updated_at'] = now();
            }

            DB::table('business_settings')->insert($values);
        }
    }

    public function down(): void
    {
        Schema::table('order_number_sequences', function (Blueprint $table) {
            $table->dropUnique('order_number_sequences_brand_document_fy_unique');
            $table->dropColumn('document_code');
            $table->unique(
                ['brand_short_code', 'financial_year_start'],
                'order_number_sequences_brand_fy_unique'
            );
        });
    }
};
