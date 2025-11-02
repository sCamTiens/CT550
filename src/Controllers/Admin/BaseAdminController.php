<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middlewares\RoleMiddleware;

class BaseAdminController extends Controller
{
    public function __construct()
    {
        // Nếu chưa đăng nhập admin -> về trang login
        if (empty($_SESSION['admin_user'])) {
            header('Location: /admin/login');
            exit;
        }

        // Kiểm tra quyền truy cập dựa trên staff_role
        $requestPath = $_SERVER['REQUEST_URI'];
        
        // Loại bỏ query string nếu có
        $requestPath = parse_url($requestPath, PHP_URL_PATH);
        
        // DEBUG
        $staffRole = $_SESSION['user']['staff_role'] ?? 'NONE';
        
        // Bỏ qua kiểm tra cho các trang không cần quyền đặc biệt
        $publicAdminPaths = [
            '/admin/login',
            '/admin/logout',
            '/admin/profile',
            '/admin/force-change-password',
            '/admin/logout-force',
            '/admin', // Dashboard - tất cả role đều truy cập được
        ];
        
        // Kiểm tra nếu path là exact match hoặc bắt đầu bằng public path
        $isPublicPath = false;
        foreach ($publicAdminPaths as $publicPath) {
            // Exact match cho /admin (dashboard)
            if ($requestPath === $publicPath) {
                $isPublicPath = true;
                break;
            }
            // Prefix match cho các path khác
            if ($publicPath !== '/admin' && strpos($requestPath, $publicPath) === 0) {
                $isPublicPath = true;
                break;
            }
        }
        
        // Nếu không phải public path thì kiểm tra quyền
        if (!$isPublicPath) {
            RoleMiddleware::authorize($requestPath);
        }
    }

    protected function currentUserName()
    {
        return $_SESSION['user']['full_name'] ?? 'Unknown';
    }

    /**
     * Kiểm tra xem user hiện tại có phải Admin không
     */
    protected function isAdmin(): bool
    {
        $staffRole = $_SESSION['user']['staff_role'] ?? '';
        return $staffRole === 'Admin';
    }

    /**
     * Yêu cầu quyền Admin - throw exception nếu không phải Admin
     */
    protected function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Forbidden',
                'message' => 'Bạn không có quyền thực hiện hành động này. Chỉ Admin mới được phép.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
