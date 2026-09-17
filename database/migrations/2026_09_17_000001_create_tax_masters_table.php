<?php

use App\Models\TaxMaster;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tax_masters')) {
            return;
        }

        Schema::create('tax_masters', function (Blueprint $table) {
            TaxMaster::schemaBlueprint($table);
        });

        TaxMaster::seedSamples();
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_masters');
    }
};
