<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Response;

/**
 * require_login middleware — invoked for routes that should not
 * be visible to anonymous users. Redirects to /login otherwise.
 *
 * Exempt paths are passed via Session user_id presence.
 */
final class Auth
{
    public static function requireLogin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return; // bootstrap will start session; if not yet, skip
        }
        if (empty($_SESSION['user_id'])) {
            Response::redirect('/login');
        }
    }
}
