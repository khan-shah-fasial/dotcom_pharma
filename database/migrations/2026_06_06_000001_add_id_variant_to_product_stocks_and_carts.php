<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_stocks')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                if (!Schema::hasColumn('product_stocks', 'id_variant')) {
                    $table->string('id_variant')->nullable()->after('variant');
                }
            });
        }

        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                if (!Schema::hasColumn('carts', 'id_variant')) {
                    $table->string('id_variant')->nullable()->after('variation');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                if (Schema::hasColumn('carts', 'id_variant')) {
                    $table->dropColumn('id_variant');
                }
            });
        }

        if (Schema::hasTable('product_stocks')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                if (Schema::hasColumn('product_stocks', 'id_variant')) {
                    $table->dropColumn('id_variant');
                }
            });
        }
    }
};
