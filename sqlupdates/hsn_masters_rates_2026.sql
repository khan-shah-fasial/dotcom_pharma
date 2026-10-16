-- Current GST rates for HSN Master.
-- Run this yourself after sqlupdates/hsn_masters.sql and sqlupdates/hsn_masters_seed.sql.
-- Do not run php artisan migrate.
-- Source: Notification 9/2025-Central Tax (Rate) dated 17.09.2025, as compiled to 31.03.2026.
-- The 12% and 28% slabs from the old seed are no longer in force, so those rates are cleared.
-- A heading is filled only where that compilation gives one rate for the whole heading.
-- Mixed headings, such as 3304, are not changed.

UPDATE `hsn_masters`
SET `taxable` = NULL,
    `tax_type` = NULL,
    `tax_percent` = NULL,
    `updated_at` = NOW()
WHERE `tax_percent` IN (12, 28)
   OR `hsn_code` IN ('0101', '3304');

UPDATE `hsn_masters`
SET `taxable` = 1,
    `tax_type` = 'taxable',
    `tax_percent` = 5.0000,
    `updated_at` = NOW()
WHERE `hsn_code` LIKE '3003%'
   OR `hsn_code` LIKE '3004%'
   OR `hsn_code` LIKE '3005%'
   OR `hsn_code` LIKE '9018%'
   OR `hsn_code` LIKE '9019%'
   OR `hsn_code` LIKE '010121%'
   OR `hsn_code` LIKE '010129%';

UPDATE `hsn_masters`
SET `taxable` = 1,
    `tax_type` = 'taxable',
    `tax_percent` = 18.0000,
    `updated_at` = NOW()
WHERE `hsn_code` LIKE '3303%';
