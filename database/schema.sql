-- Lectura Database Schema
-- MySQL/MariaDB, utf8mb4_unicode_ci
-- Run this on your database to create all tables

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Users table
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','lecturer','assistant','student') NOT NULL DEFAULT 'student',
    `lang` ENUM('ar','en') NOT NULL DEFAULT 'ar',
    `theme` ENUM('light','dark','auto') NOT NULL DEFAULT 'light',
    `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `email_notify` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `last_seen_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Application settings (key/value)
DROP TABLE IF EXISTS `app_settings`;
CREATE TABLE `app_settings` (
    `setting_key` VARCHAR(64) NOT NULL,
    `setting_value` TEXT NULL DEFAULT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`),
    CONSTRAINT `fk_app_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invites table
DROP TABLE IF EXISTS `invites`;
CREATE TABLE `invites` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` CHAR(64) NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `role` ENUM('student','lecturer') NOT NULL DEFAULT 'student',
    `expires_at` DATETIME NOT NULL,
    `used_by` INT UNSIGNED NULL DEFAULT NULL,
    `used_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_invites_token` (`token`),
    KEY `idx_invites_created_by` (`created_by`),
    KEY `idx_invites_used_by` (`used_by`),
    KEY `idx_invites_expires` (`expires_at`),
    CONSTRAINT `fk_invites_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_invites_used_by` FOREIGN KEY (`used_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts table (for rate limiting)
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(150) NOT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_login_attempts_email_ip` (`email`, `ip`),
    KEY `idx_login_attempts_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Terms table
DROP TABLE IF EXISTS `terms`;
CREATE TABLE `terms` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `is_current` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_terms_current` (`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses table
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `term_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(30) NULL DEFAULT NULL,
    `instructor` VARCHAR(100) NULL DEFAULT NULL,
    `color` CHAR(7) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_courses_term` (`term_id`),
    CONSTRAINT `fk_courses_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course assistants (many-to-many)
DROP TABLE IF EXISTS `course_assistants`;
CREATE TABLE `course_assistants` (
    `user_id` INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `course_id`),
    KEY `idx_course_assistants_course` (`course_id`),
    CONSTRAINT `fk_course_assistants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_course_assistants_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course lecturers (many-to-many; controls lecturer upload/edit scope)
DROP TABLE IF EXISTS `course_lecturers`;
CREATE TABLE `course_lecturers` (
    `user_id` INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `course_id`),
    KEY `idx_course_lecturers_course` (`course_id`),
    CONSTRAINT `fk_course_lecturers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_course_lecturers_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items table (lectures, assignments, summaries, exams, other)
DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_id` INT UNSIGNED NOT NULL,
    `type` ENUM('lecture','assignment','summary','exam','other') NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `due_at` DATETIME NULL DEFAULT NULL,
    `uploaded_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_items_course` (`course_id`),
    KEY `idx_items_type` (`type`),
    KEY `idx_items_created` (`created_at`),
    KEY `idx_items_due` (`due_at`),
    CONSTRAINT `fk_items_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_items_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Files table
DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name` VARCHAR(255) NOT NULL,
    `mime` VARCHAR(100) NOT NULL,
    `size` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_files_item` (`item_id`),
    CONSTRAINT `fk_files_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements table
DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL,
    `body` TEXT NOT NULL,
    `is_pinned` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_announcements_pinned` (`is_pinned`),
    KEY `idx_announcements_created` (`created_at`),
    CONSTRAINT `fk_announcements_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification log table
DROP TABLE IF EXISTS `notification_log`;
CREATE TABLE `notification_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT UNSIGNED NULL DEFAULT NULL,
    `announcement_id` INT UNSIGNED NULL DEFAULT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `channel` ENUM('email') NOT NULL,
    `status` ENUM('sent','failed') NOT NULL,
    `error` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notification_log_item` (`item_id`),
    KEY `idx_notification_log_announcement` (`announcement_id`),
    KEY `idx_notification_log_user` (`user_id`),
    KEY `idx_notification_log_created` (`created_at`),
    CONSTRAINT `fk_notification_log_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_notification_log_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_notification_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Global content version for live (no-reload) updates
DROP TABLE IF EXISTS `content_version`;
CREATE TABLE `content_version` (
    `id` TINYINT UNSIGNED NOT NULL,
    `version` BIGINT UNSIGNED NOT NULL DEFAULT 1,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `content_version` (`id`, `version`) VALUES (1, 1);

SET FOREIGN_KEY_CHECKS = 1;
