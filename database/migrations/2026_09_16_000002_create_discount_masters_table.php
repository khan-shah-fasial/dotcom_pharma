<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('discount_masters')) {
            return;
        }

        Schema::create('discount_masters', function (Blueprint $table) {
            $table->id();
            $table->string('applied_on', 30)->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('product_stock_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('role_key', 20)->nullable()->index();
            $table->decimal('qty_slab_from', 15, 3)->nullable();
            $table->decimal('qty_slab_to', 15, 3)->nullable();
            $table->decimal('rate', 20, 4)->nullable();
            $table->decimal('amount', 20, 4)->nullable();
            $table->decimal('effective_rate', 20, 4)->nullable();
            $table->string('discount_type', 30)->index();
            $table->string('discount_code', 40)->unique();
            $table->string('value_type', 20)->nullable();
            $table->decimal('value_amount', 20, 4)->nullable();
            $table->decimal('value_percent', 12, 4)->nullable();
            $table->decimal('earn', 15, 3)->nullable();
            $table->decimal('invoice_amount', 20, 4)->nullable();
            $table->decimal('scheme_free_qty', 15, 3)->nullable();
            $table->decimal('scheme_percent', 12, 4)->nullable();
            $table->decimal('scheme_value', 20, 4)->nullable();
            $table->boolean('scheme_product_is_same')->nullable();
            $table->unsignedBigInteger('scheme_product_stock_id')->nullable()->index();
            $table->date('from_date')->nullable()->index();
            $table->date('to_date')->nullable()->index();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_masters');
    }
};
