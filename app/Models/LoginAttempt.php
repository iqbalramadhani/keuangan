<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class LoginAttempt extends Model
{
    public function record(string $username, string $ip, bool $success): void
    {
        $this->execute(
            'INSERT INTO login_attempts (username_try, ip, success) VALUES (:u, :ip, :s)',
            [':u' => $username, ':ip' => $ip, ':s' => $success ? 1 : 0]
        );
    }

    /**
     * Count failures within the last $minutes minutes for either the given
     * username OR the given IP. Returns ['byUser' => int, 'byIp' => int]
     * — caller picks the larger to enforce.
     */
    public function recentFailures(string $username, string $ip, int $minutes = 15): array
    {
        $cutoff = (new \DateTimeImmutable('-' . $minutes . ' minutes'))
                    ->format('Y-m-d H:i:s');

        $byUser = (int)($this->fetchOne(
            'SELECT COUNT(*) AS n FROM login_attempts
             WHERE username_try = :u AND success = 0 AND attempted_at >= :c',
            [':u' => $username, ':c' => $cutoff]
        )['n'] ?? 0);

        $byIp = (int)($this->fetchOne(
            'SELECT COUNT(*) AS n FROM login_attempts
             WHERE ip = :ip AND success = 0 AND attempted_at >= :c',
            [':ip' => $ip, ':c' => $cutoff]
        )['n'] ?? 0);

        return ['byUser' => $byUser, 'byIp' => $byIp];
    }
}
