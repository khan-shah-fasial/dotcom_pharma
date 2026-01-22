<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->string('disk')->default('local')->after('file_name');
        });

        // Backfill existing rows to keep resolution fast and deterministic
        DB::table('uploads')->whereNull('disk')->update(['disk' => 'local']);
    }

    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn('disk');
        });
    }
};
