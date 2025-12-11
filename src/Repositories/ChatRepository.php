<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\DB;
use PDO;

class ChatRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = DB::getConnection();
    }

    /**
     * Lấy danh sách phiên chat
     * 
     * @param string $status - active, closed, waiting
     * @param int|null $staffId - Filter by staff (for support_staff role)
     * @return array
     */
    public function getSessions(string $status = 'active', ?int $staffId = null): array
    {
        try {
            $query = "
                SELECT 
                    cs.*,
                    u.full_name as customer_name,
                    u.email as customer_email,
                    staff.full_name as staff_name,
                    (SELECT COUNT(*) FROM chat_messages 
                     WHERE session_id = cs.id 
                     AND sender_type = 'customer' 
                     AND is_read = 0) as unread_count,
                    (SELECT message FROM chat_messages 
                     WHERE session_id = cs.id 
                     ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM chat_messages 
                     WHERE session_id = cs.id 
                     ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM chat_sessions cs
                LEFT JOIN users u ON cs.user_id = u.id
                LEFT JOIN users staff ON cs.assigned_staff_id = staff.id
                WHERE cs.status = ?
            ";

            $params = [$status];

            // Filter by staff if provided
            // Staff can see: sessions assigned to them OR unassigned sessions
            if ($staffId !== null) {
                $query .= " AND (cs.assigned_staff_id = ? OR cs.assigned_staff_id IS NULL)";
                $params[] = $staffId;
            }

            $query .= " ORDER BY cs.updated_at DESC LIMIT 50";

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("ChatRepository::getSessions SQL Error: " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Params: " . json_encode($params));
            throw $e;
        }
    }

    /**
     * Lấy thông tin một phiên chat
     */
    public function getSessionById(int $sessionId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM chat_sessions WHERE id = ?
        ");
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Lấy tin nhắn của một phiên
     */
    public function getMessagesBySession(int $sessionId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM chat_messages
            WHERE session_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo tin nhắn mới
     */
    public function createMessage(int $sessionId, string $senderType, ?int $senderId, string $message, ?string $metadata = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_messages (session_id, sender_type, sender_id, message, metadata)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$sessionId, $senderType, $senderId, $message, $metadata]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Đánh dấu tin nhắn đã delivered (staff fetch messages)
     */
    public function markMessagesAsDelivered(int $sessionId): void
    {
        $this->pdo->prepare("
            UPDATE chat_messages 
            SET status = 'delivered', 
                delivered_at = NOW() 
            WHERE session_id = ? 
              AND sender_type = 'customer' 
              AND status = 'sent'
        ")->execute([$sessionId]);
    }

    /**
     * Đánh dấu tin nhắn đã read (staff view chat)
     */
    public function markCustomerMessagesAsRead(int $sessionId): void
    {
        $this->pdo->prepare("
            UPDATE chat_messages 
            SET status = 'read', 
                read_at = NOW(),
                is_read = 1
            WHERE session_id = ? 
              AND sender_type = 'customer' 
              AND status != 'read'
        ")->execute([$sessionId]);
    }

    /**
     * Cập nhật timestamp của session
     */
    public function updateSessionTimestamp(int $sessionId): void
    {
        $this->pdo->prepare("
            UPDATE chat_sessions SET updated_at = NOW() WHERE id = ?
        ")->execute([$sessionId]);
    }

    /**
     * Assign staff vào session
     */
    public function assignStaffToSession(int $sessionId, int $staffId): void
    {
        $this->pdo->prepare("
            UPDATE chat_sessions 
            SET assigned_staff_id = ?, is_ai_mode = 0, status = 'active'
            WHERE id = ?
        ")->execute([$staffId, $sessionId]);
    }

    /**
     * Đóng phiên chat
     */
    public function closeSession(int $sessionId): void
    {
        $this->pdo->prepare("
            UPDATE chat_sessions 
            SET status = 'closed', closed_at = NOW()
            WHERE id = ?
        ")->execute([$sessionId]);
    }

    /**
     * Kiểm tra quyền truy cập session
     */
    public function canStaffAccessSession(int $sessionId, int $staffId): bool
    {
        // Staff can access: sessions assigned to them OR unassigned sessions
        $stmt = $this->pdo->prepare("
            SELECT id FROM chat_sessions 
            WHERE id = ? AND (assigned_staff_id = ? OR assigned_staff_id IS NULL)
        ");
        $stmt->execute([$sessionId, $staffId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Lấy thống kê
     */
    public function getStats(?int $staffId = null): array
    {
        // Active sessions
        $query = "SELECT COUNT(*) FROM chat_sessions WHERE status = 'active'";
        if ($staffId !== null) {
            $query .= " AND assigned_staff_id = $staffId";
        }
        $activeSessions = (int)$this->pdo->query($query)->fetchColumn();

        // Unread messages
        $query = "
            SELECT COUNT(*) FROM chat_messages cm
            JOIN chat_sessions cs ON cm.session_id = cs.id
            WHERE cm.sender_type = 'customer' AND cm.is_read = 0
        ";
        if ($staffId !== null) {
            $query .= " AND cs.assigned_staff_id = $staffId";
        }
        $unreadMessages = (int)$this->pdo->query($query)->fetchColumn();

        // AI vs Staff (only for admin - no staff filter)
        $aiSessions = (int)$this->pdo->query("
            SELECT COUNT(*) FROM chat_sessions 
            WHERE status = 'active' AND is_ai_mode = 1
        ")->fetchColumn();

        return [
            'active_sessions' => $activeSessions,
            'unread_messages' => $unreadMessages,
            'ai_sessions' => $aiSessions,
            'staff_sessions' => $activeSessions - $aiSessions
        ];
    }

    /**
     * Lấy danh sách quick replies
     */
    public function getQuickReplies(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM chat_quick_replies 
            WHERE is_active = 1 
            ORDER BY usage_count DESC, title ASC
            LIMIT 20
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tăng usage count của quick reply
     */
    public function incrementQuickReplyUsage(int $quickReplyId): void
    {
        $this->pdo->prepare("
            UPDATE chat_quick_replies 
            SET usage_count = usage_count + 1 
            WHERE id = ?
        ")->execute([$quickReplyId]);
    }

    /**
     * Lấy tổng số tin nhắn chưa đọc (cho badge)
     */
    public function getTotalUnreadCount(?int $staffId = null): int
    {
        $query = "
            SELECT COUNT(*) FROM chat_messages cm
            JOIN chat_sessions cs ON cm.session_id = cs.id
            WHERE cm.sender_type = 'customer' 
            AND cm.is_read = 0
            AND cs.status = 'active'
        ";

        if ($staffId !== null) {
            $query .= " AND cs.assigned_staff_id = $staffId";
        }

        return (int)$this->pdo->query($query)->fetchColumn();
    }
}
