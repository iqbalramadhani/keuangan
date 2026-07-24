<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Flash messages — stored in session and consumed on next render.
 * Stored as array of ['type' => 'success'|'error'|..., 'msg' => ...].
 */
final class Flash
{
    public static function set(string $type, string $msg): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
    }

    public static function success(string $msg): void { self::set('success', $msg); }
    public static function error(string $msg): void   { self::set('error',   $msg); }

    /**
     * Pull all flash messages and clear them.
     * @return array<int, array{type:string, msg:string}>
     */
    public static function pullAll(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }
        $msgs = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $msgs;
    }
}
