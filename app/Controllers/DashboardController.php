<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Transaction;

final class DashboardController extends Controller
{
    public function index(): void
    {
        \App\Helpers\auth_require_login();
        $userId = (int)$_SESSION['user_id'];

        $txModel = new Transaction($this->db);
        $kpi     = $txModel->kpiForCurrentMonth($userId);
        $recent  = $txModel->recent($userId, 10);
        $summary = $txModel->monthlySummary($userId, 12);

        $this->render('dashboard/index', [
            'title'   => 'Dashboard — Keuangan',
            'kpi'     => $kpi,
            'recent'  => $recent,
            'summary' => $summary,
            'csrf'    => \App\Helpers\csrf_token(),
        ]);
    }
}
