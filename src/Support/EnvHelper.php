<?php
namespace App\Support;

class EnvHelper
{
    private static $loaded = false;
    private static $data = [];

    /**
     * Load file .env và parse các biến
     */
    public static function load(?string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        if ($path === null) {
            $path = __DIR__ . '/../../.env';
        }

        if (!file_exists($path)) {
            // Nếu không có .env, thử load .env.example
            $path = __DIR__ . '/../../.env.example';
            if (!file_exists($path)) {
                return;
            }
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                self::$data[$key] = $value;
                
                // Cũng set vào $_ENV và putenv
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * Lấy giá trị từ .env
     */
    public static function get(string $key, $default = null)
    {
        self::load();
        return self::$data[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Lấy danh sách IP được phép từ .env
     */
    public static function getAllowedIPs(): array
    {
        $ips = self::get('ALLOWED_IPS', '127.0.0.1');
        return array_map('trim', explode(',', $ips));
    }

    /**
     * Lấy IP thực của client (xử lý proxy/load balancer)
     */
    public static function getClientIP(): string
    {
        // Thứ tự ưu tiên để lấy IP thật
        $keys = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',   // Proxy/Load balancer
            'HTTP_X_REAL_IP',         // Nginx proxy
            'REMOTE_ADDR'             // Direct connection
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // HTTP_X_FORWARDED_FOR có thể chứa nhiều IP
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0'; // Fallback
    }

    /**
     * Kiểm tra IP hiện tại có được phép không
     */
    public static function isAllowedIP(?string $ip = null): bool
    {
        if ($ip === null) {
            $ip = self::getClientIP();
        }

        $allowedIPs = self::getAllowedIPs();

        // Hỗ trợ wildcard: 192.168.1.*
        foreach ($allowedIPs as $allowedIP) {
            if ($allowedIP === '*') {
                return true; // Allow all
            }

            // Exact match
            if ($ip === $allowedIP) {
                return true;
            }

            // Wildcard match
            if (strpos($allowedIP, '*') !== false) {
                $pattern = str_replace(['.', '*'], ['\\.', '.*'], $allowedIP);
                if (preg_match("/^{$pattern}$/", $ip)) {
                    return true;
                }
            }

            // CIDR notation support (e.g., 192.168.1.0/24)
            if (strpos($allowedIP, '/') !== false) {
                if (self::ipInRange($ip, $allowedIP)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Kiểm tra IP có trong CIDR range không
     */
    private static function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);
        
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - (int)$mask);
        
        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }
}
