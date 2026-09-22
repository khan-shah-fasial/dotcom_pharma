-- Note Type Master (run on prod if table missing; do not use migrate:fresh)
CREATE TABLE IF NOT EXISTS `note_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `note_types_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `note_types` (`name`, `slug`, `status`, `created_at`, `updated_at`)
SELECT 'Refund', 'refund', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `note_types` WHERE `slug` = 'refund');

INSERT INTO `note_types` (`name`, `slug`, `status`, `created_at`, `updated_at`)
SELECT 'Warranty', 'warranty', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `note_types` WHERE `slug` = 'warranty');
