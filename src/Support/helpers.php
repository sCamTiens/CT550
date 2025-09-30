<?php
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('view_path')) {
    function view_path(string $rel): string {
        return dirname(__DIR__, 2) . '/views/' . ltrim($rel, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to): void {
        header('Location: ' . $to);
        exit;
    }
}
