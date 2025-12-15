<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Models\Customer\Repositories\CustomerRepository;
use App\Models\Repositories\PasswordResetRepository;
use App\Services\EmailService;
use App\Support\JWTHelper;

class AuthController extends Controller
{
    private $customerRepo;
    private $passwordResetRepo;
    private $emailService;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->customerRepo = new CustomerRepository();
        $this->passwordResetRepo = new PasswordResetRepository();
        $this->emailService = new EmailService();
    }

    /**
     * Hiển thị trang đăng nhập
     */
    public function loginPage()
    {
        // Nếu đã đăng nhập rồi thì chuyển về trang chủ
        if (!empty($_SESSION['customer'])) {
            $this->redirect('/');
        }

        require_once __DIR__ . '/../../views/customer/auth/login.php';
    }

    /**
     * Xử lý đăng nhập
     */
    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($username) || empty($password)) {
            $this->json(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'], 400);
            return;
        }

        // Tìm khách hàng theo username hoặc email
        $customer =  $this->customerRepo->findByUsernameOrEmail($username);

        if (!$customer) {
            $this->json(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không chính xác'], 401);
            return;
        }

        // DEBUG: Log role info
        error_log("Login attempt - Username: {$username}, Role: " . ($customer['role_name'] ?? 'NULL'));

        // Kiểm tra role - nếu không phải khách hàng thì từ chối (không lộ thông tin)
        if (isset($customer['role_name']) && $customer['role_name'] !== 'Khách hàng') {
            error_log("Blocked admin login attempt: {$username}");
            $this->json(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không chính xác'], 401);
            return;
        }

        // Kiểm tra mật khẩu
        if (!password_verify($password, $customer['password_hash'])) {
            $this->json(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không chính xác'], 401);
            return;
        }

        // Kiểm tra trạng thái tài khoản
        if (empty($customer['is_active'])) {
            $this->json(['success' => false, 'message' => 'Tài khoản đã bị khóa'], 403);
            return;
        }

        // Generate JWT tokens TRƯỚC KHI lưu vào session
        $tokenPayload = [
            'id' => $customer['id'],
            'username' => $customer['username'],
            'email' => $customer['email'],
            'role' => 'customer'
        ];

        $accessToken = JWTHelper::generateToken($tokenPayload);
        $refreshToken = JWTHelper::generateRefreshToken($tokenPayload);

        // Lưu thông tin vào session (bao gồm cả tokens)
        $_SESSION['customer'] = [
            'id' => $customer['id'],
            'username' => $customer['username'],
            'email' => $customer['email'],
            'full_name' => $customer['full_name'],
            'phone' => $customer['phone'],
            'avatar_url' => $customer['avatar_url'] ?? null,
            'loyalty_points' => $customer['loyalty_points'] ?? 0,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];

        // Load giỏ hàng từ database
        $cartRepo = new \App\Models\Customer\Repositories\CartRepository();
        $cartFromDB = $cartRepo->loadCartFromDB($customer['id']);

        // Merge với giỏ hàng session (nếu có)
        if (!empty($_SESSION['cart'])) {
            // Nếu có giỏ hàng trong session, merge với DB
            foreach ($_SESSION['cart'] as $productId => $item) {
                if (isset($cartFromDB[$productId])) {
                    // Nếu sản phẩm đã có trong DB, cộng thêm số lượng
                    $cartFromDB[$productId]['qty'] += $item['qty'];
                } else {
                    // Sản phẩm mới, thêm vào
                    $cartFromDB[$productId] = $item;
                }
            }
            // Lưu lại vào DB
            $cartRepo->saveCartToDB($customer['id'], $cartFromDB);
        }

        // Set cart vào session
        $_SESSION['cart'] = $cartFromDB;

        // Cập nhật last_login (bỏ qua nếu cột không tồn tại)
        // $this->customerRepo->updateLastLogin($customer['id']);

        // Tokens đã được generate và lưu vào session ở trên

        $this->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'redirect' => '/'
        ]);
    }

    /**
     * Hiển thị trang đăng ký
     */
    public function registerPage()
    {
        // Nếu đã đăng nhập rồi thì chuyển về trang chủ
        if (!empty($_SESSION['customer'])) {
            $this->redirect('/');
        }

        require_once __DIR__ . '/../../views/customer/auth/register.php';
    }

    /**
     * Xử lý đăng ký
     */
    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        $confirmPassword = trim($data['confirm_password'] ?? '');
        $fullName = trim($data['full_name'] ?? '');
        $phone = trim($data['phone'] ?? '');

        // Validate
        if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
            $this->json(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc'], 400);
            return;
        }

        // Validate username format
        if (strlen($username) < 3 || strlen($username) > 20) {
            $this->json(['success' => false, 'message' => 'Tên đăng nhập phải từ 3-20 ký tự'], 400);
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->json(['success' => false, 'message' => 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới'], 400);
            return;
        }

        if ($password !== $confirmPassword) {
            $this->json(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp'], 400);
            return;
        }

        if (strlen($password) < 6) {
            $this->json(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Email không đúng định dạng'], 400);
            return;
        }

        // Validate phone format (nếu có)
        if (!empty($phone) && !preg_match('/^0[0-9]{9,10}$/', $phone)) {
            $this->json(['success' => false, 'message' => 'Số điện thoại phải bắt đầu bằng 0 và có 10-11 chữ số'], 400);
            return;
        }

        // Kiểm tra username đã tồn tại
        if ($this->customerRepo->existsByUsername($username)) {
            $this->json(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'], 409);
            return;
        }

        // Kiểm tra email đã tồn tại
        if ($this->customerRepo->existsByEmail($email)) {
            $this->json(['success' => false, 'message' => 'Email đã được sử dụng'], 409);
            return;
        }

        // Kiểm tra số điện thoại (nếu có)
        if (!empty($phone) && $this->customerRepo->existsByPhone($phone)) {
            $this->json(['success' => false, 'message' => 'Số điện thoại đã được sử dụng'], 409);
            return;
        }

        // Tạo tài khoản mới
        try {
            $result = $this->customerRepo->create([
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'full_name' => $fullName,
                'phone' => $phone,
                'is_active' => 1
            ]);

            // Nếu trả về string là lỗi trùng
            if (is_string($result)) {
                $this->json(['success' => false, 'message' => $result], 409);
                return;
            }

            // Nếu trả về array là thành công
            if (is_array($result)) {
                // Generate JWT tokens TRƯỚC KHI lưu vào session
                $tokenPayload = [
                    'id' => $result['id'],
                    'username' => $result['username'],
                    'email' => $result['email'],
                    'role' => 'customer'
                ];

                $accessToken = JWTHelper::generateToken($tokenPayload);
                $refreshToken = JWTHelper::generateRefreshToken($tokenPayload);

                // Tự động đăng nhập sau khi đăng ký (bao gồm tokens)
                $_SESSION['customer'] = [
                    'id' => $result['id'],
                    'username' => $result['username'],
                    'email' => $result['email'],
                    'full_name' => $result['full_name'],
                    'phone' => $result['phone'],
                    'avatar_url' => $result['avatar_url'] ?? null,
                    'loyalty_points' => $result['loyalty_points'] ?? 0,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                ];

                $this->json([
                    'success' => true,
                    'message' => 'Đăng ký thành công',
                    'redirect' => '/'
                ]);
                return;
            }

            // Trường hợp false hoặc không xác định
            $this->json(['success' => false, 'message' => 'Không thể tạo tài khoản'], 500);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Đăng xuất
     */
    public function logout()
    {
        unset($_SESSION['customer']);
        session_destroy();

        // Check if POST/AJAX request
        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

        if ($isPost) {
            // Return JSON for POST requests
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Logged out']);
            exit;
        }

        // Redirect for GET requests
        $this->redirect('/login');
    }

    /**
     * Refresh JWT token
     */
    public function refreshToken()
    {
        // Try to get refresh token from session first
        $refreshToken = $_SESSION['customer']['refresh_token'] ?? null;

        // Fall back to request body (for API clients)
        if (!$refreshToken) {
            $data = json_decode(file_get_contents('php://input'), true);
            $refreshToken = $data['refresh_token'] ?? '';
        }

        if (empty($refreshToken)) {
            $this->json(['success' => false, 'message' => 'Refresh token required'], 400);
            return;
        }

        // Validate refresh token
        $decoded = JWTHelper::validateToken($refreshToken);

        if (!$decoded || ($decoded->type ?? '') !== 'refresh') {
            // Clear invalid session
            unset($_SESSION['customer']);
            $this->json(['success' => false, 'message' => 'Invalid refresh token'], 401);
            return;
        }

        // Generate new access token
        $userData = (array)$decoded->data;
        $newAccessToken = JWTHelper::generateToken($userData);

        // Update session with new access token
        if (!empty($_SESSION['customer'])) {
            $_SESSION['customer']['access_token'] = $newAccessToken;
        }

        $this->json([
            'success' => true,
            'access_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'expires_in' => (int)(getenv('JWT_EXPIRY') ?: 3600)
        ]);
    }

    /**
     * Gửi mã OTP để reset mật khẩu
     * POST /forgot-password
     */
    public function forgotPassword()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');

        if (empty($email)) {
            $this->json(['success' => false, 'message' => 'Vui lòng nhập email'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Email không đúng định dạng'], 400);
            return;
        }

        // Kiểm tra email có tồn tại không
        $customer = $this->customerRepo->findByEmail($email);

        if (!$customer) {
            $this->json(['success' => false, 'message' => 'Email không tồn tại trong hệ thống'], 404);
            return;
        }

        // Tạo mã OTP
        $otpCode = $this->passwordResetRepo->createOTP($email);

        // Gửi email
        $result = $this->emailService->sendOTPEmail($email, $otpCode, $customer['full_name'] ?? '');

        if ($result['success']) {
            // Tính thời gian hết hạn (10 phút từ bây giờ)
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $this->json([
                'success' => true,
                'message' => 'Mã xác nhận đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.',
                'expires_at' => $expiresAt,
                'expires_in_seconds' => 600 // 10 minutes = 600 seconds
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.'
            ], 500);
        }
    }

    /**
     * Xác thực mã OTP
     * POST /verify-otp
     */
    public function verifyOTP()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $email = trim($data['email'] ?? '');
            $otpCode = trim($data['otp_code'] ?? '');

            error_log("[AuthController] Verify OTP - Email: $email, Code: $otpCode");

            if (empty($email) || empty($otpCode)) {
                error_log("[AuthController] Verify OTP - Missing data");
                $this->json(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'], 400);
                return;
            }

            // Xác thực OTP
            $isValid = $this->passwordResetRepo->verifyOTP($email, $otpCode);

            error_log("[AuthController] Verify OTP - Result: " . ($isValid ? 'VALID' : 'INVALID'));

            if ($isValid) {
                $this->json([
                    'success' => true,
                    'message' => 'Mã xác nhận đúng. Vui lòng đặt lại mật khẩu.'
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => 'Mã xác nhận không đúng hoặc đã hết hạn'
                ], 400);
            }
        } catch (\Exception $e) {
            error_log("[AuthController] Verify OTP - Exception: " . $e->getMessage());
            error_log("[AuthController] Verify OTP - Trace: " . $e->getTraceAsString());
            $this->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đặt lại mật khẩu sau khi xác thực OTP
     * POST /reset-password
     */
    public function resetPassword()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = trim($data['email'] ?? '');
        $otpCode = trim($data['otp_code'] ?? '');
        $newPassword = trim($data['new_password'] ?? '');
        $confirmPassword = trim($data['confirm_password'] ?? '');

        if (empty($email) || empty($otpCode) || empty($newPassword)) {
            $this->json(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'], 400);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->json(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp'], 400);
            return;
        }

        // Validate password strength
        if (strlen($newPassword) < 8) {
            $this->json(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 8 ký tự'], 400);
            return;
        }

        // Check for uppercase letter
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $this->json(['success' => false, 'message' => 'Mật khẩu phải chứa ít nhất 1 chữ hoa'], 400);
            return;
        }

        // Check for lowercase letter
        if (!preg_match('/[a-z]/', $newPassword)) {
            $this->json(['success' => false, 'message' => 'Mật khẩu phải chứa ít nhất 1 chữ thường'], 400);
            return;
        }

        // Check for number
        if (!preg_match('/[0-9]/', $newPassword)) {
            $this->json(['success' => false, 'message' => 'Mật khẩu phải chứa ít nhất 1 chữ số'], 400);
            return;
        }

        // Check for special character
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\\\|`~]/', $newPassword)) {
            $this->json(['success' => false, 'message' => 'Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt'], 400);
            return;
        }

        // Xác thực OTP lần nữa
        if (!$this->passwordResetRepo->verifyOTP($email, $otpCode)) {
            $this->json([
                'success' => false,
                'message' => 'Mã xác nhận không đúng hoặc đã hết hạn'
            ], 400);
            return;
        }

        // Tìm customer theo email
        $customer = $this->customerRepo->findByEmail($email);

        if (!$customer) {
            $this->json(['success' => false, 'message' => 'Email không tồn tại'], 404);
            return;
        }

        // Cập nhật mật khẩu mới
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Cập nhật trong database
            $stmt = \App\Core\DB::pdo()->prepare("
                UPDATE users 
                SET password_hash = ?
                WHERE email = ?
            ");

            if ($stmt->execute([$hashedPassword, $email])) {
                // Đánh dấu OTP đã sử dụng
                $this->passwordResetRepo->markOTPAsUsed($email, $otpCode);

                $this->json([
                    'success' => true,
                    'message' => 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.'
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'Không thể cập nhật mật khẩu'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Debug: Kiểm tra user trong database
     */
    public function debugUser()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $username = trim($data['username'] ?? '');

        if (empty($username)) {
            $this->json(['error' => 'Username required']);
            return;
        }

        $customer = $this->customerRepo->findByUsernameOrEmail($username);

        if (!$customer) {
            $this->json(['found' => false, 'message' => 'User not found']);
            return;
        }

        // Return user data (hide password hash)
        $debugData = $customer;
        $debugData['password_hash'] = '***HIDDEN*** (length: ' . strlen($customer['password_hash'] ?? '') . ')';

        $this->json([
            'found' => true,
            'data' => $debugData,
            'keys' => array_keys($customer)
        ]);
    }
}
