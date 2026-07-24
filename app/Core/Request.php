<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Request — thin wrapper around $_GET / $_POST / $_SERVER
 * with input sanitization and helpers for CSRF.
 */
final class Request
{
    private array $get;
    private array $post;
    private array $server;

    public function __construct(array $get, array $post, array $server)
    {
        $this->get    = $get;
        $this->post   = $post;
        $this->server = $server;
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function input(string $key, $default = null)
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }
        return $this->get[$key] ?? $default;
    }

    public function postInput(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    public function getInput(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    public function allPost(): array
    {
        return $this->post;
    }

    public function allGet(): array
    {
        return $this->get;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr($this->server['HTTP_USER_AGENT'] ?? '', 0, 255);
    }

    /**
     * Strip tags & trim. Use for human-typed text fields.
     */
    public function clean(string $key, int $maxLen = 255): string
    {
        $v = (string)($this->input($key, '') ?? '');
        $v = trim(strip_tags($v));
        if ($maxLen > 0) {
            $v = mb_substr($v, 0, $maxLen);
        }
        return $v;
    }
}
