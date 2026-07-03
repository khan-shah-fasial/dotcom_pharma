<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('purchase_history')) {
            return;
        }

        $this->addIndex(
            'purchase_history',
            'ph_consolidated_report_idx',
            '(`ac_number`(64), `invoice_date`(20), `invoice_series`(32), `invoice_number`(64), `product_sku`(100), `packing`(64))'
        );

        $this->addIndex(
            'purchase_history',
            'ph_consolidated_sku_idx',
            '(`ac_number`(64), `product_sku`(100), `invoice_date`(20), `invoice_series`(32))'
        );
    }

    public function down()
    {
        if (! Schema::hasTable('purchase_history')) {
            return;
        }

        foreach ([
            'ph_consolidated_report_idx',
            'ph_consolidated_sku_idx',
        ] as $index) {
            $this->dropIndex('purchase_history', $index);
        }
    }

    private function addIndex(string $table, string $index, string $columns): void
    {
        if (! $this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$index}` {$columns}");
        }
    }

    private function dropIndex(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return ! empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]));
    }
};
