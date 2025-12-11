<?php

namespace App\Support;

use App\Core\DB;
use PDO;

class NotificationHelper
{
    /**
     * Gửi thông báo cho user
     * 
     * @param int $userId ID của user nhận thông báo
     * @param string $title Tiêu đề
     * @param string $message Nội dung
     * @param string|null $link Link liên kết (tùy chọn)
     * @param string $type Loại thông báo (info, success, warning, etc)
     */
    public static function send(int $userId, string $title, string $message, ?string $link = null, string $type = 'info'): bool
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare("
                INSERT INTO user_notifications (user_id, title, message, related_link, type, created_at, is_read)
                VALUES (?, ?, ?, ?, ?, NOW(), 0)
            ");
            return $stmt->execute([$userId, $title, $message, $link, $type]);
        } catch (\Exception $e) {
            error_log("Failed to send notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Đánh dấu tất cả là đã đọc
     */
    public static function markAllAsRead(int $userId): bool
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Đếm số thông báo chưa đọc
     */
    public static function countUnread(int $userId): int
    {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
