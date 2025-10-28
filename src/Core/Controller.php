<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    // render view: đặt view tại src/views/...
    protected function view(string $path, array $data = []): string
    {
        // trỏ đúng vào CT550/src/views
        $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views';

        $file = $base . DIRECTORY_SEPARATOR
            . str_replace(['.', '\\'], DIRECTORY_SEPARATOR, $path)
            . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $file");
        }

        ob_start();
        extract($data, EXTR_SKIP);
        require $file;
        return (string) ob_get_clean();
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
