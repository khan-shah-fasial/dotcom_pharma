<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_history', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_history', 'invoice_series')) {
                $table->string('invoice_series')->nullable()->after('invoice_date');
            }
            if (! Schema::hasColumn('purchase_history', 'tax_code')) {
                $table->string('tax_code')->nullable()->after('taxable_amount');
            }
            if (! Schema::hasColumn('purchase_history', 'late_by')) {
                $table->string('late_by')->nullable()->after('lr_date');
            }
        });

        // Rename tax columns to GST columns if they exist
        Schema::table('purchase_history', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_history', 'tax_percentage') && ! Schema::hasColumn('purchase_history', 'gst_percentage')) {
                $table->renameColumn('tax_percentage', 'gst_percentage');
            }
            if (Schema::hasColumn('purchase_history', 'tax_amount') && ! Schema::hasColumn('purchase_history', 'gst_amount')) {
                $table->renameColumn('tax_amount', 'gst_amount');
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
        Schema::table('purchase_history', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_history', 'invoice_series')) {
                $table->dropColumn('invoice_series');
            }
            if (Schema::hasColumn('purchase_history', 'tax_code')) {
                $table->dropColumn('tax_code');
            }
            if (Schema::hasColumn('purchase_history', 'late_by')) {
                $table->dropColumn('late_by');
            }
            if (Schema::hasColumn('purchase_history', 'gst_percentage') && ! Schema::hasColumn('purchase_history', 'tax_percentage')) {
                $table->renameColumn('gst_percentage', 'tax_percentage');
            }
            if (Schema::hasColumn('purchase_history', 'gst_amount') && ! Schema::hasColumn('purchase_history', 'tax_amount')) {
                $table->renameColumn('gst_amount', 'tax_amount');
            }
        });
    }
};

