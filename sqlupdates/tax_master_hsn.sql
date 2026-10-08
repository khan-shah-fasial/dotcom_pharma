-- Tax Master HSN / HS / Applied On columns from Tax Master With HSN-18-09-2026.
-- Run this yourself. Do not run php artisan migrate.
-- This file does not alter taxes, product_taxes, products, carts, or orders.

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

CALL `tax_masters_add_column`('hsn_code', 'varchar(50) DEFAULT NULL');
CALL `tax_masters_add_column`('hs_code', 'varchar(50) DEFAULT NULL');
CALL `tax_masters_add_column`('applied_on_category', 'varchar(255) DEFAULT NULL');
CALL `tax_masters_add_column`('applied_on_sku', 'varchar(255) DEFAULT NULL');
CALL `tax_masters_add_column`('applied_on_product', 'varchar(255) DEFAULT NULL');
CALL `tax_masters_add_column`('applied_on_variant', 'varchar(255) DEFAULT NULL');

DROP PROCEDURE IF EXISTS `tax_masters_add_column`;
