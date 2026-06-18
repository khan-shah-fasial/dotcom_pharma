<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('booked_to')) {
            return;
        }

        if (!Schema::hasColumn('booked_to', 'location')) {
            Schema::table('booked_to', function (Blueprint $table) {
                $table->string('location')->nullable()->after('transport_id');
            });
        }

        Schema::table('booked_to', function (Blueprint $table) {
            if (!Schema::hasColumn('booked_to', 'branch_name')) {
                $table->string('branch_name')->nullable()->after('location');
            }
            if (!Schema::hasColumn('booked_to', 'branch_code')) {
                $table->string('branch_code')->nullable()->after('branch_name');
            }
            if (!Schema::hasColumn('booked_to', 'branch_gst_number')) {
                $table->string('branch_gst_number')->nullable()->after('branch_code');
            }
            if (!Schema::hasColumn('booked_to', 'branch_mobile_number')) {
                $table->string('branch_mobile_number', 50)->nullable()->after('branch_gst_number');
            }
            if (!Schema::hasColumn('booked_to', 'branch_alternate_mobile_number')) {
                $table->string('branch_alternate_mobile_number', 50)->nullable()->after('branch_mobile_number');
            }
            if (!Schema::hasColumn('booked_to', 'contact_incharge')) {
                $table->string('contact_incharge')->nullable()->after('branch_alternate_mobile_number');
            }
            if (!Schema::hasColumn('booked_to', 'branch_email')) {
                $table->string('branch_email')->nullable()->after('contact_incharge');
            }
        });

        if (Schema::hasColumn('booked_to', 'name')) {
            DB::table('booked_to')
                ->whereNull('location')
                ->update(['location' => DB::raw('name')]);

            Schema::table('booked_to', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('booked_to')) {
            return;
        }

        if (!Schema::hasColumn('booked_to', 'name')) {
            Schema::table('booked_to', function (Blueprint $table) {
                $table->string('name')->nullable()->after('transport_id');
            });
        }

        if (Schema::hasColumn('booked_to', 'location')) {
            DB::table('booked_to')
                ->whereNull('name')
                ->update(['name' => DB::raw('location')]);
        }

        Schema::table('booked_to', function (Blueprint $table) {
            foreach ([
                'branch_email',
                'contact_incharge',
                'branch_alternate_mobile_number',
                'branch_mobile_number',
                'branch_gst_number',
                'branch_code',
                'branch_name',
                'location',
            ] as $column) {
                if (Schema::hasColumn('booked_to', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
