<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Transaction extends Model
{
    /**
     * List transactions for a user, with optional filters.
     * All filters are bound; values are validated upstream.
     *
     * @return array<int, array{
     *   id:int, type:string, amount:string, description:?string,
     *   tx_date:string, category_id:int, category_name:string
     * }>
     */
    public function listFiltered(int $userId, array $filters): array
    {
        $where  = ['t.user_id = :uid'];
        $params = [':uid' => $userId];

        if (!empty($filters['from'])) {
            $where[] = 't.tx_date >= :from';
            $params[':from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 't.tx_date <= :to';
            $params[':to'] = $filters['to'];
        }
        if (!empty($filters['type']) && in_array($filters['type'], ['income','expense'], true)) {
            $where[] = 't.type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['category_id']) && (int)$filters['category_id'] > 0) {
            $where[] = 't.category_id = :cid';
            $params[':cid'] = (int)$filters['category_id'];
        }

        $sql =
            'SELECT t.id, t.type, t.amount, t.description, t.tx_date,
                    t.category_id, c.name AS category_name
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY t.tx_date DESC, t.id DESC
             LIMIT 500';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT t.*, c.name AS category_name, c.type AS category_type
             FROM transactions t JOIN categories c ON c.id = t.category_id
             WHERE t.id = :id AND t.user_id = :uid LIMIT 1',
            [':id' => $id, ':uid' => $userId]
        );
    }

    public function create(int $userId, int $categoryId, string $type, string $amount, ?string $description, string $txDate): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO transactions
                (user_id, category_id, type, amount, description, tx_date)
             VALUES (:u, :c, :t, :a, :d, :date)'
        );
        $stmt->execute([
            ':u'    => $userId,
            ':c'    => $categoryId,
            ':t'    => $type,
            ':a'    => $amount,
            ':d'    => $description,
            ':date' => $txDate,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $userId, int $categoryId, string $type, string $amount, ?string $description, string $txDate): bool
    {
        $rows = $this->execute(
            'UPDATE transactions
             SET category_id = :c, type = :t, amount = :a, description = :d, tx_date = :date
             WHERE id = :id AND user_id = :u',
            [
                ':c'    => $categoryId,
                ':t'    => $type,
                ':a'    => $amount,
                ':d'    => $description,
                ':date' => $txDate,
                ':id'   => $id,
                ':u'    => $userId,
            ]
        );
        return $rows > 0;
    }

    public function delete(int $id, int $userId): bool
    {
        $rows = $this->execute(
            'DELETE FROM transactions WHERE id = :id AND user_id = :u',
            [':id' => $id, ':u' => $userId]
        );
        return $rows > 0;
    }

    /**
     * Last N transactions for a user, used on the dashboard.
     */
    public function recent(int $userId, int $limit = 10): array
    {
        return $this->fetchAll(
            'SELECT t.id, t.type, t.amount, t.description, t.tx_date,
                    c.name AS category_name
             FROM transactions t JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = :u
             ORDER BY t.tx_date DESC, t.id DESC
             LIMIT ' . max(1, min(200, $limit)),
            [':u' => $userId]
        );
    }

    /**
     * Returns three values for the current calendar month:
     *   [ income_total, expense_total, balance ]
     */
    public function kpiForCurrentMonth(int $userId): array
    {
        $sql =
            'SELECT
               COALESCE(SUM(CASE WHEN type = "income"  THEN amount ELSE 0 END), 0) AS income,
               COALESCE(SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END), 0) AS expense,
               COUNT(*) AS cnt
             FROM transactions
             WHERE user_id = :u
               AND tx_date >= (DATE_FORMAT(NOW(), "%Y-%m-01"))
               AND tx_date <  (DATE_FORMAT(NOW() + INTERVAL 1 MONTH, "%Y-%m-01"))';
        $row = $this->fetchOne($sql, [':u' => $userId]) ?: [
            'income' => '0', 'expense' => '0', 'cnt' => 0,
        ];
        return [
            'income'  => (float)$row['income'],
            'expense' => (float)$row['expense'],
            'balance' => (float)$row['income'] - (float)$row['expense'],
            'count'   => (int)$row['cnt'],
        ];
    }

    /**
     * Monthly aggregate for the last N months.
     * Returns: [{'label' => 'YYYY-MM', 'income' => 0.0, 'expense' => 0.0}, ...]
     */
    public function monthlySummary(int $userId, int $months = 12): array
    {
        $months = max(1, min(60, $months));

        // Generate the list of months on PHP side (avoids depending on a
        // built-in calendar table). PHP fills in zeros for empty months.
        $end   = new \DateTimeImmutable('first day of this month 00:00');
        $start = $end->modify('-' . ($months - 1) . ' months');
        $bucket = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $bucket[$key] = [
                'label'   => $cursor->format('M Y'), // e.g. "Agu 2025"
                'iso'     => $key,
                'income'  => 0.0,
                'expense' => 0.0,
            ];
            $cursor = $cursor->modify('+1 month');
        }

        $from = $start->format('Y-m-01');
        // First day of next month after $end.
        $toExclusive = $end->modify('+1 month')->format('Y-m-01');

        $sql =
            'SELECT DATE_FORMAT(tx_date, "%Y-%m") AS bucket,
                    type,
                    SUM(amount) AS total
             FROM transactions
             WHERE user_id = :u
               AND tx_date >= :from
               AND tx_date <  :to
             GROUP BY bucket, type';

        $rows = $this->fetchAll($sql, [
            ':u'    => $userId,
            ':from' => $from,
            ':to'   => $toExclusive,
        ]);

        foreach ($rows as $r) {
            $key = $r['bucket'];
            if (!isset($bucket[$key])) {
                continue;
            }
            if ($r['type'] === 'income') {
                $bucket[$key]['income'] = (float)$r['total'];
            } else {
                $bucket[$key]['expense'] = (float)$r['total'];
            }
        }

        // Return in chronological order.
        $out = array_values($bucket);
        return $out;
    }
}
