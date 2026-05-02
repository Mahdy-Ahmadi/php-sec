-- ایجاد دیتابیس
DROP DATABASE IF EXISTS `secure_db`;
CREATE DATABASE IF NOT EXISTS `secure_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `secure_db`;

-- جدول کاربران
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `email_verification_token` VARCHAR(64) NULL,
    `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `two_factor_secret` VARCHAR(255) NULL,
    `backup_codes` JSON NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
    `lock_reason` TEXT NULL,
    `failed_login_attempts` INT NOT NULL DEFAULT 0,
    `last_login_at` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `password_reset_token` VARCHAR(64) NULL,
    `password_reset_expires` TIMESTAMP NULL,
    `remember_token` VARCHAR(64) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_email` (`email`),
    UNIQUE KEY `idx_uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سشن‌ها
CREATE TABLE `sessions` (
    `id` VARCHAR(128) NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NOT NULL,
    `payload` TEXT NOT NULL,
    `last_activity` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تلاش‌های ناموفق
CREATE TABLE `failed_login_attempts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email_ip` (`email`, `ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- insert admin user (password: Admin@12345678)
INSERT INTO `users` (`uuid`, `name`, `email`, `password_hash`, `email_verified`, `role`) VALUES
(UUID(), 'Admin', 'admin@example.com', '$2y$12$YourHashHere', 1, 'admin');
