<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('product_batches', 'discount')) {
                $table->decimal('discount', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('product_batches', 'discount_type')) {
                $table->enum('discount_type', ['percent', 'flat'])->nullable();
            }
            if (!Schema::hasColumn('product_batches', 'discount_active')) {
                $table->tinyInteger('discount_active')->default(0);
            }
            if (!Schema::hasColumn('product_batches', 'discount_start_date')) {
                $table->integer('discount_start_date')->nullable();
            }
            if (!Schema::hasColumn('product_batches', 'discount_end_date')) {
                $table->integer('discount_end_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            if (Schema::hasColumn('product_batches', 'discount_end_date')) {
                $table->dropColumn('discount_end_date');
            }
            if (Schema::hasColumn('product_batches', 'discount_start_date')) {
                $table->dropColumn('discount_start_date');
            }
            if (Schema::hasColumn('product_batches', 'discount_active')) {
                $table->dropColumn('discount_active');
            }
            if (Schema::hasColumn('product_batches', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
            if (Schema::hasColumn('product_batches', 'discount')) {
                $table->dropColumn('discount');
            }
        });
    }
};

