<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('purchase_history')) {
            $this->addIndex('purchase_history', 'ph_order_date_idx', '(`order_date`(20))');
            $this->addIndex('purchase_history', 'ph_invoice_number_idx', '(`invoice_number`(64))');
            $this->addIndex('purchase_history', 'ph_order_number_idx', '(`order_number`(64))');
            $this->addIndex('purchase_history', 'ph_sales_man_code_idx', '(`sales_man_code`(64))');
            $this->addIndex('purchase_history', 'ph_report_filter_idx', '(`order_date`(20), `product_sku`(100), `ac_number`(64))');
            $this->addIndex('purchase_history', 'ph_report_group_idx', '(`ac_number`(64), `order_number`(64), `invoice_series`(32), `invoice_number`(64), `product_sku`(100), `batch_number`(64))');
            $this->addIndex('purchase_history', 'ph_report_sort_idx', '(`order_number`(64), `invoice_number`(64), `product_sku`(100), `batch_number`(64))');
        }

        if (Schema::hasTable('user_details')) {
            $this->addIndex('user_details', 'ud_crm_id_idx', '(`crm_id`(64))');
            $this->addIndex('user_details', 'ud_company_name_idx', '(`company_name`(100))');
            $this->addIndex('user_details', 'ud_user_id_idx', '(`user_id`)');
        }

        if (Schema::hasTable('product_stocks')) {
            $this->addIndex('product_stocks', 'ps_sku_idx', '(`sku`(100))');
        }
    }

    public function down()
    {
        foreach ([
            'purchase_history' => [
                'ph_order_date_idx',
                'ph_invoice_number_idx',
                'ph_order_number_idx',
                'ph_sales_man_code_idx',
                'ph_report_filter_idx',
                'ph_report_group_idx',
                'ph_report_sort_idx',
            ],
            'user_details' => [
                'ud_crm_id_idx',
                'ud_company_name_idx',
                'ud_user_id_idx',
            ],
            'product_stocks' => [
                'ps_sku_idx',
            ],
        ] as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $index) {
                $this->dropIndex($table, $index);
            }
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
