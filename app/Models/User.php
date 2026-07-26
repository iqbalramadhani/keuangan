<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    public function findByUsername(string $username): ?array
    {
        $row = $this->fetchOne(
            'SELECT id, username, password_hash, last_login_at
             FROM users WHERE username = :u LIMIT 1',
            [':u' => $username]
        );
        return $row;
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, username, password_hash, last_login_at
             FROM users WHERE id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    public function create(string $username, string $password): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql  = 'INSERT INTO users (username, password_hash) VALUES (:u, :h)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':u' => $username, ':h' => $hash]);
        return (int)$this->db->lastInsertId();
    }

    public function count(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS n FROM users');
        return (int)($row['n'] ?? 0);
    }

    public function touchLastLogin(int $id): void
    {
        $this->execute(
            'UPDATE users SET last_login_at = NOW() WHERE id = :id',
            [':id' => $id]
        );
    }
}
