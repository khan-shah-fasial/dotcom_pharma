<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'salesman')) {
                $table->string('salesman')->nullable()->after('booked_to');
            }
            if (! Schema::hasColumn('user_details', 'dl_expiry')) {
                $table->string('dl_expiry', 50)->nullable()->after('salesman');
            }
            if (! Schema::hasColumn('user_details', 'dl1')) {
                $table->string('dl1')->nullable()->after('dl_expiry');
            }
            if (! Schema::hasColumn('user_details', 'dl2')) {
                $table->string('dl2')->nullable()->after('dl1');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'dl2')) {
                $table->dropColumn('dl2');
            }
            if (Schema::hasColumn('user_details', 'dl1')) {
                $table->dropColumn('dl1');
            }
            if (Schema::hasColumn('user_details', 'dl_expiry')) {
                $table->dropColumn('dl_expiry');
            }
            if (Schema::hasColumn('user_details', 'salesman')) {
                $table->dropColumn('salesman');
            }
        });
    }
};
