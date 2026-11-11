-- Prepaid credit top-up (run on the database; do not use migrate)
-- Top-up is money already received. It is spent first at checkout and is not billed again.
-- Only the admin credit limit used on an order becomes due, and that clock starts on dispatch.
-- Safe to run more than once: each column is added only if it is missing.

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'credit_prepaid'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `users` ADD COLUMN `credit_prepaid` decimal(20,2) NOT NULL DEFAULT 0.00',
  'SELECT ''users.credit_prepaid already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'credit_uses' AND COLUMN_NAME = 'kind'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `credit_uses` ADD COLUMN `kind` varchar(20) NOT NULL DEFAULT ''facility''',
  'SELECT ''credit_uses.kind already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
