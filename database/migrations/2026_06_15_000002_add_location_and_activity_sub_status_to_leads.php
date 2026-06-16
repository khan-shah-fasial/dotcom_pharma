<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $missingLeadColumns = collect(['address', 'country_id', 'state_id', 'city_id', 'pincode'])
            ->reject(fn ($column) => Schema::hasColumn('leads', $column));

        if ($missingLeadColumns->isNotEmpty()) {
            Schema::table('leads', function (Blueprint $table) use ($missingLeadColumns) {
                if ($missingLeadColumns->contains('address')) {
                $table->text('address')->nullable()->after('whatsapp_number');
                }
                if ($missingLeadColumns->contains('country_id')) {
                $table->unsignedInteger('country_id')->nullable()->after('address')->index();
                }
                if ($missingLeadColumns->contains('state_id')) {
                $table->unsignedInteger('state_id')->nullable()->after('country_id')->index();
                }
                if ($missingLeadColumns->contains('city_id')) {
                $table->unsignedInteger('city_id')->nullable()->after('state_id')->index();
                }
                if ($missingLeadColumns->contains('pincode')) {
                $table->string('pincode', 20)->nullable()->after('city_id');
                }
            });
        }

        if (!Schema::hasColumn('lead_activities', 'activity_sub_status')) {
            Schema::table('lead_activities', function (Blueprint $table) {
                $table->string('activity_sub_status', 50)->nullable()->after('activity_type')->index();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('lead_activities', 'activity_sub_status')) {
            Schema::table('lead_activities', function (Blueprint $table) {
                $table->dropIndex(['activity_sub_status']);
                $table->dropColumn('activity_sub_status');
            });
        }

        $existingLeadColumns = collect(['address', 'country_id', 'state_id', 'city_id', 'pincode'])
            ->filter(fn ($column) => Schema::hasColumn('leads', $column));

        if ($existingLeadColumns->isNotEmpty()) {
            Schema::table('leads', function (Blueprint $table) use ($existingLeadColumns) {
                foreach (['country_id', 'state_id', 'city_id'] as $column) {
                    if ($existingLeadColumns->contains($column)) {
                    $table->dropIndex([$column]);
                    }
                }
                $table->dropColumn($existingLeadColumns->all());
            });
        }
    }
};
