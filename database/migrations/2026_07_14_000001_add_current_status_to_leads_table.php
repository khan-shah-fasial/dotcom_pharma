<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'current_status')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('current_status', 50)->nullable()->after('customer_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'current_status')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('current_status');
            });
        }
    }
};
