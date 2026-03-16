<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDesignationAndAreaToStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'designation')) {
                $table->string('designation')->nullable()->after('role_id');
            }

            if (!Schema::hasColumn('staff', 'area_assignments')) {
                $table->json('area_assignments')->nullable()->after('designation');
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
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'area_assignments')) {
                $table->dropColumn('area_assignments');
            }

            if (Schema::hasColumn('staff', 'designation')) {
                $table->dropColumn('designation');
            }
        });
    }
}

