<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('lead_sources')) {
            Schema::create('lead_sources', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 100);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lead_statuses')) {
            Schema::create('lead_statuses', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 100);
                $table->string('color', 20)->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('lead_no', 50)->nullable()->unique();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 50)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedBigInteger('status_id')->nullable();
                $table->unsignedInteger('assigned_to')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->decimal('expected_value', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('source_id')->references('id')->on('lead_sources')->nullOnDelete();
                $table->foreign('status_id')->references('id')->on('lead_statuses')->nullOnDelete();
                $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['source_id', 'status_id', 'assigned_to', 'created_by']);
            });
        }

        if (!Schema::hasTable('lead_activities')) {
            Schema::create('lead_activities', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('lead_id');
                $table->enum('activity_type', ['call', 'email', 'meeting', 'whatsapp', 'note']);
                $table->text('description')->nullable();
                $table->dateTime('next_followup')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['activity_type', 'next_followup']);
            });
        }

        DB::table('lead_statuses')->updateOrInsert(['name' => 'New'], ['color' => '#3498db', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('lead_statuses')->updateOrInsert(['name' => 'Contacted'], ['color' => '#f39c12', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('lead_statuses')->updateOrInsert(['name' => 'Follow-up'], ['color' => '#9b59b6', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('lead_statuses')->updateOrInsert(['name' => 'Qualified'], ['color' => '#2ecc71', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('lead_statuses')->updateOrInsert(['name' => 'Proposal Sent'], ['color' => '#1abc9c', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('lead_statuses')->updateOrInsert(['name' => 'Won'], ['color' => '#27ae60', 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('lead_statuses')->updateOrInsert(['name' => 'Lost'], ['color' => '#e74c3c', 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()]);

        foreach ([
            'Website',
            'Google Ads',
            'Facebook',
            'Instagram',
            'WhatsApp',
            'Referral',
            'Direct Call',
            'Email Campaign',
        ] as $source) {
            DB::table('lead_sources')->updateOrInsert(['name' => $source], ['status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->insertPermissions();
    }

    public function down()
    {
        $this->deletePermissions();

        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_statuses');
        Schema::dropIfExists('lead_sources');
    }

    protected function insertPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (['view_leads', 'add_lead', 'edit_lead', 'delete_lead'] as $permission) {
            $values = [
                'name' => $permission,
                'section' => 'lead_management',
            ];

            if (Schema::hasColumn('permissions', 'guard_name')) {
                $values['guard_name'] = 'web';
            }

            if (Schema::hasColumn('permissions', 'created_at')) {
                $values['created_at'] = now();
                $values['updated_at'] = now();
            }

            DB::table('permissions')->updateOrInsert(['name' => $permission], $values);
        }
    }

    protected function deletePermissions(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', ['view_leads', 'add_lead', 'edit_lead', 'delete_lead'])->delete();
        }
    }
};
