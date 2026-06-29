<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booked_to') && !Schema::hasColumn('booked_to', 'scanner')) {
            Schema::table('booked_to', function (Blueprint $table) {
                $table->unsignedBigInteger('scanner')->nullable()->after('branch_email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('booked_to') && Schema::hasColumn('booked_to', 'scanner')) {
            Schema::table('booked_to', function (Blueprint $table) {
                $table->dropColumn('scanner');
            });
        }
    }
};
