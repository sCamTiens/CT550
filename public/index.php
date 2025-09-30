<?php
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Router;
use App\Core\Request;

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\ProductController;
use App\Controllers\CartController;
use App\Controllers\Admin\DashboardController as AdminDashboard;
use App\Controllers\Admin\ProductController as AdminProduct;
use App\Controllers\Admin\BrandController as AdminBrand;
use App\Controllers\Admin\AuthController as AdminController;
use App\Controllers\Admin\CategoryController as AdminCategory;


/* --- load biến môi trường từ .env (đặt ở thư mục gốc dự án) --- */
Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

/* --- (tuỳ chọn) bật session sớm cho các flow đăng nhập --- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* --- khởi tạo router & khai báo routes --- */
$router = new Router();

/* routes người dùng */
$router->get('/',                [HomeController::class, 'index']);
$router->get('/login',           [AuthController::class, 'showLogin']);
$router->post('/login',          [AuthController::class, 'login']);
$router->get('/logout',          [AuthController::class, 'logout']);

$router->get('/products',        [ProductController::class, 'index']);
$router->get('/products/{slug}', [ProductController::class, 'show']);
$router->post('/cart',           [CartController::class, 'add']);

/* routes admin */
$router->group('/admin', function (Router $r): void {
    $r->get('/',                 [AdminDashboard::class, 'index']);
    $r->get('/login',           [AdminController::class, 'showLogin']);
    $r->post('/login',          [AdminController::class, 'login']);
    $r->get('/logout',          [AdminController::class, 'logout']);

    // Products
    $r->get('/products',         [AdminProduct::class, 'index']);
    $r->get('/products/create',  [AdminProduct::class, 'create']);
    $r->post('/products',        [AdminProduct::class, 'store']);

    // Categories
    $r->get (uri: '/categories',             action: [AdminCategory::class, 'index']);
    $r->get (uri: '/api/categories',         action: [AdminCategory::class, 'apiIndex']);
    $r->post(uri: '/categories',             action: [AdminCategory::class, 'store']);

    // Brands
    $r->get (uri: '/brands',             action: [AdminBrand::class, 'index']);
    $r->get (uri: '/api/brands',         action: [AdminBrand::class, 'apiIndex']);
    $r->post(uri: '/brands',             action: [AdminBrand::class, 'store']);
    $r->post(uri: '/brands/{id}',        action: [AdminBrand::class, 'update']);
    $r->post(uri: '/brands/{id}/delete', action: [AdminBrand::class, 'destroy']);
});


/* --- chạy router --- */
$router->dispatch(Request::capture());
