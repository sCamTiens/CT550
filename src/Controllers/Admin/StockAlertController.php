<?php
namespace App\Controllers\Admin;

use App\Services\DailyStockAlertService;

/**
 * Controller quản lý hệ thống cảnh báo tồn kho tự động
 */
class StockAlertController extends BaseAdminController
{
    public function __construct()
    {
        AuthController::requirePasswordChanged();
    }

    /**
     * Trang quản lý cảnh báo tồn kho
     * GET /admin/stock-alerts
     */
    public function index()
    {
        // Chỉ admin mới được truy cập
        $user = $_SESSION['admin_user'] ?? [];
        if (!isset($user['role_id']) || $user['role_id'] != 2) {
            http_response_code(403);
            echo "Forbidden";
            exit;
        }

        // Lấy thống kê
        $stats = DailyStockAlertService::getStockStats();

        require __DIR__ . '/../../views/admin/stock-alerts/index.php';
    }

    /**
     * API: Chạy kiểm tra tồn kho thủ công
     * POST /admin/api/stock-alerts/run-check
     */
    public function runCheck()
    {
        // Chỉ admin mới được chạy
        $user = $_SESSION['admin_user'] ?? [];
        if (!isset($user['role_id']) || $user['role_id'] != 2) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        try {
            $result = DailyStockAlertService::runDailyCheck();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'data' => $result
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Lấy thống kê tồn kho
     * GET /admin/api/stock-alerts/stats
     */
    public function stats()
    {
        try {
            $stats = DailyStockAlertService::getStockStats();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Dọn dẹp thông báo cũ
     * POST /admin/api/stock-alerts/cleanup
     */
    public function cleanup()
    {
        // Chỉ admin mới được chạy
        $user = $_SESSION['admin_user'] ?? [];
        if (!isset($user['role_id']) || $user['role_id'] != 2) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        try {
            $deleted = DailyStockAlertService::cleanupOldNotifications();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'deleted' => $deleted
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}
