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
        if (! Schema::hasTable('purchase_history')) {
            Schema::create('purchase_history', function (Blueprint $table) {
                $table->bigIncrements('id');

                // Core identifiers and dates
                $table->string('serial_number')->nullable();
                $table->string('order_date')->nullable();
                $table->string('order_number')->nullable();
                $table->string('invoice_date')->nullable();
                $table->string('invoice_number')->nullable();

                // Customer / account linkage
                $table->string('ac_number')->nullable()->index();

                // Product linkage
                $table->string('product_sku')->nullable()->index();
                $table->string('batch_number')->nullable();
                $table->string('expiry_date')->nullable();

                // Quantities and pricing (kept as VARCHAR by requirement)
                $table->string('quantity')->nullable();
                $table->string('free')->nullable();
                $table->string('sale_rate')->nullable();
                $table->string('mrp_rate')->nullable();
                $table->string('discount')->nullable();
                $table->string('taxable_amount')->nullable();
                $table->string('tax_percentage')->nullable();
                $table->string('tax_amount')->nullable();
                $table->string('final_amount')->nullable();

                // Salesman and logistics
                $table->string('sales_man_name')->nullable()->index();
                $table->string('sales_man_code')->nullable();
                $table->string('case_value')->nullable();
                $table->string('packing')->nullable();
                $table->string('transport')->nullable();
                $table->string('book_to')->nullable();
                $table->string('lr_number')->nullable();
                $table->string('lr_date')->nullable();

                // Location fields
                $table->string('country')->nullable();
                $table->string('state')->nullable()->index();
                $table->string('city')->nullable()->index();
                $table->string('district')->nullable();
                $table->string('pincode')->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_history');
    }
};

