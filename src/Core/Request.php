<?php

namespace App\Core;

final class Request
{
    private string $method;
    private string $path;

    /**
     * User data injected by JWT middleware
     * @var array|null
     */
    public ?array $user = null;

    private function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url($uri, PHP_URL_PATH) ?: '/';
    }

    public static function capture(): self
    {
        return new self();
    }

    public function method(): string
    {
        return $this->method;
    }
    public function path(): string
    {
        return $this->path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Get the request body as an associative array (for JSON requests)
     * 
     * @return array
     */
    public function getBody(): array
    {
        $body = file_get_contents('php://input');
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }
}
