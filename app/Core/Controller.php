<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Base Controller — provides access to the PDO instance plus
 * a thin wrapper around rendering views with a default layout.
 */
abstract class Controller
{
    protected PDO $db;
    protected Request $request;
    protected array $config;

    public function __construct(PDO $db, Request $request, array $config)
    {
        $this->db      = $db;
        $this->request = $request;
        $this->config  = $config;
    }

    /**
     * Render a view inside the default layout.
     * `$layout = null` means no layout (used for partials / login).
     */
    protected function render(string $view, array $data = [], ?string $layout = 'layouts/header'): void
    {
        // Inject commonly-needed variables.
        $data['__config']  = $this->config;
        $data['__request'] = $this->request;
        $data['__flashes'] = \App\Helpers\Flash::pullAll();
        $data['__user']    = \App\Helpers\auth_current_user();
        View::render($view, $data, $layout);
    }

    protected function redirect(string $path, int $code = 302): void
    {
        Response::redirect($path, $code);
    }

    protected function json($data, int $status = 200): void
    {
        Response::json($data, $status);
    }
}
