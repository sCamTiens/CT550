<?php

namespace App\Middlewares;

class RoleMiddleware
{
    /**
     * Định nghĩa quyền truy cập theo staff_role
     * - Admin: Truy cập tất cả
     * - Nhân viên bán hàng: Quản lý đơn hàng, khách hàng, sản phẩm (xem), coupon/promotion (xem)
     * - Kho: Quản lý kho, sản phẩm, nhà cung cấp, đơn vị tính, phiếu nhập/xuất, kiểm kê, lô sản phẩm
     * - Hỗ trợ trực tuyến: Xem dashboard, khách hàng, đơn hàng (xem)
     */
    private static $rolePermissions = [
        'Admin' => '*', // Truy cập tất cả
        'Nhân viên bán hàng' => [
            '/admin/orders',
            '/admin/customers',
            // Đã xóa /admin/products - không cho phép xem danh mục sản phẩm
            '/admin/coupons',
            '/admin/promotions',
            // API endpoints
            '/admin/api/orders',
            '/admin/api/customers',
            // Đã xóa /admin/api/products
            '/admin/api/coupons',
            '/admin/api/promotions',
            '/admin/import-history',
            '/admin/api/import-history',
            '/admin/api/import-history/{id}',
            '/admin/api/import-history/{id}'
        ],
        'Kho' => [
            '/admin/categories',
            '/admin/brands',
            '/admin/products',
            '/admin/suppliers',
            '/admin/units',
            '/admin/stocks',
            '/admin/purchase-orders',
            '/admin/stock-outs',
            '/admin/stocktakes',
            '/admin/product-batches',
            '/admin/receipt_vouchers',
            '/admin/expense_vouchers',
            '/admin/supplier-debts',
            // API endpoints
            '/admin/api/categories',
            '/admin/api/brands',
            '/admin/api/products',
            '/admin/api/suppliers',
            '/admin/api/units',
            '/admin/api/stocks',
            '/admin/api/purchase-orders',
            '/admin/api/stock-outs',
            '/admin/api/stocktakes',
            '/admin/api/product-batches',
            '/admin/api/receipt_vouchers',
            '/admin/api/expense_vouchers',
            '/admin/api/supplier-debts',
            '/admin/import-history',
            '/admin/api/import-history',
            '/admin/api/import-history/{id}',
            '/admin/api/import-history/{id}'
        ],
        'Hỗ trợ trực tuyến' => [
            '/admin/customers',
            '/admin/orders', // Chỉ xem
            // API endpoints
            '/admin/api/customers',
            '/admin/api/orders',
        ],
    ];

        /**
     * Kiểm tra quyền truy cập
     * 
     * @param string $requestPath Đường dẫn đang yêu cầu
     * @return bool True nếu có quyền, False nếu không
     */
    public static function checkAccess(string $requestPath): bool
    {
        // Kiểm tra đăng nhập
        if (!isset($_SESSION['user'])) {
            return false;
        }

        // Lấy role từ session
        $staffRole = $_SESSION['user']['staff_role'] ?? null;
        if (!$staffRole) {
            return false;
        }

        // Admin có quyền truy cập mọi nơi
        if ($staffRole === 'Admin') {
            return true;
        }

        // Kiểm tra quyền theo role
        $allowedPaths = self::$rolePermissions[$staffRole] ?? [];
        if ($allowedPaths === '*') {
            return true;
        }

        // Kiểm tra xem đường dẫn có được phép không
        foreach ($allowedPaths as $allowedPath) {
            // Exact match: đường dẫn giống hệt
            if ($requestPath === $allowedPath) {
                return true;
            }
            
            // Prefix match: đường dẫn bắt đầu bằng allowedPath + '/'
            // Ví dụ: /admin/products cho phép /admin/products/123 nhưng KHÔNG cho phép /admin/productsx
            if (strpos($requestPath, $allowedPath . '/') === 0) {
                return true;
            }
            
        }

        return false;
    }

    /**
     * Kiểm tra và redirect nếu không có quyền
     * 
     * @param string $requestPath
     */
    public static function authorize(string $requestPath): void
    {
        
        if (!self::checkAccess($requestPath)) {
            
            // Lưu thông báo lỗi vào session
            $_SESSION['flash_error'] = 'Bạn không có quyền truy cập trang này.';
            
            // Dừng tất cả output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Redirect về trang trước hoặc dashboard
            $referer = $_SERVER['HTTP_REFERER'] ?? null;
            $redirectTo = '/admin';
            
            // Nếu có referer và không phải trang hiện tại
            if ($referer) {
                $refererPath = parse_url($referer, PHP_URL_PATH);
                $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                
                // Nếu referer khác trang hiện tại và là admin path
                if ($refererPath !== $currentPath && strpos($refererPath, '/admin') === 0) {
                    $redirectTo = $refererPath;
                }
            }
                     
            // Send redirect header
            header("Location: $redirectTo", true, 302);
            
            // Force stop execution
            die();
        }
    }

    /**
     * Lấy danh sách các sections mà user có quyền xem trên sidebar
     * 
     * @return array
     */
    public static function getAllowedSections(): array
    {
        // Khởi tạo mặc định tất cả sections = false để tránh "Undefined array key"
        $sections = [
            'dashboard' => false,
            'orders' => false,
            'catalog' => false,
            'inventory' => false,
            'expense' => false,
            'promo' => false,
            'staff' => false,
            'customers' => false,
            'reports' => false,
            'audit-logs' => false,
        ];

        // Nếu chưa đăng nhập hoặc không có staff_role
        if (empty($_SESSION['user']['staff_role'])) {
            return $sections;
        }

        $staffRole = $_SESSION['user']['staff_role'];

        // Admin có quyền truy cập tất cả
        if ($staffRole === 'Admin') {
            return [
                'dashboard' => true,
                'orders' => true,
                'catalog' => true,
                'inventory' => true,
                'expense' => true,
                'promo' => true,
                'staff' => true,
                'customers' => true,
                'reports' => true,
                'audit-logs' => true,
            ];
        }

        // Tất cả role đều được truy cập dashboard
        $sections['dashboard'] = true;

        $allowedPaths = self::$rolePermissions[$staffRole] ?? [];

        foreach ($allowedPaths as $path) {
            if (strpos($path, '/admin/orders') === 0) {
                $sections['orders'] = true;
            } elseif (in_array($path, ['/admin/categories', '/admin/brands', '/admin/products', '/admin/suppliers', '/admin/units'])) {
                $sections['catalog'] = true;
            } elseif (in_array($path, ['/admin/stocks', '/admin/purchase-orders', '/admin/stock-outs', '/admin/stocktakes', '/admin/product-batches'])) {
                $sections['inventory'] = true;
            } elseif (in_array($path, ['/admin/receipt_vouchers', '/admin/expense_vouchers', '/admin/supplier-debts'])) {
                $sections['expense'] = true;
            } elseif (in_array($path, ['/admin/coupons', '/admin/promotions'])) {
                $sections['promo'] = true;
            } elseif ($path === '/admin/customers') {
                $sections['customers'] = true;
            } elseif ($path === '/admin/staff') {
                $sections['staff'] = true;
            }
        }

        return $sections;
    }

    /**
     * Kiểm tra có quyền truy cập vào một section cụ thể không
     * 
     * @param string $section
     * @return bool
     */
    public static function canAccessSection(string $section): bool
    {
        $allowedSections = self::getAllowedSections();
        return $allowedSections[$section] ?? false;
    }
}
