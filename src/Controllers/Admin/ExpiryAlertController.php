<?php
namespace App\Controllers\Admin;

use App\Services\DailyExpiryAlertService;

class ExpiryAlertController extends BaseAdminController
{
    public function __construct()
    {
        AuthController::requirePasswordChanged();
    }

    /**
     * API: Lấy thống kê hàng hết hạn
     * GET /admin/api/expiry-alert/stats
     */
    public function stats()
    {
        try {
            $stats = DailyExpiryAlertService::getExpiryStats();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            error_log("Error getting expiry stats: " . $e->getMessage());
            
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * API: Chạy kiểm tra hàng hết hạn ngay (force check)
     * POST /admin/api/expiry-alert/run-check
     */
    public function runCheck()
    {
        try {
            // Xóa cache để force chạy lại
            $pdo = \App\Core\DB::pdo();
            $pdo->exec("DELETE FROM system_jobs WHERE job_name = 'daily_expiry_alert_check'");
            
            $result = DailyExpiryAlertService::runDailyCheck();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'Đã kiểm tra và cập nhật thông báo hàng hết hạn',
                'data' => $result
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            error_log("Error running expiry check: " . $e->getMessage());
            
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi kiểm tra: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * API: Xem chi tiết các lô hàng hết hạn/sắp hết hạn
     * GET /admin/api/expiry-alert/batches?type=expired|critical|warning
     */
    public function getBatches()
    {
        try {
            $type = $_GET['type'] ?? 'all';
            $pdo = \App\Core\DB::pdo();
            
            $conditions = [];
            $params = [];
            
            switch ($type) {
                case 'expired':
                    $conditions[] = "pb.exp_date < CURDATE()";
                    break;
                case 'critical':
                    $conditions[] = "pb.exp_date >= CURDATE()";
                    $conditions[] = "pb.exp_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
                    break;
                case 'warning':
                    $conditions[] = "pb.exp_date > DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
                    $conditions[] = "pb.exp_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                    break;
                default: // all
                    $conditions[] = "pb.exp_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            $sql = "SELECT 
                        pb.id, pb.batch_code, pb.exp_date, pb.current_qty, pb.unit_cost,
                        p.id as product_id, p.name as product_name, p.sku,
                        DATEDIFF(pb.exp_date, CURDATE()) as days_left,
                        CASE 
                            WHEN pb.exp_date < CURDATE() THEN 'expired'
                            WHEN pb.exp_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'critical'
                            ELSE 'warning'
                        END as alert_level
                    FROM product_batches pb
                    JOIN products p ON p.id = pb.product_id
                    WHERE {$whereClause}
                      AND pb.current_qty > 0
                      AND pb.is_active = 1
                      AND p.is_active = 1
                    ORDER BY pb.exp_date ASC, p.name ASC";
            
            $stmt = $pdo->query($sql);
            $batches = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'data' => $batches,
                'count' => count($batches)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            error_log("Error getting expiry batches: " . $e->getMessage());
            
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * API: Dọn dẹp thông báo hết hạn cũ
     * POST /admin/api/expiry-alert/cleanup
     */
    public function cleanup()
    {
        try {
            $deleted = DailyExpiryAlertService::cleanupOldNotifications();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => "Đã xóa {$deleted} thông báo cũ",
                'deleted' => $deleted
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            error_log("Error cleaning up expiry notifications: " . $e->getMessage());
            
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi khi dọn dẹp: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
