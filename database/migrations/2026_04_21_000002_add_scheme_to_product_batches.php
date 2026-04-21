<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_batches')) {
            return;
        }

        Schema::table('product_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('product_batches', 'scheme')) {
                $table->unsignedInteger('scheme')->nullable()->after('qty');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_batches')) {
            return;
        }

        Schema::table('product_batches', function (Blueprint $table) {
            if (Schema::hasColumn('product_batches', 'scheme')) {
                $table->dropColumn('scheme');
            }
        });
    }
};
