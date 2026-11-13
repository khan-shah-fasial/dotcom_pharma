-- Credit notifications and reminders (run on the database; do not use migrate)
-- Safe to run more than once: each notification type and column is added only if it is missing.
-- Texts can be changed later from the admin panel (Notification Types). Keep the [[placeholders]].

INSERT INTO `notification_types` (`user_type`, `type`, `name`, `image`, `default_text`, `status`, `created_at`, `updated_at`)
SELECT * FROM (
    SELECT 'customer' AS user_type, 'credit_account_updated' AS type, 'Credit Account Updated' AS name, NULL AS image,
        'Your credit account was updated. Status: [[status]], limit: [[limit]], days: [[days]].' AS default_text, 1 AS status, NOW() AS created_at, NOW() AS updated_at
    UNION ALL SELECT 'customer', 'credit_topup_received', 'Credit Top Up Received', NULL,
        'Your credit top up of [[amount]] was received.', 1, NOW(), NOW()
    UNION ALL SELECT 'customer', 'credit_payment_received', 'Credit Payment Received', NULL,
        'We received your credit payment of [[amount]].', 1, NOW(), NOW()
    UNION ALL SELECT 'customer', 'credit_due_started', 'Credit Due Date Set', NULL,
        'You used [[used]] credit on order [[order_code]] on [[used_on]]. [[amount]] is due on [[due_date]] ([[days_left]] days).', 1, NOW(), NOW()
    UNION ALL SELECT 'customer', 'credit_due_soon', 'Credit Due Soon', NULL,
        'Reminder: you used [[used]] credit on order [[order_code]] on [[used_on]]. [[amount]] is due in [[days_left]] days, on [[due_date]]. Please pay.', 1, NOW(), NOW()
    UNION ALL SELECT 'customer', 'credit_overdue', 'Credit Overdue', NULL,
        'You used [[used]] credit on order [[order_code]] on [[used_on]]. [[amount]] was due on [[due_date]] and is [[days_overdue]] days overdue. Please pay it to keep ordering.', 1, NOW(), NOW()
    UNION ALL SELECT 'admin', 'credit_online_payment_admin', 'Credit Paid Online', NULL,
        '[[customer]] paid [[amount]] online ([[purpose]]).', 1, NOW(), NOW()
    UNION ALL SELECT 'admin', 'credit_overdue_admin', 'Customer Credit Overdue', NULL,
        '[[customer]] has overdue credit of [[amount]] on order [[order_code]] (due [[due_date]]).', 1, NOW(), NOW()
) AS new_types
WHERE NOT EXISTS (SELECT 1 FROM `notification_types` nt WHERE nt.`type` = new_types.`type`);

-- When the due-soon and overdue reminders were last sent, so the daily job does not repeat them every day.
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'credit_uses' AND COLUMN_NAME = 'due_reminder_sent_at'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `credit_uses` ADD COLUMN `due_reminder_sent_at` timestamp NULL DEFAULT NULL',
  'SELECT ''credit_uses.due_reminder_sent_at already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'credit_uses' AND COLUMN_NAME = 'overdue_reminder_sent_at'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `credit_uses` ADD COLUMN `overdue_reminder_sent_at` timestamp NULL DEFAULT NULL',
  'SELECT ''credit_uses.overdue_reminder_sent_at already exists''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
