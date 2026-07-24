<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Category extends Model
{
    /**
     * @return array<int, array{id:int, type:string, name:string, is_builtin:int}>
     */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT id, type, name, is_builtin FROM categories ORDER BY type, name'
        );
    }

    /**
     * @return array<int, array{id:int, type:string, name:string, is_builtin:int}>
     */
    public function findByType(string $type): array
    {
        return $this->fetchAll(
            'SELECT id, type, name, is_builtin FROM categories
             WHERE type = :t ORDER BY name',
            [':t' => $type]
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, type, name, is_builtin FROM categories WHERE id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    public function create(string $type, string $name): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (type, name, is_builtin) VALUES (:t, :n, 0)'
        );
        $stmt->execute([':t' => $type, ':n' => $name]);
        return (int)$stmt->lastInsertId();
    }

    /**
     * Refuse to delete built-in rows.
     */
    public function safeDelete(int $id): bool
    {
        $cat = $this->find($id);
        if (!$cat || (int)$cat['is_builtin'] === 1) {
            return false;
        }
        // Will fail if the category is in use (RESTRICT FK).
        $this->execute('DELETE FROM categories WHERE id = :id', [':id' => $id]);
        return true;
    }
}
