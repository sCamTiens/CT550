<?php
namespace App\Controllers\Admin;

use App\Services\DailyPaymentDueAlertService;

class PaymentDueAlertController extends BaseAdminController
{
    public function __construct()
    {
        AuthController::requirePasswordChanged();
    }

    /**
     * Lấy thống kê về phiếu nhập sắp đến hạn thanh toán
     * GET /admin/api/payment-due-alerts/stats
     */
    public function getStats()
    {
        try {
            $stats = DailyPaymentDueAlertService::getPaymentDueStats();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Lỗi khi lấy thống kê',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Chạy kiểm tra thủ công (cho admin test)
     * POST /admin/api/payment-due-alerts/run
     */
    public function runCheck()
    {
        try {
            $result = DailyPaymentDueAlertService::runDailyCheck();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'Đã kiểm tra và tạo thông báo thành công',
                'data' => $result
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi chạy kiểm tra',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Lấy danh sách phiếu nhập sắp đến hạn/quá hạn
     * GET /admin/api/payment-due-alerts/list
     */
    public function getList()
    {
        try {
            $pdo = \App\Core\DB::pdo();
            
            $sql = "SELECT 
                        po.id,
                        po.code,
                        po.due_date,
                        po.total_amount,
                        po.paid_amount,
                        po.payment_status,
                        s.name AS supplier_name,
                        DATEDIFF(po.due_date, CURDATE()) AS days_until_due,
                        (po.total_amount - po.paid_amount) AS remaining_amount
                    FROM purchase_orders po
                    LEFT JOIN suppliers s ON s.id = po.supplier_id
                    WHERE po.due_date IS NOT NULL
                      AND (po.total_amount - po.paid_amount) > 0
                      AND po.due_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    ORDER BY 
                        CASE 
                            WHEN po.due_date < CURDATE() THEN 0
                            ELSE 1
                        END,
                        po.due_date ASC";
            
            $stmt = $pdo->query($sql);
            $list = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Format dữ liệu
            foreach ($list as &$item) {
                $item['days_until_due'] = (int)$item['days_until_due'];
                $item['remaining_amount'] = (float)$item['remaining_amount'];
                $item['total_amount'] = (float)$item['total_amount'];
                $item['paid_amount'] = (float)$item['paid_amount'];
                
                // Xác định trạng thái
                if ($item['days_until_due'] < 0) {
                    $item['status'] = 'overdue';
                    $item['status_text'] = 'Quá hạn ' . abs($item['days_until_due']) . ' ngày';
                    $item['priority'] = 'critical';
                } elseif ($item['days_until_due'] == 0) {
                    $item['status'] = 'due_today';
                    $item['status_text'] = 'Đến hạn hôm nay';
                    $item['priority'] = 'critical';
                } elseif ($item['days_until_due'] <= 3) {
                    $item['status'] = 'due_soon';
                    $item['status_text'] = 'Còn ' . $item['days_until_due'] . ' ngày';
                    $item['priority'] = $item['days_until_due'] == 1 ? 'high' : 'medium';
                } else {
                    $item['status'] = 'normal';
                    $item['status_text'] = 'Còn ' . $item['days_until_due'] . ' ngày';
                    $item['priority'] = 'low';
                }
                
                // Format ngày
                $item['due_date_formatted'] = date('d/m/Y', strtotime($item['due_date']));
            }
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($list, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Lỗi khi lấy danh sách',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Dọn dẹp thông báo cũ
     * POST /admin/api/payment-due-alerts/cleanup
     */
    public function cleanup()
    {
        try {
            $deleted = DailyPaymentDueAlertService::cleanupOldNotifications();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => "Đã xóa {$deleted} thông báo cũ",
                'deleted' => $deleted
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi dọn dẹp thông báo',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
