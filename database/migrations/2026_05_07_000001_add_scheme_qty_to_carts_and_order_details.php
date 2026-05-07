<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'scheme_qty')) {
                $table->dropColumn('scheme_qty');
            }
            if (!Schema::hasColumn('carts', 'is_scheme')) {
                $table->boolean('is_scheme')->default(false)->after('quantity');
            }
        });

        Schema::table('order_details', function (Blueprint $table) {
            if (Schema::hasColumn('order_details', 'scheme_qty')) {
                $table->dropColumn('scheme_qty');
            }
            if (!Schema::hasColumn('order_details', 'is_scheme')) {
                $table->boolean('is_scheme')->default(false)->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (Schema::hasColumn('order_details', 'is_scheme')) {
                $table->dropColumn('is_scheme');
            }
        });

        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'is_scheme')) {
                $table->dropColumn('is_scheme');
            }
        });
    }
};
