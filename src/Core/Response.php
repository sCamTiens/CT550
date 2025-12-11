<?php

namespace App\Core;

class Response
{
    /**
     * Send a JSON response
     * 
     * @param array $data The data to send as JSON
     * @param int $statusCode HTTP status code (default: 200)
     * @return void
     */
    public function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send a plain text response
     * 
     * @param string $text The text to send
     * @param int $statusCode HTTP status code (default: 200)
     * @return void
     */
    public function text(string $text, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/plain; charset=utf-8');
        echo $text;
        exit;
    }

    /**
     * Send an HTML response
     * 
     * @param string $html The HTML to send
     * @param int $statusCode HTTP status code (default: 200)
     * @return void
     */
    public function html(string $html, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    /**
     * Redirect to a URL
     * 
     * @param string $url The URL to redirect to
     * @param int $statusCode HTTP status code (default: 302)
     * @return void
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: $url");
        exit;
    }

    /**
     * Set HTTP status code
     * 
     * @param int $statusCode HTTP status code
     * @return self
     */
    public function status(int $statusCode): self
    {
        http_response_code($statusCode);
        return $this;
    }

    /**
     * Set a response header
     * 
     * @param string $name Header name
     * @param string $value Header value
     * @return self
     */
    public function header(string $name, string $value): self
    {
        header("$name: $value");
        return $this;
    }
}
