<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Legacy installations use MEDIUMTEXT for code; normalize it so the
            // database can enforce duplicate order-number prevention.
            $table->string('code', 191)->nullable()->change();
            $table->string('consignee_copy_status', 30)->nullable()->after('cc_attached_path');
            $table->decimal('weight_grams', 14, 3)->nullable()->after('weight');
            $table->decimal('weight_kg', 14, 6)->nullable()->after('weight_grams');
            $table->decimal('length_cm', 12, 2)->nullable()->after('dimensions');
            $table->decimal('width_cm', 12, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 12, 2)->nullable()->after('width_cm');
            $table->string('freight_type', 30)->nullable()->after('freight_paid');
            $table->date('lr_date')->nullable()->after('lr_number');
            $table->boolean('free_shipping')->default(false)->after('freight_type');
            $table->date('order_date')->nullable()->after('date');
            $table->time('order_time')->nullable()->after('order_date');
            $table->unsignedInteger('sales_executive_id')->nullable()->index()->after('sales_person_id');
            $table->string('sales_man_code')->nullable()->after('sales_executive_id');
            $table->unsignedInteger('packed_by')->nullable()->index()->after('sales_man_code');
            $table->unsignedInteger('checked_by')->nullable()->index()->after('packed_by');
            $table->unsignedInteger('billing_by')->nullable()->index()->after('checked_by');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->after('type');
            $table->string('village')->nullable()->after('address');
            $table->string('district')->nullable()->after('village');
        });

        Schema::create('order_attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->index();
            $table->string('category', 40)->index();
            $table->text('original_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('sales_executive_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('packed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('checked_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('billing_by')->references('id')->on('users')->nullOnDelete();
            $table->unique('code', 'orders_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_code_unique');
            $table->dropForeign(['sales_executive_id']);
            $table->dropForeign(['packed_by']);
            $table->dropForeign(['checked_by']);
            $table->dropForeign(['billing_by']);
        });

        Schema::dropIfExists('order_attachments');

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'village', 'district']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['sales_executive_id']);
            $table->dropIndex(['packed_by']);
            $table->dropIndex(['checked_by']);
            $table->dropIndex(['billing_by']);
            $table->dropColumn([
                'consignee_copy_status',
                'weight_grams',
                'weight_kg',
                'length_cm',
                'width_cm',
                'height_cm',
                'freight_type',
                'lr_date',
                'free_shipping',
                'order_date',
                'order_time',
                'sales_executive_id',
                'sales_man_code',
                'packed_by',
                'checked_by',
                'billing_by',
            ]);
            $table->mediumText('code')->nullable()->change();
        });
    }
};
