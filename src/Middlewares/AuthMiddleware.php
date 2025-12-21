<?php

namespace App\Middlewares;

use App\Support\JWTHelper;

class AuthMiddleware
{
    /**
     * Get authenticated user from JWT cookie or session
     * @return array|null User data or null if not authenticated
     */
    public static function getAuthenticatedUser(): ?array
    {
        // ✅ PRIORITY 1: Check session first (available immediately after login)
        if (!empty($_SESSION['customer'])) {
            error_log('[AuthMiddleware] Returning user from SESSION');
            return $_SESSION['customer'];
        }

        // ✅ PRIORITY 2: Try to get JWT from cookie (for subsequent requests)
        $jwt = $_COOKIE['jwt_token'] ?? null;

        if (!$jwt) {
            error_log('[AuthMiddleware] No session and no cookie - user not authenticated');
            return null;
        }

        try {
            // Decode JWT using JWTHelper
            $userData = JWTHelper::getUserFromToken($jwt);

            if (!$userData) {
                error_log('[AuthMiddleware] Invalid JWT token');
                return null;
            }

            // Get fresh user info from database
            $pdo = \App\Core\DB::pdo();
            $stmt = $pdo->prepare("
                SELECT u.*, r.name as role_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ? AND r.name = 'Khách hàng'
                LIMIT 1
            ");
            $stmt->execute([$userData['id'] ?? null]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                error_log('[AuthMiddleware] User not found in database or not a customer');
                return null;
            }

            $authenticatedUser = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'phone' => $user['phone'] ?? null,
                'avatar_url' => $user['avatar_url'] ?? null,
                'google_id' => $user['google_id'] ?? null,
                'loyalty_points' => $user['loyalty_points'] ?? 0,
            ];

            // ✅ Store in session for future requests
            $_SESSION['customer'] = $authenticatedUser;
            error_log('[AuthMiddleware] User authenticated from cookie, stored in session');

            return $authenticatedUser;
        } catch (\Exception $e) {
            error_log("[AuthMiddleware] Error: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Check if user is authenticated
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        return self::getAuthenticatedUser() !== null;
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: /login');
            exit;
        }
    }
}
