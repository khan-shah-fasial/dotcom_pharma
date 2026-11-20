-- SANDBOX / TEST DATABASE ONLY. Do not run on production.
-- One-off data fix: a sandbox top-up of 10 was added to user 3596's billable credit_limit
-- (5000 became 5010) before the prepaid balance existed. This moves that 10 to credit_prepaid.
-- Run credit_prepaid.sql first. Check the user's values before running.
-- The credit_prepaid = 0 guard stops it moving the 10 twice if run again.

UPDATE `users`
SET `credit_limit` = `credit_limit` - 10,
    `credit_prepaid` = `credit_prepaid` + 10
WHERE `id` = 3596
  AND `credit_limit` >= 10
  AND `credit_prepaid` = 0;
