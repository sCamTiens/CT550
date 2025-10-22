<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

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
use App\Controllers\Admin\SupplierController as AdminSupplier;
use App\Controllers\Admin\UnitController as AdminUnit;
use App\Controllers\Admin\StockController as AdminStock;
use App\Controllers\Admin\StocktakeController as AdminStocktake;
use App\Controllers\Admin\StaffController as AdminStaff;
use App\Controllers\Admin\CustomerController as AdminCustomer;
use App\Controllers\Admin\ProductBatchController as AdminProductBatch;
use App\Controllers\Admin\PurchaseOrderController as AdminPurchaseOrder;
use App\Controllers\Admin\ExpenseVoucherController as AdminExpenseVoucher;
use App\Controllers\Admin\ReceiptVoucherController as AdminReceiptVoucher;
use App\Controllers\Admin\OrderController as AdminOrder;
use App\Controllers\Admin\StockOutController as AdminStockOut;
use App\Controllers\Admin\CouponController as AdminCoupon;
use App\Controllers\Admin\PromotionController as AdminPromotion;
use App\Controllers\Admin\AuditLogController as AdminAuditLog;
use App\Controllers\Admin\NotificationController as AdminNotification;
use App\Controllers\Admin\StockAlertController as AdminStockAlert;


/* --- load biến môi trường từ .env (đặt ở thư mục gốc dự án) --- */
Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

/* --- (tuỳ chọn) bật session sớm cho các flow đăng nhập --- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* --- khởi tạo router & khai báo routes --- */
$router = new Router();

/* routes người dùng */
$router->get('/', [HomeController::class, 'index']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/{slug}', [ProductController::class, 'show']);
$router->post('/cart', [CartController::class, 'add']);

/* routes admin */
$router->group('/admin', function (Router $r): void {
    // Force change password (bắt buộc đổi mật khẩu lần đầu)
    $r->get('/force-change-password', [\App\Controllers\Admin\ForceChangePasswordController::class, 'show']);
    $r->post('/force-change-password', [\App\Controllers\Admin\ForceChangePasswordController::class, 'update']);
    $r->get('/logout-force', [\App\Controllers\Admin\ForceChangePasswordController::class, 'logoutForce']);
    $r->get('/', [AdminDashboard::class, 'index']);
    $r->get('/api/dashboard/revenue-expense', [AdminDashboard::class, 'apiRevenueExpense']);
    $r->get('/login', [AdminController::class, 'showLogin']);
    $r->post('/login', [AdminController::class, 'login']);
    $r->get('/logout', [AdminController::class, 'logout']);


    // Profile
    $r->get('/profile', [AdminController::class, 'profile']);
    $r->post('/profile/upload-avatar', [AdminController::class, 'uploadAvatar']);
    $r->post('/profile/update-profile', [AdminController::class, 'updateProfile']);
    $r->post('/profile/change-password', [AdminController::class, 'changePassword']);

    // Products
    $r->get('/products', [AdminProduct::class, 'index']);
    $r->get('/api/products', [AdminProduct::class, 'apiIndex']);
    $r->post('/products', [AdminProduct::class, 'store']);
    $r->put('/products/{id}', [AdminProduct::class, 'update']);
    $r->delete('/products/{id}', [AdminProduct::class, 'destroy']);


    // Categories
    $r->get('/categories', [AdminCategory::class, 'index']);
    $r->get('/api/categories', [AdminCategory::class, 'apiIndex']);
    $r->post('/categories', [AdminCategory::class, 'store']);
    $r->put('/categories/{id}', [AdminCategory::class, 'update']);
    $r->delete('/categories/{id}', [AdminCategory::class, 'destroy']);

    // Brands
    $r->get('/brands', [AdminBrand::class, 'index']);
    $r->get('/api/brands', [AdminBrand::class, 'apiIndex']);
    $r->post('/brands', [AdminBrand::class, 'store']);
    $r->put('/brands/{id}', [AdminBrand::class, 'update']);
    $r->delete('/brands/{id}', [AdminBrand::class, 'destroy']);

    // Suppliers
    $r->get('/suppliers', [AdminSupplier::class, 'index']);
    $r->get('/api/suppliers', [AdminSupplier::class, 'apiIndex']);
    $r->post('/suppliers', [AdminSupplier::class, 'store']);
    $r->put('/suppliers/{id}', [AdminSupplier::class, 'update']);
    $r->delete('/suppliers/{id}', [AdminSupplier::class, 'destroy']);

    // Units
    $r->get('/units', [AdminUnit::class, 'index']);
    $r->get('/api/units', [AdminUnit::class, 'apiIndex']);
    $r->post('/units', [AdminUnit::class, 'store']);
    $r->put('/units/{id}', [AdminUnit::class, 'update']);
    $r->delete('/units/{id}', [AdminUnit::class, 'destroy']);

    // Stocks
    $r->get('/stocks', [AdminStock::class, 'index']);
    $r->get('/api/stocks', [AdminStock::class, 'apiIndex']);
    $r->get('/stocktakes', [AdminStocktake::class, 'index']);

    // Product Batches (Inventory lots)
    $r->get('/product-batches', [AdminProductBatch::class, 'index']);
    $r->get('/api/product-batches', [AdminProductBatch::class, 'apiIndex']);
    $r->post('/api/product-batches', [AdminProductBatch::class, 'store']);
    $r->put('/api/product-batches/{id}', [AdminProductBatch::class, 'update']);
    $r->delete('/api/product-batches/{id}', [AdminProductBatch::class, 'destroy']);
    $r->post('/api/product-batches/{id}/restore', [AdminProductBatch::class, 'restore']);

    // Purchase Orders / Receipts
    $r->get('/purchase-orders', [AdminPurchaseOrder::class, 'index']);
    $r->get('/api/purchase-orders', [AdminPurchaseOrder::class, 'apiIndex']);
    $r->get('/api/purchase-orders/unpaid', [AdminPurchaseOrder::class, 'unpaid']);
    $r->get('/api/purchase-orders/{id}', [AdminPurchaseOrder::class, 'show']);
    $r->post('/api/purchase-orders', [AdminPurchaseOrder::class, 'store']);
    $r->put('/api/purchase-orders/{id}', [AdminPurchaseOrder::class, 'update']);
    $r->delete('/api/purchase-orders/{id}', [AdminPurchaseOrder::class, 'destroy']);

    // Staffs
    $r->get('/staff', [AdminStaff::class, 'index']);
    $r->get('/api/staff', [AdminStaff::class, 'apiIndex']);
    $r->get('/api/staff/roles', [AdminStaff::class, 'apiRoles']);
    $r->post('/api/staff', [AdminStaff::class, 'store']);
    $r->put('/api/staff/{id}', [AdminStaff::class, 'update']);
    $r->put('/api/staff/{id}/password', [AdminStaff::class, 'changePassword']);
    $r->delete('/api/staff/{id}', [AdminStaff::class, 'delete']);

    // Customers
    $r->get('/customers', [AdminCustomer::class, 'index']);
    $r->get('/api/customers', [AdminCustomer::class, 'apiIndex']);
    $r->post('/api/customers', [AdminCustomer::class, 'store']);
    $r->put('/api/customers/{id}', [AdminCustomer::class, 'update']);
    $r->put('/api/customers/{id}/password', [AdminCustomer::class, 'changePassword']);
    $r->get('/api/customers/{id}/addresses', [AdminCustomer::class, 'getAddresses']);
    $r->delete('/api/customers/{id}', [AdminCustomer::class, 'destroy']);

    // Expense Vouchers
    $r->get('/expense_vouchers', [AdminExpenseVoucher::class, 'index']);
    $r->get('/api/expense_vouchers', [AdminExpenseVoucher::class, 'apiIndex']);
    $r->post('/api/expense_vouchers', [AdminExpenseVoucher::class, 'store']);
    $r->put('/api/expense_vouchers/{id}', [AdminExpenseVoucher::class, 'update']);
    $r->delete('/api/expense_vouchers/{id}', [AdminExpenseVoucher::class, 'destroy']);
    $r->get('/api/expense_vouchers/next-code', [AdminExpenseVoucher::class, 'nextCode']);

    // Receipt Vouchers
    $r->get('/receipt_vouchers', [AdminReceiptVoucher::class, 'index']);
    $r->get('/api/receipt_vouchers', [AdminReceiptVoucher::class, 'apiIndex']);
    $r->post('/api/receipt_vouchers', [AdminReceiptVoucher::class, 'store']);
    $r->put('/api/receipt_vouchers/{id}', [AdminReceiptVoucher::class, 'update']);
    $r->delete('/api/receipt_vouchers/{id}', [AdminReceiptVoucher::class, 'destroy']);
    $r->get('/api/receipt_vouchers/next-code', [AdminReceiptVoucher::class, 'nextCode']);

    // Orders (Quản lý bán hàng)
    $r->get('/orders', [AdminOrder::class, 'index']);
    $r->get('/api/orders', [AdminOrder::class, 'apiIndex']);
    $r->get('/api/orders/next-code', [AdminOrder::class, 'nextCode']);
    $r->get('/api/orders/unpaid', [AdminOrder::class, 'unpaid']);
    $r->get('/api/orders/{id}/items', [AdminOrder::class, 'getItems']);
    $r->get('/orders/{id}/print', [AdminOrder::class, 'print']);
    $r->post('/orders', [AdminOrder::class, 'store']);
    $r->delete('/orders/{id}', [AdminOrder::class, 'destroy']);

    // Stock Outs (Phiếu xuất kho)
    $r->get('/stock-outs', [AdminStockOut::class, 'index']);
    $r->get('/api/stock-outs', [AdminStockOut::class, 'apiIndex']);
    $r->post('/api/stock-outs', [AdminStockOut::class, 'store']);
    $r->get('/api/stock-outs/next-code', [AdminStockOut::class, 'nextCode']);
    $r->get('/api/stock-outs/pending', [AdminStockOut::class, 'pending']);
    $r->get('/api/stock-outs/{id}/items', [AdminStockOut::class, 'getItems']);
    $r->post('/api/stock-outs/{id}/approve', [AdminStockOut::class, 'approve']);
    $r->post('/api/stock-outs/{id}/complete', [AdminStockOut::class, 'complete']);
    $r->put('/api/stock-outs/{id}', [AdminStockOut::class, 'update']);
    $r->delete('/api/stock-outs/{id}', [AdminStockOut::class, 'destroy']);

    // Coupons (Mã giảm giá)
    $r->get('/coupons', [AdminCoupon::class, 'index']);
    $r->get('/api/coupons', [AdminCoupon::class, 'apiIndex']);
    $r->post('/coupons', [AdminCoupon::class, 'store']);
    $r->put('/coupons/{id}', [AdminCoupon::class, 'update']);
    $r->delete('/coupons/{id}', [AdminCoupon::class, 'destroy']);

    // Promotions (Chương trình khuyến mãi)
    $r->get('/promotions', [AdminPromotion::class, 'index']);
    $r->get('/api/promotions', [AdminPromotion::class, 'apiIndex']);
    $r->post('/promotions', [AdminPromotion::class, 'store']);
    $r->put('/promotions/{id}', [AdminPromotion::class, 'update']);
    $r->delete('/promotions/{id}', [AdminPromotion::class, 'destroy']);

    // Audit Logs (Lịch sử thao tác)
    $r->get('/audit-logs', [AdminAuditLog::class, 'index']);
    $r->get('/api/audit-logs', [AdminAuditLog::class, 'apiIndex']);
    $r->get('/api/audit-logs/entity/{type}/{id}', [AdminAuditLog::class, 'apiGetByEntity']);
    $r->get('/api/audit-logs/stats/action', [AdminAuditLog::class, 'apiStatsByAction']);
    $r->get('/api/audit-logs/stats/entity', [AdminAuditLog::class, 'apiStatsByEntity']);
    $r->get('/api/audit-logs/stats/user', [AdminAuditLog::class, 'apiStatsByUser']);

    // Notifications (Thông báo)
    $r->get('/api/notifications', [AdminNotification::class, 'index']);
    $r->get('/api/notifications/unread-count', [AdminNotification::class, 'unreadCount']);
    $r->post('/api/notifications/{id}/read', [AdminNotification::class, 'markAsRead']);
    $r->post('/api/notifications/read-all', [AdminNotification::class, 'markAllAsRead']);
    $r->delete('/api/notifications/{id}', [AdminNotification::class, 'delete']);

    // Stock Alerts (Cảnh báo tồn kho tự động)
    $r->get('/stock-alerts', [AdminStockAlert::class, 'index']);
    $r->post('/api/stock-alerts/run-check', [AdminStockAlert::class, 'runCheck']);
    $r->get('/api/stock-alerts/stats', [AdminStockAlert::class, 'stats']);
    $r->post('/api/stock-alerts/cleanup', [AdminStockAlert::class, 'cleanup']);
});

/* --- chạy router --- */
$router->dispatch(Request::capture());
