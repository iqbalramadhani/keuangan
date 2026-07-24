<?php
declare(strict_types=1);

namespace App\Core;

/**
 * View — minimal template engine.
 *
 * Renders a view file inside a layout by using output buffering.
 * Escapes via the global e() helper.
 */
final class View
{
    /**
     * Render a view file with $data extracted into local scope.
     * If $layout is provided, the view's output is captured and
     * inserted into $layout at $slot position.
     */
    public static function render(string $name, array $data = [], ?string $layout = null): void
    {
        $file = self::resolve($name);
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $name");
        }
        extract($data, EXTR_SKIP);

        if ($layout === null) {
            require $file;
            return;
        }

        ob_start();
        require $file;
        $content = (string)ob_get_clean();

        $layoutFile = self::resolve($layout);
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout not found: $layout");
        }
        // Layout receives $content in local scope.
        require $layoutFile;
    }

    /**
     * Render a partial (no layout) and return its HTML.
     */
    public static function renderPartial(string $name, array $data = []): string
    {
        $file = self::resolve($name);
        if (!is_file($file)) {
            return '';
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }

    private static function resolve(string $name): string
    {
        // Allow "layouts/header", "auth/login" — relative to app/Views/
        $candidates = [
            __DIR__ . '/../Views/' . $name . '.php',
            __DIR__ . '/../Views/' . $name,
        ];
        foreach ($candidates as $c) {
            if (is_file($c)) {
                return $c;
            }
        }
        return $candidates[0];
    }
}
