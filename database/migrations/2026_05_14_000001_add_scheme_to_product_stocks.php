<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_stocks')) {
            return;
        }

        Schema::table('product_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('product_stocks', 'scheme')) {
                $table->unsignedInteger('scheme')->default(0)->after('min_qty');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_stocks')) {
            return;
        }

        Schema::table('product_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('product_stocks', 'scheme')) {
                $table->dropColumn('scheme');
            }
        });
    }
};
