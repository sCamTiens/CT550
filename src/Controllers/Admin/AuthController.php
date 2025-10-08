<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\DB;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (!empty($_SESSION['admin_user'])) {
            header('Location: /admin');
            exit;
        }
        return $this->view('admin/auth/login');
    }

    public function login()
    {
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

        $pdo = DB::pdo();
        $st = $pdo->prepare("SELECT u.*, r.name AS role_name
                             FROM users u
                             LEFT JOIN roles r ON r.id = u.role_id
                             WHERE u.username = ?
                             LIMIT 1");
        $st->execute([$username]);
        $user = $st->fetch(\PDO::FETCH_ASSOC);

        $fail = function (string $msg) use ($isJsonReq) {
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
        if (isset($user['is_active']) && (int) $user['is_active'] === 0)
            return $fail('Tài khoản đã bị vô hiệu hóa.');
        if (!in_array($user['role_name'] ?? '', ['Nhân viên', 'Admin'], true))
            return $fail('Không có quyền vào khu vực quản trị.');

        if (!password_verify($password, $user['password_hash'] ?? ''))
            return $fail('Tài khoản hoặc mật khẩu sai.');

        // Lưu session cả cho admin_user và user
        $sessData = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'] ?? null,
            'full_name' => $user['full_name'] ?? null,
            'role' => $user['role_name'],
            'avatar_url' => $user['avatar_url'] ?? null,
            'date_of_birth' => $user['date_of_birth'] ?? null,
            'phone' => $user['phone'] ?? null,
            'gender' => $user['gender'] ?? null,
        ];
        $_SESSION['admin_user'] = $sessData;
        $_SESSION['user'] = $sessData; // để CategoryController dùng

        if ($remember) {
            setcookie('admin_remember', $user['username'], time() + 60 * 60 * 24 * 30, '/', '', false, true);
        }

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
        unset($_SESSION['admin_user'], $_SESSION['user']);
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
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
        $stmt->execute([$avatarPath, $currentUserId]);
        // Cập nhật lại session
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['avatar_url'] = $avatarPath;
        }
        if (isset($_SESSION['admin_user'])) {
            $_SESSION['admin_user']['avatar_url'] = $avatarPath;
        }
        header('Location: /admin/profile?tab=info&success=avatar');
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
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ?, gender = ?, date_of_birth = ? WHERE id = ?');
        $stmt->execute([$fullname, $email, $phone, $gender, $date_of_birth, $currentUserId]);
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
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$currentUserId]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            header('Location: /admin/profile?tab=password&error=old');
            exit;
        }
        // Check if new password is the same as old password
        if (password_verify($newPassword, $user['password_hash'])) {
            header('Location: /admin/profile?tab=password&error=same');
            exit;
        }
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$newHash, $currentUserId]);
        header('Location: /admin/profile?tab=password&success=1');
        exit;
    }
}
