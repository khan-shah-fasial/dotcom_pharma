<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff') && !Schema::hasColumn('staff', 'display_email')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->string('display_email')->nullable()->after('designation');
            });
        }

        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'alternate_mobile_number')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('alternate_mobile_number', 50)->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'alternate_mobile_number')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('alternate_mobile_number');
            });
        }

        if (Schema::hasTable('staff') && Schema::hasColumn('staff', 'display_email')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('display_email');
            });
        }
    }
};
