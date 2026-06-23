<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booked_to') && !Schema::hasColumn('booked_to', 'branch_address')) {
            Schema::table('booked_to', function (Blueprint $table) {
                $table->text('branch_address')->nullable()->after('branch_name');
            });
        }

        if (!Schema::hasTable('staff')) {
            return;
        }

        $columns = [
            'aadhaar_card_no' => fn (Blueprint $table) => $table->string('aadhaar_card_no', 12)->nullable()->after('display_email'),
            'pan_no' => fn (Blueprint $table) => $table->string('pan_no', 10)->nullable()->after('aadhaar_card_no'),
            'bank_details' => fn (Blueprint $table) => $table->text('bank_details')->nullable()->after('pan_no'),
            'attendance_id' => fn (Blueprint $table) => $table->string('attendance_id', 100)->nullable()->after('bank_details'),
            'attachments' => fn (Blueprint $table) => $table->text('attachments')->nullable()->after('attendance_id'),
            'emergency_contact_name' => fn (Blueprint $table) => $table->string('emergency_contact_name')->nullable()->after('attachments'),
            'emergency_contact_number' => fn (Blueprint $table) => $table->string('emergency_contact_number', 50)->nullable()->after('emergency_contact_name'),
            'date_of_birth' => fn (Blueprint $table) => $table->date('date_of_birth')->nullable()->after('emergency_contact_number'),
            'religion' => fn (Blueprint $table) => $table->string('religion', 100)->nullable()->after('date_of_birth'),
            'anniversary_date' => fn (Blueprint $table) => $table->date('anniversary_date')->nullable()->after('religion'),
        ];

        foreach ($columns as $column => $definition) {
            if (!Schema::hasColumn('staff', $column)) {
                Schema::table('staff', $definition);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff')) {
            $columns = [
                'anniversary_date',
                'religion',
                'date_of_birth',
                'emergency_contact_number',
                'emergency_contact_name',
                'attachments',
                'attendance_id',
                'bank_details',
                'pan_no',
                'aadhaar_card_no',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('staff', $column)) {
                    Schema::table('staff', function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }

        if (Schema::hasTable('booked_to') && Schema::hasColumn('booked_to', 'branch_address')) {
            Schema::table('booked_to', function (Blueprint $table) {
                $table->dropColumn('branch_address');
            });
        }
    }
};
