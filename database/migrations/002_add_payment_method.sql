-- ============================================================================
-- 002_add_payment_method.sql — tambah kolom sumber pembayaran ke transactions
-- ============================================================================
ALTER TABLE `transactions`
  ADD COLUMN `payment_method` ENUM('cash','transfer') NOT NULL DEFAULT 'cash'
  AFTER `description`;
