-- Create manifest_group_checkins table for tracking group QR check-ins per day
CREATE TABLE IF NOT EXISTS `manifest_group_checkins` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `upload_id` INT(11) UNSIGNED NOT NULL,
  `group_name` VARCHAR(255) NOT NULL,
  `checked_in_at` DATETIME NOT NULL,
  `direction` ENUM('DEPARTURE', 'RETURN') DEFAULT 'DEPARTURE',
  `notes` TEXT NULL,
  KEY `idx_upload_group` (`upload_id`, `group_name`),
  KEY `idx_checked_in_at` (`checked_in_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
