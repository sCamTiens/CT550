<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Repositories\StaffRepository;

class ForceChangePasswordController extends Controller
{
    public function show()
    {
        // Chỉ cho phép khi đã đăng nhập và force_change_password = 1
        if (empty($_SESSION['user']) || empty($_SESSION['user']['force_change_password']) || (int) $_SESSION['user']['force_change_password'] !== 1) {
            unset($_SESSION['user'], $_SESSION['admin_user']);
            header('Location: /admin/login');
            exit;
        }
        return $this->view('admin/auth/force_change_password');
    }

    public function update()
    {
        if (empty($_SESSION['user']) || empty($_SESSION['user']['force_change_password'])) {
            return $this->redirect('/admin/login');
        }

        $userId = $_SESSION['user']['id'];
        $password = trim($_POST['password'] ?? '');
        $password_confirm = trim($_POST['password_confirm'] ?? '');

        if ($password === '' || strlen($password) < 6) {
            $_SESSION['errors'] = ['Mật khẩu phải ít nhất 6 ký tự'];
            return $this->redirect('/admin/force-change-password');
        }

        if ($password !== $password_confirm) {
            $_SESSION['errors'] = ['Mật khẩu xác nhận không khớp'];
            return $this->redirect('/admin/force-change-password');
        }

        $repo = new \App\Models\Repositories\StaffRepository();
        $repo->changePassword($userId, $password);

        // Cập nhật force_change_password = 0
        $pdo = \App\Core\DB::pdo();
        $stmt = $pdo->prepare('UPDATE users SET force_change_password = 0 WHERE id = ?');
        $stmt->execute([$userId]);

        // Đăng xuất hoàn toàn
        unset($_SESSION['user'], $_SESSION['admin_user']);

        // Gửi thông báo
        $_SESSION['flash_error'] = 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.';

        // Xóa cookie ghi nhớ (nếu có)
        if (isset($_COOKIE['admin_remember'])) {
            setcookie('admin_remember', '', time() - 3600, '/', '', false, true);
            unset($_COOKIE['admin_remember']);
        }

        header('Location: /admin/login');
        exit;
    }
    
    public function logoutForce()
    {
        // Xoá toàn bộ session
        session_unset();
        session_destroy();

        // Xoá cookie ghi nhớ (nếu có)
        if (isset($_COOKIE['admin_remember'])) {
            setcookie('admin_remember', '', time() - 3600, '/', '', false, true);
            unset($_COOKIE['admin_remember']);
        }

        // Quay lại trang login
        header('Location: /admin/login');
        exit;
    }

}
