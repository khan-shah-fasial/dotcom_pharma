-- Credit uses (run once on the database; do not use migrate)
CREATE TABLE IF NOT EXISTS `credit_uses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `combined_order_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `credit_days` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'reserved',
  `started_on` timestamp NULL DEFAULT NULL,
  `due_on` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_uses_user_status_index` (`user_id`, `status`),
  KEY `credit_uses_order_id_index` (`order_id`),
  KEY `credit_uses_combined_order_id_index` (`combined_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
