<?php
declare(strict_types=1);

/**
 * setup.php — one-time installer / migration runner.
 *
 * Usage:
 *   php setup.php                           # interactive (prompts username + password)
 *   php setup.php admin secretpassword      # non-interactive
 *
 * What this does:
 *   1. Loads .env via Bootstrap and opens a PDO connection.
 *   2. Applies any pending migrations from database/migrations/*.sql.
 *      Idempotent: re-running on a fresh DB applies all migrations; re-running
 *      on an up-to-date DB does nothing.
 *   3. If the `users` table is empty, creates an admin user (optional).
 *
 * After deployment (via GitHub Actions), operator cukup buka browser ke
 * http://domain.com/setup.php untuk jalankan migration + buat admin jika
 * perlu. Atau cukup /migrate jika user sudah ada sebelumnya.
 */

require __DIR__ . '/app/Bootstrap.php';

use App\Bootstrap;
use App\Core\Migrator;
use App\Models\User;

try {
    /** @var PDO $db */
    $db = Bootstrap::boot();
} catch (\PDOException $e) {
    fwrite(STDERR, "Gagal konek database: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Pastikan .env terisi benar (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME).\n");
    exit(1);
} catch (\Throwable $e) {
    fwrite(STDERR, "Bootstrap error: " . $e->getMessage() . "\n");
    exit(1);
}

// ── Token validation for web access ────────────────────────────────────────
// Web request (bukan CLI) wajib punya SETUP_TOKEN di .env + ?token=xxx.
// CLI langsung jalan tanpa token (operator lokal).
if (PHP_SAPI !== 'cli') {
    $requiredToken = Bootstrap::env('SETUP_TOKEN');
    $provided      = $_GET['token'] ?? $_POST['token'] ?? '';

    if (!$requiredToken || $requiredToken === 'CHANGE-ME-TO-RANDOM-STRING') {
        http_response_code(500);
        die('SETUP_TOKEN belum dikonfigurasi di .env hosting.');
    }

    // Reject empty token — show silent page so crawler doesn't trigger it.
    if ($provided === '') {
        http_response_code(403);
        echo '{"error": "Forbidden"}';
        exit;
    }

    // Constant-time comparison to avoid timing attacks.
    if (!hash_equals($requiredToken, $provided)) {
        http_response_code(403);
        echo '{"error": "Forbidden"}';
        exit;
    }
}

$migrationsDir = __DIR__ . '/database/migrations';
$migrator  = new Migrator($db, $migrationsDir);

echo "🔧 Menjalankan migration...\n";
try {
    $applied = $migrator->up();
} catch (\Throwable $e) {
    fwrite(STDERR, "✗ Migration gagal: " . $e->getMessage() . "\n");
    exit(1);
}

if ($applied) {
    echo "✅ Migration diterapkan: " . implode(', ', $applied) . "\n";
} else {
    echo "✅ Schema sudah up-to-date.\n";
}

// Create admin user only if table is empty
$userModel = new User($db);
$hasUsers  = $userModel->count() > 0;

if ($hasUsers) {
    echo "✅ User sudah ada — tidak membuat admin baru.\n";
} else {
    echo "\nTabel users kosong — membuat admin user.\n";

    $cliUsername = $argv[1] ?? null;
    $cliPassword = $argv[2] ?? null;

    if (PHP_SAPI === 'cli' && $cliUsername && $cliPassword) {
        $username = $cliUsername;
        $password = $cliPassword;
    } else {
        echo "Buat admin user\n";
        do {
            echo "  Username (3-32, A-Z a-z 0-9 _): ";
            $username = trim((string)fgets(STDIN));
            if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
                echo "    Tidak valid.\n"; $username = '';
            }
        } while ($username === '');

        do {
            echo "  Password (min 8 chars): ";
            $password = trim((string)fgets(STDIN));
            if (strlen($password) < 8) {
                echo "    Minimal 8 karakter.\n"; $password = '';
            }
        } while ($password === '');
    }

    if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
        fwrite(STDERR, "Username harus 3-32 karakter alfanumerik/garis-bawah.\n");
        exit(1);
    }
    if (strlen($password) < 8) {
        fwrite(STDERR, "Password minimal 8 karakter.\n");
        exit(1);
    }

    $id = $userModel->create($username, $password);
    echo "✅ User '$username' (id=$id) dibuat.\n";
}

echo "\n✅ Setup selesai.\n";
echo "Sekarang buka /login di browser.\n";
