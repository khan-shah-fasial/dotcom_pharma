-- Folders for the admin Uploaded Files screen.
-- Run this yourself. Do not run php artisan migrate.
-- Files stay in their current storage path. A folder is only a label on the upload row.

CREATE TABLE IF NOT EXISTS `upload_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `upload_folders_parent_id` (`parent_id`),
  KEY `upload_folders_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS `uploads_add_folder_id`;
DELIMITER $$
CREATE PROCEDURE `uploads_add_folder_id`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'uploads'
      AND COLUMN_NAME = 'folder_id'
  ) THEN
    ALTER TABLE `uploads` ADD COLUMN `folder_id` int(11) DEFAULT NULL AFTER `user_id`;
    ALTER TABLE `uploads` ADD KEY `uploads_folder_id` (`folder_id`);
  END IF;
END$$
DELIMITER ;

CALL `uploads_add_folder_id`();
DROP PROCEDURE IF EXISTS `uploads_add_folder_id`;
