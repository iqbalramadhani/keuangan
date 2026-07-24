<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Transaction;

final class ApiController extends Controller
{
    /**
     * GET /api/summary — JSON for the dashboard chart.
     */
    public function summary(): void
    {
        \App\Helpers\auth_require_login();
        $months = (int)$this->request->getInput('months', 12);
        $userId = (int)$_SESSION['user_id'];

        $tx = new Transaction($this->db);
        $data = $tx->monthlySummary($userId, $months);

        $labels  = array_map(static fn($r) => $r['label'],  $data);
        $income  = array_map(static fn($r) => (float)$r['income'], $data);
        $expense = array_map(static fn($r) => (float)$r['expense'], $data);

        $this->json([
            'months'  => $labels,
            'income'  => $income,
            'expense' => $expense,
            'kpi'     => $tx->kpiForCurrentMonth($userId),
        ]);
    }
}
