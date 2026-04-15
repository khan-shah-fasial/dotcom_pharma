<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('order_details', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('tax');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (Schema::hasColumn('order_details', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
        });
    }
};
