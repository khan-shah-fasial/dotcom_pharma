<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sea_ports', function (Blueprint $table) {
            $table->string('authority_designation')->nullable()->after('authority_name');
            $table->string('authority_mobile', 30)->nullable()->after('authority_designation');
            $table->string('authority_whatsapp', 30)->nullable()->after('authority_mobile');
            $table->string('authority_email', 191)->nullable()->after('authority_whatsapp');
            $table->string('coordinator_name')->nullable()->after('authority_email');
            $table->string('coordinator_designation')->nullable()->after('coordinator_name');
            $table->string('coordinator_mobile', 30)->nullable()->after('coordinator_designation');
            $table->string('coordinator_whatsapp', 30)->nullable()->after('coordinator_mobile');
            $table->string('coordinator_email', 191)->nullable()->after('coordinator_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('sea_ports', function (Blueprint $table) {
            $table->dropColumn([
                'authority_designation',
                'authority_mobile',
                'authority_whatsapp',
                'authority_email',
                'coordinator_name',
                'coordinator_designation',
                'coordinator_mobile',
                'coordinator_whatsapp',
                'coordinator_email',
            ]);
        });
    }
};
