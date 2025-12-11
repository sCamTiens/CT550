<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Support\JWTHelper;
use App\Core\Request;

class JWTMiddleware
{
    /**
     * Validate JWT token from Authorization header
     * 
     * @param Request $request
     * @return bool|array Returns user data if valid, false otherwise
     */
    public static function handle(Request $request): bool|array
    {
        try {
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Check if this is an API request or page request
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isApiRequest = str_starts_with($uri, '/api/') ||
                (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
                ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' || // Most POST requests expect JSON
                str_contains($uri, '/cart/') || // Cart operations
                str_contains($uri, '/checkout/') || // Checkout operations
                str_contains($uri, '/addresses/'); // Address operations

            $token = null;

            // Priority 1: Get token from PHP session (preferred method)
            if (!empty($_SESSION['customer']['access_token'])) {
                $token = $_SESSION['customer']['access_token'];
            } else {
                // Priority 2: Get token from Authorization header (fallback)
                $authHeader = null;

                // Method 1: Check raw HTTP headers (for PHP built-in server)
                if (function_exists('getallheaders')) {
                    $headers = getallheaders();
                    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
                }

                // Method 2: apache_request_headers
                if (!$authHeader && function_exists('apache_request_headers')) {
                    $headers = apache_request_headers();
                    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
                }

                // Method 3: $_SERVER['HTTP_AUTHORIZATION']
                if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
                    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
                }

                // Method 4: REDIRECT_HTTP_AUTHORIZATION (for some Apache configs)
                if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
                }

                // Extract token from header
                $token = JWTHelper::extractToken($authHeader);
            }

            // Debug log
            error_log('[JWTMiddleware] Token found: ' . ($token ? 'YES' : 'NO'));
            error_log('[JWTMiddleware] Session customer exists: ' . (isset($_SESSION['customer']) ? 'YES' : 'NO'));
            error_log('[JWTMiddleware] Session access_token exists: ' . (isset($_SESSION['customer']['access_token']) ? 'YES' : 'NO'));

            if (!$token) {
                self::sendUnauthorizedResponse('Token not provided');
                return false;
            }

            // Validate token
            $userData = JWTHelper::getUserFromToken($token);

            // Debug log
            error_log('[JWTMiddleware] Token validation result: ' . ($userData ? 'SUCCESS' : 'FAILED'));
            if (!$userData) {
                error_log('[JWTMiddleware] Token content (first 50 chars): ' . substr($token, 0, 50));
            }

            if (!$userData) {
                // Clear invalid session
                unset($_SESSION['customer']);
                self::sendUnauthorizedResponse('Token không hợp lệ hoặc đã hết hạn');
                return false;
            }

            // Attach user data to request for controller access
            $request->user = $userData;

            return $userData;
        } catch (\Exception $e) {
            // Log error
            error_log('[JWTMiddleware] Error: ' . $e->getMessage());
            error_log('[JWTMiddleware] Stack trace: ' . $e->getTraceAsString());

            // Always return 401 for any authentication error
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Authentication error: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Send unauthorized JSON response
     */
    private static function sendUnauthorizedResponse(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json');

        // Debug info
        $debugInfo = [
            'success' => false,
            'error' => 'Unauthorized',
            'message' => $message
        ];

        // Add debug info in development
        if (getenv('APP_ENV') !== 'production') {
            $debugInfo['debug'] = [
                'http_authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'not set',
                'redirect_http_authorization' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'not set',
                'server_keys' => array_keys($_SERVER)
            ];
        }

        echo json_encode($debugInfo);
        exit;
    }

    /**
     * Verify customer role
     */
    public static function requireCustomer(Request $request): bool
    {
        $userData = self::handle($request);

        if (!$userData || ($userData['role'] ?? '') !== 'customer') {
            self::sendUnauthorizedResponse('Customer access required');
            return false;
        }

        return true;
    }

    /**
     * Verify admin role
     */
    public static function requireAdmin(Request $request): bool
    {
        $userData = self::handle($request);

        if (!$userData || ($userData['role'] ?? '') !== 'admin') {
            self::sendUnauthorizedResponse('Admin access required');
            return false;
        }

        return true;
    }
}
