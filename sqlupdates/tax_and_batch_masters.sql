-- Tax Master + Batch / Lot Master
-- Run this file yourself. Do not run php artisan migrate for these modules.
-- This file does not alter taxes, product_taxes, product_batches, product_stocks,
-- carts, orders, or Vat & TAX.

-- 1) tax_masters (no-op if the table already exists on live)
CREATE TABLE IF NOT EXISTS `tax_masters` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind` varchar(20) NOT NULL,
  `tax_code` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `purchase_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `purchase_cgst` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `purchase_sgst` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `purchase_igst` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sale_same_as_purchase` tinyint(1) NOT NULL DEFAULT 1,
  `sale_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sale_cgst` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sale_sgst` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sale_igst` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_masters_tax_code_unique` (`tax_code`),
  KEY `tax_masters_kind_index` (`kind`),
  KEY `tax_masters_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Upsert sample GST codes. Does not change product_taxes rates.
INSERT INTO `tax_masters` (
  `kind`, `tax_code`, `description`,
  `purchase_tax`, `purchase_cgst`, `purchase_sgst`, `purchase_igst`,
  `sale_same_as_purchase`,
  `sale_tax`, `sale_cgst`, `sale_sgst`, `sale_igst`,
  `status`, `created_at`, `updated_at`
) VALUES
  ('taxable', 'G5', NULL, 5.0000, 2.5000, 2.5000, 0.0000, 1, 5.0000, 2.5000, 2.5000, 0.0000, 1, NOW(), NOW()),
  ('taxable', 'G18', NULL, 18.0000, 9.0000, 9.0000, 0.0000, 1, 18.0000, 9.0000, 9.0000, 0.0000, 1, NOW(), NOW()),
  ('taxable', 'G28', NULL, 28.0000, 0.0000, 0.0000, 28.0000, 1, 28.0000, 0.0000, 0.0000, 28.0000, 1, NOW(), NOW()),
  ('exempted', 'EX', 'No Tax', 0.0000, 0.0000, 0.0000, 0.0000, 1, 0.0000, 0.0000, 0.0000, 0.0000, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `kind` = VALUES(`kind`),
  `description` = VALUES(`description`),
  `purchase_tax` = VALUES(`purchase_tax`),
  `purchase_cgst` = VALUES(`purchase_cgst`),
  `purchase_sgst` = VALUES(`purchase_sgst`),
  `purchase_igst` = VALUES(`purchase_igst`),
  `sale_same_as_purchase` = VALUES(`sale_same_as_purchase`),
  `sale_tax` = VALUES(`sale_tax`),
  `sale_cgst` = VALUES(`sale_cgst`),
  `sale_sgst` = VALUES(`sale_sgst`),
  `sale_igst` = VALUES(`sale_igst`),
  `status` = VALUES(`status`),
  `updated_at` = NOW();

-- 3) Optional G12 because purchase history uses it often. Catalog only; not a product rate change.
INSERT INTO `tax_masters` (
  `kind`, `tax_code`, `description`,
  `purchase_tax`, `purchase_cgst`, `purchase_sgst`, `purchase_igst`,
  `sale_same_as_purchase`,
  `sale_tax`, `sale_cgst`, `sale_sgst`, `sale_igst`,
  `status`, `created_at`, `updated_at`
)
SELECT
  'taxable', 'G12', NULL,
  12.0000, 6.0000, 6.0000, 0.0000, 1,
  12.0000, 6.0000, 6.0000, 0.0000,
  1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `tax_masters` WHERE `tax_code` = 'G12'
);

-- 4) batch_masters is a new catalog. It is not product_batches.
CREATE TABLE IF NOT EXISTS `batch_masters` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_stock_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_code` varchar(255) NOT NULL,
  `is_non_batch` tinyint(1) NOT NULL DEFAULT 0,
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `mrp_price` decimal(20,4) DEFAULT NULL,
  `qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `role_price` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_masters_stock_code_unique` (`product_stock_id`, `batch_code`),
  KEY `batch_masters_product_id_index` (`product_id`),
  KEY `batch_masters_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Tax Master permissions. Super Admin already bypasses via Gate::before.
INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'view_all_tax_masters', 'setup_configurations', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'view_all_tax_masters' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'add_tax_master', 'setup_configurations', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'add_tax_master' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'edit_tax_master', 'setup_configurations', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'edit_tax_master' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'delete_tax_master', 'setup_configurations', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'delete_tax_master' AND `guard_name` = 'web');

-- Copy Tax Master perms to every role that already has Vat & TAX.
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, rhp.role_id
FROM `permissions` p
INNER JOIN `permissions` src ON src.name = 'vat_&_tax_setup' AND src.guard_name = 'web'
INNER JOIN `role_has_permissions` rhp ON rhp.permission_id = src.id
WHERE p.name IN ('view_all_tax_masters', 'add_tax_master', 'edit_tax_master', 'delete_tax_master')
  AND p.guard_name = 'web'
  AND NOT EXISTS (
    SELECT 1 FROM `role_has_permissions` x
    WHERE x.permission_id = p.id AND x.role_id = rhp.role_id
  );

-- 6) Batch / Lot Master permissions.
INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'view_all_batch_masters', 'product', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'view_all_batch_masters' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'add_batch_master', 'product', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'add_batch_master' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'edit_batch_master', 'product', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'edit_batch_master' AND `guard_name` = 'web');

INSERT INTO `permissions` (`name`, `section`, `guard_name`, `created_at`, `updated_at`)
SELECT 'delete_batch_master', 'product', 'web', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'delete_batch_master' AND `guard_name` = 'web');

-- Copy Batch Master perms to every role that already has All Products.
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, rhp.role_id
FROM `permissions` p
INNER JOIN `permissions` src ON src.name = 'show_all_products' AND src.guard_name = 'web'
INNER JOIN `role_has_permissions` rhp ON rhp.permission_id = src.id
WHERE p.name IN ('view_all_batch_masters', 'add_batch_master', 'edit_batch_master', 'delete_batch_master')
  AND p.guard_name = 'web'
  AND NOT EXISTS (
    SELECT 1 FROM `role_has_permissions` x
    WHERE x.permission_id = p.id AND x.role_id = rhp.role_id
  );
