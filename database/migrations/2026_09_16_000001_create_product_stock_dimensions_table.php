<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_stock_dimensions')) {
            return;
        }

        Schema::create('product_stock_dimensions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('product_stock_id')->unique();
            $table->decimal('piece_gross', 12, 3)->nullable();
            $table->decimal('weight_buffer_per_case', 12, 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_dimensions');
    }
};
