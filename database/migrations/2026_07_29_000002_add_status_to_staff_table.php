<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('staff') && !Schema::hasColumn('staff', 'status')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->tinyInteger('status')->default(1)->index()->after('role_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('staff') && Schema::hasColumn('staff', 'status')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
