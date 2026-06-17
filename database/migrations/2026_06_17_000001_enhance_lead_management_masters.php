<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $this->createDepartmentMasters();
        $this->createActivityMasters();
        $this->addLeadColumns();
        $this->addLeadActivityColumns();
        $this->seedLeadStatuses();
        $this->seedDepartments();
        $this->seedActivityMasters();
        $this->migrateLeadActivities();
        $this->backfillLatestActivityExpectedValues();
    }

    public function down()
    {
        if (Schema::hasTable('lead_activities')) {
            Schema::table('lead_activities', function (Blueprint $table) {
                foreach (['activity_type_id', 'sub_status_id'] as $column) {
                    if (Schema::hasColumn('lead_activities', $column)) {
                        try {
                            $table->dropForeign([$column]);
                        } catch (Throwable $e) {
                            //
                        }
                    }
                }

                $columns = collect(['activity_type_id', 'sub_status_id', 'expected_value'])
                    ->filter(fn ($column) => Schema::hasColumn('lead_activities', $column))
                    ->all();

                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (Schema::hasColumn('leads', 'department_id')) {
                    try {
                        $table->dropForeign(['department_id']);
                    } catch (Throwable $e) {
                        //
                    }
                }

                $columns = collect(['designation', 'photo', 'department_id', 'work_profile', 'social_media_ids'])
                    ->filter(fn ($column) => Schema::hasColumn('leads', $column))
                    ->all();

                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::dropIfExists('lead_activity_sub_statuses');
        Schema::dropIfExists('lead_activity_types');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('department_categories');
    }

    protected function createDepartmentMasters(): void
    {
        if (!Schema::hasTable('department_categories')) {
            Schema::create('department_categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->tinyInteger('status')->default(1)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('category_id');
                $table->string('name');
                $table->tinyInteger('status')->default(1)->index();
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('department_categories')->restrictOnDelete();
                $table->index(['category_id', 'status']);
            });
        }
    }

    protected function createActivityMasters(): void
    {
        if (!Schema::hasTable('lead_activity_types')) {
            Schema::create('lead_activity_types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->tinyInteger('status')->default(1)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lead_activity_sub_statuses')) {
            Schema::create('lead_activity_sub_statuses', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->tinyInteger('status')->default(1)->index();
                $table->timestamps();
            });
        }
    }

    protected function addLeadColumns(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        $missingColumns = collect(['designation', 'photo', 'department_id', 'work_profile', 'social_media_ids'])
            ->reject(fn ($column) => Schema::hasColumn('leads', $column));

        if ($missingColumns->isEmpty()) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) use ($missingColumns) {
            if ($missingColumns->contains('designation')) {
                $table->string('designation')->nullable()->after('company_name');
            }

            if ($missingColumns->contains('photo')) {
                $table->unsignedBigInteger('photo')->nullable()->after('designation');
            }

            if ($missingColumns->contains('department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('photo')->index();
                $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            }

            if ($missingColumns->contains('work_profile')) {
                $table->text('work_profile')->nullable()->after('department_id');
            }

            if ($missingColumns->contains('social_media_ids')) {
                $table->json('social_media_ids')->nullable()->after('work_profile');
            }
        });
    }

    protected function addLeadActivityColumns(): void
    {
        if (!Schema::hasTable('lead_activities')) {
            return;
        }

        $missingColumns = collect(['activity_type_id', 'sub_status_id', 'expected_value'])
            ->reject(fn ($column) => Schema::hasColumn('lead_activities', $column));

        if ($missingColumns->isEmpty()) {
            return;
        }

        Schema::table('lead_activities', function (Blueprint $table) use ($missingColumns) {
            if ($missingColumns->contains('activity_type_id')) {
                $table->unsignedBigInteger('activity_type_id')->nullable()->after('activity_type')->index();
                $table->foreign('activity_type_id')->references('id')->on('lead_activity_types')->nullOnDelete();
            }

            if ($missingColumns->contains('sub_status_id')) {
                $table->unsignedBigInteger('sub_status_id')->nullable()->after('activity_sub_status')->index();
                $table->foreign('sub_status_id')->references('id')->on('lead_activity_sub_statuses')->nullOnDelete();
            }

            if ($missingColumns->contains('expected_value')) {
                $table->decimal('expected_value', 15, 2)->nullable()->after('next_followup');
            }

        });
    }

    protected function seedLeadStatuses(): void
    {
        if (!Schema::hasTable('lead_statuses')) {
            return;
        }

        DB::table('lead_statuses')->updateOrInsert(
            ['name' => 'New'],
            ['color' => '#3498db', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('lead_statuses')->updateOrInsert(
            ['name' => 'Follow-up'],
            ['color' => '#9b59b6', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()]
        );

        $newId = DB::table('lead_statuses')->where('name', 'New')->value('id');
        $followUpId = DB::table('lead_statuses')->where('name', 'Follow-up')->value('id');

        if ($newId && $followUpId && Schema::hasTable('leads')) {
            DB::table('leads')
                ->whereNotNull('status_id')
                ->whereNotIn('status_id', [$newId, $followUpId])
                ->update(['status_id' => $followUpId]);
        }

        if ($newId && $followUpId) {
            DB::table('lead_statuses')->whereNotIn('id', [$newId, $followUpId])->delete();
        }
    }

    protected function seedDepartments(): void
    {
        foreach ($this->departmentSeedData() as $categoryName => $departments) {
            $categoryId = $this->upsertMaster('department_categories', 'name', $categoryName);

            foreach ($departments as $departmentName) {
                $department = DB::table('departments')
                    ->where('category_id', $categoryId)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($departmentName)])
                    ->first();

                if ($department) {
                    DB::table('departments')->where('id', $department->id)->update([
                        'name' => $departmentName,
                        'status' => 1,
                        'updated_at' => now(),
                    ]);
                    continue;
                }

                DB::table('departments')->insert([
                    'category_id' => $categoryId,
                    'name' => $departmentName,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    protected function seedActivityMasters(): void
    {
        $activityTypes = collect(['Call', 'Email', 'Meeting', 'WhatsApp', 'Note']);

        if (Schema::hasTable('lead_activities') && Schema::hasColumn('lead_activities', 'activity_type')) {
            $activityTypes = $activityTypes
                ->merge(DB::table('lead_activities')->whereNotNull('activity_type')->distinct()->pluck('activity_type')->map(fn ($type) => $this->activityTypeTitle($type)))
                ->filter()
                ->unique(fn ($title) => strtolower(trim($title)));
        }

        foreach ($activityTypes as $title) {
            $this->upsertMaster('lead_activity_types', 'title', $title);
        }

        $subStatuses = collect([
            'Connected',
            'Callback',
            'Busy',
            'Out Of Network',
            'Blocked',
            'Switched Off',
            'Ringing',
            'Not Responding',
            'Sent',
            'Delivered',
            'Opened',
            'Replied',
            'Bounced',
            'Scheduled',
            'Completed',
            'Rescheduled',
            'Cancelled',
            'No Show',
            'Read',
            'Not On Whatsapp',
            'General',
            'Follow Up',
            'Internal',
            'Payment Done',
            'Payment Balance In',
            'Interested',
            'Non-Interested',
            'Order Finalized',
            'Order Cancelled',
            'Proposal Sent',
        ]);

        if (Schema::hasTable('lead_activities') && Schema::hasColumn('lead_activities', 'activity_sub_status')) {
            $subStatuses = $subStatuses
                ->merge(DB::table('lead_activities')->whereNotNull('activity_sub_status')->distinct()->pluck('activity_sub_status')->map(fn ($status) => $this->subStatusTitle($status)))
                ->filter()
                ->unique(fn ($title) => strtolower(trim($title)));
        }

        foreach ($subStatuses as $title) {
            $this->upsertMaster('lead_activity_sub_statuses', 'title', $title);
        }
    }

    protected function migrateLeadActivities(): void
    {
        if (!Schema::hasTable('lead_activities')) {
            return;
        }

        $activityTypeIds = DB::table('lead_activity_types')
            ->pluck('id', 'title')
            ->mapWithKeys(fn ($id, $title) => [strtolower(trim($title)) => $id])
            ->all();

        $subStatusIds = DB::table('lead_activity_sub_statuses')
            ->pluck('id', 'title')
            ->mapWithKeys(fn ($id, $title) => [strtolower(trim($title)) => $id])
            ->all();

        DB::table('lead_activities')
            ->select(['id', 'activity_type', 'activity_sub_status'])
            ->orderBy('id')
            ->chunkById(200, function ($activities) use ($activityTypeIds, $subStatusIds) {
                foreach ($activities as $activity) {
                    $typeTitle = $this->activityTypeTitle($activity->activity_type);
                    $subStatusTitle = $this->subStatusTitle($activity->activity_sub_status);

                    DB::table('lead_activities')->where('id', $activity->id)->update([
                        'activity_type_id' => $activityTypeIds[strtolower(trim($typeTitle))] ?? null,
                        'sub_status_id' => $subStatusTitle ? ($subStatusIds[strtolower(trim($subStatusTitle))] ?? null) : null,
                    ]);
                }
            });
    }

    protected function backfillLatestActivityExpectedValues(): void
    {
        if (
            !Schema::hasTable('leads') ||
            !Schema::hasTable('lead_activities') ||
            !Schema::hasColumn('leads', 'expected_value') ||
            !Schema::hasColumn('lead_activities', 'expected_value')
        ) {
            return;
        }

        DB::table('leads')
            ->select(['id', 'expected_value'])
            ->whereNotNull('expected_value')
            ->orderBy('id')
            ->chunkById(200, function ($leads) {
                foreach ($leads as $lead) {
                    $activityId = DB::table('lead_activities')
                        ->where('lead_id', $lead->id)
                        ->orderByDesc('id')
                        ->value('id');

                    if ($activityId) {
                        DB::table('lead_activities')
                            ->where('id', $activityId)
                            ->whereNull('expected_value')
                            ->update(['expected_value' => $lead->expected_value]);
                    }
                }
            });
    }

    protected function upsertMaster(string $table, string $column, string $value): int
    {
        $existing = DB::table($table)
            ->whereRaw("LOWER(TRIM({$column})) = ?", [strtolower($value)])
            ->first();

        if ($existing) {
            DB::table($table)->where('id', $existing->id)->update([
                $column => $value,
                'status' => 1,
                'updated_at' => now(),
            ]);

            return (int) $existing->id;
        }

        return (int) DB::table($table)->insertGetId([
            $column => $value,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function activityTypeTitle(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'call' => 'Call',
            'email' => 'Email',
            'meeting' => 'Meeting',
            'whatsapp', 'whats_app', 'whats app' => 'WhatsApp',
            default => $value !== '' ? ucwords(str_replace(['_', '-'], ' ', $value)) : 'Note',
        };
    }

    protected function subStatusTitle(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return ucwords(str_replace(['_', '-'], ' ', $value));
    }

    protected function departmentSeedData(): array
    {
        return [
            'Manufacturing & Operations' => [
                'Production (Tablets)',
                'Production (Capsules)',
                'Production (Liquids/Syrups)',
                'Production (Injectables)',
                'Production (Ointments/Creams)',
                'Production (Dry Powder Injections)',
                'Production (Veterinary Products)',
                'Packaging',
                'Manufacturing Planning',
            ],
            'Quality Departments' => [
                'Quality Assurance (QA)',
                'Quality Control (QC)',
                'Microbiology',
                'Stability Studies',
                'Validation',
                'Qualification (Equipment & Utilities)',
                'Compliance & Audits',
                'Data Integrity',
            ],
            'Research & Development' => [
                'Formulation R&D',
                'Analytical R&D',
                'Technology Transfer',
                'Product Development',
                'Clinical Research / Bioequivalence',
            ],
            'Regulatory & Documentation' => [
                'Regulatory Affairs (RA)',
                'Documentation Cell',
                'Pharmacovigilance',
                'Medical Affairs',
                'Intellectual Property (Patents)',
            ],
            'Engineering & Utilities' => [
                'Engineering',
                'Maintenance',
                'Utility Department',
                'HVAC Department',
                'Water System (PW/WFI)',
                'Instrumentation & Automation',
                'Calibration',
            ],
            'Supply Chain & Materials' => [
                'Purchase / Procurement',
                'Vendor Qualification',
                'Warehouse / Stores',
                'Raw Material Store',
                'Packaging Material Store',
                'Finished Goods Store',
                'Supply Chain Management',
                'Logistics & Dispatch',
                'Inventory Control',
            ],
            'Commercial Departments' => [
                'Sales',
                'Marketing',
                'Product Management Team (PMT)',
                'Business Development',
                'Export Department',
                'International Marketing',
                'Tender Business',
            ],
            'Corporate Functions' => [
                'Human Resources (HR)',
                'Training & Development',
                'Administration',
                'Finance & Accounts',
                'Information Technology (IT)',
                'Legal Affairs',
                'Corporate Communications',
            ],
            'Safety & Compliance' => [
                'Environment, Health & Safety (EHS)',
                'Waste Management',
                'Security Department',
                'CSR (Corporate Social Responsibility)',
            ],
            'Specialized GMP Departments' => [
                'Change Control',
                'CAPA Management',
                'Risk Management',
                'Self-Inspection Team',
                'Qualification & Validation Cell',
                'Serialization & Track-and-Trace',
                'GMP Training Cell',
                'Regulatory Intelligence',
                'Artwork & Packaging Development',
            ],
        ];
    }
};
