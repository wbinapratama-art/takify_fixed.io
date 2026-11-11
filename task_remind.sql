SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `task_remind` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `task_remind`;

CREATE TABLE `activity_logs` (
  `id` int NOT NULL,
  `workspace_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `level` enum('Info','Warning','Error') DEFAULT 'Info',
  `reference_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `activity_logs` (`id`, `workspace_id`, `user_id`, `action_type`, `description`, `level`, `reference_id`, `created_at`) VALUES
(1, 1, NULL, 'system', '18 reminder siap diproses; 1 ditunda karena jam tenang.', 'Info', 'JOB-1041', '2026-10-23 04:12:31'),
(2, 1, NULL, 'notification', 'Pengiriman email timeout. Retry dijadwalkan 09.45 WIB.', 'Warning', 'NQ-1079', '2026-10-23 04:12:31'),
(3, 1, NULL, 'task_comment', 'Menambahkan komentar pada Finalisasi landing page Q4.', 'Info', 'TSK-024', '2026-10-23 04:12:31'),
(4, 1, NULL, 'task_update', 'Menyelesaikan sub-tugas; progress berubah 50% â†’ 75%.', 'Info', 'TSK-024', '2026-10-23 04:12:31'),
(5, 2, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-23 04:28:31'),
(6, 2, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-23 05:59:52'),
(7, 2, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 08:28:08'),
(8, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 08:40:14'),
(9, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 09:03:02'),
(10, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 09:14:15'),
(11, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 09:22:27'),
(12, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 09:23:49'),
(13, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 09:24:02'),
(14, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 09:37:12'),
(15, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 09:37:43'),
(16, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 09:39:16'),
(17, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 09:44:16'),
(18, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-26 10:55:43'),
(19, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 04:24:36'),
(20, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 14:14:06'),
(21, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 14:39:02'),
(22, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 14:40:17'),
(23, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 14:46:04'),
(24, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 14:51:31'),
(25, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 14:51:36'),
(26, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 15:13:18'),
(27, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 15:13:38'),
(28, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 15:27:33'),
(29, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-27 15:36:57'),
(30, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-29 06:28:29'),
(31, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-29 06:37:10'),
(32, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-29 11:49:07'),
(33, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-29 11:53:46'),
(34, 5, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-29 11:55:09'),
(35, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-29 11:55:33'),
(36, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-29 13:39:19'),
(37, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-29 13:39:41'),
(38, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-29 13:41:39'),
(39, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-29 14:06:25'),
(40, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-30 03:16:04'),
(41, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-10-31 20:34:39'),
(42, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-05 03:39:18'),
(43, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-05 03:42:45'),
(44, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-05 04:14:25'),
(45, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-05 04:39:52'),
(46, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-05 05:40:19'),
(47, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-05 06:13:22'),
(48, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-05 07:18:41'),
(49, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 01:24:47'),
(50, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 01:25:24'),
(51, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 01:32:55'),
(52, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 01:34:29'),
(53, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 01:34:46'),
(54, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 01:37:34'),
(55, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 01:40:50'),
(56, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 01:52:22'),
(57, 3, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 01:57:32'),
(58, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 02:15:11'),
(59, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 02:22:26'),
(60, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 02:39:48'),
(61, 6, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 04:27:44'),
(62, 4, NULL, 'login', 'Login berhasil', 'Info', NULL, '2026-11-09 07:02:44');

CREATE TABLE `attachments` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `attachments` (`id`, `task_id`, `file_name`, `file_path`, `file_size`, `file_type`, `uploaded_by`, `created_at`) VALUES
(1, 1, 'Brief landing page Q4.pdf', '/uploads/brief_q4.pdf', 2400, 'PDF', NULL, '2026-10-23 04:12:31'),
(2, 1, 'Preview desktop v3.png', '/uploads/preview_v3.png', 1800, 'PNG', NULL, '2026-10-23 04:12:31');

CREATE TABLE `backup_logs` (
  `id` int NOT NULL,
  `workspace_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` varchar(50) NOT NULL,
  `status` enum('Terverifikasi','Gagal') DEFAULT 'Terverifikasi',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `backup_logs` (`id`, `workspace_id`, `file_name`, `file_size`, `status`, `created_at`) VALUES
(1, 1, 'tertata_2026-10-15_0200.zip', '42,8 MB', 'Terverifikasi', '2026-10-14 19:00:00'),
(2, 1, 'tertata_2026-10-14_0200.zip', '42,1 MB', 'Terverifikasi', '2026-10-13 19:00:00');

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `workspace_id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `color` varchar(20) DEFAULT 'bg-blue-500',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `categories` (`id`, `workspace_id`, `name`, `description`, `color`, `created_by`, `created_at`) VALUES
(1, 1, 'Produk', 'Pengembangan & pengalaman pengguna', 'bg-purple-500', NULL, '2026-10-23 04:12:31'),
(2, 1, 'Marketing', 'Kampanye, konten, dan komunikasi', 'bg-red-500', NULL, '2026-10-23 04:12:31'),
(3, 1, 'Operasional', 'Proses internal & laporan tim', 'bg-green-500', NULL, '2026-10-23 04:12:31'),
(4, 2, 'makanan', NULL, '#805ae7', NULL, '2026-10-23 06:02:02');

CREATE TABLE `category_members` (
  `category_id` int NOT NULL,
  `user_id` int NOT NULL,
  `access_level` enum('Pemilik','Editor','Pembaca') DEFAULT 'Pembaca'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `feedbacks` (
  `id` int NOT NULL,
  `workspace_id` int NOT NULL,
  `user_id` int NOT NULL,
  `subject` varchar(150) NOT NULL,
  `type` enum('Saran','Kendala','Lainnya') NOT NULL,
  `content` text NOT NULL,
  `status` enum('Baru','Diproses','Selesai') DEFAULT 'Baru',
  `admin_response` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `reminders` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_id` int DEFAULT NULL,
  `remind_at` datetime NOT NULL,
  `channel` enum('Aplikasi','Email') DEFAULT 'Aplikasi',
  `status` enum('Antre','Terkirim','Gagal') DEFAULT 'Antre',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `subtasks` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `is_completed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `subtasks` (`id`, `task_id`, `title`, `is_completed`, `created_at`) VALUES
(1, 1, 'Finalisasi struktur konten', 1, '2026-10-23 04:12:31'),
(2, 1, 'Review desain desktop bersama tim', 1, '2026-10-23 04:12:31'),
(3, 1, 'Implementasi komponen utama', 1, '2026-10-23 04:12:31'),
(4, 1, 'Uji responsif & aksesibilitas', 0, '2026-10-23 04:12:31');

CREATE TABLE `tags` (
  `id` int NOT NULL,
  `workspace_id` int NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `tags` (`id`, `workspace_id`, `name`) VALUES
(1, 1, '#website'),
(2, 1, '#q4'),
(3, 1, '#konten'),
(4, 1, '#laporan'),
(5, 1, '#riset');

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `workspace_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `status` enum('Belum dimulai','Sedang dikerjakan','Selesai') DEFAULT 'Belum dimulai',
  `priority` enum('Rendah','Sedang','Tinggi') DEFAULT 'Sedang',
  `progress` int DEFAULT '0',
  `due_date` datetime DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT '0',
  `recurrence_rule` varchar(100) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `tasks` (`id`, `workspace_id`, `category_id`, `title`, `description`, `status`, `priority`, `progress`, `due_date`, `is_recurring`, `recurrence_rule`, `created_by`, `assigned_to`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Finalisasi landing page Q4', 'Selesaikan desain dan implementasi landing page...', 'Sedang dikerjakan', 'Tinggi', 75, '2026-10-16 15:00:00', 0, NULL, NULL, NULL, '2026-10-23 04:12:31', '2026-10-23 04:12:31'),
(2, 1, 2, 'Review copy kampanye Oktober', 'Marketing - #konten', 'Belum dimulai', 'Sedang', 50, '2026-10-15 14:00:00', 0, NULL, NULL, NULL, '2026-10-23 04:12:31', '2026-10-23 04:12:31'),
(3, 1, 3, 'Perbarui panduan onboarding', 'Operasional - #dokumentasi', 'Belum dimulai', 'Tinggi', 25, '2026-10-14 17:00:00', 0, NULL, NULL, NULL, '2026-10-23 04:12:31', '2026-10-23 04:12:31'),
(4, 1, 1, 'Riset kebutuhan pengguna', 'Produk - #riset', 'Belum dimulai', 'Rendah', 0, '2026-10-19 10:00:00', 0, NULL, NULL, NULL, '2026-10-23 04:12:31', '2026-10-23 04:12:31');

CREATE TABLE `task_comments` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `task_tags` (
  `task_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `task_tags` (`task_id`, `tag_id`) VALUES
(1, 1),
(1, 2),
(2, 3),
(4, 5);

CREATE TABLE `templates` (
  `id` int NOT NULL,
  `workspace_id` int NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `category_id` int DEFAULT NULL,
  `default_priority` enum('Rendah','Sedang','Tinggi') DEFAULT 'Sedang',
  `is_public` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `users` (
  `id` int NOT NULL,
  `workspace_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('Admin','Anggota','Pengamat') DEFAULT 'Anggota',
  `status` enum('Aktif','Diundang') DEFAULT 'Aktif',
  `last_active_at` datetime DEFAULT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `gender` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `timezone` varchar(100) DEFAULT 'Asia/Jakarta',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `user_preferences` (
  `user_id` int NOT NULL,
  `app_notification` tinyint(1) DEFAULT '1',
  `email_notification` tinyint(1) DEFAULT '1',
  `daily_summary` tinyint(1) DEFAULT '0',
  `default_reminder_minutes` int DEFAULT '30',
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `timezone` varchar(50) DEFAULT 'Asia/Jakarta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `workspaces` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `workspaces` (`id`, `name`, `created_at`) VALUES
(1, 'Studio Nusantara', '2026-10-23 04:12:31'),
(2, 'Workspace agus setiawan', '2026-10-23 04:28:15'),
(3, 'karlis', '2026-10-26 08:40:11'),
(4, 'khusus admin', '2026-10-26 08:59:08'),
(5, 'User budi', '2026-10-29 11:54:43'),
(6, 'Workspace wisnu kar', '2026-11-09 04:27:28');

ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workspace_id` (`workspace_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

ALTER TABLE `backup_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workspace_id` (`workspace_id`);

ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workspace_id` (`workspace_id`),
  ADD KEY `created_by` (`created_by`);

ALTER TABLE `category_members`
  ADD PRIMARY KEY (`category_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workspace_id` (`workspace_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_id` (`task_id`);

ALTER TABLE `subtasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`);

ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workspace_id` (`workspace_id`);

ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workspace_id` (`workspace_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `assigned_to` (`assigned_to`);

ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `task_tags`
  ADD PRIMARY KEY (`task_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workspace_id` (`workspace_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `workspace_id` (`workspace_id`);

ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`);

ALTER TABLE `workspaces`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

ALTER TABLE `attachments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `backup_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `feedbacks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `reminders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `subtasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `task_comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `workspaces`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `backup_logs`
  ADD CONSTRAINT `backup_logs_ibfk_1` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE;

ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `categories_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `category_members`
  ADD CONSTRAINT `category_members_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `category_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedbacks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminders_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

ALTER TABLE `subtasks`
  ADD CONSTRAINT `subtasks_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

ALTER TABLE `tags`
  ADD CONSTRAINT `tags_ibfk_1` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE;

ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `task_tags`
  ADD CONSTRAINT `task_tags_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

ALTER TABLE `templates`
  ADD CONSTRAINT `templates_ibfk_1` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `templates_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `templates_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;