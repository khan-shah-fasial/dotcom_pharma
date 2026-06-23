<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('user_details', 'current_status')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->string('current_status', 50)->nullable()->after('company_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_details', 'current_status')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->dropColumn('current_status');
            });
        }
    }
};
