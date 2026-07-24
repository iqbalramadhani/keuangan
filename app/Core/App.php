<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * App — entry point used by public/index.php and by tests.
 */
final class App
{
    public Router $router;
    public PDO $db;
    /** @var array */
    public array $config;

    public function __construct(PDO $db, array $config)
    {
        $this->db     = $db;
        $this->config = $config;
        $this->router = new Router();

        $this->registerRoutes();

        \App\Bootstrap::$db     = $db;
        \App\Bootstrap::$config = $config;
    }

    public function run(): void
    {
        $this->router->dispatch(Request::capture());
    }

    public function router(): Router
    {
        return $this->router;
    }

    private function registerRoutes(): void
    {
        $r = $this->router;

        // Auth
        $r->get   ('/login',  'Auth', 'loginForm');
        $r->post  ('/login',  'Auth', 'loginProcess');
        $r->post  ('/logout', 'Auth', 'logout');

        // Dashboard
        $r->get   ('/',         'Dashboard', 'index');
        $r->get   ('/dashboard','Dashboard', 'index');

        // Transactions
        $r->get   ('/transactions',           'Transaction', 'index');
        $r->get   ('/transactions/new',       'Transaction', 'form');
        $r->post  ('/transactions',           'Transaction', 'create');
        $r->get   ('/transactions/edit',      'Transaction', 'form');
        $r->post  ('/transactions/update',    'Transaction', 'update');
        $r->post  ('/transactions/delete',    'Transaction', 'delete');

        // Categories
        $r->get   ('/categories',   'Category', 'index');
        $r->post  ('/categories',   'Category', 'create');
        $r->post  ('/categories/delete', 'Category', 'delete');

        // API
        $r->get   ('/api/summary',  'Api', 'summary');

        // Migration (no auth required — operator runs this after deploy)
        $r->get   ('/migrate',      'Migrate', 'index');
        $r->post  ('/migrate',      'Migrate', 'index');
    }
}
