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

    public function logout()
    {
        unset($_SESSION['admin_user'], $_SESSION['user']);
        header('Location: /admin/login');
        exit;
    }
}
