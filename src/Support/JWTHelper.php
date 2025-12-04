<?php

declare(strict_types=1);

namespace App\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTHelper
{
    private static $cachedSecret = null;

    private static function getSecretKey(): string
    {
        // Return cached secret if available
        if (self::$cachedSecret !== null) {
            return self::$cachedSecret;
        }

        // Try to get from environment
        $secret = trim(getenv('JWT_SECRET') ?: '');

        // If not in environment, try to load from .env file directly
        if (!$secret) {
            $envFile = __DIR__ . '/../../.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, 'JWT_SECRET=') === 0) {
                        $secret = trim(substr($line, strlen('JWT_SECRET=')));
                        break;
                    }
                }
            }
        }

        if (!$secret) {
            throw new Exception('JWT_SECRET not configured in .env file');
        }

        // Cache the secret for future use
        self::$cachedSecret = $secret;

        return $secret;
    }

    private static function getExpiry(): int
    {
        // Default: 1 hour
        return (int)(getenv('JWT_EXPIRY') ?: 3600);
    }

    private static function getRefreshExpiry(): int
    {
        // Default: 7 days
        return (int)(getenv('JWT_REFRESH_EXPIRY') ?: 604800);
    }

    /**
     * Generate access token
     * 
     * @param array $payload User data (id, email, role, etc.)
     * @return string JWT token
     */
    public static function generateToken(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + self::getExpiry();

        $tokenPayload = [
            'iat' => $issuedAt,           // Issued at
            'exp' => $expire,              // Expiration time
            'data' => $payload             // User data
        ];

        return JWT::encode($tokenPayload, self::getSecretKey(), 'HS256');
    }

    /**
     * Generate refresh token (longer expiry)
     * 
     * @param array $payload User data
     * @return string Refresh token
     */
    public static function generateRefreshToken(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + self::getRefreshExpiry();

        $tokenPayload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'type' => 'refresh',           // Mark as refresh token
            'data' => $payload
        ];

        return JWT::encode($tokenPayload, self::getSecretKey(), 'HS256');
    }

    /**
     * Validate and decode JWT token
     * 
     * @param string $token JWT token
     * @return object|null Decoded payload or null if invalid
     */
    public static function validateToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key(self::getSecretKey(), 'HS256'));
            return $decoded;
        } catch (\Firebase\JWT\ExpiredException $e) {
            error_log('[JWTHelper] Token expired: ' . $e->getMessage());
            return null;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            error_log('[JWTHelper] Invalid signature: ' . $e->getMessage());
            return null;
        } catch (Exception $e) {
            // Token expired, invalid signature, malformed, etc.
            error_log('[JWTHelper] Token validation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract token from Authorization header
     * 
     * @param string|null $authHeader Authorization header value
     * @return string|null Token or null if not found
     */
    public static function extractToken(?string $authHeader): ?string
    {
        if (!$authHeader) {
            return null;
        }

        // Format: "Bearer <token>"
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get user data from token
     * 
     * @param string $token JWT token
     * @return array|null User data or null if invalid
     */
    public static function getUserFromToken(string $token): ?array
    {
        $decoded = self::validateToken($token);

        if (!$decoded || !isset($decoded->data)) {
            return null;
        }

        return (array)$decoded->data;
    }

    /**
     * Check if token is expired
     * 
     * @param string $token JWT token
     * @return bool True if expired
     */
    public static function isTokenExpired(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key(self::getSecretKey(), 'HS256'));
            return false;
        } catch (\Firebase\JWT\ExpiredException $e) {
            return true;
        } catch (Exception $e) {
            return true;
        }
    }
}
