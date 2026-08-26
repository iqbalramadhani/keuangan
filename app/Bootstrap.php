<?php
declare(strict_types=1);

namespace App;

use App\Core\Logger;
use PDO;

/**
 * Bootstrap — session start, error handler, autoloader, config & DB load.
 *
 * Configuration precedence (first non-empty wins per key):
 *   1. Real environment variables getenv(name) / $_ENV / $_SERVER
 *   2. .env file at the project root (key=value lines)
 *   3. config/app.php  (defaults for app-level settings)
 *   4. config/db.php   (DB credentials fallback)
 *
 * Static $db / $config are injected into App and consumed by Controllers.
 * Tests can construct the app directly without invoking boot().
 */
final class Bootstrap
{
    public static ?PDO $db = null;
    public static array $config = [];

    private static bool $booted = false;

    /**
     * Idempotent.
     */
    public static function boot(): PDO
    {
        if (self::$booted && self::$db !== null) {
            return self::$db;
        }
        self::loadEnv();
        self::setSecureSession();
        self::startSession();
        self::registerErrorHandler();
        self::registerAutoloader();
        self::loadHelpers();
        self::loadConfig();
        $db = self::loadDb();

        self::$db     = $db;
        self::$booted = true;
        return $db;
    }

    /**
     * Parse a `.env` file at the project root. Lines like
     *   # comment
     *   KEY=value
     *   KEY="quoted"
     *   export KEY=value
     * Loaded keys do NOT overwrite existing real environment vars.
     */
    private static function loadEnv(): void
    {
        $path = dirname(__DIR__) . '/.env';
        if (!is_file($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (stripos($line, 'export ') === 0) {
                $line = trim(substr($line, 7));
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $key = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));
            // Strip surrounding quotes if present.
            if (strlen($val) >= 2
                && ($val[0] === '"' || $val[0] === "'")
                && $val[strlen($val) - 1] === $val[0]) {
                $val = substr($val, 1, -1);
            }
            // Only set if not already set in the real environment.
            if (getenv($key) === false || getenv($key) === '') {
                putenv("$key=$val");
                $_ENV[$key] = $val;
            }
        }
    }

    public static function env(string $key, $default = null)
    {
        $v = getenv($key);
        if ($v === false || $v === '') {
            return $default;
        }
        return $v;
    }

    private static function setSecureSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['SERVER_PORT'] ?? '') == '443');
        if ($https) {
            ini_set('session.cookie_secure', '1');
        }
    }

    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('KEUANGAN_SESS');
        session_start();
    }

    private static function loadHelpers(): void
    {
        $dir = dirname(__DIR__) . '/app/Helpers';
        // Preload namespace functions & classes from app/Helpers/.
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.php') ?: [] as $f) {
                require_once $f;
            }
        }
        // Preload global helper functions.
        $global = dirname(__DIR__) . '/app/helpers.php';
        if (is_file($global)) {
            require_once $global;
        }
    }

    private static function registerErrorHandler(): void
    {
        $isProd = (self::env('APP_ENV', 'production') === 'production');
        ini_set('display_errors', $isProd ? '0' : '1');
        ini_set('display_startup_errors', $isProd ? '0' : '1');
        ini_set('log_errors', '1');

        // Point PHP's built-in error_log to today's daily log file.
        $tz      = self::env('APP_TZ', 'Asia/Jakarta');
        $today   = (new \DateTimeImmutable('now', new \DateTimeZone($tz)))->format('Y-m-d');
        $logsDir = dirname(__DIR__) . '/runtime/logs';
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0775, true);
        }
        ini_set('error_log', $logsDir . '/' . $today . '.log');

        set_exception_handler(function (\Throwable $e) use ($isProd): void {
            Logger::error('[Uncaught] ' . $e::class . ': ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if ($isProd) {
                http_response_code(500);
                echo 'Internal Server Error';
            } else {
                echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
            }
            exit;
        });
    }

    private static function registerAutoloader(): void
    {
        spl_autoload_register(function (string $class): void {
            if (!str_starts_with($class, 'App\\')) {
                return;
            }
            $rel = substr($class, strlen('App\\'));
            $rel = str_replace('\\', '/', $rel) . '.php';

            $candidates = [
                dirname(__DIR__) . '/app/' . $rel,
                dirname(__DIR__) . '/app/Helpers/' . $rel,
            ];
            foreach ($candidates as $f) {
                if (is_file($f)) {
                    require $f;
                    return;
                }
            }
        });
    }

    private static function loadConfig(): void
    {
        if (self::$config) {
            return;
        }
        // Start from config/app.php if present, else default values.
        $cfg = [
            'app_name'             => 'Keuangan',
            'app_url'              => '',
            'tz'                   => self::env('APP_TZ', 'Asia/Jakarta'),
            'locale'               => self::env('APP_LOCALE', 'id_ID'),
            'currency'             => self::env('APP_CURRENCY', 'IDR'),
            'app_secret'           => 'CHANGE-ME-TO-A-LONG-RANDOM-STRING',
            'idle_timeout'         => (int)self::env('APP_IDLE_TIMEOUT', 1800),
            'login_max_failures'   => (int)self::env('LOGIN_MAX_FAILURES', 5),
            'login_failure_window' => (int)self::env('LOGIN_FAILURE_WINDOW', 900),
            // Logging: berapa hari file log disimpan (0 = tidak pernah dihapus).
            'log_retention_days'   => (int)self::env('LOG_RETENTION_DAYS', 30),
        ];
        $fileCfg = dirname(__DIR__) . '/config/app.php';
        if (is_file($fileCfg)) {
            $overrides = require $fileCfg;
            if (is_array($overrides)) {
                $cfg = array_replace($cfg, $overrides);
            }
        }
        self::$config = $cfg;
    }

    private static function loadDb(): PDO
    {
        // Prefer the .env file, then fall back to config/db.php.
        $cfg = [
            'host'    => self::env('DB_HOST',    '127.0.0.1'),
            'port'    => self::env('DB_PORT',    '3306'),
            'name'    => self::env('DB_NAME',    'keuangan'),
            'user'    => self::env('DB_USER',    'root'),
            'pass'    => self::env('DB_PASSWORD', ''),
            'charset' => self::env('DB_CHARSET', 'utf8mb4'),
        ];
        // Allow config/db.php to override .env when present.
        $fileDb = dirname(__DIR__) . '/config/db.php';
        if (is_file($fileDb)) {
            $overrides = require $fileDb;
            if (is_array($overrides)) {
                $cfg = array_replace($cfg, $overrides);
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            (int)($cfg['port'] ?? 3306),
            $cfg['name'],
            $cfg['charset'] ?? 'utf8mb4'
        );

        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => false,
        ]);

        return $pdo;
    }
}
