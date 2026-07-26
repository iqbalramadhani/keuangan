-- ============================================================================
-- 001_initial.sql — base schema for Keuangan
-- ============================================================================
-- Idempotent: every CREATE TABLE uses IF NOT EXISTS; the INSERT uses IGNORE,
-- so re-applying this file on a populated DB is harmless.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(64)  NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` DATETIME     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- categories
--   Sekarang tidak punya ENUM type — bisa dipakai untuk income ATAU expense
--   atau keduanya. Filter di app layer.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(64) NOT NULL,
  `is_builtin` TINYINT(1)  NOT NULL DEFAULT 0,
  `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- transactions
--   type adalah milik transaksi, bukan kategori.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transactions` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED    NOT NULL,
  `category_id` INT UNSIGNED    NOT NULL,
  `type`        ENUM('income','expense') NOT NULL,
  `amount`      DECIMAL(15,2)   NOT NULL,
  `description` VARCHAR(255)    NULL,
  `tx_date`     DATE            NOT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tx_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)      ON DELETE CASCADE,
  CONSTRAINT `fk_tx_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  KEY `idx_user_date`     (`user_id`, `tx_date`),
  KEY `idx_user_type_date` (`user_id`, `type`, `tx_date`),
  KEY `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- login_attempts (rate-limit & audit)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username_try` VARCHAR(64) NOT NULL,
  `ip`           VARCHAR(45) NOT NULL,
  `success`      TINYINT(1)  NOT NULL,
  `attempted_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ip_time`   (`ip`, `attempted_at`),
  KEY `idx_user_time` (`username_try`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Seed built-in parent categories (cannot be deleted)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `categories` (`name`, `is_builtin`) VALUES
  ('Pemasukan',   1),
  ('Pengeluaran', 1);
