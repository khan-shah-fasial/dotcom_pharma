<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'mrp_price')) {
                $table->decimal('mrp_price', 20, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('carts', 'sale_price')) {
                $table->decimal('sale_price', 20, 2)->nullable()->after('mrp_price');
            }
        });

        Schema::table('order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('order_details', 'mrp_price')) {
                $table->decimal('mrp_price', 20, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('order_details', 'sale_price')) {
                $table->decimal('sale_price', 20, 2)->nullable()->after('mrp_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (Schema::hasColumn('order_details', 'sale_price')) {
                $table->dropColumn('sale_price');
            }
            if (Schema::hasColumn('order_details', 'mrp_price')) {
                $table->dropColumn('mrp_price');
            }
        });

        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'sale_price')) {
                $table->dropColumn('sale_price');
            }
            if (Schema::hasColumn('carts', 'mrp_price')) {
                $table->dropColumn('mrp_price');
            }
        });
    }
};
