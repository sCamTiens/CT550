<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Router;
use App\Core\Request;
use App\Support\EnvHelper;

// Load .env file
EnvHelper::load(__DIR__ . '/../.env');

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\Customer\ProductController;
use App\Controllers\Customer\CartController;
use App\Controllers\Customer\PromotionController;
use App\Controllers\Customer\AuthController as CustomerAuth;
use App\Controllers\Customer\ProfileController as CustomerProfile;
use App\Controllers\Customer\AddressController as CustomerAddress;
use App\Controllers\Customer\CheckoutController as CustomerCheckout;
use App\Controllers\Customer\GoogleAuthController as CustomerGoogleAuth;
use App\Controllers\Customer\PaymentController as CustomerPayment;
use App\Controllers\Customer\VNPayController as CustomerVNPay;
use App\Controllers\Customer\ZaloPayController as CustomerZaloPay;
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
use App\Controllers\Admin\PaymentDueAlertController as AdminPaymentDueAlert;
use App\Controllers\Admin\AdminForgotPasswordController;
use App\Controllers\Admin\ExpiryAlertController as AdminExpiryAlert;
use App\Controllers\Admin\ReportsController as AdminReports;
use App\Controllers\Admin\ImportHistoryController as AdminImportHistory;
use App\Controllers\Admin\ScheduleController as AdminSchedule;
use App\Controllers\Admin\AttendanceController as AdminAttendance;
use App\Controllers\Admin\PayrollController as AdminPayroll;
use App\Controllers\Admin\ChatSupportController as AdminChatSupport;
use App\Controllers\Api\ChatController as ApiChat;


/* --- load biến môi trường từ .env (đặt ở thư mục gốc dự án) --- */

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

/* --- (tuỳ chọn) bật session sớm cho các flow đăng nhập --- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* --- khởi tạo router & khai báo routes --- */
$router = new Router();

/* routes người dùng (khách hàng) */
$router->get('/', [HomeController::class, 'index']);

// Search route
$router->get('/search', [\App\Controllers\Customer\SearchController::class, 'index']);

// Customer Auth Routes
$router->get('/login', [CustomerAuth::class, 'loginPage']);
$router->post('/api/customer/login', [CustomerAuth::class, 'login']);
$router->post('/api/customer/refresh-token', [CustomerAuth::class, 'refreshToken']);
$router->get('/register', [CustomerAuth::class, 'registerPage']);
$router->post('/api/customer/register', [CustomerAuth::class, 'register']);
$router->get('/logout', [CustomerAuth::class, 'logout']);
$router->post('/api/customer/debug-user', [CustomerAuth::class, 'debugUser']); // Debug endpoint

// Forgot Password Routes
$router->post('/api/customer/forgot-password', [CustomerAuth::class, 'forgotPassword']);
$router->post('/api/customer/verify-otp', [CustomerAuth::class, 'verifyOTP']);
$router->post('/api/customer/reset-password', [CustomerAuth::class, 'resetPassword']);

$router->get('/api/debug-session', function () {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    header('Content-Type: application/json');
    echo json_encode([
        'session_id' => session_id(),
        'session_data' => $_SESSION ?? [],
        'has_customer' => isset($_SESSION['customer']),
        'has_token' => isset($_SESSION['customer']['access_token']) ?? false,
        'cookie' => $_COOKIE
    ]);
    exit;
});
$router->post('/api/customer/google-login', [CustomerGoogleAuth::class, 'googleLogin']); // Google OAuth (ID token)
$router->post('/api/customer/google-login-oauth', [CustomerGoogleAuth::class, 'googleLoginOAuth']); // Google OAuth2 (access token)

// Notification API
$router->get('/api/customer/notifications', [\App\Controllers\Customer\NotificationController::class, 'apiIndex']);
$router->post('/api/customer/notifications/read', [\App\Controllers\Customer\NotificationController::class, 'markRead']);

// Old routes (for backward compatibility)
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/{slug}', [ProductController::class, 'show']);

// Cart routes
$router->get('/cart', [CartController::class, 'index']); // No middleware - controller handles auth
$router->post('/cart/add', [CartController::class, 'add'])->middleware('jwt');
$router->post('/cart/update', [CartController::class, 'update'])->middleware('jwt');
$router->post('/cart/remove', [CartController::class, 'remove'])->middleware('jwt');
$router->post('/cart/clear', [CartController::class, 'clear'])->middleware('jwt');

// Cart API routes (JWT required - must be logged in to add to cart)
$router->post('/api/cart/add', [CartController::class, 'add'])->middleware('jwt');
$router->post('/api/cart/store-selected', [CartController::class, 'storeSelected'])->middleware('jwt');
$router->post('/api/cart/add-combo', [CartController::class, 'addCombo'])->middleware('jwt');
$router->post('/api/cart/add-bundle', [CartController::class, 'addBundle'])->middleware('jwt');

// Profile routes (Page: no middleware, controller handles auth | API/Actions: JWT required)
$router->get('/profile', [CustomerProfile::class, 'index']); // Page load - controller handles auth
$router->post('/profile/update', [CustomerProfile::class, 'updateProfile'])->middleware('jwt');
$router->post('/profile/change-password', [CustomerProfile::class, 'changePassword'])->middleware('jwt');
$router->post('/profile/upload-avatar', [CustomerProfile::class, 'uploadAvatar']); // No JWT - uses session
$router->get('/api/profile/loyalty/transactions', [CustomerProfile::class, 'apiLoyaltyTransactions'])->middleware('jwt');
$router->get('/api/profile/orders', [CustomerProfile::class, 'apiOrders'])->middleware('jwt');
$router->get('/api/profile/orders/{id}', [CustomerProfile::class, 'apiOrderDetail'])->middleware('jwt');

// Loyalty routes (Page: no middleware, controller handles auth | API: JWT required)
$router->get('/loyalty', [\App\Controllers\Customer\LoyaltyController::class, 'index']); // Page load - controller handles auth
$router->get('/api/loyalty/transactions', [\App\Controllers\Customer\LoyaltyController::class, 'apiTransactions'])->middleware('jwt');

// Payment routes
$router->get('/payment/cod/success', [CustomerPayment::class, 'codSuccess']);
$router->get('/payment/vnpay/callback', [CustomerVNPay::class, 'callback']);
$router->post('/payment/zalopay/create', [CustomerZaloPay::class, 'createPayment'])->middleware('jwt');
$router->post('/payment/zalopay/callback', [CustomerZaloPay::class, 'callback']);

// Order routes
$router->post('/api/orders/{id}/cancel', [CustomerProfile::class, 'cancelOrder'])->middleware('jwt');

// Promotion API routes
$router->get('/api/promotions/{id}', [\App\Controllers\Customer\PromotionController::class, 'getDetail']);

// GHN Shipping API routes
$router->get('/api/shipping/provinces', [\App\Controllers\Api\ShippingController::class, 'getProvinces']);
$router->get('/api/shipping/districts', [\App\Controllers\Api\ShippingController::class, 'getDistricts']);
$router->get('/api/shipping/wards', [\App\Controllers\Api\ShippingController::class, 'getWards']);
$router->post('/api/shipping/calculate-fee', [\App\Controllers\Api\ShippingController::class, 'calculateFee']);
$router->post('/api/shipping/create-order', [\App\Controllers\Api\ShippingController::class, 'createOrder'])->middleware('jwt');
$router->get('/api/shipping/track/{order_code}', [\App\Controllers\Api\ShippingController::class, 'trackOrder']);
$router->post('/api/shipping/cancel/{order_code}', [\App\Controllers\Api\ShippingController::class, 'cancelOrder'])->middleware('jwt');


// Address routes (Page: no middleware, controller handles auth | API/Actions: JWT required)
$router->get('/addresses', [CustomerAddress::class, 'index']); // Page load - controller handles auth
$router->get('/api/addresses', [CustomerAddress::class, 'getAddresses'])->middleware('jwt');
$router->get('/api/addresses/{id}', [CustomerAddress::class, 'getAddresses'])->middleware('jwt');
$router->post('/addresses', [CustomerAddress::class, 'store'])->middleware('jwt');
$router->put('/addresses/{id}', [CustomerAddress::class, 'update'])->middleware('jwt');
$router->delete('/addresses/{id}', [CustomerAddress::class, 'delete'])->middleware('jwt');
$router->post('/addresses/{id}/set-default', [CustomerAddress::class, 'setDefault'])->middleware('jwt');

// Location API routes
$router->get('/api/provinces', [\App\Controllers\LocationController::class, 'getProvinces']);
$router->get('/api/wards', [\App\Controllers\LocationController::class, 'getWards']);

// Checkout routes (Page: no middleware, controller handles auth | API/Actions: JWT required)
$router->get('/checkout', [CustomerCheckout::class, 'index']); // Page load - controller handles auth
$router->post('/checkout/validate-coupon', [CustomerCheckout::class, 'validateCoupon'])->middleware('jwt');
$router->post('/checkout/process', [CustomerCheckout::class, 'process'])->middleware('jwt');

// VNPay payment routes
$router->post('/api/payment/vnpay/create', [\App\Controllers\Customer\VNPayController::class, 'createPayment'])->middleware('jwt');
$router->get('/payment/vnpay/callback', [\App\Controllers\Customer\VNPayController::class, 'callback']);

// ZaloPay payment routes
$router->post('/api/payment/zalopay/create', [\App\Controllers\Customer\ZaloPayController::class, 'createPayment'])->middleware('jwt');
$router->post('/api/payment/zalopay/callback', [\App\Controllers\Customer\ZaloPayController::class, 'callback']);
$router->get('/payment/zalopay/return', [\App\Controllers\Customer\ZaloPayController::class, 'returnUrl']);

// Chat API routes (Chat support system with Rasa integration)
// Guest users allowed - auth handled in controller
$router->post('/api/chat/init', [ApiChat::class, 'init']);
$router->post('/api/chat/send', [ApiChat::class, 'send']);
$router->get('/api/chat/messages/{sessionId}', [ApiChat::class, 'getMessages']);
$router->get('/api/chat/promotions/{id}', [ApiChat::class, 'getPromotionDetails']);

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
    $r->post('/api/refresh-token', [AdminController::class, 'refreshToken']);
    $r->post('/api/auth/forgot-password', [AdminForgotPasswordController::class, 'sendOTP']);
    $r->post('/api/auth/verify-otp', [AdminForgotPasswordController::class, 'verifyOTP']);
    $r->post('/api/auth/reset-password', [AdminForgotPasswordController::class, 'resetPassword']);
    $r->get('/logout', [AdminController::class, 'logout']);


    // Profile
    $r->get('/profile', [AdminController::class, 'profile']);
    $r->post('/profile/upload-avatar', [AdminController::class, 'uploadAvatar']);
    $r->post('/profile/update-profile', [AdminController::class, 'updateProfile']);
    $r->post('/profile/change-password', [AdminController::class, 'changePassword']);

    // Products
    $r->get('/products', [AdminProduct::class, 'index']);
    $r->get('/api/products', [AdminProduct::class, 'apiIndex']);
    $r->get('/api/products/all-including-inactive', [AdminProduct::class, 'apiAllProducts']);
    $r->get('/api/products/stock-list', [AdminProduct::class, 'apiStockList']);
    $r->get('/api/products/{id}/images', [AdminProduct::class, 'getImages']);
    $r->post('/api/products/upload-images', [AdminProduct::class, 'uploadImages']);
    $r->get('/api/products/template', [AdminProduct::class, 'downloadTemplate']);
    $r->post('/api/products/import', [AdminProduct::class, 'importExcel']);
    $r->post('/api/products/export', [AdminProduct::class, 'export']);
    $r->post('/products', [AdminProduct::class, 'store']);
    $r->put('/products/{id}', [AdminProduct::class, 'update']);
    $r->delete('/products/{id}', [AdminProduct::class, 'destroy']);


    // Categories
    $r->get('/categories', [AdminCategory::class, 'index']);
    $r->get('/api/categories', [AdminCategory::class, 'apiIndex']);
    $r->post('/api/categories/export', [AdminCategory::class, 'export']);
    $r->get('/api/categories/template', [AdminCategory::class, 'downloadTemplate']);
    $r->post('/api/categories/import', [AdminCategory::class, 'importExcel']);
    $r->post('/categories', [AdminCategory::class, 'store']);
    $r->put('/categories/{id}', [AdminCategory::class, 'update']);
    $r->delete('/categories/{id}/delete', [AdminCategory::class, 'destroy']);

    // Brands
    $r->get('/brands', [AdminBrand::class, 'index']);
    $r->get('/api/brands', [AdminBrand::class, 'apiIndex']);
    $r->get('/api/brands/template', [AdminBrand::class, 'downloadTemplate']);
    $r->post('/api/brands/import', [AdminBrand::class, 'importExcel']);
    $r->post('/api/brands/export', [AdminBrand::class, 'export']);
    $r->post('/brands', [AdminBrand::class, 'store']);
    $r->put('/brands/{id}', [AdminBrand::class, 'update']);
    $r->delete('/brands/{id}', [AdminBrand::class, 'destroy']);

    // Suppliers
    $r->get('/suppliers', [AdminSupplier::class, 'index']);
    $r->get('/api/suppliers', [AdminSupplier::class, 'apiIndex']);
    $r->get('/api/suppliers/template', [AdminSupplier::class, 'downloadTemplate']);
    $r->post('/api/suppliers/import', [AdminSupplier::class, 'importExcel']);
    $r->post('/api/suppliers/export', [AdminSupplier::class, 'export']);
    $r->post('/suppliers', [AdminSupplier::class, 'store']);
    $r->put('/suppliers/{id}', [AdminSupplier::class, 'update']);
    $r->delete('/suppliers/{id}', [AdminSupplier::class, 'destroy']);

    // Units
    $r->get('/units', [AdminUnit::class, 'index']);
    $r->get('/api/units', [AdminUnit::class, 'apiIndex']);
    $r->get('/api/units/template', [AdminUnit::class, 'downloadTemplate']);
    $r->post('/api/units/import', [AdminUnit::class, 'importExcel']);
    $r->post('/api/units/export', [AdminUnit::class, 'export']);
    $r->post('/units', [AdminUnit::class, 'store']);
    $r->put('/units/{id}', [AdminUnit::class, 'update']);
    $r->delete('/units/{id}', [AdminUnit::class, 'destroy']);

    // Stocks
    $r->get('/stocks', [AdminStock::class, 'index']);
    $r->get('/api/stocks', [AdminStock::class, 'apiIndex']);
    $r->post('/api/stocks/export', [AdminStock::class, 'export']);
    $r->get('/stocktakes', [AdminStocktake::class, 'index']);
    $r->get('/api/stocktakes', [AdminStocktake::class, 'apiIndex']);
    $r->post('/api/stocktakes/create', [AdminStocktake::class, 'apiCreate']);
    $r->get('/api/stocktakes/{id}', [AdminStocktake::class, 'apiDetail']);

    // Stock Movements (Lịch sử thay đổi tồn kho)
    $r->get('/stock-movements', [\App\Controllers\Admin\StockMovementController::class, 'index']);
    $r->get('/api/stock-movements', [\App\Controllers\Admin\StockMovementController::class, 'apiIndex']);
    $r->get('/api/stock-movements/{id}', [\App\Controllers\Admin\StockMovementController::class, 'show']);


    // Product Batches (Inventory lots)
    $r->get('/product-batches', [AdminProductBatch::class, 'index']);
    $r->get('/api/product-batches', [AdminProductBatch::class, 'apiIndex']);
    $r->post('/api/product-batches/export', [AdminProductBatch::class, 'export']);
    $r->post('/api/product-batches', [AdminProductBatch::class, 'store']);
    $r->put('/api/product-batches/{id}', [AdminProductBatch::class, 'update']);
    $r->delete('/api/product-batches/{id}', [AdminProductBatch::class, 'destroy']);
    $r->post('/api/product-batches/{id}/restore', [AdminProductBatch::class, 'restore']);

    // Purchase Orders / Receipts
    $r->get('/purchase-orders', [AdminPurchaseOrder::class, 'index']);
    $r->get('/api/purchase-orders', [AdminPurchaseOrder::class, 'apiIndex']);
    $r->post('/api/purchase-orders/export', [AdminPurchaseOrder::class, 'export']);
    $r->get('/api/purchase-orders/template', [AdminPurchaseOrder::class, 'downloadTemplate']);
    $r->post('/api/purchase-orders/import', [AdminPurchaseOrder::class, 'importExcel']);
    $r->get('/api/purchase-orders/unpaid', [AdminPurchaseOrder::class, 'unpaid']);
    $r->get('/api/purchase-orders/{id}', [AdminPurchaseOrder::class, 'show']);
    $r->get('/purchase-orders/{id}/print', [AdminPurchaseOrder::class, 'print']);
    $r->post('/api/purchase-orders', [AdminPurchaseOrder::class, 'store']);
    $r->put('/api/purchase-orders/{id}', [AdminPurchaseOrder::class, 'update']);
    $r->delete('/api/purchase-orders/{id}', [AdminPurchaseOrder::class, 'destroy']);

    // Staffs
    $r->get('/staff', [AdminStaff::class, 'index']);
    $r->get('/api/staff', [AdminStaff::class, 'apiIndex']);
    $r->get('/api/staff/template', [AdminStaff::class, 'downloadTemplate']);
    $r->post('/api/staff/import', [AdminStaff::class, 'importExcel']);
    $r->post('/api/staff/export', [AdminStaff::class, 'export']);
    $r->get('/api/staff/roles', [AdminStaff::class, 'apiRoles']);
    $r->post('/api/staff', [AdminStaff::class, 'store']);
    $r->put('/api/staff/{id}', [AdminStaff::class, 'update']);
    $r->put('/api/staff/{id}/password', [AdminStaff::class, 'changePassword']);
    $r->delete('/api/staff/{id}', [AdminStaff::class, 'delete']);

    // Lịch làm việc (Schedule)
    $r->get('/schedules', [AdminSchedule::class, 'index']);
    $r->get('/api/schedules', [AdminSchedule::class, 'apiList']);
    $r->get('/api/schedules/by-date', [AdminSchedule::class, 'apiByDate']);
    $r->get('/api/schedules/staff-list', [AdminSchedule::class, 'apiStaffList']);
    $r->get('/api/schedules/shifts', [AdminSchedule::class, 'apiShiftList']);
    $r->post('/api/schedules', [AdminSchedule::class, 'create']);
    $r->post('/api/schedules/bulk', [AdminSchedule::class, 'bulkCreate']);
    $r->post('/api/schedules/copy-week', [AdminSchedule::class, 'copyWeek']);
    $r->put('/api/schedules/{id}', [AdminSchedule::class, 'update']);
    $r->delete('/api/schedules/{id}', [AdminSchedule::class, 'delete']);
    $r->get('/api/schedules/monthly-stats', [AdminSchedule::class, 'monthlyStats']);

    // Chấm công (Attendance)
    $r->get('/attendance', [AdminAttendance::class, 'index']);
    $r->get('/api/attendance', [AdminAttendance::class, 'apiList']);
    $r->post('/api/attendance', [AdminAttendance::class, 'store']);
    $r->put('/api/attendance/{id}', [AdminAttendance::class, 'update']);
    $r->delete('/api/attendance/{id}', [AdminAttendance::class, 'delete']);

    // Attendance Check-in/Check-out API
    $r->get('/api/attendance/today-shift', [AdminAttendance::class, 'getTodayShift']);
    $r->post('/api/attendance/check-in', [AdminAttendance::class, 'checkIn']);
    $r->post('/api/attendance/check-out', [AdminAttendance::class, 'checkOut']);

    // Quản lý lương (Payroll)
    $r->get('/payroll', [AdminPayroll::class, 'index']);
    $r->get('/api/payroll', [AdminPayroll::class, 'apiIndex']);
    $r->post('/api/payroll/calculate', [AdminPayroll::class, 'calculate']);
    $r->post('/api/payroll/calculate/{id}', [AdminPayroll::class, 'calculateOne']);
    $r->post('/api/payroll/approve-all', [AdminPayroll::class, 'approveAll']);
    $r->put('/api/payroll/{id}/bonus-deduction', [AdminPayroll::class, 'updateBonusDeduction']);
    $r->post('/api/payroll/{id}/approve', [AdminPayroll::class, 'approve']);
    $r->post('/api/payroll/{id}/mark-paid', [AdminPayroll::class, 'markAsPaid']);
    $r->delete('/api/payroll/{id}', [AdminPayroll::class, 'delete']);

    // Customers
    $r->get('/customers', [AdminCustomer::class, 'index']);
    $r->get('/api/customers', [AdminCustomer::class, 'apiIndex']);
    $r->get('/api/customers/template', [AdminCustomer::class, 'downloadTemplate']);
    $r->post('/api/customers/import', [AdminCustomer::class, 'importExcel']);
    $r->post('/api/customers/export', [AdminCustomer::class, 'export']);
    $r->post('/api/customers', [AdminCustomer::class, 'store']);
    $r->put('/api/customers/{id}', [AdminCustomer::class, 'update']);
    $r->put('/api/customers/{id}/password', [AdminCustomer::class, 'changePassword']);
    $r->get('/api/customers/{id}/addresses', [AdminCustomer::class, 'getAddresses']);
    $r->get('/api/customers/{id}/detail', [AdminCustomer::class, 'getDetail']);
    $r->get('/api/customers/{id}/loyalty-transactions', [AdminCustomer::class, 'getLoyaltyTransactions']);
    $r->delete('/api/customers/{id}', [AdminCustomer::class, 'destroy']);

    // Expense Vouchers
    $r->get('/expense_vouchers', [AdminExpenseVoucher::class, 'index']);
    $r->get('/api/expense_vouchers', [AdminExpenseVoucher::class, 'apiIndex']);
    $r->post('/api/expense_vouchers/export', [AdminExpenseVoucher::class, 'export']);
    $r->post('/api/expense_vouchers', [AdminExpenseVoucher::class, 'store']);
    $r->put('/api/expense_vouchers/{id}', [AdminExpenseVoucher::class, 'update']);
    $r->delete('/api/expense_vouchers/{id}', [AdminExpenseVoucher::class, 'destroy']);
    $r->get('/api/expense_vouchers/next-code', [AdminExpenseVoucher::class, 'nextCode']);

    // Receipt Vouchers
    $r->get('/receipt_vouchers', [AdminReceiptVoucher::class, 'index']);
    $r->get('/api/receipt_vouchers', [AdminReceiptVoucher::class, 'apiIndex']);
    $r->post('/api/receipt_vouchers/export', [AdminReceiptVoucher::class, 'export']);
    $r->post('/api/receipt_vouchers', [AdminReceiptVoucher::class, 'store']);
    $r->put('/api/receipt_vouchers/{id}', [AdminReceiptVoucher::class, 'update']);
    $r->delete('/api/receipt_vouchers/{id}', [AdminReceiptVoucher::class, 'destroy']);
    $r->get('/api/receipt_vouchers/next-code', [AdminReceiptVoucher::class, 'nextCode']);

    // Supplier Debts (Công nợ nhà cung cấp)
    $r->get('/supplier-debts', [AdminSupplier::class, 'debtsIndex']);
    $r->get('/api/supplier-debts/suppliers', [AdminSupplier::class, 'apiGetSuppliersWithDebt']);
    $r->get('/api/supplier-debts/orders', [AdminSupplier::class, 'apiGetDebtOrders']);
    $r->get('/supplier-debts/detail/{id}', [AdminSupplier::class, 'debtDetail']);

    // Orders (Quản lý bán hàng)
    $r->get('/orders', [AdminOrder::class, 'index']);
    $r->get('/api/orders', [AdminOrder::class, 'apiIndex']);
    $r->get('/api/orders/next-code', [AdminOrder::class, 'nextCode']);
    $r->get('/api/orders/unpaid', [AdminOrder::class, 'unpaid']);
    $r->post('/api/orders/calculate-with-promotions', [AdminOrder::class, 'calculateWithPromotions']);
    $r->get('/api/orders/{id}/items', [AdminOrder::class, 'getItems']);
    $r->get('/orders/{id}/print', [AdminOrder::class, 'print']);
    $r->post('/orders', [AdminOrder::class, 'store']);
    $r->put('/orders/{id}', [AdminOrder::class, 'update']);
    $r->post('/api/orders/export', [AdminOrder::class, 'export']);
    $r->delete('/orders/{id}', [AdminOrder::class, 'destroy']);

    // GHN Order Processing
    $r->post('/api/orders/{id}/process', [AdminOrder::class, 'processOrder']);
    $r->post('/api/orders/{id}/ship-with-ghn', [AdminOrder::class, 'shipWithGHN']);
    $r->post('/api/orders/{id}/cancel', [AdminOrder::class, 'cancelOrder']);
    $r->post('/api/orders/{id}/manual-complete', [AdminOrder::class, 'manualComplete']);
    $r->post('/api/orders/{id}/manual-cancel', [AdminOrder::class, 'manualCancel']);
    $r->post('/api/orders/{id}/manual-ship', [AdminOrder::class, 'manualShip']);
    $r->get('/api/orders/{id}/tracking', [AdminOrder::class, 'getTracking']);


    // Stock Outs (Phiếu xuất kho)
    $r->get('/stock-outs', [AdminStockOut::class, 'index']);
    $r->get('/api/stock-outs', [AdminStockOut::class, 'apiIndex']);
    $r->post('/api/stock-outs/export', [AdminStockOut::class, 'export']);
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
    $r->get('/api/coupons/template', [AdminCoupon::class, 'downloadTemplate']);
    $r->post('/api/coupons/import', [AdminCoupon::class, 'importExcel']);
    $r->post('/api/coupons/export', [AdminCoupon::class, 'export']);
    $r->post('/api/coupons/validate', [AdminCoupon::class, 'validate']);
    $r->post('/coupons', [AdminCoupon::class, 'store']);
    $r->put('/coupons/{id}', [AdminCoupon::class, 'update']);
    $r->delete('/coupons/{id}', [AdminCoupon::class, 'destroy']);

    // Promotions (Chương trình khuyến mãi)
    // API routes phải đặt trước để không bị routes khác catch
    $r->post('/api/promotions/check', [AdminPromotion::class, 'check']);
    $r->post('/api/promotions/export', [AdminPromotion::class, 'export']);
    $r->get('/api/promotions', [AdminPromotion::class, 'apiIndex']);
    $r->get('/promotions', [AdminPromotion::class, 'index']);
    $r->post('/promotions', [AdminPromotion::class, 'store']);
    $r->put('/promotions/{id}', [AdminPromotion::class, 'update']);
    $r->delete('/promotions/{id}', [AdminPromotion::class, 'destroy']);

    // Audit Logs (Lịch sử thao tác)
    $r->get('/audit-logs', [AdminAuditLog::class, 'index']);
    $r->get('/api/audit-logs', [AdminAuditLog::class, 'apiIndex']);
    $r->get('/api/audit-logs/entity/{type}/{id}', [AdminAuditLog::class, 'apiGetByEntity']);
    $r->get('/api/audit-logs/stats/action', [AdminAuditLog::class, 'apiStatsByAction']);
    $r->get('/api/audit-logs/stats/entity', [AdminAuditLog::class, 'apiStatsByEntity']);
    $r->get('/api/audit-logs/stats/staff', [AdminAuditLog::class, 'apiStatsByStaff']);
    $r->get('/api/audit-logs/staff-list', [AdminAuditLog::class, 'apiGetStaffList']);

    // Reports (Thống kê & Báo cáo - Admin only)
    $r->get('/reports', [AdminReports::class, 'index']);
    $r->get('/api/reports/overview', [AdminReports::class, 'apiOverview']);
    $r->get('/api/reports/staff/orders', [AdminReports::class, 'apiStaffByOrders']);
    $r->get('/api/reports/staff/revenue', [AdminReports::class, 'apiStaffByRevenue']);
    $r->get('/api/reports/products/quantity', [AdminReports::class, 'apiProductsByQuantity']);
    $r->get('/api/reports/products/revenue', [AdminReports::class, 'apiProductsByRevenue']);
    $r->get('/api/reports/suppliers', [AdminReports::class, 'apiSuppliers']);
    $r->get('/api/reports/customers/spenders', [AdminReports::class, 'apiCustomersBySpending']);
    $r->get('/api/reports/customers/orders', [AdminReports::class, 'apiCustomersByOrders']);
    $r->get('/api/reports/inventory/low-stock', [AdminReports::class, 'apiLowStock']);
    $r->get('/api/reports/inventory/high-stock', [AdminReports::class, 'apiHighStock']);
    $r->get('/api/reports/order-status', [AdminReports::class, 'apiOrderStatus']);
    $r->get('/api/reports/filter', [AdminReports::class, 'apiFilter']);
    $r->get('/api/reports/export', [AdminReports::class, 'apiExport']);

    // API danh sách cho dropdown filters
    $r->get('/api/reports/staff-list', [AdminReports::class, 'apiStaffList']);
    $r->get('/api/reports/product-list', [AdminReports::class, 'apiProductList']);
    $r->get('/api/reports/customer-list', [AdminReports::class, 'apiCustomerList']);
    $r->get('/api/reports/supplier-list', [AdminReports::class, 'apiSupplierList']);

    // Import History (Lịch sử nhập file - Tất cả modules)
    $r->get('/import-history', [AdminImportHistory::class, 'index']);
    $r->get('/api/import-history', [AdminImportHistory::class, 'apiIndex']);
    $r->get('/api/import-history/{id}', [AdminImportHistory::class, 'apiDetail']);
    $r->delete('/api/import-history/{id}', [AdminImportHistory::class, 'destroy']);

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
    $r->post('/api/stock-alerts/clear-cache', [AdminStockAlert::class, 'clearCache']);

    // Payment Due Alerts (Cảnh báo hạn thanh toán)
    $r->get('/api/payment-due-alerts/stats', [AdminPaymentDueAlert::class, 'getStats']);
    $r->post('/api/payment-due-alerts/run', [AdminPaymentDueAlert::class, 'runCheck']);
    $r->get('/api/payment-due-alerts/list', [AdminPaymentDueAlert::class, 'getList']);
    $r->post('/api/payment-due-alerts/cleanup', [AdminPaymentDueAlert::class, 'cleanup']);

    // Expiry Alerts (Cảnh báo hàng hết hạn/sắp hết hạn)
    $r->get('/api/expiry-alerts/stats', [AdminExpiryAlert::class, 'stats']);
    $r->post('/api/expiry-alerts/run-check', [AdminExpiryAlert::class, 'runCheck']);
    $r->get('/api/expiry-alerts/batches', [AdminExpiryAlert::class, 'getBatches']);
    $r->post('/api/expiry-alerts/cleanup', [AdminExpiryAlert::class, 'cleanup']);

    // Attendance (Chấm công)
    $r->get('/api/attendance/today-shift', [AdminAttendance::class, 'getTodayShift']);
    $r->post('/api/attendance/check-in', [AdminAttendance::class, 'checkIn']);
    $r->post('/api/attendance/check-out', [AdminAttendance::class, 'checkOut']);
    $r->get('/api/attendance', [AdminAttendance::class, 'apiIndex']);
    $r->get('/attendance', [AdminAttendance::class, 'index']);
    $r->delete('/api/attendance/{id}', [AdminAttendance::class, 'delete']);
    $r->post('/api/attendance/{id}/approve', [AdminAttendance::class, 'approve']);

    // Payroll (Quản lý bảng lương)
    $r->get('/payroll', [AdminPayroll::class, 'index']);
    $r->get('/api/payroll', [AdminPayroll::class, 'apiIndex']);
    $r->get('/api/payroll/export', [AdminPayroll::class, 'export']);
    $r->get('/api/payroll/salary-history', [AdminPayroll::class, 'getSalaryHistory']);
    $r->get('/api/payroll/salary-history/export', [AdminPayroll::class, 'exportSalaryHistory']);
    $r->post('/api/payroll/calculate', [AdminPayroll::class, 'calculate']);
    $r->post('/api/payroll/calculate/{userId}', [AdminPayroll::class, 'calculateOne']);
    $r->put('/api/payroll/{id}/bonus-deduction', [AdminPayroll::class, 'updateBonusDeduction']);
    $r->post('/api/payroll/{id}/approve', [AdminPayroll::class, 'approve']);
    $r->post('/api/payroll/{id}/mark-paid', [AdminPayroll::class, 'markAsPaid']);
    $r->post('/api/payroll/{id}/pay', [AdminPayroll::class, 'pay']);
    $r->post('/api/payroll/pay-all', [AdminPayroll::class, 'payAll']);
    $r->delete('/api/payroll/{id}', [AdminPayroll::class, 'delete']);

    // Chat Support (Hỗ trợ trực tuyến)
    $r->get('/chat-support', [AdminChatSupport::class, 'index']);
    $r->get('/api/chat-support/stats', [AdminChatSupport::class, 'apiGetStats']);
    $r->get('/api/chat-support/sessions', [AdminChatSupport::class, 'apiGetSessions']);
    $r->get('/api/chat-support/messages/{sessionId}', [AdminChatSupport::class, 'apiGetMessages']);
    $r->post('/api/chat-support/send', [AdminChatSupport::class, 'apiSendMessage']);
    $r->post('/api/chat-support/assign', [AdminChatSupport::class, 'apiAssignStaff']);
    $r->post('/api/chat-support/close', [AdminChatSupport::class, 'apiCloseSession']);
});

/* --- chạy router --- */
$router->dispatch(Request::capture());
