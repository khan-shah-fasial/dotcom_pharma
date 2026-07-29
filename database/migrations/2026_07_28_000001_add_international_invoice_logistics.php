<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            if (!Schema::hasColumn('countries', 'iso3')) {
                $table->string('iso3', 3)->nullable()->after('code');
            }
            if (!Schema::hasColumn('countries', 'capital')) {
                $table->string('capital')->nullable()->after('name');
            }
        });

        Schema::create('sea_ports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('country_id')->nullable()->index();
            $table->string('country');
            $table->string('iso2', 2)->nullable()->index();
            $table->string('iso3', 3)->nullable();
            $table->string('continent')->nullable();
            $table->string('name')->index();
            $table->string('un_locode', 10)->nullable()->unique();
            $table->string('port_type')->nullable();
            $table->string('terminal_type')->nullable();
            $table->string('classification')->nullable();
            $table->string('water_body')->nullable();
            $table->string('ocean')->nullable();
            $table->string('state_region')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('nearest_airport')->nullable();
            $table->string('customs_port', 20)->nullable();
            $table->string('export_supported', 20)->nullable();
            $table->string('import_supported', 20)->nullable();
            $table->string('container_supported', 20)->nullable();
            $table->string('bulk_cargo_supported', 20)->nullable();
            $table->string('liquid_cargo_supported', 20)->nullable();
            $table->string('ro_ro_supported', 20)->nullable();
            $table->string('cruise_supported', 20)->nullable();
            $table->string('ferry_supported', 20)->nullable();
            $table->string('fishing_supported', 20)->nullable();
            $table->string('ship_repair_supported', 20)->nullable();
            $table->string('authority_name')->nullable();
            $table->text('authority_contact')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('country_id')->nullable()->index();
            $table->string('country');
            $table->string('iso2', 2)->nullable()->index();
            $table->string('iso3', 3)->nullable();
            $table->string('iata', 3)->nullable()->unique();
            $table->string('icao', 4)->nullable()->unique();
            $table->string('name')->index();
            $table->string('city')->nullable();
            $table->string('terminal_type')->nullable();
            $table->string('cargo_airport', 20)->nullable();
            $table->string('customs_airport', 20)->nullable();
            $table->string('cold_chain_facility', 20)->nullable();
            $table->string('authority_name')->nullable();
            $table->text('authority_contact')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('reverse_charge')->nullable()->after('payment_status');
            $table->string('loading_location_type', 10)->nullable()->after('transport_delivery_type');
            $table->unsignedBigInteger('loading_sea_port_id')->nullable()->index()->after('loading_location_type');
            $table->unsignedBigInteger('loading_airport_id')->nullable()->index()->after('loading_sea_port_id');
            $table->string('discharge_location_type', 10)->nullable()->after('loading_airport_id');
            $table->unsignedBigInteger('discharge_sea_port_id')->nullable()->index()->after('discharge_location_type');
            $table->unsignedBigInteger('discharge_airport_id')->nullable()->index()->after('discharge_sea_port_id');
            $table->string('final_destination')->nullable()->after('discharge_airport_id');
            $table->string('carrier_tax_number')->nullable()->after('final_destination');
            $table->decimal('net_weight_kg', 14, 6)->nullable()->after('weight_kg');
            $table->decimal('gross_weight_kg', 14, 6)->nullable()->after('net_weight_kg');
            $table->decimal('total_volume_cbm', 14, 6)->nullable()->after('gross_weight_kg');
        });

        $india = DB::table('countries')->where('code', 'IN')->first();
        if ($india) {
            DB::table('countries')->where('id', $india->id)->update(['iso3' => 'IND']);
        }

        $this->seedSeaPorts($india?->id);
        $this->seedAirports($india?->id);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'loading_sea_port_id',
                'loading_airport_id',
                'discharge_sea_port_id',
                'discharge_airport_id',
            ] as $column) {
                $table->dropIndex([$column]);
            }

            $table->dropColumn([
                'reverse_charge',
                'loading_location_type',
                'loading_sea_port_id',
                'loading_airport_id',
                'discharge_location_type',
                'discharge_sea_port_id',
                'discharge_airport_id',
                'final_destination',
                'carrier_tax_number',
                'net_weight_kg',
                'gross_weight_kg',
                'total_volume_cbm',
            ]);
        });

        Schema::dropIfExists('airports');
        Schema::dropIfExists('sea_ports');

        Schema::table('countries', function (Blueprint $table) {
            foreach (['capital', 'iso3'] as $column) {
                if (Schema::hasColumn('countries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function seedSeaPorts(?int $indiaId): void
    {
        $headers = [
            'name', 'un_locode', 'terminal_type', 'classification', 'water_body',
            'state_region', 'latitude', 'longitude', 'nearest_airport', 'container_supported',
            'bulk_cargo_supported', 'liquid_cargo_supported', 'ro_ro_supported',
            'cruise_supported', 'ferry_supported', 'fishing_supported',
            'ship_repair_supported', 'authority_name',
        ];

        $rows = [
            ['Deendayal Port (Kandla)', 'INIXY', 'Multi-purpose', 'Major', 'Arabian Sea', 'Gujarat', 23.033, 70.217, 'Bhuj Airport', 'Yes', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'Yes', null, 'Deendayal Port Authority'],
            ['Jawaharlal Nehru Port (Nhava Sheva)', 'INNSA', 'Container', 'Major', 'Arabian Sea', 'Maharashtra', 18.949, 72.949, 'Mumbai Airport', 'Yes', 'Yes', 'Limited', 'Yes', 'No', 'Yes', 'No', null, 'Jawaharlal Nehru Port Authority'],
            ['Mumbai Port', 'INBOM', 'Multi-purpose', 'Major', 'Arabian Sea', 'Maharashtra', 18.950, 72.840, 'Mumbai Airport', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', null, 'Mumbai Port Authority'],
            ['Mormugao Port', 'INMRM', 'Bulk', 'Major', 'Arabian Sea', 'Goa', 15.414, 73.800, 'Goa Airport', 'Limited', 'Yes', 'Yes', 'No', 'Yes', 'Yes', 'Yes', null, 'Mormugao Port Authority'],
            ['New Mangalore Port', 'INNML', 'Multi-purpose', 'Major', 'Arabian Sea', 'Karnataka', 12.924, 74.806, 'Mangalore Airport', 'Yes', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'Yes', null, 'New Mangalore Port Authority'],
            ['Cochin Port', 'INCOK', 'Container & Cruise', 'Major', 'Arabian Sea', 'Kerala', 9.966, 76.267, 'Kochi Airport', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', null, 'Cochin Port Authority'],
            ['Chennai Port', 'INMAA', 'Container', 'Major', 'Bay of Bengal', 'Tamil Nadu', 13.082, 80.289, 'Chennai Airport', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'No', 'Yes', null, 'Chennai Port Authority'],
            ['Kamarajar Port (Ennore)', 'INENR', 'Container & Bulk', 'Major', 'Bay of Bengal', 'Tamil Nadu', 13.250, 80.360, 'Chennai Airport', 'Yes', 'Yes', 'Yes', 'Yes', 'No', 'No', 'Yes', null, 'Kamarajar Port Limited'],
            ['V.O. Chidambaranar Port (Tuticorin)', 'INTUT', 'Container', 'Major', 'Gulf of Mannar', 'Tamil Nadu', 8.764, 78.173, 'Tuticorin Airport', 'Yes', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'Yes', null, 'V.O. Chidambaranar Port Authority'],
            ['Visakhapatnam Port', 'INVTZ', 'Container & Bulk', 'Major', 'Bay of Bengal', 'Andhra Pradesh', 17.686, 83.287, 'Visakhapatnam Airport', 'Yes', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'Yes', null, 'Visakhapatnam Port Authority'],
        ];

        foreach ($rows as $row) {
            $data = array_combine($headers, $row);
            $data += [
                'country_id' => $indiaId,
                'country' => 'India',
                'iso2' => 'IN',
                'iso3' => 'IND',
                'continent' => 'Asia',
                'port_type' => 'Seaport',
                'ocean' => 'Indian Ocean',
                'customs_port' => 'Yes',
                'export_supported' => 'Yes',
                'import_supported' => 'Yes',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('sea_ports')->insert($data);
        }
    }

    private function seedAirports(?int $indiaId): void
    {
        $rows = [
            ['Indira Gandhi International Airport', 'Delhi', 'DEL', 'VIDP', 'International'],
            ['Chhatrapati Shivaji Maharaj International Airport', 'Mumbai', 'BOM', 'VABB', 'International'],
            ['Kempegowda International Airport', 'Bengaluru', 'BLR', 'VOBL', 'International'],
            ['Rajiv Gandhi International Airport', 'Hyderabad', 'HYD', 'VOHS', 'International'],
            ['Chennai International Airport', 'Chennai', 'MAA', 'VOMM', 'International'],
            ['Netaji Subhas Chandra Bose International Airport', 'Kolkata', 'CCU', 'VECC', 'International'],
            ['Cochin International Airport', 'Kochi', 'COK', 'VOCI', 'International'],
            ['Sardar Vallabhbhai Patel International Airport', 'Ahmedabad', 'AMD', 'VAAH', 'International'],
            ['Pune Airport', 'Pune', 'PNQ', 'VAPO', 'Domestic/International'],
            ['Goa International Airport (Dabolim)', 'Goa', 'GOI', 'VAGO', 'International'],
            ['Manohar International Airport (Mopa)', 'Goa', 'GOX', 'VOGA', 'International'],
            ['Dr. Babasaheb Ambedkar International Airport', 'Nagpur', 'NAG', 'VANP', 'International'],
            ['Jaipur International Airport', 'Jaipur', 'JAI', 'VIJP', 'International'],
            ['Trivandrum International Airport', 'Thiruvananthapuram', 'TRV', 'VOTV', 'International'],
        ];

        foreach ($rows as [$name, $city, $iata, $icao, $type]) {
            DB::table('airports')->insert([
                'country_id' => $indiaId,
                'country' => 'India',
                'iso2' => 'IN',
                'iso3' => 'IND',
                'iata' => $iata,
                'icao' => $icao,
                'name' => $name,
                'city' => $city,
                'terminal_type' => $type,
                'cargo_airport' => 'Yes',
                'customs_airport' => 'Yes',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
