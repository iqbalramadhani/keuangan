-- ============================================================================
-- 003_telegram_bot_state.sql
-- Menyimpan state sementara sesi bot (pending category selection).
-- ============================================================================

CREATE TABLE IF NOT EXISTS `telegram_bot_state` (
  `chat_id`      BIGINT        NOT NULL PRIMARY KEY,
  `state`        VARCHAR(32)   NOT NULL DEFAULT 'idle',
  `payload`      JSON          NULL COMMENT 'Data sementara: type, amount, description, tx_date',
  `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='State mesin percakapan bot Telegram (single-user mode)';
