-- Tax Master Applied On multi-select.
-- Run this yourself. Do not run php artisan migrate.
-- Widens the Applied On columns so several category, group, SKU, product, or variant
-- labels can be stored. This file does not alter taxes, product_taxes, products, carts, or orders.

DROP PROCEDURE IF EXISTS `tax_masters_widen_applied`;
DELIMITER $$
CREATE PROCEDURE `tax_masters_widen_applied`(IN col_name VARCHAR(64))
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tax_masters'
      AND COLUMN_NAME = col_name
      AND DATA_TYPE <> 'text'
      AND DATA_TYPE <> 'mediumtext'
      AND DATA_TYPE <> 'longtext'
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `tax_masters` MODIFY `', col_name, '` TEXT NULL');
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL `tax_masters_widen_applied`('applied_on_category');
CALL `tax_masters_widen_applied`('applied_on_group');
CALL `tax_masters_widen_applied`('applied_on_sku');
CALL `tax_masters_widen_applied`('applied_on_product');
CALL `tax_masters_widen_applied`('applied_on_variant');

DROP PROCEDURE IF EXISTS `tax_masters_widen_applied`;
