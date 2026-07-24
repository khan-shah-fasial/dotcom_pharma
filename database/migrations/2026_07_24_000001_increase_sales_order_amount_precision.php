<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->decimal('price', 20, 3)->nullable()->change();
            $table->decimal('before_productandbatch_discount', 20, 3)->nullable()->change();
            $table->decimal('mrp_price', 20, 3)->nullable()->change();
            $table->decimal('sale_price', 20, 3)->nullable()->change();
            $table->decimal('tax', 20, 3)->default(0)->change();
            $table->decimal('discount_amount', 10, 3)->default(0)->change();
            $table->decimal('shipping_cost', 20, 3)->default(0)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('grand_total', 20, 3)->nullable()->change();
            $table->decimal('coupon_discount', 20, 3)->default(0)->change();
        });

        Schema::table('combined_orders', function (Blueprint $table) {
            $table->decimal('grand_total', 20, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->double('price', 20, 2)->nullable()->change();
            $table->decimal('before_productandbatch_discount', 20, 2)->nullable()->change();
            $table->decimal('mrp_price', 20, 2)->nullable()->change();
            $table->decimal('sale_price', 20, 2)->nullable()->change();
            $table->double('tax', 20, 2)->default(0)->change();
            $table->decimal('discount_amount', 10, 2)->default(0)->change();
            $table->double('shipping_cost', 20, 2)->default(0)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->double('grand_total', 20, 2)->nullable()->change();
            $table->double('coupon_discount', 20, 2)->default(0)->change();
        });

        Schema::table('combined_orders', function (Blueprint $table) {
            $table->double('grand_total', 20, 2)->default(0)->change();
        });
    }
};
