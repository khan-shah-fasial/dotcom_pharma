<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createClassificationsTable();
        $this->createContactsTable();
        $this->seedClassifications();
        $this->insertPermissions();
    }

    public function down(): void
    {
        $this->deletePermissions();

        Schema::dropIfExists('directory_contacts');
        Schema::dropIfExists('contact_classifications');
    }

    protected function createClassificationsTable(): void
    {
        if (Schema::hasTable('contact_classifications')) {
            return;
        }

        Schema::create('contact_classifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('kind', 50)->index();
            $table->string('name');
            $table->tinyInteger('status')->default(1)->index();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('contact_classifications')->restrictOnDelete();
            $table->index(['kind', 'parent_id', 'status']);
        });
    }

    protected function createContactsTable(): void
    {
        if (Schema::hasTable('directory_contacts')) {
            return;
        }

        Schema::create('directory_contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('contact_no', 50)->nullable()->unique();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('designation')->nullable();
            $table->unsignedBigInteger('photo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('alternate_mobile_number', 50)->nullable();
            $table->string('whatsapp_number', 50)->nullable();
            $table->json('social_media_ids')->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('country_id')->nullable()->index();
            $table->unsignedInteger('state_id')->nullable()->index();
            $table->string('district')->nullable();
            $table->string('post', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();

            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('subcategory_id')->nullable()->index();
            $table->unsignedBigInteger('type_id')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->unsignedBigInteger('industry_id')->nullable()->index();
            $table->unsignedBigInteger('work_profile_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('purpose_id')->nullable()->index();

            $table->unsignedInteger('created_by')->nullable()->index();
            $table->unsignedInteger('updated_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('contact_classifications')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('contact_classifications')->nullOnDelete();
            $table->foreign('subcategory_id')->references('id')->on('contact_classifications')->nullOnDelete();
            $table->foreign('type_id')->references('id')->on('contact_classifications')->nullOnDelete();
            $table->foreign('subject_id')->references('id')->on('contact_classifications')->nullOnDelete();
            $table->foreign('industry_id')->references('id')->on('contact_classifications')->nullOnDelete();
            $table->foreign('work_profile_id')->references('id')->on('contact_classifications')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('contact_classifications')->nullOnDelete();
            $table->foreign('purpose_id')->references('id')->on('contact_classifications')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    protected function seedClassifications(): void
    {
        if (!Schema::hasTable('contact_classifications')) {
            return;
        }

        $groupId = $this->upsertClassification('group', 'Repair And Maintenance', null);
        $industryId = $this->upsertClassification('industry', 'Plumbing', null);
        $this->upsertClassification('work_profile', 'Plumber', $industryId);

        unset($groupId);
    }

    protected function upsertClassification(string $kind, string $name, ?int $parentId): int
    {
        $query = DB::table('contact_classifications')
            ->where('kind', $kind)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)]);

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        $existing = $query->first();

        if ($existing) {
            DB::table('contact_classifications')->where('id', $existing->id)->update([
                'name' => $name,
                'status' => 1,
                'updated_at' => now(),
            ]);

            return (int) $existing->id;
        }

        return (int) DB::table('contact_classifications')->insertGetId([
            'parent_id' => $parentId,
            'kind' => $kind,
            'name' => $name,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (['view_contact_directory', 'add_contact_directory', 'edit_contact_directory', 'delete_contact_directory'] as $permission) {
            $values = [
                'name' => $permission,
                'section' => 'contact_management',
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
            DB::table('permissions')->whereIn('name', [
                'view_contact_directory',
                'add_contact_directory',
                'edit_contact_directory',
                'delete_contact_directory',
            ])->delete();
        }
    }
};
