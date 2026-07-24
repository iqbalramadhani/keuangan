<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Migrator;

/**
 * MigrateController — runs database migrations from the browser.
 *
 * Used both by the operator (open /migrate) and by CI after a deploy.
 */
final class MigrateController extends Controller
{
    /**
     * GET  /migrate   — show current status + form to run migrations
     * POST /migrate   — apply pending migrations, return result page or JSON
     */
    public function index(): void
    {
        $migrator = new Migrator($this->db, dirname(__DIR__, 2) . '/database/migrations');
        $status   = $migrator->status();
        $applied  = [];
        $result   = null;

        if ($this->request->isPost()) {
            // Verify CSRF before running destructive work.
            $supplied = (string)$this->request->postInput('_csrf', '');
            if (!\App\Helpers\csrf_check($supplied)) {
                $this->json(['error' => 'Invalid CSRF token'], 403);
                return;
            }

            try {
                $applied = $migrator->up();
                $result  = [
                    'status'    => 'success',
                    'applied'   => $applied,
                    'message'   => count($applied) > 0
                        ? count($applied) . ' migration(s) applied.'
                        : 'Schema already up-to-date.',
                ];
            } catch (\Throwable $e) {
                $result = [
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        // If the request accepts JSON (from cURL), return that instead of HTML.
        $acceptsJson = strpos($this->request->header('Accept', ''), 'application/json') !== false;
        if ($acceptsJson && isset($result)) {
            $this->json($result);
            return;
        }

        // HTML view for browser use.
        $this->render('migrate/result', [
            'title'    => 'Database Migration',
            'layout'   => null,
            'status'   => $status,
            'result'   => $result,
            'pending'  => $status['pending'],
        ]);
    }
}
