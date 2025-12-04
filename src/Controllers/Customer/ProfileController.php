<?php

namespace App\Controllers\Customer;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Customer\Repositories\ProfileRepository;
use App\Models\Customer\Repositories\OrderRepository;
use App\Models\Customer\Repositories\LoyaltyRepository;

class ProfileController extends Controller
{
    private ProfileRepository $profileRepo;
    private OrderRepository $orderRepo;
    private LoyaltyRepository $loyaltyRepo;

    public function __construct()
    {
        $this->profileRepo = new ProfileRepository();
        $this->orderRepo = new OrderRepository();
        $this->loyaltyRepo = new LoyaltyRepository();
    }

    /**
     * GET /profile - Hiển thị trang thông tin cá nhân (có tab orders và loyalty)
     */
    public function index(?Request $req = null): mixed
    {
        // Get customer ID from JWT token (priority 1) or session (fallback)
        $customerId = $req->user['id'] ?? $_SESSION['customer']['id'] ?? null;

        if (!$customerId) {
            header('Location: /login');
            exit;
        }

        // Lấy thông tin customer từ repository
        $customer = $this->profileRepo->findById($customerId);

        if (!$customer) {
            $_SESSION['flash_error'] = 'Không tìm thấy thông tin khách hàng';
            header('Location: /');
            exit;
        }

        // DEBUG: Log profile page load
        error_log("[ProfileController->index] Customer ID: $customerId - Loading profile page");

        // DO NOT overwrite $_SESSION['customer'] here to avoid losing JWT tokens!
        // Session already has user info + tokens from login.
        // We only use $customer data from DB for displaying in the view.

        // Lấy danh sách đơn hàng
        $orders = $this->orderRepo->getOrders($customerId);
        $totalOrders = $this->orderRepo->countOrders($customerId);

        // Lấy stats điểm tích lũy
        $loyaltyStats = [
            'totalEarned' => $this->loyaltyRepo->getTotalEarned($customerId),
            'totalRedeemed' => $this->loyaltyRepo->getTotalRedeemed($customerId),
            'orderCount' => $this->loyaltyRepo->getOrderCount($customerId)
        ];

        return $this->view('customer/profile/profile', [
            'customer' => $customer,
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'loyaltyStats' => $loyaltyStats
        ]);
    }

    /**
     * POST /profile/update - Cập nhật thông tin cá nhân
     */
    public function updateProfile(Request $req): mixed
    {
        // Get customer ID from JWT
        $customerId = $req->user['id'] ?? null;
        if (!$customerId) {
            $_SESSION['flash_error'] = 'Vui lòng đăng nhập';
            header('Location: /login');
            exit;
        }

        $data = [
            'full_name' => trim($_POST['fullname'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'gender' => trim($_POST['gender'] ?? ''),
            'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
        ];

        // Validate dữ liệu
        $errors = $this->profileRepo->validateProfileData($data);

        if (!empty($errors)) {
            $_SESSION['flash_error'] = reset($errors);
            header('Location: /profile?tab=info');
            exit;
        }

        // Convert date format
        $dateConverted = $this->profileRepo->convertDateFormat($data['date_of_birth']);
        if (!$dateConverted) {
            $_SESSION['flash_error'] = 'Định dạng ngày sinh không hợp lệ';
            header('Location: /profile?tab=info');
            exit;
        }
        $data['date_of_birth'] = $dateConverted;

        // Check email trùng
        if ($this->profileRepo->emailExists($data['email'], $customerId)) {
            $_SESSION['flash_error'] = 'Email đã được sử dụng bởi tài khoản khác';
            header('Location: /profile?tab=info');
            exit;
        }

        // Update database
        if ($this->profileRepo->updateProfile($customerId, $data)) {
            // Update session
            $_SESSION['customer']['full_name'] = $data['full_name'];
            $_SESSION['customer']['email'] = $data['email'];
            $_SESSION['customer']['phone'] = $data['phone'];
            $_SESSION['customer']['gender'] = $data['gender'];
            $_SESSION['customer']['date_of_birth'] = $data['date_of_birth'];

            $_SESSION['profile_success'] = 'Cập nhật thông tin thành công!';
        } else {
            $_SESSION['flash_error'] = 'Không thể cập nhật thông tin';
        }

        header('Location: /profile?tab=info');
        exit;
    }

    /**
     * POST /profile/change-password - Đổi mật khẩu
     */
    public function changePassword(Request $req): mixed
    {
        $customerId = $_SESSION['customer']['id'];

        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate
        $errors = $this->profileRepo->validatePassword($oldPassword, $newPassword, $confirmPassword);

        if (!empty($errors)) {
            $errorKey = key($errors);
            $errorMap = [
                'old_password' => 'old',
                'new_password' => 'weak',
                'confirm_password' => 'confirm'
            ];
            $errorParam = $errorMap[$errorKey] ?? 'empty';
            header('Location: /profile?tab=password&error=' . $errorParam);
            exit;
        }

        // Get current password
        $currentPassword = $this->profileRepo->getPassword($customerId);

        if (!$currentPassword) {
            header('Location: /profile?tab=password&error=notfound');
            exit;
        }

        // Verify old password
        if (!password_verify($oldPassword, $currentPassword)) {
            header('Location: /profile?tab=password&error=old');
            exit;
        }

        // Check if new password is same as old
        if ($oldPassword === $newPassword) {
            header('Location: /profile?tab=password&error=same');
            exit;
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        if ($this->profileRepo->updatePassword($customerId, $hashedPassword)) {
            header('Location: /profile?tab=password&success=1');
        } else {
            header('Location: /profile?tab=password&error=update');
        }

        exit;
    }

    /**
     * POST /profile/upload-avatar - Upload avatar
     */
    public function uploadAvatar(Request $req): mixed
    {
        $customerId = $_SESSION['customer']['id'];

        if (!isset($_FILES['avatar'])) {
            $_SESSION['flash_error'] = 'Vui lòng chọn file ảnh';
            header('Location: /profile?tab=info');
            exit;
        }

        $file = $_FILES['avatar'];

        // Validate file
        $errors = $this->profileRepo->validateAvatarFile($file);

        if (!empty($errors)) {
            $_SESSION['flash_error'] = reset($errors);
            header('Location: /profile?tab=info');
            exit;
        }

        $uploadDir = __DIR__ . '/../../../public/assets/images/avatar/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $filename = $this->profileRepo->generateAvatarFilename($customerId, $file['name']);
        $destination = $uploadDir . $filename;

        // Delete old avatar
        $oldAvatar = $this->profileRepo->getAvatarUrl($customerId);
        if ($oldAvatar) {
            $this->profileRepo->deleteOldAvatar($oldAvatar);
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Update database
            if ($this->profileRepo->updateAvatar($customerId, $filename)) {
                // Update session
                $_SESSION['customer']['avatar_url'] = $filename;

                $_SESSION['profile_success'] = 'Cập nhật ảnh đại diện thành công!';
                header('Location: /profile?tab=info&avatar-updated=1');
            } else {
                $_SESSION['flash_error'] = 'Không thể cập nhật database';
                header('Location: /profile?tab=info');
            }
        } else {
            $_SESSION['flash_error'] = 'Không thể upload ảnh';
            header('Location: /profile?tab=info');
        }

        exit;
    }

    /**
     * GET /api/profile/loyalty/transactions - API lấy danh sách giao dịch điểm (cho tab loyalty)
     */
    public function apiLoyaltyTransactions(Request $req): mixed
    {
        header('Content-Type: application/json');

        // Get customer ID from JWT token
        $customerId = $req->user['id'] ?? null;

        if (!$customerId) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        try {
            $transactions = $this->loyaltyRepo->getTransactions($customerId);
            $stats = [
                'totalEarned' => $this->loyaltyRepo->getTotalEarned($customerId),
                'totalRedeemed' => $this->loyaltyRepo->getTotalRedeemed($customerId),
                'orderCount' => $this->loyaltyRepo->getOrderCount($customerId)
            ];

            echo json_encode([
                'success' => true,
                'data' => $transactions,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tải dữ liệu'
            ]);
        }

        exit;
    }

    /**
     * GET /api/profile/orders - API lấy danh sách đơn hàng (cho tab orders)
     */
    public function apiOrders(Request $req): mixed
    {
        header('Content-Type: application/json');

        // Debug log
        error_log("[ProfileController->apiOrders] Request user data: " . json_encode($req->user ?? 'NULL'));

        // Get customer ID from JWT token
        $customerId = $req->user['id'] ?? null;

        error_log("[ProfileController->apiOrders] Customer ID extracted: " . ($customerId ?? 'NULL'));

        if (!$customerId) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        try {
            error_log("[ProfileController->apiOrders] Customer ID: $customerId - Loading orders");
            $orders = $this->orderRepo->getOrders($customerId);
            $totalOrders = $this->orderRepo->countOrders($customerId);

            error_log("[ProfileController->apiOrders] Orders loaded successfully - Total: $totalOrders");

            echo json_encode([
                'success' => true,
                'data' => $orders,
                'total' => $totalOrders
            ]);
        } catch (\Exception $e) {
            error_log("[ProfileController->apiOrders] Error: " . $e->getMessage());
            error_log("[ProfileController->apiOrders] Stack trace: " . $e->getTraceAsString());

            echo json_encode([
                'success' => false,
                'message' => 'Không thể tải dữ liệu',
                'error' => $e->getMessage() // Add error detail for debugging
            ]);
        }

        exit;
    }

    /**
     * GET /api/profile/orders/{id} - API lấy chi tiết đơn hàng
     */
    public function apiOrderDetail(Request $req, $id): mixed
    {
        // Clear any previous output
        if (ob_get_length()) ob_clean();

        header('Content-Type: application/json; charset=utf-8');

        // Get customer ID from JWT token
        $customerId = $req->user['id'] ?? null;

        if (!$customerId) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        try {
            // Validate id
            if (!is_numeric($id) || $id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID đơn hàng không hợp lệ'
                ]);
                exit;
            }

            $order = $this->orderRepo->getOrderDetail((int)$id, $customerId);

            if (!$order) {
                // Log for debugging
                error_log("Order not found - ID: $id, Customer ID: $customerId");

                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng',
                    'debug' => [
                        'order_id' => $id,
                        'customer_id' => $customerId
                    ]
                ]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'data' => $order
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\PDOException $e) {
            error_log('Database error in apiOrderDetail: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi cơ sở dữ liệu'
            ]);
        } catch (\Exception $e) {
            error_log('Order detail error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi hệ thống'
            ]);
        }

        exit;
    }

    /**
     * POST /api/orders/{id}/cancel - Hủy đơn hàng
     */
    public function cancelOrder(Request $req, $id): mixed
    {
        header('Content-Type: application/json');

        // Get customer ID from JWT token
        $customerId = $req->user['id'] ?? null;

        if (!$customerId) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        try {
            $pdo = \App\Core\DB::pdo();

            // Get cancel reason from request
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);
            $cancelReason = $data['reason'] ?? 'Khách hàng hủy đơn';

            // Get order info
            $stmt = $pdo->prepare("
                SELECT o.*, oi.product_id, oi.qty
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.id = ? AND o.user_id = ?
            ");
            $stmt->execute([(int)$id, $customerId]);
            $orderData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($orderData)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng'
                ]);
                exit;
            }

            $order = $orderData[0];

            // Chỉ cho phép hủy đơn ở trạng thái 'Chờ xử lý'
            if ($order['status'] !== 'Chờ xử lý') {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Chỉ có thể hủy đơn hàng ở trạng thái "Chờ xử lý"'
                ]);
                exit;
            }

            $pdo->beginTransaction();

            try {
                // Update order status to 'Đã hủy'
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'Đã hủy',
                        note = CONCAT(COALESCE(note, ''), '\n[Lý do hủy]: ', ?),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$cancelReason, (int)$id]);

                // Cộng lại số lượng vào kho
                foreach ($orderData as $item) {
                    if ($item['product_id'] && $item['qty']) {
                        $stmtStock = $pdo->prepare("
                            UPDATE stocks 
                            SET qty = qty + ?, 
                                updated_at = NOW() 
                            WHERE product_id = ?
                        ");
                        $stmtStock->execute([$item['qty'], $item['product_id']]);
                    }
                }

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Đã hủy đơn hàng thành công'
                ]);
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log('[ProfileController->cancelOrder] Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }

        exit;
    }
}
