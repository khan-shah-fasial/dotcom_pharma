-- Credit reminder wording: "you used N credit on order X on date, Y is due in D days".
-- Run after credit_notifications.sql on databases that already have the credit notification types.
-- This overwrites the text of these three types; re-apply any custom wording afterwards.
-- Placeholders: [[used]] credit taken on the order, [[amount]] still owed (less after a part payment),
-- [[order_code]], [[used_on]], [[due_date]], [[days_left]], [[days_overdue]].

UPDATE `notification_types`
SET `default_text` = 'You used [[used]] credit on order [[order_code]] on [[used_on]]. [[amount]] is due on [[due_date]] ([[days_left]] days).',
    `updated_at` = NOW()
WHERE `type` = 'credit_due_started';

UPDATE `notification_types`
SET `default_text` = 'Reminder: you used [[used]] credit on order [[order_code]] on [[used_on]]. [[amount]] is due in [[days_left]] days, on [[due_date]]. Please pay.',
    `updated_at` = NOW()
WHERE `type` = 'credit_due_soon';

UPDATE `notification_types`
SET `default_text` = 'You used [[used]] credit on order [[order_code]] on [[used_on]]. [[amount]] was due on [[due_date]] and is [[days_overdue]] days overdue. Please pay it to keep ordering.',
    `updated_at` = NOW()
WHERE `type` = 'credit_overdue';
