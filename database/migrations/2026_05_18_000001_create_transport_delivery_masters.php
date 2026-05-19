<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transports')) {
            Schema::create('transports', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('status', 20)->default('active')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('booked_to')) {
            Schema::create('booked_to', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transport_id')->index();
                $table->string('name');
                $table->string('status', 20)->default('active')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('local_delivery_partners')) {
            Schema::create('local_delivery_partners', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('status', 20)->default('active')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        $deliveryPartners = [
            'Borzo Wefast',
            'Shadowfax',
            'Delhivery',
            'Ekart Logistics',
            'Dunzo',
            'Shiprocket',
            'Shiprocket Quick',
            'Porter',
            'Blue Dart',
            'DTDC',
            'XpressBees',
            'Ecom Express',
            'Pickrr',
            'ElasticRun',
            'LoadShare',
            'Rivigo',
            'Rapido',
            'Rapido Local',
            'Swiggy Genie',
            'Blinkit',
            'Zepto',
            'Parcel Chief',
            'Delivery Express',
            'RCS Express Courier Service',
            'Courier Service Mumbai',
            'Same Day Delivery',
            'REALTIME COURIER AND CARGO',
            'Airborne International Courier Services Mumbai',
            'Ease Your Life',
            'Air Express - Domestic/International Courier Services (Andheri East)',
            'Sunrise Courier - International Courier Service in Mumbai',
            'International Courier Services in Mumbai: Unique Services',
            'Vpledge - Same Day Delivery Service',
            'Shipyaari HQ',
            'CB Logistics',
        ];

        $existingPartners = DB::table('local_delivery_partners')
            ->whereIn('name', $deliveryPartners)
            ->pluck('name')
            ->all();

        $now = now();
        $rows = collect($deliveryPartners)
            ->diff($existingPartners)
            ->map(function ($name) use ($now) {
                return [
                    'name' => $name,
                    'status' => 'active',
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        if (!empty($rows)) {
            DB::table('local_delivery_partners')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('local_delivery_partners');
        Schema::dropIfExists('booked_to');
        Schema::dropIfExists('transports');
    }
};
