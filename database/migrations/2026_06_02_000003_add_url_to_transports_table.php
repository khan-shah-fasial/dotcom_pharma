<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            if (!Schema::hasColumn('transports', 'url')) {
                $table->string('url', 500)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            if (Schema::hasColumn('transports', 'url')) {
                $table->dropColumn('url');
            }
        });
    }
};
