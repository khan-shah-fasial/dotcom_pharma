<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $values = [
            'name' => 'view_all_companies',
            'section' => 'customer',
        ];

        if (Schema::hasColumn('permissions', 'guard_name')) {
            $values['guard_name'] = 'web';
        }

        if (Schema::hasColumn('permissions', 'created_at')) {
            $values['created_at'] = now();
            $values['updated_at'] = now();
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => 'view_all_companies'],
            $values
        );

        $newPermissionId = DB::table('permissions')
            ->where('name', 'view_all_companies')
            ->value('id');
        $customerPermissionId = DB::table('permissions')
            ->where('name', 'view_all_customers')
            ->value('id');

        if ($newPermissionId && $customerPermissionId) {
            $this->copyRoleAssignments($customerPermissionId, $newPermissionId);
            $this->copyDirectAssignments($customerPermissionId, $newPermissionId);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('name', 'view_all_companies')->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function copyRoleAssignments(int $sourcePermissionId, int $newPermissionId): void
    {
        if (!Schema::hasTable('role_has_permissions')) {
            return;
        }

        $rows = DB::table('role_has_permissions')
            ->where('permission_id', $sourcePermissionId)
            ->get(['role_id'])
            ->map(fn ($row) => [
                'permission_id' => $newPermissionId,
                'role_id' => $row->role_id,
            ])
            ->all();

        if (!empty($rows)) {
            DB::table('role_has_permissions')->insertOrIgnore($rows);
        }
    }

    private function copyDirectAssignments(int $sourcePermissionId, int $newPermissionId): void
    {
        if (!Schema::hasTable('model_has_permissions')) {
            return;
        }

        $rows = DB::table('model_has_permissions')
            ->where('permission_id', $sourcePermissionId)
            ->get(['model_type', 'model_id'])
            ->map(fn ($row) => [
                'permission_id' => $newPermissionId,
                'model_type' => $row->model_type,
                'model_id' => $row->model_id,
            ])
            ->all();

        if (!empty($rows)) {
            DB::table('model_has_permissions')->insertOrIgnore($rows);
        }
    }
};
