<?php
namespace App\Core;

final class Request
{
    private string $method;
    private string $path;

    private function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url($uri, PHP_URL_PATH) ?: '/';
    }

    public static function capture(): self { return new self(); }

    public function method(): string { return $this->method; }
    public function path(): string   { return $this->path; }

    public function input(string $key, mixed $default=null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}
