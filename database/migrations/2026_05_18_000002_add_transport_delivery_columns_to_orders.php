<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'transport_id')) {
                $table->unsignedBigInteger('transport_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'booked_to_id')) {
                $table->unsignedBigInteger('booked_to_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'local_delivery_partner_id')) {
                $table->unsignedBigInteger('local_delivery_partner_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'transport_mode')) {
                $table->string('transport_mode', 30)->nullable();
            }
            if (!Schema::hasColumn('orders', 'transport_surface_mode')) {
                $table->string('transport_surface_mode', 30)->nullable();
            }
            if (!Schema::hasColumn('orders', 'transport_delivery_type')) {
                $table->string('transport_delivery_type', 60)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'transport_delivery_type',
                'transport_surface_mode',
                'transport_mode',
                'local_delivery_partner_id',
                'booked_to_id',
                'transport_id',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
