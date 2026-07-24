<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Migrator — applies versioned SQL migrations from a directory.
 *
 * File naming convention: NNN_description.sql where NNN is a zero-padded
 * sequence (001, 002, ...). Files are scanned in lexicographic order which
 * matches the numeric order.
 *
 * The applied versions live in the `schema_migrations` table. The table is
 * created lazily on first `up()`. Each migration is wrapped in a transaction;
 * a failed migration aborts without recording the version, so the next run
 * picks up where it left off.
 *
 * Concurrent safety: `up()` takes a MySQL user-level advisory lock keyed on
 * 'keuangan_migrator'. Two operators running migrate simultaneously will
 * see the second one wait (or get a clear error if non-blocking).
 *
 * Usage:
 *
 *   $m = new Migrator($pdo, __DIR__ . '/../database/migrations');
 *   $m->up();      // apply pending
 *   $m->status();  // ['applied' => [...], 'pending' => [...]]
 */
final class Migrator
{
    public const LOCK_NAME = 'keuangan_migrator';
    public const MIGRATIONS_TABLE = 'schema_migrations';

    private PDO $db;
    private string $dir;

    public function __construct(PDO $db, string $migrationsDir)
    {
        $this->db  = $db;
        $this->dir = rtrim($migrationsDir, '/');
    }

    /**
     * @return array{applied: string[], pending: string[]}
     */
    public function status(): array
    {
        $this->ensureMigrationsTable();
        $applied = $this->appliedVersions();
        $all     = $this->discoverFiles();
        $pending = array_values(array_diff($all, $applied));
        return ['applied' => $applied, 'pending' => $pending];
    }

    /**
     * Apply pending migrations in order. Returns the list of versions that
     * were applied during this run.
     */
    public function up(): array
    {
        $this->ensureMigrationsTable();

        // Try the advisory lock; if it's already held, refuse rather than
        // enter a wait that could confuse operators.
        $lock = $this->tryLock();
        if ($lock === false) {
            throw new \RuntimeException(
                'Another migrator is already running (advisory lock held).'
            );
        }

        try {
            $applied = $this->appliedVersions();
            $discovered = $this->discoverFiles();

            $newlyApplied = [];
            foreach ($discovered as $version) {
                if (in_array($version, $applied, true)) {
                    continue;
                }
                $this->runOne($version);
                $newlyApplied[] = $version;
            }
            return $newlyApplied;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Drop ALL tables in the configured database and re-run every migration
     * from scratch. DESTRUCTIVE — intended for local development only.
     *
     * Caller is expected to require an explicit confirmation flag (see
     * scripts/migrate.php). This method itself does not gate on a flag
     * so tests can drive it; humans run via the CLI which enforces the flag.
     */
    public function fresh(): array
    {
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $tables = $this->db
                ->query('SHOW TABLES')
                ->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $t) {
                // Don't drop tables outside our migrator's namespace safety,
                // but for fresh() we explicitly mean EVERYTHING. The
                // operator has confirmed; trust them.
                $this->db->exec("DROP TABLE IF EXISTS `" . str_replace('`', '``', $t) . "`");
            }
        } finally {
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
        return $this->up();
    }

    // -------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------

    private function discoverFiles(): array
    {
        if (!is_dir($this->dir)) {
            return [];
        }
        $files = glob($this->dir . '/*.sql') ?: [];
        $versions = [];
        foreach ($files as $f) {
            $versions[] = pathinfo($f, PATHINFO_FILENAME);
        }
        // Lexicographic sort equals numeric order given zero-padding.
        sort($versions, SORT_STRING);
        return $versions;
    }

    /**
     * @return string[]
     */
    private function appliedVersions(): array
    {
        $rows = $this->db->query(
            'SELECT version FROM ' . self::MIGRATIONS_TABLE . ' ORDER BY version ASC'
        )->fetchAll(PDO::FETCH_COLUMN);
        return array_map('strval', $rows);
    }

    private function ensureMigrationsTable(): void
    {
        // Cheap to run every time; idempotent.
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::MIGRATIONS_TABLE . ' (
                version    VARCHAR(255) NOT NULL PRIMARY KEY,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function runOne(string $version): void
    {
        $path = $this->dir . '/' . $version . '.sql';
        if (!is_file($path)) {
            // The filename in the table could reference a deleted file.
            // Skip with a notice rather than abort the entire run.
            fwrite(STDERR, "  skip $version (file missing)\n");
            return;
        }
        $sql = file_get_contents($path);

        $this->db->beginTransaction();
        try {
            $this->db->exec($sql);
            $stmt = $this->db->prepare(
                'INSERT INTO ' . self::MIGRATIONS_TABLE . ' (version) VALUES (:v)'
            );
            $stmt->execute([':v' => $version]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new \RuntimeException(
                "Migration $version failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Non-blocking advisory lock. Returns the lock resource on success
     * (cast to string — MySQL returns it as a numeric string), or false
     * if it could not be acquired.
     */
    private function tryLock()
    {
        $stmt = $this->db->prepare('SELECT GET_LOCK(:name, 0) AS got');
        $stmt->execute([':name' => self::LOCK_NAME]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['got'] !== 1) {
            return false;
        }
        return self::LOCK_NAME;
    }

    private function releaseLock(): void
    {
        try {
            $stmt = $this->db->prepare('SELECT RELEASE_LOCK(:name)');
            $stmt->execute([':name' => self::LOCK_NAME]);
            $stmt->fetch();
        } catch (Throwable $e) {
            // Best-effort release. The lock will free when the connection
            // closes either way.
        }
    }
}
