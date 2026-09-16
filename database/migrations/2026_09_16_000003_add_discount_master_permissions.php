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

        $names = [
            'view_all_discount_masters',
            'add_discount_master',
            'edit_discount_master',
            'delete_discount_master',
        ];

        foreach ($names as $name) {
            $values = [
                'name' => $name,
                'section' => 'marketing',
            ];

            if (Schema::hasColumn('permissions', 'guard_name')) {
                $values['guard_name'] = 'web';
            }

            if (Schema::hasColumn('permissions', 'created_at')) {
                $values['created_at'] = now();
                $values['updated_at'] = now();
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                $values
            );
        }

        $sourcePermissionId = DB::table('permissions')
            ->where('name', 'view_all_flash_deals')
            ->value('id');

        if ($sourcePermissionId) {
            foreach ($names as $name) {
                $newPermissionId = DB::table('permissions')
                    ->where('name', $name)
                    ->value('id');

                if ($newPermissionId) {
                    $this->copyRoleAssignments((int) $sourcePermissionId, (int) $newPermissionId);
                    $this->copyDirectAssignments((int) $sourcePermissionId, (int) $newPermissionId);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', [
                'view_all_discount_masters',
                'add_discount_master',
                'edit_discount_master',
                'delete_discount_master',
            ])->delete();
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
