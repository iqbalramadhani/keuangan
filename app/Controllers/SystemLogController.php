<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Helpers;

final class SystemLogController extends Controller
{
    public function index(): void
    {
        \App\Helpers\auth_require_login();

        $logs = Logger::listLogFiles();

        $this->render('systemlog/index', [
            'title' => 'System Logs',
            'logs'  => $logs,
        ]);
    }

    public function view(): void
    {
        \App\Helpers\auth_require_login();

        $file = $this->request->getInput('file', '');
        
        // Basic security check to prevent path traversal
        if (!preg_match('/^\d{4}-\d{2}-\d{2}\.log$/', basename($file))) {
            http_response_code(400);
            echo "Invalid log file name.";
            return;
        }

        // Determine full path
        $logsDir = dirname(__DIR__, 2) . '/runtime/logs';
        $fullPath = $logsDir . DIRECTORY_SEPARATOR . basename($file);

        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "Log file not found.";
            return;
        }

        $content = file_get_contents($fullPath);

        $this->render('systemlog/view', [
            'title'   => 'View Log: ' . basename($file),
            'file'    => basename($file),
            'content' => $content,
        ]);
    }
}
