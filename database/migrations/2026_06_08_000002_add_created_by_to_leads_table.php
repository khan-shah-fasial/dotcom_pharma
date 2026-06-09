<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('leads') || Schema::hasColumn('leads', 'created_by')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedInteger('created_by')->nullable()->after('assigned_to');
            $table->index('created_by');
        });

        $adminId = DB::table('users')->where('user_type', 'admin')->value('id');
        if ($adminId) {
            DB::table('leads')->whereNull('created_by')->update(['created_by' => $adminId]);
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        if (!Schema::hasTable('leads') || !Schema::hasColumn('leads', 'created_by')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
