<?php
declare(strict_types=1);

/**
 * helpers.php — global helper functions loaded by Bootstrap on every request.
 *
 * Why a single file? Two reasons:
 *   1. View templates need to call e() unqualified (see below).
 *   2. Composer's PSR-4 autoloader doesn't autoload free-standing functions.
 *
 * Note that ALL qualified helpers (csrf_token(), csrf_field(), csrf_check(),
 * auth_current_user(), auth_login(), auth_logout(), auth_require_login())
 * live in `App\Helpers\auth.php` and must be referenced as
 * `\App\Helpers\csrf_token()` — see how Views and Controllers do it.
 *
 * This file just fills the gaps: e() (escape) and old() (sticky form input).
 */

if (!function_exists('e')) {
    function e(?string $s): string
    {
        return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('old')) {
    /**
     * Pull a previously POSTed value back into a form after a failed submit.
     * Stored by the Controller into $_SESSION['_old'].
     */
    function old(string $key, $default = '')
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return $default;
        }
        if (!isset($_SESSION['_old'][$key])) {
            return $default;
        }
        $v = $_SESSION['_old'][$key];
        unset($_SESSION['_old'][$key]);
        return $v;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        \App\Core\Response::redirect($url);
    }
}