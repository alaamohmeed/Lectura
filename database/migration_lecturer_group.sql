-- Lectura additive migration: lecturer role, course assignments, group settings
-- Run once on an existing database. Do NOT import schema.sql over existing data.
-- MariaDB 10.4+ / MySQL 8.x, utf8mb4.

SET NAMES utf8mb4;

ALTER TABLE `users`
    MODIFY `role` ENUM('admin','lecturer','assistant','student') NOT NULL DEFAULT 'student';

ALTER TABLE `invites`
    ADD COLUMN `role` ENUM('student','lecturer') NOT NULL DEFAULT 'student' AFTER `created_by`;

CREATE TABLE IF NOT EXISTS `course_lecturers` (
    `user_id` INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `course_id`),
    KEY `idx_course_lecturers_course` (`course_id`),
    CONSTRAINT `fk_course_lecturers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_course_lecturers_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `app_settings` (
    `setting_key` VARCHAR(64) NOT NULL,
    `setting_value` TEXT NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`),
    CONSTRAINT `fk_app_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_settings` (`setting_key`, `setting_value`)
VALUES ('whatsapp_group_url', NULL)
ON DUPLICATE KEY UPDATE `setting_key` = VALUES(`setting_key`);
