<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ip_locations')) {
            return;
        }

        Schema::create('ip_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('relation_table', 100);
            $table->string('relation_id', 100);
            $table->longText('data')->nullable();
            $table->timestamps();

            $table->unique(['relation_table', 'relation_id'], 'ip_locations_relation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_locations');
    }
};

