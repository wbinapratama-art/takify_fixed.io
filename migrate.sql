USE `task_remind`;
SET FOREIGN_KEY_CHECKS = 0;

-- Tambah kolom profil jika belum ada
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `gender` ENUM('Male','Female','Other') DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `dob` DATE DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `shop_name` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_seen_at` DATETIME DEFAULT NULL;

-- Pastikan tabel backup_logs punya status ENUM yang sesuai
ALTER TABLE `backup_logs` MODIFY COLUMN `status` ENUM('Pending','Sukses','Gagal') NOT NULL DEFAULT 'Pending';
ALTER TABLE `backup_logs` MODIFY COLUMN `file_size` VARCHAR(20) DEFAULT NULL;

SET FOREIGN_KEY_CHECKS = 1;