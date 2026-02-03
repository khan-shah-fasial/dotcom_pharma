<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('form_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('enquiry|suggestion');
            $table->string('form_code', 50)->unique();
            $table->date('form_date')->nullable();
            $table->string('category', 30)->nullable(); // veterinary | human
            $table->string('domestic_type', 50)->nullable(); // govt_supply | exports | third_party | loan_licence

            // Product meta
            $table->integer('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();

            $table->string('product_name')->nullable();
            $table->string('drug_role')->nullable();
            $table->text('product_categories')->nullable(); // comma separated or JSON
            $table->string('product_group')->nullable();
            $table->string('brand_name')->nullable();
            $table->text('composition_text')->nullable();
            $table->text('composition_files')->nullable(); // upload ids
            $table->integer('pack_size')->nullable();
            $table->integer('quantity')->nullable();

            // Government supply
            $table->string('gov_tender_no')->nullable();
            $table->foreignId('gov_state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->string('gov_department')->nullable();
            $table->date('gov_start_date')->nullable();
            $table->date('gov_end_date')->nullable();
            $table->text('gov_tender_files')->nullable();
            $table->text('gov_required_docs')->nullable();
            $table->text('gov_authorisation_files')->nullable();

            // Exports
            $table->unsignedInteger('export_country_id')->nullable();
            $table->foreign('export_country_id')
                ->references('id')
                ->on('countries')
                ->nullOnDelete();

            $table->text('export_iec_files')->nullable();
            $table->text('export_design_files')->nullable();
            $table->text('export_required_docs')->nullable();
            $table->text('export_authorisation_files')->nullable();

            // Third Party Manufacturing
            $table->string('tp_brand_name')->nullable();
            $table->text('tp_trademark_files')->nullable();
            $table->text('tp_undertaking_files')->nullable();
            $table->text('tp_drug_approval_files')->nullable();
            $table->text('tp_design_files')->nullable();

            // Loan Licence Manufacturing
            $table->string('loan_brand_name')->nullable();
            $table->text('loan_trademark_files')->nullable();
            $table->text('loan_undertaking_files')->nullable();
            $table->text('loan_drug_approval_files')->nullable();
            $table->text('loan_design_files')->nullable();

            // Common uploads
            $table->text('common_product_photos')->nullable();
            $table->text('common_product_list_files')->nullable();
            $table->text('common_drug_licence_files')->nullable();
            $table->text('common_gst_files')->nullable();
            $table->text('common_aadhar_files')->nullable();
            $table->text('special_instruction')->nullable();

            // Company details
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_post')->nullable();
            $table->string('company_district')->nullable();
            $table->foreignId('company_state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->string('company_pincode', 20)->nullable();
            $table->unsignedInteger('company_country_id')->nullable();
            $table->foreign('company_country_id')
                ->references('id')
                ->on('countries')
                ->nullOnDelete();

            $table->string('contact_person')->nullable();
            $table->string('designation')->nullable();
            $table->string('mobile_country_code', 10)->nullable();
            $table->string('mobile_number', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('visiting_card_files')->nullable();

            $table->index(['type', 'category', 'domestic_type']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_enquiries');
    }
};
