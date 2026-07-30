<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('company_name')->index();
            $table->text('full_address');
            $table->string('contact_person')->nullable()->index();
            $table->string('designation')->nullable();
            $table->string('mobile', 30)->nullable()->index();
            $table->string('whatsapp', 30)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('company_type', 100)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('company_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('category_id')->index();
            $table->timestamps();

            $table->unique(['company_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_category');
        Schema::dropIfExists('companies');
    }
};
