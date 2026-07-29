<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sea_ports', function (Blueprint $table) {
            $table->string('port_id', 50)->nullable()->after('id');
        });

        Schema::table('airports', function (Blueprint $table) {
            $table->string('port_id', 50)->nullable()->after('id');
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

        DB::table('sea_ports')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($ports) {
                foreach ($ports as $port) {
                    DB::table('sea_ports')
                        ->where('id', $port->id)
                        ->update(['port_id' => 'SEA-' . str_pad((string) $port->id, 6, '0', STR_PAD_LEFT)]);
                }
            });

        DB::table('airports')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($airports) {
                foreach ($airports as $airport) {
                    DB::table('airports')
                        ->where('id', $airport->id)
                        ->update(['port_id' => 'AIR-' . str_pad((string) $airport->id, 6, '0', STR_PAD_LEFT)]);
                }
            });

        Schema::table('sea_ports', function (Blueprint $table) {
            $table->unique('port_id');
        });

        Schema::table('airports', function (Blueprint $table) {
            $table->unique('port_id');
        });
    }

    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropUnique(['port_id']);
            $table->dropColumn([
                'port_id',
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

        Schema::table('sea_ports', function (Blueprint $table) {
            $table->dropUnique(['port_id']);
            $table->dropColumn('port_id');
        });
    }
};
