<?php

/**
 * Chat Session Auto-Archive Scheduler
 * 
 * Chạy định kỳ mỗi 5 phút để:
 * 1. Archive các sessions không phản hồi sau 15 phút (nhân viên)
 * 2. Archive các sessions của bot khi hết ca (22:00-6:00)
 * 3. Cleanup sessions cũ hơn 30 ngày
 * 
 * Setup cron job:
 * *‍/5 * * * * php /path/to/chat_auto_archive.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Core/DB.php';

use App\Core\DB;

try {
    $pdo = DB::getConnection();
    $now = date('Y-m-d H:i:s');
    $currentHour = (int)date('H');

    echo "[" . date('Y-m-d H:i:s') . "] Starting auto-archive process...\n";

    // ============================================
    // 1. AUTO-ARCHIVE NHÂN VIÊN (6:00-22:00)
    // Sessions không phản hồi sau 15 phút
    // ============================================
    if ($currentHour >= 6 && $currentHour < 22) {
        echo "Checking staff sessions for auto-archive (15min threshold)...\n";

        $stmt = $pdo->prepare("
            UPDATE chat_sessions 
            SET status = 'archived',
                auto_archived = 1,
                archived_at = NOW()
            WHERE status = 'active'
            AND is_ai_mode = 0
            AND last_staff_activity IS NOT NULL
            AND last_staff_activity < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            AND (last_customer_activity IS NULL OR last_customer_activity < last_staff_activity)
        ");
        $stmt->execute();
        $archivedStaff = $stmt->rowCount();

        if ($archivedStaff > 0) {
            echo "✅ Auto-archived $archivedStaff staff sessions (no response for 15min)\n";
        }
    }

    // ============================================
    // 2. AUTO-ARCHIVE BOT KHI HẾT CA (22:00-6:00)
    // Lưu trữ sessions của bot để chuyển giao nhân viên
    // ============================================
    if ($currentHour >= 6 && $currentHour < 7) {
        echo "Archiving bot shift sessions (22:00-6:00 handover)...\n";

        $stmt = $pdo->prepare("
            UPDATE chat_sessions 
            SET status = 'archived',
                auto_archived = 1,
                archived_at = NOW(),
                bot_shift_end = NOW()
            WHERE status = 'active'
            AND is_ai_mode = 1
            AND created_at >= DATE_SUB(NOW(), INTERVAL 8 HOUR)
        ");
        $stmt->execute();
        $archivedBot = $stmt->rowCount();

        if ($archivedBot > 0) {
            echo "✅ Archived $archivedBot bot shift sessions for handover\n";

            // Thêm note chuyển giao
            $pdo->prepare("
                INSERT INTO chat_messages (session_id, sender_type, sender_id, message, metadata)
                SELECT id, 'system', NULL, 
                       'Ca làm việc của Bot đã kết thúc. Nhân viên sẽ tiếp tục hỗ trợ bạn từ 6:00 sáng.',
                       JSON_OBJECT('type', 'handover', 'from', 'bot', 'to', 'staff')
                FROM chat_sessions  
                WHERE archived_at IS NOT NULL 
                AND bot_shift_end IS NOT NULL
                AND archived_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ")->execute();
        }
    }

    // ============================================
    // 3. CLEANUP - XÓA SESSIONS CŨ HƠN 30 NGÀY
    // Chỉ xóa sessions đã archived và không hoạt động
    // ============================================
    echo "Cleaning up old archived sessions (>30 days)...\n";

    // Trước tiên, xóa messages của sessions cũ
    $pdo->prepare("
        DELETE cm FROM chat_messages cm
        INNER JOIN chat_sessions cs ON cm.session_id = cs.id
        WHERE cs.status = 'archived'
        AND cs.archived_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->execute();

    // Sau đó xóa sessions
    $stmt = $pdo->prepare("
        DELETE FROM chat_sessions
        WHERE status = 'archived'
        AND archived_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $deletedOld = $stmt->rowCount();

    if ($deletedOld > 0) {
        echo "✅ Deleted $deletedOld old archived sessions (>30 days)\n";
    }

    // ============================================
    // 4. AUTO-REOPEN ARCHIVED SESSIONS
    // Nếu khách hàng phản hồi, tự động mở lại
    // (Logic này nên ở ChatController, nhưng double-check ở đây)
    // ============================================
    echo "Checking for archived sessions with new customer messages...\n";

    $stmt = $pdo->prepare("
        UPDATE chat_sessions cs
        INNER JOIN chat_messages cm ON cs.id = cm.session_id
        SET cs.status = 'active',
            cs.archived_at = NULL,
            cs.auto_archived = 0
        WHERE cs.status = 'archived'
        AND cm.sender_type = 'customer'
        AND cm.created_at > cs.archived_at
        GROUP BY cs.id
    ");
    $stmt->execute();
    $reopened = $stmt->rowCount();

    if ($reopened > 0) {
        echo "✅ Auto-reopened $reopened archived sessions (customer replied)\n";
    }

    echo "\n[" . date('Y-m-d H:i:s') . "] Auto-archive process completed.\n";
    echo "Summary: Staff=$archivedStaffBot=$archivedBot, Deleted=$deletedOld, Reopened=$reopened\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
