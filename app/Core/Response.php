<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Response helpers: view render, redirect, JSON, exit helpers.
 */
final class Response
{
    public static function redirect(string $path, int $code = 302): void
    {
        // Accept either absolute paths or relative ones.
        if (!preg_match('#^https?://#i', $path)) {
            $path = self::baseUrl() . '/' . ltrim($path, '/');
        }
        header('Location: ' . $path, true, $code);
        exit;
    }

    public static function json($data, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function text(string $body, int $status = 200, string $type = 'text/plain'): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header("Content-Type: $type; charset=utf-8");
            header('X-Content-Type-Options: nosniff');
        }
        echo $body;
        exit;
    }

    public static function notFound(string $message = 'Not Found'): void
    {
        self::text($message, 404, 'text/plain');
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::text($message, 403, 'text/plain');
    }

    /**
     * Compute base URL of the app (handles subdirectory installs
     * like /keuangan/ on shared hosting via front-controller pattern).
     */
    public static function baseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['SERVER_PORT'] ?? '') == '443');

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = $https ? 'https' : 'http';

        // Public assets are served from /public; URLs from the browser
        // start at the webroot of public/. Use the script's directory
        // when running under PHP built-in server with a subdir.
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        // SCRIPT_NAME is /index.php — base is "/".
        return $scheme . '://' . $host . '';
    }
}
