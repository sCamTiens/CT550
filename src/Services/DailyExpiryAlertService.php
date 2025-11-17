<?php
namespace App\Services;

use App\Core\DB;
use App\Models\Repositories\NotificationRepository;

/**
 * Service kiểm tra hàng hết hạn/sắp hết hạn và tạo thông báo hàng ngày
 * Chạy vào 7h sáng mỗi ngày để cảnh báo sản phẩm hết hạn sử dụng
 */
class DailyExpiryAlertService
{
    /**
     * Reset và tạo lại thông báo hàng hết hạn cho ngày mới (Chạy lúc 7h sáng)
     * - XÓA HOÀN TOÀN tất cả thông báo hết hạn cũ (cả đã đọc và chưa đọc)
     * - Tạo thông báo mới cho:
     *   + Sản phẩm ĐÃ HẾT HẠN (exp_date < hôm nay)
     *   + Sản phẩm SẮP HẾT HẠN (exp_date trong vòng 7 ngày tới)
     *   + Sản phẩm SẮP HẾT HẠN GẦN (exp_date trong vòng 3 ngày tới)
     * 
     * @return array Kết quả thực hiện
     */
    public static function runDailyCheck(): array
    {
        $pdo = DB::pdo();
        $result = [
            'status' => 'skipped',
            'deleted_old_notifications' => 0,
            'expired_batches' => 0,
            'expiring_soon_batches' => 0,
            'expiring_critical_batches' => 0,
            'notifications_created' => 0,
            'old_notifications_cleaned' => 0,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        try {
            // Tạo bảng system_jobs nếu chưa có
            $pdo->exec("CREATE TABLE IF NOT EXISTS system_jobs (
                job_name VARCHAR(100) PRIMARY KEY,
                last_run DATETIME DEFAULT NULL
            )");

            // Kiểm tra đã chạy hôm nay chưa
            $stmt = $pdo->prepare("SELECT last_run FROM system_jobs WHERE job_name = 'daily_expiry_alert_check'");
            $stmt->execute();
            $lastRun = $stmt->fetchColumn();

            if ($lastRun && date('Y-m-d', strtotime($lastRun)) === date('Y-m-d')) {
                // Nếu đã chạy trong ngày → bỏ qua
                return $result;
            }

            // Nếu chưa có bản ghi → thêm mới
            $pdo->exec("INSERT IGNORE INTO system_jobs (job_name) VALUES ('daily_expiry_alert_check')");

            // ====================== BẮT ĐẦU XỬ LÝ CHÍNH ======================
            // Bước 1: Xóa các thông báo hết hạn cũ (reset mỗi ngày)
            $sqlDelete = "DELETE FROM notifications 
                         WHERE title LIKE '%hết hạn%' OR title LIKE '%quá hạn%'";
            $stmtDelete = $pdo->query($sqlDelete);
            $result['deleted_old_notifications'] = $stmtDelete->rowCount();

            // Bước 2: Lấy các lô hàng đã hết hạn (exp_date < hôm nay)
            $sqlExpired = "SELECT 
                            pb.id, pb.product_id, pb.batch_code, pb.exp_date, pb.current_qty,
                            p.name, p.sku
                        FROM product_batches pb
                        JOIN products p ON p.id = pb.product_id
                        WHERE pb.exp_date < CURDATE() 
                          AND pb.current_qty > 0
                          AND pb.is_active = 1
                          AND p.is_active = 1
                        ORDER BY pb.exp_date ASC";
            $stmtExpired = $pdo->query($sqlExpired);
            $expiredBatches = $stmtExpired->fetchAll(\PDO::FETCH_ASSOC);
            $result['expired_batches'] = count($expiredBatches);

            // Bước 3: Lấy các lô hàng sắp hết hạn (exp_date trong vòng 3 ngày)
            $sqlExpiringCritical = "SELECT 
                                    pb.id, pb.product_id, pb.batch_code, pb.exp_date, pb.current_qty,
                                    p.name, p.sku,
                                    DATEDIFF(pb.exp_date, CURDATE()) as days_left
                                FROM product_batches pb
                                JOIN products p ON p.id = pb.product_id
                                WHERE pb.exp_date >= CURDATE() 
                                  AND pb.exp_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                                  AND pb.current_qty > 0
                                  AND pb.is_active = 1
                                  AND p.is_active = 1
                                ORDER BY pb.exp_date ASC";
            $stmtCritical = $pdo->query($sqlExpiringCritical);
            $expiringCritical = $stmtCritical->fetchAll(\PDO::FETCH_ASSOC);
            $result['expiring_critical_batches'] = count($expiringCritical);

            // Bước 4: Lấy các lô hàng sắp hết hạn (exp_date trong vòng 4-7 ngày)
            $sqlExpiringSoon = "SELECT 
                                pb.id, pb.product_id, pb.batch_code, pb.exp_date, pb.current_qty,
                                p.name, p.sku,
                                DATEDIFF(pb.exp_date, CURDATE()) as days_left
                            FROM product_batches pb
                            JOIN products p ON p.id = pb.product_id
                            WHERE pb.exp_date > DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                              AND pb.exp_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                              AND pb.current_qty > 0
                              AND pb.is_active = 1
                              AND p.is_active = 1
                            ORDER BY pb.exp_date ASC";
            $stmtSoon = $pdo->query($sqlExpiringSoon);
            $expiringSoon = $stmtSoon->fetchAll(\PDO::FETCH_ASSOC);
            $result['expiring_soon_batches'] = count($expiringSoon);

            // Log số lượng
            error_log("Daily Expiry Check at " . date('Y-m-d H:i:s') . ": " . 
                     "Expired: {$result['expired_batches']}, " .
                     "Critical: {$result['expiring_critical_batches']}, " .
                     "Soon: {$result['expiring_soon_batches']}");

            // Bước 5: Tạo thông báo cho lô hàng đã hết hạn
            foreach ($expiredBatches as $batch) {
                $created = self::createNotificationForBatch(
                    $batch,
                    'expired'
                );
                if ($created) {
                    $result['notifications_created']++;
                }
            }

            // Bước 6: Tạo thông báo cho lô hàng sắp hết hạn (critical - 3 ngày)
            foreach ($expiringCritical as $batch) {
                $created = self::createNotificationForBatch(
                    $batch,
                    'critical'
                );
                if ($created) {
                    $result['notifications_created']++;
                }
            }

            // Bước 7: Tạo thông báo cho lô hàng sắp hết hạn (soon - 7 ngày)
            foreach ($expiringSoon as $batch) {
                $created = self::createNotificationForBatch(
                    $batch,
                    'warning'
                );
                if ($created) {
                    $result['notifications_created']++;
                }
            }

            // Bước 8: Dọn dẹp thông báo cũ hơn 30 ngày
            $result['old_notifications_cleaned'] = self::cleanupOldNotifications();

            // Cập nhật thời gian chạy gần nhất
            $stmtUpdate = $pdo->prepare("UPDATE system_jobs SET last_run = NOW() WHERE job_name = 'daily_expiry_alert_check'");
            $stmtUpdate->execute();

            $result['status'] = 'completed';
            error_log("Daily Expiry Check completed: " . json_encode($result));
            return $result;

        } catch (\Exception $e) {
            error_log("Error in daily expiry alert check: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tạo thông báo cho một lô hàng cụ thể
     * Gửi đến tất cả admin, quản lý và nhân viên kho
     * 
     * @param array $batch Thông tin lô hàng
     * @param string $alertType Loại cảnh báo: 'expired', 'critical', 'warning'
     * @return bool True nếu đã tạo thông báo thành công
     */
    private static function createNotificationForBatch(array $batch, string $alertType): bool
    {
        $pdo = DB::pdo();

        // Lấy danh sách user cần nhận thông báo (admin, quản lý, nhân viên kho)
        $sqlUsers = "SELECT DISTINCT u.id 
                     FROM users u 
                     LEFT JOIN staff_profiles sp ON sp.user_id = u.id 
                     WHERE u.role_id IN (2, 3, 4) 
                        OR sp.staff_role IN ('Kho', 'Admin')";
        $stmt = $pdo->query($sqlUsers);
        $userIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $created = false;

        foreach ($userIds as $userId) {
            // Xác định loại và nội dung thông báo
            $productName = $batch['name'];
            $sku = $batch['sku'];
            $batchCode = $batch['batch_code'];
            $currentQty = $batch['current_qty'];
            $expDate = date('d/m/Y', strtotime($batch['exp_date']));

            switch ($alertType) {
                case 'expired':
                    $type = 'error';
                    $title = '⚠️ Sản phẩm đã quá hạn sử dụng';
                    $daysOverdue = abs((int)((strtotime($batch['exp_date']) - strtotime('today')) / 86400));
                    $message = "Lô hàng <strong>{$productName}</strong> (SKU: <strong>{$sku}</strong>) - " .
                              "Mã lô: <strong>{$batchCode}</strong> <strong class='text-red-600'>ĐÃ QUÁ HẠN {$daysOverdue} NGÀY</strong> " .
                              "(Hạn dùng: {$expDate}). Còn lại <strong>{$currentQty}</strong> sản phẩm cần xử lý!";
                    break;

                case 'critical':
                    $type = 'error';
                    $title = '🔴 Sản phẩm sắp hết hạn (dưới 3 ngày)';
                    $daysLeft = $batch['days_left'];
                    $message = "Lô hàng <strong>{$productName}</strong> (SKU: <strong>{$sku}</strong>) - " .
                              "Mã lô: <strong>{$batchCode}</strong> sẽ hết hạn trong <strong class='text-red-600'>{$daysLeft} NGÀY</strong> " .
                              "(Hạn dùng: {$expDate}). Còn lại <strong>{$currentQty}</strong> sản phẩm.";
                    break;

                case 'warning':
                    $type = 'warning';
                    $title = '⚠️ Sản phẩm sắp hết hạn (dưới 7 ngày)';
                    $daysLeft = $batch['days_left'];
                    $message = "Lô hàng <strong>{$productName}</strong> (SKU: <strong>{$sku}</strong>) - " .
                              "Mã lô: <strong>{$batchCode}</strong> sẽ hết hạn trong <strong>{$daysLeft} ngày</strong> " .
                              "(Hạn dùng: {$expDate}). Còn lại <strong>{$currentQty}</strong> sản phẩm.";
                    break;

                default:
                    return false;
            }

            // Tạo thông báo
            try {
                NotificationRepository::create([
                    'user_id' => $userId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'link' => '/admin/batches'
                ]);
                $created = true;
            } catch (\Exception $e) {
                error_log("Error creating expiry notification for user {$userId}: " . $e->getMessage());
            }
        }

        return $created;
    }

    /**
     * Lấy thống kê chi tiết về hàng hết hạn
     * 
     * @return array Thống kê
     */
    public static function getExpiryStats(): array
    {
        $pdo = DB::pdo();

        // Lô hàng đã hết hạn
        $sqlExpired = "SELECT COUNT(*) 
                       FROM product_batches pb
                       JOIN products p ON p.id = pb.product_id
                       WHERE pb.exp_date < CURDATE() 
                         AND pb.current_qty > 0
                         AND pb.is_active = 1
                         AND p.is_active = 1";
        $expired = (int) $pdo->query($sqlExpired)->fetchColumn();

        // Lô hàng sắp hết hạn (dưới 3 ngày)
        $sqlCritical = "SELECT COUNT(*) 
                        FROM product_batches pb
                        JOIN products p ON p.id = pb.product_id
                        WHERE pb.exp_date >= CURDATE() 
                          AND pb.exp_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                          AND pb.current_qty > 0
                          AND pb.is_active = 1
                          AND p.is_active = 1";
        $critical = (int) $pdo->query($sqlCritical)->fetchColumn();

        // Lô hàng sắp hết hạn (4-7 ngày)
        $sqlWarning = "SELECT COUNT(*) 
                       FROM product_batches pb
                       JOIN products p ON p.id = pb.product_id
                       WHERE pb.exp_date > DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                         AND pb.exp_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                         AND pb.current_qty > 0
                         AND pb.is_active = 1
                         AND p.is_active = 1";
        $warning = (int) $pdo->query($sqlWarning)->fetchColumn();

        // Tổng số lô hàng còn hoạt động
        $sqlActive = "SELECT COUNT(*) 
                      FROM product_batches pb
                      JOIN products p ON p.id = pb.product_id
                      WHERE pb.is_active = 1 
                        AND pb.current_qty > 0
                        AND p.is_active = 1";
        $activeBatches = (int) $pdo->query($sqlActive)->fetchColumn();

        return [
            'expired' => $expired,
            'critical' => $critical,
            'warning' => $warning,
            'total_issues' => $expired + $critical + $warning,
            'active_batches' => $activeBatches,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Dọn dẹp thông báo hết hạn cũ (> 30 ngày) đã đọc
     * 
     * @return int Số thông báo đã xóa
     */
    public static function cleanupOldNotifications(): int
    {
        $pdo = DB::pdo();

        $sql = "DELETE FROM notifications 
                WHERE (title LIKE '%hết hạn%' OR title LIKE '%quá hạn%')
                  AND is_read = 1 
                  AND read_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";

        $stmt = $pdo->query($sql);
        return $stmt->rowCount();
    }
}
