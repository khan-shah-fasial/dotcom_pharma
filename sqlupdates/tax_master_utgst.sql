-- Tax Master UT breakup columns from the 02-10-2026 Tax Master sheet.
-- Run this yourself. Do not run php artisan migrate.
-- This file does not change existing rate values, taxes, product_taxes, carts, or orders.

DROP PROCEDURE IF EXISTS `tax_masters_add_column`;
DELIMITER $$
CREATE PROCEDURE `tax_masters_add_column`(IN col_name VARCHAR(64), IN col_def TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tax_masters'
      AND COLUMN_NAME = col_name
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `tax_masters` ADD COLUMN `', col_name, '` ', col_def);
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL `tax_masters_add_column`('purchase_ut_cgst', 'decimal(12,4) NOT NULL DEFAULT 0.0000');
CALL `tax_masters_add_column`('purchase_utgst', 'decimal(12,4) NOT NULL DEFAULT 0.0000');
CALL `tax_masters_add_column`('sale_ut_cgst', 'decimal(12,4) NOT NULL DEFAULT 0.0000');
CALL `tax_masters_add_column`('sale_utgst', 'decimal(12,4) NOT NULL DEFAULT 0.0000');

DROP PROCEDURE IF EXISTS `tax_masters_add_column`;
