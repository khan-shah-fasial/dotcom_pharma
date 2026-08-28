<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->boolean('cash_on_delivery')->default(true)->after('default_delivery_type');
            $table->boolean('free_shipping')->default(false)->after('cash_on_delivery');
            $table->boolean('has_warranty')->default(false)->after('free_shipping');
            $table->boolean('refundable')->default(true)->after('has_warranty');
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn([
                'cash_on_delivery',
                'free_shipping',
                'has_warranty',
                'refundable',
            ]);
        });
    }
};
