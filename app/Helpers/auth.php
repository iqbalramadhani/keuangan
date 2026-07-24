<?php
declare(strict_types=1);

/**
 * Auth helpers — session login, CSRF, output escaping.
 * Loaded lazily via Bootstrap; safe to call site-wide.
 */
namespace App\Helpers;

function auth_current_user(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'       => (int)$_SESSION['user_id'],
        'username' => (string)($_SESSION['username'] ?? ''),
    ];
}

function auth_login(int $userId, string $username): void
{
    // Session fixation defense + activity stamp + CSRF token bootstrap.
    session_regenerate_id(true);
    $_SESSION['user_id']   = $userId;
    $_SESSION['username']  = $username;
    $_SESSION['last_activity'] = time();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function auth_require_login(): void
{
    if (session_status() === PHP_SESSION_ACTIVE
        && !empty($_SESSION['user_id'])
        && !empty($_SESSION['last_activity'])
    ) {
        $cfg = \App\Bootstrap::$config;
        $timeout = (int)($cfg['idle_timeout'] ?? 1800);
        if ((time() - (int)$_SESSION['last_activity']) > $timeout) {
            auth_logout();
            \App\Core\Response::redirect('/login');
        }
        $_SESSION['last_activity'] = time(); // refresh
        return;
    }
    \App\Core\Response::redirect('/login');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    $t = csrf_token();
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_check(string $supplied): bool
{
    $expected = $_SESSION['csrf'] ?? '';
    if ($expected === '' || $supplied === '') {
        return false;
    }
    return hash_equals($expected, $supplied);
}

/**
 * HTML-escape. Always wrap user-controlled data in views with this.
 */
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
