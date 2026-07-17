<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->string('default_shipping_method', 20)->nullable();
            $table->string('default_transport_mode', 20)->nullable();
            $table->string('default_transport_surface_mode', 20)->nullable();
            $table->string('default_delivery_type', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn([
                'default_shipping_method',
                'default_transport_mode',
                'default_transport_surface_mode',
                'default_delivery_type',
            ]);
        });
    }
};
