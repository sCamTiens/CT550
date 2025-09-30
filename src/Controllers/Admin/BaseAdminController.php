<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class BaseAdminController extends Controller
{
    public function __construct()
    {
        // Nếu chưa đăng nhập admin -> về trang login
        if (empty($_SESSION['admin_user'])) {
            header('Location: /admin/login');
            exit;
        }
    }
}
