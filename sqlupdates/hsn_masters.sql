-- HSN Master.
-- Run this yourself. Do not run php artisan migrate.
-- This file does not alter tax_masters, taxes, product_taxes, products, carts, or orders.
-- After this file, run sqlupdates/hsn_masters_seed.sql to load codes.

CREATE TABLE IF NOT EXISTS `hsn_masters` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hsn_code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `taxable` tinyint(1) DEFAULT NULL,
  `tax_type` varchar(20) DEFAULT NULL,
  `tax_percent` decimal(12,4) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hsn_masters_hsn_code_unique` (`hsn_code`),
  KEY `hsn_masters_tax_type_index` (`tax_type`),
  KEY `hsn_masters_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions. Super Admin already bypasses via Gate::before.
INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'view_all_hsn_masters', 'setup_configurations', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'view_all_hsn_masters' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'add_hsn_master', 'setup_configurations', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'add_hsn_master' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'edit_hsn_master', 'setup_configurations', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'edit_hsn_master' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'delete_hsn_master', 'setup_configurations', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'delete_hsn_master' AND `guard_name` = 'web');

-- Copy HSN Master perms to every role that already has Tax Master view.
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, rhp.role_id
FROM `permissions` p
INNER JOIN `permissions` src ON src.name = 'view_all_tax_masters' AND src.guard_name = 'web'
INNER JOIN `role_has_permissions` rhp ON rhp.permission_id = src.id
WHERE p.name IN ('view_all_hsn_masters', 'add_hsn_master', 'edit_hsn_master', 'delete_hsn_master')
  AND p.guard_name = 'web'
  AND NOT EXISTS (
    SELECT 1 FROM `role_has_permissions` x
    WHERE x.permission_id = p.id AND x.role_id = rhp.role_id
  );
