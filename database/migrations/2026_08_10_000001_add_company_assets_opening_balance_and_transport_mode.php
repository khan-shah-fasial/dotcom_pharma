<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('logo')->nullable()->after('company_type');
            $table->unsignedBigInteger('stamp')->nullable()->after('logo');
            $table->unsignedBigInteger('sign')->nullable()->after('stamp');
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->decimal('opening_balance', 20, 3)->default(0)->after('crm_id');
        });

        Schema::table('transports', function (Blueprint $table) {
            $table->string('mode', 20)->default('surface')->index()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropIndex(['mode']);
            $table->dropColumn('mode');
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['logo', 'stamp', 'sign']);
        });
    }
};
