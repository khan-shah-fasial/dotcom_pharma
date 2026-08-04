<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_number_sequences', function (Blueprint $table) {
            $table->string('brand_short_code', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_number_sequences', function (Blueprint $table) {
            $table->string('brand_short_code', 20)->change();
        });
    }
};
