<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Logger — daily-rotating file logger with automatic expiry cleanup.
 *
 * Log files are stored under:
 *   <project-root>/runtime/logs/YYYY-MM-DD.log
 *
 * Configuration (via .env or Bootstrap::$config):
 *   LOG_RETENTION_DAYS  — how many days to keep log files (default: 30).
 *                          Set to 0 to disable auto-cleanup.
 *   APP_TZ              — timezone used for the daily filename (default: Asia/Jakarta).
 *
 * Usage:
 *   Logger::info('User logged in', ['user_id' => 1]);
 *   Logger::warning('Slow query', ['duration_ms' => 450]);
 *   Logger::error('PDOException', ['message' => $e->getMessage()]);
 *
 * The logger is safe to call before Bootstrap::boot() (falls back to defaults).
 */
final class Logger
{
    public const LEVEL_DEBUG   = 'DEBUG';
    public const LEVEL_INFO    = 'INFO';
    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_ERROR   = 'ERROR';

    /** Absolute path to the logs directory. */
    private static string $logsDir = '';

    /** Cached retention days. -1 means "not yet loaded". */
    private static int $retentionDays = -1;

    /** Cached timezone string. */
    private static string $timezone = '';

    // ── Public API ──────────────────────────────────────────────────────────

    public static function debug(string $message, array $context = []): void
    {
        self::write(self::LEVEL_DEBUG, $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write(self::LEVEL_INFO, $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write(self::LEVEL_WARNING, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write(self::LEVEL_ERROR, $message, $context);
    }

    // ── Core ────────────────────────────────────────────────────────────────

    /**
     * Write a log entry to today's file.
     *
     * Format:
     *   [2026-08-26 21:00:00 WIB] [INFO] Message {"key":"value"}
     */
    public static function write(string $level, string $message, array $context = []): void
    {
        $tz      = self::getTimezone();
        $dt      = new \DateTimeImmutable('now', new \DateTimeZone($tz));
        $logFile = self::getLogsDir() . DIRECTORY_SEPARATOR . $dt->format('Y-m-d') . '.log';

        $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $line = sprintf(
            "[%s %s] [%s] %s%s\n",
            $dt->format('Y-m-d H:i:s'),
            $dt->format('T'),   // timezone abbreviation e.g. WIB
            $level,
            $message,
            $contextStr
        );

        // @ suppresses warning if disk is full — error_log is the fallback.
        if (@file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log('[Logger] Cannot write to log file: ' . $logFile);
            error_log($line);
        }

        // Probabilistic cleanup: ~1% of requests trigger it to avoid I/O on every hit.
        if (random_int(1, 100) === 1) {
            self::purgeOldLogs();
        }
    }

    /**
     * Delete log files older than LOG_RETENTION_DAYS.
     * Called automatically (probabilistic) and can also be called manually.
     */
    public static function purgeOldLogs(): void
    {
        $days = self::getRetentionDays();
        if ($days <= 0) {
            return; // Retention disabled.
        }

        $dir = self::getLogsDir();
        $cutoff = (new \DateTimeImmutable("-{$days} days"))->setTime(0, 0, 0);

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.log') ?: [] as $file) {
            $basename = basename($file, '.log');
            // Only handle files named YYYY-MM-DD.
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $basename)) {
                continue;
            }
            try {
                $fileDate = new \DateTimeImmutable($basename);
            } catch (\Throwable) {
                continue;
            }

            if ($fileDate < $cutoff) {
                @unlink($file);
            }
        }
    }

    /**
     * Return list of existing log files sorted newest-first.
     * Useful for admin log viewer.
     *
     * @return array<array{date: string, path: string, size: int}>
     */
    public static function listLogFiles(): array
    {
        $dir   = self::getLogsDir();
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.log') ?: [];
        $result = [];

        foreach ($files as $file) {
            $basename = basename($file, '.log');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $basename)) {
                continue;
            }
            $result[] = [
                'date' => $basename,
                'path' => $file,
                'size' => (int)filesize($file),
            ];
        }

        // Sort newest first.
        usort($result, fn($a, $b) => strcmp($b['date'], $a['date']));
        return $result;
    }

    // ── Internals ───────────────────────────────────────────────────────────

    private static function getLogsDir(): string
    {
        if (self::$logsDir !== '') {
            return self::$logsDir;
        }
        // Resolve relative to project root (two levels up from app/Core/).
        $dir = dirname(__DIR__, 2) . '/runtime/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        self::$logsDir = $dir;
        return $dir;
    }

    private static function getRetentionDays(): int
    {
        if (self::$retentionDays !== -1) {
            return self::$retentionDays;
        }
        // Try Bootstrap config first, then env directly.
        if (class_exists(\App\Bootstrap::class, false) && isset(\App\Bootstrap::$config['log_retention_days'])) {
            self::$retentionDays = (int)\App\Bootstrap::$config['log_retention_days'];
        } else {
            $raw = getenv('LOG_RETENTION_DAYS');
            self::$retentionDays = ($raw !== false && $raw !== '') ? (int)$raw : 30;
        }
        return self::$retentionDays;
    }

    private static function getTimezone(): string
    {
        if (self::$timezone !== '') {
            return self::$timezone;
        }
        if (class_exists(\App\Bootstrap::class, false) && isset(\App\Bootstrap::$config['tz'])) {
            self::$timezone = \App\Bootstrap::$config['tz'];
        } else {
            $raw = getenv('APP_TZ');
            self::$timezone = ($raw !== false && $raw !== '') ? $raw : 'Asia/Jakarta';
        }
        return self::$timezone;
    }

    /**
     * Allow tests / Bootstrap to override the logs directory.
     */
    public static function setLogsDir(string $dir): void
    {
        self::$logsDir = $dir;
    }

    /**
     * Reset cached state (useful in tests).
     */
    public static function reset(): void
    {
        self::$logsDir       = '';
        self::$retentionDays = -1;
        self::$timezone      = '';
    }
}
