<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleAndScheduleToProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'role_label')) {
                $table->string('role_label')->nullable()->after('drug_name');
            }
            if (!Schema::hasColumn('products', 'schedule')) {
                $table->string('schedule')->nullable()->after('role_label');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'schedule')) {
                $table->dropColumn('schedule');
            }
            if (Schema::hasColumn('products', 'role_label')) {
                $table->dropColumn('role_label');
            }
        });
    }
}
