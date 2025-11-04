<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Repositories\UserRepository;
use App\Services\DailyStockAlertService;
use App\Services\DailyPaymentDueAlertService;
use App\Support\EnvHelper;

class AuthController extends Controller
{
    /**
     * Hàm static để chặn truy cập admin khi chưa đổi mật khẩu lần đầu
     */
    public static function requirePasswordChanged()
    {
        // Chỉ redirect nếu đã đăng nhập (có user id) và force_change_password = 1
        if (!empty($_SESSION['user']['id']) && !empty($_SESSION['user']['force_change_password']) && (int) $_SESSION['user']['force_change_password'] === 1) {
            header('Location: /admin/force-change-password');
            exit;
        }
    }
    public function showLogin()
    {
        // Kiểm tra nếu vừa đăng nhập thất bại thì không tự động đăng nhập bằng cookie
        $justFailed = $_SESSION['login_failed'] ?? false;
        unset($_SESSION['login_failed']);
        
        // Tự động đăng nhập lại nếu có cookie admin_remember và chưa có session
        // NHƯNG chỉ khi KHÔNG vừa đăng nhập thất bại
        if (!$justFailed && empty($_SESSION['user']) && !empty($_COOKIE['admin_remember'])) {
            $username = $_COOKIE['admin_remember'];
            $user = UserRepository::findByUsername($username);
            if ($user && (int) $user->force_change_password === 0) {
                $_SESSION['admin_user'] = [
                    'id' => (int) $user->id,
                    'username' => $user->username,
                    'email' => $user->email ?? null,
                    'full_name' => $user->full_name ?? null,
                    'role' => $user->role_name,
                    'avatar_url' => $user->avatar_url ?? null,
                    'force_change_password' => (int) $user->force_change_password,
                ];
                $_SESSION['user'] = $_SESSION['admin_user'];
            } else {
                // Xoá cookie ghi nhớ nếu user phải đổi mật khẩu
                setcookie('admin_remember', '', time() - 3600, '/', '', false, true);
                unset($_COOKIE['admin_remember']);
            }
        }
        // Chỉ redirect nếu đã đăng nhập (có user id) và force_change_password = 1
        if (!empty($_SESSION['user']['id']) && !empty($_SESSION['user']['force_change_password']) && (int) $_SESSION['user']['force_change_password'] === 1) {
            header('Location: /admin/force-change-password');
            exit;
        }
        // Nếu đã đăng nhập admin_user thì vào trang admin
        if (!empty($_SESSION['admin_user'])) {
            header('Location: /admin');
            exit;
        }
        // Nếu chưa đăng nhập thì cho phép vào trang login
        return $this->view('admin/auth/login');
    }

    public function login()
    {
        // Kiểm tra IP ngay từ đầu
        $clientIP = EnvHelper::getClientIP();
        if (!EnvHelper::isAllowedIP($clientIP)) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            $isJsonReq = stripos($contentType, 'application/json') !== false;
            
            $errorMsg = 'IP của bạn (' . $clientIP . ') không được phép đăng nhập. Vui lòng kết nối từ văn phòng.';
            
            if ($isJsonReq) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => $errorMsg, 'ip_blocked' => true]);
                return;
            }
            $_SESSION['flash_error'] = $errorMsg;
            header('Location: /admin/login');
            exit;
        }

        error_log("=== LOGIN ATTEMPT START ===");
        error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NONE'));
        error_log("CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'NONE'));
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];
            if ($data && empty($_POST)) {
                $_POST = $data;
            }
        } else {
            $data = $_POST;
        }

        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $remember = !empty($data['remember']);

        $errors = [];
        if ($username === '')
            $errors['username'] = 'Tài khoản không được bỏ trống';
        if ($password === '')
            $errors['password'] = 'Mật khẩu không được bỏ trống';

        $isJsonReq = stripos($contentType, 'application/json') !== false;

        if ($errors) {
            if ($isJsonReq) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => current($errors)]);
                return;
            }
            $_SESSION['errors'] = $errors;
            header('Location: /admin/login');
            exit;
        }


        $user = UserRepository::findByUsername($username);

        $fail = function (string $msg) use ($isJsonReq) {
            // Xóa session để tránh tự động đăng nhập lại khi reload
            unset($_SESSION['admin_user']);
            unset($_SESSION['user']);
            
            // Đánh dấu là vừa đăng nhập thất bại để không tự động login bằng cookie
            $_SESSION['login_failed'] = true;
            
            // KHÔNG xóa cookie ở đây vì có thể user nhập sai 1 lần
            // Cookie chỉ nên xóa khi logout
            
            if ($isJsonReq) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
            header('Location: /admin/login');
            exit;
        };


        if (!$user)
            return $fail('Tài khoản hoặc mật khẩu sai.');
        if (isset($user->is_active) && (int) $user->is_active === 0)
            return $fail('Tài khoản đã bị vô hiệu hóa.');
        if (($user->role_id ?? null) !== 2)
            return $fail('Tài khoản này không có quyền truy cập khu vực quản trị.');

        if (!password_verify($password, $user->password_hash ?? ''))
            return $fail('Tài khoản hoặc mật khẩu sai.');

        // Debug log để kiểm tra
        error_log("=== LOGIN SUCCESS ===");
        error_log("Username: " . $username);
        error_log("User ID: " . $user->id);
        error_log("Is JSON request: " . ($isJsonReq ? 'YES' : 'NO'));

        // Lấy staff_role nếu có
        $staffRole = null;
        $pdo = \App\Core\DB::pdo();
        $stmt = $pdo->prepare("SELECT staff_role FROM staff_profiles WHERE user_id = ?");
        $stmt->execute([$user->id]);
        $staffRole = $stmt->fetchColumn();

        // Lưu session cả cho admin_user và user
        $sessData = [
            'id' => (int) $user->id,
            'username' => $user->username,
            'email' => $user->email ?? null,
            'full_name' => $user->full_name ?? null,
            'role_id' => (int) $user->role_id,
            'role' => $user->role_name,
            'staff_role' => $staffRole, // Thêm staff_role vào session
            'avatar_url' => $user->avatar_url ?? null,
            'date_of_birth' => $user->date_of_birth ?? null,
            'phone' => $user->phone ?? null,
            'gender' => $user->gender ?? null,
            // force_change_password sẽ được lấy từ DB, nếu có
            'force_change_password' => isset($user->force_change_password) ? (int) $user->force_change_password : 0,
        ];
        $_SESSION['admin_user'] = $sessData;
        $_SESSION['user'] = $sessData; // để CategoryController dùng
        
        // Xóa flag login_failed khi đăng nhập thành công
        unset($_SESSION['login_failed']);

        // Tự động reset thông báo khi đăng nhập (chỉ 1 lần/ngày)
        $lastRun = $_SESSION['last_stock_check'] ?? null;
        $today = date('Y-m-d');

        if ($lastRun !== $today) {
            try {
                // Kiểm tra tồn kho thấp
                DailyStockAlertService::runDailyCheck();
                
                // Kiểm tra phiếu nhập sắp đến hạn thanh toán
                DailyPaymentDueAlertService::runDailyCheck();
                
                $_SESSION['last_stock_check'] = $today;
            } catch (\Exception $e) {
                // Bỏ qua lỗi, không ảnh hưởng đăng nhập
                error_log("Alert services error: " . $e->getMessage());
            }
        }

        if ($remember) {
            setcookie('admin_remember', $user->username, time() + 60 * 60 * 24 * 30, '/', '', false, true);
        }

        // Nếu user cần đổi mật khẩu lần đầu, chuyển hướng sang trang đổi mật khẩu
        if (!empty($sessData['force_change_password'])) {
            $_SESSION['first_time_password_change'] = 'Vui lòng đặt mật khẩu mới cho lần đăng nhập đầu tiên';
            if ($isJsonReq) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'force_change_password' => true, 'redirect' => '/admin/force-change-password']);
                return;
            }
            header('Location: /admin/force-change-password');
            exit;
        }

        // Lưu thông báo đăng nhập thành công
        $_SESSION['login_success'] = 'Đăng nhập thành công!';

        if ($isJsonReq) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            return;
        }
        header('Location: /admin');
        exit;
    }

    public function profile()
    {
        // render view profile
        return $this->view('admin/profile/profile');
    }

    public function logout()
    {
        // Debug log
        error_log("=== LOGOUT CALLED ===");
        error_log("Session before logout: " . json_encode($_SESSION));
        
        // Xóa session
        unset($_SESSION['admin_user']);
        unset($_SESSION['user']);
        unset($_SESSION['last_stock_check']);
        
        // Xóa cookie remember nếu có
        if (isset($_COOKIE['admin_remember'])) {
            setcookie('admin_remember', '', time() - 3600, '/', '', false, true);
        }
        
        // Debug log
        error_log("Session after logout: " . json_encode($_SESSION));
        error_log("Redirecting to /admin/login");
        
        // Redirect về trang login
        header('Location: /admin/login');
        exit;
    }

    // Xử lý upload avatar
    public function uploadAvatar()
    {
        if (empty($_SESSION['user']['id']) && empty($_SESSION['admin_user']['id'])) {
            header('Location: /admin/login');
            exit;
        }
        $currentUserId = $_POST['user_id'] ?? ($_SESSION['user']['id'] ?? ($_SESSION['admin_user']['id'] ?? null));
        if (!$currentUserId || !isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            header('Location: /admin/profile/profile?tab=info&error=upload');
            exit;
        }
        $targetDir = __DIR__ . '/../../../public/assets/images/avatar/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $targetFile = $targetDir . $currentUserId . '.png';
        $tmpFile = $_FILES['avatar']['tmp_name'];
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        $fileType = mime_content_type($tmpFile);
        if (!in_array($fileType, $allowedTypes)) {
            header('Location: /admin/profile/profile?tab=info&error=type');
            exit;
        }
        // Xóa file cũ nếu tồn tại
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }
        if ($fileType !== 'image/png') {
            $image = imagecreatefromstring(file_get_contents($tmpFile));
            if ($image === false) {
                header('Location: /admin/profile/profile?tab=info&error=convert');
                exit;
            }
            imagepng($image, $targetFile);
            imagedestroy($image);
        } else {
            move_uploaded_file($tmpFile, $targetFile);
        }
        // Cập nhật DB
        $avatarPath = '' . $currentUserId . '.png';
        UserRepository::updateAvatar($currentUserId, $avatarPath);
        // Cập nhật lại session
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['avatar_url'] = $avatarPath;
        }
        if (isset($_SESSION['admin_user'])) {
            $_SESSION['admin_user']['avatar_url'] = $avatarPath;
        }
        header('Location: /admin/profile?tab=info&avatar-updated=1');
        exit;

    }

    // Xử lý cập nhật thông tin cá nhân
    public function updateProfile()
    {
        if (empty($_SESSION['user']['id']) && empty($_SESSION['admin_user']['id'])) {
            header('Location: /admin/login');
            exit;
        }
        $currentUserId = $_POST['user_id'] ?? ($_SESSION['user']['id'] ?? ($_SESSION['admin_user']['id'] ?? null));
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $date_of_birth = trim($_POST['date_of_birth'] ?? '');

        // Validate số điện thoại: không rỗng, chỉ số, 10 số, bắt đầu bằng 0
        if ($phone === '' || !preg_match('/^0\d{9}$/', $phone)) {
            $_SESSION['flash_error'] = 'Số điện thoại không hợp lệ. Số điện thoại phải gồm 10 số và bắt đầu bằng 0.';
            header('Location: /admin/profile?tab=info');
            exit;
        }

        // Chuyển đổi ngày sinh về dạng Y-m-d (ưu tiên dd/mm/yyyy, nếu yyyy-mm-dd thì giữ nguyên)
        if ($date_of_birth) {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date_of_birth, $m)) {
                // dd/mm/yyyy => yyyy-mm-dd
                $date_of_birth = $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
                // Nếu không đúng định dạng yyyy-mm-dd thì để rỗng
                $date_of_birth = '';
            }
        }
        UserRepository::updateProfile($currentUserId, $fullname, $email, $phone, $gender, $date_of_birth);
        // Cập nhật lại session
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['full_name'] = $fullname;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['gender'] = $gender;
            $_SESSION['user']['date_of_birth'] = $date_of_birth;
        }
        if (isset($_SESSION['admin_user'])) {
            $_SESSION['admin_user']['full_name'] = $fullname;
            $_SESSION['admin_user']['email'] = $email;
            $_SESSION['admin_user']['phone'] = $phone;
            $_SESSION['admin_user']['gender'] = $gender;
            $_SESSION['admin_user']['date_of_birth'] = $date_of_birth;
        }
        // Đặt session để hiển thị toast thành công
        $_SESSION['profile_success'] = 'Cập nhật thông tin thành công!';
        header('Location: /admin/profile?tab=info');
        exit;
    }

    // Xử lý đổi mật khẩu
    public function changePassword()
    {
        if (empty($_SESSION['user']['id']) && empty($_SESSION['admin_user']['id'])) {
            header('Location: /admin/login');
            exit;
        }
        $currentUserId = $_POST['user_id'] ?? ($_SESSION['user']['id'] ?? ($_SESSION['admin_user']['id'] ?? null));
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($newPassword !== $confirmPassword) {
            header('Location: /admin/profile?tab=password&error=confirm');
            exit;
        }
        $user = UserRepository::findById($currentUserId);
        if (!$user || !password_verify($oldPassword, $user->password_hash)) {
            header('Location: /admin/profile?tab=password&error=old');
            exit;
        }
        // Check if new password is the same as old password
        if (password_verify($newPassword, $user->password_hash)) {
            header('Location: /admin/profile?tab=password&error=same');
            exit;
        }
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        UserRepository::updatePassword($currentUserId, $newHash);
        header('Location: /admin/profile?tab=password&success=1');
        exit;
    }
}
