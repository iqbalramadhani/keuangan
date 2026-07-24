<?php
declare(strict_types=1);

/**
 * scripts/migrate.php — CLI for the migration engine.
 *
 * Usage:
 *   php scripts/migrate.php                # alias for `up` (apply pending)
 *   php scripts/migrate.php up
 *   php scripts/migrate.php status
 *   php scripts/migrate.php fresh --confirm
 *
 * The `fresh` command drops EVERY table in the database — there is no way
 * to undo it. It requires the literal flag `--confirm` after `fresh`.
 */

require dirname(__DIR__) . '/app/Bootstrap.php';

use App\Bootstrap;
use App\Core\Migrator;

$argvShift = array_slice($argv, 1);
$cmd       = $argvShift[0] ?? 'up';

$migrationsDir = dirname(__DIR__) . '/database/migrations';

try {
    $db = Bootstrap::boot();
} catch (PDOException $e) {
    fwrite(STDERR, "Gagal konek database: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Pastikan .env (DB_HOST/DB_USER/DB_PASSWORD/DB_NAME) benar.\n");
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, "Bootstrap error: " . $e->getMessage() . "\n");
    exit(1);
}

$migrator = new Migrator($db, $migrationsDir);

switch ($cmd) {
    case 'up':
    case 'migrate':
        runUp($migrator);
        break;

    case 'status':
        runStatus($migrator);
        break;

    case 'fresh':
        if (!in_array('--confirm', $argvShift, true)) {
            fwrite(STDERR, "PERINGATAN: 'fresh' akan MENGHAPUS seluruh tabel di database.\n");
            fwrite(STDERR, "Jalankan dengan flag --confirm jika Anda benar-benar yakin.\n");
            exit(2);
        }
        runFresh($migrator);
        break;

    case '--help':
    case '-h':
    case 'help':
        printHelp();
        break;

    default:
        fwrite(STDERR, "Perintah tidak dikenal: $cmd\n\n");
        printHelp();
        exit(1);
}

// ============================================================================

function runUp(Migrator $m): void
{
    try {
        $st = $m->status();
    } catch (Throwable $e) {
        fwrite(STDERR, "Gagal membaca status: " . $e->getMessage() . "\n");
        exit(1);
    }

    if (!$st['pending']) {
        echo "✓ Schema sudah up-to-date (" . count($st['applied']) . " migration terpasang).\n";
        return;
    }

    echo "Menerapkan " . count($st['pending']) . " migration...\n";
    try {
        $applied = $m->up();
    } catch (Throwable $e) {
        fwrite(STDERR, "✗ Migration gagal: " . $e->getMessage() . "\n");
        exit(1);
    }
    foreach ($applied as $v) {
        echo "  ↑ $v\n";
    }
    echo "✓ Selesai. " . count($applied) . " migration baru diterapkan.\n";
}

function runStatus(Migrator $m): void
{
    $st = $m->status();
    echo "Applied (" . count($st['applied']) . "):\n";
    foreach ($st['applied'] as $v) {
        echo "  ✓ $v\n";
    }
    echo "\nPending (" . count($st['pending']) . "):\n";
    if (!$st['pending']) {
        echo "  (none)\n";
    } else {
        foreach ($st['pending'] as $v) {
            echo "  → $v\n";
        }
    }
}

function runFresh(Migrator $m): void
{
    echo "⚠ Menghapus seluruh tabel...\n";
    try {
        $applied = $m->fresh();
    } catch (Throwable $e) {
        fwrite(STDERR, "✗ fresh gagal: " . $e->getMessage() . "\n");
        exit(1);
    }
    foreach ($applied as $v) {
        echo "  ↑ $v\n";
    }
    echo "✓ Database direbuild dari awal. " . count($applied) . " migration diterapkan.\n";
}

function printHelp(): void
{
    echo "Usage: php scripts/migrate.php <command> [flags]\n\n";
    echo "Commands:\n";
    echo "  up (default)  Apply pending migrations.\n";
    echo "  status        Show applied & pending migrations.\n";
    echo "  fresh         Drop ALL tables and re-run every migration (requires --confirm).\n";
    echo "  help          Show this help.\n";
    echo "\nFlags:\n";
    echo "  --confirm     Required for `fresh` — confirms destructive action.\n";
}
