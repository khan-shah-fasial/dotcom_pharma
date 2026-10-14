-- Same tax code can belong to more than one Tax ID when the tax type differs.
-- Example: Inclusive G25 keeps its Tax ID. Exclusive G25 is a new Tax ID.
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

DROP PROCEDURE IF EXISTS `tax_masters_drop_index`;

SET @kind_unique := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tax_masters'
    AND INDEX_NAME = 'tax_masters_tax_code_kind_unique'
);
SET @ddl := IF(
  @kind_unique = 0,
  'ALTER TABLE `tax_masters` ADD UNIQUE KEY `tax_masters_tax_code_kind_unique` (`tax_code`, `kind`)',
  'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
