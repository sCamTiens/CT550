<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Models\Entities\Notification;
use \PDO;

class NotificationRepository
{
    /**
     * Lấy tất cả thông báo của user (mới nhất trước)
     */
    public static function getByUser(int $userId, int $limit = 50)
    {
        $pdo = DB::pdo();
        $limit = (int)$limit; // Sanitize
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT $limit";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Notification($row), $rows);
    }

    /**
     * Đếm số thông báo chưa đọc của user
     */
    public static function countUnread(int $userId): int
    {
        $pdo = DB::pdo();
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Đánh dấu 1 thông báo đã đọc
     */
    public static function markAsRead(int $id, int $userId): bool
    {
        $pdo = DB::pdo();
        $sql = "UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Đánh dấu tất cả thông báo của user đã đọc
     */
    public static function markAllAsRead(int $userId): bool
    {
        $pdo = DB::pdo();
        $sql = "UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$userId]);
    }

    /**
     * Tạo thông báo mới
     */
    public static function create(array $data): ?int
    {
        $pdo = DB::pdo();
        $sql = "INSERT INTO notifications (user_id, type, title, message, link) 
                VALUES (:user_id, :type, :title, :message, :link)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'type' => $data['type'] ?? 'info',
            'title' => $data['title'],
            'message' => $data['message'],
            'link' => $data['link'] ?? null
        ]);
        return $pdo->lastInsertId();
    }

    /**
     * Kiểm tra và tạo thông báo cảnh báo tồn kho thấp
     * Chỉ tạo nếu chưa có thông báo tương tự trong vòng 24h
     */
    public static function createLowStockAlert(int $productId, string $productName, int $currentQty, int $safetyStock)
    {
        $pdo = DB::pdo();
        
        // Lấy danh sách admin/kho để gửi thông báo
        $sqlUsers = "SELECT u.id FROM users u 
                     LEFT JOIN staff_profiles sp ON sp.user_id = u.id 
                     WHERE u.role_id IN (2, 3, 4) 
                        OR sp.staff_role IN ('Kho', 'Admin')";
        $stmt = $pdo->query($sqlUsers);
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($userIds as $userId) {
            // Kiểm tra xem đã có thông báo tương tự trong 24h chưa
            $sqlCheck = "SELECT id FROM notifications 
                         WHERE user_id = ? 
                           AND title LIKE ? 
                           AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                         LIMIT 1";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([$userId, "%$productName%"]);
            
            if (!$stmtCheck->fetch()) {
                // Chưa có thông báo, tạo mới
                self::create([
                    'user_id' => $userId,
                    'type' => 'warning',
                    'title' => "Cảnh báo tồn kho thấp",
                    'message' => "Sản phẩm '$productName' chỉ còn $currentQty (mức an toàn: $safetyStock)",
                    'link' => '/admin/stocks'
                ]);
            }
        }
    }

    /**
     * Xóa thông báo
     */
    public static function delete(int $id, int $userId): bool
    {
        $pdo = DB::pdo();
        $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }
}
