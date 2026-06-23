<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff')) {
            return;
        }

        $columns = [
            'bank_account_holder_name' => fn (Blueprint $table) => $table->string('bank_account_holder_name')->nullable()->after('bank_details'),
            'bank_name' => fn (Blueprint $table) => $table->string('bank_name')->nullable()->after('bank_account_holder_name'),
            'bank_branch_name' => fn (Blueprint $table) => $table->string('bank_branch_name')->nullable()->after('bank_name'),
            'bank_account_number' => fn (Blueprint $table) => $table->string('bank_account_number', 34)->nullable()->after('bank_branch_name'),
            'bank_account_type' => fn (Blueprint $table) => $table->string('bank_account_type', 20)->nullable()->after('bank_account_number'),
            'bank_ifsc_code' => fn (Blueprint $table) => $table->string('bank_ifsc_code', 11)->nullable()->after('bank_account_type'),
        ];

        foreach ($columns as $column => $definition) {
            if (!Schema::hasColumn('staff', $column)) {
                Schema::table('staff', $definition);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('staff')) {
            return;
        }

        foreach ([
            'bank_ifsc_code',
            'bank_account_type',
            'bank_account_number',
            'bank_branch_name',
            'bank_name',
            'bank_account_holder_name',
        ] as $column) {
            if (Schema::hasColumn('staff', $column)) {
                Schema::table('staff', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
