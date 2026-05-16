<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_batches') && Schema::hasColumn('product_batches', 'product_exp_date')) {
            DB::statement("
                UPDATE product_batches
                SET product_exp_date = LAST_DAY(product_exp_date)
                WHERE product_exp_date IS NOT NULL
                    AND product_exp_date <> ''
                    AND product_exp_date REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
            ");
        }

        if (Schema::hasTable('product_stocks') && Schema::hasColumn('product_stocks', 'product_exp_date')) {
            DB::statement("
                UPDATE product_stocks
                SET product_exp_date = LAST_DAY(product_exp_date)
                WHERE product_exp_date IS NOT NULL
                    AND product_exp_date <> ''
                    AND product_exp_date REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
            ");
        }
    }

    public function down(): void
    {
        // One-way data normalization. Do not convert month-end expiry dates back to the first day.
    }
};
