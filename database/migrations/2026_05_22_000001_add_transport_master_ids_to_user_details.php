<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransportMasterIdsToUserDetails extends Migration
{
    public function up()
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (!Schema::hasColumn('user_details', 'transport_id')) {
                $table->unsignedBigInteger('transport_id')->nullable()->after('crm_id');
            }

            if (!Schema::hasColumn('user_details', 'booked_to_id')) {
                $table->unsignedBigInteger('booked_to_id')->nullable()->after('transport_id');
            }
        });
    }

    public function down()
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'booked_to_id')) {
                $table->dropColumn('booked_to_id');
            }

            if (Schema::hasColumn('user_details', 'transport_id')) {
                $table->dropColumn('transport_id');
            }
        });
    }
}
