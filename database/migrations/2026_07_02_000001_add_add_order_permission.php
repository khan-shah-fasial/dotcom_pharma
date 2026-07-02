<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAddOrderPermission extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $values = [
            'name' => 'add_order',
            'section' => 'sales',
        ];

        if (Schema::hasColumn('permissions', 'guard_name')) {
            $values['guard_name'] = 'web';
        }

        if (Schema::hasColumn('permissions', 'created_at')) {
            $values['created_at'] = now();
            $values['updated_at'] = now();
        }

        DB::table('permissions')->updateOrInsert(['name' => 'add_order'], $values);
    }

    public function down()
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('name', 'add_order')->delete();
        }
    }
}
