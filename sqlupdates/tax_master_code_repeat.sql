-- The same tax code can belong to more than one Tax ID, including the same tax type.
-- Each HSN keeps its own Tax ID. Example: G5 + 3004 and G5 + 30049099 are two rows.
-- Run this yourself. Do not run php artisan migrate.
-- This file does not alter taxes, product_taxes, products, carts, or orders.

DROP PROCEDURE IF EXISTS `tax_masters_drop_index`;
DELIMITER $$
CREATE PROCEDURE `tax_masters_drop_index`(IN idx_name VARCHAR(64))
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tax_masters'
      AND INDEX_NAME = idx_name
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `tax_masters` DROP INDEX `', idx_name, '`');
    PREPARE stmt FROM @ddl;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL `tax_masters_drop_index`('tax_masters_tax_code_unique');
CALL `tax_masters_drop_index`('tax_masters_tax_code_kind_unique');

DROP PROCEDURE IF EXISTS `tax_masters_drop_index`;
