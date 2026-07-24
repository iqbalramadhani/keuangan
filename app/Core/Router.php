<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router — defines route → [controller, action] mappings.
 *
 * Routes are matched against PATH_INFO-style URLs OR, when running
 * under the PHP built-in server (or .htaccess is disabled), against
 * $_GET['page'] + $_GET['action'] for shared-hosting compatibility.
 */
final class Router
{
    /** @var array<string, array{ctrl:string, action:string}> */
    private array $getRoutes = [];
    /** @var array<string, array{ctrl:string, action:string}> */
    private array $postRoutes = [];
    /** @var callable[] */
    private array $middlewares = [];

    public function get(string $path, string $controller, string $action = 'index'): void
    {
        $this->getRoutes[self::norm($path)] = [
            'ctrl'   => $controller,
            'action' => $action,
        ];
    }

    public function post(string $path, string $controller, string $action = 'index'): void
    {
        $this->postRoutes[self::norm($path)] = [
            'ctrl'   => $controller,
            'action' => $action,
        ];
    }

    /** Apply a global middleware (e.g. require_login) to every route. */
    public function addMiddleware(callable $mw): void
    {
        $this->middlewares[] = $mw;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = self::currentPath();

        $routes  = $method === 'POST' ? $this->postRoutes : $this->getRoutes;
        $handler = $routes[$path] ?? null;

        if ($handler === null) {
            Response::notFound("Route not found: $method $path");
            return;
        }

        foreach ($this->middlewares as $mw) {
            $mw($request, $path, $method);
        }

        [$ctrlClass, $action] = [self::ns($handler['ctrl']), $handler['action']];
        if (!class_exists($ctrlClass)) {
            throw new \RuntimeException("Controller not found: $ctrlClass");
        }
        /** @var Controller $ctrl */
        $ctrl = new $ctrlClass(\App\Bootstrap::$db, $request, \App\Bootstrap::$config);
        if (!method_exists($ctrl, $action)) {
            throw new \RuntimeException("Action not found: $ctrlClass::$action");
        }
        $ctrl->{$action}();
    }

    private static function norm(string $p): string
    {
        if ($p === '' || $p === '/') {
            return '/';
        }
        return '/' . trim($p, '/');
    }

    /**
     * Compute the current URL path. Prefer REQUEST_URI; fall back to
     * ?page= query-param when running under index.php without rewrites.
     */
    private static function currentPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $q = strpos($uri, '?');
        if ($q !== false) {
            $uri = substr($uri, 0, $q);
        }
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // If served by index.php under a deeper webroot, strip subdir prefix.
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir)) ?: '/';
        }

        // Optional ?page= fallback (only when path is root or /index.php).
        if (($path === '/' || $path === '/index.php')
            && !empty($_GET['page'])
            && is_string($_GET['page'])) {
            $path = '/' . trim($_GET['page'], '/');
        }

        return self::norm($path);
    }

    private static function ns(string $short): string
    {
        // E.g. "Auth" → "App\Controllers\AuthController"
        return 'App\\Controllers\\' . $short . 'Controller';
    }
}
