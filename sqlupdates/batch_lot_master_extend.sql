-- Batch / Lot Master extension.
-- Run this yourself. Do not run php artisan migrate.
-- This file does not change products, product_stocks, product_batches,
-- purchase_history, tax_masters, discount_masters, carts, or orders.
-- New columns are optional. Existing batch_masters rows stay as they are.

CREATE TABLE IF NOT EXISTS `batch_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_master_id` bigint(20) UNSIGNED NOT NULL,
  `new_batch_master_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `qty` decimal(15,3) DEFAULT NULL,
  `reason` varchar(1000) DEFAULT NULL,
  `biowaste_upload_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `batch_adjustments_batch_master_id_index` (`batch_master_id`),
  KEY `batch_adjustments_new_batch_master_id_index` (`new_batch_master_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add columns only when missing. Safe to run more than once.
DROP PROCEDURE IF EXISTS `batch_masters_add_column`;
DELIMITER $$
CREATE PROCEDURE `batch_masters_add_column`(IN col_name VARCHAR(64), IN col_def TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'batch_masters'
      AND COLUMN_NAME = col_name
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `batch_masters` ADD COLUMN `', col_name, '` ', col_def);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL batch_masters_add_column('drug_name', 'varchar(255) NULL');
CALL batch_masters_add_column('marketed_by_id', 'bigint(20) UNSIGNED NULL');
CALL batch_masters_add_column('marketed_by_name', 'varchar(255) NULL');
CALL batch_masters_add_column('import_by_ids', 'text NULL');
CALL batch_masters_add_column('import_by_names', 'text NULL');
CALL batch_masters_add_column('manufactured_by_ids', 'text NULL');
CALL batch_masters_add_column('manufactured_by_names', 'text NULL');
CALL batch_masters_add_column('company_id', 'bigint(20) UNSIGNED NULL');
CALL batch_masters_add_column('free_qty', 'decimal(15,3) NULL');
CALL batch_masters_add_column('purchase_rate', 'decimal(20,4) NULL');
CALL batch_masters_add_column('tax_code', 'varchar(20) NULL');
CALL batch_masters_add_column('tax_percent', 'decimal(12,4) NULL');
CALL batch_masters_add_column('scheme', 'decimal(15,3) NULL');
CALL batch_masters_add_column('batch_discount_percent', 'decimal(12,4) NULL');
CALL batch_masters_add_column('product_discount_percent', 'decimal(12,4) NULL');
CALL batch_masters_add_column('scheme_discount_percent', 'decimal(12,4) NULL');
CALL batch_masters_add_column('coa', 'bigint(20) UNSIGNED NULL');
CALL batch_masters_add_column('price_pts', 'decimal(20,4) NULL');
CALL batch_masters_add_column('price_ptr', 'decimal(20,4) NULL');
CALL batch_masters_add_column('price_ptd', 'decimal(20,4) NULL');
CALL batch_masters_add_column('price_gov', 'decimal(20,4) NULL');
CALL batch_masters_add_column('price_expo', 'decimal(20,4) NULL');
CALL batch_masters_add_column('price_customer', 'decimal(20,4) NULL');
CALL batch_masters_add_column('source_purchase_history_id', 'bigint(20) UNSIGNED NULL');
CALL batch_masters_add_column('source_product_batch_id', 'bigint(20) UNSIGNED NULL');
CALL batch_masters_add_column('copied_at', 'timestamp NULL DEFAULT NULL');
CALL batch_masters_add_column('converted_from_id', 'bigint(20) UNSIGNED NULL');
CALL batch_masters_add_column('removed_at', 'timestamp NULL DEFAULT NULL');

DROP PROCEDURE IF EXISTS `batch_masters_add_column`;
