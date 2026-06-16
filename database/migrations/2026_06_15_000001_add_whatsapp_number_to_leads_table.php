<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('leads') || Schema::hasColumn('leads', 'whatsapp_number')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->string('whatsapp_number', 50)->nullable()->after('phone');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('leads') || !Schema::hasColumn('leads', 'whatsapp_number')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('whatsapp_number');
        });
    }
};
