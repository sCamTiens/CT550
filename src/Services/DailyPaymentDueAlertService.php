<?php
namespace App\Services;

use App\Core\DB;
use App\Models\Repositories\NotificationRepository;

/**
 * Service kiểm tra ngày hẹn thanh toán phiếu nhập và tạo thông báo cảnh báo
 * Chạy vào 7h sáng mỗi ngày để cảnh báo phiếu nhập sắp đến hạn thanh toán
 */
class DailyPaymentDueAlertService
{
    /**
     * Kiểm tra và tạo thông báo cho phiếu nhập sắp đến hạn thanh toán
     * - Cảnh báo trước 3 ngày, 2 ngày, 1 ngày
     * - Cảnh báo hết hạn (màu đỏ)
     * - Chỉ cảnh báo phiếu chưa thanh toán hết (payment_status != 2)
     * 
     * @return array Kết quả thực hiện
     */
    public static function runDailyCheck(): array
    {
        $pdo = DB::pdo();
        $result = [
            'status' => 'skipped',
            'deleted_old_notifications' => 0,
            'overdue_count' => 0,
            'due_in_1_day' => 0,
            'due_in_2_days' => 0,
            'due_in_3_days' => 0,
            'notifications_created' => 0,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        try {
            // Kiểm tra đã chạy hôm nay chưa
            $pdo->exec("CREATE TABLE IF NOT EXISTS system_jobs (
            job_name VARCHAR(100) PRIMARY KEY,
            last_run DATETIME DEFAULT NULL
        )");

            $stmt = $pdo->prepare("SELECT last_run FROM system_jobs WHERE job_name = 'daily_payment_due_check'");
            $stmt->execute();
            $lastRun = $stmt->fetchColumn();

            if ($lastRun && date('Y-m-d', strtotime($lastRun)) === date('Y-m-d')) {
                // Đã chạy hôm nay rồi → bỏ qua
                return $result;
            }

            // Nếu chưa có bản ghi thì thêm mới
            $pdo->exec("INSERT IGNORE INTO system_jobs (job_name) VALUES ('daily_payment_due_check')");

            // ================== TIẾP TỤC CODE GỐC CỦA BẠN ==================
            $sqlDelete = "DELETE FROM notifications 
                     WHERE title LIKE '%thanh toán phiếu nhập%' 
                        OR title LIKE '%hẹn thanh toán%'
                        OR title LIKE '%quá hạn thanh toán%'";
            $stmtDelete = $pdo->query($sqlDelete);
            $result['deleted_old_notifications'] = $stmtDelete->rowCount();

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
                ORDER BY po.due_date ASC";

            $stmt = $pdo->query($sql);
            $purchaseOrders = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($purchaseOrders as $po) {
                $daysUntilDue = (int) $po['days_until_due'];
                $remainingAmount = (float) $po['remaining_amount'];
                $created = false;

                if ($daysUntilDue < 0) {
                    $result['overdue_count']++;
                    $created = self::createOverdueNotification($po, abs($daysUntilDue), $remainingAmount);
                } elseif ($daysUntilDue == 0) {
                    $result['due_in_1_day']++;
                    $created = self::createDueTodayNotification($po, $remainingAmount);
                } elseif ($daysUntilDue == 1) {
                    $result['due_in_1_day']++;
                    $created = self::createDueInDaysNotification($po, 1, $remainingAmount);
                } elseif ($daysUntilDue == 2) {
                    $result['due_in_2_days']++;
                    $created = self::createDueInDaysNotification($po, 2, $remainingAmount);
                } elseif ($daysUntilDue == 3) {
                    $result['due_in_3_days']++;
                    $created = self::createDueInDaysNotification($po, 3, $remainingAmount);
                }

                if ($created) {
                    $result['notifications_created']++;
                }
            }

            // Cập nhật thời gian chạy sau khi hoàn tất
            $stmtUpdate = $pdo->prepare("UPDATE system_jobs SET last_run = NOW() WHERE job_name = 'daily_payment_due_check'");
            $stmtUpdate->execute();

            $result['status'] = 'completed';
            return $result;

        } catch (\Exception $e) {
            error_log("Error in daily payment due alert check: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tạo thông báo cho phiếu nhập QUÁ HẠN thanh toán (màu đỏ)
     */
    private static function createOverdueNotification(array $po, int $daysOverdue, float $remainingAmount): bool
    {
        $pdo = DB::pdo();

        // Xác định trạng thái thanh toán
        $paymentStatusText = self::getPaymentStatusText($po['payment_status'], $po['paid_amount']);

        $title = 'Quá hạn thanh toán phiếu nhập';
        $message = sprintf(
            "Phiếu nhập <strong>#%s</strong> (<strong>%s</strong>) đã <strong>QUÁ HẠN %d ngày</strong>. " .
            "Trạng thái: %s. <strong>Còn nợ: %s VNĐ</strong>",
            $po['code'],
            $po['supplier_name'] ?? 'Không rõ',
            $daysOverdue,
            $paymentStatusText,
            number_format($remainingAmount, 0, ',', '.')
        );

        return self::sendNotificationToUsers('error', $title, $message, '/admin/purchase-orders/' . $po['id']);
    }

    /**
     * Tạo thông báo cho phiếu nhập ĐẾN HẠN HÔM NAY
     */
    private static function createDueTodayNotification(array $po, float $remainingAmount): bool
    {
        // Xác định trạng thái thanh toán
        $paymentStatusText = self::getPaymentStatusText($po['payment_status'], $po['paid_amount']);

        $title = 'Hôm nay là hạn thanh toán';
        $message = sprintf(
            "Phiếu nhập <strong>#%s</strong> (<strong>%s</strong>) <strong>đến hạn thanh toán HÔM NAY</strong>. " .
            "Trạng thái: %s. <strong>Còn nợ: %s VNĐ</strong>",
            $po['code'],
            $po['supplier_name'] ?? 'Không rõ',
            $paymentStatusText,
            number_format($remainingAmount, 0, ',', '.')
        );

        return self::sendNotificationToUsers('error', $title, $message, '/admin/purchase-orders/' . $po['id']);
    }

    /**
     * Tạo thông báo cho phiếu nhập sắp đến hạn (1-3 ngày)
     */
    private static function createDueInDaysNotification(array $po, int $days, float $remainingAmount): bool
    {
        // Xác định trạng thái thanh toán
        $paymentStatusText = self::getPaymentStatusText($po['payment_status'], $po['paid_amount']);

        $title = sprintf('%s Sắp đến hạn thanh toán (%d ngày)', $days);
        $message = sprintf(
            "Phiếu nhập <strong>#%s</strong> (<strong>%s</strong>) sẽ <strong>đến hạn thanh toán trong %d ngày</strong> " .
            "(%s). Trạng thái: %s. <strong>Còn nợ: %s VNĐ</strong>",
            $po['code'],
            $po['supplier_name'] ?? 'Không rõ',
            $days,
            date('d/m/Y', strtotime($po['due_date'])),
            $paymentStatusText,
            number_format($remainingAmount, 0, ',', '.')
        );

        $type = $days == 1 ? 'error' : 'warning';

        return self::sendNotificationToUsers($type, $title, $message, '/admin/purchase-orders/' . $po['id']);
    }

    /**
     * Xác định text trạng thái thanh toán
     */
    private static function getPaymentStatusText(string $status, float $paidAmount): string
    {
        // Nếu đã thanh toán một phần (paid_amount > 0)
        if ($paidAmount > 0) {
            return 'Đã thanh toán một phần';
        }

        // Nếu chưa thanh toán gì cả
        if ($status === 'Chưa đối soát' || $status === '1' || $status === 1) {
            return 'Chưa đối soát';
        }

        // Mặc định
        return $status;
    }

    /**
     * Gửi thông báo đến tất cả user có quyền quản lý thanh toán
     * (Admin, Quản lý, Kế toán)
     */
    private static function sendNotificationToUsers(string $type, string $title, string $message, string $link): bool
    {
        $pdo = DB::pdo();

        // Lấy danh sách user cần nhận thông báo (admin, quản lý, kế toán)
        $sqlUsers = "SELECT DISTINCT u.id 
                     FROM users u 
                     LEFT JOIN staff_profiles sp ON sp.user_id = u.id 
                     WHERE u.role_id IN (2, 3)
                        OR sp.staff_role IN ('Kế toán', 'Admin')";
        $stmt = $pdo->query($sqlUsers);
        $userIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $created = false;

        foreach ($userIds as $userId) {
            try {
                NotificationRepository::create([
                    'user_id' => $userId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'link' => $link
                ]);
                $created = true;
            } catch (\Exception $e) {
                error_log("Error creating payment due notification for user {$userId}: " . $e->getMessage());
            }
        }

        return $created;
    }

    /**
     * Lấy thống kê chi tiết về thanh toán phiếu nhập
     */
    public static function getPaymentDueStats(): array
    {
        $pdo = DB::pdo();

        // Phiếu quá hạn
        $sqlOverdue = "SELECT COUNT(*) 
                       FROM purchase_orders 
                       WHERE due_date IS NOT NULL 
                         AND payment_status IN ('0', '1')
                         AND due_date < CURDATE()";
        $overdue = (int) $pdo->query($sqlOverdue)->fetchColumn();

        // Phiếu đến hạn hôm nay
        $sqlToday = "SELECT COUNT(*) 
                     FROM purchase_orders 
                     WHERE due_date IS NOT NULL 
                       AND payment_status IN ('0', '1')
                       AND due_date = CURDATE()";
        $dueToday = (int) $pdo->query($sqlToday)->fetchColumn();

        // Phiếu đến hạn trong 3 ngày
        $sqlNext3Days = "SELECT COUNT(*) 
                         FROM purchase_orders 
                         WHERE due_date IS NOT NULL 
                           AND payment_status IN ('0', '1')
                           AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
        $dueIn3Days = (int) $pdo->query($sqlNext3Days)->fetchColumn();

        // Tổng số tiền quá hạn
        $sqlOverdueAmount = "SELECT IFNULL(SUM(total_amount - paid_amount), 0)
                             FROM purchase_orders 
                             WHERE due_date IS NOT NULL 
                               AND payment_status IN ('0', '1')
                               AND due_date < CURDATE()";
        $overdueAmount = (float) $pdo->query($sqlOverdueAmount)->fetchColumn();

        return [
            'overdue' => $overdue,
            'due_today' => $dueToday,
            'due_in_3_days' => $dueIn3Days,
            'total_critical' => $overdue + $dueToday,
            'overdue_amount' => $overdueAmount,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Dọn dẹp thông báo cũ hơn 30 ngày
     */
    public static function cleanupOldNotifications(): int
    {
        $pdo = DB::pdo();
        $sql = "DELETE FROM notifications 
                WHERE (title LIKE '%thanh toán phiếu nhập%' 
                   OR title LIKE '%hẹn thanh toán%'
                   OR title LIKE '%quá hạn thanh toán%')
                  AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $pdo->query($sql);
        return $stmt->rowCount();
    }
}
