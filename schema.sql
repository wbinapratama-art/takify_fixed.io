SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `task_remind`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `task_remind`;

DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `backup_logs`;
DROP TABLE IF EXISTS `feedbacks`;
DROP TABLE IF EXISTS `reminders`;
DROP TABLE IF EXISTS `user_preferences`;
DROP TABLE IF EXISTS `attachments`;
DROP TABLE IF EXISTS `task_comments`;
DROP TABLE IF EXISTS `subtasks`;
DROP TABLE IF EXISTS `templates`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `category_members`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `workspaces`;

CREATE TABLE `workspaces` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `workspace_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('Admin','Anggota','Pengamat') NOT NULL DEFAULT 'Anggota',
    `status` ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
    `phone` VARCHAR(30) DEFAULT NULL,
    `gender` ENUM('Male','Female','Other') DEFAULT NULL,
    `dob` DATE DEFAULT NULL,
    `shop_name` VARCHAR(100) DEFAULT NULL,
    `last_active_at` DATETIME DEFAULT NULL,
    `last_seen_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_email` (`email`),
    KEY `idx_ws` (`workspace_id`),
    CONSTRAINT `fk_users_ws` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `workspace_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(80) NOT NULL,
    `color` VARCHAR(20) NOT NULL DEFAULT '#5b55e7',
    `description` TEXT DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ws` (`workspace_id`),
    CONSTRAINT `fk_cat_ws` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cat_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `category_members` (
    `category_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `access_level` ENUM('Lihat','Edit','Kelola') NOT NULL DEFAULT 'Lihat',
    PRIMARY KEY (`category_id`,`user_id`),
    CONSTRAINT `fk_cm_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cm_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `workspace_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `due_date` DATETIME DEFAULT NULL,
    `priority` ENUM('Rendah','Sedang','Tinggi') NOT NULL DEFAULT 'Sedang',
    `status` ENUM('Belum dimulai','Sedang dikerjakan','Selesai') NOT NULL DEFAULT 'Belum dimulai',
    `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `is_recurring` TINYINT(1) NOT NULL DEFAULT 0,
    `recurrence_rule` VARCHAR(20) DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `assigned_to` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_ws` (`workspace_id`),
    KEY `idx_status` (`status`),
    KEY `idx_due` (`due_date`),
    CONSTRAINT `fk_task_ws` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_task_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_task_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_task_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `workspace_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category_id` INT UNSIGNED DEFAULT NULL,
    `default_priority` ENUM('Rendah','Sedang','Tinggi') NOT NULL DEFAULT 'Sedang',
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tpl_ws` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tpl_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `subtasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_task` (`task_id`),
    CONSTRAINT `fk_sub_task` FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `task_comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_task` (`task_id`),
    CONSTRAINT `fk_cmnt_task` FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cmnt_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `attachments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT UNSIGNED NOT NULL,
    `uploader_id` INT UNSIGNED DEFAULT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `file_type` VARCHAR(80) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_task` (`task_id`),
    CONSTRAINT `fk_att_task` FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `reminders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `task_id` INT UNSIGNED DEFAULT NULL,
    `remind_at` DATETIME NOT NULL,
    `channel` ENUM('Aplikasi','Email') NOT NULL DEFAULT 'Aplikasi',
    `status` ENUM('Antre','Terkirim','Batal') NOT NULL DEFAULT 'Antre',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`),
    CONSTRAINT `fk_rem_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rem_task` FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user_preferences` (
    `user_id` INT UNSIGNED PRIMARY KEY,
    `app_notification` TINYINT(1) NOT NULL DEFAULT 1,
    `email_notification` TINYINT(1) NOT NULL DEFAULT 1,
    `daily_summary` TINYINT(1) NOT NULL DEFAULT 0,
    `default_reminder_minutes` INT UNSIGNED NOT NULL DEFAULT 30,
    `quiet_hours_start` TIME DEFAULT NULL,
    `quiet_hours_end` TIME DEFAULT NULL,
    `timezone` VARCHAR(40) NOT NULL DEFAULT 'Asia/Jakarta',
    CONSTRAINT `fk_pref_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `feedbacks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `workspace_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `type` ENUM('Saran','Kendala','Lainnya') NOT NULL DEFAULT 'Saran',
    `content` TEXT NOT NULL,
    `status` ENUM('Baru','Diproses','Selesai') NOT NULL DEFAULT 'Baru',
    `admin_response` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_fb_ws` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fb_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `workspace_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `action_type` VARCHAR(40) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `level` ENUM('info','warning','error') NOT NULL DEFAULT 'info',
    `reference_id` VARCHAR(64) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ws` (`workspace_id`),
    CONSTRAINT `fk_log_ws` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `backup_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `workspace_id` INT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_size` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('Pending','Sukses','Gagal') NOT NULL DEFAULT 'Pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bk_ws` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;