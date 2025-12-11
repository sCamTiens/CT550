<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Core\DB;
use App\Core\Config;
use Firebase\JWT\JWT;

class GoogleAuthController extends Controller
{
    /**
     * Handle Google OAuth callback
     * POST /api/customer/google-login
     */
    public function googleLogin(Request $req): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $credential = $input['credential'] ?? '';

            if (empty($credential)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu credential']);
                exit;
            }

            // Verify Google ID token
            $googleClientId = Config::get('GOOGLE_CLIENT_ID');
            $userData = $this->verifyGoogleToken($credential, $googleClientId);

            if (!$userData) {
                echo json_encode(['success' => false, 'message' => 'Token Google không hợp lệ']);
                exit;
            }

            // Debug: Log Google user data
            error_log("Google user data: " . json_encode($userData));

            // Check if user exists by email (regardless of role)
            $pdo = DB::pdo();

            $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u 
                                   LEFT JOIN roles r ON u.role_id = r.id 
                                   WHERE u.email = ? LIMIT 1");
            $stmt->execute([$userData['email']]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                // User exists - check role
                if ($user['role_name'] !== 'Khách hàng') {
                    // User is admin or other role - block login
                    echo json_encode([
                        'success' => false,
                        'message' => 'Email này thuộc tài khoản quản trị. Vui lòng dùng email khác.'
                    ]);
                    exit;
                }

                // User is customer - update Google ID and avatar URL
                $googleAvatarUrl = $userData['picture'] ?? null;
                $avatarUrl = null;

                // Use Google avatar URL directly (no download)
                if ($googleAvatarUrl) {
                    $avatarUrl = $this->getGoogleAvatarUrl($googleAvatarUrl);
                }

                if (empty($user['google_id'])) {
                    // Chưa có google_id - cập nhật google_id và avatar
                    $updateStmt = $pdo->prepare("UPDATE users SET google_id = ?, avatar_url = ?, updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$userData['sub'], $avatarUrl, $user['id']]);
                    $user['google_id'] = $userData['sub'];
                    $user['avatar_url'] = $avatarUrl;
                } elseif ($avatarUrl) {
                    // Đã có google_id - chỉ cập nhật avatar nếu có URL
                    $updateStmt = $pdo->prepare("UPDATE users SET avatar_url = ?, updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$avatarUrl, $user['id']]);
                    $user['avatar_url'] = $avatarUrl;
                }
            } else {
                // User doesn't exist - create new customer account

                // Get customer role_id
                $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'Khách hàng' LIMIT 1");
                $roleStmt->execute();
                $customerRole = $roleStmt->fetch(\PDO::FETCH_ASSOC);
                $customerRoleId = $customerRole ? $customerRole['id'] : 1; // Default to 1 if not found

                $username = $this->generateUniqueUsername($userData['email']);

                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, email, full_name, google_id, avatar_url, role_id, created_at, updated_at, force_change_password)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), FALSE)
                ");
                $insertStmt->execute([
                    $username,
                    $userData['email'],
                    $userData['name'] ?? '',
                    $userData['sub'],
                    null, // Tạm thời NULL
                    $customerRoleId
                ]);

                $userId = $pdo->lastInsertId();

                // Get Google avatar URL (no download)
                $googleAvatarUrl = $userData['picture'] ?? null;
                if ($googleAvatarUrl) {
                    $avatarUrl = $this->getGoogleAvatarUrl($googleAvatarUrl);
                    $updateStmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                    $updateStmt->execute([$avatarUrl, $userId]);
                }

                // Fetch newly created user
                $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u 
                                       LEFT JOIN roles r ON u.role_id = r.id 
                                       WHERE u.id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            }

            // Generate JWT tokens TRƯỚC KHI lưu vào session
            $accessToken = $this->generateAccessToken($user);
            $refreshToken = $this->generateRefreshToken($user);

            // Lưu thông tin vào session (bao gồm tokens)
            $_SESSION['customer'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'phone' => $user['phone'] ?? null,
                'avatar_url' => $user['avatar_url'] ?? null,
                'google_id' => $user['google_id'] ?? null,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ];

            // Load giỏ hàng từ database
            $cartRepo = new \App\Models\Customer\Repositories\CartRepository();
            $cartFromDB = $cartRepo->loadCartFromDB($user['id']);

            // Merge với giỏ hàng session (nếu có)
            if (!empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $productId => $item) {
                    if (isset($cartFromDB[$productId])) {
                        $cartFromDB[$productId]['qty'] += $item['qty'];
                    } else {
                        $cartFromDB[$productId] = $item;
                    }
                }
                $cartRepo->saveCartToDB($user['id'], $cartFromDB);
            }

            $_SESSION['cart'] = $cartFromDB;

            echo json_encode([
                'success' => true,
                'message' => 'Đăng nhập Google thành công',
                'redirect' => '/'
            ]);
        } catch (\Exception $e) {
            error_log("Google login error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Handle Google OAuth2 callback (from account chooser)
     * POST /api/customer/google-login-oauth
     */
    public function googleLoginOAuth(Request $req): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // OAuth2 returns user info directly
            $email = $input['email'] ?? '';
            $name = $input['name'] ?? '';
            $googleId = $input['sub'] ?? '';
            $picture = $input['picture'] ?? null;

            // Debug: Log input data
            error_log("Google OAuth input: " . json_encode([
                'email' => $email,
                'name' => $name,
                'googleId' => $googleId,
                'picture' => $picture
            ]));

            if (empty($email) || empty($googleId)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin từ Google']);
                exit;
            }

            // Check if user exists by email (regardless of role)
            $pdo = DB::pdo();

            $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u 
                                   LEFT JOIN roles r ON u.role_id = r.id 
                                   WHERE u.email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                // User exists - check role
                if ($user['role_name'] !== 'Khách hàng') {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Email này thuộc tài khoản quản trị. Vui lòng dùng email khác.'
                    ]);
                    exit;
                }

                // User is customer - update Google ID and avatar URL
                $googleAvatarUrl = $input['picture'] ?? null;
                $avatarUrl = null;

                // Use Google avatar URL directly (no download)
                if ($googleAvatarUrl) {
                    $avatarUrl = $this->getGoogleAvatarUrl($googleAvatarUrl);
                }

                if (empty($user['google_id'])) {
                    // Chưa có google_id - cập nhật google_id và avatar
                    $updateStmt = $pdo->prepare("UPDATE users SET google_id = ?, avatar_url = ?, updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$googleId, $avatarUrl, $user['id']]);
                    $user['google_id'] = $googleId;
                    $user['avatar_url'] = $avatarUrl;
                } elseif ($avatarUrl) {
                    // Đã có google_id - chỉ cập nhật avatar nếu có URL
                    $updateStmt = $pdo->prepare("UPDATE users SET avatar_url = ?, updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$avatarUrl, $user['id']]);
                    $user['avatar_url'] = $avatarUrl;
                }
            } else {
                // User doesn't exist - create new customer account

                // Get customer role_id
                $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'customer' LIMIT 1");
                $roleStmt->execute();
                $customerRole = $roleStmt->fetch(\PDO::FETCH_ASSOC);
                $customerRoleId = $customerRole ? $customerRole['id'] : 1;

                $username = $this->generateUniqueUsername($email);

                // Download avatar từ Google nếu có (tạm thời dùng user ID 0, sẽ cập nhật sau)
                $googleAvatarUrl = $input['picture'] ?? null;
                $localAvatarFilename = null;

                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, email, full_name, google_id, avatar_url, role_id, created_at, updated_at, force_change_password)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), FALSE)
                ");
                $insertStmt->execute([
                    $username,
                    $email,
                    $name,
                    $googleId,
                    $localAvatarFilename, // Tạm thời NULL
                    $customerRoleId
                ]);

                $userId = $pdo->lastInsertId();

                // Get Google avatar URL (no download)
                if ($googleAvatarUrl) {
                    $avatarUrl = $this->getGoogleAvatarUrl($googleAvatarUrl);
                    $updateStmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                    $updateStmt->execute([$avatarUrl, $userId]);
                }

                // Fetch newly created user
                $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u 
                                       LEFT JOIN roles r ON u.role_id = r.id 
                                       WHERE u.id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            }

            // Generate JWT tokens TRƯỚC KHI lưu vào session
            $accessToken = $this->generateAccessToken($user);
            $refreshToken = $this->generateRefreshToken($user);

            // Lưu thông tin vào session (bao gồm tokens)
            $_SESSION['customer'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'phone' => $user['phone'] ?? null,
                'avatar_url' => $user['avatar_url'] ?? null,
                'google_id' => $user['google_id'] ?? null,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ];

            // Load giỏ hàng từ database
            $cartRepo = new \App\Models\Customer\Repositories\CartRepository();
            $cartFromDB = $cartRepo->loadCartFromDB($user['id']);

            // Merge với giỏ hàng session (nếu có)
            if (!empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $productId => $item) {
                    if (isset($cartFromDB[$productId])) {
                        $cartFromDB[$productId]['qty'] += $item['qty'];
                    } else {
                        $cartFromDB[$productId] = $item;
                    }
                }
                $cartRepo->saveCartToDB($user['id'], $cartFromDB);
            }

            $_SESSION['cart'] = $cartFromDB;

            echo json_encode([
                'success' => true,
                'message' => 'Đăng nhập Google thành công',
                'redirect' => '/'
            ]);
        } catch (\Exception $e) {
            error_log("Google OAuth login error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }

        exit;
    }
    /**
     * Verify Google ID token
     */
    private function verifyGoogleToken(string $token, string $clientId): ?array
    {
        try {
            // Decode JWT without verification (for development)
            // In production, you should verify the signature
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

            // Verify audience (client ID)
            if (!isset($payload['aud']) || $payload['aud'] !== $clientId) {
                return null;
            }

            // Verify expiration
            if (!isset($payload['exp']) || $payload['exp'] < time()) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            error_log("Token verification error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate unique username from email
     */
    private function generateUniqueUsername(string $email): string
    {
        $base = explode('@', $email)[0];
        $base = preg_replace('/[^a-zA-Z0-9]/', '', $base);

        $pdo = DB::pdo();
        $username = $base;
        $counter = 1;

        while (true) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);

            if (!$stmt->fetch()) {
                break;
            }

            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Get Google avatar URL (no download, use Google CDN directly)
     */
    private function getGoogleAvatarUrl(string $googleAvatarUrl): string
    {
        // Remove size parameter to get high quality image
        // Google format: https://lh3.googleusercontent.com/...=s96-c
        // We want: https://lh3.googleusercontent.com/... (original size)
        $cleanUrl = preg_replace('/=s\d+-c$/', '', $googleAvatarUrl);

        error_log("Google avatar URL (cleaned): " . $cleanUrl);

        return $cleanUrl;
    }
    /**
     * Generate JWT access token
     */
    private function generateAccessToken(array $customer): string
    {
        $payload = [
            'iss' => 'minigo',
            'sub' => $customer['id'],
            'username' => $customer['username'],
            'email' => $customer['email'],
            'iat' => time(),
            'exp' => time() + (60 * 60) // 1 hour
        ];

        $jwtSecret = Config::get('JWT_SECRET', 'your-secret-key');
        return JWT::encode($payload, $jwtSecret, 'HS256');
    }

    /**
     * Generate JWT refresh token
     */
    private function generateRefreshToken(array $customer): string
    {
        $payload = [
            'iss' => 'minigo',
            'sub' => $customer['id'],
            'type' => 'refresh',
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 30) // 30 days
        ];

        $jwtSecret = Config::get('JWT_SECRET', 'your-secret-key');
        return JWT::encode($payload, $jwtSecret, 'HS256');
    }
}
