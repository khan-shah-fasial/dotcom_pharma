<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_details') && !Schema::hasColumn('user_details', 'customer_type')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->string('customer_type', 100)->nullable()->after('company_name');
            });
        }

        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'customer_type')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('customer_type', 100)->nullable()->after('company_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'customer_type')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('customer_type');
            });
        }

        if (Schema::hasTable('user_details') && Schema::hasColumn('user_details', 'customer_type')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->dropColumn('customer_type');
            });
        }
    }
};
