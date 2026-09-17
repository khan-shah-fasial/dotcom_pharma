<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_stock_dimensions')) {
            return;
        }

        if (!Schema::hasColumn('product_stock_dimensions', 'outer_net')) {
            Schema::table('product_stock_dimensions', function (Blueprint $table) {
                $table->decimal('outer_net', 12, 3)->nullable()->after('piece_gross');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_stock_dimensions') && Schema::hasColumn('product_stock_dimensions', 'outer_net')) {
            Schema::table('product_stock_dimensions', function (Blueprint $table) {
                $table->dropColumn('outer_net');
            });
        }
    }
};
