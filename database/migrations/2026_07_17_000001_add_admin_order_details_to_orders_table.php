<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('challan_number')->nullable();
            $table->string('cases')->nullable();
            $table->string('attached_file_name')->nullable();
            $table->string('pm_accountant_name')->nullable();
            $table->string('lr_number')->nullable();
            $table->string('cc_attached_path')->nullable();
            $table->boolean('freight_paid')->default(false);
            $table->text('transport_details')->nullable();
            $table->unsignedBigInteger('sales_person_id')->nullable()->index();
            $table->string('weight')->nullable();
            $table->string('dimensions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['sales_person_id']);
            $table->dropColumn([
                'challan_number',
                'cases',
                'attached_file_name',
                'pm_accountant_name',
                'lr_number',
                'cc_attached_path',
                'freight_paid',
                'transport_details',
                'sales_person_id',
                'weight',
                'dimensions',
            ]);
        });
    }
};
