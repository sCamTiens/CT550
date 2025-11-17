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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Kiểm tra đăng nhập
        if (empty($_SESSION['customer'])) {
            header('Location: /login');
            exit;
        }

        $this->profileRepo = new ProfileRepository();
        $this->orderRepo = new OrderRepository();
        $this->loyaltyRepo = new LoyaltyRepository();
    }

    /**
     * GET /profile - Hiển thị trang thông tin cá nhân (có tab orders và loyalty)
     */
    public function index(): mixed
    {
        $customerId = $_SESSION['customer']['id'];
        
        // Lấy thông tin customer từ repository
        $customer = $this->profileRepo->findById($customerId);
        
        if (!$customer) {
            $_SESSION['flash_error'] = 'Không tìm thấy thông tin khách hàng';
            header('Location: /');
            exit;
        }
        
        // Cập nhật session
        $_SESSION['customer'] = $customer;
        
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
        $customerId = $_SESSION['customer']['id'];
        
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
    public function apiLoyaltyTransactions(): mixed
    {
        header('Content-Type: application/json');
        
        $customerId = $_SESSION['customer']['id'];
        
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
    public function apiOrders(): mixed
    {
        header('Content-Type: application/json');
        
        $customerId = $_SESSION['customer']['id'];
        
        try {
            $orders = $this->orderRepo->getOrders($customerId);
            $totalOrders = $this->orderRepo->countOrders($customerId);
            
            echo json_encode([
                'success' => true,
                'data' => $orders,
                'total' => $totalOrders
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tải dữ liệu'
            ]);
        }
        
        exit;
    }
}
