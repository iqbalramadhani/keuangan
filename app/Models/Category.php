<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Category extends Model
{
    /**
     * @return array<int, array{id:int, name:string, is_builtin:int}>
     */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT id, name, is_builtin FROM categories ORDER BY name'
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, name, is_builtin FROM categories WHERE id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    public function create(string $name): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, is_builtin) VALUES (:n, 0)'
        );
        $stmt->execute([':n' => $name]);
        return (int)$this->db->lastInsertId();
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
